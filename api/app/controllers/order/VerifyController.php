<?php namespace order;

use Input;
use Response;
use Exception;
use DB;
use ordermodel\VerifyModel;
use ordermodel\OrdersModel;
use accountingmodel\MerchantModel;
use accountingmodel\TransactionModel;
use User;
use Excel;
use LMongo;
use File;

class VerifyController extends \BaseController {
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */


    public function getStatistic(){
        $TimeAcceptStart      = Input::has('time_accept_start')     ? trim(Input::get('time_accept_start'))     : strtotime(date("Y-m-d 00:00:00", strtotime("-2 months")));
        $TimePickupStart      = Input::has('time_start')            ? trim(Input::get('time_start'))            : strtotime(date('Y-m-1 00:00:00'));
        $TimePickupEnd        = Input::has('time_end')              ? trim(Input::get('time_end'))              : time();
        

        $UserInfo       = $this->UserInfo();
        $id             = (int)$UserInfo['id'];

        $Model          = new OrdersModel;

        $Model          = $Model->where('from_user_id', $id);
        $Model          = $Model->where('time_accept','>=', (int)$TimeAcceptStart);
        $Model          = $Model->where('time_accept','<=', (int)$TimePickupEnd);

        $Model          = $Model->where('time_pickup','>=', (int)$TimePickupStart);
        $Model          = $Model->where('time_pickup','<=', (int)$TimePickupEnd);
        
        

        $DonDaThanhToanChuyenTien   = [
            'collect'   => 0,
            'count'     => 0
        ];
        $DonDangGiaoHang            = [
            'collect'   => 0,
            'count'     => 0
        ];
        $DonDaGiaoChoChuyenTien     = [
            'collect'   => 0,
            'count'     => 0
        ];

        $_ModelDonDaThanhToanChuyenTien = clone $Model;
        $_ModelDonDangGiaoHang          = clone $Model;
        $_ModelDonDaGiaoChoChuyenTien   = clone $Model;

        $_ModelDonDaThanhToanChuyenTien = $_ModelDonDaThanhToanChuyenTien->where('verify_id', '>', 0)->with(['OrderDetail'])->select(['id', 'status'])->get()->toArray();
        $_ModelDonDangGiaoHang          = $_ModelDonDangGiaoHang->whereNotIn('status', [52,53,66,67])->where('verify_id', 0)->with(['OrderDetail'])->select(['id', 'status'])->get()->toArray();
        $_ModelDonDaGiaoChoChuyenTien   = $_ModelDonDaGiaoChoChuyenTien->whereIn('status', [52,53,66,67])->where('verify_id', 0)->with(['OrderDetail'])->select(['id', 'status'])->get()->toArray();
        

        foreach($_ModelDonDaThanhToanChuyenTien as $value){
            if(in_array($value['status'], [52,53])){
                $DonDaThanhToanChuyenTien['collect'] += $value['order_detail']['money_collect'];
            }
            $DonDaThanhToanChuyenTien['count'] ++ ;
        }
        foreach($_ModelDonDangGiaoHang as $value){
            $DonDangGiaoHang['collect'] += $value['order_detail']['money_collect'];
            $DonDangGiaoHang['count'] ++ ;
        }
        foreach($_ModelDonDaGiaoChoChuyenTien as $value){
            if(in_array($value['status'], [52,53])){
                $DonDaGiaoChoChuyenTien['collect'] += $value['order_detail']['money_collect'];
            }
            $DonDaGiaoChoChuyenTien['count'] ++ ;
        }
        return Response::json([
            'error'         => false,
            'error_message' => '',
            'data'          => [
                'DonDaThanhToanChuyenTien' => $DonDaThanhToanChuyenTien,
                'DonDangGiaoHang'        => $DonDangGiaoHang,
                'DonDaGiaoChoChuyenTien' => $DonDaGiaoChoChuyenTien,
            ],
            'params' => [
                'time_pickup_start'=> $TimePickupStart,
                'TimePickupEnd'=> $TimePickupEnd,
                'TimeAcceptStart'=> $TimeAcceptStart,
                
            ]
            
        ]);
        
    }


	public function getIndex()
	{
        $page           = Input::has('page')            ? (int)Input::get('page')                : 1;
        $itemPage       = Input::has('limit')           ? Input::get('limit')                    : 20;
        $TimeStart      = Input::has('time_start')      ? trim(Input::get('time_start'))         : '';
        $TimeEnd        = Input::has('time_end')        ? trim(Input::get('time_end'))           : '';
        $Search         = Input::has('search')          ? (int)Input::get('search')              : 0;
        
        $UserInfo   = $this->UserInfo();
        $id         = (int)$UserInfo['id'];
        
        
        $Model = new VerifyModel;
        $Model = $Model->where('user_id',$id);
        $Total = 0;
        
        if(!empty($TimeStart)){
            if(($TimeEnd < $TimeStart) || (($TimeEnd - $TimeStart) > $this->time_limit)){
                return Response::json(
                    ['error'         => false,
                    'message'       => 'success',
                    'item_page'     => $itemPage,
                    'total'         => $Total,
                    'data'          => []]);
            }

            $Model = $Model->where('time_create','>=',$TimeStart);
        }else{
            $Model = $Model->where('time_create','>=',strtotime(date('Y-m-1 00:00:00')));
        }
        
        if(!empty($Search)){
            $Model = $Model->where('id',$Search);
        }

        if(!empty($TimeEnd)){
            $Model = $Model->where('time_create','<',$TimeEnd);
        }
        
        $ModelTotal = clone $Model;
        
        $Total = $ModelTotal->count();
        $Data  = array();
        
        $Model = $Model->orderBy('time_create','DESC');
        if($Total > 0){
            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }
            
            $Data = $Model->with(array('User' => function($query){
                $query->get(['id','email','fullname','phone']);
            }))->get()->toArray();
        }
        
        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'item_page'     => $itemPage,
            'total'         => $Total,
            'data'          => $Data
        );
        
        return Response::json($contents);
	}



    
    /**
    * API tra cứu tiền thu hộ
    * @author thinhnv <thinhnv@peacesoft.net>
    * @return Response <json>
    */
    public function VerifyAPI()
    {
        $page           = Input::has('page')            ? (int)Input::get('page')                : 1;
        $itemPage       = Input::has('limit')           ? Input::get('limit')                    : 20;
        $TimeStart      = Input::has('time_start')      ? trim(Input::get('time_start'))         : '';
        $TimeEnd        = Input::has('time_end')        ? trim(Input::get('time_end'))           : '';
        $Search         = Input::has('search')          ? (int)Input::get('search')              : 0;
        $cmd            = Input::has('cmd')          ?  (string)Input::get('cmd')                : "";
        $ApiKey         = Input::has('MerchantKey')     ? Input::get('MerchantKey')              : "";
        

        if(empty($ApiKey)){
            return Response::json(array(
                "error"         => true,
                "message"       => "Không tìm thấy merchantkey", //. Please contact administrator !
                "data"          => array(),
                "statusCode"    => 403
            ), 403);
        }

        $merchantId   = $this->_getMerchantId($ApiKey);

        if(!$merchantId){
            return Response::json(array(
                "error"         => true,
                "Error"       => "Merchantkey không tồn tại ", //. Please contact administrator !
                "data"          => array(),
            ));
        }

        $id         = (int)$merchantId;
        
        if(!empty($cmd) && $cmd == 'demo'){
            $Data = [
                [
                    "id"                    => 104152,
                    "user_id"               => 41882,
                    "user_nl_id"            => 166197,
                    "accept_id"             => 39942,
                    "email_nl"              => "thinh.nl@gmail.com",
                    "total_fee"             => 132000,
                    "total_money_collect"   => 790000,
                    "balance_available"     => -225500,
                    "config_balance"        => 200000,
                    "balance"               => 461500,
                    "type_payment"          => 1,
                    "transaction_code"      => "",
                    "transaction_id"        => 14278362,
                    "type"                  => 1,
                    "time_create"           => 1427765259,
                    "time_accept"           => 1427855251,
                    "time_start"            => 1427302800,
                    "time_end"              => 1427648399,
                    "status"                => "SUCCESS",
                    "note"                  => null,
                    "notification"          => 0,
                    "user"                  => [
                        "id"        => 41882,
                        "email"     => "nguyenvanthinhypbn@gmail.com",
                        "fullname"  => "Nguyễn Văn Thịnh",
                        "phone"     => "01626616817"
                    ]

                ],
                [
                    "id"                    => 104152,
                    "user_id"               => 41882,
                    "user_nl_id"            => 166197,
                    "accept_id"             => 39942,
                    "email_nl"              => "thinh.nl@gmail.com",
                    "total_fee"             => 132000,
                    "total_money_collect"   => 900000,
                    "balance_available"     => -325500,
                    "config_balance"        => 200000,
                    "balance"               => 461500,
                    "type_payment"          => 1,
                    "transaction_code"      => "",
                    "transaction_id"        => 14278362,
                    "type"                  => 1,
                    "time_create"           => 1427765259,
                    "time_accept"           => 1427855251,
                    "time_start"            => 1427302800,
                    "time_end"              => 1427648399,
                    "status"                => "SUCCESS",
                    "note"                  => null,
                    "notification"          => 0,
                    "user"                  => [
                        "id"        => 41882,
                        "email"     => "nguyenvanthinhypbn@gmail.com",
                        "fullname"  => "Nguyễn Văn Thịnh",
                        "phone"     => "01626616817"
                    ]

                ]
            ];

            return Response::json(
                    [
                        'error'        => false,
                        'error_message'       => '',
                        'item_page'     => $itemPage,
                        'total'         => sizeof($Data),
                        'total_page'    => ceil(sizeof($Data)/$itemPage),
                        'data'          => $Data
                    ]);

        }
        
        $Model = new VerifyModel;
        $Model = $Model->where('user_id', $id);
        $Total = 0;

        
        if(!empty($TimeStart)){
            if($TimeStart < $this->time() - $this->time_limit){
                return Response::json(
                    ['error'        => true,
                    'error_message'       => '',
                    'item_page'     => $itemPage,
                    'total'         => $Total,
                    'total_page'    => floor($Total/$itemPage),
                    'data'          => []
                    ]);
            }

            $Model = $Model->where('time_create','>=',$TimeStart);
        }else{

            $Model = $Model->where('time_create','>=',strtotime(date('Y-m-1 00:00:00')));
        }
        
        

        if(!empty($Search)){
            $Model = $Model->where('id',$Search);
        }

        if(!empty($TimeEnd)){
            $Model = $Model->where('time_create','<',$TimeEnd);
        }
        
        $ModelTotal = clone $Model;
        
        $Total = $ModelTotal->count();
        $Data  = array();
        
        $Model = $Model->orderBy('time_create','DESC');
        if($Total > 0){
            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }
            
            $Data = $Model->with(array('User' => function($query){
                $query->get(['id','email','fullname','phone']);
            }))->get()->toArray();
        }
        
        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'item_page'     => $itemPage,
            'total'         => $Total,
            'total_page'    => ceil($Total/$itemPage),
            'data'          => $Data,
            'statusCode'    => 200
        );
        
        return Response::json($contents);
    }


    // Lấy thông tin merchant từ Api key
    private function _getMerchantId($apiKey){
        $dbKey = \ApiKeyModel::where('key',$apiKey)->first(['user_id']);
        return empty($dbKey) ? 0 : $dbKey->user_id;   
    }

    /*
        Seller export excel
    */

    public function getExportexcel()
    {

        $TimeStart      = Input::has('time_start')      ? trim(Input::get('time_start'))         : 0;
        $TimeEnd        = Input::has('time_end')        ? trim(Input::get('time_end'))           : 0;

        $UserInfo   = $this->UserInfo();
        $id         = (int)$UserInfo['id'];


        $Model = new VerifyModel;
        $Model = $Model->where('user_id',$id);
        $Total = 0;

        if(!empty($TimeStart)){
            if($TimeStart < $this->time() - $this->time_limit){
                return Response::json(
                    ['error'         => false,
                        'message'       => 'success',
                        'total'         => $Total,
                        'data'          => []]);
            }
            $Model = $Model->where('time_create','>=',$TimeStart);
        }else{
            $Model = $Model->where('time_create','>=',strtotime(date('Y-m-1 00:00:00')));
        }

        if(!empty($TimeEnd)){
            $Model = $Model->where('time_create','<',$TimeEnd);
        }

        $ModelTotal = clone $Model;
        $Total = $ModelTotal->count();
        $Data  = array();

        $Model = $Model->orderBy('time_create','DESC');
        if($Total > 0){
            $Data = $Model->with(array('User' => function($query){
                $query->get(['id','email','fullname','phone']);
            }))->get()->toArray();
        }


        $FileName   = 'Ban_ke_thanh_toan';
        $Title      = 'Bản kê thanh toán';
        $Title1     = '';

        if($TimeStart > 0){
            $FileName .= '_tu_'.date('d-m-Y' , (int)$TimeStart);
            $Title1    .= 'Từ '.date('H:i d-m-Y' , (int)$TimeStart);
        }

        if($TimeEnd > 0){
            $FileName .= '_den_'.date('d-m-Y' , (int)$TimeEnd);
            $Title1    .= ' Đến '.date('H:i d-m-Y' , (int)$TimeEnd);
        }

        return Excel::create($FileName, function($excel) use($Data, $Title, $Title1){
            $excel->sheet('Sheet1', function($sheet) use($Data, $Title, $Title1){
                $sheet->mergeCells('C1:F1');
                $sheet->mergeCells('C2:F2');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','',$Title));
                $sheet->row(2, array('','',$Title1));

                $sheet->row(3, array(
                    'STT',
                    'Mã bản kê',
                    'Thời gian thành công',
                    'MGD chuyển tiền',
                    'Số dư hiện tại',
                    'Tiền thu hộ',
                    'Tổng phí' ,
                    'Tạm giữ',
                    'Thực nhận',
                    'Trạng thái'
                ));
                $sheet->row(3,function($row){
                    $row->setBackground('#989898');
                    $row->setFontSize(12);
                });
                $sheet->setBorder('A3:F3', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $ThucNhan = 0;
                    if($val['type'] == 2){
                        $ThucNhan = number_format($val['total_money_collect'] - $val['total_fee']);
                    }else{
                        $ThucNhan = ($val['total_money_collect'] + $val['balance'] - $val['total_fee'] + (($val['balance_available'] - $val['config_balance']) < 0 ? ($val['balance_available'] - $val['config_balance']) : 0)) > 0 ?
                            number_format($val['total_money_collect'] + $val['balance'] - $val['total_fee'] + (($val['balance_available'] - $val['config_balance']) < 0 ? ($val['balance_available'] - $val['config_balance']) : 0)) : 0;
                    }

                    $dataExport = array(
                        $i++,
                        (int)$val['id'],
                        date("d-M-y  H:m",$val['time_create']),
                        $val['transaction_id'],
                        number_format(trim($val['balance'])),
                        number_format($val['total_money_collect']),
                        number_format($val['total_fee']),
                        number_format(($val['balance_available'] - $val['config_balance']) > 0 ? 0 : ($val['balance_available'] - $val['config_balance'])),
                        $ThucNhan,
                        isset($this->list_status[$val['status']])   ? $this->list_status[$val['status']] : ''
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');

    }

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getShow($id = '')
	{
        $UserInfo   = $this->UserInfo();
        $UserId     = (int)$UserInfo['id'];

        $TimeStart      = Input::has('time_start')      ? trim(Input::get('time_start'))         : 0;
        $ApiKey         = Input::has('MerchantKey')     ? Input::get('MerchantKey')              : "";
        $id             = empty($id)                    ? (int)Input::get('verify_id')           : $id;

        if(empty($UserId)){
            if(empty($ApiKey)){
                return Response::json(array(
                    "error"         => true,
                    "message"       => "Không tìm thấy merchantkey", //. Please contact administrator !
                    "data"          => array(),
                    "statusCode"    => 403
                ), 403);
            }
            $UserId   = $this->_getMerchantId($ApiKey);

            if(!$UserId){
                return Response::json(array(
                    "error"         => true,
                    "Error"       => "Merchantkey không tồn tại ", //. Please contact administrator !
                    "data"          => array(),
                ));
            }
        }

        if(empty($TimeStart)){
            return Response::json([
                'error'     => false,
                'message'   => 'success',
                'data'      => []
            ]);
        }

        $validation = \Validator::make(array('id' => $id), array(
            'id'        => 'required|numeric|min:1'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }
        
        $Model = new VerifyModel;
        $Data   = $Model->where('id',$id)->where('user_id',$UserId)->with(array('Order' => function($query) use($TimeStart){
                                                                  $query->where('time_accept','>=',$TimeStart - $this->time_limit)
                                                                        ->where('time_accept','<',$TimeStart)
                                                                        ->with(['OrderDetail', 'OrderFulfillment'])->get(array('id','tracking_code','verify_id','status'));
                                                                }))->first(array('id'));
        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $Data
        );
        return Response::json($contents);
	}

    /*
     * Show verify freeze
     */
    public function getShowFreeze($id)
    {
        $page               = Input::has('page')            ? (int)Input::get('page')                           : 1;
        $TrackingCode       = Input::has('tracking_code')   ? strtoupper(trim(Input::get('tracking_code')))     : '';

        $itemPage           = 20;

        $VerifyFreezeModel  = VerifyFreezeModel::where('verify_id',(int)$id);

        if(!empty($TrackingCode)){
            $VerifyFreezeModel  = $VerifyFreezeModel->where('tracking_code',$TrackingCode);
        }

        $offset                 = ($page - 1)*$itemPage;
        $VerifyFreezeModel      = $VerifyFreezeModel->skip($offset)->take($itemPage);

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $VerifyFreezeModel->get()->toArray()
        );

        return Response::json($contents);
    }
}
