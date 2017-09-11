<?php namespace systemmodel;

use Eloquent;
class UserConfigModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'sys_user_config' ;
    protected $connection       = 'sysdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
