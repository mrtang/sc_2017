<?php namespace warehouse;

use fulfillmentmodel\ShipMentModel;
use omsmodel\PipeJourneyModel;

class ShipmentCtrl extends BaseCtrl {

    private $list_city      = [];
    private $list_district  = [];
    private $list_ward      = [];

    function __construct(){

    }

    private function getModel(){
        $CreatedStart   = Input::has('create_start')        ? (int)Input::get('create_start')               : 0;
        $CreatedEnd     = Input::has('create_end')         ? (int)Input::get('create_end')                : 0;
        $AcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')               : 0;
        $AcceptdEnd     = Input::has('accept_end')          ? (int)Input::get('accept_end')                 : 0;
        $PickupStart    = Input::has('pickup_start')        ? (int)Input::get('pickup_start')               : 0;
        $PickupEnd      = Input::has('pickup_end')          ? (int)Input::get('pickup_end')                 : 0;
        $DeliveredStart = Input::has('delivered_start')     ? (int)Input::get('delivered_start')            : 0;
        $DeliveredEnd   = Input::has('delivered_end')       ? (int)Input::get('delivered_end')              : 0;
        $ExpectStart    = Input::has('expect_start')        ? (int)Input::get('expect_start')               : 0;
        $ExpectEnd      = Input::has('expect_end')          ? (int)Input::get('expect_end')                 : 0;

        $FromCity       = Input::has('from_city')           ? (int)Input::get('from_city')                  : 0;
        $FromDistrict   = Input::has('from_district')       ? (int)Input::get('from_district')              : 0;

        $KeyWord        = Input::has('keyword')             ? strtolower(trim(Input::get('keyword')))       : '';
        $Shipment       = Input::has('shipment_code')       ? strtoupper(trim(Input::get('shipment_code'))) : '';

        $AreaLocaiton   = Input::has('area_location')       ? (int)Input::get('area_location')              : 0;
        $ShippingMethod = Input::has('shipping_method')     ? Input::get('shipping_method')                 : '';
        $DeliverySlow   = Input::has('delivery_slow')       ? (int)Input::get('delivery_slow')              : 0;

        $Group          = Input::has('group')               ? (int)Input::get('group')                      : 0;
        $TypeProcess    = Input::has('type_process')        ? (int)Input::get('type_process')               : 0;
        $PipeStatus     = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))               : '';
        $ListStatus     = Input::has('list_status')         ? trim(Input::get('list_status'))               : '';


        if(empty($Group) || empty($TypeProcess)){
            return false;
        }

        $Model          = new ShipMentModel;

        if(!empty($CreatedStart)){
            $CreatedStart    = $this->__convert_time($CreatedStart);
            $Model          = $Model->where('created','>=',$CreatedStart);
        }else{
            return false;
        }

        if(!empty($CreatedEnd)){
            $CreatedEnd      = $this->__convert_time($CreatedEnd);
            $Model          = $Model->where('created','<=',$CreatedEnd);
        }

        if(!empty($AcceptStart)){
            $AcceptStart    = $this->__convert_time($AcceptStart);
            $Model          = $Model->where('updated_at','>=',$AcceptStart);
        }

        if(!empty($AcceptdEnd)){
            $AcceptdEnd      = $this->__convert_time($AcceptdEnd);
            $Model          = $Model->where('updated_at','<=',$AcceptdEnd);
        }

        if(!empty($ExpectStart)){
            $ExpectStart        = $this->__convert_time($ExpectStart);
            $Model              = $Model->where('expect_date','>=',$ExpectStart);
        }

        if(!empty($ExpectEnd)){
            $ExpectEnd          = $this->__convert_time($ExpectEnd);
            $Model              = $Model->where('expect_date','<=',$ExpectEnd);
        }

        /** Thời gian hàng đến kho */
        if(!empty($DeliveredStart)){
            $DeliveredStart     = $this->__convert_time($DeliveredStart);
            $Model              = $Model->where('approved','>=',$DeliveredStart);
        }

        if(!empty($DeliveredEnd)){
            $DeliveredEnd       = $this->__convert_time($DeliveredEnd);
            $Model              = $Model->where('approved','<=',$DeliveredEnd);
        }

        if(!empty($FromDistrict) || !empty($FromCity)){
            $ListId = $this->__get_inventory($FromCity,$FromDistrict);
            if(empty($ListId)){
                return false;
            }
            $Model  = $Model->whereRaw("inventory_outbound in (". implode(",", $ListId) .")");
        }

        if(!empty($Shipment)){
            if(preg_match("/^BX/i", $Shipment)){
                $Model          = $Model->where('request_code',$Shipment);
            }elseif(preg_match("/^SC/i", $Shipment)){
                $Model          = $Model->where('tracking_number',$Shipment);
            }else{
                $Model          = $Model->where('id',$Shipment);
            }
        }

        if(!empty($KeyWord)){
            $UserModel      = new \User;
            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $UserModel          = $UserModel->where('email',$KeyWord);
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $UserModel          = $UserModel->where('phone',$KeyWord);
            }else{ // search code
                $UserModel          = $UserModel->where('fullname',$KeyWord);
            }

            $ListUser = $UserModel->lists('id');
            if(empty($ListUser)){
                return false;
            }else{
                $Model  = $Model->whereIn('user_id', $ListUser);
            }
        }

        if($ShippingMethod != ''){
            $Model          = $Model->where('shipping_method',$ShippingMethod);
        }

        if(!empty($ListStatus)){
            if($ListStatus == '9'){
                $Model          = $Model->where('deleted',1);
            }else{
                $ListStatus = explode(',', $ListStatus);
                $Model      = $Model->whereIn('status',$ListStatus);
            }
        }

        if(!empty($DeliverySlow)){
            $DeliverySlow   = $DeliverySlow*3600;
            $Model = $Model->whereNotNull('expect_date')->where(function($query) use($DeliverySlow){
                $query->where(function($q) use($DeliverySlow){
                    $q->whereNotNull('received')->whereRaw('TIMESTAMPDIFF(DAY,expect_date,received) > '.$DeliverySlow);
                })->orWhere(function($q) use($DeliverySlow){
                    $q->whereNull('received')->whereRaw('TIMESTAMPDIFF(DAY,expect_date,NOW()) > '.$DeliverySlow);
                });
            });
        }

        if(!empty($PipeStatus)){
            $PipeStatus = explode(',',$PipeStatus);
            $ListId = PipeJourneyModel::where('type', $TypeProcess)->where('group_process',$Group)->whereIn('pipe_status', $PipeStatus)->lists('tracking_code');

            if(!empty($ListId)){
                $ListId = array_unique($ListId);
                $Model  = $Model->whereRaw("id in (". implode(",", $ListId) .")");
            }else{
                $this->error = true;
                return;
            }
        }

        return $Model;
    }

    private function ResponseData(){
        return Response::json([
            'error'             => false,
            'code'              => 'SUCCESS',
            'error_message'     => 'Thành công',
            'total'             => $this->total,
            'data'              => $this->data,
            'list_city'         => $this->list_city,
            'list_district'     => $this->list_district
        ]);
    }

    public function getIndex(){
        $itemPage           = 20;
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';
        $WareHouse          = Input::has('warehouse')           ? strtoupper(trim(Input::get('warehouse')))     : '';
        $Group              = Input::has('group')               ? (int)Input::get('group')                      : 1;
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')               : 12;

        $Model          = $this->getModel();
        if(!$Model){
            return $this->ResponseData();
        }

        if(!empty($WareHouse)){
            $Model  = $Model->where('warehouse', $WareHouse);
        }

        if($Cmd == 'export'){
            $Data   = [];
            $Model->with(['__get_user','__get_outbound','pipe_journey' => function($query) use($TypeProcess, $Group){
                $query->where('type',$TypeProcess)->where('group_process',$Group);
            },'__get_shipment_product'])
                ->chunk('1000', function($query) use(&$Data){
                    foreach($query as $val){
                        $val                = $val->toArray();
                        $Data[]             = $val;
                    }
                });

            $this->data = $Data;
            return $this->ResponseData();
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model      = $Model->skip($offset)->take($itemPage);
            $this->data       = $Model->with(['__get_user','__get_outbound','pipe_journey' => function($query) use($TypeProcess, $Group){
                $query->where('type',$TypeProcess)->where('group_process',$Group);
            },'__get_shipment_product'])->orderBy('created','DESC')->get()->toArray();

            foreach($this->data as $key => $val){
                $this->data[$key]['pipe_status']    = 0;
                foreach($val['pipe_journey'] as $v){
                    $this->data[$key]['pipe_status'] = (int)$v['pipe_status'];
                }

                if(!empty($val['__get_outbound'])){
                    if($val['__get_outbound']['city_id'] > 0){
                        $this->list_city[]      = (int)$val['__get_outbound']['city_id'];
                    }

                    if($val['__get_outbound']['province_id'] > 0){
                        $this->list_district[]  = (int)$val['__get_outbound']['province_id'];
                    }

                    if($val['__get_outbound']['ward_id'] > 0){
                        $this->list_ward[]      = (int)$val['__get_outbound']['ward_id'];
                    }
                }
            }

            if(!empty($this->list_city)){
                $this->list_city = array_unique($this->list_city);
                $this->list_city = $this->getCityById($this->list_city);
            }

            if(!empty($this->list_district)){
                $this->list_district = array_unique($this->list_district);
                $this->list_district = $this->getProvince($this->list_district);
            }

            if(!empty($this->list_ward)){
                $this->list_ward = array_unique($this->list_ward);
                $this->list_ward = $this->getWard($this->list_ward);
            }
        }

        return $this->ResponseData();
    }

    public function getCountGroup(){
        $Model          = $this->getModel();

        if(!$Model){
            return $this->ResponseData();
        }

        $GroupStatus    = $Model->groupBy('warehouse')->get(array('warehouse',DB::raw('count(*) as total')))->toArray();
        if(empty($GroupStatus)){
            return $this->ResponseData();
        }

        $this->data['ALL']  = 0;
        foreach($GroupStatus as $val){
            $this->data[$val['warehouse']]  = (int)$val['total'];
            $this->data['ALL']             += (int)$val['total'];
        }

        return $this->ResponseData();
    }

    public function getListing(){
        $RequestCode        = Input::has('request_code')        ? strtoupper(trim(Input::get('request_code')))  : '';

        if(empty($RequestCode)){
            return $this->ResponseData();
        }

        $this->data = ShipMentModel::where('request_code', $RequestCode)->with(['__get_shipment_product' => function($query){
            $query->with('__get_seller_product');
        },'__get_outbound','__get_user','__get_dr_product' => function($query){
            $query->with(['__get_seller_product' => function($q){
                $q->with('__product');
            }]);
        }])->first();

        return $this->ResponseData(false);
    }

    public function getSearch(){
        $KeyWord        = Input::has('keyword')             ? trim(Input::get('keyword'))                   : '';
        $UserModel      = new \User;
        $UserId         = 0;

        if(empty($KeyWord)){
            return $this->ResponseData();
        }

        if(filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){
            $UserModel  = $UserModel::where('email', $KeyWord)->with(['merchant'])->first(['id','fullname','email','phone','time_create']);
            if(isset($UserModel->id)){
                $this->data = [
                    'user_id'               => (int)$UserModel->id,
                    'fullname'              => $UserModel->fullname,
                    'email'                 => $UserModel->email,
                    'phone'                 => $UserModel->phone,
                    'time_create'           => $UserModel->time_create,
                    'mechant'               => $UserModel->merchant,
                    'list_order'            => []
                ];
            }
        }elseif(preg_match("/^SC/i", $KeyWord)){
            $Data                     =\ordermodel\OrdersModel::where('tracking_code',$KeyWord)
                ->where(function($query){
                    $query->where('time_accept','>=', time() - $this->time_limit)
                        ->orWhere('time_accept',0);
                })->first(['from_user_id']);
            if(!isset($Data->from_user_id)){
                return $this->ResponseData();
            }

            $UserModel  = $UserModel::where('id', $Data->from_user_id)->with(['merchant'])->first(['id','fullname','email','phone','time_create']);
            if(isset($UserModel->id)){
                $this->data = [
                    'user_id'               => (int)$UserModel->id,
                    'fullname'              => $UserModel->fullname,
                    'email'                 => $UserModel->email,
                    'phone'                 => $UserModel->phone,
                    'time_create'           => $UserModel->time_create,
                    'mechant'               => $UserModel->merchant,
                    'tracking_code'         => $KeyWord,
                    'list_order'            => []
                ];
            }
        }elseif(preg_match("/^BX/i", $KeyWord)){
            $Shipment   = \fulfillmentmodel\ShipMentModel::where('request_code', $KeyWord)
                ->with(['__get_user' => function($query){
                    $query->with('merchant');
                },'__get_outbound'])
                ->first();
            if(!isset($Shipment->id)){
                return $this->ResponseData();
            }

            $this->data = [
                'user_id'               => (int)$Shipment->user_id,
                'fullname'              => isset($Shipment->__get_user->fullname)    ? $Shipment->__get_user->fullname : '',
                'email'                 => isset($Shipment->__get_user->email)       ? $Shipment->__get_user->email : '',
                'phone'                 => isset($Shipment->__get_user->phone)       ? $Shipment->__get_user->phone : '',
                'time_create'           => isset($Shipment->__get_user->time_create) ? $Shipment->__get_user->time_create : '',
                'mechant'               => isset($Shipment->__get_user->merchant)    ? $Shipment->__get_user->merchant : [],
                'list_shipment'         => [$Shipment]
            ];
        }elseif(preg_match("/^DR/i", $KeyWord)){
            $ListDR = \warehousemodel\DRModel::where('dr_code', $KeyWord)->lists('id');
            if(empty($ListDR)){
                return $this->ResponseData();
            }

            $ListDRItem = \warehousemodel\DRItemModel::whereIn('delivery_receipt', $ListDR)->lists('id');
            if(empty($ListDRItem)){
                return $this->ResponseData();
            }

            $this->data['list_dr']   = \warehousemodel\DRProductModel::where('delivery_receipt_item_id', $ListDRItem)->with([
                '__get_seller_product' => function($query){
                    $query->with(['__product','__get_user']);
                },'__get_dr_item' => function($query){
                    $query->with('__get_dr');
                }
            ])->get()->toArray();
            if(empty($this->data['list_dr'])){
                return $this->ResponseData();
            }


            foreach($this->data['list_dr'] as $val){
                $ShipmentId = $val['shipment'];
            }
        }elseif(preg_match("/^PAK/i", $KeyWord)){
            $this->data['list_package']  = \warehousemodel\PackageItemModel::where('package_code', $KeyWord)->with([
                '__get_seller_product' => function($query){
                    $query->with(['__product','__get_user' => function($q){
                        $q->with('merchant');
                    }]);
                },'__get_package'
            ])->orderBy('create','DESC')->get()->toArray();

            if(empty($this->data['list_package'])){
                return $this->ResponseData();
            }

            foreach($this->data['list_package'] as $val){
                $this->data['user_id']          = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['id'] : 0;
                $this->data['fullname']         = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['fullname'] : 0;
                $this->data['email']            = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['email'] : 0;
                $this->data['phone']            = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['phone'] : 0;
                $this->data['time_create']      = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['time_create'] : 0;
                $this->data['mechant']          = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['merchant'] : [];
            }
        }elseif(preg_match("/^PA/i", $KeyWord)){
            $this->data['list_putaway']   = \warehousemodel\PutawayItemModel::where('put_away_code', $KeyWord)->with([
                '__get_seller_product' => function($query){
                    $query->with(['__product','__get_user' => function($q){
                        $q->with('merchant');
                    }]);
                }
            ])->orderBy('create_time','DESC')->get()->toArray();

            if(empty($this->data['list_putaway'])){
                return $this->ResponseData();
            }

            foreach($this->data['list_putaway'] as $val){
                $this->data['user_id']          = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['id'] : 0;
                $this->data['fullname']         = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['fullname'] : 0;
                $this->data['email']            = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['email'] : 0;
                $this->data['phone']            = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['phone'] : 0;
                $this->data['time_create']      = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['time_create'] : 0;
                $this->data['mechant']          = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['merchant'] : [];
            }
        }elseif(preg_match("/^PK/i", $KeyWord)){
            $this->data['list_pickup']   = \warehousemodel\PickupItemModel::where('pickup_code', $KeyWord)->with([
                '__get_seller_product' => function($query){
                    $query->with(['__product','__get_user' => function($q){
                        $q->with('merchant');
                    }]);
                }
            ])->orderBy('create_time','DESC')->get()->toArray();

            if(empty($this->data['list_pickup'])){
                return $this->ResponseData();
            }

            foreach($this->data['list_pickup'] as $val){
                $this->data['user_id']          = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['id'] : 0;
                $this->data['fullname']         = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['fullname'] : 0;
                $this->data['email']            = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['email'] : 0;
                $this->data['phone']            = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['phone'] : 0;
                $this->data['time_create']      = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['time_create'] : 0;
                $this->data['mechant']          = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['merchant'] : [];
            }
        }elseif(preg_match("/^RC/i", $KeyWord)){
            $this->data['list_return']   = \warehousemodel\ReturnItemModel::where('return_code', $KeyWord)->with([
                '__get_seller_product' => function($query) {
                    $query->with(['__product','__get_user' => function($q){
                        $q->with('merchant');
                    }]);
                }
            ])->orderBy('created','DESC')->get()->toArray();

            if(empty($this->data['list_return'])){
                return $this->ResponseData();
            }

            foreach($this->data['list_return'] as $val){
                $this->data['user_id']          = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['id'] : 0;
                $this->data['fullname']         = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['fullname'] : 0;
                $this->data['email']            = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['email'] : 0;
                $this->data['phone']            = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['phone'] : 0;
                $this->data['time_create']      = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['time_create'] : 0;
                $this->data['mechant']          = isset($val['__get_seller_product']['__get_user']) ? $val['__get_seller_product']['__get_user']['merchant'] : [];
            }
        }else{
            if(preg_match("/^U/i", $KeyWord)){
                $this->data['uid']   = \fulfillmentmodel\SellerProductItemModel::where('serial_number', $KeyWord)
                    ->with(['__product' => function($query){
                        $query->with('__image');
                    },'__get_user' => function($q){
                        $q->with('merchant');
                    },'__inventory','__putaway','__history' => function($query){
                        $query->orderBy('created','DESC')->with('__employee');
                    }])->orderBy('update_stocked','DESC')->first();

                if(!isset($this->data['uid']->id)){
                    return $this->ResponseData();
                }

                $Image  = [];
                if(isset($this->data['uid']->__product) && isset($this->data['uid']->__product->__image)){
                    foreach($this->data['uid']->__product->__image as $val){
                        if($val['user'] == $this->data['uid']->user){
                            $Image = $val;
                        }
                    }
                    unset($this->data['uid']['__product']['__image']);
                    $this->data['uid']['__product']['__image']  = $Image;
                }

                $this->data['uid']->barcode_value   = $this->getBarcode($this->data['uid']->serial_number);

                $this->data['user_id']          = isset($this->data['uid']->__get_user->user_id)     ? $this->data['uid']->__get_user->user_id      : 0;
                $this->data['fullname']         = isset($this->data['uid']->__get_user->fullname)    ? $this->data['uid']->__get_user->fullname     : '';
                $this->data['email']            = isset($this->data['uid']->__get_user->email)       ? $this->data['uid']->__get_user->email        : '';
                $this->data['phone']            = isset($this->data['uid']->__get_user->phone)       ? $this->data['uid']->__get_user->phone        : '';
                $this->data['time_create']      = isset($this->data['uid']->__get_user->time_create) ? $this->data['uid']->__get_user->time_create  : '';
                $this->data['mechant']          = isset($this->data['uid']->__get_user->merchant)    ? $this->data['uid']->__get_user->merchant     : [];
            }else{
                $this->data['list_item']   = \fulfillmentmodel\SellerProductItemModel::where('sku', $KeyWord)
                    ->with(['__product','__get_user' => function($q){
                        $q->with('merchant');
                    },'__inventory','__putaway'])->orderBy('update_stocked','DESC')->get()->toArray();

                if(empty($this->data['list_item'])){
                    return $this->ResponseData();
                }

                foreach($this->data['list_item'] as $val){
                    $this->data['user_id']          = isset($val['__get_user']) ? $val['__get_user']['id'] : 0;
                    $this->data['fullname']         = isset($val['__get_user']) ? $val['__get_user']['fullname'] : 0;
                    $this->data['email']            = isset($val['__get_user']) ? $val['__get_user']['email'] : 0;
                    $this->data['phone']            = isset($val['__get_user']) ? $val['__get_user']['phone'] : 0;
                    $this->data['time_create']      = isset($val['__get_user']) ? $val['__get_user']['time_create'] : 0;
                    $this->data['mechant']          = isset($val['__get_user']) ? $val['__get_user']['merchant'] : [];
                }
            }
        }

        if(isset($ShipmentId) && !empty($ShipmentId)){
            $Shipment   = \fulfillmentmodel\ShipMentModel::where('id', $ShipmentId)
                ->with(['__get_user' => function($query){
                    $query->with('merchant');
                },'__get_outbound'])
                ->first();
            if(!isset($Shipment->id)){
                return $this->ResponseData();
            }


            $this->data['user_id']          = (int)$Shipment->user_id;
            $this->data['fullname']         = isset($Shipment->__get_user->fullname)    ? $Shipment->__get_user->fullname : '';
            $this->data['email']            = isset($Shipment->__get_user->email)       ? $Shipment->__get_user->email : '';
            $this->data['phone']            = isset($Shipment->__get_user->phone)       ? $Shipment->__get_user->phone : '';
            $this->data['time_create']      = isset($Shipment->__get_user->time_create) ? $Shipment->__get_user->time_create : '';
            $this->data['mechant']          = isset($Shipment->__get_user->merchant)    ? $Shipment->__get_user->merchant : [];
        }

        if(!isset($this->data['user_id']) || empty($this->data['user_id'])){
            return $this->ResponseData();
        }

        if(isset($this->data['user_id'])){
            $Date   = date('Y-m-d');
            // Get hình thức lưu kho lựa chọn
            Input::merge(['user_id' => $this->data['user_id']]);
            $this->data['wms_type'] = $this->getWMSType(false);

            // Get phí tạm tính
            $WareHouseCtrl = new \accounting\WareHouseCtrl;

            $this->data['freeze']   = [
                'item'  => $WareHouseCtrl->getWarehouseFee($this->data['user_id']),
                'm2'    => $WareHouseCtrl->getWarehouseFeePallet($this->data['user_id'], 1, $Date),
                'm3'    => $WareHouseCtrl->getWarehouseFeePallet($this->data['user_id'], 2, $Date)
            ];

            // Get thời gian nhập kho lần đầu
            $FirstShipmentTime  = \omsmodel\SellerModel::where('user_id', $this->data['user_id'])->first(['user_id','first_shipment_time']);
            if(isset($FirstShipmentTime->user_id)){
                $this->data['first_shipment_time']  = $FirstShipmentTime->first_shipment_time;
            }


            $this->data['verify'] = \ordermodel\VerifyModel::where('user_id', $this->data['user_id'])
                ->orderBy('time_create','DESC')->take(30)->get()->toArray();
            $this->data['verify_warehouse'] = \fulfillmentmodel\WareHouseVerifyModel::where('user_id', $this->data['user_id'])
                ->orderBy('time_create','DESC')->take(30)->get()->toArray();

            $ListItem = \fulfillmentmodel\SellerProductModel::where('user_id',$this->data['user_id'])->lists('sku');
            if(!empty($ListItem)){
                $ListStatistic = \warehousemodel\StatisticReportProductModel::whereIn('sku',$ListItem)->first(array(DB::raw('sum(inventory) as inventory,sum(inventory_damaged) as inventory_damaged,sum(inventory_wait) as inventory_wait')));
                if(isset($ListStatistic->inventory)){
                    $this->data['statistic'] = $ListStatistic;
                }
            }
        }

        return $this->ResponseData();
    }

    public function postEdit($id){
        $UserInfo   = $this->UserInfo();

        $PackingVolume   = Input::has('packing_volume')     ? strtolower(trim(Input::get('packing_volume')))    : '';
        $PackageSize     = Input::has('package_size')       ? strtolower(trim(Input::get('package_size')))      : '';

        if(!empty($PackingVolume)){
            $Model = new \fulfillmentmodel\SellerProductModel;
        }

        if(!empty($PackageSize)){
            $Model = new \warehousemodel\PackageModel;
        }

        $Product  = $Model::where('id', $id)->first();
        if(!isset($Product->id)){
            $this->error        = 'NOT EXISTS';
            $this->message      = 'Không tồn tại';
            return $this->ResponseEdit(true);
        }

        $this->log['user_id']   = $UserInfo['id'];
        $this->log['id']        = $id;

        if(!empty($PackingVolume)){
            $this->UpdateLog('packing_volume', $PackingVolume, $Product->packing_volume);
            $Product->packing_volume = $PackingVolume;
        }

        if(!empty($PackageSize)){
            $this->UpdateLog('package_size', $PackageSize, $Product->size);
            $PackageHistory             = \warehousemodel\PackageHistoryModel::orderBy('id','DESC')->first();
            $PackageHistory->box        = $PackageSize;
            $PackageHistory->save();

            $Product->size = $PackageSize;
        }

        try{
            $Product->save();
            $this->InsertLog();
        }catch (\Exception $e){
            $this->error        = 'ERROR';
            $this->message      = $e->getMessage();
            return $this->ResponseEdit(true);
        }

        return $this->ResponseEdit(false);
    }
}
