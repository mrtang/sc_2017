<?php namespace warehouse;

class ProblemCtrl extends BaseCtrl {
    private $list_order     = [];
    private $list_courier   = [];
    private $count_group    = ['ALL' => 0];

    function __construct(){

    }

    private function ResponseData(){

        return Response::json([
            'error'                     => $this->error,
            'message'                   => $this->message,
            'total'                     => $this->total,
            'data'                      => $this->data,
            'list_order'                => $this->list_order,
            'list_courier'              => $this->list_courier,
            'count_group'               => $this->count_group
        ]);
    }

    private function getModel(){
        $Model              = new \ordermodel\OrdersModel;

        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')           : 0; // time_accept start
        $TimeAcceptEnd      = Input::has('accept_end')          ? (int)Input::get('accept_end')             : 0; // time_accept end
        $TimeSuccessStart   = Input::has('success_start')       ? (int)Input::get('success_start')          : 0; // time_accept start
        $TimeSuccessEnd     = Input::has('success_end')         ? (int)Input::get('success_end')            : 0; // time_accept end
        $PickupStart        = Input::has('pickup_start')        ? (int)Input::get('pickup_start')           : 0; // time_pickup start
        $PickupEnd          = Input::has('pickup_end')          ? (int)Input::get('pickup_end')             : 0; // time_pickup end

        $ServiceId          = Input::has('service')             ? (int)Input::get('service')                : 0;
        $KeyWord            = Input::has('keyword')             ? trim(Input::get('keyword'))               : 0;
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';

        $FromCity           = Input::has('from_city')           ? (int)Input::get('from_city')              : 0;
        $FromDistrict       = Input::has('from_district')       ? (int)Input::get('from_district')          : 0;

        $ToCity             = Input::has('to_city')             ? (int)Input::get('to_city')                : 0;
        $ToDistrict         = Input::has('to_district')           ? (int)Input::get('to_district')          : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))           : '';

        // Loại đơn nội thành
        // 1: Đơn trong ngày
        // 2: Đơn qua ngày

        $Location           = Input::has('location')            ? (int)Input::get('location')               : 0;

        $FromCountryId      = Input::has('from_country_id')     ? (int)Input::get('from_country_id')        : 237;
        $Global             = Input::has('global')              ? (int)Input::get('global')                 : null;

        $Model              = $Model::where('domain', 'boxme.vn')->where('from_country_id', $FromCountryId);

        if(!empty($Global)){
            $Model  = $Model->where('to_country_id','<>', $FromCountryId);
        }

        if(!empty($ListStatus) && empty($TrackingCode)){
            $ListStatus = explode(',',$ListStatus);
            $Model          = $Model->whereIn('status',$ListStatus);
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

            $ListUserSearch = $UserModel->lists('id');
            if(empty($ListUserSearch)){
                $this->error = true;
                return;
            }else{
                if(!empty($ListUser)){
                    $ListUser   = array_intersect($ListUser, $ListUserSearch);
                }else{
                    $ListUser   = $ListUserSearch;
                }
            }

            if(empty($ListUser)){
                $this->error = true;
                return;
            }
        }

        if(!empty($ListUser)){
            $Model  = $Model->whereRaw("from_user_id in (". implode(",", $ListUser) .")");
        }

        if(!empty($ToUser)){
            if (filter_var($ToUser, FILTER_VALIDATE_EMAIL)){  // search email
                $Model          = $Model->where('to_email',$ToUser);

            }elseif(filter_var((int)$ToUser, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $Model          = $Model->where('to_phone',$ToUser);
            }else{ // search code
                $Model          = $Model->where('to_name',$ToUser);
            }
        }

        if(!empty($ServiceId)){
            $Model          = $Model->where('service_id',$ServiceId);
        }

        if(!empty($FromDistrict)){
            $Model          = $Model->where('from_district_id',$FromDistrict);
        }elseif(!empty($FromCity)){
            $Model          = $Model->where('from_city_id',$FromCity);
        }

        if(!empty($Location)){// 1 nội thành,  2 ngoại thành , 3 liên tỉnh , 4 Ngoại thành or liên tỉnh

            Input::merge(['city'    => $ToCity]);

            switch ((int)$Location) {
                case 1:
                    Input::merge(['location' => 1]);
                    $ListLocation   = $this->getDistrictByLocation(false);
                    break;
                case 2:
                    Input::merge(['location' => 2]);
                    $ListLocation   = $this->getDistrictByLocation(false);
                    break;
                case 3:
                    $Model  = $Model->whereRaw('from_city_id <> to_city_id');
                    break;
                default:
                    Input::merge(['location' => 1]);
                    $ListLocation   = $this->getDistrictByLocation(false);
            }
        }

        if($Location ==  4){ // Tất cả trừ nội thành
            if(!empty($ToDistrict)){
                if(!empty($ListLocation)){ // nếu có tìm theo location
                    if(in_array($ToDistrict, $ListLocation)){
                        $this->error = true;
                        return;
                    }
                }
            }else {
                if(!empty($ToCity)){
                    $Model  = $Model->where('to_city_id', $ToCity);
                }
            }

            if(!empty($ListLocation)){
                $Model          = $Model->whereNotIn('to_district_id',$ListLocation);
            }
        }else{
            if(!empty($ToDistrict)){
                if(!empty($ListLocation)){ // nếu có tìm theo location
                    $ListLocation = array_intersect([$ToDistrict], $ListLocation);
                }
                if(empty($ListLocation)){
                    $this->error = true;
                    return;
                }
                $Model          = $Model->where('to_district_id',$ListLocation);
            }else {
                if(!empty($ToCity)){
                    $Model  = $Model->where('to_city_id', $ToCity);
                }

                if(!empty($ListLocation)){
                    $Model          = $Model->whereIn('to_district_id',$ListLocation);
                }
            }
        }



        if(!empty($TrackingCode)){
            if(preg_match("/^O/i", $TrackingCode)){
                $Model          = $Model->where('order_code',$TrackingCode);
            }else{
                $Model          = $Model->where(function($query) use($TrackingCode){
                    $query->where('tracking_code',$TrackingCode)
                        ->orWhere('courier_tracking_code', $TrackingCode);
                });
            }

        }

        if(!empty($TimeAcceptStart)){
            $Model          = $Model->where('time_accept','>=',$TimeAcceptStart);
        }

        if(empty($TimeAcceptEnd)){
            $TimeAcceptEnd  = $this->time();
        }

        $Model          = $Model->where('time_accept','<=',$TimeAcceptEnd);

        if(!empty($TimeSuccessStart)){
            $Model          = $Model->where('time_success','>=',$TimeSuccessStart);
        }

        if(!empty($TimeSuccessEnd)){
            $Model          = $Model->where('time_success','<=',$TimeSuccessEnd);
        }

        if(!empty($PickupStart)){
            $Model          = $Model->where('time_pickup','>=',$PickupStart);
        }

        if(!empty($PickupEnd)){
            $Model          = $Model->where('time_pickup','<=',$PickupEnd);
        }

        return $Model;
    }

    public function getReturnSlow(){
        $itemPage           = 20;
        $this->error        = false;
        $this->message      = 'success';

        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;

        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')           : 1;
        $PipeStatus         = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))           : '';

        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')           : 0;
        $TimePackedStart    = Input::has('packed_start')        ? (int)Input::get('packed_start')           : 0;
        $TimePackedEnd      = Input::has('packed_end')          ? (int)Input::get('packed_end')             : 0; // time_accept end
        $WareHouse          = Input::has('warehouse')           ? Input::get('warehouse')                   : "";
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';
        $OrderModel         = $this->getModel();

        if($this->error){
            $this->error    = false;
            return $this->ResponseData();
        }

        if(!empty($WareHouse)){
            $OrderModel          = $OrderModel->where('warehouse',$WareHouse);
        }

        $Order       = $OrderModel->get(['tracking_code','order_code','courier_id','time_success','time_update'])->toArray();
        if(empty($Order)){
            $this->error    = false;
            return $this->ResponseData();
        }

        $ListTrackingCode   = [];
        $ListOrderCode      = [];
        $ListOrder          = [];
        $ListReferCourier   = [];
        foreach($Order as $val){
            $ListTrackingCode[]                     = $val['tracking_code'];
            $ListOrder[$val['tracking_code']]       = ($val['time_success'] > 0) ? $val['time_success'] : $val['time_update'];
            $ListReferCourier[$val['tracking_code']] = $val['courier_id'];
            $ListReferCourier[$val['order_code']]    = $val['courier_id'];
            if(!empty($val['order_code'])){
                $ListOrderCode[]                    = $val['order_code'];
                $ListOrder[$val['order_code']]      = ($val['time_success'] > 0) ? $val['time_success'] : $val['time_update'];
            }
        }


        $ReturnItem = \warehousemodel\ReturnItemModel::whereRaw("order_code in ('". implode("','", $ListOrderCode) ."')")
                                                    ->orWhere(function($query) use($ListTrackingCode){
                                                        $query->whereRaw("tracking_code in ('". implode("','", $ListTrackingCode) ."')");
                                                    })
                                                     ->where('closed',1)->lists('uid');

        $PackedItem = \warehousemodel\PackageItemModel::where(function($query) use($ListOrderCode, $ListTrackingCode){
            $query->whereRaw("order_number in ('". implode("','", $ListOrderCode) ."')")
                ->orWhere(function($q) use($ListTrackingCode){
                    $q->whereRaw("tracking_code in ('". implode("','", $ListTrackingCode) ."')");
                });
        });

        if(!empty($ReturnItem)){
            $PackedItem = $PackedItem->whereRaw("uid not in ('". implode("','", $ReturnItem) ."')");
        }

        if(!empty($TimePackedStart)){
            $TimePackedStart = $this->__convert_time($TimePackedStart);
            $PackedItem = $PackedItem->where('time_packge','>=',$TimePackedStart);
        }

        if(!empty($TimePackedEnd)){
            $TimePackedEnd = $this->__convert_time($TimePackedEnd);
            $PackedItem = $PackedItem->where('time_packge','<=',$TimePackedEnd);
        }

        if(!empty($PipeStatus) && !empty($Group)){
            $PipeStatus = explode(',',$PipeStatus);

            $ListId = \omsmodel\PipeJourneyModel::where('time_create', '>=', $TimeAcceptStart)->where('type', $TypeProcess)
                                                ->where('group_process',$Group)->whereIn('pipe_status', $PipeStatus)->lists('tracking_code');
            if(!empty($ListId)){
                $ListId = array_unique($ListId);
                $PackedItem  = $PackedItem->whereRaw("id in (". implode(",", $ListId) .")");
            }else{
                return $this->ResponseData();
            }
        }

        if($Cmd == 'export'){
            $this->list_order       = $ListOrder;
            $this->data             = $PackedItem->orderBy('create','ASC')->get()->toArray();
            $this->list_courier     = $ListReferCourier;
            return $this->ResponseData();
        }

        $TotalModel     = clone $PackedItem;
        $this->total    = $TotalModel->count();

        if($this->total > 0) {
            $offset = ($page - 1) * $itemPage;
            $PackedItem = $PackedItem->skip($offset)->take($itemPage);

            if(!empty($Group)){
                $PackedItem  = $PackedItem->with(['pipe_journey' => function($query) use($Group,$TypeProcess){
                    $query->where('type', $TypeProcess)->where('group_process', $Group)->orderBy('time_create', 'ASC');
                }]);
            }
            $Data = $PackedItem->orderBy('create','ASC')->get()->toArray();

            foreach($Data as $key => $val){
                $Data[$key]['pipe_status'] = 0;
                if(isset($ListOrder[$val['order_number']])){
                    $this->list_order[$val['order_number']] = $ListOrder[$val['order_number']];
                }

                if(!empty($val['pipe_journey'])){
                    foreach($val['pipe_journey'] as $v){
                        $Data[$key]['pipe_status'] = (int)$v['pipe_status'];
                    }
                }
            }

            $this->data = $Data;

        }

        return $this->ResponseData();
    }

    public function getCountGroupReturnSlow(){
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')           : 1;
        $PipeStatus         = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))           : '';
        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')           : 0;

        $this->error        = false;
        $OrderModel         = $this->getModel();

        if($this->error){
            $this->error    = false;
            return $this->ResponseData();
        }

        $Order       = $OrderModel->get(['tracking_code','order_code','time_success','time_update'])->toArray();
        if(empty($Order)){
            $this->error    = false;
            return $this->ResponseData();
        }

        $ListTrackingCode   = [];
        $ListOrderCode      = [];
        $ListOrder          = [];
        foreach($Order as $val){
            $ListTrackingCode[]                     = $val['tracking_code'];
            $ListOrder[$val['tracking_code']]       = ($val['time_success'] > 0) ? $val['time_success'] : $val['time_update'];
            if(!empty($val['order_code'])){
                $ListOrderCode[]                    = $val['order_code'];
                $ListOrder[$val['order_code']]      = ($val['time_success'] > 0) ? $val['time_success'] : $val['time_update'];
            }
        }


        $ReturnItem = \warehousemodel\ReturnItemModel::whereRaw("order_code in ('". implode("','", $ListOrderCode) ."')")
            ->orWhere(function($query) use($ListTrackingCode){
                $query->whereRaw("tracking_code in ('". implode("','", $ListTrackingCode) ."')");
            })
            ->where('closed',1)->lists('uid');

        $PackedItem = \warehousemodel\PackageItemModel::where(function($query) use($ListOrderCode, $ListTrackingCode){
            $query->whereRaw("order_number in ('". implode("','", $ListOrderCode) ."')")
                ->orWhere(function($q) use($ListTrackingCode){
                    $q->whereRaw("tracking_code in ('". implode("','", $ListTrackingCode) ."')");
                });
        });

        if(!empty($ReturnItem)){
            $PackedItem = $PackedItem->whereRaw("uid not in ('". implode("','", $ReturnItem) ."')");
        }

        if(!empty($TimePackedStart)){
            $TimePackedStart = $this->__convert_time($TimePackedStart);
            $PackedItem = $PackedItem->where('time_packge','>=',$TimePackedStart);
        }

        if(!empty($TimePackedEnd)){
            $TimePackedEnd = $this->__convert_time($TimePackedEnd);
            $PackedItem = $PackedItem->where('time_packge','<=',$TimePackedEnd);
        }

        if(!empty($PipeStatus) && !empty($Group)){
            $PipeStatus = explode(',',$PipeStatus);
            $ListId = \omsmodel\PipeJourneyModel::where('time_create', '>=', $TimeAcceptStart)->where('type', $TypeProcess)
                        ->where('group_process',$Group)->whereIn('pipe_status', $PipeStatus)->lists('tracking_code');
            if(!empty($ListId)){
                $ListId = array_unique($ListId);
                $PackedItem  = $PackedItem->whereRaw("id in (". implode(",", $ListId) .")");
            }else{
                return $this->ResponseData();
            }
        }

        $GroupStatus    = $PackedItem->groupBy('warehouse')->get(array('warehouse',DB::raw('count(*) as count')))->toArray();

        $this->data = ['ALL' => 0];

        if(!empty($GroupStatus)){
            foreach($GroupStatus as $val){
                $val['warehouse']               = strtoupper(trim($val['warehouse']));
                $this->data[$val['warehouse']]  = $val['count'];
                $this->data['ALL']              += $val['count'];
            }
        }

        return $this->ResponseData();
    }

    public function getPackageSlow(){
        $itemPage           = 20;
        $this->error        = false;
        $this->message      = 'success';

        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;

        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')           : 1;
        $PipeStatus         = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))           : '';

        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')           : 0;

        $TimePackedStart    = Input::has('packed_start')        ? (int)Input::get('packed_start')           : 0;
        $TimePackedEnd      = Input::has('packed_end')          ? (int)Input::get('packed_end')             : 0;

        $WareHouse          = Input::has('warehouse')           ? Input::get('warehouse')                   : "";
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';
        $Location           = Input::has('location')            ? (int)Input::get('location')               : 0;
        $OrderModel         = $this->getModel();

        if($this->error){
            $this->error    = false;
            return $this->ResponseData();
        }

        $Hour   = date('H');

        if($Location == 1){
            //nếu là nội thành tạo trước 10h, cập nhật đóng gói trước 12h
            if($Hour <= 12){
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 10:00:00')) - 86400));
            }else{
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 10:00:00'))));
            }
        }elseif($Location == 4){
            //tất cả => tạo trước 17h, đóng gói trong ngày
            if($Hour <= 19){
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 17:00:00')) - 86400));
            }else{
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 17:00:00'))));
            }
        }

        if(!empty($WareHouse)){
            $OrderModel     = $OrderModel->where('warehouse',$WareHouse);
        }

        $Model      = clone $OrderModel;

        $ListTrackingCode      = $OrderModel->lists('tracking_code');
        if(empty($ListTrackingCode)){
            $this->error    = false;
            return $this->ResponseData();
        }

        $PackedItem = \warehousemodel\PackageItemModel::whereRaw("tracking_code in ('". implode("','", $ListTrackingCode) ."')");

        if(!empty($TimePackedStart)){
            $TimePackedStart = $this->__convert_time($TimePackedStart);
            $PackedItem = $PackedItem->where('time_packge','>=',$TimePackedStart);
        }

        if(!empty($TimePackedEnd)){
            $TimePackedEnd = $this->__convert_time($TimePackedEnd);
            $PackedItem = $PackedItem->where('time_packge','<=',$TimePackedEnd);
        }

        $ListPacked = $PackedItem->groupBy('tracking_code')->lists('tracking_code');
        if(!empty($ListPacked)){
            $Model  = $Model->whereNotIn('tracking_code', $ListPacked);
        }

        if(!empty($PipeStatus) && !empty($Group)){
            $PipeStatus = explode(',',$PipeStatus);

            $ListId = \omsmodel\PipeJourneyModel::where('time_create', '>=', $TimeAcceptStart)->where('type', $TypeProcess)
                ->where('group_process',$Group)->whereIn('pipe_status', $PipeStatus)->lists('tracking_code');
            if(!empty($ListId)){
                $ListId = array_unique($ListId);
                $Model  = $Model->whereRaw("id in (". implode(",", $ListId) .")");
            }else{
                return $this->ResponseData();
            }
        }

        if($Cmd == 'export'){
            $this->data         = $Model->orderBy('time_accept','ASC')
                                        ->get(['id','tracking_code', 'status','warehouse','product_name','total_quantity','time_accept'])->toArray();
            return $this->ResponseData();
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0) {
            $offset     = ($page - 1) * $itemPage;
            $Model      = $Model->skip($offset)->take($itemPage);

            if(!empty($Group)){
                $Model  = $Model->with(['pipe_journey' => function($query) use($Group,$TypeProcess){
                    $query->where('type', $TypeProcess)->where('group_process', $Group)->orderBy('time_create', 'ASC');
                }]);
            }
            $Data = $Model->orderBy('time_accept','ASC')->get(['id','tracking_code', 'status','warehouse','product_name','total_quantity','time_accept'])->toArray();

            foreach($Data as $key => $val){
                $Data[$key]['pipe_status'] = 0;

                if(!empty($val['pipe_journey'])){
                    foreach($val['pipe_journey'] as $v){
                        $Data[$key]['pipe_status'] = (int)$v['pipe_status'];
                    }
                }
            }

            $this->data = $Data;
        }

        return $this->ResponseData();
    }

    public function getCountGroupPackageSlow(){
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')           : 1;
        $PipeStatus         = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))           : '';

        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')           : 0;

        $TimePackedStart    = Input::has('packed_start')        ? (int)Input::get('packed_start')           : 0;
        $TimePackedEnd      = Input::has('packed_end')          ? (int)Input::get('packed_end')             : 0;
        $Location           = Input::has('location')            ? (int)Input::get('location')               : 0;
        $OrderModel         = $this->getModel();

        if($this->error){
            $this->error    = false;
            return $this->ResponseData();
        }

        $Hour   = date('H');

        if($Location == 1){
            //nếu là nội thành tạo trước 10h, cập nhật đóng gói trước 12h
            if($Hour <= 12){
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 10:00:00')) - 86400));
            }else{
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 10:00:00'))));
            }
        }elseif($Location == 4){
            //tất cả => tạo trước 17h, đóng gói trong ngày
            if($Hour <= 19){
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 17:00:00')) - 86400));
            }else{
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 17:00:00'))));
            }
        }

        $Model      = clone $OrderModel;

        $ListTrackingCode      = $OrderModel->lists('tracking_code');
        if(empty($ListTrackingCode)){
            $this->error    = false;
            return $this->ResponseData();
        }

        $PackedItem = \warehousemodel\PackageItemModel::whereRaw("tracking_code in ('". implode("','", $ListTrackingCode) ."')");

        if(!empty($TimePackedStart)){
            $TimePackedStart = $this->__convert_time($TimePackedStart);
            $PackedItem = $PackedItem->where('time_packge','>=',$TimePackedStart);
        }

        if(!empty($TimePackedEnd)){
            $TimePackedEnd = $this->__convert_time($TimePackedEnd);
            $PackedItem = $PackedItem->where('time_packge','<=',$TimePackedEnd);
        }

        $ListPacked = $PackedItem->groupBy('tracking_code')->lists('tracking_code');
        if(!empty($ListPacked)){
            $Model  = $Model->whereNotIn('tracking_code', $ListPacked);
        }

        if(!empty($PipeStatus) && !empty($Group)){
            $PipeStatus = explode(',',$PipeStatus);

            $ListId = \omsmodel\PipeJourneyModel::where('time_create', '>=', $TimeAcceptStart)->where('type', $TypeProcess)
                ->where('group_process',$Group)->whereIn('pipe_status', $PipeStatus)->lists('tracking_code');
            if(!empty($ListId)){
                $ListId = array_unique($ListId);
                $Model  = $Model->whereRaw("id in (". implode(",", $ListId) .")");
            }else{
                return $this->ResponseData();
            }
        }

        $GroupStatus    = $Model->groupBy('warehouse')->get(array('warehouse',DB::raw('count(*) as count')))->toArray();

        $this->data = ['ALL' => 0];

        if(!empty($GroupStatus)){
            foreach($GroupStatus as $val){
                $val['warehouse']   = strtoupper(trim($val['warehouse']));
                if(!isset($this->data[$val['warehouse']])){
                    $this->data[$val['warehouse']] = 0;
                }
                $this->data[$val['warehouse']] += $val['count'];
                $this->data['ALL']                += $val['count'];
            }
        }

        return $this->ResponseData();
    }

    public function getPickupSlow(){
        $itemPage           = 20;
        $this->error        = false;
        $this->message      = 'success';

        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;

        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')           : 1;
        $PipeStatus         = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))           : '';

        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')           : 0;
        $WareHouse          = Input::has('warehouse')           ? Input::get('warehouse')                   : "";
        $Location           = Input::has('location')            ? (int)Input::get('location')               : 0;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';
        $OrderModel         = $this->getModel();

        if($this->error){
            $this->error    = false;
            return $this->ResponseData();
        }

        $Hour   = date('H');

        if($Location == 1){
            //nếu là nội thành tạo trước 10h, cập nhật đóng gói trước 12h
            if($Hour <= 12){
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 10:00:00')) - 86400));
            }else{
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 10:00:00'))));
            }
        }elseif($Location == 4){
            //tất cả => tạo trước 17h, đóng gói trong ngày
            if($Hour <= 19){
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 17:00:00')) - 86400));
            }else{
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 17:00:00'))));
            }
        }

        if(!empty($WareHouse)){
            $OrderModel          = $OrderModel->where('warehouse',$WareHouse);
        }

        if(!empty($PipeStatus) && !empty($Group)){
            $PipeStatus = explode(',',$PipeStatus);

            $ListId = \omsmodel\PipeJourneyModel::where('time_create', '>=', $TimeAcceptStart)->where('type', $TypeProcess)
                ->where('group_process',$Group)->whereIn('pipe_status', $PipeStatus)->lists('tracking_code');
            if(!empty($ListId)){
                $ListId = array_unique($ListId);
                $OrderModel  = $OrderModel->whereRaw("id in (". implode(",", $ListId) .")");
            }else{
                return $this->ResponseData();
            }
        }

        if($Cmd == 'export'){
            $this->data         = $OrderModel->orderBy('time_accept','ASC')->get(['id','tracking_code', 'status','warehouse','product_name','total_quantity','time_accept'])->toArray();
            return $this->ResponseData();
        }

        $TotalModel     = clone $OrderModel;
        $this->total    = $TotalModel->count();

        if($this->total > 0) {
            $offset = ($page - 1) * $itemPage;
            $OrderModel = $OrderModel->skip($offset)->take($itemPage);

            if(!empty($Group)){
                $OrderModel  = $OrderModel->with(['pipe_journey' => function($query) use($Group,$TypeProcess){
                    $query->where('type', $TypeProcess)->where('group_process', $Group)->orderBy('time_create', 'ASC');
                }]);
            }
            $Data = $OrderModel->orderBy('time_accept','ASC')->get(['id','tracking_code', 'status','warehouse','product_name','total_quantity','time_accept'])->toArray();

            foreach($Data as $key => $val){
                $Data[$key]['pipe_status'] = 0;
                if(!empty($val['pipe_journey'])){
                    foreach($val['pipe_journey'] as $v){
                        $Data[$key]['pipe_status'] = (int)$v['pipe_status'];
                    }
                }
            }

            $this->data = $Data;
        }
        return $this->ResponseData();
    }

    public function getCountGroupPickupSlow(){
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')           : 1;
        $PipeStatus         = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))           : '';

        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')           : 0;

        $TimePackedStart    = Input::has('packed_start')        ? (int)Input::get('packed_start')           : 0;
        $TimePackedEnd      = Input::has('packed_end')          ? (int)Input::get('packed_end')             : 0;
        $Location           = Input::has('location')            ? (int)Input::get('location')               : 0;
        $OrderModel         = $this->getModel();

        if($this->error){
            $this->error    = false;
            return $this->ResponseData();
        }

        $Hour   = date('H');

        if($Location == 1){
            //nếu là nội thành tạo trước 10h, cập nhật đóng gói trước 12h
            if($Hour <= 12){
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 10:00:00')) - 86400));
            }else{
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 10:00:00'))));
            }
        }elseif($Location == 4){
            //tất cả => tạo trước 17h, đóng gói trong ngày
            if($Hour <= 19){
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 17:00:00')) - 86400));
            }else{
                $OrderModel = $OrderModel->where('time_accept', '<=', (strtotime(date('Y-m-d 17:00:00'))));
            }
        }

        $Model      = clone $OrderModel;
        if(!empty($PipeStatus) && !empty($Group)){
            $PipeStatus = explode(',',$PipeStatus);

            $ListId = \omsmodel\PipeJourneyModel::where('time_create', '>=', $TimeAcceptStart)->where('type', $TypeProcess)
                ->where('group_process',$Group)->whereIn('pipe_status', $PipeStatus)->lists('tracking_code');
            if(!empty($ListId)){
                $ListId = array_unique($ListId);
                $Model  = $Model->whereRaw("id in (". implode(",", $ListId) .")");
            }else{
                return $this->ResponseData();
            }
        }

        $GroupStatus    = $Model->groupBy('warehouse')->get(array('warehouse',DB::raw('count(*) as count')))->toArray();

        $this->data = ['ALL' => 0];

        if(!empty($GroupStatus)){
            foreach($GroupStatus as $val){
                $val['warehouse']               = strtoupper(trim($val['warehouse']));
                $this->data[$val['warehouse']]  = $val['count'];
                $this->data['ALL']             += $val['count'];
            }
        }

        return $this->ResponseData();
    }

    /*
     *
     *
     * SHIPMENT
     *
     */

    private function getModelShipment(){
        $CreatedStart   = Input::has('create_start')        ? (int)Input::get('create_start')               : 0;
        $CreatedEnd     = Input::has('create_end')         ? (int)Input::get('create_end')                  : 0;
        $AcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')               : 0;
        $AcceptdEnd     = Input::has('accept_end')          ? (int)Input::get('accept_end')                 : 0;

        $KeyWord        = Input::has('keyword')             ? strtolower(trim(Input::get('keyword')))       : '';
        $Shipment       = Input::has('shipment_code')       ? strtoupper(trim(Input::get('shipment_code'))) : '';

        $Group          = Input::has('group')               ? (int)Input::get('group')                      : 0;
        $TypeProcess    = Input::has('type_process')        ? (int)Input::get('type_process')               : 0;
        $PipeStatus     = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))               : '';
        $ListStatus     = Input::has('list_status')         ? trim(Input::get('list_status'))               : '';


        if(empty($Group) || empty($TypeProcess)){
            return false;
        }

        $Model          = new \fulfillmentmodel\ShipMentModel;

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
            $Model          = $Model->where('approved','>=',$AcceptStart);
        }

        if(!empty($AcceptdEnd)){
            $AcceptdEnd      = $this->__convert_time($AcceptdEnd);
            $Model          = $Model->where('approved','<=',$AcceptdEnd);
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

        if(!empty($ListStatus)){
            if($ListStatus == '9'){
                $Model          = $Model->where('deleted',1);
            }else{
                $ListStatus = explode(',', $ListStatus);
                $Model      = $Model->whereIn('status',$ListStatus);
            }
        }

        if(!empty($PipeStatus)){
            $PipeStatus = explode(',',$PipeStatus);
            $ListId = \omsmodel\PipeJourneyModel::where('type', $TypeProcess)->where('group_process',$Group)->whereIn('pipe_status', $PipeStatus)->lists('tracking_code');

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

    private function get_list_empty_time_stock($type = 1){
        $CreatedStart   = Input::has('create_start')        ? (int)Input::get('create_start')               : 0;
        $CreatedEnd     = Input::has('create_end')          ? (int)Input::get('create_end')                 : 0;
        $KeyWord        = Input::has('keyword')             ? strtolower(trim(Input::get('keyword')))       : '';

        $Model          = \fulfillmentmodel\SellerProductItemModel::where('update_stocked',NULL)->where('shipment','<>','NULL');

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

        if($type == 1){
            return $Model->groupBy('shipment')->get(['shipment',DB::raw('count(*) as total')])->toArray();
        }else{
            return $Model->groupBy('shipment')->lists('shipment');
        }

    }

    private function ExportMissing($ListId){
        $Model          = \fulfillmentmodel\SellerProductItemModel::whereRaw("shipment in (". implode(",", $ListId) .")");

        $Model->with(['__product','__get_user','__shipment','__putaway'])->orderBy('update_stocked','ASC')->chunk('1000', function($query) use(&$Data){
            foreach($query as $val){
                $val                = $val->toArray();
                $Data[]             = $val;
            }
        });

        $this->data = $Data;

        return $this->ResponseData();
    }

    public function getShipmentLost(){
        $itemPage           = 20;
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Cmd                = Input::has('cmd')                 ? strtoupper(trim(Input::get('cmd')))           : '';
        $WareHouse          = Input::has('warehouse')           ? strtoupper(trim(Input::get('warehouse')))     : '';
        $Group              = Input::has('group')               ? (int)Input::get('group')                      : 5;
        $TypeProcess        = Input::has('type_process')        ? (int)Input::get('type_process')               : 13;

        $Model          = $this->getModelShipment();
        if(!$Model){
            return $this->ResponseData();
        }
        $Model  = $Model->where('deleted',0);

        //Get list Shipment empty time_stock
        $ListShipment    =  $this->get_list_empty_time_stock();
        if(empty($ListShipment)){
            return $this->ResponseData();
        }

        $ListShipmentId = [];
        $Shipment       = [];
        foreach($ListShipment as $val){
            $ListShipmentId[]   = (int)$val['shipment'];
            $Shipment[(int)$val['shipment']]    = (int)$val['total'];
        }

        $Model  = $Model->whereRaw("id in (". implode(",", $ListShipmentId) .")");

        if(!empty($WareHouse)){
            $Model  = $Model->where('warehouse', $WareHouse);
        }

        if($Cmd == 'EXPORT'){
            $ListShipmentId = $Model->lists('id');
            return $this->ExportMissing($ListShipmentId);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model      = $Model->skip($offset)->take($itemPage);
            $this->data       = $Model->with(['__get_user','__get_outbound','pipe_journey' => function($query) use($TypeProcess, $Group){
                $query->where('type',$TypeProcess)->where('group_process',$Group);
            },'__get_shipment_product'])->orderBy('created','ASC')->get()->toArray();

            foreach($this->data as $key => $val){
                //total missing
                if(isset($Shipment[$val['id']])){
                    $this->data[$key]['total_missing']  = (int)$Shipment[$val['id']];
                }

                // pipe journey
                $this->data[$key]['pipe_status']    = 0;
                foreach($val['pipe_journey'] as $v){
                    $this->data[$key]['pipe_status'] = (int)$v['pipe_status'];
                }
            }
        }

        return $this->ResponseData();
    }

    public function getCountGroupShipmentLost(){
        $ListShipmentId    =  $this->get_list_empty_time_stock(2);
        if(empty($ListShipmentId)){
            return $this->ResponseData();
        }

        $Model          = $this->getModelShipment();
        if(!$Model){
            return $this->ResponseData();
        }
        $Model  = $Model->where('deleted',0);

        $Model  = $Model->whereIn('id', $ListShipmentId);

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


    /**
     *
     *
     * Package Error
     *
     *
     */
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


        $Model          = new \warehousemodel\PackageItemModel;
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
            $ListPackage    = \warehousemodel\PackageModel::where('create','>=',$CreateStart)->where('status',$PackageStatus)->lists('id');
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

            $ListUId    = \fulfillmentmodel\SellerProductItemModel::where('created','>=', $Time);
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
                $query->with(['__get_user']);
            }])
            ->select(['warehouse','package','package_code','pickup_code','tracking_code','sku','order_number','uid','create',DB::raw('count(*) as total')])
            ->chunk('1000', function($query) use(&$Data, &$WareHouse){
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
