<?php

namespace mobile_trigger;
use ordermodel\OrdersModel;
use ordermodel\StatusModel;
use order\OrderController;
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
                        'time_error'    => time(),
                        'time_update'   => time()
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
                        'time_error'    => time(),
                        'time_update'   => time()
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
                    'time_error'    => time(),
                    'time_update'   => time()
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
                    'time_error'    => time(),
                    'time_update'   => time()
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
            'vtp'   => 1,
            'vnp'   => 2,
            'ghn'   => 3,
            '123giao'   => 4,
            'netco'   => 5,
            'ghtk'  => 6,
            'gtk'  => 6,
            'sc'    => 7,
            'ems'   => 8,
            'gold'   => 9,
            'gts'    => 9
        );
        
        return isset($Courier[$this->__Logjourney['input']['username']]) ? $Courier[$this->__Logjourney['input']['username']] : false;
        
    }

    private function _getOrder(){
        $Status     = Input::has('status')      ? (int)Input::get('status')                         : (int)Input::json()->get('status');

        $OrdersModel    = new OrdersModel;
        $sCode          = (!preg_match("/sc/i", strtolower($this->__Logjourney['tracking_code'])) ? 'SC' : '') . $this->__Logjourney['tracking_code'];

        $this->__Lading = $OrdersModel::where('tracking_code',$sCode);

        if(in_array($Status,[21])){
            $this->__Lading = $this->__Lading->where('time_create','>=',time() - $this->time_limit);
        }else{
            $this->__Lading = $this->__Lading->where('time_accept','>=',time() - $this->time_limit);
        }

        return $this->__Lading          = $this->__Lading->first();
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
                    'time_error'    => time(),
                    'time_update'   => time()
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
                        'time_error'    => time(),
                        'time_update'   => time()
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
            // kiểm tra đã có trạng thái yêu cầu phát lại
            if($this->__update_pipe_journey()){ // nếu có yêu cầu phát lại, + phát không thành công => duyệt hoàn
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
                    'time_error'    => time(),
                    'time_update'   => time()
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
                    'time_error'    => time(),
                    'time_update'   => time()
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
                    'time_success'  => time(),
                    'time_update'   => time()
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
                'time_error'    => time(),
                'time_update'   => time()
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
        // với đơn hàng giá trị cao, báo khách hàng trước khi phát
        if($this->__Lading->total_amount > 5000000 && $this->__NewStatus == 51 && $this->__Lading->status != 51){
            $this->SendSMS();
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
            'time_create'   => time()
        ];
        
        return StatusModel::firstOrCreate($this->__LogOutput['Jouney']);
    }
    
    private function _update_order_status()
    {
        $this->update['status']         = $this->__NewStatus;
        $this->update['time_update']    = time();
        $this->update['is_notice']      = 0;
        
        if(isset($this->__Logjourney['input']['TrackingOrder']) 
            && $this->__Logjourney['input']['TrackingOrder'] != '' 
            && $this->__Logjourney['input']['TrackingOrder'] != $this->__Logjourney['input']['TrackingCode'])
        {
            $this->update['courier_tracking_code'] = $this->__Logjourney['input']['TrackingOrder'];
        }

        // Thời gian duyệt
        if($this->__NewStatus == 21 && $this->__Lading->time_accept == 0){
            $this->update['time_accept']  = time();
        }

        // Thời gian ở trạng thái cuối
        if(in_array($this->__NewStatus,[52,53,66])){
            $this->update['time_success'] = time();
        }
        
        // Thời gian về kho
        if(in_array($this->__NewStatus,[36,37])){
            $this->update['time_pickup'] = time();

            //Update  Seller
            $SellerModel    = new SellerModel;
            $Seller         = $SellerModel::firstOrNew(['user_id' => (int)$this->__Lading->from_user_id]);
            $Seller->last_time_pickup   = time();
            if(empty($Seller->first_time_pickup)){
                $Seller->first_time_pickup  = time();
                $this->__update_user_info((int)$this->__Lading->from_user_id);
            }
            $Seller->save();
        }
                
        if($this->__NewStatus >= 40 && $this->__Lading->time_pickup == 0){
            $timePickup = (!isset($this->update['time_success']) || ($this->__Lading->time_accept + 86400 < $this->update['time_success'])) ?  ($this->__Lading->time_accept + 86400) : ($this->__Lading->time_accept + 1800);

            $this->update['time_pickup'] = $timePickup > time() ? $timePickup : time();
        }

        $OrdersModel = new OrdersModel;
        if(empty($this->__Lading->time_accept)){
            $OrdersModel    = $OrdersModel::where('time_create','>=',time() - $this->time_limit);
        }else{
            $OrdersModel    = $OrdersModel::where('time_accept','>=',time() - $this->time_limit);
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
        return \sellermodel\UserInfoModel::where('user_id', $UserId)->where('pipe_status',400)->update(['pipe_status' => 500, 'time_update' => time()]);
    }

    public function getJourneyBoxme(){
        $OrdersModel    = new OrdersModel;
        if(Input::has('TrackingCode')){
            $Order          = $OrdersModel::where('tracking_code',Input::get('TrackingCode'))
                                          ->first(['id','domain','tracking_code','status','is_notice']);
        }else{
            $Order          = $OrdersModel::where('time_accept','>=', time() - 86400*60)
                ->where('domain','boxme.vn')
                ->where('is_notice',0)
                ->orderBy('time_update','ASC')
                ->first(['id','domain','tracking_code','status','is_notice']);
        }

        if(!isset($Order->id)){
            return Response::json( array(
                'error'         => false,
                'message'       => 'EMPTY',
                'error_message' => 'Đã xử lý hết'
            ) );
        }

        $Insert = [
            'tracking_code' => $Order->tracking_code,
            'time_create'   => time()
        ];

        $Params = json_encode(['TrackingCode'   => $Order->tracking_code, 'StatusCode' => $Order->status]);
        $respond = \cURL::rawPost('http://seller.boxme.vn/api/update_order_status_sc?Token=6c66437f3d839cb1d247990a40b2afae',$Params);

        $Insert['params'] = $Params;
        $Insert['result'] = $respond->body;

        if(!$respond->body){
            // Ghi log
            $Insert['error_code']    = 'FAIL_API';
            $Insert['messenger']     = 'Lỗi API mất rồi';
            $ResponseData  =    [
                                    'error'         => true,
                                    'message'       => 'FAIL_API',
                                    'error_message' => 'Lỗi API'
                                ];
        }else{
            $decode = json_decode($respond->body,true);
            $Insert['messenger']                        = $decode['Message'];
            $Insert['error_code']                       = $decode['Success'];

            $ResponseData  =    [
                'error'         => false,
                'message'       => 'SUCCESS',
                'error_message' => 'Thành công'
            ];
        }

        $LMongo         = new \LMongo;
        $OrdersModel    = new OrdersModel;

        try{
            $OrdersModel::where('time_accept','>=', $this->time_limit)
                        ->where('id', $Order->id)
                        ->update(['is_notice' => 1]);

            $LMongo::collection('log_journey_notice')->insert($Insert);

        }catch (\Exception $e){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'UPDATE_ERROR',
                'error_message' => $e->getMessage()
            ];
        }

        return Response::json( $ResponseData);
    }

    private function SendSmS(){
        $toPhone = str_replace(array(';','.',' ','/','|'), ',', $this->__Lading->to_phone);

        $arrPhone = array();
        if($toPhone != ''){
            $arrPhone = explode(',', $toPhone);
        }

        Input::Merge([
            'to_phone'   => $arrPhone[0],
            'content'    => 'Don hang '.$this->__Lading->tracking_code.' da giao bưu ta di phat,
             ban vui long kiem tra hang truoc khi ky nhan hang. Lien he tong đai 1900 636030 de duoc ho tro them thong tin'
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
                    $feedbackData[$k]['time_create'] = time();
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
            "time_create"   => time(),
            "time_update"   => time()
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
                    'time_error'    => time(),
                    'time_update'   => time()
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
                    'time_error'    => time(),
                    'time_update'   => time()
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
        $fromTime       = time();
        $Day            = date("N", $fromTime);
        $TimeUpdate     = time() - 48*3600;

        if($Day == 7){
            $TimeUpdate -= (time() - strtotime(date('Y-m-d').' 00:00:00'));
        }elseif(in_array($Day, [1,2])){
            $TimeUpdate -= 86400;
        }

        $Order          = OrdersModel::where('status', 60)->where('time_accept','>=',time() - $this->time_limit)
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
        $Order->time_system_update = time();
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
        $Order  = OrdersModel::whereNotIn('status',[52,53,66,61,62,62,63,64,65,70,71,73])->where('time_accept','>=',time() - $this->time_limit)->where('num_delivery', '>=', 3)->where('time_update', '<=', time() - 2*3600)->first(['id','tracking_code','status','courier_id']);

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
        $Day            = date("N", time());
        $TimeUpdate     = time() - 24*3600;

        if($Day == 7){
            $TimeUpdate -= (time() - strtotime(date('Y-m-d').' 00:00:00'));
        }elseif($Day == 1){
            $TimeUpdate -= 86400;
        }

        $Order  = OrdersModel::where('status', 77)
                             ->where('time_accept','>=',time() - $this->time_limit)
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
        $Order->time_system_update = time();
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
            'time_create'     => time(),
            'status'          => 'WAITING'
        ]);
    }

    public function getChangeOverWeight($idLog){
        $LMongo         = new LMongo;
        $Journey        = $LMongo::collection('log_over_weight')
                                       ->where('status','WAITING')
                                       ->where('_id', new \MongoId($idLog))
                                       ->whereGt('total_weight',0)
                                       ->first();

        if(!empty($Journey)){
                Input::merge(
                    [   'TrackingCode' => $Journey['tracking_code'],
                        'total_weight' => $Journey['total_weight'],
                        'UserInfo'     => ['id' => 1, 'privilege'   => 2]
                    ]);
                $ChangeOrderCtrl    = new ChangeOrderCtrl;
                $Update = $ChangeOrderCtrl->postEdit(false);
                $DataUpdate = ['status' => 'SUCCESS','time_accept' => time()];

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
                    ->where('_id',new \MongoId($idLog))
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
}
