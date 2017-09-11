<?php
class GroupPrivilegeController extends \BaseController {
    private $domain = '*';
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
     
    public function __construct(){
        
    }

    /**
	 * Create.
	 *
	 * @return Response
	 */
	public function postCreate()
	{ 
        $Data       		= Input::json()->all();
        $Privilege  	    = isset($Data['privilege'])    		? (int)$Data['privilege']  		: null;
        $Group              = isset($Data['group'])        ? (int)$Data['group']     : null;
        
        $Model      = new GroupPrivilegeModel;
        $statusCode = 200;
        
        if(empty($Privilege)){
            $contents = array(
                'error' => true, 'message' => 'Privilege is not empty!!!'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(
                    array(
                        'privilege_id'      => $Privilege, 
                        'group_id'          => $Group 
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

	public function postAction()
	{ 
        $Data       		= Input::json()->all();
        $Privilege  	    = isset($Data['privilege'])    		? (int)$Data['privilege']  		: null;
        $Group              = isset($Data['group'])        ? (int)$Data['group']     : null;
        
        $Model      = new GroupPrivilegeModel;
        $statusCode = 200;
        
        //
        $info = $Model::where('privilege_id','=',$Privilege)->where('group_id','=',$Group)->get()->toArray();
        if(!empty($info)){
        	$Delete = $Model::where('privilege_id','=',$Privilege)->where('group_id','=',$Group)->delete();
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
        	$Id = $Model::insertGetId(
                    array(
                        'privilege_id'      => $Privilege, 
                        'group_id'          => $Group 
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
        }
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}

    public function getPrivilegebygroup($id){
    	$Model  = new GroupPrivilegeModel;
    	//List privilege
        $list_privilege = UserPrivilegeModel::get(array('id','privilege_name'))->toArray();
        $arr_pri = array();
        foreach($list_privilege AS $v){
        	$arr_pri[] = $v['id'];
        }
        $Data   = $Model::where('group_id','=',$id)->get()->toArray();
        if($Data){
        	$output = array();
        	$arr_return = array();
        	foreach($list_privilege AS $one){
        		$output[$one['id']] = $one;
        	}
        	foreach($Data AS $val){
        		if(in_array($val['privilege_id'], $arr_pri)){
        			$output[$val['privilege_id']]['checked'] = 1;
        		}else{
        			$output[$val['privilege_id']]['checked'] = 0;
        		}
        	}
        	foreach($output AS $v){
        		$arr_return[] = $v;
        	}
            $statusCode = 200;
	        $contents = array(
	            'error'         => false,
	            'message'       => 'success',
	            'data'          => $arr_return
	        );
        }else{
        	$statusCode = 200;
	        $contents = array(
	            'error'         => false,
	            'message'       => 'success',
	            'data'          => $list_privilege
	        );
        }
        
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }
}
?>