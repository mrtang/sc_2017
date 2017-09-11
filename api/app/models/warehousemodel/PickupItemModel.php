<?php namespace warehousemodel;


class PickupItemModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'warehouse_pickupitem' ;
    protected $connection       = 'warehousebm';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_seller_product(){
		return $this->belongsTo('fulfillmentmodel\SellerProductItemModel','uid', 'serial_number')->select(['id','user_id', 'serial_number','seller_product','status']);
	}

	public function __get_product(){
		return $this->belongsTo('fulfillmentmodel\SellerProductModel','sku', 'sku')->select(['id','sku', 'category_name']);
	}

	public  function pickup_list(){
		return $this->belongsTo('warehousemodel\PickupListModel','pickup_code','pickup_code');
	}
}
