<?php

class AreaLocationModelDev extends Eloquent{

    protected $table        = 'courier_area_province';
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;
}
