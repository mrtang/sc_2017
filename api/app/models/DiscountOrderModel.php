<?php

class DiscountOrderModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'discount_order' ;
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;
}
