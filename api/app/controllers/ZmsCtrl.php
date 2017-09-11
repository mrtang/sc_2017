<?php
use ordermodel\OrdersModel;
use omsmodel\CustomerAdminModel;
use sellermodel\UserInfoModel;

class ZmsController extends \BaseController {
    private $domain = '*';
    /**
     * Display a listing of the resource.
     *
     * @return Response
     * @params $scenario, $user_id
     */
     
    public function __construct(){
        
    }

    //get user notice app
    private function deviceToken($id){
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

    private function convertPhone($Phone){
        if($Phone == ''){
            return null;
        }
        $Phone      = str_replace(' ','',trim($Phone));
        $Phone      = str_replace('.','',$Phone);
        $Phone      = substr($Phone, 1,12);
        $Phone      = '84'.$Phone;
        return (int)$Phone;
    }

    //KH moi dang ky
    public function getZmsnewcustomer(){
        $TimeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',$this->time())));
        $TimeEnd = strtotime(date('Y-m-d 00:00:00',$this->time()));
        $Zms = new Zalo;
        
        $InfoUser = User::where('zms',0)->where('time_create','>',$TimeStart)->where('time_create','<',$TimeEnd)->first();
        //$InfoUser = User::where('id',830)->where('zms',0)->first();
        if(!empty($InfoUser)){
            //Kiem tra xem co don hang ko
            $Order = OrdersModel::where('from_user_id',$InfoUser['id'])->where('time_create','>',$TimeStart - 3*86400)->first();
            if(!empty($Order)){
                $Update = User::where('id',$InfoUser['id'])->update(array('zms' => 4));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Da tao don hang!!'
                );
                return Response::json($contents);
            }
            $Phone = $this->convertPhone($InfoUser['phone']);
            if(empty($Phone)){
                $Update = User::where('id',$InfoUser['id'])->update(array('zms' => 2));
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not phone!!'
                );
                return Response::json($contents);
            }
            $SendZms = $Zms->newRegis($Phone);
            //$SendZms = $Zms->newRegis(84976395263);
            if($SendZms >= 0){
                //send notice app
                $Template = TemplateModel::where('id',42)->first();
                $Device = $this->deviceToken($InfoUser['id']);
                if(!empty($Device)){
                    if($Device['android_device_token'] != ''){
                        $dataNoticeApp = array(
                            'os_device' => 'android',
                            'transport_id' => 5,
                            'scenario_id' => 222,
                            'template_id' => 42,
                            'user_id' => $InfoUser['id'],
                            'received' => $InfoUser['email'],
                            'data' => json_encode(
                                array(
                                    'device_token' => $Device['android_device_token'],
                                    'message' => $Template['title'],
                                    'data' => array(
                                        'type' => 'promotion_create_order',
                                        'message' => $Template['title'],
                                        'title' => $Template['title'],
                                        'content' => $Template['content'],
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
                            'scenario_id' => 222,
                            'template_id' => 42,
                            'user_id' => $InfoUser['id'],
                            'received' => $InfoUser['email'],
                            'data' => json_encode(
                                array(
                                    'device_token' => $Device['ios_device_token'],
                                    'message' => $Template['title'],
                                    'data' => array(
                                        'type' => 'promotion_create_order',
                                        'message' => $Template['title'],
                                        'title' => $Template['title'],
                                        'content' => $Template['content'],
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

                $Update = User::where('id',$InfoUser['id'])->update(array('zms' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!'
                );
            }else{
                $Update = User::where('id',$InfoUser['id'])->update(array('zms' => 3));
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

    //don dau tien duyet
    public function getZmsfirstacceptorder(){
        $TimeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-7 day',$this->time())));
        $Zms = new Zalo;

        $Data = CustomerAdminModel::where('time_create','>',$TimeStart)->where('first_order_time','>',0)->where('first_accept_order_time','>',0)->where('zms_first_accept',0)->first();
        if(!empty($Data)){
            //
            $InfoUser = User::where('id',$Data['user_id'])->first();
            $Phone = $this->convertPhone($InfoUser['phone']);
            if(empty($Phone)){
                $Update = CustomerAdminModel::where('id',$Data['id'])->update(array('zms_first_accept' => 2,'zms_not_accept' => 1));
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not phone!!'
                );
                return Response::json($contents);
            }
            $DataZms = array('phone' => $Phone,'tracking_code' => $Data['first_tracking_code']);
            $SendZms = $Zms->acceptFirst($DataZms);
            if($SendZms >= 0){
                //send notice app
                $Template = TemplateModel::where('id',44)->first();
                $Device = $this->deviceToken($Data['user_id']);
                if(!empty($Device)){
                    if($Device['android_device_token'] != ''){
                        $dataNoticeApp = array(
                            'os_device' => 'android',
                            'transport_id' => 5,
                            'scenario_id' => 223,
                            'template_id' => 44,
                            'user_id' => $InfoUser['id'],
                            'received' => $InfoUser['email'],
                            'data' => json_encode(
                                array(
                                    'device_token' => $Device['android_device_token'],
                                    'message' => $Template['title'],
                                    'data' => array(
                                        'type' => 'promotion_guide',
                                        'message' => $Template['title'],
                                        'title' => $Template['title'],
                                        'content' => $Template['content'],
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
                            'scenario_id' => 223,
                            'template_id' => 44,
                            'user_id' => $InfoUser['id'],
                            'received' => $InfoUser['email'],
                            'data' => json_encode(
                                array(
                                    'device_token' => $Device['ios_device_token'],
                                    'message' => $Template['title'],
                                    'data' => array(
                                        'type' => 'promotion_guide',
                                        'message' => $Template['title'],
                                        'title' => $Template['title'],
                                        'content' => $Template['content'],
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

                $Update = CustomerAdminModel::where('id',$Data['id'])->update(array('zms_first_accept' => 1,'zms_not_accept' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!'
                );
            }else{
                $Update = CustomerAdminModel::where('id',$Data['id'])->update(array('zms_first_accept' => 3,'zms_not_accept' => 1));
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

    //sau 2h khong duyet don dau tien
    public function getZmsnotacceptfirstorder(){
        $TimeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-10 day',$this->time())));
        $Cond = $this->time() - 7200;
        $Zms = new Zalo;

        $Data = CustomerAdminModel::where('time_create','>',$TimeStart)->where('first_order_time','<',$Cond)->where('first_order_time','>',0)->where('first_accept_order_time',0)->where('zms_first_accept',0)->first();
        if(!empty($Data)){
            //
            $InfoUser = User::where('id',$Data['user_id'])->first();
            $Phone = $this->convertPhone($InfoUser['phone']);
            if(empty($Phone)){
                $Update = CustomerAdminModel::where('id',$Data['id'])->update(array('zms_not_accept' => 2));
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not phone!!'
                );
                return Response::json($contents);
            }
            $DataZms = array('phone' => $Phone,'tracking_code' => $Data['first_tracking_code']);
            $SendZms = $Zms->notAccept($DataZms);
            if($SendZms >= 0){
                //send notice app
                $Template = TemplateModel::where('id',43)->first();
                $Device = $this->deviceToken($Data['user_id']);
                if(!empty($Device)){
                    if($Device['android_device_token'] != ''){
                        $dataNoticeApp = array(
                            'os_device' => 'android',
                            'transport_id' => 5,
                            'scenario_id' => 224,
                            'template_id' => 43,
                            'user_id' => $InfoUser['id'],
                            'received' => $InfoUser['email'],
                            'data' => json_encode(
                                array(
                                    'device_token' => $Device['android_device_token'],
                                    'message' => $Template['title'],
                                    'data' => array(
                                        'type' => 'wait_accept',
                                        'message' => $Template['title'],
                                        'title' => $Template['title'],
                                        'content' => $Template['content'],
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
                            'scenario_id' => 224,
                            'template_id' => 43,
                            'user_id' => $InfoUser['id'],
                            'received' => $InfoUser['email'],
                            'data' => json_encode(
                                array(
                                    'device_token' => $Device['ios_device_token'],
                                    'message' => $Template['title'],
                                    'data' => array(
                                        'type' => 'wait_accept',
                                        'message' => $Template['title'],
                                        'title' => $Template['title'],
                                        'content' => $Template['content'],
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

                $Update = CustomerAdminModel::where('id',$Data['id'])->update(array('zms_not_accept' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!'
                );
            }else{
                $Update = CustomerAdminModel::where('id',$Data['id'])->update(array('zms_not_accept' => 3));
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

    //don dau tien giao thanh cong
    public function getZmssuccessorder(){
        $TimeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-7 day',$this->time())));
        $Zms = new Zalo;

        $Data = CustomerAdminModel::where('time_create','>',$TimeStart)->where('first_success_order_time','>',0)->where('zms_first_success',0)->first();
        if(!empty($Data)){
            //
            $InfoUser = User::where('id',$Data['user_id'])->first();
            $Phone = $this->convertPhone($InfoUser['phone']);
            if(empty($Phone)){
                $Update = CustomerAdminModel::where('id',$Data['id'])->update(array('zms_first_accept' => 2,'zms_not_accept' => 1));
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not phone!!'
                );
                return Response::json($contents);
            }
            $DataZms = array('phone' => $Phone,'tracking_code' => $Data['first_success_tracking_code']);
            $SendZms = $Zms->acceptFirst($DataZms);
            if($SendZms >= 0){
                //send notice app
                $Template = TemplateModel::where('id',48)->first();
                $Device = $this->deviceToken($Data['user_id']);
                if(!empty($Device)){
                    if($Device['android_device_token'] != ''){
                        $dataNoticeApp = array(
                            'os_device' => 'android',
                            'transport_id' => 5,
                            'scenario_id' => 225,
                            'template_id' => 48,
                            'user_id' => $InfoUser['id'],
                            'received' => $InfoUser['email'],
                            'data' => json_encode(
                                array(
                                    'device_token' => $Device['android_device_token'],
                                    'message' => $Template['title'],
                                    'data' => array(
                                        'type' => 'wait_accept',
                                        'message' => 'Đơn hàng '.$Data['first_success_tracking_code'].' đã giao thành công',
                                        'title' => 'Đơn hàng '.$Data['first_success_tracking_code'].' đã giao thành công',
                                        'content' => $Template['content'],
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
                            'scenario_id' => 225,
                            'template_id' => 48,
                            'user_id' => $InfoUser['id'],
                            'received' => $InfoUser['email'],
                            'data' => json_encode(
                                array(
                                    'device_token' => $Device['ios_device_token'],
                                    'message' => $Template['title'],
                                    'data' => array(
                                        'type' => 'wait_accept',
                                        'message' => 'Đơn hàng '.$Data['first_success_tracking_code'].' đã giao thành công',
                                        'title' => 'Đơn hàng '.$Data['first_success_tracking_code'].' đã giao thành công',
                                        'content' => $Template['content'],
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
                $Update = CustomerAdminModel::where('id',$Data['id'])->update(array('zms_first_success' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!'
                );
            }else{
                $Update = CustomerAdminModel::where('id',$Data['id'])->update(array('zms_first_success' => 3));
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

 
    //Gui  ZMS
    public function getSendzms($Id){
        $Zms = new Zalo;
        $Queue = QueueModel::where('id',$Id)->where('transport_id',7)->where('status',0)->first();
        if(!empty($Queue)){
            $Data = json_decode($Queue['data'],1);
            $DataZms = array(
                'phone' => $this->convertPhone($Queue['received']),
                'templateId' => $Data['template_id'],
                'data'  => $Data['data']
            );
            $SendZms = $Zms->send($DataZms);
            if($SendZms['err'] >= 0){
                $Update = QueueModel::where('id',$Queue['id'])->update(array('status' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!'
                );
            }else{
                $Update = QueueModel::where('id',$Queue['id'])->update(array('status' => 3));
                $contents = array(
                    'error'     => true,
                    'message'   => $SendZms['msg']
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
}
?>