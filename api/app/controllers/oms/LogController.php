<?php
namespace oms;
use DB;
use DbView;
use Input;
use Response;
use LMongo;
use Cache;
use CourierModel;
use CityModel;
use DistrictModel;
use ordermodel\OrdersModel;
use ordermodel\DetailModel;
use User;
use order;
use QueueModel;
use TemplateModel;
use ticketmodel\FeedbackModel;
use ScenarioModel;
use Smslib;
use Excel;
use omsmodel\AppNotifyModel;
use omsmodel\IndemnifyModel;
use sellermodel\UserInfoModel;
use UserConfigTransportModel;

class LogController extends \BaseController {
    private $domain = '*';

    private $list_district_id;
    private $list_ward_id;
    private $list_user_id;


    public function __construct(){


    }
    //journey
    public function getJourney(){
        $LMongo     = new LMongo;
        $LMongo     = $LMongo::collection('log_journey_lading');
        $itemPage   = Input::has('limit')       ? Input::get('limit')                       : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')                   : 1;
        $scCode     = Input::has('sc_code')     ? Input::get('sc_code')                     : '';
        $status     = Input::has('status')      ? (int)Input::get('status')                 : 0;

        if(!empty($scCode)){
            $LMongo = $LMongo->where('tracking_code', $scCode);
        }
        if($status > 0 ){
            //chua xl
            if($status == 1){
                $LMongo = $LMongo->where('accept',0);
            }
            //xl thanhcong
            if($status == 2){
                $LMongo = $LMongo->where('accept',1);
            }
            //chua xl
            if($status == 3){
                $LMongo = $LMongo->where('accept','>',1);
            }
        }

        $LMongoTotal    = clone $LMongo;
        $total          = $LMongoTotal->count();
        $listData       = [];
        if($total > 0){
            $offset     = ($page - 1)*$itemPage;
            $listData   = $LMongo->skip($offset)->take($itemPage)->orderBy('time_create','desc')->get()->toArray();
        }

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $listData,
            'total'         =>  $total,
            'item_page'     => $itemPage
        );

        return Response::json($contents);
    }

        //journey
    public function getChangeOrder(){
        $LMongo     = new LMongo;
        $LMongo     = $LMongo::collection('log_change_order');
        $itemPage   = Input::has('limit')       ? Input::get('limit')                    : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')                : 1;
        $scCode     = Input::has('sc_code')     ? Input::get('sc_code')                  : '';

        if(!empty($scCode)){
            $Model = new OrdersModel;
            $order_id = 0;
            $Order = $Model->where('time_create','>=', $this->time() - $this->time_limit)->where('tracking_code', $scCode)->first();
            if($Order){
                $order_id = $Order->id;
            }
            $LMongo = $LMongo->where('order_id', $order_id);
        }/*else {
            $LMongo = $LMongo->where('order_id', 0);
        }*/
        
        $LMongoTotal = clone $LMongo;
        $total       = $LMongoTotal->count();
        $offset      = ($page - 1)*$itemPage;
        $listData    = [];
        $listOrder   = [];
        $listUser    = [];

        if($total > 0){
            $listData   = $LMongo->skip($offset)->take($itemPage)->orderBy('time_create','desc')->get()->toArray();
            $listOrderId = [];
            $listUserId = [];
            if(!empty($listData)) {
                foreach ($listData as $key => $value) {
                    $listOrderId[] = $value['order_id'];
                    $listUserId[]  = $value['user_id'];
                }
                $OrderModel = new OrdersModel;
                $OrderData  = $OrderModel->where('time_create', '>=', $this->time() - $this->time_limit)->whereIn('id', $listOrderId)->get(['id','tracking_code','time_create'])->toArray();
                $UserModel  = new \User;
                $UserData   = $UserModel->whereIn('id', $listUserId)->get(['id','email','fullname'])->toArray();

                if ($OrderData) {
                    foreach ($OrderData as $val) {
                        $listOrder[(int)$val['id']] = $val['tracking_code'];
                    }
                }
                if ($UserData) {
                    foreach ($UserData as $val) {
                        $listUser[(int)$val['id']] = $val;
                    }
                }
            }
        }
        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $listData,
            'order'     => $listOrder,
            'user'      => $listUser,
            'total'     => $total,
            'item_page' => $itemPage
        );

        return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
    }

    //Log create lading
    public function getCreatelading(){
        $LMongo     = new LMongo;
        $LMongo     = $LMongo::collection('log_create_lading');
        $itemPage   = Input::has('limit')       ? Input::get('limit')                   : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')               : 1;
        $scCode     = Input::has('sc_code')     ? Input::get('sc_code')                 : '';

        if(!empty($scCode)){
            $LMongo = $LMongo->where('trackingcode', $scCode);
        }

        $LMongoTotal    = clone $LMongo;
        $total          = $LMongoTotal->count();
        $offset         = ($page - 1)*$itemPage;
        $listData       = [];

        if($total > 0) {
            $listData   = $LMongo->skip($offset)->take($itemPage)->orderBy('time_create','desc')->get()->toArray();
            foreach ($listData as $val) {
                if (!empty($val['input'])) {
                    if (isset($val['input']['From']['Province']) && $val['input']['From']['Province'] > 0) {
                        $this->list_district_id[] = (int)$val['input']['From']['Province'];
                    }
                    if (isset($val['input']['From']['Ward']) && $val['input']['From']['Ward'] > 0) {
                        $this->list_district_id[] = (int)$val['input']['From']['Ward'];
                    }
                    if ($val['input']['To']['Province'] > 0) {
                        $this->list_district_id[] = (int)$val['input']['To']['Province'];
                    }
                    if (isset($val['input']['To']['Ward']) && $val['input']['To']['Ward'] > 0) {
                        $this->list_district_id[] = (int)$val['input']['To']['Ward'];
                    }
                }

                if (!empty($val['datalog']) && isset($val['datalog']['order']) && $val['datalog']['order']['from_user_id']) {
                    $this->list_user_id[] = (int)$val['datalog']['order']['from_user_id'];
                }
            }

            if(!empty($this->list_district_id)){
                $this->list_district_id = $this->getProvince(array_unique($this->list_district_id));
            }

            if(!empty($this->list_ward_id)){
                $this->list_ward_id     = $this->getWard(array_unique($this->list_ward_id));
            }

            if(!empty($this->list_user_id)){
                $UserModel              = new User;
                $this->list_user_id     = $UserModel->getUserById(array_unique($this->list_user_id));
            }
        }

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $listData,
            'total'         =>  $total,
            'item_page'     => $itemPage,
            'list_district' => $this->list_district_id,
            'list_ward'     => $this->list_ward_id,
            'list_user'     => $this->list_user_id
        );

        return Response::json($contents);
    }
    //log sent sms
    public function getLogsms(){
        $LMongo     = new LMongo;
        $LMongo     = $LMongo::collection('log_send_sms');
        $itemPage   = Input::has('limit')       ? Input::get('limit')                       : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')                   : 1;
        $phone      = Input::has('phone')     ? Input::get('phone')                         : '';
        $listData   = [];

        if(!empty($phone)){
            $LMongo = $LMongo->whereLike('to_phone', $phone, 'im');
        }

        $LMongoTotal = clone $LMongo;
        $total      = $LMongoTotal->count();
        if($total > 0){
            $offset     = ($page - 1)*$itemPage;
            $listData   = $LMongo->skip($offset)->take($itemPage)->orderBy('time_create','desc')->get()->toArray();
        }

        $contents = array(
            'error'     => false,
            'message'       => 'success',
            'data'          => $listData,
            'total'         =>  $total
        );

        return Response::json($contents);
    }
    //log sent sms
    public function getOverWeight(){
        $LMongo         = new LMongo;
        $LMongo         = $LMongo::collection('log_over_weight');
        $itemPage       = Input::has('item_page')           ? (int)Input::get('item_page')                      : 20;
        $page           = Input::has('page')                ? (int)Input::get('page')                           : 1;

        $CreateStart    = Input::has('create_start')        ? (int)Input::get('create_start')                   : 0;
        $CreateEnd      = Input::has('create_end')          ? (int)Input::get('create_end')                     : 0;
        $TrackingCode   = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code')))     : '';
        $Status         = Input::has('status')              ? (int)Input::get('status')                         : 0;
        $Cmd            = Input::has('cmd')                 ? strtoupper(trim(Input::get('cmd')))               : '';

        $listData   = [];

        if(!empty($CreateStart)){
            $LMongo = $LMongo->whereGte('time_create', $CreateStart);
        }

        if(!empty($CreateEnd)){
            $LMongo = $LMongo->whereLte('time_create', $CreateEnd);
        }

        if(!empty($TrackingCode)){
            $LMongo = $LMongo->where('tracking_code', $TrackingCode);
        }

        if(!empty($Status)){
            if($Status == 1){
                $LMongo = $LMongo->where('status', 'WAITING');
            }elseif($Status == 2){
                $LMongo = $LMongo->where('status', 'SUCCESS');
            }else{
                $LMongo = $LMongo->whereNin('status',['SUCCESS','WAITING']);
            }
        }

        if($Cmd == 'EXPORT'){
            return $this->ExWeight($LMongo->orderBy('time_create','desc')->get()->toArray());
        }

        $LMongoTotal = clone $LMongo;
        $total      = $LMongoTotal->count();
        if($total > 0){
            $offset     = ($page - 1)*$itemPage;
            $listData   = $LMongo->skip($offset)->take($itemPage)->orderBy('time_create','desc')->get()->toArray();
        }

        $contents = array(
            'error'     => false,
            'message'       => 'success',
            'data'          => $listData,
            'total'         =>  $total
        );

        return Response::json($contents);
    }

    //log sent sms
    public function getReportCourier($type = 61){
        $LMongo         = new LMongo;
        $LMongo         = $LMongo::collection('log_report_lading')->where('status',(int)$type);

        $itemPage       = Input::has('item_page')           ? (int)Input::get('item_page')                      : 20;
        $page           = Input::has('page')                ? (int)Input::get('page')                           : 1;

        $CreateStart    = Input::has('create_start')        ? (int)Input::get('create_start')                   : 0;
        $CreateEnd      = Input::has('create_end')          ? (int)Input::get('create_end')                     : 0;
        $TrackingCode   = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code')))     : '';
        $Status         = Input::has('status')              ? (int)Input::get('status')                         : 0;
        $Cmd            = Input::has('cmd')                 ? strtoupper(trim(Input::get('cmd')))               : '';

        $listData   = [];

        if(!empty($CreateStart)){
            $LMongo = $LMongo->whereGte('time_create', $CreateStart);
        }

        if(!empty($CreateEnd)){
            $LMongo = $LMongo->whereLte('time_create', $CreateEnd);
        }

        if(!empty($TrackingCode)){
            if($type == 61){
                $LMongo = $LMongo->where('params.f_MA_VANDONGOC', $TrackingCode);
            }else{
                $LMongo = $LMongo->where('tracking_code', $TrackingCode);
            }

        }

        if(!empty($Status)){
            if($Status == 1){
                $LMongo = $LMongo->where('error_code', 'SUCCESS');
            }elseif($Status == 2){
                $LMongo = $LMongo->whereNe('error_code', 'SUCCESS');
            }
        }

        if($Cmd == 'EXPORT'){
            return $this->ExAcceptReturn($LMongo->orderBy('time_create','desc')->get()->toArray());
        }elseif($Cmd == 'EXPORT-ORDER'){
            $Data   = $LMongo->orderBy('time_create','desc')->get()->toArray();
            $Order  = [];
            $Refer  = [];
            if(!empty($Data)){
                foreach($Data as $val){
                    $Refer[]    = isset($val['tracking_code']) ? $val['tracking_code'] : $val['params']['f_MA_VANDONGOC'];
                }

                if(!empty($Refer)){
                    $Model              = new OrdersModel;
                    $Order              = $Model->where('time_accept','>=',$this->time() - $this->time_limit)->whereIn('tracking_code', $Refer);
                }
            }
            $OrderCtrl  = new OrderCtrl;
            return $OrderCtrl->ExportExcel($Order);
        }


        $LMongoTotal = clone $LMongo;
        $total      = $LMongoTotal->count();
        if($total > 0){
            $offset     = ($page - 1)*$itemPage;
            $listData   = $LMongo->skip($offset)->take($itemPage)->orderBy('time_create','desc')->get()->toArray();
        }

        $contents = array(
            'error'     => false,
            'message'       => 'success',
            'data'          => $listData,
            'total'         =>  $total
        );

        return Response::json($contents);
    }

    /*
     * Export Excel
     */
    private function ExWeight($Data){
        $FileName   = 'Danh_sach_cap_nhat_vuot_can';
        $Courier    = $this->getCourier();

        return \Excel::create($FileName, function($excel) use($Data, $Courier){
            $excel->sheet('Sheet1', function($sheet) use($Data, $Courier){
                $sheet->mergeCells('D1:F1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','Danh sách cập nhật vượt cân'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30
                ));

                $sheet->row(3, array(
                    'STT', 'Mã đơn hàng', 'Hãng vận chuyển', 'Khối lượng', 'Khối lượng cũ', 'Trạng thái'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });

                $sheet->setBorder('A3:F3', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        isset($val['tracking_code'])                                                ? $val['tracking_code']                     : '',
                        (isset($val['courier_id']) && (isset($Courier[(int)$val['courier_id']])))   ? $Courier[(int)$val['courier_id']]         : '',
                        isset($val['total_weight'])                                                 ? number_format($val['total_weight'])       : '',
                        isset($val['old_weight'])                                                   ? number_format($val['old_weight'])         : '',
                        isset($val['status'])                                                       ? $val['status']                            : '',
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }

    private function ExAcceptReturn($Data){
        $FileName   = 'Danh_sach';
        $Courier    = $this->getCourier();

        return \Excel::create($FileName, function($excel) use($Data, $Courier){
            $excel->sheet('Sheet1', function($sheet) use($Data, $Courier){
                $sheet->mergeCells('D1:F1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','Danh sách'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F' => 30
                ));

                $sheet->row(3, array(
                    'STT', 'Thời gian duyệt', 'Mã đơn hàng', 'Hãng vận chuyển', 'Trạng thái', 'Nội dung'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });

                $sheet->setBorder('A3:F3', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        $val['time_create'] > 0 ? date("d/m/y H:m",$val['time_create']) : '',
                        isset($val['params']['f_MA_VANDONGOC'])                                     ? $val['params']['f_MA_VANDONGOC']          : $val['tracking_code'],
                        (isset($val['courier']) && (isset($Courier[(int)$val['courier']])))         ? $Courier[(int)$val['courier']]            : '',
                        isset($val['error_code'])                                                   ? $val['error_code']                        : '',
                        isset($val['messenger'])                                                    ? $val['messenger']                         : '',
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }
    //tong hop email da gui trong ngay
    public function getStatistic(){
        $Type = Input::has('type')           ? Input::get('type')                      : 'app';
        $UserInfo   = $this->UserInfo();
        $QueueModel = new QueueModel;
        $FeedbackModel = new FeedbackModel;
        $DefaultStartTime = mktime(0, 0, 0, date('m'), date('d')-1, date('Y'));
        $DefaultEndTime = mktime(23, 59, 59, date('m'), date('d')-1, date('Y'));
        $FromDateGet = Input::has('from_date')       ? Input::get('from_date') : 0;        
        $ToDateGet = Input::has('to_date')       ? Input::get('to_date') : 0;
        //
        $ArrCreate = $ArrSent = array();
        $DateGet = ''; 
        if($Type == 'app'){
            $Transport = 5;
        }elseif($Type == 'email'){
            $Transport = 2;
        }elseif($Type == 'sms'){
            $Transport = 1;
        }elseif($Type == 'zalo'){
            $Transport = 7;
        }else{
            $Transport = 5;
        }
        if($FromDateGet > 0 && $ToDateGet > 0){
            $TotalEmailCreateByDate = $QueueModel->where('time_create','>',$FromDateGet)->where('time_create','<',$ToDateGet+86400)->where('transport_id',$Transport)->where('time_create','<',$ToDateGet)->groupBy('template_id')->get(array('template_id',DB::raw('count(*) as count')));
            foreach($TotalEmailCreateByDate AS $One){
                $ArrCreate[$One['template_id']] = $One['count'];
            }
            //
            $TotalEmailSentByDate = $QueueModel->where('status',1)->where('time_create','>',$FromDateGet)->where('time_create','<',$ToDateGet+86400)->where('transport_id',$Transport)->where('time_create','<',$ToDateGet)->groupBy('template_id')->get(array('template_id',DB::raw('count(*) as count')));
            foreach($TotalEmailSentByDate AS $One){
                $ArrSent[$One['template_id']] = $One['count'];
            }
        }else{
            $TotalEmailCreate = $QueueModel->where('time_create','>',$DefaultStartTime)->where('time_create','<',$DefaultEndTime)->where('transport_id',$Transport)->groupBy('template_id')->get(array('template_id',DB::raw('count(*) as count')));
            $ArrCreate = array();
            foreach($TotalEmailCreate AS $One){
                $ArrCreate[$One['template_id']] = $One['count'];
            }

            $TotalEmailSent = $QueueModel->where('status',1)->where('time_create','>',$DefaultStartTime)->where('time_create','<',$DefaultEndTime)->groupBy('template_id')->get(array('template_id',DB::raw('count(*) as count')));
            $ArrSent = array();
            foreach($TotalEmailSent AS $One){
                $ArrSent[$One['template_id']] = $One['count'];
            }
        }
        
        $ListScenario = TemplateModel::where('transport_id',$Transport)->get(array('id','title'))->toArray();
        $ArrScenario = array();
        foreach($ListScenario AS $One){
            $ArrScenario[$One['id']] = $One['title'];
        }

        $contents = array(
            'error'     => false,
            'message'       => 'success',
            'scenario'          => $ListScenario,
            'emailCreated'     => $ArrCreate,
            'emailSent'     => $ArrSent,
            'type'          => $Type
        );

        return Response::json($contents);
    }
    public function getStatisticlist($id){
        $itemPage   = Input::has('limit')       ? Input::get('limit')                       : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')                   : 1;
        $key        = Input::has('key')         ? Input::get('key')                         : '';
        $FromDate   = Input::has('from_date')        ? (int)Input::get('from_date')                   : 0;
        $ToDate   = Input::has('to_date')        ? (int)Input::get('to_date')                   : 0;
        if($FromDate > 0 && $ToDate > 0){
            $DefaultStartTime = $FromDate;
            $DefaultEndTime = $ToDate + 86400;
        }else{
            $DefaultStartTime = mktime(0, 0, 0, date('m'), date('d')-1, date('Y'));
            $DefaultEndTime = mktime(23, 59, 59, date('m'), date('d')-1, date('Y'));
        }

        $listData   = [];
        $Model = new QueueModel;
        $Model = $Model->where('template_id',(int)$id)->where('time_create','>',$DefaultStartTime)->where('time_create','<',$DefaultEndTime);
        if(!empty($key)){
            $Model = $Model->where('received', $key);
        }

        $Total = clone $Model;
        $total      = $Total->count();
        if($total > 0){
            $offset     = ($page - 1)*$itemPage;
            $listData   = $Model->skip($offset)->take($itemPage)->orderBy('time_create','desc')->get()->toArray();
        }

        $contents = array(
            'error'     => false,
            'message'       => 'success',
            'data'          => $listData,
            'total'         =>  $total
        );

        return Response::json($contents);
    }
    //tao tin nhan
    public function postCreatesms(){
        $userInfo = $this->UserInfo();
        $LMongo     = new LMongo;
        $phone          = Input::has('phone')               ? (string)Input::get('phone')               : '';
        $content        = Input::has('content')              ? Input::get('content')                    : '';
        if($phone == ''){
            return Response::json(array(
                'error'         => true,
                'message'       => 'Không có số điện thoại!!',
                'data'          => ''
            ));
        }
        if($content == ''){
            return Response::json(array(
                'error'         => true,
                'message'       => 'Bạn cần nhập nội dung !!',
                'data'          => ''
            ));
        }
        $dataInsert = array();
        $phone = str_replace(array(' ',':',';','-','_'), ',', $phone);
        $arrPhone = explode(',', $phone);
        if(!empty($arrPhone)){
            foreach($arrPhone AS $key => $value){
                $dataInsert[] = array(
                    'to_phone' => $value,
                    'content'  => $content,
                    'time_create' => $this->time(),
                    'status'     => 0,
                    'telco'      => Smslib::CheckPhone($value),
                    'time_create' => $this->time()
                );
            }
            $Model  = $LMongo::collection('log_send_sms');
            $insert = $Model->batchInsert($dataInsert);
            
            if($insert){
                //insert queue
                $dataQueue = array(
                    'scenario_id' => 555,
                    'template_id' => 69,
                    'transport_id' => 1,
                    'user_id'   => $userInfo['id'],//ng gui
                    'received'  => $phone,
                    'data'      => json_encode(array('content' => $content)),
                    'time_create' => $this->time(),
                    'status'   => 1,
                    'time_success' => $this->time() + 360
                );
                $insertQueue = QueueModel::insert($dataQueue);
                return Response::json(array(
                    'error'         => false,
                    'message'       => 'Success'
                ));
            }else{
                return Response::json(array(
                    'error'         => true,
                    'message'       => 'Không thể thực hiện!!',
                    'data'          => ''
                ));
            }
        }else{
            return Response::json(array(
                'error'         => true,
                'message'       => 'Không có số điện thoại!!',
                'data'          => ''
            ));
        }
    }
    public function postUpload(){
        $UserInfo   = $this->UserInfo();
        //
        if (Input::hasFile('file')) {
            $File       = Input::file('file');
            $name       = $File->getClientOriginalName();
            $extension  = $File->getClientOriginalExtension();
            $MimeType   = $File->getMimeType();
            $name       = md5($name.$UserInfo['id'].$this->time()).$name;

            if(in_array((string)$extension, array('csv','xls','xlsx')) && in_array((string)$MimeType,array('text/plain','application/vnd.ms-excel','application/vnd.ms-office','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))){
                
                $uploadPath = storage_path(). DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'excel' . DIRECTORY_SEPARATOR . 'oms';
                $File->move($uploadPath, $name);

                $LMongo = new LMongo;
                $id = (string)$LMongo::collection('log_import_create_lading')->insert([
                    'link_tmp'      => $uploadPath. DIRECTORY_SEPARATOR .$name,
                    'link_download' => $this->link_upload.'/excel/oms/'.$name,
                    'user_id'       => (int)$UserInfo['id'],
                    'action'        => array('del' => 0, 'insert' => 1),
                    'name'          => $File->getClientOriginalName(),
                    'time_create'   => $this->time()
                ]);

                if(!empty($id)){
                    if($this->ReadExcel((string)$id)){
                        $contents = array(
                            'error'     => false,
                            'message'   => 'success',
                            'id'        => $id,
                        ); 
                    }else{
                        $contents = array(
                            'error'     => true,
                            'message'   => 'read excel error'
                        ); 
                    }
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'insert log import fail'
                    ); 
                }
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'file invalid'
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'upload fail'
            ); 
        }
        return Response::json($contents);
    }
    //
    function Readexcel($id){
        $UserInfo   = $this->UserInfo();
        $LMongo     = new LMongo;
        $ListImport = $LMongo::collection('log_import_create_lading')->find($id);
        $Data       = Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(3)->select(
            array(2,3)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $DataInsert = $DataQueue = array();
            foreach($Data AS $key => $value){
                if(!empty($value[0]) && !empty($value[1])){
                    $phone = '0'.$value[0];
                    $DataInsert[] = array(
                        'to_phone' => $phone,
                        'content'       => $value[1],
                        'status'     => 0,
                        'telco'      => Smslib::CheckPhone($phone),
                        'time_create'   => time()
                    );
                    $DataQueue[] = array(
                        'scenario_id' => 555,
                        'template_id' => 69,
                        'transport_id' => 1,
                        'user_id'   => $UserInfo['id'],//ng gui
                        'received'  => $phone,
                        'data'      => json_encode(array('content' => $value[1])),
                        'time_create' => time(),
                        'status'   => 1,
                        'time_success' => time() + 360
                    );
                }
            }
            if(!empty($DataInsert)){
                $Model  = $LMongo::collection('log_send_sms');
                $Insert = $Model->batchInsert($DataInsert);
                $InsertQueue = QueueModel::insert($DataQueue);
                 
                if($Insert){
                    return true;
                }
            }
        }
        return false;
    }
    //thong bao qua app
    public function postCreatenoticeapp(){
        $content        = Input::has('content')         ? Input::get('content')               : '';
        $os             = Input::has('os')              ? Input::get('os')                    : '';
        $email          = Input::has('email')           ? Input::get('email')                    : '';
        $link           = Input::has('link')            ? Input::get('link')                    : '';
        $listUserApp = array();

        if($os == ''){
            return Response::json(array(
                'error'         => true,
                'message'       => 'Bạn cần chọn hệ điều hành cần thông báo !!',
                'data'          => ''
            ));
        }
        if($content == ''){
            return Response::json(array(
                'error'         => true,
                'message'       => 'Bạn cần nhập nội dung !!',
                'data'          => ''
            ));
        }
        if($email != ''){
            $email = str_replace(array(' ',':',';','-'), ',', $email);
            $arrEmail = explode(',', $email);
            $listUser = User::whereIn('email',$arrEmail)->get(array('id','email'))->toArray();
            if(!empty($listUser)){
                $listUserId = array();
                foreach($listUser AS $one){
                    $listUserId[] = $one['id'];
                }
                if($os == 'all'){
                    $listUserApp = UserInfoModel::whereIn('user_id',$listUserId)->get(array('user_id','android_device_token','ios_device_token'))->toArray();
                }elseif($os == 'ios'){
                    $listUserApp = UserInfoModel::whereIn('user_id',$listUserId)->where('verified',1)->where('ios_device_token','!=','')->get(array('user_id','ios_device_token'))->toArray();
                }elseif($os == 'android'){
                    $listUserApp = UserInfoModel::whereIn('user_id',$listUserId)->where('verified',1)->where('android_device_token','!=','')->get(array('user_id','android_device_token'))->toArray();
                }
            }else{
                $contents = array(
                    'error'     => false,
                    'message'   => 'User not exist!!!',
                );
            }
        }
        
        if($os == 'android' && $email == ''){
            $listUserApp = UserInfoModel::where('verified',1)->where('android_device_token','!=','')->get(array('user_id','android_device_token'))->toArray();
        }elseif($os == 'ios' && $email == ''){
            $listUserApp = UserInfoModel::where('verified',1)->where('ios_device_token','!=','')->get(array('user_id','ios_device_token'))->toArray();
        }elseif($os == 'all' && $email == ''){
            $listUserApp = UserInfoModel::where('verified',1)->where('android_device_token','!=','')->orWhere('ios_device_token','!=','')->get(array('user_id','android_device_token','ios_device_token'))->toArray();
        }
        if(!empty($listUserApp)){
            $dataInsert = $listIos = $listAndroid = $listUderAndroid = $listUserIos = array();
            foreach($listUserApp AS $one){
                if(!empty($one['android_device_token'])){
                    $listAndroid[] = $one;
                    $listUderAndroid[] = $one['user_id'];
                }
                if(!empty($one['ios_device_token'])){
                    $listUserIos[] = $one['user_id'];
                    $listIos[] = $one;
                }
            }
            if(!empty($listUderAndroid)){
                $listUserActiveAndroid = UserConfigTransportModel::whereIn('user_id',$listUderAndroid)->where('transport_id',5)->where('active',1)->get(array('user_id'))->toArray();
                if(!empty($listUserActiveAndroid)){
                    foreach($listUserActiveAndroid AS $user){
                        foreach($listAndroid AS $one){
                            if($user['user_id'] == $one['user_id']){
                                $dataInsert[] = array(
                                    'data' => json_encode(array('device_token' => $one['android_device_token'],'data' => array('message' => $content,'link' => $link,'type' => 'promotion','title' => $content,'content' => $content,'time' => $this->time()))),
                                    'transport_id' => 5,
                                    'scenario_id' => 100,
                                    'template_id' => 999,
                                    'received' => '',
                                    'user_id' => $one['user_id'],
                                    'os_device' => 'android',
                                    'time_create' => $this->time()
                                );
                            }
                        }
                    }
                }
            }
            if(!empty($listUserIos)){
                $listUserActiveIos = UserConfigTransportModel::whereIn('user_id',$listUserIos)->where('transport_id',5)->where('active',1)->get(array('user_id'))->toArray();
                if(!empty($listUserActiveIos)){
                    foreach($listUserActiveIos AS $user){
                        foreach($listIos AS $one){
                            if($user['user_id'] == $one['user_id']){
                                $dataInsert[] = array(
                                    'data' => json_encode(array('device_token' => $one['ios_device_token'],'message' => $content,'data' => array('link' => $link,'type' => 'promotion','title' => $content,'content' => $content,'time' => $this->time()))),
                                    'transport_id' => 5,
                                    'scenario_id' => 100,
                                    'template_id' => 999,
                                    'received' => '',
                                    'user_id' => $one['user_id'],
                                    'os_device' => 'ios',
                                    'time_create' => $this->time()
                                );
                            }
                        }
                    }
                }
            }
            if(!empty($dataInsert)){
                $insert = QueueModel::insert($dataInsert);
                if($insert){
                    $contents = array(
                        'error'     => false,
                        'message'   => 'Success!!!',
                    );
                }else{
                    $contents = array(
                        'error'     => false,
                        'message'   => 'Not insert!!!',
                    );
                }
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not data!!!',
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not send!!!',
            );
        }
        return Response::json($contents);
    }

    /*public function getSendSms(){
        $LMongo     = new LMongo;
        
        $Data       = Excel::selectSheetsByIndex(0)->load('/data/www/html/storage/template/huy-sms.xls', function($reader) {
              $reader->skip(2)->select(
              array(2,3,4,5,6,7,8,9,10,11,12,13,14)
              )->get()->toArray();
            },'UTF-8')->get()->toArray();

        $dataInsert = [];

        foreach ($Data as $key => $value) {
            $dataInsert[] = array(
                'to_phone' => $phone = str_replace(array(' ',':',';','-','_'), ',', $value[2]),
                'content'  => "Kinh gui quy khach hang! Nhung don hang duoc duyet truoc Tet Nguyen Dan 2016 chua duoc lay hang. ShipChung xin phep huy don hang. Chuc quy khach nam moi Phat Tai - Phat Loc.",
                'time_create' => $this->time(),
                'status'     => 0,
                'telco'      => Smslib::CheckPhone($value[2]),
                'time_create' => $this->time()
            );
        }
        

        $Model  = $LMongo::collection('log_send_sms');
        $insert = $Model->batchInsert($dataInsert);
        
        if($insert){
            return Response::json(array(
                'error'         => false,
                'message'       => 'Success'
            ));
        }else{
            return Response::json(array(
                'error'         => true,
                'message'       => 'Không thể thực hiện!!',
                'data'          => ''
            ));
        }

    }*/
    //get list email reject mandrill
    public function getEmailreject(){
        $LMongo     = new LMongo;
        $LMongo     = $LMongo::collection('log_mandrill');
        $itemPage   = Input::has('limit')       ? Input::get('limit')                       : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')                   : 1;
        $email      = Input::has('email')     ? Input::get('email')                         : '';
        $listData   = [];

        if(!empty($email)){
            $LMongo = $LMongo->whereLike('email', $email, 'im');
        }

        $LMongoTotal = clone $LMongo;
        $total      = $LMongoTotal->count();
        if($total > 0){
            $offset     = ($page - 1)*$itemPage;
            $listData   = $LMongo->skip($offset)->take($itemPage)->orderBy('time_create','desc')->get()->toArray();
        }

        $contents = array(
            'error'     => false,
            'message'       => 'success',
            'data'          => $listData,
            'total'         =>  $total
        );

        return Response::json($contents);
    }
    //up file tao boi hoan cho KH
    public function postUploadindemnify(){
        $UserInfo   = $this->UserInfo();
        //
        if (Input::hasFile('file')) {
            $File       = Input::file('file');
            $name       = $File->getClientOriginalName();
            $extension  = $File->getClientOriginalExtension();
            $MimeType   = $File->getMimeType();
            $name       = md5($name.$UserInfo['id'].$this->time()).$name;

            if(in_array((string)$extension, array('csv','xls','xlsx')) && in_array((string)$MimeType,array('text/plain','application/vnd.ms-excel','application/vnd.ms-office','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))){
                
                $uploadPath = storage_path(). DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'excel' . DIRECTORY_SEPARATOR . 'oms';
                $File->move($uploadPath, $name);

                $LMongo = new LMongo;
                $id = (string)$LMongo::collection('log_import_create_lading')->insert([
                    'link_tmp'      => $uploadPath. DIRECTORY_SEPARATOR .$name,
                    'link_download' => $this->link_upload.'/excel/oms/'.$name,
                    'user_id'       => (int)$UserInfo['id'],
                    'action'        => array('del' => 0, 'insert' => 1),
                    'name'          => $File->getClientOriginalName(),
                    'time_create'   => $this->time(),
                    'status'        => 0,
                    'type' => 99
                ]);

                if(!empty($id)){
                    if($this->ReadExcelIndemnify((string)$id)){
                        $contents = array(
                            'error'     => false,
                            'message'   => 'success',
                            'id'        => $id,
                        ); 
                    }else{
                        $contents = array(
                            'error'     => true,
                            'message'   => 'read excel error'
                        ); 
                    }
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'insert log import fail'
                    ); 
                }
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'file invalid'
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'upload fail'
            ); 
        }
        return Response::json($contents);
    }
    function ReadExcelIndemnify($id){
        $LMongo     = new LMongo;
        $ListImport = $LMongo::collection('log_import_create_lading')->find($id);
        $Data       = Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(3)->select(
            array(2,3,4,5,6)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $DataInsert = array();
            foreach($Data AS $key => $value){
                if(!empty($value[0]) && !empty($value[1]) && !empty($value[2]) && !empty($value[3])){
                    $courier = explode('-', $value[3]);
                    $type = explode('-', $value[2]);
                    $DataInsert[] = array(
                        'type' => $type[0],//dung tinh tien boi hoan
                        'level' => $type[1],//muc do boi hoan
                        'courier_id'       => $courier[0],
                        'courier_name'     => $courier['1'],
                        'status'     => 0,
                        'note'      => $value[4],
                        'description'   => $value[1],
                        'time_create' => $this->time(),
                        'partner'     => $id,
                        'tracking_code' => $value[0]
                    );
                }
            }
            if(!empty($DataInsert)){
                $Model  = $LMongo::collection('log_indemnify');
                $Insert = $Model->batchInsert($DataInsert);
                 
                if($Insert){
                    return true;
                }
            }
        }
        return false;
    }
    //get data import later
    function getListindemnify($id){
        $Model     = LMongo::collection('log_indemnify');
        
        $contents = array(
            'error'     => false,
            'data'      => $Model->where('partner', $id)->where('status',0)->get()->toArray(),
            'total'     => $Model->where('partner', $id)->where('status',0)->count(),
            'message'   => 'success'
        );
        return Response::json($contents);
    }
    //
    function getProcess($id){
        $Data = LMongo::collection('log_indemnify')->where('partner', $id)->where('status',0)->first();
        
        if(!$Data){
            $contents = array(
                'error'     => true,
                'message'   => 'Not exists!!',
                'data'      => array(),
                'code'      => 2
            );
            return Response::json($contents);
        }
        
        //Build data
        
        $InfoOrder = OrdersModel::where('tracking_code',$Data['tracking_code'])->first();
        $InfoOrderDetail = DetailModel::where('order_id',$InfoOrder['id'])->first();
        $InfoUser = User::where('id',$InfoOrder['from_user_id'])->first();
        if($Data['type'] == 1){//mp chuyen hoan
            $amount = $InfoOrderDetail['sc_pch'];
        }elseif($Data['type'] == 2){//mp vuot can
            $amount = $InfoOrderDetail['sc_pvk'];
        }elseif($Data['type'] == 3){//mp van chuyen
            $amount = $InfoOrderDetail['sc_pch'] + $InfoOrderDetail['sc_pvk'] + $InfoOrderDetail['sc_pvc'] - $InfoOrderDetail['sc_discount_pvc'];
        }elseif($Data['type'] == 4){//50% phi van chuyen
            $amount = $InfoOrderDetail['sc_pvc']/2;
        }else{
            $amount = 0;
        }
        $DataInsert = array(
            'user_id' => $InfoOrder['from_user_id'],
            'email'  => $InfoUser['email'],
            'tracking_code' => $Data['tracking_code'],
            'status' => 0,
            'amount' => $amount,
            'time_create' => $this->time(),
            'time_accept' => 0,
            'partner' => $id,
            'description' => $Data['description'],
            'note' => $Data['note']
        );
        $insert = IndemnifyModel::insert($DataInsert);
    
        if($insert){
            $update = LMongo::collection('log_indemnify')->where('_id', new \MongoId($Data['_id']))->update(array('status' => 1));
            $contents = array(
                'error'     => false,
                'message'   => 'Success',
                'data'      => array('total' => 1),
                'code'      => 1
            );
            return Response::json($contents);
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not update data!!!',
                'code'      => 2
            );
            return Response::json($contents);
        }
    }
    //lay ra file boi hoan da up
    public function getListfile(){
        $LMongo     = new LMongo;
        $Data = $LMongo::collection('log_import_create_lading')->where('type',99)->take(20)->orderBy('time_create','desc')->get()->toArray();
        if(!empty($Data)){
            $contents = array(
                'error'     => false,
                'message'   => 'Success',
                'data'      => $Data,
                'code'      => 1
            );
            return Response::json($contents);
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data!!!',
                'code'      => 2
            );
            return Response::json($contents);
        }
    }
    //
    public function getViewcontent(){
        $id   = Input::has('id')       ? (int)Input::get('id')                       : '';
        if($id > 0){
            $data = QueueModel::where('id',$id)->first();
            $items = json_decode($data['data'],1);
            $infoTemplate = TemplateModel::where('id',$data['template_id'])->first();
            $content = DbView::make($infoTemplate)->with($items)->render();
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'content'   => $infoTemplate['description']
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data!!!',
                'content'   => ''
            );
        }
        return Response::json($contents);
    }
}
?>