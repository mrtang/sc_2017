<?php namespace systemmodel;

class CourierPromiseModel extends \Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'courier_promise';
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;
}
