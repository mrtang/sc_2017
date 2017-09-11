<?php namespace boxmemodel;


class AccTransactionModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'transactions' ;
    protected $connection       = 'accbm';
    protected $guarded          = array();
    public    $timestamps       = false;
}
