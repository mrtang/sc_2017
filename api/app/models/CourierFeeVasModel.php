<?php

class CourierFeeVasModel extends Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'courier_vas_fee' ;
    protected $connection   = 'courierdb';
    public    $timestamps   = false;
    
}
