<?php namespace order;
use metadatamodel\GroupStatusModel;
use ordermodel\StatusModel;
use ordermodel\OrdersModel;
use Response;
use Input;
use Cache;
use Lang;

class StatusOrderCtrl extends \BaseController {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getStatusgroup($json = true)
	{
        $group              = Input::has('group')   ? (int)Input::get('group')      : 3;
        
        if (Cache::has('group_status_cache_'.$group.'_'.$this->lang)){
            $ListGroup    = Cache::get('group_status_cache_'.$group.'_'.$this->lang);
        }else{
            $Model      = new GroupStatusModel;
            $ListGroup  = $Model::where('group', $group)->with('group_order_status')->get()->toArray();

            if(!empty($ListGroup)){
                Cache::put('group_status_cache_'.$group.'_'.$this->lang,$ListGroup,60);
            }
        }
        return $json ? Response::json([ 'error'         => false, 'message'       => Lang::get('response.SUCCESS'), 'list_group'    => $ListGroup]) : $ListGroup;

	}

    public function getStatusorder()
    {
        return Response::json(
            [
                'error'             => false,
                'error_message'     => Lang::get('response.SUCCESS'),
                'data'              => $this->getStatus()
            ]
        );

    }

    public function getOrderStatus(){
        $tracking_code  = Input::has('TrackingCode')    ? (string)Input::get('TrackingCode')       : "";
        $limit          = Input::has('limit')           ? (int)Input::get('limit')                 : 10;
        $OrderModel     = new OrdersModel;

        $Order = $OrderModel->where('tracking_code', $tracking_code)->first();
        if(!$Order){
            return Response::json([
                'error' => true,
                "error_message" => Lang::get('response.NOT_EXISTS_ORDER'),
                "data" => []
            ]);
        }

        $StatusOrder    = new StatusModel;
        $listStatus     = $StatusOrder::where('order_id', $Order->id)->take($limit)->orderBy('time_create', 'DESC')->get();
        return Response::json([
            'error' => false,
            "error_message" => Lang::get('response.SUCCESS'),
            "data" => $listStatus
        ]);

    }

    public static function getStatusOrderGroup()
    {
        $Model      = new GroupStatusModel;
        $ret     =  $Model->where('group', 3)->select(['id', 'name'])->with(['order_group_status'=> function ($query){
            /*$query->select('')*/
            $query->select(['group_status','order_status_code' ]);
        }])->get();

        if($ret){
            return Response::json([
                'error'     => false,
                'error_message'   => "",
                "data"      => $ret
            ]);
        }
        return Response::json([
                'error'     => true,
                'error_message'   => Lang::get('response.server_error'),
                "data"      => []
            ]);

    }

    
    public static function getStatusgroupshow()
	{
        $Model      = new GroupStatusModel;
        $ListGroup  = $Model::where('group', 3)->get(array('id','name'))->toArray();
        
        foreach($ListGroup as $value){
            $return[] = ['StatusId' => $value['id'], 'StatusName' => $value['name']];
        }
        
        return Response::json([ 'error'         => false,
                                'message'       => Lang::get('response.SUCCESS'),
                                'data'          => $return
                                ]
                        );

	}
    //
    public static function getStatussystem()
    {
        $Model      = new GroupStatusModel;
        $ListGroup  = $Model::where('group', 4)->get(array('id','name'))->toArray();
        
        return Response::json([ 'error'         => false,
                                'message'       => Lang::get('response.SUCCESS'),
                                'data'          => $ListGroup
                                ]
                        );
    }
    //
    public static function getStatusc()
    {
        $Model      = new GroupStatusModel;
        $ListGroup  = $Model::where('group', 4)->get(array('id','name'))->toArray();
        $return = array();
        foreach($ListGroup as $value){
            $return[(int)$value['id']] = $value['name'];
        }
        
        return Response::json([ 'error'         => false,
                                'message'       => Lang::get('response.SUCCESS'),
                                'data'          => $return
                                ]
                        );

    }
}
