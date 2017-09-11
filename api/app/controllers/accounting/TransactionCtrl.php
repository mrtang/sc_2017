<?php namespace accounting;

use accountingmodel\TransactionModel;

class TransactionCtrl extends BaseCtrl {
    private $data   = [];
    private $user   = [];
    private $total  = 0;


    //List Transaction Accounting
    public function getIndex()
    {
        $page                   = Input::has('page')                    ? (int)Input::get('page')                   : 1;
        $TimeStart              = Input::has('time_start')              ? trim(Input::get('time_start'))            : '';
        $TimeEnd                = Input::has('time_end')                ? trim(Input::get('time_end'))              : '';
        $FirstShipmentStart     = Input::has('first_shipment_start')    ? trim(Input::get('first_shipment_start'))  : '';
        $ReferCode              = Input::has('refer_code')              ? trim(Input::get('refer_code'))            : '';
        $Search                 = Input::has('search')                  ? trim(Input::get('search'))                : '';
        $Type                   = Input::has('type')                    ? (int)Input::get('type')                   : 0;
        $cmd                    = Input::has('cmd')                     ? trim(strtolower(Input::get('cmd')))       : '';
        $itemPage               = 20;

        $Model = new TransactionModel;

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

        if(!empty($Type)){
            $Model  = $Model->where('type', $Type);
        }

        if(!empty($Search)){
            $ModelUser  = new \User;
            if (filter_var($Search, FILTER_VALIDATE_EMAIL)){  // search email
                $ModelUser          = $ModelUser->where('email', $Search);
            }elseif(filter_var((int)$Search, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $ModelUser          = $ModelUser->where('phone', $Search);
            }else{ // search code
                $ModelUser          = $ModelUser->where('fullname','LIKE','%'.$Search.'%');
            }
            $ListUser               = $ModelUser->lists('id');

            if(empty($ListUser)){
                return $this->ResponseData(false);
            }
        }

        if(!empty($FirstShipmentStart)){
            $User = $this->__get_user_boxme($FirstShipmentStart);
            if(empty($User)){
                return $this->ResponseData(false);
            }

            if(!empty($ListUser)){
                $ListUser   = array_intersect($ListUser, $User);
            }else{
                $ListUser   = $User;
            }
        }

        if(!empty($ListUser)){
            $ListUser[]     = 1;
            $Model          = $Model->where(function($query) use($ListUser){
                $query->whereIn('from_user_id',$ListUser)
                    ->whereIn('to_user_id',$ListUser);
            });
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

        Excel::selectSheetsByIndex(0)->load('/data/www/html/storage/template/accounting/lich_su_giao_dich.xls', function($reader) use($Data,$User) {
            $reader->sheet(0,function($sheet) use($Data,$User)
            {
                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        $val['time_create'] > 0 ? date("d/m/y H:m",$val['time_create']) : '',
                        isset($User[$val['from_user_id']]) ? $User[$val['from_user_id']]['email'] : '',
                        isset($User[$val['to_user_id']]) ? $User[$val['to_user_id']]['email'] : '',
                        $val['refer_code'],
                        number_format($val['money']),
                        number_format($val['balance_before']),
                        $val['note']
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        },'UTF-8',true)->export('xls');
    }

    private function ResponseData($error){
        $Cmd                = Input::has('cmd')                 ? strtolower(trim(Input::get('cmd')))                   : '';

        if($Cmd == 'export'){
            return $this->ExportExcel([]);
        }

        return Response::json([
            'error'         => $error,
            'code'          => 'success',
            'error_message' => 'Thành công',
            'item_page'     => 20,
            'total'         => $this->total,
            'data'          => $this->data,
            'user'          => $this->user
        ]);
    }
}
