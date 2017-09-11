<?php namespace boxmemodel;


class SellerStockModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'sellercenter_stock' ;
    protected $connection       = 'sellerbm';
    protected $guarded          = array();
    public    $timestamps       = false;
}
