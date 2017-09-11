<?php
namespace trigger;
require_once app_path().'/libraries/nusoap.php';

use Mockery\CountValidator\Exception;
use ordermodel\OrdersModel;
use DistrictModel;
use CityModel;
use WardModel;
use AreaLocationModel;
use CountryModel;
use CourierMapLocationModel;
//use nusoapclient;

use Input;
use Response;
use LMongo;
use anlutro\cURL\Request;
use Cache;

class CourierAcceptLadingCtrl extends \BaseController {

    private $__Ladings, $__LadingOne, $__LadingBackup, $__Location, $log_id, $__Logjourney;

    private $logFile = 'log_hvc_create_lading';

    private $TrackingCode   = '';
    private $Pipe;

    private $list_city      = [];
    private $list_district  = [];
    private $list_ward      = [];
    private $user           = [];
    private $pickup_config      = []; // Hãng vận chuyển lấy hàng
    private $pickup_address     = [];

    private $__PrefixCourier   = [
        'vtp'   => 1,
        'vnp'   => 2,
        'ghn'   => 3,
        'gao'   => 4,
        'net'   => 5,
        'gtk'   => 6,
        'sc'    => 7,
        'ems'   => 8,
        'gts'   => 9,
        'ctp'   => 10,
        'ttc'   => 11
    ];

    function getViettelpost( $city = 0 ){
        $params = array('courier' => 1);
        if($city > 0){
            $params['from_city_id'] = $city;
        }

        return $this->getIndex($params);
    }

    function getIndex( $config = array() ){
        $this->__LadingOne      = $this->_getLading($config);
        if(!isset($this->__LadingOne->id)){
            return Response::json(['ERROR' => 'EMPTY DATA']);
        }

        if($this->__LadingOne->courier_id == 1 && $this->__LadingOne->post_office_id > 0){
            $this->user = $this->_getUser();
        }

        if($this->__LadingOne->to_order_address->province_id == 711){
            $this->__LadingOne->to_order_address->province_id = 38;
        }

        if(empty($this->__LadingOne->post_office_id)){// Mang hàng ra bưu cục, ko cần check lấy hàng
            $this->__check_pickup_config();
        }

        try{
            if(!empty($this->pickup_config) && $this->__LadingOne->domain != "boxme.vn"){
                return $this->_process_advanced();
            }
            else{
                return $this->_process_simple(true);
            }
        }
        catch (Exception $e) {
            return Response::json(['ERROR' => 'EMPTY DATA','MSG' => $e->getMessage(),'DATA' => $this->__LadingOne]);
        }

        return Response::json($this->__LadingOne);
    }

    private function __check_pickup_config(){
        $CourierPickupConfig = \systemmodel\CourierPickupConfigModel::where('parent_courier', $this->__LadingOne->courier_id)
                                                                    ->where('service_id', $this->__LadingOne->service_id)
                                                                    ->where('country_id', $this->__LadingOne->from_country_id)
                                                                    ->whereIn('user_id', [0,$this->__LadingOne->from_user_id])
                                                                    ->whereIn('city_id', [0, $this->__LadingOne->from_city_id])
                                                                    ->whereIn('district_id', [0, $this->__LadingOne->from_district_id])
                                                                    ->whereIn('ward_id', [0, $this->__LadingOne->from_ward_id])
                                                                    ->where('active',1)
                                                                    ->orderBy('user_id','DESC')
                                                                    ->orderBy('ward_id','DESC')
                                                                    ->orderBy('district_id','DESC')
                                                                    ->orderBy('city_id','DESC')->with('__child_courier')->first();

        if(isset($CourierPickupConfig->id)){
            $this->pickup_config    = [
                'courier_id'    => (int)$CourierPickupConfig->child_courier,
                'prefix'        => isset($CourierPickupConfig->__child_courier->prefix) ? trim(strtolower($CourierPickupConfig->__child_courier->prefix)) : ''
            ];
        }
        return;
    }

    private function __get_pickup_address($CourierId, $Services, $CityId){
        $Address    = \systemmodel\CourierAddressModel::where('courier_id', $CourierId)->where('service_id', $Services)->where('city_id', $CityId)->first();
        if(isset($Address->id)){
            $this->pickup_address                               = $Address;
            $this->__LadingOne->from_city_id                    = $Address->city_id;
            $this->__LadingOne->from_district_id                = $Address->district_id;
            $this->__LadingOne->from_ward_id                    = $Address->ward_id;
            $this->__LadingOne->from_address                    = $Address->address;
            $this->__LadingOne->from_order_address->user_name   = $Address->name;
            $this->__LadingOne->from_order_address->phone       = $Address->phone;
            return true;
        }else{
            return false;// Không tồn tại
        }
    }

    private function __get_delivery_address($CourierId, $Services, $CityId){
        if(isset($this->pickup_address->id)){
            $Address    = $this->pickup_address;
        }else{
            $Address    = \systemmodel\CourierAddressModel::where('courier_id', $CourierId)->where('service_id', $Services)->where('city_id', $CityId)->first();
        }

        if(isset($Address->id)){
            $this->__LadingOne->to_order_address->city_id       = $Address->city_id;
            $this->__LadingOne->to_order_address->province_id   = $Address->district_id;
            $this->__LadingOne->to_order_address->ward_id       = $Address->ward_id;
            $this->__LadingOne->to_order_address->address       = $Address->address;
            $this->__LadingOne->to_name                         = $Address->name;
            $this->__LadingOne->to_phone                        = $Address->phone;
            return true;
        }else{
            return false;// Không tồn tại
        }
    }

    private function _process_advanced(){
        // Duyet sang HVC goc
        if(in_array($this->__LadingOne->service_id, [8,9])){
            $this->__LadingBackup   = clone $this->__LadingOne;
            // Dịch vụ quốc tế
            if(!$this->__get_pickup_address($this->__LadingOne->courier_id, $this->__LadingOne->service_id, $this->__LadingOne->from_city_id)){
                return Response::json(['ERROR' => 'COURIER_ADDRESS_EMPTY','MSG' => 'Không tồn tại địa chỉ HVC','DATA' => $this->__LadingOne]);
            }

            $Result = $this->_process_simple(false);
            $this->__LadingOne      = $this->__LadingBackup;
        }else{
            $Result = $this->_process_simple(false);
        }

        //Duyệt sang hvc lấy hàng
        if(!$this->__get_delivery_address($this->__LadingOne->courier_id, $this->__LadingOne->service_id, $this->__LadingOne->from_city_id)){
            return Response::json(['ERROR' => 'COURIER_ADDRESS_EMPTY','MSG' => 'Không tồn tại địa chỉ HVC','DATA' => $this->__LadingOne]);
        }

        // Get Location
        if(in_array($this->pickup_config['courier_id'], [1,8,9,11,14])){
            $this->__Location   = [];
            $this->_getLocationMap($this->pickup_config['courier_id']);
        }else{
            $this->_getLocation($this->pickup_config['courier_id']);
        }


        // Build Function Name
        $this->__LadingOne->order_detail->money_collect     = 0;
        $this->__LadingOne->service_id                      = 2;
        $funcName   = '_courier_'.$this->pickup_config['prefix'];
        if(method_exists($this ,$funcName)){
            // Call function
            $result     = $this->$funcName();
            if(!$result){
                return Response::json(['ERROR' => 'EMPTY DATA']);
            }
        }else{
            return Response::json(['ERROR' => 'FUNCTION_NOT_EXISTS', 'DATA' => $funcName]);
        }

        if(isset($result['ERROR']) && $result['ERROR'] == 'SUCCESS'){
            $this->_insert_log_pickup($this->__LadingOne);
            $result['update']['is_changed'] = 1;
            OrdersModel::where('time_accept','>=',$this->time() - 604800)->where('tracking_code',$this->__LadingOne->tracking_code)->update($result['update']);

            try{
                // update order_hybird
                \ordermodel\OrderHybridModel::insert([
                    'order_id'                  => $this->__LadingOne->id,
                    'tracking_code'             => $this->__LadingOne->tracking_code,
                    'courier_id'                => $this->pickup_config['courier_id'],
                    'courier_tracking_code'     => $result['update']['courier_tracking_code'],
                    'time_create'               => time()
                ]);
            }catch(\Exception $e){

            }

            $result['update']['id'] = $this->__LadingOne->id;
            $this->PushSyncElasticsearch('bxm_orders', 'orders', 'updated', $result['update']);
        }

        return $result;
    }

    private function _process_simple( $update = false ){
        // Get Location
        if(in_array($this->__LadingOne->courier_id,[1,8,9,11,14])){
            $this->_getLocationMap();
        }else{
            $this->_getLocation();
        }


        // Biuld Function Name
        $funcName   = '_courier_'.$this->__LadingOne->courier->prefix;

        //Đơn quốc tế, ko duyệt qua hvc => cap nhat = excel (service = 8)
        if(method_exists($this ,$funcName) && (!in_array($this->__LadingOne->service_id, [8,9]) || $this->__LadingOne->courier_id == 8)){
            if($this->__LadingOne->courier_id == 1 && $this->__LadingOne->total_weight < 2000 &&($this->__LadingOne->domain == 'boxme.vn') && strlen(strstr($this->__LadingOne->order_code, 'BX')) == 0 && in_array($this->__LadingOne->from_district_id,[163,186,165,175,166,167,170,173,174,178,189,190,191,717]) && in_array($this->__LadingOne->to_district_id,[163,186,165,167,170,173,174,178,189,190,191,717])){
                //$funcName   = '_courier_weship';
            }

            // Call function
            $result     = $this->$funcName();
            //return Response::json($result);
            if(!$result){
                return Response::json(['ERROR' => 'EMPTY DATA']);
            }
        }else{
            $result = ['ERROR'  => 'SUCCESS'];
            $result['update'] = ['status' => 30,'courier_tracking_code' => $this->__LadingOne->tracking_code, 'time_approve' => time()];
        }

        if($update && isset($result['ERROR']) && $result['ERROR'] == 'SUCCESS'){
            $this->_insert_log_pickup($this->__LadingOne);
            $result['update']['is_changed'] = 1;
            OrdersModel::where('time_accept','>=',time() - $this->time_limit)->where('tracking_code',$this->__LadingOne->tracking_code)->update($result['update']);
            $result['update']['id'] = $this->__LadingOne->id;
            $this->PushSyncElasticsearch('bxm_orders', 'orders', 'updated', $result['update']);
        }

        $result['courier'] = $this->__LadingOne->courier->prefix;

        return Response::json($result);
    }

    private function _getLading($config = []){

        $fields = ['id','from_address_id','to_address_id','checking','fragile','service_id','courier_id','post_office_id','tracking_code',
            'from_user_id','from_country_id','from_city_id','from_district_id','from_address','from_ward_id','to_email',
            'to_name','to_phone','product_name','total_weight','total_quantity','total_amount','status','domain', 'order_code'];

        if(Input::has('tracking_code')){
            $LadingOne = OrdersModel::where('tracking_code',Input::get('tracking_code'))->where('time_accept','>=',time() - 2592000)->where('status', 21);
        }
        else{
            $LadingOne = OrdersModel::where('status', 21)
                ->where('time_accept','>=',time() - 2592000)
                ->orderBy('time_accept','ASC');
        }
        
        //$LadingOne = $LadingOne->where('courier_id', '!=', 11);

        $LadingOne = $LadingOne->with(
            array(
                'OrderDetail',
                'Courier',
                'FromOrderAddress',
                'ToOrderAddress',
                'FromUserData'
            ))
            ->first();
        return $LadingOne;
    }

    private function _getUser(){
        return \User::where('id',$this->__LadingOne->from_user_id)->first(['id','phone','city_id','district_id','ward_id','address']);
    }


    function _getLocation( $courier = 0 ){

        if($courier == 0){
            $courier = $this->__LadingOne->courier_id;
        }

        $dbDist = DistrictModel::whereIn('id',[$this->__LadingOne->from_district_id,$this->__LadingOne->to_order_address->province_id])->get(['id','district_name']);
        $dbCity = CityModel::whereIn('id',[$this->__LadingOne->from_city_id,$this->__LadingOne->to_order_address->city_id])->get(['id','city_name']);

        $dbMap = CourierMapLocationModel::where('courier_id',$courier)
            ->whereIn('province_id',[$this->__LadingOne->from_district_id,$this->__LadingOne->to_order_address->province_id,0])
            ->whereIn('city_id',[$this->__LadingOne->from_city_id,$this->__LadingOne->to_order_address->city_id])
            ->where('active',1)->get(['city_id','province_id','code']);

        $ListWard9Id = [];
        if(!empty($this->__LadingOne->to_order_address->ward_id)){
            $ListWard9Id[]   = (int)$this->__LadingOne->to_order_address->ward_id;
        }

        if(!empty($this->__LadingOne->from_ward_id)){
            $ListWard9Id[]   = (int)$this->__LadingOne->from_ward_id;
        }

        if(!empty($ListWard9Id)){
            $dbWard = WardModel::whereIn('id',$ListWard9Id)->get(['id','ward_name'])->toArray();
            if(!empty($dbWard)){
                foreach($dbWard as $val){
                    if($val['id'] == $this->__LadingOne->to_order_address->ward_id){
                        $this->__Location['to']['ward_name'] = $val['ward_name'];
                    }

                    if($val['id'] == $this->__LadingOne->from_ward_id){
                        $this->__Location['from']['ward_name'] = $val['ward_name'];
                    }
                }

            }
        }

        foreach($dbDist as $value){
            if($this->__LadingOne->from_district_id == $this->__LadingOne->to_order_address->province_id){
                $this->__Location['from']['district_name']  = $value['district_name'];
                $this->__Location['to']['district_name']    = $value['district_name'];
            }
            else{
                $key = $value['id'] == $this->__LadingOne->from_district_id ? 'from' : 'to';
                $this->__Location[$key]['district_name'] = $value['district_name'];
            }
        }

        foreach($dbMap as $value){
            if($value['province_id'] > 0){

                if($this->__LadingOne->from_district_id == $this->__LadingOne->to_order_address->province_id){
                    $this->__Location['from']['district_code']  = $value['code'];
                    $this->__Location['to']['district_code']    = $value['code'];
                }
                else{
                    $key = $value['province_id'] == $this->__LadingOne->from_district_id ? 'from' : 'to';
                    $this->__Location[$key]['district_code'] = $value['code'];
                }

            }
            else{
                if($this->__LadingOne->from_city_id == $this->__LadingOne->to_order_address->city_id){
                    $this->__Location['from']['city_code'] = $value['code'];
                    $this->__Location['to']['city_code'] = $value['code'];
                }
                else{
                    $key = $value['city_id'] == $this->__LadingOne->from_city_id ? 'from' : 'to';
                    $this->__Location[$key]['city_code'] = $value['code'];
                }
            }
        }

        foreach($dbCity as $value){
            if($this->__LadingOne->from_city_id == $this->__LadingOne->to_order_address->city_id){
                $this->__Location['from']['city_name'] = $value['city_name'];
                $this->__Location['to']['city_name'] = $value['city_name'];
            }
            else{
                $key = $value['id'] == $this->__LadingOne->from_city_id ? 'from' : 'to';
                $this->__Location[$key]['city_name'] = $value['city_name'];

            }
        }

        $dbLocation = AreaLocationModel::leftJoin('courier_area', function($join) {
            $join->on('area_province.area_id', '=', 'courier_area.id');
        })
            ->where('courier_area.courier_id',$courier)
            ->where('area_province.province_id',$this->__LadingOne->to_order_address->province_id)
            ->first([
                'area_province.location_id'
            ]);

        $this->__Location['location'] = $dbLocation['location_id'];

        return $this->__Location;
    }

    //get location
    private function _getLocationMap($courier = ''){
        if(empty($courier)) $courier = $this->__LadingOne->courier_id;

        $LocationModel  = new \CourierLocationModel;
        $WardId         = [];

        try {
            $this->list_city       = $this->getCityGlobal([237, $this->__LadingOne->to_country_id]);
        } catch (\Exception $e) {
            print_r($e);die;
            
        }


        //var_dump($this->list_city);die();


        $ProvinceId            = [$this->__LadingOne->from_district_id, $this->__LadingOne->to_order_address->province_id];
        if(isset($this->user->district_id) && $this->user->district_id > 0){
            $ProvinceId[]   = $this->user->district_id;
        }

        $this->list_district   = $this->getProvince($ProvinceId);

        if(!empty($this->__LadingOne->from_ward_id) || !empty($this->__LadingOne->to_order_address->ward_id) || (isset($this->user->ward_id) && !empty($this->user->ward_id))){
            if($this->__LadingOne->from_ward_id > 0){
                $WardId[]   = (int)$this->__LadingOne->from_ward_id;
            }

            if($this->__LadingOne->to_order_address->ward_id > 0){
                $WardId[]   = (int)$this->__LadingOne->to_order_address->ward_id;
            }

            if(isset($this->user->ward_id) && $this->user->ward_id > 0){
                $WardId[]   = (int)$this->user->ward_id;
            }

            $this->list_ward       = $this->getWard($WardId);
        }

        if(!empty($WardId)){
            $LocationPickup = $LocationModel->where('courier_id', $courier)->whereIn('ward_id', $WardId)->get()->toArray();

            if(!empty($LocationPickup)){
                foreach($LocationPickup as $val){
                    if($val['ward_id']  == $this->__LadingOne->from_ward_id){
                        $this->__Location['from']   = [
                            'country_id'    => $val['courier_country_id'],
                            'city_id'       => $val['courier_city_id'],
                            'district_id'   => $val['courier_province_id'],
                            'ward_id'       => $val['courier_ward_id']
                        ];
                    }

                    if($val['ward_id']  == $this->__LadingOne->to_order_address->ward_id){
                        $this->__Location['to']   = [
                            'country_id'    => $val['courier_country_id'],
                            'city_id'       => $val['courier_city_id'],
                            'district_id'   => $val['courier_province_id'],
                            'ward_id'       => $val['courier_ward_id']
                        ];
                    }
                }
            }
        }

        if(!isset($this->__Location['from']) || !isset($this->__Location['to'])){
            $LocationPickup = $LocationModel->where('courier_id', $courier)->whereIn('province_id', [$this->__LadingOne->from_district_id, $this->__LadingOne->to_order_address->province_id])->where('ward_id',0)->get()->toArray();
            if(!empty($LocationPickup)){
                foreach($LocationPickup as $val){
                    if(!isset($this->__Location['from']) && ($val['province_id']  == $this->__LadingOne->from_district_id)){
                        $this->__Location['from']   = [
                            'country_id'    => $val['courier_country_id'],
                            'city_id'       => $val['courier_city_id'],
                            'district_id'   => $val['courier_province_id'],
                            'ward_id'       => ''
                        ];
                    }

                    if(!isset($this->__Location['to']) && ($val['province_id']  == $this->__LadingOne->to_order_address->province_id)){
                        $this->__Location['to']   = [
                            'country_id'    => $val['courier_country_id'],
                            'city_id'       => $val['courier_city_id'],
                            'district_id'   => $val['courier_province_id'],
                            'ward_id'       => ''
                        ];
                    }
                }
            }
        }
        return;
    }

    /**
     * get area location
     */
    private function _getAreaLocation(){
        $AreaId     = \AreaLocationModelDev::where('courier_id', $this->__LadingOne->courier_id)->where('province_id',$this->__LadingOne->to_order_address->province_id)->where('active','=',1);
        if($this->__LadingOne->from_city_id == $this->__LadingOne->to_order_address->city_id){ // nội tỉnh
            $AreaId  = $AreaId->where('type',1);
        }else{// liên tỉnh
            $AreaId  = $AreaId->where('type',2);
        }

        $AreaId = $AreaId->first();
        return isset($AreaId->location_id) ? $AreaId->location_id : 0;
    }


    /**
     * Process Courier -->
     */

    // Viettelpost
    private function _login_vtp(){return false;
        // Define Info API - WS Viettel Post
        define('USER_WEBSERVICE_VTP','SC');
        define('PASS_WEBSERVICE_VTP','SCG123456a@');
        define('KEYS_WEBSERVICE_VTP','FSuRVhbovqTS5ldFuzRc3Q==');

        $cacheFile  = 'viettelpost_login';

        if(Cache::has($cacheFile)){
            $cache = Cache::get($cacheFile);
            if(isset($cache['faultcode'])){
                Cache::forget($cacheFile);
            }
            else{
                return $cache;
            }
        }

        $soapClientLogin = new \nusoapclient('http://203.113.130.254/VTPAPITMDT/user.asmx?wsdl', 'wsdl');
        $soapClientLogin->soap_defencoding = 'UTF-8';

        // Login VTP
        $loginParams = array(
            'userName'  => USER_WEBSERVICE_VTP,
            'password'  => PASS_WEBSERVICE_VTP,
            'appKey'    => KEYS_WEBSERVICE_VTP
        );

        $resultLogin = $soapClientLogin->call('Login',$loginParams);
        // Ghi log
        $updateLog = array(
            'params' => $loginParams,
            'result' => $resultLogin,
        );

        if(!$resultLogin)
        {
            $updateLog['error_code']    = 'FAIL_API_LOGIN';
            $updateLog['messenger']     = 'Lỗi login API VTP';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Login Viettel Post thất bại');
        }

        if(isset($resultLogin['faultcode'])){
            $updateLog['error_code']    = 'FAIL_API_LOGIN';
            $updateLog['messenger']     = 'Lỗi login API VTP';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Login Viettel Post thất bại', 'DETAIL' => $resultLogin);
        }


        if(!empty($resultLogin['detail']['Error']) && empty($resultLogin['LoginResult']))
        {
            if($resultLogin['detail']['Error']['ErrorStatus'] == 1)
            {
                // Ghi log
                $updateLog['error_code']    = 'FAIL_LOGIN';
                $updateLog['messenger']     = $resultLogin['detail']['Error']['ErrorMessage'];
                \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
                return array('ERROR'=> 'FAIL', 'MSG'=> $resultLogin['detail']['Error']['ErrorMessage']);
            }
            else
            {
                // Ghi log
                $updateLog['error_code']    = 'FAIL_LOGIN_OTHER';
                $updateLog['messenger']     = $resultLogin['detail']['Error']['ErrorMessage'];
                \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
                return array('ERROR'=> 'FAIL', 'MSG'=> $resultLogin['detail']['Error']['ErrorMessage']);
            }
        }

        Cache::put($cacheFile, $resultLogin, 30);
        return $resultLogin;

    }

    // Goldtimes
    private function _login_gts(){
        $cacheFile  = 'goldtime_login';

        //if(Cache::has($cacheFile))
        //return json_decode(Cache::get($cacheFile));

        // Get token
        $RespondToken = \cURL::get('http://epost.goldtimes.vn/epost-api/oauth/token?grant_type=password&client_id=my-trusted-client&username=ship_chung&password=ship_chung@123');

        if(!$RespondToken){
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Login thất bại');
        }

        $result = json_decode($RespondToken);
        //Cache::put($cacheFile, $RespondToken, ($result->expires_in - 60) / 60);
        return $result;
    }

    private function _login_vnp(){
        $cacheFile  = 'vietnampost_login';

        if(Cache::has($cacheFile)){
            $cache = Cache::get($cacheFile);
            return $cache;
        }

        $soapClientLogin = new \nusoapclient('http://buudienhanoi.com.vn/Nhanh/BDHNNhanh.asmx?wsdl', 'wsdl');
        $soapClientLogin->soap_defencoding = 'UTF-8';

        // Login VTP
        $loginParams = array(
            'Ma'  => 'ShipChung*BuuDien123'
        );

        $resultLogin = $soapClientLogin->call('KetNoi',$loginParams);

        // Ghi log
        $updateLog = array(
            'params' => $loginParams,
            'result' => $resultLogin,
        );

        if(!$resultLogin)
        {
            $updateLog['error_code']    = 'FAIL_API_LOGIN';
            $updateLog['messenger']     = 'Lỗi login API VNP';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Login VietNam Post thất bại');
        }

        if(!isset($resultLogin['KetNoiResult']) || (int)$resultLogin['KetNoiResult'] == 0){
            $updateLog['error_code']    = $resultLogin['KetNoiResult'];
            $updateLog['messenger']     = 'Lỗi login API VNP';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Login VietNam Post thất bại', 'DETAIL' => $resultLogin);
        }

        Cache::put($cacheFile, $resultLogin['KetNoiResult'], 30);
        return $resultLogin['KetNoiResult'];
    }

    private function _login_ninjavan(){
        define('USER_CLIENT_ID','0b9c9105a31646fb8ce5796627db2960');
        define('USER_CLIENT_SECRET','102d78e9f414402189236cb98b1192ef');
        
        //dev
        //define('USER_CLIENT_ID','b70dbb2d871848f7b16921457cd3a9f1');
        //define('USER_CLIENT_SECRET','2de681b9cd804bf1aa155d295b566e12');
        define('USER_GRANT_TYPE','client_credentials');

        $cacheFile  = 'ninjavan_login';

        if(Cache::has($cacheFile)){
            $cache = Cache::get($cacheFile);
            if(isset($cache['expires']) && $cache['expires'] >  time()){
                return $cache;
                Cache::forget($cacheFile);
            }
            else{
                Cache::forget($cacheFile);
            }
        }

        $loginParams = ["client_id" => USER_CLIENT_ID,"client_secret" => USER_CLIENT_SECRET,"grant_type" =>"client_credentials"];
        $data_string = json_encode($loginParams);
        //$ch = curl_init('https://api-sandbox.ninjavan.co/sg/2.0/oauth/access_token');
        $ch = curl_init('https://api.ninjavan.co/vn/2.0/oauth/access_token');

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'));
        $resultLogin = curl_exec($ch);

        $updateLog = array(
            'params' => $loginParams,
            'result' => $resultLogin,
        );

        if(!$resultLogin)
        {
            $updateLog['error_code']    = 'FAIL_API_LOGIN';
            $updateLog['messenger']     = 'Lỗi login API NJV';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Login NinJavan thất bại');
        }

        $resultLogin = json_decode($resultLogin,1);

        if(!isset($resultLogin['access_token']) || empty($resultLogin['access_token'])){
            $updateLog['error_code']    = 'FAIL_API_LOGIN';
            $updateLog['messenger']     = 'Lỗi login API NJV';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Login NinJavan thất bại', 'DETAIL' => $resultLogin);
        }

        Cache::put($cacheFile, $resultLogin, 29000);
        return $resultLogin;

    }

    private function _courier_vtp(){
        $this->log_id = \LMongo::collection($this->logFile)->insert(array('courier' => 1,'sc_code' => $this->__LadingOne->tracking_code,'time_create' => $this->time()));

        if(!isset($this->__Location['from']) || !isset($this->__Location['to'])){
            return false;
        }

        $ToLocation    = $this->_getAreaLocation();

        // Login VTP
        /*$resultLogin = $this->_login_vtp();//return $resultLogin;
        if(isset($resultLogin['ERROR']) && $resultLogin['ERROR'] == 'FAIL')
        {
            return $resultLogin;
        }*/

        // Biuld data
        $noteBonus = $service_code = '';

        // Loại vận đơn
        if($this->__LadingOne->order_detail->money_collect > 0)
            $type_lading = 3; // cod
        else
            $type_lading = 1; //pas

        $tong_tien  = $this->__LadingOne->order_detail->seller_pvc + $this->__LadingOne->order_detail->seller_cod;

        if($this->__LadingOne->order_detail->money_collect == 0){
            $noteBonus .= '[PHÁT TẬN TAY] ';
        }

        if($this->__LadingOne->fragile == 1)
            $noteBonus .= '[Hàng dễ vỡ] ';

        if($this->__LadingOne->checking == 1){
            if($this->__LadingOne->from_user_id == 69668){
                $noteBonus .= '[Được xem(coi) hàng - Không thử] ';
            }else{
                $noteBonus .= '[Được xem(coi) hàng] ';
            }
        }else{
            $noteBonus .= '[Không xem(coi) hàng] ';
        }

        switch ((int)$this->__LadingOne->service_id) {
            case 1:
                if($this->__LadingOne->from_city_id == $this->__LadingOne->to_order_address->city_id){
                    if(in_array($this->__LadingOne->from_city_id, [18,52,35])){
                        $service_code   = 'PHS';
                    }else{
                        $service_code   = ($ToLocation > 1) ? 'VBK' : 'VBK';
                    }
                }else{
                    if($this->__LadingOne->total_weight < 2000){
                        if(in_array($this->__LadingOne->to_order_address->city_id,array(18,52,35))){
                            $service_code   = ($ToLocation > 2) ? 'VBD' : 'VBD';
                        }else{
                            $service_code   = ($ToLocation > 1) ? 'VBD' : 'VBD';
                        }
                    }else{
                        if(in_array($this->__LadingOne->to_order_address->city_id,array(18,52,35))){
                            $service_code   = ($ToLocation > 2) ? 'VBK' : 'VBK';
                        }else{
                            $service_code   = ($ToLocation > 1) ? 'VBK' : 'VBK';
                        }
                    }
                }
                break;
            case 2:
                if($this->__LadingOne->from_city_id == $this->__LadingOne->to_order_address->city_id){ // nội tỉnh
                    if(in_array($this->__LadingOne->from_city_id, [18,52,35])){
                        $service_code   = 'PTN';
                    }else{
                        $service_code   = ($ToLocation > 2) ? 'VCN' : 'VCN';
                    }
                }else{ // liên tỉnh
                    if(in_array($this->__LadingOne->to_order_address->city_id,array(18,52,35))){
                        $service_code   = ($ToLocation > 2) ? 'VCN' : 'VCN';
                    }else{
                        $service_code   = ($ToLocation > 1) ? 'VCN' : 'VCN';
                    }
                }
                break;
            case 3:
                $service_code = 'VBEC';
                break;
            case 5:
                $service_code = 'VVT';
                break;
            default:
                $service_code = 'VBEX';
        }

        // Lấy địa chỉ người gửi khi gửi hàng ra bưu cục
        $FromAddress    = '';
        if($this->__LadingOne->post_office_id > 0){
            $FromAddress = 'ĐC BC: ';
        }

        $FromAddress .= (string) ($this->__LadingOne->from_address.', '.(isset($this->list_ward[$this->__LadingOne->from_ward_id]) ? $this->list_ward[$this->__LadingOne->from_ward_id].', ' : '')
            .$this->list_district[$this->__LadingOne->from_district_id].', '
            .$this->list_city[$this->__LadingOne->from_city_id]);

        if($this->__LadingOne->post_office_id > 0){
            $FromAddress .= ';ĐC Kho của kh : '.(isset($this->user->address) ? $this->user->address : '')
                .((isset($this->list_ward[$this->user->ward_id]) && $this->user->ward_id > 0) ? ', '.$this->list_ward[$this->user->ward_id] : '')
                .((isset($this->user->district_id) && $this->user->district_id > 0) ? $this->list_district[$this->user->district_id].', ' : '')
                .((isset($this->user->city_id) && $this->user->city_id > 0) ? $this->list_city[$this->user->city_id] : '');
        }

        // Create Lading
        $LadingParams = array(
            "ORDER_ID"          => (string) substr($this->__LadingOne->tracking_code,2),
            "MA_DOITAC"         => (string) "SC",
            "MA_SHOP"           => (string) $this->__LadingOne->from_address_id > 0 ? "KH".$this->__LadingOne->from_address_id : "",
            //"MA_VANDON"         => (string) $this->__LadingOne->tracking_code,
            "TEN_KHGUI"         => (string) ($this->__LadingOne->from_address_id > 0 ? $this->__LadingOne->from_order_address->user_name : $this->__LadingOne->from_user_data->fullname),
            "DIACHI_KHGUI"      => (string) $FromAddress,
            "EMAIL_KHGUI"       => (string) '',
            "TEL_KHGUI"         => (string) ($this->__LadingOne->from_address_id > 0 ? substr($this->__LadingOne->from_order_address->phone,0,30) : substr($this->__LadingOne->from_user_data->phone,0,30)),
            "TINH_KHGUI"        => (string) $this->__Location['from']['city_id'],
            "HUYEN_KHGUI"       => (string) $this->__Location['from']['district_id'],
            "PHUONGKHGUI"       => (string) $this->__Location['from']['ward_id'],
            "LATITUDE"          => (string) 0,
            "LONGITUDE"         => (string) 0,
            "TEN_KHNHAN"        => (string) mb_substr($this->__LadingOne->to_name,0,100,'utf8'),
            "DIACHI_KHNHAN"     => (string) ($this->__LadingOne->to_order_address->address.', '
                .(isset($this->list_ward[$this->__LadingOne->to_order_address->ward_id]) ? $this->list_ward[$this->__LadingOne->to_order_address->ward_id].', ' : '')
                .$this->list_district[$this->__LadingOne->to_order_address->province_id].', '
                .$this->list_city[$this->__LadingOne->to_order_address->city_id]),
            "EMAIL_KHNHAN"      => (string) '',
            "TEL_KHNHAN"        => (string) substr($this->__LadingOne->to_phone,0,30),
            "TINH_KHNHAN"       => (string) $this->__Location['to']['city_id'],
            "HUYEN_KHNHAN"      => (string) $this->__Location['to']['district_id'],
            "PHUONGKHNHAN"      => (string) $this->__Location['to']['ward_id'],
            "MOTA_SP"           => (string) $noteBonus.'SP: ' . $this->__LadingOne->product_name . ' | Mô tả: ' ,
            "TIEN_HANG"         => (int)    (int) $this->__LadingOne->total_amount,
            "LINK_WEB"          => (string) 'http://seller.shipchung.vn/#/print?code='.$this->__LadingOne->tracking_code,
            "LOAI_VANDON"       => (int)    $type_lading, //(1: Pas; 2: Pas+COD; 3: COD; 4: Thu phí đầu nhận)
            //"NGAY_DUYET"        => (string) date('d/m/Y H:i:s'),
            //"NGAY_TAO"          => (string) date('d/m/Y H:i:s'),
            "GHI_CHU"           => (string) $noteBonus,
            "MA_DV_VIETTEL"     => (string) trim($service_code), // VSK: Thường; VSC: Nhanh
            "TRONG_LUONG"       => (int)    $this->__LadingOne->total_weight,
            "MA_LOAI_HANGHOA"   => (string) "HH", // TH: Thư | HH: Hàng hóa
            "TONG_CUOC_VND"     => (int)    0,
            "PHI_COD"           => (int)    0,
            "PHI_VAS"           => (int)    0,
            "BAO_HIEM"          => (int)    ($this->__LadingOne->order_detail->sc_pbh > 0) ? round(($this->__LadingOne->total_amount * 0.01)) : 0, // bảo hiểm
            "PHU_PHI"           => (int)    0,
            "PHU_PHI_KHAC"      => (int)    0,
            "TONG_VAT"          => (int)    round(($tong_tien * 0.1)),
            "TONG_TIEN"         => (int)    $tong_tien,
            "TIEN_THU_HO"       => (int)    $this->__LadingOne->order_detail->money_collect,
            //"TRANG_THAI"        => (int)    100,
            //"MA_KHGUI"          => (string) "",
            //"MA_BUUCUC"         => (string) "",
            "SO_LUONG"          => (int)    $this->__LadingOne->total_quantity,
            "NGAY_LAY_HANG"     => (string) date('d/m/Y H:i:s'),
            //"TOKEN"             => (string) '',
        );


        //return $LadingParams;
        $soapClient = new \SoapClient("http://api.viettelpost.com.vn/VTPAPITMDT/VTPTMDT.asmx?wsdl");
        //Create Soap Header.
        $header     = new \SOAPHeader('http://viettelpost.org/', 'ServiceAuthHeader', array('Token' => (string)'B401262F9A3D09B9A5AC7AFF3DED92CC' /*$resultLogin['LoginResult']['wTokenKey']*/));
        //set the Headers of Soap Client.
        $soapClient->__setSoapHeaders($header);
        try{
            $result =  $soapClient->InsertOrder2016($LadingParams);
        }catch (SoapFault $exception){
            //or any other handling you like
            var_dump(get_class($exception));
            var_dump($exception);
        }
        // data update Log
        $updateLog = array(
            'params' => $LadingParams,
            'result' => json_encode($result),
        );


        if(empty($result))
        {
            // Ghi log
            $updateLog['error_code']    = 'FAIL_API_CREATE_ORDER';
            $updateLog['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Tạo vận đơn sang Viettel Post bị lỗi');
        }
        elseif($result->InsertOrder2016Result == 'SUCCESS' || $result->InsertOrder2016Result == 'EXIST_ORDER' || strpos($result->InsertOrder2016Result,"Vận đơn đã tồn tại") !==False || @preg_match("/unique/i", $result->InsertOrder2016Result)) {
            // data update Log
            $updateLog['error_code']    = 'SUCCESS';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);

            // Cập nhật trạng thái vận đơn
            $dataUpdate['courier_tracking_code']    =   $this->__LadingOne->tracking_code;
            $dataUpdate['time_approve']             =   $this->time();
            $dataUpdate['status']                   =   30;
            return array('ERROR' => "SUCCESS", 'update' => $dataUpdate); // 00 -> SUCCESS
        }
        elseif($result->InsertOrder2016Result == 'FAIL')
        {
            // Ghi log
            $updateLog['error_code']    = 'FAIL_CREATE_ORDER';
            $updateLog['messenger']     = $result->InsertOrder2016Result;
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> $result->InsertOrder2016Result);
        }
        elseif(isset($result->faultstring) && $result->faultstring == 'Token invalid.')
        {
            // Ghi log
            $updateLog['error_code']    = 'FAIL_TOKEN';
            $updateLog['messenger']     = 'Lỗi token đăng nhập API';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            //$this->_login(true);
            return array('ERROR'=> 'FAIL', 'MSG'=> $result);
        }else{
            var_dump($this->__LadingOne->tracking_code);
            dd($result);
        }

        // Ghi log
        $updateLog['error_code']    = 'FAIL_OTHER_CREATE_ORDER';
        $updateLog['messenger']     = 'Lỗi gì đó mà không hiểu.';
        \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
        return array('ERROR'=> 'FAIL', 'MSG'=> 'Lỗi gì đó mà không thể hiểu nổi.');

    }

    public function _courier_njv(){
        $this->log_id = \LMongo::collection($this->logFile)->insert(array('courier' => 14,'sc_code' => $this->__LadingOne->tracking_code,'time_create' => $this->time()));
        // Login VTP
        $resultLogin = $this->_login_ninjavan();
        if(isset($resultLogin['ERROR']) && $resultLogin['ERROR'] == 'FAIL')
        {
            return $resultLogin;
        }

        $noteBonus  = '';

        if($this->__LadingOne->to_country_id != 237){
            $noteBonus .= '[ĐƠN QUỐC TẾ] ';
        }

        if($this->__LadingOne->order_detail->money_collect == 0){
            $noteBonus .= '[PHÁT TẬN TAY] ';
        }

        if($this->__LadingOne->fragile == 1)
            $noteBonus .= '[Hàng dễ vỡ] ';

        if($this->__LadingOne->checking == 1){
            if($this->__LadingOne->from_user_id == 69668){
                $noteBonus .= '[Được xem(coi) hàng - Không thử] ';
            }else{
                $noteBonus .= '[Được xem(coi) hàng] ';
            }
        }else{
            $noteBonus .= '[Không xem(coi) hàng] ';
        }


        // Lấy địa chỉ người gửi khi gửi hàng ra bưu cục
        $FromAddress = (string) $this->__LadingOne->from_address;

        // Create Lading
        $LadingParams = array(
            "from_postcode"             => 888888,
            "from_address1"             => $FromAddress,
            "from_address2"             => (string) $this->list_district[$this->__LadingOne->from_district_id],
            "from_city"                 => (string) $this->list_city[$this->__LadingOne->from_city_id],
            "from_country"              => "VN",
            "from_email"                => "hotro@shipchung.vn",//(string)$this->__LadingOne->from_user_data->email,
            "from_name"                 => (string) ($this->__LadingOne->from_address_id > 0 ? $this->__LadingOne->from_order_address->user_name : $this->__LadingOne->from_user_data->fullname),
            "from_contact"              => (string) ($this->__LadingOne->from_address_id > 0 ? substr($this->__LadingOne->from_order_address->phone,0,30) : substr($this->__LadingOne->from_user_data->phone,0,30)),

            "to_postcode"               => 888888,
            "to_address1"               => (string) $this->__LadingOne->to_order_address->address,
            "to_address2"               => (string) $this->list_district[$this->__LadingOne->to_order_address->province_id],
            "to_city"                   => (string) $this->list_city[$this->__LadingOne->to_order_address->city_id],
            "to_country"                => "VN",
            "to_email"                  => "ninjavan@ninjavan.co", //(string) $this->__LadingOne->to_email,
            "to_name"                   => (string) mb_substr($this->__LadingOne->to_name,0,100,'utf8'),
            "to_contact"                => (string) substr($this->__LadingOne->to_phone,0,30),
            "delivery_date"             => (string) ($this->__LadingOne->service_id == 2 ? date('Y-m-d') : date('Y-m-d', strtotime(' +1 day'))),
            "pickup_date"               => (string) date('Y-m-d'),
            "weekend"                   => true,
            "staging"                   => false,
            "pickup_timewindow_id"      => -2,
            "delivery_timewindow_id"    => -1,
            "max_delivery_days"         => ($this->__LadingOne->service_id == 2 ? 0 : 1),
            "cod_goods"                 => (int) $this->__LadingOne->order_detail->money_collect,
            "instruction"               => (string) $noteBonus,
            "tracking_ref_no"           => (string) substr($this->__LadingOne->tracking_code,2),
            "shipper_order_ref_no"      => (string) substr($this->__LadingOne->tracking_code,2),
            "type"                      => "C2C",
            "parcels"                   => array(array(
                    "parcel_size_id"  => 1,
                    "volume"          => 4000,
                    "weight"          => (float)($this->__LadingOne->total_weight)/1000
            ))
        );

        $data_string = json_encode($LadingParams);
        $ch = curl_init('https://api.ninjavan.co/vn/2.0/orders');


        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: '.'Bearer '.$resultLogin['access_token'],
            'Content-Type: application/json'));
        $result = curl_exec($ch);
        $info   = curl_getinfo($ch);

        // data update Log
        $updateLog = array(
            'params' => $LadingParams,
            'result' => $result,
        );

        $result = json_decode($result,1);

        if($info["http_code"] !=200 && $info["http_code"] !=202)
        {
            // Ghi log
            $updateLog['error_code']    = 'FAIL_API_CREATE_ORDER';
            $updateLog['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Tạo vận đơn sang Ninjavan lỗi');
        }
        elseif($info["http_code"] == 200 ||  $info["http_code"] == 202) {
            // data update Log
            $updateLog['error_code']    = 'SUCCESS';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);

            // Cập nhật trạng thái vận đơn
            $dataUpdate['courier_tracking_code']    =   $this->__LadingOne->tracking_code;
            $dataUpdate['time_approve']             =   time();
            $dataUpdate['status']                   =   30;
            return array('ERROR' => "SUCCESS", 'update' => $dataUpdate); // 00 -> SUCCESS
        }
       

        // Ghi log
        $updateLog['error_code']    = 'FAIL_OTHER_CREATE_ORDER';
        $updateLog['messenger']     = 'Lỗi gì đó mà không hiểu.';
        \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
        return array('ERROR'=> 'FAIL', 'MSG'=> 'Lỗi gì đó mà không thể hiểu nổi.');

    }

    public function _courier_vnp(){
        $resultLogin = $this->_login_vnp();

        if(isset($resultLogin['ERROR']) && $resultLogin['ERROR'] == 'FAIL')
        {
            return $resultLogin;
        }

        $this->log_id = \LMongo::collection($this->logFile)->insert(array('courier' => $this->__LadingOne->courier_id,'sc_code' => $this->__LadingOne->tracking_code,'time_create' => $this->time()));

        // Biuld data
        $noteBonus = $service_code = '';

        // Loại vận đơn
        if($this->__LadingOne->order_detail->money_collect > 0)
            $type_lading = 1; // cod
        else
            $type_lading = 2; //pas

        $tong_tien  = $this->__LadingOne->order_detail->seller_pvc + $this->__LadingOne->order_detail->seller_cod;

        if($this->__LadingOne->fragile == 1)
            $noteBonus .= '[Hàng dễ vỡ] ';

        if($this->__LadingOne->checking == 1){
            if($this->__LadingOne->from_user_id == 69668){
                $noteBonus .= '[Được xem(coi) hàng - Không thử] ';
            }else{
                $noteBonus .= '[Được xem(coi) hàng] ';
            }
        }else{
            $noteBonus .= '[Không xem(coi) hàng] ';
        }


        $LadingParams = array(
            //"Ma"                        => 'SC123456789',
            "SoDonHang"                 => (string) $this->__LadingOne->tracking_code,
            "HoTenNguoiGui"             => (string) (string) ($this->__LadingOne->from_address_id > 0 ? $this->__LadingOne->from_order_address->user_name : $this->__LadingOne->from_user_data->fullname),
            "DiaChiNguoiGui"            => (string) $this->__LadingOne->from_address .(isset($this->__Location['from']['ward_name']) ? ', '.$this->__Location['from']['ward_name'] : ''),
            "NoiDungHang"               => (string) $this->__LadingOne->product_name.' Số lượng: '.$this->__LadingOne->total_quantity,
            "DienThoaiNguoiGui"         => (string) ($this->__LadingOne->from_address_id > 0 ? substr($this->__LadingOne->from_order_address->phone,0,30) : substr($this->__LadingOne->from_user_data->phone,0,30)),
            "TenKhoHang"                => (string) ($this->__LadingOne->from_address_id > 0 ? $this->__LadingOne->from_order_address->name.' - '.$this->__LadingOne->from_order_address->user_name : $this->__LadingOne->from_user_data->fullname),
            "DiaChiKhoHang"             => (string) ($this->__LadingOne->from_address.', '
                .(isset($this->__Location['from']['ward_name']) ? $this->__Location['from']['ward_name'].', ' : '')
                .$this->__Location['from']['district_name'].', '
                .$this->__Location['from']['city_name']),
            "DienThoaiLienHeKhoHang"    => (string) ($this->__LadingOne->from_address_id > 0 ? substr($this->__LadingOne->from_order_address->phone,0,30) : substr($this->__LadingOne->from_user_data->phone,0,30)),
            "HoTenNguoiNhan"            => (string) $this->__LadingOne->to_name,
            "DiaChiNguoiNhan"           => (string) ($this->__LadingOne->to_order_address->address.', '
                .(isset($this->__Location['to']['ward_name']) ? $this->__Location['to']['ward_name'].', ' : '')
                .$this->__Location['to']['district_name'].', '
                .$this->__Location['to']['city_name']),
            "DienThoaiNguoiNhan"        => (string) substr($this->__LadingOne->to_phone,0,30),
            "TongTrongLuong"            => (int)    $this->__LadingOne->total_weight,
            //"TongCuoc"                  => 100000,
            "TongTienPhaiThu"           => (int)    $this->__LadingOne->order_detail->money_collect,
            "NgayGiao"                  => (string) date('m/d/Y'),
            "TinhThanh"                 => (string) $this->__Location['to']['city_name'],
            "QuanHuyen"                 => (string) $this->__Location['to']['district_name'],
            "PhuongThuc"                => (int) $type_lading,
            "MaPhien"                   => (string)$resultLogin
        );

        $soapClient = new \SoapClient("http://buudienhanoi.com.vn/Nhanh/BDHNNhanh.asmx?wsdl");
        $result = $soapClient->ThemDonHangNhanh(['rDonHang' => $LadingParams]);

        $updateLog = array(
            'params' => $LadingParams,
            'result' => json_encode($result),
        );

        if(empty($result))
        {
            // Ghi log
            $updateLog['error_code']    = 'FAIL_API_CREATE_ORDER';
            $updateLog['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Tạo vận đơn sang Vietnam Post bị lỗi');
        }
        elseif(in_array((int)$result->ThemDonHangNhanhResult, [20,99])) {
            // data update Log
            $updateLog['error_code']    = 'SUCCESS';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);

            // Cập nhật trạng thái vận đơn
            $dataUpdate['courier_tracking_code']    =   $this->__LadingOne->tracking_code;
            $dataUpdate['time_approve']             =   $this->time();
            $dataUpdate['status']                   =   30;
            return array('ERROR' => "SUCCESS", 'update' => $dataUpdate); // 00 -> SUCCESS
        }
        else
        {
            // Ghi log
            $updateLog['error_code']    = 'FAIL_CREATE_ORDER';
            $updateLog['messenger']     = $result->ThemDonHangNhanhResult;
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> $result->ThemDonHangNhanhResult);
        }
    }

    // Giao Hang Tiet Kiem
    private function _courier_gtk(){
        $this->log_id = \LMongo::collection($this->logFile)->insert(array('courier' => 6,'sc_code' => $this->__LadingOne->tracking_code,'time_create' => $this->time()));
        //

        $noteBonus = '';
        $updateLog = array();

        if(!$this->__LadingOne)
            return false;

        if($this->__LadingOne->checking == 1){
            if($this->__LadingOne->from_user_id == 69668){
                $noteBonus .= '[Được xem(coi) hàng - Không thử] ';
            }else{
                $noteBonus .= '[Được xem(coi) hàng] ';
            }
        }else{
            $noteBonus .= '[Không được xem(coi) hàng] ';
        }


        if($this->__Location['location'] > 1){
            $service_id = 3;
        }
        else{
            $service_id = $this->__LadingOne->service_id;
        }

        // Create Lading
        $LadingParams = array(
            'token' =>'5356d05Dc7fA6463Fc2E0Fa35f5233224D84c7f5',
            'orders' =>array(
                array(
                    'products' =>array(
                        array(
                            'name'      => $this->__LadingOne->product_name,
                            'price'     => $this->__LadingOne->total_amount,
                            'weight'    => $this->__LadingOne->total_weight/1000,
                            'quantity'  => $this->__LadingOne->total_quantity
                        )
                    ),
                    'order' =>array(
                        'id'            => $this->__LadingOne->tracking_code,
                        'name'          => $this->__LadingOne->to_name,
                        'address'       => $this->__LadingOne->to_order_address->address,
                        'province'      => $this->__Location['to']['city_name'],
                        'district'      => $this->__Location['to']['district_name'],
                        'ward'          => isset($this->__Location['to']['ward_name']) ? $this->__Location['to']['ward_name'] : '',
                        'street'        => '',
                        'tel'           => $this->__LadingOne->to_phone,
                        'email'         => '',
                        'pick_name'     => (string) ($this->__LadingOne->from_address_id > 0 ? $this->__LadingOne->from_order_address->user_name : $this->__LadingOne->from_user_data->fullname),
                        'pick_money'    => (int)$this->__LadingOne->order_detail->money_collect, // 0: Pas | >0 : Cod
                        'pick_address'  => $this->__LadingOne->from_address,
                        'pick_province' => $this->__Location['from']['city_name'],
                        'pick_district' => $this->__Location['from']['district_name'],
                        'pick_ward'     => isset($this->__Location['from']['ward_name']) ? $this->__Location['from']['ward_name'] : '',
                        'pick_street'   => '',
                        'pick_tel'      => ($this->__LadingOne->from_address_id > 0 ? $this->__LadingOne->from_order_address->phone : $this->__LadingOne->from_user_data->phone),
                        'pick_email'    => '',
                        'type'          => $service_id,
                        'note'          => $noteBonus.'SP: ' . $this->__LadingOne->product_name . ' | Mô tả: ' . $this->__LadingOne->tracking_code,
                        'is_freeship'   => 1,
                        'is_express'    => 0,
                    )
                )
            )
        );

        //return $LadingParams;

        $respond    = \cURL::post('http://services.giaohangtietkiem.vn:8080/services/orders/add',$LadingParams);

        // data update Log
        $updateLog = array(
            'params' => $LadingParams,
            'result' => (string)$respond,
        );

        if(!$respond){
            // Ghi log
            $updateLog['error_code']    = 'FAIL_API_CREATE_ORDER';
            $updateLog['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Duyệt không thành công, Chẳng biết lỗi gì!');
        }

        $decode = json_decode($respond,true);
        //print_r($decode);
        if($decode['success'] == false && !isset($decode['error_orders'][0]['exists_order']))
        {
            // Ghi log
            $updateLog['error_code']    = 'FAIL_CREATE_ORDER';
            $updateLog['messenger']     = $decode['message'];
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> $decode['message']);
        }

        if(!empty($decode['error_orders']) && !isset($decode['error_orders'][0]['exists_order']))
        {
            // Ghi log
            $Message = (isset($decode['validates'][0]['messages'][0]) && isset($decode['validates'][0]['messages'][0][0])) ? $decode['validates'][0]['messages'][0][0] : $decode['error_orders'][0]['messages'];
            $updateLog['error_code']    = 'FAIL_CREATE_ORDER';
            $updateLog['messenger']     = $Message;
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> $Message);
        }

        if(isset($decode['error_orders'][0]['exists_order']))
        {
            // Cập nhật trạng thái vận đơn
            if($this->__LadingOne->status == 21){
                $dataUpdate['time_approve']         = $this->time();
            }

            if(isset($decode['error_orders'][0]['exists_order']['order_id'])){
                $dataUpdate['courier_tracking_code']    = $decode['error_orders'][0]['exists_order']['order_id'];
            }

            $dataUpdate['status']                   = 30;
            //

            $updateLog['error_code']    = 'SUCCESS';
            $updateLog['messenger']     = 'Exist Order';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);

            return array('ERROR' => "SUCCESS", 'update' => $dataUpdate); // 00 -> SUCCESS
        }

        // Cập nhật trạng thái vận đơn
        if($this->__LadingOne->status == 21){
            $dataUpdate['time_approve']         = $this->time();
        }

        $dataUpdate['courier_tracking_code']    = $decode['success_orders'][0]['order_id'];
        $dataUpdate['status']                   = 30;

        $updateLog['error_code']                = 'SUCCESS';
        \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);

        return array('ERROR' => "SUCCESS", 'update' => $dataUpdate); // 00 -> SUCCESS
    }

    private function _courier_gts(){
        $this->log_id = \LMongo::collection($this->logFile)->insert(array('courier' => 9,'sc_code' => $this->__LadingOne->tracking_code,'time_create' => $this->time()));
        //

        $noteBonus = '';
        $updateLog = array();

        if(!$this->__LadingOne)
            return false;

        if($this->__LadingOne->checking == 1){
            if($this->__LadingOne->from_user_id == 69668){
                $noteBonus .= '[Được xem(coi) hàng - Không thử] ';
            }else{
                $noteBonus .= '[Được xem(coi) hàng] ';
            }
        }else{
            $noteBonus .= '[Không được xem(coi) hàng] ';
        }

        // Login Api
        $Token = $this->_login_gts();
        $this->_getLocation();

        // Create Lading
        $LadingParams = array(
            "OrderNumber"           => (string) $this->__LadingOne->tracking_code,
            "TrackingNumber"        => (string) $this->__LadingOne->tracking_code,
            "ServiceCode"           => $this->__LadingOne->service_id == 2 ? 'CODN' : 'CODTK',
            "ShipperName"           => (string) $this->__LadingOne->from_address_id > 0 ? $this->__LadingOne->from_order_address->user_name : $this->__LadingOne->from_user_data->fullname,
            "ShipperPhone"          => (string) $this->__LadingOne->from_address_id > 0 ? $this->__LadingOne->from_order_address->phone : $this->__LadingOne->from_user_data->phone,
            "PickupAddress"         => (string) ($this->__LadingOne->from_address.', '
                .(isset($this->__Location['from']['ward_name']) ? $this->__Location['from']['ward_name'].', ' : '')
                .$this->__Location['from']['district_name'].', '
                .$this->__Location['from']['city_name']),

            "ConsigneeName"         => (string) $this->__LadingOne->to_name,
            "ConsigneePhone"        => (string) $this->__LadingOne->to_phone,
            "ConsigneeAddress"      => (string) ($this->__LadingOne->to_order_address->address.', '
                .(isset($this->__Location['to']['ward_name']) ? $this->__Location['to']['ward_name'].', ' : '')
                .$this->__Location['to']['district_name'].', '
                .$this->__Location['to']['city_name']),

            "PickupZipCode"         => (int) $this->__Location['from']['district_id'],
            "ConsigneeZipCode"      => (int) $this->__Location['to']['district_id'],
            "Description"           => (string) ($noteBonus.'SP: ' . $this->__LadingOne->product_name . ' | Mô tả: ' ),
            "MoneyCollectAmount"    => (int) $this->__LadingOne->order_detail->money_collect,
            "Weight"                => (int) $this->__LadingOne->total_weight,
            "Volume"                => 0,
            "UseCoDService"         => $this->__LadingOne->order_detail->money_collect > 0 ? 1 : 2,
            "UseInsuranceService"   => $this->__LadingOne->order_detail->sc_pbh > 0 ? 1 : 2,
            "UseCoChecking"         => $this->__LadingOne->checking == 1 ? 1 : 2,
        );


        $respond = (string)\cURL::newRequest('post', 'http://epost.goldtimes.vn/epost-api/order/create', $LadingParams)
            ->setHeader('Authorization', 'Bearer '.$Token->access_token)
            ->send();

        // data update Log
        $updateLog = array(
            'params' => $LadingParams,
            'result' => $respond,
        );

        if(!$respond){
            // Ghi log
            $updateLog['error_code']    = 'FAIL_API_CREATE_ORDER';
            $updateLog['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Duyệt không thành công, Chẳng biết lỗi gì!');
        }

        $result = json_decode($respond);
        //return $result;
        if(in_array($result->error,array('00','01'))){

            if($this->__LadingOne->status == 21){
                $dataUpdate['time_approve']         = $this->time();
            }

            $dataUpdate['courier_tracking_code']    = $this->__LadingOne->tracking_code;
            $dataUpdate['status']                   = 30;
            $updateLog['error_code']                = 'SUCCESS';

            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR' => "SUCCESS", 'update' => $dataUpdate); // 00 -> SUCCESS
        }

        // Ghi log
        $updateLog['error_code']    = 'FAIL_CREATE_ORDER';
        $updateLog['messenger']     = $result->error.'|'.$result->error_message;
        \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
        return array('ERROR'=> 'FAIL', 'MSG'=> $result->error.'|'.$result->error_message);
        //
    }

    private function IsInternationalLading($lading){
        if( $lading->to_country_id != 237){
            return true;
        }
        return false;
    }

    
    // Ems
    public function _courier_ems(){
        $noteBonus = '';

        if(!$this->__LadingOne)
            return false;

        $this->log_id = \LMongo::collection($this->logFile)->insert(array('courier' => 8,'sc_code' => $this->__LadingOne->tracking_code,'time_create' => time()));
        //
        $updateLog = array();


        if($this->__LadingOne->checking == 1){
            if($this->__LadingOne->from_user_id == 69668){
                $noteBonus .= '[Được xem(coi) hàng - Không thử] ';
            }else{
                $noteBonus .= '[Được xem(coi) hàng] ';
            }
        }else{
            $noteBonus .= '[Không được xem(coi) hàng] ';
        }
       $receiver_address = (string) ($this->__LadingOne->to_order_address->address.', '
                .(isset($this->list_ward[$this->__LadingOne->to_order_address->ward_id]) ? $this->list_ward[$this->__LadingOne->to_order_address->ward_id].', ' : '')
                .(isset($this->list_district[$this->__LadingOne->to_order_address->province_id]) ? $this->list_district[$this->__LadingOne->to_order_address->province_id].', ' : '')
                .(isset($this->list_city[$this->__LadingOne->to_order_address->city_id]) ? $this->list_city[$this->__LadingOne->to_order_address->city_id] : ''));
       $sender_address = (string) ($this->__LadingOne->from_address.', '.(isset($this->list_ward[$this->__LadingOne->from_ward_id]) ? $this->list_ward[$this->__LadingOne->from_ward_id].', ' : '')
                .$this->list_district[$this->__LadingOne->from_district_id].', '
                .$this->list_city[$this->__LadingOne->from_city_id]);
    

        // Create Lading
        $LadingParams = array(
            "ORDER_CODE"            => (string) $this->__LadingOne->tracking_code,
            "PRODUCT_CODE"          => "",
            "PRODUCT_NAME"          => (string) mb_substr($this->__LadingOne->product_name,0,300,'utf8'),
            "PRODUCT_DESCRIPTION"   => str_replace("|"," - ",($noteBonus.' SP: ' . $this->__LadingOne->product_name)),
            "PRODUCT_QUANTITY"      => (int)$this->__LadingOne->total_quantity,
            "PRODUCT_VALUE"         => (int)$this->__LadingOne->total_amount,
            "STORE_ID"              => (int)$this->__LadingOne->from_address_id,
            "TOTAL_AMOUNT"          => (int) $this->__LadingOne->order_detail->money_collect,
            "COD"                   => $this->__LadingOne->order_detail->money_collect > 0 ? 1 : 2,
            "WEIGHT"                => (int) $this->__LadingOne->total_weight,

            "TO_COUNTRY"            => 'VN',
            "RECEIVER_NAME"         => (string) $this->__LadingOne->to_name,
            "RECEIVER_ADDRESS"      => str_replace("|"," - ",$receiver_address),
            "RECEIVER_PHONE"        => (string) substr($this->__LadingOne->to_phone,0,30),
            "RECEIVER_PROVINCE_ID"  => !empty($this->__Location['to']['city_id']) ? (string)$this->__Location['to']['city_id'] : "9",
            "RECEIVER_DISTRICT_ID"  => !empty($this->__Location['to']['district_id']) ? (string)$this->__Location['to']['district_id'] : "9",
            "RECEIVER_WARD_ID"      => 0,
            "SENDER_CODE"           => $this->__LadingOne->from_city_id == 18 ? 110100120301 : 110100120302,

            "SENDER_NAME"           => (string) ($this->__LadingOne->from_address_id > 0 ? $this->__LadingOne->from_order_address->user_name : $this->__LadingOne->from_user_data->fullname),
            "SENDER_ADDRESS"        => str_replace("|"," - ",$sender_address),
            "SENDER_PHONE"          => (string) ($this->__LadingOne->from_address_id > 0 ? substr($this->__LadingOne->from_order_address->phone,0,30) : substr($this->__LadingOne->from_user_data->phone,0,30)),
            "SENDER_PROVINCE_ID"    => (string) $this->__Location['from']['city_id'],
            "SENDER_DISTRICT_ID"    => (string) $this->__Location['from']['district_id'],
            "SERVICE_TYPE"          => 0
        );

        $_updateLog = [];
    
        $_updateLog['is_international'] = $this->IsInternationalLading($this->__LadingOne);

        if($this->IsInternationalLading($this->__LadingOne)){
            $CountryCode = CountryModel::getCountryCodeById($this->__LadingOne->to_country_id);
            $_updateLog['country_code'] = $CountryCode;

            if(!empty($CountryCode)){
                $LadingParams["TO_COUNTRY"] = $CountryCode;
            }
        }


        $respond    = \cURL::post('http://222.255.250.245:550/api/Shipment?key=1333dfab-4585-40f1-8508-c19b0ecd52a4', $LadingParams);

        // data update Log
        $updateLog = array(
            'params' => $LadingParams,
            'result' => $respond->body,
            '_updateLog' => $_updateLog
        );

        if(!$respond){
            // Ghi log
            $updateLog['error_code']    = 'FAIL_API_CREATE_ORDER';
            $updateLog['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Duyệt không thành công, Chẳng biết lỗi gì!');
        }

        $result = json_decode($respond->body,1);

        if(in_array($result['Code'],["00","01"])){
            if(isset($result['Value']) && !empty($result['Value'])){ // Có mã EMS  mới thành công
                $dataUpdate['courier_tracking_code']    = $result['Value'];

                if($this->__LadingOne->status == 21){
                    $dataUpdate['time_approve']         = $this->time();
                }

                $dataUpdate['status']                   = 30;
                $updateLog['error_code']                = 'SUCCESS';

                \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
                return array('ERROR' => "SUCCESS", 'update' => $dataUpdate); // 00 -> SUCCESS

            }else{
                $updateLog['error_code']    = 'EMPTY_EMS_CODE';
                $updateLog['messenger']     = 'Chưa nhận được mã EMS';
                \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
                return array('ERROR'=> 'FAIL', 'MSG'=> 'Duyệt không thành công, thử lại!');
            }
        }

        // Ghi log
        $updateLog['error_code']    = 'FAIL_CREATE_ORDER';
        $updateLog['messenger']     = isset($result['status']) ? $result['status'] : $result;
        \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
        return array('ERROR'=> 'FAIL', 'MSG'=> $updateLog['messenger']);
    }


    //Dev
    private function _courier_ems_dev(){ return 1;
        $noteBonus = '';

        if(!$this->__LadingOne)
            return false;

        $this->log_id = \LMongo::collection($this->logFile)->insert(array('courier' => 8,'sc_code' => $this->__LadingOne->tracking_code,'time_create' => $this->time()));
        //
        $updateLog = array();


        if($this->__LadingOne->checking == 1){
            $noteBonus .= '[Được xem(coi) hàng] ';
        }else{
            $noteBonus .= '[Không được xem(coi) hàng] ';
        }

        // Create Lading
        $LadingParams = array(
            "TokenKey"              => "shipchung@123",
            "OrderNumber"           => (string) $this->__LadingOne->tracking_code,
            "TrackingNumber"        => (string) $this->__LadingOne->tracking_code,
            "ServiceCode"           => 2,
            "ShipperName"           => (string) $this->__LadingOne->from_address_id > 0 ? $this->__LadingOne->from_order_address->user_name : $this->__LadingOne->from_user_data->fullname,
            "ShipperPhone"          => (string) $this->__LadingOne->from_address_id > 0 ? $this->__LadingOne->from_order_address->phone : $this->__LadingOne->from_user_data->phone,
            "PickupAddress"         => (string) ($this->__LadingOne->from_address.', '
                .(isset($this->__Location['from']['ward_name']) ? $this->__Location['from']['ward_name'].', ' : '')
                .$this->__Location['from']['district_name'].', '
                .$this->__Location['from']['city_name']),

            "ConsigneeName"         => (string) $this->__LadingOne->to_name,
            "ConsigneePhone"        => (string) $this->__LadingOne->to_phone,
            "ConsigneeAddress"      => (string) ($this->__LadingOne->to_order_address->address.', '
                .(isset($this->__Location['to']['ward_name']) ? $this->__Location['to']['ward_name'].', ' : '')
                .$this->__Location['to']['district_name'].', '
                .$this->__Location['to']['city_name']),

            "PickupZipCode"         => (int) $this->__Location['from']['city_code'],
            "ConsigneeZipCode"      => (int) $this->__Location['to']['city_code'],

            "DistrictCode"          => $this->__Location['from']['city_code'],
            "Product"               => (string) mb_substr($this->__LadingOne->product_name,0,300,'utf8'),

            "Description"           => (string) ($noteBonus.'SP: ' . $this->__LadingOne->product_name . ' | Mô tả: ' ),
            "MoneyCollectAmount"    => (int) $this->__LadingOne->order_detail->money_collect,
            "Weight"                => (int) $this->__LadingOne->total_weight,
            "Volume"                => 0,
            "UseCoDService"         => $this->__LadingOne->order_detail->money_collect > 0 ? 1 : 2,
            "UseInsuranceService"   => $this->__LadingOne->order_detail->sc_pbh > 0 ? 1 : 2,
            "UseCoChecking"         => $this->__LadingOne->checking == 1 ? 1 : 2,
            "AddressCode"           => (string)substr(md5($this->__LadingOne->from_address),-6),
        );

        $soapClient = new \SoapClient("http://api.ems.com.vn/api/ems_partner.asmx?wsdl");
        $respond    = $soapClient->create(['CreateOrder' => json_encode($LadingParams)]);

        // data update Log
        $updateLog = array(
            'params' => $LadingParams,
            'result' => $respond->createResult,
        );

        if(!$respond->createResult){
            // Ghi log
            $updateLog['error_code']    = 'FAIL_API_CREATE_ORDER';
            $updateLog['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Duyệt không thành công, Chẳng biết lỗi gì!');
        }

        $result = json_decode($respond->createResult,1);

        if(in_array($result['error'],array('00','01'))){
            if(isset($result['data']['CheckingNumber']) && !empty($result['data']['CheckingNumber'])){ // Có mã EMS  mới thành công
                $dataUpdate['courier_tracking_code']    = $result['data']['CheckingNumber'];

                if($this->__LadingOne->status == 21){
                    $dataUpdate['time_approve']         = $this->time();
                }

                $dataUpdate['status']                   = 30;
                $updateLog['error_code']                = 'SUCCESS';

                \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
                return array('ERROR' => "SUCCESS", 'update' => $dataUpdate); // 00 -> SUCCESS

            }else{
                $updateLog['error_code']    = 'EMPTY_EMS_CODE';
                $updateLog['messenger']     = 'Chưa nhận được mã EMS';
                \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
                return array('ERROR'=> 'FAIL', 'MSG'=> 'Duyệt không thành công, thử lại!');
            }
        }

        // Ghi log
        $updateLog['error_code']    = 'FAIL_CREATE_ORDER';
        $updateLog['messenger']     = $result['error'].'|'.$result['error_message'];
        \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
        return array('ERROR'=> 'FAIL', 'MSG'=> $result['error'].'|'.$result['error_message']);
    }

    //
    private function _courier_ttc(){
        if(!$this->__LadingOne)
            return false;

        if(!isset($this->__Location['from']) || !isset($this->__Location['to'])){
            return false;
        }

        $this->log_id = \LMongo::collection($this->logFile)->insert(array('courier' => 11,'sc_code' => $this->__LadingOne->tracking_code,'time_create' => $this->time()));
        /*$Bonus = '';
        if($this->__LadingOne->checking == 1){
            $Bonus = '[Đồng kiểm hàng] ';
        }
        if($this->__LadingOne->fragile == 1){
            $Bonus .= '[Hàng dễ vỡ] ';
        }*/

        //Service 0205 : chuyển phát tiết kiệm
        //0201 : chuyển phát nhanh

        $noteBonus      = '';
        if($this->__LadingOne->fragile == 1)
            $noteBonus .= '[Hàng dễ vỡ] ';

        if($this->__LadingOne->checking == 1){
            if($this->__LadingOne->from_user_id == 69668){
                $noteBonus .= '[Được xem(coi) hàng - Không thử] ';
            }else{
                $noteBonus .= '[Được xem(coi) hàng] ';
            }
        }else{
            $noteBonus .= '[Không xem(coi) hàng] ';
        }
        // VN
        $token_key                  = 'UzAjiyTLhXE5GfyuHyUxMw==';
        $province_area_code         = (string)$this->__Location['to']['city_id'];
        $district_area_code         = (string)$this->__Location['to']['district_id'];
        // QTe
        if(in_array($this->__LadingOne->service_id, [8,9])){
            $token_key              = "5RpBUtiTlkPoyf72pBctvw==";
            $province_area_code     = (string)$this->__Location['to']['country_id'];
            $district_area_code     = "";

        }

        // Create Lading
        $LadingParams =
            array(
                'order_number'          => (string) $this->__LadingOne->tracking_code,
                'waybill_number'        => (string) $this->__LadingOne->tracking_code,

                'no_packs'              => (string) 1,
                'package_weight'        => (string)($this->__LadingOne->total_weight/1000),
                'is_wood_pack'          => (string)'N',
                'cod'                   => (string)$this->__LadingOne->order_detail->money_collect,
                'service_type'          => (string)$this->__LadingOne->service_id == 1 ? '0204' : '0201',

                'zipcode'               => !empty($this->__LadingOne->to_order_address->zip_code) ? $this->__LadingOne->to_order_address->zip_code : "",

                'sender_address'        => [
                    'full_address'              => (string) $this->__LadingOne->from_address.', '
                        .(isset($this->list_ward[$this->__LadingOne->from_ward_id]) ? $this->list_ward[$this->__LadingOne->from_ward_id].', ' : ''),
                    'province_area_code'        => (string)$this->__Location['from']['city_id'],
                    'district_area_code'        => (string)$this->__Location['from']['district_id'],
                    'ward_area_code'            => (string)$this->__Location['from']['ward_id'],
                    'contact_phone'             => (string)($this->__LadingOne->from_address_id > 0 ? substr($this->__LadingOne->from_order_address->phone,0,30) : substr($this->__LadingOne->from_user_data->phone,0,30)),
                    'contact_name'              => (string)($this->__LadingOne->from_address_id > 0 ? $this->__LadingOne->from_order_address->user_name : $this->__LadingOne->from_user_data->fullname),
                ],
                //'shipping_address'      => [
                'receiver_address' => [
                    'full_address'              => (string) $this->__LadingOne->to_order_address->address,
                    'province_area_code'        => (string)$province_area_code,
                    'district_area_code'        => (string)$district_area_code,
                    //'ward_area_code'            => (string)$this->__Location['to']['ward_id'],
                    'contact_phone'             => (string) substr($this->__LadingOne->to_phone,0,30),
                    'contact_name'              => (string) mb_substr($this->__LadingOne->to_name,0,100,'utf8')
                ],
                'orderItem'             => [[
                    'product_name'      => (string) mb_substr($noteBonus . $this->__LadingOne->product_name,0,300,'utf8'),
                    'package_weight'    => (string)($this->__LadingOne->total_weight/1000),
                    'package_dimension' => (string)'0x0x0',
                    'grand_total'       => (string)$this->__LadingOne->total_amount,
                    'payment_type'      => (string)$this->__LadingOne->order_detail->money_collect > 0 ? 'COD' : '',
                    'pack_number'       => (string)'0',
                    'is_wood_product'   => (string)'N'
                ]],
                'token_key'             => (string)$token_key

            );

        $respond    = \cURL::jsonPost('http://gw.kerryexpress.com.vn/api/WS001PostNewOrderInfor',$LadingParams);
        // data update Log
        $updateLog = array(
            'params' => $LadingParams,
            'result' => $respond->body,
        );
        
        
        if(!$respond->body){
            // Ghi log
            $updateLog['error_code']    = 'FAIL_API_CREATE_ORDER';
            $updateLog['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Duyệt không thành công, Chẳng biết lỗi gì!');
        }

        $result = json_decode($respond->body,1);

        if(in_array($result['status'],['succeed','update','success'])){
            if($this->__LadingOne->status == 21){
                $dataUpdate['time_approve']         = $this->time();
            }

            $dataUpdate['courier_tracking_code']    = $this->__LadingOne->tracking_code;
            $dataUpdate['status']                   = 30;
            $updateLog['error_code']                = 'SUCCESS';

            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR' => "SUCCESS", 'update' => $dataUpdate); 
        }

        // Ghi log
        $updateLog['error_code']    = 'FAIL_CREATE_ORDER';
        $updateLog['messenger']     = $result['status'];
        \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
        return array('ERROR'=> 'FAIL', 'MSG'=> $result['status']);
    }

    private function _courier_weship(){
        $this->log_id = \LMongo::collection($this->logFile)->insert(array('courier' => $this->__LadingOne->courier_id,'sc_code' => $this->__LadingOne->tracking_code,'time_create' => $this->time()));

        $LadingParams  = [
            "Pickup" => [
                "FullName"     => ($this->__LadingOne->from_address_id > 0 ? $this->__LadingOne->from_order_address->user_name : $this->__LadingOne->from_user_data->fullname),
                "Phone"     => ($this->__LadingOne->from_address_id > 0 ? substr($this->__LadingOne->from_order_address->phone,0,30) : substr($this->__LadingOne->from_user_data->phone,0,30)),
                "Address"         => $this->__LadingOne->from_address,
                "InventoryNumber" => $this->__LadingOne->from_address_id,
                "CityId"          => $this->__LadingOne->from_city_id,
                "DistrictId"      => $this->__LadingOne->from_district_id,
                "WardId"          => $this->__LadingOne->from_ward_id
            ],
            "Delivery"=> [
                "FullName"   => $this->__LadingOne->to_name,
                "Phone"      => $this->__LadingOne->to_phone,
                "Address"    => $this->__LadingOne->to_order_address->address,
                "CityId"     => $this->__LadingOne->to_order_address->city_id,
                "DistrictId" => $this->__LadingOne->to_order_address->province_id,
                "WardId"     => $this->__LadingOne->to_order_address->ward_id
            ],
            "TrackingCode" => $this->__LadingOne->tracking_code,
            "Config" => [
                "Collection" => $this->__LadingOne->order_detail->money_collect,
                "Service"    => 1, //$this->__LadingOne->service_id | Phat qua ngay
                "Payment"    => $this->__LadingOne->order_detail->money_collect > 0 ? 1 : 2,
                "Type"       => 0,
                "Weight"     =>$this->__LadingOne->total_weight
            ],
            "ParcelItems" => [
                [
                    "TotalAmount" => $this->__LadingOne->total_amount,
                    "TotalWeight" => $this->__LadingOne->total_weight,
                    "Items"       => [
                        [
                            "Weight"    => $this->__LadingOne->total_weight,
                            "Amount"    => $this->__LadingOne->total_amount,
                            "Quanlity"  => $this->__LadingOne->total_quantity,
                            "Description"   => $this->__LadingOne->order_item->description,
                            "ProductName"   => $this->__LadingOne->product_name
                        ]
                    ]
                ]
            ],
            "Items" => [
                [
                    "ProductName"   => $this->__LadingOne->product_name,
                    "TotalWeight"   => $this->__LadingOne->total_weight,
                    "Quanlity"      => $this->__LadingOne->total_quantity,
                    "Amount"        => $this->__LadingOne->total_amount,
                    "Description"   => $this->__LadingOne->order_item->description
                ]
            ]
        ];
        $PostResult = \cURL::jsonPost('http://ops.weship.vn/api/v2/create_order?access_token=19c1b49d56d27d3f9240e4fe03238d8acc3e3ce32d3570c642e396de77a66233891c4b29b8adb2ba729c790503af8a37c150f474dff31bcf6b99af549617fa39',$LadingParams);
        $CreateLading = json_decode($PostResult, 1);

        $updateLog = array(
            'params' => $LadingParams,
            'result' => $CreateLading,
        );

        if(!$CreateLading){
            // Ghi log
            $updateLog['error_code']    = 'FAIL_API_CREATE_ORDER';
            $updateLog['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Duyệt không thành công, Chẳng biết lỗi gì!', 'DATA'=>$PostResult);
        }

        if($CreateLading['Status'] == 200){
            if($this->__LadingOne->status == 21){
                $dataUpdate['time_approve']         = $this->time();
            }

            $dataUpdate['courier_tracking_code']    = $CreateLading['Data']['OrderNumber'];
            $dataUpdate['status']                   = 30;
            $updateLog['error_code']                = 'SUCCESS';

            \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
            return array('ERROR' => "SUCCESS", 'update' => $dataUpdate); // 00 -> SUCCESS
        }

        // Ghi log
        $updateLog['error_code']    = 'FAIL_CREATE_ORDER';
        $updateLog['messenger']     = $CreateLading['Messages'];
        \LMongo::collection($this->logFile)->where('_id', new \MongoId($this->log_id))->update($updateLog);
        return array('ERROR'=> 'FAIL', 'MSG'=> $CreateLading['Messages'], 'TrackingCode'=> $this->__LadingOne->tracking_code);
    }

    private function _getOrder($status = 0, $courier = []){
        $fields = ['id','courier_id','tracking_code', 'domain','status', 'total_weight','time_accept', 'time_approve', 'time_pickup', 'time_success','time_create','from_district_id','to_district_id','courier_tracking_code', 'time_packed'];
        $OrdersModel    = new OrdersModel;
        $OrdersModel    = $OrdersModel::where('time_accept','>=',$this->time() - $this->time_limit);

        if(Input::has('order_id')){
            $OrdersModel = $OrdersModel->where('id',(int)Input::get('order_id'));
        }elseif(Input::has('tracking_code')){
            $OrdersModel = $OrdersModel->where('tracking_code',strtoupper(trim(Input::get('tracking_code'))));
        }

        if($status > 0){
            $OrdersModel  = $OrdersModel->where('status',$status);
        }

        if(!empty($courier)){
            $OrdersModel  = $OrdersModel->whereIn('courier_id',$courier);
        }

        if($status == 61){
            $OrdersModel    = $OrdersModel->where('time_accept_return', '<=', ($this->time() - 3600));
        }

        if(in_array($status, [28,61])){
            $this->__LadingOne= $OrdersModel->orderBy('time_update','ASC')->first($fields);
        }else{
            $this->__LadingOne= $OrdersModel->orderBy('time_create','ASC')->first($fields);
        }

        return $this->__LadingOne;
    }



    // lấy trạng thái theo đơn hàng từ hvc
    public function getStatusViettel($json = true){
        $ScCode     = Input::has('tracking_code')     ? strtoupper(trim(Input::get('tracking_code')))   : strtoupper(trim(Input::json()->get('tracking_code')));


        $LadingParams   = ['LadingCode'    => (string) substr($ScCode,2)];
        $soapClient = new \SoapClient("http://203.113.130.254/VTPAPITMDT/vtptmdt.asmx?wsdl");
        //Create Soap Header.
        $header     = new \SOAPHeader('http://viettelpost.org/', 'ServiceAuthHeader', array('Token' => (string)'B401262F9A3D09B9A5AC7AFF3DED92CC'));
        //set the Headers of Soap Client.
        $soapClient->__setSoapHeaders($header);
        $result =  $soapClient->SearchLadingEnd($LadingParams);
        if(empty($result))
        {
            $Response   = [
                'error'             => true,
                'message'           => 'API_FAIL',
                'error_message'     => 'Lỗi API',
                'params'            => $LadingParams
            ];
        }
        elseif(!empty($result->SearchLadingEndResult)) {
            if(!isset($result->SearchLadingEndResult->HisLading1) || empty($result->SearchLadingEndResult->HisLading1)){
                $Response   = [
                    'error'             => true,
                    'error'             => true,
                    'message'           => 'NOT_EXISTS',
                    'error_message'     => 'Không tồn tại trên hvc',
                    'params'            => $LadingParams
                ];
            }else{// tồn tại
                $Order = $result->SearchLadingEndResult->HisLading1;
                $BaseCtrl = new \BaseCtrl;

                Input::merge(['courier' => 1, 'type' => 1]);
                $StatusCourier      = $BaseCtrl->getStatusCourier(false);
                $Response   = [
                    'error'             => false,
                    'message'           => 'SUCCESS',
                    'error_message'     => 'Thành công',
                    'params'            => $LadingParams,
                    'courier_status'    => isset($Order->TRANGTHAI) ? $Order->TRANGTHAI : '',
                    'sc_status'         => isset($StatusCourier[$Order->TRANGTHAI]) ? $StatusCourier[$Order->TRANGTHAI] : '',
                    'detail'            => $Order
                ];
            }
        }
        else
        {
            $Response   = [
                'error'             => true,
                'message'           => 'NOT_EXISTS',
                'error_message'     => 'Không tồn tại trên hvc',
                'params'            => $LadingParams,
                'result'            => $result
            ];
        }

        return $json ? Response::json($Response) : $Response;
    }

    /**
     * Báo duyệt hoàn
     */
    public function getConfirmreturn(){
        $this->logFile  = 'log_report_lading';

        $this->_getOrder(61, [1,9]);

        if(!isset($this->__LadingOne->id)){
            return Response::json(['ERROR' => 'EMPTY DATA']);
        }

        $funcName   = '_return_'.$this->__LadingOne->courier->prefix;

        $DataUpdate = [
            'time_update'   => $this->time()
        ];

        if(method_exists($this ,$funcName)){
            $result     = $this->$funcName();

            if(!in_array($result['MSG'], ['EXIST_ORDER', 'SUCCESS'])){
                $Rest = $result;
            }else{
                $DataUpdate['status']               = 62;
                $DataUpdate['time_accept_return']   = $this->time();

                $Rest = ['ERROR'  => 'SUCCESS'];
            }
        }else{
            $Rest   = ['ERROR' => 'FUNCTION_NOT_EXISTS', 'DATA' => $funcName];
        }

        try{
            OrdersModel::where('time_accept','>=', $this->__LadingOne->time_accept - 3600)
                ->where('time_accept','<', $this->__LadingOne->time_accept + 3600)
                ->where('id', $this->__LadingOne->id)
                ->update($DataUpdate);

            if(isset($DataUpdate['status'])){
                $this->__InsertLogBoxme($this->__LadingOne, (int)$DataUpdate['status']);
            }

        }catch (Exception $e){
            return Response::json(['ERROR'  => 'UPDATE_STATUS_FAIL']);
        }

        return Response::json($Rest);
    }

    public function _return_vtp(){


        $LadingParams = array(
            "f_ORDER_ID"          => (string) substr($this->__LadingOne->tracking_code,2),
            "f_MA_VANDON"         => (string) $this->__LadingOne->tracking_code.'H',
            "f_MA_VANDONGOC"      => (string) $this->__LadingOne->tracking_code
        );

        $soapClient = new \SoapClient("http://203.113.130.254/VTPAPITMDT/vtptmdt.asmx?wsdl");
        //Create Soap Header.
        $header     = new \SOAPHeader('http://viettelpost.org/', 'ServiceAuthHeader', array('Token' => (string)'B401262F9A3D09B9A5AC7AFF3DED92CC'));
        //set the Headers of Soap Client.
        $soapClient->__setSoapHeaders($header);
        $result =  $soapClient->InsertOrderThuHoi($LadingParams);

        // data update Log
        $DataInsert = array(
            'params'        => $LadingParams,
            'tracking_code' => $this->__LadingOne->tracking_code,
            'time_create'   => $this->time(),
            'status'        => $this->__LadingOne->status,
            'courier'       => $this->__LadingOne->courier_id
        );

        if(empty($result))
        {
            // Ghi log
            $DataInsert['error_code']    = 'FAIL_API';
            $DataInsert['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'API bị lỗi','DATA' => $this->__LadingOne->tracking_code);
        }
        elseif($result->InsertOrderThuHoiResult == 'SUCCESS') {
            // data update Log
            $DataInsert['error_code']    = 'SUCCESS';
            $DataInsert['messenger']     = 'Thành công';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR' => "SUCCESS", 'MSG' => 'SUCCESS','DATA' => $this->__LadingOne->tracking_code);
        }
        else
        {
            // Ghi log
            $DataInsert['error_code']    = 'FAIL';
            $DataInsert['messenger']     = $result->InsertOrderThuHoiResult;
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'FAIL', 'MSG'=> $result->InsertOrderThuHoiResult,'DATA' => $this->__LadingOne->tracking_code);
        }
    }

    public function _return_gts(){
        $Token = $this->_login_gts();

        $LadingParams = array(
            "trackingNumber"        => (string) $this->__LadingOne->tracking_code,
            "returingAccept"        => true,
            "note"                  => (string) 'Khách hàng báo chuyển hoàn'
        );

        $DataInsert = [
            'tracking_code' => $this->__LadingOne->tracking_code,
            'params'        => $LadingParams,
            'time_create'   => $this->time(),
            'status'        => $this->__LadingOne->status,
            'courier'       => $this->__LadingOne->courier_id
        ];

        $respond = (string)\cURL::newRequest('post', 'http://epost.goldtimes.vn/epost-api/order/handle/returing', $LadingParams)
            ->setHeader('Authorization', 'Bearer '.$Token->access_token)
            ->send();

        if(!$respond){
            // Ghi log
            $DataInsert['error_code']    = 'FAIL';
            $DataInsert['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'FAIL');
        }

        $result = json_decode($respond,1);

        if($result['errorCode']  == '00'){
            $DataInsert['result']       = $result;
            $DataInsert['error_code']   = 'SUCCESS';
            $DataInsert['messenger']    = 'Thành công';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'SUCCESS', 'MSG'=> 'SUCCESS');
        }else{
            $DataInsert['result']       = $result;
            $DataInsert['error_code']   = 'ERROR';
            $DataInsert['messenger']    = 'Thất bại';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'ERROR', 'MSG'=> 'FAIL');
        }
    }

    public function _return_ttc(){
        $Data       = [
            'Token'             => 'OcZNgIdYMIaTRZMg83FjsoBvb2dVG8t8',
            'TrackingNumber'    => 'SC210739996',
            'Note'              => 'Khách hàng báo chuyển hoàn'
        ];

        $DataInsert = [
            'tracking_code' => $this->__LadingOne->tracking_code,
            'params'        => $Data,
            'time_create'   => $this->time(),
            'status'        => $this->__LadingOne->status,
            'courier'       => $this->__LadingOne->courier_id
        ];

        $param = http_build_query($Data);
        $respond = \cURL::newRequest('post', 'http://112.109.89.75:9999/api/BAOCHUYENHOAN?'.$param)
            ->setHeader('content-type', 'text/plain')
            ->send();
        dd($respond);
        if(!$respond->body){
            // Ghi log
            $DataInsert['error_code']    = 'FAIL';
            $DataInsert['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'FAIL');
        }

        $result = json_decode($respond->body,1);

        if($result['DATA']  == 'EXIST'){
            $DataInsert['result']       = $result;
            $DataInsert['error_code']   = 'SUCCESS';
            $DataInsert['messenger']    = 'Thành công';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'SUCCESS', 'MSG'=> 'SUCCESS');
        }else{
            $DataInsert['result']       = $result;
            $DataInsert['error_code']   = 'ERROR';
            $DataInsert['messenger']    = 'Thất bại';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'ERROR', 'MSG'=> 'FAIL');
        }
    }

    /**
     * Báo phát lại
     */
    public function getReportReplay($id = '' ){
        $this->logFile      = 'log_report_lading';
        $PipeJourneyModel   = new \omsmodel\PipeJourneyModel;
        //Lấy yêu cầu phát lại đã được vận hành xác minh
        $PipeJourneyModel         = $PipeJourneyModel::where('time_create','>=', $this->time() - 86400*30)
            ->where('type',1)
            ->where('report_courier',0)
            ->where(function($query){
                $query->where(function($q){
                    $q->where('group_process',   29)
                        ->where('pipe_status', 707);
                })->orWhere(function($q){
                    $q->where('group_process',   31)
                        ->where('pipe_status', 903);
                });
            });

        if(!empty($id)){
            $PipeJourneyModel = $PipeJourneyModel->where('id', $id);
        }

        $this->Pipe    =    $PipeJourneyModel->orderBy('time_create','ASC')->first();

        $Courier    = [
            1   => 'vtp',
            11  => 'ttc'
        ];

        if(!isset($this->Pipe->id)){
            return Response::json( array(
                'error'         => false,
                'message'       => 'EMPTY',
                'data'          => 'Đã báo hết'
            ) );
        }

        $Note   = $this->Pipe->note;


        $OrdersModel    = new OrdersModel;
        $this->__Ladings          = $OrdersModel::where('id',$this->Pipe->tracking_code)
            ->where('time_accept','>=',$this->time() - $this->time_limit)
            ->with('OrderDetail')
            ->first(['id','tracking_code','courier_id','to_phone','tracking_code','postman_id']);

        if(!isset($this->__Ladings->id)){
            try{
                $this->Pipe->report_courier   = 2;
                $this->Pipe->save();
            }catch(\Exception $e){
                return Response::json( array(
                    'error'         => false,
                    'message'       => 'UPDATE_PIPE_FAIL',
                    'data'          => 'Cập nhật lỗi'
                ) );
            }
            return Response::json( array(
                'error'         => false,
                'message'       => 'ORDER_NOT_EXISTS',
                'data'          => 'Đơn hàng không tồn tại'
            ) );
        }

        if(!in_array($this->__Ladings->courier_id, [1])){
            try{
                $this->Pipe->report_courier   = 3;
                $this->Pipe->save();
            }catch(\Exception $e){
                return Response::json( array(
                    'error'         => false,
                    'message'       => 'UPDATE_PIPE_FAIL',
                    'data'          => 'Cập nhật lỗi'
                ) );
            }
            return Response::json( array(
                'error'         => false,
                'message'       => 'COURIER',
                'data'          => 'Ko phải Viettel'
            ) );
        }

        $funcName   = '_replay_'.$Courier[$this->__Ladings->courier_id];


        if(method_exists($this ,$funcName)){
            $result     = $this->$funcName($Note);
            try{
                \LMongo::collection($this->logFile)->insert($result);
                $this->Pipe->save();
            }catch (\Exception $e){
                return Response::json(['ERROR' => 'UPDATE_ERROR', 'DATA' => $result]);
            }

            return Response::json($result);
        }else{
            return Response::json(['ERROR' => 'FUNCTION_NOT_EXISTS', 'DATA' => $funcName]);
        }
    }

    private function _replay_vtp($Note){
        /*$resultLogin = $this->_login_vtp();
        if(isset($resultLogin['ERROR']) && $resultLogin['ERROR'] == 'FAIL')
        {
            return $resultLogin;
        }*/

        $LadingParams = array(
            "f_MA_VANDON"           => (string) $this->__Ladings->tracking_code,
            "f_Ghichu"              => (string) isset($Note) ? mb_substr($Note,0,150, 'utf8') : 'Khách hàng yêu cầu phát lại - SC'
        );

        $soapClient = new \SoapClient("http://203.113.130.254/VTPAPITMDT/vtptmdt.asmx?wsdl");
        //Create Soap Header.
        $header     = new \SOAPHeader('http://viettelpost.org/', 'ServiceAuthHeader', array('Token' => (string)'B401262F9A3D09B9A5AC7AFF3DED92CC'));
        //set the Headers of Soap Client.
        $soapClient->__setSoapHeaders($header);
        $result =  $soapClient->InsertOrderPhatTiep($LadingParams);

        $DataInsert = array(
            'tracking_code' => $this->__Ladings->tracking_code,
            'params'        => $LadingParams,
            'result'        => $result,
            'status'        => 77,
            'courier'       => $this->__Ladings->courier_id,
            'time_create'   => $this->time(),
        );

        if(empty($result))
        {
            $DataInsert['error_code']    = 'FAIL_API';
            $DataInsert['messenger']     = 'Lỗi API mất rồi';
            $this->Pipe->report_courier = 4;
        }
        elseif($result->InsertOrderPhatTiepResult == 'SUCCESS') {
            $DataInsert['error_code']    = $result->InsertOrderPhatTiepResult;
            $DataInsert['messenger']     = 'Thành công';

            $this->Pipe->report_courier = 1;
            if($this->__Ladings->postman_id > 0){
                $this->SendSmS();
            }
        }
        else
        {
            $DataInsert['error_code']    = $result->InsertOrderPhatTiepResult;
            $DataInsert['messenger']     = $result->InsertOrderPhatTiepResult;
            $this->Pipe->report_courier = 5;
        }

        return $DataInsert;
    }

    public function _replay_ttc($Note){
        $Data       = [
            'Token'             => 'OcZNgIdYMIaTRZMg83FjsoBvb2dVG8t8',
            'TrackingNumber'    => (string) $this->TrackingCode,
            'Note'              => (string) isset($Note) ? mb_substr($Note,0,150, 'utf8') : 'Khách hàng yêu cầu phát lại - SC'
        ];

        $param = http_build_query($Data);
        $respond = \cURL::newRequest('post', 'http://112.109.89.75:9999/api/BAOPHATLAI?'.$param)
            ->setHeader('content-type', 'text/plain')
            ->send();

        if(!$respond->body){
            // Ghi log
            $DataInsert['error_code']    = 'FAIL';
            $DataInsert['messenger']     = 'Lỗi API mất rồi';
            $this->Pipe->report_courier = 4;
        }else{
            $result = json_decode($respond->body,1);

            $DataInsert = [
                'tracking_code' => $this->TrackingCode,
                'params'        => $Data,
                'result'        => $result,
                'status'        => 77,
                'courier'       => $this->__Ladings->courier_id,
                'time_create'   => $this->time(),
            ];

            if($result['ERROR']  == 'SUCCESS'){
                $DataInsert['error_code']   = 'SUCCESS';
                $DataInsert['messenger']    = 'Thành công';
                $this->Pipe->report_courier = 1;
            }else{
                $DataInsert['error_code']   = 'ERROR';
                $DataInsert['messenger']    = 'Thất bại';
                $this->Pipe->report_courier = 5;
            }
        }

        return $DataInsert;
    }


    /**
     * Báo hủy đơn
     */
    public function getReportCancel($json = true){
        $this->logFile  = 'log_report_lading';
        $this->_getOrder(28, [1,8,11]);
        if(!isset($this->__LadingOne->id)){
            return Response::json(['ERROR' => 'EMPTY DATA']);
        }

        $DataUpdate =   [
            'time_update'   => $this->time()
        ];
        $funcName   = '_cancel_'.$this->__LadingOne->courier->prefix;

        if(method_exists($this ,$funcName)){
            $result     = $this->$funcName();
            if($result['ERROR'] != 'SUCCESS'){
                $result['ORDER_ID'] = $this->__LadingOne->id;
                $Rest               = $result;
            }else{
                if($this->__LadingOne->time_packed > 0){
                    $DataUpdate['status']   = 121;
                }else {
                    $DataUpdate['status']   = 22;
                    
                }
                $Rest = ['ERROR'  => 'SUCCESS','ORDER_ID' => $this->__LadingOne->id];
            }

            try{
                OrdersModel::where('time_accept','>=', $this->__LadingOne->time_accept - 3600)
                    ->where('time_accept','<', $this->__LadingOne->time_accept + 3600)
                    ->where('id', $this->__LadingOne->id)
                    ->update($DataUpdate);

                if(isset($DataUpdate['status'])){
                    $this->__InsertLogBoxme($this->__LadingOne, (int)$DataUpdate['status']);
                }

            }catch (Exception $e){
                $Rest = ['ERROR'  => 'UPDATE_STATUS_FAIL','ORDER_ID' => $this->__LadingOne->id];
            }
        }else{
            $Rest = ['ERROR' => 'FUNCTION_NOT_EXISTS', 'DATA' => $funcName,'ORDER_ID' => $this->__LadingOne->id];
        }

        return $json ? Response::json($Rest) : $Rest;
    }

    private function _cancel_weship(){
        $LadingParams = array(
            "OrderNumber"         => (string) $this->__LadingOne->courier_tracking_code,
        );

        $result = json_decode(\cURL::jsonPost('http://ops.weship.vn/api/v1/cancel_order?access_token=19c1b49d56d27d3f9240e4fe03238d8acc3e3ce32d3570c642e396de77a66233891c4b29b8adb2ba729c790503af8a37c150f474dff31bcf6b99af549617fa39',$LadingParams), 1);

        // data update Log
        $DataInsert = array(
            'tracking_code' => $this->__LadingOne->tracking_code,
            'params'        => $LadingParams,
            'status'        => $this->__LadingOne->status,
            'courier'       => $this->__LadingOne->courier_id,
            'result'        => $result,
            'time_create'   => $this->time()
        );

        if(empty($result))
        {
            // Ghi log
            $DataInsert['error_code']    = 'FAIL_API';
            $DataInsert['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'API bị lỗi');
        }
        elseif($result['Status'] == 200) {
            // data update Log
            $DataInsert['error_code']    = 'SUCCESS';
            $DataInsert['messenger']     = 'Thành công';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR' => "SUCCESS", 'MSG'=> 'SUCCESS');
        }
        else
        {
            // Ghi log
            $DataInsert['error_code']    = 'FAIL';
            $DataInsert['messenger']     = $result['Messages'];
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'FAIL', 'MSG'=> $result['Messages']);
        }
    }

    private function _cancel_vtp(){

        /*$resultLogin = $this->_login_vtp();
        if(isset($resultLogin['ERROR']) && $resultLogin['ERROR'] == 'FAIL')
        {
            return $resultLogin;
        }*/

        $LadingParams = array(
            "fMA_VANDON"         => (string) substr($this->__LadingOne->tracking_code,2),
        );

        $soapClient = new \SoapClient("http://203.113.130.254/VTPAPITMDT/vtptmdt.asmx?wsdl");
        //Create Soap Header.
        $header     = new \SOAPHeader('http://viettelpost.org/', 'ServiceAuthHeader', array('Token' => (string)'B401262F9A3D09B9A5AC7AFF3DED92CC'));
        //set the Headers of Soap Client.
        $soapClient->__setSoapHeaders($header);
        $result = $soapClient->VTPDeleteOrder($LadingParams);

        // data update Log
        $DataInsert = array(
            'tracking_code' => $this->__LadingOne->tracking_code,
            'params'        => $LadingParams,
            'status'        => $this->__LadingOne->status,
            'courier'       => $this->__LadingOne->courier_id,
            'time_create'   => $this->time(),
        );

        if(empty($result))
        {
            // Ghi log
            $DataInsert['error_code']    = 'FAIL_API';
            $DataInsert['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'API bị lỗi');
        }
        elseif(in_array($result->VTPDeleteOrderResult, ['SUCCESS','NotUpdate1','NotUpdate2'])) {
            // data update Log
            $DataInsert['error_code']    = 'SUCCESS';
            $DataInsert['messenger']     = 'Thành công';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR' => "SUCCESS", 'MSG'=> 'SUCCESS');
        }
        else
        {
            // Ghi log
            $DataInsert['error_code']    = 'FAIL';
            $DataInsert['messenger']     = $result->VTPDeleteOrderResult;
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'FAIL', 'MSG'=> $result->VTPDeleteOrderResult);
        }
    }

    public function _cancel_ttc(){
        $Data       = [
            'token_key'             => 'UzAjiyTLhXE5GfyuHyUxMw==',
            'waybill_number'    => (string)$this->__LadingOne->tracking_code,
            'note'              => 'Khách hàng báo hủy'
        ];

        $DataInsert = [
            'tracking_code' => $this->__LadingOne->tracking_code,
            'params'        => $Data,
            'status'        => $this->__LadingOne->status,
            'courier'       => $this->__LadingOne->courier_id,
            'time_create'   => $this->time()
        ];

        $respond = \cURL::jsonPost('http://gw.kerryexpress.com.vn/api/WSBAOHUY',$Data);
        if(!$respond->body){
            // Ghi log
            $DataInsert['error_code']    = 'FAIL';
            $DataInsert['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Báo hủy thất bại, Chẳng biết lỗi gì!');
        }

        $result = json_decode($respond->body,1);
        if(empty($result['status'])){
            $DataInsert['result']       = $result;
            $DataInsert['error_code']   = 'ERROR';
            $DataInsert['messenger']    = 'Thất bại';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'ERROR', 'MSG'=> 'Thất bại!','RS' => $result);
        }

        if($result['status']  == 'success'){
            $DataInsert['result']       = $result;
            $DataInsert['error_code']   = 'SUCCESS';
            $DataInsert['messenger']    = 'Thành công';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'SUCCESS', 'MSG'=> 'Thành công!');
        }else{
            $DataInsert['result']       = $result;
            $DataInsert['error_code']   = 'ERROR';
            $DataInsert['messenger']    = 'Thất bại';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'ERROR', 'MSG'=> 'Thất bại!', 'RS' => $result);
        }
    }

    public function _cancel_ems(){
        $Data       = [
            'TokenKey'          => 'shipchung@123',
            'TrackingNumber'    => (string)$this->__LadingOne->tracking_code,
            'OrderNumber'       => (string)$this->__LadingOne->tracking_code
            //'Note'              => 'Khách hàng báo hủy'
        ];

        $DataInsert = [
            'tracking_code' => $this->__LadingOne->tracking_code,
            'params'        => $Data,
            'status'        => $this->__LadingOne->status,
            'courier'       => $this->__LadingOne->courier_id,
            'time_create'   => $this->time()
        ];


        $soapClient = new \SoapClient("http://api.ems.com.vn/api/ems_partner.asmx?wsdl");
        $respond    = $soapClient->delete(['delete_Order' => json_encode($Data)]);

        if(!$respond->deleteResult){
            // Ghi log
            $DataInsert['error_code']    = 'FAIL';
            $DataInsert['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Báo hủy thất bại, Chẳng biết lỗi gì!');
        }

        $result = json_decode($respond->deleteResult,1);

        if($result['error']  == '00'){
            $DataInsert['result']       = $result;
            $DataInsert['error_code']   = 'SUCCESS';
            $DataInsert['messenger']    = 'Thành công';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'SUCCESS', 'MSG'=> 'Thành công!');
        }else{
            $DataInsert['result']       = $result;
            $DataInsert['error_code']   = 'ERROR';
            $DataInsert['messenger']    = 'Thất bại';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return array('ERROR'=> 'ERROR', 'MSG'=> 'Thất bại!');
        }
    }

    /*
     * Báo lấy lại hàng
     */
    public function getReportResume(){
        $this->logFile  = 'log_report_lading';
        if(!Input::has('tracking_code')){
            return Response::json(['error' => true, 'message' => 'EMPTY_CODE', 'error_message' => 'Chưa truyền mã đơn hàng']);
        }

        $this->_getOrder(0, []);
        if(!isset($this->__LadingOne->id)){
            return Response::json(['error' => true, 'message' => 'EMPTY', 'error_message' => 'Không tồn tại đơn hàng']);
        }

        $funcName   = '_resume_'.$this->__LadingOne->courier->prefix;

        if(method_exists($this ,$funcName)){
            $result     = $this->$funcName();

            if($result['error']){
                return Response::json($result);
            }else{
                try{
                    OrdersModel::where('time_accept','>=', $this->__LadingOne->time_accept - 3600)
                        ->where('time_accept','<', $this->__LadingOne->time_accept + 3600)
                        ->where('id', $this->__LadingOne->id)
                        ->update(['status' => 30]);

                    $this->__InsertLogBoxme($this->__LadingOne, 30);

                    return Response::json([
                        'error'             => true,
                        'message'           => 'SUCCESS',
                        'error_message'     => 'Thành công'
                    ]);
                }catch (Exception $e){
                    return Response::json([
                        'error'             => true,
                        'message'           => 'UPDATE_STATUS_FAIL',
                        'error_message'     => 'Lỗi cập nhật'
                    ]);
                }
            }
        }else{
            return Response::json([
                'error'             => true,
                'message'           => 'FUNCTION_NOT_EXISTS',
                'error_message'     => 'Lỗi'
            ]);
        }
    }

    private function _resume_vtp(){
        ini_set("soap.wsdl_cache_enabled", 0);
        /*$resultLogin = $this->_login_vtp();
        if(isset($resultLogin['ERROR']) && $resultLogin['ERROR'] == 'FAIL')
        {
            return ['error' => true, 'message' => $resultLogin['ERROR'], 'error_message' => 'Lỗi login viettel'];
        }*/

        $LadingParams = array(
            "fMA_VANDON"         => (string) $this->__LadingOne->tracking_code
        );

        $soapClient = new \SoapClient("http://203.113.130.254/VTPAPITMDT/vtptmdt.asmx?wsdl");
        //Create Soap Header.
        $header     = new \SOAPHeader('http://viettelpost.org/', 'ServiceAuthHeader', array('Token' => (string)'B401262F9A3D09B9A5AC7AFF3DED92CC'));
        //set the Headers of Soap Client.
        $soapClient->__setSoapHeaders($header);
        $result =  $soapClient->LAYLAIHANG ($LadingParams);

        // data update Log
        $DataInsert = array(
            'tracking_code' => $this->__LadingOne->tracking_code,
            'params'        => $LadingParams,
            'status'        => $this->__LadingOne->status,
            'courier'       => $this->__LadingOne->courier_id,
            'time_create'   => $this->time(),
        );

        if(empty($result))
        {
            // Ghi log
            $DataInsert['error_code']    = 'FAIL_API';
            $DataInsert['messenger']     = 'Lỗi API mất rồi';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return [
                'error'             => true,
                'message'           => 'FAIL_API',
                'error_message'     => 'Lỗi kết nối viettel'
            ];
        }
        elseif($result->LAYLAIHANGResult == 'SUCCESS') {
            // data update Log
            $DataInsert['error_code']    = 'SUCCESS';
            $DataInsert['messenger']     = 'Thành công';
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return [
                'error'             => false,
                'message'           => 'SUCCESS',
                'error_message'     => 'Thành công'
            ];
        }
        else
        {
            // Ghi log
            $DataInsert['error_code']    = 'FAIL';
            $DataInsert['messenger']     = $result->LAYLAIHANGResult;
            \LMongo::collection($this->logFile)->insert($DataInsert);
            return [
                'error'             => true,
                'message'           => 'SUCCESS',
                'error_message'     => $result->LAYLAIHANGResult
            ];
        }
    }

    private function SendSmS(){ // Khi Khách hàng báo phát lại
        $toPhone = str_replace(array(';','.',' ','/','|'), ',', $this->__Ladings->to_phone);

        $arrPhone = array();
        if($toPhone != ''){
            $arrPhone = explode(',', $toPhone);
        }

        //get postman
        $PostManModel   = new \PostManModel;
        $PostMan        = $PostManModel::where('postman_id',$this->__Ladings->postman_id)->orderBy('id','DESC')->first();

        if(!isset($PostMan->id)){
            return;
        }

        $Content = $this->__Ladings->tracking_code.'-Dang duoc giao lai, ban vui long giu lien lac hoac lien he buu ta '
            .$PostMan->name.': ' . $PostMan->phone.' de nhan hang. Thu ho: '.number_format($this->__Ladings->order_detail->money_collect);

        Input::Merge([
            'to_phone'   => $arrPhone[0],
            'content'    => $Content
        ]);

        $SmsController  = new \SmsController;
        $SmsController->postSendsms(false);
    }


    // Đơn ở trạng thái hủy sau 3 ngày => báo hủy
    public function getScAutoCancel(){
        $OrdersModel    = new OrdersModel;
        $Orders         = $OrdersModel::where('time_accept','>=',$this->time() - 86400*90)
            ->where('courier_id',1)
            ->whereIn('status',[23,24,25,78])->where('time_update','<=',$this->time() - 86400*7)
            ->orderBy('time_update','ASC')
            ->first(['id','courier_id','tracking_code', 'domain','status', 'total_weight','time_accept', 'time_approve', 'time_pickup', 'time_success']);

        if(!isset($Orders->id)){
            return ['error' => true, 'message' => 'EMPTY'];
        }

        $DataUpdate = ['time_update'    => $this->time()];
        $Response   = [];

        Input::merge(['tracking_code'   => $Orders->tracking_code]);

        $DataUpdate['status']    = 28;

        try{
            OrdersModel::where('time_accept','>=', 86400*7)->where('time_create','>=',$this->time() - $this->time_limit)
                ->where('id', $Orders->id)->update($DataUpdate);

            if(isset($DataUpdate['status'])){
                $this->__InsertLogBoxme($Orders, (int)$DataUpdate['status']);
            }

            return $Response;
        }catch (\Exception $e){
            $Response['update_message']  = 'UPDATE_FAIL';
            return $Response;
        }
    }

    private function __order_detail($id){
        return \ordermodel\DetailModel::where('order_id', $id)->first();
    }

    public function __InsertLogBoxme($Order, $Status){
        if(in_array($Order->domain, ['boxme.vn', 'chodientu.vn','prostore.vn','juno.vn','www.ebay.vn'])){
            $Detail = $this->__order_detail($Order->id);

            $LMongo         = new LMongo;
            $Id = $LMongo::collection('log_journey_notice')->insert([
                'tracking_code' => $Order->tracking_code,
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

    private function _insert_log_pickup($Order){
        $LMongo         = new \LMongo;
        $Log            = $LMongo::collection('log_journey_pickup');
        $Log            = $Log->where('tracking_code', $Order->tracking_code)->delete();

        $DataUpdate = [
            'active'                    => 0,
            'order_id'                  => $Order->id,
            'tracking_code'             => $Order->tracking_code,
            'domain'                    => $Order->domain,
            'service_id'                => $Order->service_id,
            'status'                    => 30,
            'from_country_id'           => $Order->from_country_id,
            'from_city_id'              => $Order->from_city_id,
            'from_district_id'          => $Order->from_district_id,
            'from_ward_id'              => $Order->from_ward_id,
            'from_address_id'           => $Order->from_address_id,
            'from_user_id'              => $Order->from_user_id,
            'from_location'             => 0,
            'promise_pickup_time'       => 0,
            'time_accept'               => $this->time(),
            'time_pickup'               => 0,
            'time_update'               => $this->time(),
            'time_create'               => $this->time()
        ];

        $LMongo         = new \LMongo;
        $LogId          = $LMongo::collection('log_journey_pickup')->insert($DataUpdate);

        return $LogId;
    }
}