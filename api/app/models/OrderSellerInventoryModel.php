<?php

class OrderSellerInventoryModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'seller_inventory';
    protected $connection   = 'orderdb';
    public    $timestamps   = false;
    protected $guarded      = array();
    
}
