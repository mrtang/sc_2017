<?php
namespace oms;

use Response;
use Exception;
use Input;
use Validator;
use Excel;
use DB;

use sellermodel\UserInfoModel;
use accountingmodel\MerchantModel;
use omsmodel\SellerModel;
use omsmodel\GroupProcessModel;
use sellermodel\VimoModel;
use ordermodel\OrdersModel;
use ordermodel\VerifyModel;
use ticketmodel\RequestModel;
use User;

class UserCtrl extends \BaseController
{
    private $data              = [];
    private $user              = [];
    private $object            = 'sender';
    
    private $search_user       = [];
    private $tracking_code     = [];
    
    private $from_user_id      = 0;
    private $to_user           = [];
    
    private $list_district_id  = [];
    private $list_ward_id      = [];
    private $list_to_address   = [];
    private $list_from_address = [];

    /*
     * get user
     */
    private function getUser(){
        $UserModel = new User;
        if(empty($this->search_user)){
            return;
        }
        foreach($this->search_user as $key => $val){
            if($key == 'phone'){
                $UserModel = $UserModel->where(function ($q) use ($val){
                    $q->where('phone', $val)->orWhere('phone2', $val);
                });
            }else {
                $UserModel = $UserModel->where($key, $val);
            }
            //$UserModel = $UserModel->where($key, $val);
            
        }
        $User   = $UserModel->with(['user_info','oms_seller'])->first(['id','email','fullname','phone', 'time_create']);
        $TimeIncomings  = 0;
        if(!empty($User->oms_seller)){
            if($User->oms_seller->first_time_incomings > 0){
                $TimeIncomings = $User->oms_seller->first_time_incomings*1000;
            }else{
                $TimeIncomings = $User->oms_seller->first_time_pickup*1000;
            }
        }


        if(isset($User->id)){
            $this->user = [
              'id'                      => $User->id,
              'email'                   => $User->email,
              'fullname'                => $User->fullname,
              'phone'                   => $User->phone,
              'group'                   => $User->user_info->group,
              'time_create'             => $User->time_create,
              'first_time_incomings'    => $TimeIncomings,
              'avatar'      => 'http://www.gravatar.com/avatar/'.md5($User->email).'?s=80&d=mm&r=g'
              
            ];
        }
        return;
    }


    public function getStatistic($KeyWord) {$this->LogQuery();
        $KeyWord    = trim($KeyWord);
        if(!empty($KeyWord)){
            //if(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>1,'max_range'=>5)))){ // Mã đơn
            
            if(strlen($KeyWord) >= 1 && strlen($KeyWord) <= 8){ // Mã đơn
                $this->object       = 'order';
                $this->search_user['id'] = (int)$KeyWord;
            }elseif(preg_match('/^\$/',$KeyWord)){// Nhân viên
                $this->object   = 'seller';
                $KeyWord        = substr($KeyWord,1);
            }else {//Người gửi

            }

            if(in_array($this->object, ['seller','sender'])){
                if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)) {  // search email
                    $this->search_user['email'] = $KeyWord;
                } elseif (filter_var((int)$KeyWord, FILTER_VALIDATE_INT, array('option' => array('min_range' => 8, 'max_range' => 20)))) {  // search phone
                    $this->search_user['phone'] = $KeyWord;
                } else { // search code
                    $this->search_user['fullname'] = $KeyWord;
                }
            }

            $this->getUser();
        }else{
            return $this->ResponseData();
        }

        if(empty($this->user['id'])){
            return $this->ResponseData();
        }

        if($this->object == 'sender' || $this->object == 'order'){
            $UserInfoModel  = new UserInfoModel;
            $UserInfo       = $UserInfoModel::where('user_id', $this->user['id'])
                                            ->first([
                                                'user_id','user_nl_id','email_nl','email_notice','phone_notice',
                                                'freeze_money','priority_payment', 'is_vip', 'pipe_status', 'group','num_contract',
                                                'time_active_contract', 'fulfillment'
                                            ]);

            if(isset($UserInfo->user_id)){
                $this->user['user_nl_id']       = $UserInfo->user_nl_id;
                $this->user['email_nl']         = $UserInfo->email_nl;
                $this->user['email_notice']     = $UserInfo->email_notice;
                $this->user['phone_notice']     = $UserInfo->phone_notice;
                $this->user['freeze_money']     = $UserInfo->freeze_money;
                $this->user['priority_payment'] = $UserInfo->priority_payment;
                $this->user['is_vip']           = $UserInfo->is_vip;
                $this->user['group']            = $UserInfo->group;
                $this->user['contract']         = $UserInfo->num_contract;
                $this->user['time_contract']    = $UserInfo->time_active_contract;
                $this->user['fulfillment']      = $UserInfo->fulfillment;

                
                if($UserInfo->pipe_status > 0){
                    $CurrentStatus = GroupProcessModel::where('code', $UserInfo->pipe_status)->first();
                    $this->user['currentStatus']    = $CurrentStatus ? $CurrentStatus->name : '';
                    
                }

                $MerchantModel  = new MerchantModel;
                $Merchant       = $MerchantModel::where('merchant_id', $this->user['id'])->first(['merchant_id', 'balance', 'provisional', 'freeze', 'quota', 'time_create', 'level']);
                if(isset($Merchant->merchant_id)){
                    $this->user['balance']          = $Merchant->balance;
                    $this->user['provisional']      = $Merchant->provisional;
                    $this->user['freeze']           = $Merchant->freeze;
                    $this->user['quota']            = $Merchant->quota;
                    $this->user['level']            = $Merchant->level;
                    
                }

                $SellerModel = new SellerModel;
                $Seller      = $SellerModel::where('user_id', $this->user['id'])->first(['seller_id','user_id','cs_id','total_firstmonth', 'total_nextmonth', 'time_create', 'time_receive']);
                if(isset($Seller->user_id)){
                    $this->user['seller_id']            = $Seller->seller_id;
                    $this->user['cs_id']                = $Seller->cs_id;
                    $this->user['manager']              = User::select('fullname', 'id')->where('id', $Seller->seller_id)->first();
                    $this->user['total_firstmonth']     = $Seller->total_firstmonth;
                    $this->user['total_nextmonth']      = $Seller->total_nextmonth;
                    $this->user['time_manager']         = $Seller->time_receive;
                }


                $UserConfigTransportModel = new \UserConfigTransportModel;
                $UserConfigTransportModel = $UserConfigTransportModel::where('user_id', $this->user['id'])->where('transport_id', 2)->first();
                if (isset($UserConfigTransportModel)) {
                    $this->user['email_notice']   = $UserConfigTransportModel->received;
                }


                $VimoModel  = new VimoModel;
                $Vimo       = $VimoModel::where('user_id', $this->user['id'])->first(['user_id','bank_code','bank_address','account_name', 'atm_image', 'cmnd_before_image', 'cmnd_after_image', 'account_number','active','time_accept', 'note']);
                if(isset($Vimo->user_id)){
                    $this->user['bank_code']            = $Vimo->bank_code;
                    $this->user['bank_address']         = $Vimo->bank_address;
                    $this->user['account_name']         = $Vimo->account_name;
                    $this->user['account_number']       = $Vimo->account_number;
                    $this->user['atm_image']            = $Vimo->atm_image;
                    $this->user['cmnd_before_image']    = $Vimo->cmnd_before_image;
                    $this->user['cmnd_after_image']     = $Vimo->cmnd_after_image;
                    $this->user['vimo_active']          = $Vimo->active;
                    $this->user['time_accept']          = $Vimo->time_accept;
                    $this->user['note']                 = $Vimo->note;
                }
            }
        }
        return $this->ResponseData();
    }

    public function getOrderStatistic(){
        $from_time      = Input::has('from_time')   ? (int)Input::get('from_time')  : 0; 
        $to_time        = Input::has('to_time')     ? (int)Input::get('to_time')    : 0; 
        $id             = Input::has('user_id')     ? (int)Input::get('user_id')    : 0;

        $Model          = new \ordermodel\OrdersModel;
        $UserInfo       = $this->UserInfo();
        $_ret           = [];

        if(!empty($id)){
            $Model  = $Model::where('from_user_id',$id);
        }else {
            return Response::json([
                "error"     => false,
                "error_message"   => "Vui lòng gửi lên trường user_id",
                "data"      => ""
            ]);
        }

        if($from_time   ==  0) {
            $from_time = $this->time() - 30*86400;
        }
        $Model          = $Model->where('time_create', '>=', $from_time);

        if($to_time > 0){
            $Model          = $Model->where('time_create', '<=', $to_time);
        }
        $StatusOrderCtrl        = new \order\StatusOrderCtrl;
        $Group                  = [];
        $ListGroupStatus        = [];
        $GroupStatusOrder       = [];    


        $ListGroup  = $StatusOrderCtrl->getStatusgroup(false);

        if(!empty($ListGroup)) {
            foreach($ListGroup as $val){
                foreach($val['group_order_status'] as $v) {
                    if(!isset($GroupStatusOrder[$val['id']])){
                        $GroupStatusOrder[$val['id']] = [];        
                    }
                    $GroupStatusOrder[$val['id']][]    = (int)$v['order_status_code'];
                    $ListStatus[]       = (int)$v['order_status_code'];
                    $ListGroupStatus[(int)$v['order_status_code']]    = $v['group_status'];
                }
            }
        }

        if(!empty($ListStatus)) {
            $OrderModel     = clone $Model;
            $DataGroup      = $OrderModel->groupBy('status')->get(array('status',DB::raw('count(*) as count')));

            if(!empty($DataGroup)){
                foreach($DataGroup as $val){
                    if(!isset($Group[(int)$ListGroupStatus[(int)$val['status']]])){
                        $Group[(int)$ListGroupStatus[(int)$val['status']]]  = 0;
                    }
                    $Group[(int)$ListGroupStatus[(int)$val['status']]] += $val['count'];
                }
            }
        }

        $Orders = clone $Model;
        $Orders = $Orders->with(['OrderDetail' => function ($q){
            return $q->select(['order_id', 'sc_pvc', 'sc_cod', 'sc_pbh', 'sc_pvk', 'sc_pch','money_collect']);
        }])->where('status', '>', 21)->whereNotIn('status', $GroupStatusOrder[22])->select('tracking_code', 'id')->get();

        $totalCod           = 0;
        $totalFee           = 0;

        foreach ($Orders as $key => $value) {
            $totalCod           += $value['order_detail']['money_collect'];
            $totalFee           += $value['order_detail']['sc_pvc'] + $value['order_detail']['sc_cod'] + $value['order_detail']['sc_pbh']
                                + $value['order_detail']['sc_pvk'] + $value['order_detail']['sc_ch'];
        }



        $_ret['order_confirm_return']       = isset($Group[20]) ? $Group[20] : 0;
        $_ret['order_return']               = isset($Group[21]) ? $Group[21] : 0;
        $_ret['order_accept']               = isset($Group[13]) ? $Group[13] : 0;
        $_ret['order_pickuping']            = isset($Group[14]) ? $Group[14] : 0;
        $_ret['order_delivery']             = isset($Group[19]) ? $Group[19] : 0;

        
        $_ret['total_cod']                  = $totalCod;
        $_ret['total_fee']                  = $totalFee;
        if($_ret['order_return'] > 0 && $_ret['order_delivery'] > 0){
            $_ret['percent_order_return']       = ($_ret['order_return'] / ($_ret['order_return'] + $_ret['order_delivery'])) * 100;
        }else {
            $_ret['percent_order_return']       = 0;    
        }

        return Response::json([
            "error"     => false,
            "message"   => "",
            "data"      => $_ret
        ]);
    }

    // Danh sách bản kê của seller
    public function getNewestVerify(){
        $page     = Input::has('page')            ? (int)Input::get('page')                   : 1;
        $itemPage = Input::has('limit')           ? Input::get('limit')                       : 20;
        $SellerId = Input::has('seller')          ? (int)Input::get('seller')                 : 0;
        $cmd      = Input::has('cmd')             ? strtoupper(trim(Input::get('cmd')))       : '';
        $Model    = new VerifyModel;

        if(!empty($TimeStart)){
            $Model = $Model->where('time_create','>=', $TimeStart);
        }else{
            $Model = $Model->where('time_create','>=', $this->time() - 86400 * 30);
        }

        $Model = $Model->where('user_id', $SellerId);
        
        
        $Model = $Model->orderBy('time_create','DESC');
        $Data  = $Model->take(5)->get()->toArray();

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        );
        
        return Response::json($contents);

    }

    public function getNewestTicket(){
        $page     = Input::has('page')            ? (int)Input::get('page')                   : 1;
        $itemPage = Input::has('limit')           ? Input::get('limit')                       : 20;
        $SellerId = Input::has('seller')          ? (int)Input::get('seller')                 : 0;
        $cmd      = Input::has('cmd')             ? strtoupper(trim(Input::get('cmd')))       : '';
        $Model    = new RequestModel;

        $Model = $Model->where('user_id',$SellerId);
        $Total = 0;
        
        if(!empty($TimeStart)){
            $Model = $Model->where('time_create', '>=', $TimeStart);
        }else{
            $Model = $Model->where('time_create', '>=', $this->time() - 86400 * 7);
        }
        
        
        $Model = $Model->orderBy('time_create','DESC');
        $Data  = $Model->take(10)->get()->toArray();

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        );
        
        return Response::json($contents);

    }

    public function getNewestEmail(){
        $page     = Input::has('page')            ? (int)Input::get('page')                   : 1;
        $itemPage = Input::has('limit')           ? Input::get('limit')                       : 20;
        $SellerId = Input::has('seller')          ? (int)Input::get('seller')                 : 0;
        $cmd      = Input::has('cmd')             ? strtoupper(trim(Input::get('cmd')))       : '';
        $Model = new RequestModel;

        $Model = $Model->where('user_id',$SellerId);
        $Total = 0;
        
        if(!empty($TimeStart)){
            $Model = $Model->where('time_create', '>=', $TimeStart);
        }else{
            $Model = $Model->where('time_create', '>=', $this->time() - 86400 * 7);
        }
        
        if(!empty($TimeEnd)){
            $Model = $Model->where('time_create','<',$TimeEnd);
        }

        $Model = $Model->orderBy('time_create','DESC');
        $Data  = $Model->get()->toArray();

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        );
        
        return Response::json($contents);

    }

    //merchant
    public function getMerchant()
    {
        $page      = Input::has('page')            ? (int)Input::get('page')                : 1;
        $itemPage  = Input::has('limit')           ? Input::get('limit')                    :20;
        $Keywords  = Input::has('keyword')         ? Input::get('keyword')                  :"";
        $SellerId  = Input::has('seller')          ? (int)Input::get('seller')              : 0;
        $TimeStart = Input::has('first_time_pickup_start')      ? (int)Input::get('first_time_pickup_start')          : 0;
        $TimeEnd   = Input::has('first_time_pickup_end')        ? (int)Input::get('first_time_pickup_end')            : 0;
        $cmd       = Input::has('cmd')             ? strtoupper(trim(Input::get('cmd')))    :'';


        $listUID = [];
        if(!empty($Keywords)){
            if (filter_var($Keywords, FILTER_VALIDATE_EMAIL)) {  // search email
                $user  = (new User)->where('email',  $Keywords)->get();
            } else {
                $user  = (new User)->where('phone',  $Keywords)->get();
            }
        }
        if(!empty($user)){
            foreach ($user as $key => $value) {
                $listUID[] = $value['id'];
            }
        }



        $Model = new SellerModel;
        $Model = $Model->where('active',1);
        // Doanh thu lũy kế khác hàng ngừng sử dụng
        $LogSellerModel = new \omsmodel\LogSellerModel;
        $ModelInActive  = $LogSellerModel::where('active',1);

        $Total = 0;
        $User  = [];

        if($SellerId > 0){
            $Model          = $Model->where('seller_id',$SellerId);
            $ModelInActive  = $ModelInActive->where('seller_id',$SellerId);
        }

        if(!empty($listUID)){
            $Model          = $Model->whereIn('user_id',$listUID);
            $ModelInActive  = $ModelInActive->whereIn('user_id',$listUID);
        }

        if(!empty($TimeStart)){
            $Model  = $Model->where(function($query) use($TimeStart){
                $query->where(function($q) use($TimeStart){
                    $q->where('first_time_incomings','>=', $TimeStart);
                })->orWhere(function($q) use($TimeStart){
                        $q->where('first_time_incomings',0)
                        ->where('first_time_pickup','>=', $TimeStart);
                });
            });

            $ModelInActive  = $ModelInActive->where(function($query) use($TimeStart){
                $query->where(function($q) use($TimeStart){
                    $q->where('first_time_incomings','>=', $TimeStart);
                })->orWhere(function($q) use($TimeStart){
                    $q->where('first_time_incomings',0)
                        ->where('first_time_pickup','>=', $TimeStart);
                });
            });
        }

        if(!empty($TimeEnd)){
            $Model  = $Model->where(function($query) use($TimeEnd){
                $query->where(function($q) use($TimeEnd){
                    $q->where('first_time_incomings','>', 0)
                      ->where('first_time_incomings','<', $TimeEnd);
                })->orWhere(function($q) use($TimeEnd){
                    $q->where('first_time_incomings',0)
                        ->where('first_time_pickup','<', $TimeEnd);
                });
            });

            $ModelInActive  = $ModelInActive->where(function($query) use($TimeEnd){
                $query->where(function($q) use($TimeEnd){
                    $q->where('first_time_incomings','>', 0)
                        ->where('first_time_incomings','<', $TimeEnd);
                })->orWhere(function($q) use($TimeEnd){
                    $q->where('first_time_incomings',0)
                        ->where('first_time_pickup','<', $TimeEnd);
                });
            });
        }

        $Data           = [];
        $Model          = $Model->orderBy('first_time_pickup','DESC');
        $ModelInActive  = $ModelInActive->orderBy('first_time_pickup','DESC');
        
        if($cmd == 'EXPORT'){
            return $this->ExportMerchant($Model, $ModelInActive);
        }
        
        $ModelTotal = clone $Model;

        $Total = $ModelTotal->count();
        if($Total > 0){
            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model      = $Model->skip($offset)->take($itemPage);
            }
            $Data   = $Model->get()->toArray();

            if(!empty($Data)){
                $ListUserId = [];
                foreach($Data as $val){
                    $ListUserId[]   = (int)$val['seller_id'];
                    $ListUserId[]   = (int)$val['user_id'];
                }

                if(!empty($ListUserId)){
                    $ListUserId = array_unique($ListUserId);
                    $UserModel  = new User;
                    $ListUser    = $UserModel::whereIn('id',$ListUserId)->get(array('id','email','fullname','phone'))->toArray();
                    if(!empty($ListUser)){
                        foreach($ListUser as $val){
                            $User[(int)$val['id']]  = $val;
                        }
                    }

                }
            }
        }

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'item_page' => $itemPage,
            'total'     => $Total,
            'data'      => $Data,
            'user'      => $User
        );

        return Response::json($contents);
    }

    public function ExportMerchant($Model, $ModelInActive){
        $SellerId  = Input::has('seller')          ? (int)Input::get('seller')              : 0;
        $Data           = $Model->get()->toArray();
        $DataInactive   = $ModelInActive->get()->toArray();

        if(!empty($DataInactive)){
            $Data   = array_merge($Data, $DataInactive);
        }

        if(!empty($Data)){
            $ListUserId = [];
            foreach($Data as $val){
                $ListUserId[]   = (int)$val['seller_id'];
                $ListUserId[]   = (int)$val['user_id'];
            }

            if(!empty($ListUserId)){
                $ListUserId = array_unique($ListUserId);
                $UserModel  = new User;
                $ListUser    = $UserModel::whereIn('id',$ListUserId)->get(array('id','email','fullname','phone'))->toArray();
                if(!empty($ListUser)){
                    foreach($ListUser as $val){
                        $User[(int)$val['id']]  = $val;
                    }
                }
            }
        }

        $SellerInfo = $User[$SellerId];
        $FileName   = 'Khach_Hang_'.explode('@', $SellerInfo['email'])[0];
        
        return Excel::create($FileName, function($excel) use($Data, $User, $SellerInfo){
            $excel->sheet('Sheet1', function($sheet) use($Data, $User, $SellerInfo){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách khách hàng '.$SellerInfo['fullname']));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B' =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,'H'     =>  30,
                    'I'  => 30, 'J'  => 30
                ));

                $sheet->row(3, array(
                    'STT', 'Họ tên', 'Email', 'Số điện thoại', 'Thời gian', 'Tính doanh thu từ', 'Vận đơn lấy đầu tiên', 'Vận đơn lấy cuối cùng', 'Doanh thu đầu tháng', 'Doanh thu lũy kê'
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
                    $dataExport = array(
                        $i++,
                        isset($User[$val['user_id']]) ? $User[$val['user_id']]['fullname'] : "",
                        isset($User[$val['user_id']]) ? $User[$val['user_id']]['email'] : "",
                        isset($User[$val['user_id']]) ? $User[$val['user_id']]['phone'] : "",
                        $val['time_create'] > 0 ? date("d/m/y H:m",$val['time_create']) : '',
                        $val['first_time_incomings'] > 0 ? date("d/m/y H:m",$val['first_time_incomings']) : '',
                        $val['first_time_pickup'] > 0 ? date("d/m/y H:m",$val['first_time_pickup']) : '',
                        $val['last_time_pickup'] > 0 ? date("d/m/y H:m",$val['last_time_pickup']) : '',
                        isset($val['total_firstmonth']) ? number_format($val['total_firstmonth']) : '',
                        isset($val['total_nextmonth']) ? number_format($val['total_nextmonth']) : '',
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');


    }

    private function ResponseData(){
        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'user'          => $this->user,
            'object'        => $this->object
        ]);
    }
}
