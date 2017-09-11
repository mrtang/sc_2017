<?php namespace accountingmodel;

class OrdersModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'orders' ;
    protected $connection       = 'acc_orderdb';
    protected $guarded          = array();
    public    $timestamps       = false;


    public function MetaStatus(){
        return $this->belongsTo('metadatamodel\OrderStatusModel','status','code');
    }

    public function Postman(){
        return $this->belongsTo('PostManModel','postman_id', 'id');
    }

    public function GroupStatus(){
        return $this->belongsTo('metadatamodel\GroupOrderStatusModel','status','order_status_code')
            ->where('group_status','>=',12)
            ->where('group_status','<=',22)
            ->select(array('order_status_code','group_status'));
    }

    public function CourierNote(){
        return $this->hasMany('omsmodel\CourierNoteModel', 'order_id')->orderBy('id', 'DESC')->limit(5);
    }

    public function OrderProcess(){
        return $this->hasMany('ordermodel\OrderProcessModel','order_id')->orderBy('id', 'DESC')->limit(5);
    }

    public function BankingInfo(){
        return $this->hasMany('sellermodel\BankingModel','user_id','from_user_id');
    }

    public function OrderDetail(){
        return $this->hasOne('ordermodel\DetailModel','order_id');
    }

    public function OrderItem(){
        return $this->hasOne('ordermodel\OrderItemModel','order_id');
    }

    public function OrderStatus(){
        return $this->hasMany('ordermodel\StatusModel','order_id');
    }

    public function OrderStatusInOrderProcess(){
        return $this->hasMany('ordermodel\StatusModel','order_id')->orderBy('time_create','DESC')->limit(3);
    }

    public function Courier(){
        return $this->belongsTo('CourierModel','courier_id');
    }

    public function Service(){
        return $this->belongsTo('CourierServiceModel','service_id');
    }

    public function FromOrderAddress(){
        return $this->belongsTo('sellermodel\UserInventoryModel','from_address_id');
    }

    public function ToOrderAddress(){
        return $this->belongsTo('ordermodel\AddressModel','to_address_id');
    }

    public function FromUser(){
        return $this->belongsTo('User','from_user_id')->select(['id','email','fullname','phone']);
    }

    public function FromUserData(){
        return $this->belongsTo('User','from_user_id');
    }

    public function ToUserId(){
        return $this->belongsTo('User','to_user_id');
    }
    public function City(){
        return $this->belongsTo('CityModel','from_city_id');
    }
    public function District(){
        return $this->belongsTo('DistrictModel','from_district_id');
    }
    public function Ward(){
        return $this->belongsTo('WardModel','from_ward_id');
    }

    public function Item(){
        return $this->hasManyThrough('ordermodel\OrderItemModel', 'ordermodel\ItemsModel', 'id', 'item_id');
    }

    public function pipe_journey(){
        return $this->hasMany('omsmodel\PipeJourneyModel','tracking_code');
    }

    //Seller
    public function SellerDetail(){
        return $this->hasOne('ordermodel\DetailModel','order_id')->select(['id','order_id','sc_pvc','sc_cod', 'sc_pbh', 'sc_pvk', 'sc_pch', 'sc_discount_pvc', 'sc_discount_cod', 'money_collect']);
    }

    public function OrderFulfillment(){
        return $this->hasOne('ordermodel\FulfillmentModel','order_id');
    }
}
