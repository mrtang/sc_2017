<?php namespace systemmodel;

use Eloquent;
class UsersModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'sys_user' ;
    protected $connection       = 'sysdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
