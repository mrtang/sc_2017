<?php

class LocationModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'location';
    protected $connection   = 'courierdb';
    public    $timestamps   = false;
    
}
