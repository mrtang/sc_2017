<?php namespace oms;

class BaseCtrl extends \BaseCtrl{

    public $time_limit  = 8035200;  // 93 ngày

    function __construct(){

    }

    public function getListCity(){
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

        $ListUser   = \User::whereRaw("id in (". implode(",", $ListUserId) .")")->get(['id','fullname', 'phone', 'email'])->toArray();
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
}
