<?php namespace warehouse;

use bm_ecommercemodel\ProductModel;
use bm_ecommercemodel\SellerProductModel;

class WareHouseBsinCtrl extends BaseCtrl {
    function __construct(){

    }

    private function getModel(){
        $CreateStart    = Input::has('create_start')       ? (int)Input::get('create_start')                : 0;
        $CreatedEnd     = Input::has('create_end')         ? (int)Input::get('create_end')                  : 0;
        $UpdateStart    = Input::has('update_start')       ? (int)Input::get('update_start')                : 0;
        $UpdatedEnd     = Input::has('update_end')         ? (int)Input::get('update_end')                  : 0;

        $TrackingCode   = Input::has('tracking_code')       ? trim(Input::get('tracking_code'))             : '';

        $Model          = new ProductModel;

        if(!empty($CreateStart)){
            $CreateStart    = $this->__convert_time($CreateStart);
            $Model          = $Model->where('created','>=',$CreateStart);
        }else{
            return false;
        }

        if(!empty($CreatedEnd)){
            $CreatedEnd         = $this->__convert_time($CreatedEnd);
            $Model              = $Model->where('created','<=',$CreatedEnd);
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
            $Model = $Model->where('bsin', $TrackingCode);
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

        $Model          = $this->getModel();
        if(!$Model){
            return $this->ResponseData();
        }

        if($Cmd == 'export'){
            return $this->ExportExcel($Model);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset         = ($page - 1)*$itemPage;
            $this->data     = $Model->skip($offset)->take($itemPage)->orderBy('created','DESC')->get()->toArray();
        }

        return $this->ResponseData();
    }

    private function __get_product($BsinCode){
        return ProductModel::where('bsin', $BsinCode)->lists('id');
    }

    public function getDetail(){
        $CreateStart    = Input::has('create_start')       ? (int)Input::get('create_start')                : 0;
        $CreatedEnd     = Input::has('create_end')         ? (int)Input::get('create_end')                  : 0;

        $BsinCode       = Input::has('bsin_code')           ? strtolower(trim(Input::get('bsin_code')))     : '';
        $SkuCode        = Input::has('sku_code')            ? strtolower(trim(Input::get('sku_code')))   : '';
        $KeyWord        = Input::has('keyword')             ? trim(Input::get('keyword'))                   : '';

        $itemPage           = 20;
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';

        $Model          = new SellerProductModel;

        if(!empty($CreateStart)){
            $CreateStart    = $this->__convert_time($CreateStart);
            $Model          = $Model->where('created','>=',$CreateStart);
        }else{
            return $this->ResponseData();
        }

        if(!empty($CreatedEnd)){
            $CreatedEnd         = $this->__convert_time($CreatedEnd);
            $Model              = $Model->where('created','<=',$CreatedEnd);
        }

        if(!empty($UpdateStart)){
            $UpdateStart    = $this->__convert_time($UpdateStart);
            $Model          = $Model->where('updated_at','>=',$UpdateStart);
        }

        if(!empty($UpdatedEnd)){
            $UpdatedEnd    = $this->__convert_time($UpdatedEnd);
            $Model          = $Model->where('updated_at','<=',$UpdatedEnd);
        }

        if(!empty($SkuCode)){
            $Model = $Model->where('sku', $SkuCode);
        }

        if(!empty($BsinCode)){
            $ListProduct    = $this->__get_product($BsinCode);
            if(empty($ListProduct)){
                return $this->ResponseData();
            }

            $Model = $Model->whereRaw("product in (". implode(",", $ListProduct) .")");
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
                return $this->ResponseData();
            }

            $Model  = $Model->whereIn('user', $ListUser);
        }

        if($Cmd == 'export'){
            return $this->ExportExcel($Model);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset         = ($page - 1)*$itemPage;
            $this->data     = $Model->with(['__product','__inventory','__user'])->skip($offset)->take($itemPage)->orderBy('created','DESC')->get()->toArray();
        }

        return $this->ResponseData();
    }

    private function __get_history_status($CreateStart){
        $ListStatus     = Input::has('list_status')         ? strtolower(trim(Input::get('list_status')))               : '';
        if(!empty($ListStatus)){
            $ListStatus     = explode(',',$ListStatus);
        }

        $ListUId        = [];
        foreach($this->data as $val){
            $ListUId[]  = $val['serial_number'];
        }

        $ListHistory    = \metadatamodel\ItemHistoryModel::whereRaw("uid in ('". implode("','", $ListUId) ."')")
            ->where('created','<=', $CreateStart)->orderBy('created','ASC')->get(['id','created','uid','history'])->toArray();



        if(!empty($ListHistory)){
            $History    = [];
            foreach($ListHistory as $val){
                $History[$val['uid']]   = $val['history'];
            }
        }

        foreach($this->data as $key => $val){
            if(isset($History[$val['serial_number']])){
                if(!empty($ListStatus)){
                    if(in_array(strtolower($History[$val['serial_number']]), $ListStatus)){
                        $this->data[$key]['status_name'] = $History[$val['serial_number']];
                    }else{
                        unset($this->data[$key]);
                    }
                }else{
                    $this->data[$key]['status_name'] = $History[$val['serial_number']];
                }
            }else{
                unset($this->data[$key]);
            }
        }

        return;
    }

    public function getToolBsinUid(){
        $page               = Input::has('page')            ? (int)Input::get('page')                       : 1;

        $CreateStart    = Input::has('time_start')          ? (int)Input::get('time_start')                 : 0;
        $UId            = Input::has('uid')                 ? strtoupper(trim(Input::get('uid')))           : '';
        $Sku            = Input::has('sku')                 ? strtoupper(trim(Input::get('sku')))           : '';
        $WareHouse      = Input::has('warehouse')           ? strtolower(trim(Input::get('warehouse')))     : '';

        $itemPage           = 20;

        $Cmd                = Input::has('cmd')             ? trim(Input::get('cmd'))                       : '';

        $Model          = new \bm_ecommercemodel\SellerProductItemModel;
        $Model          = $Model->where('status','<>',0);

        if(!empty($CreateStart)){
            $CreateStart    = $this->__convert_time($CreateStart);
            $Model          = $Model->where('created','<=',$CreateStart);
        }else{
            return $this->ResponseData();
        }

        if(empty($Sku) && empty($UId)){
            return $this->ResponseData();
        }

        if(!empty($Sku)){
            $Model = $Model->where('sku', $Sku);
        }

        if(!empty($UId)){
            $Model = $Model->where('serial_number', $UId);
        }

        if(!empty($WareHouse)){
            $ListInventory  = $this->__get_seller_inventory($WareHouse);
            if(empty($ListInventory)){
                return $this->ResponseData();
            }

            $Model = $Model->whereIn('inventory', $ListInventory);
        }

        if($Cmd == 'export'){
            $this->data     = $Model->orderBy('created','DESC')->with(['__inventory','__product'])->get(['id','serial_number','sku','inventory','status','seller_product'])->toArray();
            $this->__get_history_status($CreateStart);
            return $this->ResponseData();
        }

        $this->data     = $Model->orderBy('created','DESC')->with(['__inventory','__product'])->get(['id','serial_number','sku','inventory','status','seller_product'])->toArray();
        $this->__get_history_status($CreateStart);

        $this->total    = count($this->data);

        $this->data    = array_chunk($this->data,20);

        if(isset($this->data[($page - 1)])){
            $this->data     = $this->data[($page - 1)];
        }else{
            $this->data     = [];
        }

        return $this->ResponseData();
    }

    public function getHistoryUid(){
        $itemPage           = 20;
        $page               = Input::has('page')            ? (int)Input::get('page')                       : 1;

        $CreateStart    = Input::has('time_start')          ? (int)Input::get('time_start')                 : 0;
        $CreateEnd      = Input::has('time_end')            ? (int)Input::get('time_end')                   : 0;
        $OrderCode      = Input::has('order_code')          ? strtoupper(trim(Input::get('order_code')))    : '';
        $UId            = Input::has('uid')                 ? strtoupper(trim(Input::get('uid')))           : '';
        $ListStatus     = Input::has('list_status')         ? strtolower(trim(Input::get('list_status')))   : '';
        $Cmd                = Input::has('cmd')             ? trim(Input::get('cmd'))                       : '';

        $Model          = new \metadatamodel\ItemHistoryModel;

        if(!empty($ListStatus)){
            $ListStatus     = explode(',',$ListStatus);
            $Model          = $Model->whereRaw("history in ('". implode("','", $ListStatus) ."')");
        }

        if(!empty($CreateStart)){
            $CreateStart    = $this->__convert_time($CreateStart);
            $Model          = $Model->where('created','>=',$CreateStart);
        }else{
            return $this->ResponseData();
        }

        if(!empty($CreateEnd)){
            $CreateEnd    = $this->__convert_time($CreateEnd);
            $Model          = $Model->where('created','<=',$CreateEnd);
        }

        if(!empty($OrderCode)){
            if(preg_match("/^SC/i", $OrderCode)){
                $Model = $Model->where('tracking_code', $OrderCode);
            }else{
                $Model = $Model->where('order_number', $OrderCode);
            }
        }

        if(!empty($UId)){
            $Model = $Model->where('uid', $UId);
        }

        if($Cmd == 'export'){
            $this->data     = $Model->orderBy('created','DESC')->get()->toArray();
            return $this->ResponseData();
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset         = ($page - 1)*$itemPage;
            $this->data     = $Model->skip($offset)->take($itemPage)->orderBy('created','DESC')->get()->toArray();
        }

        return $this->ResponseData();
    }
}
