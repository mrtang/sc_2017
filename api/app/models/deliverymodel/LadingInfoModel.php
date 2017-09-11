<?php namespace deliverymodel;

use Eloquent;
class LadingInfoModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'lading_info' ;
    protected $connection       = 'dedb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
