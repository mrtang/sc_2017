<?php namespace seller;

use Validator;
use Exception;
use Response;
use Input;
use Config;
use LMongo;
use sellermodel\UserInfoModel;
use ticketmodel\AssignModel;
use omsmodel\ChangeEmailModel;
use UserConfigTransportModel;
use sellermodel\VimoModel;
use Session;
use Artisaninweb\SoapWrapper\Facades\SoapWrapper;

class UserInfoController extends \BaseController {

    private $result = [];

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    

    public function postCreate()
    {
        $UserInfo   = $this->UserInfo();
        $id = (int)$UserInfo['id'];

        $validation = Validator::make(Input::json()->all(), array(
            'email_nl'      => 'sometimes|email',
            'freeze_money'  => 'sometimes|numeric|min:0',
            'alepay_active' => 'sometimes|in:0,1'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        /**
         * Get Data 
         * */
         
        $EmailNL                = Input::json()->get('email_nl');
        $Freeze                 = Input::has('freeze_money')        ? trim(Input::json()->get('freeze_money'))          : null;
        $PriorityPayment        = Input::has('priority_payment')    ? trim(Input::json()->get('priority_payment'))      : null;
        $Active                 = Input::has('active')              ? (int)Input::json()->get('active')                 : null;
        $AlepayActive           = Input::has('alepay_active')       ? (int)Input::json()->get('alepay_active')          : null;
        $Code                   = Input::has('code')                ? trim(strtoupper(Input::get('code')))              : '';

        if(isset($Freeze)){
            if($Freeze > 0 && $Freeze < 200000){
                return Response::json(array(
                    'error'     => true,
                    'message'   => 'Số tiền không hợp lệ'
                ));
            }
        }
        $Data               = UserInfoModel::firstOrNew(['user_id' => $id]);

        // Change email NL
        if(!empty($EmailNL)){
            if(isset($Data->layers_security) && $Data->layers_security == 1){
                $Security = $this->__check_security($UserInfo['id'], $Code, 3);

                if($Security['error']){
                    return Response::json($Security);
                }else{
                    $Security   = $Security['security'];
                }
            }

            $CheckOutNl =  $this->CheckOutNL($EmailNL, $Data);
            if($CheckOutNl['error']){
                $contents   = $CheckOutNl;
            }else{
                $contents   = ['error'  => false, 'message'   => 'success', 'data'   => $UserInfo['email']];
                if(isset($Data->layers_security) && $Data->layers_security == 1){
                    $Security->active = 2;
                    $Security->save();
                }
            }
            return Response::json($contents);
        }

        
        if(isset($Active))          $Data->active           = $Active;

        //Change active payment alepay
        if(isset($AlepayActive)){
            if(isset($Data->layers_security) && $Data->layers_security == 1){
                $Security = $this->__check_security($UserInfo['id'], $Code, 10);

                if($Security['error']){
                    return Response::json($Security);
                }else{
                    $Security   = $Security['security'];
                }
            }

            $Data->alepay_active = $AlepayActive;

            try {
                if(isset($Data->layers_security) && $Data->layers_security == 1){
                    $Security->active = 2;
                    $Security->save();
                }

                $Data->save();
            } catch (Exception $e) {
                return Response::json([
                    'error'     => true,
                    'message'   => 'Có lỗi xảy ra trong quá trình xử lý, vui lòng thử lại !'
                ]);
            }
        }

        // Change freeze
        if(isset($Freeze)){
            if(isset($Data->layers_security) && $Data->layers_security == 1){
                $Security = $this->__check_security($UserInfo['id'], $Code, 6);

                if($Security['error']){
                    return Response::json($Security);
                }else{
                    $Security   = $Security['security'];
                }
            }

            $Data->freeze_money = $Freeze;

            try {
                if(isset($Data->layers_security) && $Data->layers_security == 1){
                    $Security->active = 2;
                    $Security->save();
                }

                $Data->save();
            } catch (Exception $e) {
                return Response::json([
                    'error'     => true,
                    'message'   => 'Có lỗi xảy ra trong quá trình xử lý, vui lòng thử lại !'
                ]);
            }
        }

        // Priority Payment
        if(isset($PriorityPayment)){
            if(isset($Data->layers_security) && $Data->layers_security == 1){
                $Security = $this->__check_security($UserInfo['id'], $Code, 5);
                if($Security['error']){
                    return Response::json($Security);
                }else{
                    $Security   = $Security['security'];
                }
            }

            if($PriorityPayment == 1){
                $VimoModel = new VimoModel;
                $VimoModel = $VimoModel->where('user_id', $id)->first();
                if($VimoModel){
                    $Data->priority_payment = $PriorityPayment;
                }else {
                    return Response::json(array(
                        'error'     => true,
                        'message'   => 'Bạn chưa cấu hình tài khoản ngân hàng'
                    ));
                }
            }else {
                $Data->priority_payment = $PriorityPayment;
            }
            
            $this->_writeLogChangePayment(['priority_payment'=> $PriorityPayment]);

            try {
                if(isset($Data->layers_security) && $Data->layers_security == 1){
                    $Security->active = 2;
                    $Security->save();
                }

                $Data->save();
            } catch (Exception $e) {
                return Response::json([
                    'error'     => true,
                    'message'   => 'Có lỗi xảy ra trong quá trình xử lý, vui lòng thử lại !'
                ]);
            }
        } 

        return Response::json([
            'error'     => false,
            'message'   => 'Cập nhật thành công',
            'id'        => $Data->id
        ]);
    }

    public function _writeLogChangePayment($Data = [], $id = 0){
        if(empty($Data)){
            $contents = array(
                'error'     => true,
                'message'   => 'Có lỗi xảy ra trong quá trình xử lý, vui lòng thử lại !'
            );
        }

        if(!$id){
            $UserInfo   = $this->UserInfo();
            $id = (int)$UserInfo['id'];
        }
        

        try {
            $LMongo         = new LMongo;    
            $LMongo::collection('log_change_payment')->insert(array( 'user_id' => $id, 'input' => $Data, 'time_create' => $this->time(),'date_create' => date('d/m/Y H:i:s')));
        } catch (Exception $e) {
            $contents = array(
                'error'     => true,
                'message'   => 'Có lỗi xảy ra trong quá trình xử lý, vui lòng thử lại !'
            );
            return Response::json($contents);   
        }
    }
    public function getCheckNL(){
        $UserInfo   = $this->UserInfo();
        $id = (int)$UserInfo['id'];
        $UserInfoModel  = new UserInfoModel;

        $User           = $UserInfoModel::where('user_id', (int)$UserInfo['id'])->first();
        return Response::json([
            "error"     => false,
            "message"   => "",
            "data"      => ($User['email_nl'] && !empty($User['email_nl']))

        ]);
    }


    public function getCheckBalance(){
        $Model      = new   \MerchantModel;

        try{
            $Merchant   = $Model::firstOrCreate(['merchant_id' => $this->UserId]);
        }catch(Exception $e){
            return ['error' => true,'message'   => 'GET_MERCHANT_FAIL'];
        }

        if(!isset($Merchant->active) || $Merchant->active != 1){
            return ['error' => true,'message'   => 'USER_NOT_ALLOW_ACCEPT'];
        }

        if(empty($Merchant->balance)){
            $Merchant->balance = 0;
        }

        if(empty($Merchant->freeze)){
            $Merchant->freeze = 0;
        }

        $Total = $Merchant->balance - $Merchant->freeze + $Merchant->provisional;

        if($Merchant->level >= 2){
            $Total += $Merchant->quota;
        }

        return ['error' => false,'money_total'   => $Total, 'merchant' => $Merchant, 'level' => $Merchant->level];
    }


    
    private function CheckOutNL($EmailNL, $User){
        if($EmailNL == $User['email_nl']){
            return ['error' => false, 'message' => 'Tài khoản ngân lượng đã được liên kết'];
        }

        $UserInfo   = $this->UserInfo();
        //get email NL trong db
        $InfoUserDb = UserInfoModel::where('user_id',(int)$UserInfo['id'])->first();
        SoapWrapper::add(function ($service) {
            $service
                ->name('SoapClientNL')
                ->wsdl(Config::get('config_api.WS_NGANLUONG'))
                ->trace(true)                                                     // Optional: (parameter: true/false)
                //->header()                                                      // Optional: (parameters: $namespace,$name,$data,$mustunderstand,$actor)
                //->cookie()                                                      // Optional: (parameters: $name,$value)
                //->location()                                                    // Optional: (parameter: $location)
                //->cache(WSDL_CACHE_NONE)                                        // Optional: Set the WSDL cache
                //->options(['login' => 'username', 'password' => 'password'])    // Optional: Set some extra options
            ;
        });

        $data = [
            "username"              => Config::get('constants.USER_NGANLUONG'),
            "pass"                  => Config::get('constants.PASS_NGANLUONG'),
            "params"                => ['email' => $EmailNL]
        ];



        // Using the added service
        SoapWrapper::service('SoapClientNL', function ($service) use ($data, $EmailNL, $UserInfo) {
            $this->result = $service->call('GetUserNLId', $data);
        });

        if($this->result->error_code != 'SUCCESS'){
            return ['error'  => true, 'message'  => 'EMAIL_NL_NOT_EXISTS'];
        }

        $refer_code         = 'SCE_'.md5($UserInfo['id'].'_'.$this->time());
        $ChangeEmailModel   = new ChangeEmailModel;
        $UserConfig         = UserConfigTransportModel::where('transport_id',2)->where('user_id', (int)$UserInfo['id'])->first();

        try{
            $SaveData = [
                'user_id'       => (int)$UserInfo['id'],
                'user_nl_id'    => (int)$this->result->result->user_id,
                'received'      => isset($UserConfig->received) ? $UserConfig->received : $UserInfo['email'],
                'refer_code'    => $refer_code,
                'fullname'      => $UserInfo['fullname'],
                'email_nl'      => !empty($InfoUserDb['email_nl']) ? $InfoUserDb['email_nl'] : '',
                'email_nl_new'  => $EmailNL,
                'time_create'   => $this->time()
            ];
    
            $ChangeEmailModel->insert($SaveData);

        }catch(Exception $e){
            return ['error' => true, 'message' => 'INSERT_FAIL'];
        }

        return ['error' => false, 'message' => 'success'];

    }

    public function getToken($uid){
        

        $ChangeEmailModel   = new ChangeEmailModel;
        $ChangeEmail        = $ChangeEmailModel->where('user_id',(int)$uid)->where('status', 1)->first();
        return Response::json($ChangeEmail);
    }
    public function getConfirmchangenl($refer_code){

        $ChangeEmailModel   = new ChangeEmailModel;
        $ChangeEmail        = $ChangeEmailModel->where('refer_code', $refer_code)->where('status', 1)->first();
        if(empty($ChangeEmail)){
            return Response::json(['error' => true, 'message' => 'Mã token không đúng, hoặc đã hết hạn.']);
        }


        $UserInfoModel  = new UserInfoModel;
        $User           = $UserInfoModel::where('user_id', (int)$ChangeEmail->user_id)->first();

        if(empty($User)){
            return Response::json(['error' => true, 'message' => 'Không tìm thấy thông tin người dùng, vui lòng liên hệ CSKH để được hỗ trợ.']);
        }

        try{
            $User->email_nl     = $ChangeEmail->email_nl_new;
            $User->user_nl_id   = $ChangeEmail->user_nl_id;
            $User->save();
        }catch(Exception $e){
            return Response::json(['error' => true, 'message' => 'Lỗi cập nhật dữ liệu, quý khách vui lòng thử lại sau']);
        }


        if(!empty($ChangeEmail)){
            $ChangeEmail->status    = 2;
            try{
                $ChangeEmail->save();
            }catch(Exception $e){

            }
        }
        $this->_writeLogChangePayment(['email_nl'=> $ChangeEmail->email_nl_new], (int)$ChangeEmail->user_id);
        // send sms
        return Response::json(['error' => false, 'message' => 'Cập nhật thành công', 'data' => $User['email_nl']]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function getShow()
    {
        $UserInfo   = $this->UserInfo();
        
        $Model      = new UserInfoModel;
        $Data       = $Model::where('user_id','=',(int)$UserInfo['id'])->first();
        
        if($Data){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $Data
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'user not exits'
            );
        }
        
        return Response::json($contents);
    }
    
    //Get user admin
    /**
     * @param  int  $id   ticket_id
     **/
    public function getUseradmin(){
        $Model      = new UserInfoModel;
                   
        $contents   = array(
            'error' => false,
            'message' => 'success',
            'data' => $Model::where('privilege','>','0')
                ->whereNotIn('user_id',[521,30203])
                ->with('user')->get(array('user_id')));
        return Response::json($contents);
    }

    //
    public function getPrivilegeuser(){
        
        $UserInfo   = $this->UserInfo();
        $id = (int)$UserInfo['id'];
        
        $Model      = new UserInfoModel;
        $Data       = $Model::where('user_id','=',$id)->first();
        
        if($Data){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $Data
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'not exits'
            );
        }
        
        return Response::json($contents);
    }

    private function __insert_sercurity($Type, $Code){
        //Sinh code
        $UserInfo   = $this->UserInfo();

        try{
            \sellermodel\SecurityLayersModel::insert([
                'user_id'        => $UserInfo['id'],
                'type'          => $Type,
                'code'          => $Code,
                'time_create'   => $this->time(),
                'active'        => 1
            ]);
        }catch (Exception $e){
            return ['error' => true, 'message' => 'Có lỗi xảy ra, hãy thử lại'];
        }

        return ['error' => false, 'message' => 'Thành công'];
    }

    private function __send_sms($Code){
        $UserInfo   = $this->UserInfo();
        try{
            $content = 'Dich vu OTP: '.$Code.' .Cổng vận chuyển shipchung.vn';
            $this->__SendSms($UserInfo['phone'], $content, 1);
        }catch (\Exception $e){
            return Response::json([
                'error'     => true,
                'error_message'=> $e,
                'message'   => 'Gửi sms thất bại, hãy thử lại'
            ]);
        }

        return Response::json([
            'error'     => false,
            'message'   => 'Thành công'
        ]);
    }

    // Send sms  OTP
    public function getSendOtp(){
        $validation = Validator::make(Input::all(), array(
            'type'              => 'required|in:1,2,3,4,5,6,7,8,9,10',

            'password_current'  => 'sometimes',

            'password_new'      => 'sometimes|different:password_current|same:password_verify',

            'password_verify'   => 'sometimes|same:password_new'
        ));

        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $Type           = Input::has('type')                ? (int)Input::get('type')                   : 0;
        $PassCurrent    = Input::has('password_current')    ? trim(Input::get('password_current'))      : '';
        $PassNew        = Input::has('password_new')        ? trim(Input::get('password_new'))          : '';

        $UserInfo   = $this->UserInfo();
        if((isset($PassCurrent) && !empty($PassNew))){
            $User = \User::find($UserInfo['id']);
            if(!empty($User->password)){
                if($User->password != md5(md5($PassCurrent))){
                    return Response::json([
                        'error'     => true,
                        'message'   => 'password current incorrect'
                    ]);
                }

            }
        }

        // if(!empty($Phone) && $Phone != $UserInfo->phone){
        //     $UserInfo->phone      = $Phone;
        //     Session::put('user_info',$UserInfo);
        //     $SessionUser = Session::get('user_info');
        // }

        // Đã gửi rồi ko quá 30 phút
        $SecurityLayers = \sellermodel\SecurityLayersModel::where('user_id', $UserInfo['id'])->where('type',$Type)
                                                          ->where('active',1)->where('time_create','>=',$this->time() - 3600)->count();
        if($SecurityLayers > 0){
            return Response::json([
                'error'     => false,
                'message'   => 'Thành công'
            ]);
        }

        $Code       = $this->GenerateCode($UserInfo['id']);
        $Insert = $this->__insert_sercurity($Type, $Code);
        if($Insert['error']){
            return Response::json($Insert);
        }
        
        //Send sms
        return  $this->__send_sms($Code);
    }

    public function getReSendOtp(){
        $Type   = Input::has('type')    ? (int)Input::get('type')   : 0;
        if(empty($Type)){
            return Response::json([
                'error'     => true,
                'message'   => 'Có lỗi xảy ra, hãy thử lại'
            ]);
        }

        $UserInfo               = $this->UserInfo();
        $SecurityLayersModel    = \sellermodel\SecurityLayersModel::where('user_id', $UserInfo['id'])->where('type',$Type)
                                                                   ->where('active',1)->where('time_create','>=',$this->time() - 3600);

        $CountModel     = clone $SecurityLayersModel;
        $SecurityLayers = $CountModel->count();

        // Nếu đã gửi >= 2 lần trong ngày thì ko gửi nữa
        if($SecurityLayers >= 2){
            return Response::json([
                'error'     => false,
                'message'   => 'Thành công'
            ]);
        }

        $Code       = $this->GenerateCode($UserInfo['id']);
        try{
            $SecurityLayersModel->update(['active' => 0]);
        }catch (\Exception $e){
            return Response::json([
                'error'     => true,
                'message'   => 'Cập nhật thất bại'
            ]);
        }

        $Insert = $this->__insert_sercurity($Type, $Code);
        if($Insert['error']){
            return Response::json($Insert);
        }

        return $this->__send_sms($Code);

    }

    // Thay đổi thông tin yêu cầu mã OTP
    public function postChangeInfo(){
        $Type               = 0;
        $Code               = Input::has('code')                ? trim(strtoupper(Input::get('code')))                   : '';
        $LayersSecurity     = Input::has('layers_security')     ? trim(strtolower(Input::get('layers_security')))       : null;

        $UserInfo   = $this->UserInfo();
        $User       = UserInfoModel::where('user_id', $UserInfo['id'])->first();
        if(!isset($User->id)){
            return Response::json([
                'error'     => true,
                'message'   => 'Người dùng không tồn tại'
            ]);
        }

        if(isset($LayersSecurity)){
            $Type = 1;
        }

        //Check Code
        $Security   = \sellermodel\SecurityLayersModel::where('user_id', $UserInfo['id'])->where('type',$Type)
                                                    ->where('active',1)->where('time_create','>=',$this->time() - 3600)->where('code',$Code)->first();

        if(!isset($Security->id)){
            return Response::json([
                'error'     => true,
                'message'   => 'Mã bảo mật không chính xác hoặc đã quá hạn'
            ]);
        }

        if(isset($LayersSecurity)){
            $UserInfo['layers_security']      = $LayersSecurity;
            $User->layers_security          = $LayersSecurity;
        }

        DB::connection('sellerdb')->beginTransaction();
        try{
            $User->save();
            $Security->active = 2;
            $Security->save();
            DB::connection('sellerdb')->commit();
        }catch (Exception $e){
            return Response::json([
                'error'     => true,
                'message'   => 'Cập nhật thất bại'
            ]);
        }


        Session::put('user_info',$UserInfo);
        return Response::json([
            'error'     => false,
            'message'   => 'Thành công'
        ]);

    }


    /**
     * LINKED ALEPAY
     */
    function encryptData($data, $publicKey) {
        $rsa = new \Crypt_RSA();
        $rsa->loadKey($publicKey); // public key
        $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
        $output = $rsa->encrypt($data);
        return base64_encode($output);
    }


    function decryptData($data, $publicKey) {
        $rsa = new \Crypt_RSA();
        $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
        $ciphertext = base64_decode($data);
        $rsa->loadKey($publicKey); // public key
        $output = $rsa->decrypt($ciphertext);
        // $output = $rsa->decrypt($data);
        return $output;
    }

    function decryptCallbackData($data, $publicKey){
        $decoded = base64_decode($data);
        return $this->decryptData($decoded, $publicKey);
    }

    public function __get_token_alepay($UserId){
        $Data = [
            'token'     => Config::get('config_api.ALEPAY_API_KEY'),
            'checksum'  => Config::get('config_api.ALEPAY_CHECKSUM_KEY'),
            'encrypt'   => Config::get('config_api.ALEPAY_ENCRYPT_KEY'),
            'domain'    => 'shipchung.vn'
        ];

        $User = \sellermodel\UserInfoModel::where('user_id', $UserId)->first(['user_id','fulfillment']);
        if(isset($User->user_id) && $User->fulfillment == 1){
            $Data = [
                'token'     => Config::get('config_api.ALEPAY_BM_API_KEY'),
                'checksum'  => Config::get('config_api.ALEPAY_BM_CHECKSUM_KEY'),
                'encrypt'   => Config::get('config_api.ALEPAY_BM_ENCRYPT_KEY'),
                'domain'    => 'boxme.vn'
            ];
        }

        return $Data;
    }

    public function postAlepayLinked(){
        $UserInfo   = $this->UserInfo();
        $id         = (int)$UserInfo['id'];

        /**
         * Get Data
         * */

        $Code                   = Input::has('code')                    ? trim(strtoupper(Input::get('code')))  : '';
        $CountryId              = Input::has('country_id')              ? (int)Input::get('country_id')         : 237;

        $Data                   = UserInfoModel::firstOrNew(['user_id' => $id]);

        // Change email NL
        if(isset($Data->layers_security) && $Data->layers_security == 1){
            $Security = $this->__check_security($UserInfo['id'], $Code, 9);
            if($Security['error']){
                return Response::json($Security);
            }else{
                $Security   = $Security['security'];
            }
        }

        $ReferCode      = 'SCA_'.md5($id).'_'.time().rand(0,1000);

        $PaymentLinked  = [
            'user_id'         => $id,
            'country_id'      => $CountryId,
            'refer_code'      => $ReferCode,
            'email'           => $UserInfo['email'],
            'status'          => 'WAITING',
            'time_create'     => time()
        ];


        $Params = [
            'id'                => $ReferCode,
            'firstName'         => $UserInfo['fullname'],
            'lastName'          => $UserInfo['fullname'],
            'street'            => $UserInfo['address'],
            'city'              => 'Hà Nội',
            'state'             => 'Việt Nam',
            'postalCode'        => '10000',
            'country'           => 'Việt Nam',
            'email'             => $UserInfo['email'],
            'phoneNumber'       => $UserInfo['phone'],
            'callback'          => 'https://seller.shipchung.vn/#/verify_alepay',
        ];

        $AlepayData                 = $this->__get_token_alepay($UserInfo['id']);
        $PaymentLinked['domain']    = $AlepayData['domain'];

        $dataEncrypt = $this->encryptData(json_encode($Params),$AlepayData['encrypt']);
        //echo json_encode($params);
        $checksum = md5($dataEncrypt . $AlepayData['checksum']);
        //var_dump($this->URL['requestPayment']);die;
        $items = array(
            'token'     => $AlepayData['token'],
            'data'      => $dataEncrypt ,
            'checksum'  => $checksum
        );
        $request = \cURL::jsonPost(Config::get('config_api.ALEPAY_API').'request-profile', $items);
        $request = json_decode($request,1);

        if(!$request || !isset($request['errorCode'])){
            return Response::json([
                'error'             => true,
                'message'           => 'Hệ thống gặp sự cố, hãy thử lại',
                'error_message'     => 'Lỗi API'
            ]);
        }elseif($request['errorCode'] != '000'){
            return Response::json([
                'error'         => true,
                'message'       => $request['errorDescription']
            ]);
        }

        $Url    = json_decode($this->decryptData($request['data'], Config::get('config_api.ALEPAY_ENCRYPT_KEY')),1);

        try{
            if(isset($Data->layers_security) && $Data->layers_security == 1){
                $Security->active = 2;
                $Security->save();
            }

            $PaymentLinked['url']       = $Url['url'];
            $PaymentLinked['status']    = 'PROCESSING';
            \sellermodel\PaymentLinkedModel::insert($PaymentLinked);
        }catch (Exception $e){
            return Response::json([
                'error'         => true,
                'message'       => 'Cập nhật dữ liệu thất bại',
                'error_message' => $e->getMessage()
            ]);
        }

        return Response::json([
            'error'         => false,
            'message'       => 'Thành công',
            'url'           => $Url['url']
        ]);
    }

    public function postSucceedLinked(){
        $UserInfo   = $this->UserInfo();
        $id         = (int)$UserInfo['id'];

        $Data       = Input::has('data')        ? Input::get('data')        : '';
        $CheckSum   = Input::has('checksum')    ? Input::get('checksum')    : '';

        if(empty($Data) || empty($CheckSum)){
            return Response::json([
                'error'         => true,
                'message'       => 'Lỗi dữ liệu'
            ]);
        }

        $request = $this->decryptCallbackData($Data, Config::get('config_api.ALEPAY_ENCRYPT_KEY'));
        $request = json_decode($request,1);

        if(!$request || !isset($request['errorCode'])){
            return Response::json([
                'error'             => true,
                'message'           => 'Hệ thống gặp sự cố, hãy thử lại',
                'error_message'     => 'Lỗi API'
            ]);
        }elseif($request['errorCode'] != '000'){
            return Response::json([
                'error'         => true,
                'message'       => $request['errorDescription']
            ]);
        }

        $request        = $request['data'];
        $cardExpDate    = $request['cardExpireYear'] . '-' . $request['cardExpireMonth'];
        $PaymentLinked  = \sellermodel\PaymentLinkedModel::where('user_id', $id)
                                                         ->where('refer_code', $request['customerId'])
                                                         ->where('email', $request['email'])
                                                         ->whereIn('status', ['PROCESSING','SUCCESS'])
                                                         ->first();
        if(!isset($PaymentLinked->id)){
            return Response::json([
                'error'         => true,
                'message'       => 'Tạo yêu cầu liên kết thất bại, hãy thử lại'
            ]);
        }

        if($PaymentLinked->status == 'SUCCESS'){
            return Response::json([
                'error'         => false,
                'message'       => 'Bạn đã liên kết thẻ thành công',
                'data'           => [   'alepay_cardnumber'         => substr_replace($PaymentLinked->cardNumber,'Card *',0,-4),
                                        'alepay_cardholdername'     => $PaymentLinked->cardHolderName,
                                        'alepay_cardexpdate'        => $cardExpDate
                                    ]
                ]);
        }

        try{
            $PaymentLinked->cardHolderName  = $request['cardHolderName'];
            $PaymentLinked->cardNumber      = $request['cardNumber'];
            $PaymentLinked->cardExpireMonth = $request['cardExpireMonth'];
            $PaymentLinked->cardExpireYear  = $request['cardExpireYear'];
            $PaymentLinked->token           = $request['token'];
            $PaymentLinked->status          = 'SUCCESS';
            $PaymentLinked->time_accept     = time();
            $PaymentLinked->save();

            \sellermodel\UserInfoModel::where('user_id', $id)->update([
                'alepay_token'          => $request['token'],
                'alepay_cardnumber'     => $request['cardNumber'],
                'alepay_cardholdername' => $request['cardHolderName'],
                'alepay_cardexpdate'    => $cardExpDate,
                'alepay_active'         => 1
            ]);
        }catch (Exception $e){
            return Response::json([
                'error'         => true,
                'message'       => 'Cập nhật dữ liệu thất bại'
            ]);
        }

        return Response::json([
            'error'         => false,
            'message'       => 'Bạn đã liên kết thẻ thành công',
            'data'           => [   'alepay_cardnumber'     => substr_replace($request['cardNumber'],'Card *',0,-4),
                                    'alepay_cardholdername' => $request['cardHolderName'],
                                    'alepay_cardexpdate'    => $cardExpDate
                                ]
        ]);
    }

}
