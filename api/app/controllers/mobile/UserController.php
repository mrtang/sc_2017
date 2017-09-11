<?php namespace mobile;

use ordermodel\OrdersModel;
use sellermodel\UserInfoModel;
use omsmodel\SellerModel;
use Validator;
use Input;
use User;
use Response;
use Cache;
use Config;



class UserController extends \BaseController {

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
        $Data = $Model::where('id',$Id)
            ->with(['city', 'district'])
            ->first(array('id', 'fullname', 'group_id', 'password',  'email', 'fullname', 'phone', 'city_id', 'district_id', 'address', 'profile_id'))
            ->toArray();

        if(!($Data['password'])){
            $Data['is_fb'] = true;
        }else {
            $Data['is_fb'] = false;
        }
        unset($Data['password']);

        $Data['integration'] = array();
        $Data['_address'] = "";

        if(!empty($Data['city']) && !empty($Data['district'])){
            $Data['_address'] = $Data['district']['district_name'].', '.$Data['city']['city_name'];
        }
        if(!empty($Data['profile_id'])){

            try {
                $fbInfo = $fb->api('/'.$Data['profile_id']);

                $Data['integration']['name']        =  $fbInfo['name'];
                $Data['integration']['profile_id']  =  $fbInfo['id'];

                $Data['integration']['link']        =  'http://facebook.com/'.$fbInfo['id'];

            } catch (Exception $e) {
                var_dump($e);die;
            }

        }

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $Data
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



    public function postChangePass(){
        $UserInfo   = $this->UserInfo();


        /**
        *  Validation params
        * */
  
        // check params
 
        $validation = Validator::make(Input::all(), array(
            'password_current'  => 'required',
            'password_new'      => 'required|different:password_current|same:password_verify',
            'password_verify'   => 'required|same:password_new'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages(), 'error_message'=> 'Quý khách vui lòng nhập đầy đủ thông tin !'));
        }
        
        /**
         * Get Data 
         * */
        $PassCurrent        = Input::get('password_current');
        $PassNew            = Input::get('password_new');
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
                        'message'   => 'password current incorrect',
                        'error_message' => 'Mật khẩu không đúng, vui lòng thử lại'
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
                    'error'           => true,
                    'message'         => 'Bạn chưa nhập mã xác nhận',
                    'error_message'   => 'Bạn chưa nhập mã xác nhận'

                ]);
            }

            //Check Code
            $Security   = \sellermodel\SecurityLayersModel::where('user_id', $UserInfo['id'])->where('type',2)
                ->where('active',1)->where('time_create','>=',$this->time() - 3600)->where('code',$Code)->first();

            if(!isset($Security->id)){
                return Response::json([
                    'error'     => true,
                    'message'   => 'Mã bảo mật không chính xác hoặc đã quá hạn',
                    'error_message'   => 'Mã bảo mật không chính xác hoặc đã quá hạn'

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
                'message'   => 'Cập nhật thất bại',
                'error_message'   => 'Cập nhật thất bại'

            ]);
        }

        return Response::json([
            'error'     => false,
            'message'   => 'Thành công',
            'error_message'   => 'Thành công',
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
        $id         = (int)$UserInfo['id'];
        
        /**
        *  Validation params
        * */
  
        // check params
 
        $validation = \Validator::make(Input::all(), array(
        'fullname'          => 'sometimes|required',
        
        'email'             => 'sometimes|required|email|unique:users,email,'.$id.',id',
        
        'phone'             => 'sometimes|required|numeric',
        
        'city_id'           => 'sometimes|required|numeric|exists:lc_city,id',
        
        'district_id'       => 'sometimes|required|numeric|exists:lc_district,id,city_id,'.Input::get('city_id'),
        
        'address'           => 'sometimes|required',
        // Added by thinhnv
        'email_notice'      => 'sometimes|email',
        'password_current'  => 'sometimes',
        
        'password_new'      => 'sometimes',
        
        'password_verify'   => 'sometimes'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }


        /**
         * Get Data 
         * */
         
        $FullName           = Input::get('fullname');
        $Email              = Input::get('email');
        $Phone              = Input::get('phone');
        $CityId             = Input::get('city_id');
        $DistrictId         = Input::get('district_id');
        $Address            = Input::get('address');

        $EmailNotice        = Input::get('email_notice');
        $PhoneNotice        = Input::get('phone_notice');
        $FacebookNotice     = Input::get('facebook_notice');
        $TypeNotice         = Input::get('notice_type');

        $PassCurrent        = Input::get('password_current');
        $PassNew            = Input::get('password_new');
        
        $Model     = new User;
        $User      = $Model->find($id);
        
        if(isset($FullName))        $User->fullname         = $FullName;
        //if(isset($Email))           $User->email            = $Email;
        if(isset($Phone))           $User->phone            = $Phone;
        if(isset($CityId))          $User->city_id          = $CityId;
        if(isset($DistrictId))      $User->district_id      = $DistrictId;
        if(isset($Address))         $User->address          = $Address;


        

        
        //$userInfoModel = \sellermodel\UserInfoModel::where('user_id', $id)->first();

        

        if($EmailNotice){
            $transportEmail = \UserConfigTransportModel::where('user_id', $id)->where('transport_id', 2)->first();
            if(!$transportEmail){
                $transportEmail = new \UserConfigTransportModel();
            }
            $transportEmail['received']       = $EmailNotice;
            $transportEmail['user_id']        = $id;
            $transportEmail['transport_id']   = 2;
            $transportEmail['active']         = (isset($TypeNotice['email']) && $TypeNotice['email'] == true) ? 1 : 0;
            $transportEmail->save();
        }

        if($PhoneNotice){
            $transportPhone = \UserConfigTransportModel::where('user_id', $id)->where('transport_id', 1)->first();
            if(!$transportPhone){
                $transportPhone = new \UserConfigTransportModel();

            }
            
            $transportPhone->received       = $PhoneNotice;
            $transportPhone->user_id        = $id;
            $transportPhone->transport_id   = 1;
            $transportPhone->active         = (isset($TypeNotice['sms']) && $TypeNotice['sms'] == true) ? 1 : 0;
            $transportPhone->save();
            
            
        }

        if(isset($TypeNotice['facebook'])){
            $transportFacebook = \UserConfigTransportModel::where('user_id', $id)->where('transport_id', 4)->first();

            if($transportFacebook){
                $transportFacebook->active = (isset($TypeNotice['facebook']) && $TypeNotice['facebook'] == true) ? 1 : 0;
                $transportFacebook->save();
                
            }elseif (!empty($User->profile_id)) {
                $transportEmail = new \UserConfigTransportModel();

                $transportEmail['received']       = $User->profile_id;
                $transportEmail['user_id']        = $id;
                $transportEmail['transport_id']   = 4;
                $transportEmail['active']         = (isset($TypeNotice['facebook']) && $TypeNotice['facebook'] == true) ? 1 : 0;
                $transportEmail->save();
            }
        }


        if(isset($PassCurrent) && !empty($PassNew)){
            if($PassCurrent == $PassNew){
                $contents = array(
                    'error'     => true,
                    'message'   => 'Mật mới trùng với mật khẩu cũ, vui lòng thử lại'
                );
                return Response::json($contents);
            }
            if(!empty($User->password)){
                if($User->password == md5(md5($PassCurrent))){
                    $User->password = md5(md5($PassNew));
                }else{
                    $contents = array(
                        'error' => true,
                        'message' => 'Mật khẩu hiện tại không đúng, vui lòng thử lại'
                    );
                    return Response::json($contents);
                }
                
            }else{
                $User->password = md5(md5($PassNew));
            }
        }
        
        $Update = $User->save();
            
        if($Update){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $User->id
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'update error',
                'data'      => array()
            );
        }
        
        return Response::json($contents);
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

    
    public function getUserByPhone(){
        $Phone = Input::has('phone') ? Input::get('phone') : "";
        if (empty($Phone)) {
            return Response::json(['error'=> false, 'error_message'=> "", 'data'=> []]);
        }
        $User = User::where('phone', $Phone)
                ->select(['id', 'email', 'phone', 'fullname', 'identifier'])
                ->first();

        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => $User
        ]);
    }


    /**
     * Get notice app by user.
     *
     * @return Response
     */
    public function getNotifications(){
        $start = time() - 30*86400;
        $UserInfo   = $this->UserInfo();
        $Os = Input::has('os') ? Input::get('os') : "NULL";

        $Cmd = Input::has('cmd') ? Input::get('cmd') : "";

        if(!empty($Cmd) && $Cmd == 'demo' && $Os == 'ios'){
            $contents = array(
                'error'         => false,
                'error_message' => '',
                'data'          => [
                    [
                        "message" => "Nooij dung tssettss",
                        "data"    => [
                            "url"  => "http://dantri.com.vn",
                            "type" => "news"
                        ],
                        "id" => 911673,
                        "time_create" => 1453349027
                    ],
                    [
                        "message" => "Nội dung test 2",
                        "data"    => [
                            "type" => "news"
                        ],
                        "id" => 911672,
                        "time_create" => 1453349027
                    ],
                    [
                        "message" => "Đơn hàng SC51234823799 lấy hàng thất bại lý do không có hàng lấy !",
                        "data"    => [
                            'tracking_code' => 'SC51234823799',
                            'type'          => 'order_process',
                            'action'        => 'delivery',
                        ],
                        "id" => 911671,
                        "time_create" => 1453349027
                    ],
                    [
                        "message" => "Đơn hàng SC51234823711 lấy hàng thất bại lý do không có hàng lấy !",
                        "data"    => [
                            'tracking_code' => 'SC51234823799',
                            'type'          => 'order_process',
                            'action'        => 'pickup',
                        ],
                        "id" => 911621,
                        "time_create" => 1453349027
                    ],
                    [
                        "message" => "Đơn hàng SC51234823722 lấy hàng thất bại lý do không có hàng lấy !",
                        "data"    => [
                            'tracking_code' => 'SC51234823799',
                            'type'          => 'order_process',
                            'action'        => 'overweight',
                        ],
                        "id" => 911631,
                        "time_create" => 1453349027
                    ]
                ]
            );

            return Response::json($contents);
        }


        if(!empty($Cmd) && $Cmd == 'demo' && $Os == 'android'){
            $contents = array(
                'error'         => false,
                'error_message' => '',
                'data'          => [
                    [
                        "data"    => [
                            "message" => "Nooij dung tssettss",
                            "url"  => "http://dantri.com.vn",
                            "type" => "news"
                        ],
                        "id" => 911673,
                        "time_create" => 1453349027
                    ],
                    [
                        
                        "data"    => [
                            "message" => "Nội dung test 2",
                            "type" => "news"
                        ],
                        "id" => 911672,
                        "time_create" => 1453349027
                    ],
                    [
                        
                        "data"    => [
                            "message" => "Đơn hàng SC51234823799 lấy hàng thất bại lý do không có hàng lấy !",
                            'tracking_code' => 'SC51234823799',
                            'type'          => 'order_process',
                            'action'        => 'delivery',
                        ],
                        "id" => 911671,
                        "time_create" => 1453349027
                    ],
                    [
                        
                        "data"    => [
                            "message" => "Đơn hàng SC51234823711 lấy hàng thất bại lý do không có hàng lấy !",
                            'tracking_code' => 'SC51234823799',
                            'type'          => 'order_process',
                            'action'        => 'pickup',
                        ],
                        "id" => 911621,
                        "time_create" => 1453349027
                    ],
                    [
                        
                        "data"    => [
                            "message" => "Đơn hàng SC51234823722 lấy hàng thất bại lý do không có hàng lấy !",
                            'tracking_code' => 'SC51234823799',
                            'type'          => 'order_process',
                            'action'        => 'overweight',
                        ],
                        "id" => 911631,
                        "time_create" => 1453349027
                    ]
                ]
            );

            return Response::json($contents);
        }


        if(!$UserInfo){
            $contents = array(
                'error'         => true,
                'error_message' => 'Not Login!!',
                'data'          => ''
            );
            return Response::json($contents);
        }
        $output = array();
        $data = \QueueModel::where('user_id',$UserInfo['id'])->where('transport_id',5)->where('time_create','>',$start)->orderBy('time_create','DESC')->where('os_device', $Os)->get()->toArray();
        if(!empty($data)){
            foreach($data AS $one){
                $item = json_decode($one['data'],1);
                unset($item['device_token']);
                $item['id']          = $one['id'];
                $item['time_create'] = $one['time_create'];
                if(empty($item['data']['type'])){
                    $item['data']['type'] = 'news';
                }
                $output[] = $item;
            }
            $contents = array(
                'error'           => false,
                'error_message'   => '',
                'data'            => $output
            );
            return Response::json($contents);
        }else{
            $contents = array(
                'error'         => false,
                'error_message' => 'Not data!!',
                'data'          => []
            );
            return Response::json($contents);
        }
    }

    
}
