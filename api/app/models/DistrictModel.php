<?php

class DistrictModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table        = 'lc_district' ;
    protected $connection   = 'metadb';
    public    $timestamps   = false;

    public function get_district($city){
        return self::where('city_id',$city)->orderBy('district_name', 'DESC')->get()->toArray();
    }
}
