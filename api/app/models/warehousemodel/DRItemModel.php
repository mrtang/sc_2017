<?php namespace warehousemodel;


class DRItemModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'warehouse_deliveryreceiptitem' ;
    protected $connection       = 'warehousebm';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_dr(){
		return $this->belongsTo('warehousemodel\DRModel','delivery_receipt', 'id');
	}

	public function __get_dr_product(){
		return $this->hasMany('warehousemodel\DRProductModel','delivery_receipt_item_id')->select('id','shipment','uid','delivery_receipt_item_id','sku');
	}
}
