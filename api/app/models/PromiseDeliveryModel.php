<?php
class PromiseDeliveryModel extends Eloquent{
    protected $table            = 'courier_promise_delivery';
    protected $connection       = 'courierdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public function district(){
        return $this->belongsTo('DistrictModel','to_district');
    }
    
    public function city(){
        return $this->belongsTo('CityModel','to_province');
    }
}
