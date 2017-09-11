<?php namespace sellermodel;

use Eloquent;
class CouponModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'coupons' ;
    protected $connection       = 'sellerdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function members (){
    	return $this->hasMany('sellermodel\CouponMembersModel', 'coupon_id');
    }

    public function isUsageLimit(){
        return $this->usaged >= $this->limit_usage;
    }

    public function isOnlyUseForApp(){
        return $this->inapp == 1;
    }

    // Coupon được gán cho user cố định
    public function AssginByUserId(){
        return $this->coupon_type == 2;
    }

    public function CouponForNinjavan(){
        return $this->code == 'NJV10';
    }

    static function caculateCoupons($OrderDetail, $Coupon){
        
    }
}
