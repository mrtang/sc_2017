<?php namespace crmmodel;

use Eloquent;
class OwnerModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ownedsecurableitem' ;
    protected $connection       = 'crmdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
