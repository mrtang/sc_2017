<?php namespace bm_accounting;

class BaseCtrl extends \BaseCtrl{

    public $time_limit  = 8035200;  // 93 ngày
    public $data        = [];
    public $total       = 0;


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

    public function getProvince($ListProvinceId){
        $Province      = [];
        $DistrictModel = new \DistrictModel;
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
        $WardModel = new \WardModel;
        $ListWard  =  $WardModel::whereIn('id',$ListWardId)->get(['id','ward_name'])->toArray();
        if(!empty($ListWard)){
            foreach($ListWard as $val){
                $Ward[$val['id']]   = $val['ward_name'];
            }
        }
        return $Ward;
    }

    public function getUser($ListUserId){
        $User       = [];

        $ListUser   = \metadatamodel\OrganizationUserModel::whereRaw("id in (". implode(",", $ListUserId) .")")->get(['id','fullname','email','phone'])->toArray();
        if(!empty($ListUser)){
            foreach($ListUser as $val){
                $User[$val['id']]   = $val;
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

    public function CacheMapStatus($courier_id){
        $Status    = [];
        if (Cache::has('courier_status_cache_'+$courier_id)){
            $Status    = Cache::get('courier_status_cache_'+$courier_id);
        }else{
            $listCache    = \CourierStatusModel::where('active',1)->where('courier_id',$courier_id)->get(['courier_status','sc_status','active']);
            if(!empty($listCache)){
                foreach($listCache as $val){
                    $Status[$val['courier_status']]   = (int)$val['sc_status'];
                }
                Cache::put('courier_status_cache_'+$courier_id, $Status,3600);
            }
        }
        return $Status;
    }

    public function __get_product($ListUId){
        if(empty($ListUId)) return [];

        $ListUId        = array_unique($ListUId);
        $Res            = [];
        $ListProduct    = \bm_ecommercemodel\SellerProductItemModel::whereRaw("serial_number in ('". implode("','", $ListUId) ."')")->get(['serial_number','seller_product'])->toArray();
        if(!empty($ListProduct)){
            $ListUId        = [];

            foreach($ListProduct as $val){
                $ListUId[$val['serial_number']] = $val['seller_product'];
                $Product[]                      = $val['seller_product'];
            }

            if(!empty($Product)){
                $ListProduct    = array_unique($Product);
                $Product        = \bm_ecommercemodel\SellerProductModel::whereRaw("id in (". implode(",", $ListProduct) .")")->get(['id','category_name','name','desc'])->toArray();
                if(!empty($Product)){
                    $ListProduct    = [];
                    foreach($Product as $val){
                        $ListProduct[$val['id']]    = $val;
                    }

                    foreach($ListUId as $key => $val){
                        if(isset($ListProduct[$val])){
                            $Res[$key]  = $ListProduct[$val];
                        }
                    }
                }
            }
        }

        return $Res;
    }

    public function __check_merchant_key(){

        $UserInfo   = $this->getMerchantKey(false);
        if(!isset($UserInfo->user_id)){
            $this->code             = 'ERROR';
            $this->message          = 'Merchant Key không tồn tại';
            return false;
        }

        $UserId   = \User::where('id',$UserInfo->user_id)->first(['organization']);
        if(!isset($UserId->organization) || empty($UserId->organization)){
            $this->code             = 'ERROR';
            $this->message          = 'Tài khoản không tồn tại';
            return false;
        }

        return $UserId->organization;
    }

    public function __convert_time($time){
        return date('Y-m-d H:i:s', $time);
    }

    public function __get_time_log_warehouse($Time){
        $TimeStamp  = strtotime($Time);
        $Date       = date('d', $TimeStamp);

        if($Date > 1 && $Date < 17){
            $First  = date('Y-m-1', $TimeStamp);
            $End    = date('Y-m-d', ($TimeStamp - 86400));
        }elseif($Date == 1){
            $First  = date('Y-m-16',$TimeStamp - 86400);
            $End    = date('Y-m-d', ($TimeStamp - 86400));
        }else{// từ ngày 16 đến
            $First  = date('Y-m-16',$TimeStamp);
            $End    = date('Y-m-d', ($TimeStamp - 86400));
        }

        return ['time_start'    => $First, 'time_end'   => $End];
    }
}
