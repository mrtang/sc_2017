<?php

class DevCourierFeeVasModel extends Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'courier_vas_fee_12_2015' ;
    protected $connection   = 'courierdb';
    public    $timestamps   = false;
    
}
