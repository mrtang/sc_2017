<?php
use sellermodel\UserInfoModel;
use omsmodel\ChangeEmailModel;
use omsmodel\ForgotPasswordModel;
use omsmodel\ChildUserModel;
use omsmodel\VerifyBankingModel;
use omsmodel\NotifyConfirmUser;
use sellermodel\CashInModel;
use accountingmodel\MerchantModel;
use accountingmodel\RefundModel;

class UserNotificationController extends \BaseController {
    private $domain = '*';
    /**
     * Display a listing of the resource.
     *
     * @return Response
     * @params $scenario, $user_id
     */
     
    public function __construct(){
        
    }

    private function getTemplate($scenario_id){
        $listLayout = ScenarioTemplateModel::where('scenario_id','=',$scenario_id)->get(array('template_id'))->toArray();
        $dataReturn = array();
        if(!empty($listLayout)){
            foreach($listLayout AS $val){
                $dataReturn[] = $val['template_id'];
            }
        }
        return $dataReturn;
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

    private function getListUserNew(){
        $output = array();
        $listUser = NotifyConfirmUser::where('notification',0)->where('resend',0)->take(5)->get(array('user_id','id','email','fullname','token'))->toArray();
        if(!empty($listUser)){
            foreach($listUser AS $token){
                $output[] = array(
                    'user_id'  => $token['id'],
                    'fullname' => $token['fullname'],
                    'token'    => $token['token'],
                    'token_id' => $token['id'],
                    'email'    => $token['email']
                );
            }
            return $output;
        }else{
            return false;
        }
    }

    private function getListUserNew(){
        $output = $arrUserId = array();
        $arrUserNew = User::where('time_create','>',time()-86400)->take(5)->get(array('id','email','fullname'))->toArray();
        if(!empty($arrUserNew)){
            foreach($arrUserNew AS $one){
                $arrUserId[] = $one['id'];
            }
            $arrToken = NotifyConfirmUser::whereIn('user_id',$arrUserId)->where('notification',0)->get(array('user_id','id','email','fullname','token'))->toArray();
            foreach($arrToken AS $token){
                foreach($arrUserNew AS $user){
                    if($token['user_id'] == $user['id']){
                        $output[] = array(
                            'user_id'  => $user['id'],
                            'fullname' => $user['fullname'],
                            'token'    => $token['token'],
                            'token_id' => $token['id'],
                            'email' => $token['email']
                        );
                    }
                }
            }
            return $output;
        }else{
            return false;
        }
    }

    public function getEmailregissuccess(){
        $scenario_id = 4;
        //get user 
        $listUser = $this->getListUserNew();
        $dataInsert = $listUserId = $listTokenId = array();
        if(!empty($listUser)){
            $template = $this->getTemplate($scenario_id);
            if(empty($template)){
                $contents = array(
                    'error'         => true,
                    'message'       => 'Not template!',
                );
            }
            foreach($listUser AS $oneUser){
                $dataQueue = array(
                    'fullname' => $oneUser['fullname'],
                    'link_active' => 'http://seller.shipchung.vn/#/app/confirm_email/'.$oneUser['token']
                );
                $dataInsert[] = array(
                    'scenario_id' => $scenario_id,
                    'transport_id' => 2,
                    'template_id' => $template[0],
                    'user_id' => $oneUser['user_id'],
                    'received' => $oneUser['email'],
                    'data' => json_encode($dataQueue),
                    'time_create' => $this->time()
                );
                $listUserId[] = $oneUser['user_id'];
                $listTokenId[]  = $oneUser['token_id'];
            }
            $insert = QueueModel::insert($dataInsert);
            if($insert == true){
                //update notify
                $update = UserInfoModel::whereIn('user_id',$listUserId)->update(array('notification' => 1));
                $updateToken = NotifyConfirmUser::whereIn('id',$listTokenId)->update(array('notification' => 1));
                $contents = array(
                    'error'         => false,
                    'message'       => 'Success!',
                );
            }else{
                $contents = array(
                    'error'         => true,
                    'message'       => 'Not insert!',
                );
            }
        }else{
            $contents = array(
                'error'         => true,
                'message'       => 'Not data send!',
            );
        }

        return Response::json($contents);
    }

    //active user NEW
    private function getUserresendactive(){
        $output = $arrUserId = array();
        $arrUserNew = NotifyConfirmUser::where('resend',1)->take(10)->get(array('user_id','id','email','fullname','token'))->toArray();
        if(!empty($arrUserNew)){
            return $arrUserNew;
        }else{
            return false;
        }
    }
    public function getEmailresendactiveuser(){
        $scenario_id = 23;
        //get user 
        $listUser = $this->getUserresendactive();
        $dataInsert = $listId = array();
        if(!empty($listUser)){
            $template = $this->getTemplate($scenario_id);
            if(empty($template)){
                $contents = array(
                    'error'         => true,
                    'message'       => 'Not template!',
                );
            }
            foreach($listUser AS $oneUser){
                $dataQueue = array(
                    'fullname' => $oneUser['fullname'],
                    'email'    => $oneUser['email'],
                    'link_active' => 'http://seller.shipchung.vn/#/app/confirm_email/'.$oneUser['token']
                );
                $dataInsert[] = array(
                    'scenario_id' => $scenario_id,
                    'transport_id' => 2,
                    'template_id' => $template[0],
                    'user_id' => $oneUser['user_id'],
                    'received' => $oneUser['email'],
                    'data' => json_encode($dataQueue),
                    'time_create' => $this->time()
                );
                $listId[] = $oneUser['id'];
            }

            $insert = QueueModel::insert($dataInsert);
            if($insert == true){
                //update notify
                $update = NotifyConfirmUser::whereIn('id',$listId)->update(array('resend' => 2,'notification' => 1));
                $contents = array(
                    'error'         => false,
                    'message'       => 'Success!',
                );
            }else{
                $contents = array(
                    'error'         => true,
                    'message'       => 'Not insert!',
                );
            }
        }else{
            $contents = array(
                'error'         => true,
                'message'       => 'Not data send!',
            );
        }
        return Response::json($contents);
    }
    //
    public function getEmailwhenchangeemailnl(){
        $scenario_id = 16;
        //get template
        $template = $this->getTemplate($scenario_id);
        if(empty($template)){
            $contents = array(
                'error'         => true,
                'message'       => 'Not template!',
            );
        }
        $start = $this->time() - 6*86400;
        $listUserChangeInfo = ChangeEmailModel::where('status',0)->where('time_create','>',$start)->where('time_success',0)->get(array('id','user_id','received','email_nl','email_nl_new','fullname','refer_code'))->first();
        if(!empty($listUserChangeInfo)){
            //get email sc 
            $infoUser = User::where('id',$listUserChangeInfo['user_id'])->first();
            $dataInsert = array(
                'scenario_id' => $scenario_id,
                'transport_id' => 2,
                'template_id' => $template[0],
                'user_id' => $listUserChangeInfo['user_id'],
                'received' => $infoUser['email'],
                'data' => json_encode(array(
                    'new_data' => $listUserChangeInfo['email_nl_new'],
                    'old_data' => $listUserChangeInfo['email_nl'],
                    'fullname' => $listUserChangeInfo['fullname'],
                    'link_success' => 'http://seller.shipchung.vn/#/access/change_email?refer_code='.$listUserChangeInfo['refer_code']
                )),
                'time_create' => $this->time()
            );

            $insert = QueueModel::insertGetId($dataInsert);
            if($insert > 0){
                //goi api send
                \Predis\Autoloader::register();
                //Now we can start creating a redis client to publish event'6788', '10.0.20.164'
                $redis = new \Predis\Client(array(
                    "scheme" => "tcp",
                    "host" => "10.0.20.164",
                    "port" => 6788
                ));
                //Now we got redis client connected, we can publish event (send event)
                $redis->publish("SendMail", $insert);

                //update notify
                $update = ChangeEmailModel::where('id',$listUserChangeInfo['id'])->update(array('status' => 1));
                $contents = array(
                    'error'         => false,
                    'message'       => 'Success!',
                );
            }else{
                $contents = array(
                    'error'         => true,
                    'message'       => 'Not insert!',
                );
            }
        }else{
            $contents = array(
                'error'         => true,
                'message'       => 'Not data send change email NL!',
            );
        }
        
        return Response::json($contents);
    }
    //quen mat khau
    public function getForgotpassword(){
        $Email     = Input::has('email')  ? Input::get('email') : '';
        if(!$Email){
            return Response::json(['error' => true, 'message' => 'Bạn cần nhập email.']);
        }
        //check email co ton tai ko
        $CheckEmail = \User::where('email',$Email)->first();
        if(empty($CheckEmail)){
            return Response::json(['error' => true, 'message' => 'Email bạn nhập không tồn tại trên hệ thống.']);
        }
        $ForgotPassModel   = new ForgotPasswordModel;
        try{
            $ForgotPassModel->insert([
                'user_id'       => (int)$CheckEmail['id'],
                'email'      => $Email,
                'fullname'      => $CheckEmail['fullname'],
                'time_forgot'   => $this->time(),
                'token'         => md5($Email.$this->time()),
                'time_end_token' => $this->time() + 86400
            ]);

        }catch(Exception $e){
            return ['error' => true, 'message' => 'INSERT_FAIL'];
        }

        return ['error' => false, 'message' => 'success'];
    }
    //save new password
    public function getSavepassword(){
        $ForgotPassModel   = new ForgotPasswordModel;
        $Token     = Input::has('token')  ? Input::get('token') : '';
        if(empty($Token)){
            return Response::json(['error' => true, 'message' => 'Mã xác thực không tồn tại']);
        }
        $NewPassword     = Input::has('new_pass')  ? Input::get('new_pass') : '';
        if(empty($NewPassword)){
            return Response::json(['error' => true, 'message' => 'Bạn cần nhập mật khẩu mới']);
        }
        $ReNewPassword     = Input::has('re_new_pass')  ? Input::get('re_new_pass') : '';
        if(empty($ReNewPassword)){
            return Response::json(['error' => true, 'message' => 'Bạn cần nhập lại mật khẩu mới']);
        }
        if($NewPassword != $ReNewPassword){
            return Response::json(['error' => true, 'message' => 'Bạn xác nhận mật khẩu chưa đúng']);
        }
        $CheckToken = $ForgotPassModel->where('token',$Token)->first();
        if(empty($CheckToken)){
            return Response::json(['error' => true, 'message' => 'Mã xác thực không hợp lệ']);
        }
        if($CheckToken['time_end_token'] < $this->time()){
            return Response::json(['error' => true, 'message' => 'Mã xác thực của bạn đã hết thời gian tồn tại. Vui lòng ấn quên mật khẩu lại để nhận lại link xác thực mật khẩu mới.']);
        }
        $CheckPassword = \User::where('id',$CheckToken['user_id'])->first();
        if($CheckPassword['password'] == md5(md5($NewPassword))){
            return Response::json(['error' => true, 'message' => 'Mật khẩu mới của bạn không thay đổi so với mật khẩu hiện tại. Vui lòng đăng nhập bằng mật khẩu hiện tại']);
        }
        $Update = User::where('id',$CheckToken['user_id'])->update(array('password' => md5(md5($NewPassword))));
        if($Update){
            $ForgotPassModel->where('id',$CheckToken['id'])->update(array('token' => ''));
            return Response::json(['error' => false, 'message' => 'Thay đổi mật khẩu thành công']);
        }else{
            return Response::json(['error' => true, 'message' => 'Lỗi cập nhật mật khẩu mới. Vui lòng liên hệ bộ phận chăm sóc khách hàng để được hỗ trợ']);
        }
    }
    //insert queue forgot pass
    public function getEmailwhenforgotpass(){
        $scenario_id = 18;
        //get template
        $template = $this->getTemplate($scenario_id);
        if(empty($template)){
            $contents = array(
                'error'         => true,
                'message'       => 'Not template!',
            );
        }

        $start = $this->time() - 3*86400;
        $listUserForgotPass = ForgotPasswordModel::where('notification',0)->where('time_success',0)->where('time_forgot','>',$start)->get(array('id','user_id','email','fullname','token'))->first();
        if(!empty($listUserForgotPass)){
            $dataInsert = array(
                'scenario_id' => $scenario_id,
                'transport_id' => 2,
                'template_id' => $template[0],
                'user_id' => $listUserForgotPass['user_id'],
                'received' => $listUserForgotPass['email'],
                'data' => json_encode(array(
                    'fullname' => $listUserForgotPass['fullname'],
                    'link_get' => 'http://seller.shipchung.vn/#/access/getpwd?token='.$listUserForgotPass['token']
                )),
                'time_create' => $this->time()
            );

            $insert = QueueModel::insertGetId($dataInsert);
            if($insert > 0){
                //goi api send
                \Predis\Autoloader::register();
                //Now we can start creating a redis client to publish event
                $redis = new \Predis\Client(array(
                    "scheme" => "tcp",
                    "host" => "10.0.20.164",
                    "port" => 6788
                ));
                //Now we got redis client connected, we can publish event (send event)
                $redis->publish("SendMail", $insert);

                //update notify
                $update = ForgotPasswordModel::where('id',$listUserForgotPass['id'])->update(array('notification' => 1,'time_success' => $this->time()));
                $contents = array(
                    'error'         => false,
                    'message'       => 'Success!',
                );
            }else{
                $contents = array(
                    'error'         => true,
                    'message'       => 'Not insert!',
                );
            }
        }else{
            $contents = array(
                'error'         => true,
                'message'       => 'Not data send!',
            );
        }
        
        return Response::json($contents);
    }
    //email thiet lap tk cha con
    public function getEmailveifychild(){
        $scenario_id = 20;
        $template = $this->getTemplate($scenario_id);
        if(empty($template)){
            $contents = array(
                'error'         => true,
                'message'       => 'Not template!',
            );
        }
        $start = $this->time() - 3*86400;
        $dataInsert = array();
        $listData = ChildUserModel::where('notification',0)->where('time_success',0)->where('time_create','>',$start)->first();

        if(!empty($listData)){
            $listInfoUser = \User::where('id',$listData['child_id'])->get(array('id','fullname'))->first();
            if(!empty($listInfoUser)){
                $dataInsert = array(
                    'scenario_id' => $scenario_id,
                    'template_id' => $template[0],
                    'transport_id' => 2,
                    'user_id'   => $listInfoUser['id'],
                    'received'  => $listData['child_email'],
                    'data'      => json_encode(array(
                        'fullname' => $listInfoUser['fullname'],
                        'parent_email'     => $listData['parent_email'],
                        'child_email'     => $listData['child_email'],
                        'link'              => 'http://seller.shipchung.vn/#/access/verify_child/'.$listData['token']
                    )),
                    'time_create' => $this->time(),
                );
                
                $insert = QueueModel::insertGetId($dataInsert);
                if($insert > 0){
                    //goi api send
                    \Predis\Autoloader::register();
                    //Now we can start creating a redis client to publish event
                    $redis = new \Predis\Client(array(
                        "scheme" => "tcp",
                        "host" => "10.0.20.164",
                        "port" => 6788
                    ));
                    //Now we got redis client connected, we can publish event (send event)
                    $redis->publish("SendMail", $insert);

                    //update notify
                    $update = ChildUserModel::where('id',$listData['id'])->update(array('notification' => 1));
                    $contents = array(
                        'error'         => false,
                        'message'       => 'Success!',
                    );
                }else{
                    $contents = array(
                        'error'         => true,
                        'message'       => 'Not insert!',
                    );
                }
            }else{
                $contents = array(
                    'error'         => true,
                    'message'       => 'Not parent user!',
                );
            }
        }else{
            $contents = array(
                'error'         => true,
                'message'       => 'Not data send!',
            );
        }
        return Response::json($contents);
    }
    //email when add account bank
    public function getCreateaccountbank(){
        $scenario_id = 22;
        $template = $this->getTemplate($scenario_id);
        if(empty($template)){
            $contents = array(
                'error'         => true,
                'message'       => 'Not template!',
            );
        }
        $dataInsert = array();
        $listData = VerifyBankingModel::where('notify',0)->first();
        if(!empty($listData)){
            $listInfoUser = \User::where('id',$listData['user_id'])->get(array('id','fullname','email'))->first();
            if(!empty($listInfoUser)){
                $dataInsert = array(
                    'scenario_id' => $scenario_id,
                    'template_id' => $template[0],
                    'transport_id' => 2,
                    'user_id'   => $listData['user_id'],
                    'received'  => $listInfoUser['email'],
                    'data'      => json_encode(array(
                        'fullname' => $listInfoUser['fullname'],
                        'link'              => 'http://seller.shipchung.vn/#/access/verify_bank/'.$listData['token'],
                        'bank_name'         => $listData['bank_code'],
                        'bank_account_name' => $listData['account_name'],
                        'bank_account_num'  => $listData['account_number']
                    )),
                    'time_create' => $this->time(),
                );
                
                $insert = QueueModel::insertGetId($dataInsert);
                if($insert > 0){
                    //goi api send
                    \Predis\Autoloader::register();
                    //Now we can start creating a redis client to publish event
                    $redis = new \Predis\Client(array(
                        "scheme" => "tcp",
                        "host" => "10.0.20.164",
                        "port" => 6788
                    ));
                    //Now we got redis client connected, we can publish event (send event)
                    $redis->publish("SendMail", $insert);

                    //update notify
                    $update = VerifyBankingModel::where('id',$listData['id'])->update(array('notify' => 1));
                    $contents = array(
                        'error'         => false,
                        'message'       => 'Success!',
                    );
                }else{
                    $contents = array(
                        'error'         => true,
                        'message'       => 'Not insert!',
                    );
                }
            }else{
                $contents = array(
                    'error'         => true,
                    'message'       => 'Not parent user!',
                );
            }
        }else{
            $contents = array(
                'error'         => true,
                'message'       => 'Not data send!',
            );
        }

        return Response::json($contents);
    }
    //nap tien vao tk thanh cong
    public function getEmailcashin(){
        $scenario_id = 6;
        $template = $this->getTemplate($scenario_id);
        if(empty($template)){
            $contents = array(
                'error'         => true,
                'message'       => 'Not template!',
            );
        }
        $dataInsert = array();
        $start = $this->time() - 3*86400;
        $dataCashin = CashInModel::where('notify',0)->where('status','SUCCESS')->where('time_success','>',0)->where('time_create','>',$start)->first();
        if(!empty($dataCashin)){
            $method = '';
            if($dataCashin['method'] == 1){
                $method = 'Ngân Lượng';
            }elseif($dataCashin['method'] == 2){
                $method = 'Ngân Hàng';
            }
            $dataUser = User::where('id',$dataCashin['user_id'])->first();
            $balanceUser = MerchantModel::where('merchant_id',$dataCashin['user_id'])->first();
            $dataInsert = array(
                'scenario_id' => $scenario_id,
                'template_id' => $template[0],
                'transport_id' => 2,
                'user_id'   => $dataCashin['user_id'],
                'received'  => $dataUser['email'],
                'data'      => json_encode(array(
                    'fullname'          => $dataUser['fullname'],
                    'cashin_money'      => $dataCashin['amount'],
                    'method'            => $method,
                    'balance'           => $balanceUser['balance']
                )),
                'time_create' => $this->time(),
            );
            $insert = QueueModel::insertGetId($dataInsert);
            if($insert > 0){
                //get template app,sms
                $templateApp = TemplateModel::where('id',27)->first();
                $content = DbView::make($templateApp)->with(['amount' => $dataCashin['amount'],'email' => $dataUser['email']])->render();
                //send sms
                $LMongo         = new LMongo;
                if(!empty($dataUser['phone'])){
                    $dataSms = array(
                        'to_phone' => $dataUser['phone'],
                        'content'  => $content,
                        'time_create' => $this->time(),
                        'status'     => 0,
                        'telco'      => Smslib::CheckPhone($dataUser['phone'])
                    );
                    $insertSms = $LMongo::collection('log_send_sms')->insert($dataSms);
                }
                //notice web
                $dataNoticeWeb = array(
                    'transport_id' => 1,
                    'scenario_id' => 555,
                    'template_id' => 67,
                    'user_id' => $dataCashin['user_id'],
                    'received' =>  $dataUser['email'],
                    'data' => json_encode(array('content' => $content)),
                    'time_create' => $this->time(),
                    'status' => 1,
                    'time_success' =>  $this->time() + 312
                );
                $noticeWeb = QueueModel::insert($dataNoticeWeb);
                //thong bao qua APP
                $dataNoticeApp = array();
                $userDevice = $this->getUserapp($dataCashin['user_id']);
                if(!empty($userDevice)){
                    if($userDevice['android_device_token'] != ''){
                        $dataNoticeApp = array(
                            'os_device' => 'android',
                            'transport_id' => 5,
                            'scenario_id' => 24,
                            'template_id' => 27,
                            'user_id' => $dataCashin['user_id'],
                            'received' => $dataUser['email'],
                            'data' => json_encode(array('device_token' => $userDevice['android_device_token'],'data' => array('message' => $content))),
                            'time_create' => $this->time()
                        );
                    }elseif($userDevice['ios_device_token'] != ''){
                        $dataNoticeApp = array(
                            'os_device' => 'ios',
                            'transport_id' => 5,
                            'scenario_id' => 24,
                            'template_id' => 27,
                            'user_id' => $dataCashin['user_id'],
                            'received' => $dataUser['email'],
                            'data' => json_encode(array('device_token' => $userDevice['ios_device_token'],'message' => $content,'data' => array())),
                            'time_create' => $this->time()
                        );
                    }
                    if(!empty($dataNoticeApp)){
                        QueueModel::insert($dataNoticeApp);
                    }
                }
                //goi api send
                \Predis\Autoloader::register();
                //Now we can start creating a redis client to publish event
                $redis = new \Predis\Client(array(
                    "scheme" => "tcp",
                    "host" => "10.0.20.164",
                    "port" => 6788
                ));
                //Now we got redis client connected, we can publish event (send event)
                $redis->publish("SendMail", $insert);
                $update = CashInModel::where('id',$dataCashin['id'])->update(array('notify' => 1));
                $contents = array(
                    'error'         => false,
                    'message'       => 'Success!',
                );
            }else{
                $contents = array(
                    'error'         => true,
                    'message'       => 'Not send!',
                );
            }
        }else{
            $contents = array(
                'error'         => true,
                'message'       => 'Not data cash in!',
            );
        }

        return Response::json($contents);
    }
    //khi hoan tien cho KH
    public function getSmsrefund(){
        $LMongo         = new LMongo;
        $dataInsert = array();
        $start = $this->time() - 3*86400;
        $scenario_id = 26;
        $dataRefund = RefundModel::where('notify',0)->where('status','SUCCESS')->where('time_accept','>',0)->where('time_create','>',$start)->first();
        if(!empty($dataRefund)){
            $dataUser = \User::where('id',$dataRefund['merchant_id'])->first();
            //get template app,sms
            $templateApp = TemplateModel::where('id',30)->first();
            $content = DbView::make($templateApp)->with(['reason' => $dataRefund['reason'],'amount' => $dataRefund['amount'],'email' => $dataUser['email']])->render();
            $phone = str_replace(array(';','-',' ','.'), ',', $dataUser['phone']);
            $arrPhone = explode(',', $phone);
            if(!empty($arrPhone[0])){
                $dataSms = array(
                    'to_phone' => $arrPhone[0],
                    'content'  => $content,
                    'time_create' => $this->time(),
                    'status'     => 0,
                    'priority'   => 0,
                    'telco'      => Smslib::CheckPhone($arrPhone[0])
                );
                $insertSms = $LMongo::collection('log_send_sms')->insert($dataSms);
                if($insertSms){
                    //notice web
                    $dataNoticeWeb = array(
                        'transport_id' => 1,
                        'scenario_id' => 555,
                        'template_id' => 68,
                        'user_id' => $dataRefund['merchant_id'],
                        'received' => $arrPhone[0],
                        'data' => json_encode(array('content' => $content)),
                        'time_create' => $this->time(),
                        'status' => 1,
                        'time_success' =>  $this->time() + 312
                    );
                    $noticeWeb = QueueModel::insert($dataNoticeWeb);
                    //notice app
                    $dataNoticeApp = array();
                    $userDevice = $this->getUserapp($dataRefund['merchant_id']);
                    if(!empty($userDevice)){
                        if($userDevice['android_device_token'] != ''){
                            $dataNoticeApp = array(
                                'os_device' => 'android',
                                'transport_id' => 5,
                                'scenario_id' => $scenario_id,
                                'template_id' => 30,
                                'user_id' => $dataRefund['merchant_id'],
                                'received' => $dataUser['email'],
                                'data' => json_encode(array('device_token' => $userDevice['android_device_token'],'data' => array('message' => $content))),
                                'time_create' => $this->time()
                            );
                        }elseif($userDevice['ios_device_token'] != ''){
                            $dataNoticeApp = array(
                                'os_device' => 'ios',
                                'transport_id' => 5,
                                'scenario_id' => $scenario_id,
                                'template_id' => 30,
                                'user_id' => $dataRefund['merchant_id'],
                                'received' => $dataUser['email'],
                                'data' => json_encode(array('device_token' => $userDevice['ios_device_token'],'message' => $content,'data' => array())),
                                'time_create' => $this->time()
                            );
                        }
                        if(!empty($dataNoticeApp)){
                            QueueModel::insert($dataNoticeApp);
                        }
                    }
                    $update = RefundModel::where('id',$dataRefund['id'])->update(array('notify' => 1));
                    $contents = array(
                        'error'         => false,
                        'message'       => 'Success!!!',
                    );
                }else{
                    $contents = array(
                        'error'         => true,
                        'message'       => 'Not send sms!',
                    );
                }
            }else{
                $update = RefundModel::where('id',$dataRefund['id'])->update(array('notify' => 1));
                $contents = array(
                    'error'         => true,
                    'message'       => 'Not phone!',
                );
            }
        }else{
            $contents = array(
                'error'         => true,
                'message'       => 'Not data refund!',
            );
        }
        return Response::json($contents);
    }
    //
}
?>
