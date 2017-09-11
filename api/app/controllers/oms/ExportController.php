<?php
namespace oms;

use Illuminate\Support\Facades\Cache;
use Input;
use Maatwebsite\Excel\Facades\Excel;
use metadatamodel\OrderStatusModel;
use ordermodel\AddressModel;
use ordermodel\OrdersModel;
use Response;
use sellermodel\CourierModel;
use User;
use metadatamodel\GroupOrderStatusModel;
use CityModel, DistrictModel;

class ExportController extends \BaseController
{

    public function getIndex() {
        $UserInfo = $this->UserInfo();
        $groupStatus = Input::has('group_status') ? Input::get('group_status') : [];
        $status = Input::has('status') ? Input::get('status') : [];

        $page       = Input::has('page')        ? (int)Input::get('page')                : 1;
        $itemPage   = Input::has('limit')       ? Input::get('limit')                    : 20;
        $fromDate     = (Input::has('from_date') && (int)Input::get('from_date')>0)  ? Input::get('from_date')        : 0;
        $toDate     = (Input::has('to_date') && (int)Input::get('to_date')>0)  ? Input::get('to_date')     :  0;
        $courier_id    = Input::has('courier_id')   ? (int)Input::get('courier_id')                     : 0;
        $scCode    = Input::has('sc_code')   ?  Input::get('sc_code')                               : 0;
        $cityID    = Input::has('from_city')   ? (int)Input::get('from_city')                           : 0;
        $districtID    = Input::has('from_district')   ? (int)Input::get('from_district')                : 0;
        $key            =   Input::has('by_time')   ?   Input::get('by_time')   :   'time_accept';
        $domain = Input::has('domain')   ?   Input::get('domain')   :   '';


        if(!empty($groupStatus))
            $status = array_merge(GroupOrderStatusModel::whereIn('group_status',$groupStatus)->lists('order_status_code'),$status);

        $OrderModel = OrdersModel::query();
        $OrderModel = $OrderModel->where('time_create','>=',$this->time()-90*86400)->with(['ToOrderAddress']);
        if(!empty($status)) {
            $OrderModel->whereIn('status',$status);
        }
        if($courier_id > 0){
            $OrderModel->where('courier_id',$courier_id);
        }
        if($fromDate > 0){
            $OrderModel->where($key,'>=',$fromDate);
        } else {
            $fromDate = 30*86400;
            $OrderModel->where($key,'>=',$fromDate);
        }
        if($toDate > 0){
            $OrderModel->where($key,'<=',$toDate);
        }
        if(!empty($scCode)) {
            $OrderModel->where('tracking_code',$scCode);
        }
        if($cityID>0) {
            $OrderModel->where('from_city_id',$cityID);
        }
        if($districtID>0) {
            $OrderModel->where('from_district_id',$districtID);
        }
        
        if(!empty($domain)) {
            $OrderModel = $OrderModel->where('domain',$domain);
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

        $field = array('tracking_code','from_user_id','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create','time_pickup','time_success','estimate_delivery', 'to_address_id','to_name','to_phone');
        $total      = $OrderModel->count();

        $offset     = ($page - 1)*$itemPage;
        $OrderModel = $OrderModel->skip($offset)->take($itemPage);
        $OrderModel = $OrderModel->with([
            'Service'
        ]);
        $listData   = $OrderModel->get($field);
        if(!$listData->isEmpty()) {
            $listUserId = array();
            foreach ($listData AS $one) {
                $listUserId[] = $one['from_user_id'];
            }
            if (Cache::has('courier_cache')) {
                $listCourier = Cache::get('courier_cache');
            } else {
                $courier = new CourierModel;
                $listCourier = $courier::all(array('id', 'name'));
            }
            if (!empty($listCourier)) {
                foreach ($listCourier as $val) {
                    $LCourier[$val['id']] = $val['name'];
                }
            }

            $listStatus = OrderStatusModel::all(array('code', 'name'));
            if ($listStatus) {
                foreach ($listStatus AS $one) {
                    $LStatus[(int)$one['code']] = $one['name'];
                }
            }
            $listCity = CityModel::all(array('city_name', 'id'));
            if (!empty($listCity)) {
                foreach ($listCity AS $one) {
                    $LCity[$one['id']] = $one['city_name'];
                }
            }
            $listDistrict = DistrictModel::all(array('district_name', 'id'));
            if (!empty($listDistrict)) {
                foreach ($listDistrict AS $one) {
                    $LDistrict[$one['id']] = $one['district_name'];
                }
            }


            //set meta info
            $listAddressID = array();
            foreach ($listData as $key => $val) {
                if (isset($LCity[(int)$val['from_city_id']])) {
                    $val->city_name = $LCity[(int)$val['from_city_id']];
                }

                if (isset($LDistrict[(int)$val['from_district_id']])) {
                    $val->district_name = $LDistrict[(int)$val['from_district_id']];
                }

                if (isset($LStatus[(int)$val['status']])) {
                    $val->status_name = $LStatus[(int)$val['status']];
                }
                if (isset($LCourier[(int)$val['courier_id']])) {
                    $val->courier_name = $LCourier[(int)$val['courier_id']];
                }

                $listAddressID[] = $val->to_address_id;
            }
            $listAddress = AddressModel::whereIn('id', $listAddressID)->get();
            $LAddress = [];
            if (!$listAddress->isEmpty()) {
                foreach ($listAddress as $k => $val) {
                    $LAddress[$val->id] = $val;
                }
            }

            if (!empty($listUserId)) {
                $listUser = User::whereIn('id', $listUserId)->get(array('id', 'fullname', 'phone'));
                if (!empty($listUser)) {
                    foreach ($listUser AS $one) {
                        $LUser[$one['id']] = $one;
                    }
                    foreach ($listData as $key => $val) {
                        if (isset($LUser[(int)$val['from_user_id']])) {
                            $val->from_name = $LUser[(int)$val['from_user_id']]['fullname'];
                            $val->from_phone = $LUser[(int)$val['from_user_id']]['phone'];
                        }
                        if (isset($LAddress[$val->to_address_id])) {
                            $listData[$key]->to_address = $LAddress[$val->to_address_id]->address;
                            if (isset($LCity[$LAddress[$val->to_address_id]->city_id])) {
                                $listData[$key]->to_city = $LCity[$LAddress[$val->to_address_id]->city_id];
                            } else {
                                $listData[$key]->to_city = '';
                            }
                            if (isset($LDistrict[$LAddress[$val->to_address_id]->province_id])) {
                                $listData[$key]->to_district = $LDistrict[$LAddress[$val->to_address_id]->province_id];
                            } else {
                                $listData[$key]->to_district = '';
                            }
                        } else {
                            $listData[$key]->to_address = "";
                        }
                    }
                }
            }
            $response = [
                'error' =>  false,
                'data'  =>  $listData,
                'total' =>  $total
            ];
        } else {
            $response = [
                'error' =>  true,
                'message'   =>  'Không có dữ liệu'
            ];
        }
        return Response::json($response);
    }

    public function getExcel() {
        $UserInfo = $this->UserInfo();
        $groupStatus = Input::has('group_status') ? Input::get('group_status') : [];
        $status = Input::has('status') ? Input::get('status') : [];

        $fromDate     = (Input::has('from_date') && (int)Input::get('from_date')>0)  ? Input::get('from_date')        : 0;
        $toDate     = (Input::has('to_date') && (int)Input::get('to_date')>0)  ? Input::get('to_date')     :  0;
        $courier_id    = Input::has('courier_id')   ? (int)Input::get('courier_id')                     : 0;
        $scCode    = Input::has('sc_code')   ? Input::get('sc_code')                               : 0;
        $cityID    = Input::has('from_city')   ? (int)Input::get('from_city')                           : 0;
        $districtID    = Input::has('from_district')   ? (int)Input::get('from_district')                : 0;
        $key            =   Input::has('by_time')   ?   Input::get('by_time')   :   'time_accept';

        if(!empty($groupStatus))
        $status = array_merge(GroupOrderStatusModel::whereIn('group_status',$groupStatus)->lists('order_status_code'),$status);

        $OrderModel = OrdersModel::query();
        $OrderModel = $OrderModel->where('time_create','>=',$this->time()-90*86400);
        if(!empty($status)) {
            $OrderModel->whereIn('status',$status);
        }
        if($courier_id > 0){
            $OrderModel->where('courier_id',$courier_id);
        }
        if($fromDate > 0){
            $OrderModel->where($key,'>=',$fromDate);
        } else {
            $fromDate = 30*86400;
            $OrderModel->where($key,'>=',$fromDate);
        }
        if($toDate > 0){
            $OrderModel->where($key,'<=',$toDate);
        }
        if(!empty($scCode)) {
            $OrderModel->where('tracking_code',$scCode);
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

        $field = array('tracking_code','from_user_id','courier_tracking_code','service_id','courier_id','from_city_id','from_district_id','from_address','status','time_accept','time_create','time_pickup','time_success','estimate_delivery', 'to_address_id','to_name','to_phone');
        $listData   = $OrderModel->get($field);
        if(!$listData->isEmpty()) {
            $listUserId = array();
            foreach ($listData AS $one) {
                $listUserId[] = $one['from_user_id'];
            }
            if (Cache::has('courier_cache')) {
                $listCourier = Cache::get('courier_cache');
            } else {
                $courier = new CourierModel;
                $listCourier = $courier::all(array('id', 'name'));
            }
            if (!empty($listCourier)) {
                foreach ($listCourier as $val) {
                    $LCourier[$val['id']] = $val['name'];
                }
            }

            $listStatus = OrderStatusModel::all(array('code', 'name'));
            if ($listStatus) {
                foreach ($listStatus AS $one) {
                    $LStatus[(int)$one['code']] = $one['name'];
                }
            }
            $listCity = CityModel::all(array('city_name', 'id'));
            if (!empty($listCity)) {
                foreach ($listCity AS $one) {
                    $LCity[$one['id']] = $one['city_name'];
                }
            }
            $listDistrict = DistrictModel::all(array('district_name', 'id'));
            if (!empty($listDistrict)) {
                foreach ($listDistrict AS $one) {
                    $LDistrict[$one['id']] = $one['district_name'];
                }
            }


            //set meta info
            $listAddressID = array();
            foreach ($listData as $key => $val) {
                if (isset($LCity[(int)$val['from_city_id']])) {
                    $val->city_name = $LCity[(int)$val['from_city_id']];
                }

                if (isset($LDistrict[(int)$val['from_district_id']])) {
                    $val->district_name = $LDistrict[(int)$val['from_district_id']];
                }

                if (isset($LStatus[(int)$val['status']])) {
                    $val->status_name = $LStatus[(int)$val['status']];
                }
                if (isset($LCourier[(int)$val['courier_id']])) {
                    $val->courier_name = $LCourier[(int)$val['courier_id']];
                }

                $listAddressID[] = $val->to_address_id;
            }
            $listAddress = AddressModel::whereIn('id',$listAddressID)->get();
            $LAddress = [];
            if(!$listAddress->isEmpty()) {
                foreach($listAddress as $k => $val) {
                    $LAddress[$val->id] = $val;
                }
            }

            if (!empty($listUserId)) {
                $listUser = User::whereIn('id', $listUserId)->get(array('id', 'fullname', 'phone'));
                if (!empty($listUser)) {
                    foreach ($listUser AS $one) {
                        $LUser[$one['id']] = $one;
                    }
                    foreach ($listData as $key => $val) {
                        if (isset($LUser[(int)$val['from_user_id']])) {
                            $val->from_name = $LUser[(int)$val['from_user_id']]['fullname'];
                            $val->from_phone = $LUser[(int)$val['from_user_id']]['phone'];
                        }
                        if(isset($LAddress[$val->to_address_id])) {
                            $listData[$key]->to_address = $LAddress[$val->to_address_id]->address;
                            if(isset($LCity[$LAddress[$val->to_address_id]->city_id])) {
                                $listData[$key]->to_city = $LCity[$LAddress[$val->to_address_id]->city_id];
                            } else {
                                $listData[$key]->to_city = '';
                            }
                            if(isset($LDistrict[$LAddress[$val->to_address_id]->province_id])) {
                                $listData[$key]->to_district = $LDistrict[$LAddress[$val->to_address_id]->province_id];
                            } else {
                                $listData[$key]->to_district = '';
                            }
                        } else {
                            $listData[$key]->to_address = "";
                        }
                    }
                }
            }
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
                        'M'     => 30,
                        'N'     => 30,
                        'O'     => 30,
                        'P'     => 30,
                        'Q'     => 30,
                        'R'     => 30,
                        'S'     => 30
                    ));
                    // set content row
                    $sheet->row(3, array(
                        'STT', 'Mã vận đơn', 'Mã hãng vận chuyển', 'Hãng vận chuyển','Người gửi','Số điện thoại', 'Tỉnh Thành gửi', 'Quận huyện gửi', 'Địa chỉ gửi', 'Người nhận', 'Số điện thoại người nhận', 'Tỉnh thành nhận', 'Quận huyện nhận', 'Địa chỉ người nhận', 'Trạng thái', 'Thời gian tạo', 'Thời gian duyệt','Thời gian lấy hàng'
                    ));
                    $sheet->row(3,function($row){
                        $row->setBackground('#B6B8BA');
                        $row->setBorder('solid','solid','solid','solid');
                        $row->setFontSize(12);
                    });
                    //
                    $i = 1;
                    foreach ($listData AS $value) {
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
                            'Người nhận'      => $value['to_name'],
                            'Số điện thoại người nhận'  =>  $value['to_phone'],
                            'Tỉnh Thành nhận'  => $value['to_city'],
                            'Quận huyện nhận' => $value['to_district'],
                            'Địa chỉ nhận'    => $value['to_address'],
                            'Trạng thái' => $value['status_name'],
                            'Thời gian tạo' => date("d/M/y H:m",$value['time_create']),
                            'Thời gian duyệt' => date("d/M/y H:m",$value['time_accept']),
                            'Thời gian lấy hàng' => date("d/M/y H:m",$value['time_pickup'])
                        );
                        $sheet->appendRow($dataExport);
                    }
                });
            })->export('xls');
        } else {
            return "Không có dữ liệu";
        }
    }

    public function getStatus() {
        $groupStatus = Input::has('group_status') ? Input::get('group_status') : [];
        $status = Input::has('status') ? Input::get('status') : [];
        if(!empty($groupStatus)) {
            $status = array_merge(GroupOrderStatusModel::whereIn('group_status', $groupStatus)->lists('order_status_code'),$status);
        }

        if(!empty($status)) {
            $listOrderStatus = OrderStatusModel::whereIn('code',$status)->get();
            if(!$listOrderStatus->isEmpty()) {
                $response = [
                    'error' =>  false,
                    'data'  =>  $listOrderStatus
                ];
                return Response::json($response);
            }
        }
        $response = [
            'error' =>  true,
            'message'   =>  'Không có dữ liệu'
        ];
        return Response::json($response);
    }
}