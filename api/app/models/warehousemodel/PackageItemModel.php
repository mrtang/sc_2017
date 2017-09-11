<?php namespace warehousemodel;


class PackageItemModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'warehouse_packageitem' ;
    protected $connection       = 'warehousebm';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_seller_product(){
		return $this->belongsTo('fulfillmentmodel\SellerProductItemModel','uid', 'serial_number')->select(['id','user_id', 'serial_number','seller_product','status']);
	}

	public function __get_package(){
		return $this->belongsTo('warehousemodel\PackageModel','package', 'id')->select(['id','size','status','create']);
	}

	public function pipe_journey(){
		return $this->hasMany('omsmodel\PipeJourneyModel','tracking_code');
	}

	public function __get_history(){
		return $this->hasMany('warehousemodel\PackageHistoryModel','sku', 'sku')->orderBy('id','ASC');
	}
	public function __get_product(){
		return $this->belongsTo('fulfillmentmodel\SellerProductModel','sku', 'sku')->select(['id','user_id', 'sku','volume', 'name','supplier_name','category_name','packing_volume']);
	}
}
