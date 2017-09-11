<?php

class DiscountTypeModel extends Eloquent {
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'discount_type' ;
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;
    
}
