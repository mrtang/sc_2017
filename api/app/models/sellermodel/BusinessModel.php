<?php namespace sellermodel;

use Eloquent;
class BusinessModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'business_info' ;
    protected $connection       = 'sellerdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
