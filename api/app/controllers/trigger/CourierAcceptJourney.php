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
use metadatamodel\WebhookModel;

class CourierAcceptJourney extends \BaseController {
    
    private $__Lading, $__Logjourney, $__NewStatus;
    
    private $__Result, $__LogOutput;

    private $group_status   = [];
    private $update         = [];
    private $list_user_no_return = [84975];
    private $bonus          = 0;

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

        //Check xem đã xử lý hành trình mới nhất của mã này chưa
        if($this->check_log_process()){
            $LMongo::collection('log_journey_lading')
                ->where('_id',new \MongoId($this->__Logjourney['_id']))
                ->update([
                    'error_log' => [
                        'message'           => 'Đã có hành trình xử lý mới, bỏ qua ko xử lý hành trình này'
                    ],
                    'accept'        => 2,
                    'time_error'    => $this->time(),
                    'time_update'   => $this->time()
                ]);
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
            // EMS hoặc dv quốc tế
            if($this->__Lading->courier_id == 8 || (in_array($this->__Lading->service_id, [8,9]))){
                return $this->_process(2);
            }else{
                $LMongo::collection('log_journey_lading')
                    ->where('_id',new \MongoId($this->__Logjourney['_id']))
                    ->update([
                        'error_log' => [
                            'status_lading'     => $this->__Lading->status,
                            'message'           => 'Hãng vận chuyển không chính xác'
                        ],
                        'accept'        => 2,
                        'time_error'    => $this->time(),
                        'time_update'   => $this->time()
                    ]);

                return Response::json( array(
                    'error'         => 'status_error',
                    'error_message' => 'Hãng vận chuyển không chính xác',
                    'data'          => $this->__Logjourney['input']
                ) );
            }
        }
        
        return $this->_process(1);
    }

    private function check_log_process(){
        $LMongo         = new \LMongo;
        return $LMongo::collection('log_journey_lading')
            ->where('accept',1)->where('tracking_code', $this->__Logjourney['tracking_code'])
            ->whereGte('time_create', $this->__Logjourney['time_create'])->count();
    }

    function getJourney($idLog = ''){
        $LMongo         = new \LMongo;
        $this->__Logjourney = $LMongo::collection('log_journey_lading')
                ->whereIn('accept',[0,7]);

        $page           = Input::has('page')    ? (int)Input::get('page')   : null;
        if(isset($page)){
            $this->__Logjourney    = $this->__Logjourney->whereMod('tracking_number', 10, $page);
        }

        if(!empty($idLog)){
            $this->__Logjourney    = $this->__Logjourney->where('_id', new \MongoId($idLog));
        }

        $this->__Logjourney = $this->__Logjourney->orderBy('priority', 'asc')
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

        //Check xem đã xử lý hành trình mới nhất của mã này chưa
        if($this->check_log_process()){
            $LMongo::collection('log_journey_lading')
                ->where('_id',new \MongoId($this->__Logjourney['_id']))
                ->update([
                    'error_log' => [
                        'message'           => 'Đã có hành trình xử lý mới, bỏ qua ko xử lý hành trình này'
                    ],
                    'accept'        => 2,
                    'time_error'    => $this->time(),
                    'time_update'   => $this->time()
                ]);
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
            'ctp'       => 10,
            'ttc'       => 11,
            'ws'        => 1,
            'bxm'       => 12,
            'njv'       => 14,
            'ups'       => 15,
            'tnt'       => 16,
            'dhl'       => 17,
            'dpex'      => 18,
            'sfex'      => 19,
            'fedex'     => 20,
            'usps'      => 21,
            'sbp'       => 22,
            'wt'        => 1
        );
        
        return isset($Courier[$this->__Logjourney['input']['username']]) ? $Courier[$this->__Logjourney['input']['username']] : false;
        
    }

    private function _getOrder(){
        $Status     = Input::has('status')      ? (int)Input::get('status')                         : (int)Input::json()->get('status');

        $OrdersModel    = new OrdersModel;
        $sCode          = (!preg_match("/sc/i", strtolower($this->__Logjourney['tracking_code'])) ? 'SC' : '') . $this->__Logjourney['tracking_code'];

        $this->__Lading = $OrdersModel::where('tracking_code',$sCode);

        if(in_array($Status,[21])){
            $this->__Lading = $this->__Lading->where('time_accept',0);
        }else{
            $this->__Lading = $this->__Lading->where('time_accept','>=',$this->time() - 86400*90);
        }

        return $this->__Lading  = $this->__Lading->first(['id','domain','status','time_update','num_delivery','total_weight','tracking_code', 'courier_tracking_code','from_user_id','from_country_id','from_city_id','from_district_id','from_ward_id','from_address',
                                                                'service_id','domain','time_accept','time_pickup','from_user_id', 'total_weight','to_address_id','courier_id','postman_id','to_country_id','to_city_id','to_district_id',
                                                                'to_phone','verify_id','time_create','time_accept', 'time_pickup','time_approve','time_success', 'estimate_delivery', 'courier_estimate', 'product_name']);
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
                    '_error'        => true,
                    'error'         => 'status_courier_empty',
                    'error_message' => 'Không tìm thấy trạng thái này trong dữ liệu đồng bộ',
                    'data'          => $this->__Logjourney['input']
                ) );
            }else{
                return array(
                    '_error'        => true,
                    'error'         => 'status_courier_empty',
                    'error_message' => 'Không tìm thấy trạng thái này trong dữ liệu đồng bộ',
                    'data'          => $this->__Logjourney['input']
                );
            }

        }

        $this->__NewStatus = $dbCourierStatus->sc_status;

        //Check nếu đang hoàn thì nhận hành trình trạng thái 300,400
        if($this->__Lading->status == 62){
            if(in_array($this->__Logjourney['input']['Status'], [300,400])){
                $this->__NewStatus  = 62;
            }
        }

        $GroupStatus = array();
        $StatusOrderCtrl    = new StatusOrderCtrl;
        Input::merge(['group' => 4]);
        $dbStatusGroup      = $StatusOrderCtrl->getStatusgroup(false);
        if(empty($dbStatusGroup)){
            if($json){
                return Response::json( array(
                    '_error'        => true,
                    'error'         => 'group_status_empty',
                    'error_message' => 'Lấy nhóm trạng thái thất bại',
                    'data'          => $this->__Logjourney['input']
                ) );
            }else{
                return array(
                    '_error'        => true,
                    'error'         => 'group_status_empty',
                    'error_message' => 'Lấy nhóm trạng thái thất bại',
                    'data'          => $this->__Logjourney['input']
                );
            }
        }

        foreach($dbStatusGroup as $value){
            if(!isset($this->group_status[(int)$value['id']])){
                $this->group_status[(int)$value['id']] = [];
            }
            foreach($value['group_order_status'] as $v){
                $GroupStatus[$v['order_status_code']]           = $v['group_status'];
                $this->group_status[(int)$value['id']][]        = (int)$v['order_status_code'];
            }
        }

        // Trạng thái kéo về hoặc do nhân viên cập nhật  ko  có trạng thái phát thất bại
        if(in_array($this->__NewStatus, $this->group_status[29]) && isset($this->__Logjourney['UserId']) && (int)$this->__Logjourney['UserId'] == 1){
            $LMongo::collection('log_journey_lading')
                ->where('_id',new \MongoId($this->__Logjourney['_id']))
                ->update([
                    'error_log' => [
                        'status_lading'     => $this->__Lading->status,
                        'new_status_lading' => null,
                        'message'           => 'Trạng thái giao thất bại không được cập nhât !'
                    ],
                    'accept'        => 2,
                    'time_error'    => $this->time(),
                    'time_update'   => $this->time()
                ]);

                $Response   = [
                    '_error'        => true,
                    'error'         => 'not_update_status_delivery_fail',
                    'error_message' => 'Không tìm thấy trạng thái này trong dữ liệu đồng bộ',
                    'data'          => $this->__Logjourney['input']
                ];

            return $json ? Response::json($Response) : $Response;
        }

        //check truong hop thay doi trang thai cuoi ve trang thai da chuyen hoan hoac nguoc lai
        if( in_array($this->__Lading->status, array(52,53) ) && in_array($this->__NewStatus ,[66,67])){
            $this->__NewStatus = 71;
        }elseif(in_array((int)$this->__Lading->status, [62,66,67]) && in_array($this->__NewStatus, array(52,53)) ){
            $this->__NewStatus = 70;
        }

        if($this->__NewStatus == 52){
            $GroupOrderStatusModel  = new GroupOrderStatusModel;
            $ListStatus = $GroupOrderStatusModel::where('group_status',29)->lists('order_status_code');

            if(!empty($ListStatus) && in_array((int)$this->__Lading->status, $ListStatus)){
                $this->__NewStatus == 53;
            }
        }

        //Check Phát lại lần 2  ít nhất sau 4h viettel
        if(in_array($this->__NewStatus, $this->group_status[29])){
            if(in_array($this->__Lading->status, $this->group_status[29])){
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
                            '_error'        => true,
                            'error'         => 'status_error',
                            'error_message' => 'Trạng thái phát thất bại đã được cập nhật cách đây chưa đủ 4 tiếng',
                            'data'          => $this->__Logjourney['input'],
                            'newStatus'     => $this->__NewStatus
                        ) );
                    }else{
                        return array(
                            '_error'        => true,
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

            if(!isset($this->__Logjourney['UserId']) && ($this->__Lading->status == 81 || $this->__update_pipe_journey()) && $this->__NewStatus != 58){// nếu có yêu cầu phát lại, + phát không thành công => duyệt hoàn
                $this->__NewStatus  = 61;
            }

            if(!isset($this->__Logjourney['UserId']) && ($this->__Lading->status == 62) && ($this->__Logjourney['input']['Status'] == 505)){// nếu đang hoàn gửi tt 505  do đang hoàn bị vận hành ycpl  => phát thất bại thì lại duyệt hoàn lại
                $this->__NewStatus  = 61;
            }
        }

        /* Check Ninjavan
            cậu chặn cho tớ phần nếu tt đã CN về phát ko thành công/ chờ xử lý ý
            thì ko nhận đc trạng thái đang phát hang/ phát lại lần 2  => Lê SC
        */
        if($this->__Lading->courier_id == 14){
            if($this->__Lading->status == 77 && $this->__NewStatus == 76){
                $LMongo::collection('log_journey_lading')
                    ->where('_id',new \MongoId($this->__Logjourney['_id']))
                    ->update([
                        'error_log' => [
                            'status_lading'     => $this->__Lading->status,
                            'new_status_lading' => $this->__NewStatus,
                            'message'           => 'Trạng thái bị từ chối do yêu cầu vận hành,Hồng Lê SC'
                        ],
                        'accept'        => 2,
                        'time_error'    => $this->time(),
                        'time_update'   => $this->time()
                    ]);

                if($json){
                    return Response::json( array(
                        '_error'        => true,
                        'error'         => 'status_error',
                        'error_message' => 'Trạng thái bị từ chối do yêu cầu vận hành,Hồng Lê SC',
                        'data'          => $this->__Logjourney['input'],
                        'newStatus'     => $this->__NewStatus
                    ) );
                }else{
                    return array(
                        '_error'        => true,
                        'error'         => 'status_error',
                        'error_message' => 'Trạng thái bị từ chối do yêu cầu vận hành,Hồng Lê SC',
                        'data'          => $this->__Logjourney['input'],
                        'newStatus'     => $this->__NewStatus
                    );
                }
            }
        }


        /*    Check Kerry
            Sau khi phát thất bại lần 1 thì sẽ được cập nhật về trạng thái đang phát hàng/ chờ phát lại lần 2
            Rồi sau khi phát lại lần 2 mà thất bại mới nhận trạng thái phát không thành công/ chờ xử lý
         */
            if($this->__Lading->courier_id == 11){
                if(in_array($this->__Lading->status, $this->group_status[29]) && in_array($this->__NewStatus, $this->group_status[28])){
                    $this->__NewStatus  = 79;
                }

                if($this->__NewStatus  == 79 && in_array($this->__NewStatus, $this->group_status[29])){
                    $this->__NewStatus  = 77;
                }

                if($this->__NewStatus  == 79 && in_array($this->__NewStatus, $this->group_status[28])){
                    $this->__NewStatus  = 79;
                }
            }

        /*
         * End
         */

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
                    '_error'        => true,
                    'error'         => 'status_error',
                    'error_message' => 'Trạng thái mới không tồn tại',
                    'data'          => $this->__Logjourney['input'],
                    'newStatus'     => $this->__NewStatus
                ) );
            }else{
                return array(
                    '_error'        => true,
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
                    '_error'        => true,
                    'error'         => 'status_error',
                    'error_message' => 'Trạng thái đi ngược quy trình hoặc đã ở trạng thái cuối',
                    'data'          => $this->__Logjourney['input'],
                    'newStatus'     => $this->__NewStatus
                ) );
            }else{
                return array(
                    '_error'        => true,
                    'error'         => 'status_error',
                    'error_message' => 'Trạng thái đi ngược quy trình hoặc đã ở trạng thái cuối',
                    'data'          => $this->__Logjourney['input'],
                    'newStatus'     => $this->__NewStatus
                );
            }

        }

        /*
        if(isset($this->__Logjourney['UserId']) && in_array( $this->__NewStatus, $this->group_status[33]) && $this->__Lading->domain == 'boxme.vn'){
            $UpdateBoxme    = $this->request_cancel_boxme();
            if(!$UpdateBoxme){
                $LMongo::collection('log_journey_lading')
                    ->where('_id',new \MongoId($this->__Logjourney['_id']))
                    ->update([
                        'error_log' => [
                            'status_lading'     => $this->__Lading->status,
                            'new_status_lading' => $this->__NewStatus,
                            'message'           => 'Không cập nhật được trạng thái đến boxme'
                        ],
                        'accept'        => 2,
                        'time_error'    => $this->time(),
                        'time_update'   => $this->time()
                    ]);

                return array(
                    '_error'        => true,
                    'error'         => 'api_boxme_error',
                    'error_message' => 'Không cập nhật được trạng thái đến boxme',
                    'data'          => $this->__Logjourney['input'],
                    'newStatus'     => $this->__NewStatus
                );
            }

            if(!$UpdateBoxme['Success']){
                $LMongo::collection('log_journey_lading')
                    ->where('_id',new \MongoId($this->__Logjourney['_id']))
                    ->update([
                        'error_log' => [
                            'status_lading'     => $this->__Lading->status,
                            'new_status_lading' => $this->__NewStatus,
                            'message'           => $UpdateBoxme['Message'],
                            'result'            => $UpdateBoxme
                        ],
                        'accept'        => 2,
                        'time_error'    => $this->time(),
                        'time_update'   => $this->time()
                    ]);

                return array(
                    '_error'        => true,
                    'error'         => 'update_boxme_error',
                    'error_message' => $UpdateBoxme['Message'],
                    'data'          => $this->__Logjourney['input'],
                    'newStatus'     => $this->__NewStatus
                );
            }
        }
        */

        
        // Process database
        $this->_process_database();

        // Xử lý thành công
        if($this->__Result['ERROR'] == 'SUCCESS'){
            $this->__LogOutput['Jouney']['old_status']  = $this->__Lading->status;
            $DataUpdate = [
                'code'          => 'SUCCESS',
                'log_output'    => $this->__LogOutput,
                'accept'        => 1,
                'time_success'  => $this->time(),
                'time_update'   => $this->time(),
            ];

            if(isset($UpdateBoxme)){
                $DataUpdate['result_report']    = $UpdateBoxme;
            }

            $LMongo::collection('log_journey_lading')
                ->where('_id',new \MongoId($this->__Logjourney['_id']))
                ->update($DataUpdate);

            // insert log vượt cân

            if(isset($this->__Logjourney['weight']) && $this->__Logjourney['weight'] > 0 && $this->__Logjourney['weight'] != $this->__Lading->total_weight){
                $IdLog = $this->InsertLogWeight();
                $this->PredisWeight((string)$IdLog);
            }

            if($this->__NewStatus == 21 && isset($this->__Logjourney['UserId'])){
                $this->PredisAcceptLading($this->__Lading->tracking_code);
            }


            $QueueId = Input::get('queue_id');
            if (!empty($QueueId) && (int)$QueueId > 0) {
                try {
                    \QueueModel::where('id', (int)$QueueId)->update(['view'=> 1]);
                } catch (Exception $e) {
                    
                }
            }

            if($json){
                return Response::json( array(
                    '_error'        => false,
                    'error'         => 'success',
                    'error_message' => 'Cập nhật lịch trình - trạng thái thành công.',
                    //'data'          => $this->__Logjourney['input']
                ) );
            }else{
                return array(
                    '_error'        => false,
                    'error'         => 'success',
                    'error_message' => 'Cập nhật lịch trình - trạng thái thành công.',
                    //'data'          => $this->__Logjourney['input']
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
                '_error'        => false,
                'error'         => 'fail_database',
                'error_message' => $this->__Result,
                'data'          => $this->__Logjourney['input']
            ) );
        }else{
            return array(
                '_error'        => false,
                'error'         => 'fail_database',
                'error_message' => $this->__Result,
                'data'          => $this->__Logjourney['input']
            );
        }
    }
    
    //get user notice app
    private function getUserapp($id){
        if($id > 0){
            $deviceToken     = \sellermodel\UserInfoModel::where('user_id',$id)->first();
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

    private function _process_database(){
        /*if($this->__NewStatus != $this->__Lading->status && !isset($this->__Logjourney['UserId'])){
            if($this->__NewStatus == 51){
                if($this->__Lading->total_amount > 5000000){ // với đơn hàng gía trị cao, báo khách hàng trước khi phát
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
        }*/

        if($this->__NewStatus != $this->__Lading->status && !isset($this->__Logjourney['UserId'])){
            if($this->__NewStatus == 51){ /// Dang phat hang
                if($this->__Lading->total_amount > 5000000){ // với đơn hàng giá trị cao, báo khách hàng trước khi phát
                    try{
                        $this->SendSMS();

                        $ZaloCtrl = new \ZmsController();
                        $ZaloCtrl->ZaloDelivering($this->__Lading, $this->__NewStatus);

                    }catch (\Exception $e){

                    }

                }elseif($this->__Lading->to_district_id > 0){// huyện xã tỉ lệ chuyển hoàn cao
                    $District        = \DistrictModel::where('id', $this->__Lading->to_district_id)->remember('60')->first();
                    if(isset($District->id) && $District->return_percent > 10){
                        $Content = 'Don hang '.$this->__Lading->tracking_code.' da giao di phat,buu ta se lien lac phat hang
                                    trong thoi gian som nhat. Lien he tong đai 1900 636030 de duoc ho tro them thong tin';
                        try{
                            $this->SendSMS($Content);
                        }catch (\Exception $e){

                        }
                    }
                }
                

            }elseif(in_array($this->__NewStatus,$this->group_status[29]) ){
                // send sms  khi đơn hang phát không thành công
                if (!empty($this->__Lading->postman_id)) {
                    try{
                        $this->ReportFail();
                    }catch (\Exception $e){

                    }
                }
                
                if($this->__Lading->total_amount > 5000000){
                    try {
                        $ZaloCtrl = new \ZmsController();
                        $ZaloCtrl->ZaloDeliveryFail($this->__Lading, $this->__NewStatus);
                    } catch (Exception $e) {

                    }
                }
                
                
                
                
            }elseif(in_array($this->__NewStatus,$this->group_status[30])){
                // Phat TC
                
                    $Detail = $this->__order_detail();
                    
                    if ($Detail->money_collect == 0) {
                        try {
                            $ZaloCtrl = new \ZmsController();
                            $ZaloCtrl->ZaloDeliverySuccess($this->__Lading, $this->__NewStatus);
                        } catch (Exception $e) {
                            
                        }
                    }
                
                
                
            }elseif(in_array($this->__NewStatus,$this->group_status[27])){
                // Da lay hang

                if($this->__Lading->total_amount > 5000000){
                    try {
                        $ZaloCtrl = new \ZmsController();
                        $ZaloCtrl->ZaloOrderPicked($this->__Lading, $this->__NewStatus);
                    } catch (Exception $e) {

                    }
                }

                
                
            }else if(in_array($this->__NewStatus, [56,74,77,75])){
                // Phát khong thành công chờ xl
                try {
                   // $AppNotice = new \AppNotificationController();
                  // $AppNotice->SendNoticeDeliveryFailed($this->__Lading, $this->__NewStatus);

                    $ZaloCtrl = new \ZmsController();
                    $ZaloCtrl->ZaloOrderPicked($this->__Lading, $this->__NewStatus);
                } catch (Exception $e) {
                    
                }
                

            }
            //else if(in_array($this->__NewStatus, [31,32,33,34])){
                // Lấy không thành công chờ xl

               // try{
                  //  $AppNotice = new \AppNotificationController();
                   // $AppNotice->SendNoticePickupFailed($this->__Lading, $this->__NewStatus);
               // }catch (\Exception $e){

               // }

                
           // }
        }

        $DB = DB::connection('orderdb');
        $DB->beginTransaction();

        try {
            if($this->__Logjourney['input']['Status'] == 505 &&  !empty($this->__Logjourney['input']['params']['DeliverID']) && !empty($this->__Logjourney['input']['params']['DeliverPhone'])){
                try{
                    $this->__update_postman();
                }catch (\Exception $e){

                }
            }

            if(($this->__NewStatus < 40 || $this->__NewStatus >= 50) && isset($this->__Logjourney['input']['params']['MABUUCUC']) && !empty($this->__Logjourney['input']['params']['MABUUCUC'])){
                try{
                    $this->__update_postoffice();
                }catch (\Exception $e){

                }
            }

            $this->_insert_order_journey();
            $this->_update_order_status();
            $this->__InsertLogBoxme($this->__Lading->domain , $this->__Lading->tracking_code, $this->__Lading->courier_tracking_code, $this->__NewStatus);

            $abc = '123';

            try{
                $this->__InsertLogWebHook($this->__Lading->domain , $this->__Lading->tracking_code, $this->__Lading->courier_tracking_code, $this->__NewStatus);
            }catch(\Exception $e){
                var_dump($e->getMessage());
            }
            
            
            

            // Cần xử lý
            if(in_array($this->__NewStatus, [56,57,74,77])){
                // Cần xử lý  - Khách hàng
                $this->__insert_log_problem(1);
            }elseif(in_array($this->__NewStatus, $this->group_status[26])){
                // Lấy thất bại
                $this->__insert_log_problem(3);
            }elseif(in_array($this->__NewStatus, $this->group_status[31])){
                // Chờ xác nhận hoàn
                $this->__insert_log_problem(2);
            }

            //Xử lý cập nhật cần xử lý
            if($this->__NewStatus >= 36 || in_array($this->__NewStatus, $this->group_status[26]) || in_array($this->__NewStatus, $this->group_status[33])){
                // Từ trạng thái đã lấy hàng
                $this->__process_log_problem();
            }


            //Check giao chậm
            if(!in_array($this->__NewStatus,array_merge($this->group_status[23],$this->group_status[24],$this->group_status[25],$this->group_status[26],$this->group_status[33]))){
                $Id = $this->_insert_log_delivery();
                if(!empty($Id)){
                    try{
                        $this->PredisProcessJourney((string)$Id);
                    }catch (\Exception $e){

                    }
                }
            }

            //Check lấy chậm
            if($this->__NewStatus <= 52  || ($this->__Lading->time_pickup == 0 && isset($this->update['time_pickup']) && $this->update['time_pickup'] > 0)){
                $Id = $this->_insert_log_pickup();
                if(!empty($Id)){
                    try{
                        $this->PredisProcessPickup((string)$Id);
                    }catch (\Exception $e){

                    }
                }
            }

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

    private function _insert_log_delivery(){
        $LMongo         = new \LMongo;
        $Log            = $LMongo::collection('log_journey_delivery');
        $Log            = $Log->where('tracking_code', $this->__Lading->tracking_code)->first();

        $DataUpdate = [
            'active'                    => 0,
            'time_accept'               => ((isset($this->update['time_accept'])) ? $this->update['time_accept'] : $this->__Lading->time_accept),
            'time_update'               => $this->time()
        ];

        if($Log){ // Đã tồn tại
            /*if(in_array($this->__NewStatus,$this->group_status[29])){
                if((!isset($Log['first_fail_time']) || empty($Log['first_fail_time']))){
                    $DataUpdate['first_fail_time']  = $this->time();
                }elseif((!isset($Log['second_fail_time']) || empty($Log['second_fail_time']))){
                    $DataUpdate['second_fail_time']  = $this->time();
                }elseif((!isset($Log['third_fail_time']) || empty($Log['third_fail_time']))){
                    $DataUpdate['third_fail_time']  = $this->time();
                }

            }

            if(in_array($this->__NewStatus, [76,79])){
                if(!isset($Log['first_fail_time']) || empty($Log['first_fail_time'])){
                    $DataUpdate['first_fail_time']  = $this->time();
                }
            }*/

            $LMongo::collection('log_journey_delivery')
                ->where('_id',new \MongoId($Log['_id']))
                ->update($DataUpdate);
        }else{ // Chưa tồn tại
            $DataUpdate['order_id']             = $this->__Lading->id;
            $DataUpdate['tracking_code']        = $this->__Lading->tracking_code;
            $DataUpdate['service_id']           = (int)$this->__Lading->service_id;
            $DataUpdate['from_user_id']         = (int)$this->__Lading->from_user_id;
            $DataUpdate['domain']               = $this->__Lading->domain;
            $DataUpdate['from_country_id']      = (int)$this->__Lading->from_country_id;
            $DataUpdate['to_country_id']        = (int)$this->__Lading->to_country_id;
            $DataUpdate['from_city_id']         = (int)$this->__Lading->from_city_id;
            $DataUpdate['from_district_id']     = (int)$this->__Lading->from_district_id;
            $DataUpdate['from_ward_id']         = (int)$this->__Lading->from_ward_id;
            $DataUpdate['to_district_id']       = (int)$this->__Lading->to_district_id;
            $DataUpdate['estimate_delivery']    = (int)$this->__Lading->estimate_delivery;
            $DataUpdate['time_create']          = $this->time();
            $DataUpdate['auth']                 = 0;
            $DataUpdate['status']               = 0;
            $DataUpdate['first_fail_time']      = 0;
            $DataUpdate['second_fail_time']     = 0;
            $DataUpdate['third_fail_time']      = 0;
            $DataUpdate['first_promise_time']   = 0;
            $DataUpdate['second_promise_time']  = 0;
            $DataUpdate['third_promise_time']   = 0;
            $DataUpdate['to_city_id']           = 0;
            $DataUpdate['to_location']          = 0;
            $Log['_id'] = $LMongo::collection('log_journey_delivery')->insert($DataUpdate);
        }

        return $Log['_id'];
    }

    private function _insert_log_pickup(){
        $LMongo         = new \LMongo;
        $Log            = $LMongo::collection('log_journey_pickup');
        $Log            = $Log->where('tracking_code', $this->__Lading->tracking_code)->first();

        $DataUpdate = [
            'active'                    => 0,
            'time_accept'               => ((isset($this->update['time_accept'])) ? $this->update['time_accept'] : $this->__Lading->time_accept),
            'time_pickup'               => ((isset($this->update['time_pickup'])) ? $this->update['time_pickup'] : $this->__Lading->time_pickup),
            'time_update'               => $this->time()
        ];

        if($Log){
            $LMongo::collection('log_journey_pickup')
                ->where('_id',new \MongoId($Log['_id']))
                ->update($DataUpdate);
        }else{ // Chưa tồn tại
            $DataUpdate['order_id']                 = $this->__Lading->id;
            $DataUpdate['tracking_code']            = $this->__Lading->tracking_code;
            $DataUpdate['service_id']               = (int)$this->__Lading->service_id;
            $DataUpdate['from_user_id']             = (int)$this->__Lading->from_user_id;
            $DataUpdate['from_address_id']          = (int)$this->__Lading->from_address_id;
            $DataUpdate['domain']                   = $this->__Lading->domain;
            $DataUpdate['from_country_id']          = (int)$this->__Lading->from_country_id;
            $DataUpdate['to_country_id']            = (int)$this->__Lading->to_country_id;
            $DataUpdate['from_city_id']             = (int)$this->__Lading->from_city_id;
            $DataUpdate['from_district_id']         = (int)$this->__Lading->from_district_id;
            $DataUpdate['from_ward_id']             = (int)$this->__Lading->from_ward_id;
            $DataUpdate['from_address']             = (int)$this->__Lading->from_address;
            $DataUpdate['time_create']              = $this->time();
            $DataUpdate['status']                   = 0;
            $DataUpdate['promise_pickup_time']      = 0;
            $DataUpdate['from_location']            = 0;
            $Log['_id'] = $LMongo::collection('log_journey_pickup')->insert($DataUpdate);
        }

        return $Log['_id'];
    }

    private function __insert_log_problem($type){
        // 1 : Phát thất bại
        // 2 : Chờ xác nhận chuyển hoàn
        // 3 : Lấy không thành công
        // 4 : Đơn hàng vượt cân

        try{
            $Obj = \ordermodel\OrderProblemModel::insert([
                'order_id'          => $this->__Lading->id,
                'tracking_code'     => $this->__Lading->tracking_code,
                'user_id'           => (int)$this->__Lading->from_user_id,
                'type'              => $type,
                'status'            => 0,
                'action'            => 0,
                'reason'            => $this->__Logjourney['input']['Note'],
                'postman_phone'     => isset($this->__Logjourney['input']['params']['DeliverPhone']) ? $this->__Logjourney['input']['params']['DeliverPhone'] : '',
                'postman_name'      => isset($this->__Logjourney['input']['params']['DeliverName']) ? $this->__Logjourney['input']['params']['DeliverName'] : '',
                'time_create'       => time(),
                'time_update'       => time()
            ]);
        }catch (\Exception $e){
            return false;
        }

        return $Obj;
    }

    private function __process_log_problem(){
        $Obj    = \ordermodel\OrderProblemModel::where('tracking_code', $this->__Lading->tracking_code)
                                               ->where('active',1)
                                               ->whereIn('status', [0,1,3])
                                               ->whereIn('type',[1,2,3,4])
                                               ->get()->toArray();

        if(empty($Obj)) return false;

        $Update = [];
        foreach($Obj as $val) {
            $Update = [];

            if ($val['status'] == 0) {
                //Chưa xử lý
                if (in_array($this->__NewStatus, array_merge($this->group_status[30], $this->group_status[36]))) {
                    $Update['active'] = 0;
                }

                if($val['type'] == 1 && in_array($this->__NewStatus, array_merge($this->group_status[31],$this->group_status[32]))){
                    //Phát thất bại => đơn hàng đã chuyển qua trạng thái hoàn
                    $Update['active'] = 0;
                }

                if($val['type'] == 2 && in_array($this->__NewStatus, $this->group_status[32])){
                    $Update['active'] = 0;
                }

                if($val['type'] == 4 && in_array($this->__NewStatus, $this->group_status[33])){
                    //Vượt cân và đã hủy
                    $Update['active'] = 0;
                }

                if($val['type'] == 3){
                    //Lấy thất bại
                    if(in_array($this->__NewStatus, $this->group_status[33]) || $this->__NewStatus >= 36){
                        // Đã lấy hàng hoặc đã hủy
                        $Update['active'] = 0;
                    }
                }

            } else {
                if (in_array($val['type'], [1, 2])) {
                    // Xử lý đơn hàng phát thất bại  || Chờ xác nhận chuyển hoàn
                    if ($val['action'] == 1) {
                        //Người bán chọn yêu cầu phát lại
                        if (in_array($this->__NewStatus, $this->group_status[29])) {
                            //Trạng thái mới => Phát thất bại
                            $Update['status'] = 3;
                        } elseif (in_array($this->__NewStatus, $this->group_status[30])) {
                            //Trạng thái mới Phát Thành công
                            $Update['status'] = 2;
                        } elseif (in_array($this->__NewStatus, array_merge($this->group_status[36], $this->group_status[32]))) {
                            //Trạng thái mới Đang hoàn hoặc hoàn thành công
                            $Update['status'] = 3;
                        }
                    } elseif ($val['action'] == 2) {
                        //Người bán chọn yêu cầu chuyển hoàn
                        if (in_array($this->__NewStatus, $this->group_status[36])) {
                            //Trạng thái mới Hoàn Thành Công
                            $Update['status'] = 2;
                        }
                    }
                }elseif ($val['type'] == 3){
                    // Lấy thất bại
                   if ($val['action'] == 1) {
                        // Chọn yêu cầu lấy lại
                        if($this->__NewStatus >= 36){ // Đã lấy hàng
                            $Update['status'] = 2;
                        }elseif(in_array($this->__NewStatus, $this->group_status[26])){
                            $Update['status'] = 3;
                        }
                    }else{
                       if(in_array($this->__NewStatus, $this->group_status[26])){
                           $Update['status'] = 2;
                       }
                   }
                }
            }

            if(!empty($Update)){
                try{
                    \ordermodel\OrderProblemModel::where('order_id', $val['order_id'])
                        ->where('type',$val['type'])->where('active',1)
                        ->update($Update);
                }catch (\Exception $e){

                }

            }
        }

        return true;
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
        $this->update['is_changed'] = 1;
        $this->update['status']         = $this->__NewStatus;
        $this->update['time_update']    = $this->time();

        if(isset($this->__Logjourney['input']['TrackingOrder']) 
            && $this->__Logjourney['input']['TrackingOrder'] != '' 
            && $this->__Logjourney['input']['TrackingOrder'] != $this->__Logjourney['input']['TrackingCode'])
        {
            $this->update['courier_tracking_code'] = $this->__Logjourney['input']['TrackingOrder'];
        }

        if(isset($this->__Logjourney['input']['params']['E_CODE']) && !empty($this->__Logjourney['input']['params']['E_CODE'])){
            $this->update['courier_tracking_code'] = $this->__Logjourney['input']['params']['E_CODE'];
        }

            // Thời gian duyệt
        if($this->__NewStatus == 21 && $this->__Lading->time_accept == 0){
            $this->update['time_accept']  = $this->time();
        }
        if($this->__Lading->time_approve == 0){
             $this->update['time_approve']  = $this->__Lading->time_accept + 60;
        }

        // Thời gian ở trạng thái cuối
        if(in_array($this->__NewStatus,[52,53,66,67])){
            $this->update['time_success'] = $this->time();
        }
        
        // Thời gian về kho

        if((in_array($this->__NewStatus,[36,37]) || ($this->__NewStatus >= 40 && $this->__NewStatus != 78)) && $this->__Lading->time_pickup == 0){

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
            $OrdersModel    = $OrdersModel::where('time_create','>=',$this->time() - $this->time_limit)->where('time_accept',0);
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
                return $Update->increment('num_delivery',1, $this->update);}
            //}elseif(in_array($this->__NewStatus,[76,79]) && $this->__Lading->num_delivery == 1){
              //  return $Update->increment('num_delivery',1, $this->update);
            //}
        }

        if(!isset($this->__Logjourney['UserId']) && in_array($this->__NewStatus,array_merge($this->group_status[29],$this->group_status[30],$this->group_status[36]))){
            try{
                $CustomerAdmin  = \omsmodel\CustomerAdminModel::firstOrCreate(['user_id' => (int)$this->__Lading->from_user_id]);
                // update first fail delivery
                if(in_array($this->__NewStatus,$this->group_status[29])){
                    if(empty($CustomerAdmin->first_fail_order_time)){
                        $CustomerAdmin->first_fail_order_time   = $this->time();
                        $CustomerAdmin->fail_tracking_code      = $this->__Lading->tracking_code;
                    }
                    $CustomerAdmin->last_fail_order_time        = $this->time();
                }

                if(in_array($this->__NewStatus,$this->group_status[30])){
                    if(empty($CustomerAdmin->first_success_order_time)){
                        $CustomerAdmin->first_success_order_time        = $this->time();
                        $CustomerAdmin->first_success_tracking_code     = $this->__Lading->tracking_code;
                    }
                    $CustomerAdmin->last_success_order_time             = $this->time();
                }

                if(in_array($this->__NewStatus,$this->group_status[36])){
                    if(empty($CustomerAdmin->first_return_order_time)){
                        $CustomerAdmin->first_return_order_time        = $this->time();
                    }
                    $CustomerAdmin->last_return_order_time             = $this->time();
                }

                $CustomerAdmin->save();
        }catch (\Exception $e){

            }
        }
        $updateResult = $Update->update($this->update);
        
        $this->update['id'] = $this->__Lading->id;
        $this->PushSyncElasticsearch('bxm_orders', 'orders', 'updated', $this->update);


        return $updateResult;
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

    private function __update_postoffice(){
        try{
            $OrderRefer = \ordermodel\PostOfficeModel::firstOrNew(['order_id' => $this->__Lading->id]);
            if(!isset($OrderRefer->id)){
                $OrderRefer->time_create    = $this->time();
                $OrderRefer->tracking_code  = $this->__Lading->tracking_code;
                $OrderRefer->courier_id     = $this->__Lading->courier_id;
            }

            if($this->__NewStatus < 40){
                $OrderRefer->from_postoffice_code        = strtoupper(trim($this->__Logjourney['input']['params']['MABUUCUC']));
            }elseif($this->__NewStatus >= 50){
                $OrderRefer->to_postoffice_code        = strtoupper(trim($this->__Logjourney['input']['params']['MABUUCUC']));
            }

            $OrderRefer->time_update            = $this->time();
            $OrderRefer->save();
        }catch(\Exception $e){

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

    private function request_cancel_boxme(){
        $Params = [
            'TrackingCode'       => $this->__Lading->tracking_code
        ];

        $Params   = json_encode($Params);
        $respond = \cURL::rawPost('http://seller.boxme.vn/api/cancel_order_from_shipchung?access_token=013392b3e5853bd8db45388a3c1cf7031f32aa4c9a1d2ea010adcf2348166a04',$Params);
        if(!$respond->body){
            return false;
        }else{
            $decode = json_decode($respond->body,true);
            return $decode;
        }
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
        $TimeUpdate = Input::has('time_update')     ? strtolower(trim(Input::get('time_update')))           : strtolower(trim(Input::json()->get('time_update')));
        

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
                    '_error'        => true,
                    'error'         => 'login_time_out',
                    'error_message' => 'Bạn chưa đăng nhập',
                    'data'          => []
                ) );
            }

            if($UserInfo['privilege'] == 0 && $Status == 67){
                return Response::json( array(
                    '_error'        => true,
                    'error'         => true,
                    'message'       => 'Trạng thái đi ngược quy trình hoặc đã ở trạng thái cuối',
                    'data'          => []
                ));
            }
        }else{
            $UserInfo['id'] = 1;
        }


        $_courier = [
            1  => 'vtp',
            2  => 'vnp',
            3  => 'ghn',
            4  => '123giao',
            5  => 'netco',
            6  => 'ghtk',
            7  => 'sc',
            8  => 'ems',
            9  => 'gts',
            11 => 'ttc',
            1  => 'ws',
            12 => 'bxm',
            14 => 'njv',
            15 => 'ups',
            16 => 'tnt',
            17 => 'dhl',
            18 => 'dpex',
            19 => 'sfex',
            20 => 'fedex',
            21 => 'usps',
            22 => 'sbp',
            23 => 'wt'
        ];
            

        if (!empty($_courier[$Courier])) {
            $Courier = $_courier[$Courier];
        }

        if(empty($TimeUpdate)){
            $TimeUpdate = $this->time();
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
            "time_update"   => $TimeUpdate
        ];

        $LMongo         = new \LMongo;
        try{
            $Id = $LMongo::collection('log_journey_lading')
                ->insert($this->__Logjourney);
            $this->__Logjourney['_id']  = $Id;
        }catch(\Exception $e){
            return Response::json( array(
                '_error'        => true,
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
                '_error'        => true,
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
                '_error'        => true,
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
                                     ->whereNotIn('from_user_id', $this->list_user_no_return)
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

        $BaseCtrl       = new \BaseCtrl;
        $ListCourier    = $BaseCtrl->getCourier(false);
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
        $Order  = OrdersModel::whereNotIn('status',[52,53,66,67,61,62,62,63,64,65,70,71,73,58])
                             ->whereNotIn('from_user_id', $this->list_user_no_return)
                             ->where('time_accept','>=',$this->time() - $this->time_limit)
                             ->where('num_delivery', '>=', 3)
                             ->where('time_update', '<=', $this->time() - 2*3600)
                             ->first(['id','tracking_code','status','courier_id']);

        if(!isset($Order->id)){
            return Response::json( array(
                'error'         => true,
                'error_message' => 'Đã duyệt hết'
            ) );
        }

        $BaseCtrl       = new \BaseCtrl;
        $ListCourier    = $BaseCtrl->getCourier(false);
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
                             ->whereNotIn('from_user_id', $this->list_user_no_return)
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

        $BaseCtrl       = new \BaseCtrl;
        $ListCourier    = $BaseCtrl->getCourier(false);
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

    private function __order_detail(){
        return \ordermodel\DetailModel::where('order_id', $this->__Lading->id)->first();
    }



    

    /*
     * Insert log_journey_notice
     */
    public function __InsertLogBoxme($Domain, $TrackingCode, $CourierTrackingCode, $Status){
        if(in_array($Domain, [ 'chodientu.vn','prostore.vn','juno.vn','www.ebay.vn'])){
            $Detail = $this->__order_detail();

            $LMongo         = new LMongo;
            $Id = $LMongo::collection('log_journey_notice')->insert([
                'tracking_code'             => $TrackingCode,
                'courier_tracking_code'     => isset($this->update['courier_tracking_code']) ? $this->update['courier_tracking_code'] : $CourierTrackingCode,
                'domain'                    => $Domain,
                'status'                    => (int)$Status,
                'time'                      => [
                    'time_create'       => $this->__Lading->time_create,
                    'time_accept'       => isset($this->update['time_accept']) ? $this->update['time_accept'] : $this->__Lading->time_accept,
                    'time_approve'      => $this->__Lading->time_approve,
                    'time_pickup'       => isset($this->update['time_pickup']) ? $this->update['time_pickup'] : $this->__Lading->time_pickup,
                    'time_success'      => isset($this->update['time_success']) ? $this->update['time_success'] : $this->__Lading->time_success
                ],
                'fee'           => [
                    'sc_pvc'            => $Detail->sc_pvc,
                    'sc_cod'            => $Detail->sc_cod,
                    'sc_pbh'            => $Detail->sc_pbh,
                    'sc_pvk'            => $Detail->sc_pvk,
                    'sc_discount_pvc'   => $Detail->sc_discount_pvc,
                    'sc_discount_pcod'  => $Detail->sc_discount_cod
                ],
                'weight'        => $this->__Lading->total_weight,
                'accept'        => 0,
                'time_create'   => $this->time()
            ]);

            $this->PredisAcceptBoxme((string)$Id);
        }

        return;
    }

    

    public function __InsertLogWebHook($Domain, $TrackingCode, $CourierTrackingCode, $Status){
        $GroupStatus        = [];
        $StatusOrderCtrl    = new StatusOrderCtrl;

        Input::merge(['group' => 3]);

        $dbStatusGroup      = $StatusOrderCtrl->getStatusgroup(false);

        foreach($dbStatusGroup as $value){
            foreach($value['group_order_status'] as $v){
                $GroupStatus[$v['order_status_code']] = $v['group_status'];
            }
        }
        
        $Error = "false";

        $StatusGroup = isset($GroupStatus[$Status]) ? $GroupStatus[$Status] : "";

        $Hook   = WebhookModel::getListHookByUser((int)$this->__Lading->from_user_id, $StatusGroup);
       
        
        
        
        if(!empty($Hook)){
            
            $Detail = $this->__order_detail();

            $LMongo         = new LMongo;
            $InsertData     = [
                'tracking_code'             => $TrackingCode,
                'courier_tracking_code'     => isset($this->update['courier_tracking_code']) ? $this->update['courier_tracking_code'] : $CourierTrackingCode,
                'domain'                    => $Domain,
                'status'                    => (int)$Status,
                'time'                      => [
                    'time_create'       => $this->__Lading->time_create,
                    'time_accept'       => isset($this->update['time_accept']) ? $this->update['time_accept'] : $this->__Lading->time_accept,
                    'time_approve'      => $this->__Lading->time_approve,
                    'time_pickup'       => isset($this->update['time_pickup']) ? $this->update['time_pickup'] : $this->__Lading->time_pickup,
                    'time_success'      => isset($this->update['time_success']) ? $this->update['time_success'] : $this->__Lading->time_success
                ],
                'fee'           => [
                    'sc_pvc'            => $Detail->sc_pvc,
                    'sc_cod'            => $Detail->sc_cod,
                    'sc_pbh'            => $Detail->sc_pbh,
                    'sc_pvk'            => $Detail->sc_pvk,
                    'sc_discount_pvc'   => $Detail->sc_discount_pvc,
                    'sc_discount_pcod'  => $Detail->sc_discount_cod
                ],
                'user_id'            => (int)$this->__Lading->from_user_id,
                'hook_info'          => $Hook->toArray(),
                'weight'        => $this->__Lading->total_weight,
                'accept'        => 0,
                'time_create'   => $this->time()
            ];

            $Id = $LMongo::collection('log_webhook_process')->insert($InsertData);
            $this->PushRabbitMQ('webhook_process', ['id' => (string)$Id]);
        }

            //$this->PredisAcceptBoxme((string)$Id);

        return;
    }


    /*
     * get group status
     */
    public function __get_group_status(){
        $StatusOrderCtrl    = new StatusOrderCtrl;
        Input::merge(['group' => 4]);
        $dbStatusGroup      = $StatusOrderCtrl->getStatusgroup(false);
        if(empty($dbStatusGroup)){
            return false;
        }

        foreach($dbStatusGroup as $value){
            if(!isset($this->group_status[(int)$value['id']])){
                $this->group_status[(int)$value['id']] = [];
            }
            foreach($value['group_order_status'] as $v){
                $this->group_status[(int)$value['id']][]        = (int)$v['order_status_code'];
            }
        }
        return true;
    }


    //Check + time sunday
    private function __check_date($StartTime, $LastTime){
        $StartDate  =  date("N",$StartTime);
        $LastDate   = date("N",$LastTime);
        $Bunus      = $this->bonus;

        if(($LastDate == 7) || ($StartDate > $LastDate)){ // nếu hạn vào chủ nhật hoặc  trong thời gian giao hàng có ngày cn thì + 1 ngày
            $Bunus +=  86400;
        }

        return $Bunus;
    }

    private function __check_promise_pickup($TimeApprove, $Courier, $FromCity){ // Dịch vụ thường và nhanh lấy hàng giống nhau
        $Hour           = date("H",$TimeApprove);

        if($Courier == 1){
            if($Hour < 10){ // begin 0 - 10h
                $TimePromise    = strtotime(date('Y-m-d' ,$TimeApprove).' 12:00:00');
            }elseif($Hour < 16){ // begin 10h  to 16h
                $TimePromise    = strtotime(date('Y-m-d' ,$TimeApprove).' 18:00:00');
            }else{ // over 16h
                $TimePromise    = strtotime(date('Y-m-d' ,$TimeApprove).' 12:00:00') +86400;
            }
        }elseif(in_array($Courier, [11,9])){
            /*
             * Quyên
             3. KR HN +KR HCM : Duyệt < 8h lấy trong sáng ( 12h) , Duyệt < 14h lấy trong ngày ( 17h)
                GT HCM+ GT HN+EMS HCM : Duyệt < 8h lấy trong sáng, ( 12h), Duyệt <14h lấy trong ngày ( 17h)
             */
            if($Hour < 8){
                $TimePromise    = strtotime(date('Y-m-d' ,$TimeApprove).' 12:00:00');
            }elseif($Hour < 14){
                $TimePromise    = strtotime(date('Y-m-d' ,$TimeApprove).' 17:00:00');
            }else{
                $TimePromise    = strtotime(date('Y-m-d' ,$TimeApprove).' 12:00:00') +86400;
            }
        }elseif($Courier == 8){
            /*
             EMS HN + GHTK: Duyệt <11 lấy hàng trong ngày ( 17h)
            EMS HN + GHTK: Duyệt >11 lấy hàng ngày hôm sau ( 12h)
             GT HCM+ GT HN+EMS HCM : Duyệt < 8h lấy trong sáng, ( 12h), Duyệt <14h lấy trong ngày ( 17h)
             */
            if($FromCity == 18){
                if($Hour < 11){ // begin 0 - 10h
                    $TimePromise    = strtotime(date('Y-m-d' ,$TimeApprove).' 17:00:00');
                }else{ // begin 10h  to 16h
                    $TimePromise    = strtotime(date('Y-m-d' ,$TimeApprove).' 12:00:00') +86400;
                }
            }else{
                if($Hour < 8){
                    $TimePromise    = strtotime(date('Y-m-d' ,$TimeApprove).' 12:00:00');
                }elseif($Hour < 14){
                    $TimePromise    = strtotime(date('Y-m-d' ,$TimeApprove).' 17:00:00');
                }else{
                    $TimePromise    = strtotime(date('Y-m-d' ,$TimeApprove).' 12:00:00') +86400;
                }
            }
        }elseif($Courier == 6){
            /*
             EMS HN + GHTK: Duyệt <11 lấy hàng trong ngày ( 17h)
            EMS HN + GHTK: Duyệt >11 lấy hàng ngày hôm sau ( 12h)
             GT HCM+ GT HN+EMS HCM : Duyệt < 8h lấy trong sáng, ( 12h), Duyệt <14h lấy trong ngày ( 17h)
             */
            if($Hour < 11){ // begin 0 - 10h
                $TimePromise    = strtotime(date('Y-m-d' ,$TimeApprove).' 17:00:00');
            }else{ // begin 10h  to 16h
                $TimePromise    = strtotime(date('Y-m-d' ,$TimeApprove).' 12:00:00') +86400;
            }
        }else{
            $TimePromise = strtotime(date('Y-m-d' ,$TimeApprove).' 17:00:00') +86400;
        }


        return $TimePromise;
    }

    /*b
     * Update journey delivery
     */
    public function getJourneyDelivey($idLog = ''){
        $LMongo         = new LMongo;
        $Journey        = $LMongo::collection('log_journey_delivery')->where('active',0);

        $page           = Input::has('page')    ? (int)Input::get('page')   : null;
        if(isset($page)){
            $Journey    = $Journey->whereMod('order_id', 10, $page);
        }


        if(!empty($idLog)){
            $Journey    = $Journey->where('_id', new \MongoId($idLog));
        }

        $Journey    = $Journey->orderBy('time_update', 'asc')->first();

        if(!empty($Journey)){
            $OrdersModel    = new \accountingmodel\OrdersModel;
            if(isset($Journey['time_accept'])){
                $OrdersModel = $OrdersModel->where('time_accept','>=', $Journey['time_accept'] - 604800)->where('time_accept', '<=', $Journey['time_accept'] + 604800);
            }else{
                $OrdersModel = $OrdersModel->where('time_accept','>=', $this->time() - 86400*150);
            }

            $Order  = $OrdersModel->where('id', $Journey['order_id'])
                                 ->with('FromUser')
                                 ->first(['id', 'tracking_code','domain', 'status', 'from_user_id', 'courier_id','courier_tracking_code',
                                     'courier_estimate', 'num_delivery','time_pickup', 'time_success',
                                     'time_accept_return','service_id','from_country_id','from_city_id','from_district_id','from_ward_id',
                                     'to_city_id','to_country_id','to_district_id', 'estimate_delivery', 'time_create', 'time_accept']);

            if(!isset($Order->id)){
                $Update['message'] = 'ORDER_NOT_EXISTS';
            }else{
                if(!$this->__get_group_status()){
                    return Response::json( array(
                        'error'         => true,
                        'code'          => 'GROUP_STATUS_EMPTY'
                    ) );
                }

                 $Update = [
                'message'                   => 'SUCCESS',
                'active'                    => 1,
                'courier_tracking_code'     => $Order->courier_tracking_code,
                'courier_id'                => (int)$Order->courier_id,
                'courier_estimate'          => (int)($Order->courier_estimate > 0 ? $Order->courier_estimate : $Order->estimate_delivery),
                'time_accept'               => $Order->time_accept,
                'time_pickup'               => $Order->time_pickup,
                'time_accept_return'        => $Order->time_accept_return,
                'time_success'              => $Order->time_success,
                'time_check'                => $this->time(),
                'status'                    => $Order->status,
                'email'                     => $Order->from_user->email
                ];


                if(isset($Journey['send_zms']) && $Journey['send_zms'] == 3){
                    $Update['send_zms']	= 0;
                }

                //if($Order->num_delivery > 0){ // Đã từng phát
                    $OrderStatus    = \ordermodel\StatusModel::where('order_id', $Order->id)->orderBy('time_create','ASC')->get()->toArray();
                    $CheckFail      = false;

                    if(!empty($OrderStatus)){
                        foreach($OrderStatus as $val){
                            if(in_array((int)$val['status'],$this->group_status[29])){
                                if((!isset($Update['first_fail_time']) || empty($Update['first_fail_time']))){
                                    $Update['first_fail_time']  = $val['time_create'];
                                }elseif((!isset($Update['second_fail_time']) || empty($Update['second_fail_time']))){
                                    $Update['second_fail_time']  = $val['time_create'];
                                }elseif((!isset($Update['third_fail_time']) || empty($Update['third_fail_time']))){
                                    $Update['third_fail_time']  = $val['time_create'];
                                }
                            }elseif(in_array((int)$val['status'], [76,79]) && !isset($Update['first_fail_time'])){
                                //trạng thái đang phát hàng lần 2 và chưa nhận được trạng thái phát thất bại trước đó
                                $CheckFail  = true;
                                //if(!isset($Journey['first_fail_time']) || empty($Journey['first_fail_time'])){
                                    $Update['first_fail_time']  = $val['time_create'];
                                //}
                            }
                        }
                    }
                //}return $Update;

                if($CheckFail){
                    $Order->num_delivery += 1;
                }

                $Update['num_delivery'] = $Order->num_delivery;

                $AreaQuery        = \AreaLocationModelDev::where('courier_id', $Order->courier_id)->where('province_id', $Order->to_district_id)
                    ->where('active','=',1)
                    ->first(['province_id', 'city_id', 'location_id']);

                if(empty($AreaQuery)){
                    if($Order->from_country_id == 237 && $Order->to_country_id == 237){
                        return Response::json( array(
                            'error'         => true,
                            'code'          => 'AREA_EMPTY',
                            'province_id'   => $Order->id
                        ) );
                    }else{
                        $Update['to_city_id']   = $Order->to_city_id;
                        $Update['to_location']  = 1;
                    }
                }else{
                    $Update['to_city_id']   = $AreaQuery->city_id;
                    $Update['to_location']  = $AreaQuery->location_id;
                }



                if($Update['to_city_id'] ==  $Order->from_city_id){
                    $Update['area_type']    = 1;
                }else{
                    $Update['area_type']    = 2;
                }

                // Calculate estimate time
                if(in_array($Order->status, [52,53])){
                    $Order->num_delivery -= 1;
                }

                $Update['first_promise_time'] = $Order->time_pickup   + $Update['courier_estimate']*3600;
                $CheckBonus                   = $this->__check_date($Order->time_pickup, $Update['first_promise_time']);
                $Update['first_promise_time'] +=  $CheckBonus;

                try{
                    if($Order->num_delivery >= 1){
                        $FirstFailTime          = $Update['first_fail_time'];
                        $BonusTime              = 0;
                        if(in_array(($AreaQuery->city_id), [18,52])){
                            if(in_array($AreaQuery->location_id,[1,2])){
                                $BonusTime      = 24*3600;
                            }else{
                                $BonusTime      = 48*3600;
                            }
                        }else{
                            if($AreaQuery->location_id == 1){
                                $BonusTime      = 24*3600;
                            }else{
                                $BonusTime      = 48*3600;
                            }
                        }
                        $Update['second_promise_time'] = $FirstFailTime   +  $BonusTime;
                        $CheckBonus                   = $this->__check_date($FirstFailTime, $Update['second_promise_time']);
                        $Update['second_promise_time'] +=  $CheckBonus;
                    }

                    if($Order->num_delivery >= 2){ // Phát thất bại 2 lần
                        $SecondFailTime     = $Update['second_fail_time'];
                        $BonusTime          = 0;
                        if(in_array(($AreaQuery->city_id), [18,52])){
                            if($AreaQuery->location_id > 2){
                                $BonusTime      = 24*3600;
                            }
                        }else{
                            if($AreaQuery->location_id == 1){
                                $BonusTime      = 24*3600;
                            }else{
                                $BonusTime      = 48*3600;
                            }
                        }
                        $Update['third_promise_time'] = $SecondFailTime   +  $BonusTime;
                        $CheckBonus                   = $this->__check_date($SecondFailTime, $Update['third_promise_time']);
                        $Update['second_promise_time'] +=  $CheckBonus;
                    }
                }catch(\Exception $e){
                    OrdersModel::where('id', $Order->id)->where('time_accept', $Order->time_accept)->decrement('num_delivery',1);
                    return Response::json( array(
                        'error'         => false,
                        'code'          => 'ERROR',
                        'tracking_code' => $Order->tracking_code,
                        'message'       => $e->getMessage()
                    ) );
                }

                // Check number slow
                if(isset($Update['first_promise_time'])){ // Chậm lần 1
                    if(isset($Update['first_fail_time'])){ // tồn tại giao thất bại
                        if($Update['first_fail_time'] > $Update['first_promise_time']){
                            $Update['first_slow']   = $Update['first_fail_time'] - $Update['first_promise_time'];
                        }else{
                            $Update['first_slow']   = 0;
                        }
                    }else{ // ko tồn tại giao thất bại
                        if(in_array($Update['status'], [52,53])){ // đã giao thành công
                            if($Update['first_promise_time'] < $Update['time_success']){
                                $Update['first_slow']   = $Update['time_success'] - $Update['first_promise_time'];
                            }else{
                                $Update['first_slow']   = 0;
                            }
                        }elseif(in_array($Update['status'],[61,62,63,64,65,66,67])){
                            // Trạng thái hoàn
                            if($Update['time_accept_return'] > 0){
                                if($Update['first_promise_time'] < $Update['time_accept_return']){
                                    $Update['first_slow']   = $Update['time_accept_return'] - $Update['first_promise_time'];
                                }else{
                                    $Update['first_slow']   = 0;
                                }
                            }else{
                                $Update['first_slow']   = 0;
                            }
                        }
                    }
                }

                if(isset($Update['second_promise_time'])){ // Chậm lần 2
                    if(isset($Update['second_fail_time'])){ // tồn tại giao thất bại
                        if($Update['second_fail_time'] > $Update['second_promise_time']){
                            $Update['second_slow']   = $Update['second_fail_time'] - $Update['second_promise_time'];
                        }else{
                            $Update['second_slow']   = 0;
                        }
                    }else{ // ko tồn tại giao thất bại
                        if(in_array($Update['status'], [52,53])){ // đã giao thành công
                            if($Update['second_promise_time'] < $Update['time_success']){
                                $Update['second_slow']   = $Update['time_success'] - $Update['second_promise_time'];
                            }else{
                                $Update['second_slow']   = 0;
                            }
                        }elseif(in_array($Update['status'],[61,62,63,64,65,66,67])){
                            // Trạng thái hoàn
                            if($Update['time_accept_return'] > 0){
                                if($Update['second_promise_time'] < $Update['time_accept_return']){
                                    $Update['second_slow']   = $Update['time_accept_return'] - $Update['second_promise_time'];
                                }else{
                                    $Update['second_slow']   = 0;
                                }
                            }else{
                                $Update['second_slow']   = 0;
                            }
                        }
                    }
                }

                if(isset($Update['third_promise_time'])){ // Chậm lần 3
                    if(isset($Update['third_fail_time'])){ // tồn tại giao thất bại
                        if($Update['third_fail_time'] > $Update['third_promise_time']){
                            $Update['third_slow']   = $Update['third_fail_time'] - $Update['third_promise_time'];
                        }else{
                            $Update['third_slow']   = 0;
                        }
                    }else{ // ko tồn tại giao thất bại
                        if(in_array($Update['status'], [52,53])){ // đã giao thành công
                            if($Update['third_promise_time'] < $Update['time_success']){
                                $Update['third_slow']   = $Update['time_success'] - $Update['third_promise_time'];
                            }else{
                                $Update['third_slow']   = 0;
                            }
                        }elseif(in_array($Update['status'],[61,62,63,64,65,66,67])){
                            // Trạng thái hoàn
                            if($Update['time_accept_return'] > 0){
                                if($Update['third_promise_time'] < $Update['time_accept_return']){
                                    $Update['third_slow']   = $Update['time_accept_return'] - $Update['third_promise_time'];
                                }else{
                                    $Update['third_slow']   = 0;
                                }
                            }else{
                                $Update['third_slow']   = 0;
                            }
                        }
                    }
                }
            }

            $LMongo = new LMongo;

            try{
                $LMongo::collection('log_journey_delivery')
                    ->where('_id',new \MongoId($Journey['_id']))
                    ->update($Update);
                return Response::json( array(
                    'error'         => false,
                    'code'          => 'SUCCESS',
                    'tracking_code' => $Order->tracking_code
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

    public function getJourneyPickup($idLog = ''){
        $LMongo         = new LMongo;
        $Journey        = $LMongo::collection('log_journey_pickup')->where('active',0);

        $page           = Input::has('page')    ? (int)Input::get('page')   : null;
        if(isset($page)){
            $Journey    = $Journey->whereMod('order_id', 10, $page);
        }

        if(!empty($idLog)){
            $Journey    = $Journey->where('_id', new \MongoId($idLog));
        }

        $Journey    = $Journey->orderBy('time_update', 'asc')->first();

        if(empty($Journey)){
            return Response::json( array(
                'error'         => true,
                'code'          => 'EMPTY'
            ) );
        }

        $Order    = OrdersModel::where('time_accept','>=', $Journey['time_accept'] - 604800)->where('time_accept', '<=', $Journey['time_accept'] + 604800)
                                        ->where('id', $Journey['order_id'])->where('from_country_id', 237)->where('to_country_id', 237)
                                        ->where('courier_id', '<>', 12)
                                        ->with('FromUser')
                                        ->first(['id', 'tracking_code','domain', 'status', 'from_user_id','from_address_id', 'courier_id','courier_tracking_code'
                                        ,'service_id','from_city_id','from_district_id','from_ward_id', 'from_address', 'time_create', 'time_accept','time_approve','time_pickup']);

        if(!isset($Order->id)){
            $Update['message'] = 'ORDER_NOT_EXISTS';
            $Update['active']  = 2;
        }else{
             $Update = [
                'message'                   => 'SUCCESS',
                'active'                    => 1,
                'courier_tracking_code'     => $Order->courier_tracking_code,
                'courier_id'                => (int)$Order->courier_id,
                'from_address_id'           => $Order->from_address_id,
                'from_user_id'              => $Order->from_user_id,
                'time_accept'               => $Order->time_accept,
                'time_approve'              => $Order->time_approve,
                'time_pickup'               => $Order->time_pickup,
                'time_check'                => $this->time(),
                'status'                    => $Order->status,
                'email'                     => $Order->from_user->email,
                 'from_address'             => $Order->from_address
            ];

            $AreaQuery        = \AreaLocationModelDev::where('courier_id', $Order->courier_id)->where('province_id', $Order->from_district_id)
                                                     ->where('active','=',1)
                                                     ->remember(60)
                                                     ->first(['province_id', 'city_id', 'location_id']);

            if(empty($AreaQuery)){
                return Response::json( array(
                    'error'         => true,
                    'code'          => 'AREA_EMPTY',
                    'province_id'   => $Order->id
                ) );
            }

            $Update['from_location']    = $AreaQuery->location_id;

            $Update['promise_pickup_time']  = $this->__check_promise_pickup($Order->time_approve, $Order->courier_id, $Order->from_city_id);
            $CheckBonus                     = $this->__check_date($Order->time_approve, $Update['promise_pickup_time']);
            $Update['promise_pickup_time']  +=  $CheckBonus;

            if($Order->time_pickup > 0){
                if($Order->time_pickup > $Update['promise_pickup_time']){
                    $Update['time_slow']    = $Order->time_pickup - $Update['promise_pickup_time'];
                }else{
                    $Update['time_slow']    = 0;
                }
            }
        }

        $LMongo = new LMongo;

        try{
            $LMongo::collection('log_journey_pickup')
                ->where('_id',new \MongoId($Journey['_id']))
                ->update($Update);
            return Response::json( array(
                'error'         => false,
                'code'          => 'SUCCESS',
                'tracking_code' => $Journey['tracking_code']
            ) );
        }catch (\Exception $e){
            return Response::json( array(
                'error'         => true,
                'code'          => 'ERROR',
                'error_message' => $e->getMessage()
            ) );
        }

    }
}