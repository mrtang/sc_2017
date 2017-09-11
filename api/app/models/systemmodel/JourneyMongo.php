<?php namespace systemmodel;
class JourneyMongo extends \EloquentMongo {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    protected $collection       = 'log_journey_lading';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
