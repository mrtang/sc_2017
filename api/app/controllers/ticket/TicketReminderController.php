<?php
namespace ticket;
use ordermodel\OrdersModel;
use ticketmodel\TicketReminderModel;
use sellermodel\UserInventoryModel;
use User;
use QueueModel;
use Input;
use Response;
use DB;
use Lang;

class TicketReminderController extends \BaseController
{


    private $user;
    private $user_id;
    private $model;

    public $id;
	public $name;
	public $time_reminder;


    // for list
    public $keyword;
    public $time_start;
    public $time_end;

    public $page;
    public $limit;
    public $offset;
    public $total = 0;



    public function __construct()
    {
    	$this->model 	= new TicketReminderModel;
    	$this->user 	= $this->UserInfo();
    	$this->user_id	= (int)$this->user['id'];

        $this->id              = Input::has('id')             ? (int)Input::get('id')           : 0;

        $this->ticket_id       = Input::has('ticket_id')      ? Input::get('ticket_id')         : "";
    	$this->name 	       = Input::has('name') 	      ? Input::get('name') 		        : "";
        $this->time_reminder   = Input::has('time_reminder')  ? (int)Input::get('time_reminder')    : "";

        // For list
        $this->keyword      = Input::has('keyword')      ? Input::get('keyword')             : "";
        $this->time_start   = Input::has('time_start')   ? (int)Input::get('time_start')     : $this->time() - 7 * 86400;
        $this->time_end     = Input::has('time_end')     ? (int)Input::get('time_end')       : $this->time();
        $this->page         = Input::has('page')         ? (int)Input::get('page')           : 1;
        $this->limit        = Input::has('limit')        ? (int)Input::get('limit')          : 20;
        
        $this->offset       = ($this->page - 1) * $this->limit;
    } 

    private function getModel(){
        $Model  = new TicketReminderModel;
        $Model  = $Model->where('time_create', '>=',$this->time_start)
                        ->where('time_create', '<', $this->time_end);

        if (!empty($this->name)) {
            $Model = $Model->where('name', 'LIKE', '%'.$this->name.'%');
        }


        if(!empty($this->id)){
            $Model = $Model->where('id', (int)$this->id);   
        }
        
        if($this->user_id > 0){
            $Model       = $Model->where('user_id', $this->user_id);
        }
        return $Model;
    }



    public function getIndex (){
        $Result      = [];
        $Model       = $this->getModel();

        $Total       = clone $Model;
        $this->total = $Total->count();

        if ($this->total == 0) {
            goto done;
        }

        $Result = $Model->skip($this->offset)->take($this->limit)->orderBy('time_create', 'DESC')->get();


        done:
        return $this->_ResponseData($Result, [
            'page'      => $this->page,
            'item_page' => $this->limit,
            'total'     => $this->total
        ]);
    }


    public function getCountState(){
        $Model  = $this->getModel();
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
        return $this->_ResponseData($Data);
    }

    private function AddReminder(){
	
		$this->model->name            = $this->name;
		$this->model->ticket_id	      = $this->ticket_id;
        $this->model->user_id         = $this->user_id;
		$this->model->time_reminder   = $this->time_reminder;
		$this->model->time_create     = $this->time();
    	

		$Result = [];
    	try {
    		$Result = $this->model->save();
    	} catch (Exception $e) {
    		$this->_error = true;
    		$this->_error_message = Lang::get('response.FAIL_QUERY');
		}
    	return $this->model;
    }

    public function postAddReminder(){
        $Reminder = [];

    	if(empty($this->name)){
    		$this->_error         = true;
    		$this->_error_message = Lang::get('response.DESCRIPTION_NOT_EMPTY');
    		goto done;
    	}

        if(empty($this->time_reminder)){
            $this->_error         = true;
            $this->_error_message = Lang::get('response.PLEASE_SELECT_TIME_REDMINDER');
            goto done;
        }

    	$Reminder = $this->AddReminder();

    	if($this->_error) goto done;
        
    	$this->sendJob("TaskReminderCreate", $Reminder);


        $this->_error_message = Lang::get('response.SUCCESS');
    	done:
    	return $this->_ResponseData($Reminder);
    }

    private function sendJob($job_name, $data){
        \Predis\Autoloader::register();
        //Now we can start creating a redis client to publish event
        try{
            //Now we got redis client connected, we can publish event (send event)
            $redis = new \Predis\Client(array(
                "scheme" => "tcp",
                "host"   => "10.0.20.164",
                "port"   => 6788
            ));

            $redis->publish($job_name, json_encode($data));
        }catch (Exception $e){

        }
    }

    public function getSendNotify($reminder_id){
        $ReturnData     = [];

        $this->id       = $reminder_id;
        $this->user_id  = 0;
        $Reminder       = $this->getModel()->where('notification', 0)->first();

        if(empty($Reminder)){
            $this->_error         = true;
            $this->_error_message = $Reminder; 
            goto done;
        }

        $User = User::where('id', $Reminder->user_id)->first();

        if(empty($User)){
            $this->_error         = true;
            $this->_error_message = Lang::get('response.USER_NOT_FOUND');
            goto done;
        }

        $DataBuild = [
            'ticket_id'     => $Reminder->ticket_id,
            'reminder_id'   => $Reminder->id,
            'content'       => $Reminder->name
        ];

        $dataQueue = array(
            'scenario_id'  => 28,
            'template_id'  => 33,
            'transport_id' => 6,
            'user_id'      => $Reminder->user_id,
            'received'     => $User->email,
            'data'         => json_encode($DataBuild),
            'time_create'  => $this->time(),
            'status'       => 1,
            'time_success' => $this->time()
        );
        try {
            $ReturnData = QueueModel::insertGetId($dataQueue);
        } catch (Exception $e) {
            $this->_error         = true;
            $this->_error_message = Lang::get('response.INSERT_FAIL');
            goto done;
        }

        $Reminder->notification = 1;
        $Reminder->save();
        

        done: 
        return $this->_ResponseData($ReturnData);

    }
    
    


}
?>