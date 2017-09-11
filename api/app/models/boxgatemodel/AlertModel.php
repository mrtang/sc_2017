<?php namespace boxgatemodel;

use Eloquent;
class AlertModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'alert' ;
    protected $connection       = 'bgdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
