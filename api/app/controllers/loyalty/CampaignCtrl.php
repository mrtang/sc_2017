<?php
namespace loyalty;
use LMongo;


class CampaignCtrl extends BaseCtrl
{
    public function __construct()
    {

    }       

    public function getIndex(){
        $itemPage   = $this->itemPage;
        $page       = Input::has('page')        ? (int)Input::get('page')               : 1;
        $KeyWord    = Input::has('keyword')     ? trim(Input::get('keyword'))           : '';
        $Category   = Input::has('category')    ? (int)Input::get('category')           : 0;
        $TimeStart  = Input::has('time_start')  ? (int)Input::get('time_start')         : 0;
        $TimeEnd    = Input::has('time_end')    ? (int)Input::get('create_end')         : 0;

        $Model  = new \loyaltymodel\CampaignModel;

        if(!empty($Category)){
            $Model  = $Model->where('category_id', $Category);
        }

        if(!empty($KeyWord)){
            $Model  = $Model->where('name','LIKE', '%'.$KeyWord.'%');
        }

        if(!empty($TimeStart)){
            $Model  = $Model->where('time_start', '>=', $TimeStart);
        }

        if(!empty($TimeEnd)){
            $Model  = $Model->where('time_end', '<=', $TimeEnd);
        }

        $ModelTotal     = clone $Model;
        $this->total    = $ModelTotal->count();
        if($this->total > 0){
            $offset         = ($page - 1)*$itemPage;
            $this->data     = $Model->skip($offset)->take($itemPage)->orderBy('time_create','desc')->get()->toArray();
        }

        return $this->ResponseData();
    }

    public function getDetail(){
        $itemPage   = $this->itemPage;
        $page       = Input::has('page')        ? (int)Input::get('page')               : 1;
        $KeyWord    = Input::has('keyword')     ? trim(Input::get('keyword'))           : '';
        $Level      = Input::has('level')       ? (int)Input::get('level')              : null;
        $Code       = Input::has('code')        ? trim(Input::get('code'))              : '';
        $TimeStart  = Input::has('time_start')  ? (int)Input::get('time_start')         : 0;
        $TimeEnd    = Input::has('time_end')    ? (int)Input::get('create_end')         : 0;
        $Cmd        = Input::has('cmd')         ? strtoupper(trim(Input::get('cmd')))   : '';

        $Model  = new \loyaltymodel\CampaignDetailModel;
        $Model  = $Model->where('user_id','>',0);

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
            $Model  = $Model->where('time_create', '>=', $TimeStart);
        }

        if(!empty($TimeEnd)){
            $Model  = $Model->where('time_create', '<=', $TimeEnd);
        }

        if(isset($Level)){
            $Model  = $Model->where('level', $Level);
        }

        if(!empty($Code)){
            $Model  = $Model->where('code', $Code);
        }

        if($Cmd == 'EXPORT'){
            $Data = [];
            $this->data = $Model->with(['get_user','get_campaign'])->orderBy('time_create','desc')->chunk('1000', function($query) use(&$Data){
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
            $this->data     = $Model->skip($offset)->take($itemPage)->with(['get_user','get_campaign'])->orderBy('time_create','desc')->get()->toArray();
        }

        return $this->ResponseData();
    }

    public function getDetailId(){
        $itemPage       = $this->itemPage;
        $page           = Input::has('page')            ? (int)Input::get('page')                   : 1;
        $CampaignId     = Input::has('campaign_id')     ? (int)Input::get('campaign_id')            : 0;

        $Model  = new \loyaltymodel\CampaignDetailModel;
        $Model  = $Model->where('campaign_id',$CampaignId);

        $ModelTotal     = clone $Model;
        $this->total    = $ModelTotal->count();
        if($this->total > 0){
            $offset         = ($page - 1)*$itemPage;
            $this->data     = $Model->skip($offset)->take($itemPage)->with(['get_user','get_campaign'])->orderBy('time_create','desc')->get()->toArray();
        }

        return $this->ResponseData();
    }

    public function postAddDetail(){
        $validation = Validator::make(Input::all(), array(
            'code'              =>  'required',
            'campaign_id'       =>  'required|integer|min:1'
        ));

        //error
        if($validation->fails()) {
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => $validation->messages()]);
        }

        $Code           = Input::has('code')                ? trim(Input::get('code'))                      : '';
        $CampaignId     = Input::has('campaign_id')         ? (int)trim(Input::get('campaign_id'))          : 0;
        $CountryId      = Input::has('country_id')          ? (int)Input::get('country_id')                 : 237;



        try{
            \loyaltymodel\CampaignDetailModel::insert([
                'code'              => $Code,
                'country_id'        => $CountryId,
                'campaign_id'       => $CampaignId,
                'time_create'       => $this->time()
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

    public function postCreate(){
        $validation = Validator::make(Input::all(), array(
            'name'              =>  'required',
            'category_id'       =>  'required|integer|min:0',
            'value'             =>  'required|integer|min:0',
            'time_start'        =>  'required|integer|min:1',
            'point'             =>  'required|integer|min:1'
        ));

        //error
        if($validation->fails()) {
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => $validation->messages()]);
        }

        $Name           = Input::has('name')                ? trim(Input::get('name'))                      : '';
        $Category       = Input::has('category_id')         ? (int)trim(Input::get('category_id'))          : 0;
        $Link           = Input::has('link')                ? trim(Input::get('link'))                      : '';
        $Level          = Input::has('level')               ? (int)trim(Input::get('level'))                : 0;
        $Value          = Input::has('value')               ? (int)trim(Input::get('value'))                : 0;
        $TimeStart      = Input::has('time_start')          ? (int)trim(Input::get('time_start'))           : 0;
        $TimeEnd        = Input::has('time_end')            ? (int)trim(Input::get('time_end'))             : 0;
        $Point          = Input::has('point')               ? (int)trim(Input::get('point'))                : 0;
        $Total          = Input::has('total')               ? (int)trim(Input::get('total'))                : 0;
        $CountryId      = Input::has('country_id')          ? (int)Input::get('country_id')                 : 237;


        try{
            \loyaltymodel\CampaignModel::insert([
                'country_id'    => $CountryId,
                'category_id'   => $Category,
                'name'          => $Name,
                'link'          => $Link,
                'value'         => $Value,
                'level'         => $Level,
                'point'         => $Point,
                'total'         => $Total,
                'total_use'     => 0,
                'time_start'    => $TimeStart,
                'time_end'      => $TimeEnd,
                'time_create'   => $this->time(),
                'active'        => 1
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

    public function postEditCampaign(){
        $validation = Validator::make(Input::all(), array(
            'id'                => 'required',
            'name'              => 'sometimes|required',
            'category_id'       => 'sometimes|required|integer|min:0',
            'value'             => 'sometimes|required|integer|min:1',
            'link'              => 'sometimes|required|url',
            'level'             => 'sometimes|required|integer|min:0',
            'point'             => 'sometimes|required|integer|min:1',
            'total'             => 'sometimes|required|integer|min:0',
            'time_start'        => 'sometimes|required|integer|min:0',
            'time_end'          => 'sometimes|required|integer|min:0',
            'active'            => 'sometimes|required|integer|in:0,1',
        ));

        //error
        if($validation->fails()) {
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => $validation->messages()]);
        }

        $Id             = Input::has('id')                  ? (int)Input::get('id')                 : 0;
        $Name           = Input::has('name')                ?  trim(Input::get('name'))             : '';
        $Category       = Input::has('category_id')         ? (int)Input::get('category_id')        : 0;
        $Value          = Input::has('value')               ? (int)Input::get('value')              : 0;
        $Link           = Input::has('link')                ?  trim(Input::get('link'))             : '';
        $Level          = Input::has('level')               ? (int)Input::get('level')              : null;
        $Point          = Input::has('point')               ? (int)Input::get('point')              : 0;
        $Total          = Input::has('total')               ? (int)Input::get('total')              : null;
        $TimeStart      = Input::has('time_start')          ? (int)Input::get('time_start')         : null;
        $TimeEnd        = Input::has('time_end')            ? (int)Input::get('time_end')           : null;
        $Active         = Input::has('active')              ? (int)Input::get('active')             : null;

        $Campaign  = \loyaltymodel\CampaignModel::find($Id);
        if(!isset($Campaign->id)){
            return Response::json([
                'error'         => true,
                'code'          => 'INSERT_ERROR',
                'error_message' => 'Mã  không tồn tại'
            ]);
        }

        if(!empty($Name)){
            $Campaign->name    = $Name;
        }

        if(!empty($Category)){
            $Campaign->category_id    = $Category;
        }

        if(!empty($Value)){
            $Campaign->value    = $Value;
        }

        if(!empty($Link)){
            $Campaign->link    = $Link;
        }

        if(isset($Level)){
            $Campaign->level    = $Level;
        }

        if(!empty($Point)){
            $Campaign->point    = $Point;
        }

        if(isset($Total)){
            $Campaign->total    = $Total;
        }

        if(isset($TimeStart)){
            $Campaign->time_start    = $TimeStart;
        }

        if(isset($TimeEnd)){
            $Campaign->time_end    = $TimeEnd;
        }

        if(isset($Active)){
            $Campaign->active    = $Active;
        }


        try{
            $Campaign->save();
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
            'error_message' => 'Thành Công',
            'id'            =>  $Campaign->id
        ]);
    }

    public function postEditCampaignDetail(){
        $validation = Validator::make(Input::all(), array(
            'id'                => 'required',
            'code_number'       => 'sometimes|required',
            'code'              => 'sometimes|required',
        ));

        //error
        if($validation->fails()) {
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => $validation->messages()]);
        }

        $Id             = Input::has('id')                  ? (int)Input::get('id')                 : 0;
        $CodeNumber     = Input::has('code_number')         ?  trim(Input::get('code_number'))      : '';
        $Code           = Input::has('code')                ? trim(Input::get('code'))              : 0;

        $CampaigDetail  = \loyaltymodel\CampaignDetailModel::find($Id);
        if(!isset($CampaigDetail->id)){
            return Response::json([
                'error'         => true,
                'code'          => 'INSERT_ERROR',
                'error_message' => 'Mã  không tồn tại'
            ]);
        }

        if(!empty($Code) && empty($CampaigDetail->code)){
            if(empty($CampaigDetail->code_number)){
                return Response::json([
                    'error'         => true,
                    'code'          => 'INSERT_ERROR',
                    'error_message' => 'Bạn chưa nhập số Serial'
                ]);
            }
            $CampaigDetail->code        = $Code;
        }

        if(!empty($CodeNumber) && empty($CampaigDetail->code_number)){
            $CampaigDetail->code_number = $CodeNumber;
        }




        try{
            $CampaigDetail->save();
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
            'error_message' => 'Thành Công',
            'id'            =>  $CampaigDetail->id
        ]);
    }

    private function __get_money_sc($UserInfo, $Campaign){
        DB::connection('accdb')->beginTransaction();
        $Merchant   = \accountingmodel\MerchantModel::where(array('merchant_id' => (int)$UserInfo->user_id))->first();
        if(!isset($Merchant->id)){
            return ['error' => true,'code' => 'MERCHANT_NOT_EXISTS','message' => 'Khách hàng không tồn tại'];
        }

        $Master = $this->getMasterId($Merchant->country_id);
        if(!isset($Master->merchant_id)){
            return array('error'=> false, 'code' =>  'MASTER_NOT_EXISTS', 'error_message' => 'Tài khoản master không tồn tại, currency: '.$Merchant->home_currency);
        }

        try{
            \accountingmodel\MerchantModel::where(array('merchant_id' => (int)$UserInfo->user_id))->increment('balance', $Campaign->value);
            \accountingmodel\MerchantModel::where(array('merchant_id' => (int)$Master->merchant_id))->decrement('balance', $Campaign->value);
            \accountingmodel\RefundModel::insert([
                'merchant_id'   => (int)$UserInfo->user_id,
                'country_id'    => $Merchant->country_id,
                'amount'        => $Campaign->value,
                'refer_code'    => $Campaign->id,
                'time_create'   => $this->time(),
                'reason'        => 'Quà tặng chương trình '.$Campaign->name,
                'status'        => 'SUCCESS',
                'time_accept'   => $this->time()
            ]);

            \accountingmodel\TransactionModel::insert([
                'type'              => 4,
                'refer_code'        => $Campaign->id,
                'country_id'        => $Merchant->country_id,
                'from_user_id'      => (int)$Master->merchant_id,
                'to_user_id'        => (int)$UserInfo->user_id,
                'money'             => $Campaign->value,
                'balance_before'    => $Merchant->balance,
                'note'              => 'Quà tặng chương trình '.$Campaign->name,
                'time_create'       => $this->time()
            ]);
        }catch (\Exception $e){
            return ['error' => true,'code' => 'INSERT_ERROR','message' => $e->getMessage()];
        }
        //Update Campaign Detail


        return ['error' => false,'code' => 'SUCCESS','message' => 'Thành công'];
    }

    public function getCronLoyalty(){
        $LMongo     = new \LMongo;
        $LogCampaign   = $LMongo::collection('loyalty_history')->where('active',0)->orderBy('time_create','asc')->first();

        if(!isset($LogCampaign['campaign_id'])){
            return Response::json([
                'error'         => false,
                'code'          => 'SUCCESS',
                'error_message' => 'Kết thúc'
            ]);
        }

        //get User
        $UserInfo       = \loyaltymodel\UserModel::where('user_id', $LogCampaign['user_id'])->first();
        Input::merge(['country_id' => $UserInfo->country_id]);

        if(!isset($UserInfo['id'])){
            $Code       = 'ERROR';
            $Message    = 'Không tìm thấy khách hàng đổi thưởng';
            $DataUpdate = [
                'active'            => 2,
                'code'              => $Code,
                'error_message'     => $Message
            ];
            goto Finish;
        }

        $Campaign   = \loyaltymodel\CampaignModel::where('id', $LogCampaign['campaign_id'])
                                                ->where('time_start','<=',$LogCampaign['time_create'])
                                                ->where('time_end','>=',$LogCampaign['time_create'])
                                                ->where('level','<=',$UserInfo->level)
                                                ->whereRaw('total_use < total')
                                                ->where('active',1)
                                                ->first();
        if(!isset($Campaign->id)){
            $Code       = 'ERROR';
            $Message    = 'Mã đổi thưởng không tồn tại hoặc quá số lượng cho phép';
            $DataUpdate = [
                'active'            => 2,
                'code'              => $Code,
                'error_message'     => $Message
            ];
            goto Finish;
        }

        if($Campaign->point > $UserInfo->total_point){
            $Code       = 'ERROR';
            $Message    = 'Điểm đổi thưởng không đủ';
            $DataUpdate = [
                'active'            => 2,
                'code'              => $Code,
                'error_message'     => $Message
            ];
            goto Finish;
        }

        //Lấy thưởng
        if($Campaign->category_id == 1){ // nạp tiền vào tài khoản shipchung
            $Result = $this->__get_money_sc($UserInfo, $Campaign);

            if($Result['error']){
                $Code       = $Result['code'];
                $Message    = $Result['message'];
                $DataUpdate = [
                    'active'            => 2,
                    'code'              => $Code,
                    'error_message'     => $Message
                ];
                goto Finish;
            }
        }

        // cập nhật detail campaign, campaign, user point
        $Detail   = \loyaltymodel\CampaignDetailModel::firstOrNew(array('campaign_id' => (int)$Campaign->id, 'user_id' => 0));
        DB::connection('loyaltydb')->beginTransaction();
        try{
            $Detail->user_id        = $UserInfo->user_id;
            $Detail->level          = $UserInfo->level;
            $Detail->phone          = $LogCampaign['phone'];
            $Detail->phone_type     = $LogCampaign['phone_type'];
            $Detail->time_create    = $LogCampaign['time_create'];
            $Detail->save();

            \loyaltymodel\CampaignModel::where('id', $Campaign->id)->increment('total_use', 1);
            \loyaltymodel\UserModel::where('id', $UserInfo['id'])->decrement('total_point', $Campaign->point);

            DB::connection('loyaltydb')->commit();

            if($Campaign->category_id == 1){
                DB::connection('accdb')->commit();
            }
        }catch (\Exception $e){
            $Code       = 'UPDATE_ERROR';
            $Message    = $e->getMessage();
            $DataUpdate = [
                'active'            => 2,
                'code'              => $Code,
                'error_message'     => $Message
            ];
            goto Finish;
        }

        $Code       = 'SUCCESS';
        $Message    = 'Thành công';
        $DataUpdate = [
            'active'            => 1,
            'code'              => $Code,
            'error_message'     => $Message
        ];
        goto Finish;

        Finish:
        $LMongo     = new \LMongo;
        $LMongo     = $LMongo::collection('loyalty_history')->where('_id', new \MongoId($LogCampaign['_id']))->update($DataUpdate);
        return Response::json(['error' => false, 'code' => $Code, 'error_message' => $Message]);
    }
}
