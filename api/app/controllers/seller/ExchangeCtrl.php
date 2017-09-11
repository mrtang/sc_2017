<?php
namespace seller;

use omsmodel\ExchangeModel;
use ordermodel\OrdersModel;
use sellermodel\UserInventoryModel;
use ApiCourierCtrl;

class ExchangeCtrl extends BaseCtrl
{
    private $error          = false;
    private $code           = 'SUCCESS';
    private $message        = 'Thành Công';
    private $data;
    private $item;
    private $total          = 0;
    private $tracking_code  = '';
    private $validation;

    private function getModel(){
        $TimeCreateStart    = Input::has('create_start')        ? (int)Input::get('create_start')           : 0; // time_create start   time_stamp
        $TimeCreateEnd      = Input::has('create_end')          ? (int)Input::get('create_end')             : 0; // time_create end
        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')           : 0; // time_accept start
        $TimeAcceptEnd      = Input::has('accept_end')          ? (int)Input::get('accept_end')             : 0; // time_accept end
        $Search             = Input::has('search')              ? trim(Input::get('search'))                : '';
        $Type               = Input::has('type')                ? (int)Input::get('type')                   : 0;
        $Active             = Input::has('active')              ? (int)Input::get('active')                 : null;
        $Tab                = Input::has('tab')                 ? trim(Input::get('tab'))                   : 'ALL';

        $UserInfo           = $this->UserInfo();
        $Model              = new ExchangeModel;

        $Model              = $Model::where('from_user_id', (int)$UserInfo['id']);

        if($TimeCreateStart < ($this->time() - 86400*62)) $TimeCreateStart = $this->time() - 86400*62;

        if(!empty($TimeCreateStart)){
            $Model              = $Model->where('time_create','>=',$TimeCreateStart);
        }

        if(!empty($TimeCreateEnd)){
            $Model              = $Model->where('time_create','<=',$TimeCreateEnd);
        }

        if(!empty($TimeAcceptStart)){
            $Model          = $Model->where('time_accept','>=',$TimeAcceptStart);
        }

        if(!empty($TimeAcceptEnd)){
            $Model          = $Model->where('time_accept','<=',$TimeAcceptEnd);
        }

        if(isset($Active)){
            if($Active == 4){
                $Model          = $Model->where('active',1)->where('receiver_order_code','');
            }elseif($Active == 5){
                $Model          = $Model->where('active',1)->where('type',2)->where('sender_order_code','');
            }else{
                $Model          = $Model->where('active',$Active);
            }
        }

        if($Tab != 'ALL'){
            $Model          = $Model->where('type',$Tab);
        }

        if(!empty($Type)){
            $Model          = $Model->where('type',$Type);
        }

        if(!empty($Search)){
            if(filter_var((int)$Search, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $Model          = $Model->where('to_phone','LIKE','%'.$Search.'%');
            }else{ // search code
                $Model          = $Model->where('tracking_code',$Search);
            }
        }

        return $Model;
    }


    private function getOrderAddress($id = 0){
        $Address    = \ordermodel\AddressModel::where('id',$id)->first();
        return $Address;
    }

    /*
    * get Order
    */
    private function getOrder($id){
        $Order  = OrdersModel::where('id', $id)
            ->first(['id','tracking_code','fragile','child_id','from_user_id','to_name',
                'to_phone','from_address_id','from_city_id','from_district_id','from_ward_id',
                'from_address','to_address_id','product_name','total_weight','total_quantity','total_amount']);

        if(!isset($Order->id)){
            $this->code         = 'ORDER_NOT_EXISTS';
            $this->message      = 'Đơn hàng không tồn tại';
            return false;
        }
        return $Order;
    }

    /*
     *  create Inventory
     */
    private function CreateInventory($Data){
        return UserInventoryModel::firstOrCreate($Data);
    }

    /*
     * get Inventory
     */
    private function GetInventory($Id){
        return UserInventoryModel::where('id',$Id)->first();
    }

    public function getIndex(){
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $itemPage           = Input::has('item_page')           ? Input::get('item_page')                       : 20;

        $Model      = $this->getModel();
        $TotalModel = clone $Model;

        $this->total = $TotalModel->count();

        if($this->total > 0){
            $itemPage       = (int)$itemPage;
            $offset         = ($page - 1)*$itemPage;
            $Model          = $Model->skip($offset)->take($itemPage);
            $this->data     = $Model->with('get_image')->get()->toArray();
        }

        return $this->ResponseData(false);
    }

    private function ResponseData($error){
        return Response::json([
            'error'         => $error,
            'message'       => $this->message,
            'total'         => $this->total,
            'data'          => $this->data,
            'item'          => $this->item
        ]);
    }

    //Change Exchange
    public function postEdit(){
        $ExchangeId     = Input::has('exchange_id')     ? (int)Input::get('exchange_id')        : 0;
        $Active         = Input::has('active')          ? (int)Input::get('active')             : 0;

        if(empty($ExchangeId)){
            $this->message  = 'Kết nối dữ liệu thất bại, vui lòng thử lại';
            return $this->ResponseData(true);
        }

        $UserInfo       = $this->UserInfo();
        $Model          = new ExchangeModel;
        $Exchange               = $Model::where('id', $ExchangeId)->where('from_user_id', (int)$UserInfo['id'])->first();
        $Exchange->time_update  = $this->time();

        if(!isset($Exchange->id)){
            $this->message  = 'Yêu cầu đổi trả không tồn tại, vui lòng thử lại';
            return $this->ResponseData(true);
        }

        if($Exchange->active != 0){
            $this->message  = 'Yêu cầu đổi trả đã được xác nhận hoặc hủy.';
            return $this->ResponseData(true);
        }

        if($Active == 2){
            $Exchange->active           = $Active;
            $Exchange->user_accept      = (int)$UserInfo['id'];
            $Exchange->time_accept      = $this->time();
        }

        try{
            $Exchange->save();
        }catch (Exception $e){
            $this->message  = 'Cập nhật yêu cầu thất bại, vui lòng thử lại.';
            return $this->ResponseData(true);
        }

        $this->message  = 'Cập nhật yêu cầu thành công.';
        return $this->ResponseData(false);
    }

    //Show data
    public function getShow(){
        $ExchangeId     = Input::has('exchange_id')     ? (int)Input::get('exchange_id')        : 0;
        if(empty($ExchangeId)){
            $this->message  = 'Kết nối dữ liệu thất bại, vui lòng thử lại';
            return $this->ResponseData(true);
        }

        $UserInfo       = $this->UserInfo();
        $Model          = new ExchangeModel;
        $this->item     = $Model::where('id', $ExchangeId)->where('from_user_id', (int)$UserInfo['id'])->first(['id','order_id','tracking_code','from_user_id','receiver_order_code', 'sender_order_code','note','type']);
        if(!isset($this->item->id)){
            $this->message  = 'Yêu cầu đổi trả không tồn tại, vui lòng thử lại';
            return $this->ResponseData(true);
        }

        $OrdersModel    = new OrdersModel;
        $this->data     = $OrdersModel::where('time_accept','>=',$this->time() - $this->time_limit)
                                      ->where('id', (int)$this->item->order_id)
                                      ->with('ToOrderAddress')
                                      ->get(['id', 'tracking_code', 'from_address_id', 'from_city_id', 'from_district_id', 'from_ward_id', 'from_address', 'to_address_id',
                                          'product_name', 'total_weight', 'total_amount', 'total_quantity', 'to_name', 'to_phone', 'to_email'])->toArray();

        return $this->ResponseData(false);
    }

    private function _validation(){
        $dataInput = array(
            'exchange_id'           => 'required|numeric|min:1',
            'service_id'            => 'required|numeric|in:1,2',
            'courier_id'            => 'required|numeric|min:1',
            'checking'              => 'sometimes|required|numeric|in:1,2',
            'cod'                   => 'sometimes|required|numeric|in:1,2',
            'total_weight'          => 'sometimes|required|numeric|min:1',
            'total_amount'          => 'sometimes|required|numeric|min:1',
            'quantity'              => 'sometimes|required|numeric|min:1',
            'collect'               => 'sometimes|required|numeric|min:0',
            'cod'                   => 'sometimes|required|numeric|in:1,2', // 1: yes | 2: no
            'product_name'          => 'sometimes|required',
            'type'                  => 'required|numeric|in:1,2'
        );

        $this->validation = Validator::make(Input::all(), $dataInput);
    }

    public function postCreateReturn(){
        $ExchangeId         = Input::has('exchange_id')         ? (int)Input::get('exchange_id')        : 0;
        $ServiceId          = Input::has('service_id')          ? (int)Input::get('service_id')         : 0;
        $Protected          = Input::has('protected')           ? (int)Input::get('protected')          : 0;
        $CourierId          = Input::has('courier_id')          ? (int)Input::get('courier_id')         : 0;
        $Description        = Input::has('description')         ? trim(Input::get('description'))       : '';

        // đơn hàng đổi
        $Checking           = Input::has('checking')            ? (int)Input::get('checking')           : 1;
        $CoD                = Input::has('cod')                 ? (int)Input::get('cod')                : 2;

        $Weight             = Input::has('total_weight')        ? trim(Input::get('total_weight'))      : '';
        $Amount             = Input::has('total_amount')        ? trim(Input::get('total_amount'))      : '';
        $Quantity           = Input::has('quantity')            ? (int)Input::get('quantity')           : 0;
        $MoneyCollect       = Input::has('collect')             ? (int)Input::get('collect')            : 0;
        $ProductName        = Input::has('product_name')        ? trim(Input::get('product_name'))      : '';
        $Type               = Input::has('type')                ? (int)Input::get('type')               : 0; // 1- trả , 2 - đổi

        $this->_validation(false);
        if($this->validation->fails()) {
            $this->code         = 'INVALID';
            $this->message      = $this->validation->messages();
            return $this->ResponseCreate(true);
        }

        $UserInfo           = $this->UserInfo();
        $Exchange           = ExchangeModel::where('id', $ExchangeId)->where('from_user_id', (int)$UserInfo['id'])->first();

        if(!isset($Exchange->id)){
            $this->code         = 'NOT_EXISTS';
            $this->message      = 'Yêu cầu đổi trả không tồn tại';
            return $this->ResponseCreate(true);
        }

        if($Type == 1){
            if(!empty($Exchange->receiver_order_code)){
                $this->code         = 'RECEIVER_ORDER_EXISTS';
                $this->message      = 'Đơn hàng trả đã tồn tại';
                return $this->ResponseCreate(true);
            }
        }else{
            if(!empty($Exchange->sender_order_code)){
                $this->code         = 'SENDER_ORDER_EXISTS';
                $this->message      = 'Đơn hàng đổi đã tồn tại';
                return $this->ResponseCreate(true);
            }
        }


        $Order = $this->getOrder($Exchange->order_id);
        if(!$Order){
            return $this->ResponseCreate(true);
        }

        //get getOrderAddress
        $OrderAddress   = $this->getOrderAddress($Order->to_address_id);
        if(!isset($OrderAddress->id)){
            $this->code         = 'ORDER_ADDRESS_NOT_EXISTS';
            $this->message      = 'Địa chỉ người nhận không tồn tại';
            return $this->ResponseCreate(true);
        }

        $From   = [];
        $To     = [];

        if($Type == 1){ // trả
            // Get To  Address
            if($Order->from_address_id > 0){
                $FromAddress = $this->GetInventory($Order->from_address_id);
            }

            $From   = [
                'City'      => (int)$OrderAddress->city_id,
                'Province'  => (int)$OrderAddress->province_id,
                'Ward'      => (int)$OrderAddress->ward_id,
                'Name'      => $Order->to_name,
                'Phone'     => $Order->to_phone,
                'Address'   => $OrderAddress->address
            ];

            $To     = [
                'City'      => (int)$Order->from_city_id,
                'Province'  => (int)$Order->from_district_id,
                'Ward'      => (int)$Order->from_ward_id,
                'Name'      => isset($FromAddress->user_name)  ? $FromAddress->user_name  : $UserInfo['fullname'],
                'Phone'     => isset($FromAddress->phone)      ? $FromAddress->phone      : $UserInfo['phone'],
                'Address'   => $Order->from_address
            ];
        }else{ // đổi
            if($Order->from_address_id > 0){
                $From   = [
                    'Stock'     => (int)$Order->from_address_id
                ];
            }else{
                $From   = [
                    'City'      => (int)$Order->from_city_id,
                    'Province'  => (int)$Order->from_district_id,
                    'Ward'      => (int)$Order->from_ward_id,
                    'Name'      => $UserInfo['fullname'],
                    'Phone'     => $UserInfo['phone'],
                    'Address'   => $Order->from_address
                ];
            }


            $To     = [
                'City'      => $OrderAddress->city_id,
                'Province'  => $OrderAddress->province_id,
                'Ward'      => $OrderAddress->ward_id,
                'Address'   => $OrderAddress->address,
                'Phone'     => $Order->to_phone,
                'Name'      => $Order->to_name
            ];
        }


        $DataCreate = array(
            'From'      =>  $From,
            'To'        =>  $To,
            'Order'     => array(
                'Code'          => $Order->tracking_code,
                'Weight'        => !empty($Weight) ? $Weight : $Order->total_weight,
                'Amount'        => !empty($Amount) ? $Amount : $Order->total_amount,
                'Quantity'      => !empty($Quantity) ? $Quantity : $Order->total_quantity,
                'ProductName'   => !empty($ProductName) ? $ProductName : $Order->product_name,
                'Description'   => $Description,
                'Exchange'      => (int)$Exchange->id,
                'TypeExchange'  => $Type,
                'Collect'       => $MoneyCollect
            ),
            'Config'    => array(
                'Service'       => (int)$ServiceId,
                'CoD'           => $CoD,
                'Payment'       => 1,
                'Protected'     => (int)$Protected,
                'Checking'      => $Checking,
                'Fragile'       => (int)$Order->fragile
            ),
            'Domain'            => 'shipchung.vn',
            'Courier'           => $CourierId
        );

        Input::merge($DataCreate);
        $ApiCourierCtrl = new ApiCourierCtrl;
        $CreateLading   = $ApiCourierCtrl->postCreate(false);

        if($CreateLading['error']){
            $this->code         = $CreateLading['code'];
            $this->message      = $CreateLading['message'];
            return $this->ResponseCreate(true);
        }

        $this->tracking_code = $CreateLading['data']['TrackingCode'];
        // Update Exchange

        if($Type == 1){
            $Exchange->receiver_order_code  = $this->tracking_code;
            $Exchange->time_accept_receiver = $this->time();
        }else{
            $Exchange->sender_order_code  = $this->tracking_code;
            $Exchange->time_accept_sender = $this->time();
        }

        try{
            $Exchange->user_accept          = (int)$UserInfo['id'];
            $Exchange->active               = 1;
            $Exchange->save();
        }catch (Exception $e){
            $this->code         = 'ERROR';
            $this->message      = $e->getMessage();
            return $this->ResponseCreate(true);
        }

        return $this->ResponseCreate(false);
    }

    private function ResponseCreate($error){
        return Response::json([
            'error'         => $error,
            'code'          => $this->code,
            'message'       => $this->message,
            'tracking_code' => $this->tracking_code
        ]);
    }

    public function getTest(){
        return OrdersModel::whereRaw("MATCH(tracking_code) AGAINST(? IN BOOLEAN MODE)",['SC123'])->get();
    }
}
