<?php
use loyaltymodel\UserModel;
use loyaltymodel\LevelModel;
use loyaltymodel\CampaignModel;
use loyaltymodel\CampaignDetailModel;
use loyaltymodel\CategoryModel;

class LoyaltyController extends \BaseController {

    public function __construct(){
        
    }
    //hang muc thanh vien
    private function listLevel(){
        $output = array();
        $data = LevelModel::where('active',1)->get()->toArray();
        if(!empty($data)){
            foreach($data AS $one){
                $output[$one['code']] = $one['name'];
            }
            return $output;
        }else{
            return false;
        }
    }
    // tong hop hang thang
    public function getLoyaltyinmonth(){
        $data = UserModel::where('level','>',0)->get(array('id','user_id','total_point','current_point','level'))->toArray();
        if(!empty($data)){
            $dataBuild = $dataInsert = array();
            $levels = $this->listLevel();
            foreach($data AS $one){
                $listUserId[] = $one['user_id'];
            }
            $infoUser = User::whereIn('id',$listUserId)->get(array('id','fullname','email'))->toArray();
            foreach($data AS $one){
                foreach($infoUser AS $user){
                    if($one['user_id'] == $user['id']){
                        $one['fullname'] = $user['fullname'];
                        $one['email'] = $user['email'];
                    }
                }
                $dataBuild[] = $one;
            }
            //
            foreach($dataBuild AS $value){
                $dataInsert[] = array(
                    'scenario_id' => 5555,
                    'template_id' => 55,
                    'transport_id' => 2,
                    'user_id'       => $value['user_id'],
                    'received'    => $value['email'],
                    'data'      => json_encode(array(
                        'identifier' => !empty($value['identifier']) ? $value['identifier'] : '',
                        'fullname' => $value['fullname'],
                        'month' => date('m/Y',$this->time()),
                        'total_point' => $value['total_point'],
                        'point_in_month' => $value['current_point'],
                        'level' => $levels[$value['level']]
                    )),
                    'time_create' => $this->time(),
                    'status' => 0
                );
            }
            $insert = QueueModel::insert($dataInsert);
            if($insert){
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!'
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Fail insert!!'
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!'
            );
        }
        return Response::json($contents);
    }

    //Kh doi thuong
    public function getGift($campaign){
        if($campaign == 1){
            $data = CampaignDetailModel::where('notice',0)->where('campaign_id',1)->where('user_id','>',0)->first();    
        }elseif($campaign > 1){
            $data = CampaignDetailModel::where('notice',0)->where('campaign_id','>',1)->where('code','!=','')->where('user_id','>',0)->first();
        }else{
            $data = array();
        }
        
        $dataInsert = array();
        if(!empty($data)){
            $listType = array(1 => 'Viettel',2 => 'Mobifone',3 => 'Vinafone', 4 => 'Vietnammb');
            //
            $type = '';
            if($data['phone_type'] > 0){
                $type = ' - '.$listType[$data['phone_type']];
            }
            $infoCampaign = CampaignModel::where('id',$data['campaign_id'])->first();
            $levels = $this->listLevel();
            $infoUser = User::where('id',$data['user_id'])->first();
            $infoUserLoyalty = UserModel::where('user_id',$data['user_id'])->first();
            $pointRemain = $infoUserLoyalty['total_point'];
            $dataInsert = array(
                'scenario_id' => 5555,
                'template_id' => 56,
                'transport_id' => 2,
                'user_id'       => $data['user_id'],
                'received'    => $infoUser['email'],
                'data'      => json_encode(array(
                    'identifier' => !empty($value['identifier']) ? $value['identifier'] : '',
                    'fullname' => $infoUser['fullname'],
                    'level' => $levels[$data['level']],
                    'time_get' => date('H:i d/m/Y',$data['time_create']),
                    'product' => $infoCampaign['name'].$type,
                    'point' => $infoCampaign['point'],
                    'point_remain' => $pointRemain
                )),
                'time_create' => $this->time(),
                'status' => 0
            );
            $insert = QueueModel::insert($dataInsert);
            if($insert){
                $update = CampaignDetailModel::where('id',$data['id'])->update(array('notice' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!'
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Fail insert!!'
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!'
            );
        }
        return Response::json($contents);
    }
    //sent email
    public function getSend(){
        $start = $this->time() - 7*86400;
        $data = QueueModel::where('transport_id',2)->where('scenario_id',5555)->where('time_success',0)->where('status',0)->where('time_create','>',$start)->first();
        if(!empty($data)){
            $toEmail = $data['received'];
            $items = json_decode($data['data'],1);
            
            $template = TemplateModel::where('id',$data['template_id'])->first();
            if($data['template_id'] == 55){
                $title = '[ShipChung] Thông báo tổng hợp điểm tháng '.date('m/Y',$this->time());
            }else{
                $title = $template['title'];
            }
            if(!empty($items)){
                $html = DbView::make($template)->with($items)->render();
                //var_dump($html);die;
                Mail::send('emails.template.new', array('html' => $html) , function($message) use ($toEmail,$items,$title)
                {
                    return $message->to($toEmail)->subject($title);
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
    //send sms
    public function getSendsmsloyalty(){
        $data = CampaignDetailModel::where('sms',0)->where('code','!=','')->where('phone','!=','')->where('user_id','>',0)->first();
        if(!empty($data)){
            $LMongo = new LMongo;
            //
            $content = 'ShipChung.vn - Quy khach da doi thuong thanh cong chuong trinh KHTT. Ma the doi thuong '.$data['code'].' hoac kiem tra email de nhan thuong. Tran trong!';
            $dataSms = array(
                'to_phone' => $data['phone'],
                'content'  => $content,
                'time_create' => $this->time(),
                'status'     => 0,
                'telco'      => Smslib::CheckPhone($data['phone'])
            );
            // $dataSms = array(
            //     'scenario_id' => 333,
            //     'template_id' => 78,
            //     'transport_id' => 7,
            //     'user_id'   => $data['user_id'],
            //     'received'  => $data['phone'],
            //     'data'      => json_encode(array('content' => $content,'template_id' => 'abgfjkj394023erf')),
            //     'time_create' => $this->time(),
            //     'status'   => 0,
            //     'time_success' => 0
            // );
            $insertSms = $LMongo::collection('log_send_sms')->insert($dataSms);
            //$insertSms = QueueModel::insert($dataSms);
            $dataQueue = array(
                'scenario_id' => 555,
                'template_id' => 66,
                'transport_id' => 1,
                'user_id'       => $data['user_id'],
                'received'    => $data['phone'],
                'data'      => json_encode(array(
                    'content' => $content,
                )),
                'time_create' => $this->time(),
                'time_success' => $this->time() + 360,
                'status' => 1
            );
            $insertQueue = QueueModel::insert($dataQueue);
            if($insertSms){
                $update = CampaignDetailModel::where('id',$data['id'])->update(array('sms' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!',
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
    //email tra thuong
    public function getResponsegift(){
        $data = CampaignDetailModel::where('return',0)->where('campaign_id','>',1)->where('code','!=','')->where('user_id','>',0)->first();
        if(!empty($data)){
            $dataInsert = array();
            $listType = array(1 => 'Viettel',2 => 'Mobifone',3 => 'Vinafone', 4 => 'Vietnammb');
            $infoUser = User::where('id',$data['user_id'])->first();
            $infoCampaign = CampaignModel::where('id',$data['campaign_id'])->first();
            $dataInsert = array(
                'scenario_id' => 5555,
                'template_id' => 64,
                'transport_id' => 2,
                'user_id'       => $data['user_id'],
                'received'    => $infoUser['email'],
                'data'      => json_encode(array(
                    'identifier' => !empty($value['identifier']) ? $value['identifier'] : '',
                    'fullname' => $infoUser['fullname'],
                    'type' => $listType[$data['phone_type']],
                    'code' => $data['code'],
                    'serial' => $data['code_number'],
                    'value' => $infoCampaign['value'],
                )),
                'time_create' => $this->time(),
                'status' => 0
            );
            $insert = QueueModel::insert($dataInsert);
            if($insert){
                $update = CampaignDetailModel::where('id',$data['id'])->update(array('return' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!'
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Fail insert!!'
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