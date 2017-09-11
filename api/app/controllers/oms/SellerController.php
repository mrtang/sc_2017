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

    private $WAREHOUSE = [
        '1' => 'Nhà chung cư',
        '2' => 'Cửa hàng',
        '3' => 'Nhà mặt đất',
        '4' => 'Nhà trong ngõ',
        '5' => 'Kho hàng'
    ];

    private $PRODUCT_TYPE = [
        '1' => 'Thời trang',
        '2' => 'Mỹ phẩm',
        '3' => 'Gia dụng',
        '4' => 'Đồ công nghệ, điện tử',
        '5' => 'Khác'
    ];

    private $BUSINESS_MODEL = [
        'B2B'   => 'B2B',
        'B2C'   => 'B2C',
        'C2C'   => 'C2C',
        'B2B2C' => 'B2B2C',
    ];

    public function getBusinessModel(){
        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => $this->BUSINESS_MODEL
        ]);
    }

    public function getProductType(){
        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => $this->PRODUCT_TYPE
        ]);
    }

    public function getWarehouse(){
        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => $this->WAREHOUSE
        ]);
    }

    public function postTakeUser ($MerchantId){
        //$MerchantId     =   Input::has('merchant_id')          ? trim(Input::get('merchant_id'))                :   '';
        $ProductType    =   Input::has('product_type')           ? trim(Input::get('product_type'))             :   '';
        $City           =   Input::has('place_city')             ? (Input::get('place_city'))                :   '';
        $District       =   Input::has('place_district')         ? (Input::get('place_district'))            :   '';
        $BusinessModel  =   Input::has('business_model')         ? trim(Input::get('business_model'))           :   '';
        $Identifier     =   Input::has('identifier')             ? trim(Input::get('identifier'))               :   '';
        $Warehouse      =   Input::has('warehouse')              ? (Input::get('warehouse'))                 :   "";
        $Contract       =   Input::has('contract')               ? (Input::get('contract'))                  :   2;
        $AvgLading      =   Input::has('avg_lading')             ? (Input::get('avg_lading'))                :   "";
        $Website        =   Input::has('website')                ? (Input::get('website'))                   :   "";
        $Note           =   Input::has('note')                   ? Input::get('note')                           :   "";

        $SellerModel  = new SellerModel;
        $UserInfo     = $this->UserInfo();

        if (empty($MerchantId)){
            $response = array(
                'error'             => true,
                'error_message'     => 'seller_id empty',
            );
            return Response::json($response);
        }


        if($UserInfo['privilege'] == 0){
            $response = array(
                'error'             => true,
                'error_message'     => 'Bạn thực hiện chức năng này !',
                'privilege'         => 0
            );
            return Response::json($response);
        }

        if(!$this->check_privilege('PRIVILEGE_SELLER', 'add')){
            $response = array(
                'error'             => true,
                'error_message'     => 'Bạn thực hiện chức năng này !',
                'privilege'         => 0
            );
            return Response::json($response);
        }


        $Seller = $SellerModel->firstOrNew(['user_id' => $MerchantId]);

        if ($Seller->seller_id > 0) {
            $response = array(
                'error'             => true,
                'error_message'     => 'Khách hàng đã có người quản lý',
                'privilege'         => 0
            );
            return Response::json($response);
        }

        $Seller->time_create    = $Seller->time_create == 0 ? $this->time() : $Seller->time_create;
        $Seller->time_receive   = $this->time();
        $Seller->business_model = $BusinessModel;
        $Seller->place_city     = $City;
        $Seller->place_district = $District;
        $Seller->avg_lading     = $AvgLading;
        $Seller->warehouse      = $Warehouse;
        
        $Seller->seller_id      = (int)$UserInfo['id'];
        $Seller->product_type   = $ProductType;
        $Seller->identifier     = $Identifier;
        $Seller->contract       = $Contract;
        $Seller->website        = $Website;
        $Seller->note           = $Note;

        try {
            $Seller->save();
        } catch (Exception $e) {
            return Response::json([
                'error'             => true,
                'error_message'     => 'Lỗi kết nối hệ thống, vui lòng thử lại !',
                'privilege'         => 0
            ]);
        }


        $LogData = [
            'time_create'   =>  $this->time(),
            'seller_id'     =>  $UserInfo['id'],// Sales
            'user_id'       =>  $MerchantId,
            'status'        =>  1
        ];

        LMongo::collection('log_change_owner_customer_')->insert($LogData);

        $response = array(
            'error'           =>  false,
            'error_message'   =>  'Nhận quyền quản lý thành công'
        );

        return Response::json($response);

    }





    public function getCustomer() {

        $keyword        =   Input::has('keyword')           ? trim(Input::get('keyword'))           :   '';
        $type           =   Input::has('type')              ? trim(Input::get('type'))              :   1;
        $currentPage    =   Input::has('currentPage')       ? trim(Input::get('currentPage'))       :   1;
        $itemPerPage    =   Input::has('item_page')         ? trim(Input::get('item_page'))         :   20;
        $timeStart      =   Input::has('from_date')         ?   (int)Input::get('from_date')        :   0;
        $timeEnd        =   Input::has('to_date')           ?   (int)Input::get('to_date')          :   0;

        if($timeStart == 0) {
            $timeStart = $this->time() - 30 * 86400;
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
            $timeStart = $this->time() - 30 * 86400;
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
        $SellerModel->time_receive  = $this->time();

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
            'time_create'   =>  $this->time(),
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


    /*public function postTakeUser($UserID) {
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
            $seller->time_create    = $this->time();
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
            $seller->time_receive   = $this->time();

            try {
                \sellermodel\UserInfoModel::where('user_id', $seller->user_id)->update(['time_update' => $this->time()]);
            } catch (Exception $e) {
                return Response::json(['error'=> true, 'message' => 'UPDATE_ERROR','error_message'=> 'Khách hàng không tồn tại!']);
            }
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
                    'time_create'   =>  $this->time(),
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
                    'time_create'   =>  $this->time(),
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
    }*/

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
            'time_create'   =>  $this->time(),
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
                'error'     =>  true,
                'message'   =>  'Không có dữ liệu'
            ];
        }
        return Response::json($response);
    }

    public function postRemoveSale($sale_id){
        $user = $this->UserInfo();

        if($user['id'] !== 2){
            return Response::json([
                'error'         => true,
                'error_message' => "You can't perform this action !.",
                'data'          => ''
            ]);
        }
        $CustomerList = SellerModel::where('seller_id', $sale_id)->get();
        if($Count->isEmpty()){
            return Response::json([
                'error'         => true,
                'error_message' => "empty",
                'data'          => ''
            ]);
        }
        $Logs = [];
        foreach ($CustomerList as $key => $value) {
            $Logs[] = [
                'time_create'   =>  $this->time(),
                'user_id'       =>  (int)$user['id'],
                'customer_id'   =>  $value->user_id,
                'seller_id'     =>  $value->seller_id,
                'status'        =>  0,
                'note'          =>  "Xóa sale"
            ];
        }
        try {
            SellerModel::where('seller_id', $sale_id)->update(['seller_id', 0]);
            LMongo::collection('log_change_owner_customer')->batchInsert($Logs);
        } catch (Exception $e) {
            $response = array(
                'error'             => true,
                'error_message'     => 'Kết nối dữ liệu thất bại',
                'privilege'         => 0
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

    public function postActiveWarehouse($user_id){
        $user = $this->UserInfo();
        if(!in_array($user['group'], [10]) && $user['privilege'] != 2){
            return Response::json([
                'error'         => true,
                'error_message' => "You can't perform this action !.",
                'data'          => ''
            ]);
        }

        $Merchant = \sellermodel\UserInfoModel::where('user_id', $user_id)->first();
        if(!isset($Merchant->id)){
            return Response::json([
                'error'         => true,
                'error_message' => "empty",
                'data'          => ''
            ]);
        }

        try {
            $Merchant->fulfillment  = 1;
            $Merchant->save();
        } catch (Exception $e) {
            $response = array(
                'error'             => true,
                'error_message'     => 'Kết nối dữ liệu thất bại, hãy thử lại'
            );
            return Response::json($response);
        }

        $response = array(
            'error'         => false,
            'message'       => 'Thành công'
        );
        return Response::json($response);
    }

    public function postChangeIncomingsTime($user_id){
        $User       = \omsmodel\SellerModel::firstOrCreate(['user_id' => $user_id]);
        $TimeStart  = Input::has('time_start')  ? trim(Input::get('time_start'))    : '';

        if(!isset($User->seller_id) || $User->seller_id == 0){
            return Response::json([
                'error'         => true,
                'error_message' => "No manager yet!.",
                'data'          => ''
            ]);
        }

        $user = $this->UserInfo();
        if(!in_array($user['group'], [10]) && $user['privilege'] != 2 && $user['id'] != $User->seller_id){
            return Response::json([
                'error'         => true,
                'error_message' => "You can't perform this action !.",
                'data'          => ''
            ]);
        }

        $TimeStart = strtotime(date('Y-m-d 00:00:00', $TimeStart));

        $TimeIncomings  = ($User->first_time_incomings > 0) ? $User->first_time_incomings : $User->first_time_pickup;
        if(empty($TimeIncomings)){
            return Response::json([
                'error'         => true,
                'error_message' => "First time pickup is empty!.",
                'data'          => ''
            ]);
        }

        if($TimeStart < $TimeIncomings){
            return Response::json([
                'error'         => true,
                'error_message' => "Time no less than old time incomings!.",
                'data'          => ''
            ]);
        }

        $CheckTime = $this->__check_time_edit_kpi($TimeIncomings, $TimeStart);
        if($CheckTime['error']){
            return Response::json($CheckTime);
        }

        try {
            $User->first_time_incomings  = $TimeStart;
            $User->save();
        } catch (Exception $e) {
            $response = array(
                'error'             => true,
                'error_message'     => 'Cập nhật dữ liệu thất bại, hãy thử lại'
            );
            return Response::json($response);
        }

        $response = array(
            'error'         => false,
            'message'       => 'Thành công'
        );
        return Response::json($response);
    }
}
