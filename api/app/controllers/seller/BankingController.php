<?php namespace seller;

use Validator;
use Response;
use Input;
use sellermodel\BankingModel;

class BankingController extends \BaseController {

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate()
	{
		/**
        *  Validation params
        * */
        
        $UserInfo   = $this->UserInfo();
        $UserId = (int)$UserInfo['id'];
        
        Validator::getPresenceVerifier()->setConnection('sellerdb');
        
        $validation = Validator::make(Input::json()->all(), array(
            'id'            => 'sometimes|numeric|exists:banking_config,id'    
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }
        
        /**
         * Get Data 
         * */
         
        $Id                 = Input::json()->get('id');
        $BankCode           = Input::json()->get('bank_code');
        $BankAddress        = Input::json()->get('bank_address');
        $AccountName        = Input::json()->get('account_name');
        $AccountNumber      = Input::json()->get('account_number');
        $Code               = Input::has('code')                ? trim(strtoupper(Input::get('code')))              : '';
        
        $DataCreate         = array('user_id' => $UserId);
        $userInfoModel      = \sellermodel\UserInfoModel::where('user_id', $UserId)->first(['layers_security']);
        if($userInfoModel->layers_security == 1){ // security layers on
            $Security = $this->__check_security($UserInfo['id'], $Code, 7);
            if($Security['error']){
                return Response::json($Security);
            }else{
                $Security   = $Security['security'];
            }
        }

        $Model              = new BankingModel;
        
        $Data               = $Model::firstOrCreate($DataCreate);
        
        if(!empty($BankCode))       $Data->bank_code        = $BankCode;
        if(!empty($BankAddress))    $Data->bank_address     = $BankAddress;
        if(!empty($AccountName))    $Data->account_name     = $AccountName;
        if(!empty($AccountNumber))  $Data->account_number   = $AccountNumber;
        
        $Update = $Data->save();
        
        if($Update){
            if($userInfoModel->layers_security == 1){
                $Security->active = 2;
                $Security->save();
            }
            $contents = array(
                'error'     => false,
                'message'   => 'Thành công',
                'data'        => $Data->id
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Cập nhật thất bại',
                'data'      => array()
            );
        }
        
        return Response::json($contents);
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
        $id = (int)$UserInfo['id'];
        
		$Model      = new BankingModel;
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
                'message'   => 'not exits',
                'data'      => array()
            );
        }
        
        return Response::json($contents);
	}

}
