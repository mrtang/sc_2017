<?php

class CourierStatusModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table        = 'courier_status';
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;
    
}
