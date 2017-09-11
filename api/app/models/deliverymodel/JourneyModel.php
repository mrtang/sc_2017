<?php namespace deliverymodel;

use Eloquent;
class JourneyModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'journey' ;
    protected $connection       = 'dedb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
