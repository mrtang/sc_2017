<?php namespace warehousemodel;


class PutawayStatusModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'warehouse_putaway_status' ;
    protected $connection       = 'warehousebm';
    protected $guarded          = array();
    public    $timestamps       = false;
}
