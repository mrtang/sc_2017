<?php namespace fulfillmentmodel;


class ShipMentModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ff_shipment' ;
    protected $connection       = 'ffdb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_shipment_product(){
		return $this->hasMany('fulfillmentmodel\ShipMentSellerProductModel','shipment');
	}

	public function __get_user(){
		return $this->belongsTo('\User','user_id')->select(['id','fullname','phone','email','time_create']);
	}

	public function __get_outbound(){
		return $this->belongsTo('sellermodel\UserInventoryModel','inventory_outbound')
			->select(['id','user_id','name','user_name','phone','country_id','city_id','province_id','ward_id','address','warehouse_code']);
	}

	public function pipe_journey(){
		return $this->hasMany('omsmodel\PipeJourneyModel','tracking_code');
	}
}
