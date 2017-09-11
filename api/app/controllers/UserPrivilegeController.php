<?php
class UserPrivilegeController extends \BaseController {
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
        $page       = Input::has('page') ? (int)Input::get('page') : 1;
        $itemPage   = Input::has('item_page') ? (int)Input::get('item_page') : 20;
        $offset     = ($page - 1)*$itemPage;
       
        $Model  = new UserPrivilegeModel;
        $Data   = $Model::skip($offset)->take($itemPage)->orderBy('id','DESC')->get();
        if($Data){
        	$statusCode = 200;
            $contents = array(
                'error'         => false,
                'message'       => 'success',
                'total'         => $Model::count(),
                'item_page'     => $itemPage,
                'data'          => $Data
            );
        }else{
            $statusCode = 500;
            $contents = array(
                'error'         => true,
                'message'       => 'Not data!!!!',
                'data'          => ''
            );
        }
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
	/**
	 * Create.
	 *
	 * @return Response
	 */
	public function postCreate()
	{ 
        $Data       		= Input::json()->all();
        $Name  			    = isset($Data['name'])    		? $Data['name']  		: null;
        $Code               = isset($Data['code'])        ? $Data['code']     : null;
        
        $Model      = new UserPrivilegeModel;
        $statusCode = 200;
        
        if(empty($Name)){
            $contents = array(
                'error' => true, 'message' => 'Name is not empty!!!'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(
                    array(
                        'privilege_name'      => $Name, 
                        'privilege_code'          => trim(strtoupper($Code)) 
                    ));
        
        if($Id){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'id'        => $Id
            );
        }else{
            $contents = array(
                'error' => true,
                'message' => 'insert false'
            );
        }
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
		/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getShow($Id)
	{
        $Model      = new UserPrivilegeModel;
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
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function postEdit($Id)
	{
		$Data       = Input::json()->all();
        $Name               = isset($Data['privilege_name'])          ? $Data['privilege_name']         : null;
        $Code               = isset($Data['privilege_code'])        ? (int)$Data['privilege_code']     : null;

        $statusCode = 200;
        
        if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
            
        $Model = new UserPrivilegeModel;
        $Model = $Model::find($Id);
        if($Model){
            if(isset($Name))   $Model->privilege_name      = $Name;
            if(isset($Code))      $Model->privilege_code          = trim(strtoupper($Code));
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
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
	/**
	 * Remove.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getDestroy($Id)
	{
        $Model      = new UserPrivilegeModel;
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
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
}
?>