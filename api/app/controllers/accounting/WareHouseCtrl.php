<?php namespace accounting;

class WareHouseCtrl extends \BaseCtrl{
//    Thời gian lưu kho shipchung khuyến mãi cho khách hàng
    private $time_discount = 1440;


//    $TimeStart  Thời gian bắt đầu lưu kho
//    $TimeEnd    Thời gian xuất kho
//    $Stock      Bảng phí lưu kho theo cấu hình cung
//    $StockMerchant Bảng phí lưu kho theo cấu hình khách hàng
//    $HistoryItem Lịch sử lưu kho theo khoang kệ của sản phẩm
    private function __get_warehouse_fee($TimeStart, $TimeEnd, $Stock, $StockMerchant, $HistoryItem){
        $TotalFee       = 0;
        $TotalDiscount  = 0;

//      Thời gian miễn phí lưu kho 2 tháng
        $Data           = [];
        $TimeDiscount   = $this->time_discount;

//        Khách hàng có cấu hình phí riêng sẽ ko được miễn phí 2 tháng lưu kho
        if(!empty($StockMerchant)){
            $TimeDiscount = 0;
        }

        $Time           = $TimeEnd;

//      Gom 2 bảng phí theo thời gian vào làm 1, ưu tiên bảng phí với phí = 0 trước rồi đến  của khách hàng  => bảng phí chung
        if(!empty($StockMerchant)){
            $WarehouseStock = array_merge($Stock, $StockMerchant);
            $WarehouseStock = array_values(array_sort($WarehouseStock, function($value){
                return $value['time_start'];
            }));

            foreach($WarehouseStock as $key => $val){
                if($val['time_end'] == 0){
                    $val['time_end'] = $Time;
                }

                if($key == 0){
                    // Key xác định data trước đó
                    $PreKey = 0;
                    $Data[$key] = $val;
                }else{
                    $Item = $val;
                    //Nếu thời gian bắt đầu của chu trình mới nhỏ hơn thời gian kết thúc của chu trình cũ
                    if($val['time_start'] < $Data[$PreKey]['time_end']){
                        if((isset($Data[$PreKey]['user_id']) && isset($val['user_id'])) || (!isset($Data[$PreKey]['user_id']) && !isset($val['user_id']))){
//                          chu trình cũ và chu trình mới đều là của khách hàng   hoặc đều là cấu hình chung => ưu tiên cái mới
                            $Data[$PreKey]['time_end']  = $val['time_start'];

                            // Set giá trị prekey mới
                            $PreKey                     = $key*100;
                            $Data[$PreKey]              = $val;
                        }else{ // ngược lại
                            if(isset($val['user_id'])){//Cấu hình mới là của khách hàng, ưu tiên mới
                                $Data[$PreKey]['time_end']  = $val['time_start'];

                                // Set giá trị prekey mới
                                $PreKey                     = $key*100;
                                $Data[$PreKey]              = $val;
                            }elseif(isset($Data[$PreKey]['user_id'])){//Cấu hình cũ là của khách hàng, ưu tiên cũ
                                $val['time_start']          = $Data[$PreKey]['time_end'];

                                $PreKey                     = $key*100;
                                $Data[$PreKey]              = $val;
                            }else{//đều là cấu hình chung => ưu tiên mới
                                $Data[$PreKey]['time_end']  = $val['time_start'];

                                // Set giá trị prekey mới
                                $PreKey                     = $key*100;
                                $Data[$PreKey]              = $val;
                            }
                        }
                    }elseif($val['time_start'] > $Data[$PreKey]['time_end']){// khoảng từ pre key time_end đến time_start
                        //Nếu trống 1 khoảng thời gian ở giữ chưa có thì lấy theo cấu hình chung trước đó
                        foreach($Stock as $k => $item){
                            if($item['time_end'] == 0){
                                $item['time_end'] = $Time;
                            }
                            if($item['time_start'] <= $val['time_start'] && $item['time_end'] >= $Data[$PreKey]['time_end']){
//                              chứa khoảng cần tính

                                if($Data[$PreKey]['time_end'] > $item['time_start']){
                                    $item['time_start']  = $Data[$PreKey]['time_end'];
                                }

                                if($val['time_start'] <=  $item['time_end']){
                                    $item['time_end']  = $val['time_start'];
                                }

                                $PreKey         = $key*100 + $k;
                                $Data[$PreKey]  = $item;
                            }
                        }

                        $PreKey = $PreKey + 1;
                        $Data[$PreKey]  = $val;
                    }else{
                        $PreKey         = $key*100;
                        $Data[$PreKey]  = $val;
                    }
                }
            }
        }else{
            $Data   = $Stock;
        }

        $StockedStart   = $FlagStart = 0;
        $StockedEnd     = $FlagEnd   = 0;
        $Fee            = 0;
        $Discount       = 0;

        foreach($Data  as $val){
//          $StockedStart, $FlagStart thời gian bắt đầu mỗi chu trình
//          $StockedEnd, $FlagEnd thời gian kết thúc mỗi chu trình
//          B2 check thời gian lưu kho theo khoang kệ
//          B3 ốp thời gian miễn phí 2 tháng

            $ConfigDiscount     = 0; // Miễn phí theo cấu hình chung
            $FeeItem            = 0; //Phí theo item
            $DiscountHistory    = 0; // Miễn phí theo thời gian lưu kho theo khoang kệ
            $DiscountItem       = 0; // Discount theo item

            if(empty($StockedStart)){
                if($TimeStart < $val['time_start']){
                    $StockedStart  = $val['time_start'];
                }else{
                    $StockedStart  =  $TimeStart;
                }
            }else{
                // FlagEnd luôn nhỏ hơn hoặc = Time En
                $StockedStart = $FlagEnd;
            }

            if(empty($StockedEnd)){
                if($TimeEnd > $val['time_end']){
                    $StockedEnd = $val['time_end'];
                }else{
                    $StockedEnd = $TimeEnd;
                }
            }else{
                if($val['time_end'] <= $TimeEnd){
                    $StockedEnd = $val['time_end'];
                }else{
                    $StockedEnd = $TimeEnd;
                }
            }

            if($StockedEnd < $StockedStart){
                $StockedEnd = $StockedStart;
            }

            $TimeStock  = ceil(($StockedEnd - $StockedStart)/3600);
            $FeeItem    = $TimeStock*$val['price'];
            $Fee        += $FeeItem;

            //Check thời gian lưu kho theo khoang kệ
            if(!empty($HistoryItem)){
                foreach($HistoryItem as $item){
                    if(($item['time_start'] < $val['time_end']) && ($item['time_end'] >= $val['time_start'])){ // Trong phạm vi ảnh hưởng
                        if($item['time_start'] <= $val['time_start']){
                            $item['time_start'] = $val['time_start'];
                        }
                        if($item['time_end'] >= $val['time_end']){
                            $item['time_end'] = $val['time_end'];
                        }

                        $DiscountHistory    += ceil(($item['time_end'] - $item['time_start'])/3600)*$val['price'];
                    }
                }
            }

            //ốp thời gian discount 2 tháng cho mỗi uid vào
            if($TimeDiscount > 0 && $TimeStock > 0 && $val['price'] > 0){
                if($TimeDiscount >= $TimeStock){
                    $TimeDiscount -= $TimeStock;
                    $ConfigDiscount = $TimeStock.$val['price'];
                }else{
                    $ConfigDiscount = $TimeDiscount.$val['price'];
                    $TimeDiscount = 0;
                }
            }

            $DiscountItem   = ((($TimeStock*$val['discount']) >= $ConfigDiscount) ? (($TimeStock*$val['discount']) >= $ConfigDiscount) : $ConfigDiscount);
            $DiscountItem  += $DiscountHistory;
            $DiscountItem   = ($DiscountItem > $FeeItem) ? $FeeItem : $DiscountItem;

            $Discount   += $DiscountItem;

            $FlagEnd    = $StockedEnd;
        }
        
        return ['fee' => $Fee, 'discount' => $Discount, 'time_stock' => $TimeStart, 'time_end' => $TimeEnd];
    }

    // Tính phí lưu kho tạm tính
    public function getWarehouseFee($UserId){
        $ListItem   = \fulfillmentmodel\SellerProductItemModel::where('status',7)
                                                                ->where('user_id', $UserId)
                                                                ->where('type_sku','<>','NULL')
                                                                ->where('update_stocked','<>','NULL')
                                                                ->orderBy('update_stocked','ASC')
                                                                ->get(['user_id','serial_number','sku','status','update_stocked'])
                                                                ->toArray();

        if(empty($ListItem)){
            return 0;
        }

        $ListUId            = [];
        $UId                = [];
        $MinTime            = 0;
        $MaxTime            = time();
        foreach($ListItem as $val){
            $val['time_stock']  = strtotime($val['update_stocked']);
            if(empty($MinTime)){
                $MinTime = $val['time_stock'];
            }

            $val['uid']         = trim(strtoupper($val['serial_number']));
            $val['time_packge'] = time();
            $val['time_stock']  = strtotime($val['update_stocked']);

            //Check Lỗi
            if($val['time_stock'] < 0 && $val['time_stock'] > $val['time_packge']){
                break;
            }

            $ListUId[]  = $val['uid'];
            $UId[$val['uid']]   = [
                'uid'                       => $val['uid'],
                'time_stock'                => $val['time_stock'],
                'time_packge'               => $val['time_packge'],
                'warehouse_fee'             => 0,
                'discount_warehouse_fee'    => 0,
                'return'                    => false
            ];
        }

        //Kiểm tra cấu hình riêng của khách
        $StockMerchant   = \fulfillmentmodel\StockByUserModel::where('user_id', $UserId)->where('code',1)
            ->where(function($query) use($MinTime){
                $query->where('time_end',0)
                    ->orWhere('time_end','>=',$MinTime);
            })
            ->where('time_start','<=', $MaxTime)
            ->orderBy('time_start','ASC')
            //->remember(10)
            ->get()->toArray();
        //Cấu hình chung
        $Stock   = \fulfillmentmodel\StockModel::where('courier', 0)->where('code',1)
            ->where(function($query) use($MinTime){
                $query->where('time_end',0)
                    ->orWhere('time_end','>=',$MinTime);
            })
            ->where('time_start','<=', $MaxTime)
            ->orderBy('time_start','ASC')
            //->remember(10)
            ->get()->toArray();


        //Check return
        $ListReturn = \warehousemodel\ReturnItemModel::whereIn('uid', $ListUId)
            ->where('created','<',$MaxTime)
            ->orderBy('created','ASC')
            ->get(['id','uid','created','updated'])->toArray();
        if(!empty($ListReturn)){
            foreach($ListReturn as $val){
                $val['uid']         = trim(strtoupper($val['uid']));
                $val['created']     = strtotime($val['created']);

                if($UId[$val['uid']]['time_stock'] > $val['created']){
                    $UId[$val['uid']]['time_stock'] = strtotime($val['updated']);
                    if(empty($StockMerchant)){ // Nếu khách hàng có cấu hình phí riêng => ko được miễn phí hoàn
                        $UId[$val['uid']]['return']     = true;
                    }
                }
            }
        }

        //Lấy danh sách lịch sử lưu kho theo khoang kệ
        $CheckLog   = \fulfillmentmodel\HistoryItemModel::whereRaw("uid in ('". implode("','", $ListUId) ."')")
            ->where('type_payment','>',0)
            ->where('time_start','>=',strtotime($MinTime))
            ->where('time_end','<=',strtotime($MaxTime))
            ->orderBy('time_start', 'ASC')
            ->get()->toArray();
        if(!empty($CheckLog)){
            foreach($CheckLog as $val){
                $val['uid']                 = trim(strtoupper($val['uid']));
                $HistoryItem[$val['uid']][] = $val;
            }
        }

        //Check phí
        $TotalFee       = 0;
        $TotalDiscount  = 0;
        foreach($UId as $val){
            $ItemHistory = isset($HistoryItem[$val['uid']]) ? $HistoryItem[$val['uid']] : [];
            $Fee = $this->__get_warehouse_fee($val['time_stock'], $val['time_packge'], $Stock, $StockMerchant, $ItemHistory);
            if($val['return']){
                $Fee['discount'] = $Fee['fee'];
            }

            $TotalFee       += $Fee['fee'];
            $TotalDiscount  += $Fee['discount'];
        }

        return $TotalFee - $TotalDiscount;
    }

    public function __warehouse_pallet_fee($UserId, $TypePayment, $Date){
        $DateCalculator = $this->__get_date_calculator($Date);
        $First          = $DateCalculator['first'];
        $End            = $DateCalculator['end'];

        $LogWareHouse   = \fulfillmentmodel\WareHouseFeeModel::where('date','>=', $First)->where('date','<=',$End)
            ->where('user_id', $UserId)
            ->where('payment_type', $TypePayment)
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

        return [
            'warehouse_fee'             => ceil(array_sum($TotalFee)),
            'discount_warehouse_fee'    => ceil(array_sum($TotalDiscount)),
            'total_uid_storage'         => ceil(array_sum($TotalItem)),
            'total_sku'                 => ceil(array_sum($TotalSku)),
            'floor'                     => ceil(array_sum($TotalFloor))
        ];

    }

    public function __get_date_calculator($Date){
        if(date('d', strtotime($Date)) > 1 && date('d', strtotime($Date)) < 17){
            $First  = date('Y-m-1', strtotime($Date));
            $End    = date('Y-m-d', (strtotime($Date) - 86400));
        }elseif(date('d', strtotime($Date)) == 1){
            $First  = date('Y-m-16',strtotime($Date) - 86400);
            $End    = date('Y-m-d', (strtotime($Date) - 86400));
        }else{// từ ngày 16 đến
            $First  = date('Y-m-16',strtotime($Date));
            $End    = date('Y-m-d', (strtotime($Date) - 86400));
        }

        return ['first' => $First, 'end'    => $End];
    }

    public function getWarehouseFeePallet($UserId, $TypePayment, $Date){
        $Fee = $this->__warehouse_pallet_fee($UserId, $TypePayment, $Date);
        return $Fee['warehouse_fee'] - $Fee['discount_warehouse_fee'];
    }

    public function __check_lunar_holiday_discount($TimeStart, $TimeEnd){
        $TimeDiscount   = 0;
        $LastTime       = ($TimeEnd > 1485968400) ? 1485968400 : $TimeEnd;
        if($TimeStart < 1485363600){ // thời gian nhập kho nhỏ hơn 26/1/2017
            if($TimeEnd > 1485363600){// thời gian đóng gói >= 26/1/2017
                $TimeDiscount   = ceil(($LastTime - 1485363600)/3600);
            }
        }elseif(($TimeStart > 1485363600) && ($TimeStart < 1485968400)){
            // nếu time nhập kho lớn hơn 26/1 và time nhập kho nhỏ hơn 2/2
            $TimeDiscount  = ceil(($LastTime - $TimeStart)/3600);
        }
    }
}
