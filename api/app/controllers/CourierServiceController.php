<?php

class CourierServiceController extends \BaseController {
    private $domain = '*';
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
        $Active   = Input::has('active') ? (int)Input::get('active') : null;
        
        $Model = new CourierServiceModel;
        
        if(isset($Active)){
            $Model  = $Model->where('active','=',$Active);
        }
        
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
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate()
	{
        $Data           = Input::json()->all();
        $Name           = isset($Data['name'])      ? trim($Data['name']) : null;
        $Active         = isset($Data['active'])    ? (int)$Data['active'] : 1;
        
        $Model      = new CourierServiceModel;
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
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getShow($id)
	{
        $Model      = new CourierServiceModel;
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
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function postEdit($id)
	{
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
            
        $Model = new CourierServiceModel;
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
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getDestroy($id)
	{
        $Model      = new CourierServiceModel;
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
