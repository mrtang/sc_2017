<?php
class LocationController extends \BaseController {
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
		$Model = new LocationModel;
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
		$Model = new LocationModel;
        $Data           = Input::json()->all();
        $Name           = isset($Data['name'])      ? trim($Data['name']) : null;
        $Active         = isset($Data['active'])    ? (int)$Data['active'] : 1;
        
        $statusCode = 200;
        
        if(empty($Name)){
            $contents = array(
                'error' => true,
                'message' => 'values empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(array('name' => $Name, 'active' => $Active));
        
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
		$Model = new LocationModel;
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
		$Model = new LocationModel;
		$Data           = Input::json()->all();
        $Name           = isset($Data['name'])      ? trim($Data['name']) : null;
        $Active         = isset($Data['active'])    ? (int)$Data['active'] : null;
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
            if(isset($Active))          $Model->active      = $Active;
            
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
		$Model = new LocationModel;
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