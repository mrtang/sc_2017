<?php

class FacebookController extends \BaseController {
	
	public function __construct(){
	}

	public function postIntegration(){

		$access_token   = Input::json()->get('access_token');
        $profile_id     = Input::json()->get('profile_id');
        $expires        = Input::json()->get('expires');

        $UserInfo   = $this->UserInfo();
        $id         = (int)$UserInfo['id'];


        $validation = Validator::make(Input::json()->all(), array(
            'access_token'      => 'required',
            'profile_id'        => 'required',
            'expires'           => 'required'
        ));


        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        if(!empty($access_token)){

        	$user = User::find($id);
        	
	        if(!empty($user) && !$user['profile_id']) {
	        	// Kiểm tra facebook profile_id đã tồn tại trong hệ thống chưa  
                $checkProfileId = User::where('profile_id', $profile_id)->count();
                if($checkProfileId == 0){
            		$_configTransport = \UserConfigTransportModel::where('user_id', $id)->where('transport_id', 4)->first();

            		if(!$_configTransport){
            			$_configTransport = new \UserConfigTransportModel();
            		}


		            $user['profile_id'] 				= $profile_id;
		            $user['access_token'] 				= $access_token;
		            $user['expires_in'] 				= $expires;
		            $_configTransport['received'] 		= $profile_id;
		            $_configTransport['user_id'] 		= $id;
		            $_configTransport['transport_id'] 	= 4;
		            $_configTransport['active']			= 1;

		            

					try {
						$user->save();	
						$_configTransport->save();
					} catch (Exception $e) {
						$response = [
			                'error' =>  true,
			                'error_message'   =>  'Lỗi server , vui lòng thử lại sau !'
		            	];					

		            	return Response::json($response);
					}
					
					$fbInfo = $this->getInfo($profile_id);
					
					$this->notification($profile_id, 'Chúc mừng ! tài khoản của bạn đã liên kết thành công với hệ thống Shipchung.', 'integration');

		            $response 	= [
		                'error' 			=>  false,
		                'error_message'   	=>  'Liên kết tài khoản facebook thành công',
		                'data'				=> array(
		                	'name'			=> $fbInfo['name'],
		                	'profile_id'	=> $fbInfo['id'],
		                	'link'			=> 'http://facebook.com/'.$fbInfo['id']
	                	)
		            ];

	            }else {
					$response = [
		                'error' 			=>  true,
		                'error_message'   	=>  'Tài khoản facebook này đã được liên kết với tài khoản khác.'
		            ];	            	
	            }
	            return Response::json($response);

	        } else {
	            $response = [
	                'error' 			=>  true,
	                'error_message'     =>  'Liên kết thất bại, vui lòng thử lại sau'
	            ];
	            return Response::json($response);
	        }
        }else {
        	$response = [
                'error' 			=>  true,
                'error_message'   	=>  'Liên kết thất bại, vui lòng thử lại sau'
            ];
	        return Response::json($response);
        }
	}


	public function getTestNoti(){
		$UserInfo   = $this->UserInfo();
        $id         = (int)$UserInfo['id'];

    	$user = User::find($id);
    	$profile_id = $user['profile_id'];

    	return $this->notification($profile_id, 'Tài khoản của bạn đã tích hợp thành công với hệ thống shipchung.', 'integration');

	}

	/** 
	* Destroy facebook integration
	*/
	public function postIntegrationDestroy(){
		$UserInfo   = $this->UserInfo();
        $id         = (int)$UserInfo['id'];

    	$user = User::find($id);
		$_configTransport = \UserConfigTransportModel::where('user_id', $id)->where('transport_id', 4)->first();

		$_removeResult = $this->_removePermission($user->profile_id);

		if($_removeResult){

			$user->profile_id = "";

			
			
			try {
				$user->save();	

				if($_configTransport){
					$_configTransport->active = 0;	
					$_configTransport->save();
				}
			} catch (Exception $e) {
				$response = [
				    'error' =>  true,
				    'error_message'   =>  'Lỗi server , vui lòng thử lại sau !'
				];					
				return Response::json($response);
			}

			$response = [
			    'error' =>  false,
			    'error_message'   =>  'Hủy liên kết thành công'
			];
		}else {
			$response = [
			    'error' =>  true,
			    'error_message'   =>  'Lỗi server , vui lòng thử lại sau !'
			];
		}			

		return Response::json($response);
	}


	private function getInfo($profile_id){
		$fb = $this->facebook(Config::get('facebook.app_token'));
		try {
			$info = $fb->api('/'.$profile_id);
		} catch (Exception $e) {
			$info = null;
		}
		return $info;
	}

	public function notification($profile_id, $msg, $ref){
		$fb = $this->facebook();
		$_result = $fb->api( '/'.$profile_id.'/notifications', 'POST', array(
	        'template' 	=> $msg,
	        'href' 		=> 'fbapp/index.html',
	        'ref'  		=> $ref,
	        'access_token' => Config::get('facebook.app_token')
	    ));
	    return (isset($_result['success']) && $_result['success'] == 1);
	}

	private function _removePermission($profile_id){
		$fb = $this->facebook(Config::get('facebook.app_token'));
		try {
			$_result = $fb->api('/'.$profile_id.'/permissions', 'DELETE');
		} catch (Exception $e) {
			$_result = null;
			return fasle;
		}
		return (isset($_result['success']) && $_result['success'] == 1);
	} 
}
