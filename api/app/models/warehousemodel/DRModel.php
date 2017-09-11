<?php namespace warehousemodel;


class DRModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'warehouse_deliveryreceipt' ;
    protected $connection       = 'warehousebm';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_dr_item(){
		return $this->hasMany('warehousemodel\DRItemModel','delivery_receipt')->select('id','shipment','delivery_receipt','sku','quantity');
	}
}
