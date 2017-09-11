<?php namespace bm_accounting;

use bm_accmodel\OrderVerifyModel;
use bm_accmodel\VerifyItemDetailModel;
use bm_accmodel\VerifyOrderDetailModel;
use metadatamodel\OrganizationUserModel;

class VerifyCtrl extends BaseCtrl {
    private $data_sum       = [];
    private $user           = [];

    private $message_error  = '';
    private $Status;
    private $Active;
    private $master_id      = 1;
    private $config         = [];

    private $field_verify   = ['id','date','organization', 'type','config_warehouse','config_handling','balance','warehouse_fee','package_fee','handling_fee',
                                'discount_warehouse','discount_package','discount_handling','total_uid','time_stock', 'total_sku', 'floor','note','status','time_accept','time_create'];

    private $field_order    = ['id','verify_id','user','warehouse','order_number','sc_tracking_code','size','total_uid','time_stock','warehouse_fee','package_fee'
        ,'handling_fee', 'discount_warehouse','discount_package','discount_handling'];

    private $field_item     = ['id','verify_id','order_number','uid','time_stocked','time_packed','warehouse_fee','handling_fee','discount_warehouse','discount_handling'];

    public $message        = 'Thành Công';
    public $code           = 'SUCCESS';

    public  function __construct(){
        $this->config       = \Config::get('config_api.domain.boxme.accounting');
    }

    private function getModel(){
        $TimeCreateStart    = Input::has('create_start')    ? trim(Input::get('create_start'))                  : '';
        $TimeCreateEnd      = Input::has('create_end')      ? trim(Input::get('create_end'))                    : '';
        $Status             = Input::has('status')          ? trim(Input::get('status'))                        : 'ALL';
        $Keyword            = Input::has('keyword')         ? trim(Input::get('keyword'))                       : '';

        $Model      = new OrderVerifyModel;

        if(!empty($TimeCreateStart)){
            $TimeCreateStart    = date('Y-m-d', $TimeCreateStart);
            $Model              = $Model->where('date','>=',$TimeCreateStart);
        }

        if(!empty($TimeCreateEnd)){
            $TimeCreateEnd      = date('Y-m-d', $TimeCreateEnd);
            $Model              = $Model->where('date','<=',$TimeCreateEnd);
        }

        if($Status != 'ALL'){
            $Model = $Model->where('status',$Status);
        }

        // search
        if(!empty($Keyword)){
            if (filter_var($Keyword, FILTER_VALIDATE_EMAIL)){  // search email
                $User           = \User::where('email',$Keyword)->lists('organization');
            }elseif(filter_var((int)$Keyword, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $Model          = $Model->where('id', $Keyword);
            }else{
                $User           = OrganizationUserModel::where('fullname','LIKE','%'.$Keyword.'%')->lists('id');
            }

            if(isset($User)){
                if(!empty($User)){
                    $User    = array_unique($User);
                    $Model   = $Model->whereIn('organization',$User);
                }else{
                    return false;
                }
            }

        }

        return $Model;
    }


    /**
     * get list  system
     */

    public function getIndex()
    {
        $itemPage           = 20;
        $page               = Input::has('page')            ? (int)Input::get('page')                   : 1;
        $cmd                = Input::has('cmd')             ? strtolower(trim(Input::get('cmd')))       : '';

        $TrackingCode       = Input::has('tracking_code')   ? trim(Input::get('tracking_code'))         : '';
        $Token              = Input::has('Token')           ? trim(Input::get('Token'))                 : '';
        $MerchantKey        = Input::has('MerchantKey')     ? trim(Input::get('MerchantKey'))           : '';

        $Type               = Input::has('type')            ? (int)Input::get('type')                   : 0;
        $Config             = Input::has('config')          ? (int)Input::get('config')                 : null;


        $UserInfo   = $this->UserInfo();

        if(empty($UserInfo) && $Token != $this->config){ // gọi từ seller
            $this->code             = 'ERROR';
            $this->message          = 'Token không chính xác';
            return $this->ResponseData(true);
        }

        if(empty($UserInfo) && empty($MerchantKey)){ // gọi từ seller
            $this->code             = 'ERROR';
            $this->message          = 'Thiếu merchant_key';
            return $this->ResponseData(true);
        }

        $Model      = $this->getModel();
        if(!$Model){
            return $this->ResponseData(false);
        }

        if(!empty($MerchantKey)){
            $Organization   = $this->__check_merchant_key($MerchantKey);

            if(!$Organization){
                $this->code             = 'ERROR';
                $this->message          = 'MerchantKey không chính xác';
                return $this->ResponseData(true);
            }

            $Model  = $Model->where('organization', $Organization);
        }

        if(!empty($Type)){
            $Model  = $Model->where('type', $Type);
        }

        if(isset($Config)){
            $Model  = $Model->where('config_warehouse', $Config);
        }

        if(!empty($TrackingCode)){
            if(preg_match("/^SC/i", $TrackingCode)){
                $ListVerify = VerifyOrderDetailModel::where('sc_tracking_code', $TrackingCode)->lists('verify_id');

            }elseif(preg_match("/^O/i", $TrackingCode)){
                $ListVerify = VerifyOrderDetailModel::where('order_number', $TrackingCode)->lists('verify_id');
            }else{
                $ListVerify = VerifyItemDetailModel::where('uid', $TrackingCode)->lists('verify_id');
            }

            if(empty($ListVerify)){
                return $this->ResponseData(false);
            }

            $Model  = $Model->whereIn('id',$ListVerify);
        }

        $Model = $Model->orderBy('date','DESC')->orderBy('time_create','DESC');

        if($cmd == 'export'){
            $Data   = [];
            $Model->with('getOrganization')->select()->chunk('1000', function($query) use(&$Data){
                foreach($query as $val){
                    $Data[]             = $val->toArray();
                }
            });
            $this->data = $Data;
            return $this->ResponseData(false);
        }

        $ModelTotal = clone $Model;
        $this->data_sum    = $ModelTotal->first([DB::raw(
                                                'count(*) as total,
                                                sum(warehouse_fee) as warehouse_fee,
                                                sum(package_fee) as package_fee,
                                                 sum(handling_fee) as handling_fee,
                                                 sum(total_uid) as total_uid,
                                                 sum(total_uid_storage) as total_uid_storage,
                                                 sum(total_sku) as total_sku,
                                                 sum(floor) as floor,
                                                 sum(time_stock) as time_stock,
                                                 sum(discount_warehouse) as discount_warehouse,
                                                 sum(discount_package)  as discount_package,
                                                 sum(discount_handling) as discount_handling
                                                 ')]);

        if(isset($this->data_sum->total) && $this->data_sum->total > 0){
            $this->total    = $this->data_sum->total;

            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model      = $Model->skip($offset)->take($itemPage);
            }
           $this->data = $Model->with('getOrganization')->get()->toArray();
        }

        return $this->ResponseData(false);
    }

    public function getExcel($id){
        $VerifyItem     = VerifyItemDetailModel::where('verify_id', $id)->get($this->field_item)->toArray();
        $ListProduct    = [];
        if(!empty($VerifyItem)){
            $ListUId    = [];
            foreach($VerifyItem as $val){
                $ListUId[]  = $val['uid'];
            }
            $ListProduct    = $this->__get_product($ListUId);
        }

        return Response::json([
            'error'         => false,
            'code'          => $this->code,
            'error_message' => $this->message,
            'order_verify'  => OrderVerifyModel::where('id', $id)->with('getOrganization')->get($this->field_verify)->toArray(),
            'verify_order'  => VerifyOrderDetailModel::where('verify_id', $id)->get($this->field_order)->toArray(),
            'verify_item'   => $VerifyItem,
            'product'       => $ListProduct
        ]);

    }

    private function ResponseData($error){
        return Response::json([
            'error'         => $error,
            'code'          => $this->code,
            'error_message' => $this->message,
            'item_page'     => 20,
            'total'         => $this->total,
            'data'          => $this->data,
            'data_sum'      => $this->data_sum,
        ]);
    }

    /**
     * get verify detail
     */
    public function getVerifyDetail($id = ''){
        $page               = Input::has('page')            ? (int)Input::get('page')                       : 1;
        $itemPage           = Input::has('limit')           ? Input::get('limit')                           : 20;
        $cmd                = Input::has('cmd')             ? strtolower(trim(Input::get('cmd')))           : '';
        $Token              = Input::has('token')           ? trim(Input::get('token'))                     : '';
        $MerchantKey        = Input::has('MerchantKey')     ? trim(Input::get('MerchantKey'))               : '';
        $TrackingCode       = Input::has('tracking_code')   ? trim(Input::get('tracking_code'))             : '';

        $UserInfo               = $this->UserInfo();
        if(empty($UserInfo) && $Token != $this->config){
            return Response::json([
                'error'         => true,
                'message'       => 'Token không chính xác',
                'data'          => []
            ]);
        }

        if(empty($UserInfo) && empty($MerchantKey)){ // gọi từ seller
            $this->code             = 'ERROR';
            $this->message          = 'Thiếu merchant_key';
            return $this->ResponseData(true);
        }

        $OrderVerifyModel       = $this->getModel();
        if(!$OrderVerifyModel){
            return $this->ResponseData(false);
        }

        $VerifyOrderDetailModel = new VerifyOrderDetailModel;

        if(!empty($MerchantKey)){
            $Organization   = $this->__check_merchant_key($MerchantKey);

            if(!$Organization){
                $this->code             = 'ERROR';
                $this->message          = 'MerchantKey không chính xác';
                return $this->ResponseData(true);
            }

            $OrderVerifyModel  = $OrderVerifyModel->where('organization', $Organization);
        }

        if(!empty($TrackingCode)){
            if(preg_match("/^SC/i", $TrackingCode)){
                $VerifyOrderDetailModel = $VerifyOrderDetailModel->where('sc_tracking_code', $TrackingCode);

            }elseif(preg_match("/^O/i", $TrackingCode)){
                $VerifyOrderDetailModel = $VerifyOrderDetailModel->where('order_number', $TrackingCode);
            }else{
                $ListVerifyItem = VerifyItemDetailModel::where('uid', $TrackingCode)->lists('verify_id');
                if(empty($ListVerifyItem)){
                    return $this->ResponseData(false);
                }
                $OrderVerifyModel   = $OrderVerifyModel->whereIn('id',$ListVerifyItem);
            }
        }

        if(empty($id)){
            $id               = Input::has('id')            ? (int)Input::get('id')       : 0;
        }

        if(!empty($id)){
            $OrderVerifyModel   = $OrderVerifyModel->where('id',$id);
        }

        $ListVerify             = $OrderVerifyModel->get(['id','organization'])->toArray();
        if(empty($ListVerify)){
            return $this->ResponseData(false);
        }

        $ListId = [];
        $ListOrganization   = [];
        foreach($ListVerify as $val){
            $ListId[]                           = (int)$val['id'];
            $ListOrganization[(int)$val['id']]  = (int)$val['organization'];

        }

        $VerifyOrderDetailModel = $VerifyOrderDetailModel->whereIn('verify_id', $ListId);

        if($cmd == 'export'){
            if(!empty($ListOrganization)){
                $DataOrganization   = \metadatamodel\OrganizationUserModel::whereIn('id',array_values($ListOrganization))
                    ->get(['id','fullname','phone','email'])->toArray();
                $Organization   = [];
                if(!empty($DataOrganization)){
                    foreach($DataOrganization as $val){
                        $Organization[$val['id']]   = $val;
                    }

                    foreach($ListOrganization as $key => $val){
                        if(isset($Organization[$val])){
                            $ListOrganization[$key] = $Organization[$val];
                        }
                    }
                }

            }

            return Response::json([
                'error'         => false,
                'message'       => 'success',
                'total'         => 0,
                'data'          => $VerifyOrderDetailModel->get($this->field_order)->toArray(),
                'organization'  => $ListOrganization
            ]);
        }

        $ModelTotal = clone $VerifyOrderDetailModel;
        $Total      = $ModelTotal->count();
        $Data = [];

        if($Total > 0){
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
            $Data       = $VerifyOrderDetailModel->skip($offset)->take($itemPage)->get($this->field_order)->toArray();
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'total'         => $Total,
            'data'          => $Data
        ]);
    }

    /**
     * get item detail
     */
    public function getVerifyItemDetail($id = ''){
        $Token              = Input::has('Token')           ? trim(Input::get('Token'))                 : '';
        $cmd                = Input::has('cmd')             ? strtolower(trim(Input::get('cmd')))       : '';
        $MerchantKey        = Input::has('MerchantKey')     ? trim(Input::get('MerchantKey'))               : '';
        $TrackingCode       = Input::has('tracking_code')   ? trim(Input::get('tracking_code'))         : '';

        if(empty($id)){
            $id             = Input::has('id')            ? (int)Input::get('id')       : 0;
        }

        $UserInfo               = $this->UserInfo();
        $VerifyItemDetailModel  = new VerifyItemDetailModel;

        if(empty($UserInfo) && $Token != $this->config){ // gọi từ seller
            $this->code             = 'ERROR';
            $this->message          = 'Token không chính xác';
            return $this->ResponseData(true);
        }

        if(empty($UserInfo) && empty($MerchantKey)){ // gọi từ seller
            $this->code             = 'ERROR';
            $this->message          = 'Thiếu merchant_key';
            return $this->ResponseData(true);
        }

        $OrderVerifyModel       = $this->getModel();
        if(!$OrderVerifyModel){
            return $this->ResponseData(false);
        }

        if(!empty($MerchantKey)){
            $Organization   = $this->__check_merchant_key($MerchantKey);

            if(!$Organization){
                $this->code             = 'ERROR';
                $this->message          = 'MerchantKey không chính xác';
                return $this->ResponseData(true);
            }

            $OrderVerifyModel  = $OrderVerifyModel->where('organization', $Organization);
        }

        if(!empty($TrackingCode)){
            if(preg_match("/^U/i", $TrackingCode)){
                $VerifyItemDetailModel  = $VerifyItemDetailModel->where('uid', $TrackingCode);
            }else{
                $VerifyOrderDetailModel = new VerifyOrderDetailModel;
                if(preg_match("/^SC/i", $TrackingCode)){
                    $VerifyOrderDetailModel = $VerifyOrderDetailModel->where('sc_tracking_code', $TrackingCode);
                }else{
                    $VerifyOrderDetailModel = $VerifyOrderDetailModel->where('order_number', $TrackingCode);
                }
            }
        }

        if(!empty($id)){
            if(!isset($VerifyOrderDetailModel)){
                $VerifyOrderDetailModel = new VerifyOrderDetailModel;
            }
            $VerifyOrderDetailModel   = $VerifyOrderDetailModel->where('id',$id);
        }

        if(isset($VerifyOrderDetailModel)){
            $ListVerifyOrder    = $VerifyOrderDetailModel->get(['verify_id','order_number']);

            if(empty($ListVerifyOrder)){
                return $this->ResponseData(false);
            }

            foreach($ListVerifyOrder as $val){
                $ListId [] = (int)$val['verify_id'];
                $ListOrder[] = trim(strtoupper($val['order_number']));
            }

            $VerifyItemDetailModel  = $VerifyItemDetailModel->whereIn('order_number', $ListOrder);
            $OrderVerifyModel       = $OrderVerifyModel->whereIn('id',$ListId);
        }

        $ListVerify             = $OrderVerifyModel->lists('id');
        if(empty($ListVerify)){
            return $this->ResponseData(false);
        }

        $VerifyItemDetailModel          = $VerifyItemDetailModel->whereIn('verify_id', $ListVerify);

        if($cmd == 'export'){
            $VerifyItem     = $VerifyItemDetailModel->orderBy('id','DESC')->get($this->field_item)->toArray();
            $ListProduct    = [];
            if(!empty($VerifyItem)){
                $ListUId    = [];
                foreach($VerifyItem as $val){
                    $ListUId[]  = $val['uid'];
                }
                $ListProduct    = $this->__get_product($ListUId);
            }

            return Response::json([
                'error'         => false,
                'message'       => 'success',
                'data'          => $VerifyItem,
                'product'       => $ListProduct
            ]);
        }

        $this->data       = $VerifyItemDetailModel->orderBy('id','DESC')->get($this->field_item)->toArray();
        return $this->ResponseData(false);
    }

    public function getLogWarehouse(){
        $VerifyId   = Input::has('verify_id')   ? (int)Input::get('verify_id')      : 0;

        if(empty($VerifyId)){
            return $this->ResponseData(false);
        }

        $Verify = OrderVerifyModel::where('id', $VerifyId)->whereIn('config_warehouse',[1,2])->first();
        if(!isset($Verify->id)){
            return $this->ResponseData(false);
        }

        $Date = explode("-",$Verify->date);
        $Date = $Date[2];

        $Time = $this->__get_time_log_warehouse($Verify->date);

        $this->data = \bm_accmodel\LogWareHouseModel::where('organization', $Verify->organization)
                                                    ->where('payment_type', $Verify->config_warehouse)
                                                    ->where('date','>=', $Time['time_start'])
                                                    ->where('date','<=',$Time['time_end'])
                                                    ->get()->toArray();
        return $this->ResponseData(false);

    }

    public function getLogWarehouseDetail(){
        $itemPage           = 20;
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';

        $LogId          = Input::has('log_id')                      ? (int)Input::get('log_id')                     : 0;
        $TypeSku        = Input::has('type_sku')                    ? trim(strtoupper(Input::get('type_sku')))      : 0;
        $Organization   = Input::has('organization')                ? (int)Input::get('organization')               : 0;
        $CreatedStart   = Input::has('created_start')               ? (int)Input::get('created_start')              : 0;
        $CreatedEnd     = Input::has('created_end')                 ? (int)Input::get('created_end')                : 0;
        $WareHouse      = Input::has('warehouse')                    ? trim(strtoupper(Input::get('warehouse')))      : 0;

        $Model  = new \bm_accmodel\LogWareHouseDetailModel;

        if(!empty($CreatedStart) || !empty($CreatedEnd) || !empty($LogId) || !empty($Organization)){
            $ListLog = new \bm_accmodel\LogWareHouseModel;

            if(!empty($CreatedStart)){
                $CreatedStart    = date('Y-m-d', $CreatedStart);
                $ListLog          = $ListLog->where('date','>=',$CreatedStart);
            }

            if(!empty($CreatedEnd)){
                $CreatedEnd    = date('Y-m-d', $CreatedEnd);
                $ListLog          = $ListLog->where('date','<=',$CreatedEnd);
            }

            if(!empty($Organization)){
                $ListLog  = $ListLog->where('organization', $Organization);
            }

            if(!empty($LogId)){
                $ListLog  = $ListLog->where('id', $LogId);
            }

            $ListLog    = $ListLog->lists('id');

            if(empty($ListLog)){
                return $this->ResponseData(false);
            }

            $Model  = $Model->whereIn('log_id', $ListLog);
        }

        if(!empty($TypeSku)){
            $Model  = $Model->where('type_sku', $TypeSku);
        }

        if(!empty($WareHouse)){
            $Model  = $Model->where('warehouse', $WareHouse);
        }
        
        if($Cmd == 'export'){
            $this->data = $Model->orderBy('id','DESC')->with(['get_log_warehouse' => function($query){
                $query->with('getOrganization');
            }])->get()->toArray();
            return $this->ResponseData(false);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model      = $Model->skip($offset)->take($itemPage);
            $this->data = $Model->orderBy('id','DESC')->with('get_log_warehouse')->get()->toArray();
        }

        return $this->ResponseData(false);
    }


    public function getExcelLogWareHouse(){
        $CreatedStart   = Input::has('create_start')               ? (int)Input::get('create_start')              : 0;
        $CreatedEnd     = Input::has('create_end')                 ? (int)Input::get('create_end')                : 0;

        $OrderVerifyModel   = new OrderVerifyModel;

        $OrderVerifyModel   = $OrderVerifyModel::where('warehouse_fee','>',0);

        if(!empty($CreatedStart)){
            $CreatedStart           = date('Y-m-d', $CreatedStart);
            $OrderVerifyModel       = $OrderVerifyModel->where('date','>=',$CreatedStart);
        }

        if(!empty($CreatedEnd)){
            $CreatedEnd         = date('Y-m-d', $CreatedEnd);
            $OrderVerifyModel   = $OrderVerifyModel->where('date','<=',$CreatedEnd);
        }

        $Verify = $OrderVerifyModel->whereIn('config_warehouse',[1,2])->with('getOrganization')->get()->toArray();

        if(empty($Verify)){
            return $this->ResponseData(false);
        }

        $LogWareHouseModel  = new \bm_accmodel\LogWareHouseModel;
        foreach($Verify as $val){
            $Time = $this->__get_time_log_warehouse($val['date']);

            $this->data[$val['id']] = [
                'data'              => [],
                'id'                => $val['id'],
                'date'              => $val['date'],
                'type'              => $val['type'],
                'config_warehouse'  => $val['config_warehouse'],
                'get_organization'  => $val['get_organization']
            ];

            $this->data[$val['id']]['data']   = $LogWareHouseModel->where('organization', $val['organization'])
                                                                  ->where('payment_type', $val['config_warehouse'])
                                                                  ->where('date','>=', $Time['time_start'])
                                                                  ->where('date','<=',$Time['time_end'])
                                                                  ->with('__warehouse_detail')
                                                                  ->get(['id','date','organization','payment_type','warehouse'])->toArray();
        }

        return $this->ResponseData(false);
    }

}
