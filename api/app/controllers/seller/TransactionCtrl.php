<?php namespace seller;

use Response;
use Exception;
use Input;
use accountingmodel\TransactionModel;
use Validator;
use User;
use Lang;

class TransactionCtrl extends \BaseController {

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
        $Search         = Input::has('search')          ? trim(Input::get('search'))             : '';
        $cmd            = Input::has('cmd')             ? trim(strtolower(Input::get('cmd')))    : '';

        $Model      = new TransactionModel;
        $UserInfo   = $this->UserInfo();

        $Model = $Model->where(function($query) use($UserInfo){
            $query->where('from_user_id',(int)$UserInfo['id'])
                  ->orWhere('to_user_id', (int)$UserInfo['id']);
        });



        if(!empty($TimeStart)){
            $Model = $Model->where('time_create','>=',$TimeStart);
        }else{
            $Model = $Model->where('time_create','>=',$this->time() - $this->time_limit);
        }

        if(!empty($TimeEnd)){
            $Model = $Model->where('time_create','<',$TimeEnd);
        }

        if(!empty($Search)){
            $Model = $Model->where('refer_code','LIKE','%'.$Search.'%');
        }

        $ModelTotal = clone $Model;

        $Total = $ModelTotal->count();
        $Data  = [];
        $User  = [];

        $Model = $Model->orderBy('time_create','DESC');

        if($cmd == 'export'){
            $Data = $Model->get()->toArray();
            return $this->getExcel($Data);
        }

        if($Total > 0){
            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }

            $Data = $Model->get()->toArray();

            /*if(!empty($Data)){
                foreach($Data as $val){
                    $ListUserId[]   = (int)$val['from_user_id'];
                    $ListUserId[]   = (int)$val['to_user_id'];
                }

                if(!empty($ListUserId)){
                    $ListUserId = array_unique($ListUserId);
                    $UserModel  = new User;
                    $ListUser   = $UserModel::whereIn('id',$ListUserId)->get(['id','fullname','email','phone'])->toArray();

                    if(!empty($ListUser)){
                        foreach($ListUser as $val){
                            $User[(int)$val['id']]  = $val;
                        }
                    }
                }
            }*/
        }

        $contents = array(
            'error'         => false,
            'message'       => Lang::get('response.SUCCESS'),
            'item_page'     => $itemPage,
            'total'         => $Total,
            'data'          => $Data,
            //'user'          => $User
        );

        return Response::json($contents);
	}

    //List Transaction Accounting
    public function getAccounting()
    {
        $UserInfo   = $this->UserInfo();
        if($UserInfo['privilege'] < 1){
            return Response::json([
                'error'         => false,
                'message'       => Lang::get('response.SUCCESS'),
                'item_page'     => 20,
                'total'         => 0,
                'data'          => [],
                'user'          => []
            ]);
        }

        $page           = Input::has('page')            ? (int)Input::get('page')                : 1;
        $itemPage       = Input::has('limit')           ? Input::get('limit')                    : 20;
        $TimeStart      = Input::has('time_start')      ? trim(Input::get('time_start'))         : '';
        $TimeEnd        = Input::has('time_end')        ? trim(Input::get('time_end'))           : '';
        $ReferCode      = Input::has('refer_code')      ? trim(Input::get('refer_code'))         : '';
        $Search         = Input::has('search')          ? trim(Input::get('search'))             : '';
        $cmd            = Input::has('cmd')             ? trim(strtolower(Input::get('cmd')))    : '';

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

        if(!empty($Search)){
            $ModelUser  = new \User;
            if (filter_var($Search, FILTER_VALIDATE_EMAIL)){  // search email
                $ModelUser          = $ModelUser->where('email','LIKE','%'.$Search.'%');
            }elseif(filter_var((int)$Search, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $ModelUser          = $ModelUser->where('phone','LIKE','%'.$Search.'%');
            }else{ // search code
                $ModelUser          = $ModelUser->where('fullname','LIKE','%'.$Search.'%');
            }
            $ListUser = $ModelUser->get(array('id'))->toArray();

            if(!empty($ListUser)){
                $UserId[]       = 1;
                foreach($ListUser as $val){
                    $UserId[]     =   (int)$val['id'];
                }
                $UserId = array_unique($UserId);
                $Model  = $Model->where(function($query) use($UserId){
                    $query->whereIn('from_user_id',$UserId)
                          ->whereIn('to_user_id',$UserId);
                });
            }else{
                return Response::json([
                    'error'         => false,
                    'message'       => Lang::get('response.SUCCESS'),
                    'item_page'     => 20,
                    'total'         => 0,
                    'data'          => [],
                    'user'          => []
                ]);
            }
        }

        $ModelTotal = clone $Model;

        $Total = $ModelTotal->count();
        $Data  = [];
        $User  = [];

        $Model = $Model->orderBy('time_create','DESC');

        if($cmd == 'export'){
            $Data = $Model->get()->toArray();
            return $this->getExcelSystem($Data);
        }

        if($Total > 0){
            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }

            $Data = $Model->get()->toArray();

            if(!empty($Data)){
                foreach($Data as $val){
                    $ListUserId[]   = (int)$val['from_user_id'];
                    $ListUserId[]   = (int)$val['to_user_id'];
                }

                if(!empty($ListUserId)){
                    $ListUserId = array_unique($ListUserId);
                    $UserModel  = new User;
                    $ListUser   = $UserModel::whereIn('id',$ListUserId)->get(['id','fullname','email','phone'])->toArray();

                    if(!empty($ListUser)){
                        foreach($ListUser as $val){
                            $User[(int)$val['id']]  = $val;
                        }
                    }
                }
            }
        }

        $contents = array(
            'error'         => false,
            'message'       => Lang::get('response.SUCCESS'),
            'item_page'     => $itemPage,
            'total'         => $Total,
            'data'          => $Data,
            'user'          => $User
        );

        return Response::json($contents);
    }

    private function getExcel($Data){
        $FileName   = 'Lich_su_giao_dich';
        $UserInfo   = $this->UserInfo();

        return \Excel::create($FileName, function($excel) use($Data, $UserInfo){
            $excel->sheet('Sheet1', function($sheet) use($Data, $UserInfo){
                $sheet->mergeCells('C1:D1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','Lịch sử giao dịch'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  60
                ));
                $sheet->setMergeColumn(array(
                    'columns' => array('A','B','C','D','E'),
                    'rows' => array(
                        array(3,4)
                    )
                ));

                $sheet->row(3, array(
                    'STT', 'Thời gian', 'Mã tham chiếu', 'Số tiền', 'Lý do'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });

                $sheet->setBorder('A3:E4', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        $val['time_create'] > 0 ? date("d/m/y H:m",$val['time_create']) : '',
                        $val['refer_code'],
                        number_format($val['money']),
                        $val['note']
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }

    private function getExcelSystem($Data){
        $FileName   = 'Lich_su_giao_dich';
        $User       = [];

        if(!empty($Data)){
            foreach($Data as $val){
                $ListUserId[]   = (int)$val['from_user_id'];
                $ListUserId[]   = (int)$val['to_user_id'];
            }

            if(!empty($ListUserId)){
                $ListUserId = array_unique($ListUserId);
                $UserModel  = new User;
                $ListUser   = $UserModel::whereIn('id',$ListUserId)->get(['id','fullname','email','phone'])->toArray();

                if(!empty($ListUser)){
                    foreach($ListUser as $val){
                        $User[(int)$val['id']]  = $val;
                    }
                }
            }
        }

        return \Excel::create($FileName, function($excel) use($Data, $User){
            $excel->sheet('Sheet1', function($sheet) use($Data, $User){
                $sheet->mergeCells('C1:D1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','Lịch sử giao dịch'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  30, 'C'     =>  30, 'D'     =>  30, 'E' => 30, 'F' => 30, 'G' => 30,'H'     =>  60
                ));
                $sheet->setMergeColumn(array(
                    'columns' => array('A','B','C','D','E','F','G','H'),
                    'rows' => array(
                        array(3,4)
                    )
                ));

                $sheet->row(3, array(
                    'STT', 'Thời gian', 'Bên gửi', 'Bên nhận','Mã tham chiếu', 'Số tiền', 'Số dư trước kỳ', 'Lý do'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });

                $sheet->setBorder('A3:H4', 'thin');

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
        })->export('xls');
    }
}
