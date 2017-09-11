<?php namespace warehousemodel;


class DRProductModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'warehouse_deliveryreceiptitemproduct' ;
    protected $connection       = 'warehousebm';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_dr_item(){
		return $this->belongsTo('warehousemodel\DRItemModel','delivery_receipt_item_id', 'id')->select(['id','delivery_receipt']);
	}

	public function __get_seller_product(){
		return $this->belongsTo('fulfillmentmodel\SellerProductItemModel','uid', 'serial_number')->select(['id','user_id', 'serial_number','seller_product','status']);
	}

	public function __get_seller_shipment(){
		return $this->belongsTo('warehousemodel\ShipMentModel','shipment', 'id')->select(['id','request_code']);
	}
}
