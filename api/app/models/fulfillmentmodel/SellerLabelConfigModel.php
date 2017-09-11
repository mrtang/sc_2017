<?php namespace fulfillmentmodel;


class SellerLabelConfigModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ff_sellerlabelconfig' ;
    protected $connection       = 'ffdb';
    protected $guarded          = array();
    public    $timestamps       = false;


}
