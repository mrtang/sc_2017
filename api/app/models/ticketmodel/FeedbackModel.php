<?php namespace ticketmodel;

use Eloquent;
class FeedbackModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'feedback' ;
    protected $connection       = 'ticketdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public function users(){
        return $this->belongsTo('User','user_id');
    }
    
    public function attach(){
        return $this->hasMany('ticketmodel\AttachModel','refer_id');
    }
    
}
