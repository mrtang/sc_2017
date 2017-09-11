<?php
use ticketmodel\RequestModel;
use ticketmodel\FeedbackModel;
use ticketmodel\AssignModel;
use Michelf\Markdown;
class TicketNotificationController extends \BaseController {
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 * @params $scenario, $user_id
	 */
     
    public function __construct(){
        
    }

    //
    private function getContent($scenario_id){
    	$listLayout = ScenarioTemplateModel::where('scenario_id','=',$scenario_id)->get(array('template_id'))->toArray();
    	$dataReturn = array();
    	if(!empty($listLayout)){
    		foreach($listLayout AS $val){
    			$dataReturn[] = $val['template_id'];
    		}
    	}
    	return $dataReturn;
    }

    //
    private function getUserinfo($list_user_id){
    	$dataUser = array();
    	if(!empty($list_user_id)){
    		$dataUser = User::whereIn('id',$list_user_id)->get(array('id','fullname'))->toArray();
    	}
    	return $dataUser;
    }

    //
    private function getListusernotify($status){
    	$dataReturn = array();
    	$listUser = RequestModel::groupBy('user_id')->get(array('id','user_id'))->toArray();
    	if(!empty($listUser)){
    		$listUserId = array();
    		foreach($listUser AS $one){
    			$listUserId[] = (int)$one['user_id'];
    		}

    		$listInfoTicket = RequestModel::whereIn('user_id',$listUserId)->where('status','=',$status)->where('notification',0)->orderBy('user_id','DESC')->get(array('id','user_id','title','content','status'))->toArray();
    		if(!empty($listInfoTicket)){
    			$dataReturn = $listInfoTicket;
    		}
    	}
    	return $dataReturn;
    }

    //
    private function getUserlist(){
    	$listUser = RequestModel::groupBy('user_id')->get(array('id','user_id'))->toArray();
    	$listUserId = array();
    	if(!empty($listUser)){
    		foreach($listUser AS $one){
    			$listUserId[] = (int)$one['user_id'];
    		}
    	}
    	return $listUserId;
    }

    //
    private function getTicket($status){
    	$dataTicket = array();
    	if(!empty($status)){
    		$listInfoTicket = RequestModel::whereIn('status',$status)->where('notification',0)->get(array('id','user_id','title','content','status'))->first();
    		if(!empty($listInfoTicket)){
    			$dataTicket = $listInfoTicket;
    		}
    	}
    	return $dataTicket;
    }

    //get ticket by list ID
    private function getTicketById($ticketId){
    	$infoTicket = array();
    	if(!empty($ticketId)){
    		$infoTicket = RequestModel::where('id',$ticketId)->first();
    	}
    	return $infoTicket;
    }

    private function getFeedback($source = array('sms','web','note')){
    	$output = array();
		$dataFeedback = FeedbackModel::where('notification',0)->whereIn('source',$source)->first();
		if(!empty($dataFeedback)){
			$output = $dataFeedback;
		}
		return $output;
    }
    //get config transport
    private function getUserconfigtransport($user){
    	$output = array();
    	if(!empty($user)){
    		$userConfig = UserConfigTransportModel::where('user_id',$user)->where('transport_id',2)->first();
    		if(!empty($userConfig)){
    			$userInfo = User::where('id',$user)->first();
    			$output = array(
    				'email' => $userConfig['received'],
    				'fullname' => $userInfo['fullname']
    			);
    		}else{
    			$userInfo = User::where('id',$user)->first();
    			$output = array(
    				'email' => $userInfo['email'],
    				'fullname' => $userInfo['fullname']
    			);
    		}
    	}
    	return $output;
    }

    // hanh dong
    public function getSendnotifywhencreateticket(){
    	$scenario_id = 7;
    	$status = array('NEW_ISSUE','ASSIGNED');

    	$dataTicket = $this->getTicket($status);
    	if(!empty($dataTicket)){
    		//
    		$template = $this->getContent($scenario_id);
    		if(empty($template)){
    			$contents = array(
		            'error'         => true,
		            'message'       => 'Not template!',
		        );
    		}

    		$infoUser = $this->getUserconfigtransport($dataTicket['user_id']);

    		if(!empty($infoUser)){
    			$dataInsert = array(
					'scenario_id' => $scenario_id,
	    			'template_id' => $template[0],
	    			'transport_id' => 2,
	    			'user_id'	=> $dataTicket['user_id'],
	    			'received'	=> $infoUser['email'],
	    			'data'		=> json_encode(
	    				array('id' => $dataTicket['id'],'title' => $dataTicket['title'],'content' => Markdown::defaultTransform($dataTicket['content']),'status' => $dataTicket['status'],'fullname' => $infoUser['fullname'])
	    			),
	    			'time_create' => $this->time()
				);
    		}

    		if(!empty($dataInsert)){
	    		$insert = QueueModel::insertGetId($dataInsert);
		    	if($insert > 0){
                    //goi api send
                    \Predis\Autoloader::register();
                    //Now we can start creating a redis client to publish event
                    $redis = new \Predis\Client(array(
                        "scheme" => "tcp",
                        "host" => "192.168.100.85",
                        "port" => 8899
                    ));
                    //Now we got redis client connected, we can publish event (send event)
                    $redis->publish("SendMail", $insert);

		    		//update trang thai vao bang ticket khi da gui notify
		    		$update = RequestModel::where('id',$dataTicket['id'])->update(array('notification' => 1));
			        $contents = array(
			            'error'         => false,
			            'message'       => 'Success',
			        );
		    	}else{
			        $contents = array(
			            'error'         => true,
			            'message'       => 'Not insert!',
			        );
		    	}
		    }else{
		        $contents = array(
		            'error'         => true,
		            'message'       => 'Not data!!!',
		        );
		    }
    	}else{
    		$contents = array(
		            'error'         => true,
		            'message'       => 'Not data send notification!',
		        );
    	}
	    
    	return Response::json($contents);
    }

    public function getSendnotifywhensuccessticket(){
    	$scenario_id = 9;
    	$status = array('CLOSED');
    	$dataInsert = array();

    	$dataTicket = $this->getTicket($status);
    	if(!empty($dataTicket)){
    		//
    		$template = $this->getContent($scenario_id);
    		if(empty($template)){
    			$contents = array(
		            'error'         => true,
		            'message'       => 'Not template!',
		        );
    		}

    		$infoUser = $this->getUserconfigtransport($dataTicket['user_id']);
    		if(!empty($infoUser)){
    			$dataInsert[] = array(
					'scenario_id' => $scenario_id,
	    			'template_id' => $template[0],
	    			'transport_id' => 2,
	    			'user_id'	=> $dataTicket['user_id'],
	    			'received'	=> $infoUser['email'],
	    			'data'		=> json_encode(
	    				array('id' => $dataTicket['id'],'title' => $dataTicket['title'],'content' => Markdown::defaultTransform($dataTicket['content']),'status' => $dataTicket['status'],'fullname' => $infoUser['fullname'])
	    			),
	    			'time_create' => $this->time()
				);
    		}
    		
    		if(!empty($dataInsert)){
	    		$insert = QueueModel::insert($dataInsert);
		    	if($insert == true){
		    		//update trang thai vao bang ticket khi da gui notify
		    		$update = RequestModel::where('id',$dataTicket['id'])->update(array('notification' => 1));
			        $contents = array(
			            'error'         => false,
			            'message'       => 'Success',
			        );
		    	}else{
			        $contents = array(
			            'error'         => true,
			            'message'       => 'Not insert!',
			        );
		    	}
		    }else{
		        $contents = array(
		            'error'         => true,
		            'message'       => 'Not data!!!',
		        );
		    }
    	}else{
    		$contents = array(
		            'error'         => true,
		            'message'       => 'Not data send notification!',
		        );
    	}
		
    	return Response::json($contents);
    }
    //send notify when reply ticket
    public function getSendnotifywhenreplyticket(){
    	$scenario_id = 8;
    	//
    	$dataBuild = array();
    	$dataFeedback = $this->getFeedback();
    	if(!empty($dataFeedback)){
    		//lay ra ticket co feedback
    		$dataTicketFeedback = $this->getTicketById($dataFeedback['ticket_id']);
    		if(!empty($dataTicketFeedback)){
    			$infoUser = $this->getUserconfigtransport($dataFeedback['user_id']);
    			$dataBuild = array(
    				'title_ticket' => $dataTicketFeedback['title'],
    				'content_ticket' => $dataTicketFeedback['content'],
    				'ticket_time'	=> $dataTicketFeedback['time_create'],
    				'content_feed' => $dataFeedback['content'],
    				'feed_time'	=> $dataFeedback['time_create'],
    				'ticket_id'	=> $dataTicketFeedback['id'],
    				'email'		=> $infoUser[0]['email'],
    				'feedback_name' => $infoUser[0]['fullname']
    			);
    				    	
	    		$dataInsert = array(
					'scenario_id' => $scenario_id,
	    			'template_id' => 13,
	    			'transport_id' => 2,
	    			'user_id'	=> $dataTicketFeedback['user_id'],
	    			'received'	=> $infoUser[0]['email'],
	    			'data'		=> json_encode($dataBuild),
	    			'time_create' => $this->time()
				);

				$insert = QueueModel::insertGetId($dataInsert);
		    	if($insert > 0){
                    //goi api send
                    \Predis\Autoloader::register();
                    //Now we can start creating a redis client to publish event
                    $redis = new \Predis\Client(array(
                        "scheme" => "tcp",
                        "host" => "10.0.20.164",
                        "port" => 6788
                    ));
                    //Now we got redis client connected, we can publish event (send event)
                    $redis->publish("SendMail", $insert);
		    		//update notification 
		    		$update = FeedbackModel::where('id',$dataFeedback['id'])->update(array('notification' => 1));
			        $contents = array(
			            'error'         => false,
			            'message'       => 'Success',
			        );
		    	}else{
			        $contents = array(
			            'error'         => true,
			            'message'       => 'Not send notification',
			        );
		    	}
	    	}else{
		        $contents = array(
		            'error'         => true,
		            'message'       => 'Not data!!!',
		        );
	    	}
    	}else{
	        $contents = array(
	            'error'         => true,
	            'message'       => 'Not data!!!',
	        );
    	}

		return Response::json($contents);
    }
    //
    public function getSendnotifywhenassign(){
    	$scenario_id = 17;
    	$dataQueue = array();

    	$dataAssign = AssignModel::where('notification',0)->where('assign_id','!=',0)->where('active',1)->first();
    	if(!empty($dataAssign)){
    		$infoUserAssign = $this->getUserconfigtransport($dataAssign['assign_id']);
    		$infoUserTicket = $this->getUserconfigtransport($dataAssign['user_id']);
    		if(!empty($infoUserAssign)){
    			$infoTicket = RequestModel::where('id',$dataAssign['ticket_id'])->get()->first();
    			if(!empty($infoTicket)){
    				$dataQueue = array(
	        			'id' => $infoTicket['id'],
	        			'title' => $infoTicket['title'],
	        			'content' => $infoTicket['content'],
	        			'status' => $infoTicket['status'],
	        			'fullname' => $infoUserAssign['fullname']
	        		);
	        		$dataInsert = array(
						'scenario_id' => $scenario_id,
		    			'template_id' => 21,
		    			'transport_id' => 2,
		    			'user_id'	=> $dataAssign['assign_id'],
		    			'received'	=> $infoUserAssign['email'],
		    			'data'		=> json_encode($dataQueue),
		    			'time_create' => $this->time()
					);
					$insert = QueueModel::insert($dataInsert);
			    	if($insert > 0){
                        //goi api send
                        \Predis\Autoloader::register();
                        //Now we can start creating a redis client to publish event
                        $redis = new \Predis\Client(array(
                            "scheme" => "tcp",
                            "host" => "192.168.100.85",
                            "port" => 8899
                        ));
                        //Now we got redis client connected, we can publish event (send event)
                        $redis->publish("SendMail", $insert);
                        //
			    		$update = AssignModel::where('id',$dataAssign['id'])->update(array('notification' => 1));
				        $contents = array(
				            'error'         => false,
				            'message'       => 'Success',
				        );
			    	}else{
				        $contents = array(
				            'error'         => true,
				            'message'       => 'Not send notification',
				        );
			    	}
    			}else{
	        		$contents = array(
			            'error'         => true,
			            'message'       => 'Not ticket!!!',
			        );
	        	}
    		}else{
    			$contents = array(
		            'error'         => true,
		            'message'       => 'Not user assign!!!',
		        );
    		}
    	}else{
        	$contents = array(
	            'error'         => true,
	            'message'       => 'Not data Assign!!',
	        );
    	}

    	return Response::json($contents);
    }

    // Gui zms ticket
    public function getCreatezmsticket(){
        $data = FeedbackModel::where('source','sms')->where('notification',0)->first();
        if(!empty($data)){
            $dataTicket = RequestModel::where('id',$data['ticket_id'])->first();
            $infoUser = User::where('id',$dataTicket['user_id'])->first();
            if(!empty($infoUser)){
                $toPhone = $infoUser['phone'];
                if($toPhone == ''){
                    $update = FeedbackModel::where('id',$data['id'])->update(array('notification' => 1));
                    return Response::json(array('error' => false, 'message' => 'nsuccess'));
                }
                $dataInsert = array(
                    'scenario_id' => 333,
                    'template_id' => 78,
                    'transport_id' => 7,
                    'user_id'   => $dataTicket['user_id'],
                    'received'  => $toPhone,
                    'data'      => json_encode(array('content' => $data['content'],'template_id' => '6362ba8886cd6f9336dc')),
                    'time_create' => $this->time(),
                    'status'   => 0,
                    'time_success' => 0
                );
                $insert = QueueModel::insert($dataInsert);
                if($insert > 0){
                    //goi rabbitMQ
                    $send = BaseController::PushRabbitMQ('SendZms',array('id' => (int)$insert));
                    return $send;
                }else{
                    $contents = array(
                        'error'         => true,
                        'message'       => 'Not send notification',
                    );
                    return Response::json($contents);
                }
            }else{
                 $contents = array(
                    'error'     => true,
                    'message'   => 'Not data user create ticket!!',
                    'data'      => ''
                );
                return Response::json($contents);
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data send!!',
                'data'      => ''
            );
            return Response::json($contents);
        }
    }
}
?>