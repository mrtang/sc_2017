<?php namespace seller;
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
        $Model      = $Model::where('time_create','>=', $this->time() - $this->time_limit);

        if(!empty($TrackingCode)){
            $TrackingCode   = explode(',',$TrackingCode);
            $Model          = $Model->whereIn('tracking_code', $TrackingCode);
        }

        return $Model;
    }

    public function getShow()
    {
        $Model      = $this-> getModel();
        $Data       = $Model->with(['SellerDetail'])
                            ->get(['id','tracking_code', 'order_code', 'to_name', 'to_phone', 'to_email', 'total_weight', 'total_amount', 'to_address_id',
                                 'checking', 'fragile', 'total_quantity', 'product_name', 'status'])->toArray();

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        );

        return Response::json($contents);
    }


    // Exchange 
    public function getShowOrder()
    {
        $Model      = $this->getModel();
        $Data       = $Model->whereIn('status',[52,53])->with(['MetaStatus', 'SellerDetail', 'ToOrderAddress'=> function ($query){
                                $query->with('City', 'District', 'Ward');
                            }])
                            ->get(['id','tracking_code', 'order_code', 'to_name', 'to_phone', 'to_email', 'total_weight', 'total_amount', 'to_address_id',
                                 'checking', 'fragile', 'total_quantity', 'product_name', 'status'])->toArray();

        $contents = array(
            'error'   => false,
            'message' => 'success',
            'data'    => $Data
        );

        return Response::json($contents);
    }
}
