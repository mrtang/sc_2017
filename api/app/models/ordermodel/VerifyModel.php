<?php namespace ordermodel;

class VerifyModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'order_verify' ;
    protected $connection       = 'orderdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public function User(){
        return $this->belongsTo('User','user_id');
    }
    
    public function Order(){
        return $this->hasMany('ordermodel\OrdersModel','verify_id');
    }

    public function Merchant(){
        return $this->hasOne('accountingmodel\MerchantModel','merchant_id');
    }
}
