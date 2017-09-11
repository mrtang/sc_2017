<?php namespace loyaltymodel;

use Input;
class CampaignDetailModel extends \Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'campaign_detail' ;
    protected $connection       = 'loyaltydb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function newQuery($excludeDeleted = true) {
        $CountryId      = Input::has('country_id')      ? (int)Input::get('country_id')          : 237;

        return parent::newQuery($excludeDeleted = true)
            ->where('country_id', '=', $CountryId);
    }

    public function get_user(){
        return $this->belongsTo('User','user_id')->select(['id','email','phone','fullname', 'time_create']);
    }

    public function get_campaign(){
        return $this->belongsTo('loyaltymodel\CampaignModel','campaign_id')->select(['id','name','value','point','category_id']);
    }

}
