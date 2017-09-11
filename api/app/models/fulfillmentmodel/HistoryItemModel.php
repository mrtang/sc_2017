<?php namespace fulfillmentmodel;


class HistoryItemModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ff_itemhistory' ;
    protected $connection       = 'ffdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
