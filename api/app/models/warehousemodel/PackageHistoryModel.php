<?php namespace warehousemodel;


class PackageHistoryModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'warehouse_packagehistory' ;
    protected $connection       = 'warehousebm';
    protected $guarded          = array();
    public    $timestamps       = false;
}
