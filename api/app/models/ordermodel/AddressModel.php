<?php namespace ordermodel;

use Eloquent;
class AddressModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'order_address' ;
    protected $connection       = 'orderdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public function City(){
        return $this->belongsTo('CityModel','city_id');
    }
    public function District(){
        return $this->belongsTo('DistrictModel','province_id');
    }
    public function Ward(){
        return $this->belongsTo('WardModel','ward_id');
    }
}
