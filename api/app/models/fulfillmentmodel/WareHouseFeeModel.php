<?php namespace fulfillmentmodel;


class WareHouseFeeModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ff_warehousefee' ;
    protected $connection       = 'ffdb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function getOrganization(){
		return $this->belongsTo('metadatamodel\OrganizationUserModel','organization','id')->select(['id','fullname','email','phone','website','career','stock','payment_type']);
	}

	public function __get_user(){
		return $this->belongsTo('\User','user_id')->select(['id','fullname','email','phone']);
	}

	public function __warehouse_detail(){
		return $this->hasMany('fulfillmentmodel\WareHouseFeeDetailModel', 'log_id');
	}
	public function __warehouse_sku_detail(){
		return $this->hasMany('fulfillmentmodel\WareHouseFeeSkuModel', 'log_id');
	}
}
