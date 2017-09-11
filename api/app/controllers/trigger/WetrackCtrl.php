<?php
namespace trigger;

use Input;
use LMongo;
use Response;
use DB;
use Cache;
use ordermodel\OrderTrackingModel;
class WetrackCtrl extends \BaseController {

    private $courier_map = [
        8  => 100, // EMS - USPS
        15 => 204, // UPS
        17 => 200, // DHL
        18 => 203, // DPEX
        19 => 202, // SFex
        20 => 201,
        21 => 100
    ];


    public function postIndex(){
        $OrderNumber     = Input::get('OrderNumber');
        $TrackingCode    = Input::get('TrackingCode');  
        $TimeCreate      = Input::get('TimeCreate');  
        $StatusId        = Input::get('StatusId');  
        $StatusName      = Input::get('StatusName');  
        $Note            = Input::get('Note');
        $OriginCity      = Input::get('OriginCity');  
        $CurrentCity     = Input::get('CurrentCity');  
        $DestinationCity = Input::get('DestinationCity');
        
        $ResponseData           = [
            'error'    => false
        ];
        
        $ResponseData['input']  = Input::all(); 
        
        if(empty($TrackingCode)  || empty($StatusId) || empty($Note) ){
            $this->_error           = true;
            $this->_error_message   = "Dữ liệu đầu vào không đúng vui lòng thử lại";
            goto done;
        }

        $time_limit     = $this->time_limit;
        
        $Order  = OrderTrackingModel::where('courier_tracking_code', $TrackingCode)->with(['Order'=> function ($query) use ($time_limit){
            return $query->where('time_accept', '>=', $time_limit)
                ->select(['from_user_id', 'id', 'tracking_code'])
                ->with(['FromUserData' => function ($q){
                    return $q->select(['fullname', 'id', 'phone', 'email', 'address', 'career']);
                }]);
        }])->first();

        if(empty($Order)){
            $this->_error = true;
            $this->_error_message = "Không tìm thấy đơn hàng";
            goto done;
        }

        $Order = $Order->toArray();


        $BaseCtrl = new \BaseCtrl;
        Input::merge(['courier' => 23, 'type' => 1]);
        $StatusCourier   = $BaseCtrl->getStatusCourier(false);

        if(empty($StatusCourier[$StatusId])){
            $this->_error = true;
            $this->_error_message = "Trạng thái hvc không tồn tại";
            goto done;
        }


        Input::merge([
            'status'    => $StatusCourier[$StatusId],
            'sc_code'   => $Order['order']['tracking_code'],
            'city'      => !empty($CurrentCity)  ? $CurrentCity : "WT",
            'note'      => $Note,
            'courier'   => 'wt',
            'time_update'=> strtotime($TimeCreate)
        ]);

        $CourierJourney = new \trigger\CourierAcceptJourney;
        return $CourierJourney->postAcceptstatus(true);
        
          
        done: 
            return $this->_ResponseData($ResponseData);
    }

    public function getJobSendOrder(){
        $CourierTrackingCode = Input::get('courier_tracking_code');

        $Model          = new OrderTrackingModel;
        $time_limit     = $this->time_limit;
        $list_user_id   = [];

        $ResponseData = [];
        $Data  = $Model::where('synced', 0)->with(['Order'=> function ($query) use ($time_limit){
            return $query->where('time_accept', '>=', $time_limit)
                        ->select(['from_user_id', 'id', 'tracking_code'])
                        ->with(['FromUserData' => function ($q){
                            return $q->select(['fullname', 'id', 'phone', 'email', 'address', 'career']);
                        }]);
        }]);
        if(!empty($CourierTrackingCode)){
            $Data = $Data->where('courier_tracking_code', $CourierTrackingCode);
        }

        $Data = $Data->first();


        
        if(empty($Data)){
            $this->_error           = true;
            $this->_error_message   = "Không còn đơn nào chưa đẩy !";
            goto done;
            
        }

        $Data = $Data->toArray();

        $ResponseData['data'] = $Data['order'];
        

        if(empty($Data['order'])){
            $this->_error           = true;
            $this->_error_message   = "Không lấy được dữ liệu đơn hàng !";
            $this->_error_code      = $Data;
            goto done;
        }

        if(empty($Data['order']['from_user_data'])){
            $this->_error           = true;
            $this->_error_message   = "Không lấy được dữ liệu khách hàng !";
            $this->_error_code      = $Data;
            
            goto done;
        }

        $Params = [
            "Customer"=>[
                "PhoneNumber"   => $Data['order']['from_user_data']['phone'],
                "Email"         => $Data['order']['from_user_data']['email'],
                "FullName"      => $Data['order']['from_user_data']['fullname'],
                "Address"       => $Data['order']['from_user_data']['address']
            ],
            "Tracking"      => $Data['courier_tracking_code'],
            "Courier"       => $this->courier_map[$Data['courier_tracking_id']],
            "OrderNumber"   => $Data['order']['tracking_code'],
            "ReceiveName"   => "",
            "ReceivePhone"  => "",
            "Note"          => ""
        ];

        $Response = \cURL::newJsonRequest('post', 'http://apps.wetrack.asia/merchant/v1/add_tracking', $Params)
                        ->setHeader('Authorization', 'qSdaGhMQYUErRZ2dSPC5BRWbC3+wqpJTfXuj4A2mNfTw/AU3Yo9AED7NCb3DBGjX')
                        ->send();

        
        $Responses = json_decode($Response, 1);
        
        
        $ResponseData['params'] = $Params;
        $ResponseData['response'] = $Responses;
        $ResponseData['_response'] = $Response;
        

        if($Response->statusCode == 200){
            OrderTrackingModel::where('id', $Data['id'])->update(['synced'=>1]);
        }

        done:
            return $this->_ResponseData($ResponseData);
    }


}
