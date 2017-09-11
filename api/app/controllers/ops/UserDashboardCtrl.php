<?php
namespace ops;
use ordermodel\OrdersModel;
use omsmodel\TasksModel;
use omsmodel\TasksReferModel;
use omsmodel\TasksCategoryModel;
use omsmodel\TasksAssginModel;
use omsmodel\TasksCommentModel;
use LMongo;


class UserDashboardCtrl extends BaseCtrl
{

    public $page;
    

    public $time_start;
    public $time_end;

    public $limit;
    public $offset;
    public $total = 0;

    private $user_id,$user;


    public function __construct()
    {

        $this->user     = $this->UserInfo();
        $this->user_id  = (int)$this->user['id'];


        $this->time_start   = Input::has('time_start')   ? (int)Input::get('time_start')     : $this->time() - 7 * 86400;
        $this->time_end     = Input::has('time_end')     ? (int)Input::get('time_end')       : $this->time();

        $this->page         = Input::has('page')         ? (int)Input::get('page')           : 1;
        $this->limit        = Input::has('limit')        ? (int)Input::get('limit')          : 20;
        $this->offset       = ($this->page - 1) * $this->limit;
    }       

    public function getIndex(){
        return 1;
    }


    public function getRecentActivity(){
        $Data = [];
        $User = [];

        try {
            $Model = LMongo::collection('log_task_change')->orderBy('time_create','desc')->where('user_id', $this->user_id)->whereGte('time_create', $this->time_start)->get()->toArray();
        } catch (Exception $e) {
            $this->_error = true;
            $this->_error_message = "Lỗi truy vấn !";
            goto done;
        }
        
        $ListUserId = [];
        foreach ($Model as $key => $value) {
            $Data[] = $value;
            $ListUserId[] = (int)$value['user_id'];
            $ListUserId[] = (int)$value['assginer_id'];
        }
        $ListUserId = array_unique($ListUserId);

        if(empty($ListUserId)) goto done;



        $User = \User::whereIn('id', $ListUserId)->select(['id', 'fullname', 'email', 'phone'])->get()->toArray();

        done:
        return $this->_ResponseData($Data, ['user'=> $User]);
    }


    public function getTaskStatitics(){
        $AssginModel = new TasksAssginModel;

        $TaskCtrl = new \oms\TasksController();
        $Model    = $TaskCtrl->getModel();

        $ListTaskId = $AssginModel->getListTaskOfUser($this->user_id);
        $Model = $Model->whereIn('id', $ListTaskId);

        $TaskOverDue    = clone $Model;
        $TaskOveringDue = clone $Model;
        $TaskProcessing = clone $Model;


        $TaskOverDue    = $TaskOverDue->where('due_date', '>', 0)->where('due_date', '<', $this->time())->where('state', '!=', 'CLOSED')->count();
        $TaskOveringDue = $TaskOveringDue->where('due_date', '>', $this->time())->where('due_date','<=', $this->time() - 86400)->where('state', '!=', 'CLOSED')->count();
        $TaskProcessing = $TaskProcessing->whereNotIn('state', ['CLOSED'])->count();

        return $this->_ResponseData([
            'over_due'      => $TaskOverDue,
            'overing_due'   => $TaskOveringDue,
            'processing'    => $TaskProcessing,
        ]);


    }


    


}
?>