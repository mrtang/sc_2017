<?php namespace mobile_ticket;

use Exception;
use Validator;
use Response;
use Input;
use ticketmodel\FeedbackModel;
use mobile_ticket\RequestController;
use ticketmodel\RequestModel;
use ticketmodel\AssignModel;
use ticketmodel\LogViewModel;
use sellermodel\UserInfoModel;
use Cache;
use Session;
use ticketmodel\ReferModel;

class FeedbackController extends \BaseController {
    
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getByticket($TicketId)
	{
        $Model  = new FeedbackModel();
        $ListData   = $Model->where('time_create','>',time() - 86400*90)
                            ->where('ticket_id',$TicketId)
                            ->orderBy('time_create','ASC')
                            ->with(array('attach' => function($query){
                                $query->where('type','=',1)
                                    ->get(array('refer_id','link_tmp','name'))
                                    ->toArray();
                            }))
                            ->get()->toArray();
        
        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $ListData
        );
        return Response::json($contents);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate($id)
	{   
	    $UserInfo   = $this->UserInfo();

		/**
        *  Validation params
        * */
        
        $validation = Validator::make(Input::all(), array(
            'content'       => 'required',
            //'source'        => 'required|in:web,email,sms'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }
        
        Validator::getPresenceVerifier()->setConnection('ticketdb');
        $validation = Validator::make(array('ticket_id' => $id), array(
            'ticket_id'        => 'required|numeric'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }
        
        /**
         * Get Data 
         * */
        $Data               = Input::all();
        $Content            = Input::get('content');
        $Status             = isset($Data['status']) ? $Data['status'] : null;
        $UserId             = (int)$UserInfo['id'];
        $Source             = isset($Data['source']) ? strtolower($Data['source']) : 'web';
        $Contact            = isset($Data['contact']) ? $Data['contact'] : '';

        $TicketModel    = new RequestModel;
        $Ticket = $TicketModel->where('id',$id)->first();
        if(empty($Ticket)){
            $contents = array(
                'error'     => true,
                'message'   => 'TICKET_NOT_EXISTS'
            );
            return Response::json($contents);
        }

        if(preg_match_all("/(@)[0-9]{1,}/", $Content, $output)) {
            $listTicketRefer = [];
            if(!empty($output[0])) {
                $ticketReferCode = ReferModel::where('ticket_id',$id)->where('type',3)->lists('code');
                $k = 0;
                foreach($output[0] as $ticketID) {
                    if(!in_array($ticketID,$ticketReferCode)) {
                        $code = substr($ticketID,1);
                        $checkExists = ReferModel::where('ticket_id',$code)->where('type',3)->where('code',$id)->first();
                        if(empty($checkExists)) {
                            $listTicketRefer[$k]['ticket_id'] = $code;
                            $listTicketRefer[$k]['type'] = 3;
                            $listTicketRefer[$k]['code'] = $id;
                            ++$k;
                        }
                    }
                }
                if(!empty($listTicketRefer)) {
                    ReferModel::insert($listTicketRefer);
                }
            }

        }

        $Model              = new FeedbackModel;
        $DataInsert         = ['ticket_id' => $id, 'user_id' => $UserId, 'content' => $Content,'contact' => $Contact, 'source' => $Source, 'time_create' => time()];
        if($Source == 'note'){
            $DataInsert['notification'] = 1;
        }

        if($Ticket->time_reply==0) {
            $Ticket->time_reply = time() -  $Ticket->time_create;
        }

        $Insert             = $Model::insertGetId($DataInsert);
        
        if($Insert){

            // Insert Log View
            $this->InsertLogView($id, (int)$UserId);
            $LogOld = ['status'    => $Ticket->status];

            // Update Ticket Request
            if(!empty($Status)){
                $Ticket->status = $Status;
            }
            if($Ticket->user_id == $UserId && ($Ticket->status == 'PENDING_FOR_CUSTOMER' || $Ticket->status == 'PROCESSED')) {
                $Ticket->status = 'CUSTOMER_REPLY';
            } else if($Ticket->user_id != $UserId && $Ticket->status == 'CUSTOMER_REPLY' && $Source != 'note') {
                $Ticket->status = 'PENDING_FOR_CUSTOMER';
            }
            try{
                $Ticket->time_update        = time();
                $Ticket->user_last_action   = (int)$UserId;
                $Ticket->save();

                if(!empty($Status)){
                    $RequestController  = new RequestController;
                    $RequestController->InsertLog($id, $LogOld, ['status' => $Status], 'status');
                }

                $contents = array(
                    'error'     => false,
                    'message'   => 'success',
                    'data'      => (object)[
                        "id"        => $Insert,
                        "ticket_id" => $id,
                        "user_id"   => (int)$UserId,
                        "source"    => "web",
                        "contact"   => "",
                        "content"   => $Content,
                        "time_create"   => time(),
                        "notification"  => 1,
                        "fullname"  => isset($UserInfo['fullname']) ? $UserInfo['fullname'] : "",
                        "avt"       => "http://www.gravatar.com/avatar/".$UserInfo['email']."?s=80&d=mm&r=g",
                        "attach"    => []
                    ],
                    'id'        => $Insert,
                    'user_id'   => $UserId
                );
            }catch (Exception $e){
                $contents = array(
                    'error'     => true,
                    'message'   => 'UPDATE_TICKET_FAIL',
                    'data'      => (object)[]
                );
            }

            

        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'insert false',
                'data'      => (object)[]
            );
        }
        
        return Response::json($contents);
	}

    public function ScenarioTime($time){

        if($time > 0){
            $hours   = floor($time/60);

            if($hours > 518400){
                $str   = floor($hours/518400).' năm';
            }
            elseif($hours > 43200){ // 30 ngày
                $str   = floor($hours/43200).' tháng';
            }elseif($hours > 1440){ // 1 ngày
                $str   = floor($hours/1440).' ngày';
            }elseif($hours > 60){// 1 hours
                $str   = floor($hours/60).' giờ';
            }elseif($hours > 0){
                $str   = $hours.' phút';
            }else{
                $str   = '1 phút';
            }

        }
        return $str;
    }

    public function InsertLogView($TicketId, $UserId){
        if($TicketId > 0 && $UserId > 0){
            $LogViewModel   = new LogViewModel;

            try{
                $LogViewModel->where('ticket_id',$TicketId)->where('user_id','<>',$UserId)->update(['view' => 0]);
            }catch (Exception $e){

            }
        }
        return false;
    }

}
