<?php namespace accounting;


class PartnerVerifyCtrl extends BaseCtrl {
    public $message        = 'Thành Công';
    public $code           = 'SUCCESS';

    public  function __construct(){

    }

    public function getIndex(){
        $page               = Input::has('page')            ? (int)Input::get('page')                       : 1;
        $itemPage           = Input::has('limit')           ? Input::get('limit')                           : 20;
        $cmd                = Input::has('cmd')             ? strtolower(trim(Input::get('cmd')))           : '';

        $CreateStart        = Input::has('create_start')    ? trim(Input::get('create_start'))              : '';
        $CreateEnd          = Input::has('create_end')      ? trim(Input::get('create_end'))                : '';
        $WareHouse          = Input::has('warehouse')       ? trim(Input::get('warehouse'))                 : '';
        $CourierId          = Input::has('courier_id')      ? (int)Input::get('courier_id')                 : 0;

        $Model              = new \partnermodel\PartnerVerifyModel;
        $Total              = 0;
        $Data               = [];

        if(!empty($CreateStart)){
            $CreateStart    = date('Y-m-d', $CreateStart);
            $Model          = $Model->where('date','>=',$CreateStart);
        }

        if(!empty($CreateEnd)){
            $CreateEnd      = date('Y-m-d', $CreateEnd);
            $Model          = $Model->where('date','<=',$CreateEnd);
        }

        if(!empty($WareHouse)){
            $Model          = $Model->where('warehouse',$WareHouse);
        }

        if(!empty($CourierId)){
            $Model          = $Model->where('courier',$CourierId);
        }

        if($cmd == 'export'){
            $Data           = $Model->with(['__get_refer' => function($query){
                $query->with(['__get_warehouse_fee' => function($q){
                    $q->with(['__warehouse_detail', '__warehouse_sku_detail']);
                }]);
            }])->get()->toArray();

            return Response::json([
                'error'         => false,
                'message'       => 'success',
                'total'         => $Total,
                'data'          => $Data
            ]);
        }

        $ModelTotal = clone $Model;
        $Total      = $ModelTotal->count();
        $Data = [];

        if($Total > 0){
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
            $Data       = $Model->skip($offset)->take($itemPage)->get()->toArray();
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'total'         => $Total,
            'data'          => $Data
        ]);
    }

}
