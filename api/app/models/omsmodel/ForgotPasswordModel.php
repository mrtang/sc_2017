<?php namespace omsmodel;

use Eloquent;
class ForgotPasswordModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'oms_forgot_password' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
