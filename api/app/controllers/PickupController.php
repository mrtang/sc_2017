<?php
use ordermodel\OrdersModel;
use metadatamodel\OrderStatusModel;
use metadatamodel\GroupOrderStatusModel;

class PickupController extends \BaseController {
    private $domain = '*';

    public function __construct(){
        
    }
    //
    public function getAll(){
        $UserInfo   = $this->UserInfo();
        $page       = Input::has('page')        ? (int)Input::get('page')                : 1;
        $itemPage   = Input::has('limit')       ? Input::get('limit')                    : 20;
        $scCode     = Input::has('sc_code')     ? Input::get('sc_code')                  : '';
        $courier    = Input::has('courier')     ? (int)Input::get('courier')             : 0;
        $fromDate   = Input::has('time_start')   ? Input::get('time_start')                : 0;
        $toDate     = Input::has('time_end')     ? Input::get('time_end')                  : 0;
        $fromCity   = Input::has('from_city')   ? Input::get('from_city')                : 0;
        $fromDistrict   = Input::has('from_district')   ? Input::get('from_district')                : 0;

        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege'] == 0){
            $contents = array(
                'error'         => true,
                'message'       => 'Không có quyền !',
                'privilege'     => 0
            );
            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }
        $Model          = new OrdersModel;
        
        $Model = $Model->whereIn('status',array(21,30,35));
        if(!empty($scCode)){
            $Model = $Model->where('tracking_code', $scCode);
        }

        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }
        
        if($fromDate > 0){
            $Model = $Model->where('time_accept','>',$fromDate);
        }
        if($toDate > 0){
            $Model = $Model->where('time_accept','<',$toDate);
        }
        if($fromCity > 0){
            $Model = $Model->where('from_city_id',$fromCity);
        }
        if($fromDistrict > 0){
            $Model = $Model->where('from_district_id',$fromDistrict);
        }
        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','lamido.vn');
        }
            
        //chodientu
        if($UserInfo['id'] == 2956 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','chodientu.vn');
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
        
        $field = array('id', 'to_phone', 'tracking_code','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create');
        $total      = $Model->count();

        $offset     = ($page - 1)*$itemPage;
        $Model = $Model->with([
            'Service'
        ]);
        $Model = $Model->skip($offset)->take($itemPage);
        $listData   = $Model->get($field);

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
            $listStatus = OrderStatusModel::get(array('code','name'));
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
            $listCity = CityModel::get(array('id','city_name'));
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
            $listDistrict = DistrictModel::get(array('id','district_name'));
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
                'message'   => 'error',
                'data'      => ''
            );
                    
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }

    // Exports excel all hello
    public function getExportexcelall(){
        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege'] == 0){
            $contents = array(
                'error'         => true,
                'message'       => 'Bạn không có quyền !',
                'privilege'     => 0
            );
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
        $page       = Input::has('page')        ? (int)Input::get('page')                : 1;
        $itemPage   = Input::has('limit')       ? Input::get('limit')                    : 20;
        $scCode     = Input::has('sc_code')     ? Input::get('sc_code')                  : '';
        $courier    = Input::has('courier')     ? (int)Input::get('courier')             : 0;
        $fromDate   = Input::has('time_start')   ? Input::get('time_start')                : 0;
        $toDate     = Input::has('time_end')     ? Input::get('time_end')                  : 0;
        $fromCity   = Input::has('from_city')   ? Input::get('from_city')                : 0;
        $fromDistrict   = Input::has('from_district')   ? Input::get('from_district')                : 0;

        $Model          = new OrdersModel;
        
        $Model = $Model->whereIn('status',array(21,30,35));
        if(!empty($scCode)){
            $Model = $Model->where('tracking_code', $scCode);
        }

        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }
        
        if($fromDate > 0){
            $Model = $Model->where('time_accept','>',$fromDate);
        }
        if($toDate > 0){
            $Model = $Model->where('time_accept','<',$toDate);
        }
        if($fromCity > 0){
            $Model = $Model->where('from_city_id',$fromCity);
        }
        if($fromDistrict > 0){
            $Model = $Model->where('from_district_id',$fromDistrict);
        }
        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','lamido.vn');
        }
        //chodientu
        if($UserInfo['id'] == 2956 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','chodientu.vn');
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
        

        $field = array('id', 'to_phone', 'tracking_code','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create','from_user_id');
        $listData   = $Model->get($field);

        if($listData){
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
                    $LCourier[(int)$val['id']]   = $val['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCourier[(int)$val['courier_id']])){
                        $val->courier_name = $LCourier[(int)$val['courier_id']];
                    }
                }
            }
            $listStatus = OrderStatusModel::get(array('code','name'));
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
            $listCity = CityModel::get(array('id','city_name'));
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
            $listDistrict = DistrictModel::get(array('id','district_name'));
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
                $listUser = User::whereIn('id',$listUserId)->get(array('id','fullname','phone','email'));
                if(!empty($listUser)){
                    foreach($listUser AS $one){
                        $LUser[$one['id']] = $one;
                    }
                    foreach($listData as $key => $val){
                        if (isset($LUser[(int)$val['from_user_id']])){
                            $val->from_name = $LUser[(int)$val['from_user_id']]['fullname'];
                            $val->from_email = $LUser[(int)$val['from_user_id']]['email'];
                        }
                    }
                }
            }
            //xuat du lieu ra excel
            return Excel::create('Van_don_lay_cham', function ($excel) use($listData) {
                $excel->sheet('Vận đơn', function ($sheet) use($listData) {
                    $sheet->mergeCells('C1:F1');
                    $sheet->row(1, function ($row) {
                        $row->setFontSize(20);
                    });
                    $sheet->row(1, array('','','Vận đơn đang lấy hàng'));
                    // set width column
                    $sheet->setWidth(array(
                        'A'     => 5,
                        'B'     => 20,
                        'C'     => 15,
                        'D'     => 15,
                        'E'     => 40,
                        'F'     => 30,
                        'G'     => 20,
                        'H'     => 30,
                        'I'     => 30,
                        'J'     => 30,
                        'L'     => 30
                    ));
                    // set content row
                    $sheet->row(3, array(
                         'STT', 
                         'Mã vận đơn', 
                         'Hãng vận chuyển', 
                         'Dịch vụ', 
                         'Người gửi', 
                         'Email', 
                         'Tỉnh thành gửi', 
                         'Quận huyện gửi',
                         'Địa chỉ gửi', 
                         'Thời gian tạo',
                         'Thời gian duyệt'
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
                            'Hãng vận chuyển'  => $value['courier_name'],
                            'Dịch vụ' => ($value['service_id'] == 1) ? 'Chuyển thường' : 'Chuyển nhanh',
                            'Người gửi' => $value['from_name'],
                            'Email' => $value['from_email'],
                            'Tỉnh thành gửi' => $value['city_name'],
                            'Quận huyện gửi' => $value['district_name'],
                            'Địa chỉ gửi' => $value['from_address'],
                            'Thời gian tạo' => date("d/M/y H:m",$value['time_create']),
                            'Thời gian duyệt' => date("d/M/y H:m",$value['time_accept'])
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
                    
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }



    //
    public function getPickuplate(){
        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege'] == 0){
            $contents = array(
                'error'         => true,
                'message'       => 'Bạn không có quyền !',
                'privilege'     => 0
            );
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
        $page       = Input::has('page')        ? (int)Input::get('page')                : 1;
        $itemPage   = Input::has('limit')       ? Input::get('limit')                    : 20;
        $scCode     = Input::has('sc_code')     ? Input::get('sc_code')                  : '';
        $domain     = Input::has('domain')     ? Input::get('domain')                  : '';
        $Courier     = Input::has('courier')     ? Input::get('courier')                  : 0;
        $time       = Input::has('time_late')   ? Input::get('time_late')                : 12;
        $fromDate   = Input::has('time_start')   ? Input::get('time_start')                : 0;
        $toDate     = Input::has('time_end')     ? Input::get('time_end')                  : 0;
        $fromCity   = Input::has('from_city')   ? Input::get('from_city')                : 0;
        $fromDistrict   = Input::has('from_district')   ? Input::get('from_district')                : 0;

        // Add by ThinhNV 
        $sender     = Input::has('sender')      ? Input::get('sender')                   : null;
        $receiver   = Input::has('receiver')    ? Input::get('receiver')                 : null;
        //

        $statusLatePickup = GroupOrderStatusModel::where('group_status',3)->lists('order_status_code');
        $Model          = new OrdersModel;
        $Model = $Model->whereIn('status',$statusLatePickup);
        if(!empty($scCode)){
            $Model = $Model->where('tracking_code', $scCode);
        }

        // added by ThinhNV
        if(!empty($receiver)){
            $Model = $Model->where('to_name' , 'LIKE', '%' + $receiver + '%');
            $Model = $Model->where('to_email', 'LIKE', '%' + $receiver + '%');
        }
        if($Courier > 0){
            $Model = $Model->where('courier_id',$Courier);
        }
        if($fromDate > 0){
            $Model = $Model->where('time_accept','>',$fromDate);
        }
        if($toDate > 0){
            $Model = $Model->where('time_accept','<',$toDate);
        }
        if($fromCity > 0){
            $Model = $Model->where('from_city_id',$fromCity);
        }
        if($fromDistrict > 0){
            $Model = $Model->where('from_district_id',$fromDistrict);
        }
        if(!empty($domain)) {
            $Model = $Model->where('domain',$domain);
        }

        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','lamido.vn');
        }
        //chodientu
        if($UserInfo['id'] == 2956 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','chodientu.vn');
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

        $field = array('id','tracking_code','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create');

        $Model = $Model->where('time_accept','<=',$this->time() - $time*3600);
        $total      = $Model->count();
        $offset     = ($page - 1)*$itemPage;
        $Model = $Model->with([
            'Service'
        ]);
        $Model = $Model->skip($offset)->take($itemPage);
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
                    $LCourier[(int)$val['id']]   = $val['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCourier[(int)$val['courier_id']])){
                        $val->courier_name = $LCourier[(int)$val['courier_id']];
                    }
                }
            }
            $listStatus = OrderStatusModel::get(array('code','name'));
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
            $listCity = CityModel::get(array('id','city_name'));
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
            $listDistrict = DistrictModel::get(array('id','district_name'));
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
                'message'   => 'error',
                'data'      => ''
            );
                    
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }

    //Xuat excel pickup late
    public function getExportexcelpickuplate(){
        $UserInfo   = $this->UserInfo();
        $time       = Input::has('time_late')  ? Input::get('time_late')                    : 4;
        $timeLate = strtotime(date('Y-m-d 00:00:00',strtotime('-'.$time.' hour',$this->time())));

        $statusLatePickup = GroupOrderStatusModel::where('group_status',3)->lists('order_status_code');

        $Model          = new OrdersModel;
        $Model = $Model->whereIn('status',$statusLatePickup);


        $Model = $Model->where('time_accept','<=',$this->time() - $time*3600);
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

        $listData   = $Model->get();
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
            return Excel::create('Van_don_lay_cham', function ($excel) use($listData) {
                $excel->sheet('Vận đơn', function ($sheet) use($listData) {
                    $sheet->mergeCells('C1:F1');
                    $sheet->row(1, function ($row) {
                        $row->setFontSize(20);
                    });
                    $sheet->row(1, array('','','Vận đơn lấy hàng chậm'));
                    // set width column
                    $sheet->setWidth(array(
                        'A'     => 5,
                        'B'     => 20,
                        'C'     => 15,
                        'D'     => 15,
                        'E'     => 40,
                        'F'     => 30,
                        'G'     => 20,
                        'H'     => 30
                    ));
                    // set content row
                    $sheet->row(3, array(
                         'STT', 'Mã vận đơn', 'Hãng vận chuyển', 'Thành phố', 'Quận huyện', 'Địa chỉ', 'Trạng thái', 'Thời gian tạo', 'Thời gian duyệt'
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
                            'Hãng vận chuyển'   =>  $value['courier_name'],
                            'Thành phố'  => $value['city_name'],
                            'Quận huyện' => $value['district_name'],
                            'Địa chỉ'    => $value['from_address'],
                            'Trạng thái' => $value['status_name'],
                            'Thời gian tạo' => date("d/M/y H:m",$value['time_create']),
                            'Thời gian duyệt' => date("d/M/y H:m",$value['time_accept'])
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
                    
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }

    public function getPickupfail(){
        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege'] == 0){
            $contents = array(
                'error'         => true,
                'message'       => 'Bạn không có quyền !',
                'privilege'     => 0
            );
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
        $page       = Input::has('page')   ? (int)Input::get('page')                : 1;
        $itemPage   = Input::has('limit')  ? Input::get('limit')                    : 20;
        $scCode     = Input::has('sc_code')  ? Input::get('sc_code')                : '';
        $courier       = Input::has('courier')   ? (int)Input::get('courier')       : 0;
        $fromDate     = Input::has('time_start')  ? Input::get('time_start')        : 0;
        $toDate     = Input::has('time_end')  ? Input::get('time_end')              : 0;
        $fromCity   = Input::has('from_city')   ? Input::get('from_city')                : 0;
        $fromDistrict   = Input::has('from_district')   ? Input::get('from_district')                : 0;

        $statusFailPickup = GroupOrderStatusModel::where('group_status',4)->lists('order_status_code');

        $Model          = new OrdersModel;
        $Model = $Model->whereIn('status',$statusFailPickup);
        if(!empty($scCode)){
            $Model = $Model->where('tracking_code',$scCode);
        }
        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }
        if($fromDate > 0){
            $Model = $Model->where('time_accept','>',$fromDate);
        }
        if($toDate > 0){
            $Model = $Model->where('time_accept','<',$toDate);
        }
        if($fromCity > 0){
            $Model = $Model->where('from_city_id',$fromCity);
        }
        if($fromDistrict > 0){
            $Model = $Model->where('from_district_id',$fromDistrict);
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
        $field = array('tracking_code','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create');
        $total      = $Model->count();

        $offset     = ($page - 1)*$itemPage;
        $Model = $Model->with([
            'Service'
        ]);
        $Model = $Model->skip($offset)->take($itemPage);
        $listData   = $Model->get($field);
        

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
            $listStatus = OrderStatusModel::get(array('code','name'));
            if($listStatus){
                foreach($listStatus AS $one){
                    $LStatus[$one['code']] = $one['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LStatus[(int)$val['status']])){
                        $val->status_name = $LStatus[(int)$val['status']];
                    }
                }
            }
            $listCity = CityModel::get(array('id','city_name'));
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
            $listDistrict = DistrictModel::get(array('id','district_name'));
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
                'data'      => $listData
            );

            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'error',
                'data'      => ''
            );
                    
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }

    //Xuat excel pickup fail
    public function getExportexcelpickupfail(){
        $UserInfo   = $this->UserInfo();

        $statusFailPickup = GroupOrderStatusModel::where('group_status',4)->lists('order_status_code');
        $Model          = new OrdersModel;
        $Model = $Model->whereIn('status',$statusFailPickup);

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

        $listData   = $Model->get();

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
            return Excel::create('Van_don_lay_loi', function ($excel) use($listData) {
                $excel->sheet('Vận đơn', function ($sheet) use($listData) {
                    $sheet->mergeCells('C1:F1');
                    $sheet->row(1, function ($row) {
                        $row->setFontSize(20);
                    });
                    $sheet->row(1, array('','','Vận đơn lấy hàng lỗi'));
                    // set width column
                    $sheet->setWidth(array(
                        'A'     => 5,
                        'B'     => 20,
                        'C'     => 30,
                        'D'     => 30,
                        'E'     => 40,
                        'F'     => 30,
                        'G'     => 20,
                        'H'     => 30,
                        'I'     => 30
                    ));
                    // set content row
                    $sheet->row(3, array(
                         'STT', 'Mã vận đơn','Hãng vận chuyển', 'Thành phố', 'Quận huyện', 'Địa chỉ', 'Trạng thái', 'Thời gian tạo', 'Thời gian duyệt'
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
                            'Hãng vận chuyển' => $value['courier_name'],
                            'Thành phố'  => $value['city_name'],
                            'Quận huyện' => $value['district_name'],
                            'Địa chỉ'    => $value['from_address'],
                            'Trạng thái' => $value['status_name'],
                            'Thời gian tạo' => date("d/M/y H:m",$value['time_create']),
                            'Thời gian duyệt' => date("d/M/y H:m",$value['time_accept'])
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
                    
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }

    public function getListstatus(){
        $listStatus = OrderStatusModel::all();

        if($listStatus){
            $output = array();
            foreach($listStatus AS $val){
                $output[] = array(
                    'id' => (int)$val['id'],
                    'code' => (int)$val['code'],
                    'name'  => $val['name']
                );
            }
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $output
            );

            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'error',
                'data'      => ''
            );
                    
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }
    //
    public function getListstatuscancel(){
        $listStatusCancel = GroupOrderStatusModel::where('group_status',11)->get(array('order_status_code'))->toArray();
        if(!empty($listStatusCancel)){
            $listId = array();
            foreach($listStatusCancel AS $one){
                $listId[] = $one['order_status_code'];
            }
            $infoStatusCancel = OrderStatusModel::whereIn('code',$listId)->get()->toArray();
            if($infoStatusCancel){
                $output = array();
                foreach($infoStatusCancel AS $val){
                    $output[] = array(
                        'id' => (int)$val['id'],
                        'code' => (int)$val['code'],
                        'name'  => $val['name']
                    );
                }
                $contents = array(
                    'error'     => false,
                    'message'   => 'success',
                    'data'      => $output
                );

                return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'error',
                    'data'      => ''
                );
                        
                return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'error',
                'data'      => ''
            );
                    
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }
    //
    public function getListstatusacceptcancel(){
        $listStatus = OrderStatusModel::where('code','<',36)->get()->toArray();

        if($listStatus){
            $output = array();
            foreach($listStatus AS $val){
                $output[] = (int)$val['code'];
            }
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $output
            );

            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'error',
                'data'      => ''
            );
                    
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }
    //
    public function getUpdatestatuspickup(){
        $UserInfo   = $this->UserInfo();
        if(!isset($UserInfo) || empty($UserInfo)){
            $contents = array(
                'error'     => true,
                'message'   => 'login timeout'
            );
            return Response::json($contents, 403, array('Access-Control-Allow-Origin' => $this->domain));
        }
        $orderId       = Input::has('order_id')   ? (int)Input::get('order_id')                : '';
        $status   = Input::has('status')  ? Input::get('status')                    : '';
        if(empty($orderId) || empty($status)){
            $contents = array(
                'error' => true, 'message' => 'Not update status!!'
            );
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
        $Model          = new OrdersModel;
        $infoOrder = $Model::find($orderId);
        if($infoOrder){
            if(!empty($status)){
                $infoOrder->status           = $status;
                $infoOrder->time_update      = $this->time();
            }
            $Update = $infoOrder->save();
       
            if($Update){
                $contents = array(
                    'error'     => false,
                    'message'   => 'success'
                );
            }else{
                $contents = array(
                    'error' => true,
                    'message' => 'fail'
                );
            }
        }else{
            $contents = array(
                'error' => true,
                'message' => 'not exits'
            );
        }
        return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));

    }

    public function getPickupcancel(){
        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege'] == 0){
            $contents = array(
                'error'         => true,
                'message'       => 'Bạn không có quyền !',
                'privilege'     => 0
            );
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
        $page       = Input::has('page')   ? (int)Input::get('page')                : 1;
        $courier    = Input::has('courier')   ? (int)Input::get('courier')                : 0;
        $itemPage   = Input::has('limit')  ? Input::get('limit')                    : 20;
        $fromDate     = Input::has('time_start')  ? Input::get('time_start')                    : 0;
        $toDate     = Input::has('time_end')  ? Input::get('time_end')                    : 0;
        $scCode     = Input::has('sc_code')  ? Input::get('sc_code')                : '';
        $status     = Input::has('status')  ? Input::get('status')                : '';
        $fromCity   = Input::has('city_id') ? (int)Input::get('city_id')     :   0;
        $fromDistrict   = Input::has('district_id') ? (int)Input::get('district_id')     :   0;

        $statusFailPickup = GroupOrderStatusModel::where('group_status',11)->lists('order_status_code');
        $timeEnd = $this->time() - 30*86400;

        $Model          = new OrdersModel;
        $Model->where('time_accept','>',$timeEnd);
        if(!empty($status)) {
            $Model = $Model->where('status',$status);
        } else {
            $Model = $Model->whereIn('status',$statusFailPickup);
        }
        if(!empty($scCode)){
            $Model = $Model->where('tracking_code',$scCode);
        }
        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }
        if($fromDate > 0){
            $Model = $Model->where('time_accept','>',$fromDate);
        }
        if($toDate > 0){
            $Model = $Model->where('time_accept','<',$toDate);
        }
        if($fromCity > 0){
            $Model = $Model->where('from_city_id',$fromCity);
        }
        if($fromDistrict > 0){
            $Model = $Model->where('from_district_id',$fromDistrict);
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

        $field = array('tracking_code','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create','time_pickup');

        $total      = $Model->count();
        $offset     = ($page - 1)*$itemPage;
        $Model = $Model->skip($offset)->take($itemPage);
        $listData   = $Model->get($field);

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
            $listStatus = OrderStatusModel::all(array('code','name'));
            if($listStatus){
                foreach($listStatus AS $one){
                    $LStatus[$one['code']] = $one['name'];
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
                'data'      => $listData
            );

            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'error',
                'data'      => ''
            );
                    
            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }
    //Xuat excel pickup cancel
    public function getExportexcelpickupcancel(){
        $UserInfo   = $this->UserInfo();
        $status = Input::has('status') ? Input::get('status') : '';

        $statusFailPickup = GroupOrderStatusModel::where('group_status',11)->lists('order_status_code');

        $Model          = new OrdersModel;

        if(!empty($status)) {
            $Model = $Model->where('status',$status);
        } else {
            $Model = $Model->whereIn('status',$statusFailPickup);
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

        $listData   = $Model->get();
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
            return Excel::create('Van_don_lay_huy', function ($excel) use($listData) {
                $excel->sheet('Vận đơn', function ($sheet) use($listData) {
                    $sheet->mergeCells('C1:F1');
                    $sheet->row(1, function ($row) {
                        $row->setFontSize(20);
                    });
                    $sheet->row(1, array('','','Vận đơn lấy hàng huỷ'));
                    // set width column
                    $sheet->setWidth(array(
                        'A'     => 5,
                        'B'     => 20,
                        'C'     => 15,
                        'D'     => 15,
                        'E'     => 40,
                        'F'     => 30,
                        'G'     => 20,
                        'H'     => 30,
                        'I'     => 30
                    ));
                    // set content row
                    $sheet->row(3, array(
                         'STT', 'Mã vận đơn','Hãng vận chuyển', 'Thành phố', 'Quận huyện', 'Địa chỉ', 'Trạng thái', 'Thời gian tạo', 'Thời gian duyệt'
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
                            'Hãng vận chuyển' => $value['courier_name'],
                            'Thành phố'  => $value['city_name'],
                            'Quận huyện' => $value['district_name'],
                            'Địa chỉ'    => $value['from_address'],
                            'Trạng thái' => $value['status_name'],
                            'Thời gian tạo' => date("d/M/y H:m",$value['time_create']),
                            'Thời gian duyệt' => date("d/M/y H:m",$value['time_accept'])
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

            return Response::json($contents, 500, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }



}
?>