<?php namespace crmmodel;

use Eloquent;
class ContactModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'contact' ;
    protected $connection       = 'crmdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
