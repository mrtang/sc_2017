<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class BaseCtrl extends Controller{
    private $user = [];
    public $url_upload = 'http://cloud.boxme.vn/storage/';
    public $link_upload = 'uploads';
    public $url         = 'http://services.shipchung.vn/api/rest';
    private $lang = 'vi';


    public function __construct(){
        $headers = getallheaders();
        if(isset($headers['LANGUAGE']) && !empty($headers['LANGUAGE'])){
            $this->lang = $headers['LANGUAGE']; 
        }
    }

    /**
     * User Info
     */
    public function UserInfo()
    {
        if (Session::has('user_info'))
        {
            $this->user = Session::get('user_info');
        }

        return $this->user;
    }

    public function time(){
        $remote_tz = 'Asia/Ho_Chi_Minh';
        if(!is_string($origin_tz = date_default_timezone_get())) {
            return time();
        }

        $origin_dtz = new DateTimeZone($origin_tz);
        $remote_dtz = new DateTimeZone($remote_tz);
        $origin_dt = new DateTime("now", $origin_dtz);
        $remote_dt = new DateTime("now", $remote_dtz);
        return time() - (int)$origin_dtz->getOffset($origin_dt) + (int)$remote_dtz->getOffset($remote_dt);
    }

    public function __convert_time($time){
        return date('Y-m-d H:i:s', $time);
    }

    /**
     * Log Query
     */
    public function LogQuery(){
        DB::listen(function($sql, $bindings, $time){
            $logFile = storage_path('logs/query.log');
            $monolog = new Logger('log');
            $monolog->pushHandler(new StreamHandler($logFile), Logger::INFO);
            $monolog->info($sql, compact('bindings', 'time'));
        });
    }

    /**
     * Merchant Key
     */
    public function getMerchantKey($json = true){
        $Key    = Input::has('MerchantKey') ? strtoupper(trim(Input::get('MerchantKey')))   : '';
        $dbKey  = \ApiKeyModel::where('key',$Key)->first(['user_id','auto']);
        return $json ? Response::json(['error' => false,'data'  => $dbKey]) : $dbKey;
    }

    public function getMasterId($CountryId){
        return accountingmodel\MerchantModel::where('level',10)->where('country_id',$CountryId)->first();
    }


    public function getListAddress(){
        $Full   = Input::has('full') ? Input::get('full') : false;
        $Country = Input::has('country_id') ? Input::get('country_id') : 237;
        
        if((int)$Country == 237){

            $Type = 'address_symnoyms_v2';
            if ($Full) {
                $Type = 'address_full';
            }

            $Model = new \ElasticBuilder('suggestions', $Type);
            $Data = $Model->take(20000)->get();

            $ret = json_decode(json_encode($Data,JSON_UNESCAPED_UNICODE));
        }else {
            $query ="SELECT lc_ward.id as ward_id, ward_name, ward_name_local, lc_district.id as district_id, lc_district.district_name, lc_district.district_name_local, lc_city_global.id as city_id, lc_city_global.city_name , CONCAT(ward_name, ', ', district_name, ', ', city_name) as full_address FROM `lc_ward` JOIN lc_district ON lc_ward.district_id = lc_district.id JOIN lc_city_global ON lc_ward.city_id = lc_city_global.id WHERE zip_code is NOT NULL AND lc_city_global.country_id=".$Country;
           $ret = \DB::connection('metadb')->select(\DB::raw($query));
        }

        return Response::json([
            'error'=> false,
            'data' => $ret
        ]);
        
    }

    public function getFlushCache ($cache_name = ''){

        try {
            if(!empty($cache_name)){
                Cache::forget($cache_name);
            }else {
                Cache::flush();
            }
        } catch (\Exception $e) {
            return Response::json(['error' => true,'data'  => "Flush Fail"]);
        }

        return Response::json(['error' => false,'data'  => "Flush Success"]);
    }

	/**
	 * Get Courier
	 */
	public function getCourier($json = true){
        if(Cache::has('cache_courier')){
            $Courier        =  Cache::get('cache_courier');
        }else{
            $CourierModel   = new CourierModel;
            $Courier        = $CourierModel->get_courier();

            if(!empty($Courier)){
                Cache::put('cache_courier', $Courier, 30);
            }

        }
        return $json ? Response::json(['error' => false,'data'  => $Courier]) : $Courier;
    }

    /**
     * Get Service
     */
    public function getService($json = true){
        if(Cache::has('cache_service_'.$this->lang)){
            $Service        =  Cache::get('cache_service_'.$this->lang);
        }else{
            $ServiceModel   = new CourierServiceModel;
            $Service        = $ServiceModel->get_service();

            if(!empty($Courier)){
                Cache::put('cache_service_'.$this->lang, $Service, 30);
            }
        }

        return $json ? Response::json(['error' => false,'data'  => $Service]) : $Service;
    }

    /* *
     * Get City
     */
    public function getCity($json = true){
        if(Cache::has('cache_city')){
            $City        =  Cache::get('cache_city');
        }else{
            $CityModel   = new CityModel;
            $City        = $CityModel->get_city();

            if(!empty($City)){
                Cache::put('cache_city', $City, 30);
            }
        }

        return $json ? Response::json(['error' => false,'data'  => $City], 200, ['Access-Control-Allow-Origin' => 'shipchung.vn']) : $City;
    }

    public function getCityGlobal($country_id){
        $CityModel   = new \CityGlobalModel;
        $City        = $CityModel::where('country_id', $country_id)->get()->toArray();

        return Response::json([
            'error'          => false,
            'error_message'  => '',
            'data'           => $City
        ]);
    }

    public function getCountry($json = true){

    //  if(Cache::has('cache_country')){
    //      $Country        = Cache::get('cache_country');
    //  }else{
         

    //      if(!empty($CountryModel)){
    //          Cache::put('cache_country', $CountryModel, 10);
    //      }
    //  }

     $CountryModel   = new CountryModel;
         $Country        = $CountryModel::get()->toArray();

     return $json ? Response::json(['error' => false,'data'  => $Country]) : $Country;
    }

    //list tien te
    public function getCurrency(){
        $Currency = [];
        $Model = new metadatamodel\CurrencyModel;
        $data = $Model->get(array('id','country','code','symbol'))->toArray();
        if(!empty($data)){
            foreach($data AS $val){
                $Currency[] = array(
                    'id' => $val['id'],
                    'country' => $val['code'].' - '.$val['country'].' ('.$val['symbol'].')',
                    'symbol' => $val['symbol']
                );
            }
        }
        return Response::json([
            'error'          => false,
            'error_message'  => '',
            'data'           => $Currency
        ]);
    }

    /**
     * Get Status
     */
    public function getStatus($json = true){
        
        if(Cache::has('cache_status_'.$this->lang)){
            $Status         =  Cache::get('cache_status_'.$this->lang);
        }else{
            $OrderStatusModel   = new metadatamodel\OrderStatusModel;
            $Status             = $OrderStatusModel->get_status();
            if(!empty($Status)){
                Cache::put('cache_status_'.$this->lang, $Status, 30);
            }
        }

        return $json ? Response::json(['error' => false,'data'  => $Status]) : $Status;
    }

    /*
     * Get Status Group
     */
    public function getStatusGroup($json = true)
    {
        $group              = Input::has('group')                ? (int)Input::get('group')                       : 4;
        $CacheName          = 'cache_group_status_'.$group.'_'.$this->lang;
        if (Cache::has($CacheName)){
            $ListGroup    = Cache::get($CacheName);
        }else{
            $Model      = new metadatamodel\GroupStatusModel;
            $ListGroup  = $Model::where('group', $group)->with('group_order_status')->get()->toArray();
            if(!empty($ListGroup)){
                Cache::put($CacheName,$ListGroup,30);
            }
        }
        return $json ? Response::json([ 'error'         => false, 'list_group'    => $ListGroup]) : $ListGroup;

    }

    /**
     * Get District
     */
    public function getDistrict($json = true){
        $City           = Input::has('city')    ? (int)Input::get('city')   : 18 ;
        $CacheName      = 'cache_district_'.$City;

        if(Cache::has($CacheName)){
            $District   =  Cache::get($CacheName);
        }else{
            $DistrictModel   = new DistrictModel;
            $District   = $DistrictModel->get_district($City);

            if(!empty($District)){
                Cache::put($CacheName, $District, 30);
            }
        }
        return $json ? Response::json(['error' => false,'data'  => $District]) : $District;

    }

    /**
     * Get District
     */
    public function getAllDistrict($json = true){
        $CacheName      = 'cache_district';

        if(Cache::has($CacheName)){
            $District   =  Cache::get($CacheName);
        }else{
            $District   = DistrictModel::orderBy('district_name','DESC')->get()->toArray();

            if(!empty($District)){
                Cache::put($CacheName, $District, 30);
            }
        }
        return $json ? Response::json(['error' => false,'data'  => $District]) : $District;

    }

  

    /**
     * Get status courier
     */
    public function getStatusCourier($json = true){
        $Courier        = Input::has('courier')     ? (int)Input::get('courier')    : 1 ;
        $Type           = Input::has('type')        ? (int)Input::get('type')       : 1 ;

        $CacheName      = 'cache_status_courier_'.$Courier.'_'.$Type.'_'.$this->lang;
        $Status         = [];

        if(Cache::has($CacheName)){
            $Status   =  Cache::get($CacheName);
        }else{
            $CourierStatusModel  = new CourierStatusModel;
            $ListStatus   = $CourierStatusModel::where('courier_id', $Courier)
                                               ->where('type', $Type)
                                               ->where('active', 1)
                                               ->remember(60)
                                               ->get()->toArray();

            if(!empty($ListStatus)){
                foreach($ListStatus as $val){
                    $Status[$val['courier_status']]    = (int)$val['sc_status'];
                }

                Cache::put($CacheName, $Status, 30);
            }
        }
        return $json ? Response::json(['error' => false,'data'  => $Status]) : $Status;

    }

    /**
     * Get Group Status   // Lấy nhóm theo trạng thái
     */
    public function getGroupByStatus($json = true){
        $group              = Input::has('group')                ? (int)Input::get('group')                       : 4;
        $CacheName          = 'cache_status_group_'.$group.'_'.$this->lang;
        $ListGroup          = [];

        if (Cache::has($CacheName)){
            $ListGroup    = Cache::get($CacheName);
        }else{
            $GroupStatus = $this->getStatusGroup(false);
            if(!empty($GroupStatus)){
                foreach($GroupStatus as $val){
                    if(!empty($val['group_order_status'])){
                        foreach($val['group_order_status'] as $v){
                            $ListGroup[(int)$v['order_status_code']]    = [
                                'group_status'  => $val['id'],
                                'group_name'    => $val['name']
                            ];
                        }
                    }
                }

                if(!empty($ListGroup)){
                    Cache::put($CacheName,$ListGroup,30);
                }
            }
        }

        return $json ? Response::json([ 'error'         => false, 'list_group'    => $ListGroup]) : $ListGroup;
    }

    /**
     * Get Group Status   // Lấy nhóm theo trạng thái
     */
    public function getDistrictByLocation($json = true)
    {
        $location   = Input::has('location')    ? (int)Input::get('location')   : 0;
        $city       = Input::has('city')        ? (int)Input::get('city')       : 0;

        $CacheName = 'cache_district_by_location_' . $location . '_' . $city.'_'.$this->lang;
        $ListLocation = [];

        if (Cache::has($CacheName)) {
            $ListLocation = Cache::get($CacheName);
        } else {
            $AreaQuery = new \AreaLocationModel;

            if (!empty($location)) {
                $AreaQuery = $AreaQuery->where('location_id', $location);
            }

            if (!empty($city)) {
                $AreaQuery = $AreaQuery->where('city_id', $city);
            }

            $ListLocation = $AreaQuery->where('active', '=', 1)->groupBy('province_id')->lists('province_id');

            if (!empty($ListLocation)) {
                Cache::put($CacheName, $ListLocation, 30);
            }
        }

        return $json ? Response::json(['error' => false, 'list_location' => $ListLocation]) : $ListLocation;
    }

        /*
         * Get Status by Group
         */
    public function getStatusByGroup($json = true){
        $group              = Input::has('group')                ? (int)Input::get('group')                       : 4;
        $CacheName          = 'cache_group_status'.$group.'_'.$this->lang;
        $ListGroup          = [];

        if (Cache::has($CacheName)){
            $ListGroup    = Cache::get($CacheName);
        }else{
            $GroupStatus = $this->getStatusGroup(false);
            if(!empty($GroupStatus)){
                foreach($GroupStatus as $val){
                    if(!empty($val['group_order_status'])){
                        foreach($val['group_order_status'] as $v){
                            $ListGroup[(int)$val['id']][] = (int)$v['order_status_code'];
                        }
                    }
                }

                if(!empty($ListGroup)){
                    Cache::put($CacheName,$ListGroup,30);
                }
            }
        }

        return $json ? Response::json([[ 'error'         => false, 'list_group'    => $ListGroup], 200, ['Access-Control-Allow-Origin' => '*']]) : $ListGroup;
    }

    public function getVip($json = true){

        $UserInfo       = new sellermodel\UserInfoModel;
        return $json ? Response::json(['error'         => false,'message'       => 'success','data'          => $UserInfo->getVip()]) : $UserInfo->getVip();
    }

    public function getLoyaltyUser($json = true){
        $User = [];

        if(Cache::has('cache_loyalty_user')){
            $User         =  Cache::get('cache_loyalty_user');
        }else{
            $ListUser   = \loyaltymodel\UserModel::where('level','>',0)->get(['id','user_id','level'])->toArray();
            if(!empty($ListUser)){
                foreach($ListUser as $val){
                    $User[$val['user_id']]   = $val;
                }

                Cache::put('cache_loyalty_user', $User, 30);
            }
        }

        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $User]) : $User;
    }

    public function getTag($json = true){
        $ListTag    = [];

        if(Cache::has('cache_tag')){
            $ListTag         =  Cache::get('cache_tag');
        }else{
            $OrderTagModel   = new ordermodel\OrderTagModel;
            $Data   = $OrderTagModel::where('active',1)->orderBy('id','ASC')->get()->toArray();
            if(!empty($Data)){
                foreach($Data as $val){
                    $ListTag[$val['code']]   = $val;
                }

                Cache::put('cache_tag', $ListTag, 30);
            }
        }

        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $ListTag]) : $ListTag;
    }

    public function getCaseTicket($json = true)
    {
        $Data  = ticketmodel\CaseModel::where('active',1)->remember(30)->get()->toArray();
        return $json ? Response::json(['error'  => false,'message'  => 'success','data' => $Data]) : $Data;
    }

    public function getCaseType($json = true)
    {
        $Data  = ticketmodel\CaseTypeModel::where('active',1)->remember(30)->get()->toArray();
        return $json ? Response::json(['error'     => false, 'message'   => 'success', 'data'      => $Data]) : $Data;
    }

    public function getLoyaltyLevel($json = true){
        $CacheName  = 'cache_sc_loyalty_level';

        if(Cache::has($CacheName)){
            $Level  =  Cache::get($CacheName);
        }else{
            $Data   = loyaltymodel\LevelModel::where('active',1)->get()->toArray();
            if(!empty($Data)){
                foreach($Data as $val){
                    $Level[$val['code']]   = $val;
                }

                Cache::put($CacheName, $Level, 30);
            }
        }

        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $Level]) : $Level;
    }

    public function getLoyaltyCategory($json = true){
        $CacheName  = 'cache_sc_loyalty_category';
        if(Cache::has($CacheName)){
            $Category  =  Cache::get($CacheName);
        }else{
            $Data   = loyaltymodel\CategoryModel::where('active',1)->get()->toArray();
            if(!empty($Data)){
                foreach($Data as $val){
                    $Category[$val['id']]   = $val;
                }

                Cache::put($CacheName, $Category, 30);
            }
        }

        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $Category]) : $Category;
    }

    public function getKpiGroupCategory($json = true){
        $Group          = Input::has('group')   ? (int)Input::get('group')    : 0;
        $GroupCategory  = [];

        $CacheName  = 'cache_kpi_group_category_'.$Group;
        if(Cache::has($CacheName)){
            $GroupCategory  =  Cache::get($CacheName);
        }else{
            $KPIGroupCategoryModel   = reportmodel\KPIGroupCategoryModel::where('active',1);
            if(!empty($Group)){
                $KPIGroupCategoryModel   = $KPIGroupCategoryModel->where('group', $Group);
            }
            $Data   = $KPIGroupCategoryModel->get()->toArray();

            if(!empty($Data)){
                foreach($Data as $val){
                    $GroupCategory[$val['id']]   = $val;
                }

                Cache::put($CacheName, $GroupCategory, 10);
            }
        }

        return $json ?  Response::json(['error' => false,'message'  => 'success','data' => $GroupCategory]) : $GroupCategory;
    }

    public function getKpiCategory($json = true){
        $CacheName  = 'cache_kpi_category';
        if(Cache::has($CacheName)){
            $Category  =  Cache::get($CacheName);
        }else{
            $Data   = reportmodel\KPICategoryModel::where('active',1)->get()->toArray();
            if(!empty($Data)){
                foreach($Data as $val){
                    $Category[$val['id']]   = $val;
                }

                Cache::put($CacheName, $Category, 10);
            }
        }

        return $json ?  Response::json(['error' => false,'message'  => 'success','data' => $Category]) : $Category;
    }

    public function getBmSellerStock($json = true){
        $Courier      = Input::has('courier')   ? (int)Input::get('courier')    : 0;
        $ListStock    = [];


        $CacheName  = 'cache_bm_seller_stock_'.$Courier;Cache::forget($CacheName);
        if(Cache::has($CacheName)){
            $ListStock         =  Cache::get($CacheName);
        }else{
            $StockModel         = new fulfillmentmodel\StockModel;
            $Data   = $StockModel::where('courier',$Courier)->where('active',1)->orderBy('id','ASC')->get()->toArray();
            if(!empty($Data)){
                foreach($Data as $val){
                    $ListStock[$val['code']][]   = $val;
                }

                Cache::put($CacheName, $ListStock, 10);
            }
        }

        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $ListStock]) : $ListStock;
    }

    public function getBmSellerPacking($json = true){
        $Courier      = Input::has('courier')   ? (int)Input::get('courier')    : 0;
        $ListPacking  = [];

        $CacheName  = 'bm_seller_packing_cache_'.$Courier;
        if (Cache::has($CacheName)){
            $ListPacking    = Cache::get($CacheName);
        }else{
            $Packing  = fulfillmentmodel\PackingModel::where('courier',$Courier)->get()->toArray();
            if(!empty($Packing)){
                foreach($Packing as $val){
                    $ListPacking[$val['volume_limit']]    = $val;
                }

                Cache::put($CacheName,$ListPacking,10);
            }
        }

        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $ListPacking]) : $ListPacking;
    }

    public function getBarcode($str){
        if(!empty($str)){
            return \DNS1D::getBarcodePNG($str, "C128A",2,40);
        }
        return false;
    }

    public function __status_partner($domain = ''){
        $Status     = [];
        $PartnerModel   = new omsmodel\PartnerStatusModel;
        $ListStatus = $PartnerModel::where('domain',$domain)->where('active',1)->with('__order_status')->remember(10)->get(['status','partner_status'])->toArray();
        if(!empty($ListStatus)){
            foreach($ListStatus as $val){
                $Status[$val['status']] = [
                    'code'      => strtoupper(trim($val['partner_status'])),
                    'detail'    => isset($val['__order_status']['name']) ? $val['__order_status']['name'] : ''
                ];

            }
        }

        return $Status;
    }

    public function getWareHouseBoxme($json = true){
        $ListWareHouse  = [];
        if (Cache::has('bm_warehouse_cache')){
            $ListWareHouse    = Cache::get('bm_warehouse_cache');
        }else{
            $WareHouse  = warehousemodel\WareHouseModel::get()->toArray();
            if(!empty($WareHouse)){
                foreach($WareHouse as $val){
                    $ListWareHouse[strtoupper(trim($val['code']))]    = $val;
                }
                Cache::put('bm_warehouse_cache',$ListWareHouse,60);
            }
        }

        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $ListWareHouse]) : $ListWareHouse;
    }

    public function getShipmentStatusBoxme($json = true){
        $ListStatus  = [];
        if (Cache::has('bm_shipment_status_cache')){
            $ListStatus    = Cache::get('bm_shipment_status_cache');
        }else{
            $Status  = metadatamodel\ShipmentStatusModel::get()->toArray();
            if(!empty($Status)){
                foreach($Status as $val){
                    $ListStatus[strtoupper(trim($val['code']))]    = $val;
                }
                Cache::put('bm_shipment_status_cache',$ListStatus,60);
            }
        }

        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $ListStatus]) : $ListStatus;
    }

    public function getItemStatusBoxme($json = true){
        $ListStatus  = [];
        if (Cache::has('bm_item_status_cache')){
            $ListStatus    = Cache::get('bm_item_status_cache');
        }else{
            $Status  = metadatamodel\ProductItemStatusModel::get()->toArray();
            if(!empty($Status)){
                foreach($Status as $val){
                    $ListStatus[strtoupper(trim($val['code']))]    = $val;
                }
                Cache::put('bm_item_status_cache',$ListStatus,60);
            }
        }

        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $ListStatus]) : $ListStatus;
    }

    public function getPickupStatusBoxme($json = true){
        $ListStatus  = [];
        if (Cache::has('bm_pickup_status_cache')){
            $ListStatus    = Cache::get('bm_pickup_status_cache');
        }else{
            $Status  = warehousemodel\PickupStatusModel::get()->toArray();
            if(!empty($Status)){
                foreach($Status as $val){
                    $ListStatus[strtoupper(trim($val['code']))]    = $val;
                }
                Cache::put('bm_pickup_status_cache',$ListStatus,60);
            }
        }

        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $ListStatus]) : $ListStatus;
    }

    // Boxme DR Status
    public function getDrStatusBoxme($json = true){
        $ListStatus  = [];
        if (Cache::has('bm_dr_status_cache')){
            $ListStatus    = Cache::get('bm_dr_status_cache');
        }else{
            $Status  = warehousemodel\DRStatusModel::get()->toArray();
            if(!empty($Status)){
                foreach($Status as $val){
                    $ListStatus[strtoupper(trim($val['code']))]    = $val;
                }
                Cache::put('bm_dr_status_cache',$ListStatus,60);
            }
        }
        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $ListStatus]) : $ListStatus;
    }

    public function getPutawayStatusBoxme($json = true){
        $ListStatus  = [];
        if (Cache::has('bm_putaway_status_cache')){
            $ListStatus    = Cache::get('bm_putaway_status_cache');
        }else{
            $Status  = warehousemodel\PutawayStatusModel::get()->toArray();
            if(!empty($Status)){
                foreach($Status as $val){
                    $ListStatus[strtoupper(trim($val['code']))]    = $val;
                }
                Cache::put('bm_putaway_status_cache',$ListStatus,60);
            }
        }
        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $ListStatus]) : $ListStatus;
    }

    public function getPackageStatusBoxme($json = true){
        $ListStatus  = [];
        if (Cache::has('bm_package_status_cache')){
            $ListStatus    = Cache::get('bm_package_status_cache');
        }else{
            $Status  = warehousemodel\PackageStatusModel::get()->toArray();
            if(!empty($Status)){
                foreach($Status as $val){
                    $ListStatus[strtoupper(trim($val['code']))]    = $val;
                }
                Cache::put('bm_package_status_cache',$ListStatus,60);
            }
        }
        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $ListStatus]) : $ListStatus;
    }

    public function getProductStandard($json = true){
        $List  = [];Cache::forget('bm_product_standard_cache');
        if (Cache::has('bm_product_standard_cache')){
            $List    = Cache::get('bm_product_standard_cache');
        }else{
            $ListProduct  = fulfillmentmodel\ProductStandardModel::get()->toArray();
            if(!empty($ListProduct)){
                foreach($ListProduct as $val){
                    $List[strtoupper(trim($val['product_standard']))]    = $val;
                }
                Cache::put('bm_product_standard_cache',$List,10);
            }
        }
        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $List]) : $List;
    }

    public function getWarehouseStatusBoxme($json = true){
        $ListStatus  = [];
        if (Cache::has('bm_warehouse_status_cache')){
            $ListStatus    = Cache::get('bm_warehouse_status_cache');
        }else{
            $Status  = warehousemodel\ItemStatusModel::get()->toArray();
            if(!empty($Status)){
                foreach($Status as $val){
                    $ListStatus[strtoupper(trim($val['id']))]    = $val;
                }
                Cache::put('bm_warehouse_status_cache',$ListStatus,60);
            }
        }

        return $json ?  Response::json(['error'         => false,'message'       => 'success','data'          => $ListStatus]) : $ListStatus;
    }

    public function getWMSType($json = true){
        $UserId      = Input::has('user_id')    ? (int)Input::get('user_id')    : 0;
        $Data       = ['error' => true, 'data'   => null];

        $Merchant = sellermodel\UserWMSTypeModel::where('user_id', $UserId)->where('active',1)->first();
        if(isset($Merchant->id)){
            $Data  = ['error' => false, 'data'    => $Merchant->wms_type];
        }

        return $json ?  Response::json($Data) : $Data['data'];
    }

    public function getPackingByWeight($json = true){
        $UserId      = Input::has('user_id')    ? (int)Input::get('user_id')    : 0;
        $Time        = Input::has('time')       ? (int)Input::get('time')       : 0;
        $Data        = [];

        if(empty($UserId)){
            return $json ?  Response::json($Data) : $Data;
        }

        //Check theo cấu hình riêng của khách hàng
        $Data   = \fulfillmentmodel\PackingByWeightModel::where('user_id', $UserId);
        if(!empty($Time)){
            $Data = $Data->where('time_start','<=',$Time)
                         ->where(function($query) use($Time){
                         $query->where(function($q) use($Time){
                            $q->where('active',0)
                                ->where('time_end','>=',$Time);
                         })->orWhere(function($q) use($Time){
                            $q->where('active',1)
                                ->where('time_end', 0);
                         });
                     });
        }
        $Data   = $Data->remember(10)->get()->toArray();

        return $json ?  Response::json($Data) : $Data;
    }

    public function getPackingByUser($json = true){
        $UserId      = Input::has('user_id')    ? (int)Input::get('user_id')    : 0;
        $Time        = Input::has('time')       ? (int)Input::get('time')       : 0;
        $Data        = [];

        if(empty($UserId)){
            return $json ?  Response::json($Data) : $Data;
        }

        //Check theo cấu hình riêng của khách hàng
        $Data   = \fulfillmentmodel\PackingByUserModel::where('user_id', $UserId);
        if(!empty($Time)){
            $Data = $Data->where('time_start','<=',$Time)
                ->where(function($query) use($Time){
                    $query->where(function($q) use($Time){
                        $q->where('active',0)
                            ->where('time_end','>=',$Time);
                    })->orWhere(function($q) use($Time){
                        $q->where('active',1)
                            ->where('time_end', 0);
                    });
                });
        }
        $Data   = $Data->remember(10)->get()->toArray();

        return $json ?  Response::json($Data) : $Data;
    }

    public function getStockByUser($json = true){
        $UserId      = Input::has('user_id')    ? (int)Input::get('user_id')    : 0;
        $Time        = Input::has('time')       ? (int)Input::get('time')       : 0;
        $Data        = [];

        if(empty($UserId)){
            return $json ?  Response::json($Data) : $Data;
        }

        //Check theo cấu hình riêng của khách hàng
        $Data   = \fulfillmentmodel\StockByUserModel::where('user_id', $UserId);
        if(!empty($Time)){
            $Data = $Data->where('time_start','<=',$Time)
                ->where(function($query) use($Time){
                    $query->where(function($q) use($Time){
                        $q->where('active',0)
                            ->where('time_end','>=',$Time);
                    })->orWhere(function($q) use($Time){
                        $q->where('active',1)
                            ->where('time_end', 0);
                    });
                });
        }
        $Data   = $Data->get()->toArray();

        return $json ?  Response::json($Data) : $Data;
    }

    /**
     * KPI ID by  code
     */
    public function getKpiByCode($json = true){
        $Code       = Input::has('code')    ? strtolower(trim(Input::get('code')))      : '';
        $Group      = Input::has('group')   ? (int)Input::get('group')                  : 0;
        $Active     = Input::has('active')  ? (int)Input::get('active')                 : null;

        $Model      = new \reportmodel\KPICategoryModel;
        if(!empty($Code)){
            $Code   = explode(',',$Code);
            $Model  = $Model->whereIn('code', $Code);
        }

        if(!empty($Group)){
            $Model  = $Model->where('group_category_id', $Group);
        }

        if(isset($Active)){
            $Model  = $Model->where('active', $Active);
        }

        $Data   = $Model->get()->toArray();

        return $json ? Response::json(['error' => false,'data'  => $Data]) : $Data;
    }

    // Lấy nhóm của nhóm nhân viên theo quốc gia, nhóm
    public function getKpiGroupEmployee($json = true){
        $Group          = Input::has('group')       ? (int)Input::get('group')          : 0;
        $CountryId      = Input::has('country_id')  ? (int)Input::get('country_id')     : 0;
        $Active         = Input::has('active')  ? (int)Input::get('active')             : null;

        $Model      = new \reportmodel\KPIGroupConfigModel;

        if(!empty($Group)){
            $Model  = $Model->where('group', $Group);
        }

        if(!empty($CountryId)){
            $Model  = $Model->where('country_id', $CountryId);
        }

        if(isset($Active)){
            $Model  = $Model->where('active', $Active);
        }

        $Data   = $Model->remember(10)->get()->toArray();

        return $json ? Response::json(['error' => false,'data'  => $Data]) : $Data;
    }

    public function getEmployeeGroup($json = true){
        $Group          = Input::has('group')       ? (int)Input::get('group')          : 0;
        $CountryId      = Input::has('country_id')  ? (int)Input::get('country_id')     : 0;
        $Active         = Input::has('active')  ? (int)Input::get('active')             : null;

        $Model  = new \reportmodel\KPIGroupConfigModel;
        $Data   = [];

        if(!empty($Group)){
            $Model  = $Model->where('group', $Group);
        }

        if(!empty($CountryId)){
            $Model  = $Model->where('country_id', $CountryId);
        }

        if(isset($Active)){
            $Model  = $Model->where('active', $Active);
        }

        $ListGroup  = $Model->remember(10)->lists('id');
        if(!empty($ListGroup)){
            $Data   = \reportmodel\CrmEmployeeModel::whereIn('group_config', $ListGroup)->where('active',1)->remember(10)->lists('user_id');
        }

        return $json ? Response::json(['error' => false,'data'  => $Data]) : $Data;
    }

}
