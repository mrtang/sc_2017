<?php
class SystemScenarioConfigController extends \BaseController {
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
       
        $Model  = new SystemScenarioConfigModel;
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
	//
		/**
	 * Create.
	 *
	 * @return Response
	 */
	public function postCreate()
	{ 
        $Data       		= Input::json()->all();
        $Scenario_id       = isset($Data['scenario_id'])  ? (int)$Data['scenario_id']     : 0;
        $Active       		= isset($Data['active'])          ? $Data['active']             : 1;
        $Transport_id      	= isset($Data['transport_id'])         ? $Data['transport_id']            : 0;
        
        $Model      = new SystemScenarioConfigModel;
        $statusCode = 200;
        
        if(empty($Scenario_id) || empty($Transport_id)){
            $contents = array(
                'error' => true, 'message' => 'values empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(
                    array(
                        'active'    => $Active, 
                        'transport_id'          => $Transport_id, 
                        'scenario_id'         => $Scenario_id 
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
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function postEdit($Id)
	{
		$Data       = Input::json()->all();
        $Scenario_id       = isset($Data['scenario_id'])  ? (int)$Data['scenario_id']     : null;
        $Active       		= isset($Data['active'])          ? $Data['active']             : 1;
        $Transport_id      	= isset($Data['transport_id'])         ? $Data['transport_id']            : null;
        $statusCode = 200;
        
        if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
            
        $Model = new SystemScenarioConfigModel;
        $Model = $Model::find($Id);
        if($Model){
            if(isset($Scenario_id))     $Model->scenario_id      = $Scenario_id;
            if(isset($Transport_id))    $Model->transport_id          = $Transport_id;
            if(isset($Active))          $Model->active          = $Active;
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
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getShow($Id)
	{
        $Model      = new SystemScenarioConfigModel;
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
	 * Remove.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getDestroy($Id)
	{
        $Model      = new SystemScenarioConfigModel;
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
    public function postAction()
    { 
        $Data               = Input::json()->all();
        $Scenario_id       = isset($Data['scenario_id'])  ? (int)$Data['scenario_id']     : null;
        $Active             = isset($Data['active'])          ? $Data['active']             : 1;
        $Transport_id       = isset($Data['transport_id'])         ? $Data['transport_id']            : null;
        
        $Model      = new SystemScenarioConfigModel;
        $statusCode = 200;
        
        //
        $info = $Model::where('scenario_id','=',$Scenario_id)->where('transport_id','=',$Transport_id)->get()->toArray();
        if(!empty($info)){
            $Delete = $Model::where('scenario_id','=',$Scenario_id)->where('transport_id','=',$Transport_id)->delete();
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
                        'scenario_id'      => $Scenario_id, 
                        'transport_id'          => $Transport_id,
                        'active'            => $Active
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
    public function getTransport($id){
        $Model  = new SystemScenarioConfigModel;
        //List 
        $list_transport = TransportModel::get(array('id','name'))->toArray();
        $arr_ts = array();
        foreach($list_transport AS $v){
            $arr_ts[] = $v['id'];
        }
        $Data   = $Model::where('scenario_id','=',$id)->get()->toArray();
        if($Data){
            $output = array();
            $arr_return = array();
            foreach($list_transport AS $one){
                $output[$one['id']] = $one;
            }
            foreach($Data AS $val){
                if(in_array($val['transport_id'], $arr_ts)){
                    $output[$val['transport_id']]['checked'] = 1;
                }else{
                    $output[$val['transport_id']]['checked'] = 0;
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
                'data'          => $list_transport
            );
        }
        
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }
    public function getSysconfigbyidscenario($id)
    {  
        $Model  = new SystemScenarioConfigModel;
        $Data       = $Model::where('scenario_id','=',(int)$id)->get(array('id','transport_id'));
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
        }
        $statusCode = 200;
        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        );
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }

}
?>