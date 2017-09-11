<?php

class WardModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'lc_ward' ;
    protected $connection   = 'metadb';
    public    $timestamps   = false;
    
    public function city(){
        return $this->belongsTo('CityModel','city_id');
    }
    
    public function district(){
        return $this->belongsTo('DistrictModel','district_id');
    }
}
