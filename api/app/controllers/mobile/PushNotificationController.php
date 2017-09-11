<?php
namespace mobile;
use DB;
use Input;
use Response;
use CourierPostOfficeModel;
use Excel;
use metadatamodel\GroupOrderStatusModel;

class PushNotificationController extends \BaseCtrl {
  
  private $ios_passphrase  = "";
  //p rivate $ios_cert_local  = "/certificates/scdev_ios.pem";
  private $ios_cert_local  = "/certificates/pushcert.pem";
  
  
  private $android_api_key = "" ;
  
  
  private function getContextIos (){
	$ctx = stream_context_create();
	stream_context_set_option($ctx, 'ssl', 'local_cert', app_path().$this->ios_cert_local);
	stream_context_set_option($ctx, 'ssl', 'passphrase', $this->ios_passphrase );
	$fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
	//    production
		//$   fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
	//    developement
		if(!$fp){
	  return false;
	}
	return $fp;
  }
  
  
  /*
  * Data đơn cần xử lý : ['tracking_code'=> 'SC51234823799', 'type'=> 'order_process', 'action'=> 'overweight'] //  action: overweight, pickup, delivery
	* Data gửi thông báo : ['url' => 'https://www.shipchung.vn/shipchung-thong-bao-ap-dung-bang-gia-moi-tu-1122015/', 'type'=> 'news']
	* Message : 'Đơn hàng SC51234823799 lấy hàng thất bại lý do không có hàng lấy !'
  */
  
  public function PushIos ($deviceToken, $message, $data){
	$context    = $this->getContextIos();
	if(!$context){
	  return false;
	}
	
	$body['aps'] = array_merge(array('alert' => $message, 'sound' => 'default'), $data);
	$payload   = json_encode($body);
	$msg     = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
	$result    = fwrite($context, $msg, strlen($msg));
	var_dump($body);
	
	fclose($context);
	
	if (!$result){
	  return false;
	}
	
	return true;
	
  }
  
  public function PushAndroid($device_token = NULL, $objJson, $collapse_key = NULL) {
	if (!$device_token) {
	  return FALSE;
	}
	
	//    prep the bundle
	$msg['message']   = $objJson;
	$msg['vibrate']   = 1;
	$msg['sound']     = 1;
	
	$fields = array(
				'registration_ids' => $device_token,
				'data' => $msg
			);
	var_dump($fields);
	if($collapse_key){
	  $fields['collapse_key'] = $collapse_key;
	}
	
	//$   api_key = "AIzaSyC4lCKI4015hhkgmFvxtJCD1cpuw_rk4lc";
	$api_key = "AIzaSyDe7GmJG80WcnEwTkKoEGZ9mcOl31jh22s";
	//$api_key = "AAAAThQN8x8:APA91bHEXUivnR0o53UYGDjJLNCLqxeNxTq2zO3URrj6vVDI0jMhgZG80TCPpbFuQ_05vLRu-OMcgS98NSiFrv_a86rstd9eVXul28UsjONwCuIUsbvjaad5kdsmgWSH21QPXrLhAAFHT_Zl2jquaFxkcMoxjC_dhg";
	$headers = array(
				'Authorization: key=' . $api_key,
				'Content-Type: application/json'
			);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/send');
	//curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	//    Disabling SSL Certificate support temporarly
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
	$result = curl_exec($ch);
	curl_close($ch);
	
	var_dump($result);
	return !$result ? FALSE : TRUE;
	
  }

  //pushandroidnew
    public function PushAndroidNew($device_token = NULL, $objJson, $collapse_key = NULL) {
	if (!$device_token) {
	  return FALSE;
	}
	
	//    prep the bundle
	$msg['message']   = $objJson;
	$msg['vibrate']   = 1;
	$msg['sound']     = 1;
	
	$fields = array(
				'registration_ids' => $device_token,
				'data' => $msg
			);
	var_dump($fields);
	if($collapse_key){
	  $fields['collapse_key'] = $collapse_key;
	}
	
	//$   api_key = "AIzaSyC4lCKI4015hhkgmFvxtJCD1cpuw_rk4lc";
	$api_key = "AAAAThQN8x8:APA91bHEXUivnR0o53UYGDjJLNCLqxeNxTq2zO3URrj6vVDI0jMhgZG80TCPpbFuQ_05vLRu-OMcgS98NSiFrv_a86rstd9eVXul28UsjONwCuIUsbvjaad5kdsmgWSH21QPXrLhAAFHT_Zl2jquaFxkcMoxjC_dhg";
	$headers = array(
				'Authorization: key=' . $api_key,
				'Content-Type: application/json'
			);
	
	$ch = curl_init();
	//curl_setopt($ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/send');
	curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	//    Disabling SSL Certificate support temporarly
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
	$result = curl_exec($ch);
	curl_close($ch);
	
	var_dump($result);
	return !$result ? FALSE : TRUE;
	
  }
  
  public function getPushNews(){
	$token = Input::get("token");
	return $this->PushIos($token, 'ShipChung thông báo áp dụng bảng giá mới từ 1/12/2015', ['url'=>'https://www.shipchung.vn/shipchung-thong-bao-ap-dung-bang-gia-moi-tu-1122015/', 'type'=> 'news']) ? "true" : "false";
  }
  
  public function getPush(){
	$token = Input::get("token");
	$action = Input::get("action");
	$tracking_code = Input::get("tracking_code");
	return Response::json([$this->PushIos($token, 'Đơn hàng SC51234823799 lấy hàng thất bại lý do không có hàng lấy !', ['tracking_code'=> $tracking_code, 'type'=> 'order_process', 'action' => $action])]);
  }
  
  public function getPushtt(){
	$token = Input::get("token");
	$action = Input::get("action");
	$tracking_code = Input::get("tracking_code");
	return Response::json([$this->PushIos($token, 'Nạp tiền thành công, số tiền 2.000.000đ', [])]);
	
  }
  
  public function getPushAndroid($token){
	$obj = [
			'url' => 'https://www.shipchung.vn/shipchung-thong-bao-ap-dung-bang-gia-moi-tu-1122015/',
			'type'=> 'news',
			'message' => "ShipChung thông báo áp dụng bảng giá mới từ 1/12/2015"
		  ];
	return Response::json([$this->PushAndroid([$token], json_encode($obj))]);
  }
  
  public function getPushAndroid2($token){
	$obj = [
			'tracking_code' => 'SC51234823799',
			'type'          => 'order_process',
			'action'        => 'overweight',
			'message'       => "Đơn hàng SC51234823799 lấy hàng thất bại lý do không có hàng lấy !"
		  ];
	return Response::json([$this->PushAndroid([$token], json_encode($obj))]);
  }
  	
  	//get user notice app
	private function getUserapp($id){
	    if($id > 0){
	        $deviceToken 	 = \sellermodel\UserInfoModel::where('user_id',$id)->first();
	        $configTransport = \UserConfigTransportModel::where('user_id',$id)->where('transport_id',5)->first();

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

	private function getListUserapp($ids){
	    if(!empty($ids)){
	        $ListUser 	 = \sellermodel\UserInfoModel::whereIn('user_id', $ids)->get()->toArray();
	        
	        if(!empty($ListUser)){
	            return $ListUser;
	        }else{
	            return false;
	        }
	    }else{
	        return false;
	    }
	}


	public function getFuck2(){

		$UserId  = Input::has('user_id') ? Input::get('user_id') : 0;
		$OrderId = Input::has('order_id') ? Input::get('order_id') : 0;

		if (empty($OrderId) || empty($UserId)) {
			return Response::json([
				'error'			=> true,
				'error_message'	=> 'order_id must not empty'
			]);
		}

		$Model  = new \ElasticBuilder('bxm_orders', 'orders');
		$Order  = $Model->where('from_user_id', $UserId)->where('post_office_id', 'gt', 0)->get();

		if (empty($Order)) {
			return Response::json([
				'error'			=> true,
				'error_message'	=> 'eo the tim thay dc van don'
			]);
		}

		/*if (count($Order) > 1) {
			return Response::json([
				'error'			=> true,
				'error_message'	=> 'Khong phai van don dau tien'
			]);
		}*/

		$PostOfficeId = $Order[0]['post_office_id'];

		$PostOfficeModel = \CourierPostOfficeModel::where('id', 768)->first();

		if (empty($Order)) {
			return Response::json([
				'error'			=> true,
				'error_message'	=> 'Khong tim thay post office'
			]);
		}

		$Template = \TemplateModel::where('id',47)->first();

        $Device = $this->getUserapp($UserId);

        if(!empty($Device)){

            if($Device['android_device_token'] != ''){
                $dataNoticeApp = array(
                    'os_device' => 'android',
                    'transport_id' => 5,
                    'scenario_id' => 40,
                    'template_id' => 47,
                    'user_id'  => $UserId,
                    'received' => $UserId,
                    'data' => json_encode(
                        array(
                            'device_token' => $Device['android_device_token'],
                            'message' 	   => $Template['title'],
                            'data' => array(
                                'content' 	=> $Template['content'],
                                'type' 			=> 'send_at_postoffice',
								'message' 		=> $Template['title'],
								'bc_name' 		=> $PostOfficeModel['name'],
								'bc_address' 	=> $PostOfficeModel['address'],
								'courier_id'	=> $PostOfficeModel['courier_id'],
								'lat' 			=> $PostOfficeModel['lat'],
								'lng' 			=> $PostOfficeModel['lng'],
                            )
                        )
                    ),
                    'time_create' => time(),
                    'status' => 1,
                    'in_app' => 1
                );

            }elseif($Device['ios_device_token'] != ''){
            	$dataNoticeApp = array(
                    'os_device' => 'ios',
                    'transport_id' => 5,
                    'scenario_id' => 40,
                    'template_id' => 47,
                    'user_id' => $UserId,
                    'received' => $UserId,
                    'data' => json_encode(
                        array(
                            'device_token' => $Device['ios_device_token'], //$Device['ios_device_token']
                            'message' 	   => $Template['title'],
                            'data' => array(
                                'content' 		=> $Template['content'],
                                'type' 			=> 'send_at_postoffice',
								'message' 		=> $Template['title'],
								'bc_name' 		=> $PostOfficeModel['name'],
								'bc_address' 	=> $PostOfficeModel['address'],
								'courier_id'	=> $PostOfficeModel['courier_id'],
								'lat' 			=> $PostOfficeModel['lat'],
								'lng' 			=> $PostOfficeModel['lng'],
                            )
                        )
                    ),
                    'time_create' => time(),
                    'status' => 1,
                    'in_app' => 1
                );

            }

        }

        if (!empty($dataNoticeApp)) {

        	try {
        		$Insert = \QueueModel::insert($dataNoticeApp);
        	} catch (Exception $e) {
        		
        	}
        	
        }

		return Response::json($dataNoticeApp);
	}  

	public function getFuck3(){

		$OrderModel = new \ordermodel\OrdersModel;
		$Data 		= $OrderModel->where('time_accept', 0)->where('time_create', '>=',time() - (7 * 86400))->select(DB::raw('from_user_id, count(from_user_id) as total'))->groupBy('from_user_id')->get();

		$ListUserId = [];
		$_temp 		= [];

		
		foreach ($Data as $key => $value) {
			$ListUserId[] = $value['from_user_id'];
			$_temp[$value['from_user_id']] = $value['total'];
		}

		if (empty($ListUserId)) {
			return Response::json([
				'error'=> true,
				'error_message'=> 'Em po ty list user id'
			]);
		}


		$ListDevice 	= $this->getListUserapp($ListUserId);
		$dataNoticeApp 	= [];

		$Template = \TemplateModel::where('id',51)->first();

		foreach ($ListDevice as $key => $user) {

			if($user['android_device_token'] != ''){

                $dataNoticeApp[] = array(
                    'os_device' 	=> 'android',
                    'transport_id' 	=> 5,
                    'scenario_id' 	=> 45,
                    'template_id' 	=> 51,
                    'user_id'  	=> $user['user_id'],
                    'received' 	=> $user['user_id'],
                    'data' 		=> json_encode(
                        array(
                            'device_token' => $user['android_device_token'],
                            'message' 	   => str_replace('{{$number}}', !empty($_temp[$user['user_id']]) ? $_temp[$user['user_id']] : 0 , $Template['description']),
                            'data' 	=> array(
                            	'type' => 'wait_accept',

								'message' => str_replace('{{$number}}', !empty($_temp[$user['user_id']]) ? $_temp[$user['user_id']] : 0 , $Template['description']),
								'title' => str_replace('{{$number}}', !empty($_temp[$user['user_id']]) ? $_temp[$user['user_id']] : 0 , $Template['title']),
								'content' => str_replace('{{$number}}', !empty($_temp[$user['user_id']]) ? $_temp[$user['user_id']] : 0 , $Template['content']),
								'number_order' => !empty($_temp[$user['user_id']]) ? $_temp[$user['user_id']] : 0,
                            )
                        )
                    ),
                    'time_create' => time(),
                    'status' => 1,
                    'in_app' => 1
                );

            }elseif($user['ios_device_token'] != ''){
            	$dataNoticeApp[] = array(
                    'os_device' 	=> 'ios',
                    'transport_id' 	=> 5,
                    'scenario_id' 	=> 45,
                    'template_id' 	=> 51,
                    'user_id'  	=> $user['user_id'],
                    'received' 	=> $user['user_id'],
                    'data' 		=> json_encode(
                        array(
                            'device_token' => $user['ios_device_token'],
                            'message' 	   => str_replace('{{$number}}', !empty($_temp[$user['user_id']]) ? $_temp[$user['user_id']] : 0 , $Template['description']),
                            'data' 	=> array(
                            	'type' => 'wait_accept',
								'message' => str_replace('{{$number}}', !empty($_temp[$user['user_id']]) ? $_temp[$user['user_id']] : 0 , $Template['description']),
								'title' => str_replace('{{$number}}', !empty($_temp[$user['user_id']]) ? $_temp[$user['user_id']] : 0 , $Template['title']),
								'content' => str_replace('{{$number}}', !empty($_temp[$user['user_id']]) ? $_temp[$user['user_id']] : 0 , $Template['content']),
								'number_order' => !empty($_temp[$user['user_id']]) ? $_temp[$user['user_id']] : 0,
                            )
                        )
                    ),
                    'time_create' => time(),
                    'status' => 1,
                    'in_app' => 1
                );
            }
		}

		if (!empty($dataNoticeApp)) {
        	try {
        		$Insert = \QueueModel::insert($dataNoticeApp[0]);
        	} catch (Exception $e) {
        		
        	}
        	
        }

		return Response::json($dataNoticeApp);



	}



 	public function getFuck(){

 		Input::merge(['group', 1]);

 		$StatusGroup = $this->getStatusGroup(false);

 		$ListUser = \UserConfigTransportModel::where('transport_id', 5)->where('active', 1)->lists('user_id');
 		
 		$Model  = new \ElasticBuilder('bxm_orders', 'orders');
 		$ListSttDaLay = [];
 		$ListSTTThanhCong = [];
 		$ListSTTDonCanXly = [];

        $GroupOrderStatusModel  = new \metadatamodel\GroupOrderStatusModel;
        $ListStatusOrder = $GroupOrderStatusModel::whereIn('group_status',[41, 20])->get(array('group_status', 'order_status_code'))->toArray();
        $ListSTTDonCanXly = [];

        if(!empty($ListStatusOrder)){
            foreach($ListStatusOrder as $val){
                $ListSTTDonCanXly[]   = (int)$val['order_status_code'];
            }
        }


 		foreach ($StatusGroup as $key => $value) {
 			if ($value['id'] == 27) {
 				foreach ($value['group_order_status'] as $k => $v) {
 					$ListSttDaLay[] = $v['order_status_code'];
 				}
 			}

 			if ($value['id'] == 30) {
 				foreach ($value['group_order_status'] as $i => $j) {
 					$ListSTTThanhCong[] = $j['order_status_code'];
 				}
 			}
 		}

 		$_TempData = [];
 		foreach ($ListUser as $key => $value) {
 			$_TempData[$value] = [
 				'order_picked' 	=> 0,
				'order_success' => 0,
				'pending_process' 	=> 0,
				'ticket_processing' => 0,
 			];
 		}

 		$ModelDaLay = clone $Model;
 		$DataDaLay = $ModelDaLay
 					->whereIn('status', $ListSttDaLay)
 					
 					->where('time_accept', 'gte', time() - (86400) * 60)
 					->groupBy('from_user_id', count($ListUser))
 					->get();

 		$ModelThanhCong = clone $Model;
 		$DataThanhCong = $ModelThanhCong
 						->whereIn('status', $ListSTTThanhCong)
 						->whereIn('from_user_id', $ListUser)
 						->where('time_accept', 'gte', time() - (86400) * 60)
 						->where('time_success', 'gte', time() - 86400)
 						->groupBy('from_user_id', count($ListUser))
 						->get();

 		$ModelCanXly = clone $Model;
 		$DataCanXly  = $ModelCanXly
 						->whereIn('status', $ListSTTDonCanXly)
 						->whereIn('from_user_id', $ListUser)
 						->where('time_accept', 'gte', time() - (86400) * 60)
 						->groupBy('from_user_id', count($ListUser))
 						->get();

 						 ;

        $TicketModel = \ticketmodel\RequestModel::whereIn('user_id',$ListUser)
        				->where('time_create', '>=', time() - 86400 * 7)
        				->whereNotIn('status', ['CLOSED', 'PROCESSED'])
        				->select(DB::raw('user_id, count(user_id) as total'))
        				->groupBy('user_id')->get();
        
        

 		foreach ($DataDaLay['from_user_id'] as $key => $value) {
 			if (!empty($_TempData[$value['key']])) {
 				$_TempData[$value['key']]['order_picked'] = $value['doc_count'];
 			}
 		}

 		foreach ($DataThanhCong['from_user_id'] as $key => $value) {
 			if (!empty($_TempData[$value['key']])) {
 				$_TempData[$value['key']]['order_success'] = $value['doc_count'];
 			}
 		}

 		foreach ($DataCanXly['from_user_id'] as $key => $value) {
 			if (!empty($_TempData[$value['key']])) {
 				$_TempData[$value['key']]['pending_process'] = $value['doc_count'];
 			}
 		}

 		foreach ($TicketModel as $key => $value) {
 			if (!empty($_TempData[$value['user_id']])) {
 				$_TempData[$value['user_id']]['ticket_processing'] = $value['total'];
 			}
 		}

 		$Template = \TemplateModel::where('id',50)->first();

        $ListDevice = $this->getListUserapp($ListUser);
        $dataNoticeApp = [];

        if(!empty($ListDevice)){

        	foreach ($ListDevice as $key => $user) {
	            if($user['android_device_token'] != ''){

	                $dataNoticeApp[] = array(
	                    'os_device' 	=> 'android',
	                    'transport_id' 	=> 5,
	                    'scenario_id' 	=> 44,
	                    'template_id' 	=> 50,
	                    'user_id'  	=> $user['user_id'],
	                    'received' 	=> $user['user_id'],
	                    'data' 		=> json_encode(
	                        array(
	                            'device_token' => $user['android_device_token'],
	                            'message' 	   => str_replace('{{$date}}', time(), $Template['title']),
	                            'data' 	=> array(
	                            	'type' => 'statistic',
									'title' => str_replace('{{$date}}', time(), $Template['title']),
									'order_picked' 		=> $_TempData[$user['user_id']]['order_picked'],
									'order_success' 	=> $_TempData[$user['user_id']]['order_success'],
									'pending_process' 	=> $_TempData[$user['user_id']]['pending_process'],
									'ticket_processing' => $_TempData[$user['user_id']]['ticket_processing'],
	                            )
	                        )
	                    ),
	                    'time_create' => time(),
	                    'status' => 1,
	                    'in_app' => 1
	                );

	            }elseif($user['ios_device_token'] != ''){
	            	$dataNoticeApp[] = array(
	                    'os_device' 	=> 'ios',
	                    'transport_id' 	=> 5,
	                    'scenario_id' 	=> 44,
	                    'template_id' 	=> 50,
	                    'user_id'  	=> $user['user_id'],
	                    'received' 	=> $user['user_id'],
	                    'data' 		=> json_encode(
	                        array(
	                            'device_token' => $user['ios_device_token'],
	                            'message' 	   => str_replace('{{$date}}', time(), $Template['title']),
	                            'data' 	=> array(
	                            	'type' => 'statistic',
									'title' => str_replace('{{$date}}', time(), $Template['title']),
									'order_picked' 		=> $_TempData[$user['user_id']]['order_picked'],
									'order_success' 	=> $_TempData[$user['user_id']]['order_success'],
									'pending_process' 	=> $_TempData[$user['user_id']]['pending_process'],
									'ticket_processing' => $_TempData[$user['user_id']]['ticket_processing'],
	                            )
	                        )
	                    ),
	                    'time_create' => time(),
	                    'status' => 1,
	                    'in_app' => 1
	                );
	            }
            }
        }

        if (!empty($dataNoticeApp)) {
        	try {
        		$Insert = \QueueModel::insert($dataNoticeApp[0]);
        	} catch (Exception $e) {
        		
        	}
        	
        }

 		return Response::json($dataNoticeApp);
 	}

	public function postHide($queue_id){
		$UserInfo   = $this->UserInfo();
        $UserId 	= (int)$UserInfo['id'];

		$QueueModel = new \QueueModel;
		//$QueueModel = $QueueModel::where('id', $queue_id)->where('user_id', $UserId)->where('delete', 0)->first();
		$QueueModel = $QueueModel::where('id', $queue_id)->where('delete', 0)->first();
		if (!empty($QueueModel)) {
			$QueueModel->delete = 1;
			try {
				$QueueModel->save();
			} catch (Exception $e) {
				
			}
		}

		return Response::json([
			'error'			=> false,
			'error_message' => "SUCCESSFULL",
			'data'			=> []
		]);
	}

	public function postUnsubscribe($queue_id){
		$UserInfo   = $this->UserInfo();
        $UserId 	= (int)$UserInfo['id'];

		$QueueModel = new \QueueModel;
		$QueueModel = $QueueModel::where('id', $queue_id)->where('user_id', $UserId)->where('delete', 0)->first();

		if (!empty($QueueModel)) {
			try {
				\ScenarioUnsubscribeModel::firstOrCreate([
					'scenario_id' => $QueueModel->scenario_id,
					'user_id'	  => $QueueModel->user_id,
					'time_create' => time()
				]);
			} catch (Exception $e) {
				
			}
		}

		return Response::json([
			'error'			=> false,
			'error_message' => "SUCCESSFULL",
			'data'			=> ""
		]);
	}
  
  public function getNotify($id = 0){
	$_data = 
	  array (
	  	array (
		  'id' => 31,
		  'user_id' => 1234,
		  'view' => 1,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'message' => 'Yêu cầu mới được trả lời',
			'type' => 'new_ticket_reply',
			'title'=> 'Yêu cầu mới được trả lời',
			'ticket_id'=> 296767,
			'time_update' => 1463366116,
			'link'=> 'http://dantri.com',
			'content' =>  \Michelf\Markdown::defaultTransform('Kính gửi shop!

Đơn hàng SC61496159433  đã được tiếp nhận và yêu cầu hãng vận chuyển qua lấy hàng. Bưu tá sẽ lấy hàng từ 15h đến 18h. 
Nếu vẫn chưa có ai qua lấy hàng, vui lòng phản hồi lại để Shipchung tiếp tục xử lý.
Trân trọng!')
		  ),
		),
		array (
		  'id' => 31,
		  'user_id' => 1234,
		  'view' => 1,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'message' => 'Yêu cầu mới được trả lời',
			'type' => 'new_ticket_reply',
			'title'=> 'Yêu cầu mới được trả lời',
			'ticket_id'=> 296767,
			'time_update' => 1463366116,
			'link'=> 'http://dantri.com',
			'content' =>  \Michelf\Markdown::defaultTransform('Kính gửi shop!

Đơn hàng SC61496159433  đã được tiếp nhận và yêu cầu hãng vận chuyển qua lấy hàng. Bưu tá sẽ lấy hàng từ 15h đến 18h. 
Nếu vẫn chưa có ai qua lấy hàng, vui lòng phản hồi lại để Shipchung tiếp tục xử lý.
Trân trọng!')
		  ),
		),
		array (
		  'id' => 31,
		  'user_id' => 1234,
		  'view' => 1,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'message' => 'Yêu cầu mới được trả lời',
			'type' => 'new_ticket_reply',
			'title'=> 'Yêu cầu mới được trả lời',
			'ticket_id'=> 296767,
			'time_update' => 1463366116,
			'link'=> 'http://dantri.com',
			'content' =>  \Michelf\Markdown::defaultTransform('Kính gửi shop!

Đơn hàng SC61496159433  đã được tiếp nhận và yêu cầu hãng vận chuyển qua lấy hàng. Bưu tá sẽ lấy hàng từ 15h đến 18h. 
Nếu vẫn chưa có ai qua lấy hàng, vui lòng phản hồi lại để Shipchung tiếp tục xử lý.
Trân trọng!')
		  ),
		),
		array (
		  'id' => 31,
		  'user_id' => 1234,
		  'view' => 1,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'message' => 'Yêu cầu mới được trả lời',
			'type' => 'new_ticket_reply',
			'title'=> 'Yêu cầu mới được trả lời',
			'ticket_id'=> 296767,
			'time_update' => 1463366116,
			'link'=> 'http://dantri.com',
			'content' =>  \Michelf\Markdown::defaultTransform('Kính gửi shop!

Đơn hàng SC61496159433  đã được tiếp nhận và yêu cầu hãng vận chuyển qua lấy hàng. Bưu tá sẽ lấy hàng từ 15h đến 18h. 
Nếu vẫn chưa có ai qua lấy hàng, vui lòng phản hồi lại để Shipchung tiếp tục xử lý.
Trân trọng!')
		  ),
		),
	  	array (
		  'id' => 31,
		  'user_id' => 1234,
		  'view' => 1,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'message' => 'Yêu cầu mới được trả lời',
			'type' => 'new_ticket_reply',
			'title'=> 'Yêu cầu mới được trả lời',
			'ticket_id'=> 296767,
			'time_update' => 1463366116,
			'link'=> 'http://dantri.com',
			'content' =>  \Michelf\Markdown::defaultTransform('Kính gửi shop!

Đơn hàng SC61496159433  đã được tiếp nhận và yêu cầu hãng vận chuyển qua lấy hàng. Bưu tá sẽ lấy hàng từ 15h đến 18h. 
Nếu vẫn chưa có ai qua lấy hàng, vui lòng phản hồi lại để Shipchung tiếp tục xử lý.
Trân trọng!')
		  ),
		),
		array (
		  'id' => 1,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'statistic',
			'title' => 'Báo cáo vận đơn ngày 20/2/2016',
			'message' => 'Báo cáo vận đơn ngày 20/2/2016',
			'order_picked' => 120,
			'order_success' => 203,
			'pending_process' => 20,
			'ticket_processing' => 2,
		  ),
		),
		array (
		  'id' => 2,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'slow_delivery',
			'message' 	=> 'Đơn hàng giao chậm',
			'content' 	=> 'Shipchung xin cáo lỗi với quý khách về đơn hàng SC12345678 có thể giao chậm hơn dự kiến từ 2-3 ngày. Quý khách có muốn thông báo với người mua không? Shipchung xin cáo lỗi với quý khách về đơn hàng SC12345678 có thể giao chậm hơn dự kiến từ 2-3 ngày. Quý khách có muốn thông báo với người mua không? Shipchung xin cáo lỗi với quý khách về đơn hàng SC12345678 có thể giao chậm hơn dự kiến từ 2-3 ngày. Quý khách có muốn thông báo với người mua không?',
			'title'		=> 'Đơn hàng giao chậm',
			'order_id' 	=> 1,
			'tracking_code' 	=> 'SC12345678',
			'customer_phone' 	=> '01626616817',
			'status'		=> 60,
			'status_name' 	=> 'Đang vận chuyển',
			'time_update' 	=> 1463366116,
		  ),
		),
		array (
		  'id' => 31,
		  'user_id' => 1234,
		  'view' => 1,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'message' => 'Yêu cầu mới được trả lời',
			'type' => 'new_ticket_reply',
			'title'=> 'Yêu cầu mới được trả lời',
			'ticket_id'=> 296767,
			'time_update' => 1463366116,
			'link'=> 'http://dantri.com',
			'content' =>  \Michelf\Markdown::defaultTransform('Kính gửi shop!

Đơn hàng SC61496159433  đã được tiếp nhận và yêu cầu hãng vận chuyển qua lấy hàng. Bưu tá sẽ lấy hàng từ 15h đến 18h. 
Nếu vẫn chưa có ai qua lấy hàng, vui lòng phản hồi lại để Shipchung tiếp tục xử lý.
Trân trọng!')
		  ),
		),
		array (
		  'id' => 2,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'slow_delivery',
			'message' => 'Đơn hàng giao chậm',
			'content' => 'Shipchung xin cáo lỗi với quý khách về đơn hàng SC12345678 có thể giao chậm hơn dự kiến từ 2-3 ngày. Quý khách có muốn thông báo với người mua không? Shipchung xin cáo lỗi với quý khách về đơn hàng SC12345678 có thể giao chậm hơn dự kiến từ 2-3 ngày. Quý khách có muốn thông báo với người mua không? Shipchung xin cáo lỗi với quý khách về đơn hàng SC12345678 có thể giao chậm hơn dự kiến từ 2-3 ngày. Quý khách có muốn thông báo với người mua không?',
			'title'		=> 'Đơn hàng giao chậm',
			'order_id' => 1,
			'tracking_code' => 'SC12345678',
			'status'		=> 60,
			'customer_phone' => '01626616817',
			'status_name' => 'Đang vận chuyển',
			'time_update' => 1463366116,
		  ),
		),
		array (
		  'id' => 2,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'slow_delivery',
			'message' => 'Đơn hàng giao chậm',
			'content' => 'Shipchung xin cáo lỗi với quý khách về đơn hàng SC12345678 có thể giao chậm hơn dự kiến từ 2-3 ngày. Quý khách có muốn thông báo với người mua không? Shipchung xin cáo lỗi với quý khách về đơn hàng SC12345678 có thể giao chậm hơn dự kiến từ 2-3 ngày. Quý khách có muốn thông báo với người mua không? Shipchung xin cáo lỗi với quý khách về đơn hàng SC12345678 có thể giao chậm hơn dự kiến từ 2-3 ngày. Quý khách có muốn thông báo với người mua không?',
			'title'		=> 'Đơn hàng giao chậm',
			'order_id' => 1,
			'tracking_code' => 'SC12345678',
			'customer_phone' => '01626616817',
			'status'		=> 60,
			'status_name' => 'Đang vận chuyển',
			'time_update' => 1463366116,
		  ),
		),
		array (
		  'id' => 31,
		  'user_id' => 1234,
		  'view' => 1,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'message' => 'Yêu cầu mới được trả lời',
			'type' => 'new_ticket_reply',
			'title'=> 'Yêu cầu mới được trả lời',
			'ticket_id'=> 296767,
			'time_update' => 1463366116,
			'link'=> 'http://dantri.com',
			'content' =>  \Michelf\Markdown::defaultTransform('Kính gửi shop!

Đơn hàng SC61496159433  đã được tiếp nhận và yêu cầu hãng vận chuyển qua lấy hàng. Bưu tá sẽ lấy hàng từ 15h đến 18h. 
Nếu vẫn chưa có ai qua lấy hàng, vui lòng phản hồi lại để Shipchung tiếp tục xử lý.
Trân trọng!')
		  ),
		),
		array (
		  'id' => 31,
		  'user_id' => 1234,
		  'view' => 1,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'message' => 'Yêu cầu mới được trả lời',
			'type' => 'new_ticket_reply',
			'title'=> 'Yêu cầu mới được trả lời',
			'ticket_id'=> 296767,
			'time_update' => 1463366116,
			'link'=> 'http://dantri.com',
			'content' =>  \Michelf\Markdown::defaultTransform('Kính gửi shop!

Đơn hàng SC61496159433  đã được tiếp nhận và yêu cầu hãng vận chuyển qua lấy hàng. Bưu tá sẽ lấy hàng từ 15h đến 18h. 
Nếu vẫn chưa có ai qua lấy hàng, vui lòng phản hồi lại để Shipchung tiếp tục xử lý.
Trân trọng!')
		  ),
		),
		array (
		  'id' => 41,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'promotion',
			'message' => 'Khuyến mãi',
			'title' => 'Khuyến mãi sốc',
			'content' => 'Giảm 15% phí vận chuyển khi tạo đơn hàng gửi hàng tại bưu cục trên Shipchung.vn',
			'link' => 'https://www.shipchung.vn/bang-gia-van-chuyen/',
			'time' => 1463366116,
		  ),
		),
		array (
		  'id' => 51,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'promotion_create_order',
			'message' => 'Hướng dẫn tạo đơn hàng',
			'title' => 'Bạn đã tạo đơn hàng? ',
			'content' => 'Bán hàng online dễ dàng và tiện lợi hơn trên ứng dụng di động Shipchung.vn',
			'meta' => 'NvFB8cOns8E',
		  ),
		),
		
		array (
		  'id' => 61,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'delivery_failed',
			'courier_id' => 1,
			'status' => 60,
			'message' => 'Giao hàng thất bại',
			'title' => 'SC12345678',
			'content' => ' Đơn hàng giao hàng không thành công do không liên hệ được với người mua, bạn vui lòng liên hệ lại với người mua và phản hồi trước <strong>10:20</strong> sáng ngày <strong>10/05/2016</strong> Đơn hàng giao hàng không thành công do không liên hệ được với người mua, bạn vui lòng liên hệ lại với người mua và phản hồi trước <strong>10:20</strong> sáng ngày <strong>10/05/2016</strong>',
			'order_id' => 1,
			'tracking_code' => 'SC12345678',
			'customer_phone' => '01626616817',
			'status'		=> 60,
			'status_name' => 'Giao không thành công',
			'time_update' => 1463366116,
		  ),
		),
		
		

		array (
		  'id' => 62,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'order_overweight',
			'message' => 'Bạn có đơn hàng vượt cân ',
			'title' => 'SC12345678',
			'content' => 'Đơn hàng của bạn có khối lượng tăng thêm (giảm đi) <strong>500g</strong> do phát sinh thêm phí vượt cân mới (được giảm phí) <strong>89.000đ</strong>, Lưu ý: Khi có thay đổi về khối lượng, tổng số tiền thu hộ vẫn sẽ giữ nguyên là <strong>490.000đ</strong> do đơn hàng đã duyệt qua hãng vận chuyển. Bạn vui lòng kiểm tra và phản hồi trước <strong>10:20</strong> ngày <strong>20/06/2016</strong> Đơn hàng của bạn có khối lượng tăng thêm (giảm đi) <strong>500g</strong> do phát sinh thêm phí vượt cân mới (được giảm phí) <strong>89.000đ</strong>, Lưu ý: Khi có thay đổi về khối lượng, tổng số tiền thu hộ vẫn sẽ giữ nguyên là <strong>490.000đ</strong> do đơn hàng đã duyệt qua hãng vận chuyển. Bạn vui lòng kiểm tra và phản hồi trước <strong>10:20</strong> ngày <strong>20/06/2016</strong>',
			'order_id' => 1,
			'tracking_code' => 'SC12345678',
			'status'		=> 60,
			'status_name' => 'Đã lấy hàng',
			'time_update' => 1463366116,
		  ),
		),

		
		array (
		  'id' => 62,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'pickup_failed',
			'message' => 'Lấy hàng không thành công',
			'title' => 'SC12345678',
			'content' => 'Đơn hàng lấy không thành công do không liên hệ được với quý khách, quý khách vui lòng phàn hồi lại cho chúng tôi trước <strong>10:20</strong> sáng ngày <strong>10/05/2016</strong> Đơn hàng lấy không thành công do không liên hệ được với quý khách, quý khách vui lòng phàn hồi lại cho chúng tôi trước <strong>10:20</strong> sáng ngày <strong>10/05/2016</strong>',
			'order_id' => 1,
			'tracking_code' => 'SC12345678',
			'status'		=> 60,
			'status_name' => 'Lấy hàng không thành công',
			'time_update' => 1463366116,
		  ),
		),
		array (
		  'id' => 71,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'wait_accept',
			'message' => 'Duyệt đơn hàng ngay',
			'content' => 'Hãy duyệt đơn hàng ngay bây giờ để bắt đầu sử dụng dịch vụ của Shipchung.vn',
			'number_order' => 6,
		  ),
		),
		array (
		  'id' => 81,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'promotion_guide_video',
			'message' => 'Hướng dẫn giao hàng',
			'title' => 'Hướng dẫn giao hàng',
			'content' => 'Bạn hãy dành ít phút đọc hướng dẫn đóng gói và giao hàng cho bưu tá.',
			'meta' => 'NvFB8cOns8E',
			'link' => 'https://www.shipchung.vn/bang-gia-van-chuyen/',
		  ),
		),
		array (
		  'id' => 91,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'order_status',
			'message' => 'Đơn hàng đã được lấy thành công',
			'title' => 'SC12345678',
			'content' => 'Đã được lấy hàng thành công.',
			'time_update' => 1463366116,
		  ),
		),
		array (
		  'id' => 11,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'send_at_postoffice',
			'message' => 'Gửi hàng tại bưu cục',
			'title' => 'Gửi hàng tại bưu cục',
			'bc_name' => 'BC quận 12',
			'bc_address' => 'Sô 12 , Đường 13 ,Quận 1 - HCM',
			'courier_id'	=> 1,
			'lat' => '20.987899780273',
			'lng' => '105.87699890137',
		  ),
		),
		array (
		  'id' => 12,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'title' => 'Số dư khả dụng',
			'message' => 'Hướng dẫn về số dư',
			'type' => 'promotion_guide_video',
			'content' => 'Số dư khả dụng của bạn.',
			'meta' => 'NvFB8cOns8E',
			'link' => 'http://shipchung.vn',
		  ),
		),
		array (
		  'id' => 71,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'wait_accept',
			'message' => 'Duyệt đơn hàng ngay',
			'content' => 'Hãy duyệt đơn hàng ngay bây giờ để bắt đầu sử dụng dịch vụ của Shipchung.vn',
			'number_order' => 6,
		  ),
		),
		array (
		  'id' => 81,
		  'user_id' => 1234,
		  'view' => 0,
		  'time_create' => 1463366116,
		  'data' => 
		  array (
			'type' => 'promotion_guide',
			'message' => 'Hướng dẫn giao hàng',
			'title' => 'Hướng dẫn giao hàng',
			'content' => 'Bạn hãy dành ít phút đọc hướng dẫn đóng gói và giao hàng cho bưu tá.',
			'meta' => 'https://tctechcrunch2011.files.wordpress.com/2015/09/2015_super_bowl_media_day_prime_time_event.jpg?w=960',
			'link' => 'https://www.shipchung.vn/bang-gia-van-chuyen/',
		  ),
		)
	  );
	
	$result = array (
	  'error' => false,
	  'error_message' => '',
	  'total' => 50,
	  'total_page' => 3,
	  'data' => []
	
	);
	
	if (Input::get('cmd') == 'demo') {
		$page           = Input::has('page')    ? (int)Input::get('page') : 1;
	    $itemPage       = Input::has('limit')   ? (int)Input::get('limit') 	  : 20;
	    $offset 		= ($page - 1) * $itemPage;
	    $i = 0;
	    foreach ($_data as $key => $value) {
	    	if ($i < $itemPage) {
				$result['data'][] = $_data[array_rand($_data)];
    		}
	    	$i++;
	    }
	    if (sizeof($_data) < $page * $itemPage ) {
	    	$result['data'] = [];
	    }
	    $result['total'] 		= sizeof($_data);
	    $result['total_page'] 	= ceil(sizeof($_data) / $itemPage);
		$result['data'] 		= array_reverse($result['data']);
		return Response::json($result);
	}



	$page           = Input::has('page')    ? (int)Input::get('page') : 1;
    $itemPage       = Input::has('limit')   ? (int)Input::get('limit') 	  : 20;
    $offset 		= ($page - 1) * $itemPage;

	$UserInfo   = $this->UserInfo();
    $UserId 	= (int)$UserInfo['id'];

    $Data 		= [];

    $QueueModel = new \QueueModel;
	$QueueModel 	= $QueueModel->where('user_id', $UserId);

	if (!empty($id)) {
		$QueueModel = $QueueModel->where('id', $id);
	}

	$QueueModel = $QueueModel->where('in_app', 1)->where('delete', 0);
	$CountModel = clone $QueueModel;
    $Total 		= $CountModel->count();

    if ($Total > 0) {
    	$QueueModel = $QueueModel->skip($offset)->take($itemPage);
		$Data 		= $QueueModel->select(['id', 'user_id', 'view', 'time_create', 'data'])->orderBy('id', 'DESC')->get();

		foreach ($Data as $key => $value) {
	    	if (!empty($value['data'])) {
	    		$_data = (array)json_decode($value['data']);

	    		if (!empty($_data['data'])) {
	    			$_data = array_merge($_data, (array)$_data['data']);
	    			unset($_data['data']);
	    		}

	    		$Data[$key]['data'] = $_data;


	    	}
    	}
    }else {
    	if ($page == 1) {
    		# code...
    	
	    	$Data[] = [
	    	'id' => 51,
			  'user_id' => 1234,
			  'view' => 0,
			  'time_create' => 1463366116,
			  'data' => 
			  array (
				'type' => 'promotion_create_order',
				'message' => 'Hướng dẫn tạo đơn hàng',
				'title' => 'Bạn đã tạo đơn hàng? ',
				'content' => 'Bán hàng online dễ dàng và tiện lợi hơn trên ứng dụng di động Shipchung.vn',
				'meta' => '4Rosx9WlH9I',
			  )
			];
		}
    }

    

	return Response::json([
		'error'			=> false,
		'error_message'	=> '',
		'data'			=> $Data,
		'total'			=> $Total,
		'total_page'	=> ceil($Total / $itemPage)
	]);

  }
  
}





?>