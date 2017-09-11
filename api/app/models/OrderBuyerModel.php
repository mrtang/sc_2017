<?php

class OrderBuyerModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'order_buyer';
    protected $connection   = 'orderdb';
    public    $timestamps   = false;
    protected $guarded      = array();
    
}

