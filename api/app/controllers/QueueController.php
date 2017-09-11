<?php
class QueueController extends \BaseController {
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
        $search   = Input::has('search') ? Input::get('search') : '';
        $offset     = ($page - 1)*$itemPage;
        $timeStart = $this->time() - 7*86400;
       
        $Model  = new QueueModel;
        $Model = $Model::where('time_create','>',$timeStart);
        if(!empty($search)){
            $Model = $Model->where('received',$search);
        }
        $total      = $Model->count();
        $Data   = $Model->skip($offset)->take($itemPage)->orderBy('id','DESC')->get();
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
            $Template       = TemplateModel::all(array('id','title'));
        	if($Template){
                foreach($Template as $val){
                    $LTemplate[$val['id']] = $val['title'];
                }
                foreach($Data as $key => $val){
                    if (isset($LTemplate[$val['template_id']])){
                        $val->template_name = $LTemplate[$val['template_id']];
                    }
                }
            }
            $User       = User::all(array('id','fullname'));
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
            'item_page'     => $itemPage,
            'total'         => $total,
            'data'          => $Data
        );
        
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
        $Model      = new QueueModel;
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
        $Model      = new QueueModel;
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

    /*
     * Count Notify
     */
    public function getCount(){return Response::json(['error'     => true, 'data' => 0]);
        if(($this->time() % 180) != 0){
            return Response::json(['error'     => true, 'data' => 0]);
        }

        $Model      = new QueueModel;
        $UserInfo   = $this->UserInfo();

        $contents    = array(
            'error'     => false,
            'data'      => $Model->where('transport_id',3)
                ->where('user_id',(int)$UserInfo['id'])
                ->where('view',0)
                ->where('time_create','>=',$this->time() - 86400*7)
                ->count()
        );
        return Response::json($contents);
    }
    //
    public function getEmailbyuser($userId){
        $Model      = new QueueModel;
        if((int)$userId < 1){
            $contents = array(
                'error'     => true,
                'message'   => 'Fail!!!'
            );
            return Response::json($contents);
        }
        $listEmail = $Model::where('user_id',$userId)->with(['scenario'])->take(5)->orderBy('time_create','DESC')->get(array('scenario_id', 'received','time_create','time_success','status'));
        if(!empty($listEmail)){
            $contents = array(
                'error'     => false,
                'message'   => 'Success!!!',
                'data'      => $listEmail
            );
            return Response::json($contents);
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Empty data!!!'
            );
            return Response::json($contents);
        }
    }
}
?>