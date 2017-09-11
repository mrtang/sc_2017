<?php

class CourierComissionModel extends Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table       = 'courier_comission' ;
    protected $connection  = 'courierdb';
    public    $timestamps  = false;
    
}
