<?php namespace metadatamodel;

use Eloquent;
class ProvinceModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'province_ttc' ;
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
