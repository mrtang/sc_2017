<?php
namespace oms;

use Response;
use Exception;
use Input;
use Validator;
use Excel;
use DB;
use LMongo;

use ordermodel\OrdersModel;
use sellermodel\UserInfoModel;
use ordermodel\AddressModel;
use ordermodel\StatusModel;
use sellermodel\UserInventoryModel;
use omsmodel\PipeJourneyModel;
use omsmodel\SellerModel;
use omsmodel\LogSellerModel;

use ticketmodel\ReferModel;
use User;
use WardModel;
use CourierStatusAcceptModel;

class OrderCtrl extends \BaseController
{
    private $error              = true;
    private $message            = 'error';
    private $total              = 0;
    private $total_all          = 0;
    private $total_group        = [];
    private $data               = [];
    private $list_district_id   = [];
    private $list_ward_id       = [];
    private $list_to_address    = [];
    private $list_from_address  = [];

    private $range_sale         = [
        0      => [
            'hard'        => 3000000,
            'commission'  => 0.03
        ],
        1      => [
          'hard'        => 3000000,
          'commission'  => 0.03
        ],
        2      => [
          'hard'        => 3500000,
          'commission'  => 0.05
        ],
        3      => [
            'hard'        => 4000000,
            'commission'  => 0.07
        ],
        4      => [
            'hard'        => 5000000,
            'commission'  => 0.09
        ],
        5       => [
            'hard'        => 6000000,
            'commission'  => 0.1
        ]
    ];

    private $range_lead     = [
        0      => [
            'hard'        => 5000000,
            'commission'  => 0.003
        ],
        1      => [
            'hard'        => 5000000,
            'commission'  => 0.003
        ],
        2      => [
            'hard'        => 5500000,
            'commission'  => 0.005
        ],
        3      => [
            'hard'        => 6000000,
            'commission'  => 0.007
        ],
        4      => [
            'hard'        => 6500000,
            'commission'  => 0.009
        ],
        5       => [
            'hard'        => 7000000,
            'commission'  => 0.012
        ]
    ];

    function __construct(){
        set_time_limit (180);
    }

    private function getModel(){
        $Model              = new OrdersModel;
        $PipeJourneyModel   = new PipeJourneyModel;
        $BaseCtrl           = new \BaseCtrl;

        $TimeCreateStart    = Input::has('create_start')        ? (int)Input::get('create_start')           : 0; // time_create start   time_stamp
        $TimeCreateEnd      = Input::has('create_end')          ? (int)Input::get('create_end')             : 0; // time_create end
        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')           : 0; // time_accept start
        $TimeAcceptEnd      = Input::has('accept_end')          ? (int)Input::get('accept_end')             : 0; // time_accept end
        $TimeSuccessStart   = Input::has('success_start')       ? (int)Input::get('success_start')          : 0; // time_accept start
        $TimeSuccessEnd     = Input::has('success_end')         ? (int)Input::get('success_end')            : 0; // time_accept end
        $PickupStart        = Input::has('pickup_start')        ? (int)Input::get('pickup_start')           : 0; // time_pickup start
        $PickupEnd          = Input::has('pickup_end')          ? (int)Input::get('pickup_end')             : 0; // time_pickup end


        $ServiceId          = Input::has('service')             ? (int)Input::get('service')                : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))           : '';
        $Domain             = Input::has('domain')              ? trim(Input::get('domain'))                 : 0;
        $KeyWord            = Input::has('keyword')             ? trim(Input::get('keyword'))               : 0;
        $Vip                = Input::has('vip')                 ? (int)Input::get('vip')                    : 0;
        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';

        $PipeStatus         = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))           : '';
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TypeProcess        = Input::has('type-process')        ? (int)Input::get('type-process')           : 1;

        $FromUser           = Input::has('from_user')           ? (int)Input::get('from_user')              : 0;
        $ToUser             = Input::has('to_user')             ? trim(Input::get('to_user'))               : '';

        $FromCity           = Input::has('from_city')           ? (int)Input::get('from_city')              : 0;
        $FromDistrict       = Input::has('from_district')       ? (int)Input::get('from_district')          : 0;

        $ToCity             = Input::has('to_city')             ? (int)Input::get('to_city')                : 0;
        $ToDistrict         = Input::has('to_district')           ? (int)Input::get('to_district')          : 0;

        $Tag                = Input::has('tag')                 ? strtolower(trim(Input::get('tag')))       : 0;
        /*
         *  Lấy chậm
         */
        $Slow               = Input::has('slow')                ? (int)Input::get('slow')                       : 0;

        /*
         * Giao chậm
         */
        $DeliverySlow       = Input::has('delivery_slow')       ? (int)Input::get('delivery_slow')              : null;
        $BonusDelivery      = 0; // thời gian cộng thêm theo khu vực

        /*
         * Cập nhật chậm
         */
        $LastUpdate         = Input::has('last_update')         ? (int)Input::get('last_update')                : 0;

        /*
         * Giá trị cao
         */
        $Amount             = Input::has('amount')              ? (int)Input::get('amount')                     : 0;

        /*
         * Giá trị cao
         */
        $Weight             = Input::has('weight')              ? (int)Input::get('weight')                     : 0;

        /*
         * Vượt cân
         */
        $OverWeight         = Input::has('over_weight')         ? (int)Input::get('over_weight')                : 0;

        /*
         * Khu vực
         */
        $Location           = Input::has('location')            ? (int)Input::get('location')                   : 0;

        if(!empty($Vip)){
            $UserInfoModel  = new UserInfoModel;
            $ListUser       = $UserInfoModel->getVip();

            // ko có dữ liệu , return []
            if(empty($ListUser)){
                $this->error = true;
                return;
            }
        }

        if(!empty($KeyWord)){
            $UserModel      = new User;

            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $UserModel          = $UserModel->where('email',$KeyWord);
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $UserModel          = $UserModel->where('phone',$KeyWord);
            }else{ // search code
                $UserModel          = $UserModel->where('fullname',$KeyWord);
            }

            $ListUserSearch = $UserModel->lists('id');
            if(empty($ListUserSearch)){
                $this->error = true;
                return;
            }else{
                if(!empty($ListUser)){
                    $ListUser   = array_intersect($ListUser, $ListUserSearch);
                }else{
                    $ListUser   = $ListUserSearch;
                }
            }

            if(empty($ListUser)){
                $this->error = true;
                return;
            }
        }elseif($FromUser > 0){
            $ListUser[] = $FromUser;
        }

        if(!empty($ListUser)){
            $Model  = $Model->whereRaw("from_user_id in (". implode(",", $ListUser) .")");
        }

        if(!empty($ToUser)){
            if (filter_var($ToUser, FILTER_VALIDATE_EMAIL)){  // search email
                $Model          = $Model->where('to_email',$ToUser);
                
            }elseif(filter_var((int)$ToUser, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $Model          = $Model->where('to_phone',$ToUser);
            }else{ // search code
                $Model          = $Model->where('to_name',$ToUser);
            }
        }

        $ListLocation   = [];
        if(!empty($Location)){
            if(empty($FromCity)){
                $this->error    = true;
                $this->message  = 'Bạn phải chọn Tỉnh/Thành gửi';
                return;
            }
            Input::merge(['city' => $FromCity]);
            $ListDistrictId = $BaseCtrl->getDistrict(false);

            if(empty($ListDistrictId)){
                $this->error    = true;
                return;
            }

            $ListId = [];
            foreach($ListDistrictId as $val){
                $ListId[]   = (int)$val['id'];
            }

            $CourierAreaModel   = new \CourierAreaModel;
            $AreaId   = $CourierAreaModel->get_area_id(7);
            if(empty($AreaId)){
                $this->error = true;
                return;
            }

            $AreaLocationModel  = new \AreaLocationModel;
            $AreaLocationModel  = $AreaLocationModel::whereIn('area_id', $AreaId);
            if($Location <= 3){
                /*  Nội thành: Quá 1 ngày kể từ khi lấy hàng
                    Ngoại thành: Quá 1 ngày kể từ khi lấy hàng
                    Huyện xã thuộc nội tỉnh: Quá 2 ngày kể từ khi lấy hàng
                */
                if($Location == 3){
                    $BonusDelivery = 2;
                }else{
                    $BonusDelivery = 1;
                }

                $AreaLocationModel  = $AreaLocationModel->whereIn('province_id', $ListId);
            }else{
                /*  Trung tâm: quá 72h kể từ khi lấy hàng
                    Huyện xã: Quá 96h kể từ khi lấy hàng
                */
                if(in_array($Location,[4,5])){
                    $BonusDelivery  = 3;
                }else{
                    $BonusDelivery  = 4;
                }

                $AreaLocationModel  = $AreaLocationModel->whereNotIn('province_id', $ListId);
            }

            if(in_array($Location, [1,4])){
                $AreaLocationModel  = $AreaLocationModel->where('location_id',1);
            }elseif(in_array($Location, [2,5])){
                $AreaLocationModel  = $AreaLocationModel->where('location_id',2);
            }else{
                $AreaLocationModel  = $AreaLocationModel->where('location_id','>=',3);
            }
            $ListLocation   = $AreaLocationModel->lists('province_id');
            if(empty($ListLocation)){
                $this->error = true;
                return;
            }
        }

        if(!empty($Domain)){
            $Model          = $Model->where('domain',$Domain);
        }

        if(!empty($ServiceId)){
            $Model          = $Model->where('service_id',$ServiceId);
        }

        if(!empty($FromDistrict)){
            $Model          = $Model->where('from_district_id',$FromDistrict);
        }elseif(!empty($FromCity)){
            $Model          = $Model->where('from_city_id',$FromCity);
        }

        if(!empty($ToDistrict)){
            if(!empty($ListLocation)){ // nếu có tìm theo location
                $ToDistrict = array_intersect($ToDistrict, $ListLocation);
            }
            if(empty($ToDistrict)){
                $this->error = true;
                return;
            }

            $Model          = $Model->where('to_district_id',$ToDistrict);
        }elseif(!empty($ToCity)){
            Input::merge(['city' => $ToCity]);
            $ListDistrictId = $BaseCtrl->getDistrict(false);
            if(!empty($ListDistrictId)){
                $ListId = [];
                foreach($ListDistrictId as $val){
                    $ListId[]   = (int)$val['id'];
                }

                if(!empty($ListLocation)){ // nếu có tìm theo location
                    $ListId = array_intersect($ListId, $ListLocation);
                }
                if(empty($ListId)){
                    $this->error = true;
                    return;
                }
                $Model          = $Model->whereRaw("to_district_id in (". implode(",", $ListId) .")");
            }else{
                $this->error = true;
                return;
            }
        }elseif(!empty($ListLocation)){
            $Model          = $Model->whereRaw("to_district_id in (". implode(",", $ListLocation) .")");
        }

        if(!empty($Tag)){
            $Model          = $Model->where('tag','LIKE', '%'.$Tag.'%');
        }

        if(!empty($TrackingCode)){
            $Model          = $Model->where(function($query) use($TrackingCode){
                $query->where('tracking_code',$TrackingCode)
                      ->orWhere('courier_tracking_code', $TrackingCode);
            });
        }

        if(!empty($ListStatus) && !$TrackingCode){
            $ListStatus = explode(',',$ListStatus);
            $Model          = $Model->whereIn('status',$ListStatus);
        }

        if(empty($TimeCreateStart) && empty($TimeAcceptStart)){
            $this->error = true;
            return;
        }

        if(!empty($TimeCreateEnd)){
            $Model              = $Model->where('time_create','<=',$TimeCreateEnd);
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','<=',$TimeCreateEnd);
        }

        if(!empty($TimeCreateStart)){
            if(empty($TimeCreateEnd)){
                $TimeCreateEnd  = $this->$this->time();
            }

            if(($TimeCreateEnd - $TimeCreateStart) > 93*86400){
                $this->error = true;
                return;
            }

            $Model              = $Model->where('time_create','>=',$TimeCreateStart);
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$TimeCreateStart);
        }

        if(!empty($TimeAcceptEnd)){
            $Model          = $Model->where('time_accept','<=',$TimeAcceptEnd);

            if(empty($TimeAcceptEnd)){
                $PipeJourneyModel   = $PipeJourneyModel->where('time_create','<=',$TimeCreateEnd + 86400*30);
            }
        }

        if(!empty($TimeAcceptStart)){
            if(empty($TimeAcceptEnd)){
                $TimeAcceptEnd  = $this->time();
            }

            if(($TimeAcceptEnd - $TimeAcceptStart) > 93*86400){
                $this->error = true;
                return;
            }

            $Model          = $Model->where('time_accept','>=',$TimeAcceptStart);

            if(empty($TimeCreateStart)){
                $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$TimeAcceptStart - 86400*30);
            }
        }

        if(empty($TimeAcceptStart) && empty($TimeCreateStart)){
            $Model              = $Model->where('time_accept','>=',$this->time() - 86400*30);
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$this->time() - 86400*30);
        }

        if(!empty($TimeSuccessStart)){
            $Model          = $Model->where('time_success','>=',$TimeSuccessStart);
        }

        if(!empty($TimeSuccessEnd)){
            $Model          = $Model->where('time_success','<=',$TimeSuccessEnd);
        }

        if(!empty($PickupStart)){
            $Model          = $Model->where('time_pickup','>=',$PickupStart);
        }

        if(!empty($PickupEnd)){
            $Model          = $Model->where('time_pickup','<=',$PickupEnd);
        }

        if(!empty($Slow)){ // lấy chậm
            $currentHour    = date("G", $this->time());

            if($currentHour < 8){
                $TimeSlow = strtotime(date('Y-m-d 14:00:00', strtotime(' -1 day')));
            }elseif($currentHour >= 8 && $currentHour < 14){
                $TimeSlow = strtotime(date('Y-m-d 14:00:00', strtotime(' -1 day')));
            }elseif($currentHour >= 14 && $currentHour < 18){
                $TimeSlow = strtotime(date('Y-m-d 18:00:00', strtotime(' -1 day')));
            }else{
                $TimeSlow = strtotime(date('Y-m-d 14:00:00'));
            }

            $TimeSlow   -= $Slow*3600;

            $Model          = $Model->where('time_approve','<=', $TimeSlow);
        }

        if(isset($DeliverySlow)){ // giao chậm
            $Model = $Model->where('estimate_delivery','>',0)->where('time_pickup','>',0)->whereRaw('(time_pickup + estimate_delivery*3600 + '.(($DeliverySlow + $BonusDelivery)*86400).') <= '.$this->time());

        }

        if(!empty($LastUpdate)){
            $Model          = $Model->where('time_update','<=',$this->time() - $LastUpdate*3600);
        }

        if(!empty($Amount)){
            $Model          = $Model->where('total_amount','>=',$Amount);
        }

        if(!empty($Weight)){
            $Model          = $Model->where('total_weight','>=',$Weight);
        }

        if(!empty($OverWeight)){
            $MaxOverWeight  = 0;
            $MinOverWeight  = 0;
            if($OverWeight == 5000){
                $MinOverWeight  =   $OverWeight;
            }else{
                $MaxOverWeight  = $OverWeight;
                if(in_array($OverWeight, [250,500])){
                    $MinOverWeight  = $OverWeight - 250;
                }else{
                    $MinOverWeight  = $OverWeight - 500;
                }
            }

            $Model          = $Model->where('over_weight','>',$MinOverWeight);
            if(!empty($MaxOverWeight)){
                $Model          = $Model->where('over_weight','<',$MaxOverWeight);
            }
        }

        if(!empty($PipeStatus) && !empty($Group)){
            $PipeStatus = explode(',',$PipeStatus);
            $ListId = [];
            $ListId = $PipeJourneyModel->where('type', $TypeProcess)->where('group_process',$Group)->whereIn('pipe_status', $PipeStatus)->lists('tracking_code');

            if(!empty($ListId)){
                $ListId = array_unique($ListId);
                $Model  = $Model->whereRaw("id in (". implode(",", $ListId) .")");
            }else{
                $this->error = true;
                return;
            }
        }

        return $Model;
    }

    /*
     * get  to_address
     */
    private function getToaddress($ListToAddress){
        $AddressModel   = new AddressModel;
        $ListAddress    = $AddressModel::whereIn('id',$ListToAddress)->get()->toArray();
        if(!empty($ListAddress)){
            foreach($ListAddress as $val){
                $this->list_to_address[(int)$val['id']]    = $val;
                if($val['province_id'] > 0){
                    $this->list_district_id[]   = (int)$val['province_id'];
                }

                if($val['province_id'] > 0){
                    $this->list_ward_id[]   = (int)$val['ward_id'];
                }
            }
        }
    }

    private function getFromaddress($ListFromAddress){
        $AddressModel   = new UserInventoryModel;
        $ListAddress    = $AddressModel::whereIn('id',$ListFromAddress)->get(['id','name','user_name','phone'])->toArray();
        if(!empty($ListAddress)){
            foreach($ListAddress as $val){
                $this->list_from_address[(int)$val['id']]    = $val;
            }
        }
    }

    /*
     * get list order
     */
    public function getIndex(){
        set_time_limit (180);
        $itemPage           = 20;
        $this->error        = false;
        $this->message      = 'success';

        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 'ALL';
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TypeProcess        = Input::has('type-process')        ? (int)Input::get('type-process')           : 1;

        $Model          = $this->getModel();

        if($this->error){
            $this->error    = false;
            if($Cmd == 'export'){
                return $this->ExportExcel([]);
            }
            return $this->ResponseData();
        }

        /**
         * get data
         */
        if($Cmd == 'export'){
            if($CourierId != 'ALL'){
                $Model          = $Model->where('courier_id',$CourierId);
            }
            return $this->ExportExcel($Model);
        }

        /*
         * count
         */

        if($CourierId != 'ALL'){
            $Model          = $Model->where('courier_id',$CourierId);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model       = $Model->skip($offset)->take($itemPage);

            if(!empty($Group)){
                $Model  = $Model->with(['pipe_journey' => function($query) use($Group,$TypeProcess){
                    $query->where('type', $TypeProcess)->where('group_process', $Group)->orderBy('time_create', 'ASC');
                }]);
            }else {
                $Model  = $Model->with(['pipe_journey' => function($query) use($TypeProcess){
                    $query->where('type', $TypeProcess)->orderBy('time_create', 'ASC');
                }]);
            }

            $Data       = $Model->with(['OrderDetail','FromUser'])->orderBy('time_create','DESC')->get()->toArray();
            if(!empty($Data)){
                $ListToAddress    = [];
                $ListFromAddress  = [];

                foreach($Data as $key => $val){
                    $Data[$key]['pipe_status'] = 0;

                    if($val['from_district_id'] > 0){
                        $this->list_district_id[]   = (int)$val['from_district_id'];
                    }
                    if($val['from_ward_id'] > 0){
                        $this->list_ward_id[]   = (int)$val['from_ward_id'];
                    }
                    if($val['to_address_id'] > 0){
                        $ListToAddress[]   = (int)$val['to_address_id'];
                    }
                    if($val['from_address_id'] > 0){
                        $ListFromAddress[]  = (int)$val['from_address_id'];
                    }

                    if(!empty($val['pipe_journey'])){
                        foreach($val['pipe_journey'] as $v){
                            $Data[$key]['pipe_status'] = (int)$v['pipe_status'];
                        }
                    }

                    if(!empty($val['tag'])){
                        $Data[$key]['list_tag']    = explode(',',$val['tag']);
                    }else{
                        $Data[$key]['list_tag']    = [];
                    }

                }

                if(!empty($ListToAddress)){
                    $ListToAddress = array_unique($ListToAddress);
                    $this->getToaddress($ListToAddress);
                }

                if(!empty($ListFromAddress)){
                    $ListFromAddress = array_unique($ListFromAddress);
                    $this->getFromaddress($ListFromAddress);
                }

                if(!empty($this->list_district_id)){
                    $this->list_district_id = array_unique($this->list_district_id);
                    $this->list_district_id = $this->getProvince($this->list_district_id);
                }

                if(!empty($this->list_ward_id)){
                    $this->list_ward_id = array_unique($this->list_ward_id);
                    $this->list_ward_id = $this->getWard($this->list_ward_id);
                }

                $this->data = $Data;
            }
        }

        return $this->ResponseData();
    }

    /**
     * Đơn hàng yêu cầu phát lại
     */
    public function getReportReplay(){
        $itemPage           = 20;
        $this->error        = false;
        $this->message      = 'success';

        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 'ALL';
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';

        $ReportStart        = Input::has('report_start')        ? (int)Input::get('report_start')           : 0;
        $ReportEnd          = Input::has('report_end')          ? (int)Input::get('report_end')             : 0;
        $ReportReplay       = Input::has('report_replay')       ? (int)Input::get('report_replay')          : 0;


        $Model              = $this->getModel();

        if($this->error){
            $this->error    = false;
            if($Cmd == 'export'){
                return $this->ReportExcel([]);
            }
            return $this->ResponseData();
        }

        /**
         * get data
         */

        /*
         * 707 - 903  yêu cầu phát lại
         * 708 - 904    đã báo hvc
        */

        $PipeJourneyModel   = new PipeJourneyModel;
        $PipeJourneyModel   = $PipeJourneyModel::where('type',1)
                                ->where(function($query){
                                    $query->where(function($q){
                                        $q->whereIn('pipe_status', [707,708])
                                            ->where('group_process', 29);
                                    })->orWhere(function($q){
                                        $q->whereIn('pipe_status', [903, 904])
                                            ->where('group_process', 31);
                                    });
                                });

        if(!empty($ReportStart)){
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$ReportStart);
        }else{
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$this->time() - $this->time_limit);
        }

        if(!empty($ReportEnd)){
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','<=',$ReportEnd);
        }

        $PipeJourney            = $PipeJourneyModel->orderBy('time_create','ASC')->get(['user_id','tracking_code','type','group_process','pipe_status','note','time_create'])->toArray();
        if(empty($PipeJourney)){
            if($Cmd == 'export'){
                return $this->ReportExcel([]);
            }
            $this->error    = false;
            return $this->ResponseData;
        }

        $ListPipe           = [];
        $ListOrderId        = [];
        $ListOrderReportId  = [];

        foreach($PipeJourney as $val){
            $ListOrderId[]                              = (int)$val['tracking_code'];

            if(in_array($val['pipe_status'], [708,904])){
                $ListOrderReportId[]                              = (int)$val['tracking_code'];
            }

            $ListPipe[(int)$val['tracking_code']][]     = $val;
        }
        $ListOrderId        = array_unique($ListOrderId);
        $ListOrderReportId  = array_unique($ListOrderReportId);

        if(!empty($ReportReplay)){
            if($ReportReplay == 1){// Chưa báo HVC
                $ListOrderId    = array_diff($ListOrderId,$ListOrderReportId);
            }else{ // Đã báo HVC
                $ListOrderId    = $ListOrderReportId;
            }
        }

        if($CourierId != 'ALL'){
            $Model          = $Model->where('courier_id',$CourierId);
        }

        if(empty($ListOrderId)){
            if($Cmd == 'export'){
                return $this->ReportExcel([]);
            }
            $this->error    = false;
            return $this->ResponseData;
        }

        $ListOrderId    = array_unique($ListOrderId);
        $Model  = $Model->whereRaw("id in (". implode(",", $ListOrderId) .")");

        if($Cmd == 'export'){
            return $this->ReportExcel($Model->get()->ToArray(), $ListPipe);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model       = $Model->skip($offset)->take($itemPage);

            $Data       = $Model->with(['OrderDetail','FromUser'])->orderBy('time_create','DESC')->get()->toArray();
            if(!empty($Data)){
                $ListToAddress    = [];
                $ListFromAddress  = [];

                foreach($Data as $key => $val){
                    $Data[$key]['pipe_status'] = 0;

                    if($val['from_district_id'] > 0){
                        $this->list_district_id[]   = (int)$val['from_district_id'];
                    }
                    if($val['from_ward_id'] > 0){
                        $this->list_ward_id[]   = (int)$val['from_ward_id'];
                    }
                    if($val['to_address_id'] > 0){
                        $ListToAddress[]   = (int)$val['to_address_id'];
                    }
                    if($val['from_address_id'] > 0){
                        $ListFromAddress[]  = (int)$val['from_address_id'];
                    }

                    if(isset($ListPipe[(int)$val['id']])){
                        $Data[$key]['pipe_journey'] = $ListPipe[(int)$val['id']];
                    }

                    if(!empty($val['tag'])){
                        $Data[$key]['list_tag']    = explode(',',$val['tag']);
                    }else{
                        $Data[$key]['list_tag']    = [];
                    }

                }

                if(!empty($ListToAddress)){
                    $ListToAddress = array_unique($ListToAddress);
                    $this->getToaddress($ListToAddress);
                }

                if(!empty($ListFromAddress)){
                    $ListFromAddress = array_unique($ListFromAddress);
                    $this->getFromaddress($ListFromAddress);
                }

                if(!empty($this->list_district_id)){
                    $this->list_district_id = array_unique($this->list_district_id);
                    $this->list_district_id = $this->getProvince($this->list_district_id);
                }

                if(!empty($this->list_ward_id)){
                    $this->list_ward_id = array_unique($this->list_ward_id);
                    $this->list_ward_id = $this->getWard($this->list_ward_id);
                }

                $this->data = $Data;
            }
        }
        return $this->ResponseData();
    }

    public function getCountReportReplay(){
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 0;
        $ReportStart        = Input::has('report_start')        ? (int)Input::get('report_start')           : 0;
        $ReportEnd          = Input::has('report_end')          ? (int)Input::get('report_end')             : 0;
        $ReportReplay       = Input::has('report_replay')       ? (int)Input::get('report_replay')          : 0;

        $this->error        = false;
        $Model              = $this->getModel();
        if(!$this->error){
            if(!empty($CourierId)){
                $Model          = $Model->where('courier_id',$CourierId);
            }

            $PipeJourneyModel   = new PipeJourneyModel;
            $PipeJourneyModel   = $PipeJourneyModel::where('type',1)
                ->where(function($query){
                    $query->where(function($q){
                        $q->whereIn('pipe_status', [707,708])
                            ->where('group_process', 29);
                    })->orWhere(function($q){
                        $q->whereIn('pipe_status', [903, 904])
                            ->where('group_process', 31);
                    });
                });

            if(!empty($ReportStart)){
                $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$ReportStart);
            }else{
                $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$this->time() - $this->time_limit);
            }

            if(!empty($ReportEnd)){
                $PipeJourneyModel   = $PipeJourneyModel->where('time_create','<=',$ReportEnd);
            }

            $PipeJourney            = $PipeJourneyModel->get(['user_id','tracking_code','type','group_process','pipe_status','note','time_create'])->toArray();
            if(empty($PipeJourney)){
                $this->error    = false;
                return $this->ResponseData;
            }

            $ListPipe           = [];
            $ListOrderId        = [];
            $ListOrderReportId  = [];

            foreach($PipeJourney as $val){
                $ListOrderId[]                              = (int)$val['tracking_code'];

                if(in_array($val['pipe_status'], [708,904])){
                    $ListOrderReportId[]                              = (int)$val['tracking_code'];
                }

                $ListPipe[(int)$val['tracking_code']][]     = $val;
            }
            $ListOrderId        = array_unique($ListOrderId);
            $ListOrderReportId  = array_unique($ListOrderReportId);

            if(!empty($ReportReplay)){
                if($ReportReplay == 1){// Chưa báo HVC
                    $ListOrderId    = array_diff($ListOrderId,$ListOrderReportId);
                }else{ // Đã báo HVC
                    $ListOrderId    = $ListOrderReportId;
                }
            }

            if(empty($ListOrderId)){
                $this->error    = false;
                return $this->ResponseData;
            }

            $Model  = $Model->whereRaw("id in (". implode(",", $ListOrderId) .")");

            $GroupStatus    = $Model->groupBy('status')->get(array('status',DB::raw('count(*) as count')))->toArray();

            if(!empty($GroupStatus)){
                foreach($GroupStatus as $val){
                    if(!isset($this->total_group[(int)$val['status']])){
                        $this->total_group[(int)$val['status']] = 0;
                    }
                    $this->total_group[(int)$val['status']] += $val['count'];
                    $this->total_all                            += $val['count'];
                }
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'total'         => $this->total_all,
            'data'          => $this->total_group
        ]);
    }

    public function ReportExcel($Data, $ListPipe = []){
        $FileName   = 'Danh_sach_yeu_cau_phat_lai';
        $StatusReport     = [
                        707 => 'Phát thất bại - YCPL',
                        708 => 'Phát thất bại - Đã báo HVC',
                        903 => 'Chờ XNCH - YCPL',
                        904 => 'Chờ XNCH - Đã báo HVC'
                    ];

        $Courier    = [];
        $Service    = [];
        $City       = [];
        $Address    = [];
        $District   = [];
        $Ward       = [];
        $User       = [];
        $Status     = [];
        $ListUserId = [];

        if(!empty($Data)){
            $Courier    = $this->getCourier();
            $Service    = $this->getService();
            $City       = $this->getCity();
            $Status     = $this->getStatus();

            foreach($Data as $val){
                $ListDistrictId[]   = $val['from_district_id'];
                $ListWardId[]       = $val['from_ward_id'];
                $ListToAddress[]    = $val['to_address_id'];
                $ListUserId[]       = $val['from_user_id'];
            }

            if(isset($ListToAddress) && !empty($ListToAddress)){
                $AddressModel   = new AddressModel;
                $ListAddress    = $AddressModel::whereIn('id', $ListToAddress)->get()->toArray();
            }

            if(isset($ListAddress) && !empty($ListAddress)){
                foreach($ListAddress as $val){
                    if(!empty($val)){
                        $Address[$val['id']]    = $val;
                        $ListDistrictId[]       = (int)$val['province_id'];
                        $ListWardId[]           = (int)$val['ward_id'];
                    }
                }
            }

            $ListDistrictId = array_unique($ListDistrictId);
            $ListWardId     = array_unique($ListWardId);
            $ListUserId     = array_unique($ListUserId);

            if(!empty($ListDistrictId)){
                $District   = $this->getProvince($ListDistrictId);
            }

            if(!empty($ListUserId)){
                $UserModel = new \User;
                $User       = [];
                $ListUser   = [];

                $ListUser   = $UserModel->whereRaw("id in (". implode(",", $ListUserId) .")")->with('user_info')->get(['id','fullname', 'phone', 'email', 'time_create'])->toArray();
                if(!empty($ListUser)){
                    foreach($ListUser as $val){
                        $User[$val['id']]   = $val;
                    }
                }
            }

            if(!empty($ListWardId)){
                $WardModel = new WardModel;
                $ListWard  =  $WardModel::whereIn('id',$ListWardId)->get(['id','ward_name'])->toArray();
                if(!empty($ListWard)){
                    foreach($ListWard as $val){
                        if(!empty($Ward[$val['id']])){
                            $Ward[$val['id']]   = $val['ward_name'];
                        }

                    }
                }
            }
        }

        return Excel::create($FileName, function($excel) use($Data, $StatusReport, $ListPipe,$Courier,$Service,$City,$Address,$District,$Ward,$User,$Status){
            $excel->sheet('Sheet1', function($sheet) use($Data, $StatusReport, $ListPipe,$Courier,$Service,$City,$Address,$District,$Ward,$User,$Status){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách vận đơn'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,
                    'H'     =>  30, 'I'     =>  30, 'J'     =>  30, 'K'     =>  50, 'L'     =>  30, 'M'     =>  30, 'N'     =>  30,
                    'O'     =>  30, 'P'     =>  30, 'Q'     =>  30, 'R'     =>  30, 'S'     =>  30, 'T'     =>  30, 'U'     =>  30,
                    'V'     =>  30, 'W'     =>  30, 'X'     =>  30, 'Y'     =>  30, 'Z'     =>  80
                ));
                $sheet->setMergeColumn(array(
                    'columns' => array('A','B','C','D','E','F','G','H','I','J','K','Z'),
                    'rows' => array(
                        array(3,4)
                    )
                ));
                $sheet->mergeCells('L3:R3');
                $sheet->mergeCells('S3:Y3');

                $sheet->row(3, array(
                    'STT', 'Thời gian duyệt', 'Thời gian lấy hàng', 'Thời gian tạo yêu cầu', 'Mã vận đơn', 'Mã đơn hàng của KH', 'Hãng vận chuyển', 'Mã hãng vận chuyển',
                    'Trạng thái yêu cầu', 'Dịch vụ', 'Trạng thái', 'Nơi gửi','','','','','', '', 'Nơi nhận', '', '', '', '', '', '', 'Nội dung'
                ));

                $sheet->row(4, array(
                    '', '', '', '', '', '', '', '', '', '','','Họ tên', 'Email', 'Số điện thoại',  'Tỉnh/Thành phố',
                    'Quận/Huyện', 'Phường xã', 'Địa chỉ','Họ tên', 'Email', 'Số điện thoại',    'Tỉnh/Thành phố', 'Quận/Huyện', 'Phường xã','Địa chỉ',''
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });
                $sheet->row(4,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });
                $sheet->setBorder('A3:Z4', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    if(isset($ListPipe[(int)$val['id']])){
                        foreach($ListPipe[(int)$val['id']] as $v){
                            $dataExport = array(
                                $i++,
                                $val['time_accept'] > 0 ? date("d/m/y H:m",$val['time_accept']) : '',
                                $val['time_pickup'] > 0 ? date("d/m/y H:m",$val['time_pickup']) : '',
                                $v['time_create'] > 0 ? date("d/m/y H:m",$v['time_create']) : '',
                                $val['tracking_code'],
                                (isset($val['order_code'])) ?  ' '.$val['order_code'] :"",
                                isset($Courier[(int)$val['courier_id']]) ? $Courier[(int)$val['courier_id']] : 'HVC',
                                $val['courier_tracking_code'],
                                isset($StatusReport[$v['pipe_status']]) ? $StatusReport[$v['pipe_status']] : 'Trạng thái',
                                isset($Service[(int)$val['service_id']]) ? $Service[(int)$val['service_id']] : 'DV',
                                isset($Status[(int)$val['status']]) ? $Status[(int)$val['status']] : 'Trạng thái',
                                isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['fullname'] : '',
                                isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['email'] : '',
                                isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['phone'] : '',
                                isset($City[(int)$val['from_city_id']]) ? $City[(int)$val['from_city_id']] : '',
                                isset($District[(int)$val['from_district_id']]) ? $District[(int)$val['from_district_id']] : '',
                                isset($Ward[(int)$val['from_ward_id']]) ? $Ward[(int)$val['from_ward_id']] : '',
                                $val['from_address'],

                                $val['to_name'],
                                $val['to_email'],
                                $val['to_phone'],
                                (isset($Address[(int)$val['to_address_id']]) && isset($City[$Address[(int)$val['to_address_id']]['city_id']])) ? $City[$Address[(int)$val['to_address_id']]['city_id']] : '',
                                (isset($Address[(int)$val['to_address_id']]) && isset($District[$Address[(int)$val['to_address_id']]['province_id']])) ? $District[$Address[(int)$val['to_address_id']]['province_id']] : '',
                                (isset($Address[(int)$val['to_address_id']]) && isset($Ward[$Address[(int)$val['to_address_id']]['ward_id']])) ? $Ward[$Address[(int)$val['to_address_id']]['ward_id']] : '',
                                isset($Address[(int)$val['to_address_id']]) ? $Address[(int)$val['to_address_id']]['address'] : '',
                                $v['note']
                            );
                            $sheet->appendRow($dataExport);
                        }
                    }

                }
            });
        })->export('xls');
    }

    public function getCountgroup(){
        $this->error        = false;
        $Model              = $this->getModel();
        if(!$this->error){
            $GroupStatus    = $Model->groupBy('courier_id')->get(array('courier_id',DB::raw('count(*) as count')))->toArray();

            if(!empty($GroupStatus)){
                foreach($GroupStatus as $val){
                    if(!isset($this->total_group[(int)$val['courier_id']])){
                        $this->total_group[(int)$val['courier_id']] = 0;
                    }
                    $this->total_group[(int)$val['courier_id']] += $val['count'];
                    $this->total_all                            += $val['count'];
                }
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'total'         => $this->total_all,
            'data'          => $this->total_group
        ]);
    }

    public function getCountgroupstatus(){
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 0;

        $this->error        = false;
        $Model              = $this->getModel();
        if(!$this->error){
            if(!empty($CourierId)){
                $Model          = $Model->where('courier_id',$CourierId);
            }

            $GroupStatus    = $Model->groupBy('status')->get(array('status',DB::raw('count(*) as count')))->toArray();

            if(!empty($GroupStatus)){
                foreach($GroupStatus as $val){
                    if(!isset($this->total_group[(int)$val['status']])){
                        $this->total_group[(int)$val['status']] = 0;
                    }
                    $this->total_group[(int)$val['status']] += $val['count'];
                    $this->total_all                            += $val['count'];
                }
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'total'         => $this->total_all,
            'data'          => $this->total_group
        ]);
    }

    private function ResponseData(){

        return Response::json([
            'error'         => $this->error,
            'message'       => $this->message,
            'total'         => $this->total,
            'data'          => $this->data,
            'list_district' => $this->list_district_id,
            'list_ward'     => $this->list_ward_id,
            'list_to_address'   => $this->list_to_address,
            'list_from_address' => $this->list_from_address
        ]);
    }

    public function ExportExcel($Model){
        $PipeStatus         = Input::has('pipe_status')         ? trim(Input::get('pipe_status'))           : '';

        $FileName   = 'Danh_sach_van_đon';

        $Data               = [];

        if(!empty($Model)){
            $Address    = [];
            $District   = [];
            $Ward       = [];
            $User       = [];
            $ListUserId = [];
            $ListOrderStatus    = []; //Danh sách đơn hàng   khách hàng yêu cầu phát lại 67, Chờ lấy hàng lần 2 38
            $ListAcceptReturn   = []; // Danh sách đơn hàng đã xác nhận chuyển hoàn
            $StatusProcess      = [];
            $OrderId            = [];
            $TimeAcceptReturn   = [];

            $Courier    = $this->getCourier();
            $Service    = $this->getService();
            $City       = $this->getCity();
            $Status     = $this->getStatus();

            $Model->with('OrderDetail')->chunk('1000', function($query) use(&$Data, &$ListDistrictId, &$ListWardId, &$ListToAddress, &$ListUserId, &$ListOrderStatus, &$PipeStatus, &$OrderId, &$ListAcceptReturn){
                foreach($query as $val){
                    $Data[]             = $val->toArray();
                    $ListDistrictId[]   = $val['from_district_id'];
                    $ListWardId[]       = $val['from_ward_id'];
                    $ListToAddress[]    = $val['to_address_id'];
                    $ListUserId[]       = $val['from_user_id'];

                    if(in_array($val['status'], [38,67])){
                        $ListOrderStatus[]  =  (int)$val['id'];
                    }

                    if($PipeStatus == 707 || $PipeStatus == 903){
                        $OrderId[]  = (int)$val['id'];
                    }

                    if(in_array($val['status'], [61,62,63,64,65])){
                        $ListAcceptReturn[]  =  (int)$val['id'];
                    }
                }
            });

            if(!empty($Data)){
                if(isset($ListToAddress) && !empty($ListToAddress)){
                    $AddressModel   = new AddressModel;
                    $ListAddress    = $AddressModel::whereRaw("id in (". implode(",", $ListToAddress) .")")->get()->toArray();
                }

                if(isset($ListAddress) && !empty($ListAddress)){
                    foreach($ListAddress as $val){
                        if(!empty($val)){
                            $Address[$val['id']]    = $val;
                            $ListDistrictId[]       = (int)$val['province_id'];
                            $ListWardId[]           = (int)$val['ward_id'];
                        }
                    }
                }

                $ListDistrictId = array_unique($ListDistrictId);
                $ListWardId     = array_unique($ListWardId);
                $ListUserId     = array_unique($ListUserId);

                if(!empty($ListDistrictId)){
                    $District   = $this->getProvince($ListDistrictId);
                }

                if(!empty($ListUserId)){
                    $UserModel = new \User;
                    $User       = [];

                    $ListUser   = $UserModel->whereRaw("id in (". implode(",", $ListUserId) .")")->get(['id','fullname', 'phone', 'email', 'time_create'])->toArray();
                    if(!empty($ListUser)){
                        foreach($ListUser as $val){
                            $User[$val['id']]   = $val;
                        }
                    }
                }

                if(!empty($ListWardId)){
                    $WardModel = new WardModel;
                    $ListWard  =  $WardModel::whereRaw("id in (". implode(",", $ListWardId) .")")->get(['id','ward_name'])->toArray();
                    if(!empty($ListWard)){
                        foreach($ListWard as $val){
                            if(!empty($Ward[$val['id']])){
                                $Ward[$val['id']]   = $val['ward_name'];
                            }

                        }
                    }
                }

                if(!empty($ListOrderStatus) && empty($OrderId)){
                    $StatusModel        = new StatusModel;
                    $ListOrderStatus    = $StatusModel::whereRaw("order_id in (". implode(",", $ListOrderStatus) .")")->whereIn('status',[38,67])->orderBy('time_create','ASC')->get()->toArray();
                    if(!empty($ListOrderStatus)){
                        foreach($ListOrderStatus as $val){
                            if(!isset($StatusProcess[(int)$val['order_id']])){
                                $StatusProcess[(int)$val['order_id']]  = '';
                            }
                            $StatusProcess[(int)$val['order_id']]   .= $val['note'].',';

                        }
                    }
                }

                //get list request
                if(!empty($OrderId)){
                    $PipeJourneyModel   = new PipeJourneyModel;
                    $ListOrderStatus    = $PipeJourneyModel::whereRaw("tracking_code in (". implode(",", $OrderId) .")")
                        ->where('type',1)
                        ->whereIn('pipe_status',[707, 903])
                        ->orderBy('time_create','ASC')->get()->toArray();
                    if(!empty($ListOrderStatus)){
                        foreach($ListOrderStatus as $val){
                            if(!isset($StatusProcess[(int)$val['tracking_code']])){
                                $StatusProcess[(int)$val['tracking_code']]  = '';
                            }
                            $StatusProcess[(int)$val['tracking_code']]   .= $val['note'].',';

                        }
                    }
                }

                //get list order status  accept return
                if(!empty($ListAcceptReturn)){
                    $StatusModel        = new StatusModel;
                    $ListOrderStatus    = $StatusModel::whereRaw("order_id in (". implode(",", $ListAcceptReturn) .")")->where('status',61)->orderBy('time_create','ASC')->get()->toArray();
                    if(!empty($ListOrderStatus)){
                        foreach($ListOrderStatus as $val){
                            $TimeAcceptReturn[(int)$val['order_id']]   = $val['time_create'];
                        }
                    }
                }

                //get note process;
            }
        }

        Excel::selectSheetsByIndex(0)->load('/data/www/html/storage/template_export/danh_sach_van_don.xls', function($reader) use($Data,$Courier, $Service, $City, $Address, $District, $Ward, $User, $Status, $StatusProcess, $TimeAcceptReturn) {
            $reader->sheet(0,function($sheet) use($Data,$Courier, $Service, $City, $Address, $District, $Ward, $User, $Status, $StatusProcess, $TimeAcceptReturn)
            {
                $i = 1;
                foreach ($Data as $val) {
                    $Payment    = (isset($User[(int)$val['from_user_id']]) && (isset($User[(int)$val['from_user_id']]['info']))) ? $User[(int)$val['from_user_id']]['info']['priority_payment'] : 2;
                    $dataExport = array(
                        $i++,
                        $val['time_accept'] > 0 ? date("d/m/y H:m",$val['time_accept']) : '',
                        $val['time_pickup'] > 0 ? date("d/m/y H:m",$val['time_pickup']) : '',
                        $val['time_success'] > 0 ? date("d/m/y H:m",$val['time_success']) : '',
                        isset($val['verify_id']) ? $val['verify_id'] : '',
                        (isset($val['tracking_code'])) ? $val['tracking_code'] : '',
                        (isset($val['order_code'])) ?  ' '.$val['order_code'] :"",
                        isset($Courier[(int)$val['courier_id']]) ? $Courier[(int)$val['courier_id']] : 'HVC',
                        $val['courier_tracking_code'],
                        isset($Service[(int)$val['service_id']]) ? $Service[(int)$val['service_id']] : 'DV',
                        isset($Status[(int)$val['status']]) ? $Status[(int)$val['status']] : 'Trạng thái',

                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['fullname'] : '',
                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['email'] : '',
                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['phone'] : '',
                        isset($City[(int)$val['from_city_id']]) ? $City[(int)$val['from_city_id']] : '',
                        isset($District[(int)$val['from_district_id']]) ? $District[(int)$val['from_district_id']] : '',
                        isset($Ward[(int)$val['from_ward_id']]) ? $Ward[(int)$val['from_ward_id']] : '',
                        $val['from_address'],

                        $val['to_name'],
                        $val['to_email'],
                        $val['to_phone'],
                        (isset($Address[(int)$val['to_address_id']]) && isset($City[$Address[(int)$val['to_address_id']]['city_id']])) ? $City[$Address[(int)$val['to_address_id']]['city_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($District[$Address[(int)$val['to_address_id']]['province_id']])) ? $District[$Address[(int)$val['to_address_id']]['province_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($Ward[$Address[(int)$val['to_address_id']]['ward_id']])) ? $Ward[$Address[(int)$val['to_address_id']]['ward_id']] : '',
                        isset($Address[(int)$val['to_address_id']]) ? $Address[(int)$val['to_address_id']]['address'] : '',

                        $val['product_name'],
                        isset($val['total_amount']) ? $val['total_amount'] : '',
                        isset($val['total_weight']) ? $val['total_weight'] : '',

                        number_format($val['order_detail']['sc_pvc']),
                        ($val['status'] != 66) ? number_format($val['order_detail']['sc_cod']) : '',
                        ($val['status'] != 66) ? number_format($val['order_detail']['sc_pbh']) : '',
                        number_format($val['order_detail']['sc_pvk']),
                        number_format($val['order_detail']['sc_pch']),
                        number_format(($val['order_detail']['sc_discount_pvc'] + (($val['status'] != 66) ? $val['order_detail']['sc_discount_cod'] : 0))),
                        ($val['status'] != 66) ? number_format($val['order_detail']['money_collect']) : '',
                        ($Payment == 1 ) ? 'Vimo' : 'Ngân Lượng',
                        isset($StatusProcess[(int)$val['id']]) ? $StatusProcess[(int)$val['id']] : '',
                        isset($User[(int)$val['from_user_id']]) ? date("d/m/y H:m",$User[(int)$val['from_user_id']]['time_create']) : '',
                        isset($TimeAcceptReturn[(int)$val['id']]) ? date("d/m/y H:m",$TimeAcceptReturn[(int)$val['id']]) : ''
                    );

                    $sheet->appendRow($dataExport);
                }
            });
        },'UTF-8',true)->export('xls');
    }

    public function getOrderstatus($OrderId){
        $Status          = Input::has('status')             ? (int)Input::get('status')                : 0;

        $OrderProcessModel  = new StatusModel;
        $Data               = [];

        $OrderProcessModel  = $OrderProcessModel::where('order_id', (int)$OrderId);
        if($Status > 0){
            $OrderProcessModel  = $OrderProcessModel->where('status', $Status);
        }

        if($OrderId > 0){
            $Data = $OrderProcessModel->orderBy('time_create','DESC')->get()->toArray();
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        ]);

    }

    public function getStatusaccept($group){
        $CourierStatusAcceptModel   = new CourierStatusAcceptModel;
        $ListStatus                 = [];
        $Data   = $CourierStatusAcceptModel::where('status_id',$group)->where('active',1)->orderBy('status_accept_id','ASC')->get()->toArray();
        if(!empty($Data)){
            foreach($Data as $val){
                $ListStatus[]   = (int)$val['status_accept_id'];
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'data'          => $ListStatus
        ]);
    }

    public function getReferticket(){
        $TrackingCode   = Input::has('tracking_code')   ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $Refer          = [];

        if(!empty($TrackingCode)){
            $ReferModel = new ReferModel;
            $Data       = $ReferModel::where('type',1)->where('code',$TrackingCode)->orderBy('id','DESC')->get()->toArray();
            if(!empty($Data)){
                foreach($Data as $val){
                    $Refer[]   = (int)$val['ticket_id'];
                }
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'data'          => $Refer
        ]);
    }

    public function getPostman(){
        $PostmanId = Input::has('postman')   ? strtoupper(trim(Input::get('postman'))) : '';
        $Data      = [];

        if(!empty($PostmanId)){ 
            $PostManModel = new \PostManModel;
            $Data       = $PostManModel::where('postman_id',$PostmanId)->first();
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        ]);
    }

    public function postChangetag(){
        $OrderId            = Input::has('order_id')            ? (int)Input::get('order_id')               : 0;
        $Tag                = Input::has('tag')                 ? strtolower(trim(Input::get('tag')))   : '';
        $OrdersModel        = new OrdersModel;
        $UserInfo           = $this->UserInfo();

        if(!empty($OrderId)){
            try{
                $OrdersModel::where('time_create','>=',$this->time() - $this->time_limit)->where('id',$OrderId)->update(['tag' => $Tag]);
            }catch (Exception $e){
                return Response::json([
                    'error'         => true,
                    'message'       => 'UPDATE_FAIL',
                    'data'          => ''
                ]);
            }

            $this->data_log = [
                'order_id'      => $OrderId,
                'time_create'   => $this->time(),
                'user_id'       => $UserInfo['id'],
                'tag'           => [
                    'type'  => 'tag',
                    'new'   => $Tag
                ]
            ];
            $this->insertLog();
            return Response::json([
                'error'         => false,
                'message'       => 'SUCCESS',
                'data'          => ''
            ]);
        }else{
            return Response::json([
                'error'         => true,
                'message'       => 'EMPTY',
                'data'          => ''
            ]);
        }
    }

    private function insertLog(){
        \LMongo::collection('log_change_order')->insert($this->data_log);
        try{

        }catch(Exception $e){
            return ['error' => true, 'message'  => 'INSERT_LOG_FAIL', 'data' => $e->getMessage()];
        }
        return ['error' => false];
    }


    /*
     * Thống kê
     */
    public function getStatistic() {
        $UserInfo       = $this->UserInfo();
        $ListStatus     = Input::has('list_status')     ? trim(Input::get('list_status'))   : '';
        $TimeNow        = strtotime(date("Y-m-d"));

        $Model          = new OrdersModel;
        $Data           = [
            'group'     => [],
            'day'       => [],
            'problem'   => [],
            'courier'   => []
        ];

        if(!empty($ListStatus)){
            $ListStatus     = explode(',',$ListStatus);
            $Model          = $Model->whereIn('status',$ListStatus);
        }elseif($UserInfo['privilege'] != 2){
            return Response::json([
                'error'         => false,
                'message'       => 'success',
                'data'          => $Data
            ]);
        }

        // thống kê tất cả đơn
        $ModelA         = clone $Model;
        $DataGroup      = $Model->where('time_create','>=', strtotime(date('Y-m-1 00:00:00')))
            ->groupBy('status')
            ->get(array('status',DB::raw('count(*) as count')))->toArray();
        if(!empty($DataGroup)){
            $Group  = [];
            foreach($DataGroup as $val){
                $Group[$val['status']]  = (int)$val['count'];
            }
            $Data['group']  = $Group;
        }

        // thống kê đơn trong ngày
        $DataGroup      = $ModelA->where('time_create','>=',$TimeNow - $this->time_limit)->where('time_update','>=', $TimeNow)
            ->groupBy('status')
            ->get(array('status',DB::raw('count(*) as count')))->toArray();
        if(!empty($DataGroup)){
            $Group  = [];
            foreach($DataGroup as $val){
                $Group[$val['status']]  = (int)$val['count'];
            }
            $Data['day']  = $Group;
        }

        // thống kê lỗi lấy chậm hoặc giao chậm
        $Model          = new OrdersModel;
        if($UserInfo['group']   = 1){ // lấy hàng


        }elseif($UserInfo['group']   = 2){ // giao hàng

        }


        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        ]);
    }

    // Sale
    public function getStatisticSale(){
        $Model          = new SellerModel;
        $ModelLastMonth = new SellerModel;

        $UserInfo       = $this->UserInfo();

        if(in_array(date('j'), [1,2])){
            $TimeStart      = strtotime(date('Y-m-1 00:00:00', strtotime("-1 month")));
            $TimePreMonth   = strtotime(date('Y-m-1 00:00:00', strtotime("-2 month")));
        }else{
            $TimeStart      = strtotime(date('Y-m-1 00:00:00'));
            $TimePreMonth   = strtotime(date('Y-m-1 00:00:00', strtotime("-1 month")));
        }

        $Data           = [
            'total_firstmonth'  => 0,
            'total_nextmonth'   => 0
        ];

        $ListUserId     = [];
        $SumTotal       = 0;
        $First          = [];
        $Pre            = [];

        if($UserInfo['group']   == 10){ // Trưởng nhóm sale
            // Doanh thu đầu tháng
            $DataFirst        = $Model::where('first_time_pickup', '>=', $TimeStart)
                                    ->where('seller_id','>',0)
                                    ->groupBy('seller_id')
                ->get(['seller_id',DB::raw('sum(total_firstmonth) as total_firstmonth')])->toArray();

            if(!empty($DataFirst)){
                foreach($DataFirst as $val){
                    $SumTotal                       += $val['total_firstmonth'];
                    $ListUserId[]                   = (int)$val['seller_id'];
                    $First[(int)$val['seller_id']]  = $val['total_firstmonth'];
                }
            }

            // Doanh thu lũy kế khác hàng đang sử dụng
            $DataPre     = $ModelLastMonth::where('first_time_pickup','>=',$TimePreMonth)
                                          ->where('first_time_pickup','<',$TimeStart)
                                          ->where('seller_id','>',0)
                                          ->groupBy('seller_id')
                                          ->get(['seller_id',DB::raw('sum(total_nextmonth) as total_nextmonth')])->toArray();
            if(!empty($DataPre)){
                foreach($DataPre as $val){
                    $SumTotal                      += $val['total_nextmonth'];
                    $ListUserId[]                   = (int)$val['seller_id'];
                    $Pre[(int)$val['seller_id']]    = $val['total_nextmonth'];
                }
            }

            // Doanh thu lũy kế khác hàng ngừng sử dụng
            $LogSellerModel = new LogSellerModel;
            $DataPreStop     = $LogSellerModel::where('first_time_pickup','>=',$TimePreMonth)
                                             ->where('first_time_pickup','<',$TimeStart)
                                             ->where('seller_id','>',0)
                                             ->groupBy('seller_id')
                                             ->get(['seller_id',DB::raw('sum(total_nextmonth) as total_nextmonth')])->toArray();
            if(!empty($DataPreStop)){
                foreach($DataPreStop as $val){
                    if(!isset($Pre[(int)$val['seller_id']])){
                        $Pre[(int)$val['seller_id']]  = 0;
                    }

                    $SumTotal                      += $val['total_nextmonth'];
                    $ListUserId[]                   = (int)$val['seller_id'];
                    $Pre[(int)$val['seller_id']]   += $val['total_nextmonth'];
                }
            }


            $Data['total']  = $SumTotal; // Tổng doanh thu
            $Key            = ceil($SumTotal/125000000) < 5 ? ceil($SumTotal/125000000) : 5;
            $Data['money']  = $this->range_lead[$Key]['hard'] + $this->range_lead[$Key]['commission']*$SumTotal;

        }else{ // Sale
            // Doanh thu đầu tháng
            $DataSum        = $Model::where('first_time_pickup', '>=', $TimeStart)
                                    ->where('seller_id',(int)$UserInfo['id'])
                                    ->first([DB::raw('sum(total_firstmonth) as total_firstmonth')]);

            if(isset($DataSum->total_firstmonth)){
                $SumTotal                       = $DataSum->total_firstmonth;
                $Data['total_firstmonth']       = $DataSum->total_firstmonth;
            }

            // Doanh thu lũy kế
            $DataSum     = $ModelLastMonth::where('first_time_pickup','>=',$TimePreMonth)
                                          ->where('first_time_pickup','<',$TimeStart)
                                          ->where('seller_id',(int)$UserInfo['id'])
                                          ->first([DB::raw('sum(total_nextmonth) as total_nextmonth')]);

            if(isset($DataSum->total_nextmonth)){
                $SumTotal                      += $DataSum->total_nextmonth;
                $Data['total_nextmonth']        = $DataSum->total_nextmonth;
            }

            // Doanh thu lũy kế khác hàng ngừng sử dụng
            $LogSellerModel = new LogSellerModel;
            $DataPreStop     = $LogSellerModel::where('first_time_pickup','>=',$TimePreMonth)
                ->where('first_time_pickup','<',$TimeStart)
                ->where('seller_id',(int)$UserInfo['id'])
                ->first([DB::raw('sum(total_nextmonth) as total_nextmonth')]);
            if(!empty($DataPreStop)){
                foreach($DataPreStop as $val){
                    $SumTotal                      += $val['total_nextmonth'];
                    $Data['total_nextmonth']       += $val['total_nextmonth'];
                }
            }

            // Khách hàng quay lại

            $Data['total']  = $SumTotal; // Tổng doanh thu
            $Key            = ceil($SumTotal/25000000) < 5 ? ceil($SumTotal/25000000) : 5;
            if($Key == 0) $Key = 1;
            $Data['money']  = $this->range_sale[$Key]['hard'] + $this->range_sale[$Key]['commission']*$SumTotal;
        }

        if(!empty($ListUserId)){
            $ListUserId = array_unique($ListUserId);
            $UserModel = new \User;
            $ListUser   = $UserModel->whereIn('id',$ListUserId)->get(['id','fullname', 'phone', 'email', 'time_create'])->toArray();
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data,
            'list'          => ['first' => $First, 'pre' => $Pre],
            'user'          => isset($ListUser) ? $ListUser : []
        ]);
    }
}
