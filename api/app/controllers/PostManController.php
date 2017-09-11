<?php
class PostManController extends \BaseController {
    private $domain = '*';
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
     
    public function __construct(){
        
    }
    
    public function getAbc(){
        $user = $this->UserInfo();
        $App = new \AppController();

        $PrivilegeGroup     = $App->Privilege((int)$user['group']);
	
        return Response::json([
			'user'=> $user,
            'group'=> $user['group'],
            'privige'=> $user['privilege'],
            '1'=>  $this->check_privilege('PRIVILEGE_TICKET','edit')


		]);
    }
	public function getIndex()
	{  
        $page       = Input::has('page')        ? (int)Input::get('page')           : 1;
        $itemPage   = Input::has('item_page')   ? (int)Input::get('item_page')      : 20;
        $cityId     = Input::has('city_id')     ? (int)Input::get('city_id')        : 0;
        $districtId = Input::has('district_id') ? (int)Input::get('district_id')    : 0;
        $wardId     = Input::has('ward_id')     ? (int)Input::get('ward_id')        : 0;
        $courierId  = Input::has('courier_id')  ? (int)Input::get('courier_id')     : 0;


        $offset     = ($page - 1) * $itemPage;
       
        $Model  = new PostManModel;

        if(!empty($cityId)){
            $Model = $Model->where('city_id', $cityId);
        }
        if(!empty($districtId)){
            $Model = $Model->where('district_id', $districtId);
        }
        if(!empty($wardId)){
            $Model = $Model->where('ward_id', $wardId);
        }

        if(!empty($courierId)){
            $Model = $Model->where('courier_id', $courierId);
        }
        

        $Data   = $Model->skip($offset)->take($itemPage)->orderBy('id','DESC')->get();

        if($Data){
            $listward = [];

            foreach ($Data as $key => $value) {
                $listward[] = $value['ward_id'];
            }
            $listward = array_unique($listward);
            $Wards = $this->getWard($listward);

            if (Cache::has('courier_cache')){
                $ListCourier    = Cache::get('courier_cache');
                
            }else{
                $Courier        = new CourierModel;
                $ListCourier    = $Courier::all(array('id','name'));
            }
            
            if($ListCourier){
                foreach($ListCourier as $val){
                    $LCourier[$val['id']]   = $val['name'];
                }
                foreach($Data as $key => $val){
                    if (isset($LCourier[$val['courier_id']])){
                        $val->courier_name = $LCourier[$val['courier_id']];
                    }
                    if(!empty($Wards[$value['ward_id']])){
                        $val->ward_name = $Wards[$value['ward_id']];
                    }
                }
            }
        }
        
        $statusCode = 200;
        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'total'         => $Model->count(),
            'item_page'     => $itemPage,
            'data'          => $Data,
            
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
        $Data       = Input::json()->all();
        $CourierId  = isset($Data['courier_id'])    ? (int)$Data['courier_id']  : 0;
        $Name       = isset($Data['name'])          ? $Data['name']             : null;
        $Code       = isset($Data['code'])          ? $Data['code']             : null;
        $Phone      = isset($Data['phone'])         ? $Data['phone']            : null;
        $Active     = isset($Data['active'])        ? (int)$Data['active']      : 0;
        
        $Model      = new PostManModel;
        $statusCode = 200;
        
        if(empty($CourierId) || empty($Name) || empty($Phone)){
            $contents = array(
                'error' => true, 'message' => 'values empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
        
        $Id = $Model::insertGetId(
                    array(
                        'courier_id'    => $CourierId, 
                        'name'          => $Name, 
                        'phone'         => $Phone, 
                        'code'          => $Code, 
                        'active'        => $Active
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
	public function getShow($Id)
	{
        $Model      = new PostManModel;
        $Model      = $Model::find($Id);
        $statusCode = 200;
        if($Model){
            
            if (Cache::has('courier_cache')){
                $ListCourier    = Cache::get('courier_cache');
            }else{
                $Courier        = new CourierModel;
                $ListCourier    = $Courier::all(array('id','name'));
            }
            
            if($ListCourier){
                foreach($ListCourier as $val){
                    if($val['id']  == $Model->courier_id){
                        $Model->courier_name   = $val['name'];
                    }
                }
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
	public function postEdit($Id)
	{
		$Data       = Input::json()->all();
        $CourierId  = isset($Data['courier_id'])    ? (int)$Data['courier_id']  : null;
        $Name       = isset($Data['name'])          ? trim($Data['name'])       : null;
        $Phone      = isset($Data['phone'])         ? trim($Data['phone'])      : null;
        $Active     = isset($Data['active'])        ? (int)$Data['active']      : null;
        $Code       = isset($Data['code'])          ? trim($Data['code'])       : null;
        $statusCode = 200;
        
        if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }
            
        $Model = new PostManModel;
        $Model = $Model::find($Id);
        if($Model){
            if(isset($CourierId))   $Model->courier_id      = $CourierId;
            if(!empty($Name))       $Model->name            = $Name;
            if(!empty($Phone))      $Model->phone           = $Phone;
            if(isset($Active))      $Model->active          = $Active;
            if(!empty($Code))       $Model->code            = $Code;
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
	public function getDestroy($Id)
	{
        $Model      = new PostManModel;
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
}
