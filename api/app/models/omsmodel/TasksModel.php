<?php namespace omsmodel;

use Eloquent;
class TasksModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'tasks' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function category(){
    	return $this->belongsTo('\omsmodel\TasksCategoryModel','category_id');	
    }
    public function comments(){
        return $this->hasMany('omsmodel\TasksCommentModel','task_id')->orderBy('id', 'DESC');
    }
    public function refers(){
        return $this->hasMany('omsmodel\TasksReferModel','task_id')->orderBy('id', 'DESC');
    }
    public function assigner(){
        return $this->hasMany('omsmodel\TasksAssginModel','task_id')->orderBy('id', 'DESC');
    }
}
