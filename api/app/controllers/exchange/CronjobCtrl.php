<?php
namespace exchange;

use Response;
use Exception;
use Input;
use Validator;
use Excel;
use DB;
use LMongo;
use Smslib;

use omsmodel\ExchangeModel;
use ordermodel\OrdersModel;
use systemmodel\SendSmsMongo;

class CronjobCtrl extends \BaseController
{
    function __construct(){

    }

    private function SendSms($json = false){
        $Phone          = Input::has('phone')           ?   strtoupper(trim(Input::get('phone')))   : '';
        $Content        = Input::has('content')         ?   trim(Input::get('content'))             : '';

        $SendSmsMongo   =  new SendSmsMongo;
        if(empty($Phone) || empty($Content)){
            $Response = ['error' => true, 'message' => 'DATA_EMPTY', 'error_message' => 'Dữ liệu thiếu !'];
        }else{
            try{
                $SendSmsMongo->insert(array(
                    'telco'         => Smslib::CheckPhone($Phone),
                    'to_phone'      => $Phone,
                    'status'        => 0,
                    'content'       => $Content,
                    'time_create'   => $this->time(),
                    'time_send'   => 0,
                ));
                $Response = ['error' => false, 'message' => 'SUCCESS', 'error_message' => 'Thành công'];
            }catch (Exception $e){
                $Response = ['error' => true, 'message' => 'INSERT_FAIL', 'error_message' => 'Thất bại'];
            }
        }

        return $json ? Response::json($Response) : $Response;
    }

    public function getNotification(){
        $ExchangeModel  = new ExchangeModel;
        $OrdersModel    = new OrdersModel;

        $Exchange       = $ExchangeModel::whereIn('active',[1,2])->where('notification',0)->first();
        if(!isset($Exchange->id)){
            return Response::json(['error' => false, 'message' => 'EMPTY', 'error_message' => 'Đã xử lý hết']);
        }

        $toPhone = str_replace(array(';','.',' ','/','|'), ',', $Exchange->to_phone);

        $arrPhone = array();
        if($toPhone != ''){
            $arrPhone = explode(',', $toPhone);
        }

        Input::Merge([
            'to_phone'   => $arrPhone[0],
            'content'    => 'Don hang '.$Exchange->tracking_code.' da duoc thuc hien'
        ]);

        $SendSms    = $this->SendSms(false);
        if($SendSms['error']){
            return Response::json(['error' => true, 'message' => 'SEND_SMS_FAIL', 'error_message' => 'Gửi sms thất bại!']);
        }

        $Exchange->notification = 1;

        try{
            $Exchange->save();
            return Response::json(['error' => false, 'message' => 'SUCCESS', 'error_message' => 'Thành công']);
        }catch (Exception $e){
            return Response::json(['error' => true, 'message' => 'UPDATE_FAIL', 'error_message' => 'Cập nhật lỗi !']);
        }
    }
}
