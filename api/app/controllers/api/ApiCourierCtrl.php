<?php

class ApiCourierCtrl extends \BaseController {
    private $error          = false;
    public $message        = 'Thành Công';
    private $code           = 'SUCCESS';
    private $json           = true;
    private $validation, $insertBuyerAddress, $insertBuyer, $__DataLog;
    private $auto_accept    = 0;
    private $uCourier       = [];
    private $LCourier       = [];
    private $id_log         = '';
    private $data           = [];
    private $__TrackingCode = '';
    private $caculate_courier   = 7;

    private $list_vas       = [];
    private $output         = [
        'pvc'           => 0,
        'collect'       => 0,
        'vas'           => [
            'cod'           => 0,
            'protected'     => 0
        ],
        'discount'      => [
            'pvc'  => 0,
            'pcod'  => 0
        ],
        'seller'        => [
            'pvc'           => 0,
            'pcod'           => 0,
            'discount'      => 0
        ]
    ];

    private $user_id        = 0;
    private $child_id       = 0;
    private $courier        = 0;
    private $list_courier   = [];
    private $MerchantKey    = '';

    private $from_location  = 0;
    private $to_location    = 0;

    private $from_city      = 0;
    private $from_district  = 0;
    private $from_ward      = 0;
    private $from_name      = '';
    private $from_phone     = '';
    private $from_address   = '';
    private $stock          = 0;
    private $inventory      = 0;//Kho hàng từ chợ điện tử
    private $boxme_warehouse_code = "";

    private $to_city        = 0;
    private $to_district    = 0;
    private $to_ward        = 0;
    private $to_name        = '';
    private $to_phone       = '';
    private $to_address     = '';
    private $to_email       = '';

    private $to_buyer_id    = 0;
    private $item_id        = 0;

    private $weight         = 0;
    private $boxsize        = '';
    private $amount         = 0;
    private $product_name   = '';
    private $quantity       = 0;
    private $description    = '';
    private $order_code     = '';
    private $exchange_id    = 0;
    private $type_exchange  = 1;

    private $money_collect  = null;
    private $discount       = 0;

    //config
    private $service        = 2;
    private $cod            = 1;
    private $protected      = 2;
    private $payment        = 2;
    private $payment_cod    = 0;
    private $checking       = 2;
    private $fragile        = 2;

    private $domain         = 'shipchung.vn';
    private $type           = 'shipchung';
    private $token          = '';
    private $check_app      = false;

    private $fee_id         = 0;
    private $post_code      = 0;

    private $coupon_code    = "";
    private $coupon_info    = null;

    function __construct(){
        Input::merge(Input::json()->all());

        $this->MerchantKey          = Input::has('MerchantKey')     ? trim(Input::get('MerchantKey'))           : '';
        $this->domain               = Input::has('Domain')          ? trim(Input::get('Domain'))                : 'shipchung.vn';
        $this->courier              = Input::has('Courier')         ? (int)Input::get('Courier')                : 0;
        $this->type                 = Input::has('Type')            ? strtolower(trim(Input::get('Type')))      : 'shipchung';
        $this->token                = Input::has('Token')           ? strtolower(trim(Input::get('Token')))     : '';

        $this->from_city            = Input::has('From.City')       ? (int)Input::get('From.City')      : 0;
        $this->from_district        = Input::has('From.Province')   ? (int)Input::get('From.Province')  : 0;
        $this->from_ward            = Input::has('From.Ward')       ? (int)Input::get('From.Ward')      : 0;
        $this->from_address         = Input::has('From.Address')    ? trim(Input::get('From.Address'))  : '';
        $this->from_name            = Input::has('From.Name')       ? trim(Input::get('From.Name'))     : '';
        $this->from_phone           = Input::has('From.Phone')      ? trim(Input::get('From.Phone'))    : '';
        $this->stock                = Input::has('From.Stock')      ? (int)Input::get('From.Stock')     : 0;
        $this->inventory            = Input::has('From.Inventory')  ? trim(Input::get('From.Inventory')) : 0;// Kho hàng chợ điện tử
        $this->post_code            = Input::has('From.PostCode')   ? (int)Input::get('From.PostCode')  : 0;


        $this->to_city              = Input::has('To.City')         ? (int)Input::get('To.City')        : 0;
        $this->to_district          = Input::has('To.Province')     ? (int)Input::get('To.Province')    : 0;
        $this->to_ward              = Input::has('To.Ward')         ? (int)Input::get('To.Ward')        : 0;
        $this->to_name              = Input::has('To.Name')         ? trim(Input::get('To.Name'))       : '';
        $this->to_phone             = Input::has('To.Phone')        ? trim(Input::get('To.Phone'))      : '';
        $this->to_address           = Input::has('To.Address')      ? trim(Input::get('To.Address'))    : '';
        $this->to_email             = Input::has('To.Email')        ? trim(Input::get('To.Email'))      : '';

        $this->to_buyer_id          = Input::has('To.BuyerId')      ? (int)Input::get('To.BuyerId')      : 0;

        $this->service              = Input::has('Config.Service')          ? (int)Input::get('Config.Service')     : 2;
        $this->cod                  = Input::has('Config.CoD')              ? (int)Input::get('Config.CoD')         : 0;
        $this->protected            = Input::has('Config.Protected')        ? (int)Input::get('Config.Protected')   : 2;
        $this->payment              = Input::has('Config.Payment')          ? (int)Input::get('Config.Payment')     : 0;
        $this->checking             = Input::has('Config.Checking')         ? (int)Input::get('Config.Checking')    : 2;
        $this->fragile              = Input::has('Config.Fragile')          ? (int)Input::get('Config.Fragile')     : 2;
        $this->payment_cod          = Input::has('Config.PaymentCod')       ? (int)Input::get('Config.PaymentCod')  : 0;
        $this->auto_accept          = Input::has('Config.AutoAccept')       ? (int)Input::get('Config.AutoAccept')  : 0;


        //ThinhNV 
        $this->coupon_code          = Input::has('Config.CouponCode')       ? Input::get('Config.CouponCode')  : "";


        $this->order_code           = Input::has('Order.Code')              ? trim(Input::get('Order.Code'))            : '';
        $this->weight               = Input::has('Order.Weight')            ? (int)Input::get('Order.Weight')           : 350;
        $this->boxsize              = Input::has('Order.BoxSize')           ? trim(Input::get('Order.BoxSize'))         : '';
        $this->amount               = Input::has('Order.Amount')            ? (int)Input::get('Order.Amount')           : 0;
        $this->product_name         = Input::has('Order.ProductName')       ? trim(Input::get('Order.ProductName'))     : '';
        $this->quantity             = Input::has('Order.Quantity')          ? (int)Input::get('Order.Quantity')         : 0;
        $this->description          = Input::has('Order.Description')       ? trim(Input::get('Order.Description'))     : '';
        $this->discount             = Input::has('Order.Discount')          ? (int)Input::get('Order.Discount')         : 0;
        $this->money_collect        = Input::has('Order.Collect')           ? (int)Input::get('Order.Collect')          : null;
        $this->exchange_id          = Input::has('Order.Exchange')          ? (int)Input::get('Order.Exchange')         : 0;
        $this->type_exchange        = Input::has('Order.TypeExchange')      ? (int)Input::get('Order.TypeExchange')     : 0;

        $this->item_id             = Input::has('Order.ItemId')            ? (int)Input::get('Order.ItemId')           : 0;

        $this->check_app            = Input::has('App')                     ? Input::get('App')                         : false;

        if($this->weight == 0){
            $this->weight   = 350;
        }

        //$itemss = Input::get('Items');
        if($this->domain == "Donghoredep.com" && $this->MerchantKey == "4cecfe9c8082d909c1094858317beee1"){
            $this->domain = 'boxme.vn';
        }
	//Check phone
        if(!empty($this->to_phone) && !preg_match('/^0/', $this->to_phone)){
            if(!preg_match('/^84/', $this->to_phone)){
                $this->to_phone = '0'.$this->to_phone;
            }
        }

        if(!empty($this->boxsize)){
            $str            = explode('x',$this->boxsize);
            $this->weight   = ceil(($str[0] * $str[1] * $str[2]) / ( Input::get('Config.Service') == 1 ? 3 : 6 ));
        }

        if(!empty($this->post_code)){
            if(!$this->__getPostOffice()){
                $this->error    = true;
                return;
            }
        }

        // Khi ko truyền kho
        if(empty($this->stock) && empty($this->from_city)){
            if(!$this->_getMerchantInfo()){
                $this->error    = true;
                return;
            }

            if(!$this->_SuggestInventory()){
                $this->error    = true;
                return;
            }
        }

        if(!empty($this->stock)){
            $SellerInventoryInfo = $this->_getInventoryInfo();
            
            if((empty($this->from_city) || empty($this->from_district)) && !$SellerInventoryInfo){
                $this->error    = true;
                return;
            }
            
        }

        $this->_validation_sku();
        if($this->error){
            return ;
        }

        

        // Lấy thông tin City or province nếu chưa có
        if(!$this->CheckLocation()){
            $this->error    = true;
            return;
        }

        //get List courier
        $this->list_courier = $this->getCourier();
        if(empty($this->list_courier)){
            $this->code     = 'LIST_COURIER_EMPTY';
            $this->message  = 'Lỗi lấy dữ liệu hãng vận chuyển';
            $this->error    = true;
            return;
        }

        //get vas
        if($this->cod == 1 || $this->money_collect > 0 || (!isset($this->money_collect) && $this->payment == 2)){
            $this->list_vas[]   = 'cod';
        }

        if($this->protected == 1){
            $this->list_vas[]   = 'protected';
        }

        // Đơn đổi trả tự động duyệt
        if($this->exchange_id > 0){
            $this->auto_accept  = 1;
        }
    }

	public function getIndex()
	{
        return Response::json('1', 200);
	}

    private function _validation_sku(){
        $hasItem = Input::has('Items');

        if($hasItem && !empty($this->boxme_warehouse_code)) {
            $Items = Input::get('Items'); 
            $ListSKU = [];
            foreach($Items as $value){
                if(isset($value['BSIN'])){
                    $ListSKU[] = $value['BSIN'];
                }
                 
            }
            if(empty($ListSKU)){
                $this->message  = 'Mã SKU không tồn tại trong kho, vui lòng kiểm tra lại.';
                $this->error    = true;
                return false;
            }
            $SKUData = \warehousemodel\StatisticReportProductModel::whereIn('sku', $ListSKU)->where('warehouse', $this->boxme_warehouse_code)->lists('sku');
            if(count($ListSKU) !== count($SKUData)){
                $this->message  = 'Mã SKU không tồn tại trong kho, vui lòng kiểm tra lại.';
                $this->error    = true;

            }
        }
    }

    private function _validation($create = false){
        $Data       = [
            'user_id'           => $this->user_id,
            'courier'           => $this->courier,
            'domain'            => $this->domain,

            'from_city'         => $this->from_city,
            'from_district'     => $this->from_district,
            'from_ward'         => $this->from_ward,
            'from_address'      => $this->from_address,
            'stock'             => $this->stock,
            'from_name'         => $this->from_name,
            'from_phone'        => $this->from_phone,
            'inventory'         => $this->inventory,

            'to_city'           => $this->to_city,
            'to_district'       => $this->to_district,
            'to_ward'           => $this->to_ward,
            'to_address'        => $this->to_address,
            'to_name'           => $this->to_name,
            'to_phone'          => $this->to_phone,

            'amount'            => $this->amount,
            'weight'            => $this->weight,
            'discount'          => $this->discount,
            'product_name'      => $this->product_name,
            'quantity'          => $this->quantity,

            'domain'            => $this->domain,
            'service'           => $this->service,
            'cod'               => $this->cod,
            'protected'         => $this->protected,
            'payment'           => $this->payment,
            'checking'          => $this->checking,
            'fragile'           => $this->fragile

        ];

        if(isset($this->money_collect)){
            $Data['collect']    =  $this->money_collect;
        }

        $dataInput = array(
            'from_city'         => 'required|numeric|min:1',
            'from_district'     => 'required|numeric|min:1',
            'from_ward'         => 'required|numeric|min:0',

            'to_city'           => 'required|numeric|min:1',
            'to_district'       => 'required|numeric|min:1',
            'from_ward'         => 'required|numeric|min:0',

            'amount'            => 'required|numeric|min:1',
            'weight'            => 'required|numeric|min:1',

            'service'           => 'required|numeric|in:1,2,5,6',
            'cod'               => 'required|numeric|in:1,2', // 1: yes | 2: no
            'protected'         => 'required|numeric|in:1,2',
            'payment'           => 'required|numeric|in:1,2',
            'checking'          => 'required|numeric|in:1,2',
            'fragile'           => 'required|numeric|in:1,2',
        );

        if($create){
            $dataInput += array(
                'courier'               => $this->type != 'excel' ?  'required|numeric|min:1' : '', // tạo = excel không truyền hvc
                'discount'              => 'sometimes|required|numeric|min:0',
                'collect'               => 'sometimes|required|numeric|min:0',
                'user_id'               => 'required|numeric|min:1',

                'product_name'          => 'required',
                'quantity'              => 'required|numeric|min:1',

                'from_address'          => 'required',
                'from_name'             => 'required',
                'from_phone'            => 'required',

                'to_name'               => 'required',
                'to_phone'              => 'required',
                'to_address'            => 'required',
            );
        }

        $message = [
            'from_phone.required'    =>  'Thiếu thông tin số điện thoại người gửi, vui lòng cập nhật !'
        ];

        $this->validation = Validator::make($Data, $dataInput, $message);
    }


    private function _checkCoupon(){
        $CouponModel    = new \sellermodel\CouponModel;
        $this->coupon_info    = $CouponModel::where('code',$this->coupon_code)->where('time_expired','>=',$this->time())->where('active',1)->first();
        if(empty($this->coupon_info)){
            $this->error           = true;
            $this->code            = "COUPON_NOT_EXISTS";
            $this->message         = "Mã khuyến mãi không tồn tại !";
            return false;
        }

        if(Input::get('App') == true){
            if ($this->coupon_info->isOnlyUseForApp()) {
                $this->error        = true;
                $this->code         = "COUPON_ONLY_INAPP";
                $this->message      = "Mã khuyến mãi này chỉ được áp dụng với những đơn hàng tạo trên ứng dụng di dộng Shipchung";
                return false;
            }
        }

        if($this->coupon_info->isUsageLimit()){
            $this->error     = true;
            $this->code      = "COUPON_LIMITED";
            $this->message   = "Mã khuyến mãi đã được sử dụng đến giới hạn";
            return false;
        }

        if($this->coupon_info->AssginByUserId()){
            $CouponMembersModel = new \sellermodel\CouponMembersModel;
            $CheckMember        = $CouponMembersModel::where('coupon_id',$this->coupon_info->id)
                ->where('user_id', $this->user_id)
                ->count();

            if($CheckMember == 0){
                $this->error     = true;
                $this->code      = "COUPON_NOT_EXISTS";
                $this->message   = "Bạn không được phép sử dụng mã khuyến mãi này, xin cảm ơn !". $this->coupon_info->id.' - '.$this->user_id;
                return false;
            }
        }

        return true;
    }

    public function postCalculate($json = true){
        $this->json     = $json;
        // Check  construct
        if($this->error){
            return $this->ResponseData(true);
        }

        // Check và báo invalid
        $this->_validation(false);
        if($this->validation->fails()) {
            $this->code         = 'INVALID';
            $this->message      = $this->validation->messages();
            return $this->ResponseData(true);
        }

        $this->_getMerchantInfo();

        if(!empty($this->coupon_code)){
            $this->_checkCoupon();
            if($this->error){
                return $this->ResponseData(true);
            }
        }


        $courier = $this->SuggestCourier();
        if(!$courier){
            return $this->ResponseData(true);
        }

        if(!empty($this->token)){ // Boxme     điểm lấy là nội thành
            $Domain  = Config::get('config_api.domain.boxme');
            if(!empty($Domain) && ($this->token == $Domain['caculate'])){
                if(preg_match("/^BX/i", $this->order_code)){
                    $this->to_location    = 1;
                }else{
                    $this->from_location    = 1;
                }
            }
        }elseif($this->domain == 'boxme.vn' && $this->type == 'change'){
            if(preg_match("/^BX/i", $this->order_code)){
                $this->to_location    = 1;
            }else{
                $this->from_location    = 1;
            }
        }

        $calculate = $this->_calculate();
        if(!$calculate){
            return $this->ResponseData(true);
        }

        // get courier cấu hình
        //$this->_ConfigCarrier();
        foreach($courier as $val){
            $who = in_array($val['courier_id'],$this->uCourier) ? 'me' : 'system';
            $this->courier              = (int)$val['courier_id'];
            $val['money_pickup']        = $this->FeePickup();
            $this->output['courier'][$who][] = $val;
        }

        return $this->ResponseData(false);
    }

    public function postCreate($json = true){
        $this->json     = $json;
        // Insert log
        $LMongo         = new LMongo;
        // Log đầu vào
        try{
            $this->id_log   = $LMongo::collection('log_create_lading')->insert(array( 'input' => Input::all(),'time_create' => $this->time(),'date_create' => date('d/m/Y H:i:s')));
        }catch (Exception $e){
            $this->code     = 'INSERT_LOG_ERROR';
            $this->message  = 'Tạo vận đơn lỗi, hãy thử lại!';
            $this->data     = $e->getMessage(); 
            return $this->ResponseCreate(true);
        }

        // Check  construct
        if($this->error){
            return $this->ResponseCreate(true);
        }

        // Lấy thông tin khách hàng
        if(!$this->_getMerchantInfo()){
            return $this->ResponseCreate(true);
        }

        if(!empty($this->coupon_code)){
            $this->_checkCoupon();
            if($this->error){
                return $this->ResponseData(true);
            }
        }



        // Tạo Kho CDT
        if(!empty($this->inventory) && !$this->_getInventory()){
            return $this->ResponseCreate(true);
        }else{
            //Check Haravan
            if($this->domain == 'haravan.com' && empty($this->inventory)){
                $this->inventory    = md5('haravan-'.$this->from_city.'-'.$this->from_district.'-'.$this->from_phone.'-'.$this->from_address.'-'.$this->user_id);
                if(!$this->_getInventory()){
                    return $this->ResponseCreate(true);
                }
            }
        }

        // Check và báo invalid
        $this->_validation(true);
        if($this->validation->fails()) {
            $this->code         = 'INVALID';
            $this->message      = $this->validation->messages();
            return $this->ResponseCreate(true);
        }

        /* check phường xã nhận
        if(empty($this->from_ward))
        {
            $this->code         = 'INVALID';
            $this->message      = 'Bạn cần chọn phường xã cho kho hàng trước khi tạo đơn !';
            return $this->ResponseCreate(true);
        }*/

        //Get Courier
        $courier = $this->SuggestCourier();
        if(!$courier){
            return $this->ResponseCreate(true);
        }

        if(!empty($this->token)){ // Boxme     điểm lấy là nội thành
            $Domain  = Config::get('config_api.domain.boxme');
            if(!empty($Domain) && ($this->token == $Domain['create'])){
                if(preg_match("/^BX/i", $this->order_code)){
                    $this->to_location    = 1;
                }else{
                    $this->from_location    = 1;
                }
                $this->auto_accept      = 1;
            }
        }elseif($this->domain == 'boxme.vn' && $this->type == 'change'){
            if(preg_match("/^BX/i", $this->order_code)){
                $this->to_location    = 1;
            }else{
                $this->from_location    = 1;
            }
        }

        $this->courier = (int)$courier[0]['courier_id'];

        if(empty($this->courier)){ // trường hợp ko truyền hvc excel
            

            $finded_special_courier = false;

            foreach($courier as $val){
                if($val['courier_id'] == 8 && $this->domain == 'boxme.vn' && in_array($this->boxme_warehouse_code, ['VTPHN02'])){
                    $this->courier  = (int)$val['courier_id'];
                    $this->LCourier = $val;
                    $finded_special_courier = true;
                }
            }

            if(!$finded_special_courier){
                $this->courier  = (int)$courier[0]['courier_id'];
                $this->LCourier = $courier[0];
            }
            

        }else{
            foreach($courier as $val) {
                if ($val['courier_id'] == $this->courier) {
                    $this->courier = (int)$val['courier_id'];
                    $this->LCourier = $val;
                }
            }
        }

        $this->LCourier['money_pickup']        = $this->FeePickup();

        //Calculate Fee
        $calculate = $this->_calculate();
        if(!$calculate){
            return $this->ResponseCreate(true);
        }



        //Tạo = excel
        if(!in_array($this->type, ['shipchung','change'])){
            if(!empty($this->discount)){
                $this->output['collect']                = ($this->output['collect'] - $this->discount) > 0 ? ($this->output['collect'] - $this->discount) : 0;

                if($this->output['collect'] == 0){
                    $this->output['vas']['cod']         = 0;
                    $this->output['seller']['pcod']     = 0;
                    $this->output['discount']['pcod']   = 0;
                }
                $this->output['seller']['discount']     = $this->discount + ($this->output['pvc'] - $this->output['discount']['pvc'] - $this->output['seller']['pvc']) + ($this->output['vas']['cod'] - $this->output['discount']['pcod'] - $this->output['seller']['pcod']);
            }
        }else{
            $this->output['seller']['discount'] = $this->discount;
        }

        $ErrorAccept = [];
        if($this->auto_accept == 1){
            $CheckBalance   = $this->CheckBalance();
            if($CheckBalance['error']){
                $this->code         = $CheckBalance['message'];
                $this->message      = Lang::get('response.BALANCE_UNDEFINED');
                $this->_update_log([
                    'error' => 'fail','error_message' => Lang::get('response.CREATE_ORDER_FAIL'), 'data' => $this->code
                ]);
                return $this->ResponseCreate(true);
            }else{
                $TotalFee = ($this->output['pvc'] + (int)$this->LCourier['money_pickup']) + $this->output['vas']['cod'] + $this->output['vas']['protected'] - $this->output['discount']['pvc'] - $this->output['discount']['pcod'];
                if($this->output['collect'] > 0){
                    // CoD
                    if(($CheckBalance['money_total'] - $TotalFee) < -200000){
                        $this->code         = 'NOT_ENOUGH_MONEY';
                        $this->message      = Lang::get('response.NOT_ENOUGH_MONEY');
                        $this->data         = [
                            'money_total'   => $CheckBalance['money_total'] + 200000,
                            'fee'           => $TotalFee
                        ];
                        $this->_update_log([
                            'error' => 'fail','error_message' => 'Tạo vận đơn thất bại', 'data' => $this->code
                        ]);
                        return $this->ResponseCreate(true);
                    }
                }else{
                    if(($CheckBalance['money_total'] - $TotalFee) < 0){
                        $this->code         = 'NOT_ENOUGH_MONEY';
                        $this->message      = Lang::get('response.NOT_ENOUGH_MONEY');
                        $this->data         = [
                            'money_total'   => $CheckBalance['money_total'],
                            'fee'           => $TotalFee
                        ];
                        $this->_update_log([
                            'error' => 'fail','error_message' => Lang::get('response.CREATE_ORDER_FAIL'), 'data' => $this->code
                        ]);
                        return $this->ResponseCreate(true);
                    }
                }
            }
        }

        if($this->domain == 'boxme.vn' && !empty($this->order_code)) {
            $DB = DB::connection('orderdb');
            try {
                $DB->table('code')->insert(
                    array('domain' => $this->domain, 'order_code' => $this->order_code)
                );
            }catch (Exception $e){
                $Order = ordermodel\OrdersModel::where('time_accept','>=',$this->time() - 86400*30)->where('order_code', $this->order_code)->first(['tracking_code']);
                $this->code         = 'DUPLICATE_ORDER_CODE';
                $this->message      = 'Mã '.$this->order_code.' đã tồn tại!';
                $this->data['TrackingCode']         = isset($Order->tracking_code) ? $Order->tracking_code : '';
                return $this->ResponseCreate(true);
            }
        }

        // Create
        $Create = $this->_create();

        if(!$Create){
            $this->_update_log([
                'error' => 'fail','error_message' => Lang::get('response.CREATE_ORDER_FAIL'), 'data' => $this->code
            ]);
            return $this->ResponseCreate(true);
        }

        $checkExist = omsmodel\CustomerAdminModel::firstOrNew(['user_id' => $this->user_id]);
        try{
            if($checkExist->first_order_time    == 0){
                $checkExist->first_order_time   = $this->time();
            }
            $checkExist->last_order_time    = $this->time();
            $checkExist->save();
        }catch (Exception $e){

        }

        $this->data  = [
                'TrackingCode'  => $this->__TrackingCode,
                'CourierId'     => $this->courier,
                'MoneyCollect'  => $this->output['collect'],
                'ShowFee'       => [
                    'pvc' => $this->output['pvc'],
                    'cod' => $this->output['vas']['cod'],
                    'pbh' => $this->output['vas']['protected']
                ],
                'Discount'      => $this->output['seller']['discount']
        ];

        if($this->domain == 'beta.chodientu.vn' || $this->domain == 'chodientu.vn'){
            $this->data['ShowFee']['money_pickup']      = isset($this->LCourier['money_pickup']) ? $this->LCourier['money_pickup'] : 0;
            $this->data['ShowFee']['discount_pvc']      = $this->output['discount']['pvc'];
            $this->data['ShowFee']['discount_pcod']     = $this->output['discount']['pcod'];
        }

        $this->_update_log([
            'error'         => 'successs',
            'trackingcode'  => $this->__TrackingCode,
            'message'       => $this->message,
            'datalog'       => $this->__DataLog,
            'output'        => $this->data,
        ]);

        return $this->ResponseCreate(false);
    }

    /**
     * update log
     */
    private function _update_log($log_error){
        $LMongo         = new LMongo;
        $LMongo::collection('log_create_lading')->where('_id', new \MongoId($this->id_log))
            ->update($log_error);
        return;
    }

    /**
     *  Suggest Inventory
     */
    private function _SuggestInventory(){
        $Data        = [];
        $dbInventory = sellermodel\UserInventoryModel::where('user_id', $this->user_id)
                                                     ->where('active',1)
                                                     ->where('delete',0)
                                                     ->get()->toArray();

        if(empty($dbInventory)){
            $this->code     = 'STOCK_NOT_EXISTS';
            $this->message  = Lang::get('response.STOCK_NOT_EXISTS');
            return false;
        }

        $ListInvetory   = [
            'ward'      => [],
            'district'  => [],
            'city'      => []
        ];

        foreach($dbInventory as $val){
            if(($this->to_ward > 0) && $this->to_ward == $val['ward_id']){
                $ListInvetory['ward'][] = $val;
            }

            if(($this->to_district > 0) && $this->to_district == $val['province_id']){
                $ListInvetory['district'][] = $val;
            }

            if(($this->to_city > 0) && $this->to_city == $val['city_id']){
                $ListInvetory['city'][] = $val;
            }
        }

        // Nếu có kho cùng phường xã
        if(!empty($ListInvetory['ward'])){
            $Data = $ListInvetory['ward'][0];
        }elseif(!empty($ListInvetory['district'])){ // nếu có quận huyện
            $Data = $ListInvetory['district'][0];
        }elseif(!empty($ListInvetory['city'])){
            $Data = $ListInvetory['city'][0];
        }

        if(empty($Data)){
            $Data = $dbInventory[0];
        }

        $this->from_city        = (int)$Data['city_id'];
        $this->from_district    = (int)$Data['province_id'];
        $this->from_ward        = (int)$Data['ward_id'];
        $this->from_address     = trim($Data['address']);
        $this->from_name        = trim($Data['user_name']);
        $this->from_phone       = trim($Data['phone']);
        $this->stock            = (int)$Data['id'];
        return true;
    }


    /**
     * get config courier
     */
    private function _ConfigCarrier(){
        if($this->user_id > 0){

            if($this->to_city == $this->from_city && $this->from_city == 18){
                $where = array('support_hn',1);
            }
            elseif($this->to_city == $this->from_city && $this->from_city == 52){
                $where = array('support_hcm',1);
            }
            else{
                $where = array('support_other',1);
            }

            $dbConfig = sellermodel\CourierModel::where('active',1)
                ->where('user_id',$this->user_id)
                ->where($where[0],$where[1])
                ->get(['courier_id'])->toArray();

            if(!empty($dbConfig)){
                foreach($dbConfig as $value){
                    $this->uCourier[] = $value['courier_id'];
                }
            }
            return $this->uCourier;
        }

        return $this->uCourier  = array();
    }

    /**
     * get Post Office
     */
    private function __getPostOffice(){
        $PostOffice = CourierPostOfficeModel::where('id',$this->post_code)->first();
        if(!isset($PostOffice->id)){
            $this->code     = 'POST_OFFICE_NOT_EXISTS';
            $this->message  = Lang::get('response.POST_OFFICE_NOT_EXISTS');
            return false;
        }

        $this->from_city        = $PostOffice->city_id;
        $this->from_district    = $PostOffice->district_id;
        $this->from_ward        = $PostOffice->ward_id;
        $this->from_address     = $PostOffice->address;
        $this->courier          = $PostOffice->courier_id;

        $UserInfo   = $this->UserInfo();
        if(!empty($UserInfo)){
            $this->from_name        = $UserInfo['fullname'];
            $this->from_phone       = $UserInfo['phone'];
        }

        return true;
    }

    /**
     * Get User
     */
    private function _getMerchantInfo(){
        $UserInfo   = $this->UserInfo();
        if (!empty($UserInfo)) // Có session
        {
            $this->user_id   = (int)$UserInfo['id'];
            $this->child_id  = (isset($UserInfo['child_id']) && $UserInfo['child_id'] > 0) ? (int)$UserInfo['child_id'] : 0;
        }
        elseif(!empty($this->MerchantKey)){
            $dbKey = ApiKeyModel::where('key',$this->MerchantKey)->first(['user_id','auto']);
            if(!isset($dbKey->user_id)){
                $this->code     = 'MERCHANTKEY_NOT_EXISTS';
                $this->message  = Lang::get('response.MERCHANTKEY_NOT_EXISTS');
                return false;
            }
            $this->user_id          = (int)$dbKey->user_id;
            if($this->auto_accept == 0){
                $this->auto_accept      = (int)$dbKey->auto;
            }
        }elseif($this->type == 'excel' && Input::has('UserId')){
            $this->user_id          = (int)Input::get('UserId');
        }else {
            $this->code     = 'PERMISSION_DENIED';
            $this->message  = Lang::get('response.PERMISSION_DENIED');
            return false;
        }

        return true;
    }

    /**
    /**
    /**
     * get Inventory
     */
    private function _getInventoryInfo(){
        $UserInfo       = $this->UserInfo();
        $Phone          = '';
        if(!empty($UserInfo)){
            $Phone  = $UserInfo['phone'];
        }

        $dbInventory    = sellermodel\UserInventoryModel::find($this->stock);
        if(!isset($dbInventory['id'])){
            $this->error    = true; // Prevent create order
            $this->code     = 'STOCK_NOT_EXISTS';
            $this->message  = Lang::get('response.STOCK_NOT_EXISTS');
            return false;
        }

        $this->from_name            = trim($dbInventory['user_name']);
        $this->from_phone           = !empty($dbInventory['phone']) ? trim($dbInventory['phone']) : $Phone;
        $this->from_address         = trim($dbInventory['address']);
        $this->from_city            = (int)$dbInventory['city_id'];
        $this->from_district        = (int)$dbInventory['province_id'];
        $this->from_ward            = (int)$dbInventory['ward_id'];
        $this->boxme_warehouse_code = $dbInventory['warehouse_code'];
        return true;
    }

    /**
     * get Inventory CDT
     */
    /**
     *  Create Inventory
     */
    private function _getInventory(){ // lấy kho từ chợ điện tử
        //Check exists
        $Change     = false;
        $Model      = new sellermodel\UserInventoryModel;
        $Inventory  = $Model::firstOrCreate(['sys_name'    => $this->domain, 'sys_number' => $this->inventory]);
        if(empty($Inventory->name)){
            $Change             = true;
            $Inventory->name    = 'Kho hàng '.$this->domain;
        }

        if(empty($Inventory->user_id)){
            $Change             = true;
            $Inventory->user_id    = $this->user_id;
        }

        if(empty($Inventory->user_name)){
            $Change                 = true;
            $Inventory->user_name   = $this->from_name;
        }

        if(empty($Inventory->phone)){
            $Change                 = true;
            $Inventory->phone   = $this->from_phone;
        }

        if(empty($Inventory->time_create)){ // Chưa tồn tại
            $Change                 = true;

            if(empty($Inventory->city_id)){
                $Change                 = true;
                $Inventory->city_id   = $this->from_city;
            }

            if(empty($Inventory->province_id)){
                $Change                 = true;
                $Inventory->province_id   = $this->from_district;
            }

            if(empty($Inventory->ward_id)){
                $Change                 = true;
                $Inventory->ward_id   = $this->from_ward;
            }

            if(empty($Inventory->address)){
                $Change                 = true;
                $Inventory->address   = $this->from_address;
            }

            $Inventory->time_create   = $this->time();
        }else{
            $this->from_city        = (int)$Inventory->city_id;
            $this->from_district    = (int)$Inventory->province_id;
            $this->from_ward        = (int)$Inventory->ward_id;
            $this->from_address     = trim($Inventory->address);
        }

        if($Change){
            try{
                $Inventory->save();
            }catch (Exception $e){
                $this->code     = 'INSERT_INVENTORY_FAIL';
                $this->message  = Lang::get('response.INSERT_INVENTORY_FAIL');
                return false;
            }
        }

        $this->stock    = (int)$Inventory->id;
        return true;
    }

    /**
     * Check District - City
     */
    private function CheckLocation(){
        // get province
        $ListWard       = [];
        $ListDistrict   = [];
        if(!empty($this->from_ward)){
            $ListWard[] = $this->from_ward;
        }
        if(!empty($this->to_ward)){
            $ListWard[] = $this->to_ward;
        }

        if(!empty($ListWard)){
            $Ward   = WardModel::whereIn('id',$ListWard)->get()->toArray();
            if(!empty($Ward)){
                foreach($Ward as $val){
                    if($val['id']   == $this->from_ward){
                        if(empty($this->from_district)){ // nếu chưa truyền district
                            $this->from_district    = (int)$val['district_id'];
                        }else{// nếu đã truyền thì check
                            if($this->from_district != (int)$val['district_id']){
                                $this->code     = 'FROM_WARD_ERROR';
                                $this->message  = Lang::get('response.FROM_WARD_ERROR');
                                return false;
                            }
                        }

                    }
                    if($val['id']   == $this->to_ward){
                        $this->to_district      = (int)$val['district_id'];
                        if(empty($this->to_district)){ // nếu chưa truyền district
                            $this->to_district    = (int)$val['district_id'];
                        }else{// nếu đã truyền thì check
                            if($this->to_district != (int)$val['district_id']){
                                $this->code     = 'TO_WARD_ERROR';
                                $this->message  = Lang::get('response.FROM_WARD_ERROR');
                                return false;
                            }
                        }
                    }
                }
            }else{
                $this->code     = 'WARDID_NOT_EXISTS';
                $this->message  = Lang::get('response.FROM_WARD_ERROR');
                return false;
            }
        }

        if(!empty($this->from_district)){
            $ListDistrict[] = $this->from_district;
        }
        if(!empty($this->to_district)){
            $ListDistrict[] = $this->to_district;
        }
        if(!empty($ListDistrict)){
            $District   = DistrictModel::whereIn('id',$ListDistrict)->get()->toArray();
            if(!empty($District)){
                foreach($District as $val){
                    if($val['id']   == $this->from_district){
                        if(empty($this->from_city)){// nếu chưa truyền city
                            $this->from_city    = (int)$val['city_id'];
                        }else{
                            if($this->from_city != (int)$val['city_id']){
                                $this->code     = 'FROM_DISTRICT_ERROR';
                                $this->message  = Lang::get('response.FROM_DISTRICT_ERROR');
                                return false;
                            }
                        }
                    }
                    if($val['id']   == $this->to_district){
                        if(empty($this->to_city)){// nếu chưa truyền city
                            $this->to_city    = (int)$val['city_id'];
                        }else{
                            if($this->to_city != (int)$val['city_id']){
                                $this->code     = 'TO_DISTRICT_ERROR';
                                $this->message  = Lang::get('response.FROM_DISTRICT_ERROR');
                                return false;
                            }
                        }
                    }
                }
            }else{
                $this->code     = 'DISTRICTID_NOT_EXISTS';
                $this->message  = Lang::get('response.DISTRICTID_NOT_EXISTS');
                return false;
            }
        }

        return true;
    }

    //get Type Config Courier
    private function TypeConfigCourier(){
        $ConfigModel        = new seller\BaseCtrl;
        $TypeConfig         = ($this->from_city == $this->to_city) ? 1 : 2;
        $City               = $ConfigId = 0;
        if(in_array($this->from_city, [18,52])){
            $City    = $this->from_city;
        }

        $ConfigTypeCourier  = $ConfigModel->getConfigTypeCourier(false);
        foreach($ConfigTypeCourier as $val){
            if($val['type'] == $TypeConfig){
                if($TypeConfig == 2 || ($TypeConfig == 1 && $val['city_id'] == $City)){
                    $ConfigId   = (int)$val['id'];
                }
            }
        }

        return $ConfigId;
    }

    /**
     * SuggestCourier
     */
    public function SuggestCourier(){
        $arrCourier = $estimate_pickup = $OuputCourier = $CourierRefuse = $arrDeCourier = [];
        //get location delivery
        $AreaQuery        = AreaLocationModel::where('province_id',$this->to_district)
            ->where('city_id',$this->to_city)
            ->where('active','=',1)
            ->remember(10)
            ->first(['province_id', 'city_id', 'area_id', 'location_id']);
        if(!isset($AreaQuery->location_id)){
            $this->message  = Lang::get('response.UNSUPPORT_DELIVERY');
            $this->code     = 'UNSUPPORT_DELIVERY';
            return false;
        }
        $this->to_location  = (int)$AreaQuery->location_id;

        // location Pickup
        $dbLocationPick = AreaLocationModel::where('province_id',$this->from_district)->where('active','=',1)->remember(10)->first(['id','location_id']);
        if(!isset($dbLocationPick->id)){
            $this->message  = Lang::get('response.UNSUPPORT_PICKUP');
            $this->code     = 'UNSUPPORT_PICKUP';
            return false;
        }
        $this->from_location    = (int)$dbLocationPick->location_id;

            // Check theo Area
        $PickupArea = PromisePickupModel
            ::whereIn('province_id',[0,$this->from_city])
            ->whereIn('district_id',array(0,$this->from_district))
            ->where('service_id',$this->service)
            ->where('courier_id','<>',3);

        if($this->domain != 'boxme.vn'){
            $PickupArea = $PickupArea->where('amount_start','<=',$this->amount)
                                     ->where('amount_end','>=',$this->amount)
                                     ->where('active',1);
        }

        if(!empty($this->courier)){
            $PickupArea = $PickupArea->where('courier_id',$this->courier);
        }

        $PickupArea = $PickupArea->orderBy('district_id','desc')->orderBy('province_id','desc')
                                    ->remember(10)->get(['id','courier_id','estimate_pickup','district_id'])->toArray();

        if(empty($PickupArea)){
            $this->code     = 'UNSUPPORTED';
            $this->message  = (!empty($this->courier) ? $this->list_courier[$this->courier] : 'Shipchung').' chưa hỗ trợ lấy hàng tại khu vực này, vui lòng liên hệ CSKH để được hỗ trợ.';
            return false;
        }

        //Check Refuse Pickup
        $arrRefusePickup    = [];
        if(!empty($this->from_ward)){
            //$arrRefusePickup   = CourierRefusePickupModel::where('ward_id', $this->from_ward)->where('active',1)->remember(30)->lists('courier_id');
            $arrRefusePickup   = CourierRefusePickupModel::where('ward_id', $this->from_ward)->where('active',1)->lists('courier_id');
            
        }

        foreach($PickupArea as $value){
            if(!in_array($value['courier_id'],$arrRefusePickup)){
                $arrCourier[]                           = (int)$value['courier_id'];
                $arrRefusePickup[]                      = (int)$value['courier_id'];
            }
        }


        //Get config courier
        $PriorityConfig     = [];
        $ListDropCourier    = [];

        if($this->user_id > 0 && ($this->domain != 'boxme.vn' || $this->user_id == 102556)){
            $ConfigCourier  = $this->TypeConfigCourier();

            if(empty($ConfigCourier)){
                $this->code     = 'CONFIG_COURIER_ERROR';
                $this->message  = Lang::get('response.CONFIG_COURIER_ERROR');
                return false;
            }

            $CourierConfig = sellermodel\CourierModel::where('user_id',$this->user_id)
                                ->where('config_type'  , $ConfigCourier)
                                ->get()->toArray();

            if(!empty($CourierConfig)){
                foreach($CourierConfig as $val){
                    if($val['amount_start'] > $this->amount || $val['amount_end'] < $this->amount || $val['active'] == 0){
                        $ListDropCourier[]                            = (int)$val['courier_id'];
                    }elseif($val['active'] == 1){
                        $PriorityConfig[(int)$val['courier_id']]        = (int)abs($val['priority'] - 4);
                    }
                }

                $arrCourier = array_diff($arrCourier, $ListDropCourier);
                if(empty($arrCourier)){
                    $this->code     = 'CONFIG_COURIER_EMPTY';
                    $this->message  = Lang::get('response.CONFIG_COURIER_EMPTY');
                    return false;
                }
            }
        }

        $CourierPromiseModel    = new systemmodel\CourierPromiseModelDev; // promise delivery
        $DeliveryPromise = $CourierPromiseModel
            ::where('from_district', $this->from_district)
            ->where('to_district', $this->to_district)
            ->whereIn('courier_id',$arrCourier)
            ->where('service_id',$this->service)
            ->where('active',1)->orderBy('estimate_delivery')
            ->orderBy('courier_id','ASC')
            ->remember(10)
            ->get()->toArray();

        $DeliveryDistrict   = [];
        if(!empty($DeliveryPromise)){
            foreach($DeliveryPromise as $val){
                $val['estimate_delivery'] = $val['courier_estimate_delivery'];
                $DeliveryDistrict[(int)$val['courier_id']]      = $val;
            }
        }

        $Estimate   = 1*24*3600;
        if($this->service == 1){
            $Estimate   = $Estimate*2;
        }

        if(in_array(8, $arrCourier) && $this->from_city != $this->to_city){
            $DeliveryDistrict[] = [
                'courier_id'          => 8,
                'estimate_delivery'   => $Estimate + ($this->to_location >= 3 ? (2*$Estimate) : 0),
                'priority'            => 4
            ];
        }

        if(in_array(1, $arrCourier)){
            if(in_array($this->service, [5,6]) || ($this->service == 1 && $this->from_city != $this->to_city && $this->to_district != 669)){
                $DeliveryDistrict[]   =
                    [
                        'courier_id'          => 1,
                        'estimate_delivery'   => $Estimate + ($this->to_location >= 3 ? (2*$Estimate) : 0),
                        'priority'            => 3
                    ];
            }
        }

        if(in_array(12, $arrCourier)){
            if(in_array($this->service, [6])){
                $DeliveryDistrict[]   =
                    [
                        'courier_id'          => 12,
                        'estimate_delivery'   => $Estimate + ($this->to_location >= 3 ? (2*$Estimate) : 0),
                        'priority'            => 3
                    ];
            }
        }

        if(empty($DeliveryDistrict)){
            $this->code     = 'UNSUPPORTED';
            $this->message  = (!empty($this->courier) ? $this->list_courier[$this->courier] : 'Shipchung').' chưa hỗ trợ giao hàng tới khu vực này, vui lòng liên hệ CSKH để được hỗ trợ.';
            return false;
        }


        // check support ward
        $CourierRefuse  = [];
        $dbRefuse = CourierRefuseModel::where('district_id',$this->to_district)->whereIn('courier_id',$arrCourier);
        if($this->to_ward > 0){
            $dbRefuse = $dbRefuse->where('ward_id',$this->to_ward);
        }

        // $dbRefuse   = $dbRefuse->remember(60)->get(['ward_id', 'courier_id'])->toArray();
        $dbRefuse   = $dbRefuse->get(['ward_id', 'courier_id'])->toArray();

        if(!empty($dbRefuse)){
            $ListWard       = [];
            $ListCourier    = [];

            foreach($dbRefuse as $val){
                if($this->to_ward == (int)$val['ward_id']){
                    $CourierRefuse[]    = (int)$val['courier_id'];
                }else{
                    $ListWard[]                             = (int)$val['ward_id'];
                    $ListCourier[(int)$val['ward_id']][]    =  (int)$val['courier_id'];
                }
            }
            
            if(empty($this->to_ward) && !empty($ListWard)){
                $ListWard   = WardModel::whereIn('id', $ListWard)->get(['ward_name','id'])->toArray();
                foreach($ListWard as $val){
                    $WardName = strtolower(trim(str_replace(['Thị Trấn','Xã', 'ấp', 'cụm', 'Ấp', 'Cụm','xã','thị trấn'],'',$val['ward_name'])));
                    if(preg_match('/'.$WardName.'/i', $this->to_address)){
                        $CourierRefuse = array_merge($CourierRefuse, $ListCourier[(int)$val['id']]);
                    }
                }
            }
        }

        foreach($DeliveryDistrict as $value){
            if(!in_array($value['courier_id'],$CourierRefuse)){
                $leatime_delivery   = ceil($value['estimate_delivery'] / 3600);
                $leatime_total      = $leatime_delivery;
                $CourierRefuse[]    = (int)$value['courier_id'];

                if($leatime_delivery > 24){
                    $leatime_str = ceil($leatime_delivery / 24)." ".Lang::get('response.DAY');
                }else {
                    $leatime_str = $leatime_delivery." ".Lang::get('response.HOUR');
                }

                if($this->check_app && ($leatime_total > 5*24)){
                    $leatime_total    = $leatime_total - 2*24;
                    $leatime_delivery = $leatime_total;
                }

                $courier_description = $this->getListCourier();

                $CourierInfo = [
                    'courier_id'            => $value['courier_id'],
                    'courier_name'          => $this->list_courier[(int)$value['courier_id']],
                    'courier_description'   => isset($courier_description[(int)$value['courier_id']]) ? $courier_description[(int)$value['courier_id']]['description'] : "",
                    'courier_logo'      => isset($courier_description[(int)$value['courier_id']]) ? $courier_description[(int)$value['courier_id']]['logo'] : "",
                    'money_pickup'      => 0,
                    'leatime_delivery'  => $leatime_delivery,
                    'leatime_courier'   => isset($value['courier_estimate_delivery']) ? ceil($value['courier_estimate_delivery'] / 3600) : $leatime_delivery,
                    'leatime_total'     => $leatime_total,
                    'leatime_ward'      => 0,
                    'leatime_str'       => $leatime_str,
                    'priority'          => (isset($PriorityConfig[(int)$value['courier_id']]) ? ($PriorityConfig[(int)$value['courier_id']]) : ((int)$value['priority']))
                ];
                
                $OuputCourier[$value['courier_id']] = $CourierInfo; 
            }
        }

        if(empty($OuputCourier)){
            $this->code     = 'UNSUPPORTED';
            $this->message  = Lang::get('response.UNSUPPORTED');
            return false;
        }
        $Return = [];
        $OuputCourier = array_values(array_sort($OuputCourier, function($value){
            return $value['priority'];
        }));

        if($this->domain == 'boxme.vn' && !empty($this->boxme_warehouse_code) && strlen(strstr($this->boxme_warehouse_code, 'VTP')) == 0){
            foreach($OuputCourier as $value){
                if($value['courier_id'] == 11){
                array_unshift($Return, $value); 
                }else {
                    $Return[] = $value; 
                }
            }
            return $Return;
        }
        return $OuputCourier;

    }

    /*
     * sinh mã
     */
    function _generateCode(){
        $md5 = md5(uniqid($this->user_id, true).microtime());

        $crc = crc32((string)$md5);
        if ($crc & 0x80000000) {
            $crc ^= 0xffffffff;
            $crc += 1;
        }

        $sc_code = abs($crc);

        if(strlen($sc_code) > 10 || strlen($sc_code) < 8)
            return $this->_generateCode();

        $prefix = 'SC5';

        return $this->__TrackingCode = $prefix.$sc_code;
    }

    /*
     *  Caculate
     */
    private function _calculate(){
        // Get Fee id
        $CourierDeliveryModel   = new CourierDeliveryModel;
        $Fee  = $CourierDeliveryModel::where('courier_id', $this->caculate_courier)
                ->where('service_id', $this->service)
                ->where('from_city',$this->from_city)
                ->where('to_city', $this->to_city);

        if($this->to_location == 1){
            if($this->caculate_courier == 9 && $this->from_location > 1){
                $Fee    = $Fee->where('location', 2);
            }else{
                $Fee    = $Fee->where('location', 1);
            }

        }else{
            $Fee    = $Fee->where('location', 2);
        }

        $Fee    = $Fee->remember(10)->first();

        if(!isset($Fee->id)){
            $this->message  = Lang::get('response.SYSTEM_UNSUPPORT');
            $this->code     = 'SYSTEM_UNSUPPORT';
            return false;
        }
        $this->fee_id   = (int)$Fee->fee_id;

        /*
         *  Select Fee
         */
        /*$CourierFee         = CourierFeeModel::where('id','=',$this->fee_id)
            ->where('active','=',1)
            ->first(array('vat'));*/

        $CourierFeeDetail   = CourierFeeDetailModel::where('fee_id','=',$this->fee_id)
            ->where('weight_start','<',$this->weight)
            ->where('weight_end','>=',$this->weight)
            ->remember(10)
            ->first(array('money','surcharge'));

        if(!isset($CourierFeeDetail->money)){
            $this->message  = Lang::get('response.FEE_NOT_EXISTS');
            $this->code     = 'FEE_NOT_EXISTS';
            return false;
        }

        $this->output['pvc'] = $CourierFeeDetail->money;

        if($this->to_location > 2){
            $this->output['pvc'] += $CourierFeeDetail->surcharge;
        }
                                                                                                                  
        /*   Nội Thành đi Ngoại thành
        if($this->to_city == $this->from_city && $this->to_location >=3 && in_array($this->from_city,array(18,52))){
            $this->output['pvc'] = $this->output['pvc'] * 1.2;
        }
        */

        /*
         * Select Vas
         */

        if(!empty($this->list_vas)){
            $Vas = $this->FeeVas();
            if(!$Vas){
                return false;
            }
            $this->output['vas']    = $Vas;
        }

        /*
         * Select Discount
         */
        $this->FeeDiscount();

        /*
         * Money Collect
        */

        $this->output['collect']                = $this->output['seller']['pvc'] + $this->output['seller']['pcod'];
        // Tạo trên Shipchung
        if(isset($this->money_collect)){// Nếu truyền tiền thu hộ
            $this->output['collect']    = $this->money_collect;

            if($this->output['collect'] == 0){ // nếu truyền tiền thu hộ = 0;
                $this->output['vas']['cod']         = 0;
                $this->output['seller']['pcod']     = 0;
                $this->output['discount']['pcod']   = 0;
            }
        }else{
            if($this->cod == 1){
                $this->output['collect'] += $this->amount;
            }
        }

        return true;
    }

    /*
     * Create
     */
    private function _sync_buyer_elasticsearch (){
        $Buyer = [
            'id'            => $this->insertBuyer->id,
            'city_id'       => $this->to_city,
            'province_id'   => $this->to_district,
            'ward_id'       => $this->to_ward,
            'address'       => $this->to_address,
            'seller_id'     => $this->user_id,
            'fullname'      => $this->to_name,
            'phone'         => $this->to_phone,
            'email'         => $this->to_email,
            'ward_name'     => ""
        ];

        $Cities     = $this->getCity();
        $Provincies = $this->getProvince([$this->to_district]);


        if (!empty($Cities[$this->to_city])) {
            $Buyer['city_name'] = $Cities[$this->to_city];
        }

        if (!empty($Provincies[$this->to_district])) {
            $Buyer['district_name'] = $Provincies[$this->to_district];
        }

        if (!empty($this->to_ward)) {
            $Wards      = $this->getWard([$this->to_ward]);

            if (!empty($Wards[$this->to_ward])) {
                $Buyer['ward_name'] = $Wards[$this->to_ward];
            }
        }

        $this->PushSyncElasticsearch('buyers_suggestion', 'buyers', 'created', (object)$Buyer);

    }

    private function _sync_item_elasticsearch ($item_id){
        $action = 'created';

        if(empty($this->item_id)){
            $SyncOrderItem = [
                'id'            => $item_id->id,
                'seller_id'     => $this->user_id,
                'product_name'  => $this->product_name,
                'quantity'      => $this->quantity,
                'product_code'  => $this->order_code,
                'price'         => $this->amount,
                'weight'        => $this->weight,
                'time_update'   => $this->time(),
            ];

        }else {
            $action = 'updated';
            
            $SyncOrderItem = [
                'id'            => $this->item_id,
                'time_update'   => $this->time()
            ];
        }

        
        
        $this->PushSyncElasticsearch('bxm_orders', 'order_item', 'created', (object)$SyncOrderItem);
    }



    private function _insertBuyer(){
        $this->insertBuyerAddress = OrderAddressModel::create([
            'seller_id'     => $this->user_id,
            'city_id'       => $this->to_city,
            'province_id'   => $this->to_district,
            'ward_id'       => $this->to_ward,
            'address'       => $this->to_address,
            'time_update'   => $this->time(),
        ]);

        $this->insertBuyer = OrderBuyerModel::create([
            'seller_id'     => $this->user_id,
            'fullname'      => $this->to_name,
            'phone'         => $this->to_phone,
            'email'         => $this->to_email,
            'address_id'    => $this->insertBuyerAddress->id,
            'time_create'   => $this->time()
        ]);

        if ($this->domain == 'seller.shipchung.vn' && empty($this->to_buyer_id)) {
            $this->_sync_buyer_elasticsearch();
        }

    }

    private function _insertItems(){
        if(Input::has('Items')){
            foreach(Input::get('Items') as $value){
                //items model
                $insert = OrderItemsModel::create([
                    'seller_id'     => (int)$this->user_id,
                    'name'          => $value['Name'],
                    'price'         => $value['Price'],
                    'weight'        => $value['Weight'],
                    'time_update'   => $this->time(),
                ]);
                //order items model
                OrderItemModel::create([
                    'order_id'      => $this->insertOrders->id,
                    'item_id'       => $insert->id,
                    'product_name'  => $value['Name'],
                    'quantity'      => (int)$value['Quantity'],
                    'bsin'          => isset($value['BSIN']) ? $value['BSIN'] : "",
                    'description'   => $this->description
                ]);
            }

            //return $insert->id;
        }else{
            //items model
            $insert = OrderItemsModel::create([
                'seller_id'     => $this->user_id,
                'name'          => $this->product_name,
                'price'         => $this->amount,
                'weight'        => $this->weight,
                'time_update'   => $this->time(),
            ]);
            //order items model

            $itemId = OrderItemModel::create([
                'order_id'      => $this->insertOrders->id,
                'item_id'       => $insert->id,
                'product_name'  => $this->product_name,
                'quantity'      => $this->quantity,
                'description'   => $this->description
            ]);

            if ($this->domain == 'seller.shipchung.vn') {
                $this->_sync_item_elasticsearch($itemId);
            }
        }

    }

    private function _insertOrders(){
        $status = 20;
        // Cấu hình tự động duyệt theo khách hàng
        

        $this->__DataLog['order'] = [
            'service_id'            => $this->service,
            'courier_id'            => $this->courier,
            'tracking_code'         => $this->__TrackingCode,
            'order_code'            => $this->order_code,
            'domain'                => $this->domain,
            //'exchange_id'           => $this->exchange_id,
            'post_office_id'        => $this->post_code,
            'courier_tracking_code' => '',
            //'child_id'              => $this->child_id,
            
            'createby'              => !empty($this->child_id) ? $this->child_id : $this->user_id,
            'from_user_id'          => $this->user_id,
            'from_address_id'       => $this->stock,
            'from_city_id'          => $this->from_city,
            'from_district_id'      => $this->from_district,
            'from_ward_id'          => $this->from_ward,
            'from_address'          => $this->from_address,

            'to_buyer_id'           => $this->insertBuyer->id,
            'to_name'               => $this->to_name,
            'to_phone'              => $this->to_phone,
            'to_email'              => $this->to_email,

            'to_address_id'         => $this->insertBuyerAddress->id,
            'to_district_id'        => $this->to_district,
            'to_city_id'            => $this->to_city,
            
            'product_name'          => $this->product_name,
            'total_weight'          => $this->weight,
            'total_quantity'        => $this->quantity,
            'total_amount'          => $this->amount,
            'status'                => $status,

            'checking'              => $this->checking,
            'fragile'               => $this->fragile,

            'domain'                => $this->domain,
            'warehouse'             => !empty($this->boxme_warehouse_code) ? $this->boxme_warehouse_code : "",

            'time_create'           => $this->time(),
            'time_update'           => $this->time(),
            'time_accept'           => isset($time_accept) ? $time_accept : 0,
            'estimate_delivery'     => (isset($this->LCourier['leatime_delivery']) ? (int)$this->LCourier['leatime_delivery']  : 0) + (isset($this->LCourier['leatime_ward'])     ? (int)$this->LCourier['leatime_ward']      : 0),
            'courier_estimate'      => $this->LCourier['leatime_courier']
        ];
        $this->insertOrders = OrderOrdersModel::create($this->__DataLog['order']);
    }

    private function _insertOrderDetail(){
        if(Input::has('Discount'))
        {
            $seller_discount = (int)Input::get('Discount');
        }
        elseif(Input::has('Config.Payment') && Input::get('Config.Payment') == 1){
            //$seller_discount = (int)$this->calculate['pvc'];
        }
        else{
            $seller_discount = 0;
        }

        //
        $FeeDetail =  [
            'order_id'      => $this->insertOrders->id,
            'sc_pvc'        => (int)$this->output['pvc'] + (int)$this->LCourier['money_pickup'],
            'sc_cod'        => (int)$this->output['vas']['cod'],
            'sc_pbh'        => (int)$this->output['vas']['protected'],
            'sc_pvk'        => 0,
            'sc_pch'        => 0,
            'seller_discount'   => $this->output['seller']['discount'],
            'seller_pvc'        => $this->output['seller']['pvc'],
            'seller_cod'        => $this->output['seller']['pcod'],
            'hvc_pvc'       => 0,
            'hvc_cod'       => 0,
            'hvc_pbh'       => 0,
            'hvc_pvk'       => 0,
            'hvc_pch'       => 0,
            'money_collect' => $this->output['collect'],
            'sc_discount_pvc'   => $this->output['discount']['pvc'],
            'sc_discount_cod'   => $this->output['discount']['pcod']

        ];
        
        if(!empty($this->coupon_info)){
            $FeeDetail = $this->_calculateCoupon($FeeDetail);
            $this->insertOrders->coupon_id = $this->coupon_info->id;
        }

        $this->__DataLog['detail'] = $FeeDetail;
        OrderDetailModel::create($this->__DataLog['detail']);
    }

    private function _isBoxmeOrder (){
        return $this->domain == 'boxme.vn' && !empty($this->boxme_warehouse_code);
    }

    private function _create(){
        $DB = DB::connection('orderdb');
        $this->_generateCode();
        try{
            $DB->table('order_code')->insert(
                array('order_code' => $this->__TrackingCode)
            );
        }catch (Exception $e){
            return $this->_create();
        }

        $DB->beginTransaction();
        try {
                $this->_insertBuyer();
                $this->_insertOrders();
                $this->_insertItems();
                $this->_insertOrderDetail();


                if($this->auto_accept == 1){
                    $status      = 21;
                    $time_accept = $this->time();

                    if($this->_isBoxmeOrder()){
                        $OrderItemModel = new \ordermodel\OrderItemModel();

                        $CheckBSIN   = $OrderItemModel->BMCheckBSINAvailableInStock($this->insertOrders->id, $this->boxme_warehouse_code);
                        
                        if(!$CheckBSIN['available']){
                           $status      = 120; // Thiếu hàng
                           $this->code  = "OUT_OF_STOCK";
 
                        }else {
                           foreach($CheckBSIN['available_bsin'] as $value){
                               \warehousemodel\StatisticReportProductModel::PlusInventoryWait($value['sku'], $value['quantity'], $this->boxme_warehouse_code, $this->insertOrders);
                           } 
                        }
                    }

                    $this->insertOrders->status      = $status;
                    $this->insertOrders->time_accept = $time_accept;
                    $this->insertOrders->save();

                    $DB->commit();
                    $this->PredisAcceptLading($this->__TrackingCode);
                }else {
                    $DB->commit();
                }
                //return $queries = $DB->getQueryLog();
                //var_dump($queries);die;
                return true;
        }
        catch(ValidationException $e)
        {
            $DB->rollback();
            $this->message  = 'ERROR';//$e->getMessage();
            $this->code     = 'FAIL';
            return false;
        }
        catch(\Exception $e)
        {
            $DB->rollback();
            $this->message  = $e->getMessage();
            $this->code     = 'FAIL';
            return false;
        }
        catch(PDOException $e)
        {
            $DB->rollback();

            $info   = $e->errorInfo;
            // Check if mysql error is for a duplicate key
            if (in_array($info[1], array(1062, 1022, 1558))) {
                return $this->_create();
            }

            $this->message  = $e->getMessage();
            $this->code     = 'FAIL';
            return false;
        }
        return true;
    }

    private function FeePickup(){
        if(!empty($this->post_code)){
            return 0;
        }
        if($this->domain == 'boxme.vn'){
            return 0;
        }

        $CourierPickupConfig = \systemmodel\CourierPickupConfigModel::where('parent_courier', $this->courier)
            ->where('service_id', $this->service)
            ->where('country_id', 237)
            ->whereIn('user_id', [0,$this->user_id])
            ->whereIn('city_id', [0, $this->from_city])
            ->whereIn('district_id', [0, $this->from_district])
            ->whereIn('ward_id', [0, $this->from_ward])
            ->where('active',1)
            ->remember(5)
            ->orderBy('user_id','DESC')
            ->orderBy('ward_id','DESC')
            ->orderBy('district_id','DESC')
            ->orderBy('city_id','DESC')->first();

        if(isset($CourierPickupConfig->id)){
            return $CourierPickupConfig->money_pickup;
        }

        return 0;
    }

    private function FeeDelivery(){
        $total_fee              = 0;
        $CourierDeliveryModel   = new CourierDeliveryModel;
        $Fee  = $CourierDeliveryModel::where('fee_id', $this->fee_id)
            ->where('courier_id', $this->courier)
            ->where('service_id', $this->service)
            ->where('from_city',$this->from_city)
            ->where('to_city', $this->to_city)
            ->remember(10)
            ->first();

        if(isset($Fee->id)){
            $total_fee += $total_fee*($Fee->vat) + $total_fee*($Fee->oil);
        }

        return $total_fee;
    }

    private function FeeVas(){
        $DbQueryVas         =
        $DbQueryVasPrice    =
        $DbQueryVasCollect  =
        $DbQueryVasWeight   =
        $DbVasPrice         =
        $VasIdPrice         =
        $VasIdCollect       =
        $VasIdWeight        =
        $arrVasCode         = [];
        $Fee                = [
            'cod'           => 0,
            'protected'     => 0
        ];


        if($this->caculate_courier == 7){
            $MoneyCollect   = 0;
            if($this->cod == 1){
                $MoneyCollect   += $this->amount;
            }

            if(isset($this->money_collect) && $this->money_collect > 0){ // thay dổi thông tin vận đơn - tính theo tiền thu hộ
                if($this->payment == 2){
                    $MoneyCollect += $this->output['pvc'];
                }


                if(($this->money_collect < $MoneyCollect) || ($this->money_collect > ($MoneyCollect + 200000))){
                    $MoneyCollect   = $this->money_collect;
                }

                /*if($this->type == 'change') { // sửa đơn
                    $MoneyCollect   = $this->money_collect;
                }else{
                }*/
            }else{ // Thay đổi thông tin - tính theo tiền hàng
                if($this->payment == 2){
                    $MoneyCollect += $this->output['pvc'];
                }
            }
        }else{
            $MoneyCollect   = $this->money_collect;
        }


        $CourierVas     = CourierVasModel::whereIn('code',$this->list_vas)
            ->where('active','=',1)
            ->get(array('id','code','vas_value_type'))->toArray();

        if(!empty($CourierVas)){
            foreach($CourierVas as $val){
                if($val['vas_value_type'] == 1){
                    if($val['code'] == 'cod'){
                        $VasIdCollect[] = $val['id'];
                    }else{
                        $VasIdPrice[]   = $val['id'];
                    }
                }elseif($val['vas_value_type'] == 2){
                    $VasIdWeight[]  = $val['id'];
                }

                $arrVasCode[$val['id']] = $val['code'];
            }
        }

        $CourierFeeVasModel = new CourierFeeVasModel;

        // Vas Price
        if(!empty($VasIdCollect))
        {
            $DbQueryVasCollect  = $CourierFeeVasModel::where('fee_id','=',$this->fee_id)
                ->whereIn('vas_id',$VasIdCollect)
                ->where('value_start','<',$MoneyCollect)
                ->where('value_end','>=',$MoneyCollect)
                ->where('location','=',$this->to_location)
                ->where('active','=',1)
                ->remember(10)
                ->get(array('id','vas_id','percent','money','money_add'))->toArray();
            if(empty($DbQueryVasCollect)){
                $this->message  = Lang::get('response.UNSUPPORT_COLLECT');
                $this->code     = 'UNSUPPORT_COLLECT';
                return false;
            }
        }

        // Vas Price
        if(!empty($VasIdPrice))
        {
            $DbQueryVasPrice  = $CourierFeeVasModel::where('fee_id','=',$this->fee_id)
                ->whereIn('vas_id',$VasIdPrice)
                ->where('value_start','<',$this->amount)
                ->where('value_end','>=',$this->amount)
                ->where('location','=',$this->to_location)
                ->where('active','=',1)
                ->remember(10)
                ->get(array('id','vas_id','percent','money','money_add'))->toArray();
            if(empty($DbQueryVasPrice)){
                $this->message  = Lang::get('response.UNSUPPORT_AMOUNT');
                $this->code     = 'UNSUPPORT_AMOUNT';
                return false;
            }
        }

        //Vas Weight
        if(!empty($VasIdWeight))
        {
            $DbQueryVasWeight  = $CourierFeeVasModel::where('fee_id','=',$this->fee_id)
                ->whereIn('vas_id',$VasIdWeight)
                ->where('value_start','<',$this->weight)
                ->where('value_end','>=',$this->weight)
                ->where('location','=',$this->to_location)
                ->where('active','=',1)
                ->remember(10)
                ->get(array('vas_id','percent','money','money_add'))->toArray();
            if(empty($DbQueryVasWeight)){
                $this->message  = Lang::get('response.UNSUPPORT_WEIGHT');
                $this->code     = 'UNSUPPORT_WEIGHT';
                return false;
            }
        }

        $DbQueryVas = array_merge($DbQueryVasPrice,$DbQueryVasWeight,$DbQueryVasCollect);

        if(!empty($DbQueryVas)){
            foreach($DbQueryVas as $value){
                if(isset($arrVasCode[((int)$value['vas_id'])])){
                    $Code   = strtolower(trim($arrVasCode[((int)$value['vas_id'])]));
                    if($value['percent'] == 0){
                        $Fee[$Code] = $value['money'] + $value['money_add'];
                    }else{
                        if($arrVasCode[((int)$value['vas_id'])] == 'cod'){ // Cod
                            $Fee[$Code]  = $value['percent'] * $MoneyCollect;
                            if($this->caculate_courier != 7){
                                $Fee[$Code]  += $value['money_add'];
                            }

                        }elseif($arrVasCode[((int)$value['vas_id'])] == 'protected'){ // Bảo Hiểm
                            $Fee[$Code]  = $value['percent'] * $this->amount;
                        }else{ // Khối lượng
                            $Fee[$Code]  = $value['money'] + $value['money_add'];
                        }

                        if($this->caculate_courier == 7){
                            // Cho khách hàng
                            $Fee[$Code] = $Fee[$Code] > $value['money'] ? round($Fee[$Code], -2) : $value['money'];
                        }else{
                            // Cho hvc
                            $Fee[$Code] = $Fee[$Code] > $value['money'] ? round($Fee[$Code], 0) : $value['money'];
                        }

                    }
                }
            }
        }

        return $Fee;
    }

    private function FeeDiscount(){
        $FeePickup  = 0;
        if(!empty($this->courier) && !empty($this->LCourier)){
            $FeePickup  = (int)$this->LCourier['money_pickup'];
        }

        $this->output['discount']   = [
            'pvc'       => 0,
            'pcod'       => 0,
        ];

        // Người bán miễn giảm cho người mua
        $this->output['seller']     = [
            'pvc'       => 0,
            'pcod'      => 0,
            'discount'  => 0
        ];

        // Đơn hàng về kho boxme , miễn phí vận chuyển
        if(preg_match("/^BX/i", $this->order_code) && ($this->from_city == $this->to_city) && (in_array($this->from_location, [1,2]))){
            if(!empty($this->token)){
                $Domain  = Config::get('config_api.domain.boxme');
                if(!empty($Domain) && (($this->token == $Domain['caculate']) || ($this->token == $Domain['create']))){
                    $this->output['discount']['pvc']    = $this->output['pvc'] + $FeePickup;
                }
            }elseif($this->domain == 'boxme.vn' && $this->type == 'change'){
                $this->output['discount']['pvc']    = $this->output['pvc'] + $FeePickup;
            }
        }

        if(!empty($this->exchange_id) && $this->type_exchange == 1){ // Đơn hàng quay về miễn phí 15% pvc
            $this->output['discount']['pvc']    += $this->output['pvc']*0.15;
        }

        $TotalFeeVC   = $this->output['pvc'] + $FeePickup - $this->output['discount']['pvc'];
        // Miễn phí vận chuyển cho người nhận
       if($this->payment == 2){ // Không
            $this->output['seller']['pvc']          = $TotalFeeVC;
        }

        if($this->payment_cod == 1){ // C?thu ph?CoD c?a ngu?i mua
            $this->output['seller']['pcod']         = $this->output['vas']['cod'] - $this->output['discount']['pcod'];
        }else{
            // Tru?ng h?p t?o kh?g t? trang web ShipChung  t?h cod theo c?u h?h ngu?i b?
            if(!in_array($this->type, ['shipchung','change']) && $this->user_id  > 0){
                $Config = sellermodel\FeeModel::where('user_id',    $this->user_id)->first();
                if(isset($Config->id)){
                    if(empty($this->payment)){ // Khi ko truy?n c?u h?h  tr? ph?v?n chuy?n
                        if($Config->shipping_fee    == 1){
                            $this->output['seller']['pvc']          = ($TotalFeeVC) > $Config->shipping_cost_value ? $Config->shipping_cost_value : $TotalFeeVC;
                        }elseif($Config->shipping_fee   == 2){
                            $this->output['seller']['pvc'] = $TotalFeeVC;
                        }
                    }

                    // T?h ph?CoD khi kh?g truy?n c?u h?h l?
                    if(empty($this->payment_cod)){
                        if($Config->cod_fee == 1){
                            $this->output['seller']['pcod']         = $this->output['vas']['cod'] - $this->output['discount']['pcod'];
                        }
                    }
                }
            }
        }

        /* có thu tiền phí vận chuyển nhưng phí cod = 0
        if($this->output['seller']['pvc'] > 0 && $this->output['vas']['cod'] == 0){
            if($this->to_city == $this->from_city && in_array($this->from_city, [18,52])){
                if(in_array($this->to_location, [1,2])){
                    $this->output['vas']['cod'] = 0;
                }else{
                    $this->output['vas']['cod'] = 10000;
                }
            }
            else{
                if(in_array($this->to_city, [18,52])){
                    if(in_array($this->to_location, [1,2])){
                        $this->output['vas']['cod'] = 10000;
                    }else{
                        $this->output['vas']['cod'] = 15000;
                    }
                }else{
                    if($this->to_location == 1){
                        $this->output['vas']['cod'] = 10000;
                    }else{
                        $this->output['vas']['cod'] = 15000;
                    }
                }
            }

            //Check lại discount
            if($this->domain == 'chodientu.vn'){
                $this->output['discount']['pcod']   = $this->output['vas']['cod']/2;
            }
            // nếu khách hàng tạo từ nguồn khác   trạng tạo shipchung
            if(isset($Config->id) && $Config->cod_fee == 1){
                $this->output['seller']['pcod'] = $this->output['vas']['cod'] - $this->output['discount']['pcod'];
            }
        }*/

        // seller discount   tổng tiền người bán giảm cho người mua
        $this->output['seller']['discount'] = $TotalFeeVC + array_sum($this->output['vas']) - $this->output['discount']['pcod'] - $this->output['seller']['pvc'] - $this->output['seller']['pcod'];

        return true;
    }
    /**
     * End Calculate
     *
     */

    public function ResponseData($error){
        $ret = [
            'error'         => $error,
            'code'          => $this->code,
            'message'       => mb_convert_encoding($this->message,'UTF-8','UTF-8'),
            'error_message' => mb_convert_encoding($this->message,'UTF-8','UTF-8'),
            'data'          => $this->output,
            'stock'         => $this->stock
        ];

        return $this->json ? Response::json($ret) : $ret;
    }

    private function ResponseCreate($error){
        $ret = [
            'error'         => $error,
            'code'          => $this->code,
            'message'       => mb_convert_encoding($this->message,'UTF-8','UTF-8'),
            'data'          => $this->data
        ];

        if ($this->json) {
            $res = Response::json($ret, 200);
            $res->setEncodingOptions(JSON_UNESCAPED_UNICODE);
            return $res;
        }

        return $ret;
    }

    private function __check_boxme($Order){ // check đơn hàng về kho boxme
        if($Order->domain   == 'boxme.vn' && (!preg_match("/^o[0-9]+$/i", $Order->order_code))){
            return true;
        }else{
            return false;
        }
    }

    // Check Balance
    private function CheckBalance(){
        $Model      = new   \accountingmodel\MerchantModel;

        try{
            $Merchant   = $Model::firstOrCreate(['merchant_id' => $this->user_id]);
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

        $Total = $Merchant->balance - $Merchant->freeze + $Merchant->provisional;

        if($Merchant->level >= 2){
            $Total += $Merchant->quota;
        }

        return ['error' => false,'money_total'   => $Total, 'merchant' => $Merchant, 'level' => $Merchant->level];
    }

    /**
     * Caculate Fee HVC
     */
    public function getCaculaterCourier(){
        $type           = Input::has('type')                ? (int)Input::get('type')                   : null;
        $tracking_code  = Input::has('tracking_code')       ? trim(Input::get('tracking_code'))         : 0;
        $Order  = ordermodel\OrdersModel::where('time_accept','>=',1475254800)->where('time_pickup','>=',1477933200);

        if(!empty($tracking_code)){
            $Order  = $Order->where('tracking_code', $tracking_code);
        }

        if(isset($type)){
            $Order  = $Order->whereRaw('time_pickup % 10 = '.$type);
        }

        $Order  = $Order->where('verify_id','>',0)
                        ->where('courier_id',1)
                        ->where('is_finalized',0)
                        ->where('from_country_id',237)
                        ->where('to_country_id',237)
                        ->orderBy('time_accept','ASC')
                        ->first(['id','tracking_code','to_address_id','courier_id','service_id','from_city_id','from_district_id',
                            'total_weight','total_amount','status']);

        if(!isset($Order->id)){
            return ['error' => true, 'message'  => 'EMPTY'];
        }

        $OrderDetail    = ordermodel\DetailModel::where('order_id',$Order->id)->first();
        if(!isset($OrderDetail->id)){
            return ['error' => true, 'message'  => 'ORDER_DETAIL_NOT_EXIST', 'order_id' => $Order->tracking_code];
        }

        $OrderAddress   = ordermodel\AddressModel::where('id', $Order->to_address_id)->first();
        if(!isset($OrderAddress->id)){
            return ['error' => true, 'message'  => 'ORDER_ADDRESS_NOT_EXIST', 'order_id' => $Order->tracking_code];
        }

        $this->caculate_courier = $Order->courier_id;
        $this->service          = $Order->service_id;
        $this->from_city        = $Order->from_city_id;
        $this->from_district    = $Order->from_district_id;
        $this->to_city          = $OrderAddress->city_id;
        $this->to_district      = $OrderAddress->province_id;
        $this->weight           = $Order->total_weight;
        $this->amount           = $Order->total_amount;
        $this->money_collect    = $OrderDetail->money_collect;
        if($Order->total_amount > ($OrderDetail->sc_pvc + $OrderDetail->sc_cod + $OrderDetail->sc_pbh - $OrderDetail->sc_discount_pvc - $OrderDetail->sc_discount_cod)){
            $this->cod          = 1;
        }


        //Boxme
        $ArrayService   = [
            1   => 1,
            2   => 2,
            3   => 1,
            4   => 2,
            5   => 5,
            6   => 6,
            8   => 8
        ];

        $this->service  = $ArrayService[$this->service];
        if($this->__check_boxme($Order)){ // đơn về kho
            $this->service    = 1;
        }

        //get vas
        if($OrderDetail->money_collect > 0){
            $this->list_vas[]   = 'cod';
        }

        if($OrderDetail->sc_pbh > 0){
            $this->list_vas[]   = 'protected';
        }

        $AreaQuery        = AreaLocationModelDev::where('courier_id', $this->caculate_courier)->whereIn('province_id',[$this->to_district, $this->from_district])
            ->where('active','=',1);

        if($this->from_city == $this->to_city){ // nội tỉnh
            $AreaQuery  = $AreaQuery->where('type',1);
        }else{// liên tỉnh
            $AreaQuery  = $AreaQuery->where('type',2);
        }

        $AreaQuery = $AreaQuery->remember(10)->get(['type', 'province_id', 'city_id', 'location_id'])->toArray();

        foreach($AreaQuery as $val){
            if($val['province_id'] == $this->from_district){
                $this->from_location    = (int)$val['location_id'];
            }

            if($val['province_id'] == $this->to_district){
                $this->to_location    = (int)$val['location_id'];
            }
        }

        if(empty($this->from_location) || empty($this->to_location)){
            return ['error' => true, 'message'  => 'EMPTY_LOCATION', 'order_id' => $Order->tracking_code];
        }

        if(!$this->_calculate()){
            ordermodel\OrdersModel::where('time_accept','>=',1422723600)->where('id', $Order->id)->update(['is_finalized' => 2]);
            return ['error' => true, 'message'  => $this->message, 'order_id' => $Order->tracking_code];
        }
        DB::connection('orderdb')->beginTransaction();

        if($this->__check_boxme($Order)){ // đơn về kho
            $this->output['pvc']    = 0;
        }

        $DataUpdate = [
            'hvc_pvc'   => $this->output['pvc'],
            'hvc_cod'   => $this->output['vas']['cod'],
            'hvc_pbh'   => $this->output['vas']['protected'],
            'hvc_pch'   => 0
        ];

        if($Order->status == 66){
            $DataUpdate['hvc_cod']    = 0;
            $DataUpdate['hvc_pbh']    = 0;

            if($Order->courier_id == 8){
                $DataUpdate['hvc_pch']   = $DataUpdate['hvc_pvc']/2;
            }elseif($Order->courier_id == 1){
                if(($this->from_city != $this->to_city) || !in_array($this->to_location,[1])){
                    $DataUpdate['hvc_pch']   = $DataUpdate['hvc_pvc']/2;
                }
            }elseif($Order->courier_id == 11){
                if($this->from_city != $this->to_city){
                    $DataUpdate['hvc_pch']   = $DataUpdate['hvc_pvc']/2;
                }
            }
        }

        try{
            ordermodel\OrdersModel::where('time_accept','>=',1443632400)->where('id', $Order->id)->update(['is_finalized' => 1]);
            ordermodel\DetailModel::where('order_id',$Order->id)->update($DataUpdate);

            DB::connection('orderdb')->commit();
        }catch (Exception $e){
            return ['error' => true, 'message'  => $e->getMessage(), 'order_id' => $Order->tracking_code];
        }

        return ['error' => true, 'message'  => 'Success', 'order_id' => $Order->tracking_code];

    }

    /**
     * Checkout NL
     */
    function postCheckoutnganluong( $token = '' ){
        //return Response::json(array('error' => 'success', 'message' => 'Táº¡o giao dá»‹ch NgÃ¢n LÆ°á»£ng thÃ nh cÃ´ng', 'LinkCheckout' => 'http://services.shipchung.vn/popup/nganluong?Token=2dc5dab81c9814dfae07b86687774f75&OrderCode=SC1423719151'));
        // Get Log Merchant
        $LMongo         = new LMongo;
        $dbMerchant = LMongo::collection('log_checkout_merchant')->find($token);
        if(!$dbMerchant OR !$dbMerchant['ReceiverEmail']){
            return Response::json(array('error' => 'empty', 'message' => 'Not found data!'));
        }

        if(!$dbMerchant['ReceiverEmail']){
            return Response::json(array('error' => 'ReceiverEmail', 'message' => 'Not data'));
        }

        $result = $this->postCreate(false);

        if($result['error'] == true){
            return Response::json($result);
        }

        $transactionToken = md5($this->__TrackingCode.$dbMerchant['ReceiverEmail'].Config::get('app.key'));

        $params = array(
            'merchant_site_code'    => strval(Config::get('constants.MERCHANT_ID_SC')),
            'return_url'            => URL::to('popup/nganluong').'?Token='.$transactionToken.'&OrderCode='.$this->__TrackingCode,
            'receiver'              => $dbMerchant['ReceiverEmail'],
            'transaction_info'      => strval('Thanh toán đơn hàng'),
            'order_code'            => strval($this->__TrackingCode),
            'price'                 => strval($dbMerchant['Order']['Amount']),
            'currency'              => strval('vnd'),
            'quantity'              => strval($dbMerchant['Order']['Quantity']),
            'tax'                   => strval(0),
            'discount'              => strval(0),
            'fee_cal'               => strval(0),
            'fee_shipping'          => ($this->__DataLog['detail']['seller_pvc'] + $this->__DataLog['detail']['seller_cod'] - $this->__DataLog['detail']['seller_discount']) < 0 ? 0 : ($this->__DataLog['detail']['seller_pvc'] + $this->__DataLog['detail']['seller_cod'] - $this->__DataLog['detail']['seller_discount']),
            'order_description'     => $dbMerchant['Order']['ProductName'],
            'buyer_info'            => Input::get('To.Name').' *|* '.Input::get('To.Email').' *|* '.Input::get('To.Phone').' *|* '.Input::get('To.Address'),
            'affiliate_code'        => ''
        );

        $secure_code = implode(' ', $params) . ' ' . Config::get('constants.MERCHANT_PASS_SC');

        $params['secure_code']  = md5($secure_code);
        $params['cancel_url']  = $dbMerchant['Domain'];

        foreach($dbMerchant['Item'] as $i => $item){
            $stt = $i + 1;
            $params['item_name'.$stt]         = trim($item['Name']);
            $params['item_amount'.$stt]       = (int)$item['Price'];
            $params['item_quantity'.$stt]     = (int)$item['Quantity'];
            $params['item_weight'.$stt]       = (int)$item['Weight'];
        }

        //var_dump($params);die;
        //return Response::json($params);

        /*try {
            $CurlResult = \cURL::post(Config::get('config_api.API_POST_NL'),$params);
        } catch (Exception $e) {
            return Response::json(array('error' => 'ERROR NGAN LUONG', 'message' => 'Lá»—i táº¡o giao dá»‹ch NgÃ¢n LÆ°á»£ng - '.$nl_errorcode));
        }


        $xml_result =  preg_replace('#&(?=[a-z_0-9]+=)#', '&amp;',(string)$CurlResult);
        $nl_result  = simplexml_load_string($xml_result);

        //return Response::json(array('NganLuong' => $nl_result, 'ShipChung' => $params));

        $nl_errorcode       = (string)$nl_result->error_code;
        $nl_checkout_url    = (string)$nl_result->checkout_url;
        $nl_token           = (string)$nl_result->token;
        $nl_time_limit      = (string)$nl_result->time_limit;
        $nl_description     = (string)$nl_result->description;

        if($nl_errorcode != '00'){
            return Response::json(array('error' => 'ERROR NGAN LUONG', 'message' => 'Lá»—i táº¡o giao dá»‹ch NgÃ¢n LÆ°á»£ng - '.$nl_errorcode));
        }

        sellermodel\TransactionNLmodel::insert(array(
            'token'             => $transactionToken,
            'tracking_code'     => $this->__TrackingCode,
            'transaction_code'  => $nl_token,
            'params'            => json_encode($params),
            'respond'           => json_encode($nl_result),
            'status'            => 'PENDING',
            'time_due'          => $nl_time_limit - 300,
            'time_create'       => $this->time(),
            'time_update'       => $this->time()
        ));*/
        $nl_checkout_url = Config::get('config_api.API_POST_NL').'?'.http_build_query($params);

        return Response::json(array('error' => 'success', 'message' => Lang::get('response.CREATE_TRANSACTION_NL_SUCCESS'), 'LinkCheckout' => $nl_checkout_url));
    }

}