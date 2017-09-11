<?php namespace bm_accounting;

use bm_accmodel\TransactionModel;
use metadatamodel\OrganizationUserModel;

class TransactionCtrl extends BaseCtrl {
    private $user;
    private $config         = [];
    private $code           = 'success';
    private $message        = 'Thành công';

    public  function __construct(){
        $this->config       = \Config::get('config_api.domain.boxme.accounting');
    }

    //List Transaction Accounting
    public function getIndex()
    {
        $page           = Input::has('page')            ? (int)Input::get('page')                : 1;
        $TimeStart      = Input::has('time_start')      ? trim(Input::get('time_start'))         : '';
        $TimeEnd        = Input::has('time_end')        ? trim(Input::get('time_end'))           : '';
        $ReferCode      = Input::has('refer_code')      ? trim(Input::get('refer_code'))         : '';
        $Search         = Input::has('search')          ? trim(Input::get('search'))             : '';
        $cmd            = Input::has('cmd')             ? trim(strtolower(Input::get('cmd')))    : '';
        $itemPage       = 20;

        $Token              = Input::has('Token')           ? trim(Input::get('Token'))                 : '';
        $MerchantKey        = Input::has('MerchantKey')     ? trim(Input::get('MerchantKey'))           : '';

        $UserInfo   = $this->UserInfo();
        if(empty($UserInfo) && $Token != $this->config){
            $this->code             = 'ERROR';
            $this->message          = 'Token không chính xác';
            return $this->ResponseData(true);
        }

        if(!empty($Token) && empty($MerchantKey)){
            $this->code             = 'ERROR';
            $this->message          = 'MerchantKey không chính xác';
            return $this->ResponseData(true);
        }

        $Model = new TransactionModel;

        if(!empty($MerchantKey)){
            $Organization   = $this->__check_merchant_key();

            if(!$Organization){
                return $this->ResponseData(true);
            }

            $Model          = $Model->where(function($query) use($Organization){
                                    $query->where('from_user_id',$Organization)
                                        ->orWhere('to_user_id',$Organization);
                                });
        }else{
            if(!empty($Search)){
                if (filter_var($Search, FILTER_VALIDATE_EMAIL)){  // search email
                    $User          = \User::where('email',$Search)->lists('organization');
                }elseif(filter_var((int)$Search, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                    $User          = \User::where('phone',$Search)->lists('organization');
                }else{
                    $User          = OrganizationUserModel::where('fullname','LIKE','%'.$Search.'%')->lists('id');
                }

                if(empty($User)){
                    return $this->ResponseData(false);
                }

                $User[]         = 1;
                $Model          = $Model->where(function($query) use($User){
                    $query->whereIn('from_user_id',$User)
                        ->whereIn('to_user_id',$User);
                });
            }
        }

        if(!empty($TimeStart)){
            $Model = $Model->where('time_create','>=',$TimeStart);
        }else{
            $Model = $Model->where('time_create','>=',$this->time() - $this->time_limit);
        }

        if(!empty($TimeEnd)){
            $Model = $Model->where('time_create','<',$TimeEnd);
        }

        if(!empty($ReferCode)){
            $Model = $Model->where('refer_code','LIKE','%'.$ReferCode.'%');
        }

        $ModelTotal = clone $Model;

        $this->total = $ModelTotal->count();
        $Model = $Model->orderBy('time_create','DESC');

        if($cmd == 'export'){
            return $this->ExportExcel($Model->get()->toArray());
        }

        if($this->total > 0){
            $itemPage       = (int)$itemPage;
            $offset         = ($page - 1)*$itemPage;
            $this->data     = $Model->skip($offset)->take($itemPage)->get()->toArray();

            if(!empty($this->data)){
                foreach($this->data as $val){
                    $ListUserId[]   = (int)$val['from_user_id'];
                    $ListUserId[]   = (int)$val['to_user_id'];
                }

                if(!empty($ListUserId)){
                    $ListUserId     = array_unique($ListUserId);
                    $this->user     = $this->getUser($ListUserId);
                }
            }
        }

        return $this->ResponseData(false);
    }

    private function ExportExcel($Data){
        $User       = [];

        if(!empty($Data)){
            foreach($Data as $val){
                $ListUserId[]   = (int)$val['from_user_id'];
                $ListUserId[]   = (int)$val['to_user_id'];
            }

            if(!empty($ListUserId)){
                $ListUserId = array_unique($ListUserId);
                $User       = $this->getUser($ListUserId);
            }
        }

        return  Response::json([
            'error'         => false,
            'code'          => 'success',
            'error_message' => 'Thành công',
            'data'          => $Data,
            'user'          => $User
            ]);
    }

    /*private function ExportExcel1($Data){
        $User       = [];

        if(!empty($Data)){
            foreach($Data as $val){
                $ListUserId[]   = (int)$val['from_user_id'];
                $ListUserId[]   = (int)$val['to_user_id'];
            }

            if(!empty($ListUserId)){
                $ListUserId = array_unique($ListUserId);
                $User       = $this->getUser($ListUserId);
            }
        }

        Excel::selectSheetsByIndex(0)->load('/data/www/html/storage/template/bm_accounting/lich_su_giao_dich.xls', function($reader) use($Data,$User) {
            $reader->sheet(0,function($sheet) use($Data,$User)
            {
                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        $val['time_create'] > 0 ? date("d/m/y H:m",$val['time_create']) : '',
                        isset($User[$val['from_user_id']]) ? $User[$val['from_user_id']]['fullname'] : '',
                        isset($User[$val['to_user_id']]) ? $User[$val['to_user_id']]['fullname'] : '',
                        $val['refer_code'],
                        number_format($val['money']),
                        number_format($val['balance_before']),
                        $val['note']
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        },'UTF-8',true)->export('xls');
    }*/

    private function ResponseData($error){
        $Cmd                = Input::has('cmd')                 ? strtolower(trim(Input::get('cmd')))                   : '';

        if($Cmd == 'export'){
            return $this->ExportExcel([]);
        }

        return Response::json([
            'error'         => $error,
            'code'          => $this->code,
            'error_message' => $this->message,
            'item_page'     => 20,
            'total'         => $this->total,
            'data'          => $this->data,
            'user'          => $this->user
        ]);
    }
}
