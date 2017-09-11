<?php namespace mobile_seller;

use Validator;
use Response;
use Input;
use sellermodel\BankingModel;
use sellermodel\VimoModel;
use sellermodel\UserInfoModel;
use LMongo;
use Excel;

class VimoController extends \BaseController {

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
    public function getListBank(){
        $Bank = [];
        foreach ($this->vimo as $key => $value) {
            $Bank[] = [
                'code' => $key,
                'name' => $value
            ];
        }
        return Response::json([
            'error'         =>false,
            'error_message' => "Thành công",
            'data'          => $Bank
        ]);
    }
	public function postCreate()
	{
		/**
        *  Validation params
        * */
        
        $UserInfo = $this->UserInfo();
        $UserId   = (int)$UserInfo['id'];
        
        Validator::getPresenceVerifier()->setConnection('sellerdb');
        
        $validation = Validator::make(Input::all(), array(
            'id'                => 'sometimes|numeric|exists:vimo_config,id'  ,
            'account_number'    => 'numeric|required',
            'account_name'      => 'required',
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => "Dữ liệu nhập vào không đúng, quý khách vui lòng kiểm tra lại" , 'error_message'=> "Dữ liệu nhập vào không đúng, quý khách vui lòng kiểm tra lại"));
        }
        
        /**
         * Get Data 
         * */
         
        $Id                 = Input::get('id');
        $BankCode           = Input::get('bank_code');
        $AccountName        = Input::get('account_name');
        $AccountNumber      = trim(Input::get('account_number'));
        $Atm                = trim(Input::get('atm_image'));
        $Cmnd_before        = trim(Input::get('cmnd_before_image'));
        $Cmnd_after         = trim(Input::get('cmnd_after_image'));
        $Code               = Input::has('code')                ? trim(strtoupper(Input::get('code')))                   : '';

        
        $User = \User::where('id', $UserId)->first();

        $userInfoModel      = \sellermodel\UserInfoModel::where('user_id', $UserId)->first(['layers_security']);
        if($userInfoModel->layers_security == 1){ // security layers on
            $Security = $this->__check_security($UserId, $Code, 4);
            if($Security['error']){
                return Response::json($Security);
            }else{
                $Security   = $Security['security'];
            }
        }


        $DataCreate         = array('user_id' => $UserId);
        
        if($AccountNumber && (strlen($AccountNumber) > 20 || strlen($AccountNumber) < 16) ){
            return ['error' => true, 'message' => 'Số thẻ ngân hàng không hợp lệ.', 'error_message'=> 'Số thẻ ngân hàng không hợp lệ.'];
        }
        if(empty($Atm)){
            return ['error' => true, 'message' => 'Vui lòng tải lên ảnh scan mặt trước của thẻ ATM.', 'error_message'=> 'Vui lòng tải lên ảnh scan mặt trước của thẻ ATM.'];
        }

        if(empty($Cmnd_before) || empty($Cmnd_after)){
            return ['error' => true, 'message' => 'Vui lòng tải lên ảnh scan hai mặt của CMND.', 'error_message'=> 'Vui lòng tải lên ảnh scan hai mặt của CMND.'];
        }

        $VimoModel = new VimoModel;
        $VimoModel = $VimoModel->where('user_id', $UserId)->first();

        if($VimoModel && $VimoModel->active == 1){
            return [
                'error' => true, 
                'message' => 'Tài khoản của bạn đã được xác thực, để cập nhật vui lòng hiện hệ bộ phận CSKH của Shipchung. Trân trọng !',
                'error_message'=> 'Tài khoản của bạn đã được xác thực, để cập nhật vui lòng hiện hệ bộ phận CSKH của Shipchung. Trân trọng !'
            ];
        }

        $VerifyBankingModel = new \omsmodel\VerifyBankingModel;

        $refer_code         = 'SCV_'.md5($UserInfo['id'].'_'.time());
        $UserConfig         = \UserConfigTransportModel::where('transport_id',2)->where('user_id', (int)$UserInfo['id'])->first();

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        try{
            $VerifyBankingModel->insert([
                'user_id'           => (int)$UserInfo['id'],
                'received'          => isset($UserConfig->received) ? $UserConfig->received : $UserInfo['email'],
                'token'             => $refer_code,
                'bank_code'         => $BankCode,
                'account_name'      => $AccountName,
                'account_number'    => $AccountNumber,
                'atm_image'         => $Atm,
                'cmnd_before_image' => $Cmnd_before,
                'cmnd_after_image'  => $Cmnd_after,
                'ip'                => $ip,
                'time_create'       => time(),
                'time_expired'      => time() + 48*60*60,
                'actived'           => 0
            ]);

            if(isset($userInfoModel->layers_security) && $userInfoModel->layers_security == 1){
                $Security->active = 2;
                $Security->save();
            }

        }catch(Exception $e){
            return ['error' => true, 'message' => 'Lỗi kết nối dữ liệu, hãy thử lại!', 'error_message'=> 'Lỗi kết nối dữ liệu, hãy thử lại!'];
        }

        return ['error' => false, 'message' => 'Cập nhật thành công, vui lòng truy cập vào hòm thư để xác nhận thông tin thẻ !', 'error_message' => 'Cập nhật thành công, vui lòng truy cập vào hòm thư để xác nhận thông tin thẻ !'];

	}


    public function postUploadScanImg(){
        if (Input::hasFile('file')) {
            $File       = Input::file('file');
            $name       = $File->getClientOriginalName();
            $extension  = $File->getClientOriginalExtension();
            $MimeType   = $File->getMimeType();
            $Size       = $File->getClientSize();
            $name       = md5(time()).'.'.$extension;

            if(!in_array($MimeType, array('image/jpeg','image/png','image/jpg'))){

                 return Response::json(array(
                    'error'         => true,
                    'error_message' => 'Tải lên không thành công.',
                    'data'          => ''

                ));
            }
            if(in_array(strtolower($extension), array('jpg','png'))){
                $uploadPath = storage_path(). DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cards';
                $fullpath = DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cards'. DIRECTORY_SEPARATOR .$name;
                
                if($File->move($uploadPath, $name)){
                    return Response::json(array(
                        'error'         => false,
                        'error_message' => 'Tải lên thành công',
                        'data'          => $fullpath
                    ));
                }else {
                    return Response::json(array(
                        'error'         => true,
                        'error_message' => 'Tải lên không thành công.',
                        'data'          => ''
                    ));
                }
                
                
            }else {
                return Response::json(array(
                    'error'         => true,
                    'error_message' => 'Định dạng file không đúng.',
                    'data'          => ''
                ));
            }
        }
        return Response::json(array(
            'error'         => true,
            'error_message' => 'Tải lên không thành công.',
            'data'          => ''
        ));
    }

	public function getShow()
	{
	    $UserInfo   = $this->UserInfo();
        $id = (int)$UserInfo['id'];
        
		$Model      = new VimoModel;
        $Data       = $Model::where('user_id','=',$id)->where('delete', 2)->first();
        
        if($Data){
            $Data->bank_name = $this->vimo[$Data['bank_code']] ? $this->vimo[$Data['bank_code']] : '';
            $contents = array(
                'error'     => false,
                'error_message'   => 'Thành công',
                'data'      => $Data
            );
        }else{
            $contents = array(
                'error'         => true,
                'error_message' => 'Tài khoản chưa liên kết thanh toán qua ngân hàng',
                'data'          => array()
            );
        }
        
        return Response::json($contents);
	}

}
