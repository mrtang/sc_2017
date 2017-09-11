<?php namespace boxmemodel;


class SellerPackingModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'sellercenter_packing' ;
    protected $connection       = 'sellerbm';
    protected $guarded          = array();
    public    $timestamps       = false;
}
