<?php
namespace accounting;

use ordermodel\OrdersModel;
use sellermodel\UserInfoModel;
use ordermodel\AddressModel;
use ordermodel\DetailModel;
use sellermodel\UserInventoryModel;

use User;
use WardModel;
class OrderCtrl extends BaseCtrl
{
    private $total              = 0;
    private $total_all          = 0;
    private $total_group        = [];
    private $data               = [];
    private $list_district_id   = [];
    private $list_ward_id       = [];
    private $list_to_address    = [];
    private $list_from_address  = [];

    function __construct(){

    }

    private function getModel(){
        $Model              = new OrdersModel;

        $TimeCreateStart    = Input::has('create_start')        ? (int)Input::get('create_start')           : 0; // time_create start   time_stamp
        $TimeCreateEnd      = Input::has('create_end')          ? (int)Input::get('create_end')             : 0; // time_create end
        $TimeAcceptStart    = Input::has('accept_start')        ? (int)Input::get('accept_start')           : 0; // time_accept start
        $TimeAcceptEnd      = Input::has('accept_end')          ? (int)Input::get('accept_end')             : 0; // time_accept end
        $TimeSuccessStart   = Input::has('success_start')       ? (int)Input::get('success_start')          : 0; // time_accept start
        $TimeSuccessEnd     = Input::has('success_end')         ? (int)Input::get('success_end')            : 0; // time_accept end
        $PickupStart        = Input::has('pickup_start')        ? (int)Input::get('pickup_start')           : 0; // time_pickup start
        $PickupEnd          = Input::has('pickup_end')          ? (int)Input::get('pickup_end')             : 0; // time_pickup end


        $ServiceId          = Input::has('service')             ? (int)Input::get('service')                : 0;
        $Domain             = Input::has('domain')              ? trim(Input::get('domain'))                : 0;
        $FromUser           = Input::has('from_user')           ? trim(Input::get('from_user'))             : '';
        $ToUser             = Input::has('to_user')             ? trim(Input::get('to_user'))               : '';

        $TrackingCode       = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';

        $FromCity           = Input::has('from_city')           ? (int)Input::get('from_city')              : 0;
        $FromDistrict       = Input::has('from_district')       ? (int)Input::get('from_district')          : 0;

        $ToCity             = Input::has('to_city')             ? (int)Input::get('to_city')                : 0;
        $ToDistrict         = Input::has('to_district')         ? (int)Input::get('to_district')            : 0;
        $Level              = Input::has('level')               ? (int)Input::get('level')                  : null;

        $PostOffice         = Input::has('post_office_id')      ? (int)Input::get('post_office_id')         : 0;

        $VerifyMoneyCollect = Input::has('verify_money_collect')    ? (int)Input::get('verify_money_collect')       : 0;
        $Verify             = Input::has('verify')                  ? (int)Input::get('verify')                     : null;

        $Global             = Input::has('global')              ? (int)Input::get('global')                 : null;
        $CountryId          = Input::has('country_id')          ? Input::get('country_id')                  : 237;

        $Model              = $Model::where('from_country_id', $CountryId);

        if(!empty($Global)){
            $Model  = $Model->where('to_country_id','<>', $CountryId);
        }

        if(isset($Level)){
            $ListUser       = \loyaltymodel\UserModel::where('level', $Level)->lists('user_id');

            // ko có dữ liệu , return []
            if(empty($ListUser)){
                return false;
            }
        }

        if(!empty($FromUser)){
            $UserModel      = new User;

            if (filter_var($FromUser, FILTER_VALIDATE_EMAIL)){  // search email
                $UserModel          = $UserModel->where('email',$FromUser);
            }elseif(filter_var((int)$FromUser, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $UserModel          = $UserModel->where('phone',$FromUser);
            }else{ // search code
                $UserModel          = $UserModel->where('fullname',$FromUser);
            }

            $ListUserSearch = $UserModel->lists('id');
            if(empty($ListUserSearch)){
                return false;
            }else{
                if(!empty($ListUser)){
                    $ListUser   = array_intersect($ListUser, $ListUserSearch);
                }else{
                    $ListUser   = $ListUserSearch;
                }
            }

            if(empty($ListUser)){
                return false;
            }
        }

        if(!empty($ListUser)){
            $Model  = $Model->whereRaw("from_user_id in (". implode(",", $ListUser) .")");
            unset($ListUser);
        }

        if(!empty($ToUser)){
            if (filter_var($ToUser, FILTER_VALIDATE_EMAIL)){  // search email
                $Model          = $Model->where('to_email',$ToUser);
                
            }elseif(filter_var((int)$ToUser, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $Model          = $Model->where('to_phone',$ToUser);
            }else{ // search code
                $Model          = $Model->where('to_name',$ToUser);
            }
        }

        if(!empty($Domain)){
            $Model          = $Model->where('domain',$Domain);
        }

        if(!empty($ServiceId)){
            $Model          = $Model->where('service_id',$ServiceId);
        }

        if(!empty($FromDistrict)){
            $Model          = $Model->where('from_district_id',$FromDistrict);
        }elseif(!empty($FromCity)){
            $Model          = $Model->where('from_city_id',$FromCity);
        }

        if(!empty($ToDistrict)){
            $Model          = $Model->where('to_district_id',$ToDistrict);
        }elseif(!empty($ToCity)){
            $ListDistrictId = \DistrictModel::where('city_id', $ToCity)->remember(60)->lists('id');
            if(!empty($ListDistrictId)){
                $Model          = $Model->whereIn('to_district_id',$ListDistrictId);
                unset($ListDistrictId);
            }else{
                return false;
            }
        }

        if(!empty($TrackingCode)){
            $Model          = $Model->where(function($query) use($TrackingCode){
                $query->where('tracking_code',$TrackingCode)
                      ->orWhere('courier_tracking_code', $TrackingCode);
            });
        }

        if(!empty($PostOffice)){
            $Model          = $Model->where('post_office_id','>',0);
        }

        if(empty($TimeCreateStart) && empty($TimeAcceptStart)){
            return false;
        }

        $TimeRange  = $this->__time_range();
        if(empty($TimeAcceptEnd) && !empty($TimeAcceptStart)){
            $TimeAcceptEnd  = $TimeCreateEnd > 0 ? $TimeCreateEnd : $this->time();
        }

        if(empty($TimeAcceptStart) && !empty($TimeAcceptEnd)){
            $TimeAcceptStart  = $TimeCreateStart > 0 ? $TimeCreateStart : $TimeRange;
        }

        if(!empty($TimeCreateStart)){
            $Model              = $Model->where('time_create','>=',$TimeCreateStart);
        }

        if(!empty($TimeCreateEnd)){
            $Model              = $Model->where('time_create','<=',$TimeCreateEnd);
        }

        if(!empty($TimeAcceptStart)){
            $Model              = $Model->where('time_accept','>=', $TimeAcceptStart)
                                        ->where('time_accept','<=', $TimeAcceptEnd);
        }else{
            $Model              = $Model->where(function($query) use($TimeRange) {
                $query->where('time_accept','>=', $TimeRange)
                    ->orWhere('time_accept',0);
            });
        }

        if(!empty($TimeSuccessStart)){
            $Model          = $Model->where('time_success','>=',$TimeSuccessStart);
        }

        if(!empty($TimeSuccessEnd)){
            $Model          = $Model->where('time_success','<=',$TimeSuccessEnd);
        }

        if(!empty($PickupStart)){
            $Model          = $Model->where('time_pickup','>=',$PickupStart);
        }

        if(!empty($PickupEnd)){
            $Model          = $Model->where('time_pickup','<=',$PickupEnd);
        }

        if(!empty($VerifyMoneyCollect)){
            if($VerifyMoneyCollect == 1){
                $Model          = $Model->where('verify_money_collect', '>',0);
            }else{
                $Model          = $Model->where('verify_money_collect', 0);
            }
        }

        if(isset($Verify)){
            if($Verify == 0){
                $Model          = $Model->where('verify_id', 0);
            }else{
                $Model          = $Model->where('verify_id','>', 0);
            }
        }

        return $Model;
    }

    /*
     * get  to_address

    private function getToaddress($ListToAddress){
        $AddressModel   = new AddressModel;
        $ListAddress    = $AddressModel::whereIn('id',$ListToAddress)->get()->toArray();
        if(!empty($ListAddress)){
            foreach($ListAddress as $val){
                $this->list_to_address[(int)$val['id']]    = $val;
                if($val['province_id'] > 0){
                    $this->list_district_id[]   = (int)$val['province_id'];
                }

                if($val['province_id'] > 0){
                    $this->list_ward_id[]   = (int)$val['ward_id'];
                }
            }
        }
    }

    private function getFromaddress($ListFromAddress){
        $AddressModel   = new UserInventoryModel;
        $ListAddress    = $AddressModel::whereIn('id',$ListFromAddress)->get(['id','name','user_name','phone'])->toArray();
        if(!empty($ListAddress)){
            foreach($ListAddress as $val){
                $this->list_from_address[(int)$val['id']]    = $val;
            }
        }
    }*/

    /*
     * get list order
     */
    public function getIndex(){
        set_time_limit (180);
        ini_set('max_execution_time',3000);
        ini_set('memory_limit', "256M");

        $itemPage           = 20;
        $this->message      = 'success';

        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 'ALL';
        $TypeCod            = Input::has('type_cod')            ? (int)Input::get('type_cod')               : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))           : '';
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';

        $Model              = $this->getModel();

        if(!$Model){
            return $this->ResponseData();
        }

        if($CourierId != 'ALL'){
            $Model          = $Model->where('courier_id',$CourierId);
        }

        if(!empty($ListStatus)){
            $ListStatus = explode(',',$ListStatus);
            $Model          = $Model->whereIn('status',$ListStatus);
            unset($ListStatus);
        }

        //Thu hộ và không thu hộ
        if(!empty($TypeCod)){
            $TyPeCoDModel = clone $Model;
            $RangerOrder = $TyPeCoDModel->first([DB::raw('MAX(id) as max , MIN(id) as min')]);

            if(!isset($RangerOrder->min)){
                return $this->ResponseData();
            }

            $DetailModel    = new DetailModel;
            $DetailModel    = $DetailModel::where('order_id','>=',$RangerOrder->min)->where('order_id','<=',$RangerOrder->max);
            unset($RangerOrder);

            if($TypeCod == 1){
                $DetailModel    = $DetailModel->where('money_collect','>',0);
            }else{
                $DetailModel    = $DetailModel->where('money_collect',0);
            }
            unset($TypeCod);

            $ListId = $DetailModel->lists('order_id');
            if(empty($ListId)){
                return $this->ResponseData();
            }
            $Model  = $Model->whereRaw("id in (". implode(",", $ListId) .")");
            unset($DetailModel);
            unset($ListId);
        }

        /**
         * get data
         */
        if($Cmd == 'export'){
            return $this->ExportExcel($Model);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model      = $Model->skip($offset)->take($itemPage);
            $Data       = [];

            $this->data = $Model->orderBy('time_create','DESC')->with(['OrderDetail', 'OrderFulfillment'])->get(['id','status','tracking_code','total_amount','verify_money_collect','verify_fee','invoice_id','verify_id','time_create','time_accept','time_success','time_pickup'])->toArray();
        }

        return $this->ResponseData();
    }

    public function getCountGroupStatus(){
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))           : '';
        $TypeCod            = Input::has('type_cod')            ? (int)Input::get('type_cod')               : 0;

        $Model              = $this->getModel();
        if($Model){
            if(!empty($CourierId)){
                $Model          = $Model->where('courier_id',$CourierId);
            }

            //Thu hộ và không thu hộ
            if(!empty($TypeCod)){
                $TyPeCoDModel = clone $Model;
                $RangerOrder = $TyPeCoDModel->first([DB::raw('MAX(id) as max , MIN(id) as min')]);

                if(!isset($RangerOrder->min)){
                    return $this->ResponseData();
                }

                $DetailModel    = new DetailModel;
                $DetailModel    = $DetailModel::where('order_id','>=',$RangerOrder->min)->where('order_id','<=',$RangerOrder->max);
                unset($RangerOrder);
                if($TypeCod == 1){
                    $DetailModel    = $DetailModel->where('money_collect','>',0);
                }else{
                    $DetailModel    = $DetailModel->where('money_collect',0);
                }

                $ListId = $DetailModel->lists('order_id');
                if(empty($ListId)){
                    return $this->ResponseData();
                }
                $Model  = $Model->whereRaw("id in (". implode(",", $ListId) .")");
                unset($ListId);
                unset($DetailModel);
            }

            // Sum data
            $ModelSum       = clone $Model;
            //$GroupStatus    = $Model->groupBy('status')->get(array('status',DB::raw('count(*) as count, sum(total_amount) as total_amount')))->toArray();
            $AmountGroup    = [];
            $AmountAll      = 0;

            /*if(empty($GroupStatus)){
                return Response::json([
                    'error'         => false,
                    'code'          => 'success',
                    'error_message' => 'Thành công',
                    'sum_total'     => [],
                    'total'         => 0,
                    'data'          => []
                ]);
            }

            foreach($GroupStatus as $val){
                if(!isset($this->total_group[(int)$val['status']])){
                    $this->total_group[(int)$val['status']] = 0;
                }
                $this->total_group[(int)$val['status']] += $val['count'];
                $this->total_all                            += $val['count'];
                $AmountGroup[(int)$val['status']]            = $val['total_amount'];
                $AmountAll                                  += $val['total_amount'];
            }
            unset($Model);
            unset($GroupStatus);*/

            if(!empty($ListStatus)){
                $ListStatus = explode(',',$ListStatus);
                $ModelSum   = $ModelSum->whereIn('status',$ListStatus);
            }

            $SuccessModel   = clone $ModelSum;
            $ReturnModel    = clone $ModelSum;
            $WarehouseModel = clone $ModelSum;

            $SuccessModel       = $SuccessModel->where('status','<>',66);
            $ReturnModel        = $ReturnModel->where('status',66);
            $WarehouseModel     = $WarehouseModel->where('domain','boxme.vn')->whereIn('status', [52,53,66,67])->where('verify_return',1);

            $IdSuccess      = $SuccessModel->lists('id');
            $IdReturn       = $ReturnModel->lists('id');
            $IdWarehouse    = $WarehouseModel->lists('id');

            if(empty($IdSuccess) && empty($IdReturn) && empty($IdWarehouse)){
                return $this->ResponseData();
            }

            $DetailModel        = new DetailModel;
            $SuccessModel       = clone $DetailModel;
            $ReturnModel        = clone $DetailModel;
            $WarehouseModel     = new \ordermodel\FulfillmentModel;

            $DataSum            = [
                'sc_pvc'    => 0,
                'sc_cod'    => 0,
                'sc_pvk'    => 0,
                'sc_pbh'    => 0,
                'sc_pch'    => 0,
                'sc_remote'         => 0,
                'sc_clearance'      => 0,
                'sc_discount_pvc'   => 0,
                'sc_discount_cod'   => 0,
                'money_collect'     => 0,

                'sc_plk'            => 0,
                'sc_pdg'            => 0,
                'sc_pxl'            => 0,
                'sc_discount_plk'   => 0,
                'sc_discount_pdg'   => 0,
                'sc_discount_pxl'   => 0,

                /*'hvc_pvc'           => 0,
                'hvc_cod'           => 0,
                'hvc_pbh'           => 0,
                'hvc_pvk'           => 0,
                'hvc_pch'           => 0,

                'historical_plk'    => 0,
                'historical_pdg'    => 0,
                'historical_pxl'    => 0,
                'historical_discount_plk'   => 0,
                'historical_discount_pdg'   => 0,
                'historical_discount_pxl'   => 0,*/

                'total_amount'      => 0
            ];

            if(!empty($IdSuccess)){
                $SumSuccess = $SuccessModel->whereRaw("order_id in (". implode(",", $IdSuccess) .")")
                    ->first(array(DB::raw(
                        'sum(sc_pvc) as sc_pvc,
                        sum(sc_cod) as sc_cod,
                        sum(sc_pbh) as sc_pbh,
                        sum(sc_pvk) as sc_pvk,
                        sum(sc_remote) as sc_remote,
                        sum(sc_clearance) as sc_clearance,

                        sum(sc_discount_pvc) as sc_discount_pvc,
                        sum(sc_discount_cod) as sc_discount_cod,
                        sum(money_collect) as money_collect
                        ')));

                if(isset($SumSuccess->sc_pvc)){
                    $DataSum['sc_pvc']    += $SumSuccess->sc_pvc;
                    $DataSum['sc_cod']    += $SumSuccess->sc_cod;
                    $DataSum['sc_pbh']    += $SumSuccess->sc_pbh;
                    $DataSum['sc_pvk']    += $SumSuccess->sc_pvk;
                    $DataSum['sc_remote']       += $SumSuccess->sc_remote;
                    $DataSum['sc_clearance']    += $SumSuccess->sc_clearance;

                    $DataSum['sc_discount_pvc']     += $SumSuccess->sc_discount_pvc;
                    $DataSum['sc_discount_cod']     += $SumSuccess->sc_discount_cod;
                    $DataSum['money_collect']       += $SumSuccess->money_collect;
                }
            }

            if(!empty($IdReturn)){
                $SumReturn = $ReturnModel->whereRaw("order_id in (". implode(",", $IdReturn) .")")
                    ->first(array(DB::raw(
                        'sum(sc_pvc) as sc_pvc,
                        sum(sc_pvk) as sc_pvk,
                        sum(sc_pch) as sc_pch,
                        sum(sc_remote) as sc_remote,
                        sum(sc_clearance) as sc_clearance,

                        sum(sc_discount_pvc) as sc_discount_pvc
                        ')));

                if(isset($SumReturn->sc_pvc)){
                    $DataSum['sc_pvc']    += $SumReturn->sc_pvc;
                    $DataSum['sc_pvk']    += $SumReturn->sc_pvk;
                    $DataSum['sc_pch']    += $SumReturn->sc_pch;
                    $DataSum['sc_remote']       += $SumReturn->sc_remote;
                    $DataSum['sc_clearance']    += $SumReturn->sc_clearance;

                    $DataSum['sc_discount_pvc']     += $SumReturn->sc_discount_pvc;
                }
            }

            if(!empty($IdWarehouse)){
                $SumWareHouse   = $WarehouseModel->whereRaw("order_id in (". implode(",", $IdWarehouse) .")")
                    ->first(array(DB::raw(
                        'sum(sc_plk) as sc_plk,
                        sum(sc_pdg) as sc_pdg,
                        sum(sc_pxl) as sc_pxl,
                        sum(sc_discount_plk) as sc_discount_plk,
                        sum(sc_discount_pdg) as sc_discount_pdg,
                        sum(sc_discount_pxl) as sc_discount_pxl
                        ')));

                if(isset($SumWareHouse->sc_plk)){
                    $DataSum['sc_plk']    += $SumWareHouse->sc_plk;
                    $DataSum['sc_pdg']    += $SumWareHouse->sc_pdg;
                    $DataSum['sc_pxl']    += $SumWareHouse->sc_pxl;
                    $DataSum['sc_discount_plk']    += $SumWareHouse->sc_discount_plk;
                    $DataSum['sc_discount_pdg']    += $SumWareHouse->sc_discount_pdg;
                    $DataSum['sc_discount_pxl']    += $SumWareHouse->sc_discount_pxl;
                }
            }

            unset($IdSuccess);
            unset($IdReturn);
            unset($SumSuccess);
            unset($SumReturn);
            unset($SumWareHouse);

            if(!empty($ListStatus)){
                $DataSum['total_amount']    = 0;
                foreach($ListStatus as $val){
                    if(isset($AmountGroup[(int)$val])){
                        $DataSum['total_amount'] += $AmountGroup[(int)$val];
                    }
                }
            }else{
                $DataSum['total_amount']    = $AmountAll;
            }
        }

        return Response::json([
            'error'         => false,
            'code'          => 'success',
            'error_message' => 'Thành công',
            'total'         => $this->total_all,
            'data'          => $this->total_group,
            'sum_total'     => isset($DataSum) ? $DataSum : []
        ]);
    }

    public function getCountGroup(){
        $CourierId          = Input::has('courier')             ? (int)Input::get('courier')                : 0;
        $ListStatus         = Input::has('list_status')         ? trim(Input::get('list_status'))           : '';
        $TypeCod            = Input::has('type_cod')            ? (int)Input::get('type_cod')               : 0;

        $Model              = $this->getModel();
        if($Model){
            if(!empty($ListStatus)){
                $ListStatus = explode(',',$ListStatus);
                $Model          = $Model->whereIn('status',$ListStatus);
                unset($ListStatus);
            }

            //Thu hộ và không thu hộ
            if(!empty($TypeCod)){
                $TyPeCoDModel = clone $Model;
                $RangerOrder = $TyPeCoDModel->first([DB::raw('MAX(id) as max , MIN(id) as min')]);

                if(!isset($RangerOrder->min)){
                    return $this->ResponseData();
                }

                $DetailModel    = new DetailModel;
                $DetailModel    = $DetailModel::where('order_id','>=',$RangerOrder->min)->where('order_id','<=',$RangerOrder->max);
                unset($RangerOrder);
                unset($TyPeCoDModel);
                if($TypeCod == 1){
                    $DetailModel    = $DetailModel->where('money_collect','>',0);
                }else{
                    $DetailModel    = $DetailModel->where('money_collect',0);
                }

                $ListId = $DetailModel->lists('order_id');
                if(empty($ListId)){
                    return $this->ResponseData();
                }
                $Model  = $Model->whereRaw("id in (". implode(",", $ListId) .")");
                unset($DetailModel);
                unset($ListId);
            }

            $ModelSum   = clone $Model;

            // Count Group
            $GroupStatus    = $Model->groupBy('courier_id')->get(array('courier_id',DB::raw('count(*) as count, sum(total_amount) as total_amount')))->toArray();
            $AmountGroup    = [];
            $AmountAll      = 0;

            if(empty($GroupStatus)){
                return Response::json([
                    'error'         => false,
                    'code'          => 'success',
                    'error_message' => 'Thành công',
                    'sum_total'     => [],
                    'total'         => 0,
                    'data'          => []
                ]);
            }

            foreach($GroupStatus as $val){
                $this->total_group[(int)$val['courier_id']] = $val['count'];
                $this->total_all                            += $val['count'];
                $AmountGroup[(int)$val['courier_id']]       = $val['total_amount'];
                $AmountAll                                  += $val['total_amount'];
            }
            unset($GroupStatus);
            unset($Model);

            // Sum total
            if(!empty($CourierId)){
                $ModelSum   = $ModelSum->where('courier_id',$CourierId);
            }

            $SuccessModel   = clone $ModelSum;
            $ReturnModel    = clone $ModelSum;
            $WarehouseModel = clone $ModelSum;

            $SuccessModel       = $SuccessModel->where('status','<>',66);
            $ReturnModel        = $ReturnModel->where('status',66);
            $WarehouseModel     = $WarehouseModel->where('domain','boxme.vn')->whereIn('status', [52,53,66,67])->where('verify_return',1);

            $IdSuccess      = $SuccessModel->lists('id');
            $IdReturn       = $ReturnModel->lists('id');
            $IdWarehouse    = $WarehouseModel->lists('id');

            if(empty($IdSuccess) && empty($IdReturn) && empty($IdWarehouse)){
                return $this->ResponseData();
            }

            $DetailModel        = new DetailModel;
            $SuccessModel       = clone $DetailModel;
            $ReturnModel        = clone $DetailModel;
            $WarehouseModel     = new \ordermodel\FulfillmentModel;

            $DataSum            = [
                'sc_pvc'    => 0,
                'sc_cod'    => 0,
                'sc_pvk'    => 0,
                'sc_pbh'    => 0,
                'sc_pch'    => 0,
                'sc_remote'         => 0,
                'sc_clearance'      => 0,
                'sc_discount_pvc'   => 0,
                'sc_discount_cod'   => 0,
                'money_collect'     => 0,

                'sc_plk'            => 0,
                'sc_pdg'            => 0,
                'sc_pxl'            => 0,
                'sc_discount_plk'   => 0,
                'sc_discount_pdg'   => 0,
                'sc_discount_pxl'   => 0,

                'hvc_pvc'           => 0,
                'hvc_cod'           => 0,
                'hvc_pbh'           => 0,
                'hvc_pvk'           => 0,
                'hvc_pch'           => 0,

                'historical_plk'    => 0,
                'historical_pdg'    => 0,
                'historical_pxl'    => 0,
                'historical_discount_plk'   => 0,
                'historical_discount_pdg'   => 0,
                'historical_discount_pxl'   => 0,

                'total_amount'      => 0
            ];

            if(!empty($IdSuccess)){
                $SumSuccess = $SuccessModel->whereRaw("order_id in (". implode(",", $IdSuccess) .")")
                    ->first(array(DB::raw(
                        'sum(sc_pvc) as sc_pvc,
                        sum(sc_cod) as sc_cod,
                        sum(sc_pbh) as sc_pbh,
                        sum(sc_pvk) as sc_pvk,
                        sum(sc_remote) as sc_remote,
                        sum(sc_clearance) as sc_clearance,

                        sum(hvc_pvc) as hvc_pvc,
                        sum(hvc_cod) as hvc_cod,
                        sum(hvc_pbh) as hvc_pbh,
                        sum(hvc_pvk) as hvc_pvk,

                        sum(sc_discount_pvc) as sc_discount_pvc,
                        sum(sc_discount_cod) as sc_discount_cod,
                        sum(money_collect) as money_collect
                        ')));

                if(isset($SumSuccess->sc_pvc)){
                    $DataSum['sc_pvc']    += $SumSuccess->sc_pvc;
                    $DataSum['sc_cod']    += $SumSuccess->sc_cod;
                    $DataSum['sc_pbh']    += $SumSuccess->sc_pbh;
                    $DataSum['sc_pvk']    += $SumSuccess->sc_pvk;
                    $DataSum['sc_remote']       += $SumSuccess->sc_remote;
                    $DataSum['sc_clearance']    += $SumSuccess->sc_clearance;

                    $DataSum['hvc_pvc']    += $SumSuccess->hvc_pvc;
                    $DataSum['hvc_cod']    += $SumSuccess->hvc_cod;
                    $DataSum['hvc_pbh']    += $SumSuccess->hvc_pbh;
                    $DataSum['hvc_pvk']    += $SumSuccess->hvc_pvk;

                    $DataSum['sc_discount_pvc']     += $SumSuccess->sc_discount_pvc;
                    $DataSum['sc_discount_cod']     += $SumSuccess->sc_discount_cod;
                    $DataSum['money_collect']       += $SumSuccess->money_collect;
                }
            }

            if(!empty($IdReturn)){
                $SumReturn = $ReturnModel->whereRaw("order_id in (". implode(",", $IdReturn) .")")
                    ->first(array(DB::raw(
                        'sum(sc_pvc) as sc_pvc,
                        sum(sc_pvk) as sc_pvk,
                        sum(sc_pch) as sc_pch,
                        sum(sc_remote) as sc_remote,
                        sum(sc_clearance) as sc_clearance,

                        sum(hvc_pvc) as hvc_pvc,
                        sum(hvc_pvk) as hvc_pvk,
                        sum(hvc_pch) as hvc_pch,

                        sum(sc_discount_pvc) as sc_discount_pvc
                        ')));

                if(isset($SumReturn->sc_pvc)){
                    $DataSum['sc_pvc']    += $SumReturn->sc_pvc;
                    $DataSum['sc_pvk']    += $SumReturn->sc_pvk;
                    $DataSum['sc_pch']    += $SumReturn->sc_pch;
                    $DataSum['sc_remote']       += $SumReturn->sc_remote;
                    $DataSum['sc_clearance']    += $SumReturn->sc_clearance;

                    $DataSum['hvc_pvc']    += $SumReturn->hvc_pvc;
                    $DataSum['hvc_pvk']    += $SumReturn->hvc_pvk;
                    $DataSum['hvc_pch']    += $SumReturn->hvc_pch;

                    $DataSum['sc_discount_pvc']     += $SumReturn->sc_discount_pvc;
                }
            }

            if(!empty($IdWarehouse)){
                $SumWareHouse   = $WarehouseModel->whereRaw("order_id in (". implode(",", $IdWarehouse) .")")
                    ->first(array(DB::raw(
                        'sum(sc_plk) as sc_plk,
                        sum(sc_pdg) as sc_pdg,
                        sum(sc_pxl) as sc_pxl,
                        sum(sc_discount_plk) as sc_discount_plk,
                        sum(sc_discount_pdg) as sc_discount_pdg,
                        sum(sc_discount_pxl) as sc_discount_pxl,

                        sum(historical_plk) as historical_plk,
                        sum(historical_pdg) as historical_pdg,
                        sum(historical_pxl) as historical_pxl,

                        sum(historical_discount_plk) as historical_discount_plk,
                        sum(historical_discount_pdg) as historical_discount_pdg,
                        sum(historical_discount_pxl) as historical_discount_pxl
                        ')));

                if(isset($SumWareHouse->sc_plk)){
                    $DataSum['sc_plk']    += $SumWareHouse->sc_plk;
                    $DataSum['sc_pdg']    += $SumWareHouse->sc_pdg;
                    $DataSum['sc_pxl']    += $SumWareHouse->sc_pxl;
                    $DataSum['sc_discount_plk']    += $SumWareHouse->sc_discount_plk;
                    $DataSum['sc_discount_pdg']    += $SumWareHouse->sc_discount_pdg;
                    $DataSum['sc_discount_pxl']    += $SumWareHouse->sc_discount_pxl;

                    $DataSum['historical_plk']    += $SumWareHouse->historical_plk;
                    $DataSum['historical_pdg']    += $SumWareHouse->historical_pdg;
                    $DataSum['historical_pxl']    += $SumWareHouse->historical_pxl;
                    $DataSum['historical_discount_plk']    += $SumWareHouse->historical_discount_plk;
                    $DataSum['historical_discount_pdg']    += $SumWareHouse->historical_discount_pdg;
                    $DataSum['historical_discount_pxl']    += $SumWareHouse->historical_discount_pxl;
                }
            }

            unset($IdSuccess);
            unset($IdReturn);
            unset($SumSuccess);
            unset($SumReturn);
            unset($SumWareHouse);

            if(!empty($ListStatus)){
                $DataSum['total_amount']    = 0;
                foreach($ListStatus as $val){
                    if(isset($AmountGroup[(int)$val])){
                        $DataSum['total_amount'] += $AmountGroup[(int)$val];
                    }
                }
            }else{
                $DataSum['total_amount']    = $AmountAll;
            }
        }

        return Response::json([
            'error'         => false,
            'code'          => 'success',
            'error_message' => 'Thành công',
            'sum_total'     => isset($DataSum) ? $DataSum : [],
            'total'         => $this->total_all,
            'data'          => $this->total_group
        ]);
    }

    private function ResponseData(){
        $Cmd                = Input::has('cmd')                 ? strtolower(trim(Input::get('cmd')))                   : '';

        if($Cmd == 'export'){
            return $this->ExportExcel([]);
        }

        return Response::json([
            'error'         => false,
            'code'          => 'SUCCESS',
            'error_message' => 'Thành công',
            'total'         => $this->total,
            'data'          => $this->data
        ]);
    }

    public function ExportExcel($Model){
        $Type               = Input::has('type')                ? (int)Input::get('type')                   : 1;
        if($Type == 2){
            return $this->ExportExcelMerchant($Model);
        }elseif($Type == 3){// Lấy theo danh sách Item
            return $this->ExportExcelItem($Model);
        }

        $ListCityId     = [];
        $ListDistrictId = [];

        $City       = [];
        $District   = [];
        $User       = [];
        $ListUserId = [];
        $Data       = [];

        if(!empty($Model)){
            $ListField  = ['id','tracking_code','time_create','time_accept','time_pickup','time_success','order_code','courier_id',
                            'from_district_id','from_user_id','courier_tracking_code','service_id','status',
                            'total_weight', 'from_city_id', 'to_city_id','domain','to_district_id','warehouse', 'total_quantity'
                            ];

            $Model->with(['OrderDetail','OrderFulfillment'])->select($ListField)->chunk('1000', function($query) use(&$Data, &$ListCityId, &$ListDistrictId, &$ListUserId){
                foreach($query as $val){
                    $val                = $val->toArray();
                    // Check dịch vụ
                    $Data[]             = $val;

                    $ListCityId[]       = $val['from_city_id'];
                    $ListCityId[]       = $val['to_city_id'];
                    $ListDistrictId[]   = $val['from_district_id'];
                    $ListDistrictId[]   = $val['to_district_id'];
                    $ListUserId[]       = $val['from_user_id'];
                }
            });
            unset($Model);
            unset($ListField);

            if(!empty($ListDistrictId)){
                $ListDistrictId = array_unique($ListDistrictId);
                $District   = $this->getProvince($ListDistrictId);
                //unset($ListDistrictId);

                //Get list location
                $dbLocation = \AreaLocationModelDev::whereIn('province_id', $ListDistrictId)->where('active',1)->get(['province_id','location_id'])->toArray();
                if(!empty($dbLocation)){
                    foreach($dbLocation as $val){
                        $Location[$val['province_id']]  = $val['location_id'];
                    }
                }
            }

            if(!empty($ListCityId)){
                $ListCityId = array_unique($ListCityId);
                $City   = $this->getCityById($ListCityId);
                unset($ListDistrictId);
            }

            if(!empty($ListUserId)){
                $ListUserId     = array_unique($ListUserId);
                $User           = $this->getUser($ListUserId);
                unset($ListUserId);
            }



            //Get service
            if(!empty($Location)){
                $this->courier  = $this->getCourier(false);
                foreach($Data as $key => $val){
                    if(isset($this->courier[(int)$val['courier_id']]) && isset($Location[$val['to_district_id']])){
                        $funcName   = '__get_service_'.($this->courier[(int)$val['courier_id']]['prefix']);
                        if(method_exists($this ,$funcName)){
                            $Data[$key]['courier_service'] = $this->$funcName($val['service_id'], $val['total_weight'], $val['from_city_id'], $val['to_city_id'], $Location[$val['to_district_id']]);
                        }
                    }
                }
            }
        }



        return  Response::json([
            'error'         => false,
            'code'          => 'success',
            'error_message' => 'Thành công',
            'data'          => $Data,
            'city'          => $City,
            'district'      => $District,
            'user'          => $User
        ]);
    }

    public function ExportExcelMerchant($Model){
        $Address        = [];
        $City           = [];
        $District       = [];
        $Ward           = [];
        $User           = [];

        $ListCityId     = [];
        $ListDistrictId = [];
        $ListWardId     = [];
        $ListUserId     = [];

        $UserInfo   = [];
        $Data       = [];

        if(!empty($Model)){

            $ListField  = ['id','tracking_code','time_create','time_accept','time_pickup','time_success','order_code','courier_id','from_district_id','to_address_id','from_user_id',
                'courier_tracking_code','service_id','status','total_weight','total_amount', 'from_city_id', 'domain', 'verify_id', 'from_ward_id', 'from_address',
                'to_name','to_email','to_phone', 'to_address_id', 'product_name', 'total_amount','total_quantity','to_city_id','to_district_id'
            ];

            $Model->with(['OrderDetail','__post_office','OrderFulfillment'])->select($ListField)
                  ->chunk('1000', function($query) use(&$Data, &$ListCityId, &$ListDistrictId, &$ListWardId, &$ListToAddress, &$ListUserId){
                foreach($query as $val){
                    $val                = $val->toArray();
                    $Data[]             = $val;

                    $ListCityId[]       = $val['from_city_id'];
                    $ListCityId[]       = $val['to_city_id'];

                    $ListDistrictId[]   = $val['from_district_id'];
                    $ListDistrictId[]   = $val['to_district_id'];

                    $ListWardId[]       = $val['from_ward_id'];

                    $ListToAddress[]    = $val['to_address_id'];
                    $ListUserId[]       = $val['from_user_id'];
                }
            });

            unset($Model);
            unset($ListField);

            if(isset($ListToAddress) && !empty($ListToAddress)){
                $AddressModel   = new AddressModel;
                $ListAddress    = $AddressModel::whereRaw("id in (". implode(",", $ListToAddress) .")")->get()->toArray();
                unset($ListToAddress);
            }


            if(isset($ListAddress) && !empty($ListAddress)){
                foreach($ListAddress as $val){
                    if(!empty($val)){
                        $Address[$val['id']]    = $val;
                        $ListWardId[]           = (int)$val['ward_id'];
                    }
                }
                unset($ListAddress);
            }

            if(!empty($ListCityId)){
                $ListCityId = array_unique($ListCityId);
                $City   = $this->getCityById($ListCityId);
                unset($ListCityId);
            }

            if(!empty($ListDistrictId)){
                $ListDistrictId = array_unique($ListDistrictId);
                $District   = $this->getProvince($ListDistrictId);
                unset($ListDistrictId);
            }

            if(!empty($ListUserId)){
                $ListUserId     = array_unique($ListUserId);
                $User           = $this->getUser($ListUserId);
                $UserInfo       = $this->getUserInfo($ListUserId);
                $Loyalty        = $this->getUserLoyalty($ListUserId);
                unset($ListUserId);
            }

            if(!empty($ListWardId)){
                $ListWardId     = array_unique($ListWardId);
                $Ward   = $this->getWard($ListWardId);
                unset($ListWardId);
            }
        }


        return  Response::json([
            'error'         => false,
            'code'          => 'success',
            'error_message' => 'Thành công',
            'data'          => $Data,
            'to_address'    => $Address,
            'city'          => $City,
            'district'      => $District,
            'ward'          => $Ward,
            'user'          => $User,
            'user_info'     => $UserInfo,
            'user_loyalty'  => $Loyalty

        ]);
    }

    public function ExportExcelItem($Model){
        $Data       = [];

        if(!empty($Model)){
            $ListOrderId        = $Model->lists('id');
            if(!empty($ListOrderId)){
                $Data   = \ordermodel\FulfillmentModel::whereRaw("order_id in (". implode(",", $ListOrderId) .")")
                    ->with('__get_detail')->get()->toArray();
            }
        }


        return  Response::json([
            'error'         => false,
            'code'          => 'success',
            'error_message' => 'Thành công',
            'data'          => $Data

        ]);
    }
}
