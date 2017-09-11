<?php

class ScenarioTemplateModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
   
	protected $table = 'scenario_template' ;
    protected $connection = 'noticedb';
    public    $timestamps = false;
}
