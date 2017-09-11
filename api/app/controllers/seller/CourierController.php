<?php namespace seller;

use Validator;
use Response;
use Input;
use Cache;
use sellermodel\CourierModel;

class CourierController extends \BaseController {

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
	public function create()
	{
		//
	}
    
    /**
	 * Show the form for multi creating a new resource.
	 *
	 * @return Response
	 */
	public function postMulticreate()
	{
		$UserInfo   = $this->UserInfo();
        $id         = (int)$UserInfo['id'];
        
        $Data =  Input::json()->all();
        if($Data){
            $Model  = new CourierModel;
            foreach($Data as $val){
                $Data          = $Model->firstOrCreate(array('user_id' => $id, 'courier_id' => (int)$val['courier_id']));
                $Data->support_hcm      =  (int)$val['support_hcm'];
                $Data->support_hn       =  (int)$val['support_hn'];
                $Data->support_other    =  (int)$val['support_other'];
                $Data->priority         =  (int)$val['priority'];
                $Data->active           =  (int)$val['active'];
                $Data->save();
            }

            $contents = array(
                'error'     => false,
                'message'   => 'success'
            );
                
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'data empty'
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
	public function getShow()
	{
	    $UserInfo   = $this->UserInfo();
        $id         = (int)$UserInfo['id'];
        $active		= Input::has('active') ? Input::get('active') : "";

        //return $active;
        // List Courier
        $CourierModel       = new \CourierModel;
        $Data = $CourierModel::where('default' ,0);

       	if(!empty($active)){
       		$Data = $Data->where('active', '=', $active);
       	}
   		
       	
        $Data = $Data->with(array('seller_courier_config' => function($query) use($id){
                                                                $query->where('user_id','=', $id);
                                                            }))->get(array('id', 'name'))->toArray();

        if($Data){
            foreach($Data as $key => $val){
                if(empty($val['seller_courier_config'])){
                    $Data[$key]['seller_courier_config']    = array(
                            'active'        => 0,
                            'support_hcm'   => 0,
                            'support_hn'    => 0,
                            'support_other' => 0,
                            'priority'      => 0,
                            'courier_id'    => $val['id']
                    );
                }else{
                    $Data[$key]['seller_courier_config']    = $val['seller_courier_config'][0];
                }
            }
            
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $Data
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'list courier empty'
            );
        }
        
        return Response::json($contents);
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
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
