<?php namespace fulfillmentmodel;


class PackingByUserModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ff_packing_by_user' ;
    protected $connection       = 'ffdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
