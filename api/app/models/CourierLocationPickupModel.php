<?php

class CourierLocationPickupModel extends Eloquent{
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'location_pickup' ;
    protected $connection   = 'courierdb';
    public    $timestamps   = false;
}
