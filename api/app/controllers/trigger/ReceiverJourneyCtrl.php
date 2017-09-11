<?php
namespace trigger;
use Input;
use LMongo;
use Response;
use DB;
use Cache;
use Config;
use Validator;
use ordermodel\OrdersModel;
class ReceiverJourneyCtrl extends \BaseController {
    
    private $validation, $__Lading, $__Address, $TrackingOrder, $Note, $_HVCPASS, $Status, $checksum;

    private $_parse_data_couriers = [
        'ttc'
    ];


    private function __get_checksum(){
        $headers = getallheaders();

        if(!empty($this->checksum)){
            return $this->checksum;
        }

        if(isset($headers['SHIPCHUNG-CHECKSUM-DATA']) && !empty($headers['SHIPCHUNG-CHECKSUM-DATA'])){
            return $headers['SHIPCHUNG-CHECKSUM-DATA'];
        }

        return "";
    }

    private function __get_checksum_ninjav(){
        return !empty($_SERVER['HTTP_X_NINJAVAN_HMAC_SHA256']) ? $_SERVER['HTTP_X_NINJAVAN_HMAC_SHA256'] : "1234";
    }


    private function __validate_checksum(){
        $checksum           = $this->__get_checksum();
        $checksum_calculate = base64_encode(md5($this->TrackingOrder.'_'.$this->Status.'_'.$this->_HVCPASS));
        if($checksum == $checksum_calculate){
            return true;
        }
        $this->_error           = true;
        $this->_error_message   = "Mã checksum không đúng ". $checksum  ;
        
        return false;
    }
    

    function __construct(){
        Input::merge(Input::json()->all());

        $courier = $this->__get_carrier();

        // Xử lý dữ liệu đầu vào 
        if(in_array($courier, $this->_parse_data_couriers)){
             $func = '__parse_data_'.$courier;
             $func();
        }
        
        if(Input::has('params')){            
            $merge = array(
                'TrackingOrder' => (!preg_match("/sc/i", Input::get('params.SC_CODE')) ? 'SC' : '') . Input::get('params.SC_CODE'),
                'TrackingCode'  => Input::has('params.HVC_CODE') ? Input::get('params.HVC_CODE') : ( (!preg_match("/sc/i", Input::get('params.SC_CODE')) ? 'SC' : '') . Input::get('params.SC_CODE') ),
                'Status'        => Input::get('params.STATUS'),
                'Note'          => Input::get('params.NOTE'),
                'City'          => Input::get('params.CITY'),
            );
            

            if(Input::has('params.WEIGHT')){
                $merge['Weight']    = Input::get('params.WEIGHT');
            }

            if(Input::has('params.Weight')){
                $merge['Weight']    = Input::get('params.Weight');
            }

            if(Input::has('params.COLLECT')){
                $merge['Collect']    = Input::get('params.COLLECT');
            }

            if(Input::has('params.Collect')){
                $merge['Collect']    = Input::get('params.Collect');
            }

            Input::merge($merge);

            
            
        }
    }

    private function __get_carrier(){
        return Input::has('username') ? Input::get('username') : Input::get('carrier'); 
    }
    

    private function  __validate_client_ip(){
        $ip_carriers    = Config::get('config_api.cfg_carrier_ip');
        $carrier        = $this->__get_carrier();

        if(!isset($ip_carriers[$carrier])){
            $this->_error           = true;
            $this->_error_message   = "Your IP address : ".$this->__get_client_ip(). ' not accepted !';
            return false;
        }

        if(!empty($ip_carriers[$carrier]) && !in_array($this->__get_client_ip(), $ip_carriers[$carrier])){
            $this->_error = true;
            $this->_error_message = "Your IP address : ".$this->__get_client_ip(). ' not allowed !';
            return false;
        }
    }



    private function __get_client_ip (){
        $ipaddress = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(!empty($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(!empty($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(!empty($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(!empty($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';

        return $ipaddress;
    }

    private function _check_login_v1(){
        $arrAcc = Config::get('config_api.cfg_carrier_api');

        if(isset($arrAcc[Input::get('username')]) && $arrAcc[Input::get('username')] == Input::get('password'))
        {
            
        }else {
            $this->_error_message   = "Bạn không có quyền truy cập đường dẫn này, vui lòng liên hệ kỹ thuật để được hỗ trợ";
            $this->_error           = true;
        }
        return;
    }

    
	public function getIndex()
	{
        return Response::json([
            'ip_address'=> $this->__get_client_ip()
        ], 200);
	}

    private function _validation($params = false){
        $dataInput = array(
            //'Courier'           => 'required',
            'Status'            => 'required',
            'Note'              => 'required',
            'TrackingCode'      => 'required', // Mã HVC
            'TrackingOrder'     => 'required', // Mã SC
            'Weight'            => 'sometimes|required|numeric|min:0',
            'Collect'           => 'sometimes|required|numeric|min:0'
        );
        if($params){
            $dataInput += array(
                'City'              => 'required',
                'Province'          => 'required',
                'PostOffice'        => 'required',
            );
        }
        
        $this->validation = Validator::make(Input::all(), $dataInput);
    }


    private function _checkTrackingCode(){
        $this->__Lading = \ordermodel\OrdersModel::where('tracking_code',Input::get('TrackingOrder'))
                            ->where('time_accept','>=',$this->time() - $this->time_limit)
                            ->first(['tracking_code','courier_id','status','to_address_id','domain']);
    }

    private function __parse_data_ttc (){
        Input::merge([
            'params'    => [
                'NOTE'      =>  urldecode(Input::get('params.NOTE')),
                'CITY'      =>  urldecode(Input::get('params.CITY')),
                'SC_CODE'   => Input::get('params.SC_CODE'),
                'STATUS'    => Input::get('params.STATUS'),
                'WEIGHT'    => Input::get('params.WEIGHT'),
                'COLLECT'   => Input::get('params.COLLECT'),
            ]
        ]);
    }


    
    public function postIndex()
	{   
        $this->_validation();

        // Check và báo invalid
        if($this->validation->fails()) {
            return Response::json(array('error' => 301, 'error_message' => $this->validation->messages(), 'input'=> Input::all()));
        }

        $this->TrackingOrder = Input::get('TrackingOrder');
        $this->Status        = Input::get('Status');
        
        $this->_HVCPASS      = Input::get('password');
        
        // Check Login
        $this->_check_login_v1();
        
        if($this->_error){
            return $this->_ResponseData();
        }

        $this->__validate_client_ip();
        
        if($this->_error){
            return $this->_ResponseData();
        }

        $this->__validate_checksum();
        
        if($this->_error){
            return $this->_ResponseData();
        }



        
        $LMongo = new LMongo;
        
        $idLog  = $LMongo::collection('log_journey_lading')
                                    ->insert(array(
                                                'tracking_code'     => Input::get('TrackingOrder'),
                                                'tracking_number'   => (int)substr(Input::get('TrackingOrder'),2),
                                                'input'         => Input::all(),
                                                "priority"      => 2,
                                                'accept'        => 0,
                                                'time_create'   => $this->time(),
                                            ));
        
        // Check Exist Lading
        $this->_checkTrackingCode();

        if(!$this->__Lading){
            $this->_error           = true;
            $this->_error_message   = "Không tìm thấy vận đơn ". Input::get('TrackingOrder');
            return $this->_ResponseData();
        }
        

        //
        if(is_numeric(Input::get('City')))
            Input::merge(array('City' => (int)Input::get('City')));

        if(is_numeric(Input::get('Province')))
            Input::merge(array('Province' => (int)Input::get('Province')));

        if(Input::get('TrackingCode')){
            Input::merge(array('courier_tracking_code' => Input::get('TrackingCode')));
        }

        
        
        if((int)$this->__Address['city_id'] > 0){
            Input::merge(array('City' => (int)$this->__Address['city_id']));
        }
        if((int)$this->__Address['province_id'] > 0){
            Input::merge(array('Province' => (int)$this->__Address['province_id']));
        }

        if($idLog){
            $LMongo::collection('log_journey_lading')->where('_id', new \MongoId($idLog))->update(array(
                'courier'       => (int)$this->__Lading['courier_id'],
                'domain'        => $this->__Lading['domain'],
                'status'        => Input::get('Status'),
                'address'       => ['city' => Input::get('City'), 'province' => Input::get('Province')],                                                
                'note'          => Input::get('Note'),
                'weight'        => Input::has('Weight') ? (int)Input::get('Weight') : 0,
                'collect'       => Input::has('Collect') ? (int)Input::get('Collect') : 0,
                'time_update'   => $this->time(),
            ));

           // Call Predis
           try{
               $this->RabbitJourney($idLog);
           } catch(\Exception $e){

           }


            return Response::json(array(
                'error'         => 200, 
                'error_message' => 'success',
                'data'          => array(
                                    'LadingCode'    => Input::get('TrackingOrder'),
                                    'Status'        => Input::get('Status'),
                                )
                ));
        }
        else{
            return Response::json(array(
                'error'         => 301, 
                'error_message' => 'fail',
                'data'          => array(
                                    'LadingCode'    => Input::get('TrackingOrder'),
                                    'Status'        => Input::get('Status'),
                                )
                ));
        }
    }
    
	
    //xu ly Ninjavan
    public function postNinjavan($id){
        
        if((int)$id < 0){
            return Response::json(array('error' => 301, 'error_message' => 'Not status'));
        }

        $checksum = $this->__get_checksum_ninjav();

        if(empty($checksum)){
            $this->_error           = true;
            $this->_error_message   = "Mã checksum không đúng";
            return $this->_ResponseData();
        }

       

        //define pickup, delivery fail
        $dataPickupFail = array(
            'Nobody at Location' => array('reason_id' => 1,'reason'=> 'Người bán không ở địa chỉ lấy hàng đã cũng cấp'),
            'Inaccurate Address' => array('reason_id' => 2,'reason'=> 'Địa chỉ lấy hàng không chính xác'),
            'Parcel Not Available' => array('reason_id' => 3,'reason'=> 'Không có hàng gửi'),
            'Parcel Too Bulky'  => array('reason_id' => 4,'reason'=> 'Hàng gửi quá cồng kềnh'),
            'Cancellation Requested' => array('reason_id' => 5,'reason'=> 'Huỷ yêu cầu lấy hàng do bưu tá huỷ')
        );
        $dataDeliveryFail = array(
            'Return to Sender: Nobody at address' => array('reason_id' => 1,'reason'=> 'Phát không thành công khách hàng không ở địa chỉ cung cấp'),
            'Return to Sender: Unable to find Address' => array('reason_id' => 2,'reason'=> 'Không thể xác định địa điểm giao hàng'),
            'Return to Sender: Item refused at Doorstep' => array('reason_id' => 3,'reason'=> 'Khách hàng từ chối nhận hàng'),
            'Return to Sender: Refused to pay COD' => array('reason_id' => 4,'reason'=> 'Khách hàng từ chối trả tiền'),
            'Return to Sender: Customer delayed beyond delivery period' => array('reason_id' => 5,'reason'=> 'Khách hàng hẹn giao hàng vào lần khác'),
            'Return to Sender: Cancelled by Shipper' => array('reason_id' => 6,'reason'=> 'Huỷ giao hàng từ bưu tá'),
        );

        

        $dataInput = array(
            'username' => 'njv',
            'password' => 'c5c688235c628707023df94677935909',
            'function' => '',
            'params'   => array(
                'SC_CODE'      => Input::get('tracking_id'),
                'STATUS'       => $id,
                'CITY'         => 'Ha Noi',
                'NOTE'         => $id.' / Tạo đơn hàng qua NJV thành công',
                'ERROR_CODE'   => '',
                'Weight'       => '',
                'MABUUCUC'     => ''
            )
        );

        switch ($id) {
            case '100'://vua duyet sang HVC
                $dataInput['params']['NOTE']   = $id.' / Tạo đơn hàng qua NJV thành công';
                break;
            case '103'://cho lay hang
                $dataInput['params']['NOTE']   = $id.' / Chờ lấy hàng';
                break;
            case '104':// dang lay hang
                $dataInput['params']['NOTE']   = $id.' / Đang lấy hàng';
                break;
            case '105'://nhan bang ke den
                $dataInput['params']['NOTE']   = $id.' / Nhận bảng kê đến';
                break;
            case '106'://lay hang khong thanh cong
                $note = $dataPickupFail[Input::get('comments')]['reason'];
                $dataInput['params']['NOTE']   = $id.' / '.$note;
                break;
            case '107'://thay doi kich thuoc
                $dataInput['params']['NOTE']   = $id.' / Đã được thay đổi kích thước';
                break;
            case '200'://lay thanh cong
                $dataInput['params']['NOTE']   = $id.' / Đã lấy hàng';
                break;
            case '300'://san sang phat hang
                $dataPod = Input::get('pod');
                $dataInput['params']['NOTE']   = $id.' / Đang giao hàng '.$dataPod['name'].' - '.$dataPod['uri'];

                break;
            case '400'://huy don hang
                $dataInput['params']['NOTE']   = $id.' / Huỷ đơn hàng từ HVC';
                break;
            case '401'://phat khong thanh cong, hen phat lai
                $dataInput['params']['NOTE']   = $id.' / Phát không thành công, hẹn lịch phát lại';

                break;
            case '500'://hoan hang
                $note = $dataDeliveryFail[Input::get('comments')]['reason'];
                $dataInput['params']['NOTE']   = $id.$dataDeliveryFail[Input::get('comments')]['reason_id'];
                break;
            case '600'://phat thanh cong
                $dataInput['params']['NOTE']   = $id.' / Phát thành công';
                break;
        }

        $dataInput['TrackingOrder']   = Input::get('tracking_id');
        $dataInput['TrackingCode']    = Input::get('tracking_id');
        $dataInput['Note']            = $dataInput['params']['NOTE'];
        $dataInput['Status']          = $id;
        
        
        

        $this->checksum = base64_encode(md5($dataInput['params']['SC_CODE'].'_'.$dataInput['params']['STATUS'].'_'.$dataInput['password']));

        Input::merge($dataInput);
        return $this->postIndex();

    }



}