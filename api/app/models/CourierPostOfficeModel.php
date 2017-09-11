<?php

class CourierPostOfficeModel extends Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'post_office';
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;

    public function City(){
        return $this->belongsTo('CityModel','city_id');
    }
    
    public function district(){
        return $this->belongsTo('DistrictModel','district_id');
    }
    
    public function ward(){
        return $this->belongsTo('WardModel','ward_id');
    }
}
