<?php namespace omsmodel;

use Eloquent;
class TasksAssginModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'tasks_assgin' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function InsertAssigner($tasksId, $userId, $assignIds){
        $Model = new TasksAssginModel;
        $Inserts = [];
        foreach ($assignIds as $key => $value) {
            $Inserts[] = [
                'user_id'       => $userId,
                'task_id'       => $tasksId,
                'assign_id'     => $value,
                'time_create'   => time()
            ];
        }
        
        try {
            $Result = $Model->insert($Inserts);
        } catch (Exception $e) {
            return false;
        }

        return $Result;
    }

    // Return Array
    public function getListTaskOfUser($UserId, $TimeStart = 0, $TimeEnd = 0){
        $Model = $this->where('assign_id', $UserId);

        if(!empty($TimeStart)){
            $Model = $Model->where('time_create', '>=', $TimeStart);
        }else {
            $Model = $Model->where('time_create', '>=', time() - 7 * 86401); 
        }

        if(!empty($TimeEnd)){
            $Model = $Model->where('time_create', '<=', $TimeEnd);
        }

        $ListId = $Model->get()->lists('task_id');
        return $ListId;
    }
}
