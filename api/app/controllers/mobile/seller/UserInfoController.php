<?php namespace mobile_seller;

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

        $validation = Validator::make(Input::all(), array(
            'email_nl'      => 'sometimes|email',
            'freeze_money'  => 'sometimes|numeric|min:0',
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        /**
         * Get Data 
         * */
         
        $EmailNL                = Input::get('email_nl');
        $Freeze                 = Input::has('freeze_money')        ? trim(Input::get('freeze_money'))          : null;
        $PriorityPayment        = Input::has('priority_payment')    ? trim(Input::get('priority_payment'))      : null;
        $Active                 = Input::has('active')              ? (int)Input::get('active')                 : null;
        $Code                   = Input::has('code')                ? trim(strtoupper(Input::get('code')))              : '';

        if(isset($Freeze)){
            if($Freeze > 0 && $Freeze < 200000){
                return Response::json(array(
                    'error'     => true,
                    'message'   => 'Số tiền không hợp lệ',
                    'error_message'   => 'Số tiền không hợp lệ'

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
                $contents   = ['error'  => false, 'message'   => 'Cập nhật thành công, vui lòng truy cập vào hòm thư để xác nhận thông tin !', 'error_message'=>'Cập nhật thành công, vui lòng truy cập vào hòm thư để xác nhận thông tin !', 'data'   => $UserInfo['email']];
                if(isset($Data->layers_security) && $Data->layers_security == 1){
                    $Security->active = 2;
                    $Security->save();
                }
            }
            $contents['input'] = Input::all();
            return Response::json($contents);
        }

        
        if(isset($Active))          $Data->active       = $Active;

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
            $this->_writeLogChangePayment(['freeze_money'=> $Freeze, 'old_freeze_money'=> $Data->freeze_money]);
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
                    'message'   => 'Có lỗi xảy ra trong quá trình xử lý, vui lòng thử lại !',
                    'message'   => 'Có lỗi xảy ra trong quá trình xử lý, vui lòng thử lại !'
                ]);
            }

            //$this->_writeLogChangePayment(['freeze_money'=> $Freeze]);
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
                        'message'   => 'Bạn chưa cấu hình tài khoản ngân hàng',
                        'error_message'   => 'Bạn chưa cấu hình tài khoản ngân hàng'
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
                    'message'   => 'Có lỗi xảy ra trong quá trình xử lý, vui lòng thử lại !',
                    'error_message'   => 'Có lỗi xảy ra trong quá trình xử lý, vui lòng thử lại !'

                ]);
            }
        } 

        return Response::json([
            'error'     => false,
            'message'   => 'Cập nhật thành công',
            'id'        => $Data->id
        ]);
    }

    public function _writeLogChangePayment(){
        try {
            $LMongo         = new LMongo;    
            $LMongo::collection('log_change_payment')->insert(array( 'input' => Input::all(),'time_create' => time(),'date_create' => date('d/m/Y H:i:s') ));
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

    private function CheckOutNL($EmailNL, $User){
        if($EmailNL == $User['email_nl']){
            return ['error' => false, 'message' => 'Tài khoản ngân lượng đã được liên kết', 'error_message'=> 'Tài khoản ngân lượng đã được liên kết'];
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
            return ['error'  => true, 'message'  => 'Tài khoản ngân lượng không tồn tại, hoặc chưa được kích hoạt.', 'error_message'=> 'Tài khoản ngân lượng không tồn tại, hoặc chưa được kích hoạt.'];
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
            return ['error' => true, 'message' => 'Lỗi máy chủ, vui lòng thử lại sau.', 'error_message'=> 'Lỗi máy chủ, vui lòng thử lại sau.'];
        }

        return ['error' => false, 'message' => 'success', 'Liên kết tài khoản thành công.'=> 'Liên kết tài khoản thành công.'];

    }

    public function getToken($uid){
        

        $ChangeEmailModel   = new ChangeEmailModel;
        $ChangeEmail        = $ChangeEmailModel->where('user_id',(int)$uid)->where('status', 1)->first();
        return Response::json($ChangeEmail);
    }
    public function getConfirmchangenl($refer_code){
        $UserInfo   = $this->UserInfo();

        $UserInfoModel  = new UserInfoModel;
        $User           = $UserInfoModel::where('user_id', (int)$UserInfo['id'])->first();

        if(empty($User)){
            return Response::json(['error' => true, 'message' => 'USER_INFO_NOT_EXISTS']);
        }


        $ChangeEmailModel   = new ChangeEmailModel;
        $ChangeEmail        = $ChangeEmailModel->where('user_id',(int)$UserInfo['id'])->where('refer_code',$refer_code)->where('status',1)->first();
        if(empty($ChangeEmail)){
            return Response::json(['error' => true, 'message' => 'LOG_CHANGE_NOT_EXISTS']);
        }

        try{
            $User->email_nl     = $ChangeEmail->email_nl_new;
            $User->user_nl_id   = $ChangeEmail->user_nl_id;
            $User->save();
        }catch(Exception $e){
            return Response::json(['error' => true, 'message' => 'UPDATE_USER_FAIL']);
        }


        if(!empty($ChangeEmail)){
            $ChangeEmail->status    = 2;
            try{
                $ChangeEmail->save();
            }catch(Exception $e){

            }
        }

        // send sms
        return Response::json(['error' => false, 'message' => 'success', 'data' => $User['email_nl']]);
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
        $Data       = $Model::where('user_id','=',(int)$UserInfo['id'])->select('id', 'user_id', 'priority_payment', 'freeze_money', 'email_nl')->first();
        
        if($Data){
            $contents = array(
                'error'         => false,
                'message'       => 'success',
                'error_message' => 'Thành công',
                'data'          => $Data
            );
        }else{
            $contents = array(
                'error'         => true,
                'error_message' => 'Tài khoản không tồn tại',
                'message'       => 'user not exits'
            );
        }
        
        return Response::json($contents);
	}

    private function __send_sms($Code){
        $UserInfo   = $this->UserInfo();
        //$a = $this->__SendSms($UserInfo['phone'], $content, 1);
        try{
            $content = 'Dich vu OTP: '.$Code.' .Cổng vận chuyển shipchung.vn';
            $this->__SendSms($UserInfo['phone'], $content, 1);
        }catch (\Exception $e){
            return Response::json([
                'error'     => true,
                'error_message'=> $e->getMessage(),
                'message'   => 'Gửi sms thất bại, hãy thử lại'
            ]);
        }

        return Response::json([
            'error'     => false,
            'message'   => 'Thành công'
        ]);
    }

    public function getReSendOtp(){
        var_dump(34);die;
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


    // Send sms  OTP
    public function getSendOtp(){
        $validation = Validator::make(Input::all(), array(
            'type'              => 'required|in:1,2,3,4,5,6,7,8',

            'phone'             => 'sometimes|required|numeric',

            'password_current'  => 'sometimes',

            'password_new'      => 'sometimes|different:password_current|same:password_verify',

            'password_verify'   => 'sometimes|same:password_new'
        ));

        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $Type           = Input::has('type')                ? (int)Input::get('type')                   : 0;
        $Phone          = Input::has('phone')               ? trim(Input::get('phone'))                 : '';
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

        // if(!empty($Phone) && $Phone == $UserInfo->phone){
        //     return Response::json([
        //         'error'     => false,
        //         'message'   => 'Thành công'
        //     ]);
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
            return ['error' => true, 'message' => 'Có lỗi xảy ra, hãy thử lại', 'error_message'=> $e->getMessage()];
        }

        return ['error' => false, 'message' => 'Thành công'];
    }


    public function postChangePhoneOtp(){
        $Type         = 9;
        $Code         = Input::has('code')          ? trim(strtoupper(Input::get('code')))            : '';
        $NewPhone     = Input::has('new_phone')     ? trim(strtolower(Input::get('new_phone')))       : null;

        $UserInfo   = $this->UserInfo();
        $User       = UserInfoModel::where('user_id', $UserInfo['id'])->first();


        if(empty($NewPhone)){
            return Response::json([
                'error'     => true,
                'message'   => 'Vui lòng nhập số điện thoại mới'
            ]);
        }
        if(!isset($User->id)){
            return Response::json([
                'error'     => true,
                'message'   => 'Người dùng không tồn tại'
            ]);
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

        $User->phone          = $NewPhone;


        \DB::connection('sellerdb')->beginTransaction();
        try{
            $User->save();
            $Security->active = 2;
            $Security->save();
            \DB::connection('sellerdb')->commit();
        }catch (\Exception $e){
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

    // Thay đổi thông tin yêu cầu mã OTP
    public function postChangeInfo(){
        $Type               = 1;
        $Code               = Input::has('code')                ? trim(strtoupper(Input::get('code')))                   : '';
        $LayersSecurity     = Input::has('layers_security')     ? trim(strtolower(Input::get('layers_security')))       : null;
        $NewPhone     		= Input::has('new_phone')     ? trim(strtolower(Input::get('new_phone')))       : null;


        $UserInfo   = $this->UserInfo();
        $User       = UserInfoModel::where('user_id', $UserInfo['id'])->first();
        if(!isset($User->id)){
            return Response::json([
                'error'     => true,
                'message'   => 'Người dùng không tồn tại'
            ]);
        }

        $UserTable = \User::find($UserInfo['id']);

        // if(isset($LayersSecurity)){
        //     $Type = 1;
        // }

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

        if(isset($NewPhone)){
            $UserInfo['phone']    = $NewPhone;
            $UserTable->phone     = $NewPhone;
        }

        \DB::connection('sellerdb')->beginTransaction();
        try{
        	$UserTable->save();
            $User->save();
            $Security->active = 2;
            $Security->save();
            \DB::connection('sellerdb')->commit();
        }catch (\Exception $e){
            return Response::json([
                'error'     => true,
                'message'   => 'Cập nhật thất bại',
                'error_message'=> $e->getMessage()
            ]);
        }


        Session::put('user_info',$UserInfo);
        return Response::json([
            'error'     => false,
            'message'   => 'Thành công',
            'data'      => [
                'layers_security'=> $LayersSecurity,
                'user'  => $User
            ]
        ]);
    }

}
