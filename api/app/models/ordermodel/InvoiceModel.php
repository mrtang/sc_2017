<?php namespace ordermodel;

class InvoiceModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'invoice' ;
    protected $connection       = 'orderdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public function User(){
        return $this->belongsTo('User','user_id')->select(array('id', 'email', 'fullname', 'phone'));;
    }
}
