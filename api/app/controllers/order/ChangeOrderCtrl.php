<?php namespace order;

use Response;
use Input;
use DB;
use Exception;
use ordermodel\OrdersModel;
use ordermodel\AddressModel;
use ordermodel\DetailModel;
use ordermodel\OrderItemModel;
use accountingmodel\MerchantModel;
use sellermodel\UserInventoryModel;
use omsmodel\CustomerAdminModel;
use sellermodel\CouponModel;
use sellermodel\CouponMembersModel;
use LMongo;
use Validator;

class ChangeOrderCtrl extends \BaseController {
    private $data_log       = [];
    private $OrderId        = 0;
    private $UserId         = 0;
    private $UserEdit       = 0;
    private $data_update    = []; // data update fee
    private $data_update_order    = []; // data update order
    private $data_calculate = []; // data update fee

    function __construct()
    {
        Input::merge(Input::json()->all());
    }

    private function _validation(){
        Validator::getPresenceVerifier()->setConnection('orderdb');
        $this->validation = Validator::make(Input::all(), array(
            'id'                    => 'sometimes|required|numeric|min:1', // check exists in table orders by id
            'tracking_code'         => 'sometimes|required', // check exists in table orders by tracking_code
            'from_address_id'       => 'sometimes|required|numeric|min:1', // Kho hàng
            'service_id'            => 'sometimes|required|numeric|min:1',
            'to_name'               => 'sometimes|required',
            'to_phone'              => 'sometimes|required',
            'to_email'              => 'sometimes|required|email',
            'item_name'             => 'sometimes|required',
            'total_quantity'        => 'sometimes|required|numeric|min:1',
            'total_weight'          => 'sometimes|required|numeric|min:1',
            'total_amount'          => 'sometimes|required|numeric|min:1',
            'description'           => 'sometimes|required',
            'status'                => 'sometimes|required|numeric|min:20',
            'money_collect'         => 'sometimes|required|numeric|min:0'
        ));
    }


    public function getCourierSupport ($json = true){
        $Id                     = Input::has('id')                  ? Input::get('id')                  : 0;  // Id  table orders
        $TrackingCode           = Input::has('TrackingCode')        ? Input::get('TrackingCode')        : '';  // Id  table orders
        $CourierTrackingCode    = Input::has('CourierTrackingCode') ? Input::get('CourierTrackingCode') : '';
        $Services               = Input::has('service_id')          ? Input::get('service_id')          : 0;  // Id  table orders
        $OrderDetail            = [];
        $CalculteFee            = [];

        if(Input::has('tracking_code')){
            $TrackingCode   = Input::get('tracking_code');
        }

        $Model              = new OrdersModel;

        if(!empty($Id)){
            $Order              = $Model::find((int)$Id);
        }elseif(!empty($TrackingCode)){
            $Order              = $Model::where('tracking_code', $TrackingCode)->where('time_create','>=',$this->time() - $this->time_limit)->first();
        }elseif(!empty($CourierTrackingCode)){
            $Order              = $Model::where('courier_tracking_code', $CourierTrackingCode)->where('time_create','>=',$this->time() - $this->time_limit)->first();
        }

        if(!isset($Order) || empty($Order)){
            return $json ? Response::json( ['error'  => true, 'message'  => 'ORDER_NOT_EXISTS', 'error_message' => 'Đơn hàng không tồn tại']) : ['error' => true,'message' => 'ORDER_NOT_EXISTS', 'error_message' => 'Đơn hàng không tồn tại'];
        }

        $this->OrderId      = $Order->id;

        $DetailModel = new DetailModel;
        $OrderDetail = $DetailModel->where('order_id', $this->OrderId)->first();

        if (empty($OrderDetail)) {
            return $json ? Response::json(['error' => true, 'message' => 'ORDER_DETAIL_NOT_EXISTS', 'error_message' => 'Đơn hàng không đúng.']) : ['error' => true,'message' => 'ORDER_DETAIL_NOT_EXISTS', 'error_message' => 'Đơn hàng không đúng.'];
        }   


        $ToAddressId    = (int)$Order->to_address_id;
        
        $OrderAddress   = new AddressModel;
        $ToAddress      = $OrderAddress->find($ToAddressId);

        if(empty($ToAddress)){
            return ['error' => true, 'message' => 'TO_ADDRESS_NOT_EXISTS', 'error_message' => 'Đơn hàng không dúng !'];
        }

        $_protect = $OrderDetail->sc_pbh > 0 ? 1 : 2;
        
        $Service = !empty($Services) ? $Services : $Order->service_id;

        $DataUpdate = [
            'From'  => [
                'City'      => (int)$Order->from_city_id,
                'Province'  => (int)$Order->from_district_id,
                'Ward'      => (int)$Order->from_ward_id,
                'Stock'     => (int)$Order->from_address_id
            ],
            'To'    => [
                'City'      => (int)$ToAddress->city_id,
                'Province'  => (int)$ToAddress->province_id,
                'Ward'      => (int)$ToAddress->ward_id
            ],
            'Order' => [
                'Amount'    => $Order->total_amount,
                'Weight'    => $Order->total_weight,
                'Collect'   => $OrderDetail->money_collect
            ],
            'Config'    => [
                'Checking'  => $Order->checking,
                'Fragile'   => $Order->fragile,
                'Service'   => $Services,
                'Protected' => $_protect,
                'CoD'       => $OrderDetail->sc_cod         > 0 ? 1 : 2,
                'Payment'   => $OrderDetail->seller_pvc     > 0 ? 2 : 1
            ],
            'Domain'        => !empty($Order->domain) ? $Order->domain : 'shipchung.vn',
            'Type'          => 'change'
        ];

        Input::merge($DataUpdate);
        $ApiCourierCtrl = new \ApiCourierCtrl;
        $Calculater     = $ApiCourierCtrl->SuggestCourier(false);
        if($Calculater == false){
            return $ApiCourierCtrl->ResponseData(true);
        }

        return Response::json([
            'error'         => false,
            'error_message' => 'Thành công',
            'data'          => $Calculater
        ]);
    }
    
    public function postEdit($json = true)
    {
        $UserInfo               = Input::has('UserInfo')                ? Input::get('UserInfo')            : $this->UserInfo();
        $Id                     = Input::has('id')                      ? Input::get('id')                  : 0;  // Id  table orders
        $TrackingCode           = Input::has('TrackingCode')            ? Input::get('TrackingCode')        : '';  // Id  table orders
        $CourierTrackingCode    = Input::has('CourierTrackingCode')     ? Input::get('CourierTrackingCode') : '';
        $Status                 = Input::has('status')                  ? Input::get('status')              : 0;
        $Token                  = Input::has('Token')                   ? Input::get('Token')               : 0;
        $FromMerchantApi        = Input::has('from_merchant_api')       ? Input::get('from_merchant_api')   : false;
        $OrderDetail    = [];
        $CalculteFee    = [];

        $this->_json    = $json;

        if(Input::has('tracking_code')){
            $TrackingCode   = Input::get('tracking_code');
        }

        /**
         *  Validation params
         * */
        $this->_validation();
        if($this->validation->fails()) {
            $this->_error         = true;
            $this->_error_message = "Dữ liệu gửi lên không đúng, vui lòng thử lại";
            $this->_message       = $this->validation->messages();
            goto done;
        }


        $Model              = new OrdersModel;
        $Model              = $Model::where(function($query){
            $query->where('time_accept','>=', $this->time() - $this->time_limit)
                  ->orWhere('time_accept',0);
        });

        if(!empty($Id)){
            $Order              = $Model->find((int)$Id);
        }elseif(!empty($TrackingCode)){
            $Order              = $Model->where('tracking_code', $TrackingCode)->first();
        }elseif(!empty($CourierTrackingCode)){
            $Order              = $Model->where('courier_tracking_code', $CourierTrackingCode)->first();
        }

        if(!isset($Order) || empty($Order)){
            $this->_error         = true;
            $this->_error_message = "Đơn hàng không tồn tại";
            $this->_message       = "ORDER_NOT_EXISTS";
            goto done;
        }

        /* Không cần -- Đã chặn ở dưới
        if($UserInfo['id'] != $Order['from_user_id'] && $UserInfo['privilege'] < 1){
            $this->_error         = true;
            $this->_error_message = "Bạn không được phép sửa đơn này";
            $this->_message       = "USER_NOT_ALLOW";
            goto done;
        }*/

        if((!isset($UserInfo['privilege']) || $UserInfo['privilege'] < 1) &&
            (!in_array($Order->status,[20,21,60,101,103,120,30,31,34,35,51,52,53,54,55,56,57,58,59,77]) ||
                (
                    in_array($Order->status,[20,21,60,101,103,120,30,31,34,35,51,52,53,54,55,56,57,58,59,77]) &&
                    $UserInfo['id'] != $Order->from_user_id)
                )
        ){
            $this->_error         = true;
            $this->_error_message = "Bạn không được phép sửa đơn này";
            $this->_message       = "USER_NOT_ALLOW";
            goto done;
        }
        //in_array($Order->status, [52,53,66]) ||
        if(in_array($Order->status, [52,53,66]) || $Order->verify_id > 0){
            $this->_error         = true;
            $this->_error_message = "Đơn hàng đã ở trạng thái cuối hoặc đã được đối soát, không thể sửa đổi";
            $this->_message       = "ORDER_CANNOT_CHANGE";
            goto done;
        }

        // if($Order->domain == 'boxme.vn'  && $Order->is_weship !== 1 && (empty($Token) || $Token != 'b2dacd40ee860b5440f743d60efff438') && !empty($Status) &&  in_array($Status, [22, 28]) && $UserInfo['privilege'] < 1){
        //     $UpdateBoxme    = $this->request_cancel_boxme($Order->tracking_code);
        //     if(!$UpdateBoxme){
        //         $this->_error         = true;
        //         $this->_error_message = "Hủy đơn hàng thất bại, vui lòng thử lại!";
        //         $this->_message       = "ORDER_CANNOT_CHANGE";
        //         goto done;
                
        //     }

        //     if(!$UpdateBoxme['Success']){
        //         $this->_error         = true;
        //         $this->_error_message = "Đơn hàng không thể hủy trên hệ thống boxme.vn!";
        //         $this->_message       = "ORDER_CANNOT_CHANGE";
        //         goto done;
        //     }
        // }

        $this->OrderId      = $Order->id;
        $this->UserId       = $Order->from_user_id ? $Order->from_user_id : (int)$UserInfo['id'];
        $this->UserEdit     = isset($UserInfo['child_id']) && (int)$UserInfo['child_id'] > 0 ? (int)$UserInfo['child_id'] : (int)$UserInfo['id'];

        // Khách hàng yêu cầu lấy lại hàng
        if(in_array($Order->status, [31,32,33,34]) && Input::has('status') && Input::get('status') == 38 && $UserInfo['privilege'] < 1){
            return $this->ConfirmPickup($Order);
        }

        if(in_array($Order->status, [51,52,53,54,55,56,57,58,59,60]) && Input::has('status') && in_array(Input::get('status'), [67, 75, 60]) && $UserInfo['privilege'] < 1){// Xác nhận giao lại
            return $this->ConfirmDelivery($Order);
        }
        elseif($Order->status == 60 && $UserInfo['privilege'] < 1){ // Xác nhận chuyển hoàn
            $Confirm =  $this->ConfirmReturn($Order);
            if(!$this->_error){
                $this->insertLog();
                try{
                    $this->__InsertLogBoxme($Order, $Status);
                    $this->PredisReportReturn($Order->id);
                }catch(Exception $e){

                }
            }
            goto done;
        }

        // Báo hủy
        if($Status == 28 && in_array($Order->status, [21,101,103,120,30,31,35]) && $UserInfo['privilege'] < 1){
            $this->ReportCancel($Order);
            goto done;
        }

        //Update to address
        if(Input::has('to_address_id')){
            $EditToAddress  = $this->changeToAddressId();

            if($this->_error){
                goto done;
            }
            Input::merge(['to_address_id' => (int)$EditToAddress]);
        }

        if(Input::has('money_collect') || $Status == 21 || Input::has('from_address_id') || Input::has('total_weight') || Input::has('to_address_id') || Input::has('service_id') || Input::has('protect') || Input::has('courier_id')) {
            
            $DetailModel = new DetailModel;
            $OrderDetail = $DetailModel->where('order_id', $this->OrderId)->first();

            if (empty($OrderDetail)) {
                $this->_error           = true;
                $this->_message         = "ORDER_DETAIL_NOT_EXISTS";
                $this->_error_message   = "Đơn hàng không đúng.";
                goto done;
            }
        }

        DB::connection('orderdb')->beginTransaction();
        // Update table order
        $UpdateOrder    = $this->UpdateOrder($Order,$OrderDetail);

        if($this->_error){
            goto done;
        }


        if(Input::has('description')){
            $UpdateOrderItem = $this->UpdateOrderItem();

            if($this->_error){
                goto done;
            }

        }

        // CalculateFee
        if(!empty($this->data_calculate) || Input::has('money_collect')){
            $CalculteFee  = $this->calculateFee($Order, $OrderDetail);
            
            if($this->_error){
                goto done;
            }

        }elseif(isset($OrderDetail->id)){
            $CalculteFee    = [
                'fee'   => [
                    'discount'  => [],
                    'vas'       => []
                ]
            ];

            $CalculteFee['fee']['discount']['pvc']  = $OrderDetail->sc_discount_pvc;
        }

        DB::connection('orderdb')->commit();
        // Insert Log
        
        if(!empty($this->data_log)){
            $InsertLog  = $this->insertLog();
            
            if($this->_error){
                goto done;
            }
        }


        // Predis accept lading
        if($Status == 21){
            $this->PredisAcceptLading($Order->tracking_code);
        }

        
        

        $this->_additional['data_log'] = $this->data_log;


        done:
            //Đơn hàng vượt cân cần xử lý
            if($Order->status > 21 && isset($this->data_log['sc_pvk']) && $this->data_log['sc_pvk']['new'] > 0){
                $this->__insert_log_problem($Order);
            }
            return $this->_ResponseData([], $this->_additional);
    }

    public function postAcceptOrderweight(){

        $UserInfo               = Input::has('UserInfo')            ? Input::get('UserInfo')            : $this->UserInfo();
        $Id                     = Input::get('id');  // Id  table orders
        $TrackingCode           = Input::has('TrackingCode')        ? Input::get('TrackingCode')        : '';  // Id  table orders
        $CourierTrackingCode    = Input::has('CourierTrackingCode') ? Input::get('CourierTrackingCode') : '';

        $Model                  = new OrdersModel;
        $_retObject = [
            "error"     => true,
            "data"      => [],
            "message"   => ""
        ];


        if(!empty($Id)){
            $Order              = $Model::find((int)$Id);
        }elseif(!empty($TrackingCode)){
            $Order              = $Model::where('tracking_code', $TrackingCode)->where('time_create','>=',$this->time() - $this->time_limit)->first();
        }elseif(!empty($CourierTrackingCode)){
            $Order              = $Model::where('courier_tracking_code', $CourierTrackingCode)->where('time_create','>=',$this->time() - $this->time_limit)->first();
        }

        if(isset($Order) && $Order == null || empty($Order)){
            return Response::json( ['error'  => true, 'message'  => 'ORDER_NOT_EXISTS', 'error_message'=> 'Bạn không thể cập nhật đơn hàng này, vui lòng liên hệ bộ phận CSKH.']);
        }

        if($Order['from_user_id'] == $UserInfo['id'] &&  $Order['accept_overweight'] == 0){
            $Order->accept_overweight = 1;
            $result = $Order->save();
            if($result){
                $_retObject['error']    = false;
                $_retObject['data']     = $result;
            }
        }else {
            $_retObject['message'] = "Bạn không có quyền sửa đơn hàng này !" ;      
        }

        $QueueId = Input::get('queue_id');
        if (!empty($QueueId) && (int)$QueueId > 0) {
            try {
                \QueueModel::where('id', (int)$QueueId)->update(['view'=> 1]);
            } catch (Exception $e) {
                
            }
        }

        return Response::json($_retObject);
    }

    private  function _getInventory  ($id){
        return \sellermodel\UserInventoryModel::where('id', $id)->first();
    }

    private function UpdateOrder($Order,$OrderDetail){
        if(
            Input::has('to_name') 
            || Input::has('to_phone') 
            || Input::has('to_email') 
            || Input::has('status') 
            || Input::has('total_quantity')
            || Input::has('total_weight')   
            || Input::has('item_name') 
            || Input::has('service_id') 
            || Input::has('checking') 
            
            || Input::has('courier_id') 
            || Input::has('from_address_id') 
            || Input::has('to_address_id')
            || Input::has('order_code')
            || Input::has('protect')
        ){
            $UserInfo           = Input::has('UserInfo')                ? Input::get('UserInfo')                                : $this->UserInfo();
            $ToName             = Input::has('to_name')                 ? trim(Input::get('to_name'))                           : '';
            $ToPhone            = Input::has('to_phone')                ? preg_replace('/\D/',',',trim(Input::get('to_phone'))) : '';
            $ToEmail            = Input::has('to_email')                ? trim(Input::get('to_email'))                          : '';
            $Status             = Input::has('status')                  ? (int)Input::get('status')                             : 0;
            $TotalQuantity      = Input::has('total_quantity')          ? (int)Input::get('total_quantity')                     : 0 ;
            $TotalWeight        = Input::has('total_weight')            ? trim(Input::get('total_weight'))                      : 0;
            $ItemName           = Input::has('item_name')               ? trim(Input::get('item_name'))                         : '';
            $Service            = Input::has('service_id')              ? (int)(Input::get('service_id'))                       : 0;

            $Checking           = Input::has('checking')                ? (int)(Input::get('checking'))                         : 0;

            $Courier            = Input::has('courier_id')              ? (int)(Input::get('courier_id'))                       : 0;
            $FromAddressId      = Input::has('from_address_id')         ? (int)(Input::get('from_address_id'))                  : 0;
            $ToAddressId        = Input::has('to_address_id')           ? (int)(Input::get('to_address_id'))                    : 0;
            $OrderCode          = Input::has('order_code')              ? (Input::get('order_code'))                            : '';
            $Protect            = Input::has('protect')                 ? (bool)(Input::get('protect'))                         : 0;
            $ToDistrictId       = Input::has('to_province_id')          ? (int)Input::get('to_province_id')                     : 0;
            $Coupon             = Input::has('coupon_code')             ? strtoupper(trim(Input::get('coupon_code')))           : '';

            if(!empty($ToName) && $Order->to_name != $ToName){
                $this->changeItem('to_name', $ToName, $Order->to_name);
                $Order->to_name = $ToName;
            }

            // Buy protect
            if(!empty($Protect) && $Protect == true){

                $this->data_log['protect']   = [
                    'type'          => 'protect',
                    'new'           => 1,
                    'old'           => (isset($OrderDetail['sc_pbh']) && $OrderDetail['sc_pbh'] > 0) ? 1 : 2,
                ];

                $this->data_calculate['Protect']    = 1;
            }

            if(!empty($OrderCode) && $Order->order_code != $OrderCode){
                $this->changeItem('order_code', $OrderCode, $Order->order_code);
                $Order->order_code = $OrderCode;
            }

            if(!empty($Checking) && $Order->checking != $Checking){

                $this->changeItem('checking', $Checking, $Order->checking);
                $Order->checking = $Checking;

            }


            if(!empty($ToPhone) && $Order->to_phone != $ToPhone){
                $this->changeItem('to_phone', $ToPhone, $Order->to_phone);
                $Order->to_phone = $ToPhone;
            }
            if(!empty($ToEmail) && $Order->to_email != $ToEmail){
                $this->changeItem('to_email', $ToEmail, $Order->to_email);
                $Order->to_email = $ToEmail;
            }

            if(!empty($TotalQuantity) && $Order->total_quantity != $TotalQuantity){
                $this->changeItem('total_quantity', $TotalQuantity, $Order->total_quantity);
                $Order->total_quantity = $TotalQuantity;
            }

            if(!empty($ItemName) && $Order->product_name != $ItemName){
                $this->changeItem('product_name', $ItemName, $Order->product_name);
                $Order->product_name = $ItemName;
            }

            if(!empty($Status) && $Order->status != $Status){
                if($Order->status == 20 && !in_array($Status, array(21, 22,28)) && $UserInfo['privilege'] < 1){
                    $this->_error           = true;
                    $this->_message         = "USER_NOT_ALLOW";
                    $this->_error_message   = "Bạn không thể cập nhật đơn hàng này, vui lòng liên hệ bộ phận CSKH.";
                    
                    return false;
                }

                if($Status == 28 && !in_array($Order->status, [20,101,103,120,21,30,31,32,33,34,35]) && $UserInfo['privilege'] < 1){
                    $this->_error           = true;
                    $this->_message         = "USER_NOT_ALLOW";
                    $this->_error_message   = "Bạn không thể cập nhật đơn hàng này, vui lòng liên hệ bộ phận CSKH.";
                    if($Status == 102){
                        $this->_error_message   = "Quý khách không thể hủy những đơn hàng đã đóng gói, vui lòng liên hệ bộ phận CSKH.";
                    }
                    return false;
                }

                if($Status == 22 && !in_array($Order->status, [20]) && $UserInfo['privilege'] < 1){
                    $this->_error           = true;
                    $this->_message         = "USER_NOT_ALLOW";
                    $this->_error_message   = "Bạn không thể cập nhật đơn hàng này, vui lòng liên hệ bộ phận CSKH.";
                    return false;
                }


                if($Status == 21){
                    if ($Order->status > 20 && $Order->status !== 120) {
                        $this->_error           = true;
                        $this->_message         = "CANNOT_ACCEPT_ORDER";
                        $this->_error_message   = "Đơn hàng đã được duyệt, không thể duyệt lại.";
                        return false;
                    }

                    // tạo quá 1 tuần ko được duyệt
                    if($Order->time_create < ($this->time() - 604800)){
                        $this->_error           = true;
                        $this->_message         = "CANNOT_ACCEPT_ORDER";
                        $this->_error_message   = "Đơn hàng đã được tạo quá 7 ngày, bạn không thể duyệt. ";
                        return false;
                    }

                    $CheckBalance = $this->CheckBalance();
                    if($CheckBalance['error']){
                        return $CheckBalance;
                    }

                    if(!empty($Coupon)){
                        if($Order->coupon_id > 0){
                            $this->_error           = true;
                            $this->_message         = "USED_COUPON";
                            $this->_error_message   = "Mã giảm giá đã được sử dụng";
                            return false;
                        }

                        $CouponModel    = new CouponModel;
                        $CheckCoupon    = $CouponModel::where('code',$Coupon)->where('time_expired','>=',$this->time())->where('active',1)->first();

                        
                        
                        if(!isset($CheckCoupon->id)){
                            $this->_error           = true;
                            $this->_message         = "COUPON_NOT_EXISTS";
                            $this->_error_message   = "Mã khuyến mãi không tồn tại !";
                            return false;
                        }

                        if($Order->domain !== 'app.shipchung.vn'){
                            if ($CheckCoupon->inapp == 1 ) {
                                $this->_error           = true;
                                $this->_message         = "COUPON_ONLY_INAPP";
                                $this->_error_message   = "Mã khuyến mãi này chỉ được áp dụng với những đơn hàng tạo trên ứng dụng di dộng Shipchung";
                                return false;
                            }   
                        }

                        if($CheckCoupon->usaged >= $CheckCoupon->limit_usage){
                            $this->_error           = true;
                            $this->_message         = "COUPON_LIMITED";
                            $this->_error_message   = "Mã khuyến mãi đã được sử dụng đến giới hạn";
                            return false;
                        }
                        // Edit by ThinhNV
                        // if($CheckCoupon->discount_type == 2){

                        if($CheckCoupon->coupon_type == 2){
                            $CouponMembersModel = new CouponMembersModel;
                            $CheckMember        = $CouponMembersModel::where('coupon_id',$CheckCoupon->id)
                                ->where('user_id', (int)$UserInfo['id'])
                                ->count();

                            if($CheckMember == 0){
                                $this->_error           = true;
                                $this->_message         = "COUPON_NOT_EXISTS";
                                $this->_error_message   = "Bạn không được phép sử dụng mã khuyến mãi này, xin cảm ơn !";
                                return false;
                            }
                        }

                        //check coupon QT
                        if($CheckCoupon->code == 'QT20' || $CheckCoupon->code == 'QT40'){
                            if($Order->service_id != 8){
                                $this->_error           = true;
                                $this->_message         = "COUPON_NOT_USES_FOR_THIS_SERVICE";
                                $this->_error_message   = "Mã khuyến mãi không được xử dụng cho dịch vụ này, xin cảm ơn !";
                                return false;
                            }
                            if($CheckCoupon->code == 'QT40'){
                                if(!in_array($Order->to_country_id, [195,96])){
                                    $this->_error           = true;
                                    $this->_message         = "COUPON_NOT_USES_FOR_THIS_COUNTRY1";
                                    $this->_error_message   = "Mã khuyến mãi không được xử dụng cho quốc gia này, xin cảm ơn !";
                                    return false;
                                }
                            }elseif($CheckCoupon->code == 'QT20'){
                                if(in_array($Order->to_country_id, [195,96,237])){
                                    $this->_error           = true;
                                    $this->_message         = "COUPON_NOT_USES_FOR_THIS_COUNTRY2";
                                    $this->_error_message   = "Mã khuyến mãi không được xử dụng cho quốc gia này, xin cảm ơn !";
                                    return false;
                                }
                            }
                        }
                        
                        
                        $OrderDetail      = $this->caculateCoupons($OrderDetail, $CheckCoupon);
                        $Order->coupon_id = $CheckCoupon->id;

                        try{
                            $OrderDetail->save();
                        }catch (Exception $e){
                            $this->_error           = true;
                            $this->_message         = "UPDATE_ORDER_DETAIL_FAIL";
                            $this->_error_message   = "Cập nhật phí lỗi, hãy thử lại";
                            return false;
                        }
                    }else{
                        if($this->__check_discount($Order,$OrderDetail)){
                            try{
                                $OrderDetail->save();
                            }catch (Exception $e){
                                $this->_error           = true;
                                $this->_message         = "UPDATE_ORDER_DETAIL_FAIL";
                                $this->_error_message   = "Cập nhật phí lỗi, hãy thử lại";
                                return false;
                            }
                        }
                    }

                    if($OrderDetail->money_collect > 0){
                        // CoD
                        if(($CheckBalance['money_total'] - $OrderDetail->sc_pvc - $OrderDetail->sc_cod - $OrderDetail->sc_pbh + $OrderDetail->sc_discount_pvc + $OrderDetail->sc_discount_cod) < -200000){

                            $this->_error           = true;
                            $this->_message         = "NOT_ENOUGH_MONEY";
                            $this->_error_message   = "Số dư tài khoản của quý khách không đủ để duyệt đơn hàng này. Vui lòng nạp tiền để tạo đơn hàng.";
                            $this->_additional['money_total'] =  $CheckBalance['money_total'] + 200000;
                            $this->_additional['fee']         = ($OrderDetail->sc_pvc + $OrderDetail->sc_cod + $OrderDetail->sc_pbh - $OrderDetail->sc_discount_pvc - $OrderDetail->sc_discount_cod);
                            return false;
                        }
                    }else{
                        if(($CheckBalance['money_total'] - $OrderDetail->sc_pvc - $OrderDetail->sc_cod - $OrderDetail->sc_pbh + $OrderDetail->sc_discount_pvc + $OrderDetail->sc_discount_cod) < 0){
                            $this->_error           = true;
                            $this->_message         = "NOT_ENOUGH_MONEY";
                            $this->_error_message   = "Số dư tài khoản của quý khách không đủ để duyệt đơn hàng này. Vui lòng nạp tiền để tạo đơn hàng.";
                            $this->_additional['money_total'] =  $CheckBalance['money_total'];
                            $this->_additional['fee']         = ($OrderDetail->sc_pvc + $OrderDetail->sc_cod + $OrderDetail->sc_pbh - $OrderDetail->sc_discount_pvc - $OrderDetail->sc_discount_cod);
                            return false;
                        }
                    }


                    // if($Order->domain == 'boxme.vn'){
                    if($Order->domain == 'boxme.vn' && !empty($Order->warehouse) && !preg_match("/^BX/i", $Order->order_code) ){
                        
                        $OrderItemModel = new \ordermodel\OrderItemModel();
                        
                        $CheckBSIN   = $OrderItemModel->BMCheckBSINAvailableInStock($Order->id, $Order->warehouse);
                        if(!$CheckBSIN['available']){
                           $Status = 120; // Thiếu hàng
                           $this->_message = "OUT_OF_STOCK";
                           $this->_error_message = "Sản phẩm đã hết hàng trong kho !"; 
                        }else {
                           $Status = 21; // Thiếu hàng
                           foreach($CheckBSIN['available_bsin'] as $value){
                               \warehousemodel\StatisticReportProductModel::PlusInventoryWait($value['sku'], $value['quantity'], $Order->warehouse, $Order);
                           } 
                        }
                    }


                    if(!empty($Coupon)){ // update coupon nếu đơn được duyệt
                        try{
                            $CheckCoupon->increment('usaged',1);
                        }catch (Exception $e){
                            $this->_error           = true;
                            $this->_message         = "UPDATE_COUPON_ERROR";
                            $this->_error_message   = "Cập nhật mã khuyến mãi thất bại, hãy thử lại";
                            return false;
                        }
                    }



                    $Order->time_accept = $this->time();

                    //update first accept order time
                    try{
                        $CustomerAdmin  = \omsmodel\CustomerAdminModel::firstOrCreate(['user_id' => (int)$UserInfo['id']]);
                        if($CustomerAdmin->first_accept_order_time < 10){
                            $CustomerAdmin->first_accept_order_time     = $this->time();
                            $CustomerAdmin->first_tracking_code         = $Order->tracking_code;
                        }
                        $CustomerAdmin->last_accept_order_time  = $this->time();
                        $CustomerAdmin->save();
                    }catch (Exception $e){

                    }


                    $this->UpdateFeeze($CheckBalance['merchant'], $OrderDetail->sc_pvc + $OrderDetail->sc_cod + $OrderDetail->sc_pbh - $OrderDetail->sc_discount_pvc - $OrderDetail->sc_discount_cod);
                    // Update feeze

                }

                $UpdateJourney  =  $this->changeStatus($Status, $Order->status);
                if($UpdateJourney['error']){
                    return $UpdateJourney;
                }
                $Order->status      = $Status;
                $this->__InsertLogBoxme($Order, $Status);
                if(in_array($Status, [52,53,66])){ // cập nhật trạng thái cuối
                    $Order->time_success = $this->time();
                }
            }

            // Caculate fee

            if(!empty($TotalWeight) && $Order->total_weight != $TotalWeight){
                $this->changeWeight($TotalWeight, $Order->total_weight);
                /** 
                * @desc Update old weight to over_weight field in database
                * @writer ThinhNV 
                */
                
                $Order->over_weight += $TotalWeight - $Order->total_weight;

                $Order->total_weight = $TotalWeight;
            }

            if(!empty($Service) && $Order->service_id != $Service){
                if(($Order->domain == 'boxme.vn' && !in_array($Service, [3,4])) || ($Order->domain != 'boxme.vn' && !in_array($Service, [1,2]))){ // dịch vụ boxme 3,4
                    $this->_error           = true;
                    $this->_message         = "SERVICE_ERROR";
                    $this->_error_message   = "Dịch vụ không phù hợp, vui lòng liên hệ bộ phận CSKH.";
                    return false;
                }

                $this->changeService($Service, $Order->service_id);
                $Order->service_id = $Service;
            }
            if(!empty($Courier) && $Order->courier_id != $Courier){
                if(($Order->status > 20 )){ // dịch vụ boxme 3,4
                    $this->_error           = true;
                    $this->_message         = "COURIER_ERROR";
                    $this->_error_message   = "Đơn hàng đã duyệt, không thể đổi hãng vận chuyển.";
                    return false;
                }

                $this->changeCourier($Courier, $Order->courier_id);
                $Order->courier_id = $Courier;
            }



            if(!empty($FromAddressId) && $Order->from_address_id != $FromAddressId){
                $UserInventoryModel = new UserInventoryModel;
                $Inventory          = $UserInventoryModel->where('id',(int)$FromAddressId)->where('user_id', $UserInfo['id'])->first();
                if(!isset($Inventory->id)){
                    $this->_error           = true;
                    $this->_message         = "INVENTORY_NOT_EXISTS";
                    $this->_error_message   = "Bạn không thể cập nhật đơn hàng này, vui lòng liên hệ bộ phận CSKH.";
                    return false;
                }

                $this->changeFromAddressId($FromAddressId, $Order->from_address_id);
                $Order->from_address_id     = $FromAddressId;
                $Order->from_city_id        = $Inventory->city_id;
                $Order->from_district_id    = $Inventory->province_id;
                $Order->from_ward_id        = $Inventory->ward_id;
                $Order->from_address        = $Inventory->address;
            }

            if(!empty($ToAddressId) && $Order->to_address_id != $ToAddressId){
                $this->changeToAddressLog($ToAddressId, $Order->to_address_id);
                $Order->to_address_id   = $ToAddressId;
                $Order->to_district_id  = $ToDistrictId;
            }

            $Order->time_update = $this->time();

            try{
                $Order->save();
            }catch (Exception $e){
                DB::connection('orderdb')->rollBack();
                $this->_error           = true;
                $this->_message         = "ORDER_UPDATE_FAIL";
                $this->_error_message   = "Bạn không thể cập nhật đơn hàng này, vui lòng liên hệ bộ phận CSKH.";
                return false;
            }
        }

        $this->_error           = false;
        return false;
    }

    private function caculateCoupons($_orderDetail, $CheckCoupon){
        $Discount   = $CheckCoupon->discount;

        // 2 : Theo phần trăm
        if($CheckCoupon->discount_type == 2){
            if($CheckCoupon->id == 1801 || $CheckCoupon->id == 1802){
                $Discount   = ($_orderDetail->sc_pvc - $_orderDetail->sc_discount_pvc) * $CheckCoupon->discount;
            }else {
                $Discount   = ($_orderDetail->sc_pvc + $_orderDetail->sc_cod + $_orderDetail->sc_pbh - $_orderDetail->sc_discount_pvc - $_orderDetail->sc_discount_cod) * $CheckCoupon->discount;
            }
        }

        // Giảm phí vận chuyển
        $CheckDiscount   = $Discount - ($_orderDetail->sc_pvc - $_orderDetail->sc_discount_pvc);
        if($CheckDiscount < 0 || $CheckDiscount == 0){
            $_orderDetail->sc_discount_pvc  += $Discount;
            return $_orderDetail;
        }

        $_orderDetail->sc_discount_pvc  = $_orderDetail->sc_pvc;
        $Discount                       = $CheckDiscount;

        // Nếu sau khi giảm PVC mà vẫn còn thừa tiền -> giảm tiếp phí CoD
        if($Discount > ($_orderDetail->sc_cod - $_orderDetail->sc_discount_cod)){
            $_orderDetail->sc_discount_cod  = $_orderDetail->sc_cod;
        }else{
            $_orderDetail->sc_discount_cod  += $Discount;
        }

        return $_orderDetail;
    }

    private function UpdateOrderItem(){
        $Description      = trim(Input::get('description'));

        $OrderItemModel = new OrderItemModel;
        $OrderItem      = $OrderItemModel->where('order_id',$this->OrderId)->first();
        if(empty($OrderItem)){
            $this->_error        = true;
            $this->_message      = "ORDER_ITEM_NOT_EXISTS";
            $this->_error_mesage = "Bạn không thể cập nhật đơn hàng này, vui lòng liên hệ bộ phận CSKH.";
            return false;
        }

        if(!empty($Description) && $OrderItem->description != $Description){
            $this->data_log['description']   = [
                'type'          => 'description',
                'new'           => $Description,
                'old'           => $OrderItem->description
            ];
            $OrderItem->description = $Description;
        }

        try{
            $OrderItem->save();
        }catch (Exception $e){
            DB::connection('orderdb')->rollBack();
            $this->_error        = true;
            $this->_message      = "ORDER_UPDATE_FAIL";
            $this->_error_mesage = "Bạn không thể cập nhật đơn hàng này, vui lòng liên hệ bộ phận CSKH.";
            return false;
        }

    }

    private function changeStatus($Status, $OldStatus,$note ="")
    {   
        if(empty($note)){
            $note = Input::has('note') ? Input::get('note') : "";
        }
        if ($Status >= 30) { // Chờ lấy hàng
            $JouneyModel = new \ordermodel\StatusModel;

            try {
                $JouneyModel::insertGetId(array(
                    'order_id' => $this->OrderId,
                    'status' => $Status,
                    'note' => $note,
                    'time_create' => $this->time()
                ));
            } catch (Exception $e) {
                return ['error' => true, 'message' => 'INSERT_JOURNEY_FAIL'];
            }
        }

        $this->data_log['status']   = [
            'type'          => 'status',
            'new'           => $Status,
            'old'           => $OldStatus
        ];

        return ['error' => false,'message'=> 'ACCEPTED'];
    }

    private function changeWeight($Weight, $OldWeight){
        $this->data_log['total_weight']   = [
            'type'          => 'total_weight',
            'new'           => $Weight,
            'old'           => $OldWeight
        ];

        $this->data_calculate['total_weight']    = $OldWeight;
    }

    private function changeService($Service, $OldService){
        $this->data_log['service_id']   = [
            'type'          => 'service_id',
            'new'           => $Service,
            'old'           => $OldService
        ];

        $this->data_calculate['service_id']    = $OldService;
    }

    private function changeCourier($Courier, $OldCourier){
        $this->data_log['courier_id']   = [
            'type'          => 'courier_id',
            'new'           => $Courier,
            'old'           => $OldCourier
        ];

        $this->data_calculate['courier_id']    = $Courier;
    }



    private function changeFromAddressId($FromAddress, $OldFromAddress){
        $this->data_log['from_address_id']   = [
            'type'          => 'from_address_id',
            'new'           => $FromAddress,
            'old'           => $OldFromAddress
        ];

        $this->data_calculate['from_address_id']    = $FromAddress;
    }

    private function changeToAddressId(){
        if(Input::has('to_address_id') || Input::has('to_city_id') || Input::has('to_province_id')
            || Input::has('to_ward_id') || Input::has('to_address')
        ){
            $ToCity         = (int)Input::get('to_city_id');
            $ToProvince     = (int)Input::get('to_province_id');
            $ToWard         = (int)Input::get('to_ward_id');
            $ToAddress      = trim(Input::get('to_address'));

            if(empty($ToCity) || empty($ToProvince) || empty($ToAddress)){
                $this->_error   = true;
                $this->_message = 'DATA_UPDATE_ERROR';
                return false;
            }

            $AddressModel   = new AddressModel;
            try{
                $Address = $AddressModel::firstOrCreate(['seller_id' => $this->UserId, 'city_id' => $ToCity,
                    'province_id' => $ToProvince, 'ward_id' => $ToWard, 'address' => $ToAddress]);
            }catch(Exception $e){
                $this->_error   = true;
                $this->_message = 'CREATE_ADDRESS_ERROR';
                return false;
            }
            return $Address->id;
        }
    }

    private function changeToAddressLog($ToAddress, $OldToAddress){ // Insert log
        $this->data_log['to_address_id']   = [
            'type'          => 'to_address_id',
            'new'           => $ToAddress,
            'old'           => $OldToAddress
        ];

        $this->data_calculate['to_address_id']    = $ToAddress;
    }

    // name , quantity, description
    private function changeItem($Field, $Data, $OldData){
        $this->data_log[$Field]   = [
            'type'          => $Field,
            'new'           => $Data,
            'old'           => $OldData
        ];

        return ['error' => false];
    }

    // Check Balance
    private function CheckBalance(){
        $Model      = new   MerchantModel;

        try{
            $Merchant   = $Model::firstOrCreate(['merchant_id' => $this->UserId]);
        }catch(Exception $e){
            return ['error' => true,'message'   => 'GET_MERCHANT_FAIL'];
        }

        if(!isset($Merchant->active) || $Merchant->active != 1){
            return ['error' => true,'message'   => 'USER_NOT_ALLOW_ACCEPT'];
        }

        if(empty($Merchant->balance)){
            $Merchant->balance = 0;
        }

        if(empty($Merchant->freeze)){
            $Merchant->freeze = 0;
        }

        $Total = $Merchant->balance - $Merchant->freeze + $Merchant->provisional - $Merchant->warehouse_freeze;

        if($Merchant->level >= 2){
            $Total += $Merchant->quota;
        }

        return ['error' => false,'money_total'   => $Total, 'merchant' => $Merchant, 'level' => $Merchant->level];
    }

    private function calculateFee($Order, $OrderDetail){
        if(isset($this->data_calculate['to_address_id']) && $this->data_calculate['to_address_id'] > 0){
            $ToAddressId    = (int)$this->data_calculate['to_address_id'];
        }else{
            $ToAddressId    = (int)$Order->to_address_id;
        }

        $OrderAddress   = new AddressModel;
        $ToAddress      = $OrderAddress->find($ToAddressId);
        if(empty($ToAddress)){
            $this->_error = true;
            $this->_message = 'TO_ADDRESS_NOT_EXISTS';
            return false;
        }

        if(Input::has('money_collect')){
            if($Order->status > 20){
                $this->_error         = true;
                $this->_error_message = "Bạn không được phép thu hộ sửa đơn này";
                $this->_message       = "USER_NOT_ALLOW";
                return false;
            }

            $this->changeItem('money_collect', Input::get('money_collect'), $OrderDetail->money_collect);
            $OrderDetail->money_collect     = trim(Input::get('money_collect'));
        }

        $this->data_calculate['money_collect']      = $OrderDetail->money_collect;


        if(isset($this->data_calculate['Protect']) && !empty($this->data_calculate['Protect'])){
            $_protect   = $this->data_calculate['Protect'];
        }else {
            $_protect   = $OrderDetail->sc_pbh > 0 ? 1 : 2;
        }

        $DataUpdate = [
            'From'  => [
                'City'      => (int)$Order->from_city_id,
                'Province'  => (int)$Order->from_district_id,
                'Ward'      => (int)$Order->from_ward_id,
                'Stock'     => (int)$Order->from_address_id
            ],
            'To'    => [
                'Country'   => (int)$Order->to_country_id,
                'City'      => (int)$ToAddress->city_id,
                'Province'  => (int)$ToAddress->province_id,
                'Ward'      => (int)$ToAddress->ward_id
            ],
            'Order' => [
                'Amount'    => $Order->total_amount,
                'Weight'    => $Order->total_weight,
                'Collect'   => $OrderDetail->money_collect,
                'Code'      => $Order->order_code
            ],
            'Config'    => [
                'Checking'  => $Order->checking,
                'Fragile'   => $Order->fragile,
                'Service'   => $Order->service_id,
                'Protected' => $_protect,
                'CoD'       => $OrderDetail->money_collect  > 0 ? 1 : 2,
                'Payment'   => $OrderDetail->seller_pvc     > 0 ? 2 : 1
            ],
            'Domain'        => !empty($Order->domain) ? $Order->domain : 'shipchung.vn',
            'Type'          => 'change'
        ];

        if(isset($this->data_calculate['courier_id']) && !empty($this->data_calculate['courier_id'])){
            $DataUpdate['Courier'] = (int)$this->data_calculate['courier_id'];
        }
        
        Input::merge($DataUpdate);

        if($Order->to_country_id == 237){
            // Chuyển phát trong nước
            $ApiCourierCtrl = new \ApiCourierCtrl;
        }else{
            // Chuyển phát quốc tế
            $ApiCourierCtrl = new \ApiGlobalCtrl;
        }

        $Calculater     = $ApiCourierCtrl->postCalculate(false);
        if($Calculater['error']){
          return $Calculater;
        }

        $Data       = $Calculater['data'];

        // change  estimate_delivery
        if(isset($Data['courier'])){
            if(isset($Data['courier']['me'])){
                foreach($Data['courier']['me'] as $val){
                    if($val['courier_id'] == $Order->courier_id){
                        $Courier = $val;
                    }
                }
            }
            if(isset($Data['courier']['system']) && !isset($Courier)){
                foreach($Data['courier']['system'] as $val){
                    if($val['courier_id'] == $Order->courier_id){
                        $Courier = $val;
                    }
                }
            }

            if(!isset($Courier)){
                if(isset($Data['courier']['me'][0])){
                    $Courier    = $Data['courier']['me'][0];
                }elseif($Data['courier']['system'][0]){
                    $Courier    = $Data['courier']['system'][0];
                }
            }

            if(isset($Courier)){
                // OverWeight
                $Data['pvc'] = $Data['pvc'] + $Courier['money_pickup'];

                if($Data['pvc'] != ($OrderDetail->sc_pvc + $OrderDetail->sc_pvk)){
                    // Đơn hàng nhập kho boxme
                    if($Order->domain == 'boxme.vn' && preg_match("/^BX/i", $Order->order_code)){
                        $this->data_log['sc_pvc']   = [
                            'type'          => 'sc_pvc',
                            'new'           => trim($Data['pvc']),
                            'old'           => $OrderDetail->sc_pvc
                        ];
                        $this->data_log['sc_discount_pvc']   = [
                            'type'          => 'sc_discount_pvc',
                            'new'           => trim($Data['discount']['pvc']),
                            'old'           => $OrderDetail->sc_discount_pvc
                        ];
                        $OrderDetail->sc_pvc                =   trim($Data['pvc']);
                        $OrderDetail->sc_discount_pvc       =   trim($Data['discount']['pvc']);
                    }else{
                        if(isset($this->data_calculate['total_weight']) && $Order->total_weight != $this->data_calculate['total_weight'] && $Order->status > 20){
                            // Nếu phí vc + pvk cũ  nhỏ hơn pvc mới  =>  pvk mới =  pvc mới - phí vc cũ
                            if($Data['pvc'] >= ($OrderDetail->sc_pvc + $OrderDetail->sc_pvk)){
                                $Pvk    = (trim($Data['pvc']) - $OrderDetail->sc_pvc);
                            }else{
                                if($Data['pvc'] < $OrderDetail->sc_pvc){
                                    $Pvk    = 0;
                                    $Pvc    = $Data['pvc'];

                                    $this->data_log['sc_pvc']   = [
                                        'type'          => 'sc_pvc',
                                        'new'           => trim($Data['pvc']),
                                        'old'           => $OrderDetail->sc_pvc
                                    ];
                                    $OrderDetail->sc_pvc  =   trim($Data['pvc']);
                                }else{
                                    $Pvk    = $Data['pvc'] - $OrderDetail->sc_pvc;
                                }
                            }

                            $this->data_log['sc_pvk']   = [
                                'type'          => 'sc_pvk',
                                'new'           => $Pvk,
                                'old'           => $OrderDetail->sc_pvk
                            ];
                            $OrderDetail->sc_pvk  =   $Pvk > 0 ? $Pvk : 0;
                        }else{
                            $this->data_log['sc_pvc']   = [
                                'type'          => 'sc_pvc',
                                'new'           => trim($Data['pvc']),
                                'old'           => $OrderDetail->sc_pvc
                            ];
                            $OrderDetail->sc_pvc  =   trim($Data['pvc']);
                        }
                    }
                }

                $Leadtime = $Courier['leatime_delivery'] + $Courier['leatime_ward'];
                if(($Leadtime) != $Order->estimate_delivery || ($Courier['courier_id'] != $Order->courier_id && $Order->status == 20)){
                    if(($Leadtime) != $Order->estimate_delivery) {
                        $this->data_log['leatime_delivery'] = [
                            'type' => 'leatime_delivery',
                            'new' => $Leadtime,
                            'old' => $Order->estimate_delivery
                        ];
                        $Order->estimate_delivery = $Leadtime;
                    }

                    if(($Courier['courier_id'] != $Order->courier_id) && $Order->status == 20){
                        $this->data_log['courier_id'] = [
                            'type' => 'courier_id',
                            'new' => $Courier['courier_id'],
                            'old' => $Order->courier_id
                        ];
                        $Order->courier_id = $Courier['courier_id'];
                    }

                    try{
                        $Order->save();
                    }catch (Exception $e){
                        DB::connection('orderdb')->rollBack();
                        $this->_error = true;
                        $this->_message = 'UPDATE_ORDER_COURIER_LEATIME_FAIL';
                        return false;
                    }
                }
            }

        }

        if($Data['vas']['cod'] != $OrderDetail->sc_cod){
            $this->data_log['sc_cod']   = [
                'type'          => 'sc_cod',
                'new'           => trim($Data['vas']['cod']),
                'old'           => $OrderDetail->sc_cod
            ];

            $OrderDetail->sc_cod  =   trim($Data['vas']['cod']);
        }

        if($Data['vas']['protected'] != $OrderDetail->sc_pbh){
            $OrderDetail->sc_pbh = $Data['vas']['protected'];
            $this->data_log['sc_pbh']   = [
                'type'          => 'sc_pbh',
                'new'           => trim($Data['vas']['protected']) ,
                'old'           => $OrderDetail->sc_pbh
            ];
        }

        /*if(isset($DataFee['collect']) && $DataFee['collect'] != $this->data_calculate['money_collect']){
            $OrderDetail->money_collect = $DataFee['collect'];
            $this->data_log['money_collect']   = [
                'type'          => 'money_collect',
                'old'           => $OrderDetail->money_collect,
                'new'           => $this->data_calculate['money_collect']
            ];
        }*/

        if(isset($this->data_calculate['from_address_id']) && isset($Data['courier'])){
            if(isset($Data['courier']['me'])){
                $this->data_log['courier_id']   = [
                    'type'          => 'courier_id',
                    'new'           => (int)$Data['courier']['me'][0]['courier_id'],
                    'old'           => $Order->courier_id
                ];
                $this->data_update_order['courier_id']  = (int)$Data['courier']['me'][0]['courier_id'];
            }elseif(isset($Data['courier']['system'])){
                $this->data_log['courier_id']   = [
                    'type'          => 'courier_id',
                    'new'           => (int)$Data['courier']['system'][0]['courier_id'],
                    'old'           => $Order->courier_id
                ];
                $this->data_update_order['courier_id']  = (int)$Data['courier']['system'][0]['courier_id'];
            }
        }

        try{
            $OrderDetail->save();
        }catch (Exception $e){
            DB::connection('orderdb')->rollBack();
            $this->_error   = true;
            $this->_message = 'UPDATE_ORDER_DETAIL_FAIL';
            return false;
        }

        $this->_error                             = false;
        $this->_additional['fee']                 = $Data;
        $this->_additional['estimate_delivery']   = $Order->estimate_delivery;

        return false;        
    }

    private function UpdateFeeze($Merchant, $total_fee){
        if($Merchant->level ==  3){ // khách hàng được bảo lãnh - không tạm giữ
            return;
        }

        $Merchant->freeze += $total_fee;

        try{
            $Merchant->save();
        }catch(Exception $e){

        }
    }


    /**
     * Xác nhận chuyển hoàn
     */
    private function ConfirmReturn($Order){
        $Status = Input::has('status') ? Input::get('status') : 0;
        if($Status == 61){
            $this->data_log['status']   = [
                'type'          => 'status',
                'new'           => $Status,
                'old'           => $Order->status
            ];
            $this->changeStatus(61, $Order->status, "Người bán xác nhận chuyển hoàn");

            $Order->status      = $Status;
            $Order->time_update = $this->time();

            try{
                $Order->save();
            }catch (Exception $e){
                $this->_error = true;
                $this->_message = 'ORDER_UPDATE_FAIL';
                return false;
            }
        }

        $this->_error = false;
        $this->_message = 'SUCCESS';
        $this->_error_message = 'ConfirmReturn';
         
        return false;
    }


    private function ConfirmPickup($Order){
        $Status = Input::has('status') ? Input::get('status') : 0;

        if($Status == 38){
            $this->data_log['status']   = [
                'type'          => 'status',
                'new'           => $Status,
                'old'           => $Order->status
            ];
            $this->changeStatus(38, $Order->status);

            $Order->status      = $Status;
            $Order->time_update = $this->time();

            try{
                $Order->save();
            }catch (Exception $e){
                return Response::json(['error'  => true, 'message'  => 'ORDER_UPDATE_FAIL']);
            }

            $this->__InsertLogBoxme($Order, $Status);
            $this->insertLog();
        }
        return Response::json(['error'  => false, 'message'  => 'SUCCESS', 'error_message'=> 'ConfirmPickup']);
    }

    /**
     * Giao lại
     */
    private function ConfirmDelivery($Order){
        $Status = Input::has('status') ? Input::get('status') : 0;
        
        $this->data_log['status']   = [
            'type'          => 'status',
            'new'           => $Status,
            'old'           => $Order->status
        ];

        $this->changeStatus($Status, $Order->status);

        $Order->status      = $Status;
        $Order->time_update = $this->time();

        try{
            $Order->save();
        }catch (Exception $e){
            return Response::json(['error'  => true, 'message'  => 'ORDER_UPDATE_FAIL']);
        }

        $this->__InsertLogBoxme($Order, $Status);
        $this->insertLog();
        
        return Response::json(['error'  => false, 'message'  => 'SUCCESS', 'error_message'=> 'ConfirmPickup']);
    }

    /**
     * Báo hủy đơn hàng
     */
    private function ReportCancel($Order){
        $Status = Input::has('status') ? Input::get('status') : 0;
        if($Status == 28){
            $OldStatus = $Order->status;
            $this->data_log['status']   = [
                'type'          => 'status',
                'new'           => $Status,
                'old'           => $OldStatus
            ];

            $CheckBalance = $this->CheckBalance();
            if($CheckBalance['error']){
                return $CheckBalance;
            }

            $DetailModel = new DetailModel;
            $OrderDetail = $DetailModel->where('order_id', $this->OrderId)->first();
            if (empty($OrderDetail)) {
                $this->_error = true;
                $this->_message  = 'ORDER_DETAIL_NOT_EXISTS';
                return false;
            }
            
            if( $Order->domain == 'boxme.vn' && !empty($Order->warehouse) && !preg_match("/^BX/i", $Order->order_code)){

                if(in_array($Order->status, [101, 103, 120])){
                    $Status = 121;
                }
                // Cập nhật lại tồn kho
                $OrderItems = \ordermodel\OrderItemModel::where('order_id', $Order->id)->get()->toArray();
                foreach($OrderItems as $value){
                    if(!empty($value['bsin'])){
                        \warehousemodel\StatisticReportProductModel::MinusInventoryWait($value['bsin'], $value['quantity'], $Order->warehouse, $Order);
                    }
                }
            }

            $Order->status      = $Status;

            $Order->time_update = $this->time();



            try{
                $Order->save();
            }catch (Exception $e){
                $this->_error = true;
                $this->_message  = 'ORDER_UPDATE_FAIL';
                return false;
            }

            $this->__InsertLogBoxme($Order, $Status);
            $this->UpdateFeeze($CheckBalance['merchant'], -($OrderDetail->sc_pvc + $OrderDetail->sc_cod + $OrderDetail->sc_pbh + $OrderDetail->sc_pvk - $OrderDetail->sc_discount_pvc - $OrderDetail->sc_discount_cod));
            $this->insertLog();

            


            //Update log pickup slow
            $LMongo         = new \LMongo;
            $LMongo::collection('log_journey_pickup')->where('tracking_code', $Order->tracking_code)->update(['active' => 0]);

            

        }

        $this->_error    = false;
        $this->_message  = 'SUCCESS';
        $this->_error_message  = 'ReportCancel';
        

        return false;

    }

    private function insertLog(){
        $this->data_log['order_id']     = $this->OrderId;
        $this->data_log['time_create']  = $this->time();
        $this->data_log['user_id']      = $this->UserEdit;
        \LMongo::collection('log_change_order')->insert($this->data_log);
        try{

        }catch(Exception $e){
            $this->_error   = true;
            $this->_message = 'INSERT_LOG_FAIL';
            return false;
        }

        $this->_error   = false;
        return true;
        
        return ['error' => false];
    }


    /*
     * Insert log_journey_notice
     */
    private function __order_detail($id){
        return DetailModel::where('order_id', $id)->first();
    }

    private function __check_discount($Order, $OrderDetail){
        if($this->time() >= 1475254800 && $this->time() <= 1483203600 && $Order->service_id == 2 && $Order->domain != 'boxme.vn'){
            $ListLoyalty = \loyaltymodel\UserModel::where('level','>',0)->where('active',1)->remember(10)->lists('user_id');
            if(in_array($Order->from_user_id, $ListLoyalty)){ // KHTT
                $Discount   = (0.1*$OrderDetail->sc_pvc);
                $OrderDetail->sc_discount_pvc       = $Discount > $OrderDetail->sc_discount_pvc ? $Discount : $OrderDetail->sc_discount_pvc;
            }else{
                $Discount   = (0.05*$OrderDetail->sc_pvc);
                $OrderDetail->sc_discount_pvc       = $Discount > $OrderDetail->sc_discount_pvc ? $Discount : $OrderDetail->sc_discount_pvc;
            }
            return true;
        }
        return false;
    }

    private function request_cancel_boxme($TrackingCode){
        $Params = [
            'TrackingCode'       => $TrackingCode
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

    public function __InsertLogBoxme($Order, $Status){
        if(in_array($Order->domain, ['boxme.vn', 'chodientu.vn','prostore.vn','juno.vn','www.ebay.vn'])){
            $Detail = $this->__order_detail($Order->id);

            $LMongo         = new LMongo;
            $Id = $LMongo::collection('log_journey_notice')->insert([
                'tracking_code'         => $Order->tracking_code,
                'courier_tracking_code' => $Order->courier_tracking_code,
                'domain'        => $Order->domain,
                'status'        => (int)$Status,
                'time'          => [
                    'time_create'   => $Order->time_create,
                    'time_accept'   => $Order->time_accept,
                    'time_approve'  => $Order->time_approve,
                    'time_pickup'   => $Order->time_pickup,
                    'time_success'  => $Order->time_success
                ],
                'fee'           => [
                    'sc_pvc'            => $Detail->sc_pvc,
                    'sc_cod'            => $Detail->sc_cod,
                    'sc_pbh'            => $Detail->sc_pbh,
                    'sc_pvk'            => $Detail->sc_pvk,
                    'sc_discount_pvc'   => $Detail->sc_discount_pvc,
                    'sc_discount_pcod'  => $Detail->sc_discount_cod
                ],
                'weight'        => $Order->total_weight,
                'accept'        => 0,
                'time_create'   => $this->time()
            ]);

            $this->PredisAcceptBoxme((string)$Id);
        }
        return;
    }

    private function __insert_log_problem($Order){
        // 1 : Phát thất bại
        // 2 : Chờ xác nhận chuyển hoàn
        // 3 : Lấy không thành công
        // 4 : Đơn hàng vượt cân

        try{
            $Obj = \ordermodel\OrderProblemModel::insert([
                'order_id'          => $Order->id,
                'tracking_code'     => $Order->tracking_code,
                'user_id'           => (int)$Order->from_user_id,
                'type'              => 4,
                'status'            => 0,
                'action'            => 0,
                'reason'            => '',
                'postman_phone'     => '',
                'postman_name'      => '',
                'time_create'       => time(),
                'time_update'       => time()
            ]);
        }catch (\Exception $e){
            return false;
        }

        return true;
    }
}
