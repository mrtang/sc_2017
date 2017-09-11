<?php namespace order;

use Input;
use Response;
use ordermodel\InvoiceModel;
use ordermodel\OrdersModel;
use ordermodel\AddressModel;
use WardModel;
use order\StatusOrderCtrl;
use Excel;

class InvoiceController extends \BaseController {
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
        $page           = Input::has('page')            ? (int)Input::get('page')                : 1;
        $itemPage       = Input::has('limit')           ? Input::get('limit')                    : 20;
        $TimeStart      = Input::has('time_start')      ? trim(Input::get('time_start'))         : '';
        $TimeEnd        = Input::has('time_end')        ? trim(Input::get('time_end'))           : '';
        $Search         = Input::has('search')          ? (int)Input::get('search')              : 0;
        
        $UserInfo   = $this->UserInfo();
        
        $id         = (int)$UserInfo['id'];
        
        
        $Model = new InvoiceModel;
        $Model = $Model->where('user_id',$id);
        
        if(!empty($TimeStart)){
            $Model = $Model->where('time_create','>=',$TimeStart);
        }
        
        if(!empty($TimeEnd)){
            $Model = $Model->where('time_create','<',$TimeEnd);
        }

        if($Search > 0){
            $Model = $Model->where('id',$Search);
        }
        
        $ModelTotal = $Model;
        
        $Total = $ModelTotal->count();
        $Data  = array();

        if($Total > 0){
            $Model = $Model->orderBy('time_create','DESC');
            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }
            
            $Data = $Model->get()->toArray();
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
    * @desc : API tra cứu lịch sử hóa đơn 
    * @author thinhnv <thinhnv@peacesoft.net>
    */

    public function InvoiceAPI(){
        
        $page           = Input::has('page')            ? (int)Input::get('page')                : 1;
        $itemPage       = Input::has('limit')           ? Input::get('limit')                    : 20;
        $ApiKey         = Input::has('MerchantKey')     ? Input::get('MerchantKey')                  : "";
        $TimeStart      = Input::has('time_start')      ? trim(Input::get('time_start'))         : '';
        $TimeEnd        = Input::has('time_end')        ? trim(Input::get('time_end'))           : '';
        $Search         = Input::has('search')          ? (int)Input::get('search')              : 0;


        if(empty($ApiKey)){
            return Response::json(array(
                "error"         => true,
                "message"       => "Không tìm thấy MerchantKey", //. Please contact administrator !
                "data"          => array(),
                "statusCode"    => 403
            ), 403);
        }


        $merchantId   = $this->_getMerchantId($ApiKey);

        if(!$merchantId){
            return Response::json(array(
                "error"         => true,
                "message"       => "MerchantKey không tồn tại ", //. Please contact administrator !
                "data"          => array(),
            ));
        }

        $Model = new InvoiceModel;
        $Model = $Model->where('user_id',$merchantId);

        if(!empty($TimeStart)){
            $Model = $Model->where('time_create','>=',$TimeStart);
        }
        
        if(!empty($TimeEnd)){
            $Model = $Model->where('time_create','<',$TimeEnd);
        }

        if($Search > 0){
            $Model = $Model->where('id',$Search);
        }
        
        $ModelTotal = $Model;
        
        $Total = $ModelTotal->count();
        $Data  = array();

        if($Total > 0){
            $Model = $Model->orderBy('time_create','DESC');
            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }
            
            $Data = $Model->get()->toArray();
        }
        
        $contents = array(
            'error'         => false,
            'error_message'       => 'success',
            'item_page'     => $itemPage,
            'total'         => $Total,
            'total_page'    => ceil($Total/$itemPage),
            'data'          => $Data,
        );
        
        return Response::json($contents);

    }

    // Lấy thông tin merchant từ Api key
    private function _getMerchantId($apiKey){
        $dbKey = \ApiKeyModel::where('key',$apiKey)->first(['user_id']);
        return empty($dbKey) ? 0 : $dbKey->user_id;   
    }

    private function GroupStatus(){
        $StatusOrderCtrl    = new StatusOrderCtrl;
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

    public function getExportexcel(){
        $InvoiceId  = Input::has('id')  ? (int)Input::get('id') : 0;

        if(empty($InvoiceId)){
            die;
        }

        $UserInfo   = $this->UserInfo();
        $Model      = new InvoiceModel;

        if($UserInfo['privilege'] == 0){
            $Model = $Model->where('user_id', (int)$UserInfo['id']);
        }

        $Invoice    = $Model->where('id',$InvoiceId)->first();
        if(!$Invoice->exists){
            die;
        }

        $FileName   = 'Danh_sach_van_đon_theo_hoa_don_so_'.$InvoiceId;

        $GroupStatus    = $this->GroupStatus();
        if(isset($GroupStatus['error'])){
            die;
        }

        /**
         *  Set time
         */
        if($Invoice->month == 12){
            $TimeAcceptEnd      = strtotime(date(($Invoice->year + 1).'-01-01 00:00:00'));
            $TimeAcceptStart    = strtotime(date($Invoice->year.'-'.($Invoice->month - 2).'-'.'01 00:00:00'));
        }else{
            if($Invoice->month == 1){
                $TimeAcceptStart    = strtotime(date(($Invoice->year - 1).'-11-'.'01 00:00:00'));
            }else{
                $TimeAcceptStart    = strtotime(date($Invoice->year.'-'.($Invoice->month - 2).'-'.'01 00:00:00'));
            }

            $TimeAcceptEnd      = strtotime(date($Invoice->year.'-'.($Invoice->month + 1).'-'.'01 00:00:00'));
        }
        $TimePickupEnd      = $TimeAcceptEnd;
        $TimePickupStart    = strtotime(date($Invoice->year.'-'.($Invoice->month).'-'.'01 00:00:00'));

        $OrderModel = new OrdersModel;
        // Lấy list đơn hàng thành công trong kỳ
        $ListOrder  = $OrderModel->where('time_accept','>=',$TimeAcceptStart)
                                 ->where('time_accept','<',$TimeAcceptEnd)
                                 ->where('invoice_id', $Invoice->id)
                                 ->orderBy('time_accept', 'ASC')
                                 ->with('OrderDetail')
                                 ->get()->toArray();

        $ListSuccess    = [];
        $ListReturn     = [];
        $ListLast       = []; // Tồn tháng trước

        $Courier    = [];
        $Service    = [];
        $City       = [];
        $Address    = [];
        $District   = [];
        $Ward       = [];
        $User       = [];
        $Status     = [];

        if(!empty($ListOrder) || !empty($ListOrderL)){
            $Courier    = $this->getCourier();
            $Service    = $this->getService();
            $City       = $this->getCity();
            $Status     = $this->getStatus();

            foreach($ListOrder as $val){ // THành công tháng này
                if($val['time_pickup'] < $TimePickupStart){ // Tồn tháng trước
                    $ListLast[] = $val;
                }else{ // trong tháng này
                    if(in_array($val['status'], $GroupStatus[19])){
                        $ListSuccess[]  = $val;
                    }else{
                        $ListReturn[]   = $val;
                    }
                }

                $ListDistrictId[] = $val['from_district_id'];
                $ListWardId[]     = $val['from_ward_id'];
                $ListToAddress[]  = $val['to_address_id'];
                $ListUser[]       = $val['from_user_id'];
            }

            if(isset($ListToAddress) && !empty($ListToAddress)){
                $AddressModel   = new AddressModel;
                $ListAddress    = $AddressModel::whereIn('id', $ListToAddress)->get()->toArray();
            }

            if(isset($ListAddress) && !empty($ListAddress)){
                foreach($ListAddress as $val){
                    $Address[$val['id']]    = $val;
                    $ListDistrictId[]       = (int)$val['province_id'];
                    $ListWardId[]           = (int)$val['ward_id'];
                }
            }

            $ListDistrictId = array_unique($ListDistrictId);
            $ListWardId     = array_unique($ListWardId);
            $ListUser       = array_unique($ListUser);

            if(!empty($ListDistrictId)){
                $District   = $this->getProvince($ListDistrictId);
            }

            if(!empty($ListUser)){
                $UserModel = new \User;
                $ListUser  =  $UserModel::whereIn('id',$ListUser)->get(['id','fullname', 'phone', 'email'])->toArray();
                if(!empty($ListUser)){
                    foreach($ListUser as $val){
                        $User[$val['id']]   = $val;
                    }
                }
            }

            if(!empty($ListWardId)){
                $WardModel = new WardModel;
                $ListWard  =  $WardModel::whereIn('id',$ListWardId)->get(['id','ward_name'])->toArray();
                if(!empty($ListWard)){
                    foreach($ListWard as $val){
                        $Ward[$val['id']]   = $val['ward_name'];
                    }
                }
            }
        }

        return Excel::create($FileName, function($excel) use($ListSuccess, $ListReturn, $ListLast, $Courier, $Service, $City, $Address, $District, $Ward, $User, $Status){

            /**
             * List thành công
             */
            $excel->sheet('Thành công', function($sheet) use($ListSuccess, $Courier, $Service, $City, $Address, $District, $Ward, $User, $Status){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách vận đơn thành công trong kỳ'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,
                    'H'     =>  30, 'I'     =>  30, 'J'     =>  30, 'K'     =>  30, 'L'     =>  30, 'M'     =>  30, 'N'     =>  30,
                    'O'     =>  30, 'P'     =>  30, 'Q'     =>  30, 'R'     =>  30, 'S'     =>  30, 'T'     =>  30, 'U'     =>  30,
                    'V'     =>  30, 'W'     =>  30, 'X'     =>  30, 'Y'     =>  30, 'Z'     =>  30, 'AA'     =>  30, 'AB'     =>  30,
                    'AC'     =>  30, 'AD'     =>  30, 'AE'     =>  30, 'AF'  => 30, 'AG'    => 30,   'AH'    =>  30, 'AI'    =>  30
                ));
                $sheet->setMergeColumn(array(
                    'columns' => array('A','B','C','D','E','F','G','H','I','J','K' ,'AH','AI'),
                    'rows' => array(
                        array(3,4)
                    )
                ));
                $sheet->mergeCells('L3:R3');
                $sheet->mergeCells('S3:Y3');
                $sheet->mergeCells('Z3:AB3');
                $sheet->mergeCells('AC3:AG3');

                $sheet->row(3, array(
                    'STT', 'Thời gian duyệt', 'Thời gian lấy hàng', 'Thời gian giao hàng', 'Bản kê đối soát', 'Mã vận đơn', 'Mã đơn hàng của KH',
                    'Hãng vận chuyển', 'Mã hãng vận chuyển', 'Dịch vụ', 'Trạng thái', 'Nơi gửi','','','','','', '', 'Nơi nhận', '', '', '', '', '', '',
                    'Thông tin sản phẩm', '', '',
                    'Thông tin phí', '', '', '', '', 'Giảm giá', 'Tổng tiền thu hộ'
                ));

                $sheet->row(4, array(
                    '', '', '', '', '', '', '', '', '', '', '',  'Họ tên', 'Email', 'Số điện thoại',  'Tỉnh/Thành phố',
                    'Quận/Huyện', 'Phường xã', 'Địa chỉ','Họ tên', 'Email', 'Số điện thoại',    'Tỉnh/Thành phố', 'Quận/Huyện', 'Phường xã','Địa chỉ',
                    'Tên sản phẩm', 'Tổng giá trị', 'Khối lượng', 'Phí vận chuyển', 'Phí thu hộ', 'Phí bảo hiểm', 'Phí vượt cân', 'Phí chuyển hoàn'
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
                $sheet->setBorder('A3:AI4', 'thin');

                $i = 1;
                foreach ($ListSuccess as $val) {
                    $dataExport = array(
                        $i++,
                        $val['time_accept'] > 0 ? date("d/m/y H:m",$val['time_accept']) : '',
                        $val['time_pickup'] > 0 ? date("d/m/y H:m",$val['time_pickup']) : '',
                        $val['time_success'] > 0 ? date("d/m/y H:m",$val['time_success']) : '',
                        $val['verify_id'],
                        $val['tracking_code'],
                        $val['order_code'],
                        isset($Courier[(int)$val['courier_id']]) ? $Courier[(int)$val['courier_id']] : 'HVC',
                        $val['courier_tracking_code'],
                        isset($Service[(int)$val['service_id']]) ? $Service[(int)$val['service_id']] : 'DV',
                        isset($Status[(int)$val['status']]) ? $Status[(int)$val['status']] : 'Trạng thái',

                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['fullname'] : '',
                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['email'] : '',
                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['phone'] : '',
                        isset($City[(int)$val['from_city_id']]) ? $City[(int)$val['from_city_id']] : '',
                        isset($District[(int)$val['from_district_id']]) ? $District[(int)$val['from_district_id']] : '',
                        isset($Ward[(int)$val['from_ward_id']]) ? $Ward[(int)$val['from_ward_id']] : '',
                        $val['from_address'],

                        $val['to_name'],
                        $val['to_email'],
                        $val['to_phone'],
                        (isset($Address[(int)$val['to_address_id']]) && isset($City[$Address[(int)$val['to_address_id']]['city_id']])) ? $City[$Address[(int)$val['to_address_id']]['city_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($District[$Address[(int)$val['to_address_id']]['province_id']])) ? $District[$Address[(int)$val['to_address_id']]['province_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($Ward[$Address[(int)$val['to_address_id']]['ward_id']])) ? $Ward[$Address[(int)$val['to_address_id']]['ward_id']] : '',
                        isset($Address[(int)$val['to_address_id']]) ? $Address[(int)$val['to_address_id']]['address'] : '',

                        $val['product_name'],
                        $val['total_amount'],
                        $val['total_weight'],

                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvc']) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['sc_cod']) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['sc_pbh']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvk']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pch']) : '',
                        isset($val['order_detail']) ? number_format(($val['order_detail']['sc_discount_pvc'] + (($val['status'] != 66) ? $val['order_detail']['sc_discount_cod'] : 0))) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['money_collect']) : ''
                    );
                    $sheet->appendRow($dataExport);
                }
            });

            /**
             * List Chuyển hoàn
             */
            $excel->sheet('Chuyển hoàn', function($sheet) use($ListReturn, $Courier, $Service, $City, $Address, $District, $Ward, $User, $Status){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách vận đơn chuyển hoàn trong kỳ'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,
                    'H'     =>  30, 'I'     =>  30, 'J'     =>  30, 'K'     =>  30, 'L'     =>  30, 'M'     =>  30, 'N'     =>  30,
                    'O'     =>  30, 'P'     =>  30, 'Q'     =>  30, 'R'     =>  30, 'S'     =>  30, 'T'     =>  30, 'U'     =>  30,
                    'V'     =>  30, 'W'     =>  30, 'X'     =>  30, 'Y'     =>  30, 'Z'     =>  30, 'AA'     =>  30, 'AB'     =>  30,
                    'AC'     =>  30, 'AD'     =>  30, 'AE'     =>  30, 'AF'  => 30, 'AG'    => 30,   'AH'    =>  30
                ));
                $sheet->setMergeColumn(array(
                    'columns' => array('A','B','C','D','E','F','G','H','I','J','AG','AH'),
                    'rows' => array(
                        array(3,4)
                    )
                ));
                $sheet->mergeCells('K3:Q3');
                $sheet->mergeCells('R3:X3');
                $sheet->mergeCells('Y3:AA3');
                $sheet->mergeCells('AB3:AF3');

                $sheet->row(3, array(
                    'STT', 'Thời gian duyệt', 'Thời gian lấy hàng', 'Thời gian giao hàng', 'Bản kê đối soát', 'Mã vận đơn',
                    'Hãng vận chuyển', 'Mã hãng vận chuyển', 'Dịch vụ', 'Trạng thái','Nơi gửi','','','','','', '', 'Nơi nhận', '', '', '', '', '', '',
                    'Thông tin sản phẩm', '', '',
                    'Thông tin phí', '', '', '', '', 'Giảm giá', 'Tổng tiền thu hộ'
                ));

                $sheet->row(4, array(
                    '', '', '', '', '', '', '', '', '', '','Họ tên', 'Email', 'Số điện thoại',	'Tỉnh/Thành phố',
                    'Quận/Huyện', 'Phường xã', 'Địa chỉ','Họ tên', 'Email', 'Số điện thoại',	'Tỉnh/Thành phố', 'Quận/Huyện', 'Phường xã','Địa chỉ',
                    'Tên sản phẩm', 'Tổng giá trị', 'Khối lượng', 'Phí vận chuyển',	'Phí thu hộ', 'Phí bảo hiểm', 'Phí vượt cân',
                    'Phí chuyển hoàn'
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
                $sheet->setBorder('A3:AH4', 'thin');

                $i = 1;
                foreach ($ListReturn as $val) {
                    $dataExport = array(
                        $i++,
                        $val['time_accept'] > 0 ? date("d/m/y H:m",$val['time_accept']) : '',
                        $val['time_pickup'] > 0 ? date("d/m/y H:m",$val['time_pickup']) : '',
                        $val['time_success'] > 0 ? date("d/m/y H:m",$val['time_success']) : '',
                        $val['verify_id'],
                        $val['tracking_code'],
                        isset($Courier[(int)$val['courier_id']]) ? $Courier[(int)$val['courier_id']] : 'HVC',
                        $val['courier_tracking_code'],
                        isset($Service[(int)$val['service_id']]) ? $Service[(int)$val['service_id']] : 'DV',
                        isset($Status[(int)$val['status']]) ? $Status[(int)$val['status']] : 'Trạng thái',

                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['fullname'] : '',
                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['email'] : '',
                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['phone'] : '',
                        isset($City[(int)$val['from_city_id']]) ? $City[(int)$val['from_city_id']] : '',
                        isset($District[(int)$val['from_district_id']]) ? $District[(int)$val['from_district_id']] : '',
                        isset($Ward[(int)$val['from_ward_id']]) ? $Ward[(int)$val['from_ward_id']] : '',
                        $val['from_address'],

                        $val['to_name'],
                        $val['to_email'],
                        $val['to_phone'],
                        (isset($Address[(int)$val['to_address_id']]) && isset($City[$Address[(int)$val['to_address_id']]['city_id']])) ? $City[$Address[(int)$val['to_address_id']]['city_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($District[$Address[(int)$val['to_address_id']]['province_id']])) ? $District[$Address[(int)$val['to_address_id']]['province_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($Ward[$Address[(int)$val['to_address_id']]['ward_id']])) ? $Ward[$Address[(int)$val['to_address_id']]['ward_id']] : '',
                        isset($Address[(int)$val['to_address_id']]) ? $Address[(int)$val['to_address_id']]['address'] : '',

                        $val['product_name'],
                        $val['total_amount'],
                        $val['total_weight'],

                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvc']) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['sc_cod']) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['sc_pbh']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvk']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pch']) : '',
                        isset($val['order_detail']) ? number_format(($val['order_detail']['sc_discount_pvc'] + (($val['status'] != 66) ? $val['order_detail']['sc_discount_cod'] : 0))) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['money_collect']) : ''
                    );
                    $sheet->appendRow($dataExport);
                }
            });

            /**
             *  Đơn tồn
             */
            $excel->sheet('Tháng trước', function($sheet) use($ListLast, $Courier, $Service, $City, $Address, $District, $Ward, $User, $Status){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách vận đơn tháng trước thành công trong kỳ'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,
                    'H'     =>  30, 'I'     =>  30, 'J'     =>  30, 'K'     =>  30, 'L'     =>  30, 'M'     =>  30, 'N'     =>  30,
                    'O'     =>  30, 'P'     =>  30, 'Q'     =>  30, 'R'     =>  30, 'S'     =>  30, 'T'     =>  30, 'U'     =>  30,
                    'V'     =>  30, 'W'     =>  30, 'X'     =>  30, 'Y'     =>  30, 'Z'     =>  30, 'AA'     =>  30, 'AB'     =>  30,
                    'AC'     =>  30, 'AD'     =>  30, 'AE'     =>  30, 'AF'  => 30, 'AG'    => 30,   'AH'    =>  30
                ));
                $sheet->setMergeColumn(array(
                    'columns' => array('A','B','C','D','E','F','G','H','I','J','AG','AH'),
                    'rows' => array(
                        array(3,4)
                    )
                ));
                $sheet->mergeCells('K3:Q3');
                $sheet->mergeCells('R3:X3');
                $sheet->mergeCells('Y3:AA3');
                $sheet->mergeCells('AB3:AF3');

                $sheet->row(3, array(
                    'STT', 'Thời gian duyệt', 'Thời gian lấy hàng', 'Thời gian giao hàng', 'Bản kê đối soát', 'Mã vận đơn',
                    'Hãng vận chuyển', 'Mã hãng vận chuyển', 'Dịch vụ', 'Trạng thái','Nơi gửi','','','','','', '', 'Nơi nhận', '', '', '', '', '', '',
                    'Thông tin sản phẩm', '', '',
                    'Thông tin phí', '', '', '', '', 'Giảm giá', 'Tổng tiền thu hộ'
                ));

                $sheet->row(4, array(
                    '', '', '', '', '', '', '', '', '', '','Họ tên', 'Email', 'Số điện thoại',	'Tỉnh/Thành phố',
                    'Quận/Huyện', 'Phường xã', 'Địa chỉ','Họ tên', 'Email', 'Số điện thoại',	'Tỉnh/Thành phố', 'Quận/Huyện', 'Phường xã','Địa chỉ',
                    'Tên sản phẩm', 'Tổng giá trị', 'Khối lượng', 'Phí vận chuyển',	'Phí thu hộ', 'Phí bảo hiểm', 'Phí vượt cân',
                    'Phí chuyển hoàn'
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
                $sheet->setBorder('A3:AH4', 'thin');

                $i = 1;
                foreach ($ListLast as $val) {
                    $dataExport = array(
                        $i++,
                        $val['time_accept'] > 0 ? date("d/m/y H:m",$val['time_accept']) : '',
                        $val['time_pickup'] > 0 ? date("d/m/y H:m",$val['time_pickup']) : '',
                        $val['time_success'] > 0 ? date("d/m/y H:m",$val['time_success']) : '',
                        $val['verify_id'],
                        $val['tracking_code'],
                        isset($Courier[(int)$val['courier_id']]) ? $Courier[(int)$val['courier_id']] : 'HVC',
                        $val['courier_tracking_code'],
                        isset($Service[(int)$val['service_id']]) ? $Service[(int)$val['service_id']] : 'DV',
                        isset($Status[(int)$val['status']]) ? $Status[(int)$val['status']] : 'Trạng thái',

                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['fullname'] : '',
                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['email'] : '',
                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['phone'] : '',
                        isset($City[(int)$val['from_city_id']]) ? $City[(int)$val['from_city_id']] : '',
                        isset($District[(int)$val['from_district_id']]) ? $District[(int)$val['from_district_id']] : '',
                        isset($Ward[(int)$val['from_ward_id']]) ? $Ward[(int)$val['from_ward_id']] : '',
                        $val['from_address'],

                        $val['to_name'],
                        $val['to_email'],
                        $val['to_phone'],
                        (isset($Address[(int)$val['to_address_id']]) && isset($City[$Address[(int)$val['to_address_id']]['city_id']])) ? $City[$Address[(int)$val['to_address_id']]['city_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($District[$Address[(int)$val['to_address_id']]['province_id']])) ? $District[$Address[(int)$val['to_address_id']]['province_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($Ward[$Address[(int)$val['to_address_id']]['ward_id']])) ? $Ward[$Address[(int)$val['to_address_id']]['ward_id']] : '',
                        isset($Address[(int)$val['to_address_id']]) ? $Address[(int)$val['to_address_id']]['address'] : '',

                        $val['product_name'],
                        $val['total_amount'],
                        $val['total_weight'],

                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvc']) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['sc_cod']) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['sc_pbh']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvk']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pch']) : '',
                        isset($val['order_detail']) ? number_format(($val['order_detail']['sc_discount_pvc'] + (($val['status'] != 66) ? $val['order_detail']['sc_discount_cod'] : 0))) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['money_collect']) : ''
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }
}
