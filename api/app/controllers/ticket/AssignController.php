<?php namespace ticket;

use Validator;
use Response;
use Input;
use Exception;
use DB;
use User;
use ticketmodel\AssignModel;
use ticketmodel\RequestModel;
use ticketmodel\AssignGroupModel;
use ticketmodel\TicketGroupModel;

use \TicketNotificationController;
class AssignController extends \BaseController {
    private $data_new   = array();
    private $data_old   = array();
    private $field      = '';

    private function SetData($type, $data_old, $data_new){
        $this->data_old[$type]     = $data_old;
        $this->data_new[$type]      = $data_new;
    }

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate()
	{
	    $UserInfo   = $this->UserInfo();

        /**
        * Get Data
        * */
        $Data                 = Input::json()->all();
        $TicketId             = (int)$Data['ticket_id'];
        $AssignId             = (isset($Data['assign_id'])) ? (int)$Data['assign_id'] : '';
        $Active               = isset($Data['active']) ? (int)$Data['active'] : null;
        $UserId               = (int)$UserInfo['id'];
        $Notification         = isset($Data['notification'])    ? (int)$Data['notification'] : null;
        $type                = isset($Data['value']) ? $Data['value'] : '';
		/**
        *  Validation params
        * */

        //error
        Validator::getPresenceVerifier()->setConnection('ticketdb');
        $validation = Validator::make(Input::json()->all(), array(
            'ticket_id'        => 'required'
        ));

        $TicketModel    = new RequestModel;
        $Ticket = $TicketModel->where('id',$TicketId)->first();
        if(empty($Ticket)){
            $contents = array(
                'error'     => true,
                'message'   => 'NOT_EXISTS'
            );
            return Response::json($contents);
        }

        if($UserInfo['privilege'] != 2 && $Ticket->status == 'CLOSED'){
            $contents = array(
                'error'     => true,
                'message'   => 'USER_NOT_ALLOWED'
            );
            return Response::json($contents);
        }

        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()), 200);
        }

        $ListId             = [];
        $ListInsert         = [];
        $InsertLog          = [];

        if(!isset($Active) || !$this->check_privilege('PRIVILEGE_TICKET','del')){
            $Active = 1;
        }

        if(!isset($Notification)){
            $Notification   = 0;
        }

        if(empty($AssignId) && !empty($type['id'])) {
            $listAssignID = AssignGroupModel::where('group_id', $type['id'])->get()->toArray();
            if (!empty($listAssignID)) {
                foreach ($listAssignID as $oneAssignID) {
                    $ListId[] = (int)$oneAssignID['assign_id'];
                    $ListInsert[(int)$oneAssignID['assign_id']] = [
                        'ticket_id' => $TicketId,
                        'assign_id' => (int)$oneAssignID['assign_id'],
                        'active' => $Active,
                        'user_id' => $UserId,
                        'time_create' => $this->time(),
                        'notification' => $Notification
                    ];

                    $InsertLog[(int)$oneAssignID['assign_id']] = [
                        'id' => (int)$TicketId,
                        'new' => [
                            'active'    => $Active,
                            'assign_id' => (int)$oneAssignID['assign_id']
                        ],
                        'old' => [
                            'active'    => ($Active == 1) ? 0 : 1,
                            'assign_id' => (int)$oneAssignID['assign_id']
                        ],
                        'time_create' => $this->time(),
                        'user_id' => $UserId,
                        'type' => 'assign'
                    ];
                }
            }
        }else{
            $ListId[]   = $AssignId;
            $ListInsert[(int)$AssignId] = [
                'ticket_id'     => $TicketId,
                'assign_id'     => (int)$AssignId,
                'active'        => $Active,
                'user_id'       => $UserId,
                'time_create'   => $this->time(),
                'notification'  => $Notification
            ];

            $InsertLog[] = [
                'id' => (int)$TicketId,
                'new' => [
                    'active'    => $Active,
                    'assign_id' => (int)$AssignId
                ],
                'old' => [
                    'active'    => ($Active == 1) ? 0 : 1,
                    'assign_id' => (int)$AssignId
                ],
                'time_create'   => $this->time(),
                'user_id'       => $UserId,
                'type'          => 'assign'
            ];
        }

        // Danh sách các nhân viên đã được giao
        $ListAssignExists = AssignModel::where('ticket_id',$TicketId)->whereIn('assign_id', $ListId)->get()->toArray();
        $ListIdUpdate = [];
        if(!empty($ListAssignExists)){
            foreach($ListAssignExists as $val){
                if(isset($ListInsert[(int)$val['assign_id']])){
                    unset($ListInsert[(int)$val['assign_id']]);
                }

                $ListIdUpdate[] = (int)$val['id'];
                /*if($val['active'] != $Active){
                    $ListIdUpdate[] = (int)$val['id'];
                }*/

                if(!empty($type['id']) && $val['active'] == 1){
                    unset($InsertLog[(int)$val['assign_id']]);
                }
            }
        }

        //Action
        DB::connection('ticketdb')->beginTransaction();
        if(!empty($ListIdUpdate)){
            try{
                AssignModel::whereIn('id', $ListIdUpdate)->update(['active' => $Active]);
            }catch (Exception $e){
                $contents = array(
                    'error'     => true,
                    'message'   => 'UPDATE_ASSIGN_FAIL'
                );
                return Response::json($contents);
            }
        }

        if(!empty($ListInsert)){
            try{
                AssignModel::insert($ListInsert);
            }catch (Exception $e){
                $contents = array(
                    'error'     => true,
                    'message'   => 'INSERT_FAIL'
                );
                return Response::json($contents);
            }
        }

        if($Ticket->status == 'NEW_ISSUE'){
            $Ticket->status = 'ASSIGNED';
            $InsertLog[]    = [
                'id' => (int)$TicketId,
                'new' => [
                    'status'    => 'ASSIGNED',
                ],
                'old' => [
                    'status'    => 'NEW_ISSUE',
                ],
                'time_create'   => $this->time(),
                'user_id'       => $UserId,
                'type'          => 'status'
            ];
        }

        try{
            $Ticket->time_update        = $this->time();
            if($Ticket->time_receive == 0){
                $Ticket->time_receive	= $this->time();
            }
            if(!Input::has('data.content')){
                $Ticket->user_last_action   = $UserId;
            }
            $Ticket->save();
        }catch (Exception $e){
            $contents = array(
                'error'     => true,
                'message'   => 'UPDATE_TICKET_FAIL'
            );
            return Response::json($contents);
        }

        $RequestController  = new RequestController;
        if($RequestController->InsertMultiLog($InsertLog)){
            DB::connection('ticketdb')->commit();
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'UPDATE_LOG_FAIL'
            );
            return Response::json($contents);
        }

        $contents = array(
            'error'     => false,
            'message'   => 'SUCCESS',
            'list_id'   => $ListId
        );
        return Response::json($contents);
	}

    public function getGroup() {
        $group = new TicketGroupModel;
        $group = $group->with(['user_assign'])->get();
        if(!$group->isEmpty()) {
            $response = [
                'status'    =>  true,
                'data'      =>  $group
            ];
        } else {
            $response = [
                'status'    =>  false,
                'message'   =>  'Không có dữ liệu'
            ];
        }
        return Response::json($response);
    }

}
