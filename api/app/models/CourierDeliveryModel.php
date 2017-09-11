<?php

class CourierDeliveryModel extends Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'courier_delivery';
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;
}
