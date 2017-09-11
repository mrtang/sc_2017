<?php

use accountingmodel\MerchantModel;
use accountingmodel\TransactionModel;

use fulfillmentmodel\SellerProductItemModel;
use fulfillmentmodel\WareHouseFeeModel;
use fulfillmentmodel\WareHouseFeeDetailModel;
use fulfillmentmodel\WareHouseFeeSkuModel;
use fulfillmentmodel\WareHouseVerifyModel;
use fulfillmentmodel\HistoryItemModel;
use sellermodel\UserWMSTypeModel;


class ApiBoxmeCtrl extends \BaseCtrl {
    private $time_start     = '';
    private $time_end       = '';
    private $master         = 1;
    private $message        = 'Thành Công';
    private $code           = 'SUCCESS';

    private $type_sku   = [
        'S1'    => 2,
        'S2'    => 2,
        'S3'    => 2,
        'S4'    => 2,
        'S5'    => 1,
        'S6'    => 1
    ];

    /**
     * Cập nhật lịch sử lưu kho theo sản phẩm
     */
    private function update_product_item_history($UserId){
        //Danh sách nhập kho hôm qua
        $ListUId    = SellerProductItemModel::where('user_id', $UserId)
            ->where('update_stocked','>=',$this->time_start)
            ->where('update_stocked','<=',$this->time_end)
            ->get(['serial_number','update_stocked','user_id'])->toArray();

        if(empty($ListUId)){
            return true;
        }

        //Lấy hình thức lưu kho hiện tại
        $TypeUser = UserWMSTypeModel::where('user_id', $UserId)->where('active',1)->first();
        if(!isset($TypeUser->wms_type)){
            $TypeUser   = new UserWMSTypeModel;
            $TypeUser->wms_type = 0;
        }

        $UID        = [];
        foreach($ListUId as $val){
            $val['serial_number']               = trim(strtoupper($val['serial_number']));
            $UID[]                              = $val['serial_number'];
        }

        //Danh sách item đã tồn tại
        $ListHistory    = HistoryItemModel::where('user_id',$UserId)
                                          ->whereRaw("uid in ('". implode("','", $UID) ."')")
                                          ->lists('uid');

        foreach($ListUId as $val){
            $val['serial_number']               = trim(strtoupper($val['serial_number']));
            if(!in_array($val['serial_number'], $ListHistory)){
                $ListInsert[$val['serial_number']]     = [
                    'user_id'       => $UserId,
                    'uid'           => $val['serial_number'],
                    'type_payment'  => $TypeUser->wms_type,
                    'time_start'    => strtotime($val['update_stocked']),
                    'time_end'      => 0
                ];
            }
        }

        try{
            if(!empty($ListInsert)){
                HistoryItemModel::insert($ListInsert);
            }

            return true;
        }catch (Exception $e){
            return false;
        }

    }

    public function getCreateProvisional(){
        $Date   = date('Y-m-d');

        //Lấy danh sách user_id có sp đang lưu kho
        $ListItem   = SellerProductItemModel::where('status',7)->where('type_sku','<>','NULL')->groupBy('user_id')
            ->lists('user_id');
        if(empty($ListItem)){
            return array('error'=> false, 'code' => 'EMPTY', 'error_message' => 'Không có sản phẩm lưu kho');
        }

        $MerchantModel  = new MerchantModel;
        try{
            $MerchantModel::whereRaw("merchant_id in (". implode(",", $ListItem) .")")->where('time_inventory','<>',$Date)->update(['time_inventory' => 'WAITING']);
        }catch (Exception $e){
            return array('error'=> true, 'code' => 'ERROR', 'error_message' => $e->getMessage());
        }

        return array('error'=> false, 'code' => 'SUCCESS', 'error_message' => 'Thành Công');
    }

    private function CalculateWareHouseFee($Merchant){
        $Date   	    = date('Y-m-d');
        $UserWMSType    = \sellermodel\UserWMSTypeModel::where('user_id', $Merchant->merchant_id)->where('active',1)->first();
        $WareHouseCtrl  = new \accounting\WareHouseCtrl;
        if(!isset($UserWMSType) || $UserWMSType->wms_type == 0){
            //Luu kho theo san pham
            $Merchant->warehouse_freeze     = $WareHouseCtrl->getWarehouseFee($Merchant->merchant_id);
        }
        else{
            //Luu kho theo khoang ke
            $Merchant->warehouse_freeze    = $WareHouseCtrl->getWarehouseFeePallet($Merchant->merchant_id, $UserWMSType->wms_type,$Date);
        }

        return $Merchant;
    }


    public function getProvisional(){
        $Date               = date('Y-m-d');
        $Time               = time() - 86400;
        $this->time_start   = date('Y-m-d 00:00:00',$Time);
        $this->time_end     = date('Y-m-d 23:59:59',$Time);

        $Merchant = MerchantModel::where('time_inventory', 'WAITING')->where('level','<>',10)->orderBy('time_update','ASC')->first();
        if(!isset($Merchant->merchant_id)){
            return array('error'=> false, 'code' => 'EMPTY', 'error_message' => 'Thành Công');
        }

        $Merchant->time_update    =  time();
        //Cập nhật lịch sử lưu kho cho item
        if(!$this->update_product_item_history($Merchant->merchant_id)){
            return array('error'=> true, 'code'=> 'UPDATE_HISTORY_ERROR', 'error_message' => 'Cập nhật lịch sử lưu kho thất bại ngày '.$Date);
        }

        //Check đã tồn tại trong bảng warehouse_fee chưa
        if(WareHouseFeeModel::where('user_id', $Merchant->merchant_id)->where('date',$Date)->count() > 0){
            try{
                $Merchant->time_inventory = $Date;
                //Tính luôn phí tạm tính
                $Merchant = $this->CalculateWareHouseFee($Merchant);


                $Merchant->save();
                return array('error'=> false, 'code' => 'EMPTY', 'error_message' => 'Thành Công', 'merchant' => $Merchant);
            }catch (Exception $e){
                return array('error'=> true, 'code' => 'ERROR', 'error_message' => $e->getMessage());
            }
        }

        //Lấy danh sách sản phẩm đang lưu kho
        $ListItem       = SellerProductItemModel::where('status',7)->where('type_sku','<>','NULL')
                                                ->where('user_id', $Merchant->merchant_id)->with('__putaway')
                                                ->get(['id','user_id','sku','type_sku','serial_number'])->toArray();

        if(empty($ListItem)){
            try{
                $Merchant->time_inventory  = $Date;
                //Tính luôn phí tạm tính
                $Merchant = $this->CalculateWareHouseFee($Merchant);

                $Merchant->save();
                return array('error'=> false, 'code' => 'EMPTY', 'error_message' => 'Thành Công', 'merchant' => $Merchant);
            }catch (Exception $e){
                return array('error'=> false, 'code' => 'UPDATE_FAIL', 'error_message' => $e->getMessage());
            }
        }

        // Lay hvc
        $WareHouse  = $this->getWareHouseBoxme(false);

        $Item           = [];
        $TypeSku        = $this->type_sku;
        $CheckType      = 0;
        $ListCourier    = [];
        $LogSku         = [];
        $Sku            = [];

        foreach($ListItem as $val){
            $val['type_sku']    = strtoupper($val['type_sku']);
            //Check nếu có type_sku  ở khoảng [S1,S2,S3,S4] và ở khoảng [S5,S6] => Lỗi
            if(!empty($CheckType) && $CheckType != $TypeSku[$val['type_sku']]){
                try{
                    $Merchant->time_inventory  = $Date;
                    //Tính luôn phí tạm tính
                    $Merchant = $this->CalculateWareHouseFee($Merchant);
                    $Merchant->save();
                    return array('error'=> false, 'code' => 'ERROR_TYPE_SKU', 'error_message' => 'Tồn tại 2 loại type_sku', 'user_id' => $Merchant->merchant_id);
                }catch (Exception $e){
                    return array('error'=> false, 'code' => 'ERROR', 'error_message' => $e->getMessage());
                }
            }

            $CheckType  = $TypeSku[$val['type_sku']];
            if(!isset($Item[$val['__putaway']['warehouse']])){
                $Item[$val['__putaway']['warehouse']]  = [];
            }

            if(!isset($Item[$val['__putaway']['warehouse']][$val['type_sku']])){
                $Item[$val['__putaway']['warehouse']][$val['type_sku']] = [
                    'sku'       => 0,
                    'total'     => 0
                ];
            }

            if(!isset($Sku[$val['sku']])){
                $Sku[$val['sku']]   = 1;
                $Item[$val['__putaway']['warehouse']][$val['type_sku']]['sku']      += 1;
            }
            $Item[$val['__putaway']['warehouse']][$val['type_sku']]['total']    += 1;

            $ListCourier[]      = (int)$WareHouse[$val['__putaway']['warehouse']]['courier_id'];

            //Log detail Sku
            if(!isset($LogSku[$val['__putaway']['warehouse']][$val['sku'].'-'.$val['type_sku']])){
                $LogSku[$val['__putaway']['warehouse']][$val['sku'].'-'.$val['type_sku']]   = [
                    'log_id'        => 0,
                    'warehouse'     => strtoupper($val['__putaway']['warehouse']),
                    'sku'           => $val['sku'],
                    'type_sku'      => $val['type_sku'],
                    'total_item'    => 0,
                    'time_create'   => time()
                ];
            }

            $LogSku[$val['__putaway']['warehouse']][$val['sku'].'-'.$val['type_sku']]['total_item'] += 1;

        }

        // Lay kich thuoc, phi cho khach hang
        $ListStandard   = $this->getProductStandard(false);
        $Stock          = $this->getBmSellerStock(false);
        // Kiểm tra cấu hình khách hàng
        Input::merge(['user_id' => $Merchant->merchant_id,'time' => time()]);
        $StockMerchant  = $this->getStockByUser(false);
        if(!empty($StockMerchant)){
            $Stock  = [];
            foreach($StockMerchant as $val){
                $Stock[$val['code']][]  = $val;
            }
        }

        if(empty($ListStandard) || empty($Stock)){
            return array('error'=> false, 'code' => 'EMPTY_PRODUCT_STANDARD', 'error_message' => 'Lỗi');
        }

        // Lay ra phi luu kho theo khoang

        foreach($Stock[2] as $val){
            $Fee            = $val;
        }

        // lay phi cho hang
        $ListCourier    = array_unique($ListCourier);
        foreach($ListCourier as $val){
            $val    = (int)$val;
            Input::merge(['courier' => $val]);
            $PartnerStock[$val]        = $this->getBmSellerStock(false);
        }

        //Check discount  30 ngày đầu tiên
        $FirstTime = SellerProductItemModel::where('user_id', $Merchant->merchant_id)->whereNotNull('update_stocked')->min('update_stocked');
        // Trước 1/1/2017 thì free 100%
        if(empty($FirstTime) || (strtotime($FirstTime) < 1483203600)){
            $Check      = true;
        }else{
            $WareHouseCtrl          = new \accounting\WareHouseCtrl;
            $TimeBonusLunarHoliday  = $WareHouseCtrl->__check_lunar_holiday_discount($FirstTime, strtotime($this->time_start));


            // Check discount cho khách hàng
            $TimeBonusLunarHoliday  += 30*24;
            if((strtotime($this->time_start) - strtotime($FirstTime)) < $TimeBonusLunarHoliday*3600){
                // Mới nhập kho chưa quá 30 ngày free
                $Check      = true;
            }else{
                $Check      = false;
            }
        }

        //Khách có cấu hình riêng => ko được miễn phí
        if(!empty($StockMerchant)){
            $Check  = false;
        }

        DB::connection('ffdb')->beginTransaction();
        foreach($Item as $k => $value){
            // Phi khoang ke voi hvc
            $PartnerFee         = $PartnerStock[$WareHouse[$k]['courier_id']][2][0]['price'];
            $PartnerDiscountFee = $PartnerStock[$WareHouse[$k]['courier_id']][2][0]['discount'];
            try{
                $Log    = WareHouseFeeModel::firstOrCreate([
                    'date'          => $Date,
                    'user_id'       => $Merchant->merchant_id,
                    'warehouse'     => $k
                ]);

                $Log->payment_type  = $CheckType;
                $Log->total_item    = 0;
                $Log->total_sku     = 0;
                $Log->floor         = 0;
                $Log->total_fee     = 0;
                $Log->status        = 'WAITING';
                $Log->time_create   = time();

            }catch (Exception $e){
                return array('error'=> false, 'code' => 'INSERT_ERROR', 'error_message' => $e->getMessage());
            }

            $LogDetail      = [];

            foreach($value as $key => $val){
                $DiscountFee        = 0;
                $DiscountPartner    = 0;

                if(!isset($ListStandard[$key])){
                    return array('error'=> false, 'code' => 'ERROR_EMPTY_PRODUCT_STANDARD', 'error_message' => 'Lỗi tồn tại mã type_sku lạ');
                }

                $LimitItem  = !empty($ListStandard[$key]['max_item_by_volume_in_m2']) ? $ListStandard[$key]['max_item_by_volume_in_m2'] : $ListStandard[$key]['max_item_by_volume_in_m3'];
                $LimitSku   = !empty($ListStandard[$key]['max_sku_in_m2']) ? $ListStandard[$key]['max_sku_in_m2'] : $ListStandard[$key]['max_sku_in_m3'];

                $FloorItem      = ceil($val['total']/$LimitItem);
                $FloorSku       = ceil($val['sku']/$LimitSku);

                $TotalFloor     = ($FloorItem > $FloorSku) ? $FloorItem : $FloorSku;

                $WareHouseFee           = $Fee['price']*$TotalFloor;
                if($Check){
                    $DiscountFee            = $Fee['discount']*$TotalFloor;
                }

                $PartnerWareHouseFee    = $PartnerFee*$TotalFloor;
                $DiscountPartner        = $PartnerDiscountFee*$TotalFloor;


                
                $Log->total_item        += $val['total'];
                $Log->total_sku         += $val['sku'];
                $Log->floor             += $TotalFloor;
                $Log->total_fee         += $WareHouseFee;
                $Log->total_discount    += $DiscountFee;
                $Log->partner_total_fee         += $PartnerWareHouseFee;
                $Log->partner_total_discount    += $DiscountPartner;

                $LogDetail[]    = [
                    'log_id'                => $Log->id,
                    'warehouse'             => $k,
                    'type_sku'              => $key,
                    'total_item'            => $val['total'],
                    'total_sku'             => $val['sku'],
                    'standard_item'         => $LimitItem,
                    'standard_sku'          => $LimitSku,
                    'floor'                 => $TotalFloor,
                    'fee'                   => $WareHouseFee,
                    'discount_fee'          => $DiscountFee,
                    'partner_fee'           => $PartnerWareHouseFee,
                    'partner_discount_fee'  => $DiscountPartner,
                    'time_create'           => time()
                ];
            }

            // Insert Logdetail SKU
            foreach($LogSku[$k] as $m => $n){
                $LogSku[$k][$m]['log_id']   = $Log->id;
            }

            try{
                WareHouseFeeDetailModel::insert($LogDetail);
                WareHouseFeeSkuModel::insert($LogSku[$k]);
                $Log->status    = 'SUCCESS';
                $Log->save();
            }catch (Exception $e){
                return array('error'=> false, 'code' => 'INSERT_ERROR', 'error_message' =>  $e->getMessage());
            }
        }

        try{
            DB::connection('ffdb')->commit();
            $Merchant->time_inventory  = $Date;
            //Tính luôn phí tạm tính
            $Merchant = $this->CalculateWareHouseFee($Merchant);
            $Merchant->save();
        }catch (Exception $e){
            return array('error'=> false, 'code' => 'INSERT_ERROR', 'error_message' =>  $e->getMessage());
        }
        return array('error'=> false, 'code' => 'SUCCESS', 'error_message' => $Log);
    }

    /**
     * End tính phí lưu kho tạm tính theo ngày
     */

    /**
     * Đối soát định kỳ theo khoang kệ ngày 1 và 16 hàng tháng hoặc khi thay đổi hình thức tính phí
     */
    public function getCreateVerify(){
        $Time   = Input::get('time');

        if(!in_array($Time, [1,16])){
            return array('error'=> true, 'code'=> 'TIME_ERROR', 'error_message' => $Time);
        }

        $Time   = strtotime(date('2017-3-'.$Time.' 01:00:00'));

        $Date               = date('Y-m-d',$Time);

        if(WareHouseVerifyModel::where('date',$Date)->orWhere('status','<>','SUCCESS')->count() > 0){
            return array('error'=> true, 'code'=> 'VERIFY_EXISTS', 'error_message' => 'Đã đối soát ngày '.$Date);
        }


        //Lấy danh sách khách hàng tính phí  theo m2,m3 để tính phí lưu kho
        $ListUser   = UserWMSTypeModel::where('active',1)->whereIn('wms_type',[1,2])->get(['id', 'user_id', 'wms_type'])->toArray();
        if(empty($ListUser)){
            return array('error'=> true, 'code'=> 'EMPTY_DATA', 'error_message' => 'Không có danh sách đối soát ngày '.$Date);
        }

        foreach($ListUser as $val){
            $Insert[]   = [
                'date'              => $Date,
                'user_id'           => $val['user_id'],
                'config_warehouse'  => (int)$val['wms_type'],
                'status'            => 'WAITING',
                'time_create'       => time()
            ];
        }

        DB::connection('accbm')->beginTransaction();

        try{
            if(!empty($Insert)){
                WareHouseVerifyModel::insert($Insert);
            }

            DB::connection('accbm')->commit();
        }catch (Exception $e){
            return array('error'=> true, 'code'=> 'INSERT_ERROR', 'error_message' => $e->getMessage());
        }

        return array('error'=> false, 'code'=> 'SUCCESS', 'error_message' => 'Đối soát thành công ngày '.$Date);
    }

    private function __Transaction($Verify, $Merchant){
        $Transaction = [];

        if(($Verify->warehouse_fee - $Verify->discount_warehouse) > 0){
            $Transaction[]  = [
                'type'              => 2,
                'refer_code'        => (int)$Verify->id,
                'from_user_id'      => (int)$Verify->user_id,
                'to_user_id'        => (int)$this->master,
                'money'             => (int)$Verify->warehouse_fee - $Verify->discount_warehouse,
                'balance_before'    => $Merchant->balance,
                'note'              => 'Thanh toán phí lưu kho theo bảng kê số '.(int)$Verify->id,
                'time_create'       => time()
            ];
        }

        return $Transaction;
    }

    private function ResponseVerify($error, $Verify){

        if($error){
            try{
                $Verify->status = $this->code;
                $Verify->save();
            }catch (Exception $e){
                return Response::json(['error' => false, 'message' => 'UPDATE_FAIL']);
            }

            return array('error'=> false, 'code' =>  $this->code, 'error_message' => $this->message);
        }else{
            $MerchantModel      = new MerchantModel;
            $Merchant           = $MerchantModel->firstOrNew(['merchant_id' => $Verify->user_id]);

            if(!isset($Merchant->time_create)){
                $Merchant->time_create  = time();
            }

            if(!isset($Merchant->home_currency)){
                $Merchant->home_currency    = 1;
            }

            try{
                $Verify->time_accept    = time();
                $Verify->status         = 'SUCCESS';
                $Verify->balance        = $Merchant->balance;
                $Verify->save();
                DB::connection('accdb')->commit();
                return array('error'=> false, 'code' => 'SUCCESS', 'error_message' => 'Tiếp tục');
            }catch (Exception $e){
                return array('error'=> true, 'code' => 'ERROR', 'error_message' => $e->getMessage());
            }

            /*$MerchantModel      = new MerchantModel;
            $Merchant           = $MerchantModel->firstOrNew(['merchant_id' => $Verify->user_id]);

            if(!isset($Merchant->time_create)){
                $Merchant->time_create      = time();
                $Merchant->balance          = 0;
            }

            if(!isset($Merchant->home_currency)){
                $Merchant->home_currency    = 1;
            }

            //Get Master
            $Master = $this->getMasterId($Merchant->country_id);
            if(!isset($Master->merchant_id)){
                return array('error'=> false, 'code' =>  'MASTER_NOT_EXISTS', 'error_message' => 'Tài khoản master không tồn tại, currency: '.$Merchant->home_currency);
            }
            $this->master   = $Master->merchant_id;

            $Verify->time_accept    = time();
            $Verify->status         = 'SUCCESS';

            $Verify->balance        = $Merchant->balance;

            $Transaction            = $this->__Transaction($Verify, $Merchant);
            $TotalFee               = $Verify->warehouse_fee - $Verify->discount_warehouse;

            DB::connection('accdb')->beginTransaction();
            try{
                MerchantModel::where('merchant_id', (int)$this->master)->increment('balance', $TotalFee);
                MerchantModel::where('merchant_id', (int)$Merchant->merchant_id)->decrement('balance', $TotalFee);

                if(!empty($Transaction)){
                    TransactionModel::insert($Transaction);
                }

                $Verify->save();

                DB::connection('accdb')->commit();
                return array('error'=> false, 'code' => 'SUCCESS', 'error_message' => 'Tiếp tục');
            }catch (\Exception $e){
                return array('error'=> true, 'code' => 'ERROR', 'error_message' => $e->getMessage());
            }*/
        }
    }

    /*
     * tính phí theo m2,m3
     */
    private function __charge_volume($Verify){ // đối soát ngày 1 và 16
        if(date('d', strtotime($Verify->date)) > 1 && date('d', strtotime($Verify->date)) < 17){
            $First  = date('Y-m-1', strtotime($Verify->date));
            $End    = date('Y-m-d', (strtotime($Verify->date) - 86400));
        }elseif(date('d', strtotime($Verify->date)) == 1){
            $First  = date('Y-m-16',strtotime($Verify->date) - 86400);
            $End    = date('Y-m-d', (strtotime($Verify->date) - 86400));
        }else{// từ ngày 16 đến
            $First  = date('Y-m-16',strtotime($Verify->date));
            $End    = date('Y-m-d', (strtotime($Verify->date) - 86400));
        }

        $LogWareHouse   = WareHouseFeeModel::where('date','>=', $First)->where('date','<=',$End)
            ->where('user_id', $Verify->user_id)
            ->where('payment_type', $Verify->config_warehouse)
            ->groupBy('date')
            ->groupBy('warehouse')
            ->get(['date','warehouse',DB::raw(
                'sum(total_fee)         as total_fee,
                                                             sum(total_discount)    as total_discount,
                                                             sum(total_item)        as total_item,
                                                             sum(total_sku)         as total_sku,
                                                             sum(floor)             as floor'
            )])->toArray();

        $TotalFee           = [];
        $TotalDiscount      = [];
        $TotalItem          = [];
        $TotalSku           = [];
        $TotalFloor         = [];
        $Total              = [];
        if(!empty($LogWareHouse)){
            foreach($LogWareHouse as $val){
                if(!isset($Total[$val['warehouse']])){
                    $Total[$val['warehouse']]           = 0;
                    $TotalFee[$val['warehouse']]        = 0;
                    $TotalDiscount[$val['warehouse']]   = 0;
                    $TotalItem[$val['warehouse']]       = 0;
                    $TotalSku[$val['warehouse']]        = 0;
                    $TotalFloor[$val['warehouse']]      = 0;
                }

                $Total[$val['warehouse']]          += 1;
                $TotalFee[$val['warehouse']]       += $val['total_fee'];
                $TotalDiscount[$val['warehouse']]  += $val['total_discount']; // Khuyến mãi 100% phí lưu kho
                $TotalItem[$val['warehouse']]      += $val['total_item'];
                $TotalSku[$val['warehouse']]       += $val['total_sku'];
                $TotalFloor[$val['warehouse']]     += $val['floor'];
            }

            foreach($Total as $key => $val){
                $TotalFee[$key]         = $TotalFee[$key]/$val;
                $TotalDiscount[$key]    = $TotalDiscount[$key]/$val;
                $TotalItem[$key]        = $TotalItem[$key]/$val;
                $TotalSku[$key]         = $TotalSku[$key]/$val;
                $TotalFloor[$key]       = $TotalFloor[$key]/$val;
            }
        }

        $Verify->warehouse_fee          = ceil(array_sum($TotalFee));
        $Verify->discount_warehouse     = ceil(array_sum($TotalDiscount));
        $Verify->total_uid_storage      = ceil(array_sum($TotalItem));
        $Verify->total_sku              = ceil(array_sum($TotalSku));
        $Verify->floor                  = ceil(array_sum($TotalFloor));

        return ['error' => false,'verify'   => $Verify];
    }


    // Đối soát định kỳ
    private function  __charge_verify($Verify){
        $ChargeFee  = $this->__charge_volume($Verify);
        return $this->ResponseVerify($ChargeFee['error'], $Verify);
    }

    //Đối soát tính phí khi thay đổi
    private function __charge_change($Verify){
        if(!in_array(date('d', strtotime($Verify->date)), [1,16])){
            $ChargeFee  = $this->__charge_volume($Verify);
        }else{
            //chuyển đổi ngày 1, 16  free phí chuyển đổi
            $ChargeFee['error'] = false;
        }
        return $this->ResponseVerify($ChargeFee['error'], $Verify);
    }

    public function getVerify(){
        $Verify   = WareHouseVerifyModel::where('status','WAITING')->orderBy('id','ASC')->first();
        if(!isset($Verify->id)){
            return array('error'=> false, 'code' => 'EMPTY', 'error_message' => 'Thành Công');
        }

        $Verify->config_handling    = 3;

        try{
            //$Verify->status = 'PROCESSING';
            //$Verify->save();
        }catch (Exception $e){
            return Response::json(['error' => false, 'message' => 'UPDATE_FAIL']);
        }

        if($Verify->type == 1){ // Đối soát định kỳ
            return $this->__charge_verify($Verify);
        }else{ // Đối soát tính phí khi thay đổi
            return $this->__charge_change($Verify);
        }
    }

    /**
     * Thay đổi hình thức tính phí lưu kho Boxme
     */
    private function __create_verify($UserId, $Config){
        $Date       = date("Y-m-d");
        $TimeNow    = time();

        //Check List Item
        $ListItem = SellerProductItemModel::where('user_id', $UserId)->where('status',7)->where('type_sku','<>','NULL')->get(['serial_number','type_sku'])->toArray();
        $ListUID  = [];
        $TypeSku  = [];
        if(!empty($ListItem)){
            foreach($ListItem as $val){
                $val['serial_number']   = trim(strtoupper($val['serial_number']));
                $val['type_sku']        = trim(strtoupper($val['type_sku']));

                $ListUID[$val['serial_number']]    =  [
                    'user_id'       => $UserId,
                    'uid'           => $val['serial_number'],
                    'type_payment'  => $Config,
                    'time_start'    => $TimeNow,
                    'time_end'      => 0
                ];

                if(!isset($TypeSku[$val['type_sku']])){
                    $TypeSku[$val['type_sku']]  = 0;
                }

                $TypeSku[$val['type_sku']]  += 1;
            }
        }

        DB::connection('ffdb')->beginTransaction();
        // Insert Log
        $UserWMSTypeModel   = new \sellermodel\UserWMSTypeModel;
        try{
            \sellermodel\UserWMSTypeModel::where('user_id', $UserId)->where('active',1)->update(['active' => 0, 'end_date' => $TimeNow]);

            $UserWMSTypeModel::insert([
                'user_id'           => $UserId,
                'wms_type'          => $Config,
                'start_date'        => $TimeNow,
                'end_date'          => 0,
                'active'            => 1
            ]);

            \fulfillmentmodel\HistoryItemModel::where('user_id', $UserId)->where('time_end',0)->update(['time_end' => $TimeNow]);
            if(!empty($ListUID)){
                \fulfillmentmodel\HistoryItemModel::insert($ListUID);
            }
        }catch (Exception $e){
            $this->code             = 'INSERT_ERROR';
            $this->message          = 'Cập nhật lịch sử thất bại';
            return false;
        }

        $Insert = [
            'date'              => $Date,
            'user_id'           => $UserId,
            'config_warehouse'  => $Config,
            'type'              => 2,
            'time_create'       => $TimeNow,
            'status'            => 'WAITING'
        ];

        if($Config == 0){// Thu phí theo sản phẩm, tạo bảng kê chuyển đổi, không thu phí
            $Insert['time_accept']  = $TimeNow;
            $Insert['status']       = 'SUCCESS';
        }else{
            // chuyển về hình thức tính phí theo m2,m3
            if(!empty($TypeSku)){
                foreach($TypeSku as $key => $val){
                    if($this->type_sku[$key] != $Config){
                        $this->code             = 'EXISTS_SIZE_'.$val['type_sku'];
                        $this->message          = 'Không thể đổi dịch vụ do tồn tại kích cỡ '.$key.' của sản phẩm đang lưu kho';
                        return false;
                    }
                }
            }
        }

        try{
            WareHouseVerifyModel::insert($Insert);
            DB::connection('ffdb')->commit();
            return true;
        }catch (Exception $e){
            $this->code             = 'INSERT_VERIFY_ORDER_ERROR';
            $this->message          = 'Tạo đối soát lỗi';
            return false;
        }
    }

    public function getChangePaymentType(){
        $UserId             = Input::has('organiration')        ? (int)Input::get('organiration')           : 0;
        $PaymentType        = Input::has('payment_type')        ? (int)Input::get('payment_type')           : null;
        $Token              = Input::has('token')               ? trim(Input::get('token'))                 : '';

        $Config = \Config::get('config_api.domain.boxme.seller');

        if(empty($UserInfo) && $Token != $Config){ // gọi từ seller
            $this->code             = 'ERROR';
            $this->message          = 'Token không chính xác';
            return $this->ResponseData(true);
        }

        if(!isset($PaymentType)){
            $this->code             = 'ERROR';
            $this->message          = 'Chưa truyền loại chuyển đổi';
            return $this->ResponseData(true);
        }

        if(empty($UserId)){
            $this->code             = 'ERROR';
            $this->message          = 'Chưa truyền mã khách hàng';
            return $this->ResponseData(true);
        }

        $TypeUser = UserWMSTypeModel::where('user_id', $UserId)->where('active',1)->first();
        if(isset($TypeUser->id)){
            if($TypeUser->wms_type == $PaymentType){
                $this->code             = 'SUCCESS';
                $this->message          = 'Thành công';
                return $this->ResponseData(false);
            }

            // Check 15 ngày
            if(SellerProductItemModel::where('user_id', $UserId)->whereNotNull('update_stocked')->count() > 0){
                // Đã có sản phẩm nhập kho
                if(($TypeUser->start_date +86400*15) > time()){ // Thời gian thay đổi mỗi lần là 15 ngày
                    $this->code             = 'ERROR';
                    $this->message          = 'Thời gian thay đổi chưa đủ 15 ngày.Thời gian thay đổi gần nhất : '.date('d-m-Y H:i:s', $TypeUser->start_date);
                    return $this->ResponseData(true);
                }
            }
        }

        $Merchant           = MerchantModel::firstOrNew(['merchant_id' => $UserId]);
        if(!isset($Merchant->balance) || $Merchant->balance < 0){
            $this->code             = 'ERROR';
            $this->message          = 'Số dư hiện tại không đủ, bạn vui lòng nạp thêm tiền để sử dụng dịch vụ';
            return $this->ResponseData(true);
        }

        if(!$this->__create_verify($UserId, $PaymentType)){
            return $this->ResponseData(true);
        }else{
            return $this->ResponseData(false);
        }
    }

    private function ResponseData($error){
        return Response::json([
            'error'         => $error,
            'code'          => $this->code,
            'error_message' => $this->message
        ]);
    }

    public function getVerifyPartner(){
        $Verify     = WareHouseVerifyModel::where('status','SUCCESS')->where('is_finalized',0)
                                          ->where('date','>=','2016-12-01')
                                          ->orderBy('date','ASC')->first();

        if(!isset($Verify->id)){
            return array('error'=> false, 'code' => 'EMPTY', 'error_message' => 'Thành công');
        }

        $Verify->is_finalized   = 1;

        $WareHouse      = $this->getWareHouseBoxme(false);
        $WareHouseCtrl  = new \accounting\WareHouseCtrl;
        $Date           = $WareHouseCtrl->__get_date_calculator($Verify->date);

        $WareHouseFee   = \fulfillmentmodel\WareHouseFeeModel::where('date','>=', $Date['first'])
                                                             ->where('date','<=',$Date['end'])
                                                             ->where('user_id'  , $Verify->user_id)
                                                             ->where('payment_type'  , $Verify->config_warehouse)
                                                             ->groupBy('date')
                                                             ->groupBy('warehouse')
                                                             ->get(['date','warehouse',DB::raw(
                                                                'sum(partner_total_fee)         as partner_total_fee,
                                                                 sum(partner_total_discount)    as partner_total_discount,
                                                                 sum(total_item)                as total_item,
                                                                 sum(total_sku)                 as total_sku,
                                                                 sum(floor)                     as floor'
                                                             )])->toArray();

        if(empty($WareHouseFee)){
            try{
                $Verify->save();
            }catch (Exception $e){
                return array('error'=> true, 'code' => 'UPDATE_ERROR');
            }
            return array('error'=> false, 'code' => 'SUCCESS', 'data'   => $Verify);
        }

        $TotalFee           = [];
        $TotalDiscount      = [];
        $TotalItem          = [];
        $TotalSku           = [];
        $TotalFloor         = [];
        $Total              = [];
        foreach($WareHouseFee as $val){
            if(!isset($Total[$val['warehouse']])){
                $Total[$val['warehouse']]   = 1;
                $TotalFee[$val['warehouse']]       = $val['partner_total_fee'];
                $TotalDiscount[$val['warehouse']]  = $val['partner_total_discount'];
                $TotalItem[$val['warehouse']]      = $val['total_item'];
                $TotalSku[$val['warehouse']]       = $val['total_sku'];
                $TotalFloor[$val['warehouse']]     = $val['floor'];
            }else{
                $Total[$val['warehouse']]   += 1;
                $TotalFee[$val['warehouse']]       += $val['partner_total_fee'];
                $TotalDiscount[$val['warehouse']]  += $val['partner_total_discount'];
                $TotalItem[$val['warehouse']]      += $val['total_item'];
                $TotalSku[$val['warehouse']]       += $val['total_sku'];
                $TotalFloor[$val['warehouse']]     += $val['floor'];
            }

            if(!isset($Partner[$val['warehouse']])){
                $PartnerVerifyModel = new \partnermodel\PartnerVerifyModel;
                $Partner[$val['warehouse']]  = $PartnerVerifyModel->firstOrCreate([
                    'date' => $Verify->date, 'warehouse' => $val['warehouse'], 'courier' => $WareHouse[$val['warehouse']]['courier_id']
                ]);

                $Partner[$val['warehouse']]->time_update         = time();
                if(!isset($Partner[$val['warehouse']]->time_create) || empty($Partner[$val['warehouse']]->time_create)){
                    $Partner[$val['warehouse']]->time_create     = time();
                }
            }
        }

        foreach($Total as $key => $val){
            $Partner[$key]['warehouse_fee']         += ceil($TotalFee[$key]/$Total[$key]);
            $Partner[$key]['discount_warehouse']    += ceil($TotalDiscount[$key]/$Total[$key]);
            $Partner[$key]['total_uid_storage']     += ceil($TotalItem[$key]/$Total[$key]);
            $Partner[$key]['total_sku']             += ceil($TotalSku[$key]/$Total[$key]);
            $Partner[$key]['floor']                 += ceil($TotalFloor[$key]/$Total[$key]);
        }

        $WareHouseFeeModel  = new \fulfillmentmodel\WareHouseFeeModel;
        $ListLogId          = $WareHouseFeeModel::where('date','>=', $Date['first'])
                                                ->where('date','<=',$Date['end'])
                                                ->where('user_id'  , $Verify->user_id)
                                                ->where('payment_type'  , $Verify->config_warehouse)
                                                ->get(['id', 'warehouse']);

        $InsertRefer    = [];
        foreach($ListLogId as $val){
            $InsertRefer[]  = [
                'partner_id'  => $Partner[$val['warehouse']]->id,
                'log_id'      => $val['id']
            ];
        }

        \partnermodel\PartnerReferModel::insert($InsertRefer);

        // End Transaction
        try{
            if(!empty($Partner)){
                foreach($Partner as $val){
                    $val->save();
                }
            }

            $Verify->save();
            DB::connection('accbm')->commit();
        }catch (Exception $e){
            return array('error'=> false, 'code' => 'ERROR', 'message_error' => $e->getMessage());
        }

        return array('error'=> false, 'code' => 'SUCCESS', 'message_error' => 'Thành Công');
    }

    // Push Pipe Journey
    public function postJourneyCreate(){
        $validation = Validator::make(Input::all(), array(
            'type'              => 'required|in:10,11,12',
            'group'             => 'required|numeric|min:1',
            'pipe_status'       => 'required|numeric|min:1',
            'tracking_code'     => 'required',
            'token'             => 'required|in:'.\Config::get('config_api.domain.boxme.ops'),
        ));

        //error
        if ($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $PipeStatus         = Input::has('pipe_status')         ? (int)Input::get('pipe_status')            : 0;
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : 0;
        $TrackingCode       = Input::has('tracking_code')       ? Input::get('tracking_code')               : '';
        $Note               = Input::has('note')                ? Input::get('note')                        : '';
        $Type               = Input::has('type')                ? (int)Input::get('type')                   : 0;

        try{
            \omsmodel\PipeJourneyModel::insert ([
                'user_id'           => 1,
                'tracking_code'     => $TrackingCode,
                'type'              => $Type,
                'group_process'     => $Group,
                'pipe_status'       => $PipeStatus,
                'note'              => $Note,
                'time_create'       => time()
            ]);
        }catch (Exception $e){
            return Response::json([
                'error'         => true,
                'message'       => 'INSERT_ERROR',
                'error_message' => $e->getMessage()
            ]);
        }

        return Response::json([
            'error'             => false,
            'message'           => 'SUCCESS',
            'error_message'     => 'Thành công'
        ]);
    }
}


