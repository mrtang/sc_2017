<?php namespace ticketmodel;

use Eloquent;
class AssignModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ticket_assign' ;
    protected $connection       = 'ticketdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public function request(){
        return $this->belongsTo('ticketmodel\RequestModel','ticket_id');
    }
    
    public function user_assign(){
        return $this->belongsTo('User','assign_id');
    }
}
