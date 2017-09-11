<?php
class AreaLocationController extends \BaseController {
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
	   echo ":'("; die;
	}
	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate()
	{   $statusCode = 200;
        /**
         *  Validation params
         * */
         
        Validator::getPresenceVerifier()->setConnection('courierdb'); // set connection
        $messages = array(
            'area_id.required'      => 'We need to know area code !',
            'area_id.numeric'       => 'Area code not a number',
            'area_id.exists'        => 'Area not exist or incorrect !',
            'province_id.required'  => 'We need to know province code  !',
            'province_id.numeric'   => 'Province code not a number !',
        );
        
        $validation = Validator::make(Input::json()->all(), array(
            'area_id'       => 'required|numeric|exists:courier_area,id',
            'province_id'   => 'required|numeric',
            'district_id'   => 'sometimes|numeric',
            'location_id'   => 'sometimes|numeric|in:1,2,3,4,5',
            'active'        => 'sometimes|required|boolean',
            
        ),$messages);
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()), $statusCode);
        }
        
        /**
         * Get Data 
         * */
        $AreaId         = Input::json()->get('area_id');
        $ProvinceId     = Input::json()->get('province_id');
        $DistrictId     = Input::json()->get('district_id');
        $LocationId     = Input::json()->get('location_id');
        $Active         = Input::json()->get('active');
        
        $AreaLocation   = new AreaLocationModel;
        $DataCreate     = array('area_id'=>$AreaId, 'province_id'=>$ProvinceId);
        
        if(!empty($DistrictId)){
            $DataCreate['district_id']  = $DistrictId;
        }else{
            $DataCreate['district_id']  = 0;
        }
        
        $Location            = $AreaLocation::firstOrCreate($DataCreate);
        if(!empty($Location)){
            if(isset($LocationId))          $Location->location_id   = $LocationId;
            if(isset($Active))              $Location->active        = $Active;
            
            $Update = $Location->save();
            if($Update){
                
                if(empty($DistrictId) && isset($Active)){
                    $UpdateAllbyCity    = $AreaLocation::where('area_id','=',$AreaId)
                                                          ->where('province_id', '=',$ProvinceId)
                                                          ->update(array('active' => $Active));
                }elseif(!empty($DistrictId) && isset($Active) && ($Active == 1)){
                    $UpdateParents      = $AreaLocation::where('area_id','=',$AreaId)
                                                          ->where('province_id', '=',$ProvinceId)
                                                          ->where('district_id', '=',0)
                                                          ->update(array('active' => $Active));
                }
                
                $contents = array(
                    'error'     => false,
                    'message'   => 'success',
                    'id'        => $Location->id
                );
            }else{
                $contents = array(
                    'error' => true,
                    'message' => 'update error'
                );
            }
        }else{
            $contents = array(
                'error' => true,
                'message' => 'check error'
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
        $Model      = new AreaLocationModel;
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
	 * Delete.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getDestroy($id)
	{
		$Model = new AreaLocationModel;
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
     *  get location by area
     * 
     *  $id   ---  area_id
     **/
    public function getLocationbyarea($id){
        $statusCode = 200;
        
        /**
         *  Validation params
         * */
        Validator::getPresenceVerifier()->setConnection('courierdb'); // set connection
        $messages = array(
            'area_id.required'      => 'We need to know service code !',
            'area_id.numeric'       => 'Area code not a number',
            'area_id.exists'        => 'Area not exist or incorrect !'
        );
        
        $validation = Validator::make(array('area_id' => $id), array(
            'area_id'    => 'required|numeric|exists:courier_area,id'
        ),$messages);
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()), $statusCode);
        }
        
        /**
         *  get value
         **/
         
		$Model      = new AreaLocationModel;
		$Data 		= $Model::where('area_id','=',(int)$id)
                            ->where('district_id','<>',0)
                            ->with(array('area' => function($query){
                                $query->get(array('id','name'));
                            },'province' => function($query){
                                $query->get(array('id','city_name'));
                            },'district' => function($query){
                                $query->get(array('id','district_name'));
                            }))
                            ->get(array('id', 'area_id', 'district_id', 'location_id', 'province_id'));
        
		if($Data){
            foreach($Data as $key => $val){
                if(isset($val['area']) && isset($val['area']['name'])){
                    $Data[$key]['area_name']    = $val['area']['name'];
                }else{
                    $Data[$key]['area_name']    = '';
                }
                
                if(isset($val['province']) && isset($val['province']['city_name'])){
                    $Data[$key]['province_name']    = $val['province']['city_name'];
                }else{
                    $Data[$key]['province_name']    = '';
                }
                
                if(isset($val['district']) && isset($val['district']['district_name'])){
                    $Data[$key]['district_name']    = $val['district']['district_name'];
                }else{
                    $Data[$key]['district_name']    = '';
                }
                
                unset($Data[$key]['area']);
                unset($Data[$key]['province']);
                unset($Data[$key]['district']);
                
            }
            
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
     *  Check Value Input
     * */
     // Check courier
     private function CheckCourier($id){
        Validator::getPresenceVerifier()->setConnection('courierdb'); // set connection
        
        // check courier
        $messages   = array(
            'courier_id.required'   => 'We need to know courier code',
            'courier_id.numeric'    => 'Courier code not a number',
            'courier_id.exists'     => 'Courier code not exist',
        );
        $validation = Validator::make(array('courier_id' => $id), array(
            'courier_id'            => 'required|numeric|exists:courier,id',
        ),$messages);
        
        if($validation->fails()) {
            return $validation->messages();
        }
        return false;
     }
    
    
    /**
     *  Get List City By Courier and Area
     **/
     public function getListcity($area_id){
        $statusCode         = 200;
        
        /**
         *  Validation params
         * */
        Validator::getPresenceVerifier()->setConnection('courierdb'); // set connection
        $messages = array(
            'area_id.required'      => 'We need to know service code !',
            'area_id.numeric'       => 'Area code not a number',
            'area_id.exists'        => 'Area not exist or incorrect !'
        );
        
        $validation = Validator::make(array('area_id' => $area_id), array(
            'area_id'    => 'required|numeric|exists:courier_area,id'
        ),$messages);
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()), $statusCode);
        }
        
        /**
         *  get value
         **/
         $ModelCity     = new CityModel;
         $ListCity      = $ModelCity::with(array('arealocation' => function($query) use($area_id){
                                                            $query->where('area_id','=',$area_id)
                                                                  ->where('district_id','=',0)
                                                                  ->get(array('province_id','active'));
                                                        }))->get();
        
        if(!empty($ListCity)){
            foreach($ListCity as $val){
                $City[$val['id']]           = $val;
                if(isset($val['arealocation'][0]) &&  isset($val['arealocation'][0]['active'])){
                    $City[$val['id']]['active'] = $val['arealocation'][0]['active'];
                }else{
                    $City[$val['id']]['active'] = 0;
                }
                
                unset($City[$val['id']]['arealocation']);
            }
        }else{
            $City   = array();
        }
        
        $contents = array(
            'error'         => false,
            'data'          => $City,
            'message'       => 'success'
        );
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
     }
     
     /**
     * Get List City
     * @param   int $id  -- area_id
     *          int     service_id
     *          string  stage
     * 
	 * @return Response
     * */
     public function getListdistrict(){
        $statusCode         = 200;
        
        /**
         *  Validation params
         * */
 
        Validator::getPresenceVerifier()->setConnection('courierdb'); // set connection
        $messages = array(
            'area_id.required'      => 'We need to know area code !',
            'area_id.numeric'       => 'Area code not a number',
            'area_id.exists'        => 'Area not exist or incorrect !',
            'province_id.required'  => 'We need to know province code !',
            'province_id.numeric'   => 'Province code not a number !',
        );
        
        $validation = Validator::make(Input::all(), array(
            'area_id'    => 'required|numeric|exists:courier_area,id',
            'province_id'   => 'required|numeric'
        ),$messages);
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()), $statusCode);
        }
        
        /**
         *  get value
         **/
         
        $AreaId         = (int)Input::get('area_id');
        $ProvinceId     = (int)Input::get('province_id');
        
        $AreaLocationModel   = new AreaLocationModel;
        
        $ListDistrict   = $AreaLocationModel::where('area_id','=',$AreaId)
                                            ->where('province_id','=',$ProvinceId)
                                            ->where('district_id','<>',0)
                                            ->with('district')
                                            ->get();
        if(!empty($ListDistrict)){
            foreach($ListDistrict as $val){
                $District[$val['district_id']]   = $val;
                if(isset($val['district']['district_name'])){
                    $District[$val['district_id']]['district_name']  = $val['district']['district_name'];
                }
                unset($District[$val['district_id']]['district']);
            }
        }
        
        $contents = array(
            'error'         => false,
            'data'          => isset($District) ? $District : array(),
            'message'       => 'success'
        );
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }
    
     
     /**
      *     Courier Area
      **/
    
    public function getListarea()
    {
        $statusCode         = 200;
        $courier_id         = Input::has('courier_id') ? (int)Input::get('courier_id') : null;
    
        /**
         *  Validation params
         * */
         
        $ListCourierArea = new CourierAreaModel;
        if($courier_id > 0){
            // check courier
            $CheckCourier = $this->CheckCourier($courier_id);
            if($CheckCourier) {
                return Response::json(array('error' => true, 'message' => $CheckCourier), $statusCode);
            }
            
            $ListCourierArea = $ListCourierArea->where('courier_id','=',$courier_id);
        }
    
        $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $ListCourierArea->get(array('id','name','courier_id'))
            );
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        
    }
    
    public function postCreatearea(){
        $statusCode         = 200;
        
        Validator::getPresenceVerifier()->setConnection('courierdb'); // set connection
        $messages = array(
            'name.required'         => 'We need to know area name !',
            'name.unique'           => 'Area Name is exists',
            'courier_id.required'   => 'We need to know courier code',
            'courier_id.numeric'    => 'Courier code not a number',
            'courier_id.exists'     => 'Courier code not exist',
        );
        
        $validation = Validator::make(Input::json()->all(), array(
            'courier_id'    => 'required|numeric|exists:courier,id',
            'name'     => 'required|unique:courier_area,name'
        ),$messages);
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()), $statusCode);
        }
        
        $Model  = new CourierAreaModel;
        
        $Name       = Input::json()->get('name');
        $CourierId  = (int)Input::json()->get('courier_id');
        
        $Id = $Model::insertGetId(
            array(
                'courier_id'  => $CourierId,
                'name'        => $Name
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
	 * Delete.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getDestroyarea($id)
	{
		$Model = new CourierAreaModel;
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
     *  Edit Area
     * */
    public function postEditarea(){
        $statusCode         = 200;
        
        Validator::getPresenceVerifier()->setConnection('courierdb'); // set connection
        $messages = array(
            'name.unique'           => 'Area Name is exists',
            'courier_id.numeric'    => 'Courier code not a number',
            'courier_id.exists'     => 'Courier code not exist',
        );
        
        $validation = Validator::make(Input::json()->all(), array(
            'id'            => 'required|numeric|exists:courier_area,id',
            'courier_id'    => 'sometimes|numeric|exists:courier,id',
            'name'          => 'sometimes|unique:courier_area,name'
        ),$messages);
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()), $statusCode);
        }
        
        $Model  = new CourierAreaModel;
        
        $Id         = (int)Input::json()->get('id');
        $CourierId  = (int)Input::json()->get('courier_id');
        $Name       = trim(Input::json()->get('name'));
        
        $CourierArea    = $Model::find($Id);
        
        if(!empty($CourierId))   $CourierArea->courier_id    = $CourierId;
        if(!empty($Name))       $CourierArea->name          = $Name;
        
        $Update = $CourierArea->save();
        
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
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }
}
?>