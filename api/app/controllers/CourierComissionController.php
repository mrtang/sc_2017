<?php

class CourierComissionController extends \BaseController {
    private $domain = '*';
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{  
        $page       = Input::has('page') ? (int)Input::get('page') : 1;
        $itemPage   = Input::has('item_page') ? (int)Input::get('item_page') : 20;
        $offset     = ($page - 1)*$itemPage;
       
        $Model  = new CourierComissionModel;
        $Data   = $Model::skip($offset)->take($itemPage)->orderBy('id','DESC')->get();
        if($Data){
            if (Cache::has('courier_cache')){
                $ListCourier    = Cache::get('courier_cache');
                
            }else{
                $Courier        = new CourierModel;
                $ListCourier    = $Courier::all(array('id','name'));
            }
            
            if($ListCourier){
                foreach($ListCourier as $val){
                    $LCourier[$val['id']]   = $val['name'];
                }
                foreach($Data as $key => $val){
                    if (isset($LCourier[$val['courier_id']])){
                        $val->courier_name = $LCourier[$val['courier_id']];
                    }
                }
            }
        }
        $statusCode = 200;
        $contents   = array(
            'error'         => false,
            'message'       => '',
            'total'         => $Model::count(),
            'item_page'     => $itemPage,
            'data'          => $Data
        );
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate()
	{ 
        $Data       = Input::json()->all();
        $CourierId  = isset($Data['courier_id']) ? (int)$Data['courier_id'] : null;
        $Quota      = isset($Data['quota']) ? (int)$Data['quota'] : 0;
        $Discount   = isset($Data['discount']) ? (int)$Data['discount'] : 0;
        $FromDate   = isset($Data['from_date']) ? trim($Data['from_date']) : null;
        $ToDate     = isset($Data['to_date']) ? trim($Data['to_date']) : null;
        $Status     = isset($Data['status']) ? (int)$Data['status'] : 0;
        
        $Model = new CourierComissionModel;
        $statusCode = 200;
        
        if(empty($CourierId) || empty($FromDate) || empty($ToDate)){
            $contents = array(
                'error' => true, 'message' => 'values empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(
                    array(
                        'courier_id'    => $CourierId, 
                        'quota'         => $Quota, 
                        'discount'      => $Discount, 
                        'from_date'     => strtotime(str_replace('/', '-', $FromDate)),
                        'to_date'       => strtotime(str_replace('/', '-', $ToDate)),
                        'status'        => $Status
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
	public function getShow($id)
	{
        $Model = new CourierComissionModel;
        $Model = $Model::find($id);
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
	public function postEdit($id)
	{
		$Data       = Input::json()->all();
        $CourierId  = isset($Data['courier_id']) ? (int)$Data['courier_id'] : null;
        $Quota      = isset($Data['quota']) ? (int)$Data['quota'] : null;
        $Discount   = isset($Data['discount']) ? (int)$Data['discount'] : null;
        $FromDate   = isset($Data['from_date']) ? trim($Data['from_date']) : null;
        $ToDate     = isset($Data['to_date']) ? trim($Data['to_date']) : null;
        $Status     = isset($Data['status']) ? (int)$Data['status'] : null;
        $statusCode = 200;
        
        if($id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
            
        $Model = new CourierComissionModel;
        $Model = $Model::find($id);
        if($Model){
            if(!empty($CourierId))  $Model->courier_id      = $CourierId;
            if(isset($Quota))       $Model->quota           = $Quota;
            if(isset($Discount))    $Model->discount        = $Discount;
            if(!empty($FromDate))   $Model->from_date       = strtotime(str_replace('/', '-', $FromDate));
            if(!empty($ToDate))     $Model->to_date         = strtotime(str_replace('/', '-', $ToDate));
            if(isset($Status))      $Model->status          = $Status;
            
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
        $Model      = new CourierComissionModel;
        $Model      = $Model::find($id);
        $statusCode = 200;
        
        if($Model){
            $Delete = $Model->delete();
            if($Delete){
                $contents = array(
                    'error'     => false,
                    'message'   => 'success'
                );
            }else{
                $contents   = array(
                    'error'     => true,
                    'message'   => 'delete error'
                );
            }
        }else{
            $contents   = array(
                'error'     => true,
                'message'   => 'not exits'
            );
        }
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}


}
