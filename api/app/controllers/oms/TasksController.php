<?php
namespace oms;
use ordermodel\OrdersModel;

use omsmodel\TasksModel;
use omsmodel\TasksReferModel;
use omsmodel\TasksCategoryModel;
use omsmodel\TasksAssginModel;
use omsmodel\TasksCommentModel;

use sellermodel\UserInventoryModel;
use User;
use Input;
use Response;
use DB;
use LMongo;

class TasksController extends \BaseController
{

	public $name;
	public $state;
	public $due_date;
    public $category;
	public $reminder;
	private $user;
	private $user_id;
	private $model;

    // for list
    public $keyword;
    public $filter;
    public $time_start;
    public $time_end;

    public $page;
    public $limit;
    public $offset;
    public $total = 0;


    public $logs = [];

    const REFER_TYPE_ORDER      = 1;
    const REFER_TYPE_INVENTORY  = 2;
    const REFER_TYPE_CUSTOMER   = 3;
        
    const STATE_NOT_STATED    = "NOT_STARTED";
    const STATE_IN_PROCESS    = "IN_PROCESS";
    const STATE_SUCCESS       = "SUCCESS";
    const STATE_PAUSED        = "PAUSED";

    const FILTER_CREATE_BY_ME = 'create_by_me';
    const FILTER_ASSIGN_ME    = 'assign_me';


    public function __construct()
    {
    	$this->model 	= new TasksModel;
    	$this->user 	= $this->UserInfo();
    	$this->user_id	= (int)$this->user['id'];

        $this->id            = Input::has('id')            ? (int)Input::get('id')          : 0;

    	$this->name 	     = Input::has('name') 	       ? Input::get('name') 			: "";
    	$this->state 	     = Input::has('state') 	       ? Input::get('state') 			: "";
        $this->task_refer    = Input::has('task_refer')    ? Input::get('task_refer')       : [];
        $this->assigner      = Input::has('assigner')      ? Input::get('assigner')         : [];
        $this->category      = Input::has('category')      ? (int)Input::get('category')    : 0;
    	$this->due_date      = Input::has('due_date')      ? (int)Input::get('due_date') 	: 0;
    	$this->reminder      = Input::has('reminder')      ? (int)Input::get('reminder') 	: 0;
        $this->description   = Input::has('description')   ? Input::get('description')      : "";


        // For list
        $this->keyword      = Input::has('keyword')      ? Input::get('keyword')             : "";
        $this->filter       = Input::has('filter')       ? Input::get('filter')              : "";
        $this->time_start   = Input::has('time_start')   ? (int)Input::get('time_start')     : $this->time() - 7 * 86400;
        $this->time_end     = Input::has('time_end')     ? (int)Input::get('time_end')       : $this->time();
        $this->page         = Input::has('page')         ? (int)Input::get('page')           : 1;
        $this->limit        = Input::has('limit')        ? (int)Input::get('limit')          : 20;
        $this->offset       = ($this->page - 1) * $this->limit;
    } 


    public function getModel(){
        $AssignModel = new TasksAssginModel;

        $Model  = new TasksModel;
        $Model  = $Model->where('time_create', '>=', $this->time_start)
                        ->where('time_create', '<', $this->time_end);

        

        if (!empty($this->name)) {
            $Model = $Model->where('name', 'LIKE', '%'.$this->name.'%');
        }

        if (!empty($this->category)) {
            $Model = $Model->where('category_id', $this->category);
        }

        if (!empty($this->filter)) {

            switch ($this->filter) {
                case self::FILTER_CREATE_BY_ME:
                    $Model = $Model->where('user_id', $this->user_id);
                    break;
                case self::FILTER_ASSIGN_ME:
                    $ListTaskId = $AssignModel->getListTaskOfUser($this->user_id);

                    if(empty($ListTaskId)){
                        return false;
                    }
                    $Model  = $Model->whereIn("id", $ListTaskId);
                    break;

                default:
                    # code...
                    break;
            }
        }

        
        $Model  = $Model->with(['category']);
        

        //$Model  = $Model->where('user_id', $this->user_id);


        return $Model;
    }



    public function getIndex (){
        $Result      = [];

        $Model       = $this->getModel();

        if(!$Model){
            goto done;
        }

        if (!empty($this->state) && $this->state != 'ALL') {
            $listState  = explode(',', $this->state);
            $Model      = $Model->whereIn('state', $listState);
        }


        $Total       = clone $Model;
        $this->total = $Total->count();

        if ($this->total == 0) {
            goto done;
        }
        
        $Result = $Model->with(['refers'])->skip($this->offset)->take($this->limit)->orderBy('time_create', 'DESC')->get()->toArray();

        

        done:
        return $this->_ResponseData($Result, [
            'page'      => $this->page,
            'item_page' => $this->limit,
            'total'     => $this->total
        ]);
    }


    // Show one record 
    public function getShow($Id){

        $Result  = [];
        $Model   = $this->getModel();

        if (!empty($this->state) && $this->state != 'ALL') {
            $listState  = explode(',', $this->state);
            $Model      = $Model->whereIn('state', $listState);
        }

        $Model   = $Model->with(['comments', 'assigner', 'refers']);
        $Result  = $Model->where('id', $Id)->first();

        if(empty($Result)) goto done;


        $ListUserId = [$Result->user_id];

        foreach ($Result->comments as $k => $comment) {
            if(!empty($comment->user_id)){
                $ListUserId[] = $comment->user_id;
            }
        }
        
        foreach ($Result->assigner as $k => $assigner) {
            if(!empty($assigner->assign_id)){
                $ListUserId[] = $assigner->assign_id;
            }
        }

        
        
        $Users = [];

        if(!empty($ListUserId)){
            $ListUserId = array_unique($ListUserId);
            $UsersModel = \User::whereIn('id', $ListUserId)->select(['id', 'fullname', 'email', 'phone'])->get()->toArray();
            foreach ($UsersModel as $key => $value) {
                $Users[$value['id']] = $value;
            }
        }


        done:
        return $this->_ResponseData($Result, [
            'users' => $Users
        ]);
    }

    
    public function getCountState(){
        $Model  = $this->getModel();
        $Data   = [];


        if(!$Model){
            goto done;
        }
        $Total  = clone $Model;
        $Total  = $Model->count();
        $Group  = $Model->groupBy('state')
                       ->select(DB::raw('count(*) as total, state'))
                       ->get()->toArray();

        $Data   = [
            'ALL' => $Total
        ];

        foreach ($Group as $key => $value) {
            $Data[$value['state']] = $value['total'];
        }

        done:
        return $this->_ResponseData($Data);
    }

    public function getTaskCategory (){
        $Model = new TasksCategoryModel;
        $Model = $Model->whereIn('user_id', [0, $this->user_id])->get()->toArray();
        return $this->_ResponseData($Model);
    }
    

    public function postUpdateField (){
        $field         = Input::has('field')          ? Input::get('field')             : "";
        $value         = Input::has('value')         ? Input::get('value')              : "";

        $ReturnData    = [];

        if(empty($this->id) || empty($field) || empty($value)){
            $this->_error_message   = "Dữ liệu gửi lên không đúng.";
            $this->_error           = true;
            goto done;
        }

        $SaveData                = [];
        $SaveData[$field]        = $value;
        $SaveData['time_update'] = $this->time();

        $Result = TasksModel::where('id', $this->id)->update($SaveData);
        if ($Result) {
            $this->_error_message   = "Cập nhật thành công";
            goto done;
        }

        $this->_error           = true;
        $this->_error_message   = "Cập nhật thất bại";

        done:
        return $this->_ResponseData($ReturnData);
    }




    public function postAddTask(){
        $Result = [];

    	if(empty($this->name)){
    		$this->_error         = true;
    		$this->_error_message = "Tên công việc không được để trống";
    		goto done;
    	}

        if(empty($this->category)){
            $this->_error         = true;
            $this->_error_message = "Vui lòng chọn loại công việc";
            goto done;
        }

    	$Task      = $this->addTask();
        $TaskId    = $Task->id;
    	if($this->_error) goto done;

        

        $AssignModel = new TasksAssginModel;


        $Assginer = [$this->user_id];

        if(!empty($this->assigner)){
            $this->assigner = explode(',', $this->assigner);
            $Assginer       = array_merge($this->assigner, $Assginer);
            $Assginer       = array_unique($Assginer);

            foreach ($Assginer as $key => $value) {
                if($value != $this->user_id){
                    $this->Logs[] = [
                        'task_id'     => $TaskId,
                        'task_name'   => $Task->name,
                        'type'        => 'assgin',
                        'user_id'     => (int)$value,
                        'assginer_id' => $this->user_id,
                        'time_create' => $this->time()
                    ];
                }
            }
        }



        
        if(!$AssignModel->InsertAssigner($TaskId, $this->user_id, $Assginer)){
            $this->_error           = true;
            $this->_error_message   = "Lỗi kết nối dữ liệu";
            goto done;
        };

        if(!empty($this->task_refer) && gettype($this->task_refer) == 'array'){
            $this->AddTaskRefer($TaskId, $this->task_refer);
            if($this->_error) goto done;
        }

        // Insert log
        $this->InsertLog();

    	// TODO: do something here 

        $this->_error_message   = "Tạo thành công";
        $Result                 = $TaskId;

    	done:
    	return $this->_ResponseData($Task);
    }


    private function InsertLog (){
        if(!empty($this->Logs)){
            try {
                $Insert = LMongo::collection('log_task_change')->batchInsert($this->Logs);
            } catch (Exception $e) {
                $this->_error = true;
                $this->_error_message = "ERROR INSERT LOGS";
            }
        }
    }
    public function postCreateComment($TaskId){
        $Result       = [];
        $CommentModel = new TasksCommentModel;
        $Content      = Input::has('content')   ? Input::get('content')    : "";

        if(empty($Content)){
            $this->_error         = true;
            $this->_error_message = "Nội dung không được để trống.";
            goto done;
        }

        $Result = $CommentModel->Add($TaskId, $this->user_id, $Content);

        if(!$Result){
            $this->_error         = true;
            $this->_error_message = "Lỗi truy vấn máy chủ !";
            goto done;
        }

        done: 
        return $this->_ResponseData($Result);
    }

    public function postCreateCategory(){
        $Color      = Input::has('color')   ? Input::get('color')    : "";
        $Model      = new TasksCategoryModel;
        $Result     = [];

        $Model->user_id         = $this->user_id;
        $Model->color           = $Color;
        $Model->time_create     = $this->time();
        $Model->name            = $this->name;

        try {
            $Result                 = $Model->save();
        } catch (Exception $e) {
            $this->_error  = true;
            $this->_error_message = "Lỗi câu truy vấn";
        }
        
        return $this->_ResponseData($Result);

    }


    
    public function getSuggest(){
        $Type    = Input::has('type')   ? Input::get('type')    : 1;
        $Keyword = Input::has('q')      ? Input::get('q')       : "";

        $Data    = [];

        if (empty($Keyword)) {
            $this->_error = true;
            $this->_error_message = "Từ khóa không được để trống";
            goto done;
        }
        
        switch ($Type) {
            case 1:
                $Data = $this->suggestOrder($Keyword);
            break;
            case 2:
                $Data = $this->suggestInventory($Keyword);
            break;
            case 3:
                $Data = $this->suggestCustomer($Keyword);
            break;
            default:
                $Data = $this->suggestCustomer($Keyword);
                break;
        }

        done:
        return $this->_ResponseData($Data);
    }

    private function suggestOrder($Keyword){
        $Data   = [];
        $Model  = new OrdersModel;
        $Model  = $Model->where('tracking_code', $Keyword)->where(function ($query){
            $query->where('time_accept','>=', $this->time() - 30 * 86400)->orWhere('time_accept', 0);
        })->first();

        if(!empty($Model)){
            $Data[] = [
                'id'    => $Model->id,
                'name'  => $Model->tracking_code
            ];
        }
        return $Data;
    }

    private function suggestCustomer($Keyword){
        $Data  = [];
        $Model = new User;
        $Model = $Model->where('email', $Keyword)->first();

        if(!empty($Model)){
            $Data[] = [
                'id'    => $Model->id,
                'name'  => $Model->fullname . ' - ' . $Model->phone
            ];
        }
        return $Data;
    }

    private function suggestInventory($Keyword){
        $Data  = [];
        $Model = new UserInventoryModel;
        $Model = $Model->where('id', $Keyword)->first();

        if(!empty($Model)){
            $Data[] = [
                'id' => $Model->id,
                'name' => $Model->name . ' - ' . $Model->user_name . ' - ' . $Model->address
            ];
        }
        return $Data;
    }

    private function AddTask(){

        $this->model->name         = $this->name;
        $this->model->user_id      = $this->user_id;
        $this->model->state        = !empty($this->state) ? $this->state : self::NOT_STARTED; //NOT_STARTED
        $this->model->category_id  = $this->category;
        $this->model->due_date     = $this->due_date;
        $this->model->reminder     = $this->reminder;
        $this->model->description  = $this->description;
        $this->model->time_create  = $this->time();

        try {
            $result = $this->model->save();
        } catch (Exception $e) {
            $this->_error = true;
            $this->_error_message = "Lỗi truy vấn máy chủ, vui lòng thử lại";
        }
        return $this->model;
    }

    private function AddTaskRefer ($taskId, $ArrRefer){
        $data = [];
        foreach ($ArrRefer as $key => $value) {
            $data[]  = [
                'task_id'   => $taskId,
                'refer_id'  => $value['refer_id'],
                'type'      => $value['type'],
                'name'      => !empty($value['name']) ? $value['name'] : "",
            ];
        }
        if(!empty($data)){
            try {
                TasksReferModel::insert($data);
            } catch (Exception $e) {
                $this->_error = true;
                $this->_error_message = $e->getMessage();
            }
        }

    }


}
?>