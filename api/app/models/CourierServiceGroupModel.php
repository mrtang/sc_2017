<?php

class CourierServiceGroupModel extends Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table        = 'courier_service_group' ;
    protected $connection   = 'courierdb';
    public    $timestamps   = false;
}
