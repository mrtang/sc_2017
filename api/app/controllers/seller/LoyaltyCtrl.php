<?php namespace seller;

class LoyaltyCtrl extends BaseCtrl {
    private $data   = [];
    private $total  = 0;

    public function __construct()
    {
        $UserInfo   = $this->UserInfo();
        Input::merge(['country_id' => $UserInfo['country_id']]);
    }

    public function getIndex()
	{
        $UserInfo   = $this->UserInfo();

        $this->data['user']         = \loyaltymodel\UserModel::where('user_id', $UserInfo['id'])->first();
        $this->data['level']        = \loyaltymodel\HistoryModel::where('user_id', $UserInfo['id'])->orderBy('time_create','DESC')->take(10)->remember(10)->get()->toArray();
        $this->data['gift']         = \loyaltymodel\CampaignDetailModel::where('user_id', $UserInfo['id'])->orderBy('time_create','DESC')->with('get_campaign')->take(10)->remember(10)->get()->toArray();
        $this->data['point']        = \loyaltymodel\LogUpdatePointModel::where('user_id', $UserInfo['id'])->orderBy('time_create','DESC')->take(10)->remember(10)->get()->toArray();

        $DataAll                    = array_merge($this->data['level'], $this->data['gift']);

        $this->data['all'] = array_values(array_sort($DataAll, function($value){
            return $value['time_create'];
        }));
        return $this->ResponseData();
    }

    public function getListGift(){
        $UserInfo       = $this->UserInfo();
        $this->data     = \loyaltymodel\CampaignModel::where('time_start','<=',$this->time())
                                                 ->where('time_end','>=',$this->time())
                                                 ->where('active',1)
                                                 ->orderBy('category_id','ASC')->get(['id','name','link','value','point','category_id'])->toArray();
        return $this->ResponseData();
    }

    public function postCreate(){
        $Id             = Input::has('id')                  ? trim(Input::get('id'))                        : 0;
        $PhoneType      = Input::has('phone_type')          ? (int)Input::get('phone_type')                 : 0;
        $Phone          = Input::has('phone')               ? trim(Input::get('phone'))                     : '';

        $validation = Validator::make(Input::all(), array(
            'id'            => 'required|integer|min:1',
            'phone'         => 'sometimes|required',
            'phone_type'    => 'sometimes|required|in:1,2,3,4',
        ),[
            'required'  => 'Bạn chưa nhập :attribute, hãy thử lại'
        ]);

        //error
        if($validation->fails()) {
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => $validation->messages()]);
        }

        $UserInfo   = $this->UserInfo();
        $Campaign   = \loyaltymodel\CampaignModel::where('id', $Id)
                                                ->where('time_start','<=',$this->time())
                                                ->where('time_end','>=',$this->time())
                                                //->where('level','<=',$UserInfo->loy_level)
                                                //->whereRaw('total_use < total')
                                                ->where('active',1)
                                                ->first();

        if(!isset($Campaign->id)){
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => 'Đã hết hạn đổi thưởng cho phần thưởng này, vui lòng chọn phần thưởng khác.']);
        }

        if($Campaign->category_id == 2){
            if(empty($PhoneType)){
                return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => 'Bạn chưa chọn nhà mạng đổi thưởng, vui lòng thử lại.']);
            }

            if(empty($Phone)){
                return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => 'Bạn chưa nhập số điện thoại, vui lòng thử lại.']);
            }
        }

        $User       = \loyaltymodel\UserModel::where('user_id', $UserInfo['id'])->first();
        if(!isset($User->id)){
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => 'Tài khoản không đủ điều kiện tham gia đổi thưởng']);
        }

        if($User->level < $Campaign->level){
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => 'Bạn không đủ điều kiện đổi thưởng, vui lòng chọn phần thưởng khác.']);
        }

        if($User->total_point < $Campaign->point){
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => 'Điểm của bạn không đủ đổi phần thưởng này, vui lòng chọn phần thưởng khác.']);
        }

        if($Campaign->total_use >= $Campaign->total){
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => 'Phần thưởng đã được đổi đến giới hạn, vui lòng chọn phần thưởng khác.']);
        }

        if(date('m', $User->time_update) >= date('m', $this->time()) && date('Y', $User->time_update) >= date('Y', $this->time())){ // 1 tháng 1 lần
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => 'Bạn đã đổi thưởng trong tháng']);
        }

        // Hình thức cộng tiền vào tài khoản Shipchung
        $Insert     =   [
            'user_id'       => $UserInfo['id'],
            'campaign_id'   => $Campaign->id,
            'phone_type'    => $PhoneType,
            'phone'         => $Phone,
            'active'        => 0,
            'time_create'   => $this->time()
        ];

        try{
            $User->time_update = $this->time();
            $User->save();

        }catch (\Exception $e){
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => 'Thực hiện đổi thưởng thất bại, vui lòng thử lại!']);
        }

        $LMongo     = new \LMongo;
        $LMongo::collection('loyalty_history')->batchInsert($Insert);

        $Message = 'Đổi thưởng thành công, phần thưởng sẽ được gửi đến bạn trong 24h';
        if($Campaign->category_id == 1){
            $Message    = 'Chúc mừng bạn đã đổi thưởng thành công.';
        }

        return Response::json([
            'error'         => false,
            'code'          => 'SUCCESS',
            'error_message' => $Message
        ]);
    }

    public function ResponseData(){

        return Response::json([
            'error'         => false,
            'message'       => 'Thành công',
            'total'         => $this->total,
            'data'          => $this->data
        ]);
    }

}
