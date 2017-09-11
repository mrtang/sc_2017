<?php namespace accounting;

class BaseCtrl extends \BaseCtrl{
    public $list_status    = [
        'WAITING'                   => 'Chờ đối soát',
        'PROCESSING'                => 'Đang đối soát',
        'NOT_ACTIVE'                => 'Chưa đối soát',
        'CODE_NOT_EXISTS'           => 'Thiếu mã đơn hàng hoặc mã hvc',
        'ORDER_NOT_EXISTS'          => 'Đơn hàng không tồn tại',
        'COURIER_ERROR'             => 'Hãng vận chuyển không chính xác',
        'ORDER_DETAIL_NOT_EXISTS'   => 'Không có chi tiết đơn hàng',
        'MISMATCH'                  => 'Sai Lệch',
        'UPDATE_ORDER_VERIFY_FAIL'  => 'Cập nhật đối soát lỗi',
        'UPDATE_ORDER_FAIL'         => 'Cập nhật đơn hàng lỗi',
        'SUCCESS'                   => 'Thành công'
    ];

    public $time_limit  = 8035200;  // 93 ngày
    public function __time_range(){
        return $this->time() - 8035200;
    }

    public function __convert_time($time){
        return date('Y-m-d H:i:s', $time);
    }

    public $list_courier_service   = [
        1       => [
            'ECB'       => 1,
            'NDB'       => 1,
            'HGS'       => 1,
            'VGS'       => 1,
            'VSK'       => 1,
            'HVSK'      => 1,
            'VSC'       => 2,
            'HVSC'      => 2,
            'SDB'       => 2,
            'VVT'       => 5
        ],

        6       => [
            1   => 1,
            2   => 2
        ],
        8       => [
            2   => 2
        ],
        9       => [
            'CODTK'   =>    1,
            'CODN'    =>    2
        ]
    ];

    function __construct(){

    }

    public function getListCity(){
        $City   = [];
        if (Cache::has('list_city_cache')){
            $City    = Cache::get('list_city_cache');
        }else{
            $listCity           = \CityModel::all(array('id','city_name'));
            if(!$listCity->isEmpty()){
                foreach($listCity as $val){
                    $City[(int)$val['id']]   = $val['city_name'];
                }
                Cache::put('list_city_cache', $City, 1440);
            }
        }
        return $City;
    }

    public function getCityById($ListCityId){
        $City               = [];
        $CityGlobalModel    = new \CityGlobalModel;
        $ListCity           =  $CityGlobalModel::whereIn('id',$ListCityId)->get(['id','city_name'])->toArray();
        if(!empty($ListCity)){
            foreach($ListCity as $val){
                if(in_array($val['id'], [18,19,6,1,3,14,12,7,10,5,4,17,16,15,11,2,23,22,25,24,8,28,20,26,31,30,27,32,29,35,34,37,36,33])){
                    $val['city_name'] .= '(MB)';
                }else{
                    $val['city_name'] .= '(MN)';
                }
                $City[$val['id']]   = $val['city_name'];
            }
        }
        return $City;
    }

    public function getProvince($ListProvinceId){
        $Province      = [];
        $DistrictModel = new \DistrictModel;
        $ListProvince  =  $DistrictModel::whereIn('id', $ListProvinceId)->remember(60)->get(['id','district_name'])->toArray();
        if(!empty($ListProvince)){
            foreach($ListProvince as $val){
                $Province[$val['id']]   = $val['district_name'];
            }
        }
        return $Province;
    }

    public function getWard($ListWardId){
        $Ward      = [];
        $WardModel = new \WardModel;
        $ListWard  =  $WardModel::whereRaw("id in (". implode(",", $ListWardId) .")")->get(['id','ward_name'])->toArray();
        if(!empty($ListWard)){
            foreach($ListWard as $val){
                $Ward[$val['id']]   = $val['ward_name'];
            }
        }
        return $Ward;
    }

    public function getUser($ListUserId){
        $User       = [];

        $ListUser   = \User::whereRaw("id in (". implode(",", $ListUserId) .")")->get(['id','fullname', 'phone', 'email'])->toArray();
        if(!empty($ListUser)){
            foreach($ListUser as $val){
                $User[$val['id']]   = $val;
            }
        }
        return $User;
    }

    public function getUserLoyalty($ListUserId){
        $User       = [];

        $ListUser   = \loyaltymodel\UserModel::whereRaw("user_id in (". implode(",", $ListUserId) .")")->get(['id','user_id', 'level'])->toArray();
        if(!empty($ListUser)){
            foreach($ListUser as $val){
                $User[$val['user_id']]   = $val;
            }
        }
        return $User;
    }

    public function getUserInfo($ListUserId){
        $UserModel = new \sellermodel\UserInfoModel;
        $User       = [];

        $ListUser   = $UserModel::whereRaw("user_id in (". implode(",", $ListUserId) .")")->get(['id','user_id', 'user_nl_id', 'pipe_status', 'priority_payment'])->toArray();
        if(!empty($ListUser)){
            foreach($ListUser as $val){
                $User[$val['user_id']]   = $val;
            }
        }
        return $User;
    }
    
    public function __cache_courier_city($courier_id){
        $City    = [];
        $CacheName  = (string)'city_courier_cache_'.(int)$courier_id;
        $courier_id = (int)$courier_id;
        if (Cache::has($CacheName)){
            $City    = Cache::get($CacheName);
        }else{
            $listCache    = \CourierLocationModel::where('courier_id',$courier_id)->where('province_id',0)->where('ward_id',0)
                ->get(['city_id','courier_city_id'])->toArray();
            if(!empty($listCache)){
                foreach($listCache as $val){
                    $val['courier_city_id'] = strtoupper(trim($val['courier_city_id']));
                    $City[(int)$val['city_id']]   = $val['courier_city_id'];
                }
                Cache::put($CacheName, $City,3600);
            }
        }
        return $City;
    }

    public function __cache_courier_district($courier_id, $city){
        $District    = [];
        $CacheName  = (string)'city_courier_cache_'.(int)$courier_id.'_'.$city;
        $courier_id = (int)$courier_id;
        if (Cache::has($CacheName)){
            $District    = Cache::get($CacheName);
        }else{
            $listCache    = \CourierLocationModel::where('courier_id',$courier_id)->where('city_id', $city)->where('ward_id',0)
                ->where('province_id','>',0)
                ->get(['province_id','courier_province_id'])->toArray();
            if(!empty($listCache)){
                foreach($listCache as $val){
                    $val['courier_province_id'] = strtoupper(trim($val['courier_province_id']));
                    $District[(int)$val['province_id']]   = $val['courier_province_id'];
                }
                Cache::put($CacheName, $District,3600);
            }
        }
        return $District;
    }

    public function SendSmS($toPhone = '', $Content = ''){
        $toPhone = str_replace(array(';','.',' ','/','|'), ',', $toPhone);

        $arrPhone = array();
        if($toPhone != ''){
            $arrPhone = explode(',', $toPhone);
        }

        Input::Merge([
            'to_phone'   => $arrPhone[0],
            'content'    => $Content
        ]);

        $SmsController  = new \SmsController;
        $SmsController->postSendsms(false);
    }

    public function __get_service_vtp($Service, $Weight, $FromCity, $ToCity, $ToLocation){
        switch ((int)$Service) {
            case 1:
                if($FromCity == $ToCity){
                    if(in_array($FromCity, [18,52,35])){
                        $service_code   = 'PHS';
                    }else{
                        $service_code   = ($ToLocation > 1) ? 'VBK' : 'VBK';
                    }
                }else{
                    if($Weight < 2000){
                        if(in_array($ToCity,array(18,52,35))){
                            $service_code   = ($ToLocation > 2) ? 'VBD' : 'VBD';
                        }else{
                            $service_code   = ($ToLocation > 1) ? 'VBD' : 'VBD';
                        }
                    }else{
                        if(in_array($ToCity,array(18,52,35))){
                            $service_code   = ($ToLocation > 2) ? 'VBK' : 'VBK';
                        }else{
                            $service_code   = ($ToLocation > 1) ? 'VBK' : 'VBK';
                        }
                    }
                }
                break;
            case 2:
                if($FromCity == $ToCity){ // nội tỉnh
                    if(in_array($FromCity, [18,52,35])){
                        $service_code   = 'PTN';
                    }else{
                        $service_code   = ($ToLocation > 2) ? 'VCN' : 'VCN';
                    }
                }else{ // liên tỉnh
                    if(in_array($ToCity,array(18,52,35))){
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

        return $service_code;
    }


    public function __get_service_gtk($Service, $Weight, $FromCity, $ToCity, $ToLocation){
        if($ToLocation > 1){
            $service_code   = 3;
        }else{
            $service_code   = $Service;
        }
        return $service_code;
    }

    public function __get_service_gts($Service, $Weight, $FromCity, $ToCity, $ToLocation){
        return $Service == 2 ? 'CODN' : 'CODTK';
    }

    public function __get_service_njv($Service, $Weight, $FromCity, $ToCity, $ToLocation){
        return $Service == 2 ? 'SDB' : 'NDB';
    }

    public function CacheMapStatus($courier_id){
        $CacheName  = (string)'courier_status_cache_'.(int)$courier_id;
        $Status    = [];
        if (Cache::has($CacheName)){
            $Status    = Cache::get($CacheName);
        }else{
            $listCache    = \CourierStatusModel::where('active',1)->where('type', 1)->where('courier_id',$courier_id)->get(['courier_status','sc_status','type','active'])->toArray();
            if(!empty($listCache)){
                foreach($listCache as $val){
                    $Status[$val['courier_status']]   = (int)$val['sc_status'];
                }
                Cache::put($CacheName, $Status,3600);
            }
        }
        return $Status;
    }

    public function __to_address($ToAddress){
        return \ordermodel\AddressModel::where('id',$ToAddress)->first();
    }

    public function __get_area_location($Courier, $district_id, $from_city, $to_city){
        $AreaId     = \AreaLocationModelDev::where('courier_id', $Courier)->where('province_id',$district_id)->where('active','=',1);
        if($from_city == $to_city){ // nội tỉnh
            $AreaId  = $AreaId->where('type',1);
        }else{// liên tỉnh
            $AreaId  = $AreaId->where('type',2);
        }

        $AreaId = $AreaId->first();
        return isset($AreaId->location_id) ? $AreaId->location_id : 0;
    }

    public function __get_user_boxme($TimeStart){
        return \omsmodel\SellerModel::where('first_shipment_time', '>=', $TimeStart)
                                                   ->lists('user_id');
    }
}
