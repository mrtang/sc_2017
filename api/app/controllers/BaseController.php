<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class BaseController extends Controller {
    private $user = [];
    public $url_upload  = 'http://cloud.boxme.vn/storage/';
    public $link_upload = 'uploads';
    public $url         = 'http://services.shipchung.vn/api/rest';
    public $time_limit  = 8035200;  // 93 ngày

    public $facebook;

    public $_error           = false;
    public $_json            = true;
    public $_error_code      = "";
    public $_error_message   = "";
    public $_additional      = [];
    public $_message         = "";

    private $RabbitMQConnection = null;

    public $lang = 'vi';



	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
    public $vimo   =   [
        'ABB'   => 'ABBank - Ngân hàng TMCP An Bình',
        'ACB'   => 'ACB - Ngân hàng TMCP Á Châu',
        'BAB'   => 'BacA Bank - Ngân hàng TMCP Bắc Á',
        'BVB'   => 'Baoviet Bank - Ngân hàng TMCP Bảo Việt',
        'GAB'   => 'DaiA Bank - Ngân hàng TMCP Đại Á', 
        'EXB'   => 'Eximbank - Ngân hàng TMCP XNK Việt Nam',  
        'GPB'   => 'GPBank - Ngân hàng TMCP Dầu khí Toàn Cầu', 
        'HDB'   => 'HD Bank - Ngân hàng Phát triển Nhà TPHCM', 
        'LVB'   => 'Lien Viet Post Bank - Ngân hàng Bưu Điện Liên Việt', 
        'MBB'   => 'MB Bank - Ngân hàng TMCP Quân Đội',  
        'MHB'   => 'MHB - Ngân hàng TMCP PT Nhà Đồng bằng sông Cửu Long', 
        'NVB'   => 'Navibank - Ngân hàng TMCP Nam Việt', 
        'OJB'   => 'OceanBank - Ngân hàng TMCP Đại Dương', 
        'SCB'   => 'Sacombank - Ngân hàng TMCP Sài Gòn thương tín', 
        'SHB'   => 'SHB - Ngân hàng TMCP Sài Gòn - Hà Nội', 
        'TCB'   => 'Techcombank - Ngân hàng TMCP Kỹ Thương Việt Nam',
        'TPB'   => 'TienPhong Bank - Ngân hàng TMCP Tiên Phong', 
        'VIB'   => 'VIB - Ngân hàng TMCP Quốc tế', 
        'VAB'   => 'Viet A Bank - Ngân hàng TMCP Việt Á', 
        'VCB'   => 'Vietcombank - Ngân hàng TMCP Ngoại Thương Việt Nam', 
        'ICB'   => 'VietinBank - Ngân hàng TMCP Công Thương Việt Nam', 
        'VPB'   => 'VPBank - Ngân hàng TMCP Việt Nam Thịnh Vượng', 
        'HLBVN' => 'Ngân hàng Hong Leong Việt Nam',
        'OCB'   => 'Ngân hàng TMCP Phương Đông',
        'AGB' => 'Ngân hàng NN&PT Nông thôn',
        'ANZ' => 'Ngân hàng ANZ',
        'BIDC' => 'Ngân hàng ĐT&PT Campuchia',
        'CTB' => 'Ngân hàng CITY BANK',
        'DAB' => 'Ngân hàng TMCP Đông Á',
        'HSB' => 'Ngân hàng HSBC',
        'IVB' => 'Ngân Hàng Indovina',
        'KLB' => 'Ngân hàng TMCP Kiên Long',
        'MDB' => 'Ngân hàng TMCP PT Mê Kông',
        'MHB' => 'Ngân hàng TMCP PT Nhà Đồng bằng sông Cửu Long',
        'NCB' => 'Ngân hàng TMCP Quốc Dân',
        'NHOFFLINE' => 'Ngân hàng Offline',
        'PGB' => 'Ngân hàng TMCP Xăng dầu Petrolimex',
        'PNB' => 'Ngân hàng Phương Nam',
        'PVB' => 'Ngân hàng TMCP Đại Chúng Việt Nam',
        'SEA' => 'Ngân hàng TMCP Đông Nam Á',
        'SGB' => 'Ngân hàng TMCP Sài Gòn Công Thương',
        'SGCB' => 'Ngân hàng TMCP Sài Gòn',
        'SHNB' => 'Ngân hàng SHINHAN',
        'SMB' => 'Ngân hàng SUMITOMO-MITSUI',
        'STCB' => 'Ngân hàng STANDARD CHARTERED',
        'VB' => 'Ngân hàng Việt Nam Thương Tín',
        'VCCB' => 'Ngân hàng TMCP Bản Việt',
        'VDB' => 'Ngân hàng Phát triển Việt Nam',
        'VIDPB' => 'Ngân hàng VID Public Bank',
        'VNCB' => 'Ngân hàng TMCP Xây dựng Việt Nam',
        'VRB' => 'Ngân hàng Liên doanh Việt - Nga',
        'VSB' => 'Ngân Hàng Liên Doanh Việt Thái',
        'NONE'  => 'Ngân hàng khác (áp dụng với thẻ visa)'
    ];
    
    public function __construct(){


        $headers = getallheaders();
        if(isset($headers['LANGUAGE']) && !empty($headers['LANGUAGE'])){
            $this->lang = $headers['LANGUAGE']; 
        }


        //if(Config::get('app.debug'))
//        {
//            $this->beforeFilter(function()
//            {
//                Event::fire('clockwork.controller.start');
//            });
//            
//            $this->afterFilter(function()
//            {
//                Event::fire('clockwork.controller.end');
//            });
//        }
    }


    public function getAppCountryId(){
        return 237;
    }
    

    //Get time now
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


    public function _getRabbitMQInstance (){
        if(empty($this->RabbitMQConnection)){
            try {
                $this->RabbitMQConnection = new AMQPConnection(
                    '10.0.20.164',   #host - host name where the RabbitMQ server is runing
                    5672,           #port - port number of the service, 5672 is the default
                    'shipchung',    #user - username to connect to server
                    'ShipchungaA@2' #password
                );
            } catch (Exception $e) {
                return false;
            }
        }
        return $this->RabbitMQConnection;
    }


    /*
    * $action : updated, created
    */
    public function PushSyncElasticsearch($index, $type, $action, $data = []){
        
        $conn = $this->_getRabbitMQInstance();
        if(!$conn){return false;}

        $channel = $conn->channel();
        $channel->queue_declare(
            'sync_elastic', #queue name - Queue names may be up to 255 bytes of UTF-8 characters
            false,          #passive - can use this to check whether an exchange exists without modifying the server state
            true,           #durable - make sure that RabbitMQ will never lose our queue if a crash occurs - the queue will survive a broker restart
            false,          #exclusive - used by only one connection and the queue will be deleted when that connection closes
            false           #autodelete - queue is deleted when last consumer unsubscribes
        );

        $Message = [
            'index'     => $index,
            'type'      => $type,
            'action'    => $action,
            'data'      => $data
        ];
        
        $msg = new AMQPMessage(json_encode($Message));

        try {
            $channel->basic_publish(
                $msg,           #message 
                '',             #exchange
                'sync_elastic'  #routing key
            );
        } catch (Exception $e) {
            return false;
        }
    }


    //
    public function PushJourneyProcess($queue_name, $data = []){
        
        $conn = $this->_getRabbitMQInstance();
        if(!$conn){return false;}

        $channel = $conn->channel();
        $channel->queue_declare(
            $queue_name, #queue name - Queue names may be up to 255 bytes of UTF-8 characters
            false,          #passive - can use this to check whether an exchange exists without modifying the server state
            true,           #durable - make sure that RabbitMQ will never lose our queue if a crash occurs - the queue will survive a broker restart
            false,          #exclusive - used by only one connection and the queue will be deleted when that connection closes
            false           #autodelete - queue is deleted when last consumer unsubscribes
        );

        
        
        $msg = new AMQPMessage(json_encode($data));

        try {
            $channel->basic_publish(
                $msg,           #message 
                '',             #exchange
                $queue_name  #routing key
            );
        } catch (Exception $e) {
            return false;
        }
    }

    public function PushRabbitMQ($queue_name, $data = []){
        
        $conn = $this->_getRabbitMQInstance();
        if(!$conn){return false;}

        $channel = $conn->channel();
        $channel->queue_declare(
            $queue_name, #queue name - Queue names may be up to 255 bytes of UTF-8 characters
            false,          #passive - can use this to check whether an exchange exists without modifying the server state
            true,           #durable - make sure that RabbitMQ will never lose our queue if a crash occurs - the queue will survive a broker restart
            false,          #exclusive - used by only one connection and the queue will be deleted when that connection closes
            false           #autodelete - queue is deleted when last consumer unsubscribes
        );

        
        
        $msg = new AMQPMessage(json_encode($data));

        try {
            $channel->basic_publish(
                $msg,           #message 
                '',             #exchange
                $queue_name  #routing key
            );
        } catch (Exception $e) {
            return false;
        }
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

    public function getLocate(){

        $headers = getallheaders();
        $locate = "";
        if(isset($headers['Locate']) && !empty($headers['Locate'])){
            $locate = $headers['Locate'];
        }
        return $locate;
    }
    public function _ResponseData($data = [], $additional = []) {
        $returnData = [
            'error'         => $this->_error,
            'error_code'    => $this->_error_code,
            'message'       => $this->_message,
            'error_message' => $this->_error_message,
            'data'          => $data
        ];
        
        $returnData = array_merge($returnData, $this->_additional);
        $returnData = array_merge($returnData, $additional);
        

        if($this->_json){
            return Response::json($returnData);
        }
        return $returnData;
    }


	protected function setupLayout()
	{
		if ( ! is_null($this->layout))
		{
			$this->layout = View::make($this->layout);
		}
	}

    public function facebook($access_token = ""){
        $this->facebook = new Facebook\SDK(array(
            'appId'  => Config::get('facebook.appId'), // 451866784967740 //377802115738753 
            'secret' => Config::get('facebook.secret'), // d95f4817d6e78784545986f673077938 //a784edeb37fde10f789bfd5f688db847
        ));
        if(!empty($access_token)){
            $this->facebook = $this->facebook->setAccessToken($access_token);
        }else {
            $_user = $this->UserInfo();
            if(!empty($_user) && $_user['access_token']){
               $this->facebook = $this->facebook->setAccessToken($_user['access_token']); 
            }
        }

        return $this->facebook;
    }
    
    public function UserInfo()
	{   
        if (Session::has('user_info'))
        {
            $User       = Session::get('user_info');
            $this->user = $User;
        }
        
        return $this->user;
	}
    
    public function CacheData($key){
        if(!empty($key)){
            if(Cache::has($key)){
                return Cache::get($key);
            }else{
                switch ($key){
                    case 'token' : 
                        $Data = json_decode(cURL::post('http://services.boxme.vn/oauth/token/access',array('client_id' => 'itest','client_secret' => 'itest', 'grant_type' => 'patner_credentials')),1);
                        break;
                    default :
                        break;
                }
                
                if(isset($Data) && !empty($Data['access_token'])){ 
                    Cache::forever($key, $Data);
                    return Cache::get($key);
                }
            }
        }
        return false;
    }
    
    public function RemoveCache($key){
        Cache::forget($key);
        return;
    }
    
    public function Encrypt32($str){
        if(!empty($str)){
            return md5(Crypt::encrypt($str));
        }
        return false;
    }
    
    public function getBarcode($str){
        if(!empty($str)){
            return \DNS1D::getBarcodePNG($str, "C128",1,40);
        }
        return false;
    }

    public function getMasterId($CountryId){
        return accountingmodel\MerchantModel::where('level',10)->where('country_id',$CountryId)->first();
    }

    public function getCourier(){
        $Courier    = [];
        if (Cache::has('list_courier_cache')){
            $Courier    = Cache::get('list_courier_cache');
        }else{
            $listCourier    = CourierModel::all(array('id','name'));
            if(!$listCourier->isEmpty()){
                foreach($listCourier as $val){
                    $Courier[(int)$val['id']]   = $val['name'];
                }
                Cache::put('list_courier_cache', $Courier, 1440);
            }
        }
        return $Courier;
    }

    public function getListCourier(){
        $Courier    = [];
        if (Cache::has('list_courier__cache')){
            $Courier    = Cache::get('list_courier__cache');
        }else{
            $listCourier    = CourierModel::all(array('id','description', 'name', 'logo'));
            if(!$listCourier->isEmpty()){
                foreach($listCourier as $val){
                    $Courier[(int)$val['id']]   = $val;
                }
                Cache::put('list_courier__cache', $Courier, 1440);
            }
        }
        return $Courier;
    }


    public function SwearFilter($text){
        $filterWords = [
            'dkm',
            'địt',
            'lồn',
            'con cặc',
            'tao',
            'chúng mày',
            'chung mày',
            'lũ chúng mày',
            'bọn mày',
            'chung may',
            'cụ mày',
            'bố mày',
            'cụ chúng mày',
            'im mồm', 
            'im mom',
            'câm mồm',
            'câm',
            'mẹ chúng',
            'con mẹ',
            'bố chúng',
            'thằng bố',
            'con chó',
            'chó chết',
            'du ma',
            'đụ má',
            'chết',
            'dmm',
            'dit con me',
            'con me',
            'dit',
            'địt cụ',
            'thằng cụ',
            'Mả cha',
            'mả cha',
            'tổ cha',
            'Tổ cha',
            'mẹ nhà',
            'mẹ nhà',
            'tổ cụ',
            'Tổ cụ'
        ];
        $filterCount = sizeof($filterWords);
        for($i=0; $i < $filterCount; $i++){
        $text = preg_replace('/\b'.$filterWords[$i].'\b/ie',"str_repeat('*',strlen('$0'))",$text);
        }
        return $text;
    }
    
    public function getService(){
        $Service    = [];
        if (Cache::has('list_service_cache')){
            $Service    = Cache::get('list_service_cache');
        }else{
            $listService    = CourierServiceModel::all(array('id','name'));
            if(!$listService->isEmpty()){
                foreach($listService as $val){
                    $Service[(int)$val['id']]   = $val['name'];
                }
                Cache::forever('list_service_cache', $Service);
            }
        }
        return $Service;
    }

    public function getCity(){
        $City   = [];
        if (Cache::has('list_city_cache')){
            $City    = Cache::get('list_city_cache');
        }else{
            $listCity           = CityModel::all(array('id','city_name'));
            if(!$listCity->isEmpty()){
                foreach($listCity as $val){
                    $City[(int)$val['id']]   = $val['city_name'];
                }
                Cache::put('list_city_cache', $City, 1440);
            }
        }
        return $City;
    }

    public function getCityGlobal($country, $list_id = []){
        $City       = [];
        $country    = array_unique($country);
        try {
            $listCity           = \CityGlobalModel::whereIn('country_id', $country);

            if(!empty($list_id)){
                $listCity = $listCity->whereIn('id', $list_id);
            }

            $listCity = $listCity->select(array('id','city_name'))->get()->toArray();

            if(!empty($listCity)){
                foreach($listCity as $val){
                    $City[(int)$val['id']]   = $val['city_name'];
                }
            }
        } catch (Exception $e) {
            print_r($e);die;
        }
        
        return $City;
    }
    
    public function getProvince($ListProvinceId){
        $Province      = [];
        $DistrictModel = new DistrictModel;
        $ListProvince  =  $DistrictModel::whereIn('id',$ListProvinceId)->get(['id','district_name'])->toArray();
        if(!empty($ListProvince)){
            foreach($ListProvince as $val){
                $Province[$val['id']]   = $val['district_name'];
            }
        }
        return $Province;
    }

    public function getWard($ListWardId){
        $Ward      = [];
        $WardModel = new WardModel;
        $ListWard  =  $WardModel::whereIn('id',$ListWardId)->get(['id','ward_name'])->toArray();
        if(!empty($ListWard)){
            foreach($ListWard as $val){
                $Ward[$val['id']]   = $val['ward_name'];
            }
        }
        return $Ward;
    }

    public function getCountry($ListCountryId){
        $Ward      = [];
        $CountryModel = new CountryModel;
        $ListCountry  =  $CountryModel::whereIn('id',$ListCountryId)->get(['id','country_name'])->toArray();
        if(!empty($ListCountry)){
            foreach($ListCountry as $val){
                $Ward[$val['id']]   = $val['country_name'];
            }
        }
        return $Ward;
    }

    public function getStatus(){
        $Status = [];
        if (Cache::has('list_status_cache')){
            $Status    = Cache::get('list_status_cache');
        }else{
            $StatusModel          = new metadatamodel\OrderStatusModel;
            $ListStatus           = $StatusModel::get(['code','name'])->toArray();
            if(!empty($ListStatus)){
                foreach($ListStatus as $val){
                    $Status[(int)$val['code']]   = $val['name'];
                }
                Cache::put('list_status_cache', $Status, 1440);
            }
        }
        return $Status;
    }

    public function check_privilege($code, $action){
        $UserInfo   = (array) $this->UserInfo();

        if(!empty($UserInfo) && (($UserInfo['privilege'] == 2) ||  (isset($UserInfo['group_privilege'][$code]) && $UserInfo['group_privilege'][$code][$action] == 1))){
            return true;
        }
        return false;
    }

    /*Predis Journey*/
    public function PredisJourney($IdLog){
         $this->PushJourneyProcess('JourneyProcess', ['id'=> $IdLog]);
         return false;
    }

    public function RabbitJourney($IdLog){
         $this->PushJourneyProcess('JourneyProcess', ['id'=> $IdLog]);
         return false;
    }

    /*Predis Weight*/
    public function PredisWeight($IdLog){
        \Predis\Autoloader::register();
        //Now we can start creating a redis client to publish event
        try{
            //Now we got redis client connected, we can publish event (send event)
            $redis = new \Predis\Client(array(
                "scheme" => "tcp",
                "host" => "10.0.20.164",
                "port" => 6788
            ));

            $redis->publish("ChangeWeight", $IdLog);
        }catch (Exception $e){

        }
    }

    /*
     * Predis accept lading
     */
    public function PredisAcceptLading($TrackingCode){
        \Predis\Autoloader::register();
        //Now we can start creating a redis client to publish event
        try{
            //Now we got redis client connected, we can publish event (send event)
            $redis = new \Predis\Client(array(
                "scheme" => "tcp",
                "host" => "10.0.20.164",
                "port" => 6788
            ));

            $redis->publish("AcceptOrder", $TrackingCode);
        }catch (Exception $e){

        }
    }

    /*
     * Predis accept status Boxme
     */
    public function PredisAcceptBoxme($Id){
        \Predis\Autoloader::register();
        //Now we can start creating a redis client to publish event
        try{
            //Now we got redis client connected, we can publish event (send event)
            $redis = new \Predis\Client(array(
                "scheme" => "tcp",
                "host" => "10.0.20.164",
                "port" => 6788
            ));

            $redis->publish("OrderBoxme", $Id);
        }catch (Exception $e){

        }
    }

    /*Predis Report Replay*/
    public function PredisReportReplay($Id){
        \Predis\Autoloader::register();
        //Now we can start creating a redis client to publish event
        try{
            //Now we got redis client connected, we can publish event (send event)
            $redis = new \Predis\Client(array(
                "scheme" => "tcp",
                "host" => "10.0.20.164",
                "port" => 6788
            ));

            $redis->publish("ReportReplay", $Id);
        }catch (Exception $e){

        }
    }

    /*Predis Report Return*/
    public function PredisReportReturn($Id){
        \Predis\Autoloader::register();
        //Now we can start creating a redis client to publish event
        try{
            //Now we got redis client connected, we can publish event (send event)
            $redis = new \Predis\Client(array(
                "scheme" => "tcp",
                "host" => "10.0.20.164",
                "port" => 6788
            ));

            $redis->publish("ConfirmReturn", $Id);
        }catch (Exception $e){

        }
    }

    /*Process Journey*/
    public function PredisProcessJourney($Id){
        $this->PushJourneyProcess('JourneyDelivery', ['id'=> $Id]);
        return false;
    }
    /*Process Pickup*/
    public function PredisProcessPickup($Id){
        $this->PushJourneyProcess('JourneyPickup', ['id'=> $Id]);
        return false;
    }

    /*
     * sinh mã 6 ký tự
     */
    public function GenerateCode($UserId){
        $md5 = md5(uniqid($UserId, true).microtime());

        $crc = crc32((string)$md5);
        if ($crc & 0x80000000) {
            $crc ^= 0xffffffff;
            $crc += 1;
        }

        $code = abs($crc);

        if(strlen($code) != 6)
            return $this->GenerateCode($UserId);

        return $code;
    }

    /*
     * Send Sms
     */
    public function __SendSms($toPhone, $content, $priority = 0){
        $toPhone = str_replace(array(';','.',' ','/','|'), ',', $toPhone);

        $arrPhone = array();
        if($toPhone != ''){
            $arrPhone = explode(',', $toPhone);
        }

        Input::Merge([
            'to_phone'   => $arrPhone[0],
            'content'    => $content,
            'priority'   => $priority
        ]);

        $SmsController  = new \SmsController;
        $SmsController->postSendsms(false);
    }

    /**
     * Security
     */
    public function __check_security($UId, $Code, $Type){
        if(empty($Code)){
            return [
                'error'     => true,
                'message'   => 'Bạn chưa nhập mã xác nhận'
            ];
        }

        $Security   = \sellermodel\SecurityLayersModel::where('user_id', $UId)->where('type',$Type)
            ->where('active',1)->where('time_create','>=',$this->time() - 3600)->where('code',$Code)->first();

        if(!isset($Security->id)){
            return [
                'error'     => true,
                'message'   => 'Mã bảo mật không chính xác hoặc đã quá hạn'
            ];
        }

        return ['error' => false, 'security' => $Security];
    }

    /**
     * Check time edit kpi
     */
    public function __check_time_edit_kpi($Time, $TimeRevenue){
        if(date('d', $Time) < 25){
            $StartTime      = strtotime(date('Y-m-25 00:00:00', strtotime("-1 month", $Time)));
            $EndTime        = strtotime(date('Y-m-25 00:00:00', $Time));
        }else{
            $StartTime      = strtotime(date('Y-m-25 00:00:00', $Time));
            $EndTime        = strtotime(date('Y-m-25 00:00:00', strtotime("+1 month", $Time)));
        }
        if($TimeRevenue <= $StartTime){ // thời gian giới hạn bắt đầu từ
            return ['error'         => true,'error_message' => "Cannot Edit!."];
        }

        if($this->time() >= $EndTime){
            return ['error'         => true,'error_message' => "Cannot Edit!."];
        }

        if($TimeRevenue >= $EndTime){ // thời gian giới hạn bắt đầu đến
            return ['error'         => true,'error_message' => "Cannot Edit!."];
        }

        return ['error'         => false,'error_message' => "SUCCESS"];
    }
}
