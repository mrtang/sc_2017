<?php
use ticketmodel\RequestModel;
use ticketmodel\FeedbackModel;
use omsmodel\CustomerAdminModel;
use ordermodel\OrdersModel;
use omsmodel\AppNotifyModel;
use sellermodel\UserInfoModel;
use omsmodel\SellerModel;
use sellermodel\VimoModel;
use ordermodel\StatusModel;
use ordermodel\PostOfficeModel;

class NoticeController extends \BaseController {
    private $domain = '*';
    /**
     * Display a listing of the resource.
     *
     * @return Response
     * @params $scenario, $user_id
     */
     
    public function __construct(){
        
    }

    private function getUserconfigtransport($user){
        $output = array();
        if(!empty($user)){
            $userConfig = UserConfigTransportModel::where('user_id',$user)->where('transport_id',1)->first();
            if(!empty($userConfig)){
                $userInfo = User::where('id',$user)->first();
                $output = array(
                    'phone' => $userConfig['received'],
                    'fullname' => $userInfo['fullname'],
                    'email' => $userInfo['email']
                );
            }else{
                $userInfo = User::where('id',$user)->first();
                $output = array(
                    'phone' => $userInfo['phone'],
                    'fullname' => $userInfo['fullname'],
                    'email' => $userInfo['email']
                );
            }
        }
        return $output;
    }

    private function getUserconfigfb($user){
        $output = array();
        if(!empty($user)){
            $output = UserConfigTransportModel::where('user_id',$user)->where('transport_id',4)->first();
        }
        return $output;
    }

    //get user notice app
    private function getUserapp($id){
        if($id > 0){
            $deviceToken = UserInfoModel::where('user_id',$id)->first();
            $configTransport = UserConfigTransportModel::where('user_id',$id)->where('transport_id',5)->first();
            if(!empty($configTransport) && $configTransport['active'] == 1){
                if($deviceToken['android_device_token'] != '' || $deviceToken['ios_device_token'] != ''){
                    return $deviceToken;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function getSendmailticket(){
        $start = $this->time() - 6*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',7)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        $items = json_decode($data['data'],1);
        $toEmail = $data['received'];

        $template = TemplateModel::where('id',$data['template_id'])->first();
        if(!empty($items)){
            $html = DbView::make($template)->with(['code' => $items['id'],'fullname' => $items['fullname'],'title' => $items['title'],'content' => $items['content']])->render();
            Mail::send('emails.template.demo', array('html' => $html) , function($message) use ($toEmail,$items)
            {
                return $message->to($toEmail)->subject('[Shipchung.vn] #'.$items['id'].' - ' .$items['title']);
            });
            $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
            $contents = array(
                'error'     => false,
                'message'   => 'Success!!!',
                'data'      => ''
            );
            return Response::json($contents);
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!',
                'data'      => ''
            );
            return Response::json($contents);
        }
    }
    //send mail when assign
    public function getSendmailwhenassign(){
        $start = $this->time() - 6*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',17)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        $items = json_decode($data['data'],1);
        $toEmail = $data['received'];

        $template = TemplateModel::where('id',$data['template_id'])->first();
        if(!empty($items)){
            $html = DbView::make($template)->with(['code' => $items['id'],'fullname' => $items['fullname'],'title' => $items['title'],'content' => $items['content']])->render();
            Mail::send('emails.template.demo', array('html' => $html) , function($message) use ($toEmail,$items)
            {
                return $message->to($toEmail)->subject('[Shipchung.vn] #'.$items['id'].' - ' .$items['title']);
            });
            $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
            $contents = array(
                'error'     => false,
                'message'   => 'Success!!!',
                'data'      => ''
            );
            return Response::json($contents);
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!',
                'data'      => ''
            );
            return Response::json($contents);
        }
    }
    public function getSendmailwhenreplyticket(){
        $start = $this->time() - 1*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',8)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        $items = json_decode($data['data'],1);
        $toEmail = $data['received'];

        $template = TemplateModel::where('id',$data['template_id'])->first();
        
        if(!empty($items)){
            $html = DbView::make($template)->with(['code' => $items['ticket_id'],'ticket_title' => $items['title_ticket'],'time_ticket' => date("H:i d/m/Y",$items['ticket_time']),'content_ticket' => $items['content_ticket'],'content_feed' => $items['content_feed'],'time_feedback' => date("H:i d/m/Y",$items['feed_time'])])->render();
            Mail::send('emails.template.demo', array('html' => $html) , function($message) use ($toEmail,$items)
            {
                return $message->to($toEmail)->subject('[ShipChung] Re: #'.$items['ticket_id'].' - '.$items['title_ticket']);
            });
            $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
            $contents = array(
                'error'     => false,
                'message'   => 'Success!!!',
                'data'      => ''
            );
            return Response::json($contents);
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!',
                'data'      => ''
            );
            return Response::json($contents);
        }
    }

    public function getSendsmsticket(){
        return 1;
        $LMongo         = new LMongo;
        $dataInsert     = array();
        $data = FeedbackModel::where('source','sms')->where('notification',0)->first();
        if(!empty($data)){
            $dataTicket = RequestModel::where('id',$data['ticket_id'])->first();
            if(!empty($dataTicket)){
                $infoUserTicket = $this->getUserconfigtransport($dataTicket['user_id']);
                $infoUserFeedback = User::where('id',$data['user_id'])->first();
                if(!empty($infoUserTicket)){
                    $toPhone = $infoUserTicket['phone'];
                    $toPhone = str_replace(array(';','.',' ','/','|'), ',', $toPhone); 

                    if($toPhone == ''){
                        $update = FeedbackModel::where('id',$data['id'])->update(array('notification' => 1));
                        return Response::json(array('error' => false, 'message' => 'nsuccess'));
                    }

                    $arrPhone = array();
                    if($toPhone != ''){
                        $arrPhone = explode(',', $toPhone);
                    }
                    if(!empty($arrPhone[0])){
                        $dataInsert = array(
                            'to_phone' => $arrPhone[0],
                            'content'  => $data['content'],
                            'time_create' => $this->time(),
                            'status'     => 0,
                            'priority'   => 0,
                            'telco'      => Smslib::CheckPhone($toPhone)
                        );
                    }else{
                        $dataInsert = array(
                            'to_phone' => '',
                            'content'  => $data['content'],
                            'time_create' => $this->time(),
                            'status'     => 0,
                            'priority'   => 0,
                            'telco'      => ''
                        );
                    }

                    //insert vao queue de send mail
                    $dataBuild = array(
                        'title_ticket' => $dataTicket['title'],
                        'content_ticket' => $dataTicket['content'],
                        'ticket_time'   => $dataTicket['time_create'],
                        'content_feed' => $data['content'],
                        'feed_time' => $data['time_create'],
                        'ticket_id' => $dataTicket['id'],
                        'email'     => $infoUserFeedback['email'],
                        'feedback_name' => $infoUserFeedback['fullname']
                    );
                    $dataQueue = array(
                        'scenario_id' => 555,
                        'template_id' => 65,
                        'transport_id' => 1,
                        'user_id'   => $dataTicket['user_id'],
                        'received'  => $arrPhone[0],
                        'data'      => json_encode(array('content' => $data['content'])),
                        'time_create' => $this->time(),
                        'status'   => 1,
                        'time_success' => $this->time() + 360
                    );
                    
                    //
                    $insertQueue = QueueModel::insert($dataQueue);
                    $insert = false;
                    if(!empty($dataInsert)){
                        $insert = $LMongo::collection('log_send_sms')->insert($dataInsert);
                    }
                    if($insert){
                        //update time_success
                        $update = FeedbackModel::where('id',$data['id'])->update(array('notification' => 1));
                        //notification FB
                        $userFb = $this->getUserconfigfb($dataTicket['user_id']);
                        if(!empty($userFb)){
                            $fb = new FacebookController;
                            $response = $fb->notification($userFb['received'],$data['content'],'sms');
                            var_dump($response);
                        }
                        
                        return Response::json(array('error' => false, 'message' => 'success'));
                    }else{
                        return Response::json(array('error' => true, 'message' => 'Not send sms'));
                    }
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Not data user create ticket!!',
                        'data'      => ''
                    );
                    return Response::json($contents);
                }
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not data ticket!!',
                    'data'      => ''
                );
                return Response::json($contents);
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!',
                'data'      => ''
            );
            return Response::json($contents);
        }
    }
    //test send sms
    public function getSendtestsms(){
        $LMongo         = new LMongo;
        $dataInsert = array(
            'to_phone' => '0976395263',
            'content'  => 'Hello world !!!',
            'time_create' => $this->time(),
            'status'     => 0,
            'priority'   => 0,
            'telco'      => Smslib::CheckPhone('0976395263')
        );
        $insert = $LMongo::collection('log_send_sms')->insert($dataInsert);
        if($insert){
            return Response::json(array('error' => false, 'message' => 'success!!'));
        }else{
            return Response::json(array('error' => true, 'message' => 'Not send!'));
        }
    }
    //send sms
    public function getSendsms(){
        $start = strtotime(date('Y-m-d 08:00:00',$this->time()));
        $end = strtotime(date('Y-m-d 21:00:00',$this->time()));
        $timeCurrent = $this->time();
        if($timeCurrent > $start && $timeCurrent < $end){
            $send = Smslib::send();
            var_dump($send);die;
        }else{
            echo ('Fulltime!!');die;
        }
    }
    //send sms active acc
    public function getSendsmsacc(){
        $send = Smslib::sendacc();
        var_dump($send);die;
    }
    //send mail active account
    public function getSendemailactiveaccount(){
        $start = $this->time() - 6*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',4)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $toEmail = $data['received'];
            $items = json_decode($data['data'],1);
            $template = TemplateModel::where('id',$data['template_id'])->first();
            if(!empty($items)){
                $html = DbView::make($template)->with(['fullname' => $items['fullname'],'link_active' => $items['link_active']])->render();
                Mail::send('emails.template.new', array('html' => $html) , function($message) use ($toEmail)
                {
                    return $message->to($toEmail)->subject('[ShipChung] thông báo kích hoạt tài khoản shipchung');
                });
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!!',
                    'data'      => ''
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not send email!!!',
                    'data'      => ''
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
                'data'      => ''
            );
        }
        return Response::json($contents);
    }
    //resend mail active account
    public function getResendemailactiveaccount(){
        $start = $this->time() - 6*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',23)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $toEmail = $data['received'];
            $items = json_decode($data['data'],1);
            $template = TemplateModel::where('id',$data['template_id'])->first();
            if(!empty($items)){
                $html = DbView::make($template)->with(['fullname' => $items['fullname'],'email' => $items['email'],'link_active' => $items['link_active']])->render();
                Mail::send('emails.template.demo', array('html' => $html) , function($message) use ($toEmail)
                {
                    return $message->to($toEmail)->subject('[ShipChung] Thông báo kích hoạt tài khoản shipchung');
                });
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!!',
                    'data'      => ''
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not send email!!!',
                    'data'      => ''
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
                'data'      => ''
            );
        }
        return Response::json($contents);
    }
    //send email order
    public function getSendemailorder(){
        $start = $this->time() - 6*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',12)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $toEmail = $data['received'];
            $items = json_decode($data['data'],1);
            $dataLading = array();
            if(!empty($items['ladings'])){
                foreach($items['ladings'] AS $one){
                    $dataLading[] = (object)$one;
                }
            }

            $template = TemplateModel::where('id',$data['template_id'])->first();
            if(!empty($items)){
                $html = DbView::make($template)->with(['fullname' => $items['fullname'],'count' => $items['count'],'count_success' => $items['count_success'],'count_fail' => $items['count_fail'],'ladings' => $dataLading])->render();
                Mail::send('emails.template.demo', array('html' => $html) , function($message) use ($toEmail)
                {
                    return $message->to($toEmail)->subject('[ShipChung] Thông báo tổng hợp vận đơn trong ngày '.date("d/m/Y",strtotime("-1 day")));
                });
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!!',
                );
            }else{
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not send email!!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
            );
        }
        return Response::json($contents);
    }
    
    //send email delivery fail
    public function getSendemaildeliveryfail(){
        $start = $this->time() - 6*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',14)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $toEmail = $data['received'];
            $items = json_decode($data['data'],1);
            $dataLading = array();
            if(!empty($items['ladings'])){
                foreach($items['ladings'] AS $one){
                    $dataLading[] = (object)$one;
                }
            }else{
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Nexxt!!!',
                );
                return Response::json($contents);
            }

            $template = TemplateModel::where('id',$data['template_id'])->first();
            if(!empty($items)){
                $html = DbView::make($template)->with(['fullname' => $items['fullname'],'count_fail' => $items['count_fail'],'count_success' => $items['count_success'],'count_return' => $items['count_return'],'ladings' => $dataLading])->render();
                Mail::send('emails.template.demo', array('html' => $html) , function($message) use ($toEmail)
                {
                    return $message->to($toEmail)->subject('[ShipChung] Thông báo tổng hợp vận đơn giao trong ngày '.date("d/m/Y",strtotime("-1 day")));
                });
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!!',
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not send email!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
            );
        }
        return Response::json($contents);
    }

    //send email change emailNL
    public function getSendemailchangeemailnl(){
        $start = $this->time() - 6*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',16)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $toEmail = $data['received'];
            $items = json_decode($data['data'],1);

            $template = TemplateModel::where('id',$data['template_id'])->first();
            if(!empty($items)){
                $html = DbView::make($template)->with(['fullname' => $items['fullname'],'new_data' => $items['new_data'],'old_data' => $items['old_data'],'link_active' => $items['link_success']])->render();
                Mail::send('emails.template.demo', array('html' => $html) , function($message) use ($toEmail)
                {
                    return $message->to($toEmail)->subject('[ShipChung] Thông báo thay đổi email Ngân Lượng trên hệ thống');
                });
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!!',
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not send email!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
            );
        }
        return Response::json($contents);
    }
    //send email when forgot password
    public function getSendemailforgotpassword(){
        $start = $this->time() - 6*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',18)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $toEmail = $data['received'];
            $items = json_decode($data['data'],1);

            $template = TemplateModel::where('id',$data['template_id'])->first();
            if(!empty($items)){
                $html = DbView::make($template)->with(['fullname' => $items['fullname'],'link_get' => $items['link_get']])->render();
                Mail::send('emails.template.new', array('html' => $html) , function($message) use ($toEmail)
                {
                    return $message->to($toEmail)->subject('[ShipChung] Thông báo lấy lại mật khẩu');
                });
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!!',
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not send email!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
            );
        }
        return Response::json($contents);
    }
    // Send email verify
    public function getSendemailverify(){
        $start = $this->time() - 6*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',19)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $toEmail = $data['received'];
            $items = json_decode($data['data'],1);

            $template = TemplateModel::where('id',$data['template_id'])->first();
            if(!empty($items)){
                $html = DbView::make($template)->with(['fullname' => $items['fullname'],'email' => $items['email'],'account' => $items['account'],'verify_id' => $items['verify_id'],'time_start' => $items['time_success_start'],'time_end' => $items['time_success_end'],'total_money_collect' => $items['total_money_collect'],'total_fee' => $items['total_fee'],'transaction_id' => $items['transaction_id'],'money_in' => $items['money_in'],'money_hold' => $items['money_hold'],'balance' => $items['balance'],'note' => $items['note'],'type' => $items['type']])->render();
                //var_dump($html);die;
                Mail::send('emails.template.demo', array('html' => $html) , function($message) use ($toEmail,$items)
                {
                    return $message->to($toEmail)->subject('[ShipChung] Thông báo đối soát khách hàng từ ngày '.$items['time_success_start'].' đến '.$items['time_success_end']);
                });
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!!',
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not send email!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
            );
        }
        return Response::json($contents);
    }
    //gui email cau hinh tk cha con
    public function getSendemailchilduser(){
        $start = $this->time() - 6*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',20)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $toEmail = $data['received'];
            $items = json_decode($data['data'],1);

            $template = TemplateModel::where('id',$data['template_id'])->first();
            if(!empty($items)){
                $html = DbView::make($template)->with(['fullname' => $items['fullname'],'parent_email' => $items['parent_email'],'child_email' => $items['child_email'],'link' => $items['link']])->render();
                //var_dump($html);die;
                Mail::send('emails.template.demo', array('html' => $html) , function($message) use ($toEmail,$items)
                {
                    return $message->to($toEmail)->subject('[ShipChung] Thông báo tạo tài khoản con thành công');
                });
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!!',
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not send email!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
            );
        }
        return Response::json($contents);
    }
    // gui email van don vuot can
    public  function getSendemailoverweight(){
        $start = $this->time() - 6*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',21)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $toEmail = $data['received'];
            $items = json_decode($data['data'],1);
            $dataLading = array();
            foreach($items['ladings'] AS $one){
                $dataLading[] = (object)$one;
            }
            $template = TemplateModel::where('id',$data['template_id'])->first();
            if(!empty($items)){
                $html = DbView::make($template)->with(['fullname' => $items['fullname'],'count' => $items['count'],'ladings' => $dataLading])->render();
                Mail::send('emails.template.demo', array('html' => $html) , function($message) use ($toEmail,$items)
                {
                    return $message->to($toEmail)->subject('[ShipChung] Thông báo những vận đơn vượt khối lượng trong ngày '.date("d/m/Y",strtotime("-1 day")));
                });
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!!',
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not send email!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
            );
        }
        return Response::json($contents);
    }
    //sync mailchimp
    public function getSyncmailchimp(){
        $dataSync = CustomerAdminModel::where('sync',0)->where('time_sync',0)->first();
        if(!empty($dataSync)){
            $dataUserSync = User::where('id',$dataSync['user_id'])->first();
            $Id     = 'e1af6805a1';
            $batch[] = array(
                'email'         =>  array('email' => $dataUserSync['email']),
                'email_type'    => 'html',
                'merge_vars'    => array('FULLNAME'=>$dataUserSync['fullname'], 'PHONE'=>$dataUserSync['phone'],'UPDATEDAY' => date("d M Y g:i a",$this->time()),'DATESIGNUP' => date("d M Y g:i a",$dataUserSync['time_create']))
            );

            $result = MailchimpWrapper::lists()->batchSubscribe($Id,$batch,0,1,1);
            if(empty($result['errors']) || $result['errors'][0]['code'] == 212 || $result['errors'][0]['code'] == 220){
                $update = CustomerAdminModel::where('id',$dataSync['id'])->update(array('sync' => 1,'time_sync' => $this->time()));
                $contents = array(
                    'error'     => true,
                    'message'   => 'Success!!!',
                    'data'          => $result
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not sync data!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data sync!!!',
            );
        }
        return Response::json($contents);
    }
    //sync fo
    public function getSync(){
        $Model = new CustomerAdminModel;
        $userInfo = User::where('add',0)->first();
        $timeCreate = 0;
        if(!empty($userInfo)){
            $infoOrder = OrdersModel::where('from_user_id',$userInfo['id'])->where('status','>',30)->select(DB::raw('from_user_id, max(time_create) as time_create'))->get(['time_create','from_user_id'])->toArray();
            $check = $Model->where('user_id',$userInfo['id'])->first();
            
            if(!empty($infoOrder) && $infoOrder[0]['from_user_id'] > 0 && $infoOrder[0]['time_create'] > 0 && !empty($check)){
                $update = $Model->where('user_id',$userInfo['id'])->update(array('last_order_time' => $infoOrder[0]['time_create']));
                if($update){
                    $update1 = User::where('id',$userInfo['id'])->update(array('add' => 1));
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Success!!!'
                    );
                }else{
                    $contents = array(
                        'error'     => false,
                        'message'   => 'Not insert'
                    );
                }
            }else{
                $update = User::where('id',$userInfo['id'])->update(array('add' => 1));
                $contents = array(
                    'error'     => true,
                    'message'   => 'Update Success!!!'
                );
            }
        }else{
            $contents = array(
                'error'     => false,
                'message'   => 'Not data'
            );
        }
        return Response::json($contents);
    }
    //sync first order new user
    public function getUserfirstorder(){
        $Id     = 'e1af6805a1';
        $Model = new CustomerAdminModel;

        $listData = $Model->where('time_send_notice_fo',0)->where('first_order_time','>',0)->first();
        if(!empty($listData)){
            $userInfo = User::where('id',$listData['user_id'])->first();
            $infoOrder = OrdersModel::where('from_user_id',$listData['user_id'])->where('time_create',$listData['first_order_time'])->first();
            if(!empty($userInfo)){
                $batch[] = array(
                    'email'         =>  array('email' => $userInfo['email']),
                    'email_type'    => 'html',
                    'merge_vars'    => array('FULLNAME'=>$userInfo['fullname'], 'PHONE'=>$userInfo['phone'],'MERGE28' => $infoOrder['tracking_code'],'FIRSTORDER' => 1,'MERGE7' => date("d M Y g:i a",$listData['first_order_time']),'UPDATEDAY' => date("d M Y g:i a",$this->time()))
                );

                $result = MailchimpWrapper::lists()->batchSubscribe($Id,$batch,0,1,1);
                if(empty($result['errors']) || $result['errors'][0]['code'] == 212 || $result['errors'][0]['code'] == 213 || $result['errors'][0]['code'] == 220){
                    $update = CustomerAdminModel::where('id',$listData['id'])->update(array('time_send_notice_fo' => $this->time()));
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Success!!!',
                        'data'          => $result
                    );
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Not sync data!!!',
                    );
                }
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not user!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data sync!!!',
            );
        }
        
        return Response::json($contents);
    }
    //sync first time accept
    public function getSynctimeaccept(){
        $Id     = 'e1af6805a1';
        $Model = new CustomerAdminModel;

        $listData = $Model->where('first_accept_order_time','>',1)->where('first_order_time','>',0)->where('sync_accept_order',0)->first();
        if(!empty($listData)){
            $userInfo = User::where('id',$listData['user_id'])->first();
            if(!empty($userInfo)){
                $batch[] = array(
                    'email'         =>  array('email' => $userInfo['email']),
                    'email_type'    => 'html',
                    'merge_vars'    => array('FULLNAME'=>$userInfo['fullname'], 'PHONE'=>$userInfo['phone'],'DUYETVD' => 1,'UPDATEDAY' => date("d M Y g:i a",$this->time()))
                );
                $result = MailchimpWrapper::lists()->batchSubscribe($Id,$batch,0,1,1);
                if(empty($result['errors']) || $result['errors'][0]['code'] == 212 || $result['errors'][0]['code'] == 213 || $result['errors'][0]['code'] == 220){
                    $update = CustomerAdminModel::where('id',$listData['id'])->update(array('sync_accept_order' => 1));
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Success!!!',
                        'data'          => $result
                    );
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Not sync data!!!',
                    );
                }
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not user!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data sync!!!',
            );
        }
        return Response::json($contents);
    }
    //sync first delivery
    public function getSyncdelivery(){
        $Id     = 'e1af6805a1';
        $Model = new CustomerAdminModel;
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-10 day',$this->time())));
        $batch = array();

        $listUser = $Model->where('sync_delivery_order',0)->where('time_create','>',$timeStart)->get(array('id','user_id'))->toArray();
        if(!empty($listUser)){
            $listUserId = $listId = array();
            foreach($listUser AS $oneUser){
                $listUserId[] = $oneUser['user_id'];
                $listId[]     = $oneUser['id'];
            }
            $infoUser = User::whereIn('id',$listUserId)->get(array('fullname','email','phone','id'))->toArray();
            $listOrderUser = OrdersModel::whereIn('from_user_id',$listUserId)->where('status',52)->get(array('from_user_id','time_success'))->toArray();
            if(!empty($listOrderUser)){
                foreach($listOrderUser AS $oneOrder){
                    foreach($infoUser AS $user){
                        if($user['id'] == $oneOrder['from_user_id']){
                            $batch[] = array(
                                'email'         =>  array('email' => $user['email']),
                                'email_type'    => 'html',
                                'merge_vars'    => array('FULLNAME'=>$user['fullname'], 'PHONE'=>$user['phone'],'GIAOTC' => 1,'UPDATEDAY' => date("d M Y g:i a",$this->time()))
                            );
                        }
                    }
                }
                //
                $result = MailchimpWrapper::lists()->batchSubscribe($Id,$batch,0,1,1);
                if(empty($result['errors']) || $result['errors'][0]['code'] > 0){
                    $update = CustomerAdminModel::whereIn('id',$listId)->update(array('sync_delivery_order' => 1));
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Success!!!',
                        'data'          => $result
                    );
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Not sync data!!!',
                    );
                }
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not data order sync!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data sync!!!',
            );
        }
        return Response::json($contents);
    }
    //sync mailchimp not lading after 7days
    public function getSyncnotlading(){
        $Id     = 'e1af6805a1';
        $Model = new CustomerAdminModel;
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-7 day',$this->time())));
        $timeEnd = $timeStart + 86400;

        $listData = $Model->where('first_order_time',0)->where('not_create',0)->where('time_create','>',$timeStart)->where('time_create','<',$timeEnd)->first();
        if(!empty($listData)){
            $userInfo = User::where('id',$listData['user_id'])->first();
            $infoOrder = OrdersModel::where('from_user_id',$listData['user_id'])->first();
            if(!empty($userInfo) && empty($infoOrder)){
                $batch[] = array(
                    'email'         =>  array('email' => $userInfo['email']),
                    'email_type'    => 'html',
                    'merge_vars'    => array('FULLNAME'=>$userInfo['fullname'], 'PHONE'=>$userInfo['phone'],'MERGE14' => 1,'UPDATEDAY' => date("d M Y g:i a",$this->time()))
                );

                $result = MailchimpWrapper::lists()->batchSubscribe($Id,$batch,0,1,1);
                if(empty($result['errors']) || $result['errors'][0]['code'] == 212 || $result['errors'][0]['code'] == 213 || $result['errors'][0]['code'] == 220){
                    $update = CustomerAdminModel::where('id',$listData['id'])->update(array('not_create' => 1));
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Success!!!',
                        'data'          => $result
                    );
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Not sync data!!!',
                    );
                }
            }else{
                $batch[] = array(
                    'email'         =>  array('email' => $userInfo['email']),
                    'email_type'    => 'html',
                    'merge_vars'    => array('FULLNAME'=>$userInfo['fullname'], 'PHONE'=>$userInfo['phone'],'MERGE28' => $infoOrder['tracking_code'],'FIRSTORDER' => 1,'MERGE7' => date("d M Y g:i a",$infoOrder['time_create']),'UPDATEDAY' => date("d M Y g:i a",$this->time()))
                );
                $result = MailchimpWrapper::lists()->batchSubscribe($Id,$batch,0,1,1);
                if(empty($result['errors']) || $result['errors'][0]['code'] == 212 || $result['errors'][0]['code'] == 213 || $result['errors'][0]['code'] == 220){
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Success u!!!',
                        'data'          => $result
                    );
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Not sync data!!!',
                    );
                }
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data sync!!!',
            );
        }
        
        return Response::json($contents);
    }
    //sync when not action after 5days
    public function getSyncnotaction(){
        $Id     = 'e1af6805a1';
        $Model = new CustomerAdminModel;
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-5 day',$this->time())));
        $timeEnd = $timeStart + 86400;

        $listData = $Model->where('first_order_time','>',0)->where('first_accept_order_time',0)->where('not_action',0)->where('time_create','>',$timeStart)->where('time_create','<',$timeEnd)->first();
        if(!empty($listData)){
            $userInfo = User::where('id',$listData['user_id'])->first();
            if(!empty($userInfo)){
                $batch[] = array(
                    'email'         =>  array('email' => $userInfo['email']),
                    'email_type'    => 'html',
                    'merge_vars'    => array('FULLNAME'=>$userInfo['fullname'], 'PHONE'=>$userInfo['phone'],'TAO5DAY' => 1,'UPDATEDAY' => date("d M Y g:i a",$this->time()))
                );
                $result = MailchimpWrapper::lists()->batchSubscribe($Id,$batch,0,1,1);
                if(empty($result['errors']) || $result['errors'][0]['code'] == 212 || $result['errors'][0]['code'] == 213 || $result['errors'][0]['code'] == 220){
                    $update = CustomerAdminModel::where('id',$listData['id'])->update(array('not_action' => 1));
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Success!!!',
                        'data'          => $result
                    );
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Not sync data!!!',
                    );
                }
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not user!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data sync!!!',
            );
        }
        
        return Response::json($contents);
    }
    //send notification fb
    public function getSendnotifyfb(){
        $data = QueueModel::where('transport_id',4)->where('time_success',0)->where('status',0)->first();//var_dump($data);die;
        if(!empty($data)){
            $content = json_decode($data['data'],1);
            $fb = new FacebookController;
            $response = $fb->notification($data['received'],$content['content'],$content['href']);
            if($response == 1){
                $update = QueueModel::where('id',$data['id'])->update(array('status' => 1,'time_success' => $this->time()));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!'
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => $response
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data notification!!!',
            );
        }
        return Response::json($contents);
    }
    //send email verify bank account
    public function getSendemailverifybankaccount(){
        $start = $this->time() - 6*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',22)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $toEmail = $data['received'];
            $items = json_decode($data['data'],1);
            
            $template = TemplateModel::where('id',$data['template_id'])->first();
            if(!empty($items)){
                $html = DbView::make($template)->with(['fullname' => $items['fullname'],'bank_name' => $items['bank_name'],'bank_account_name' => $items['bank_account_name'],'bank_account_num' => $items['bank_account_num'],'link' => $items['link']])->render();
                Mail::send('emails.template.demo', array('html' => $html) , function($message) use ($toEmail,$items)
                {
                    return $message->to($toEmail)->subject('[ShipChung] Thông báo xác minh việc cập nhật tài khoản ngân hàng');
                });
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!!',
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not send email!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
            );
        }
        return Response::json($contents);
    }
    //send all email
    public function getSendemail($queue){
        $id = (int)$queue;
        $start = $this->time() - 7*86400;
        $data = QueueModel::where('id',$queue)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $toEmail = $data['received'];
            $items = json_decode($data['data'],1);
            $unique = '';
            //
            $template = TemplateModel::where('id',$data['template_id'])->first();

            if(in_array($template['id'], [13,14,21,25])){
                $unique = $template['title'].' - #'.$items['ticket_id'];
            }elseif($template['id'] == 12){
                $unique = $template['title'].' - #'.$items['id'];
            }elseif($template['id'] == 16 || $template['id'] == 38){
                $unique = $template['title'].'-'.date("d/m/Y",strtotime("-1 day"));
            }elseif($template['id'] == 29){
                $unique = $items['title'].' - #'.$items['id'];
            }else{
                $unique = $template['title'];
            }

            if(!empty($items) && empty($items['type'])){
                $html = DbView::make($template)->with($items)->render();
                Mail::send('emails.template.new', array('html' => $html) , function($message) use ($toEmail,$items,$template,$unique)
                {
                    $a = $message->getBody();
                    return $message->to($toEmail)->subject($unique);
                });
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!!',
                );
            }elseif(!empty($items) && $items['type'] == 'successOrder'){
                $html = DbView::make($template)->with($items)->render();
                Mail::send('emails.template.success', array('html' => $html) , function($message) use ($toEmail,$items,$template,$unique)
                {
                    $a = $message->getBody();var_dump($a);die;
                    return $message->to($toEmail)->subject($unique);
                });
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!!',
                );
            }elseif(!empty($items) && $items['type'] == 'failOrder'){
                $html = DbView::make($template)->with($items)->render();
                Mail::send('emails.template.fail', array('html' => $html) , function($message) use ($toEmail,$items,$template,$unique)
                {
                    $a = $message->getBody();var_dump($a);die;
                    return $message->to($toEmail)->subject($unique);
                });
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!!',
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not send email!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
            );
        }
        return Response::json($contents);
    }
    //gui SMS khi tao don hang thanh cong
    public function getSmssuccessorder(){
        $start = $this->time() - 7*86400;
        $data = CustomerAdminModel::where('first_accept_order_time','>',0)->where('sms_first_accept_order',0)->orderBy('first_accept_order_time','ASC')->first();
        if(isset($data->id)){
            $infoUser = $this->getUserconfigtransport($data->user_id);
            $data->sms_first_accept_order   = 1;

            if(!empty($infoUser['phone'])){
                $dataInsert = array(
                    'to_phone' => $infoUser['phone'],
                    'content'  => 'Bạn vui lòng chuẩn bị hàng và đóng gói đảm bảo sau đó giao bưu tá, yêu cầu bưu tá ký nhận phiếu gửi hàng hoặc sổ ghi chép cá nhân.',
                    'time_create' => $this->time(),
                    'status'     => 0,
                    'priority'   => 0,
                    'telco'      => Smslib::CheckPhone($infoUser['phone'])
                );

                $LMongo         = new LMongo;
                $LMongo::collection('log_send_sms')->insert($dataInsert);

                $contents = array(
                    'error'     => false,
                    'message'   => 'success!!!',
                    'id'        => $data->id
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not phone!!!',
                    'id'        => $data->id
                );
            }

            try{
                $data->save();
            }catch(Exception $e){
                $contents = array(
                    'error'     => true,
                    'message'   => 'update fail!!!'
                );
            }

        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
                'id'        => $data['id']
            );
        }
        return Response::json($contents);
    }
    //gui SMS khi co don hang dau tien bi loi
    public function getSmsfailorder(){
        $start = $this->time() - 7*86400;
        $LMongo         = new LMongo;
        $data = CustomerAdminModel::where('first_fail_order_time','>',0)->where('sms_fail_order',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $infoUser = $this->getUserconfigtransport($data['user_id']);
            $dataInsert = array();
            if(!empty($infoUser['phone'])){
                $dataInsert = array(
                    'to_phone' => $infoUser['phone'],
                    'content'  => 'Bạn có đơn hàng mã '.$data['fail_tracking_code'].' giao không thành công cần xử lý, vui lòng đăng nhập vào hệ thống Shipchung để xử lý các đơn hàng kịp thời.',
                    'time_create' => $this->time(),
                    'status'     => 0,
                    'priority'   => 0,
                    'telco'      => Smslib::CheckPhone($infoUser['phone'])
                );
            }else{
                $update = CustomerAdminModel::where('id',$data['id'])->update(array('sms_fail_order' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Not phone!!!',
                );
            }
            if(!empty($dataInsert)){
                $insert = $LMongo::collection('log_send_sms')->insert($dataInsert);
                if($insert){
                    $update = CustomerAdminModel::where('id',$data['id'])->update(array('sms_fail_order' => 1));
                    $contents = array(
                        'error'     => false,
                        'message'   => 'Send success!!!',
                    );
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Not send sms!!!',
                    );
                }
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not data!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
            );
        }
        return Response::json($contents);
    }
    //gui sms khi don thanh cong dau tien cua KH moi
    public function getSendsmsfirstordersuccess(){
        $start = $this->time() - 7*86400;

        $data = CustomerAdminModel::where('first_success_order_time','>',0)->where('sms_first_order_success',0)->where('time_create','>',$start)->orderBy('time_create','ASC')->first();
        if(isset($data->id)){
            $infoUser = $this->getUserconfigtransport($data->user_id);
            if(!empty($infoUser['phone'])){
                $dataInsert = array(
                    'to_phone' => $infoUser['phone'],
                    'content'  => 'Bạn có đơn hàng mã '. $data->first_success_tracking_code .' đã giao thành công. ',
                    'time_create' => $this->time(),
                    'status'     => 0,
                    'priority'   => 0,
                    'telco'      => Smslib::CheckPhone($infoUser['phone'])
                );

                $LMongo         = new LMongo;
                $LMongo::collection('log_send_sms')->insert($dataInsert);
                $contents = array(
                    'error'     => false,
                    'message'   => 'success!!!',
                    'id'        => $data->id
                );
            }else{
                $contents = array(
                    'error'     => false,
                    'message'   => 'Not phone!!!',
                    'id'        => $data->id
                );
            }

            $data->sms_first_order_success = 1;

            try{
                $data->save();
            }catch(Exception $e){
                $contents = array(
                    'error'     => true,
                    'message'   => 'update fail!!!',
                    'id'        => $data->id
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!'
            );
        }
        return Response::json($contents);
    }
    //sync mailchimp KH ngung su dung
    public function getSyncuserpause(){
        $start = $this->time() - 30*86400;
        $dataSync = CustomerAdminModel::where('insighly',0)->where('last_order_time','<',$start)->where('first_order_time','>',0)->first();
        if(!empty($dataSync)){
            $dataUserSync = User::where('id',$dataSync['user_id'])->first();
            $Id     = 'e1af6805a1';
            $batch[] = array(
                'email'         =>  array('email' => $dataUserSync['email']),
                'email_type'    => 'html',
                'merge_vars'    => array('FIRSTORDER' => date("d M Y",$dataSync['first_order_time']),'LASTORDER' => date("d M Y",$dataSync['last_order_time']),'PAUSE' => 1)
            );
            $result = MailchimpWrapper::lists()->batchSubscribe($Id,$batch,0,1,1);
            if(empty($result['errors']) || $result['errors'][0]['code'] > 0 || $result['errors'][0]['code'] < 0){
                $update = CustomerAdminModel::where('id',$dataSync['id'])->update(array('insighly' => 1));
                $contents = array(
                    'error'     => true,
                    'message'   => 'Success!!!',
                    'data'          => $result
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not sync data!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data sync!!!',
            );
        }
        return Response::json($contents);
    }
    public function getSyncusernotuse(){
        $dataSync = CustomerAdminModel::where('insighly',0)->where('first_order_time',0)->first();
        if(!empty($dataSync)){
            $dataUserSync = User::where('id',$dataSync['user_id'])->first();
            $Id     = 'e1af6805a1';
            $batch[] = array(
                'email'         =>  array('email' => $dataUserSync['email']),
                'email_type'    => 'html',
                'merge_vars'    => array('FULLNAME'=>$dataUserSync['fullname'], 'PHONE'=>$dataUserSync['phone'],'TIMEREGIS' => date("d M Y",$dataUserSync['time_create']),'NOTUSE' => 1)
            );
            $result = MailchimpWrapper::lists()->batchSubscribe($Id,$batch,0,1,1);
            if(empty($result['errors']) ||  $result['errors'][0]['code'] > 0 || $result['errors'][0]['code'] < 0){
                $update = CustomerAdminModel::where('id',$dataSync['id'])->update(array('insighly' => 1));
                $contents = array(
                    'error'     => true,
                    'message'   => 'Success!!!',
                    'data'          => $result
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not sync data!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data sync!!!',
            );
        }
        return Response::json($contents);
    }
    
    //gui sms khi KH khong duoc xac thuc tk NH
    public function getSendsmsbankfail(){
        $start = $this->time() - 7*86400;
        $LMongo         = new LMongo;
        $data = VimoModel::where('active',0)->where('notify',0)->where('note','!=','')->where('time_create','>',$start)->first();
        if(!empty($data)){
            $infoUser = User::where('id',$data['user_id'])->first();
            $dataInsert = array();
            $content = 'Tài khoản ngân hàng của quý khách chưa được xác thực với lý do "'.$data['note'].'" quý khách hãy kiểm tra lại';
            if(!empty($infoUser['phone'])){
                $dataInsert = array(
                    'to_phone' => $infoUser['phone'],
                    'content'  => $content,
                    'time_create' => $this->time(),
                    'status'     => 0,
                    'priority'   => 0,
                    'telco'      => Smslib::CheckPhone($infoUser['phone'])
                );
                // $dataInsert = array(
                //     'scenario_id' => 333,
                //     'template_id' => 78,
                //     'transport_id' => 7,
                //     'user_id'   => $data['user_id'],
                //     'received'  => $infoUser['phone'],
                //     'data'      => json_encode(array('content' => $content,'template_id' => 'abgfjkj394023erf')),
                //     'time_create' => $this->time(),
                //     'status'   => 0,
                //     'time_success' => 0
                // );
            }else{
                $update = VimoModel::where('id',$data['id'])->update(array('notify' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Not phone!!!',
                );
            }
            if(!empty($dataInsert)){
                $insert = $LMongo::collection('log_send_sms')->insert($dataInsert);
                //$insert = QueueModel::insert($dataInsert);
                //notice app
                $dataNoticeApp = array();
                $userDevice = $this->getUserapp($data['user_id']);
                if(!empty($userDevice)){
                    if($userDevice['android_device_token'] != ''){
                        $dataNoticeApp = array(
                            'os_device' => 'android',
                            'transport_id' => 5,
                            'scenario_id' => 2424,
                            'template_id' => 2727,
                            'user_id' => $data['user_id'],
                            'received' => $data['user_id'],
                            'data' => json_encode(array('device_token' => $userDevice['android_device_token'],'data' => array('message' => $content))),
                            'time_create' => $this->time()
                        );
                    }elseif($userDevice['ios_device_token'] != ''){
                        $dataNoticeApp = array(
                            'os_device' => 'ios',
                            'transport_id' => 5,
                            'scenario_id' => 24,
                            'template_id' => 27,
                            'user_id' => $data['user_id'],
                            'received' => $data['user_id'],
                            'data' => json_encode(array('device_token' => $userDevice['ios_device_token'],'message' => $content,'data' => array())),
                            'time_create' => $this->time()
                        );
                    }
                    if(!empty($dataNoticeApp)){
                        QueueModel::insert($dataNoticeApp);
                    }
                }
                if($insert){
                    $update = VimoModel::where('id',$data['id'])->update(array('notify' => 1));
                    $contents = array(
                        'error'     => false,
                        'message'   => 'Send success!!!',
                    );
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Not send sms!!!',
                    );
                }
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not data!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
            );
        }
        return Response::json($contents);
    }
    //send coupon
    public function getSendcoupon(){
        $LMongo         = new LMongo;
        $dataSms = array();
        $data = $LMongo::collection('refer_sigup')->where('status',1)->where('notify',0)->first();
        if(!empty($data)){
            $user = $this->getUserconfigtransport($data['user_id']);
            $userRefer = $this->getUserconfigtransport($data['refer_id']);
            $template = TemplateModel::where('id',34)->first();
            $content = DbView::make($template)->with(['code' => $data['coupon'],'date' => date("d/m/Y",$data['time_expired'])])->render();
            $templateRefer = TemplateModel::where('id',36)->first();
            $contentRefer = DbView::make($templateRefer)->with(['code' => $data['refer_coupon'],'date' => date("d/m/Y",$data['time_expired'])])->render();
            //send sms
            if(!empty($user['phone']) && !empty($userRefer['phone'])){
                $dataSms = array(
                    array(
                        'to_phone' => $user['phone'],
                        'content'  => $content,
                        'time_create' => $this->time(),
                        'status'     => 0,
                        'priority'   => 0,
                        'telco'      => Smslib::CheckPhone($user['phone'])
                    ),
                    array(
                        'to_phone' => $userRefer['phone'],
                        'content'  => $contentRefer,
                        'time_create' => $this->time(),
                        'status'     => 0,
                        'priority'   => 0,
                        'telco'      => Smslib::CheckPhone($userRefer['phone'])
                    )
                );
                $Model  = $LMongo::collection('log_send_sms');
                $insert = $Model->batchInsert($dataSms);
                if($insert){
                    //insert sms queue
                    $dataQueue = array(
                        array(
                            'scenario_id' => 555,
                            'template_id' => 34,
                            'transport_id' => 1,
                            'user_id'       => $data['user_id'],
                            'received'    => $user['phone'],
                            'data'      => json_encode(array('fullname' => $user['fullname'],'coupon' => $data['coupon'])),
                            'time_create' => $this->time(),
                            'status' => 1,
                            'time_success' => $this->time() + 600
                        ),
                        array(
                            'scenario_id' => 555,
                            'template_id' => 36,
                            'transport_id' => 1,
                            'user_id'       => $data['refer_id'],
                            'received'    => $userRefer['phone'],
                            'data'      => json_encode(array('fullname' => $user['fullname'],'coupon' => $data['coupon'])),
                            'time_create' => $this->time(),
                            'status' => 1,
                            'time_success' => $this->time() + 600
                        ),
                    );
                    $insertSms = QueueModel::insert($dataQueue);
                    //email
                    $dataEmail = array(
                        array(
                            'scenario_id' => 88,
                            'template_id' => 37,
                            'transport_id' => 2,
                            'user_id'       => $data['user_id'],
                            'received'    => $user['email'],
                            'data'      => json_encode(array('fullname' => $user['fullname'],'coupon' => $data['coupon'])),
                            'time_create' => $this->time(),
                            'status' => 0
                        ),
                        array(
                            'scenario_id' => 88,
                            'template_id' => 37,
                            'transport_id' => 2,
                            'user_id'       => $data['refer_id'],
                            'received'    => $userRefer['email'],
                            'data'      => json_encode(array('fullname' => $userRefer['fullname'],'coupon' => $data['refer_coupon'])),
                            'time_create' => $this->time(),
                            'status' => 0
                        )
                    );

                    $insertEmail = QueueModel::insert($dataEmail);
                    //app notice
                    $dataNoticeApp = array();
                    $userDevice = $this->getUserapp($data['user_id']);
                    if(!empty($userDevice)){
                        $content = '';
                        if($userDevice['android_device_token'] != ''){
                            $dataNoticeApp = array(
                                'os_device' => 'android',
                                'transport_id' => 5,
                                'scenario_id' => 24,
                                'template_id' => 27,
                                'user_id' => $data['user_id'],
                                'received' => $user['email'],
                                'data' => json_encode(array('device_token' => $userDevice['android_device_token'],'data' => array('message' => $content))),
                                'time_create' => $this->time()
                            );
                        }elseif($userDevice['ios_device_token'] != ''){
                            $dataNoticeApp = array(
                                'os_device' => 'ios',
                                'transport_id' => 5,
                                'scenario_id' => 24,
                                'template_id' => 27,
                                'user_id' => $data['user_id'],
                                'received' => $user['email'],
                                'data' => json_encode(array('device_token' => $userDevice['ios_device_token'],'message' => $content,'data' => array())),
                                'time_create' => $this->time()
                            );
                        }
                        if(!empty($dataNoticeApp)){
                            QueueModel::insert($dataNoticeApp);
                        }
                    }
                    $update = $LMongo::collection('refer_sigup')->where('_id',new \MongoId($data['_id']))->update(array('notify' => 1));
                    $contents = array(
                        'error'     => false,
                        'message'   => 'Send success!!!',
                    );
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Not send sms!!!',
                    );
                }
            }else{
                $update = $LMongo::collection('refer_sigup')->where('_id',new \MongoId($data['_id']))->update(array('notify' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Not phone!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
            );
        }
        return Response::json($contents);
    }
    //email send coupon code
    public function getEmailcoupon(){
        $start = $this->time() - 7*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',88)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $toEmail = $data['received'];
            $items = json_decode($data['data'],1);
            
            $template = TemplateModel::where('id',$data['template_id'])->first();
            if(!empty($items)){
                $html = DbView::make($template)->with(['fullname' => $items['fullname'],'coupon' => $items['coupon']])->render();
                Mail::send('emails.template.demo', array('html' => $html) , function($message) use ($toEmail,$items)
                {
                    return $message->to($toEmail)->subject('[ShipChung] Thông báo gửi mã khuyến mãi');
                });
                $update = QueueModel::where('id',$data['id'])->where('time_create','>',$start)->update(array('time_success' => $this->time(),'status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!!',
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not send email!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
            );
        }
        return Response::json($contents);
    }
    //get list email reject in Mandrill
    public function getListemailreject(){
        $LMongo         = new LMongo;
        //xoa du lieu
        $key = '5-zDlq9aBK3ZcaxE3ROCHg';
        $mandrill = new Mandrill($key);
        $result = $mandrill->rejects->getList('', true, '');
        //insert vao log
        $dataInsert = array();
        if(!empty($result)){
            foreach($result AS $one){
                $dataInsert[] = array(
                    'email' => $one['email'],
                    'created_at' => $one['created_at']
                );
            }
            $Model  = $LMongo::collection('log_mandrill');
            $Insert = $Model->batchInsert($dataInsert);
            var_dump($Insert);die;
        }else{
            die(2); 
        }
    }
    //update oms_seller
    public function getOmsseller($seller,$custom){
        if(empty($seller) || empty($custom)){
            $contents = array(
                'error'     => true,
                'message'   => 'Not data get!!!',
            );
            return Response::json($contents);
        }
        $seller = trim($seller);
        $custom = trim($custom);
        $custom = str_replace(array(':',';',' '), ',', $custom);
        $infoSeller = User::where('email',$seller)->first();
        if(empty($infoSeller)){
            $contents = array(
                'error'     => true,
                'message'   => 'Not Sales!!!',
            );
            return Response::json($contents);
        }
        $arrCustom = explode(',', $custom);
        $infoCustom = User::whereIn('email',$arrCustom)->get(array('id'))->toArray();
        foreach($infoCustom AS $key => $value){
            $check = SellerModel::where('user_id',$value['id'])->first();
            if(!empty($check)){
                echo 1;
                $update = SellerModel::where('id',$check['id'])->update(array('seller_id' => $infoSeller['id'],'time_sync_insightly' => $this->time()));
            }elseif(empty($check)){
                echo 2;
                $dataSync = array(
                    'user_id' => $value['id'],
                    'seller_id' => $infoSeller['id'],
                    'time_create' => $this->time(),
                    'time_sync_insightly' => $this->time()
                );
                $insert = SellerModel::insert($dataSync);
            }else{
                die(67);
            }
        }
    }
    //email  don hang dau tien giao thanh cong
    public function getEmailfirstordersuccess(){
        $TimeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-3 day',$this->time())));

        $Data = CustomerAdminModel::where('time_create','>',$TimeStart)->where('first_success_order_time','>',0)->where('first_delivered',0)->first();
        if(!empty($Data)){
            $InfoUser = User::where('id',$Data['user_id'])->first();
            $Template = TemplateModel::where('id',40)->first();
            if(empty($Template)){
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not template!!!',
                );
                return Response::json($contents);
            }
            $DataInsert = array(
                'scenario_id' => 8888,
                'template_id' => 40,
                'transport_id' => 2,
                'user_id'       => $Data['user_id'],
                'received'    => $InfoUser['email'],
                'data'      => json_encode(array('type' => 'successOrder','fullname' => $InfoUser['fullname'],'first_tracking_code_success' => $Data['first_success_tracking_code'])),
                'time_create' => $this->time(),
                'status' => 0
            );
            $Send = QueueModel::insertGetId($DataInsert);
            if($Send >= 0){
                \Predis\Autoloader::register();
                //Now we can start creating a redis client to publish event'6788', '10.0.20.164'
                $redis = new \Predis\Client(array(
                    "scheme" => "tcp",
                    "host" => "10.0.20.164",
                    "port" => 6788
                ));
                //Now we got redis client connected, we can publish event (send event)
                $redis->publish("SendMail", $Send);

                $Update = CustomerAdminModel::where('id',$Data['id'])->update(array('first_delivered' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!'
                );
            }else{
                $Update = CustomerAdminModel::where('id',$Data['id'])->update(array('first_delivered' => 2));
                $contents = array(
                    'error'     => true,
                    'message'   => 'Fail send!!'
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data!!'
            );
        }
        return Response::json($contents);
    }
    //don hang that bai dau tien
    public function getEmailfirstorderfail(){
        $TimeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-3 day',$this->time())));
        $Data = CustomerAdminModel::where('time_create','>',$TimeStart)->where('first_fail_order_time','>',0)->where('first_failed',0)->first();
        if(!empty($Data)){
            $InfoUser = User::where('id',$Data['user_id'])->first();
            $Template = TemplateModel::where('id',41)->first();
            if(empty($Template)){
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not template!!!',
                );
                return Response::json($contents);
            }
            //ly do giao that bai
            $Note = '';
            if($Data['fail_tracking_code'] != ''){
                $InfoOrder = OrdersModel::where('tracking_code',$Data['fail_tracking_code'])->first();
                $StatusOrder = StatusModel::where('order_id',$InfoOrder['id'])->whereIn('status',array(54,55,56,57,58,59))->first();
                if(!empty($StatusOrder)){
                    $Note = $StatusOrder['note'];
                }else{
                    $Note = '';
                }
            }
            //sinh ma coupon
            $Ctrl = new \seller\CouponController;
            $Params = [
                'campaign_id'   => 6,
                'coupon_type'   => 2,
                'discount_type' => 1,
                'discount'      => 20000,
                'limit_usage'   => 1,
                'inapp'         => 1,
                'seller_email'  => $InfoUser['email'],
                'time_expired'  => $this->time() + 7 * 86400,
            ];
            Input::merge($Params);
            $Coupon = $Ctrl->postCreateCoupon(false);
            //
            $DataInsert = array(
                'scenario_id' => 8888,
                'template_id' => 41,
                'transport_id' => 2,
                'user_id'       => $Data['user_id'],
                'received'    => $InfoUser['email'],
                'data'      => json_encode(array('type' => 'failOrder','fullname' => $InfoUser['fullname'],'tracking_code' => $Data['fail_tracking_code'],'note' => $Note,'coupon_code' => $Coupon)),
                'time_create' => $this->time(),
                'status' => 0
            );
            $Send = QueueModel::insertGetId($DataInsert);
            if($Send >= 0){
                \Predis\Autoloader::register();
                //Now we can start creating a redis client to publish event'6788', '10.0.20.164'
                $redis = new \Predis\Client(array(
                    "scheme" => "tcp",
                    "host" => "10.0.20.164",
                    "port" => 6788
                ));
                //Now we got redis client connected, we can publish event (send event)
                $redis->publish("SendMail", $Send);
                //send notice app
                $TemplateApp = TemplateModel::where('id',49)->first();
                $ContentApp = DbView::make($TemplateApp)->with(['tracking_code' => $Data['fail_tracking_code'],'note' => $Note,'time_response' => date("H:i d/m/Y",$InfoOrder['time_update'] + 86400),'coupon_code' => $Coupon])->render();
                $Device = $this->getUserapp($Data['user_id']);
                if(!empty($Device)){
                    if($Device['android_device_token'] != ''){
                        $dataNoticeApp = array(
                            'os_device' => 'android',
                            'transport_id' => 5,
                            'scenario_id' => 233,
                            'template_id' => 49,
                            'user_id' => $InfoUser['id'],
                            'received' => $InfoUser['email'],
                            'data' => json_encode(
                                array(
                                    'device_token' => $Device['android_device_token'],
                                    'message' => $Template['title'],
                                    'data' => array(
                                        'type' => 'delivery_failed',
                                        'message' => $Template['title'],
                                        'title' => $Template['title'],
                                        'content' => $ContentApp,
                                        'order_id' => $InfoOrder['id'],
                                        'tracking_code' => $Data['fail_tracking_code'],
                                        'customer_phone' => $InfoUser['phone'],
                                        'status'        => $InfoOrder['status'],
                                        'status_name' => 'Giao không thành công',
                                        'time_update' => $InfoOrder['time_update'],
                                        'courier_id' => $InfoOrder['courier_id']
                                    )
                                )
                            ),
                            'time_create' => $this->time(),
                            'in_app' => 1
                        );
                    }elseif($Device['ios_device_token'] != ''){
                        $dataNoticeApp = array(
                            'os_device' => 'ios',
                            'transport_id' => 5,
                            'scenario_id' => 233,
                            'template_id' => 49,
                            'user_id' => $InfoUser['id'],
                            'received' => $InfoUser['email'],
                            'data' => json_encode(
                                array(
                                    'device_token' => $Device['ios_device_token'],
                                    'message' => $Template['title'],
                                    'data' => array(
                                        'type' => 'delivery_failed',
                                        'message' => $Template['title'],
                                        'title' => $Template['title'],
                                        'content' => $ContentApp,
                                        'order_id' => $InfoOrder['id'],
                                        'tracking_code' => $Data['fail_tracking_code'],
                                        'customer_phone' => $InfoUser['phone'],
                                        'status'        => $InfoOrder['status'],
                                        'status_name' => 'Giao không thành công',
                                        'time_update' => $InfoOrder['time_update'],
                                        'courier_id' => $InfoOrder['courier_id']
                                    )
                                )
                            ),
                            'time_create' => $this->time(),
                            'in_app' => 1
                        );
                    }
                    if(!empty($dataNoticeApp)){
                        QueueModel::insert($dataNoticeApp);
                    }
                }
                //notice in web
                
                $Update = CustomerAdminModel::where('id',$Data['id'])->update(array('first_failed' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!'
                );
            }else{
                $Update = CustomerAdminModel::where('id',$Data['id'])->update(array('first_failed' => 2));
                $contents = array(
                    'error'     => true,
                    'message'   => 'Fail send!!'
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data!!'
            );
        }
        return Response::json($contents);
    }
    //new reply ticket
    public function getSendemailnewticket(){
        $start = $this->time() - 3*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',27)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $send = $this->getSendemail($data['id']);
            if($send){
                $contents = array(
                    'error'     => false,
                    'message'   => 'Send success!!!',
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not send!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!!',
            );
        }
        return Response::json($contents);
    }
}
?>