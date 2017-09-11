<?php namespace accounting;

use ordermodel\VerifyFreezeModel;
use ordermodel\VerifyModel;
use ordermodel\OrdersModel;
use accountingmodel\MerchantModel;
use accountingmodel\TransactionModel;

class VerifyCtrl extends BaseCtrl {

    private $table_log      = 'log_verify';
    private $table_import   = 'log_import_verify';

    private $data           = [];
    private $total          = 0;
    private $user           = [];

    private $message_error  = '';
    private $Status;
    private $Active;
    private $master_id      = 1;

    public  function __construct(){

    }

    /**
     * get list  system
     */

    public function getIndex()
    {
        $itemPage       = 20; 

        $page               = Input::has('page')            ? (int)Input::get('page')                : 1;
        $TimeCreateStart    = Input::has('create_start')    ? trim(Input::get('create_start'))      : '';
        $TimeCreateEnd      = Input::has('create_end')      ? trim(Input::get('create_end'))        : '';
        $TimeAcceptStart    = Input::has('accept_start')    ? trim(Input::get('accept_start'))      : '';
        $TimeAcceptEnd      = Input::has('accept_end')      ? trim(Input::get('accept_end'))        : '';
        $FirstShipmentStart = Input::has('first_shipment_start')    ? trim(Input::get('first_shipment_start'))  : '';
        $Status             = Input::has('status')          ? (int)Input::get('status')            : 0;
        $KeyWord            = Input::has('keyword')         ? trim(Input::get('keyword'))           : '';
        $TypePayment        = Input::has('type_payment')    ? (int)Input::get('type_payment')       : 0;
        $Type               = Input::has('type')            ? (int)Input::get('type')               : 0;
        $Loyalty            = Input::has('loyalty')         ? (int)Input::get('loyalty')        : null;
        $cmd                = Input::has('cmd')             ? strtolower(trim(Input::get('cmd')))   : '';

        $Model = new VerifyModel;
        $Data       = [];
        $ListUser   = [];
        $User       = [];

        if(isset($Loyalty)){
            $ListUser       = \loyaltymodel\UserModel::where('level',$Loyalty)->remember(60)->lists('user_id');

            // ko có dữ liệu , return []
            if(empty($ListUser)){
                return $this->ResponseData(false);
            }
        }

        if(!empty($TimeCreateStart)){
            $Model = $Model->where('time_create','>=',$TimeCreateStart);
        }

        if(!empty($TimeCreateEnd)){
            $Model = $Model->where('time_create','<',$TimeCreateEnd);
        }

        if(!empty($TimeAcceptStart)){
            $Model = $Model->where('time_accept','>=',$TimeAcceptStart);
        }

        if(!empty($TimeAcceptEnd)){
            $Model = $Model->where('time_accept','<',$TimeAcceptEnd);
        }

        if(!empty($Status)){
            if($Status == 1){
                $Model = $Model->where('status','SUCCESS');
            }else{
                $Model = $Model->where('status','WAITING');
            }
        }

        if(!empty($Type)){
            $Model = $Model->where('type',$Type);
        }

        if($TypePayment > 0){
            $Model = $Model->where('type_payment',$TypePayment);
        }

        // search

        if(!empty($KeyWord)){
            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $UserModel          = \User::where('email',$KeyWord);
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT)){  // search phone
                if(preg_match('/^0\d+$/i',$KeyWord)){
                    $UserModel          = \User::where('phone',$KeyWord);
                }else{
                    $Model  = $Model->where('id',(int)$KeyWord);
                }
            }else{ // search code
                $UserModel          = \User::where('fullname',$KeyWord);
            }

            if(isset($UserModel)){
                $ListUserSearch = $UserModel->lists('id');
                if(empty($ListUserSearch)){
                    return $this->ResponseData(false);
                }else{
                    if(!empty($ListUser)){
                        $ListUser   = array_intersect($ListUser, $ListUserSearch);
                    }else{
                        $ListUser   = $ListUserSearch;
                    }
                }

                if(empty($ListUser)){
                    return $this->ResponseData(false);
                }
            }
        }

        if(!empty($FirstShipmentStart)){
            $User = $this->__get_user_boxme($FirstShipmentStart);
            if(!empty($ListUser)){
                $ListUser   = array_intersect($ListUser, $User);
            }else{
                $ListUser   = $User;
            }

            if(empty($ListUser)){
                return $this->ResponseData(false);
            }
        }

        if(!empty($ListUser)){
            $Model  = $Model->whereRaw("user_id in (". implode(",", $ListUser) .")");
        }

        $Model = $Model->orderBy('time_create','DESC');

        if($cmd == 'export'){
            return $this->ExportExcel($Model);
        }

        $ModelTotal = clone $Model;
        $Total      = $ModelTotal->count();
        $User       = [];
        if($Total > 0){
            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model      = $Model->skip($offset)->take($itemPage);
            }
            $Data   = $Model->get()->toArray();

            if(!empty($Data)){
                $ListUserId = [];
                foreach($Data as $val){
                    $ListUserId[]   = (int)$val['user_id'];
                    $ListUserId[]   = (int)$val['accept_id'];
                }

                if(!empty($ListUserId)){
                    $ListUserId = array_unique($ListUserId);
                    $UserModel  = new \User;
                    $User = $UserModel::whereIn('id',$ListUserId)->get(array('id','email','fullname','phone'));
                }
            }
        }

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'item_page'     => $itemPage,
            'total'         => $Total,
            'data'          => $Data,
            'user'          => $User
        );

        return Response::json($contents);
    }

    private function ExportExcel($Model){
        $Data   = [];
        if(!empty($Model)){
            $Model->with('User')->chunk('1000', function($query) use(&$Data){
                foreach($query as $val){
                    $val                = $val->toArray();
                    $Data[]             = $val;
                }
            });
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        ]);

        /*
         Excel::selectSheetsByIndex(0)->load('/data/www/html/storage/template/accounting/bang-ke-thanh-toan.xls', function($reader) use($Data) {
            $reader->sheet(0,function($sheet) use($Data)
            {
                $i = 1;
                foreach ($Data as $val) {

                    $dataExport = array(
                        $i++,
                        (int)$val['id'],
                        date("d/M/y H:i:s",$val['time_create']),
                        isset($val['user']['fullname']) ? $val['user']['fullname'] : '',
                        isset($val['user']['email'])    ? $val['user']['email']     : '',
                        (string)$val['account'],
                        (string)$val['acc_name'],
                        (string)$val['acc_number'].' ',
                        $val['type_payment'] == 1 ? 'Ngân Lượng' : 'Ví Vimo',
                        number_format($val['total_fee']),
                        number_format($val['total_money_collect']),
                        number_format(trim($val['balance'])),
                        number_format(trim($val['config_balance'])),
                        number_format(trim($val['balance_available'] - $val['config_balance'])),
                        $val['total_money_collect'] + $val['balance'] - $val['total_fee'] + (($val['balance_available'] - $val['config_balance']) < 0 ? ($val['balance_available'] - $val['config_balance']) : 0),
                        isset($this->list_status[$val['status']])   ? $this->list_status[$val['status']] : ''

                    );
                    $sheet->appendRow($dataExport);
                }
            });
        },'UTF-8',true)->export('xls');
         */
    }

    private function ResponseData($error){
        $Cmd                = Input::has('cmd')                 ? strtolower(trim(Input::get('cmd')))                   : '';

        if($Cmd == 'export'){
            return $this->ExportExcel([]);
        }

        return Response::json([
            'error'         => $error,
            'code'          => 'success',
            'error_message' => 'Thành công',
            'item_page'     => 20,
            'total'         => $this->total,
            'data'          => $this->data,
            'user'          => $this->user
        ]);
    }

    public function postUpload(){
        $UploadCtrl                     = new UploadCtrl;
        $UploadCtrl->table              = $this->table_import;
        $UploadCtrl->type               = 'merchant';
        $UploadCtrl->link_stogare       = storage_path(). DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR .'excel'. DIRECTORY_SEPARATOR .'verify'. DIRECTORY_SEPARATOR .date("Y_m_d");
        $UploadCtrl->link_download      = $this->link_upload.'/excel/verify/'.date("Y_m_d").'/';
        $Upload                         = $UploadCtrl->Upload();

        if($Upload['error']){
            return Response::json($Upload);
        }

        $IdLog  = $Upload['id'];
        return $this->ExcelMerchant((string)$IdLog);
    }

    function ExcelMerchant($id){
        $LMongo     = new \LMongo;
        $ListImport = $LMongo::collection($this->table_import)->find($id);
        $Data       = \Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(1)->select(
                array(2,3,4,5,6)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $DataInsert = array();

            foreach($Data as $key => $val){
                if(!empty($val[0]) && isset($val[4])){
                    $DataInsert[(int)$val[0]] = array(
                        'partner'           => $id,
                        'status'            => 'WAITING',
                        'active'            => 0,
                        'request_id'        => (int)$val[0],
                        'acc_number'        => trim($val[1]),
                        'account'           => strtolower(trim($val[2])),
                        'transaction_id'    => trim($val[3]),
                        'amount'            => (int)str_replace(array(',','.'),'',trim($val[4])),
                        'time_create'       => $this->time()
                    );
                }
            }

            if(!empty($DataInsert)){
                $ListModel  = $LMongo::collection('log_verify');
                try{
                    $Insert = $ListModel->batchInsert($DataInsert);
                    return Response::json([
                        'error'             => false,
                        'message'           => 'SUCCESS',
                        'message_error'     => 'Thành công',
                        'id'                => $id
                    ]);
                }catch (Exception $e){
                    return Response::json([
                        'error'             => true,
                        'message'           => 'INSERT_LOG_FAIL',
                        'message_error'     => $e->getMessage()
                    ]);
                }
            }

        }

        return Response::json([
            'error'             => true,
            'message'           => 'EMPTY',
            'message_error'     => 'File dữ liệu trống'
        ]);
    }

    public function getShowFreeze($id)
    {
        $page               = Input::has('page')            ? (int)Input::get('page')                           : 1;
        $TrackingCode       = Input::has('tracking_code')   ? strtoupper(trim(Input::get('tracking_code')))     : '';

        $itemPage           = 20;

        $VerifyFreezeModel  = VerifyFreezeModel::where('verify_id',(int)$id);

        if(!empty($TrackingCode)){
            $VerifyFreezeModel  = $VerifyFreezeModel->where('tracking_code',$TrackingCode);
        }

        $ModelTotal = clone $VerifyFreezeModel;
        $Total      = $ModelTotal->count();

        $offset                 = ($page - 1)*$itemPage;
        $VerifyFreezeModel      = $VerifyFreezeModel->skip($offset)->take($itemPage);


        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $VerifyFreezeModel->get()->toArray(),
            'total'         => $Total

        );

        return Response::json($contents);
    }



    /**
     * get verify detail
     */
    public function getVerifyDetail($id){
        $page       = Input::has('page')            ? (int)Input::get('page')       : 1;
        $Search     = Input::has('search')          ? trim(Input::get('search'))    : '';
        $itemPage   = Input::has('limit')           ? Input::get('limit')           : 20;
        $TimeStart  = Input::has('time_start')      ? (int)Input::get('time_start') : 0;

        $Model          = new OrdersModel;
        $Model          = $Model::where('time_accept','>=',$TimeStart - $this->time_limit)
            ->where('time_accept','<=',$TimeStart)
            ->where('verify_id',(int)$id);

        if(!empty($Search)){
            $Model          = $Model->where('tracking_code',$Search);
        }

        $ModelTotal = clone $Model;
        $Total      = $ModelTotal->count();
        $Data = [];

        if($Total > 0){
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
            $Data       = $Model->skip($offset)->take($itemPage)->with(['OrderDetail','OrderFulfillment'])->get(array('id','tracking_code','verify_id','status'))->toArray();
        }

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'total'         => $Total,
            'data'          => $Data
        );

        return Response::json($contents);

    }

    public function getLastverify(){
        $Model = new VerifyModel;
        $Model = $Model->where('time_create','>=',strtotime(date('Y-m-1 00:00:00')));
        $Model = $Model->where('status', '=', 'SUCCESS');
        $Model = $Model->orderBy('time_create', 'DESC');
        $Verify = $Model->select('id', 'time_create', 'time_accept')->first();
        //var_dump($Verify);


        return Response::json(array(
            "error"     =>false,
            "data"      => ($Verify) ? $Verify : [],
            "message"   => ""
        ));

    }

    /**
     * đối soát bảng kê
     */
    public function getVerifyRequest($id){
        $LMongo     = LMongo::collection('log_verify')->where('partner', $id);

        $Total   = $LMongo->where('active',0)->count();
        if($Total > 0){
            $Item = $LMongo->where('active',0)->first();
            if(!empty($Item)){
                $this->CheckVerify($Item);
            }else{
                $this->Status           = 'NOT_EXISTS';
                $this->Active           = 2;
                $this->message_error    = 'Không tìm thấy dữ liệu !';
            }

            $LMongo->where('_id', new \MongoId($Item['_id']))->update(array(
                'status' => $this->Status,
                'active' => $this->Active
            ));

            $contents = array(
                'error'             => $this->Active == 1 ? false : true,
                'total'             => $Total,
                'code'              => $this->Status,
                'message_error'     => $this->message_error
            );

        }else{
            LMongo::collection('log_import_verify')->where('_id', new \MongoId($id))->update(array('action.insert' => 1));

            $contents = array(
                'error'         => false,
                'total'         => 0,
                'code'          => 'success',
                'message_error' => 'Kết thúc !'

            );
        }
        return Response::json($contents);
    }

    private function CheckVerify($Item){
        $UserInfo       = $this->UserInfo();
        $VerifyModel    = new VerifyModel;
        $Verify         = $VerifyModel->find((int)$Item['request_id']);

        if(!empty($Verify)){
            if($Verify->status != 'WAITING'){
                $this->Status           = 'STATUS_NOT_WAITING';
                $this->message_error    = 'Đang đối soát';
                $this->Active           = 2;
                return false;
            }

            try{
                $Verify->status = 'PROCESSING';
                $Verify->save();
            }catch (Exception $e){
                $this->Status           = 'UPDATE_STATUS_FAIL';
                $this->message_error    = 'Cập nhật trạng thái lỗi';
                $this->Active           = 2;
                return false;
            }

            /*if(strtolower($Verify->email_nl) != strtolower($Item['email_nl'])){
                $this->Status = 'EMAIL_NL_ERROR';
                $this->Active = 2;
                return false;
            }*/

            // Số tiền chuyển cho khách hàng
            $BalanceFee     = $Verify->balance + (($Verify->balance_available - $Verify->config_balance) < 0 ? ($Verify->balance_available - $Verify->config_balance) : 0);
            $TotalPayment   = $Verify->total_money_collect - $Verify->total_fee + $BalanceFee;

            if($TotalPayment != $Item['amount']){
                $this->Status           = 'AMOUNT_ERROR';
                $this->message_error    = 'Tổng tiền sai lệch';
                $this->Active           = 2;
                return false;
            }

            /**
             * Check có chuyển tiền hay ko
             *
             */
            $CheckCT = ($TotalPayment >= 100000 && !empty($Item['acc_number']));  // Số tiền được chuyển  > 100000  và có id nl

            $TransactionModel   = new TransactionModel;
            $MerchantModel      = new MerchantModel;

            if($CheckCT){
                if($Verify->acc_number != $Item['acc_number']){
                    $this->Status           = 'ACCOUNT_NUMBER_ERROR';
                    $this->message_error    = 'Mã tài khoản sai lệch';
                    $this->Active           = 2;
                    return false;
                }

                if(empty($Item['transaction_id'])){
                    $this->Status           = 'TRANSACTION_ID_EMPTY';
                    $this->message_error    = 'Mã giao dịch trống';
                    $this->Active           = 2;
                    return false;
                }

                $CheckTransactionIn = $TransactionModel->where('refer_code',$Item['transaction_id'])->count();
                if($CheckTransactionIn > 0){
                    $this->Status           = 'TRANSACTION_ID_EXISTS';
                    $this->message_error    = 'Mã giao dịch đã tồn tại';
                    $this->Active           = 2;
                    return false;
                }
            }

            $Merchant       = $MerchantModel->where('merchant_id', (int)$Verify->user_id)->first();
            if(empty($Merchant)){
                //DB::connection('accdb')->rollBack();
                $this->Status           = 'MERCHANT_NOT_EXISTS';
                $this->message_error    = 'Merchant không tồn tại';
                $this->Active           = 2;
                return false;
            }

            // Insert CashIn , Transaction, Update Balance
            DB::connection('accdb')->beginTransaction();
            // InsertCashIn
            $InsertTransaction   = [
                [// transaction pay pvc
                    'type'              => 2,
                    'refer_code'        => (int)$Item['request_id'],
                    'transaction_id'    => "",
                    'from_user_id'      => (int)$Verify->user_id,
                    'to_user_id'        => (int)$this->master_id,
                    'money'             => trim($Verify->total_fee),
                    'balance_before'    => $Merchant->balance,
                    'note'              => 'Thanh toán phí vận chuyển cho bảng kê số '.(int)$Item['request_id'],
                    'view'              => 0,
                    'time_create'       => $this->time(),
                    'check'             => 0
                ],
                [// transaction pay money_collect
                    'type'              => 2,
                    'refer_code'        => (int)$Item['request_id'],
                    'transaction_id'    => "",
                    'from_user_id'      => (int)$this->master_id,
                    'to_user_id'        => (int)$Verify->user_id,
                    'money'             => $Verify->total_money_collect,
                    'balance_before'    => $Merchant->balance - $Verify->total_fee,
                    'note'              => 'Nhận thanh toán thu hộ cho bảng kê số '.(int)$Item['request_id'],
                    'view'              => 0,
                    'time_create'       => $this->time(),
                    'check'             => 0
                ]
            ];

            if($CheckCT){ // trên 100k mới chuyển tiền hoặc
                /* if($BalanceFee < 0){  // Nếu khách có số dư < số dư tạm giữ  + số dư cấu hình. Khach bi giu lai tien
                     $InsertTransaction[] = [
                         'refer_code'        => (int)$Item['request_id'],
                         'from_user_id'      => (int)$this->master_id,
                         'to_user_id'        => (int)$Verify->user_id,
                         'money'             => -$BalanceFee,
                         'balance_before'    => $Verify->balance - $Verify->total_fee + $Verify->total_money_collect,
                         'note'              => 'Tạm giữ theo bảng kê số '.(int)$Item['request_id'],
                         'time_create'       => $this->time()
                     ];
                 }*/

                $InsertTransaction[] = [
                    'type'              => 2,
                    'refer_code'        => (int)$Item['request_id'],
                    'transaction_id'    => $Verify->transaction_id,
                    'from_user_id'      => (int)$Verify->user_id,
                    'to_user_id'        => (int)$this->master_id,
                    'money'             => $TotalPayment,
                    'balance_before'    => $Merchant->balance - $Verify->total_fee + $Verify->total_money_collect,
                    'note'              => 'Rút tiền theo bảng kê số '.(int)$Item['request_id'],
                    'view'              => 0,
                    'time_create'       => $this->time(),
                    'check'             => 0
                ];
            }

            try{
                TransactionModel::insert($InsertTransaction);
                DB::connection('accdb')->commit();
            }catch(\Exception $e){
                $this->Status           = 'INSERT_TRANSACTION_FAIL';
                $this->message_error    = 'Ghi giao dịch lỗi';
                $this->Active           = 2;
                return false;
            }

            // Update Balance Merchant

            // Số dư merchant
            // So sánh số dư trước và sau khi thanh toán
            $BonusBalance   =  $Merchant->balance - $Verify->balance;

            if($CheckCT){ // Nếu khách hàng được chuyển tiền hoặc
                $Merchant->balance = $BonusBalance + (($Verify->config_balance - $Verify->balance_available) > 0 ? ($Verify->config_balance - $Verify->balance_available) : 0);
            }else{ // Khách hàng ko được chuyển tiền
                $Merchant->balance +=  $Verify->total_money_collect - $Verify->total_fee;
            }

            try{
                $Merchant->save();
            }catch(Exception $e){
                //DB::connection('accdb')->rollBack();
                $this->Status           = 'UPDATE_BALANCE_MERCHANT_ERROR';
                $this->message_error    = 'Cập nhật số dư khách hàng lỗi';
                $this->Active           = 2;
                return false;
            }

            // Số dư Master
            $Master       = $MerchantModel->where('merchant_id', (int)$this->master_id)->first();
            try{
                if(!$CheckCT){ // khách hàng ko được chuyển tiền
                    $BalanceFee    = $Verify->total_fee - $Verify->total_money_collect;
                }
                $MerchantModel  = new MerchantModel;
                $MerchantModel->where('merchant_id', (int)$this->master_id)->increment('balance', $BalanceFee);
            }catch(Exception $e){
                //DB::connection('accdb')->rollBack();
                $this->Status           = 'UPDATE_BALANCE_MASTER_ERROR';
                $this->message_error    = 'Cập nhật số dư hệ thống lỗi';
                $this->Active           = 2;
                return false;
            }

            try{
                $CustomerAdmin  = \omsmodel\CustomerAdminModel::firstOrCreate(['user_id' => (int)$Verify->user_id]);
                if(empty($CustomerAdmin->first_time_verifed)){
                    $CustomerAdmin->first_time_verifed  = $this->time();
                }
                if($CheckCT && empty($CustomerAdmin->first_time_paid)){
                    $CustomerAdmin->first_time_paid  = $this->time();
                }
                $CustomerAdmin->save();
            }catch (\Exception $e){

            }

            try{
                $Verify->accept_id          = (int)$UserInfo['id'];
                $Verify->transaction_id     = isset($Item['transaction_id']) ? trim($Item['transaction_id']) : '';
                $Verify->time_accept        = $this->time();

                if($CheckCT){
                    $Verify->type   = 1; // Đã chuyển tiền
                }else{
                    $Verify->type   = 2; // Ko chuyển tiền, chỉ cập nhật số dư
                }

                $Verify->status             = 'SUCCESS';
                $Verify->save();
                $this->Status           = 'SUCCESS';
                $this->message_error    = 'Thành công';
                $this->Active = 1;
                DB::connection('accdb')->commit();
            }catch(Exception $e){
                DB::connection('accdb')->rollBack();
                $this->Status           = 'UPDATE_VERIFY_ERROR';
                $this->message_error    = 'Cập nhật bảng kê lỗi';
                $this->Active           = 2;
                return false;
            }

            return true;

        }else{
            $this->Status           = 'REQUEST_NOT_EXISTS';
            $this->message_error    = 'Mã bảng kê không tồn tại';
            $this->Active           = 2;
            return false;
        }
    }
}
