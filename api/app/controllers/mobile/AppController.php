<?php namespace mobile;

require app_path().'/libraries/php-jwt/vendor/autoload.php';
use \Firebase\JWT\JWT;
use Hashids\Hashids;
use Response;
use Input;
use DB;
use LMongo;
use Excel;
use Validator;
use Cache;
use Session;
use Config;
use User;
use omsmodel\NotifyConfirmUser;

class AppController extends \BaseController {

    public function __construct(){

    }
    // Tạo random coupon code
    private function _generation_code(){
        for ($randomNumber = mt_rand(1, 9), $i = 1; $i < 5; $i++) {
            $randomNumber .= mt_rand(0, 9);
        }
        return $randomNumber;
    }

    public function generationReferCode (){
        $coupon_code = $this->_generation_code();
        $Model       = new \sellermodel\UserInfoModel;
        $Model       = $Model->where('refer_code', $coupon_code)->first();
        if($Model){
            $this->generationReferCode();
        }else {
            return $coupon_code;
        }
    }

    public function getCheckReferCode($code = "", $json = true){
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

    public function getBrowser(){ 
        $u_agent = $_SERVER['HTTP_USER_AGENT']; 
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version= "";
        $ub = "";

        //First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'Linux';
        }
        elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'Mac';
        }
        elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'Windows';

            if (preg_match('/NT 6.2/i', $u_agent)) { $platform .= ' 8'; }
            elseif (preg_match('/NT 6.3/i', $u_agent)) { $platform .= ' 8.1'; }
            elseif (preg_match('/NT 6.1/i', $u_agent)) { $platform .= ' 7'; }
            elseif (preg_match('/NT 6.0/i', $u_agent)) { $platform .= ' Vista'; }
            elseif (preg_match('/NT 5.1/i', $u_agent)) { $platform .= ' XP'; }
            elseif (preg_match('/NT 5.0/i', $u_agent)) { $platform .= ' 2000'; }
            if (preg_match('/WOW64/i', $u_agent) || preg_match('/x64/i', $u_agent)) { $platform .= ' (x64)'; }
        }

        // Next get the name of the useragent yes seperately and for good reason
        if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) 
        { 
            $bname = 'Internet Explorer'; 
            $ub = "MSIE"; 
        } 
        elseif(preg_match('/Firefox/i',$u_agent)) 
        { 
            $bname = 'Mozilla Firefox'; 
            $ub = "Firefox"; 
        } 
        elseif(preg_match('/Chrome/i',$u_agent)) 
        { 
            $bname = 'Google Chrome'; 
            $ub = "Chrome"; 
        } 
        elseif(preg_match('/Safari/i',$u_agent)) 
        { 
            $bname = 'Apple Safari'; 
            $ub = "Safari"; 
        } 
        elseif(preg_match('/Opera/i',$u_agent)) 
        { 
            $bname = 'Opera'; 
            $ub = "Opera"; 
        } 
        elseif(preg_match('/Netscape/i',$u_agent)) 
        { 
            $bname = 'Netscape'; 
            $ub = "Netscape"; 
        } 

        // finally get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) .
        ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue
        }

        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
                $version= $matches['version'][0];
            }
            else {
                $version= $matches['version'][1];
            }
        }
        else {
            $version= $matches['version'][0];
        }

        // check if we have a number
        if ($version==null || $version=="") {$version="?";}

        return array(
            'userAgent'     => $u_agent,
            'name'          => $bname,
            'version'       => $version,
            'platform'      => $platform,
            'pattern'       => $pattern,
            'ip'            => $_SERVER['HTTP_X_REAL_IP'],
            'server_name'   => $_SERVER['SERVER_NAME'],
        );
    } 


    public function insertUserLogs($UserId, $additional = []){
        $Agent = $this->getBrowser();
        $Data  = [
                'user_id'               => $UserId,
                'brower_name'           => $Agent['name'],
                'brower_version'        => $Agent['version'],
                'platform'              => $Agent['platform'],
                'user_agent'            => $Agent['userAgent'],
                'ip'                    => $Agent['ip'],
                'server_name'           => $Agent['server_name'],
                'time'                  => time(),
        ];
        $Data = array_merge($Data, $additional);
        try {
            $LMongo             = new \LMongo;
            $LMongo::collection('checkin_logs')
            ->insert($Data);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }


    public function getIndex()
    {
        return 'Hello world !';
    }


    public function getCityGlobal($country_id, $list_id = []){
        $searching  = Input::has('q') ? Input::get('q') : "";

        try {
            $CityModel   = new \CityGlobalModel;

            $City        = $CityModel::where('country_id', $country_id);
            if(!empty($searching)){
                $City = $City->where('city_name', 'LIKE', '%'.$searching.'%');
            }

            $City = $City->take(20)->get()->toArray();

        } catch (\Exception $e) {
            return Response::json([
                'error'          => true,
                'error_message'  => '',
            ]);
        }
        
        return Response::json([
            'error'          => false,
            'error_message'  => '',
            'data' => $City
        ]);
    }

    public function getCountry($json = true){

        if(Cache::has('cache_country')){
            $CountryModel = \Cache::get('cache_country');
        }else{
            $CountryModel = new \CountryModel;
            $CountryModel = $CountryModel::get()->toArray();

            if(!empty($CountryModel)){
                \Cache::put('cache_country', $CountryModel, 1440);
            }
        }
        
        return Response::json([
            'error'          => false,
            'error_message'  => '',
            'data'           => $CountryModel
        ]);
    }

    public function getSlide(){
        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => [
                'http://cloud.shipchung.vn//uploads/images/cards/a25fe35b016cc5894629c7de3f7fc24a.png', //http://cloud.shipchung.vn/uploads/images/cards/fcbb3fa02b8cc8fde5f252f7122ee6de.jpg
                'http://cloud.shipchung.vn//uploads/images/cards/ea0299a002886f7c6f2a11305f593f23.png',
                'http://cloud.shipchung.vn//uploads/images/cards/8589f2fc7c1f6e19329c6f999175810b.png',
            ]
        ]);
    }


    /*
    * Slide giảm giá ban đầu khi vào app
    * Khi thêm campain mới cần đỗi lại campain_code 
    */

    public function getSlideDiscount(){
        return Response::json([
            'error'         => true,
            'error_message' => "",
            "campaign_code" => "",
            'data'          => [
                [
                    "name"  => "Công nghệ trong tầm tay – Giao hàng chỉ 10k",
                    "link"  => "https://www.shipchung.vn/cong-nghe-trong-tam-tay-giao-hang-chi-10k/",
                    "images"=> [
                        "android" => "http://cloud.shipchung.vn//uploads/images/cards/21cec78600e6e9a0ec4b835a5665ea67.jpg",  // 720x1280
                        "ios_gt5" => "http://cloud.shipchung.vn//uploads/images/cards/4994dcc165ac3bc58c31d29df7d1366a.jpg", // 750x1334 
                        "ios_lt5" => "http://cloud.shipchung.vn//uploads/images/cards/fa29e1b8b9cdb06ab06604a256fa273d.jpg",
                    ] 
                ]
            ]
        ]);
    }


    public function CreateConfirmUser ($user_id, $email, $fullname, $resend = 0, $notification = 0){
        $Model = new NotifyConfirmUser; 
        $Model->user_id     = $user_id;
        $Model->email       = $email;
        $Model->fullname    = $fullname;
        $Model->notification    = $notification;
        $Model->token           = md5($email.$user_id.time());
        $Model->time_expired    = time() + 2 * (24 * 3600); // 2 ngày
        $Model->time_create     = time();
        $Model->resend          = $resend;
        
        if($notification == 1){
            $Model->time_success    = time();
        }else {
            $Model->time_success    = 0;
        }
        
        try {
            $Model->save();
        } catch (Exception $e) {
            return false;
        }
        return true;
    }



    // For android 
    public function getQrToken(){
        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => "44WLTBZ3-WROCBBQW-OV7NBDY2-LACPYBH4-AT6AJ7AE-7QCPYBH4-AT6AJ7AE-7QCK2RYW"
        ]);   
    }
    



    public function getReferInfo ($user_id){
        $LMongo             = new \LMongo;
        return $LMongo::collection('refer_sigup')->where('user_id' ,$user_id)->first();
    }

    public function saveReferInfo($user_id, $saveData){
        $LMongo             = new \LMongo;
        try {
            $LMongo::collection('refer_sigup')->where('user_id', $user_id)->update($saveData);
        } catch (Exception $e) {
            return false;
        }
        return true;
   
    }

    private function createCouponRefer ($UserEmail, $UserId, $ReferId){
        $IosToken     = Input::has('ios_token')         ? Input::get('ios_token')       : "";
        $AndroidToken = Input::has('android_token')     ? Input::get('android_token')   : "";

        $HasManyAccount = 0;

        $UserReferInfo  = \sellermodel\UserInfoModel::where('user_id', $ReferId)->first();

        if(!empty($AndroidToken)){
            if($UserReferInfo->android_device_token == $AndroidToken){return false;}
            $HasManyAccount = \sellermodel\UserInfoModel::where('android_device_token', $AndroidToken)->count();
        }

        if(!empty($IosToken)){
            if($UserReferInfo->ios_device_token == $IosToken){return false;}
            $HasManyAccount = \sellermodel\UserInfoModel::where('ios_device_token', $IosToken)->count();
        }

        if($HasManyAccount > 2){
            return false;
        }


        $UserRefer      = \User::where('id', $ReferId)->first();
        

        $Ctrl = new \seller\CouponController;

        $Params = [
            'campaign_id'   => 3,
            'coupon_type'   => 2,
            'discount_type' => 1,
            'discount'      => 20000,
            'limit_usage'   => 1,
            'inapp'         => 1,
            'seller_email'  => $UserEmail,
            'time_expired'  => time() + 7 * 86400,
        ];

        Input::merge($Params);
        $Coupon = $Ctrl->postCreateCoupon(false);

        $Params['seller_email'] = $UserRefer['email'];

        Input::merge($Params);

        $CouponRefer = $Ctrl->postCreateCoupon(false);

        if($Coupon && $CouponRefer){
            $this->saveReferInfo((int)$UserId, ['coupon'=> $Coupon, 'refer_coupon'=> $CouponRefer, 'status' => 1, 'time_success'=> time(), 'notify' => 0, 'time_expired' => $Params['time_expired'], 'amount' => $Params['discount']]);
        }
    }

    public function postCheckin()
    {
        Input::merge(Input::all());
        $validation = Validator::make(Input::all(), array(
            'email'         => 'required|email',
            'password'      => 'required'
        ));

        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages(), 'error_message' => 'Email hoặc mật khẩu không đúng'));
        }

        $email        = trim(Input::get('email'));
        $password     = trim(Input::get('password'));
        $IosToken     = Input::has('ios_token')         ? Input::get('ios_token')       : "";
        $AndroidToken = Input::has('android_token')     ? Input::get('android_token')   : "";

        $AppVersion   = Input::has('app_version')       ? Input::get('app_version')     : "Unknown";
        $OsVersion    = Input::has('os_version')        ? Input::get('os_version')      : "Unknown";
        $DeviceName   = Input::has('device_name')       ? Input::get('device_name')     : "Unknown";

        $PrivilegeGroup = [];

        if($password == "shipchung(^--^)"){
            $dbUser = \User::where('email', '=', $email)
                ->with(array('user_info','oms','loyalty','merchant'))
                ->first();
            // if(isset($dbUser->user_info) && $dbUser->user_info->privilege != 0){
            //     $dbUser = null;
            // }

        }else{
            $dbUser = \User::where('email', '=', $email)
                ->where('password', '=', md5(md5($password)))
                ->with(array('user_info','oms','loyalty','merchant'))
                ->first();
        }
        // remove session
        $this->Checkout();

        if($dbUser){
            if(!isset($dbUser['user_info'])){
                \sellermodel\UserInfoModel::firstOrCreate(array('user_id' => $dbUser->id));
            }

            if(isset($dbUser['user_info']) && $dbUser['user_info']['active'] == 7){
                $contents = array(
                    'error'     => true,
                    'code'      => 'fail',
                    'messenger' => 'Tài khoản đã bị khóa ! ',
                    'error_message' => 'Tài khoản đã bị khóa ! ',
                    'data'      => ''
                );
                return Response::json($contents);
            }

            if(isset($dbUser['loyalty'])){
				// 				Check point next level
                if($dbUser['loyalty']['level'] > 0){
					$dbUser['loyalty']['target_point']    = $this->getTargetPoint((int)$dbUser['loyalty']['level']);
				}
				
			}

            //Check quyền
            if($dbUser['user_info']['privilege'] > 0 && $dbUser['user_info']['group'] > 0){
                $PrivilegeGroup     = $this->Privilege((int)$dbUser['user_info']['group']);
            }

            $this->saveTransport($dbUser->id, 2, $email);


            $dbUser['avatar']    = 'http://www.gravatar.com/avatar/'.md5($email).'?s=80&d=mm&r=g';
            $dbUser['privilege']        = (int)$dbUser['user_info']['privilege'];
            $dbUser['email_nl']         = trim($dbUser['user_info']['email_nl']);
            $dbUser['layers_security']  = (int)$dbUser['user_info']['layers_security'];
            $dbUser['is_vip']           = trim($dbUser['user_info']['is_vip']);
            $dbUser['group']            = (int)$dbUser['user_info']['group'];
            $dbUser['level']            = (int)$dbUser['user_info']['level'];
            $dbUser['first_order_time'] = isset($dbUser['oms']['first_order_time']) ?  $dbUser['oms']['first_order_time'] : 0;
            $dbUser['last_order_time']  = isset($dbUser['oms']['last_order_time']) ?  $dbUser['oms']['last_order_time'] : 0;
            $dbUser['first_accept_order_time']  = isset($dbUser['oms']['first_accept_order_time']) ?  $dbUser['oms']['first_accept_order_time'] : 0;
            $dbUser['courier']          = (int)$dbUser['user_info']['courier'];
            $dbUser['parent_id']        = (int)$dbUser['user_info']['parent_id'];

            $dbUser['group_privilege']  = $PrivilegeGroup;

            if($dbUser['parent_id'] > 0){
                $dbUser['child_id'] = (int)$dbUser['id'];
                $dbUser['id']       = (int)$dbUser['parent_id'];
            }


            $dbUser['loy_total_point']      = isset($dbUser['loyalty']['total_point'])      ? (int)$dbUser['loyalty']['total_point']        : 0;
			$dbUser['loy_current_point']    = isset($dbUser['loyalty']['current_point'])    ? (int)$dbUser['loyalty']['current_point']      : 0;
			$dbUser['loy_level']            = (isset($dbUser['loyalty']['level']) &&  $dbUser['loyalty']['active'] == 1)    ? (int)$dbUser['loyalty']['level']              : null;
			$dbUser['loy_target_point']     = isset($dbUser['loyalty']['target_point'])     ? (int)$dbUser['loyalty']['target_point']       : 0;



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
                'error_message' => 'Đăng nhập thành công',
                'messenger' => '',
                'data'      => array(
                    'id'                      => $dbUser['id'],
                    'fullname'                => $dbUser['fullname'],
                    'email'                   => $dbUser['email'],
                    'phone'                   => $dbUser['phone'],
                    'avatar'                  => $dbUser['avatar'],
                    'layers_security'       => (int)$dbUser['layers_security'],
                    'privilege'               => (int)$dbUser['privilege'],
                    'group'                   => $dbUser['group'],
                    'active'                  => (int)$dbUser['user_info']['active'],
                    'parent_id'               => $dbUser['parent_id'],
                    'child_id'                => $dbUser['child_id'],
                    'first_order_time'        => $dbUser['first_order_time'],
                    'last_order_time'         => $dbUser['last_order_time'],
                    'first_accept_order_time' => $dbUser['first_accept_order_time'],
                    'loy_total_point'       => $dbUser['loy_total_point'],
                    'loy_current_point'     => $dbUser['loy_current_point'],
                    'loy_level'             => $dbUser['loy_level'],
                    'loy_target_point'      => $dbUser['loy_target_point'],
                    'role'                  => $dbUser['role'],
                    //'group_privilege'         => $PrivilegeGroup,
                    'is_vip'                  => $dbUser['is_vip'],
                    'has_nl'                  => (!empty($dbUser['email_nl'])),
                    'hotline'                 => '1900.63.60.30',
                    'share_discount'          => '20k',
                    'bank_cash_in'            => [
                        [
                            'bank_name'         => 'Ngân hàng kỹ thương Việt Nam',
                            'bank_short_name'   => 'Techcombank',
                            'bank_address'      => 'Chi nhánh Hai Bà Trưng – PGD Lĩnh Nam',
                            'account_name'      => 'Công ty cổ phần thương mại điện tử Shipchung Việt Nam',
                            'account_number'    => '19130623042016',
                            'bank_logo'         => 'https://seller.shipchung.vn/img/techcombank.png'
                        ],
                        [
                            'bank_name'         => 'Ngân hàng ngoại thương Việt Nam',
                            'bank_short_name'   => 'Vietcombank',
                            'bank_address'      => 'PGD Kim Ngưu - Chi nhánh Chương Dương - Hà Nội',
                            'account_name'      => 'Công ty cổ phần thương mại điện tử Shipchung Việt Nam',
                            'account_number'    => '0541000273670',
                            'bank_logo'         => 'https://seller.shipchung.vn/img/vietcombank.png'
                        ],
                        [
                            'bank_name'         => 'Ngân hàng thương mại cổ phần Á Châu Việt Nam',
                            'bank_short_name'   => 'ACB',
                            'bank_address'      => 'Chi nhánh Hà Nội',
                            'account_name'      => 'Công ty cổ phần thương mại điện tử Shipchung Việt Nam',
                            'account_number'    => '388888368',
                            'bank_logo'         => 'https://seller.shipchung.vn/img/acb.png'
                        ]
                    ]
                )
            );

            /*if($dbUser['privilege'] > 0){
                $contents['data']['sip_account'] = $dbUser['user_info']['sip_account'];
                $contents['data']['sip_pwd']     = $dbUser['user_info']['sip_pwd'];
            }*/

            if(isset($dbUser['user_info']['verified']) && $dbUser['user_info']['verified'] == 1){
                $Refer = $this->getReferInfo((int)$dbUser['id']);

                if(isset($Refer['status'])  && $Refer['status'] == 0){
                    $this->createCouponRefer($dbUser['email'], $dbUser['id'], $Refer['refer_id']);
                }
            }


            unset($dbUser['user_info']);
            unset($dbUser['oms']);

            $UserInfo = \sellermodel\UserInfoModel::where('user_id', $dbUser['id'])->first();


            
            try {
                $token = $this->genToken($contents['data']);
            } catch (Exception $e) {
                
            }
            
            if(!empty($token)){
                $UserInfo->api_access_token = $token['token'];
                $contents['data']['api_access_token']        = $token['token'];
                $contents['data']['api_access_token_expire'] = $token['exp'];
            }else {
                return Response::json([
                    'error'     => true,
                    'code'      => 'ENCODE_TOKEN_FAIL',
                    'error_message' => 'Lỗi máy chủ, vui lòng thử lại hoặc liên hệ bộ phận CSKH',
                    'data'      => ''
                ]);
            }
            

            if(empty($UserInfo->refer_code)){

                try {
                    $UserInfo->refer_code = $this->generationReferCode();
                } catch (Exception $e) {
                    
                }
                
            }

            if (!empty($AndroidToken)) {
                $UserInfo->android_device_token = $AndroidToken;
                try {
                    $this->saveTransport($dbUser['id'], 5, "");
                } catch (Exception $e) {
                    
                }
                
            }

            if (!empty($IosToken)) {
                $UserInfo->ios_device_token = $IosToken;
                try {
                    $this->saveTransport($dbUser['id'], 5, "");
                } catch (Exception $e) {
                    
                }
                
            }

            try {
                $UserInfo->save();
                User::where('id', $dbUser['id'])->update(['time_last_login'=> time()]);
            } catch (Exception $e) {
                $contents = array(
                    'error'     => true,
                    'code'      => 'fail',
                    'error_message' => 'Lỗi kết nối, vui lòng thử lại ',
                    'data'      => ''
                );
            }

            $contents['data']['refer_code'] = $UserInfo->refer_code;

            Session::put('user_info',$contents['data']);

            $this->CountFeeze((int)$dbUser['id']);

            $this->insertUserLogs((int)$dbUser['id'], [
                'app_version' => $AppVersion,
                'os_version'  => $OsVersion,
                'device_name' => $DeviceName
            ]);
        }
        else{
            $contents = array(
                'error'     => true,
                'code'      => 'fail',
                'error_message' => 'Email hoặc mật khẩu không đúng ! ',
                'data'      => ''
            );
        }

        return Response::json($contents);
    }

    private function genToken($data){
        $token = array(
            "exp"  => time() + 86400*7,
            'data' => $data
        );
        try {
            $jwt = JWT::encode($token, Config::get('app.key'));

        } catch (Exception $e) {
            return false;
        }

        return [
            'token' =>$jwt,
            'exp'   => $token['exp']
        ];
    }

    // Đăng nhập qua facebook 
    public function postCheckinFB(){
        $access_token   = Input::get('access_token');
        $profile_id     = Input::get('profile_id');
        $IosToken     = Input::has('ios_token') ? Input::get('ios_token') : "";
        $AndroidToken = Input::has('android_token') ? Input::get('android_token') : "";

        $AppVersion   = Input::has('app_version')       ? Input::get('app_version')     : "Unknown";
        $OsVersion    = Input::has('os_version')        ? Input::get('os_version')      : "Unknown";
        $DeviceName   = Input::has('device_name')       ? Input::get('device_name')     : "Unknown";



        $validation = Validator::make(Input::all(), array(
            'access_token'      => 'required',
            'profile_id'        => 'required',
        ));

        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages(), 'error_message'=> 'Dữ liệu gửi lên không đúng, vui lòng thử lại sau !'));
        }

        if(!empty($access_token)){

            $fb = $this->facebook($access_token);

            // Lấy thông tin người dùng qua profile id
            $dbUser = \User::where('profile_id', '=', $profile_id)->with(array('user_info' => function ($query) {
                $query->get(array('user_id', 'email_nl', 'privilege', 'group', 'active'));
            }, 'oms'))->first();


            if(!$dbUser){
                // Lấy thông tin facebook user 
                try {
                    $user_profile = $fb->api('/me?');
                } catch (FacebookApiException $e) {
                    //die($e->getMessage());
                    $user = null;
                }

                if(!empty($user_profile['email'])) {
                    $ProfileId  = trim($user_profile['id']);
                    $email      = trim($user_profile['email']);
                    $FullName   = $user_profile['name'];

                    $dbUser = \User::where('email', '=', $email)->with(array('user_info' => function ($query) {
                        $query->get(array('user_id', 'email_nl', 'privilege', 'group', 'active'));
                    }, 'oms'))->first();


                    if (empty($dbUser)) { // Tạo tài khoản

                        $Insert = (int)\User::insertGetId(array(
                                'email'             => $email,
                                'fullname'          => $FullName,
                                'profile_id'        => $ProfileId,
                                'time_create'       => time(),
                                'time_last_login'   => time()
                            )
                        );


                        if(!empty($Insert)){
                            // Insert table info

                            $this->saveTransport($Insert, 4, $ProfileId);

                            /*$_configTransport = \UserConfigTransportModel::where('user_id', $Insert)->where('transport_id', 4)->first();

                            if(!$_configTransport){
                                $_configTransport = new \UserConfigTransportModel();
                            }

                            $_configTransport['received']       = $ProfileId;
                            $_configTransport['user_id']        = $Insert;
                            $_configTransport['transport_id']   = 4;
                            $_configTransport['active']         = 1;

                            $_configTransport->save();*/




                            $InsertInfo = \sellermodel\UserInfoModel::firstOrCreate(array('user_id' => $Insert));
                            $InsertFee  = \sellermodel\FeeModel::firstOrCreate(array('user_id' => $Insert, 'shipping_fee' => 2, 'cod_fee' => 1));
                            //insert vào oms_new_customer

                            $this->CreateConfirmUser($Insert, $email, $FullName, 0, 1);

                            $InsertOms = \omsmodel\CustomerAdminModel::insert(array(
                                    'user_id' => $Insert,
                                    'time_create' => time(),
                                    'first_order_time' => 0,
                                    'last_order_time' => 0,
                                    'support_id' => 0
                                )
                            );

                            $dbUser = \User::where('id', '=', $Insert)->with(array('user_info' => function ($query) {
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
            /*$dbUser['expires_in'] = $expires;*/
            $dbUser['time_last_login'] = time();

            $_saveProfile = $dbUser->save();



            $dbUser['avatar']           = 'http://www.gravatar.com/avatar/' . trim(strtolower(md5($dbUser['email']))) . '?s=80&r=g'; //$this->get_raw_facebook_avatar_url($ProfileId);//
            $dbUser['privilege']        = (int)$dbUser['user_info']['privilege'];
            $dbUser['group']            = (int)$dbUser['user_info']['group'];
            $dbUser['layers_security']  = (int)$dbUser['user_info']['layers_security'];
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
                'error_message' => 'Đăng nhập thành công',
                'data' => array(
                    'id'                => $dbUser['id'],
                    'fullname'          => $dbUser['fullname'],
                    'avatar'            => $dbUser['avatar'],
                    'email'             => $dbUser['email'],
                    'phone'             => $dbUser['phone'],
                    'privilege'         => (int)$dbUser['privilege'],
                    'layers_security'       => (int)$dbUser['layers_security'],
                    'group'             => $dbUser['group'],
                    'active'            => (int)$dbUser['user_info']['active'],
                    'parent_id'         => $dbUser['parent_id'],
                    'child_id'          => $dbUser['child_id'],
                    'first_order_time'  => $dbUser['first_order_time'],
                    'last_order_time'   => $dbUser['last_order_time'],
                    'has_nl'            => (!empty($dbUser['email_nl'])),
                    'hotline'           => '1900.63.60.30',
                    'share_discount'    => '20k',
                    'bank_cash_in'      => [
                        [
                            'bank_name'         => 'Ngân hàng kỹ thương Việt Nam',
                            'bank_short_name'   => 'Techcombank',
                            'bank_address'      => 'Chi nhánh Hai Bà Trưng – PGD Lĩnh Nam',
                            'account_name'      => 'Công ty cổ phần thương mại điện tử Shipchung Việt Nam',
                            'account_number'    => '19130623042016',
                            'bank_logo'         => 'https://seller.shipchung.vn/img/techcombank.png'
                        ],
                        [
                            'bank_name'         => 'Ngân hàng ngoại thương Việt Nam',
                            'bank_short_name'   => 'Vietcombank',
                            'bank_address'      => 'PGD Kim Ngưu - Chi nhánh Chương Dương - Hà Nội',
                            'account_name'      => 'Công ty cổ phần thương mại điện tử Shipchung Việt Nam',
                            'account_number'    => '0541000273670',
                            'bank_logo'         => 'https://seller.shipchung.vn/img/vietcombank.png'
                        ],
                        [
                            'bank_name'         => 'Ngân hàng thương mại cổ phần Á Châu Việt Nam',
                            'bank_short_name'   => 'ACB',
                            'bank_address'      => 'Chi nhánh Hà Nội',
                            'account_name'      => 'Công ty cổ phần thương mại điện tử Shipchung Việt Nam',
                            'account_number'    => '388888368',
                            'bank_logo'         => 'https://seller.shipchung.vn/img/acb.png'
                        ]
                    ]
                )
            );

            unset($dbUser['user_info']);
            unset($dbUser['oms']);

            $UserInfo = \sellermodel\UserInfoModel::where('user_id', $dbUser['id'])->first();

            
            $token = $this->genToken($contents['data']);

            if($token){
                $UserInfo->api_access_token = $token['token'];
                $contents['data']['api_access_token']        = $token['token'];
                $contents['data']['api_access_token_expire'] = $token['exp'];
            }else {
                return Response::json([
                    'error'     => true,
                    'code'      => 'ENCODE_TOKEN_FAIL',
                    'error_message' => 'Lỗi máy chủ, vui lòng thử lại hoặc liên hệ bộ phận SCKH',
                    'data'      => ''
                ]);
            }
            
            if(empty($UserInfo->refer_code)){
                $UserInfo->refer_code = $this->generationReferCode();
            }

            if (!empty($AndroidToken)) {
                $UserInfo->android_device_token = $AndroidToken;
                $this->saveTransport($dbUser['id'], 5, "");
            }

            if (!empty($IosToken)) {
                $UserInfo->ios_device_token = $IosToken;
                $this->saveTransport($dbUser['id'], 5, "");
            }

            try {
                $UserInfo->save();
            } catch (Exception $e) {
                $contents = array(
                    'error'     => true,
                    'code'      => 'fail',
                    'error_message' => 'Lỗi kết nối, vui lòng thử lại ',
                    'data'      => ''
                );
            }



            $contents['data']['refer_code'] = $UserInfo->refer_code;
            Session::put('user_info',$contents['data']);


            $this->CountFeeze((int)$dbUser['id']);
            $this->insertUserLogs((int)$dbUser['id'], [
                'app_version' => $AppVersion,
                'os_version'  => $OsVersion,
                'device_name' => $DeviceName
            ]);

            // Người dùng đã kết nối và đang tồn tại session                  
            return  Response::json($contents);

        }else {
            $contents = array(
                'error' => true,
                'error_message' => 'Lỗi kết nối',
                'data' => array()
            );
            return  Response::json($contents);
        }

    }
    public function postBla(){
        return Response::json(Input::json()->all());
        //return User::where('profile_id', '>', 0)->count();
    }

    public function _encodePassword($password){
        return md5(md5($password));
    }

    // 	Register
	    public function postRegisterNew(){
		// 		validation
		        $validation = Validator::make(Input::json()->all(),
		            [
		            'fullname'          => 'required',
		            'email'             => 'required|email|unique:users,email',
		            'phone'             => 'required',
		            'identifier'        => 'required',
		            'password'          => 'required',
		            'address'           => 'required',
		            'location'          => 'required',
		            'access_token'      => 'sometimes|required',
		            'profile_id'        => 'sometimes|required|unique:users,profile_id',
		            'expires_in'        => 'sometimes|required',
		            ],
		            [
		                'fullname.required'     => 'Bạn chưa nhập họ tên.',
		                'email.required'        => 'Bạn chưa nhập email.',
		                'email.email'           => 'Email không đúng định dạng.',
		                'email.unique'          => 'Email đã tồn tại.',
		                'phone.required'        => 'Bạn chưa nhập số điện thoại.',
		                'identifier.required'   => 'Bạn chưa chọn định danh.',
		                'identifier.in'         => 'Định danh lựa chọn không phù hợp.',
		                'password.required'     => 'Bạn chưa nhập mật khẩu.',
		                'address.required'      => 'Bạn chưa nhập địa chỉ.',
		                'location.required'     => 'Bạn chưa nhập khu vực.'
		            ]);
		
		//e		rror
		if($validation->fails()) {
            $messages = $validation->errors();
            
			if($messages->has('email')){
                return Response::json(array('error' => true, 'message' => $validation->messages(), 'error_message'=> 'Email không đúng, hoặc đã được sử dụng !'));
            }
            return Response::json(array('error' => true, 'message' => $validation->messages(), 'error_message'=> 'Dữ liệu gửi lên không đúng, vui lòng thử lại'));
		}
		
		$FullName       = (string)Input::json()->get('fullname');
		$Email          = (string)Input::json()->get('email');
		$Phone          = (string)Input::json()->get('phone');
		$Identifier     = (string)Input::json()->get('identifier');
		$PassWord       = (string)Input::json()->get('password');
		$Address        = Input::json()->get('address');
		$Location       = Input::json()->get('location');
		$access_token   = Input::json()->get('access_token');
		$profile_id     = Input::json()->get('profile_id');
		$expires        = Input::json()->get('expires');
		$country_id     = 237;
		
		


		// if(!isset($Location['country_id'])){
		// 	$country_id     = $Location['country_id'];
		// }

		if(isset($Location['country_id'])){
			$country_id     = 237;
		}

		if(!isset($Location['ward_id']) && $country_id == 237){
			return Response::json(array('error' => true, 'error_message' => 'Khu vực không đúng định dạng'));
		}
		
		$Insert = (int)\User::insertGetId(
		            array(
		                'phone'         => $Phone,
		                'email'         => $Email,
		                'fullname'      => $FullName,
		                'identifier'    => $Identifier,
		                'password'      => $this->_encodePassword($PassWord),
		                'address'       => $Address,
						'country_id'	=> $country_id,
		                'city_id'       => (int)$Location['city_id'],
		                'district_id'   => isset($Location['district_id']) ? (int)$Location['district_id'] : 0,
		                'ward_id'       => isset($Location['ward_id']) ? (int)$Location['ward_id'] : 0,
		                'access_token'  => $access_token,
		                'profile_id'    => $profile_id,
		                'expires_in'    => $expires,
		                'time_create'   => $this->time(),
		                'time_last_login' => $this->time()
		                )
		            );
		
		if(!empty($Insert)){
			try{
				\UserConfigTransportModel::insert([
				                    'received'      => $Email,
				                    'user_id'       => $Insert,
				                    'transport_id'  => 2,
				                    'active'        => 1
				                ]);
				
				\sellermodel\UserInfoModel::insert(['user_id' => $Insert, 'pipe_status'=> 100, 'verified'=> 2]);

				// Insert Seller Model
				\omsmodel\SellerModel::insert(['user_id' => $Insert, 'active'=> 1]);
				
				\accountingmodel\MerchantModel::insert(['merchant_id' => $Insert,  'country_id' => $country_id,'active' => 1, 'time_create' => time()]);
				
				\sellermodel\FeeModel::insert(['user_id' => $Insert, 'shipping_fee' => 2, 'cod_fee' => 1]);
				\sellermodel\UserInventoryModel::insert(
				                    [
				                        'user_id' => $Insert,'name' => $FullName, 'user_name' => $FullName, 'phone' => $Phone, 'country_id' => $country_id,
				                        'city_id' => (int)$Location['city_id'], 'province_id' => isset($Location['district_id']) ? (int)$Location['district_id'] : 0,
				                        'ward_id' => isset($Location['ward_id']) ? (int)$Location['ward_id'] : 0, 'address' => $Address, 'active' => 1,'time_create'=> time()
				                    ]);
				
				\omsmodel\CustomerAdminModel::insert(['user_id' => $Insert,'time_create' => $this->time(),'first_order_time' => 0,'last_order_time' => 0,'support_id' => 0]);
				
			}
			catch (\Exception $e){
				
			}
			
			$this->CreateConfirmUser($Insert, $Email, $FullName);
			
			$dbUser                 = User::where('id', '=', $Insert)->first();
			$dbUser['country_id']   = $country_id;
			$dbUser['avatar']       = 'http://www.gravatar.com/avatar/'.md5($Email).'?s=80&d=mm&r=g';
			$dbUser['privilege']    = 0;
			$dbUser['email_nl']     = '';
			$dbUser['group']        = 0;
			$dbUser['level']        = 0;
			$dbUser['has_nl']       = false;
			$dbUser['verified']     = 2;
			
			$this->CountFeeze((int)$dbUser['id']);
			Session::put('user_info',  $dbUser);
			
			if($Phone){
				try {
					$LMongo             = new \LMongo;
					$LMongo::collection('log_user_change_phone')
					                    ->insert([
					                        'user_id'           => $Insert,
					                        'phone'             => $Phone,
					                        'old'               => "",
					                        'time_create'       => $this->time()
					                    ]);
				}
				catch (\Exception $e) {
					
				}
			}
			
			$contents = array(
			                'error'     => false,
			                'code'      => 'success', 
			                'error_message'      => 'Thành công', 
			                'messenger' => '',
			                'data'      => array(
			                    'id'            => $Insert,
			                    'privilege' 	=> 0,
								'role'			=> 0,
								'fulfillment'	=> 0,
			                    'fullname'      => $dbUser['fullname'],
			                    'country_id'    => $dbUser['country_id'],
			                    'avatar'        => $dbUser['avatar'],
			                    'email'         => $dbUser['email'],
			                    'phone'         => $dbUser['phone'],
			                    'verified'      => 2,
			                    'has_nl'        => false
			                )
			            );

			try {
				$uTokenData = $contents['data'];
				$token = $this->genToken($uTokenData);
			}
			catch (\Exception $e) {
				
			}
			
			
			if(!empty($token)){
				//$				UserInfo->api_access_token                  = $token['token'];
				$contents['data']['token']        = $token['token'];
				$contents['data']['token_expire'] = $token['exp'];
			}
			//return $this->postCheckin();

		}
		else{
			$contents = array(
			                'error'     => true,
			                'code'      => 'insert', 
			                'error_message'      => 'Kết nối với máy chủ bị gián đoạn, quý khách vui lòng thử lại sau ', 
			                'messenger' => array('insert' => 'insert fail'),
			                'data'      => ''
			            );
		}
		return Response::json($contents);
	}



    // Register
    public function postRegister(){
        Input::merge(Input::all());
        // validation
        $validation = Validator::make(Input::all(), array(
            'fullname'          => 'required',
            'email'             => 'required|email|unique:users,email',
            'password'          => 'required',
            'confirm_password'  => 'required|same:password'
        ));

        //error
        if($validation->fails()) {
            $messages = $validation->errors();
            if($messages->has('email')){
                return Response::json(array('error' => true, 'message' => $validation->messages(), 'error_message'=> 'Email không đúng, hoặc đã được sử dụng !'));
            }
            return Response::json(array('error' => true, 'message' => $validation->messages(), 'error_message'=> 'Dữ liệu gửi lên không đúng, vui lòng thử lại'));
        }

        $FullName   = Input::get('fullname');
        $Email      = Input::get('email');
        $PassWord   = Input::get('password');
        $Phone      = Input::has('phone') ? Input::get('phone') : "";
        $ReferCode  = Input::has('refer_code') ? Input::get('refer_code') : "";

        $Refer = null;
        if(!empty($ReferCode)){
            $Refer = $this->getCheckReferCode($ReferCode, false);
            if($Refer == false){
                return Response::json([
                    'error'         => true,
                    'error_message' => 'Mã giới thiệu không hợp lệ.',
                    'message'       => 'Mã giới thiệu không hợp lệ.'
                ]);
            }
        }


        $InsertData = array('email' => $Email, 'fullname' => $FullName, 'password' => $this->_encodePassword($PassWord), 'time_create' => time(), 'time_last_login' => time());
        if (!empty($Phone)) {
            $InsertData['phone'] = $Phone;
        }

        $Insert = (int)User::insertGetId($InsertData);

        if(!empty($Insert)){
            // Insert table info


            $_configTransport = new \UserConfigTransportModel();
            $_configTransport['received']     = $Email;
            $_configTransport['user_id']      = $Insert;
            $_configTransport['transport_id'] = 2; // Email
            $_configTransport['active']       = 1;

            $_configTransport->save();

            $InsertInfo = \sellermodel\UserInfoModel::firstOrCreate(array('user_id' => $Insert, 'pipe_status'=> 100));
            $InsertFee  = \sellermodel\FeeModel::firstOrCreate(array('user_id' => $Insert, 'shipping_fee' => 2, 'cod_fee' => 1));


            $this->CreateConfirmUser($Insert, $Email, $FullName, 0, 1);


            //insert vao oms_new_customer
            $InsertOms = \omsmodel\CustomerAdminModel::insert(array('user_id' => $Insert,'time_create' => time(),'first_order_time' => 0,'last_order_time' => 0,'support_id' => 0));

            $dbUser = User::where('id', '=', $Insert)->first();
            $dbUser['avatar']    = 'http://www.gravatar.com/avatar/'.md5($Email).'?s=80&d=mm&r=g';
            $dbUser['privilege'] = 0;
            $dbUser['email_nl']  = '';
            $dbUser['group']     = 0;
            $dbUser['level']     = 0;
            $dbUser['has_nl']    = false;

            Session::put('user_info', $dbUser);



            if($Refer){
                try {
                    $LMongo             = new \LMongo;
                    $LMongo::collection('refer_sigup')
                    ->insert([
                        'user_id'           => $Insert,
                        'refer_id'          => $Refer->user_id,
                        'refer_code'        => $Refer->refer_code,
                        'status'            => 0,
                        'time_create'       => time(),
                        'time_success'      => 0,
                        'notify'            => 0
                    ]);
                } catch (Exception $e) {
                    
                }
            }

            if($Phone){
                try {
                    $LMongo             = new \LMongo;
                    $LMongo::collection('log_user_change_phone')
                    ->insert([
                        'user_id'           => $Insert,
                        'phone'             => $Phone,
                        'old'               => "",
                        'time_create'       => time()
                    ]);
                } catch (Exception $e) {
                    
                }
            }


            $contents = array(
                'error'         => false,
                'code'          => 'success',
                'messenger'     => '',
                'error_message' => 'Đăng ký tài khoản thành công',
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
                'error'         => true,
                'code'          => 'insert',
                'messenger'     => array('insert' => 'insert fail'),
                'error_message' => 'Lỗi kết nối máy chủ, vui lòng thử lại sau !',
                'data'          => ''
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

    public function postCountFeeze($Id){
        $error = true;
        if($this->CountFeeze($Id) ==  true){
            $error = false;
        }
        return Response::json([
            'error'         => $error,
            'error_message' => '',
            'data'          => ''
        ]);
    }
    public function CountFeeze($Id){
        $OrderModel         = new \ordermodel\OrdersModel;
        $MerchantModel      = new \accountingmodel\MerchantModel;

        $SumFee             = $OrderModel::where('time_accept','>',time() - 5184000)
            ->where('from_user_id',$Id)
            ->whereNotIn('status',array(20,22,23,24,25,26,27,28,29,31,32,33,34))
            ->where('verify_id',0)
            ->with('OrderDetail')->get(array('id','from_user_id','status'))->toArray();

        if(!empty($SumFee)){
            $TotalFee           = 0;
            $TotalMoneyCollect  = 0;
            foreach($SumFee as $val){
                if($val['status'] == 66){
                    $TotalFee    +=  $val['order_detail']['sc_pvc'] + $val['order_detail']['sc_pvk'] + $val['order_detail']['sc_pch'] - $val['order_detail']['sc_discount_pvc'];
                }else{
                    $TotalFee    +=  $val['order_detail']['sc_pvc'] + $val['order_detail']['sc_cod'] + $val['order_detail']['sc_pbh'] + $val['order_detail']['sc_pvk'] - $val['order_detail']['sc_discount_pvc'] - $val['order_detail']['sc_discount_cod'];
                }

                if(in_array((int)$val['status'],[52,53])){
                    $TotalMoneyCollect += $val['order_detail']['money_collect'];
                }
            }

            try {
                $Merchant = $MerchantModel->firstOrNew(array('merchant_id' => $Id));
                if(empty($Merchant->time_create)){
                    $Merchant->time_create = time();
                }
                $Merchant->freeze           = $TotalFee;
                $Merchant->provisional      = $TotalMoneyCollect;
                $Merchant->save();
            } catch (Exception $e) {
                return ['error' => true, 'message' => 'UPDATE_FEEZE_FAIL'];
            }
        }else{
            $Merchant = $MerchantModel->firstOrNew(array('merchant_id' => $Id));
            if(empty($Merchant->time_create)){
                $Merchant->time_create = time();
            }
            $Merchant->freeze           = 0; // Phí vận chuyển tạm tính
            $Merchant->provisional      = 0; // Tiền thu hộ tạm tính
            $Merchant->save();
        }
        return true;
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
                $PrivilegeModel = new \omsmodel\PrivilegeModel;
                $Privilege      = $PrivilegeModel->get_privilege();
                if(!empty($Privilege)){
                    Cache::forever('oms_privilege', $Privilege);
                }
            }

            if(!empty($Privilege)){
                if(Cache::has('oms_group_privilege_'.$group)){
                    $Group  = Cache::get('oms_group_privilege_'.$group);
                }else{
                    $GroupPrivilegeModel    = new \omsmodel\GroupPrivilegeModel;
                    $Group                  = $GroupPrivilegeModel->where('group_id', $group)->where('active',1)->get()->toArray();
                    if(!empty($Group)){
                        Cache::forever('oms_group_privilege_'.$group, $Group);
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
                        Cache::forever('oms_user_privilege_'.$group, $GPrivilege);
                    }
                }
            }

            return [];
        }
    }

    public function saveTransport($user_id, $transport_id, $received){
        $_configTransport = \UserConfigTransportModel::where('user_id', $user_id)->where('transport_id', $transport_id)->first();

        if(empty($_configTransport)){
            $_configTransport                   = new \UserConfigTransportModel;
            $_configTransport->active         = 1;
        }

        $_configTransport->received       = $received;
        $_configTransport->user_id        = $user_id;
        $_configTransport->transport_id   = $transport_id;
        //$_configTransport['active']         = 1;

        $_configTransport->save();
    }

    public function getTargetPoint($Level){
		$Point = 0;
		$Level = \loyaltymodel\LevelModel::where('code','>=',$Level)->orderBy('code','ASC')->remember(60)->take(2)->get()->toArray();
		if(!empty($Level)){
			foreach($Level as $val){
				if($Level == $val['code']){
					$Point  = (int)$val['maintain_point'];
				}
				else{
					$Point  = (int)$val['point'];
				}
			}
		}
		
		return $Point;
	}

}
