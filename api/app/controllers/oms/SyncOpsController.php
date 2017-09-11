<?php
namespace oms;
use DB;
use Input;
use Response;
use User;
use sellermodel\UserInfoModel;
use omsmodel\SellerModel;
use OrderModel;

class SyncOpsController extends \BaseController {
    private $domain = '*';

	public function __construct(){

    }

    public function getSync(){
    	$User = User::where('sync',0)->first();
    	if(!empty($User)){
    		$FirstTimePickup = OrderModel::where('from_user_id',$User['id'])->where('time_pickup','>',0)->orderBy('time_pickup','ASC')->first();
    		$LastTimePickup = OrderModel::where('from_user_id',$User['id'])->where('time_pickup','>',0)->orderBy('time_pickup','DESC')->first();
    		//
    		if(!empty($FirstTimePickup)){
    			$CheckExistOms = SellerModel::where('user_id',$User['id'])->first();
    			$CheckExistSeller = UserInfoModel::where('user_id',$User['id'])->first();
    			$CheckTime = 7*86400 + $LastTimePickup['time_pickup'];
    			if($CheckTime > $this->time()){
					//kh dang su dung
					$StatusPipe = 300;
				}else{
					//kh dang ngung sd
					$StatusPipe = 200;
				}

    			//dem so van don trung binh lay trong 30 ngay gan nhat
    			$Days = (int)($LastTimePickup['time_pickup'] - $FirstTimePickup['time_pickup'])/86400;
    			if($Days > 30){
    				$Days = 30;
    				$CountLading = OrderModel::where('from_user_id',$User['id'])->where('time_pickup','>=',($LastTimePickup['time_pickup'] - 30*86400))->where('time_pickup','<=',$LastTimePickup['time_pickup'])->count();
    			}else{
    				$CountLading = OrderModel::where('from_user_id',$User['id'])->where('time_pickup','>=', $LastTimePickup['time_pickup'])->where('time_pickup','<=',$LastTimePickup['time_pickup'])->count();
    			}
    			if($Days <= 1){
    				$Days = 1;
    			}
    			$NumAvgOrder = ceil($CountLading/$Days);

    			//khi da ton tai
    			if(!empty($CheckExistSeller) && !empty($CheckExistOms)){
    				//update oms
    				$DataUpdateOms = array(
    					'num_order_avg' => $NumAvgOrder,
    					'first_time_pickup' => $FirstTimePickup['time_pickup'],
    					'last_time_pickup' => (!empty($LastTimePickup)) ? $LastTimePickup['time_pickup'] : $FirstTimePickup['time_pickup']
    				);
    				$UpdateOms = SellerModel::where('user_id',$User['id'])->update($DataUpdateOms);
    				//update user_info
    				$DataUpdateSeller = array(
    					'pipe_status' => $StatusPipe
    				);
    				$UpdateSeller = UserInfoModel::where('user_id',$User['id'])->update($DataUpdateSeller);
    				if($UpdateSeller){
    					//update metadata user
    					User::where('id',$User['id'])->update(array('sync' => 1));
    					$contents = array(
			                'error'     => false,
			                'message'   => 'Success sync !'
			            );
			            return Response::json($contents);
    				}else{
    					$contents = array(
			                'error'     => true,
			                'message'   => 'Fail !'
			            );
			            return Response::json($contents);
    				}
    			}elseif(!empty($CheckExistSeller) && empty($CheckExistOms)){
    				$DataInsertOms = array(
	    				'user_id' => $User['id'],
	    				'time_create' => $this->time(),
	    				'num_order_avg' => $NumAvgOrder,
    					'first_time_pickup' => $FirstTimePickup['time_pickup'],
    					'last_time_pickup' => (!empty($LastTimePickup)) ? $LastTimePickup['time_pickup'] : $FirstTimePickup['time_pickup']
	    			);
	    			$InsertOms = SellerModel::insert($DataInsertOms);
	    			if($InsertOms){
	    				$DataUpdateSeller = array(
	    					'pipe_status' => $StatusPipe
	    				);
	    				$UpdateSeller = UserInfoModel::where('user_id',$User['id'])->update($DataUpdateSeller);
	    				//update metadata user
    					User::where('id',$User['id'])->update(array('sync' => 1));
	    				if(!$UpdateSeller){
	    					$contents = array(
				                'error'     => true,
				                'message'   => 'Not syns !!'
				            );
				            return Response::json($contents);
	    				}
	    				$contents = array(
			                'error'     => false,
			                'message'   => 'Success sync !!'
			            );
			            return Response::json($contents);
	    			}else{
	    				$contents = array(
			                'error'     => true,
			                'message'   => 'Fail !!'
			            );
			            return Response::json($contents);
	    			}
    			}elseif(empty($CheckExistSeller) && !empty($CheckExistOms)){
    				$DataInsertSeller = array(
	    				'user_id' => $User['id'],
	    				'email_nl' => '',
	    				'active' => 1,
	    				'privilege' => 0,
	    				'notification' => 1,
	    				'pipe_status' => $StatusPipe
	    			);
	    			$InsertSeller = UserInfoModel::insert($DataInsertSeller);
	    			if($InsertSeller){
	    				$DataUpdateOms = array(
	    					'num_order_avg' => $NumAvgOrder,
	    					'first_time_pickup' => $FirstTimePickup['time_pickup'],
	    					'last_time_pickup' => (!empty($LastTimePickup)) ? $LastTimePickup['time_pickup'] : $FirstTimePickup['time_pickup']
	    				);
	    				$UpdateOms = SellerModel::where('user_id',$User['id'])->update($DataUpdateOms);
	    				//update metadata user
    					User::where('id',$User['id'])->update(array('sync' => 1));
	    				if(!$UpdateOms){
	    					$contents = array(
				                'error'     => true,
				                'message'   => 'Not syns !!!'
				            );
				            return Response::json($contents);
	    				}
	    				$contents = array(
			                'error'     => false,
			                'message'   => 'Success sync !!!'
			            );
			            return Response::json($contents);
	    			}else{
	    				$contents = array(
			                'error'     => true,
			                'message'   => 'Fail !!!'
			            );
			            return Response::json($contents);
	    			}
    			}
    		}else{
    			//insert KH tiem nang
    			$DataInsertOms = array(
    				'user_id' => $User['id'],
    				'time_create' => $this->time(),
    				'num_order_avg' => 0,
					'first_time_pickup' => 0,
					'last_time_pickup' => 0
    			);
    			
    			$InsertOms = SellerModel::insert($DataInsertOms);
    			if($InsertOms){
    				$CheckExistSeller = UserInfoModel::where('user_id',$User['id'])->first();
    				if(!$CheckExistSeller){
	    				$DataInsert = array(
		    				'user_id' => $User['id'],
		    				'email_nl' => '',
		    				'active' => 1,
		    				'privilege' => 0,
		    				'notification' => 1,
		    				'pipe_status' => 100,
		    			);
		    			$Insert = UserInfoModel::insert($DataInsert);
		    			if(!$Insert){
		    				$contents = array(
				                'error'     => true,
				                'message'   => 'Not sync customer potential!!'
				            );
				            return Response::json($contents);
		    			}
		    			//update metadata user
	    				User::where('id',$User['id'])->update(array('sync' => 1));
	    				$contents = array(
			                'error'     => false,
			                'message'   => 'Success sync customer potential!!'
			            );
			            return Response::json($contents);
			        }else{
			        	//update metadata user
	    				User::where('id',$User['id'])->update(array('sync' => 1));
	    				$contents = array(
			                'error'     => false,
			                'message'   => 'Success sync customer potential!!'
			            );
			            return Response::json($contents);
			        }
    			}else{
    				$contents = array(
		                'error'     => true,
		                'message'   => 'Not sync customer potential!!'
		            );
		            return Response::json($contents);
    			}
    		}
    	}else{
    		$contents = array(
                'error'     => true,
                'message'   => 'Not user sync!!'
            );
            return Response::json($contents);
    	}
    }
    //cap nhat KH tiem nang sang KH moi
    public function getConvertcustomernew(){
    	$customer = SellerModel::where('last_time_pickup','>',0)->where('sync',0)->first();
    	if(!empty($customer)){
    		//
    		$update = UserInfoModel::where('user_id',$customer['user_id'])->update(array('pipe_status' => 200));
    		if($update){
    			SellerModel::where('id',$customer['id'])->update(array('sync' => 1));
    			$contents = array(
	                'error'     => false,
	                'message'   => 'Success!',
	                'user_id'   => $customer['user_id']
	            );
	            return Response::json($contents);
    		}else{
    			$contents = array(
	                'error'     => true,
	                'message'   => 'Not update!'
	            );
	            return Response::json($contents);
    		}
    	}else{
    		$contents = array(
                'error'     => true,
                'message'   => 'Not user!'
            );
            return Response::json($contents);
		}
    }

    //update sale quan ly
    public function getUpdatesale(){
    	$timeCheck = $this->time() - 7*86400;
    	$data = SellerModel::where('seller_id','>',0)->where('sale',0)->where('last_time_pickup','<',$timeCheck)->first();
    	if(!empty($data)){
    		$update = SellerModel::where('id',$data['id'])->update(array('seller_id' => 0));
    		if($update){
    			SellerModel::where('id',$data['id'])->update(array('sale' => 1));
    			$contents = array(
	                'error'     => false,
	                'message'   => 'Success!',
	                'user_id'   => $data['user_id']
	            );
	            return Response::json($contents);
    		}else{
    			$contents = array(
	                'error'     => true,
	                'message'   => 'Not update!'
	            );
	            return Response::json($contents);
    		}
    	}else{
    		$contents = array(
                'error'     => true,
                'message'   => 'Not data!'
            );
            return Response::json($contents);
    	}
    }




}
?>