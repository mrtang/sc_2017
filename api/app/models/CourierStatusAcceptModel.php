<?php

class CourierStatusAcceptModel extends Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'courier_status_accept';
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;
}
