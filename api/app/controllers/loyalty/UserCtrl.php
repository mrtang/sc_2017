<?php
namespace loyalty;
use LMongo;


class UserCtrl extends BaseCtrl
{
    public function __construct()
    {

    }       

    public function getIndex(){
        $itemPage       = $this->itemPage;
        $page           = Input::has('page')                ? (int)Input::get('page')               : 1;
        $KeyWord        = Input::has('keyword')             ? trim(Input::get('keyword'))           : '';
        $Level          = Input::has('level')               ? (int)Input::get('level')              : null;
        $Cmd            = Input::has('cmd')                 ? strtoupper(trim(Input::get('cmd')))   : '';

        $Model  = new \loyaltymodel\UserModel;

        if(isset($Level)){
            $Model  = $Model->where('level', $Level);
        }

        if(!empty($KeyWord)){
            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $UserModel          = \User::where('email',$KeyWord);
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $UserModel          = \User::where('phone',$KeyWord);
            }else{ // search code
                $UserModel          = \User::where('fullname',$KeyWord);
            }

            $UserId = $UserModel->first(['id']);

            if(!isset($UserId->id)){
                return $this->ResponseData();
            }

            $Model  = $Model->where('user_id', $UserId->id);
        }

        if($Cmd == 'EXPORT'){
            $Data = [];
            $this->data = $Model->with('get_user')->orderBy('time_create','desc')->chunk('1000', function($query) use(&$Data){
                foreach($query as $val){
                    $val                = $val->toArray();
                    $Data[]             = $val;
                }
            });

            $this->data = $Data;
            return $this->ResponseData();
        }

        $ModelTotal     = clone $Model;
        $this->total    = $ModelTotal->count();
        if($this->total > 0){
            $offset         = ($page - 1)*$itemPage;
            $this->data     = $Model->skip($offset)->take($itemPage)->with('get_user')->orderBy('time_create','desc')->get()->toArray();
        }

        return $this->ResponseData();
    }

    public function getHistory(){
        $itemPage   = $this->itemPage;
        $page       = Input::has('page')        ? (int)Input::get('page')               : 1;
        $KeyWord    = Input::has('keyword')     ? trim(Input::get('keyword'))           : '';
        $Level      = Input::has('level')       ? (int)Input::get('level')              : null;
        $TimeStart  = Input::has('time_start')  ? trim(Input::get('time_start'))        : '';
        $TimeEnd    = Input::has('time_end')    ? trim(Input::get('time_end'))          : '';

        $Model  = new \loyaltymodel\HistoryModel;

        if(isset($Level)){
            $Model  = $Model->where('level', $Level);
        }

        if(!empty($KeyWord)){
            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $UserModel          = \User::where('email',$KeyWord);
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $UserModel          = \User::where('phone',$KeyWord);
            }else{ // search code
                $UserModel          = \User::where('fullname',$KeyWord);
            }

            $UserId = $UserModel->first(['id']);

            if(!isset($UserId->id)){
                return $this->ResponseData();
            }

            $Model  = $Model->where('user_id', $UserId->id);
        }

        if(!empty($TimeStart)){
            $TimeStart = explode('-',$TimeStart);
            $Model  = $Model->where('month','>=',$TimeStart[0])->where('year','>=',$TimeStart[1]);

        }

        if(!empty($TimeEnd)){
            $TimeEnd = explode('-',$TimeEnd);
            $Model  = $Model->where('month','<=',$TimeEnd[0])->where('year','<=',$TimeEnd[1]);
        }

        $ModelTotal     = clone $Model;
        $this->total    = $ModelTotal->count();
        if($this->total > 0){
            $offset         = ($page - 1)*$itemPage;
            $this->data     = $Model->skip($offset)->take($itemPage)->with('get_user')->orderBy('time_create','desc')->get()->toArray();
        }

        return $this->ResponseData();
    }

    public function postCreate(){
        $Email          = Input::has('email')               ? strtolower(trim(Input::get('email')))         : '';
        $Level          = Input::has('level')               ? (int)Input::get('level')                      : 0;
        $TotalPoint     = Input::has('total_point')         ? (int)Input::get('total_point')                : 0;
        $CurrentPoint   = Input::has('current_point')       ? (int)Input::get('current_point')              : 0;
        $Active         = Input::has('active')              ? (int)Input::get('active')                     : 1;

        $validation = Validator::make(Input::all(), array(
            'email'             => 'required|email',
            'level'             => 'required|integer|min:0',
            'total_point'       => 'required|integer|min:0',
            'current_point'     => 'required|integer|min:0',
        ));

        //error
        if($validation->fails()) {
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => $validation->messages()]);
        }

        $User           = \User::where('email', $Email)->first(['id','email']);
        if(!isset($User->id)){
            return Response::json(['error' => true, 'code' => 'EMAIL_ERROR','error_message' => 'email không chính xác']);
        }

        // Check exist
        if(\loyaltymodel\UserModel::where('user_id', $User->id)->count() > 0){
            return Response::json(['error' => true, 'code' => 'USER_EXISTS','error_message' => 'Đã tồn tại khách hàng']);
        }

        $Merchant   = \accountingmodel\MerchantModel::where('merchant_id',$User->id)->first(['merchant_id','country_id','level']);
        if(!isset($Merchant['merchant_id'])){
            return Response::json(['error' => true,'merchant' => $Merchant , 'code' => 'MERCHANT_NOT_EXISTS','error_message' => 'Khách hàng không tồn tại']);
        }

        try{
            \loyaltymodel\UserModel::insert([
                'user_id'       => $User->id,
                'country_id'    => $Merchant->country_id,
                'level'         => $Level,
                'total_point'   => $TotalPoint,
                'current_point' => $CurrentPoint,
                'time_create'   => $this->time(),
                'active'        => $Active
            ]);
        }catch(\Exception $e){
            return Response::json([
                'error'         => true,
                'code'          => 'INSERT_ERROR',
                'error_message' => 'Thêm mới thất bại '. $e->getMessage()
            ]);
        }

        return Response::json([
            'error'         => false,
            'code'          => 'SUCCESS',
            'error_message' => 'Thành Công'
        ]);
    }
    // change level user
    public function postChangelevel(){
        $Model  = new \loyaltymodel\UserModel;
        $Level      = Input::has('level')       ? (int)Input::get('level')        : 0;
        $Active     = Input::has('active')      ? (int)Input::get('active')       : 0;
        $Id         = Input::has('id')          ? (int)Input::get('id')           : 0;
        if($Id <= 0){
            return Response::json([
                'error'         => true,
                'code'          => 'USER_NOT_EXISTS',
                'error_message' => 'Khách hàng không tồn tại trên hệ thống'
            ]);
        }
        $Update = $Model->where('id',$Id)->update(array('level' => $Level,'active' => $Active));
        if($Update){
            return Response::json([
                'error'         => false,
                'code'          => 'SUCCESS',
                'error_message' => 'Thành Công'
            ]);
        }else{
            return Response::json([
                'error'         => true,
                'code'          => 'INSERT_ERROR',
                'error_message' => 'Cập nhật thất bại!'
            ]);
        }
    }
}
?>