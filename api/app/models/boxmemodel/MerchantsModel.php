<?php namespace boxmemodel;


class MerchantsModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'merchants' ;
    protected $connection       = 'accbm';
    protected $guarded          = array();
    public    $timestamps       = false;
}
