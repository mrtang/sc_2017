<?php
namespace ops;

use ordermodel\OrdersModel;
use sellermodel\UserInfoModel;
use ordermodel\AddressModel;
use ordermodel\StatusModel;
use sellermodel\UserInventoryModel;
use omsmodel\PipeJourneyModel;
use omsmodel\SellerModel;
use omsmodel\LogSellerModel;
use CourierPostOfficeModel;
use ordermodel\PostOfficeModel;
use ticketmodel\ReferModel;
use User;
use WardModel;
use CourierStatusAcceptModel;
use Elasticsearch\Client;
use OrderDetailModel;

use ElasticBuilder;

class OrderESCtrl extends BaseCtrl
{
    private $error              = true;
    private $message            = 'error';
    private $total              = 0;
    private $total_all          = 0;
    private $total_group        = [];
    private $data               = [];

    private $list_city_id       = [];
    private $list_district_id   = [];
    private $list_ward_id       = [];

    private $list_to_address    = [];
    private $list_from_address  = [];
    private $list_journey       = [];
    private $note               = [];

    private $range_filter     = [];
    private $term_filter      = [];
    private $terms_filter     = [];

    private $list_postoffice  = [];


    private $range_sale         = [
        0      => [
            'hard'        => 3000000,
            'commission'  => 0.03
        ],
        1      => [
          'hard'        => 3000000,
          'commission'  => 0.03
        ],
        2      => [
          'hard'        => 3500000,
          'commission'  => 0.05
        ],
        3      => [
            'hard'        => 4000000,
            'commission'  => 0.07
        ],
        4      => [
            'hard'        => 5000000,
            'commission'  => 0.09
        ],
        5       => [
            'hard'        => 6000000,
            'commission'  => 0.1
        ]
    ];

    private $range_lead     = [
        0      => [
            'hard'        => 5000000,
            'commission'  => 0.003
        ],
        1      => [
            'hard'        => 5000000,
            'commission'  => 0.003
        ],
        2      => [
            'hard'        => 5500000,
            'commission'  => 0.005
        ],
        3      => [
            'hard'        => 6000000,
            'commission'  => 0.007
        ],
        4      => [
            'hard'        => 6500000,
            'commission'  => 0.009
        ],
        5       => [
            'hard'        => 7000000,
            'commission'  => 0.012
        ]
    ];

    function __construct(){
        //set_time_limit (180);
    }

    public function getEsModel(){
        $SearchQuery = [
            'index' => 'bxm_orders',
            'type'  => 'orders',
            'body'  => [
                'filter'=> [],
                "sort" => ["id" => "desc"]

            ]
        ];

        $this->range_filter     = [];
        $this->term_filter      = [];
        $this->terms_filter     = [];

        $PipeJourneyModel   = new PipeJourneyModel;

        $TimeCreateStart    = Input::has('create_start')        ? (int)Input::get('create_start')           : 0; // time_create start   time_stamp
        $TimeCreateEnd      = Input::has('create_end')          ? (int)Input::get('create_end')             : 0; // time_create end
        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')           : 0; // time_accept start
        $TimeAcceptEnd      = Input::has('accept_end')          ? (int)Input::get('accept_end')             : 0; // time_accept end
        $TimeSuccessStart   = Input::has('success_start')       ? (int)Input::get('success_start')          : 0; // time_accept start
        $TimeSuccessEnd     = Input::has('success_end')         ? (int)Input::get('success_end')            : 0; // time_accept end
        $PickupStart        = Input::has('pickup_start')        ? (int)Input::get('pickup_start')           : 0; // time_pickup start
        $PickupEnd          = Input::has('pickup_end')          ? (int)Input::get('pickup_end')             : 0; // time_pickup end
        $NewCustomerFrom    = Input::has('new_customer_from')   ? (int)Input::get('new_customer_from')      : 0;
        $ServiceId          = Input::has('service')             ? (int)Input::get('service')                : 0;
        $Domain             = Input::has('domain')              ? trim(Input::get('domain'))                : 0;
        $KeyWord            = Input::has('keyword')             ? trim(Input::get('keyword'))               : 0;
        $Loyalty            = Input::has('loyalty')           ? (int)Input::get('loyalty')                  : null;
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $PipeStatus         = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))           : '';
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TypeProcess        = Input::has('type-process')        ? (int)Input::get('type-process')           : 1;
        $FromUser           = Input::has('from_user')           ? (int)Input::get('from_user')              : 0;
        $ToUser             = Input::has('to_user')             ? trim(Input::get('to_user'))               : '';
        $FromCity           = Input::has('from_city')           ? (int)Input::get('from_city')              : 0;
        $FromDistrict       = Input::has('from_district')       ? (int)Input::get('from_district')          : 0;
        $ToCity             = Input::has('to_city')             ? (int)Input::get('to_city')                : 0;
        $ToDistrict         = Input::has('to_district')         ? (int)Input::get('to_district')            : 0;
        $Tag                = Input::has('tag')                 ? strtolower(trim(Input::get('tag')))       : 0;
        /*
         *  Lấy chậm
         */
        $Slow               = Input::has('slow')                ? (int)Input::get('slow')                       : 0;
        /*
         * Cập nhật chậm
         */
        $LastUpdate         = Input::has('last_update')         ? (int)Input::get('last_update')                : 0;

        /*
         * Giá trị cao
         */
        $Amount             = Input::has('amount')              ? (int)Input::get('amount')                     : 0;

        /*
         * Giá trị cao
         */
        $Weight             = Input::has('weight')              ? (int)Input::get('weight')                     : 0;

        /*
         * Vượt cân
         */
        $OverWeight         = Input::has('over_weight')         ? (int)Input::get('over_weight')                : 0;
        $PostOfficeId       = Input::has('post_office_id')      ? Input::get('post_office_id')                  : '';





        // Cho dien tu
        $UserInfo           = $this->UserInfo();
        if(isset($UserInfo['domain']) && !empty($UserInfo['domain'])){
            $Domain = $UserInfo['domain'];
        }

        if(isset($Loyalty)){
            $ListUser       = \loyaltymodel\UserModel::where('level',$Loyalty)->remember(60)->lists('user_id');

            // ko có dữ liệu , return []
            if(empty($ListUser)){
                $this->error = true;
                return;
            }
        }

        if(!empty($KeyWord)){
            $UserModel      = new User;

            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $UserModel          = $UserModel->where('email',$KeyWord);
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $UserModel          = $UserModel->where('phone',$KeyWord);
            }else{ // search code
                $UserModel          = $UserModel->where('fullname',$KeyWord);
            }

            $ListUserSearch = $UserModel->lists('id');
            if(empty($ListUserSearch)){
                $this->error = true;
                return;
            }else{
                if(!empty($ListUser)){
                    $ListUser   = array_intersect($ListUser, $ListUserSearch);
                }else{
                    $ListUser   = $ListUserSearch;
                }
            }

            if(empty($ListUser)){
                $this->error = true;
                return;
            }
        }elseif($FromUser > 0){
            $ListUser[] = $FromUser;
        }

        if(!empty($NewCustomerFrom)){
            $UserAdminModel = new \omsmodel\CustomerAdminModel;
            $ListId         = $UserAdminModel->where('first_order_time', '>=', $NewCustomerFrom)->get()->lists('user_id');

            if(empty($ListId)){
                $this->error = true;
                return;   
            }
            
            if(!empty($ListUser)){
                $ListUser   = array_intersect($ListUser, $ListId);
            }else{
                $ListUser   = $ListId;
            }
        }

        if(!empty($ListUser)){
            $this->terms_filter['from_user_id'] = $ListUser;
        }

        if(!empty($ToUser)){
            if (filter_var($ToUser, FILTER_VALIDATE_EMAIL)){  // search email
                $this->term_filter['to_email'] = $ToUser;
                
            }elseif(filter_var((int)$ToUser, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $this->term_filter['to_phone'] = $ToUser;
            }else{ // search code
                $this->term_filter['to_name']  = $ToUser;
            }
        }

        if (!empty($PostOfficeId)) {
            if ($PostOfficeId == 'ALL') {
                $this->range_filter['post_office_id']['gt'] = 0;
            }else {
                $this->term_filter['post_office_id'] = $PostOfficeId;
                //$Model          = $Model->where('post_office_id', $PostOfficeId);
            }
        }

        if(!empty($Domain)){
            $this->term_filter['domain'] = $Domain;
            //$Model          = $Model->where('domain',$Domain);
        }

        if(!empty($ServiceId)){
            $this->term_filter['service_id'] = $ServiceId;
        }

        if(!empty($FromDistrict)){

            $this->term_filter['from_district_id'] = $FromDistrict;
        }elseif(!empty($FromCity)){

            $this->term_filter['from_city_id'] = $FromCity;
        }

        if(!empty($ToDistrict)){
            if(!empty($ListLocation)){ // nếu có tìm theo location
                $ToDistrict = array_intersect($ToDistrict, $ListLocation);
            }
            if(empty($ToDistrict)){
                $this->error = true;
                return;
            }

            $this->terms_filter['to_district_id'] = $ToDistrict;

        }elseif(!empty($ToCity)){
            Input::merge(['city' => $ToCity]);
            $ListDistrictId = $this->getDistrict(false);
            if(!empty($ListDistrictId)){
                $ListId = [];
                foreach($ListDistrictId as $val){
                    $ListId[]   = (int)$val['id'];
                }

                if(!empty($ListLocation)){ // nếu có tìm theo location
                    $ListId = array_intersect($ListId, $ListLocation);
                }
                if(empty($ListId)){
                    $this->error = true;
                    return;
                }
                $this->terms_filter['to_district_id'] = $ListId;
            }else{
                $this->error = true;
                return;
            }
        }elseif(!empty($ListLocation)){

            $this->terms_filter['to_district_id'] = $ListLocation;
        }

        /*if(!empty($Tag)){
            $Model          = $Model->where('tag','LIKE', '%'.$Tag.'%');
        }*/
        // More
        if(!empty($TrackingCode)){
            $SearchQuery['body']['query']['query_string']['query'] = "courier_tracking_code:".$TrackingCode." OR tracking_code:".$TrackingCode;
        }

        if(empty($TimeCreateStart) && empty($TimeAcceptStart)){
            $this->error = true;
            return;
        }

        if(!empty($TimeCreateEnd)){
            $this->range_filter['time_create']['lte'] = $TimeCreateEnd;
            
            //$Model              = $Model->where('time_create','<=',$TimeCreateEnd);
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','<=',$TimeCreateEnd);
        }elseif(!empty($TimeAcceptEnd)){
            $this->range_filter['time_create']['lte'] = $TimeAcceptEnd;
            
            //$Model              = $Model->where('time_create',  '<=',   $TimeAcceptEnd);
        }

        if(!empty($TimeCreateStart)){
            if(empty($TimeCreateEnd)){
                $TimeCreateEnd  = $this->time();
            }

            if(($TimeCreateEnd - $TimeCreateStart) > 93*86400){
                $this->error = true;
                return;
            }


            //$Model              = $Model->where('time_create','>=',$TimeCreateStart);
            $this->range_filter['time_create']['gte'] = $TimeCreateStart;

            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$TimeCreateStart);
        }

        if(!empty($TimeAcceptEnd)){
            //$Model          = $Model->where('time_accept','<=',$TimeAcceptEnd);

            $this->range_filter['time_accept']['lte'] = $TimeAcceptEnd;

            if(empty($TimeAcceptEnd)){
                $PipeJourneyModel   = $PipeJourneyModel->where('time_create','<=',$TimeCreateEnd + 86400*30);
            }
        }

        if(!empty($TimeAcceptStart)){
            if(empty($TimeAcceptEnd)){
                $TimeAcceptEnd  = $this->time();
            }

            if(($TimeAcceptEnd - $TimeAcceptStart) > 93*86400){
                $this->error = true;
                return;
            }

            //$Model          = $Model->where('time_accept','>=',$TimeAcceptStart);
            
            $this->range_filter['time_accept']['gte'] = $TimeAcceptStart;

            if(empty($TimeCreateStart)){
                $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$TimeAcceptStart - 86400*30);
            }
        }else{
            /*$Model          = $Model->where(function($query) {
                $query->where('time_accept','>=', $this->time() - 86400*60)
                    ->orWhere('time_accept',0);
            });*/
        }

        if(empty($TimeAcceptStart) && empty($TimeCreateStart)){
            $this->range_filter['time_accept']['gte'] = $this->time() - 86400*30;
            
            //$Model              = $Model->where('time_accept','>=',$this->time() - 86400*30);
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$this->time() - 86400*30);
        }

        if(!empty($TimeSuccessStart)){
            
            $this->range_filter['time_success']['gte'] = $TimeSuccessStart;
            //$Model          = $Model->where('time_success','>=',$TimeSuccessStart);
        }

        if(!empty($TimeSuccessEnd)){
            $this->range_filter['time_success']['lte'] = $TimeSuccessEnd;
            
            //$Model          = $Model->where('time_success','<=',$TimeSuccessEnd);
        }

        if(!empty($PickupStart)){
            $this->range_filter['time_pickup']['gte'] = $PickupStart;
        }

        if(!empty($PickupEnd)){
            $this->range_filter['time_pickup']['lte'] = $PickupEnd;
        }

        if(!empty($Slow)){ // lấy chậm
            $currentHour    = date("G", $this->time());

            if($currentHour < 8){
                $TimeSlow = strtotime(date('Y-m-d 14:00:00', strtotime(' -1 day')));
            }elseif($currentHour >= 8 && $currentHour < 14){
                $TimeSlow = strtotime(date('Y-m-d 14:00:00', strtotime(' -1 day')));
            }elseif($currentHour >= 14 && $currentHour < 18){
                $TimeSlow = strtotime(date('Y-m-d 18:00:00', strtotime(' -1 day')));
            }else{
                $TimeSlow = strtotime(date('Y-m-d 14:00:00'));
            }

            $TimeSlow   -= $Slow*3600;

            $this->range_filter['time_approve']['gte'] = $TimeSlow;
            //$SearchQuery['body']['filter']['range']['time_approve']['lte'] = $TimeSlow;
            //$Model          = $Model->where('time_approve','<=', $TimeSlow);
        }

        if(!empty($LastUpdate)){
            $this->range_filter['time_update']['lte'] = $this->time() - $LastUpdate*3600;
            
            //$Model          = $Model->where('time_update','<=',$this->time() - $LastUpdate*3600);
        }

        if(!empty($Amount)){
            $this->range_filter['total_amount']['gte'] = $Amount;
            
            //$Model          = $Model->where('total_amount','>=',$Amount);
        }

        if(!empty($Weight)){
            $this->range_filter['total_weight']['gte'] = $Weight;
            //$Model          = $Model->where('total_weight','>=',$Weight);
        }

        if(!empty($OverWeight)){
            $MaxOverWeight  = 0;
            $MinOverWeight  = 0;
            if($OverWeight == 5000){
                $MinOverWeight  =   $OverWeight;
            }else{
                $MaxOverWeight  = $OverWeight;
                if(in_array($OverWeight, [250,500])){
                    $MinOverWeight  = $OverWeight - 250;
                }else{
                    $MinOverWeight  = $OverWeight - 500;
                }
            }
            $this->range_filter['over_weight']['gt'] = $MinOverWeight;
            //$Model          = $Model->where('over_weight','>',$MinOverWeight);

            if(!empty($MaxOverWeight)){
                $this->range_filter['over_weight']['lt'] = $MinOverWeight;
                //$Model          = $Model->where('over_weight','<',$MaxOverWeight);
            }
        }

        if(!empty($PipeStatus) && !empty($Group)){
            $PipeStatus = explode(',',$PipeStatus);
            $ListId = [];
            $ListId = $PipeJourneyModel->where('type', $TypeProcess)->where('group_process',$Group)->whereIn('pipe_status', $PipeStatus)->lists('tracking_code');

            if(!empty($ListId)){
                $ListId = array_unique($ListId);
                //$Model  = $Model->whereRaw("id in (". implode(",", $ListId) .")");
                $this->terms_filter['id'] = $ListId;
                //$SearchQuery['body']['query']['terms']['id'] = $ListId;
            }else{
                $this->error = true;
                return;
            }
        }

        
        return $SearchQuery;
        



    }

    private function getModel(){
        $Model              = new OrdersModel;
        $PipeJourneyModel   = new PipeJourneyModel;

        $TimeCreateStart    = Input::has('create_start')        ? (int)Input::get('create_start')           : 0; // time_create start   time_stamp
        $TimeCreateEnd      = Input::has('create_end')          ? (int)Input::get('create_end')             : 0; // time_create end
        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')           : 0; // time_accept start
        $TimeAcceptEnd      = Input::has('accept_end')          ? (int)Input::get('accept_end')             : 0; // time_accept end
        $TimeSuccessStart   = Input::has('success_start')       ? (int)Input::get('success_start')          : 0; // time_accept start
        $TimeSuccessEnd     = Input::has('success_end')         ? (int)Input::get('success_end')            : 0; // time_accept end
        $PickupStart        = Input::has('pickup_start')        ? (int)Input::get('pickup_start')           : 0; // time_pickup start
        $PickupEnd          = Input::has('pickup_end')          ? (int)Input::get('pickup_end')             : 0; // time_pickup end
        $PackageStart       = Input::has('package_start')       ? (int)Input::get('package_start')          : 0;
        $PackageEnd         = Input::has('package_end')         ? (int)Input::get('package_end')            : 0;

        $NewCustomerFrom    = Input::has('new_customer_from')   ? (int)Input::get('new_customer_from')      : 0;

        $ServiceId          = Input::has('service')             ? (int)Input::get('service')                : 0;
        $Domain             = Input::has('domain')              ? trim(Input::get('domain'))                 : 0;
        $KeyWord            = Input::has('keyword')             ? trim(Input::get('keyword'))               : 0;
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';

        $PipeStatus         = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))           : '';
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')           : 1;

        $FromUser           = Input::has('from_user')           ? (int)Input::get('from_user')              : 0;
        $ToUser             = Input::has('to_user')             ? trim(Input::get('to_user'))               : '';

        $FromCity           = Input::has('from_city')           ? (int)Input::get('from_city')              : 0;
        $FromDistrict       = Input::has('from_district')       ? (int)Input::get('from_district')          : 0;

        $ToCity             = Input::has('to_city')             ? (int)Input::get('to_city')                : 0;
        $ToDistrict         = Input::has('to_district')           ? (int)Input::get('to_district')          : 0;

        $Tag                = Input::has('tag')                 ? strtolower(trim(Input::get('tag')))       : 0;


        $IsWeship           = Input::has('is_weship')                 ? Input::get('is_weship')       : "";

        /*
         *  Lấy chậm
         */
        $Slow               = Input::has('slow')                ? (int)Input::get('slow')                       : 0;


         // Loại đơn nội thành
        // 1: Đơn trong ngày
        // 2: Đơn qua ngày

        $TypeNoiThanh       = Input::has('type_noithanh')       ? (int)Input::get('type_noithanh')             : 0;

        // Giao Cham NT
        $SlowDeliveryNT     = Input::has('slow_delivery_nt')    ? (int)Input::get('slow_delivery_nt')             : 0;


        /*
         * Cập nhật chậm
         */
        $LastUpdate         = Input::has('last_update')         ? (int)Input::get('last_update')                : 0;

        /*
         * Giá trị cao
         */
        $Amount             = Input::has('amount')              ? (int)Input::get('amount')                     : 0;

        /*
         * Giá trị cao
         */
        $Weight             = Input::has('weight')              ? (int)Input::get('weight')                     : 0;

        /*
         * Vượt cân
         */
        $OverWeight         = Input::has('over_weight')         ? (int)Input::get('over_weight')                : 0;

        $PostOfficeId       = Input::has('post_office_id')      ? Input::get('post_office_id')                  : '';

        $Location           = Input::has('location')            ? (int)Input::get('location')                   : 0;

        $Loyalty            = Input::has('loyalty')             ? (int)Input::get('loyalty')                      : null;

        $FromCountryId      = Input::has('from_country_id')     ? (int)Input::get('from_country_id')            : 237;
        $Global             = Input::has('global')              ? (int)Input::get('global')                     : null;

        $Model              = $Model::where('from_country_id', $FromCountryId);

        if(!empty($Global)){
            $Model  = $Model->where('to_country_id','<>', $FromCountryId);
        }

        // Cho dien tu
        $UserInfo           = $this->UserInfo();
        if(isset($UserInfo['domain']) && !empty($UserInfo['domain'])){
            $Domain = $UserInfo['domain'];
        }

        if(isset($Loyalty)){
            $ListUser       = \loyaltymodel\UserModel::where('level',$Loyalty)->remember(60)->lists('user_id');

            // ko có dữ liệu , return []
            if(empty($ListUser)){
                $this->error = true;
                return;
            }
        }

        if(!empty($KeyWord)){
            $UserModel      = new User;

            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $UserModel          = $UserModel->where('email',$KeyWord);
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $UserModel          = $UserModel->where('phone',$KeyWord);
            }else{ // search code
                $UserModel          = $UserModel->where('fullname',$KeyWord);
            }

            $ListUserSearch = $UserModel->lists('id');
            if(empty($ListUserSearch)){
                $this->error = true;
                return;
            }else{
                if(!empty($ListUser)){
                    $ListUser   = array_intersect($ListUser, $ListUserSearch);
                }else{
                    $ListUser   = $ListUserSearch;
                }
            }

            if(empty($ListUser)){
                $this->error = true;
                return;
            }
        }elseif($FromUser > 0){
            $ListUser[] = $FromUser;
        }

        if(!empty($NewCustomerFrom)){
            $UserAdminModel = new \omsmodel\CustomerAdminModel;
            $ListId         = $UserAdminModel->where('first_order_time', '>=', $NewCustomerFrom)->get()->lists('user_id');

            if(empty($ListId)){
                $this->error = true;
                return;   
            }
            
            if(!empty($ListUser)){
                $ListUser   = array_intersect($ListUser, $ListId);
            }else{
                $ListUser   = $ListId;
            }
        }

        if(!empty($ListUser)){
            $Model  = $Model->whereRaw("from_user_id in (". implode(",", $ListUser) .")");
        }

        if(!empty($ToUser)){
            if (filter_var($ToUser, FILTER_VALIDATE_EMAIL)){  // search email
                $Model          = $Model->where('to_email',$ToUser);
                
            }elseif(filter_var((int)$ToUser, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $Model          = $Model->where('to_phone',$ToUser);
            }else{ // search code
                $Model          = $Model->where('to_name',$ToUser);
            }
        }

        if (!empty($PostOfficeId)) {
            if ($PostOfficeId == 'ALL') {
                $Model          = $Model->where('post_office_id', '>', 0);
            }else {
                $Model          = $Model->where('post_office_id', $PostOfficeId);
            }
        }

        if(!empty($Domain)){
            $Model          = $Model->where('domain',$Domain);
        }


        if($IsWeship !== ""){
            $Model          = $Model->where('is_weship', $IsWeship);
        }

        if(!empty($ServiceId)){
            $Model          = $Model->where('service_id',$ServiceId);
        }

        if(!empty($FromDistrict)){
            $Model          = $Model->where('from_district_id',$FromDistrict);
        }elseif(!empty($FromCity)){
            $Model          = $Model->where('from_city_id',$FromCity);
        }

        if(!empty($Location)){// 1 nội thành,  2 ngoại thành , 3 liên tỉnh
            if(empty($ToCity)){
                $this->error = true;
                return;
            }

            Input::merge(['city'    => $ToCity]);

            switch ((int)$Location) {
                case 1:
                    Input::merge(['location' => 1]);
                    $ListLocation   = $this->getDistrictByLocation(false);
                    break;
                case 2:
                    Input::merge(['location' => 2]);
                    $ListLocation   = $this->getDistrictByLocation(false);
                    break;
                default:

                    $ListLocationNotIn   = $this->getDistrictByLocation(false);
                    if(!empty($ListLocationNotIn)){
                        $Model          = $Model->whereNotIn('to_district_id',$ListLocationNotIn);
                    }
            }

        }

        if(!empty($ToDistrict)){
            if(!empty($ListLocation)){ // nếu có tìm theo location
                $ToDistrict = array_intersect([$ToDistrict], $ListLocation);
            }
            if(empty($ToDistrict)){
                $this->error = true;
                return;
            }

            $Model          = $Model->where('to_district_id',$ToDistrict);
        }elseif(!empty($ToCity)){
            Input::merge(['city' => $ToCity]);
            $ListDistrictId = $this->getDistrict(false);
            if(!empty($ListDistrictId)){
                $ListId = [];
                foreach($ListDistrictId as $val){
                    $ListId[]   = (int)$val['id'];
                }

                if(!empty($ListLocation)){ // nếu có tìm theo location
                    $ListId = array_intersect($ListId, $ListLocation);
                }
                if(empty($ListId)){
                    $this->error = true;
                    return;
                }
                $Model          = $Model->whereRaw("to_district_id in (". implode(",", $ListId) .")");
            }else{
                $this->error = true;
                return;
            }
        }

        if(!empty($Tag)){
            $Model          = $Model->where('tag','LIKE', '%'.$Tag.'%');
        }

        if(!empty($TrackingCode)){
            if(preg_match("/^O/i", $TrackingCode)){
                $Model          = $Model->where('order_code',$TrackingCode);
            }else{
                $Model          = $Model->where(function($query) use($TrackingCode){
                    $query->where('tracking_code',$TrackingCode)
                        ->orWhere('courier_tracking_code', $TrackingCode);
                });
            }

        }

        if(empty($TimeCreateStart) && empty($TimeAcceptStart)){
            $this->error = true;
            return;
        }

        if(!empty($TimeAcceptStart)){
            if(empty($TimeAcceptEnd)){
                $TimeAcceptEnd  = $this->time();
            }

            if(($TimeAcceptEnd - $TimeAcceptStart) > 93*86400){
                $this->error = true;
                return;
            }

            $Model          = $Model->where('time_accept','>=',$TimeAcceptStart);

            if(empty($TimeCreateStart)){
                $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$TimeAcceptStart - 86400*30);
            }
        }else{
            $Model          = $Model->where(function($query) use($TimeCreateStart) {
                $query->where('time_accept','>=', $TimeCreateStart)
                    ->orWhere('time_accept',0);
            });
        }

        if(!empty($TimeCreateEnd)){
            $Model              = $Model->where('time_create','<=',$TimeCreateEnd);
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','<=',$TimeCreateEnd);
        }elseif(!empty($TimeAcceptEnd)){
            $TimeCreateEnd      = $TimeAcceptEnd;
        }else{
            $TimeCreateEnd  = $this->time();
        }

        if(!empty($TimeCreateStart)){
            if(($TimeCreateEnd - $TimeCreateStart) > 93*86400){
                $this->error = true;
                return;
            }

            $Model              = $Model->where('time_create','>=',$TimeCreateStart);
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$TimeCreateStart);
        }

        if(!empty($TimeAcceptEnd)){
            $Model          = $Model->where('time_accept','<=',$TimeAcceptEnd);
        }else{
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','<=',$TimeCreateEnd + 86400*30);

        }

        if(!empty($TimeSuccessStart)){
            $Model          = $Model->where('time_success','>=',$TimeSuccessStart);
        }

        if(!empty($TimeSuccessEnd)){
            $Model          = $Model->where('time_success','<=',$TimeSuccessEnd);
        }

        if(!empty($PickupStart)){
            $Model          = $Model->where('time_pickup','>=',$PickupStart);
        }

        if(!empty($PickupEnd)){
            $Model          = $Model->where('time_pickup','<=',$PickupEnd);
        }

        if(!empty($PackageStart) || !empty($PackageEnd)){
            $ListTrackingCode  = $this->getPackageByTime($PackageStart, $PackageEnd);
            if(empty($ListTrackingCode)){
                $this->error = true;
                return;
            }

            $Model  = $Model->whereIn('tracking_code', $ListTrackingCode);
        }

        if(!empty($Slow)){ // lấy chậm
            $currentHour    = date("G", $this->time());

            switch ((int)$Slow) {
                case 1:// Trong ngày
                    if($currentHour > 18){// sau 12h
                        $Model  = $Model->where('time_accept','<=',strtotime(date('Y-m-d 16:00:00')))
                                        ->where('time_accept','>',strtotime(date('Y-m-d 16:00:00', strtotime(' -1 day'))));
                    }elseif($currentHour > 12){
                        $Model  = $Model->where('time_accept','<=',strtotime(date('Y-m-d 10:00:00')))
                                        ->where('time_accept','>',strtotime(date('Y-m-d 16:00:00', strtotime(' -1 day'))));
                    }else{
                        $this->error = true;
                        return;
                    }
                    break;
                case 2:// Qua ngày
                    $Model          = $Model->where('time_accept','<=',strtotime(date('Y-m-d 16:00:00', strtotime(' -1 day'))))
                                            ->where('time_accept','>',strtotime(date('Y-m-d 16:00:00', strtotime(' -2 day'))));
                    break;
                default:// Hơn 1 ngày
                    $Model          = $Model->where('time_accept','<=',strtotime(date('Y-m-d 16:00:00', strtotime(' -2 day'))));
                    break;
            }
        }

        if (!empty($TypeNoiThanh)) {
            if ($TypeNoiThanh == 1) {
                $Model = $Model->whereRaw("time_accept < UNIX_TIMESTAMP( CONCAT(DATE(FROM_UNIXTIME(time_accept)) , ' 10:00:00'))");
            }

            if ($TypeNoiThanh == 2) {
                $Model = $Model->whereRaw("time_accept > UNIX_TIMESTAMP( CONCAT(DATE(FROM_UNIXTIME(time_accept)) , ' 10:00:00'))");
            }
        }


        if($SlowDeliveryNT == 1){
            $Model = $Model->where(function ($query){
                $query->orWhereRaw("time_accept < UNIX_TIMESTAMP( CONCAT(DATE(FROM_UNIXTIME(time_accept)) , ' 10:00:00')) AND CASE WHEN time_success = 0 THEN ".$this->time()." > UNIX_TIMESTAMP( DATE_ADD( DATE( FROM_UNIXTIME( time_accept ) ) , INTERVAL 1 DAY)) ELSE  time_success > UNIX_TIMESTAMP( DATE_ADD( DATE( FROM_UNIXTIME( time_accept ) ) , INTERVAL 1 DAY)) END");
                $query->orWhereRaw("time_accept > UNIX_TIMESTAMP( CONCAT(DATE(FROM_UNIXTIME(time_accept)) , ' 10:00:00')) AND CASE WHEN time_success = 0 THEN ".$this->time()." > UNIX_TIMESTAMP( DATE_ADD( DATE( FROM_UNIXTIME( time_accept ) ) , INTERVAL 2 DAY)) ELSE  time_success > UNIX_TIMESTAMP( DATE_ADD( DATE( FROM_UNIXTIME( time_accept ) ) , INTERVAL 2 DAY)) END");
                //$query->orWhereRaw("time_accept > UNIX_TIMESTAMP( CONCAT(DATE(FROM_UNIXTIME(time_accept)) , ' 10:00:00')) AND (time_success = 0 OR time_success > UNIX_TIMESTAMP( DATE_ADD( DATE( FROM_UNIXTIME( time_accept ) ) , INTERVAL 2 DAY)))");
            });
        }



        if(!empty($LastUpdate)){
            $Model          = $Model->where('time_update','<=',$this->time() - $LastUpdate*3600);
        }

        if(!empty($Amount)){
            $Model          = $Model->where('total_amount','>=',$Amount);
        }

        if(!empty($Weight)){
            $Model          = $Model->where('total_weight','>=',$Weight);
        }

        if(!empty($OverWeight)){
            $MaxOverWeight  = 0;
            $MinOverWeight  = 0;
            if($OverWeight == 5000){
                $MinOverWeight  =   $OverWeight;
            }else{
                $MaxOverWeight  = $OverWeight;
                if(in_array($OverWeight, [250,500])){
                    $MinOverWeight  = $OverWeight - 250;
                }else{
                    $MinOverWeight  = $OverWeight - 500;
                }
            }

            $Model          = $Model->where('over_weight','>',$MinOverWeight);
            if(!empty($MaxOverWeight)){
                $Model          = $Model->where('over_weight','<',$MaxOverWeight);
            }
        }

        if(!empty($PipeStatus) && !empty($Group)){
            $PipeStatus = explode(',',$PipeStatus);
            $ListId = $PipeJourneyModel->where('type', $TypeProcess)->where('group_process',$Group)->whereIn('pipe_status', $PipeStatus)->lists('tracking_code');
            if(!empty($ListId)){
                $ListId = array_unique($ListId);
                $Model  = $Model->whereRaw("id in (". implode(",", $ListId) .")");
            }else{
                $this->error = true;
                return;
            }
        }

        return $Model;
    }

    /*
     * get  to_address
     */
    private function getToaddress($ListToAddress){
        $AddressModel   = new AddressModel;
        $ListAddress    = $AddressModel::whereIn('id',$ListToAddress)->get()->toArray();
        if(!empty($ListAddress)){
            foreach($ListAddress as $val){
                $this->list_to_address[(int)$val['id']]    = $val;
                if($val['province_id'] > 0){
                    $this->list_district_id[]   = (int)$val['province_id'];
                }

                if($val['ward_id'] > 0){
                    $this->list_ward_id[]   = (int)$val['ward_id'];
                }
            }
        }
    }

    private function getFromaddress($ListFromAddress){
        $AddressModel   = new UserInventoryModel;
        $ListAddress    = $AddressModel::whereIn('id',$ListFromAddress)->get(['id','name','user_name','phone','zipcode'])->toArray();
        if(!empty($ListAddress)){
            foreach($ListAddress as $val){
                $this->list_from_address[(int)$val['id']]    = $val;
            }
        }
    }

    private function getPostoffice($ListOrderId){
        $OrderPostOffice = PostOfficeModel::whereIn('order_id', $ListOrderId)->where('time_create', '>=', $this->time() - $this->time_limit)->get();
        $ListBCC         = [];

        foreach ($OrderPostOffice as $key => $value) {
            if(!empty($value['to_postoffice_code'])){
                $ListBCC[] = $value['to_postoffice_code'];
            }
        }

        if(!empty($ListBCC)){
            $PostOfficeData = CourierPostOfficeModel::whereIn('bccode', $ListBCC)->get()->toArray();
            $ListPostOffice = [];
            foreach ($PostOfficeData as $key => $value) {
                $ListPostOffice[$value['bccode']] = $value;
            }

            foreach ($OrderPostOffice as $key => $value) {
                if (!empty($ListPostOffice[$value['to_postoffice_code']])) {
                    $this->list_postoffice[$value['order_id']] = $ListPostOffice[$value['to_postoffice_code']];
                }
                
            }
        }
        return $this->list_postoffice;
    }


    /*
     * get list order
     */

    public function getIndex(){
        $itemPage           = 20;
        $this->error        = false;
        $this->message      = 'success';

        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 'ALL';
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')           : 1;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))           : '';
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $LastUpdate         = Input::has('last_update')         ? (int)Input::get('last_update')                : null;
        $Domain             = Input::has('domain')              ? trim(Input::get('domain'))                 : 0;
        $WareHouse          = Input::has('warehouse')           ? Input::get('warehouse')                       : "";


        $Model          = $this->getModel();
        if($this->error){
            $this->error    = false;
            if($Cmd == 'export' && !isset($LastUpdate)){
                return $this->ExportExcel([]);
            }
            return $this->ResponseData();
        }

        if(!empty($ListStatus) && empty($TrackingCode)){
            $ListStatus = explode(',',$ListStatus);
            $Model          = $Model->whereIn('status',$ListStatus);
        }

        if(!empty($WareHouse)){
            $Model          = $Model->where('warehouse',$WareHouse);
        }

        /**
         * get data
         */
        if($Cmd == 'export'){
            if($CourierId != 'ALL'){
                $Model          = $Model->where('courier_id',$CourierId);
            }

            if(!isset($LastUpdate)){
                return $this->ExportExcel($Model);
            }else{
                return $this->ExportExcelBlob($Model);
            }
        }

        /*
         * count
         */

        if($CourierId != 'ALL'){
            $Model          = $Model->where('courier_id',$CourierId);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model       = $Model->skip($offset)->take($itemPage);

            if(!empty($Group)){
                $Model  = $Model->with(['pipe_journey' => function($query) use($Group,$TypeProcess){
                    $query->where('type', $TypeProcess)->where('group_process', $Group)->orderBy('time_create', 'ASC');
                }]);
            }else {
                $Model  = $Model->with(['pipe_journey' => function($query) use($TypeProcess){
                    $query->where('type', $TypeProcess)->orderBy('time_create', 'ASC');
                }]);
            }

            if($Domain == 'boxme.vn'){
                $Model  = $Model->with('_get_package');
            }

            $Data       = $Model->with(['OrderDetail','OrderFulfillment','FromUser'])->orderBy('time_create','DESC')->get()->toArray();
            if(!empty($Data)){
                $ListToAddress    = [];
                $ListFromAddress  = [];
                $ListOrderId      = [];

                foreach($Data as $key => $val){
                    $Data[$key]['pipe_status'] = 0;

                    $ListOrderId[] = $val['id'];

                    if($val['from_city_id'] > 0){
                        $this->list_city_id[]   = (int)$val['from_city_id'];
                    }
                    if($val['to_city_id'] > 0){
                        $this->list_city_id[]   = (int)$val['to_city_id'];
                    }

                    if($val['from_district_id'] > 0){
                        $this->list_district_id[]   = (int)$val['from_district_id'];
                    }
                    if($val['to_district_id'] > 0){
                        $this->list_district_id[]   = (int)$val['to_district_id'];
                    }

                    if($val['from_ward_id'] > 0){
                        $this->list_ward_id[]   = (int)$val['from_ward_id'];
                    }
                    if($val['to_address_id'] > 0){
                        $ListToAddress[]   = (int)$val['to_address_id'];
                    }
                    if($val['from_address_id'] > 0){
                        $ListFromAddress[]  = (int)$val['from_address_id'];
                    }

                    if(!empty($val['pipe_journey'])){
                        foreach($val['pipe_journey'] as $v){
                            $Data[$key]['pipe_status'] = (int)$v['pipe_status'];
                        }
                    }

                }

                if (!empty($ListOrderId)) {
                    $this->getPostoffice($ListOrderId);
                }

                if(!empty($ListToAddress)){
                    $ListToAddress = array_unique($ListToAddress);
                    $this->getToaddress($ListToAddress);
                }

                if(!empty($ListFromAddress)){
                    $ListFromAddress = array_unique($ListFromAddress);
                    $this->getFromaddress($ListFromAddress);
                }

                if(!empty($this->list_city_id)){
                    $this->list_city_id = array_unique($this->list_city_id);
                    $this->list_city_id = $this->getCityById($this->list_city_id);
                }

                if(!empty($this->list_district_id)){
                    $this->list_district_id = array_unique($this->list_district_id);
                    $this->list_district_id = $this->getProvince($this->list_district_id);
                }

                if(!empty($this->list_ward_id)){
                    $this->list_ward_id = array_unique($this->list_ward_id);
                    $this->list_ward_id = $this->getWard($this->list_ward_id);
                }

                $this->data = $Data;
            }
        }
        return $this->ResponseData();
    }

    public function getOrder(){
        $itemPage           = 20;
        $this->error        = false;
        $this->message      = 'success';

        $page               = Input::has('page')                ? (int)Input::get('page')                               : 1;
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                            : 'ALL';
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                               : '';
        $Group              = Input::has('group')               ? (int)Input::get('group')                              : '';
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')                       : 1;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))                       : '';
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code')))         : '';
        $WareHouse          = Input::has('warehouse')           ? Input::get('warehouse')                               : "";


        $Model          = $this->getModel();
        if($this->error){
            return $this->ResponseData();
        }

        if(!empty($ListStatus) && empty($TrackingCode)){
            $ListStatus = explode(',',$ListStatus);
            $Model          = $Model->whereIn('status',$ListStatus);
        }

        if(!empty($WareHouse)){
            $Model          = $Model->where('warehouse',$WareHouse);
        }

        if($CourierId != 'ALL'){
            $Model          = $Model->where('courier_id',$CourierId);
        }

        /**
         * get data
         */
        if($Cmd != 'export'){
            $TotalModel     = clone $Model;
            $this->total    = $TotalModel->count();
            if(empty($this->total)){
                return $this->ResponseData();
            }

            $offset         = ($page - 1)*$itemPage;
            $Model          = $Model->skip($offset)->take($itemPage);
            if(!empty($Group)){
                $Model  = $Model->with(['pipe_journey' => function($query) use($Group,$TypeProcess){
                    $query->where('type', $TypeProcess)->where('group_process', $Group)->orderBy('time_create', 'ASC');
                }]);
            }else {
                $Model  = $Model->with(['pipe_journey' => function($query) use($TypeProcess){
                    $query->where('type', $TypeProcess)->orderBy('time_create', 'ASC');
                }]);
            }
        }else{
            Input::merge(['group' => 4]);
            $GroupStatus    = $this->getStatusByGroup(false);
            if($Group == 29){ // Phát thất bại, lấy thêm thời gian phát không thành công chờ xử lý
                $Model = $Model->with(['OrderStatus' => function($query) use($GroupStatus){
                    $query->whereIn('status', $GroupStatus[29])->orderBy('time_create', 'ASC');
                }]);
            }elseif(in_array($Group, [31,32,36])){
                $GroupStatus[29][]  = 60;
                $GroupStatus[29][]  = 61;
                $Model = $Model->with(['OrderStatus' => function($query) use($GroupStatus){
                    $query->whereIn('status', $GroupStatus[29])->orderBy('time_create', 'ASC');
                }]);
            }
        }


        $ListToAddress    = [];
        $ListFromAddress  = [];
        $ListOrderId      = [];
        $ListCity         = [];
        $ListDistrict     = [];
        $ListWard         = [];
        $Data             = [];
        $Model->with(['OrderDetail','__post_office', 'OrderFulfillment', 'FromUser'])
            ->chunk('1000', function($query) use(&$Data, &$ListCity,&$ListDistrict, &$ListWard, &$ListOrderId, &$ListToAddress, &$ListFromAddress){
            foreach($query as $val){
                $val            = $val->toArray();
                $ListOrderId[]  = $val['id'];

                if($val['from_city_id'] > 0){
                    $ListCity[]   = (int)$val['from_city_id'];
                }
                if($val['to_city_id'] > 0){
                    $ListCity[]   = (int)$val['to_city_id'];
                }

                if($val['from_district_id'] > 0){
                    $ListDistrict[]   = (int)$val['from_district_id'];
                }
                if($val['to_district_id'] > 0){
                    $ListDistrict[]   = (int)$val['to_district_id'];
                }

                if($val['from_ward_id'] > 0){
                    $ListWard[]   = (int)$val['from_ward_id'];
                }
                if($val['to_address_id'] > 0){
                    $ListToAddress[]   = (int)$val['to_address_id'];
                }
                if($val['from_address_id'] > 0){
                    $ListFromAddress[]  = (int)$val['from_address_id'];
                }

                if(isset($val['pipe_journey']) && !empty($val['pipe_journey'])){
                    foreach($val['pipe_journey'] as $v){
                        $val['pipe_status'] = (int)$v['pipe_status'];
                    }
                }

                $Data[]         = $val;
            }
        });

        $this->list_ward_id = $ListWard;

        if(!empty($ListToAddress)){
            $ListToAddress = array_unique($ListToAddress);
            $this->getToaddress($ListToAddress);
        }

        if(!empty($ListFromAddress)){
            $ListFromAddress = array_unique($ListFromAddress);
            $this->getFromaddress($ListFromAddress);
        }

        if(!empty($ListCity)){
            $ListCity = array_unique($ListCity);
            $this->list_city_id = $this->getCityById($ListCity);
        }

        if(!empty($ListDistrict)){
            $ListDistrict = array_unique($ListDistrict);
            $this->list_district_id = $this->getProvince($ListDistrict);
        }

        if($Cmd != 'export'){
            if (!empty($ListOrderId)) {
                $this->getPostoffice($ListOrderId);
            }
        }


        if(!empty($this->list_ward_id)){
            $this->list_ward_id = array_unique($this->list_ward_id);
            $this->list_ward_id = $this->getWard($this->list_ward_id);
        }
        $this->data = $Data;

        return $this->ResponseData();
    }

    public function getEsIndex(){
        $itemPage           = 20;
        $this->error        = false;
        $this->message      = 'success';

        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 'ALL';
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TypeProcess        = Input::has('type-process')        ? (int)Input::get('type-process')           : 1;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))           : '';
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';

        $SearchQuery        = $this->getEsModel();
        if($this->error){
            $this->error    = false;
            if($Cmd == 'export'){
                return $this->ExportExcel([]);
            }
            return $this->ResponseData();
        }

        if(!empty($ListStatus) && empty($TrackingCode)){
            $ListStatus = explode(',',$ListStatus);
            $this->terms_filter['status'] = $ListStatus;
        }

        /**
         * get data
         */
        if($Cmd == 'export'){
            if($CourierId != 'ALL'){
                $this->term_filter['courier_id'] = $CourierId;
            }
            return $this->ExportExcel($SearchQuery);
        }

        /*
         * count
         */

        if($CourierId != 'ALL'){
            $this->term_filter['courier_id'] = $CourierId;
        }

        /*$TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();*/

        $offset     = ($page - 1)*$itemPage;

        $SearchQuery['size'] = $itemPage;
        $SearchQuery['from'] = $offset;

        if(!empty($this->range_filter)){
            $SearchQuery['body']['filter']['bool']['must'][] = [
                'range' => $this->range_filter
            ];
        }

        /*if(!empty($this->term_filter)){

            $SearchQuery['body']['filter']['bool']['must'][] = [
                'term'  => $this->term_filter
            ];
        }*/

        foreach ($this->term_filter as $key => $value) {   
            $SearchQuery['body']['filter']['bool']['must'][] = [
                'term' => [
                    $key => $value
                ]
            ];
        }

        foreach ($this->terms_filter as $key => $value) {   
            $SearchQuery['body']['filter']['bool']['must'][] = [
                'terms' => [
                    $key => $value
                ]
            ];
        }

        $Data = $this->ExecuteES($SearchQuery);


        if ($Data instanceof Response) {
            return $Data;
        }

        $this->total = $this->_total;

        if($this->total > 0){

            $list_item_id = [];
            $list_user_id = [];
            $PipeJouney   = [];
            $OrderDetail  = [];
            $FromUser     = [];

            $ListToAddress    = [];
            $ListFromAddress  = [];

            foreach ($Data as $key => $value) {
                $list_item_id[]             = $value['id'];
                $list_user_id[]             = $value['from_user_id'];

                if($value['from_district_id'] > 0){
                    $this->list_district_id[]   = (int)$value['from_district_id'];
                }
                if($value['from_ward_id'] > 0){
                    $this->list_ward_id[]   = (int)$value['from_ward_id'];
                }
                if($value['to_address_id'] > 0){
                    $ListToAddress[]   = (int)$value['to_address_id'];
                }
                if($value['from_address_id'] > 0){
                    $ListFromAddress[]  = (int)$value['from_address_id'];
                }

                $Data[$key]['pipe_status']  = 0;
            }




            if(!empty($Group)){

                $PipeJouney = PipeJourneyModel::where('type', $TypeProcess)->whereIn('tracking_code', $list_item_id)->where('group_process', $Group)->orderBy('time_create', 'ASC')->get()->toArray();
            }else {
                $PipeJouney = PipeJourneyModel::where('type', $TypeProcess)->whereIn('tracking_code', $list_item_id)->orderBy('time_create', 'ASC')->get()->toArray();
            }




            $Detail = OrderDetailModel::whereIn('order_id', $list_item_id)->get()->toArray();
            $FromUser = User::whereIn('id', $list_user_id)->select(['id','email','fullname','phone'])->get()->toArray();



            
            

            foreach($Data as $key => $value){
                foreach ($Detail as $k => $val) {
                    if ($val['order_id'] == $value['id']) {
                        $Data[$key]['order_detail'] = $val;
                    }
                }

                foreach ($PipeJouney as $k => $val) {
                    if ($val['tracking_code'] == $value['id']) {
                        $Data[$key]['pipe_journey'][] = $val;
                        $Data[$key]['pipe_status']    = (int)$val['pipe_status'];
                    }
                }


                foreach ($FromUser as $k => $val) {
                    if ($val['id'] == $value['from_user_id']) {
                        $Data[$key]['from_user'] = $val;
                    }
                }

                /*if(!empty($val['pipe_journey'])){
                    foreach($val['pipe_journey'] as $v){
                        $Data[$key]['pipe_status'] = (int)$v['pipe_status'];
                    }
                }*/

            }

            if(!empty($ListToAddress)){
                $ListToAddress = array_unique($ListToAddress);
                $this->getToaddress($ListToAddress);
            }

            if(!empty($ListFromAddress)){
                $ListFromAddress = array_unique($ListFromAddress);
                $this->getFromaddress($ListFromAddress);
            }

            if(!empty($this->list_district_id)){
                $this->list_district_id = array_unique($this->list_district_id);
                $this->list_district_id = $this->getProvince($this->list_district_id);
            }

            if(!empty($this->list_ward_id)){
                $this->list_ward_id = array_unique($this->list_ward_id);
                $this->list_ward_id = $this->getWard($this->list_ward_id);
            }

            $this->data = $Data;
        }

        return $this->ResponseData();
    }

    /**
     * Đơn hàng yêu cầu phát lại
     */
    public function getReportReplay(){
        $itemPage           = 20;
        $this->error        = false;
        $this->message      = 'success';

        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 'ALL';
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';

        $ReportStart        = Input::has('report_start')        ? (int)Input::get('report_start')           : 0;
        $ReportEnd          = Input::has('report_end')          ? (int)Input::get('report_end')             : 0;
        $ReportReplay       = Input::has('report_replay')       ? (int)Input::get('report_replay')          : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))           : '';
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';

        $Model          = $this->getModel();

        if($this->error){
            $this->error    = false;
            return $this->ResponseData();
        }

        if(!empty($ListStatus) && !$TrackingCode){
            $ListStatus = explode(',',$ListStatus);
            $Model          = $Model->whereIn('status',$ListStatus);
        }

        /**
         * get data
         */

        /*
         * 707 - 903  yêu cầu phát lại
         * 708 - 904    đã báo hvc
        */

        $PipeJourneyModel   = new PipeJourneyModel;
        $PipeJourneyModel   = $PipeJourneyModel::where('type',1)
            ->where('time_store_stock',0)
            ->where(function($query){
                $query->where(function($q){
                    $q->whereIn('pipe_status', [707,708,711])
                        ->where('group_process', 29);
                })->orWhere(function($q){
                    $q->whereIn('pipe_status', [903, 904, 908])
                        ->where('group_process', 31);
                });
            });

        if(!empty($ReportStart)){
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$ReportStart);
        }else{
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$this->time() - 86400*60);
        }

        if(!empty($ReportEnd)){
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','<=',$ReportEnd);
        }

        $PipeJourney            = $PipeJourneyModel->orderBy('time_create','ASC')->get(['user_id','tracking_code','type','group_process','pipe_status','note','time_create'])->toArray();
        unset($PipeJourneyModel);

        if(empty($PipeJourney)){
            $this->error    = false;
            return $this->ResponseData();
        }

        $ListPipe           = [];
        $ListOrderId        = [];
        $ListOrderReportId  = [];
        $ListOrderConfirm   = [];
        $ListReportReplay   = [];

        foreach($PipeJourney as $val){
            $ListOrderId[]                              = (int)$val['tracking_code'];

            if(in_array($val['pipe_status'], [708,904])){
                $ListOrderReportId[]                              = (int)$val['tracking_code'];
            }

            if(in_array($val['pipe_status'], [711,908])){
                $ListOrderConfirm[]                              = (int)$val['tracking_code'];
            }

            if(in_array($val['pipe_status'], [707,903])){
                $ListReportReplay[]                              = (int)$val['tracking_code'];
            }

            $ListPipe[(int)$val['tracking_code']][]     = $val;

        }
        unset($PipeJourney);

        $ListOrderId        = array_unique($ListOrderId);
        $ListOrderReportId  = array_unique($ListOrderReportId);
        $ListOrderConfirm   = array_unique($ListOrderConfirm);
        $ListReportReplay   = array_unique($ListReportReplay);

        if(!empty($ReportReplay)){
            if($ReportReplay == 1){// Chưa báo HVC
                $ListOrderId    = array_diff($ListOrderId,$ListOrderReportId);
            }elseif($ReportReplay == 2){ // Đã báo HVC
                $ListOrderId    = $ListOrderReportId;
            }elseif($ReportReplay == 3){ // Chưa xác nhận
                $ListReportReplay   = array_intersect($ListReportReplay, $ListOrderId);
                $ListOrderId        = array_diff($ListReportReplay,$ListOrderConfirm);
            }elseif($ReportReplay == 4){ // Đã xác nhận
                $ListReportReplay   = array_intersect($ListReportReplay, $ListOrderId);
                $ListOrderId        = array_intersect($ListReportReplay,$ListOrderConfirm);
            }
        }

        unset($ListOrderReportId);

        if($CourierId != 'ALL'){
            $Model          = $Model->where('courier_id',$CourierId);
        }

        if(empty($ListOrderId)){
            $this->error    = false;
            return $this->ResponseData();
        }

        $ListOrderId    = array_unique($ListOrderId);
        $Model  = $Model->whereRaw("id in (". implode(",", $ListOrderId) .")");
        unset($ListOrderId);

        if($Cmd == 'export'){
            return $this->ReportExcel($Model, $ListPipe);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();
        unset($TotalModel);

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model       = $Model->skip($offset)->take($itemPage);

            $Data       = $Model->with(['OrderDetail','FromUser'])->orderBy('time_create','DESC')->get()->toArray();
            if(!empty($Data)){
                $ListToAddress    = [];
                $ListFromAddress  = [];

                foreach($Data as $key => $val){
                    $Data[$key]['pipe_status'] = 0;
                    if($val['from_city_id'] > 0){
                        $this->list_city_id[]   = (int)$val['from_city_id'];
                    }
                    if($val['to_city_id'] > 0){
                        $this->list_city_id[]   = (int)$val['to_city_id'];
                    }

                    if($val['from_district_id'] > 0){
                        $this->list_district_id[]   = (int)$val['from_district_id'];
                    }
                    if($val['from_ward_id'] > 0){
                        $this->list_ward_id[]   = (int)$val['from_ward_id'];
                    }
                    if($val['to_address_id'] > 0){
                        $ListToAddress[]   = (int)$val['to_address_id'];
                    }
                    if($val['from_address_id'] > 0){
                        $ListFromAddress[]  = (int)$val['from_address_id'];
                    }

                    if(isset($ListPipe[(int)$val['id']])){
                        $Data[$key]['pipe_journey'] = $ListPipe[(int)$val['id']];
                    }

                    if(!empty($val['tag'])){
                        $Data[$key]['list_tag']    = explode(',',$val['tag']);
                    }else{
                        $Data[$key]['list_tag']    = [];
                    }

                }

                if(!empty($ListToAddress)){
                    $ListToAddress = array_unique($ListToAddress);
                    $this->getToaddress($ListToAddress);
                    unset($ListToAddress);
                }

                if(!empty($ListFromAddress)){
                    $ListFromAddress = array_unique($ListFromAddress);
                    $this->getFromaddress($ListFromAddress);
                    unset($ListFromAddress);
                }

                if(!empty($this->list_city_id)){
                    $this->list_city_id = array_unique($this->list_city_id);
                    $this->list_city_id = $this->getCityById($this->list_city_id);
                }

                if(!empty($this->list_district_id)){
                    $this->list_district_id = array_unique($this->list_district_id);
                    $this->list_district_id = $this->getProvince($this->list_district_id);
                }

                if(!empty($this->list_ward_id)){
                    $this->list_ward_id = array_unique($this->list_ward_id);
                    $this->list_ward_id = $this->getWard($this->list_ward_id);
                }

                $this->data = $Data;
            }
        }
        return $this->ResponseData();
    }

    public function getCountReportReplay(){
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 0;
        $ReportStart        = Input::has('report_start')        ? (int)Input::get('report_start')           : 0;
        $ReportEnd          = Input::has('report_end')          ? (int)Input::get('report_end')             : 0;
        $ReportReplay       = Input::has('report_replay')       ? (int)Input::get('report_replay')          : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))           : '';

        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';

        $Model          = $this->getModel();


        $this->error        = false;
        if(!$this->error){
            if(!empty($ListStatus) && !$TrackingCode){
                $ListStatus = explode(',',$ListStatus);
                $Model          = $Model->whereIn('status',$ListStatus);
            }

            if(!empty($CourierId)){
                $Model          = $Model->where('courier_id',$CourierId);
            }

            $PipeJourneyModel   = new PipeJourneyModel;
            $PipeJourneyModel   = $PipeJourneyModel::where('type',1)
                ->where('time_store_stock',0)
                ->where(function($query){
                    $query->where(function($q){
                        $q->whereIn('pipe_status', [707,708,711])
                            ->where('group_process', 29);
                    })->orWhere(function($q){
                        $q->whereIn('pipe_status', [903, 904, 908])
                            ->where('group_process', 31);
                    });
                });

            if(!empty($ReportStart)){
                $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$ReportStart);
            }else{
                $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$this->time() - 86400*60);
            }

            if(!empty($ReportEnd)){
                $PipeJourneyModel   = $PipeJourneyModel->where('time_create','<=',$ReportEnd);
            }

            $PipeJourney            = $PipeJourneyModel->get(['user_id','tracking_code','type','group_process','pipe_status','note','time_create'])->toArray();
            if(empty($PipeJourney)){
                $this->error    = false;
                return $this->ResponseData();
            }

            $ListPipe           = [];
            $ListOrderId        = [];
            $ListOrderReportId  = [];
            $ListOrderConfirm   = [];
            $ListReportReplay   = [];

            foreach($PipeJourney as $val){
                $ListOrderId[]                              = (int)$val['tracking_code'];

                if(in_array($val['pipe_status'], [708,904])){
                    $ListOrderReportId[]                              = (int)$val['tracking_code'];
                }

                if(in_array($val['pipe_status'], [711,908])){
                    $ListOrderConfirm[]                              = (int)$val['tracking_code'];
                }

                if(in_array($val['pipe_status'], [707,903])){
                    $ListReportReplay[]                              = (int)$val['tracking_code'];
                }

                $ListPipe[(int)$val['tracking_code']][]     = $val;
            }
            $ListOrderId        = array_unique($ListOrderId);
            $ListOrderReportId  = array_unique($ListOrderReportId);
            $ListOrderConfirm   = array_unique($ListOrderConfirm);
            $ListReportReplay   = array_unique($ListReportReplay);

            if(!empty($ReportReplay)){
                if($ReportReplay == 1){// Chưa báo HVC
                    $ListOrderId    = array_diff($ListOrderId,$ListOrderReportId);
                }elseif($ReportReplay == 2){ // Đã báo HVC
                    $ListOrderId    = $ListOrderReportId;
                }elseif($ReportReplay == 3){ // Chưa xác nhận
                    $ListReportReplay   = array_intersect($ListReportReplay, $ListOrderId);
                    $ListOrderId        = array_diff($ListReportReplay,$ListOrderConfirm);
                }elseif($ReportReplay == 4){ // Đã xác nhận
                    $ListReportReplay   = array_intersect($ListReportReplay, $ListOrderId);
                    $ListOrderId        = array_intersect($ListReportReplay,$ListOrderConfirm);
                }
            }

            if(empty($ListOrderId)){
                $this->error    = false;
                return $this->ResponseData();
            }

            $Model  = $Model->whereRaw("id in (". implode(",", $ListOrderId) .")");

            $GroupStatus    = $Model->groupBy('status')->get(array('status',DB::raw('count(*) as count')))->toArray();

            if(!empty($GroupStatus)){
                foreach($GroupStatus as $val){
                    if(!isset($this->total_group[(int)$val['status']])){
                        $this->total_group[(int)$val['status']] = 0;
                    }
                    $this->total_group[(int)$val['status']] += $val['count'];
                    $this->total_all                            += $val['count'];
                }
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'total'         => $this->total_all,
            'data'          => $this->total_group
        ]);
    }

    /**
     * Đơn hàng yêu cầu lưu kho 
     */
    public function getReportStore(){
        $itemPage           = 20;
        $this->error        = false;
        $this->message      = 'success';

        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 'ALL';
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';

        $ReportStart        = Input::has('report_start')        ? (int)Input::get('report_start')           : 0;
        $ReportEnd          = Input::has('report_end')          ? (int)Input::get('report_end')             : 0;
        $ReportReplay       = Input::has('report_replay')       ? (int)Input::get('report_replay')          : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))           : '';
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';

        $Model          = $this->getModel();
        if(!empty($ListStatus) && !$TrackingCode){
            $ListStatus = explode(',',$ListStatus);
            $Model          = $Model->whereIn('status',$ListStatus);
        }

        if($this->error){
            $this->error    = false;
            return $this->ResponseData();
        }

        /**
         * get data
         */

        /*
         * 707 - 903  yêu cầu phát lại
         * 708 - 904    đã báo hvc
        */

        $PipeJourneyModel   = new PipeJourneyModel;
        $PipeJourneyModel   = $PipeJourneyModel::where('type',1)
                                ->where(function($query){
                                    $query->where(function($q){
                                        $q->whereIn('pipe_status', [707,708])
                                            ->where('group_process', 29);
                                    })->orWhere(function($q){
                                        $q->whereIn('pipe_status', [903, 904])
                                            ->where('group_process', 31);
                                    });
                                });

        if(!empty($ReportStart)){
            $PipeJourneyModel   = $PipeJourneyModel->where('time_store_stock','>=',$ReportStart);
        }else{
            $PipeJourneyModel   = $PipeJourneyModel->where('time_store_stock','>=',$this->time() - 86400*60);
        }

        if(!empty($ReportEnd)){
            $PipeJourneyModel   = $PipeJourneyModel->where('time_store_stock','<=',$ReportEnd);
        }

        $PipeJourney            = $PipeJourneyModel->orderBy('time_create','ASC')->get(['user_id','tracking_code','type','group_process','pipe_status','note','time_create'])->toArray();
        unset($PipeJourneyModel);

        if(empty($PipeJourney)){
            $this->error    = false;
            return $this->ResponseData();
        }

        $ListPipe           = [];
        $ListOrderId        = [];
        $ListOrderReportId  = [];

        foreach($PipeJourney as $val){
            $ListOrderId[]                              = (int)$val['tracking_code'];

            if(in_array($val['pipe_status'], [708,904])){
                $ListOrderReportId[]                              = (int)$val['tracking_code'];
            }

            $ListPipe[(int)$val['tracking_code']][]     = $val;
        }
        unset($PipeJourney);

        $ListOrderId        = array_unique($ListOrderId);
        $ListOrderReportId  = array_unique($ListOrderReportId);

        if(!empty($ReportReplay)){
            if($ReportReplay == 1){// Chưa báo HVC
                $ListOrderId    = array_diff($ListOrderId,$ListOrderReportId);
            }else{ // Đã báo HVC
                $ListOrderId    = $ListOrderReportId;
            }
        }

        unset($ListOrderReportId);

        if($CourierId != 'ALL'){
            $Model          = $Model->where('courier_id',$CourierId);
        }

        if(empty($ListOrderId)){
            $this->error    = false;
            return $this->ResponseData();
        }

        $ListOrderId    = array_unique($ListOrderId);
        $Model  = $Model->whereRaw("id in (". implode(",", $ListOrderId) .")");
        unset($ListOrderId);

        if($Cmd == 'export'){
            return $this->ReportExcel($Model, $ListPipe);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();
        unset($TotalModel);

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model       = $Model->skip($offset)->take($itemPage);

            $Data       = $Model->with(['OrderDetail','FromUser'])->orderBy('time_create','DESC')->get()->toArray();
            if(!empty($Data)){
                $ListToAddress    = [];
                $ListFromAddress  = [];

                foreach($Data as $key => $val){
                    $Data[$key]['pipe_status'] = 0;

                    if($val['from_city_id'] > 0){
                        $this->list_city_id[]   = (int)$val['from_city_id'];
                    }
                    if($val['to_city_id'] > 0){
                        $this->list_city_id[]   = (int)$val['to_city_id'];
                    }

                    if($val['from_district_id'] > 0){
                        $this->list_district_id[]   = (int)$val['from_district_id'];
                    }
                    if($val['from_ward_id'] > 0){
                        $this->list_ward_id[]   = (int)$val['from_ward_id'];
                    }
                    if($val['to_address_id'] > 0){
                        $ListToAddress[]   = (int)$val['to_address_id'];
                    }
                    if($val['from_address_id'] > 0){
                        $ListFromAddress[]  = (int)$val['from_address_id'];
                    }

                    if(isset($ListPipe[(int)$val['id']])){
                        $Data[$key]['pipe_journey'] = $ListPipe[(int)$val['id']];
                    }

                    if(!empty($val['tag'])){
                        $Data[$key]['list_tag']    = explode(',',$val['tag']);
                    }else{
                        $Data[$key]['list_tag']    = [];
                    }

                }

                if(!empty($ListToAddress)){
                    $ListToAddress = array_unique($ListToAddress);
                    $this->getToaddress($ListToAddress);
                    unset($ListToAddress);
                }

                if(!empty($ListFromAddress)){
                    $ListFromAddress = array_unique($ListFromAddress);
                    $this->getFromaddress($ListFromAddress);
                    unset($ListFromAddress);
                }

                if(!empty($this->list_city_id)){
                    $this->list_city_id = array_unique($this->list_city_id);
                    $this->list_city_id = $this->getCityById($this->list_city_id);
                }

                if(!empty($this->list_district_id)){
                    $this->list_district_id = array_unique($this->list_district_id);
                    $this->list_district_id = $this->getProvince($this->list_district_id);
                }

                if(!empty($this->list_ward_id)){
                    $this->list_ward_id = array_unique($this->list_ward_id);
                    $this->list_ward_id = $this->getWard($this->list_ward_id);
                }

                $this->data = $Data;
            }
        }
        return $this->ResponseData();
    }

    public function getCountReportStock(){
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 0;
        $ReportStart        = Input::has('report_start')        ? (int)Input::get('report_start')           : 0;
        $ReportEnd          = Input::has('report_end')          ? (int)Input::get('report_end')             : 0;
        $ReportReplay       = Input::has('report_replay')       ? (int)Input::get('report_replay')          : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))           : '';

        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';

        $Model          = $this->getModel();


        $this->error        = false;
        if(!$this->error){
            if(!empty($ListStatus) && !$TrackingCode){
                $ListStatus = explode(',',$ListStatus);
                $Model          = $Model->whereIn('status',$ListStatus);
            }

            if(!empty($CourierId)){
                $Model          = $Model->where('courier_id',$CourierId);
            }

            $PipeJourneyModel   = new PipeJourneyModel;
            $PipeJourneyModel   = $PipeJourneyModel::where('type',1)
                ->where(function($query){
                    $query->where(function($q){
                        $q->whereIn('pipe_status', [707,708,711])
                            ->where('group_process', 29);
                    })->orWhere(function($q){
                        $q->whereIn('pipe_status', [903, 904, 908])
                            ->where('group_process', 31);
                    });
                });

            if(!empty($ReportStart)){
                $PipeJourneyModel   = $PipeJourneyModel->where('time_store_stock','>=',$ReportStart);
            }else{
                $PipeJourneyModel   = $PipeJourneyModel->where('time_store_stock','>=',$this->time() - 86400*60);
            }

            if(!empty($ReportEnd)){
                $PipeJourneyModel   = $PipeJourneyModel->where('time_store_stock','<=',$ReportEnd);
            }

            $PipeJourney            = $PipeJourneyModel->get(['user_id','tracking_code','type','group_process','pipe_status','note','time_create'])->toArray();
            if(empty($PipeJourney)){
                $this->error    = false;
                return $this->ResponseData();
            } 

            $ListPipe           = [];
            $ListOrderId        = [];
            $ListOrderReportId  = [];
            $ListOrderConfirm   = [];
            $ListReportReplay   = [];

            foreach($PipeJourney as $val){
                $ListOrderId[]                              = (int)$val['tracking_code'];

                if(in_array($val['pipe_status'], [708,904])){
                    $ListOrderReportId[]                              = (int)$val['tracking_code'];
                }

                if(in_array($val['pipe_status'], [711,908])){
                    $ListOrderConfirm[]                              = (int)$val['tracking_code'];
                }

                if(in_array($val['pipe_status'], [707,903])){
                    $ListReportReplay[]                              = (int)$val['tracking_code'];
                }

                $ListPipe[(int)$val['tracking_code']][]     = $val;
            }
            $ListOrderId        = array_unique($ListOrderId);
            $ListOrderReportId  = array_unique($ListOrderReportId);
            $ListOrderConfirm   = array_unique($ListOrderConfirm);
            $ListReportReplay   = array_unique($ListReportReplay);

            if(!empty($ReportReplay)){
                if($ReportReplay == 1){// Chưa báo HVC
                    $ListOrderId    = array_diff($ListOrderId,$ListOrderReportId);
                }elseif($ReportReplay == 2){ // Đã báo HVC
                    $ListOrderId    = $ListOrderReportId;
                }elseif($ReportReplay == 3){ // Chưa xác nhận
                    $ListReportReplay   = array_intersect($ListReportReplay, $ListOrderId);
                    $ListOrderId        = array_diff($ListReportReplay,$ListOrderConfirm);
                }elseif($ReportReplay == 4){ // Đã xác nhận
                    $ListReportReplay   = array_intersect($ListReportReplay, $ListOrderId);
                    $ListOrderId        = array_intersect($ListReportReplay,$ListOrderConfirm);
                }
            }

            if(empty($ListOrderId)){
                $this->error    = false;
                return $this->ResponseData();
            }

            $Model  = $Model->whereRaw("id in (". implode(",", $ListOrderId) .")");

            $GroupStatus    = $Model->groupBy('status')->get(array('status',DB::raw('count(*) as count')))->toArray();

            if(!empty($GroupStatus)){
                foreach($GroupStatus as $val){
                    if(!isset($this->total_group[(int)$val['status']])){
                        $this->total_group[(int)$val['status']] = 0;
                    }
                    $this->total_group[(int)$val['status']] += $val['count'];
                    $this->total_all                            += $val['count'];
                }
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'total'         => $this->total_all,
            'data'          => $this->total_group
        ]);
    }


    private function __get_promise_return($item, $ReturnCenter){
        $PromiseTime = $item['time_accept_return'] + $item['courier_estimate']*3600 + 100800;
        if(!empty($ReturnCenter) && !in_array($item['to_district_id'], $ReturnCenter)){
            $PromiseTime += 86400;
        }

        return $PromiseTime;
    }

    private function __export_return($Model, $ListField, $ReturnCenter){
        $Data               = [];
        $ListCityId         = [];
        $ListDistrictId     = [];
        $ListFromAddress    = [];
        $ListUserId         = [];

        $Model->with('__post_office')->select($ListField)->chunk('1000', function($query) use(&$Data, &$ListCityId, &$ListDistrictId, &$ListToAddress, &$ListFromAddress, &$ListUserId, &$ReturnCenter){
            foreach($query as $val){
                $val                = $val->toArray();

                $val['promise_time'] = $this->__get_promise_return($val, $ReturnCenter);

                $Data[]             = $val;

                if($val['from_city_id'] > 0){
                    $ListCityId[]   = (int)$val['from_city_id'];
                }
                if($val['to_city_id'] > 0){
                    $ListCityId[]   = (int)$val['to_city_id'];
                }

                if($val['from_district_id'] > 0){
                    $ListDistrictId[]   = $val['from_district_id'];
                }
                if($val['to_district_id'] > 0){
                    $ListDistrictId[]   = $val['to_district_id'];
                }

                if($val['from_address_id'] > 0){
                    $ListFromAddress[]  = (int)$val['from_address_id'];
                }
                if($val['from_user_id'] > 0){
                    $ListUserId[]  = (int)$val['from_user_id'];
                }
            }
        });

        unset($Model);
        unset($ListField);

        if(!empty($ListFromAddress)){
            $ListFromAddress = array_unique($ListFromAddress);
            $this->getFromaddress($ListFromAddress);
        }

        if(!empty($ListDistrictId)){
            $ListDistrictId = array_unique($ListDistrictId);
            $ListDistrictId = $this->getProvince($ListDistrictId);
        }

        if(!empty($ListUserId)){
            $ListUserId     = array_unique($ListUserId);
            $ListUserId     = $this->getUser($ListUserId);
        }

        if(!empty($ListCityId)){
            $ListCityId         = array_unique($ListCityId);
            $ListCityId         = $this->getCityById($ListCityId);
        }

        return Response::json([
            'error'             => $this->error,
            'message'           => $this->message,
            'total'             => 0,
            'data'              => $Data,
            'list_city'         => $ListCityId,
            'list_district'     => $ListDistrictId,
            'list_from_address' => $ListFromAddress,
            'list_user'         => $ListUserId
        ]);

    }

    public function getReturnSlow(){
        $itemPage           = 20;
        $this->error        = false;
        $this->message      = 'success';
        $Group              = 106;
        $TypeProcess        = 5;

        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';
        $AcceptReturnStart  = Input::has('accept_return_start') ? (int)Input::get('accept_return_start')        : 0; // accept_return start
        $AcceptReturnEnd    = Input::has('accept_return_end')   ? (int)Input::get('accept_return_end')          : 0; // accept_return end
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                    : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))               : '';
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $ReturnSlow         = Input::has('return_slow')         ? (int)Input::get('return_slow')                : 0;
        $FromCity           = Input::has('from_city')           ? (int)Input::get('from_city')                  : 0;
        $FromDistrict       = Input::has('from_district')       ? (int)Input::get('from_district')              : 0;

        $Model          = $this->getModel();
        if($this->error){
            return $this->ResponseData();
        }

        if(empty($CourierId)){
            return $this->ResponseData();
        }

        $Model  = $Model->where('courier_id',$CourierId);

        if(!empty($ListStatus) && !$TrackingCode){
            $ListStatus = explode(',',$ListStatus);
            $Model          = $Model->whereIn('status',$ListStatus);
        }

        if(!empty($AcceptReturnStart)){
            $Model  = $Model->where('time_accept_return','>=',$AcceptReturnStart);
        }

        if(!empty($AcceptReturnEnd)){
            $Model  = $Model->where('time_accept_return','<=',$AcceptReturnEnd);
        }

        $AreaLocationModelDev   = \AreaLocationModelDev::where('courier_id',$CourierId)->where('active',1);
        if(!empty($FromDistrict)){
            $AreaLocationModelDev   = $AreaLocationModelDev->where('province_id',$FromDistrict);
        }elseif(!empty($FromCity)){
            $AreaLocationModelDev   = $AreaLocationModelDev->where('city_id',$FromCity);
        }

        $AreaLocation   = $AreaLocationModelDev->remember(60)->get(['province_id','location_id'])->toArray();
        if(empty($AreaLocation)){
            return $this->ResponseData();
        }

        $ReturnCenter = [];
        foreach($AreaLocation as $val){
            if(in_array($val['location_id'], [1,2])){
                $ReturnCenter[]   = (int)$val['province_id'];
            }
        }

        switch ((int)$ReturnSlow) {
            case 1:
                $RangeStart = 0;
                $RangeEnd = 1;
                break;
            case 2:
                $RangeStart = 1;
                $RangeEnd = 2;
                break;
            case 3:
                $RangeStart = 2;
                $RangeEnd = 4;
                break;
            case 4:
                $RangeStart = 4;
                break;
            default:
                $RangeStart = 0;
        }

        if((date("N") - $ReturnSlow)  <= 0){
            $RangeStart += 1;

            if(!empty($RangeEnd)){
                $RangeEnd   += 1;
            }
        }

        $Model  = $Model->where('courier_estimate','>',0)->where('time_accept_return','>',0);

        $StrRawStart = 'time_accept_return + courier_estimate*3600 + 100800 + '.$RangeStart*86400;
        $StrRawEnd   = '';

        if(!empty($RangeEnd)){
            $StrRawEnd   = 'time_accept_return + courier_estimate*3600 + 100800 + '.$RangeEnd*86400;
        }

        if(!empty($ReturnCenter)){
            $Model  = $Model->where(function($query) use($StrRawStart,$ReturnCenter,$StrRawEnd){
                $query->where(function($q) use($StrRawStart,$ReturnCenter, $StrRawEnd){
                    $q->where(function($p) use($StrRawStart,$ReturnCenter, $StrRawEnd){
                        $p->where(function($k) use($StrRawStart,$ReturnCenter, $StrRawEnd){
                            $k->where('status',66)->whereIn('to_district_id', $ReturnCenter)->whereRaw('('.$StrRawStart.') <= time_success');
                            if(!empty($StrRawEnd)){
                                $k->whereRaw('('.$StrRawEnd.') >= time_success');
                            }
                        })->orWhere(function($k) use($StrRawStart,$ReturnCenter, $StrRawEnd){
                            $k->where('status','<>',66)->whereIn('to_district_id', $ReturnCenter)->whereRaw('('.$StrRawStart.') <= '.$this->time());
                            if(!empty($StrRawEnd)){
                                $k->whereRaw('('.$StrRawEnd.') >= '.$this->time());
                            }
                        });

                    })->orWhere(function($p) use($StrRawStart,$ReturnCenter, $StrRawEnd){
                        $p->where(function($k) use($StrRawStart,$ReturnCenter, $StrRawEnd){
                            $k->where('status',66)->whereNotIn('to_district_id', $ReturnCenter)->whereRaw('('.$StrRawStart.' + 86400) <= time_success');
                            if(!empty($StrRawEnd)){
                                $k->whereRaw('('.$StrRawEnd.' + 86400) >= time_success');
                            }
                        })->orWhere(function($k)use($StrRawStart,$ReturnCenter, $StrRawEnd){
                            $k->where('status','<>',66)->whereNotIn('to_district_id', $ReturnCenter)->whereRaw('('.$StrRawStart.' + 86400) <= '.$this->time());
                            if(!empty($StrRawEnd)){
                                $k->whereRaw('('.$StrRawEnd.' + 86400) >= '.$this->time());
                            }
                        });
                    });
                });

            });

        }else{
            $Model->where(function($query) use($StrRawStart, $StrRawEnd){
                $query->where(function($q) use($StrRawStart, $StrRawEnd){
                    $q->where('status',66)->whereRaw('('.$StrRawStart.' + 86400) <= time_success');
                    if(!empty($StrRawEnd)){
                        $q->whereRaw('('.$StrRawEnd.' + 86400) >= time_success');
                    }
                })->orWhere(function($q) use($StrRawStart, $StrRawEnd){
                    $q->where('status','<>',66)->whereRaw('('.$StrRawStart.' + 86400) <= '.$this->time());
                    if(!empty($StrRawEnd)){
                        $q->whereRaw('('.$StrRawEnd.' + 86400) >= '.$this->time());
                    }
                });
            });

        }

        $ListField          = ['id','tracking_code','courier_tracking_code', 'time_accept', 'time_pickup', 'time_success',
            'time_accept_return', 'courier_estimate', 'status','service_id', 'courier_id', 'domain', 'from_user_id', 'to_name', 'to_phone', 'to_email',
            'from_address_id', 'from_city_id', 'from_district_id', 'to_address_id', 'to_district_id', 'to_city_id'];

        if($Cmd == 'export'){
            return $this->__export_return($Model, $ListField, $ReturnCenter);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0) {
            $offset = ($page - 1) * $itemPage;
            $Model = $Model->skip($offset)->take($itemPage);
            $Data  = $Model->with(['pipe_journey' => function($query) use($Group,$TypeProcess){
                $query->where('type', $TypeProcess)->where('group_process', $Group)->orderBy('time_create', 'ASC');
            }, 'FromUser'])->orderBy('time_create','DESC')->get($ListField)->toArray();

            if(!empty($Data)){
                $ListToAddress    = [];
                $ListFromAddress  = [];

                foreach($Data as $key => $val){
                    $Data[$key]['pipe_status']  = 0;
                    $Data[$key]['promise_time'] = $this->__get_promise_return($val, $ReturnCenter);

                    if($val['from_city_id'] > 0){
                        $this->list_city_id[]   = (int)$val['from_city_id'];
                    }
                    if($val['to_city_id'] > 0){
                        $this->list_city_id[]   = (int)$val['to_city_id'];
                    }

                    if($val['from_district_id'] > 0){
                        $this->list_district_id[]   = (int)$val['from_district_id'];
                    }

                    if($val['to_district_id'] > 0){
                        $ListDistrictId[]   = $val['to_district_id'];
                    }
                    
                    if($val['from_address_id'] > 0){
                        $ListFromAddress[]  = (int)$val['from_address_id'];
                    }

                    if(!empty($val['pipe_journey'])){
                        foreach($val['pipe_journey'] as $v){
                            $Data[$key]['pipe_status'] = (int)$v['pipe_status'];
                        }
                    }
                }

                if(!empty($ListFromAddress)){
                    $ListFromAddress = array_unique($ListFromAddress);
                    $this->getFromaddress($ListFromAddress);
                }

                if(!empty($this->list_city_id)){
                    $this->list_city_id = array_unique($this->list_city_id);
                    $this->list_city_id = $this->getCityById($this->list_city_id);
                }

                if(!empty($this->list_district_id)){
                    $this->list_district_id = array_unique($this->list_district_id);
                    $this->list_district_id = $this->getProvince($this->list_district_id);
                }

                $this->data = $Data;
            }
        }

        return $this->ResponseData();
    }

    //Check lý do giao chậm
    private function __check_problem($Note){
        $pattern_1 = "/(không nghe máy|ko gọi được|ll|sdt|sđt|gọi điện tb|khóa máy|khong lien lac|đt nhiều lần|dien nhieu|K NGHE
                        |khong nghe|số điện thoại không đúng|KO NGE MAY|k nge|nt|khong nghe máy|k ll dc|ko ll|K NGHE MAY|ko đúng tên
                        |KHÔNG LLD|k liên lạc|ko ll dc|K LL|tắt máy|sdt ko đúng|ko goi duoc|k nge may|ko nge may|sai tên|K NGE MAY|KO LL
                        |dt khoa may|k nge may|không nge máy|sai sđt|máy bận|KHÔNG NGHE MÁY|dt k nghe|nt k tra loi|k nghe máy|không liên lạc
                        |sai số|không nghe|KHONG NGHE|KHONG LIEN LAC|k nghe|THUÊ BAO|gọi kh thuê bao|K NGHE MÁY|thuê bao|ko nghe|khong nghe may
                        |BT DI PHAT DT N LAN KBM|dt k nghe may|liên hệ|dt rta nhiue lan ko nghe|kh ko khi nao nghe may|ko liên lạc
                        |DT NHIEU LAN KH K NGHE MAY|DT KO NGHE MAY|dt k lien lac duoc|goi k nghe may|điện thoại không nghe
                        |dt rất nhiều lần knm|so dt thieu so|sai sđt ko tìm thấy kh|sai số ddienj thoại|gọi điện nhưng báo nhầm số
                        |SAI SO DTHOAI|so dt ko phai ten ng nhan|khong nghe may nhieu lần|sdt ko liên lạc được|gọi nhiều lần k nghe
                        |đt thuê bao|sdt ko phải của người nhận|dt k dung ten|ĐT NHIỀU KHÔNG NGHE MÁY|đc ko rõ ràng đt ko nghe máy
                        |đt rất nhiều lần kh ko nge máy|GỌI NHIU LAN KO NGHE MAY|so dt ko nge|kh đt là sai số|goi kh nhieu ln k nghe may
                        |SĐT sai tên nguời nhận|đt kg liên lạc được|đt tắt máy|KO NGHE|SDT thừa số|SĐT thuê bao|đt ko nghe
                        |đt ko đúng tên người nhận|GOI DIEN NHIEU NGAY KO NGHE MAY|GỌI ĐIỆN KO NGHE MÁY|GỌI ĐIỆN HIỀU LẦN KO NGHE MÁY
                        |đt ko llac được|KH K nghe máy|dt ko ai nghe may|dt kh tat may|goi rất nhiều lần kh ko bắt máy|dt ko đúng tên người nhận
                        |đt ko liên lạc được|đt gọi nhiều ngày|DT SAI|dt KH k nghe may|DT NHIEUF KO NGHE MÁY|SO DT GOI KO DUOC|gọi kp nghe
                        |GỌI RẤT NHIỀU LẦN K NGHE MÁY|gọi đt nhiều lần khách ko nghe|ĐT NHIỀU LẦN KO NGHE MÁY|kh ko nghe|điện thoại thuê bao
                        |so dt ko dung ten|ĐT là thuê bao|Điện thoại nhiều lần  KO NGHE|SĐT sai ko đúng tên|đt k đúng|gọi thuê bao|dt ko ll dc
                        |ko nghe may|đt nhiều lần ko nghe|thuê bao không ll được|dien thoai khong lien lac duoc|sdt k đúng|dt k liên hệ được
                        |đt kg nghe máy|GỌI K NGHE MÁY|dt kh ko nghe|sdt ko phai|DT NHIEU KO NGHE|LL nhiều lần|ko nghe máy|dt ko nghe
                        |đ\/t ko đúng|sđt thuue bao|sđt ko liên lạc được|sdtd thuê bao|thue bao|THUE BAO|đt nhiều lần k nghe|sdt k dung
                        |sđt sai ko liên lạc được|sdt k ll được|KH KO NGHE MÁY|dt k nge may|k nghe máy|DT KH KHÔNG NGHE MÁY
                        |dt nhieu lan khong nghe may)/i";

        $pattern_2  = "/(từ chối nhận|ko co tien|nhận rồi|ko đặt mua|từ chối|hủy|mua roi|hủy ko lấy hàng|bao ko dat mua gi het
                        |hủy đơn|đã nhận roi|từ chôi nhan|ko dat hang|từ chối nhân|da nhan 1 don hang|chưa có tiền nhận|HỦY ĐƠN HÀNG T\/C NHẬN|sai hàng
                        |Ko đúng mẫu|ko co dat don hang|khach hang bao ko co tien nhan|hủy đơn vì mua rồi|kh huy hang|k\\h đã mua hàng rồi
                        |hàng giao kg đúng mẫu|Khách hàng từ chối|kh chỉ lấy|từ chối nhận hàng|TƯ CHOI  NHAN|đã mua 1 rồi|khách hủy
                        |ko biết khi nao fnhanaj đc|từ chối vì giá cao|kh đặt 1 đơn nhưng gửi 2 đơn|KHACH HUY DON|huy don hang|khach huy don hang
                        |kh hen tuan sau|KHÁCH TƯ  CHỐI NHẬN|kh tc nhan|kh ko đặt hàng|kh từ chối nhận|kh k đặt mua|huy dh|kh mua hàng rồi
                        |k dung mau nen k nhan|nhan 1 cai|TỪ CHỐI NHẬN|kh không chịu nhận|TU CHOI NHAN|kh da nhan dc hang|huy voi nguoi ban
                        |KH yu choi nhan|KH hủy đơn|từ chối|khách hàng ko chịu thanh toán tiền|KHÁCH HÀNG TỪ CHỐI|đã nhận 1 đơn rồi|KH Tc nhận
                        |hàng bị lỗi|TC NHẬN|KH KG CÓ ĐẶT HÀNG|tc nhận|báo hủy đơn|khach huy don k nhan|khách hàng từ chối|huy dat don hang
                        |ko nhận|kh k nhận|KH KO ĐẶT MUA|KH từ chối|kh huy vi ko dung|TỪ CHỐI HỦY ĐƠN HÀNG|khách hẹn ngày này qua ngày khác
                        |kh ko nhận hàng|KH HUY DON|đã thanh toán|kh từ choi nhận|kh ko mau hủy đơn|kh huy don|khach da huy don|kh đã nhận đc 1 sp
                        |không có tiền để nhận hàng|không mua|tc nhận|khach che loa re|khách báo hủy đơn|kh k mua vì kđúng hàng|kh da di ha noi
                        |KH KO LẤY|không đúng đơn hàng khách đặt|KH KO ƯNG SP|khách hàng không mang vừa|ko đúng màu|khách hàngko đúng sản phẩm
                        |không đúng màu khách đặt|kh tuc hoi nhan|KH đã nhận 1 đơn hàng|TỪ CHỐI NHẬN|hết tiền kh ko nhận|t choi nhan
                        |khách hàng ko có tiền nhận|KH huy don hang|KH tù chối|KH BÁO DO MÁY TRẦY NÊN KHÔNG NHẬN|ko nhan|khách hàng tc nhận
                        |chỉ đặt 1 đơn hàng|KH KO MUỐN NHẬN HÀNG|kh noi lau qua k mua nua|khách hàng ko có tiền|khong dung gia|kh chê đắt ko nhận
                        |huy don|KH TỰ CHƠI NHÂN|don hang da huy|K\/H TU CHOI NHAN|Kh nói k đặt|TU CHOI NHAN HANG|khong co dat don hang|đã hủy đơn
                        |KO ĐẶT HÀNG|kh từ chối nhânk|TU CHOI NHAN|K.H KG NHẬN|KH nhận tại BC|k.h đã hủy đơn|JKO CÓ ĐẶT HÀNG|mua roi khong mua nua
                        |kh tư chối nhận|TU CHOI NHẬN|KO MUA|tu choi|kh ko nhan|kh da nhan roi|kh da mua roi|kh da huy don|KHÁCH TỪ CHỐI|hủy đơn hàng
                        |kh hủy đơn|từ chối nhận|k\/h từ chối nhận|KH TỪ CHỐI NHẬN|k\/h tu choi nhan|khách từ chối nhận|kh không nhận|kh trả hàng
                        |kh đã hủy đơn|ko mua|KH từ chối nhận|ĐÃ NHẬN HÀNG RỒI|kh khong nhận hàng|kh tu choi|kh tu choi nhan|K\/h từ chối nhận
                        |Kh từ chối nhận)/i";

        $pattern_3  = "/(DC KO DUNG|địa chỉ xa|Đ\/c ko rõ|k có địa chỉ này|ko co tai dc|địa chỉ k rõ|đc là toà nhà|dc toa nha|dc ko đung ng nhan
                        |địa chỉ đóng cuẳ thường xuyên|địa chỉ thiếu|địa chỉ k có phường|dia chi khong ro rang|ĐI PHÁT K TÌM ĐC KH
                        |DIA CHI KO CO TEN NGUOI NHAN|d\/c chung chung|địa chỉ ko rõ|ko có địa chỉ này|d\/c khong tim thay|dia chi k ro rang
                        |ko co so nha d\/c ro rang|d\/c k co|địa chỉ ko tìm thấy|dc ko tim thay|không có - Chợ bộ|Kh không có tại địa chỉ
                        |không có địa chỉn này)/i";

        $pattern_4  = "/(đi làm về muộn|về quê|về quê|ve que|không có nhà|xa|khach di cong tac|VỀ QUÊ|khdi congtac dai ngay
                        |k\/h ddi xa noi cu tru|kh về quê|kh nói đi ctac k biết khi nào về|khách hàng vắng nhà|cong tac|đi công tác
                        |di cong tac|VANG NHA|kh ko có nhà|k.h về quê|dag di cong tac|đi công tác|kh đi làm ăn xa|khach hang ve que|KH ĐI VẮNG
                        |NHÀ ĐÓNG CỬA|KH DI VANG|khach hang ve que|K\/H DI XA NHIEU NGAY|kh k co nha|KH ĐI XA|đi công tác|kh di xa roi
                        |đt bao di cong tác rùi|KH ĐI SÀI GÒN|cong tac|KH ĐI CÔNG TÁC|k.h đi làm xa|KH về quê|kh đi xa|KH đi công tác
                        |kh dang nam vien|Khách hàng vắng nhà|kh đi du lịch|di lam xa|kh di vang|kh di lam xa|CÔNG TÁC|nhà đóng cửa|về quê
                        |đi công tác)/i";

        $pattern_5  = "/(HẸN ra bc|hẹn|hẹn|HẸN|đến bưu cục nhận|Hẹn|HEN|hen|Khách đến bưu cục nhận)/i";

        if (preg_match($pattern_5, $Note)){
            return 'hen_len_bc';
        }elseif(preg_match($pattern_2, $Note)){
            return 'tu_choi_nhan';
        }elseif(preg_match($pattern_3, $Note)){
            return 'sai_dia_chi';
        }elseif(preg_match($pattern_4, $Note)){
            return 'di_vang';
        }elseif(preg_match($pattern_1, $Note)){
            return 'ko_ll_duoc';
        }else{
            return 'ly_do_khac';
        }
    }

    // Giao chậm
    public function getDeliverySlow(){
        $itemPage           = 20;
        $this->error        = false;
        $this->message      = 'success';

        $page               = Input::has('page')        ? (int)Input::get('page')               : 1;
        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')               : 0; // time_accept start
        $TimeAcceptEnd      = Input::has('accept_end')          ? (int)Input::get('accept_end')                 : 0; // time_accept end
        $TimeSuccessStart   = Input::has('success_start')       ? (int)Input::get('success_start')              : 0; // time_accept start
        $TimeSuccessEnd     = Input::has('success_end')         ? (int)Input::get('success_end')                : 0; // time_accept end
        $PickupStart        = Input::has('pickup_start')        ? (int)Input::get('pickup_start')               : 0; // time_pickup start
        $PickupEnd          = Input::has('pickup_end')          ? (int)Input::get('pickup_end')                 : 0; // time_pickup end
        $ServiceId          = Input::has('service')             ? (int)Input::get('service')                    : 0;
        $Domain             = Input::has('domain')              ? trim(Input::get('domain'))                    : 0;
        $KeyWord            = Input::has('keyword')             ? trim(Input::get('keyword'))                   : 0;
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $PipeStatus         = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))           : '';
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TypeProcess        = Input::has('type-process')        ? (int)Input::get('type-process')           : 1;

        $FromCity           = Input::has('from_city')           ? (int)Input::get('from_city')              : 0;
        $FromDistrict       = Input::has('from_district')       ? (int)Input::get('from_district')          : 0;

        $ToCity             = Input::has('to_city')             ? (int)Input::get('to_city')                : 0;
        $ToDistrict         = Input::has('to_district')         ? (int)Input::get('to_district')          : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))           : '';
        $DeliverySlow       = Input::has('delivery_slow')       ? (int)Input::get('delivery_slow')          : null;
        $NumSlow            = Input::has('num_slow')            ? (int)Input::get('num_slow')               : null;
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 0;
        $Loyalty            = Input::has('loyalty')           ? (int)Input::get('loyalty')                      : null;

        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';

        /*
         * Khu vực
         */
        $Location           = Input::has('location')            ? (int)Input::get('location')                   : 0;

        $FromCountryId      = Input::has('from_country_id')     ? (int)Input::get('from_country_id')            : 237;
        $Global             = Input::has('global')              ? (int)Input::get('global')                     : null;

        $LMongo     = new LMongo;
        $LMongo     = $LMongo::collection('log_journey_delivery')->where('from_country_id',$FromCountryId)->where('active',1);

        if(!empty($Global)){
            $LMongo  = $LMongo->whereNe('to_country_id', $FromCountryId);
        }

        if(!empty($ListStatus) && !$TrackingCode){
            $ListStatus = array_map('intval', explode(',',$ListStatus));
            $LMongo     = $LMongo->whereIn('status',$ListStatus);
        }


        if(!empty($TimeAcceptStart)){
            $LMongo          = $LMongo->whereGte('time_accept',$TimeAcceptStart);
        }else{
            $this->error = true;
            return $this->ResponseData();
        }

        if(!empty($TimeAcceptEnd)){
            $LMongo          = $LMongo->whereLte('time_accept',$TimeAcceptEnd);
        }

        if(!empty($PickupStart)){
            $LMongo          = $LMongo->whereGte('time_pickup',$PickupStart);
        }

        if(!empty($PickupEnd)){
            $LMongo          = $LMongo->whereLte('time_pickup',$PickupEnd);
        }

        if(!empty($TimeSuccessStart)){
            $LMongo          = $LMongo->whereGte('time_success',$TimeSuccessStart);
        }

        if(!empty($TimeSuccessEnd)){
            $LMongo          = $LMongo->whereLte('time_success',$TimeSuccessEnd);
        }

        if(!empty($ServiceId)){
            $LMongo          = $LMongo->where('service_id',$ServiceId);
        }

        if(!empty($Domain)){
            $LMongo          = $LMongo->where('domain',$Domain);
        }

        if(isset($Loyalty)){
            $ListUser       = \loyaltymodel\UserModel::where('level',$Loyalty)->remember(60)->lists('user_id');

            // ko có dữ liệu , return []
            if(empty($ListUser)){
                $this->error = true;
                return;
            }
        }

        if(!empty($KeyWord)){
            $UserModel      = new User;

            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $UserModel          = $UserModel->where('email',$KeyWord);
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $UserModel          = $UserModel->where('phone',$KeyWord);
            }else{ // search code
                $UserModel          = $UserModel->where('fullname',$KeyWord);
            }

            $ListUserSearch = $UserModel->lists('id');
            if(empty($ListUserSearch)){
                return $this->ResponseData();
            }else{
                if(!empty($ListUser)){
                    $ListUser   = array_intersect($ListUser, $ListUserSearch);
                }else{
                    $ListUser   = $ListUserSearch;
                }
            }

            if(empty($ListUser)){
                return $this->ResponseData();
            }
        }

        if(!empty($ListUser)){
            $LMongo = $LMongo->whereIn('from_user_id', $ListUser);
        }

        if(!empty($TrackingCode)){
            $LMongo     = $LMongo->where('tracking_code', $TrackingCode);
        }

        if(!empty($CourierId)){
            $LMongo          = $LMongo->where('courier_id',$CourierId);
        }

        if(!empty($PipeStatus) && !empty($Group)){
            $PipeStatus = explode(',',$PipeStatus);
            $ListId = PipeJourneyModel::where('time_create','>=',$TimeAcceptStart - 86400*30)->where('type', $TypeProcess)->where('group_process',$Group)->whereIn('pipe_status', $PipeStatus)->lists('tracking_code');

            if(!empty($ListId)){
                $ListId = array_unique($ListId);
                $LMongo  = $LMongo->whereIn('order_id', $ListId);
            }else{
                return $this->ResponseData();
            }
        }

        if(!empty($FromDistrict)){
            $LMongo          = $LMongo->where('from_district_id',$FromDistrict);
        }elseif(!empty($FromCity)){
            $LMongo          = $LMongo->where('from_city_id',$FromCity);
        }

        if(!empty($ToDistrict)){
            $LMongo          = $LMongo->where('to_district_id',$ToDistrict);
        }elseif(!empty($ToCity)){
            $LMongo          = $LMongo->where('to_city_id',$ToCity);
        }

        if(!empty($Location)){
            switch ($Location) {
                case 1: // Cùng tỉnh - Nội thành
                    $LMongo = $LMongo->where('area_type',1)->where('to_location',1);
                break;
                case 2:
                    $LMongo = $LMongo->where('area_type',1)->where('to_location',2);
                break;
                case 3:
                    $LMongo = $LMongo->where('area_type',1)->whereGt('to_location',2);
                break;
                case 4:
                    $LMongo = $LMongo->where('area_type',2)->where('to_location',1);
                break;
                case 5:
                    $LMongo = $LMongo->where('area_type',2)->where('to_location',2);
                break;
                case 6:
                    $LMongo = $LMongo->where('area_type',2)->whereGt('to_location',2);
                break;
                default:
                    break;
            }
        }

        $RangeDeliveryStart = 0;
        $RangeDeliveryEnd   = 0;
        if(!empty($DeliverySlow)){
            switch ((int)$DeliverySlow) {
                case 1:
                    $RangeDeliveryStart = 0;
                    $RangeDeliveryEnd = 1;
                    break;
                case 2:
                    $RangeDeliveryStart = 1;
                    $RangeDeliveryEnd = 2;
                    break;
                case 3:
                    $RangeDeliveryStart = 2;
                    $RangeDeliveryEnd = 4;
                    break;
                case 7:
                    $RangeDeliveryStart = 7;
                    break;
                default:
                    $RangeDeliveryStart = 0;
            }

            if((date("N") - $DeliverySlow)  <= 0){
                $RangeDeliveryStart += 1;
                if(!empty($RangeDeliveryEnd)){
                    $RangeDeliveryEnd += 1;
                }
            }
        }

        $RangeDeliveryStart = $RangeDeliveryStart*86400;
        $RangeDeliveryEnd   = $RangeDeliveryEnd*86400;

        if(!empty($NumSlow)){ // Giao chậm lần
            switch ($NumSlow) {
                case 1: // Lần 1
                    $LMongo = $LMongo->where(function($query) use($RangeDeliveryStart, $RangeDeliveryEnd){
                        $query->where(function($q) use($RangeDeliveryStart, $RangeDeliveryEnd){
                            $q->whereExists('first_slow')->whereGt('first_slow',$RangeDeliveryStart);
                            if(!empty($RangeDeliveryEnd)) $q->whereLte('first_slow', $RangeDeliveryEnd);
                        })->orWhere(function($q) use($RangeDeliveryStart, $RangeDeliveryEnd){
                            $q->where('first_slow', null)->whereLt('first_promise_time',$this->time() - $RangeDeliveryStart);
                            if(!empty($RangeDeliveryEnd)){
                                $q->whereGte('first_promise_time',$this->time() - $RangeDeliveryEnd);
                            }else{
                                $q->whereGt('first_promise_time',0);
                            }
                        });
                    });
                    break;
                case 2: // Lần 2
                    $LMongo = $LMongo->where(function($query) use($RangeDeliveryStart, $RangeDeliveryEnd){
                        $query->where(function($q) use($RangeDeliveryStart, $RangeDeliveryEnd){
                            $q->whereExists('second_slow')->whereGt('second_slow',$RangeDeliveryStart);
                            if(!empty($RangeDeliveryEnd)) $q->whereLte('second_slow', $RangeDeliveryEnd);
                        })->orWhere(function($q) use($RangeDeliveryStart, $RangeDeliveryEnd){
                            $q->where('second_slow', null)->whereLt('second_promise_time',$this->time() - $RangeDeliveryStart);
                            if(!empty($RangeDeliveryEnd)){
                                $q->whereGte('second_promise_time',$this->time() - $RangeDeliveryEnd);
                            }else{
                                $q->whereGt('second_promise_time',0);
                            }
                        });
                    });
                    break;
                default: // Lần 3
                    $LMongo = $LMongo->where(function($query) use($RangeDeliveryStart, $RangeDeliveryEnd){
                        $query->where(function($q) use($RangeDeliveryStart, $RangeDeliveryEnd){
                            $q->whereExists('third_slow')->whereGt('third_slow',$RangeDeliveryStart);
                            if(!empty($RangeDeliveryEnd)) $q->whereLte('third_slow', $RangeDeliveryEnd);
                        })->orWhere(function($q) use($RangeDeliveryStart, $RangeDeliveryEnd){
                            $q->where('third_slow', null)->whereLt('third_promise_time',$this->time() - $RangeDeliveryStart);
                            if(!empty($RangeDeliveryEnd)){
                                $q->whereGte('third_promise_time',$this->time() - $RangeDeliveryEnd);
                            }else{
                                $q->whereGt('third_promise_time',0);
                            }
                        });
                    });
                    break;
            }
        }else{
            $LMongo = $LMongo->where(function($query) use($RangeDeliveryStart, $RangeDeliveryEnd){
                $query->where(function($q) use($RangeDeliveryStart, $RangeDeliveryEnd){
                    $q->where(function($p) use($RangeDeliveryStart, $RangeDeliveryEnd){
                        $p->whereExists('first_slow')->whereGt('first_slow',$RangeDeliveryStart);
                        if(!empty($RangeDeliveryEnd)) $p->whereLte('first_slow', $RangeDeliveryEnd);
                    })->orWhere(function($p) use($RangeDeliveryStart, $RangeDeliveryEnd){
                        $p->where('first_slow', null)->whereLt('first_promise_time',$this->time() - $RangeDeliveryStart);
                        if(!empty($RangeDeliveryEnd)){
                            $p->whereGte('first_promise_time',$this->time() - $RangeDeliveryEnd);
                        }else{
                            $p->whereGt('first_promise_time',0);
                        }
                    });
                })->orWhere(function($q) use($RangeDeliveryStart, $RangeDeliveryEnd){
                    $q->where(function($p) use($RangeDeliveryStart, $RangeDeliveryEnd){
                        $p->whereExists('second_slow')->whereGt('second_slow',$RangeDeliveryStart);
                        if(!empty($RangeDeliveryEnd)) $p->whereLte('second_slow', $RangeDeliveryEnd);
                    })->orWhere(function($p) use($RangeDeliveryStart, $RangeDeliveryEnd){
                        $p->where('second_slow', null)->whereLt('second_promise_time',$this->time() - $RangeDeliveryStart);
                        if(!empty($RangeDeliveryEnd)){
                            $p->whereGte('second_promise_time',$this->time() - $RangeDeliveryEnd);
                        }else{
                            $p->whereGt('second_promise_time',0);
                        }
                    });
                })->orWhere(function($q) use($RangeDeliveryStart, $RangeDeliveryEnd){
                    $q->where(function($p) use($RangeDeliveryStart, $RangeDeliveryEnd){
                        $p->whereExists('third_slow')->whereGt('third_slow',$RangeDeliveryStart);
                        if(!empty($RangeDeliveryEnd)) $p->whereLte('third_slow', $RangeDeliveryEnd);
                    })->orWhere(function($p) use($RangeDeliveryStart, $RangeDeliveryEnd){
                        $p->where('third_slow', null)->whereLt('third_promise_time',$this->time() - $RangeDeliveryStart);
                        if(!empty($RangeDeliveryEnd)){
                            $p->whereGte('third_promise_time',$this->time() - $RangeDeliveryEnd);
                        }else{
                            $p->whereGt('third_promise_time',0);
                        }
                    });
                });
            });

        }

        if($Cmd == 'export'){
            $Data               = $LMongo->orderBy('time_accept','asc')->get()->toArray();
            $ListDeliverySlow   = [];
            $PostOffice         = [];

            if(!empty($Data)){
                $ListOrderId    = [];
                foreach($Data as $val){
                    if($val['order_id'] > 0){
                        $ListOrderId[]   = (int)$val['order_id'];
                    }

                    if($val['from_city_id'] > 0){
                        $this->list_city_id[]   = (int)$val['from_city_id'];
                    }
                    if($val['to_city_id'] > 0){
                        $this->list_city_id[]   = (int)$val['to_city_id'];
                    }

                    if($val['from_district_id'] > 0){
                        $this->list_district_id[]   = (int)$val['from_district_id'];
                    }

                    if($val['to_district_id'] > 0){
                        $this->list_district_id[]   = (int)$val['to_district_id'];
                    }
                }

                if(!empty($this->list_district_id)){
                    $this->list_district_id = array_unique($this->list_district_id);
                    $this->list_district_id = $this->getProvince($this->list_district_id);
                }

                if(!empty($this->list_city_id)){
                    $this->list_city_id = array_unique($this->list_city_id);
                    $this->list_city_id = $this->getCityById($this->list_city_id);
                }

                //  delivery slow
                if(!empty($ListOrderId)){
                    $ListOrderStatus    = StatusModel::whereRaw("order_id in (". implode(",", $ListOrderId) .")")->where('status','>',51)->orderBy('time_create','ASC')->get()->toArray();
                    $ListDeliverySlow   = [];
                    if(!empty($ListOrderStatus)){
                        foreach($ListOrderStatus as $val){
                            if(!isset($ListDeliverySlow[(int)$val['order_id']])) $ListDeliverySlow[(int)$val['order_id']] = '';
                            $ListDeliverySlow[(int)$val['order_id']]   .= $val['note'].', ';
                        }
                    }

                    $ListPost           = \ordermodel\PostOfficeModel::whereRaw("order_id in (". implode(",", $ListOrderId) .")")->get()->toArray();
                    if(!empty($ListPost)){
                        foreach($ListPost as $val){
                            $PostOffice[$val['order_id']]   = $val;
                        }
                    }
                }
            }

            return Response::json([
                'error'             => $this->error,
                'message'           => $this->message,
                'total'             => $this->total,
                'data'              => $Data,
                'list_city'         => $this->list_city_id,
                'list_district'     => $this->list_district_id,
                'post_office'       => $PostOffice,
                'note'              => $ListDeliverySlow
            ]);
        }

        //Thống kê lý do
        if($Cmd == 'statistic'){
            $Data               = $LMongo->orderBy('time_accept','asc')->get()->toArray();
            $ListDeliverySlow   = [];
            $ListProblem        = [];
            $TotalGroup         = ['total' => 0];

            if(!empty($Data)){
                $ListOrderId    = [];
                foreach($Data as $val){
                    if($val['order_id'] > 0){
                        $ListOrderId[]   = (int)$val['order_id'];
                    }

                    if($val['from_city_id'] > 0){
                        $this->list_city_id[]   = (int)$val['from_city_id'];
                    }
                    if($val['to_city_id'] > 0){
                        $this->list_city_id[]   = (int)$val['to_city_id'];
                    }

                    if($val['from_district_id'] > 0){
                        $this->list_district_id[]   = (int)$val['from_district_id'];
                    }

                    if($val['to_district_id'] > 0){
                        $this->list_district_id[]   = (int)$val['to_district_id'];
                    }
                }

                if(!empty($this->list_district_id)){
                    $this->list_district_id = array_unique($this->list_district_id);
                    $this->list_district_id = $this->getProvince($this->list_district_id);
                }

                if(!empty($this->list_city_id)){
                    $this->list_city_id = array_unique($this->list_city_id);
                    $this->list_city_id = $this->getCityById($this->list_city_id);
                }

                //  delivery slow
                if(!empty($ListOrderId)){
                    $ListOrderStatus    = StatusModel::whereRaw("order_id in (". implode(",", $ListOrderId) .")")->whereIn('status',[76,77])->orderBy('time_create','ASC')->get()->toArray();

                    if(!empty($ListOrderStatus)){
                        foreach($ListOrderStatus as $val){
                            if(!isset($ListDeliverySlow[(int)$val['order_id']])){
                                $ListDeliverySlow[(int)$val['order_id']] = '';
                                $ListProblem[(int)$val['order_id']]      = '';
                            }

                            $Problem = $this->__check_problem($val['note']);
                            if(!isset($TotalGroup[$Problem])){
                                $TotalGroup[$Problem]   = 0;
                            }

                            $TotalGroup[$Problem] += 1;
                            $TotalGroup['total']  += 1;

                            //Check problem
                            $ListProblem[(int)$val['order_id']]         = $Problem;
                            $ListDeliverySlow[(int)$val['order_id']]   .= $val['note'].', ';
                        }
                    }
                }
            }

            return Response::json([
                'error'         => $this->error,
                'message'       => $this->message,
                'total'         => $this->total,
                'data'          => $Data,
                'list_city'     => $this->list_city_id,
                'list_district' => $this->list_district_id,
                'note'          => $ListDeliverySlow,
                'problem'       => $ListProblem,
                'total_group'   => $TotalGroup
            ]);
        }

        $TotalModel     = clone $LMongo;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $LMongo     = $LMongo->skip($offset)->take($itemPage);

            $Data       = $LMongo->orderBy('time_accept','asc')->get()->toArray();
            if(!empty($Data)){
                $ListOrderId    = [];
                foreach($Data as $val){
                    if($val['order_id'] > 0){
                        $ListOrderId[]   = (int)$val['order_id'];
                    }

                    if($val['from_city_id'] > 0){
                        $this->list_city_id[]   = (int)$val['from_city_id'];
                    }
                    if($val['to_city_id'] > 0){
                        $this->list_city_id[]   = (int)$val['to_city_id'];
                    }

                    if($val['from_district_id'] > 0){
                        $this->list_district_id[]   = (int)$val['from_district_id'];
                    }

                    if($val['to_district_id'] > 0){
                        $this->list_district_id[]   = (int)$val['to_district_id'];
                    }
                }

                if(!empty($ListOrderId) && !empty($PipeStatus) && !empty($Group)){
                    $ListJourney   = PipeJourneyModel::where('group_process',$Group)->where('type', $TypeProcess)
                                                           ->where('time_create','>=',$TimeAcceptStart - 86400*30)->whereIn('tracking_code', $ListOrderId)->orderBy('time_create','ASC')->get()->toArray();
                    if(!empty($ListJourney)){
                        foreach($ListJourney as $val){
                            $this->list_journey[$val['tracking_code']]      =    $val;
                        }
                    }
                }

                if(!empty($this->list_city_id)){
                    $this->list_city_id = array_unique($this->list_city_id);
                    $this->list_city_id = $this->getCityById($this->list_city_id);
                }

                if(!empty($this->list_district_id)){
                    $this->list_district_id = array_unique($this->list_district_id);
                    $this->list_district_id = $this->getProvince($this->list_district_id);
                }

                $this->data = $Data;
            }
        }

        return $this->ResponseData();
    }

    public function getPickupSlow(){
        $itemPage           = 20;
        $this->error        = false;
        $this->message      = 'success';

        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')               : 0; // time_accept start
        $TimeAcceptEnd      = Input::has('accept_end')          ? (int)Input::get('accept_end')                 : 0; // time_accept end

        $PickupStart        = Input::has('pickup_start')        ? (int)Input::get('pickup_start')               : 0; // time_pickup start
        $PickupEnd          = Input::has('pickup_end')          ? (int)Input::get('pickup_end')                 : 0; // time_pickup end
        $ServiceId          = Input::has('service')             ? (int)Input::get('service')                    : 0;
        $Domain             = Input::has('domain')              ? trim(Input::get('domain'))                    : 0;
        $KeyWord            = Input::has('keyword')             ? trim(Input::get('keyword'))                   : 0;
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $PipeStatus         = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))           : '';
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : 107;
        $TypeProcess        = Input::has('type-process')        ? (int)Input::get('type-process')           : 5;

        $FromCity           = Input::has('from_city')           ? (int)Input::get('from_city')              : 0;
        $FromDistrict       = Input::has('from_district')       ? (int)Input::get('from_district')          : 0;

        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))           : '';
        $PickupSlow         = Input::has('pickup_slow')         ? (int)Input::get('pickup_slow')            : null;
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 0;
        $Loyalty            = Input::has('loyalty')             ? (int)Input::get('loyalty')                : null;

        $NoPickup           = Input::has('no_pickup')           ? (int)Input::get('no_pickup')              : 0;

        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';

        $GroupOrder         = Input::has('group_order')         ? (int)Input::get('group_order')            : 108;
        $PipeStatusOrder    = Input::has('pipe_status_order')   ? trim(Input::get('pipe_status_order'))     : '';

        $FromCountryId      = Input::has('from_country_id')     ? (int)Input::get('from_country_id')            : 237;
        $Global             = Input::has('global')              ? (int)Input::get('global')                     : null;

        /*
         * Khu vực
         */

        $LMongo     = new LMongo;
        $LMongo     = $LMongo::collection('log_journey_pickup')->whereGt('active',0)->where('message','SUCCESS')
                             ->where('from_country_id', $FromCountryId)
                             ->whereGt('from_address_id',0)->whereGte('status',30)->whereNe('status',78);

        if(!empty($Global)){
            $LMongo  = $LMongo->whereNe('to_country_id', $FromCountryId);
        }

        if(!empty($ListStatus) && !$TrackingCode){
            $ListStatus = array_map('intval', explode(',',$ListStatus));
            $LMongo     = $LMongo->whereIn('status',$ListStatus);
        }

        if(!empty($TimeAcceptStart)){
            $LMongo          = $LMongo->whereGte('time_accept',$TimeAcceptStart);
        }else{
            $this->error = true;
            return $this->ResponseData();
        }

        if(!empty($TimeAcceptEnd)){
            $LMongo          = $LMongo->whereLte('time_accept',$TimeAcceptEnd);
        }

        if(!empty($PickupStart)){
            $LMongo          = $LMongo->whereGte('time_pickup',$PickupStart);
        }

        if(!empty($PickupEnd)){
            $LMongo          = $LMongo->whereLte('time_pickup',$PickupEnd);
        }

        if(!empty($ServiceId)){
            $LMongo          = $LMongo->where('service_id',$ServiceId);
        }

        if(!empty($Domain)){
            $LMongo          = $LMongo->where('domain',$Domain);
        }

        if(isset($Loyalty)){
            $ListUser       = \loyaltymodel\UserModel::where('level',$Loyalty)->remember(60)->lists('user_id');

            // ko có dữ liệu , return []
            if(empty($ListUser)){
                $this->error = true;
                return;
            }
        }

        if(!empty($KeyWord)){
            $UserModel      = new User;

            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $UserModel          = $UserModel->where('email',$KeyWord);
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $UserModel          = $UserModel->where('phone',$KeyWord);
            }else{ // search code
                $UserModel          = $UserModel->where('fullname',$KeyWord);
            }

            $ListUserSearch = $UserModel->lists('id');
            if(empty($ListUserSearch)){
                return $this->ResponseData();
            }else{
                if(!empty($ListUser)){
                    $ListUser   = array_intersect($ListUser, $ListUserSearch);
                }else{
                    $ListUser   = $ListUserSearch;
                }
            }

            if(empty($ListUser)){
                return $this->ResponseData();
            }
        }
        
        if(!empty($ListUser)){
            $LMongo = $LMongo->whereIn('from_user_id', $ListUser);
        }

        if(!empty($TrackingCode)){
            $LMongo     = $LMongo->where('tracking_code', $TrackingCode);
        }

        if(!empty($CourierId)){
            $LMongo          = $LMongo->where('courier_id',$CourierId);
        }

        if(!empty($PipeStatus) && !empty($Group)){
            $PipeStatus = explode(',',$PipeStatus);
            $ListId = PipeJourneyModel::where('time_create','>=',$TimeAcceptStart - 86400*30)->where('type', $TypeProcess)->where('group_process',$Group)->whereIn('pipe_status', $PipeStatus)->lists('tracking_code');

            if(!empty($ListId)){
                $ListId = array_unique($ListId);
                $LMongo  = $LMongo->whereIn('order_id', $ListId);
            }else{
                return $this->ResponseData();
            }
        }

        if(!empty($PipeStatusOrder) && !empty($GroupOrder)){
            $PipeStatusOrder = explode(',',$PipeStatusOrder);
            $ListId = PipeJourneyModel::where('time_create','>=',$TimeAcceptStart - 86400*30)->where('type', $TypeProcess)->where('group_process',$GroupOrder)->whereIn('pipe_status', $PipeStatusOrder)->lists('tracking_code');

            if(!empty($ListId)){
                $ListId = array_unique($ListId);
                $LMongo  = $LMongo->whereIn('order_id', $ListId);
            }else{
                return $this->ResponseData();
            }
        }

        if(!empty($FromDistrict)){
            $LMongo          = $LMongo->where('from_district_id',$FromDistrict);
        }elseif(!empty($FromCity)){
            $LMongo          = $LMongo->where('from_city_id',$FromCity);
        }

        if(!empty($NoPickup)){
            $DevMongo   = clone  $LMongo;
            $Time       = strtotime(date('Y-m-d')) - 86400*$NoPickup;

            $ListOrder  = $DevMongo->whereGte('time_pickup', $Time)->get()->toArray();
            if(!empty($ListOrder)){
                $ListNotIn  = [];
                foreach($ListOrder as $val){
                    $ListNotIn[]    = (int)$val['from_address_id'];
                }

                $ListNotIn = array_unique($ListNotIn);
                $LMongo    = $LMongo->whereNin('from_address_id', $ListNotIn);
            } 
        }

        $RangePickupStart = 0;
        $RangePickupEnd   = 0;
        if(!empty($PickupSlow)){
            switch ((int)$PickupSlow) {
                case 4:
                    $RangePickupStart   = 1;
                    $RangePickupEnd     = 14400;
                    break;
                case 8:
                    $RangePickupStart   = 14400;
                    break;
                case 24:
                    $RangePickupStart   = 28800;
                    break;
                default:
                    $RangePickupStart   = 86400;
            }

            $LMongo = $LMongo->where(function($query) use($RangePickupStart, $RangePickupEnd){
                $query->where(function($q) use($RangePickupStart, $RangePickupEnd){
                    $q->whereExists('time_slow')->whereGt('time_slow',$RangePickupStart);
                    if(!empty($RangePickupEnd)) $q->whereLte('time_slow', $RangePickupEnd);
                })->orWhere(function($q) use($RangePickupStart, $RangePickupEnd){
                    $q->where('time_slow', null)->whereLt('promise_pickup_time',$this->time() - $RangePickupStart);
                    if(!empty($RangePickupEnd)){
                        $q->whereGte('promise_pickup_time',$this->time() - $RangePickupEnd);
                    }else{
                        $q->whereGt('promise_pickup_time',0);
                    }
                });
            });
        }

        $Data = $LMongo->get()->toArray();

        if($Cmd == 'export'){
            $Data               = $LMongo->orderBy('time_accept','asc')->get()->toArray();
            $ListInventory      = [];

            if(!empty($Data)){
                $ListInventory    = [];
                $ListOrderId      = [];

                foreach($Data as $val){
                    if($val['order_id'] > 0){
                        $ListOrderId[]   = (int)$val['order_id'];
                    }

                    if($val['from_address_id'] > 0){
                        $ListInventory[]    = (int)$val['from_address_id'];
                    }

                    if($val['from_district_id'] > 0){
                        $this->list_district_id[]   = (int)$val['from_district_id'];
                    }

                    if($val['from_ward_id'] > 0){
                        $this->list_ward_id[]       = (int)$val['from_ward_id'];
                    }
                }

                if(!empty($this->list_district_id)){
                    $this->list_district_id = array_unique($this->list_district_id);
                    $this->list_district_id = $this->getProvince($this->list_district_id);
                }

                if (!empty($this->list_ward_id)) {
                    $this->list_ward_id = array_unique($this->list_ward_id);
                    $this->list_ward_id = $this->getWard($this->list_ward_id);
                }

                if(!empty($ListInventory)){
                    $Inventory      = \sellermodel\UserInventoryModel::whereRaw("id in (". implode(",", $ListInventory) .")")->get(['id','name','user_name','phone'])->toArray();
                    $ListInventory  = [];
                    foreach($Inventory as $val){
                        $ListInventory[$val['id']]  = $val;
                    }
                }

                if(!empty($ListOrderId)){
                    $ListOrderId    = array_chunk($ListOrderId,1000);
                    foreach($ListOrderId as $val){
                        $ListJourney = PipeJourneyModel::where('group_process', $Group)->where('type', $TypeProcess)
                            ->where('time_create', '>=', $TimeAcceptStart - 86400 * 30)->whereRaw("tracking_code in (". implode(",", $val) .")")->orderBy('time_create', 'ASC')->get()->toArray();
                        if (!empty($ListJourney)) {
                            foreach ($ListJourney as $v) {
                                $this->list_journey[$v['tracking_code']][] = $v;
                            }
                        }
                    }
                }

            }

            return Response::json([
                'error'         => $this->error,
                'message'       => $this->message,
                'total'         => $this->total,
                'data'          => $Data,
                'list_district' => $this->list_district_id,
                'list_ward'     => $this->list_ward_id,
                'list_inventory' => $ListInventory,
                'list_pipe_journey' => $this->list_journey
            ]);
        }


        if (!empty($Data)) {
            $ListInventory = [];
            $InventorySlow = [];
            $Inventory     = [];
            foreach ($Data as $val) {
                $ListInventory[] = (int)$val['from_address_id'];

                if (!isset($InventorySlow[(int)$val['from_address_id']])) {
                    $Inventory[(int)$val['from_address_id']]                 = 1;
                    $InventorySlow[(int)$val['from_address_id']]['num_slow'] = 1;
                    $InventorySlow[(int)$val['from_address_id']]['from_address_id']     = (int)$val['from_address_id'];
                    $InventorySlow[(int)$val['from_address_id']]['from_city_id']        = (int)$val['from_city_id'];
                    $InventorySlow[(int)$val['from_address_id']]['from_district_id']    = (int)$val['from_district_id'];
                    $InventorySlow[(int)$val['from_address_id']]['from_ward_id']        = (int)$val['from_ward_id'];
                } else {
                    $Inventory[(int)$val['from_address_id']]                 += 1;
                    $InventorySlow[(int)$val['from_address_id']]['num_slow'] += 1;
                }
            }

            $ListInventory  = array_unique($ListInventory);
            $this->total    = count($ListInventory);

            array_multisort($Inventory, SORT_DESC, $InventorySlow);

            $offset             = ($page - 1)*$itemPage;
            $InventorySlow      = array_slice($InventorySlow,$offset, $itemPage);

            $ListInventory  = [];
            $Inventory      = [];
            foreach($InventorySlow as $val){
                $ListInventory[]                        = $val['from_address_id'];
                $Inventory[$val['from_address_id']]     = $val['num_slow'];

                if($val['from_city_id'] > 0){
                    $this->list_city_id[]   = (int)$val['from_city_id'];
                }

                if ($val['from_district_id'] > 0) {
                    $this->list_district_id[] = (int)$val['from_district_id'];
                }

                if ($val['from_ward_id'] > 0) {
                    $this->list_ward_id[] = (int)$val['from_ward_id'];
                }
            }

            // get pipe journey
            if (!empty($ListInventory)) {
                $ListJourney = PipeJourneyModel::where('group_process', $Group)->where('type', $TypeProcess)
                    ->where('time_create', '>=', $TimeAcceptStart - 86400 * 30)->whereIn('tracking_code', $ListInventory)->orderBy('time_create', 'ASC')->get()->toArray();
                if (!empty($ListJourney)) {
                    foreach ($ListJourney as $val) {
                        $this->list_journey[$val['tracking_code']][] = $val;
                    }
                }
            }

            //get Inventory Info
            $this->data = \sellermodel\UserInventoryModel::whereIn('id', $ListInventory)->with(['user'])->get()->toArray();

            foreach ($this->data as $key => $val) {
                $this->data[$key]['pipe_status'] = 0;
                if(isset($this->list_journey[$val['id']])){
                    $this->data[$key]['pipe_journey']   = $this->list_journey[$val['id']];
                    foreach($this->list_journey[$val['id']] as $v){
                        $this->data[$key]['pipe_status'] = (int)$v['pipe_status'];
                    }
                }else{
                    $this->data[$key]['pipe_journey']   = [];
                }

                if (isset($Inventory[$val['id']])) {
                    $this->data[$key]['num_slow'] = $Inventory[$val['id']];
                } else {
                    $this->data[$key]['num_slow'] = 0;
                }
            }

            if(!empty($this->list_city_id)){
                $this->list_city_id = array_unique($this->list_city_id);
                $this->list_city_id = $this->getCityById($this->list_city_id);
            }

            if (!empty($this->list_district_id)) {
                $this->list_district_id = array_unique($this->list_district_id);
                $this->list_district_id = $this->getProvince($this->list_district_id);
            }

            if (!empty($this->list_ward_id)) {
                $this->list_ward_id = array_unique($this->list_ward_id);
                $this->list_ward_id = $this->getWard($this->list_ward_id);
            }
        }

        return $this->ResponseData();
    }

    public function ReportExcel($Model, $ListPipe = []){
        $Data = $Model->get(['id', 'tracking_code', 'order_code', 'courier_id', 'courier_tracking_code', 'service_id', 'status', 'from_district_id',
            'from_ward_id','from_city_id','from_address', 'to_address_id', 'from_user_id', 'to_name', 'to_email', 'to_phone', 'time_accept',
            'time_pickup', 'time_create','time_success', 'to_address_id'])->toArray();

        $Address        = [];
        $User           = [];
        $ListUserId     = [];
        $ListOrderId    = [];
        $PostOffice     = [];

        if(!empty($Data)){
            foreach($Data as $key => $val){
                $ListOrderId[]      = $val['id'];
                $ListToAddress[]    = $val['to_address_id'];
                $ListUserId[]       = $val['from_user_id'];

                if(isset($ListPipe[$val['id']])){
                    $Data[$key]['journey']  = $ListPipe[$val['id']];
                }
            }

            if(isset($ListToAddress) && !empty($ListToAddress)){
                $AddressModel   = new AddressModel;
                $ListAddress    = $AddressModel::whereIn('id', $ListToAddress)->get()->toArray();
            }

            if(isset($ListAddress) && !empty($ListAddress)){
                foreach($ListAddress as $val){
                    if(!empty($val)){
                        $Address[$val['id']]    = $val;
                    }
                }
            }

            $ListUserId     = array_unique($ListUserId);

            if(!empty($ListUserId)){
                $UserModel = new \User;
                $User       = [];

                $ListUser   = $UserModel->whereRaw("id in (". implode(",", $ListUserId) .")")->with('user_info')->get(['id','fullname', 'phone', 'email', 'time_create'])->toArray();
                if(!empty($ListUser)){
                    foreach($ListUser as $val){
                        $User[$val['id']]   = $val;
                    }
                }
            }

            $ListPost           = \ordermodel\PostOfficeModel::whereRaw("order_id in (". implode(",", $ListOrderId) .")")->get()->toArray();
            if(!empty($ListPost)){
                foreach($ListPost as $val){
                    $PostOffice[$val['order_id']]   = $val;
                }
            }
        }
        
        return Response::json([
            'error'             => $this->error,
            'message'           => $this->message,
            'data'              => $Data,
            'user'              => $User,
            'list_to_address'   => $Address,
            'post_office'       => $PostOffice
        ]);
    }

    public function getCountgroup(){
        $this->error        = false;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))               : '';
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $WareHouse          = Input::has('warehouse')           ? Input::get('warehouse')                       : "";

        $Model          = $this->getModel();

        if(!$this->error){
            if(!empty($ListStatus) && !$TrackingCode){
                $ListStatus = explode(',',$ListStatus);
                $Model          = $Model->whereIn('status',$ListStatus);
            }

            if(!empty($WareHouse)){
                $Model          = $Model->where('warehouse',$WareHouse);
            }

            $GroupStatus    = $Model->groupBy('courier_id')->get(array('courier_id',DB::raw('count(*) as count')))->toArray();

            if(!empty($GroupStatus)){
                foreach($GroupStatus as $val){
                    if(!isset($this->total_group[(int)$val['courier_id']])){
                        $this->total_group[(int)$val['courier_id']] = 0;
                    }
                    $this->total_group[(int)$val['courier_id']] += $val['count'];
                    $this->total_all                            += $val['count'];
                }
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'total'         => $this->total_all,
            'data'          => $this->total_group
        ]);
    }

    public function getCountgroupstatus(){
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))               : '';
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $WareHouse          = Input::has('warehouse')           ? Input::get('warehouse')                       : "";

        $this->error        = false;
        $Model          = $this->getModel();

        if(!$this->error){
            if(!empty($ListStatus) && !$TrackingCode){
                $ListStatus = explode(',',$ListStatus);
                $Model          = $Model->whereIn('status',$ListStatus);
            }
            
            if(!empty($CourierId)){
                $Model          = $Model->where('courier_id',$CourierId);
            }

            if(!empty($WareHouse)){
                $Model          = $Model->where('warehouse',$WareHouse);
            }

            $GroupStatus    = $Model->groupBy('status')->get(array('status',DB::raw('count(*) as count')))->toArray();

            if(!empty($GroupStatus)){
                foreach($GroupStatus as $val){
                    if(!isset($this->total_group[(int)$val['status']])){
                        $this->total_group[(int)$val['status']] = 0;
                    }
                    $this->total_group[(int)$val['status']] += $val['count'];
                    $this->total_all                            += $val['count'];
                }
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'total'         => $this->total_all,
            'data'          => $this->total_group
        ]);
    }

    public function getCountgroupwarehouse(){
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))               : '';
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';

        $this->error        = false;
        $Model          = $this->getModel();

        if(!$this->error){
            if(!empty($ListStatus) && !$TrackingCode){
                $ListStatus = explode(',',$ListStatus);
                $Model          = $Model->whereIn('status',$ListStatus);
            }

            if(!empty($CourierId)){
                $Model          = $Model->where('courier_id',$CourierId);
            }

            $GroupStatus    = $Model->groupBy('warehouse')->get(array('warehouse',DB::raw('count(*) as count')))->toArray();

            if(!empty($GroupStatus)){
                foreach($GroupStatus as $val){
                    $val['warehouse']   = strtoupper($val['warehouse']);
                    if(!isset($this->total_group[$val['warehouse']])){
                        $this->total_group[$val['warehouse']] = 0;
                    }
                    $this->total_group[$val['warehouse']]       += $val['count'];
                    $this->total_all                            += $val['count'];
                }
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'total'         => $this->total_all,
            'data'          => $this->total_group
        ]);
    }

    private function ResponseData(){

        return Response::json([
            'error'         => $this->error,
            'message'       => $this->message,
            'total'         => $this->total,
            'data'          => $this->data,
            'list_city'     => $this->list_city_id,
            'list_district' => $this->list_district_id,
            'list_ward'     => $this->list_ward_id,
            'list_to_address'   => $this->list_to_address,
            'list_from_address' => $this->list_from_address,
            'list_pipe_journey' => $this->list_journey,
            'list_postoffice'   => $this->list_postoffice
        ]);
    }

    public function ExportExcel($Model){
        $PipeStatus         = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))           : '';
        $DeliverySlow       = Input::has('delivery_slow')       ? (int)Input::get('delivery_slow')          : null;
        $Domain             = Input::has('domain')              ? Input::get('domain')                         : "";

        $FileName   = 'Danh_sach_van_đon';

        $Data               = [];

        $Address            = [];
        $FromAddress        = [];
        $City               = [];
        $District           = [];
        $Ward               = [];
        $User               = [];

        $ListCityId         = [];
        $ListDistrictId     = [];
        $ListWardId         = [];

        $ListUserId         = [];
        $ListOrderStatus    = []; //Danh sách đơn hàng   khách hàng yêu cầu phát lại 67, Chờ lấy hàng lần 2 38
        $ListAcceptReturn   = []; // Danh sách đơn hàng đã xác nhận chuyển hoàn
        $ListReturn         = []; // Danh sách đơn hàng chuyển hoàn
        $StatusProcess      = [];
        $OrderId            = [];
        $TimeAcceptReturn   = [];
        $ListDeliverySlow   = [];

        $Courier    = $this->getCourier(false);
        $Service    = $this->getService(false);
        $Status     = $this->getStatus(false);

        if(!empty($Model)){

            $Model->with(['OrderDetail','__post_office'])->chunk('1000', function($query) use(&$Data, &$ListCityId, &$ListDistrictId, &$ListWardId, &$ListToAddress, &$ListFromAddress,  &$ListUserId, &$ListOrderStatus, &$PipeStatus, &$OrderId, &$ListAcceptReturn, &$ListReturn, &$DeliverySlow, &$ListDeliverySlow){
                foreach($query as $val){
                    $val                = $val->toArray();
                    $Data[]             = $val;
                    $ListCityId[]       = $val['from_city_id'];
                    $ListCityId[]       = $val['to_city_id'];
                    $ListDistrictId[]   = $val['to_district_id'];
                    $ListDistrictId[]   = $val['from_district_id'];
                    $ListWardId[]       = $val['from_ward_id'];
                    $ListToAddress[]    = $val['to_address_id'];
                    $ListFromAddress[]  = $val['from_address_id'];
                    $ListUserId[]       = $val['from_user_id'];

                    if(in_array($val['status'], [38,67])){
                        $ListOrderStatus[]  =  (int)$val['id'];
                    }

                    if(isset($ListDeliverySlow)){
                        $ListDeliverySlow[] = (int)$val['id'];
                    }

                    if($PipeStatus == 707 || $PipeStatus == 903){
                        $OrderId[]  = (int)$val['id'];
                    }

                    if(in_array($val['status'], [61,62,63,64,65])){
                        $ListAcceptReturn[]  =  (int)$val['id'];
                    }

                    if($val['status'] == 66){
                        $ListReturn[]   = (int)$val['id'];
                    }
                }
            });

            if(!empty($Data)){

                if(isset($ListToAddress) && !empty($ListToAddress)){
                    $AddressModel   = new AddressModel;
                    $ListAddress    = $AddressModel::whereRaw("id in (". implode(",", $ListToAddress) .")")->get()->toArray();
                }

                if(isset($ListFromAddress) && !empty($ListFromAddress)){
                    $InventoryModel  = new UserInventoryModel;
                    $_FromAddress     = $InventoryModel::whereRaw("id in (". implode(",", $ListFromAddress) .")")->get()->toArray();
                }

                
                if(isset($_FromAddress) && !empty($_FromAddress)){
                    foreach($_FromAddress as $val){
                        if(!empty($val)){
                            $FromAddress[$val['id']]    = $val;
                            $ListWardId[]               = (int)$val['ward_id'];
                        }
                    }
                }


                if(isset($ListAddress) && !empty($ListAddress)){
                    foreach($ListAddress as $val){
                        if(!empty($val)){
                            $Address[$val['id']]    = $val;
                            $ListWardId[]           = (int)$val['ward_id'];
                        }
                    }
                }

                $ListCityId     = array_unique($ListCityId);
                $ListDistrictId = array_unique($ListDistrictId);
                $ListWardId     = array_unique($ListWardId);
                $ListUserId     = array_unique($ListUserId);

                if(!empty($ListCityId)){
                    $City   = $this->getCityById($ListCityId);
                }

                if(!empty($ListDistrictId)){
                    $District   = $this->getProvince($ListDistrictId);
                }

                if(!empty($ListUserId)){
                    $UserModel = new \User;
                    $User       = [];

                    $ListUser   = $UserModel->whereRaw("id in (". implode(",", $ListUserId) .")")->get(['id','fullname', 'phone', 'email', 'time_create'])->toArray();
                    if(!empty($ListUser)){
                        foreach($ListUser as $val){
                            $User[$val['id']]   = $val;
                        }
                    }
                }

                if(!empty($ListWardId)){
                    $WardModel = new WardModel;
                    $ListWard  =  $WardModel::whereRaw("id in (". implode(",", $ListWardId) .")")->get(['id','ward_name'])->toArray();
                    if(!empty($ListWard)){
                        foreach($ListWard as $val){
                            if(!empty($Ward[$val['id']])){
                                $Ward[$val['id']]   = $val['ward_name'];
                            }

                        }
                    }
                }

                if(!empty($ListOrderStatus) && empty($OrderId)){
                    $StatusModel        = new StatusModel;
                    $ListOrderStatus    = $StatusModel::whereRaw("order_id in (". implode(",", $ListOrderStatus) .")")->whereIn('status',[38,67])->orderBy('time_create','ASC')->get()->toArray();
                    if(!empty($ListOrderStatus)){
                        foreach($ListOrderStatus as $val){
                            if(!isset($StatusProcess[(int)$val['order_id']])){
                                $StatusProcess[(int)$val['order_id']]  = '';
                            }
                            $StatusProcess[(int)$val['order_id']]   .= $val['note'].',';

                        }
                    }
                }

                //get list request
                if(!empty($OrderId)){
                    $PipeJourneyModel   = new PipeJourneyModel;
                    $ListOrderStatus    = $PipeJourneyModel::whereRaw("tracking_code in (". implode(",", $OrderId) .")")
                        ->where('type',1)
                        ->whereIn('pipe_status',[707, 903])
                        ->orderBy('time_create','ASC')->get()->toArray();
                    if(!empty($ListOrderStatus)){
                        foreach($ListOrderStatus as $val){
                            if(!isset($StatusProcess[(int)$val['tracking_code']])){
                                $StatusProcess[(int)$val['tracking_code']]  = '';
                            }
                            $StatusProcess[(int)$val['tracking_code']]   .= $val['note'].',';

                        }
                    }
                }

                //get list order status  accept return
                if(!empty($ListAcceptReturn)){
                    $StatusModel        = new StatusModel;
                    $ListOrderStatus    = $StatusModel::whereRaw("order_id in (". implode(",", $ListAcceptReturn) .")")->where('status',61)->orderBy('time_create','ASC')->get()->toArray();
                    if(!empty($ListOrderStatus)){
                        foreach($ListOrderStatus as $val){
                            $TimeAcceptReturn[(int)$val['order_id']]   = $val['time_create'];
                        }
                    }
                }

                //  delivery slow
                if(!empty($ListDeliverySlow)){
                    $StatusModel        = new StatusModel;
                    $ListOrderStatus    = $StatusModel::whereRaw("order_id in (". implode(",", $ListDeliverySlow) .")")->where('status','>=',40)->orderBy('time_create','ASC')->get()->toArray();
                    $ListDeliverySlow   = [];
                    if(!empty($ListOrderStatus)){
                        foreach($ListOrderStatus as $val){
                            $ListDeliverySlow[(int)$val['order_id']]   = $val['note'];
                        }
                    }
                }

                if(!empty($ListReturn)){
                    Input::merge(['group' => 4]);
                    $StatusByGroup  = $this->getStatusByGroup(false);
                    if(!empty($StatusByGroup[29])){
                        $StatusModel        = new StatusModel;
                        $ListNoteReturn     = $StatusModel::whereRaw("order_id in (". implode(",", $ListReturn) .")")->whereIn('status',$StatusByGroup[29])->orderBy('time_create','DESC')->get()->toArray();
                        $ListReturn         = [];
                        if(!empty($ListNoteReturn)){
                            foreach($ListNoteReturn as $val){
                                if(!isset($ListReturn[$val['order_id']])){
                                    $ListReturn[$val['order_id']]   = $val['note'];
                                }
                            }
                        }
                    }
                }

                //get note process;
            }
        }

        Excel::selectSheetsByIndex(0)->load('/data/www/html/storage/template_export/danh_sach_van_don.xls', function($reader) use($Data, $Courier, $Service, $City, $Address, $FromAddress, $District, $Ward, $User, $Status, $StatusProcess, $TimeAcceptReturn, $ListReturn, $ListDeliverySlow, $Domain) {
            $reader->sheet(0, function($sheet) use($Data, $Courier, $Service, $City, $Address, $FromAddress, $District, $Ward, $User, $Status, $StatusProcess, $TimeAcceptReturn, $ListReturn, $ListDeliverySlow, $Domain)
            {
                $i = 1;
                foreach ($Data as $val) {
                    $Payment    = (isset($User[(int)$val['from_user_id']]) && (isset($User[(int)$val['from_user_id']]['info']))) ? $User[(int)$val['from_user_id']]['info']['priority_payment'] : 2;

                    $Phone    = "";
                    $FullName = "";

                    if(!empty($Domain) && $Domain = 'chodientu.vn' ){
                        $Phone    = isset($FromAddress[$val['from_address_id']]) ? $FromAddress[$val['from_address_id']]['phone']       : '';
                        $FullName = isset($FromAddress[$val['from_address_id']]) ? $FromAddress[$val['from_address_id']]['user_name']   : '';
                    }else {
                        $Phone    = isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['fullname'] : '';
                        $FullName = isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['phone'] : '';
                    }

                    $dataExport = array(
                        $i++,
                        $val['time_accept'] > 0 ? date("d/m/Y H:i:s",$val['time_accept']) : '',
                        $val['time_pickup'] > 0 ? date("d/m/Y H:i:s",$val['time_pickup']) : '',
                        $val['time_success'] > 0 ? date("d/m/Y H:i:s",$val['time_success']) : '',
                        isset($val['verify_id']) ? $val['verify_id'] : '',
                        (isset($val['tracking_code'])) ? $val['tracking_code'] : '',
                        (isset($val['order_code'])) ?  ' '.$val['order_code'] :"",
                        isset($Courier[(int)$val['courier_id']]) ? $Courier[(int)$val['courier_id']]['name'] : 'HVC',
                        $val['courier_tracking_code'],
                        isset($Service[(int)$val['service_id']]) ? $Service[(int)$val['service_id']]['name'] : 'DV',
                        isset($Status[(int)$val['status']]) ? $Status[(int)$val['status']] : 'Trạng thái',

                        $FullName,//isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['fullname'] : '',
                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['email'] : '',
                        $Phone,//isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['phone'] : '',


                        isset($City[(int)$val['from_city_id']]) ? $City[(int)$val['from_city_id']] : '',
                        isset($District[(int)$val['from_district_id']]) ? $District[(int)$val['from_district_id']] : '',
                        isset($Ward[(int)$val['from_ward_id']]) ? $Ward[(int)$val['from_ward_id']] : '',
                        $val['from_address'],
                        isset($val['__post_office']['from_postoffice_code']) ? $val['__post_office']['from_postoffice_code'] : '',

                        $val['to_name'],
                        $val['to_email'],
                        $val['to_phone'],
                        (isset($Address[(int)$val['to_address_id']]) && isset($City[$Address[(int)$val['to_address_id']]['city_id']])) ? $City[$Address[(int)$val['to_address_id']]['city_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($District[$Address[(int)$val['to_address_id']]['province_id']])) ? $District[$Address[(int)$val['to_address_id']]['province_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($Ward[$Address[(int)$val['to_address_id']]['ward_id']])) ? $Ward[$Address[(int)$val['to_address_id']]['ward_id']] : '',
                        isset($Address[(int)$val['to_address_id']]) ? $Address[(int)$val['to_address_id']]['address'] : '',
                        isset($val['__post_office']['to_postoffice_code']) ? $val['__post_office']['to_postoffice_code'] : '',

                        $val['product_name'],
                        isset($val['total_amount']) ? $val['total_amount'] : '',
                        isset($val['total_weight']) ? $val['total_weight'] : '',

                        number_format($val['order_detail']['sc_pvc']),
                        ($val['status'] != 66) ? number_format($val['order_detail']['sc_cod']) : '',
                        ($val['status'] != 66) ? number_format($val['order_detail']['sc_pbh']) : '',
                        number_format($val['order_detail']['sc_pvk']),
                        number_format($val['order_detail']['sc_pch']),
                        number_format(($val['order_detail']['sc_discount_pvc'] + (($val['status'] != 66) ? $val['order_detail']['sc_discount_cod'] : 0))),
                        ($val['status'] != 66) ? number_format($val['order_detail']['money_collect']) : '',
                        ($Payment == 1 ) ? 'Vimo' : 'Ngân Lượng',
                        isset($StatusProcess[(int)$val['id']]) ? $StatusProcess[(int)$val['id']] : '',
                        isset($User[(int)$val['from_user_id']]) ? date("d/m/y H:m",$User[(int)$val['from_user_id']]['time_create']) : '',
                        isset($TimeAcceptReturn[(int)$val['id']]) ? date("d/m/y H:m",$TimeAcceptReturn[(int)$val['id']]) : '',
                        isset($ListReturn[(int)$val['id']]) ? $ListReturn[(int)$val['id']] : '',
                        isset($ListDeliverySlow[(int)$val['id']]) ? $ListDeliverySlow[(int)$val['id']] : '',
                    );

                    $sheet->appendRow($dataExport);
                }
            });
        },'UTF-8',true)->export('xls');
    }

    public function ExportExcelBlob($Model){
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')           : 1;
        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')           : 0; // time_accept start

        $Data               = [];
        $District           = [];
        $OrderId            = [];
        $PipeJourney        = [];

        if(!empty($Model)){
            $Model->with(['OrderDetail','__post_office','FromUser','ToOrderAddress'])->chunk('1000', function($query) use(&$Data, &$District,&$OrderId){
                foreach($query as $val){
                    $val                = $val->toArray();

                    $Data[]             = $val;
                    $District[]         = $val['from_district_id'];
                    $District[]         = $val['to_district_id'];

                    $OrderId[]          = $val['id'];
                }
            });

            if(!empty($Data)){

                if(!empty($District)){
                    $District = array_unique($District);
                    $District = $this->getProvince($District);
                }

                if(!empty($ListOrderId)){
                    $ListOrderId    = array_chunk($ListOrderId,1000);
                    foreach($ListOrderId as $val){
                        $ListJourney = PipeJourneyModel::where('group_process', $Group)->where('type', $TypeProcess)
                            ->where('time_create', '>=', $TimeAcceptStart - 86400 * 30)->whereRaw("tracking_code in (". implode(",", $val) .")")->orderBy('time_create', 'ASC')->get()->toArray();
                        if (!empty($ListJourney)) {
                            foreach ($ListJourney as $v) {
                                $PipeJourney[$v['tracking_code']][] = $v;
                            }
                        }
                    }
                }
            }
        }

        return Response::json([
            'error'             => $this->error,
            'message'           => $this->message,
            'total'             => $this->total,
            'data'              => $Data,
            'list_district'     => $District,
            'list_pipe_journey' => $PipeJourney
        ]);
    }

    public function ExportExcel1($Model){

        $PipeStatus         = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))           : '';
        $DeliverySlow       = Input::has('delivery_slow')       ? (int)Input::get('delivery_slow')          : null;
        $Data               = [];

        $Address            = [];
        $District           = [];
        $Ward               = [];
        $User               = [];
        $ListUserId         = [];
        $ListOrderStatus    = []; //Danh sách đơn hàng   khách hàng yêu cầu phát lại 67, Chờ lấy hàng lần 2 38
        $ListAcceptReturn   = []; // Danh sách đơn hàng đã xác nhận chuyển hoàn
        $ListReturn         = []; // Danh sách đơn hàng chuyển hoàn
        $StatusProcess      = [];
        $OrderId            = [];
        $TimeAcceptReturn   = [];
        $ListNoteReturn     = [];
        $ListDeliverySlow   = [];

        $Courier    = $this->getCourier(false);
        $Service    = $this->getService(false);
        $City       = $this->getListCity();
        $Status     = $this->getStatus(false);

        if(!empty($Model)){
            $ListField  = ['id','tracking_code','time_accept','time_pickup','time_success','order_code','courier_id','from_district_id','to_address_id','from_user_id',
                'courier_tracking_code','service_id','status','total_weight','total_amount', 'from_city_id', 'verify_id', 'from_ward_id', 'from_address',
                'to_name','to_email','to_phone', 'to_address_id', 'product_name', 'total_amount'
            ];
            $Model->with('OrderDetail')->select($ListField)->chunk('1000', function($query) use(&$Data, &$ListDistrictId, &$ListWardId, &$ListToAddress, &$ListUserId, &$ListOrderStatus, &$PipeStatus, &$OrderId, &$ListAcceptReturn, &$ListReturn, &$DeliverySlow, &$ListDeliverySlow){
                foreach($query as $val){
                    $val                = $val->toArray();
                    $Data[]             = $val;
                    $ListDistrictId[]   = $val['from_district_id'];
                    $ListWardId[]       = $val['from_ward_id'];
                    $ListToAddress[]    = $val['to_address_id'];
                    $ListUserId[]       = $val['from_user_id'];

                    if(in_array($val['status'], [38,67])){
                        $ListOrderStatus[]  =  (int)$val['id'];
                    }

                    if(isset($ListDeliverySlow)){
                        $ListDeliverySlow[] = (int)$val['id'];
                    }

                    if($PipeStatus == 707 || $PipeStatus == 903){
                        $OrderId[]  = (int)$val['id'];
                    }

                    if(in_array($val['status'], [61,62,63,64,65])){
                        $ListAcceptReturn[]  =  (int)$val['id'];
                    }

                    if($val['status'] == 66){
                        $ListReturn[]   = (int)$val['id'];
                    }
                }
            });

            if(!empty($Data)){
                if(isset($ListToAddress) && !empty($ListToAddress)){
                    $ListAddress    = AddressModel::whereRaw("id in (". implode(",", $ListToAddress) .")")->get()->toArray();
                    unset($ListToAddress);
                }

                if(isset($ListAddress) && !empty($ListAddress)){
                    foreach($ListAddress as $val){
                        if(!empty($val)){
                            $Address[$val['id']]    = $val;
                            $ListDistrictId[]       = (int)$val['province_id'];
                            $ListWardId[]           = (int)$val['ward_id'];
                        }
                    }
                    unset($ListAddress);
                }

                if(!empty($ListDistrictId)){
                    $ListDistrictId = array_unique($ListDistrictId);
                    $District   = $this->getProvince($ListDistrictId);
                    unset($ListDistrictId);
                }

                if(!empty($ListUserId)){
                    $ListUserId     = array_unique($ListUserId);
                    $User       = [];

                    $ListUser   = \User::whereRaw("id in (". implode(",", $ListUserId) .")")->get(['id','fullname', 'phone', 'email', 'time_create'])->toArray();
                    if(!empty($ListUser)){
                        foreach($ListUser as $val){
                            $User[$val['id']]   = $val;
                        }
                    }
                    unset($ListUserId);
                    unset($UserModel);
                }

                if(!empty($ListWardId)){
                    $ListWardId     = array_unique($ListWardId);
                    $ListWard  =  WardModel::whereRaw("id in (". implode(",", $ListWardId) .")")->get(['id','ward_name'])->toArray();
                    if(!empty($ListWard)){
                        foreach($ListWard as $val){
                            if(!empty($Ward[$val['id']])){
                                $Ward[$val['id']]   = $val['ward_name'];
                            }

                        }
                    }
                    unset($WardModel);
                    unset($ListWard);
                }

                if(!empty($ListOrderStatus) && empty($OrderId)){
                    $ListOrderStatus    = StatusModel::whereRaw("order_id in (". implode(",", $ListOrderStatus) .")")->whereIn('status',[38,67])->orderBy('time_create','ASC')->get()->toArray();
                    if(!empty($ListOrderStatus)){
                        foreach($ListOrderStatus as $val){
                            if(!isset($StatusProcess[(int)$val['order_id']])){
                                $StatusProcess[(int)$val['order_id']]  = '';
                            }
                            $StatusProcess[(int)$val['order_id']]   .= $val['note'].',';

                        }
                    }
                    unset($ListOrderStatus);
                    unset($StatusModel);
                }

                //get list request
                if(!empty($OrderId)){
                    $ListOrderStatus    = PipeJourneyModel::whereRaw("tracking_code in (". implode(",", $OrderId) .")")
                        ->where('type',1)
                        ->where(function($query){
                            $query->where(function($q){
                                $q->where('pipe_status',707)->where('group_process',29);
                            })->orWhere(function($q){
                                $q->where('pipe_status',903)->where('group_process',31);
                            });
                        })
                        ->orderBy('time_create','ASC')->get()->toArray();
                    if(!empty($ListOrderStatus)){
                        foreach($ListOrderStatus as $val){
                            if(!isset($StatusProcess[(int)$val['tracking_code']])){
                                $StatusProcess[(int)$val['tracking_code']]  = '';
                            }
                            $StatusProcess[(int)$val['tracking_code']]   .= $val['note'].',';

                        }
                    }
                    unset($OrderId);
                }

                //get list order status  accept return
                if(!empty($ListAcceptReturn)){
                    $ListOrderStatus    = StatusModel::whereRaw("order_id in (". implode(",", $ListAcceptReturn) .")")->where('status',61)->orderBy('time_create','ASC')->get()->toArray();
                    if(!empty($ListOrderStatus)){
                        foreach($ListOrderStatus as $val){
                            $TimeAcceptReturn[(int)$val['order_id']]   = $val['time_create'];
                        }
                    }
                    unset($ListAcceptReturn);
                    unset($ListOrderStatus);
                }

                //  delivery slow
                if(!empty($ListDeliverySlow)){
                    $ListOrderStatus    = StatusModel::whereRaw("order_id in (". implode(",", $ListDeliverySlow) .")")->where('status','>=',40)->orderBy('time_create','ASC')->get()->toArray();
                    $ListDeliverySlow   = [];
                    if(!empty($ListOrderStatus)){
                        foreach($ListOrderStatus as $val){
                            $ListDeliverySlow[(int)$val['order_id']]   = $val['note'];
                        }
                    }
                    unset($ListDeliverySlow);
                    unset($ListOrderStatus);
                }

                if(!empty($ListReturn)){
                    Input::merge(['group' => 4]);
                    $StatusByGroup  = $this->getStatusByGroup(false);
                    if(!empty($StatusByGroup[29])){
                        $ListNoteReturn     = StatusModel::whereRaw("order_id in (". implode(",", $ListReturn) .")")->whereIn('status',$StatusByGroup[29])->orderBy('time_create','DESC')->get()->toArray();
                        $ListReturn         = [];
                        if(!empty($ListNoteReturn)){
                            foreach($ListNoteReturn as $val){
                                if(!isset($ListReturn[$val['order_id']])){
                                    $ListReturn[$val['order_id']]   = $val['note'];
                                }
                            }
                        }
                    }
                    unset($ListNoteReturn);
                    unset($StatusByGroup);
                }
            }
        }

        $html = '
             <table width=\'100%\' border=\'1\'>
                <thead>
                    <tr>
                        <td colspan=\'3\' style=\'border-style:none\'></td>
                        <td colspan=\'4\' style=\'font-size: 18px; border-style:none \'><strong>Báo cáo khách hàng</strong></td>
                    </tr>
                    <tr></tr>
                    <tr style=\'font-size: 14px; background: #6b94b3\'>
                        <th rowspan=\'2\'>STT</th>
                        <th rowspan=\'2\'>TG Duyệt</th>
                        <th rowspan=\'2\'>TG Lấy Hàng</th>
                        <th rowspan=\'2\'>TG Giao Hàng</th>
                        <th rowspan=\'2\'>Mã vận đơn</th>
                        <th rowspan=\'2\'>Bảng kê</th>
                        <th rowspan=\'2\'>Mã Order</th>
                        <th rowspan=\'2\'>HVC</th>
                        <th rowspan=\'2\'>Mã HVC</th>
                        <th rowspan=\'2\'>Dịch Vụ</th>
                        <th rowspan=\'2\'>Trạng thái</th>

                        <th colspan=\'7\'>Gửi</th>
                        <th colspan=\'7\'>Nhận</th>
                        <th colspan=\'3\'>Sản phẩm</th>
                        <th colspan=\'6\'>Phí</th>
                        <th rowspan=\'2\'>Giảm giá</th>
                        <th rowspan=\'2\'>Tổng tiền thu hộ</th>
                        <th rowspan=\'2\'>Thanh Toán</th>
                        <th rowspan=\'2\'>Ghi chú</th>
                        <th rowspan=\'2\'>Lý do phát thất bại</th>
                        <th rowspan=\'2\'>Lý do phát chậm</th>
                        <th rowspan=\'2\'>Thời gian tạo</th>
                        <th rowspan=\'2\'>Thời gian XN Hoàn</th>
                    </tr>
                    <tr style=\'font-size: 14px; background: #6b94b3\'>
                        <td>Họ tên</td>
                        <td>Email</td>
                        <td>SDT</td>
                        <td>Tỉnh/Thành Phố</td>
                        <td>Quận/Huyện</td>
                        <td>Phường/Xã</td>
                        <td>Địa chỉ</td>
                        <td>Họ tên</td>
                        <td>Email</td>
                        <td>SDT</td>
                        <td>Tỉnh/Thành Phố</td>
                        <td>Quận/Huyện</td>
                        <td>Phường/Xã</td>
                        <td>Địa chỉ</td>
                        <td>Tên</td>
                        <td>Giá trị</td>
                        <td>K Lượng</td>
                        <td>Phí VC</td>
                        <td>Phí VK</td>
                        <td>Phí CoD</td>
                        <td>Phí BH</td>
                        <td>Phí CH</td>
                    </tr>
                </thead>
                <tbody>
        ';

        $i = 1;
        foreach($Data as $val){
            $Payment    = isset($UserInfo[(int)$val['from_user_id']]) ? $UserInfo[(int)$val['from_user_id']]['priority_payment'] : 2;
            $html   .=
                '<tr style=\'font-size: 12px\'>
                    <td>'.$i++.'</td>
                    <td>'.($val['time_accept'] > 0 ? date("d/m/y H:m",$val['time_accept']) : '').'</td>
                    <td>'.($val['time_pickup'] > 0 ? date("d/m/y H:m",$val['time_pickup']) : '').'</td>
                    <td>'.($val['time_success'] > 0 ? date("d/m/y H:m",$val['time_success']) : '').'</td>
                    <td>'.$val['tracking_code'].'</td>
                    <td>'.$val['verify_id'].'</td>
                    <td>'.$val['order_code'].'</td>
                    <td>'.(isset($Courier[(int)$val['courier_id']]) ? $Courier[(int)$val['courier_id']]['name'] : 'HVC').'</td>
                    <td>'.$val['courier_tracking_code'].'</td>
                    <td>'.(isset($Service[(int)$val['service_id']]) ? $Service[(int)$val['service_id']]['name'] : 'DV').'</td>
                    <td>'.(isset($Status[(int)$val['status']]) ? $Status[(int)$val['status']] : 'Trạng thái').'</td>

                     <td>'.(isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['fullname'] : '').'</td>
                     <td>'.(isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['email'] : '').'</td>
                     <td>'.(isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['phone'] : '').'</td>
                     <td>'.(isset($City[(int)$val['from_city_id']]) ? $City[(int)$val['from_city_id']] : '').'</td>
                     <td>'.(isset($District[(int)$val['from_district_id']]) ? $District[(int)$val['from_district_id']] : '').'</td>
                     <td>'.(isset($Ward[(int)$val['from_ward_id']]) ? $Ward[(int)$val['from_ward_id']] : '').'</td>
                     <td>'.$val['from_address'].'</td>

                     <td>'.$val['to_name'].'</td>
                     <td>'.$val['to_email'].'</td>
                     <td>'.$val['to_phone'].'</td>
                     <td>'.((isset($Address[(int)$val['to_address_id']]) && isset($City[$Address[(int)$val['to_address_id']]['city_id']])) ? $City[$Address[(int)$val['to_address_id']]['city_id']] : '').'</td>
                     <td>'.((isset($Address[(int)$val['to_address_id']]) && isset($District[$Address[(int)$val['to_address_id']]['province_id']])) ? $District[$Address[(int)$val['to_address_id']]['province_id']] : '').'</td>
                     <td>'.((isset($Address[(int)$val['to_address_id']]) && isset($Ward[$Address[(int)$val['to_address_id']]['ward_id']])) ? $Ward[$Address[(int)$val['to_address_id']]['ward_id']] : '').'</td>
                     <td>'.(isset($Address[(int)$val['to_address_id']]) ? $Address[(int)$val['to_address_id']]['address'] : '').'</td>

                     <td>'.$val['product_name'].'</td>
                     <td>'.(isset($val['total_amount']) ? number_format($val['total_amount']) : '').'</td>
                     <td>'.(isset($val['total_weight']) ? number_format($val['total_weight']) : '').'</td>

                    <td>'.number_format($val['order_detail']['sc_pvc']).'</td>
                    <td>'.number_format($val['order_detail']['sc_pvk']).'</td>
                    <td>'.(($val['status'] != 66) ? number_format($val['order_detail']['sc_cod']) : '').'</td>
                    <td>'.(($val['status'] != 66) ? number_format($val['order_detail']['sc_pbh']) : 0).'</td>
                    <td>'.(($val['status'] == 66) ? number_format($val['order_detail']['sc_pch']) : 0).'</td>
                    <td>'.(number_format(($val['order_detail']['sc_discount_pvc'] + (($val['status'] != 66) ? $val['order_detail']['sc_discount_cod'] : 0)))).'</td>

                    <td>'.(($val['status'] != 66) ? number_format($val['order_detail']['money_collect']) : 0).'</td>

                    <td>'.(($Payment == 1 ) ? 'Vimo' : 'Ngân Lượng').'</td>

                    <td>'.(isset($StatusProcess[(int)$val['id']]) ? $StatusProcess[(int)$val['id']] : '').'</td>
                    <td>'.(isset($ListReturn[(int)$val['id']]) ? $ListReturn[(int)$val['id']] : '').'</td>
                    <td>'.(isset($ListDeliverySlow[(int)$val['id']]) ? $ListDeliverySlow[(int)$val['id']] : '').'</td>
                    <td>'.(isset($User[(int)$val['from_user_id']]) ? date("d/m/y H:m",$User[(int)$val['from_user_id']]['time_create']) : '').'</td>
                    <td>'.(isset($TimeAcceptReturn[(int)$val['id']]) ? date("d/m/y H:m",$TimeAcceptReturn[(int)$val['id']]) : '').'</td>
                </tr>';
        }

        $html   .= '</tbody></table>';

        return  Response::json([
            'error'         => false,
            'code'          => 'success',
            'error_message' => 'Thành công',
            'html'          => $html
        ]);
    }

    public function getOrderstatus($OrderId){
        $Status          = Input::has('status')             ? (int)Input::get('status')                : 0;

        $OrderProcessModel  = new StatusModel;
        $Data               = [];

        $OrderProcessModel  = $OrderProcessModel::where('order_id', (int)$OrderId);
        if($Status > 0){
            $OrderProcessModel  = $OrderProcessModel->where('status', $Status);
        }

        if($OrderId > 0){
            $Data = $OrderProcessModel->orderBy('time_create','DESC')->get()->toArray();
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        ]);

    }

    public function getStatusaccept($group){
        $CourierStatusAcceptModel   = new CourierStatusAcceptModel;
        $ListStatus                 = [];
        $Data   = $CourierStatusAcceptModel::where('status_id',$group)->where('active',1)->orderBy('status_accept_id','ASC')->get()->toArray();
        if(!empty($Data)){
            foreach($Data as $val){
                $ListStatus[]   = (int)$val['status_accept_id'];
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'data'          => $ListStatus
        ]);
    }

    public function getReferticket(){
        $TrackingCode   = Input::has('tracking_code')   ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $Refer          = [];

        if(!empty($TrackingCode)){
            $ReferModel = new ReferModel;
            $Data       = $ReferModel::where('type',1)->where('code',$TrackingCode)->orderBy('id','DESC')->get()->toArray();
            if(!empty($Data)){
                foreach($Data as $val){
                    $Refer[]   = (int)$val['ticket_id'];
                }
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'data'          => $Refer
        ]);
    }

    public function getPostman(){
        $PostmanId = Input::has('postman')   ? strtoupper(trim(Input::get('postman'))) : '';
        $Data      = [];

        if(!empty($PostmanId)){ 
            $PostManModel = new \PostManModel;
            $Data       = $PostManModel::where('postman_id',$PostmanId)->first();
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        ]);
    }

    public function postChangetag(){
        $OrderId            = Input::has('order_id')            ? (int)Input::get('order_id')               : 0;
        $Tag                = Input::has('tag')                 ? strtolower(trim(Input::get('tag')))   : '';
        $OrdersModel        = new OrdersModel;
        $UserInfo           = $this->UserInfo();

        if(!empty($OrderId)){
            try{
                $OrdersModel::where('time_accept','>=',$this->time() - $this->time_limit)->where('id',$OrderId)->update(['tag' => $Tag]);
            }catch (Exception $e){
                return Response::json([
                    'error'         => true,
                    'message'       => 'UPDATE_FAIL',
                    'data'          => ''
                ]);
            }

            $this->data_log = [
                'order_id'      => $OrderId,
                'time_create'   => $this->time(),
                'user_id'       => $UserInfo['id'],
                'tag'           => [
                    'type'  => 'tag',
                    'new'   => $Tag
                ]
            ];
            $this->insertLog();
            return Response::json([
                'error'         => false,
                'message'       => 'SUCCESS',
                'data'          => ''
            ]);
        }else{
            return Response::json([
                'error'         => true,
                'message'       => 'EMPTY',
                'data'          => ''
            ]);
        }
    }

    private function insertLog(){
        \LMongo::collection('log_change_order')->insert($this->data_log);
        try{

        }catch(Exception $e){
            return ['error' => true, 'message'  => 'INSERT_LOG_FAIL', 'data' => $e->getMessage()];
        }
        return ['error' => false];
    }


    /*
     * Thống kê
     */
    public function getStatistic() {
        $UserInfo       = $this->UserInfo();
        $ListStatus     = Input::has('list_status')     ? trim(Input::get('list_status'))   : '';
        $TimeNow        = strtotime(date("Y-m-d"));

        $Model          = new ElasticBuilder('bxm_orders', 'orders');
        //$Model->where('time_create', 'gte', $this->time() - 86400*90);
        
        if($UserInfo['group'] == 16){ // Chơ dien tu
            $Model  = $Model->where('domain','chodientu.vn');
        }

        if(isset($UserInfo['domain']) && !empty($UserInfo['domain'])){
            $Model  = $Model->where('domain', $UserInfo['domain']);
        }

        $Data           = [
            'group'     => [],
            'day'       => [],
            'problem'   => [],
            'courier'   => []
        ];

        if(!empty($ListStatus)){
            $ListStatus     = explode(',',$ListStatus);
            $Model          = $Model->whereIn('status', $ListStatus);
        }elseif($UserInfo['privilege'] != 2){
            return Response::json([
                'error'         => false,
                'message'       => 'success',
                'data'          => $Data
            ]);
        }

        // thống kê tất cả đơn
        $ModelA         = clone $Model;
        $DataGroup      = $Model->where('time_create','gte', strtotime(date('Y-m-1 00:00:00')))
            ->groupBy('status')
            ->get();
        if(!empty($DataGroup)){
            $Group  = [];
            foreach($DataGroup['status'] as $key => $val){
                $Group[$val['key']]  = (int)$val['doc_count'];
            }
            $Data['group']  = $Group;
        }


        // thống kê đơn trong ngày
        $DataGroup      = $ModelA->where('time_create','gte',$TimeNow - $this->time_limit)->where('time_update','gte', $TimeNow)
            ->groupBy('status')
            ->get();
        if(!empty($DataGroup)){
            $Group  = [];
            foreach($DataGroup['status'] as $val){
                $Group[$val['key']]  = (int)$val['doc_count'];
            }
            $Data['day']  = $Group;
        }

        // thống kê lỗi lấy chậm hoặc giao chậm
        $Model          = new OrdersModel;
        if($UserInfo['group']   = 1){ // lấy hàng


        }elseif($UserInfo['group']   = 2){ // giao hàng

        }


        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        ]);
    }

    // Sale
    public function getStatisticSale(){
        $Model              = new SellerModel;
        $ModelLastMonth     = new SellerModel;

        $UserInfo       = $this->UserInfo();

        if(in_array(date('d'), [25,26])){
            $TimeStart      = strtotime(date('Y-m-25 00:00:00', strtotime("first day of last month")));
            $TimeEnd        = strtotime(date('Y-m-25 00:00:00'));
            $TimePreMonth   = strtotime(date('Y-m-25 00:00:00', strtotime("-2 month")));
        }else{
            if(date('d') < 25){
                $TimeStart      = strtotime(date('Y-m-25 00:00:00', strtotime("first day of last month")));
                $TimeEnd        = strtotime(date('Y-m-25 00:00:00'));
                $TimePreMonth   = strtotime(date('Y-m-25 00:00:00', strtotime("-2 month")));
            }else{
                $TimeStart      = strtotime(date('Y-m-25 00:00:00'));
                $TimeEnd        = strtotime(date('Y-m-25 00:00:00', strtotime("+1 month")));
                $TimePreMonth   = strtotime(date('Y-'.(date('m') - 1).'-25 00:00:00', strtotime("first day of last month")));
            }
        }

        $Data           = [
            'total_firstmonth'  => 0,
            'total_nextmonth'   => 0
        ];

        $ListUserId     = [];
        $SumTotal       = 0;
        $First          = [];
        $Pre            = [];

        if($UserInfo['group']   == 10){ // Trưởng nhóm sale
            // Doanh thu đầu tháng
            $DataFirst      = $Model::where('seller_id','>',0)->where('active',1)
                                ->where(function($query) use($TimeStart, $TimeEnd){
                                    $query->where(function($q) use($TimeStart, $TimeEnd){
                                            $q->where('first_time_incomings',0)
                                            ->where('first_time_pickup', '>=', $TimeStart)
                                            ->where('first_time_pickup', '<', $TimeEnd);
                                    })->orWhere(function($q) use($TimeStart, $TimeEnd){
                                        $q->where('first_time_incomings', '>=', $TimeStart)
                                            ->where('first_time_incomings', '<', $TimeEnd);
                                    });
                                })->groupBy('seller_id')
                                ->get(['seller_id',DB::raw('sum(total_firstmonth) as total_firstmonth')])->toArray();

            if(!empty($DataFirst)){
                foreach($DataFirst as $val){
                    $val['total_firstmonth']        = ceil($val['total_firstmonth']*10/11);
                    $SumTotal                       += $val['total_firstmonth'];
                    $ListUserId[]                   = (int)$val['seller_id'];
                    $First[(int)$val['seller_id']]  = $val['total_firstmonth'];
                }
            }

            // Doanh thu lũy kế khác hàng đang sử dụng
            $DataPre     = $ModelLastMonth::where('seller_id','>',0)->where('active',1)
                                        ->where(function($query) use($TimeStart, $TimePreMonth){
                                            $query->where(function($q) use($TimeStart, $TimePreMonth){
                                                $q->where('first_time_incomings',0)
                                                    ->where('first_time_pickup', '>=', $TimePreMonth)
                                                    ->where('first_time_pickup', '<', $TimeStart);
                                            })->orWhere(function($q) use($TimeStart, $TimePreMonth){
                                                $q->where('first_time_incomings', '>=', $TimePreMonth)
                                                    ->where('first_time_incomings', '<', $TimeStart);
                                            });
                                        })->groupBy('seller_id')
                                        ->get(['seller_id',DB::raw('sum(total_nextmonth) as total_nextmonth')])->toArray();

            if(!empty($DataPre)){
                foreach($DataPre as $val){
                    $val['total_nextmonth']         = ceil($val['total_nextmonth']*10/11);
                    $SumTotal                      += $val['total_nextmonth'];
                    $ListUserId[]                   = (int)$val['seller_id'];
                    $Pre[(int)$val['seller_id']]    = $val['total_nextmonth'];
                }
            }

            // Doanh thu lũy kế khác hàng ngừng sử dụng
            $LogSellerModel = new LogSellerModel;
            $DataPreStop     = $LogSellerModel::where(function($query) use($TimeStart, $TimePreMonth){
                                                    $query->where(function($q) use($TimeStart, $TimePreMonth){
                                                          $q->where('first_time_incomings',0)
                                                            ->where('first_time_pickup', '>=', $TimePreMonth)
                                                            ->where('first_time_pickup', '<', $TimeStart);
                                                    })->orWhere(function($q) use($TimeStart, $TimePreMonth){
                                                        $q->where('first_time_incomings', '>=', $TimePreMonth)
                                                          ->where('first_time_incomings', '<', $TimeStart);
                                                    });
                                                })->where('seller_id','>',0)
                                                 ->where('active',1)
                                                 ->groupBy('seller_id')
                                                 ->get(['seller_id',DB::raw('sum(total_nextmonth) as total_nextmonth')])->toArray();
            if(!empty($DataPreStop)){
                foreach($DataPreStop as $val){
                    $val['total_nextmonth'] = ceil($val['total_nextmonth']*10/11);
                    if(!isset($Pre[(int)$val['seller_id']])){
                        $Pre[(int)$val['seller_id']]  = 0;
                    }

                    $SumTotal                      += $val['total_nextmonth'];
                    $ListUserId[]                   = (int)$val['seller_id'];
                    $Pre[(int)$val['seller_id']]   += $val['total_nextmonth'];
                }
            }


            $Data['total']  = $SumTotal; // Tổng doanh thu
            $Key            = ceil($SumTotal/125000000) < 5 ? ceil($SumTotal/125000000) : 5;
            $Data['money']  = $this->range_lead[$Key]['hard'] + $this->range_lead[$Key]['commission']*$SumTotal;

        }else{ // Sale
            // Doanh thu đầu tháng
            $DataSum        = $Model::where(function($query) use($TimeStart, $TimeEnd){
                                        $query->where(function($q) use($TimeStart, $TimeEnd){
                                            $q->where('first_time_incomings',0)
                                                ->where('first_time_pickup', '>=', $TimeStart)
                                                ->where('first_time_pickup', '<', $TimeEnd);
                                        })->orWhere(function($q) use($TimeStart, $TimeEnd){
                                            $q->where('first_time_incomings', '>=', $TimeStart)
                                                ->where('first_time_incomings', '<', $TimeEnd);
                                        });
                                    })->where('seller_id',(int)$UserInfo['id'])
                                    ->where('active',1)
                                    ->first([DB::raw('sum(total_firstmonth) as total_firstmonth')]);

            if(isset($DataSum->total_firstmonth)){
                $DataSum->total_firstmonth      = ceil($DataSum->total_firstmonth*10/11);
                $SumTotal                       = $DataSum->total_firstmonth;
                $Data['total_firstmonth']       = $DataSum->total_firstmonth;
            }

            // Doanh thu lũy kế
            $DataSum     = $ModelLastMonth::where(function($query) use($TimeStart, $TimePreMonth){
                                                $query->where(function($q) use($TimeStart, $TimePreMonth){
                                                    $q->where('first_time_incomings',0)
                                                        ->where('first_time_pickup', '>=', $TimePreMonth)
                                                        ->where('first_time_pickup', '<', $TimeStart);
                                                })->orWhere(function($q) use($TimeStart, $TimePreMonth){
                                                    $q->where('first_time_incomings', '>=', $TimePreMonth)
                                                        ->where('first_time_incomings', '<', $TimeStart);
                                                });
                                            })->where('active',1)
                                            ->where('seller_id',(int)$UserInfo['id'])
                                            ->first([DB::raw('sum(total_nextmonth) as total_nextmonth')]);

            if(isset($DataSum->total_nextmonth)){
                $DataSum->total_nextmonth       = ceil($DataSum->total_nextmonth*10/11);
                $SumTotal                      += $DataSum->total_nextmonth;
                $Data['total_nextmonth']        = $DataSum->total_nextmonth;
            }

            // Doanh thu lũy kế khác hàng ngừng sử dụng
            $LogSellerModel = new LogSellerModel;
            $DataPreStop     = $LogSellerModel::where(function($query) use($TimeStart, $TimePreMonth){
                                                    $query->where(function($q) use($TimeStart, $TimePreMonth){
                                                        $q->where('first_time_incomings',0)
                                                            ->where('first_time_pickup', '>=', $TimePreMonth)
                                                            ->where('first_time_pickup', '<', $TimeStart);
                                                    })->orWhere(function($q) use($TimeStart, $TimePreMonth){
                                                        $q->where('first_time_incomings', '>=', $TimePreMonth)
                                                            ->where('first_time_incomings', '<', $TimeStart);
                                                    });
                                                })->where('seller_id',(int)$UserInfo['id'])
                                                  ->where('active',1)
                                                  ->first([DB::raw('sum(total_nextmonth) as total_nextmonth')]);
            if(isset($DataPreStop->total_nextmonth)){
                    $val['total_nextmonth']         = ceil($DataPreStop->total_nextmonth*10/11);
                    $SumTotal                      += $DataPreStop->total_nextmonth;
                    $Data['total_nextmonth']       += $DataPreStop->total_nextmonth;
            }

            // Khách hàng quay lại

            $Data['total']  = $SumTotal; // Tổng doanh thu
            $Key            = ceil($SumTotal/25000000) < 5 ? ceil($SumTotal/25000000) : 5;
            if($Key == 0) $Key = 1;
            $Data['money']  = $this->range_sale[$Key]['hard'] + $this->range_sale[$Key]['commission']*$SumTotal;
        }

        if(!empty($ListUserId)){
            $ListUserId = array_unique($ListUserId);
            $UserModel = new \User;
            $ListUser   = $UserModel->whereIn('id',$ListUserId)->get(['id','fullname', 'phone', 'email', 'time_create'])->toArray();
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data,
            'list'          => ['first' => $First, 'pre' => $Pre],
            'user'          => isset($ListUser) ? $ListUser : []
        ]);
    }

    public function getEventDashboard(){
        $OrdersModel    = new OrdersModel;
        $User           = User::where('time_create','>=',1458406800)->where('time_create','<',1460480400)
                          ->groupBy('group_tc')
                          ->get(array(DB::raw('DATE_FORMAT( FROM_UNIXTIME(  `time_create` ) ,  \'%e\' ) AS  `group_tc`,count(*) as count')))->toArray();

        $ListOrder  = OrdersModel::where('time_create','>=',1458406800)->where('time_create','<',1460480400)->where(function($query){
            $query->where('time_accept','>=',1458406800)->orWhere('time_accept',0);
        })->get(['id','from_user_id','status','time_pickup','post_office_id','time_create'])->toArray();

        $UserOrder      = [];
        $Order          = [];
        $OrderSuccess   = [];
        $OrderFail      = [];
        $ListUser       = [];

            //date("d",$StartTime)
        if(!empty($ListOrder)){
            foreach($ListOrder as $val){
                $Date               = (int)date("d",$val['time_create']);
                /** khách hàng tạo đơn */
                if(!isset($UserOrder[$Date])){
                    $UserOrder[$Date]   = [
                        'group_tc'  =>  $Date,
                        'count'     => 0
                    ];
                }

                if(!in_array((int)$val['from_user_id'], $ListUser)){
                    $UserOrder[$Date]['count']   += 1;
                    $ListUser[] = (int)$val['from_user_id'];
                }

                if($val['post_office_id'] > 0){
                    /** order tạo mang ra bưu cục */
                    if(!isset($Order[$Date])){
                        $Order[$Date]   = [
                            'group_tc'  =>  $Date,
                            'count'     => 0
                        ];
                    }

                    $Order[$Date]['count']  += 1;

                    /** order mang ra bưu cục thành công */
                    if($val['time_pickup'] > 0 && !in_array((int)$val['status'], [31,32,33,34])){
                        if(!isset($OrderSuccess[$Date])){
                            $OrderSuccess[$Date]   = [
                                'group_tc'  =>  $Date,
                                'count'     => 0
                            ];
                        }

                        $OrderSuccess[$Date]['count']  += 1;
                    }

                    /** order mang ra bưu cục thất bại */
                    if(in_array((int)$val['status'], [31,32,33,34])){
                        if(!isset($OrderFail[$Date])){
                            $OrderFail[$Date]   = [
                                'group_tc'  =>  $Date,
                                'count'     => 0
                            ];
                        }

                        $OrderFail[$Date]['count']  += 1;
                    }
                }
            }
        }

        return Response::json([
            'error'             => false,
            'message'           => 'success',
            'user'              => $User,
            'user_order'        => $UserOrder,
            'order'             => $Order,
            'order_success'     => $OrderSuccess,
            'order_fail'        => $OrderFail
        ]);
    }

    /*
     * Suggest Courier
     */
    public function getSuggestCourier(){
        $TrackingCode           = Input::has('tracking_code')        ? Input::get('tracking_code')        : '';

        if(empty($TrackingCode)){
            return Response::json([
                'error'             => false,
                'message'           => 'success',
                'data'              => [],
            ]);
        }

        $Order = \ordermodel\OrdersModel::where(function($query){
                                            $query->where('time_accept','>=', $this->time() - 86400*60)
                                              ->orWhere('time_accept',0);
                                        })->where('tracking_code',$TrackingCode)->with(['ToOrderAddress'])
                                        ->first(['id','tracking_code','service_id','to_district_id','to_address_id','from_city_id',
                                                'from_district_id','from_ward_id','total_amount','from_country_id','to_country_id']);


        if(!isset($Order->id)){
            return Response::json([
                'error'             => true,
                'message'           => 'Không tìm thấy vận đơn',
                'data'              => [],
            ]);
        }

        if((int)$Order->from_country_id == 237 && (int)$Order->to_country_id == 237){
            Input::merge([
                'To' => [
                    'City'       => (int)$Order->to_order_address->city_id,
                    'Province'   => (int)$Order->to_order_address->province_id,
                    'Ward'       => (int)$Order->to_order_address->ward_id,
                    'Address'    => (int)$Order->to_order_address->address,
                ],
                'From'  => [
                    'City'          => (int)$Order->from_city_id,
                    'Province'      => (int)$Order->from_district_id,
                    'Ward'          => (int)$Order->from_ward_id
                ],
                'Config'    => [
                    'Service'   => (int)$Order->service_id
                ],
                'Order'     => [
                    'Amount'    => (int)$Order->total_amount
                ]
            ]);

            $ApiCourierCtrl = new \ApiCourierCtrl;
            $ListCourier    = $ApiCourierCtrl->SuggestCourier();
        }else{
            Input::merge([
                'To' => [
                    'Country'    => (int)$Order->to_country_id,
                    'City'       => (int)$Order->to_order_address->city_id,
                    'Province'   => (int)$Order->to_order_address->province_id,
                    'Ward'       => (int)$Order->to_order_address->ward_id,
                    'Address'    => (int)$Order->to_order_address->address,
                ],
                'From'  => [
                    'Country'       => (int)$Order->from_country_id,
                    'City'          => (int)$Order->from_city_id,
                    'Province'      => (int)$Order->from_district_id,
                    'Ward'          => (int)$Order->from_ward_id
                ],
                'Config'    => [
                    'Service'   => (int)$Order->service_id
                ],
                'Order'     => [
                    'Amount'    => (int)$Order->total_amount
                ]
            ]);

            $ApiCourierCtrl = new \ApiGlobalCtrl;
            $ListCourier    = $ApiCourierCtrl->SuggestCourier();
        }


        if(!$ListCourier){
            return Response::json([
                'error'             => true,
                'message'           => $ApiCourierCtrl->message,
                'data'              => Input::all(),
            ]);
        }

        return Response::json([
            'error'             => false,
            'message'           => $ApiCourierCtrl->message,
            'data'              => $ListCourier,
        ]);

    }


    /**
     * Change Courier
     */
    private function UpdateOrder($TrackingCode, $DataUpdate){
        try{
            \ordermodel\OrdersModel::where(function($query){
                $query->where('time_accept','>=', $this->time() - 86400*60)
                    ->orWhere('time_accept',0);
            })->where('tracking_code',$TrackingCode)
              ->update($DataUpdate);
        }catch (\Exception $e){
            return ['error' => true, 'message' => $e->getMessage()];
        }

        return ['error' => false, 'message' => 'Thành công'];
    }

    public function postChangeCourier(){
        $TrackingCode       = Input::has('tracking_code')   ? Input::get('tracking_code')       : '';
        $Courier            = Input::has('courier_id')      ? (int)Input::get('courier_id')     : '';
        $Note               = Input::has('note')            ? Input::get('note')                : '';

        if(empty($TrackingCode) || empty($Courier)){
            return Response::json([
                'error'             => false,
                'message'           => 'success',
                'data'              => [],
            ]);
        }

        $Order = \ordermodel\OrdersModel::where(function($query){
            $query->where('time_accept','>=', $this->time() - 86400*60)
                ->orWhere('time_accept',0);
        })->where('tracking_code',$TrackingCode)->with(['ToOrderAddress'])
            ->first(['id','tracking_code','status','verify_id','time_pickup','courier_id','from_country_id','to_country_id']);


        if(!isset($Order->id)){
            return Response::json([
                'error'             => true,
                'message'           => 'Không tìm thấy vận đơn',
                'data'              => [],
            ]);
        }

        if($Order->time_pickup > 0){
            return Response::json([
                'error'             => true,
                'message'           => 'Đơn hàng đã được lấy hàng, không thể thay đổi hãng vận chuyển',
                'data'              => [],
            ]);
        }

        if($Order->verify_id > 0){
            return Response::json([
                'error'             => true,
                'message'           => 'Đơn hàng đã được đối soát, không thể thay đổi hãng vận chuyển',
                'data'              => [],
            ]);
        }

        $UserInfo   = $this->UserInfo();
        $LastStatus = $Order->status;

        $this->data_log['order_id']     = $Order->id;
        $this->data_log['time_create']  = $this->time();
        $this->data_log['user_id']      = $UserInfo['id'];
        $this->data_log['courier_id']   = [
            'type'          => 'courier_id',
            'new'           => $Courier,
            'old'           => $Order->courier_id,
            'note'          => $Note
        ];


        // Xử lý báo hủy hvc
        if($Order->status >= 22 && in_array($Order->courier_id,[1,8]) && ($Order->from_country_id == 237) && ($Order->to_country_id == 237)){
            // đã duyệt sang hvc và ko bị hủy
            $Update = $this->UpdateOrder($Order->tracking_code, ['status' => 28]);
            if($Update['error']){
                $this->data_log['message']  =   $Update['message'];
                \LMongo::collection('log_change_order')->insert($this->data_log);
                return Response::json($Update);
            }

            $CourierAcceptLadingCtrl    = new \trigger\CourierAcceptLadingCtrl;
            $ReportCancel               = $CourierAcceptLadingCtrl->getReportCancel(false);
            if($ReportCancel['ERROR'] != 'SUCCESS'){
                $Update = $this->UpdateOrder($Order->tracking_code, ['status' => $LastStatus]);
                if($Update['error']){
                    $this->data_log['message']  =   $Update['message'];
                    \LMongo::collection('log_change_order')->insert($this->data_log);
                    return Response::json($Update);
                }

                $this->data_log['message']  =   $ReportCancel['ERROR'];
                \LMongo::collection('log_change_order')->insert($this->data_log);
                return Response::json([
                    'error'             => true,
                    'message'           => $ReportCancel['ERROR']
                ]);
            }
        }


        $DataUpdate = ['courier_id' => $Courier];
        if($LastStatus > 20){
            $DataUpdate['status'] = 21;
        }

        $Update = $this->UpdateOrder($Order->tracking_code, $DataUpdate);
        if($Update['error']){
            $this->data_log['message']  =   $Update['message'];
            \LMongo::collection('log_change_order')->insert($this->data_log);
            return Response::json($Update);
        }

        $this->data_log['message']  =   'Thành công';
        \LMongo::collection('log_change_order')->insert($this->data_log);
        return Response::json([
            'error'             => false,
            'message'           => 'Thành công'
        ]);

    }
}
