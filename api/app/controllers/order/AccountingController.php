<?php namespace order;
use Response;
use Input;
use DB;
use ordermodel\OrdersModel;
use ordermodel\DetailModel;
use ordermodel\InvoiceModel;
use accountingmodel\MerchantModel;
use User;
use Cache;
use Excel;
use Validator;

class AccountingController extends \BaseController {

    private $time_accept_start  = '';
    private $time_accept_end    = '';
    private $time_success_start  = '';
    private $time_success_end    = '';

    public function getProvidemerchant(){
        $page               = Input::has('page')                ? (int)Input::get('page')               : 1;
        $itemPage           = Input::has('limit')               ? Input::get('limit')                   : 20;
        $TimeAcceptStart    = Input::has('time_accept_start')   ? (int)Input::get('time_accept_start')  : 0;
        $TimeAcceptEnd      = Input::has('time_accept_end')     ? (int)Input::get('time_accept_end')    : 0;
        $TimeSuccessStart   = Input::has('time_success_start')  ? (int)Input::get('time_success_start') : 0;
        $TimeSuccessEnd     = Input::has('time_success_end')    ? (int)Input::get('time_success_end')   : 0;

        $TimeCreateStart    = Input::has('time_create_start')   ? (int)Input::get('time_create_start')  : 0;
        $TimeCreateEnd      = Input::has('time_create_end')     ? (int)Input::get('time_create_end')    : 0;
        $Merchant           = Input::has('merchant')            ? trim(Input::get('merchant'))          : '';
        $Cmd                = Input::has('cmd')                 ? strtoupper(trim(Input::get('cmd')))   : '';

        $Model              = new MerchantModel;

        $ListMerchant       = [];
        $ListId             = [];

        if(!empty($Merchant)){
            if (filter_var($Merchant, FILTER_VALIDATE_EMAIL)){  // search email
                $FieldUser  = 'email';
            }elseif(filter_var((int)$Merchant, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $FieldUser  = 'phone';
            }else{ // search fullname
                $FieldUser  = 'fullname';
            }

            $User   = User::where($FieldUser,'LIKE','%'.$Merchant.'%')->get(array('id','fullname','phone','email'))->toArray();
            if(!empty($User)){
                $ListUser = array_fetch($User,'id');
                $Model          = $Model->whereIn('merchant_id',$ListUser);
            }else{
                if($Cmd == 'EXPORT'){
                    return $this->ExportExcel([]);
                }

                return $this->__return_data( false, 'success', 0, []);
            }
        }

        if (!empty($TimeCreateStart)) {
            $Model = $Model->where('time_create', '>=', $TimeCreateStart);
        }

        if (!empty($TimeCreateEnd)) {
            $Model = $Model->where('time_create', '<', $TimeCreateEnd);
        }

        $TotalModel = clone $Model;
        $Total      = $TotalModel->count();

        if($Total > 0) {
            if($Cmd == 'EXPORT'){
                $Merchant = $Model->orderBy('time_create', 'DESC')->get(['id', 'merchant_id','time_create']);
            }else{
                $itemPage = (int)$itemPage;
                $offset = ($page - 1) * $itemPage;
                $Merchant = $Model->orderBy('time_create', 'DESC')
                    ->skip($offset)
                    ->take($itemPage)->get(['id', 'merchant_id', 'time_create']);
            }

            if (!empty($Merchant)) {
                foreach($Merchant as $val){
                    $ListId[]                                           = (int)$val['merchant_id'];

                    $ListMerchant[(int)$val['merchant_id']]['time_create']     = $val['time_create'];

                    $ListMerchant[(int)$val['merchant_id']]['ps']       = 0;
                    $ListMerchant[(int)$val['merchant_id']]['tc']       = 0;
                    $ListMerchant[(int)$val['merchant_id']]['ch']       = 0;
                    $ListMerchant[(int)$val['merchant_id']]['ton']      = 0;

                    $ListMerchant[(int)$val['merchant_id']]['sc_pvc']   = 0;
                    $ListMerchant[(int)$val['merchant_id']]['sc_cod']   = 0;
                    $ListMerchant[(int)$val['merchant_id']]['sc_pvk']   = 0;
                    $ListMerchant[(int)$val['merchant_id']]['sc_pbh']   = 0;
                    $ListMerchant[(int)$val['merchant_id']]['sc_pch']   = 0;
                    $ListMerchant[(int)$val['merchant_id']]['sc_discount_pvc']   = 0;
                    $ListMerchant[(int)$val['merchant_id']]['sc_discount_cod']   = 0;
                    $ListMerchant[(int)$val['merchant_id']]['money_collect']     = 0;
                }

                $UserModel      = new User;
                $OrderModel     = new OrdersModel;

                // get User
                $ListUser   =   $UserModel->whereIn('id',$ListId)->get(['id','fullname','phone','email'])->toArray();
                if(!empty($ListUser)){
                    foreach($ListUser as $val){
                        $ListMerchant[(int)$val['id']]['fullname']      = $val['fullname'];
                        $ListMerchant[(int)$val['id']]['phone']         = $val['phone'];
                        $ListMerchant[(int)$val['id']]['email']         = $val['email'];
                    }
                }

                $OrderModel = $OrderModel->whereIn('from_user_id', $ListId);
                if (!empty($TimeAcceptStart)) {
                    $OrderModel = $OrderModel->where('time_accept', '>=', $TimeAcceptStart);
                    $this->time_accept_start    = date('m/d/Y', $TimeAcceptStart);
                } else {
                    $OrderModel = $OrderModel->where('time_accept', '>=', strtotime(date('Y-m-1 00:00:00')));
                    $this->time_accept_start    = date('m/d/Y', strtotime(date('Y-m-1 00:00:00')));
                }

                if (!empty($TimeAcceptEnd)) {
                    $OrderModel = $OrderModel->where('time_accept', '<', $TimeAcceptEnd);
                    $this->time_accept_end      = date('m/d/Y', $TimeAcceptEnd);
                }

                if (!empty($TimeSuccessStart)) {
                    $OrderModel = $OrderModel->where('time_success', '>=', $TimeSuccessStart);
                    $this->time_success_start    = date('m/d/Y', $TimeSuccessStart);
                }

                if (!empty($TimeSuccessEnd)) {
                    $OrderModel = $OrderModel->where('time_success', '<', $TimeSuccessEnd);
                    $this->time_success_end      = date('m/d/Y', $TimeSuccessEnd);
                }

                $Data = $OrderModel->with('OrderDetail')->get(['id', 'status','time_success','time_accept', 'from_user_id'])->toArray();

                if(!empty($Data)){
                    foreach($Data as $val){
                        if(isset($ListMerchant[(int)$val['from_user_id']])){
                            $ListMerchant[(int)$val['from_user_id']]['ps']  += 1;
                            if((int)$val['status'] == 66){
                                $ListMerchant[(int)$val['from_user_id']]['ch']  += 1;
                                if(isset($val['order_detail'])){
                                    $ListMerchant[(int)$val['from_user_id']]['sc_pvc']   += $val['order_detail']['sc_pvc'];
                                    $ListMerchant[(int)$val['from_user_id']]['sc_pvk']   += $val['order_detail']['sc_pvk'];
                                    $ListMerchant[(int)$val['from_user_id']]['sc_pch']   += $val['order_detail']['sc_pch'];
                                    $ListMerchant[(int)$val['from_user_id']]['sc_discount_pvc']   += $val['order_detail']['sc_discount_pvc'];
                                }
                            }else{
                                if(isset($val['order_detail'])){
                                    $ListMerchant[(int)$val['from_user_id']]['sc_pvc']   += $val['order_detail']['sc_pvc'];
                                    $ListMerchant[(int)$val['from_user_id']]['sc_cod']   += $val['order_detail']['sc_cod'];
                                    $ListMerchant[(int)$val['from_user_id']]['sc_pvk']   += $val['order_detail']['sc_pvk'];
                                    $ListMerchant[(int)$val['from_user_id']]['sc_pbh']   += $val['order_detail']['sc_pbh'];
                                    $ListMerchant[(int)$val['from_user_id']]['sc_discount_pvc']   += $val['order_detail']['sc_discount_pvc'];
                                    $ListMerchant[(int)$val['from_user_id']]['sc_discount_cod']   += $val['order_detail']['sc_discount_cod'];
                                    $ListMerchant[(int)$val['from_user_id']]['money_collect']     += $val['order_detail']['money_collect'];
                                }

                                if(in_array((int)$val['status'], [52,53])){
                                    $ListMerchant[(int)$val['from_user_id']]['tc']  += 1;
                                }else{
                                    $ListMerchant[(int)$val['from_user_id']]['ton']  += 1;
                                }
                            }
                        }
                    }
                }
            }
        }

        if($Cmd == 'EXPORT'){
            return $this->ExportExcel($ListMerchant);
        }

        return $this->__return_data(false, 'success', $Total, $ListMerchant);
    }

    private function __return_data($error, $message, $total, $data){
        return Response::json([
            'error'         => $error,
            'message'       => $message,
            'total'         => $total,
            'data'          => $data
        ]);
    }

    public function ExportExcel($Data){
        $FileName   = 'Bao_cao_khach_hang';
        if(!empty($this->time_accept_start) || !empty($this->time_accept_end)){
            $FileName .= '_duyet_tu_'.$this->time_accept_start.'_den_'.$this->time_accept_end;
        }
        if(!empty($this->time_success_start) || !empty($this->time_success_end)){
            $FileName .= '_thanh_cong_tu_'.$this->time_success_start.'_den_'.$this->time_success_end;
        }

        return Excel::create($FileName, function($excel) use($Data){
            $excel->sheet('Sheet1', function($sheet) use($Data){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách vận đơn'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,
                    'H'     =>  30, 'I'     =>  30, 'J'     =>  30, 'K'     =>  30, 'L'     =>  30, 'M'     =>  30, 'N'     =>  30,
                    'O'     =>  30, 'P'     => 30, 'Q'     => 30
                ));
                $sheet->setMergeColumn(array(
                    'columns' => array('A','Q'),
                    'rows' => array(
                        array(3,4)
                    )
                ));
                $sheet->mergeCells('B3:E3');
                $sheet->mergeCells('F3:I3');
                $sheet->mergeCells('J3:P3');

                $sheet->row(3, array(
                    'STT', 'Khách hàng', '', '', '', 'Vận đơn', '', '', '', 'Phí', '', '', '', '', '', '', 'Thu hộ'
                ));

                $sheet->row(4, array(
                    '', 'Thời gian tạo', 'Họ tên', 'Email', 'Số điện thoại', 'Phát sinh', 'Thành công', 'Chuyển hoàn', 'Tồn',
                    'Phí vận chuyển', 'Phí Cod', 'Phí bảo hiểm', 'Phí vượt cân', 'Phí chuyển hoàn', 'Miễn phí vận chuyển', 'Miễn phí CoD'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });
                $sheet->row(4,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });
                $sheet->setBorder('A3:Q4', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        $val['time_create'] > 0 ? date("d/m/y H:m",$val['time_create']) : '',
                        isset($val['fullname']) ? $val['fullname'] : '',
                        isset($val['email']) ? $val['email'] : '',
                        isset($val['phone']) ? $val['phone'] : '',

                        $val['ps'],
                        $val['tc'],
                        $val['ch'],
                        $val['ton'],

                        number_format($val['sc_pvc']),
                        number_format($val['sc_cod']),
                        number_format($val['sc_pbh']),
                        number_format($val['sc_pvk']),
                        number_format($val['sc_pch']),
                        number_format($val['sc_discount_pvc']),
                        number_format($val['sc_discount_cod']),
                        number_format($val['money_collect'])
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }



}
?>