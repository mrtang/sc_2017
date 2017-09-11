<?php
use metadatamodel\WebhookModel;

class ApiKeyController extends \BaseController {
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
		$UserInfo   = $this->UserInfo();
        $UserId = (int)$UserInfo['id'];
        
        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => ApiKeyModel::where('user_id',$UserId)->orderBy('time_create','DESC')->get()->toArray()
        );
        
        return Response::json($contents);
        
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate()
	{
        $UserInfo   = $this->UserInfo();
        $UserId = (int)$UserInfo['id'];
        
        $Key = $this->Encrypt32($UserInfo['email'].$UserInfo['id'].$this->time());
        
        $Insert = ApiKeyModel::create(array('user_id' => $UserId, 'key' => $Key, 'time_create' => $this->time(), 'active' => 1));

        if($Insert){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => array(
                    'id'            => $Insert['id'],
                    'key'           => $Insert['key'],
                    'time_create'   => $Insert['time_create'],
                    'active'        => $Insert['active']
                )
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'insert fail'
            );
        }
        
        return Response::json($contents);
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
	public function show($id)
	{
		//
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function postEdit($id)
	{
	    $UserInfo   = $this->UserInfo();
        $UserId = (int)$UserInfo['id'];
        
        $validation = Validator::make(array('id' => $id), array(
            'id'        => 'required|numeric|exists:merchant_token,id,user_id,'.$UserId 
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages(),'data' => array()), 200);
        }
        
        $Active     = (int)Input::json()->get('active');
        
        $Model      = new ApiKeyModel;
        $Data       = $Model::find($id);
        
        if(isset($Active))     $Data->active   = $Active;
        
        $Update = $Data->save();
       
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
        
        return Response::json($contents);
	}

	public function postAuto($id)
	{
	    $UserInfo   = $this->UserInfo();
        $UserId = (int)$UserInfo['id'];
        
        $validation = Validator::make(array('id' => $id), array(
            'id'        => 'required|numeric|exists:merchant_token,id,user_id,'.$UserId 
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages(),'data' => array()), 200);
        }
        
        $Auto       = (int)Input::json()->get('auto');
        
        $Model      = new ApiKeyModel;
        $Data       = $Model::find($id);
        
        if(isset($Auto))       $Data->auto     = $Auto;
        
        $Update = $Data->save();
       
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
        
        return Response::json($contents);
	}

    //
    public function getListurlapi(){
        $UserInfo   = $this->UserInfo();
        $UserId = (int)$UserInfo['id'];

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => WebhookModel::where('seller_id',$UserId)->orderBy('time_create','DESC')->get()->toArray()
        );
        
        return Response::json($contents);
    }
    public function postAddurl(){
        $UserInfo   = $this->UserInfo();
        $UserId = (int)$UserInfo['id'];
        $DataInsert = array();

        $Group              = 3;
        $CacheName          = 'cache_group_status_'.$Group.'_'.$this->lang;
        if (Cache::has($CacheName)){
            $ListStatus    = Cache::get($CacheName);
        }else{
            $Model      = new metadatamodel\GroupStatusModel;
            $ListStatus  = $Model::where('group', $Group)->with('group_order_status')->get()->toArray();
            if(!empty($ListStatus)){
                Cache::put($CacheName,$ListStatus,30);
            }
        }

        $Link       = Input::json()->get('link');

        if($Link == ''){
            $contents = array(
                'error' => true,
                'message' => 'Link not null!'
            );
            return Response::json($contents);
        }
        foreach($ListStatus AS $One){
            $DataInsert[] = array(
                'seller_id' => $UserId,
                'status_group' => $One['id'],
                'status_name' => $One['name'],
                'link' => $Link,
                'time_create' => time(),
                'client_secret' => md5(time().$UserId)
            );
        }
        $Insert = WebhookModel::insert($DataInsert);
        if($Insert){
            $contents = array(
                'error'     => false,
                'message'   => 'success'
            );
        }else{
            $contents = array(
                'error' => true,
                'message' => 'Insert error!!!'
            );
        }
        
        return Response::json($contents);
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
	public function destroy($id)
	{
		//
	}


}
