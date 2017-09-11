<?php

class CourierRouter extends Eloquent{
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'courier_router' ;
    protected $connection   = 'courierdb';
    public    $timestamps   = false;
}
