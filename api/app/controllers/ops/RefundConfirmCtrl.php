<?php
namespace ops;
use ordermodel\OrdersModel;
use OrderDetailModel;
use omsmodel\RefundConfirmModel;
use omsmodel\RefundConfirmItemModel;
use ticketmodel\FeedbackModel;
use ElasticBuilder;
use Validator;
use User;
use Excel;
use ticketmodel\RequestModel;
use CourierModel;
class RefundConfirmCtrl extends BaseCtrl
{
    public function __construct()
    {

    }

    const MP_VANCHUYEN  = 1;
    const MP_CHUYENHOAN = 2;
    const MP_VUOTCAN    = 3;
    const MP_50PVC      = 4;



    private function checkExists($TicketId){
        return RefundConfirmModel::where('ticket_id', $TicketId)->first();
    }

    

    private function getOrdersDetail($list_tracking){
        if (empty($list_tracking)) {
            return false;
        }        
        $Data =  OrdersModel::whereIn('tracking_code', $list_tracking)
                            ->where('time_accept','>=', $this->time() - 86400*60)
                            ->with(['OrderDetail'])
                            ->select(['id', 'tracking_code'])
                            ->get()
                            ->toArray();
        $Result  =  [];

        foreach ($Data as $key => $value) {
            $Result[$value['tracking_code']] = $value;
        }
        return $Result;
    }

    private function calculateFeeRefund($ListOrder){
        $TotalAmount = 0;

        $list_tracking_code = [];

        foreach ($ListOrder as $key => $value) {
            if (isset($value['tracking_code'])) {
                $list_tracking_code[]  = $value['tracking_code'];
            }
        }

        
        $OrderDetail = $this->getOrdersDetail($list_tracking_code);

        if (!$OrderDetail) {
            $this->_error_message = Lang::get('response.NOT_EXISTS_ORDER');

            return false;
        }

        foreach ($ListOrder as $key => $value) {
            if (isset($OrderDetail[$value['tracking_code']])) {

                $Amount = 0;
                $Detail = $OrderDetail[$value['tracking_code']]['order_detail'];

                switch ($value['refund_type']) {
                    case self::MP_VANCHUYEN:
                        $Amount = $Detail['sc_pch'] + $Detail['sc_pvk'] + $Detail['sc_pvc'] - $Detail['sc_discount_pvc'];
                        break;
                    case self::MP_CHUYENHOAN:
                        $Amount = $Detail['sc_pch'];
                        break;
                    case self::MP_VUOTCAN:
                        $Amount = $Detail['sc_pvk'];
                        break;
                    case self::MP_50PVC:
                        $Amount = $Detail['sc_pvc']/2;
                        break;
                    default:
                        $this->_error_message = Lang::get('response.NOT_SELECT_TYPE_REFUND');
                        return false;
                        break;
                }
            }

            $TotalAmount                += (int)$Amount;
            $ListOrder[$key]['amount']  = (int)$Amount;
        }

        return ['data'=> $ListOrder, 'total_amount'=> $TotalAmount];
    }

    public function getIndex(){
        $ItemPage           = Input::has('limit')         ? Input::get('limit')               : 20;
        $Page               = Input::has('page')          ? (int)Input::get('page')           : 1;
        $TicketId           = Input::has('ticket_id')     ? (int)Input::get('ticket_id')      : '';
        $Email              = Input::has('email')         ? Input::get('email')               : '';
        $Status             = Input::has('status')        ? Input::get('status')              : '';
        $TimeStart          = Input::has('time_start')    ? (int)Input::get('time_start')     : 0;
        $CountryId          = Input::has('from_country_id')     ? (int)Input::get('from_country_id')            : 237;
        $Output     = [];

        $Refund = new RefundConfirmModel;
        $Refund = $Refund::where('country_id', $CountryId);

        if($TicketId != ''){
            $Refund = $Refund->where('ticket_id',$TicketId);
        }
        if($Email != ''){
            $Email = trim($Email);
            $InfoUser = User::where('email',$Email)->first();
            if(!empty($InfoUser)){
                $Refund = $Refund->where('seller_id',$InfoUser['id']);
            }
        }
        if($Status != 'none'){
            $Status = strtoupper($Status);
            $Refund = $Refund->where('status',$Status);
        }
        if($TimeStart > 0){
            $Refund = $Refund->where('time_create','>',$TimeStart);
        }

        $RefundTotal = clone $Refund;
        $Total = $RefundTotal->count();
        if($Total > 0){
            $Offset     = ($Page - 1)*$ItemPage;
            $ListData   = $Refund->skip($Offset)->take($ItemPage)->orderBy('time_create','desc')->get()->toArray();
            if(!empty($ListData)){
                foreach($ListData AS $One){
                    $ListUserId[] = $One['seller_id'];
                }
                $ListUserInfo = User::whereIn('id',$ListUserId)->get(array('id','email'))->toArray();
                foreach($ListData AS $Data){
                    foreach($ListUserInfo AS $User){
                        if($Data['seller_id'] == $User['id']){
                            $Data['email'] = $User['email'];
                        }
                    }
                    $Output[] = $Data;
                }
            }
        }

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $Output,
            'total'     =>  $Total
        );
        return Response::json($contents);
    }

    public function postCreate(){
        $TicketId  = Input::has('ticket_id') ? (int)Input::get('ticket_id') : 0;
        $SellerId  = Input::has('seller_id') ? (int)Input::get('seller_id') : 0;
        $Note      = Input::has('note')         ? Input::get('note')        : "";
        $ListOrder = Input::has('list_order')   ? Input::get('list_order')  : [];

        $UserInfo   = $this->UserInfo();
        

        $validation = Validator::make(Input::all(), [
            'ticket_id'     => 'required|numeric|min:1',
            'note'          => 'required'
        ]);


        if ($validation->fails()) {
            $this->_error = true;
            $this->_error_message = $validation->messages();
            return $this->_ResponseData();
        }

        if (empty($ListOrder)) {
            $this->_error = true;
            $this->_error_message = Lang::get('response.NOT_EXISTS_ORDER');
            return $this->_ResponseData();
        }

        if (empty($SellerId)) {
            $this->_error         = true;
            $this->_error_message = Lang::get('response.NOT_ID_CUSTOMER');
            return $this->_ResponseData();   
        }


        $isExists = $this->checkExists($TicketId);

        if ($isExists) {
            $this->_error         = true;
            $this->_error_message = Lang::get('response.SENT_REQUEST');
            return $this->_ResponseData();
        }

        $Orders = $this->calculateFeeRefund($ListOrder);

        if (!$Orders) {
            $this->_error         = true;
            //$this->_error_message = "Có lỗi xảy ra trong quá trình xử lý, vui lòng thử lại.";
            return $this->_ResponseData();
        }        

        


        $Refund = new RefundConfirmModel;
        $Refund->ticket_id   = $TicketId;
        $Refund->amount      = $Orders['total_amount'];
        $Refund->note        = $Note;
        $Refund->seller_id   = $SellerId;
        $Refund->user_id     = (int)$UserInfo['id'];
        $Refund->time_create = $this->time();


        try {
            $Refund->save();
        } catch (Exception $e) {
            $this->_error = true;
            $this->_error_message = Lang::get('response.FAIL_QUERY');
            return $this->_ResponseData();
        }

        $RefundItemSave = array_map(function ($value)use ($Refund){
            return [
                'refund_id'     => $Refund->id,
                'amount'        => $value['amount'],
                'refund_type'   => $value['refund_type'],
                'tracking_code' => $value['tracking_code'],
                'courier_id'    => $value['courier_id'],
                'city_id'       => $value['from_city_id'],
                'time_create'   => $this->time()
            ];
        }, $Orders['data']);
        

        try {
            RefundConfirmItemModel::insert($RefundItemSave);
        } catch (Exception $e) {
            $this->_error = true;
            $this->_error_message = Lang::get('response.FAIL_QUERY');
            return $this->_ResponseData();
        }

        return $this->_ResponseData($Refund);
    }

    public function postEdit(){
        $Id             = Input::has('id')            ? (int)Input::get('id')      : 0;
        $Type           = Input::has('type')          ? (int)Input::get('type')      : 0;
        $RejectNote     = Input::has('rnote')         ? Input::get('rnote')          : "";
        $TicketId       = Input::has('ticket_id')     ? Input::get('ticket_id')          : 0;
        $Refund         = new RefundConfirmModel;

        if($Type == 1){
            //duyet boi hoan
            $Update = $Refund->where('id',$Id)->update(array('status' => 'CONFIRMED','time_confirm' => $this->time()));
            if($Update){
                //day vao feedback ticket
                $feedback = FeedbackModel::insert(array('ticket_id' => $TicketId,'user_id' => 2,'source' => 'web','content' => 'Shipchung đồng ý bồi hoàn cho khách hàng.','time_create' => $this->time(),'notification' => 1));
                $updateTicket = RequestModel::where('id',$TicketId)->update(array('status' => 'CLOSED'));
                $this->_error = false;
                $this->_error_message = Lang::get('response.SUCCESS');
                return $this->_ResponseData();
            }else{
                $this->_error = true;
                $this->_error_message = Lang::get('response.FAIL_QUERY');
                return $this->_ResponseData();
            }
        }
        if($Type == 2){
            //huy boi hoan
            if($RejectNote == ''){
                $contents = array(
                    'error'     => true,
                    'message'   => Lang::get('response.PLEASE_TYPING_NOTE_NOT_ACCEPT'),
                    'data'      => '',
                );
                return Response::json($contents);
            }
            $Reject = $Refund->where('id',$Id)->update(array('status' => 'REJECT','reject_note' => $RejectNote,'time_confirm' => $this->time()));
            if($Reject){
                //day vao feedback ticket
                $feedback = FeedbackModel::insert(array('ticket_id' => $TicketId,'user_id' => 2,'source' => 'note','content' => $RejectNote,'time_create' => $this->time(),'notification' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => Lang::get('response.SUCCESS'),
                    'data'      => '',
                );
                return Response::json($contents);
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => Lang::get('response.FAIL_QUERY'),
                    'data'      => '',
                );
                return Response::json($contents);
            }
        }
    }

    public function getExport(){
        $TicketId   = Input::has('ticket_id')     ? (int)Input::get('ticket_id')      : '';
        $Email      = Input::has('email')         ? Input::get('email')               : '';
        $Status     = Input::has('status')        ? Input::get('status')              : '';
        $Status     = strtoupper($Status);
        $TimeStart  = Input::has('time_start')    ? (int)Input::get('time_start')     : 0;

        $Refund = new RefundConfirmModel;
        if($TicketId != ''){
            $Refund = $Refund->where('ticket_id',$TicketId);
        }
        if($Email != ''){
            $InfoUser = User::where('email',$Email)->first();
            if(!empty($InfoUser)){
                $Refund = $Refund->where('seller_id',$InfoUser['id']);
            }
        }
        if($Status != '' && $Status != 'NONE'){
            $Refund = $Refund->where('status',$Status);
        }
        if($TimeStart > 0){
            $Refund = $Refund->where('time_create','>',$TimeStart);
        }

        $Output = $Response = $ListTrackingCode = $ReturnData = $Return = array();
        $ListData   = $Refund->get()->toArray();
        if(!empty($ListData)){
            foreach($ListData AS $One){
                $ListUserId[] = $One['seller_id'];
                $ListRefundId[] = $One['id']; 
            }
            //
            if (Cache::has('courier_cache')){
                $ListCourier    = Cache::get('courier_cache');
            }else{
                $Courier        = new CourierModel;
                $ListCourier    = $Courier::all(array('id','name'));
            }

            //lay sccode
            $ListOrder = RefundConfirmItemModel::whereIn('refund_id',$ListRefundId)->get(array('id','refund_id','tracking_code','courier_id','city_id'))->toArray();

            if(!empty($ListOrder)){
                foreach($ListOrder AS $Val){
                    $ListTrackingCode[$Val['refund_id']][] = $Val['tracking_code'];
                    $CourierL[$Val['refund_id']] = $Val['courier_id'];
                    $City[$Val['refund_id']] = $Val['city_id'];
                }
                foreach($ListData AS $One){
                    foreach($ListTrackingCode AS $Key => $Value){
                        if($Key == $One['id']){
                            if(count($Value) > 1){
                                $One['tracking_code'] = implode(',', $Value);
                            }else{
                                $One['tracking_code'] = $Value[0];
                            }
                        }
                    }
                    $Response[] = $One;
                }
                if(!empty($ListCourier)){
                    foreach($ListCourier AS $val){
                        $LCourier[$val['id']]   = $val['name'];
                    }
                }
                
                foreach($Response AS $One){
                    foreach($CourierL AS $Key => $Value){
                        if($Key == $One['id']){
                            $One['courier_id'] = (int)$Value;
                        }
                    }
                    $ReturnData[] = $One;
                }
                foreach($ReturnData AS $One){
                    foreach($City AS $Key => $Value){
                        if($Key == $One['id']){
                            $One['city_id'] = (int)$Value;
                        }
                    }
                    $Return[] = $One;
                }
            }

            $ListUserInfo = User::whereIn('id',$ListUserId)->get(array('id','email'))->toArray();
            foreach($Return AS $Data){
                foreach($ListUserInfo AS $User){
                    if($Data['seller_id'] == $User['id']){
                        $Data['email'] = $User['email'];
                    }
                }
                $Output[] = $Data;
            }

            if(!empty($Output)){
                return Excel::create('Danh_sach_boi_hoan_'.date("d/m/y",$this->time()), function ($excel) use($Output,$LCourier) {
                    $excel->sheet('Danh sách', function ($sheet) use($Output,$LCourier) {
                        // set width column
                        $sheet->setWidth(array(
                            'A'     => 5,
                            'B'     => 20,
                            'C'     => 45,
                            'D'     => 15,
                            'E'     => 40,
                            'F'     => 40,
                            'G'     => 40
                        ));
                        // set content row
                        $sheet->row(1, array(
                             'STT',
                             'Tài khoản nhận',
                             'Mã đơn',
                             'Hãng vận chuyển',
                             'Mã giao dịch',
                             'Số tiền',
                             'Lý do'
                        ));
                        $sheet->row(1,function($row){
                            $row->setBackground('#B6B8BA');
                            $row->setBorder('solid','solid','solid','solid');
                            $row->setFontSize(12);
                        });
                        //
                        $i = 1;
                        foreach ($Output AS $value) {
                            if($value['city_id'] == 18 && $value['courier_id'] == 1){
                                $courier = 'Viettelpost HN';
                            }elseif($value['city_id'] == 52 && $value['courier_id'] == 1){
                                $courier = 'Viettelpost HCM';
                            }else{
                                $courier = $LCourier[(int)$value['courier_id']];
                            }
                            
                            $dataExport = array(
                                'STT' => $i++,
                                'Tài khoản nhận' => $value['email'],
                                'Mã đơn' => $value['tracking_code'],
                                'Hãng vận chuyển' => $courier,
                                'Mã giao dịch'  => '',
                                'Số tiền' => $value['amount'],
                                'Lý do' => $value['note']
                            );
                            $sheet->appendRow($dataExport);
                        }
                    });
                })->export('xls');
            }else{
                return false;
            }
        }
    }
}
?>