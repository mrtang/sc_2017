<?php namespace order;
use ordermodel\OrderProcessModel;
use ordermodel\OrderProblemModel;
use Response;
use Input;
use Cache;
use ordermodel\OrdersModel;
use User;

class OrderProcessController extends \BaseController {

    /**
    * @desc : Create order process record
    * @author : ThinhNV
    * @return <Json object>
    */

    public function postCreate(){
        
        $order_id   = Input::has('order_id')    ? (int)Input::get('order_id')      : '';
        /**
        *  @param $action (string) 
        *  chuyenhoan : Gửi yêu cầu chuyển hoàn
        *  phatlai    : Gửi yêu cầu phát lại
        */
        $action     = Input::has('action')      ? trim(Input::get('action'))        : '';
        $note       = Input::has('note')        ? trim(Input::get('note'))          : '';

        $_actionList = array(
            1 => "Gửi yêu cầu giao lại",
            2 => "Xác nhận chuyển hoàn",
            3 => "Gửi yêu cầu vượt cân",
            4 => "Gửi yêu cầu lấy lại hàng",
            5 => "Cập nhật liên lạc"
        );


        // Object return 
        $_ret = array(
            "error"     => true,
            "data"      => array(),
            "message"   => ""
        );
        
        if(empty($order_id) || empty($action) || !array_key_exists($action, $_actionList)){
            $_ret['message'] = "Please check fields ";
            return Response::json($_ret);
        }

        $model = new OrderProcessModel;

        $UserInfo           = $this->UserInfo();
        $user_id            = (int)$UserInfo['id'];
        
        $model->seller_id   = $user_id;
        $model->order_id    = $order_id;
        $model->status      = 1;
        $model->note        = $note;
        $model->time_create = $this->time();
        $model->action      = $_actionList[$action];

        $result = $model->save();

        if($result){
            $_ret['error'] = false;
            $_ret['data']  = $result;
        }else {
            $_ret['message'] = "Server errors";
        }

        return Response::json($_ret);
    }

    public function getIndex() {
        $timeStart = Input::has('from_date')    ?   (int)Input::get('from_date') : 0;
        $timeEnd = Input::has('to_date')        ?   (int)Input::get('to_date') : 0;
        $scCode = Input::has('sc_code')         ?   Input::get('sc_code') : '';
        $itemPage = Input::has('itemPerPage')         ?   (int)Input::get('itemPerPage') : 20;
        $currentPage = Input::has('currentPage')         ?   (int)Input::get('currentPage') : 0;


        if($timeStart == 0) {
            $timeStart = 30*86400;
        }
        if(!empty($scCode)) {
            $OrderIDByTrackingCode = OrdersModel::where('tracking_code',$scCode)->where('time_create','>=',$timeStart)->pluck('id');
        }

        $OrderProcessModel = new OrderProcessModel;
        if(isset($OrderIDByTrackingCode) && !empty($OrderIDByTrackingCode)) {
            $OrderProcessModel = $OrderProcessModel->where('order_id',$OrderIDByTrackingCode);
        }
        $OrderProcessModel = $OrderProcessModel->where('time_create','>=',$timeStart);
        if($timeEnd>0) {
            $OrderProcessModel = $OrderProcessModel->where('time_create','<=',$timeEnd);
        }
        $total = $OrderProcessModel->count();
        $ListOrderProcess = $OrderProcessModel->take($itemPage)->skip($itemPage* ($currentPage-1))->orderBy('time_create','desc')->get();

        //listOrderID
        $ListOrderID = array();
        $ListSellerID = array();
        if(!$ListOrderProcess->isEmpty()) {
            foreach($ListOrderProcess as $k => $OneOrderProcess) {
                $ListOrderID[] = $OneOrderProcess->order_id;
                $ListSellerID[] = $OneOrderProcess->seller_id;
            }

            $ListOrder = OrdersModel::whereIn('id',$ListOrderID)->where('time_create','>=',$timeStart)->get();
            $ListOrderArr = array();
            if(!$ListOrder->isEmpty()) {
                foreach($ListOrder as $OneOrder) {
                    $ListOrderArr[$OneOrder->id] = $OneOrder;
                }
            }

            $ListSeller = User::whereIn('id',$ListSellerID)->get();

            $ListSellerArr = array();
            if(!$ListSeller->isEmpty()) {
                foreach ($ListSeller as $OneSeller) {
                    $ListSellerArr[$OneSeller->id] = $OneSeller;
                }
            }
            foreach($ListOrderProcess as $k => $OneOrderProcess) {
                $ListOrderProcess[$k]->user = isset($ListSellerArr[$OneOrderProcess->seller_id]) ? $ListSellerArr[$OneOrderProcess->seller_id] : '';
                $ListOrderProcess[$k]->order = isset($ListOrderArr[$OneOrderProcess->order_id]) ? $ListOrderArr[$OneOrderProcess->order_id] :'';
            }
            $Response = [
                'error' =>  false,
                'data'  =>  $ListOrderProcess,
                'total' =>  $total
            ];
        } else {
            $Response = [
                'error' =>  true,
                'message'   =>  'Không có dữ liệu'
            ];
        }

        return Response::json($Response);

    }

    public function postIndex() {
        $UserInfo = $this->UserInfo();

        $scCode = Input::has('sc_code') ?   Input::get('sc_code'): '';
        $orderID = Input::has('order_id') ?   (int)Input::get('order_id'): 0;
        $status = Input::has('status')  ?   (int)Input::get('status') : '';
        $action = Input::has('action')  ?   Input::get('action') : '';

        $TimeStart = $this->time() - 90*38600;
        if($orderID==0) {
            $scCode = OrdersModel::where('tracking_code',$scCode)->where('time_create','>=',$TimeStart)->first();
        }
        if(empty($scCode) && $orderID==0) {
            $Response = [
                'error'     =>  true,
                'message'   =>  'Không tìm thấy vận đơn này'
            ];
        } else {
            $OrderProcess = new OrderProcessModel();
            if($orderID>0) {
                $OrderProcess->order_id = $orderID;
            } else {
                $OrderProcess->order_id = $scCode->id;
            }
            $OrderProcess->seller_id = $UserInfo['id'];
            $OrderProcess->status       =   $status;
            $OrderProcess->note       =   $action;
            $OrderProcess->time_create  =   $this->time();
            $OrderProcess->save();
            $Response = [
                'error' =>  false,
                'order_process_id'  =>  $OrderProcess->id
            ];
        }
        return Response::json($Response);
    }

    public function getHistory($OrderID) {

        $orderProcessModel = new OrderProcessModel;
        $listOrderProcess = $orderProcessModel->where('order_id',$OrderID)->orderBy('time_create','desc')->get();

        if(!$listOrderProcess->isEmpty()) {
            $listSeller = array();

            foreach($listOrderProcess as $oneOrderProcess) {
                if(!in_array($oneOrderProcess->seller_id,$listSeller)) {
                    $listSeller[] = $oneOrderProcess->seller_id;
                }
            }

            //get list seller
            $listUsers = User::whereIn('id',$listSeller)->get();
            $listUsersArr = [];
            if(!$listUsers->isEmpty()) {
                foreach($listUsers as $oneUser) {
                    $listUsersArr[$oneUser->id] = $oneUser;
                }
            }

            foreach($listOrderProcess as $k=> $oneOrderProcess) {
                $listOrderProcess[$k]->user = isset($listUsersArr[$oneOrderProcess->seller_id]) ? $listUsersArr[$oneOrderProcess->seller_id] : '';
            }
            $response = [
                'error' =>  false,
                'data'  =>  $listOrderProcess
            ];
        } else {
            $response = [
                'error' =>  true,
                'message'   =>  'Không có dữ liệu'
            ];
        }
        return Response::json($response);
    }



    /**
     Get list Process
     */
    public function getOrderProcess(){
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $itemPage           = Input::has('limit')               ? (int)Input::get('limit')                      : 10;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';
        $Option             = Input::has('option')              ? (int)Input::get('option')                     : 0;
        $Status             = Input::has('status')              ? Input::get('status')                          : null;
        $Search             = Input::has('search')              ? strtoupper(trim(Input::get('search')))        : '';

        $UserInfo           = $this->UserInfo();
        $Total              = 0;
        $Data               = 0;
        $ListOrderId        = [];

        $TimeStart          = strtotime(date('Y-m-1', strtotime('-2 months')));
        $Model              = \ordermodel\OrderProblemModel::where('time_create','>=', $TimeStart)
                                         ->where('user_id', $UserInfo['id'])->where('active',1);

        if(!empty($Search)){
            if(preg_match("/^SC/i", $Search)){
                $Model  = $Model->where('tracking_code', $Search);
            }else{
                $ListOrder = \ordermodel\OrdersModel::where('time_accept', '>=', $TimeStart - 86400*60)
                                                    ->where('from_user_id', $UserInfo['id']);
                if(preg_match("/^0d+/i", $Search)){
                    $ListOrder  = $ListOrder->where('to_phone','LIKE','%'.$Search.'%');
                }else{
                    $ListOrder  = $ListOrder->where('order_code', $Search);
                }

                $ListOrder  = $ListOrder->lists('id');

                if(!empty($ListOrder)){
                    $Model = $Model->whereIn('order_id', $ListOrder);
                }else{
                    return Response::json([
                        'error'                 => false,
                        'message'               => 'success',
                        'total'                 => 0,
                        'data'                  => []
                    ]);
                }
            }

        }

        $ModelTotal = clone  $Model;
        //Count Group
        $TotalGroup = $ModelTotal->groupBy('type')->groupBy('status')->get(array('type','status',\DB::raw('count(*) as count')))->toArray();

        if(!empty($Option)){
            $Model  = $Model->where('type', $Option);
        }

        if(isset($Status)){
            $Status = explode(',',$Status);
            $Model          = $Model->whereIn('status',$Status);
            unset($Status);
        }

        $DataGroup  = [];
        foreach($TotalGroup as $val){
            $DataGroup[$val['type']][$val['status']]    = $val['count'];
        }

        $Data           = [];
        $ModelTotal     = clone  $Model;
        $Total          = $ModelTotal->count();
        if($Total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Data       = $Model->skip($offset)->take($itemPage)->with(['Order' => function($query) use($TimeStart){
                $query->where('time_accept', '>=', $TimeStart - 86400*60)->with(['ToOrderAddress', 'MetaStatus', 'ToCountry']);
            },'OrderDetail','OrderStatus' => function($query){$query->with(['MetaStatus']);}])->orderBy('time_create','desc')->get()->toArray();
        }

        $contents = array(
            'error'                 => false,
            'message'               => 'success',
            'total'                 => $Total,
            'data'                  => $Data,
            'total_group'           => $DataGroup
        );

        return Response::json($contents);
    }

    public function getPipeJourneyDetail(){
        $Status             = Input::has('status')          ? Input::get('status')                              : 0;
        $Group              = Input::has('group')           ? (int)Input::get('group')                          : 0;
        $TrackingCode       = Input::has('tracking_code')   ? strtoupper(trim(Input::get('tracking_code')))     : '';

        $PipeJourneyModel   = new \omsmodel\PipeJourneyModel;
        $PipeJourneyModel   = $PipeJourneyModel::where('time_create','>=', time() - 86400*30)
                                               ->where('tracking_code',$TrackingCode)
                                               ->where('type',1)
                                               ->where('group_process', $Group);

        if(!empty($Status)){
            $Status             = explode(',',$Status);
            $PipeJourneyModel   = $PipeJourneyModel->whereIn('pipe_status', $Status);
        }

        return Response::json([
            'error'                 => false,
            'message'               => 'success',
            'data'                  => $PipeJourneyModel->with(['User', 'PipeStatus' => function($query) use($Group){
                $query->where('type',1)->where('group_status',$Group);
            }])->orderBy('time_create','ASC')->get(['user_id','note','time_create','report_courier','pipe_status'])->toArray()
        ]);
    }

    public function postCreateJourney(){
        $UserInfo = $this->UserInfo();
        Input::merge(Input::json()->all());

        $validation = \Validator::make(Input::all(), array(
            'tracking_code' => 'required',
            'group'         => 'required|in:29,31',
            'pipe_status'   => 'required|in:707,903',
            'option'        => 'required|in:1,2,3,4,5', // Truong hop xu ly cua don (phat ko thanh cong, cho xác nhan hoan, vuot can...)
            'action'        => 'required|in:1,2,3,4,5' // Loai hanh dong (giao lai, xac nhan hoan, dong ý vuot can ....)
        ));

        //error
        if ($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $data               = Input::all();
        $PipeStatus         = (isset($data['pipe_status']))     ? (int)$data['pipe_status']         : 0;
        $Group              = (isset($data['group']))           ? (int)$data['group']               : 0;
        $TrackingCode       = (isset($data['tracking_code']))   ? $data['tracking_code']            : "";
        $Note               = (isset($data['note']))            ? trim($data['note'])               : "";
        $Option             = (isset($data['option']))          ? (int)$data['option']              : 1;
        $Action             = (isset($data['action']))          ? (int)$data['action']              : 1;
        $TimeStock          = (isset($data['time_store']))          ? (int)$data['time_store']              : 0;
        $SendZalo           = (isset($data['send_zalo']))       ? $data['send_zalo']                : 0;
        //Check Order
        $Order              = \ordermodel\OrdersModel::where('id',$TrackingCode)->where('from_user_id', $UserInfo['id'])
                                                     ->where('time_accept','>=', time() - 86400*90)
                                                     ->first(['id','tracking_code','product_name','to_phone']);
        if(!isset($Order->id)){
            return Response::json([
                'error'         => true,
                'message'       => 'UPDATE_ERROR',
                'error_message' => 'Đơn hàng không tồn tại'
            ]);
        }

        $Process = \ordermodel\OrderProblemModel::where('order_id',$TrackingCode)->where('active',1)->where('type',$Option)
                                                ->where('status',0)->where('user_id', $UserInfo['id'])->first();
        if(!isset($Process->id)){
            return Response::json([
                'error'         => true,
                'message'       => 'UPDATE_ERROR',
                'error_message' => 'Mã xử lý không tồn tại'
            ]);
        }
        if($TimeStock > 0){
            $Note = 'Lưu kho đến ngày: '.date('d/m/Y',$TimeStock) .". ".$Note;
        }

        $Jouney = new \omsmodel\PipeJourneyModel;
        if($Jouney->where('tracking_code', $TrackingCode)->where('type',1)->where('group_process', $Group)->where('pipe_status', $PipeStatus)->count() > 0){
            try{
                $Process->status = 1;
                $Process->action = $Action;
                $Process->zms    = $SendZalo;
                $Process->time_update = time();
                $Process->save();
            }catch (Exception $e){
                return Response::json([
                    'error'         => true,
                    'message'       => 'UPDATE_ERROR',
                    'error_message' => 'Cập nhật thất bại'
                ]);
            }

            return Response::json([
                'error'         => false,
                'message'       => 'SUCCESS',
                'error_message' => 'Yêu cầu đã được gửi , không thể gửi thêm.'
            ]);
        }

        try{
            \omsmodel\PipeJourneyModel::insert ([
                'user_id'           => (int)$UserInfo['id'],
                'tracking_code'     => $TrackingCode,
                'type'              => 1,
                'group_process'     => $Group,
                'pipe_status'       => $PipeStatus,
                'note'              => $Note,
                'time_store_stock'  => $TimeStock,
                'time_create'       => time()
            ]);

            $Process->status        = 1;
            $Process->action        = $Action;
            $Process->zms           = $SendZalo;
            $Process->time_update   = time();
            $Process->save();

            if($SendZalo == 1){
                if(in_array($Option, [1,2]) && $Action == 1){
                    // Phát thất bại - yêu cầu phát lại - gửi zalo cho người nhận
                    $ZaloCtrl = new \ZmsController();
                    $ZaloCtrl->ZaloProcessDeliveryFail($UserInfo['id'], $Order->to_phone, $Order->tracking_code, $Order->product_name, $Process->postman_name, $Process->postman_phone);
                    $Process->zms           = 2;
                    $Process->time_send_zms = time();
                    $Process->save();
                }
            }

        }catch (Exception $e){
            return Response::json([
                'error'         => true,
                'message'       => 'INSERT_ERROR',
                'error_message' => 'Cập nhật thất bại'
            ]);
        }

        return Response::json([
            'error'             => false,
            'message'           => 'SUCCESS',
            'error_message'     => 'Thành công'
        ]);
    }

    //luu kho cho CS
    public function postCreateJourneyCs(){
        $UserInfo = $this->UserInfo();
        Input::merge(Input::json()->all());

        $validation = \Validator::make(Input::all(), array(
            'tracking_code' => 'required',
            'group'         => 'required|in:29,31',
            'pipe_status'   => 'required|in:707,903',
            'option'        => 'required|in:1,2,3,4,5', // Truong hop xu ly cua don (phat ko thanh cong, cho xác nhan hoan, vuot can...)
            'action'        => 'required|in:1,2,3,4,5' // Loai hanh dong (giao lai, xac nhan hoan, dong ý vuot can ....)
        ));

        //error
        if ($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $data               = Input::all();
        $PipeStatus         = (isset($data['pipe_status']))     ? (int)$data['pipe_status']         : 0;
        $Group              = (isset($data['group']))           ? (int)$data['group']               : 0;
        $TrackingCode       = (isset($data['tracking_code']))   ? $data['tracking_code']            : "";
        $Note               = (isset($data['note']))            ? trim($data['note'])               : "";
        $Option             = (isset($data['option']))          ? (int)$data['option']              : 1;
        $Action             = (isset($data['action']))          ? (int)$data['action']              : 1;
        $TimeStock          = (isset($data['time_store']))          ? (int)$data['time_store']              : 0;
        $SendZalo           = (isset($data['send_zalo']))       ? $data['send_zalo']                : 0;
        //Check Order
        $Order              = \ordermodel\OrdersModel::where('id',$TrackingCode)
                                                     ->where('time_accept','>=', time() - 86400*90)
                                                     ->first(['id','tracking_code','product_name','to_phone','from_user_id']);
        if(!isset($Order->id)){
            return Response::json([
                'error'         => true,
                'message'       => 'UPDATE_ERROR',
                'error_message' => 'Đơn hàng không tồn tại'
            ]);
        }

        $Process = \ordermodel\OrderProblemModel::where('order_id',$TrackingCode)->where('active',1)->where('type',$Option)
                                                ->where('status',0)->first();
        if(!isset($Process->id)){
            return Response::json([
                'error'         => true,
                'message'       => 'UPDATE_ERROR',
                'error_message' => 'Mã xử lý không tồn tại'
            ]);
        }
        if($TimeStock > 0){
            $Note = 'Lưu kho đến ngày: '.date('d/m/Y',$TimeStock) .". ".$Note;
        }

        $Jouney = new \omsmodel\PipeJourneyModel;
        if($Jouney->where('tracking_code', $TrackingCode)->where('type',1)->where('group_process', $Group)->where('pipe_status', $PipeStatus)->count() > 0){
            try{
                $Process->status = 1;
                $Process->action = $Action;
                $Process->zms    = $SendZalo;
                $Process->time_update = time();
                $Process->save();
            }catch (Exception $e){
                return Response::json([
                    'error'         => true,
                    'message'       => 'UPDATE_ERROR',
                    'error_message' => 'Cập nhật thất bại'
                ]);
            }

            return Response::json([
                'error'         => false,
                'message'       => 'SUCCESS',
                'error_message' => 'Yêu cầu đã được gửi , không thể gửi thêm.'
            ]);
        }

        try{
            \omsmodel\PipeJourneyModel::insert ([
                'user_id'           => (int)$UserInfo['id'],
                'tracking_code'     => $TrackingCode,
                'type'              => 1,
                'group_process'     => $Group,
                'pipe_status'       => $PipeStatus,
                'note'              => $Note,
                'time_store_stock'  => $TimeStock,
                'time_create'       => time()
            ]);

            $Process->status        = 1;
            $Process->action        = $Action;
            $Process->zms           = $SendZalo;
            $Process->time_update   = time();
            $Process->save();

            if($SendZalo == 1){
                if(in_array($Option, [1,2]) && $Action == 1){
                    // Phát thất bại - yêu cầu phát lại - gửi zalo cho người nhận
                    $ZaloCtrl = new \ZmsController();
                    $ZaloCtrl->ZaloProcessDeliveryFail($Order['from_user_id'], $Order->to_phone, $Order->tracking_code, $Order->product_name, $Process->postman_name, $Process->postman_phone);
                    $Process->zms           = 2;
                    $Process->time_send_zms = time();
                    $Process->save();
                }
            }

        }catch (Exception $e){
            return Response::json([
                'error'         => true,
                'message'       => 'INSERT_ERROR',
                'error_message' => 'Cập nhật thất bại'
            ]);
        }

        return Response::json([
            'error'             => false,
            'message'           => 'SUCCESS',
            'error_message'     => 'Thành công'
        ]);
    }

    public function postChangeOrder(){
        $UserInfo = $this->UserInfo();
        Input::merge(Input::json()->all());

        $validation = \Validator::make(Input::all(), array(
            'tracking_code' => 'required',
            'status'        => 'required|in:28,38,61',
            'option'        => 'required|in:1,2,3,4,5',
            'action'        => 'required|in:1,2,3,4,5',
            'courier'       => 'required'
        ));

        //error
        if ($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $data               = Input::all();
        $TrackingCode       = (isset($data['tracking_code']))   ? $data['tracking_code']    : "";
        $Option             = (isset($data['option']))          ? (int)$data['option']      : 1;
        $Action             = (isset($data['action']))          ? (int)$data['action']      : 1;
        $Courier            = (isset($data['courier']))         ? (int)$data['courier']     : 1;
        $Status             = (isset($data['status']))          ? (int)$data['status']      : 1;
        $Note               = (isset($data['note']))            ? $data['note']             : "Người bán xác nhận chuyển hoàn";

        $OrderProblem       = \ordermodel\OrderProblemModel::where('tracking_code',$TrackingCode)->where('user_id', $UserInfo['id'])
                                                           ->where('active',1)->whereIn('status',[0,1,3])->where('type',$Option)
                                                           ->first();
        if(!isset($OrderProblem)){
            return Response::json([
                'error'         => true,
                'message'       => 'UPDATE_ERROR',
                'error_message' => 'Bạn không được sửa đơn hàng này'
            ]);
        }

        $CourierAcceptJourney   = new \trigger\CourierAcceptJourney;
        Input::merge([
            'sc_code'   =>  $TrackingCode,
            'city'      => 'Khách hàng SC',
            'note'      => $Note,
            'courier'   => $Courier
        ]);

        $Update = $CourierAcceptJourney->postAcceptstatus();
        if(is_object($Update)){
            $Update = json_decode($Update->getContent(),1);
        }

        $StatusProcess = 1;
        if($Status == 28){
            $StatusProcess  = 2;
        }elseif($Status == 61){
            if($OrderProblem->status == 3){
                $StatusProcess  = 3;
            }
        }

        if(!$Update['_error']){
            try{
                $OrderProblem->status       = $StatusProcess;
                $OrderProblem->action       = $Action;
                $OrderProblem->time_update  = time();
                $OrderProblem->save();
            }catch (Exception $e){
                return Response::json([
                    'error'         => true,
                    'message'       => 'INSERT_ERROR',
                    'error_message' => 'Cập nhật thất bại'
                ]);
            }

        }else{
            return Response::json([
                'error'         => true,
                'message'       => 'INSERT_ERROR',
                'error_message' => 'Cập nhật thất bại'
            ]);
        }

        return Response::json([
            'error'             => false,
            'message'           => 'SUCCESS',
            'error_message'     => 'Thành công'
        ]);
    }

    public function postAcceptOverWeight(){
        $UserInfo = $this->UserInfo();
        Input::merge(Input::json()->all());

        $validation = \Validator::make(Input::all(), array(
            'tracking_code' => 'required',
        ));

        //error
        if ($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $data               = Input::all();
        $TrackingCode       = (isset($data['tracking_code']))   ? $data['tracking_code']    : "";

        try{
            \ordermodel\OrderProblemModel::where('tracking_code',$TrackingCode)->where('user_id', $UserInfo['id'])
                ->where('active',1)->where('type',4)->where('status',0)
                ->update(['status' => 2, 'action' => 1, 'time_update' => time()]);

        }catch (Exception $e){
            return Response::json([
                'error'         => true,
                'message'       => 'INSERT_ERROR',
                'error_message' => 'Cập nhật thất bại'
            ]);
        }

        return Response::json([
            'error'             => false,
            'message'           => 'SUCCESS',
            'error_message'     => 'Thành công'
        ]);
    }

    //Confirm Pickup
    public function postConfirmPickup(){
        $UserInfo = $this->UserInfo();
        Input::merge(Input::json()->all());

        $validation = \Validator::make(Input::all(), array(
            'tracking_code' => 'required',
            'group'         => 'required|in:109',
            'pipe_status'   => 'required|in:1',
            'option'        => 'required|in:3',
            'action'        => 'required|in:1'
        ));

        //error
        if ($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $data               = Input::all();
        $TrackingCode       = (isset($data['tracking_code']))   ? $data['tracking_code']            : "";
        $Note               = (isset($data['note']))            ? trim($data['note'])               : "";
        $Option             = (isset($data['option']))          ? (int)$data['option']              : 1;
        $Action             = (isset($data['action']))          ? (int)$data['action']              : 1;

        $Order = \ordermodel\OrdersModel::where('time_accept', '>=', time() - 86400*60)
            ->where('from_user_id', $UserInfo['id'])->where('id', $TrackingCode)->first(['id','courier_id','tracking_code']);
        if(!isset($Order->id)){
            return Response::json([
                'error'         => true,
                'message'       => 'ERROR',
                'error_message' => 'Không tìm thấy vận đơn'
            ]);
        }

        $CourierAcceptJourney   = new \trigger\CourierAcceptJourney;
        Input::merge([
            'sc_code'   =>  $Order->tracking_code,
            'city'      => 'Khách hàng SC',
            'note'      => $Note,
            'courier'   => $Order->courier_id,
            'status'    => 38
        ]);

        $Update = $CourierAcceptJourney->postAcceptstatus();
        if(is_object($Update)){
            $Update = json_decode($Update->getContent(),1);
        }

        if(!$Update['_error']){
            try{
                \ordermodel\OrderProblemModel::where('order_id',$TrackingCode)->where('active',1)->where('status',0)
                                             ->where('type',$Option)->where('user_id', $UserInfo['id'])
                                             ->update(['status' => 1,'action' => $Action, 'zms' => 0, 'time_update' => time()]);
            }catch (Exception $e){
                return Response::json([
                    'error'         => true,
                    'message'       => 'INSERT_ERROR',
                    'error_message' => 'Cập nhật thất bại'
                ]);
            }

        }else{
            return Response::json([
                'error'         => true,
                'message'       => 'INSERT_ERROR',
                'error_message' => 'Cập nhật thất bại'
            ]);
        }



        return Response::json([
            'error'             => false,
            'message'           => 'SUCCESS',
            'error_message'     => 'Thành công'
        ]);
    }
    // cap nhat ngay luu kho KH chon
    public function postSavestock(){
        $UserInfo = $this->UserInfo();
        Input::merge(Input::json()->all());
        $data               = Input::all();
        $OrderId       = (isset($data['order_id']))   ? $data['order_id']            : 0;
        if($OrderId > 0){
            $Update = OrderProblemModel::where('order_id',$OrderID)->update(array('time_store_stock' => time()));
            if($Update){
                return Response::json([
                    'error'             => false,
                    'message'           => 'SUCCESS',
                    'error_message'     => 'Thành công'
                ]);
            }else{
                return Response::json([
                    'error'         => true,
                    'message'       => 'UPDATE_ERROR',
                    'error_message' => 'Cập nhật thất bại'
                ]);
            }
        }else{
            return Response::json([
                'error'         => true,
                'message'       => 'UPDATE_ERROR',
                'error_message' => 'Không thể cập nhật'
            ]);
        }
    }
}
