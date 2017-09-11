<?php

class ScenarioModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'scenario' ;
    protected $connection   = 'noticedb';
    public    $timestamps   = false;
    
}
