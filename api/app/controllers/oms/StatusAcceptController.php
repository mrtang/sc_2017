<?php
namespace oms;
use DB;
use Input;
use Response;
use CourierStatusAcceptModel;

class StatusAcceptController extends \BaseController {

	public function __construct(){
        
    }
    //
    public function getIndex(){
        $itemPage   = Input::has('limit')       ? Input::get('limit')                    : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')                : 1;
        
        $Model = new CourierStatusAcceptModel;
        
        $offset = ($page - 1) * $itemPage;
        $total = $Model->count();
        $data = $Model->skip($offset)->take($itemPage)->get()->toArray();

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $data,
            'total'         => $total,
            'item_page'     => $itemPage
        );
        return Response::json($contents);
    }
    //create
    public function postCreate()
	{
		$Model              = new CourierStatusAcceptModel;
        $Data               = Input::json()->all();
        $StatusAccept       = isset($Data['status_accept_id'])        ? (int)$Data['status_accept_id']    : 0;
        $Status             = isset($Data['status_id'])           ? (int)$Data['status_id']       : 0;

        if($StatusAccept == 0 || $Status == 0){
            $contents = array(
                'error'         => true,
                'message'       => 'Bạn hãy nhập đủ dữ liệu!',
            );
            return Response::json($contents);
        }


        $DataInsert = array(
            'status_id' => $Status,
            'status_accept_id' => $StatusAccept,
            'active' => 1,
        );
        
        $Insert = $Model->insert($DataInsert);
        if($Insert){
            $contents = array(
                'error'         => false,
                'message'       => 'Thêm mới thành công!',
            );
            return Response::json($contents);
        }else{
            $contents = array(
                'error'         => true,
                'message'       => 'Không thể thêm mới!',
            );
            return Response::json($contents);
        }
	}
	//Edit
	public function postEdit($Id)
	{
		if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents);
        }

        $Model = new CourierStatusAcceptModel;

        $Data               = Input::json()->all();
        $StatusAccept       = isset($Data['status_accept_id'])        ? (int)$Data['status_accept_id']    : 0;
        $Status             = isset($Data['status_id'])           ? (int)$Data['status_id']       : 0;

        if($StatusAccept == 0 || $Status == 0){
            $contents = array(
                'error'         => true,
                'message'       => 'Bạn hãy nhập đủ dữ liệu!',
            );
            return Response::json($contents);
        }
        //
        $Model = $Model::find($Id);
        if(!empty($Model)){
            if(isset($Status))       		$Model->status_id      = $Status;
            if(isset($StatusAccept))        $Model->status_accept_id            = $StatusAccept;
            //
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
        return Response::json($contents);
	}
	//
	public function getShow($Id)
	{
        $Model       = new CourierStatusAcceptModel;
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
        
        return Response::json($contents, $statusCode);
	}
	//Delete
	public function getDestroy($Id)
	{
        $Model = new CourierStatusAcceptModel;
        $Model = $Model::find($Id);
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
        
        return Response::json($contents, $statusCode);
	}



}
?>