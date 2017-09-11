<?php namespace sellermodel;

use Eloquent;
class ChildInfoModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'child_info' ;
    protected $connection       = 'sellerdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
