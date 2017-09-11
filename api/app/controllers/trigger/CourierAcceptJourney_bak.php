<?php

namespace trigger;

use ordermodel\OrdersModel;
use ordermodel\StatusModel;
use order\OrderController;
use seller\Exception;
use ticketmodel\RequestModel;
use ticketmodel\ReferModel;
use ticketmodel\FeedbackModel;
use metadatamodel\OrderStatusModel;
use omsmodel\PipeJourneyModel;
use Input;
use LMongo;
use Response;
use DB;
use Cache;
use metadatamodel\GroupOrderStatusModel;
use omsmodel\SellerModel;
use order\StatusOrderCtrl;
use ordermodel\AddressModel;

use order\ChangeOrderCtrl;

class CourierAcceptJourney extends \BaseController {
    
    private $__Lading, $__Logjourney, $__NewStatus;
    
    private $__Result, $__LogOutput;

    private $group_status   = [];
    private $update         = [];

    function getIndex($idLog){
        $LMongo         = new \LMongo;
        $this->__Logjourney = $LMongo::collection('log_journey_lading')
                    ->where('_id', new \MongoId($idLog))
                    ->where('accept',0)
                    ->first();
                    //->where('accept','<',1)
                    //->get();
        //return Response::json($this->__Logjourney);  

        if(!$this->__Logjourney){
            return Response::json( array(
                'error'         => 'data_log_empty', 
                'error_message' => 'Không tìm thấy lịch trình cần thực hiện',
                'data'          => null
                ) );
        }
        
        // Get Data Lading
        $this->_getOrder();

        if(!isset($this->__Lading->id) || !$this->_convertCourier()){
            $LMongo::collection('log_journey_lading')
                    ->where('_id',new \MongoId($this->__Logjourney['_id']))
                    ->update([
                        'error_log' => [
                            'message'           => 'Không tìm thấy vận đơn',
                        ],
                        'accept'        => 5,
                        'time_error'    => $this->time(),
                        'time_update'   => $this->time()
                    ]);
                    
            return Response::json( array(
                'error'         => 'data_lading_empty', 
                'error_message' => 'Không tìm thấy vận đơn',
                'data'          => $this->__Logjourney['input']
                ) );
        }
        
        //$StatusDenied = [52,53,66];
        //if(in_array($this->__Lading['data'][0]['status'],$StatusDenied)){

        if($this->__Lading->verify_id > 0){
            $LMongo::collection('log_journey_lading')
                    ->where('_id',new \MongoId($this->__Logjourney['_id']))
                    ->update([
                        'error_log' => [
                            'status_lading'     => $this->__Lading->status,
                            'message'           => 'Đơn hàng đã đối soát'
                        ],
                        'accept'        => 2,
                        'time_error'    => $this->time(),
                        'time_update'   => $this->time()
                    ]);
                    
            return Response::json( array(
                    'error'         => 'status_error', 
                    'error_message' => 'Đơn hàng đã đối soát',
                    'data'          => $this->__Logjourney['input']
                ) );
        }
                
        // Xử lý vận đơn chuyển tiếp
        if($this->_convertCourier() != $this->__Lading->courier_id){
            return $this->_process(2);
        }
        
        return $this->_process(1);
    }

    function getJourney(){
        $LMongo         = new \LMongo;
        $this->__Logjourney = $LMongo::collection('log_journey_lading')
                ->whereIn('accept',[0,7])
                //->whereGt('UserId',0)
                ->orderBy('priority', 'asc')
                ->orderBy('time_create', 'asc')
                ->orderBy('time_update', 'asc')
                ->first();
        if(!$this->__Logjourney){
            return Response::json( array(
                'error'         => 'data_log_empty',
                'error_message' => 'Không tìm thấy lịch trình cần thực hiện',
                'data'          => null
                ) );
        }
        $this->_getOrder();

        if(!isset($this->__Lading->id) || !$this->_convertCourier()){
            $LMongo::collection('log_journey_lading')
                ->where('_id',new \MongoId($this->__Logjourney['_id']))
                ->update([
                    'error_log' => [
                        'message'           => 'Không tìm thấy vận đơn',
                    ],
                    'accept'        => 5,
                    'time_error'    => $this->time(),
                    'time_update'   => $this->time()
                ]);

            return Response::json( array(
                'error'         => 'data_lading_empty',
                'error_message' => 'Không tìm thấy vận đơn',
                'data'          => $this->__Logjourney['input']
            ) );
        }

        if($this->__Lading->verify_id > 0){
            $LMongo::collection('log_journey_lading')
                ->where('_id',new \MongoId($this->__Logjourney['_id']))
                ->update([
                    'error_log' => [
                        'status_lading'     => $this->__Lading->status,
                        'message'           => 'Đơn hàng đã đối soát'
                    ],
                    'accept'        => 2,
                    'time_error'    => $this->time(),
                    'time_update'   => $this->time()
                ]);

            return Response::json( array(
                'error'         => 'status_error',
                'error_message' => 'Đơn hàng đã đối soát',
                'data'          => $this->__Logjourney['input']
            ) );
        }
                    
        // Xử lý vận đơn chuyển tiếp
        if($this->_convertCourier() != $this->__Lading->courier_id){
            return $this->_process(2);
        }

        return $this->_process(1);
    }
        
    private function _convertCourier(){
        
        if(isset($this->__Logjourney['courier'])){
            //return $this->__Logjourney['courier'];
        }
                
        // COnfig courier
        $Courier = array(
            'vtp'       => 1,
            'vnp'       => 2,
            'ghn'       => 3,
            '123giao'   => 4,
            'netco'     => 5,
            'ghtk'      => 6,
            'gtk'       => 6,
            'sc'        => 7,
            'ems'       => 8,
            'gold'      => 9,
            'gts'       => 9,
            'ttc'       => 11
        );
        
        return isset($Courier[$this->__Logjourney['input']['username']]) ? $Courier[$this->__Logjourney['input']['username']] : false;
        
    }

    private function _getOrder(){
        $Status     = Input::has('status')      ? (int)Input::get('status')                         : (int)Input::json()->get('status');

        $OrdersModel    = new OrdersModel;
        $sCode          = (!preg_match("/sc/i", strtolower($this->__Logjourney['tracking_code'])) ? 'SC' : '') . $this->__Logjourney['tracking_code'];

        $this->__Lading = $OrdersModel::where('tracking_code',$sCode);

        if(in_array($Status,[21])){
            $this->__Lading = $this->__Lading->where('time_create','>=',$this->time() - $this->time_limit);
        }else{
            $this->__Lading = $this->__Lading->where('time_accept','>=',$this->time() - $this->time_limit);
        }

        return $this->__Lading          = $this->__Lading->first(['id','status','time_update','num_delivery','total_weight','tracking_code',
                                                                'tracking_code','domain','time_accept','time_pickup','from_user_id','to_address_id','courier_id','postman_id','to_district_id',
                                                                'to_phone','verify_id','time_create','time_accept','time_approve','time_pickup','time_success']);
    }

    function _process($typeStatus = 2, $json = true){
        $LMongo             = new \LMongo;
        $dbCourierStatus    = new \stdClass();
        if(isset($this->__Logjourney['UserId']) && $this->__Logjourney['UserId'] > 0){
            $dbCourierStatus->sc_status = $this->__Logjourney['input']['Status'];
        }
        else{
        $courier = $this->_convertCourier();

        $dbCourierStatus = \DB::connection('courierdb')->table('courier_status')
            ->where('courier_id',$courier)
            ->where('type',$typeStatus)
            ->where('courier_status',$this->__Logjourney['input']['Status'])
            ->where('active',1)
            ->remember(10)
            ->first(['sc_status']);
        }

        //return Response::json($dbCourierStatus);
        if(!$dbCourierStatus){
            // Log check CourierStatus
            $LMongo::collection('log_journey_lading')
                ->where('_id',new \MongoId($this->__Logjourney['_id']))
                ->update([
                    'error_log' => [
                        'status_lading'     => $this->__Lading->status,
                        'new_status_lading' => null,
                        'message'           => 'Không tìm thấy trạng thái này trong dữ liệu đồng bộ'
                    ],
                    'accept'        => 2,
                    'time_error'    => $this->time(),
                    'time_update'   => $this->time()
                ]);

            if($json){
                return Response::json( array(
                    'error'         => 'status_courier_empty',
                    'error_message' => 'Không tìm thấy trạng thái này trong dữ liệu đồng bộ',
                    'data'          => $this->__Logjourney['input']
                ) );
            }else{
                return array(
                    'error'         => 'status_courier_empty',
                    'error_message' => 'Không tìm thấy trạng thái này trong dữ liệu đồng bộ',
                    'data'          => $this->__Logjourney['input']
                );
            }

        }

        $this->__NewStatus = $dbCourierStatus->sc_status;

        //check truong hop thay doi trang thai cuoi ve trang thai da chuyen hoan hoac nguoc lai
        if( in_array($this->__Lading->status, array(52,53) ) && $this->__NewStatus == 66 ){
            $this->__NewStatus = 71;
        }elseif(in_array((int)$this->__Lading->status, [62,66]) && in_array($this->__NewStatus, array(52,53)) ){
            $this->__NewStatus = 70;
        }

        if($this->__NewStatus == 52){
            $GroupOrderStatusModel  = new GroupOrderStatusModel;
            $ListStatus = $GroupOrderStatusModel::where('group_status',29)->lists('order_status_code');

            if(!empty($ListStatus) && in_array((int)$this->__Lading->status, $ListStatus)){
                $this->__NewStatus == 53;
            }
        }

        $StatusOrderCtrl    = new StatusOrderCtrl;
        Input::merge(['group' => 4]);
        $dbStatusGroup      = $StatusOrderCtrl->getStatusgroup(false);
        if(empty($dbStatusGroup)){
            if($json){
                return Response::json( array(
                    'error'         => 'group_status_empty',
                    'error_message' => 'Lấy nhóm trạng thái thất bại',
                    'data'          => $this->__Logjourney['input']
                ) );
            }else{
                return array(
                    'error'         => 'group_status_empty',
                    'error_message' => 'Lấy nhóm trạng thái thất bại',
                    'data'          => $this->__Logjourney['input']
                );
            }
        }

        $GroupStatus = array();
        foreach($dbStatusGroup as $value){
            if(!isset($this->group_status[(int)$value['id']])){
                $this->group_status[(int)$value['id']] = [];
            }
            foreach($value['group_order_status'] as $v){
                $GroupStatus[$v['order_status_code']]           = $v['group_status'];
                $this->group_status[(int)$value['id']][]        = (int)$v['order_status_code'];
            }
        }

        //Check Phát lại lần 2  ít nhất sau 4h viettel
        if(in_array($this->__NewStatus, $this->group_status[29]) && in_array($this->__Lading->status, $this->group_status[29])){
            if($this->__Logjourney['time_create'] - $this->__Lading->time_update < 3600*4){
                $LMongo::collection('log_journey_lading')
                    ->where('_id',new \MongoId($this->__Logjourney['_id']))
                    ->update([
                        'error_log' => [
                            'status_lading'     => $this->__Lading->status,
                            'new_status_lading' => $this->__NewStatus,
                            'message'           => 'Trạng thái phát thất bại đã được cập nhật cách đây chưa đủ 4 tiếng'
                        ],
                        'accept'        => 2,
                        'time_error'    => $this->time(),
                        'time_update'   => $this->time()
                    ]);

                if($json){
                    return Response::json( array(
                        'error'         => 'status_error',
                        'error_message' => 'Trạng thái phát thất bại đã được cập nhật cách đây chưa đủ 4 tiếng',
                        'data'          => $this->__Logjourney['input'],
                        'newStatus'     => $this->__NewStatus
                    ) );
                }else{
                    return array(
                        'error'         => 'status_error',
                        'error_message' => 'Trạng thái phát thất bại đã được cập nhật cách đây chưa đủ 4 tiếng',
                        'data'          => $this->__Logjourney['input'],
                        'newStatus'     => $this->__NewStatus
                    );
                }
            }

            // Phát không thành công lần 2
            if($this->__Lading->num_delivery == 1){
                $this->__NewStatus  = 77;
            }
        }

        if(in_array($this->__NewStatus, $this->group_status[29]) && !isset($this->__Logjourney['UserId'])){
            // kiểm tra đã có trạng thái yêu cầu phát lại hoặc ở trạng thái phát lại theo yêu cầu
            if($this->__Lading->status == 81 || $this->__update_pipe_journey()){// nếu có yêu cầu phát lại, + phát không thành công => duyệt hoàn
                $this->__NewStatus  = 61;
            }
        }

        if(isset($GroupStatus[$this->__NewStatus]) && isset($GroupStatus[$this->__Lading->status])){
            $dbStatusAccept = \DB::connection('courierdb')->table('courier_status_accept')
                ->where('status_id',$GroupStatus[$this->__Lading->status])
                ->where('status_accept_id',$GroupStatus[$this->__NewStatus])
                ->where('active',1)
                ->first(['status_accept_id']);
            //return Response::json($dbStatusAccept);
        }else{
            // Log check StatusAccept
            $LMongo::collection('log_journey_lading')
                ->where('_id',new \MongoId($this->__Logjourney['_id']))
                ->update([
                    'error_log' => [
                        'status_lading'     => $this->__Lading->status,
                        'new_status_lading' => $this->__NewStatus,
                        'message'           => 'Trạng thái mới không tồn tại'
                    ],
                    'accept'        => 2,
                    'time_error'    => $this->time(),
                    'time_update'   => $this->time()
                ]);

            if($json){
                return Response::json( array(
                    'error'         => 'status_error',
                    'error_message' => 'Trạng thái mới không tồn tại',
                    'data'          => $this->__Logjourney['input'],
                    'newStatus'     => $this->__NewStatus
                ) );
            }else{
                return array(
                    'error'         => 'status_error',
                    'error_message' => 'Trạng thái mới không tồn tại',
                    'data'          => $this->__Logjourney['input'],
                    'newStatus'     => $this->__NewStatus
                );
            }
        }
        if(!$dbStatusAccept){
            // Log check StatusAccept
            $LMongo::collection('log_journey_lading')
                ->where('_id',new \MongoId($this->__Logjourney['_id']))
                ->update([
                    'error_log' => [
                        'status_lading'     => $this->__Lading->status,
                        'new_status_lading' => $this->__NewStatus,
                        'message'           => 'Trạng thái đi ngược quy trình hoặc đã ở trạng thái cuối'
                    ],
                    'accept'        => 2,
                    'time_error'    => $this->time(),
                    'time_update'   => $this->time()
                ]);

            if($json){
                return Response::json( array(
                    'error'         => 'status_error',
                    'error_message' => 'Trạng thái đi ngược quy trình hoặc đã ở trạng thái cuối',
                    'data'          => $this->__Logjourney['input'],
                    'newStatus'     => $this->__NewStatus
                ) );
            }else{
                return array(
                    'error'         => 'status_error',
                    'error_message' => 'Trạng thái đi ngược quy trình hoặc đã ở trạng thái cuối',
                    'data'          => $this->__Logjourney['input'],
                    'newStatus'     => $this->__NewStatus
                );
            }

        }

        // Process database
        $this->_process_database();

        // Xử lý thành công
        if($this->__Result['ERROR'] == 'SUCCESS'){
            $this->__LogOutput['Jouney']['old_status']  = $this->__Lading->status;

            $LMongo::collection('log_journey_lading')
                ->where('_id',new \MongoId($this->__Logjourney['_id']))
                ->update([
                    'code'          => 'SUCCESS',
                    'log_output'    => $this->__LogOutput,
                    'accept'        => 1,
                    'time_success'  => $this->time(),
                    'time_update'   => $this->time()
                ]);

            // insert log vượt cân

            if(isset($this->__Logjourney['weight']) && $this->__Logjourney['weight'] > 0 && $this->__Logjourney['weight'] != $this->__Lading->total_weight){
                $IdLog = $this->InsertLogWeight();
                $this->PredisWeight((string)$IdLog);
            }

            if($this->__NewStatus == 21 && isset($this->__Logjourney['UserId'])){
                $this->PredisAcceptLading($this->__Lading->tracking_code);
            }

            if($json){
                return Response::json( array(
                    'error'         => 'success',
                    'error_message' => 'Cập nhật lịch trình - trạng thái thành công.',
                    'data'          => $this->__Logjourney['input']
                ) );
            }else{
                return array(
                    'error'         => 'success',
                    'error_message' => 'Cập nhật lịch trình - trạng thái thành công.',
                    'data'          => $this->__Logjourney['input']
                );
            }

        }

        // Log error
        $LMongo::collection('log_journey_lading')
            ->where('_id',new \MongoId($this->__Logjourney['_id']))
            ->update([
                'log_output'    => $this->__LogOutput,
                'code'          => 'INSERT_DATABASE_ERROR',
                'error_log'     => [
                    'status_lading'     => $this->__Lading->status,
                    'new_status_lading' => $this->__NewStatus,
                    'message'           => $this->__Result
                ],
                'accept'        => 3,
                'time_error'    => $this->time(),
                'time_update'   => $this->time()
            ]);

        if($json){
            return Response::json( array(
                'error'         => 'fail_database',
                'error_message' => $this->__Result,
                'data'          => $this->__Logjourney['input']
            ) );
        }else{
            return array(
                'error'         => 'fail_database',
                'error_message' => $this->__Result,
                'data'          => $this->__Logjourney['input']
            );
        }
    }
                    
    private function _process_database(){
        if($this->__NewStatus != $this->__Lading->status && !isset($this->__Logjourney['UserId'])){
            if($this->__NewStatus == 51){
                if($this->__Lading->total_amount > 5000000){ // với đơn hàng giá trị cao, báo khách hàng trước khi phát
                    $this->SendSMS();
                }elseif($this->__Lading->to_district_id > 0){// huyện xã tỉ lệ chuyển hoàn cao
                    $District        = \DistrictModel::where('id', $this->__Lading->to_district_id)->remember('60')->first();
                    if(isset($District->id) && $District->return_percent > 10){
                        $Content = 'Don hang '.$this->__Lading->tracking_code.' da giao di phat,buu ta se lien lac phat hang
                                    trong thoi gian som nhat. Lien he tong đai 1900 636030 de duoc ho tro them thong tin';
                        $this->SendSMS($Content);
                    }
                }
            }elseif(in_array($this->__NewStatus,$this->group_status[29]) && !empty($this->__Lading->postman_id)){
                // send sms  khi đơn hang phát không thành công
                $this->ReportFail();
            }
        }

        $DB = DB::connection('orderdb');
        $DB->beginTransaction();

        try {
            if($this->__Logjourney['input']['Status'] == 505 &&  !empty($this->__Logjourney['input']['params']['DeliverID']) && !empty($this->__Logjourney['input']['params']['DeliverPhone'])){
                $this->__update_postman();
            }

            $this->_insert_order_journey();
            $this->_update_order_status();

            $DB->commit();
            //return $queries = $DB->getQueryLog();
            //var_dump($queries);die;
            $this->__Result = array('ERROR' => 'SUCCESS','MSG' => '');
        } catch(ValidationException $e)
        {
            $DB->rollback();
            $this->__Result = array('ERROR' => 'FAIL','MSG' => 'ValidationException', 'DATA' => $e);
        } catch(\Exception $e)
        {
            $DB->rollback();
            //throw $e;
            $this->__Result = array('ERROR' => 'FAIL','MSG' => 'Exception', 'DATA' => $e->getMessage());
        }
    }
    
    private function _insert_order_journey()
    {
        $this->__LogOutput['Jouney'] = [
            'order_id'      => $this->__Lading->id,
            'status'        => $this->__NewStatus,
            'city_name'     => $this->__Logjourney['input']['City'],
            'note'          => $this->__Logjourney['input']['Note'],
            'time_create'   => $this->time()
        ];
        
        return StatusModel::firstOrCreate($this->__LogOutput['Jouney']);
    }
    
    private function _update_order_status()
    {
        $this->update['status']         = $this->__NewStatus;
        $this->update['time_update']    = $this->time();
        $this->__InsertLogBoxme($this->__Lading->domain , $this->__Lading->tracking_code, $this->__NewStatus);
        
        if(isset($this->__Logjourney['input']['TrackingOrder']) 
            && $this->__Logjourney['input']['TrackingOrder'] != '' 
            && $this->__Logjourney['input']['TrackingOrder'] != $this->__Logjourney['input']['TrackingCode'])
        {
            $this->update['courier_tracking_code'] = $this->__Logjourney['input']['TrackingOrder'];
        }

        // Thời gian duyệt
        if($this->__NewStatus == 21 && $this->__Lading->time_accept == 0){
            $this->update['time_accept']  = $this->time();
        }

        // Thời gian ở trạng thái cuối
        if(in_array($this->__NewStatus,[52,53,66])){
            $this->update['time_success'] = $this->time();
        }
        
        // Thời gian về kho

        if((in_array($this->__NewStatus,[36,37]) || $this->__NewStatus >= 40) && $this->__Lading->time_pickup == 0){

            if(in_array($this->__NewStatus,[36,37])) {
                $this->update['time_pickup'] = $this->time();
            }else{
                $timePickup = (!isset($this->update['time_success']) || ($this->__Lading->time_accept + 86400 < $this->update['time_success'])) ?  ($this->__Lading->time_accept + 86400) : ($this->__Lading->time_accept + 1800);
                $this->update['time_pickup'] = $timePickup < $this->time() ? $timePickup : $this->time();
            }

            //Update  Seller
            $SellerModel    = new SellerModel;
            $Seller         = $SellerModel::firstOrNew(['user_id' => (int)$this->__Lading->from_user_id]);
            $Seller->last_time_pickup   = $this->update['time_pickup'];
            if(empty($Seller->first_time_pickup)){
                $Seller->first_time_pickup  = $this->update['time_pickup'];
                $this->__update_user_info((int)$this->__Lading->from_user_id);
            }
            $Seller->save();
        }

        $OrdersModel = new OrdersModel;
        if(empty($this->__Lading->time_accept)){
            $OrdersModel    = $OrdersModel::where('time_create','>=',$this->time() - $this->time_limit);
        }else{
            $OrdersModel    = $OrdersModel::where('time_accept','>=',$this->time() - $this->time_limit);
        }

        $Update = $OrdersModel->where('id',$this->__Lading->id);

        // update num_pickup  num_delivery
        if(!isset($this->__Logjourney['UserId'])  && (($this->__Lading->status != $this->__NewStatus) || ($this->__Logjourney['time_create'] - $this->__Lading->time_update > 3600))){
            $GroupPickup    = array_merge($this->group_status[27], $this->group_status[26]); // pickup
            $GroupDelivery  = array_merge($this->group_status[29], $this->group_status[30]); // delivery

            if(in_array($this->__NewStatus, $GroupPickup)){
                return $Update->increment('num_pick',1, $this->update);
            }elseif(in_array($this->__NewStatus, $GroupDelivery)){
                return $Update->increment('num_delivery',1, $this->update);
            }
        }

        // update first fail delivery
        if(!isset($this->__Logjourney['UserId']) && in_array($this->__NewStatus,$this->group_status[29])){
            try{
                \omsmodel\CustomerAdminModel::where('user_id',$this->__Lading->from_user_id)->where('first_fail_order_time',0)->update(['first_fail_order_time' => $this->time(), 'fail_tracking_code' => $this->__Lading->tracking_code]);
            }catch (\Exception $e){

            }
        }

        // update num_delivery

        return $Update->update($this->update);
    }

    private function __update_postman(){
        $AddressModel   = new AddressModel;
        $Address = $AddressModel::find($this->__Lading->to_address_id);
        if(isset($Address->id)){
            $PostManModel   = new \PostManModel;
            $PostMan        = $PostManModel->firstOrNew([
                'postman_id'        => (int)$this->__Logjourney['input']['params']['DeliverID'],
                'courier_id'        => (int)$this->__Lading->courier_id,
                'city_id'           => (int)$Address->city_id,
                'district_id'       => (int)$Address->province_id,
                'ward_id'           => (int)$Address->ward_id,
                'phone'             => trim($this->__Logjourney['input']['params']['DeliverPhone']),
            ]);

            if(!$PostMan->exists){
                $PostMan->name      = isset($this->__Logjourney['input']['params']['DeliverName']) ? $this->__Logjourney['input']['params']['DeliverName'] : 'Bưu tá';
                $PostMan->address   = isset($this->__Logjourney['input']['params']['DeliverHome']) ? $this->__Logjourney['input']['params']['DeliverHome'] : 'Địa chỉ';
                $PostMan->save();
            }

            //insert table
            if(empty($this->__Lading->postman_id)){
                $this->update['postman_id'] = (int)$PostMan->postman_id;
            }
        }
        return;
    }

    private function __update_pipe_journey(){
        return PipeJourneyModel::where('tracking_code', $this->__Lading->id)
            ->where('type',1)
            ->where(function($query){
                $query->where(function($q){
                    $q->where('pipe_status', 707)
                        ->where('group_process', 29);
                })->orWhere(function($q){
                    $q->where('pipe_status', 903)
                        ->where('group_process', 31);
                });
            })->where('active',0)->update(['active' => 1]);
    }

    private function __update_user_info($UserId){
        return \sellermodel\UserInfoModel::where('user_id', $UserId)->where('pipe_status',400)->update(['pipe_status' => 500, 'time_update' => $this->time()]);
    }

    public function getJourneyBoxme($idLog = ''){
        $LMongo         = new \LMongo;
        $Log            = $LMongo::collection('log_journey_notice');

        if(!empty($idLog)){
            $Log    = $Log->where('_id', new \MongoId($idLog));
        }

        $Log    = $Log->where('accept',0)
                        ->orderBy('time_create', 'asc')
                        ->first();

        if(!$Log){
            return Response::json( array(
                'error'         => 'EMPTY'
            ) );
        }

        $Params = [
            'TrackingCode'   => $Log['tracking_code']
            , 'StatusCode' => $Log['status']
        ];

        if(isset($Log['time'])){
            $Params['TimeCreate']       =  $Log['time']['time_create'];
            $Params['TimeAccept']       =  $Log['time']['time_accept'];
            $Params['TimeApprove']      =  $Log['time']['time_approve'];
            $Params['TimePickup']       =  $Log['time']['time_pickup'];
            $Params['TimeSuccess']      =  $Log['time']['time_success'];
        }

        $Params   = json_encode($Params);

        $respond = \cURL::rawPost('http://seller.boxme.vn/api/update_order_status_sc?Token=6c66437f3d839cb1d247990a40b2afae',$Params);

        $Update['accept']       = 1;
        $Update['time_accept']  = $this->time();
        $Update['params']       = $Params;
        $Update['result']       = $respond->body;

        if(!$respond->body){
            $ResponseData  =    [
                                    'error'         => true,
                                    'message'       => 'FAIL_API',
                                    'error_message' => 'Lỗi API',
                                    'data'          => $Log
                                ];
            return Response::json( $ResponseData);
        }else{
            $decode = json_decode($respond->body,true);
            $Update['messenger']                        = $decode['Message'];
            $Update['error_code']                       = $decode['Success'];

            $ResponseData  =    [
                'error'         => false,
                'message'       => 'SUCCESS',
                'error_message' => 'Thành công',
                'data'          => $Log
            ];
        }


        try{
            $LMongo::collection('log_journey_notice')
                ->where('_id',new \MongoId($Log['_id']))
                ->update($Update);

        }catch (\Exception $e){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'UPDATE_ERROR',
                'error_message' => $e->getMessage(),
                'data'          => $Log
            ];
        }

        return Response::json( $ResponseData);
    }

    // sms phát thất bại
    private function ReportFail(){
        $PostManModel   = new \PostManModel;
        $PostMan        = $PostManModel::where('courier_id', $this->__Lading->courier_id)->where('postman_id', $this->__Lading->postman_id)->orderBy('id','DESC')->first();

        if(isset($PostMan->id)){
            $User = \User::where('id', $this->__Lading->from_user_id)->first(['id','phone']);
            if(isset($User->phone)){
                $toPhone = str_replace(array(';','.',' ','/','|'), ',', $User->phone);

                $arrPhone = array();
                if($toPhone != ''){
                    $arrPhone = explode(',', $toPhone);
                }

                Input::Merge([
                    'to_phone'   => $arrPhone[0],
                    'content'    => 'Don hang '.$this->__Lading->tracking_code.' phat khong thanh cong,ban vui long lien he buu ta:'.$PostMan->name.', sdt:'.$PostMan->phone
                ]);

                $SmsController  = new \SmsController;
                $SmsController->postSendsms(false);
            }
        }
        return ;
    }

    private function SendSmS($Content = ''){
        $toPhone = str_replace(array(';','.',' ','/','|'), ',', $this->__Lading->to_phone);

        $arrPhone = array();
        if($toPhone != ''){
            $arrPhone = explode(',', $toPhone);
        }

        if(empty($Content)){
            $Content    = 'Don hang '.$this->__Lading->tracking_code.' da giao bưu ta di phat, ban vui long kiem tra hang truoc khi ky nhan hang. Lien he 1900636030 de duoc ho tro';
        }

        Input::Merge([
            'to_phone'   => $arrPhone[0],
            'content'    => $Content
        ]);

        $SmsController  = new \SmsController;
        $SmsController->postSendsms(false);
    }

    private function Feedback(){
        $ListUpdate = [];

        $listTicketID = ReferModel::where('type',1)->where('code', $this->__Lading['data'][0]['tracking_code'])->lists('ticket_id');
        if(!empty($listTicketID)) {
            $listTicketIDNotDone = RequestModel::whereIn('id',$listTicketID)->where('status','!=','CLOSED')->get(['id', 'status'])->toArray();

            $statusData = OrderStatusModel::where('code',(int)$this->__Logjourney['input']['Status'])->first();
            if(!empty($listTicketIDNotDone)) {
                $feedbackData = [];
                foreach($listTicketIDNotDone as $k => $oneTicketID) {

                    $content = "Vận đơn ".$this->__Lading['data'][0]['tracking_code']." đã thay đổi sang trạng thái ".$statusData->name;
                    $content .= "<blockquote>".$this->__Logjourney['input']['Note']."</blockquote>";
                    $feedbackData[$k]['content'] = $content;
                    $feedbackData[$k]['source'] = 'system';
                    $feedbackData[$k]['user_id'] = $this->__Logjourney['UserId'];
                    $feedbackData[$k]['time_create'] = $this->time();
                    $feedbackData[$k]['notification'] = 0;
                    $feedbackData[$k]['ticket_id'] = (int)$oneTicketID['id'];

                    //update ticket status
                    if($oneTicketID['status'] != 'PROCESSED') {
                        $ListUpdate[] = (int)$oneTicketID['id'];
                    }
                }

                if(!empty($ListUpdate)){
                    RequestModel::whereIn('id', $ListUpdate)->update(['status' => 'PENDING_FOR_CUSTOMER']);
                }
                FeedbackModel::insert($feedbackData);

            }
        }
    }

    public function postAcceptstatus($system = false){
        $Status     = Input::has('status')      ? (int)Input::get('status')                         : (int)Input::json()->get('status');
        $ScCode     = Input::has('sc_code')     ? strtoupper(trim(Input::get('sc_code')))           : strtoupper(trim(Input::json()->get('sc_code')));
        $City       = Input::has('city')        ? trim(Input::get('city'))                          : trim(Input::json()->get('city'));
        $Note       = Input::has('note')        ? trim(Input::get('note'))                          : trim(Input::json()->get('note'));
        $Courier    = Input::has('courier')     ? strtolower(trim(Input::get('courier')))           : strtolower(trim(Input::json()->get('courier')));

        if(empty($Status) || empty($ScCode) || empty($City) || empty($Note) || empty($Courier)){
            return Response::json( array(
                'error'         => 'data_empty',
                'error_message' => 'Thiếu dữ liệu',
                'data'          => []
            ) );
        }

        if(!$system){
            $UserInfo   = $this->UserInfo();
            if(empty($UserInfo)){
                return Response::json( array(
                    'error'         => 'login_time_out',
                    'error_message' => 'Bạn chưa đăng nhập',
                    'data'          => []
                ) );
            }

            if($UserInfo['privilege'] == 0 && $Status == 67){
                return Response::json( array(
                    'error'         => true,
                    'message'       => 'Trạng thái đi ngược quy trình hoặc đã ở trạng thái cuối',
                    'data'          => []
                ));
            }
        }else{
            $UserInfo['id'] = 1;
        }

        
        $this->__Logjourney = [
            'tracking_code'   => $ScCode,
            'input'   => [
                  'username'    =>  $Courier,
                  'function'    => 'LichTrinh',
                  'params'      => [
                      'SC_CODE' => $ScCode,
                      'STATUS'  => $Status,
                      'CITY'    => $City,
                      'NOTE'    => $Note,
                  ],
                  'TrackingOrder' =>  $ScCode,
                  'TrackingCode'  =>  $ScCode,
                  'Status'        => $Status,
                  'Note'          => $Note,
                  'City'          => $City
            ],
            'UserId'        => (int)$UserInfo['id'],
            "accept"        => 0,
            "priority"      => 1,
            "time_create"   => $this->time(),
            "time_update"   => $this->time()
        ];

        $LMongo         = new \LMongo;
        try{
            $Id = $LMongo::collection('log_journey_lading')
                ->insert($this->__Logjourney);
            $this->__Logjourney['_id']  = $Id;
        }catch(\Exception $e){
            return Response::json( array(
                'error'         => 'insert_error',
                'error_message' => 'Thêm mới lỗi',
                'data'          => []
            ) );
        }

        // Get Data Lading
        $this->_getOrder();
        //return Response::json($this->__Lading['data']);
        if(!isset($this->__Lading->id) || !$this->_convertCourier()){
            $LMongo::collection('log_journey_lading')
                ->where('_id',new \MongoId($this->__Logjourney['_id']))
                ->update([
                    'error_log' => [
                        'message'           => 'Không tìm thấy vận đơn',
                    ],
                    'accept'        => 5,
                    'time_error'    => $this->time(),
                    'time_update'   => $this->time()
                ]);

            return Response::json( array(
                'error'         => 'data_lading_empty',
                'error_message' => 'Không tìm thấy vận đơn',
                'data'          => $this->__Logjourney['input']
            ) );
        }

        //$StatusDenied = [52,53,66];
        //if(in_array($this->__Lading['data'][0]['status'],$StatusDenied)){

        if($this->__Lading->verify_id > 0){
            $LMongo::collection('log_journey_lading')
                ->where('_id',new \MongoId($this->__Logjourney['_id']))
                ->update([
                    'error_log' => [
                        'status_lading'     => $this->__Lading->status,
                        'message'           => 'Đơn hàng đã đối soát'
                    ],
                    'accept'        => 2,
                    'time_error'    => $this->time(),
                    'time_update'   => $this->time()
                ]);

            return Response::json( array(
                'error'         => 'status_error',
                'error_message' => 'Đơn hàng đã đối soát',
                'data'          => $this->__Logjourney['input']
            ) );
        }

        return $this->_process(1, false);
    }

    private function get_pipe_journey($Order){
        return PipeJourneyModel::where('tracking_code',$Order->id)
            ->where('type',1)
            ->where(function($query){
                $query->where(function($q){
                    $q->where('pipe_status', 707)
                        ->where('group_process', 29);
                })->orWhere(function($q){
                    $q->where('pipe_status', 903)
                        ->where('group_process', 31);
                });
            })->where('active',0)->count();
    }

    public function getAutoreturn(){
        $fromTime       = $this->time();
        $Day            = date("N", $fromTime);
        $TimeUpdate     = $this->time() - 48*3600;

        if($Day == 7){
            $TimeUpdate -= ($this->time() - strtotime(date('Y-m-d').' 00:00:00'));
        }elseif(in_array($Day, [1,2])){
            $TimeUpdate -= 86400;
        }

        $Order          = OrdersModel::where('status', 60)->where('time_accept','>=',$this->time() - $this->time_limit)
                                     ->where('time_update', '<=', $TimeUpdate)
                                     ->orderBy('time_system_update','ASC')
                                     ->orderBy('time_update','ASC')
                                     ->first(['id','tracking_code','status','courier_id','time_update','time_system_update']);
        if(!isset($Order->id)){
            return Response::json( array(
                'error'         => true,
                'error_message' => 'Đã duyệt hết'
            ) );
        }

        $ListCourier    = Cache::get('courier_cache');
        if(empty($ListCourier) || !isset($ListCourier[$Order->courier_id])){
            return Response::json( array(
                'error'         => true,
                'error_message' => 'COURIER_EMPTY'
            ) );
        }

        //update time
        $Order->time_system_update = $this->time();
        try{
            $Order->save();
        }catch (\Exception $e){
            return Response::json( array(
                'error'         => true,
                'error_message' => 'Cập nhật đơn hàng lỗi'
            ) );
        }

        // kiểm tra xem có yêu cầu phát lại chưa xử lý ko, có thì return
        $PipeJourney    = $this->get_pipe_journey($Order);
        if($PipeJourney > 0){
            return Response::json( array(
                'error'         => false,
                'error_message' => 'Cập nhật vận đơn thành công'
            ) );
        }

        Input::merge([
            'status'    => 61,
            'sc_code'   => $Order->tracking_code,
            'city'      => 'ShipChung',
            'note'      => 'Quá hạn lưu kho, hệ thống tự động duyệt chuyển hoàn',
            'courier'   => $ListCourier[$Order->courier_id]['prefix']
        ]);

        return $this->postAcceptstatus(true);
    }

    public function getReturnoverthree(){
        $Order  = OrdersModel::whereNotIn('status',[52,53,66,61,62,62,63,64,65,70,71,73])->where('time_accept','>=',$this->time() - $this->time_limit)->where('num_delivery', '>=', 3)->where('time_update', '<=', $this->time() - 2*3600)->first(['id','tracking_code','status','courier_id']);

        if(!isset($Order->id)){
            return Response::json( array(
                'error'         => true,
                'error_message' => 'Đã duyệt hết'
            ) );
        }

        $ListCourier    = Cache::get('courier_cache');
        if(empty($ListCourier) || !isset($ListCourier[$Order->courier_id])){
            return Response::json( array(
                'error'         => true,
                'error_message' => 'COURIER_EMPTY'
            ) );
        }

        Input::merge([
            'status'    => 61,
            'sc_code'   => $Order->tracking_code,
            'city'      => 'ShipChung',
            'note'      => 'Quá hạn lưu kho, hệ thống tự động duyệt chuyển hoàn',
            'courier'   => $ListCourier[$Order->courier_id]['prefix']
        ]);

        return $this->postAcceptstatus(true);
    }

    public function getAutowaiting(){
        $Day            = date("N", $this->time());
        $TimeUpdate     = $this->time() - 24*3600;

        if($Day == 7){
            $TimeUpdate -= ($this->time() - strtotime(date('Y-m-d').' 00:00:00'));
        }elseif($Day == 1){
            $TimeUpdate -= 86400;
        }

        $Order  = OrdersModel::where('status', 77)
                             ->where('time_accept','>=',$this->time() - $this->time_limit)
                             ->where('time_update', '<=', $TimeUpdate)
                             ->orderBy('time_system_update','ASC')
                             ->orderBy('time_update','ASC')
                             ->first(['id','tracking_code','status','courier_id','time_update','time_system_update']);

        if(!isset($Order->id)){
            return Response::json( array(
                'error'         => true,
                'error_message' => 'Đã duyệt hết'
            ) );
        }

        $ListCourier    = Cache::get('courier_cache');
        if(empty($ListCourier) || !isset($ListCourier[$Order->courier_id])){
            return Response::json( array(
                'error'         => true,
                'error_message' => 'COURIER_EMPTY'
            ) );
        }

        //update time
        $Order->time_system_update = $this->time();
        try{
            $Order->save();
        }catch (\Exception $e){
            return Response::json( array(
                'error'         => true,
                'error_message' => 'Cập nhật đơn hàng lỗi'
            ) );
        }

        // kiểm tra xem có yêu cầu phát lại chưa xử lý ko, có thì return
        $PipeJourney    = $this->get_pipe_journey($Order);
        if($PipeJourney > 0){
            return Response::json( array(
                'error'         => false,
                'error_message' => 'Cập nhật vận đơn thành công'
            ) );
        }

        Input::merge([
            'status'    => 60,
            'sc_code'   => $Order->tracking_code,
            'city'      => 'ShipChung',
            'note'      => 'Quá hạn xử lý, hệ thống tự động chuyển trạng thái chờ xác nhận chuyển hoàn',
            'courier'   => $ListCourier[$Order->courier_id]['prefix']
        ]);

        return $this->postAcceptstatus(true);
    }

    private function InsertLogWeight(){
        $LMongo = new LMongo;
        return $Id     = $LMongo::collection('log_over_weight')->insert([
            'partner'         => (string)$this->__Logjourney['_id'],
            'tracking_code'   => $this->__Logjourney['tracking_code'],
            'courier_id'      => (int)$this->__Logjourney['courier'],
            'total_weight'    => (int)$this->__Logjourney['weight'],
            'old_weight'      => 0,
            'time_create'     => $this->time(),
            'status'          => 'WAITING'
        ]);
    }

    public function getChangeOverWeight($idLog = ''){
        $LMongo         = new LMongo;
        $Journey        = $LMongo::collection('log_over_weight')
                                       ->where('status','WAITING')
                                       ->whereGt('total_weight',0);

        if(!empty($idLog)){
            $Journey    = $Journey->where('_id', new \MongoId($idLog));
        }

        $Journey    = $Journey->orderBy('time_create', 'asc')->first();

        if(!empty($Journey)){
                Input::merge(
                    [   'TrackingCode' => $Journey['tracking_code'],
                        'total_weight' => $Journey['total_weight'],
                        'UserInfo'     => ['id' => 1, 'privilege'   => 2]
                    ]);
                $ChangeOrderCtrl    = new ChangeOrderCtrl;
                $Update = $ChangeOrderCtrl->postEdit(false);
                $DataUpdate = ['status' => 'SUCCESS','time_accept' => $this->time()];

                if(!$Update){
                    $DataUpdate['status'] = 'API_FAIL';
                }else{
                    if($Update['error']){
                        $DataUpdate['status'] = $Update['message'];
                    }else{
                        $DataUpdate['old_weight']           = (int)$Journey['total_weight'];
                        if(!empty($Update['data_log'])){
                            if(!empty($Update['data_log']['total_weight'])){
                                $DataUpdate['old_weight']    = (int)$Update['data_log']['total_weight']['old'];
                            }
                        }
                    }
                }


            $LMongo = new LMongo;

            try{
                $LMongo::collection('log_over_weight')
                    ->where('_id',new \MongoId($Journey['_id']))
                    ->update($DataUpdate);
                return Response::json( array(
                    'error'         => false,
                    'code'          => 'SUCCESS'
                ) );
            }catch (\Exception $e){
                return Response::json( array(
                    'error'         => true,
                    'code'          => 'ERROR',
                    'error_message' => $e->getMessage()
                ) );
            }

        }

        return Response::json( array(
            'error'         => true,
            'code'          => 'EMPTY'
        ) );
    }

    /*
     * Insert log_journey_notice
     */
    public function __InsertLogBoxme($Domain, $TrackingCode, $Status){
        if(in_array($Domain, ['boxme.vn', 'prostore.vn']) ||($Domain == 'chodientu.vn' && in_array($Status, [52,53,66,22,23,24,25,27,29]))){
            $LMongo         = new LMongo;
            $Id = $LMongo::collection('log_journey_notice')->insert([
                'tracking_code' => $TrackingCode,
                'domain'        => $Domain,
                'status'        => (int)$Status,
                'time'          => [
                    'time_create'   => $this->__Lading->time_create,
                    'time_accept'   => $this->__Lading->time_accept,
                    'time_approve'  => $this->__Lading->time_approve,
                    'time_pickup'   => $this->__Lading->time_pickup,
                    'time_success'  => $this->__Lading->time_success
                ],
                'accept'        => 0,
                'time_create'   => $this->time()
            ]);

            $this->PredisAcceptBoxme((string)$Id);
        }

        return;
    }
}
