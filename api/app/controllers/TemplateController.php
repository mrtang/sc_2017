<?php
class TemplateController extends \BaseController {
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
       
        $Model  = new TemplateModel;
        $Data   = $Model::skip($offset)->take($itemPage)->orderBy('id','DESC')->get();
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
            'total'         => $Model::count(),
            'item_page'     => $itemPage,
            'data'          => $Data
        );
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
    //Get template by type
    public function getTemplatebytype()
    {  
        $Model  = new TemplateModel;
        $Data   = $Model::get(array('id','title','transport_id'))->toArray();
        if($Data){
            $Transport       = TransportModel::all(array('id','name'));
            if($Transport){
                foreach($Transport AS $val){
                    foreach($Data AS $value){
                        if(isset($val['id'])){
                            if($val['id'] == $value['transport_id']){
                                $OutPut[$val['name']]['type'] = $val['name'];
                                $OutPut[$val['name']]['child'][] = $value;
                            }
                        }
                        
                    }
                }
            }
        }
        $statusCode = 200;
        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'total'         => $Model::count(),
            'data'          => $OutPut
        );
        
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
        $Title  			= isset($Data['title'])    		? $Data['title']  		: null;
        $Transport_id       = isset($Data['transport_id'])  ? (int)$Data['transport_id']     : 0;
        $Code       		= isset($Data['code'])          ? $Data['code']             : null;
        $Description      	= isset($Data['description'])         ? $Data['description']            : null;
        $Content     		= isset($Data['content'])        ? $Data['content']      : null;
        
        $Model      = new TemplateModel;
        $statusCode = 200;
        
        if(empty($Title) || empty($Content) || empty($Code) || empty($Transport_id)){
            $contents = array(
                'error' => true, 'message' => 'values empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(
                    array(
                        'title'    => $Title, 
                        'transport_id'          => $Transport_id, 
                        'content'         => $Content, 
                        'code'          => strtoupper($Code), 
                        'description'        => $Description
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
        $Model      = new TemplateModel;
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
        $Title  			= isset($Data['title'])    		? $Data['title']  		: null;
        $Transport_id       = isset($Data['transport_id'])  ? (int)$Data['transport_id']     : null;
        $Code       		= isset($Data['code'])          ? $Data['code']             : null;
        $Description      	= isset($Data['description'])         ? $Data['description']            : null;
        $Content     		= isset($Data['content'])        ? $Data['content']      : null;
        $statusCode = 200;
        
        if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
            
        $Model = new TemplateModel;
        $Model = $Model::find($Id);
        if($Model){
            if(isset($Title))   $Model->title      = $Title;
            if(!empty($Transport_id))       $Model->transport_id            = $Transport_id;
            if(!empty($Content))      $Model->content           = $Content;
            if(isset($Description))      $Model->description          = $Description;
            if(!empty($Code))       $Model->code            = strtoupper($Code);
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
        $Model      = new TemplateModel;
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