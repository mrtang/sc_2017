<?php

class CourierTypeModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'courier_type' ;
    protected $connection   = 'courierdb';
    public    $timestamps   = false;
    
}
