<?php namespace systemmodel;
class SendSmsMongo extends \EloquentMongo {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    protected $collection       = 'log_send_sms';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
