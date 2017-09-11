<?php

class CityModel extends Eloquent{

    protected $table        = 'lc_city' ;
    protected $connection   = 'metadb';
    protected $fillable     = array('id', 'city_name', 'region', 'code');
    protected $guarded      = array();
    public    $timestamps   = false;

    public function get_city(){
        return self::orderBy('priority','ASC')->orderBy('city_name', 'DESC')->get()->toArray();
    }

    /**
     * Area Location 
     **/
    public function arealocation(){
        return $this->hasMany('AreaLocationModel','province_id');
    }
    
    /**
     * Promise
     **/
     
    public function promise_pickpup(){
        return $this->hasMany('PromisePickupModel', 'province_id');
    }
    
    public function promise_delivery(){
        return $this->hasMany('PromiseDeliveryModel', 'from_province');
    }
}
