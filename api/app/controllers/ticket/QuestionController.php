<?php namespace ticket;

use Validator;
use Response;
use Input;
use Cache;
use ticketmodel\QuestionModel;
use Lang;

class QuestionController extends \BaseController {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
        $UserInfo   = $this->UserInfo();
        /**
        *  Validation params
        * */

        $validation = Validator::make(Input::all(), array(
            'page'          => 'sometimes|numeric'
        ));
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }
        
        /**
         * Get Data 
         * */
        
        $page       = Input::has('page')   ? (int)Input::get('page')    : 1;
        $itemPage   = Input::has('limit')  ? Input::get('limit')        : 20;
        
        $offset     = ($page - 1)*$itemPage;
        $statusCode = 200;
        
        if($itemPage == 'all'){
            $Data   = $this->GetCache();
            
            if(!empty($Data)){
                $contents = array(
                    'error'     => false,
                    'message'   => Lang::get('response.SUCCESS'),
                    'data'      => $Data,
                    'privilege' => (int)$UserInfo['privilege']
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => Lang::get('response.DATA_EMPTY')
                );
            }
        }else{
            $Model      = new QuestionModel;
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'total'     => $Model::count(),
                'data'      => $Model::skip($offset)->take($itemPage)->get(),
                'privilege' => (int)$UserInfo['privilege']
            );
        }
        
        return Response::json($contents);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate()
	{
		
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

    private function CacheList(){
        $Data  = QuestionModel::all(array('id','content','active'));
        if(!$Data->isEmpty()){
            Cache::forever('request_rating_cache', $Data);
        }
        return true;
    }
    
    public function GetCache(){
        if(Cache::has('request_rating_cache')){
            return Cache::get('request_rating_cache');
        }else{
            $this->CacheList();
            if(Cache::has('request_rating_cache')){
               return Cache::get('request_rating_cache');
            }
        }
        return false;
    }
    
}
