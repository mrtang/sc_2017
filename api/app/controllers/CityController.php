<?php
use Carbon\Carbon;
//use LMongo;
class CityController extends \BaseController {
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
        $city_name  = Input::has('city_name')   ? Input::get('city_name') : null;
        $statusCode = 200;
        
        if($itemPage == 'all'){
            $Data   = $this->GetCache();
            
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
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
            
            $Model = new CityModel;
            
            if(!empty($city_name)){
                $Model = $Model::where('city_name', 'LIKE', "%$city_name%");
            }
            
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'total'     => $Model->count(),
                'data'      => $Model->skip($offset)->take($itemPage)->get()
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
        $Code           = isset($Data['code']) ? trim($Data['code']) : null;
        $CityName       = isset($Data['city_name']) ? trim($Data['city_name']) : null;
        $Region         = isset($Data['region']) ? (int)$Data['region'] : null;
      
        $Model = new CityModel;
        $statusCode = 200;
        
        if(empty($CityName) || empty($Region)){
            $contents = array(
                'error' => true, 'message' => 'values empty'
            );
            
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
            
        $Id = $Model::insertGetId(
            array(
                'code'          => $Code,
                'city_name'     => $CityName,
                'region'        => $Region
                )
        );
            
        if($Id){
            
            // cache
            $this->CacheList();
            
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
	public function getShow($Id)
	{
        $Model      = new CityModel;
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
        $Code           = isset($Data['code']) ? trim($Data['code']) : null;
        $CityName       = isset($Data['city_name']) ? trim($Data['city_name']) : null;
        $Region         = isset($Data['region']) ? (int)$Data['region'] : null;
        $statusCode     = 200;
        
        if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
        }else{
            $Model = new CityModel;
            $Model = $Model::find($Id);
            if($Model){
                if(isset($Code))         $Model->code            = $Code;
                if(!empty($CityName))    $Model->city_name       = $CityName;
                if(!empty($Region))      $Model->region          = $Region;
                
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
        $Model = new CityModel;
        $Model = $Model::find($Id);
        $statusCode = 200;
        
        if($Model){
            $Delete = $Model->delete();
            if($Delete){
                
                // cache
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
        $Data = CityModel::orderBy('city_name', 'ASC')->get(array('id','city_name','region'));
        if(!$Data->isEmpty()){
            foreach($Data as $key => $val){
                if(in_array($val->id, array(18,52))){
                    $Value[]  = $val;
                }else{
                    $Value_old[]    = $val;
                }
            }
            
           $Data = array_merge((array)$Value, (array)$Value_old);
           Cache::put('city_cache', $Data, 1440);
        }
        return true;
    }

    private function CacheCourier(){
        $Data = CityModel::orderBy('city_name', 'ASC')->get(array('id','city_name'));
        if(!$Data->isEmpty()){
            foreach($Data as $key => $val){
                $output[$val['id']] = $val['city_name']; 
            }
            
           Cache::forever('city2_cache', $output);
        }
        return true;
    }

    public function GetCache(){
        if(Cache::has('city_cache')){
            return Cache::get('city_cache');
        }else{
            $this->CacheList();
            if(Cache::has('city_cache')){
               return Cache::get('city_cache');
            }
        }
        return false;
    }
    
    public function getCachecourier(){
        if(Cache::has('city2_cache')){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => Cache::get('city2_cache')
            );
            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }else{
            $this->CacheCourier();
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => Cache::get('city2_cache')
            );
            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }
        return false;
    }



    public function getSync($_skip = 0, $_take = 1000, $_total = 0){
        set_time_limit(1800);
        $Model = new CityModel;
        $Data  = $Model->join('lc_district', 'lc_city.id', '=', 'lc_district.city_id');
        $Data  = $Data->join('lc_ward', 'lc_district.id', '=', 'lc_ward.district_id');

        if($_total == 0){
            $_total = clone $Data;
            $_total = $_total->count();
        }
        $Data  = $Data->skip($_skip)->take($_take);
        $Data  = $Data->select(['lc_city.id as city_id', 'lc_city.city_name', 'lc_district.id as district_id', 'district_name', 'lc_ward.id as ward_id', 'ward_name']);
        $Data  = $Data->get();

        
        foreach ($Data as $key => $value) {

            if(preg_match('/Quận/',$value['district_name'])){
                $add = implode(', ', [$value['city_name'], $value['district_name']]);
                $data = \LMongo::connection('cdt')->collection('address')->where('city_id', $value['city_id'])->where('district_id', $value['district_id'])->first();
                
                if($data){
                    //echo $add." da ton tai ! \n";
                }else {
                    $ret = \LMongo::connection('cdt')->collection('address')->insert([
                        "city_id" => $value['city_id'], 
                        "district_id" => $value['district_id'],
                        "name" => $add
                    ]);

                    if($ret){
                        
                    }else {
                        echo "Cap nhat loi : ". $add." \n";
                    }    
                }
            }else {
                $add = implode(', ', [$value['city_name'], $value['district_name'], $value['ward_name']]);
                $data = \LMongo::connection('cdt')->collection('address')->where('city_id', $value['city_id'])->where('district_id', $value['district_id'])->where('ward_id', $value['ward_id'])->first();

                if($data){
                    //echo $add." da ton tai ! \n";
                }else {
                    $ret = \LMongo::connection('cdt')->collection('address')->insert([
                        "city_id" => $value['city_id'], 
                        "district_id" => $value['district_id'],
                        "name" => $add,
                        "ward_id" => $value['ward_id']
                    ]);
                    if($ret){
                        
                    }else {
                        echo "Cap nhat loi : ". $add." \n";
                    }    
                }
            }

        }
        
        if($_total > $_skip){
            $_skip = $_skip + 1000;

            echo "Da cap nhat : ".$_skip." ban ghi \n";
            $this->getSync($_skip, $_take, $_total);
        }else {
            echo "Done : " + $_total;
        }



        
    }

}
