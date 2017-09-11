<?php

class DiscountSetupModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'discount_setup' ;
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;
}
