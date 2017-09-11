<?php

class ApiPublicCtrl extends \BaseController {
    /*
     * get inventory by code domain
     */
    private $token  = [
        'fa327e984ca800c31d290a4e04f8abcd'    => 'boxme.vn'
    ];


    public function getKhoCdt($Code){
        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => sellermodel\UserInventoryModel::where('sys_name','chodientu.vn')->where('sys_number',$Code)->first(['sys_number','name','user_name','phone','city_id','province_id','ward_id','address'])
        );
        return Response::json($contents);
    }

    public function getOrderDetail(){
        $Token          = Input::has('token')   ? strtolower(trim(Input::get('token')))                     : '';
        $TrackingCode   = Input::has('tracking_code')   ? strtoupper(trim(Input::get('tracking_code')))     : '';

        $Data       = [];
        if(!empty($TrackingCode) && !empty($Token) && isset($this->token[$Token])){
            $OrdersModel = new ordermodel\OrdersModel;
            $Data        = $OrdersModel::where('time_accept', '>=', $this->time() - 8035200)
                ->where('tracking_code', $TrackingCode)
                ->where('domain', $this->token[$Token])
                ->first(['tracking_code', 'time_create', 'time_accept', 'time_approve', 'time_pickup', 'time_success']);

        }

        return Response::json(
            ['error'  => false,'code'  => 'SUCCESS', 'error_message' => 'Thành công', 'tracking_code' => $TrackingCode, 'data'    => $Data]
        );
    }

    public function getCalculateFeeVc(){
        $TrackingCode   = Input::has('tracking_code')   ? strtoupper(trim(Input::get('tracking_code')))     : '';
        $Weight         = Input::has('weight')          ? (int)Input::get('weight')                         : 0;

        if(empty($TrackingCode)){
            return Response::json(
                ['error'  => true,'code'  => 'EMPTY_TRACKING_CODE',
                 'error_message' => 'Không nhận được mã  tracking_code',
                 'tracking_code' => $TrackingCode, 'weight' => $Weight]
            );
        }

        if(empty($Weight)){
            return Response::json( ['error'  => true,'code'  => 'EMPTY_WEIGHT',
                                    'error_message' => 'Không nhận được khối lượng mới',
                                    'tracking_code' => $TrackingCode, 'weight' => $Weight]);
        }

        $OrdersModel    = new ordermodel\OrdersModel;
        $Order          = $OrdersModel::where('tracking_code', $TrackingCode)
                                      ->where('time_accept','>=', $this->time() - $this->time_limit)
                                      ->where('courier_id',1)
                                      ->with(['ToOrderAddress','OrderDetail'])
                                      ->first()->toArray();

        if(empty($Order)){
            return Response::json(
                ['error'  => true,'code'  => 'ORDER_NOT_EXISTS', 'error_message' => 'Mã đơn hàng không tồn tại',
                 'tracking_code' => $TrackingCode, 'weight' => $Weight]);
        }

        if(empty($Order['to_order_address'])){
            return Response::json(
                ['error'  => true,'code'  => 'TO_ADDRESS_NOT_EXISTS', 'error_message' => 'Địa chỉ người nhận không tồn tại',
                    'tracking_code' => $TrackingCode, 'weight' => $Weight]);
        }

        $DataUpdate = [
            'From'  => [
                'City'      => (int)$Order['from_city_id'],
                'Province'  => (int)$Order['from_district_id'],
                'Ward'      => (int)$Order['from_ward_id'],
                'Stock'     => (int)$Order['from_address_id']
            ],
            'To'    => [
                'City'      => (int)$Order['to_order_address']['city_id'],
                'Province'  => (int)$Order['to_order_address']['province_id'],
                'Ward'      => (int)$Order['to_order_address']['ward_id']
            ],
            'Order' => [
                'Amount'    => $Order['total_amount'],
                'Weight'    => $Weight,
            ],
            'Config'    => [
                'Checking'  => $Order['checking'],
                'Fragile'   => $Order['fragile'],
                'Service'   => $Order['service_id'],
                'Protected' => 2,
                'CoD'       => 1,
                'Payment'   => 1
            ],
            'Domain'        => !empty($Order['domain']) ? $Order['domain'] : 'shipchung.vn',
            'Type'          => 'change'
        ];

        Input::merge($DataUpdate);
        $ApiCourierCtrl = new \ApiCourierCtrl;
        $Calculater     = $ApiCourierCtrl->postCalculate(false);

        if($Calculater['error']){
            $Calculater['tracking_code']    = $TrackingCode;
            $Calculater['weight']           = $Weight;
            return Response::json( $Calculater);
        }

        $Pvc        = ($Calculater['data']['pvc'] - $Calculater['data']['discount']['pvc']);
        $MoneyAdd   = $Pvc - ($Order['order_detail']['sc_pvc'] + $Order['order_detail']['sc_pvk'] - $Order['order_detail']['sc_discount_pvc']);

        return Response::json(
            ['error'  => true,'code'  => 'SUCCESS', 'error_message' => 'Thành Công', 'tracking_code' => $TrackingCode, 'weight' => $Weight,
                'sc_pvc'    => $Pvc,
                'money_add' => $MoneyAdd > 0 ? $MoneyAdd : 0
            ]
        );

    }
}
