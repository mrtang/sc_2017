<?php
namespace oms;

use Response;
use Exception;
use Input;
use Validator;
use Excel;
use DB;
use LMongo;
use File;

use ordermodel\OrdersModel;

use order\ChangeOrderCtrl;
use omsmodel\PipeStatusModel;
use omsmodel\PipeJourneyModel;

use User;
use CourierController;

class UploadCtrl extends \BaseController
{

    private  $country_id    = 237;
    private $data_update    = [];
    private $total          = 0;
    private $__Logjourney   = [];
    private $error_message  = '';

    /*
     * get list order
     */
    public function getListimport(){
        $itemPage           = Input::has('item_page')           ? (int)Input::get('item_page')                  : 20;
        $TimeCreateStart    = Input::has('create_start')        ? (int)Input::get('create_start')               : 0; // time_create start   time_stamp
        $TimeCreateEnd      = Input::has('create_end')          ? (int)Input::get('create_end')                 : 0; // time_create end
        $CountryId          = Input::has('country_id')          ? (int)Input::get('country_id')                 : 237;
        $page               = Input::has('page')                ? (int)Input::get('page')                       : 1;
        $Type               = Input::has('type')                ? strtolower(trim(Input::get('type')))          : 'journey';

        $LMongo     = \LMongo::collection('log_import')->where('country_id',$CountryId)->where('type',$Type);
        if($TimeCreateStart > 0){
            $LMongo = $LMongo->andWhereGte('time_create',$TimeCreateStart);
        }
        if($TimeCreateEnd > 0){
            $LMongo = $LMongo->andWhereLt('time_create',$TimeCreateEnd);
        }

        $ModelTotal     = clone $LMongo;
        $Total          = $ModelTotal->count();

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
                    $UserModel = new User;
                    $User = $UserModel->getUserById($ListUserId);
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

    /*
     * get list upload
     */
    function getListupload($id){
        $page           = Input::has('page')            ? (int)Input::get('page')                   : 1;
        $itemPage       = Input::has('item_page')       ? (int)Input::get('item_page')              : 20;
        $tab            = Input::has('tab')             ? trim(strtoupper(Input::get('tab')))       : 'ALL';
        $Type           = Input::has('type')            ? trim(strtoupper(Input::get('type')))      : 'journey';
        $CountryId      = Input::has('country_id')      ? (int)Input::get('country_id')             : 237;
        $cmd            = Input::has('cmd')             ? trim(strtoupper(Input::get('cmd')))       : '';

        $ListModel      = \LMongo::collection('log_upload_change')->where('partner', $id)->where('country_id',$CountryId);

        if($tab != 'ALL'){
            if($tab == 'MISMATCH'){
                $ListModel = $ListModel->where('active',2);
            }else{
                $ListModel = $ListModel->where('status',$tab);
            }
        }

        if(!empty($cmd)){
            $Data = $ListModel->where('partner', $id)->get()->toArray();
            if($cmd == 'JOURNEY'){
                return $this->ExJourney($Data);
            }elseif($cmd == 'PROCESS'){
                return $this->ExProcess($Data);
            }elseif($cmd == 'WEIGHT'){
                return $this->ExWeight($Data);
            }elseif($cmd == 'STATUS'){
                return $this->ExStatus($Data);
            }elseif($cmd == 'ESTIMATE'){
                return $this->ExEstimate($Data);
            }elseif($cmd == 'ESTIMATE_PLUS'){
                return $this->ExEstimate($Data);
            }

        }

        $MotalNewTotal  = clone $ListModel;
        $Total          = $ListModel->count();
        $NewTotal       = $MotalNewTotal->where('active',0)->count();

        if($itemPage != 'all'){
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
            $ListModel  = $ListModel->skip($offset)->take($itemPage);
        }

        $Data = $ListModel->orderBy('time_create','desc')->where('partner', $id)->get()->toArray();

        $contents = array(
            'error'         => false,
            'data'          => $Data,
            'total'         => $Total,
            'new_total'     => $NewTotal,
            'message'       => 'success'
        );

        return Response::json($contents);
    }

    /*
     * Upload
     */
    public function postUpload(){
        $UserInfo           = $this->UserInfo();
        $type               = Input::has('type')            ? strtolower(trim(Input::get('type')))          : 'journey';
        $TypeProcess        = Input::has('type_process')    ? strtolower(trim(Input::get('type_process')))  : 0;
        $Courier            = Input::has('courier_id')      ? (int)(Input::get('courier_id'))               : 0;
        $Service            = Input::has('service_id')      ? (int)(Input::get('service_id'))               : 0;
        $this->country_id   = Input::has('country_id')      ? (int)Input::get('country_id')                 : 237;
        $TimeStart          = Input::has('time')            ? (int)Input::get('time')                       : 0;

        if(empty($Courier) && $type == 'journey'){
            $contents = array(
                'error'     => true,
                'message'   => 'Courier_Empty'
            );
            return Response::json($contents);
        }

        $validation = \Validator::make(array('type' => $type), array(
            'type'     => 'required|in:journey,weight,process,status,estimate,estimate_plus,kpi_cs',
        ));
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        if (Input::hasFile('file')) {
            $File       = Input::file('file');
            $extension  = $File->getClientOriginalExtension();
            $name       = pathinfo($File->getClientOriginalName());
            $name       = $name['filename'].'_'.$this->time().'.'.$extension;

            $MimeType   = $File->getMimeType();

            if(in_array((string)$extension, array('csv','xls','xlsx')) && in_array((string)$MimeType,array('text/plain','application/vnd.ms-excel','application/vnd.ms-office','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))){

                $uploadPath = storage_path(). DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR .'excel'. DIRECTORY_SEPARATOR .$type. DIRECTORY_SEPARATOR .date("Y_m_d");

                if(!file_exists($uploadPath)){
                    File::makeDirectory($uploadPath,0777, true, true);
                }

                $File->move($uploadPath, $name);
                $Insert = [
                    'link_tmp'      => $uploadPath. DIRECTORY_SEPARATOR .$name,
                    'link_download' => $this->link_upload.'/excel/'.$type.'/'.date("Y_m_d").'/'.$name,
                    'name'          => $name,
                    'user_id'       => (int)$UserInfo['id'],
                    'type'          => $type,
                    'type_process'  => (int)$TypeProcess,
                    'action'        => array('del' => 0, 'insert' => 0),
                    'country_id'    => $this->country_id,
                    'time_create'   => $this->time()
                ];

                if(!empty($Courier)){
                    $Insert['courier_id']   = $Courier;
                }

                if(!empty($Service)){
                    $Insert['service_id']   = $Service;
                }

                $LMongo = new \LMongo;
                $id = (string)$LMongo::collection('log_import')->insert($Insert);

                if(!empty($id)){
                    switch ($type) {
                        case "weight":
                            $ImportExcel = $this->UploadWeight((string)$id);
                            break;
                        case "process":
                            $ImportExcel = $this->UploadProcess((string)$id);
                            break;
                        case "status":
                            $ImportExcel = $this->UploadVerifyStatus((string)$id);
                            break;
                        case "estimate":
                            $ImportExcel = $this->UploadEstimate((string)$id, $Service, $Courier);
                            break;
                        case "estimate_plus":
                            $ImportExcel = $this->UploadEstimatePLus((string)$id, $Service, $Courier);
                            break;
                        case "kpi_cs":
                            $ImportExcel = $this->UploadKpiCs((string)$id, $TimeStart);
                            break;
                        default:
                            $ImportExcel = $this->UploadJourney((string)$id);
                    }

                    if($ImportExcel){
                        $contents = array(
                            'error'     => false,
                            'message'   => 'success',
                            'id'        => $id
                        );
                    }else{
                        $contents = array(
                            'error'             => true,
                            'message'           => 'read excel error',
                            'error_message'     => $this->error_message
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

    private function UploadJourney($id){
        $LMongo     = new \LMongo;
        $ListImport = $LMongo::collection('log_import')->find($id);
        $Data       = \Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(1)->select(
                array(2,3,4,5,6)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $DataInsert = [];

            foreach($Data as $key => $val){
                if((!empty($val[0]) || !empty($val[1])) && !empty($val[2])){
                    $ArrStatus = explode('-', $val[2]);
                    $DataInsert[] = array(
                        'partner'           => $id,
                        'status'            => 'NOT_ACTIVE',
                        'active'            => 0,
                        'tracking_code'     => strtoupper(trim($val[0])),
                        'courier_track_code'=> strtoupper(trim($val[1])),
                        'courier_id'        => $ListImport['courier_id'],
                        'status_sc'	        => (int)$ArrStatus[1],
                        'content'	        => !empty($val[3]) ? trim($val[3]) : 'SC Hà Nội',
                        'city'              => !empty($val[4]) ? trim($val[4]) : 'Hà Nội',
                        'user_id'           => $ListImport['user_id'],
                        'country_id'        => $this->country_id,
                        'time_create'       => $this->time()
                    );
                }
            }

            if(!empty($DataInsert)){
                $ListModel  = $LMongo::collection('log_upload_change');
                $Insert = $ListModel->batchInsert($DataInsert);

                if($Insert) return true;
            }

        }
        return false;
    }

    /**
     *  Excel estimate courier
     */
    function UploadEstimate($id, $service, $courier){
        if(empty($service)){
            return false;
        }

        $LMongo     = new \LMongo;
        $ListImport = $LMongo::collection('log_import')->find($id);
        $Data       = \Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(0)->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $MapData    = [];
            for($i = 0; $i < 100; $i++){
                if($i > 0){
                    if(!isset($Data[$i][0]) || empty($Data[$i][0])){
                        break;
                    }
                }

                for($j = 0; $j < 100; $j++){
                    if($i > 0 && $j > 0){
                        if(!isset($Data[$i][$j]) || empty($Data[$i][$j])){
                            break;
                        }
                        $MapData []  = [
                            'partner'       => $id,
                            'status'        => 'NOT_ACTIVE',
                            'active'        => 0,
                            'courier_id'    => $courier,
                            'service_id'    => $service,
                            'from_city'     => strtoupper(trim($Data[$i][0])),
                            'to_city'       => strtoupper(trim($Data[0][$j])),
                            'sc_from_city'  => 0,
                            'sc_to_city'    => 0,
                            'estimate'      => (int)$Data[$i][$j],
                            'user_id'       => $ListImport['user_id'],
                            'country_id'    => $this->country_id,
                            'time_create'   => $this->time()
                        ];
                    }
                }
            }

            if(!empty($MapData)){
                $ListModel  = $LMongo::collection('log_upload_change');

                $i = 0;
                $Insert = [];
                foreach($MapData as $val) {
                    $i++;
                    $Insert[$i % 15][] = $val;
                }

                foreach($Insert as $val){
                    $Insert = $ListModel->batchInsert($val);
                }

                if($Insert){
                    return true;
                }else{
                    return false;
                }
            }
        }
        return false;
    }
    function UploadEstimatePlus($id, $service, $courier){
        if(empty($service)){
            return false;
        }

        $LMongo     = new \LMongo;
        $ListImport = $LMongo::collection('log_import')->find($id);
        $Data       = \Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(1)->select(
                array(1,2,3)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $DataInsert = [];
            foreach($Data as $val){
                if(!empty($val[0]) && !empty($val[1]) && !empty($val[2])){
                    $DataInsert[]   = [
                        'partner'       => $id,
                        'status'        => 'NOT_ACTIVE',
                        'active'        => 0,
                        'courier_id'    => $courier,
                        'service_id'    => $service,
                        'city'          => strtoupper(trim($val[0])),
                        'district'      => strtoupper(trim($val[1])),
                        'sc_city'       => 0,
                        'sc_district'   => 0,
                        'estimate'      => (int)$val[2],
                        'user_id'       => $ListImport['user_id'],
                        'country_id'    => $this->country_id,
                        'time_create'   => $this->time()
                    ];
                }
            }

            if(empty($DataInsert)){
                return false;
            }

            $i = 0;
            $Insert = [];
            foreach($DataInsert as $val) {
                $i++;
                $Insert[$i % 15][] = $val;
            }

            $ListModel  = $LMongo::collection('log_upload_change');
            foreach($Insert as $val){
                $Insert = $ListModel->batchInsert($val);
            }

            if($Insert){
                return true;
            }else{
                return false;
            }
        }
        return false;
    }

    // Status
    private function UploadVerifyStatus($id){
        $LMongo     = new \LMongo;
        $ListImport = $LMongo::collection('log_import')->find($id);
        $Data       = \Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(1)->select(
                array(2,3)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $DataInsert = [];

            foreach($Data as $key => $val){
                if((!empty($val[0]) || !empty($val[1]))){
                    $DataInsert[] = array(
                        'partner'           => $id,
                        'status'            => 'NOT_ACTIVE',
                        'active'            => 0,
                        'tracking_code'     => strtoupper(trim($val[0])),
                        'courier_track_code'=> strtoupper(trim($val[1])),
                        'courier_id'        => $ListImport['courier_id'],
                        'user_id'           => $ListImport['user_id'],
                        'country_id'        => $this->country_id,
                        'time_create'       => $this->time()
                    );
                }
            }

            if(!empty($DataInsert)){
                $ListModel  = $LMongo::collection('log_upload_change');
                $Insert = $ListModel->batchInsert($DataInsert);

                if($Insert) return true;
            }

        }
        return false;
    }

    private function UploadWeight($id){
        $LMongo     = new \LMongo;
        $ListImport = $LMongo::collection('log_import')->find($id);
        $Data       = \Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(1)->select(
                array(2,3,4)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $DataInsert = [];

            foreach($Data as $key => $val){
                if((!empty($val[0]) || !empty($val[1])) && !empty($val[2])){
                    $DataInsert[] = array(
                        'partner'           => $id,
                        'status'            => 'NOT_ACTIVE',
                        'active'            => 0,
                        'tracking_code'     => strtoupper(trim($val[0])),
                        'courier_track_code'=> strtoupper(trim($val[1])),
                        'weight'	        => str_replace([',','.'],'',trim($val[2])),
                        'user_id'           => $ListImport['user_id'],
                        'country_id'        => $this->country_id,
                        'time_create'       => $this->time()
                    );
                }
            }

            if(!empty($DataInsert)){
                $ListModel  = $LMongo::collection('log_upload_change');
                $Insert = $ListModel->batchInsert($DataInsert);

                if($Insert) return true;
            }

        }
        return false;
    }


    private function UploadProcess($id){
        $LMongo     = new \LMongo;
        $ListImport = $LMongo::collection('log_import')->find($id);
        $Data       = \Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(1)->select(
                array(2,3,4,5)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $DataInsert = [];

            foreach($Data as $key => $val){
                if((!empty($val[0]) || !empty($val[1])) && !empty($val[2])){
                    $arr_process         = explode('_',$val[1]);
                    $process             = (int)end($arr_process);
                    $arr_process         = explode('_',$val[2]);
                    $status              = (int)end($arr_process);

                    $DataInsert[] = array(
                        'partner'           => $id,
                        'status'            => 'NOT_ACTIVE',
                        'active'            => 0,
                        'type_process'      => (int)$ListImport['type_process'],
                        'tracking_code'     => strtoupper(trim($val[0])),
                        'group_process'     => $process,
                        'pipe_status'	    => $status,
                        'note'              => trim($val[3]),
                        'user_id'           => $ListImport['user_id'],
                        'country_id'        => $this->country_id,
                        'time_create'       => $this->time()
                    );
                }
            }

            if(!empty($DataInsert)){
                $ListModel  = $LMongo::collection('log_upload_change');
                $Insert = $ListModel->batchInsert($DataInsert);

                if($Insert) return true;
            }

        }
        return false;
    }

    private function UploadKpiCs($id, $TimeStart){
        $Date = date('Y-m-d', $TimeStart);
        if(strtotime($Date) > strtotime(date('Y-m-d', $this->time()))){
            $this->error_message    = 'Time Error';
            return false;
        }

        $CheckTime = $this->__check_time_edit_kpi($TimeStart);
        if($CheckTime['error']){
            $this->error_message    = $CheckTime['error_message'];
            return false;
        }

        $LMongo     = new \LMongo;
        $ListImport = $LMongo::collection('log_import')->find($id);
        $Data       = \Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(1)->select(
                array(2,3,4,5)
            )->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $ListCode   = [];
            $ListData   = [];
            $ListDetail = [];
            $ListEmail  = [];
            foreach($Data as $key => $val){
                if((!empty($val[0]) && !empty($val[1]))){
                    $Prefix     = explode('-',$val[1]);
                    $Code       = trim(strtolower(end($Prefix)));
                    $val[0]     = trim(strtolower($val[0])); // email
                    $val[2]     = isset($val[2]) ? (int)$val[2] : 0;
                    $val[3]     = isset($val[3]) ? (int)$val[3] : 0;

                    if(!isset($ListData[$Code])){
                        $ListData[$Code]    = [
                            'total'     => 0,
                            'succeed'   => 0
                        ];
                    }
                    $ListData[$Code]['total']       += $val[2];
                    $ListData[$Code]['succeed']     += $val[3];

                    if(!isset($ListDetail[$val[0]])){
                        $ListDetail[$val[0]]    = [];
                    }
                    $ListDetail[$val[0]][$Code] = [
                        'code'      => $Code,
                        'total'     => $val[2],
                        'succeed'   => $val[3]
                    ];

                    $ListCode[]         = $Code;
                    $ListEmail[]        = trim(strtolower($val[0]));
                }
            }

            if(empty($ListCode) || empty($ListEmail)){
                $this->error_message    = 'Empty list_code or list_email';
                return false;
            }

            //Get List User
            $ListUser   = \User::whereIn('email', $ListEmail)->get(['id','email'])->toArray();
            if(empty($ListUser)){
                $this->error_message    = 'Email is incorrect';
                return false;
            }
            $ReferEmail = [];
            foreach($ListUser as $val){
                $ReferEmail[$val['id']] = $val['email'];
            }

            $ListKpi    = \reportmodel\KPICategoryModel::whereIn('code', $ListCode)
                                                       ->where('active', 1)
                                                       ->get(['id','code','percent','weight','target'])->toArray();
            if(empty($ListKpi)){
                $this->error_message    = 'Empty list kpi';
                return false;
            }

            $ListCode       = [];
            $ReferCode      = [];
            foreach($ListKpi as $val){
                $ListCode[]             = $val['id'];
                $ReferCode[$val['id']]  = $val;
            }

            //Danh sách công việc được giao
            $ListAssign = \reportmodel\KPIConfigModel::whereIn('category_id', $ListCode)
                                                     ->where('active',1)
                                                     ->get(['category_id', 'user_id'])->toArray();
            if(empty($ListAssign)){
                $this->error_message    = 'Empty list assign';
                return false;
            }

            $ListInsert     = [];
            $ListUserInsert = [];
            foreach($ListAssign as $val){
                if(!isset($ReferEmail[$val['user_id']]) || !isset($ReferCode[$val['category_id']])
                    || !isset($ListData[$ReferCode[$val['category_id']]['code']])
                    || !isset($ListDetail[$ReferEmail[$val['user_id']]])
                    || !isset($ListDetail[$ReferEmail[$val['user_id']]][$ReferCode[$val['category_id']]['code']])
                ){
                    $this->error_message    = 'Empty user_id or code : '.$val['user_id'].' - '. $val['category_id'];
                    return false;
                }
                $Category   = $ReferCode[$val['category_id']];
                $Data       = $ListData[$ReferCode[$val['category_id']]['code']];
                $DataUser   = $ListDetail[$ReferEmail[$val['user_id']]][$ReferCode[$val['category_id']]['code']];

                $ListInsert[$val['category_id']]    = [
                    'date'              => $Date,
                    'user_id'           => 0,
                    'category_id'       => $val['category_id'],
                    'percent'           => (($Data['total'] > 0) ? round(($Data['succeed']/$Data['total']),3) : 0),
                    'succeed'           => $Data['succeed'],
                    'total'             => $Data['total'],
                    'weight'            => $Category['weight'],
                    'succeed_target'    => $Category['target'],
                    'percent_target'    => $Category['percent'],
                ];

                $ListUserInsert[]   = [
                    'date'              => $Date,
                    'user_id'           => $val['user_id'],
                    'category_id'       => $val['category_id'],
                    'percent'           => ($DataUser['total'] > 0 ? round(($DataUser['succeed']/$DataUser['total']),3) : 0),
                    'succeed'           => $DataUser['succeed'],
                    'total'             => $DataUser['total'],
                    'weight'            => $Category['weight'],
                    'succeed_target'    => $Category['target'],
                    'percent_target'    => $Category['percent'],
                ];
            }

            $Data = array_merge($ListInsert, $ListUserInsert);
            try{
                $KPIModel   = new \reportmodel\KPIModel;
                foreach($Data as $val){
                    $Model = clone $KPIModel;
                    $Kpi   = $Model::firstOrNew(['date' => $Date, 'user_id' => $val['user_id'], 'category_id' => $val['category_id']]);
                    $Kpi->percent           = $val['percent'];
                    $Kpi->succeed           = $val['succeed'];
                    $Kpi->total             = $val['total'];
                    $Kpi->weight            = $val['weight'];
                    $Kpi->succeed_target   = $val['succeed_target'];
                    $Kpi->percent_target   = $val['percent_target'];
                    $Kpi->save();
                }
                return true;
            }catch (Exception $e){
                return false;
            }

        }
        return false;
    }

    private function Courier(){
        $CourierController  = new CourierController;
        return $CourierController->getCache();
    }

    //Update Journey
    public function getJourney($id){
        $CountryId  = Input::has('country_id')          ? (int)Input::get('country_id')                 : 237;
        $UserInfo   = $this->UserInfo();

        $LMongo     = LMongo::collection('log_upload_change')->where('country_id', $CountryId)->where('partner', $id);
        $this->total                    = $LMongo->where('active',0)->count();
        $this->data_update['status']    = 'SUCCESS';
        $this->data_update['active']    = 1;
        if($this->total > 0){
            $Item = $LMongo->where('active',0)->first();
            if(empty($Item['tracking_code'])){
                $OrdersModel    = new OrdersModel;
                $Order          = $OrdersModel::where('time_accept','>=',$this->time() - $this->time_limit)
                                              ->where('courier_tracking_code', $Item['courier_track_code'])
                                              ->where('from_country_id',$CountryId)
                                              ->first(['id', 'tracking_code', 'courier_tracking_code']);
                if(!isset($Order->tracking_code)){
                    $this->data_update['status']        = 'ORDER_NOT_EXISTS';
                    $this->data_update['active']        = 2;
                    return $this->ResponseData($Item['_id'], 1);
                }

                $Item['tracking_code']  = $Order->tracking_code;
            }

            $Courier    = $this->Courier();
            if(empty($Courier) || !isset($Courier[$Item['courier_id']])){
                $this->data_update['status']        = 'COURIER_NOT_EXISTS';
                $this->data_update['active']        = 2;
                return $this->ResponseData($Item['_id'], 0);
            }

            $this->__Logjourney = [
                "tracking_code"     => (string) $Item['tracking_code'],
                'tracking_number'   => (int)substr($Item['tracking_code'],2),
                "input" => [
                    "username" => (string) $Courier[$Item['courier_id']]['prefix'],
                    "function" => (string)  "LichTrinh",
                    "params" => [
                        "SC_CODE"   => (string)  $Item['tracking_code'],
                        "STATUS"    => (int)$Item['status_sc'],
                        "CITY"      => (string)  $Item['city'],
                        "NOTE"      => (string)  $Item['content'],
                    ],
                    "TrackingOrder" => (string) !empty($Item['courier_track_code']) ? $Item['courier_track_code'] : $Item['tracking_code'],
                    "TrackingCode"  => (string) $Item['tracking_code'],
                    "Status"        => (int)$Item['status_sc'],
                    "Note"          => (string) $Item['content'],
                    "City"          => (string) $Item['city']
                ],
                'UserId'            => (int)$UserInfo['id'],
                "accept"            => 0,
                "priority"          => 1,
                "time_create"       => $this->time(),
                "time_update"       => $this->time()
            ];

            try{
                $LMongo = new LMongo;
                $IdLog  = $LMongo::collection('log_journey_lading')->insert($this->__Logjourney);
            }catch (Exception $e){
                $this->data_update['status']     = 'INSERT_FAIL';
                $this->data_update['active']     = 2;
                return $this->ResponseData($Item['_id'], 1);
            }

            $this->RabbitJourney((string)$IdLog);

            return $this->ResponseData($Item['_id'], 1);
        }else{
            return $this->ResponseData($id, 0); // End
        }
    }

    //DS Status
    public function getStatusVerify($id = ''){
        //header("Access-Control-Allow-Origin: *");
        $UserInfo   = ['id' => 1];
        $CountryId  = Input::has('country_id')          ? (int)Input::get('country_id')                 : 237;

        $LMongo     = LMongo::collection('log_upload_change')->where('partner', $id)->where('country_id', $CountryId);
        $this->total                    = $LMongo->where('active',0)->count();
        $this->data_update['status']    = 'SUCCESS';
        $this->data_update['active']    = 1;
        if($this->total > 0){
            $Item = $LMongo->where('active',0)->first();

            $OrdersModel    = new OrdersModel;
            $OrdersModel    = $OrdersModel::where('time_accept','>=',$this->time() - $this->time_limit)
                                          ->where('from_country_id', $CountryId);

            if(!empty($Item['tracking_code'])){
                $OrdersModel    = $OrdersModel->where('tracking_code', $Item['tracking_code']);
            }else{
                $OrdersModel    = $OrdersModel->where('courier_tracking_code', $Item['courier_tracking_code']);
            }

            $Order = $OrdersModel->first(['id', 'tracking_code', 'courier_tracking_code', 'courier_id', 'status']);
            if(!isset($Order->id)){
                $this->data_update['status']        = 'ORDER_NOT_EXISTS';
                $this->data_update['active']        = 2;
                return $this->ResponseData($Item['_id'], 1);
            }

            $this->data_update['status_sc']         = (int)$Order->status;
            Input::merge(['tracking_code' => $Order->tracking_code]);
            $CourierAcceptLadingCtrl    = new \trigger\CourierAcceptLadingCtrl;
            $Detail                     = $CourierAcceptLadingCtrl->getStatusViettel(false);
            if(empty($Detail)){
                $this->data_update['status']        = 'API_FAIL';
                $this->data_update['active']        = 2;
                return $this->ResponseData($Item['_id'], 0);
            }

            if($Detail['error']){
                $this->data_update['params']                = $Detail['params'];
                $this->data_update['status']                = $Detail['message'];
                $this->data_update['error_message']         = $Detail['error_message'];
                $this->data_update['active']        = 2;
                return $this->ResponseData($Item['_id'], 1);
            }

            $this->data_update['status_hvc']            = $Detail['sc_status'];
            $this->data_update['params']                = $Detail['params'];
            $this->data_update['result']                = $Detail['detail'];
            $this->data_update['status']                = $Detail['message'];
            $this->data_update['error_message']         = $Detail['error_message'];
            $this->data_update['active']                = 1;

            if($this->data_update['status_hvc'] != $this->data_update['status_sc']){
                //Insert Log Journey
                $Courier    = $this->Courier();
                if(empty($Courier) || !isset($Courier[$Order->courier_id])){
                    $this->data_update['params']        = $Detail['params'];
                    $this->data_update['status']        = 'COURIER_NOT_EXISTS';
                    $this->data_update['error_message'] = 'Lỗi, hãy thử lại';
                    $this->data_update['active']        = 2;
                    return $this->ResponseData($Item['_id'], 0);
                }

                $Logjourney = [
                    "tracking_code"     => (string) $Order->tracking_code,
                    'tracking_number'   => (int)substr($Order->tracking_code,2),
                    "input" => [
                        "username" => (string) $Courier[$Order->courier_id]['prefix'],
                        "function" => (string)  "LichTrinh",
                        "params" => [
                            "SC_CODE"   => (string)  $Order->tracking_code,
                            "STATUS"    => (int)$Detail['sc_status'],
                            "CITY"      => (string)  (isset($Detail['detail']->TENTINH) && !empty($Detail['detail']->TENTINH))  ? $Detail['detail']->TENTINH : 'SC-HN',
                            "NOTE"      => (string)  (isset($Detail['detail']->GHICHU) && !empty($Detail['detail']->GHICHU))  ? $Detail['detail']->GHICHU : 'Cập nhật trạng thái tự động từ Viettel'
                        ],
                        "TrackingOrder" => (string) !empty($Item['courier_track_code']) ? $Item['courier_track_code'] : $Item['tracking_code'],
                        "TrackingCode"  => (string) $Item['tracking_code'],
                        "Status"        => (int)$Detail['sc_status'],
                        "Note"          => (string)  (isset($Detail['detail']->GHICHU) && !empty($Detail['detail']->GHICHU))  ? $Detail['detail']->GHICHU : 'Cập nhật trạng thái tự động từ Viettel',
                        "City"          => (string)  (isset($Detail['detail']->TENTINH) && !empty($Detail['detail']->TENTINH))  ? $Detail['detail']->TENTINH : 'SC-HN'
                    ],
                    'UserId'            => (int)1,
                    "accept"            => 0,
                    "priority"          => 1,
                    "time_create"       => $this->time(),
                    "time_update"       => $this->time()
                ];

                try{
                    $LMongo = new LMongo;
                    $IdLog  = $LMongo::collection('log_journey_lading')->insert($Logjourney);
                }catch (Exception $e){
                    $this->data_update['status']     = 'INSERT_FAIL';
                    $this->data_update['active']     = 2;
                    return $this->ResponseData($Item['_id'], 1);
                }

                $this->PredisJourney((string)$IdLog);
            }

            return $this->ResponseData($Item['_id'], 1);
        }else{
            return $this->ResponseData($id, 0); // End
        }
    }

    // Update Weight
    public function getWeight($id){
        $UserInfo   = $this->UserInfo();
        $CountryId  = Input::has('country_id')          ? (int)Input::get('country_id')                 : 237;

        $LMongo     = LMongo::collection('log_upload_change')->where('partner', $id)->where('country_id', $CountryId);
        $this->total                    = $LMongo->where('active',0)->count();
        $this->data_update['status']    = 'SUCCESS';
        $this->data_update['active']    = 1;
        if($this->total > 0){
            $Item = $LMongo->where('active',0)->first();

            $Input['total_weight']      = (int)$Item['weight'];
            $Input['from_country_id']   = $CountryId;

            if(!empty($Item['tracking_code'])){
               $Input['TrackingCode']   = $Item['tracking_code'];
            }elseif(!empty($Item['courier_track_code'])){
                $Input['CourierTrackingCode']   = $Item['courier_track_code'];
            }

            Input::merge($Input);
            $ChangeOrderCtrl    = new ChangeOrderCtrl;
            $Update = $ChangeOrderCtrl->postEdit(false);
            

            if(!$Update){
                $this->data_update['status']     = 'API_FAIL';
                $this->data_update['active']     = 2;
                return $this->ResponseData($Item['_id'], 1);
            }
            /*try{
                $Update = $ChangeOrderCtrl->postEdit(false);
            }catch (Exception $e){var_dump($e->getMessage());

            }*/

            if($Update['error']){
                $this->data_update['active']     = 2;
                $this->data_update['status']     = (string)$Update['message'];
            }else{
                $this->data_update['active']        = 1;
                $this->data_update['status']        = "SUCCESS";
                $this->data_update['old_weight']    = $Input['total_weight'];
                if(!empty($Update['data_log'])){
                    if(!empty($Update['data_log']['total_weight'])){
                        $this->data_update['old_weight']    = $Update['data_log']['total_weight']['old'];
                    }
                }
            }

            return $this->ResponseData($Item['_id'], 1);
        }else{
            return $this->ResponseData($id, 0); // End
        }
    }

    // Update Weight
    public function getProcess($id){
        $UserInfo   = $this->UserInfo();
        $CountryId  = Input::has('country_id')          ? (int)Input::get('country_id')                 : 237;

        $LMongo     = LMongo::collection('log_upload_change')->where('partner', $id)->where('country_id', $CountryId);

        $this->total                    = $LMongo->where('active',0)->count();
        $this->data_update['status']    = 'SUCCESS';
        $this->data_update['active']    = 1;
        if($this->total > 0){
            $Item = $LMongo->where('active',0)->first();

            // Theo đơn hàng
            if(in_array($Item['type_process'], [1]) || ($Item['type_process'] == 5 && $Item['group_process'] != 107)){
                $Order          = OrdersModel::where('time_accept','>=',$this->time() - $this->time_limit)
                                             ->where('from_country_id', $CountryId)
                                             ->where('tracking_code', $Item['tracking_code'])->first(['id', 'tracking_code', 'status']);

                if(!isset($Order->tracking_code)){
                    $this->data_update['status']        = 'ORDER_NOT_EXISTS';
                    $this->data_update['active']        = 2;
                    return $this->ResponseData($Item['_id'], 1);
                }

                $TrackingCode = $Order->id;
            }elseif(in_array($Item['type_process'], [2,4])){
                $User = \User::where('email', $Item['tracking_code'])->first(['id','email']);
                if(!isset($User->id)){
                    $this->data_update['status']        = 'USER_NOT_EXISTS';
                    $this->data_update['active']        = 2;
                    return $this->ResponseData($Item['_id'], 1);
                }

                $TrackingCode = $User->id;
            }else{
                $this->data_update['status']        = 'NOT_ALLOW';
                $this->data_update['active']        = 2;
                return $this->ResponseData($Item['_id'], 1);
            }


            $PipeStatusModel    = new PipeStatusModel;
            $Status             = $PipeStatusModel::where('type',$Item['type_process'])
                                                  ->where('active',1)
                                                  ->where('group_status', $Item['group_process'])
                                                  ->where('status', $Item['pipe_status'])
                                                  ->remember(10)
                                                  ->count();
            if(empty($Status)){
                $this->data_update['status']        = 'STATUS_ERROR';
                $this->data_update['active']        = 2;
                return $this->ResponseData($Item['_id'], 1);
            }

            $PipeJourneyModel   = new PipeJourneyModel;
            try{
                $PipeJourneyModel->insert(
                    [
                        'user_id'       => (int)$UserInfo['id'],
                        'tracking_code' => $TrackingCode,
                        'type'          => $Item['type_process'],
                        'group_process' => $Item['group_process'],
                        'pipe_status'   => $Item['pipe_status'],
                        'note'          => $Item['note'],
                        'time_create'   => $this->time()
                    ]
                );
            }catch (Exception $e){
                $this->data_update['status']        = 'UPDATE_STATUS_FAIL';
                $this->data_update['active']        = 2;
                return $this->ResponseData($Item['_id'], 1);
            }

            return $this->ResponseData($Item['_id'], 1);
        }else{
            return $this->ResponseData($id, 0); // End
        }
    }

    public function getEstimate($id){
        $CountryId  = Input::has('country_id')          ? (int)Input::get('country_id')                 : 237;

        $LMongo     = LMongo::collection('log_upload_change')->where('partner', $id)->where('country_id', $CountryId);
        $this->total                    = $LMongo->where('active',0)->count();
        $this->data_update['status']    = 'SUCCESS';
        $this->data_update['active']    = 1;
        if($this->total > 0){
            $Item = $LMongo->where('active',0)->first();
            $Item['from_city']      = trim(strtoupper($Item['from_city']));
            $Item['to_city']        = trim(strtoupper($Item['to_city']));
            $Location = \CourierLocationModel::where('courier_id', (int)$Item['courier_id'])
                        ->whereIn('courier_city_id',[$Item['from_city'], $Item['to_city']])
                        ->where('province_id','>',0)->where('ward_id',0)->get()->toArray();

            if(empty($Location)){
                $this->data_update['status']        = 'LOCATION_NOT_EXISTS';
                $this->data_update['active']        = 2;
                return $this->ResponseData($Item['_id'], 1);
            }

            $ListFromDistrict   = [];
            $ListToDistrict     = [];
            foreach($Location as $val){
                if($val['courier_city_id']    == $Item['from_city']){
                    $ListFromDistrict[]                 = (int)$val['province_id'];
                    $this->data_update['sc_from_city']  = (int)$val['city_id'];
                }
                if($val['courier_city_id']    == $Item['to_city']){
                    $ListToDistrict[] = (int)$val['province_id'];
                    $this->data_update['sc_to_city']  = (int)$val['city_id'];
                }
            }

            $ListFromDistrict   = array_unique($ListFromDistrict);
            $ListToDistrict     = array_unique($ListToDistrict);

            if(empty($ListFromDistrict)){
                $this->data_update['status']        = 'FROM_DISTRICT_EMPTY';
                $this->data_update['active']        = 2;
                return $this->ResponseData($Item['_id'], 1);
            }

            if(empty($ListToDistrict)){
                $this->data_update['status']        = 'TO_DISTRICT_EMPTY';
                $this->data_update['active']        = 2;
                return $this->ResponseData($Item['_id'], 1);
            }

            $this->data_update['from_district'] = implode(',',$ListFromDistrict);
            $this->data_update['to_district']   = implode(',',$ListToDistrict);
            // action
            try{
                foreach($ListFromDistrict as $val){
                    foreach($ListToDistrict as $v){
                        $Promise = \systemmodel\CourierPromiseModelDev::firstOrNew(['courier_id' => $Item['courier_id'], 'service_id' => $Item['service_id'], 'from_district' => $val, 'to_district' => $v]);
                        if(!isset($Promise->id)){
                            $Promise->priority = 6;
                        }
                        $Promise->courier_estimate_delivery = $Item['estimate'] * 3600;
                        $Promise->save();
                    }
                }
            }catch (Exception $e){
                $this->data_update['status']        = 'UPDATE_ERROR';
                $this->data_update['active']        = 2;
                return $this->ResponseData($Item['_id'], 1);
            }

            return $this->ResponseData($Item['_id'], 1);
        }else{
            return $this->ResponseData($id, 0); // End
        }
    }

    public function getEstimatePlus($id){
        $CountryId  = Input::has('country_id')          ? (int)Input::get('country_id')                 : 237;

        $LMongo     = LMongo::collection('log_upload_change')->where('partner', $id)->where('country_id', $CountryId);
        $this->total                    = $LMongo->where('active',0)->count();
        $this->data_update['status']    = 'SUCCESS';
        $this->data_update['active']    = 1;
        if($this->total > 0){
            $Item = $LMongo->where('active',0)->first();
            $Item['city']           = trim(strtoupper($Item['city']));
            $Item['district']       = trim(strtoupper($Item['district']));
            $Location = \CourierLocationModel::where('courier_id', (int)$Item['courier_id'])
                ->where('courier_city_id', $Item['city'])
                ->where('courier_province_id',$Item['district'])->where('ward_id',0)->first();

            if(!isset($Location->id)){
                $this->data_update['status']        = 'LOCATION_NOT_EXISTS';
                $this->data_update['active']        = 2;
                return $this->ResponseData($Item['_id'], 1);
            }

            $this->data_update['sc_city']       = $Location->city_id;
            $this->data_update['sc_district']   = $Location->province_id;
            // action
            try{
                $Estimate = $Item['estimate'] * 3600;;
                \systemmodel\CourierPromiseModelDev::where('courier_id', $Item['courier_id'])
                                                   ->where('service_id', $Item['service_id'])
                                                   ->where('to_district', $Location->province_id)
                                                   ->increment('courier_estimate_delivery',$Estimate);
            }catch (Exception $e){
                $this->data_update['status']        = 'UPDATE_ERROR';
                $this->data_update['active']        = 2;
                return $this->ResponseData($Item['_id'], 1);
            }

            return $this->ResponseData($Item['_id'], 1);
        }else{
            return $this->ResponseData($id, 0); // End
        }
    }

    /**
     * response
     */
    private function ResponseData( $id, $active){
        if($active == 0){
            \LMongo::collection('log_import')->where('_id', new \MongoId($id))->update(array('action.insert' => 1));
            $contents = array(
                'error'     => false,
                'total'     => 0,
                'message'   => 'SUCCESS'
            );
        }else{
            \LMongo::collection('log_upload_change')->where('_id', new \MongoId($id))->update($this->data_update);

            $contents = array(
                'error'             => ($this->data_update['status'] == 'SUCCESS') ? false : true,
                'total'             => $this->total + 1,
                'message'           => $this->data_update['status']
            );
        }

        return Response::json($contents);
    }

    /**
     * Export Excel
     */
    private function ExEstimate($Data){
        return Response::json([
            'error'     => false,
            'message'   => 'SUCCESS',
            'data'      => $Data
        ]);
    }

    private function ExWeight($Data){
        $FileName   = 'Danh_sach_cap_nhat_vuot_can';

        return \Excel::create($FileName, function($excel) use($Data){
            $excel->sheet('Sheet1', function($sheet) use($Data){
                $sheet->mergeCells('D1:F1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','Danh sách cập nhật vượt cân'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30
                ));

                $sheet->row(3, array(
                    'STT', 'Mã đơn hàng', 'Mã hãng vận chuyển', 'Khối lượng', 'Khối lượng cũ', 'Trạng thái'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });

                $sheet->setBorder('A3:F3', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        isset($val['tracking_code'])        ? $val['tracking_code']             : '',
                        isset($val['courier_track_code'])   ? $val['courier_track_code']        : '',
                        isset($val['weight'])               ? number_format($val['weight'])     : '',
                        isset($val['old_weight'])           ? number_format($val['old_weight']) : '',
                        isset($val['status'])               ? $val['status']                    : '',
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }

    private function ExJourney($Data){
        $FileName   = 'Danh_sach_cap_nhat_trang_thai';
        $Courier    = $Status = [];
        if(!empty($Data)){
            $Courier    = $this->getCourier();
            $Status     = $this->getStatus();
        }

        return \Excel::create($FileName, function($excel) use($Data, $Courier, $Status){
            $excel->sheet('Sheet1', function($sheet) use($Data, $Courier, $Status){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách cập nhật trạng thái'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     => 80, 'H'      => 30
                ));

                $sheet->row(3, array(
                    'STT', 'Mã đơn hàng', 'Mã hãng vận chuyển', 'Hãng vận chuyển', 'Trạng thái SC', 'Thành Phố', 'Nội dung', 'Trạng thái'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });

                $sheet->setBorder('A3:H3', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        isset($val['tracking_code'])                ? $val['tracking_code']             : '',
                        isset($val['courier_track_code'])           ? $val['courier_track_code']        : '',
                        isset($Courier[(int)$val['courier_id']])    ? $Courier[(int)$val['courier_id']] : '',
                        isset($Status[(int)$val['status_sc']])      ? $Status[(int)$val['status_sc']]   : '',
                        isset($val['city'])                         ? $val['city']                      : '',
                        isset($val['content'])                      ? $val['content']                   : '',
                        isset($val['status'])                       ? $val['status']                    : '',
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }

    private function ExProcess($Data){
        $FileName   = 'Danh_sach_cap_nhat_xu_ly';
        $Process    = $Status = [];
        $PipeStatusController   = new \oms\PipeStatusController;

        if(!empty($Data)){
            $Status     = $this->getStatus();

            Input::merge(['type'    => 1]);
            $ProcessStatus      = $PipeStatusController->getPipebygroup(false);
            if(!empty($ProcessStatus)){
                foreach($ProcessStatus as $val){
                    if(!isset($Process[(int)$val['group_status']])){
                        $Process[(int)$val['group_status']] = [];
                    }

                    $Process[(int)$val['group_status']][(int)$val['status']]    = $val['name'];
                }
            }
        }

        return \Excel::create($FileName, function($excel) use($Data, $Process, $Status){
            $excel->sheet('Sheet1', function($sheet) use($Data, $Process, $Status){
                $sheet->mergeCells('D1:F1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','Danh sách cập nhật xử lý'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  60, 'D'     =>  60, 'E'     =>  60, 'F'     =>  30
                ));

                $sheet->row(3, array(
                    'STT', 'Mã đơn hàng', 'Trạng thái SC', 'Trạng thái xử lý', 'Ghi chú', 'Trạng thái'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });

                $sheet->setBorder('A3:F3', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        isset($val['tracking_code'])                ? $val['tracking_code']             : '',
                        isset($Status[(int)$val['sc_status']])      ? $Status[(int)$val['sc_status']]   : '',
                        (isset($Process[(int)$val['sc_status']]) && isset($Process[(int)$val['sc_status']][(int)$val['process_status']])) ?  $Process[(int)$val['sc_status']][(int)$val['process_status']] : '',
                        isset($val['note'])                         ? $val['note']                      : '',
                        isset($val['status'])                       ? $val['status']                    : '',
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }

    private function ExStatus($Data){
        $FileName   = 'Danh_sach_doi_soat_trang_thai_don_hang';
        $Courier    = $Status = [];
        if(!empty($Data)){
            $Courier    = $this->getCourier();
            $Status     = $this->getStatus();
        }

        return \Excel::create($FileName, function($excel) use($Data, $Courier, $Status){
            $excel->sheet('Sheet1', function($sheet) use($Data, $Courier, $Status){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách đối soát trạng thái đơn hàng'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  60, 'F'     =>  60, 'G'     => 30, 'H'  => 30
                ));

                $sheet->row(3, array(
                    'STT', 'Mã đơn hàng', 'Mã hãng vận chuyển', 'Hãng vận chuyển', 'Trạng thái SC', 'Trạng thái HVC', 'Trạng thái', 'Trạng thái xử lý'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });

                $sheet->setBorder('A3:H3', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        isset($val['tracking_code'])                                            ? $val['tracking_code']                 : '',
                        isset($val['courier_track_code'])                                       ? $val['courier_track_code']            : '',
                        isset($Courier[(int)$val['courier_id']])                                ? $Courier[(int)$val['courier_id']]     : '',
                        (isset($val['status_sc']) && isset($Status[(int)$val['status_sc']]))    ? $Status[(int)$val['status_sc']]       : '',
                        (isset($val['status_hvc']) && isset($Status[(int)$val['status_hvc']]))  ? $Status[(int)$val['status_hvc']]      : '',
                        (isset($val['status_hvc']) &&  ($val['status_sc'] == $val['status_hvc']))                               ? 'Chính xác'                           : 'Sai lệch',
                        isset($val['status'])                                                   ? $val['status']                        : '',
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }

    /**
     * Report Excel
     */

    public function getTemplateprocess(){
        $TypeProcess    = Input::has('type_process')    ? strtolower(trim(Input::get('type_process')))  : 1;

        $PipeStatusModel    = new PipeStatusModel;
        $ListStatus         = $PipeStatusModel::where('type',$TypeProcess)
                                              ->where('active',1)
                                              ->orderBy('group_status','ASC')
                                              ->get()->toArray();

        $ListData   = [];
        $ListCount  = [];
        $Max        = 0;
        $last_group = 0;

        foreach($ListStatus as $val){
            $name   = $val['name'].'_'.(int)$val['status'];
            if(!isset($ListCount[$val['group_status']])){
                $ListCount[$val['group_status']]    = 0;
            }
            $ListCount[$val['group_status']]++;

            switch ($val['group_status']) {
                case 23:
                    $ListData[1][]  = $name;
                break;

                case 24:
                    $ListData[2][]  = $name;
                break;

                case 25:
                    $ListData[3][]  = $name;
                break;

                case 26:
                    $ListData[4][]  = $name;
                break;

                case 27:
                    $ListData[5][]  = $name;
                break;

                case 28:
                    $ListData[6][]  = $name;
                break;

                case 29:
                    $ListData[7][]  = $name;
                    break;

                case 30:
                    $ListData[8][]  = $name;
                    break;

                case 31:
                    $ListData[9][]  = $name;
                    break;

                case 32:
                    $ListData[10][]  = $name;
                break;

                case 33:
                    $ListData[11][]  = $name;
                    break;

                case 35:
                    $ListData[12][]  = $name;
                    break;

                case 36:
                    $ListData[13][]  = $name;
                break;

                case 40:
                    $ListData[14][]  = $name;
                break;

                default:
                    break;
            }
        }

        $Max    = max($ListCount);
        $Data   = [];


        for ($x = 0; $x < $Max; $x++) {
            for ($y = 0; $y <= 14; $y++) {
                if(!isset($ListData[$y][$x]) || empty($ListData[$y][$x])){
                    $Data[$x][$y]   = '';
                }else{
                    $Data[$x][$y]   = $ListData[$y][$x];
                }
            }
        }


        Excel::selectSheetsByIndex(0, 1)->load('/data/www/html/storage/template/Template_Process.xlsx', function($reader) use($Data) {
            $reader->sheet('statusgroup',function($sheet) use($Data)
            {
                foreach($Data as $val){
                    $sheet->appendRow($val);
                }

            });
        },'UTF-8',true)->export('xlsx');
    }
}
