<?php
namespace exchange;

use Response;
use Exception;
use Input;
use Validator;
use Excel;
use DB;
use LMongo;

use omsmodel\ExchangeModel;
use ordermodel\OrdersModel;
use systemmodel\SendSmsMongo;
use sellermodel\UserInventoryModel;
use User;

class ExchangeCtrl extends \BaseController
{
    private $error          = false;
    private $code           = 'SUCCESS';
    private $message        = 'Thành Công';
    private $id             = 0;

    private $OrderId                = 0;
    private $TrackingCode           = '';
    private $Action                 = '';
    private $FromPhone              = '';
    private $ToPhone                = '';
    private $Note                   = '';
    private $Order;


    function __construct(){
        $this->OrderId              = Input::has('order_id')        ? (int)Input::get('order_id')               : 0;
        $this->TrackingCode         = Input::has('tracking_code')   ? (int)Input::get('tracking_code')          : 0;
        $this->Action               = Input::has('action')          ? trim(strtolower(Input::get('action')))    : '';
        $this->Note                 = Input::has('note')            ? trim(Input::get('note'))                  : '';
    }

    private function _validation(){
        $Data       = [
            'order_id'                  => $this->OrderId,
            'tracking_code'             => $this->TrackingCode,
            'action'                    => $this->Action,
            'note'                      => $this->Note
        ];

        $dataInput = [
            'order_id'          => 'required|numeric|min:1',
            'tracking_code'     => 'sometimes|required',
            'action'            => 'required|in:return,exchange',
            'note'              => 'required'
            ];

        $this->validation = Validator::make($Data, $dataInput);
    }

    private function getOrder(){
        $OrdersModel    = new OrdersModel;
        $this->Order    = $OrdersModel::where('time_accept','>=', $this->time_limit);

        if($this->OrderId > 0){
            $this->Order    = $this->Order->where('id', $this->OrderId);
        }elseif(!empty($this->TrackingCode)){
            $this->Order    = $this->Order->where('tracking_code', $this->TrackingCode);
        }

        $this->Order    = $this->Order->first(['id','tracking_code','status','from_address_id', 'from_user_id', 'to_phone', 'to_email']);
    }

    private function getFromPhone(){
        if($this->Order->from_address_id > 0){
            $UserInventoryModel = new UserInventoryModel;
            $Inventory          = $UserInventoryModel->get_by_id($this->Order->from_address_id,'','');
        }

        if(isset($Inventory->id) && !empty($Inventory->phone)){
            $this->FromPhone    = $Inventory->phone;
        }else{
            $User = new User;
            $User = $User->get_by_id($this->Order->from_user_id);
            if(isset($User->id)){
                $this->FromPhone  = $User->phone;
            }
        }
        return;
    }

    public function postCreate(){

        $this->_validation();
        if($this->validation->fails()) {
            $this->code         = 'INVALID';
            $this->message      = $this->validation->messages();
            return $this->ResponseData(true);
        }

        //get order
        $this->getOrder();
        if(!isset($this->Order->id)){
            $this->code         = 'EMPTY';
            $this->message      = 'Vận đơn không tồn tại';
            return $this->ResponseData(true);
        }

        if(!in_array($this->Order->status, [52,53])){
            $this->code         = 'STATUS_ERROR';
            $this->message      = 'Vận đơn chưa giao thành công, quý khách không thể yêu cầu đổi trả !';
            return $this->ResponseData(true);
        }

        //get detail
        $this->ToPhone          = $this->Order->to_phone;
        $this->TrackingCode     = $this->Order->tracking_code;

        //get from phone
        $this->getFromPhone();
        if(empty($this->FromPhone)){
            $this->code         = 'PHONE_ERROR';
            $this->message      = 'Lỗi kết nỗi dữ liệu, hãy thử lại !';
            return $this->ResponseData(true);
        }

        $CountModel     = new ExchangeModel;
        if($CountModel::where('order_id',$this->OrderId)->where('active', 0)->count() > 0){
            $this->code         = 'REQUEST_EXISTS';
            $this->message      = 'Bạn đã tạo yêu cầu đổi trả, vui lòng kiểm tra lại !';
            return $this->ResponseData(true);
        }

        $ExchangeModel  = new ExchangeModel;
        try{
            $this->id = $ExchangeModel->insertGetId([
                'order_id'          => $this->OrderId,
                'tracking_code'     => $this->TrackingCode,
                'from_user_id'      => $this->Order->from_user_id,
                'from_phone'        => $this->FromPhone,
                'to_phone'          => $this->ToPhone,
                'note'              => $this->Note,
                'time_create'       => $this->time(),
                'time_update'       => $this->time(),
                'type'              => $this->Action == 'return' ? 1 : 2
            ]);
        }catch (Exception $e){
            $this->code         = 'REQUEST_EXISTS';
            $this->message      = $e->getMessage();
            return $this->ResponseData(true);
        }

        return $this->ResponseData(false);
    }

    private function ResponseData($error){
        return Response::json([
            'error'         => $error,
            'code'          => $this->code,
            'message'       => $this->message,
            'id'            => $this->id
        ]);
    }
}
