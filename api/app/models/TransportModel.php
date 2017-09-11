<?php

class TransportModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'transport' ;
    protected $connection   = 'noticedb';
    public    $timestamps   = false;
    
}
