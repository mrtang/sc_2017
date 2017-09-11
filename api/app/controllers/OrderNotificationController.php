<?php
use ordermodel\OrdersModel;
use ordermodel\DetailModel;
use metadatamodel\OrderStatusModel;
use omsmodel\NotifyOrderModel;
class OrderNotificationController extends \BaseController {
    private $domain = '*';
    /**
     * Display a listing of the resource.
     *
     * @return Response
     * @params $scenario, $user_id
     */
     
    public function __construct(){
        
    }

    private function getListuser(){
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',time())));
        $timeEnd = strtotime(date('Y-m-d 00:00:00',time()));
        $listUser = OrdersModel::where('time_create','>',$timeStart)->where('time_create','<',$timeEnd)->orWhere('time_accept','>',$timeStart)->orWhere('time_accept','<',$timeEnd)->groupBy('from_user_id')->get(array('id','from_user_id'))->toArray();
        $listUserId = array();
        if(!empty($listUser)){
            foreach($listUser AS $one){
                if((int)$one['from_user_id'] > 0){
                    $listUserId[] = (int)$one['from_user_id'];
                }
            }
        }
        return $listUserId;
    }

    //user fail
    private function getListuserdeliveryfail(){
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',time())));
        $timeEnd = strtotime(date('Y-m-d 00:00:00',time()));

        $status = array(52,53,54,55,56,57,58,59,60,61);
        $listUser = OrdersModel::where("time_accept",">",time() - 30 * 86400)->where('time_update','>',$timeStart)->where('time_update','<',$timeEnd)->whereIn('status',$status)->groupBy('from_user_id')->get(array('id','from_user_id'))->toArray();
        $listUserId = array();
        if(!empty($listUser)){
            foreach($listUser AS $one){
                if((int)$one['from_user_id'] > 0){
                    $listUserId[] = (int)$one['from_user_id'];
                }
            }
        }
        return $listUserId;
    }
    //user over weight
    private function getListuseroverweight(){
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',time())));
        $timeEnd = strtotime(date('Y-m-d 00:00:00',time()));
        $listUser = OrdersModel::where('time_update','>',$timeStart)->where('time_update','<',$timeEnd)->groupBy('from_user_id')->get(array('id','from_user_id'))->toArray();
        $listUserId = array();
        if(!empty($listUser)){
            foreach($listUser AS $one){
                if((int)$one['from_user_id'] > 0){
                    $listUserId[] = (int)$one['from_user_id'];
                }
            }
        }
        return $listUserId;
    }

    private function getListtemplate($scenario_id){
        $listLayout = ScenarioTemplateModel::where('scenario_id','=',$scenario_id)->get(array('template_id'))->toArray();
        $dataReturn = array();
        if(!empty($listLayout)){
            foreach($listLayout AS $val){
                $dataReturn[] = $val['template_id'];
            }
        }
        return $dataReturn;
    }

    private function getInfonotify($scenario_id,$list_user_id){
        $dataReturn = array();
    
        $listTransport = UserScenarioConfigModel::whereIn('user_id',$list_user_id)->where('scenario_id',$scenario_id)->orderBy('user_id','DESC')->get(array('id','transport_id','scenario_id','user_id'))->toArray();
        if(!empty($listTransport)){
            $listUserConfigId = array();
            foreach($listTransport AS $one){
                $listUserConfigId[] = $one['user_id'];
            }
            $listReceived = UserConfigTransportModel::whereIn('user_id',$listUserConfigId)->where('active','=',1)->get(array('id','user_id','transport_id','received'))->toArray();
            $dataReturn = $listReceived;
        }

        return $dataReturn;
    }

    private function getListorder($listStatus = array(),$listUserId = array()){
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',time())));
        $timeEnd = strtotime(date('Y-m-d 00:00:00',time()));
        $dataReturn = $dataBuild = $dataOrder = array();
        $OrdersList = new OrdersModel();
        if(!empty($listUserId)){
            $OrdersList = $OrdersList->whereIn('from_user_id',$listUserId);
            if(!empty($listStatus)){
                $OrdersList = $OrdersList->whereIn('status',$listStatus);
            }
                
            $listOrder = $OrdersList->where('time_create','>',$timeStart)->where('time_create','<',$timeEnd)->orWhere('time_accept','>',$timeStart)->orWhere('time_accept','<',$timeEnd)->where('notification',0)->get(array('id','from_user_id','tracking_code','time_create','status','courier_id'))->toArray();

            if(!empty($listOrder)){
                $count = 1;
                //
                $listStatus = OrderStatusModel::all(array('code','name'));
                if(!empty($listStatus)){
                    foreach($listStatus AS $one){
                        $LStatus[(int)$one['code']] = $one['name'];
                    }
                    foreach($listOrder AS $val){
                        if (isset($LStatus[(int)$val['status']])){
                            $val['status_name'] = $LStatus[(int)$val['status']];
                        }
                        $val['stt'] = $count++;
                        $val['time_create'] = date("d/m/Y H:m:s",$val['time_create']);
                        $dataBuild[] = $val;
                    }
                }
                //
                if (Cache::has('courier_cache')){
                    $listCourier    = Cache::get('courier_cache');
                }else{
                    $courier        = new CourierModel;
                    $listCourier    = $courier::all(array('id','name'));
                }
                if(!empty($listCourier)){
                    foreach($listCourier AS $val){
                        $LCourier[$val['id']]   = $val['name'];
                    }
                    foreach($dataBuild AS $value){
                        if (isset($LCourier[(int)$value['courier_id']])){
                            $value['courier_name'] = $LCourier[(int)$value['courier_id']];
                            $dataOrder[] = $value;
                        }
                    }
                }
                foreach($dataOrder AS $one){
                    if(in_array($one['from_user_id'], $listUserId)){
                        $dataReturn[$one['from_user_id']][] = (object)$one;
                    }
                }
            }
        }
        return $dataReturn;
    }

    //get Delivery fail
    private function getDeliveryfail($listStatus,$listUserId){
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',time())));
        $timeEnd = strtotime(date('Y-m-d 00:00:00',time()));
        $dataReturn = $dataBuild = $dataOrder = array();
        if(!empty($listUserId)){
            $listOrder = OrdersModel::whereIn('status',$listStatus)->whereIn('from_user_id',$listUserId)->where("time_accept",">",time() - 10 * 86400)->where('time_update','>',$timeStart)->where('time_update','<',$timeEnd)->get(array('id','from_user_id','tracking_code','time_accept','status','courier_id','time_pickup','time_update'))->toArray();
            if(!empty($listOrder)){
                $count = 1;
                //
                $listStatus = OrderStatusModel::all(array('code','name'));
                if(!empty($listStatus)){
                    foreach($listStatus AS $one){
                        $LStatus[(int)$one['code']] = $one['name'];
                    }
                    foreach($listOrder AS $val){
                        if (isset($LStatus[(int)$val['status']])){
                            $val['status_name'] = $LStatus[(int)$val['status']];
                        }
                        $val['stt'] = $count++;
                        $val['time_accept'] = date("d/m/Y H:m:s",$val['time_accept']);
                        $val['time_pickup'] = date("d/m/Y H:m:s",$val['time_pickup']);
                        $dataBuild[] = $val;
                    }
                }
                //
                if (Cache::has('courier_cache')){
                    $listCourier    = Cache::get('courier_cache');
                }else{
                    $courier        = new CourierModel;
                    $listCourier    = $courier::all(array('id','name'));
                }
                if(!empty($listCourier)){
                    foreach($listCourier AS $val){
                        $LCourier[$val['id']]   = $val['name'];
                    }
                    foreach($dataBuild AS $value){
                        if (isset($LCourier[(int)$value['courier_id']])){
                            $value['courier_name'] = $LCourier[(int)$value['courier_id']];
                            $dataOrder[] = $value;
                        }
                    }
                }
                foreach($dataOrder AS $one){
                    if(in_array($one['from_user_id'], $listUserId)){
                        $dataReturn[$one['from_user_id']][] = (object)$one;
                    }
                }
            }
        }
        return $dataReturn;
    }

    //
    private function getListladingoverweight($listUserId){
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',time())));
        $timeEnd = strtotime(date('Y-m-d 00:00:00',time()));
        $dataReturn = $listOrderDetail = $dataOrderDetail = $dataBuild = $dataOrder = array();
        if(!empty($listUserId)){
            $listOrder = OrdersModel::whereIn('from_user_id',$listUserId)->where('over_weight','>',0)->where("time_accept",">",time() - 3 * 86400)->where('time_update','>',$timeStart)->where('time_update','<',$timeEnd)->get(array('id','from_user_id','tracking_code','time_accept','status','courier_id','time_update'))->toArray();
            if(!empty($listOrder)){
                $count = 1;
                $listOrderId = array();
                //
                foreach($listOrder AS $one){
                    $listOrderId[] = $one['id'];
                }
                if(!empty($listOrderId)){
                    $listOrderDetail = DetailModel::whereIn('order_id',$listOrderId)->get(array('order_id','sc_pvk'))->toArray();
                }
                if(!empty($listOrderDetail)){
                    foreach($listOrderDetail AS $oneDetail){
                        foreach($listOrder AS $oneOrder){
                            if($oneOrder['id'] == $oneDetail['order_id']){
                                $oneOrder['pvk'] = $oneDetail['sc_pvk'];
                                $dataOrderDetail[] = $oneOrder;
                            }
                        }
                    }
                }
                $listStatus = OrderStatusModel::all(array('code','name'));
                if(!empty($listStatus)){
                    foreach($listStatus AS $one){
                        $LStatus[(int)$one['code']] = $one['name'];
                    }
                    foreach($dataOrderDetail AS $val){
                        if (isset($LStatus[(int)$val['status']])){
                            $val['status_name'] = $LStatus[(int)$val['status']];
                        }
                        $val['stt'] = $count++;
                        $val['time_update'] = date("d/m/Y H:m:s",$val['time_update']);
                        $dataBuild[] = $val;
                    }
                }
                //
                if (Cache::has('courier_cache')){
                    $listCourier    = Cache::get('courier_cache');
                }else{
                    $courier        = new CourierModel;
                    $listCourier    = $courier::all(array('id','name'));
                }
                if(!empty($listCourier)){
                    foreach($listCourier AS $val){
                        $LCourier[$val['id']]   = $val['name'];
                    }
                    foreach($dataBuild AS $value){
                        if (isset($LCourier[(int)$value['courier_id']])){
                            $value['courier_name'] = $LCourier[(int)$value['courier_id']];
                            $dataOrder[] = $value;
                        }
                    }
                }
                foreach($dataOrder AS $one){
                    if(in_array($one['from_user_id'], $listUserId)){
                        $dataReturn[$one['from_user_id']][] = (object)$one;
                    }
                }
            }
        }
        return $dataReturn;
    }

    ////insert queue
    public function getSendnotifywhencreateorder(){
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',time())));
        $timeEnd = strtotime(date('Y-m-d 00:00:00',time()));
        $scenario_id = 12;
        //$status = array(20,21);
        $listUser = $this->getListuser();
        
        $dataInsert = $dataBuild = $listUserInfo = $listOrderId = $listFail = $listSuccess = array();
        
        if(!empty($listUser)){
            $dataOrder = $this->getListorder(array(),$listUser);
            if(!empty($dataOrder)){
                //get template
                $listTemplate = $this->getListtemplate($scenario_id);
                $dataTemplate = TemplateModel::whereIn('id',$listTemplate)->get(array('id','transport_id'))->toArray();
                if(empty($dataTemplate)){
                    $statusCode = 500;
                    $contents = array(
                        'error'         => true,
                        'message'       => 'Not template!!!',
                    );
                }
                //
                $pickupFail = OrdersModel::where('time_create','>',$timeStart)->where('time_create','<',$timeEnd)->where('time_pickup',0)->groupBy('from_user_id')->get(array('from_user_id',DB::raw('count(*) as count')));
                if(!empty($pickupFail)){
                    foreach($pickupFail AS $fail){
                        $listFail[$fail['from_user_id']] = $fail['count'];
                    }
                }
                $pickupSuccess = OrdersModel::where('time_create','>',$timeStart)->where('time_create','<',$timeEnd)->where('time_pickup','>',0)->groupBy('from_user_id')->get(array('from_user_id',DB::raw('count(*) as count')));
                if(!empty($pickupSuccess)){
                    foreach($pickupSuccess AS $success){
                        $listSuccess[$success['from_user_id']] = $fail['count'];
                    }
                }
                
                $listUserInfo = User::whereIn('id',$listUser)->get(array('id','fullname','email'))->toArray();
                foreach($dataOrder AS $key => $lading){
                    $dataBuild[$key]['count'] = count($lading);
                    $dataBuild[$key]['count_fail'] = !empty($listFail[$key]) ? $listFail[$key] : 0;
                    $dataBuild[$key]['count_success'] = !empty($listSuccess[$key]) ? $listSuccess[$key] : 0;
                    $dataBuild[$key]['ladings'] = $lading;
                    foreach($lading AS $item){
                        $listOrderId[] = $item->id;
                    }
                }
                
                foreach($listUserInfo AS $oneUser){
                    $dataBuild[$oneUser['id']]['fullname'] = $oneUser['fullname'];
                    $dataBuild[$oneUser['id']]['email'] = $oneUser['email'];
                }
                
                if(!empty($dataBuild)){
                    foreach($dataBuild AS $key => $value){
                        if(!empty($value['ladings'])){
                            $dataInsert[] = array(
                                'scenario_id' => $scenario_id,
                                'template_id' => $dataTemplate[0]['id'],
                                'transport_id' => 2,
                                'user_id'   => $key,
                                'received'  => $value['email'],
                                'data'      => json_encode($value),
                                'time_create' => time()
                            );
                        }
                    }
                    $insert = QueueModel::insert($dataInsert);
                    if($insert == true){
                        $update = OrdersModel::whereIn('id',$listOrderId)->update(array('notification' => 1));
                        $contents = array(
                            'error'         => false,
                            'message'       => 'Success',
                        );
                    }else{
                        $contents = array(
                            'error'         => true,
                            'message'       => 'Not send notification',
                        );
                    }
                }else{
                    $contents = array(
                        'error'         => true,
                        'message'       => 'Not order!',
                    );
                }
            }else{
                $contents = array(
                    'error'         => true,
                    'message'       => 'Not order!',
                );
            }
        }else{
            $contents = array(
                'error'         => true,
                'message'       => 'Not data!!!',
            );
        }
        return Response::json($contents);
    }
    
    //delivery fail
    public function getSendnotifywhendeliveryfail(){
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',time())));
        $timeEnd = strtotime(date('Y-m-d 00:00:00',time()));
        // $timeStart = strtotime(date('Y-m-d 00:00:00',time()));
        // $timeEnd = strtotime(date('Y-m-d 10:00:00',time()));
        //check send
        $checkSend = NotifyOrderModel::where('type','delivery')->where('time_start',$timeStart)->where('time_end',$timeEnd)->first();
        if(!empty($checkSend)){
            $contents = array(
                'error'         => true,
                'message'       => 'Not send 2nd!!',
            );
        }else{
            $scenario_id = 14;
            $status = array(52,53,54,55,56,57,58,59,60,61);
            $listUser = $this->getListuserdeliveryfail();

            $dataInsert = $dataBuild = $listFail = $listSuccess = $listReturn = $listUserInfo = array();
            if(!empty($listUser)){
                $dataOrder = $this->getDeliveryfail($status,$listUser);
                $deliveryFail = OrdersModel::whereIn('status',[54,55,56,57,58,59])->where("time_accept",">",time() - 30 * 86400)->where('time_update','>',$timeStart)->where('time_update','<',$timeEnd)->groupBy('from_user_id')->get(array('from_user_id',DB::raw('count(*) as count')));
                if(!empty($deliveryFail)){
                    foreach($deliveryFail AS $fail){
                        $listFail[$fail['from_user_id']] = $fail['count'];
                    }
                }
                $deliverySuccess = OrdersModel::whereIn('status',[52,53])->where("time_accept",">",time() - 30 * 86400)->where('time_update','>',$timeStart)->where('time_update','<',$timeEnd)->groupBy('from_user_id')->get(array('from_user_id',DB::raw('count(*) as count')));
                if(!empty($deliverySuccess)){
                    foreach($deliverySuccess AS $success){
                        $listSuccess[$success['from_user_id']] = $success['count'];
                    }
                }
                $return = OrdersModel::whereIn('status',[60,61])->where("time_accept",">",time() - 30 * 86400)->where('time_update','>',$timeStart)->where('time_update','<',$timeEnd)->groupBy('from_user_id')->get(array('from_user_id',DB::raw('count(*) as count')));
                if(!empty($return)){
                    foreach($return AS $one){
                        $listReturn[$one['from_user_id']] = $one['count'];
                    }
                }
                
                if(!empty($dataOrder)){
                    //get template
                    $listTemplate = $this->getListtemplate($scenario_id);
                    $dataTemplate = TemplateModel::whereIn('id',$listTemplate)->get(array('id','transport_id'))->toArray();
                    if(empty($dataTemplate)){
                        $statusCode = 500;
                        $contents = array(
                            'error'         => true,
                            'message'       => 'Not template!!!',
                        );
                    }
                    $listUserInfo = User::whereIn('id',$listUser)->get(array('id','fullname','email'))->toArray();
                    foreach($dataOrder AS $key => $lading){
                        $dataBuild[$key]['count_fail'] = !empty($listFail[$key]) ? $listFail[$key] : 0;
                        $dataBuild[$key]['count_success'] = !empty($listSuccess[$key]) ? $listSuccess[$key] : 0;
                        $dataBuild[$key]['count_return'] = !empty($listReturn[$key]) ? $listReturn[$key] : 0;
                        $dataBuild[$key]['ladings'] = $lading;
                    }
                    
                    foreach($listUserInfo AS $oneUser){
                        $dataBuild[$oneUser['id']]['fullname'] = $oneUser['fullname'];
                        $dataBuild[$oneUser['id']]['email'] = $oneUser['email'];
                    }
                    
                    if(!empty($dataBuild)){
                        foreach($dataBuild AS $key => $value){
                            $dataInsert[] = array(
                                'scenario_id' => $scenario_id,
                                'template_id' => $dataTemplate[0]['id'],
                                'transport_id' => 2,
                                'user_id'   => $key,
                                'received'  => $value['email'],
                                'data'      => json_encode($value),
                                'time_create' => time()
                            );
                        }
                        //var_dump($dataInsert);die;
                        $insert = QueueModel::insert($dataInsert);
                        if($insert == true){
                            NotifyOrderModel::insert(array('type' => 'delivery','time_start' => $timeStart,'time_end' => $timeEnd,'time_send' => time()));
                            $contents = array(
                                'error'         => false,
                                'message'       => 'Success',
                            );
                        }else{
                            $contents = array(
                                'error'         => true,
                                'message'       => 'Not send notification',
                            );
                        }
                    }else{
                        $contents = array(
                            'error'         => true,
                            'message'       => 'Not order!',
                        );
                    }
                }else{
                    $contents = array(
                        'error'         => true,
                        'message'       => 'Not order!',
                    );
                }
            }else{
                $contents = array(
                    'error'         => true,
                    'message'       => 'Not data!!!',
                );
            }
        }
        return Response::json($contents);
    }

    //van don vuot kg
    public function getOrderoverweight(){
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',time())));
        $timeEnd = strtotime(date('Y-m-d 00:00:00',time()));
        //check send
        $checkSend = NotifyOrderModel::where('type','weight')->where('time_start',$timeStart)->where('time_end',$timeEnd)->first();
        if(!empty($checkSend)){
            $contents = array(
                'error'         => true,
                'message'       => 'Not send 2nd!!',
            );
        }else{
            $scenario_id = 21;
            $dataInsert = $dataBuild = $listUserInfo = array();

            $listUser = $this->getListuseroverweight();
            if(!empty($listUser)){
                $dataOrder = $this->getListladingoverweight($listUser);
                if(!empty($dataOrder)){
                    //get template
                    $listTemplate = $this->getListtemplate($scenario_id);
                    $dataTemplate = TemplateModel::whereIn('id',$listTemplate)->get(array('id','transport_id'))->toArray();
                    if(empty($dataTemplate)){
                        $contents = array(
                            'error'         => true,
                            'message'       => 'Not template!!!',
                        );
                    }
                    //
                    $listUserInfo = User::whereIn('id',$listUser)->get(array('id','fullname','email'))->toArray();
                    foreach($dataOrder AS $key => $lading){
                        $dataBuild[$key]['count'] = count($lading);
                        $dataBuild[$key]['ladings'] = $lading;
                    }
                    
                    foreach($listUserInfo AS $oneUser){
                        $dataBuild[$oneUser['id']]['fullname'] = $oneUser['fullname'];
                        $dataBuild[$oneUser['id']]['email'] = $oneUser['email'];
                    }
                    if(!empty($dataBuild)){
                        foreach($dataBuild AS $key => $value){
                            if(!empty($value['ladings'])){
                                $dataInsert[] = array(
                                    'scenario_id' => $scenario_id,
                                    'template_id' => $dataTemplate[0]['id'],
                                    'transport_id' => 2,
                                    'user_id'   => $key,
                                    'received'  => $value['email'],
                                    'data'      => json_encode($value),
                                    'time_create' => time()
                                );
                            }
                        }
                        $insert = QueueModel::insert($dataInsert);
                        if($insert == true){
                            NotifyOrderModel::insert(array('type' => 'weight','time_start' => $timeStart,'time_end' => $timeEnd,'time_send' => time()));
                            $contents = array(
                                'error'         => false,
                                'message'       => 'Success',
                            );
                        }else{
                            $contents = array(
                                'error'         => true,
                                'message'       => 'Not send notification',
                            );
                        }
                    }else{
                        $contents = array(
                            'error'         => true,
                            'message'       => 'Not lading over weight!',
                        );
                    }
                }else{
                    $contents = array(
                        'error'         => true,
                        'message'       => 'Not lading over weight!!!',
                    );
                }
            }else{
                $contents = array(
                    'error'         => true,
                    'message'       => 'Not data over weight!!!',
                );
            }
        }
        return Response::json($contents);
    }

    //create email order in day 
    public function getEmailorderinday(){
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',time())));
        $timeEnd = strtotime(date('Y-m-d 00:00:00',time()));
        $count = 1;
        $dataInsert = $dataBuild = $objOrder = $listOrderId = array();
        $countCreateSuccess = $countPSucess = $countPFail = $countOverW = $countDFail = $countReturn = 0;

        $order = OrdersModel::where('time_create','>',$timeStart)->where('time_create','<',$timeEnd)->where('notification',0)->take(1)->get(array('id','from_user_id','tracking_code','time_create','status','courier_id','time_update','over_weight','notification'))->toArray();
        //var_dump($order);
        if(!empty($order)){
            $infoUser = User::where('id',$order[0]['from_user_id'])->first();
            //get email notification
            $infoNotice = UserConfigTransportModel::where('user_id',$infoUser['id'])->where('transport_id',2)->first();
            if(!empty($infoNotice) && $infoNotice['received'] != ''){
                $emailReceived = $infoNotice['received'];
            }else{
                $emailReceived = $infoUser['email'];
            }
            $listOrderFUser = OrdersModel::where('time_create','>',$timeStart)->where('time_create','<',$timeEnd)->where('from_user_id',$infoUser['id'])->where('notification',0)->get(array('id','from_user_id','tracking_code','time_create','status','courier_id','time_update','over_weight','notification'))->toArray();
            //var_dump($listOrderFUser);die;
            if(!empty($listOrderFUser)){
                $listOrderUser = array_merge($order,$listOrderFUser);
                $listStatus = OrderStatusModel::all(array('code','name'));
                if(!empty($listStatus)){
                    foreach($listStatus AS $one){
                        $LStatus[(int)$one['code']] = $one['name'];
                    }
                    if (Cache::has('courier_cache')){
                        $listCourier    = Cache::get('courier_cache');
                    }else{
                        $courier        = new CourierModel;
                        $listCourier    = $courier::all(array('id','name'));
                    }
                    if(!empty($listCourier)){
                        foreach($listCourier AS $val){
                            $LCourier[$val['id']]   = $val['name'];
                        }
                    }
                    foreach($listOrderUser AS $val){
                        if (isset($LStatus[(int)$val['status']])){
                            $val['status_name'] = $LStatus[(int)$val['status']];
                        }
                        if (isset($LCourier[(int)$val['courier_id']])){
                            $val['courier_name'] = $LCourier[(int)$val['courier_id']];
                        }
                        $val['stt'] = $count++;
                        $val['time_update'] = date("d/m/Y H:m:s",$val['time_update']);
                        $dataBuild[] = $val;
                        //
                        if(in_array($val['status'], array(20,21,30,35,36),true)){
                            $countCreateSuccess = $countCreateSuccess + 1;
                        }
                        if($val['status'] == 36){
                            $countPSucess = $countPSucess + 1;
                        }
                        if(in_array($val['status'], array(31,32,33,34),true)){
                            $countPFail = $countPFail + 1;
                        }
                        if(abs($val['over_weight']) > 350){
                            $countOverW = $countOverW + 1;
                        }
                        if(in_array($val['status'], array(54,55,56,57,58,59),true)){
                            $countDFail = $countDFail + 1;
                        }
                        if($val['status'] == 60){
                            $countReturn = $countReturn + 1;
                        }
                        //
                        $listOrderId[] = $val['id'];
                    }
                }
                foreach($dataBuild AS $one){
                    $objOrder[] = (object)$one;
                }

                $content = array(
                    'fullname' => $infoUser['fullname'],
                    'today'    => date("d/m/Y",strtotime("-1 day")),
                    'create_success' => $countCreateSuccess,
                    'pickup_success' => $countPSucess,
                    'pickup_fail'    => $countPFail,
                    'over_weight'    => $countOverW,
                    'delivery_fail'  => $countDFail,
                    'return'         => $countReturn,
                    'ladings'  => $objOrder
                );

                $dataInsert = array(
                    'scenario_id' => 31,
                    'template_id' => 38,
                    'transport_id' => 2,
                    'user_id'   => $infoUser['id'],
                    'received'  => $emailReceived,
                    'data'      => json_encode($content),
                    'time_create' => time()
                );

                $insert = QueueModel::insertGetId($dataInsert);
                if($insert > 0){
                    $up = OrdersModel::whereIn('id',$listOrderId)->update(array('notification' => 1));
                    \Predis\Autoloader::register();
                    //Now we can start creating a redis client to publish event
                    $redis = new \Predis\Client(array(
                        "scheme" => "tcp",
                        "host" => "10.0.20.164",
                        "port" => 6788
                    ));
                    //Now we got redis client connected, we can publish event (send event)
                    $redis->publish("SendMail", $insert);
                    $contents = array(
                        'error'         => false,
                        'message'       => 'Sent success!!!',
                    );
                }else{
                    $contents = array(
                        'error'         => true,
                        'message'       => 'Not send mail!!!',
                    );
                }
            }else{
                $listStatus = OrderStatusModel::all(array('code','name'));
                if(!empty($listStatus)){
                    foreach($listStatus AS $one){
                        $LStatus[(int)$one['code']] = $one['name'];
                    }
                    if (Cache::has('courier_cache')){
                        $listCourier    = Cache::get('courier_cache');
                    }else{
                        $courier        = new CourierModel;
                        $listCourier    = $courier::all(array('id','name'));
                    }
                    if(!empty($listCourier)){
                        foreach($listCourier AS $val){
                            $LCourier[$val['id']]   = $val['name'];
                        }
                    }
                    foreach($order AS $val){
                        if (isset($LStatus[(int)$val['status']])){
                            $val['status_name'] = $LStatus[(int)$val['status']];
                        }
                        if (isset($LCourier[(int)$val['courier_id']])){
                            $val['courier_name'] = $LCourier[(int)$val['courier_id']];
                        }
                        $val['stt'] = $count++;
                        $val['time_update'] = date("d/m/Y H:m:s",$val['time_update']);
                        $dataBuild[] = $val;
                        //
                        if(in_array($val['status'], array(20,21,30,35,36),true)){
                            $countCreateSuccess = $countCreateSuccess + 1;
                        }
                        if($val['status'] == 36){
                            $countPSucess = $countPSucess + 1;
                        }
                        if(in_array($val['status'], array(31,32,33,34),true)){
                            $countPFail = $countPFail + 1;
                        }
                        if(abs($val['over_weight']) > 350){
                            $countOverW = $countOverW + 1;
                        }
                        if(in_array($val['status'], array(54,55,56,57,58,59),true)){
                            $countDFail = $countDFail + 1;
                        }
                        if($val['status'] == 60){
                            $countReturn = $countReturn + 1;
                        }
                        //
                        $listOrderId[] = $val['id'];
                    }
                }
                foreach($dataBuild AS $one){
                    $objOrder[] = (object)$one;
                }

                $content = array(
                    'fullname' => $infoUser['fullname'],
                    'today'    => date("d/m/Y",strtotime("-1 day")),
                    'create_success' => $countCreateSuccess,
                    'pickup_success' => $countPSucess,
                    'pickup_fail'    => $countPFail,
                    'over_weight'    => $countOverW,
                    'delivery_fail'  => $countDFail,
                    'return'         => $countReturn,
                    'ladings'  => $objOrder
                );
                $dataInsert = array(
                    'scenario_id' => 31,
                    'template_id' => 38,
                    'transport_id' => 2,
                    'user_id'   => $infoUser['id'],
                    'received'  => $emailReceived,
                    'data'      => json_encode($content),
                    'time_create' => time()
                );
                $insert = QueueModel::insertGetId($dataInsert);
                if($insert > 0){
                    $up = OrdersModel::where('id',$order[0]['id'])->update(array('notification' => 1));
                    \Predis\Autoloader::register();
                    //Now we can start creating a redis client to publish event
                    $redis = new \Predis\Client(array(
                        "scheme" => "tcp",
                        "host" => "10.0.20.164",
                        "port" => 6788
                    ));
                    //Now we got redis client connected, we can publish event (send event)
                    $redis->publish("SendMail", $insert);
                    $contents = array(
                        'error'         => false,
                        'message'       => 'Sent success!!!',
                    );
                }else{
                    $contents = array(
                        'error'         => true,
                        'message'       => 'Not send mail!!!',
                    );
                }
            }
        }else{
            $contents = array(
                'error'         => true,
                'message'       => 'Not data!!!',
            );
        }
        return Response::json($contents);
    }
}
?>