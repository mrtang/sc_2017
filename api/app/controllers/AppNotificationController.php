<?php
use omsmodel\AppNotifyModel;
use sellermodel\UserInfoModel;
use mobile\PushNotificationController;
use ordermodel\OrdersModel;
use ordermodel\DetailModel;
use sellermodel\CashInModel;
use ordermodel\OrderProblemModel;


class AppNotificationController extends \BaseController {
	//lay ra user dung app
	private function getUser(){
		$data = UserInfoModel::where('verified',1)->where('android_device_token','!=','')->orWhere('ios_device_token','!=','')->get(array('user_id','android_device_token','ios_device_token'))->toArray();
		
	}

	public function getStatusGroup($json = true)
    {
        $group              = Input::has('group')                ? (int)Input::get('group')                       : 4;
        $CacheName          = 'cache_group_status_'.$group;
        if (Cache::has($CacheName)){
            $ListGroup    = Cache::get($CacheName);
        }else{
            $Model      = new metadatamodel\GroupStatusModel;
            $ListGroup  = $Model::where('group', $group)->with('group_order_status')->get()->toArray();
            if(!empty($ListGroup)){
                Cache::put($CacheName,$ListGroup,1440);
            }
        }
        return $json ? Response::json([ 'error'         => false, 'list_group'    => $ListGroup]) : $ListGroup;

    }

    
	public function getTestNotifyProcessPage(){
		if (Input::get('os') == 'android') {
			$queueAndroid = QueueModel::where('id',1234729)->where('os_device','android')->first();
			$result[] = $this->getSendnoticeandroid($queueAndroid);	
		}else {
			$queueIOs = QueueModel::where('id',1234779)->where('os_device','ios')->first();

			$result[] = $this->getSendnoticeios($queueIOs);
		}
		
		return $result;
		
	}



	//gui notice android
	public function getSendnoticeandroid($queue = null){
		$objNotice = new PushNotificationController;
		if (empty($queue)) {
			$queue = QueueModel::where('transport_id',5)->where('status',0)->where('os_device','android')->first();	
		}
		
		if(!empty($queue)){

			$data = json_decode($queue['data'],1);

			if(!empty($data)){

				if (!empty($data['data']) && !empty($data['data']['type'])) {

					$type = "notify_process_page";

					if (in_array($data['data']['type'], ['promotion_create_order', 'wait_accept'])) {
						$type = $data['data']['type'];
					}

					$data['data']['type'] = $type;

					if (empty($data['data']['message']) && !empty($data['message'])) {
						$data['data']['message'] = $data['message'];
					}

				}

				$push = $objNotice->PushAndroid(array($data['device_token']),$data['data']);
				if($push == true){
					$update = QueueModel::where('id',$queue['id'])->update(array('status' => 1,'time_success' => $this->time()));
					$contents = array(
		                'error'         => false,
		                'message'       => 'Notify success!',
		            );
				}else{
					$contents = array(
		                'error'         => true,
		                'message'       => 'Notify fail!',
		            );
				}
			}else{
				$contents = array(
	                'error'         => true,
	                'message'       => 'Not message notice app!',
	            );
			}
		}else{
			$contents = array(
                'error'         => true,
                'message'       => 'Not data notice app!',
            );
		}

		return Response::json($contents);
	}
	//gui notice ios
	public function getSendnoticeios($queue = null){
		$objNotice = new PushNotificationController;
		if (empty($queue)) {
			$queue = QueueModel::where('transport_id',5)->where('status',0)->where('os_device','ios')->first();	
		}
		
		if(!empty($queue)){
			$data = json_decode($queue['data'],1);
			if(!empty($data)){

				if (!empty($data['data']) && !empty($data['data']['type'])) {

					$type = "notify_process_page";
					if (in_array($data['data']['type'], ['promotion_create_order', 'wait_accept'])) {
						$type = $data['data']['type'];
					}
					$data['data']['type'] = $type;
				}

				$push = $objNotice->PushIos($data['device_token'],$data['message'],$data['data']);
				if($push == true){
					$update = QueueModel::where('id',$queue['id'])->update(array('status' => 1,'time_success' => $this->time()));
					$contents = array(
		                'error'         => false,
		                'message'       => 'Notify success!',
		            );
				}else{
					$contents = array(
		                'error'         => true,
		                'message'       => 'Notify fail!',
		            );
				}
			}else{
				$contents = array(
	                'error'         => true,
	                'message'       => 'Not message notice app!',
	            );
			}
		}else{
			$contents = array(
                'error'         => true,
                'message'       => 'Not data notice app!',
            );
		}

		return Response::json($contents);
	}



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

	
	public function getStatisticEachDay(){

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
 					
 					->where('time_accept', 'gte', $this->time() - (86400) * 60)
 					->groupBy('from_user_id', count($ListUser))
 					->get();

 		$ModelThanhCong = clone $Model;
 		$DataThanhCong = $ModelThanhCong
 						->whereIn('status', $ListSTTThanhCong)
 						->whereIn('from_user_id', $ListUser)
 						->where('time_accept', 'gte', $this->time() - (86400) * 60)
 						->where('time_success', 'gte', $this->time() - 86400)
 						->groupBy('from_user_id', count($ListUser))
 						->get();

 		$ModelCanXly = clone $Model;
 		$DataCanXly  = $ModelCanXly
 						->whereIn('status', $ListSTTDonCanXly)
 						->whereIn('from_user_id', $ListUser)
 						->where('time_accept', 'gte', $this->time() - (86400) * 60)
 						->groupBy('from_user_id', count($ListUser))
 						->get();

 						 ;

        $TicketModel = \ticketmodel\RequestModel::whereIn('user_id',$ListUser)
        				->where('time_create', '>=', $this->time() - 86400 * 7)
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
	                            'message' 	   => str_replace('{{$date}}', date("d/m/Y H:i", $this->time()), $Template['title']),
	                            'data' 	=> array(
	                            	'type' => 'statistic',
									'title' => str_replace('{{$date}}', date("d/m/Y H:i", $this->time()), $Template['title']),
									'order_picked' 		=> $_TempData[$user['user_id']]['order_picked'],
									'order_success' 	=> $_TempData[$user['user_id']]['order_success'],
									'pending_process' 	=> $_TempData[$user['user_id']]['pending_process'],
									'ticket_processing' => $_TempData[$user['user_id']]['ticket_processing'],
	                            )
	                        )
	                    ),
	                    'time_create' => $this->time(),
	                    'status' => 0,
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
	                            'message' 	   => str_replace('{{$date}}', date("d/m/Y H:i", $this->time()), $Template['title']),
	                            'data' 	=> array(
	                            	'type' => 'statistic',
									'title' => str_replace('{{$date}}', date("d/m/Y H:i", $this->time()), $Template['title']),
									'order_picked' 		=> $_TempData[$user['user_id']]['order_picked'],
									'order_success' 	=> $_TempData[$user['user_id']]['order_success'],
									'pending_process' 	=> $_TempData[$user['user_id']]['pending_process'],
									'ticket_processing' => $_TempData[$user['user_id']]['ticket_processing'],
	                            )
	                        )
	                    ),
	                    'time_create' => $this->time(),
	                    'status' => 0,
	                    'in_app' => 1
	                );
	            }
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






 	public function getOrderWaitAccept(){

		$OrderModel = new \ordermodel\OrdersModel;
		$Data 		= $OrderModel->where('time_accept', 0)->where('status')->where('time_create', '>=',$this->time() - (7 * 86400))->select(DB::raw('from_user_id, count(from_user_id) as total'))->groupBy('from_user_id')->get();

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
                    'time_create' => $this->time(),
                    'status' => 0,
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
                    'time_create' => $this->time(),
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







	public function getOrderSendFromPostoffice(){

		$UserId  = Input::has('user_id') ? Input::get('user_id') : 0;
		$OrderId = Input::has('order_id') ? Input::get('order_id') : 0;

		if (empty($OrderId) || empty($UserId)) {
			return Response::json([
				'error'			=> true,
				'error_message'	=> 'order_id must not empty'
			]);
		}

		$Model  = new \ElasticBuilder('bxm_orders', 'orders');
		$ListOrder  = $Model->where('from_user_id', $UserId)->where('post_office_id', 'gt', 0)->get();


		

		if (count($ListOrder) > 1) {
			return Response::json([
				'error'			=> true,
				'error_message'	=> 'Khong phai van don dau tien',
				'data'	=> count($ListOrder)
			]);
		}

		$OrderModel = new \ElasticBuilder('bxm_orders', 'orders');
		$Order = $OrderModel->where('id', $OrderId)->get();

		if (empty($OrderModel)) {
			return Response::json([
				'error'			=> true,
				'error_message'	=> 'abc',
				'data'	=> count($OrderModel)
			]);
		}

		$PostOfficeId = $Order[0]['post_office_id'];

		$PostOfficeModel = \CourierPostOfficeModel::where('id', $PostOfficeId)->first();

		if (empty($PostOfficeModel)) {
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
								'title' 		=> $Template['title'],
								'bc_name' 		=> $PostOfficeModel['name'],
								'bc_address' 	=> $PostOfficeModel['address'],
								'courier_id'	=> $PostOfficeModel['courier_id'],
								'lat' 			=> $PostOfficeModel['lat'],
								'lng' 			=> $PostOfficeModel['lng'],
                            )
                        )
                    ),
                    'time_create' => $this->time(),
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
								'title' 		=> $Template['title'],
								'bc_name' 		=> $PostOfficeModel['name'],
								'bc_address' 	=> $PostOfficeModel['address'],
								'courier_id'	=> $PostOfficeModel['courier_id'],
								'lat' 			=> $PostOfficeModel['lat'],
								'lng' 			=> $PostOfficeModel['lng'],
                            )
                        )
                    ),
                    'time_create' => $this->time(),
                    'status' => 0,
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

	//notice app overweight
	private function getNoticeoverweight($id){
		if(!$id){
			$contents = array(
                'error'     => true,
                'message'   => 'Not order in database!!'
            );
            return Response::json($contents);
		}
		$Data = OrdersModel::where('id',$id)->first();
		if(!empty($Data)){
			$DetailOrder = DetailModel::where('order_id',$Data['id'])->first();
			$ListStatus = BaseController::getStatus();
			if(empty($DetailOrder)){
				$contents = array(
	                'error'     => true,
	                'message'   => 'Fail DB!!'
	            );
	            return Response::json($contents);
			}

			if ((int)$DetailOrder['sc_pvk'] == 0) {
				return 1;
			}
			if((int)$Data['over_weight'] > 0){
				$OptionType = 'tăng thêm';
				$OptionPrice = 'phát sinh thêm phí vượt cân mới';
			}else{
				$OptionType = 'giảm đi';
				$OptionPrice = 'được giảm phí';
			}
			$Template = TemplateModel::where('id',52)->first();
			if(empty($Template)){
				$contents = array(
	                'error'     => true,
	                'message'   => 'Not template!!'
	            );
	            return Response::json($contents);
			}
            $Device = $this->getUserapp($Data['from_user_id']);
            if(!empty($Device)){
            	$Replace = array(
            		'option' => $OptionType,
            		'weight' => abs((int)$Data['over_weight']),
            		'option_price' => $OptionPrice,
            		'pvk' => abs((int)$DetailOrder['sc_pvk']),
            		'money_collect' => $DetailOrder['money_collect'],
            		'time_check' => date("H:i d/m/Y", $Data['time_update'] + 86400)
            	);
            	$Content = DbView::make($Template)->with($Replace)->render();
            	if($Device['android_device_token'] != ''){
                    $NoticeApp = array(
                        'os_device' => 'android',
                        'transport_id' => 5,
                        'scenario_id' => 252,
                        'template_id' => 52,
                        'user_id' => $Data['from_user_id'],
                        'received' => $Data['from_user_id'],
                        'data' => json_encode(
                            array(
                                'device_token' => $Device['android_device_token'],
                                'message' => $Template['title'],
                                'data' => array(
                                    'type' => 'order_overweight',
                                    'message' => $Template['title'],
                                    'title' => $Template['title'],
                                    'content' => $Content,
                                    'order_id' => $Data['id'],
                                    'tracking_code' => $Data['tracking_code'],
                                    'status' => $Data['status'],
                                    'status_name' => $ListStatus[$Data['status']],
                                    'time_update' => $Data['time_update']
                                )
                            )
                        ),
                        'time_create' => $this->time(),
                        'in_app' => 1,
                        'status' => 0
                    );
                }elseif($Device['ios_device_token'] != ''){
                    $NoticeApp = array(
                        'os_device' => 'ios',
                        'transport_id' => 5,
                        'scenario_id' => 252,
                        'template_id' => 52,
                        'user_id' => $Data['from_user_id'],
                        'received' => $Data['from_user_id'],
                        'data' => json_encode(
                            array(
                                'device_token' => $Device['ios_device_token'],
                                'message' => $Template['title'],
                                'data' => array(
                                    'type' => 'order_overweight',
                                    'message' => $Template['title'],
                                    'title' => $Template['title'],
                                    'content' => $Content,
                                    'order_id' => $Data['id'],
                                    'tracking_code' => $Data['tracking_code'],
                                    'status' => $Data['status'],
                                    'status_name' => $ListStatus[$Data['status']],
                                    'time_update' => $Data['time_update']
                                )
                            )
                        ),
                        'time_create' => $this->time(),
                        'in_app' => 1,
                        'status' => 0
                    );
                }
                $Send = QueueModel::insertGetId($NoticeApp);
                if($Send){
                	//update queue_id in order_proplem
            		$update = OrderProblemModel::where('order_id',$id)->update(array('queue_id' => (int)$Send));
                	return 1;
                }else{
                	return 2;
                }
            }else{
            	return 3;
            }
		}else{
			return 2;
		}
	}



	public function SendTicketReply($Ticket, $FeedBack){
			if (empty($Ticket)) {
				return Response::json([
					'error' => true,
					'error_message'=> 'Khong tim thay ticket'
				]);
			}

		
			$Template = \TemplateModel::where('id', 58)->first();
            $Device   = $this->getUserapp($Ticket['user_id']);

            if (!empty($Device)) {

        		$os_device 	  = "";
        		$device_token = "";
        		$Content 	  = "";
        		

        		if ($Device['android_device_token'] != '') {
        			$os_device = 'android';
        			$device_token = $Device['android_device_token'];
        		}elseif($Device['ios_device_token'] != ''){
        			$os_device 	  = 'ios';
        			$device_token = $Device['ios_device_token'];
        		}

            	$Content = $FeedBack['content'];

            	$dataNoticeApp = array(
                    'os_device' => $os_device,
                    'transport_id' => 5,
                    'scenario_id' => 46,
                    'template_id' => 58,
                    'user_id'  => $Ticket['user_id'],
                    'received' => $Ticket['user_id'],
                    'data' =>  json_encode(array(
                        'device_token' => $device_token,
                        'message'      => "Yêu cầu mới được trả lời",
                        'data' => array(
	                        'type' 		=> 'new_ticket_reply',
							'title'=> 'Yêu cầu mới được trả lời',
							'ticket_id'=> $FeedBack['ticket_id'],
							'time_update' => $FeedBack['time_create'],
							'link'=> 'http://shipchung.vn',
							'content' =>  \Michelf\Markdown::defaultTransform($Content)
						  )
                        
                    )),
                    'time_create' => $this->time(),
                    'status' => 0,
                    'in_app' => 1
                );
				

				$Send = QueueModel::insert($dataNoticeApp);
                if($Send){
                	$contents = array(
		                'error'     => false,
		                'message'   => 'Success!!'
		            );
                }else{
                	$contents = array(
		                'error'     => true,
		                'message'   => 'Not send!!'
		            );
                }
                

            }
            else {
				$contents = array(
			                'error'     => true,
			                'message'   => 'No device'
	            );
			}

			return Response::json($contents);
	}

	private function SendNoticePickupFailed($Order, $NewStatus){
		if (empty($Order)) {
			return Response::json([
				'error' => true,
				'error_message'=> 'Khong tim thay order'
			]);
		}

		
		$Template = \TemplateModel::where('id', 53)->first();
        $Device   = $this->getUserapp($Order->from_user_id);

        if (!empty($Device)) {
    		$ListStatus = $this->getStatus();

    		$os_device 	  = "";
    		$device_token = "";
    		$Content 	  = "";
    		$Reason 	  = "";

    		if ($Device['android_device_token'] != '') {
    			$os_device = 'android';
    			$device_token = $Device['android_device_token'];
    		}elseif($Device['ios_device_token'] != ''){
    			$os_device 	  = 'ios';
    			$device_token = $Device['ios_device_token'];
    		}
    		

    		

        	$StatusName = !empty($ListStatus[$NewStatus]) ? $ListStatus[$NewStatus] : "Lấy không thành công";
        	$StatusName = explode('/', $StatusName);
        	
        	$Reason = $Order->reason;
        	// if (sizeof($StatusName) == 3) {
        	// 	$Reason = end($StatusName);
        	// }else {
        	// 	$Reason = $StatusName[0];
        	// }

        	$StatusName = $StatusName[0];

        	$Replace = array(
    			'reason' => $Reason,
    			'time'	=> date("H:i", 	 $this->time() + 86400),
    			'date'	=> date("d/m/Y", $this->time() + 86400)
        	);

        	$Content = DbView::make($Template)->with($Replace)->render();

        	$dataNoticeApp = array(
                'os_device' => $os_device,
                'transport_id' => 5,
                'scenario_id' => 46,
                'template_id' => 53,
                'user_id'  => $Order->from_user_id,
                'received' => $Order->from_user_id,
                'data' =>  json_encode(array(
                    'device_token' => $device_token,
                    'message'      => str_replace('{{$tracking_code}}', $Order->tracking_code, $Template['title']),
                    'data' => array(
                        'type' 		=> 'pickup_failed',
						'title' 	=> str_replace('{{$tracking_code}}', $Order->tracking_code, $Template['title']),
						'content' 	=> $Content,
						'order_id' 	=> $Order->id,
						'tracking_code' => $Order->tracking_code,
						'status'		=> $Order->status,
						'courier_id'	=> $Order->courier_id,
						'status_name' 	=> $StatusName,
						'time_update' 	=> $this->time(),
                    )
                )),
                'time_create' => $this->time(),
                'status' => 0,
                'in_app' => 1
            );
			
			$Send = QueueModel::insertGetId($dataNoticeApp);
            if($Send){
            	//update queue_id in order_proplem
            	$update = OrderProblemModel::where('order_id',$Order->id)->update(array('queue_id' => (int)$Send));
            	return 1;
            }else{
            	return 4;
            }

		}else {
			return 3;
		}
	}

	//KH moi chua nap tien lan nao
  	public function getPushusernotcashin(){
        $TimeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',time())));
        $TimeEnd = strtotime(date('Y-m-d 00:00:00',time()));

        $InfoUser = User::where('app_cashin',0)->where('time_create','>',$TimeStart)->where('time_create','<',$TimeEnd)->first();
        if(!empty($InfoUser)){
            //check xem da nap tien chua
            $Cashin = CashInModel::where('status','SUCCESS')->where('user_id',$InfoUser['id'])->first();
            if(!empty($Cashin)){
            	$Update = User::where('id',$InfoUser['id'])->update(array('app_cashin' => 1));
            	$contents = array(
                    'error'     => true,
                    'message'   => 'Have cashin !!!',
                );
                return Response::json($contents);
            }
            //send notice app
            $Template = TemplateModel::where('id',46)->first();
            $Device = $this->getUserapp($InfoUser['id']);
            if(!empty($Device)){
                if($Device['android_device_token'] != ''){
                    $dataNoticeApp = array(
                        'os_device' => 'android',
                        'transport_id' => 5,
                        'scenario_id' => 226,
                        'template_id' => 46,
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
                                    'link' => 'https://www.shipchung.vn/ho-tro/nap-tien-vao-tai-khoan/'
                                )
                            )
                        ),
                        'time_create' => time(),
                        'status' => 0,
                        'in_app' => 1
                    );
                }elseif($Device['ios_device_token'] != ''){
                    $dataNoticeApp = array(
                        'os_device' => 'ios',
                        'transport_id' => 5,
                        'scenario_id' => 226,
                        'template_id' => 46,
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
                                    'link' => 'https://www.shipchung.vn/ho-tro/nap-tien-vao-tai-khoan/'
                                )
                            )
                        ),
                        'time_create' => time(),
                        'status' => 1,
                        'in_app' => 1
                    );
                }
                $Insert = QueueModel::insert($dataNoticeApp);
                if($Insert){
                	$Update = User::where('id',$InfoUser['id'])->update(array('app_cashin' => 1));
                	$contents = array(
	                    'error'     => false,
	                    'message'   => 'Success !!!',
	                );
                }else{
                	$contents = array(
	                    'error'     => true,
	                    'message'   => 'Send fail !!!',
	                );
                }
            }else{
            	$Update = User::where('id',$InfoUser['id'])->update(array('app_cashin' => 1));
            	$contents = array(
	                'error'     => true,
	                'message'   => 'Not device!!'
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

    private function SendNoticeDeliveryFailed($Order, $NewStatus){

		if (empty($Order)) {
			return Response::json([
				'error' 		=> true,
				'code'			=> 2,
				'error_message' => 'Khong tim thay order'
			]);
		}

		$Template = \TemplateModel::where('id', 57)->first();
        $Device   = $this->getUserapp($Order->from_user_id);

        if (!empty($Device)) {
    		$ListStatus = $this->getStatus();

    		$os_device 	  = "";
    		$device_token = "";
    		$Content 	  = "";
    		$Reason 	  = "";

    		if ($Device['android_device_token'] != '') {
    			$os_device = 'android';
    			$device_token = $Device['android_device_token'];
    		}elseif($Device['ios_device_token'] != ''){
    			$os_device 	  = 'ios';
    			$device_token = $Device['ios_device_token'];
    		}
    		

    		

        	$StatusName = !empty($ListStatus[$NewStatus]) ? $ListStatus[$NewStatus] : "Giao không thành công";
        	$StatusName = explode('/', $StatusName);
        	
        	$Reason = $Order->reason;
        	// if (sizeof($StatusName) == 3) {
        	// 	$Reason = end($StatusName);
        	// }else {
        	// 	$Reason = $StatusName[0];
        	// }

        	$StatusName = $StatusName[0];

        	$Replace = array(
    			'note' => $Reason,
    			'tracking_code'	=> $Order->tracking_code,
    			'time_response'	=> date("H:i d/m/Y", $this->time() + 86400)
        	);

        	$Content = DbView::make($Template)->with($Replace)->render();

        	$dataNoticeApp = array(
                'os_device' => $os_device,
                'transport_id' => 5,
                'scenario_id' => 46,
                'template_id' => 57,
                'user_id'  => $Order->from_user_id,
                'received' => $Order->from_user_id,
                'data' =>  json_encode(array(
                    'device_token' => $device_token,
                    'message'      => str_replace('{{$tracking_code}}', $Order->tracking_code, $Template['title']),
                    'data' => array(
                        'type' 		=> 'delivery_failed',
						'title' 	=> str_replace('{{$tracking_code}}', $Order->tracking_code, $Template['title']),
						'content' 	=> $Content,
						'order_id' 	=> $Order->id,
						'tracking_code'  => $Order->tracking_code,
						'status'		 => $Order->status,
						'courier_id'	 => $Order->courier_id,
						'status_name' 	 => $StatusName,
						'customer_phone' => $Order->to_phone,
						'time_update' 	 => $this->time(),
                    )
                )),
                'time_create' => $this->time(),
                'status' => 0,
                'in_app' => 1
            );
			
			$Send = QueueModel::insertGetId($dataNoticeApp);
            if($Send){
            	//update queue_id in order_proplem
            	$update = OrderProblemModel::where('order_id',$Order->id)->update(array('queue_id' => (int)$Send));
            	return 1;
            }else{
            	return 4;
            }
            

        }
        else {
			return 3;
		}
	}

    private function SendNoticeReturnOrder($Order, $NewStatus){

		if (empty($Order)) {
			return Response::json([
				'error' 		=> true,
				'code'			=> 2,
				'error_message' => 'Khong tim thay order'
			]);
		}

		$Template = \TemplateModel::where('id', 77)->first();
        $Device   = $this->getUserapp($Order->from_user_id);

        if (!empty($Device)) {
    		$ListStatus = $this->getStatus();

    		$os_device 	  = "";
    		$device_token = "";
    		$Content 	  = "";
    		$Reason 	  = "";

    		if ($Device['android_device_token'] != '') {
    			$os_device = 'android';
    			$device_token = $Device['android_device_token'];
    		}elseif($Device['ios_device_token'] != ''){
    			$os_device 	  = 'ios';
    			$device_token = $Device['ios_device_token'];
    		}
    		

    		

        	$StatusName = !empty($ListStatus[$NewStatus]) ? $ListStatus[$NewStatus] : "Yêu cầu chuyển hoàn";
        	$StatusName = explode('/', $StatusName);
        	
        	$Reason = $Order->reason;
        	// if (sizeof($StatusName) == 3) {
        	// 	$Reason = $Order->reason;
        	// }else {
        	// 	$Reason = $StatusName[0];
        	// }

        	$StatusName = $StatusName[0];

        	$Replace = array(
    			'note' => $Reason,
    			'tracking_code'	=> $Order->tracking_code,
    			'time_response'	=> date("H:i d/m/Y", $this->time() + 86400)
        	);

        	$Content = DbView::make($Template)->with($Replace)->render();

        	$dataNoticeApp = array(
                'os_device' => $os_device,
                'transport_id' => 5,
                'scenario_id' => 48,
                'template_id' => 77,
                'user_id'  => $Order->from_user_id,
                'received' => $Order->from_user_id,
                'data' =>  json_encode(array(
                    'device_token' => $device_token,
                    'message'      => str_replace('{{$tracking_code}}', $Order->tracking_code, $Template['title']),
                    'data' => array(
                        'type' 		=> 'delivery_failed',
						'title' 	=> str_replace('{{$tracking_code}}', $Order->tracking_code, $Template['title']),
						'content' 	=> $Content,
						'order_id' 	=> $Order->id,
						'tracking_code'  => $Order->tracking_code,
						'status'		 => $Order->status,
						'courier_id'	 => $Order->courier_id,
						'status_name' 	 => $StatusName,
						'customer_phone' => $Order->to_phone,
						'time_update' 	 => $this->time(),
                    )
                )),
                'time_create' => $this->time(),
                'status' => 0,
                'in_app' => 1
            );
			
			$Send = QueueModel::insertGetId($dataNoticeApp);
            if($Send){
            	//update queue_id in order_proplem
            	$update = OrderProblemModel::where('order_id',$Order->id)->update(array('queue_id' => (int)$Send));
            	return 1;
            }else{
            	return 4;
            }
            

        }
        else {
			return 3;
		}
	}

    public function SendNoticeSlowDelivery($Order, $delay_day){
			if (empty($Order)) {
				return Response::json([
					'error' => true,
					'error_message'=> 'Khong tim thay order'
				]);
			}

		
			$Template = \TemplateModel::where('id', 54)->first();
            $Device   = $this->getUserapp($Order['from_user_id']);

            if (!empty($Device)) {
        		$ListStatus = $this->getStatus();

        		$os_device 	  = "";
        		$device_token = "";
        		$Content 	  = "";
        		

        		if ($Device['android_device_token'] != '') {
        			$os_device = 'android';
        			$device_token = $Device['android_device_token'];
        		}elseif($Device['ios_device_token'] != ''){
        			$os_device 	  = 'ios';
        			$device_token = $Device['ios_device_token'];
        		}
        		

        		

            	$StatusName = !empty($ListStatus[$Order['status']]) ? $ListStatus[$Order['status']] : "Giao chậm";
            	$StatusName = explode('/', $StatusName);
            	
            	$StatusName = $StatusName[0];

            	$Replace = array(
        			'tracking_code' => $Order['tracking_code'],
        			'delay_days'	=> $delay_day,
            	);

            	$Content = DbView::make($Template)->with($Replace)->render();

            	$dataNoticeApp = array(
                    'os_device' => $os_device,
                    'transport_id' => 5,
                    'scenario_id' => 46,
                    'template_id' => 53,
                    'user_id'  => $Order['from_user_id'],
                    'received' => $Order['from_user_id'],
                    'data' =>  json_encode(array(
                        'device_token' => $device_token,
                        'message'      => $Order['tracking_code'],
                        'data' => array(
	                        'type' 		=> 'slow_delivery',
							'title' 	=> $Order['tracking_code'],
							'content' 	=> $Content,
							'order_id' 	=> $Order['id'],
							'tracking_code' => $Order['tracking_code'],
							'status'		=> $Order['status'],
							'courier_id'	=> $Order['courier_id'],
							'status_name' 	=> $StatusName,
							'customer_phone' => $Order['to_phone'],
							'time_update' 	=> $this->time(),
                        )
                    )),
                    'time_create' => $this->time(),
                    'status' => 0,
                    'in_app' => 1
                );
				

				$Send = QueueModel::insertGetId($dataNoticeApp);
				
                if($Send){
                	$contents = array(
		                'error'     => false,
		                'message'   => 'Success!!'
		            );
                }else{
                	$contents = array(
		                'error'     => true,
		                'message'   => 'Not send!!'
		            );
                }
                

            }
            else {
				$contents = array(
			                'error'     => true,
			                'message'   => 'No device'
	            );
			}

			
			return Response::json($contents);
	}








}
?>