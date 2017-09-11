<?php namespace crmmodel;

use Eloquent;
class EmailModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'email' ;
    protected $connection       = 'crmdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
