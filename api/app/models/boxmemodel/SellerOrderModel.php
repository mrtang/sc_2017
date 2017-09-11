<?php namespace boxmemodel;


class SellerOrderModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'sellercenter_order' ;
    protected $connection       = 'sellerbm';
    protected $guarded          = array();
    public    $timestamps       = false;
}
