<?php namespace seller;

use Validator;
use Response;
use Input;
use sellermodel\BusinessModel;

class BussinessInfoController extends \BaseController {
    
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */

	public function getIndex()
	{
		
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate()
	{
		/**
        *  Validation params
        * */
        
        $UserInfo   = $this->UserInfo();
        $UserId = (int)$UserInfo['id'];
        
        Validator::getPresenceVerifier()->setConnection('sellerdb');
        
        $validation = Validator::make(Input::json()->all(), array(
            'id'        => 'sometimes|numeric|exists:business_info,id',
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }
        
        /**
         * Get Data 
         * */
         
        $Name               = Input::json()->get('name');
        $Website            = Input::json()->get('website');
        $Industry           = Input::json()->get('industry');
        $Ceo                = Input::json()->get('ceo');
        $License            = Input::json()->get('license');
        $TaxCode            = Input::json()->get('taxCode');
        $Phone              = Input::json()->get('phone');
        $CityId             = Input::json()->get('city_id');
        $DistrictId         = Input::json()->get('district_id');
        $Address            = Input::json()->get('address');
        
        $DataCreate         = array('user_id' => $UserId);
        
        $Model              = new BusinessModel;
        
        $Data               = $Model::firstOrCreate($DataCreate);
        
        if(!empty($Name))       $Data->name         = $Name;
        if(isset($Website))     $Data->website      = $Website;
        if(isset($Industry))    $Data->industry     = $Industry;
        if(isset($Ceo))         $Data->ceo          = $Ceo;
        if(isset($License))     $Data->license      = $License;
        
        if(isset($TaxCode))     $Data->taxCode      = $TaxCode;
        if(isset($Phone))       $Data->phone        = $Phone;
        if(!empty($CityId))     $Data->city_id      = $CityId;
        if(!empty($DistrictId)) $Data->district_id  = $DistrictId;
        if(isset($Address))     $Data->address      = $Address;
        
        $Data->time_update  = $this->time();
        $Update = $Data->save();
        
        if($Update){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'        => $Data->id
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'update false',
                'data'      => array()
            );
        }
        
        return Response::json($contents);
        
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getShow()
	{
        /**
        *  Validation params
        * */
        
        $UserInfo   = $this->UserInfo();
        $id = (int)$UserInfo['id'];
        
		$Model      = new BusinessModel;

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $Model::where('user_id','=',$id)->first()
        );

        
        return Response::json($contents);
	}
}
