<?php namespace seller;

class BaseCtrl extends \BaseCtrl {
    public $time_limit  = 8035200;  // 93 ngày

    public function __construct(){

    }

    public  function getConfigTypeCourier($json = true){
        $Data   = [];

        if(Cache::has('cache_config_courier_type')){
            $Data   =  Cache::get('cache_config_courier_type');
        }else{
            $Model          = new \sellermodel\CourierTypeModel;
            $CourierType    = $Model->where('active',1)->get()->toArray();

            if(!empty($CourierType)){
                $Data = array_values(array_sort($CourierType, function($value){
                    return $value['priority'];
                }));

                Cache::put('cache_config_courier_type', $Data, 1440);
            }

        }
        return $json ? Response::json(['error' => false,'data'  => $Data]) : $Data;
    }
}
