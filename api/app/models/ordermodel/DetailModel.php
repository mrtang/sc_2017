<?php namespace ordermodel;

use Eloquent;
class DetailModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'order_detail' ;
    protected $connection       = 'orderdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function Order(){
        return $this->belongsTo('ordermodel\OrdersModel','order_id');
    }
}
