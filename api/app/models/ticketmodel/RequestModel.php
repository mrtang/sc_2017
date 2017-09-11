<?php namespace ticketmodel;

use Eloquent;
class RequestModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ticket_request' ;
    protected $connection       = 'ticketdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public function refer(){
        return $this->hasMany('ticketmodel\ReferModel','ticket_id');
    }
    
    public function feedback(){
        return $this->hasMany('ticketmodel\FeedbackModel','ticket_id');
    }
    
    public function rating(){
        return $this->hasMany('ticketmodel\RatingModel','ticket_id');
    }
    
    public function assign(){
        return $this->hasMany('ticketmodel\AssignModel','ticket_id');
    }
    
    public function users(){
        return $this->belongsTo('User','user_id')->select(['id', 'email', 'fullname']);
    }

    public function attach(){
        return $this->hasMany('ticketmodel\AttachModel','refer_id');
    }

    public function case_ticket(){
        return $this->hasMany('ticketmodel\CaseTicketModel','ticket_id');
    }
}
