<?php namespace ordermodel;

class CourierVerifyModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'order_courier_verify' ;
    protected $connection       = 'orderdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
