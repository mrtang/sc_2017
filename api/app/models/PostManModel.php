<?php

class PostManModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'postman' ;
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;
    
}
