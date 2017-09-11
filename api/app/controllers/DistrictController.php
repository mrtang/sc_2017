<?php

class DistrictController extends \BaseController {
    private $domain = '*';
    private $cityid = 18;
    
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
    // 
    public function getAll(){
        $Data = $this->getCachealldistrict();
        
        return Response::json([
            'error'         => false,
            'error_message' => '',
            'data'          => $Data ? $Data : []
        ]);
    }


    public function getDistrictByRemote(){
        $Model = new DistrictModel;
        $Model = $Model->where('remote', 2)->lists('id');

        return $this->_ResponseData($Model);
    }

	public function getIndex()
	{
        $page           = Input::has('page')            ? (int)Input::get('page')           : 1;
        $city_id        = Input::has('city_id')         ? (int)Input::get('city_id')        : 0;
        $district_name  = Input::has('district_name')   ? trim(Input::get('district_name')) : null;
        $remote         = Input::has('remote')          ? Input::get('remote')              : false;
        $itemPage       = Input::has('limit')           ? (int)Input::get('limit') : 20;
        $offset         = ($page - 1) * $itemPage;
        $statusCode     = 200;
        $itemPage = ($itemPage) ? $itemPage : 20;
        if(false/*$city_id > 0 && $itemPage == 'all'*/){
            $this->cityid   =$city_id;
            $Data           = $this->GetCache();

            if(!empty($Data)){
                $contents = array(
                    'error'     => false,
                    'message'   => Lang::get('response.SUCCESS'),
                    'data'      => $Data
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => Lang::get('response.DATA_EMPTY')
                );
            }
        }else{            
            $Model = new DistrictModel;
            
            if($city_id > 0){
                $Model = $Model->where('city_id','=',$city_id);
            }

            if(!empty($district_name)){
                $Model = $Model->where('district_name','LIKE','%'.$district_name.'%')->orderBy('district_name','DESC');
            }
            $ModelTotal     = clone $Model;
            $Data           = $Model->orderBy('district_name','DESC')->remember(1440)->get()->toArray();

            $_RemoteDistrictIds = array();
            $_RemoteWards       = array();
            $Wards  = null;
            if($remote == true){
                $Wards  = new WardModel;
                if($Data){
                    foreach ($Data as $key => $value) {
                        $Data[$key]['wards'] = array();
                        $Data[$key]['district_id']  = $value['id'];
                        if($value['remote'] == 2){
                            $_RemoteDistrictIds[] = $value['id'];
                        }
                    }
                }
                
                if(!empty($_RemoteDistrictIds)){
                    $Wards = $Wards->select('id as ward_id', 'district_id', 'ward_name')->whereIn('district_id', $_RemoteDistrictIds)->get()->toArray();
                    foreach ($Data as $key => $value) {
                        
                        foreach ($Wards as $wkey => $wvalue) {
                            if($value['id'] == $wvalue['district_id']){
                                $Data[$key]['wards'][] = $wvalue;
                            }
                        }
                    }
                }

            }
            
            $contents = array(
                'error'     => false,
                'message'   => '',
                'total'     => $ModelTotal->count(),
                'data'      => $Data
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
        
        $CityId         = isset($Data['city_id'])       ? (int)$Data['city_id']         : null;
        $DistrictName   = isset($Data['district_name']) ? trim($Data['district_name'])  : null;
        
        $Model      = new DistrictModel;
        $statusCode = 200;
       
        if( empty($CityId) || empty($DistrictName)){
            $contents = array(
                'error' => true, 'message' => Lang::get('response.DATA_EMPTY')
            );
            
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(
            array(
                'city_id'           => $CityId,
                'district_name'     => $DistrictName
            ));
        if($Id){
            // cache
            $this->cityid = $CityId;
            $this->CacheList();
                
            $contents = array(
                'error'     => false,
                'message'   => Lang::get('response.SUCCESS'),
                'id'        => $Id
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => Lang::get('response.FAIL_QUERY')
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
        $Model = new DistrictModel;
        $Model = $Model::find($Id);
        $statusCode = 200;
        
        if($Model){
            $contents   = array(
                'error'     => false,
                'message'   => Lang::get('response.SUCCESS'),
                'data'      => $Model
            );
        }else{
            $contents   = array(
                'error'     => true,
                'message'   => Lang::get('response.NOT_EXISTS')
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
		$Data       = Input::json()->all();
        
        $CityId         = isset($Data['city_id'])       ? (int)$Data['city_id']         : null;
        $DistrictName   = isset($Data['district_name']) ? trim($Data['district_name'])  : null;
        
        $statusCode = 200;
        
        if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => Lang::get('response.NOT_EXISTS')
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Model = new DistrictModel;
        $Model = $Model::find($Id);
        if($Model){
            if(!empty($CityId))         $Model->city_id         = $CityId;
            if(!empty($DistrictName))   $Model->district_name   = $DistrictName;
            
            $Update = $Model->save();
       
            if($Update){
                
                // cache
                $this->cityid = (int)$Model->city_id;
                $this->CacheList();
                
                $contents = array(
                    'error'     => false,
                    'message'   => Lang::get('response.SUCCESS')
                );
            }else{
                $contents = array(
                    'error' => true,
                    'message' => Lang::get('response.FAIL_QUERY')
                );
            }
        }else{
            $contents = array(
                'error' => true,
                'message' => Lang::get('response.NOT_EXISTS')
            );
        }
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}

    public function getShipchung(){
        $cities = Input::get('cities');
        $cmd = Input::get('cmd');
        $_resp  = array();
        $sql    = "SELECT lc_city.city_name, lc_city.priority as _priority, lc_district.district_name , lc_district.id as district_id , lc_city.id as city_id";
        $sql    .= " FROM `lc_city` INNER JOIN `lc_district` ON lc_district.city_id = lc_city.id ";
        
        $cities = explode(',', $cities);
        $cities = implode(',', $cities);
        
        if($cities){
            $sql .= " WHERE lc_city.id IN (".$cities.")";
        }

        if(!empty($cmd) && $cmd == 'index'){
            $sql .= " OR lc_district.id IN (215, 213, 285, 299, 331, 371, 533)";  
        }
        
        $sql .= " ORDER BY _priority ASC";
        /*return $sql;*/
        
        $results = DB::select( DB::raw($sql));

        $listDistrict = [];
        foreach ($results as $key => $value) {
            $listDistrict[] =  $value->district_id;
        }

        $Location = AreaLocationModel::whereIn('province_id', $listDistrict)->where('active', 1)->get();
        $LocationData = [];
        foreach ($Location as $key => $value) {
            $LocationData[$value['province_id']] = $value['location_id'];
        }

        foreach ($results as $key => $value) {
            $cityInfo = array(
                'city_name' => $value->city_name,
                'city_id' => $value->city_id
            );

            if(!isset($_resp[$value->city_id])){
                $_resp[$value->city_id] = $cityInfo;
            }
            
            if(!isset($_resp[$value->city_id]['districts'])){
                $_resp[$value->city_id]['districts'] = array();
            }
            $districtInfo = array(
                'district_name' => $value->district_name,
                'district_id'   => $value->district_id,
                'location'      => !empty($LocationData[$value->district_id]) ? $LocationData[$value->district_id] : 0
            );

            $_resp[$value->city_id]['districts'][]  = $districtInfo;

        }

        $data_return = [];
        foreach ($_resp as $key => $value) {
            $data_return[] = $value;
        }
        
        return Response::json($data_return) ;
    }
    
    public function getSearch(){
        $cities = Input::get('cities');
        $cmd = Input::get('cmd');
        $_resp  = array();
        $sql    = "SELECT lc_city.city_name, lc_district.district_name , lc_district.id as district_id , lc_city.id as city_id";
        $sql    .= " FROM `lc_city` INNER JOIN `lc_district` ON lc_district.city_id = lc_city.id ";
        
        $cities = explode(',', $cities);
        $cities = implode(',', $cities);
        
        if($cities){
            $sql .= " WHERE lc_city.id IN (".$cities.")";
        }

        if(!empty($cmd) && $cmd == 'index'){
            $sql .= " OR lc_district.id IN (215, 213, 285, 299, 331, 371, 533)";  
        }
        
        /*return $sql;*/
        
        $results = DB::select( DB::raw($sql));

        $listDistrict = [];
        foreach ($results as $key => $value) {
            $listDistrict[] =  $value->district_id;
        }

        $Location = AreaLocationModel::whereIn('province_id', $listDistrict)->where('active', 1)->get();
        $LocationData = [];
        foreach ($Location as $key => $value) {
            $LocationData[$value['province_id']] = $value['location_id'];
        }

        foreach ($results as $key => $value) {
            $cityInfo = array(
                'city_name' => $value->city_name,
                'city_id' => $value->city_id
            );

            if(!isset($_resp[$value->city_id])){
                $_resp[$value->city_id] = $cityInfo;
            }
            
            if(!isset($_resp[$value->city_id]['districts'])){
                $_resp[$value->city_id]['districts'] = array();
            }
            $districtInfo = array(
                'district_name' => $value->district_name,
                'district_id'   => $value->district_id,
                'location'      => !empty($LocationData[$value->district_id]) ? $LocationData[$value->district_id] : 0
            );

            $_resp[$value->city_id]['districts'][]  = $districtInfo;

        }

        return $_resp;
    }
	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getDestroy($Id)
	{
        $Model = new DistrictModel;
        $Model = $Model::find($Id);
        $statusCode = 200;
        
        if($Model){
            $City  = (int)$Model->city_id;
            $Delete = $Model->delete();
            if($Delete){
                
                // cache
                $this->cityid   = $City;
                $this->CacheList();
                
                $contents = array(
                    'error'     => false,
                    'message'   => Lang::get('response.SUCCESS')
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => Lang::get('response.FAIL_QUERY')
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => Lang::get('response.NOT_EXISTS')
            );
        }
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}

    private function CacheList(){
        // TODO : Edited by ThinhNV remove field `remote` in query
        $Data   = DistrictModel::where('city_id','=',(int)$this->cityid)->orderBy('district_name','DESC')->get(array('id','district_name','remote'));
        if(!$Data->isEmpty()){
            Cache::forever('district_cache_'.(int)$this->cityid, $Data);
        }
        return true;
    }
    
    public function GetCache($city = 0){
        $this->cityid = $city > 0 ? (int)$city : $this->cityid;
        Cache::forget('district_cache_'.$this->cityid);
        if(Cache::has('district_cache_'.$this->cityid)){
              return Cache::get('district_cache_'.$this->cityid);
        }else{
            $this->CacheList();
            if(Cache::has('district_cache_'.$this->cityid)){
             return Cache::get('district_cache_'.$this->cityid);
            }
        }
        return false;
    }



    //cache all district
    private function CacheAllDistrict(){
        Cache::forget('cache_district_all');
        $Data   = DistrictModel::orderBy('district_name','DESC')->get(array('id', 'city_id', 'district_name','remote'));
        if(!$Data->isEmpty()){
            Cache::put('cache_district_all', $Data, 1440);
        }
        return true;
    }
    public function getCachealldistrict(){
        
        if(Cache::has('cache_district_all')){
              return Cache::get('cache_district_all');
        }else{
            $this->CacheAllDistrict();
            if(Cache::has('cache_district_all')){
                return Cache::get('cache_district_all');
            }
        }
        return false;
    }
}
