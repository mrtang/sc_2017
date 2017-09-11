<?php namespace order;
use Response;
use Input;
use DB;
use ordermodel\OrdersModel;
use sellermodel\UserInventoryModel;
use Cache;

class OrderDetailCtrl extends \BaseController {
    public  function __construct(){

    }

    private function getModel(){
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';

        $Model      = new OrdersModel;
        $Model      = $Model::where(function($query) {
            $query->where('time_accept','>=', $this->time_limit)
                ->orWhere('time_accept',0);
        });

        if(!empty($TrackingCode)){
            $TrackingCode   = explode(',',$TrackingCode);
            $Model          = $Model->whereIn('tracking_code', $TrackingCode);
        }

        return $Model;
    }

    public function getShow()
    {
        $Model      = $this-> getModel();
        $Data       = $Model->with('SellerDetail')
                            ->get(['id','tracking_code', 'order_code', 'to_name', 'to_phone', 'to_email', 'total_weight', 'total_amount', 'total_quantity',
                                    'product_name', 'status'])->toArray();

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        );

        return Response::json($contents);
    }
}
