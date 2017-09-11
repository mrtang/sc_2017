<?php

class TemplateModel extends Eloquent {
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'template' ;
    protected $connection   = 'noticedb';
    public    $timestamps   = false;
    
}
