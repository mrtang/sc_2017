<?php namespace crmmodel;

use Eloquent;
class OpportunityModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'opportunity' ;
    protected $connection       = 'crmdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
