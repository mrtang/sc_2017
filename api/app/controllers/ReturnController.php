<?php
use ordermodel\OrdersModel;
use metadatamodel\OrderStatusModel;
class ReturnController extends \BaseController {
    private $domain = '*';

    public function __construct(){
        
    }

    public function getReturnlading(){
    	$UserInfo   = $this->UserInfo();
        if(!isset($UserInfo) || empty($UserInfo)){
            $contents = array(
                'error'     => true,
                'message'   => 'login timeout'
            );
            return Response::json($contents, 403, array('Access-Control-Allow-Origin' => $this->domain));
        }

        $page       = Input::has('page')   ? (int)Input::get('page')                : 1;
        $itemPage   = Input::has('limit')  ? Input::get('limit')                    : 20;
        $scCode     = Input::has('sc_code')  ? Input::get('sc_code')                    : '';
        $courier    = Input::has('courier')  ? (int)Input::get('courier')                    : 0;
        $fromDate     = (Input::has('time_start') && Input::get('time_start')!="")  ? (int)Input::get('time_start')                    : 0;
        $toDate     = (Input::has('time_start') && Input::get('time_start')!="")  ? (int)Input::get('time_end')                   : 0;

        $groupStatus     = Input::has('group_status')   ? Input::get('group_status') : '';
        if(empty($groupStatus)) {
            $statusReturn = \metadatamodel\GroupOrderStatusModel::where('group_status',10)->lists('order_status_code');
        } else {
            $statusReturn = \metadatamodel\GroupOrderStatusModel::where('group_status',$groupStatus)->lists('order_status_code');
        }

        $Model          = new OrdersModel;
        $offset     = ($page - 1)*$itemPage;
        $Model = $Model::skip($offset)->take($itemPage);
        $Model = $Model->where('time_create','>=',$this->time()-90*86400);
        $Model = $Model->with([
            'Service'
        ]);
        $Model = $Model->whereIn('status',$statusReturn);
        if(!empty($scCode)){
        	$Model = $Model->where('tracking_code',$scCode);
        }
        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }
        if($fromDate > 0){
            $Model = $Model->where('time_pickup','>',$fromDate);
        } else {
            $timeStart = $this->time() - 60*86400;
            $Model = $Model->where('time_pickup','>',$timeStart);
        }
        if($toDate > 0){
            $Model = $Model->where('time_pickup','<',$toDate);
        }
        //lamido
        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','lamido.vn');
        }
        $listData 	= $Model->get();
        $total		= $Model->count();
        if($listData){
        	if (Cache::has('courier_cache')) {
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
            $listIdSeller = array();
            foreach($listData AS $one){
            	$listIdSeller[] = $one['from_user_id'];
            }
            if(!empty($listIdSeller)){
            	$listInfoSeller = User::whereIn('id',$listIdSeller)->get(array('id','fullname','phone'));
            	foreach($listInfoSeller as $val){
                    $LSeller[$val['id']]   = $val['fullname'];
                    $LSellerPhone[$val['id']]   = $val['phone'];
                }
                foreach($listData as $key => $val){
                    if (isset($LSeller[(int)$val['from_user_id']])){
                        $val->seller_name = $LSeller[(int)$val['from_user_id']];
                    }
                    if (isset($LSellerPhone[(int)$val['from_user_id']])){
                        $val->seller_phone = $LSellerPhone[(int)$val['from_user_id']];
                    }
                }
            }
        	$contents = array(
	            'error'     => false,
	            'message'   => 'success',
	            'total'		=>	$total,
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
    //van don cho xac nhan chuyen hoan
    public function getLadingwaitingreturn(){
        $UserInfo   = $this->UserInfo();
        if(!isset($UserInfo) || empty($UserInfo)){
            $contents = array(
                'error'     => true,
                'message'   => 'login timeout'
            );
            return Response::json($contents, 403, array('Access-Control-Allow-Origin' => $this->domain));
        }
        $page       = Input::has('page')   ? (int)Input::get('page')                : 1;
        $itemPage   = Input::has('limit')  ? Input::get('limit')                    : 20;
        $scCode     = Input::has('sc_code')  ? Input::get('sc_code')                    : '';
        $courier    = Input::has('courier')  ? (int)Input::get('courier')                    : 0;
        $fromDate     = (Input::has('time_start') && Input::get('time_start')!="")  ? (int)Input::get('time_start')                    : 0;
        $toDate     = (Input::has('time_start') && Input::get('time_start')!="")  ? (int)Input::get('time_end')                    : 0;

        $status = \metadatamodel\GroupOrderStatusModel::where('group_status',9)->lists('order_status_code');

        $Model          = new OrdersModel;
        $offset     = ($page - 1)*$itemPage;
        $Model = $Model::skip($offset)->take($itemPage);

        $Model = $Model->where('time_create','>=',$this->time()-90*86400);
        $Model = $Model->with([
            'Service'
        ]);
        $Model = $Model->whereIn('status',$status);
        if(!empty($scCode)){
            $Model = $Model->where('tracking_code',$scCode);
        }
        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }
        if($fromDate > 0){
            $Model = $Model->where('time_pickup','>',$fromDate);
        } else {
            $timeStart = $this->time() - 60*86400;
            $Model = $Model->where('time_pickup','>',$timeStart);
        }
        if($toDate > 0){
            $Model = $Model->where('time_pickup','<',$toDate);
        }
        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','lamido.vn');
        }

        $listData   = $Model->get();
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
            $listIdSeller = array();
            foreach($listData AS $one){
                $listIdSeller[] = $one['from_user_id'];
            }
            if(!empty($listIdSeller)){
                $listInfoSeller = User::whereIn('id',$listIdSeller)->get(array('id','fullname','phone'));
                foreach($listInfoSeller as $val){
                    $LSeller[$val['id']]   = $val['fullname'];
                    $LSellerPhone[$val['id']]   = $val['phone'];
                }
                foreach($listData as $key => $val){
                    if (isset($LSeller[(int)$val['from_user_id']])){
                        $val->seller_name = $LSeller[(int)$val['from_user_id']];
                    }
                    if (isset($LSellerPhone[(int)$val['from_user_id']])){
                        $val->seller_phone = $LSellerPhone[(int)$val['from_user_id']];
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
    //van don cho xac nhan chuyen hoan
    public function getLadingreturnaccepted(){
        $UserInfo   = $this->UserInfo();
        if(!isset($UserInfo) || empty($UserInfo)){
            $contents = array(
                'error'     => true,
                'message'   => 'login timeout'
            );
            return Response::json($contents, 403, array('Access-Control-Allow-Origin' => $this->domain));
        }
        $page       = Input::has('page')   ? (int)Input::get('page')                : 1;
        $itemPage   = Input::has('limit')  ? Input::get('limit')                    : 20;
        $scCode     = Input::has('sc_code')  ? Input::get('sc_code')                    : '';
        $courier    = Input::has('courier')  ? (int)Input::get('courier')                    : 0;
        $fromDate     = (Input::has('time_start') && Input::get('time_start')!="")  ? (int)Input::get('time_start')                    : 0;
        $toDate     = (Input::has('time_start') && Input::get('time_start')!="")  ? (int)Input::get('time_end')                    : 0;

        $status = \metadatamodel\GroupOrderStatusModel::where('group_status',37)->lists('order_status_code');

        $Model          = new OrdersModel;
        $offset     = ($page - 1)*$itemPage;
        $Model = $Model::skip($offset)->take($itemPage);
        $Model = $Model->where('time_create','>=',$this->time()-90*86400);
        $Model = $Model->with([
            'Service'
        ]);
        $Model = $Model->whereIn('status',$status);
        if(!empty($scCode)){
            $Model = $Model->where('tracking_code',$scCode);
        }
        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }
        if($fromDate > 0){
            $Model = $Model->where('time_pickup','>',$fromDate);
        } else {
            $timeStart = $this->time() - 60*86400;
            $Model = $Model->where('time_pickup','>',$timeStart);
        }
        if($toDate > 0){
            $Model = $Model->where('time_pickup','<',$toDate);
        }
        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','lamido.vn');
        }

        $listData   = $Model->get();
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
            $listIdSeller = array();
            foreach($listData AS $one){
                $listIdSeller[] = $one['from_user_id'];
            }
            if(!empty($listIdSeller)){
                $listInfoSeller = User::whereIn('id',$listIdSeller)->get(array('id','fullname','phone'));
                foreach($listInfoSeller as $val){
                    $LSeller[$val['id']]   = $val['fullname'];
                    $LSellerPhone[$val['id']]   = $val['phone'];
                }
                foreach($listData as $key => $val){
                    if (isset($LSeller[(int)$val['from_user_id']])){
                        $val->seller_name = $LSeller[(int)$val['from_user_id']];
                    }
                    if (isset($LSellerPhone[(int)$val['from_user_id']])){
                        $val->seller_phone = $LSellerPhone[(int)$val['from_user_id']];
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

    public function getExportexcel() {
        $courierID = Input::has('courier_id') ? Input::get('courier_id') : 0;
        $scCode     = Input::has('sc_code')  ? Input::get('sc_code')                    : '';
        $fromDate     = (Input::has('time_start') && Input::get('time_start')!="")  ? (int)Input::get('time_start')                    : 0;
        $toDate     = (Input::has('time_start') && Input::get('time_start')!="")  ? (int)Input::get('time_end')                    : 0;


        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege']==0) {
            $response = [
                'error' =>  true,
                'message'   =>  'Không có quyền'
            ];
            return Response::json($response);
        }
        $OrderModel = OrdersModel::query();
        $OrderModel = $OrderModel->where('time_create','>=',$this->time()-90*86400);
        $groupStatus     = Input::has('group_status')   ? Input::get('group_status') : '';
        if(empty($groupStatus)) {

            $statusReturn = \metadatamodel\GroupOrderStatusModel::where('group_status',10)->lists('order_status_code');
        } else {
            $statusReturn = \metadatamodel\GroupOrderStatusModel::whereIn('group_status',$groupStatus)->lists('order_status_code');
        }
        if(!empty($statusReturn)) {
            $OrderModel->whereIn('status',$statusReturn);
        }
        if($courierID>0) {
            $OrderModel->where('courier_id',$courierID);
        }
        if(!empty($scCode)) {
            $OrderModel->where('tracking_code',$scCode);
        }
        if($fromDate==0) {
            $fromDate = $this->time() - 60*86400;
        }
        $OrderModel->where('time_pickup','>=',$fromDate);

        if($toDate>0) {
            $OrderModel->where('time_pickup','<=',$toDate);
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

        $field = array('tracking_code','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create','time_pickup','time_success','estimate_delivery');
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
                        'J'     => 30
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
                'message'   => 'Không có dữ liệu',
                'data'      => ''
            );
            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }
}
?>