<?php

class CourierRefuseModel extends Eloquent{
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'courier_refuse' ;
    protected $connection   = 'courierdb';
    public    $timestamps   = false;
    protected $guarded      = array();

    public function __get_ward(){
        return $this->belongsTo('WardModel','ward_id','id');
    }

    public function __get_district(){
        return $this->belongsTo('DistrictModel','district_id','id');
    }

    public function __get_city(){
        return $this->belongsTo('CityModel','province_id','id');
    }

    public function __get_courier(){
        return $this->belongsTo('CourierModel','courier_id','id');
    }
}
