<?php
class CourierEstimateController extends \BaseController {
    private $domain = '*';
     
    public function __construct(){
        
    }
    //
    public function getIndex(){
        $itemPage   = Input::has('limit')       ? Input::get('limit')                    : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')                : 1;
        $courier    = Input::has('courier')     ? (int)Input::get('courier')             : 0;
        $fromDistrict    = Input::has('from_district')     ? (int)Input::get('from_district')             : 0;
        $toDistrict    = Input::has('to_district')     ? (int)Input::get('to_district')             : 0;
        
        $Model = new CourierEstimateModel;
        if($courier > 0){
            $Model = $Model->where('courier',$courier);
        }
        if($fromDistrict > 0){
            $Model = $Model->where('from_district_id',$fromDistrict);
        }
        if($toDistrict > 0){
            $Model = $Model->where('to_district_id',$toDistrict);
        }

        $offset = ($page - 1) * $itemPage;
        $total = $Model->count();
        $data = $Model->skip($offset)->take($itemPage)->get()->toArray();

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $data,
            'total'         => $total,
            'item_page'     => $itemPage
        );
        return Response::json($contents);
    }
    //
    public function postEdit($Id)
	{
		$Data       = Input::json()->all();
        $Active     = isset($Data['active'])        ? (int)$Data['active']      : null;
        
        if($Id < 1){
            $contents = array(
                'error'     => true, 
                'message'   => 'id empty'
            );
            return Response::json($contents);
        }
            
        $Model = new CourierEstimateModel;
        $Model = $Model::find($Id);
        if($Model){
            if(isset($Active))      $Model->active          = $Active;
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
        
        return Response::json($contents);
	}
}
?>