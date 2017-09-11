<?php namespace ordermodel;

use Eloquent;
class StatusModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'order_status' ;
    protected $connection       = 'orderdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public function MetaStatus(){
        return $this->belongsTo('metadatamodel\OrderStatusModel','status','code');
    }
}
