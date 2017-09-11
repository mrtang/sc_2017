<?php namespace accountingmodel;

use Input;
class VerifyModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'order_verify' ;
    protected $connection       = 'acc_orderdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function newQuery($excludeDeleted = true) {
        $CountryId      = Input::has('country_id')      ? (int)Input::get('country_id')          : 237;

        return parent::newQuery($excludeDeleted = true)
            ->where('country_id', '=', $CountryId);
    }
    
    public function User(){
        return $this->belongsTo('User','user_id');
    }
    
    public function Order(){
        return $this->hasMany('\ordermodel\OrdersModel','verify_id');
    }

    public function Merchant(){
        return $this->hasOne('\accountingmodel\MerchantModel','merchant_id');
    }
}
