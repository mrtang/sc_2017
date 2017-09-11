<?php namespace systemmodel;

class CourierPickupConfigModel extends \Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'courier_pickup_config';
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;

    public function __child_courier(){
        return $this->belongsTo('CourierModel','child_courier')->select(['id','prefix']);
    }
}
