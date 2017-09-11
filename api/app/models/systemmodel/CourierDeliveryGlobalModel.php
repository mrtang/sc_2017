<?php namespace systemmodel;

class CourierDeliveryGlobalModel extends \Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'courier_delivery_global';
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;
}
