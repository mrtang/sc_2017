<?php

class SmsController extends \BaseController {
    private $domain = '*';

    public function __construct(){
        
    }
    //
    public function postSendsms($json = true){
    	$LMongo         = new LMongo;

        $Data           = $json ? Input::json()->all() : Input::all();
        $Phone          = isset($Data['to_phone'])         ? $Data['to_phone']            : null;
        $Content        = isset($Data['content'])        ? $Data['content']      : null;
        $Priority       = isset($Data['priority'])        ? $Data['priority']      : 0;

        if(empty($Phone) || empty($Content)){
            $contents = array(
                'error' => true, 'message' => 'Push data!!'
            );
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }

    	$Insert = $LMongo::collection('log_send_sms')
                                    ->insert(array(
                                                'telco'         => Smslib::CheckPhone($Phone),
                                                'to_phone'      => $Phone,
                                                'status'        => 0,
                                                'content'       => $Content,
                                                'time_create'   => $this->time(),
                                                'time_send'     => 0,
                                                'priority'      => $Priority
                                            ));
        if($Insert){
        	$contents = array(
	            'error'     => false,
	            'message'   => 'success',
	        );

			return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }else{
        	$contents = array(
	            'error'     => true,
	            'message'   => 'Not send sms!!',
	        );

			return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }
}
?>