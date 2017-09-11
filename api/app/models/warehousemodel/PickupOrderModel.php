<?php namespace warehousemodel;


class PickupOrderModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'warehouse_pickuporder' ;
    protected $connection       = 'warehousebm';
    protected $guarded          = array();
    public    $timestamps       = false;
}
