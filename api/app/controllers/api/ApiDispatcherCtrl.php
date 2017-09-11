<?php
class ApiDispatcherCtrl extends \BaseController {
    
    private $sgCourier,$listCacheCourier,
            $userId = 0, $childId = 0,$uDomain,
            $uCourier, $PickupLocation = 0, $DeliveryLocation = 0,
            $lCourier = [],
            $idLog,$dataCreate,$calculate,
            $autoAccept,
            $privilege = 0;

    
    private $insertBuyerAddress,$insertSellerAddress,
            $insertBuyer,$insertSeller,
            $insertOrders,
            $__Result,$__DataLog,$__TrackingCode,$__Inventory, $_SellerPvc, $_SellerCod;
    //Hash::make(Input::get('password'));
    
    private $validation;
    
    
    function __construct(){
        if (Request::isJson())
        {
            //Input::replace((array)Input::json());
        }
        
        // Load Courier
        $this->getCourier();
        $this->_getMerchantInfo();
        
        // Load CityId by Province Id
        $this->_getCityId();
        
        // Load inventory by MerchantInventory
        if(Input::has('MerchantInventory')){
            $this->_getInventoryInfo();
        }

        if(Input::has('Order.Weight') && (int)Input::get('Order.Weight') < 1 && !Input::has('Order.BoxSize')){
            $merge              = Input::get('Order');
            $merge['Weight']    = 350;
            Input::merge(['Order' => $merge]);
        }
    }
    
    
    private function _getCityId(){
        $DistArray = $merge = array();
        
        if(!Input::has('From.City') && Input::has('From.Province')){
            $DistArray[] = (int)Input::get('From.Province');
        }
        
        if(!Input::has('To.City') && Input::has('To.Province')){
            $DistArray[] = (int)Input::get('To.Province');
        }
        
        if(!empty($DistArray)){
            $dbData = DistrictModel::whereIn('id',$DistArray)->get(['id','city_id'])->toArray();
            if($dbData){
                foreach($dbData as $value){
                    if($value['id'] == Input::get('From.Province')){
                        $merge['From']          = Input::get('From');
                        $merge['From']['City']  = $value['city_id'];
                    }
                    
                    if($value['id'] == Input::get('To.Province')){
                        $merge['To']            = Input::get('To');
                        $merge['To']['City']    = $value['city_id'];
                    }
                }// endforeach
                
                Input::merge($merge);
                
            }// endif
        }
    }
    
    function postInventory(){
        $validation = Validator::make(Input::all(), 
                    array(
                        'MerchantKey'   => array('required'),
                    ));
                    
        if($validation->fails()) {
            return Response::json(array('error' => 'invalid', 'error_message' => $validation->messages()));
        }
        
        if($this->userId == 0){
            return Response::json(array('error' => 'not_exist_merchantkey', 'error_message' => 'Không tồn tại Merchant Key'));
        }
        
        
        $this->_getInventory();
        
        if(!$this->__Inventory){
            return Response::json(array('error' => 'not_exist_inventory', 'error_message' => 'Không tồn tại Kho hàng nào'));
        }
        
        foreach($this->__Inventory as $value){
            $output[] = array(
                'InventoryId' => $value['id'],
                'Name'     => $value['user_name'],
                'Phone'    => $value['phone'],
                'Address'  => $value['address'],
                'Province' => $value['province_id'],
                'City'     => $value['city_id'],
            );
        }
        
        return Response::json(array('error' => 'success', 'error_message' => 'Thành công','data' => $output));
    }
    
    private function _getInventoryInfo(){
        $dbInventory = sellermodel\UserInventoryModel::find((int)Input::get('MerchantInventory'));
        
        $merge = array(
            'Name'     => $dbInventory['user_name'],
            'Phone'    => $dbInventory['phone'],
            'Address'  => $dbInventory['address'],
            'Province' => $dbInventory['province_id'],
            'City'     => $dbInventory['city_id'],
            'Ward'     => $dbInventory['ward_id'],
            'Stock'    => $dbInventory['id']
        );
        
        Input::merge(array('From' => $merge));
    }
    
    
    /**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
        echo Hash::make(Input::get(md5(time())));die;
        return Response::json(1, 200);
	}

    private function _getMerchantInfo(){
        if (Session::has('user_info'))
        {
            if(Session::get('user_info')->parent_id > 0){
                $this->userId   = Session::get('user_info')->parent_id;
                $this->childId  = Session::get('user_info')->id;
            }
            else{
                $this->userId = Session::get('user_info')->id;
            }
        }
        elseif(Input::has('UserId')){
            $this->userId = (int)Input::get('UserId');
        }
        elseif(Input::has('MerchantKey')){
            $dbKey = ApiKeyModel::where('key',Input::get('MerchantKey'))->first(['user_id','auto']);
            $this->userId = empty($dbKey) ? 0 : $dbKey->user_id;
            $this->autoAccept = empty($dbKey) ? 0 : $dbKey->auto;
        }
        
        if($this->userId > 0 && !Session::has('user_info')){
            //$dbParent = sellermodel\UserInfoModel::where('user_id',$this->userId)->where('parent_id','>',0)->first(['parent_id', 'privilege']);
            $dbParent = sellermodel\UserInfoModel::where('user_id',$this->userId)->first(['parent_id', 'privilege']);
            
                if(isset($dbParent->parent_id) && $dbParent->parent_id > 0){
                    $this->userId    = $dbParent->parent_id;
                    $this->childId   = $this->userId;
                }
                
                if(isset($dbParent->privilege)){
                    $this->privilege = $dbParent->privilege;
                }
        }
        
        
    }
    
    private function _getInventory(){
        if($this->userId < 1){
            return false;
        }

        return $this->__Inventory = sellermodel\UserInventoryModel::where('user_id',(int)$this->userId)->where('active',1)->where('delete',0)->get()->toArray();
    }

    public function postAccept(){
        $validation = Validator::make(Input::all(), 
                    array(
                        'TrackingCode'  => array('required','regex:/SC[0-9]+$/'),
                        'MerchantKey'   => array('required'),
                    ));
                    
        if($validation->fails()) {
            return Response::json(array('error' => 'invalid', 'error_message' => $validation->messages()));
        }
        
        if($this->userId == 0){
            return Response::json(array('error' => 'not_exist_merchantkey', 'error_message' => 'Không tồn tại Merchant Key'));
        }
        
        Input::merge(['status' => 21,'UserInfo' => ['id' => $this->userId, 'privilege' => $this->privilege] ]);
        //return Response::json(Input::all());
        $obj    = new order\ChangeOrderCtrl;
        //$result = $obj->postEdit();
        
        return $obj->postEdit();
    }
    
    
    // Check Balance
    public function postBalance(){
        $validation = Validator::make(Input::all(), 
                    array(
                        'MerchantKey'   => array('required'),
                    ));
                    
        if($validation->fails()) {
            return Response::json(array('error' => 'invalid', 'error_message' => $validation->messages()));
        }
        
        if($this->userId == 0){
            return Response::json(array('error' => 'not_exist_merchantkey', 'error_message' => 'Không tồn tại Merchant Key'));
        }
        
        $Model = new accountingmodel\MerchantModel;

        try{
            $Merchant   = $Model::firstOrCreate(['merchant_id' => $this->userId]);
        }catch(Exception $e){
            return ['error' => 'GET_MERCHANT_FAIL','error_message'   => 'Không tìm thấy tài khoản ví'];
        }

        if(!isset($Merchant->active) || $Merchant->active != 1){
            return ['error' => 'USER_NOT_ALLOW_ACCEPT','error_message'   => 'Merchant chưa được kích hoạt ví'];
        }

        if(empty($Merchant->balance)){
            $Merchant->balance = 0;
        }

        if(empty($Merchant->freeze)){
            $Merchant->freeze = 0;
        }

        $Total = $Merchant->balance - $Merchant->freeze + $Merchant->provisional;

        if($Merchant->level == 2){
            $Total += $Merchant->quota;
        }
        
        if( Input::get('Domain') == 'chodientu.vn' && (Input::get('LadingCoD') == 1 || Input::get('Config.CoD') == 1) ){
            $Total += 200000;
        }

        return ['error' => 'success','data' => ['money_total'   => $Total]];
    }
    
	public function postStatus()
	{
        $validation = Validator::make(Input::all(), 
                    array(
                        //'TrackingCode' => array('required|array','regex:/SC[0-9]+$/'),
                        'TrackingCode' => 'required'
                    ));
                    
        if($validation->fails()) {
            return Response::json(array('error' => 'invalid', 'error_message' => $validation->messages()));
        }
        
        $tracking_code = is_array(Input::get('TrackingCode')) ? Input::get('TrackingCode') : array(Input::get('TrackingCode'));
        
        //$dbOrder = OrderOrdersModel::whereIn('tracking_code',$tracking_code)->get(['tracking_code','status'])->toArray();
        
        $dbOrder = ordermodel\OrdersModel::whereIn('tracking_code',$tracking_code)
                                ->where(function($query) {
                                    $query->where('time_accept','>=', time() - 86400*60)
                                        ->orWhere('time_accept',0);
                                })
                                ->with('GroupStatus')
                                ->get(['tracking_code','status'])->toArray();
        
        if(!$dbOrder){
            return Response::json(array('error' => 'fail', 'error_message' => 'not exist data','data' => null));
        }
        
        foreach($dbOrder as $value){
            $result[$value['tracking_code']] = array('StatusCode' => $value['group_status']['group_status']);
        }
        
        return Response::json(array('error' => 200, 'error_message' => 'success','data' => $result), 200);
	}

    //GET
    public function getOrderStatus(){
        $TrackingCode   = Input::has('tracking_code')   ? Input::get('tracking_code') : '';
        if(empty($TrackingCode)){
            return Response::json(array('error' => true, 'code' => 'EMPTY', 'error_message' => 'Chưa truyền mã đơn hàng'));
        }
        $TrackingCode = explode(',',$TrackingCode);
        $ListStatus   = Cache::get('list_status_cache');

        $dbOrder = ordermodel\OrdersModel::where(function($query) {
                $query->where('time_accept','>=', time() - 86400*93)
                    ->orWhere('time_accept',0);
            })
            ->whereIn('tracking_code',$TrackingCode)
            ->remember(10)
            ->get(['tracking_code','status', 'time_success'])->toArray();

        if(empty($dbOrder)){
            return Response::json(array('error' => true, 'code' => 'EMPTY', 'error_message' => 'Dữ liệu trống'));
        }

        $BaseCtrl   = new BaseCtrl;
        Input::merge(['group' => 3]);
        $Group      = $BaseCtrl->getGroupByStatus(false);

        $Res    = [];
        foreach($dbOrder as $val){
            $Res[$val['tracking_code']]['StatusId']             = (int)$val['status'];
            $Res[$val['tracking_code']]['StatusName']           = isset($ListStatus[(int)$val['status']]) ? $ListStatus[(int)$val['status']] : 'Trạng thái';
            $Res[$val['tracking_code']]['GroupId']              = (isset($Group[(int)$val['status']]) && isset($Group[(int)$val['status']]['group_status'])) ? $Group[(int)$val['status']]['group_status'] : 0;
            $Res[$val['tracking_code']]['GroupName']            = (isset($Group[(int)$val['status']]) && isset($Group[(int)$val['status']]['group_status'])) ? $Group[(int)$val['status']]['group_name'] : 'Nhóm';
            $Res[$val['tracking_code']]['TimeSuccess']          = (int)$val['time_success'];
        }

        return Response::json(['error' => false, 'code' => 'SUCCESS', 'error_message' => 'Thành công','data' => $Res]);
    }

    //Post
    public function postOrderStatus(){
        $TrackingCode   = Input::has('tracking_code')   ? Input::get('tracking_code') : '';
        if(empty($TrackingCode)){
            return Response::json(array('error' => true, 'code' => 'EMPTY', 'error_message' => 'Chưa truyền mã đơn hàng'));
        }
        $TrackingCode = explode(',',$TrackingCode);
        $ListStatus   = Cache::get('list_status_cache');

        $dbOrder = ordermodel\OrdersModel::where(function($query) {
                $query->where('time_accept','>=', time() - 86400*60)
                    ->orWhere('time_accept',0);
            })
            ->whereIn('tracking_code',$TrackingCode)
            ->remember(10)
            ->get(['tracking_code','status'])->toArray();

        if(empty($dbOrder)){
            return Response::json(array('error' => true, 'code' => 'EMPTY', 'error_message' => 'Dữ liệu trống'));
        }

        $BaseCtrl   = new BaseCtrl;
        Input::merge(['group' => 3]);
        $Group      = $BaseCtrl->getGroupByStatus(false);

        $Res    = [];
        foreach($dbOrder as $val){
            $Res[$val['tracking_code']]['StatusId']             = (int)$val['status'];
            $Res[$val['tracking_code']]['StatusName']           = isset($ListStatus[(int)$val['status']]) ? $ListStatus[(int)$val['status']] : 'Trạng thái';
            $Res[$val['tracking_code']]['GroupId']              = (isset($Group[(int)$val['status']]) && isset($Group[(int)$val['status']]['group_status'])) ? $Group[(int)$val['status']]['group_status'] : 0;
            $Res[$val['tracking_code']]['GroupName']            = (isset($Group[(int)$val['status']]) && isset($Group[(int)$val['status']]['group_status'])) ? $Group[(int)$val['status']]['group_name'] : 'Nhóm';
        }

        return Response::json(['error' => false, 'code' => 'SUCCESS', 'error_message' => 'Thành công','data' => $Res]);
    }

    private function _validation($create = false){
        $dataInput = array(
            'From'              => 'required|array',
            //'From.City'         => 'required|numeric',
            'From.Province'     => 'required|numeric',
            //'To.City'           => 'required|numeric',
            'To.Province'       => 'required|numeric',
            'To.Ward'           => 'numeric',
            
            'Order.Amount'       => 'required|numeric',
            'Order.Weight'       => 'numeric',
            'Order.Collect'      => 'numeric',
            
            'Config'            => 'required',
            'Config.Service'    => 'required|numeric|in:1,2,3,4',
            'Config.CoD'        => 'required|numeric|in:1,2', // 1: yes | 2: no
            'Config.Protected'  => 'required|numeric|in:1,2',
            'Config.Payment'    => 'sometimes|required|numeric|in:1,2',
            'Config.Checking'   => 'required|numeric|in:1,2',
            'Config.Fragile'    => 'required|numeric|in:1,2',
            
            'Domain'            => 'required',
        );
        
        if($create){
            $dataInput += array(
                'Courier'               => 'required|numeric',
                'MerchantKey'           => $this->userId == 0 ? 'required' : '',
                'Order.ProductName'     => 'required',
                'Order.Quantity'        => 'required|numeric',
                
                //'Items'             => 'required|array',
                //'Items.Name'        => 'required',
                //'Items.Price'       => 'required|numeric',
                //'Items.Quantity'    => 'required|numeric',
                //'Items.Weight'      => 'required|numeric',
                
                'From.Name'         => 'required',
                'From.Phone'        => 'required',
                'From.Address'      => 'required',
                
                'To.Name'           => 'required',
                'To.Phone'          => 'required',
                'To.Address'        => 'required',
            );
        }
                               
        $this->validation = Validator::make(Input::all(), $dataInput);
    }


    /**
	 * Calculate.
	 *
     * @param
     *      - UserId
     *      - From (array[])    
     *          - District
     *          - Province
     *          - Ward
     *      - To (array[])    
     *          - District
     *          - Province
     *          - Ward
     *      - Items (array[])
     *          - Name
     *          - Link
     *          - Image
     *          - Price
     *          - Note
     *          - Quantity
     *          - Weight (1sp)
     *      - Data
     *          - Amount
     *          - Quantity
     *          - Weight
     *          - Collect
     *      - Config
     *          - Service   1: Nhanh; 2: Cham
     *          - Cod       1: Yes; 2: No
     *          - Protect   1: Yes; 2: No
     *          - Payment   1: Toi tra; 2: Ng mua tra
     *          - Checking  1: Yes; 2: No
     *          - Fragile   1: Yes; 2: No
     *      - Coupon
     * 
	 * @return Response
	 */
	public function postCalculate()
	{   
        $this->_validation();
        // Check và báo invalid
        if($this->validation->fails()) {
            return Response::json(array('error' => 'invalid', 'error_message' => $this->validation->messages()));
        }

        if(!Input::has('Order.BoxSize') && !Input::has('Order.Weight')){
            return Response::json(array('error' => 'invalid', 'error_message' => 'value BoxSize or Weight empty'));
        }

        if(Input::has('Order.BoxSize')){
            $str = explode('x',Input::get('Order.BoxSize'));
            $sMerge = Input::get('Order');
            $sMerge['Weight'] = ceil(($str[0] * $str[1] * $str[2]) / ( Input::get('Config.Service') == 1 ? 3 : 6 ));
            Input::merge(array('Order' => $sMerge));
        }

        $courier = $this->SuggestCourier();
        if(isset($courier['error']))
            return Response::json($courier);
            
        $this->ConfigUserCarrier();
        $calculate = $this->_calculate();
        //
        if($calculate['error'] != 200){
            return Response::json($calculate);
        }

        $rCalculate = array('data' => array('fee' => $this->calculate));
        
        foreach($courier as $value){
            $who = in_array($value['courier_id'],$this->uCourier) ? 'me' : 'system';

            if($value['courier_id'] != 8){
                if( ($this->PickupLocation > 1 && Input::get('From.Province')   == Input::get('To.Province')) // Ngoại thành giao cùng huyện
                    || ( $this->PickupLocation == 1 && Input::get('From.City')      == Input::get('To.City') ) // Nội thành
                    || ($this->PickupLocation >= 1 && Input::get('From.City')       != Input::get('To.City') ) // Liên tỉnh
                    || ($this->PickupLocation == 2 && $this->DeliveryLocation >= 3) ) // Nội thành đi huyện xã
                {
                    $value['money_pickup'] = $value['money_delivery'] = 0;
                }
            }
            
            $rCalculate['data']['courier'][$who][] = $value;
        }
        
        return Response::json($rCalculate);
	}
    
    
    private function _MerchantValidation($create = false){
        $dataInput = array(
            'MerchantKey'       => $this->userId == 0 ? 'required' : '',
            'Domain'            => 'required',

            //'To.City'           => 'required|numeric',
            'To.Province'       => 'required|numeric',
            //'To.Ward'           => 'numeric',
            
            'Order.Amount'       => 'required|numeric',
            'Order.Weight'       => 'required|numeric',
            //'Order.Collect'      => 'numeric',
        );
        
        if($create){
            $dataInput += array(
                'Order.ProductName'     => 'required',
                'Order.Quantity'        => 'required|numeric',
                
                'Items'             => 'required|array',
                
                'To.Name'           => 'required',
                'To.Phone'          => 'required',
                'To.Address'        => 'required',
            );
        }
                               
        $this->validation = Validator::make(Input::all(), $dataInput);
    }
    
    function postCheckmerchantkey(){
        $validation = Validator::make(Input::all(), 
                    array(
                        'MerchantKey' => 'required|min:32|max:32'
                    ));
                    
        if($validation->fails()) {
            return Response::json(array('error' => 'invalid', 'error_message' => $validation->messages()));
        }
        
        if($this->userId > 0){
           $dbUser = User::where('id',$this->userId)->first(array('email'));
           return Response::json(array('error' => 'success', 'error_message' => 'success','data' => $dbUser));
        }
        
        return Response::json(array('error' => 'not_exist_merchantkey', 'error_message' => 'Không tồn tại hoặc chưa cấu hình Merchant Key'));
    }
        
    function postMerchantcalculate($json = true){
        $this->_MerchantValidation();
        
        // Check và báo invalid
        if($this->validation->fails()) {
            return Response::json(array('error' => 'invalid', 'error_message' => $this->validation->messages()));
        }
        
        // Get Inventory từ User Id        
        $this->_getInventory();
        
        if(empty($this->__Inventory)){
            return Response::json( array('error' => 'inventory', 'error_message' => 'Bạn chưa cấu hình kho hàng.') );
        }
        
        // Define 
        $arrCalculate = $return = array();
        $me = $system = 0;

        foreach($this->__Inventory as $inventory){
            Input::merge( array(
                        'From'     => array('Stock' => $inventory['id'] ,'City' => $inventory['city_id'],'Province' => $inventory['province_id'], 'Ward' => $inventory['ward_id'])
                        ) );

            $courier = $this->SuggestCourier();

            if(isset($courier['error']))
            {
                continue;return;
            }

            $this->_calculate();     
                        
            foreach($courier as $value){
                $owner = $this->uCourier && in_array($value['courier_id'],$this->uCourier) ? 'me' : 'system';
                
                if($value['leatime_total'] < $$owner || !isset($return['courier'][$owner]))             
                {
                    if($value['courier_id'] != 8){
                        if( ($this->PickupLocation > 1 && Input::get('From.Province')   == Input::get('To.Province')) // Ngoại thành giao cùng huyện
                            || ( $this->PickupLocation == 1 && Input::get('From.City')      == Input::get('To.City') ) // Nội thành
                            || ($this->PickupLocation >= 1 && Input::get('From.City')       != Input::get('To.City') ) // Liên tỉnh
                            || ($this->PickupLocation == 2 && $this->DeliveryLocation >= 3) ) // Nội thành đi huyện xã
                        {
                            $value['money_pickup'] = $value['money_delivery'] = 0;
                        }
                    }

                    $return['fee']                 = $this->calculate;
                    $return['inventory']           = $inventory['id'];
                    $return['courier'][$owner]     = $value;
                    $$owner                        = $value['leatime_total'];
                }
                
            }
            
            if(isset($return['courier']['me']) && isset($return['courier']['system'])){
                unset($return['courier']['system']);
            }
            
            if($this->calculate['total_fee'] > 0){
                $arrCalculate[$this->calculate['total_fee']] = $return;
            }

        }
        
        if(!$arrCalculate){
            return Response::json( array( 'error' => 'fail', 'error_message' => 'Không hỗ trợ vận chuyển tuyến đường này.','data' => null ) );
        }
        
        // Ưu tiên thằng nhỏ
        ksort($arrCalculate);
        
        return 
        $json ? Response::json(array('error' => 'success', 'error_message' => 'Tính phí thành công','data' => current($arrCalculate))) 
        : array('error' => 'success','data' => current($arrCalculate));
    }

    function postMerchantcalculateexcel($json = true){
        $this->_MerchantValidation();

        // Check và báo invalid
        if($this->validation->fails()) {
            return Response::json(array('error' => 'invalid', 'error_message' => $this->validation->messages()));
        }

        /* Get Inventory từ User Id
        $this->_getInventory();

        if(empty($this->__Inventory)){
            return Response::json( array('error' => 'inventory', 'error_message' => 'Bạn chưa cấu hình kho hàng.') );
        }*/

        // Define
        $arrCalculate = $return = array();
        $me = $system = 0;

            $courier = $this->SuggestCourier();
            if(isset($courier['error']))return Response::json($courier);

            $this->_calculate();

            foreach($courier as $value){
                $owner = $this->uCourier && in_array($value['courier_id'],$this->uCourier) ? 'me' : 'system';

                if($value['leatime_total'] < $$owner || !isset($return['courier'][$owner]))
                {
                    if($value['courier_id'] != 8){
                        if( ($this->PickupLocation > 1 && Input::get('From.Province')   == Input::get('To.Province')) // Ngoại thành giao cùng huyện
                            || ( $this->PickupLocation == 1 && Input::get('From.City')      == Input::get('To.City') ) // Nội thành
                            || ($this->PickupLocation >= 1 && Input::get('From.City')       != Input::get('To.City') ) // Liên tỉnh
                            || ($this->PickupLocation == 2 && $this->DeliveryLocation >= 3) ) // Nội thành đi huyện xã
                        {
                            $value['money_pickup'] = $value['money_delivery'] = 0;
                        }
                    }

                    $return['fee']                 = $this->calculate;
                    $return['inventory']           = Input::get('MerchantInventory');
                    $return['courier'][$owner]     = $value;
                    $$owner                        = $value['leatime_total'];
                }

            }

            if(isset($return['courier']['me']) && isset($return['courier']['system'])){
                unset($return['courier']['system']);
            }

            if($this->calculate['total_fee'] > 0){
                $arrCalculate[$this->calculate['total_fee']] = $return;
            }

        if(!$arrCalculate){
            return Response::json( array( 'error' => 'fail', 'error_message' => 'Không hỗ trợ vận chuyển tuyến đường này.','data' => null ) );
        }

        // Ưu tiên thằng nhỏ
        ksort($arrCalculate);

        return
            $json ? Response::json(array('error' => 'success', 'error_message' => 'Tính phí thành công','data' => current($arrCalculate)))
                : array('error' => 'success','data' => current($arrCalculate));
    }
    
    
    public function postCreateformulti(){
        $MultiCalculate = $this->postMerchantcalculateexcel(false);

        if(!is_array($MultiCalculate)){
            return $MultiCalculate;
        }
        
        if(!isset($MultiCalculate['error']) || $MultiCalculate['error'] != 'success'){
            return Response::json($MultiCalculate);
        }
        
        if(isset($MultiCalculate['data'])){
            if(isset($MultiCalculate['data']['courier']['me'])){
                Input::merge(array('Courier' => (int)$MultiCalculate['data']['courier']['me']['courier_id']));
            }
            elseif(isset($MultiCalculate['data']['courier']['system'])){
                Input::merge(array('Courier' => (int)$MultiCalculate['data']['courier']['system']['courier_id']));
            }
            else{
                return Response::json(array('error' => 'Courier empty', 'error_message' => 'Truyền thiếu Hãng Vận Chuyển','data' => null));
            }
            
            //$this->_getInventoryInfo();
        }
        
        return $this->postCreate();
    }
    
    
    function postMerchantcreate(){
        $MultiCalculate = $this->postMerchantcalculate(false);

        if(!is_array($MultiCalculate)){
            return $MultiCalculate;
        }

        if(!isset($MultiCalculate['error']) || $MultiCalculate['error'] != 'success'){
            return Response::json($MultiCalculate);
        }

        if(isset($MultiCalculate['data'])){

            Input::merge(array('MerchantInventory' => (int)$MultiCalculate['data']['inventory']));
            if(isset($MultiCalculate['data']['courier']['me'])){
                Input::merge(array('Courier' => (int)$MultiCalculate['data']['courier']['me']['courier_id']));
            }
            elseif(isset($MultiCalculate['data']['courier']['system'])){
                Input::merge(array('Courier' => (int)$MultiCalculate['data']['courier']['system']['courier_id']));
            }
            else{
                return Response::json(array('error' => 'Courier empty', 'error_message' => 'Truyền thiếu Hãng Vận Chuyển','data' => null));
            }

            $this->_getInventoryInfo();
        }

        return $this->postCreate();
    }

    /**
    * Cancel order
    * @author ThinhNV
    */
    public function postCancel(){
        $validation = Validator::make(Input::all(), 
                    array(
                        'TrackingCode'  => array('required','regex:/SC[0-9]+$/'),
                        'MerchantKey'   => array('required'),
                    ));
                    
        if($validation->fails()) {
            return Response::json(array('error' => 'invalid', 'error_message' => $validation->messages()));
        }
        
        if($this->userId == 0){
            return Response::json(array('error' => 'not_exist_merchantkey', 'error_message' => 'Không tồn tại Merchant Key'));
        }

        Input::merge(['from_merchant_api' => true, 'status' => 28,'UserInfo' => ['id' => $this->userId, 'privilege' => $this->privilege] ]);
        $obj    = new order\ChangeOrderCtrl;
        
        return $obj->postEdit();
    }

    /**
     *  Create Inventory
     */
    private function CreateInventory(){
        $Domain         = Input::has('Domain')          ? strtolower(trim(Input::get('Domain')))    : 'shipchung.vn';
        $InventoryId    = Input::has('From.Inventory')  ? trim(Input::get('From.Inventory'))        : 0;
        $Phone          = Input::has('From.Phone')      ? trim(Input::get('From.Phone'))            : '';


        if(!empty($InventoryId)){
            //Check exists
            $Model  = new sellermodel\UserInventoryModel;
            $Inventory  = $Model->where('sys_name',$Domain)->where('sys_number',$InventoryId)->first(['id']);
            if(isset($Inventory->id)){
                $Inventory  = $Inventory->id;
            }else{
                $Model  = new sellermodel\UserInventoryModel;
                $Inventory  = $Model->insertGetId([
                    'user_id'    => $this->userId,
                    'sys_name'   => $Domain,
                    'sys_number' => $InventoryId,
                    'name'       => Input::has('From.Name') ? Input::get('From.Name') : 'Kho hàng từ '.$Domain,
                    'user_name'  => Input::has('From.Name') ? Input::get('From.Name') : 'Kho hàng từ '.$Domain,
                    'phone'      => $Phone,
                    'city_id'       => (int)Input::get('From.City'),
                    'province_id'   => (int)Input::get('From.Province'),
                    'ward_id'       => (int)Input::get('From.Ward'),
                    'address'       => Input::get('From.Address'),
                    'active'        => 1,
                    'time_create'   => time()
                ]);
            }

            Input::merge(['From.Stock' => (int)$Inventory]);
        }
        return;
    }


    
    /**
     *      - From
     *          - Name
     *          - Phone
     *          - Address
     *      - To
     *          - Name
     *          - Phone
     *          - Address                        
     *      - Items (array[])
     *          - Name
     *          - Link
     *          - Image
     *          - Price
     *          - Note
     *          - Quantity
     *          - Weight (1sp)
     *      - Courier (int)     
     * */            
    public function postCreate( $json = true )
	{
        // Đặt Rule dữ liệu đầu vào
        if(empty($this->userId)){
            $content = array('error' => 'true', 'error_message' => 'MERCHANT_KEY_NOT_EXISTS');
            return $json ? Response::json($content) : $content;
        }

        $Domain     = strtolower(trim(Input::get('Domain')));
        $OrderCode  = Input::has('Order.Code') ? trim(Input::get('Order.Code')) : '';
        if($Domain == 'chodientu.vn' && !empty($OrderCode)) {
            $DB = DB::connection('orderdb');
            try {
                $DB->table('code')->insert(
                    array('domain' => $Domain, 'order_code' => $OrderCode)
                );
            }catch (Exception $e){
                $Content        = 'mã vận đơn này đã tồn tại: ';

                $OrdersModel    = new ordermodel\OrdersModel;
                $CheckOrderCode = $OrdersModel::where(function($query) {
                    $query->where('time_accept','>=', time() - 86400*60)
                        ->orWhere('time_accept',0);
                })->where('order_code', $OrderCode)->first(['id','tracking_code','order_code']);

                if(isset($CheckOrderCode->id)){
                    $Content .= $CheckOrderCode->tracking_code;
                }
                //return Response::json(array('error' => 'true', 'code' => 'DUPLICATE_ORDER_CODE_'.$OrderCode, 'error_message' => $Content));
                $content = array('error' => 'true', 'code' => 'DUPLICATE_ORDER_CODE_'.$OrderCode, 'error_message' => $Content);
                return $json ? Response::json($content) : $content;
            }
        }
        // Validation
        if(empty($this->do))
        $this->_validation(true);      
        
        if(!Input::has('Order.BoxSize') && !Input::has('Order.Weight')){
            
            //return Response::json();
            $content = array('error' => 'invalid', 'error_message' => 'value BoxSize or Weight empty');
            return $json ? Response::json($content) : $content;
        }

        if(Input::has('Order.BoxSize')){
            $str = explode('x',Input::get('Order.BoxSize'));
            $sMerge = Input::get('Order');
            $sMerge['Weight'] = ceil(($str[0] * $str[1] * $str[2]) / ( Input::get('Config.Service') == 1 ? 3 : 6 ));
            Input::merge(array('Order' => $sMerge));
        }        
        
        $LMongo         = new LMongo;
        // Log đầu vào
        $this->idLog    = $LMongo::collection('log_create_lading')->insert(array( 'input' => Input::all(),'time_create' => time(),'date_create' => date('d/m/Y H:i:s') ));
        // Check và báo invalid
        if($this->validation->fails()) {
            $LMongo::collection('log_create_lading')->where('_id', new \MongoId($this->idLog))->update(array('error' => json_encode($this->validation->messages())));

            $content = array('error' => 'invalid', 'error_message' => $this->validation->messages());
            return $json ? Response::json($content) : $content;
            //return Response::json(array('error' => 'invalid', 'error_message' => $this->validation->messages()));
        }
        
        // Chay tinh phi
        $this->_calculate();
        if(isset($this->calculate['error'])){
            $LMongo::collection('log_create_lading')->where('_id', new \MongoId($this->idLog))->update($this->calculate);

            return $json ? Response::json($this->calculate) : $this->calculate;
            //return Response::json($this->calculate);
        }

        //get courier
        $courier = $this->SuggestCourier();
        if(isset($courier['error']))
            return $json ? Response::json($courier) : $courier;
            //return Response::json($courier);

        foreach($courier as $value){
            if($value['courier_id'] == (int)Input::get('Courier')){
                
                if($value['courier_id'] != 8){
                    if( ($this->PickupLocation > 1 && Input::get('From.Province')   == Input::get('To.Province')) // Ngoại thành giao cùng huyện
                    || ( $this->PickupLocation == 1 && Input::get('From.City')      == Input::get('To.City') ) // Nội thành
                    || ($this->PickupLocation >= 1 && Input::get('From.City')       != Input::get('To.City') ) // Liên tỉnh
                    || ($this->PickupLocation == 2 && $this->DeliveryLocation >= 3)) // Nội thành đi huyện xã
                    {
                        $value['money_pickup'] = $value['money_delivery'] = 0;
                    }
                }
                
                
                $this->lCourier = $value;
            }
        }

        
        if(Input::has('Order.Weight') > 200000){
            //return Response::json(array('error' => 'error_weight', 'error_message' => 'Khối lượng tối đa 200kg'));
            $content = array('error' => 'error_weight', 'error_message' => 'Khối lượng tối đa 200kg');
            return $json ? Response::json($content) : $content;
        }

        //kiểm tra và tạo kho từ khu vực khác
        if(Input::has('From.Inventory') && Input::has('From.Phone')){
            $this->CreateInventory();
        }

        // Chạy tạo vận đơn
        $this->_create();
        if($this->__Result['ERROR'] == 'SUCCESS')
        {
            //update first time order
            $checkExist = omsmodel\CustomerAdminModel::where('user_id',$this->userId)->where('first_order_time',0)->first();
            if(!empty($checkExist)){
                $Update = omsmodel\CustomerAdminModel::where('user_id',$this->userId)->update(array('first_order_time' => time(),'first_tracking_code' => $this->__TrackingCode));
            }
            //update last time order
            $checkExistUser = omsmodel\CustomerAdminModel::where('user_id',$this->userId)->first();
            if(!empty($checkExistUser)){
                $Update = omsmodel\CustomerAdminModel::where('user_id',$this->userId)->update(array('last_order_time' => time()));
            }
            $respond = array(
                            'error' => 'success',
                            'error_message' => 'Tạo vận đơn thành công',
                            'data' => array(
                                'TrackingCode'  => $this->__TrackingCode,
                                'CourierId'     => (int)Input::get('Courier'),
                                //'CourierName'   => $this->listCacheCourier[(int)Input::get('Courier')],
                                'MoneyCollect'  => isset($this->calculate['collect']) ? $this->calculate['collect'] : 0,
                                'ShowFee'       => [
                                                        'pvc' => $this->calculate['total_fee'], 
                                                        'cod' => isset($this->calculate['vas']['cod'])          ? (int)$this->calculate['vas']['cod']       : 0,
                                                        'pbh' => isset($this->calculate['vas']['protected'])    ? (int)$this->calculate['vas']['protected'] : 0,
                                                    ],
                                'Discount'      => isset($this->calculate['discount']) ? $this->calculate['discount'] : null,
                            ),
                        );
        }
        else{
            $respond = array('error' => 'fail','error_message' => 'Tạo vận đơn thất bại', 'data' => $this->__Result);
        }

        $LMongo::collection('log_create_lading')->where('_id', new \MongoId($this->idLog))
                            ->update(array(
                                'error'     => $this->__Result['ERROR'],
                                'trackingcode'  => $this->__TrackingCode,
                                'message'   => $this->__Result['MSG'],
                                'datalog'   => $this->__DataLog,
                                'output'    => $respond,
                            ));
                   
        return $json ? Response::json($respond) : $respond;
	}
    
    function SuggestCourier(){      
        $arrCourier = $arrDeCourier = $estimate_pickup = $OuputCourier = $CourierRefuse = array();
        // Check theo Area
        $PickupArea = PromisePickupModel
                        ::where('province_id',Input::get('From.City'))
                        ->whereIn('district_id',array(0,Input::get('From.Province')))
                        ->where('service_id',Input::get('Config.Service'))
                        ->where('courier_id','!=',3)
                        ->where('active',1)->orderBy('district_id','desc')->orderBy('estimate_pickup')
                        ->get(['id','courier_id','estimate_pickup','district_id']);

        if($PickupArea->isEmpty()){
            return array(
                'error'         => 'unsupported', 
                'error_message' => 'Shipchung chưa hỗ trợ lấy hàng tại khu vực này, vui lòng liên hệ CSKH để được hỗ trợ.',
                'debug'         => $PickupArea
            );
        }

        foreach($PickupArea as $value){
            if(!in_array($value['courier_id'],$arrCourier)){
                $arrCourier[]                           = $value['courier_id'];
                $estimate_pickup[$value['courier_id']]  = $value['estimate_pickup'];
            }            
        }

        $DeliveryArea = PromiseDeliveryModel
                        ::where('from_province',Input::get('From.City'))
                        ->where('to_province',Input::get('To.City'))
                        ->whereIn('courier_id',$arrCourier)
                        ->whereIn('to_district',array(0,Input::get('To.Province')))
                        ->where('service_id',Input::get('Config.Service'))
                        ->where('active',1)->orderBy('to_district','desc')->orderBy('estimate_delivery')
                        ->get(['id','courier_id','estimate_delivery','to_district','estimate_ward','optional']);
        

        if($DeliveryArea->isEmpty()){
            return array(
                'error'         => 'unsupported', 
                'error_message' => 'Shipchung chưa hỗ trợ giao hàng tới khu vực này, vui lòng liên hệ CSKH để được hỗ trợ.',
                'debug'         => $DeliveryArea
            );
        }
        
        // check support ward
        $ToAddress = trim(Input::get('To.Address'));
        if(Input::has('To.Ward') && (int)Input::get('To.Ward') > 0){
            $dbRefuse = CourierRefuseModel::where('ward_id',(int)Input::get('To.Ward'))->whereIn('courier_id',$arrCourier)->get(['courier_id']);
            if(!$dbRefuse->isEmpty()){
                foreach($dbRefuse as $iRefuse){
                    $CourierRefuse[] = $iRefuse['courier_id'];
                }
            }
        }elseif(!empty($ToAddress)){
            $dbRefuse   = CourierRefuseModel::where('district_id', (int)Input::get('To.Province'))->whereIn('courier_id',$arrCourier)->get(['ward_id', 'courier_id'])->toArray();
            if(!empty($dbRefuse)){
                $ListWard       = [];
                $ListCourier    = [];
                foreach($dbRefuse as $val){
                    $ListWard[]                             = (int)$val['ward_id'];
                    $ListCourier[(int)$val['ward_id']]      =  (int)$val['courier_id'];
                }

                if(!empty($ListWard)){
                    $ListWard   = WardModel::whereIn('id', $ListWard)->get(['ward_name','id'])->toArray();
                    foreach($ListWard as $val){
                        $WardName = trim(str_replace(['Thị Trấn','Xã', 'ấp', 'cụm', 'Ấp', 'Cụm'],'',$val['ward_name']));
                        if(preg_match('/'.$WardName.'/i', $ToAddress)){
                            $CourierRefuse[]    = $ListCourier[(int)$val['id']];
                        }
                    }
                }
            }
        }
        
        foreach($DeliveryArea as $value){
            if(!in_array($value['courier_id'],$arrDeCourier) && !in_array($value['courier_id'],$CourierRefuse)){                
                $arrDeCourier[] = $value['courier_id'];
                $leatime_total  = $estimate_pickup[$value['courier_id']] + $value['estimate_delivery'] + $value['estimate_ward'];
                $OuputCourier[] = array(
                            'courier_id'        => $value['courier_id'],
                            'courier_name'      => $this->listCacheCourier[$value['courier_id']]['name'],
                            'money_pickup'      => (int)$this->listCacheCourier[$value['courier_id']]['money_pickup'],
                            'money_delivery'    => (int)$this->listCacheCourier[$value['courier_id']]['money_delivery'],
                            'leatime_pickup'    => $estimate_pickup[$value['courier_id']],
                            'optional'          => $value['optional'],
                            'leatime_delivery'  => $value['estimate_delivery'],
                            'leatime_ward'      => $value['estimate_ward'],
                            'leatime_total'     => $leatime_total,
                        );                
            }

        }
        
        if(empty($OuputCourier)){
            return array(
                'error'         => 'unsupported', 
                'error_message' => 'Shipchung chưa hỗ trợ giao hàng tới khu vực này, vui lòng liên hệ CSKH để được hỗ trợ.'
            
            );
        }

        $OuputCourier = array_values(array_sort($OuputCourier, function($value){
            return $value['leatime_total'];
        }));

        return $OuputCourier;
    }
    
    function ConfigUserCarrier(){
        $this->uCourier  = array();
        
        if($this->userId > 0){
            
            if(Input::get('To.City') == Input::get('From.City') && Input::get('From.City') == 18){
                $where = array('support_hn',1);
            }
            elseif(Input::get('To.City') == Input::get('From.City') && Input::get('From.City') == 52){
                $where = array('support_hcm',1);
            }
            else{
                $where = array('support_other',1);
            }
            
            $dbConfig = sellermodel\CourierModel::where('active',1)
                                                    ->where('user_id',$this->userId)
                                                    ->where($where[0],$where[1])
                                                    ->get(['courier_id'])->toArray();
            
            if(!empty($dbConfig)){
                foreach($dbConfig as $value){
                    $this->uCourier[] = $value['courier_id'];
                }
            }
            return $this->uCourier;
        }
        
        return $this->uCourier  = array();
    }
    
    public function _calculate( $Courier = 0 ){
        $Service            = Input::get('Config.Service');
        $Vas                = Input::get('Config');
        $Location           = array('To' => Input::get('To'),'From' => Input::get('From'));
        $Input              = Input::get('Order');
        
        $this->uDomain      = strtolower(Input::get('Domain'));
                
        // Define --
        $ListVas            = 
        $DbQueryVas         =
        $DbVasPrice = array();
        
        if($Vas){
            if(isset($Vas['Service'])){
                unset($Vas['Fragile']);
                unset($Vas['Checking']);
                unset($Vas['Service']);
            }
            
            if(Input::has('Config.CoD') && (int)Input::get('Config.CoD') == 0)
            {
                unset($Vas['CoD']);
            }
            
            foreach($Vas as $key => $val){
                if($val == 1){
                    $ListVas[]  = strtolower($key);
                }
            }
        }

        // Get Courier Default
        if($Courier == 0)
        {
            $CourierDb          = CourierModel::where('default','=',1)->first(array('id'))->toArray();
            $Courier            = $CourierDb['id'];
        }
        
        $ToAreaQuery        = AreaLocationModel::where('province_id','=',$Location['To']['Province'])
                                ->where('city_id','=',$Location['To']['City'])
                                ->where('active','=',1)
                                ->get(array('area_id','location_id'))->toArray();

        if(!$ToAreaQuery){
            return array(
                'error'         => 'unsupported', 
                'error_message' => 'system not support area'
            );
        }


        //return $ToAreaQuery;
        
        foreach($ToAreaQuery as $value){
            $arrArea[]  = $value['area_id'];
            $locationID = $value['location_id'];
        }

        $this->DeliveryLocation = $locationID;

        $RouterQuery        = CourierRouter::whereIn('to_area_id',$arrArea)
                                ->where('courier_id','=',$Courier)
                                ->where('service_id','=',Input::get('Config.Service'))
                                ->where('from_province_id','=',$Location['From']['Province'])
                                ->where('active','=',1)->first(array('id','courier_id','fee_id'));

        //return $RouterQuery;
        if(!$RouterQuery){
            return array(
                'error'         => 'unsupported', 
                'error_message' => 'system not support router'
            );
        }
        
        // location Pickup
        $dbLocationPick = CourierLocationPickupModel::where('district_id',$Location['From']['Province'])->first(['location']);
        if($dbLocationPick){
            $this->PickupLocation = $dbLocationPick['location'];
        }
        
        // Select Fee
        // Chặn khi khối lượng vượt quá 200.000kg
        if($Input['Weight'] > 200000){
            return array(
                'error'         => 'unsupported',
                'error_message' => 'system not support'
            );
        }

        $CourierFee         = CourierFeeModel::where('id','=',$RouterQuery['fee_id'])
                                 ->where('active','=',1)
                                 ->first(array('vat'));

        $CourierFeeDetail   = CourierFeeDetailModel::where('fee_id','=',$RouterQuery['fee_id'])
                                ->where('weight_start','<',$Input['Weight'])
                                ->where('weight_end','>=',$Input['Weight'])
                                ->first(array('money','surcharge'));
        //return $CourierFeeDetail;
        $total_fee = $CourierFeeDetail['money'];
        
        if($locationID > 1 && $Location['To']['City'] == $Location['From']['City']){
            $total_fee += $CourierFeeDetail['surcharge'];
        }
        elseif($locationID > 2){
            $total_fee += $CourierFeeDetail['surcharge'];
        }
        
        if($Location['To']['City'] == $Location['From']['City'] && $locationID >=3 && in_array($Location['From']['City'],array(18,52))){
            $total_fee = $total_fee * 1.2;
        }
        
        $total_fee += $total_fee * $CourierFee['vat'];
        
        $OutputFee = array(
            //'pickup'    => 10000,
            //'delivery'  => 20000,
            'total_fee' => $total_fee,
//            'collect'   => 530000,
//            'vas'       => array(
//                        'protect' => 5000,
//                        'cod'       => 10000
//                        )
        );
        /////////////////////// == ///////////////////////

        // vas fee
        if(!empty($ListVas)){
            $CourierVas     = CourierVasModel::whereIn('code',$ListVas)
                                             ->where('active','=',1)
                                             ->get(array('id','code','vas_value_type'))->toArray();

            //return $CourierVas;
            if(!empty($CourierVas)){
                foreach($CourierVas as $val){
                    if($val['vas_value_type'] == 1){
                        $VasIdPrice[]   = $val['id'];
                    }
                    
                    if($val['vas_value_type'] == 2){
                        $VasIdWeight[]  = $val['id'];
                    }
                    
                    $arrVasCode[$val['id']] = $val['code'];
                }
            }

            if(!empty($VasIdPrice))
            {
                $DbQueryVas  = CourierFeeVasModel::where('fee_id','=',$RouterQuery['fee_id'])
                                                 ->whereIn('vas_id',$VasIdPrice)
                                                 ->where('value_start','<',$Input['Amount'])
                                                 ->where('value_end','>=',$Input['Amount'])
                                                 ->where('location','=',$locationID)
                                                 ->where('active','=',1)
                                                 ->get(array('id','vas_id','percent','money','money_add'))->toArray();

                //return $DbQueryVas;
            }

            if(!empty($VasIdWeight))
            {
                $DbQueryVas  = CourierFeeVasModel::where('fee_id','=',$RouterQuery['fee_id'])
                                                 ->whereIn('vas_id',$VasIdWeight)
                                                 ->where('value_start','<',$Input['Weight'])
                                                 ->where('value_end','>=',$Input['Weight'])
                                                 ->where('location','=',$locationID)
                                                 ->where('active','=',1)
                                                 ->get(array('vas_id','percent','money','money_add'))->toArray();
            }
            
            if($DbQueryVas){
                $DbVasPrice += $DbQueryVas;
            }

            if(!empty($DbVasPrice)){
                foreach($DbVasPrice as $value){
                    if($value['percent'] == 0){
                        $vasPrice = $value['money'];
                    }
                    else{
                        if(Input::has('Action') && Input::get('Action') == 'Change' && Input::has('Order.Collect')){
                            $vasPrice = $value['percent'] * Input::get('Order.Collect');
                        }
                        else{
                            $vasPrice = $value['percent'] * $Input['Amount'];
                        }
                        
                        $vasPrice = $vasPrice > $value['money'] ? ceil($vasPrice) : $value['money'];
                    }
                    
                    $OutputFee['vas'][$arrVasCode[$value['vas_id']]]    = $vasPrice + $value['money_add'];
                }
            }
            //
        }

        
        if($Vas){
            foreach($Vas as $key => $val){
                if($val != 1){
                    $OutputFee['vas'][strtolower($key)] = 0;
                }
            }
        }

        // Nội thành <=> Ngoại thành , cùng tỉng , phí nhỏ hơn 3tr  miễn phí CoD
        if(in_array($this->PickupLocation, [1,2]) && in_array($this->DeliveryLocation, [1,2]) && $Location['To']['City'] == $Location['From']['City'] && $Input['Amount'] < 3000000){
            $OutputFee['vas']['cod'] = 0;
        }

        // Return Collect Money
        if(Input::get('Config.Payment') == 1){
            // Miễn phí VC và có CoD
            if(in_array('cod',$ListVas))
            {
                $vasFee = isset($OutputFee['vas']['cod']) ? $OutputFee['vas']['cod'] : 0;
                $OutputFee['collect'] =  Input::has('Order.Collect') ? (int)Input::get('Order.Collect') : ( $Input['Amount'] + $vasFee - Input::get('Discount') );
            }
                        
            // Miễn phí VC và ko CoD
            else{
                $OutputFee['collect'] =  Input::has('Order.Collect') ? (int)Input::get('Order.Collect') : 0;
            }
            
            $OutputFee['base_collect']  = $OutputFee['collect'] + $total_fee;
        }
        else{
            // Ko Miễn phí vc & có CoD
            if(in_array('cod',$ListVas))
            {
                $vasFee = isset($OutputFee['vas']['cod']) ? $OutputFee['vas']['cod'] : 0;
                $OutputFee['collect'] =  Input::has('Order.Collect') ? (int)Input::get('Order.Collect') : ($total_fee + $Input['Amount'] + $vasFee - Input::get('Discount'));
            }
            
            //  Ko Miễn phí vc & ko CoD
            else{
                
                if($Location['To']['City'] == $Location['From']['City'] && in_array($locationID, [1,2]) && in_array($this->DeliveryLocation, [1,2])){
                    $OutputFee['vas']['cod'] = 0;
                }
                else{
                    $OutputFee['vas']['cod'] = 10000;
                }
                
                $OutputFee['collect'] =  Input::has('Order.Collect') ? (int)Input::get('Order.Collect') : $total_fee + $OutputFee['vas']['cod'] - Input::get('Discount');
                
            }
            
            $OutputFee['base_collect']  = $OutputFee['collect'];
        }

        //
        if($OutputFee['collect'] <= 0)
        {
            $OutputFee['collect'] = 0;
            $OutputFee['vas']['cod'] = 0; 

        }

        // Return Total Vas
        $OutputFee['total_vas'] = isset($OutputFee['vas']) ? array_sum($OutputFee['vas']) : 0;
        
        $this->_SellerPvc = $OutputFee['total_fee'];
        
        if(isset($this->lCourier['money_delivery'])){
            $this->_SellerPvc += (int)$this->lCourier['money_delivery'];
        }
        
        if(isset($this->lCourier['money_pickup'])){
            $this->_SellerPvc += (int)$this->lCourier['money_pickup'];
        }
        
        $this->_SellerCod = isset($OutputFee['vas']['cod']) ? (int)$OutputFee['vas']['cod'] : 0;
        
        // Chodientu có cấu hình phí vận chuyển riêng, Shipchung không cần tính toán lại
        if($this->userId > 0  && Input::get('Domain') != 'chodientu.vn')
        {
            // Check Discount User
            $dbCfgFee = sellermodel\FeeModel::where('user_id',$this->userId)->first();
            
            if ( $dbCfgFee){
                
                // Xử lý cho PVC
                if(Input::get('Config.Payment') != 1)
                {
                    if($dbCfgFee['shipping_fee'] == 1){
                        $this->_SellerPvc = (int)$dbCfgFee['shipping_cost_value'];                        
                        if(isset($OutputFee['collect']) && $OutputFee['collect'] > $this->_SellerPvc && $this->_SellerPvc > 0 && !Input::has('Order.Collect')){
                            $OutputFee['collect'] -= $this->_SellerPvc;
                        }

                    }
                    // Người bán trả phí
                    elseif($dbCfgFee['shipping_fee'] == 3){
                        if(isset($OutputFee['collect']) && $OutputFee['collect'] > $this->_SellerPvc && $this->_SellerPvc > 0 && !Input::has('Order.Collect')){
                            $OutputFee['collect'] -= $this->_SellerPvc;
                        }
                        
                        $this->_SellerPvc = 0;
                    }
                }
                
                // Xử lý cho CoD
                if($dbCfgFee['cod_fee'] == 2){
                    if(isset($OutputFee['collect']) && $OutputFee['collect'] > $this->_SellerCod && $this->_SellerCod > 0 && !Input::has('Order.Collect')){
                        $OutputFee['collect'] -= $this->_SellerCod;
                    }
                    
                    $this->_SellerCod = 0;
                }
                
            }
        }
         
        $OutputFee['seller'] = array('pvc' => (int)$this->_SellerPvc, 'pcod' => (int)$this->_SellerCod);    
        
        $this->calculate = $OutputFee;
        $this->getDiscount();
        return array('error' => 200,'message'   => 'success');
    }
    
    function _money_payment($money){
        return $money;
    }
    

    /**
    * @desc : Lấy thông tin order theo mã bản kê
    * @author : ThinhNV
    */

    public function getOrderByVerify()
    {
        $verify_id      = Input::has('verify_id')           ?       Input::get('verify_id')         :      0;
        $MerchantKey    = Input::has('MerchantKey')         ?       Input::get('MerchantKey')       :      "";
        $cmd            = Input::has('cmd')                 ?       Input::get('cmd')               :      "";

        $validation = \Validator::make(array('verify_id' => $verify_id, 'MerchantKey' => $MerchantKey), array(
            'verify_id'         => 'required|numeric|min:1',
            'MerchantKey'       => 'required',
        ));
        
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        if($this->userId == 0){
            return Response::json(array('error' => 'not_exist_merchantkey', 'error_message' => 'Không tồn tại Merchant Key'));
        }
        
        if($cmd == 'demo'){
            $demoData = 
            [
                "id" => 104155,
                "order" => [
                    [
                        "id" => 1,
                        "tracking_code" => "SC1393717756",
                        "verify_id" => 104155,
                        "status" => 20,
                        "order_detail" => [
                            "order_id" => 1,
                            "seller_pvc" => 0,
                            "seller_cod" => 0,
                            "sc_pvc" => 2300022,
                            "sc_cod" => 10000,
                            "sc_pbh" => 0,
                            "sc_pvk" => 5800,
                            "sc_pch" => 0,
                            "sc_discount_pvc" => 0,
                            "sc_discount_cod"=> 0,
                            "money_collect" => 90000
                        ],
                        "meta_status" => [
                            "id"    => 1,
                            "code"  => 20,
                            "name"  => "Chờ duyệt"
                        ]
                    ],
                    [
                        "id" => 1,
                        "tracking_code" => "SC1393715345",
                        "verify_id" => 104155,
                        "status" => 20,
                        "order_detail" => [
                            "order_id" => 1,
                            "seller_pvc" => 0,
                            "seller_cod" => 0,
                            "sc_pvc" => 2300022,
                            "sc_cod" => 10000,
                            "sc_pbh" => 0,
                            "sc_pvk" => 5800,
                            "sc_pch" => 0,
                            "sc_discount_pvc" => 0,
                            "sc_discount_cod"=> 0,
                            "money_collect" => 90000
                        ],
                        "meta_status" => [
                            "id"    => 1,
                            "code"  => 20,
                            "name"  => "Chờ duyệt"
                        ]
                    ]
                ]
            ];
            return Response::json(array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $demoData
            ));

        }

        $UserId     = $this->userId;        
        $Model = new \ordermodel\VerifyModel;
        $Data   = $Model->where('id',$verify_id)->where('user_id',$UserId)->with(array('Order' => function($query){
                                                            $query->where('time_create','>=',time() - $this->time_limit)->with(array('OrderDetail', 'MetaStatus'))->get(array('id','tracking_code','verify_id','status'));
                                                                }))->first(array('id'));
        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $Data
        );
        return Response::json($contents);
    }

    
    function getCourier(){
        $cCourier   = new CourierController;
        $CacheList  = $cCourier->GetCache();
        $return     = [];

        // Call list Courier
        if(!empty($CacheList)) {
            foreach ($CacheList as $item) {
                $this->listCacheCourier[$item['id']] = $item;
                $return[] = array('CourierId' => $item['id'], 'CourierName' => $item['name']);
            }
        }

        return Response::json(array('error' => false, 'error_message' => 'success','data' => $return));
    }
    
    function getCity(){
        $CtrCity = new CityController;
        $CacheList  = $CtrCity->GetCache();
        $return     = [];

        if(!empty($CacheList)) {
            foreach ($CacheList as $value) {
                $return[] = array('CityId' => $value['id'], 'CityName' => $value['city_name']);
            }
        }

        return Response::json(array('error' => false, 'error_message' => 'success','data' => $return));
    }
    
    function getProvince($city = 0){
        if($city == 0){
            return Response::json(array('error' => 'invalid', 'error_message' => 'Province Id Empty'));
        }
        
        $CtrCity    = new DistrictController;
        $CacheList  = $CtrCity->GetCache($city);
        $return     = [];

        if(!empty($CacheList)) {
            foreach ($CacheList as $value) {
                $return[] = array('ProvinceId' => $value['id'], 'ProvinceName' => $value['district_name'], 'Remote' => $value['remote']);
            }
        }

        return Response::json(array('error' => false, 'error_message' => 'success','data' => $return));
    }
    
    function getWard($dist = 0){
        if($dist == 0){
            return Response::json(array('error' => 'invalid', 'error_message' => 'District Id Empty'));
        }
        
        $Controller = new WardController;
        $CacheList  = $Controller->getCache($dist);
        $return     = [];

        if(!empty($CacheList)){
            foreach($CacheList as $value){
                $return[] = array('WardId' => $value['id'],'WardName' => $value['ward_name']);
            }
        }
        return Response::json(array('error' => false, 'error_message' => 'success','data' => $return));
    }
    
    /**
     *  Calculate Discount
     *  @param
     *      - UserId
     *      - Coupon
     * 
     * @return Response
     **/
    
    public function getDiscount(){
        $this->calculate['discount'] = array();
        if($this->uDomain == 'chodientu.vn'){
            $this->calculate['discount'] = array(
                'pvc'   => $this->calculate['total_fee']*0.05, 
                'cod'   => isset($this->calculate['vas']['cod']) ? $this->calculate['vas']['cod'] : 0
            );
        }
        
        // Cấu hình miễn phí
        if(Input::has('Config.Payment')){
            $this->calculate['discount']['seller'] = Input::get('Config.Payment') == 1 ? (int)$this->calculate['total_fee'] : 0;
        }
        
        return true;
        
        $messages = array(
            'UserId.exists'     => 'UserId not exists',
            'Coupon.exists'     => 'Coupon code not exists'
        );
        
        Validator::getPresenceVerifier()->setConnection('courierdb'); // set connection
        $validation = Validator::make(Input::all(), array(
            'UserId'        => 'required|numeric|exists:discount_setup,seller_id',
            'Coupon'        => 'required|alpha_num|exists:discount_config,code'
        ),$messages);
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => 'invalid', 'message' => $validation->messages()));
        }
        
        $DiscountConfigModel    = new DiscountConfigModel;
        $UserId                 = (int)Input::get('UserId');
        $Coupon                 = trim(Input::get('Coupon'));
        
        $DiscountConfig = $DiscountConfigModel::where('code','=',$Coupon)
                                              ->where('from_date','<',time())
                                              ->where('to_date','>=',time())
                                              ->whereHas('discount_setup' , function($q) use($UserId){
                                                    $q->where('seller_id','=',$UserId)
                                                      ->where('active','=',1)
                                                      ->where('from_date','<',time())
                                                      ->where('to_date','>=',time());
                                                })->first()->toArray();
        
        if($DiscountConfig){
            $DiscountOrderModel = new DiscountOrderModel;
            $Count  = $DiscountOrderModel::where('seller_id','=',$UserId)
                                          ->where('code','=',$Coupon)
                                          ->count();
            if($Count < $DiscountConfig['use_number']){
                $Data   = array('type' => $DiscountConfig['value_type'],'value' => $DiscountConfig['value']);
            }else{
                $Data   = array('type' => '','value' => 0);
            }
        }else{
            $Data   = array('type' => '','value' => 0);
        }
        
        return array('error' => false,'message'   => 'success','discount' => $Data);
    }
    
    
    function _create(){
        $DB = DB::connection('orderdb');
        
        // Gen code
        $this->_generateCode();
        
        // Begin Transaction
        $DB->beginTransaction();
        
        try {
            $DB->table('order_code')->insert(
                array('order_code' => $this->__TrackingCode)
            );
            
            $this->_insertBuyer();
            $this->_insertOrders();
            $this->_insertItems();
            $this->_insertOrderDetail();
            $DB->commit();

            if(isset($this->autoAccept) && $this->autoAccept == 1){
                $this->PredisAcceptLading($this->__TrackingCode);
            }

            //return $queries = $DB->getQueryLog();
            //var_dump($queries);die;
            $this->__Result = array('ERROR' => 'SUCCESS','MSG' => '');
        } 
        catch(ValidationException $e)
        {
            $DB->rollback();
            $this->__Result = array('ERROR' => 'FAIL',
                                    'MSG' => 'ValidationException', 
                                    //'DATA' => $e
                                );
        } 
        catch(\Exception $e)
        {
            $DB->rollback();
            $this->__Result = array(
                                    'ERROR' => 'FAIL',
                                    'MSG' => 'Exception', 
                                    'DATA' => $e->getMessage()
                                );
        }
        catch(PDOException $e)
        {
            $DB->rollback();
            
            $info   = $e->errorInfo;
            // Check if mysql error is for a duplicate key
            if (in_array($info[1], array(1062, 1022, 1558))) {
                return $this->_create();
            }
            
            $this->__Result = array(
                                    'ERROR' => 'FAIL',
                                    'MSG' => 'PDOException', 
                                    //'DATA' => $e->getMessage()
                                );
        }
    }
    
    function _generateCode(){
        $md5 = md5(uniqid($this->userId, true).microtime());
        
        $crc = crc32((string)$md5);
        if ($crc & 0x80000000) {
            $crc ^= 0xffffffff;
            $crc += 1;
        }

        $sc_code = abs($crc);
        
        if(strlen($sc_code) > 10 || strlen($sc_code) < 8)
            return $this->_generateCode();
        
        $prefix = 'SC5';
        
        return $this->__TrackingCode = $prefix.$sc_code;
    }
    
    function _insertBuyer(){
        $this->insertBuyerAddress = OrderAddressModel::create([
            'seller_id'     => (int)$this->userId,
            'city_id'       => (int)Input::get('To.City'),
            'province_id'   => (int)Input::get('To.Province'),
            'ward_id'       => (int)Input::get('To.Ward'),
            'address'       => Input::get('To.Address'),
            'time_update'   => time(),
        ]);
        
        $this->insertBuyer = OrderBuyerModel::create([
            'seller_id'     => (int)$this->userId,
            'fullname'      => Input::get('To.Name'),
            'phone'         => Input::get('To.Phone'),
            'email'         => Input::get('To.Email'),
            'address_id'    => $this->insertBuyerAddress->id,
        ]);
    }

    function _insertItems(){
        if(Input::has('Items')){
            foreach(Input::get('Items') as $value){
                //items model
                $insert = OrderItemsModel::create([
                    'seller_id'     => (int)$this->userId,
                    'name'          => $value['Name'],
                    'price'         => $value['Price'],
                    'weight'        => $value['Weight'],
                    'time_update'   => time(),
                ]);
                //order items model
                OrderItemModel::create([
                    'order_id'      => $this->insertOrders->id,
                    'item_id'       => $insert->id,
                    'quantity'      => (int)$value['Quantity'],
                    'description'   => Input::get('Order.Description')
                ]);
            }
            
            return $insert->id;
        }else{
            //items model
                $insert = OrderItemsModel::create([
                    'seller_id'     => (int)$this->userId,
                    'name'          => Input::get('Order.ProductName'),
                    'price'         => Input::get('Order.Amount'),
                    'weight'        => Input::get('Order.Weight'),
                    'time_update'   => time(),
                ]);
                //order items model
                OrderItemModel::create([
                    'order_id'      => $this->insertOrders->id,
                    'item_id'       => $insert->id,
                    'quantity'      => (int)Input::get('Order.Quantity'),
                    'description'   => Input::get('Order.Description')
                ]);
        }
        
    }
    
    function _insertOrders(){
        $status = 20;
        // Cấu hình tự động duyệt theo khách hàng
        if($this->autoAccept == 1){
            $status = 21;
            $time_accept = time();
        }

        $this->__DataLog['order'] = [
            'service_id'            => (int)Input::get('Config.Service'),
            'courier_id'            => Input::get('Courier'),
            'tracking_code'         => $this->__TrackingCode,
            'order_code'            => Input::get('Order.Code'),
            'domain'                => Input::has('Domain') ? Input::get('Domain') : 'shipchung.vn',
            'courier_tracking_code' => '',
            'child_id'              => $this->childId,
            'from_user_id'          => (int)$this->userId,
            'from_address_id'       => (int)Input::get('From.Stock'),
            'from_city_id'          => (int)Input::get('From.City'),
            'from_district_id'      => (int)Input::get('From.Province'),
            'from_ward_id'          => (int)Input::get('From.Ward'),
            'from_address'          => Input::get('From.Address'),
            
            'to_buyer_id'           => $this->insertBuyer->id,
            'to_name'               => Input::get('To.Name'),
            'to_phone'              => Input::get('To.Phone'),
            'to_email'              => Input::get('To.Email'),
            
            'to_address_id'         => $this->insertBuyerAddress->id,
            'product_name'          => Input::get('Order.ProductName'),
            'total_weight'          => Input::get('Order.Weight'),
            'total_quantity'        => Input::get('Order.Quantity'),
            'total_amount'          => Input::get('Order.Amount'),
            'status'                => $status,
            
            'checking'              => (int)Input::get('Config.Checking'),
            'fragile'               => (int)Input::get('Config.Fragile'),
            
            'domain'                => $this->uDomain,
            
            'time_create'           => time(),
            'time_update'           => time(),
            'time_accept'           => isset($time_accept) ? $time_accept : 0,
            'estimate_delivery'     => (isset($this->lCourier['leatime_delivery']) ? (int)$this->lCourier['leatime_delivery']  : 0) + (isset($this->lCourier['leatime_ward'])     ? (int)$this->lCourier['leatime_ward']      : 0)
        ];
        $this->insertOrders = OrderOrdersModel::create($this->__DataLog['order']);
    }
    
    function _insertOrderDetail(){
        if(Input::has('Discount'))
        {
            $seller_discount = (int)Input::get('Discount');
        }
        elseif(Input::has('Config.Payment') && Input::get('Config.Payment') == 1){
            $seller_discount = (int)$this->calculate['total_fee'];
        }
        else{
            $seller_discount = 0;
        }
        
        //
        $this->__DataLog['detai'] = [
            'order_id'      => $this->insertOrders->id,
            'sc_pvc'        => (int)$this->calculate['total_fee'] + (int)$this->lCourier['money_delivery'] + (int)$this->lCourier['money_pickup'],
            'sc_cod'        => isset($this->calculate['vas']['cod']) ? (int)$this->calculate['vas']['cod'] : 0,
            'sc_pbh'        => isset($this->calculate['vas']['protected']) ? (int)$this->calculate['vas']['protected'] : 0,
            'sc_pvk'        => 0,
            'sc_pch'        => 0,
            'seller_discount'=> $seller_discount,
            'seller_pvc'    => $this->_SellerPvc,
            'seller_cod'    => $this->_SellerCod,
            'hvc_pvc'       => 0,
            'hvc_cod'       => 0,
            'hvc_pbh'       => 0,
            'hvc_pvk'       => 0,
            'hvc_pch'       => 0,
            'money_collect' => $this->calculate['collect'],             
            'sc_discount_pvc'   => isset($this->calculate['discount']['pvc']) ? (int)$this->calculate['discount']['pvc'] : 0,
            'sc_discount_cod'   => isset($this->calculate['discount']['cod']) ? (int)$this->calculate['discount']['cod'] : 0,
            
        ];
        OrderDetailModel::create($this->__DataLog['detai']);
    }
    
    function getStatus(){
        return $data = order\StatusOrderCtrl::getStatusgroupshow();
    }
    function getStatusorder(){
        return $data = (new order\StatusOrderCtrl())->getStatusorder();
    }
    
    function postCheckoutnganluong( $token = '' ){
        //return Response::json(array('error' => 'success', 'message' => 'Tạo giao dịch Ngân Lượng thành công', 'LinkCheckout' => 'http://services.shipchung.vn/popup/nganluong?Token=2dc5dab81c9814dfae07b86687774f75&OrderCode=SC1423719151'));
        // Get Log Merchant
        $LMongo         = new LMongo;
        $dbMerchant = LMongo::collection('log_checkout_merchant')->find($token);
        if(!$dbMerchant OR !$dbMerchant['ReceiverEmail']){
            return Response::json(array('error' => 'empty', 'message' => 'Không tìm thấy dữ liệu'));
        }
        
        if(!$dbMerchant['ReceiverEmail']){
            return Response::json(array('error' => 'ReceiverEmail', 'message' => 'Không có dữ liệu về ReceiverEmail'));
        }
        $ApiCourier  = new ApiCourierCtrl();
        $result = $ApiCourier->postCreate(false);
        
        if($result['error'] != 'success'){
            return Response::json($result);
        }
        
        $transactionToken = md5($this->__TrackingCode.$dbMerchant['ReceiverEmail'].Config::get('app.key'));

        $params = array(
            'merchant_id'       => Config::get('constants.MERCHANT_ID_SC'),
            'merchant_password' => Config::get('constants.MERCHANT_PASS_SC'),
            'version'           => '3.1',
            'function'          => 'SetExpressCheckout',
            'receiver_email'    => $dbMerchant['ReceiverEmail'],
            'order_code'        => $this->__TrackingCode,
            'total_amount'      => $dbMerchant['Order']['Amount'],
            'payment_method'    => 'NL',
            'order_description' => $dbMerchant['Order']['ProductName'],
            'fee_shipping'      => $this->__DataLog['detai']['seller_pvc'] + $this->__DataLog['detai']['seller_cod'] - $this->__DataLog['detai']['seller_discount'],
            'return_url'        => URL::to('popup/nganluong').'?Token='.$transactionToken.'&OrderCode='.$this->__TrackingCode,
            
            'buyer_address'     => Input::get('To.Address'),
            'buyer_fullname'    => Input::get('To.Name'),
            'buyer_mobile'      => Input::get('To.Phone'),
            'buyer_email'       => Input::get('To.Email'),
            
            'total_item'        => $dbMerchant['Order']['Quantity'],
        );
        
        foreach($dbMerchant['Item'] as $i => $item){
            $stt = $i + 1;
            $params['item_name'.$stt]         = trim($item['Name']);
            $params['item_amount'.$stt]       = (int)$item['Price'];
            $params['item_quantity'.$stt]     = (int)$item['Quantity'];
            $params['item_weight'.$stt]       = (int)$item['Weight'];
        }
        
        //var_dump($params);die;
        //return Response::json($params);
        $xml_result =  preg_replace('#&(?=[a-z_0-9]+=)#', '&amp;',(string)\cURL::post(Config::get('config_api.API_POST_NL'),$params));
        $nl_result  = simplexml_load_string($xml_result);
        //return Response::json(array('NganLuong' => $nl_result, 'ShipChung' => $params));
        
        $nl_errorcode       = (string)$nl_result->error_code;
        $nl_checkout_url    = (string)$nl_result->checkout_url;
        $nl_token           = (string)$nl_result->token;
        $nl_time_limit      = (string)$nl_result->time_limit;
        $nl_description     = (string)$nl_result->description;
        
        if($nl_errorcode != '00'){
            return Response::json(array('error' => 'ERROR NGAN LUONG', 'message' => 'Lỗi tạo giao dịch Ngân Lượng - '.$nl_errorcode));
        }
        
        sellermodel\TransactionNLmodel::insert(array(
            'token'             => $transactionToken,
            'tracking_code'     => $this->__TrackingCode,
            'transaction_code'  => $nl_token,
            'params'            => json_encode($params),
            'respond'           => json_encode($nl_result),
            'status'            => 'PENDING',
            'time_due'          => $nl_time_limit - 300,
            'time_create'       => time(),
            'time_update'       => time()
        ));
        
        return Response::json(array('error' => 'success', 'message' => 'Tạo giao dịch Ngân Lượng thành công', 'LinkCheckout' => $nl_checkout_url));
    }
    
    function getDetail(){
        $validation = Validator::make(Input::all(), 
                    array(
                        'TrackingCode' => array('required','regex:/SC[0-9]+$/'),
                        'MerchantKey'   => array('required'),
                    ));

        // if($validation->fails()) {
            return Response::json(array('error' => true, 'error_message' => $validation->messages(), 'message'=> 'Currently not support this api !'));
        // ?}

        if($this->userId == 0){
            return Response::json(array('error' => true, 'error_message' => 'Không tồn tại Merchant Key', 'messsage'=> 'MERCHANT_KEY_NOT_FOUND'));
        }
        
        
        $OrderCtr = new order\OrderController;
        $Model    = new ordermodel\OrdersModel;

        $Detail = $OrderCtr->getPrintmulti(Input::get('TrackingCode'), $this->userId, false);
        
        if($Detail['error']){
            return Response::json($Detail);
        }
        $Detail['banking_info'] = $Model->where('tracking_code', Input::get('TrackingCode'))->with(['BankingInfo'])->select(array('tracking_code', 'from_user_id'))->first();
        

         
        /**
        * Thêm thông tin tỉnh thành ,  quận huyện , phường xã .
        * @last-update : 8/4/2014 
        * @by : ThinhNV
        */

        foreach($Detail['data'] as $key => $lading){
            $Detail['data'][$key]['from_city_name']     = (isset($Detail['city'][$lading['from_city_id']]))         ? $Detail['city'][$lading['from_city_id']]              : "";
            $Detail['data'][$key]['from_district_name'] = (isset($Detail['district'][$lading['from_district_id']])) ? $Detail['district'][$lading['from_district_id']]      : "";  
            $Detail['data'][$key]['from_ward_name']     = (isset($Detail['ward'][$lading['from_ward_id']]))         ? $Detail['ward'][$lading['from_ward_id']]              : "";

            $Detail['data'][$key]['to_order_address']['city_name']      = (isset($Detail['city'][$lading['to_order_address']['city_id']]))              ? $Detail['city'][$lading['to_order_address']['city_id']]              : "";
            $Detail['data'][$key]['to_order_address']['province_id']    = (isset($Detail['district'][$lading['to_order_address']['province_id']]))      ? $Detail['district'][$lading['to_order_address']['province_id']]      : "";  
            $Detail['data'][$key]['to_order_address']['ward_name']      = (isset($Detail['ward'][$lading['to_order_address']['ward_id']]))              ? $Detail['ward'][$lading['to_order_address']['ward_id']]              : "";                        
            
            $Detail['data'][$key]['service_name']           = ($lading['service_id'] === 1) ? 'Chuyển phát tiết kiệm' : 'Chuyển phát nhanh';
            /*
            $estimate =  $lading['estimate_delivery'] > 24 ? ($lading['estimate_delivery'] / 24).' ngày' : $lading['estimate_delivery'] .' tiếng';
            $Detail['data'][$key]['estimate_delivery_str']  = $estimate;
            */
        }

        return $Detail;
        
    }
    
    
    function getCourierfee()
    {
        $Model      = new ordermodel\OrdersModel;
        
        if(Input::has('TrackingCode')){
            $dbLading = $Model->where('tracking_code', Input::get('TrackingCode'))
                                //->whereIn('status',[52,53,66])
                                ->first();
        }
        else{
            $dbLading = $Model->whereIn('status',[52,53,66])
                                ->where('courier_id',1)
                                ->where('time_accept','>=', 1427821200)
                                ->where('is_finalized',0)
                                ->orderBy('time_success','asc')
                                ->first();
        }

        //return Response::json($dbLading);
        if(!$dbLading){
            return Response::json(['error' => 'empty_data','error_message' => 'Đã xử lý hết']);
        }

        $dbDetail = ordermodel\DetailModel::where('order_id',$dbLading['id'])->first();

        $dbToAddress = ordermodel\AddressModel::find($dbLading['to_address_id'],['city_id','province_id','ward_id']);

        if($dbLading['total_weight'] == 0){
            $dbLog      = \LMongo::collection('log_create_lading')->where('trackingcode',(string)$dbLading['tracking_code'])->first(['input']);
            $boxSize = $dbLog['input']['Order']['BoxSize'];
            $str = explode('x',$boxSize);
            $dbLading['total_weight'] = ceil(($str[0] * $str[1] * $str[2]) / ( $dbLading['service_id'] == 1 ? 3 : 6 ));
        }

        $paramPost = array(
                        'UserId'    => $dbLading['from_user_id'],
                        'From'      => ['Province' => $dbLading['from_district_id'], 'City' => $dbLading['from_city_id'], 'Ward' => $dbLading['from_ward_id']],
                        'To'        => ['Province' => $dbToAddress['province_id'], 'City' => $dbToAddress['city_id'], 'Ward' => $dbToAddress['ward_id']],
                        'Order'     => ['Amount' => $dbLading['total_amount'], 'Quantity' => $dbLading['total_quantity'], 'Weight' => $dbLading['total_weight']],
                        'Config'    => [
                                            'Service'       => $dbLading['service_id'],
                                            'CoD'           => $dbDetail['money_collect'] > 0 ? 1 : 2,
                                            'Protected'     => $dbDetail['sc_pbh'] > 0 ? 1 : 2,
                                        ],


                    );
        Input::merge($paramPost);
        $this->_calculateCourier($dbLading['courier_id']);

        if($this->calculate['total_fee'] < 1){
            $dbLading->is_finalized = 2;
            $dbLading->save();
            return Response::json(['ERROR' => 'ZERO_FEE',$dbLading,$this->calculate]);
        }
        
        $update = ['hvc_pvc' => $this->calculate['total_fee']];
        
        if(isset($this->calculate['vas']['cod'])){
            $update['hvc_cod'] = (int)$this->calculate['vas']['cod'];
        }
        
        if(isset($this->calculate['vas']['protected'])){
            $update['hvc_pbh'] = (int)$this->calculate['vas']['protected'];
        }
        
        $dbLading->is_finalized = 1;
        $dbLading->save();
        ordermodel\DetailModel::where('order_id',$dbLading['id'])->update($update);
        
        return Response::json([$dbLading,$paramPost,$this->calculate]);
    }
    
    
    public function _calculateCourier( $Courier = 0 ){        
        $Service            = Input::get('Config.Service');
        $Vas                = Input::get('Config');
        $Location           = array('To' => Input::get('To'),'From' => Input::get('From'));
        $Input              = Input::get('Order');
        
        $this->uDomain      = strtolower(Input::get('Domain'));
                
        // Define --
        $ListVas            = 
        $DbQueryVas         =
        $DbVasPrice = array();
        
        if($Vas){
            if(isset($Vas['Service'])){
                unset($Vas['Fragile']);
                unset($Vas['Checking']);
                unset($Vas['Service']);
            }
            
            if(Input::has('Config.CoD') && (int)Input::get('Config.CoD') == 0)
            {
                unset($Vas['CoD']);
            }
            
            foreach($Vas as $key => $val){
                if($val == 1){
                    $ListVas[]  = strtolower($key);
                }
            }
        }

        // Get Courier Default
        if($Courier == 0)
        {
            $CourierDb          = CourierModel::where('default','=',1)->first(array('id'))->toArray();
            $Courier            = $CourierDb['id'];
        }
        
        $ToAreaQuery        = AreaLocationModel::where('province_id','=',$Location['To']['Province'])
                                ->where('city_id','=',$Location['To']['City'])
                                ->where('active','=',1)
                                ->get(array('area_id','location_id'))->toArray();
        
        if(!$ToAreaQuery){
            return array(
                'error'         => 'unsupported', 
                'error_message' => 'system not support area'
            );
        }
        //return $ToAreaQuery;
        
        foreach($ToAreaQuery as $value){
            $arrArea[]  = $value['area_id'];
            $locationID = $value['location_id'];
        }

        $RouterQuery        = CourierRouter::whereIn('to_area_id',$arrArea)
                                ->where('courier_id','=',$Courier)
                                ->where('service_id','=',Input::get('Config.Service'))
                                ->where('from_province_id','=',$Location['From']['Province'])
                                ->where('active','=',1)->first(array('id','courier_id','fee_id'));
        //return $RouterQuery;
        if(!$RouterQuery){
            return array(
                'error'         => 'unsupported', 
                'error_message' => 'system not support router'
            );
        }
        
        // location Pickup
        $dbLocationPick = CourierLocationPickupModel::where('district_id',$Location['From']['Province'])->first(['location']);
        if($dbLocationPick){
            $this->PickupLocation = $dbLocationPick['location'];
        }
        
        // Select Fee
        $CourierFee         = CourierFeeModel::where('id','=',$RouterQuery['fee_id'])
                                 ->where('active','=',1)
                                 ->first(array('vat'));

        $CourierFeeDetail   = CourierFeeDetailModel::where('fee_id','=',$RouterQuery['fee_id'])
                                ->where('weight_start','<',$Input['Weight'])
                                ->where('weight_end','>=',$Input['Weight'])
                                ->first(array('money','surcharge'));
        //return $CourierFeeDetail;
        $total_fee = $CourierFeeDetail['money'];
        
        if($locationID > 1 && $Location['To']['City'] == $Location['From']['City']){
            $total_fee += $CourierFeeDetail['surcharge'];
        }
        elseif($locationID > 2){
            $total_fee += $CourierFeeDetail['surcharge'];
        }
        
        if($Location['To']['City'] == $Location['From']['City'] && $locationID >=3 && in_array($Location['From']['City'],array(18,52))){
            $total_fee = $total_fee * 1.2;
        }
        
        $total_fee += $total_fee * $CourierFee['vat'];
        
        $OutputFee = array(
            //'pickup'    => 10000,
            //'delivery'  => 20000,
            'total_fee' => $total_fee,
//            'collect'   => 530000,
//            'vas'       => array(
//                        'protect' => 5000,
//                        'cod'       => 10000
//                        )
        );
        /////////////////////// == ///////////////////////

        // vas fee
        if(!empty($ListVas)){
            $CourierVas     = CourierVasModel::whereIn('code',$ListVas)
                                             ->where('active','=',1)
                                             ->get(array('id','code','vas_value_type'))->toArray();
            //return $CourierVas;
            if(!empty($CourierVas)){
                foreach($CourierVas as $val){
                    if($val['vas_value_type'] == 1){
                        $VasIdPrice[]   = $val['id'];
                    }
                    
                    if($val['vas_value_type'] == 2){
                        $VasIdWeight[]  = $val['id'];
                    }
                    
                    $arrVasCode[$val['id']] = $val['code'];
                }
            }

            if(!empty($VasIdPrice))
            {
                $DbQueryVas  = CourierFeeVasModel::where('fee_id','=',$RouterQuery['fee_id'])
                                                 ->whereIn('vas_id',$VasIdPrice)
                                                 ->where('value_start','<',$Input['Amount'])
                                                 ->where('value_end','>=',$Input['Amount'])
                                                 ->where('location','=',$locationID)
                                                 ->where('active','=',1)
                                                 ->get(array('id','vas_id','percent','money','money_add'))->toArray();
                
                //return $DbQueryVas;
            }

            if(!empty($VasIdWeight))
            {
                $DbQueryVas  = CourierFeeVasModel::where('fee_id','=',$RouterQuery['fee_id'])
                                                 ->whereIn('vas_id',$VasIdWeight)
                                                 ->where('value_start','<',$Input['Weight'])
                                                 ->where('value_end','>=',$Input['Weight'])
                                                 ->where('location','=',$locationID)
                                                 ->where('active','=',1)
                                                 ->get(array('vas_id','percent','money','money_add'))->toArray();
            }
            
            if($DbQueryVas){
                $DbVasPrice += $DbQueryVas;
            }
            
            if(!empty($DbVasPrice)){
                foreach($DbVasPrice as $value){
                    if($value['percent'] == 0){
                        $vasPrice = $value['money'];
                    }
                    else{
                        if(Input::has('Action') && Input::get('Action') == 'Change' && Input::has('Order.Collect')){
                            $vasPrice = $value['percent'] * Input::get('Order.Collect');
                        }
                        else{
                            $vasPrice = $value['percent'] * $Input['Amount'];
                        }
                        
                        $vasPrice = $vasPrice > $value['money'] ? ceil($vasPrice) : $value['money'];
                    }
                    
                    $OutputFee['vas'][$arrVasCode[$value['vas_id']]]    = $vasPrice + $value['money_add'];
                }
            }
            //
        }
        
        
        if($Vas){
            foreach($Vas as $key => $val){
                if($val != 1){
                    $OutputFee['vas'][strtolower($key)] = 0;
                }
            }
        }
        
        
        // Return Total Vas
        $OutputFee['total_vas'] = isset($OutputFee['vas']) ? array_sum($OutputFee['vas']) : 0; 
        
     
        return $this->calculate = $OutputFee;
    }
    
    function getFixems(){
        $dbLading = ordermodel\OrdersModel::where('courier_id',8)
                                                ->where('courier_tracking_code','!=','')
                                                ->where('courier_tracking_code','like','SC%')
                                                ->where('time_accept','>=',time() - 7776000)
                                                ->first(['tracking_code','courier_tracking_code']);                                
        //return Response::json($dbLading);
        
        if(empty($dbLading)){
            return Response::json(['ERROR' => 'Đã hết rồi']);
        }
        
        $dbLog = LMongo::collection('log_journey_lading')->where('tracking_code',$dbLading['tracking_code'])->orderBy('time_create','desc')->first(['input','_id']);
        
        if(empty($dbLog)){
            ordermodel\OrdersModel::where('tracking_code',$dbLading['tracking_code'])->where('time_accept','>=',time() - 7776000)->update([ 'courier_tracking_code' => null ]);
            return Response::json($dbLading);
        }

        if(isset($dbLog['input']['TrackingOrder']) && $dbLog['input']['TrackingOrder'] != $dbLading['tracking_code']){
            ordermodel\OrdersModel::where('tracking_code',$dbLading['tracking_code'])->where('time_accept','>=',time() - 7776000)->update([ 'courier_tracking_code' => trim($dbLog['input']['TrackingOrder']) ]);
        }
        else{
            ordermodel\OrdersModel::where('tracking_code',$dbLading['tracking_code'])->where('time_accept','>=',time() - 7776000)->update(['courier_tracking_code' => null] );
        }
        
        return Response::json($dbLog);
    }

    /**
     * @desc : API tra cứu lịch sử giao dịch
     * @author thinhnv <thinhnv@peacesoft.net>
     */

    public function TransactionAPI()
    {
        $page           = Input::has('page')            ? (int)Input::get('page')                : 1;
        $itemPage       = Input::has('limit')           ? Input::get('limit')                    : 20;
        $TimeStart      = Input::has('time_start')      ? trim(Input::get('time_start'))         : '';
        $TimeEnd        = Input::has('time_end')        ? trim(Input::get('time_end'))           : '';
        $Search         = Input::has('search')          ? trim(Input::get('search'))             : '';
        $cmd            = Input::has('cmd')             ? trim(strtolower(Input::get('cmd')))    : '';
        $ApiKey         = Input::has('MerchantKey')     ? Input::get('MerchantKey')              : "";

        $Model = new accountingmodel\TransactionModel;

        if(empty($ApiKey)){
            return Response::json(array(
                "error"         => true,
                "message"       => "Access denied, api key not empty", //. Please contact administrator !
                "data"          => array(),
                "statusCode"    => 403
            ), 403);
        }

        $merchantId   = $this->_getMerchantId($ApiKey);

        if(!$merchantId){
            return Response::json(array(
                "error"         => true,
                "message"       => "Access denied, Api key not found ", //. Please contact administrator !
                "data"          => array(),
                "statusCode"    => 403
            ));
        }

        $Model = $Model->where(function($query) use($merchantId){
            $query->where('from_user_id',(int)$merchantId)
                ->orWhere('to_user_id', (int)$merchantId);
        });



        if(!empty($TimeStart)){
            $Model = $Model->where('time_create','>=',$TimeStart);
        }else{
            $Model = $Model->where('time_create','>=',time() - $this->time_limit);
        }

        if(!empty($TimeEnd)){
            $Model = $Model->where('time_create','<',$TimeEnd);
        }

        if(!empty($Search)){
            $Model = $Model->where('refer_code','LIKE','%'.$Search.'%');
        }

        $ModelTotal = clone $Model;

        $Total = $ModelTotal->count();
        $Data  = [];
        $User  = [];

        $Model = $Model->orderBy('time_create','DESC');

        if($cmd == 'export'){
            $Data = $Model->get()->toArray();
            return $this->getExcel($Data);
        }
        if($cmd == 'demo'){
            $Data = array(
                array(
                    "id"                => 13,
                    "refer_code"        => "1065",
                    /*"from_user_id"      => 555,
                    "to_user_id"        => 1,*/
                    "type"              => 1,
                    "money"             => 342660,
                    "balance_before"    => 764060,
                    "note"              => "Rút tiền theo bảng kê số 1065",
                    "view"              => 0,
                    "time_create"       => 1427855182
                ),
                array(
                    "id"                => 14,
                    "refer_code"        => "1066",
                    /*"from_user_id"      => 1,
                    "to_user_id"        => 555,*/
                    "type"              => 2,
                    "money"             => 819860,
                    "balance_before"    => -55800,
                    "note"              => "Nhận thanh toán thu hộ cho bảng kê số 1066",
                    "view"              => 0,
                    "time_create"       => 1426909731
                ),
                array(
                    "id"                => 21,
                    "refer_code"        => "1067",
                    /*"from_user_id"      => 555,
                    "to_user_id"        => 1,*/
                    "type"              => 1,
                    "money"             => 155800,
                    "balance_before"    => 10000,
                    "note"              => "Thanh toán phí vận chuyển cho bảng kê số 1067",
                    "view"              => 0,
                    "time_create"       => 1427251995
                ),
                array(
                    "id"                => 20,
                    "refer_code"        => "1068",
                    /*"from_user_id"      => 555,
                    "to_user_id"        => 1,*/
                    "type"              => 1,
                    "money"             => 1604400,
                    "balance_before"    => 1604400,
                    "note"              => "Rút tiền theo bảng kê số 1068",
                    "view"              => 0,
                    "time_create"       => 1427469496
                )
            );
            return Response::json([
                'error'         => false,
                'error_message' => 'success',
                'item_page'     => $itemPage,
                'total'         => sizeof($Data),
                'total_page'    => 1,
                'data'          => $Data,
            ]);
        }

        if($Total > 0){
            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }

            $Data = $Model->get()->toArray();

            /*if(!empty($Data)){
                foreach($Data as $val){
                    $ListUserId[]   = (int)$val['from_user_id'];
                    $ListUserId[]   = (int)$val['to_user_id'];
                }

                if(!empty($ListUserId)){
                    $ListUserId = array_unique($ListUserId);
                    $UserModel  = new User;
                    $ListUser   = $UserModel::whereIn('id',$ListUserId)->get(['id','fullname','email','phone'])->toArray();

                    if(!empty($ListUser)){
                        foreach($ListUser as $val){
                            $User[(int)$val['id']]  = $val;
                        }
                    }
                }
            }*/
        }

        $contents = array(
            'error'         => false,
            'error_message'       => 'success',
            'item_page'     => $itemPage,
            'total'         => $Total,
            'total_page'    => ceil($Total/$itemPage),
            'data'          => $Data,
        );

        return Response::json($contents);
    }

    // Lấy thông tin merchant từ Api key
    private function _getMerchantId($apiKey){
        $dbKey = \ApiKeyModel::where('key',$apiKey)->first(['user_id']);
        return empty($dbKey) ? 0 : $dbKey->user_id;
    }
    
}
