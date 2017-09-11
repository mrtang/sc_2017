<?php namespace fulfillmentmodel;


class SellerProductItemModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ff_sellerproductitem' ;
    protected $connection       = 'ffdb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __putaway(){
		return $this->belongsTo('\warehousemodel\PutAwayItemModel','serial_number','uid')->select(['id','put_away_code','uid', 'sku', 'bin', 'warehouse', 'create_time']);
	}

	public function __get_user(){
		return $this->belongsTo('\User','user_id')->select(['id','fullname','phone','email','time_create']);
	}

	public function __product(){
		return $this->belongsTo('fulfillmentmodel\SellerProductModel','seller_product','id')->select(['id','user_id', 'product', 'barcode', 'category_name', 'name','desc','product_model_name','category','brand','barcode_manufacturer','tag','quantity','weight','packing_volume']);
	}

	public function __history(){
		return $this->hasMany('fulfillmentmodel\ItemHistoryModel','uid','serial_number');
	}

	public function __inventory(){
		return $this->belongsTo('sellermodel\UserInventoryModel','inventory','id')->select(['id','user_id', 'name', 'user_name', 'phone', 'country_id', 'city_id', 'province_id', 'ward_id', 'address', 'warehouse_code']);
	}

	public function __shipment(){
		return $this->belongsTo('\fulfillmentmodel\ShipMentModel','shipment','id')->select(['id','request_code','tracking_number', 'warehouse', 'status']);
	}
	
}
