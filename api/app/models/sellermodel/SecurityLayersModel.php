<?php namespace sellermodel;

class SecurityLayersModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'security_layers' ;
    protected $connection       = 'sellerdb';
    protected $guarded          = array();
    public    $timestamps       = false;

}
