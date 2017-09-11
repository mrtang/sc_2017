<?php

class WardController extends \BaseController {
    private $domain = '*';
    private $district_id = 0;
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */



    private function getCacheAll(){
        Cache::forget('cache_all_ward_remote');
        $ListDistrictId = DistrictModel::where('remote', 2)->lists('id');
        $Data           = WardModel::whereIn('district_id', $ListDistrictId)->get();
        if(!$Data->isEmpty()){
            return Cache::put('cache_all_ward_remote', $Data, 1440);
        }
        return false;
    }
    private function CacheAll(){
        if(Cache::has('cache_all_ward_remote')){
            return Cache::get('cache_all_ward_remote');
        }

        $this->getCacheAll();
        if(Cache::has('cache_all_ward_remote')){
            return Cache::get('cache_all_ward_remote');
        }
        return false;
    }
    
    public function getAll(){
        $Data = $this->CacheAll();
        
        return Response::json([
            'error'         => false,
            'error_message' => '',
            'data'          => $Data ? $Data : []
        ]);
    }



	public function getIndex()
	{
        $page           = Input::has('page')        ? (int)Input::get('page') : 1;
        $city_id        = Input::has('city_id')     ? (int)Input::get('city_id') : 0;
        $district_id    = Input::has('district_id') ? (int)Input::get('district_id') : 0;
        $ward_name      = Input::has('ward_name')   ? trim(Input::get('ward_name')) : null;
        $itemPage       = Input::has('limit')       ? Input::get('limit') : 20;
        
        $offset         = ($page - 1)*$itemPage;
       
        $statusCode = 200;
        
        if($district_id > 0 && $itemPage == 'all'){
            $this->district_id   = $district_id;
            $Data   = $this->GetCache();
            
            if($Data){
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
            $Model      = new WardModel;
            
            if($city_id > 0){
                $Model  = $Model->where('city_id','=',$city_id);
            }
            if($district_id > 0){
                $Model  = $Model->where('district_id','=',$district_id);
            }
            if(!empty($ward_name)){
                $Model  = $Model->where('ward_name','LIKE', '%'.$ward_name.'%');
            }
            
            $Total  = $Model->count();
            $Data   = $Model->skip($offset)->take($itemPage)->get();
            if(!$Data->isEmpty()){
                foreach($Data as $val){
                    $ListDistrictId[] = (int)$val['district_id'];
                }
                $ListDistrictId = array_unique($ListDistrictId);
                if($ListDistrictId){
                    $ListDistrict   = DistrictModel::whereIn('id',$ListDistrictId)->get(array('id','district_name'));
                    if(!$ListDistrict->isEmpty()){
                        foreach($ListDistrict as $val){
                            $District[$val['id']]   = $val['district_name'];
                        }
                        if($District){
                            foreach($Data as $key => $val){
                                if(isset($District[$val['district_id']])){
                                    $Data[$key]['district_name']    = $District[$val['district_id']];
                                }
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
        }
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}


    public function getRefuse(){
        $page               = Input::has('page')        ? (int)Input::get('page')           : 1;
        $city_id            = Input::has('city_id')     ? (int)Input::get('city_id')        : 0;
        $district_id        = Input::has('district_id') ? (int)Input::get('district_id')    : 0;
        $ward_id            = Input::has('ward_id')     ? trim(Input::get('ward_id'))       : null;
        $itemPage           = Input::has('limit')       ? Input::get('limit')               : 20;
        $Cmd                = Input::has('cmd')         ? trim(Input::get('cmd'))           : '';
        
        $offset         = ($page - 1)*$itemPage;
       
        $statusCode = 200;
        
        
        $Model      = new CourierRefuseModel;
        
        if($city_id > 0){
            $Model  = $Model->where('province_id','=',$city_id);
        }
        if($district_id > 0){
            $Model  = $Model->where('district_id','=',$district_id);
        }
        if($ward_id > 0){
            $Model  = $Model->where('ward_id','=',$ward_id);
        }
        
        $Model  = $Model->orderBy('id', 'DESC');

        if($Cmd == 'export'){
            return $this->ExportExcel($Model);
        }

        $Total  = $Model->count();
        $Data   = $Model->skip($offset)->take($itemPage)->get();
        if(!$Data->isEmpty()){
            foreach($Data as $val){
                $ListDistrictId[] = (int)$val['district_id'];
                $ListWardId[]     = (int)$val['ward_id'];
            }
            $ListDistrictId = array_unique($ListDistrictId);
            $ListWardId = array_unique($ListWardId);

            if($ListDistrictId){
                $ListDistrict   = DistrictModel::whereIn('id',$ListDistrictId)->get(array('id','district_name'));
                if(!$ListDistrict->isEmpty()){
                    foreach($ListDistrict as $val){
                        $District[$val['id']]   = $val['district_name'];
                    }
                    if($District){
                        foreach($Data as $key => $val){
                            if(isset($District[$val['district_id']])){
                                $Data[$key]['district_name']    = $District[$val['district_id']];
                            }
                        }
                    }
                }
            }

            if($ListWardId){
                $ListWard   = WardModel::whereIn('id',$ListWardId)->get(array('id','ward_name'));
                if(!$ListWard->isEmpty()){
                    foreach($ListWard as $val){
                        $Ward[$val['id']]   = $val['ward_name'];
                    }
                    if($Ward){

                        foreach($Data as $key => $val){

                            if(isset($Ward[$val['ward_id']])){
                                $Data[$key]['ward_name']    = $Ward[$val['ward_id']];
                            }
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

    public function ExportExcel($Model){
        $Data  = $Model->with(['__get_city','__get_district','__get_ward','__get_courier'])->get()->toArray();

        Excel::selectSheetsByIndex(0)->load('/data/www/html/storage/template/ops/log_refuse.xls', function($reader) use($Data) {
            $reader->sheet(0, function($sheet) use($Data)
            {
                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        isset($val['__get_courier']['name']) ? $val['__get_courier']['name'] : '',
                        isset($val['__get_city']['city_name']) ? $val['__get_city']['city_name'] : '',
                        isset($val['__get_district']['district_name']) ? $val['__get_district']['district_name'] : '',
                        isset($val['__get_ward']['ward_name']) ? $val['__get_ward']['ward_name'] : '',
                    );

                    $sheet->appendRow($dataExport);
                }
            });
        },'UTF-8',true)->export('xls');
    }


    public function postCreateRefuse(){
        $Data       = Input::json()->all();

        $CityId     = isset($Data['city_id'])       ? (int)$Data['city_id']     : 0;
        $DistrictId = isset($Data['district_id'])   ? (int)$Data['district_id'] : 0;
        $WardId     = isset($Data['ward_id'])       ? trim($Data['ward_id'])    : 0;
        $CourierId  = isset($Data['courier_id'])    ? trim($Data['courier_id']) : 0;

        if(empty($CityId) || empty($DistrictId)  || empty($CourierId) ){
            return Response::json([
                'error' => true,
                'error_message'=> 'Vui lòng kiểm tra lại dữ liệu gửi lên'
            ]);
        }

        $Model = new CourierRefuseModel;

        if(empty($WardId)){
            $Wards          = new WardModel;
            $Wards          = $Wards->where('district_id', $DistrictId)->lists('id');
            try {
                foreach ($Wards as $key => $value) {
                   $Model->firstOrCreate([
                        'province_id' => $CityId,
                        'district_id' => $DistrictId,
                        'ward_id'     => $value,
                        'courier_id'  => $CourierId
                   ]);
                }
            } catch (Exception $e) {
                return Response::json([
                    'error'         => true,
                    'error_message' => 'Lỗi kết nối máy chủ',
                ]);
            }

            return Response::json([
                'error'         => false,
                'error_message' => 'Thêm thành công',
                'data'          => []
            ]);
            

            
        }else {
            $CheckExits = $Model->where('province_id', $CityId)->where('district_id', $DistrictId)->where('ward_id', $WardId)->where('courier_id', $CourierId)->first();
            if(!$CheckExits){
                $Model->province_id = $CityId;
                $Model->ward_id     = $WardId;
                $Model->district_id = $DistrictId;
                $Model->courier_id  = $CourierId;
                try {
                    $Model->save();
                } catch (Exception $e) {
                    return Response::json([
                        'error'         => true,
                        'error_message' => 'Lỗi kết nối máy chủ',
                    ]);
                }

                return Response::json([
                    'error'         => false,
                    'error_message' => 'Thêm thành công',
                    'data'          => $Model
                ]);
            }else {
                return Response::json([
                    'error'         => true,
                    'error_message' => 'Tuyến này đã có trong danh sách'
                ]);
            }
        }
    }

    public function getRemoveRefuse($Id)
    {

        $Model      = new CourierRefuseModel;
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

    public function postEditRefuse($Id)
    {
        $Data       = Input::json()->all();

        $WardId     = isset($Data['ward_id'])       ? (int)$Data['ward_id']     : 0;
        $CourierId  = isset($Data['courier_id'])    ? trim($Data['courier_id']) : 0;

        $Model      = new CourierRefuseModel;
        $Model      = $Model::find($Id);
        $statusCode = 200;
        if($Model){
            if($WardId){
                $Model->ward_id = $WardId;
            }

            if($CourierId){
                $Model->courier_id = $CourierId;
            }
            $Save = $Model->save();
            if($Save){
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
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate()
	{ 
        $Data           = Input::json()->all();
        $CityId         = isset($Data['city_id'])       ? (int)$Data['city_id']     : 0;
        $DistrictId     = isset($Data['district_id'])   ? (int)$Data['district_id'] : 0;
        $WardName       = isset($Data['ward_name'])     ? trim($Data['ward_name'])  : null;
        
        $Model      = new WardModel;
        $statusCode = 200;
        
        if(empty($WardName)){
            $contents = array(
                'error' => true, 'message' => 'values empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(
            array(
                'city_id'       => $CityId,
                'district_id'   => $DistrictId,
                'ward_name'     => $WardName
            ));
            
        if($Id){
            //cache
            $this->district_id  = $DistrictId;
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
	public function getShow($Id)
	{
        $Model      = new WardModel;
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
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function postEdit($Id)
	{
		$Data           = Input::json()->all();
        $CityId         = isset($Data['city_id'])       ? (int)$Data['city_id']     : null;
        $DistrictId     = isset($Data['district_id'])   ? (int)$Data['district_id'] : null;
        $WardName       = isset($Data['ward_name'])     ? trim($Data['ward_name'])  : null;
        $statusCode     = 200;
        
        if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Model = new WardModel;
        $Model = $Model::find($Id);
        if($Model){
            if(isset($CityId))      $Model->city_id         = $CityId;
            if(isset($DistrictId))  $Model->district_id     = $DistrictId;
            if(!empty($WardName))   $Model->ward_name       = $WardName;
            $Update = $Model->save();
       
            if($Update){
                
                //cache
                $this->district_id  = (int)$Model->district_id;
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
	public function getDestroy($Id)
	{
        $Model      = new WardModel;
        $Model      = $Model::find($Id);
        $statusCode = 200;
        if($Model){
            $district_id = (int)$Model->district_id;
            $Delete = $Model->delete();
            if($Delete){
                //cache
                $this->district_id  = $district_id;
                $this->CacheList();
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
    
    private function CacheList(){
        Cache::forget('ward_cache_'.$this->district_id);
        $Data   = WardModel::where('district_id','=',$this->district_id)->orderBy('ward_name', 'ASC')->get(array('id','ward_name'));
        if(!$Data->isEmpty()){
            Cache::forever('ward_cache_'.$this->district_id,$Data);
        }
        return true;
    }
    
    public function getCache($district = 0){
        $this->district_id = $district > 0 ? (int)$district : $this->district_id;
        Cache::forget('ward_cache_'.$this->district_id);
        if(Cache::has('ward_cache_'.$this->district_id)){
              return Cache::get('ward_cache_'.$this->district_id);
            }else{
                $this->CacheList();
                if(Cache::has('ward_cache_'.$this->district_id)){
                 return Cache::get('ward_cache_'.$this->district_id);
                }
            }
        return false;
    }
    //
    public function getWardcache($district = 0){
        $statusCode = 200;
        $this->district_id = $district > 0 ? (int)$district : $this->district_id;
        Cache::forget('ward_cache_'.$this->district_id);
        if(Cache::has('ward_cache_'.$this->district_id)){
                $listWard = Cache::get('ward_cache_'.$this->district_id);
                $contents = array(
                    'error'     => false,
                    'message'   => 'success',
                    'data'      => $listWard
                );
                return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
            }else{
                $this->CacheList();
                if(Cache::has('ward_cache_'.$this->district_id)){
                    $listWard = Cache::get('ward_cache_'.$this->district_id);

                    $contents = array(
                        'error'     => false,
                        'message'   => 'success',
                        'data'      => $listWard
                    );
                    return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'not exits'
                    );
                    return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
                }
            }
        return false;
    }
    
    
    
}
