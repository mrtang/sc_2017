<?php
class UserConfigTransportController extends \BaseController {
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
        $Active      = isset($Data['active'])         ? $Data['active']            : 1;
        
        $Model      = new UserConfigTransportModel;
        $statusCode = 200;
        
        if(empty($UserId) || empty($Transport_id)){
            $contents = array(
                'error' => true, 'message' => 'values empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(
                    array(
                        'user_id'    => $UserId, 
                        'transport_id'          => $Transport_id, 
                        'active'         => $Active, 
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
        $Model      = new UserConfigTransportModel;
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
     * Display the specified resource by user_id.
     *
     * @param  null
     * @return Response
     */
    public function getShowbyuser()
    {
        $UserInfo   = $this->UserInfo();
        $id = (int)$UserInfo['id'];

        $Model      = new UserConfigTransportModel;
        $Model      = $Model::where('user_id', $id)->get();

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
        $Model      = new UserConfigTransportModel;
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
	/**
	 * Get config by user_id
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getTransportconfigbyuserid($id)
    {  
        $Model  = new UserConfigTransportModel;
        $Transport       = TransportModel::get(array('id','name'))->toArray();
        $arr_ts = array();
        foreach($Transport AS $v){
            $arr_ts[] = $v['id'];
        }

        $Data       = $Model::where('user_id','=',(int)$id)->get(array('id','transport_id'))->toArray();
        $output = array();
        if($Data){
            $arr_return = array();
            foreach($Transport AS $one){
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
                'data'          => $Transport
            );
        }
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }
    //
    //
    public function postAction()
    { 
        $Data               = Input::json()->all();
        $UserId     = isset($Data['user_id'])       ? (int)$Data['user_id']     : 0;
        $Transport_id       = isset($Data['transport_id'])          ? $Data['transport_id']             : 0;
        $Active      = isset($Data['active'])         ? $Data['active']            : 1;
        
        $Model      = new UserConfigTransportModel;
        $statusCode = 200;
        
        //
        $info = $Model::where('user_id','=',$UserId)->where('transport_id','=',$Transport_id)->get()->toArray();
        if(!empty($info)){
            $Delete = $Model::where('user_id','=',$UserId)->where('transport_id','=',$Transport_id)->delete();
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
                        'user_id'      => $UserId, 
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
}
?>