<?php

class CourierController extends \BaseController {
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
        $page       = Input::has('page') ? (int)Input::get('page') : 1;
        $itemPage   = Input::has('limit') ? Input::get('limit') : 20;
        $offset     = ($page - 1)*$itemPage;
        $statusCode = 200;
        
        if($itemPage == 'all'){
            $Data   = $this->getCache();
            
            if(!empty($Data)){
                $contents = array(
                    'error'     => false,
                    'message'   => 'success',
                    'data'      => $Data
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'data empty'
                );
            }
        }else{
            $Model = new CourierModel;
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'total'     => $Model::count(),
                'data'      => $Model::skip($offset)->take($itemPage)->get()
            );
        }
        
        return Response::json($contents, $statusCode);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate()
	{ 
        $Data                   = Input::json()->all();
        $TypeId                 = isset($Data['type_id'])               ? (int)$Data['type_id']                 : 0;
        $Prefix                 = isset($Data['prefix'])                ? trim($Data['prefix'])                 : null;
        $Name                   = isset($Data['name'])                  ? trim($Data['name'])                   : null;
        $NumberAttempt          = isset($Data['number_attempt'])        ? (int)$Data['number_attempt']          : 0;
        $Storage                = isset($Data['storage'])               ? (int)$Data['storage']                 : 0;
        $CodDelay               = isset($Data['cod_delay'])             ? (int)$Data['cod_delay']               : 0;
        $Schedule               = isset($Data['schedule'])              ? (int)$Data['schedule']                : 0;
        $GenerateTracking       = isset($Data['generate_tracking'])     ? (int)$Data['generate_tracking']       : 0;
        $DeliveryPromise        = isset($Data['delivery_promise'])      ? (int)$Data['delivery_promise']        : 0;
        $VolumeMetricConstant   = isset($Data['volume_metric_constant'])? (int)$Data['volume_metric_constant']  : 0;
        $Active                 = isset($Data['active'])                ? (int)$Data['active']                  : 1;
        
        $statusCode = 200;
        if(empty($Name)){
            $contents = array(
                'error' => true, 'message' => 'values empty'
            );
            return Response::json($contents, $statusCode);
        }

        $Id = CourierModel::insertGetId(array('type_id' => $TypeId, 'prefix' => $Prefix, 'name' => $Name, 'number_attempt' => $NumberAttempt, 'storage' => $Storage,
                                              'cod_delay' => $CodDelay, 'schedule'  => $Schedule, 'generate_tracking' => $GenerateTracking, 'delivery_promise' => $DeliveryPromise,
                                              'volume_metric_constant'  => $VolumeMetricConstant, 'active'  => $Active));

        if((int)$Id > 0){
            
            // cache
            $this->CacheList();
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
        
        return Response::json($contents, $statusCode);
	}


    public function getUpdateRefuse(){
        $tracking_code  = (Input::has('TrackingCode'))  ? Input::get('TrackingCode') : "";
        $ward_id        = (Input::has('WardId'))        ? Input::get('WardId')       : "";
        
        if(empty($tracking_code) || empty($tracking_code)){
            return Response::json([
                "error"         => true,
                "error_message" => "Vận đơn không tồn tại !",
                "data"          => []
            ]);
        }

        $OrderModel = new \ordermodel\OrdersModel;
        
        $_order = $OrderModel::where('tracking_code', $tracking_code)->with(['ToOrderAddress'])->select('courier_id',  'to_address_id')->first();
        if(!$_order){
            return Response::json([
                "error"         => true,
                "error_message" => "Vận đơn không tồn tại !",
                "data"          => []
            ]);
        }
        $CourierRefuseModel = new CourierRefuseModel;
        
        $data = array(
            "courier_id"    => $_order->courier_id,
            "province_id"   => $_order->to_order_address->city_id,
            "district_id"   => $_order->to_order_address->province_id,
            "ward_id"       => $ward_id
        );
        
        $result = $CourierRefuseModel::firstOrCreate($data);
        
        if($result){
            return Response::json([
                "error"         => false,
                "error_message" => "Cập nhật thành công",
                "data"          => []
            ]);
        }

        return Response::json([
            "error"         => true,
            "error_message" => "Cập nhật lỗi, vui lòng liên hệ administrator",
            "data"          => []
        ]);
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
        $Model      = new CourierModel;
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
    //
    public function getCourierconfig(){
        $ReturnData = array();
        $Data   = $this->getCache();
        $courierOld = Config::get('config_api.cfg_old_carrier');

        if(!empty($Data)){
            foreach($Data AS $One){
                foreach($courierOld AS $key => $value){
                    if($One['id'] == $key){
                        $ReturnData[] = array(
                            'code' => $value,
                            'name' => $One['name']
                        );
                    }
                }
            }
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $ReturnData
            );
        }else{
            $Model = new CourierModel;
            $listCourier = $Model->get(array('id','name'))->toArray();
            if(!empty($listCourier)){
                foreach($listCourier AS $One){
                    foreach($courierOld AS $key => $value){
                        if($One['id'] == $key){
                            $ReturnData[] = array(
                                'code' => $value,
                                'name' => $One['name']
                            );
                        }
                    }
                }
                $contents = array(
                    'error'     => false,
                    'message'   => 'success',
                    'data'      => $ReturnData
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not data',
                    'data'      => ''
                );
            }
        }

        return Response::json($contents, 200);
    }


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function postEdit($Id)
	{
		$Data                   = Input::json()->all();
        $TypeId                 = isset($Data['type_id'])               ? (int)$Data['type_id']                 : null;
        $Prefix                 = isset($Data['prefix'])                ? trim($Data['prefix'])                 : null;
        $Name                   = isset($Data['name'])                  ? trim($Data['name'])                   : null;
        $NumberAttempt          = isset($Data['number_attempt'])        ? (int)$Data['number_attempt']          : null;
        $Storage                = isset($Data['storage'])               ? (int)$Data['storage']                 : null;
        $CodDelay               = isset($Data['cod_delay'])             ? (int)$Data['cod_delay']               : null;
        $Schedule               = isset($Data['schedule'])              ? (int)$Data['schedule']                : null;
        $GenerateTracking       = isset($Data['generate_tracking'])     ? (int)$Data['generate_tracking']       : null;
        $DeliveryPromise        = isset($Data['delivery_promise'])      ? (int)$Data['delivery_promise']        : null;
        $VolumeMetricConstant   = isset($Data['volume_metric_constant'])? (int)$Data['volume_metric_constant']  : null;
        $Active                 = isset($Data['active'])                ? (int)$Data['active']                  : null;
        $statusCode             = 200;
        
        if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents, $statusCode);
        }
        
        $Model = CourierModel::find($Id);;
        if($Model){
            if(!empty($TypeId))             $Model->type_id                 = $TypeId;
            if(isset($Prefix))              $Model->prefix                  = $Prefix;
            if(!empty($Name))               $Model->name                    = $Name;
            if(isset($NumberAttempt))       $Model->number_attempt          = $NumberAttempt;
            if(isset($Storage))             $Model->storage                 = $Storage;
            if(isset($CodDelay))            $Model->cod_delay               = $CodDelay;
            if(isset($Schedule))            $Model->schedule                = $Schedule;
            if(isset($GenerateTracking))    $Model->generate_tracking       = $GenerateTracking;
            if(isset($DeliveryPromise))     $Model->delivery_promise        = $DeliveryPromise;
            if(isset($VolumeMetricConstant))$Model->volume_metric_constant  = $VolumeMetricConstant;
            if(isset($Active))              $Model->active                  = $Active;

            $Update = $Model->save();
       
            if($Update){               
                // cache
                $this->CacheList();
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
        
        return Response::json($contents, $statusCode);
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
	public function getDestroy($Id)
	{
        $Model      = new CourierModel;
        $Model      = $Model::find($Id);
        $statusCode = 200;
        if($Model){
            $Delete = $Model->delete();
            if($Delete){
                // cache
                $this->CacheList();
                // remove table promise
                $Promise    = new CourierPromiseModel;
                $Promise    = $Promise::where('courier_id','=',(int)$Id)->delete();
                
                // remove table comision
                $Commision  = new CourierComissionModel;
                $Commision  = $Commision::where('courier_id','=',(int)$Id)->delete();
                
                
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

    private function CacheList(){
        $Courier  = CourierModel::all(array('id','name','prefix','money_pickup','money_delivery'));
        $Data     = [];
        if(!$Courier->isEmpty()){
            foreach($Courier as $val){
                $Data[(int)$val['id']]  = $val;
            }
            Cache::put('courier_cache_', $Data, 1440);
        }
        return true;
    }
    
    public function getCache(){
        Cache::forget('courier_cache_');
        if(Cache::has('courier_cache_')){
            return Cache::get('courier_cache_');
        }else{
            $this->CacheList();
            if(Cache::has('courier_cache_')){
               return Cache::get('courier_cache_');
            }
        }
        return false;
    }
}
