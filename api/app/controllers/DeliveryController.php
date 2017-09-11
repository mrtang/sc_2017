<?php
use ordermodel\OrdersModel;
use metadatamodel\OrderStatusModel;
use metadatamodel\GroupOrderStatusModel;
class DeliveryController extends \BaseController {
    private $domain = '*';

    public function __construct(){
        
    }

    public function getIndex() {return 1;
        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege']==0) {
            $response = [
                'error' =>  true,
                'message'   =>  'Không có quyền'
            ];
            return Response::json($response);
        }
        $currentPage       = Input::has('currentPage')   ? (int)Input::get('currentPage')                : 1;
        $itemPage   = Input::has('item_page')  ? Input::get('item_page')                    : 20;
        $scCode     = Input::has('sc_code')  ? Input::get('sc_code')                    : '';
        $offset     = ($currentPage - 1)*$itemPage;
        $fromDate     = (Input::has('time_start') && (int)Input::get('time_start')>0)  ? Input::get('time_start')                    : 0;
        $toDate     = (Input::has('time_end') && (int)Input::get('time_end')>0)  ? Input::get('time_end')                    : 0;
        $courier_id    = Input::has('courier_id')   ? (int)Input::get('courier_id')                : 0;
        $TimeStart  = $this->time() - 90*86400;


        $OrderModel = OrdersModel::query();
        $OrderModel = $OrderModel->where('time_create','>=',$this->time()-90*86400);
        //$status = GroupOrderStatusModel::whereIn('group_status',array(17,18,19,20,21))->lists('order_status_code');
        //$infoStatus = OrderStatusModel::whereIn('code',$status)->get()->toArray();
        

        //$OrderModel->where('time_create','>',$timeEnd);
        /*if(!empty($status)) {
            $OrderModel->whereIn('status',$status);
        };
        */
        if(!empty($scCode)) {
            $OrderModel->where('tracking_code',$scCode);
        }
        if($courier_id > 0){
            $OrderModel->where('courier_id',$courier_id);
        }
        if($fromDate > 0){
            $OrderModel->where('time_pickup','>=',$fromDate);
        }else{
            $OrderModel->where('time_pickup','>=',$TimeStart);
        }
        if($toDate > 0){
            $OrderModel->where('time_pickup','<',$toDate);
        }

        if(!empty($UserInfo['courier'])){
            if($UserInfo['courier'] == 'ems'){
                $OrderModel->where('courier_id',8);
            }elseif($UserInfo['courier'] == 'emshn'){
                $OrderModel->where('courier_id',8)->where('from_city_id',18);
            }elseif($UserInfo['courier'] == 'emshcm'){
                $OrderModel->where('courier_id',8)->where('from_city_id',52);
            }elseif($UserInfo['courier'] == 'gts'){
                $OrderModel->where('courier_id',9);
            }
        }
        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $OrderModel->where('domain','lamido.vn');
        }
        //chodientu
        if($UserInfo['id'] == 2956 && $UserInfo['privilege'] == 3){
            $OrderModel->where('domain','chodientu.vn');
        }

        $total      = $OrderModel->count();

        $field = array('tracking_code','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create','time_pickup','time_success','estimate_delivery');
        $OrderModel->skip($offset)->take($itemPage);
        $listData   = $OrderModel->get($field);
        if(!$listData->isEmpty()) {
            if (Cache::has('courier_cache')){
                $listCourier    = Cache::get('courier_cache');
            }else{
                $courier        = new CourierModel;
                $listCourier    = $courier::all(array('id','name'));
            }
            if(!empty($listCourier)){
                foreach($listCourier as $val){
                    $LCourier[$val['id']]   = $val['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCourier[(int)$val['courier_id']])){
                        $val->courier_name = $LCourier[(int)$val['courier_id']];
                    }
                }
            }


            $listStatus = OrderStatusModel::all(array('code','name'));
            if($listStatus){
                foreach($listStatus AS $one){
                    $LStatus[(int)$one['code']] = $one['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LStatus[(int)$val['status']])){
                        $val->status_name = $LStatus[(int)$val['status']];
                    }
                }
            }
            $listCity = CityModel::all(array('city_name','id'));
            if(!empty($listCity)){
                foreach($listCity AS $one){
                    $LCity[$one['id']] = $one['city_name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCity[(int)$val['from_city_id']])){
                        $val->city_name = $LCity[(int)$val['from_city_id']];
                    }
                }
            }
            $listDistrict = DistrictModel::all(array('district_name','id'));
            if(!empty($listDistrict)){
                foreach($listDistrict AS $one){
                    $LDistrict[$one['id']] = $one['district_name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LDistrict[(int)$val['from_district_id']])){
                        $val->district_name = $LDistrict[(int)$val['from_district_id']];
                    }
                }
            }
            $response = array(
                'error'     => false,
                'message'   => 'success',
                'total'     =>  $total,
                'data'      => $listData,
                //'status'    => $infoStatus,
                'item_page'     => $itemPage
            );
            return Response::json($response, 200, array('Access-Control-Allow-Origin' => $this->domain));
        } else {
            $response = array(
                'error'     => true,
                'message'   => 'Không có vận đơn!',
                'data'      => $listData,
                'total'     => 0
            );

            return Response::json($response, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }

    public function getExportexceldelivery() {return 1;
        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege']==0) {
            $response = [
                'error' =>  true,
                'message'   =>  'Không có quyền'
            ];
            return Response::json($response);
        }
        $fromDate     = (Input::has('time_start') && (int)Input::get('time_start')>0)  ? Input::get('time_start')                    : 0;
        $toDate     = (Input::has('time_end') && (int)Input::get('time_end')>0)  ? Input::get('time_end')                    : 0;
        $courier_id    = Input::has('courier')   ? (int)Input::get('courier')                : 0;

        $OrderModel = OrdersModel::query();

        $OrderModel = $OrderModel->where('time_create','>=',$this->time()-90*86400);
        /*$status = GroupOrderStatusModel::whereIn('group_status',array(17,18,19,20,21))->lists('order_status_code');

        if(!empty($status)) {
            $OrderModel->whereIn('status',$status);
        }*/
        if($courier_id > 0){
            $OrderModel->where('courier_id',$courier_id);
        }
        if($fromDate > 0){
            $OrderModel->where('time_pickup','>',$fromDate);
        }
        if($toDate > 0){
            $OrderModel->where('time_pickup','<',$toDate);
        }
        if(!empty($UserInfo['courier'])){
            if($UserInfo['courier'] == 'ems'){
                $OrderModel->where('courier_id',8);
            }elseif($UserInfo['courier'] == 'emshn'){
                $OrderModel->where('courier_id',8)->where('from_city_id',18);
            }elseif($UserInfo['courier'] == 'emshcm'){
                $OrderModel->where('courier_id',8)->where('from_city_id',52);
            }elseif($UserInfo['courier'] == 'gts'){
                $OrderModel->where('courier_id',9);
            }
        }
        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $OrderModel->where('domain','lamido.vn');
        }
        //chodientu
        if($UserInfo['id'] == 2956 && $UserInfo['privilege'] == 3){
            $OrderModel->where('domain','chodientu.vn');
        }

        $field = array('tracking_code','from_user_id','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create','time_pickup','time_success','estimate_delivery');
        $listData   = $OrderModel->get($field);
        if(!$listData->isEmpty()) {
            $listUserId = array();
            foreach($listData AS $one){
                $listUserId[] = $one['from_user_id'];
            }
            if (Cache::has('courier_cache')){
                $listCourier    = Cache::get('courier_cache');
            }else{
                $courier        = new CourierModel;
                $listCourier    = $courier::all(array('id','name'));
            }
            if(!empty($listCourier)){
                foreach($listCourier as $val){
                    $LCourier[$val['id']]   = $val['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCourier[(int)$val['courier_id']])){
                        $val->courier_name = $LCourier[(int)$val['courier_id']];
                    }
                }
            }

            $listStatus = OrderStatusModel::all(array('code','name'));
            if($listStatus){
                foreach($listStatus AS $one){
                    $LStatus[(int)$one['code']] = $one['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LStatus[(int)$val['status']])){
                        $val->status_name = $LStatus[(int)$val['status']];
                    }
                }
            }
            $listCity = CityModel::all(array('city_name','id'));
            if(!empty($listCity)){
                foreach($listCity AS $one){
                    $LCity[$one['id']] = $one['city_name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCity[(int)$val['from_city_id']])){
                        $val->city_name = $LCity[(int)$val['from_city_id']];
                    }
                }
            }
            $listDistrict = DistrictModel::all(array('district_name','id'));
            if(!empty($listDistrict)){
                foreach($listDistrict AS $one){
                    $LDistrict[$one['id']] = $one['district_name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LDistrict[(int)$val['from_district_id']])){
                        $val->district_name = $LDistrict[(int)$val['from_district_id']];
                    }
                }
            }
            if(!empty($listUserId)){
                $listUser = User::whereIn('id',$listUserId)->get(array('id','fullname','phone'));
                if(!empty($listUser)){
                    foreach($listUser AS $one){
                        $LUser[$one['id']] = $one;
                    }
                    foreach($listData as $key => $val){
                        if (isset($LUser[(int)$val['from_user_id']])){
                            $val->from_name = $LUser[(int)$val['from_user_id']]['fullname'];
                            $val->from_phone = $LUser[(int)$val['from_user_id']]['phone'];
                        }
                    }
                }
            }
            //xuat du lieu ra excel
            return Excel::create('Van_don', function ($excel) use($listData) {
                $excel->sheet('Vận đơn', function ($sheet) use($listData) {
                    $sheet->mergeCells('C1:F1');
                    $sheet->row(1, function ($row) {
                        $row->setFontSize(20);
                    });
                    $sheet->row(1, array('','','Vận đơn'));
                    // set width column
                    $sheet->setWidth(array(
                        'A'     => 5,
                        'B'     => 20,
                        'C'     => 25,
                        'D'     => 15,
                        'E'     => 40,
                        'F'     => 30,
                        'G'     => 20,
                        'H'     => 30,
                        'I'     => 30,
                        'J'     => 30,
                        'K'     => 30,
                        'L'     => 30,
                        'M'     => 30
                    ));
                    // set content row
                    $sheet->row(3, array(
                        'STT', 'Mã vận đơn', 'Mã hãng vận chuyển', 'Hãng vận chuyển','Người gửi','Số điện thoại', 'Tỉnh Thành gửi', 'Quận huyện gửi', 'Địa chỉ gửi', 'Trạng thái', 'Thời gian tạo', 'Thời gian duyệt','Thời gian lấy hàng'
                    ));
                    $sheet->row(3,function($row){
                        $row->setBackground('#B6B8BA');
                        $row->setBorder('solid','solid','solid','solid');
                        $row->setFontSize(12);
                    });
                    //
                    $i = 1;
                    foreach ($listData AS $value) {
                        $dataExport = array(
                            'STT' => $i++,
                            'Mã vận đơn' => $value['tracking_code'],
                            'Mã hãng vận chuyển' => $value['courier_tracking_code'],
                            'Hãng vận chuyển' => $value['courier_name'],
                            'Người gửi' => $value['from_name'],
                            'Số điện thoại' => $value['from_phone'],
                            'Tỉnh Thành gửi'  => $value['city_name'],
                            'Quận huyện gửi' => $value['district_name'],
                            'Địa chỉ gửi'    => $value['from_address'],
                            'Trạng thái' => $value['status_name'],
                            'Thời gian tạo' => date("d/M/y H:m",$value['time_create']),
                            'Thời gian duyệt' => date("d/M/y H:m",$value['time_accept']),
                            'Thời gian lấy hàng' => date("d/M/y H:m",$value['time_pickup'])
                        );
                        $sheet->appendRow($dataExport);
                    }
                });
            })->export('xls');
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'error',
                'data'      => ''
            );
            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }

    public function getDeliverylate(){
        $UserInfo   = $this->UserInfo();

        $page       = Input::has('page')   ? (int)Input::get('page')                : 1;
        $itemPage   = Input::has('limit')  ? Input::get('limit')                    : 20;
        $scCode     = Input::has('sc_code')  ? Input::get('sc_code')                    : '';
        $domain     = Input::has('domain')  ? Input::get('domain')                    : '';
        $offset     = ($page - 1)*$itemPage;
        $fromDate     = Input::has('time_start')  ? Input::get('time_start')                    : 0;
        $toDate     = Input::has('time_end')  ? Input::get('time_end')                    : 0;
        $courier    = Input::has('courier')   ? (int)Input::get('courier')                : 0;
        $timeLate   = Input::has('time_late')   ? (int)Input::get('time_late')                : 3;

        $Model          = new OrdersModel;
        $Model = $Model->where('time_create','>=',$this->time()-90*86400);
        $listStatus = GroupOrderStatusModel::where('group_status',6)->lists('order_status_code');
        $Model = $Model->whereIn('status',$listStatus)->where('time_pickup','>',0)->where('time_success',0);

        if(!empty($scCode)){
            $Model = $Model->where('tracking_code',$scCode);
        }
        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }
        if($fromDate > 0){
            $Model = $Model->where('time_pickup','>',$fromDate);
        }
        if($toDate > 0){
            $Model = $Model->where('time_pickup','<',$toDate);
        }
        if(!empty($domain)){
            $Model = $Model->where('domain',$domain);
        } 

        if(!empty($UserInfo['courier'])){
            if($UserInfo['courier'] == 'ems'){
                $Model = $Model->where('courier_id',8);
            }elseif($UserInfo['courier'] == 'emshn'){
                $Model = $Model->where('courier_id',8)->where('from_city_id',18);
            }elseif($UserInfo['courier'] == 'emshcm'){
                $Model = $Model->where('courier_id',8)->where('from_city_id',52);
            }elseif($UserInfo['courier'] == 'gts'){
                $Model = $Model->where('courier_id',9);
            }
        }
        /*if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','lamido.vn');
        }*/
        //chodientu
        if($UserInfo['id'] == 2956 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','chodientu.vn');
        }
        $timeDeleveryEstimate = $this->time() - ((int)$timeLate + 3)*86400;

        $field = array('tracking_code','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create','time_pickup','time_success','estimate_delivery', 'to_address_id');
        $Model = $Model->where('time_pickup','<=',$timeDeleveryEstimate)->where('time_success',0)->with(['ToOrderAddress']);

        $total      = $Model->count();
        $Model = $Model->skip($offset)->take($itemPage);
        $Model = $Model->with([
            'Service'
        ]);
        $listData   = $Model->get($field);

        if(!empty($listData)){
            if (Cache::has('courier_cache')){
                $listCourier    = Cache::get('courier_cache');
            }else{
                $courier        = new CourierModel;
                $listCourier    = $courier::all(array('id','name'));
            }
            if(!empty($listCourier)){
                foreach($listCourier as $val){
                    $LCourier[$val['id']]   = $val['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCourier[(int)$val['courier_id']])){
                        $val->courier_name = $LCourier[(int)$val['courier_id']];
                    }
                }
            }
            $listStatus = OrderStatusModel::all(array('code','name'));
            if($listStatus){
                foreach($listStatus AS $one){
                    $LStatus[(int)$one['code']] = $one['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LStatus[(int)$val['status']])){
                        $val->status_name = $LStatus[(int)$val['status']];
                    }
                }
            }
            $listCity = CityModel::all(array('city_name','id'));
            if(!empty($listCity)){
                foreach($listCity AS $one){
                    $LCity[$one['id']] = $one['city_name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCity[(int)$val['from_city_id']])){
                        $val->city_name = $LCity[(int)$val['from_city_id']];
                    }
                }
            }
            $listDistrict = DistrictModel::all(array('district_name','id'));
            if(!empty($listDistrict)){
                foreach($listDistrict AS $one){
                    $LDistrict[$one['id']] = $one['district_name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LDistrict[(int)$val['from_district_id']])){
                        $val->district_name = $LDistrict[(int)$val['from_district_id']];
                    }
                }
            }
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'total'     =>  $total,
                'data'      => $listData,
                'item_page'     => $itemPage
            );

            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Không có vận đơn!',
                'data'      => $listData,
                'total'     => 0
            );
                    
            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }

    //xuat excel giao hang cham
    public function getExportexceldeliverylate(){
        $UserInfo   = $this->UserInfo();

        $scCode     = Input::has('sc_code')  ? Input::get('sc_code')                    : '';
        $domain     = Input::has('domain')  ? Input::get('domain')                    : '';
        $fromDate     = (Input::has('time_start') && Input::get('time_start')!="")  ? strtotime(Input::get('time_start')." 00:00:00")                    : 0;
        $toDate     = (Input::has('time_end') && Input::get('time_end')!="")  ? strtotime(Input::get('time_end')." 23:59:59")                    : 0;
        $courier    = Input::has('courier')   ? (int)Input::get('courier')                : 0;
        $timeLate   = Input::has('time_late')   ? (int)Input::get('time_late')                : 3;

        $Model          = new OrdersModel;
        $Model = $Model->where('time_create','>=',$this->time()-90*86400);
        $listStatus = GroupOrderStatusModel::where('group_status',6)->lists('order_status_code');
        $Model = $Model->whereIn('status',$listStatus)->where('time_pickup','>',0)->where('time_success',0);
        if(!empty($scCode)){
            $Model = $Model->where('tracking_code',$scCode);
        }
        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }
        if($fromDate > 0){
            $Model = $Model->where('time_pickup','>',$fromDate);
        }
        if($toDate > 0){
            $Model = $Model->where('time_pickup','<',$toDate);
        }
        if(!empty($domain)){
            $Model = $Model->where('domain',$domain);
        }

        if(!empty($UserInfo['courier'])){
            if($UserInfo['courier'] == 'ems'){
                $Model = $Model->where('courier_id',8);
            }elseif($UserInfo['courier'] == 'emshn'){
                $Model = $Model->where('courier_id',8)->where('from_city_id',18);
            }elseif($UserInfo['courier'] == 'emshcm'){
                $Model = $Model->where('courier_id',8)->where('from_city_id',52);
            }elseif($UserInfo['courier'] == 'gts'){
                $Model = $Model->where('courier_id',9);
            }
        }
        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','lamido.vn');
        }
        //chodientu
        if($UserInfo['id'] == 2956 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','chodientu.vn');
        }

        $timeDeleveryEstimate = $this->time() - ((int)$timeLate + 3)*86400;
        $field = array('tracking_code','from_user_id','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create','time_pickup','time_success','estimate_delivery','to_address_id','to_name','to_phone');
        $Model = $Model->where('time_pickup','<=',$timeDeleveryEstimate)->where('time_success',0);
        $listData   = $Model->get($field);

        if(!$listData->isEmpty()) {
            $listUserId = array();
            foreach($listData AS $one){
                $listUserId[] = $one['from_user_id'];
            }
            if (Cache::has('courier_cache')){
                $listCourier    = Cache::get('courier_cache');
            }else{
                $courier        = new CourierModel;
                $listCourier    = $courier::all(array('id','name'));
            }
            if(!empty($listCourier)){
                foreach($listCourier as $val){
                    $LCourier[$val['id']]   = $val['name'];
                }
            }
            $listStatus = OrderStatusModel::all(array('code','name'));
            if($listStatus){
                foreach($listStatus AS $one){
                    $LStatus[(int)$one['code']] = $one['name'];
                }
            }
            $listCity = CityModel::all(array('city_name','id'));
            if(!empty($listCity)){
                foreach($listCity AS $one){
                    $LCity[$one['id']] = $one['city_name'];
                }
            }
            $listDistrict = DistrictModel::all(array('district_name','id'));
            if(!empty($listDistrict)){
                foreach($listDistrict AS $one){
                    $LDistrict[$one['id']] = $one['district_name'];
                }
            }


            //set meta info
            $listAddressID = array();
            foreach ($listData as $key => $val) {
                if (isset($LCity[(int)$val['from_city_id']])) {
                    $val->city_name = $LCity[(int)$val['from_city_id']];
                }

                if (isset($LDistrict[(int)$val['from_district_id']])) {
                    $val->district_name = $LDistrict[(int)$val['from_district_id']];
                }

                if (isset($LStatus[(int)$val['status']])) {
                    $val->status_name = $LStatus[(int)$val['status']];
                }
                if (isset($LCourier[(int)$val['courier_id']])) {
                    $val->courier_name = $LCourier[(int)$val['courier_id']];
                }
                $listAddressID[] = $val->to_address_id;
            }
            if(!empty($listAddressID)) {
                $listAddress = \ordermodel\AddressModel::whereIn('id',$listAddressID)->get();
                $LAddress = [];
                if(!$listAddress->isEmpty()) {
                    foreach($listAddress as $k => $val) {
                        $LAddress[$val->id] = $val;
                    }
                }

            }
            if(!empty($listUserId)){
                $listUser = User::whereIn('id',$listUserId)->get(array('id','fullname','phone'));
                if(!empty($listUser)){
                    foreach($listUser AS $one){
                        $LUser[$one['id']] = $one;
                    }
                    foreach($listData as $key => $val){
                        if (isset($LUser[(int)$val['from_user_id']])){
                            $val->from_name = $LUser[(int)$val['from_user_id']]['fullname'];
                            $val->from_phone = $LUser[(int)$val['from_user_id']]['phone'];
                        }
                        if(isset($LAddress[$val->to_address_id])) {
                            $listData[$key]->to_address = $LAddress[$val->to_address_id]->address;
                            if(isset($LCity[$LAddress[$val->to_address_id]->city_id])) {
                                $listData[$key]->to_city = $LCity[$LAddress[$val->to_address_id]->city_id];
                            } else {
                                $listData[$key]->to_city = '';
                            }
                            if(isset($LDistrict[$LAddress[$val->to_address_id]->province_id])) {
                                $listData[$key]->to_district = $LDistrict[$LAddress[$val->to_address_id]->province_id];
                            } else {
                                $listData[$key]->to_district = '';
                            }
                        } else {
                            $listData[$key]->to_address = "";
                        }
                    }
                }
            }

            //xuat du lieu ra excel
            return Excel::create('Van_don_giao_cham', function ($excel) use($listData) {
                $excel->sheet('Vận đơn', function ($sheet) use($listData) {
                    $sheet->mergeCells('C1:F1');
                    $sheet->row(1, function ($row) {
                        $row->setFontSize(20);
                    });
                    $sheet->row(1, array('','','Vận đơn giao hàng chậm'));
                    // set width column
                    $sheet->setWidth(array(
                        'A'     => 5,
                        'B'     => 20,
                        'C'     => 25,
                        'D'     => 15,
                        'E'     => 40,
                        'F'     => 30,
                        'G'     => 20,
                        'H'     => 30,
                        'I'     => 30,
                        'J'     => 30,
                        'K'     => 30,
                        'L'     => 30,
                        'M'     => 30
                    ));
                    // set content row
                    $sheet->row(3, array(
                         'STT', 'Mã vận đơn', 'Mã hãng vận chuyển','Hãng vận chuyển','Người gửi','Số điện thoại', 'Tỉnh thành gửi', 'Quận huyện gửi', 'Địa chỉ gửi',
                        'Người nhận', 'Số điện thoại người nhận', 'Tỉnh Thành nhận', 'Quận huyện nhận', 'Địa chỉ nhận', 'Trạng thái', 'Thời gian tạo', 'Thời gian duyệt','Thời gian lấy hàng'
                    ));
                    $sheet->row(3,function($row){
                        $row->setBackground('#B6B8BA');
                        $row->setBorder('solid','solid','solid','solid');
                        $row->setFontSize(12);
                    });
                    //
                    $i = 1;
                    foreach ($listData AS $value) {
                        $dataExport = array(
                            'STT' => $i++,
                            'Mã vận đơn' => $value['tracking_code'],
                            'Mã hãng vận chuyển' => $value['courier_tracking_code'],
                            'Hãng vận chuyển' => $value['courier_name'],
                            'Người gửi' => $value['from_name'],
                            'Số điện thoại' => $value['from_phone'],
                            'Tỉnh thành gửi'  => $value['city_name'],
                            'Quận huyện gửi' => $value['district_name'],
                            'Địa chỉ gửi'    => $value['from_address'],
                            'Người nhận'      => $value['to_name'],
                            'Số điện thoại người nhận'  =>  $value['to_phone'],
                            'Tỉnh Thành nhận'  => $value['to_city'],
                            'Quận huyện nhận' => $value['to_district'],
                            'Địa chỉ nhận'    => $value['to_address'],
                            'Trạng thái' => $value['status_name'],
                            'Thời gian tạo' => date("d/M/y H:m",$value['time_create']),
                            'Thời gian duyệt' => date("d/M/y H:m",$value['time_accept']),
                            'Thời gian lấy hàng' => date("d/M/y H:m",$value['time_pickup'])
                        );
                        $sheet->appendRow($dataExport);
                    }
                });
            })->export('xls');
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'error',
                'data'      => ''
            );

            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }

    public function getDeliveryfail(){
        $UserInfo   = $this->UserInfo();

        $page       = Input::has('page')   ? (int)Input::get('page')                : 1;
        $itemPage   = Input::has('limit')  ? Input::get('limit')                    : 20;
        $offset     = ($page - 1) * $itemPage;
        $scCode     = Input::has('sc_code')  ? Input::get('sc_code')                : '';
        $courier    = Input::has('courier')   ? (int)Input::get('courier')          : 0;
        $fromDate     = Input::has('time_start')  ? Input::get('time_start')                    : 0;
        $toDate     = Input::has('time_end')  ? Input::get('time_end')                    : 0;

        $statusFailDelivery = GroupOrderStatusModel::where('group_status',7)->lists('order_status_code');
        $timeEnd = $this->time() - 60*86400;

        $Model          = new OrdersModel;
        $Model = $Model->where('time_create','>=',$this->time()-90*86400)->skip($offset)->take($itemPage);
        $Model = $Model->whereIn('status',$statusFailDelivery);
        if(!empty($scCode)){
            $Model = $Model->where('tracking_code',$scCode);
        }
        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }
        if($fromDate > 0){
            $Model = $Model->where('time_pickup','>',$fromDate);
        }
        if($toDate > 0){
            $Model = $Model->where('time_pickup','<',$toDate);
        }

        if(!empty($UserInfo['courier'])){
            if($UserInfo['courier'] == 'ems'){
                $Model = $Model->where('courier_id',8);
            }elseif($UserInfo['courier'] == 'emshn'){
                $Model = $Model->where('courier_id',8)->where('from_city_id',18);
            }elseif($UserInfo['courier'] == 'emshcm'){
                $Model = $Model->where('courier_id',8)->where('from_city_id',52);
            }elseif($UserInfo['courier'] == 'gts'){
                $Model = $Model->where('courier_id',9);
            }
        }
        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','lamido.vn');
        }
        //chodientu
        if($UserInfo['id'] == 2956 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','chodientu.vn');
        }

        $field = array('tracking_code','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create','time_pickup', 'to_address_id');
        $listData   = $Model->where('time_create','>',$timeEnd)->with(['ToOrderAddress'])->get($field);
        $total      = $Model->count();

        if($listData){
            if (Cache::has('courier_cache')){
                $listCourier    = Cache::get('courier_cache');
            }else{
                $courier        = new CourierModel;
                $listCourier    = $courier::all(array('id','name'));
            }
            if(!empty($listCourier)){
                foreach($listCourier as $val){
                    $LCourier[$val['id']]   = $val['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCourier[(int)$val['courier_id']])){
                        $val->courier_name = $LCourier[(int)$val['courier_id']];
                    }
                }
            }
            $listStatus = OrderStatusModel::all(array('name','code'));
            if($listStatus){
                foreach($listStatus AS $one){
                    $LStatus[(int)$one['code']] = $one['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LStatus[(int)$val['status']])){
                        $val->status_name = $LStatus[(int)$val['status']];
                    }
                }
            }
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'total'     =>  $total,
                'item_page'     => $itemPage,
                'data'      => $listData
            );

            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'error',
                'data'      => ''
            );
                    
            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }
    //
    public function getExportexceldeliveryfail(){

        $scCode     = Input::has('sc_code')  ? Input::get('sc_code')                : '';
        $courier    = Input::has('courier')   ? (int)Input::get('courier')          : 0;
        $fromDate     = Input::has('time_start')  ? Input::get('time_start')                    : 0;
        $toDate     = Input::has('time_end')  ? Input::get('time_end')                    : 0;

        $UserInfo   = $this->UserInfo();
        $statusFailDelivery = GroupOrderStatusModel::where('group_status',7)->lists('order_status_code');

        $Model          = new OrdersModel;
        $Model = $Model->where('time_create','>=',$this->time()-90*86400);
        $Model = $Model->whereIn('status',$statusFailDelivery);
        $timeEnd = $this->time() - 60*86400;

        if(!empty($scCode)){
            $Model = $Model->where('tracking_code',$scCode);
        }
        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }
        if($fromDate > 0){
            $Model = $Model->where('time_pickup','>',$fromDate);
        }
        if($toDate > 0){
            $Model = $Model->where('time_pickup','<',$toDate);
        }

        if(!empty($UserInfo['courier'])){
            if($UserInfo['courier'] == 'ems'){
                $Model = $Model->where('courier_id',8);
            }elseif($UserInfo['courier'] == 'emshn'){
                $Model = $Model->where('courier_id',8)->where('from_city_id',18);
            }elseif($UserInfo['courier'] == 'emshcm'){
                $Model = $Model->where('courier_id',8)->where('from_city_id',52);
            }elseif($UserInfo['courier'] == 'gts'){
                $Model = $Model->where('courier_id',9);
            }
        }
        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','lamido.vn');
        }
        //chodientu
        if($UserInfo['id'] == 2956 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','chodientu.vn');
        }

        $listData   = $Model->where('time_create','>',$timeEnd)->get();
        if($listData){
            if (Cache::has('courier_cache')){
                $listCourier    = Cache::get('courier_cache');
            }else{
                $courier        = new CourierModel;
                $listCourier    = $courier::all(array('id','name'));
            }
            if(!empty($listCourier)){
                foreach($listCourier as $val){
                    $LCourier[(int)$val['id']]   = $val['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCourier[(int)$val['courier_id']])){
                        $val->courier_name = $LCourier[(int)$val['courier_id']];
                    }
                }
            }
            $listStatus = OrderStatusModel::all();
            if($listStatus){
                foreach($listStatus AS $one){
                    $LStatus[(int)$one['code']] = $one['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LStatus[(int)$val['status']])){
                        $val->status_name = $LStatus[(int)$val['status']];
                    }
                }
            }
            $listCity = CityModel::all();
            if(!empty($listCity)){
                foreach($listCity AS $one){
                    $LCity[$one['id']] = $one['city_name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCity[(int)$val['from_city_id']])){
                        $val->city_name = $LCity[(int)$val['from_city_id']];
                    }
                }
            }
            $listDistrict = DistrictModel::all();
            if(!empty($listDistrict)){
                foreach($listDistrict AS $one){
                    $LDistrict[$one['id']] = $one['district_name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LDistrict[(int)$val['from_district_id']])){
                        $val->district_name = $LDistrict[(int)$val['from_district_id']];
                    }
                }
            }
            //xuat du lieu ra excel
            return Excel::create('Van_don_giao_hang_loi', function ($excel) use($listData) {
                $excel->sheet('Vận đơn', function ($sheet) use($listData) {
                    $sheet->mergeCells('C1:F1');
                    $sheet->row(1, function ($row) {
                        $row->setFontSize(20);
                    });
                    $sheet->row(1, array('','','Vận đơn giao hàng lỗi'));
                    // set width column
                    $sheet->setWidth(array(
                        'A'     => 5,
                        'B'     => 20,
                        'C'     => 25,
                        'D'     => 15,
                        'E'     => 40,
                        'F'     => 30,
                        'G'     => 20,
                        'H'     => 30,
                        'I'     => 30,
                        'J'     => 30,
                        'K'     => 30,
                        'L'     => 30,
                        'M'     => 30
                    ));
                    // set content row
                    $sheet->row(3, array(
                         'STT', 'Mã vận đơn', 'Mã hãng vận chuyển', 'Thành phố', 'Quận huyện', 'Địa chỉ', 'Trạng thái', 'Thời gian tạo', 'Thời gian duyệt','Thời gian lấy hàng'
                    ));
                    $sheet->row(3,function($row){
                        $row->setBackground('#B6B8BA');
                        $row->setBorder('solid','solid','solid','solid');
                        $row->setFontSize(12);
                    });
                    //
                    $i = 1;
                    foreach ($listData AS $value) {
                        $dataExport = array(
                            'STT' => $i++,
                            'Mã vận đơn' => $value['tracking_code'],
                            'Mã hãng vận chuyển' => $value['courier_tracking_code'],
                            'Thành phố'  => $value['city_name'],
                            'Quận huyện' => $value['district_name'],
                            'Địa chỉ'    => $value['from_address'],
                            'Trạng thái' => $value['status_name'],
                            'Thời gian tạo' => date("d/M/y H:m",$value['time_create']),
                            'Thời gian duyệt' => date("d/M/y H:m",$value['time_accept']),
                            'Thời gian lấy hàng' => date("d/M/y H:m",$value['time_pickup'])
                        );
                        $sheet->appendRow($dataExport);
                    }
                });
            })->export('xls');
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'error',
                'data'      => ''
            );
                    
            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }
}
?>