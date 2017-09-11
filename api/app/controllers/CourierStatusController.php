<?php

class CourierStatusController extends \BaseController {
    
    private $domain = '*';
    
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
		//
        $itemPage   = Input::has('limit')       ? Input::get('limit')                    : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')                : 1;
        $courier    = Input::has('courier')     ? (int)Input::get('courier')             : 0;
        
        $Model = new CourierStatusModel;
        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }

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


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate()
	{
		$Model              = new CourierStatusModel;
        $Data               = Input::json()->all();
        $CourierId          = isset($Data['courier_id'])        ? (int)$Data['courier_id']    : 0;
        $Type               = isset($Data['type'])           ? (int)$Data['type']       : 0;
        $CourierStatus      = isset($Data['courier_status'])           ? $Data['courier_status']       : '';
        $SCStatus              = isset($Data['sc_status'])           ? (int)$Data['sc_status']       : 0;

        if($CourierId == 0 || $Type == 0 || $CourierStatus == '' || $SCStatus == 0){
            $contents = array(
                'error'         => true,
                'message'       => 'Bạn hãy nhập đủ dữ liệu!',
            );
            return Response::json($contents);
        }


        $DataInsert = array(
            'courier_id' => $CourierId,
            'type' => $Type,
            'sc_status' => $SCStatus,
            'courier_status' => $CourierStatus,
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


	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getShow($Id)
	{
    
        // List Courier 
        
        $Model       = new CourierStatusModel;
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


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function postEdit($Id)
	{
		if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents);
        }

        $Model = new CourierStatusModel;

        $Data               = Input::json()->all();
        $CourierId          = isset($Data['courier_id'])        ? (int)$Data['courier_id']    : 0;
        $Type               = isset($Data['type'])           ? (int)$Data['type']       : 0;
        $CourierStatus      = isset($Data['courier_status'])           ? $Data['courier_status']       : '';
        $SCStatus              = isset($Data['sc_status'])           ? (int)$Data['sc_status']       : 0;

        if($CourierId == 0 || $Type == 0 || $CourierStatus == '' || $SCStatus == 0){
            $contents = array(
                'error'         => true,
                'message'       => 'Bạn hãy nhập đủ dữ liệu!',
            );
            return Response::json($contents);
        }
        //
        $Model = $Model::find($Id);
        if(!empty($Model)){
            if(isset($CourierId))       $Model->courier_id      = $CourierId;
            if(isset($Type))            $Model->type            = $Type;
            if(isset($CourierStatus))            $Model->courier_status            = $CourierStatus;
            if(isset($SCStatus))           $Model->sc_status           = $SCStatus;
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
	public function getDestroy($id)
	{
        $Model = new CourierStatusModel;
        $Model = $Model::find($id);
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
