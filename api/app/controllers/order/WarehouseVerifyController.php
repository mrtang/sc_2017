<?php namespace order;

use Input;
use Response;
use Exception;
use DB;
use fulfillmentmodel\WareHouseVerifyModel;
use ordermodel\OrdersModel;
use accountingmodel\MerchantModel;
use accountingmodel\TransactionModel;
use User;
use Excel;
use LMongo;
use File;

class WarehouseVerifyController extends \BaseController {


    private $page       = 1;
    private $item_page  = 20;
    private $time_start = "";
    private $time_end   = "";
    private $search     = 0;
    
    
    private function __getModel (){
        $this->page           = Input::has('page')            ? (int)Input::get('page')                : 1;
        $this->item_page     = Input::has('limit')           ? Input::get('limit')                    : 20;
        $this->time_start     = Input::has('time_start')      ? trim(Input::get('time_start'))         : '';
        $this->time_end       = Input::has('time_end')        ? trim(Input::get('time_end'))           : '';
        $this->search         = Input::has('search')          ? (int)Input::get('search')              : 0;

        $UserInfo   = $this->UserInfo();
        $id         = (int)$UserInfo['id'];
        
        
        $Model = new WareHouseVerifyModel;
        $Model = $Model->where('user_id',$id);
        $Total = 0;
        
        if(!empty($this->time_start)){
            if(($this->time_end < $this->time_start) || (($this->time_end - $this->time_start) > $this->time_limit)){
                $this->_error = true;
                return false;
                
            }

            $Model = $Model->where('time_create','>=',$this->time_start);
        }else{
            $Model = $Model->where('time_create','>=',strtotime(date('Y-m-1 00:00:00')));
        }
        
        if(!empty($this->search)){
            $Model = $Model->where('id',$this->search);
        }

        if(!empty($this->time_end)){
            $Model = $Model->where('time_create','<',$this->time_end);
        }
        $Model = $Model->orderBy('time_create','DESC');

        return $Model;
    }

	public function getIndex()
	{


        $Model = $this->__getModel();
        if($this->_error){
            return $this->_ResponseData();
        }

        $CountModel = clone $Model;
        $Count      = $CountModel->count();
        $Data       = [];

        if($Count > 0){
            if((int)$this->item_page > 0){
                $offset     = ($this->page - 1) * $this->item_page;
                $Model      = $Model->skip($offset)->take($this->item_page);
            }

            $Data = $Model->get()->toArray();
        }

        $Addition = [
            'item_page' => $this->item_page,
            'total'     => $Count
        ];

        return $this->_ResponseData($Data, $Addition);
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
