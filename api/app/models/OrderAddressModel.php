<?php

class OrderAddressModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'order_address';
    protected $connection   = 'orderdb';
    public    $timestamps   = false;
    protected $guarded      = array();
    
}

