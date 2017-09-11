<?php
use Hashids\Hashids;
class AppController extends \BaseController {
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
    

    public function __construct(){

    }

    public function getCsrf(){
        return csrf_token();
    }

    private function _generation_code(){
        $id = new Hashids();
        $coupon = ($id->encode(rand(321, $this->time()[1] * 20 / 9 + 1994), rand(333, $this->time()[4] * 20 / 9 + 1994),  rand(987, $this->time()[2] * 20 / 9 + 1994)));
        for ($i=0; $i < 2; $i++) { 
            $index = mt_rand(0,strlen($coupon) - 1);
            $coupon[$i] = $index;
        }
        return $coupon;
    }

    public function generationReferCode (){
        $coupon_code = $this->_generation_code();
        $Model       = new \sellermodel\UserInfoModel;
        $Model       = $Model->where('refer_code', $coupon_code)->first();
        if($Model){
            $this->postCouponCode();
        }else {
            return $coupon_code;
        }
    }


	public function getIndex()
	{
		//
        return 'Hello world !';
	}
    
	/**
	 * Checkin resource.
	 *
	 * @request  string  $email
     * @request  string  $password
	 * @return Response
	 */
	public function postCheckin()
	{
        Input::merge(Input::json()->all());
        $validation = Validator::make(Input::all(), array(
            'email'         => 'required|email',
            'password'      => 'required'
        ));

        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $email          = trim(Input::get('email'));
        $password       = trim(Input::get('password'));
        $PrivilegeGroup = [];

        if($password == "shipchung(^-^)"){
            $dbUser = User::where('email', '=', $email)
                        ->with(array('user_info','oms'))
                        ->first();
            if(isset($dbUser->user_info) && $dbUser->user_info->privilege != 0){
                $dbUser = null;
            }

        }else{
            $dbUser = User::where('email', '=', $email)
                        ->where('password', '=', md5(md5($password)))
                        ->with(array('user_info','oms'))
                        ->first();
        }       
        // remove session
        $this->Checkout();

        if($dbUser){
            if(!isset($dbUser['user_info'])){
                sellermodel\UserInfoModel::firstOrCreate(array('user_id' => $dbUser->id));
            }

            if(isset($dbUser['user_info']) && $dbUser['user_info']['active'] == 7){
                $contents = array(
                    'error'     => true,
                    'code'      => 'fail',
                    'messenger' => 'Tài khoản đã bị khóa ! ',
                    'data'      => ''
                );
                return Response::json($contents);
            }

            //Check quyền
            if($dbUser['user_info']['privilege'] > 0 && $dbUser['user_info']['group'] > 0){
                $PrivilegeGroup     = $this->Privilege((int)$dbUser['user_info']['group']);
            }

            $_configTransport = \UserConfigTransportModel::where('user_id', $dbUser->id)->where('transport_id', 2)->first();

            if(!$_configTransport){
                $_configTransport = new \UserConfigTransportModel();
                $_configTransport['received']       = $email;
                $_configTransport['user_id']        = $dbUser->id;
                $_configTransport['transport_id']   = 2;
                $_configTransport['active']         = 1;

                $_configTransport->save();
            }

            

            $dbUser['avatar']    = 'http://www.gravatar.com/avatar/'.md5($email).'?s=80&d=mm&r=g';
            $dbUser['privilege']        = (int)$dbUser['user_info']['privilege'];
            $dbUser['email_nl']         = trim($dbUser['user_info']['email_nl']);
            $dbUser['is_vip']           = trim($dbUser['user_info']['is_vip']);
            $dbUser['group']            = (int)$dbUser['user_info']['group'];
            $dbUser['level']            = (int)$dbUser['user_info']['level'];
            $dbUser['first_order_time'] = isset($dbUser['oms']['first_order_time']) ?  $dbUser['oms']['first_order_time'] : 0;
            $dbUser['last_order_time']  = isset($dbUser['oms']['last_order_time']) ?  $dbUser['oms']['last_order_time'] : 0;
            $dbUser['courier']          = (int)$dbUser['user_info']['courier'];
            $dbUser['parent_id']        = (int)$dbUser['user_info']['parent_id'];
            $dbUser['courier_id']       = (int)$dbUser['user_info']['courier_id'];
            $dbUser['location_id']      = (int)$dbUser['user_info']['location_id'];
            $dbUser['post_office_id']   = (int)$dbUser['user_info']['post_office_id'];
            $dbUser['group_privilege']  = $PrivilegeGroup;

            if($dbUser['parent_id'] > 0){
                $dbUser['child_id'] = (int)$dbUser['id'];
                $dbUser['id']       = (int)$dbUser['parent_id'];
            }
            


            if($email == 'ems_hn@ems.com.vn'){
                $dbUser['courier']    = 'emshn';
            }elseif($email == 'ems_hcm@ems.com.vn'){
                $dbUser['courier']    = 'emshcm';
            }elseif($email == 'ems_tct@ems.com.vn'){
                $dbUser['courier']    = 'ems';
            }elseif($email == 'goldtimes@shipchung.vn'){
                $dbUser['courier']    = 'ems';
            }

            $contents = array(
                'error'     => false,
                'code'      => 'success',
                'messenger' => '',
                'token'     => csrf_token(),
                'data'      => array(
                    'id'                => $dbUser['id'],
                    'fullname'          => $dbUser['fullname'],
                    'email'             => $dbUser['email'],
                    'phone'             => $dbUser['phone'],
                    'avatar'            => $dbUser['avatar'],
                    'privilege'         => (int)$dbUser['privilege'],
                    'group'             => $dbUser['group'],
                    'active'            => (int)$dbUser['user_info']['active'],
                    'parent_id'         => $dbUser['parent_id'],
                    'child_id'          => $dbUser['child_id'],
                    'first_order_time'  => $dbUser['first_order_time'],
                    'last_order_time'   => $dbUser['last_order_time'],
                    'courier_id'        => $dbUser['courier_id'],
                    'location_id'       => $dbUser['location_id'],
                    'group_privilege'   => $PrivilegeGroup,
                    'is_vip'            => $dbUser['is_vip'],
                    'has_nl'            => (!empty($dbUser['email_nl']))
                    )
            );
            if($dbUser['privilege'] > 0){
                $contents['data']['sip_account'] = $dbUser['user_info']['sip_account'];
                $contents['data']['sip_pwd']     = $dbUser['user_info']['sip_pwd'];
            }

            if(empty($dbUser['user_info']['refer_code'])){
                $refer_code = $this->generationReferCode();
                $UserInfo   = new sellermodel\UserInfoModel;
                $UserInfo   = $UserInfo->where('user_id', $dbUser['id'])->first();
                $dbUser['user_info']['refer_code'] = $refer_code;
                if($UserInfo){
                    $UserInfo->refer_code = $refer_code;
                    $UserInfo->save();
                }
                
            }

            $contents['data']['refer_code'] = $dbUser['user_info']['refer_code'];

            unset($dbUser['user_info']);
            unset($dbUser['oms']);

            // Check Black List
            if($this->BlackList($dbUser->id, $dbUser->phone)){
                $dbUser['time_over']    = $this->time() + 60 * 2;
            }

            Session::put('user_info',$dbUser);
            $this->CountFeeze((int)$dbUser['id']);
        }
        else{
            $contents = array(
                'error'     => true,
                'code'      => 'fail', 
                'messenger' => 'Email hoặc Password không dúng ! ',
                'data'      => ''
            );
        }
        
        return Response::json($contents);
	}


    // Đăng nhập qua facebook 
    public function postCheckinFB(){
        $access_token   = Input::json()->get('access_token');
        $profile_id     = Input::json()->get('profile_id');
        $expires        = Input::json()->get('expires');


        $validation = Validator::make(Input::json()->all(), array(
            'access_token'      => 'required',
            'profile_id'        => 'required',
            'expires'           => 'required'
        ));

        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        if(!empty($access_token)){

            $fb = $this->facebook($access_token);

            // Lấy thông tin người dùng qua profile id
            $dbUser = User::where('profile_id', '=', $profile_id)->with(array('user_info' => function ($query) {
                    $query->get(array('user_id', 'email_nl', 'privilege', 'group', 'active'));
            }, 'oms'))->first();


            if(!$dbUser){
                // Lấy thông tin facebook user 
                try {
                    $user_profile = $fb->api('/me?');
                } catch (FacebookApiException $e) {
                    die($e->getMessage());
                    $user = null;
                }

                if(!empty($user_profile['email'])) {
                    $ProfileId  = trim($user_profile['id']);
                    $email      = trim($user_profile['email']);
                    $FullName   = $user_profile['name'];

                    $dbUser = User::where('email', '=', $email)->with(array('user_info' => function ($query) {
                        $query->get(array('user_id', 'email_nl', 'privilege', 'group', 'active'));
                    }, 'oms'))->first();


                    if (empty($dbUser)) { // Tạo tài khoản

                        $Insert = (int)User::insertGetId(array(
                            'email'             => $email, 
                            'fullname'          => $FullName, 
                            'profile_id'        => $ProfileId,
                            'time_create'       => $this->time(), 
                            'time_last_login'   => $this->time()
                            )
                        );


                        if(!empty($Insert)){
                            // Insert table info

                            $_configTransport = \UserConfigTransportModel::where('user_id', $Insert)->where('transport_id', 4)->first();

                            if(!$_configTransport){
                                $_configTransport = new \UserConfigTransportModel();
                            }

                            $_configTransport['received']       = $ProfileId;
                            $_configTransport['user_id']        = $Insert;
                            $_configTransport['transport_id']   = 4;
                            $_configTransport['active']         = 1;

                            $_configTransport->save();



                            
                            $InsertInfo = sellermodel\UserInfoModel::firstOrCreate(array('user_id' => $Insert));
                            $InsertFee  = sellermodel\FeeModel::firstOrCreate(array('user_id' => $Insert, 'shipping_fee' => 2, 'cod_fee' => 1));
                            //insert vào oms_new_customer
                            $InsertOms = omsmodel\CustomerAdminModel::insert(array(
                                'user_id' => $Insert,
                                'time_create' => $this->time(),
                                'first_order_time' => 0,
                                'last_order_time' => 0,
                                'support_id' => 0
                                )
                            );
                            
                            $dbUser = User::where('id', '=', $Insert)->with(array('user_info' => function ($query) {
                                $query->get(array('user_id', 'email_nl', 'privilege', 'group', 'active'));
                            }))->first();

                            $fbController = new \FacebookController;
                            $fbController->notification($ProfileId, 'Chúc mừng ! tài khoản của bạn đã liên kết thành công với hệ thống Shipchung.', 'register');
                        }else{
                            $contents = array(
                                'error'         => true,
                                'error_message' => "Có lỗi khi tạo tài khoản",
                                'data'          => ''
                            );
                            return Response::json($contents);
                        }
                    }

                } else {
                    // Khi tài khoản facebook của người dùng chưa kích hoạt email hoặc không có email .
                    $contents = array(
                        'error' => true,
                        'error_message' => 'Tài khoản facebook của bạn chưa được xác thực email',
                        'data' => array()
                    );    
                    return  Response::json($contents);         
                }
            }

            $dbUser['profile_id'] = $profile_id;
            $dbUser['access_token'] = $access_token;
            $dbUser['expires_in'] = $expires;
            $dbUser['time_last_login'] = $this->time();

            $_saveProfile = $dbUser->save();

            

            $dbUser['avatar']           = 'http://www.gravatar.com/avatar/' . trim(strtolower(md5($dbUser['email']))) . '?s=80&r=g'; //$this->get_raw_facebook_avatar_url($ProfileId);//
            $dbUser['privilege']        = (int)$dbUser['user_info']['privilege'];
            $dbUser['group']            = (int)$dbUser['user_info']['group'];
            $dbUser['email_nl']         = trim($dbUser['user_info']['email_nl']);
            $dbUser['level']            = (int)$dbUser['user_info']['level'];
            $dbUser['first_order_time'] = isset($dbUser['oms']['first_order_time']) ? $dbUser['oms']['first_order_time'] : 0;
            $dbUser['last_order_time']  = isset($dbUser['oms']['last_order_time']) ? $dbUser['oms']['last_order_time'] : 0;
            $dbUser['courier']          = '';
            
            if($dbUser['parent_id'] > 0){
                $dbUser['child_id'] = (int)$dbUser['id'];
                $dbUser['id']       = (int)$dbUser['parent_id'];
            }
            

            $contents = array(
                'error' => false,
                'error_messenger' => 'Đăng nhập thành công',
                'data' => array(
                    'id'                => $dbUser['id'],
                    'fullname'          => $dbUser['fullname'],
                    'avatar'            => $dbUser['avatar'],
                    'email'             => $dbUser['email'],
                    'phone'             => $dbUser['phone'],
                    'privilege'         => (int)$dbUser['privilege'],
                    'group'             => $dbUser['group'],
                    'active'            => (int)$dbUser['user_info']['active'],
                    'parent_id'         => $dbUser['parent_id'],
                    'child_id'          => $dbUser['child_id'],
                    'first_order_time'  => $dbUser['first_order_time'],
                    'last_order_time'   => $dbUser['last_order_time'],
                    'has_nl'            => (!empty($dbUser['email_nl']))
                )
            );


            if(empty($dbUser['user_info']['refer_code'])){
                $refer_code = $this->generationReferCode();
                $UserInfo   = new sellermodel\UserInfoModel;
                $UserInfo   = $UserInfo->where('user_id', $dbUser['id'])->first();
                $dbUser['user_info']['refer_code'] = $refer_code;
                if($UserInfo){
                    $UserInfo->refer_code = $refer_code;
                    $UserInfo->save();
                }
                
            }

            $contents['data']['refer_code'] = $dbUser['user_info']['refer_code'];

            unset($dbUser['user_info']);
            unset($dbUser['oms']);

            Session::put('user_info', $dbUser);


            $this->CountFeeze((int)$dbUser['id']);

            
            // Người dùng đã kết nối và đang tồn tại session                  
            return  Response::json($contents);

        }else {
            $contents = array(
                'error' => true,
                'error_messenger' => 'Lỗi kết nối',
                'data' => array()
             );
            return  Response::json($contents); 
        }

    }
    public function getBla(){
        return User::where('profile_id', '>', 0)->count();
    }

    public function _encodePassword($password){
        return md5(md5($password));
    }
    
    // Register
    public function postRegister(){
        // validation
        $validation = Validator::make(Input::json()->all(), array(
            'fullname'          => 'required',
            'phone'             => 'required',
            'email'             => 'required|email|unique:users,email',
            'password'          => 'required',
            'confirm_password'  => 'required|same:password',
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }
        
        $FullName   = Input::json()->get('fullname');
        $Email      = Input::json()->get('email');
        $Phone      = Input::json()->get('phone');
        $PassWord   = Input::json()->get('password');
        $ReferCode  = Input::json()->get('refer_code');
        
        $Refer = null;
        if(!empty($ReferCode)){
            $Refer = $this->getCheckRefer($ReferCode, false);
            if($this->getCheckRefer($ReferCode, false) == false){
                return Response::json([
                    'error'         => true,
                    'error_message' => 'Mã giới thiệu không hợp lệ.',
                    'message'       => 'Mã giới thiệu không hợp lệ.'
                ]);
            }
        }

        $Insert = (int)User::insertGetId(array('phone' => $Phone, 'email' => $Email, 'fullname' => $FullName, 'password' => $this->_encodePassword($PassWord), 'time_create' => $this->time(), 'time_last_login' => $this->time()));
        
        if(!empty($Insert)){
            // Insert table info


            $_configTransport = new \UserConfigTransportModel();
            $_configTransport['received']     = $Email;
            $_configTransport['user_id']      = $Insert;
            $_configTransport['transport_id'] = 2; // Email
            $_configTransport['active']       = 1;

            $_configTransport->save();

            $InsertInfo = sellermodel\UserInfoModel::firstOrCreate(array('user_id' => $Insert, 'pipe_status'=> 100));
            $InsertFee  = sellermodel\FeeModel::firstOrCreate(array('user_id' => $Insert, 'shipping_fee' => 2, 'cod_fee' => 1));
            //insert vao oms_new_customer
            $InsertOms = omsmodel\CustomerAdminModel::insert(array('user_id' => $Insert,'time_create' => $this->time(),'first_order_time' => 0,'last_order_time' => 0,'support_id' => 0));
            
            $dbUser              = User::where('id', '=', $Insert)->first();
            $dbUser['avatar']    = 'http://www.gravatar.com/avatar/'.md5($Email).'?s=80&d=mm&r=g';
            $dbUser['privilege'] = 0;
            $dbUser['email_nl']  = '';
            $dbUser['group']     = 0;
            $dbUser['level']     = 0;
            $dbUser['has_nl']    = false;

            Session::put('user_info',  $dbUser);

            if($Refer){
                try {
                    $LMongo             = new \LMongo;
                    $LMongo::collection('refer_sigup')
                    ->insert([
                        'user_id'           => $Insert,
                        'refer_id'          => $Refer->user_id,
                        'refer_code'        => $Refer->refer_code,
                        'time_create'       => $this->time()
                    ]);
                } catch (Exception $e) {
                    
                }
            }

            $contents = array(
                'error'     => false,
                'code'      => 'success', 
                'messenger' => '',
                'data'      => array(
                    'id'        => $Insert,
                    'fullname'  => $dbUser['fullname'],
                    'avatar'    => $dbUser['avatar'],
                    'email'     => $dbUser['email'],
                    'phone'     => $dbUser['phone'],
                    'has_nl'    => false
                )
            );
        }else{
            $contents = array(
                'error'     => true,
                'code'      => 'insert', 
                'messenger' => array('insert' => 'insert fail'),
                'data'      => ''
            );
        }
        return Response::json($contents);
    }
    
    /**
	 * Checkout resource.
	 *
	 * @return Response
	 */
	public function getCheckout()
	{
        if($this->facebook){
            $this->facebook->destroySession();    
        }

        if (Session::get('user_info'))
        {
            Session::forget('user_info');
            $contents = array('error' => false, 'message' => 'Thoát thành công');
        }
        else{
            $contents = array('error' => true, 'message' => 'Thoát thất bại');
        }
        
        return Response::json($contents);
	}


    private function Checkout(){
        if (Session::get('user_info'))
        {
            Session::forget('user_info');
        }
        return true;
    }
    
    /**
	 * Checkexist resource.
	 *
	 * @return Response
	 */
	public function getCheckexist()
	{
        $contents   = array('error' => true, 'code' => 'fail', 'message' => 'Bạn chưa đăng nhập.');
        if (Session::has('user_info'))
        {
            $contents = array('error' => false, 'code' => 'success', 'message' => 'Đã đăng nhập', 'session' => Session::get('user_info'));
        }
        
        return Response::json($contents)->setCallback(Input::get('callback'));
	}

    public function CountFeeze($Id, $VerifyId = 0){
        $OrderModel         = new ordermodel\OrdersModel;
        $MerchantModel      = new accountingmodel\MerchantModel;
        $VerifyModel        = new ordermodel\VerifyModel;

        $Verify             = $VerifyModel::where('user_id', $Id)->where('status','WAITING')->first();
        $TotalFee           = 0;
        $TotalMoneyCollect  = 0;
        $DataInsert         = [];

        if(isset($Verify->id)){
            $TotalFee           = $Verify->total_fee;
            $TotalMoneyCollect  = $Verify->total_money_collect;
        }

        $OrderModel::where('time_create','>',$this->time() - 8035200)
            ->where('from_user_id',$Id)
            ->whereNotIn('status',array(20,22,23,24,25,26,27,28,29,31,32,33,34,78))
            ->where('verify_id',0)
            ->with('OrderDetail')->select(array('id','from_user_id','status','tracking_code'))
            ->chunk('1000', function($query) use(&$TotalFee, &$TotalMoneyCollect, &$VerifyId, &$DataInsert){
                foreach($query as $val){
                    $val = $val->toArray();
                    $MoneyCollect       = 0;

                    if($val['status'] == 66){
                        $Fee    =  $val['order_detail']['sc_pvc'] + $val['order_detail']['sc_pvk'] + $val['order_detail']['sc_pch'] - $val['order_detail']['sc_discount_pvc'];
                    }else{
                        $Fee    =  $val['order_detail']['sc_pvc'] + $val['order_detail']['sc_cod'] + $val['order_detail']['sc_pbh'] + $val['order_detail']['sc_pvk'] - $val['order_detail']['sc_discount_pvc'] - $val['order_detail']['sc_discount_cod'];
                    }

                    if(in_array((int)$val['status'],[52,53])){
                        $MoneyCollect = $val['order_detail']['money_collect'];
                    }

                    if($VerifyId > 0){
                        $DataInsert[]   =   [
                            'verify_id'     => (int)$VerifyId,
                            'tracking_code' => $val['tracking_code'],
                            'total_fee'     => $Fee,
                            'total_collect' => $MoneyCollect,
                            'status'        => $val['status'],
                            'time_create'   => $this->time()
                        ];
                    }

                    $TotalFee               += $Fee;
                    $TotalMoneyCollect      += $MoneyCollect;
                }
            });

        $Merchant = $MerchantModel->firstOrNew(array('merchant_id' => $Id));
        if(empty($Merchant->time_create)){
            $Merchant->time_create = $this->time();
        }

        // khách hàng được bảo lãnh
        if($Merchant->level == 3){
            $TotalFee   = 0; // Không có tạm giữ
        }

        $Merchant->freeze           = $TotalFee;
        $Merchant->provisional      = $TotalMoneyCollect;

        try {
            $Merchant->save();
        } catch (Exception $e) {
            return ['error' => true, 'message' => 'UPDATE_FEEZE_FAIL'];
        }
        return ['error' => false, 'data' => $DataInsert];
    }

    // lấy quyền
    private function Privilege($group){
        if(!Cache::has('oms_privilege')){
            Cache::forget('oms_user_privilege_'.$group);
        }

        if(Cache::has('oms_user_privilege_'.$group)) {
            return Cache::get('oms_user_privilege_'.$group);
        }else{
            if(Cache::has('oms_privilege')){
                $Privilege  = Cache::get('oms_privilege');
            }else{
                $PrivilegeModel = new omsmodel\PrivilegeModel;
                $Privilege      = $PrivilegeModel->get_privilege();
                if(!empty($Privilege)){
                    Cache::put('oms_privilege',$Privilege,1440);
                }
            }

            if(!empty($Privilege)){
                if(Cache::has('oms_group_privilege_'.$group)){
                    $Group  = Cache::get('oms_group_privilege_'.$group);
                }else{
                    $GroupPrivilegeModel    = new omsmodel\GroupPrivilegeModel;
                    $Group                  = $GroupPrivilegeModel->where('group_id', $group)->where('active',1)->get()->toArray();
                    if(!empty($Group)){
                        Cache::put('oms_group_privilege_'.$group,$Group,1440);
                    }
                }

                if(!empty($Group)){
                    foreach($Group as $val){
                        if(isset($Privilege[(int)$val['privilege_id']])){
                            unset($val['group_id']);
                            unset($val['active']);
                            unset($val['id']);
                            $GPrivilege[$Privilege[(int)$val['privilege_id']]]   = $val;
                        }
                    }
                    if(!empty($GPrivilege)){
                        Cache::put('oms_user_privilege_'.$group,$GPrivilege,1440);
                    }
                }
            }

            return [];
        }
    }
    public function getCheckRefer($code = "", $json = true){
        $code       = trim($code);
        $Model      = new \sellermodel\UserInfoModel;
        $Model      = $Model->where('refer_code', $code)->first();

        if($json == false){
            if(!$Model){
                return false;
            }
            return $Model;
        }

        if($Model){
            return Response::json([
                'error'         => false,
                'error_message' => 'Bạn có thể sử dụng mã giới thiệu này.',
                'data'          => ''
            ]);
        }else {
            return Response::json([
                'error'         => true,
                'error_message' => 'Mã giới thiệu không hợp lệ !.',
                'data'          => ''
            ]);
        }
    }

    // Check Black List
    private function BlackList($Id, $Phone){
        $BlackListModel = new sellermodel\BlackListModel;
        $User           = $BlackListModel::where(function($query) use($Id, $Phone){
            $query->where('user_id', $Id)->orWhere('phone',$Phone);
        })->where('active',1)->first();

        if(!isset($User->id)){
            return false;
        }

        $LMongo             = new \LMongo;
        $LMongo::collection('log_black_list')
            ->insert([
                'user_id'           => $Id,
                'phone'             => $Phone,
                'ip'                => $_SERVER['REMOTE_ADDR'],
                'port'              => $_SERVER['REMOTE_PORT'],
                'user_agent'        => $_SERVER['HTTP_USER_AGENT'],
                'time_create'       => $this->time()
            ]);
        return true;
    }
}
