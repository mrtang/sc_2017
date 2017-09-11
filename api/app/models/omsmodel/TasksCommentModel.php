<?php namespace omsmodel;

use Eloquent;
class TasksCommentModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'tasks_comment' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function Add($TasksId, $UserId, $Content){
        $this->user_id      = $UserId;
        $this->task_id      = $TasksId;
        $this->content      = $Content;
        $this->time_create  = time();


        try {
            $Result = $this->save();
        } catch (Exception $e) {
            return false;
        }

        return $this;
    }

}
