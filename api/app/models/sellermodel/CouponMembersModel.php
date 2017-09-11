<?php namespace sellermodel;

use Eloquent;
class CouponMembersModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'coupon_members' ;
    protected $connection       = 'sellerdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function user (){
    	return $this->belongsTo('User', 'user_id')->select('id', 'email', 'fullname', 'phone');
    }
}
