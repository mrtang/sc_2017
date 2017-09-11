<?php

class AreaLocationModel extends Eloquent{

    protected $table        = 'area_province';
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;
    
    // get province
    public function province(){
        return $this->belongsTo('CityModel','province_id');
    }
    
    // get district
    public function district(){
        return $this->belongsTo('DistrictModel','district_id');
    }
    
    // get courier_area
    public function area(){
        return $this->belongsTo('CourierAreaModel','area_id');
    }
    
}
