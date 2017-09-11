<?php

use ordermodel\OrdersModel;
use sellermodel\UserInfoModel;
use omsmodel\SellerModel;
use sellermodel\VimoModel;

class UserController extends \BaseController {
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
        $page       = Input::has('page') ? (int)Input::get('page') : 1;
        $itemPage   = Input::has('item_page') ? trim(Input::get('item_page')) : 20;
        $search     = Input::has('search') ? strtoupper(trim(Input::get('search'))) : '';

        $date = new DateTime('now');
        $date->modify('first day of this month');
        $firstDayString = $date->format('Y-m-d') . " 00:00:00";
        $date->modify('last day of this month');
        $lastDayString = $date->format('Y-m-d') . " 23:59:59";

        $startMonth = strtotime($firstDayString);
        $endMonth = strtotime($lastDayString);
        $totalNewCustomerInMonth = User::where('time_create','>=',$startMonth)->where('time_create','<=',$endMonth)->count();


        $offset     = ($page - 1)*$itemPage;
        $UserInfo   = $this->UserInfo();
        $User   = new User;
        $checkId = preg_match('/^@([0-9]+)$/',$search);
        if(!empty($search)){
            if(preg_match('/^SC\d+$/i',$search)){
                $Order = OrdersModel::where(function($query) {
                    $query->where('time_accept','>=', $this->time() - 86400*90)
                        ->orWhere('time_accept',0);
                })->where('tracking_code', $search)->get(array('tracking_code','from_user_id'));

                $ListUserId = array(0);
                $UserId     = array();

                if(!empty($Order)){
                    foreach($Order as $val){
                        $ListUserId[]                   = (int)$val['from_user_id'];
                        $UserId[$val['from_user_id']]   = $val['tracking_code'];
                    }
                }

                $User   = $User->whereIn('id',$ListUserId);
            }elseif($checkId == 1){
                $UserId = explode('@', $search);
                if($UserId[1] > 0){
                    $User   = $User->where('id',$UserId[1]);
                }
            }else{
                $User = $User->where(function($query) use($search){
                    $query->where('email','LIKE','%'.$search.'%')
                        ->orWhere('fullname','LIKE','%'.$search.'%')
                        ->orWhere('phone','LIKE','%'.$search.'%');
                });
            }
        }
        
        $Total  = $User->count();
        
        if((int)$itemPage > 0){
            $User = $User->with('user_info')->skip($offset)->take($itemPage)->get(array('id','fullname','email','phone','time_create'))->toArray();
        }
        
        if(isset($ListUserId) && !empty($User)){
            foreach($User as $key => $val){
                $User[$key]['tracking_code']    =   $UserId[$val['id']];
            }
        }

        $contents = array(
            'error'     => false,
            'message'   => '',
            'total'     => $Total,
            'item_page' => $itemPage,
            'data'      => $User,
            'privilege' => $UserInfo['privilege'],
            'totalUser' =>  $totalNewCustomerInMonth
        );
        
        return Response::json($contents);
	}

    public function getView($userID) {

        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege'] == 0){
            $response = array(
                'error'         => true,
                'message'       => 'Không có quyền !',
                'privilege'     => 0
            );
        } else {
            $sender = User::find($userID);
            $sender->user_info = \sellermodel\UserInfoModel::where('user_id',$userID)->with(['bankInfo'])->first();
            $sender->merchants = \accountingmodel\MerchantModel::where('merchant_id',$userID)->first();
            $seller = \omsmodel\SellerModel::where('user_id',$userID)->first();

            //tỷ lệ chuyển hoàn của user
            $status = [61,66,52];
            $order = OrdersModel::where('time_accept','>=',$this->time() - $this->time_limit)->where('from_user_id',$userID)->whereIn('status',$status)->select([
                'status',
                DB::raw('COUNT(*) as total')
            ])->get();

            $phatThanhCong = 0;
            $daXNChuyenHoan = 0;
            $chuyenHoan = 0;
            if(!$order->isEmpty()) {
                foreach($order as $oneOrderReport) {
                    if($oneOrderReport->status == 61) {
                        $daXNChuyenHoan = $oneOrderReport->total;
                    } else if($oneOrderReport->status == 66) {
                        $chuyenHoan = $oneOrderReport->total;
                    } else {
                        $phatThanhCong = $oneOrderReport->total;
                    }
                }   
            }
            $sender->min_chuyenhoan = (($phatThanhCong + $chuyenHoan) > 0) ? $chuyenHoan/($phatThanhCong + $chuyenHoan) : 0;
            $sender->max_chuyenhoan = ($phatThanhCong + $chuyenHoan + $daXNChuyenHoan) ? ($chuyenHoan + $daXNChuyenHoan)/($phatThanhCong + $chuyenHoan + $daXNChuyenHoan) : 0;
            if(!empty($seller) && $seller->seller_id > 0) {
                $sellerID = $seller->seller_id;
                $sender->seller_manage = User::find($sellerID);
                $sender->seller_manage->time_create = $seller->time_create;
            }

            $response = array(
                'error'     =>  false,
                'message'   =>  'success',
                'data'      =>  $sender,
                'privilege' =>  $UserInfo['privilege']
            );
        }
        return Response::json($response);
    }

    //get notify by user
    public function getNotifybyuser(){
        $UserInfo   = $this->UserInfo();
        if(!$UserInfo){
            $contents = array(
                'error'     => true,
                'message'   => 'Not Login!!',
                'data'      => ''
            );
            return Response::json($contents);
        }
        $data = QueueModel::where('user_id',$UserInfo['id'])->where('transport_id',3)->orderBy('time_create','DESC')->take(10)->get(array('data','id','template_id','received','time_create'))->toArray();

        if($data){
            $arrId = $arrTemplate = $dataReturn = $output = array();
            $template = TemplateModel::where('id',15)->first();
            $content = $html = array();
            
            foreach($data AS $value){
                $info = json_decode($value['data'],1);
                $item['html'] = DbView::make($template)->with(['content' => $info['content']])->render();
                $item['time_create'] = $value['time_create'];
                $html[] = $item;
                $arrId[] = $value['id'];
            }
            //update view=1
            $update = QueueModel::whereIn('id',$arrId)->update(array('view' => 1));
            
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $html
            );
            return Response::json($contents);
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data!!',
                'data'      => ''
            );
            return Response::json($contents);
        }
    }

    /**
     * Get notice app by user.
     *
     * @return Response
     */
    public function getNoticeappuser(){
        $start = $this->time() - 7*86400;
        $UserInfo   = $this->UserInfo();
        if(!$UserInfo){
            $contents = array(
                'error'     => true,
                'message'   => 'Not Login!!',
                'data'      => ''
            );
            return Response::json($contents);
        }
        $output = array();
        $data = QueueModel::where('user_id',$UserInfo['id'])->where('transport_id',5)->where('time_create','>',$start)->orderBy('time_create','DESC')->toArray();
        if(!empty($data)){
            foreach($data AS $one){
                $item = json_decode($one['data'],1);
                unset($item['device_token']);
                $output[] = $item;
            }
            $contents = array(
                'error'     => true,
                'message'   => 'Success!!!',
                'data'          => $output
            );
            return Response::json($contents);
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data!!',
                'data'      => ''
            );
            return Response::json($contents);
        }
    }


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		//
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getShow()
	{
	    $UserInfo   = $this->UserInfo();
        $Id = (int)$UserInfo['id'];

        $fb = $this->facebook(Config::get('facebook.app_token'));
        
        
        $Model  = new User;
        $Data = $Model::where('id',$Id)->first(array('id', 'fullname', 'password',  'email', 'fullname', 'phone', 'country_id' , 'city_id', 'district_id', 'address', 'profile_id'))->toArray();
        $DataInfo = UserInfoModel::where('user_id',$Id)->first();
        $Data['currency'] = $DataInfo['currency'];

        if(!($Data['password'])){
            $Data['is_fb'] = true;
        }else {
            $Data['is_fb'] = false;
        }
        unset($Data['password']);

        $Data['integration'] = array();

        if(!empty($Data['profile_id'])){

            try {

                $fbInfo = $fb->api('/'.$Data['profile_id']);
                $Data['integration']['name']        =  $fbInfo['name'];
                $Data['integration']['profile_id']  =  $fbInfo['id'];

                $Data['integration']['link']        =  'http://facebook.com/'.$fbInfo['id'];
                
            } catch (Exception $e) {
                return Response::json(['error' => false, 'message' => 'error get profile facebook', 'data'        => $Data]);
            }

        }

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'        => $Data
        );
        
        return Response::json($contents);
	}

    public function getCheck(){
        $requestsPerHour = 60;
        // Rate limit by IP address
        $key = sprintf('api-user-id:%s', Request::getClientIp());
        // Add if doesn't exist - Remember for 1 hour
        \Cache::add($key, 0, 60);
        // Add to count
        $count = \Cache::increment($key);
        if( $count > $requestsPerHour )
        {
            $contents   = array('error' => true, 'message' => 'Rate limit exceeded');
            $statusCode = 403;
        }
        else{
            $User       = new User;
            $statusCode = 200;
            $contents   = array('error' => false, 'message' => 'Rate limit exceeded');
        }

        $header['X-Ratelimit-Limit']        = $requestsPerHour;
        $header['X-Ratelimit-Remaining']    = $requestsPerHour-(int)$count;
        $header['Access-Control-Allow-Origin'] = $this->domain;

        return Response::json($contents,$statusCode,$header)->setCallback(Input::get('callback'));
    }

    public function postEditInfo(){
        $UserInfo   = $this->UserInfo();

        $validation = Validator::make(Input::json()->all(), array(
            'fullname'          => 'sometimes|required',
            'phone'             => 'sometimes|required|numeric',
            'country_id'        => 'sometimes|required|numeric',
            'city_id'           => 'sometimes|required|numeric|exists:lc_city,id',
            //'district_id'       => 'sometimes|required|numeric|exists:lc_district,id,city_id,'.Input::json()->get('city_id'),
            'address'           => 'sometimes|required',
            'email_notice'      => 'sometimes|email',
        ));

        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $FullName           = Input::json()->get('fullname');
        $Phone              = Input::json()->get('phone');
        $CountryId          = Input::json()->get('country_id');
        $CityId             = Input::json()->get('city_id');
        $DistrictId         = Input::json()->get('district_id');
        $Address            = Input::json()->get('address');
        $EmailNotice        = Input::json()->get('email_notice');
        $TypeNotice         = Input::json()->get('notice_type');
        $Currency           = Input::json()->get('currency');

        $User               = User::find($UserInfo['id']);

        if(isset($FullName)){
            if ($User->fullname !== $FullName) {
                try {
                    $VimoModel = VimoModel::where('user_id', $UserInfo['id'])->update(['active'=> 0, 'time_update'=> time(), 'delete'=> 2, 'notify'=> 1, 'note'=> 'Khách hàng thay đổi tên']);
                } catch (\Exception $e) {
                    
                }
            }
            $User->fullname         = $FullName;
        }     
        if(isset($Phone)){
            if($UserInfo['phone'] != '' && $UserInfo['layers_security'] == 0 && $Phone != $UserInfo['phone']){
                return Response::json([
                    'error'     => true,
                    'message'   => 'Bạn cần bật chế độ bảo mật hai lớp để thay đổi số điện thoại!'
                ]);
            }
        }

        if(isset($Phone))           $User->phone            = $Phone;
        if(isset($CountryId))       $User->country_id       = $CountryId;
        if(isset($CityId))          $User->city_id          = $CityId;
        if(isset($DistrictId))      $User->district_id      = $DistrictId;
        if(isset($Address))         $User->address          = $Address;
        if(isset($Currency)){
            $Update = UserInfoModel::where('user_id',$UserInfo['id'])->update(array('currency' => $Currency));
        }

        if($EmailNotice){
            $transportEmail = \UserConfigTransportModel::where('user_id', $UserInfo['id'])->where('transport_id', 2)->first();
            if(!$transportEmail){
                $transportEmail = new \UserConfigTransportModel();
            }
            $transportEmail['received']       = $EmailNotice;
            $transportEmail['user_id']        = $UserInfo['id'];
            $transportEmail['transport_id']   = 2;
            $transportEmail['active']         = (isset($TypeNotice['email']) && $TypeNotice['email'] == true) ? 1 : 0;
            $transportEmail->save();
        }

        if(isset($TypeNotice['facebook'])){
            $transportFacebook = \UserConfigTransportModel::where('user_id', $UserInfo['id'])->where('transport_id', 4)->first();

            if($transportFacebook){
                $transportFacebook->active = (isset($TypeNotice['facebook']) && $TypeNotice['facebook'] == true) ? 1 : 0;
                $transportFacebook->save();

            }elseif (!empty($User->profile_id)) {
                $transportEmail = new \UserConfigTransportModel();

                $transportEmail['received']       = $User->profile_id;
                $transportEmail['user_id']        = $UserInfo['id'];
                $transportEmail['transport_id']   = 4;
                $transportEmail['active']         = (isset($TypeNotice['facebook']) && $TypeNotice['facebook'] == true) ? 1 : 0;
                $transportEmail->save();
            }
        }

        try{
            $User->save();
            //log thong tin thay doi
            $LMongo = new \LMongo;
            $DataLog = array(
                'user_id' => $UserInfo['id'],
                'old' => array(
                    "phone" => $UserInfo['phone'],
                    "fullname" => $UserInfo['fullname'],
                    'city_id'  => $UserInfo['city_id'],
                    'district_id' => $UserInfo['district_id'],
                    'address' => $UserInfo['address']
                ),
                'new' => array(
                    "phone" => $Phone,
                    "fullname" => $FullName,
                    'city_id'  => $CityId,
                    'district_id' => $DistrictId,
                    'address' => $Address
                ),
                'time_change' => time()
            );
            $LMongo::collection('log_user_change_info_account')->insert($DataLog);
        }catch (Exception $e){
            return Response::json([
                'error'     => true,
                'message'   => 'Cập nhật thất bại'
            ]);
        }

        return Response::json([
            'error'     => false,
            'message'   => 'Thành công'
        ]);
    }

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function postEdit()
	{
        $UserInfo   = $this->UserInfo();
        /**
        *  Validation params
        * */
  
        // check params
 
        $validation = Validator::make(Input::json()->all(), array(
        'password_current'  => 'sometimes',
        
        'password_new'      => 'sometimes|different:password_current|same:password_verify',
        
        'password_verify'   => 'sometimes|same:password_new'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }
        
        /**
         * Get Data 
         * */
        $PassCurrent        = Input::json()->get('password_current');
        $PassNew            = Input::json()->get('password_new');
        $Code               = Input::has('code')                ? trim(strtoupper(Input::get('code')))                   : '';

        $User               = User::find($UserInfo['id']);

        /*
         * Thay đổi sdt hoặc mật khẩu  kiểm tra xác nhận OPT
         */

        if(isset($PassCurrent) && !empty($PassNew)){
            if(!empty($User->password)){
                if($User->password == md5(md5($PassCurrent))){
                    $User->password = md5(md5($PassNew));
                    $User->time_change_password = $this->time();
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'password current incorrect'
                    );
                    return Response::json($contents);
                }

            }else{
                $User->password = md5(md5($PassNew));
                $User->time_change_password = $this->time();
            }
        }

        $userInfoModel = \sellermodel\UserInfoModel::where('user_id', $UserInfo['id'])->first(['layers_security']);
        if($userInfoModel->layers_security == 1){ // security layers on
            if(empty($Code)){
                return Response::json([
                    'error'     => true,
                    'message'   => 'Bạn chưa nhập mã xác nhận'
                ]);
            }

            //Check Code
            $Security   = \sellermodel\SecurityLayersModel::where('user_id', $UserInfo['id'])->where('type',2)
                ->where('active',1)->where('time_create','>=',$this->time() - 3600)->where('code',$Code)->first();

            if(!isset($Security->id)){
                return Response::json([
                    'error'     => true,
                    'message'   => 'Mã bảo mật không chính xác hoặc đã quá hạn'
                ]);
            }

            if($userInfoModel->layers_security == 1){
                $Security->active = 2;
                $Security->save();
            }
        }

        try{
            $User->save();
        }catch (Exception $e){
            return Response::json([
                'error'     => true,
                'message'   => 'Cập nhật thất bại'
            ]);
        }

        return Response::json([
            'error'     => false,
            'message'   => 'Thành công'
        ]);
	}

    public function getSyncPhone(){
        $Model     = new User;
        $Users      = $Model->where('sync2', 0)->take(1000)->get()->toArray();

        if(empty($Users)){
            return "EMPTY";
        }

        $InsertData = [];
        $ListUserId = [];
        foreach ($Users as $key => $value) {
            $ListUserId[] = $value['id'];
            $InsertData[] = [
                'user_id'           => $value['id'],
                'phone'             => $value['phone'],
                'old'               => "",
                'time_create'       => $this->time()
            ];
        }
        try {
            $LMongo             = new \LMongo;
            $LMongo::collection('log_user_change_phone')
            ->batchInsert($InsertData);
        } catch (Exception $e) {
            
        }

        User::whereIn('id', $ListUserId)->update(['sync2' => 1]);
        return "NEXT";

    }

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}


    public function postVip($userID) {
        $user   = User::find($userID);
        if(!empty($user)) {

            $userInfo         = \sellermodel\UserInfoModel::firstOrNew(['user_id'    =>  $userID]);
            $userInfo->is_vip = ($userInfo->is_vip == 1) ? 0 : 1;
            $userInfo->save();
            $response = [
                'error'   =>  false,
                'message' =>  'Cập nhật thành công'
            ];
        } else {
            $response = [
                'error' =>  true,
                'message'   =>  'Không thể cập nhật'
            ];
        }
        return Response::json($response);
    }

    public function postIdentifier($userID) {
        $user = User::find($userID);
        if(!empty($user)) {
            $user->identifier = Input::get('identifier');
            $user->save();
            $response = [
                'error' =>  false,
                'message'   =>  'Cập nhật thành công'
            ];
        } else {
            $response = [
                'error' =>  true,
                'message'   =>  'Tài khoản không tồn tại'
            ];
        }
        return Response::json($response);
    }

    public function getVip() {
        $itemsPerPage = Input::has('itemsPerPage') ? (int)Input::get('itemsPerPage') : 20;
        $currentPage    =   Input::has('currentPage') ? (int)Input::get('currentPage') : 1;
        $email          =   Input::has('email')         ?   Input::get('email') : '';

        if(!empty($email)) {
            $userID = (int)User::where('email',$email)->pluck('id');
            if($userID  == 0) {
                return Response::json([
                    'error' =>  true,
                    'message'   =>  'Không có dữ liệu'
                ]);
            }
        }
        $userInfoModel = new UserInfoModel();
        if(!empty($email)) {
            $userInfoModel = $userInfoModel->where('user_id',$userID);
        }
        $userInfoModel = $userInfoModel->where('is_vip',1);
        $totalUserModel = clone $userInfoModel;
        $listUserID = $userInfoModel->take($itemsPerPage)->skip(($currentPage-1)*$itemsPerPage)->lists('user_id');

        if(!empty($listUserID)) {

            $listSeller = SellerModel::whereIn('user_id',$listUserID)->get();
            $listSellerID = [];
            $listCSManage = [];
            if(!$listSeller->isEmpty()) {
                foreach($listSeller as $oneSellerID) {
                    $listSellerID[] = $oneSellerID->cs_id;
                    $listCSManage[$oneSellerID->user_id] = $oneSellerID->cs_id;
                }
            }
            if(!empty($listSellerID)) {
                $listSellerUser = User::whereIn('id',$listSellerID)->get();
                if(!$listSellerUser->isEmpty()) {
                    foreach($listSellerUser as $oneSellerUser) {
                        foreach($listCSManage as $k => $oneCS) {
                            if($oneCS === $oneSellerUser->id) {
                                $listCSManage[$k] = $oneSellerUser;
                            }
                        }
                    }
                }
            }
            $total = $totalUserModel->count();
            $user = User::whereIn('id',$listUserID)->take($itemsPerPage)->get();
            foreach($user as $k => $oneUser) {
                $user[$k]->cs = isset($listCSManage[$oneUser->id]) ? $listCSManage[$oneUser->id] : '';
            }
            $response = [
                'error' =>  false,
                'data'  =>  $user,
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

    public function getUserByPhone(){
        $Phone = Input::has('phone') ? Input::get('phone') : "";
        if (empty($Phone)) {
            return Response::json(['error'=> false, 'error_message'=> "", 'data'=> []]);
        }
        $User = User::where('phone', $Phone)->orWhere('phone2', $Phone)
                ->select(['id', 'email', 'phone', 'fullname', 'identifier'])
                ->first();

        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => $User
        ]);
    }

    public function getChildUsers(){
        $UserInfo   = $this->UserInfo();
        $ParentId   = $UserInfo['id'];

        $Model = UserInfoModel::where('parent_id', $ParentId)->with(['user'])->select(['id', 'user_id'])->get()->toArray();
        $this->_error           = false;
        $this->_error_message   = "";

        return $this->_ResponseData($Model);
    }

    public function getSuggest(){
        $query = Input::has('query') ? Input::get('query') : "";
        $Model = new User;

        if(filter_var($query,FILTER_VALIDATE_EMAIL)) {
            $Model = User::where('email', 'LIKE', $query);
        } else if(filter_var((int)$query,FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))) {
            $Model = User::where('phone', 'LIKE', $query);
        } else {
            $Model = User::where('email', 'LIKE', $query);
        }
        $Data = $Model->get();
        $Ret  = [];
        foreach ($Data as $key => $value) {
            $Ret[] = array(
                'id'    => $value['id'],
                'name'  => $value['email']
            );
        }
        return Response::json($Ret);
    }

    public function postUpdatePhone2 (){
        $phone      = Input::has('phone')   ? Input::get('phone') : "";
        $user_id    = Input::has('user_id') ? Input::get('user_id') : "";

        if(empty($phone) || empty($user_id)){
            return Response::json([
                'error'         => true,
                'error_message' => "Người dùng không tồn tại !",
                'data'          => [$phone, $user_id]
            ]);
        }

        $Model = User::where('id', $user_id)->first();
        if(empty($Model)){
            return Response::json([
                'error'         => true,
                'error_message' => "Người dùng không tồn tại !",
                'data'          => "1"
            ]);   
        }

        try {
            $Model->phone2 = $phone;
            $Model->save();
        } catch (Exception $e) {
            return Response::json([
                'error'         => true,
                'error_message' => "Lỗi kết nối dữ liệu",
                'data'          => ""
            ]);   
        }

        return Response::json([
            'error'         => false,
            'error_message' => "Cập nhật thành công",
            'data'          => ""
        ]);
    }
}
