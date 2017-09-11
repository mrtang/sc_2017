<?php namespace boxmemodel;


class VerifyItemDetailModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'verify_item_detail' ;
    protected $connection       = 'accbm';
    protected $guarded          = array();
    public    $timestamps       = false;
}
