<?php namespace boxmemodel;


class VerifyOrderDetailModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'verify_order_detail' ;
    protected $connection       = 'accbm';
    protected $guarded          = array();
    public    $timestamps       = false;
}
