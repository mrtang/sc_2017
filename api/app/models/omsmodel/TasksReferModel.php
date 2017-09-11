<?php namespace omsmodel;

use Eloquent;
class TasksReferModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'tasks_refer' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function inventory(){
        return $this->belongsTo('\sellermodel\UserInventoryModel','refer_id');
    }
    public function task(){
    	return $this->belongsTo('\omsmodel\TasksModel','task_id');	
    }

}
