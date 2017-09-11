<?php namespace crmmodel;

use Eloquent;
class CustomfieldModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'customfield' ;
    protected $connection       = 'crmdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
