<?php

class PromisePickupModel extends Eloquent{
    
    protected $table            = 'courier_promise_pickup';
    protected $connection       = 'courierdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public function district(){
        return $this->belongsTo('DistrictModel','district_id');
    }
}
