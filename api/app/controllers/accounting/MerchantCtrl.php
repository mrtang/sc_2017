<?php namespace accounting;

use accountingmodel\MerchantModel;
use accountingmodel\TransactionModel;
use accountingmodel\AuditTransactionModel;
use sellermodel\UserInfoModel;
use sellermodel\VimoModel;

class MerchantCtrl extends BaseCtrl {
    private $total      = 0;
    private $data       = [];
    private $data_sum   = [];

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
        $page                   = Input::has('page')                    ? (int)Input::get('page')                   : 1;
        $itemPage               = Input::has('limit')                   ? Input::get('limit')                       : 20;
        $TimeStart              = Input::has('time_start')              ? trim(Input::get('time_start'))            : '';
        $TimeEnd                = Input::has('time_end')                ? trim(Input::get('time_end'))              : '';
        $FirstShipmentStart     = Input::has('first_shipment_start')    ? trim(Input::get('first_shipment_start'))  : '';
        $KeyWord                = Input::has('search')                  ? trim(Input::get('search'))                : '';
        $Type                   = Input::has('type')                    ? (int)Input::get('type')                   : '';
        $cmd                    = Input::has('cmd')                     ? strtoupper(trim(Input::get('cmd')))       : '';
        $File                   = Input::has('file')                    ? (int)Input::get('file')                   : '';

        $Model = new MerchantModel;

        if(!empty($TimeStart)){
            $Model = $Model->where('time_create','>=',$TimeStart);
        }

        if(!empty($TimeEnd)){
            $Model = $Model->where('time_create','<',$TimeEnd);
        }

        if(!empty($FirstShipmentStart)){
            $ListUser = $this->__get_user_boxme($FirstShipmentStart);
            if(empty($ListUser)){
                return $this->ResponseData();
            }
            $Model  = $Model->whereIn('merchant_id', $ListUser);
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
            $ListUser = $ModelUser->get(array('id'))->toArray();

            if(!empty($ListUser)){
                foreach($ListUser as $val){
                    $UserId[]     =   (int)$val['id'];
                }
            }else{
                $UserId = array(0);
            }

            $Model  = $Model->whereIn('merchant_id',$UserId);
        }

        if(!empty($Type)){
            if($Type == 1){ //Khách hàng nợ
                $Model = $Model->where('balance','<',0);
                $Model = $Model->orderBy('balance','ASC');
            }else{ // Khách hàng không đủ điều kiện thanh toán
                $Model  = $Model->whereRaw('(balance + provisional) > freeze');

                // List khách hàng ko có email NL hoặc ko có tài khoản NH
                $UserInfoModel  = new UserInfoModel;
                $ListUserInfo   = $UserInfoModel::where(function($query){
                   $query->where(function($q){
                       $q->where('priority_payment',2)
                         ->where('user_nl_id',0);
                   })->orWhere('priority_payment',1);
                })->get(['id','user_id','user_nl_id','priority_payment'])->toArray();

                if(empty($ListUserInfo)){
                    return $this->ResponseData();
                }

                $ListIdVimo    = [];
                $ListIdError   = [];
                foreach($ListUserInfo as $val){
                    if($val['priority_payment'] == 1){
                        $ListIdVimo[]  = (int)$val['user_id'];
                    }
                    $ListIdError    []  = (int)$val['user_id'];
                }

                if(!empty($ListIdVimo)){
                    $VimoModel  = new VimoModel;
                    $Vimo       = $VimoModel::whereIn('user_id',$ListIdVimo)->where('active',1)->get(['user_id'])->toArray();
                    if(!empty($Vimo)){
                        $ListIdVimo     = array_pluck($Vimo,'user_id');
                        $ListIdError    = array_diff($ListIdError,$ListIdVimo);
                    }
                }

                $Model  = $Model->whereRaw("merchant_id in (". implode(",", $ListIdError) .")");
            }

        }

        if($cmd == 'EXPORT'){
            return $this->getExcel($Model);
        }

        if(empty($Type)){
            $Model = $Model->orderBy('freeze','DESC');
        }

        $ModelTotal = clone $Model;
        $ModelSum   = clone $Model;
        $this->total        = $ModelTotal->count();

        if($this->total > 0){
            $this->data_sum     = $ModelSum->first(array(DB::raw(
                'sum(balance) as balance, sum(freeze) as freeze, sum(quota) as quota')));

            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }

            $this->data = $Model->with(['User','UserInfo','VimoConfig'])->get()->toArray();
        }

        return $this->ResponseData();
	}

    private function ResponseData(){
        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'item_page'     => 20,
            'total'         => $this->total,
            'data'          => $this->data,
            'data_sum'      => $this->data_sum
        ]);
    }

    /**
     * List Audit transaction
     */
    public function getAudit()
    {
        $page                   = Input::has('page')                    ? (int)Input::get('page')                   : 1;
        $itemPage               = Input::has('limit')                   ? Input::get('limit')                       : 20;
        $TimeEnd                = Input::has('time_end')                ? trim(Input::get('time_end'))              : '';
        $FirstShipmentStart     = Input::has('first_shipment_start')    ? trim(Input::get('first_shipment_start'))  : '';
        $KeyWord                = Input::has('search')                  ? trim(Input::get('search'))                : '';
        $Type                   = Input::has('type')                    ? (int)Input::get('type')                   : '';
        $Record                 = Input::has('num')                     ? (int)Input::get('num')                    : '';
        $cmd                    = Input::has('cmd')                     ? strtoupper(trim(Input::get('cmd')))       : '';

        $Model = new AuditTransactionModel;
        $Total = 0;

        if(!empty($TimeEnd)){
            $Model  = $Model->where('time_end',$TimeEnd);
        }else{
            $contents = array(
                'error'         => false,
                'message'       => 'success',
                'item_page'     => $itemPage,
                'total'         => 0,
                'data'          => [],
                'data_sum'      => []
            );

            return Response::json($contents);
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
            $ListUser = $ModelUser->get(array('id'))->toArray();

            if(!empty($ListUser)){
                foreach($ListUser as $val){
                    $UserId[]     =   (int)$val['id'];
                }
            }else{
                $UserId = array(0);
            }

            $Model  = $Model->whereIn('user_id',$UserId);
        }

        if(!empty($FirstShipmentStart)){
            $ListUser = $this->__get_user_boxme($FirstShipmentStart);
            if(empty($ListUser)){
                return $this->ResponseData();
            }
            $Model  = $Model->whereIn('user_id', $ListUser);
        }

        if(!empty($Type)){
            if($Type == 2){
                $Model = $Model->where('balance','<',0);
            }
            $Model = $Model->orderBy('balance','ASC');
        }else{
            $Model = $Model->orderBy('user_id','ASC');
        }

        if($cmd == 'EXPORT'){
            $Skip   = ($Record - 1)*20000;
            $Model  = $Model->skip($Skip)->take(20000);
            $Data   = [];
            $Model->with('User')->chunk('1000', function($query) use(&$Data){
                foreach($query as $val){
                    $val                = $val->toArray();
                    $Data[]             = $val;
                }
            });
            return  Response::json([
                'error'         => false,
                'code'          => 'success',
                'error_message' => 'Thành công',
                'data'          => $Data,
            ]);
        }

        $ModelSum   = clone $Model;

        $DataSum    = $ModelSum->first(array(DB::raw(
            'sum(balance) as balance, sum(audit) as audit, count(*) as total')));

        $Data  = array();
        $Total      = (int)$DataSum['total'];

        if($Total > 0){
            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }

            $Data   = $Model->with('User')->get()->toArray();
        }

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'item_page'     => $itemPage,
            'total'         => $Total,
            'data'          => $Data,
            'data_sum'      => $DataSum
        );

        return Response::json($contents);
    }

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getShow()
	{
        $UserInfo   = $this->UserInfo();
        $Id = (int)$UserInfo['id'];

        $Model          = new MerchantModel;
        $QueueModel     = new \QueueModel;

        $Data   = $Model->where('merchant_id',$Id)->remember(5)->first(array('balance', 'freeze', 'provisional'));
        //$Queue  = $QueueModel->where('user_id',$Id)->where('transport_id',3)->where('view',0)->count();

        if(empty($Data)){
            $Data['balance']    = 0;
            $Data['freeze']     = 0;
        }

        $Data['queue']     = 0;
        /*if($Queue > 0){
            $Data['queue']     = $Queue;
        }*/

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'        => $Data
        );

        return Response::json($contents);
	}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function postEdit($id)
    {
        $Quota      = Input::json()->get('quota');
        $Level      = Input::json()->get('level');

        $UserInfo   = $this->UserInfo();
        $Model      = new MerchantModel;

        $Merchant = $Model::find($id);

        if(empty($Merchant)){
            return Response::json(['error' => true, 'message' => 'MERCHANT_NOT_EXISTS']);
        }

        if((int)$UserInfo['privilege'] != 2){
            $contents = array(
                'error'     => true,
                'message'   => 'USER_NOT_ALLOWED'
            );
            return Response::json($contents);
        }

        if(isset($Quota)){
            $Merchant->quota    = $Quota;
        }

        if(!empty($Level)){
            $Merchant->level    = $Level;
        }

        $contents   = ['error' => false, 'message'  => 'SUCCESS'];

        try{
            $Merchant->save();
        }catch (Exception $e){
            $contents   = ['error' => true, 'message'  => 'UPDATE_FAIL'];
        }

        if($Level == 3){ // cấp quyền bảo lãnh
            $User   = \User::where('id',(int)$Merchant->user)->first(['id','email']);
            $Content = $UserInfo['fullname'].' da cap quyen bao lanh tien cho tai khoan '. $User->email;
            $this->SendSmS('0906262181', $Content);
        }

        return Response::json($contents);
    }

    private function getExcel($Model){
        $Data = [];
        $Model->orderBy('time_create','ASC')->with('User')->chunk('1000', function($query) use(&$Data){
            foreach($query as $val){
                $val                = $val->toArray();
                $Data[]             = $val;
            }
        });

        $this->data = $Data;
        return $this->ResponseData();
    }

    /**
     * Kiểm tra lích sử giao dịch
     */
    public function getHandling(){
        $Time = Input::has('time_start') ? str_replace('/', '-', trim(Input::get('time_start'))) : '';

        if (empty($Time)) {
            return Response::json(['error' => true, 'message' => 'EMPTY_TIME_START']);
        }

        $TimeEnd  = strtotime($Time . ' 23:59:59');

        if(!$TimeEnd){
            return Response::json(['error' => true, 'message' => 'TIME_ERROR']);
        }

        $MerchantModel      = new MerchantModel;
        $TransactionModel   = new TransactionModel;
        $AuditTransaction   = new AuditTransactionModel;

        $Time   = (int)date('dm',$TimeEnd);

        $Merchant = $MerchantModel->where('time_audit', '<>',$Time)->where('merchant_id','>',1)->orderBy('id','ASC')->first();
        if(!isset($Merchant->id)){
            return Response::json([
                'error'     => true,
                'message'   => 'EMPTY',
                'data'      => []
            ]);
        }

        if(AuditTransactionModel::where('user_id',(int)$Merchant->merchant_id)->where('time_end', $TimeEnd)->count() > 0){
            try{
                $Merchant->time_audit  = $Time;
                $Merchant->save();
                return Response::json([
                    'error'     => false,
                    'message'   => 'SUCCESS',
                    'data'      => [
                        'user_id'   => (int)$Merchant->merchant_id,
                        'time'      => Input::get('time_start'),
                        'balance'   => 0,
                        'audit'     => 0,
                        'status'    => 'EXISTS'
                    ]
                ]);
            }catch (Exception $e){
                return Response::json([
                    'error'     => true,
                    'message'   => 'UPDATE_ERROR',
                    'data'      => []
                ]);
            }
        }

        $Audit          = $AuditTransaction->where('user_id',(int)$Merchant->merchant_id)->where('time_end','<=',$TimeEnd)->orderBy('time_create','DESC')->first();
        $TimeStart      = 0;
        if(isset($Audit->id)){
            $TimeStart  = $Audit->time_end;
        }

        $ListTransaction = $TransactionModel
            ->where('time_create','>=',$TimeStart)
            ->where('time_create','<',$TimeEnd)
            ->where(function($query) use($Merchant){
                $query->where('from_user_id', (int)$Merchant->merchant_id)
                    ->orWhere('to_user_id', (int)$Merchant->merchant_id);
            })
            ->orderBy('time_create','ASC')
            ->orderBy('id','ASC')
            ->get(['from_user_id','to_user_id','money','balance_before','time_create'])->toArray();

        $Status             = 'SUCCESS';
        $BalanceMerchant    = 0;

        if(!empty($ListTransaction)){
            $Balance    = 0;
            $i          = 0;

            foreach($ListTransaction as $val){
                if($i == 0){
                    $i = 1;
                    $Balance   = $val['balance_before'];
                }

                if($val['from_user_id'] == (int)$Merchant->merchant_id){
                    $Money  = -$val['money'];
                }else{
                    $Money  = $val['money'];
                }

                $BalanceMerchant    = $val['balance_before'];

                if($Balance != $val['balance_before']){
                    $Status             = 'ERROR';
                    break;
                }

                $BalanceMerchant    += $Money;
                $Balance            += $Money;
            }

            DB::connection('accdb')->beginTransaction();

            try{
                $AuditTransaction->insert([
                    'user_id'       => (int)$Merchant->merchant_id,
                    'time_start'    => $TimeStart,
                    'time_end'      => $TimeEnd,
                    'balance'       => $BalanceMerchant,
                    'audit'         => $Balance,
                    'status'        => $Status,
                    'time_create'   => $this->time()
                ]);
                $Merchant->time_audit  = $Time;
                $Merchant->save();

                DB::connection('accdb')->commit();
                return Response::json([
                    'error'     => false,
                    'message'   => 'SUCCESS',
                    'data'      => [
                        'user_id'   => (int)$Merchant->merchant_id,
                        'time'      => Input::get('time_start'),
                        'balance'   => $BalanceMerchant,
                        'audit'     => $Balance,
                        'status'    => 'SUCCESS'
                    ]
                ]);
            }catch (Exception $e){
                DB::connection('accdb')->rollBack();
                return Response::json([
                    'error'     => false,
                    'message'   => 'UPDATE_ERROR',
                    'data'      => []
                ]);
            }
        }else{
            $Transaction = $TransactionModel->where('time_create','<',$TimeStart)
                ->where(function($query) use($Merchant){
                    $query->where('from_user_id', (int)$Merchant->merchant_id)
                        ->orWhere('to_user_id', (int)$Merchant->merchant_id);
                })
                ->orderBy('time_create','DESC')
                ->orderBy('id','DESC')
                ->first(['id','from_user_id','to_user_id','money','balance_before','time_create']);

            $Balance = $Merchant->balance_pvc + $Merchant->balance_cod;
            if(isset($Transaction->id)){
                $Money = 0;
                if($Transaction->from_user_id == $Merchant->merchant_id){
                    $Money = -($Transaction->money);
                }else{
                    $Money = $Transaction->money;
                }

                $Balance    = $Transaction->balance_before + $Money;
            }

            DB::connection('accdb')->beginTransaction();
            try {
                $AuditTransaction->insert([
                    'user_id'       => (int)$Merchant->merchant_id,
                    'time_start'    => $TimeStart,
                    'time_end'      => $TimeEnd,
                    'balance'       => $Balance,
                    'audit'         => $Balance,
                    'status'        => 'SUCCESS',
                    'time_create'   => $this->time()
                ]);

                $Merchant->time_audit  = $Time;
                $Merchant->save();
            }catch(Exception $e){
                DB::connection('accdb')->rollBack();
                return Response::json([
                    'error'     => false,
                    'message'   => 'INSERT_ERROR',
                    'data'      => []
                ]);
            }

            DB::connection('accdb')->commit();
            return Response::json([
                'error'     => false,
                'message'   => 'success',
                'data'      => [
                    'user_id'   => (int)$Merchant->merchant_id,
                    'time'      => Input::get('time_start'),
                    'balance'   => $Balance,
                    'audit'     => $Balance,
                    'status'    => 'SUCCESS'
                ]
            ]);
        }
    }

    // Audit  Transaction  all
    public function getAudittransaction(){
        $MerchantModel      = new MerchantModel;
        $TransactionModel   = new TransactionModel;
        $AuditTransaction   = new AuditTransactionModel;

        $time   = (int)date('dm',$this->time()); //  1105
        $Merchant = $MerchantModel->where('time_audit', '<>',$time)->where('merchant_id','>',1)->orderBy('id','ASC')->first();
        if(!isset($Merchant->id)){
            return Response::json([
                'error'     => true,
                'message'   => 'EMPTY'
            ]);
        }

        $Audit          = $AuditTransaction->where('user_id',(int)$Merchant->merchant_id)->orderBy('time_create','DESC')->first();

        $TimeStart      = 0;
        $TimeEnd        = $this->time();
        if(isset($Audit->id)){
            if($Audit->status  == 'ERROR'){
                return Response::json([
                    'error'     => true,
                    'message'   => 'ERROR'
                ]);
            }
            $TimeStart  = $Audit->time_end;
        }

        $ListTransaction = $TransactionModel
            ->where('time_create','>=',$TimeStart)
            ->where('time_create','<',$TimeEnd)
            ->where(function($query) use($Merchant){
                $query->where('from_user_id', (int)$Merchant->merchant_id)
                    ->orWhere('to_user_id', (int)$Merchant->merchant_id);
            })
            ->orderBy('time_create','ASC')
            ->orderBy('id','ASC')
            ->get(['from_user_id','to_user_id','money','balance_before','time_create'])->toArray();

        $Status             = 'SUCCESS';
        $BalanceMerchant    = $Merchant->balance;

        if(!empty($ListTransaction)){
            $Balance    = 0;
            $i          = 0;

            foreach($ListTransaction as $val){
                if($i == 0){
                    $i = 1;
                    $Balance   = $val['balance_before'];
                }

                if($val['from_user_id'] == (int)$Merchant->merchant_id){
                    $Money  = -$val['money'];
                }else{
                    $Money  = $val['money'];
                }

                if($Balance != $val['balance_before']){
                    $Status             = 'ERROR';
                    $BalanceMerchant    = $val['balance_before'];
                    break;
                }

                $Balance += $Money;
            }

            if($Status == 'SUCCESS' && $BalanceMerchant != $Balance){
                $Status = 'ERROR';
            }


            $Insert = [
                'user_id'       => (int)$Merchant->merchant_id,
                'time_start'    => $TimeStart,
                'time_end'      => $TimeEnd,
                'balance'       => $BalanceMerchant,
                'audit'         => $Balance,
                'status'        => $Status,
                'time_create'   => $TimeEnd
            ];
        }else{
            $Insert = [
                'user_id'       => (int)$Merchant->merchant_id,
                'time_start'    => $TimeStart,
                'time_end'      => $TimeEnd,
                'balance'       => $Merchant->balance,
                'audit'         => $Merchant->balance,
                'status'        => 'SUCCESS',
                'time_create'   => $this->time()
            ];
        }

        DB::connection('accdb')->beginTransaction();

        try{
            $AuditTransaction->insert($Insert);
            $Merchant->time_audit  = $time;
            $Merchant->save();

            DB::connection('accdb')->commit();
            return Response::json([
                'error'     => false,
                'message'   => 'SUCCESS'
            ]);
        }catch (Exception $e){
            DB::connection('accdb')->rollBack();
            return Response::json([
                'error'     => false,
                'message'   => 'UPDATE_ERROR'
            ]);
        }
    }

}