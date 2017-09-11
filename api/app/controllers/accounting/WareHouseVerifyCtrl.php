<?php namespace accounting;


class WareHouseVerifyCtrl extends BaseCtrl {
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
        $KeyWord            = Input::has('keyword')         ? trim(Input::get('keyword'))                   : '';
        $PaymentType        = Input::has('payment_type')    ? (int)Input::get('payment_type')               : 0;
        $Type               = Input::has('type')            ? (int)Input::get('type')                       : 0;

        $Model              = new \fulfillmentmodel\WareHouseVerifyModel;
        $Total              = 0;
        $Data               = [];

        if(!empty($KeyWord)){
            $ModelUser  = new \User;
            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $ModelUser          = $ModelUser->where('email','LIKE','%'.$KeyWord.'%');
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $ModelUser          = $ModelUser->where('phone','LIKE','%'.$KeyWord.'%');
            }else{ // search code
                $ModelUser          = $ModelUser->where('fullname','LIKE','%'.$KeyWord.'%');
            }
            $ListUser = $ModelUser->lists('id');

            if(empty($ListUser)){
                return Response::json([
                    'error'         => false,
                    'message'       => 'success',
                    'total'         => $Total,
                    'data'          => $Data
                ]);
            }

            $Model  = $Model->whereIn('user_id',$ListUser);
        }

        if(!empty($CreateStart)){
            $CreateStart    = date('Y-m-d', $CreateStart);
            $Model          = $Model->where('date','>=',$CreateStart);
        }

        if(!empty($CreateEnd)){
            $CreateEnd      = date('Y-m-d', $CreateEnd);
            $Model          = $Model->where('date','<=',$CreateEnd);
        }

        if(!empty($PaymentType)){
            $Model          = $Model->where('config_warehouse',$PaymentType);
        }

        if(!empty($Type)){
            $Model          = $Model->where('type',$Type);
        }

        if($cmd == 'export'){
            $Data           = $Model->with('__get_user')->get()->toArray();
            $WareHouseCtrl  = new WareHouseCtrl;

            $ListWareHouse  = [];
            if(!empty($Data)){
                foreach($Data as $val){
                    $Date           = $WareHouseCtrl->__get_date_calculator($val['date']);
                    $WareHouseModel = new \fulfillmentmodel\WareHouseFeeModel;
                    $ListWareHouse[$val['id']]  = $WareHouseModel->where('date','>=', $Date['first'])
                                                                 ->where('date','<=', $Date['end'])
                                                                 ->where('user_id', $val['user_id'])
                                                                 ->with(['__warehouse_detail', '__warehouse_sku_detail'])
                                                                 ->get()->toArray();
                }
            }


            return Response::json([
                'error'         => false,
                'message'       => 'success',
                'total'         => $Total,
                'data'          => $Data,
                'data_detail'   => $ListWareHouse
            ]);
        }

        $ModelTotal = clone $Model;
        $Total      = $ModelTotal->count();
        $Data = [];

        if($Total > 0){
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
            $Data       = $Model->skip($offset)->take($itemPage)->with('__get_user')->get()->toArray();
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'total'         => $Total,
            'data'          => $Data
        ]);
    }

    /**
     * get verify detail
     */
    public function getWarehouseFee(){
        $page               = Input::has('page')            ? (int)Input::get('page')                       : 1;
        $itemPage           = Input::has('limit')           ? Input::get('limit')                           : 20;
        $cmd                = Input::has('cmd')             ? strtolower(trim(Input::get('cmd')))           : '';

        $CreateStart        = Input::has('create_start')    ? trim(Input::get('create_start'))              : '';
        $CreateEnd          = Input::has('create_end')      ? trim(Input::get('create_end'))                : '';
        $KeyWord            = Input::has('keyword')         ? trim(Input::get('keyword'))                   : '';
        $WareHouse          = Input::has('warehouse')       ? trim(Input::get('warehouse'))                 : '';
        $PaymentType        = Input::has('payment_type')    ? (int)Input::get('payment_type')               : 0;
        $VerifyId           = Input::has('verify_id')       ? (int)Input::get('verify_id')                  : 0;

        $Model              = new \fulfillmentmodel\WareHouseFeeModel;
        $Total              = 0;
        $Data               = [];

        if(!empty($KeyWord)){
            $ModelUser  = new \User;
            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $ModelUser          = $ModelUser->where('email','LIKE','%'.$KeyWord.'%');
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $ModelUser          = $ModelUser->where('phone','LIKE','%'.$KeyWord.'%');
            }else{ // search code
                $ModelUser          = $ModelUser->where('fullname','LIKE','%'.$KeyWord.'%');
            }
            $ListUser = $ModelUser->lists('id');

            if(empty($ListUser)){
                return Response::json([
                    'error'         => false,
                    'message'       => 'success',
                    'total'         => $Total,
                    'data'          => $Data
                ]);
            }

            $Model  = $Model->whereIn('user_id',$ListUser);
        }

        if(!empty($VerifyId)){
            $Verify = \fulfillmentmodel\WareHouseVerifyModel::where('id', $VerifyId)->first();
            if(!isset($Verify->id)){
                return Response::json([
                    'error'         => false,
                    'message'       => 'success',
                    'total'         => $Total,
                    'data'          => $Data
                ]);
            }

            $WareHouseCtrl  = new WareHouseCtrl;
            $DateCalc       = $WareHouseCtrl->__get_date_calculator($Verify->date);
            $Model          = $Model->where('date','>=', $DateCalc['first'])
                                    ->where('date','<=', $DateCalc['end'])
                                    ->where('user_id', $Verify->user_id);
        }else{
            if(!empty($CreateStart)){
                $CreateStart    = date('Y-m-d', $CreateStart);
                $Model          = $Model->where('date','>=',$CreateStart);
            }

            if(!empty($CreateEnd)){
                $CreateEnd      = date('Y-m-d', $CreateEnd);
                $Model          = $Model->where('date','<=',$CreateEnd);
            }
        }

        if(!empty($PaymentType)){
            $Model          = $Model->where('payment_type',$PaymentType);
        }

        if(!empty($WareHouse)){
            $Model          = $Model->where('warehouse',$WareHouse);
        }

        if($cmd == 'export'){
            $Data   = $Model->with('__get_user')->get()->toArray();
            $ListWareHouseDetail    = $ListWareHouseSku = [];
            if(!empty($Data)){
                $ListId = [];
                foreach($Data as $val){
                    $ListId[]   = (int)$val['id'];
                }

                $ListWareHouseDetail    = \fulfillmentmodel\WareHouseFeeDetailModel::whereIn('log_id', $ListId)->get()->toArray();
                $ListWareHouseSku       = \fulfillmentmodel\WareHouseFeeSkuModel::whereIn('log_id', $ListId)->get()->toArray();
            }


            return Response::json([
                'error'         => false,
                'message'       => 'success',
                'total'         => $Total,
                'data'          => $Data,
                'data_detail'   => $ListWareHouseDetail,
                'data_sku'      => $ListWareHouseSku
            ]);
        }

        $ModelTotal = clone $Model;
        $Total      = $ModelTotal->count();
        $Data = [];

        if($Total > 0){
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
            $Data       = $Model->skip($offset)->take($itemPage)->with('__get_user')->get()->toArray();
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'total'         => $Total,
            'data'          => $Data
        ]);
    }

}
