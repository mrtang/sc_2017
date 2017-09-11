<?php namespace accounting;

use accountingmodel\CashOutModel;
use accountingmodel\MerchantModel;
use accountingmodel\TransactionModel;
use accountingmodel\RefundModel;
use accountingmodel\RecoverModel;

class CashOutController extends BaseCtrl {
    private $master_id      = 1;
    private $table_log      = '';
    private $table_import   = '';

    private $code           = 'SUCCESS';
    private $message_error  = 'Thành công';

    /** ghi log
     * @param $id
     * @return bool
     */
    private function ReadExcel($id){
        $LMongo     = new LMongo;
        $ListImport = $LMongo::collection($this->table_import)->find($id);
        $Data       = Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(1)->select(
                array(2,3,4,5)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $DataInsert = array();

            foreach($Data as $key => $val){
                if(!empty($val[0]) && !empty($val[2])){
                    $DataInsert[] = array(
                        'partner'           => $id,
                        'status'            => 'NOT_ACTIVE',
                        'active'            => 0,
                        'to_email'          => strtolower(trim($val[0])),
                        'transaction_id'    => strtoupper(trim($val[1])),
                        'amount'            => (int)str_replace(array(',','.'),'',trim($val[2])),
                        'reason'            => trim($val[3])
                    );
                }
            }

            if(!empty($DataInsert)){
                $ListModel  = $LMongo::collection($this->table_log);

                try{
                    $Insert = $ListModel->batchInsert($DataInsert);
                    return Response::json([
                        'error'             => false,
                        'code'              => 'SUCCESS',
                        'error_message'     => 'Thành công',
                        'id'                => $id
                    ]);
                }catch (Exception $e){
                    return Response::json([
                        'error'             => true,
                        'code'              => 'INSERT_LOG_FAIL',
                        'error_message'     => $e->getMessage()
                    ]);
                }
            }
        }

        return Response::json([
            'error'             => true,
            'code'              => 'EMPTY',
            'error_message'     => 'File dữ liệu trống'
        ]);
    }

    /**
     * upload  cash out
     */

    public function postUploadHttps(){

       
        
        $this->table_import             = 'log_import_cash_out';
        $this->table_log                = 'log_cash_out';

        $UploadCtrl                     = new UploadCtrl;
        $UploadCtrl->table              = $this->table_import;
        $UploadCtrl->link_stogare       = storage_path(). DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR .'excel'. DIRECTORY_SEPARATOR .'cash_out'. DIRECTORY_SEPARATOR .date("Y_m_d");
        $UploadCtrl->link_download      = $this->link_upload.'/excel/cash_out/'.date("Y_m_d").'/';
        $Upload                         = $UploadCtrl->UploadHttps();

        if($Upload['error']){
            return Response::json($Upload);
        }

        $IdLog  = $Upload['id'];
        return $this->ReadExcel((string)$IdLog);
    }

    public function postUpload(){
        $this->table_import             = 'log_import_cash_out';
        $this->table_log                = 'log_cash_out';

        $UploadCtrl                     = new UploadCtrl;
        $UploadCtrl->table              = $this->table_import;
        $UploadCtrl->link_stogare       = storage_path(). DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR .'excel'. DIRECTORY_SEPARATOR .'cash_out'. DIRECTORY_SEPARATOR .date("Y_m_d");
        $UploadCtrl->link_download      = $this->link_upload.'/excel/cash_out/'.date("Y_m_d").'/';
        $Upload                         = $UploadCtrl->Upload();

        if($Upload['error']){
            return Response::json($Upload);
        }

        $IdLog  = $Upload['id'];
        return $this->ReadExcel((string)$IdLog);
    }

    /**
     * upload refund    Hoàn tiền cho khách hàng
     */
    public function postUploadRefund(){
        $this->table_import     = 'log_import_refund';
        $this->table_log        = 'log_refund';

        $UploadCtrl                     = new UploadCtrl;
        $UploadCtrl->table              = $this->table_import;
        $UploadCtrl->link_stogare       = storage_path(). DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR .'excel'. DIRECTORY_SEPARATOR .'refund'. DIRECTORY_SEPARATOR .date("Y_m_d");
        $UploadCtrl->link_download      = $this->link_upload.'/excel/refund/'.date("Y_m_d").'/';
        $Upload                         = $UploadCtrl->Upload();

        if($Upload['error']){
            return Response::json($Upload);
        }

        $IdLog  = $Upload['id'];
        return $this->ReadExcel((string)$IdLog);
    }

    /**
     * upload recover    thu hồi tiền cho khách hàng
     */
    public function postUploadRecover(){
        $this->table_import     = 'log_import_refund';
        $this->table_log        = 'log_refund';

        $UploadCtrl                     = new UploadCtrl;
        $UploadCtrl->table              = $this->table_import;
        $UploadCtrl->link_stogare       = storage_path(). DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR .'excel'. DIRECTORY_SEPARATOR .'recover'. DIRECTORY_SEPARATOR .date("Y_m_d");
        $UploadCtrl->link_download      = $this->link_upload.'/excel/recover/'.date("Y_m_d").'/';
        $Upload                         = $UploadCtrl->Upload();

        if($Upload['error']){
            return Response::json($Upload);
        }

        $IdLog  = $Upload['id'];
        return $this->ReadExcel((string)$IdLog);
    }

    private function getUserByEmail($email){
        $UserModel  = new \User;
        $User       = $UserModel::where('email',$email)->first(['id', 'email']);
        if(!isset($User->id)){
            $this->code             = 'EMAIL_NOT_EXISTS';
            $this->message_error    = 'email không tồn tại';
            return false;
        }
        return $User;
    }

    private function CheckTransaction($transaction_id){
        $TransactionModel   = new TransactionModel;
        $Transaction        = $TransactionModel::where('refer_code',$transaction_id)->count();

        if($Transaction > 0){
            $this->code             = 'REFER_CODE_EXISTS';
            $this->message_error    = 'Mã giao dịch đã tồn tại';
            return false;
        }
        return true;
    }

    /**
     * Thực hiện chuyển tiền cho khách hàng , trừ số dư trong tài khoản
    **/
      public function getCashOut($id){
        $this->table_import     = 'log_import_cash_out';
        $this->table_log        = 'log_cash_out';

        $LMongo     = LMongo::collection('log_cash_out')->where('partner', $id);
        $this->total   = $LMongo->where('active',0)->count();
        if($this->total > 0){
            $Item = $LMongo->where('active',0)->first();
            $User = $this->getUserByEmail($Item['to_email']);

            if(!$User){
                $this->data_update = [
                    'active'    => 2,
                    'status'    => $this->code
                ];
                return $this->ResponseData($Item['_id'], 2);
            }

            if($Item['amount'] < 0){
                $this->code             = 'AMOUNT_ERROR';
                $this->message_error    = 'Số tiền hợp lệ';
                $this->data_update  = [
                    'active'    => 2,
                    'status'    => $this->code
                ];
                return $this->ResponseData($Item['_id'], 2);
            }

            // Insert table courier verify
            DB::connection('accdb')->beginTransaction();

           /* Check transaction id;
            if(!empty($Item['transaction_id'])){
                $Transaction   = $this->CheckTransaction($Item['transaction_id']);
                if(!$Transaction){
                    $this->data_update = [
                        'active'    => 2,
                        'status'    => $this->code
                    ];
                    return $this->ResponseData($Item['_id'], 2);
                }
            }*/

            $MerchantModel  = new MerchantModel;
            $Merchant   = $MerchantModel->firstOrNew(array('merchant_id' => (int)$User->id));
            if(!isset($Merchant->balance)){
                $Merchant->balance      = 0;
                $Merchant->active       = 1;
                $Merchant->time_create  = $this->time();
            }

            // Insert Transaction
            $TransactionModel   = new TransactionModel;

            try{
                $TransactionModel::insert([
                    'type'              => 5,
                    'refer_code'        => !empty($Item['transaction_id']) ? $Item['transaction_id'] : 'SC_Chuyen_Tien',
                    'from_user_id'      => (int)(int)$User->id,
                    'to_user_id'        => (int)$this->master_id,
                    'money'             => $Item['amount'] > 0 ? $Item['amount'] : -($Item['amount']),
                    'balance_before'    => $Merchant->balance,
                    'note'              => $Item['reason'],
                    'time_create'       => $this->time()
                ]);
            }catch(Exception $e){
                $this->code             = 'INSERT_TRANSACTION_FAIL';
                $this->message_error    = 'Ghi nhận giao dịch thất bại';
                $this->data_update = [
                    'active'        => 2,
                    'status'        => $this->code,
                ];
                return $this->ResponseData($Item['_id'], 2);
            }

            // Insert Cash Out
            $CashOutModel   = new CashOutModel;
            try{
                $CashOutModel::insert([
                    'merchant_id'   => (int)$User->id,
                    'amount'        => $Item['amount'],
                    'refer_code'    => !empty($Item['transaction_id']) ? $Item['transaction_id'] : 'SC_Chuyen_Tien',
                    'time_create'   => $this->time(),
                    'reason'        => $Item['reason'],
                    'status'        => 'SUCCESS',
                    'time_accept'   => $this->time()
                ]);
            }catch(Exception $e){
                $this->code             = 'INSERT_CASH_OUT_FAIL';
                $this->message_error    = 'Ghi nhận chuyển tiền thất bại';
                $this->data_update = [
                    'active'        => 2,
                    'status'        => $this->code,
                ];
                return $this->ResponseData($Item['_id'], 2);
            }

            //Upload Balance Merchant
            try{
                $Merchant->balance  -= $Item['amount'];

                $Merchant->save();
            }catch(Exception $e){
                $this->code             = 'UPDATE_BALANCE_MERCHANT_FAIL';
                $this->message_error    = 'Cập nhật số dư khách hàng thất bại';
                $this->data_update = [
                    'active'        => 2,
                    'status'        => $this->code,
                ];
                return $this->ResponseData($Item['_id'], 2);
            }


            // Update Balance Master
            $Master = $MerchantModel->where('merchant_id', $this->master_id)->first();
            try{
                $Master->balance    += $Item['amount'];
                $Master->save();
                DB::connection('accdb')->commit();
            }catch(Exception $e){
                DB::connection('accdb')->rollBack();
                $this->code             = 'UPDATE_BALANCE_MASTER_FAIL';
                $this->message_error    = 'Cập nhật số dư hệ thống thất bại';
                $this->data_update = [
                    'active'    => 2,
                    'status'    => $this->code
                ];
                return $this->ResponseData($Item['_id'], 2);
            }

            $this->data_update = [
                'active'            => 1,
                'status'            => $this->code
            ];
            return $this->ResponseData($Item['_id'], 1);
        }else{
            return $this->ResponseData($id, 'SUCCESS', 0); // End
        }
    }

    /**
     * Thực hiện hoàn tiền cho khách hàng , cộng số dư trong tài khoản
     **/
    public function getRefund($id){
        $this->table_import     = 'log_import_refund';
        $this->table_log        = 'log_refund';

        $LMongo     = LMongo::collection('log_refund')->where('partner', $id);
        $this->total   = $LMongo->where('active',0)->count();
        if($this->total > 0){
            $Item = $LMongo->where('active',0)->first();
            $User = $this->getUserByEmail($Item['to_email']);

            if(!$User){
                $this->data_update = [
                    'active'    => 2,
                    'status'    => $this->code
                ];
                return $this->ResponseData($Item['_id'], 2);
            }

            if($Item['amount'] < 0){
                $this->code             = 'AMOUNT_ERROR';
                $this->message_error    = 'Số tiền hợp lệ';
                    $this->data_update  = [
                        'active'    => 2,
                        'status'    => $this->code
                    ];
                return $this->ResponseData($Item['_id'], 2);
            }

            // Insert table courier verify
            DB::connection('accdb')->beginTransaction();

            /* Check transaction id;
            if(!empty($Item['transaction_id'])){
                $Transaction   = $this->CheckTransaction($Item['transaction_id']);
                if(!$Transaction){
                    $this->data_update = [
                        'active'    => 2,
                        'status'    => $this->code
                    ];
                    return $this->ResponseData($Item['_id'], 2);
                }
            }*/

            $MerchantModel  = new MerchantModel;
            $Merchant   = $MerchantModel->firstOrNew(array('merchant_id' => (int)$User->id));
            if(!isset($Merchant->balance)){
                $Merchant->balance      = 0;
                $Merchant->active       = 1;
                $Merchant->time_create  = $this->time();
            }

            // Insert Transaction
            $TransactionModel   = new TransactionModel;

            try{
                $TransactionModel::insert([
                    'type'              => 4,
                    'refer_code'        => !empty($Item['transaction_id']) ? $Item['transaction_id'] : 'SC_Hoan_Tien',
                    'from_user_id'      => (int)$this->master_id,
                    'to_user_id'        => (int)(int)$User->id,
                    'money'             => $Item['amount'] > 0 ? $Item['amount'] : -($Item['amount']),
                    'balance_before'    => $Merchant->balance,
                    'note'              => $Item['reason'],
                    'time_create'       => $this->time()
                ]);
            }catch(Exception $e){
                $this->code             = 'INSERT_TRANSACTION_FAIL';
                $this->message_error    = 'Ghi nhận giao dịch thất bại';
                $this->data_update = [
                    'active'        => 2,
                    'status'        => $this->code,
                ];
                return $this->ResponseData($Item['_id'], 2);
            }

            // Insert Cash Out
            $RefundModel   = new RefundModel;
            try{
                $RefundModel::insert([
                    'merchant_id'   => (int)$User->id,
                    'amount'        => $Item['amount'],
                    'refer_code'    => !empty($Item['transaction_id']) ? $Item['transaction_id'] : 'SC_Chuyen_Tien',
                    'time_create'   => $this->time(),
                    'reason'        => $Item['reason'],
                    'status'        => 'SUCCESS',
                    'time_accept'   => $this->time()
                ]);
            }catch(Exception $e){
                $this->code             = 'INSERT_RECOVER_FAIL';
                $this->message_error    = 'Ghi nhận hoàn tiền thất bại';
                $this->data_update = [
                    'active'        => 2,
                    'status'        => $this->code,
                ];
                return $this->ResponseData($Item['_id'], 2);
            }

            //Upload Balance Merchant
            try{
                $Merchant->balance  += $Item['amount'];

                $Merchant->save();
            }catch(Exception $e){
                $this->code             = 'UPDATE_BALANCE_MERCHANT_FAIL';
                $this->message_error    = 'Cập nhật số dư khách hàng thất bại';
                $this->data_update = [
                    'active'        => 2,
                    'status'        => $this->code,
                ];
                return $this->ResponseData($Item['_id'], 2);
            }


            // Update Balance Master
            $Master = $MerchantModel->where('merchant_id', $this->master_id)->first();
            try{
                $Master->balance    -= $Item['amount'];
                $Master->save();
                DB::connection('accdb')->commit();
            }catch(Exception $e){
                DB::connection('accdb')->rollBack();
                $this->code             = 'UPDATE_BALANCE_MASTER_FAIL';
                $this->message_error    = 'Cập nhật số dư hệ thống thất bại';
                $this->data_update = [
                    'active'    => 2,
                    'status'    => $this->code
                ];
                return $this->ResponseData($Item['_id'], 2);
            }

            $this->data_update = [
                'active'            => 1,
                'status'            => $this->code
            ];
            return $this->ResponseData($Item['_id'], 1);
        }else{
            return $this->ResponseData($id, 0); // End
        }
    }

    /**
     * Thực hiện thu hồi tiền khách hàng , trừ số dư trong tài khoản
     **/
    public function getRecover($id){
        $this->table_import     = 'log_import_refund';
        $this->table_log        = 'log_refund';

        $LMongo         = LMongo::collection('log_refund')->where('partner', $id);
        $this->total    = $LMongo->where('active',0)->count();
        if($this->total > 0){
            $Item = $LMongo->where('active',0)->first();
            $User = $this->getUserByEmail($Item['to_email']);

            if(!$User){
                $this->data_update = [
                    'active'    => 2,
                    'status'    => $this->code
                ];
                return $this->ResponseData($Item['_id'], 2);
            }

            if($Item['amount'] < 0){
                $this->code             = 'AMOUNT_ERROR';
                $this->message_error    = 'Số tiền hợp lệ'.
                $this->data_update  = [
                    'active'    => 2,
                    'status'    => $this->code
                ];
                return $this->ResponseData($Item['_id'], 2);
            }

            // Insert table courier verify
            DB::connection('accdb')->beginTransaction();

            $MerchantModel  = new MerchantModel;
            $Merchant   = $MerchantModel->firstOrNew(array('merchant_id' => (int)$User->id));
            if(!isset($Merchant->balance)){
                $Merchant->balance      = 0;
                $Merchant->active       = 1;
                $Merchant->time_create  = $this->time();
            }

            // Insert Transaction
            $TransactionModel   = new TransactionModel;

            try{
                $TransactionModel::insert([
                    'type'              => 3,
                    'refer_code'        => !empty($Item['transaction_id']) ? $Item['transaction_id'] : 'SC_Thu_Hoi',
                    'from_user_id'      => (int)(int)$User->id,
                    'to_user_id'        => (int)$this->master_id,
                    'money'             => $Item['amount'] > 0 ? $Item['amount'] : -($Item['amount']),
                    'balance_before'    => $Merchant->balance,
                    'note'              => $Item['reason'],
                    'time_create'       => $this->time()
                ]);
            }catch(Exception $e){
                $this->code             = 'INSERT_TRANSACTION_FAIL';
                $this->message_error    = 'Ghi nhận giao dịch thất bại';
                $this->data_update = [
                    'active'        => 2,
                    'status'        => $this->code,
                ];
                return $this->ResponseData($Item['_id'], 2);
            }

            // Insert Recover
            $RecoverModel   = new RecoverModel;
            try{
                $RecoverModel::insert([
                    'merchant_id'   => (int)$User->id,
                    'amount'        => $Item['amount'],
                    'refer_code'    => !empty($Item['transaction_id']) ? $Item['transaction_id'] : 'SC_Thu_Hoi',
                    'time_create'   => $this->time(),
                    'reason'        => $Item['reason'],
                    'status'        => 'SUCCESS',
                    'time_accept'   => $this->time()
                ]);
            }catch(Exception $e){
                $this->code             = 'INSERT_RECOVER_FAIL';
                $this->message_error    = 'Ghi thu hồi thất bại';
                $this->data_update = [
                    'active'        => 2,
                    'status'        => $this->code,
                ];
                return $this->ResponseData($Item['_id'], 2);
            }

            //Upload Balance Merchant
            try{
                $Merchant->balance  -= $Item['amount'];

                $Merchant->save();
            }catch(Exception $e){
                $this->code             = 'UPDATE_BALANCE_MERCHANT_FAIL';
                $this->message_error    = 'Cập nhật số dư khách hàng thất bại';

                $this->data_update = [
                    'active'        => 2,
                    'status'        => $this->code,
                ];
                return $this->ResponseData($Item['_id'], 2);
            }


            // Update Balance Master
            $Master = $MerchantModel->where('merchant_id', $this->master_id)->first();
            try{
                $Master->balance    += $Item['amount'];
                $Master->save();
                DB::connection('accdb')->commit();
            }catch(Exception $e){
                DB::connection('accdb')->rollBack();
                $this->code             = 'UPDATE_BALANCE_MASTER_FAIL';
                $this->message_error    = 'Cập nhật số dư hệ thống thất bại';
                $this->data_update = [
                    'active'    => 2,
                    'status'    => $this->code
                ];
                return $this->ResponseData($Item['_id'], 2);
            }

            $this->data_update = [
                'active'            => 1,
                'status'            => $this->code
            ];
            return $this->ResponseData($Item['_id'], 1);
        }else{
            return $this->ResponseData($id,  0); // End
        }
    }

    /**
     * @param $id
     * @param $Status
     * @param $Active
     * @return mixed
     */

    private function ResponseData( $id, $Active){

        if($Active == 0){
            LMongo::collection($this->table_import)->where('_id', new \MongoId($id))->update(array('action.insert' => 1));
            $contents = array(
                'error'             => false,
                'code'              => $this->code,
                'message_error'     => $this->message_error,
                'total'             => 0
            );
        }else{
            LMongo::collection($this->table_log)->where('_id', new \MongoId($id))->update($this->data_update);

            $contents = array(
                'error'             => $Active == 1 ? false : true,
                'code'              => $this->code,
                'message_error'     => $this->message_error,
                'total'             => $this->total + 1
            );
        }

        return Response::json($contents);
    }

    /** get list verify excel
     * @param $id
     * @return mixed
     */
    function getListExcel($id){
        $page           = Input::has('page')    ? (int)Input::get('page')                   : 1;
        $itemPage       = Input::has('limit')   ? (int)Input::get('limit')                  : 20;
        $tab            = Input::has('tab')     ? trim(strtoupper(Input::get('tab')))       : 'ALL';
        $type           = Input::has('type')    ? trim(strtolower(Input::get('type')))      : 'cash_out';

        $NewTotal       = 0;
        $DataSum        = $Data     = [];

        if(in_array($type, ['refund','recover'])){
            $ListModel      = LMongo::collection('log_refund')->where('partner', $id);
        }else{
            $ListModel      = LMongo::collection('log_cash_out')->where('partner', $id);
        }

        if($tab != 'ALL'){
            if($tab == 'MISMATCH'){
                $ListModel = $ListModel->where('active',2);
            }else{
                $ListModel = $ListModel->where('status',$tab);
            }
        }

        $Model              = clone $ListModel;
        $Total              = $Model->count();

        if($Total > 0){
            $MotalNewTotal  = clone $ListModel;
            $SumModel       = clone $ListModel;
            $NewTotal       = $MotalNewTotal->where('active',0)->count();
            $DataSum        = $SumModel->sum('amount');

            $offset     = ($page - 1)*$itemPage;
            $Data       = $ListModel->skip($offset)->take($itemPage)->get()->toArray();
        }



        return Response::json([
            'error'         => false,
            'code'          => 'SUCCESS',
            'message_error' => 'Thành công',
            'data'          => $Data,
            'total'         => $Total,
            'new_total'     => $NewTotal,
            'data_sum'      => $DataSum
        ]);
    }

    /**
     * Danh sách
     */
    public function getIndex()
    {
        $page                   = Input::has('page')                    ? (int)Input::get('page')                   : 1;
        $itemPage               = Input::has('limit')                   ? Input::get('limit')                       : 20;
        $TimeStart              = Input::has('time_start')              ? trim(Input::get('time_start'))            : '';
        $TimeEnd                = Input::has('time_end')                ? trim(Input::get('time_end'))              : '';
        $FirstShipmentStart     = Input::has('first_shipment_start')    ? trim(Input::get('first_shipment_start'))  : '';
        $KeyWord                = Input::has('keyword')                 ? trim(Input::get('keyword'))               : '';

        $Model = new CashOutModel;

        if(!empty($TimeStart)){
            $Model = $Model->where('time_accept','>=',$TimeStart);
        }

        if(!empty($TimeEnd)){
            $Model = $Model->where('time_accept','<',$TimeEnd);
        }

        if(!empty($KeyWord)){
            $ModelUser  = new \User;
            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $ModelUser          = $ModelUser->where('email','LIKE','%'.$KeyWord.'%');
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $ModelUser          = $ModelUser->where('phone','LIKE','%'.$KeyWord.'%');
            }else{ // search code
                $ModelUser          = $ModelUser->where('fullname','LIKE','%'.$KeyWord.'%');
            }
            $ListUser = $ModelUser->lists('id');

            if(empty($ListUser)){
                return Response::json([
                    'error'         => false,
                    'code'          => 'SUCCESS',
                    'message_error' => 'Thành công',
                    'item_page'     => $itemPage,
                    'total'         => 0,
                    'data'          => []
                ]);
            }

            $Model  = $Model->whereIn('merchant_id',$ListUser);
        }

        if(!empty($FirstShipmentStart)){
            $ListUser = $this->__get_user_boxme($FirstShipmentStart);
            if(empty($ListUser)){
                return $this->ResponseData();
            }
            $Model  = $Model->whereIn('merchant_id', $ListUser);
        }

        $ModelTotal = clone $Model;

        $Total = $ModelTotal->count();
        $Data  = [];

        $Model = $Model->orderBy('time_accept','DESC');
        if($Total > 0){
            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }

            $Data = $Model->with('User')->get()->toArray();
        }

        return Response::json([
            'error'         => false,
            'code'          => 'SUCCESS',
            'message_error' => 'Thành công',
            'item_page'     => $itemPage,
            'total'         => $Total,
            'data'          => $Data
        ]);
    }
}
