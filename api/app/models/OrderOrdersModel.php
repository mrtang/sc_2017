<?php

class OrderOrdersModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'orders' ;
    protected $connection   = 'orderdb';
    public    $timestamps   = false;
    protected $guarded      = array();
}
