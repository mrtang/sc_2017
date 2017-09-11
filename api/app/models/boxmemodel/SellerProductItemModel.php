<?php namespace boxmemodel;


class SellerProductItemModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ecommerce_sellerproductitem' ;
    protected $connection       = 'ecmbm';
    protected $guarded          = array();
    public    $timestamps       = false;
}
