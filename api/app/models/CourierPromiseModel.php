<?php

class CourierPromiseModel extends Eloquent{
    protected $table            = 'courier_promise_pickup';
    protected $connection       = 'courierdb';
    protected $guarded          = array();
    public    $timestamps       = false;
        
    public function promise()
    {
        return $this->hasMany('PromiseDeliveryModel','promise_id');
    }

}
