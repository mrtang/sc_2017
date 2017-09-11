<?php

class DiscountUseModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'discount_use' ;
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;
}
