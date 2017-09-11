<?php namespace metadatamodel;

use Eloquent;
class TokenModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'merchant_token' ;
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
