<?php

class OrderItemModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'order_item';
    protected $connection   = 'orderdb';
    public    $timestamps   = false;
    protected $guarded      = array();
    
}

