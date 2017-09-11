<?php namespace boxmemodel;


class ItemHistoryModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'product_item_history' ;
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
