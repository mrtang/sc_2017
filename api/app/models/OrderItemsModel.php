<?php

class OrderItemsModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'items' ;
    protected $connection   = 'orderdb';
    public    $timestamps   = false;
    protected $guarded      = array();
}

