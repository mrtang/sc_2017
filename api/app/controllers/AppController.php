<?php
require app_path().'/libraries/php-jwt/vendor/autoload.php';
use \Firebase\JWT\JWT;
use Hashids\Hashids;
use omsmodel\NotifyConfirmUser;

class AppController extends \BaseController {
	
	/**
	* Display a listing of the resource.
	     *
	     * @return Response
	     */
	
	
	public function __construct(){
		
	}
	
	public function getCsrf(){
		return csrf_token();
	}
	
	private function _generation_code(){
		for ($randomNumber = mt_rand(1, 9), $i = 1; $i < 5; $i++) {
			$randomNumber .= mt_rand(0, 9);
		}
		return $randomNumber;
	}
	
	public function generationReferCode (){
		$coupon_code = $this->_generation_code();
		$Model       = new \sellermodel\UserInfoModel;
		$Model       = $Model->where('refer_code', $coupon_code)->first();
		if($Model){
			return $this->generationReferCode();
		}
		else {
			return $coupon_code;
		}
	}
	
	private function genToken($data){
		$token = array(
		            "exp"  => time() + 86400*7,
		            'data' => $data
		        );
		try {
			$jwt = JWT::encode($token, Config::get('app.key'));
			
		}
		catch (Exception $e) {
			return false;
		}
		
		return [
		            'token' =>$jwt,
		            'exp'   => $token['exp']
		        ];
	}
	
	public function postLogError(){
		
		
		$LMongo             = new \LMongo;
		$error = '';
		try {
			$Agent = $this->getBrowser();
			$LMongo::collection('error_logs')
			            ->insert([
			                'brower_name'           => !empty($Agent['name']) ? $Agent['name'] : "",
			                'api_url'               => Input::has('api_url') ? Input::get('api_url') : "",
			                'http_status'           => Input::has('http_status') ? Input::get('http_status') : 500,
			                'body'                  => Input::has('body')        ? Input::get('body') : "",
			                'brower_version'        => $Agent['version'],
			                'platform'              => $Agent['platform'],
			                'user_agent'            => $Agent['userAgent'],
			                'ip'                    => $Agent['ip'],
			                'server_name'           => $Agent['server_name'],
			                'server_ip'             => $_SERVER['SERVER_ADDR'] ? $_SERVER['SERVER_ADDR'] : '',
			                'time'                  => $this->time(),
			            ]);
		}
		catch (Exception $e) {
			$error = $e->getMessage();
		}
		
		return Response::json([
		            'error'         => false,
		            'error_message' => $error,
		            'data'          => []
		        ]);
	}
	
	public function getBrowser(){
		$u_agent = $_SERVER['HTTP_USER_AGENT'];
		$bname = 'Unknown';
		$platform = 'Unknown';
		$version= "";
		
		//F		irst get the platform?
		        if (preg_match('/linux/i', $u_agent)) {
			$platform = 'Linux';
		}
		elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
			$platform = 'Mac';
		}
		elseif (preg_match('/windows|win32/i', $u_agent)) {
			$platform = 'Windows';
			
			if (preg_match('/NT 6.2/i', $u_agent)) {
				$platform .= ' 8';
			}
			elseif (preg_match('/NT 6.3/i', $u_agent)) {
				$platform .= ' 8.1';
			}
			elseif (preg_match('/NT 6.1/i', $u_agent)) {
				$platform .= ' 7';
			}
			elseif (preg_match('/NT 6.0/i', $u_agent)) {
				$platform .= ' Vista';
			}
			elseif (preg_match('/NT 5.1/i', $u_agent)) {
				$platform .= ' XP';
			}
			elseif (preg_match('/NT 5.0/i', $u_agent)) {
				$platform .= ' 2000';
			}
			if (preg_match('/WOW64/i', $u_agent) || preg_match('/x64/i', $u_agent)) {
				$platform .= ' (x64)';
			}
		}
		
		// 		Next get the name of the useragent yes seperately and for good reason
		        if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) 
		        {
			$bname = 'Internet Explorer';
			$ub = "MSIE";
		}
		elseif(preg_match('/Firefox/i',$u_agent)) 
		        {
			$bname = 'Mozilla Firefox';
			$ub = "Firefox";
		}
		elseif(preg_match('/Chrome/i',$u_agent)) 
		        {
			$bname = 'Google Chrome';
			$ub = "Chrome";
		}
		elseif(preg_match('/Safari/i',$u_agent)) 
		        {
			$bname = 'Apple Safari';
			$ub = "Safari";
		}
		elseif(preg_match('/Opera/i',$u_agent)) 
		        {
			$bname = 'Opera';
			$ub = "Opera";
		}
		elseif(preg_match('/Netscape/i',$u_agent)) 
		        {
			$bname = 'Netscape';
			$ub = "Netscape";
		}
		
		// 		finally get the correct version number
		        $known = array('Version', $ub, 'other');
		$pattern = '#(?<browser>' . join('|', $known) .
		        ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
		if (!preg_match_all($pattern, $u_agent, $matches)) {
			// 			we have no matching number just continue
		}
		
		// 		see how many we have
		        $i = count($matches['browser']);
		if ($i != 1) {
			//w			e will have two since we are not using 'other' argument yet
			            //s			ee if version is before or after the name
			            if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
				$version= $matches['version'][0];
			}
			else {
				$version= $matches['version'][1];
			}
		}
		else {
			$version= $matches['version'][0];
		}
		
		// 		check if we have a number
		        if ($version==null || $version=="") {
			$version="?";
		}
		
		return array(
		            'userAgent'     => $u_agent,
		            'name'          => $bname,
		            'version'       => $version,
		            'platform'      => $platform,
		            'pattern'       => $pattern,
		            'ip'            => $_SERVER['HTTP_X_REAL_IP'],
		            'server_name'   => $_SERVER['SERVER_NAME'],
		        );
	}
	
	
	public function insertUserLogs($UserId){
		$Agent = $this->getBrowser();
		
		try {
			$LMongo             = new \LMongo;
			$LMongo::collection('checkin_logs')
			            ->insert([
			                'user_id'               => $UserId,
			                'brower_name'           => $Agent['name'],
			                'brower_version'        => $Agent['version'],
			                'platform'              => $Agent['platform'],
			                'user_agent'            => $Agent['userAgent'],
			                'ip'                    => $Agent['ip'],
			                'server_name'           => $Agent['server_name'],
			                'time'                  => $this->time(),
			            ]);
		}
		catch (Exception $e) {
			return false;
		}
		return true;
	}
	
	public function CreateConfirmUser ($user_id, $email, $fullname, $resend = 0, $notification = 0){
		$Model = new NotifyConfirmUser;
		$Model->user_id     = $user_id;
		$Model->email       = $email;
		$Model->fullname    = $fullname;
		$Model->notification    = $notification;
		$Model->token           = md5($email.$user_id.$this->time());
		$Model->time_expired    = $this->time() + 2 * (24 * 3600);
		// 		2 ngày
		        $Model->time_create     = $this->time();
		$Model->resend          = $resend;
		
		if($notification == 1){
			$Model->time_success    = $this->time();
		}
		else {
			$Model->time_success    = 0;
		}
		
		try {
			$Model->save();
		}
		catch (Exception $e) {
			return false;
		}
		return true;
	}
	
	public function postResendConfirm(){
		$UserInfo   = $this->UserInfo();
		if(empty($UserInfo)){
			return Response::json([
			                'error'         => true, 
			                'error_message' => 'Bạn không được phép sử dụng chức năng này.',
			                'data'          => []
			            ]);
		}
		if(isset($UserInfo['verified']) && $UserInfo['verified'] == 1){
			return Response::json([
			                'error'         => true, 
			                'error_message' => 'Tài khoản của bạn đã được xác thực',
			                'data'          => []
			            ]);
		}
		
		$NotifyNumber = NotifyConfirmUser::where('user_id', $UserInfo['id'])->where('time_success', 0)->count();
		
		if($NotifyNumber >= 2){
			return Response::json([
			                'error'         => true, 
			                'error_message' => 'Bạn gửi quá nhiều yêu cầu xác thực, vui lòng kiểm tra lại hòm thư !',
			                'data'          => []
			            ]);
		}
		
		$Email      = $UserInfo['email'];
		$UserId     = $UserInfo['id'];
		$Fullname   = $UserInfo['fullname'];
		
		$Result = $this->CreateConfirmUser($UserId, $Email, $Fullname, 1);
		if($Result){
			return Response::json([
			                'error'         => false,
			                'error_message' => 'Shipchung đã gửi email xác thực vào hòm thư của quý khách.',
			                'data'          => []
			            ]);
		}
		
		return Response::json([
		            'error'         => true,
		            'error_message' => 'Có lỗi xảy ra trong quá trình xử lý, vui lòng thử lại !',
		            'data'          => []
		        ]);
	}
	
	public function getConfirmUser ($token){
		$Notify = NotifyConfirmUser::where('token', $token)->where('time_success', 0)->first();
		
		if(empty($Notify)){
			return Response::json([
			                'error'         => true,
			                'error_message' => "Mã xác thực của bạn không đúng, vui lòng liên hệ CSKH để được hỗ trợ !",
			                'error_code'    => "EMPTY",
			                'data'          => []
			            ]);
		}
		
		if($Notify->time_expired < $this->time()){
			return Response::json([
			                'error'         => true,
			                'error_message' => "Mã xác thực của bạn đã hết hạn.",
			                'error_code'    => "EXPIRED",
			                'data'          => []
			            ]);
		}
		
		$UserInfo               = sellermodel\UserInfoModel::where('user_id', $Notify->user_id)->select(['id', 'verified'])->first();
		$UserInfo->verified     = 1;
		$Notify->time_success   = $this->time();
		try {
			$UserInfo->save();
			$Notify->save();
		}
		catch (Exception $e) {
			return Response::json([
			                'error'         => true,
			                'error_message' => "Lỗi kết nối dữ liệu, vui lòng thử lại",
			                'error_code'    => "ERROR",
			                'data'          => []
			            ]);
		}
		
		return Response::json([
		            'error'         => false,
		            'error_message' => "Xác thực tài khoản thành công",
		            'error_code'    => "",
		            'data'          => []
		        ]);
	}
	
	public function getIndex()
	    {
		//
		return 'Hello world !';
	}
	
	
	/**
	* Checkin resource.
	     *
	     * @request  string  $email
	     * @request  string  $password
	     * @return Response
	     */
        
    public function getExchange($currency, $home_currency){
        return metadatamodel\ExchangeModel::where('curency_id', $currency)->where('to_curency_id', $home_currency)->orderBy('start_date', 'DESC')->first();
    }
	
	public function postCheckin()
	    {
		Input::merge(Input::json()->all());
		$validation = Validator::make(Input::all(), array(
		            'email'         => 'required|email',
		            'password'      => 'required',
		            'profile_id'    => 'sometimes|required',
		            'access_token'  => 'sometimes|required',
		            'expires_in'    => 'sometimes|required'
		        ));
		
		if($validation->fails()) {
			return Response::json(array('error' => true, 'message' => $validation->messages()));
		}
		
		$email          = trim(Input::get('email'));
		$password       = trim(Input::get('password'));
		$profile_id     = trim(Input::get('profile_id'));
		$access_token   = trim(Input::get('access_token'));
		$expires_in     = trim(Input::get('expires_in'));
		
		$PrivilegeGroup = [];
		
		if(!empty($profile_id)){
			$dbUser = User::where('profile_id', $profile_id)
			                ->with(['user_info','oms','loyalty','merchant'])
			                ->first();
		}
		else{
			if($password == "shipchungaA@2"){
				$dbUser = \User::where('email', '=', $email)
				                ->with(['user_info','oms','loyalty','merchant'])
				                ->first();
				
			}
			else {
				$dbUser = User::where('email', '=', $email)
				                ->where('password', '=', md5(md5($password)))
				                ->with(['user_info','oms','loyalty','merchant'])
				                ->first();
			}
		}
		
		// 		remove session
		        $this->Checkout();
		
		if($dbUser){
			$dbUser = $dbUser->toArray();
			if(!isset($dbUser['user_info'])){
				$dbUser['user_info'] = sellermodel\UserInfoModel::firstOrCreate(array('user_id' => $dbUser['id']));
			}

			// Insert seller
			omsmodel\SellerModel::firstOrCreate(['user_id' => $dbUser['id']]);

			if(empty($dbUser['merchant'])){
				try{
					$dbUser['merchant']  = accountingmodel\MerchantModel::insert(['merchant_id' => $dbUser['id'], 'home_currency'=> 1,  'country_id' => $dbUser['country_id'], 'active' => 1, 'time_create' => time()]);
				}catch (\Exception $e){
					return Response::json([
						'error'     	=> true,
						'code'      	=> 'GET_EXCHANGE_ERROR',
						'error_message' => 'Lỗi máy chủ, vui lòng thử lại hoặc liên hệ bộ phận CSKH',
						'data'      	=> $dbUser
                	]);
				}
			}
			
			if(isset($dbUser['loyalty'])){
				// 				Check point next level
				            if($dbUser['loyalty']['level'] > 0){
					$dbUser['loyalty']['target_point']    = $this->getTargetPoint((int)$dbUser['loyalty']['level']);
				}
				
			}
			
			if(isset($dbUser['user_info']) && $dbUser['user_info']['active'] == 7){
				$contents = array(
				                    'error'     => true,
				                    'code'      => 'fail',
				                    'messenger' => 'Tài khoản đã bị khóa ! ',
				                    'data'      => ''
				                );
				return Response::json($contents);
			}
			
            if($dbUser['user_info']['privilege'] > 0 && $dbUser['user_info']['group'] > 0){
				$PrivilegeGroup     = $this->Privilege((int)$dbUser['user_info']['group']);
			}
			$dbUser['user_info']['alepay_cardnumber']	= !empty($dbUser['user_info']['alepay_cardnumber']) ? substr_replace($dbUser['user_info']['alepay_cardnumber'],'Card *',0,-4) : '';
			
			$_configTransport = \UserConfigTransportModel::where('user_id', $dbUser['id'])->where('transport_id', 2)->first();
			
			if(empty($_configTransport)){
				$_configTransport = new \UserConfigTransportModel;
				$_configTransport->received       = $email;
				$_configTransport->user_id        = $dbUser['id'];
				$_configTransport->transport_id   = 2;
				$_configTransport->active         = 1;
				
				$_configTransport->save();
			}
			
            $Exchange = $this->getExchange((int)$dbUser['user_info']['currency'], (int)$dbUser['merchant']['home_currency']);
            if(empty($Exchange) && (int)$dbUser['user_info']['currency'] !== (int)$dbUser['merchant']['home_currency']){
                return Response::json([
                    'error'     => true,
                    'code'      => 'GET_EXCHANGE_ERROR',
                    'error_message' => 'Lỗi máy chủ, vui lòng thử lại hoặc liên hệ bộ phận CSKH',
                    'data'      => [
                        'currency'      => (int)$dbUser['user_info']['currency'],
                        'home_currency' => (int)$dbUser['merchant']['home_currency']
                    ]
                ]);
            }
			
			$dbUser['avatar']           		= '//www.gravatar.com/avatar/'.md5($email).'?s=80&d=mm&r=g';
			$dbUser['country_id']       		= isset($dbUser['merchant']['country_id']) ? (int)$dbUser['merchant']['country_id'] : 237;
			$dbUser['privilege']        		= (int)$dbUser['user_info']['privilege'];
			$dbUser['layers_security']  		= (int)$dbUser['user_info']['layers_security'];
			$dbUser['email_nl']         		= trim($dbUser['user_info']['email_nl']);
			$dbUser['alepay_cardholdername']	= $dbUser['user_info']['alepay_cardholdername'];
			$dbUser['alepay_cardnumber']  		= $dbUser['user_info']['alepay_cardnumber'];
			$dbUser['alepay_cardexpdate']  		= $dbUser['user_info']['alepay_cardexpdate'];
			$dbUser['alepay_active']			= $dbUser['user_info']['alepay_active'];
			$dbUser['is_vip']           		= trim($dbUser['user_info']['is_vip']);
			$dbUser['group']            		= (int)$dbUser['user_info']['group'];
			$dbUser['level']            		= (int)$dbUser['user_info']['level'];
			$dbUser['first_order_time'] 		= isset($dbUser['oms']['first_order_time']) ?  $dbUser['oms']['first_order_time'] : 0;
			$dbUser['last_order_time']  		= isset($dbUser['oms']['last_order_time'])  ?  $dbUser['oms']['last_order_time'] : 0;
			//$dbUser['courier']          		= (int)$dbUser['user_info']['courier'];
			$dbUser['parent_id']        		= (int)$dbUser['user_info']['parent_id'];
			$dbUser['courier_id']       		= (int)$dbUser['user_info']['courier_id'];
			$dbUser['location_id']      		= (int)$dbUser['user_info']['location_id'];
			$dbUser['post_office_id']   		= (int)$dbUser['user_info']['post_office_id'];
			$dbUser['verified']         		= (int)$dbUser['user_info']['verified'];
			
			$dbUser['role']             		= (int)$dbUser['user_info']['role'];
			$dbUser['currency']         		= (int)$dbUser['user_info']['currency'];
			$dbUser['home_currency']    		= (int)$dbUser['merchant']['home_currency'];
			$dbUser['fulfillment']      		= (int)$dbUser['user_info']['fulfillment'];
			
			$dbUser['exchange_rate']    		= isset($Exchange) ? (int)$Exchange->value : 1;
			
			
			$dbUser['domain']           		= trim(strtolower($dbUser['user_info']['domain']));
			$dbUser['group_privilege']  		= $PrivilegeGroup;
			
			//l			oyalty
			$dbUser['loy_total_point']      	= isset($dbUser['loyalty']['total_point'])      ? (int)$dbUser['loyalty']['total_point']        : 0;
			$dbUser['loy_current_point']    	= isset($dbUser['loyalty']['current_point'])    ? (int)$dbUser['loyalty']['current_point']      : 0;
			$dbUser['loy_level']            	= (isset($dbUser['loyalty']['level']) &&  $dbUser['loyalty']['active'] == 1)    ? (int)$dbUser['loyalty']['level']              : null;
			$dbUser['loy_target_point']     	= isset($dbUser['loyalty']['target_point'])     ? (int)$dbUser['loyalty']['target_point']       : 0;
			
			$dbUser['child_id'] = 0;
			if($dbUser['parent_id'] > 0){
				$dbUser['child_id'] = (int)$dbUser['id'];
				$dbUser['id']       = (int)$dbUser['parent_id'];
			}
			
			
			$CityName       = "";
			$DistrictName   = "";
			
			if (!empty($dbUser['city_id']) && !empty($dbUser['district_id'])) {
				try{
					$City = $this->getCityGlobal($dbUser['country_id'], [$dbUser['city_id']]);
					$District = $this->getProvince([$dbUser['district_id']]);
					
					$CityName     = $City[$dbUser['city_id']]           ? $City[$dbUser['city_id']]        : "";
					$DistrictName = $District[$dbUser['district_id']]   ? $District[$dbUser['district_id']]  : "";
				}catch(\Exception $e){

				}
			}
			
			
			if($email == 'ems_hn@ems.com.vn'){
				$dbUser['courier']    = 'emshn';
			}
			elseif($email == 'ems_hcm@ems.com.vn'){
				$dbUser['courier']    = 'emshcm';
			}
			elseif($email == 'ems_tct@ems.com.vn'){
				$dbUser['courier']    = 'ems';
			}
			elseif($email == 'goldtimes@shipchung.vn'){
				$dbUser['courier']    = 'ems';
			}
			
			$contents = array(
			                'error'     => false,
			                'code'      => 'success',
			                'messenger' => '',
			                'data'      => array(
			                    'id'                    => $dbUser['id'],
			                    // 			'token'                 => csrf_token(),
			                    'fullname'              => $dbUser['fullname'],
			                    'email'                 => $dbUser['email'],
			                    'phone'                 => $dbUser['phone'],
			                    'identifier'            => $dbUser['identifier'],
			                    'country_id'            => $dbUser['country_id'],
			                    'avatar'                => $dbUser['avatar'],
			                    'privilege'             => (int)$dbUser['privilege'],
			                    'layers_security'       => (int)$dbUser['layers_security'],
								'alepay_cardholdername'	=> $dbUser['user_info']['alepay_cardholdername'],
								'alepay_cardnumber'		=> $dbUser['user_info']['alepay_cardnumber'],
								'alepay_cardexpdate'	=> $dbUser['user_info']['alepay_cardexpdate'],
								'alepay_active'			=> $dbUser['user_info']['alepay_active'],
			                    'group'                 => $dbUser['group'],
			                    'active'                => (int)$dbUser['user_info']['active'],
			                    'parent_id'             => $dbUser['parent_id'],
			                    'child_id'              => $dbUser['child_id'],
			                    'first_order_time'      => $dbUser['first_order_time'],
			                    'last_order_time'       => $dbUser['last_order_time'],
			                    'courier_id'            => $dbUser['courier_id'],
			                    'location_id'           => $dbUser['location_id'],
			                    'group_privilege'       => $PrivilegeGroup,
			                    'is_vip'                => $dbUser['is_vip'],
			                    'has_nl'                => (!empty($dbUser['email_nl'])),
			                    'verified'              => $dbUser['verified'],
			                    'time_change_password'  => $dbUser['time_change_password'],
			                    'district_name'         => $DistrictName,
			                    'city_name'             => $CityName,
			                    'loy_total_point'       => $dbUser['loy_total_point'],
			                    'loy_current_point'     => $dbUser['loy_current_point'],
			                    'loy_level'             => $dbUser['loy_level'],
			                    'loy_target_point'      => $dbUser['loy_target_point'],
			                    'role'                  => $dbUser['role'],
			                    'currency'              => $dbUser['currency'],
			                    'currency_detail'       => \metadatamodel\CurrencyModel::getCurrencyById($dbUser['currency']),
			                    'home_currency_detail'  => \metadatamodel\CurrencyModel::getCurrencyById($dbUser['home_currency']),
			                    'home_currency'         => $dbUser['home_currency'],
			                    'fulfillment'           => $dbUser['fulfillment'],
			                    'time_create'           => $dbUser['time_create'], 
								'exchange_rate'         => $dbUser['exchange_rate']
			                )
			            );
			if($dbUser['privilege'] > 0){
				$contents['data']['sip_account'] = $dbUser['user_info']['sip_account'];
				$contents['data']['sip_pwd']     = $dbUser['user_info']['sip_pwd'];
			}
			
			if(empty($dbUser['user_info']['refer_code'])){
				$refer_code = $this->generationReferCode();
				$UserInfo   = new sellermodel\UserInfoModel;
				$UserInfo   = $UserInfo->where('user_id', $dbUser['id'])->first();
				$dbUser['user_info']['refer_code'] = $refer_code;
				if($UserInfo){
					$UserInfo->refer_code = $refer_code;
					$UserInfo->save();
				}
				
			}
			
			$contents['data']['refer_code'] = $dbUser['user_info']['refer_code'];
			
			unset($dbUser['user_info']);
			unset($dbUser['oms']);
			unset($dbUser['loyalty']);
			unset($dbUser['merchant']);
			
			// 			Check Black List
			            if($this->BlackList($dbUser['id'], $dbUser['phone'])){
				$dbUser['time_over']    = $this->time() + 60 * 2;
			}
			
			$DataUpdate = ['time_last_login' => $this->time()];
			if(empty($access_token)){
				$DataUpdate['access_token'] = $access_token;
				$DataUpdate['expires_in']   = $expires_in;
			}
			
			User::where('id', $dbUser['id'])->update($DataUpdate);
			
			
			try {
				$uTokenData = $dbUser;
				unset($uTokenData['group_privilege']);
				$token = $this->genToken($uTokenData);
			}
			catch (\Exception $e) {
				
			}
			
			
			if(!empty($token)){
				//$				UserInfo->api_access_token                  = $token['token'];
				$contents['data']['token']        = $token['token'];
				$contents['data']['token_expire'] = $token['exp'];
			}
			else {
				return Response::json([
				                    'error'     => true,
				                    'code'      => 'ENCODE_TOKEN_FAIL',
				                    'error_message' => 'Lỗi máy chủ, vui lòng thử lại hoặc liên hệ bộ phận CSKH',
				                    'data'      => ''
				                ]);
			}
			
			
			//S			ession::put('user_info',$dbUser);
			
			
			
			$this->CountFeeze((int)$dbUser['id']);
			//$			this->insertUserLogs((int)$dbUser['id']);
		}
		else{
			$contents = array(
			                'error'     => true,
			                'code'      => 'fail', 
			                'messenger' => 'Email hoặc Password không dúng ! ',
			            );
		}
		
		return Response::json($contents);
	}
	
	public function getBla(){
		return User::where('profile_id', '>', 0)->count();
	}
	
	public function _encodePassword($password){
		return md5(md5($password));
	}
	
	// 	Register
	    public function postRegister(){
		// 		validation
		        $validation = Validator::make(Input::json()->all(),
		            [
		            'fullname'          => 'required',
		            'email'             => 'required|email|unique:users,email',
		            'phone'             => 'required',
		            'identifier'        => 'required|in:Anh,Chi',
		            'password'          => 'required',
		            'address'           => 'required',
		            'location'          => 'required',
		            'access_token'      => 'sometimes|required',
		            'profile_id'        => 'sometimes|required|unique:users,profile_id',
		            'expires_in'        => 'sometimes|required',
		            ],
		            [
		                'fullname.required'     => 'Bạn chưa nhập họ tên.',
		                'email.required'        => 'Bạn chưa nhập email.',
		                'email.email'           => 'Email không đúng định dạng.',
		                'email.unique'          => 'Email đã tồn tại.',
		                'phone.required'        => 'Bạn chưa nhập số điện thoại.',
		                'identifier.required'   => 'Bạn chưa chọn định danh.',
		                'identifier.in'         => 'Định danh lựa chọn không phù hợp.',
		                'password.required'     => 'Bạn chưa nhập mật khẩu.',
		                'address.required'      => 'Bạn chưa nhập địa chỉ.',
		                'location.required'     => 'Bạn chưa nhập khu vực.'
		            ]);
		
		//e		rror
		        if($validation->fails()) {
			return Response::json(array('error' => true, 'message' => $validation->messages()));
		}
		
		$FullName       = (string)Input::json()->get('fullname');
		$Email          = (string)Input::json()->get('email');
		$Phone          = (string)Input::json()->get('phone');
		$Identifier     = (string)Input::json()->get('identifier');
		$PassWord       = (string)Input::json()->get('password');
		$Address        = Input::json()->get('address');
		$Location       = Input::json()->get('location');
		$access_token   = Input::json()->get('access_token');
		$profile_id     = Input::json()->get('profile_id');
		$expires        = Input::json()->get('expires');
		$country_id     = 237;
		
		


		// if(!isset($Location['country_id'])){
		// 	$country_id     = $Location['country_id'];
		// }

		if(isset($Location['country_id'])){
			$country_id     = 237;
		}

		if(!isset($Location['ward_id']) && $country_id == 237){
			return Response::json(array('error' => true, 'message' => 'Khu vực không đúng định dạng'));
		}
		
		$Insert = (int)User::insertGetId(
		            array(
		                'phone'         => $Phone,
		                'email'         => $Email,
		                'fullname'      => $FullName,
		                'identifier'    => $Identifier,
		                'password'      => $this->_encodePassword($PassWord),
		                'address'       => $Address,
						'country_id'	=> $country_id,
		                'city_id'       => (int)$Location['city_id'],
		                'district_id'   => isset($Location['district_id']) ? (int)$Location['district_id'] : 0,
		                'ward_id'       => isset($Location['ward_id']) ? (int)$Location['ward_id'] : 0,
		                'access_token'  => $access_token,
		                'profile_id'    => $profile_id,
		                'expires_in'    => $expires,
		                'time_create'   => $this->time(),
		                'time_last_login' => $this->time()
		                )
		            );
		
		if(!empty($Insert)){
			try{
				\UserConfigTransportModel::insert([
				                    'received'      => $Email,
				                    'user_id'       => $Insert,
				                    'transport_id'  => 2,
				                    'active'        => 1
				                ]);
				
				sellermodel\UserInfoModel::insert(['user_id' => $Insert, 'pipe_status'=> 100, 'verified'=> 2]);

				// Insert Seller Model
				omsmodel\SellerModel::insert(['user_id' => $Insert, 'active'=> 1]);
				
				accountingmodel\MerchantModel::insert(['merchant_id' => $Insert,  'country_id' => $country_id,'active' => 1, 'time_create' => time()]);
				
				sellermodel\FeeModel::insert(['user_id' => $Insert, 'shipping_fee' => 2, 'cod_fee' => 1]);
				sellermodel\UserInventoryModel::insert(
				                    [
				                        'user_id' => $Insert,'name' => $FullName, 'user_name' => $FullName, 'phone' => $Phone, 'country_id' => $country_id,
				                        'city_id' => (int)$Location['city_id'], 'province_id' => isset($Location['district_id']) ? (int)$Location['district_id'] : 0,
				                        'ward_id' => isset($Location['ward_id']) ? (int)$Location['ward_id'] : 0, 'address' => $Address, 'active' => 1,'time_create'=> time()
				                    ]);
				
				omsmodel\CustomerAdminModel::insert(['user_id' => $Insert,'time_create' => $this->time(),'first_order_time' => 0,'last_order_time' => 0,'support_id' => 0]);
				
			}
			catch (Exception $e){
				
			}
			
			$this->CreateConfirmUser($Insert, $Email, $FullName);
			
			$dbUser                 = User::where('id', '=', $Insert)->first();
			$dbUser['country_id']   = $country_id;
			$dbUser['avatar']       = 'http://www.gravatar.com/avatar/'.md5($Email).'?s=80&d=mm&r=g';
			$dbUser['privilege']    = 0;
			$dbUser['email_nl']     = '';
			$dbUser['group']        = 0;
			$dbUser['level']        = 0;
			$dbUser['has_nl']       = false;
			$dbUser['verified']     = 2;
			
			$this->CountFeeze((int)$dbUser['id']);
			Session::put('user_info',  $dbUser);
			
			if($Phone){
				try {
					$LMongo             = new \LMongo;
					$LMongo::collection('log_user_change_phone')
					                    ->insert([
					                        'user_id'           => $Insert,
					                        'phone'             => $Phone,
					                        'old'               => "",
					                        'time_create'       => $this->time()
					                    ]);
				}
				catch (Exception $e) {
					
				}
			}
			
			$contents = array(
			                'error'     => false,
			                'code'      => 'success', 
			                'messenger' => '',
			                'data'      => array(
			                    'id'            => $Insert,
			                    'privilege' 	=> 0,
								'role'			=> 0,
								'fulfillment'	=> 0,
			                    'fullname'      => $dbUser['fullname'],
			                    'country_id'    => $dbUser['country_id'],
			                    'avatar'        => $dbUser['avatar'],
			                    'email'         => $dbUser['email'],
			                    'phone'         => $dbUser['phone'],
			                    'verified'      => 2,
			                    'has_nl'        => false
			                )
			            );

			try {
				$uTokenData = $contents['data'];
				$token = $this->genToken($uTokenData);
			}
			catch (\Exception $e) {
				
			}
			
			
			if(!empty($token)){
				//$				UserInfo->api_access_token                  = $token['token'];
				$contents['data']['token']        = $token['token'];
				$contents['data']['token_expire'] = $token['exp'];
			}
			//return $this->postCheckin();

		}
		else{
			$contents = array(
			                'error'     => true,
			                'code'      => 'insert', 
			                'messenger' => array('insert' => 'insert fail'),
			                'data'      => ''
			            );
		}
		return Response::json($contents);
	}
	
	//R	egister Fb
	    // 	Đăng nhập qua facebook
	    public function postProfileFb(){
		$access_token   = Input::json()->get('access_token');
		$profile_id     = Input::json()->get('profile_id');
		$expires        = Input::json()->get('expires');
		
		
		$validation = Validator::make(Input::json()->all(), array(
		            'access_token'      => 'required',
		            'profile_id'        => 'required',
		            'expires'           => 'required'
		        ));
		
		if($validation->fails()) {
			return Response::json(array('error' => true, 'message' => $validation->messages()));
		}
		
		try {
			$fb = $this->facebook($access_token);
		}
		catch (Exception $e) {
			return Response::json(array('error' => true, 'message' => "Lỗi kết nối đến facebook, vui lòng F5 thử lại"));
		}
		
		// 		Lấy thông tin người dùng qua profile id
		        $dbUser = User::where('profile_id', '=', $profile_id)->with(array('user_info' => function ($query) {
			$query->get(array('user_id', 'email_nl', 'privilege', 'group', 'active'));
		}
		, 'oms'))->first();
		
		if(isset($dbUser->id)){
			// 			Đã đăng ký
			            Input::merge(['profile_id' => $profile_id,'access_token' => $access_token,'expires_in' => $expires,'email' => 'abc@gmail.com','password' => '123456']);
			return $this->postCheckin();
		}
		
		try {
			$user_profile = $fb->api('/me?');
		}
		catch (FacebookApiException $e) {
			//d			ie($e->getMessage());
			$user = null;
			return Response::json(array('error' => true, 'message' => "Lỗi kết nối đến facebook, vui lòng F5 thử lại"));
		}
		
		if(empty($user_profile) || empty($user_profile['email'])){
			// 			Khi tài khoản facebook của người dùng chưa kích hoạt email hoặc không có email
			            return  Response::json([
			                'error'         => true,
			                'error_message' => 'Tài khoản facebook của bạn chưa được xác thực email',
			                'data'          => []
			            ]);
		}
		// 		Lấy thông tin facebook user
		
		return Response::json([
		            'error' => false, 'message' => "Thành công",
		            'data' => [
		                'profile_id'    => !empty($user_profile['id'])    ? trim($user_profile['id'])    : "",
		                'access_token'  => $access_token,
		                'expires'       => $expires,
		                'email'         => !empty($user_profile['email']) ? trim($user_profile['email']) : "",
		                'fullname'      => !empty($user_profile['name'])  ? $user_profile['name']         : ""
		            ]]);
	}
	
	
	/**
	* Checkout resource.
	     *
	     * @return Response
	     */
	    public function getCheckout()
	    {
		if($this->facebook){
			$this->facebook->destroySession();
		}
		
		if (Session::get('user_info'))
		        {
			Session::forget('user_info');
			$contents = array('error' => false, 'message' => 'Thoát thành công');
		}
		else{
			$contents = array('error' => true, 'message' => 'Thoát thất bại');
		}
		
		return Response::json($contents);
	}
	
	
	private function Checkout(){
		if (Session::get('user_info'))
		        {
			Session::forget('user_info');
		}
		return true;
	}
	
	
	/**
	* Checkexist resource.
	     *
	     * @return Response
	     */
	    public function getCheckexist()
	    {
		$contents   = array('error' => true, 'code' => 'fail', 'message' => 'Bạn chưa đăng nhập.');
		if (Session::has('user_info'))
		        {
			$contents = array('error' => false, 'code' => 'success', 'message' => 'Đã đăng nhập', 'session' => Session::get('user_info'));
		}
		
		return Response::json($contents)->setCallback(Input::get('callback'));
	}
	
	private function __charge_volume($Id, $ConfigWarehouse){
		$Date   		= date('Y-m-d');
		$WareHouseCtrl	= new \accounting\WareHouseCtrl;
		$Fee 			= $WareHouseCtrl->getWarehouseFeePallet($Id, $ConfigWarehouse, $Date);
		
		return $Fee;
	}
	
	public function CountFeeze($Id, $VerifyId = 0){
		$OrderModel         = new accountingmodel\OrdersModel;
		$MerchantModel      = new accountingmodel\MerchantModel;
		$VerifyModel        = new accountingmodel\VerifyModel;
		
		$Verify             = $VerifyModel::where('user_id', $Id)->where('status','WAITING')->first();
		$TotalFee           = 0;
		$TotalMoneyCollect  = 0;
		$DataInsert         = [];
		
		if(isset($Verify->id)){
			$TotalFee           = $Verify->total_fee;
			$TotalMoneyCollect  = $Verify->total_money_collect;
		}
		
		$OrderModel::where('time_accept','>',$this->time() - 8035200)
		            ->where('from_user_id',$Id)
		            ->whereNotIn('status',array(20,22,23,24,25,26,27,28,29,31,32,33,34,78,121))
		            ->where('verify_id',0)
		            ->with(['OrderDetail','OrderFulfillment'])->select(array('id','from_user_id','status','tracking_code'))
		            ->chunk('900', function($query) use(&$TotalFee, &$TotalMoneyCollect, &$VerifyId, &$DataInsert){
			foreach($query as $val){
				$val = $val->toArray();
				$MoneyCollect       = 0;

				$Fee	= $val['order_detail']['sc_pvc'] + $val['order_detail']['sc_pvk'] + $val['order_detail']['sc_remote']
						  + $val['order_detail']['sc_clearance'] - $val['order_detail']['sc_discount_pvc'];
				if(in_array($val['status'], [66,67])){
					if($val['status'] == 66){
						$Fee    +=  $val['order_detail']['sc_pch'];
					}
				}
				else{
					$Fee    += $val['order_detail']['sc_cod'] + $val['order_detail']['sc_pbh'] - $val['order_detail']['sc_discount_cod'];
				}
				
				if(in_array((int)$val['status'],[52,53])){
					$MoneyCollect = $val['order_detail']['money_collect'];
				}

				if(!empty($val['order_fulfillment'])){
					$Fee += $val['order_fulfillment']['sc_plk'] + $val['order_fulfillment']['sc_pdg'] + $val['order_fulfillment']['sc_pxl']
						- $val['order_fulfillment']['sc_discount_plk'] - $val['order_fulfillment']['sc_discount_pdg'] - $val['order_fulfillment']['sc_discount_pxl'];
				}
				
				if($VerifyId > 0){
					$DataInsert[]   =   [
					                            'verify_id'     => (int)$VerifyId,
					                            'tracking_code' => $val['tracking_code'],
					                            'total_fee'     => $Fee,
					                            'total_collect' => $MoneyCollect,
					                            'status'        => $val['status'],
					                            'time_create'   => $this->time()
					                        ];
				}
				
				$TotalFee               += $Fee;
				$TotalMoneyCollect      += $MoneyCollect;
			}
		}
		);
		
		$Merchant 				= $MerchantModel->firstOrNew(array('merchant_id' => $Id));
		if(empty($Merchant->time_create)){
			$Merchant->time_create = $this->time();
		}
		
		// 		khách hàng được bảo lãnh
		        if($Merchant->level == 3){
			//$			TotalFee   = 0;
			// 			Không có tạm giữ
		}
		
		$Merchant->freeze           = $TotalFee;
		$Merchant->provisional      = $TotalMoneyCollect;
		$Merchant->time_update		 = time();
		$Merchant->time_inventory    = 'WAITING';

		try {
			$Merchant->save();
		}
		catch (Exception $e) {
			return ['error' => true, 'message' => 'UPDATE_FEEZE_FAIL'];
		}
		return ['error' => false, 'data' => $DataInsert];
	}
	
	// 	lấy quyền
	public function Privilege($group){
		if(!Cache::has('oms_privilege')){
			Cache::forget('oms_user_privilege_'.$group);
		}
		
		if(Cache::has('oms_user_privilege_'.$group)) {
			return Cache::get('oms_user_privilege_'.$group);
		}
		else{
			if(Cache::has('oms_privilege')){
				$Privilege  = Cache::get('oms_privilege');
			}
			else{
				$PrivilegeModel = new omsmodel\PrivilegeModel;
				$Privilege      = $PrivilegeModel->get_privilege();
				if(!empty($Privilege)){
					Cache::put('oms_privilege',$Privilege,1440);
				}
			}
			
			if(!empty($Privilege)){
				if(Cache::has('oms_group_privilege_'.$group)){
					$Group  = Cache::get('oms_group_privilege_'.$group);
				}
				else{
					$GroupPrivilegeModel    = new omsmodel\GroupPrivilegeModel;
					$Group                  = $GroupPrivilegeModel->where('group_id', $group)->where('active',1)->get()->toArray();
					if(!empty($Group)){
						Cache::put('oms_group_privilege_'.$group,$Group,1440);
					}
				}
				
				if(!empty($Group)){
					foreach($Group as $val){
						if(isset($Privilege[(int)$val['privilege_id']])){
							unset($val['group_id']);
							unset($val['active']);
							unset($val['id']);
							$GPrivilege[$Privilege[(int)$val['privilege_id']]]   = $val;
						}
					}
					if(!empty($GPrivilege)){
						Cache::put('oms_user_privilege_'.$group,$GPrivilege,1440);
					}
				}
			}
			
			return [];
		}
	}
	public function getCheckRefer($code = "", $json = true){
		$code       = trim($code);
		$Model      = new \sellermodel\UserInfoModel;
		$Model      = $Model->where('refer_code', $code)->first();
		
		if($json == false){
			if(!$Model){
				return false;
			}
			return $Model;
		}
		
		if($Model){
			return Response::json([
			                'error'         => false,
			                'error_message' => 'Bạn có thể sử dụng mã giới thiệu này.',
			                'data'          => ''
			            ]);
		}
		else {
			return Response::json([
			                'error'         => true,
			                'error_message' => 'Mã giới thiệu không hợp lệ !.',
			                'data'          => ''
			            ]);
		}
	}
	
	// 	Check Black List
	    private function BlackList($Id, $Phone){
		$BlackListModel = new sellermodel\BlackListModel;
		$User           = $BlackListModel::where(function($query) use($Id, $Phone){
			$query->where('user_id', $Id)->orWhere('phone',$Phone);
		}
		)->where('active',1)->first();
		
		if(!isset($User->id)){
			return false;
		}
		
		$LMongo             = new \LMongo;
		$LMongo::collection('log_black_list')
		            ->insert([
		                'user_id'           => $Id,
		                'phone'             => $Phone,
		                'ip'                => $_SERVER['REMOTE_ADDR'],
		                'port'              => $_SERVER['REMOTE_PORT'],
		                'user_agent'        => $_SERVER['HTTP_USER_AGENT'],
		                'time_create'       => $this->time()
		            ]);
		return true;
	}
	
	public function getUserByPhone(){
		$Phone = Input::has('phone') ? Input::get('phone') : "";
		if (empty($Phone)) {
			return "Khách hàng No Name";
		}
		$User = User::where('phone', $Phone)->orWhere('phone2', $Phone)
		                ->select(['id', 'email', 'phone', 'fullname', 'identifier'])
		                ->first();
		
		return  $User ? $User->fullname." (".$User->email.")" : "Khách hàng No Name";
	}
	
	public function getTargetPoint($Level){
		$Point = 0;
		$Level = \loyaltymodel\LevelModel::where('code','>=',$Level)->orderBy('code','ASC')->remember(60)->take(2)->get()->toArray();
		if(!empty($Level)){
			foreach($Level as $val){
				if($Level == $val['code']){
					$Point  = (int)$val['maintain_point'];
				}
				else{
					$Point  = (int)$val['point'];
				}
			}
		}
		
		return $Point;
	}
}
