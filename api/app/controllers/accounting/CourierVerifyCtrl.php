<?php namespace accounting;
use ordermodel\CourierVerifyModel;
use CourierStatusModel;

class CourierVerifyCtrl extends BaseCtrl {
    private $data_update    = [];
    private $total          = 0;
    private $tracking_code          = '';
    private $courier_track_code     = '';
    private $table_log      = 'log_verify';
    private $table_import   = 'log_import_verify';
    private $courier        = [];

    function __construct(){
        $this->courier  = $this->getCourier(false);
    }

    public function getIndex(){
        dd(':D');
    }

    /** Upload Excel Verify Money Collect
     * @return mixed
     */
    public function postUpload($type){
        $Courier    = Input::has('courier_id')         ? (int)(Input::get('courier_id'))       : 0;

        if(empty($Courier)){
            $contents = array(
                'error'     => true,
                'message'   => 'Courier_Empty'
            );
            return Response::json($contents);
        }

        $validation = \Validator::make(array('type' => $type), array(
                'type'     => 'required|in:money_collect,fee,service,estimate'
        ));
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'code' => 'invalid', 'message_error' => $validation->messages()));
        }

        $UploadCtrl                     = new UploadCtrl;
        $UploadCtrl->table              = $this->table_import;
        $UploadCtrl->type               = $type;
        $UploadCtrl->link_stogare       = storage_path(). DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR .'excel'. DIRECTORY_SEPARATOR .'verify'. DIRECTORY_SEPARATOR .date("Y_m_d");
        $UploadCtrl->link_download      = $this->link_upload.'/excel/verify/'.date("Y_m_d").'/';
        $Upload                         = $UploadCtrl->Upload();

        if($Upload['error']){
            return Response::json($Upload);
        }

        $IdLog  = $Upload['id'];

        switch ($type) {
            case "money_collect":
                return $this->ExcelMoneyCollect((string)$IdLog);
        break;
            case "service":
                return $this->ExcelService((string)$IdLog);
        break;
            default:
                return $this->ExcelFee((string)$IdLog);
        }
    }

    /** ReadExcel create lading excel
     * @param $id
     * @return bool
     */
    function ExcelMoneyCollect($id){
        $LMongo     = new \LMongo;
        $ListImport = $LMongo::collection($this->table_import)->find($id);
        $Data       = \Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(1)->select(
                array(2,3,4,5)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $DataInsert = array();


            foreach($Data as $key => $val){
                if((!empty($val[0]) || !empty($val[1])) && !empty($val[2])){
                    $DataInsert[strtoupper(trim(!empty($val[0]) ? $val[0] : $val[1]))] = array(
                        'partner'           => $id,
                        'status'            => 'NOT_ACTIVE',
                        'active'            => 0,
                        'tracking_code'     => strtoupper(trim($val[0])),
                        'tracking_number'   => (int)substr(strtoupper(trim($val[0])),5),
                        'courier_track_code'=> strtoupper(trim($val[1])),
                        'courier_status'    => strtoupper(trim($val[2])),
                        'money_collect'     => str_replace(array(',','.'),'',trim($val[3])),
                        'courier_id'        => $ListImport['courier_id'],
                        'time_create'       => $this->time()
                    );
                }
            }

            if(!empty($DataInsert)){
                $ListModel  = $LMongo::collection($this->table_log);
                try{
                    $Insert = $ListModel->batchInsert($DataInsert);
                    return Response::json([
                        'error'             => false,
                        'message'           => 'SUCCESS',
                        'message_error'     => 'Thành công',
                        'id'                => $id
                    ]);
                }catch (Exception $e){
                    return Response::json([
                        'error'             => true,
                        'message'           => 'INSERT_LOG_FAIL',
                        'message_error'     => $e->getMessage()
                    ]);
                }
            }
        }

        return Response::json([
            'error'             => true,
            'message'           => 'EMPTY',
            'message_error'     => 'File dữ liệu trống'
        ]);
    }

    /** ReadExcel create lading excel
     * @param $id
     * @return bool
     */
    function ExcelFee($id){
        $LMongo     = new \LMongo;
        $ListImport = $LMongo::collection($this->table_import)->find($id);
        $Data       = \Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(1)->select(
                array(2,3,4,5,6,7)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $DataInsert = array();

            foreach($Data as $key => $val){
                if(!empty($val[0])){
                    $DataInsert[strtoupper(trim($val[0]))] = array(
                        'partner'           => $id,
                        'status'            => 'NOT_ACTIVE',
                        'active'            => 0,
                        'tracking_code'     => strtoupper(trim($val[0])),
                        'tracking_number'   => (int)substr(strtoupper(trim($val[0])),5),
                        'courier_track_code'=> strtoupper(trim($val[1])),
                        'courier_id'        => $ListImport['courier_id'],
                            'hvc'           => array(
                            'pvc'           => (int)str_replace(array(',','.'),'',trim($val[2])),
                            'cod'           => (int)str_replace(array(',','.'),'',trim($val[3])),
                            'pbh'           => (int)str_replace(array(',','.'),'',trim($val[4])),
                            'pch'           => (int)str_replace(array(',','.'),'',trim($val[5])),
                        ),
                        'time_create'       => $this->time()
                    );
                }
            }

            if(!empty($DataInsert)){
                $ListModel  = $LMongo::collection($this->table_log);

                $i = 0;
                $Insert = [];
                foreach($DataInsert as $val) {
                    $i++;
                    $Insert[$i % 15][] = $val;
                }

                foreach($Insert as $val){
                    $Insert = $ListModel->batchInsert($val);
                }

                if($Insert){
                    return Response::json([
                        'error'             => false,
                        'message'           => 'SUCCESS',
                        'message_error'     => 'Thành công',
                        'id'                => $id
                    ]);
                }else{
                    return Response::json([
                        'error'             => true,
                        'message'           => 'INSERT_LOG_FAIL',
                        'message_error'     => 'Thất bại, hãy thử lại !'
                    ]);
                }
            }

        }
        return Response::json([
            'error'             => true,
            'message'           => 'EMPTY',
            'message_error'     => 'File dữ liệu trống'
        ]);
    }

    /**
     * Excel Service
     */
    function ExcelService($id){
        $LMongo     = new \LMongo;
        $ListImport = $LMongo::collection($this->table_import)->find($id);
        $Data       = \Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(1)->select(
                array(2,3,4,5,6,7,8)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $DataInsert = array();

            foreach($Data as $key => $val){
                if((!empty($val[0]) || !empty($val[1])) && (!empty($val[2]) || !empty($val[3]) || !empty($val[4])
                        || !empty($val[5]) || !empty($val[6]))){
                    $DataInsert[strtoupper(trim($val[0]))] = array(
                        'partner'               => $id,
                        'status'                => 'NOT_ACTIVE',
                        'active'                => 0,
                        'tracking_code'         => isset($val[0]) ? strtoupper(trim($val[0])) : '',
                        'tracking_number'       => (int)substr(strtoupper(trim($val[0])),5),
                        'courier_track_code'    => isset($val[1]) ? strtoupper(trim($val[1])) : '',
                        'courier_from_city'     => isset($val[2]) ? strtoupper(trim($val[2])) : '',
                        'courier_to_city'       => isset($val[3]) ? strtoupper(trim($val[3])) : '',
                        'courier_service'       => isset($val[4]) ? strtoupper(trim($val[4])) : '',
                        'courier_weight'        => isset($val[5]) ? (int)preg_replace("/[^0-9.,]/", "", $val[5]) : 0,
                        'courier_money_collect' => isset($val[5]) ? (int)preg_replace("/[^0-9.,]/", "", $val[6]) : 0,
                        'sc_service'            => '',
                        'sc_weight'             => 0,
                        'sc_money_collect'      => 0,
                        'sc_from_city'          => '',
                        'sc_to_city'            => '',
                        'sc_to_district'        => '',
                        'service'               => 0,
                        'location'              => 0,
                        'courier_id'            => $ListImport['courier_id'],
                        'time_create'           => $this->time()
                    );
                }
            }

            if(!empty($DataInsert)){
                $ListModel  = $LMongo::collection($this->table_log);
                $Insert = $ListModel->batchInsert($DataInsert);
                if($Insert){
                    return Response::json([
                        'error'             => false,
                        'message'           => 'SUCCESS',
                        'message_error'     => 'Thành công',
                        'id'                => $id
                    ]);
                }else{
                    return Response::json([
                        'error'             => true,
                        'message'           => 'INSERT_LOG_FAIL',
                        'message_error'     => 'Thất bại, hãy thử lại !'
                    ]);
                }
            }

        }
        return Response::json([
            'error'             => true,
            'message'           => 'EMPTY',
            'message_error'     => 'File dữ liệu trống'
        ]);
    }

    /** get list verify excel
     * @param $id
     * @return mixed
     */
    function getListExcel($id){
        $page           = Input::has('page')    ? (int)Input::get('page')                : 1;
        $itemPage       = Input::has('limit')   ? Input::get('limit')                    : 20;
        $tab            = Input::has('tab')     ? trim(strtoupper(Input::get('tab')))    : 'ALL';
        $type           = Input::has('type')    ? trim(strtoupper(Input::get('type')))   : '';
        $cmd            = Input::has('cmd')     ? trim(strtoupper(Input::get('cmd')))    : '';
        $Status         = [];
        $Service        = [];

        $ListModel      = \LMongo::collection($this->table_log)->where('partner', $id);

        if($tab != 'ALL'){
            if($tab == 'MISMATCH'){
                $ListModel = $ListModel->where('active',2);
            }else{
                $ListModel = $ListModel->where('status',$tab);
            }
        }

        if(!empty($cmd)){
            $Data           = $ListModel->where('partner', $id)->get()->toArray();

            if($cmd == 'MONEY_COLLECT'){
                $StatusCourier  = [];
                $Item           = reset($Data);
                if(isset($Item['courier_id'])){
                    $StatusCourier = $this->CacheMapStatus($Item['courier_id']);
                }

                return Response::json([
                    'error'             => false,
                    'data'              => $Data,
                    'status_courier'    => $StatusCourier,
                    'message'           => 'success'
                ]);
            }

            if($cmd == 'SERVICE'){
                return Response::json([
                    'error'             => false,
                    'data'              => $Data,
                    'message'           => 'success'
                ]);
            }

            if($cmd == 'EXPORT'){
                $Data = $ListModel->orderBy('time_create','desc')->get()->toArray();
                $contents = array(
                    'error'         => false,
                    'data'          => $Data,
                    'message'       => 'success'
                );

                return Response::json($contents);
            }
        }

        $SumModel       = clone $ListModel;
        $MotalNewTotal  = clone $ListModel;
        $Total          = $ListModel->count();
        $DataSum        = $SumModel->sum('amount');

        $NewTotal       = $MotalNewTotal->where('active',0)->count();

        if($itemPage != 'all'){
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
            $ListModel  = $ListModel->skip($offset)->take($itemPage);
        }

        $Data = $ListModel->orderBy('time_create','desc')->get()->toArray();

        if(!empty($Data)){
            $Item = reset($Data);
            if(isset($Item['courier_id'])){
                if(!in_array($type, ['SERVICE','FEE'])){
                    $StatusMap = $this->CacheMapStatus($Item['courier_id']);
                }
            }
        }

        $contents = array(
            'error'         => false,
            'data'          => $Data,
            'total'         => $Total,
            'new_total'     => $NewTotal,
            'status_map'    => isset($StatusMap)    ? $StatusMap    : [],
            'data_sum'      => $DataSum,
            'message'       => 'success'
        );

        return Response::json($contents);
    }

    /**
     * get list import excel verify
     */
    public function getListImport(){
        $page       = Input::has('page')        ? (int)Input::get('page')           : 1;
        $itemPage   = Input::has('limit')       ? Input::get('limit')               : 20;
        $TimeStart  = Input::has('time_start')  ? (int)Input::get('time_start')     : 0;
        $TimeEnd    = Input::has('time_end')    ? (int)Input::get('time_end')       : 0;
        $Type       = Input::has('type')        ? trim(Input::get('type'))          : '';
        // validation
        $validation = \Validator::make(array('type' => $Type), array(
            'type'     => 'required|in:money_collect,fee,service'
        ));

        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }


        $offset     = ($page - 1)*$itemPage;

        $LMongo     = \LMongo::collection($this->table_import)->where('type',$Type);

        if($TimeStart > 0){
            $LMongo = $LMongo->andWhereGte('time_create',$TimeStart);
        }
        if($TimeEnd > 0){
            $LMongo = $LMongo->andWhereLt('time_create',$TimeEnd);
        }

        $ModelTotal = clone $LMongo;
        $Total = $ModelTotal->count();

        // getdata
        $Data   = [];
        $User   = [];
        if($Total > 0){
            $LMongo      = $LMongo->orderBy('time_create','desc');

            if($itemPage != 'all'){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $LMongo       = $LMongo->skip($offset)->take($itemPage);
            }

            $Data       = $LMongo->get()->toArray();
            if(!empty($Data)){
                $ListUserId = [];
                foreach($Data as $val){
                    $ListUserId[]   = $val['user_id'];
                }

                if(!empty($ListUserId)){
                    $ListUser = \User::whereIn('id',$ListUserId)->get(array('id','email','fullname','phone'))->toArray();
                    if(!empty($ListUser)){
                        foreach($ListUser as $val){
                            $User[$val['id']]   = $val;
                        }
                    }
                }
            }
        }

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'total'         => $Total,
            'data'          => $Data,
            'user'          => $User
        );

        return Response::json($contents);
    }

    /**
     * verify money collect
     */



    public function getVerifyMoneyCollect($id = ''){
        $type           = Input::has('type')    ? (int)Input::get('type')   : null;
        $LMongo         = \LMongo::collection($this->table_log)->where('active',0);

        if(empty($id)){
            $MongoModel = \LMongo::collection($this->table_import);
            $LogImport  = $MongoModel->where('type','money_collect')
                ->where('action.insert',0)
                ->where('action.del',0)
                ->whereGte('time_create',(time() - 86400*31))
                ->get()->toArray();
            if(empty($LogImport)){
                return Response::json([
                    'error'         => false,
                    'total'         => 0,
                    'message'       => 'EMPTY',
                    'message_error' => 'Kết thúc'
                ]);
            }
            $ListId = [];
            foreach($LogImport as $val){
                $ListId[] = (string)$val['_id'];
            }

            $LMongo     = $LMongo->whereIn('partner', $ListId);
        }else{
            $LMongo = $LMongo->where('partner', $id);
        }

        $ModelTotal     = clone $LMongo;
        $this->total    = (int)$ModelTotal->count();

        if($this->total > 0){
            if(isset($type)){
                $LMongo    = $LMongo->whereMod('tracking_number', 3, $type);
            }

            $Item = $LMongo->where('active',0)->first();

            if(!isset($Item['tracking_code'])){
                return Response::json([
                    'error'         => false,
                    'total'         => 0,
                    'message'       => 'EMPTY_LOG',
                    'message_error' => 'Kết thúc'
                ]);
            }

            $Order = $this->getOrder($Item['tracking_code'], $Item['courier_track_code']);
            if(isset($Order['error'])){
                $this->tracking_code            = $Item['tracking_code'];
                $this->courier_track_code       = $Item['courier_track_code'];
                $this->data_update = [
                    'active'    => 2,
                    'status'    => $Order['message'],
                    'sc_status' => ''
                ];
                return $this->ResponseData($Item['_id'], $Order['message'], 2);
            }else{
                $this->tracking_code            = $Order->tracking_code;
                $this->courier_track_code       = $Order->courier_tracking_code;
            }

            $this->tracking_code            = !empty($Order->tracking_code) ? $Order->tracking_code : $Item['tracking_code'];
            $this->courier_track_code       = !empty($Order->courier_tracking_code) ? $Order->courier_tracking_code : $Item['courier_track_code'];

            if($Order->courier_id != $Item['courier_id']){ // Check  Courier
                $this->data_update = [
                    'active'    => 2,
                    'status'    => 'COURIER_ERROR',
                    'sc_status' => '',
                ];
                return $this->ResponseData($Item['_id'], $Order['message'], 2);
            }

            $OrderDetail    = $this->getOrderDetail($Order->id);
            if(isset($OrderDetail['error'])){
                $this->data_update = [
                    'active'        => 2,
                    'status'        => $OrderDetail['message'],
                    'sc_status'     => '',
                ];
                return $this->ResponseData($Item['_id'], $OrderDetail['message'], 2);
            }

            if($Item['money_collect'] != $OrderDetail->money_collect){
                $this->data_update = [
                    'sc_money_collect'  => $OrderDetail->money_collect,
                    'active'            => 2,
                    'status'            => 'MISMATCH',
                    'sc_status'         => $Order->status,
                ];
                return $this->ResponseData($Item['_id'], 'MISMATCH', 2);
            }

            // Insert table courier verify
            DB::connection('orderdb')->beginTransaction();

            //Upload CourierVerify Money collect
            try{
                $Verify                     = \ordermodel\CourierVerifyModel::firstOrNew(array('order_id' => (int)$Order->id));
                $Verify->money_collect      = $Item['money_collect'];
                $Verify->save();
            }catch(Exception $e){
                $this->data_update = [
                    'active'        => 2,
                    'status'        => 'UPDATE_ORDER_VERIFY_FAIL',
                    'sc_status'     => $Order->status
                ];
                return $this->ResponseData($Item['_id'], 'UPDATE_ORDER_VERIFY_FAIL', 2);
            }

            // Update Order table
            try{
                $Order->verify_money_collect    = $this->time();
                $Order->save();
                DB::connection('orderdb')->commit();
            }catch(Exception $e){
                DB::connection('orderdb')->rollBack();
                $this->data_update = [
                    'active'    => 2,
                    'status'    => 'UPDATE_ORDER_FAIL',
                    'sc_status'     => $Order->status
                ];
                return $this->ResponseData($Item['_id'], 'UPDATE_ORDER_FAIL', 2);
            }

            $this->data_update = [
                'active'            => 1,
                'status'            => 'SUCCESS',
                'sc_money_collect'  => $OrderDetail->money_collect,
                'sc_status'         => $Order->status
            ];
            return $this->ResponseData($Item['_id'], 'SUCCESS', 1);
        }else{
            return $this->ResponseData($id, 'SUCCESS', 0); // End
        }
    }

    /**
     * verify money collect
     */
    public function getVerifyFee($id = ''){
        $type       = Input::has('type')    ? (int)Input::get('type')   : null;
        $LMongo     = \LMongo::collection($this->table_log)->where('active',0);

        if(empty($id)){
            $LogImport  = \LMongo::collection($this->table_import)->where('type','fee')
                                 ->where('action.insert',0)
                                 ->where('action.del',0)
                                 ->whereGte('time_create',(time() - 86400*31))
                                 ->get()->toArray();
            if(empty($LogImport)){
                return Response::json([
                    'error'         => false,
                    'total'         => 0,
                    'message'       => 'EMPTY',
                    'message_error' => 'Kết thúc'
                ]);
            }
            $ListId = [];
            foreach($LogImport as $val){
                $ListId[] = (string)$val['_id'];
            }
            
            $LMongo     = $LMongo->whereIn('partner', $ListId);
        }else{
            $LMongo = $LMongo->where('partner', $id);
        }

        $ModelTotal = clone $LMongo;

        $this->total   = (int)$ModelTotal->count();
        if($this->total > 0){
            if(isset($type)){
                $LMongo    = $LMongo->whereMod('tracking_number', 10, $type);
            }
            $Item = $LMongo->where('active',0)->first();
            if(!isset($Item['tracking_code'])){
                return Response::json([
                    'error'         => false,
                    'total'         => 0,
                    'message'       => 'EMPTY_LOG',
                    'message_error' => 'Kết thúc'
                ]);
            }

            $Order = $this->getOrder($Item['tracking_code'], $Item['courier_track_code']);
            if(isset($Order['error'])){
                $this->tracking_code            = $Item['tracking_code'];
                $this->courier_track_code       = $Item['courier_track_code'];
                $this->data_update = [
                    'active'    => 2,
                    'status'    => $Order['message'],
                    'sc_status' => ''
                ];
                return $this->ResponseData($Item['_id'], $Order['message'], 2);
            }else{
                $this->tracking_code            = $Order->tracking_code;
                $this->courier_track_code       = $Order->courier_tracking_code;
            }

            if($Order->courier_id != $Item['courier_id']){ // Check  Courier
                $this->data_update = [
                    'active'    => 2,
                    'status'    => 'COURIER_ERROR',
                    'sc_status' => ''
                ];
                return $this->ResponseData($Item['_id'], $Order['message'], 2);
            }

            $OrderDetail    = $this->getOrderDetail($Order->id);
            if(isset($OrderDetail['error'])){
                $this->data_update = [
                    'active'    => 2,
                    'status'    => $OrderDetail['message'],
                    'sc_status' => ''
                ];
                return $this->ResponseData($Item['_id'], $OrderDetail['message'], 2);
            }

            //Update Order Detail
            $this->data_update['sc']['hvc_pvc'] = (int)$OrderDetail->hvc_pvc;
            $this->data_update['sc']['hvc_cod'] = (int)$OrderDetail->hvc_cod ;
            $this->data_update['sc']['hvc_pbh'] = (int)$OrderDetail->hvc_pbh;
            $this->data_update['sc']['hvc_pch'] = (int)$OrderDetail->hvc_pch;
            $this->data_update['active']                = 1;
            $this->data_update['status']                = 'SUCCESS';

            if($Item['hvc']['pvc'] == $OrderDetail->hvc_pvc){
                $this->data_update['mismatch']['hvc_pvc'] = (int)0;
            }else{
                $this->data_update['mismatch']['hvc_pvc']   = (int)1;
                $this->data_update['active']                = 2;
                $this->data_update['status']                = 'MISMATCH';
            }

            if($Item['hvc']['cod'] == $OrderDetail->hvc_cod){
                $this->data_update['mismatch']['hvc_cod'] = (int)0;
            }else{
                $this->data_update['mismatch']['hvc_cod']   = (int)1;
                $this->data_update['active']                = 2;
                $this->data_update['status']                = 'MISMATCH';
            }

            if($Item['hvc']['pbh'] == $OrderDetail->hvc_pbh){
                $this->data_update['mismatch']['hvc_pbh'] = (int)0;
            }else{
                $this->data_update['mismatch']['hvc_pbh']   = (int)1;
                $this->data_update['active']                = 2;
                $this->data_update['status']                = 'MISMATCH';
            }

            if($Item['hvc']['pch'] == $OrderDetail->hvc_pch){
                $this->data_update['mismatch']['hvc_pch'] = (int)0;
            }else{
                $this->data_update['mismatch']['hvc_pch']   = (int)1;
                $this->data_update['active']                = 2;
                $this->data_update['status']                = 'MISMATCH';
            }

            if($this->data_update['active'] == 2){
                return $this->ResponseData($Item['_id'], 'MISMATCH', 2);
            }

            // Insert table courier verify
            DB::connection('orderdb')->beginTransaction();
            try{
                $Verify   = \ordermodel\CourierVerifyModel::firstOrNew(array('order_id' => (int)$Order->id));
                $Verify->hvc_pvc = $Item['hvc']['pvc'];
                $Verify->hvc_cod = $Item['hvc']['cod'];
                $Verify->hvc_pbh = $Item['hvc']['pbh'];
                $Verify->hvc_pch = $Item['hvc']['pch'];
                $Verify->save();
            }catch (Exception $e){
                $this->data_update['active']                = 2;
                $this->data_update['status']                = 'UPDATE_ORDER_VERIFY_FAIL';
                return $this->ResponseData($Item['_id'], 'UPDATE_ORDER_VERIFY_FAIL', 2);
            }

            try{
                \ordermodel\OrdersModel::where('time_accept','>=',$this->time() - 86400*90)->where('id',$Order->id)->update(['verify_fee' => $this->time()]);
                DB::connection('orderdb')->commit();
            }catch(Exception $e){
                DB::connection('orderdb')->rollBack();
                $this->data_update['active']                = 2;
                $this->data_update['status']                = 'UPDATE_ORDER_VERIFY_FAIL';
                return $this->ResponseData($Item['_id'], 'UPDATE_ORDER_FAIL', 2);
            }

            return $this->ResponseData($Item['_id'], 'SUCCESS', 1);
        }else{
            return $this->ResponseData($id, 'SUCCESS', 0);
        }
    }

    /**
     *  đối soát dịch vụ
     */
    public function getVerifyService($id = ''){
        $type           = Input::has('type')    ? (int)Input::get('type')   : null;
        $LMongo         = \LMongo::collection($this->table_log)->where('active',0);

        if(empty($id)){
            $MongoModel = \LMongo::collection($this->table_import);
            $LogImport  = $MongoModel->where('type','service')
                ->where('action.insert',0)
                ->where('action.del',0)
                ->whereGte('time_create',(time() - 86400*31))
                ->get()->toArray();
            if(empty($LogImport)){
                return Response::json([
                    'error'         => false,
                    'total'         => 0,
                    'message'       => 'EMPTY',
                    'message_error' => 'Kết thúc'
                ]);
            }
            $ListId = [];
            foreach($LogImport as $val){
                $ListId[] = (string)$val['_id'];
            }

            $LMongo     = $LMongo->whereIn('partner', $ListId);
        }else{
            $LMongo     = \LMongo::collection($this->table_log)->where('partner', $id);
        }

        $ModelTotal     = clone $LMongo;
        $this->total    = (int)$ModelTotal->count();

        if($this->total > 0){
            if(isset($type)){
                $LMongo    = $LMongo->whereMod('tracking_number', 3, $type);
            }
            $Item = $LMongo->first();
            if(!isset($Item['tracking_code'])){
                return Response::json([
                    'error'         => false,
                    'total'         => 0,
                    'message'       => 'EMPTY_LOG',
                    'message_error' => 'Kết thúc'
                ]);
            }

            $this->data_update  = [
                'active'            => 1,
                'status'            => 'SUCCESS',
                'sc_service'        => '',
                'sc_weight'         => 0,
                'sc_money_collect'  => 0,
                'sc_from_city'      => '',
                'sc_to_city'        => '',
                'sc_to_district'    => '',
                'service'           => 0,
                'location'          => 0
            ];

            $Order = $this->getOrder($Item['tracking_code'], $Item['courier_track_code']);
            if(isset($Order['error'])){
                $this->tracking_code            = $Item['tracking_code'];
                $this->courier_track_code       = $Item['courier_track_code'];

                $this->data_update['active']    = 2;
                $this->data_update['status']    = $Order['message'];
                return $this->ResponseData($Item['_id'], $this->data_update['status'], 2);
            }else{
                $this->tracking_code            = $Order->tracking_code;
                $this->courier_track_code       = $Order->courier_tracking_code;
            }

            $this->data_update['tracking_code']         = $this->tracking_code;
            $this->data_update['courier_track_code']    = $this->courier_track_code;
            $this->data_update['sc_to_district']        = $Order->to_district_id;
            $this->data_update['service']               = $Order->service_id;

            if($Order->courier_id != $Item['courier_id']){ // Check  Courier
                $this->data_update['active']    = 2;
                $this->data_update['status']    = 'COURIER_ERROR';
                return $this->ResponseData($Item['_id'], $this->data_update['status'], 2);
            }

            if(!empty($Item['courier_service']) || !empty($Item['courier_to_city'])){
                $ToAddress      = $this->__to_address($Order->to_address_id);
            }
            
            if(!empty($Item['courier_service'])){
                if(isset($ToAddress->id)){
                    //Check location
                    $this->data_update['location']  = $this->__get_area_location($Order->courier_id, $ToAddress->province_id, $Order->from_city_id,$ToAddress->city_id);
                    if(!empty($this->data_update['location'])){
                        $funcName   = '__get_service_'.(isset($this->courier[(int)$Item['courier_id']]) ? $this->courier[(int)$Item['courier_id']]['prefix'] : '');
                        if(method_exists($this ,$funcName)){
                            $ServiceCourier = $this->$funcName($Order->service_id, $Order->total_weight, $Order->from_city_id, $ToAddress->city_id, $this->data_update['location']);
                            $this->data_update['sc_service']    = $ServiceCourier;
                            if(strtoupper(trim($ServiceCourier)) != $Item['courier_service']){
                                $this->data_update['active']    = 2;
                                $this->data_update['status']    = 'MISMATCH';
                            }
                        }else{
                            $this->data_update['active']    = 2;
                            $this->data_update['status']    = 'FUNCTION_SERVICE_NOT_EXISTS';
                        }

                    }else{
                        $this->data_update['active']    = 2;
                        $this->data_update['status']    = 'SERIVCE_NOT_EXISTS';
                    }
                }else{
                    $this->data_update['active']    = 2;
                    $this->data_update['status']    = 'TO_ADDRESS_NOT_EXIST';
                }
            }

            // Khối lượng
            if(!empty($Item['courier_weight'])){
                $this->data_update['sc_weight'] =   $Order->total_weight;
                if((int)$Item['courier_weight'] != (int)$Order->total_weight){
                    $this->data_update['active']    = 2;
                    $this->data_update['status']    = 'MISMATCH';
                }
            }

            if(!empty($Item['courier_money_collect'])){
                $OrderDetail    = $this->getOrderDetail($Order->id);
                if(!isset($OrderDetail['error'])){
                    $this->data_update['sc_money_collect']  = $OrderDetail->money_collect;
                    if((int)$Item['courier_money_collect'] != $OrderDetail->money_collect){
                        $this->data_update['active']    = 2;
                        $this->data_update['status']    = 'MISMATCH';
                    }
                }
            }


            if(!empty($Item['courier_from_city']) || !empty($Item['courier_to_city'])){
                $CityMap        = $this->__cache_courier_city($Item['courier_id']);
                //$DistrictMap    = $this->__cache_courier_district($Item['courier_id'], $ToAddress->city_id);
            }

            if(!empty($Item['courier_from_city'])){
                if(isset($CityMap[$Order->from_city_id])){
                    $FromCity   = $CityMap[$Order->from_city_id];
                    $this->data_update['sc_from_city']  = $FromCity;
                    if($FromCity != $Item['courier_from_city']){
                        $this->data_update['active']        = 2;
                        $this->data_update['status']        = 'MISMATCH';
                    }
                }else{
                    $this->data_update['active']        = 2;
                    $this->data_update['status']        = 'CITY_MAP_NOT_EXIST';
                }
            }

            if(!empty($Item['courier_to_city'])){
                if(isset($ToAddress->id)){
                    if(isset($CityMap[$ToAddress->city_id])){
                        $ToCity   = $CityMap[$ToAddress->city_id];
                        $this->data_update['sc_to_city']  = $ToCity;

                        if($ToCity != $Item['courier_to_city']){
                            $this->data_update['active']        = 2;
                            $this->data_update['status']        = 'MISMATCH';
                        }
                    }else{
                        $this->data_update['active']        = 2;
                        $this->data_update['status']        = 'CITY_MAP_NOT_EXIST';
                    }

                    $this->data_update['to_district']           = $ToAddress->province_id;
                }else{
                    $this->data_update['active']        = 2;
                    $this->data_update['status']        = 'TO_ADDRESS_NOT_EXIST';
                }
            }

            return $this->ResponseData($Item['_id'], 'SUCCESS', 1);
        }else{
            return $this->ResponseData($id, 'SUCCESS', 0); // End
        }
    }

    /**
     * @param $Code
     * @return array
     */
    private function getOrder($Code, $CourierCode){
        $OrderModel     = new \ordermodel\OrdersModel;

        try{
            $OrderModel   = $OrderModel->where('time_accept', '>=',$this->time() - 86400*90/*$this->__time_range()*/);

            if(!empty($Code)){
                $Order  =   $OrderModel->where('tracking_code',$Code);
            }elseif(!empty($CourierCode)){
                $Order  =   $OrderModel->where('courier_tracking_code',$CourierCode);
            }else{
                return ['error'     => true, 'message' => 'CODE_NOT_EXISTS'];
            }

            $Order  = $Order->first(
                                ['id','tracking_code','courier_tracking_code','courier_id','status','verify_money_collect','verify_fee',
                                    'to_address_id','to_district_id','service_id','total_weight','from_city_id']);

        }catch(Exception $e){
            return ['error'     => true, 'message' => 'ORDER_NOT_EXISTS'];
        }

        if(empty($Order) || !isset($Order->id)){
            return ['error'     => true, 'message' => 'ORDER_NOT_EXISTS'];
        }

        return $Order;
    }

    private function getOrderDetail($order_id){
        $DetailModel    = new \ordermodel\DetailModel;

        try{
            $OrderDetail          = $DetailModel->where('order_id',$order_id)->first();
        }catch(Exception $e){
            return ['error'     => true, 'message' => 'ORDER_DETAIL_NOT_EXISTS'];
        }

        if(empty($OrderDetail) || !isset($OrderDetail->id)){
            return ['error'     => true, 'message' => 'ORDER_DETAIL_NOT_EXISTS'];
        }

        return $OrderDetail;
    }

    /**
     * @param $id   id parent or id
     * @param $Status
     * @param $Active 0 -  kết thúc
     * @param $DataUpdate - update  log_verify
     * @return mixed
     */
    private function ResponseData( $id, $Status, $Active){
        $this->data_update['tracking_code']      = $this->tracking_code;
        $this->data_update['courier_track_code'] = $this->courier_track_code;

        if($Active == 0){
            if(!empty($id)){
                \LMongo::collection($this->table_import)->where('_id', new \MongoId($id))->update(array('action.insert' => 1));
            }
            $contents = array(
                'error'         => false,
                'total'         => 0,
                'message'       => 'SUCCESS',
                'message_error' => 'Kết thúc'
            );
        }else{
            \LMongo::collection($this->table_log)->where('_id', new \MongoId($id))->update($this->data_update);

            $contents = array(
                'error'             => $Active == 1 ? false : true,
                'total'             => $this->total + 1,
                'message'           => $Status,
                'message_error'     => $Status == 'SUCCESS' ? 'Thành công' : 'Có lỗi rồi !'
            );
        }

        return Response::json($contents);
    }

    private function ExMoneyCollect($Data){
        $FileName   = 'Danh_sach_van_đon';

        $Courier        = [];
        $Status         = [];
        $StatusCourier  = [];

        if(!empty($Data)){
            $Courier    = $this->getCourier(false);
            $Status     = $this->getStatus(false);

            $Item = reset($Data);
            if(isset($Item['courier_id'])){
                $StatusCourier = $this->CacheMapStatus($Item['courier_id']);
            }
        }

        return \Excel::create($FileName, function($excel) use($Data, $Courier, $Status, $StatusCourier){
            $excel->sheet('Sheet1', function($sheet) use($Data, $Courier, $Status, $StatusCourier){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách vận đơn'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,
                    'H'     =>  30, 'I'     =>  30
                ));
                $sheet->setMergeColumn(array(
                    'columns' => array('A','B','C','D','E','F','G','H','I'),
                    'rows' => array(
                        array(3,4)
                    )
                ));

                $sheet->row(3, array(
                    'STT', 'Mã đơn hàng', 'Hãng vận chuyển', 'Mã hãng vận chuyển', 'Trạng thái HVC', 'Trạng thái SC', 'Thu hộ HVC', 'Thu hộ SC', 'Trạng thái'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });

                $sheet->setBorder('A3:I4', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        isset($val['tracking_code']) ? $val['tracking_code'] : '',
                        isset($Courier[$val['courier_id']]) ? $Courier[$val['courier_id']]['name'] : '',
                        isset($val['courier_track_code']) ? $val['courier_track_code'] : '',
                        (isset($StatusCourier[$val['courier_status']]) && $Status[$StatusCourier[$val['courier_status']]]) ? $Status[$StatusCourier[$val['courier_status']]] : '',
                        (!empty($val['sc_status']) && isset($Status[$val['sc_status']])) ? $Status[$val['sc_status']] : '',
                        !empty($val['money_collect'])       ? number_format($val['money_collect']) : 0,
                        !empty($val['sc_money_collect'])    ? number_format($val['sc_money_collect']) : 0,
                        (!empty($val['status']) && isset($this->list_status[$val['status']])) ? $this->list_status[$val['status']] : 'Trạng thái',
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }

    private function ExService($Data){
        $FileName   = 'Danh_sach_van_đon';

        $Courier        = [];
        $ServiceMap     = [];
        $Service        = [];

        if(!empty($Data)){
            $Item = reset($Data);
            $Courier    = $this->getCourier(false);
            $ServiceMap = $this->list_courier_service[$Item['courier_id']];
            $CityMap    = $this->__cache_courier_city($Item['courier_id']);
            if(!empty($CityMap)){
                $CityMap    = array_flip($CityMap);
            }

            $Service    = $this->getService(false);
            $City       = $this->getListCity();
        }

        Excel::selectSheetsByIndex(0)->load('/data/www/html/storage/template/accounting/doi_soat_dich_vu.xls', function($reader) use($Data, $Courier, $Service, $ServiceMap, $CityMap, $City){
            $reader->sheet(0,function($sheet) use($Data, $Courier, $Service, $ServiceMap, $CityMap, $City){
                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        isset($val['tracking_code'])            ? $val['tracking_code']                         : '',
                        isset($val['courier_track_code'])       ? $val['courier_track_code']                    : '',
                        isset($Courier[$val['courier_id']])     ? $Courier[$val['courier_id']]['name']          : '',

                        isset($val['sc_service']) ? $val['sc_service'] : '',
                        isset($val['courier_service']) ? $val['courier_service'] : '',

                        (isset($val['sc_money_collect']) && !empty($val['sc_money_collect'])) ? number_format($val['sc_money_collect'])       : 0,
                        !empty($val['courier_money_collect'])   ? number_format($val['courier_money_collect'])  : 0,

                        (isset($val['sc_weight']) && !empty($val['sc_weight']))               ? number_format($val['sc_weight'])              : 0,
                        (isset($val['courier_weight']) && !empty($val['courier_weight']))          ? number_format($val['courier_weight'])         : 0,


                        (isset($val['sc_from_city']) && !empty($val['sc_from_city']) && isset($CityMap[$val['sc_from_city']]) && isset($City[(int)$CityMap[$val['sc_from_city']]])) ? $City[(int)$CityMap[$val['sc_from_city']]]              : 0,
                        (isset($val['courier_from_city']) && !empty($val['courier_from_city']) && isset($CityMap[$val['courier_from_city']]) && isset($CityMap[$val['courier_from_city']]) && isset($City[$CityMap[$val['courier_from_city']]]))       ? $City[$CityMap[$val['courier_from_city']]] : $val['courier_from_city'],

                        (isset($val['sc_to_city']) && !empty($val['sc_to_city']) && isset($CityMap[$val['sc_to_city']]) && isset($City[(int)$CityMap[$val['sc_to_city']]])) ? $City[(int)$CityMap[$val['sc_to_city']]]              : 0,
                        (isset($val['courier_to_city']) && !empty($val['courier_to_city']) && isset($CityMap[$val['courier_to_city']]) && isset($CityMap[$val['courier_to_city']]) && isset($City[$CityMap[$val['courier_to_city']]]))       ? $City[$CityMap[$val['courier_to_city']]] : $val['courier_to_city'],

                        (!empty($val['status']) && isset($this->list_status[$val['status']])) ? $this->list_status[$val['status']] : $val['status'],
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        },'UTF-8',true)->export('xls');
    }
}
