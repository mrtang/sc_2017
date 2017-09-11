<?php namespace ordermodel;

use Eloquent;
class OrderTrackingModel extends Eloquent {
        
	protected $table            = 'order_tracking' ;
    protected $connection       = 'orderdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public function Order(){
        return $this->belongsTo('ordermodel\OrdersModel','order_id');
    }
}
