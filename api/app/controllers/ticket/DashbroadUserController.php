<?php
namespace ticket;

use Response;
use Exception;
use Input;
use Validator;
use Excel;
use DB;
use LMongo;

use ordermodel\OrdersModel;
use sellermodel\UserInfoModel;
use ticketmodel\TicketReminderModel;

use User;
use ticketmodel\RequestModel;
use ticketmodel\AssignModel;


class DashbroadUserController extends \BaseController
{

    private $user;
    private $user_id;
    private $model;


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
        $this->model    = new RequestModel;
        $this->user     = $this->UserInfo();
        $this->user_id  = Input::has('user_id')             ? (int)Input::get('user_id') : (int)$this->user['id'];


        // Ticket Filtering

         $Priority   = Input::has('priority')    ? (int)Input::get('priority')               : 0;



        $this->time_start   = Input::has('time_start')   ? (int)Input::get('time_start')     : $this->time() - 7 * 86400;
        $this->time_end     = Input::has('time_end')     ? (int)Input::get('time_end')       : $this->time();
        $this->page         = Input::has('page')         ? (int)Input::get('page')           : 1;
        $this->limit        = Input::has('limit')        ? (int)Input::get('limit')          : 20;
        $this->filter       = Input::has('filter')       ? Input::get('filter')          : "";
    }


    public function getModel (){
        $Model              = new RequestModel; 
        $ListTicketAssign   = AssignModel::where('assign_id', $this->user_id)->where('time_create','>=',$this->time_start)->where('active',1)->lists('ticket_id');

        if(!empty($ListTicketAssign)){
            $Model = $Model->whereIn('id', $ListTicketAssign);
        }else {
            $this->_error           = true;
            $this->_error_message   = "Không có yêu cầu nào !";
            return false;
        }


        if(!empty($this->filter)){
            switch ($this->filter) {
                case 'overdue':
                        $Model  = $Model->where('time_over','>',0)->where(function($query){
                            $query->where(function($q){
                                $q->where('status', 'CLOSED')
                                   ->whereRaw('time_over < time_update');
                            })->orWhere(function($q) {
                                $q->where('status', '<>', 'CLOSED')
                                    ->where('time_over', '<=', $this->time());
                            });
                        });

                    break;
                case 'overing_due':
                        $Model = $Model->where('time_over','>=', $this->time())->where('time_over','<=', $this->time() - 86400);
                    break;
                case 'priority':
                        $Model  = $Model->whereIn('priority',[2, 3]);
                    break;
            }
        }

        $Model          = $Model->where('time_create','>=',$this->time_start)->where('time_create', '<=', $this->time_end)->where('status', '!=', 'CLOSED');


        return $Model;
    }   

    public function getTicket (){
        $Data       = [];
        $Total      = 0;


        
        $Model      = $this->getModel();

        if($this->_error) goto done;

        $Total = clone $Model;
        $Total = $Total->count();

        $Data  = $Model->orderBy('time_update', 'DESC')->take(15)->get()->toArray();

        done:
        return $this->_ResponseData($Data, [
            'total' => $Total
        ]);
    }   


    public function getReminder(){
        $Model  = new TicketReminderModel;
        $Model  = $Model->where('time_create', '>=',$this->time_start)
                        ->where('time_create', '<', $this->time_end)
                        ->where('user_id', $this->user_id)
                        ->orderBy('time_create', 'DESC');


        $TotalWaiting      = 0;
        $TotalWaitingModel = clone $Model;
        $TotalWaitingModel = $TotalWaitingModel->where('time_reminder', '>=', $this->time())->count();


        if(!empty($this->filter) && $this->filter == 'reminded'){
            $Model = $Model->where('time_reminder', '<=', $this->time());
        }

        if(!empty($this->filter) && $this->filter == 'waiting'){
            $Model = $Model->where('time_reminder', '>', $this->time());
        }

        $Total           = (clone $Model);
        $Total           = $Total->count();

        $RecentReminder  = $Model->take(15)->get()->toArray();

        return $this->_ResponseData($RecentReminder, [
            'total'         => $Total,
            'total_waiting' => $TotalWaitingModel
        ]);
        
    }



}
