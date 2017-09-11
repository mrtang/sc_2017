<?php namespace seller;

use Validator;
use Response;
use Input;
use sellermodel\ChildInfoModel;
use sellermodel\UserInfoModel;
//
use omsmodel\ChildUserModel;

class ChildInfoController extends \BaseController {
    
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		//
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		$UserInfo   	= $this->UserInfo();
        $id 			= (int)$UserInfo['id'];
        $parent_email 	= (string)$UserInfo['email'];

        $Email = Input::has('email') ? Input::get('email') : "";
        $Email = Input::has('email') ? Input::get('email') : "";
	}
    
    /**
	 * Show the form for multi creating a new resource.
	 *
	 * @return Response
	 */
	public function postMulticreate()
	{
		$UserInfo   	= $this->UserInfo();
        $id 			= (int)$UserInfo['id'];
        $parent_email 	= (string)$UserInfo['email'];
        


		$Data =  Input::json()->all();
        $_UserInfo = new \sellermodel\UserInfoModel;
        $_User = new \User;
        $Model = new \omsmodel\ChildUserModel;

		
        if(!empty($Data)){
                
            $validation = Validator::make($Data, array(
                'email'         => 'required|email', // Child
                //'role'          => 'required|numeric',
            ));


            if($validation->fails()) {
                return Response::json(array('error' => true, 'message' => $validation->messages()), 200);
            }

            $_User = $_User->where('email', $Data['email'])->first();

            if(!$_User){
            	return Response::json(array('error' => true, 'message' => array(
            		'USER_NOT_FOUND', $Data['email']
        		)), 200);
            }

        	if($_User->id == $id){
            	return Response::json(array('error' => true, 'message' => array(
            		'DUPLICATE', $Data['email']
        		)), 200);
            }
            
            $_UserInfo = $_UserInfo->where('id', $_User->id)->where('parent_id', $id)->first();
            /*return $_UserInfo;*/
        	
            if($_UserInfo){
            	return Response::json(array('error' => true, 'message' => array(
            		'DUPLICATE', $Data['email']
        		)), 200);
            }


            
            
            $DataInsert[]   = array(
            	'parent_id'     	=> $id,
                'parent_email'     	=> $parent_email,
            	'child_email'		=> $Data['email'],
            	'child_id'			=> $_User->id,
            	'role_id'			=> 1,
            	'time_create'		=> $this->time(),
                'token'          	=> md5($parent_email + $Data['email'] + $this->time()) 
            );
            
            if(!empty($DataInsert)){
                $Insert     = $Model->insert($DataInsert);
            }
                
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => array()
            );
                
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'DATA_EMPTY',
                'data'      => array()
            );
        }
        
        return Response::json($contents);
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

	public function getVerifychild($token)
	{	
		// Table user_info in databse sellers
		$_UserInfo = new \sellermodel\UserInfoModel;

		$Model = new \omsmodel\ChildUserModel;
		$Model = $Model->where('token', $token)->first();



		if($Model){
			
			if($Model->time_success > 0){ // Check is actived 
				return Response::json(array(
	                'error'     => true,
	                'message'   => 'ACTIVED',
	                'data'      => array()
	        	));
			}else {

				// get info of child use , check has parent 

				$_UserInfo= $_UserInfo->where('user_id', $Model->child_id)->first();


				if($_UserInfo && empty($_UserInfo->parent_id)){

					// set parent_id and privilege to user if has'nt parent
					$_UserInfo->parent_id = $Model->parent_id;
					$_UserInfo->role = $Model->role_id;
					$_UserInfo->save();
					//  Save and update time_success
					$Model->time_success = $this->time();
					$Model->save();

					return Response::json(array(
		                'error'     => false,
		                'message'   => 'SUCCESS',
		                'data'      => array()
	        		));
				}else {
					return Response::json(array(
		                'error'     => true,
		                'message'   => 'HAS_PARENT',
		                'data'      => array()
	        		));
				}
			}
		}else {
			return Response::json(array(
                'error'     => true,
                'message'   => 'TOKEN_NOT_FOUND',
                'data'      => array()
    		));
		}
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */

	public function getShow()
	{
	   $UserInfo   = $this->UserInfo();
        $id = (int)$UserInfo['id'];
    
        // List Courier 
        
        $Model      = new \sellermodel\UserInfoModel;
        $Data       = $Model::where('parent_id','=',$id)->with(['user' => function ($query){
        	$query->select('id', 'email', 'fullname');
        }])->get()->toArray();
        
        
        if($Data){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $Data
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'list courier empty'
            );
        }
        
        return Response::json($contents);
	}

	/**
	 * Delete child acc
	 *
	 */
	public function postDeletechild(){
		$Data =  Input::json()->all();
		$Id = $Data[0];
		if($Id > 0){
			$Delete = UserInfoModel::where('user_id',$Id)->update(array('parent_id' => 0,'role' => 0));
			if($Delete){
				$contents = array(
	                'error'     => false,
	                'message'   => 'success'
	            );
			}else{
				$contents = array(
	                'error'     => true,
	                'message'   => 'Not delete'
	            );
			}
		}else{
			$contents = array(
                'error'     => true,
                'message'   => 'Not user'
            );
		}
		return Response::json($contents);
	}


	//set quyen cho NV
    public function postSetPrivilege(){
        $UserInfo   = $this->UserInfo(); 
        $ChildId            = Input::has('id')                ? (int)Input::get('id')        : 0;
        $Privilege          = Input::has('privilege')         ? (int)Input::get('privilege') : 0;

        if($ChildId > 0){
        	$Update = UserInfoModel::where('user_id',$ChildId)->where('parent_id',$UserInfo['id'])->update(array('role' => $Privilege));
        	if($Update){
        		$contents = array(
	                'error'     => false,
	                'message'   => 'success'
	            );
        	}else{
        		$contents = array(
	                'error'     => true,
	                'message'   => 'Not update'
	            );
        	}
        }else{
        	$contents = array(
                'error'     => true,
                'message'   => 'Not user'
            );
        }

        return Response::json($contents);
    }


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}


}
