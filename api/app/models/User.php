<?php

class User extends Eloquent {



    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'users';
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */

    public function ticket_assign(){
        return $this->hasMany('ticketmodel\AssignModel','assign_id');
    }

    public function user_info(){
        return $this->hasOne('sellermodel\UserInfoModel','user_id');
    }

    public function info(){
        return $this->hasOne('sellermodel\UserInfoModel','user_id')->select(['id','user_id','priority_payment']);
    }

    public function loyalty(){
        return $this->hasOne('\loyaltymodel\UserModel','user_id')->select(['id','user_id','total_point','current_point','level','active']);
    }

    public function oms(){
        return $this->hasOne('omsmodel\CustomerAdminModel','user_id');
    }

    public function oms_seller(){
        return $this->hasOne('omsmodel\SellerModel','user_id')->select(['seller_id','user_id','first_time_pickup','first_time_incomings']);
    }

    public function district(){
        return $this->belongsTo('DistrictModel','district_id');
    }
    public function city(){
        return $this->belongsTo('CityModel', 'city_id');
    }
    public function ward(){
        return $this->belongsTo('WardModel','ward_id');
    }

    public function merchant(){
        return $this->hasOne('accountingmodel\CustomMerchantModel','merchant_id');
    }

    public function getUserById($ListId){
        $User = [];
        $Data =  $this->whereIn('id', $ListId)->get(['id', 'fullname', 'email', 'phone'])->toArray();
        if(!empty($Data)){
            foreach($Data as $val){
                $User[(int)$val['id']]  = $val;
            }
        }
        return $User;
    }

    public function get_by_id($id){
        return self::where('id',$id)->remember(60)->first(['id','email','fullname','phone']);
    }

    public function get_organization(){
        return $this->belongsTo('metadatamodel\OrganizationUserModel','organization')->select(['id','fullname','email','phone','payment_type']);
    }
}
