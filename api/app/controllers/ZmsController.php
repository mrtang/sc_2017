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
        $TimeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',time())));
        $TimeEnd = strtotime(date('Y-m-d 00:00:00',time()));
        $Zms = new Zalo;
        
        $InfoUser = User::where('zms',0)->where('time_create','>',$TimeStart)->where('time_create','<',$TimeEnd)->first();
        //$InfoUser = User::where('id',81773)->where('zms',0)->first();
        //var_dump($InfoUser);die;
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
            $Template = TemplateModel::where('id',42)->first();
            //log zms
            $DataLog = array(
                'scenario_id' => 666,
                'template_id' => 70,
                'transport_id' => 7,
                'user_id'   => $InfoUser['id'],
                'received'  => $InfoUser['phone'],
                'data'      => json_encode(array('content' => $Template['content'])),
                'time_create' => time(),
                'status'   => 1,
                'time_success' => time() + 360
            );
            $InsertLog =  QueueModel::insert($DataLog);
            //send notice app
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
                                    'meta' => '4Rosx9WlH9I'
                                )
                            )
                        ),
                        'time_create' => time(),
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
                                    'meta' => '4Rosx9WlH9I'
                                )
                            )
                        ),
                        'time_create' => time(),
                        'in_app' => 1
                    );
                }
                if(!empty($dataNoticeApp)){
                    QueueModel::insert($dataNoticeApp);
                }
            }
            $SendZms = $Zms->newRegis($Phone);
            //$SendZms = $Zms->newRegis(84976395263);
            if($SendZms >= 0){
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
        $TimeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-2 day',time())));
        $Zms = new Zalo;

        $Data = CustomerAdminModel::where('first_order_time','>',0)->where('first_accept_order_time','>',0)->where('zms_first_accept',0)->first();
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
            $Template = TemplateModel::where('id',44)->first();
            //log zms
            $DataLog = array(
                'scenario_id' => 666,
                'template_id' => 71,
                'transport_id' => 7,
                'user_id'   => $InfoUser['id'],
                'received'  => $InfoUser['phone'],
                'data'      => json_encode(array('content' => $Template['content'])),
                'time_create' => time(),
                'status'   => 1,
                'time_success' => time() + 360
            );
            $InsertLog =  QueueModel::insert($DataLog);
            //send notice app
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
                                    'meta' => 'https://www.shipchung.vn/wp-content/uploads/2014/08/all.jpg',
                                    'link' => 'https://www.shipchung.vn/cach-dong-goi-hang-khi-chuyen-phat-nhanh/'
                                )
                            )
                        ),
                        'time_create' => time(),
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
                                    'meta' => 'https://www.shipchung.vn/wp-content/uploads/2014/08/all.jpg',
                                    'link' => 'https://www.shipchung.vn/cach-dong-goi-hang-khi-chuyen-phat-nhanh/'
                                )
                            )
                        ),
                        'time_create' => time(),
                        'in_app' => 1
                    );
                }
                if(!empty($dataNoticeApp)){
                    QueueModel::insert($dataNoticeApp);
                }
            }
            $SendZms = $Zms->acceptFirst($DataZms);
            if($SendZms >= 0){
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
        $TimeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-3 day',time())));
        $Cond = time() - 7200;
        $Zms = new Zalo;

        $Data = CustomerAdminModel::where('time_create','>',$TimeStart)->where('first_order_time','<',$Cond)->where('first_order_time','>',0)->where('first_accept_order_time',0)->where('zms_first_accept',0)->where('zms_not_accept',0)->first();
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
            $Template = TemplateModel::where('id',43)->first();
            //log zms
            $DataLog = array(
                'scenario_id' => 666,
                'template_id' => 72,
                'transport_id' => 7,
                'user_id'   => $InfoUser['id'],
                'received'  => $InfoUser['phone'],
                'data'      => json_encode(array('content' => $Template['content'])),
                'time_create' => time(),
                'status'   => 1,
                'time_success' => time() + 360
            );
            $InsertLog =  QueueModel::insert($DataLog);
            //send notice app
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
                        'time_create' => time(),
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
                        'time_create' => time(),
                        'in_app' => 1
                    );
                }
                if(!empty($dataNoticeApp)){
                    QueueModel::insert($dataNoticeApp);
                }
            }
            $SendZms = $Zms->notAccept($DataZms);
            if($SendZms >= 0){
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
        $TimeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-30 day',time())));
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
            $Template = TemplateModel::where('id',48)->first();
            //log zms
            $DataLog = array(
                'scenario_id' => 666,
                'template_id' => 73,
                'transport_id' => 7,
                'user_id'   => $InfoUser['id'],
                'received'  => $InfoUser['phone'],
                'data'      => json_encode(array('content' => $Template['content'])),
                'time_create' => time(),
                'status'   => 1,
                'time_success' => time() + 360
            );
            $InsertLog =  QueueModel::insert($DataLog);
            //send notice app
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
                                    'type' => 'order_status',
                                    'message' => 'Đơn hàng '.$Data['first_success_tracking_code'].' đã giao thành công',
                                    'title' => 'Đơn hàng '.$Data['first_success_tracking_code'].' đã giao thành công',
                                    'content' => $Template['content'],
                                    'time_update' => $Data['first_success_order_time']
                                )
                            )
                        ),
                        'time_create' => time(),
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
                                    'type' => 'order_status',
                                    'message' => 'Đơn hàng '.$Data['first_success_tracking_code'].' đã giao thành công',
                                    'title' => 'Đơn hàng '.$Data['first_success_tracking_code'].' đã giao thành công',
                                    'content' => $Template['content'],
                                    'time_update' => $Data['first_success_order_time']
                                )
                            )
                        ),
                        'time_create' => time(),
                        'in_app' => 1
                    );
                }
                if(!empty($dataNoticeApp)){
                    QueueModel::insert($dataNoticeApp);
                }
            }
            $SendZms = $Zms->deliveredFirst($DataZms);
            if($SendZms >= 0){
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
	public function SendZms($dataSend){
		$Zms = new Zalo;
        if(!empty($dataSend)){
            $Queue = QueueModel::where('id',$dataSend['id'])->where('transport_id',7)->where('status',0)->first();    
        }else{
            $Queue = QueueModel::where('transport_id',7)->where('status',0)->first();
        }
		if(!empty($Queue)){
			$Data = json_decode($Queue['data'],1);
			$DataZms = array(
				'phone' => $this->convertPhone($Queue['received']),
				'templateId' => $Data['template_id'],
				'data'	=> $Data['data']
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


	private function InsertQueue ($template,$scenario, $user_id, $received, $data){
		$Queue = new \QueueModel;
		$Queue->scenario_id     = $scenario;
		$Queue->template_id     = $template;
		$Queue->transport_id    = 7;
		$Queue->user_id         = $user_id;
		$Queue->received        = $received;
		$Queue->data            = json_encode($data, JSON_UNESCAPED_UNICODE);
		$Queue->time_create     = time();
		
		try{
			$Queue->save();
			$this->PushRabbitMQ('SendZalo', ['id' => $Queue->id]);
		}catch(\Exception $e){
			var_dump($e->getMessage());
			return;
		}
	}

	public function __order_detail($lading_id){
		return \ordermodel\DetailModel::where('order_id', $lading_id)->first();
	}
	// Zalo SMS khi giao thất bại

	public function ZaloDeliveryFail($Lading, $newStatus = 0){
			$zalo_data              = [];
			$OrderPostOfficeModel   = new \ordermodel\PostOfficeModel;
			$OrderPostOffice        = $OrderPostOfficeModel->where('order_id', $Lading->id)->where('courier_id', 1)->orderBy('time_create', 'DESC')->first();
			$OrderStatus            = $this->getStatus();

			$zalo_data['template_id']               = "179976734a36a368fa27";
			$zalo_data['data']['tracking_code']     = $Lading->tracking_code;
			$zalo_data['data']['item_name']         = $Lading->product_name;
			$zalo_data['data']['failed_reason']     = !empty($OrderStatus[$newStatus]) ? $OrderStatus[$newStatus] : "lý do khác";
			
			if (!empty($OrderPostOffice->id)) {
				$CourierPostOfficeModel = new \CourierPostOfficeModel;
				$Postoffice = $CourierPostOfficeModel->where('bccode', $OrderPostOffice->to_postoffice_code )->first();

				if (!empty($Postoffice)) {
					$zalo_data['template_id']               = "98b7c75dfb1812464b09";
					$zalo_data['data']['post_office_name']  = $Postoffice->name;
					$zalo_data['data']['post_office_phone'] = $Postoffice->phone;
				}
			}

			$this->InsertQueue(59,35, $Lading->from_user_id, $Lading->to_phone, $zalo_data);
		return;
	}


	// Zalo SMS khi giao  chậm
	public function ZaloSlowDelivery($Lading, $delay_days){
			$zalo_data              = [];
			$OrderPostOfficeModel   = new \ordermodel\PostOfficeModel;
			$OrderPostOffice        = $OrderPostOfficeModel->where('order_id', $Lading->id)->where('courier_id', 1)->orderBy('time_create', 'DESC')->first();
			//$OrderStatus            = $this->getStatus();

			$zalo_data['template_id']               = "240a46e07aa593fbcab4";
			$zalo_data['data']['tracking_code']     = $Lading->tracking_code;
			$zalo_data['data']['item_name']         = $Lading->product_name;
			$zalo_data['data']['delay_days']        = $delay_days;
			
			
			
			if (!empty($OrderPostOffice->id)) {
				$CourierPostOfficeModel = new \CourierPostOfficeModel;
				$Postoffice = $CourierPostOfficeModel->where('bccode', $OrderPostOffice->to_postoffice_code )->first();

				if (!empty($Postoffice)) {
					$zalo_data['template_id']               = "86a2de48e20d0b53521c";
					$zalo_data['data']['post_office_name']  = $Postoffice->name;
					$zalo_data['data']['post_office_phone'] = $Postoffice->phone;
				}

				
			}

			$this->InsertQueue(60,33, $Lading->from_user_id, $Lading->to_phone, $zalo_data);
		return;
	}

	// Zalo SMS khi giao đi phát
	public function ZaloDelivering($Lading, $newStatus = 0){

			
		
			$zalo_data              = [];
			$OrderPostOfficeModel   = new \ordermodel\PostOfficeModel;
			$OrderPostOffice        = $OrderPostOfficeModel->where('order_id', $Lading->id)->where('courier_id', 1)->orderBy('time_create', 'DESC')->first();
			//$OrderStatus            = $this->getStatus();

			$zalo_data['template_id']               = "89e1e90bd54e3c10655f";
			$zalo_data['data']['tracking_code']     = $Lading->tracking_code;
			$zalo_data['data']['item_name']         = $Lading->product_name;
			
			
			if (!empty($OrderPostOffice->id)) {
				$CourierPostOfficeModel = new \CourierPostOfficeModel;
				$Postoffice = $CourierPostOfficeModel->where('bccode', $OrderPostOffice->to_postoffice_code )->first();

				if (!empty($Postoffice)) {
					$zalo_data['template_id']               = "48a6164c2a09c3579a18";
					$zalo_data['data']['post_office_name']  = $Postoffice->name;
					$zalo_data['data']['post_office_phone'] = $Postoffice->phone;
				}

				
			}

			$this->InsertQueue(61,36, $Lading->from_user_id, $Lading->to_phone,$zalo_data);
		return;
	}


	// Zalo SMS khi đã lấy hàng
	public function ZaloOrderPicked($Lading, $newStatus = 0){
            try {
                $OrderDetail = $this->__order_detail($Lading->id);
            } catch (Exception $e) {
                
            }
			
			if (empty($OrderDetail)) {
				return false;
			}
			$zalo_data              = [];
			$zalo_data['template_id']               = "e3ff8015bc50550e0c41";
			$zalo_data['data']['tracking_code']     = $Lading->tracking_code;
			$zalo_data['data']['item_name']         = $Lading->product_name;

			if($Lading->estimate_delivery > 24){
				$leatime_str = ceil($Lading->estimate_delivery / 24)." ngày";
			}else {
				$leatime_str = $Lading->estimate_delivery." giờ";
			}

			$User = \User::where('id', $Lading->from_user_id)->first();
			if(empty($User)){
				return;
			}
			$zalo_data['data']['customer_name']     = $User->fullname;
			$zalo_data['data']['remain_days']       = $leatime_str;
			$zalo_data['data']['cod_amount']        = number_format($OrderDetail->money_collect);



			$this->InsertQueue(62,34, $Lading->from_user_id, $Lading->to_phone, $zalo_data);

		return;
	}


	
	// Zalo SMS khi giao thành công 
	public function ZaloDeliverySuccess($Lading, $newStatus = 0){
			$zalo_data['template_id']               = "9e0ef9e4c5a12cff75b0";
			$zalo_data['data']['tracking_code']     = $Lading->tracking_code;
			$zalo_data['data']['item_name']         = $Lading->product_name;
			
			
			$User = \User::where('id', $Lading->from_user_id)->first();
			if(empty($User)){
				return;
			}
			$zalo_data['data']['customer_name']     = $User->fullname;


			$this->InsertQueue(63,32, $Lading->from_user_id, $Lading->to_phone, $zalo_data);
	   return;
	}

    //Zalo Cần xử lý - Phát thất bại - người bán gửi zalo cho người mua
    public function ZaloProcessDeliveryFail($FromUser, $ToPhone, $TrackingCode, $ItemName, $DeliverName, $DeliverPhone){
        $zalo_data              = [];

        $zalo_data['template_id']               = "951612fd2eb8c7e69ea9";
        $zalo_data['data']['tracking_code']     = $TrackingCode;
        $zalo_data['data']['item_name']         = $ItemName;

        if (!empty($DeliverName)) {
            $zalo_data['template_id']               = "7bbffd54c111284f7100";
            $zalo_data['data']['deliver_name']      = $DeliverName;
            $zalo_data['data']['deliver_phone_num'] = $DeliverPhone;
        }

        $this->InsertQueue(76,47, $FromUser, $ToPhone, $zalo_data);
        return;
    }

	private function updateOrderSlowDelivery($order_id, $data){
        $LMongo     = new LMongo;
        return $LMongo::collection('log_journey_delivery')->where('order_id', $order_id)->update($data);
    }

	public function getCronSendZaloSlowDelivery (){
        $LMongo     = new LMongo;
        $LMongo     = $LMongo::collection('log_journey_delivery')->where('active',1)->whereGte('time_accept', time()  - 86400*15)->whereNe('send_zms', 1)->whereNe('send_zms', 3);

        $RangeDeliveryStart = 1*86400;
        $RangeDeliveryEnd   = 2*86400;

        $GROUP = [
            'DELIVERING' => [40],
            'DELIVERING_AND_FAILS' => [54,55,56,57,58,59,77,50,51,76,79,80,81],
        ];

        $LMongo = $LMongo->whereIn('status', array_merge($GROUP['DELIVERING'], $GROUP['DELIVERING_AND_FAILS']));

        $LMongo = $LMongo->where(function($query) use($RangeDeliveryStart, $RangeDeliveryEnd){
            $query->where(function($q) use($RangeDeliveryStart, $RangeDeliveryEnd){
                $q->where(function($p) use($RangeDeliveryStart, $RangeDeliveryEnd){
                    $p->whereExists('first_slow')->whereGt('first_slow',$RangeDeliveryStart);
                    if(!empty($RangeDeliveryEnd)) $p->whereLte('first_slow', $RangeDeliveryEnd);
                })->orWhere(function($p) use($RangeDeliveryStart, $RangeDeliveryEnd){
                    $p->where('first_slow', null)->whereLt('first_promise_time',time() - $RangeDeliveryStart);
                    if(!empty($RangeDeliveryEnd)){
                        $p->whereGte('first_promise_time',time() - $RangeDeliveryEnd);
                    }else{
                        $p->whereGt('first_promise_time',0);
                    }
                });
            })->orWhere(function($q) use($RangeDeliveryStart, $RangeDeliveryEnd){
                $q->where(function($p) use($RangeDeliveryStart, $RangeDeliveryEnd){
                    $p->whereExists('second_slow')->whereGt('second_slow',$RangeDeliveryStart);
                    if(!empty($RangeDeliveryEnd)) $p->whereLte('second_slow', $RangeDeliveryEnd);
                })->orWhere(function($p) use($RangeDeliveryStart, $RangeDeliveryEnd){
                    $p->where('second_slow', null)->whereLt('second_promise_time',time() - $RangeDeliveryStart);
                    if(!empty($RangeDeliveryEnd)){
                        $p->whereGte('second_promise_time',time() - $RangeDeliveryEnd);
                    }else{
                        $p->whereGt('second_promise_time',0);
                    }
                });
            })->orWhere(function($q) use($RangeDeliveryStart, $RangeDeliveryEnd){
                $q->where(function($p) use($RangeDeliveryStart, $RangeDeliveryEnd){
                    $p->whereExists('third_slow')->whereGt('third_slow',$RangeDeliveryStart);
                    if(!empty($RangeDeliveryEnd)) $p->whereLte('third_slow', $RangeDeliveryEnd);
                })->orWhere(function($p) use($RangeDeliveryStart, $RangeDeliveryEnd){
                    $p->where('third_slow', null)->whereLt('third_promise_time',time() - $RangeDeliveryStart);
                    if(!empty($RangeDeliveryEnd)){
                        $p->whereGte('third_promise_time',time() - $RangeDeliveryEnd);
                    }else{
                        $p->whereGt('third_promise_time',0);
                    }
                });
            });
        });

        $Data = $LMongo->orderBy('time_accept','asc')->first();
        if (!empty($Data)) {
            $Model = new \ElasticBuilder('bxm_orders', 'orders');
            //$Model->where('id', 1789591);
            $Model->where('id', $Data['order_id']);
            $Order = $Model->get();

            if (!empty($Order)) {

                
                $delay_days = '1-2';
                if (in_array($Data['status'], $GROUP['DELIVERING'])) {
                    $delay_days = '2-3';
                }

                $AppNotice = new \AppNotificationController();

                if (!empty($Data['third_slow']) && $Data['third_slow'] >= 86400) {
                    $this->ZaloSlowDelivery((object)$Order[0], $delay_days);

                    $AppNotice->SendNoticeSlowDelivery($Order[0], $delay_days);

                    $this->updateOrderSlowDelivery($Data['order_id'], ['send_zms' => 1]);

                }elseif(!empty($Data['second_slow']) && $Data['second_slow'] >= 86400){
                    $this->ZaloSlowDelivery((object)$Order[0], $delay_days);

                    $AppNotice->SendNoticeSlowDelivery($Order[0], $delay_days);

                    $this->updateOrderSlowDelivery($Data['order_id'], ['send_zms' => 1]);

                }else if(!empty($Data['first_slow']) && $Data['first_slow'] >= 86400){
                    $this->ZaloSlowDelivery((object)$Order[0], $delay_days);
                    
                    $AppNotice->SendNoticeSlowDelivery($Order[0], $delay_days);

                    $this->updateOrderSlowDelivery($Data['order_id'], ['send_zms' => 1]);

                }else {
                    $this->updateOrderSlowDelivery($Data['order_id'], ['send_zms' => 3]);
                }
                var_dump($delay_days);
            }
        }
        
        return Response::json($Data);

    }

    //send test
    public function getSendtest(){
        $Zms = new Zalo; 
        $test = $Zms->sendnottemplate();
        var_dump($test);die;
    }

    public function getSendzms($Id){
        $Zms = new Zalo;
        $Queue = QueueModel::where('id',$Id)->where('transport_id',7)->where('status',0)->first();
        if(!empty($Queue)){
            $Data = json_decode($Queue['data'],1);
            $DataZms = array(
                'phone' => $this->convertPhone($Queue['received']),
                'templateId' => $Data['template_id'],
                'data'  => $Data['content']
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