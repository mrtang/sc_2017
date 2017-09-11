<?php
class UserScenarioConfigController extends \BaseController {
    private $domain = '*';
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
     
    public function __construct(){
        
    }
    /**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate()
	{ 
        $Data       = Input::json()->all();
        $UserId     = isset($Data['user_id'])       ? (int)$Data['user_id']     : 0;
        $Transport_id       = isset($Data['transport_id'])          ? $Data['transport_id']             : 0;
        $Scenario_id      = isset($Data['scenario_id'])         ? $Data['scenario_id']            : 0;
        $Active      = isset($Data['active'])         ? $Data['active']            : 1;
        
        $Model      = new UserScenarioConfigModel;
        $statusCode = 200;
        
        if(empty($UserId) || empty($Transport_id) || empty($Scenario_id)){
            $contents = array(
                'error' => true, 'message' => 'values empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(
                    array(
                        'user_id'    => $UserId, 
                        'transport_id'          => $Transport_id, 
                        'scenario_id'         => $Scenario_id, 
                        'active'			=> $Active
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
        $Model      = new UserScenarioConfigModel;
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
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getDestroy($Id)
	{
        $Model      = new UserScenarioConfigModel;
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
	//
    public function getIndex()
	{  
        $page       = Input::has('page') ? (int)Input::get('page') : 1;
        $itemPage   = Input::has('item_page') ? (int)Input::get('item_page') : 20;
        $offset     = ($page - 1)*$itemPage;
       
        $Model  = new UserScenarioConfigModel;
        $Data   = $Model::skip($offset)->take($itemPage)->orderBy('id','DESC')->get();
        if($Data){
        	$Scenario       = ScenarioModel::all(array('id','name'));
        	if($Scenario){
                foreach($Scenario as $val){
                    $LScenario[$val['id']] = $val['name'];
                }
                foreach($Data as $key => $val){
                    if (isset($LScenario[$val['scenario_id']])){
                        $val->scenario_name = $LScenario[$val['scenario_id']];
                    }
                }
            }
            $Transport       = TransportModel::all(array('id','name'));
        	if($Transport){
                foreach($Transport as $val){
                    $LTransport[$val['id']] = $val['name'];
                }
                foreach($Data as $key => $val){
                    if (isset($LTransport[$val['transport_id']])){
                        $val->transport_name = $LTransport[$val['transport_id']];
                    }
                }
            }
            $User       = UserModel::all(array('id','fullname'));
        	if($User){
                foreach($User as $val){
                    $LUser[$val['id']] = $val['fullname'];
                }
                foreach($Data as $key => $val){
                    if (isset($LUser[$val['user_id']])){
                        $val->user_name = $LUser[$val['user_id']];
                    }
                }
            }
        }
        $statusCode = 200;
        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'total'         => $Model::count(),
            'item_page'     => $itemPage,
            'data'          => $Data
        );
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
	/**
	 * Get config by user_id
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getScenarioconfigbyuserid($id)
    {  
        $Model  = new UserScenarioConfigModel;
        $Data       = $Model::where('user_id','=',(int)$id)->get(array('id','transport_id','scenario_id','active'));
        if($Data){
            $Transport       = TransportModel::all(array('id','name'));
            if($Transport){
                foreach($Transport as $val){
                    $LTransport[$val['id']] = $val['name'];
                }
                foreach($Data as $key => $val){
                    if (isset($LTransport[$val['transport_id']])){
                        $val->transport_name = $LTransport[$val['transport_id']];
                    }
                }
            }
            $Scenario       = ScenarioModel::all(array('id','name'));
        	if($Scenario){
                foreach($Scenario as $val){
                    $LScenario[$val['id']] = $val['name'];
                }
                foreach($Data as $key => $val){
                    if (isset($LScenario[$val['scenario_id']])){
                        $val->scenario_name = $LScenario[$val['scenario_id']];
                    }
                }
            }
        }
        $statusCode = 200;
        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        );
        
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
        $Scenario           = isset($Data['scenario_id'])           ? $Data['scenario_id']          : null;
        $Transport           = isset($Data['transport_id'])         ? $Data['transport_id']            : null;
        $Active           = isset($Data['active'])         ? $Data['active']            : null;
        $statusCode = 200;
        
        if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
            
        $Model = new UserScenarioConfigModel;
        $Model = $Model::find($Id);
        if($Model){
            if(isset($Scenario))   $Model->scenario_id      = $Scenario;
            if(isset($Transport))      $Model->transport_id          = $Transport;
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