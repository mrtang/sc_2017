<?php

namespace trigger;
use LMongo;
use anlutro\cURL\Request;
use Cache;
use Response;
use Input;

use ordermodel\OrdersModel;
use metadatamodel\GroupOrderStatusModel;

class CourierCrawlJourney extends \BaseController {
    
    private $__Ladings;
    
    private $CourierCrawl = [8];
    
    private $ConvertCourier =   [
                                    8   => 'ems',
                                    9   => 'gold'
                                ];
    
    function checkExistLog($time = '', $status = ''){
        if(in_array($status, ['I','G'])){
            return false;
        }

        $Journey    =  \LMongo::collection('log_journey_lading')
                        ->where('input.username',$this->ConvertCourier[$this->__Ladings['courier_id']])
                        ->where('tracking_code',$this->__Ladings['tracking_code'])
                        ->whereIn('accept',[0,1])
                        ->orderBy('time_create','desc')->first();

        if(isset($Journey['_id'])){
            if(isset($Journey['input']['statustime']) && !empty($Journey['input']['statustime'])){
                if($Journey['input']['statustime'] == $time){
                    return true;
                }

            }else{
                if($Journey['input']['Status'] == $status){
                    return true;
                }
            }
        }

        return false;
    }
    
    function getIndex(){
        $this->_getLading();
        //return Response::json($this->__Ladings);

        if(!$this->__Ladings){
            return Response::json( array(
                'error'         => 'empty_data', 
                'error_message' => 'Đã chạy hết dữ liệu',
                'data'          => null
                ) );
        }
                
        $funcName = '_process_'.$this->ConvertCourier[$this->__Ladings['courier_id']];
        
        $result = $this->$funcName();var_dump($result);
        //return Response::json($this->$funcName());
        
        if(!$result){
            OrdersModel::where('id',$this->__Ladings['id'])->update(['time_cron_journey' => $this->time()]);
            return Response::json( array(
                'error'         => 'fail', 
                'error_message' => 'Không crawl được lịch trình',
                'data'          => $this->__Ladings
                ) );
        }


        if($this->checkExistLog((string)$result['statustime'], $result['status'])){
            OrdersModel::where('id',$this->__Ladings['id'])->update(['time_cron_journey' => $this->time()]);
            return Response::json( array(
                'error'         => 'exist_log', 
                'error_message' => 'Đã tồn tại trạng thái này',
                'data'          => $this->__Ladings
                ) );
        }
        
        $insert = array(
            "tracking_code"     => $this->__Ladings['tracking_code'],
            'tracking_number'   => (int)substr($this->__Ladings['tracking_code'],2),
            'domain'        => $this->__Ladings['domain'],
            "input" => Array (
                "username" => $this->ConvertCourier[$this->__Ladings['courier_id']],
                "function"      => "LichTrinh",
                "statustime"    => $result['statustime'],
                "params" => Array (
                    "SC_CODE"   => $this->__Ladings['tracking_code'],
                    "STATUS"    => $result['status'],
                    "CITY"      => $result['city'],
                    "NOTE"      => $result['note'],
                ),
                "TrackingOrder" =>  isset($result['TrackingNumber']) ? $result['TrackingNumber'] : $this->__Ladings['tracking_code'],
                "TrackingCode"  =>  $this->__Ladings['tracking_code'],
                "Status"        => $result['status'],
                "Note"          => $result['note'],
                "City"          => $result['city'],
            ),
            "priority"      => 2,
            "accept"        => 0,
            "time_create"   => $this->time(),
            "time_update"   => $this->time()
        );
        
        //return Response::json($insert);

        $IdLog  = \LMongo::collection('log_journey_lading')->insert($insert);

        if($IdLog){
            OrdersModel::where('id',$this->__Ladings['id'])->update(['time_cron_journey' => $this->time()]);

            // Call Predis
            $this->RabbitJourney($IdLog);

            return Response::json( array(
                'error'         => 'success', 
                'error_message' => 'Thành công',
                'data'          => $this->__Ladings
                ) );
        }
        else{
            return Response::json( array(
                'error'         => 'db_fail', 
                'error_message' => 'Lỗi ghi log lịch trình',
                'data'          => $this->__Ladings
                ) );
        }
    }
    
    private function _getLading($config = array()){
        $dbListStatus   = GroupOrderStatusModel::whereIn('group_status',[25,26,27,28,29,31,32])->get(['order_status_code']);
        $arrLstatus     = array();
        
        foreach($dbListStatus as $value){
            $arrLstatus[] = $value["order_status_code"];
        }
        
        //return $this->__Ladings = $arrLstatus;
        
        $fields = ['id','courier_id','tracking_code','status','domain'];
        
        if(Input::has('TrackingCode')){
            $this->__Ladings = OrdersModel::where('tracking_code',Input::get('TrackingCode'))->where('time_accept','>=',$this->time() - $this->time_limit)->first($fields);
        }
        else{
            $this->__Ladings = OrdersModel::whereIn('status',$arrLstatus)
                    ->whereIn('courier_id',$this->CourierCrawl)
                    ->where('time_accept','>=',$this->time() - 7776000)
                    ->orderBy('time_cron_journey','asc')
                    ->orderBy('time_accept','asc')
                    ->first($fields);
        }        
        
        return $this->__Ladings;
    }
    
    // Goldtimes
    private function _login_gold(){
        $cacheFile  = 'goldtime_login';
        
        //if(Cache::has($cacheFile))
            //return json_decode(Cache::get($cacheFile));
 
        // Get token
        $RespondToken = \cURL::get('http://115.78.132.190:8081/epost-adapter/oauth/token?grant_type=password&client_id=my-trusted-client&username=ship_chung&password=ship_chung@123');
        
        if(!$RespondToken){
            return array('ERROR'=> 'FAIL', 'MSG'=> 'Login thất bại');
        }
        
        $result = json_decode($RespondToken);
        //Cache::put($cacheFile, $RespondToken, ($result->expires_in - 60) / 60);        
        return $result;
    }
    
    function _process_gold(){
        $Token = $this->_login_gold();
        $LadingParams = array('TrackingNumber' => $this->__Ladings['tracking_code']);
        $respond = \cURL::newRequest('post', 'http://115.78.132.190:8081/epost-adapter/order/detail', $LadingParams)
                        ->setHeader('Authorization', 'Bearer '.$Token->access_token)
                        ->send();
        //return $respond->body;                        
        if(!$respond){
            return false;
        }
        
        $decode = json_decode( (string)$respond->body,1);
         
        if($decode['error'] == "00"){
            return array(
            'status' => $decode['data']['status'],
            'city' => empty($decode['data']['journey']) ? $decode['data']['consigneeAddress'] : $decode['data']['journey'][0]['consigneeZipCode'],
            'note' => empty($decode['data']['journey']) || !$decode['data']['journey'][0]['postmanName'] ? 'Hệ thống tự động cập nhật lịch trình.' : trim($decode['data']['journey'][0]['postmanName']));
        }
        
        return false;
    }
    
    
    function _process_ems(){
        ini_set('default_socket_timeout', 600); // or whatever new value you want
        $soapClient = new \SoapClient("http://demo.ems.com.vn/API/EMS_SHIPCHUNG.asmx?wsdl");
        $LadingParams = array('TrackingNumber' => $this->__Ladings['tracking_code'],"TokenKey" => "ems!@#");
        $respond = $soapClient->detail($LadingParams);
        //return $respond->detailResult;

        if(!$respond){
            return false;
        }
        
        $decode = json_decode($respond->detailResult,1);
         
        if($decode['error'] == "00" && $decode['data']['status'] != '' && $decode['data']['status'] != 'Null'){
            return array(
                'TrackingNumber'    => $decode['data']['TrackingNumber'],
                'status'            => $decode['data']['status'],
                'city'              => $decode['data']['ConsigneeAddress'],
                'note'              => isset($decode['data']['StatusNote']) ? $decode['data']['StatusNote'] : '',
                'statustime'        => !empty($decode['data']['statustime']) ? strtotime(str_replace('/','-',$decode['data']['statustime'])) : ''
            );
        }
        
        return false;
    }
    
}
