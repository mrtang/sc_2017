<?php

class PostManCareController extends \BaseController {
    private $domain = '*';
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
        $page       = Input::has('page')        ? (int)Input::get('page') : 1;
        $itemPage   = Input::has('item_page')   ? (int)Input::get('item_page') : 20;
        $PostManId  = Input::has('postman_id')  ? (int)Input::get('postman_id') : 0;       
        $offset     = ($page - 1)*$itemPage;
       
        $statusCode = 200;
        $Model = new PostManCareModel;
        if($PostManId > 0){
            $Data = $Model::where('postman_id','=',$PostManId)->get();
            if(!$Data->isEmpty()){
                $Total  = count($Data);
                $ListCityName   = array();
                
                if (Cache::has('city_cache')){
                    $ListCity    = Cache::get('city_cache');
                    
                }else{
                    $ListCity       = CityModel::all(array('id','city_name'));
                }
                
                if($ListCity){
                    foreach($ListCity as $val){
                        $City[$val['id']]   = $val;
                    }
                }
                
                foreach($Data as $val){
                    $ListDistrict[]                             = $val['district_id'];
                    $ListWard[]                                 = $val['ward_id'];
                    $ListId[$val['ward_id']]                    = $val['id'];
                }
                
                if($ListDistrict){
                    $ListDistrict   = array_unique($ListDistrict);
                    $District       = DistrictModel::whereIn('id', $ListDistrict)->get(array('id','city_id','district_name'));
                    if($District){
                        foreach($District as $val){
                            $LDistrict[$val['id']] = $val;
                        }
                    }
                }
                
                if($ListWard){
                    $ListWard   = array_unique($ListWard);
                    $Ward       = WardModel::whereIn('id', $ListWard)->get(array('id','ward_name','district_id'));
                    if($Ward){
                        foreach($Ward as $val){
                            if(isset($ListId[$val['id']])){
                                $val['care_id']  = $ListId[$val['id']];
                            }
                            
                            $LWard[$val['district_id']][]  = $val;
                        }
                    }
                }
                
                if(isset($LDistrict) && isset($LWard)){
                    foreach($LDistrict as $key => $val){
                        if(isset($LWard[$key])){
                            $LDistrict[$key]['child'] = $LWard[$key];
                            $LDistrict[$key]['count'] = count($LWard[$key]);
                        }
                    }
                }
                
                if(isset($LDistrict)){
                    foreach($LDistrict as $val){
                        $LCity[$val['city_id']][]   = $val;
                    }
                }
                
                if($City){
                    foreach($LCity as $key => $val){
                        if(isset($City[$key])){
                            $Location            = $City[$key];
                            $Location['child']    = $val;
                        }
                    }
                }
                
                $contents = array(
                    'error'         => false,
                    'message'       => 'success',
                    'data'          => isset($Location) ? $Location : array(),
                    'total'         => $Total
                );
            }else{
                $contents = array(
                    'error'         => true,
                    'message'       => 'not exits',
                );
            }
        }else{
            $contents = array(
                'error' => false,
                'message' => 'success',
                'total' => $Model::count(),
                'data'  => $Model::skip($offset)->take($itemPage)->get()
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
        $CityId         = isset($Data['city_id'])       ? (int)$Data['city_id']     : null;
        $PostManId      = isset($Data['postman_id'])    ? (int)$Data['postman_id']  : null;
        $DistrictId     = isset($Data['district_id'])   ? (int)$Data['district_id'] : null;
        $WardId         = isset($Data['ward_id'])       ? (int)$Data['ward_id']     : 0;
        
        $Model      = new PostManCareModel;
        $statusCode = 200;
        
        if(empty($CityId) || empty($PostManId) || empty($DistrictId)){
            $contents = array(
                'error' => true, 'message' => 'values empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(
                    array(
                        'city_id'           => $CityId, 
                        'postman_id'        => $PostManId, 
                        'district_id'       => $DistrictId, 
                        'ward_id'           => $WardId
                    ));
        
        if($Id){
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
	public function getShow($id)
	{
		$Model          = new PostManCareModel;
        $Model          = $Model::find($id);
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
	public function postEdit($id)
	{
		$Data           = Input::json()->all();
        $CityId         = isset($Data['city_id'])       ? (int)$Data['city_id']     : null;
        $PostManId      = isset($Data['postman_id'])    ? (int)$Data['postman_id']  : null;
        $DistrictId     = isset($Data['district_id'])   ? (int)$Data['district_id'] : null;
        $WardId         = isset($Data['ward_id'])       ? (int)$Data['ward_id']     : null;
        $statusCode     = 200;
        
        if($id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
            
        $Model = new PostManCareModel;
        $Model = $Model::find($id);
        if($Model){
            if(!empty($CityId))         $Model->city_id         = $CityId;
            if(!empty($PostManId))      $Model->postman_id      = $PostManId;
            if(!empty($DistrictId))     $Model->district_id     = $DistrictId;
            if(isset($WardId))          $Model->ward_id         = $WardId;
            
            $Update = $Model->save();
       
            if($Update){
                $contents = array(
                    'error'     => false,
                    'message'   => 'success'
                );
            }else{
                $contents = array(
                    'error' => true,
                    'message' => 'fail'
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
	public function getDestroy($id)
	{
		
        $Model = new PostManCareModel;
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
