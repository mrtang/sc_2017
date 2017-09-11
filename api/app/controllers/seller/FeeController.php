<?php namespace seller;

use Validator;
use Response;
use Input;
use sellermodel\FeeModel;

class FeeController extends \BaseController {
    
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		//
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate()
	{  
	    $UserInfo   = $this->UserInfo();
        $UserId     = (int)$UserInfo['id'];
        
		
		/**
        *  Validation params
        * */
        
        Validator::getPresenceVerifier()->setConnection('sellerdb');
        
        $validation = Validator::make(Input::json()->all(), array(
            'id'                    => 'sometimes|numeric|exists:fee_config,id',
            'cod_fee'               => 'sometimes|numeric',
            'shipping_fee'          => 'sometimes|numeric',
            'shipping_cost_value'   => 'sometimes|numeric'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }
        
        /**
         * Get Data 
         **/
         
        $Id                 = Input::json()->get('id');
        $CoD                = Input::json()->get('cod_fee');
        $FeeValue           = Input::json()->get('shipping_cost_value');
        $Fee                = Input::json()->get('shipping_fee');
        
        $DataCreate         = array('user_id' => $UserId);
        
        $Model              = new FeeModel;
        
        $Data               = $Model::firstOrCreate($DataCreate);
        
        if(!empty($CoD))            $Data->cod_fee          = $CoD;
        
        if(!empty($Fee)){
            $Data->shipping_fee    = $Fee;
            
            if($Fee == 1){
                if(isset($FeeValue)){
                    $Data->shipping_cost_value  = $FeeValue;
                };
            }
        }   
        
        $Update = $Data->save();
        
        if($Update){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'id'        => $Data->id
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'update false'
            );
        }
        
        return Response::json($contents);
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
	public function getShow()
	{
		$UserInfo   = $this->UserInfo();
        $id         = (int)$UserInfo['id'];
        
		$Model      = new FeeModel;
        $Data       = $Model::where('user_id','=',$id)->first();
        
        if($Data){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $Data
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'not exits',
                'data'      => array()
            );
        }
        
        return Response::json($contents);
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
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
	public function destroy($id)
	{
		//
	}


}
