<?php

namespace trigger;

use Input;
use LMongo;
use Response;
use DB;
use Cache;

class PushStatusCtrl extends \BaseCtrl {
    public function getJourney($idLog = ''){
        $LMongo         = new \LMongo;
        $Log            = $LMongo::collection('log_journey_notice');

        if(!empty($idLog)){
            if($idLog == 1){
                $Log    = $Log->where('domain','chodientu.vn');
            }elseif($idLog == 2){
                $Log    = $Log->where('domain','boxme.vn');
            }elseif($idLog == 3){
                $Log    = $Log->where('domain','prostore.vn');
            }else{
                $Log    = $Log->where('_id', new \MongoId($idLog));
            }
        }

        $Log    = $Log->where('accept',0)
            ->orderBy('time_create', 'asc')
            ->first();

        if(!$Log){
            return Response::json( array(
                'error'         => 'EMPTY'
            ) );
        }

        if($Log['domain']   == 'boxme.vn'){
            $Update['accept']           = 1;
            $LMongo::collection('log_journey_notice')
                ->where('_id',new \MongoId($Log['_id']))
                ->update($Update);
            //return $this->_status_boxme($Log);
        }elseif($Log['domain']   == 'chodientu.vn'){
            return $this->_status_cdt($Log);
        }elseif($Log['domain'] == 'prostore.vn'){
            return $this->_status_prostore($Log);
        }elseif($Log['domain'] == 'juno.vn'){
            return $this->_status_juno($Log);
        }elseif($Log['domain'] == 'www.ebay.vn'){
            return $this->_status_ebay($Log);
        }
        return;
    }

    public function _status_boxme($Log){
        $Params = [
                'TrackingCode'          => $Log['tracking_code'],
                'CourierTrackingCode'   => isset($Log['courier_tracking_code']) ? $Log['courier_tracking_code'] : '',
                'StatusCode'    => $Log['status'],
                'TimeJourney'   => $Log['time_create'],
        ];

        if(isset($Log['time'])){
            $Params['TimeCreate']       =  $Log['time']['time_create'];
            $Params['TimeAccept']       =  $Log['time']['time_accept'];
            $Params['TimeApprove']      =  $Log['time']['time_approve'];
            $Params['TimePickup']       =  $Log['time']['time_pickup'];
            $Params['TimeSuccess']      =  $Log['time']['time_success'];
        }

        if(isset($Log['weight'])){
            $Params['Weight']       =  $Log['weight'];
        }

        $Params   = json_encode($Params);

        $respond = \cURL::rawPost('http://seller.boxme.vn/api/update_order_status_sc?Token=6c66437f3d839cb1d247990a40b2afae',$Params);


        $Update['time_accept']  = time();
        $Update['params']       = $Params;
        $Update['result']       = $respond->body;

        if(!$respond->body){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'FAIL_API',
                'error_message' => 'Lỗi API',
                'data'          => $Log
            ];
            return Response::json( $ResponseData);
        }else{
            $decode = json_decode($respond->body,true);
            $Update['accept']           = 1;
            $Update['messenger']        = $decode['Message'];
            $Update['error_code']       = $decode;

            $ResponseData  =    [
                'error'         => false,
                'message'       => 'SUCCESS',
                'error_message' => 'Thành công',
                'data'          => $Log
            ];
        }

        $LMongo         = new \LMongo;
        try{
            $LMongo::collection('log_journey_notice')
                ->where('_id',new \MongoId($Log['_id']))
                ->update($Update);

        }catch (\Exception $e){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'UPDATE_ERROR',
                'error_message' => $e->getMessage(),
                'data'          => $Log
            ];
        }

        return Response::json( $ResponseData);
    }

    public function __get_partner_status(){

    }

    public function _status_juno($Log){
        $LMongo             = new \LMongo;
        Input::merge(['group' => 3]);
        $GroupStatus        = $this->getGroupByStatus(false);

        if(!isset($GroupStatus[(int)$Log['status']]) && !isset($GroupStatus[(int)$Log['status']]['group_status'])){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'FAIL_API',
                'error_message' => 'Lỗi API',
                'data'          => $Log
            ];
            return Response::json( $ResponseData);
        }

        $Data = [
            "tracking_code" => $Log['tracking_code'],
            "status_code"  => $GroupStatus[(int)$Log['status']]['group_status'],
            "status_name"  => $GroupStatus[(int)$Log['status']]['group_name'],
            "update_time" => $Log['time_create'],
        ];

        $respond = \cURL::jsonPost('http://inside.juno.vn/insidegate/apis/updStatusAPISC',$Data);

        if(!$respond){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'FAIL_API',
                'error_message' => 'Lỗi API',
                'data'          => $Log
            ];
            return Response::json( $ResponseData);
        }else{
            $respond = json_decode($respond, 1);

            $Update['accept']       = 1;
            $Update['time_accept']  = time();
            $Update['params']       = $Data;
            $Update['result']       = [
                'error_code'    => $respond['status'],
                'message'       => $respond['message'],
                'data'          => $respond['detail'],
            ];

            $Update['messenger']                        = $respond['message'];
            $Update['error_code']                       = $respond['status'];

            $ResponseData  =    [
                'error'         => false,
                'message'       => 'SUCCESS',
                'error_message' => 'Thành công',
                'data'          => $Log
            ];
        }


        try{
            $LMongo::collection('log_journey_notice')
                ->where('_id',new \MongoId($Log['_id']))
                ->update($Update);

        }catch (\Exception $e){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'UPDATE_ERROR',
                'error_message' => $e->getMessage(),
                'data'          => $Log
            ];
        }

        return Response::json( $ResponseData);
    }

    public function _status_ebay($Log){
        $LMongo             = new \LMongo;
        Input::merge(['group' => 3]);
        $GroupStatus        = $this->getGroupByStatus(false);

        if(!isset($GroupStatus[(int)$Log['status']]) && !isset($GroupStatus[(int)$Log['status']]['group_status'])){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'FAIL_API',
                'error_message' => 'Lỗi API',
                'data'          => $Log
            ];
            return Response::json( $ResponseData);
        }

        $Data = [
            "tracking_code" => $Log['tracking_code'],
            "status_code"   => $GroupStatus[(int)$Log['status']]['group_status'],
            "status_name"   => $GroupStatus[(int)$Log['status']]['group_name'],
            "update_time"   => $Log['time_create'],
            "token"         => md5('vn_provider_' . $Log['tracking_code'] . '_shipchung')
        ];

        $respond = \cURL::jsonPost('http://backendbeta.chodientu.vn/outrequest/shipchungupdateshippingstatus',$Data);

        if(!$respond){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'FAIL_API',
                'error_message' => 'Lỗi API',
                'data'          => $Log
            ];
            return Response::json( $ResponseData);
        }else{
            $respond = json_decode($respond, 1);

            $Update['accept']       = 1;
            $Update['time_accept']  = time();
            $Update['params']       = $Data;
            $Update['result']       = [
                'error_code'    => $respond,
            ];

            $Update['error_code']                       = $respond;

            $ResponseData  =    [
                'error'         => false,
                'message'       => 'SUCCESS',
                'error_message' => 'Thành công',
                'data'          => $Log
            ];
        }


        try{
            $LMongo::collection('log_journey_notice')
                ->where('_id',new \MongoId($Log['_id']))
                ->update($Update);

        }catch (\Exception $e){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'UPDATE_ERROR',
                'error_message' => $e->getMessage(),
                'data'          => $Log
            ];
        }

        return Response::json( $ResponseData);
    }

    public function _status_cdt($Log){
        $LMongo         = new \LMongo;
        $Status         = $this->__status_partner('chodientu.vn');
        if(empty($Status)){
            return Response::json( [
                'error'         => true,
                'message'       => 'EMPTY_STATUS_PARTNER',
                'error_message' => 'Lỗi trạng thái đối tác',
                'data'          => $Log
            ]);
        }

        $Params = [
            "email"     => "dev@chodientu.vn",
            "code"      => "chodientu69!@#96",
            "params"    => json_encode([
                "scId"                  => $Log['tracking_code'],
                "scWeight"              => $Log['weight'],
                "shipmentStatus"        => $Status[(int)$Log['status']]['code'],
                "scCode"                => (int)$Log['status'],
                "message"               => $Status[(int)$Log['status']]['detail'],
                "scCodPrice"            => $Log['fee']['sc_cod'] - $Log['fee']['sc_discount_pcod'],
                "scShipmentPrice"       => $Log['fee']['sc_pvc'] - $Log['fee']['sc_discount_pvc'],
                "scProtectedFee"        => $Log['fee']['sc_pbh'],
                "scWeightexceedFee"         => $Log['fee']['sc_pvk'],
                "scDiscountShipmentPrice"   => $Log['fee']['sc_discount_pvc'],
                "scDiscountCodPrice"        => $Log['fee']['sc_discount_pcod'],
                "timeJourney"           => $Log['time_create']
            ])
        ];

        $respond = \cURL::jsonPost('https://www.chodientu.vn/api/lading/updatestatus.api',$Params);



        if(!$respond){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'FAIL_API',
                'error_message' => 'Lỗi API',
                'data'          => $Log
            ];
            return Response::json( $ResponseData);
        }else{
            $respond = json_decode($respond, 1);

            $Update['accept']       = 1;
            $Update['time_accept']  = time();
            $Update['params']       = $Params;
            $Update['result']       = [
                'success'   => $respond['success'],
                'message'   => $respond['message']
            ];

            $Update['messenger']                        = $respond['message'];
            $Update['error_code']                       = $respond['message'];

            $ResponseData  =    [
                'error'         => false,
                'message'       => 'SUCCESS',
                'error_message' => 'Thành công',
                'data'          => $Log
            ];
        }


        try{
            $LMongo::collection('log_journey_notice')
                ->where('_id',new \MongoId($Log['_id']))
                ->update($Update);

        }catch (\Exception $e){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'UPDATE_ERROR',
                'error_message' => $e->getMessage(),
                'data'          => $Log
            ];
        }

        return Response::json( $ResponseData);
    }

    public function _status_prostore($Log){
        $LMongo             = new \LMongo;
        Input::merge(['group' => 3]);
        $GroupStatus        = $this->getGroupByStatus(false);

        if(!isset($GroupStatus[(int)$Log['status']]) && !isset($GroupStatus[(int)$Log['status']]['group_status'])){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'FAIL_API',
                'error_message' => 'Lỗi API',
                'data'          => $Log
            ];
            return Response::json( $ResponseData);
        }

        $Data = [
            "tracking_code" => $Log['tracking_code'],
            "status_code"  => $GroupStatus[(int)$Log['status']]['group_status'],
            "status_name"  => $GroupStatus[(int)$Log['status']]['group_name'],
            "update_time" => $Log['time_create'],
        ];

        $Params = [
            "api_key"       => "mcYG486x3unJ8cBT",
            "params"        => json_encode($Data),
            "checksum"      => md5(json_encode($Data)."mcYG486x3unJ8cBT")
        ];

        $respond = \cURL::post('http://prostore.vn/partner/update_shipment_status',$Params);

        if(!$respond){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'FAIL_API',
                'error_message' => 'Lỗi API',
                'data'          => $Log
            ];
            return Response::json( $ResponseData);
        }else{
            $respond = json_decode($respond, 1);

            $Update['accept']       = 1;
            $Update['time_accept']  = time();
            $Update['params']       = $Params;
            $Update['result']       = [
                'error_code'    => $respond['error_code'],
                'message'       => $respond['message'],
                'data'          => $respond['data'],
            ];

            $Update['messenger']                        = $respond['message'];
            $Update['error_code']                       = $respond['error_code'];

            $ResponseData  =    [
                'error'         => false,
                'message'       => 'SUCCESS',
                'error_message' => 'Thành công',
                'data'          => $Log
            ];
        }


        try{
            $LMongo::collection('log_journey_notice')
                ->where('_id',new \MongoId($Log['_id']))
                ->update($Update);

        }catch (\Exception $e){
            $ResponseData  =    [
                'error'         => true,
                'message'       => 'UPDATE_ERROR',
                'error_message' => $e->getMessage(),
                'data'          => $Log
            ];
        }

        return Response::json( $ResponseData);
    }
}
