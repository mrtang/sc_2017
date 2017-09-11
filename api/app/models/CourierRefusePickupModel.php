<?php

class CourierRefusePickupModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'courier_refuse_pickup' ;
    protected $connection       = 'courierdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
