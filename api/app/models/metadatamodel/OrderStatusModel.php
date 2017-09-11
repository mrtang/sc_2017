<?php namespace metadatamodel;

use Eloquent;
class OrderStatusModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'order_status' ;
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function get_status(){
        $Data    = [];
        $Status   = self::remember(60)->get()->toArray();
        if(!empty($Status)){
            foreach($Status as $val){
                $Data[(int)$val['code']]  = $val['name'];
            }
        }
        return $Data;
    }

    public function Orders(){
        return $this->hasMany('ordermodel\OrdersModel','status','code');
    }

    public function group_order_status(){
        return $this->belongsTo('metadatamodel\GroupOrderStatusModel','code','order_status_code');
    }

    public function getNameAttribute($value)
    {
        $ret  = $this->attributes['name'];
        $locate = \Config::get('app.locale');

        if(!empty($locate) && $locate !== 'vi'){
            $ret  = $this->attributes['name_'.$locate];
        }
        return $ret;
    }
}
