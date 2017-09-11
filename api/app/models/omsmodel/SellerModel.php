<?php namespace omsmodel;

use Eloquent;
class SellerModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'oms_seller' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function new_customer(){
        return $this->belongsTo('omsmodel\CustomerAdminModel','user_id','user_id')
                    ->select(['id','user_id','first_order_time','last_order_time', 'first_accept_order_time','first_success_order_time',
                        'first_return_order_time','first_time_verifed', 'first_time_paid','last_accept_order_time']);
    }

    public function __get_user(){
        return $this->belongsTo('\User','user_id')->select(['id','fullname','email','phone', 'time_last_login']);
    }

    public function __get_userinfo(){
        return $this->belongsTo('\sellermodel\UserInfoModel','user_id','user_id')->select(['user_id','user_nl_id','priority_payment']);
    }

    public function __get_return(){
        return $this->belongsTo('omsmodel\LogSellerModel','user_id','user_id')->select(['user_id','seller_id']);
    }
}
