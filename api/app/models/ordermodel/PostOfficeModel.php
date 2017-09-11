<?php namespace ordermodel;

class PostOfficeModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'order_postoffice' ;
    protected $connection       = 'orderdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
