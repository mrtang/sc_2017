<?php namespace bm_accounting;

use bm_accmodel\VerifyItemDetailModel;
use bm_accmodel\VerifyOrderDetailModel;
use metadatamodel\OrganizationUserModel;
use bm_accmodel\PartnerVerifyModel;

class VerifyPartnerCtrl extends BaseCtrl {
    private $data_sum       = [];
    private $user           = [];

    private $message_error  = '';
    private $Status;
    private $Active;
    private $master_id      = 1;
    private $config         = [];
    private $message        = 'Thành Công';
    private $code           = 'SUCCESS';
    private $verify         = [];

    private $field_order    = ['id','user','partner_verify_id','warehouse','order_number','sc_tracking_code','size','total_uid','time_stock','partner_warehouse_fee','partner_package_fee'
        ,'partner_handling_fee', 'partner_discount_warehouse','partner_discount_package','partner_discount_handling'];

    private $field_item     = ['id','partner_verify_id','order_number','uid','time_stocked','time_packed','partner_warehouse_fee','partner_handling_fee','partner_discount_warehouse','partner_discount_handling'];

    public  function __construct(){
        $this->config       = \Config::get('config_api.domain.boxme.accounting');
    }

    private function getModel(){
        $TimeCreateStart    = Input::has('create_start')    ? trim(Input::get('create_start'))                  : '';
        $TimeCreateEnd      = Input::has('create_end')      ? trim(Input::get('create_end'))                    : '';
        $Status             = Input::has('status')          ? trim(Input::get('status'))                        : 'ALL';
        $Keyword            = Input::has('keyword')         ? trim(Input::get('keyword'))                       : '';

        $Model      = new PartnerVerifyModel;

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
        $itemPage       = 20;

        $page               = Input::has('page')            ? (int)Input::get('page')                               : 1;

        $cmd                = Input::has('cmd')             ? strtolower(trim(Input::get('cmd')))                   : '';
        $Token              = Input::has('token')           ? trim(Input::get('token'))                             : '';
        $WareHouse          = Input::has('warehouse')       ? strtoupper(trim(Input::get('warehouse')))             : 'ALL';
        $TrackingCode       = Input::has('tracking_code')   ? trim(Input::get('tracking_code'))                     : '';

        $UserInfo           = $this->UserInfo();

        if(empty($UserInfo) && $Token != $this->config){ // gọi từ seller
            $this->code             = 'ERROR';
            $this->message          = 'Token không chính xác';
            return $this->ResponseData(true);
        }

        $Model              = $this->getModel();
        if(!$Model){
            return $this->ResponseData(false);
        }


        if($WareHouse != 'ALL'){
            $Model  = $Model->where('warehouse', $WareHouse);
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

        $Model = $Model->orderBy('time_create','DESC');


        if($cmd == 'export'){
            $this->data = $Model->get()->toArray();
            return $this->ResponseData(false);
        }

        $ModelTotal = clone $Model;
        $this->data_sum    = $ModelTotal->first([DB::raw(
                                                'count(*) as total,
                                                sum(warehouse_fee) as warehouse_fee,
                                                sum(package_fee) as package_fee,
                                                 sum(handling_fee) as handling_fee,
                                                 sum(total_uid) as total_uid,
                                                 sum(time_stock) as time_stock,
                                                 sum(discount_warehouse) as discount_warehouse,
                                                 sum(discount_package)  as discount_package,
                                                 sum(discount_handling) as discount_handling,
                                                 sum(total_uid_storage) as total_uid_storage,
                                                 sum(total_sku) as total_sku,
                                                 sum(floor) as floor
                                                 ')]);

        if(isset($this->data_sum->total) && $this->data_sum->total > 0){
            $this->total    = $this->data_sum->total;

            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model      = $Model->skip($offset)->take($itemPage);
            }
           $this->data = $Model->get()->toArray();
        }

        return $this->ResponseData(false);
    }

    public function getCountGroup(){
        $TrackingCode       = Input::has('tracking_code')   ? trim(Input::get('tracking_code'))         : '';
        $Model              = $this->getModel();
        if(!$Model){
            return $this->ResponseData(false);
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


        $GroupStatus    = $Model->groupBy('warehouse')->get(array('warehouse',DB::raw('count(*) as total')))->toArray();
        if(empty($GroupStatus)){
            return $this->ResponseData(false);
        }

        $this->data['ALL']  = 0;
        foreach($GroupStatus as $val){
            $this->data[$val['warehouse']]  = (int)$val['total'];
            $this->data['ALL']             += (int)$val['total'];
        }

        return $this->ResponseData(false);
    }

    public function getExcel($id){
        //$VerifyItem     = VerifyItemDetailModel::where('partner_verify_id', $id)->get($this->field_item)->toArray();
        $VerifyItem     = VerifyItemDetailModel::where('partner_verify_id', $id)->get($this->field_item)->toArray();
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
            'order_verify'  => PartnerVerifyModel::where('id', $id)->get()->toArray(),
            'verify_order'  => VerifyOrderDetailModel::where('partner_verify_id', $id)->get($this->field_order)->toArray(),
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
            'user'          => $this->user,
            'data_sum'      => $this->data_sum,
        ]);
    }

    /**
     * get verify detail
     */
    public function getVerifyDetail($id = ''){
        $page               = Input::has('page')            ? (int)Input::get('page')                   : 1;
        $itemPage           = Input::has('limit')           ? Input::get('limit')                       : 20;
        $cmd                = Input::has('cmd')             ? strtolower(trim(Input::get('cmd')))       : '';
        $Token              = Input::has('token')           ? trim(Input::get('token'))                 : '';
        $TrackingCode       = Input::has('tracking_code')   ? trim(Input::get('tracking_code'))         : '';
        $WareHouse          = Input::has('warehouse')       ? strtoupper(trim(Input::get('warehouse'))) : '';

        if(empty($id)){
            $id               = Input::has('id')            ? (int)Input::get('id')       : '';
        }

        $UserInfo           = $this->UserInfo();

        if(empty($UserInfo) && $Token != $this->config){
            return Response::json([
                'error'         => true,
                'message'       => 'Token không chính xác',
                'data'          => []
            ]);
        }

        $Model                      = $this->getModel();
        $VerifyOrderDetailModel     = new VerifyOrderDetailModel;
        if(!$Model){
            return $this->ResponseData(false);
        }

        if(!empty($WareHouse)){
            $Model = $Model->where('warehouse', $WareHouse);
        }

        if(!empty($TrackingCode)){
            if(preg_match("/^SC/i", $TrackingCode)){
                $VerifyOrderDetailModel = $VerifyOrderDetailModel::where('sc_tracking_code', $TrackingCode);

            }elseif(preg_match("/^O/i", $TrackingCode)){
                $VerifyOrderDetailModel = $VerifyOrderDetailModel::where('order_number', $TrackingCode);
            }else{
                $ListVerify = VerifyItemDetailModel::where('uid', $TrackingCode)->lists('verify_id');

                if(empty($ListVerify)){
                    return $this->ResponseData(false);
                }

                $Model  = $Model->whereIn('id',$ListVerify);
            }
        }

        if(!empty($id)){
            $Model  = $Model->where('id', $id);
        }

        $ListPartner    = $Model->lists('id');
        if(empty($ListPartner)){
            return $this->ResponseData(false);
        }

        $VerifyOrder    = $VerifyOrderDetailModel->whereIn('partner_verify_id', $ListPartner);

        if($cmd == 'export'){
            $Data       = [];
            $UserId     = [];
            $VerifyOrder->with('getOrder')->select($this->field_order)->chunk(1000,function($query) use(&$Data, &$UserId){
                foreach($query as $val){
                    $val                = $val->toArray();
                    $Data[]             = $val;
                    $UserId[]           = $val['user'];
                }
            });

            if(!empty($UserId)){
                $ListOrganization   = \User::whereIn('id',$UserId)->with('get_organization')->get(['id','organization'])->toArray();
                if(!empty($ListOrganization)){
                    foreach ($ListOrganization as $val){
                        $this->user[$val['id']] = $val;
                    }
                }
            }

            $this->data = $Data;
            return $this->ResponseData(false);
        }

        $ModelTotal         = clone $VerifyOrder;
        $this->total        = $ModelTotal->count();

        if($this->total > 0){
            $itemPage       = (int)$itemPage;
            $offset         = ($page - 1)*$itemPage;
            $this->data     = $VerifyOrder->skip($offset)->take($itemPage)->get($this->field_order)->toArray();
        }

        return $this->ResponseData(false);
    }

    /**
     * get item detail
     */
    public function getVerifyItemDetail($id = ''){
        $cmd                = Input::has('cmd')             ? strtolower(trim(Input::get('cmd')))       : '';
        $Token              = Input::has('token')           ? trim(Input::get('token'))                 : '';
        $TrackingCode       = Input::has('tracking_code')   ? trim(Input::get('tracking_code'))         : '';
        $WareHouse          = Input::has('warehouse')       ? strtoupper(trim(Input::get('warehouse'))) : '';

        $UserInfo               = $this->UserInfo();

        if(empty($UserInfo) && $Token != $this->config){
            $this->code     = 'ERROR';
            $this->message  = 'Token không chính xác';
            return $this->ResponseData(true);
        }

        $Model                      = $this->getModel();
        $VerifyItemDetailModel      = new VerifyItemDetailModel;
        if(!$Model){
            return $this->ResponseData(false);
        }

        if(!empty($WareHouse)){
            $Model = $Model->where('warehouse', $WareHouse);
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
            $Model                  = $Model->whereIn('id',$ListId);
        }

        $ListVerify             = $Model->lists('id');
        if(empty($ListVerify)){
            return $this->ResponseData(false);
        }

        $VerifyItemDetailModel          = $VerifyItemDetailModel->whereIn('partner_verify_id', $ListVerify);

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
            $contents = array(
                'error'         => false,
                'message'       => 'success',
                'data'          => $VerifyItem,
                'product'       => $ListProduct
            );
            return Response::json($contents);
        }

        $this->data       = $VerifyItemDetailModel->orderBy('id','DESC')->get($this->field_item)->toArray();
        return $this->ResponseData(false);
    }

    public function getLogWarehouseDetail(){
        $itemPage       = 20;
        $page           = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Cmd            = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';

        $PartnerId      = Input::has('partner_id')                  ? (int)Input::get('partner_id')                 : 0;
        $TypeSku        = Input::has('type_sku')                    ? trim(strtoupper(Input::get('type_sku')))      : 0;
        $WareHouse      = Input::has('warehouse')                   ? trim(strtoupper(Input::get('warehouse')))     : 0;

        $Model  = new \bm_accmodel\LogWareHouseDetailModel;
        $ListLog      = new \bm_accmodel\PartnerReferLogModel;
        if(!empty($PartnerId)){
            $ListLog  = $ListLog::where('partner_id', $PartnerId);
        }

        $ListLog  = $ListLog->lists('log_id');
        if(empty($ListLog)){
            return $this->ResponseData(false);
        }

        $Model  = $Model->whereIn('log_id', $ListLog);

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

        $Model          = new PartnerVerifyModel;
        $LogWareHouse   = [];
        $LogId          = [];

        if(!empty($CreatedStart)){
            $CreatedStart           = date('Y-m-d', $CreatedStart);
            $Model                  = $Model->where('date','>=',$CreatedStart);
        }

        if(!empty($CreatedEnd)){
            $CreatedEnd         = date('Y-m-d', $CreatedEnd);
            $Model              = $Model->where('date','<=',$CreatedEnd);
        }

        $this->data = $Model->with('__refer_warehouse')->get(['id','date','warehouse'])->toArray();

        if(empty($this->data)){
            return Response::json([
                'error'         => false,
                'code'          => $this->code,
                'error_message' => $this->message,
                'data'          => $this->data,
                'log_warehouse' => $LogWareHouse,
            ]);
        }

        foreach($this->data as $val){
            if(isset($val['__refer_warehouse'])){
                foreach($val['__refer_warehouse'] as $v){
                    $LogId[]    = $v['log_id'];
                }
            }
        }

        if(!empty($LogId)){
            $LogWareHouse  = \bm_accmodel\LogWareHouseModel::whereIn('id',$LogId)->with(['__warehouse_detail_partner','getOrganization'])
                ->get(['id','date','organization','payment_type','warehouse'])->toArray();;
        }

        return Response::json([
            'error'         => false,
            'code'          => $this->code,
            'error_message' => $this->message,
            'data'          => $this->data,
            'log_warehouse' => $LogWareHouse,
        ]);
    }
}
