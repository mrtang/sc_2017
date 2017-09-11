<?php namespace deliverymodel;

use Eloquent;
class LadingModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'lading' ;
    protected $connection       = 'dedb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
