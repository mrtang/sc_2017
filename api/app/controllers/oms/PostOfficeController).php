<?php
namespace oms;
use DB;
use Input;
use Response;
use CourierPostOfficeModel;
use Excel;

class PostOfficeController extends \BaseController {

	public function __construct(){
        
    }

    //
    public function getIndex(){
        $itemPage   = Input::has('limit')       ? Input::get('limit')                    : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')                : 1;
        $courier    = Input::has('courier')     ? (int)Input::get('courier')             : 0;
        
        $Model = new CourierPostOfficeModel;
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
    //
    public function postCreate(){
    	$Model = new CourierPostOfficeModel;

    	$Data               = Input::json()->all();
        $CourierId          = isset($Data['courier_id'])        ? (int)$Data['courier_id']    : 0;
        $Code               = isset($Data['code'])           ? $Data['code']       : '';
        $Name               = isset($Data['name'])           ? $Data['name']       : '';
        $Phone				= isset($Data['phone'])           ? $Data['phone']       : '';
        $Description		= isset($Data['description'])           ? $Data['description']       : '';
        $City				= isset($Data['city_id'])           ? (int)$Data['city_id']       : 0;
        $District	 		= isset($Data['district_id'])           ? (int)$Data['district_id']       : 0;
        $Ward     	 		= isset($Data['ward_id'])           ? (int)$Data['ward_id']       : 0;
        $Address	 		= isset($Data['address'])           ? $Data['address']       : '';

        $DataInsert = array(
        	'courier_id' => $CourierId,
        	'code' => $Code,
        	'name' => $Name,
        	'description' => $Description,
        	'phone' => $Phone,
        	'city_id' => $City,
        	'district_id' => $District,
        	'ward_id' => $Ward,
        	'address' => $Address
        );
        
        $Insert = $Model->insert($DataInsert);
        if($Insert){
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
    //Edit
    public function postEdit($Id){
    	if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents);
        }

    	$Model = new CourierPostOfficeModel;

    	$Data               = Input::json()->all();
        $CourierId          = isset($Data['courier_id'])        ? (int)$Data['courier_id']    : 0;
        $Code               = isset($Data['code'])           ? $Data['code']       : '';
        $Name               = isset($Data['name'])           ? $Data['name']       : '';
        $Phone				= isset($Data['phone'])           ? $Data['phone']       : '';
        $Description		= isset($Data['description'])           ? $Data['description']       : '';
        $City				= isset($Data['city_id'])           ? (int)$Data['city_id']       : 0;
        $District	 		= isset($Data['district_id'])           ? (int)$Data['district_id']       : 0;
        $Ward     	 		= isset($Data['ward_id'])           ? (int)$Data['ward_id']       : 0;
        $Address	 		= isset($Data['address'])           ? $Data['address']       : '';
        //
        $Model = $Model::find($Id);
        if(!empty($Model)){
        	if(isset($CourierId))       $Model->courier_id      = $CourierId;
        	if(isset($Code)) 		    $Model->code 			= $Code;
        	if(isset($Name)) 		    $Model->name 			= $Name;
        	if(isset($Phone)) 		    $Model->phone 			= $Phone;
        	if(isset($Description)) 	$Model->description 	= $Description;
        	if(isset($City))	 		$Model->city_id 		= $City;
        	if(isset($District)) 		$Model->district_id     = $District;
        	if(isset($Ward)) 		    $Model->ward_id 		= $Ward;
        	if(isset($Address)) 		$Model->address 		= $Address;
        	//
        	$Update = $Model->save();
        	if($Update){
                $contents = array(
                    'error'     => false,
                    'message'   => 'success'
                );
            }else{
                $contents = array(
                    'error' => true,
                    'message' => 'fail'
                );
            }
        }else{
            $contents = array(
                'error' => true,
                'message' => 'not exits'
            );
        }
        return Response::json($contents);
    }
    //
    public function getDestroy($Id)
	{
        $Model      = new CourierPostOfficeModel;
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
	//
	public function getShow($Id)
	{
        $Model      = new CourierPostOfficeModel;
        $Model      = $Model::find($Id);
        $statusCode = 200;
        if($Model){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $Model
            );
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