<?php
class UserGroupController extends \BaseController {
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
       
        $Model  = new UserGroupModel;
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
        $Group  			= isset($Data['group'])    		? $Data['group']  		: null;
        $User             = isset($Data['user'])        ? (int)$Data['user']     : null;
        
        $Model      = new UserGroupModel;
        $statusCode = 200;
        
        if(empty($Group)){
            $contents = array(
                'error' => true, 'message' => 'Name is not empty!!!'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(
                    array(
                        'group_id'      => $Group, 
                        'user_id'          => $User 
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
        $Data               = Input::json()->all();
        $Group              = isset($Data['group'])         ? $Data['group']        : null;
        $User             = isset($Data['user'])        ? (int)$Data['user']     : null;
        
        $Model      = new UserGroupModel;
        $statusCode = 200;
        
        //
        $info = $Model::where('user_id','=',$User)->where('group_id','=',$Group)->get()->toArray();
        if(!empty($info)){
            $Delete = $Model::where('user_id','=',$User)->where('group_id','=',$Group)->delete();
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
                        'user_id'      => $User, 
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
	//
    public function getGroupbyuser($id){
        $Model  = new UserGroupModel;
        //List privilege
        $list_group = GroupModel::get(array('id','group_name'))->toArray();
        $arr_gr = array();
        foreach($list_group AS $v){
            $arr_gr[] = $v['id'];
        }
        $Data   = $Model::where('user_id','=',$id)->get()->toArray();
        if($Data){
            $output = array();
            $arr_return = array();
            foreach($list_group AS $one){
                $output[$one['id']] = $one;
            }
            foreach($Data AS $val){
                if(in_array($val['group_id'], $arr_gr)){
                    $output[$val['group_id']]['checked'] = 1;
                }else{
                    $output[$val['group_id']]['checked'] = 0;
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
                'data'          => $list_group
            );
        }
        
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }


}
?>