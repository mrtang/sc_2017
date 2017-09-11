<?php namespace metadatamodel;

use Eloquent;
class GroupOrderStatusModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'group_order_status' ;
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public function OrderStatus(){
        return $this->hasOne('metadatamodel\OrderStatusModel','code','order_status_code');
    }

    public function group_status_merchant(){
        return $this->belongsTo('metadatamodel\GroupStatusModel','group_status');
    }
}
