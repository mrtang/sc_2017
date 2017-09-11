<?php namespace ticketmodel;

use Eloquent;
class RatingModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'rating' ;
    protected $connection       = 'ticketdb';
    protected $guarded          = array();
    public    $timestamps       = false;
	
	function ticket(){
		return $this->belongsTo('ticketmodel\RequestModel','ticket_id')->select(['title', 'time_over', 'id', 'status', 'user_id']);
	}

	public function case_ticket(){
		return $this->hasMany('ticketmodel\CaseTicketModel','ticket_id','ticket_id');
	}
}
