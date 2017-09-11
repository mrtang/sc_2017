<?php
namespace oms;
use DB;
use Input;
use Response;
use omsmodel\NotifyFacebookModel;

class FbController extends \BaseController {
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
       
        $Model  = new NotifyFacebookModel;
        $Data   = $Model::skip($offset)->take($itemPage)->orderBy('time_create','DESC')->get()->toArray();
        
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
     * Create.
     *
     * @return Response
     */
    public function postCreate()
    { 
        $Data               = Input::json()->all();
        $Title               = isset($Data['title'])          ? $Data['title']         : null;
        $Link        = isset($Data['link'])         ? $Data['link']            : null;
        $Content        = isset($Data['content'])         ? $Data['content']            : null;
        
        $Model      = new NotifyFacebookModel;
        $statusCode = 200;
        
        if(empty($Title) || empty($Content)){
            $contents = array(
                'error' => true, 'message' => 'values empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(
                    array(
                        'title'    => $Title, 
                        'href'        => $Link,
                        'content'   => $Content,
                        'time_create'  => $this->time()
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
        $Model      = new NotifyFacebookModel;
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
        $Name           = isset($Data['name'])          ? $Data['name']         : null;
        $Description        = isset($Data['description'])         ? $Data['description']            : null;
        $statusCode = 200;
        
        if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
            
        $Model = new NotifyFacebookModel;
        $Model = $Model::find($Id);
        if($Model){
            if(isset($Name))   $Model->name      = $Name;
            if(isset($Description))      $Model->description          = $Description;
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
        $Model      = new NotifyFacebookModel;
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