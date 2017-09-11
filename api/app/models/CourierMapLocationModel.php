<?php

class CourierMapLocationModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table        = 'courier_map_location';
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;
    
}
