<?php
namespace oms;

use Response;
use Exception;
use Input;
use Validator;
use Excel;
use DB;

use ordermodel\OrdersModel;
use metadatamodel\GroupOrderStatusModel;
use sellermodel\UserInfoModel;
use sellermodel\UserInventoryModel;
use omsmodel\PipeStatusModel;
use omsmodel\PipeJourneyModel;
use omsmodel\CustomerAdminModel;

use omsmodel\TasksModel;
use omsmodel\TasksReferModel;

use WardModel;

use User;

class InventoryCtrl extends \BaseController
{
    private $error              = true;
    private $message            = 'error';
    private $data               = [];
    private $group_order        = [];

    private function getModel(){
        $Model              = new UserInventoryModel;
        $PipeJourneyModel   = new PipeJourneyModel;

        $TimeCreateStart    = Input::has('create_start')        ? (int)Input::get('create_start')           : 0; // time_create start   time_stamp
        $TimeCreateEnd      = Input::has('create_end')          ? (int)Input::get('create_end')             : 0; // time_create end

        $Domain             = Input::has('domain')              ? (int)Input::get('domain')                 : 0;
        $KeyWord            = Input::has('keyword')             ? trim(Input::get('keyword'))               : 0;

        $CityId             = Input::has('city_id')             ? (int)Input::get('city_id')                : 0;
        $CityId             = Input::has('city_id')             ? (int)Input::get('city_id')                : 0;
        $DistrictId         = Input::has('district_id')         ? (int)Input::get('district_id')            : 0;

        $Vip                = Input::has('vip')                 ? (int)Input::get('vip')                    : 0;

        $Tab                = Input::has('tab')                 ? trim(Input::get('tab'))                   : 'ALL';

        $CountryId          = Input::has('country_id')          ? (int)Input::get('country_id')            : 237;

        $Model              = $Model::where('country_id', $CountryId);

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
        }

        if(!empty($ListUser)){
            $Model  = $Model->whereIn('user_id', $ListUser);
        }

        if(!empty($Domain)){
            $Model          = $Model->where('sys_name',$Domain);
        }

        if(!empty($TimeCreateStart)){
            $Model              = $Model->where('time_create','>=',$TimeCreateStart);
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',$TimeCreateStart);
        }else{
            $Model              = $Model->where('time_create','>=',strtotime(date('Y-m-1 00:00:00')));
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','>=',strtotime(date('Y-m-1 00:00:00')));
        }

        if(!empty($TimeCreateEnd)){
            $Model              = $Model->where('time_create','<=',$TimeCreateEnd);
            $PipeJourneyModel   = $PipeJourneyModel->where('time_create','<=',$TimeCreateEnd);
        }

        if(!empty($DistrictId)){
            $Model          = $Model->where('province_id',$DistrictId);
        }else{
            if(!empty($CityId)){
                $Model          = $Model->where('city_id',$CityId);
            }
        }

        if($Tab != 'ALL'){
            $ListId = [];
            $ListId = $PipeJourneyModel->where('type', 3)->where('pipe_status', (int)$Tab)->lists('tracking_code');

            if(!empty($ListId)){
                $ListId = array_unique($ListId);
                $Model  = $Model->whereIn('id',$ListId);
            }else{
                $this->error = true;
                return;
            }
        }

        return $Model;
    }

    /*
     * get list order
     */
    public function getIndex(){
        //Check Quyền  Quản lý kho hàng
        if(!$this->check_privilege('PRIVILEGE_PICKUP_ADDRESS','view')){
            return $this->ResponseData();
        }

        $itemPage           = 20;
        $this->error        = false;
        $this->message      = 'success';

        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : '';
        $TimeCreateStart    = Input::has('create_start')        ? (int)Input::get('create_start')           : $this->time() - 86400*30;

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
            return $this->ExportExcel($Model->get()->ToArray());
        }

        /*
         * count
         */

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model       = $Model->skip($offset)->take($itemPage);

            if(!empty($Group)){
                $ListId = [];
                $PipeStatusModel    = new PipeStatusModel;
                $ListId = $PipeStatusModel->getPipe($Group, 3);
                if(!empty($ListId))
                $Model  = $Model->with(['pipe_journey' => function($query) use($ListId){
                    $query->whereIn('pipe_status', $ListId)->orderBy('time_create', 'ASC');
                }]);
            }

            $Data       = $Model->with(['district','City','ward','user'])->orderBy('time_create','DESC')->get()->toArray();
            if(!empty($Data)){
                $ListId         = [];
                foreach($Data as $key => $val){
                    $ListId[]   = (int)$val['id'];

                    $Data[$key]['pipe_status'] = 0;
                    if(!empty($val['pipe_journey'])){
                        foreach($val['pipe_journey'] as $v){
                            $Data[$key]['pipe_status'] = (int)$v['pipe_status'];
                        }
                    }
                }

                $OrderModel = new OrdersModel;
                $GroupOrder = $OrderModel->where('time_create','>=', $TimeCreateStart)
                                         ->whereIn('from_address_id', $ListId)
                                         ->whereIn('status',[30,35,38])
                                         ->groupBy('from_address_id')
                                         ->get(['from_address_id', DB::raw('count(*) as total, sum(total_weight) as total_weight')]);

                if(!empty($GroupOrder)){
                    foreach($GroupOrder as $val){
                        $this->group_order[$val['from_address_id']] = $val;
                    }
                }

                $ListRefer = TasksReferModel::where('type', '2')
                                ->whereIn('refer_id', $ListId)
                                ->with(['task'=> function ($query){
                                    return $query->where('state', '!=', 'SUCCESS');
                                }])
                                ->get()->toArray();

                if(!empty($ListRefer)){
                    foreach ($Data as $key => $value) {
                        foreach ($ListRefer as $k => $refer) {
                            if($refer['refer_id'] == $value['id'] && is_array($refer['task'])){
                                $Data[$key]['task'] = $refer['task'];
                            }
                        }
                    }
                }


                $this->data = $Data;
            }
        }

        return $this->ResponseData();
    }

    private function ResponseData(){
        return Response::json([
            'error'         => $this->error,
            'message'       => $this->message,
            'total'         => $this->total,
            'data'          => $this->data,
            'group'         => $this->group_order
        ]);
    }

    public function ExportExcel($Data){
        $FileName   = 'Danh_sach_kho_hang';

        $City       = [];
        $District   = [];
        $Ward       = [];
        $User       = [];
        $ListUserId = [];

        if(!empty($Data)){
            $City       = $this->getCity();

            foreach($Data as $val){
                $ListDistrictId[]   = $val['province_id'];
                $ListWardId[]       = $val['ward_id'];
                $ListUserId[]       = $val['user_id'];
            }

            $ListDistrictId = array_unique($ListDistrictId);
            $ListWardId     = array_unique($ListWardId);
            $ListUserId     = array_unique($ListUserId);

            if(!empty($ListDistrictId)){
                $District   = $this->getProvince($ListDistrictId);
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

            if(!empty($ListUserId)){
                $UserModel  = new \User;
                $ListUser   = $UserModel->whereRaw("id in (". implode(",", $ListUserId) .")")->get(['id','fullname', 'phone', 'email'])->toArray();

                if(!empty($ListUser)){
                    foreach($ListUser as $val){
                        $User[$val['id']]   = $val;
                    }
                }
            }


        }

        return Excel::create($FileName, function($excel) use($Data, $City, $District, $Ward, $User){
            $excel->sheet('Sheet1', function($sheet) use($Data, $City, $District, $Ward, $User){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách kho hàng'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,'H'     =>  30,
                    'I'  => 30, 'J'  => 30, 'K' => 30
                ));

                $sheet->row(3, array(
                    'STT', 'Thời gian','Họ tên', 'Email', 'Tên kho hàng', 'Người liên hệ', 'Số điện thoại', 'Tỉnh thành', 'Quận huyện', 'Phường xã',
                    'Địa chỉ', 'Trạng thái'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });
                $sheet->setBorder('A3:K3', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $Status = ($val['delete'] == 1) ? 'Đã xóa' : (($val['active'] == 1) ? 'Đang sử dụng' : 'Ngừng sử dụng');
                    $dataExport = array(
                        $i++,
                        $val['time_create'] > 0 ? date("d/m/y H:m",$val['time_create']) : '',
                        isset($User[(int)$val['user_id']]) ? $User[(int)$val['user_id']]['email'] : '',
                        isset($User[(int)$val['user_id']]) ? $User[(int)$val['user_id']]['fullname'] : '',

                        $val['name'],
                        $val['user_name'],
                        $val['phone'].' ',

                        ($val['city_id'] > 0 && isset($City[(int)$val['city_id']])) ? $City[(int)$val['city_id']] : '',
                        ($val['province_id'] > 0 && isset($District[(int)$val['province_id']])) ? $District[(int)$val['province_id']] : '',
                        ($val['ward_id'] > 0 && isset($Ward[(int)$val['ward_id']])) ? $Ward[(int)$val['ward_id']] : '',
                        $val['address'],
                        $Status
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }


    public function getExportCustomerByOrderAccept(){
        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')           : 0; // time_create start   time_stamp
        $TimeAcceptEnd      = Input::has('accept_end') && (int)Input::get('accept_end') >0          ? (int)Input::get('accept_end')             : $this->time(); // time_create end

        $FileName   = 'Danh_sach_khach_hang';

        if(empty($TimeAcceptStart)){
            $this->_error_message = "No record foúnd1";
            goto done;
        }

        $Model  = new CustomerAdminModel;
        $Data   = $Model->where('first_accept_order_time', '>=', $TimeAcceptStart)->where('first_accept_order_time', '<=', $TimeAcceptEnd)->get()->toArray();

        $ListUserID         = [];
        $ListTrackingCode   = [];
        foreach ($Data as $key => $value) {
            $ListUserID[]       = $value['user_id'];
            $ListTrackingCode[] = $value['first_tracking_code'];
        }

        $ListTrackingCode = array_unique($ListTrackingCode);



        if(empty($ListUserID)){
            $this->_error_message = $Data;
            goto done;
        }


        $OrderModel = new OrdersModel;
        $OrderModel = $OrderModel->where('time_accept', '>=', $TimeAcceptStart)->whereIn('tracking_code', $ListTrackingCode)->get()->toArray();

        $Orders = [];
        foreach ($OrderModel as $key => $value) {
            $Orders[$value['from_user_id']] = $value;
            $ListDistrictId[] = $value['from_district_id'];
        }

        $Users = User::whereIn('id', $ListUserID)->get()->toArray();


        $City       = $this->getCity();
        $District   = [];
        


        

        $ListDistrictId = array_unique($ListDistrictId);
        $District       = $this->getProvince($ListDistrictId);



        return Excel::create($FileName, function($excel) use($City, $District, $Users, $Orders){
            $excel->sheet('Sheet1', function($sheet) use($City, $District, $Users, $Orders){

                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });

                $sheet->row(1, array('','','','','Danh sách khách hàng'));

                $sheet->setWidth(array(
                    'A'  =>  10, 'B'  => 30, 'C' => 30, 'D' => 30, 'E' => 30, 'F' =>  30, 'G' =>  30,'H'     =>  30,
                    'I'  => 30,  'J'  => 30, 'K' => 30
                ));

                $sheet->row(3, array(
                    'STT', 'Thoi gian tao tai khoan','Họ tên', 'Email', 'Số điện thoại', 'Tỉnh thành', 'Quận huyện', 'Địa chỉ'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });
                $sheet->setBorder('A3:K3', 'thin');

                $i = 1;
                foreach ($Users as $val) {
                    $dataExport = array(
                        $i++,
                        $val['time_create'] > 0 ? date("d/m/y H:m",$val['time_create']) : '',
                        $val['fullname'],
                        $val['email'],
                        $val['phone'],
                        isset($Orders[$val['id']]) && $Orders[$val['id']]['from_city_id'] > 0 ? $City[$Orders[$val['id']]['from_city_id']] : "",
                        isset($Orders[$val['id']]) && $Orders[$val['id']]['from_district_id'] > 0 ? $District[$Orders[$val['id']]['from_district_id']] : "",
                        isset($Orders[$val['id']]) ? $Orders[$val['id']]['from_address'] : "",
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');


        done: 
        return $this->_ResponseData();


    }

    public function ExportExcelInDay($Data , $TotalGroupPick, $TotalGroupPicked, $TotalGroupPickFail){
        $FileName   = 'Danh_sach_kho_hang_trong_ngay';

        $City       = [];
        $District   = [];
        $Ward       = [];
        $User       = [];
        $ListUserId = [];

        if(!empty($Data)){
            $City       = $this->getCity();

            foreach($Data as $val){
                $ListDistrictId[]   = $val['province_id'];
                $ListWardId[]       = $val['ward_id'];
                $ListUserId[]       = $val['user_id'];
            }

            $ListDistrictId = array_unique($ListDistrictId);
            $ListWardId     = array_unique($ListWardId);
            $ListUserId     = array_unique($ListUserId);

            if(!empty($ListDistrictId)){
                $District   = $this->getProvince($ListDistrictId);
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

            if(!empty($ListUserId)){
                $UserModel  = new \User;
                $ListUser   = $UserModel->whereRaw("id in (". implode(",", $ListUserId) .")")->get(['id','fullname', 'phone', 'email'])->toArray();

                if(!empty($ListUser)){
                    foreach($ListUser as $val){
                        $User[$val['id']]   = $val;
                    }
                }
            }


        }

        return Excel::create($FileName, function($excel) use($Data, $City, $District, $Ward, $User, $TotalGroupPick, $TotalGroupPicked, $TotalGroupPickFail){
            $excel->sheet('Sheet1', function($sheet) use($Data, $City, $District, $Ward, $User, $TotalGroupPick, $TotalGroupPicked, $TotalGroupPickFail){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách kho hàng'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,'H'     =>  30,
                    'I'  => 30, 'J'  => 30, 'K' => 30
                ));

                $sheet->row(3, array(
                    'STT', /*'Thời gian',*/'Họ tên', 'Email', 'Tên kho hàng', 'Người liên hệ', 'Số điện thoại', 'Tỉnh thành', 'Quận huyện', 'Phường xã',
                    'Địa chỉ', 'Trạng thái' , 'Số đơn cần lấy', 'Số đơn lấy thành công', 'Số đơn lấy thất bại'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });
                $sheet->setBorder('A3:K3', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $Status = ($val['delete'] == 1) ? 'Đã xóa' : (($val['active'] == 1) ? 'Đang sử dụng' : 'Ngừng sử dụng');
                    $dataExport = array(
                        $i++,
                        //$val['time_create'] > 0 ? date("d/m/y H:m",$val['time_create']) : '',
                        isset($User[(int)$val['user_id']]) ? $User[(int)$val['user_id']]['email'] : '',
                        isset($User[(int)$val['user_id']]) ? $User[(int)$val['user_id']]['fullname'] : '',

                        $val['name'],
                        $val['user_name'],
                        $val['phone'].' ',

                        ($val['city_id'] > 0 && isset($City[(int)$val['city_id']])) ? $City[(int)$val['city_id']] : '',
                        ($val['province_id'] > 0 && isset($District[(int)$val['province_id']])) ? $District[(int)$val['province_id']] : '',
                        ($val['ward_id'] > 0 && isset($Ward[(int)$val['ward_id']])) ? $Ward[(int)$val['ward_id']] : '',
                        $val['address'],
                        $Status,
                        (!empty($TotalGroupPick[$val['id']]))       ? $TotalGroupPick[$val['id']] : 0,
                        (!empty($TotalGroupPicked[$val['id']]))     ? $TotalGroupPicked[$val['id']] : 0,
                        (!empty($TotalGroupPickFail[$val['id']]))   ? $TotalGroupPickFail[$val['id']] : 0

                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }


    public function getAddressInDay(){
        //Check Quyền  Quản lý kho hàng
        if(!$this->check_privilege('PRIVILEGE_PICKUP_ADDRESS','view')){
            return Response::json([
                'error'                 => false,
                'error_message'         => "Thành công",
                'data'                  => [],
                'total_group'           => [],
                'total_group_picked'    => [],
                'total_group_pick_fail' => [],
                'total'                 => 0
            ]);
        }


        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $TimeCreateStart    = Input::has('create_start')        ? (int)Input::get('create_start')           : 0; // time_create start   time_stamp
        $TimeCreateEnd      = Input::has('create_end')          ? (int)Input::get('create_end')             : 0; // time_create end
        $Domain             = Input::has('domain')              ? (int)Input::get('domain')                 : 0;
        $KeyWord            = Input::has('keyword')             ? trim(Input::get('keyword'))               : 0;
        $CityId             = Input::has('city_id')             ? (int)Input::get('city_id')                : 0;
        $DistrictId         = Input::has('district_id')         ? (int)Input::get('district_id')            : 0;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';

        $CountryId          = Input::has('country_id')          ? (int)Input::get('country_id')            : 237;

        $itemPage           = 20;
        $offset             = ($page - 1)*$itemPage;

        $Model              = new OrdersModel;
        $Model              = $Model::where('from_country_id', $CountryId);

        if(empty($TimeCreateStart)){
            $start = mktime(0, 0, 0);
            $Model = $Model->where('time_accept', '>=', $start);
        }else {
            $Model = $Model->where('time_accept', '>=', $TimeCreateStart);
        }

        if(empty($TimeCreateEnd)){
            $end = mktime(23, 59, 59);
            $Model = $Model->where('time_accept', '<=', $end);
        }else {
            $Model = $Model->where('time_accept', '<=', $TimeCreateEnd);
        }
        // 16 : Lấy thành công,
        // 15 : Lấy không thành công

        $GroupStatus  = new GroupOrderStatusModel;
        $GroupStatus  = $GroupStatus->get()->toArray(); 
        
        $ListPickedStatus = [];
        $ListPickFailStatus = [];
        foreach ($GroupStatus as $key => $value) {
            switch ($value['group_status']) {
                /*case 16:
                    $ListPickedStatus[] = $value['order_status_code'];
                break;*/
                case 15:
                    $ListPickFailStatus[] = $value['order_status_code'];
                break;
            }
        }


        
        // Lấy danh sách order đã lấy hàng trong ngày
        $ListAddressPickFail = clone $Model;
        $ListAddressPicked   = clone $Model;

        $ListAddressPicked   = $ListAddressPicked
                                ->where('time_pickup', '>', 0)
                                ->groupBy('from_address_id')
                                ->get(array('from_address_id', DB::raw('count(from_address_id) as total')))
                                ->toArray();

        $ListAddressPickFail = $ListAddressPickFail
                                ->where('time_pickup', 0)
                                ->whereIn('status', $ListPickFailStatus)
                                ->groupBy('from_address_id')
                                ->get(array('from_address_id', DB::raw('count(from_address_id) as total')))
                                ->toArray();

        $ListAddress         = $Model
                                ->groupBy('from_address_id')
                                ->where('status', '>', 20)
                                ->get(array('from_address_id', DB::raw('count(from_address_id) as total')))
                                ->toArray();


        $ListAddressId = [];
        $ListAddressGroupTotal = [];
        $ListAddressGroupTotalPicked = [];
        $ListAddressGroupTotalPickFail = [];
        

        foreach ($ListAddress as $key => $value) {
            $ListAddressId[] = $value['from_address_id'];
            $ListAddressGroupTotal[$value['from_address_id']] = $value['total'];
        }

        foreach ($ListAddressPicked as $key => $value) {
            $ListAddressGroupTotalPicked[$value['from_address_id']] = $value['total'];
        }

        foreach ($ListAddressPickFail as $key => $value) {
            $ListAddressGroupTotalPickFail[$value['from_address_id']] = $value['total'];
        }

        $InventoryModel = new UserInventoryModel;
        if($ListAddressId){
            $ListAddressId = array_unique($ListAddressId);
            $InventoryModel = $InventoryModel->whereIn('id', $ListAddressId);
        }else {
            return Response::json([
                "error"           => false,
                "error_message"   => "Không có địa chỉ nào cần lấy",
                "data"            => []
            ]);
        }
        

        if($DistrictId){
            $InventoryModel = $InventoryModel->where('province_id', $DistrictId);
        }
        if($CityId){
            $InventoryModel = $InventoryModel->where('city_id', $CityId);
        }

        if(!empty($Domain)){
            $InventoryModel   = $InventoryModel->where('sys_name',$Domain);
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
                $ListUser = [000000000000000000001];
            }else{

                if(!empty($ListUser)){
                    $ListUser   = array_intersect($ListUser, $ListUserSearch);
                }else{
                    $ListUser   = $ListUserSearch;
                }

            }
        }

        if(!empty($ListUser)){
            $InventoryModel  = $InventoryModel->whereIn('user_id', $ListUser);
        }


        if($Cmd == 'export'){
            return $this->ExportExcelInDay($InventoryModel->get()->ToArray(), $ListAddressGroupTotal, $ListAddressGroupTotalPicked, $ListAddressGroupTotalPickFail);
        }
        $Total = $InventoryModel->count();
        $InventoryModel = $InventoryModel->with(['user','ward','district','City'])->skip($offset)->take($itemPage)->get();

        return Response::json([
            'error'                 => false,
            'error_message'         => "Thành công",
            'data'                  => $InventoryModel,
            'total_group'           => $ListAddressGroupTotal,
            'total_group_picked'    => $ListAddressGroupTotalPicked,
            'total_group_pick_fail' => $ListAddressGroupTotalPickFail,
            'total'                 => $Total
        ]);

    }
}
