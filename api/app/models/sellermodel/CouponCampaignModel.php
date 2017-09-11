<?php namespace sellermodel;

use Eloquent;
class CouponCampaignModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'coupon_campaigns' ;
    protected $connection       = 'sellerdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function coupons_count(){
        return $this->hasOne('sellermodel\CouponModel','campaign_id')->selectRaw('campaign_id, count(*) as total')->groupBy('campaign_id');
    }

}
