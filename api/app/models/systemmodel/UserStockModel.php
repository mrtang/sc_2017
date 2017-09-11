<?php namespace systemmodel;

use Eloquent;
class UserStockModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'sys_user_stocks' ;
    protected $connection       = 'sysdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
