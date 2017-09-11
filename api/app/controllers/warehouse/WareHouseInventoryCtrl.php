<?php namespace warehouse;

use bm_ecommercemodel\SellerProductItemModel;
use omsmodel\PipeJourneyModel;
use bm_sellermodel\InventorySellerModel;
use bm_warehousemodel\ReturnItemModel;

class WareHouseInventoryCtrl extends BaseCtrl {
    function __construct(){

    }

    private function getModel(){
        $CreateStart    = Input::has('create_start')       ? (int)Input::get('create_start')                : 0;
        $CreatedEnd     = Input::has('create_end')         ? (int)Input::get('create_end')                  : 0;
        $UpdateStart    = Input::has('update_start')       ? (int)Input::get('update_start')                : 0;
        $UpdatedEnd     = Input::has('update_end')         ? (int)Input::get('update_end')                  : 0;

        $TrackingCode   = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $KeyWord        = Input::has('keyword')             ? trim(Input::get('keyword'))                   : '';
        $ListStatus     = Input::has('list_status')         ? trim(Input::get('list_status'))               : '';

        $Group          = Input::has('group')               ? (int)Input::get('group')                      : 0;
        $TypeProcess    = Input::has('type_process')        ? (int)Input::get('type_process')               : 0;
        $PipeStatus     = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))               : '';
        $TimeStock      = Input::has('time_stock')          ? (int)Input::get('time_stock')               : 0;

        $Model          = new SellerProductItemModel;

        if(!empty($CreateStart)){
            $CreateStart    = $this->__convert_time($CreateStart);
            $Model          = $Model->where('update_stocked','>=',$CreateStart);
        }else{
            return false;
        }

        if(empty($Group) || empty($TypeProcess)){
            return false;
        }

        if(!empty($CreatedEnd)){
            $CreatedEnd         = $this->__convert_time($CreatedEnd);
            $Model              = $Model->where('update_stocked','<=',$CreatedEnd);
        }

        if(!empty($UpdateStart)){
            $UpdateStart    = $this->__convert_time($UpdateStart);
            $Model          = $Model->where('updated_at','>=',$UpdateStart);
        }

        if(!empty($UpdatedEnd)){
            $UpdatedEnd    = $this->__convert_time($UpdatedEnd);
            $Model          = $Model->where('updated_at','<=',$UpdatedEnd);
        }

        if(!empty($TrackingCode)){
            if(preg_match("/^U/i", $TrackingCode)) {
                $Model = $Model->where('serial_number', $TrackingCode);
            }else{
                $Model = $Model->where('sku', $TrackingCode);
            }
        }

        if(!empty($KeyWord)){
            $UserModel      = new \User;
            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $UserModel          = $UserModel->where('email',$KeyWord);
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $UserModel          = $UserModel->where('phone',$KeyWord);
            }else{
                $UserModel          = $UserModel->where('fullname',$KeyWord);
            }

            $ListUser = $UserModel->lists('id');
            if(empty($ListUser)){
                return false;
            }

            $Model  = $Model->whereIn('user', $ListUser);
        }

        if(!empty($ListStatus)){
            $ListStatus = explode(',',$ListStatus);
            $Model  = $Model->whereIn('status',$ListStatus);
        }

        if(!empty($TimeStock)){
            $Model = $Model->whereNotNull('update_stocked')->where(function($query) use($TimeStock){
                $query->where(function($q) use($TimeStock){
                    $q->whereNotNull('update_packed')->whereRaw('(update_packed > update_stocked) AND  TIMESTAMPDIFF(DAY,update_stocked,update_packed) > '.$TimeStock);
                })->orWhere(function($q) use($TimeStock){
                    $q->whereNull('update_packed')->whereRaw('TIMESTAMPDIFF(DAY,update_stocked,NOW()) > '.$TimeStock)->where(function($p){
                        $p->whereRaw('update_packed = null OR update_packed < update_stocked');
                    });
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

    private function ResponseData($check = true){
        return Response::json([
            'error'             => false,
            'code'              => 'SUCCESS',
            'error_message'     => 'Thành công',
            'total'             => $this->total,
            'data'              => $this->data
        ]);
    }

    public function getIndex(){
        $itemPage           = 20;
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';
        $WareHouse          = Input::has('warehouse')           ? strtoupper(trim(Input::get('warehouse')))     : '';
        $CreateStart        = Input::has('create_start')        ? (int)Input::get('create_start')               : 0;
        $Group              = Input::has('group')               ? (int)Input::get('group')                      : 0;
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')               : 0;

        $Model          = $this->getModel();
        if(!$Model){
            return $this->ResponseData();
        }

        if(!empty($CreateStart)) {
            $CreateStart = $this->__convert_time($CreateStart);
        }

        if(!empty($WareHouse)){
            $ListInventory  = $this->__get_seller_inventory($WareHouse);
            if(empty($ListInventory)){
                return $this->ResponseData();
            }

            $Model = $Model->whereIn('inventory', $ListInventory);
        }

        if($Cmd == 'export'){
            return $this->ExportExcel($Model);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model      = $Model->skip($offset)->take($itemPage);
            $this->data = $Model->with(['__product','__user','__inventory',
                'pipe_journey' => function($query) use($Group, $TypeProcess){
                    $query->where('type', $TypeProcess)->where('group_process', $Group);
                },
                '__putaway' => function($query) use($CreateStart){
                $query->where('create_time','>=', $CreateStart);
            }])->orderBy('update_stocked','DESC')->get()->toArray();

            foreach($this->data as $key => $val){
                $this->data[$key]['pipe_status']    = 0;
                foreach($val['pipe_journey'] as $v){
                    $this->data[$key]['pipe_status'] = (int)$v['pipe_status'];
                }
            }
        }

        return $this->ResponseData();
    }

    public function getDamaged(){
        $itemPage           = 20;
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';
        $WareHouse          = Input::has('warehouse')           ? strtoupper(trim(Input::get('warehouse')))     : '';
        $CreateStart        = Input::has('create_start')        ? (int)Input::get('create_start')               : 0;
        $Group              = Input::has('group')               ? (int)Input::get('group')                      : 0;
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')               : 0;

        $Model          = $this->getModel();
        if(!$Model){
            return $this->ResponseData();
        }

        if(!empty($CreateStart)) {
            $CreateStart = $this->__convert_time($CreateStart);
        }

        if(!empty($WareHouse)){
            $ListInventory  = $this->__get_seller_inventory($WareHouse);
            if(empty($ListInventory)){
                return $this->ResponseData();
            }

            $Model = $Model->whereIn('inventory', $ListInventory);
        }

        if($Cmd == 'export'){
            return $this->DamagedExcel($Model);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model      = $Model->skip($offset)->take($itemPage);
            $this->data = $Model->with(['__product','__user','__inventory'
                ,'pipe_journey' => function($query) use($Group, $TypeProcess){
                    $query->where('type', $TypeProcess)->where('group_process', $Group);
                },'__putaway' => function($query) use($CreateStart){
                    $query->where('create_time','>=', $CreateStart);
                },'__history' => function($query) use($CreateStart){
                    $query->where('created','>=',$CreateStart)->where('history','Damaged')->orderBy('created','DESC')->with('__employee');
                }])->orderBy('updated_at','DESC')->get()->toArray();

            foreach($this->data as $key => $val){
                $this->data[$key]['pipe_status']    = 0;
                foreach($val['pipe_journey'] as $v){
                    $this->data[$key]['pipe_status'] = (int)$v['pipe_status'];
                }
            }
        }

        return $this->ResponseData();
    }

    public function getLost(){
        $itemPage           = 20;
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';
        $WareHouse          = Input::has('warehouse')           ? strtoupper(trim(Input::get('warehouse')))     : '';
        $CreateStart        = Input::has('create_start')        ? (int)Input::get('create_start')               : 0;
        $Group              = Input::has('group')               ? (int)Input::get('group')                      : 0;
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')               : 0;

        $Model          = $this->getModel();
        if(!$Model){
            return $this->ResponseData();
        }

        if(!empty($CreateStart)) {
            $CreateStart = $this->__convert_time($CreateStart);
        }

        if(!empty($WareHouse)){
            $ListInventory  = $this->__get_seller_inventory($WareHouse);
            if(empty($ListInventory)){
                return $this->ResponseData();
            }

            $Model = $Model->whereIn('inventory', $ListInventory);
        }

        if($Cmd == 'export'){
            return $this->LostExcel($Model);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model      = $Model->skip($offset)->take($itemPage);
            $this->data = $Model->with(['__product','__user','__inventory',
            'pipe_journey' => function($query) use($Group, $TypeProcess){
                $query->where('type', $TypeProcess)->where('group_process', $Group);
            },'__putaway' => function($query) use($CreateStart){
                $query->where('create_time','>=', $CreateStart);
            },'__history' => function($query) use($CreateStart){
                $query->where('created','>=',$CreateStart)->where('history','Lost')->orderBy('created','DESC')->with('__employee');
            }])->orderBy('updated_at','DESC')->get()->toArray();

            foreach($this->data as $key => $val){
                $this->data[$key]['pipe_status']    = 0;
                foreach($val['pipe_journey'] as $v){
                    $this->data[$key]['pipe_status'] = (int)$v['pipe_status'];
                }
            }
        }

        return $this->ResponseData();
    }

    public function getReturned(){
        $itemPage       = 20;
        $page           = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Cmd            = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';

        $CreateStart    = Input::has('create_start')       ? (int)Input::get('create_start')                : 0;
        $CreatedEnd     = Input::has('create_end')         ? (int)Input::get('create_end')                  : 0;
        $UpdateStart    = Input::has('update_start')       ? (int)Input::get('update_start')                : 0;
        $UpdatedEnd     = Input::has('update_end')         ? (int)Input::get('update_end')                  : 0;

        $TrackingCode   = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $KeyWord        = Input::has('keyword')             ? trim(Input::get('keyword'))                   : '';
        $ListStatus     = Input::has('list_status')         ? trim(Input::get('list_status'))               : '';

        $Group          = Input::has('group')               ? (int)Input::get('group')                      : 0;
        $TypeProcess    = Input::has('type_process')        ? (int)Input::get('type_process')               : 0;
        $PipeStatus     = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))               : '';
        $WareHouse      = Input::has('warehouse')           ? strtoupper(trim(Input::get('warehouse')))     : '';

        $Model          = new ReturnItemModel;

        if(!empty($CreateStart)) {
            $Time           = $CreateStart - 86400*90;
            $CreateStart    = $this->__convert_time($CreateStart);
            $Time           = $this->__convert_time($Time);
            $Model       = $Model->where('created','>=',$CreateStart);
        }else{
            return $this->ResponseData();
        }

        if(!empty($CreatedEnd)){
            $CreatedEnd         = $this->__convert_time($CreatedEnd);
            $Model              = $Model->where('update_stocked','<=',$CreatedEnd);
        }

        if(!empty($UpdateStart)){
            $UpdateStart    = $this->__convert_time($UpdateStart);
            $Model          = $Model->where('updated','>=',$UpdateStart);
        }

        if(!empty($UpdatedEnd)){
            $UpdatedEnd    = $this->__convert_time($UpdatedEnd);
            $Model          = $Model->where('updated','<=',$UpdatedEnd);
        }

        if(!empty($WareHouse)){
            $Model = $Model->where('warehouse_code', $WareHouse);
        }

        if(!empty($KeyWord) || !empty($ListStatus) || !empty($PipeStatus)){
            if(!empty($KeyWord)){
                $UserModel      = new \User;
                if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                    $UserModel          = $UserModel->where('email',$KeyWord);
                }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                    $UserModel          = $UserModel->where('phone',$KeyWord);
                }else{
                    $UserModel          = $UserModel->where('fullname',$KeyWord);
                }

                $ListUser = $UserModel->lists('id');
                if(empty($ListUser)){
                    return $this->ResponseData();
                }
            }

            $ListUId    = \bm_ecommercemodel\SellerProductItemModel::where('created','>=', $Time);
            if(!empty($ListUser)){
                $ListUId    = $ListUId->whereIn('user',$ListUser);
            }

            if(!empty($ListStatus)){
                $ListStatus = array_map('intval', explode(',', $ListStatus));
                $ListUId    = $ListUId->whereIn('status',$ListStatus);
            }

            if(!empty($PipeStatus)){
                $PipeStatus = explode(',',$PipeStatus);
                $ListId = PipeJourneyModel::where('type', $TypeProcess)->where('group_process',$Group)->whereIn('pipe_status', $PipeStatus)->lists('tracking_code');

                if(!empty($ListId)){
                    $ListId     = array_unique($ListId);
                    $ListUId    = $ListUId->whereRaw("id in (". implode(",", $ListId) .")");
                }else{
                    return $this->ResponseData();
                }
            }

            $ListUId    = $ListUId->lists('serial_number');
            if(empty($ListUId)){
                return false;
            }
            $Model  = $Model->whereIn('uid', $ListUId);
        }

        if(!empty($TrackingCode)){
            if(preg_match("/^RC/i", $TrackingCode)) {
                $Model = $Model->where('return_code', $TrackingCode);
            }elseif(preg_match("/^U/i", $TrackingCode)){
                $Model = $Model->where('uid', $TrackingCode);
            }elseif(preg_match("/^O/i", $TrackingCode)){
                $Model = $Model->where('order_code', $TrackingCode);
            }elseif(preg_match("/^SC/i", $TrackingCode)){
                $Model = $Model->where('tracking_code', $TrackingCode);
            }else{
                $Model = $Model->where('sku', $TrackingCode);
            }
        }

        if($Cmd == 'export'){
            return $this->ReturnedExcel($Model);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model      = $Model->skip($offset)->take($itemPage);
            $this->data = $Model->with([
                '__get_seller_product' => function($query) use($Group, $TypeProcess) {
                    $query->with(['__product','__user','pipe_journey' => function($query) use($Group, $TypeProcess){
                        $query->where('type', $TypeProcess)->where('group_process', $Group);
                    }]);
                },'__get_return' => function($query){
                    $query->with('__employee');
                }])->orderBy('created','DESC')->get()->toArray();

            foreach($this->data as $key => $val){
                $this->data[$key]['__get_seller_product']['pipe_status']    = 0;
                if(isset($val['__get_seller_product']['pipe_journey']))
                foreach($val['__get_seller_product']['pipe_journey'] as $v){
                    $this->data[$key]['__get_seller_product']['pipe_status'] = (int)$v['pipe_status'];
                }
            }
        }

        return $this->ResponseData();
    }

    private function LostExcel($Model){
        $CreateStart        = Input::has('create_start')        ? (int)Input::get('create_start')               : 0;
        if(!empty($CreateStart)) {
            $CreateStart = $this->__convert_time($CreateStart);
        }

        $Data               = [];

        $Model->with(['__product','__user','__inventory','__putaway' => function($query) use($CreateStart){
            $query->where('create_time','>=', $CreateStart);
        },'__history' => function($query) use($CreateStart){
            $query->where('created','>=',$CreateStart)->where('history','Lost')->orderBy('created','DESC')->with('__employee');
        }])->orderBy('updated_at','DESC')->chunk('1000', function($query) use(&$Data){
            foreach($query as $val){
                $val                = $val->toArray();
                $Data[]             = $val;
            }
        });

        $this->data = $Data;

        return $this->ResponseData();
    }

    private function DamagedExcel($Model){
        $CreateStart        = Input::has('create_start')        ? (int)Input::get('create_start')               : 0;
        if(!empty($CreateStart)) {
            $CreateStart = $this->__convert_time($CreateStart);
        }

        $Data               = [];

        $Model->with(['__product','__user','__inventory','__putaway' => function($query) use($CreateStart){
            $query->where('create_time','>=', $CreateStart);
        },'__history' => function($query) use($CreateStart){
            $query->where('created','>=',$CreateStart)->where('history','Damaged')->orderBy('created','DESC')->with('__employee');
        }])->orderBy('updated_at','DESC')->chunk('1000', function($query) use(&$Data){
            foreach($query as $val){
                $val                = $val->toArray();
                $Data[]             = $val;
            }
        });

        $this->data = $Data;

        return $this->ResponseData();
    }

    private function ExportExcel($Model){
        $CreateStart        = Input::has('create_start')        ? (int)Input::get('create_start')               : 0;
        if(!empty($CreateStart)) {
            $CreateStart = $this->__convert_time($CreateStart);
        }

        $Data               = [];

        $Model->with(['__product','__user','__inventory','__putaway' => function($query) use($CreateStart){
            $query->where('create_time','>=', $CreateStart);
        }])->orderBy('update_stocked','DESC')->chunk('1000', function($query) use(&$Data){
            foreach($query as $val){
                $val                = $val->toArray();
                $Data[]             = $val;
            }
        });

        $this->data = $Data;

        return $this->ResponseData();
    }
}
