<?php

namespace trigger;

use Input;
use LMongo;
use Response;
use DB;
use Cache;
use ordermodel\OrdersModel;

class EmsCourierCtrl extends \BaseController {
    private $error          = false;
    private $message        = 'Thành Công';
    private $code           = 'SUCCESS';
    private $output         = [];
    private $json           = true;

    private $__LadingOne;
    private $__Location     = [];

    private $list_city      = [];
    private $list_district  = [];
    private $list_ward      = [];


    function __construct(){

    }

    private function _getLading(){

        $fields = ['id','from_address_id','to_address_id','checking','fragile','service_id','courier_id','tracking_code',
            'from_user_id','from_city_id','from_district_id','from_address','from_ward_id',
            'to_name','to_phone','product_name','total_weight','total_quantity','total_amount','status'];

        if(Input::has('tracking_code')){
            $LadingOne = OrdersModel::where('tracking_code',Input::get('tracking_code'))->where('time_accept','>=',$this->time() - 2592000);
        }

        return  $LadingOne->where('time_create','>=',$this->time() - $this->time_limit)->with(
            array(
                'ToOrderAddress'
            ))
            ->first();
    }

    //get location
    private function _getLocationMap(){
        $this->list_city        = [$this->__LadingOne->from_city_id, $this->__LadingOne->to_order_address->city_id];
        $this->list_district    = [$this->__LadingOne->from_district_id, $this->__LadingOne->to_order_address->province_id];
        $this->list_ward        = [$this->__LadingOne->from_ward_id, $this->__LadingOne->to_order_address->ward_id];
        $LocationModel          = new \CourierLocationModel;

        // ward
        if(!empty($this->list_ward)){
            $LocationPickup = $LocationModel::where('courier_id', $this->__LadingOne->courier_id)->whereIn('ward_id', $this->list_ward)->get()->toArray();

            if(!empty($LocationPickup)){
                foreach($LocationPickup as $val){
                    if($val['ward_id']  == $this->__LadingOne->from_ward_id){
                        $this->__Location['from']   = [
                            'city_id'       => $val['courier_city_id'],
                            'district_id'   => $val['courier_province_id'],
                            'ward_id'       => $val['courier_ward_id']
                        ];
                    }

                    if($val['ward_id']  == $this->__LadingOne->to_order_address->ward_id){
                        $this->__Location['to']   = [
                            'city_id'       => $val['courier_city_id'],
                            'district_id'   => $val['courier_province_id'],
                            'ward_id'       => $val['courier_ward_id']
                        ];
                    }
                }
            }
        }

        // District
        if(!isset($this->__Location['from']) || !isset($this->__Location['to'])){
            $LocationPickup = $LocationModel->where('courier_id', $this->__LadingOne->courier_id)->whereIn('province_id', $this->list_district)->where('ward_id',0)->get()->toArray();
            if(!empty($LocationPickup)){
                foreach($LocationPickup as $val){
                    if(!isset($this->__Location['from']) && ($val['province_id']  == $this->__LadingOne->from_district_id)){
                        $this->__Location['from']   = [
                            'city_id'       => $val['courier_city_id'],
                            'district_id'   => $val['courier_province_id'],
                            'ward_id'       => ''
                        ];
                    }

                    if(!isset($this->__Location['to']) && ($val['province_id']  == $this->__LadingOne->to_order_address->province_id)){
                        $this->__Location['to']   = [
                            'city_id'       => $val['courier_city_id'],
                            'district_id'   => $val['courier_province_id'],
                            'ward_id'       => ''
                        ];
                    }
                }
            }
        }

        // City
        if(!isset($this->__Location['from']) || !isset($this->__Location['to'])){
            $LocationPickup = $LocationModel->where('courier_id', $this->__LadingOne->courier_id)->whereIn('city_id', $this->list_city)->where('province_id',0)->where('ward_id',0)->get()->toArray();
            if(!empty($LocationPickup)){
                foreach($LocationPickup as $val){
                    if(!isset($this->__Location['from']) && ($val['city_id']  == $this->__LadingOne->from_city_id)){
                        $this->__Location['from']   = [
                            'city_id'       => $val['courier_city_id'],
                            'district_id'   => '',
                            'ward_id'       => ''
                        ];
                    }

                    if(!isset($this->__Location['to']) && ($val['city_id']  == $this->__LadingOne->to_order_address->city_id)){
                        $this->__Location['to']   = [
                            'city_id'       => $val['courier_city_id'],
                            'district_id'   => '',
                            'ward_id'       => ''
                        ];
                    }
                }
            }
        }

        return;
    }

    public function getCaculate(){
        $this->__LadingOne = $this->_getLading();
        if(!isset($this->__LadingOne->id)){
            $this->message  = 'Không tồn tại đơn hàng.';
            $this->code     = 'ORDER_NOT_EXISTS';
            return $this->ResponseData(true);
        }


        $this->_getLocationMap();
        return $this->_calculate();
    }

    public function _calculate(){
        if(!isset($this->__Location['from']) || !isset($this->__Location['to'])){
            $this->message  = 'Khu vực lấy hàng hoặc giao hàng chưa được hệ thống hỗ trợ.';
            $this->code     = 'SYSTEM_UNSUPPORT';
            return false;
        }

        $LadingParams   = [
            'TokenKey'          => 'shipchung@123',
            'PickupZipCode'     => $this->__Location['from']['city_id'],
            'ConsigneeZipCode'  => $this->__Location['to']['city_id'],
            'Weight'            => (string)$this->__LadingOne->total_weight
        ];

        $soapClient = new \SoapClient("http://api.ems.com.vn/api/ems_partner.asmx?wsdl");
        
        $respond    = $soapClient->Domestic(['Order' => json_encode($LadingParams)]);

        dd($respond);
    }

    private function ResponseData($error){
        $ret = [
            'error'         => $error,
            'code'          => $this->code,
            'message'       => $this->message,
            'data'          => $this->output
        ];

        return $this->json ? Response::json($ret) : $ret;
    }
}
