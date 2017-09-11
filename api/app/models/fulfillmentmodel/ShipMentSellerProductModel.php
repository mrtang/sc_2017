<?php namespace fulfillmentmodel;


class ShipMentSellerProductModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ff_shipmentsellerproduct' ;
    protected $connection       = 'ffdb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_seller_product(){
		return $this->belongsTo('fulfillmentmodel\SellerProductModel','seller_product')
			->select(['id','sku','barcode','category_name','supplier_name','name']);
	}
}
