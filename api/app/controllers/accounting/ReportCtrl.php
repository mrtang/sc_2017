<?php namespace accounting;

use accountingmodel\ReportMerchantModel;
use ordermodel\InvoiceModel;
use ordermodel\OrdersModel;
use ticketmodel\RequestModel;
use AreaLocationModel;

class ReportCtrl extends BaseCtrl {
    private $data           = [];
    private $message        = 'SUCCESS';
    private $error_message  = 'Thành công';
    private $total          = 0;

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
        set_time_limit (180);
        $page           = Input::has('page')            ? (int)Input::get('page')                : 1;
        $itemPage       = Input::has('limit')           ? Input::get('limit')                    : 20;
        $Search         = Input::has('search')          ? trim(Input::get('search'))             : '';
        $FromDay        = Input::has('from_day')        ? trim(Input::get('from_day'))           : '';
        $ToDay          = Input::has('to_day')          ? trim(Input::get('to_day'))             : '';
        $Month          = Input::has('month')           ? trim(Input::get('month'))              : '';
        $cmd            = Input::has('cmd')             ? strtoupper(trim(Input::get('cmd')))    : '';

        $Total          = 0;
        $Model = new ReportMerchantModel;

        if(!empty($Search)){
            $ModelUser  = new \User;
            if (filter_var($Search, FILTER_VALIDATE_EMAIL)){  // search email
                $ModelUser          = $ModelUser->where('email','LIKE','%'.$Search.'%');
            }elseif(filter_var((int)$Search, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $ModelUser          = $ModelUser->where('phone','LIKE','%'.$Search.'%');
            }else{ // search code
                $ModelUser          = $ModelUser->where('fullname','LIKE','%'.$Search.'%');
            }
            $ListUser = $ModelUser->lists('id');

            if(!empty($ListUser)){
                $Model  = $Model->whereIn('user_id',$ListUser);
            }else{
                return Response::json([
                    'error'         => false,
                    'message'       => 'success',
                    'item_page'     => $itemPage,
                    'total'         => 0,
                    'data'          => []
                ]);
            }
        }

        if(!empty($Month)){ // thống kê theo tháng
            $Mon    = explode('-',$Month);
            $Model  = $Model->where('month',(int)$Mon[0])->where('year',(int)$Mon[1]);
        }else{
            if(!empty($FromDay) && !empty($ToDay)){
                $From = explode('-',$FromDay);
                $To = explode('-',$ToDay);

                if((int)$To[1] < (int)$From[1] && (int)$To[2] <= (int)$From[2]){
                    return Response::json([
                        'error'         => false,
                        'message'       => 'success',
                        'item_page'     => $itemPage,
                        'total'         => 0,
                        'data'          => []
                    ]);
                }

                if((int)$From[1] == (int)$To[1] && (int)$From[2] == (int)$To[2]){
                    $Model  = $Model->where('date', '>=', (int)$From[0])
                                    ->where('date', '<=', (int)$To[0])
                                    ->where('month', (int)$To[1])
                                    ->where('year', (int)$To[2]);

                }else{
                    $Model = $Model->where(function($query) use ($From, $To) {
                        $query->where(function ($q) use ($From) {
                            $q->where('date', '>=', (int)$From[0])
                                ->where('month', (int)$From[1])
                                ->where('year', (int)$From[2]);
                        })->orWhere(function ($q) use ($To) {
                            $q->where('date', '<=', (int)$To[0])
                                ->where('month', (int)$To[1])
                                ->where('year', (int)$To[2]);
                        });
                    });
                }

            }elseif(!empty($FromDay)){
                $From = explode('-',$FromDay);
                $Model = $Model->where('date',(int)$From[0])->where('month',(int)$From[1])->where('year',(int)$From[2]);
            }elseif(!empty($ToDay)){
                $To = explode('-',$ToDay);
                $Model = $Model->where('date',(int)$To[0])->where('month',(int)$To[1])->where('year',(int)$To[2]);
            }else{
                return Response::json([
                    'error'         => false,
                    'message'       => 'success',
                    'item_page'     => $itemPage,
                    'total'         => 0,
                    'data'          => []
                ]);
            }
        }

      if($cmd == 'EXPORT'){
          $Model = $Model->groupBy('user_id')->orderBy('id','DESC');
          if((!empty($FromDay) && !empty($ToDay)) || !empty($Month)) {
              $Data = $Model->with('User')->get(array(DB::raw(
                  'user_id,sum(generate) as generate,
                   sum(success) as success,
                   sum(total_return) as total_return,
                   sum(sc_pvc) as sc_pvc,
                   sum(sc_cod) as sc_cod,
                   sum(sc_pbh) as sc_pbh,
                   sum(sc_discount_pvc) as sc_discount_pvc,
                   sum(sc_discount_cod) as sc_discount_cod,
                   sum(money_collect) as money_collect,
                   sum(sc_pvk) as sc_pvk,
                   sum(return_sc_cod) as return_sc_cod,
                   sum(return_sc_pbh) as return_sc_pbh,
                   sum(return_sc_pbh) as return_sc_pbh,
                   sum(return_sc_discount_cod) as return_sc_discount_cod,
                   sum(return_money_collect) as return_money_collect,
                   sum(lsuccess) as lsuccess,
                   sum(lreturn) as lreturn,
                   sum(lreturn_sc_cod) as lreturn_sc_cod,
                   sum(lreturn_sc_pch) as lreturn_sc_pch,
                   sum(lreturn_sc_pbh) as lreturn_sc_pbh,
                   sum(lreturn_sc_discount_cod) as lreturn_sc_discount_cod,
                   sum(lreturn_money_collect) as lreturn_money_collect,
                   sum(l_sc_pvk) as l_sc_pvk'
              )))->toArray();
          }else{
              $Data = $Model->with(['User'])->get()->toArray();
          }

            return $this->ExcelReport($Data);
        }

        $ModelTotal = clone $Model;
        $ModelSum   = clone $Model;

        $Total      = $ModelTotal->first([DB::raw('COUNT(DISTINCT user_id) as total')]);
        $Data       = [];
        $DataSum    = [];


        if(isset($Total['total']) && $Total['total'] > 0){
            $DataSum    = $ModelSum->first(array(DB::raw(
                'sum(generate) as generate,
                   sum(success) as success,
                   sum(total_return) as total_return,
                   sum(sc_pvc) as sc_pvc,
                   sum(sc_cod) as sc_cod,
                   sum(sc_pbh) as sc_pbh,
                   sum(sc_discount_pvc) as sc_discount_pvc,
                   sum(sc_discount_cod) as sc_discount_cod,
                   sum(money_collect) as money_collect,
                   sum(sc_pvk) as sc_pvk,
                   sum(return_sc_cod) as return_sc_cod,
                   sum(return_sc_pbh) as return_sc_pbh,
                   sum(return_sc_pbh) as return_sc_pbh,
                   sum(return_sc_discount_cod) as return_sc_discount_cod,
                   sum(return_money_collect) as return_money_collect,
                   sum(lsuccess) as lsuccess,
                   sum(lreturn) as lreturn,
                   sum(lreturn_sc_cod) as lreturn_sc_cod,
                   sum(lreturn_sc_pch) as lreturn_sc_pch,
                   sum(lreturn_sc_pbh) as lreturn_sc_pbh,
                   sum(lreturn_sc_discount_cod) as lreturn_sc_discount_cod,
                   sum(lreturn_money_collect) as lreturn_money_collect,
                   sum(l_sc_pvk) as l_sc_pvk'
            )))->toArray();

            $Model = $Model->groupBy('user_id')->orderBy('generate','DESC');
            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }

            if((!empty($FromDay) && !empty($ToDay)) || !empty($Month)) {
                $Data = $Model->with('User')->get(array(DB::raw(
                  'user_id,sum(generate) as generate,
                   sum(success) as success,
                   sum(total_return) as total_return,
                   sum(sc_pvc) as sc_pvc,
                   sum(sc_cod) as sc_cod,
                   sum(sc_pbh) as sc_pbh,
                   sum(sc_discount_pvc) as sc_discount_pvc,
                   sum(sc_discount_cod) as sc_discount_cod,
                   sum(money_collect) as money_collect,
                   sum(sc_pvk) as sc_pvk,
                   sum(return_sc_cod) as return_sc_cod,
                   sum(return_sc_pbh) as return_sc_pbh,
                   sum(return_sc_pbh) as return_sc_pbh,
                   sum(return_sc_discount_cod) as return_sc_discount_cod,
                   sum(return_money_collect) as return_money_collect,
                   sum(lsuccess) as lsuccess,
                   sum(lreturn) as lreturn,
                   sum(lreturn_sc_cod) as lreturn_sc_cod,
                   sum(lreturn_sc_pch) as lreturn_sc_pch,
                   sum(lreturn_sc_pbh) as lreturn_sc_pbh,
                   sum(lreturn_sc_discount_cod) as lreturn_sc_discount_cod,
                   sum(lreturn_money_collect) as lreturn_money_collect,
                   sum(l_sc_pvk) as l_sc_pvk'
                )))->toArray();
            }else{
                $Data = $Model->with(['User'])->get()->toArray();
            }

        }

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'item_page'     => $itemPage,
            'total'         => $Total['total'],
            'data'          => $Data,
            'data_sum'      => $DataSum
        );

        return Response::json($contents);
	}

    /**
     * group
     */
    private function GroupStatus($group){
        $StatusOrderCtrl    = new \order\StatusOrderCtrl;
        if($group > 0){
            Input::merge(['group' => $group]);
        }
        $ListGroupStatus    = $StatusOrderCtrl->getStatusgroup(false);
        if(!empty($ListGroupStatus)){
            $GroupStatus    = [];
            foreach($ListGroupStatus as $val){
                $GroupStatus[(int)$val['id']]   = [];
                if(!empty($val['group_order_status'])){
                    foreach($val['group_order_status'] as $v){
                        $GroupStatus[(int)$val['id']][] = (int)$v['order_status_code'];
                    }
                }
            }
            return $GroupStatus;
        }else{
            return ['error' => true, 'message' => 'GROUP_STATUS_EMPTY'];
        }
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

        $Data   = $Model->where('merchant_id',$Id)->first(array('merchant_id', 'balance', 'freeze', 'provisional'));
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

        return Response::json($contents);
    }

    private function getExcel($Data){
        $FileName   = 'Danh_sach_khach_hang';

        $ListId        = [0];
        $User          = [];

        if(!empty($Data)){
            foreach($Data as $val){
                $ListId[]   = (int)$val['merchant_id'];
            }
            $ModelUser  = new \User;
            $ListId     = array_unique($ListId);
            $User = $ModelUser->getUserById($ListId);

        }

        return \Excel::create($FileName, function($excel) use($Data, $User){
            $excel->sheet('Sheet1', function($sheet) use($Data, $User){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách khách hàng'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,
                    'H'     =>  30, 'I'     =>  30, 'J'     => 30
                ));
                $sheet->setMergeColumn(array(
                    'columns' => array('A','B','C','D','E','F','G','H','I', 'J'),
                    'rows' => array(
                        array(3,4)
                    )
                ));

                $sheet->row(3, array(
                    'STT', 'Khách hàng', 'Email', 'Số điện thoại', 'Ngày tạo', 'Level', 'Số dư', 'Phí vận chuyển tạm tính', 'Thu hộ tạm tính', 'Quota'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });

                $sheet->setBorder('A3:J4', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        isset($User[$val['merchant_id']]) ? $User[$val['merchant_id']]['fullname'] : '',
                        isset($User[$val['merchant_id']]) ? $User[$val['merchant_id']]['email'] : '',
                        isset($User[$val['merchant_id']]) ? $User[$val['merchant_id']]['phone'] : '',
                        $val['time_create'] > 0 ? date("d/m/y H:m",$val['time_create']) : '',
                        $val['level'],
                        isset($val['balance']) ? number_format($val['balance']) : '',
                        isset($val['freeze']) ? number_format($val['freeze']) : '',
                        isset($val['provisional']) ? number_format($val['provisional']) : '',
                        isset($val['quota']) ? number_format($val['quota']) : '',
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }

    private function ExcelReport($Data){
        $ListId        = [0];
        $User          = [];

        if(!empty($Data)){
            foreach($Data as $val){
                $ListId[]   = (int)$val['user_id'];
            }
            $ModelUser  = new \User;
            $ListId     = array_unique($ListId);
            $User = $ModelUser->getUserById($ListId);

        }

        Excel::selectSheetsByIndex(0)->load('/data/www/html/storage/template/accounting/bao_cao_khach_hang.xls', function($reader) use($Data, $User) {
            $reader->sheet(0,function($sheet) use($Data,$User)
            {
                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        isset($User[$val['user_id']]) ? $User[$val['user_id']]['fullname'] : '',
                        isset($User[$val['user_id']]) ? $User[$val['user_id']]['email'] : '',
                        isset($User[$val['user_id']]) ? $User[$val['user_id']]['phone'] : '',

                        $val['generate'],
                        $val['success'],
                        $val['total_return'],

                        isset($val['sc_pvc']) ? number_format($val['sc_pvc']) : '',
                        isset($val['sc_cod']) ? number_format($val['sc_cod']) : '',
                        isset($val['sc_pbh']) ? number_format($val['sc_pbh']) : '',
                        isset($val['sc_discount_pvc']) ? number_format($val['sc_discount_pvc']) : '',
                        isset($val['sc_discount_cod']) ? number_format($val['sc_discount_cod']) : '',
                        isset($val['money_collect']) ? number_format($val['money_collect']) : '',

                        isset($val['sc_pvk']) ? number_format($val['sc_pvk']) : '',

                        isset($val['return_sc_cod']) ? number_format($val['return_sc_cod']) : '',
                        isset($val['return_sc_pbh']) ? number_format($val['return_sc_pbh']) : '',
                        isset($val['return_sc_pch']) ? number_format($val['return_sc_pch']) : '',
                        isset($val['return_sc_discount_cod']) ? number_format($val['return_sc_discount_cod']) : '',
                        isset($val['return_money_collect']) ? number_format($val['return_money_collect']) : '',

                        $val['lsuccess'],
                        $val['lreturn'],

                        isset($val['l_sc_pvk']) ? number_format($val['l_sc_pvk']) : '',

                        isset($val['lreturn_sc_cod']) ? number_format($val['lreturn_sc_cod']) : '',
                        isset($val['lreturn_sc_pch']) ? number_format($val['lreturn_sc_pch']) : '',
                        isset($val['lreturn_sc_pbh']) ? number_format($val['lreturn_sc_pbh']) : '',
                        isset($val['lreturn_sc_discount_cod']) ? number_format($val['lreturn_sc_discount_cod']) : '',
                        isset($val['lreturn_money_collect']) ? number_format($val['lreturn_money_collect']) : ''
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        },'UTF-8',true)->export('xls');
    }

    public function getReport(){
        $InvoiceModel           = new InvoiceModel;
        $RequestModel           = new RequestModel;

        $UserId                 = Input::has('user_id') ? (int)Input::get('user_id')    : 0;
        $Time                   = Input::has('time')    ? trim(Input::get('time'))      : '';
        $Check                  = 0;

        if(empty($UserId) || empty($Time)){
            return Response::json([
                'error'         => true,
                'message'       => 'USER_OR_TIME_EMPTY',
                'data'          => []
            ]);
        }

        $Time       = str_replace('/', '-', $Time);
        $TimeStart  = strtotime('1-'.$Time. ' 00:00:00');
        $Time       = explode('-',$Time);
        $TimeEnd    = strtotime('1-'.($Time[0] + 1).'-'.$Time[1]. ' 00:00:00');

        $LastMonth  = $NextMonth    = $LastYear = $NextYear = 0;
        if($Time[0] == 12){
            $LastMonth  = 11;
            $NextMonth  = 1;
            $LastYear   = $Time[1];
            $NextYear   = $Time[1] + 1;
        }elseif($Time[0] == 1){
            $LastMonth  = 12;
            $NextMonth  = 2;
            $LastYear   = $Time[1] - 1;
            $NextYear   = $Time[1];
        }else{
            $LastMonth  = $Time[0] - 1;
            $NextMonth  = $Time[0] + 1;
            $LastYear   = $Time[1];
            $NextYear   = $Time[1];
        }

        $Data       = [
            'order'     => [
                'month'             => (int)$Time[0],
                'total'             => 0, // phát sinh
                'success'           => 0, // thành công
                'return'            => 0, // chuyển hoàn
                'backlog'           => 0, // tồn
                'delivering'        => 0,   // đang phát hàng
                'problem'           => 0,   // phát không thành công
                'confirm_return'    => 0,   // xác nhận chuyển hoàn
                'returning'         => 0,   // đang chuyển hoàn
                'cod'               => 0,   // vận đơn cod
                'no_cod'            => 0    // vận đơn không cod
            ],
            'next_month'    => [
                'month'             => $NextMonth,
                'total'             => 0, // phát sinh
                'success'           => 0, // thành công
                'return'            => 0, // chuyển hoàn
                'backlog'           => 0, // tồn
            ],
            'last_month'    => [
                'month'             => $LastMonth,
                'total'             => 0, // phát sinh
                'success'           => 0, // thành công
                'return'            => 0, // chuyển hoàn
                'backlog'           => 0, // tồn
            ],
            'total_money_collect'   => 0, // tổng tiền thu hộ
            'total_fee'             => 0, // tổng phí vận chuyển
            'ticket'                => [
                'total'                 => 0,
                'closed'                => 0, //'đã xử lý và đóng'
                'backlog'               => 0,
                'overtime'              => 0
        ]
        ];

        // Order
        $Invoice = $InvoiceModel::where('user_id', $UserId)
                                ->where(function($query) use($Time, $LastMonth, $NextMonth, $LastYear, $NextYear){
                                    $query->where(function($q) use($Time){
                                        $q->where('month', (int)$Time[0])
                                          ->where('year', (int)$Time[1]);
                                    })->orWhere(function($q) use ($LastMonth, $LastYear){
                                        $q->where('month', (int)$LastMonth)
                                            ->where('year', (int)$LastYear);
                                    })->orWhere(function($q) use ($NextMonth, $NextYear){
                                        $q->where('month', (int)$NextMonth)
                                            ->where('year', (int)$NextYear);
                                    });
                                })
                                ->get()->toArray();

        if(!empty($Invoice)){ // đã có hóa đơn
            foreach($Invoice as $val){
                if($val['month']    == $Time[0]){
                    $Check = 1;
                    $Data['order']['total']     = $val['total_success'] + $val['total_return'] + $val['total_backlog'];
                    $Data['order']['success']   = $val['total_success'];
                    $Data['order']['return']    = $val['total_return'];
                    $Data['order']['backlog']   = $val['total_backlog'];

                    $Data['order']['delivering']        = $val['total_delivering'];
                    $Data['order']['problem']           = $val['total_problem'];
                    $Data['order']['confirm_return']    = $val['total_confirm_return'];
                    $Data['order']['returning']         = $val['total_returning'];

                    $Data['order']['cod']               = $val['total_cod'];
                    $Data['order']['no_cod']            = $Data['order']['total'] - $val['total_cod'];

                    $Data['total_money_collect']        = $val['total_money_collect'] + $val['total_lmoney_collect'];
                    $Data['total_fee']                  = $val['total_sc_pvc'] - $val['total_sc_discount_pvc'] + $val['total_sc_cod'] - $val['total_sc_discount_cod']
                                                        + $val['total_sc_pbh'] + $val['total_sc_pvk'] + $val['total_sc_pch'] + $val['total_lsc_pvc'] + $val['total_lsc_cod']
                                                        + $val['total_lsc_pbh'] + $val['total_lsc_pvk'] + $val['total_lsc_pch'] - $val['total_lsc_discount_pvc'] - $val['total_lsc_discount_cod'];
                }elseif($val['month']   == $NextMonth){
                    $Data['next_month']['total']     = $val['total_success'] + $val['total_return'] + $val['total_backlog'];
                    $Data['next_month']['success']   = $val['total_success'];
                    $Data['next_month']['return']    = $val['total_return'];
                    $Data['next_month']['backlog']   = $val['total_backlog'];
                }else{
                    $Data['last_month']['total']     = $val['total_success'] + $val['total_return'] + $val['total_backlog'];
                    $Data['last_month']['success']   = $val['total_success'];
                    $Data['last_month']['return']    = $val['total_return'];
                    $Data['last_month']['backlog']   = $val['total_backlog'];
                }
            }
        }

        if(!$Check){ //chưa có hóa đơn của tháng
            if($Time[0] == 12){
                $TimeSuccessEnd     = strtotime(date(($Time[1] + 1).'-01-15 00:00:00'));
            }else{
                $TimeSuccessEnd     = strtotime(date($Time[1].'-'.($Time[0] + 1).'-'.'15 00:00:00'));
            }

            $TimeSuccessStartT  = strtotime(date($Time[1].'-'.$Time[0].'-'.'15 00:00:00'));

            $GroupStatus    = $this->GroupStatus(4);
            if(!isset($GroupStatus['error'])){
                $OrderModel         = new OrdersModel;// tháng này
                $ListOrder          = $OrderModel->where('invoice_id',0)
                    ->where('from_user_id', $UserId)
                    ->where('time_accept','>=', $TimeStart - $this->time_limit)
                    ->where('time_accept','<',  $TimeEnd)
                    ->where('time_pickup','>=', $TimeStart)
                    ->where('time_pickup','<',$TimeEnd)
                    ->where('time_success','<',$TimeSuccessEnd)
                    ->whereNotIn('status',$GroupStatus[33])
                    ->with('OrderDetail')
                    ->get(['id','status','time_pickup','time_success'])->toArray();

                $OrderModel         = new OrdersModel; // Tồn tháng trước thành công trong tháng
                $ListLOrder         = $OrderModel->where('invoice_id',0)
                                        ->where('from_user_id', $UserId)
                                        ->where('time_accept','>=', $TimeStart - $this->time_limit)
                                        ->where('time_accept','<',  $TimeEnd)
                                        ->where('time_pickup','<',$TimeStart)
                                        ->where('time_success','<=',$TimeSuccessStartT)
                                        ->where('time_success','<',$TimeSuccessEnd)
                                        ->whereIn('status',[52,53,66])
                                        ->with('OrderDetail')
                                        ->get(['id','status','time_pickup','time_success'])->toArray();

                if(!empty($ListOrder)){
                    foreach($ListOrder as $val){
                        $Data['total_fee']          += $val['order_detail']['sc_pvc'] + $val['order_detail']['sc_pvk'] - $val['order_detail']['sc_discount_pvc'];
                        $Data['order']['total']     += 1;

                        if(in_array($val['status'], $GroupStatus[30])){ // THành công
                            $Data['total_money_collect']    += $val['order_detail']['money_collect'];
                            $Data['total_fee']              += $val['order_detail']['sc_cod'] + $val['order_detail']['sc_pbh'] - $val['order_detail']['sc_discount_cod'];
                            $Data['order']['success']       += 1;
                        }elseif(in_array($val['status'], $GroupStatus[36])){// Chuyển hoàn
                            $Data['total_fee']          += $val['order_detail']['sc_pch'];
                            $Data['order']['return']    += 1;
                        }else{
                            $Data['total_fee']          += $val['order_detail']['sc_cod'] + $val['order_detail']['sc_pbh'] - $val['order_detail']['sc_discount_cod'];

                            $Data['order']['backlog']   += 1;
                            if(in_array($val['status'], $GroupStatus[28])){
                                $Data['order']['delivering']            += 1;
                            }elseif(in_array($val['status'], $GroupStatus[29])){
                                $Data['order']['problem']               += 1;
                            }elseif(in_array($val['status'], $GroupStatus[31])){
                                $Data['order']['confirm_return']        += 1;
                            }elseif(in_array($val['status'], $GroupStatus[32])){
                                $Data['order']['returning']             += 1;
                            }
                        }

                        if($val['order_detail']['money_collect'] > 0){
                            $Data['order']['cod']               += 1;
                        }else{
                            $Data['order']['no_cod']            += 1;
                        }
                    }
                }

                if(!empty($ListLOrder)){
                    foreach($ListLOrder as $val){
                        $Data['total_fee']          += $val['order_detail']['sc_pvc'] + $val['order_detail']['sc_pvk'] - $val['order_detail']['sc_discount_pvc'];

                        if(in_array($val['status'], $GroupStatus[30])){ // THành công
                            $Data['total_money_collect']    += $val['order_detail']['money_collect'];
                            $Data['total_fee']              += $val['order_detail']['sc_cod'] + $val['order_detail']['sc_pbh'] - $val['order_detail']['sc_discount_cod'];
                        }elseif(in_array($val['status'], $GroupStatus[36])){// Chuyển hoàn
                            $Data['total_fee']          += $val['order_detail']['sc_pch'];
                        }
                    }
                }
            }
        }

        //Ticket
        $RequestModel    = $RequestModel::where('user_id', $UserId)->where('time_create', '>=', $TimeStart)
                                   ->where('time_create', '<', $TimeEnd);
        $RequestModelC  = clone $RequestModel;

        $Request         = $RequestModel->groupBy('status')->get(array('status',DB::raw('count(*) as count')))->toArray();
        if(!empty($Request)){
            foreach($Request as $val){
                $Data['ticket']['total']    += $val['count'];
                if(in_array($val['status'], ['CLOSED', 'PROCESSED'])){
                    $Data['ticket']['closed']       += $val['count'];
                }else{
                    $Data['ticket']['backlog']      += $val['count'];
                }
            }
        }

        if($TimeEnd > $this->time()){
            $TimeEnd    = $this->time();
        }
        // Ticket overtime
        $TotalOverTime    = $RequestModelC->where('time_over','>',0)->where(function($query) use($TimeEnd){
            $query->where(function($q){
                $q->where('status','CLOSED')->whereRaw('time_update > time_over');
            })
            ->orWhere(function($q) use($TimeEnd){
                $q->where('status','<>','CLOSED')->where('time_over','<=',$TimeEnd);
            });
        })->count();
        if($TotalOverTime > 0){
            $Data['ticket']['overtime'] = $TotalOverTime;
        }

        $contents = array(
            'error'         => false,
            'code'          => 'SUCCESS',
            'error_message' => 'Thành công',
            'data'          => $Data
        );
        return Response::json($contents);
    }

    public function getReportLocation(){
        $OrdersModel    = new OrdersModel;

        $UserId                 = Input::has('user_id') ? (int)Input::get('user_id')    : 0;
        $Time                   = Input::has('time')    ? trim(Input::get('time'))      : '';

        if(empty($UserId) || empty($Time)){
            return Response::json([
                'error'         => true,
                'message'       => 'USER_OR_TIME_EMPTY',
                'data'          => []
            ]);
        }

        $Data = [
          'CTTT'   => 0, // cùng tỉnh trung tâm
          'CTHX'   => 0,
          'LTTT'   => 0, // liên tỉnh trung tâm
          'LTHX'   => 0
        ];

        $Time       = str_replace('/', '-', $Time);
        $TimeStart  = strtotime('1-'.$Time. ' 00:00:00');
        $Time       = explode('-',$Time);
        $TimeEnd    = strtotime('1-'.($Time[0] + 1).'-'.$Time[1]. ' 00:00:00');

        $ListOrder  = $OrdersModel::where('from_user_id', $UserId)
                                  ->where('time_accept','>=', $TimeStart - 86400*60)
                                  ->where('time_accept','<', $TimeEnd)
                                  ->where('time_pickup', '>=', $TimeStart)
                                  ->where('time_pickup','<', $TimeEnd)
                                  ->with('ToOrderAddress')
                                  ->get(['from_city_id','from_district_id','from_user_id','status','to_address_id'])->toArray();

        if(!empty($ListOrder)){
            $ListToAddress  = [];
            foreach($ListOrder as $val){
                if(!empty($val['to_order_address']) && $val['to_order_address']['province_id'] > 0){
                    $ListToAddress[]    = (int)$val['to_order_address']['province_id'];
                }
                if($val['from_district_id'] > 0){
                    $ListToAddress[]    = (int)$val['from_district_id'];
                }
            }

            if(!empty($ListToAddress)){
                $ListToAddress  = array_unique($ListToAddress);
                $AreaLocation   =  AreaLocationModel::where('active',1)->whereIn('province_id', $ListToAddress)->get(['province_id', 'location_id'])->toArray();
                $Location       = [];

                if(!empty($AreaLocation)){
                    foreach($AreaLocation as $val){
                        $Location[(int)$val['province_id']] = (int)$val['location_id'];
                    }
                }
            }

            foreach($ListOrder as $val){
                if(!empty($val['to_order_address'])){
                    if($val['from_city_id'] == $val['to_order_address']['city_id']){
                        if(isset($Location[(int)$val['to_order_address']['province_id']])){
                            if($Location[(int)$val['to_order_address']['province_id']] == 1){
                                $Data['CTTT']   += 1;
                            }else{
                                $Data['CTHX']   += 1;
                            }
                        }
                    }else{
                        if(isset($Location[(int)$val['to_order_address']['province_id']])){
                            if($Location[(int)$val['to_order_address']['province_id']] == 1){
                                $Data['LTTT']   += 1;
                            }else{
                                $Data['LTHX']   += 1;
                            }
                        }
                    }
                }
            }
        }

        return Response::json([
            'error'         => false,
            'code'          => 'SUCCESS',
            'error_message' => 'Thành công',
            'data'          => $Data
        ]);
    }

    /*
     * Report Order   -- thống kê đơn hàng phát sinh
     */
    public function getReportOrder(){
        $page                   = Input::has('page')            ? (int)Input::get('page')           : 1;
        $Search                 = Input::has('search')          ? trim(Input::get('search'))        : '';
        $Month                  = Input::has('month')           ? trim(Input::get('month'))         : '';
        $SortDate               = Input::has('sort_date')       ? trim(Input::get('sort_date'))     : '';
        $SortValue              = Input::has('sort_value')      ? trim(Input::get('sort_value'))    : '';

        if(empty($Month)){
            return $this->ResponseData(false);
        }

        $Model          = new \accountingmodel\ReportOrderModel;
        $Time = explode('-',$Month);
        $Month  = $Time[0];
        $Year   = $Time[1];

        $Model  = $Model::where('year',$Year)->where('month',$Month);
        if(!empty($Search)){
            $ModelUser  = new \User;
            if (filter_var($Search, FILTER_VALIDATE_EMAIL)){  // search email
                $ModelUser          = $ModelUser->where('email','LIKE','%'.$Search.'%');
            }elseif(filter_var((int)$Search, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $ModelUser          = $ModelUser->where('phone','LIKE','%'.$Search.'%');
            }else{ // search code
                $ModelUser          = $ModelUser->where('fullname','LIKE','%'.$Search.'%');
            }
            $ListUser = $ModelUser->lists('id');

            if(!empty($ListUser)){
                $Model  = $Model->whereIn('user_id',$ListUser);
            }else{
                return $this->ResponseData(false);
            }
        }

        $ModelTotal     = clone $Model;
        $ModelSort      = clone $Model;
        $this->total    = $ModelTotal->distinct('user_id')->count('user_id');

        if($this->total > 0){
            if(!empty($SortDate) && !empty($SortValue)){
                $ModelSort  = $ModelSort->where('date',$SortDate)->orderBy('generate',$SortValue);
            }else{
                $ModelSort  =  $ModelSort->distinct('user_id');
            }
            $itemPage   = 20;
            $offset     = ($page - 1)*$itemPage;
            $ListSort   = $ModelSort->skip($offset)->take($itemPage)->lists('user_id');

            if(!empty($ListSort)){
                $Model      = $Model->whereIn('user_id',$ListSort);
            }else{
                $this->total = 0;
                return $this->ResponseData(false);
            }

            $UserModel  = new \User;
            $ListUser   = $UserModel->getUserById($ListSort);
            $ListSort   = array_flip($ListSort);

            $Data = $Model->orderBy('month','ASC')->orderBy('date','ASC')->get()->toArray();
            if(!empty($Data)){
                $LastReport = [];
                foreach($Data as $val){
                    if(!isset($this->data[$ListSort[$val['user_id']]]['fullname'])){
                        $this->data[$ListSort[$val['user_id']]]['fullname'] = isset($ListUser[$val['user_id']]['fullname']) ? $ListUser[$val['user_id']]['fullname']    : '';
                        $this->data[$ListSort[$val['user_id']]]['email']    = isset($ListUser[$val['user_id']]['email'])    ? $ListUser[$val['user_id']]['email']       : '';
                        $this->data[$ListSort[$val['user_id']]]['phone']    = isset($ListUser[$val['user_id']]['phone'])    ? $ListUser[$val['user_id']]['phone']       : '';
                    }

                    if(!isset($LastReport[$ListSort[$val['user_id']]])){
                        $LastReport[$val['user_id']]    = [
                            'generate'  => 0,
                            'pickup'    => 0
                        ];
                    }

                    $this->data[$ListSort[$val['user_id']]][(int)$val['date']]  = [
                        'date'          => (int)$val['date'],
                        'generate'      => (int)$val['generate'],
                        'pickup'        => (int)$val['pickup'],
                        'sub_generate'  => isset($LastReport[$ListSort[$val['user_id']]])  ? ($val['generate'] - $LastReport[$ListSort[$val['user_id']]]['generate'])  : 0,
                        'sub_pickup'    => isset($LastReport[$ListSort[$val['user_id']]])  ? ($val['pickup'] - $LastReport[$ListSort[$val['user_id']]]['pickup'])      : 0
                    ];

                    $LastReport[$ListSort[$val['user_id']]]    = [
                        'generate'  => (int)$val['generate'],
                        'pickup'    => (int)$val['pickup']
                    ];
                }
            }
        }

        return $this->ResponseData(false);
    }

    private function ResponseData($error = false){
        return Response::json([
            'error'         => $error,
            'code'          => $this->message,
            'error_message' => $this->error_message,
            'data'          => $this->data,
            'total'         => $this->total
        ]);
    }

}
