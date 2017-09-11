<?php
use ordermodel\VerifyModel;
class VerifyNotificationController extends \BaseController {
    private $domain = '*';
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 * @params $scenario, $user_id
	 */
     
    public function __construct(){
        
    }
    //lay ds them vao queue
    private function getVerify(){
    	$model = new VerifyModel;

    	$dataReturn = $listIdUser = array();
    	$start = $this->time() - 6*86400;
    	$listVerify = $model->where('notification',0)->where('status','SUCCESS')->where('time_create','>',$start)->take(1)->get()->toArray();
    	if(!empty($listVerify)){
    		foreach($listVerify AS $one){
    			$listIdUser[] = $one['user_id'];
    		}
    		$listInfoUser = \User::whereIn('id',$listIdUser)->get(array('id','fullname','email'))->toArray();
    		if(!empty($listInfoUser)){
    			foreach($listVerify AS $oneV){
	    			foreach($listInfoUser AS $oneUser){
	    				if($oneV['user_id'] == $oneUser['id']){
	    					$oneV['fullname'] = $oneUser['fullname'];
	    					$oneV['email']	  = $oneUser['email'];
	    					$dataReturn[]     = $oneV;
	    				}
	    			}
	    		}
    		}
    	}
    	return $dataReturn;
    }
    //them vao queue
    public function getNotifyverify(){
    	$dataInsert = $listId = array();
    	$model = new VerifyModel;
    	$listVerify = $this->getVerify();
        
    	if(!empty($listVerify)){
    		foreach($listVerify AS $oneV){
    			$listId[] = $oneV['id'];
    			$money_in = 0;
    			if($oneV['type'] == 1){
    				$money_in = ($oneV['total_money_collect'] - $oneV['total_fee'] + $oneV['balance'] + (($oneV['balance_available'] - $oneV['config_balance']) < 0 ? ($oneV['balance_available'] - $oneV['config_balance']) : 0));
    			}elseif($oneV['type'] == 2){
    				$money_in = $oneV['total_money_collect'] - $oneV['total_fee'];
    			}
    			$money_hold = $oneV['balance_available'] - $oneV['config_balance'];
    			$dataInsert[] = array(
					'scenario_id' => 19,
	    			'template_id' => 23,
	    			'transport_id' => 2,
	    			'user_id'	=> $oneV['user_id'],
	    			'received'	=> $oneV['email'],
	    			'data'		=> json_encode(array(
	    				'fullname' => $oneV['fullname'],
	    				'email'	   => $oneV['email'],
	    				'note'	   => $oneV['note'],
	    				'account' => $oneV['account'],
	    				'balance'  => number_format($oneV['balance']),
	    				'verify_id' => $oneV['id'],
	    				'time_success_start' => date("d/m/Y",$oneV['time_start']),
	    				'time_success_end' => date("d/m/Y",$oneV['time_end']),
	    				'total_money_collect' => number_format($oneV['total_money_collect']),
	    				'total_fee'			=> number_format($oneV['total_fee']),
	    				'transaction_id'	=> $oneV['transaction_id'],
	    				'money_hold'		=> number_format($money_hold),
	    				'money_in'			=> number_format($money_in),
	    				'type'				=> $oneV['type']
	    			)),
	    			'time_create' => $this->time(),
				);
    		}
    		$insert = QueueModel::insert($dataInsert);
            if($insert == true){
                //update notify
                $update = $model->whereIn('id',$listId)->update(array('notification' => 1));
                $contents = array(
                    'error'         => false,
                    'message'       => 'Success!',
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
                'message'       => 'Not data insert!',
            );
    	}
    	return Response::json($contents);
    }



}
?>