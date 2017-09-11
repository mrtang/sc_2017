<?php namespace fulfillmentmodel;


class ImageObjectModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ff_imageobject' ;
    protected $connection       = 'ffdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
