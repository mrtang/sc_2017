<?php namespace global_api;
use CountryModel;
class BaseCtrl extends \BaseController{

    public function getCountry($json = true){
        $this->_json = $json;

        if(Cache::has('cache_country')){
            $Country        = Cache::get('cache_country');
        }else{

            $CountryModel   = new CountryModel;
            $Country        = $CountryModel::get()->toArray();
            Cache::put('cache_country', $Country, 1440);
        }

        return $this->_ResponseData($Country);
    }

    public function getCity($country_id){
        $searching  = Input::has('q') ? Input::get('q') : "";
        $City       = [];

        try {
            $CityModel   = new \CityGlobalModel;
            $City        = $CityModel::where('country_id', $country_id);

            if(!empty($searching)){
                $City = $City->where('city_name', 'LIKE', '%'.$searching.'%');
            }

            $City = $City->take(20)->get()->toArray();

        } catch (\Exception $e) {
            $this->_error           = true;
            $this->_error_message   = "Lỗi máy chủ, vui lòng thử lại sau";
            return $this->_ResponseData();
        }

        return $this->_ResponseData($City);
    }

    public function getDistrict($City = 18, $json = true){

        $CacheName      = 'cache_global_district_'.$City;

        if(Cache::has($CacheName)){
            $District   =  Cache::get($CacheName);
        }else{
            $DistrictModel   = new \DistrictModel;
            $District   = $DistrictModel->get_district($City);

            if(!empty($District)){
                Cache::put($CacheName, $District, 30);
            }
        }
        return $json ? Response::json(['error' => false,'data'  => $District]) : $District;
    }

}
