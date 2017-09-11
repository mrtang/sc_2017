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
        $bccode    = Input::has('bccode')     ? Input::get('bccode')                    : "";
        
        $Model = new CourierPostOfficeModel;
        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }

        if(!empty($bccode)){
            $Model = $Model->where('bccode',$bccode);
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
        $Lat                = isset($Data['lat'])           ? $Data['lat']       : '';
        $Lng                = isset($Data['lng'])           ? $Data['lng']       : '';

        $Description		= isset($Data['description'])           ? $Data['description']       : '';
        $City				= isset($Data['city_id'])           ? (int)$Data['city_id']       : 0;
        $District	 		= isset($Data['district_id'])           ? (int)$Data['district_id']       : 0;
        $Ward     	 		= isset($Data['ward_id'])           ? (int)$Data['ward_id']       : 0;
        $Address	 		= isset($Data['address'])           ? $Data['address']       : '';

        $DataInsert = array(
        	'courier_id' => $CourierId,
/*        	'code' => $Code,
*/        	'name' => $Name,
/*        	'description' => $Description,
*/        	'phone' => $Phone,
            'lat'   => $Lat,
            'lng'   => $Lng,
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
        $Lat                = isset($Data['lat'])           ? $Data['lat']       : '';
        $Lng                = isset($Data['lng'])           ? $Data['lng']       : '';
        $Name               = isset($Data['name'])           ? $Data['name']       : '';
        $Phone				= isset($Data['phone'])           ? $Data['phone']       : '';
        $Description		= isset($Data['description'])           ? $Data['description']       : '';
        $City				= isset($Data['city_id'])           ? (int)$Data['city_id']       : 0;
        $District	 		= isset($Data['district_id'])           ? (int)$Data['district_id']       : 0;
        $Ward     	 		= isset($Data['ward_id'])           ? (int)$Data['ward_id']       : 0;
        $Address	 		= isset($Data['address'])           ? $Data['address']       : '';
        $Verified           = isset($Data['verified'])           ? $Data['verified']       : '';
        //
        $Model = $Model::find($Id);
        if(!empty($Model)){
        	if(isset($CourierId))       $Model->courier_id      = $CourierId;
        	//if(isset($Code) && "") 		    $Model->code 			= $Code;
        	if(isset($Name)) 		    $Model->name 			= $Name;
        	if(isset($Phone)) 		    $Model->phone 			= $Phone;
        	if(isset($Lat)) 	        $Model->lat 	        = $Lat;
            if(isset($Lng))             $Model->lng             = $Lng;
        	if(isset($City))	 		$Model->city_id 		= $City;
        	if(isset($District)) 		$Model->district_id     = $District;
        	if(isset($Ward)) 		    $Model->ward_id 		= $Ward;
        	if(isset($Address)) 		$Model->address 		= $Address;
            if(!empty($Verified))        $Model->verified        = $Verified;
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


    public function getFindAround()
    {
        $Lat = Input::has('lat') ? Input::get('lat') : 0;
        $Lng = Input::has('lng') ? Input::get('lng') : 0;
        $Radius = Input::has('radius') ? Input::get('radius') : 2;

        $Cmd = Input::has('cmd') ? Input::get('cmd') : "";

        if (empty($Lng) || empty($Lat)) {
            return Response::json([
                'error' => true,
                'error_message' => 'Vui lòng nhập vị trí hiện tại của bạn '
            ]);
        }

        $Model = new CourierPostOfficeModel;
        $Model = $Model->select('*', DB::raw("ROUND(3959 * acos (cos ( radians($Lat) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians($Lng) ) + sin ( radians($Lat) ) * sin( radians( lat ))), 2) AS distance"));
        $Data = $Model
            ->having('distance', '<=', $Radius)
            ->where('courier_id', '!=', 2)
            ->where('lat', '>', 0)
            ->where('lng', '>', 0)
            ->where('ward_id', '>', 0)
            ->orderBy('distance', 'ASC')
            ->take(10)
            ->get()->toArray();

        if (!empty($Data)) {
            
            $Courier = $this->getCourier();
            foreach ($Data as $key => $value) {
                $Data[$key]['courier_name'] = "";
                if (!empty($Courier[$value['courier_id']])) {
                    $Data[$key]['courier_name'] = $Courier[$value['courier_id']];
                }
            }
        }
        return Response::json([
            'error' => false,
            'error_message' => 'Thành công',
            'data' => $Data
        ]);
    }



}





?>