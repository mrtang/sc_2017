<?php
use ordermodel\OrdersModel;
use metadatamodel\OrderStatusModel;
class ComplainController extends \BaseController {
    private $domain = '*';

    public function __construct(){
        
    }

    public function getComplainlading(){
    	$UserInfo   = $this->UserInfo();
        if(!isset($UserInfo) || empty($UserInfo)){
            $contents = array(
                'error'     => true,
                'message'   => 'login timeout'
            );
            return Response::json($contents, 403, array('Access-Control-Allow-Origin' => $this->domain));
        }

        $page       = Input::has('page')   ? (int)Input::get('page')                : 1;
        $itemPage   = Input::has('limit')  ? Input::get('limit')                    : 20;
        $scCode     = Input::has('sc_code')  ? Input::get('sc_code')                    : '';
        $statusReturn = array();

        $Model          = new OrdersModel;
        $listData 	= $Model::whereIn('status',$statusReturn)->get();
        $total		= $Model::whereIn('status',$statusReturn)->count();
        $output     = array();
        if($listData){
        	if (Cache::has('courier_cache')){
                $listCourier    = Cache::get('courier_cache');
            }else{
                $courier        = new CourierModel;
                $listCourier    = $courier::all(array('id','name'));
            }
            if(!empty($listCourier)){
                foreach($listCourier as $val){
                    $LCourier[$val['id']]   = $val['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCourier[(int)$val['courier_id']])){
                        $val->courier_name = $LCourier[(int)$val['courier_id']];
                    }
                }
            }
            $listStatus = OrderStatusModel::all();
            if($listStatus){
            	foreach($listStatus AS $one){
            		$LStatus[(int)$one['code']] = $one['name'];
            	}
            	foreach($listData as $key => $val){
                    if (isset($LStatus[(int)$val['status']])){
                        $val->status_name = $LStatus[(int)$val['status']];
                    }
                }
            }
            $listIdSeller = array();
            foreach($listData AS $one){
            	$listIdSeller[] = $one['from_user_id'];
            }
            if(!empty($listIdSeller)){
            	$listInfoSeller = User::whereIn('id',$listIdSeller)->get(array('id','fullname','phone'));
            	foreach($listInfoSeller as $val){
                    $LSeller[$val['id']]   = $val['fullname'];
                }
                foreach($listData as $key => $val){
                    if (isset($LSeller[(int)$val['from_user_id']])){
                        $val->seller_name = $LSeller[(int)$val['from_user_id']];
                    }
                }
            }
            
        	$contents = array(
	            'error'     => false,
	            'message'   => 'success',
	            'total'		=>	$total,
	            'data'      => $listData
	        );

			return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }else{
        	$contents = array(
	            'error'     => true,
	            'message'   => 'error',
	            'data'      => ''
	        );
			        
			return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }
}
?>