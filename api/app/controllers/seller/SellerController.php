<?php
namespace oms;

use Input;
use LMongo\Facades\LMongo;
use Maatwebsite\Excel\Facades\Excel;
use Response;
use User;
use omsmodel\SellerModel;
use omsmodel\CustomerAdminModel;

class SellerController extends \BaseController
{
    private $domain = '*';

    public function __construct()
    {

    }

    public function getCustomer() {

        $keyword        =   Input::has('keyword')           ? trim(Input::get('keyword'))           :   '';
        $type           =   Input::has('type')              ? trim(Input::get('type'))              :   1;
        $currentPage    =   Input::has('currentPage')       ? trim(Input::get('currentPage'))       :   1;
        $itemPerPage    =   Input::has('item_page')         ? trim(Input::get('item_page'))         :   20;
        $timeStart      =   Input::has('from_date')         ?   (int)Input::get('from_date')        :   0;
        $timeEnd        =   Input::has('to_date')           ?   (int)Input::get('to_date')          :   0;

        if($timeStart==0) {
            $timeStart = time() - 30*86400;
        }

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
        if(filter_var($keyword,FILTER_VALIDATE_EMAIL)) {
            $User = User::where('email',$keyword)->first();
        } else {
            $User = User::where('phone',$keyword)->first();
        }
        if(!empty($User)) {

            $UserID = $User->id;

            if($type==2) { //tim theo khach hang
                $sellerInfo = SellerModel::where('user_id', $UserID)->first();
                if (!empty($sellerInfo)) {
                    $seller = User::find($sellerInfo->seller_id);
                    $User->time_manager = $sellerInfo->time_create;
                    $newCustomerInfo = CustomerAdminModel::where('user_id', $UserID)->where('active', 1)->get();

                    $listCustomerInfo = [];
                    if (!$newCustomerInfo->isEmpty()) {
                        foreach ($newCustomerInfo as $oneNewCustomerInfo) {
                            $listCustomerInfo[$oneNewCustomerInfo->user_id] = $oneNewCustomerInfo;
                        }
                    }

                    if (isset($listCustomerInfo[$UserID])) {
                        $User->new_customer = $listCustomerInfo[$UserID];
                    }
                    $User->total = $sellerInfo;
                    $customers = array(0 => $User);
                }
            } else { //tim theo seller
                $sellerModel = SellerModel::where('seller_id',$UserID)->where('time_create','>=',$timeStart)->where('time_create','<=',$timeEnd);
                $CountModel    = clone $sellerModel;
                $totalAll = $CountModel->count();
                $customers = $sellerModel->take($itemPerPage)->skip($itemPerPage*($currentPage-1))->get();
                $seller = $User;
                $customerIDs = array();
                if(!$customers->isEmpty()) {
                    foreach($customers as $var) {
                        $customerIDs[] = $var->user_id;
                    }
                }
                if(!empty($customerIDs)) {
                    $customersInfo = User::whereIn('id',$customerIDs)->get();
                    $newCustomerInfo = CustomerAdminModel::whereIn('user_id',$customerIDs)->where('active',1)->get();

                    $listCustomerInfo = [];
                    if(!$newCustomerInfo->isEmpty()) {
                        foreach($newCustomerInfo as $oneNewCustomerInfo) {
                            $listCustomerInfo[$oneNewCustomerInfo->user_id] = $oneNewCustomerInfo;
                        }
                    }
                    if(!$customers->isEmpty()) {
                        foreach($customers as $k => $customer) {
                            if(!$customersInfo->isEmpty()) {
                                foreach($customersInfo as $customerInfo) {
                                    if($customer->user_id == $customerInfo->id) {
                                        $customers[$k] = new \stdClass();
                                        $customers[$k] = $customerInfo;
                                        $customers[$k]->total = $customer;
                                        $customers[$k]->time_manager = $customer->time_create;
                                    }
                                }
                            }

                            if(isset($listCustomerInfo[$customer->user_id])) {
                                $customers[$k]->new_customer = $listCustomerInfo[$customer->user_id];
                            }

                        }
                    }
                }
            }
            $returnData = array('seller'    =>  isset($seller) ? $seller : '', 'customer' =>  $customers);

            $response = array(
                'error' =>  false,
                'message'   =>  'success',
                'total_all' =>  isset($totalAll) ? $totalAll : 1,
                'item_page'     => $itemPerPage,
                'data'      =>  $returnData
            );

        } else {
            $response = array(
                'error' =>  true,
                'message'   =>  'Tài khoản không tồn tại'
            );
        }
        return Response::json($response);
    }



    public function getExcel()
    {

        $keyword = Input::has('keyword') ? trim(Input::get('keyword')) : '';
        $type = Input::has('type') ? trim(Input::get('type')) : 1;
        $timeStart = Input::has('from_date') ? (int)Input::get('from_date') : 0;
        $timeEnd = Input::has('to_date') ? (int)Input::get('to_date') : 0;

        if ($timeStart == 0) {
            $timeStart = time() - 30 * 86400;
        }

        $UserInfo = $this->UserInfo();
        if ($UserInfo['privilege'] == 0) {
            $response = array(
                'error' => true,
                'message' => 'Không có quyền !',
                'privilege' => 0
            );
        }
        if (empty($keyword)) {
            $response = array(
                'error' => true,
                'message' => 'Bạn cần nhập từ khóa',
                'privilege' => $UserInfo['privilege']
            );
        }
        if (isset($response)) {
            return Response::json($response);
        }
        if (filter_var($keyword, FILTER_VALIDATE_EMAIL)) {
            $User = User::where('email', $keyword)->first();
        } else {
            $User = User::where('phone', $keyword)->first();
        }
        if (!empty($User)) {

            $UserID = $User->id;

            if ($type == 2) { //tim theo khach hang
                $sellerInfo = SellerModel::where('user_id', $UserID)->first();
                if (!empty($sellerInfo)) {
                    $User->time_manager = $sellerInfo->time_create;
                    $newCustomerInfo = CustomerAdminModel::where('user_id', $UserID)->where('active', 1)->get();

                    $listCustomerInfo = [];
                    if (!$newCustomerInfo->isEmpty()) {
                        foreach ($newCustomerInfo as $oneNewCustomerInfo) {
                            $listCustomerInfo[$oneNewCustomerInfo->user_id] = $oneNewCustomerInfo;
                        }
                    }

                    if (isset($listCustomerInfo[$UserID])) {
                        $User->new_customer = $listCustomerInfo[$UserID];
                    }
                    $User->total = $sellerInfo;
                    $customers = array(0 => $User);
                }
            } else { 
            //tim theo seller
                $sellerModel = SellerModel::where('seller_id', $UserID)->where('time_create', '>=', $timeStart)->where('time_create', '<=', $timeEnd);
                $customers = $sellerModel->get();
                $customerIDs = array();
                if (!$customers->isEmpty()) {
                    foreach ($customers as $var) {
                        $customerIDs[] = $var->user_id;
                    }
                }
                if (!empty($customerIDs)) {
                    $customersInfo = User::whereRaw("id in (". implode(",", $customerIDs) .")")->get();
                    $newCustomerInfo = CustomerAdminModel::whereRaw("user_id in (". implode(",", $customerIDs) .")")->where('active', 1)->get();

                    $listCustomerInfo = [];
                    if (!$newCustomerInfo->isEmpty()) {
                        foreach ($newCustomerInfo as $oneNewCustomerInfo) {
                            $listCustomerInfo[$oneNewCustomerInfo->user_id] = $oneNewCustomerInfo;
                        }
                    }
                    if (!$customers->isEmpty()) {
                        foreach ($customers as $k => $customer) {
                            if (!$customersInfo->isEmpty()) {
                                foreach ($customersInfo as $customerInfo) {
                                    if ($customer->user_id == $customerInfo->id) {
                                        $customers[$k] = $customerInfo;
                                        $customers[$k]->total = $customer;
                                        $customers[$k]->time_manager = $customer->time_create;
                                    }
                                }
                            }

                            if (isset($listCustomerInfo[$customer->user_id])) {
                                $customers[$k]->new_customer = $listCustomerInfo[$customer->user_id];
                            }

                        }
                    }
                }
            }
            if(!empty($customers)) {
                return Excel::create('Khach_Hang', function ($excel) use($customers) {
                    $excel->sheet('Khách hàng', function ($sheet) use($customers) {
                        $sheet->mergeCells('C1:F1');
                        $sheet->row(1, function ($row) {
                            $row->setFontSize(20);
                        });
                        $sheet->row(1, array('','','Khách hàng'));
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
                            'STT', 'Tên khách hàng', 'Email khách hàng', 'Thời gian tạo tài khoản','First Order time', 'Tháng', 'Năm','Doanh thu tháng đầu', 'Doanh thu lũy kế','Last order time'
                        ));
                        $sheet->row(3,function($row){
                            $row->setBackground('#B6B8BA');
                            $row->setBorder('solid','solid','solid','solid');
                            $row->setFontSize(12);
                        });
                        //
                        $i = 1;
                        foreach ($customers AS $value) {
                            $dataExport = array(
                                'STT' => $i++,
                                'Tên khách hàng' => $value['fullname'],
                                'Email khách hàng' => $value['email'],
                                'Thời gian tạo tài khoản' => ($value['time_create'] > 0) ? date("d/m/Y H:i",$value['time_create']) : '',
                                'First Order time' => ($value['new_customer']['first_order_time'] > 0) ? date("d/m/Y H:i",$value['new_customer']['first_order_time']) : '',
                                'Tháng'        =>  ($value['new_customer']['first_order_time'] > 0) ? date("m",$value['new_customer']['first_order_time']) : '',
                                'Năm'        =>  ($value['new_customer']['first_order_time'] > 0) ? date("Y",$value['new_customer']['first_order_time']) : '',
                                'Doanh thu tháng đầu' => $value['total']['total_firstmonth'],
                                'Doanh thu lũy kế' => $value['total']['total_nextmonth'],
                                'Last order time' => ($value['new_customer']['last_order_time'] > 0) ? date("d/m/Y H:i",$value['new_customer']['last_order_time']) : ''
                            );
                            $sheet->appendRow($dataExport);
                        }
                    });
                })->export('xls');
            }
        }
    }


    public function postChangeUser($UserID){
        $Seller   = Input::has('seller')    ? (int)Input::get('seller')     : 0;
        $Note     = Input::has('note')      ? trim(Input::get('note'))      : '';
        $UserInfo = $this->UserInfo();
        if(empty($Seller)){
            $response = array(
                'error'         => true,
                'message'       => 'Vui lòng chọn quản lý mới',
                'privilege'     => 0
            );
            return Response::json($response);
        }

        if($UserInfo['privilege'] != 2 && $UserInfo['group'] != 10){
            $response = array(
                'error'         => true,
                'message'       => 'Không có quyền !',
                'privilege'     => 0
            );
            return Response::json($response);
        }

        $SellerModel              = SellerModel::where('user_id', $UserID)->first();
        if(!isset($SellerModel->id)){
            $response = array(
                'error'         => true,
                'message'       => 'Khách hàng không tồn tại',
                'privilege'     => 0
            );
            return Response::json($response);
        }

        $Old                        = $SellerModel->seller_id;
        $SellerModel->seller_id     = $Seller;
        $SellerModel->time_receive  = time();

        try {
            $SellerModel->save();
        } catch (Exception $e) {
            $response = array(
                'error'     =>  true,
                'message'   =>  'Lỗi kết nối dữ liêu',
                'privilege' =>  $UserInfo['privilege']
            );
            return Response::json($response);   
        }

        $data = [
            'time_create'   =>  time(),
            'user_id'       =>  $UserInfo['id'],
            'customer_id'   =>  $SellerModel->user_id,
            'seller_id'     =>  $Seller,
            'old_seller_id' =>  $Old,
            'note'          =>  $Note,
            'type'          =>  'change_manager' 
        ];
        LMongo::collection('log_change_owner_customer')->insert($data);

        $response = array(  
            'error'   =>  false,
            'data'    =>  $SellerModel,
            'message' =>  'Đổi quyền quản lý thành công'
        );
        return Response::json($response);
        

    }


    public function postTakeUser($UserID) {
        $type                  = Input::has('type')             ? strtolower(trim(Input::get('type')))  : 'seller';
        $csID                  = Input::has('cs_id')            ? (int)Input::get('cs_id')  : 0;
        $BusinessModel         = Input::has('business_model')   ? Input::get('business_model')  : "";
        $BusinessPlaceCity     = Input::has('place_city')       ? Input::get('place_city')  : 0;
        $BusinessPlaceDistrict = Input::has('place_district')   ? Input::get('place_district')  : 0;
        $AvgLading             = Input::has('avg_lading')       ? Input::get('avg_lading')  : 0;

        $UserInfo   = $this->UserInfo();

        if($type != 'cs'){
            $validation = \Validator::make(Input::all(), array(
                'business_model' => 'required',
                'place_city'     => '|required|numeric',
                'place_district' => '|required|numeric',
                'avg_lading'     => '|numeric|numeric|min:1',
            ));
        
            //error
            if($validation->fails()) {
                return Response::json(array('error' => true, 'message' => 'Vui lòng nhập đầy đủ các trường dữ liệu'));
            }

        }
        if(!$this->check_privilege('PRIVILEGE_SELLER', 'add')){
            $response = array(
                'error'         => true,
                'message'       => 'Không có quyền !',
                'privilege'     => 0
            );
            return Response::json($response);
        }

        //check take user?
        $seller = SellerModel::firstOrNew(['user_id'=>$UserID]);

        if(!isset($seller->time_create) || $seller->time_create == 0){
            $seller->time_create    = time();
        }

        if($type == 'cs') {
            if(isset($seller->cs_id) && $seller->cs_id > 0){
                $response = array(
                    'error' =>  true,
                    'message'   =>  'Khách hàng này đã có người chăm sóc',
                    'privilege' =>  $UserInfo['privilege']
                );
                return Response::json($response);
            }

            $seller->cs_id = $csID;
        } else {
            if(isset($seller->seller_id) && $seller->seller_id > 0){
                $response = array(
                    'error' =>  true,
                    'message'   =>  'Khách hàng này đã có người quản lý',
                    'privilege' =>  $UserInfo['privilege']
                );
                return Response::json($response);
            }
            /// 
            $seller->business_model = $BusinessModel;
            $seller->place_city     = $BusinessPlaceCity;
            $seller->place_district = $BusinessPlaceDistrict;
            $seller->avg_lading     = $AvgLading;
            $seller->sale           = 0;
            $seller->seller_id      = (int)$UserInfo['id'];
            $seller->time_receive   = time();
        }

        try{
            $seller->save();
        }catch (\Exception $e){
            $response = array(
                'error' =>  true,
                'message'   =>  'Lỗi kết nối dữ liệu .'.$e->getMessage(),
                'privilege' =>  $UserInfo['privilege']
            );
            return Response::json($response);
        }

            if($type == 'seller') {
                $data = [
                    'time_create'   =>  time(),
                    'user_id'       =>  $UserInfo['id'],
                    'customer_id'   =>  $UserID,
                    'seller_id'     =>  $seller->seller_id,
                    'status'        =>  1
                ];
                LMongo::collection('log_change_owner_customer')->insert($data);

                $response = array(
                    'error' =>  false,
                    //'data'  =>  $sellerUser,
                    'message'   =>  'Nhận quyền quản lý thành công'
                );

            } else {
                $data = [
                    'time_create'   =>  time(),
                    'user_id'       =>  $UserInfo['id'],
                    'customer_id'   =>  $UserID,
                    'cs_id'         =>  $seller->cs_id,
                ];
                LMongo::collection('log_change_vip_customer')->insert($data);
                $response = [
                    'error' =>  false,
                    'message'   =>  'Phân quyền quản lý thành công'
                ];
            }

            return Response::json($response);
    }

    public function getRemove($userID) {
        $user = $this->UserInfo();
        if(!$this->check_privilege('PRIVILEGE_SELLER', 'del')){
            $response = array(
                'error'         => true,
                'message'       => 'Không có quyền !',
                'privilege'     => 0
            );
            return Response::json($response);
        }

        $seller = SellerModel::firstOrNew(['user_id' => $userID]);

        $data = [
            'time_create'   =>  time(),
            'user_id'       =>  (int)$user['id'],
            'customer_id'   =>  $seller->user_id,
            'seller_id'     =>  $seller->seller_id,
            'status'        =>  0,
            'note'          =>  trim(Input::get('note'))
        ];

        $seller->seller_id = 0;

        try{
            $seller->save();
            LMongo::collection('log_change_owner_customer')->insert($data);
        }catch (\Exception $e){
            $response = array(
                'error'         => true,
                'message'       => 'Kết nối dữ liệu thất bại',
                'privilege'     => 0
            );
            return Response::json($response);
        }

        $response = array(
            'error'         => false,
            'message'       => 'Thành công',
            'privilege'     => 0
        );
        return Response::json($response);

    }

    public function getHistory($userID) {
        $LMongo = new LMongo;
        $listHistory = $LMongo::collection('log_change_owner_customer')->where('customer_id',(int)$userID)->get()->toArray();

        if(!empty($listHistory)) {
            $listUserID = [];
            foreach($listHistory as $oneHistory) {
                $listUserID[] = $oneHistory['user_id'];
                $listUserID[] = $oneHistory['seller_id'];
                $listUserID[] = $oneHistory['customer_id'];
                if(isset($oneHistory['old_seller_id'])){
                    $listUserID[] = $oneHistory['old_seller_id'];
                }

            }
            $listUserID = array_unique($listUserID);
            if(!empty($listUserID)) {
                $user = User::whereIn('id',$listUserID)->get(['id', 'email', 'password', 'fullname', 'phone']);
                $listUser = [];
                if(!$user->isEmpty()) {
                    foreach($user as $oneUser) {
                        $listUser[$oneUser->id] = $oneUser;
                    }
                }
                foreach($listHistory as $k=> $oneHistory) {
                    $listHistory[$k]['user_update'] = isset($listUser[$oneHistory['user_id']]) ? $listUser[$oneHistory['user_id']] : '';
                    $listHistory[$k]['seller'] = isset($listUser[$oneHistory['seller_id']]) ? $listUser[$oneHistory['seller_id']] : '';
                    $listHistory[$k]['customer'] = isset($listUser[$oneHistory['customer_id']]) ? $listUser[$oneHistory['customer_id']] : '';
                    if(isset($oneHistory['old_seller_id'])){
                        $listHistory[$k]['old_seller_id'] = isset($listUser[$oneHistory['old_seller_id']]) ? $listUser[$oneHistory['old_seller_id']] : '';
                    }   
                }
                $response = [
                    'error'    =>  false,
                    'data'      =>  $listHistory
                ];
            } else {
                $response = [
                    'error'    =>  true,
                    'message'   =>  'Không có dữ liệu'
                ];
            }
        } else {
            $response = [
                'error'    =>  true,
                'message'   =>  'Không có dữ liệu'
            ];
        }
        return Response::json($response);
    }

    public function getHistoryCs($userID) {
        $LMongo = new LMongo;
        $listHistory = $LMongo::collection('log_change_vip_customer')->where('customer_id',(int)$userID)->get()->toArray();

        if(!empty($listHistory)) {
            $listUserID = [];
            foreach($listHistory as $oneHistory) {
                $listUserID[] = $oneHistory['user_id'];
                $listUserID[] = $oneHistory['cs_id'];
                $listUserID[] = $oneHistory['customer_id'];
            }
            $listUserID = array_unique($listUserID);
            if(!empty($listUserID)) {
                $user = User::whereIn('id',$listUserID)->get();
                $listUser = [];
                if(!$user->isEmpty()) {
                    foreach($user as $oneUser) {
                        $listUser[$oneUser->id] = $oneUser;
                    }
                }
                foreach($listHistory as $k=> $oneHistory) {
                    $listHistory[$k]['user_update'] = isset($listUser[$oneHistory['user_id']]) ? $listUser[$oneHistory['user_id']] : '';
                    $listHistory[$k]['cs'] = isset($listUser[$oneHistory['cs_id']]) ? $listUser[$oneHistory['cs_id']] : '';
                    $listHistory[$k]['customer'] = isset($listUser[$oneHistory['customer_id']]) ? $listUser[$oneHistory['customer_id']] : '';
                }
                $response = [
                    'error'    =>  false,
                    'data'      =>  $listHistory
                ];
            } else {
                $response = [
                    'error'    =>  true,
                    'message'   =>  'Không có dữ liệu'
                ];
            }
        } else {
            $response = [
                'error'    =>  true,
                'message'   =>  'Không có dữ liệu'
            ];
        }
        return Response::json($response);
    }
}
