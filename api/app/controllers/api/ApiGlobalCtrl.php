<?php

class ApiGlobalCtrl extends \BaseController {
    private $error          = false;
    public $message        = 'success';
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
    private $exchange_id    = 0;

    private $from_country   = 237;
    private $from_city      = 0;
    private $from_district  = 0;
    private $from_ward      = 0;
    private $from_name      = '';
    private $from_phone     = '';
    private $from_address   = '';
    private $stock          = 0;
    private $inventory      = 0;//Kho hàng từ chợ điện tử
    private $boxme_warehouse_code = "";


    private $to_country     = 0;
    private $to_city        = 0;
    private $to_district    = 0;
    private $to_ward        = 0;
    private $to_name        = '';
    private $to_phone       = '';
    private $to_phone_code  = '84';

    private $to_address     = '';
    private $to_email       = '';
    private $to_zipcode      = '';

    private $to_buyer_id    = 0;
    private $item_id        = 0;

    private $weight         = 0;
    private $boxsize        = '';
    private $amount         = 0;
    private $product_name   = '';
    private $quantity       = 0;
    private $description    = '';
    private $order_code     = '';

    //config
    private $service        = 8;
    private $checking       = 2;
    private $fragile        = 2;

    private $domain         = 'shipchung.vn';
    private $type           = 'shipchung';
    private $token          = '';
    private $check_app      = false;

    private $fee_id         = 0;
    private $post_code      = 0;

    function __construct(){
        $this->MerchantKey          = Input::has('MerchantKey')     ? trim(Input::get('MerchantKey'))           : '';
        $this->domain               = Input::has('Domain')          ? trim(Input::get('Domain'))                : 'shipchung.vn';
        $this->courier              = Input::has('Courier')         ? (int)Input::get('Courier')                : 0;
        $this->type                 = Input::has('Type')            ? strtolower(trim(Input::get('Type')))      : 'shipchung';
        $this->token                = Input::has('Token')           ? strtolower(trim(Input::get('Token')))     : '';

        $this->from_country         = Input::has('From.Country')    ? (int)Input::get('From.Country')    : 237;
        $this->from_city            = Input::has('From.City')       ? (int)Input::get('From.City')       : 0;
        $this->from_district        = Input::has('From.Province')   ? (int)Input::get('From.Province')   : 0;
        $this->from_ward            = Input::has('From.Ward')       ? (int)Input::get('From.Ward')       : 0;
        $this->from_address         = Input::has('From.Address')    ? trim(Input::get('From.Address'))   : '';
        $this->from_name            = Input::has('From.Name')       ? trim(Input::get('From.Name'))      : '';
        $this->from_phone           = Input::has('From.Phone')      ? trim(Input::get('From.Phone'))     : '';
        $this->stock                = Input::has('From.Stock')      ? (int)Input::get('From.Stock')      : 0;
        $this->inventory            = Input::has('From.Inventory')  ? trim(Input::get('From.Inventory')) : 0;// Kho hàng chợ điện tử
        $this->post_code            = Input::has('From.PostCode')   ? (int)Input::get('From.PostCode')   : 0;

        $this->to_country           = Input::has('To.Country')      ? (int)Input::get('To.Country')     : 237;
        $this->to_city              = Input::has('To.City')         ? (int)Input::get('To.City')        : 0;
        $this->to_district          = Input::has('To.Province')     ? (int)Input::get('To.Province')    : 0;
        $this->to_ward              = Input::has('To.Ward')         ? (int)Input::get('To.Ward')        : 0;
        $this->to_name              = Input::has('To.Name')         ? trim(Input::get('To.Name'))       : '';
        $this->to_phone             = Input::has('To.Phone')        ? trim(Input::get('To.Phone'))      : '';
        $this->to_phone_code        = Input::has('To.PhoneCode')    ? trim(Input::get('To.PhoneCode'))  : '84';
        $this->to_zipcode           = Input::has('To.Zipcode')      ? trim(Input::get('To.Zipcode'))    : '';

        $this->to_address           = Input::has('To.Address')      ? trim(Input::get('To.Address'))    : '';
        $this->to_email             = Input::has('To.Email')        ? trim(Input::get('To.Email'))      : '';

        $this->to_buyer_id          = Input::has('To.BuyerId')      ? (int)Input::get('To.BuyerId')      : 0;

        $this->service              = Input::has('Config.Service')          ? (int)Input::get('Config.Service')     : 8;
        $this->checking             = Input::has('Config.Checking')         ? (int)Input::get('Config.Checking')    : 2;
        $this->fragile              = Input::has('Config.Fragile')          ? (int)Input::get('Config.Fragile')     : 2;
        $this->auto_accept          = Input::has('Config.AutoAccept')       ? (int)Input::get('Config.AutoAccept')  : 0;


        $this->order_code           = Input::has('Order.Code')              ? trim(Input::get('Order.Code'))            : '';
        $this->weight               = Input::has('Order.Weight')            ? (int)Input::get('Order.Weight')           : 350;
        $this->boxsize              = Input::has('Order.BoxSize')           ? trim(Input::get('Order.BoxSize'))         : '';
        $this->amount               = Input::has('Order.Amount')            ? (int)Input::get('Order.Amount')           : 0;
        $this->product_name         = Input::has('Order.ProductName')       ? trim(Input::get('Order.ProductName'))     : '';
        $this->quantity             = Input::has('Order.Quantity')          ? (int)Input::get('Order.Quantity')         : 0;
        $this->description          = Input::has('Order.Description')       ? trim(Input::get('Order.Description'))     : '';

        $this->item_id             = Input::has('Order.ItemId')            ? (int)Input::get('Order.ItemId')           : 0;

        $this->check_app            = Input::has('App')                     ? Input::get('App')                         : false;

        if($this->weight == 0){
            $this->weight   = 350;
        }

	//Check phone
        // if(!empty($this->to_phone) && !preg_match('/^0/', $this->to_phone)){
        //     if(!preg_match('/^84/', $this->to_phone)){
        //         $this->to_phone = '0'.$this->to_phone;
        //     }
        // }

        if(!empty($this->boxsize)){
            $str            = explode('x',$this->boxsize);
            $this->weight   = ceil(($str[0] * $str[1] * $str[2]) / 5000);
        }

        if(!empty($this->post_code)){
            if(!$this->__getPostOffice()){
                $this->error    = true;
                return;
            }
        }

        // lấy thông tin user
        if(!$this->_getMerchantInfo()){
            $this->error    = true;
            return;
        }

        // Khi ko truyền kho
        if(empty($this->stock) && empty($this->from_city)){
            if(empty($this->user_id)){
                $this->message  = "Bạn chưa chọn địa chỉ lấy hàng";
                $this->code     = "Inventory Empty";
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
            
            if((empty($this->from_city)) && !$SellerInventoryInfo){
                $this->error    = true;
                return;
            }
            
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
    }

	public function getIndex()
	{
        return Response::json('1', 200);
	}

    private function _validation($create = false){
        $Data       = [
            'user_id'           => $this->user_id,
            'courier'           => $this->courier,
            'domain'            => $this->domain,

            'from_country'      => $this->from_country,
            'from_city'         => $this->from_city,
            'from_district'     => $this->from_district,
            'from_ward'         => $this->from_ward,
            'from_address'      => $this->from_address,
            'stock'             => $this->stock,
            'from_name'         => $this->from_name,
            'from_phone'        => $this->from_phone,
            'inventory'         => $this->inventory,

            'to_country'        => $this->to_country,
            'to_city'           => $this->to_city,
            'to_address'        => $this->to_address,
            'to_name'           => $this->to_name,
            'to_phone'          => $this->to_phone,

            'amount'            => $this->amount,
            'weight'            => $this->weight,
            'product_name'      => $this->product_name,
            'quantity'          => $this->quantity,

            'domain'            => $this->domain,
            'service'           => $this->service,
            'checking'          => $this->checking,
            'fragile'           => $this->fragile

        ];

        $dataInput = array(
            'from_country'      => 'required|numeric|min:1',
            'from_city'         => 'required|numeric|min:1',
            'from_district'     => 'sometimes|required|numeric',
            'to_country'        => 'required|numeric|min:1',

            'amount'            => 'required|numeric|min:1',
            'weight'            => 'required|numeric|min:1',

            'service'           => 'required|numeric|in:8,9',
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

                'from_ward'             => 'required|numeric|min:0',
                'from_address'          => 'required',
                'from_name'             => 'required',
                'from_phone'            => 'required',

                'to_name'               => 'required',
                'to_phone'              => 'required',
                'to_city'               => 'required|numeric|min:1',
                'to_address'            => 'required',
            );
        }

        $message = [
            'from_phone.required'    =>  'Thiếu thông tin số điện thoại người gửi, vui lòng cập nhật !'
        ];

        $this->validation = Validator::make($Data, $dataInput, $message);
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

        $courier = $this->SuggestCourier();
        if(!$courier){
            return $this->ResponseData(true);
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
            $this->id_log   = $LMongo::collection('log_create_lading')->insert(array( 'input' => Input::all(),'time_create' => $this->time(),'date_create' => date('d/m/Y H:i:s') ));
        }catch (Exception $e){
            $this->code     = 'INSERT_LOG_ERROR';
            $this->message  = 'Tạo vận đơn lỗi, hãy thử lại!';
            $this->data = $e->getMessage();
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

        //Get Courier
        $courier = $this->SuggestCourier();
        if(!$courier){
            return $this->ResponseCreate(true);
        }

        $this->courier = (int)$courier[0]['courier_id'];

        if(empty($this->courier)){ // trường hợp ko truyền hvc excel
            $this->courier = (int)$courier[0]['courier_id'];
            $this->LCourier = $courier[0];
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

        if($this->auto_accept == 1){
            $CheckBalance   = $this->CheckBalance();
            if($CheckBalance['error']){
                $this->code         = $CheckBalance['message'];
                $this->message      = 'Số dư của bạn chưa được xác định, hãy thử lại';
                $this->_update_log([
                    'error' => 'fail','error_message' => 'Tạo vận đơn thất bại', 'data' => $this->code
                ]);
                return $this->ResponseCreate(true);
            }else{
                $TotalFee = ($this->output['pvc'] + (int)$this->LCourier['money_pickup']) - $this->output['discount']['pvc'];
                if(($CheckBalance['money_total'] - $TotalFee) < 0){
                    $this->code         = 'NOT_ENOUGH_MONEY';
                    $this->message      = 'Số dư tài khoản của quý khách không đủ để duyệt đơn hàng này. Vui lòng nạp tiền để tạo đơn hàng.';
                    $this->data         = [
                        'money_total'   => $CheckBalance['money_total'],
                        'fee'           => $TotalFee
                    ];
                    $this->_update_log([
                        'error' => 'fail','error_message' => 'Tạo vận đơn thất bại', 'data' => $this->code
                    ]);
                    return $this->ResponseCreate(true);
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
                'error' => 'fail','error_message' => 'Tạo vận đơn thất bại', 'data' => $this->code
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
                'ShowFee'       => [
                    'pvc' => $this->output['pvc']
                ]
        ];

        if($this->domain == 'beta.chodientu.vn' || $this->domain == 'chodientu.vn'){
            $this->data['ShowFee']['money_pickup']      = isset($this->LCourier['money_pickup']) ? $this->LCourier['money_pickup'] : 0;
            $this->data['ShowFee']['discount_pvc']      = $this->output['discount']['pvc'];
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
            $this->message  = 'Bạn chưa cấu hình kho hàng trên hệ thống ShipChung';
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
        
        $this->from_country     = (int)$Data['country_id'];
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
            $this->message  = 'Bưu cục lựa chọn không tồn tại';
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
                $this->message  = 'Mã không tồn tại';
                return false;
            }
            $this->user_id          = (int)$dbKey->user_id;
            if($this->auto_accept == 0){
                $this->auto_accept      = (int)$dbKey->auto;
            }
        }elseif($this->type == 'excel' && Input::has('UserId')){
            $this->user_id          = (int)Input::get('UserId');
        }

        return true;
    }

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
            $this->message  = 'Mã kho hàng không tồn tại';
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
                $this->message  = 'Thêm mới kho hàng thất bại';
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
                                $this->message  = 'Mã Phường/Xã không chính xác';
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
                                $this->message  = 'Mã Phường/Xã không chính xác';
                                return false;
                            }
                        }
                    }
                }
            }else{
                $this->code     = 'WARDID_NOT_EXISTS';
                $this->message  = 'Mã Phường/Xã không tồn tại';
                return false;
            }
        }

        if(!empty($this->from_district)){
            $ListDistrict[] = $this->from_district;
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
                                $this->message  = 'Mã Quận/Huyện không chính xác';
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
                                $this->message  = 'Mã Quận/Huyện không chính xác';
                                return false;
                            }
                        }
                    }
                }
            }else{
                $this->code     = 'DISTRICTID_NOT_EXISTS';
                $this->message  = 'Mã Quận/Huyện không tồn tại';
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
        $PickupArea = PromisePickupModel
            ::where('province_id', $this->from_city)
            ->where('district_id', $this->from_district)
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
                                    /*->remember(60)*/->get(['id','courier_id','estimate_pickup','district_id'])->toArray();

        if(empty($PickupArea)){
            $this->code     = 'UNSUPPORTED';
            $this->message  = (!empty($this->courier) ? $this->list_courier[$this->courier] : 'Shipchung').' chưa hỗ trợ lấy hàng tại khu vực này, vui lòng liên hệ CSKH để được hỗ trợ.';
            return false;
        }

        //Check Refuse Pickup
        $arrRefusePickup    = [];
        if(!empty($this->from_ward)){
            $arrRefusePickup   = CourierRefusePickupModel::where('ward_id', $this->from_ward)->where('active',1)->remember(30)->lists('courier_id');
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

        if($this->user_id > 0 && $this->domain != 'boxme.vn'){
            $ConfigCourier  = $this->TypeConfigCourier();

            if(empty($ConfigCourier)){
                $this->code     = 'CONFIG_COURIER_ERROR';
                $this->message  = 'Kết nối dữ liệu thất bại, hãy thử lại.';
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
                    $this->message  = 'Không tìm được hãng vận chuyển cấu hình phù hợp.';
                    return false;
                }
            }
        }

        $CourierPromiseModel    = new systemmodel\CourierPromiseGlobalModel; // promise delivery
        $DeliveryPromise = $CourierPromiseModel
            ::where('from_country', $this->from_country)
            ->where('to_country', $this->to_country)
            ->whereIn('courier_id',$arrCourier)
            ->where('service_id',$this->service)
            ->where('active',1)->orderBy('estimate_delivery')
            ->orderBy('courier_id','ASC')
            /*->remember(60)*/
            ->get()->toArray();

        $DeliveryDistrict   = [];
        if(!empty($DeliveryPromise)){
            foreach($DeliveryPromise as $val){
                $val['estimate_delivery'] = $val['courier_estimate_delivery'];
                $DeliveryDistrict[(int)$val['courier_id']]      = $val;
            }
        }

        if(empty($DeliveryDistrict)){
            $this->code     = 'UNSUPPORTED';
            $this->message  = (!empty($this->courier) ? $this->list_courier[$this->courier] : 'Shipchung').' chưa hỗ trợ giao hàng tới khu vực này, vui lòng liên hệ CSKH để được hỗ trợ.';
            return false;
        }

        $CourierRefuse  = [];
        foreach($DeliveryDistrict as $value){
            if(!in_array($value['courier_id'],$CourierRefuse)){
                $leatime_delivery   = $value['estimate_delivery'];
                $leatime_total      = $leatime_delivery;
                $CourierRefuse[]    = (int)$value['courier_id'];

                if($leatime_delivery > 24){
                    $leatime_str = ceil($leatime_delivery / 24)." ngày";
                }else {
                    $leatime_str = $leatime_delivery." giờ";
                }

                if($this->check_app && ($leatime_total > 5*24)){
                    $leatime_total    = $leatime_total - 2*24;
                    $leatime_delivery = $leatime_total;
                }

                $courier_description = $this->getListCourier();

                $OuputCourier[$value['courier_id']] = [
                    'courier_id'            => $value['courier_id'],
                    'courier_name'          => $this->list_courier[(int)$value['courier_id']],
                    'courier_description'   => isset($courier_description[(int)$value['courier_id']]) ? $courier_description[(int)$value['courier_id']]['description'] : "",
                    'courier_logo'          => isset($courier_description[(int)$value['courier_id']]) ? $courier_description[(int)$value['courier_id']]['logo'] : "",
                    'money_pickup'      => 0,
                    'leatime_delivery'  => $leatime_delivery,
                    'leatime_courier'   => isset($value['courier_estimate_delivery']) ? ceil($value['courier_estimate_delivery'] / 3600) : $leatime_delivery,
                    'leatime_total'     => $leatime_total,
                    'leatime_ward'      => 0,
                    'leatime_str'      => $leatime_str,
                    'priority'          => (isset($PriorityConfig[(int)$value['courier_id']]) ? ($PriorityConfig[(int)$value['courier_id']]) : ((int)$value['priority']))
                ];
            }
        }

        if(empty($OuputCourier)){
            $this->code     = 'UNSUPPORTED';
            $this->message  = 'Shipchung chưa hỗ trợ giao hàng tới khu vực này, vui lòng liên hệ CSKH để được hỗ trợ.';
            return false;
        }

        $OuputCourier = array_values(array_sort($OuputCourier, function($value){
            return $value['priority'];
        }));

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
        $CourierDeliveryModel   = new \systemmodel\CourierDeliveryGlobalModel;
        $Fee  = $CourierDeliveryModel::where('courier_id', $this->caculate_courier)
                ->where('service_id', $this->service)
                ->where('from_country',$this->from_country)
                ->where('to_country', $this->to_country)
                ->remember(60)->first();

        if(!isset($Fee->id)){
            $this->message  = 'Shipchung chưa cấu hình phân vùng cho khu vực này, vui lòng liên hệ CSKH để được hỗ trợ.';
            $this->code     = 'SYSTEM_UNSUPPORT';
            return false;
        }
        $this->fee_id   = (int)$Fee->fee_id;

        $CourierFeeDetail   = \systemmodel\CourierFeeDetailGlobalModel::where('fee_id','=',$this->fee_id)
            ->where('weight_start','<',$this->weight)
            ->where('weight_end','>=',$this->weight)
            ->remember(60)
            ->first(array('money','surcharge'));

        if(!isset($CourierFeeDetail->money)){
            $this->message  = 'Hệ thống chưa hỗ trợ ở mức khối lượng này';
            $this->code     = 'FEE_NOT_EXISTS';
            return false;
        }

        $this->output['pvc'] = $CourierFeeDetail->money + $CourierFeeDetail->surcharge;


        /*
         * Select Discount
         */
        $this->FeeDiscount();
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
            'phonecode'     => $this->to_phone_code,
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

        $this->PushSyncElasticsearch('bxm_orders', 'order_item', 'created', (object)$SyncOrderItem);
    }



    private function _insertBuyer(){
        $this->insertBuyerAddress = OrderAddressModel::create([
            'seller_id'     => $this->user_id,
            'country_id'    => $this->to_country,
            'city_id'       => $this->to_city,
            'province_id'   => $this->to_district,
            'zip_code'      => $this->to_zipcode,
            'ward_id'       => $this->to_ward,
            'address'       => $this->to_address,
            'time_update'   => $this->time(),
        ]);

        $this->insertBuyer = OrderBuyerModel::create([
            'seller_id'     => $this->user_id,
            'fullname'      => $this->to_name,
            'phone'         => $this->to_phone,
            'phone_code'    => $this->to_phone_code,
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

            if ($this->domain == 'seller.shipchung.vn' && empty($this->item_id)) {
                $this->_sync_item_elasticsearch($itemId);
            }
        }

    }

    private function _insertOrders(){
        $status = 20;
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
            'from_country_id'       => $this->from_country,
            

            'to_buyer_id'           => $this->insertBuyer->id,
            'to_name'               => $this->to_name,
            'to_phone'              => $this->to_phone,
            'to_phone_code'         => $this->to_phone_code,

            'to_email'              => $this->to_email,
            'to_address_id'         => $this->insertBuyerAddress->id,
            'to_country_id'         => $this->to_country,
            'to_city_id'            => $this->to_city,
            'to_district_id'        => $this->to_district,
            'product_name'          => $this->product_name,
            'total_weight'          => $this->weight,
            'total_quantity'        => $this->quantity,
            'total_amount'          => $this->amount,
            //'purchase_value'        => $this->amount,
            
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
        $this->__DataLog['detail'] = [
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
                           $status = 120; // Thiếu hàng
                           $this->code = "OUT_OF_STOCK";
                        }else {
                           foreach($CheckBSIN['available_bsin'] as $value){
                               \warehousemodel\StatisticReportProductModel::PlusInventoryWait($value['sku'], $value['quantity'], $this->boxme_warehouse_code);
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
            ->where('country_id', $this->from_country)
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

    private function FeeDiscount(){
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
}
