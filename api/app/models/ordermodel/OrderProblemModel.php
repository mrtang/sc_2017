<?php namespace ordermodel;

class OrderProblemModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'order_problem' ;
    protected $connection       = 'orderdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function Order(){
        return $this->belongsTo('ordermodel\OrdersModel','order_id')->select(['id','tracking_code','status', 'courier_id','to_district_id','to_address_id',
            'time_accept','time_success','to_name','to_phone', 'product_name','order_code','total_weight', 'over_weight', 'to_country_id']);
    }

    public function OrderDetail(){
        return $this->belongsTo('ordermodel\DetailModel','order_id','order_id')->select(['id','order_id','money_collect','sc_pvk']);
    }

    public function OrderStatus(){
        return $this->hasMany('ordermodel\StatusModel','order_id','order_id')->orderBy('time_create','DESC')->select(['id','order_id','status','city_name','note','time_create']);
    }
}
