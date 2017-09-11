<?php namespace fulfillmentmodel;


class PackingModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ff_packing' ;
    protected $connection       = 'ffdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
