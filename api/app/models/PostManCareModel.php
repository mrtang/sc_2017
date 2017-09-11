<?php

class PostManCareModel extends Eloquent {
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'postman_care' ;
    protected $connection   = 'courierdb';
    public    $timestamps   = false;
    
}
