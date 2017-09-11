<?php
class CourierAreaController extends \BaseController {
    private $domain = '*';
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
     
    public function __construct(){
        
    }
    //
    public function getIndex()
	{
		$Model = new CourierAreaModel;
        $statusCode = 200;
        $Data   = $Model->get();
        if($Data){
            if (Cache::has('courier_cache')){
                $ListCourier    = Cache::get('courier_cache');
                
            }else{
                $Courier        = new CourierModel;
                $ListCourier    = $Courier::all(array('id','name'));
            }
            
            if($ListCourier){
                foreach($ListCourier as $val){
                    $LCourier[$val['id']]   = $val['name'];
                }
                foreach($Data as $key => $val){
                    if (isset($LCourier[$val['courier_id']])){
                        $val->courier_name = $LCourier[$val['courier_id']];
                    }
                }
            }
        }else{
            $contents = array(
                'error'         => false,
                'message'       => '',
                'total'         => $Model->count(),
                'data'          => ''
            );
        }
        $contents = array(
            'error'         => false,
            'message'       => '',
            'total'         => $Model->count(),
            'data'          => $Data
        );
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
	/**
	 * Create New.
	 *
	 * @return Response
	 */
	public function postCreate()
	{
		$Model = new CourierAreaModel;
        $Data           = Input::json()->all();
        $Name           = isset($Data['name_area'])      ? trim($Data['name_area']) : null;
        $Courier        = isset($Data['courier_id'])    ? (int)$Data['courier_id'] : null;
        
        $statusCode = 200;
        
        if(empty($Name)){
            $contents = array(
                'error' => true,
                'message' => 'values empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(array('name' => $Name, 'courier_id' => $Courier));
        
        if($Id){
            $contents = array(
                'error'     => false,
                'message'   => '',
                'id'        => $Id
            );
        }else{
            $contents = array(
                'error' => true,
                'message' => 'insert false'
            );
        }
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
		//
	}
	/**
	 * Get one record
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getShow($id)
	{
		$Model = new CourierAreaModel;
        $Model      = $Model::find($id);
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
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
	/**
	 * Edit.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function postEdit($id)
	{
		$Model = new CourierAreaModel;
		$Data           = Input::json()->all();
        $Name           = isset($Data['name_area'])      ? trim($Data['name_area']) : null;
        $Courier        = isset($Data['courier_id'])    ? (int)$Data['courier_id'] : 1;
        $statusCode     = 200;
        
        if($id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
            
        $Model = $Model::find($id);
        if($Model){
            if(!empty($Name))           $Model->name        = $Name;
            if(isset($Courier))          $Model->courier_id      = $Courier;
            
            $Update = $Model->save();
       
            if($Update){
                $contents = array(
                    'error'     => false,
                    'message'   => 'success'
                );
            }else{
                $contents = array(
                    'error' => true,
                    'message' => 'edit error'
                );
            }
        }else{
            $contents = array(
                'error' => true,
                'message' => 'not exits'
            );
        }
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
	/**
	 * Delete.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getDestroy($id)
	{
		$Model = new CourierAreaModel;
        $Model      = $Model::find($id);
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
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
    /**
     * Get list by area_id.
     *
     */
    public function getAreabycourier($courier_id){
        $Model      = new CourierAreaModel;
        $Data       = $Model::where('courier_id','=',(int)$courier_id)->get(array('id','name'));
        $statusCode = 200;
        if($Data){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $Data
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'not exits'
            );
        }
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }
    //get area city
    public function getCityinarea($courier_id){
        $Model      = new CourierAreaModel;
        $Data       = $Model::where('courier_id','=',(int)$courier_id)->get(array('id','name'))->toArray();

        if(!empty($Data)){
            $listAreaId = array();
            foreach($Data AS $one){
                $listAreaId[] = $one['id'];
            }

            $output = array();
            $listCity = AreaLocationModel::whereIn('area_id',$listAreaId)->groupBy('area_id','city_id')->get(array('area_id','city_id'))->toArray();
            foreach($Data AS $one){
                foreach($listCity AS $city){
                    if($one['id'] == $city['area_id']){
                        $output[$one['id']][] = array(
                            'area_id' => $city['area_id'],
                            'area_name' => $one['name'],
                            'city_id' => $city['city_id']
                        );
                    }
                }
            }

            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $output
            );
            
            
        }else{
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => ''
            );
        }
        return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
    }


}
?>