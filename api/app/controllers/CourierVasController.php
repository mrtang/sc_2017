<?php
class CourierVasController extends \BaseController {
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
		$Model = new CourierVasModel;
        $statusCode = 200;
        $contents = array(
            'error'         => false,
            'message'       => '',
            'total'         => $Model->count(),
            'data'          => $Model->get()
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
		$Model = new CourierVasModel;
        $Data           = Input::json()->all();
        $Name           = isset($Data['name_vas'])      ? trim($Data['name_vas']) : null;
        $Code           = isset($Data['code'])      ? trim($Data['code']) : null;
        $Active         = isset($Data['active'])    ? (int)$Data['active'] : 1;
        $VasValueType   = isset($Data['vas_value_type'])    ? (int)$Data['vas_value_type'] : 1;
        
        $statusCode = 200;
        
        if(empty($Name)){
            $contents = array(
                'error' => true,
                'message' => 'values empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        if(empty($Code)){
            $contents = array(
                'error' => true,
                'message' => 'values empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(array('name' => $Name, 'code' => $Code, 'active' => $Active,'vas_value_type' => $VasValueType));
        
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
		$Model = new CourierVasModel;
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
		$Model = new CourierVasModel;
		$Data           = Input::json()->all();
        $Name           = isset($Data['name_vas'])      ? trim($Data['name_vas']) : null;
        $Code           = isset($Data['code'])      ? trim($Data['code']) : null;
        $Active         = isset($Data['active'])    ? (int)$Data['active'] : null;
        $VasValueType   = isset($Data['vas_value_type'])    ? (int)$Data['vas_value_type'] : null;
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
            if(!empty($Code))           $Model->code        = $Code;
            if(isset($Active))          $Model->active      = $Active;
            if(isset($VasValueType))    $Model->vas_value_type      = $VasValueType;
            
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
		$Model = new CourierVasModel;
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
}
?>