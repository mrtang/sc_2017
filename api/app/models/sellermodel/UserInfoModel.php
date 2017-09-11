<?php namespace sellermodel;

use Eloquent;
class UserInfoModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'user_info' ;
    protected $connection       = 'sellerdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public function user(){
        return $this->belongsTo('User','user_id')->select(['id','email','phone','fullname', 'time_create']);
    }
    
    public function pipe_journey(){
        return $this->hasMany('omsmodel\PipeJourneyModel', 'tracking_code', 'user_id');
    }
    public function merchant(){
        return $this->hasOne('accountingmodel\MerchantModel','merchant_id', 'user_id');
    }

    public function bankInfo(){
        return $this->belongsTo('sellermodel\VimoModel','user_id', 'user_id');
    }
    public function pipe_status(){
        return $this->belongsTo('omsmodel\PipeStatusModel','pipe_status', 'status');
    }
    public function orderInfo(){
        return $this->belongsTo('omsmodel\SellerModel','user_id','user_id');
    }

    public static function checkNLAccount ($user_id){
        $seller =  self::where('user_id', '=', $user_id)->first();
        if(!empty($seller['email_nl'])){
            return $seller;
        }
        return false;
     }

    public function getVip(){
       return self::where('is_vip', 1)->remember(1440)->lists('user_id');
    }
}
