<?php namespace seller;

use Validator;
use Response;
use Input;
use LMongo;
use Hashids\Hashids;
use sellermodel\CouponModel;
use sellermodel\CouponCampaignModel;
use sellermodel\CouponMembersModel;
use ordermodel\OrdersModel;

class CouponController extends \BaseController {

    // Tạo random coupon code
    private function _generation_code(){
        $id = new Hashids();
        $coupon = strtoupper($id->encode(rand(1, $this->time()[1] * 20 / 9 + 1994),  rand(987, $this->time()[2] * 20 / 9 + 1994)));
        return $coupon;
    }


    public function postCouponCode ($json = true){
        $coupon_code = $this->_generation_code();
        $Model       = new CouponModel;
        $Model       = $Model->where('code', $coupon_code)->first();
        if($Model){
            $this->postCouponCode();
        }else {
            if($json){
                return Response::json(array('error' => false, 'error_message' => "Thành công", 'data' => $coupon_code), 200);
            }
            return $coupon_code;
        }

    }
    // Kiểm tra code đã tồn tại chưa
    private function hasCode($coupon){
        $Model = new CouponModel;
        $Model = $Model->where('code', $coupon)->first();
        return $Model ? true: false;
    }

    // Tạo Campaign
    public function postCreateCampaign($id = ""){
        $params     =  Input::json()->all();
        $validation = Validator::make($params, array(
            'name'         => 'required',
            'time_start'   => 'required|numeric',
            'time_end'     => 'required|numeric',
        ));


        if($validation->fails()) {
            return Response::json(array('error' => true, 'error_message' => "Vui lòng kiểm ra lại các trường gửi lên !"), 200);
        }

        $name           = $params['name'];
        $description    = Input::has('description') ? $params['description'] : "";
        $time_start     = (int)$params['time_start'];
        $time_end       = (int)$params['time_end'];

        $Model = new CouponCampaignModel;

        if(!empty($id)){
            $Model = $Model->where('id', $id)->first();
            if(!$Model){
                return Response::json(array('error' => true, 'error_message' => "Campagin không tồn tài"), 200);
            }
            $Model->time_updated    = $this->time();
        }else {
            $Model->time_created    = $this->time();
        }


        $Model->name            = $name;
        $Model->description     = $description;
        $Model->time_start      = $time_start;
        $Model->time_end        = $time_end;
        $Model->active          = 1;

        try {
            $Model->save();
        } catch (Exception $e) {
            return Response::json(array('error' => true, 'error_message' => "Lỗi truy vấn !"), 200);
        }
        return Response::json(array('error' => false, 'error_message' => "Thành công !", 'data' => $Model), 200);
    }
    public function getCreateCoupon (){
        return $this->postCreateCoupon(false);
    }
    public function postCreateCoupon($json = true){
        $params     =  Input::all();
        $UserInfo  = $this->UserInfo();
        if(!empty($UserInfo['id'])){
            $UserId    = $UserInfo['id'];
        }else {
            $UserId    = 1;
        }
        

        $validation = Validator::make($params, array(
            'campaign_id'       => 'required',
            'code'              => 'sometimes|required',
            'coupon_type'       => 'required|numeric',
            'discount_type'     => 'required|numeric',
            'discount'          => 'required',
            'limit_usage'       => 'required|numeric',
            'inapp'             => 'sometimes|required|numeric',
            'time_expired'      => 'required|numeric',
        ));

        if($validation->fails()) {
            return Response::json(array('error' => true, 'error_message' => "Vui lòng kiểm ra lại các trường gửi lên !"), 200);
        }

        $code = !empty($params['code']) ? $params['code'] : "";

        if(empty($code)){
            $code = $this->postCouponCode(false);
        }
        

        if($this->hasCode($code)){
            return Response::json(array('error' => true, 'error_message' => "Mã coupon đã tồn tại, vui lòng nhập mã khác."), 200);   
        }

        $campaign_id    = (int)$params['campaign_id'];
        $coupon_type    = (int)$params['coupon_type'];
        $discount_type  = (int)$params['discount_type'];
        $discount       = (int)$params['discount'];
        $limit_usage    = (int)$params['limit_usage'];
        $time_expired   = (int)$params['time_expired'];
        $inapp          = !empty($params['inapp']) ? $params['inapp'] : 2;
        $seller_email   = !empty($params['seller_email']) ? $params['seller_email'] : "";
        $seller         = !empty($params['seller']) ? $params['seller'] : "";

        $Model                = new CouponModel;
        $Model->code          = $code;
        $Model->user_create   = $UserId;
        $Model->campaign_id   = $campaign_id;
        $Model->coupon_type   = $coupon_type;
        $Model->discount_type = $discount_type;
        $Model->discount      = $discount;
        $Model->limit_usage   = $limit_usage;
        $Model->inapp         = $inapp;
        $Model->time_expired  = $time_expired;
        $Model->time_created  = $this->time();

        try {
            $Model->save();
        } catch (Exception $e) {
            return Response::json(array('error' => true, 'error_message' => "Lỗi truy vấn !"), 200);
        }   

        $MembersInsert = [];
        if(!empty($seller)){
            foreach ($seller as $key => $value) {
                if(!empty($value['id'])){
                    $MembersInsert[] = [
                        'coupon_id'     => $Model->id,
                        'user_id'       => $value['id'],
                        'time_created'  => $this->time()
                    ];
                }
            }
        }
        if(!empty($seller_email)){
            $UserModel = new \User;
            $UserModel = $UserModel->where('email', $seller_email)->first();

            if(!empty($UserModel)){
                $MembersInsert[] = [
                    'coupon_id'     => $Model->id,
                    'user_id'       => $UserModel->id,
                    'time_created'  => $this->time()
                ];
            }else {
                return Response::json(array('error' => true, 'error_message' => "Email khách hàng không tồn tại"), 200);  
            }
        }

        if(!empty($MembersInsert)){
            $CouponMember = new CouponMembersModel;
            try {
                $CouponMember::insert($MembersInsert);
            } catch (Exception $e) {
                return Response::json(array('error' => true, 'error_message' => "Lỗi truy vấn !"), 200);    
            }
        }
        if(!$json){
            return $code;
        }
        return Response::json(array('error' => false, 'error_message' => "Thành công !", 'data' => $Model), 200);
    }

    public function getShowCampaign($id = ""){
        $page     = Input::has('page')            ? (int)Input::get('page')                : 1;
        $itemPage = Input::has('limit')           ? Input::get('limit')                    : 20;
        $offset   = ($page - 1)*$itemPage;
        $Model    = new CouponCampaignModel;

        if(!empty($id)){
            $Model = $Model->where('id', $id)->with(['coupons_count'])->first();
        }
        try {
            $Model       = $Model->with(['coupons_count'])->orderBy('id', 'DESC')->skip($offset)->take($itemPage)->get();
        } catch (Exception $e) {
            return Response::json(array('error' => true, 'error_message' => "Lỗi truy vấn !"), 200);
        }

        return Response::json(array('error' => false, 'error_message' => "Thành công !", 'data' => $Model), 200);
    }


    public function getShow($campagin, $couponId = "",$Email = ""){
        $page     = Input::has('page')              ? (int)Input::get('page')                : 1;
        $itemPage = Input::has('limit')             ? Input::get('limit')                    : 20;
        $Code   = Input::has('code')            ? Input::get('code')                  : "";
        $Email   = Input::has('email')            ? Input::get('email')                  : "";

        $offset   = ($page - 1) * $itemPage;
        $Model    = new CouponModel;
        $Model    = $Model->where('campaign_id', (int)$campagin)->with(['members'=> function ($q){
                        /*return $q->with(['user']);*/
                    }]);

        if(!empty($Code)){
            $Model = $Model->where('code', 'LIKE', '%'.$Code.'%');
        }
        if(!empty($Email)){
            $InfoUser = \User::where('email',$Email)->first();
            if(empty($InfoUser)){
                return Response::json(array('error' => true, 'error_message' => "Không tồn tại email !"), 200);
            }
            $ListCouponEmail = CouponMembersModel::where('user_id',$InfoUser['id'])->lists('coupon_id');
            $Model = $Model->whereIn('id', $ListCouponEmail);
        }

        $Total = clone $Model;
        $Total = $Total->count();
        try {
            $Model = $Model->skip($offset)->take($itemPage)->orderBy('id', 'DESC')->get();
            //get sc_code
            if(!empty($Model)){
                foreach($Model AS $One){
                    $ListId[] = $One['id'];
                }
                $Orders = OrdersModel::whereIn('coupon_id',$ListId)->get(array('id','tracking_code','coupon_id','time_accept'))->toArray();
                if(!empty($Orders)){
                    foreach($Orders AS $Od){
                        $ListOrder[$Od['coupon_id']] = array(
                            'tracking_code' => $Od['tracking_code'],
                            'time_used' => $Od['time_accept']
                        ); 
                    }
                }else{
                    $ListOrder = array();
                }
            }
        } catch (Exception $e) {
            return Response::json(array('error' => true, 'error_message' => "Lỗi truy vấn !"), 200);
        }
        return Response::json(array('error' => false, 'error_message' => "Thành công !", 'data' => $Model,'orders' => $ListOrder, 'total'=> $Total), 200);
    }


    public function getMembers($couponId){
        $CouponMember = new CouponMembersModel;
        $CouponMember = $CouponMember::where('coupon_id', $couponId)->with(['user'])->get();

        return Response::json(array('error' => false, 'error_message' => "Thành công !", 'data' => $CouponMember), 200);
    }

    public function postInsertMember ($couponId){
        $params =  Input::json()->all();
        $seller = ($params['seller']) ? $params['seller'] : "";
        
        $Model  = new CouponModel;
        $Coupon = $Model->find($couponId);

        if($Coupon){
            $MembersInsert = [];
            if(!empty($seller)){
                foreach ($seller as $key => $value) {
                    if(!empty($value['id'])){
                        $MembersInsert[] = [
                            'coupon_id'     => $Coupon->id,
                            'user_id'       => $value['id'],
                            'time_created'  => $this->time()
                        ];
                    }
                }
            }

            if(!empty($MembersInsert)){
                $CouponMember = new CouponMembersModel;
                try {
                    $CouponMember::insert($MembersInsert);
                } catch (Exception $e) {
                    return Response::json(array('error' => true, 'error_message' => "Lỗi truy vấn !"), 200);
                }
            }

            return Response::json(array('error' => false, 'error_message' => "Thành công", 'data' => ""), 200);
        }else {
            return Response::json(array('error' => true, 'error_message' => "Không tìm thấy mã coupon !", 'data' => ""), 200);
        }
    }


    public function getCheckCoupon(){
        $Coupon     = Input::has('coupon_code')             ? Input::get('coupon_code')                    : "";
        $UserInfo  = $this->UserInfo();
        $UserId    = $UserInfo['id'];

        if(empty($Coupon)){
            return Response::json([
                'error'         => true,
                'error_message' => "Mã khuyến mãi không đúng, vui lòng thử lại"
            ]);
        }

        $CouponModel    = new CouponModel;
        $CheckCoupon    = $CouponModel::where('code',$Coupon)->where('time_expired','>=',$this->time())->where('active',1)->first();

        if(!isset($CheckCoupon->id)){
            return ['error' => true, 'message'  => 'COUPON_NOT_EXISTS', 'error_message' => 'Mã khuyến mãi không đúng, vui lòng thử lại !'];
        }

        if($CheckCoupon->usaged >= $CheckCoupon->limit_usage){
            return ['error' => true, 'message'  => 'COUPON_LIMITED', 'error_message' => 'Mã khuyến mãi đã được sử dụng đến giới hạn'];
        }
        // Edit by ThinhNV
        // if($CheckCoupon->discount_type == 2){

        if($CheckCoupon->coupon_type == 2){
            $CouponMembersModel = new CouponMembersModel;
            $CheckMember        = $CouponMembersModel::where('coupon_id', $CheckCoupon->id)
                ->where('user_id', (int)$UserInfo['id'])
                ->count();

            if($CheckMember == 0){
                return ['error' => true, 'message'  => 'COUPON_NOT_EXISTS', 'error_message' => 'Bạn không được phép sử dụng mã khuyến mãi này, xin cảm ơn !'];
            }
        }
        $discount = 0;
        if($CheckCoupon->discount_type == 2){
            $discount = $CheckCoupon->discount."%";
        }else {
            $discount = number_format($CheckCoupon->discount)."đ";
        }
        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => [
                'discount_type' => $CheckCoupon->discount_type,
                'discount' => $discount,

            ]
        ]);

    }

    //coupons refer
    public function getShowrefer($couponId = "",$Email = ""){
        $page     = Input::has('page')              ? (int)Input::get('page')                : 1;
        $itemPage = Input::has('limit')             ? Input::get('limit')                    : 20;
        $Code   = Input::has('code')            ? Input::get('code')                  : "";
        $Email   = Input::has('email')            ? Input::get('email')                  : "";

        $offset   = ($page - 1) * $itemPage;
        $ReturnData = $ListCouponId = $ListOrder = array();
        $ReferModel = new LMongo;
        $ReferModel     = $ReferModel::collection('refer_sigup');
        $ReferList = $ReferModel->where('status',1);

        if(!empty($Code)){
            $ReferModel = $ReferModel->where('coupon', $Code);
        }
        if(!empty($Email)){
            $InfoUser = \User::where('email',$Email)->first();
            if(empty($InfoUser)){
                return Response::json(array('error' => true, 'error_message' => "Không tồn tại email !"), 200);
            }
            $ReferModel = $ReferModel->where('user_id', (int)$InfoUser['id']);
        }

        $Total = clone $ReferModel;
        $Total = $Total->count();

        if($Total > 0){
            $ReferList = $ReferModel->skip($offset)->take($itemPage)->orderBy('time_create', 'desc')->get()->toArray();
            foreach($ReferList AS $One){
                $ListUserId[] = $One['user_id'];//user dc gioi thieu
                $ListCoupon[] = $One['coupon'];
                $ListReferId[] = $One['refer_id'];
            }
            $ListInfoUser = \User::whereIn('id',$ListUserId)->get(array('id','email'))->toArray();
            if(!empty($ListInfoUser)){
                foreach($ListInfoUser AS $Val){
                    $ReturnInfoUser[$Val['id']] = $Val['email'];
                }
            }else{
                $ReturnInfoUser = array();
            }
            $ListInfoRefer = \User::whereIn('id',$ListReferId)->get(array('id','email'))->toArray();
            if(!empty($ListInfoRefer)){
                foreach($ListInfoRefer AS $Val){
                    $ReturnInfoRefer[$Val['id']] = $Val['email'];
                }
            }else{
                $ReturnInfoRefer = array();
            }
            $ListInfoCoupon = CouponModel::whereIn('code',$ListCoupon)->get(array('id','code','usaged'))->toArray();
            
            if(!empty($ListInfoCoupon)){
                foreach($ListInfoCoupon AS $Coupon){
                    $ListCouponId[] = $Coupon['id'];
                    $InfoCoupons[$Coupon['code']] = array('usaged' => $Coupon['usaged'],'id' => $Coupon['id']);
                }
            }
            //var_dump($InfoCoupons);die;
            if(!empty($ListCouponId)){
                $Orders = OrdersModel::whereIn('coupon_id',$ListCouponId)->get(array('id','tracking_code','coupon_id','time_accept'))->toArray();
                if(!empty($Orders)){
                    foreach($Orders AS $Od){
                        $ListOrder[$Od['coupon_id']] = array(
                            'tracking_code' => $Od['tracking_code'],
                            'time_used' => (int)$Od['time_accept']
                        ); 
                    }
                    foreach($ListOrder AS $Key => $Value){
                        foreach($ListInfoCoupon AS $V){
                            if($Key == $V['id']){
                                $ListOrderReturn[$V['code']] = $Value;
                            }
                        }
                    }
                }else{
                    $ListOrderReturn = array();
                }
            }
            return Response::json(array('error' => false, 'error_message' => "Thành công !", 'data' => $ReferList,'orders' => $ListOrderReturn,'users' => $ReturnInfoUser,'refers' => $ReturnInfoRefer,'infoCoupon' => $InfoCoupons, 'total'=> $Total), 200);
        }else{
            return Response::json(array('error' => true, 'error_message' => "Lỗi truy vấn !"), 200);
        }
    }

}
