<?php namespace systemmodel;

class CourierAddressModel extends \Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'courier_address';
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;
}
