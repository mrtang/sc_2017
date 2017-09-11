<?php namespace warehousemodel;


class ReturnItemModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'warehouse_returnitem' ;
    protected $connection       = 'warehousebm';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_seller_product(){
		return $this->belongsTo('fulfillmentmodel\SellerProductItemModel','uid', 'serial_number')->select(['id','user_id', 'serial_number','seller_product','status']);
	}

	public function __get_return(){
		return $this->hasOne('fulfillmentmodel\ReturnModel','return_code','return_code')->select(['id','return_code','update_by']);
	}
}
