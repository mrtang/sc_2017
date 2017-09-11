<?php

class DiscountConfigController extends \BaseController {
    private $domain = '*';
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
		$page       = Input::has('page')        ? (int)Input::get('page') : 1;
        $itemPage   = Input::has('limit')       ? Input::get('limit') : 20;
        $CourierId  = Input::has('courier_id')  ? (int)Input::get('courier_id') : null;
        $statusCode = 200;
        
        if($itemPage == 'all'){
            $itemPage = 9999;
            $offset   = 0;
        }else{
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
        }
       
        
        $Model = new DiscountConfigModel;
        
        if(!empty($CourierId)){
            $Model  = $Model->where('courier_id','=',$CourierId);
        }
        
        $Total  = $Model->count();
        $Data   = $Model->skip($offset)->take($itemPage)->with('discount_type')->get();
        
        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'total'     => $Total,
            'data'      => $Data
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
        $Data                   = Input::json()->all();
        $CourierId              = isset($Data['courier_id'])            ? (int)$Data['courier_id']      : null;
        $FromDate               = isset($Data['from_date'])             ? trim($Data['from_date'])      : null;
        $ToDate                 = isset($Data['to_date'])               ? trim($Data['to_date'])        : null;
        $TypeId                 = isset($Data['type_id'])               ? trim($Data['type_id'])        : null;
        $Code                   = isset($Data['code'])                  ? trim($Data['code'])           : null;
        $ValueType              = isset($Data['value_type'])            ? trim($Data['value_type'])     : null;
        $Value                  = isset($Data['value'])                 ? trim($Data['value'])          : null;
        $UseNumber              = isset($Data['use_number'])            ? (int)$Data['use_number']      : null;
        $Active                 = isset($Data['active'])                ? (int)$Data['active']          : 1;
        $statusCode             = 200;
        
      
        $Model = new DiscountConfigModel;
        $statusCode = 200;
        
        if(empty($CourierId) || empty($FromDate) || empty($TypeId) || empty($Code) || empty($ValueType) || empty($Value) || empty($UseNumber)){
            $contents = array(
                'error' => true, 'message' => 'values empty'
            );
            
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
            
        $Id = $Model::insertGetId(
            array(
                'courier_id'            => $CourierId,
                'from_date'             => $FromDate,
                'to_date'               => $ToDate,
                'type_id'               => $TypeId,
                'code'                  => $Code,
                'value_type'            => $ValueType,
                'value'                 => $Value,
                'use_number'           => $UseNumber,
                'active'                => $Active
                )
        );
            
        if($Id){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'id'        => $Id
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'insert false'
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
        $Model      = new DiscountConfigModel;
        $Data       = $Model::find($id)->with(array('courier' => function($query){
            $query->get(array('id','name'));
            
        },'discount_type'))->first();
 
        $statusCode = 200;
        
        if($Model){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $Data
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
		$Data                   = Input::json()->all();
        $CourierId              = isset($Data['courier_id'])            ? (int)$Data['courier_id']      : null;
        $FromDate               = isset($Data['from_date'])             ? trim($Data['from_date'])      : null;
        $ToDate                 = isset($Data['to_date'])               ? trim($Data['to_date'])        : null;
        $TypeId                 = isset($Data['type_id'])               ? trim($Data['type_id'])        : null;
        $Code                   = isset($Data['code'])                  ? trim($Data['code'])           : null;
        $ValueType              = isset($Data['value_type'])            ? trim($Data['value_type'])     : null;
        $Value                  = isset($Data['value'])                 ? trim($Data['value'])          : null;
        $UseNumber              = isset($Data['use_number'])            ? (int)$Data['use_number']      : null;
        $Active                 = isset($Data['active'])                ? (int)$Data['active']          : 1;
        $statusCode             = 200;
       
        if($id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Model = DiscountConfigModel::find($id);
        if($Model){
            if(!empty($CourierId))          $Model->courier_id              = $CourierId;
            if(!empty($FromDate))           $Model->from_date               = $FromDate;
            if(!empty($ToDate))             $Model->to_date                 = $ToDate;
            if(!empty($TypeId))             $Model->type_id                 = $TypeId;
            if(!empty($Code))               $Model->code                    = $Code;
            if(!empty($ValueType))          $Model->value_type              = $ValueType;
            if(isset($Value))               $Model->value                   = $Value;
            if(isset($UseNumber))           $Model->use_number              = $UseNumber;
            if(isset($Active))              $Model->active                  = $Active;

            $Update = $Model->save();
            
            if($Update){
                $contents = array(
                    'error'     => false,
                    'message'   => 'success'
                );
            }else{
                $contents = array(
                    'error' => true,
                    'message' => 'edit error'
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
        $Model      = new DiscountConfigModel;
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
                $contents = array(
                    'error'     => true,
                    'message'   => 'del false'
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
     *  Discount Type
     * */
    public function getType()
    {
        
		$page       = Input::has('page')        ? (int)Input::get('page') : 1;
        $itemPage   = Input::has('limit')       ? Input::get('limit') : 20;
        $statusCode = 200;
        
        if($itemPage == 'all'){
            $itemPage = 9999;
            $offset   = 0;
        }else{
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
        }
        
        $Model = new DiscountTypeModel;
        
        $Total  = $Model->count();
        $Data   = $Model->skip($offset)->take($itemPage)->get(array('id','name'));
        
        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'total'     => $Total,
            'data'      => $Data
        );
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }
    
    // Create
    public function postCreatetype()
    {
        // validation data
        Validator::getPresenceVerifier()->setConnection('courierdb');
        $validation = Validator::make(Input::all(), 
        array(
            'name'      => 'required|max:50|unique:discount_type,name'
        ));
        
        // Check và báo invalid
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()), 200);
        }
        
        $Model = new DiscountTypeModel;
        
    }


}
