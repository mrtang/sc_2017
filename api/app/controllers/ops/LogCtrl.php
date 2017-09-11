<?php
namespace ops;
use ordermodel\OrdersModel;
use omsmodel\SellerModel;
use sellermodel\UserInfoModel;
use User;
use QueueModel;

class LogCtrl extends BaseCtrl
{
    public function __construct()
    {

    }

    public function getCreateLading(){
        //
        $UserInfo           = $this->UserInfo();
        if($UserInfo['group'] == 3 && $UserInfo['privilege'] == 3){
            return Response::json(array('error' => true,'message' => Lang::get('response.PERMISSION_DENIED_OPS')));
        }

        
        $LMongo     = new LMongo;
        $LMongo     = $LMongo::collection('log_create_lading');
        $itemPage   = Input::has('limit')       ? Input::get('limit')                   : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')               : 1;
        $scCode     = Input::has('sc_code')     ? Input::get('sc_code')                 : '';

        $ListDistrictId     = $ListWardId       = $ListUserId       = [];

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
                        $ListDistrictId[] = (int)$val['input']['From']['Province'];
                    }
                    if (isset($val['input']['From']['Ward']) && $val['input']['From']['Ward'] > 0) {
                        $ListWardId[] = (int)$val['input']['From']['Ward'];
                    }
                    if ($val['input']['To']['Province'] > 0) {
                        $ListDistrictId[] = (int)$val['input']['To']['Province'];
                    }
                    if (isset($val['input']['To']['Ward']) && $val['input']['To']['Ward'] > 0) {
                        $ListWardId[] = (int)$val['input']['To']['Ward'];
                    }
                }

                if (!empty($val['datalog']) && isset($val['datalog']['order']) && $val['datalog']['order']['from_user_id']) {
                    $ListUserId[] = (int)$val['datalog']['order']['from_user_id'];
                }
            }

            if(!empty($ListDistrictId)){
                $ListDistrictId = $this->getProvince(array_unique($ListDistrictId));
            }

            if(!empty($ListWardId)){
                $ListWardId     = $this->getWard(array_unique($ListWardId));
            }

            if(!empty($ListUserId)){
                $ListUserId     = $this->getUser(array_unique($ListUserId));
            }
        }

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $listData,
            'total'         =>  $total,
            'item_page'     => $itemPage,
            'list_district' => $ListDistrictId,
            'list_ward'     => $ListWardId,
            'list_user'     => $ListUserId
        );

        return Response::json($contents);
    }

    //journey
    public function getJourney(){
        $LMongo     = new LMongo;
        $LMongo     = $LMongo::collection('log_journey_lading');
        $itemPage   = Input::has('limit')       ? Input::get('limit')                       : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')                   : 1;
        $scCode     = Input::has('sc_code')     ? Input::get('sc_code')                     : '';
        $status     = Input::has('status')      ? (int)Input::get('status')                 : 0;

        $courier    = Input::has('courier')  ? (int)Input::get('courier')                   : 0;
        $courier_status = Input::has('courier_status')  ? Input::get('courier_status')      : "";
        $time_start = Input::has('time_start')  ? (int)Input::get('time_start')             : $this->time() - 7 * 86400;
        $time_end   = Input::has('time_end')    ? (int)Input::get('time_end')               : $this->time();

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

        if(!empty($courier_status)){
            $LMongo = $LMongo->where('status', $courier_status);
        }

        if(!empty($courier)){
            $LMongo = $LMongo->where('courier', $courier);
        }

        $LMongo = $LMongo->whereGte('time_create',  $time_start)->whereLte('time_create',$time_end);



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

    public function getChangeOrder(){
        $LMongo     = new LMongo;
        $LMongo     = $LMongo::collection('log_change_order');
        $itemPage   = Input::has('limit')       ? Input::get('limit')                    : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')                : 1;
        $scCode     = Input::has('sc_code')     ? Input::get('sc_code')                  : '';

        if(!empty($scCode)){
            $Model = new OrdersModel;
            $Order = $Model->where('tracking_code', $scCode)->first(['id']);
            if(!isset($Order->id)){
                return Response::json([
                    'error'     => false,
                    'message'   => 'success',
                    'data'      => [],
                    'order'     => [],
                    'user'      => [],
                    'total'     => 0,
                    'item_page' => $itemPage
                ]);
            }
            $LMongo = $LMongo->where('order_id', $Order->id);
        }

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

        return Response::json($contents);
    }

    public function getLogSms(){
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


    public function getLogChangePayment(){
        $LMongo     = new LMongo;
        $LMongo     = $LMongo::collection('log_change_payment');
        $itemPage   = Input::has('limit')       ? Input::get('limit')                       : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')                   : 1;
        $email      = Input::has('email')       ? Input::get('email')                         : '';
        $listData   = [];

        $User = [];

        if(!empty($email)){
            $User = \User::where('email', $email)->first();
        }


        if(!empty($User)){
            $LMongo = $LMongo->where('user_id', $User->id);
        }


        $LMongoTotal = clone $LMongo;
        $total       = $LMongoTotal->count();
        if($total > 0){
            $offset     = ($page - 1)*$itemPage;
            $listData   = $LMongo->skip($offset)->take($itemPage)->orderBy('time_create','desc')->get()->toArray();
        }

        $list_user_id = [];
        foreach ($listData as $key => $value) {
            $list_user_id[] = $value['user_id'];
        }

        $ListUser = [];

        if (!empty($list_user_id)) {
            $Users = \User::whereIn('id', $list_user_id)->select(['id', 'fullname', 'email'])->get()->toArray();
            foreach ($Users as $key => $value) {
                $ListUser[$value['id']]  = ['fullname' => $value['fullname'], 'email'=> $value['email']];
            }
        }




        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $listData,
            'total'         => $total,
            'user'          => $ListUser
        );

        return Response::json($contents);
    }




    /*
     * Export Excel
     */
    private function ExWeight($Data){
        $Courier    = $this->getCourier(false);

        Excel::selectSheetsByIndex(0)->load('/data/www/html/storage/template/ops/log_vuot_can.xls', function($reader) use($Data,$Courier, $Courier) {
            $reader->sheet(0,function($sheet) use($Data,$Courier)
            {
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
        },'UTF-8',true)->export('xls');
    }

    private function ExAcceptReturn($Data){
        $FileName   = 'Danh_sach';
        $Courier    = $this->getCourier(false);

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
                        (isset($val['courier']) && (isset($Courier[(int)$val['courier']])))         ? $Courier[(int)$val['courier']]['name']            : '',
                        isset($val['error_code'])                                                   ? $val['error_code']                        : '',
                        isset($val['messenger'])                                                    ? $val['messenger']                         : '',
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }
    //update hop dong
    public function postContract($id){
        $Data       = Input::json()->all();
        $Contract      = isset($Data['num_contract'])         ? trim($Data['num_contract'])      : null;
        if($Contract != ''){
            $update = UserInfoModel::where('user_id',$id)->update(array('num_contract' => $Contract,'time_active_contract' => $this->time()));
            if($update){
                //gui email thong bao 
                $infoUser = User::where('id',$id)->first();
                $owner = SellerModel::where('user_id',$id)->first();
                $infoOwner = User::where('id',$owner['seller_id'])->first();
                $dataInsert = array(
                    'scenario_id' => 90,
                    'transport_id' => 2,
                    'template_id' => 39,
                    'user_id' => $id,
                    'received' => $infoUser['email'],
                    'data' => json_encode(array(
                        'fullname' => $infoUser['fullname'],
                        'time_active' => date('d/m/Y',$this->time()),
                        'owner' => $infoOwner['fullname']
                    )),
                    'time_create' => $this->time()
                );
                $insert = QueueModel::insertGetId($dataInsert);
                //goi api send
                \Predis\Autoloader::register();
                //Now we can start creating a redis client to publish event'6788', '10.0.20.164'
                $redis = new \Predis\Client(array(
                    "scheme" => "tcp",
                    "host" => "10.0.20.164",
                    "port" => 6788
                ));
                //Now we got redis client connected, we can publish event (send event)
                $redis->publish("SendMail", $insert);

                $contents = array(
                    'error'     => false,
                    'message'   => Lang::get('response.SUCCESS'),
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => Lang::get('response.NOT_UPDATE'),
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => Lang::get('response.NOT_DATA'),
            );
        }
        return Response::json($contents);
    }
}
?>