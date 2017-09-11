<?php namespace accounting;

use ordermodel\InvoiceModel;
use ordermodel\OrdersModel;
use ordermodel\AddressModel;
use WardModel;
use sellermodel\BusinessModel;

class InvoiceCtrl extends BaseCtrl {
    private $data   = [];
    private $sum    = [];
    private $total  = 0;
    private $user   = [];


    public function __contruct(){

    }

    public function getIndex()
    {
        $page                   = Input::has('page')                    ? (int)Input::get('page')                   : 1;
        $itemPage               = 20;
        $TimeStart              = Input::has('time_start')              ? trim(Input::get('time_start'))            : '';
        $TimeEnd                = Input::has('time_end')                ? trim(Input::get('time_end'))              : '';
        $FirstShipmentStart     = Input::has('first_shipment_start')    ? trim(Input::get('first_shipment_start'))  : '';
        $Merchant               = Input::has('merchant')                ? trim(Input::get('merchant'))              : '';
        $Cmd                    = Input::has('cmd')                     ? strtoupper(trim(Input::get('cmd')))       : '';

        $Model = new InvoiceModel;

        if(!empty($Merchant)){
            if (filter_var($Merchant, FILTER_VALIDATE_EMAIL)){  // search email
                $FieldUser  = 'email';
            }elseif(filter_var((int)$Merchant, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>1,'max_range'=>7)))){
                $Model  = $Model->where('id',(int)$Merchant);
            }elseif(filter_var((int)$Merchant, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $FieldUser  = 'phone';
            }else{ // search fullname
                $FieldUser  = 'fullname';
            }

            if(isset($FieldUser)){
                $User   = \User::where($FieldUser,'LIKE','%'.$Merchant.'%')->get(array('id','fullname','phone','email'))->toArray();
                if(empty($User)){
                    return $this->ResponseData(false);
                }

                $ListUser       = array_fetch($User,'id');
                $Model          = $Model->whereIn('user_id',$ListUser);
            }
        }

        if(!empty($TimeStart)){
            $Model = $Model->where('time_create','>=',$TimeStart);
        }

        if(!empty($TimeEnd)){
            $Model = $Model->where('time_create','<',$TimeEnd);
        }

        if(!empty($FirstShipmentStart)){
            $ListUser = $this->__get_user_boxme($FirstShipmentStart);
            if(empty($ListUser)){
                return $this->ResponseData();
            }
            $Model  = $Model->whereIn('user_id', $ListUser);
        }

        if($Cmd == 'EXPORT'){
            return $this->ExportExcel($Model->get()->toArray());
        }

        $ModelSum = clone $Model;

        $this->sum  = $ModelSum->first(array(DB::raw('
                                            count(*) as count,
                                            sum(total_success)  as total_success,
                                            sum(total_return)   as total_return,
                                            sum(total_backlog)  as total_backlog,
                                            sum(total_lsuccess) as total_lsuccess,
                                            sum(total_lreturn) as total_lreturn,

                                            sum(
                                                total_sc_pvc + total_sc_cod + total_sc_pbh + total_sc_pvk + total_sc_pch + total_premote +
                                                 total_pclearance + total_sc_plk + total_sc_pdg + total_sc_pxl +
                                                 total_lsc_pvc + total_lsc_cod + total_lsc_pbh +  total_lsc_pvk + total_lsc_pch +
                                                total_lsc_pclearance + total_lsc_premote + total_lsc_plk + total_lsc_pdg + total_lsc_pxl 
                                                - total_sc_discount_pvc - total_sc_discount_cod - total_lsc_discount_pvc - total_lsc_discount_cod
                                                - total_discount_plk - total_discount_pdg - total_discount_pxl - total_ldiscount_plk - total_ldiscount_pdg 
                                                - total_ldiscount_pxl
                                            ) as total_fee,

                                            sum(total_money_collect + total_lmoney_collect) as total_money_collect
                                            ')));

        $this->total    = (int)$this->sum['count'];

        if($this->total > 0){
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;

            $this->data = $Model->orderBy('time_create','DESC')->skip($offset)->take($itemPage)->get()->toArray();

            if(!isset($User)){
                $ListId     = array_fetch($this->data,'user_id');
                $User       = \User::whereIn('id',$ListId)->get(array('id','fullname','phone','email'))->toArray();
            }

            if(!empty($User)){
                foreach($User as $val){
                    $this->user[$val['id']] = $val;
                }
            }
        }

        return $this->ResponseData(false);
    }

    private function ResponseData($error){
        $Cmd                = Input::has('cmd')     ? strtolower(trim(Input::get('cmd')))       : '';

        if($Cmd == 'export'){
            return $this->ExportExcel([]);
        }

        return Response::json([
            'error'         => $error,
            'code'          => 'success',
            'error_message' => 'Thành công',
            'item_page'     => 20,
            'total'         => $this->total,
            'data'          => $this->data,
            'sum'           => $this->sum,
            'user'          => $this->user
        ]);
    }

    public function ExportExcel($Data){
        $ListUser   = [];

        if(!empty($Data)){
            foreach($Data as $val){
                $ListId[]   = $val['user_id'];
            }
            $ListId = array_unique($ListId);
            $User   = \User::whereRaw("id in (". implode(",", $ListId) .")")->get(array('id','fullname','phone','email'))->toArray();

            $ListUser   = [];
            if(!empty($User)){
                foreach($User as $val){
                    $ListUser[$val['id']] = $val;
                }
            }
        }

        Excel::selectSheetsByIndex(0)->load('/data/www/html/storage/template/accounting/danh_sach_hoa_don.xls', function($reader) use($Data,$ListUser) {
            $reader->sheet(0,function($sheet) use($Data,$ListUser)
            {
                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        'Tháng '.$val['month']. ' Năm '.$val['year'],
                        isset($ListUser[$val['user_id']]) ? $ListUser[$val['user_id']]['fullname'] : '',
                        isset($ListUser[$val['user_id']]) ? $ListUser[$val['user_id']]['email'] : '',
                        isset($ListUser[$val['user_id']]) ? ' '.(string)$ListUser[$val['user_id']]['phone'] : '',

                        $val['total_success'],
                        $val['total_return'],
                        $val['total_backlog'],

                        $val['total_lsuccess'],
                        $val['total_lreturn'],

                        number_format($val['total_sc_pvc'] + $val['total_lsc_pvc']),
                        number_format($val['total_sc_cod'] + $val['total_lsc_cod']),
                        number_format($val['total_sc_pbh'] + $val['total_lsc_pbh']),
                        number_format($val['total_sc_pvk'] + $val['total_lsc_pvk']),
                        number_format($val['total_sc_pch'] + $val['total_lsc_pch']),
                        number_format($val['total_premote'] + $val['total_lsc_premote']),
                        number_format($val['total_pclearance'] + $val['total_lsc_pclearance']),

                        number_format($val['total_sc_discount_pvc'] + $val['total_lsc_discount_pvc']),
                        number_format($val['total_sc_discount_cod'] + $val['total_lsc_discount_cod']),

                        number_format($val['total_sc_plk'] + $val['total_lsc_plk']),
                        number_format($val['total_sc_pxl'] + $val['total_lsc_pxl']),
                        number_format($val['total_sc_pdg'] + $val['total_lsc_pdg']),

                        number_format($val['total_discount_plk'] + $val['total_ldiscount_plk']),
                        number_format($val['total_discount_pxl'] + $val['total_ldiscount_pxl']),
                        number_format($val['total_discount_pdg'] + $val['total_ldiscount_pdg']),

                        number_format($val['total_money_collect'] + $val['total_lmoney_collect']),
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        },'UTF-8',true)->export('xls');
    }

    // excel detail
    public function getExportExcel(){
        $InvoiceId  = Input::has('id')  ? (int)Input::get('id') : 0;

        $Invoice    = InvoiceModel::where('id',$InvoiceId)->first();
        //lay thong tin ma so thue 
        $InfoTax = BusinessModel::where('user_id',$Invoice['user_id'])->first();

        Input::merge(['group' => 3]);
        $GroupStatus    = $this->getStatusByGroup(false);

        if(!isset($Invoice->id)) dd('empty');

        /**
         *  Set time
         */
        if($Invoice->month == 12){
            $TimeAcceptEnd      = strtotime(date(($Invoice->year + 1).'-01-01 00:00:00'));
            $TimeAcceptStart    = strtotime(date($Invoice->year.'-'.($Invoice->month - 2).'-'.'01 00:00:00'));
        }else{
            if($Invoice->month == 1){
                $TimeAcceptStart    = strtotime(date(($Invoice->year - 1).'-11-'.'01 00:00:00'));
            }else{
                $TimeAcceptStart    = strtotime(date($Invoice->year.'-'.($Invoice->month - 2).'-'.'01 00:00:00'));
            }

            $TimeAcceptEnd      = strtotime(date($Invoice->year.'-'.($Invoice->month + 1).'-'.'01 00:00:00'));
        }
        $TimePickupEnd      = $TimeAcceptEnd;
        $TimePickupStart    = strtotime(date($Invoice->year.'-'.($Invoice->month).'-'.'01 00:00:00'));
        $ListSuccess    = [];
        $ListReturn     = [];
        $ListLast       = []; // Tồn tháng trước

        $City       = [];
        $Courier    = [];
        $Address    = [];
        $User       = [];

        $OrderModel = new OrdersModel;
        // Lấy list đơn hàng thành công trong kỳ
        $OrderModel
            ->where('time_create','>=',$TimeAcceptStart - 86400*62)
            ->where('time_create','<',$TimeAcceptEnd)
            ->where('time_accept','>=',$TimeAcceptStart)
            ->where('time_accept','<',$TimeAcceptEnd)
            ->where('invoice_id', $Invoice->id)
            ->orderBy('time_accept', 'ASC')
            ->with(['OrderDetail','OrderFulfillment'])
            ->chunk('1000', function($query) use(&$TimePickupStart, &$ListLast, &$GroupStatus, &$ListSuccess, &$ListReturn, &$ListCityId, &$ListToAddress, &$ListUser) {
                foreach ($query as $val) {
                    $val = $val->toArray();

                    if($val['time_pickup'] < $TimePickupStart){ // Tồn tháng trước
                        $ListLast[] = $val;
                    }else{ // trong tháng này
                        if(in_array($val['status'], $GroupStatus[19])){
                            $ListSuccess[]  = $val;
                        }else{
                            $ListReturn[]   = $val;
                        }
                    }

                    if($val['from_city_id'] > 0) $ListCityId[]     = $val['from_city_id'];
                    if($val['to_city_id'] > 0)   $ListCityId[]     = $val['to_city_id'];
                    $ListUser[]       = $val['from_user_id'];
                    $ListToAddress[]  = $val['to_address_id'];
                }
            });

        $Courier    = $this->getCourier(false);
        $Service    = $this->getService(false);
        $Status     = $this->getStatus(false);
        $ListCountry    = $this->getCountry(false);
        $Country        = [];
        if(!empty($ListCountry)){
            foreach($ListCountry as $val){
                $Country[$val['id']]    = $val['country_name'];
            }
        }

        if(isset($ListToAddress) && !empty($ListToAddress)){
            $AddressModel   = new AddressModel;
            $ListAddress    = $AddressModel::whereRaw("id in (". implode(",", $ListToAddress) .")")->get()->toArray();
        }

        if(isset($ListAddress) && !empty($ListAddress)){
            foreach($ListAddress as $val){
                $Address[$val['id']]    = $val;
                if($val['city_id'] > 0)   $ListCityId[]     = $val['city_id'];
            }
        }

        $ListUser           = array_unique($ListUser);
        $ListCityId         = array_unique($ListCityId);

        if(!empty($ListUser)){
            $User   = $this->getUser($ListUser);
        }

        if(!empty($ListCityId)){
            $City = $this->getCityById($ListCityId);
        }

        Excel::selectSheetsByIndex(0,1,2)->load('/data/www/html/storage/template/accounting/chi_tiet_hoa_don_new.xls', function($reader) use($ListSuccess, $ListReturn, $ListLast, $Courier, $Service, $Country, $City, $Address, $User, $Status,$InfoTax) {
            // Thành công
            $reader->sheet(0,function($sheet) use($ListSuccess, $Courier, $Service, $Country, $City, $Address, $User, $Status, $InfoTax)
            {
                //
                // if(!empty($InfoTax)){
                //     $sheet->row(6, array('Tên khách hàng : '.$InfoTax['name']));
                //     $sheet->row(7, array('Địa chỉ : '.$InfoTax['address'].', '.$District[$InfoTax['district_id']].', '.$City[$InfoTax['city_id']]));
                //     $sheet->row(8, array('Mã số thuế : '));
                // }
                $i = 1;
                foreach ($ListSuccess as $val) {
                    $TotalFee = 0;
                    if(isset($val['order_detail'])){
                        $TotalFee = $val['order_detail']['sc_pvc'] + $val['order_detail']['sc_cod'] + $val['order_detail']['sc_pbh'] +
                                    $val['order_detail']['sc_remote'] + $val['order_detail']['sc_clearance'] +
                                    $val['order_detail']['sc_pvk'] - $val['order_detail']['sc_discount_pvc'] - $val['order_detail']['sc_discount_cod'];
                    }

                    if(isset($val['order_fulfillment'])){
                        $TotalFee += $val['order_fulfillment']['sc_plk'] + $val['order_fulfillment']['sc_pdg'] + $val['order_fulfillment']['sc_pxl'] -
                            $val['order_fulfillment']['sc_discount_plk'] - $val['order_fulfillment']['sc_discount_pdg'] - $val['order_fulfillment']['sc_discount_pxl'];
                    }

                    $dataExport = array(
                        $i++,
                        $val['time_pickup'] > 0 ? date("d/m/y H:m",$val['time_pickup']) : '',
                        $val['time_success'] > 0 ? date("d/m/y H:m",$val['time_success']) : '',
                        $val['tracking_code'],
                        isset($Service[(int)$val['service_id']]) ? $Service[(int)$val['service_id']]['name'] : 'DV',
                        isset($Status[(int)$val['status']]) ? $Status[(int)$val['status']] : 'Trạng thái',

                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['email'] : '',
                        isset($Country[(int)$val['from_country_id']]) ? $Country[(int)$val['from_country_id']] : '',
                        isset($City[(int)$val['from_city_id']]) ? $City[(int)$val['from_city_id']] : '',

                        isset($Country[(int)$val['to_country_id']]) ? $Country[(int)$val['to_country_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($City[$Address[(int)$val['to_address_id']]['city_id']])) ? $City[$Address[(int)$val['to_address_id']]['city_id']] : '',

                        $val['product_name'],
                        $val['total_amount'],
                        $val['total_weight'],

                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvc']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_cod']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pbh']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvk']) : '',
                        '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_remote']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_clearance']) : '',

                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_discount_pvc']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_discount_cod']) : '',

                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_plk']) : '',
                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_pxl']) : '',
                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_pdg']) : '',

                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_discount_plk']) : '',
                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_discount_pxl']) : '',
                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_discount_pdg']) : '',


                        number_format($TotalFee),//tongphi
                        isset($val['order_detail']) ? number_format($val['order_detail']['money_collect']) : ''
                    );
                    $sheet->appendRow($dataExport);
                }
            });

            // Chuyển hoàn
            $reader->sheet(1,function($sheet) use($ListReturn, $Courier, $Service, $City, $Address, $User, $Status)
            {
                $i = 1;
                foreach ($ListReturn as $val) {
                    $TotalFee = 0;
                    if(isset($val['order_detail'])){
                        $TotalFee = $val['order_detail']['sc_pvc'] + $val['order_detail']['sc_pvk'] + $val['order_detail']['sc_pch'] +
                                    $val['order_detail']['sc_remote'] + $val['order_detail']['sc_clearance'] -
                                    $val['order_detail']['sc_discount_pvc'];
                    }

                    if(isset($val['order_fulfillment'])){
                        $TotalFee += $val['order_fulfillment']['sc_plk'] + $val['order_fulfillment']['sc_pdg'] + $val['order_fulfillment']['sc_pxl'] -
                            $val['order_fulfillment']['sc_discount_plk'] - $val['order_fulfillment']['sc_discount_pdg'] - $val['order_fulfillment']['sc_discount_pxl'];
                    }

                    $dataExport = array(
                        $i++,
                        $val['time_pickup'] > 0 ? date("d/m/y H:m",$val['time_pickup']) : '',
                        $val['time_success'] > 0 ? date("d/m/y H:m",$val['time_success']) : '',
                        $val['tracking_code'],
                        isset($Service[(int)$val['service_id']]) ? $Service[(int)$val['service_id']]['name'] : 'DV',
                        isset($Status[(int)$val['status']]) ? $Status[(int)$val['status']] : 'Trạng thái',

                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['email'] : '',
                        isset($Country[(int)$val['from_country_id']]) ? $Country[(int)$val['from_country_id']] : '',
                        isset($City[(int)$val['from_city_id']]) ? $City[(int)$val['from_city_id']] : '',
                        isset($Country[(int)$val['to_country_id']]) ? $Country[(int)$val['to_country_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($City[$Address[(int)$val['to_address_id']]['city_id']])) ? $City[$Address[(int)$val['to_address_id']]['city_id']] : '',
                        $val['product_name'],
                        $val['total_amount'],
                        $val['total_weight'],

                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvc']) : '',
                        '',
                        '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvk']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pch']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_remote']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_clearance']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_discount_pvc']) : '',
                        '',

                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_plk']) : '',
                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_pxl']) : '',
                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_pdg']) : '',

                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_discount_plk']) : '',
                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_discount_pxl']) : '',
                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_discount_pdg']) : '',

                        number_format($TotalFee)
                    );
                    $sheet->appendRow($dataExport);
                }
            });

            /*
             * Đơn tồn
             */
            $reader->sheet(2,function($sheet) use($ListLast, $Courier, $Service, $City, $Address, $User, $Status)
            {
                $i = 1;
                foreach ($ListLast as $val) {
                    $TotalFee = 0;
                    if(isset($val['order_detail'])){
                        $TotalFee = $val['order_detail']['sc_pvc'] + $val['order_detail']['sc_remote'] + $val['order_detail']['sc_clearance'] +
                            $val['order_detail']['sc_pvk'] - $val['order_detail']['sc_discount_pvc'];

                        if(in_array($val['status'], [66, 67])){
                            if($val['status'] == 66){
                                $TotalFee   += $val['order_detail']['sc_pch'];
                            }
                        }else{
                            $TotalFee   += $val['order_detail']['sc_cod'] + $val['order_detail']['sc_pbh'] - $val['order_detail']['sc_discount_cod'];
                        }
                    }

                    if(isset($val['order_fulfillment'])){
                        $TotalFee += $val['order_fulfillment']['sc_plk'] + $val['order_fulfillment']['sc_pdg'] + $val['order_fulfillment']['sc_pxl'] -
                            $val['order_fulfillment']['sc_discount_plk'] - $val['order_fulfillment']['sc_discount_pdg'] - $val['order_fulfillment']['sc_discount_pxl'];
                    }

                    $dataExport = array(
                        $i++,
                        $val['time_pickup'] > 0 ? date("d/m/y H:m",$val['time_pickup']) : '',
                        $val['time_success'] > 0 ? date("d/m/y H:m",$val['time_success']) : '',
                        $val['tracking_code'],
                        isset($Service[(int)$val['service_id']]) ? $Service[(int)$val['service_id']]['name'] : 'DV',
                        isset($Status[(int)$val['status']]) ? $Status[(int)$val['status']] : 'Trạng thái',

                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['email'] : '',
                        isset($Country[(int)$val['from_country_id']]) ? $Country[(int)$val['from_country_id']] : '',
                        isset($City[(int)$val['from_city_id']]) ? $City[(int)$val['from_city_id']] : '',
                        isset($Country[(int)$val['to_country_id']]) ? $Country[(int)$val['to_country_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($City[$Address[(int)$val['to_address_id']]['city_id']])) ? $City[$Address[(int)$val['to_address_id']]['city_id']] : '',
                        $val['product_name'],
                        $val['total_amount'],
                        $val['total_weight'],

                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvc']) : '',
                        (isset($val['order_detail']) && !in_array($val['status'], [66, 67])) ? number_format($val['order_detail']['sc_cod']) : '',
                        (isset($val['order_detail']) && !in_array($val['status'], [66, 67])) ? number_format($val['order_detail']['sc_pbh']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvk']) : '',
                        (isset($val['order_detail']) && $val['status'] == 66) ? number_format($val['order_detail']['sc_pch']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_remote']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_clearance']) : '',

                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_discount_pvc']) : '',
                        (isset($val['order_detail']) && !in_array($val['status'], [66, 67])) ? number_format($val['order_detail']['sc_discount_cod']) : '',

                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_plk']) : '',
                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_pxl']) : '',
                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_pdg']) : '',

                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_discount_plk']) : '',
                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_discount_pxl']) : '',
                        isset($val['order_fulfillment']) ? number_format($val['order_fulfillment']['sc_discount_pdg']) : '',

                        number_format($TotalFee),
                        (isset($val['order_detail']) && !in_array($val['status'], [66, 67])) ? number_format($val['order_detail']['money_collect']) : ''
                    );
                    $sheet->appendRow($dataExport);
                }
            });

        },'UTF-8',true)->export('xls');

    }
}
