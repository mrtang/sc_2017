<?php
class CourierPromiseController extends \BaseController {
    private $domain = '*';
    private $city_id;
    private $district_id;
    private $courier_id;
    
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
	public function postCreate($id)
	{    
	   $statusCode = 200;
       
        /**
        *  Validation params
        * */
        
        // check courier
        $CheckCourier = $this->CheckCourier($id);
        if($CheckCourier) {
            return Response::json(array('error' => true, 'message' => $CheckCourier), $statusCode);
        }
        
        Validator::getPresenceVerifier()->setConnection('courierdb'); // set connection
        
        // check params
        $messages = array(
        'service_id.required'   => 'We need to know service code !',
        'service_id.numeric'    => 'Service is not a number !',
        'service_id.exist'      => 'Service not exits or not active !',
        'stage.required'        => 'Stage not in pickup or delivery !',
        'stage.in'              => 'Stage not in pickup or delivery !',
        );
        
        $validation = Validator::make(Input::json()->all(), array(
        'service_id'        => 'required|numeric|exists:courier_service,id,active,1',
        'stage'             => 'required|in:pickup,delivery',
        'province_id'       => 'sometimes|required|numeric',
        'to_province'       => 'sometimes|required|numeric',
        'district_id'       => 'sometimes|numeric',
        'estimate_pickup'   => 'sometimes|required',
        'estimate_delivery' => 'sometimes|required',
        'estimate_return'   => 'sometimes|required',
        'estimate_ward'     => 'sometimes|required',
        'active'            => 'sometimes|required|boolean',
        ),$messages);
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()), $statusCode);
        }
        
        /**
         * Get Data 
         * */
         
        $ServiceId          = Input::json()->get('service_id');
        $ProvinceId         = Input::json()->get('province_id');
        $ToProvince         = Input::json()->get('to_province');
        $DistrictId         = Input::json()->get('district_id');
        $Stage              = Input::json()->get('stage');
        $EstimatePickup     = Input::json()->get('estimate_pickup');
        $EstimateDelivery   = Input::json()->get('estimate_delivery');
        $EstimateReturn     = Input::json()->get('estimate_return');
        $EstimateWard       = Input::json()->get('estimate_ward');
        $Active             = Input::json()->get('active');
        
        //Check exits  when not exits  create
        if($Stage == 'pickup'){
            $CourierPromise     = new PromisePickupModel;
            $DataCreate     = array('courier_id'=>$id, 'service_id'=>$ServiceId, 'province_id'=>$ProvinceId);
            
            if(!empty($DistrictId)){
                $DataCreate['district_id']  = $DistrictId;
            }else{
                $DataCreate['district_id']  = 0;
            }
        }else{
            $CourierPromise     = new PromiseDeliveryModel;
            $DataCreate     = array('courier_id'=>$id, 'service_id'=>$ServiceId, 'from_province'=>$ProvinceId);
            
            if(!empty($ToProvince)){
                $DataCreate['to_province']  = $ToProvince;
            }else{
                $DataCreate['to_province']  = 0;
            }
            
            if(!empty($DistrictId)){
                $DataCreate['to_district']  = $DistrictId;
            }else{
                $DataCreate['to_district']  = 0;
            }
        }
        
        $Promise            = $CourierPromise::firstOrCreate($DataCreate);
        
        if(!empty($Promise)){
            if(isset($EstimatePickup))      $Promise->estimate_pickup       = $EstimatePickup;
            if(isset($EstimateDelivery))    $Promise->estimate_delivery     = $EstimateDelivery;
            if(isset($EstimateReturn))      $Promise->estimate_return       = $EstimateReturn;
            if(isset($EstimateWard))        $Promise->estimate_ward         = $EstimateWard;
            if(isset($Active))              $Promise->active                = $Active;
            
            $Promise->time_update   = $this->time();
            $Update = $Promise->save();
       
            if($Update){
                // change city
                
                if($Stage == 'pickup'){
                    if(empty($DistrictId) && isset($Active)){
                        $UpdateAllbyCity    = $CourierPromise::where('courier_id',  '=',$id)
                                                              ->where('service_id', '=',$ServiceId)
                                                              ->where('province_id', '=',$ProvinceId)
                                                              ->update(array('active' => $Active));
                    }elseif(!empty($DistrictId) && isset($Active) && ($Active == 1)){
                        $UpdateParents      = $CourierPromise::where('courier_id',  '=',$id)
                                                              ->where('service_id', '=',$ServiceId)
                                                              ->where('province_id', '=',$ProvinceId)
                                                              ->where('district_id', '=',0)
                                                              ->update(array('active' => $Active));
                    }
                }else{
                    if(empty($DistrictId) && isset($Active)){
                        $UpdateAllbyCity    = $CourierPromise::where('courier_id',  '=',$id)
                                                              ->where('service_id', '=',$ServiceId)
                                                              ->where('from_province', '=',$ProvinceId);
                                                              
                        if(!empty($ToProvince)){
                            $UpdateAllbyCity    = $UpdateAllbyCity->where('to_province','=',$ToProvince);
                        }
                        
                        $UpdateAllbyCity    = $UpdateAllbyCity->update(array('active' => $Active));                                      
                    }elseif(!empty($DistrictId) && isset($Active) && ($Active == 1)){
                        $UpdateParentsFrom      = $CourierPromise::where('courier_id',  '=',$id)
                                                              ->where('service_id', '=',$ServiceId)
                                                              ->where('from_province', '=',$ProvinceId)
                                                              ->where('to_province', '=',0)
                                                              ->where('to_district', '=',0)
                                                              ->update(array('active' => $Active));
                        
                        $UpdateParentsTo        = $CourierPromise::where('courier_id',  '=',$id)
                                                              ->where('service_id', '=',$ServiceId)
                                                              ->where('from_province', '=',$ProvinceId)
                                                              ->where('to_province', '=',$ToProvince)
                                                              ->where('to_district', '=',0)
                                                              ->update(array('active' => $Active));
                        
                    }
                }
                
                $contents = array(
                    'error'     => false,
                    'message'   => 'success',
                    'id'        => $Promise->id
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
		$Model          = new CourierPromiseModel;
        $Model          = $Model::find($Id);
        $statusCode     = 200;
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
	   
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getDestroy($Id)
	{    
	    $Stage          = trim(Input::get('stage'));
        if($Stage == 'delivery'){
            $Model      = new PromiseDeliveryModel;
        }else{
            $Model      = new PromisePickupModel;
        }
        
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
     * Get List City
     * @param   int $id  -- courier_id
     *          int     service_id
     *          string  stage
     * 
	 * @return Response
     * */
    public function getListcity($id){
        $statusCode         = 200;
        
        /**
         *  Validation params
         * */
        // check courier
        $CheckCourier = $this->CheckCourier($id);
        if($CheckCourier) {
            return Response::json(array('error' => true, 'message' => $CheckCourier), $statusCode);
        }
        
        // check params
        Validator::getPresenceVerifier()->setConnection('courierdb'); // set connection
        $messages = array(
            'service_id.required'   => 'We need to know service code !',
            'service_id.numeric'    => 'Service code not a number',
            'service_id.exists'     => 'Service not exist or not active !',
            'stage.required'        => 'Stage not in pickup or delivery !',
            'stage.in'              => 'Stage not in pickup or delivery !',
        );
        
        $validation = Validator::make(Input::all(), array(
            'service_id'    => 'required|numeric|exists:courier_service,id,active,1',
            'stage'         => 'required|in:pickup,delivery'
        ),$messages);
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()), $statusCode);
        }
        
        /**
         *  get value
         **/
        $ServiceId      = (int)Input::get('service_id');
        $Stage          = trim(Input::get('stage'));
        
        if($Stage == 'pickup'){
            $ListCity = CityModel::orderBy('city_name', 'ASC')->with(array('promise_pickpup' => function($query) use ($id,$ServiceId){
                                                                $query->where('courier_id', '=', $id)
                                                                      ->where('service_id', '=', $ServiceId)
                                                                      ->where('district_id', '=', 0)
                                                                      ->first(array('province_id', 'active'));
                                                            }))->get(array('id','city_name','region'))->toArray();
        }else{
            $ListCity = CityModel::orderBy('city_name', 'ASC')->with(array('promise_delivery' => function($query) use ($id,$ServiceId){
                                                                $query->where('courier_id', '=', $id)
                                                                      ->where('service_id', '=', $ServiceId)
                                                                      ->where('to_province', '=', 0)
                                                                      ->where('to_district', '=', 0)
                                                                      ->first(array('from_province', 'active'));
                                                            }))->get(array('id','city_name','region'))->toArray();
        }
        
        
                         
        if(!empty($ListCity)){
            foreach($ListCity as $val){
                $City[$val['id']]['id']         = $val['id'];
                $City[$val['id']]['city_name'] = $val['city_name'];
                $City[$val['id']]['region']    = $val['region'];
                
                if($Stage == 'pickup'){
                    if(isset($val['promise_pickpup'][0]) && isset($val['promise_pickpup'][0]['active'])){
                        $City[$val['id']]['active']    = $val['promise_pickpup'][0]['active'];
                    }else{
                        $City[$val['id']]['active']    = 0;
                    }
                }else{
                    if(isset($val['promise_delivery'][0]) && isset($val['promise_delivery'][0]['active'])){
                        $City[$val['id']]['active']    = $val['promise_delivery'][0]['active'];
                    }else{
                        $City[$val['id']]['active']    = 0;
                    }
                }
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
     * @param   int $id  -- courier_id
     *          int     service_id
     *          string  stage
     * 
	 * @return Response
     * */
    public function getListdistrict($id){
        $statusCode         = 200;
        
        /**
         *  Validation params
         * */
        // check courier
        $CheckCourier = $this->CheckCourier($id);
        if($CheckCourier) {
            return Response::json(array('error' => true, 'message' => $CheckCourier), $statusCode);
        }
        
        // check params
        Validator::getPresenceVerifier()->setConnection('courierdb'); // set connection
        $messages = array(
            'service_id.required'   => 'We need to know service code !',
            'service_id.numeric'    => 'Service code not a number',
            'service_id.exists'     => 'Service not exist or not active !',
            'stage.required'        => 'Stage not in pickup or delivery !',
            'stage.in'              => 'Stage not in pickup or delivery !',
        );
        
        $validation = Validator::make(Input::all(), array(
            'service_id'    => 'required|numeric|exists:courier_service,id,active,1',
            'province_id'   => 'required|numeric',
            'to_province'   => 'sometimes|required|numeric',
            'stage'         => 'required|in:pickup,delivery'
        ),$messages);
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()), $statusCode);
        }
        
        /**
         *  get value
         **/
        $ServiceId      = (int)Input::get('service_id');
        $ProvinceId     = (int)Input::get('province_id');
        $ToProvince     = (int)Input::get('to_province');
        $Stage          = trim(Input::get('stage'));
        $Search         = trim(Input::get('district_name'));
        
        if($Stage == 'pickup'){
            $ListDistrict = PromisePickupModel::where('courier_id','=',$id)
                                    ->where('service_id','=',$ServiceId)
                                    ->where('province_id','=',$ProvinceId)
                                    ->where('district_id','<>',0)
                                    ->with(array('district' => function($query) use($Search){
                                        if(!empty($Search)){
                                            $query->where('district_name','LIKE','%'.$Search.'%');
                                        }
                                        $query->orderBy('district_name','ASC')->get(array('id','district_name'));
                                    }))->get();
                                    
            if(!empty($ListDistrict)){
                foreach($ListDistrict as $val){
                    $District[$val['district_id']]                  = $val;
                    $District[$val['district_id']]['district_name'] = $val['district']['district_name'];
                    unset($District[$val['district_id']]['district']);
                }
            }
        
        }else{
            $ListDistrict = PromiseDeliveryModel::where('courier_id','=',$id)
                                    ->where('service_id','=',$ServiceId)
                                    ->where('from_province','=',$ProvinceId)
                                    ->where('to_province','=',$ToProvince)
                                    ->where('to_district','<>',0)
                                    ->with(array('district' => function($query) use($Search){
                                        if(!empty($Search)){
                                            $query->where('district_name','LIKE','%'.$Search.'%');
                                        }
                                        $query->orderBy('district_name','ASC')->get(array('id','district_name'));
                                    },'city' => function($query){
                                        $query->get(array('id','city_name'));
                                    }))->get();
                                    
            if(!empty($ListDistrict)){
                foreach($ListDistrict as $val){
                    $District[$val['to_district']]                  = $val;
                    $District[$val['to_district']]['district_name'] = $val['district']['district_name'];
                    $District[$val['to_district']]['province_name'] = $val['city']['city_name'];
                    unset($District[$val['to_district']]['district']);
                    unset($District[$val['to_district']]['city']);
                }
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
     *  Excel
     * */
     
    public function getTemplate(){
        Excel::create('Promise_Pickup', function($excel) {
            $excel->sheet('promise pickup', function($sheet) {
                // set width column
                $sheet->setWidth(array(
                    'A'     => 7,
                    'B'     => 15,
                    'C'     => 15,
                    'D'     => 15,
                    'E'     => 15,
                    'F'     => 20
                ));
                
                // set content row 1
                $sheet->row(1, array(
                     'STT', 'Courier ID', 'Service ID', 'Province ID', 'District ID', 'Estimate (hours)'
                ));
                
                // set color row 1
                $sheet->row(1,function($row){
                    $row->setBackground('#B6B8BA');
                    $row->setBorder('solid','solid','solid','solid');
                    $row->setFontSize(12);
                });
                
                
            });
        })->export('xls');
    }

    // Show dev version
    public function getShowNew (){
        $from_district     = Input::has('from_district') ? Input::get('from_district') : "";
        $to_district       = Input::has('to_district') ? Input::get('to_district') : "";
        $service           = Input::has('service') ? Input::get('service') : "";
        $courier           = Input::has('courier') ? Input::get('courier') : "";
        $page              = Input::has('page') ? (int)Input::get('page')   : 1;
        $itemPage          = Input::has('limit')    ? Input::get('limit')   : 20;
        $offset            = ($page - 1) * $itemPage;


        $PromiseModel     = new systemmodel\CourierPromiseModelDev;

        if (!empty($from_district)) {
            $PromiseModel = $PromiseModel->where('from_district', $from_district);
        }

        if (!empty($to_district)) {
            $PromiseModel = $PromiseModel->where('to_district', $to_district);
        }

        if (!empty($courier)) {
            $PromiseModel = $PromiseModel->where('courier_id', $courier);
        }
        if (!empty($service)) {
            $PromiseModel = $PromiseModel->where('service', $service);
        }

        $PromiseModel = $PromiseModel->skip($offset)->take($itemPage);

        try {
            $PromiseModel = $PromiseModel->get();
        } catch (Exception $e) {
            return Response::json([
                'error'         => true,
                'error_message' => "Query sai rồi e"
            ]);
        }

        $list_dist = [];

        foreach ($PromiseModel as $key => $value) {
            $list_dist[] = $value['from_district'];
            $list_dist[] = $value['to_district'];
        }

        $list_dist = array_unique($list_dist);
        $ListDistrict = $this->getProvince($list_dist);



        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => $PromiseModel,
            'list_dist'     => $ListDistrict 
        ]);
    }


    private function hasSunday($startDate, $endDate){
        while ($startDate <= $endDate) {
            if (date('N', $startDate) > 6) {
                return true;
            }
            $startDate += 86400;
        }
        return false;
    }

    public function getSyncPromises(){
        $FromCityID       = Input::has('from_city') ? Input::get('from_city') : "";

        if(empty($FromCityID)){
            return Response::json([
                'error'         => true,
                'error_message' => "Data not true",
                'data'          => []
            ]);
        }

        $DistrictModel    = new DistrictModel;
        $PromiseModel     = new systemmodel\CourierPromiseModelDev;
        $GroupOrderStatusModel = new metadatamodel\GroupOrderStatusModel;


        $ListDistricts  = $DistrictModel->get(['id', 'district_name', 'city_id']);

        $FromDistricts    = $this->getListDistrictId($ListDistricts, $FromCityID);
        
        $ListRefuseStatus = $GroupOrderStatusModel->whereIn('group_status', [15, 18, 20, 21, 22])->lists('order_status_code');

        $PromiseModel     = $PromiseModel
                            ->where('verified', 2)
                            ->whereIn('from_district', $FromDistricts)
                            ->take(5)
                            ->get();

        if($PromiseModel->isEmpty()){
            return 'Empty';
        }
        foreach ($PromiseModel as $key => $promise) {
            $OrderModel     = new \ordermodel\OrdersModel;
            $OrderModel     = $OrderModel->whereIn('status', [52, 53])
                                    ->whereHas('OrderStatus', function ($q) use ($ListRefuseStatus){
                                        return $q->whereNotIn('status', $ListRefuseStatus)->where('time_create', '>=', $this->time() - ($this->time_limit));
                                    })
                                    
                                    ->with(['OrderStatus'=> function ($q){
                                        return $q->where('time_create', '>=', $this->time() - ($this->time_limit));
                                    }])
                                    ->where('service_id', $promise->service_id)
                                    ->where('courier_id', $promise->courier_id)
                                    ->where('from_district_id', $promise->from_district)
                                    ->where('to_district_id', $promise->to_district)
                                    ->where('time_accept','>=', $this->time() - $this->time_limit)
                                    ->where('time_create','>=', $this->time() - ($this->time_limit + 2592000))
                                    ->orderBy('id', 'DESC')
                                    //->select(['id', 'service_id', 'courier_id', 'from_city_id', 'from_district_id', 'to_district_id', 'time_accept', 'time_success', 'time_pickup', 'status'])

                                    ->get()->toArray();


            if(sizeof($OrderModel) > 20){
                $AvgTimeDelivery = 0;
                $Count = 0;
                foreach ($OrderModel as $k => $order) {
                    foreach ($order['order_status'] as $key => $value) {
                        if($value['status'] == 51){ // Trạng thái đang phát hàng
                            if($this->hasSunday($order['time_pickup'], $value['time_create'])){ // Check có chủ nhật
                                // Thời gian của trạng thái đăng giao hàng - time_pickup
                                $AvgTimeDelivery += ($value['time_create'] - $order['time_pickup']) - 86400;
                            }else {
                                $AvgTimeDelivery += ($value['time_create'] - $order['time_pickup']);
                            }
                            $Count ++;
                        }
                    }
                }
                if($Count > 0){
                    $AvgTimeDelivery = round($AvgTimeDelivery / $Count);
                    if( ($AvgTimeDelivery + 86400) < $promise->courier_estimate_delivery){
                        $promise->verified = 1;
                    }else {
                        $promise->verified = 3;
                    }
                    $promise->estimate_delivery  = $AvgTimeDelivery;
                    $promise->save();
                }else {
                    $promise->verified = 3;
                    $promise->save();
                }

            }else {
                $promise->verified = 3;
                $promise->save();
            }
        }
        return 'NEXT';



    }
  


    private function getListDistrictId ($Source, $CityId){
        $Districts = [];
        foreach ($Source as $key => $value) {
            if($value->city_id == $CityId){
                $Districts[] = $value->id;
            }
        }
        return $Districts;
    }

    /*public function getAbc(){
        $ListDist       = AreaLocationModel::whereIn('city_id', [18, 52])->where('active', 1)->get()->lists('province_id');

        $PromiseModel   = new systemmodel\CourierPromiseModelDev;

        try {
            $PromiseModel = $PromiseModel::whereIn('to_district', $ListDist)->where('courier_id', 11)->update(['active' => 1]);
        } catch (Exception $e) {
            return Response::json([$e->getMessage()]);
        }
        return Response::json(["abc", $PromiseModel]);
    }*/

    public function getAbc(){
        $ListDist       = AreaLocationModel::where('location_id', 1)->where('active', 1)->get()->lists('province_id');

        $PromiseModel   = new systemmodel\CourierPromiseModelDev;

        try {
            $PromiseModel = $PromiseModel::whereIn('to_district', $ListDist)->where('courier_id', 9)->update(['active' => 1]);
        } catch (Exception $e) {
            return Response::json([$e->getMessage()]);
        }
        return Response::json(["abc", $PromiseModel]);
    }

}
