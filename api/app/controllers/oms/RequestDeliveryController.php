<?php
namespace oms;

use Illuminate\Support\Facades\Cache;
use Input;
use Maatwebsite\Excel\Facades\Excel;
use metadatamodel\GroupOrderStatusModel;
use metadatamodel\OrderStatusModel;
use Response;
use sellermodel\CourierModel;
use User;
use ordermodel\OrdersModel;
use ordermodel\OrderProcessModel;

class RequestDeliveryController extends \BaseController
{

    public function getIndex() {
        $timeStart = Input::has('from_date') ? (int)Input::get('from_date') : 0;
        $timeEnd = Input::has('to_date') ? (int)Input::get('to_date') : 0;
        $courierID = Input::has('courier_id') ? (int)Input::get('courier_id') : 0;
        $cityID = Input::has('from_city') ? (int)Input::get('from_city') : 0;
        $districtID = Input::has('from_district') ? (int)Input::get('from_district') : 0;
        $currentPage = Input::has('currentPage') ? (int)Input::get('currentPage') : 0;
        $item_page = Input::has('item_page') ? (int)Input::get('item_page') : 0;
        $scCode = Input::has('sc_code') ? Input::get('sc_code') : 0;

        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege']==0) {
            $response = [
                'error' =>  true,
                'message'   =>  'Không có quyền'
            ];
            return Response::json($response);
        }

        $OrderModel = OrdersModel::query();

        $status = GroupOrderStatusModel::where('group_status',39)->lists('order_status_code');
        if(!empty($status)) {
            $OrderModel->whereIn('status',$status);
        }
        if(!empty($scCode)) {
            $OrderModel->where('tracking_code',$scCode);
        }
        if($courierID > 0){
            $OrderModel->where('courier_id',$courierID);
        }
        if($timeStart > 0){
            $OrderModel->where('time_create','>=',$timeStart);
        } else {
            $timeStart = $this->time() - 30*86400;
            $OrderModel->where('time_create','>=',$timeStart);
        }
        if($timeEnd > 0){
            $OrderModel->where('time_create','<=',$timeEnd);
        }

        if($cityID>0) {
            $OrderModel->where('from_city_id',$cityID);
        }
        if($districtID>0) {
            $OrderModel->where('from_district_id',$districtID);
        }

        if(!empty($UserInfo['courier'])){
            if($UserInfo['courier'] == 'ems'){
                $OrderModel->where('courier_id',8);
            }elseif($UserInfo['courier'] == 'emshn'){
                $OrderModel->where('courier_id',8)->where('from_city_id',18);
            }elseif($UserInfo['courier'] == 'emshcm'){
                $OrderModel->where('courier_id',8)->where('from_city_id',52);
            }elseif($UserInfo['courier'] == 'gts'){
                $OrderModel->where('courier_id',9);
            }
        }
        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $OrderModel->where('domain','lamido.vn');
        }
        //chodientu
        if($UserInfo['id'] == 2956 && $UserInfo['privilege'] == 3){
            $OrderModel->where('domain','chodientu.vn');
        }

        $total      = $OrderModel->count();

        $field = array('id', 'tracking_code','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create','time_pickup','time_success','estimate_delivery');

        $OrderModel = $OrderModel->with([
            'Service'
        ]);
        $OrderModel->skip($item_page*($currentPage-1))->take($item_page);
        $listData   = $OrderModel->get($field);
        if(!$listData->isEmpty()) {
            if (Cache::has('courier_cache')){
                $listCourier    = Cache::get('courier_cache');
            }else{
                $courier        = new CourierModel;
                $listCourier    = $courier::all(array('id','name'));
            }
            if(!empty($listCourier)){
                foreach($listCourier as $val){
                    $LCourier[$val['id']]   = $val['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCourier[(int)$val['courier_id']])){
                        $val->courier_name = $LCourier[(int)$val['courier_id']];
                    }
                }
            }


            $listStatus = OrderStatusModel::all(array('code','name'));
            if($listStatus){
                foreach($listStatus AS $one){
                    $LStatus[(int)$one['code']] = $one['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LStatus[(int)$val['status']])){
                        $val->status_name = $LStatus[(int)$val['status']];
                    }
                }
            }
            $listCity = \CityModel::all(array('city_name','id'));
            if(!empty($listCity)){
                foreach($listCity AS $one){
                    $LCity[$one['id']] = $one['city_name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCity[(int)$val['from_city_id']])){
                        $val->city_name = $LCity[(int)$val['from_city_id']];
                    }
                }
            }
            $listDistrict = \DistrictModel::all(array('district_name','id'));
            if(!empty($listDistrict)){
                foreach($listDistrict AS $one){
                    $LDistrict[$one['id']] = $one['district_name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LDistrict[(int)$val['from_district_id']])){
                        $val->district_name = $LDistrict[(int)$val['from_district_id']];
                    }
                }
            }
            $response = array(
                'error'     => false,
                'message'   => 'success',
                'total'     =>  $total,
                'data'      => $listData
            );
            return Response::json($response);
        } else {
            $response = array(
                'error'     => true,
                'message'   => 'Không có vận đơn!',
                'total'     => 0
            );

            return Response::json($response);
        }
    }

    public function getExportexcel() {
        $timeStart = Input::has('from_date') ? (int)Input::get('from_date') : 0;
        $timeEnd = Input::has('to_date') ? (int)Input::get('to_date') : 0;
        $courierID = Input::has('courier_id') ? (int)Input::get('courier_id') : 0;
        $cityID = Input::has('from_city') ? (int)Input::get('from_city') : 0;
        $districtID = Input::has('from_district') ? (int)Input::get('from_district') : 0;
        $currentPage = Input::has('currentPage') ? (int)Input::get('currentPage') : 0;
        $item_page = Input::has('item_page') ? (int)Input::get('item_page') : 0;
        $scCode = Input::has('sc_code') ? Input::get('sc_code') : 0;

        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege']==0) {
            $response = [
                'error' =>  true,
                'message'   =>  'Không có quyền'
            ];
            return Response::json($response);
        }

        $OrderModel = OrdersModel::query();

        $status = GroupOrderStatusModel::where('group_status',39)->lists('order_status_code');
        if(!empty($status)) {
            $OrderModel->whereIn('status',$status);
        }
        if(!empty($scCode)) {
            $OrderModel->where('tracking_code',$scCode);
        }
        if($courierID > 0){
            $OrderModel->where('courier_id',$courierID);
        }
        if($timeStart > 0){
            $OrderModel->where('time_create','>=',$timeStart);
        } else {
            $timeStart = $this->time() - 30*86400;
            $OrderModel->where('time_create','>=',$timeStart);
        }
        if($timeEnd > 0){
            $OrderModel->where('time_create','<=',$timeEnd);
        }

        if($cityID>0) {
            $OrderModel->where('from_city_id',$cityID);
        }
        if($districtID>0) {
            $OrderModel->where('from_district_id',$districtID);
        }

        if(!empty($UserInfo['courier'])){
            if($UserInfo['courier'] == 'ems'){
                $OrderModel->where('courier_id',8);
            }elseif($UserInfo['courier'] == 'emshn'){
                $OrderModel->where('courier_id',8)->where('from_city_id',18);
            }elseif($UserInfo['courier'] == 'emshcm'){
                $OrderModel->where('courier_id',8)->where('from_city_id',52);
            }elseif($UserInfo['courier'] == 'gts'){
                $OrderModel->where('courier_id',9);
            }
        }
        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $OrderModel->where('domain','lamido.vn');
        }
        //chodientu
        if($UserInfo['id'] == 2956 && $UserInfo['privilege'] == 3){
            $OrderModel->where('domain','chodientu.vn');
        }

        $total      = $OrderModel->count();

        $field = array('id','tracking_code','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create','time_pickup','time_success','estimate_delivery','to_address_id','to_name','to_phone');


        $listData   = $OrderModel->with([
            'OrderStatus'=> function ($query) use ($status){

            }
        ]);

        $listData   = $OrderModel->get($field);
        if(!$listData->isEmpty()) {
            if (Cache::has('courier_cache')) {
                $listCourier    = Cache::get('courier_cache');
            } else {
                $courier        = new CourierModel;
                $listCourier    = $courier::all(array('id','name'));
            }
            if(!empty($listCourier)){
                foreach($listCourier as $val){
                    $LCourier[$val['id']]   = $val['name'];
                }
            }
            $listAddressID = array();
            $listOrderID = [];
            foreach($listData as $key => $val){
                if (isset($LCourier[(int)$val['courier_id']])){
                    $val->courier_name = $LCourier[(int)$val['courier_id']];
                }
                $listAddressID[] = $val->to_address_id;
                $listOrderID[] = $val->id;
            }

            //get data for order
            $listAddress = \ordermodel\AddressModel::whereIn('id',$listAddressID)->get();
            //$orderProcess = OrderProcessModel::whereIn('order_id',$listOrderID)->get();

            $listOrderProcess = [];
            $listCity = $listDistrict = $listWard = [];

            if(!$listAddress->isEmpty()) {
                foreach($listAddress as $address) {
                    if(!in_array($address->city_id,$listCity)) {
                        $listCity[] = $address->city_id;
                    }
                    if(!in_array($address->province_id,$listDistrict)) {
                        $listDistrict[] = $address->province_id;
                    }
                    if(!in_array($address->ward_id,$listWard)) {
                        $listWard[] = $address->ward_id;
                    }
                }
            }
            $addressArr = $this->mapAddress($listCity,$listDistrict,$listWard);

            /*if(!$orderProcess->isEmpty()) {
                foreach($orderProcess as $oneOrderProcess) {
                    $listOrderProcess[$oneOrderProcess->order_id][] = $oneOrderProcess;
                }
            }*/
            foreach($listData as $k => $var) {

                //merge order address
                foreach($listAddress as $address) {
                    if($address->id == $var->to_address_id) {
                        $listData[$k]->to_address = $address->address. ((isset($addressArr[$address->city_id]) && isset($addressArr[$address->city_id][$address->province_id]) && isset($addressArr[$address->city_id][$address->province_id][$address->ward_id])) ? ' - '.$addressArr[$address->city_id][$address->province_id][$address->ward_id] : "");
                    }
                }
                // Modify by ThinhNV
                $note = "";
                $OrderStatus = $var->order_status;
                foreach ($OrderStatus as $key => $value) {
                    if($value->note && in_array($value->status, $status)){
                        $note .= $value->note.", \n";
                    }
                }
                if(!empty($note)){
                    $note = substr($note,0,-2);
                }
                $listData[$k]->note = $note;


                /*if(!empty($listOrderProcess[$var->id])) {
                    $totalItems = count($listOrderProcess[$var->id]);
                    foreach($listOrderProcess[$var->id] as $j => $oneOrderProcess) {
                        if($j+3 >= $totalItems) {
                            $note .= $oneOrderProcess->note.", \n";
                        }
                    }
                    $note = substr($note,0,-2);
                }
                $listData[$k]->note = $note;*/
            }

            $listStatus = OrderStatusModel::all(array('code','name'));
            if($listStatus){
                foreach($listStatus AS $one){
                    $LStatus[(int)$one['code']] = $one['name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LStatus[(int)$val['status']])){
                        $val->status_name = $LStatus[(int)$val['status']];
                    }
                }
            }
            $listCity = \CityModel::all(array('city_name','id'));
            if(!empty($listCity)){
                foreach($listCity AS $one){
                    $LCity[$one['id']] = $one['city_name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LCity[(int)$val['from_city_id']])){
                        $val->city_name = $LCity[(int)$val['from_city_id']];
                    }
                }
            }
            $listDistrict = \DistrictModel::all(array('district_name','id'));
            if(!empty($listDistrict)){
                foreach($listDistrict AS $one){
                    $LDistrict[$one['id']] = $one['district_name'];
                }
                foreach($listData as $key => $val){
                    if (isset($LDistrict[(int)$val['from_district_id']])){
                        $val->district_name = $LDistrict[(int)$val['from_district_id']];
                    }
                }
            }

            //var_dump($listData);die();
            //xuat du lieu ra excel
            return Excel::create('Van_don', function ($excel) use($listData) {
                $excel->sheet('Vận đơn', function ($sheet) use($listData) {
                    $sheet->mergeCells('C1:F1');
                    $sheet->row(1, function ($row) {
                        $row->setFontSize(20);
                    });
                    $sheet->row(1, array('','','Vận đơn'));
                    // set width column
                    $sheet->setWidth(array(
                        'A'     => 5,
                        'B'     => 20,
                        'C'     => 25,
                        'D'     => 15,
                        'E'     => 40,
                        'F'     => 30,
                        'G'     => 20,
                        'H'     => 30,
                        'I'     => 30,
                        'J'     => 30,
                        'K'     => 30,
                        'L'     => 30,
                        'M'     => 30
                    ));
                    // set content row
                    $sheet->row(3, array(
                        'STT', 'Mã vận đơn', 'Mã hãng vận chuyển', 'Hãng vận chuyển','Người gửi','Số điện thoại', 'Tỉnh Thành gửi', 'Quận huyện gửi', 'Địa chỉ gửi', 'Người nhận', 'Số điện thoại người nhận', 'Địa chỉ người nhận', 'Trạng thái', 'Thời gian tạo', 'Thời gian duyệt','Thời gian lấy hàng','Ghi chú'
                    ));
                    $sheet->row(3,function($row){
                        $row->setBackground('#B6B8BA');
                        $row->setBorder('solid','solid','solid','solid');
                        $row->setFontSize(12);
                    });
                    //
                    $i = 1;
                    foreach ($listData AS $value) {
                        $note = "";
                        $dataExport = array(
                            'STT' => $i++,
                            'Mã vận đơn' => $value['tracking_code'],
                            'Mã hãng vận chuyển' => $value['courier_tracking_code'],
                            'Hãng vận chuyển' => $value['courier_name'],
                            'Người gửi' => $value['from_name'],
                            'Số điện thoại người gửi' => $value['from_phone'],
                            'Tỉnh Thành gửi'  => $value['city_name'],
                            'Quận huyện gửi' => $value['district_name'],
                            'Địa chỉ gửi'    => $value['from_address'],
                            'Người nhận' => $value['to_name'],
                            'Số điện thoại người nhận' => $value['to_phone'],
                            'Địa chỉ nhận' => $value['to_address'],
                            'Trạng thái' => $value['status_name'],
                            'Thời gian tạo' => date("d/M/y H:m",$value['time_create']),
                            'Thời gian duyệt' => date("d/M/y H:m",$value['time_accept']),
                            'Thời gian lấy hàng' => date("d/M/y H:m",$value['time_pickup']),
                            'Ghi chú'       =>  $value['note']
                        );
                        $sheet->appendRow($dataExport);
                    }
                });
            })->export('xls');
        } else {
            $response = array(
                'error'     => true,
                'message'   => 'Không có vận đơn!',
                'total'     => 0
            );

            return Response::json($response);
        }
    }


    private function mapAddress($cityArr,$districtArr,$wardArr) {
        if(!empty($cityArr)) {
            $cities = \CityModel::whereIn('id',$cityArr)->get();
            $data = array();
            if(!$cities->isEmpty()) {
                foreach($cities as $ck => $city) {
                    if(!empty($districtArr)) {
                        $districts = \DistrictModel::whereIn('id', $districtArr)->where('city_id', $city->id)->get();
                        $data[$city->id][0][0] = $city->city_name;

                        if (!$districts->isEmpty()) {
                            foreach ($districts as $dk => $district) {
                                $data[$city->id][$district->id][0] = $district->district_name . ', ' . $city->city_name;

                                if(!empty($wardArr)) {
                                    $wards = \WardModel::whereIn('id', $wardArr)->where('city_id', $city->id)->where('district_id', $district->id)->get();
                                    if (!$wards->isEmpty()) {
                                        foreach ($wards as $wk => $ward) {
                                            $data[$city->id][$district->id][$ward->id] = $ward->ward_name . ', ' . $district->district_name . ', ' . $city->city_name;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return $data;
        }

    }
}