<?php namespace warehousemodel;


class PutAwayItemModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'warehouse_putawayitem' ;
    protected $connection       = 'warehousebm';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_seller_product(){
		return $this->belongsTo('fulfillmentmodel\SellerProductItemModel','uid', 'serial_number')->select(['id','user_id', 'serial_number','seller_product','status']);
	}
}
