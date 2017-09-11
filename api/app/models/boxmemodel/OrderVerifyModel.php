<?php namespace boxmemodel;


class OrderVerifyModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'order_verify' ;
    protected $connection       = 'accbm';
    protected $guarded          = array();
    public    $timestamps       = false;
}
