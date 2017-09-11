<?php namespace ticketmodel;

use Eloquent;
class CaseTicketModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'case_ticket' ;
    protected $connection       = 'ticketdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function case_type(){
        return $this->belongsTo('ticketmodel\CaseTypeModel','type_id');
    }
    
}
