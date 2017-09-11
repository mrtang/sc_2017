<?php

class OrderDetailModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'order_detail';
    protected $connection   = 'orderdb';
    public    $timestamps   = false;
    protected $guarded      = array();
}
