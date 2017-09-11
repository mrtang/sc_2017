<?php
use ordermodel\OrdersModel;
use metadatamodel\GroupStatusModel;
use Illuminate\Support\Facades\Response;
use metadatamodel\OrderStatusModel;
use sellermodel\UserInventoryModel;
use Maatwebsite\Excel\Facades\Excel;
use metadatamodel\GroupOrderStatusModel;
use trigger\CourierAcceptJourney;
use ticketmodel\FeedbackModel;
use ticketmodel\ReferModel;
use ticketmodel\RequestModel;

class SearchController extends BaseController {

    public function getSearch() {
        $keyword        =   Input::has('keyword')           ? trim(Input::get('keyword'))           :   '';
        $type           =   Input::has('type')              ? trim(Input::get('type'))              :   0;
        $currentPage    =   Input::has('currentPage')       ? trim(Input::get('currentPage'))       :   1;
        $itemPerPage    =   Input::has('item_page')         ? trim(Input::get('item_page'))         :   20;
        $fromDate       =   Input::has('from_date')         ? (int)Input::get('from_date')         :   0;
        $toDate         =   Input::has('to_date')           ? (int)Input::get('to_date')         :   0;
        $status         =   Input::has('status')            ? Input::get('status')         :   'ALL';
        $UserInfo       = $this->UserInfo();
        if($UserInfo['privilege'] == 0){
            $response = array(
                'error'         => true,
                'message'       => 'Không có quyền !',
                'privilege'     => 0
            );
        }
        if(empty($keyword)) {
            $response = array(
                'error' =>  true,
                'message'   =>  'Bạn cần nhập từ khóa',
                'privilege' =>  $UserInfo['privilege']
            );
        }
        if(isset($response)) {
            return Response::json($response);
        }

        $orderModel = OrdersModel::query();
        if($fromDate==0) {
            $fromDate = $this->time() - 90*86400;
        }
        $orderModel->where('time_create', ">=", $fromDate);
        $orderModel->where('time_create', "<=", $toDate);
        //search
        if($type==1) {
            if(filter_var($keyword,FILTER_VALIDATE_EMAIL)) {
                $fromUserID = User::where('email',$keyword)->pluck('id');
                $orderModel->where('from_user_id',$fromUserID);
            } else if(filter_var((int)$keyword,FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))) {
                $fromUserID = User::where('phone',$keyword)->pluck('id');
                $orderModel->where('from_user_id',$fromUserID);
            } else {
                $orderModel->where('tracking_code',$keyword);
            }
        } else if($type==2) {
            if(filter_var($keyword,FILTER_VALIDATE_EMAIL)) {
                $orderModel->where('to_email',$keyword);
            } else if(filter_var((int)$keyword,FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))) {
                $orderModel->where('to_phone',$keyword);
            } else {
                $orderModel->where('tracking_code',$keyword);
            }
        } else if($type==3) {
            $orderModel->where('domain',$keyword);
        } else if($type == 4) {
            $orderModel->where('courier_tracking_code',$keyword);
        } else {
            $orderModel->where('tracking_code',$keyword);
        }
        $totalModel = clone $orderModel;
        $listGroupStatus = GroupStatusModel::where('group',3)->lists('id');
        $listStatusByGroup = GroupOrderStatusModel::whereIn('group_status',$listGroupStatus)->lists('order_status_code');
        if($status != "ALL") {
            $listStatus = GroupOrderStatusModel::where('group_status',$status)->lists('order_status_code');
        } else {
            $listStatus = $listStatusByGroup;
        }
        $totalModel->whereIn('status',$listStatusByGroup);
        $orderModel->whereIn('status',$listStatus);

        $data = $orderModel->with(
            array(
                'MetaStatus'   => function($query){
                    $query->with(array('group_order_status'));
                },
                'Service',
                'Courier'))->take($itemPerPage)->skip($itemPerPage*($currentPage-1))->orderBy('time_create','desc')->get();


        //merge order detail + address
        if(!$data->isEmpty()) {
            $listOrderID = $listAddressID = $listFromAddressID = array();
            $listCity = $listDistrict = $listWard = array();
            foreach($data as $var) {
                $listOrderID[] = $var->id;
                $listAddressID[] = $var->to_address_id;
                $listFromAddressID[] = $var->from_address_id;
                $listCity[] = $var->from_city_id;
                $listDistrict[] = $var->from_district_id;
                $listWard[] = $var->from_ward_id;
            }
            $listOrderDetail = \ordermodel\DetailModel::whereIn('order_id',$listOrderID)->get();
            $listAddress = \ordermodel\AddressModel::whereIn('id',$listAddressID)->get();
            $Inventory = UserInventoryModel::whereIn('id',$listFromAddressID)->get();

            $ListInventory = [];
            if(!$Inventory->isEmpty()) {
                foreach($Inventory as $OneInventory) {
                    $ListInventory[$OneInventory->id] = $OneInventory;
                }
            }
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

            if(!$listOrderDetail->isEmpty()) {
                foreach($data as $k => $var) {
                    //merge order detail
                    foreach($listOrderDetail as $detail) {
                        if($detail->order_id==$var->id) {
                            $data[$k]->order_detail = $detail;
                        }
                    }

                    //merge order address
                    foreach($listAddress as $address) {
                        if($address->id == $var->to_address_id) {
                            $data[$k]->to_address = $address->address. ((isset($addressArr[$address->city_id]) && isset($addressArr[$address->city_id][$address->province_id]) && isset($addressArr[$address->city_id][$address->province_id][$address->ward_id])) ? ' - '.$addressArr[$address->city_id][$address->province_id][$address->ward_id] : "");
                        }
                    }
                    $data[$k]->from_address = $var->from_address. ((isset($addressArr[$var->from_city_id]) && isset($addressArr[$var->from_city_id][$var->from_district_id]) && isset($addressArr[$var->from_city_id][$var->from_district_id][$var->from_ward_id])) ? ' - '.$addressArr[$var->from_city_id][$var->from_district_id][$var->from_ward_id] : "");

                    if(isset($ListInventory[$var->from_address_id])) {
                        $data[$k]->inventory = $ListInventory[$var->from_address_id];
                    }
                }
            }
        }

        $totalOrder = $totalModel->groupBy('status')->get([
            'status',
            \Illuminate\Support\Facades\DB::raw('COUNT(*) as total')
        ]);
        $status_group = [];
        $totalAll = 0;
        
        $listGroupStatus = GroupStatusModel::where('group',3)->lists('id');
        $listMapStatus = GroupOrderStatusModel::whereIn('group_status',$listGroupStatus)->get();
        $listStatusByGroup = [];
        if(!$listMapStatus->isEmpty()) {
            foreach ($listMapStatus as $oneMapStatus) {
                $listStatusByGroup[$oneMapStatus->order_status_code] = $oneMapStatus->group_status;
            }
        }
        foreach($totalOrder as $oneOrder) {
            if(!isset($status_group[$listStatusByGroup[$oneOrder->status]])) {

                $status_group[$listStatusByGroup[$oneOrder->status]] = $oneOrder->total;
            } else {
                $status_group[$listStatusByGroup[$oneOrder->status]] += $oneOrder->total;
            }
            $totalAll += $oneOrder->total;
        }

        if(isset($fromUserID)) {
            $userID = $fromUserID;
        } else if(!$data->isEmpty()) {
            $userID = $data[0]->from_user_id;
        } else {

        }

        $count = $this->countSearch();
        $response = array(
            "error" =>  false,
            "message"   =>  "success",
            "data"      =>  $data,
            "user_id"   =>  isset($userID) ? $userID : 0,
            "last_activity" =>  $count->last_activity,
            "total_all" =>  $count->total,
            'total_status'  =>  $status_group,
            'total'         =>  $totalAll,
            'item_page'     => $itemPerPage,
            'privilege'     => $UserInfo['privilege']
        );
        return Response::json($response);
    }

    public function countSearch() {
        $keyword        =   Input::has('keyword')           ? trim(Input::get('keyword'))           :   '';
        $type           =   Input::has('type')              ? trim(Input::get('type'))              :   0;
        $fromDate    =   Input::has('from_date')         ? (int)Input::get('from_date')         :   0;
        $toDate    =   Input::has('to_date')         ? (int)Input::get('to_date')         :   0;
        $status    =   Input::has('status')         ? Input::get('status')         :   'ALL';

        $orderModel = OrdersModel::query();

        $orderModel->select(DB::raw("COUNT(id) as total, MAX(time_create) as last_activity"));

        if($fromDate==0) {
            $fromDate = $this->time() - 90*86400;
        }
        $orderModel->where('time_create', ">=", $fromDate);
        $orderModel->where('time_create', "<=", $toDate);

        //search
        $orderModel->where(function($query) use ($keyword,$type) {
            if($type==1) {
                if(filter_var($keyword,FILTER_VALIDATE_EMAIL)) {
                    $fromUserID = User::where('email',$keyword)->pluck('id');
                    $query->where('from_user_id',$fromUserID);
                } else if(filter_var((int)$keyword,FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))) {
                    $fromUserID = User::where('phone',$keyword)->pluck('id');
                    $query->where('from_user_id',$fromUserID);
                } else {
                    $query->where('tracking_code',$keyword);
                }
            } else if($type==2) {
                if(filter_var($keyword,FILTER_VALIDATE_EMAIL)) {
                    $query->where('to_email',$keyword);
                } else if(filter_var((int)$keyword,FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))) {
                    $query->where('to_phone',$keyword);
                } else {
                    $query->where('tracking_code',$keyword);
                }
            } else if($type==3) {
                $query->where('domain',$keyword);
            } else if($type == 4) {
                $query->where('courier_tracking_code',$keyword);
            } else {
                $query->where('tracking_code',$keyword);
            }
        });
        if($status != "ALL") {
            $listStatus = GroupOrderStatusModel::where('group_status',$status)->lists('order_status_code');
            $orderModel->whereIn('status',$listStatus);
        } else {
            $listGroupStatus = GroupStatusModel::where('group',3)->lists('id');
            $listStatus = GroupOrderStatusModel::whereIn('group_status',$listGroupStatus)->lists('order_status_code');
            $orderModel->whereIn('status',$listStatus);
        }
        return $orderModel->with(
            array(
                'MetaStatus'   => function($query){
                    $query->with(array('group_order_status'));
                },
                'Service',
                'Courier'))->first();
    }

    private function mapAddress($cityArr,$districtArr,$wardArr) {
        $cities = CityModel::whereIn('id',$cityArr)->get();
        $data = array();
        if(!$cities->isEmpty()) {
            foreach($cities as $ck => $city) {
                $districts = DistrictModel::whereIn('id',$districtArr)->where('city_id',$city->id)->get();
                $data[$city->id][0][0] = $city->city_name;

                if(!$districts->isEmpty()) {
                    foreach($districts as $dk=> $district) {
                        $data[$city->id][$district->id][0] = $district->district_name. ', ' .$city->city_name;
                        $wards = WardModel::whereIn('id',$wardArr)->where('city_id',$city->id)->where('district_id',$district->id)->get();
                        if(!$wards->isEmpty()) {
                            foreach($wards as $wk => $ward) {
                                $data[$city->id][$district->id][$ward->id] = $ward->ward_name. ', ' .$district->district_name. ', ' .$city->city_name;
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }

    public function getUpdateStatus() {
        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege'] == 0){
            $response = array(
                'error'         => true,
                'message'       => 'Không có quyền !',
                'privilege'     => 0
            );
            return Response::json($response);
        }
        $orderID = Input::has('order_id') ? (int)Input::get('order_id') : 0;
        $status= Input::has('status') ? (int)Input::get('status') : 0;


        $listStatus = array(70,71);
        $newApceptStatus = array(52,53,66);
        if($orderID>0 && in_array($status,$newApceptStatus)) {
            $timeStart = $this->time() - 90*86400;

            $order = OrdersModel::where('id',$orderID)->whereIn('status',$listStatus)->where('verify_id',0)->where('time_create','>=',$timeStart)->first();


            if(!empty($order)) {
                $currentTime = $this->time();
                $orderStatus =  new ordermodel\StatusModel;
                $orderStatus->city_name = "SC";
                $orderStatus->note = "Quản trị viên ".$UserInfo['fullname']. " đã cập nhật trạng thái vận đơn.";
                $orderStatus->order_id =$orderID;
                $orderStatus->status = $status;
                $orderStatus->time_create =
                $orderStatus->save();
                if($orderStatus->exists) {

                    $orderModel = OrdersModel::find($orderID);
                    $orderModel->status = $status;
                    $orderModel->time_update = $currentTime;
                    $orderModel->time_success = $currentTime;
                    $orderModel->save();

                    $LMongo = new \LMongo\Facades\LMongo;

                    $idLog  = $LMongo::collection('log_update_order')
                        ->insert(array(
                            'tracking_code' => $order->tracking_code,
                            'order_id'         => $orderID,
                            'old_status'         => $order->status,
                            'new_status'         => $status,
                            'user_id'   =>  $UserInfo['id'],
                            'time_create'   => $this->time(),
                        ));
                }
                return Response::json([
                    'error' =>  false,
                    'message'   =>  'Cập nhật trạng thái thành công'
                ]);
            }
        }
        return Response::json([
            'error' =>  true,
            'message'   =>  'Cập nhật trạng thái thật bại'
        ]);
    }

    public function getListStatus() {
        $status = OrderStatusModel::all();
        return Response::json($status);
    }

    public function getExportExcel()
    {
        $keyword        =   Input::has('keyword')           ? trim(Input::get('keyword'))           :   '';
        $type           =   Input::has('type')              ? trim(Input::get('type'))              :   0;
        $fromDate    =   Input::has('from_date')         ? (int)Input::get('from_date')         :   0;
        $toDate    =   Input::has('to_date')         ? (int)Input::get('to_date')         :   0;
        $status    =   Input::has('status')         ? Input::get('status')         :   'ALL';
        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege'] == 0){
            $response = array(
                'error'         => true,
                'message'       => 'Không có quyền !',
                'privilege'     => 0
            );
        }
        if(empty($keyword)) {
            $response = array(
                'error' =>  true,
                'message'   =>  'Bạn cần nhập từ khóa',
                'privilege' =>  $UserInfo['privilege']
            );
        }
        if(isset($response)) {
            return Response::json($response);
        }

        $orderModel = OrdersModel::query();
        if($fromDate==0) {
            $fromDate = $this->time() - 90*86400;
        }
        $orderModel->where('time_create', ">=", $fromDate);
        $orderModel->where('time_create', "<=", $toDate);
        //search
        if($type==1) {
            if(filter_var($keyword,FILTER_VALIDATE_EMAIL)) {
                $fromUserID = User::where('email',$keyword)->pluck('id');
                $orderModel->where('from_user_id',$fromUserID);
            } else if(filter_var((int)$keyword,FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))) {
                $fromUserID = User::where('phone',$keyword)->pluck('id');
                $orderModel->where('from_user_id',$fromUserID);
            } else {
                $orderModel->where('tracking_code',$keyword);
            }
        } else if($type==2) {
            if(filter_var($keyword,FILTER_VALIDATE_EMAIL)) {
                $orderModel->where('to_email',$keyword);
            } else if(filter_var((int)$keyword,FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))) {
                $orderModel->where('to_phone',$keyword);
            } else {
                $orderModel->where('tracking_code',$keyword);
            }
        } else if($type==3) {
            $orderModel->where('domain',$keyword);
        } else if($type == 4) {
            $orderModel->where('courier_tracking_code',$keyword);
        } else {
            $orderModel->where('tracking_code',$keyword);
        }
        if($status != "ALL") {
            $listStatus = GroupOrderStatusModel::where('group_status',$status)->lists('order_status_code');
            $orderModel->whereIn('status',$listStatus);
        } else {
            $listGroupStatus = GroupStatusModel::where('group',3)->lists('id');
            $listStatus = GroupOrderStatusModel::whereIn('group_status',$listGroupStatus)->lists('order_status_code');
        }
        $orderModel->whereIn('status',$listStatus);

        $data = $orderModel->with(
            array(
                'MetaStatus'   => function($query){
                    $query->with(array('group_order_status'));
                },
                'Service',
                'Courier'))->orderBy('time_create','desc')->get();



        //merge order detail + address
        if(!$data->isEmpty()) {
            $listUserID = $listOrderID = $listAddressID = $listFromAddressID = array();
            $listCity = $listDistrict = $listWard = array();
            foreach($data as $var) {
                $listUserID[] = $var->from_user_id;
                $listOrderID[] = $var->id;
                $listAddressID[] = $var->to_address_id;
                $listFromAddressID[] = $var->from_address_id;
                $listCity[] = $var->from_city_id;
                $listDistrict[] = $var->from_district_id;
                $listWard[] = $var->from_ward_id;
            }
            $listOrderDetail = \ordermodel\DetailModel::whereIn('order_id',$listOrderID)->get();
            $listAddress = \ordermodel\AddressModel::whereIn('id',$listAddressID)->get();
            $Inventory = UserInventoryModel::whereIn('id',$listFromAddressID)->get();
            $listUser = User::whereIn('id',$listUserID)->get();

            $ListInventory = [];
            if(!$Inventory->isEmpty()) {
                foreach($Inventory as $OneInventory) {
                    $ListInventory[$OneInventory->id] = $OneInventory;
                }
            }
            $listUserArr = [];
            if(!$listUser->isEmpty()) {
                foreach($listUser as $oneUser) {
                    $listUserArr[$oneUser->id] = $oneUser;
                }
            }
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



            if(!$listOrderDetail->isEmpty()) {

                $LCity = $LDistrict = [];


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
                foreach($data as $k => $var) {
                    //merge order detail
                    foreach($listOrderDetail as $detail) {
                        if($detail->order_id==$var->id) {
                            $data[$k]->order_detail = $detail;
                        }
                    }
                    $to_city = $to_district = '';
                    //merge order address
                    foreach($listAddress as $address) {
                        if($address->id == $var->to_address_id) {
                            $data[$k]->to_address = $address->address;
                            $to_city = $address->city_id;
                            $to_district = $address->province_id;
                        }
                    }
                    $data[$k]->from_user = $listUserArr[$var->from_user_id];
                    if(isset($ListInventory[$var->from_address_id])) {
                        $data[$k]->inventory = $ListInventory[$var->from_address_id];
                    }

                    $data[$k]->from_city = isset($LCity[$var->from_city_id]) ? $LCity[$var->from_city_id] : '';
                    $data[$k]->from_district = isset($LDistrict[$var->from_district_id]) ? $LDistrict[$var->from_district_id] : '';
                    $data[$k]->to_city = isset($LCity[$to_city]) ?  $LCity[$to_city] : '';
                    $data[$k]->to_district = isset($LDistrict[$to_district]) ? $LDistrict[$to_district] : '';
                }
            }
            //xuat du lieu ra excel
            return Excel::create('Van_don', function ($excel) use($data) {
                $excel->sheet('Vận đơn', function ($sheet) use($data) {
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
                    foreach ($data AS $value) {
                        $dataExport = array(
                            'STT' => $i++,
                            'Mã vận đơn' => $value->tracking_code,
                            'Mã hãng vận chuyển' => $value->courier_tracking_code,
                            'Hãng vận chuyển' => $value->courier->name,
                            'Người gửi' => $value->from_user->fullname,
                            'Số điện thoại người gửi' => $value->from_user->phone,
                            'Tỉnh Thành gửi'  => $value->from_city,
                            'Quận huyện gửi' => $value->from_district,
                            'Địa chỉ gửi'    => isset($value->inventory->address) ? $value->inventory->address : '',
                            'Người nhận'      => $value->to_name,
                            'Số điện thoại người nhận'  =>  $value->to_phone,
                            'Tỉnh Thành nhận'  => $value->to_city,
                            'Quận huyện nhận' => $value->to_district,
                            'Địa chỉ nhận'    => $value->to_address,
                            'Trạng thái' => $value->MetaStatus->name,
                            'Thời gian tạo' => date("d/M/y H:m",$value->time_create),
                            'Thời gian duyệt' => date("d/M/y H:m",$value->time_accept),
                            'Thời gian lấy hàng' => date("d/M/y H:m",$value->time_pickup)
                        );
                        $sheet->appendRow($dataExport);
                    }
                });
            })->export('xls');
        } else {
            return Response::json([
                'error' =>  true,
                'message'   =>  'Không có dữ liệu',
            ]);
        }
    }

    public function postUpdateStatus() {
        $ctrl = new CourierAcceptJourney;
        $response = $ctrl->postAcceptstatus();
        return $response;

    }
}