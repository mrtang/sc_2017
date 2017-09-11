<?php namespace fulfillmentmodel;


class SellerProductModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ff_sellerproduct' ;
    protected $connection       = 'ffdb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_user(){
		return $this->belongsTo('\User','user_id')->select(['id','fullname','phone','email','time_create']);
	}

	public function __image(){
		return $this->hasMany('fulfillmentmodel\ImageObjectModel','data_id')->select(['id','user','data_id', 'url']);
	}
}
