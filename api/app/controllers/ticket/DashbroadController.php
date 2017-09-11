<?php
namespace ticket;

use Response;
use Exception;
use Input;
use Validator;
use Excel;
use DB;
use LMongo;

use ordermodel\OrdersModel;
use sellermodel\UserInfoModel;
use sellermodel\UserInventoryModel;
use omsmodel\PipeJourneyModel;
use User;
use ticketmodel\RequestModel;
use ticketmodel\AssignModel;
use omsmodel\CourierNoteModel;


class DashbroadController extends \BaseController
{


    public $GroupStatus = [];

    public function __construct()
    {
        $BaseCtrl = new \BaseCtrl;
        $StatusGroup = $BaseCtrl->getStatusGroup(false);
        $this->GroupStatus = [];
        foreach ($StatusGroup as $key => $group) {
            $this->GroupStatus[$group['id']] = isset($this->GroupStatus[$group['id']]) ? $this->GroupStatus[$group['id']] : [];
            foreach ($group['group_order_status'] as $k => $v) {
                $this->GroupStatus[$group['id']][] = $v['order_status_code'];
            }
        }
    }

    // Giao chậm

    private function getAdditionalData($Data, $file_name = '')
    {
        $listUserID = $listOrderID = $listAddressID = $listFromAddressID = array();
        $listCity = $listDistrict = $listWard = array();
        foreach ($Data as $var) {
            $listUserID[] = $var->from_user_id;
            $listOrderID[] = $var->id;
            $listAddressID[] = $var->to_address_id;
            $listFromAddressID[] = $var->from_address_id;
            $listCity[] = $var->from_city_id;
            $listDistrict[] = $var->from_district_id;
            $listWard[] = $var->from_ward_id;
        }

        /*$listOrderDetail = \ordermodel\DetailModel::whereIn('order_id',$listOrderID)->select('id')->get();*/
        $listAddress = \ordermodel\AddressModel::whereIn('id', $listAddressID)->get();
        $Inventory = UserInventoryModel::whereIn('id', $listFromAddressID)->get();
        $listUser = User::whereIn('id', $listUserID)->select(['id', 'email', 'fullname', 'phone'])->get();

        $ListInventory = [];
        if (!$Inventory->isEmpty()) {
            foreach ($Inventory as $OneInventory) {
                $ListInventory[$OneInventory->id] = $OneInventory;
            }
        }
        $listUserArr = [];
        if (!$listUser->isEmpty()) {

            foreach ($listUser as $oneUser) {
                $listUserArr[$oneUser->id] = $oneUser;
            }
        }

        if (!$listAddress->isEmpty()) {
            foreach ($listAddress as $address) {
                if (!in_array($address->city_id, $listCity)) {
                    $listCity[] = $address->city_id;
                }
                if (!in_array($address->province_id, $listDistrict)) {
                    $listDistrict[] = $address->province_id;
                }
                if (!in_array($address->ward_id, $listWard)) {
                    $listWard[] = $address->ward_id;
                }
            }
        }


        $LCity = $LDistrict = [];


        $listCity = \CityModel::all(array('city_name', 'id'));
        if (!empty($listCity)) {
            foreach ($listCity AS $one) {
                $LCity[$one['id']] = $one['city_name'];
            }
        }
        $listDistrict = \DistrictModel::all(array('district_name', 'id'));
        if (!empty($listDistrict)) {
            foreach ($listDistrict AS $one) {
                $LDistrict[$one['id']] = $one['district_name'];
            }
        }
        foreach ($Data as $k => $var) {
            //merge order detail
            /*foreach($listOrderDetail as $detail) {
                if($detail->order_id==$var->id) {
                    $DataGroup[$k]->order_detail = $detail;
                }
            }*/
            $to_city = $to_district = '';
            //merge order address
            foreach ($listAddress as $address) {
                if ($address->id == $var->to_address_id) {
                    $Data[$k]->to_address = $address->address;
                    $to_city = $address->city_id;
                    $to_district = $address->province_id;
                }
            }
            $Data[$k]->from_user = $listUserArr[$var->from_user_id];
            if (isset($ListInventory[$var->from_address_id])) {
                $Data[$k]->inventory = $ListInventory[$var->from_address_id];
            }

            $Data[$k]->from_city = isset($LCity[$var->from_city_id]) ? $LCity[$var->from_city_id] : '';
            $Data[$k]->from_district = isset($LDistrict[$var->from_district_id]) ? $LDistrict[$var->from_district_id] : '';
            $Data[$k]->to_city = isset($LCity[$to_city]) ? $LCity[$to_city] : '';
            $Data[$k]->to_district = isset($LDistrict[$to_district]) ? $LDistrict[$to_district] : '';
        }
        if (!empty($file_name)) {
            return $this->ExportExcel($file_name, $Data);
        }
        return $Data;
    }

    public function getOrderNote(){
        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $itemPage           = Input::has('item_page')           ? (int)Input::get('item_page')              : 20;
        $TimeCreate         = Input::has('time_create')         ? (int)Input::get('time_create') : 0;
        $TimeCreateEnd      = Input::has('time_create_end')     ? (int)Input::get('time_create_end') : 0;
        $TrackingCode       = Input::has('tracking_code')       ? Input::get('tracking_code') : "";

        $Model      = new CourierNoteModel;
        $OrderModel = new OrdersModel;

        if(!empty($TrackingCode)){
            $Order = $OrderModel->where('tracking_code', $TrackingCode)->first();

            if(empty($Order)){
                return Response::json([
                    "error" => false,
                    "error_message"=> "Không tìm thấy mã vận đơn"
                ]);
            }
            $Model = $Model->where('order_id', $Order->id);
        }

        if(empty($TimeCreate)){
            $TimeCreate = strtotime(date('Y-m-d', strtotime('-30 day')));
        }

        if(!empty($TimeCreateEnd)){
            $Model  = $Model->where('time_create', '<=', $TimeCreateEnd);
        }

        $offset     = ($page - 1)*$itemPage;


        $Model  = $Model
            ->where('time_create', '>=', $TimeCreate)
            ->orderBy('id', 'DESC');
        $Total = clone $Model;
        $Total= $Total->count();

        if(empty($Total)){
            return Response::json([
                "error"         => false,
                "error_message" => "",
                "data"          => [],
                "total"         => 0

            ]);
        }

        $Data = $Model->skip($offset)->take($itemPage)->get()->toArray();



        $ListOrderID = [];
        foreach ($Data  as $key => $value){
            $ListOrderID[] = $value['order_id'];
        }

        $Orders = $OrderModel
            ->whereIn('id', $ListOrderID)
            ->where('time_create', '>=', strtotime(date('Y-m-d', strtotime('-30 day'))))
            ->with(['pipe_journey' => function ($q) {
                $q->where('type', 1)->orderBy('time_create', 'ASC');
            }])
            ->get();

        $Orders =  $this->getAdditionalData($Orders);
        $_Order = [];
        foreach($Orders as $key => $value){
            $_Order[$value['id']] = $value;
        }

        foreach($Data as $key=> $value){
            $Data[$key]['order'] = [];
            if(!empty($_Order[$value['order_id']])){
                $Data[$key]['order'] = $_Order[$value['order_id']];
            }
        }


        return Response::json([
            'error'         => false,
            'error_message' => "Thành công",
            'data'          => $Data,
            "total"         => $Total
        ]);

    }

    public function postUpdateNote(){
        $Active         = Input::has('active') ? Input::get('active') : 0;
        $NoteId         = Input::has('note_id') ? (int)Input::get('note_id') : 0;

        if($Active){
            $Active = 2;
        }else {
            $Active = 1;
        }

        $Model = new CourierNoteModel;
        $Model = $Model->where('id', $NoteId)->first();

        if(empty($Model)){
            return Response::json([
                'error'=> true,
                'error_message'=> 'Không tìm thấy',
                'data'=> ''
            ]);
        }

        $Model->active = $Active;


        $result = $Model->save();

        return Response::json([
            'error'=> false,
            'error_message'=> 'Thành công',
            'data'=> $result
        ]);


    }

    public function getShowNote(){
        $CourierId       = Input::has('courier_id') ? (int)Input::get('courier_id') : 0;
        $OrderId         = Input::has('order_id') ? (int)Input::get('order_id') : 0;

        $Model = new CourierNoteModel;
        if(!empty($CourierId)){
            $Model  = $Model->where('courier_id', $CourierId);
        }
        if(!empty($OrderId)){
            $Model  = $Model->where('order_id', $OrderId);
        }
        $Data = $Model->orderBy('id', 'DESC')->get()->toArray();

        return Response::json([
            'error'         => false,
            'error_message' => 'Thành công',
            'data'          => $Data
        ]);
    }

    public function postCreateNote(){
        $CourierId       = Input::has('courier_id') ? (int)Input::get('courier_id') : 0;
        $OrderId         = Input::has('order_id') ? (int)Input::get('order_id') : 0;
        $Note         = Input::has('note') ? Input::get('note') : "";

        $UserInfo = $this->UserInfo();
        if(empty($CourierId) || empty($OrderId) || empty($Note)){
            return Response::json([
                'error'         => true,
                'error_message' => 'Dữ liệu gửi lên không đúng',
                'data'          => []
            ]);
        }

        $Model = new CourierNoteModel;

        $Model->courier_id  = $CourierId;
        $Model->user_id     = $UserInfo['id'];
        $Model->order_id    = $OrderId;
        $Model->note        = $Note;
        $Model->time_create = time();
        $Model->time_update = 0;

        try{
            $Model->save();
        }catch (Exception $e){
            return Response::json([
                'error'         => true,
                'error_message' => 'Lỗi kết nối, vui lòng thử lại sau !',
                'message'=>     $e->getMessage(),
                'data'          => []
            ]);
        }

        return Response::json([
            'error'         => false,
            'error_message' => 'Thành công',
            'data'          => []
        ]);




    }

    public function getSlowDelivery()
    {
        $page       = Input::has('page') ? (int)Input::get('page') : 1;
        $itemPage   = Input::has('limit') ? Input::get('limit') : 20;
        $Cmd        = Input::has('cmd') ? Input::get('cmd') : "";
        $District   = Input::has('district_id') ? Input::get('district_id') : 0;
        $City       = Input::has('city_id') ? Input::get('city_id') : 0;

        $TrackingCode   = Input::has('tracking_code')     ? Input::get('tracking_code') : 0;
        $Service        = Input::has('service')     ? Input::get('service') : 0;

        $ToDistrict   = Input::has('to_district_id') ? Input::get('to_district_id') : 0;
        $ToCity       = Input::has('to_city_id') ? Input::get('to_city_id') : 0;

        $offset = ($page - 1) * $itemPage;


        $UserInfo = $this->UserInfo();
        $Model = new OrdersModel;

        if (empty($this->GroupStatus)) {
            return Response::json([
                'error' => true,
                'error_message' => 'Lỗi lấy trạng thái',
                'data' => [],

            ]);
        }


        $DataGroup = $Model
            ->where('time_pickup', '>=', strtotime(date('Y-m-d', strtotime('-30 day'))))
            ->where('status', 51)
            ->where('estimate_delivery', '>', 0)
            ->where('time_pickup', '>', 0)
            ->where('courier_id', $UserInfo['courier_id']);



        if (!empty($City)) {
            $DataGroup = $DataGroup->where('from_city_id', $City);
        }

        if (!empty($District)) {
            $DataGroup = $DataGroup->where('from_district_id', $District);
        }

        if (!empty($ToCity) && empty($ToDistrict) ) {
            $BaseCtrl           = new \BaseCtrl;

            Input::merge(['city' => $ToCity]);
            $ListDistrictId = $BaseCtrl->getDistrict(false);
            $ListId         = [];
            foreach($ListDistrictId as $val){
                $ListId[]   = (int)$val['id'];
            }

            $DataGroup = $DataGroup->whereIn('to_district_id', $ListId);
        }


        if (!empty($ToDistrict)) {
            $DataGroup = $DataGroup->where('to_district_id', $ToDistrict);
        }

        if (!empty($TrackingCode)) {
            $DataGroup = $DataGroup->where('tracking_code',$TrackingCode)
                ->orWhere('courier_tracking_code', $TrackingCode);
        }

        if (!empty($Service)) {
            $DataGroup = $DataGroup->where('service_id', $Service);
        }

        $DataGroup = $DataGroup->whereRaw('(time_pickup + estimate_delivery * 3600) <= ' . time())
            ->with(['pipe_journey' => function ($query) {
                $query->where('type', 1)->orderBy('time_create', 'ASC');
            }, 'CourierNote'])
            ->select([
                'id',
                'tracking_code',
                'order_code',
                'service_id',
                'courier_id',
                'courier_tracking_code',
                'checking',
                'fragile',
                'from_user_id',
                'to_buyer_id',
                'to_name',
                'to_phone',
                'to_email',
                'from_city_id',
                'from_district_id',
                'from_ward_id',
                'from_address',
                'to_address_id',
                'to_district_id',
                'product_name',
                'total_amount',
                'status',
                'time_create',
                'time_update',
                'time_accept',
                'time_pickup',
                'time_success',
                'estimate_delivery',
                'total_weight'
            ]);

        $Total = clone $DataGroup;
        $Total = $Total->count();
        if ($Total == 0) {
            return Response::json([
                'error' => false,
                'error_message' => 'Thành công',
                'data' => [],
                'total' => $Total
            ]);
        }


        if ($Cmd == 'count') {
            if ($Cmd == 'count') {
                $TotalDoing = 0;
                $TotalDone  = 0;
                $SlowDelivery = $DataGroup->get()->toArray();

                foreach ($DataGroup as $key => $value) {
                    if(!empty($value['courier_note'])){
                        $TotalDone ++;
                    }
                }

                $TotalDoing = sizeof($SlowDelivery) - $TotalDone;

                return Response::json([
                    'error'         => false,
                    'error_message' => 'Thành công',
                    'data'          => [],
                    'total'         => $Total,
                    'total_done'    => $TotalDone,
                    'total_doing'   => $TotalDoing
                ]);
            }
        }


        if ($Cmd == 'export') {
            return $this->getAdditionalData($DataGroup->get(), 'Don_hang_giao_cham');

        }
        if ($Cmd == 'diagram') {
            return $DataGroup;
        }


        $DataGroup = $DataGroup->skip($offset)->take($itemPage)->get();


        return Response::json([
            'error' => false,
            'error_message' => 'Thành công',
            'data' => $this->getAdditionalData($DataGroup),
            'total' => $Total
        ]);
    }


    public function getReturn()
    {
        $page       = Input::has('page') ? (int)Input::get('page') : 1;
        $itemPage   = Input::has('limit') ? Input::get('limit') : 20;
        $Cmd        = Input::has('cmd') ? Input::get('cmd') : "";
        $District   = Input::has('district_id') ? Input::get('district_id') : 0;
        $City       = Input::has('city_id') ? Input::get('city_id') : 0;
        $TrackingCode   = Input::has('tracking_code')     ? Input::get('tracking_code') : 0;
        $Service        = Input::has('service')     ? Input::get('service') : 0;


        $ToDistrict   = Input::has('to_district_id') ? Input::get('to_district_id') : 0;
        $ToCity       = Input::has('to_city_id') ? Input::get('to_city_id') : 0;

        $offset = ($page - 1) * $itemPage;

        $UserInfo = $this->UserInfo();

        $Model = new OrdersModel;

        if (empty($this->GroupStatus)) {
            return Response::json([
                'error' => true,
                'error_message' => 'Lỗi lấy trạng thái',
                'data' => []
            ]);
        }

        $DataGroup = $Model
            ->where('time_pickup', '>=', strtotime(date('Y-m-d', strtotime('-30 day'))))
            ->where('status', 61)
            ->where('courier_id', $UserInfo['courier_id']);

        if (!empty($City)) {
            $DataGroup = $DataGroup->where('from_city_id', $City);
        }

        if (!empty($District)) {
            $DataGroup = $DataGroup->where('from_district_id', $District);
        }

        if (!empty($ToCity) && empty($ToDistrict) ) {
            $BaseCtrl           = new \BaseCtrl;

            Input::merge(['city' => $ToCity]);
            $ListDistrictId = $BaseCtrl->getDistrict(false);
            $ListId         = [];
            foreach($ListDistrictId as $val){
                $ListId[]   = (int)$val['id'];
            }

            $DataGroup = $DataGroup->whereIn('to_district_id', $ListId);
        }

        if (!empty($ToDistrict)) {
            $DataGroup = $DataGroup->where('to_district_id', $ToDistrict);
        }

        if (!empty($TrackingCode)) {
            $DataGroup = $DataGroup->where('tracking_code',$TrackingCode)
                ->orWhere('courier_tracking_code', $TrackingCode);
        }

        if (!empty($Service)) {
            $DataGroup = $DataGroup->where('service_id', $Service);
        }

        $DataGroup = $DataGroup->with(['pipe_journey' => function ($query) {
            $query->where('type', 1)->orderBy('time_create', 'ASC');
        }, 'CourierNote'])
            ->select([
                'id',
                'tracking_code',
                'order_code',
                'service_id',
                'courier_id',
                'courier_tracking_code',
                'checking',
                'fragile',
                'from_user_id',
                'to_buyer_id',
                'to_name',
                'to_phone',
                'to_email',
                'from_city_id',
                'from_district_id',
                'from_ward_id',
                'from_address',
                'to_address_id',
                'to_district_id',
                'product_name',
                'total_amount',
                'status',
                'time_create',
                'time_update',
                'time_accept',
                'time_pickup',
                'time_success',
                'estimate_delivery',
                'total_weight'
            ]);

        $Total = clone $DataGroup;
        $Total = $Total->count();

        if ($Total == 0) {
            return Response::json([
                'error' => false,
                'error_message' => 'Thành công',
                'data' => [],
                'total' => $Total
            ]);
        }

        if ($Cmd == 'count') {
            $TotalDoing = 0;
            $TotalDone  = 0;
            $DataGroup = $DataGroup->get()->toArray();

            foreach ($DataGroup as $key => $value) {
                if(!empty($value['courier_note'])){
                    $TotalDone ++;
                }
            }

            
           
            $TotalDoing = sizeof($DataGroup) - $TotalDone;
            return Response::json([
                'error'         => false,
                'error_message' => 'Thành công',
                'data'          => [],
                'total'         => $Total,
                'total_done'    => $TotalDone,
                'total_doing'   => $TotalDoing
            ]);
        }
        if ($Cmd == 'export') {
            return $this->getAdditionalData($DataGroup->get(), 'Don_hang_chuyen_hoan');
        }
        if ($Cmd == 'diagram') {
            return $DataGroup;
        }

        $DataGroup = $DataGroup->skip($offset)->take($itemPage)->get();


        return Response::json([
            'error' => false,
            'error_message' => 'Thành công',
            'data' => $this->getAdditionalData($DataGroup),
            'total' => $Total
        ]);
    }

    public function getSlowPickup()
    {
        $page       = Input::has('page')    ? (int)Input::get('page') : 1;
        $itemPage   = Input::has('limit')   ? Input::get('limit') : 20;

        $Cmd        = Input::has('cmd')     ? Input::get('cmd') : "";
        $offset     = ($page - 1) * $itemPage;

        $District   = Input::has('district_id') ? Input::get('district_id') : 0;
        $City       = Input::has('city_id')     ? Input::get('city_id') : 0;

        $TrackingCode   = Input::has('tracking_code')     ? Input::get('tracking_code') : 0;
        $Service        = Input::has('service')     ? Input::get('service') : 0;

        $ToDistrict   = Input::has('to_district_id') ? Input::get('to_district_id') : 0;
        $ToCity       = Input::has('to_city_id') ? Input::get('to_city_id') : 0;


        $UserInfo = $this->UserInfo();
        $TimeNow = strtotime(date("Y-m-d"));

        $PipeJourneyModel = new PipeJourneyModel;
        $Model = new OrdersModel;

        if (empty($this->GroupStatus)) {
            return Response::json([
                'error' => true,
                'error_message' => 'Lỗi lấy trạng thái',
                'data' => []
            ]);
        }





        $DataSlowPickup = $Model->where('time_create', '>=', strtotime(date('Y-m-d', strtotime(' -7 day'))))
            ->whereIn('status', $this->GroupStatus['25'])
            ->where('time_pickup', '=', 0)
            ->where('courier_id', $UserInfo['courier_id']);

        if (!empty($City)) {
            $DataSlowPickup = $DataSlowPickup->where('from_city_id', $City);
        }

        if (!empty($District)) {
            $DataSlowPickup = $DataSlowPickup->where('from_district_id', $District);
        }

        if (!empty($ToCity) && empty($ToDistrict) ) {
            $BaseCtrl           = new \BaseCtrl;

            Input::merge(['city' => $ToCity]);
            $ListDistrictId = $BaseCtrl->getDistrict(false);
            $ListId         = [];
            foreach($ListDistrictId as $val){
                $ListId[]   = (int)$val['id'];
            }

            $DataSlowPickup = $DataSlowPickup->whereIn('to_district_id', $ListId);
        }

        if (!empty($ToDistrict)) {
            $DataSlowPickup = $DataSlowPickup->where('to_district_id', $ToDistrict);
        }

        if (!empty($TrackingCode)) {
            $DataSlowPickup = $DataSlowPickup->where('tracking_code',$TrackingCode)
                ->orWhere('courier_tracking_code', $TrackingCode);
        }

        if (!empty($Service)) {
            $DataSlowPickup = $DataSlowPickup->where('service_id', $Service);
        }

        $DataSlowPickup = $DataSlowPickup->where(function ($q) use ($TimeNow) {
            $q->orWhere(function ($query) use ($TimeNow) {
                $query = $query// < 8h
                ->whereRaw('time_accept <= UNIX_TIMESTAMP(FROM_UNIXTIME(time_accept ,"%Y-%m-%d 00:00:00")) + 8 * 3600')
                    ->whereRaw('(UNIX_TIMESTAMP(FROM_UNIXTIME(time_accept ,"%Y-%m-%d 00:00:00")) + 12 * 3600)' . ' < ' . time());
            })
                ->orWhere(function ($query) use ($TimeNow) {
                    $query = $query
                        ->whereRaw('time_accept <= UNIX_TIMESTAMP(FROM_UNIXTIME(time_accept ,"%Y-%m-%d 00:00:00")) + 14 * 3600')
                        ->whereRaw('(UNIX_TIMESTAMP(FROM_UNIXTIME(time_accept ,"%Y-%m-%d 00:00:00")) + 18 * 3600)' . ' < ' . time());
                });
        })
            ->with(['pipe_journey' => function ($query) {
                $query->where('type', 1)->orderBy('time_create', 'ASC');
            }, 'CourierNote'])
            ->select([
                'id',
                'tracking_code',
                'order_code',
                'service_id',
                'courier_id',
                'courier_tracking_code',
                'checking',
                'fragile',
                'from_user_id',
                'to_buyer_id',
                'to_name',
                'to_phone',
                'to_email',
                'from_city_id',
                'from_district_id',
                'from_ward_id',
                'from_address',
                'to_address_id',
                'to_district_id',
                'product_name',
                'total_amount',
                'status',
                'time_create',
                'time_update',
                'time_accept',
                'time_pickup',
                'time_success',
                'estimate_delivery',
                'total_weight'
            ]);

        $Total = clone $DataSlowPickup;
        $Total = $Total->count();
        if ($Total == 0) {
            return Response::json([
                'error' => false,
                'error_message' => 'Thành công',
                'data' => [],
                'total' => $Total
            ]);
        }

        if ($Cmd == 'count') {
            $TotalDoing = 0;
            $TotalDone  = 0;
            $SlowPickup = $DataSlowPickup->get()->toArray();

            foreach ($SlowPickup as $key => $value) {
                if(!empty($value['courier_note'])){
                    $TotalDone ++;
                }
            }


           
            $TotalDoing = sizeof($SlowPickup) - $TotalDone;
            return Response::json([
                'error'         => false,
                'error_message' => 'Thành công',
                'data'          => [],
                'total'         => $Total,
                'total_done'    => $TotalDone,
                'total_doing'   => $TotalDoing
            ]);
        }
        if ($Cmd == 'export') {
            return $this->getAdditionalData($DataSlowPickup->get(), 'Don_hang_lay_cham');
        }
        if ($Cmd == 'diagram') {
            return $DataSlowPickup;
        }

        $DataSlowPickup = $DataSlowPickup->skip($offset)->take($itemPage)->get();


        return Response::json([
            'error'         => false,
            'error_message' => 'Thành công',
            'data'  => $this->getAdditionalData($DataSlowPickup),
            'total' => $Total
        ]);
    }

    public function getReDelivery()
    {
        $page           = Input::has('page')    ? (int)Input::get('page') : 1;
        $itemPage       = Input::has('limit')   ? Input::get('limit') : 20;
        $Process        = Input::has('process') ? Input::get('process') : "";
        $Cmd            = Input::has('cmd')                 ? Input::get('cmd') : "";
        $District       = Input::has('district_id')         ? Input::get('district_id') : 0;
        $City           = Input::has('city_id')             ? Input::get('city_id') : 0;
        $TrackingCode   = Input::has('tracking_code')       ? Input::get('tracking_code') : 0;
        $Service        = Input::has('service')             ? Input::get('service') : 0;
        $ToDistrict     = Input::has('to_district_id') ? Input::get('to_district_id') : 0;
        $ToCity         = Input::has('to_city_id') ? Input::get('to_city_id') : 0;


        $offset     = ($page - 1) * $itemPage;
        $UserInfo   = $this->UserInfo();

        $PipeJourneyModel   = new PipeJourneyModel;
        $Model              = new OrdersModel;

        if (empty($this->GroupStatus)) {
            return Response::json([
                'error' => true,
                'error_message' => 'Lỗi lấy trạng thái',
                'data' => []
            ]);
        }



        $ListIdReDelivery = $PipeJourneyModel->where('time_create', '>=', strtotime(date('Y-m-d', strtotime(' -7 day'))))
            ->where('type', 1)
            ->where(function ($query) {
                $query
                    ->orWhere(function ($q) {
                        $q->where('pipe_status', 707)->where('group_process', 29);
                    })
                    ->orWhere(function ($q) {
                        $q->where('pipe_status', 903)->where('group_process', 31);
                    });
            })
            ->where('report_courier', 1);

        /*if (!empty($Process) && $Process == 'done') {
            $ListIdReDelivery = $ListIdReDelivery->where(function ($query) {
                $query
                    ->orWhere(function ($q) {
                        $q->where('pipe_status', 709)->where('group_process', 29);
                    })
                    ->orWhere(function ($q) {
                        $q->where('pipe_status', 907)->where('group_process', 31);
                    });
            });
        }else if($Process == 'doing'){
            $ListIdReDelivery = $ListIdReDelivery->where(function ($query) {
                $query
                    ->orWhere(function ($q) {
                        $q->where('pipe_status','!=', 709)->where('group_process', 29);
                    })
                    ->orWhere(function ($q) {
                        $q->where('pipe_status', 907)->where('group_process', 31);
                    });
            });
        }*/

        $ListIdReDelivery = $ListIdReDelivery->lists('tracking_code');

        if (empty($ListIdReDelivery)) {
            return Response::json([
                'error'     => false,
                'message'   => 'success',
                'data'      => []
            ]);
        }

        $ListIdReDelivery = array_unique($ListIdReDelivery);

        $DataReDelivery = $Model->where('time_pickup', '>=', strtotime(date('Y-m-d', strtotime(' -30 day'))))
            ->whereIn('id', $ListIdReDelivery)
            ->where('courier_id', $UserInfo['courier_id']);


        if (!empty($TrackingCode)) {
            $DataReDelivery = $DataReDelivery->where('tracking_code',$TrackingCode)
                ->orWhere('courier_tracking_code', $TrackingCode);
        }

        if (!empty($Service)) {
            $DataReDelivery = $DataReDelivery->where('service_id', $Service);
        }

        if (!empty($City)) {
            $DataReDelivery = $DataReDelivery->where('from_city_id', $City);
        }


        if (!empty($District)) {
            $DataReDelivery = $DataReDelivery->where('from_district_id', $District);
        }


        if (!empty($ToCity) && empty($ToDistrict) ) {
            $BaseCtrl           = new \BaseCtrl;

            Input::merge(['city' => $ToCity]);
            $ListDistrictId = $BaseCtrl->getDistrict(false);
            $ListId         = [];
            foreach($ListDistrictId as $val){
                $ListId[]   = (int)$val['id'];
            }

            $DataReDelivery = $DataReDelivery->whereIn('to_district_id', $ListId);
        }

        if (!empty($ToDistrict)) {
            $DataReDelivery = $DataReDelivery->where('to_district_id', $ToDistrict);
        }


        $DataReDelivery = $DataReDelivery->whereIn('status', [54, 55, 56, 57, 58, 59, 60, 77])

            ->with(['pipe_journey' => function ($query) {
                $query->where('type', 1)->orderBy('time_create', 'ASC');
            }, 'MetaStatus', 'CourierNote'])
            ->select([
                'id',
                'tracking_code',
                'order_code',
                'service_id',
                'courier_id',
                'courier_tracking_code',
                'checking',
                'fragile',
                'from_user_id',
                'to_buyer_id',
                'to_name',
                'to_phone',
                'to_email',
                'from_city_id',
                'from_district_id',
                'from_ward_id',
                'from_address',
                'to_address_id',
                'to_district_id',
                'product_name',
                'total_amount',
                'status',
                'time_create',
                'time_update',
                'time_accept',
                'time_pickup',
                'time_success',
                'estimate_delivery',
                'total_weight'
            ]);

        $Total = clone $DataReDelivery;
        $Total = $Total->count();

        if ($Total == 0) {
            return Response::json([
                'error' => false,
                'error_message' => 'Thành công',
                'data' => [],
                'total' => $Total
            ]);
        }

        if ($Cmd == 'count') {

            $TotalDoing = 0;
            $TotalDone  = 0;
            $ReDelivery = $DataReDelivery->get()->toArray();

            foreach ($ReDelivery as $key => $value) {
                if(!empty($value['courier_note'])){
                    $TotalDone ++;
                }
            }
            $TotalDoing = sizeof($ReDelivery) - $TotalDone;

            return Response::json([
                'error'         => false,
                'error_message' => 'Thành công',
                'data'          => [],
                'total'         => $Total,
                'total_done'    => $TotalDone,
                'total_doing'   => $TotalDoing
            ]);
        }

        if ($Cmd == 'export') {
            return $this->getAdditionalData($DataReDelivery->get(), 'Don_hang_can_phat_lai');
        }
        if ($Cmd == 'diagram') {
            return $DataReDelivery;
        }
        $DataReDelivery = $DataReDelivery
            ->skip($offset)->take($itemPage)
            ->get();


        return Response::json([
            'error' => false,
            'error_message' => 'Thành công',
            'data' => $this->getAdditionalData($DataReDelivery),
            'total' => $Total
        ]);
    }

    public function getDiagram()
    {
        
    }

    public function getTicket()
    {
        $UserInfo = $this->UserInfo();
        $id       = (int)$UserInfo['id'];

        $ListTicketAssign = AssignModel::where('assign_id', $id)->where('time_create', '>=', strtotime(date('Y-m-d', strtotime(' -7 day'))))->where('active', 1)->lists('ticket_id');
        $ListTicketAssign = array_unique($ListTicketAssign);

        if (empty($ListTicketAssign)) {
            $contents = array(
                'error' => false,
                'message' => 'success',
                'data' => []
            );
            return Response::json($contents);
        }

        $Model = new RequestModel;
        $Model = $Model->whereIn('id', $ListTicketAssign);

        $Total = 0;
        if (!empty($TimeStart)) {
            $Model = $Model->where('time_create', '>=', $TimeStart);
        } else {
            $Model = $Model->where('time_create', '>=', time() - 86400 * 7);
        }

        $Model = $Model->orderBy('time_create', 'DESC');
        $Data = $Model->take(10)->get()->toArray();

        $contents = array(
            'error' => false,
            'message' => 'success',
            'data' => $Data
        );

        return Response::json($contents);
    }
    public function getProcessed(){
        $UserInfo = $this->UserInfo();

        $TimeStart = mktime(0, 0, 0);

        $UserInfoModel  = new UserInfoModel;
        $ListUserId  = $UserInfoModel->where('courier_id', $UserInfo['courier_id'])->whereIn('privilege', [3, 4, 5])->where('location_id', '>', 0)->get()->lists('user_id');

        $PipeJourneys = PipeJourneyModel::where('time_create', '>=', $TimeStart)
            ->whereIn('user_id', $ListUserId)
            ->where('type', 1)
            ->select(['user_id', 'tracking_code'])
            ->get()->toArray();
        $ListOrderId = [];

        $ListOrderId = CourierNoteModel::where('time_create', '>=', $TimeStart)->lists('order_id');

        if(empty($ListOrderId)){
            return Response::json([
                'error'         => false,
                'error_message' =>'',
                'data'          => []
            ]);
        }

        $Orders = OrdersModel::whereIn('id', $ListOrderId)
            ->where('time_create', '>=', strtotime(date('Y-m-d', strtotime(' -30 day'))))
            ->select(DB::raw('from_city_id, from_district_id, count(*) as total'))
            ->groupBy('from_district_id')
            ->get()->toArray();

        return Response::json([
            'error'         => false,
            'error_message' =>'',
            'data'          => $Orders
        ]);
    }
    /*public function getProcessedByCity(){
        $UserInfo = $this->UserInfo();


        $UserInfoModel  = new UserInfoModel;
        $UserInfoModel  = $UserInfoModel->where('courier_id', $UserInfo['courier_id'])->where('privilege', 3)->where('location_id', '>', 0)->get(['id', 'user_id', 'courier_id', 'location_id'])->toArray();
        $ListUserId     = [];
        $Location       = [];
        foreach ($UserInfoModel as $key => $value){
            $ListUserId[] = $value['user_id'];
            $Location[$value['user_id']] = $value['location_id'];
        }


        $TimeStart = mktime(0, 0, 0);

        $PipeJourneys = PipeJourneyModel::where('time_create', '>=', $TimeStart)
            ->whereIn('user_id', $ListUserId)
            ->where('type', 1)
            ->select(DB::raw('user_id, count(user_id) as total'))
            ->groupBy('user_id')
            ->get()->toArray();

        $Data = [];
        foreach($PipeJourneys as $key => $value){
            if(!empty($Location[$value['user_id']])){

                if(empty($Data[$Location[$value['user_id']]])){
                    $Data[$Location[$value['user_id']]] = 0;
                }

                $Data[$Location[$value['user_id']]] ++ ;
            }
        }
        return Response::json(array(
            'error'         => false,
            'error_message' => 'Thành công',
            'data'          => $Data
        ));
    }*/


    public function ExportExcel($filename = 'Van_don_can_xu_ly', $data)
    {
        return Excel::create($filename, function ($excel) use ($data) {
            $excel->sheet('Vận đơn', function ($sheet) use ($data) {
                $sheet->mergeCells('C1:F1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('', '', 'Vận đơn'));
                // set width column
                $sheet->setWidth(array(
                    'A' => 5,
                    'B' => 20,
                    'C' => 25,
                    'D' => 30,
                    'E' => 30,
                    'F' => 30,
                    'G' => 20,
                    'H' => 70,
                    'I' => 30,
                    'J' => 30,
                    'K' => 30,
                    'L' => 30,
                    'M' => 70,
                    'N' => 30,
                    'O' => 30,
                    'P' => 30,
                    'Q' => 30,
                    'R' => 30,
                    'S' => 30
                ));
                // set content row
                $sheet->row(3, array(
                    'STT', 'Mã vận đơn', 'Mã hãng vận chuyển', 'Người gửi', 'Số điện thoại', 'Tỉnh Thành gửi', 'Quận huyện gửi', 'Địa chỉ gửi', 'Người nhận', 'Số điện thoại người nhận', 'Tỉnh thành nhận', 'Quận huyện nhận', 'Địa chỉ người nhận', 'Trạng thái', 'Thời gian tạo', 'Thời gian duyệt', 'Thời gian lấy hàng'
                ));
                $sheet->row(3, function ($row) {
                    $row->setBackground('#B6B8BA');
                    $row->setBorder('solid', 'solid', 'solid', 'solid');
                    $row->setFontSize(12);
                });
                //
                $i = 1;
                foreach ($data AS $value) {
                    $dataExport = array(
                        'STT' => $i++,
                        'Mã vận đơn' => $value->tracking_code,
                        'Mã hãng vận chuyển' => $value->courier_tracking_code,
                        'Người gửi' => $value->from_user->fullname,
                        'Số điện thoại người gửi' => $value->from_user->phone,
                        'Tỉnh Thành gửi' => $value->from_city,
                        'Quận huyện gửi' => $value->from_district,
                        'Địa chỉ gửi' => isset($value->from_address) ? $value->from_address : '',
                        'Người nhận' => $value->to_name,
                        'Số điện thoại người nhận' => $value->to_phone,
                        'Tỉnh Thành nhận' => $value->to_city,
                        'Quận huyện nhận' => $value->to_district,
                        'Địa chỉ nhận' => $value->to_address,
                        'Trạng thái' => $value->meta_status->name,
                        'Thời gian tạo' => date("d/m/y H:m", $value->time_create),
                        'Thời gian duyệt' => date("d/m/y H:m", $value->time_accept),
                        'Thời gian lấy hàng' => date("d/m/y H:m", $value->time_pickup)
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }


}
