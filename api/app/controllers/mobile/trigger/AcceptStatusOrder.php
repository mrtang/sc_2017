<?php

namespace mobile_trigger;
use ordermodel\OrdersModel;
use ordermodel\StatusModel;
use order\OrderController;
use ticketmodel\RequestModel;
use ticketmodel\ReferModel;
use ticketmodel\FeedbackModel;
use metadatamodel\OrderStatusModel;
use omsmodel\PipeJourneyModel;
use Input;
use LMongo;
use Response;
use DB;
use Cache;
use metadatamodel\GroupOrderStatusModel;
use omsmodel\SellerModel;
use order\StatusOrderCtrl;
use ordermodel\AddressModel;
use QueueModel;
use order\ChangeOrderCtrl;

class AcceptStatusOrder extends \BaseController {
    private $__Lading;

    private $TrackingCode;
    private $Note;
    private function _getOrder(){
        $this->TrackingCode = Input::has('tracking_code') ? Input::get('tracking_code') : "";
        $this->Note         = Input::has('note')          ? Input::get('note')          : "";
    
        if (empty($this->TrackingCode)) {
            return false;
        }
        $OrdersModel    = new OrdersModel;
        
        $this->__Lading = $OrdersModel::where('tracking_code',$this->TrackingCode);
       
       $this->__Lading = $this->__Lading->where('time_accept','>=',time() - $this->time_limit);

        return $this->__Lading          = $this->__Lading->first();
    }


    public function postReDelivery (){
        $this->_getOrder();
        if (empty($this->__Lading)) {
            return Response::json([
                'error'         => true,
                'error_message' => "Không tìm thấy đơn hàng này, quý khách vui lòng thử lại sau ",
                'data'          => [
                    'tracking_code' => $this->TrackingCode
                ]
            ]);
        }



        $OrderStatus = $this->__Lading->status;
        $Courier     = $this->__Lading->courier_id;
        $PipeStatus  = $OrderStatus == 60 ? 903 : 707;
        $Group       = $OrderStatus == 60 ? 31  : 29;

        $frm = [
            'tracking_code' => $this->__Lading->id,
            'pipe_status'   => $PipeStatus,
            'note'          => !empty($this->Note) ? $this->Note : "Khách hàng yêu cầu phát lại",
            'group'         => $Group,
            'option'        => 1,
            'action'        => 1,
        ];
        Input::merge($frm);
        $Ctrl     = new \order\OrderProcessController();
        $Response = $Ctrl->postCreateJourney();
        $Output   = json_decode($Response,1);
        //update view in tbl queue
        if($Output['error'] == false){
            //check queue_id
            $Check = QueueModel::where('id',$Output['queue_id'])->first();
            if($Check){
                $Update = QueueModel::where('id',$Output['queue_id'])->update(array('view' => $Output['status']));
                return $Response;
            }else{
                return $Response;
            }
        }else{
            return $Response;
        }
    }

    public function postConfirmReturn (){
        $this->_getOrder();
        if (empty($this->__Lading)) {
            return Response::json([
                'error'         => true,
                'error_message' => "Không tìm thấy đơn hàng này, quý khách vui lòng thử lại sau ",
                'data'          => []
            ]);
        }


        $frm = [
            'status'        => 61,
            'tracking_code'       => $this->__Lading->tracking_code,
            'courier'       => $this->__Lading->courier_id,
            'city'          => 'SC_APP',
            'note'          => !empty($this->Note) ? $this->Note : "Khách hàng yêu cầu chuyển hoàn",
            'option'        => 1,
            'action'        => 1
        ];

        Input::merge($frm);

        $Ctrl     = new \order\OrderProcessController();
        $Response = $Ctrl->postChangeOrder();
        $Output   = json_decode($Response,1);
        //update view in tbl queue
        if($Output['error'] == false){
            //check queue_id
            $Check = QueueModel::where('id',$Output['queue_id'])->first();
            if($Check){
                $Update = QueueModel::where('id',$Output['queue_id'])->update(array('view' => $Output['status']));
                return $Response;
            }else{
                return $Response;
            }
        }else{
            return $Response;
        }
    }

    public function postReportCancel (){
        $this->_getOrder();
        if (empty($this->__Lading)) {
            return Response::json([
                'error'         => true,
                'error_message' => "Không tìm thấy đơn hàng này, quý khách vui lòng thử lại sau ",
                'data'          => [
                    'tracking_code' => $this->TrackingCode
                ]
            ]);
        }


        $frm = [
            'status'        => 28,
            'sc_code'       => $this->__Lading->tracking_code,
            'courier'       => $this->__Lading->courier_id,
            'city'          => 'SC_APP',
            'note'          => !empty($this->Note) ? $this->Note : "Khách hàng yêu cầu huỷ đơn",
        ];

        Input::merge($frm);

        $Ctrl     = new \trigger\CourierAcceptJourney();
        $Response = $Ctrl->postAcceptstatus();
        return $Response;
    }

    public function postRePickup (){
        $this->_getOrder();
        if (empty($this->__Lading)) {
            return Response::json([
                'error'         => true,
                'error_message' => "Không tìm thấy đơn hàng này, quý khách vui lòng thử lại sau ",
                'data'          => [
                    'tracking_code' => $this->TrackingCode
                ]
            ]);
        }


        $frm = [
            'group'        => 109,
            'tracking_code' => $this->__Lading->id,
            'city'          => 'SC_APP',
            'note'          => !empty($this->Note) ? $this->Note : "Khách hàng yêu cầu lấy lại",
            'option'        => 3,
            'action'        => 1,
            'pipe_status'   => 1
        ];

        Input::merge($frm);

        $Ctrl     = new \order\OrderProcessController();
        $Response = $Ctrl->postConfirmPickup();
        //update view in tbl queue
        if($Output['error'] == false){
            //check queue_id
            $Check = QueueModel::where('id',$Output['queue_id'])->first();
            if($Check){
                $Update = QueueModel::where('id',$Output['queue_id'])->update(array('view' => $Output['status']));
                return $Response;
            }else{
                return $Response;
            }
        }else{
            return $Response;
        }
    }

}