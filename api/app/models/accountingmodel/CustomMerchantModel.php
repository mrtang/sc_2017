<?php namespace accountingmodel;

use Eloquent;
use Input;
class CustomMerchantModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'merchants' ;
    protected $connection       = 'accdb';
    protected $guarded          = array();
    public    $timestamps       = false;


    public function User(){
        return $this->belongsTo('\User','merchant_id')->select(['id','fullname','email','phone']);
    }

    public function UserInfo(){
        return $this->belongsTo('sellermodel\UserInfoModel','merchant_id','user_id')->select(['id','user_id','user_nl_id','email_nl','priority_payment','freeze_money']);
    }

    public function VimoConfig(){
        return $this->belongsTo('sellermodel\VimoModel','merchant_id','user_id');
    }

    public function Order(){
        return $this->belongsTo('ordermodel\OrdersModel','merchant_id','from_user_id');
    }

    public function OrderDetail(){
        return $this->hasManyThrough('ordermodel\OrdersModel', 'ordermodel\DetailModel','order_id','id');
    }
}
