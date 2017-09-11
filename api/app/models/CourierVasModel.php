<?php

class CourierVasModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table = 'courier_vas';
    protected $connection = 'courierdb';
    public    $timestamps = false;
    
}
