<?php
use omsmodel\NotifyFacebookModel;

class FacebookNotificationController extends \BaseController {
    private $domain = '*';
	
     
    public function __construct(){
        
    }
    //get user
    private function getUser(){
    	$listUserFb = UserConfigTransportModel::where('transport_id',4)->get(array('user_id','received'))->toArray();
    	if(!empty($listUserFb)){
    		return $listUserFb;
    	}
    	return array();
    }
    //
    public function getCreatenotify(){
    	$listUser = $this->getUser();
    	$dataNotify = NotifyFacebookModel::where('notify',0)->first();
    	$dataQueue = array();
    	if(!empty($listUser) && !empty($dataNotify)){
    		foreach($listUser AS $user){
    			$dataQueue[] = array(
    				'user_id' => $user['user_id'],
    				'scenario_id' => 1,
    				'template_id' => 1,
    				'transport_id' => 4,
    				'received' => $user['received'],
    				'time_create' => $this->time(),
    				'data' => json_encode(array('href' => $dataNotify['href'],'content' => $dataNotify['content']))
    			);
    		}
    		$insert = QueueModel::insert($dataQueue);
    		if($insert){
    			$update = NotifyFacebookModel::where('id',$dataNotify['id'])->update(array('notify' => 1));
    			return Response::json(array('error' => false, 'message' => 'success'));
    		}else{
    			return Response::json(array('error' => true, 'message' => 'Not create notification!'));
    		}
    	}else{
    		return Response::json(array('error' => true, 'message' => 'Not data create!'));
    	}
    }
}
?>
