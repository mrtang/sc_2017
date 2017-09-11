<?php namespace ordermodel;

use Eloquent;
class FulfillmentModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'order_fulfilment' ;
    protected $connection       = 'orderdb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_detail(){
		return $this->hasMany('ordermodel\FulfillmentDetailModel','fulfillment_id');
	}
}
