<?php namespace api;

use Illuminate\Support\Facades\Config;
use ticketmodel\RequestModel;
use ticketmodel\LiveChatModel;
use ticketmodel\CaseTicketModel;
use ticketmodel\AssignModel;
use User;
use sellermodel\UserInfoModel;
use sellermodel\FeeModel;

class MigrateLiveChatCtrl extends \BaseController {


    public function getIndex() {
        $config = array();
        $config['url'] = Config::get('livechat.url').'chats';
        $config['method'] = 'GET';
        $config['email'] = Config::get('livechat.email');
        $config['api_key'] = Config::get('livechat.api_key');
        $num = 0;
        $response = $this->_request($config);

        if(!empty($response->chats)) {
            foreach($response->chats as $chat) {
                $checkHasTicket = LiveChatModel::where('livechat_id',$chat->id)->first();
                if($chat->pending==false && (empty($checkHasTicket) || $checkHasTicket->duration !=$chat->duration)) {
                    $hasReply = false;
                    foreach($chat->prechat_survey as $userInfo) {
                        $key = $userInfo->key;
                        $value = $userInfo->value;
                        if($key=="E-mail:") {
                            $email = $value;
                        } else if($key=='Tên quý khách:') {
                            $fullname = $value;
                        } else if($key=='Số di động:') {
                            $phone = $value;
                        } else if($key=='Yêu cầu hoặc câu hỏi:') {
                            $question = $value;
                        }
                    }

                    if(!empty($email)){
                        $totalMessage = count($chat->messages);

                        $content = "<blockquote>";
                        $timeReply = 0;
                        foreach($chat->messages as $k => $message) {
                            if($k==1) {
                                $timeStart = $message->timestamp;
                            }
                            if($k>0 && $message->user_type!="visitor") {
                                $hasReply = true;
                                $timeReply = $message->timestamp - $timeStart;
                            }

                            if($k>0) {
                                $content .= "<b>".$message->author_name . '</b>: <i>(' .date("d/m/Y H:i",$message->timestamp). ')</i> '.$message->text .'<br/>';
                            }
                        }
                        $content .= "</blockquote>";


                        //check & create user
                        $customer = User::firstOrNew(['email'=>$email]);
                        if(!$customer->exists) {
                            //create new user
                            $password = $this->RandomString();
                            $customer->email = $email;
                            $customer->password = md5(md5($password));
                            $customer->fullname = $fullname;
                            $customer->phone = $phone;
                            $customer->time_create = $this->time();
                            $customer->time_last_login = $this->time();
                            $customer->save();
                            $customerID = $customer->id;
                            UserInfoModel::firstOrCreate(array('user_id' => $customerID, 'notification' =>  1));
                            FeeModel::firstOrCreate(array('user_id' => $customerID, 'shipping_fee' => 2, 'cod_fee' => 1));
                        } else {
                            $customerID = false;
                        }

                        //check & create ticket
                        $ticketContent = "Hệ thống Shipchung ghi nhận được cuộc hội thoại chat của khách hàng trên hệ thống vào lúc " . date("d/m/Y H:i",$chat->started_timestamp) . "\n\n\n".
                            "Nội dung như sau:\n\n\n".
                            $content;
                        if($customerID) {
                            $ticketContent .= "\n\n\nChúng tôi không tìm thấy tài khoản của quý khách trên hệ thống nên tự động tạo một tài khoản trên shipchung.vn với email là:\n\n".
                                "<p>Email đăng nhập: " . $customer->email."</p>".
                                "<p>Mật khẩu: " . $password."</p>".
                                "<p>Họ tên: " . $fullname."</p><br />".
                                "Quý khách hàng vui lòng đăng nhập vào hệ thống seller.shipchung.vn và đổi lại mật khẩu mới. Trường hợp quý khách đã có một tài khoản khác, vui lòng bỏ qua tài khoản này.";
                        }
                        $ticketContent .= "\n\n\n Nội dung này lưu lại nhằm mục đích nâng cao dịch vụ khách hàng. Rất mong quý khách hàng tiếp tục tin tưởng và sử dụng dịch vụ của Shipchung.vn";
                        if(empty($checkHasTicket)) {
                            $request =  new RequestModel;
                            $request->status = 'NEW_ISSUE';
                            if($hasReply){
                                $request->status = 'CLOSED';
                            }
                            $request->time_reply = $timeReply;
                        } else {
                            $request = RequestModel::where('id',$checkHasTicket->ticket_id)->first();
                        }
                        $request->user_id = $customer->id;
                        $request->title = $question;
                        $request->time_create = $chat->started_timestamp;
                        $request->time_update = $chat->ended_timestamp;
                        $request->source = 'live chat';
                        $request->content = $ticketContent;
                        $request->notification = 1;
                        $request->save();
                        ++$num;

                        //assign operators
                        if(!empty($chat->operators)) {
                            foreach($chat->operators as $operator) {
                                $user = User::where('email',$operator->email)->first();
                                if(!empty($user)) {
                                    $ticketAssign = AssignModel::firstOrNew(['ticket_id'=>$request->id, 'assign_id' => $user->id]);
                                    if(!$ticketAssign->exists) {
                                        $ticketAssign->user_id = $user->id;
                                        $ticketAssign->time_create = $chat->started_timestamp;
                                        $ticketAssign->save();
                                    }
                                }
                            }
                        }

                        //log live chat
                        $livechat = LiveChatModel::firstOrNew(['ticket_id'=>$request->id]);
                        $livechat->ticket_id = $request->id;
                        $livechat->livechat_id = $chat->id;
                        $livechat->duration = $chat->duration;
                        $livechat->agent_name = !empty($chat->operators) ? $chat->operators[0]->display_name : "";

                        if(!$livechat->exists) {
                            $livechat->time_create = $this->time();
                        }
                        $livechat->save();

                        //phân loại
                        if(empty($checkHasTicket)) {
                            $caseTicket = new CaseTicketModel;
                            $caseTicket->active = 1;
                            $caseTicket->ticket_id = $request->id;
                            $type = ($hasReply) ? 58 : 59;
                            $caseTicket->type_id = $type;
                            //$caseTicket->case_id = 8;
                            $caseTicket->save();
                        }
                    }
                }else {
                    return "Khong co ticket";
                }
            }
        }

        return $num. " ticket da duoc cap nhat tu live chat";

    }

    private function _request($config) {

        $process = curl_init($config['url']);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($process, CURLOPT_SSL_VERIFYHOST, 2);

        curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($process, CURLOPT_USERPWD, $config['email'] . ':' . $config['api_key']);
        curl_setopt($process, CURLOPT_ENCODING ,"");
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        $return = curl_exec($process);

        curl_close($process);
        return json_decode($return);
    }


    private function RandomString($strLength=10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randstring = '';
        for ($i = 0; $i < $strLength; $i++) {
            $randstring .= $characters[rand(0, (strlen($characters)-1))];
        }
        return $randstring;
    }


    /*public function getReport(){
        $Model = RequestModel::where('source', 'live chat')->where('time_reply', '>', 0)
    }*/
}