<?php namespace omsmodel;

use Eloquent;
class NotifyFacebookModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'oms_notify_facebook' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
