<?php namespace warehouse;

use warehousemodel\PackageItemModel;
use warehousemodel\PackageModel;

class WareHousePackageCtrl extends BaseCtrl {
    function __construct(){

    }

    private $count_group = ['ALL' => 0];

    private function getModel(){
        $CreateStart    = Input::has('create_start')       ? (int)Input::get('create_start')              : 0;
        $CreatedEnd     = Input::has('create_end')         ? (int)Input::get('create_end')                : 0;

        $TrackingCode   = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $KeyWord        = Input::has('keyword')             ? trim(Input::get('keyword'))                   : '';
        $PackageStatus  = Input::has('package_status')      ? trim(Input::get('package_status'))                   : '';
        $ListStatus     = Input::has('list_status')         ? trim(Input::get('list_status'))               : '';


        $Model          = new PackageItemModel;
        $ListUser       = [];

        if(!empty($CreateStart)){
            $Time           = $CreateStart - 86400*90;
            $CreateStart    = $this->__convert_time($CreateStart);
            $Time           = $this->__convert_time($Time);
            $Model          = $Model->where('create','>=',$CreateStart);
        }else{
            return false;
        }

        if(!empty($CreatedEnd)){
            $CreatedEnd         = $this->__convert_time($CreatedEnd);
            $Model              = $Model->where('create','<=',$CreatedEnd);
        }

        if(!empty($PackageStatus)){
            $ListPackage    = PackageModel::where('create','>=',$CreateStart)->where('status',$PackageStatus)->lists('id');
            if(empty($ListPackage)){
                return false;
            }

            $Model  = $Model->whereIn('package', $ListPackage);
        }

        if(!empty($TrackingCode)){
                if(preg_match("/^PAK/i", $TrackingCode)) {
                    $Model = $Model->where('package_code', $TrackingCode);
                }elseif(preg_match("/^PK/i", $TrackingCode)) {
                    $Model = $Model->where('pickup_code', $TrackingCode);
                }elseif(preg_match("/^SC/i", $TrackingCode)) {
                    $Model = $Model->where('tracking_code', $TrackingCode);
                }elseif(preg_match("/^O/i", $TrackingCode)) {
                    $Model = $Model->where('order_number', $TrackingCode);
                }elseif(preg_match("/^U/i", $TrackingCode)) {
                    $Model = $Model->where('uid', $TrackingCode);
                }else{
                    $Model = $Model->where('sku', $TrackingCode);
                }
        }

        if(!empty($KeyWord) || !empty($ListStatus)){
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
            }

            $ListUId    = \bm_ecommercemodel\SellerProductItemModel::where('created','>=', $Time);
            if(!empty($ListUser)){
                $ListUId    = $ListUId->whereIn('user',$ListUser);
            }

            if(!empty($ListStatus)){
                $ListStatus = array_map('intval', explode(',', $ListStatus));
                $ListUId    = $ListUId->whereIn('status',$ListStatus);
            }

            $ListUId    = $ListUId->lists('serial_number');
            if(empty($ListUId)){
                return false;
            }
            $Model  = $Model->whereIn('uid', $ListUId);
        }

        return $Model;
    }

    private function ResponseData($check = true){
        return Response::json([
            'error'             => false,
            'code'              => 'SUCCESS',
            'error_message'     => 'Thành công',
            'total'             => $this->total,
            'data'              => $this->data,
            'group'             => $this->count_group
        ]);
    }

    public function getIndex(){
        $itemPage           = 20;
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';
        $WareHouse          = Input::has('warehouse')           ? strtoupper(trim(Input::get('warehouse')))     : '';
        $CreateStart        = Input::has('create_start')        ? (int)Input::get('create_start')               : 0;

        $Model          = $this->getModel();
        if(!$Model){
            return $this->ResponseData();
        }

        if(!empty($WareHouse)){
            $Model  = $Model->where('warehouse', $WareHouse);
        }

        if($Cmd == 'export'){
            return $this->ExportExcel($Model);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model      = $Model->skip($offset)->take($itemPage);
            $this->data = $Model->with([
                '__get_seller_product' => function($query){
                    $query->with(['__product','__get_user']);
                },'__get_package'
            ])->orderBy('create','DESC')->get()->toArray();
        }

        return $this->ResponseData();
    }

    private function                                                                                                                                                                           ExportExcel($Model){
        $CreateStart        = Input::has('create_start')        ? (int)Input::get('create_start')               : 0;
        $Time               = $this->__convert_time($CreateStart - 86400*90);
        $Data               = [];

        $Model->with([
            '__get_seller_product' => function($query) use($Time){
                $query->with(['__product','__get_user'])->where('created','>=',$Time);
            },'__get_package'
        ])->orderBy('create','DESC')->chunk('1000', function($query) use(&$Data){
            foreach($query as $val){
                $val                = $val->toArray();
                $Data[]             = $val;
            }
        });

        $this->data = $Data;
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

    private function get_list_no_putaway(){
        $Model          = $this->getModel();
        if(!$Model){
            return false;
        }

        $ListPackage    = $Model->lists('uid');
        if(empty($ListPackage)){
            return false;
        }

        $ListPutAway    = \bm_warehousemodel\PutawayItemModel::whereRaw("uid in ('". implode("','", $ListPackage) ."')")
            ->lists('uid');
        if(!empty($ListPutAway)){
            $ListUId    = array_diff($ListPackage, $ListPutAway);
        }else{
            $ListUId    = $ListPackage;
        }

        return $ListUId;
    }

    private function ExportNoPutAway($Model){
        $Model->with([
            '__get_seller_product' => function($query){
                $query->with(['__product','__user']);
            },'__get_package'
        ])->orderBy('create','DESC')->chunk('1000', function($query) use(&$Data){
            foreach($query as $val){
                $val                = $val->toArray();
                $Data[]             = $val;
            }
        });

        $this->data = $Data;

        return $this->ResponseData();
    }

    public function getNoPutaway(){
        $itemPage           = 20;
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Cmd                = Input::has('cmd')                 ? strtoupper(trim(Input::get('cmd')))           : '';
        $WareHouse          = Input::has('warehouse')           ? strtoupper(trim(Input::get('warehouse')))     : '';
        $CreateStart        = Input::has('create_start')        ? (int)Input::get('create_start')               : 0;
        $Group              = Input::has('group')               ? (int)Input::get('group')                      : 5;
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')               : 13;

        $ListUId        = $this->get_list_no_putaway();
        if(empty($ListUId)){
            return $this->ResponseData();
        }

        $Model          = PackageItemModel::whereRaw("uid in ('". implode("','", $ListUId) ."')");

        if(!empty($WareHouse)){
            $Model  = $Model->where('warehouse', $WareHouse);
        }

        if($Cmd == 'EXPORT'){
            return $this->ExportNoPutAway($Model);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model      = $Model->skip($offset)->take($itemPage);
            $this->data = $Model->with([
                '__get_seller_product' => function($query){
                    $query->with(['__product','__user']);
                },'__get_package'
            ])->orderBy('create','DESC')->get()->toArray();
        }

        return $this->ResponseData();
    }

    public function getNoPutawayCountGroup(){
        $ListUId        = $this->get_list_no_putaway();
        if(empty($ListUId)){
            return $this->ResponseData();
        }

        $Model          = PackageItemModel::whereRaw("uid in ('". implode("','", $ListUId) ."')");

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

    private function get_list_no_pickup(){
        $Model          = $this->getModel();
        if(!$Model){
            return false;
        }

        $ListPackage    = $Model->lists('uid');
        if(empty($ListPackage)){
            return false;
        }

        $ListPickup    = \bm_warehousemodel\PickupItemModel::where('status',5)
            ->whereRaw("uid in ('". implode("','", $ListPackage) ."')")
            ->lists('uid');
        if(!empty($ListPickup)){
            $ListUId    = array_diff($ListPackage, $ListPickup);
        }else{
            $ListUId    = $ListPackage;
        }

        return $ListUId;
    }

    private function ExportNoPickup($Model){
        $Model->with([
            '__get_seller_product' => function($query){
                $query->with(['__product','__user']);
            },'__get_package','__get_pickup'
        ])->orderBy('create','DESC')->chunk('1000', function($query) use(&$Data){
            foreach($query as $val){
                $val                = $val->toArray();
                $Data[]             = $val;
            }
        });

        $this->data = $Data;

        return $this->ResponseData();
    }

    public function getNoPickup(){
        $itemPage           = 20;
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Cmd                = Input::has('cmd')                 ? strtoupper(trim(Input::get('cmd')))           : '';
        $WareHouse          = Input::has('warehouse')           ? strtoupper(trim(Input::get('warehouse')))     : '';
        $CreateStart        = Input::has('create_start')        ? (int)Input::get('create_start')               : 0;
        $Group              = Input::has('group')               ? (int)Input::get('group')                      : 5;
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')               : 13;

        $ListUId        = $this->get_list_no_pickup();
        if(empty($ListUId)){
            return $this->ResponseData();
        }

        $Model          = PackageItemModel::whereRaw("uid in ('". implode("','", $ListUId) ."')");

        if(!empty($WareHouse)){
            $Model  = $Model->where('warehouse', $WareHouse);
        }

        if($Cmd == 'EXPORT'){
            return $this->ExportNoPickup($Model);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model      = $Model->skip($offset)->take($itemPage);
            $this->data = $Model->with([
            '__get_seller_product' => function($query){
                $query->with(['__product','__user']);
            },'__get_package','__get_pickup'
        ])->orderBy('create','DESC')->get()->toArray();
    }

        return $this->ResponseData();
    }

    public function getNoPickupCountGroup(){
        $ListUId        = $this->get_list_no_pickup();
        if(empty($ListUId)){
            return $this->ResponseData();
        }

        $Model          = PackageItemModel::whereRaw("uid in ('". implode("','", $ListUId) ."')");

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

    private function __calculate_size($size = ''){
        $str    = explode('x',$size);
        return ($str[0] * $str[1] * $str[2]);
    }

    private function get_list_error_size(){
        $CreateStart    = Input::has('create_start')       ? (int)Input::get('create_start')              : 0;
        $CreatedEnd     = Input::has('create_end')         ? (int)Input::get('create_end')                : 0;

        $TrackingCode   = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $KeyWord        = Input::has('keyword')             ? trim(Input::get('keyword'))                   : '';
        $PackageStatus  = Input::has('package_status')      ? trim(Input::get('package_status'))            : '';
        $WareHouse      = Input::has('warehouse')           ? strtoupper(trim(Input::get('warehouse')))     : '';


        $Model          = new PackageItemModel;
        $ListUser       = [];

        if(!empty($CreateStart)){
            $Time           = $CreateStart - 86400*90;
            $CreateStart    = $this->__convert_time($CreateStart);
            $Time           = $this->__convert_time($Time);
            $Model          = $Model->where('create','>=',$CreateStart);
        }else{
            return false;
        }

        if(!empty($CreatedEnd)){
            $CreatedEnd         = $this->__convert_time($CreatedEnd);
            $Model              = $Model->where('create','<=',$CreatedEnd);
        }

        if(!empty($PackageStatus)){
            $ListPackage    = PackageModel::where('create','>=',$CreateStart)->where('status',$PackageStatus)->lists('id');
            if(empty($ListPackage)){
                return false;
            }

            $Model  = $Model->whereIn('package', $ListPackage);
        }

        if(!empty($TrackingCode)){
            if(preg_match("/^PAK/i", $TrackingCode)) {
                $Model = $Model->where('package_code', $TrackingCode);
            }elseif(preg_match("/^PK/i", $TrackingCode)) {
                $Model = $Model->where('pickup_code', $TrackingCode);
            }elseif(preg_match("/^SC/i", $TrackingCode)) {
                $Model = $Model->where('tracking_code', $TrackingCode);
            }elseif(preg_match("/^O/i", $TrackingCode)) {
                $Model = $Model->where('order_number', $TrackingCode);
            }elseif(preg_match("/^U/i", $TrackingCode)) {
                $Model = $Model->where('uid', $TrackingCode);
            }else{
                $Model = $Model->where('sku', $TrackingCode);
            }
        }

        if(!empty($KeyWord)){
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
            }

            $ListUId    = \bm_ecommercemodel\SellerProductItemModel::where('created','>=', $Time);
            if(!empty($ListUser)){
                $ListUId    = $ListUId->whereIn('user',$ListUser);
            }

            $ListUId    = $ListUId->lists('serial_number');
            if(empty($ListUId)){
                return false;
            }
            $Model  = $Model->whereIn('uid', $ListUId);
        }

        $Data   = [];
        $Model->groupBy('order_number')->having('total', '=', 1)
        ->with(['__get_package','__get_history','__get_product' => function($query){
            $query->with(['__user']);
        }])
        ->select(['warehouse','package','package_code','pickup_code','tracking_code','sku','order_number','uid','create',DB::raw('count(*) as total')])
        ->chunk('1000', function($query) use(&$Data){
            foreach($query as $val){
                $val                = $val->toArray();
                $val['warehouse']   = trim(strtoupper($val['warehouse']));

                if(!empty($val['__get_package'])){
                    if(!empty($val['__get_product']['volume']) && (((!empty($val['__get_history']) && !empty($val['__get_history'][0]['box'])) ||  !empty($val['__get_product']['packing_volume'])) && trim(strtolower($val['__get_package']['size'])) != '0x0x0')){
                        $volume = !empty($val['__get_product']['packing_volume']) ? $val['__get_product']['packing_volume'] : $val['__get_history'][0]['box'];
                        
                        if(trim(strtolower($val['__get_package']['size'])) != trim(strtolower($volume))){
                            if($this->__calculate_size($val['__get_package']['size']) > $this->__calculate_size($volume)){
                                if(!empty($WareHouse)){
                                    if($val['warehouse'] == $WareHouse){
                                        $Data[] = $val;
                                    }
                                }else{
                                    $Data[] = $val;
                                }

                                if(!isset($this->count_group[$val['warehouse']])){
                                    $this->count_group[$val['warehouse']]   = 0;
                                }
                                $this->count_group[$val['warehouse']] += 1;
                                $this->count_group['ALL'] += 1;
                            }
                        }
                    }
                }else{
                    $Data[] = $val;
                    if(!isset($this->count_group[$val['warehouse']])){
                        $this->count_group[$val['warehouse']]   = 0;
                    }
                    $this->count_group[$val['warehouse']] += 1;
                    $this->count_group['ALL'] += 1;
                }
            }
        });

        return $Data;
    }

    public function getErrorSize(){
        $itemPage           = 20;
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Cmd                = Input::has('cmd')                 ? strtoupper(trim(Input::get('cmd')))           : '';

        $ListPackage        = $this->get_list_error_size();
        if(empty($ListPackage)){
            return $this->ResponseData();
        }


        if($Cmd == 'EXPORT'){
            $this->data = $ListPackage;
            return $this->ResponseData();
        }

        $this->total    = count($ListPackage);

        $this->data    = array_chunk($ListPackage,$itemPage);

        if(isset($this->data[($page - 1)])){
            $this->data     = $this->data[($page - 1)];
        }else{
            $this->data     = [];
        }

        return $this->ResponseData();
    }
}
