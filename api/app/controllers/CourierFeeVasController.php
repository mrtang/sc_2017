<?php

class CourierFeeVasController extends \BaseController {
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
        
        $itemPage   = (int)$itemPage;
        $offset     = ($page - 1)*$itemPage;
        
        $Model = new CourierFeeVasModel;
        
        if(!empty($CourierId)){
            $Model  = $Model->where('courier_id','=',$CourierId);
        }
        
        $Total  = $Model->count();
        $Data   = $Model->skip($offset)->take($itemPage)->get();
        
        if(!$Data->isEmpty()){
            foreach($Data as $val){
                $ListDistrictId[]   = $val['from_district_id'];
            }
            if($ListDistrictId){
                $ListDistrictId = array_unique($ListDistrictId);
                $ListDistrict = DistrictModel::whereIn('id',$ListDistrictId)->get();
                if(!$ListDistrict->isEmpty()){
                    foreach($ListDistrict as $val){
                        $District[$val['id']]  = $val['district_name'];
                    }
                    foreach($Data as $key => $val){
                        if(isset($District[$val['from_district_id']])){
                            $Data[$key]['from_district_name']   = $District[$val['from_district_id']];
                        }
                    }
                }
            }
        }
        
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
        $Data               = Input::json()->all();
        
        $CourierId          = isset($Data['courier_id'])        ? (int)$Data['courier_id']      : null;
        $FromAreaId         = isset($Data['from_area_id'])      ? (int)$Data['from_area_id']    : null;
        $FromDistrictId     = isset($Data['from_district_id'])  ? (int)$Data['from_district_id']: null;
        $ToAreaId           = isset($Data['to_area_id'])        ? (int)$Data['to_area_id']      : null;
        $VasId              = isset($Data['vas_id'])            ? (int)$Data['vas_id']          : null;
        $ValueType          = isset($Data['value_type'])        ? (int)$Data['value_type']      : null;
        $ValueStart         = isset($Data['value_start'])       ? (int)$Data['value_start']     : null;
        $ValueEnd           = isset($Data['value_end'])         ? (int)$Data['value_end']       : null;
        $Money              = isset($Data['money'])             ? (int)$Data['money']           : null;
        $Vat                = isset($Data['vat'])               ? (int)$Data['vat']             : null;
        $Surcharge          = isset($Data['surcharge'])         ? (int)$Data['surcharge']       : null;
        $TotalAmount        = isset($Data['total_amount'])      ? (int)$Data['total_amount']    : null;
        $Active             = isset($Data['active'])            ? (int)$Data['active']          : 1;
      
        $Model = new CourierFeeVasModel;
        $statusCode = 200;
        
        if(empty($CourierId) || empty($FromAreaId) || empty($FromDistrictId) || empty($ToAreaId) || !isset($VasId) || !isset($ValueType)
            || !isset($ValueStart) || empty($ValueEnd) || empty($Money) || !isset($Vat) || !isset($Surcharge) || empty($TotalAmount)){
            $contents = array(
                'error' => true, 'message' => 'values empty'
            );
            
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
            
        $Id = $Model::insertGetId(
            array(
                'courier_id'            => $CourierId,
                'from_area_id'          => $FromAreaId,
                'from_district_id'      => $FromDistrictId,
                'to_area_id'            => $ToAreaId,
                'vas_id'                => $VasId,
                'value_type'            => $ValueType,
                'value_start'           => $ValueStart,
                'value_end'             => $ValueEnd,
                'money'                 => $Money,
                'vat'                   => $Vat,
                'surcharge'             => $Surcharge,
                'total_amount'          => $TotalAmount,
                'time_update'           => $this->time(),
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
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getShow($id)
	{
        $Model      = new CourierFeeVasModel;
        $Model      = $Model::find($id);
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
		$Data               = Input::json()->all();
        $CourierId          = isset($Data['courier_id'])        ? (int)$Data['courier_id']      : null;
        $FromAreaId         = isset($Data['from_area_id'])      ? (int)$Data['from_area_id']    : null;
        $FromDistrictId     = isset($Data['from_district_id'])  ? (int)$Data['from_district_id']: null;
        $ToAreaId           = isset($Data['to_area_id'])        ? (int)$Data['to_area_id']      : null;
        $VasId              = isset($Data['vas_id'])            ? (int)$Data['vas_id']          : null;
        $ValueType          = isset($Data['value_type'])        ? (int)$Data['value_type']      : null;
        $ValueStart         = isset($Data['value_start'])       ? (int)$Data['value_start']     : null;
        $ValueEnd           = isset($Data['value_end'])         ? (int)$Data['value_end']       : null;
        $Money              = isset($Data['money'])             ? (int)$Data['money']           : null;
        $Vat                = isset($Data['vat'])               ? (int)$Data['vat']             : null;
        $Surcharge          = isset($Data['surcharge'])         ? (int)$Data['surcharge']       : null;
        $TotalAmount        = isset($Data['total_amount'])      ? (int)$Data['total_amount']    : null;
        $Active             = isset($Data['active'])            ? (int)$Data['active']          : null;
        $statusCode     = 200;
        
        if($id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
        }else{
            $Model = new CourierFeeVasModel;
            $Model = $Model::find($id);
            if($Model){
                if(!empty($CourierId))          $Model->courier_id          = $CourierId;
                if(isset($StageId))             $Model->stage_id            = $StageId;
                if(!empty($FromAreaId))         $Model->from_area_id        = $FromAreaId;
                if(!empty($FromDistrictId))     $Model->from_district_id    = $FromDistrictId;
                if(!empty($ToAreaId))           $Model->to_area_id          = $ToAreaId;
                if(isset($VasId))               $Model->vas_id              = $VasId;
                if(isset($ValueType))           $Model->value_type          = $ValueType;
                if(isset($ValueStart))          $Model->value_start         = $ValueStart;
                if(isset($ValueEnd))            $Model->value_end           = $ValueEnd;
                if(isset($Money))               $Model->money               = $Money;
                if(isset($Vat))                 $Model->vat                 = $Vat;
                if(isset($Surcharge))           $Model->surcharge           = $Surcharge;
                if(isset($TotalAmount))         $Model->total_amount        = $TotalAmount;
                if(isset($Active))              $Model->active              = $Active;
                                                $Model->time_update         = $this->time();
                
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
        }
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getDestroy($id)
	{
        $Model = new CourierFeeVasModel;
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
