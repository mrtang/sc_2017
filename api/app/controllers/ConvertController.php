<?php
//require('simple_html_dom.php');
require_once app_path().'/libraries/nusoap.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ConvertController extends \BaseController {
    private $MasterId = 1;
    public $data        = [];
    public $total       = 0;


    public function getUser(){return 1;
        $TimeStart    = Input::has('time_start')   ? trim(Input::get('time_start'))          : 0; // time_create start   time_stamp
        $TimeEnd      = Input::has('time_end')     ? trim(Input::get('time_end'))            : 0;

        $Model = new systemmodel\UsersModel;


        if(!empty($TimeStart)){
            $TimeStart = strtotime(str_replace('/', '-', $TimeStart.' 00:00:00'));
            $Model  = $Model::where('time_create','>=',$TimeStart);
        }else{
            echo 'time_create_start null';die;
        }

        if(!empty($TimeEnd)){
            $TimeEnd = strtotime(str_replace('/', '-', $TimeEnd.' 23:59:59'));
            $Model    = $Model->where('time_create','<',$TimeEnd);
        }

        $Model  = $Model->where('convert_scf1',0)->orderBy('id','ASC');
        $ModelCount = clone $Model;

        $User   = $Model->first();

        if(!empty($User)){
            // Update User
            $UserModel              = new User; // user metadata
            $DataCreate             = array('id' => (int)$User['id']);
            $Data                   = $UserModel::firstOrCreate($DataCreate); // table User
            $Data->group_id         = (int)$User['group_id'];
            $Data->email            = trim($User['email']);
            $Data->password         = trim($User['password']);
            $Data->fullname         = trim($User['fullname']);
            $Data->phone            = trim($User['mobile_phone']);
            $Data->time_create      = trim($User['time_create']);
            $Data->time_last_login  = trim($User['time_last_login']);
            $Data->active           = (int)$User['active'];

            if(!($Data->save())){
                echo 'update user  fail';die;
            }

            // Update User Info
            $UserInfoModel  = new sellermodel\UserInfoModel;
            $DataCreate     = array('user_id' => (int)$User['id']);
            $Data                   = $UserInfoModel::firstOrCreate($DataCreate); // table User Info
            $Data->user_nl_id       = (int)$User['user_nl_id'];
            $Data->email_nl         = trim($User['email_nl']);
            $Data->email_notice     = trim($User['email_notice']);
            $Data->phone_notice     = trim($User['phone_notice']);
            $Data->freeze_money     = 200000;
            $Data->privilege        = (int)$User['privilege'];
            if(!($Data->save())){
                echo 'update user info fail';die;
            }

            // Update or Insert Token Key User
            if($User['token'] != ''){
                ApiKeyModel::firstOrCreate(array(
                    'user_id'       => (int)$User['id'],
                    'key'           => trim($User['token']),
                    'time_create'   => $this->time(),
                    'active'        => 1
                ));
            }

            // Update courier fee
            $ConfigFee  = systemmodel\UserConfigModel::where('user_id',(int)$User['id'])->first();
            if(!empty($ConfigFee)){
                $ConfigFeeModel         = new sellermodel\FeeModel;
                $Data                   = $ConfigFeeModel::firstOrCreate($DataCreate);
                if((int)$ConfigFee['shipping_fee'] == 0){
                    $Data->shipping_fee = 3;
                }elseif((int)$ConfigFee['shipping_fee'] == 1){
                    $Data->shipping_fee = 2;
                }elseif((int)$ConfigFee['shipping_fee'] > 1){
                    $Data->shipping_fee = 1;
                    $Data->shipping_cost_value  = (int)$ConfigFee['shipping_fee'];
                }

                if((int)$ConfigFee['cod_fee'] == 0){
                    $Data->cod_fee  = 1;
                }elseif((int)$ConfigFee['cod_fee'] == 1){
                    $Data->cod_fee  = 2;
                }

                if(!($Data->save())){
                    echo 'update user fee fail';die;
                }
            }

            // Update stock
            $ConfigStock  = systemmodel\UserStockModel::where('user_id',(int)$User['id'])->get()->toArray();
            if(!empty($ConfigStock)){
                $ConfigStockModel         = new sellermodel\UserInventoryModel;
                foreach($ConfigStock as $val){
                    $DataCreate         = array('user_id' => (int)$User['id'], 'city_id' => (int)$val['city_id'], 'province_id' => (int)$val['district_id']);
                    $Data               = $ConfigStockModel::firstOrCreate($DataCreate);
                    $Data->user_name    = trim($User['fullname']);
                    $Data->phone        = trim($User['mobile_phone']);
                    $Data->address      = trim($val['address']);
                    if(!($Data->save())){
                        echo 'update user stock fail';die;
                    }
                }
            }

            $User->convert_scf1 = 1;
            if(!($User->save())){
                echo 'update user';die;
            }

            $Count =    $ModelCount->count();
            echo 'Còn '.$Count.' user nữa !';

        }else{
            echo 'Hết rồi !';die;
        }

    }

    public function getOrder(){return 1;
        $TimeStart    = Input::has('time_start')   ? trim(Input::get('time_start'))          : 0; // time_create start   time_stamp
        $TimeEnd      = Input::has('time_end')     ? trim(Input::get('time_end'))            : 0;

        $Model = new deliverymodel\LadingInfoModel;

        if(!empty($TimeStart)){
            $TimeStart = strtotime(str_replace('/', '-', $TimeStart.' 00:00:00'));
            $Model  = $Model::where('time_create','>=',$TimeStart);
        }else{
            echo 'time_create_start null';die;
        }

        if(!empty($TimeEnd)){
            $TimeEnd = strtotime(str_replace('/', '-', $TimeEnd.' 23:59:59'));
            $Model    = $Model->where('time_create','<',$TimeEnd);
        }

        $Model  = $Model->where('convert_scf1',0);
        $ModelCount = clone $Model;

        $DataInfo       = $Model->orderBy('id', 'ASC')->first();

        if(!empty($DataInfo)){
            $Data           = deliverymodel\LadingModel::where('id',(int)$DataInfo['lading_id'])->first();

            if(empty($Data)){
                $DataInfo->convert_scf1 = 2;
                $DataInfo->save();
                echo 'Lading not exists , sc_code = '.$DataInfo['sc_code'];die;
            }

            // COnfig courier
            $Courier = array(
                'vtp'   => 1,
                'vnp'   => 2,
                'ghn'   => 3,
                '123giao'   => 4,
                'netco'   => 5,
                'ghtk'  => 6,
                'sc'    => 7,
                'ems'   => 8,
                'gold'   => 9
            );

            $Status = array(
              'NEW_REQUEST'     => 20,
              'ACCEPT_REQUEST'  => 21,
              'STOCKING'        => 35,
              'REQUEST_DENIED'  => 56,
              'STOCKED'         => 36,
              'CANCEL'          => 22,
              'DELIVERED_RETURN'=> 66,
              'RETURN'          => 66,
              'RETURNING'       => 62,
              'DELIVERED'       => 52,
              'DELIVERING'      => 50,
              'DELIVERY_CANCEL' => 22,
              'DELIVER_STOCKED' => 50,
              'DELIVERY_PROBLEM'=> 54,
              'DELIVERY_ASSIGNED'=> 51
            );

            // get Stock
            $StockModel = new sellermodel\UserInventoryModel;
            $Stock = $StockModel->where('user_id',(int)$DataInfo['user_id'])
                                                    ->where('city_id',(int)$DataInfo['from_city_id'])
                                                    ->where('province_id',(int)$DataInfo['from_district_id'])
                                                    ->where('ward_id',(int)$DataInfo['from_ward_id'])
                                                    ->where('address','LIKE','%'.trim($DataInfo['from_address']).'%')
                                                    ->first();

            if(!empty($Stock)) {
                $Stock = (int)$Stock['id'];
            }else{
                $Stock = $StockModel::insertGetId(array(
                    'user_id'       => (int)$DataInfo['user_id'],
                    'name'          => '',
                    'user_name'     => trim($DataInfo['from_name']),
                    'phone'         => trim($DataInfo['from_phone']),
                    'city_id'       => (int)$DataInfo['from_city_id'],
                    'province_id'   => (int)$DataInfo['from_district_id'],
                    'ward_id'       => (int)$DataInfo['from_ward_id'],
                    'address'       => trim($DataInfo['from_address']),
                    'active'    => 1
                ));
            }

            // get order address
            $AddressModel   = new ordermodel\AddressModel;
            $OrderAddress   = $AddressModel->where('seller_id',(int)$DataInfo['user_id'])
                ->where('city_id',(int)$DataInfo['to_city_id'])
                ->where('province_id',(int)$DataInfo['to_district_id'])
                ->where('ward_id',(int)$DataInfo['to_ward_id'])
                ->first();

            if(!empty($OrderAddress)) {
                $OrderAddress = (int)$OrderAddress['id'];
            }else{
                $OrderAddress = $AddressModel::insertGetId(array(
                    'seller_id'       => (int)$DataInfo['user_id'],
                    'city_id'       => (int)$DataInfo['to_city_id'],
                    'province_id'   => (int)$DataInfo['to_district_id'],
                    'ward_id'       => (int)$DataInfo['from_ward_id'],
                    'address'       => trim($DataInfo['to_address']),
                    'time_update'    => $this->time()
                ));
            }

            //Update Order buyer
            $BuyerModel = new ordermodel\BuyerModel;
            $DataCreate = array(
                'seller_id'     => (int)$DataInfo['user_id'],
                'fullname'      => trim($DataInfo['to_name']),
                'phone'         => trim($DataInfo['to_phone']),
                'email'         => trim($DataInfo['to_email']),
                'address_id'    => (int)$OrderAddress
            );
            $DataBuyer          = $BuyerModel::firstOrCreate($DataCreate);


            // Update Order
            $OrderModel             = new ordermodel\OrdersModel;
            $DataCreate             = array('tracking_code' => $Data['sc_code']);
            $DataOrder              = $OrderModel::firstOrCreate($DataCreate);

            $DataOrder->service_id              =    (int)$DataInfo['service'];
            $DataOrder->courier_id              =    $Courier[trim($DataInfo['carrier'])];
            $DataOrder->courier_tracking_code   = trim($DataInfo['hvc_code']);

            $DataOrder->from_user_id        = (int)$DataInfo['user_id'];
            $DataOrder->to_buyer_id         = (int)$DataBuyer['id'];
            $DataOrder->to_name             = trim($DataInfo['to_name']);
            $DataOrder->to_phone            = trim($DataInfo['to_phone']);
            $DataOrder->to_email            = trim($DataInfo['to_email']);
            $DataOrder->from_address_id     = (int)$Stock;
            $DataOrder->from_city_id        = (int)$DataInfo['from_city_id'];
            $DataOrder->from_district_id    = (int)$DataInfo['from_district_id'];
            $DataOrder->from_ward_id        = (int)$DataInfo['from_ward_id'];
            $DataOrder->from_address        = trim($DataInfo['from_address']);
            $DataOrder->to_address_id       = (int)$OrderAddress;

            $DataOrder->total_weight            = trim($Data['item_weight']);
            $DataOrder->total_quantity          = trim($Data['item_quantity']);
            $DataOrder->total_amount            = trim($Data['item_price']);
            $DataOrder->status                  = (int)21;

            $DataOrder->time_create   = trim($DataInfo['time_create']);
            $DataOrder->time_update   = trim($DataInfo['time_update']);
            $DataOrder->time_accept   = trim($DataInfo['time_accept']);
            $DataOrder->time_success  = trim($DataInfo['time_success']);
            $DataOrder->time_approve  = trim($DataInfo['time_accept_hvc']);
            $DataOrder->time_pickup   = trim($DataInfo['time_pickup']);

            $DataOrder->verify_money_collect   = trim($DataInfo['time_ds']);
            $DataOrder->verify_fee             = trim($DataInfo['time_ds']);

            if(!($DataOrder->save())){
                $DataInfo->convert_scf1 = 3;
                $DataInfo->save();
                echo 'update user  fail';die;
            }

            // Update Order detail
            $DetailModel            = new ordermodel\DetailModel;
            $DataCreate             = array('order_id' => (int)$DataOrder['id']);
            $DataDetail             = $DetailModel::firstOrCreate($DataCreate);

            $DataDetail->sc_pvc              = trim($Data['sc_pvc']);
            $DataDetail->sc_cod              = trim($Data['sc_pcod']);
            $DataDetail->sc_pbh              = trim($Data['sc_pbh']);
            $DataDetail->sc_pvk              = trim($Data['sc_pvk']);
            $DataDetail->sc_pch              = trim($Data['sc_pch']);
            $DataDetail->sc_discount_pvc     = trim($Data['sc_discount']);
            $DataDetail->seller_pvc          = trim($Data['seller_pvc']);
            $DataDetail->seller_cod          = trim($Data['seller_pcod']);
            $DataDetail->seller_discount     = 0;
            $DataDetail->hvc_pvc             = trim($Data['hvc_pvc']);
            $DataDetail->hvc_cod             = trim($Data['hvc_pcod']);
            $DataDetail->hvc_pbh             = trim($Data['hvc_pbh']);
            $DataDetail->hvc_pvk             = 0;
            $DataDetail->hvc_pch             = trim($Data['hvc_pch']);
            $DataDetail->money_collect       = trim($Data['seller_collect_money']);

            if(!($DataDetail->save())){
                $DataInfo->convert_scf1 = 4;
                $DataInfo->save();
                echo 'update order detail fail';die;
            }

            //Update Item
            $ItemModel   = new ordermodel\ItemsModel;
            $OrderItem   = $ItemModel->where('seller_id',(int)$DataInfo['user_id'])
                ->where('price',(int)$Data['item_price'])
                ->where('weight',(int)$Data['item_weight'])
                ->where('name','LIKE','%'.trim($DataInfo['item_name']).'%')
                ->first();

            if(!empty($OrderItem)) {
                $OrderItem = (int)$OrderItem['id'];
            }else{
                $OrderItem = $ItemModel::insertGetId(array(
                    'seller_id'         => (int)$DataInfo['user_id'],
                    'price'             => (int)$Data['item_price'],
                    'weight'            => (int)$Data['item_weight'],
                    'name'              => trim($DataInfo['item_name']),
                    'time_update'    => $this->time()
                ));
            }

            // Update Order Item
            $OrderItemModel = new ordermodel\OrderItemModel;
            $DataCreate = array(
                'order_id'          => (int)$DataOrder['id'],
                'item_id'           => $OrderItem,
                'quantity'          => (int)$Data['item_quantity'],
                'description'       => trim($DataInfo['item_desc'])
            );
            $DataOrderItem          = $OrderItemModel::firstOrCreate($DataCreate);

            //Update journey
            $JourneyModel = new deliverymodel\JourneyModel;
            $DataJourney  = $JourneyModel->where('sc_code',$DataInfo['sc_code'])->get()->toArray();
            if(!empty($DataJourney)){
                $OrderStatus    = new ordermodel\StatusModel;
                $DataCreate = [];
                foreach($DataJourney as $val){
                    $DataCreate[]   = array(
                        'order_id'      => (int)$DataOrder['id'],
                        'status'        => (int)$Status[trim($val['status'])],
                        'city_name'     => trim($val['city_name']),
                        'note'          => trim($val['note']),
                        'time_create'   => trim($val['time_create'])
                    );
                }
                $Journey          = $OrderStatus::insert($DataCreate);
            }

            $DataInfo->convert_scf1 = 1;
            if(!($DataInfo->save())){
                $DataInfo->convert_scf1 = 5;
                $DataInfo->save();
                echo 'update convert_scf1 fail';die;
            }

            echo 'Còn '.$ModelCount->count().' vận đơn nữa';
        }else{
            echo 'Hết rồi !';die;
        }
    }

    function getStatus(){
        // COnfig courier
            $Courier = array(
                'vtp'   => 1,
                'vnp'   => 2,
                'ghn'   => 3,
                '123giao'   => 4,
                'netco'   => 5,
                'ghtk'  => 6,
                'sc'    => 7,
                'ems'   => 8,
                'gold'   => 9
            );

            $Status = array(
              'NEW_REQUEST'     => 20,
              'ACCEPT_REQUEST'  => 21,
              'STOCKING'        => 35,
              'REQUEST_DENIED'  => 56,
              'STOCKED'         => 36,
              'CANCEL'          => 22,
              'DELIVERED_RETURN'=> 63,
              'RETURN'          => 66,
              'RETURNING'       => 62,
              'DELIVERED'       => 52,
              'DELIVERING'      => 50,
              'DELIVERY_CANCEL' => 22,
              'DELIVER_STOCKED' => 50,
              'DELIVERY_PROBLEM'=> 54,
              'DELIVERY_ASSIGNED'=> 51
            );
            
            $StatusCourier = array(
                'vtp'   => array(
                            '100'   => 'STOCKING',
                            '101'   => 'CANCEL',
                            '102'   => 'CANCEL',
                            '103'   => 'STOCKING',
                            '104'   => 'STOCKING',
                            '105'   => 'STOCKED',
                            '106'   => 'DELIVERY_CANCEL',
                            '107'   => '',
                            '108'   => '',
                            '109'   => '',
                            '200'   => 'STOCKED',
                            '201'   => 'STOCKED',
                            '300'   => 'DELIVERING',
                            '301'   => 'DELIVERING',
                            '302'   => 'DELIVERING',
                            '303'   => 'DELIVERING',
                            '400'   => 'DELIVERING',
                            '401'   => 'DELIVERING',
                            '402'   => 'DELIVERING',
                            '403'   => 'DELIVERING',
                            '500'   => 'DELIVERY_ASSIGNED',
                            '501'   => 'DELIVERED',
                            '502'   => 'RETURNING',
                            '503'   => 'RETURNING',
                            '504'   => 'RETURN',
                            '505'   => 'DELIVERY_PROBLEM',
                            '506'   => 'DELIVERY_PROBLEM',
                            '507'   => 'DELIVER_STOCKED',
                            '508'   => 'DELIVERING',
                            '509'   => 'DELIVER_STOCKED',
                            '510'   => 'DELIVERY_PROBLEM',
                ),
                'vnp'   => array(
                            '10'   => 'DELIVERING',
                            '11'   => 'DELIVERING',
                            '12'   => 'DELIVERED',
                            '13'   => 'RETURN',
                            '14'   => 'DELIVERY_CANCEL'
                ),
                'ghn'   => array(
                            'ReadyToPick'   => 'STOCKING',
                            'Picking'       => 'STOCKING',
                            'Storing'       => 'STOCKED',
                            'Delivering'    => 'DELIVERING',
                            'Delivered'     => 'DELIVERED',
                            'Return'        => 'RETURNING',
                            'EndReturn'     => 'RETURN',
                            'Cancel'        => 'CANCEL',
                
                            'Draft'         => 'STOCKING',
                            'Finish'        => 'DELIVERED',
                            'WaitingToFinish'   => 'DELIVERED'
                ),
                '123giao'   => array(
                            1   => 'STOCKING',
                            2   => 'STOCKING',
                            3   => 'STOCKED',
                            4   => 'STOCKED',
                            5   => 'DELIVER_STOCKED',
                            6   => 'DELIVERING',
                            //7   => 'DELIVERING',
                            //8   => 'DELIVERED', // 'Hoàn tất'
                            9   => 'CANCEL',
                
                            11 => 'DELIVERED', //Đã giao hàng
                            12 => 'STOCKED', //Đã lấy hàng
                            13 => 'RETURN', //Đã chuyển hoàn
                            14 => 'DELIVERED_RETURN', //Chuyển hoàn 1 phần - Tiền thu hộ đã thu
                            15 => 'DELIVERING', //Đang vận chuyển
                            16 => 'RETURNING', //Đang chuyển hoàn
                
                            17 => 'DELIVERY_CANCEL', //"Hủy giao hàng",
                            18 => 'CANCEL', //"Hủy đơn hàng",
                            19 => 'DELIVERY_PROBLEM', //"Giao không thành công"
                ),
                'netco'   => array(
                            1 => 'STOCKED',
                            2 => 'DELIVERING',
                            3 => 'DELIVERED',
                            4 => 'STOCKED',
                            5 => 'RETURN',
                            6 => 'STOCKING',
                            7 => 'STOCKING',
                            8 => 'CANCEL'
                ),
                'ghtk'  => array(
                            '-1'=> 'CANCEL', //'Hủy đơn hàng'
                            0   => 'RETURNING',
                            1   => 'STOCKING', //'Chưa tiếp nhận'
                            2   => 'STOCKING', //'Đã tiếp nhận/Đang lấy hàng'
                            3   => 'STOCKED', //'Đã lấy hàng/Đã nhập kho'
                            4   => 'DELIVERING', //'Đã điều phối giao hàng/Đang giao hàng'
                            5   => 'DELIVERED', //'Đã giao hàng/Chưa đối soát'
                            7   => 'CANCEL', //'Không lấy được hàng'
                            8   => 'STOCKING', //'Delay lấy hàng'
                            9   => 'DELIVERY_PROBLEM',//'Không giao được hàng'
                            10  => 'DELIVER_STOCKED', //'Delay giao hàng'
                            11  => 'RETURN', //'Đã trả hàng',
                            12  => 'STOCKING'
                        
                        ),
                'ems'   => array(
                            "A" => 'DELIVERING',
                            "B" => 'DELIVER_STOCKED',          
                            "C" => 'DELIVERY_ASSIGNED',
                            "I" => 'DELIVERED',
                            "H" => 'DELIVERY_PROBLEM',
                            "T" => 'RETURNING',
                ),
                'gold'   => array(
                            '100'   => 'STOCKING',
                            '101'   => 'CANCEL',
                            '102'   => 'CANCEL',
                            '103'   => 'STOCKING',
                            '104'   => 'STOCKING',
                            '105'   => 'STOCKED',
                            '106'   => 'DELIVERY_CANCEL',
                            //'107'   => '',
                            //'108'   => '',
                            '109'   => 'RETURNING',
                            '200'   => 'STOCKED',
                            '201'   => 'STOCKED',
                            '300'   => 'DELIVERING',
                            '301'   => 'DELIVERING',
                            '302'   => 'DELIVERING',
                            '303'   => 'DELIVERING',
                            '400'   => 'DELIVERING',
                            '401'   => 'DELIVERING',
                            '402'   => 'DELIVERING',
                            '403'   => 'DELIVERING',
                            '500'   => 'DELIVERY_ASSIGNED',
                            '501'   => 'DELIVERED',
                            '502'   => 'RETURNING',
                            '503'   => 'RETURNING',
                            '504'   => 'RETURN',
                            '505'   => 'DELIVERY_PROBLEM',
                            '506'   => 'DELIVERY_PROBLEM',
                            '507'   => 'DELIVER_STOCKED',
                            '508'   => 'DELIVERING',
                            '509'   => 'DELIVER_STOCKED',
                            '510'   => 'DELIVERY_PROBLEM',
                    )
            );
            
            // Proccess
            foreach($StatusCourier as $courier => $arrStatus){
                foreach($arrStatus as $id => $status){
                    $insert[] = array(
                        'courier_id'   => $Courier[$courier],
                        'courier_status' => $id,
                        'sc_old_status' => $status,
                        'sc_status'     => isset($Status[$status]) ? $Status[$status] : '',
                        'active'        => 1
                    ); 
                }
            }
            
            //DB::connection('courierdb')->table('courier_status')->insert($insert);
            
            print_r($insert);
    }

    public function getCroncashin(){
        $MerchantModel      = new \accountingmodel\MerchantModel;
        $CashInAcc          = new \accountingmodel\CashInModel;
        $TransactionModel   = new \accountingmodel\TransactionModel;

        $Model      = new \sellermodel\CashInModel;
        $CashIn     = $Model->where('status','PROCESSING')
            ->where('time_create','>=',$this->time() - $this->time_limit)
            ->orderBy('time_create', 'ASC')
            ->first();

        if(!empty($CashIn)){
            DB::connection('accdb')->beginTransaction();
            $Merchant = $MerchantModel::firstOrNew(array('merchant_id' => (int)$CashIn->user_id));
            if(!isset($Merchant->balance)){
                $Merchant->balance      = 0;
                $Merchant->country_id   = 237;
            }

            //GetMasterId
            $Master = $this->getMasterId($Merchant->country_id);
            if(!isset($Master->id)){
                return Response::json(['error' => true, 'message' => 'MASTERID NOT EXISTS','country_id' => $Merchant->country_id]);
            }

            try{
                $TransactionInsert = [
                    'country_id'        => $Merchant->country_id,
                    'type'              => 1,
                    'refer_code'        => $CashIn->refer_code,
                    'transaction_id'    => $CashIn->transaction_id,
                    'from_user_id'      => $Master->merchant_id,
                    'to_user_id'        => (int)$CashIn->user_id,
                    'money'             => $CashIn->amount,
                    'balance_before'    => $Merchant->balance,
                    'note'              => 'Nạp tiền phí vận chuyển',
                    'time_create'       => $this->time()
                ];
                $TransactionModel::insert($TransactionInsert);
            }catch (Exception $e){
                return Response::json($TransactionInsert);
            }

            try{
                $Merchant->balance = $Merchant->balance + $CashIn->amount;
                $Merchant->save();
            }catch (Exception $e){
                return Response::json(['error' => true, 'message' => 'UPDATE_BALANCE_FALSE','cash_id' => $CashIn->id]);
            }

            try{
                $MerchantModel->where('merchant_id', (int)$Master->merchant_id)->decrement('balance', $CashIn->amount);
            }catch (Exception $e){
                return Response::json(['error' => true, 'message' => 'UPDATE_BALANCE_MASTER_FALSE','cash_id' => $CashIn->id]);
            }

            try{
                $CashInAcc::insert([
                    'merchant_id'       => (int)$CashIn->user_id,
                    'country_id'        => $Merchant->country_id,
                    'amount'            => $CashIn->amount,
                    'refer_code'        => $CashIn->refer_code,
                    'transaction_id'    => $CashIn->transaction_id,
                    'reason'            => 'Nạp tiền phí vận chuyển',
                    'time_accept'       => $this->time(),
                    'status'            => 'SUCCESS'
                ]);
            }catch (Exception $e){
                return Response::json(['error' => true, 'message' => 'INSERT_CASH_IN_FALSE','cash_id' => $CashIn->id, 'error_message' => $e->getMessage()]);
            }

            try {
                $CashIn->status         = 'SUCCESS';
                $CashIn->time_success   = $this->time();
                $CashIn->save();
                DB::connection('accdb')->commit();
                $contents = array(
                    'error'     => false,
                    'message'   => 'SUCCESS',
                    'amount'    => $CashIn->amount,
                    'type'      => $CashIn->type

                );

            } catch (Exception $e) {
                DB::connection('accdb')->rollBack();
                $contents = array(
                    'error'     => true,
                    'message'   => 'UPDATE_FALSE',
                    'amount'    => $CashIn->amount
                );
            }

            return Response::json($contents);
        }else{
            return Response::json(['error' => false, 'message' => 'EMPTY']);
        }
    }

    public function getConvertds(){return 1;
        $Max    = Input::has('max')   ? (int)Input::get('max')          : 0; // time_create start   time_stamp
        $Min    = Input::has('min')   ? (int)Input::get('min')          : 0;

        if(empty($Max) || empty($Min)){
            return Response::json(['error' => true, 'message' => 'ERROR_MAX']);
        }


        $UserModel          = new  User;
        $TransactionModel   = new deliverymodel\TransactionModel;
        $VerifyModel        = new ordermodel\VerifyModel;
        $OrderModel         = new ordermodel\OrdersModel;

        $User = $UserModel::where('id','>=',$Min)->where('id','<',$Max)->where('convert',1)->orderBy('id', 'ASC')->first();

        if(empty($User)){
            return Response::json(['error' => true, 'message' => 'EMPTY']);
        }

        $Transaction    = $TransactionModel
                                ->where(function($query) use($User){
                                    $query->where('type','PAY_PVC')
                                        ->where('from_user_id',$User->id)
                                        ->where('to_user_id',4);
                                })->orWhere(function($query) use ($User){
                                    $query->where('type','PAY_COD')
                                        ->where('from_user_id',4)
                                        ->where('to_user_id',$User->id);
                                })->orderBy('time_create', 'ASC')
                                    ->get(array('time_create', 'type', 'from_user_id', 'to_user_id', 'refer_code', 'money'));

        if(empty($Transaction)){
            return Response::json(['error' => false, 'message' => 'NULL']);
        }

        $ListInsert = [];
        foreach($Transaction as $val){
            $TimeCreate = strtotime(date("Y-m-d", $val['time_create']) . ' 00:00:00');
            if($val['type'] == 'PAY_PVC'){
                $ListTransaction[$TimeCreate][] = trim($val['refer_code']);
            }

            if(!isset($ListInsert[$TimeCreate])){
                $ListInsert[$TimeCreate]   = [
                    'user_id'               => $User->id,
                    'accept_id'             => 2,
                    'total_money_collect'   => 0,
                    'total_fee'             => 0,
                    'time_create'           => $TimeCreate,
                    'time_accept'           => $val['time_create'],
                    'status'                => 'SUCCESS'
                ];
            }

            if($val['type'] == 'PAY_COD'){
                $ListInsert[$TimeCreate]['total_money_collect'] += $val['money'];
            }

            if($val['type'] == 'PAY_PVC'){
                $ListInsert[$TimeCreate]['total_fee'] += $val['money'];
            }
        }

        DB::connection('orderdb')->beginTransaction();

        foreach($ListInsert as $val){
            try{
                $Verify = $VerifyModel->insertGetId($val);
                if(isset($ListTransaction[$val['time_create']]) && !empty($ListTransaction[$val['time_create']])){
                    $OrderModel->whereIn('tracking_code', $ListTransaction[$val['time_create']])
                               ->update(array('verify_id' => $Verify));
                }
            }catch(Exception $e){
                DB::connection('orderdb')->rollBack();
                return Response::json(['error' => true, 'message' => 'UPDATE_VERIFY_FAIL']);
            }
        }

        try{
            $User->convert = 2;
            $User->save();
            DB::connection('orderdb')->commit();
        }catch(Exception $e){
            DB::connection('orderdb')->rollBack();
            return Response::json(['error' => true, 'message' => 'UPDATE_USER_FAIL']);
        }

        return Response::json(['error' => false, 'message' => 'SUCCESS']);

    }

    public function getCreateverify($type = 0){
        $TimeStart = Input::has('time_start') ? trim(Input::get('time_start')) : '';
        $TimeEnd = Input::has('time_end') ? trim(Input::get('time_end')) : '';

        if (empty($TimeStart) || empty($TimeEnd)) {
            return Response::json(['error' => true, 'message' => 'EMPTY_TIME_START_OR_TIME_END']);
        }

        $TimeStart = strtotime(str_replace('/', '-', $TimeStart . ' 00:00:00'));
        $TimeEnd = strtotime(str_replace('/', '-', $TimeEnd . ' 23:59:59'));

        if ($TimeEnd - $TimeStart > $this->time_limit) {
            //return Response::json(['error' => true, 'message' => 'TIME_INTERVAL_ERROR']);
        }

        // Get List User
        $UserModel  = new loyaltymodel\UserModel;
        $ListNotUser = [];
        $ListUser    = [];
        if($type == 0){// Khách hàng thường
            $ListNotUser    = $UserModel::where('level','>',0)->lists('user_id');
        }elseif($type == 1){ // khách bạc
            $ListUser       = $UserModel::where('level',1)->lists('user_id');
            if(empty($ListUser)){
                return Response::json(['error' => false, 'message' => 'EMPTY']);
            }
        }else{// Khách vàng
            $ListUser       = $UserModel::where('level',2)->lists('user_id');
            if(empty($ListUser)){
                return Response::json(['error' => false, 'message' => 'EMPTY']);
            }
        }

        // Check exist
        $VerifyModel    = new ordermodel\VerifyModel;
        if($VerifyModel::where('time_create','>',$this->time() - 604800)->where('status','INSERT')->count() > 0){
            //return Response::json(['error' => false, 'message' => 'EXISTS']);
        }

        $OrdersModel = accountingmodel\OrdersModel::where('time_accept','>=',$this->time() - $this->time_limit)
                                                  ->where('time_success', '>=', $TimeStart)
                                                  ->where('time_success', '<=', $TimeEnd)
                                                  ->where('verify_id', 0)
                                                  ->whereIn('status',[52,53,66,67]);

        if(!empty($ListNotUser)){
            $OrdersModel    = $OrdersModel->whereNotIn('from_user_id',$ListNotUser);
        }

        if(!empty($ListUser)){
            $OrdersModel    = $OrdersModel->whereIn('from_user_id',$ListUser);
        }

        $Order = $OrdersModel->groupBy('from_user_id')
                             ->get(array('from_user_id','time_accept', 'time_success', 'verify_id', 'status'))->toArray();

        $DataInsert = [];

        if(!empty($Order)){
            foreach($Order as $val){
                $DataInsert[]   = [
                    'user_id'       => (int)$val['from_user_id'],
                    'time_create'   => $this->time(),
                    'type_payment'  => 1,
                    'time_start'    => $TimeStart,
                    'time_end'      => $TimeEnd,
                    'status'        => 'INSERT'
                ];
            }

            DB::connection('orderdb')->beginTransaction();
            try{
                ordermodel\VerifyModel::insert($DataInsert);

            DB::connection('orderdb')->commit();
            }catch(Exception $e){
                return Response::json(['error' => true, 'message' => 'INSERT_ERROR']);
            }

            return Response::json(['error' => false, 'message' => 'SUCCESS']);
        }
        return Response::json(['error' => false, 'message' => 'EMPTY']);
    }

    public function getVerifyorder()
    {   $this->LogQuery();
        $VerifyModel    = new accountingmodel\VerifyModel;
        $Verify         = $VerifyModel::where('status','INSERT')->orderBy('id','ASC')->first();

        if(empty($Verify)){
            return Response::json(['error' => false, 'message' => 'EMPTY']);
        }

        /*
         * get user info
         */
        $UserInfoModel  = new sellermodel\UserInfoModel;
        $UserInfo       = $UserInfoModel::where('user_id',(int)$Verify->user_id)->first();
        if(empty($UserInfo)){
            return Response::json(['error' => false, 'message' => 'USER_INFO_NOT_EXISTS', 'user_id' => (int)$Verify->user_id]);
        }

        //Update Verify Order
        $OrdersModel = new accountingmodel\OrdersModel;
        try{
            $OrdersModel
                ->where('from_user_id',(int)$Verify->user_id)
                ->where('time_accept','>=',time() - $this->time_limit)
                ->where('time_success', '>=', $Verify->time_start)
                ->where('time_success', '<=', $Verify->time_end)
                ->where('verify_id', 0)
                ->whereIn('status',[52,53,66,67])
                ->update(['verify_id' => (int)$Verify->id]);
        }catch(Exception $e){
            return Response::json(['error' => true, 'message' => 'UPDATE_ORDER_FAIL', 'user_id' => (int)$Verify->user_id]);
        }

        // Update freeze
        $AppController  = new AppController;
        $Freeze         = $AppController->CountFeeze($Verify->user_id, $Verify->id);
        if($Freeze['error']){
            return Response::json(['error' => false, 'message' => 'UPDATE_FEEZE_FAIL']);
        }

        DB::connection('orderdb')->beginTransaction();

        // Insert Verify Freeze
        if(!empty($Freeze['data'])){
            $i = 0;
            $ListInsert = [];
            foreach($Freeze['data'] as $val) {
                $i++;
                $ListInsert[$i % 10][] = $val;
            }

            unset($Freeze);

            try{
                foreach($ListInsert as $val){
                    $VerifyFreezeModel  = new ordermodel\VerifyFreezeModel;
                    $VerifyFreezeModel::insert($val);
                }
            }catch (Exception $e){
                return Response::json(['error' => false, 'message' => 'INSERT_FREEZE_FAIL', 'user_id' => (int)$Verify->user_id]);
            }
        }

        $MerchantModel  = new accountingmodel\MerchantModel;
        $Merchant       = $MerchantModel::where('merchant_id',(int)$Verify->user_id)->first();
        if(!isset($Merchant->id)){
            return Response::json(['error' => false, 'message' => 'MERCHANT_NOT_EXISTS', 'user_id' => (int)$Verify->user_id]);
        }

        if($UserInfo->priority_payment == 1){
            $VimoModel  = new sellermodel\VimoModel;
            $ConfigBank  = $VimoModel::where('user_id', (int)$Verify->user_id)->where('active',1)->first();
            if(!isset($ConfigBank->id)){
                $Verify->account                = $UserInfo->email_nl;
                $Verify->acc_number             = $UserInfo->user_nl_id;
            }else{
                // Vimo
                $Verify->type_payment           = 2;
                $Verify->account                = $ConfigBank->bank_code;
                $Verify->acc_name               = $ConfigBank->account_name;
                $Verify->acc_number             = $ConfigBank->account_number;
            }
        }else{
            $Verify->account                = $UserInfo->email_nl;
            $Verify->acc_number             = $UserInfo->user_nl_id;
        }

        $Verify->total_fee              = 0;
        $Verify->total_money_collect    = 0;
        $Verify->config_balance         = (int)$UserInfo->freeze_money;
        $Verify->balance                = (int)$Merchant->balance;
        $Verify->balance_available      = $Merchant->provisional - $Merchant->freeze;
        if($Merchant->level == 3){
            $Verify->balance_available  += $Merchant->freeze;
        }

        $Verify->status                 = 'WAITING';

        // Get Order
        $OrdersModel = new accountingmodel\OrdersModel;
        $ListOrder   = $OrdersModel::
            where('time_accept','>=',$this->time() - $this->time_limit)
            ->where('verify_id', (int)$Verify->id)
            ->with(['OrderDetail','OrderFulfillment'])
            ->get(['id','time_accept','status'])->toArray();

        if(empty($ListOrder)){
            return Response::json(['error' => false, 'message' => 'LIST_ORDER_EMPTY', 'user_id' => (int)$Verify->user_id]);
        }

        foreach($ListOrder as $val){
            try{
                if($val['status'] == 66){
                    $Verify->total_fee              += $val['order_detail']['sc_pvc'] - $val['order_detail']['sc_discount_pvc']  + $val['order_detail']['sc_pvk'] + $val['order_detail']['sc_pch'] + $val['order_detail']['sc_remote'] + $val['order_detail']['sc_clearance'];
                }elseif($val['status'] == 67){
                    $Verify->total_fee              += $val['order_detail']['sc_pvc'] - $val['order_detail']['sc_discount_pvc']  + $val['order_detail']['sc_pvk'] + $val['order_detail']['sc_remote'] + $val['order_detail']['sc_clearance'];
                }else{
                    $Verify->total_fee              += $val['order_detail']['sc_pvc'] - $val['order_detail']['sc_discount_pvc'] + $val['order_detail']['sc_cod'] - $val['order_detail']['sc_discount_cod'] + $val['order_detail']['sc_pbh'] + $val['order_detail']['sc_pvk'] + $val['order_detail']['sc_remote'] + $val['order_detail']['sc_clearance'];
                    $Verify->total_money_collect    += $val['order_detail']['money_collect'];
                }

                if(!empty($val['order_fulfillment'])){
                    $Verify->total_fee              += $val['order_fulfillment']['sc_plk'] + $val['order_fulfillment']['sc_pdg'] + $val['order_fulfillment']['sc_pxl']
                                                        - $val['order_fulfillment']['sc_discount_plk'] - $val['order_fulfillment']['sc_discount_pdg'] - $val['order_fulfillment']['sc_discount_pxl'];
                }
            }catch(Exception $e){
                DB::connection('orderdb')->rollBack();
                return Response::json(['error' => true, 'message' => 'UPDATE_VERIFY_FAIL', 'user_id' => (int)$Verify->user_id]);
            }
        }

        //Check lvl 4  => không đối trừ phí khi thu hộ, ko tạm giữ
        if($Merchant->level == 4){
            $Verify->total_fee          = 0;
            $Verify->balance_available  = 0;
        }

        try{
            $Verify->save();
        }catch (Exception $e){
            DB::connection('orderdb')->rollBack();
            return Response::json(['error' => true, 'message' => 'UPDATE_VERIFY_FAIL', 'user_id' => (int)$Verify->user_id]);
        }

        DB::connection('orderdb')->commit();
        return Response::json(['error' => true, 'message' => 'CONTINUES']);
    }

    private function __handling_fee($TotalItem, $Stock){
        $Data = ['price' => 0, 'discount' => 0];
        foreach($Stock as $val){
            if($val['unit_start'] <= $TotalItem && $val['unit_end'] >= $TotalItem){
                $Data['price']      = (int)$val['price'];
                $Data['discount']   = (int)$val['discount'];
            }
        }

        return $Data;
    }

    private function __warehouse_fee($TotalItem, $hours,$Stock){
        $Data = ['price' => 0, 'discount' => 0];
        foreach($Stock as $val){
            if($val['unit_start'] <= $TotalItem && $val['unit_end'] >= $TotalItem){
                $Data['price']      = (int)$val['price']*$hours;
                $Data['discount']   = (int)$val['discount']*$hours;
            }
        }

        return $Data;
    }

    private function __calculate_discount_warehouse($Stock, $ProviderStock, $UId, $ListUId, $LastTime, $TotalItem){
        $Data   = [
                    'total_time'                => 0,
                    'total_fee'                 => 0,
                    'total_discount'            => 0,
                    'provider_total_fee'        => 0,
                    'provider_total_discount'   => 0,
                    'list'                      => $UId
                ];

        //Check có log lưu kho theo khoang kệ
        $CheckLog   = fulfillmentmodel\HistoryItemModel::whereRaw("uid in ('". implode("','", $ListUId) ."')")
            ->where('type_payment','>',0)
            ->where('time_end','<=',strtotime($LastTime))
            ->count();

        foreach($UId as $key => $val){
            $TimeStock          = ceil(($val['time_packge'] - $val['time_stock'])/3600);

            $WareHouseFee           = $this->__warehouse_fee($TotalItem, $TimeStock, $Stock);
            $PartnerWareHouseFee    = $this->__warehouse_fee($TotalItem, $TimeStock, $ProviderStock);

            $Data['total_fee']          += $WareHouseFee['price'];
            $Data['provider_total_fee'] += $PartnerWareHouseFee['price'];

            $Data['total_time'] += $TimeStock;
            if(!$val['return']){//chưa hoàn
                //Check thưởng khuyến mãi cho khách 2 tháng từ khi nhập kho
                $TimeStock  = ($TimeStock > 1440) ? 1440 : $TimeStock;

                //Check thưởng tết
                $LastTime   = ($val['time_packge'] > 1455444000) ? 1455444000 : $val['time_packge'];
                if($val['time_stock'] <= 1454580000){ // thời gian nhập kho nhỏ hơn 5/2/2016
                    if($val['time_packge'] > 1454580000){// thời gian đóng gói > 5/2/2016
                        $TimeStock  += ceil(($LastTime - 1454580000)/3600);
                    }
                }elseif(($val['time_stock'] > 1454580000) && ($val['time_stock'] <= 1455444000)){
                    // nếu time nhập kho lớn hơn 5/2 và time nhập kho nhỏ hơn 15/2
                    $TimeStock  += ceil(($LastTime - $val['time_stock'])/3600);
                }

                //Get lịch sử lưu kho theo khoang kệ
                if($CheckLog > 0){
                    $LogUId = fulfillmentmodel\HistoryItemModel::where('uid',$val['uid'])
                        ->where('type_payment','>',0)
                        ->whereRaw('time_end > time_start')
                        ->where('time_start','>=',$val['time_stock'])
                        ->where('time_end','<=',$val['time_packge'])
                        ->get(array(DB::raw('sum(time_end - time_start) as total_time')));
                    if(!empty($LogUId)){
                        foreach($LogUId as $val){
                            $TimeStock += $val['total_time'];
                        }
                    }
                }
            }

            $_discount              = $this->__warehouse_fee($TotalItem, $TimeStock,$Stock);
            $_discount              = ($_discount > $WareHouseFee['price']) ? $WareHouseFee['price'] : $_discount;
            $_provider_discount     = $this->__warehouse_fee($TotalItem, $TimeStock,$ProviderStock);
            $_provider_discount     = ($_provider_discount > $PartnerWareHouseFee['price']) ? $PartnerWareHouseFee['price'] : $_provider_discount;

            $UId[$key]['discount_warehouse_fee']            = $_discount;
            $UId[$key]['warehouse_fee']                     = $WareHouseFee['price'];
            $UId[$key]['historical_discount_warehouse']     = $_provider_discount;
            $UId[$key]['historical_warehouse_fee']          = $PartnerWareHouseFee['price'];

            $Data['total_discount']                 += $_discount;
            $Data['provider_total_discount']        += $_provider_discount;
        }

        $Data['list']   = $UId;

        return $Data;
    }

    public function getUpdatereturn(){
        $OrdersModel = new ordermodel\OrdersModel;
        $Order = $OrdersModel::where('time_accept','>=',time() - 86400*123)
            ->where('time_success', '>=', 1480525200)
            ->where(function($query){
                $query->where(function($q){
                    $q->where('status',66);
                })->orWhere(function($q){
                    $q->where('domain','boxme.vn')->whereIn('status',[52,53,67]);
                });
            })
            ->where('verify_id', 0)
            ->where('verify_return',0)
            //->where('domain','boxme.vn')
            ->orderBy('time_accept','ASC')
            ->with('ToOrderAddress')
            ->first([
                'id', 'tracking_code','order_code','from_user_id','total_weight','time_create','time_accept',
                'time_success', 'verify_id', 'status', 'domain', 'to_address_id', 'to_district_id', 'from_city_id',
                'warehouse','verify_return']);

        if(!isset($Order->id)){
            return Response::json(['error' => false, 'message' => 'EMPTY']);
        }

        //Check Status
        if($Order->status == 66){
            //Hoàn thành công => tính phí chuyển hoàn
            $DetailModel            = new ordermodel\DetailModel;
            $AreaLocationModel      = new AreaLocationModel;

            $ListArea               = CourierAreaModel::where('courier_id',7)->lists('id');
            if(empty($ListArea)){
                return Response::json(['error' => true, 'message' => 'LIST_AREA_7_EMPTY']);
            }

            //get location
            $ListAreaLocation   = $AreaLocationModel::where('active',1)->whereIn('area_id',$ListArea)->where('province_id', $Order->to_district_id)->first(['id', 'location_id']);

            $Detail = $DetailModel->where('order_id',(int)$Order->id)->first();
            if($Order->domain != 'chodientu.vn' && (($Order->from_city_id !=  $Order->to_order_address->city_id) ||
                    ($Order->from_city_id ==  $Order->to_order_address->city_id &&
                        (!isset($ListAreaLocation->location_id) || !in_array($ListAreaLocation->location_id,[1,2]))))){

                $Detail->sc_pch = ($Detail->sc_pvc + $Detail->sc_pvk)*50/100;
            }else{
                $Detail->sc_pch = 0;
            }

            if($Order->domain == 'boxme.vn'){
                $Detail->sc_pch = 0;
            }

            try{
                $Detail->save();
            }catch(Exception $e){
                return Response::json(['error' => true, 'message' => 'UPDATE_ORDER_DETAIL_FAIL', 'order' => $Order]);
            }
        }

        if($Order->domain == 'boxme.vn'){
            //Tính phí luu kho, phi xu ly, phi dong goi
            $Package  = warehousemodel\PackageModel::where('tracking_code', $Order->tracking_code)
                ->first(['id','tracking_code','order_number','size','create']);

            //Nếu có trong bảng package => đơn xuất kho, thời gian đóng gói lớn hơn 1/12 => tính phí
            if(isset($Package->id) && (strtotime($Package->create) >= 1480525200 || in_array($Order->from_user_id, [102556,111087]))){
                $ListItem = warehousemodel\PackageItemModel::where('tracking_code', $Order->tracking_code)
                                                           ->orderBy('time_packge','ASC')
                                                           ->get(['id','tracking_code','order_number','uid','time_stock','time_packge'])
                                                           ->toArray();

                if(!empty($ListItem)){
                    //Cấu hình phí cho khách
                    $BaseCtrl           = new BaseCtrl;
//                    Phí đóng gói
                    $Packing            = $BaseCtrl->getBmSellerPacking(false);
//                    Lấy bảng giá phí xử lý, phí lưu kho
                    $Stock              = $BaseCtrl->getBmSellerStock(false);
                    $WareHouse          = $BaseCtrl->getWareHouseBoxme(false);

                    $package_fee                        = 0;
                    $discount_package                   = 0;
                    $handling_fee                       = 0;
                    $discount_handling_fee              = 0;

                    $provider_package_fee               = 0;
                    $provider_discount_package_fee      = 0;
                    $provider_handling_fee              = 0;
                    $provider_discount_handling_fee     = 0;


                    //Cấu hình phí cho nhà cung cấp
                    $Provider           = $WareHouse[strtoupper($Order->warehouse)]['courier_id'];
                    if(empty($Provider)){
                        return Response::json(['error' => true, 'message' => 'PROVIDER_ERROR', 'order' => $Order]);
                    }

                    Input::merge(['courier' => $Provider]);
                    $StockProvider        = $BaseCtrl->getBmSellerStock(false);
                    $PackingProvider      = $BaseCtrl->getBmSellerPacking(false);
                    Input::merge(['courier' => 0]);


                    //Phí đóng gói Check theo cấu hình riêng của khách hàng
                    Input::merge(['user_id' => $Order->from_user_id,'time' => strtotime($Package->create)]);
                    $PackingMerchant    = $BaseCtrl->getPackingByWeight(false);
                    if(empty($PackingMerchant)){
                        $PackingMerchantSize = $BaseCtrl->getPackingByUser(false);
                    }

                    // Tính phí đóng gói cho khách hàng
                    if(isset($Package->size)){
                        $Package->size  = strtolower(trim($Package->size));

                        if(!empty($PackingMerchant)){
                            //Có cấu hình tính phí đóng gói theo khối lượng
                            foreach($PackingMerchant as $val){
                                if($val['min_weight'] < $Order->total_weight && $Order->total_weight <= $val['max-weight']){
                                    $package_fee        = (int)$val['price'];
                                    $discount_package   = (int)$val['discount'];
                                }
                            }
                        }elseif(isset($PackingMerchantSize) && !empty($PackingMerchantSize)){
                            foreach($PackingMerchantSize as $val){
                                $val['volume_limit'] = strtolower(trim($val['volume_limit']));
                                if($val['volume_limit'] == $Package->size){
                                    $package_fee        = (int)$val['price'];
                                    $discount_package   = (int)$val['discount'];
                                }
                            }
                        }else{
                            $package_fee        = (int)$Packing[$Package->size]['price'];
                            $discount_package   = (int)$Packing[$Package->size]['discount'];
                        }

//                        Phí lưu kho cho nhà cung cấp
                        $provider_package_fee           = (int)$PackingProvider[$Package->size]['price'];
                        $provider_discount_package_fee  = (int)$PackingProvider[$Package->size]['discount'];
                    }

                    //Kiểm tra cấu hình riêng của khách
                    $StockMerchant      = $BaseCtrl->getStockByUser(false);
                    if(!empty($StockMerchant)){
                        $Stock  = [];
                        foreach($StockMerchant as $val){
                            $Stock[$val['code']][]  = $val;
                        }
                    }

                    $ListUId        = [];
                    $UID            = [];
                    $MaxTimePackage = 0;


                    $total_item     = count($ListItem);

                    //Tính phí xử lý
//                    Khách hàng
                    $HandlingFee            = $this->__handling_fee($total_item, $Stock[3]);
//                    Nhà cung cấp
                    $ProviderHandlingFee    = $this->__handling_fee($total_item, $StockProvider[3]);

                    $handling_fee               = $HandlingFee['price']*$total_item;
                    $discount_handling_fee      = $HandlingFee['discount']*$total_item;

                    $provider_handling_fee              = $ProviderHandlingFee['price']*$total_item;
                    $provider_discount_handling_fee     = $ProviderHandlingFee['discount']*$total_item;


                    foreach($ListItem as $val){
                        $val['uid']         = trim(strtoupper($val['uid']));
                        $val['time_packge'] = strtotime($val['time_packge']);
                        $val['time_stock']  = strtotime($val['time_stock']);

                        //Check Lỗi
                        if($val['time_packge'] <= 0 || $val['time_stock'] <= 0){
                            return Response::json(['error' => true, 'message' => 'TIME_ERROR', 'item' => $val]);
                        }

                        if($val['time_packge'] < $val['time_stock']){
                            return Response::json(['error' => true, 'message' => 'TIME_PACKAGE_ERROR', 'item' => $val]);
                        }

                        $ListUId[]  = $val['uid'];
                        $UID[$val['uid']]   = [
                            'uid'                           => $val['uid'],
                            'time_stock'                    => $val['time_stock'],
                            'time_packge'                   => $val['time_packge'],
                            'handling_fee'                  => $HandlingFee['price'],
                            'discount_handling_fee'         => $HandlingFee['discount'],
                            'warehouse_fee'                 => 0,
                            'discount_warehouse_fee'        => 0,
                            'historical_handling_fee'       => $ProviderHandlingFee['price'],
                            'historical_discount_handling'  => $ProviderHandlingFee['discount'],
                            'historical_warehouse_fee'      => 0,
                            'historical_discount_warehouse' => 0,
                            'return'                        => false
                        ];

                        if(!isset($val['time_packge'])){
                            return $ListItem;
                        }
                        $MaxTimePackage          = $val['time_packge'];
                    }


                    //Tính phí lưu kho
                    //Kiểm tra thời điểm đóng gói khách hàng đang lưu kho theo hình thức gì
                    $Merchant = sellermodel\UserWMSTypeModel::where('user_id', $Order->from_user_id)
                        ->where('start_date','<=', $MaxTimePackage)
                        ->where(function($query) use($MaxTimePackage){
                            $query->where('end_date','>=',$MaxTimePackage)
                                ->orWhere('end_date',0);
                        })->first();
                    if(!isset($Merchant->id) || ($Merchant->wms_type == 0)){//Lưu kho theo sản phẩm
                        //Lấy danh sách hoàn
                        $ListReturn = warehousemodel\ReturnItemModel::whereIn('uid', $ListUId)
                            ->where('created','<',$MaxTimePackage)
                            ->orderBy('created','ASC')
                            ->get(['id','uid','created','updated'])->toArray();
                        if(!empty($ListReturn)){
                            foreach($ListReturn as $val){
                                $val['uid']         = trim(strtoupper($val['uid']));
                                $val['created']     = strtotime($val['created']);
                                // Cho kho 1 giờ cập nhật hoàn, dm kho
                                if(($UID[$val['uid']]['time_stock'] >= ($val['created']) - 3600)){
                                    $UID[$val['uid']]['time_stock'] = strtotime($val['updated']);
                                    $UID[$val['uid']]['return']     = true;
                                }
                            }
                        }

                        //Check Discount
                        if(!empty($StockMerchant)){
                            $Data   = ['total_time' => 0, 'total_fee'  => 0, 'total_discount' => 0, 'provider_total_fee' => 0, 'provider_total_discount' => 0, 'list' => $UID];

                            foreach($UID as $key => $val){
                                $TimeStock                              = ceil(($val['time_packge'] - $val['time_stock'])/3600);
                                $WareHouseFee                           = $this->__warehouse_fee($total_item, $TimeStock, $Stock[1]);
                                $UID[$key]['warehouse_fee']             = $WareHouseFee['price'];
                                $UID[$key]['discount_warehouse_fee']    = $WareHouseFee['discount'];

                                $WareHouseProviderFee                           = $this->__warehouse_fee($total_item, $TimeStock, $StockProvider[1]);
                                $UID[$key]['historical_warehouse_fee']          = $WareHouseProviderFee['price'];
                                $UID[$key]['historical_discount_warehouse']     = $WareHouseProviderFee['discount'];

                                $Data['total_fee']          += $WareHouseFee['price'];
                                $Data['total_discount']     += $WareHouseFee['discount'];
                                $Data['total_time']         += $TimeStock;

                                $Data['provider_total_fee']             += $WareHouseProviderFee['price'];
                                $Data['provider_total_discount']        += $WareHouseProviderFee['discount'];
                            }

                            $Data['list']   = $UID;
                        }else{
                            $Data   = $this->__calculate_discount_warehouse($Stock[1], $StockProvider[1], $UID, $ListUId, $MaxTimePackage, $total_item);
                        }
                    }else{
                        $Data   = ['total_time' => 0, 'total_fee'  => 0, 'total_discount' => 0, 'provider_total_fee' => 0, 'provider_total_discount' => 0, 'list' => $UID];
                    }


                    try{
                        $InsertId = ordermodel\FulfillmentModel::insertGetId ([
                            'order_id'          => $Order->id,
                            'tracking_code'     => $Order->tracking_code,
                            'time_stock'        => $Data['total_time'],
                            'size'              => $Package->size,
                            'sc_plk'            => $Data['total_fee'],
                            'sc_pdg'            => $package_fee,
                            'sc_pxl'            => $handling_fee,
                            'sc_discount_plk'   => $Data['total_discount'],
                            'sc_discount_pdg'   => $discount_package,
                            'sc_discount_pxl'   => $discount_handling_fee,

                            'historical_pxl'    => $provider_handling_fee,
                            'historical_pdg'    => $provider_package_fee,
                            'historical_plk'    => $Data['provider_total_fee'],

                            'historical_discount_pxl'   => $provider_discount_handling_fee,
                            'historical_discount_pdg'   => $provider_discount_package_fee,
                            'historical_discount_plk'   => $Data['provider_total_discount'],
                            'time_create'               => time()
                        ]);

                        //Insert
                        $Insert = [];
                        foreach($Data['list'] as $val){
                            $Insert[]   = [
                                'fulfillment_id'    => $InsertId,
                                'uid'               => $val['uid'],
                                'time_stocked'      => $val['time_stock'],
                                'time_packed'       => $val['time_packge'],
                                'warehouse_fee'     => $val['warehouse_fee'],
                                'handling_fee'      => $val['handling_fee'],
                                'discount_warehouse'=> $val['discount_warehouse_fee'],
                                'discount_handling' => $val['discount_handling_fee'],
                                'historical_warehouse_fee'  => $val['historical_warehouse_fee'],
                                'historical_handling_fee'   => $val['historical_handling_fee'],
                                'historical_discount_warehouse' => $val['historical_discount_warehouse'],
                                'historical_discount_handling'  => $val['historical_discount_handling']
                            ];
                        }
                        ordermodel\FulfillmentDetailModel::insert($Insert);
                    }catch (Exception $e){
                        return Response::json(['error' => true, 'message' => $e->getMessage(), 'order' => $Order]);
                    }
                }
            }
        }

        try{
            $OrdersModel::where('time_accept',$Order->time_accept)
                ->where('id', $Order->id)
                ->where('verify_id', 0)
                ->where('verify_return',0)
                ->update(['verify_return' => 1]);
            DB::connection('orderdb')->commit();
        }catch(Exception $e){
            return Response::json(['error' => true, 'message' => 'UPDATE_ORDER_FAIL']);
        }

        return Response::json(['error' => false, 'message' => 'SUCCESS', 'order' => $Order]);
    }


    public function getUpdateticket(){
        $ListCode       = [];
        $Order         = [];
        $RequestUpdate = [];
        $RequestModel = new ticketmodel\RequestModel;
        $OrderModel   = new ordermodel\OrdersModel;
        $ListRequest  = $RequestModel::where('time_create','>=',$this->time() - $this->time_limit)->where('status','PROCESSED')->with(['refer' => function($query){
            $query->where('type',1);
        }])->get(['id', 'status']);

        if(!empty($ListRequest)){
            foreach($ListRequest as $val){
                if(!empty($val['refer'])){
                    foreach($val['refer'] as $v){
                        $ListCode[] = strtoupper(trim($v['code']));
                    }
                }
            }

            if(!empty($ListCode)){
                $ListCode   = array_unique($ListCode);
                $ListOrder  = $OrderModel::where('time_create','>=',$this->time() - $this->time_limit)->whereIn('tracking_code',$ListCode)->whereIn('status',[52,53,66,67])->get(['tracking_code','status']);

                if(!empty($ListCode)){
                    foreach($ListCode as $val){
                        $Order[]    = $val;
                    }
                }

                if(!empty($Order)){
                    foreach($ListRequest as $val){
                        if(!empty($val['refer'])){
                            foreach($val['refer'] as $v){
                                if(in_array(strtoupper(trim($v['code'])), $Order)){
                                    $RequestUpdate[]    = (int)$val['id'];
                                }
                            }
                        }
                    }
                }

                if(!empty($RequestUpdate)){
                    $RequestModel->whereIn('id',$RequestUpdate)->update(['status' => 'CLOSED']);
                }
            }
        }

        return;

    }

    public function getConverttransaction(){return 1;
        $MerchantModel      = new \accountingmodel\MerchantModel;
        $TransactionModel   = new \accountingmodel\TransactionModel;
        $UserId             = 0;
        $Money              = 0;

        $Transaction = $TransactionModel->where('check',5)->orderBy('time_create','DESC')->orderBy('id','DESC')->first();

        if(empty($Transaction)){
            return Response::json(['error' => false, 'message' => 'END']);
        }

        $Money_Transaction = ABS($Transaction->money);

        if($Transaction->from_user_id != 1){
            $UserId = (int)$Transaction->from_user_id;
            $Money  = $Money_Transaction;
        }else{
            $UserId = (int)$Transaction->to_user_id;
            $Money  = - $Money_Transaction;
        }

        $Merchant   = $MerchantModel->where('merchant_id', $UserId)->first(['id','merchant_id','balance','balance_x']);
        DB::connection('accdb')->beginTransaction();

        if(empty($Merchant)){
            $Transaction->check             = 6;
            try{
                $Transaction->save();
                DB::connection('accdb')->commit();
                return Response::json(['error' => false, 'message' => 'MERCHANT_NOT_EXISTS', 'id' => $Transaction->id]);
            }catch (Exception $e){
                DB::connection('accdb')->rollBack();
                return Response::json(['error' => false, 'message' => 'UPDATE_TRANSACTION_FAIL', 'id' => $Transaction->id]);
            }
        }else{
            $Transaction->balance_before    = $Merchant->balance_x + $Money;
            $Merchant->balance_x            = $Merchant->balance_x + $Money;
            $Transaction->check             = 7;

            try{
                $Transaction->save();
                $Merchant->save();
                DB::connection('accdb')->commit();
                return Response::json(['error' => false, 'message' => 'SUCCESS']);
            }catch (Exception $e){
                return Response::json(['error' => false, 'message' => 'UPDATE_FAIL', 'id' => $Transaction->id]);
                DB::connection('accdb')->rollBack();
            }
        }
    }

    public function getFixtransaction(){return 1;
        $TransactionModel   = new \accountingmodel\TransactionModel;
        $VerifyModel        = new ordermodel\VerifyModel;

        $Transaction        = $TransactionModel->where('note','LIKE','%Rút tiền theo bảng kê số%')->where('check',1)->orderBy('id','DESC')->first();
        if(!empty($Transaction)){
            $Verify         = $VerifyModel->where('id',$Transaction->refer_code)->first();
            if(!empty($Verify)){
                if($Verify->transaction_id > 0){ // chuyển tiền
                    $Money  = $Verify->total_money_collect - $Verify->total_fee + $Verify->balance + (($Verify->balance_available - $Verify->config_balance) < 0 ? ($Verify->balance_available - $Verify->config_balance) : 0);
                    $Transaction->money = $Money;
                    $Transaction->check = 2;
                }else{ //ko chuyển tiền
                    $Transaction->check = 3;
                }

                try{
                    $Transaction->save();
                    return Response::json(['error' => false, 'message' => 'SUCCESS', 'id' => $Transaction->id]);
                }catch (Exception $e){
                    return Response::json(['error' => true, 'message' => 'UPDATE FAIL', 'id' => $Transaction->id]);
                }
            }else{
                return Response::json(['error' => true, 'message' => 'VERIFY_FAIL', 'id' => $Transaction->id]);
            }
        }else{
            return Response::json(['error' => false, 'message' => 'END']);
        }
    }

    public function getCreateverifymerchant(){
        $Month  = Input::has('month')   ? (int)Input::get('month')      : 0;
        $Year   = Input::has('year')    ? (int)Input::get('year')       : 0;
        if (empty($Month) || empty($Year) || ($Month < 1) || ($Month > 12)) {
            return Response::json(['error' => true, 'message' => 'TIME_ERROR']);
        }

        //Check Exists Verify
        $Count          = ordermodel\InvoiceModel::where('month',$Month)->where('year',$Year)->count();
        if ($Count > 0) {
            return Response::json(['error' => true, 'message' => 'INVOICE_EXISTS']);
        }

        if($Month == 12){
            $TimeAcceptEnd      = strtotime(date(($Year + 1).'-01-01 00:00:00'));
            $TimeSuccessEnd     = strtotime(date(($Year + 1).'-01-01 00:00:00'));
            $TimeAcceptStart    = strtotime(date($Year.'-'.($Month - 3).'-'.'01 00:00:00'));
        }else{
            if($Month == 1){
                $TimeAcceptStart    = strtotime(date(($Year - 1).'-10-'.'01 00:00:00'));
            }else{
                $TimeAcceptStart    = strtotime(date($Year.'-'.($Month - 3).'-'.'01 00:00:00'));
            }

            $TimeAcceptEnd      = strtotime(date($Year.'-'.($Month + 1).'-'.'01 00:00:00'));
            $TimeSuccessEnd     = strtotime(date($Year.'-'.($Month + 1).'-'.'15 00:00:00'));
        }
        $TimePickupEnd      = $TimeAcceptEnd;
        $TimePickupStart    = strtotime(date($Year.'-'.($Month).'-'.'01 00:00:00'));
        $TimeSuccessStart   = $TimePickupStart;
        $TimeSuccessStartT  = strtotime(date($Year.'-'.($Month).'-'.'15 00:00:00')); // Thời gian thành công bắt đầu của đơn tồn tháng trước

        $Data           = [];
        $GroupStatus    = $this->GroupStatus(4);
        if(isset($GroupStatus['error'])){
            return Response::json($GroupStatus);
        }

        $OrderModel         = new accountingmodel\OrdersModel;
        $OrderLModel        = clone $OrderModel;

        // get danh sách đơn hàng thành công của kỳ này và thành công trong kỳ
        $TotalAll         = $OrderModel::where('invoice_id',0)
            ->where('time_accept','>=',$TimeAcceptStart)
            ->where('time_accept','<',$TimeAcceptEnd)
            ->where('time_pickup','>=', $TimePickupStart)
            ->where('time_pickup','<',$TimePickupEnd)
            ->where('time_success','<',$TimeSuccessEnd)
            ->whereNotIn('status',$GroupStatus[33])
            ->groupBy('status','from_user_id')
            ->get(array('status', 'from_user_id',DB::raw('count(*) as count')))
            ->toArray();

        // Danh sách đơn tồn tháng trước thành công trong tháng
        $TotalLAll         = $OrderModel::where('invoice_id',0)
            ->where('time_accept','>=',$TimeAcceptStart)
            ->where('time_accept','<',$TimeAcceptEnd)
            ->where('time_pickup','<', $TimePickupStart)
            ->where('time_success','>=',$TimeSuccessStartT)
            ->where('time_success','<',$TimeSuccessEnd)
            ->whereIn('status',[52,53,66,67])
            ->groupBy('status','from_user_id')
            ->get(array('status', 'from_user_id',DB::raw('count(*) as count')))
            ->toArray();

        foreach($TotalAll as $val){
            if(!isset($Data[(int)$val['from_user_id']])){
                $Data[(int)$val['from_user_id']]    = [
                    'user_id'           => (int)$val['from_user_id'],
                    'month'             => $Month,
                    'year'              => $Year,
                    'total_success'     => 0,
                    'total_return'      => 0,
                    'total_backlog'     => 0,

                    'total_delivering'      => 0,
                    'total_problem'         => 0,
                    'total_confirm_return'  => 0,
                    'total_returning'       => 0,
                    'total_cod'             => 0,

                    'total_sc_pvc'          => 0,
                    'total_sc_cod'          => 0,
                    'total_sc_pbh'          => 0,
                    'total_sc_pvk'          => 0,
                    'total_sc_pch'          => 0,
                    'total_sc_plk'          => 0,
                    'total_sc_pdg'          => 0,
                    'total_sc_pxl'          => 0,
                    'total_premote'         => 0,
                    'total_pclearance'      => 0,
                    'total_sc_discount_pvc' => 0,
                    'total_sc_discount_cod' => 0,
                    'total_discount_plk'    => 0,
                    'total_discount_pdg'    => 0,
                    'total_discount_pxl'    => 0,
                    'total_money_collect'   => 0,
                    'total_lsuccess'        => 0,
                    'total_lreturn'         => 0,
                    'total_lsc_pvc'         => 0,
                    'total_lsc_cod'         => 0,
                    'total_lsc_pbh'         => 0,
                    'total_lsc_pvk'         => 0,
                    'total_lsc_pch'         => 0,
                    'total_lsc_plk'         => 0,
                    'total_lsc_pdg'         => 0,
                    'total_lsc_pxl'         => 0,
                    'total_lsc_pclearance'  => 0,
                    'total_lsc_premote'     => 0,
                    'total_lsc_discount_pvc'=> 0,
                    'total_lsc_discount_cod'=> 0,
                    'total_ldiscount_plk'   => 0,
                    'total_ldiscount_pdg'   => 0,
                    'total_ldiscount_pxl'   => 0,
                    'total_lmoney_collect'  => 0,
                    'status'                => 'WAITING',
                    'time_create'           => $this->time()
                ];
            }

            if(in_array($val['status'], $GroupStatus[30])){ // THành công
                $Data[(int)$val['from_user_id']]['total_success']   += (int)$val['count'];
            }elseif(in_array($val['status'], $GroupStatus[36])){// Chuyển hoàn
                $Data[(int)$val['from_user_id']]['total_return']    += (int)$val['count'];
            }else{
                $Data[(int)$val['from_user_id']]['total_backlog']   += (int)$val['count'];

                if(in_array($val['status'], $GroupStatus[28])){
                    $Data[(int)$val['from_user_id']]['total_delivering']            += (int)$val['count'];
                }elseif(in_array($val['status'], $GroupStatus[29])){
                    $Data[(int)$val['from_user_id']]['total_problem']               += (int)$val['count'];
                }elseif(in_array($val['status'], $GroupStatus[31])){
                    $Data[(int)$val['from_user_id']]['total_confirm_return']        += (int)$val['count'];
                }elseif(in_array($val['status'], $GroupStatus[32])){
                    $Data[(int)$val['from_user_id']]['total_returning']             += (int)$val['count'];
                }

            }
        }

        foreach($TotalLAll as $val){
            if(!isset($Data[(int)$val['from_user_id']])){
                $Data[(int)$val['from_user_id']]    = [
                    'user_id'           => (int)$val['from_user_id'],
                    'month'             => $Month,
                    'year'              => $Year,
                    'total_success'     => 0,
                    'total_return'      => 0,
                    'total_backlog'     => 0,

                    'total_delivering'      => 0,
                    'total_problem'         => 0,
                    'total_confirm_return'  => 0,
                    'total_returning'       => 0,
                    'total_cod'             => 0,

                    'total_sc_pvc'          => 0,
                    'total_sc_cod'          => 0,
                    'total_sc_pbh'          => 0,
                    'total_sc_pvk'          => 0,
                    'total_sc_pch'          => 0,
                    'total_sc_plk'          => 0,
                    'total_sc_pdg'          => 0,
                    'total_sc_pxl'          => 0,
                    'total_premote'         => 0,
                    'total_pclearance'      => 0,
                    'total_sc_discount_pvc' => 0,
                    'total_sc_discount_cod' => 0,
                    'total_discount_plk'    => 0,
                    'total_discount_pdg'    => 0,
                    'total_discount_pxl'    => 0,
                    'total_money_collect'   => 0,
                    'total_lsuccess'        => 0,
                    'total_lreturn'         => 0,
                    'total_lsc_pvc'         => 0,
                    'total_lsc_cod'         => 0,
                    'total_lsc_pbh'         => 0,
                    'total_lsc_pvk'         => 0,
                    'total_lsc_pch'         => 0,
                    'total_lsc_plk'         => 0,
                    'total_lsc_pdg'         => 0,
                    'total_lsc_pxl'         => 0,
                    'total_lsc_pclearance'  => 0,
                    'total_lsc_premote'     => 0,
                    'total_lsc_discount_pvc'=> 0,
                    'total_lsc_discount_cod'=> 0,
                    'total_ldiscount_plk'   => 0,
                    'total_ldiscount_pdg'   => 0,
                    'total_ldiscount_pxl'   => 0,
                    'total_lmoney_collect'  => 0,
                    'status'                => 'WAITING',
                    'time_create'           => $this->time()
                ];
            }

            if(in_array($val['status'], $GroupStatus[30])){ // THành công
                $Data[(int)$val['from_user_id']]['total_lsuccess']   += (int)$val['count'];
            }elseif(in_array($val['status'], $GroupStatus[36])){// Chuyển hoàn
                $Data[(int)$val['from_user_id']]['total_lreturn']    += (int)$val['count'];
            }
        }

        $i = 0;
        $ListInsert = [];
        foreach($Data as $val) {
            $i++;
            $ListInsert[$i % 10][] = $val;
        }

        $InvoiceModel   = new ordermodel\InvoiceModel;
        try{
            foreach($ListInsert as $val){
                $InvoiceModel::insert($val);
            }
        }catch (Exception $e){
            return Response::json(['error' => false, 'message' => 'INSERT_ERROR']);
        }

        return Response::json(['error' => false, 'message' => 'SUCCESS']);
    }


    public function getVerifymerchant(){
        $InvoiceModel   = new ordermodel\InvoiceModel;
        $Invoice        = $InvoiceModel::where('status','WAITING')->orderBy('id','ASC')->first();

        if(empty($Invoice)){
            return Response::json(['error' => false, 'message' => 'EMPTY']);
        }

        try{
            $Invoice->status = 'PROCESSING';
            $Invoice->save();
        }catch (Exception $e){
            return Response::json(['error' => false, 'message' => 'UPDATE_INVOICE_FAIL']);
        }

        if($Invoice->month == 12){
            $TimeAcceptEnd      = strtotime(date(($Invoice->year + 1).'-01-01 00:00:00'));
            $TimeSuccessEnd     = strtotime(date(($Invoice->year + 1).'-01-01 00:00:00'));
            $TimeAcceptStart    = strtotime(date($Invoice->year.'-'.($Invoice->month - 3).'-'.'01 00:00:00'));
        }else{
            if($Invoice->month == 1){
                $TimeAcceptStart    = strtotime(date(($Invoice->year - 1).'-10-'.'01 00:00:00'));
            }else{
                $TimeAcceptStart    = strtotime(date($Invoice->year.'-'.($Invoice->month - 3).'-'.'01 00:00:00'));
            }

            $TimeAcceptEnd      = strtotime(date($Invoice->year.'-'.($Invoice->month + 1).'-'.'01 00:00:00'));
            $TimeSuccessEnd     = strtotime(date($Invoice->year.'-'.($Invoice->month + 1).'-'.'15 00:00:00'));
        }
        $TimePickupEnd      = $TimeAcceptEnd;
        $TimePickupStart    = strtotime(date($Invoice->year.'-'.($Invoice->month).'-'.'01 00:00:00'));
        $TimeSuccessStart   = $TimePickupStart;
        $TimeSuccessStartT  = strtotime(date($Invoice->year.'-'.($Invoice->month).'-'.'15 00:00:00')); // Thời gian thành công bắt đầu của đơn tồn tháng trước

        $GroupStatus    = $this->GroupStatus(4);
        if(isset($GroupStatus['error'])){
            return Response::json($GroupStatus);
        }

        $OrderModel         = new ordermodel\OrdersModel;
        $OrderModel         = $OrderModel->where('invoice_id',0)
                                            ->where('from_user_id', $Invoice->user_id)
                                            ->where('time_create','>=', $TimeAcceptStart - 86400*60)
                                            ->where('time_accept','>=',$TimeAcceptStart)
                                            ->where('time_accept','<',$TimeAcceptEnd)
                                            ->where('time_success','<',$TimeSuccessEnd)
                                            ->whereIn('status',[52,53,66,67])
                                            ->where(function($query) use($TimePickupEnd, $TimePickupStart, $TimeSuccessStart,$TimeSuccessStartT){
                                                $query->where(function($q) use($TimePickupEnd, $TimePickupStart, $TimeSuccessStart){
                                                    $q->where('time_pickup','>=',$TimePickupStart)
                                                        ->where('time_pickup','<',$TimePickupEnd)
                                                        ->where('time_success','>=',$TimeSuccessStart);
                                                })->orWhere(function($q) use($TimePickupStart,$TimeSuccessStartT){
                                                    $q->where('time_pickup','<',$TimePickupStart)
                                                        ->where('time_success','>=',$TimeSuccessStartT);
                                                });
                                            });

        $ListModel  = clone $OrderModel;
        $ListModel->with(['OrderDetail','OrderFulfillment'])
            ->chunk('1000', function($query) use(&$Invoice, &$TimePickupStart, &$GroupStatus) {
                foreach ($query as $val) {
                    $val    = $val->toArray();
                    if($val['time_pickup'] >= $TimePickupStart){ // Vận đơn tháng này
                        $Invoice->total_sc_pvc              += $val['order_detail']['sc_pvc'];
                        $Invoice->total_sc_pvk              += $val['order_detail']['sc_pvk'];
                        $Invoice->total_premote             += $val['order_detail']['sc_remote'];
                        $Invoice->total_pclearance          += $val['order_detail']['sc_clearance'];

                        if(!empty($val['order_fulfillment'])){
                            $Invoice->total_sc_plk              += $val['order_fulfillment']['sc_plk'];
                            $Invoice->total_sc_pdg              += $val['order_fulfillment']['sc_pdg'];
                            $Invoice->total_sc_pxl              += $val['order_fulfillment']['sc_pxl'];

                            $Invoice->total_discount_plk        += $val['order_fulfillment']['sc_discount_plk'];
                            $Invoice->total_discount_pdg        += $val['order_fulfillment']['sc_discount_pdg'];
                            $Invoice->total_discount_pxl        += $val['order_fulfillment']['sc_discount_pxl'];
                        }

                        $Invoice->total_sc_discount_pvc     += $val['order_detail']['sc_discount_pvc'];

                        if($val['order_detail']['money_collect'] > 0){
                            $Invoice->total_cod += 1;
                        }

                        if(in_array($val['status'], $GroupStatus[30])){ // Thành công
                            $Invoice->total_sc_cod              += $val['order_detail']['sc_cod'];
                            $Invoice->total_sc_pbh              += $val['order_detail']['sc_pbh'];
                            $Invoice->total_sc_discount_cod     += $val['order_detail']['sc_discount_cod'];
                            $Invoice->total_money_collect       += $val['order_detail']['money_collect'];
                        }elseif(in_array($val['status'], $GroupStatus[36])){
                            if($val['status'] == 66){
                                $Invoice->total_sc_pch              += $val['order_detail']['sc_pch'];
                            }
                        }
                    }else{ // Vận đơn tháng trước
                        $Invoice->total_lsc_pvc              += $val['order_detail']['sc_pvc'];
                        $Invoice->total_lsc_pvk              += $val['order_detail']['sc_pvk'];

                        $Invoice->total_lsc_premote          += $val['order_detail']['sc_remote'];
                        $Invoice->total_lsc_pclearance       += $val['order_detail']['sc_clearance'];

                        if(!empty($val['order_fulfillment'])){
                            $Invoice->total_lsc_plk         += $val['order_fulfillment']['sc_plk'];
                            $Invoice->total_lsc_pdg         += $val['order_fulfillment']['sc_pdg'];
                            $Invoice->total_lsc_pxl         += $val['order_fulfillment']['sc_pxl'];

                            $Invoice->total_ldiscount_plk        += $val['order_fulfillment']['sc_discount_plk'];
                            $Invoice->total_ldiscount_pdg        += $val['order_fulfillment']['sc_discount_pdg'];
                            $Invoice->total_ldiscount_pxl        += $val['order_fulfillment']['sc_discount_pxl'];
                        }

                        $Invoice->total_lsc_discount_pvc     += $val['order_detail']['sc_discount_pvc'];

                        if(in_array($val['status'], $GroupStatus[30])){ // Thành công
                            $Invoice->total_lsc_cod             += $val['order_detail']['sc_cod'];
                            $Invoice->total_lsc_pbh             += $val['order_detail']['sc_pbh'];
                            $Invoice->total_lsc_discount_cod    += $val['order_detail']['sc_discount_cod'];
                            $Invoice->total_lmoney_collect      += $val['order_detail']['money_collect'];
                        }elseif(in_array($val['status'], $GroupStatus[36])){
                            if($val['status'] == 66){
                                $Invoice->total_lsc_pch             += $val['order_detail']['sc_pch'];
                            }
                        }
                    }

                }
            });

        //Lấy ra phí lưu kho theo khoang kệ hoặc phí chuyển đổi từ 2 tháng này đến 1 tháng tiếp
        $WareHouseFee   = \fulfillmentmodel\WareHouseVerifyModel::where('user_id', $Invoice->user_id)
                                                                ->where('date','>=',(date('Y-m-1',$TimePickupStart)))
                                                                ->where('date','<',(date('Y-m-1',$TimeSuccessEnd)))
                                                                ->get()->toArray();
        if(!empty($WareHouseFee)){
            foreach($WareHouseFee as $val){
                $Invoice->total_sc_plk          += $val['warehouse_fee'];
                $Invoice->total_discount_plk    += $val['discount_warehouse'];
            }
        }

        DB::connection('orderdb')->beginTransaction();
        try{
            $OrderModel->update(['invoice_id' => $Invoice->id]);
            DB::connection('orderdb')->commit();
        }catch (Exception $e){
            DB::connection('orderdb')->rollBack();
            return Response::json(['error' => false, 'message' => 'UPDATE_ORDER_FAIL']);
        }

        try{
            $Invoice->status = 'SUCCESS';
            $Invoice->save();
        }catch (Exception $e){
            return Response::json(['error' => false, 'message' => 'UPDATE_INVOICE_FAIL']);
        }
        return Response::json(['error' => true, 'message' => 'SUCCESS']);
    }

    private function GroupStatus($group){
        $StatusOrderCtrl    = new order\StatusOrderCtrl;
        if($group > 0){
            Input::merge(['group' => $group]);
        }
        $ListGroupStatus    = $StatusOrderCtrl->getStatusgroup(false);
        if(!empty($ListGroupStatus)){
            $GroupStatus    = [];
            foreach($ListGroupStatus as $val){
                $GroupStatus[(int)$val['id']]   = [];
                if(!empty($val['group_order_status'])){
                    foreach($val['group_order_status'] as $v){
                        $GroupStatus[(int)$val['id']][] = (int)$v['order_status_code'];
                    }
                }
            }
            return $GroupStatus;
        }else{
            return ['error' => true, 'message' => 'GROUP_STATUS_EMPTY'];
        }
    }

    public function getReportmerchant(){
        /*$Time = Input::has('time') ? trim(Input::get('time')) : '';
        if(empty($Time)){
            return ['error' => true, 'message' => 'TIME_EMPTY'];
        }*/

        $ReportMerchantModel    = new accountingmodel\ReportMerchantModel;
        $OrderModel             = new ordermodel\OrdersModel;

        $TimeEnd        = strtotime(date('Y-m-d', strtotime(' -1 day')));
        $TimeStart      = $TimeEnd - 86400;

        $DateMonth      = explode('/',date('Y/m/d',$TimeStart));

        $Year           = (int)$DateMonth[0];
        $Month          = (int)$DateMonth[1];
        $Day            = (int)$DateMonth[2];
        $TimePickup     = strtotime(date($Year."-".$Month."-1"));


        $Check = $ReportMerchantModel->where('date',$Day)->where('month',$Month)->where('year',$Year)->count();
        if($Check > 0){
            return ['error' => true, 'message' => 'DATA_EXISTS'];
        }

        $Insert = [];
        $Model = $OrderModel::where('time_accept','>=',$TimePickup - 86400*60)
            ->where(function($query) use($TimeStart, $TimeEnd){
                $query->where(function($q)use($TimeStart, $TimeEnd){
                    $q->where('time_pickup','>=',$TimeStart)
                        ->where('time_pickup','<',$TimeEnd);
                })->orWhere(function($q) use($TimeStart, $TimeEnd){
                    $q->where('time_success','>=',$TimeStart)
                        ->where('time_success','<',$TimeEnd)
                        ->whereIn('status',[52,53,66,67]);
                });
            })
            ->select(['id', 'from_user_id','status','time_pickup','time_success'])
            ->with('OrderDetail')
            ->chunk('1000', function($query) use(&$Insert, &$Day, &$Month, &$Year, &$TimeStart, &$TimeEnd, &$TimePickup) {
                foreach ($query as $val) {
                    $val    = $val->toArray();

                    if(!isset($Insert[(int)$val['from_user_id']])){
                        $Insert[(int)$val['from_user_id']] = [
                            'user_id'       => (int)$val['from_user_id'],
                            'date'          => $Day,
                            'month'         => $Month,
                            'year'          => $Year,
                            'time_create'   => $this->time(),
                            'generate'      => 0,
                            'success'       => 0,
                            'total_return'  => 0,
                            'sc_pvc'        => 0,
                            'sc_cod'        => 0,
                            'sc_pbh'        => 0,
                            'sc_discount_pvc' => 0,
                            'sc_discount_cod' => 0,
                            'money_collect' => 0,
                            'sc_pvk'        => 0,
                            'return_sc_cod' => 0,
                            'return_sc_pbh' => 0,
                            'return_sc_pch' => 0,
                            'return_sc_discount_cod'    => 0,
                            'return_money_collect'      => 0,
                            'lsuccess'                  => 0,
                            'lreturn'                   => 0,
                            'lreturn_sc_cod'            => 0,
                            'lreturn_sc_pch'            => 0,
                            'lreturn_sc_pbh'            => 0,
                            'lreturn_sc_discount_cod'   => 0,
                            'lreturn_money_collect'     => 0,
                            'l_sc_pvk'                  => 0
                        ];
                    }

                    if($val['time_pickup'] >= $TimeStart && $val['time_pickup'] < $TimeEnd){ // Đơn lấy hàng trong ngày
                        $Insert[(int)$val['from_user_id']]['generate'] += 1;
                        if(!empty($val['order_detail'])){
                            $Insert[(int)$val['from_user_id']]['sc_pvc']   += $val['order_detail']['sc_pvc'];
                            $Insert[(int)$val['from_user_id']]['sc_cod']   += $val['order_detail']['sc_cod'];
                            $Insert[(int)$val['from_user_id']]['sc_pbh']   += $val['order_detail']['sc_pbh'];
                            $Insert[(int)$val['from_user_id']]['sc_discount_pvc']   += $val['order_detail']['sc_discount_pvc'];
                            $Insert[(int)$val['from_user_id']]['sc_discount_cod']   += $val['order_detail']['sc_discount_cod'];
                            $Insert[(int)$val['from_user_id']]['money_collect']     += $val['order_detail']['money_collect'];
                        }

                        if(in_array((int)$val['status'], [52,53,66,67])){
                            if(in_array($val['status'], [66,67])){ // Chuyển hoàn
                                $Insert[(int)$val['from_user_id']]['total_return']   += 1;
                                if(!empty($val['order_detail'])) {
                                    $Insert[(int)$val['from_user_id']]['sc_pvk']                   += $val['order_detail']['sc_pvk'];
                                    $Insert[(int)$val['from_user_id']]['return_sc_cod']            += $val['order_detail']['sc_cod'];
                                    $Insert[(int)$val['from_user_id']]['return_sc_pbh']            += $val['order_detail']['sc_pbh'];
                                    $Insert[(int)$val['from_user_id']]['return_sc_discount_cod']   += $val['order_detail']['sc_discount_cod'];
                                    $Insert[(int)$val['from_user_id']]['return_money_collect']     += $val['order_detail']['money_collect'];

                                    if($val['status'] == 66){
                                        $Insert[(int)$val['from_user_id']]['return_sc_pch']            += $val['order_detail']['sc_pch'];
                                    }
                                }

                            }else{ // Thành công
                                $Insert[(int)$val['from_user_id']]['success']   += 1;
                                if(!empty($val['order_detail'])) {
                                    if(!empty($val['order_detail'])) {
                                        $Insert[(int)$val['from_user_id']]['sc_pvk'] += $val['order_detail']['sc_pvk'];
                                    }
                                }
                            }
                        }
                    }else{// Đơn thành công
                        if($val['time_pickup'] > $TimePickup){ // Thành công trong tháng này
                            if(in_array($val['status'], [66,67])){ // Chuyển hoàn
                                $Insert[(int)$val['from_user_id']]['total_return']   += 1;
                                if(!empty($val['order_detail'])) {
                                    $Insert[(int)$val['from_user_id']]['sc_pvk']                   += $val['order_detail']['sc_pvk'];
                                    $Insert[(int)$val['from_user_id']]['return_sc_cod']            += $val['order_detail']['sc_cod'];
                                    $Insert[(int)$val['from_user_id']]['return_sc_pbh']            += $val['order_detail']['sc_pbh'];
                                    $Insert[(int)$val['from_user_id']]['return_sc_discount_cod']   += $val['order_detail']['sc_discount_cod'];
                                    $Insert[(int)$val['from_user_id']]['return_money_collect']     += $val['order_detail']['money_collect'];

                                    if($val['status'] == 66){
                                        $Insert[(int)$val['from_user_id']]['return_sc_pch']            += $val['order_detail']['sc_pch'];
                                    }
                                }

                            }else{ // Thành công
                                $Insert[(int)$val['from_user_id']]['success']   += 1;
                                if(!empty($val['order_detail'])) {
                                    if(!empty($val['order_detail'])) {
                                        $Insert[(int)$val['from_user_id']]['sc_pvk'] += $val['order_detail']['sc_pvk'];
                                    }
                                }
                            }
                        }else{// tồn tháng trước thành công
                            if(in_array($val['status'], [66,67])){ // Chuyển hoàn
                                $Insert[(int)$val['from_user_id']]['lreturn']   += 1;
                                if(!empty($val['order_detail'])) {
                                    $Insert[(int)$val['from_user_id']]['l_sc_pvk']                 += $val['order_detail']['sc_pvk'];
                                    $Insert[(int)$val['from_user_id']]['lreturn_sc_cod']           += $val['order_detail']['sc_cod'];
                                    $Insert[(int)$val['from_user_id']]['lreturn_sc_pbh']           += $val['order_detail']['sc_pbh'];
                                    $Insert[(int)$val['from_user_id']]['lreturn_sc_discount_cod']  += $val['order_detail']['sc_discount_cod'];
                                    $Insert[(int)$val['from_user_id']]['lreturn_money_collect']    += $val['order_detail']['money_collect'];

                                    if($val['status'] == 66){
                                        $Insert[(int)$val['from_user_id']]['lreturn_sc_pch']           += $val['order_detail']['sc_pch'];
                                    }
                                }
                            }else{
                                $Insert[(int)$val['from_user_id']]['lsuccess']   += 1;
                                if(!empty($val['order_detail'])) {
                                    if(!empty($val['order_detail'])) {
                                        $Insert[(int)$val['from_user_id']]['l_sc_pvk'] += $val['order_detail']['sc_pvk'];
                                    }
                                }
                            }
                        }
                    }
                }
            });

        if(!empty($Insert)){
            $i = 0;
            $DataInsert = [];
            foreach($Insert as $val) {
                $i++;
                $DataInsert[$i % 10][] = $val;
            }

            $ReportMerchantModel    = new accountingmodel\ReportMerchantModel;
            try{
                foreach($DataInsert as $val){
                    $ReportMerchantModel->insert($val);
                }

            }catch (Exception $e){
                return ['error' => true, 'message' => 'INSERT_ERROR'];
            }
        }
        return ['error' => false, 'message' => 'SUCCESS'];
    }

    public function getUpdateLoyalty(){
        $Date   = date('Y-m-d');

        $User   = loyaltymodel\UserModel::where('provisional','<>',$Date)->where('active',1)->orderBy('user_id','ASC')->first();
        if(!isset($User->id)){
            return 'Hết rồi';
        }

        $TimeEnd        = strtotime($Date);
        $TimeStart      = $TimeEnd - 86400;

        $TotalFee       = 0;
        $TotalSuccess   = 0;
        $TotalReturn    = 0;
        $Model = ordermodel\OrdersModel::where('time_accept','>=',$TimeStart - 86400*90)
                    ->where('time_success','>=',$TimeStart)
                    ->where('time_success','<',$TimeEnd)
                    ->where('from_user_id', $User->user_id)
                    ->whereIn('status',[52,53,66,67])
            ->select(['id', 'from_user_id','status','time_pickup','time_success'])
            ->with('OrderDetail')
            ->chunk('1000', function($query) use(&$TotalFee, &$TotalReturn, &$TotalSuccess) {
                foreach ($query as $val) {
                    $val = $val->toArray();
                    $TotalFee += $val['order_detail']['sc_pvc'] + $val['order_detail']['sc_pvk'] - $val['order_detail']['sc_discount_pvc'];
                    if(in_array($val['status'], [66,67])){
                        $TotalReturn    += 1;
                        if($val['status'] == 66){
                            $TotalFee       += $val['order_detail']['sc_pch'];
                        }
                    }else{
                        $TotalSuccess   += 1;
                        $TotalFee       += $val['order_detail']['sc_cod'] + $val['order_detail']['sc_pbh'] - $val['order_detail']['sc_discount_cod'];
                    }
                }
            });

            DB::connection('loyaltydb')->beginTransaction();
            $Point = ceil($TotalFee/10000);
            try{
                if($Point > 0){
                    loyaltymodel\LogUpdatePointModel::insert([
                        'user_id'   => $User->user_id,
                        'date'      => date('Y-m-d', $TimeStart),
                        'success'   => $TotalSuccess,
                        'return'    => $TotalReturn,
                        'total_fee' => $TotalFee,
                        'point'     => $Point,
                        'time_create'   => $this->time()
                    ]);
                }


                $User->total_point      += $Point;
                $User->provisional      = $Date;

                if(date('d', $TimeStart) == 1 && ($User->time_create <= strtotime(date('Y-m-1')))){ // đầu tháng  cập nhật level
                    $Level = loyaltymodel\LevelModel::orderBy('code','DESC')->remember(60)->get();
                    $PointUpdate = 0;
                    foreach($Level as $val){
                        if($User->level = $val['code']){ // cấp hiện tại
                            if($User->current_point >= $val['maintain_point']){
                                $User->level   =  $val['code'];
                                $PointUpdate   = $val['maintain_point'];
                                break;
                            }
                        }else{
                            if($User->current_point >= $val['point']){
                                $User->level   =  $val['code'];
                                $PointUpdate   = $val['point'];
                                break;
                            }
                        }
                    }

                    loyaltymodel\HistoryModel::insert(
                        ['user_id' => $User->user_id, 'month' => date('m', ($TimeStart - 86400)), 'year' => date('Y', ($TimeStart - 86400)),'level' => $User->level,
                            'current_point' => $User->current_point,'point' => $PointUpdate,'time_create' => $this->time()]
                    );

                    $User->current_point    = $Point;
                }else{ // cộng điểm
                    $User->current_point    += $Point;
                }
                $User->save();
                DB::connection('loyaltydb')->commit();
                return 'Thành công';
            }catch (Exception $e){
                return $e->getMessage();
            }
    }

    /**
     * Report  Phát sinh trong ngày
     */
    public function getReportOrder(){
        $TimeStart          = strtotime(date('Y-m-d', strtotime(' -2 day')));
        $DateMonth          = explode('/',date('Y/m/d',$TimeStart));

        $Year               = (int)$DateMonth[0];
        $Month              = (int)$DateMonth[1];
        $Day                = (int)$DateMonth[2];

        $ReportOrderModel       = new accountingmodel\ReportOrderModel;
        $OrderModel             = new ordermodel\OrdersModel;

        if($ReportOrderModel::where('date',$Day)->where('month',$Month)->where('year',$Year)->count() > 0){
            return ['error' => true, 'message' => 'DATA_EXISTS'];
        }

        $OrderModel         = $OrderModel::where('time_accept','>=',$TimeStart)->where('time_accept','<',($TimeStart + 86400));
        $OrderPickupModel   = clone $OrderModel;

        $OrderGenerate      = $OrderModel->groupBy('from_user_id')->get(['from_user_id',DB::raw('count(*) as count')])->toArray();

        if(!empty($OrderGenerate)){
            $OrderPickup        = $OrderPickupModel->where('time_pickup','>',0)->where('time_pickup','<=',($TimeStart + 172800))
                                                   ->groupBy('from_user_id')
                                                   ->get(['from_user_id',DB::raw('count(*) as count')])->toArray();

            $Insert = [];
            foreach($OrderGenerate as $val){
                $Insert[(int)$val['from_user_id']]   =   [
                    'user_id'     => (int)$val['from_user_id'],
                    'date'        => $Day,
                    'month'       => $Month,
                    'year'        => $Year,
                    'generate'    => (int)$val['count'],
                    'pickup'      => 0,
                    'time_create' => $this->time()
                ];
            }

            if(!empty($OrderPickup)){
                foreach($OrderPickup as $val){
                    if(isset($Insert[(int)$val['from_user_id']])){
                        $Insert[(int)$val['from_user_id']]['pickup']    = (int)$val['count'];
                    }
                }
            }


            try{
                $ReportOrderModel->insert($Insert);
                return ['error' => false, 'message' => 'SUCCESS'];
            }catch (Exception $e){
                return ['error' => true, 'message' => $e->getMessage()];
            }

        }

        return ['error' => false, 'message' => 'EMPTY'];
    }

    public function getMaplocation(){
        $ProvinceModel      = new metadatamodel\ProvinceModel;
        $CityModel          = new CityModel;
        $CourierMapModel    = new metadatamodel\CourierMapModel;

        $City = $CityModel->where('map',0)->first();
        if(isset($City->id)){
            $TTC = $ProvinceModel->where('name','LIKE','%'.trim(str_replace(['Quận', 'quận','Huyện','huyện','Thị xã','thị xã'],'',$City->city_name)).'%')->first();
            if(isset($TTC->province_id)){
                $CourierMapModel->insert([
                    'courier_id'            => 11, //Tín thành
                    'city_id'               => $City->id,
                    'province_id'           => 0,
                    'ward_id'               => 0,
                    'courier_city_id'       => $TTC->province_id,
                    'courier_province_id'   => 0,
                    'courier_ward_id'       => 0

                ]);
                $City->map = 1;
                $City->save();
                return ['error' => false, 'message' => 'SUCCESS'];
            }else{
                $City->map = 2;
                $City->save();
                return ['error' => false, 'message' => 'ERROR', 'city' => $City];
            }

        }else{
            return ['error' => false, 'message' => 'EMPTY'];
        }
    }

    public function getMapward(){
        $WardModel          = new WardModel;
        $LocationModel      = new CourierLocationModel;
        $LMongo             = new \LMongo;
        $LMongo               = $LMongo::collection('ward_vtp');

        $Ward = $WardModel->where('map',0)->orderBy('id','ASC')->first();
        if(isset($Ward->id)){
            $DistrictMap = $LocationModel->where('courier_id',1)->where('province_id',$Ward->district_id)->where('ward_id',0)->first();
            if(!isset($DistrictMap->id)){
                $Ward->map = 3;
                $Ward->save();
                return ['error' => false, 'message' => 'DISTRICT_NOT_EXISTS'];
            }

            $TTC = $LMongo->where('district',$DistrictMap->courier_province_id)->where('city', $DistrictMap->courier_city_id)->whereLike('name',trim(str_replace(['Thị Trấn', 'thị trấn','Xã','xã','Thị xã','thị xã','Phường','phường'],'',$Ward->ward_name)), 'im')->first();

            if(!empty($TTC)){
                $LocationModel->insert([
                    'courier_id'            => 1, //Tín thành
                    'city_id'               => $Ward->city_id,
                    'province_id'           => $Ward->district_id,
                    'ward_id'               => $Ward->id,
                    'courier_city_id'       => $TTC['city'],
                    'courier_province_id'   => $TTC['district'],
                    'courier_ward_id'       => $TTC['ward']

                ]);
                $Ward->map = 1;
                $Ward->save();
                return ['error' => false, 'message' => 'SUCCESS'];
            }else{
                $Ward->map = 2;
                $Ward->save();
                return ['error' => false, 'message' => 'ERROR', 'city' => $Ward];
            }

        }else{
            return ['error' => false, 'message' => 'EMPTY'];
        }
    }

    public function getMapdistrict(){
        $DistrictModel      = new DistrictModel;
        $LocationModel      = new CourierLocationModel;
        $LMongo             = new \LMongo;
        $LMongo               = $LMongo::collection('location_vtp');

        $District = $DistrictModel->where('map',0)->first();
        if(isset($District->id)){
            $TTC = $LMongo->whereLike('name',trim(str_replace(['Quận', 'quận','Huyện','huyện','Thị xã','thị xã'],'',$District->district_name)), 'im')->first();

            if(!empty($TTC)){
                $LocationModel->insert([
                    'courier_id'            => 1, //Tín thành
                    'city_id'               => $District->city_id,
                    'province_id'           => $District->id,
                    'ward_id'               => 0,
                    'courier_city_id'       => $TTC['code'],
                    'courier_province_id'   => $TTC['id'],
                    'courier_ward_id'       => ""

                ]);
                $District->map = 1;
                $District->save();
                return ['error' => false, 'message' => 'SUCCESS'];
            }else{
                $District->map = 2;
                $District->save();
                return ['error' => false, 'message' => 'ERROR', 'city' => $District];
            }

        }else{
            return ['error' => false, 'message' => 'EMPTY'];
        }
    }

    public function getTest(){
        $Array = [
          ['id' => 2, 'name' => '2'],
            ['id' => 4, 'name' => '4'],
            ['id' => 6, 'name' => '6'],
            ['id' => 3, 'name' => '3'],
            ['id' => 1, 'name' => '1']
        ];
        return array_values(array_sort($Array, function($value){
            return $value['id'];
        }));
    }

    public function getSeller($user_id = 0){
        $SellerModel            = new omsmodel\SellerModel;
        $ReportMerchantModel    = new accountingmodel\ReportMerchantModel;

        $ListMonth              = [
            1   => 31,
            2   => 28,
            3   => 31,
            4   => 30,
            5   => 31,
            6   => 30,
            7   => 31,
            8   => 31,
            9   => 30,
            10  => 31,
            11  => 30,
            12  => 31
        ];

        if(!empty($user_id)){
            $SellerModel    = $SellerModel->where('user_id',$user_id);
        }

        $Merchant = $SellerModel->where('first_time_pickup','>',0)->where('release',0)->orderBy('time_update','ASC')->orderBy('id','ASC')->first();

        if(!isset($Merchant->id)){
            return ['error' => false, 'message' => 'EMPTY'];
        }

        $Merchant->time_update  = $this->time();
        $FistOrderTime          = ($Merchant->first_time_incomings > 0) ? $Merchant->first_time_incomings : $Merchant->first_time_pickup;

        $DateMonth      = explode('/',date('Y/m/d',$FistOrderTime));
        $Month = $DateMonth[1];
        $Date  = $DateMonth[2];
        $Year  = $DateMonth[0];

        $NextMonth  = (int)$Month + 1;
        if($Month == 12){
            $NextMonth  = 1;
            $NextYear   = $Year + 1;
        }else{
            $NextYear   = $Year;
        }

//        Doanh thu đầu tháng
//        nếu ngày bắt đầu < 25 thì sẽ tính từ  1 -> 25
//        nếu ngày bắt đầu > 25 thì sẽ tính từ 25 -> 25 tháng sau
        $ReportMerchant = new accountingmodel\ReportMerchantModel;
        $ReportMerchant = $ReportMerchant::where('user_id', $Merchant->user_id);
        if($Date < 25){
            $ReportMerchant = $ReportMerchant->where('date', '>=', $Date)
                                             ->where('date', '<', 25)
                                             ->where('month',(int)$Month)
                                             ->where('year',(int)$Year);
        }else{
            $ReportMerchant = $ReportMerchant->where(function($query) use($Date, $Month, $Year, $NextMonth, $NextYear){
                                        $query->where(function($q) use($Date, $Month, $Year){
                                                $q->where('date', '>=', $Date)
                                                    ->where('month',(int)$Month)
                                                    ->where('year',(int)$Year);
                                        })->orWhere(function($q) use($Date, $NextMonth, $NextYear){
                                            $q->where('date', '<', 25)
                                                ->where('month',(int)$NextMonth)
                                                ->where('year',(int)$NextYear);
                                        });
                                });
        }

        $ReportMerchant = $ReportMerchant->groupBy('user_id')
                                            ->first(array(DB::raw(
                                                'user_id,
                                               sum(sc_pvc) as sc_pvc,
                                               sum(sc_cod) as sc_cod,
                                               sum(sc_pbh) as sc_pbh,
                                               sum(sc_discount_pvc) as sc_discount_pvc,
                                               sum(sc_discount_cod) as sc_discount_cod'
                                            )));

        if(!empty($ReportMerchant)){
            $Merchant->total_firstmonth = $ReportMerchant->sc_pvc + $ReportMerchant->sc_cod
                                                                    + $ReportMerchant->sc_pbh - $ReportMerchant->sc_discount_pvc
                                                                    - $ReportMerchant->sc_discount_cod;
        }

//        Lũy kế
//        Nếu bắt đầu trước ngày 25 thì lũy kế tính từ 25 tháng đó đến ngày hiện tại tháng sau
//        Nếu bắt đầu sau ngày 25 thì lũy kế tính từ ngày 25 tháng sau đến ngày hiện tại tháng sau
        $ReportMerchantModel    = new accountingmodel\ReportMerchantModel;
        $ReportMerchantModel    = $ReportMerchantModel::where('user_id',$Merchant->user_id);
        $ReportMerchant         = [];
        if($Date < 25){
            if($Month != date('n') || date('d') >=  25){ // có tính lũy kế
                $ReportMerchant    = $ReportMerchantModel->where(function($query) use($Date, $Month, $Year, $NextMonth, $NextYear){
                    $query = $query->where(function($q) use($Month, $Year){
                        $q->where('date', '>=', 25)
                          ->where('month', $Month)
                          ->where('year', $Year);
                    });

                    if($Month != date('n')){ // sang tháng mới sẽ từ đầu tháng tới ngày cuối
                        $query = $query->orWhere(function($q) use($Date, $NextMonth, $NextYear){
                            $q->where('date', '<', $Date)
                                ->where('month', $NextMonth)
                                ->where('year', $NextYear);
                        });
                    }
                })->groupBy('user_id')
                ->first(array(DB::raw(
                            'user_id,
                           sum(sc_pvc) as sc_pvc,
                           sum(sc_cod) as sc_cod,
                           sum(sc_pbh) as sc_pbh,
                           sum(sc_discount_pvc) as sc_discount_pvc,
                           sum(sc_discount_cod) as sc_discount_cod'
                )));
            }
        }else{
            if($Month != date('n') || date('d') >= 25){
                $ReportMerchant    = $ReportMerchantModel->where('date', '>=', 25)
                                                              ->where('date', '<', $Date)
                                                              ->where('month', $NextMonth)
                                                              ->where('year', $NextYear)->groupBy('user_id')
                                                                ->first(array(DB::raw(
                                                                    'user_id,
                                                                       sum(sc_pvc) as sc_pvc,
                                                                       sum(sc_cod) as sc_cod,
                                                                       sum(sc_pbh) as sc_pbh,
                                                                       sum(sc_discount_pvc) as sc_discount_pvc,
                                                                       sum(sc_discount_cod) as sc_discount_cod'
                                                                )));
            }
        }

        if(isset($ReportMerchant->user_id)){
            $Merchant->total_nextmonth = $ReportMerchant->sc_pvc + $ReportMerchant->sc_cod
                                        + $ReportMerchant->sc_pbh - $ReportMerchant->sc_discount_pvc
                                        - $ReportMerchant->sc_discount_cod;
        }

        if(($this->time() - $FistOrderTime) > 86400*$ListMonth[(int)$Month]){
            $Merchant->release = 1;
        }

        try{
            $Merchant->save();
        }catch (Exception $e){
            return ['error' => false, 'message' => 'UPDATE_FAIL'];
        }

        return ['error' => false, 'message' => 'SUCCESS', 'user_id' => $Merchant->user_id];
    }

    public function getReportcourrier(){
        set_time_limit(300000);
        /* Thời gian lấy hàng trung bình =  thời gian lấy hàng - thời gian duyệt sang hvc ( trừ các trường hợp lấy hàng gặp sự cố)
        time_pickup   -  total_pickup

        Thời gian chờ phát hàng trung bình = thời gian chờ phát hàng đâu tiên - thời gian lấy hàng (các trường hợp có trạng thái 50)
        time_pending_delivery - total_pending

        Thời gian xác nhận chuyển hoàn trung bình = thời gian xác nhận chuyển hoàn - thời gian phát không thành công (các trường hợp có thời gian xác nhận chuyển hoàn và thời gian phát không thành công)
        time_confirm_return - total_confirm

        Thời gian giao hàng thành công trung bình = thời gian giao thành công  - thời gian lấy hàng (trừ các trường hợp giao hàng gặp sự cố)
        time_delivery - total_delivery

        Thời gian chuyển hoàn trung bình = thời gian chuyển hoàn thành công - thời gian lấy hàng
        time_return - total_return
        */

        $Courier    = Input::has('courier') ? trim(Input::get('courier')) : 0;
        $Service    = Input::has('service') ? trim(Input::get('service')) : 0;
        $City       = Input::has('city')    ? (int)Input::get('city') : 0;

        if($Courier == 0 || $Service == 0){
            return ['error' => true, 'message' => 'COURIER_SERVICE_EMPTY'];
        }

        $DateMonth = date("Y-m-d",strtotime("first day of previous month"));
        //$DateMonth  = '2015-8-1';
        $Month = explode('-',$DateMonth);
        $Year  = $Month[0];
        $Month = $Month[1];

        $TimeStart  = strtotime($DateMonth);
        $TimeEnd    = strtotime(date("Y-m"));

        $OrderModel     = new ordermodel\OrdersModel;
        $StatusModel    = new ordermodel\StatusModel;
        $ReportModel    = new CourierEstimateModel;

        /*if($ReportModel->where('year',$Year)->where('month',$Month)->where('courier', $Courier)->where('service_id',$Service)->count() > 0){
            return ['error' => false, 'message' => 'EXISTS'];
        }*/

        $OrderStart = ordermodel\OrdersModel::where('time_create','>=',$TimeStart - $this->time_limit)
                                 ->where('time_create','<',$TimeEnd)
                                 ->where('courier_id',$Courier)
                                 ->where('service_id',$Service)
                                 ->where('from_city_id',$City)
                                 ->whereIn('status',[52,53,66,67])
                                 ->where('time_success', '>=', $TimeStart)
                                 ->where('time_success', '<', $TimeEnd)
                                 ->first([DB::raw('MAX(id) as max , MIN(id) as min')]);

        if(isset($OrderStart['min']) && isset($OrderStart['max'])){
            // calculate time pending delivery
            $Insert = [];
            ordermodel\OrdersModel::where('time_create','>=',$TimeStart - $this->time_limit)
               ->where('time_create','<',$TimeEnd)
                ->where('courier_id',$Courier)
                ->where('service_id',$Service)
                ->whereIn('status',[52,53,66,67])
               ->where('from_city_id',$City)
                ->where('time_success', '>=', $TimeStart)
                ->where('time_success', '<', $TimeEnd)
                ->whereRaw('time_success > time_pickup')
                ->whereRaw('time_pickup > time_approve')
                ->with(['OrderStatus'   => function($query) use($OrderStart){
                    $query->where('order_id','>=',$OrderStart['min'])
                          ->where('order_id','<=',$OrderStart['max'])
                          ->whereIn('status', [31,32,33,34,54,55,56,57,58,59,50,61])
                          ->orderBy('time_create', 'DESC');
                }])
               ->chunk('1000', function($query) use(&$Insert, &$Month, &$Year) {
                   foreach ($query as $val) {
                       $val = $val->toArray();

                       $key                = (int)$val['from_district_id'] . '-' . (int)$val['to_district_id'];
                       $CheckPickup        = false; // kiểm tra xem có bị gặp sự cố khi lấy hàng không
                       $CheckDelivery      = false; // kiểm tra xem đơn có bị gặp sự cố khi giao hàng không

                       if(!isset($Insert[$key])){
                           $Insert[$key]   = [
                               'month'                     => $Month,
                               'year'                      => $Year,
                               'courier'                   => (int)$val['courier_id'],
                               'service_id'                => (int)$val['service_id'],
                               'from_district_id'          => (int)$val['from_district_id'],
                               'to_district_id'            => (int)$val['to_district_id'],
                               'total_pickup'              => 1,
                               'total_pending'             => 0,
                               'total_success'             => 0,
                               'total_confirm'             => 0,
                               'total_return'              => 0,
                               'time_pickup'               => $val['time_pickup'] - $val['time_approve'],
                               'time_pending_delivery'     => 0,
                               'time_delivery'             => 0,
                               'time_confirm_return'       => 0,
                               'time_return'               => 0,
                               'time_create'               => $this->time()
                           ];

                           $TimeError      = 0;
                           $TimeConfirm    = 0;

                           if(in_array($val['status'], [66,67])){
                               $Insert[$key]['total_return']       = 1;
                               $Insert[$key]['time_return']        = $val['time_success'] - $val['time_pickup'];
                           }else{
                               $Insert[$key]['total_success']      = 1;
                               $Insert[$key]['time_delivery']      = $val['time_success'] - $val['time_pickup'];
                           }

                           if(!empty($val['order_status'])){
                               foreach($val['order_status'] as $v){
                                   if(in_array($val['status'], [66,67]) && $v['status'] == 61){ // chuyển hoàn
                                       $TimeConfirm    = $v['time_create'];
                                   }

                                   if($v['status'] == 50 && ($v['time_create'] > $val['time_pickup'])){
                                       $Insert[$key]['total_pending']          = 1;
                                       $Insert[$key]['time_pending_delivery']  = $v['time_create'] - $val['time_pickup'];
                                   }

                                   if(in_array((int)$v['status'], [31,32,33,34])){
                                       $CheckPickup  = true;
                                   }

                                   if(in_array((int)$v['status'], [54,55,56,57,58,59])){
                                       $CheckDelivery  = true;
                                       $TimeError      = $v['time_create'];
                                   }
                               }
                           }

                           if(!empty($TimeDelivery) && !empty($TimeConfirm) && !empty($TimeError)){
                               $Insert[$key]['total_confirm']        = 1;
                               $Insert[$key]['time_confirm_return']  = $TimeConfirm - $TimeError;
                           }

                           // có trạng thái lấy thất bại
                           if($CheckPickup){
                               $Insert[$key]['total_pickup']           = 0;
                               $Insert[$key]['time_pickup']            = 0;
                           }

                           // có trạng thái phát thất bại
                           if($CheckDelivery){
                               $Insert[$key]['total_success']          = 0;
                               $Insert[$key]['time_delivery']          = 0;
                           }

                       }else{
                           $TimePending    = 0;
                           $TimeError      = 0;
                           $TimeConfirm    = 0;

                           if(!empty($val['order_status'])){
                               foreach($val['order_status'] as $v){
                                   if(in_array($val['status'], [66,67]) && $v['status'] == 61){
                                       $TimeConfirm    = $v['time_create'];
                                   }

                                   if($v['status'] == 50  && ($v['time_create'] > $val['time_pickup'])) {
                                       $TimePending  = $v['time_create'] - $val['time_pickup'];
                                   }

                                   if(in_array((int)$v['status'], [31,32,33,34])){
                                       $CheckPickup  = true;
                                   }

                                   if(in_array((int)$v['status'], [54,55,56,57,58,59])){
                                       $CheckDelivery  = true;
                                       $TimeError      = $v['time_create'];
                                   }
                               }
                           }

                           // time_pickup
                           if(!$CheckPickup){
                               if($Insert[$key]['time_pickup'] > 0){
                                   $Insert[$key]['time_pickup']              = ($Insert[$key]['time_pickup']*($Insert[$key]['total_pickup']) + ($val['time_pickup'] - $val['time_approve']))/($Insert[$key]['total_pickup'] + 1);
                               }else{
                                   $Insert[$key]['time_pickup']              =   $val['time_pickup'] - $val['time_approve'];
                               }
                               $Insert[$key]['total_pickup']   += 1;
                           }

                           // time_pending_delivery
                           if($TimePending > 0){
                               if($Insert[$key]['time_pending_delivery'] > 0){
                                   $Insert[$key]['time_pending_delivery']    = ($Insert[$key]['time_pending_delivery']*$Insert[$key]['total_pending'] + $TimePending)/($Insert[$key]['total_pending'] + 1);
                               }else{
                                   $Insert[$key]['time_pending_delivery']  = $TimePending;
                               }
                               $Insert[$key]['total_pending']   += 1;
                           }

                           if(in_array($val['status'], [66,67])){
                               // time_confirm_return
                               if($TimeConfirm > 0 && $TimeError > 0){
                                   if($Insert[$key]['time_confirm_return'] > 0){
                                       $Insert[$key]['time_confirm_return']    = ($Insert[$key]['time_confirm_return']*$Insert[$key]['total_confirm'] + ($TimeConfirm - $TimeError))/($Insert[$key]['total_confirm'] + 1);
                                   }else{
                                       $Insert[$key]['time_confirm_return']    = $TimeConfirm - $TimeError;
                                   }
                                   $Insert[$key]['total_confirm']   += 1;
                               }

                               // time_return
                               if($Insert[$key]['total_return'] > 0){
                                   $Insert[$key]['time_return']            = ($Insert[$key]['time_return']*$Insert[$key]['total_return'] + $val['time_success'] - $val['time_pickup'])/($Insert[$key]['total_return'] + 1);
                               }else{
                                   $Insert[$key]['time_return']            =   $val['time_success'] - $val['time_pickup'];
                               }
                               $Insert[$key]['total_return'] += 1;
                           }else{
                               // time_success
                               if(!$CheckDelivery){
                                   if($Insert[$key]['total_success'] > 0){
                                       $Insert[$key]['time_delivery']            = ($Insert[$key]['time_delivery']*$Insert[$key]['total_success'] + $val['time_success'] - $val['time_pickup'])/($Insert[$key]['total_success'] + 1);
                                   }else{
                                       $Insert[$key]['time_delivery']            =  $val['time_success'] - $val['time_pickup'];
                                   }
                                   $Insert[$key]['total_success'] += 1;
                               }
                           }

                       }
                   }
               });

            if(!empty($Insert)){
                $i = 0;
                $ListInsert = [];
                foreach($Insert as $val) {
                    $i++;
                    $ListInsert[$i % 10][] = $val;
                }

                $CourierEstimateModel   = new CourierEstimateModel;
                try{
                    foreach($ListInsert as $val){
                        $CourierEstimateModel->insert($val);
                    }

                }catch (Exception $e){
                    return ['error' => false, 'message' => 'INSERT_FAIL_'.$City];
                }

                return ['error' => false, 'message' => 'SUCCESS_'.$City];
            }
        }

        return ['error' => false, 'message' => 'ERROR_'.$City];
    }

    public function getSyncpromise(){return 1;
        $CourierEstimateModel   = new CourierEstimateModel;
        $Estimate   = $CourierEstimateModel::where('active',1)
                                            ->orderBy('time_create','ASC')
                                            ->orderBy('id','ASC')
                                            ->first();

        if(!isset($Estimate->id)){
            return ['error' => false, 'message' => 'EMPTY'];
        }

        if($Estimate->time_delivery > 0 || $Estimate->time_return > 0){
            // Delivery
            $CourierPromiseModel    = new systemmodel\CourierPromiseModel;
            $CourierPromise = $CourierPromiseModel::firstOrNew([
                'courier_id'    => (int)$Estimate->courier,
                'service_id'    => (int)$Estimate->service_id,
                'from_district' => (int)$Estimate->from_district_id,
                'to_district'   => (int)$Estimate->to_district_id
            ]);

            if(!$CourierPromise->exists){ // đã tồn tại
                $CourierPromise->estimate_delivery  = 0;
                $CourierPromise->estimate_return    = 0;
                $CourierPromise->num_delivery       = 0;
                $CourierPromise->num_return         = 0;
            }

            if($Estimate->time_delivery > 0){
                $CourierPromise->estimate_delivery    = ($CourierPromise->estimate_delivery*$CourierPromise->num_delivery + $Estimate->time_delivery)/($CourierPromise->num_delivery + 1);
                $CourierPromise->num_delivery++;
            }

            if($Estimate->time_return > 0){
                $CourierPromise->estimate_return      = ($CourierPromise->estimate_return*$CourierPromise->num_return + $Estimate->time_return)/($CourierPromise->num_return + 1);
                $CourierPromise->num_return++;
            }

            $CourierPromise->time_update    = $this->time();
            try{
                $CourierPromise->save();
            }catch (Exception $e){
                return ['error' => true, 'message' => 'UPDATE_DELIVERY_FAIL'];
            }
        }

        if($Estimate->time_pickup > 0){
            // Pickup
            $PickupPromiseModel     = new CourierPromiseModel;
            $PickupPromise          = $PickupPromiseModel::firstOrNew([
                'courier_id'    => (int)$Estimate->courier,
                'service_id'    => (int)$Estimate->service_id,
                'district_id'   => (int)$Estimate->from_district_id
            ]);

            if(!$PickupPromise->exists){ // đã tồn tại
                $PickupPromise->estimate_pickup     = 0;
                $PickupPromise->num                 = 0;
            }

            $PickupPromise->estimate_pickup      = ($PickupPromise->estimate_pickup*$PickupPromise->num + $Estimate->time_pickup)/($PickupPromise->num + 1);
            $PickupPromise->num++;
            $PickupPromise->time_update    = $this->time();

            try{
                $PickupPromise->save();
            }catch (Exception $e){
                return ['error' => true, 'message' => 'UPDATE_PICKUP_FAIL'];
            }
        }

        try{
            $Estimate->active   = 2;
            $Estimate->save();
        }catch (Exception $e){
            return ['error' => true, 'message' => 'UPDATE_ESTIMATE_FAIL'];
        }

        return ['error' => false, 'message' => 'SUCCESS'];
    }

    public function getSyncPromiseCourier(){
        $CourierPromiseModelDev = new systemmodel\CourierPromiseModelDev;
        $Promise                = $CourierPromiseModelDev::where('active',0)->orderBy('id','ASC')->first();

        if(!isset($Promise->id)){
            return ['error' => true, 'message' => 'EMPTY'];
        }

        $CourierEstimateModel   = new CourierEstimateModel;
        $Estimate               = $CourierEstimateModel::where('courier', $Promise->courier_id)
                                                        ->where('service_id', $Promise->service_id)
                                                        ->where('time_delivery', '>', 0)
                                                        ->where('from_district_id', $Promise->from_district)
                                                        ->where('to_district_id', $Promise->to_district)
                                                        ->orderBy('year','DESC')
                                                        ->orderBy('month','DESC')
                                                        ->take(3)
                                                        ->get()->toArray();

        if(count($Estimate) == 3){
            foreach($Estimate as $val){
                $Promise->estimate_delivery +=  $val['time_delivery'];
            }
            $Promise->estimate_delivery = $Promise->estimate_delivery/3;
        }

        $Promise->active = 1;
        try{
            $Promise->save();
            return ['error' => false, 'message' => 'SUCCESS'];
        }catch (Exception $e){
            return ['error' => false, 'message' => 'ERROR'];
        }
    }

    public function getSyncticketorder(){
        $Error          = false;
        $ReferModel     = new ticketmodel\ReferModel;
        $Refer          = $ReferModel::where('sync',0)->where('type',1)->orderBy('id','ASC')->first();
        if(!isset($Refer->id) || empty($Refer->code)){
            return ['error' => false, 'message' => 'EMPTY'];
        }

        $CaseTicketModel = new ticketmodel\CaseTicketModel;
        $Case            = $CaseTicketModel::where('ticket_id',$Refer->ticket_id)
                                           ->where('active',1)->get();

        if(empty($Case)){
            $Refer->sync = 2;
            $Message     = 'CASE_EMPTY';
        }

        $CaseTypeController = new ticket\CaseTypeController;
        $CaseType           = $CaseTypeController->getIndex(false);
        if(!isset($CaseType) || $CaseType['error'] || !empty($CaseType['data'])){
            $Error       = true;
            $Message     = 'CASE_TYPE_EMPTY';
        }

        $CaseType = $CaseType['data'];

        $ListType   = [];
        foreach($CaseType as $val){
            $ListType[(int)$val['id']]  = trim(strtolower($val['code']));
        }

        $ListTag    = [];
        foreach($Case as $val){
            if(!empty($ListType[(int)$val['type_id']])){
                $ListTag[]  = $ListType[(int)$val['type_id']];
            }
        }

        if(!empty($ListTag)){
            $OrderModel = new ordermodel\OrdersModel;
            $Order      = $OrderModel::where('time_accept','>=',$this->time() - $this->time_limit)->where('tracking_code',trim($Refer->code))->first(['id','time_accept','tracking_code']);
            if(!isset($Order->id)){
                $Refer->sync = 3;
                $Message     = 'ORDER_NOT_EXISTS';
            }

            $Tag    = [];
            if(!empty($Order->tag)){
                $Tag = explode(',',$Order->tag);
            }

            if(!empty($Tag)){
                $ListTag    = array_unique(array_merge($ListTag, $Tag));
            }

            try{
                $OrderModel = new ordermodel\OrdersModel;
                $OrderModel->where('time_accept','>=',$this->time() - $this->time_limit)->where('id', $Order->id)->update(['tag' => implode(',',$ListTag)]);
            }catch (Exception $e){
                $Error       = true;
                $Message     = 'UPDATE_ERROR';
            }
            $Refer->sync = 1;
            $Message     = 'SUCCESS';

        }else{
            $Refer->sync = 4;
            $Message     = 'EMPTY_TAG';
        }

        try{
            if($Error){
                $Refer->save();
            }
        }catch (Exception $e){
            return ['error' => false, 'message' => 'UPDATE_ERROR'];
        }

        return ['error' => false, 'message' => $Message];
    }

    /*
     * Cập nhật khách hàng quay lại
     */
    public function getUpdateMerchantReturn(){
        $SellerModel            = new omsmodel\SellerModel;
        $LogSellerModel         = new omsmodel\LogSellerModel;

        $Seller                 = $SellerModel::where('last_time_pickup','>',0)
                                              ->where('last_time_pickup','<',$this->time() - 86400*30)
                                              ->where('release',1)
                                              ->orderBy('last_time_pickup','ASC')
                                              ->first();

        if(!isset($Seller->id)){
            return ['error' => false, 'message' => 'EMPTY'];
        }

        //Update UserInfo
        try{
            sellermodel\UserInfoModel::where('user_id', $Seller->user_id)->update(['pipe_status' => 400, 'time_update' => $this->time()]);
        }catch (Exception $e){
            return ['error' => false, 'message' => 'UPDATE_USER_INFO_ERROR'];
        }

        DB::connection('omsdb')->beginTransaction();

        //Insert log seller
        $Insert = [
            'user_id'               => $Seller->user_id,
            'seller_id'             => $Seller->seller_id,
            'total_firstmonth'      => $Seller->total_firstmonth,
            'total_nextmonth'       => $Seller->total_nextmonth,
            'first_time_pickup'     => $Seller->first_time_pickup,
            'last_time_pickup'      => $Seller->last_time_pickup,
            'first_time_incomings'  => $Seller->first_time_incomings,
            'time_create'           => $this->time(),
            'active'                => $Seller->active
        ];

        // Update Seller
        $Seller->total_firstmonth       = 0;
        $Seller->total_nextmonth        = 0;
        $Seller->first_time_pickup      = 0;
        $Seller->first_time_incomings   = 0;
        $Seller->last_time_pickup       = 0;
        $Seller->release                = 0;
        $Seller->activity_status        = 0;
        $Seller->active                 = 1;

        try{
            $LogSellerModel->insert($Insert);
            $Seller->save();
            DB::connection('omsdb')->commit();
        }catch (Exception $e){
            DB::connection('omsdb')->rollBack();
            return ['error' => true, 'message' => $e->getMessage(), 'data' => $Seller->user_id];
        }

        return ['error' => false, 'message' => 'SUCCESS', 'data' => $Seller->user_id];
    }

    function get_timezone_offset($remote_tz, $origin_tz = null) {
        if($origin_tz === null) {
            if(!is_string($origin_tz = date_default_timezone_get())) {
                return false; // A UTC timestamp was returned -- bail out!
            }
        }
        $origin_dtz = new DateTimeZone($origin_tz);
        $remote_dtz = new DateTimeZone($remote_tz);
        $origin_dt = new DateTime("now", $origin_dtz);
        $remote_dt = new DateTime("now", $remote_dtz);
        $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
        return $offset;
    }

    public function getTest1(){
        $LMongo         = new \LMongo;
        return $LMongo::collection('log_journey_delivery')->whereIn('tracking_code',["SC51291124466",
            "SC51294880328",
            "SC51994563718",
            "SC5649806003",
            "SC5335419666",
            "SC51644052169",
            "SC51891444515",
            "SC51901573331",
            "SC51538456131",
            "SC51442157283",
            "SC51900555256",
            "SC5731638210",
            "SC5321829468",
            "SC51154042693",
            "SC51784823636",
            "SC51941100914",
            "SC5627765235",
            "SC5859798973",
            "SC5292244035",
            "SC51656276855",
            "SC51432349700",
            "SC51851492142",
            "SC51169702557",
            "SC5168894185",
            "SC546313937",
            "SC5243460216",
            "SC574652995",
            "SC510256087",
            "SC5582072634",
            "SC5480645947",
            "SC51584763981",
            "SC51011942237",
            "SC51483577059",
            "SC5352770292",
            "SC5786462464",
            "SC5912577544",
            "SC51666034274",
            "SC51278611079",
            "SC5221567835",
            "SC51412380318",
            "SC5912377105",
            "SC51627100708",
            "SC5806234505",
            "SC512190343",
            "SC5852189193",
            "SC51295035565",
            "SC51674203813",
            "SC5177879495",
            "SC51602902336",
            "SC5360371255",
            "SC5795420245",
            "SC5426501854",
            "SC51846940836",
            "SC51574400235",
            "SC51859531009",
            "SC5969029698",
            "SC5530359062",
            "SC52105366733",
            "SC51612060065",
            "SC51340045836",
            "SC51234368112",
            "SC51408752807",
            "SC5952445873",
            "SC51363867479",
            "SC5804676876",
            "SC5632047452",
            "SC51166852669",
            "SC5603625890",
            "SC51830926380",
            "SC51035496353",
            "SC5701994632",
            "SC522986380",
            "SC51410806632",
            "SC5470104074",
            "SC52000361779",
            "SC5424439350",
            "SC5621972352",
            "SC52106315139",
            "SC5393581198",
            "SC51880470505",
            "SC5551998291",
            "SC51749099538",
            "SC5647458699",
            "SC5192022338",
            "SC51766566166",
            "SC51214363751",
            "SC5893877779",
            "SC534316606",
            "SC51492642242",
            "SC5155520733",
            "SC5948824567",
            "SC51526675218",
            "SC5109930340",
            "SC51149207888",
            "SC5216804924",
            "SC542645304",
            "SC5608585612",
            "SC5146213115",
            "SC51351446805",
            "SC51303982788",
            "SC52100600312",
            "SC51491255115",
            "SC51471995417",
            "SC5893982948",
            "SC544959202",
            "SC5959334430",
            "SC5941336782",
            "SC5114826064",
            "SC51731220181",
            "SC5393765512",
            "SC52007280419",
            "SC51542046506",
            "SC5367740901",
            "SC51893703006",
            "SC5665089564",
            "SC51106340678",
            "SC51588795343",
            "SC5915347968",
            "SC51901891520",
            "SC5197098722",
            "SC5995634738",
            "SC5642627981",
            "SC529489524",
            "SC533250914",
            "SC51668606518",
            "SC5767532091",
            "SC51979550309",
            "SC5137183647",
            "SC5651038755",
            "SC5370355267",
            "SC51249934502",
            "SC51897034235",
            "SC5570539291",
            "SC5363489531",
            "SC5145215041",
            "SC51971758634",
            "SC51471269618",
            "SC51689672912",
            "SC51730715544",
            "SC5643003787",
            "SC5140262954",
            "SC52106569661",
            "SC5241403544",
            "SC51585839162",
            "SC51793732105",
            "SC5170480827",
            "SC5152100591",
            "SC51184051646",
            "SC5273412423",
            "SC51445270150",
            "SC51705718240",
            "SC51885878828",
            "SC5610918001",
            "SC51170629660",
            "SC51205557698",
            "SC513535395",
            "SC51616986091",
            "SC5832910181",
            "SC51554070731",
            "SC5572529766",
            "SC5172262252",
            "SC5497807089",
            "SC5555173098",
            "SC51384009778",
            "SC5299058095",
            "SC52104339805",
            "SC52129828623",
            "SC5751112688",
            "SC5693538371",
            "SC51248734330",
            "SC51217122032",
            "SC5268576495",
            "SC51113042769",
            "SC51414447912",
            "SC51114750919",
            "SC51634079345",
            "SC568424557",
            "SC51191522815",
            "SC5699427132",
            "SC51932462883",
            "SC52005728653",
            "SC5810527297",
            "SC5799922999",
            "SC5918126280",
            "SC588566118",
            "SC5884059956",
            "SC5191367404",
            "SC51936507147",
            "SC51408153658",
            "SC51465389024",
            "SC51216062364",
            "SC5418045970",
            "SC51480623026",
            "SC5998238814",
            "SC51085133271",
            "SC51514100785",
            "SC5689420988",
            "SC5345489421",
            "SC574745561",
            "SC51133347299",
            "SC5237347472",
            "SC5995035001",
            "SC5182727464",
            "SC522916296",
            "SC5418043777",
            "SC51334544829",
            "SC51783453233",
            "SC51606178734",
            "SC51439442931",
            "SC5745101338",
            "SC5741911149",
            "SC51144392759",
            "SC51137076870",
            "SC51494362007",
            "SC5843470514",
            "SC5842584121",
            "SC5863252332",
            "SC5812969915",
            "SC51895508916",
            "SC51228338172",
            "SC5907964336",
            "SC51994225660",
            "SC51235570992",
            "SC5463273797",
            "SC52012906257",
            "SC51704784938",
            "SC582810690",
            "SC51121956322",
            "SC51366809570",
            "SC51924917223",
            "SC51704571809",
            "SC516073097",
            "SC51066915980",
            "SC51983891381",
            "SC52011133332",
            "SC51526970720",
            "SC5454843557",
            "SC5667284023",
            "SC51584190860",
            "SC51690161708",
            "SC51915867596",
            "SC5451762147",
            "SC5807964719",
            "SC51184380060",
            "SC51190506853",
            "SC51534962525",
            "SC5651220811",
            "SC5562321195",
            "SC5792415377",
            "SC51652644788",
            "SC5563781629",
            "SC51587807545",
            "SC5456630304",
            "SC5608371359",
            "SC51876697262",
            "SC5131767337",
            "SC51817883648",
            "SC5960702026",
            "SC51871486553",
            "SC51074419962",
            "SC51958302563",
            "SC51806961631",
            "SC5385357336",
            "SC5305484006",
            "SC5232145715",
            "SC5638302593",
            "SC549680985",
            "SC51048955333",
            "SC5916964644",
            "SC52021339013",
            "SC5456896940",
            "SC5634347387",
            "SC51989242836",
            "SC5144173275",
            "SC51920170470",
            "SC51290704590",
            "SC51324382141",
            "SC5784792042",
            "SC5480994265",
            "SC5130311960",
            "SC5122518439",
            "SC51239954549",
            "SC52026152728",
            "SC5128949470",
            "SC551088781",
            "SC51488701516",
            "SC5338295317",
            "SC5547864530",
            "SC51948079981",
            "SC5896096268",
            "SC5751832005",
            "SC513036032",
            "SC5693670156",
            "SC5519101529",
            "SC5379631362",
            "SC51819167085",
            "SC51741738332",
            "SC51856566485",
            "SC5101727216",
            "SC5369991709",
            "SC51073070974",
            "SC5737316020",
            "SC52129475383",
            "SC5922584795",
            "SC5700462604",
            "SC5674123719",
            "SC5962684660",
            "SC51811608012",
            "SC51900352245",
            "SC51790583854",
            "SC5724064837",
            "SC5844822463",
            "SC51548176568",
            "SC51861068544",
            "SC5732116919",
            "SC51066323348",
            "SC51871293884",
            "SC51389155205",
            "SC51860330228",
            "SC5445644938",
            "SC51422645632",
            "SC51206354001",
            "SC51066763101",
            "SC51318635107",
            "SC51757114503",
            "SC5160909040",
            "SC51419421031",
            "SC51416913645",
            "SC51385628864",
            "SC51229446395",
            "SC51144596304",
            "SC51587664167",
            "SC51000405869",
            "SC5601748842",
            "SC51377294660",
            "SC51585010339",
            "SC5240447183",
            "SC51960599743",
            "SC51125654881",
            "SC52041792561",
            "SC51407452878",
            "SC5604877460",
            "SC51367530204",
            "SC51018288099",
            "SC51275620184",
            "SC5681928416",
            "SC51856901680",
            "SC51726969823",
            "SC5587261261",
            "SC5683957750",
            "SC51706478730",
            "SC51231646663",
            "SC51663565190",
            "SC51923645230",
            "SC5656493120",
            "SC5634830681",
            "SC555296716",
            "SC5404769106",
            "SC51718863135",
            "SC51434738200",
            "SC51708584100",
            "SC5722193809",
            "SC52104096634",
            "SC51597920181",
            "SC51725808993",
            "SC5251525847",
            "SC51239646360",
            "SC5153064642",
            "SC51601479213",
            "SC5587485300",
            "SC51764644995",
            "SC5998574667",
            "SC51405985300",
            "SC5579131800",
            "SC5754042907",
            "SC5960785179",
            "SC51349764168",
            "SC51698970463",
            "SC5627051099",
            "SC566655218",
            "SC51201284394",
            "SC52008848525",
            "SC51366353728",
            "SC51013539617",
            "SC5731268785",
            "SC5875731329",
            "SC5630024844",
            "SC51743191000",
            "SC51391200100",
            "SC5203829145",
            "SC51702966461",
            "SC598812355",
            "SC5186200340",
            "SC52107266562",
            "SC52019979895",
            "SC5378725450",
            "SC51223016884",
            "SC5248014316",
            "SC51236097543",
            "SC51039237806",
            "SC5355005515",
            "SC51629551663",
            "SC5448341446",
            "SC5438785553",
            "SC51697094254",
            "SC51920016645",
            "SC51752961429",
            "SC5743714767",
            "SC5386385070",
            "SC523501217",
            "SC51554488538",
            "SC5783443552",
            "SC51110976908",
            "SC51115706703",
            "SC5552483444",
            "SC5539430707",
            "SC52094973092",
            "SC5255864300",
            "SC5616000073",
            "SC51957272866",
            "SC5792437799",
            "SC51439140564",
            "SC51130187094",
            "SC51060821150",
            "SC51663378140",
            "SC5547311544",
            "SC537690802",
            "SC51039667791",
            "SC5202041150",
            "SC545070755",
            "SC5581297650",
            "SC51803865485",
            "SC5288942924",
            "SC5661890449",
            "SC5554001107",
            "SC5434375623",
            "SC5211616939",
            "SC5860240588",
            "SC51517344390",
            "SC51319126868",
            "SC5991726308",
            "SC5788116467",
            "SC51779157369",
            "SC51079119162",
            "SC5958223295",
            "SC51041552166",
            "SC51357467181",
            "SC5328931396",
            "SC5998715518",
            "SC5309047293",
            "SC5956448733",
            "SC51238577912",
            "SC5931629230",
            "SC51356599398",
            "SC51678068663",
            "SC52145131737",
            "SC5113752888",
            "SC51294228253",
            "SC51815115462",
            "SC51038523747",
            "SC51181727899",
            "SC51989235467",
            "SC5148471652",
            "SC51601588166",
            "SC5213835963",
            "SC5835113075",
            "SC5261116042",
            "SC5702366382",
            "SC5592453537",
            "SC576903874",
            "SC521876169",
            "SC51799858468",
            "SC5470677194",
            "SC5278195723",
            "SC5218974442",
            "SC51737145301",
            "SC5801112571",
            "SC5503632463",
            "SC586708393",
            "SC51568719994"])->update(['active' => 0]);
    }

    /*
     * Báo cáo
     */
    public function getExcel(){return $this->time();
        $Model          = new ordermodel\OrdersModel;
        $OrderStatus    = new ordermodel\StatusModel;

        /*  Xảy ra khiếu nại
         $Data       = $Model::where('time_pickup','>=',1433091600)->where('time_pickup','<',1436547600)
            ->where('tag','<>','')
            ->orderBy('time_pickup','ASC')
            ->get(['tracking_code','status','time_pickup','time_update'])->toArray();

        Thành công
        $Data       = $Model::where('time_pickup','>=',1433091600)->where('time_pickup','<',1436547600)
                            ->where('status',52)
                            ->orderBy('time_pickup','ASC')
                            ->skip(60000)->take(20000)
                            ->get(['tracking_code','status','time_pickup','time_update'])->toArray();

        Chuyển hoàn
         $Data       = $Model::where('time_pickup','>=',1433091600)->where('time_pickup','<',1436547600)
            ->whereIn('status',[62,63,64,65,66])
            ->orderBy('time_pickup','ASC')
            ->get(['tracking_code','status','time_pickup','time_update'])->toArray();
        */
        // Trải qua trạng thái phát không thành công lần 1
        $Data       = $Model::where('time_pickup','>=',1433091600)->where('time_pickup','<',1436547600)
            ->where(function($query){
                $query->whereIn('status',[53,54,55,56,57,58,59,76,77,79,80])
                    ->orWhere('num_delivery','>',1);
            })
            ->orderBy('time_pickup','ASC')
            ->get(['tracking_code','status','time_pickup','time_update'])->toArray();


        $Status     = BaseController::getStatus();

        return \Excel::create('Danh_sach_don_hang_thanh_cong_lan_1', function($excel) use($Data,$Status){
            $excel->sheet('Sheet1', function($sheet) use($Data,$Status){
                $sheet->mergeCells('D1:E1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','Danh sách đơn hàng phát thành công lần 1'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30
                ));

                $sheet->row(3, array(
                    'STT', 'Mã đơn hàng', 'Thời gian lấy hàng', 'Thời gian cập nhật cuối', 'Trạng thái'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });

                $sheet->setBorder('A3:E3', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        isset($val['tracking_code'])        ? $val['tracking_code']                 : '',
                        $val['time_pickup'] > 0             ? date("d/m/Y H:m",$val['time_pickup']) : '',
                        $val['time_update'] > 0             ? date("d/m/Y H:m",$val['time_update']) : '',
                        isset($Status[$val['status']])               ? $Status[$val['status']]                    : '',
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }

    /*
     * Yêu cầu phát lại
     */
    public function getExcelReplay(){
        $Model              = new ordermodel\OrdersModel;
        $OrderStatus        = new ordermodel\StatusModel;
        $CaseTicketModel    = new ticketmodel\CaseTicketModel;
        $ReferModel         = new ticketmodel\ReferModel;

        /*
         * Không liên lạc được
         */
        $Data              = $Model::where('time_pickup','>=',1433091600)->where('time_pickup','<',1435683600)
                                    ->whereNotIn('status',[61,62,63,64,65,66])
                                    ->whereHas('OrderStatus' ,function($query){
                                        $query->whereIn('status',[79, 54, 55 ]);
                                    })
                                    ->get(['id','tracking_code','status','time_pickup','time_update'])->toArray();

        $Status     = BaseController::getStatus();

        return \Excel::create('Danh_sach_yeu_cau_phat_lai', function($excel) use($Data,$Status){
            $excel->sheet('Sheet1', function($sheet) use($Data,$Status){
                $sheet->mergeCells('D1:E1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','Danh sách đơn hàng phát không thành công - yêu cầu giao lại'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30
                ));

                $sheet->row(3, array(
                    'STT', 'Mã đơn hàng', 'Thời gian lấy hàng', 'Thời gian cập nhật cuối', 'Trạng thái'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });

                $sheet->setBorder('A3:E3', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        isset($val['tracking_code'])        ? $val['tracking_code']                 : '',
                        $val['time_pickup'] > 0             ? date("d/m/Y H:m",$val['time_pickup']) : '',
                        $val['time_update'] > 0             ? date("d/m/Y H:m",$val['time_update']) : '',
                        isset($Status[$val['status']])               ? $Status[$val['status']]                    : '',
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }



    /*
     * End Report
     */

    public function getLogQuery(){return date('j');
        DB::listen(function($sql, $bindings, $time){
            $logFile = storage_path('logs/query.log');
            $monolog = new Logger('log');
            $monolog->pushHandler(new StreamHandler($logFile), Logger::INFO);
            $monolog->info($sql, compact('bindings', 'time'));
        });

        $User   = [];
        $OrderModel         = new ordermodel\OrdersModel;
        $SumFee             = $OrderModel::where('time_accept','>',$this->time() - 5184000)
            ->where('from_user_id',3415)
            ->whereNotIn('status',array(20,22,23,24,25,26,27,28,29,31,32,33,34))
            ->where('verify_id',0)
            ->with('OrderDetail')->select(array('id','from_user_id','status','tracking_code'))
            ->chunk('1000', function($query) use(&$User){
                $User = array_merge($User,(array($query)));
            });
            //->get(array('id','from_user_id','status','tracking_code'))->toArray();

        return $User;
        //$users = DB::connection('omsdb')->table('pipe_journey')->remember(10,'log_pipe_journey')->count();
        $users = DB::connection('omsdb')->table('pipe_journey')->count();
        var_dump($users);

        dd('bb');
    }

    /**
     * Report Ticket
     */
    public function getTicket($UserId){
        echo $UserId.' - ';

        $LMongo         = new \LMongo;
        $Data           = $LMongo::collection('log_change_ticket')
            ->where('user_id',(int)$UserId)
            ->whereGte('id',137653)
            ->whereLte('id',159010)
            ->where('type','status')
            ->where('new.status','CLOSED')
            ->get(['id'])->toArray();

        if(!empty($Data)){
            foreach($Data as $val){
                $List[] =  (int)$val['id'];
            }
        }

        $Total = 0;

        if(!empty($List)){
            $Total = ticketmodel\RequestModel::whereIn('id',$List)->where('status','CLOSED')->count();

        }

        return $Total;
    }

    public function getFix(){
        $id   = Input::get('id');
        $id = explode(',',$id);

        foreach($id as $val){
            $Audit  = accountingmodel\AuditTransactionModel::where('status','ERROR')->where('id', (int)$val)->first();

            if(!isset($Audit->id)){
                return ['message' => 'Kết thúc', 'id' => $val];
            }

            $Verify = ordermodel\VerifyModel::where('user_id', $Audit->user_id)->where('time_create','>=',1451581200)->where('status','SUCCESS')->get(['id'])->toArray();
            if(!empty($Verify)){
                $ListVerifyId   = [];
                foreach($Verify as $val){
                    $ListVerifyId[$val['id']] = $val['id'];
                }

                $ListTransaction    = accountingmodel\TransactionModel::where('time_create','>=',1451581200)
                    ->where(function($query) use($Audit){
                        $query->where('from_user_id',$Audit->user_id)
                            ->orWhere('to_user_id',$Audit->user_id);
                    })
                    ->orderBy('time_create','ASC')->orderBy('id','ASC')->get();

                if(empty($ListTransaction)){
                    return ['message' => 'EMPTY TRANSACTION', 'user_id' => $Audit->user_id];
                }

                foreach($ListTransaction as $val){
                    if(isset($ListVerifyId[$val['refer_code']])){
                        unset($ListVerifyId[$val['refer_code']]);
                    }
                }

                if(empty($ListVerifyId)){
                    return ['message' => 'EMPTY VERIFY ID', 'user_id' => $Audit->user_id];
                }

                $ListVerify = ordermodel\VerifyModel::whereIn('id', $ListVerifyId)->where('time_create','>=',1451581200)->orderBy('time_create','ASC')->get()->toArray();
                $DataTransaction    = [];
                foreach($ListVerify as $val){
                    $Payment = $val['total_money_collect'] + $val['balance'] - $val['total_fee'] + ((($val['balance_available'] - $val['config_balance']) < 0 ) ? ($val['balance_available'] - $val['config_balance']) : 0);

                    $DataTransaction[]    =
                        [
                            'refer_code'          => (int)$val['id'],
                            'transaction_id'    => "",
                            'from_user_id'      => (int)$Audit->user_id,
                            'to_user_id'        => (int)1,
                            'money'             => $val['total_fee'],
                            'balance_before'    => 0,
                            'note'              => 'Thanh toán phí vận chuyển cho bảng kê số '.(int)$val['id'],
                            'view'              => 0,
                            'time_create'       => $val['time_accept'],
                            'check'             => 0

                        ];

                    $DataTransaction[] = [
                        'refer_code'          => (int)$val['id'],
                        'transaction_id'    => "",
                        'from_user_id'      => 1,
                        'to_user_id'        => (int)$Audit->user_id,
                        'money'             => $val['total_money_collect'],
                        'balance_before'    => 0,
                        'note'              => 'Nhận thanh toán thu hộ cho bảng kê số '.(int)$val['id'],
                        'view'              => 0,
                        'time_create'       => $val['time_accept'],
                        'check'             => 0

                    ];
                    $DataTransaction[] =
                        [
                            'refer_code'          => (int)$val['id'],
                            'transaction_id'    => $val['transaction_id'],
                            'from_user_id'      => (int)$Audit->user_id,
                            'to_user_id'        => 1,
                            'money'             => $Payment,
                            'balance_before'    => 0,
                            'note'              => 'Rút tiền theo bảng kê số '.(int)$val['id'],
                            'view'              => 0,
                            'time_create'       => $val['time_accept'],
                            'check'             => 0
                        ];
                }

                DB::connection('orderdb')->beginTransaction();
                try{
                    accountingmodel\TransactionModel::insert($DataTransaction);
                }catch(Exception $e){
                    return ['message' => $e->getMessage()];
                }

                try{
                    $Audit->status = 'SUCCESS';
                    $Audit->save();
                    DB::connection('orderdb')->commit();
                }catch(Exception $e){
                    return ['message' => 'UPDATE ERROR', 'user_id' => $Audit->user_id];
                }
            }
        }

        return ['message' => 'thành công', 'user_id' => $id];

        return ['message' => 'EMPTY', 'user_id' => $id];

    }

    public function getFix1(){
        $user   = Input::get('user');
        $user = explode(',',$user);

        foreach($user as $user_id){
            if(!in_array($user,[40103,40665,40785,41038,41524,41752,42122,42344,42395])){
                $ListTranSaction    = accountingmodel\TransactionModel::where('from_user_id',$user_id)->orWhere('to_user_id', $user_id)
                    ->orderBy('time_create','ASC')->orderBy('id','ASC')->get()->toArray();
                $Balance    = null;
                foreach($ListTranSaction as $val){
                    if(!isset($Balance)){
                        $Balance    = $val['balance_before'];
                    }else{
                        if($Balance != $val['balance_before']){
                            accountingmodel\TransactionModel::where('id',$val['id'])->update(['balance_before' => $Balance]);
                        }
                    }


                    if($val['from_user_id'] == $user_id){
                        $Balance    -= $val['money'];
                    }else{
                        $Balance    += $val['money'];
                    }
                }
            }

        }
        return $user;
    }

    public function getFix3(){
        $verify_id   = Input::get('id');
        $verify_id = explode(',',$verify_id);

        foreach($verify_id as $val){
            $Verify = ordermodel\VerifyModel::where('id', (int)$val)->where('status','SUCCESS')->first();
            if(isset($Verify->id)){
                $BalanceFee     = $Verify->balance + (($Verify->balance_available - $Verify->config_balance) < 0 ? ($Verify->balance_available - $Verify->config_balance) : 0);
                $TotalPayment   = $Verify->total_money_collect - $Verify->total_fee + $BalanceFee;

                $DataTransaction[]    =
                    [
                        'refer_code'          => (int)$Verify->id,
                        'transaction_id'    => "",
                        'from_user_id'      => (int)$Verify->user_id,
                        'to_user_id'        => (int)1,
                        'money'             => $Verify->total_fee,
                        'balance_before'    => 0,
                        'note'              => 'Thanh toán phí vận chuyển cho bảng kê số '.(int)$Verify->id,
                        'view'              => 1,
                        'time_create'       => $Verify->time_accept,
                        'check'             => 0

                    ];

                $DataTransaction[] = [
                    'refer_code'          => (int)$Verify->id,
                    'transaction_id'    => "",
                    'from_user_id'      => 1,
                    'to_user_id'        => (int)$Verify->user_id,
                    'money'             => $Verify->total_money_collect,
                    'balance_before'    => 0,
                    'note'              => 'Nhận thanh toán thu hộ cho bảng kê số '.(int)$Verify->id,
                    'view'              => 1,
                    'time_create'       => $Verify->time_accept,
                    'check'             => 0

                ];
                $DataTransaction[] =
                    [
                        'refer_code'          => (int)$Verify->id,
                        'transaction_id'    => $Verify->transaction_id,
                        'from_user_id'      => (int)$Verify->user_id,
                        'to_user_id'        => 1,
                        'money'             => $TotalPayment,
                        'balance_before'    => 0,
                        'note'              => 'Rút tiền theo bảng kê số '.(int)$Verify->id,
                        'view'              => 1,
                        'time_create'       => $Verify->time_accept,
                        'check'             => 0
                    ];


            }

        }

        try{
            accountingmodel\TransactionModel::insert($DataTransaction);
        }catch(Exception $e){
            return ['message' => $e->getMessage()];
        }

        return 'Thanh cong';

    }


    public function getAudit($userId){return 1;
        $User = explode(',',$userId);
        $Res = [];
        foreach($User as $userId){
            $ListTransaction =  accountingmodel\TransactionModel::where('from_user_id',$userId)->orWhere('to_user_id',$userId)
                ->orderBy('time_create','ASC')->orderBy('id','ASC')->get()->toArray();

            $Balance = 0;
            $Check = false;
            $Error = 0;
            foreach($ListTransaction as $val){
                if(!$Check){
                    accountingmodel\TransactionModel::where('id', $val['id'])->update(['balance_before' => 0]);
                    $Balance    = 0;
                    $Check      = true;
                }else{
                    if($Balance != $val['balance_before']){
                        $Error  = 1;
                        accountingmodel\TransactionModel::where('id', $val['id'])->update(['balance_before' => $Balance]);
                    }
                }

                if($val['from_user_id'] == $userId){ // Chuyển vào boxme
                    $Balance = $Balance - $val['money'];
                }else{
                    $Balance = $Balance + $val['money'];
                }
            }

            $Merchant = accountingmodel\MerchantModel::where('merchant_id', $userId)->first(['balance']);
            if($Merchant->balance != $Balance){
                return ['error' => $Error, 'msg' => $userId];
            }

            $Res[]  = ['error' => $Error,'balance' => $Balance,'user' => $userId];
        }

        return $Res;
    }


    public function getHandling($UserId){

        $TimeEnd  = $this->time();

        if(!$TimeEnd){
            return Response::json(['error' => true, 'message' => 'TIME_ERROR']);
        }

        $MerchantModel      = new \accountingmodel\MerchantModel;
        $TransactionModel   = new \accountingmodel\TransactionModel;

        $Merchant   = $MerchantModel->where('merchant_id', $UserId)->first();

        $ListTransaction = $TransactionModel
            ->where('time_create','<',$TimeEnd)
            ->where(function($query) use($UserId){
                $query->where('from_user_id', (int)$UserId)
                    ->orWhere('to_user_id', (int)$UserId);
            })
            ->orderBy('time_create','ASC')
            ->orderBy('id','ASC')
            ->get(['id','from_user_id','to_user_id','money','balance_before','time_create'])->toArray();

        $Status             = 'SUCCESS';
        $BalanceMerchant    = 0;
        $IdError            = 0;

        if(!empty($ListTransaction)){
            $Balance    = 0;
            $i          = 0;

            foreach($ListTransaction as $val){
                if($i == 0){
                    $i = 1;
                    $Balance   = $val['balance_before'];
                }

                if($val['from_user_id'] == (int)$Merchant->merchant_id){
                    $Money  = -$val['money'];
                }else{
                    $Money  = $val['money'];
                }

                $BalanceMerchant    = $val['balance_before'];

                if($Balance != $val['balance_before']){
                    $Status             = 'ERROR';
                    $IdError            = $val['id'];
                    break;
                }

                $BalanceMerchant    += $Money;
                $Balance            += $Money;
            }

            if($Status == 'SUCCESS'){
                if($Merchant->balance != $Balance){
                    $Status = 'BALANCE_ERROR';
                }
            }

        }

        return [$Status, $IdError];
    }

    public function getUpdateBoxme($ListUId){
        $ListUId = explode(',',$ListUId);

        $ListError      = [];
        $ListSuccess    = [];
        foreach($ListUId as $val){
            $Check = metadatamodel\ItemHistoryModel::whereIn('history', ['Stocked','Damaged'])->where('uid', $val)->count();

            if($Check > 0){
                $ListError[]    = $val;
            }else{
                $ProductItem    = bm_ecommercemodel\SellerProductItemModel::where('serial_number', $val)->first();
                if(!isset($ProductItem->update_stocked) || empty($ProductItem->update_stocked)){
                    $ListError[]    = $val;
                }else{
                    $ListSuccess[]  = $val;
                    metadatamodel\ItemHistoryModel::insert([
                        'uid'        => $val,
                        'history'    => 'Stocked',
                        'created'   => $ProductItem->update_stocked
                    ]);
                }

            }

        }


        return ['error' => $ListError, 'success' => $ListSuccess];
    }

    public function getUpdateLogWareHouse($ListUId){return 1;
        $ListUId = explode(',',$ListUId);

        foreach($ListUId as $User){
        $ListUser       = \User::where('organization', $User)->lists('id');
        $ListItem       = bm_ecommercemodel\SellerProductItemModel::whereIn('user', $ListUser)
                                                ->groupBy('type_sku')->groupBy('sku')
                                                ->get(['type_sku','sku'])->toArray();

        if(empty($ListItem)) return 'Trống';

        $ListSku    = [];
        $ListType   = [];
        foreach($ListItem as $val){
            $val['sku']         = trim($val['sku']);
            $val['type_sku']    = strtoupper(trim($val['type_sku']));

            $ListSku[]                      = $val['sku'];
            $ListType[$val['sku']]          = $val['type_sku'];
        }


        $Inventory  = bm_warehousemodel\InventoryModel::where('date_report','2016-05-31')->whereIn('sku',$ListSku)->where('inventory','>',0)->get();


        $InsertSku      = [];
        $InsertDetail   = [];
        $Insert         = [];
        foreach($Inventory as $val){
            $val['warehouse']   = strtoupper($val['warehouse']);
            $InsertSku[$val['warehouse']][$ListType[$val['sku']]][]    = [
                'log_id'            => 0,
                'sku'               => $val['sku'],
                'type_sku'          => $ListType[$val['sku']],
                'total_item'        => $val['inventory']
            ];

            if(!isset($InsertDetail[$val['warehouse']][$ListType[$val['sku']]])){
                $InsertDetail[$val['warehouse']][$ListType[$val['sku']]] = [
                    'log_id'        => 0,
                    'warehouse'     => $val['warehouse'],
                    'type_sku'      => $ListType[$val['sku']],
                    'total_item'    => $val['inventory'],
                    'total_sku'     => 1,
                    'standard_item' => 0,
                    'standard_sku'  => 0,
                    'floor'         => 0,
                    'fee'           => 0,
                    'discount_fee'  => 0,
                    'partner_fee'   => 0,
                    'partner_discount_fee'  => 0
                ];
            }else{
                $InsertDetail[$val['warehouse']][$ListType[$val['sku']]]['total_item']  += $val['inventory'];
                $InsertDetail[$val['warehouse']][$ListType[$val['sku']]]['total_sku']   += 1;
            }

            if(!isset($Insert[$val['warehouse']])){
                $Insert[$val['warehouse']]  = [
                    'date'          => '2016-05-31',
                    'organization'  => $User,
                    'warehouse'     => $val['warehouse'],
                    'total_item'    => $val['inventory'],
                    'total_sku'     => 1,
                    'status'        => 'PROVISION',
                    'time_create'   => $this->time()
                ];
            }else{
                $Insert[$val['warehouse']]['total_item']    += $val['inventory'];
                $Insert[$val['warehouse']]['total_sku']     += 1;
            }
        }


        foreach($Insert as $key => $val){
            $Id = \bm_accmodel\LogWareHouseModel::insertGetId($val);
            foreach($InsertDetail[$key] as $k => $v){
                $v['log_id']   = $Id;
                foreach($InsertSku[$key][$k] as $n){
                    $n['log_id'] = $Id;
                    \bm_accmodel\LogWareHouseSkuModel::insert($n);
                }
                \bm_accmodel\LogWareHouseDetailModel::insert($v);
            }

        }

        }
        return $ListUId;
    }

    public function getFixBoxme(){
        $WareHouse = \bm_accmodel\LogWareHouseModel::where('status','PRO')->orderBy('date','ASC')->orderBy('id','ASC')->first();
        if(!isset($WareHouse->id)) return 'Hết';
        $type_sku   = [
            'S1'    => 2,
            'S2'    => 2,
            'S3'    => 2,
            'S4'    => 2,
            'S5'    => 1,
            'S6'    => 1
        ];

        $ListUser       = \User::where('organization', $WareHouse->organization)->lists('id');
        $Check          = bm_ecommercemodel\SellerProductItemModel::whereIn('user', $ListUser)->whereNotNull('update_stocked')->min('update_stocked');
        if(empty($Check) || (strtotime($WareHouse->date) - strtotime($Check) < 30*86400)){// Chưa có sản phẩm nhập kho hoặc mới nhập kho chưa quá 30 ngày
            $Check      = true;
        }else{
            $Check      = false;
        }

        if($Check){ // Được khuyến mãi
            \bm_accmodel\LogWareHouseDetailModel::where('log_id',$WareHouse->id)->update(['discount_fee' => DB::raw( 'fee' )]);
        }

        $WareHouse->status = 'PRO1';
        $WareHouse->save();
        return 'Ok';

    }

    public function getFixBoxme1(){
        $WareHouse = \bm_accmodel\LogWareHouseModel::where('status','PRO1')->orderBy('date','ASC')->orderBy('id','ASC')->first();
        if(!isset($WareHouse->id)){
            return 'Hết';
        }

        $type_sku   = [
            'S1'    => 2,
            'S2'    => 2,
            'S3'    => 2,
            'S4'    => 2,
            'S5'    => 1,
            'S6'    => 1
        ];

        $LogWareHouse   = \bm_accmodel\LogWareHouseDetailModel::where('log_id',$WareHouse->id)
            ->first(['type_sku',DB::raw(
                'sum(total_item)                as total_item,
                 sum(total_sku)                 as total_sku,
                 sum(floor)                     as floor,
                 sum(fee)                       as fee,
                 sum(discount_fee)              as discount_fee,
                 sum(partner_fee)               as partner_fee,
                 sum(partner_discount_fee)      as partner_discount_fee'
            )]);


        $WareHouse->payment_type    = $type_sku[$LogWareHouse->type_sku];
        $WareHouse->total_item      = $LogWareHouse->total_item;
        $WareHouse->total_sku       = $LogWareHouse->total_sku;
        $WareHouse->floor           = $LogWareHouse->floor;
        $WareHouse->total_fee       = $LogWareHouse->fee;
        $WareHouse->total_discount  = $LogWareHouse->discount_fee;
        $WareHouse->partner_total_fee       = $LogWareHouse->partner_fee;
        $WareHouse->partner_total_discount  = $LogWareHouse->partner_discount_fee;
        $WareHouse->status = 'SUCCESS';
        $WareHouse->save();
        return 'Tiếp';

    }

    public function getAuditBoxme($userId){
        $User = explode(',',$userId);
        $Res = [];
        foreach($User as $userId){
            $ListTransaction =  bm_accmodel\TransactionModel::where('from_user_id',$userId)->orWhere('to_user_id',$userId)
                ->orderBy('time_create','ASC')->orderBy('id','ASC')->get()->toArray();

            $Balance = 0;
            $Check = false;
            $Error = 0;
            foreach($ListTransaction as $val){
                if(!$Check){
                    bm_accmodel\TransactionModel::where('id', $val['id'])->update(['balance_before' => 0]);
                    $Balance    = 0;
                    $Check      = true;
                }else{
                    if($Balance != $val['balance_before']){
                        $Error  = 1;
                        bm_accmodel\TransactionModel::where('id', $val['id'])->update(['balance_before' => $Balance]);
                    }
                }

                if($val['from_user_id'] == $userId){ // Chuyển vào boxme
                    $Balance = $Balance - $val['money'];
                }else{
                    $Balance = $Balance + $val['money'];
                }
            }

            $Merchant = bm_accmodel\MerchantModel::where('merchant_id', $userId)->first(['balance']);
            if($Merchant->balance != $Balance){
                return ['error' => $Error, 'msg' => $userId];
            }

            $Res[]  = ['error' => $Error,'balance' => $Balance,'user' => $userId];
        }

        return $Res;
    }


    public function getSendZms(){
        $Process = \ordermodel\OrderProblemModel::where('active',1)->where('type',1)
            ->where('zms',1)->first();

        if(!isset($Process->id)){
            return 'EMPTY';
        }

        $Order              = \ordermodel\OrdersModel::where('id',$Process->order_id)->where('from_user_id', $Process->user_id)
            ->where('time_accept','>=', time() - 86400*90)
            ->first(['id','tracking_code','product_name','to_phone']);

        try{
            $ZaloCtrl = new \ZmsController();
            $ZaloCtrl->ZaloProcessDeliveryFail($Process->user_id, $Order->to_phone, $Order->tracking_code, $Order->product_name, $Process->postman_name, $Process->postman_phone);
            $Process->zms           = 2;
            $Process->time_send_zms = time();
            $Process->save();
        }catch (Exception $e){
            return $e;
        }

        return $Process;
    }

    public function getFixWarehouse($ListCode){
        $ListCode = explode(',',$ListCode);

        $Data = \warehousemodel\PackageModel::whereIn('tracking_code',$ListCode)->get()->ToArray();
        if(empty($Data)){
            return 'Không có danh sách package';
        }

        $ListOrder = [];
        $ListCode  = [];
        $ListData  = [];
        foreach($Data as $val){
            $val['order_number']        = strtoupper(trim($val['order_number']));
            $val['tracking_code']       = strtoupper(trim($val['tracking_code']));

            if(!isset($val['tracking_code']) || empty($val['tracking_code'])){
                return $val;
            }else{
                $ListCode[]    = $val['tracking_code'];
            }

            if(!isset($val['order_number']) || empty($val['order_number'])){
                return $val;
            }else{
                $ListOrder[]    = $val['order_number'];
            }

            $ListData[$val['tracking_code']]    = $val;
            $ListData[$val['order_number']]     = $val;
        }

        $ListUId   = \fulfillmentmodel\ItemHistoryModel::where('history','Packed')->where(function($query) use($ListOrder,$ListCode){
                                                        $query->whereIn('tracking_code', $ListCode)
                                                        ->orWhere(function($q) use($ListOrder){
                                                            $q->whereIn('order_number',$ListOrder);
                                                        });
                                                    })->get()->ToArray();
        if(empty($ListUId)){
            return $ListCode;
        }

        $UId    = [];
        $DataUId= [];
        foreach($ListUId as $val){
            $val['order_number']        = strtoupper(trim($val['order_number']));
            $val['tracking_code']       = strtoupper(trim($val['tracking_code']));
            $val['uid']                 = strtoupper(trim($val['uid']));

            $UId[$val['uid']] = [
                'uid'           => $val['uid'],
                'time_packge'   => $val['created']
            ];
            $DataUId[]  = $val['uid'];

            if(isset($ListData[$val['tracking_code']])){
                $UId[$val['uid']]['user']           = $ListData[$val['tracking_code']]['user'];
                $UId[$val['uid']]['warehouse']      = $ListData[$val['tracking_code']]['warehouse'];
                $UId[$val['uid']]['package']        = $ListData[$val['tracking_code']]['id'];
                $UId[$val['uid']]['package_code']   = $ListData[$val['tracking_code']]['package_code'];
                $UId[$val['uid']]['pickup_code']    = $ListData[$val['tracking_code']]['pickup_code'];
                $UId[$val['uid']]['tracking_code']  = $ListData[$val['tracking_code']]['tracking_code'];
                $UId[$val['uid']]['order_number']   = $ListData[$val['tracking_code']]['order_number'];
                $UId[$val['uid']]['create']         = $ListData[$val['tracking_code']]['create'];
                $UId[$val['uid']]['update']         = $ListData[$val['tracking_code']]['update'];

            }elseif($ListData[$val['order_number']]){
                $UId[$val['uid']]['user']           = $ListData[$val['order_number']]['user'];
                $UId[$val['uid']]['warehouse']      = $ListData[$val['order_number']]['warehouse'];
                $UId[$val['uid']]['package']        = $ListData[$val['order_number']]['id'];
                $UId[$val['uid']]['package_code']   = $ListData[$val['order_number']]['package_code'];
                $UId[$val['uid']]['pickup_code']    = $ListData[$val['order_number']]['pickup_code'];
                $UId[$val['uid']]['tracking_code']  = $ListData[$val['order_number']]['tracking_code'];
                $UId[$val['uid']]['order_number']   = $ListData[$val['order_number']]['order_number'];
                $UId[$val['uid']]['create']         = $ListData[$val['order_number']]['create'];
                $UId[$val['uid']]['update']         = $ListData[$val['order_number']]['update'];
            }
        }

        if(empty($UId)){
            return $ListOrder;
        }
        $DataHistory    = \fulfillmentmodel\ItemHistoryModel::whereIn('uid', $DataUId)->whereIn('history',['Packed','Stocked'])->orderBy('created','ASC')->get()->toArray();
        foreach($DataHistory as $val){
            foreach($DataHistory as $val){
                $val['uid']     = strtoupper(trim($val['uid']));
                $Created        = strtotime($val['created']);
                if($Created < strtotime($UId[$val['uid']]['time_packge'])){
                    $UId[$val['uid']]['time_stock'] = $val['created'];
                }
            }
        }

        $DataProduct            = \fulfillmentmodel\SellerProductItemModel::whereIn('serial_number',$DataUId)->get()->ToArray();
        foreach($DataProduct as $val){
            $val['serial_number']     = strtoupper(trim($val['serial_number']));
            $UId[$val['serial_number']]['sku'] = $val['sku'];
        }

        $Error = '';
        foreach($UId as $val){
            try{
                \warehousemodel\PackageItemModel::insert($val);
            }catch (Exception $e){
                $Error  = 'Lỗi';
            }
        }
        return ['error' => $Error,'data' => $UId];
    }

    public function getExportExcel($Id){
        $LMongo   = new \LMongo;
        $Data     = $LMongo::collection('log_kpi')->where('category_id', (int)$Id)->get()->toArray();
        $City     = $this->getCity();
        $District = [];
        $Ward     = [];
        $FromAddress = [];

        if(!empty($Data)){
            foreach($Data as $val){
                $ListDistrictId[]  = $val['from_district_id'];
                $ListWardId[]      = $val['from_ward_id'];
                $ListFromAddress[] = $val['from_address_id'];
            }

            if(isset($ListFromAddress) && !empty($ListFromAddress)){
                $InventoryModel   = new \sellermodel\UserInventoryModel;
                $ListFromAddress  = $InventoryModel::whereRaw("id in (". implode(",", $ListFromAddress) .")")->get()->toArray();
            }

            if(isset($ListFromAddress) && !empty($ListFromAddress)){
                foreach($ListFromAddress as $val){
                    if(!empty($val)){
                        $FromAddress[$val['id']]    = $val;
                    }
                }
            }

            $ListDistrictId = array_unique($ListDistrictId);
            $ListWardId     = array_unique($ListWardId);

            if(!empty($ListDistrictId)){
                $BaseCtrl = new \ops\BaseCtrl;
                $District   = $BaseCtrl->getProvince($ListDistrictId);
            }

            if(!empty($ListWardId)){
                $WardModel = new WardModel;
                $ListWard  =  $WardModel::whereRaw("id in (". implode(",", $ListWardId) .")")->get(['id','ward_name'])->toArray();
                if(!empty($ListWard)){
                    foreach($ListWard as $val){
                        $Ward[$val['id']]   = $val['ward_name'];
                    }
                }
            }
        }

        return Excel::create('Danh_sach', function($excel) use($Data, $City, $District, $Ward, $FromAddress){
            $excel->sheet('Sheet1', function($sheet) use($Data, $City, $District, $Ward, $FromAddress){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách'));

                $sheet->row(3, array(
                    'STT', 'Email', 'Kho hàng', 'tổng số đơn', 'số đơn đã lấy', 'tổng đơn', 'đơn đã lấy','Tỉnh/Thành Phố', 'Quận/Huyện', 'Phường Xã', 'Địa chỉ', 'Trạng thái'
                ));

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        $val['email'],
                        isset($FromAddress[$val['from_address_id']]) ? $FromAddress[$val['from_address_id']]['user_name'] : '',
                        $val['total'],
                        $val['total_picked'],
                        $val['tracking_code'],
                        $val['tracking_code_picked'],
                        isset($City[(int)$val['from_city_id']]) ? $City[(int)$val['from_city_id']] : '',
                        isset($District[(int)$val['from_district_id']]) ? $District[(int)$val['from_district_id']] : '',
                        isset($Ward[(int)$val['from_ward_id']]) ? $Ward[(int)$val['from_ward_id']] : '',
                        $val['from_address'],
                        ($val['category_type'] == 1) ? 'Đã lấy' : 'Chưa lấy'
                    );

                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }

    function encryptData($data, $publicKey) {
        $rsa = new Crypt_RSA();
        $rsa->loadKey($publicKey); // public key
        $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
        $output = $rsa->encrypt($data);
        return base64_encode($output);
    }


    function decryptData($data, $publicKey) {
        $rsa = new Crypt_RSA();
        $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
        $ciphertext = base64_decode($data);
        $rsa->loadKey($publicKey); // public key
        $output = $rsa->decrypt($ciphertext);
        // $output = $rsa->decrypt($data);
        return $output;
    }

    function decryptCallbackData($data, $publicKey){
        $decoded = base64_decode($data);
        return $this->decryptData($decoded, $publicKey);
    }

    public function getCreateAlepay(){
        $Amount         = 10000;
        $OrderCode      = 'SC123456789';

        $Params = [
            'orderCode'         => $OrderCode,
            'amount'            => $Amount,
            'currency'          => 'VND',
            'orderDescription'  => 'Nạp tiền phí vận chuyển vào tài khoản trên shipchung.vn',
            'totalItem'         => 1,
            'checkoutType'      => 1,
            'installment'       => false,
            'returnUrl'         => 'http://seller.shipchung.vn/1',
            'cancelUrl'         => 'http://seller.shipchung.vn/2',
            'buyerName'         => 'Shipchung',
            'buyerEmail'        => 'shipchung@gmail.com',
            'buyerPhone'        => '01232032828',
            'buyerAddress'      => '12A 18 Tam Trinh Hai Bà Trưng Hà Nội',
            'buyerCity'         => 'Hà Nội',
            'buyerCountry'      => 'Việt Nam',
            'paymentHours'      => 1
        ];

        $dataEncrypt = $this->encryptData(json_encode($Params),Config::get('config_api.ALEPAY_ENCRYPT_KEY'));
        //echo json_encode($params);
        $checksum = md5($dataEncrypt . Config::get('config_api.ALEPAY_CHECKSUM_KEY'));
        //var_dump($this->URL['requestPayment']);die;
        $items = array(
            'token' => Config::get('config_api.ALEPAY_API_KEY'),
            'data' => $dataEncrypt ,
            'checksum' => $checksum
        );
        $data_string = json_encode($items);
        $ch = curl_init(Config::get('config_api.ALEPAY_API').'request-order');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result,1);
        return $this->decryptData($result['data'], Config::get('config_api.ALEPAY_ENCRYPT_KEY'));
    }

    public function getLinkedAlepay(){
        $Params = [
            'id'                => 3850,
            'firstName'         => 'DƯơng',
            'lastName'          => 'Dương',
            'street'            => 'Đường',
            'city'              => 'Hà Nội',
            'state'             => 'Việt Nam',
            'postalCode'        => '10000',
            'country'           => 'Việt Nam',
            'email'             => 'khmt1008@gmail.com',
            'phoneNumber'       => '0123456789',
            'callback'          => 'http://localhost/sellercenter/api/public/cronjob/convert/linked-alepay1',
        ];

        $dataEncrypt = $this->encryptData(json_encode($Params),Config::get('config_api.ALEPAY_ENCRYPT_KEY'));
        //echo json_encode($params);
        $checksum = md5($dataEncrypt . Config::get('config_api.ALEPAY_CHECKSUM_KEY'));
        //var_dump($this->URL['requestPayment']);die;
        $items = array(
            'token' => Config::get('config_api.ALEPAY_API_KEY'),
            'data' => $dataEncrypt ,
            'checksum' => $checksum
        );
        $data_string = json_encode($items);
        $ch = curl_init(Config::get('config_api.ALEPAY_API').'request-profile');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result,1);
        return $this->decryptData($result['data'], Config::get('config_api.ALEPAY_ENCRYPT_KEY'));
    }

    public function getPaymentAlepay(){
        $Params = [
            'customerToken'     => "8e4896f7a408f37dce6cf5d5f22e68ff",
            'orderCode'         => 'SC12313123123',
            'amount'            => '10000',
            'currency'          => 'VND',
            'orderDescription'  => 'nothing',
            'returnUrl'         => 'http://localhost/sellercenter/api/public/cronjob/convert/linked-alepay2',
            'cancelUrl'         => 'http://localhost/sellercenter/api/public/cronjob/convert/linked-alepay3'
        ];

        $dataEncrypt = $this->encryptData(json_encode($Params),Config::get('config_api.ALEPAY_ENCRYPT_KEY'));
        //echo json_encode($params);
        $checksum = md5($dataEncrypt . Config::get('config_api.ALEPAY_CHECKSUM_KEY'));
        //var_dump($this->URL['requestPayment']);die;
        $items = array(
            'token' => Config::get('config_api.ALEPAY_API_KEY'),
            'data' => $dataEncrypt ,
            'checksum' => $checksum
        );
        $data_string = json_encode($items);
        $ch = curl_init(Config::get('config_api.ALEPAY_API').'request-tokenization-payment');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result,1);
        return $this->decryptData($result['data'], Config::get('config_api.ALEPAY_ENCRYPT_KEY'));
    }

    //Revenue
    private function __category_revenue($DataCategory){
        //Danh sách merchant
        $SellerModel            = new omsmodel\SellerModel;
        if(date('d', strtotime($DataCategory->date)) < 25){
            $TimeStart      = strtotime(date('2016-5-25 00:00:00'));
            $TimePreMonth   = strtotime(date('2016-4-25 00:00:00'));
        }else{
            $TimeStart      = strtotime(date('2016-6-25 00:00:00'));
            $TimePreMonth   = strtotime(date('2016-5-25 00:00:00'));
        }

        $SellerModel            = new omsmodel\SellerModel;
        $ReportMerchantModel    = new accountingmodel\ReportMerchantModel;
        $Model                  = new \omsmodel\SellerModel;
        $ModelLastMonth         = new \omsmodel\SellerModel;
        $Data           = [
            'total_firstmonth'  => 0,
            'total_nextmonth'   => 0
        ];
        $SumTotal       = 0;

        // Doanh thu đầu tháng
        $DataSum        = $Model::where('first_time_pickup', '>=', $TimeStart)
            ->where('seller_id',(int)$DataCategory->user_id)
            ->where('active',1)
            ->first([DB::raw('sum(total_firstmonth) as total_firstmonth')]);

        if(isset($DataSum->total_firstmonth)){
            $DataSum->total_firstmonth      = ceil($DataSum->total_firstmonth*10/11);
            $SumTotal                       = $DataSum->total_firstmonth;
            $Data['total_firstmonth']       = $DataSum->total_firstmonth;
        }

        // Doanh thu lũy kế
        $DataSum     = $ModelLastMonth::where('first_time_pickup','>=',$TimePreMonth)
            ->where('first_time_pickup','<',$TimeStart)
            ->where('active',1)
            ->where('seller_id',(int)$DataCategory->user_id)
            ->first([DB::raw('sum(total_nextmonth) as total_nextmonth')]);

        if(isset($DataSum->total_nextmonth)){
            $DataSum->total_nextmonth       = ceil($DataSum->total_nextmonth*10/11);
            $SumTotal                      += $DataSum->total_nextmonth;
            $Data['total_nextmonth']        = $DataSum->total_nextmonth;
        }

        // Doanh thu lũy kế khác hàng ngừng sử dụng
        $LogSellerModel = new \omsmodel\LogSellerModel;
        $DataPreStop     = $LogSellerModel::where('first_time_pickup','>=',$TimePreMonth)
            ->where('first_time_pickup','<',$TimeStart)
            ->where('seller_id',(int)$DataCategory->user_id)
            ->where('active',1)
            ->first([DB::raw('sum(total_nextmonth) as total_nextmonth')]);
        if(isset($DataPreStop->total_nextmonth)){
            $val['total_nextmonth']         = ceil($DataPreStop->total_nextmonth*10/11);
            $SumTotal                      += $DataPreStop->total_nextmonth;
            $Data['total_nextmonth']       += $DataPreStop->total_nextmonth;
        }

        $DataCategory->revenue_firstmonth   = $Data['total_firstmonth'];
        $DataCategory->revenue_nextmonth    = $Data['total_nextmonth'];
        $DataCategory->succeed              = $SumTotal;
        $DataCategory->percent              = round(($DataCategory->succeed/$DataCategory->succeed_target),3);

        return ['error' => false, 'data' => $DataCategory];
    }

    public function getUpdateKpi(){
        return 1;
        $Date = Input::has('date') ? trim(Input::get('date')) : '';

        $ListKPI = \reportmodel\KPICategoryModel::whereIn('code', ['revenue', 'team revenue'])->get()->toArray();
        if(empty($ListKPI)){
            return Response::json(['error' => false, 'code' => 'EMPTY', 'error_message' => 'Không có dữ liệu']);
        }

        $KPI            = [];
        $DataKpi        = [];

        $KPITeam        = [];
        $DataKpiTeam    = [];

        foreach($ListKPI as $val){
            if($val['code'] == 'revenue'){
                $KPI[]                  = $val['id'];
                $DataKpi[$val['id']]    = $val;
            }else{
                $KPITeam[]                  = $val['id'];
                $DataKpiTeam[$val['id']]    = $val;
            }
        }

        $Config = \reportmodel\KPIConfigModel::whereIn('category_id', $KPI)
                                             ->where('date','<>', $Date)
                                             ->where('active',1)->orderBy('user_id', 'ASC')->first();
        if(!isset($Config->id)){
            return Response::json(['error' => false, 'code' => 'PROCESSED', 'error_message' => 'Xử lý xong']);
        }

        //Get Team
        $ConfigTeam = \reportmodel\KPIConfigModel::whereIn('category_id', $KPITeam)
                                                    ->where('user_id', $Config->user_id)
                                                    ->where('active',1)->first();

        try{
            $Kpi = \reportmodel\KPIModel::firstOrCreate([
                'date'              => $Date,
                'user_id'           => $Config->user_id,
                'category_id'       => $Config->category_id
            ]);
            $Kpi->succeed_target   = $DataKpi[$Config->category_id]['target'];
            $Kpi->percent_target   = $DataKpi[$Config->category_id]['percent'];
            $Kpi->weight           = $DataKpi[$Config->category_id]['weight'];

            $KpiTeam = \reportmodel\KPIModel::firstOrCreate([
                'date'              => $Date,
                'user_id'           => 0,
                'category_id'       => $ConfigTeam->category_id
            ]);
            $KpiTeam->succeed_target   = $DataKpiTeam[$ConfigTeam->category_id]['target'];
            $KpiTeam->percent_target   = $DataKpiTeam[$ConfigTeam->category_id]['percent'];
            $KpiTeam->weight           = $DataKpiTeam[$ConfigTeam->category_id]['weight'];
        }catch (Exception $e){
            return Response::json(['ERROR' => $e->getMessage()]);
        }

        //Lấy ra khách hàng cần tính
        $TotalFirstmonth    = 0;
        $TotalNextmonth     = 0;

        if(date('d', strtotime($Date)) < 25){
            $DateStart      = '2017-01-25';
            $TimeStart      = strtotime(date($DateStart.' 00:00:00'));
            $TimePreMonth   = strtotime(date('2016-12-25 00:00:00'));
        }else{
            $DateStart      = '2017-02-25';
            $TimeStart      = strtotime(date($DateStart.' 00:00:00'));
            $TimePreMonth   = strtotime(date('2017-01-25 00:00:00'));
        }

        // Tính doanh thu đầu tháng
        $SellerModel            = new \omsmodel\SellerModel;
        $LogSellerModel         = new \omsmodel\LogSellerModel;

        $LastMonthModel         = new \omsmodel\SellerModel;
        $LogLastMonthModel      = new \omsmodel\LogSellerModel;

        // Khách hàng có doanh thu đầu tháng
        $ListUser               = $SellerModel::where('first_time_pickup', '>=', $TimeStart)
                                                        ->where('first_time_pickup', '<=', strtotime(date($Date .' 23:59:59')))
                                                        ->where('seller_id',$Config->user_id)
                                                        ->get(['user_id', 'seller_id','first_time_pickup'])->toArray();

        $LogUser                = $LogSellerModel::where('first_time_pickup', '>=', $TimeStart)
                                                        ->where('first_time_pickup', '<=', strtotime(date($Date .' 23:59:59')))
                                                        ->where('seller_id',$Config->user_id)
                                                        ->get(['user_id', 'seller_id','first_time_pickup'])->toArray();

        //Khách hàng tính lũy kế
        $ListUserPre            = $LastMonthModel::where('first_time_pickup','>=',$TimePreMonth)
                                                ->where('first_time_pickup','<',$TimeStart)
                                                ->where('seller_id',$Config->user_id)
                                                ->get(['user_id', 'seller_id','first_time_pickup'])->toArray();

        $LogListUserPre     = $LogLastMonthModel::where('first_time_pickup','>=',$TimePreMonth)
                                                ->where('first_time_pickup','<',$TimeStart)
                                                ->where('seller_id',$Config->user_id)
                                                ->get(['user_id', 'seller_id','first_time_pickup'])->toArray();


        if(!empty($ListUser) && !empty($LogUser)){
            $ListUser       = array_merge($ListUser, $LogUser);
        }else{
            $ListUser       = !empty($ListUser) ? $ListUser : $LogUser;
        }

        if(!empty($ListUserPre) && !empty($LogListUserPre)){
            $ListUserPre    = array_merge($ListUserPre, $LogListUserPre);
        }else{
            $ListUserPre    = !empty($ListUserPre) ? $ListUserPre : $LogListUserPre;
        }

        $ReferUser  = [];
        $DateNow    = explode('-', $Date);
        foreach($ListUser as $val) {
            $DateMonth  = explode('/', date('Y/m/d', $val['first_time_pickup']));
            $MonthFirst = $DateMonth[1];
            $DateFirst  = $DateMonth[2];
            $YearFirst  = $DateMonth[0];

            $NextMonth = (int)$MonthFirst + 1;
            if ($MonthFirst == 12) {
                $NextMonth = 1;
                $NextYear = $YearFirst + 1;
            } else {
                $NextYear = $YearFirst;
            }

//        Doanh thu đầu tháng
//        nếu ngày bắt đầu < 25 thì sẽ tính từ  1 -> 25
//        nếu ngày bắt đầu > 25 thì sẽ tính từ 25 -> 25 tháng sau
            $ReportMerchant = new accountingmodel\ReportMerchantModel;
            $ReportMerchant = $ReportMerchant::where('user_id', $val['user_id']);
            if ($DateFirst < 25) {
                $ReportMerchant = $ReportMerchant->where('date', '>=', $DateFirst)
                    ->where('month', (int)$MonthFirst)
                    ->where('year', (int)$YearFirst);

                if (($DateNow[2] < 25) && ($DateNow[1] == $MonthFirst)) {
                    $ReportMerchant = $ReportMerchant->where('date', '<=', $DateNow[2]);
                } else {
                    $ReportMerchant = $ReportMerchant->where('date', '<', 25);
                }
            } else {
                if ($DateNow[1] == $MonthFirst) {
                    $ReportMerchant = $ReportMerchant->where('date', '>=', $DateFirst)
                        ->where('date', '<=', $DateNow[2])
                        ->where('month', (int)$MonthFirst)
                        ->where('year', (int)$YearFirst);
                } else {
                    $DateEnd = $DateNow[2] < 25 ? $DateNow[2] : 24;
                    $ReportMerchant = $ReportMerchant->where(function ($query) use ($DateFirst, $MonthFirst, $YearFirst, $NextMonth, $NextYear, $DateEnd) {
                        $query->where(function ($q) use ($DateFirst, $MonthFirst, $YearFirst) {
                            $q->where('date', '>=', $DateFirst)
                                ->where('month', (int)$MonthFirst)
                                ->where('year', (int)$YearFirst);
                        })->orWhere(function ($q) use ($DateFirst, $NextMonth, $NextYear, $DateEnd) {
                            $q->where('date', '<=', $DateEnd)
                                ->where('month', (int)$NextMonth)
                                ->where('year', (int)$NextYear);
                        });
                    });
                }
            }

            $ReportMerchant = $ReportMerchant->groupBy('user_id')
                ->first(array(DB::raw(
                    'user_id,
                                               sum(sc_pvc) as sc_pvc,
                                               sum(sc_cod) as sc_cod,
                                               sum(sc_pbh) as sc_pbh,
                                               sum(sc_discount_pvc) as sc_discount_pvc,
                                               sum(sc_discount_cod) as sc_discount_cod'
                )));

            if (!empty($ReportMerchant)) {
                $TotalFirstmonth += $ReportMerchant->sc_pvc + $ReportMerchant->sc_cod
                    + $ReportMerchant->sc_pbh - $ReportMerchant->sc_discount_pvc
                    - $ReportMerchant->sc_discount_cod;
            }
        }

        // Tính doanh thu lũy kế
        foreach($ListUserPre as $val) {
            $DateMonth  = explode('/', date('Y/m/d', $val['first_time_pickup']));
            $MonthPre   = $DateMonth[1];
            $DatePre    = $DateMonth[2];
            $YearPre    = $DateMonth[0];


            $NextMonth = (int)$MonthPre + 1;
            if ($MonthPre == 12) {
                $NextMonth = 1;
                $NextYear = $YearPre + 1;
            } else {
                $NextYear = $YearPre;
            }
            $ReportMerchantModel    = new accountingmodel\ReportMerchantModel;
            $ReportMerchantModel    = $ReportMerchantModel::where('user_id',$val['user_id']);
            $ReportMerchant         = [];

            if($DateNow[2] < 25) {
                if($DatePre < 25) {
                    $DateEnd = $DatePre > $DateNow[2] ? $DateNow[2] : ($DatePre - 1);
                    $ReportMerchant = $ReportMerchantModel->where(function ($query) use ($DateEnd, $MonthPre, $YearPre, $NextMonth, $NextYear) {
                        $query->where(function ($q) use ($MonthPre, $YearPre) {
                            $q->where('date', '>=', 25)
                                ->where('month', $MonthPre)
                                ->where('year', $YearPre);
                        })->orWhere(function ($q) use ($DateEnd, $NextMonth, $NextYear) {
                            $q->where('date', '<=', $DateEnd)
                                ->where('month', (int)$NextMonth)
                                ->where('year', (int)$NextYear);
                        });
                    });
                }else {
                    $ReportMerchant    = $ReportMerchantModel->where('date', '>=', 25)
                                                             ->where('date', '<', $DatePre)
                                                             ->where('month', $NextMonth)
                                                             ->where('year', $NextYear);
                }
            }else {
                if($DatePre < 25){
                    $DateEnd    = $DateNow[2];
                }else{
                    $DateEnd    = $DatePre > $DateNow[2] ? $DateNow[2] : ($DatePre -  1);
                }
                $ReportMerchant    = $ReportMerchantModel->where('date', '>=', 25)
                                                        ->where('date', '<=', $DateEnd)
                                                        ->where('month', $DateNow[1])
                                                        ->where('year', $DateNow[0]);
            }

            $ReportMerchant = $ReportMerchant->groupBy('user_id')
                                            ->first(array(DB::raw(
                                                'user_id,
                                                   sum(sc_pvc) as sc_pvc,
                                                   sum(sc_cod) as sc_cod,
                                                   sum(sc_pbh) as sc_pbh,
                                                   sum(sc_discount_pvc) as sc_discount_pvc,
                                                   sum(sc_discount_cod) as sc_discount_cod'
                                            )));

            if(isset($ReportMerchant->user_id)){
                $TotalNextmonth += $ReportMerchant->sc_pvc + $ReportMerchant->sc_cod
                    + $ReportMerchant->sc_pbh - $ReportMerchant->sc_discount_pvc
                    - $ReportMerchant->sc_discount_cod;
            }
        }

        try{

            $Kpi->revenue_firstmonth    = $TotalFirstmonth;
            $Kpi->revenue_nextmonth     = $TotalNextmonth;
            $Kpi->succeed              = ($TotalFirstmonth + $TotalNextmonth);
            $Kpi->percent              = round((($TotalFirstmonth + $TotalNextmonth)/$Kpi->succeed_target),3);
            $Kpi->time_create           = time();
            $Kpi->save();

            $KpiTeam->revenue_firstmonth    += $Kpi->revenue_firstmonth;
            $KpiTeam->revenue_nextmonth     += $Kpi->revenue_nextmonth;
            $KpiTeam->succeed               += $Kpi->succeed;
            $KpiTeam->percent               = round(($KpiTeam->succeed/$KpiTeam->succeed_target),3);
            $KpiTeam->time_create           = time();
            $KpiTeam->save();

            $Config->date       = $Date;
            $Config->save();
        }catch (Exception $e){
            return Response::json(['error' => false, 'message' => $e->getMessage()]);
        }

        return Response::json(['error' => false, 'message' => 'Thành công']);
    }

    public function getUsApi($AB){
        set_time_limit (100);

        $DataInsert = [];
        for($i = (($AB - 1)*10 + 1); $i <= $AB*10; $i++){
            $k      = sprintf("%03d", $i);
            $Data   = [];
            $html = file_get_html('https://postcalc.usps.com/Zonecharts/ZoneChartPrintable.aspx?zipcode='.$k);
            foreach($html->find('tr') as $row) {
                if(isset($row->find('td',1)->plaintext) && isset($row->find('td',0)->plaintext)){
                    $Data[strtolower($row->find('td',1)->plaintext)][]  = $row->find('td',0)->plaintext;
                }

                if(isset($row->find('td',3)->plaintext) && isset($row->find('td',2)->plaintext)){
                    $Data[strtolower($row->find('td',3)->plaintext)][]  = $row->find('td',2)->plaintext;
                }

                if(isset($row->find('td',5)->plaintext) && isset($row->find('td',4)->plaintext)){
                    $Data[strtolower($row->find('td',5)->plaintext)][]  = $row->find('td',4)->plaintext;
                }

                if(isset($row->find('td',7)->plaintext) && isset($row->find('td',6)->plaintext)){
                    $Data[strtolower($row->find('td',7)->plaintext)][]  = $row->find('td',6)->plaintext;
                }
                // Parse table row here
            }

            if(!empty($Data)){
                foreach($Data as $key => $val){
                    if(!in_array($key, ['zone'])){
                        if($key == '9+'){
                            $key = 8;
                        }

                        $key = (int)$key;
                        foreach($val as $v){
                            $Split = explode('---', $v);
                            if(isset($Split[1])){
                                if(strlen($Split[0])  != 5){
                                    for($j = $Split[0]; $j <= $Split[1]; $j++){
                                        $DataInsert[]   = [
                                            'courier_id'    => 7,
                                            'service_id'    => 2,
                                            'from_zipcode'  => $k,
                                            'to_zipcode'    => sprintf("%03d", $j),
                                            'fee_id'        => $key,
                                            'vail'          => 0,
                                            'oil'           => 0,
                                            'active'        => 1
                                        ];
                                    }
                                }
                            }else{
                                $DataInsert[]   = [
                                    'courier_id'    => 7,
                                    'service_id'    => 2,
                                    'from_zipcode'  => $k,
                                    'to_zipcode'    => $v,
                                    'fee_id'        => $key,
                                    'vail'          => 0,
                                    'oil'           => 0,
                                    'active'        => 1
                                ];
                            }
                        }
                    }
                }
            }
        }

        $i = 0;
        $Insert = [];
        foreach($DataInsert as $val) {
            $i++;
            $Insert[$i % 10][] = $val;
        }

        try{
            foreach($Insert as $val){
                DB::connection('courierdb')->table('courier_delivery_us')->insert($val);
            }

        }catch (Exception $e){
            return ['error' => true, 'message' => 'INSERT_ERROR'];
        }

        return 1;
    }

    public function getAcceptTt(){
        $soapClient = new \SoapClient("http://118.70.176.254:6565/WebServiceNT/NTWebService?wsdl");
        $result =  $soapClient->cancelOrderFull(["username"          => "shipchung","password"          => "wfwGPT24FKJx5cLT",'orderNumber'  => 'SC123456799', 'note' => 'Shipchung hủy đơn hàng']);
        dd($result);

        ini_set("soap.wsdl_cache_enabled", 0);

        $LadingParams   = [
            "username"          => "shipchung",
            "password"          => "wfwGPT24FKJx5cLT",
            "orderNumber"       => "SC123456799",
            "serviceCode"       => "10",
            "isWoodPack"        => "N",
            "noPacks"            => 1,
            "packageWeight"     => 0.50,
            "cod"               => 100000,
            "description"       => "Test",
            "length"            => "",
            "width"             => "",
            "height"            => "",
            "isCount"           => "Y",
            "sender"            => [
                "addressCode"       => "321",
                "addressName"       => "Tên địa chỉ",
                "fullAddress"       => "Địa chỉ đầy đủ",
                "provinceAreaCode"  => "11",
                "districtAreaCode"  => "1113",
                "contactPhone"      => "0123456789",
                "contactName"       => "ShipChung Test"
            ],
            "receiver"          => [
                "addressCode"       => "",
                "addressName"       => "Tên địa chỉ",
                "fullAddress"       => "Địa chỉ đầy đủ",
                "provinceAreaCode"  => "11",
                "districtAreaCode"  => "1113",
                "contactPhone"      => "0123456789",
                "contactName"       => "ShipChung Test",
            ],
            "payMethod"             => 1,
            "note"                  => "Ghi chú"
        ];

        $soapClient = new \SoapClient("http://118.70.176.254:6565/WebServiceNT/NTWebService?wsdl");
        //$header     = new \SOAPHeader('http://viettelpost.org/', 'ServiceAuthHeader', array('Token' => (string)'B401262F9A3D09B9A5AC7AFF3DED92CC' /*$resultLogin['LoginResult']['wTokenKey']*/));
        //set the Headers of Soap Client.
        //$soapClient->__setSoapHeaders($header);
        $result =  $soapClient->updateDoInfoAll($LadingParams);
        dd($result);
    }
}
