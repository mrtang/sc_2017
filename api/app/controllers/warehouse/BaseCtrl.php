<?php namespace warehouse;

class BaseCtrl extends \BaseCtrl{

    public $time_limit  = 8035200;  // 93 ngày
    public $data        = [];
    public $total       = 0;
    public $code           = 'SUCCESS',$message = 'Thành công',$error    = false;


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

    public function __get_seller_inventory($code){
        return \bm_sellermodel\InventorySellerModel::where('code', $code)->remember(10)->lists('id');
    }

    public function __get_inventory($City = '', $District = ''){
        $ListId = new \sellermodel\UserInventoryModel;
        if(!empty($City)){
            $ListId = $ListId->where('city_id', (int)$City);
        }

        if(!empty($District)){
            $ListId = $ListId->where('province_id', (int)$District);
        }

        return $ListId->lists('id');
    }

    public function getCityById($ListCityId){
        $City               = [];
        $CityGlobalModel    = new \CityGlobalModel;
        $ListCity           =  $CityGlobalModel::whereIn('id',$ListCityId)->get(['id','city_name'])->toArray();
        if(!empty($ListCity)){
            foreach($ListCity as $val){
                $City[$val['id']]   = $val['city_name'];
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


}