<?php namespace metadatamodel;

use Eloquent;
class CourierMapModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'courier_map_ttc' ;
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
