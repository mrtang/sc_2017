<?php namespace fulfillmentmodel;


class PackingByWeightModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ff_packing_by_weight' ;
    protected $connection       = 'ffdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
