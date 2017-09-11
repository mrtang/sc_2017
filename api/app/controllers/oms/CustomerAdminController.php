<?php
namespace oms;
use DB;
use Input;
use Response;
use omsmodel\CustomerAdminModel;

class CustomerAdminController extends \BaseController {
    private $domain = '*';

	public function __construct(){
        
    }

    //create
    public function postCreate(){
    	$UserInfo   = $this->UserInfo();
    	if(!isset($UserInfo) || empty($UserInfo)){
            $contents = array(
                'error'     => true,
                'message'   => 'login timeout'
            );
            return Response::json($contents, 440, array('Access-Control-Allow-Origin' => $this->domain));
        }

    	$Data       	 = Input::json()->all();
        $userId  		 = isset($Data['user_id'])    ? (int)$Data['user_id']  : 0;
        $supportId       = $UserInfo['id'];
        $Integrate       = isset($Data['integrate'])          ? $Data['integrate']             : 0;
        $Active     	 = isset($Data['active'])        ? (int)$Data['active']      : 0;
        
        $Model      = new CustomerAdminModel;
        $statusCode = 200;
        
        if(empty($userId)){
            $contents = array(
                'error' => true, 'message' => 'Không tồn tại khách hàng!!'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(
                    array(
                        'user_id'    => $userId, 
                        'support_id'          => $supportId, 
                        'integrate'         => $Integrate, 
                        'active'        => $Active
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

    ///Edit
    public function postEdit($Id)
	{
		$UserInfo   = $this->UserInfo();
    	if(!isset($UserInfo) || empty($UserInfo)){
            $contents = array(
                'error'     => true,
                'message'   => 'login timeout'
            );
            return Response::json($contents, 440, array('Access-Control-Allow-Origin' => $this->domain));
        }

		$Data       	 = Input::json()->all();
        $userId  		 = isset($Data['user_id'])    ? (int)$Data['user_id']  : 0;
        $supportId       = $UserInfo['id'];
        $Integrate       = isset($Data['integrate'])          ? $Data['integrate']             : 0;
        $Active     	 = isset($Data['active'])        ? (int)$Data['active']      : 0;
        
        $Model      = new CustomerAdminModel;
        $statusCode = 200;
        
        if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
            
        $Model = $Model::find($Id);
        if($Model){
            if(isset($userId))      $Model->user_id         = $userId;
            if(!empty($supportId))  $Model->support_id       = $supportId;
            if(!empty($Integrate))      $Model->integrate           = $Integrate;
            if(isset($Active))      $Model->active          = $Active;
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

}
?>