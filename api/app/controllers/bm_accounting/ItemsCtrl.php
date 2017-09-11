<?php namespace bm_accounting;
use bm_warehousemodel\WareHouseModel;
use bm_ecommercemodel\SellerProductItemModel;
use bm_sellermodel\InventorySellerModel;
use metadatamodel\OrganizationUserModel;
use User;

class ItemsCtrl extends BaseCtrl {

    private $list_user          = [];
    private $list_organization  = [];

    function __construct(){

    }

    private function getModel(){
        $StockedStart    = Input::has('stocked_start')       ? (int)Input::get('stocked_start')           : 0;
        $StockedEnd      = Input::has('stocked_end')         ? (int)Input::get('stocked_end')             : 0;
        $PickupStart    = Input::has('pickup_start')        ? (int)Input::get('pickup_start')           : 0;
        $PickupEnd      = Input::has('pickup_end')          ? (int)Input::get('pickup_end')             : 0;
        $PackedStart    = Input::has('packed_start')        ? (int)Input::get('packed_start')           : 0;
        $PackedEnd      = Input::has('packed_end')          ? (int)Input::get('packed_end')             : 0;

        $TrackingCode   = Input::has('tracking_code')       ? strtoupper(trim(Input::get('tracking_code'))) : '';
        $FromUser       = Input::has('from_user')           ? trim(Input::get('from_user'))                 : '';
        $FromCity       = Input::has('from_city')           ? (int)Input::get('from_city')              : 0;
        $FromDistrict   = Input::has('from_district')       ? (int)Input::get('from_district')          : 0;
        $ListStatus     = Input::has('list_status')         ? trim(Input::get('list_status'))           : '';


        $Model          = new SellerProductItemModel;
        $UserId         = [];
        $Organization   = [];

        if(!empty($ListStatus)){
            $ListStatus = explode(',',$ListStatus);
            $Model          = $Model->whereIn('status',$ListStatus);
        }

        if(!empty($StockedStart)){
            $StockedStart    = $this->__convert_time($StockedStart);
            $Model          = $Model->where('update_stocked','>=',$StockedStart);
        }

        if(!empty($StockedEnd)){
            $StockedEnd      = $this->__convert_time($StockedEnd);
            $Model          = $Model->where('update_stocked','<=',$StockedEnd);
        }

        if(!empty($PickupStart)){
            $PickupStart    = $this->__convert_time($PickupStart);
            $Model          = $Model->where('update_picked','>=',$PickupStart);
        }

        if(!empty($PickupEnd)){
            $PickupEnd      = $this->__convert_time($PickupEnd);
            $Model          = $Model->where('update_picked','<=',$PickupEnd);
        }

        if(!empty($PackedStart)){
            $PackedStart    = $this->__convert_time($PackedStart);
            $Model          = $Model->where('update_packed','>=',$PackedStart);
        }

        if(!empty($PackedEnd)){
            $PackedEnd      = $this->__convert_time($PackedEnd);
            $Model          = $Model->where('update_packed','<=',$PackedEnd);
        }

        if(!empty($TrackingCode)){
            if(filter_var((int)$TrackingCode, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>7,'max_range'=>20)))){  // search phone
                $Model          = $Model->where('sku',(int)$TrackingCode);
            }else{ // search code
                $Model          = $Model->where('serial_number',$TrackingCode);
            }
        }

        if(!empty($FromUser)){
            $UserModel      = new \User;
            if (filter_var($FromUser, FILTER_VALIDATE_EMAIL)){  // search email
                $UserModel          = $UserModel->where('email',$FromUser);
            }elseif(filter_var((int)$FromUser, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $UserModel          = $UserModel->where('phone',$FromUser);
            }else{ // search code
                $UserModel          = $UserModel->where('fullname',$FromUser);
            }

            $ListUser = $UserModel->get(['id','organization']);
            if(empty($ListUser)){
                return false;
            }else{
                foreach($ListUser as $val){
                    $UserId[]           = (int)$val['id'];
                    $Organization[]     = (int)$val['organization'];
                }

                $Model  = $Model->whereIn('user', $UserId);
            }
        }

        if(!empty($FromCity)){
            $Inventory  = InventorySellerModel::where('province',$FromCity);

            if(!empty($FromDistrict)){
                $Inventory  = $Inventory->where('district',$FromDistrict);
            }

            if(!empty($Organization)){
                $Inventory  = $Inventory->whereIn('organization',$Organization);
            }

            $Inventory  = $Inventory->lists('id');

            if(!empty($Inventory)){
                $Model  = $Model->whereIn('inventory', $Inventory);
            }else{
                return false;
            }
        }

        return $Model;
    }

    private function ResponseData(){
        $Cmd                = Input::has('cmd')                 ? strtolower(trim(Input::get('cmd')))                   : '';

        if($Cmd == 'export'){
            return $this->ExportExcel([]);
        }

        return Response::json([
            'error'             => false,
            'code'              => 'SUCCESS',
            'error_message'     => 'Thành công',
            'total'             => $this->total,
            'data'              => $this->data,
            'list_organization' => $this->list_organization,
            'list_user'         => $this->list_user
        ]);
    }

    public function getIndex(){
        $itemPage           = 20;
        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';

        $Model          = $this->getModel();
        if(!$Model){
            return $this->ResponseData();
        }

        if($Cmd == 'export'){
            return $this->ExportExcel($Model);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0){
            $offset     = ($page - 1)*$itemPage;
            $Model      = $Model->skip($offset)->take($itemPage);
            $this->data = $Model->orderBy('created','DESC')->with(['__inventory', '__product'])->get()->toArray();

            if(!empty($this->data)){
                $UserID         = [];
                $OrganizationId = [];
                foreach($this->data as $val){
                    $UserID[]           = (int)$val['user'];
                    $OrganizationId[]   = (int)$val['__product']['organization'];
                }

                $ListOrganization   = OrganizationUserModel::whereIn('id', $OrganizationId)->get(['id','fullname'])->toArray();
                $ListUser           = User::whereIn('id', $UserID)->get(['id','fullname','email','phone'])->toArray();

                foreach($ListOrganization   as $val){
                    $this->list_organization[$val['id']]    = $val['fullname'];
                }

                foreach($ListUser   as $val){
                    $this->list_user[$val['id']]    = $val;
                }
            }

        }

        return $this->ResponseData();
    }

    public function ExportExcel($Model){
        $User               = [];
        $ListUser           = [];
        $Organization       = [];
        $ListOrganization   = [];
        $Status         = $this->getItemStatusBoxme(false);
        $SellerStock    = $this->getBmSellerStock(false);
        $Data           = [];

        if(!empty($Model)){
            $Model->with(['__inventory', '__product'])->chunk('1000', function($query) use(&$Data, &$User, &$Organization, &$SellerStock){
                foreach($query as $val){
                    $val                    = $val->toArray();
                    $UpdateStocked          = strtotime($val['update_stocked']);
                    $UpdatePacked           = 0;

                    if(!empty($val['update_packed'])){
                        $UpdatePacked   = strtotime($val['update_packed']);
                    }

                    if($UpdatePacked == 0 || $UpdatePacked < $UpdateStocked){
                        $UpdatePacked   = strtotime("now");
                    }

                    $val['time_stock']  = ceil(($UpdatePacked - $UpdateStocked)/3600);
                    $val['fee_stocked'] = $SellerStock[1]['price']*$val['time_stock'];

                    $Data[]                 = $val;
                    $User[]                 = (int)$val['user'];
                    $Organization[]         = (int)$val['__product']['organization'];
                }
            });

            if(!empty($User)){
                $ListUser   =    User::whereRaw("id in (". implode(",", $User) .")")->get(['id','fullname','email','phone'])->toArray();
                $User       = [];
                if(!empty($ListUser)){
                    foreach($ListUser as $val){
                        $User[$val['id']]    = $val;
                    }
                }
            }

            if(!empty($Organization)){
                $ListOrganization   =    OrganizationUserModel::whereRaw("id in (". implode(",", $Organization) .")")->get(['id','fullname'])->toArray();
                $Organization       = [];
                if(!empty($ListOrganization)){
                    foreach($ListOrganization as $val){
                        $Organization[$val['id']]    = $val['fullname'];
                    }
                }
            }
        }

        Excel::selectSheetsByIndex(0)->load('/data/www/html/storage/template/bm_accounting/danh_sach_item.xls', function($reader) use($Data,$User, $Organization, $Status) {
            $reader->sheet(0,function($sheet) use($Data,$User, $Organization, $Status)
            {
                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        $val['sku'],
                        $val['serial_number'],
                        $val['update_stocked'],
                        $val['update_packed'],
                        isset($Status[$val['status']]) ? $Status[$val['status']]['name'] : '',
                        isset($User[$val['user']]) ? $User[$val['user']]['email'] : '',
                        isset($Organization[$val['__product']['organization']]) ? $Organization[$val['__product']['organization']] : '',
                        $val['__product']['name'],
                        $val['__inventory']['address_line1'],
                        isset($val['time_stock']) ? number_format($val['time_stock']) : '',
                        isset($val['fee_stocked'])  ? number_format($val['fee_stocked']) : ''

                    );
                    $sheet->appendRow($dataExport);
                }
            });
        },'UTF-8',true)->export('xls');
    }
}
