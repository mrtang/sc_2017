<?php namespace warehousemodel;


class PackageModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'warehouse_package' ;
    protected $connection       = 'warehousebm';
    protected $guarded          = array();
    public    $timestamps       = false;
}
