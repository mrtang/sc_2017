<?php

class CourierFeeController extends \BaseController {
    private $domain     = '*';
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
        
        $Model = new CourierFeeModel;
        
        if(!empty($CourierId)){
            $Model  = $Model->where('courier_id','=',$CourierId);
        }
        
        $Total  = $Model->count();
        $Data   = $Model->skip($offset)->take($itemPage)->get();
        
        if(!$Data->isEmpty()){
            foreach($Data as $val){
                $ListDistrictId[]   = $val['from_district_id'];
                $ListFeeId[]        = $val['id'];
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
        $StageId            = isset($Data['stage_id'])          ? (int)$Data['stage_id']        : null;
        $FromAreaId         = isset($Data['from_area_id'])      ? (int)$Data['from_area_id']    : null;
        $FromDistrictId     = isset($Data['from_district_id'])  ? (int)$Data['from_district_id']: null;
        
        $Vat                = isset($Data['vat'])               ? (int)$Data['vat']             : 10;
        $ToAreaId           = isset($Data['to_area_id'])        ? (int)$Data['to_area_id']      : null;
        $ServiceId          = isset($Data['service_id'])        ? (int)$Data['service_id']      : null;
        $Active             = isset($Data['active'])            ? (int)$Data['active']          : 1;
        
      
        $Model = new CourierFeeModel;
        $statusCode = 200;
        
        if(empty($CourierId) || empty($StageId) || empty($FromAreaId) || empty($FromDistrictId) || empty($ToAreaId) || empty($ServiceId)){
            $contents = array(
                'error' => true, 'message' => 'values empty'
            );
            
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
            
        $Id = $Model::insertGetId(
            array(
                'courier_id'            => $CourierId,
                'stage_id'              => $StageId,
                'from_area_id'          => $FromAreaId,
                'from_district_id'      => $FromDistrictId,
                'to_area_id'            => $ToAreaId,
                'service_id'            => $ServiceId,
                'vat'                   => $Vat,
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
        $Model      = new CourierFeeModel;
        $Model      = $Model::find($id);
        $statusCode = 200;
        
        if($Model){
            $ListFeeDetail  = CourierFeeDetailModel::where('fee_id','=',$id)->get();
            if(!$ListFeeDetail->isEmpty()){
                $Model['fee_detail']    = $ListFeeDetail;
            } 
            
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
        $type               = Input::has('type')        ? trim(Input::get('type')) : null;
        
		$Data               = Input::json()->all();
        $CourierId          = isset($Data['courier_id'])        ? (int)$Data['courier_id']      : null;
        $StageId            = isset($Data['stage_id'])          ? (int)$Data['stage_id']        : null;
        $FromAreaId         = isset($Data['from_area_id'])      ? (int)$Data['from_area_id']    : null;
        $FromDistrictId     = isset($Data['from_district_id'])  ? (int)$Data['from_district_id']: null;
        
        $ToAreaId           = isset($Data['to_area_id'])        ? (int)$Data['to_area_id']      : null;
        $ServiceId          = isset($Data['service_id'])        ? (int)$Data['service_id']      : null;
        $WeightStart        = isset($Data['weight_start'])      ? (int)$Data['weight_start']    : null;
        $WeightEnd          = isset($Data['weight_end'])        ? (int)$Data['weight_end']      : null;
        $Money              = isset($Data['money'])             ? (int)$Data['money']           : null;
        $Vat                = isset($Data['vat'])               ? (int)$Data['vat']             : null;
        $Surcharge          = isset($Data['surcharge'])         ? (int)$Data['surcharge']       : null;
        $Active             = isset($Data['active'])            ? (int)$Data['active']          : null;
        $statusCode     = 200;
        
        if($id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
        }else{
            if($type == 'detail'){
                $Model = new CourierFeeDetailModel;
            }else{
                $Model = new CourierFeeModel;
            }
            
            $Model = $Model::find($id);
            if($Model){
                if(!empty($CourierId))          $Model->courier_id          = $CourierId;
                if(isset($StageId))             $Model->stage_id            = $StageId;
                if(!empty($FromAreaId))         $Model->from_area_id        = $FromAreaId;
                if(!empty($FromDistrictId))     $Model->from_district_id    = $FromDistrictId;
                if(!empty($ToAreaId))           $Model->to_area_id          = $ToAreaId;
                if(isset($ServiceId))           $Model->service_id          = $ServiceId;
                if(isset($WeightStart))         $Model->weight_start        = $WeightStart;
                if(isset($WeightEnd))           $Model->weight_end          = $WeightEnd;
                if(isset($Money))               $Model->money               = $Money;
                if(isset($Vat))                 $Model->vat                 = $Vat;
                if(isset($Surcharge))           $Model->surcharge           = $Surcharge;
                if(isset($Active))              $Model->active              = $Active;
                
                
                if($type != 'detail'){
                    $Model->time_update         = $this->time();
                }
                
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
        $type       = Input::has('type')        ? trim(Input::get('type')) : null;
        
        if($type == 'detail'){
            $Model = new CourierFeeDetailModel;
        }else{
            $Model = new CourierFeeModel;
        }
        
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
    
    public function postUpload($id_fee){
            $statusCode = 200;
            
            Validator::getPresenceVerifier()->setConnection('courierdb'); // set connection
            $messages = array(
                'fee_id.numeric'    => 'fee code not a number',
                'fee_id.exists'     => 'Fee code not exist',
            );
            
            $validation = Validator::make(array('fee_id' => $id_fee), array(
                'fee_id'            => 'required|numeric|exists:courier_fee,id',
            ),$messages);
            
            //error
            if($validation->fails()) {
                return Response::json(array('error' => true, 'message' => $validation->messages()), $statusCode);
            }
            
            if ( !empty( $_FILES ) ) {
            $tempPath = $_FILES[ 'file' ][ 'tmp_name' ];
            $uploadPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . '../uploads' . DIRECTORY_SEPARATOR . $_FILES[ 'file' ][ 'name' ];
            move_uploaded_file( $tempPath, $uploadPath );
            
            $LMongo = new LMongo;
            $id = (string)$LMongo::collection('log_import_fee')->insert(
              array('link_tmp' => $uploadPath, 'fee_id' => $id_fee,'action' => array('del' => 0, 'insert' => 0))
            );
            
            if(!empty($id)){
             if($this->ReadExcel((string)$id)){
                 $contents = array(
                    'error'     => false,
                    'message'   => 'success',
                    'id'        => $id,
                    'data'      => json_decode($this->ListDataUpload($id),1)
                ); 
             }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'read excel error'
                ); 
             }
             
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'insert log import fail'
                ); 
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'upload fail'
            ); 
        }
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }
    
    function Readexcel($id){
        $LMongo     = new LMongo;
        $ListImport = $LMongo::collection('log_import_fee')->find($id);
        $Data = Excel::load($ListImport['link_tmp'], function($reader) {
          $reader->skip(0)->select(array(0,1,2,3))->toArray();
        })->get();
        
        if($Data){
            $DataInsert = array();
            
            foreach($Data as $key => $val){
              $DataInsert[] = array(
                    'partner'           => $id,
                    'status'            => 'NOT ACTIVE',
                    'active'            => 0,
                    'fee_id'            => (int)$ListImport['fee_id'],
                    'weight_start'      => $val['weight_start'],
                    'weight_end'        => $val['weight_end'],
                    'money'             => $val['money'],
                    'surcharge'         => $val['surcharge']
              );
            }
            
            $ListModel  = $LMongo::collection('log_list_fee');
            $Insert = $ListModel->batchInsert($DataInsert);
             
            if($Insert) return true;
        }
        return false;
    }
    
    function ListDataUpload($id){
            $ListModel     = LMongo::collection('log_list_fee');
            return $ListModel->where('partner', $id)->get();
    }
    
    function getAcceptfee($id){
        $statusCode         = 200;
        
        $Model     = LMongo::collection('log_list_fee');
        
        $Fee    = $Model->where('_id', new MongoId($id))
                        ->where('active',0)
                        ->first();
        
        if($Fee){
            $DataUpdate = new CourierFeeDetailModel;
            $DataUpdate->fee_id = $Fee['fee_id'];
            if(isset($Fee['weight_start'])) $DataUpdate->weight_start = $Fee['weight_start'];
            if(isset($Fee['weight_end']))   $DataUpdate->weight_end   = $Fee['weight_end'];
            if(isset($Fee['money']))        $DataUpdate->money        = $Fee['money'];
            if(isset($Fee['surcharge']))    $DataUpdate->surcharge    = $Fee['surcharge'];
            
            $DataUpdate  = $DataUpdate->save();
            
                if($DataUpdate){
                    $Model->where('_id', new MongoId($id))
                          ->update(array('active' => 1, 'status' => 'SUCCESS'));
                    $contents = array(
                        'error' => false,
                        'message' => 'success'
                    );
                }else{
                    $Model->where('_id', new MongoId($id))
                          ->update(array('active' => 1, 'status' => 'ERROR'));
                    $contents = array(
                        'error' => false,
                        'message' => 'insert error'
                    );
                }
            
        }else{
            $contents = array(
                'error' => true,
                'message' => 'row not exists'
            );
        }
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }
    
    function getListdata($id){
            $statusCode         = 200;
            $ListModel     = LMongo::collection('log_list_fee')->where('partner', $id)->get()->toArray();
            
            if($ListModel){
                $contents = array(
                'error' => false,
                'message' => 'fee not exists',
                'data'    => $ListModel
                );
            }else{
                $contents = array(
                'error' => true,
                'message' => 'not exists'
                );
            }
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }
    //get fee with courier_id
    public function getFeebycourier($courier){
        $Model = new CourierFeeModel;
        $listFee = $Model->where('courier_id',$courier)->get(array('id','name'))->toArray();
        if(!empty($listFee)){
            $contents = array(
                'error' => false,
                'message' => 'success',
                'data'    => $listFee
            );
        }else{
            $contents = array(
                'error' => true,
                'message' => 'Not fee!!!!',
                'data'    => array()
            );
        }
        return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
    }
    //get all fee
    public function getAllfee(){
        $Model = new CourierFeeModel;
        $listFee = $Model->get(array('id','name'))->toArray();
        $output = array();
        if(!empty($listFee)){
            foreach($listFee AS $fee){
                $output[$fee['id']] = $fee['name'];
            }
            $contents = array(
                'error' => false,
                'message' => 'success',
                'data'    => $output
            );
        }else{
            $contents = array(
                'error' => true,
                'message' => 'Not fee!!!!',
                'data'    => array()
            );
        }
        return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
    }
}
