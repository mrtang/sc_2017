<?php namespace order;
use Response;
use Input;
use DB;
use ordermodel\OrdersModel;
use ordermodel\AddressModel;
use ordermodel\DetailModel;
use metadatamodel\GroupStatusModel;
use metadatamodel\GroupOrderStatusModel;
use sellermodel\UserInventoryModel;
use sellermodel\UserInfoModel;
use Cache;
use CourierModel;
use CityModel;
use DistrictModel;
use WardModel;
use LMongo;
use Excel;
use Validator;
use ApiDispatcherCtrl;
use anlutro\cURL\Request;
use omsmodel\StatisticModel;
use omsmodel\SellerModel;
use omsmodel\PipeJourneyModel;
use ElasticBuilder;


class OrderESController extends \BaseController {
    private $time_create_start  = '';
    private $time_create_end    = '';
    private $time_accept_start  = '';
    private $time_accept_end    = '';
    private $time_success_start  = '';
    private $time_success_end    = '';
    private $list_status        = [];
    private $user_id;
    private $stock_id;
    private $list_province      = [];
    private $address            = [];

    private $range_filter     = [];
    private $term_filter      = [];
    private $terms_filter     = [];


    /**
     * Display a listing of the resource.
     *
     * @return Response
     */

    private function GetModelES(){
        
        $Model = new ElasticBuilder('bxm_orders', 'orders');
        

        $Search             = Input::has('search')              ? trim(Input::get('search'))                    : '';
        $TimeStart          = Input::has('time_start')          ? (int)Input::get('time_start')                 : 0; //  7 day  - 14 day  -  30day  - 90 day
        $TimeCreateStart    = Input::has('time_create_start')   ? (int)Input::get('time_create_start')          : 0; // time_create start   time_stamp
        $TimeCreateEnd      = Input::has('time_create_end')     ? (int)Input::get('time_create_end')            : 0; // time_create end
        $TimeAcceptStart    = Input::has('time_accept_start')   ? (int)Input::get('time_accept_start')          : 0; // time_accept start
        $TimeAcceptEnd      = Input::has('time_accept_end')     ? (int)Input::get('time_accept_end')            : 0; // time_accept end

        $TimeSuccessStart   = Input::has('time_success_start')  ? (int)Input::get('time_success_start')         : 0; // time_accept start
        $TimeSuccessEnd     = Input::has('time_success_end')    ? (int)Input::get('time_success_end')           : 0; // time_accept end

        
        $UserId             = Input::has('user_id')             ? (int)Input::get('user_id')                    : 0;
        $CourierId          = Input::has('courier_id')          ? (int)Input::get('courier_id')                 : 0;
        $ToCity             = Input::has('to_city')             ? (int)Input::get('to_city')                    : 0;
        $ToDistrict         = Input::has('to_district')         ? (int)Input::get('to_district')                : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))               : '';
        $VerifyId           = Input::has('verify_id')           ? (int)Input::get('verify_id')                  : 0;
        $Inventory          = Input::has('inventory')           ? (int)Input::get('inventory')                  : 0;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';
        $OverWeight         = Input::has('over_weight')         ? trim(Input::get('over_weight'))               : '';
        $ByVip              = Input::has('by_vip')              ? trim(Input::get('by_vip'))                    : '';


        $UserInfo   = $this->UserInfo();


        if($UserInfo['privilege'] == 1) {
            
        }
        if($UserId > 0 && $UserInfo['privilege'] > 0){
            $id     = $UserId;
        }else {
            $id = (int)$UserInfo['id'];
        }

        $OrdersModel          = new OrdersModel;

        
        if(!empty($ByVip)){
            
            $_userInfo      = new UserInfoModel();


            $listUserVip    = $_userInfo->where('is_vip', 1)->select('user_id', 'is_vip')->get()->toArray();
            if($listUserVip) {
                $listUserVip = array_map(function ($value){
                    return $value['user_id'];
                }, $listUserVip);


            }
        }else {
            if(!empty($id)){
                $Model->where('from_user_id', $id);
            }    
        }
        
        

        if(!empty($Search)){
            if (filter_var($Search, FILTER_VALIDATE_EMAIL)){  // search email
                $Model->where('to_email', $Search);
                
            }elseif(filter_var((int)$Search, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                
                $Model->where('to_phone', $Search);
                
            }else{ // search code

                $validation = Validator::make(['TrackingCode' => $Search], 
                    array(
                        'TrackingCode'  => array('required','regex:/SC[0-9]+$/'),
                    ));
                if($validation->fails()) {
                    $Model->where('order_code', $Search);
                }else {
                    $Model->where('tracking_code', $Search);
                }
            }
        }

        if($TimeStart > 0){
            $TimeCreateStart    = time() - $TimeStart*86400;
        }

        if($TimeCreateStart > 0){

            $Model->where('time_create', 'gte', $TimeCreateStart);
        }else{
            $TimeCreateStart    = strtotime(date('Y-m-1 00:00:00'));
            $Model->where('time_create', 'gte', $TimeCreateStart);
        }

        if($TimeCreateEnd > 0){
            
            $Model->where('time_create', 'lte', $TimeCreateEnd);
        }
        if($TimeAcceptStart > 0){
            $Model->where('time_accept', 'gte', $TimeAcceptStart);
        }else{
            /*$this->range_filter['time_accept']['gte'] = $TimeAcceptStart;
            $Model          = $Model->where(function($query) use($TimeCreateStart){
                $query->where('time_accept','>=', $TimeCreateStart)
                      ->orWhere('time_accept',0);
            });*/
        }

        if($TimeSuccessStart > 0){
            $Model->where('time_success','gte',$TimeSuccessStart);
        }

        if($TimeSuccessEnd > 0){
            $Model->where('time_success','lte',$TimeSuccessEnd);
        }


        if($TimeAcceptEnd > 0){
            $Model->where('time_accept', 'lte', $TimeAcceptEnd);
            
        }
        if($CourierId > 0){
            $Model->where('courier_id',  $CourierId);
        }

        if($VerifyId > 0){
            $Model->where('verify_id',  $VerifyId);
        }

        if($Inventory > 0){
            $Model->where('from_address_id',  $Inventory);
        }



        if(!empty($OverWeight)){

            $ModelOverWeight    = clone $Model;
            $ListOrderId        = $ModelOverWeight->where('accept_overweight', 0)->where('verify_id', 0)->get()->lists('id');
            $ListOverWeight     = [];

            if(!empty($ListOrderId)){
                $OrderDetail = new DetailModel;
                $OrderDetail = $OrderDetail->whereIn('order_id', $ListOrderId)->get()->toArray();
                foreach ($OrderDetail as $key => $value) {
                    if($value['sc_pvk'] != 0){
                        $ListOverWeight[] = $value['order_id'];
                    }
                }
            }
            
            if(!empty($ListOverWeight)){
                $Model = $Model->whereIn('id', $ListOverWeight);
            }else {
                $Model = $Model->whereIn('id', [0]);
            }
        }


        if(!empty($ListStatus)){
            $ListStatus         = explode(',',$ListStatus);
            $GroupOrderStatusModel  = new GroupOrderStatusModel;
            $ListStatusOrder = $GroupOrderStatusModel::whereIn('group_status',$ListStatus)->get(array('group_status', 'order_status_code'))->toArray();
            $ListStatus = [0];
            if(!empty($ListStatusOrder)){
                foreach($ListStatusOrder as $val){
                    $ListStatus[]   = (int)$val['order_status_code'];
                }
            }

            $Model->whereIn('status', $ListStatus);
            
            //$Model = $Model->whereIn('status', $ListStatus);
        }

        if($ToCity > 0 && empty($ToDistrict)){
            $AddressModel   = new AddressModel;

            if(!empty($id)){
                $AddressModel = $AddressModel->where('seller_id', $id);
            }

            $ListAddress    = $AddressModel->where('city_id',$ToCity)->get(array('id', 'seller_id', 'city_id','province_id'))->ToArray();

            if (!empty($ListAddress)) {
                foreach ($ListAddress as $val) {
                    $ListId[]                   = $val['id'];
                    $this->address[$val['id']]  = $val;
                    $this->list_province[]      = (int)$val['province_id'];
                }
            }
        }elseif($ToDistrict > 0){
            $AddressModel   = new AddressModel;

            if(!empty($id)){
                $AddressModel = $AddressModel->where('seller_id', $id);
            }

            $ListAddress    = $AddressModel->where('province_id',$ToDistrict)->get(array('id', 'seller_id', 'city_id', 'province_id'))->ToArray();

            $ListId = [0];
            if (!empty($ListAddress)) {
                foreach ($ListAddress as $val) {
                    $ListId[]                   = $val['id'];
                    $this->address[$val['id']]  = $val;
                    $this->list_province[]      = (int)$val['province_id'];
                }
            }
        }

        if(!empty($ListId)){
            $Model->whereIn('to_address_id', $ListId);
        }

        if($Cmd == 'export'){
            if(!empty($TimeCreateStart))    $this->time_create_start    = date('m/d/Y', $TimeCreateStart);
            if(!empty($TimeCreateEnd))      $this->time_create_end      = date('m/d/Y', $TimeCreateEnd);
            if(!empty($TimeAcceptStart))    $this->time_accept_start    = date('m/d/Y', $TimeAcceptStart);
            if(!empty($TimeAcceptEnd))      $this->time_accept_end      = date('m/d/Y', $TimeAcceptEnd);
        }

        return $Model;
    }



    private function GetModel(){
        $Search             = Input::has('search')              ? trim(Input::get('search'))                    : '';
        $TimeStart          = Input::has('time_start')          ? (int)Input::get('time_start')                 : 0; //  7 day  - 14 day  -  30day  - 90 day
        $TimeCreateStart    = Input::has('time_create_start')   ? (int)Input::get('time_create_start')          : 0; // time_create start   time_stamp
        $TimeCreateEnd      = Input::has('time_create_end')     ? (int)Input::get('time_create_end')            : 0; // time_create end
        $TimeAcceptStart    = Input::has('time_accept_start')   ? (int)Input::get('time_accept_start')          : 0; // time_accept start
        $TimeAcceptEnd      = Input::has('time_accept_end')     ? (int)Input::get('time_accept_end')            : 0; // time_accept end

        $TimeSuccessStart   = Input::has('time_success_start')  ? (int)Input::get('time_success_start')         : 0; // time_accept start
        $TimeSuccessEnd     = Input::has('time_success_end')    ? (int)Input::get('time_success_end')           : 0; // time_accept end

        $UserId             = Input::has('user_id')             ? (int)Input::get('user_id')                    : 0;
        $CourierId          = Input::has('courier_id')          ? (int)Input::get('courier_id')                 : 0;
        $ToCity             = Input::has('to_city')             ? (int)Input::get('to_city')                    : 0;
        $ToDistrict         = Input::has('to_district')         ? (int)Input::get('to_district')                : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))               : '';
        $VerifyId           = Input::has('verify_id')           ? (int)Input::get('verify_id')                  : 0;
        $Inventory          = Input::has('inventory')           ? (int)Input::get('inventory')                  : 0;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';
        $OverWeight         = Input::has('over_weight')         ? trim(Input::get('over_weight'))               : '';
        $ByVip              = Input::has('by_vip')              ? trim(Input::get('by_vip'))                    : '';


        //\Shipchung\Logs::TrackQuery();
        $UserInfo   = $this->UserInfo();


        if($UserInfo['privilege'] == 1) {
            //list customer Vip đc quản lý
            /*$listUserID = SellerModel::where('cs_id',$UserInfo['id'])->lists('user_id');
            if(empty($listUserID)) {
                $listUserID = [0];
            }*/
        }
        if($UserId > 0 && $UserInfo['privilege'] > 0){
            $id     = $UserId;
        }else {
            $id = (int)$UserInfo['id'];
        }

        $Model          = new OrdersModel;
        
        if(!empty($ByVip)){
            
            $_userInfo      = new UserInfoModel();

            /*if(!empty($listUserID)) {
                $_userInfo = $_userInfo->whereIn('user_id',$listUserID);
            }*/

            $listUserVip    = $_userInfo->where('is_vip', 1)->select('user_id', 'is_vip')->get()->toArray();
            //var_dump($listUserVip);die();
            if($listUserVip) {
                $listUserVip = array_map(function ($value){
                    return $value['user_id'];
                }, $listUserVip);


                $Model          = $Model::whereIn('from_user_id', $listUserVip);
            }
        }else {
            if(!empty($id)){
                $Model          = $Model::where('from_user_id',$id);
            }    
        }
        
        

        if(!empty($Search)){
            if (filter_var($Search, FILTER_VALIDATE_EMAIL)){  // search email
                $Model          = $Model->where('to_email',$Search);
            }elseif(filter_var((int)$Search, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $Model          = $Model->where('to_phone',$Search);
            }else{ // search code

                $validation = Validator::make(['TrackingCode' => $Search], 
                    array(
                        'TrackingCode'  => array('required','regex:/SC[0-9]+$/'),
                    ));
                if($validation->fails()) {
                    $Model = $Model->where('order_code',$Search);
                }else {
                    $Model = $Model->where('tracking_code',$Search);
                }
                //$Model          = $Model->where('tracking_code','LIKE','%'.$Search.'%');
            }
        }

        if($TimeStart > 0){
            $TimeCreateStart    = time() - $TimeStart*86400;
        }

        if($TimeCreateStart > 0){
            $Model          = $Model->where('time_create','>=',$TimeCreateStart);
        }else{
            $TimeCreateStart    = strtotime(date('Y-m-1 00:00:00'));
            $Model              = $Model->where('time_create','>=',$TimeCreateStart);

        }

        if($TimeCreateEnd > 0){
            $Model          = $Model->where('time_create','<=',$TimeCreateEnd);
        }
        if($TimeAcceptStart > 0){
            $Model          = $Model->where('time_accept','>=',$TimeAcceptStart);
        }else{
            $Model          = $Model->where(function($query) use($TimeCreateStart){
                $query->where('time_accept','>=', $TimeCreateStart)
                      ->orWhere('time_accept',0);
            });
        }

        if($TimeAcceptEnd > 0){
            $Model          = $Model->where('time_accept','<=',$TimeAcceptEnd);
        }

        if($TimeSuccessStart > 0){
            $Model          = $Model->where('time_success','>=',$TimeSuccessStart);
        }

        if($TimeSuccessEnd > 0){
            $Model          = $Model->where('time_success','<=',$TimeSuccessEnd);
        }

        if($CourierId > 0){
            $Model = $Model->where('courier_id',$CourierId);
        }

        if($VerifyId > 0){
            $Model = $Model->where('verify_id',$VerifyId);
        }

        if($Inventory > 0){
            $Model = $Model->where('from_address_id',$Inventory);
        }



        if(!empty($OverWeight)){

            /*$Model = $Model->join('order_detail', 'order_detail.order_id', '=', 'orders.id');
            $Model  = $Model->where('order_detail.sc_pvk', '!=', 0)->where('orders.accept_overweight' ,'=', 0)->where('orders.verify_id', '=', 0);*/


            $ModelOverWeight    = clone $Model;
            $ListOrderId        = $ModelOverWeight->where('accept_overweight', 0)->where('verify_id', 0)->get()->lists('id');
            $ListOverWeight     = [];

            if(!empty($ListOrderId)){
                $OrderDetail = new DetailModel;
                $OrderDetail = $OrderDetail->whereIn('order_id', $ListOrderId)->get()->toArray();
                foreach ($OrderDetail as $key => $value) {
                    if($value['sc_pvk'] != 0){
                        $ListOverWeight[] = $value['order_id'];
                    }
                }
            }
            
            if(!empty($ListOverWeight)){
                $Model = $Model->whereIn('id', $ListOverWeight);
            }else {
                $Model = $Model->whereIn('id', [0]);
            }
        }


        if(!empty($ListStatus)){
            $ListStatus         = explode(',',$ListStatus);
            $GroupOrderStatusModel  = new GroupOrderStatusModel;
            $ListStatusOrder = $GroupOrderStatusModel::whereIn('group_status',$ListStatus)->get(array('group_status', 'order_status_code'))->toArray();
            $ListStatus = [0];
            if(!empty($ListStatusOrder)){
                foreach($ListStatusOrder as $val){
                    $ListStatus[]   = (int)$val['order_status_code'];
                }
            }
            $Model          = $Model->whereIn('status',$ListStatus);
        }

        if($ToCity > 0 && empty($ToDistrict)){
            $AddressModel   = new AddressModel;

            if(!empty($id)){
                $AddressModel = $AddressModel->where('seller_id', $id);
            }

            $ListAddress    = $AddressModel->where('city_id',$ToCity)->get(array('id', 'seller_id', 'city_id','province_id'))->ToArray();

            if (!empty($ListAddress)) {
                foreach ($ListAddress as $val) {
                    $ListId[]                   = $val['id'];
                    $this->address[$val['id']]  = $val;
                    $this->list_province[]      = (int)$val['province_id'];
                }
            }
        }elseif($ToDistrict > 0){
            $AddressModel   = new AddressModel;

            if(!empty($id)){
                $AddressModel = $AddressModel->where('seller_id', $id);
            }

            $ListAddress    = $AddressModel->where('province_id',$ToDistrict)->get(array('id', 'seller_id', 'city_id', 'province_id'))->ToArray();

            $ListId = [0];
            if (!empty($ListAddress)) {
                foreach ($ListAddress as $val) {
                    $ListId[]                   = $val['id'];
                    $this->address[$val['id']]  = $val;
                    $this->list_province[]      = (int)$val['province_id'];
                }
            }
        }

        if(!empty($ListId)){
            $Model  = $Model->whereIn('to_address_id',$ListId);
        }

        if($Cmd == 'export'){
            if(!empty($TimeCreateStart))    $this->time_create_start    = date('m/d/Y', $TimeCreateStart);
            if(!empty($TimeCreateEnd))      $this->time_create_end      = date('m/d/Y', $TimeCreateEnd);
            if(!empty($TimeAcceptStart))    $this->time_accept_start    = date('m/d/Y', $TimeAcceptStart);
            if(!empty($TimeAcceptEnd))      $this->time_accept_end      = date('m/d/Y', $TimeAcceptEnd);
        }

        return $Model;
    }


    public function getIndex() // seller center 7day - 3 month
    {
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Status             = Input::has('status')              ? strtoupper(trim(Input::get('status')))        : 'ALL';
        $itemPage           = Input::has('limit')               ? Input::get('limit')                           : 20;


        $Model          = $this->GetModel();

        // total group
        $CountGroupStatus       = $this->getCountGroup();

        $TotalAll       = 0;
        $Total          = 0;
        if(!empty($CountGroupStatus)){
            foreach($CountGroupStatus as $val){
                $TotalAll                  += (int)$val;
            }
        }

        // getdata
        $Data   = array();
        /*if($TotalAll > 0){*/
            if($Status != 'ALL'){
                if(!empty($this->list_status[$Status]) /*&& isset($CountGroupStatus[$Status]) && $CountGroupStatus[$Status] > 0*/){
                    $Model = $Model->whereIn('status',$this->list_status[$Status]);
                }else{
                    return Response::json([
                        'error'         => false,
                        'message'       => 'success',
                        'total'         => $Total,
                        'total_all'     => $TotalAll,
                        'total_group'   => $CountGroupStatus,
                        'data'          => []
                    ]);
                }
            }

            if($Status == 'ALL'){
                $Model      = $Model->where('status', '!=', 22);
                if(isset($CountGroupStatus[22])){
                    $TotalAll   = $TotalAll - $CountGroupStatus[22];    
                }
                $Total      = $TotalAll;

            }else{
                if(isset($CountGroupStatus[22])){
                    $TotalAll   = $TotalAll - $CountGroupStatus[22];    
                }
                $Total  = isset($CountGroupStatus[$Status]) ? $CountGroupStatus[$Status]: 0;
            }

            $Model      = $Model->orderBy('time_create','DESC')->with(array('OrderDetail', 'Courier' => function ($query){
                return $query->select(array('id', 'name', 'prefix'));
            }));

            if($itemPage != 'all'){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }

            $Data       = $Model->get(array('id','tracking_code','status','to_name','to_phone', 'domain','to_email','total_amount','total_weight','to_address_id', 'product_name','courier_id','service_id', 'verify_id','time_create','time_accept', 'order_code', 'time_update'))->toArray();
        /*}*/

        

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'total'         => $Total,
            'total_all'     => $TotalAll,
            'total_group'   => $CountGroupStatus,
            'data'          => $Data
        );
        
        return Response::json($contents);
    }


    /*public function getIndex() // seller center 7day - 3 month
    {
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Status             = Input::has('status')              ? strtoupper(trim(Input::get('status')))        : 'ALL';
        $itemPage           = Input::has('limit')               ? Input::get('limit')                           : 20;


        $Model              = $this->GetModelES();

        $Model->orderBy('time_create', 'desc');
        // total group
        $CountGroupStatus       = $this->getCountGroup();

        $TotalAll       = 0;
        $Total          = 0;
        if(!empty($CountGroupStatus)){
            foreach($CountGroupStatus as $val){
                $TotalAll                  += (int)$val;
            }
        }

        // getdata
        $Data   = array();
        if($TotalAll > 0){
            if($Status != 'ALL'){
                if(!empty($this->list_status[$Status]) && isset($CountGroupStatus[$Status]) && $CountGroupStatus[$Status] > 0){
                    //$this->terms_filter['status'] = $this->list_status[$Status];
                    $Model->whereIn('status', $this->list_status[$Status]);

                }else{
                    return Response::json([
                        'error'         => false,
                        'message'       => 'success',
                        'total'         => $Total,
                        'total_all'     => $TotalAll,
                        'total_group'   => $CountGroupStatus,
                        'data'          => []
                    ]);
                }
            }

            if($Status == 'ALL'){
                $Model->where('status', '!=', 22);
                if(isset($CountGroupStatus[22])){
                    $TotalAll   = $TotalAll - $CountGroupStatus[22];    
                }
                $Total      = $TotalAll;

            }else{
                if(isset($CountGroupStatus[22])){
                    $TotalAll   = $TotalAll - $CountGroupStatus[22];    
                }
                $Total  = $CountGroupStatus[$Status];
            }


            
            
            if($itemPage != 'all'){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;

                $Model->take($itemPage)->skip($offset);
            }

            $Data = $Model->get();


            $ListOrderId = [];
            $CourierId = [];

            if(sizeof($Data) > 0){

            
                foreach ($Data as $key => $value) {
                    $ListOrderId[] = $value['id'];
                    $CourierId[]   = $value['courier_id'];
                }
                
                $Courier     = CourierModel::whereIn('id', $CourierId)->select(['id', 'name', 'prefix'])->get()->toArray();
                $OrderDetail = DetailModel::whereIn('order_id', $ListOrderId)->get()->toArray();

                $OrderDetails = [];
                $Couriers = [];

                foreach ($Courier as $key => $value) {
                    $Couriers[$value['id']] = $value;
                }

                foreach ($OrderDetail as $key => $value) {
                    $OrderDetails[$value['order_id']] = $value;
                }

                foreach ($Data as $key => $value) {
                    if(!empty($OrderDetails[$value['id']])){
                        $Data[$key]['order_detail'] = $OrderDetails[$value['id']];
                    }

                    if(!empty($Couriers[$value['courier_id']])){
                        $Data[$key]['courier'] = $Couriers[$value['courier_id']];
                    }

                }
            }
        }

        

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'total'         => $Total,
            'total_all'     => $TotalAll,
            'total_group'   => $CountGroupStatus,
            'data'          => $Data
        );
        
        return Response::json($contents);
    }*/

    public function getOrder(){ // seller center - management
        $page           = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $itemPage       = Input::has('limit')               ? (int)Input::get('limit')                      : 20;
        $Cmd            = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';
        
        $Model      = $this->GetModel();

        if($Cmd == 'export'){
            return $this->ExportExcel($Model->with(['OrderDetail', 'OrderItem'])->get(
                array(
                        'orders.id',
                        'tracking_code',
                        'status',
                        'to_name',
                        'to_phone',
                        'to_email',
                        'total_amount',
                        'total_weight',
                        'over_weight',
                        'to_address_id',
                        'product_name',
                        'courier_id',
                        'time_create', 
                        'time_update', 
                        'verify_id', 
                        'from_address', 
                        'from_district_id', 
                        'from_ward_id', 
                        'from_user_id', 
                        'time_accept', 
                        'time_pickup', 
                        'time_success',
                        'courier_tracking_code',
                        'service_id',
                        'from_city_id',
                        'order_code'
                )
            )->ToArray());
        }

        $Data       = [];
        $District   = [];

        $Total      = $Model->count();

        if($Total > 0){
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
            $Data       = $Model->orderBy('time_create','DESC')
                                ->with(['OrderDetail','MetaStatus'])
                                ->skip($offset)
                                ->take($itemPage)
                                ->get(array('id','tracking_code', 'status','to_name','to_phone','to_email','total_amount','total_weight',
                                            'to_address_id', 'product_name','courier_id','time_create', 'verify_id'));

            if(!empty($Data)){
                $ListProvinceId = [];

                if(!empty($this->list_province)){
                    $ListProvinceId = array_unique($this->list_province);
                }else{
                    $Address       = [];
                    $ListToAddress = [];
                    foreach($Data as $val){
                        $ListToAddress[]    = (int)$val['to_address_id'];
                    }
                    $ListToAddress      = array_unique($ListToAddress);

                    if(!empty($ListToAddress)){
                        $AddressModel   = new AddressModel;
                        $ListAddress    = $AddressModel::whereIn('id',$ListToAddress)->get()->toArray();
                        if(!empty($ListAddress)){
                            foreach($ListAddress as $val){
                                $Address[(int)$val['id']]    = $val;
                                $ListProvinceId[]   = (int)$val['province_id'];
                            }
                        }
                    }
                }
                
                if(!empty($ListProvinceId)){
                    $District = $this->getProvince($ListProvinceId);
                }
            }
        }

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'total'         => $Total,
            'data'          => $Data,
            'district'      => $District,
            'address'       => isset($Address) ? $Address : $this->address
        );

        return Response::json($contents);
    }

    /**
    * Sellercentral Order Process
    * @desc : Lấy tổng số vận đơn cần xử lý theo group 
    *  
    */


    public function getGroupOrderProcess (){
        $TimeStart          = Input::has('time_start')          ? (int)Input::get('time_start')                 : 0; //  7 day  - 14 day  -  30day  - 90 day
        $TimeCreateStart    = Input::has('time_create_start')   ? (int)Input::get('time_create_start')          : 0; // time_create start   time_stamp
        $TimeCreateEnd      = Input::has('time_create_end')     ? (int)Input::get('time_create_end')            : 0; // time_create end
        $TimeAcceptStart    = Input::has('time_accept_start')   ? (int)Input::get('time_accept_start')          : 0; // time_accept start
        $TimeAcceptEnd      = Input::has('time_accept_end')     ? (int)Input::get('time_accept_end')            : 0; // time_accept end
        $cmd                = Input::has('cmd')                 ? (string)Input::get('cmd')                     : "";
        $ByVip              = Input::has('by_vip')              ? trim(Input::get('by_vip'))                    : '';

        $UserInfo   = $this->UserInfo();
        $id = (int)$UserInfo['id'];

        $totalOverWeight = 0;

        $Model          = new ElasticBuilder('bxm_orders', 'orders');

        if(!empty($ByVip)){
            $_userInfo      = new UserInfoModel;
            

            $listUserVip    = $_userInfo->where('is_vip', 1)->select('user_id')->get()->toArray();
            if($listUserVip){
                $listUserVip = array_map(function ($value){
                    return $value['user_id'];
                }, $listUserVip);

                $Model          = $Model->whereIn('from_user_id', $listUserVip);
            }
        }else {
            if(!empty($id)){
                $Model          = $Model->where('from_user_id',$id);
            }    
        }

        
        

        if($TimeStart > 0){
            $TimeCreateStart    = time() - $TimeStart * 86400;
        }

        if($TimeCreateStart > 0){
            $Model          = $Model->where('time_create','gte',$TimeCreateStart);
        }else{
            $Model          = $Model->where('time_create','gte',strtotime(date('Y-m-1 00:00:00')));
        }

        if($TimeCreateEnd > 0){
            $Model          = $Model->where('time_create','lte',$TimeCreateEnd);
        }

        if($TimeAcceptStart > 0){
            $Model          = $Model->where('time_accept','gte',$TimeAcceptStart);

        }

        if($TimeAcceptEnd > 0){
            $Model          = $Model->where('time_accept','lte',$TimeAcceptEnd);
        }


        $ModelOverWeight    = clone $Model;
        $ListOverWeightId   = $ModelOverWeight->where('accept_overweight', 0)->where('verify_id', 0)->where('time_update', 'gte', time() - 172800)->lists('id');
        $totalOverWeight    = 0;


        if(!empty($ListOverWeightId)){
            $OrderDetail = new DetailModel;
            $OrderDetail = $OrderDetail->whereRaw("order_id in (". implode(",", $ListOverWeightId) .")")->get()->toArray();
            foreach ($OrderDetail as $key => $value) {
                if($value['sc_pvk'] != 0){
                    $totalOverWeight ++;
                }
            }

        }

        
        if (Cache::has('list_status_process_cache')){
            $ListStatusOrder = Cache::get('list_status_process_cache');
        }else{
            $ListStatus            = array(41, 20, 15);
            $GroupOrderStatusModel = new GroupOrderStatusModel;
            $ListStatusOrder       = $GroupOrderStatusModel::whereIn('group_status',$ListStatus)->get(array('group_status', 'order_status_code'))->toArray();

            Cache::put('list_status_process_cache', $ListStatusOrder, 10);
        }

        $ListStatus = [0];
        
        if(!empty($ListStatusOrder)){
            foreach($ListStatusOrder as $val){
                // bỏ trạng thái HVC báo hủy
                if($val['order_status_code'] != 78){
                    $ListStatus[]   = (int)$val['order_status_code'];
                }
            }
        }


        $Model           = $Model->whereIn('status',$ListStatus);

        $DataGroup       = $Model->get(array('id', 'status'));


        $ListGroupStatus = [];
        $ListOrderId     = [];

        $ListOrderId = array_map(function($value){
            return $value['id'];
        }, $DataGroup);

        if (!empty($ListOrderId)) {
            $PipeJourney    = PipeJourneyModel::whereIn('tracking_code', $ListOrderId)->orderBy('time_create', 'ASC')->get()->toArray();

            foreach ($DataGroup as $key => $value) {
                $DataGroup[$key]['pipe_journey'] = [];
                foreach ($PipeJourney as $k => $val) {
                    if($value['id'] == $val['tracking_code']){
                        $DataGroup[$key]['pipe_journey'][] = $val;
                    }
                }
            }
            
        }


        

        $GROUPING = [];
        $GROUPING['CONFIRM_DELIVERED'] = 0;
        $Total = 0;
        foreach ($ListStatusOrder as $value) {

            foreach ($DataGroup as $val) {
                if($value['order_status_code'] == $val['status']){

                    if(!isset($GROUPING[(int)$value['group_status']])){
                        $GROUPING[(int)$value['group_status']] = 0;
                    }
                    
                    $GROUPING[(int)$value['group_status']] ++;
                    $Total ++;

                    if($this->hasPipe($val['pipe_journey'], 707) && $value['group_status'] == 41){
                        $GROUPING[(int)$value['group_status']] --;
                        $GROUPING['CONFIRM_DELIVERED'] ++;
                        $Total--;

                    }else if($this->hasPipe($val['pipe_journey'], 903) && $value['group_status'] == 15){
                        $GROUPING[(int)$value['group_status']] --;
                        $GROUPING['CONFIRM_DELIVERED'] ++;
                        $Total--;
                    }
                }
            }
        }

        if($cmd && $cmd == 'json'){
            return Response::json(array(
                "error"     => false,
                "total"     => $Total + $totalOverWeight,
                "data"      => ['total_group' => $GROUPING, 'total_over_weight'=> $totalOverWeight],
                "message"   => "" 
            ));
        }        
        return ['total_group' => $GROUPING, 'total_over_weight'=> $totalOverWeight];
    }

    private function hasPipe($pipe_journey, $status){
        foreach ($pipe_journey as $key => $item) {
            if($item['pipe_status'] == $status){
                return true;
            }
        }
        return false;
    }

    /**
    *  Sellercentral Order Process
    *  @desc   : Lấy các vân đơn cần xử lý (giao không thành công / chờ xn chuyển hoàn / vượt cân )
    *  @author : ThinhNV
    */

    
    public function getOrderProcess(){
        $page       = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $itemPage   = Input::has('limit')               ? (int)Input::get('limit')                      : 20;
        $Cmd        = Input::has('cmd')                 ? trim(Input::get('cmd'))                       : '';
        $Option     = Input::has('tab_option')          ? trim(Input::get('tab_option'))                : '';
        $ListStatus = Input::has('list_status')         ? Input::get('list_status')                    : '';
        $OverWeight = Input::has('over_weight')         ? Input::get('over_weight')                    : '';
        
        $Model      = $this->GetModel();


        

        $Total      = 0;

        $Data       = [];
        $District   = [];
        $GroupOrder = $this->getGroupOrderProcess();

        $ModelTotal = clone $Model;
        $Total      = $ModelTotal->count();

        if($Total > 0 || $Cmd == 'export' ){
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
            $Data       = $Model->orderBy('time_create','DESC')
                                ->with(['OrderDetail', 'MetaStatus', 'OrderProcess', 'ToOrderAddress']);

            if($Option && $Option == 'CONFIRM_DELIVERED'){
                 $_Model = clone $Data;
                 $_ListId = $_Model->lists('orders.id');

                 $_Pipe = PipeJourneyModel::whereIn('tracking_code', $_ListId)->whereIn('pipe_status', [707, 903])->lists('tracking_code');

                 if($_Pipe){
                    $Data = $Data->whereIn('id', $_Pipe);
                }else {
                    $contents = array(
                        'error'                 => false,
                        'message'               => 'success',
                        'total'                 => $Total,
                        'total_group'           => $GroupOrder['total_group'],
                        'total_over_weight'     => $GroupOrder['total_over_weight'],
                        'data'                  => [],
                        'district'              => [],
                        'address'               => [],
                        'status'                => []
                    );

                    return Response::json($contents);
                }

                 

            }
            $Data = $Data->with(['pipe_journey']);
            
            if($Cmd == 'export'){
                $Filename = "Don_Hang_Can_XL";

                if ($ListStatus == 41) {
                    $Filename .= "_Phat_Khong_Thanh_Cong";
                }else if ($ListStatus == 20) {
                    $Filename .= "_Cho_XN_Chuyen_Hoan";
                }else if($ListStatus == 15){
                    $Filename .= "_Lay_Khong_Thanh_Cong";
                }else if(!empty($OverWeight)){
                    $Filename .= "_Vuot_Can";
                }

                return $this->ExportExcelProcess($Data->get(
                    array(
                        'orders.id',
                        'tracking_code',
                        'status',
                        'to_name',
                        'to_phone',
                        'to_email',
                        'total_amount',
                        'total_weight',
                        'over_weight',
                        'to_address_id',
                        'product_name',
                        'courier_id',
                        'time_create', 
                        'time_update', 
                        'verify_id', 
                        'from_address', 
                        'from_district_id', 
                        'from_ward_id', 
                        'from_user_id', 
                        'time_accept', 
                        'time_pickup', 
                        'time_success',
                        'courier_tracking_code',
                        'service_id',
                        'from_city_id',
                        'order_code',
                        'num_delivery',
                        'from_address_id'

                ))->ToArray(), $Filename);
            }
            // 'id','tracking_code', 'status','to_name','to_phone','to_email','total_amount','total_weight','to_address_id', 'product_name','courier_id','time_create', 'verify_id'
            $Data = $Data->skip($offset)
                        ->take($itemPage)
                        ->get(array('orders.id','tracking_code', 'status','to_name','to_phone','to_email','total_amount','total_weight', 'over_weight',
                                    'to_address_id', 'product_name','courier_id','time_create', 'time_update', 'verify_id', 'from_address', 'num_delivery'));


            if(!empty($Data)){
                $ListProvinceId = [];

                if(!empty($this->list_province)){
                    $ListProvinceId = array_unique($this->list_province);
                }else{
                    $Address       = [];
                    $ListToAddress = [];
                    foreach($Data as $val){
                        $ListToAddress[]    = (int)$val['to_address_id'];
                    }
                    $ListToAddress      = array_unique($ListToAddress);

                    if(!empty($ListToAddress)){
                        $AddressModel   = new AddressModel;
                        $ListAddress    = $AddressModel::whereIn('id',$ListToAddress)->get()->toArray();
                        if(!empty($ListAddress)){
                            foreach($ListAddress as $val){
                                $Address[(int)$val['id']]    = $val;
                                $ListProvinceId[]   = (int)$val['province_id'];
                            }
                        }
                    }

                }

                if(!empty($ListProvinceId)){
                    $District = $this->getProvince($ListProvinceId);
                }
            }
        }



        $contents = array(
            'error'                 => false,
            'message'               => 'success',
            'total'                 => $Total,
            'total_group'           => $GroupOrder['total_group'],
            'total_over_weight'     => $GroupOrder['total_over_weight'],
            'data'                  => $Data,
            'district'              => $District,
            'address'               => isset($Address) ? $Address : $this->address,
            'status'                => $this->getStatus()
        );

        return Response::json($contents);
    }





    public function getCountGroup($json = false){
        $StatusOrderCtrl        = new StatusOrderCtrl;
        $Group                  = [];
        $ListGroupStatus        = [];

        $ListGroup  = $StatusOrderCtrl->getStatusgroup(false);

        if(!empty($ListGroup)) {
            foreach($ListGroup as $val){
                foreach($val['group_order_status'] as $v) {
                    $this->list_status[$val['id']][]    = (int)$v['order_status_code'];
                    $ListStatus[]       = (int)$v['order_status_code'];
                    $ListGroupStatus[(int)$v['order_status_code']]    = $v['group_status'];
                }
            }
        }

        if(!empty($ListStatus)) {
            $Model    = $this->GetModelES();

            $Data = $Model->groupBy('status')->get();

            foreach ($Data['status'] as $key => $value) {

                if(!isset($Group[(int)$ListGroupStatus[(int)$value['key']]])){
                        $Group[(int)$ListGroupStatus[(int)$value['key']]]  = 0;
                    }
                $Group[(int)$ListGroupStatus[(int)$value['key']]] += $value['doc_count'];                

            }

        }

        return (boolean)$json ? Response::json(['error'=> false, 'data'=> $Group, 'error_message'=> '']) : $Group;

    }

    //Lading EMS
    public function getLadingmerchant()
    {
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Status             = Input::has('status')              ? strtoupper(trim(Input::get('status')))        : 'ALL';
        $itemPage           = Input::has('limit')               ? Input::get('limit')                           : 20;
        $Search             = Input::has('search')              ? trim(Input::get('search'))                    : '';
        $TimeCreateStart    = Input::has('time_create_start')   ? (int)Input::get('time_create_start')          : 0; // time_create start   time_stamp
        $TimeCreateEnd      = Input::has('time_create_end')     ? (int)Input::get('time_create_end')            : 0; // time_create end
        $TimeAcceptStart    = Input::has('time_accept_start')   ? (int)Input::get('time_accept_start')         : 0; // time_accept start
        $TimeAcceptEnd      = Input::has('time_accept_end')     ? (int)Input::get('time_accept_end')                   : 0; // time_accept end
        $UserId             = Input::has('user_id')             ? (int)Input::get('user_id')                    : 0;
        $ToCity             = Input::has('to_city')             ? (int)Input::get('to_city')                    : 0;
        $ToDistrict         = Input::has('to_district')         ? (int)Input::get('to_district')                : 0;
        $FromCity           = Input::has('from_city')           ? (int)Input::get('from_city')                  : 0;
        $FromDistrict       = Input::has('from_district')       ? (int)Input::get('from_district')              : 0;
        $Domain             = Input::has('domain')              ? Input::get('domain')                         : '';
        $FromPhone          = Input::has('from_phone')          ? Input::get('from_phone')                     : '';
        $CourierId          = Input::has('courier_id')          ? (int)Input::get('courier_id')                 : 0;
        $FromEmail          = Input::has('from_email')          ? trim(Input::get('from_email'))                    : '';

        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege'] == 0){
            $contents = array(
                'error'         => true,
                'message'       => 'Không có quyền !',
                'privilege'     => 0
            );
        }
        if($UserId > 0 && $UserInfo['privilege'] > 0){
            $id     = $UserId;
        }elseif($UserInfo['privilege'] == 0) {
            $id = $UserInfo['id'];
        }

        $Model                  = new GroupStatusModel;
        $ListGroup  = $Model::where('group', 3)->with(array('group_order_status'))->get(array('id','group'))->toArray();

        if(!empty($ListGroup)) {
            foreach($ListGroup as $val){
                foreach($val['group_order_status'] as $v) {
                    $this->list_status[$val['id']][]    = (int)$v['order_status_code'];
                    $ListStatus[]       = (int)$v['order_status_code'];
                }
            }
        }

        $Model          = OrdersModel::query();
        if($TimeAcceptStart > 0){
            $TimeCreateStart = 0;
            $Model          = $Model->where('time_accept','>=',$TimeAcceptStart);
        }
        if($TimeAcceptEnd > 0){
            $TimeCreateEnd = 0;
            $Model          = $Model->where('time_accept','<',$TimeAcceptEnd);
        }

        if(!empty($ListStatus)) {
            $Model          =   $Model->whereIn('status',$ListStatus);
        }
        if(!empty($id)){
            $Model          = $Model->where('from_user_id',$id);
        }

        if($CourierId > 0){
            $Model          = $Model->where('courier_id',$CourierId);
        }
        if(!empty($UserInfo['courier'])){
            if($UserInfo['courier'] == 'ems'){
                $Model = $Model->where('courier_id',8);
            }elseif($UserInfo['courier'] == 'emshn'){
                $Model = $Model->where('courier_id',8)->where('from_city_id',18);
            }elseif($UserInfo['courier'] == 'emshcm'){
                $Model = $Model->where('courier_id',8)->where('from_city_id',52);
            }elseif($UserInfo['courier'] == 'gts'){
                $Model = $Model->where('courier_id',9);
            }
        }
       
        //lamido
        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','lamido.vn');
        }
        //chodientu
        if($UserInfo['id'] == 2956 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','chodientu.vn');
        }

        if(!empty($Search)){
            if (filter_var($Search, FILTER_VALIDATE_EMAIL)){  // search email
                $Model          = $Model->where('to_email',$Search);
            }elseif(filter_var((int)$Search, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $Model          = $Model->where('to_phone',$Search);
            }else{ // search code
                $Model          = $Model->where('tracking_code',$Search);
            }
        }

        if(!empty($FromEmail)){
            $InfoFromEmail = \User::where('email',$FromEmail)->first();

            if(!empty($InfoFromEmail)){
                $Model          = $Model->where('from_user_id',$InfoFromEmail['id']);
            }
        }

        if(!empty($FromPhone)){
            $InfoFromPhone = \User::where('phone',$FromPhone)->first();
            if(!empty($InfoFromPhone)){
                $Model          = $Model->where('from_user_id',$InfoFromPhone['id']);
            }
        }

        if($Domain != ''){
            $Model = $Model->where('domain',$Domain);
        }

        if(
        $TimeCreateStart > 0){
            $Model          = $Model->where('time_create','>=',$TimeCreateStart);
        }
        if($TimeCreateEnd > 0){
            $Model          = $Model->where('time_create','<',$TimeCreateEnd);
        }

        if($CourierId > 0){
            $Model = $Model->where('courier_id',$CourierId);
        }

        if($ToCity > 0){
            $Model = $Model->whereHas('ToOrderAddress', function($q) use($ToCity){
                $q->where('city_id',$ToCity);
            });
        }
        if($ToDistrict > 0){
            $Model = $Model->whereHas('ToOrderAddress', function($q) use($ToDistrict){
                $q->where('province_id',$ToDistrict);
            });
        }
        if($FromCity > 0){
            $Model = $Model->where('from_city_id',$FromCity);
        }
        if($FromDistrict > 0){
            $Model = $Model->where('from_district_id',$FromDistrict);
        }

        // total group
        $CountGroupStatus       = $this->getCountGroupMerchant();

        $TotalAll       = 0;
        $Total          = 0;
        if(!empty($CountGroupStatus)){
            foreach($CountGroupStatus as $val){
                $TotalAll                  += (int)$val;
            }
        }

        // getdata
        if($TotalAll > 0){
            if($Status != 'ALL'){
                if(isset($this->list_status[$Status]) && !empty($this->list_status[$Status]) && isset($CountGroupStatus[$Status]) && $CountGroupStatus[$Status] > 0){
                    $Model = $Model->whereIn('status',$this->list_status[$Status]);
                }else{
                    return Response::json([
                        'error'         => false,
                        'message'       => 'success',
                        'total'         => $Total,
                        'total_all'     => $TotalAll,
                        'total_group'   => $CountGroupStatus,
                        'data'          => []
                    ]);
                }
            }
    
            if($Status == 'ALL'){
                $Total  = $TotalAll;
            }else{
                $Total  = $CountGroupStatus[$Status];
            }

            $Model      = $Model->orderBy('time_create','DESC')->with(
                array(
                    'MetaStatus'   => function($query){
                        $query->with(array('group_order_status'));
                },
                    'OrderDetail'
                ,
                    'ToOrderAddress'  => function($query){
                        $query->with(array(
                            'City',
                            'District'
                        ));
                    }
                ));

            if($itemPage != 'all'){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }

            $Data       = $Model->orderBy('time_create','DESC')->get(array('id','tracking_code','courier_tracking_code','courier_id','status','to_name','to_phone','to_email','total_amount','total_weight','to_address_id', 'product_name','courier_id','service_id', 'verify_id','time_create','time_accept','from_user_id','time_approve'))->toArray();

            $ReturnData = array();
            if(!empty($Data)){
                $listSellerId = array();
                foreach($Data AS $value){
                    $listSellerId[] = $value['from_user_id'];
                }
                //
                $arrInfoSeller = \User::whereIn('id',$listSellerId)->get(array('id','fullname','email','phone','city_id','district_id'))->toArray();
                if(!empty($arrInfoSeller)){
                    foreach($Data AS $val){
                        foreach($arrInfoSeller AS $one){
                            if($val['from_user_id'] == $one['id']){
                                $val['from_email'] = $one['email'];
                                $val['from_name'] = $one['fullname'];
                                $val['from_phone'] = $one['phone'];
                                $ReturnData[] = $val;
                            }
                        }
                    }
                }
            
        
                $listCourier = array();
                if (Cache::has('courier_cache')){
                    $listCourier    = Cache::get('courier_cache');
                }else{
                    $courier        = new CourierModel;
                    $listCourier    = $courier::all(array('id','name'));
                }
                if(!empty($listCourier)){
                    foreach($listCourier as $val){
                        $LCourier[(int)$val['id']]   = $val['name'];
                    }
                    foreach($ReturnData as $key => $val){
                        if (isset($LCourier[(int)$val['courier_id']])){
                            $ReturnData[$key]['courier_name'] = $LCourier[(int)$val['courier_id']];
                        }
                    }
                }
                $contents = array(
                    'error'         => false,
                    'message'       => 'success',
                    'total'         => $Total,
                    'total_all'     => $TotalAll,
                    'total_group'   => $CountGroupStatus,
                    'data'          => $ReturnData,
                    'item_page'     => $itemPage,
                    'privilege'     => $UserInfo['privilege'],
                    'status'        => $Status
                );
            }else{
                $contents = array(
                    'error'         => true,
                    'message'       => 'Không có vận đơn !',
                    'privilege'     => $UserInfo['privilege'],
                    'total'         => 0
                );
            }
        }else{
            $contents = array(
                'error'         => true,
                'message'       => 'Không có vận đơn !',
                'privilege'     => $UserInfo['privilege'],
                'total'         => 0
            );
        }
        
        return Response::json($contents);
    }
    //
    public function getExportladingexcel(){
        $Status             = Input::has('status')              ? strtoupper(trim(Input::get('status')))        : 'ALL';
        $Search             = Input::has('search')              ? trim(Input::get('search'))                    : '';
        $TimeStart          = Input::has('time_start')          ? (int)Input::get('time_start')                 : 0; //  7 day  - 14 day  -  30day  - 90 day
        $TimeCreateStart    = Input::has('time_create_start')   ? (int)Input::get('time_create_start')          : 0; // time_create start   time_stamp
        $TimeCreateEnd      = Input::has('time_create_end')     ? (int)Input::get('time_create_end')            : 0; // time_create end
        $TimeAcceptStart    = Input::has('time_accept_start')   ? (int)Input::get('time_accept_start')          : 0; // time_accept start
        $TimeAcceptEnd      = Input::has('time_accept_end')     ? (int)Input::get('time_accept_end')            : 0; // time_accept end
        $UserId             = Input::has('user_id')             ? (int)Input::get('user_id')                    : 0;
        $ToCity             = Input::has('to_city')             ? (int)Input::get('to_city')                    : 0;
        $ToDistrict         = Input::has('to_district')         ? (int)Input::get('to_district')                : 0;
        $FromCity           = Input::has('from_city')           ? (int)Input::get('from_city')                  : 0;
        $FromDistrict       = Input::has('from_district')       ? (int)Input::get('from_district')              : 0;
        $CourierId          = Input::has('courier_id')          ? (int)Input::get('courier_id')                 : 0;
        $FromEmail          = Input::has('from_email')          ? trim(Input::get('from_email'))                : '';
        $Domain             = Input::has('domain')              ? Input::get('domain')                          : '';
        $FromPhone          = Input::has('from_phone')          ? Input::get('from_phone')                      : '';
        
        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege'] == 0){
            $contents = array(
                'error'         => true,
                'message'       => 'Không có quyền !',
                'privilege'     => 0
            );
        }
        if($UserId > 0 && $UserInfo['privilege'] > 0){
            $id     = $UserId;
        }elseif($UserInfo['privilege'] == 0) {
            $id = $UserInfo['id'];
        }

        $Model          = new OrdersModel;

        if(!empty($id)){
            $Model          = $Model::where('from_user_id',$id);
        }

        if($CourierId > 0){
            $Model          = $Model->where('courier_id',$CourierId);
        }
        if(!empty($UserInfo['courier'])){
            if($UserInfo['courier'] == 'ems'){
                $Model = $Model->where('courier_id',8);
            }elseif($UserInfo['courier'] == 'emshn'){
                $Model = $Model->where('courier_id',8)->where('from_city_id',18);
            }elseif($UserInfo['courier'] == 'emshcm'){
                $Model = $Model->where('courier_id',8)->where('from_city_id',52);
            }elseif($UserInfo['courier'] == 'gts'){
                $Model = $Model->where('courier_id',9);
            }
        }
        //lamido
        if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','lamido.vn');
        }
        //chodientu
        if($UserInfo['id'] == 2956 && $UserInfo['privilege'] == 3){
            $Model = $Model->where('domain','chodientu.vn');
        }

        if(!empty($Search)){
            if (filter_var($Search, FILTER_VALIDATE_EMAIL)){  // search email
                $Model          = $Model->where('to_email',$Search);
            }elseif(filter_var((int)$Search, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $Model          = $Model->where('to_phone',$Search);
            }else{ // search code
                $Model          = $Model->where('tracking_code',$Search);
            }
        }

        if(!empty($FromEmail)){
            $InfoFromEmail = \User::where('email',$FromEmail)->first();
            if(!empty($InfoFromEmail)){
                $Model          = $Model->where('from_user_id',$InfoFromEmail['id']);
            }
        }
        if(!empty($FromPhone)){
            $InfoFromPhone = \User::where('phone',$FromPhone)->first();
            if(!empty($InfoFromPhone)){
                $Model          = $Model->where('from_user_id',$InfoFromPhone['id']);
            }
        }
        if($Domain != ''){
            $Model = $Model->where('domain',$Domain);
        }
        if($TimeAcceptStart > 0){
            $TimeCreateStart = 0;
            $Model          = $Model->where('time_accept','>=',$TimeAcceptStart);
        }
        if($TimeAcceptEnd > 0){
            $TimeCreateEnd = 0;
            $Model          = $Model->where('time_accept','<',$TimeAcceptEnd);
        }
        if($TimeCreateStart > 0){
            $Model          = $Model->where('time_create','>=',$TimeCreateStart);
        }
        if($TimeCreateEnd > 0){
            $Model          = $Model->where('time_create','<',$TimeCreateEnd);
        }

        if($CourierId > 0){
            $Model = $Model->where('courier_id',$CourierId);
        }

        if($ToCity > 0){
            $Model = $Model->whereHas('ToOrderAddress', function($q) use($ToCity){
                $q->where('city_id',$ToCity);
            });
        }
        if($ToDistrict > 0){
            $Model = $Model->whereHas('ToOrderAddress', function($q) use($ToDistrict){
                $q->where('province_id',$ToDistrict);
            });
        }
        if($FromCity > 0){
            $Model = $Model->where('from_city_id',$FromCity);
        }
        if($FromDistrict > 0){
            $Model = $Model->where('from_district_id',$FromDistrict);
        }

        // total group
        $CountGroupStatus       = $this->getCountGroupMerchant();

        $TotalAll       = 0;
        $Total          = 0;
        if(!empty($CountGroupStatus)){
            foreach($CountGroupStatus as $val){
                $TotalAll                  += (int)$val;
            }
        }

        // getdata
        $Data   = array();
        if($TotalAll > 0){
            if($Status != 'ALL'){
                if(!empty($this->list_status[$Status]) && $CountGroupStatus[$Status] > 0){
                    $Model = $Model->whereIn('status',$this->list_status[$Status]);
                }else{
                    return Response::json([
                        'error'         => false,
                        'message'       => 'success',
                        'total'         => $Total,
                        'total_all'     => $TotalAll,
                        'total_group'   => $CountGroupStatus,
                        'data'          => []
                    ]);
                }
            }
            
            $ModelTotal = $Model;

            if($Status == 'ALL'){
                $Total  = $TotalAll;
            }else{
                $Total  = $CountGroupStatus[$Status];
            }

            $Model      = $Model->orderBy('time_create','DESC')->with(
                array(
                    'MetaStatus'   => function($query){
                        $query->with(array('group_order_status'));
                },
                    'OrderDetail'
                ,
                    'ToOrderAddress'  => function($query){
                        $query->with(array(
                            'City',
                            'District'
                        ));
                    }
                ));

            
            $ReturnData = array();
            $Data       = $Model->orderBy('time_create','DESC')->get(array('id','tracking_code','courier_tracking_code','courier_id','status','to_name','to_phone','to_email','total_amount','total_weight','to_address_id', 'product_name','courier_id','service_id', 'verify_id','time_create','time_accept','from_user_id','time_approve'))->toArray();
            if(!empty($Data)){
                $listSellerId = array();
                foreach($Data AS $value){
                    $listSellerId[] = $value['from_user_id'];
                }
                //
                $arrInfoSeller = \User::whereIn('id',$listSellerId)->get(array('id','fullname','email','phone','city_id','district_id'))->toArray();
                if(!empty($arrInfoSeller)){
                    $ReturnData = array();
                    foreach($Data AS $val){
                        foreach($arrInfoSeller AS $one){
                            if($val['from_user_id'] == $one['id']){
                                $val['from_email'] = $one['email'];
                                $val['from_name'] = $one['fullname'];
                                $val['from_phone'] = $one['phone'];
                                $ReturnData[] = $val;
                            }
                        }
                    }
                }
            }
        
            $listCourier = array();
            if (Cache::has('courier_cache')){
                $listCourier    = Cache::get('courier_cache');
            }else{
                $courier        = new CourierModel;
                $listCourier    = $courier::all(array('id','name'));
            }
            if(!empty($listCourier)){
                foreach($listCourier as $val){
                    $LCourier[(int)$val['id']]   = $val['name'];
                }
                foreach($ReturnData as $key => $val){
                    if (isset($LCourier[(int)$val['courier_id']])){
                        $ReturnData[$key]['courier_name'] = $LCourier[(int)$val['courier_id']];
                    }
                }
            }
        


            $Data =  $ReturnData;
            
            return Excel::create('Danh_sach_van_đon', function ($excel) use($Data) {
                $excel->sheet('Vận đơn', function ($sheet) use($Data) {
                    $sheet->mergeCells('E1:J1');
                    $sheet->row(1, function ($row) {
                        $row->setFontSize(20);
                    });
                    $sheet->row(1, array('','','','','Danh sách vận đơn'));
                    // set width column
                    $sheet->setWidth(array(
                        'A'     => 5,
                        'B'     => 20,
                        'C'     => 25,
                        'D'     => 15,
                        'E'     => 40,
                        'F'     => 30,
                        'G'     => 20,
                        'H'     => 30,
                        'I'     => 30,
                        'J'     => 30,
                        'K'     => 30,
                        'L'     => 30,
                        'M'     => 30,
                        'N'     => 30,
                        'O'     => 30
                    ));
                    // set content row
                    $sheet->row(3, array(
                         'STT', 
                         'Mã vận đơn', 
                         'Mã hãng vận chuyển', 
                         'Người nhận', 
                         'Số điện thoại nhận', 
                         'Thành phố', 
                         'Quận huyện', 
                         'Địa chỉ', 
                         'Trạng thái', 
                         'Phí vân chuyểm',
                         'Phí thu hộ',
                         'Tổng tiền thu hộ',
                         'Thời gian tạo', 
                         'Thời gian duyệt'
                         /*'Thời gian lấy hàng',*/

                         
                    ));
                    $sheet->row(3,function($row){
                        $row->setBackground('#B6B8BA');
                        $row->setBorder('solid','solid','solid','solid');
                        $row->setFontSize(12);
                    });
                    //
                    $i = 1;

                    foreach ($Data AS $value) {
                        
                        $dataExport = array(
                            'STT' => $i++,
                            'Mã vận đơn' => $value['tracking_code'],
                            'Mã hãng vận chuyển' => $value['courier_tracking_code'],
                            'Người nhận'    => $value['to_name'],
                            'Số điện thoại nhận'    => $value['to_phone'],
                            'Thành phố'  => $value['to_order_address']['city']['city_name'],
                            'Quận huyện' => $value['to_order_address']['district']['district_name'],
                            'Địa chỉ'    => $value['to_order_address']['address'],
                            'Trạng thái' => $value['meta_status']['name'],
                            'Phí vận chuyển' => $value['order_detail']['seller_pvc'],
                            'Phí thu hộ' => $value['order_detail']['seller_cod'],
                            'Tổng tiền thu hộ' => $value['order_detail']['money_collect'],
                            'Thời gian tạo' => date("d/M/y H:m",$value['time_create']),
                            'Thời gian duyệt' => date("d/M/y H:m",$value['time_accept'])
                            /*'Thời gian lấy hàng' => date("d/M/y H:m",$value['time_pickup']),*/
                            
                        );
                        $sheet->appendRow($dataExport);
                    }
                });
            })->export('xls');
            
            
        }
    }
    
    public function getOrderfee(){
        $page           = Input::has('page')            ? (int)Input::get('page')                       : 1;
        $Courier        = Input::has('courier')         ? strtoupper(trim(Input::get('courier')))       : '';
        $TrackingCode   = Input::has('tracking_code')   ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $ListStatus     = Input::has('list_status')     ? Input::get('list_status')                     : '';
        $itemPage       = Input::has('limit')           ? Input::get('limit')                           : 20;

        $TimeAcceptStart    = Input::has('time_accept_start')   ? (int)Input::get('time_accept_start')  : 0;
        $TimeAcceptEnd      = Input::has('time_accept_end')     ? (int)Input::get('time_accept_end')    : 0;
        $TimePickupStart    = Input::has('time_pickup_start')   ? (int)Input::get('time_pickup_start')  : 0;
        $TimePickupEnd      = Input::has('time_pickup_end')     ? (int)Input::get('time_pickup_end')    : 0;
        $TimeSuccessStart   = Input::has('time_success_start')  ? (int)Input::get('time_success_start') : 0;
        $TimeSuccessEnd     = Input::has('time_success_end')    ? (int)Input::get('time_success_end')   : 0;

        $Service            = Input::has('service')             ? (int)Input::get('service')            : 0;
        $Type               = Input::has('type')                ? (int)Input::get('type')               : 0;
        $CourierId          = Input::has('courier_id')          ? (int)Input::get('courier_id')         : 0;
        
        $KeyWord            = Input::has('keyword')             ? trim(Input::get('keyword'))           : '';  // user
        $Domain             = Input::has('domain')              ? trim(Input::get('domain'))            : '';
        $VerifyMoneyCollect = Input::has('verify_money_collect')     ? (int)Input::get('verify_money_collect')         : 0;

        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))               : '';
        $Model              = new OrdersModel;

        $Data           = array();
        $DataSum        = array();
        $Total          = 0;
        $TotalAmount    = 0;
        $TotalGroup     = [];

        if(!empty($TimeAcceptStart)){
            $Model          = $Model->where('time_accept','>=',$TimeAcceptStart);
        }else{
            $Model          = $Model->where('time_accept','>=',strtotime(date('Y-m-1 00:00:00')));
        }
        
        if(!empty($TimeAcceptEnd)){
            $Model          = $Model->where('time_accept','<=',$TimeAcceptEnd);
        }

        if(!empty($TimePickupStart)){
            $Model          = $Model->where('time_pickup','>=',$TimePickupStart);
        }

        if(!empty($TimePickupEnd)){
            $Model          = $Model->where('time_pickup','<=',$TimePickupEnd);
        }
        
        if(!empty($TimeSuccessStart)){
            $Model          = $Model->where('time_success','>=',$TimeSuccessStart);
        }
        
        if(!empty($TimeSuccessEnd)){
            $Model          = $Model->where('time_success','<=',$TimeSuccessEnd);
        }
        
        if(!empty($TrackingCode)){
            $Model          = $Model->where(function($query) use($TrackingCode){
                $query->where('tracking_code', $TrackingCode)
                      ->orWhere('courier_tracking_code', $TrackingCode);
            });
        }

        if(!empty($Service)){
            $Model          = $Model->where('service_id',$Service);
        }

        if(!empty($VerifyMoneyCollect)){
            if($VerifyMoneyCollect == 1){
                $Model          = $Model->where('verify_money_collect', '>',0);
            }else{
                $Model          = $Model->where('verify_money_collect', 0);
            }
        }

        if(!empty($Domain)){
            $Model          = $Model->where('domain',$Domain);
        }

        if(!empty($ListStatus)){
            $ListStatus = explode(',',$ListStatus);
            $GroupOrderStatusModel  = new GroupOrderStatusModel;
            $ListStatusOrder = $GroupOrderStatusModel::whereIn('group_status',$ListStatus)->get(array('group_status', 'order_status_code'))->toArray();
            $ListStatus = [0];
            if(!empty($ListStatusOrder)){
                foreach($ListStatusOrder as $val){
                    $ListStatus[]   = (int)$val['order_status_code'];
                }
            }

            $Model          = $Model->whereIn('status',$ListStatus);
        }
        
        // search
        if(!empty($KeyWord)){
            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email 
                $FieldUser  = 'email';
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $FieldUser  = 'phone';
            }else{ // search fullname
                $FieldUser  = 'fullname';
            }
            
            $User   = \User::where($FieldUser,$KeyWord)->get(array('id'))->toArray();
            if(!empty($User)){
                $ListUser = [];
                foreach($User as $val){
                    $ListUser[] = (int)$val['id'];
                }
                $Model          = $Model->whereIn('from_user_id',$ListUser);
            }
        }

        // Loại vận đơn CoD  hoặc ko CoD   order report
        if($Type == 1){ // CoD
            $Model = $Model->whereHas('OrderDetail',function($q){
                $q->where('money_collect','>',0);
            });
        }elseif($Type == 2){ // ko CoD
            $Model = $Model->whereHas('OrderDetail',function($q){
                $q->where('money_collect',0);
            });
        }

        if($Cmd == 'export'){
            if(!empty($Courier) && $Courier != 'ALL'){
                $Model  = $Model->where('courier_id',(int)$Courier);
            }

            if(!empty($CourierId)){
                $Model = $Model->where('courier_id',$CourierId);
            }
            return $this->ExportExcel($Model->with(['OrderDetail'])->get()->ToArray());
        }

        // get data


        // total group
        if(!empty($Courier)){  // provide
            $ModelGroup     = clone $Model;
            $GroupStatus    = $ModelGroup->groupBy('courier_id')->get(array('courier_id',DB::raw('count(*) as count, sum(total_amount) as total_amount')))->toArray();

            $TotalGroup     = [];
            $AmountGroup    = [];
            $TotalAll       = 0;
            $AmountAll      = 0;
            if(!empty($GroupStatus)){
                foreach($GroupStatus as $val){
                    $TotalGroup[$val['courier_id']]     = (int)$val['count'];
                    $TotalAll                          += (int)$val['count'];
                    $AmountGroup[$val['courier_id']]    = $val['total_amount'];
                    $AmountAll                         += $val['total_amount'];
                }
            }

            if($Courier != 'ALL'){
                $Total          = isset($TotalGroup[(int)$Courier]) ? $TotalGroup[(int)$Courier] : 0;
                $TotalAmount    = isset($AmountGroup[(int)$Courier]) ? $AmountGroup[(int)$Courier] : 0;
            }else{
                $Total          = $TotalAll;
                $TotalAmount    = $AmountAll;
            }

        }else{ // order
            $TotalAll       = 1;
        }

        if($TotalAll > 0){
            if(!empty($Courier) && $Courier != 'ALL'){
                $Model  = $Model->where('courier_id',(int)$Courier);
            }

            if(empty($Courier)){
                if(!empty($CourierId)){
                    $Model = $Model->where('courier_id',$CourierId);
                }

                $ModelTotal = clone $Model;
                $MTotal     = $ModelTotal->first(array(DB::raw('count(*) as count, sum(total_amount) as total_amount')));

                if($MTotal){
                    $Total          = (int)$MTotal['count'];
                    $TotalAmount    = (int)$MTotal['total_amount'];
                }
            }

            $Model      = $Model->orderBy('time_create','DESC');

            if($Total > 0){
                $ModelSum   = clone $Model;
                $DataSum = $ModelSum->get(array('id'))->toArray();
                $ListOrder = [];
                foreach ($DataSum as $val) {
                    $ListOrder[] = $val['id'];
                }
                $ListOrder      = array_unique($ListOrder);
                $DetailModel    = new DetailModel;
                $DataSum = $DetailModel->whereRaw("order_id in (". implode(",", $ListOrder) .")")
                    ->first(array(DB::raw(
                        'sum(sc_pvc) as sc_pvc,
                        sum(sc_cod) as sc_cod,
                        sum(sc_pbh) as sc_pbh,
                        sum(sc_pvk) as sc_pvk,
                        sum(sc_pch) as sc_pch,

                        sum(hvc_pvc) as hvc_pvc,
                        sum(hvc_cod) as hvc_cod,
                        sum(hvc_pbh) as hvc_pbh,
                        sum(hvc_pvk) as hvc_pvk,
                        sum(hvc_pch) as hvc_pch,

                        sum(sc_discount_pvc) as sc_discount_pvc,
                        sum(sc_discount_cod) as sc_discount_cod,
                        sum(money_collect) as money_collect
                        ')));

                if($itemPage != 'all'){
                    $itemPage   = (int)$itemPage;
                    $offset     = ($page - 1)*$itemPage;
                    $Model       = $Model->skip($offset)->take($itemPage);
                }
                
                $Data       = $Model->with(array('OrderDetail'))->orderBy('time_create','DESC')->get(array('id', 'tracking_code', 'total_amount', 'verify_money_collect', 'verify_fee', 'status', 'domain'))->toArray();
            }

        }

        $DataSum['total_amount']    = $TotalAmount;
        
        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'total'         => $Total,
            'total_all'     => $TotalAll,
            'total_group'   => $TotalGroup,
            'data_sum'      => $DataSum,
            'data'          => $Data
        );
        
        return Response::json($contents);
    }

    public function getCountGroupMerchant(){
        $Search     = Input::has('search')      ? trim(Input::get('search'))            : '';
        $TimeStart  = Input::has('time_start')  ? (int)Input::get('time_start')         : 0;
        $UserId     = Input::has('user_id')     ? (int)Input::get('user_id')            : 0;
        $group      = Input::has('group')       ? (int)Input::get('group')              : 1;
        $CourierId  = Input::has('courier_id')  ? (int)Input::get('courier_id')         : 0;
        $TimeCreateStart    = Input::has('time_create_start')   ? (int)Input::get('time_create_start')          : 0; // time_create start   time_stamp
        $TimeCreateEnd      = Input::has('time_create_end')     ? (int)Input::get('time_create_end')            : 0; // time_create end
        $TimeAcceptStart    = Input::has('time_accept_start')   ? (int)Input::get('time_accept_start')         : 0; // time_accept start
        $TimeAcceptEnd      = Input::has('time_accept_end')     ? (int)Input::get('time_accept_end')                   : 0; // time_accept end
        $ToCity             = Input::has('to_city')             ? (int)Input::get('to_city')                    : 0;
        $ToDistrict         = Input::has('to_district')         ? (int)Input::get('to_district')                : 0;
        $FromCity           = Input::has('from_city')           ? (int)Input::get('from_city')                  : 0;
        $FromDistrict       = Input::has('from_district')       ? (int)Input::get('from_district')              : 0;
        $CourierId          = Input::has('courier_id')          ? (int)Input::get('courier_id')                 : 0;
        $FromEmail          = Input::has('from_email')          ? trim(Input::get('from_email'))                    : '';
        $Domain             = Input::has('domain')              ? Input::get('domain')                         : '';
        $FromPhone          = Input::has('from_phone')          ? Input::get('from_phone')                     : '';

        $id = 0;
        $UserInfo   = $this->UserInfo();
        if($UserId > 0 && $UserInfo['privilege'] > 0){
            $id     = $UserId;
        }elseif($UserInfo['privilege'] == 0){
            $id = $UserInfo['id'];
        }

        $Model                  = new GroupStatusModel;
        $OrderModel             = new OrdersModel;
        $Group                  = [];
        $ListGroupStatus        = [];

        $ListGroup  = $Model::where('group', 3)->with(array('group_order_status'))->get(array('id','group'))->toArray();

        if(!empty($ListGroup)) {
            foreach($ListGroup as $val){
                foreach($val['group_order_status'] as $v) {
                    $this->list_status[$val['id']][]    = (int)$v['order_status_code'];
                    $ListStatus[]       = (int)$v['order_status_code'];
                    $ListGroupStatus[(int)$v['order_status_code']]    = $v['group_status'];
                }
            }
        }

        if(!empty($ListStatus)) {
            if($id > 0){
                $OrderModel  = $OrderModel::where('from_user_id',$id)->whereIn('status',$ListStatus);
            }
            if($UserInfo['id'] == 34626 && $UserInfo['privilege'] == 3){
                $OrderModel = $OrderModel->where('domain','lamido.vn');
            }
            
            //chodientu
            if($UserInfo['id'] == 2956 && $UserInfo['privilege'] == 3){
                $OrderModel = $OrderModel->where('domain','chodientu.vn');
            }

            if(!empty($Search)){
                if (filter_var($Search, FILTER_VALIDATE_EMAIL)){  // search email
                    $OrderModel          = $OrderModel->where('to_email',$Search);
                }elseif(filter_var((int)$Search, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                    $OrderModel          = $OrderModel->where('to_phone',$Search);
                }else{ // search code
                    $OrderModel          = $OrderModel->where('tracking_code',$Search);
                }
            }

            if($CourierId > 0){
                $OrderModel = $OrderModel->where('courier_id',$CourierId);
            }

            if(!empty($FromEmail)){
                $InfoFromEmail = \User::where('email',$FromEmail)->first();
                if(!empty($InfoFromEmail)){
                    $OrderModel          = $OrderModel->where('from_user_id',$InfoFromEmail['id']);
                }
            }
            if(!empty($FromPhone)){
                $InfoFromPhone = \User::where('phone',$FromPhone)->first();
                if(!empty($InfoFromPhone)){
                    $OrderModel          = $OrderModel->where('from_user_id',$InfoFromPhone['id']);
                }
            }
            if($Domain != ''){
                $OrderModel = $OrderModel->where('domain',$Domain);
            }
            if($TimeAcceptStart > 0){
                $TimeCreateStart = 0;
                $OrderModel          = $OrderModel->where('time_accept','>=',$TimeAcceptStart);
            }
            if($TimeAcceptEnd > 0){
                $TimeCreateEnd = 0;
                $OrderModel          = $OrderModel->where('time_accept','<',$TimeAcceptEnd);
            }
            if($TimeCreateStart > 0){
                $OrderModel          = $OrderModel->where('time_create','>=',$TimeCreateStart);
            }
            if($TimeCreateEnd > 0){
                $OrderModel          = $OrderModel->where('time_create','<',$TimeCreateEnd);
            }

            if($CourierId > 0){
                $OrderModel          = $OrderModel->where('courier_id',$CourierId);
            }
            if($FromCity > 0){
                $OrderModel = $OrderModel->where('from_city_id',$FromCity);
            }
            if($FromDistrict > 0){
                $OrderModel = $OrderModel->where('from_district_id',$FromDistrict);
            }
            $DataGroup  = $OrderModel->groupBy('status')->get(array('status',DB::raw('count(*) as count')));
            if(!empty($DataGroup)){
                foreach($DataGroup as $val){
                    if(isset($ListGroupStatus[(int)$val['status']])){
                        if(!isset($Group[(int)$ListGroupStatus[(int)$val['status']]])){
                            $Group[(int)$ListGroupStatus[(int)$val['status']]]  = 0;
                        }
                        $Group[(int)$ListGroupStatus[(int)$val['status']]] += $val['count'];
                    }
                }
            }
        }

        return $Group;
    }

    /**
     * @param $carrier_id
     * @param $location_id
     * @return array
     */
    public function ListDistrictArea($carrier_id,$location_id){
        if($carrier_id > 0 && $location_id > 0){
            $Model  = new \AreaLocationModel;
            $ListDistrictId     = $Model::whereHas('area',function($q) use($carrier_id){
                $q->where('courier_id',$carrier_id);
            })->where('location_id','>',$location_id)->get(array('province_id'))->toArray();

            if(!empty($ListDistrictId)){
                $DistrictId     = [];
                foreach($ListDistrictId as $val){
                    $DistrictId[]   = (int)$val['province_id'];
                }
                return $DistrictId;
            }
        }
        return array(0);
    }
    
    public function getPrintmulti($code, $userId = 0, $json = true){
        
        
        
        $ListCode   = explode(',', $code);
        if(empty($ListCode)){
            $contents = array(
                'error'     => true,
                'message'   => 'empty code',
                'data'      => []
            );
            return Response::json($contents);
        }
        
        $Model      = new OrdersModel;
        $Data       = [];
        $City       = [];
        $District   = [];
        $Ward       = [];
        $ReturnData = [];
        
        if(!empty($userId) && $userId > 0){
            $Model = $Model->where('from_user_id', $userId);
        }

        $Data       = $Model->whereIn('tracking_code',$ListCode)
                        ->where(function($query) {
                            $query->where('time_accept','>=', time() - $this->time_limit)
                                ->orWhere('time_accept',0);
                        })
            ->with(array(
                'OrderItem',
                'ToOrderAddress',
                'MetaStatus' => function ($q){
                    return $q->with(['group_order_status' => function ($q){
                        $q->with(['group_status_merchant']);
                    }]);
                },
                'Courier' => function ($q){
                    return $q->select('name', 'id');
                },
                'OrderStatus' => function ($query){
                    return $query->with('MetaStatus')->orderBy('time_create','DESC');
                },
                'OrderDetail',
            ))->get(array('id','tracking_code', 'checking', 'fragile','status','service_id','courier_id','total_weight', 'total_amount',
                'total_quantity','to_name','to_phone','to_email', 'product_name',
                'to_address_id','time_accept','time_create','time_pickup','time_update','estimate_delivery','verify_id','from_user_id', 'from_address_id', 'from_city_id',
                'from_district_id','from_ward_id','from_address', 'order_code','num_delivery'))
            ->toArray(); 

        if(!empty($Data)){
            foreach($Data as $key => $val){
                $Data[$key]['barcode']  = $this->getBarcode($val['tracking_code']);
                if((int)$val['from_city_id'] > 0){
                    $ListCityId[]       = (int)$val['from_city_id'];
                }

                if((int)$val['from_district_id'] > 0){
                    $ListDistrictId[]   = (int)$val['from_district_id'];
                }
                if((int)$val['from_user_id'] > 0){
                    $ListFromUserId[]   = (int)$val['from_user_id'];
                }

                if((int)$val['from_ward_id'] > 0){
                    $ListWardId[]       = (int)$val['from_ward_id'];
                }

                if(!empty($val['to_order_address'])){
                    if((int)$val['to_order_address']['city_id'] > 0){
                        $ListCityId[]       = (int)$val['to_order_address']['city_id'];
                    }

                    if((int)$val['to_order_address']['province_id'] > 0){
                        $ListDistrictId[]   = (int)$val['to_order_address']['province_id'];
                    }

                    if((int)$val['to_order_address']['ward_id'] > 0){
                        $ListWardId[]       = (int)$val['to_order_address']['ward_id'];
                    }
                }
            }

            if(!empty($ListCityId)){
                $CityModel  = new CityModel;
                $ListCityId = array_unique($ListCityId);
                $ListCity   = $CityModel->whereIn('id',$ListCityId)->get(array('id','city_name'))->toArray();
                if(!empty($ListCity)){
                    foreach($ListCity as $val){
                        $City[(int)$val['id']]  = $val['city_name'];
                    }
                }
            }
            if(!empty($ListFromUserId)){
                $ListFromUserId = array_unique($ListFromUserId);
                $ListFromUser   = \User::whereIn('id',$ListFromUserId)->get(array('id','fullname','email','phone'))->toArray();
                if(!empty($ListFromUser)){
                    foreach($Data AS $OneData){
                        foreach($ListFromUser AS $OneUser){
                            if((int)$OneUser['id'] == (int)$OneData['from_user_id']){
                                $OneData['from_email'] = $OneUser['email'];
                                $OneData['from_name']  = $OneUser['fullname'];
                                $OneData['from_phone'] = $OneUser['phone'];
                                $ReturnData[] = $OneData;
                            }
                        }
                    }
                }
            }

            if(!empty($ListDistrictId)){
                $DistrictModel  = new DistrictModel;
                $ListDistrictId = array_unique($ListDistrictId);
                $ListDistrict   = $DistrictModel->whereIn('id',$ListDistrictId)->get(array('id','district_name'))->toArray();
                if(!empty($ListDistrict)){
                    foreach($ListDistrict as $val){
                        $District[(int)$val['id']]  = $val['district_name'];
                    }
                }
            }

            if(!empty($ListWardId)){
                $WardModel  = new WardModel;
                $ListWardId = array_unique($ListWardId);
                $ListWard   = $WardModel->whereIn('id',$ListWardId)->get(array('id','ward_name'))->toArray();
                if(!empty($ListWard)){
                    foreach($ListWard as $val){
                        $Ward[(int)$val['id']]  = $val['ward_name'];
                    }
                }
            }
        }

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $ReturnData,
            'city'      => $City,
            'district'  => $District,
            'ward'      => $Ward
        );
        return $json ? Response::json($contents) : $contents;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public static function getShow($code)
    {
        $Model = new OrdersModel;
        $code  = strtoupper(trim($code));
        
        $Data = $Model->where('tracking_code',$code)->with(array(
                                'MetaStatus'   => function($query){
                                    $query->get(array('code','name'));
                                },
                                'OrderStatus'  => function($query){
                                    $query->with(array('MetaStatus' => function($q){
                                        $q->get(array('code','name'));
                                    }))->orderBy('time_create','DESC');
                                },
                                'Courier'       => function($query){
                                    $query->get(array('id','name','prefix'));
                                },
                                'Service'       => function($query){
                                    $query->get(array('id','name'));
                                },
                                'FromOrderAddress'  => function($query){
                                    $query->with(array('City' => function($q){
                                        $q->get(array('id','city_name'));
                                    }))->get(array('id','city_id'));
                                },
                                'ToOrderAddress'  => function($query){
                                    $query->with(array(
                                    'City' => function($q){
                                        $q->get(array('id','city_name'));
                                    },
                                    'District' => function($q){
                                        $q->get(array('id','district_name'));
                                    },
                                    'Ward'      => function($q){
                                        $q->get(array('id','ward_name'));
                                    }
                                    ))->get(array('id','city_id','province_id','ward_id','address'));
                                }
                                
        ))->first(array('id','tracking_code','service_id','courier_id','to_name','to_phone','to_email','status','from_address_id','to_address_id','time_create','time_accept'));
        
        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        );
        
        return Response::json($contents);
    }
    
    public function postUpload(){
        $Domain     = Input::has('domain')      ? strtolower(trim(Input::get('domain')))            : '';

        $UserInfo   = $this->UserInfo();
        if (Input::hasFile('file')) {
            $File       = Input::file('file');
            $name       = $File->getClientOriginalName();
            $extension  = $File->getClientOriginalExtension();
            $MimeType   = $File->getMimeType();
            
            if(in_array((string)$extension, array('csv','xls','xlsx')) && in_array((string)$MimeType,array('text/plain','application/vnd.ms-excel','application/vnd.ms-office','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))){

                $Upload = $this->CreateFolder(\Hash::make($UserInfo['email']));
                $uploadPath = $Upload['uploadPath'];
                $linkPath   = $Upload['linkPath'];

                $File->move($uploadPath, $name);

                $LMongo = new LMongo;
                $id = (string)$LMongo::collection('log_import_create_lading')->insert(
                  array('link_tmp' => $uploadPath. DIRECTORY_SEPARATOR .$name, 'user_id' => (int)$UserInfo['id'],'action' => array('del' => 0, 'insert' => 0))
                );
                
                if(!empty($id)){
                 if($Domain == 'lamido'){
                     $ReadExcel = $this->ReadExcelLamido((string)$id);
                 }else{

                     $ReadExcel = $this->ReadExcel((string)$id);
                 }

                 if($ReadExcel){
                     $contents = array(
                        'error'     => false,
                        'message'   => 'success',
                        'id'        => $id,
                    ); 
                 }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'read excel error'
                    ); 
                 }
                 
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'insert log import fail'
                    ); 
                }  
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'file invalid'
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'upload fail'
            ); 
        }
        return Response::json($contents);
    }

    /** ReadExcel create lading excel
     * @param $id
     * @return bool
     */
    function ReadExcel($id){
        $LMongo     = new LMongo;
        $ListImport = $LMongo::collection('log_import_create_lading')->find($id);

        $Data       = Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(1)->select(
                array(2,3,4,5,6,7,8,9,10,11,12,13,14)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();


        if($Data){
            $DataInsert = array();

            foreach($Data as $key => $val){
                if($key == 0){
                    if(trim(strtolower($val[12])) != 'tổng tiền thu hộ'){
                        return false;
                    }
                }elseif(!empty($val[0]) && !empty($val[1]) && !empty($val[2]) && !empty($val[3]) &&
                    !empty($val[4]) && !empty($val[5]) && !empty($val[7]) &&
                    !empty($val[8]) && !empty($val[9]) && !empty($val[10]) && !empty($val[11])){

                    $phone = '';
                    $phone = preg_replace("/[^0-9.,]/", "", $val[8]);
                    $phone = (substr($phone,0,1) != 0 ? '0': '') . $phone;

                    // city
                    $arr_city       = explode('_',$val[9]);
                    $city_id        = (int)end($arr_city);
                    array_pop($arr_city);
                    $city_name      = implode(' ',$arr_city);

                    // district
                    $arr_district       = explode('-',$val[10]);
                    $district_id        = (int)end($arr_district);
                    array_pop($arr_district);
                    $district_name      = implode(' ',$arr_district);

                    $DataInsert[] = array(
                        'partner'           => $id,
                        'status'            => 'NOT ACTIVE',
                        'active'            => 0,
                        'service'           => (int)$val[0],
                        'vas'               => (int)$val[1],
                        'item_name'         => trim($val[2]),
                        'item_weight'       => (int)preg_replace("/[^0-9]/", "", $val[3]),
                        'item_qty'          => (int)preg_replace("/[^0-9]/", "", $val[4]),
                        'item_price'        => preg_replace("/[^0-9]/", "", $val[5]),
                        'item_desc'         => trim($val[6]),
                        'to_name'           => trim($val[7]),
                        'to_phone'          => $phone,
                        'checking'          => 1,
                        'to_city'           => $city_id,
                        'to_district'       => $district_id,
                        'to_address'        => trim($val[11]),
                        'collect'           => preg_replace("/[^0-9]/", "", $val[12]),
                        'city_name'         => $city_name,
                        'district_name'     => $district_name
                    );
                }
            }

            if(!empty($DataInsert)){
                $ListModel  = $LMongo::collection('log_list_lading');
                $Insert = $ListModel->batchInsert($DataInsert);

                if($Insert) return true;
            }

        }
        return false;
    }

    /**
     * Read excel Lamido
     */
    function ReadExcelLamido($id){
        $LMongo     = new LMongo;
        $ListImport = $LMongo::collection('log_import_create_lading')->find($id);
        $Data       = Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(1)->select(
                array(1,2,3,4,5,6,7,8,9,10,11,12)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $DataInsert     = array();
            $ListCity       = [];
            $ListDistrict   = [];

            foreach($Data as $key => $val){
                if(!empty($val[0]) && !empty($val[1]) && !empty($val[2]) && !empty($val[3]) &&
                    !empty($val[4]) && !empty($val[5]) && !empty($val[7]) &&
                    !empty($val[8]) && !empty($val[9]) && !empty($val[10]) && !empty($val[11])){

                    $phone = '';
                    $phone = preg_replace("/[^0-9.,]/", "", $val[9]);
                    $phone = (substr($phone,0,1) != 0 ? '0': '') . $phone;
                    $ListCity[]     = (int)$val[6];
                    $ListDistrict[] = (int)$val[7];

                    $DataInsert[] = array(
                        'partner'           => $id,
                        'status'            => 'NOT ACTIVE',
                        'active'            => 0,
                        'email'             => trim($val[0]),
                        'item_name'         => trim($val[1]),
                        'item_weight'       => (int)preg_replace("/[^0-9]/", "", $val[4]),
                        'item_qty'          => (int)preg_replace("/[^0-9]/", "", $val[3]),
                        'item_price'        => (int)preg_replace("/[^0-9]/", "", $val[2]),
                        'item_desc'         => '',
                        'to_name'           => trim($val[5]),
                        'to_phone'          => $phone,
                        'to_email'          => trim($val[10]),
                        'to_city'           => (int)$val[6],
                        'to_district'       => (int)$val[7],
                        'to_address'        => trim($val[8]),
                        'money_collect'     => (int)preg_replace("/[^0-9]/", "", $val[11]),
                    );
                }
            }

            if(!empty($DataInsert)){
                $City       = [];
                $District   = [];

                if(!empty($ListCity)){
                    $ListCity   = array_unique($ListCity);
                    $CityModel  = new CityModel;
                    $ListCity       = $CityModel::whereIn('id',$ListCity)->get(array('id', 'city_name'))->toArray();
                    if(!empty($ListCity)){
                        foreach($ListCity as $val){
                            $City[(int)$val['id']]  = $val['city_name'];
                        }
                    }
                }

                if(!empty($ListDistrict)){
                    $ListDistrict   = array_unique($ListDistrict);
                    $DistrictModel  = new DistrictModel;
                    $ListDistrict       = $DistrictModel::whereIn('id',$ListDistrict)->get(array('id', 'district_name'));
                    if(!empty($ListDistrict)){
                        foreach($ListDistrict as $val){
                            $District[(int)$val['id']]  = $val['district_name'];
                        }
                    }
                }

                foreach($DataInsert as $key => $val){
                        $DataInsert[$key]['city_name']      = isset($City[(int)$val['to_city']])            ? $City[(int)$val['to_city']]          : '';
                    $DataInsert[$key]['district_name']      = isset($District[(int)$val['to_district']])    ? $District[(int)$val['to_district']]  : '';
                }

                $ListModel  = $LMongo::collection('log_list_lading');
                $Insert = $ListModel->batchInsert($DataInsert);

                if($Insert) return true;
            }
        }
        return false;
    }

    /** get list lading excel
     * @param $id
     * @return mixed
     */
    function getListexcel($id){
        $page               = Input::has('page')    ? (int)Input::get('page')                       : 1;
        $itemPage           = Input::has('limit')   ? Input::get('limit')                           : 500;
        $Status             = Input::has('status')  ? strtoupper(trim(Input::get('status')))        : 'ALL';

        $ListModel      = LMongo::collection('log_list_lading')->where('partner', $id);
        $ModelTotal     = clone $ListModel;

        $CountNotActive = $ModelTotal->where('active',0)->count();
        if($Status != 'ALL'){
            if($Status == 'FAIL'){
                $ListModel  = $ListModel->where('active',2);
            }else{
                $ListModel  = $ListModel->where('status',$Status);
            }
        }

        $CountModel = clone $ListModel;
        $Count      = $CountModel->count();

        if($Count > 0){
            if($itemPage != 'all'){
                $itemPage       = (int)$itemPage;
                $offset         = ($page - 1)*$itemPage;
                $ListModel      = $ListModel->skip($offset)->take($itemPage);
            }
            $Data = $ListModel->get()->toArray();
        }else{
            $Data = new \stdClass();
        }

        $contents = array(
            'error'             => false,
            'data'              => $Data,
            'total'             => $Count,
            'total_not_active'  => $CountNotActive,
            'message'           => 'success'
        );

        return Response::json($contents);
    }

    /** change log  create lading excel
     * @param $id
     * @return mixed
     */
    function postChangelog($id){
        $Data   = Input::json()->all();
        
        /**
        *  Validation params
        * */

        $validation = Validator::make($Data, array(
            'email'         => 'sometimes|required|email',
            'to_email'      => 'sometimes|required|email',
            'item_name'     => 'sometimes|required',
            'item_desc'     => 'sometimes|required',
            'to_address'    => 'sometimes|required',
            'city_name'     => 'sometimes|required',
            'district_name' => 'sometimes|required',
            'to_city'       => 'sometimes|required|numeric|min:1',
            'to_district'   => 'sometimes|required|numeric|min:1',
            'to_name'       => 'sometimes|required',
            'to_phone'      => 'sometimes|required',
            'item_qty'      => 'sometimes|required',
            'item_weight'   => 'sometimes|required',
            'checking'      => 'sometimes|required|in:1,2',
            'vas'           => 'sometimes|required|in:1,2,3,4,5',
            'service'       => 'sometimes|required|in:1,2',
            'item_price'    => 'sometimes|required',
            'collect'       => 'sometimes|required',
            'active'        => 'sometimes|required|in:1,2,3'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }
        
        $Model          = LMongo::collection('log_list_lading');
        
        $DataUpdate = array();

        if(isset($Data['email'])){
            $DataUpdate['email']         = trim($Data['email']);
        }

        if(isset($Data['item_name'])){
            $DataUpdate['item_name']        = trim($Data['item_name']);
        }   
        
        if(isset($Data['item_desc'])){
            $DataUpdate['item_desc']        = trim($Data['item_desc']);
        }      
        
        if(isset($Data['to_address'])){
            $DataUpdate['to_address']       = trim($Data['to_address']);
        } 
        
        if(isset($Data['city_name'])){
            $DataUpdate['city_name']        = trim($Data['city_name']);
        } 
        
        if(isset($Data['district_name'])){
            $DataUpdate['district_name']    = trim($Data['district_name']);
        } 
        
        if(isset($Data['to_name'])){
            $DataUpdate['to_name']          = trim($Data['to_name']);
        }

        if(isset($Data['to_email'])){
            $DataUpdate['to_email']         = trim($Data['to_email']);
        }

        if(isset($Data['checking'])){
            $DataUpdate['checking']         = (int)$Data['checking'];
        }

        if(isset($Data['to_city']) && $Data['to_city'] > 0){
            $DataUpdate['to_city']          = (int)$Data['to_city'];
            $City                           = \CityModel::where('id',$DataUpdate['to_city'])->first(array('city_name'));
            $DataUpdate['city_name']        = $City['city_name'];
        } 
        
        if(isset($Data['to_district']) && $Data['to_district'] > 0){
            $DataUpdate['to_district']      = (int)$Data['to_district'];
            $District                       = \DistrictModel::where('id',$DataUpdate['to_district'])->first(array('district_name'));
            $DataUpdate['district_name']    = $District['district_name'];
        } 
        
        if(isset($Data['to_phone'])){
            $DataUpdate['to_phone']         = trim($Data['to_phone']);
        } 
        
        if(isset($Data['item_qty'])){
            $DataUpdate['item_qty']         = (int)str_replace(array(',','.'),'',$Data['item_qty']);
        } 
        
        if(isset($Data['item_weight'])){
            $DataUpdate['item_weight']      = (int)str_replace(array(',','.'),'',$Data['item_weight']);
        } 
        
        if(isset($Data['vas'])){
            $DataUpdate['vas']              = (int)$Data['vas'];
        } 
        
        if(isset($Data['service'])){
            $DataUpdate['service']          = (int)$Data['service'];
        } 
        
        if(isset($Data['item_price'])){
            $DataUpdate['item_price']       = (int)str_replace(array(',','.'),'',$Data['item_price']);
        } 
        
        if(isset($Data['collect'])){
            $DataUpdate['collect']          = (int)str_replace(array(',','.'),'',$Data['collect']);
        }

        if(isset($Data['money_collect'])){
            $DataUpdate['money_collect']          = (int)str_replace(array(',','.'),'',$Data['money_collect']);
        }

        if(isset($Data['active'])){
            $DataUpdate['active']          = (int)$Data['active'];
            if($Data['active'] == 3){
                $DataUpdate['status']   = 'CANCEL';
            }
        }
        
        if(!empty($DataUpdate)){
            $Update          = $Model->where('_id', new \MongoId($id))
                                     ->where('active',0)
                                     ->update($DataUpdate);
                                    
            if($Update){
                $contents = array(
                    'error'         => false,
                    'message'       => 'success',
                    'data'          => $id,
                    'city_name'     => isset($City['city_name'])            ? $City['city_name']            : '' ,
                    'district_name' => isset($District['district_name'])    ? $District['district_name']    : '' ,
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'update fail',
                    'data'      => $id
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'data update empty',
                'data'      => $id
            );
        }
        return Response::json($contents);
    }

    /** remove lading in create lading excel
     * @param $id
     * @return mixed
     */
    public function getRemovelog($id){
        $Remove = LMongo::collection('log_list_lading')->where('_id', new \MongoId($id))->where('active',0)->remove();
        
        if($Remove){
            $contents = array(
                'error'         => false,
                'message'       => 'success',
                'data'          => $id
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'remove fail',
                'data'      => $id
            );
        }
        return Response::json($contents);
    }

    /** create lading excel
     * @param $id
     * @return mixed
     */
    public function getCreateorder($id){
        $StockId    = Input::has('stock_id')   ? (int)Input::get('stock_id')    : 0;
        $Checking    = Input::has('checking')   ? (int)Input::get('checking')    : 0;

        $UserInfo   = $this->UserInfo();

        $Stock = new \sellermodel\UserInventoryModel;
        $Stock = $Stock::where('id',$StockId)->first();

        if(!$Stock){
            $contents = array(
                'error'     => true,
                'message'   => 'inventory not exists',
                'data'      => array(),
                'code'      => 2
            );
            return Response::json($contents);
        }

        $Order = LMongo::collection('log_list_lading')->where('_id', new \MongoId($id))->where('active',0)->first();
        if(!$Order){
            $contents = array(
                'error'     => true,
                'message'   => 'not exists',
                'data'      => array(),
                'code'      => 2
            );
            return Response::json($contents);
        }


        $Collect = null;
        switch ($Order['vas']) {
            case 1:
                $Payment        = 1;
                $CoD            = 1;
                $PaymentCode    = 2;
                $Collect = $Order['collect'];
                break;
            case 2:
                $Payment        = 2;
                $CoD            = 1;
                $PaymentCode    = 1;
                break;
            case 3:
                $Payment        = 1;
                $CoD            = 1;
                $PaymentCode    = 2;
                break;
            case 4:
                $Payment        = 2;
                $CoD            = 2;
                $PaymentCode    = 1;
                break;
            case 5:
                $Payment        = 1;
                $CoD            = 2;
                $PaymentCode    = 2;
                break;

            default:
                $Payment        = 1;
                $CoD            = 1;
                $PaymentCode    = 1;
        }

        $DataCreate = array(
            'From'   =>     array(
                'Stock'     => (int)$StockId,
                'City'      => (int)$Stock['city_id'],
                'Province'  => (int)$Stock['province_id'],
                'Ward'      => (int)$Stock['ward_id'],
                'Name'      => isset($Stock['user_name']) ? $Stock['user_name'] : $UserInfo['fullname'],
                'Phone'     => isset($Stock['phone']) ? $Stock['phone'] : $UserInfo['phone'],
                'Address'   => $Stock['address'],
            ),
            'To'        => array(
                'City'      => (int)$Order['to_city'],
                'Province'  => (int)$Order['to_district'],
                'Name'      => $Order['to_name'],
                'Phone'     => $Order['to_phone'],
                'Address'   => $Order['to_address']
            ),
            'Order'     => array(
                'Weight'        => $Order['item_weight'],
                'Amount'        => $Order['item_price'],
                'Quantity'      => $Order['item_qty'],
                'ProductName'   => $Order['item_name'],
                'Description'   => $Order['item_desc']
            ),
            'Config'    => array(
                'Service'       => (int)$Order['service'],
                'CoD'           => $CoD,
                'Payment'       => $Payment,
                'Protected'     => 2,
                'Checking'      => ($Checking) ? $Checking :  (int)$Order['checking'],
                'Fragile'       => 2,
                'PaymentCod'    => $PaymentCode
            ),
            'Domain'            => 'shipchung.vn',
            'Type'              => 'excel',
            'UserId'            => (int)$UserInfo['id']
        );

        if(isset($Collect)){
            $DataCreate['Order']['Collect'] = $Collect;
        }

        $CreateLading = json_decode(\cURL::post($this->url.'/courier/create',$DataCreate)->body,1);

        $Model = LMongo::collection('log_list_lading');
        if($CreateLading && !$CreateLading['error']){
            $Update = $Model->where('_id', new \MongoId($id))->update(array('active' => 1, 'trackingcode' => $CreateLading['data']['TrackingCode'],'status' => 'SUCCESS'));
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      =>  $CreateLading['data']['TrackingCode'],
                'code'      => 1
            );
        }else{
            $Update = $Model->where('_id', new \MongoId($id))->update(array('active' => 2, 'status' => 'FAIL'));
            $contents = array(
                'error'     => true,
                'message'   => $CreateLading['message'],
                'code'      => 1
            );
        }

        $total = LMongo::collection('log_list_lading')->where('partner', $Order['partner'])->where('active',0)->count();

        // finish
        if($total == 0){
            LMongo::collection('log_import_create_lading')->where('_id', new \MongoId($Order['partner']))->update(array('action.insert' => 1));
        }

        $contents['total']  = $total;

        return Response::json($contents);
    }

    private function __response($json,$contents){
        if($json){
            return Response::json($contents);
        }else{
            return $contents;
        }
    }

    private function CheckUser($Email){
        $Model = new \User;
        $User = $Model::where('email',$Email)->first();
        if(empty($User)){
            return ['error' => true, 'message' => 'USER_NOT_EXISTS', 'code' => 1];
        }

        $this->user_id  = (int)$User['id'];

        $UserInventoryModel = new UserInventoryModel;
        $Inventory          = $UserInventoryModel::where('user_id', $this->user_id)->first();
        if(empty($Inventory)){
            return ['error' => true, 'message' => 'INVENTORY_NOT_EXISTS', 'code' => 1];
        }
        $this->stock_id  = (int)$Inventory['id'];

        return;
    }

    private function Createfolder($item = ''){

        $uploadPath = storage_path(). DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'excel' . DIRECTORY_SEPARATOR . 'orders';
        $linkPath   = $this->link_upload.'/excel/orders';

        if(!empty($item)){
            $item   = str_split(preg_replace('/(\W)/','',(string)$item));

            for($i = 0; $i<5; $i++){
                if(isset($item[$i]) && $item[$i] != ''){
                    $uploadPath .= DIRECTORY_SEPARATOR.$item[$i];
                    $linkPath   .= '/'.$item[$i];
                }
            }

            if(!file_exists($uploadPath)){
                \File::makeDirectory($uploadPath,0777, true, true);
            }
        }

        return array(
            'uploadPath'    => $uploadPath,
            'linkPath'      => $linkPath
        );
    }


    public function ExportExcelProcess($Data, $Name = ""){
        $FileName   = 'Danh_sach_van_đon';
        if(!empty($this->time_create_start) || !empty($this->time_create_end)){
            $FileName .= '_tao_tu_'.$this->time_create_start.'_den_'.$this->time_create_end;
        }
        if(!empty($this->time_accept_start) || !empty($this->time_accept_end)){
            $FileName .= '_duyet_tu_'.$this->time_accept_start.'_den_'.$this->time_accept_end;
        }

        if(!empty($Name)){
            $FileName = $Name;
        }

        $Courier    = [];
        $Service    = [];
        $City       = [];
        $Address    = [];
        $FromAddress= [];
        $District   = [];
        $Ward       = [];
        $User       = [];
        $Status     = [];
        $ListUserId = [];

        if(!empty($Data)){
            $Courier    = $this->getCourier();
            $Service    = $this->getService();
            $City       = $this->getCity();
            $Status     = $this->getStatus();

            foreach($Data as $val){
                $ListDistrictId[]  = $val['from_district_id'];
                $ListWardId[]      = $val['from_ward_id'];
                $ListToAddress[]   = $val['to_address_id'];
                $ListFromAddress[] = $val['from_address_id'];
                $ListUserId[]      = $val['from_user_id'];
            }

            if(isset($ListToAddress) && !empty($ListToAddress)){
                $AddressModel   = new AddressModel;
                $ListAddress    = $AddressModel::whereIn('id', $ListToAddress)->get()->toArray();
            }

            if(isset($ListAddress) && !empty($ListAddress)){
                foreach($ListAddress as $val){
                    if(!empty($val)){
                        $Address[$val['id']]    = $val;
                        $ListDistrictId[]       = (int)$val['province_id'];
                        $ListWardId[]           = (int)$val['ward_id'];
                    }
                }
            }

            if(isset($ListFromAddress) && !empty($ListFromAddress)){
                $InventoryModel   = new UserInventoryModel;
                $ListFromAddress  = $InventoryModel::whereIn('id', $ListFromAddress)->get()->toArray();
            }

            if(isset($ListFromAddress) && !empty($ListFromAddress)){
                foreach($ListFromAddress as $val){
                    if(!empty($val)){
                        $FromAddress[$val['id']]    = $val;
                    }
                }
            }

            $ListDistrictId = array_unique($ListDistrictId);
            $ListWardId     = array_unique($ListWardId);
            $ListUserId     = array_unique($ListUserId);

            if(!empty($ListDistrictId)){
                $District   = $this->getProvince($ListDistrictId);
            }

            if(!empty($ListUserId)){
                $UserModel = new \User;
                $Count      = count($ListUserId);
                $User       = [];
                $ListUser   = [];

                if($Count > 3000){
                    $ListUser4      = array_slice($ListUserId, 3000);
                    $User           = $UserModel->whereIn('id',$ListUser4)->with('user_info')->get(['id','fullname', 'phone', 'email'])->toArray();
                    $ListUser      += $User;
                }

                if($Count > 2000){
                    $ListUser3    = array_slice($ListUserId, 2000, 3000);
                    $User         = $UserModel->whereIn('id',$ListUser3)->with('user_info')->get(['id','fullname', 'phone', 'email'])->toArray();
                    $ListUser    += $User;
                }

                if($Count > 1000){
                    $ListUser2    = array_slice($ListUserId, 1000, 2000);
                    $User         = $UserModel->whereIn('id',$ListUser2)->with('user_info')->get(['id','fullname', 'phone', 'email'])->toArray();
                    $ListUser    += $User;
                }

                $ListUser1    = array_slice($ListUserId, 0, 1000);
                $User         = $UserModel->whereIn('id',$ListUser1)->with('info')->get(['id','fullname', 'phone', 'email'])->toArray();
                $ListUser    += $User;

                $User        = [];
                if(!empty($ListUser)){
                    foreach($ListUser as $val){
                        $User[$val['id']]   = $val;
                    }
                }
            }

            if(!empty($ListWardId)){
                $WardModel = new WardModel;
                $ListWard  =  $WardModel::whereIn('id',$ListWardId)->get(['id','ward_name'])->toArray();
                if(!empty($ListWard)){
                    foreach($ListWard as $val){
                        if(!empty($Ward[$val['id']])){
                            $Ward[$val['id']]   = $val['ward_name'];
                        }
                        
                    }
                }
            }
        }

        return Excel::create($FileName, function($excel) use($Data, $Courier, $Service, $City, $Address, $District, $Ward, $User, $Status, $FromAddress){
            $excel->sheet('Sheet1', function($sheet) use($Data, $Courier, $Service, $City, $Address, $District, $Ward, $User, $Status, $FromAddress){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách vận đơn'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,
                    'H'     =>  30, 'I'     =>  30, 'J'     =>  30, 'K'     =>  30, 'L'     =>  30, 'M'     =>  30, 'N'     =>  30,
                    'O'     =>  30, 'P'     =>  30, 'Q'     =>  30, 'R'     =>  30, 'S'     =>  30, 'T'     =>  30, 'U'     =>  30,
                    'V'     =>  30, 'W'     =>  30, 'X'     =>  30, 'Y'     =>  30, 'Z'     =>  30, 'AA'     =>  30, 'AB'   =>  30,
                    'AC'     =>  30, 'AD'     =>  30, 'AE'     =>  30, 'AF'  => 30, 'AG'    => 30,   'AH'    =>  30, 'AI'   => 30,
                    'AJ'     => 30, 'AK'    => 30
                ));
                $sheet->setMergeColumn(array(
                    'columns' => array('A','B','C','D','E','F','G','H','I','J','K' ,'AI','AJ','AK'),
                    'rows' => array(
                        array(3,4)
                    )
                ));
                $sheet->mergeCells('L3:R3');
                $sheet->mergeCells('S3:Y3');
                $sheet->mergeCells('Z3:AC3');
                $sheet->mergeCells('AD3:AH3');

                $sheet->row(3, array(
                    'STT', 'Thời gian duyệt', 'Thời gian lấy hàng', 'Thời gian giao hàng', 'Bản kê đối soát', 'Mã vận đơn', 'Mã đơn hàng của KH',
                    'Hãng vận chuyển', 'Mã hãng vận chuyển', 'Dịch vụ', 'Trạng thái', 'Nơi gửi','','','','','', '', 'Nơi nhận', '', '', '', '', '', '',
                    'Thông tin sản phẩm', '', '', '',
                    'Thông tin phí', '', '', '', '', 'Giảm giá', 'Tổng tiền thu hộ','Thanh toán'
                ));

                $sheet->row(4, array(
                    '', '', '', '', '', '', '', '', '', '', '',  'Họ tên', 'Email', 'Số điện thoại',  'Tỉnh/Thành phố',
                    'Quận/Huyện', 'Phường xã', 'Địa chỉ','Họ tên', 'Email', 'Số điện thoại',    'Tỉnh/Thành phố', 'Quận/Huyện', 'Phường xã','Địa chỉ',
                    'Tên sản phẩm', 'Tổng giá trị', 'Khối lượng', 'Mô tả', 'Phí vận chuyển', 'Phí thu hộ', 'Phí bảo hiểm', 'Phí vượt cân', 'Phí chuyển hoàn'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });
                $sheet->row(4,function($row){
                    $row->setBackground('#989898')
                    ->setFontSize(12)
                    ->setFontWeight('bold')
                    ->setAlignment('center')
                    ->setValignment('top');
                });
                $sheet->setBorder('A3:AK4', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $Payment    = (isset($User[(int)$val['from_user_id']]) && (isset($User[(int)$val['from_user_id']]['info']))) ? $User[(int)$val['from_user_id']]['info']['priority_payment'] : 2;
                    $dataExport = array(
                        $i++,
                        $val['time_accept'] > 0 ? date("d/m/y H:m",$val['time_accept']) : '',
                        $val['time_pickup'] > 0 ? date("d/m/y H:m",$val['time_pickup']) : '',
                        $val['time_success'] > 0 ? date("d/m/y H:m",$val['time_success']) : '',
                        isset($val['verify_id']) ? $val['verify_id'] : '',
                        (isset($val['tracking_code'])) ? $val['tracking_code'] : '',
                        (isset($val['order_code'])) ?  $val['order_code'] :"",
                        isset($Courier[(int)$val['courier_id']]) ? $Courier[(int)$val['courier_id']] : 'HVC',
                        $val['courier_tracking_code'],
                        isset($Service[(int)$val['service_id']]) ? $Service[(int)$val['service_id']] : 'DV',
                        isset($Status[(int)$val['status']]) ? $Status[(int)$val['status']] : 'Trạng thái',

                        isset($FromAddress[(int)$val['from_address_id']]) ? $FromAddress[(int)$val['from_address_id']]['user_name'] : '',
                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['email'] : '',
                        isset($FromAddress[(int)$val['from_address_id']]) ? $FromAddress[(int)$val['from_address_id']]['phone'] : '',
                        isset($City[(int)$val['from_city_id']]) ? $City[(int)$val['from_city_id']] : '',
                        isset($District[(int)$val['from_district_id']]) ? $District[(int)$val['from_district_id']] : '',
                        isset($Ward[(int)$val['from_ward_id']]) ? $Ward[(int)$val['from_ward_id']] : '',
                        $val['from_address'],

                        $val['to_name'],
                        $val['to_email'],
                        $val['to_phone'],
                        (isset($Address[(int)$val['to_address_id']]) && isset($City[$Address[(int)$val['to_address_id']]['city_id']])) ? $City[$Address[(int)$val['to_address_id']]['city_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($District[$Address[(int)$val['to_address_id']]['province_id']])) ? $District[$Address[(int)$val['to_address_id']]['province_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($Ward[$Address[(int)$val['to_address_id']]['ward_id']])) ? $Ward[$Address[(int)$val['to_address_id']]['ward_id']] : '',
                        isset($Address[(int)$val['to_address_id']]) ? $Address[(int)$val['to_address_id']]['address'] : '',

                        $val['product_name'],
                        isset($val['total_amount']) ? $val['total_amount'] : '',
                        isset($val['total_weight']) ? $val['total_weight'] : '',
                        isset($val['order_item']) ? $val['order_item']['description'] : '',

                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvc']) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['sc_cod']) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['sc_pbh']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvk']) : '',
                        (isset($val['order_detail']) && ($val['status'] == 66)) ? number_format($val['order_detail']['sc_pch']) : '',
                        isset($val['order_detail']) ? number_format(($val['order_detail']['sc_discount_pvc'] + (($val['status'] != 66) ? $val['order_detail']['sc_discount_cod'] : 0))) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['money_collect']) : '',
                        ($Payment == 1 ) ? 'Vimo' : 'Ngân Lượng'
                    );

                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }


    public function ExportExcel($Data, $Name = ""){
        $FileName   = 'Danh_sach_van_đon';
        if(!empty($this->time_create_start) || !empty($this->time_create_end)){
            $FileName .= '_tao_tu_'.$this->time_create_start.'_den_'.$this->time_create_end;
        }
        if(!empty($this->time_accept_start) || !empty($this->time_accept_end)){
            $FileName .= '_duyet_tu_'.$this->time_accept_start.'_den_'.$this->time_accept_end;
        }

        if(!empty($Name)){
            $FileName = $Name;
        }

        $Courier    = [];
        $Service    = [];
        $City       = [];
        $Address    = [];
        $District   = [];
        $Ward       = [];
        $User       = [];
        $Status     = [];
        $ListUserId = [];

        if(!empty($Data)){
            $Courier    = $this->getCourier();
            $Service    = $this->getService();
            $City       = $this->getCity();
            $Status     = $this->getStatus();

            foreach($Data as $val){
                $ListDistrictId[] = $val['from_district_id'];
                $ListWardId[]     = $val['from_ward_id'];
                $ListToAddress[]  = $val['to_address_id'];
                $ListUserId[]     = $val['from_user_id'];
            }

            if(isset($ListToAddress) && !empty($ListToAddress)){
                $AddressModel   = new AddressModel;
                $ListAddress    = $AddressModel::whereIn('id', $ListToAddress)->get()->toArray();
            }

            if(isset($ListAddress) && !empty($ListAddress)){
                foreach($ListAddress as $val){
                    if(!empty($val)){
                        $Address[$val['id']]    = $val;
                        $ListDistrictId[]       = (int)$val['province_id'];
                        $ListWardId[]           = (int)$val['ward_id'];
                    }
                }
            }

            $ListDistrictId = array_unique($ListDistrictId);
            $ListWardId     = array_unique($ListWardId);
            $ListUserId     = array_unique($ListUserId);

            if(!empty($ListDistrictId)){
                $District   = $this->getProvince($ListDistrictId);
            }

            if(!empty($ListUserId)){
                $UserModel = new \User;
                $Count      = count($ListUserId);
                $User       = [];
                $ListUser   = [];

                if($Count > 3000){
                    $ListUser4      = array_slice($ListUserId, 3000);
                    $User           = $UserModel->whereIn('id',$ListUser4)->with('user_info')->get(['id','fullname', 'phone', 'email'])->toArray();
                    $ListUser      += $User;
                }

                if($Count > 2000){
                    $ListUser3    = array_slice($ListUserId, 2000, 3000);
                    $User         = $UserModel->whereIn('id',$ListUser3)->with('user_info')->get(['id','fullname', 'phone', 'email'])->toArray();
                    $ListUser    += $User;
                }

                if($Count > 1000){
                    $ListUser2    = array_slice($ListUserId, 1000, 2000);
                    $User         = $UserModel->whereIn('id',$ListUser2)->with('user_info')->get(['id','fullname', 'phone', 'email'])->toArray();
                    $ListUser    += $User;
                }

                $ListUser1    = array_slice($ListUserId, 0, 1000);
                $User         = $UserModel->whereIn('id',$ListUser1)->with('info')->get(['id','fullname', 'phone', 'email'])->toArray();
                $ListUser    += $User;

                $User        = [];
                if(!empty($ListUser)){
                    foreach($ListUser as $val){
                        $User[$val['id']]   = $val;
                    }
                }
            }

            if(!empty($ListWardId)){
                $WardModel = new WardModel;
                $ListWard  =  $WardModel::whereIn('id',$ListWardId)->get(['id','ward_name'])->toArray();
                if(!empty($ListWard)){
                    foreach($ListWard as $val){
                        if(!empty($Ward[$val['id']])){
                            $Ward[$val['id']]   = $val['ward_name'];
                        }
                        
                    }
                }
            }
        }

        return Excel::create($FileName, function($excel) use($Data, $Courier, $Service, $City, $Address, $District, $Ward, $User, $Status){
            $excel->sheet('Sheet1', function($sheet) use($Data, $Courier, $Service, $City, $Address, $District, $Ward, $User, $Status){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách vận đơn'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,
                    'H'     =>  30, 'I'     =>  30, 'J'     =>  30, 'K'     =>  30, 'L'     =>  30, 'M'     =>  30, 'N'     =>  30,
                    'O'     =>  30, 'P'     =>  30, 'Q'     =>  30, 'R'     =>  30, 'S'     =>  30, 'T'     =>  30, 'U'     =>  30,
                    'V'     =>  30, 'W'     =>  30, 'X'     =>  30, 'Y'     =>  30, 'Z'     =>  30, 'AA'     =>  30, 'AB'   =>  30,
                    'AC'     =>  30, 'AD'     =>  30, 'AE'     =>  30, 'AF'  => 30, 'AG'    => 30,   'AH'    =>  30, 'AI'   => 30,
                    'AJ'     => 30, 'AK'    => 30
                ));
                $sheet->setMergeColumn(array(
                    'columns' => array('A','B','C','D','E','F','G','H','I','J','K' ,'AI','AJ','AK'),
                    'rows' => array(
                        array(3,4)
                    )
                ));
                $sheet->mergeCells('L3:R3');
                $sheet->mergeCells('S3:Y3');
                $sheet->mergeCells('Z3:AC3');
                $sheet->mergeCells('AD3:AH3');

                $sheet->row(3, array(
                    'STT', 'Thời gian duyệt', 'Thời gian lấy hàng', 'Thời gian giao hàng', 'Bản kê đối soát', 'Mã vận đơn', 'Mã đơn hàng của KH',
                    'Hãng vận chuyển', 'Mã hãng vận chuyển', 'Dịch vụ', 'Trạng thái', 'Nơi gửi','','','','','', '', 'Nơi nhận', '', '', '', '', '', '',
                    'Thông tin sản phẩm', '', '', '',
                    'Thông tin phí', '', '', '', '', 'Giảm giá', 'Tổng tiền thu hộ','Thanh toán'
                ));

                $sheet->row(4, array(
                    '', '', '', '', '', '', '', '', '', '', '',  'Họ tên', 'Email', 'Số điện thoại',  'Tỉnh/Thành phố',
                    'Quận/Huyện', 'Phường xã', 'Địa chỉ','Họ tên', 'Email', 'Số điện thoại',    'Tỉnh/Thành phố', 'Quận/Huyện', 'Phường xã','Địa chỉ',
                    'Tên sản phẩm', 'Tổng giá trị', 'Khối lượng', 'Mô tả', 'Phí vận chuyển', 'Phí thu hộ', 'Phí bảo hiểm', 'Phí vượt cân', 'Phí chuyển hoàn'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });
                $sheet->row(4,function($row){
                    $row->setBackground('#989898')
                    ->setFontSize(12)
                    ->setFontWeight('bold')
                    ->setAlignment('center')
                    ->setValignment('top');
                });
                $sheet->setBorder('A3:AK4', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $Payment    = (isset($User[(int)$val['from_user_id']]) && (isset($User[(int)$val['from_user_id']]['info']))) ? $User[(int)$val['from_user_id']]['info']['priority_payment'] : 2;
                    $dataExport = array(
                        $i++,
                        $val['time_accept'] > 0 ? date("d/m/y H:m",$val['time_accept']) : '',
                        $val['time_pickup'] > 0 ? date("d/m/y H:m",$val['time_pickup']) : '',
                        $val['time_success'] > 0 ? date("d/m/y H:m",$val['time_success']) : '',
                        isset($val['verify_id']) ? $val['verify_id'] : '',
                        (isset($val['tracking_code'])) ? $val['tracking_code'] : '',
                        (isset($val['order_code'])) ?  $val['order_code'] :"",
                        isset($Courier[(int)$val['courier_id']]) ? $Courier[(int)$val['courier_id']] : 'HVC',
                        $val['courier_tracking_code'],
                        isset($Service[(int)$val['service_id']]) ? $Service[(int)$val['service_id']] : 'DV',
                        isset($Status[(int)$val['status']]) ? $Status[(int)$val['status']] : 'Trạng thái',

                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['fullname'] : '',
                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['email'] : '',
                        isset($User[(int)$val['from_user_id']]) ? $User[(int)$val['from_user_id']]['phone'] : '',
                        isset($City[(int)$val['from_city_id']]) ? $City[(int)$val['from_city_id']] : '',
                        isset($District[(int)$val['from_district_id']]) ? $District[(int)$val['from_district_id']] : '',
                        isset($Ward[(int)$val['from_ward_id']]) ? $Ward[(int)$val['from_ward_id']] : '',
                        $val['from_address'],

                        $val['to_name'],
                        $val['to_email'],
                        $val['to_phone'],
                        (isset($Address[(int)$val['to_address_id']]) && isset($City[$Address[(int)$val['to_address_id']]['city_id']])) ? $City[$Address[(int)$val['to_address_id']]['city_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($District[$Address[(int)$val['to_address_id']]['province_id']])) ? $District[$Address[(int)$val['to_address_id']]['province_id']] : '',
                        (isset($Address[(int)$val['to_address_id']]) && isset($Ward[$Address[(int)$val['to_address_id']]['ward_id']])) ? $Ward[$Address[(int)$val['to_address_id']]['ward_id']] : '',
                        isset($Address[(int)$val['to_address_id']]) ? $Address[(int)$val['to_address_id']]['address'] : '',

                        $val['product_name'],
                        isset($val['total_amount']) ? $val['total_amount'] : '',
                        isset($val['total_weight']) ? $val['total_weight'] : '',
                        isset($val['order_item']) ? $val['order_item']['description'] : '',

                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvc']) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['sc_cod']) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['sc_pbh']) : '',
                        isset($val['order_detail']) ? number_format($val['order_detail']['sc_pvk']) : '',
                        (isset($val['order_detail']) && ($val['status'] == 66)) ? number_format($val['order_detail']['sc_pch']) : '',
                        isset($val['order_detail']) ? number_format(($val['order_detail']['sc_discount_pvc'] + (($val['status'] != 66) ? $val['order_detail']['sc_discount_cod'] : 0))) : '',
                        (isset($val['order_detail']) && $val['status'] != 66) ? number_format($val['order_detail']['money_collect']) : '',
                        ($Payment == 1 ) ? 'Vimo' : 'Ngân Lượng'
                    );

                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }

    public function getAcceptorder($ScCode){
        $UserInfo   = $this->UserInfo();
        $UserId     = (int)$UserInfo['id'];

        $OrderModel = new OrdersModel;
        $Order      = $OrderModel->where('tracking_code',$ScCode)->where('from_user_id',$UserId)->first(array('status'));
        if(!empty($Order)){

        }else{

        }

    }

    public function getDashboard() {

        $courier = CourierModel::all();
        $timeStart = strtotime(date("Y-m-d 00:00:00"));
        $InTime = time() - 30*86400;

        $statistic = StatisticModel::where('time_create',$timeStart)->first();

        $total = OrdersModel::where('status',21)->where('time_accept','>=',$InTime)->where('time_update','>=',$timeStart)->groupBy('courier_id')->get(array('courier_id',DB::raw('count(*) as total')));;
        $data = array();
        if(!$total->isEmpty()) {
            foreach($total as $var) {
                $data[$var->courier_id] = (int)$var->total;
            }
        }
        if(!$courier->isEmpty()) {
            foreach($courier as $k => $var) {
                $courier[$k]->total = isset($data[$var->id]) ? $data[$var->id] : 0;
            }
        }
        $timeStart30Days = time()  - 30*86400;
        $orderModel = OrdersModel::query();
        $orderModel->where('time_create','>=',$timeStart30Days)->where('status','>=',36)->where('time_pickup',0);

        $orderError = $orderModel->get();
        if(!$courier->isEmpty()) {
            $response = [
                'error' =>  false,
                'data'  =>  $courier,
                'orderError'   =>  $orderError,
                'statistic' => $statistic
            ];
        } else {
            $response = [
                'error' =>  true,
                'message'   =>  'Không có dữ liệu'
            ];
        }

        return Response::json($response);
    }

    public function getUserDashboard() {
        $fromDate = strtotime(date("Y-m-d 00:00:00"));

        $timeYesterday = $fromDate - 10*3600;
        $timeEndYesterday = $fromDate - 6*3600;
        $timeOff = 14*3600;
        $timeToday = $fromDate + 14*3600;
        $timeStartToday = $fromDate + 8*3600;
        $currentTime = time() - 4 *3600;

        //get shipchung status
        $listGroupStatus = GroupStatusModel::where('group',3)->lists('id');
        $listStatus = GroupOrderStatusModel::whereIn('group_status',$listGroupStatus)->lists('order_status_code');


        $orderToday = OrdersModel::where('time_create','>=',$fromDate)->whereIn('status',$listStatus)->count();
        $orderNeedPickup = OrdersModel::where('time_accept','>=',$fromDate - 7*86400)->where('time_pickup','=',0)->whereIn('status',[21,30,35])->count();
        $orderPickupNeedProcess = OrdersModel::where('time_accept','<',$timeToday)
            ->where('time_accept','>',$timeYesterday)
            ->where(function($query) use($timeYesterday, $timeEndYesterday, $timeToday, $timeStartToday, $currentTime, $timeOff) {
                $query->where(function($query) use($timeEndYesterday, $timeOff, $currentTime) {
                    $query->where('time_accept','<',$timeEndYesterday);
                    $query->whereRaw('time_accept + '.$timeOff . ' < '.$currentTime);
                });
                $query->orWhere(function($query) use ($timeEndYesterday, $timeStartToday) {
                    $query->where('time_accept','>=',$timeEndYesterday);
                    $query->where('time_accept','<=',$timeStartToday);
                    $query->whereRaw(time().' >= '.($timeStartToday+4*3600));
                });
                $query->orWhere(function($query) use($timeStartToday, $currentTime) {
                    $query->where('time_accept','>',$timeStartToday);
                    $query->where('time_accept','<',$currentTime);
                });
        })->whereIn('status',[30,35])->count();
        $orderAddress = OrdersModel::where('time_create','>=',$fromDate)->whereIn('status',$listStatus)->select(DB::raw('COUNT(DISTINCT to_address_id) AS total'))->pluck('total');
        $orderPickupFail = OrdersModel::where('time_accept','>=',$fromDate - 7*86400)->whereIn('status',[31,32,33,34])->count();

        $result = [
            'orderToday'        =>  $orderToday,
            'orderNeedPickup'   =>  $orderNeedPickup,
            'orderPickupNeedProcess'    =>  $orderPickupNeedProcess,
            'orderAddress'      =>  $orderAddress,
            'orderPickupFail'   =>  $orderPickupFail
        ];
        return Response::json([
            'error'    =>  false,
            'data'      =>  $result
        ]);
    }

    public function getReport() {
        $timeStart = strtotime(date("Y-m-d 00:00:00"));
        $InTime = time() - 30*86400;

        //get shipchung status
        $listGroupStatus = GroupStatusModel::where('group',3)->lists('id');
        $listStatus = GroupOrderStatusModel::whereIn('group_status',$listGroupStatus)->lists('order_status_code');

        //đơn hàng phát sinh trong ngày
        //đơn hàng duyệt trong ngày
        $orderPSTN = $orderDTN = 0;
        $Order = OrdersModel::where('time_create','>=',$timeStart)->whereIn('status',$listStatus)->get();
        if(!$Order->isEmpty()) {
            foreach($Order as $OneOrder) {
                ++$orderPSTN;
                if($OneOrder->time_accept >= $timeStart) {
                    ++$orderDTN;
                }
            }
        }
        //lấy thành công
        $orderLTC = OrdersModel::where('time_accept','>=',$InTime)->where('status',36)->where('time_pickup','>=',$timeStart)->count();
        //đơn hàng hủy
        $orderHuy = OrdersModel::where('time_accept','>=',$InTime)->whereIn('status',[22,23,24,25])->where('time_update','>=',$timeStart)->count();
        //giao hàng thành công
        $orderGHTC = OrdersModel::where('time_accept','>=',$InTime)->where('status',52)->where('time_update','>=',$timeStart)->count();
        //đang giao hàng
        $orderDGH = OrdersModel::where('time_accept','>=',$InTime)->where('status',51)->where('time_pickup','>=',$timeStart)->count();
        //đang chuyển hoàn
        $orderDCH = OrdersModel::where('time_accept','>=',$InTime)->whereIn('status',[62,63,64,65])->where('time_update','>=',$timeStart)->count();
        //chờ xác nhận chuyển hoàn
        $orderCXNCH = OrdersModel::where('time_accept','>=',$InTime)->where('status',60)->where('time_update','>=',$timeStart)->count();

        $statistic = StatisticModel::firstOrNew(['time_create'   =>  $timeStart]);
        $statistic->order_pstn = $orderPSTN;
        $statistic->order_dtn = $orderDTN;
        $statistic->order_ltc = $orderLTC;
        $statistic->order_huy = $orderHuy;
        $statistic->order_ghtc = $orderGHTC;
        $statistic->order_dgh = $orderDGH;
        $statistic->order_dch = $orderDCH;
        $statistic->order_cxnch = $orderCXNCH;
        $statistic->save();
    }

    public function getRecent($id) {
        $timeStart = time()-90*86400;
        $order = OrdersModel::where('from_user_id',$id)->where('time_create','>=',$timeStart)->take(10)
            ->with([
                'MetaStatus'   => function($query){
                    $query->get(array('code','name'));
                }])->orderBy('time_create','DESC')->get();
        if(!$order->isEmpty()) {
            return Response::json([
                'status'    =>  true,
                'data'      =>  $order
            ]);
        } else {
            return Response::json([
                'status'    =>  false,
                'message'   =>  'Không có dữ liệu'
            ]);
        }
    }
}

?>