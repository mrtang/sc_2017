<?php namespace deliverymodel;

use Eloquent;
class TransactionModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'transactions' ;
    protected $connection       = 'dedb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
