<?php
namespace oms;
use DB;
use Input;
use Response;
use LMongo;
use ordermodel\OrdersModel;
use CourierDeliveryModel;
use Excel;

class FeeDeliveryController extends \BaseController {

	public function __construct(){
        
    }
    //
    public function getIndex(){
        $itemPage   = Input::has('limit')       ? Input::get('limit')                    : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')                : 1;
        $courier    = Input::has('courier')     ? (int)Input::get('courier')             : 0;
        
        $Model = new CourierDeliveryModel;
        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }

        $offset = ($page - 1) * $itemPage;
        $total = $Model->count();
        $data = $Model->skip($offset)->take($itemPage)->get()->toArray();

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $data,
            'total'         => $total,
            'item_page'     => $itemPage
        );
        return Response::json($contents);
    }

    public function postCreatemulty(){
    	$Model = new CourierDeliveryModel;

    	$Data               = Input::json()->all();
        $CourierId          = isset($Data['courier'])        ? (int)$Data['courier']    : 0;
        $ServiceId          = isset($Data['service'])        ? (int)$Data['service']    : 0;
        $FeeId              = isset($Data['fee'])            ? (int)$Data['fee']        : 0;
        $Vat                = isset($Data['vat'])            ? (int)$Data['vat']        : 10;
        $Oil                = isset($Data['oil'])            ? (int)$Data['oil']        : 0;
        $FromCity           = isset($Data['from_city'])      ? (int)$Data['from_city']      : null;
        $ToCity             = isset($Data['to_city'])        ? $Data['to_city']      : null;

        if($CourierId == '' || $ServiceId == '' || $FeeId == '' || $FromCity == '' || $ToCity == ''){
        	$contents = array(
	            'error'         => true,
	            'message'       => 'Bạn hãy nhập đủ dữ liệu!',
	        );
	        return Response::json($contents);
        }

        $ListToCity = $Model->where('courier_id',$CourierId)->where('service_id',$ServiceId)->where('fee_id',$FeeId)->where('from_city',$FromCity)->lists('to_city');
        if(!empty($ListToCity)){
        	$result = array_diff($ToCity,$ListToCity);
        	if(empty($result)){
        		$contents = array(
		            'error'         => true,
		            'message'       => 'Cấu hình phí đã tồn tại!!',
		        );
		        return Response::json($contents);
        	}
        	$DataInsert = array();
	        
	        $DataBuild = array(
	        	'courier_id' => $CourierId,
	        	'service_id' => $ServiceId,
	        	'fee_id'     => $FeeId,
	        	'from_city'  => $FromCity,
	        	'vat' 	     => $Vat/100,
	        	'oil'   	 => $Oil,
	        );

	        foreach($result AS $Key => $Val){
	        	$DataBuild['to_city'] = $Val;
	        	$DataInsert[] = $DataBuild; 
	        }
	        //var_dump($DataInsert);die;
	        $Inserts = $Model->insert($DataInsert);
	        if($Inserts){
	        	$contents = array(
		            'error'         => false,
		            'message'       => 'Thêm mới thành công!',
		        );
		        return Response::json($contents);
	        }else{
	        	$contents = array(
		            'error'         => true,
		            'message'       => 'Không thể thêm mới!',
		        );
		        return Response::json($contents);
	        }
        }else{
	        $DataInsert = array();
	        
	        $DataBuild = array(
	        	'courier_id' => $CourierId,
	        	'service_id' => $ServiceId,
	        	'fee_id'     => $FeeId,
	        	'from_city'  => $FromCity,
	        	'vat' 	     => $Vat/100,
	        	'oil'   	 => $Oil,
	        );

	        foreach($ToCity AS $Key => $Val){
	        	$DataBuild['to_city'] = $Val;
	        	$DataInsert[] = $DataBuild; 
	        }
	        //var_dump($DataInsert);die;
	        $Inserts = $Model->insert($DataInsert);
	        if($Inserts){
	        	$contents = array(
		            'error'         => false,
		            'message'       => 'Thêm mới thành công!',
		        );
		        return Response::json($contents);
	        }else{
	        	$contents = array(
		            'error'         => true,
		            'message'       => 'Không thể thêm mới!',
		        );
		        return Response::json($contents);
	        }
	    }
    }
    //Delete
    public function getDestroy($Id){
        $Model      = new CourierDeliveryModel;
        $Model      = $Model::find($Id);
        $statusCode = 200;
        
        if($Model){
            $Delete = $Model->delete();
            if($Delete){
                $contents = array(
                    'error'     => false,
                    'message'   => 'success'
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'delete error'
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'not exits'
            );
        }
        
        return Response::json($contents, $statusCode);
    }
}
?>