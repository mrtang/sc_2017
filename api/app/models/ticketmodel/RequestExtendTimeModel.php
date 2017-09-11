<?php namespace ticketmodel;

use Eloquent;
class RequestExtendTimeModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'request_extend_time' ;
    protected $connection       = 'ticketdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function user(){
    	return $this->belongsTo('User', 'user_id');
    }
    public function ticket(){
    	return $this->belongsTo('ticketmodel\RequestModel', 'ticket_id');
    }
    
}
