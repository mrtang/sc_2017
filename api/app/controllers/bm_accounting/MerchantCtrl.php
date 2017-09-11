<?php namespace bm_accounting;

use bm_accmodel\MerchantModel;
use metadatamodel\OrganizationUserModel;

class MerchantCtrl extends BaseCtrl {
    private $data_sum       = [];
    private $config         = [];

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
        $page           = Input::has('page')            ? (int)Input::get('page')                : 1;
        $itemPage       = Input::has('limit')           ? Input::get('limit')                    : 20;
        $TimeStart      = Input::has('time_start')      ? trim(Input::get('time_start'))         : '';
        $TimeEnd        = Input::has('time_end')        ? trim(Input::get('time_end'))           : '';
        $KeyWord        = Input::has('search')          ? trim(Input::get('search'))             : '';
        $Type           = Input::has('type')            ? (int)Input::get('type')                : '';
        $cmd            = Input::has('cmd')             ? strtoupper(trim(Input::get('cmd')))    : '';

        $Model = new MerchantModel;

        if(!empty($TimeStart)){
            $Model = $Model->where('time_create','>=',$TimeStart);
        }

        if(!empty($TimeEnd)){
            $Model = $Model->where('time_create','<',$TimeEnd);
        }

        if(!empty($KeyWord)){
            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $User          = \User::where('email',$KeyWord)->lists('organization');
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $User          = \User::where('phone',$KeyWord)->lists('organization');
            }else{
                $User          = OrganizationUserModel::where('fullname','LIKE','%'.$KeyWord.'%')->lists('id');
            }

            if(!empty($User)){
                $User    = array_unique($User);
                $Model   = $Model->whereIn('merchant_id',$User);
            }else{
                return $this->ResponseData();
            }
        }

        if(!empty($Type)){
            if($Type == 1){ //Khách hàng nợ
                $Model = $Model->where('balance','<',0);
                $Model = $Model->orderBy('balance','ASC');
            }
        }else{
            $Model = $Model->orderBy('id','ASC');
        }

        if($cmd == 'EXPORT'){
            return $this->getExcel($Model->with(['getOrganization'])->get()->toArray());
        }

        $ModelTotal = clone $Model;
        $ModelSum   = clone $Model;
        $this->total        = $ModelTotal->count();

        if($this->total > 0){
            $this->data_sum     = $ModelSum->first(array(DB::raw(
                'sum(balance) as balance')));

            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }

            $this->data = $Model->with('getOrganization')->get()->toArray();
        }

        return $this->ResponseData();
	}

    public  function __construct(){
        $this->config       = \Config::get('config_api.domain.boxme.accounting');
        Input::merge(Input::json()->all());
    }

    public function getDetail(){
        $Token              = Input::has('Token')           ? trim(Input::get('Token'))                 : '';
        $MerchantKey        = Input::has('MerchantKey')     ? trim(Input::get('MerchantKey'))           : '';
        $Model              = new MerchantModel;

        if($Token != $this->config){ // gọi từ seller
            return Response::json([
                'error'         => true,
                'message'       => 'TOKEN_ERROR',
                'data'          => [],
            ]);
        }

        $Organization   = $this->__check_merchant_key();

            if(!$Organization){
                return Response::json([
                    'error'         => true,
                    'message'       => 'MerchantKey_NOT_EXISTS',
                    'data'          => [],
                ]);
            }

            $Model  = $Model->where('merchant_id', $Organization);

            return Response::json([
                'error'         => false,
                'message'       => 'SUCCESS',
                'data'          => $Model->first(),
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function postEdit($id)
    {
        $Quota          = Input::has('quota')   ? (int)Input::get('quota')  : null;
        $Level          = (int)Input::json()->get('level');
        $QuotaStart     = (int)Input::json()->get('quota_start');;
        $QuotaEnd       = (int)Input::json()->get('quota_end');;

        $UserInfo   = $this->UserInfo();
        $Model      = new MerchantModel;

        $Merchant = $Model::find($id);

        if(empty($Merchant)){
            return Response::json(['error' => true, 'message' => 'MERCHANT_NOT_EXISTS']);
        }

        if((int)$UserInfo['privilege'] != 2 && $UserInfo['group'] != 13){
            $contents = array(
                'error'     => true,
                'message'   => 'USER_NOT_ALLOWED'
            );
            return Response::json($contents);
        }

        if(isset($Quota)){
            $Merchant->quota        = $Quota;
        }

        if(!empty($Level)){
            $Merchant->level        = $Level;
        }

        if(!empty($QuotaStart)){
            $Merchant->quota_start  = $QuotaStart;
        }

        if(!empty($QuotaEnd)){
            $Merchant->quota_end    = $QuotaEnd;
        }

        $contents   = ['error' => false, 'message'  => 'SUCCESS'];
    
        try{
            $Merchant->time_update = $this->time();
            $Merchant->save();
        }catch (Exception $e){
            $contents   = ['error' => true, 'message'  => 'UPDATE_FAIL'];
        }

        if($Level == 3){ // cấp quyền bảo lãnh
            $User   = \User::where('id',(int)$Merchant->user)->first(['id','email']);
            $Content = $UserInfo['fullname'].' da cap quyen bao lanh tien cho tai khoan '. $User->email;
            $this->SendSmS('0906262181', $Content);
        }

        return Response::json($contents);
    }

    private function getExcel($Data){
        Excel::selectSheetsByIndex(0)->load('/data/www/html/storage/template/bm_accounting/danh_sach_khach_hang.xls', function($reader) use($Data) {
            $reader->sheet(0,function($sheet) use($Data)
            {
                $Freeze  = 0;
                $i = 1;
                foreach ($Data as $val) {
                    $Freeze = $val['balance'] + $val['quota'];
                    if(isset($val['get_organization'])){
                        if($val['get_organization']['payment_type'] == 0){
                            $Freeze -= $val['freeze'];
                        }elseif($val['get_organization']['payment_type'] == 1){
                            $Freeze -= $val['freeze_sqm'];
                        }else{
                            $Freeze -= $val['freeze_cbm'];
                        }
                    }

                    $dataExport = array(
                        $i++,
                        $val['time_create'] > 0 ? date("d/m/y H:m",$val['time_create']) : '',
                        isset($val['get_organization']) ? $val['get_organization']['fullname'] : '',
                        $val['get_organization']['email'],
                        '_'.$val['get_organization']['phone'],
                        number_format($val['balance']),
                        number_format($val['freeze']),
                        number_format($val['freeze_cbm']),
                        number_format($val['freeze_sqm']),
                        number_format($Freeze),
                        number_format($val['quota']),
                        $val['level']

                    );
                    $sheet->appendRow($dataExport);
                }
            });
        },'UTF-8',true)->export('xls');
    }

    private function ResponseData(){
        $Cmd                = Input::has('cmd')                 ? strtolower(trim(Input::get('cmd')))                   : '';

        if($Cmd == 'export'){
            return $this->getExcel([]);
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'item_page'     => 20,
            'total'         => $this->total,
            'data'          => $this->data,
            'data_sum'      => $this->data_sum
        ]);
    }

    public function getReportMerchant(){
        $itemPage       = 20;
        $TimeStart      = Input::has('time_start')      ? trim(Input::get('time_start'))        : '';
        $TimeEnd        = Input::has('time_end')        ? trim(Input::get('time_end'))          : '';
        $Keyword        = Input::has('keyword')         ? trim(Input::get('keyword'))           : '';
        $page           = Input::has('page')            ? (int)Input::get('page')               : 1;
        $cmd            = Input::has('cmd')             ? strtoupper(trim(Input::get('cmd')))   : '';

        $Model = new \bm_accmodel\OrderVerifyModel;

        if(!empty($Keyword)){
            if (filter_var($Keyword, FILTER_VALIDATE_EMAIL)){  // search email
                $User          = \User::where('email',$Keyword)->lists('organization');
            }elseif(filter_var((int)$Keyword, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $User          = \User::where('phone',$Keyword)->lists('organization');
            }else{
                $User          = OrganizationUserModel::where('fullname','LIKE','%'.$Keyword.'%')->lists('id');
            }

            if(!empty($User)){
                $User    = array_unique($User);
                $Model   = $Model->whereIn('organization',$User);
            }else{
                return Response::json([
                    'error'         => false,
                    'message'       => 'success',
                    'item_page'     => $itemPage,
                    'total'         => 0,
                    'data'          => [],
                    'data_sum'      => []
                ]);
            }
        }

        if(!empty($TimeStart)){
            $TimeStart          = date('Y-m-d', $TimeStart);
            $Model              = $Model->where('date','>=',$TimeStart);
        }

        if(!empty($TimeEnd)){
            $TimeEnd            = date('Y-m-d', $TimeEnd);
            $Model              = $Model->where('date','<=',$TimeEnd);
        }

        if($cmd != 'EXPORT'){
            $ModelTotal = clone $Model;
            $ModelSum   = clone $Model;

            $Total      = $ModelTotal->first([DB::raw('COUNT(DISTINCT organization) as total')]);
        }else{
            $Total['total'] = 1;
        }

        $Data       = [];
        $DataSum    = [];

        if(isset($Total['total']) && $Total['total'] > 0){
            $Model = $Model->groupBy('organization')->orderBy('total_uid','DESC');
            if($cmd != 'EXPORT'){
                $DataSum    = $ModelSum->first(array(DB::raw(
                    'sum(warehouse_fee) as warehouse_fee, sum(package_fee) as package_fee, sum(handling_fee) as handling_fee,
                 sum(discount_warehouse) as discount_warehouse, sum(discount_package) as discount_package, sum(discount_handling) as discount_handling,
                 sum(total_uid) as total_uid, sum(total_uid_storage) as total_uid_storage, sum(total_sku) as total_sku,
                  sum(floor) as floor, sum(time_stock) as time_stock'
                )))->toArray();

                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }

            $Data = $Model->with('getOrganization')->get(array('organization',DB::raw(
                'sum(warehouse_fee) as warehouse_fee, sum(package_fee) as package_fee, sum(handling_fee) as  handling_fee, sum(discount_warehouse) as discount_warehouse,
                 sum(discount_package) as discount_package, sum(discount_handling) as discount_handling, sum(total_uid) as total_uid, sum(total_uid_storage) as  total_uid_storage,
                  sum(total_sku) as total_sku, sum(floor) as floor, sum(time_stock) as time_stock'
            )))->toArray();
        }

        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'item_page'     => $itemPage,
            'total'         => $Total['total'],
            'data'          => $Data,
            'data_sum'      => $DataSum
        ]);
    }
}
