<?php namespace omsmodel;

use Eloquent;
class NotifyConfirmUser extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'oms_notify_confirm_user' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
