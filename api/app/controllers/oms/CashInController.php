<?php
namespace oms;
use DB;
use Input;
use Response;
use Cache;
use sellermodel\CashInModel;
use Validator;
use Excel;
use OrderModel;
use LMongo;



class CashInController extends \BaseController {
    private $domain = '*';

	public function __construct(){

    }

    public function getReport() {
        $timeStart = Input::has('from_date') ? (int)Input::get('from_date') : 0;
        $timeEnd = Input::has('to_date') ? (int)Input::get('to_date') : 0;
        $cardinModel = CashInModel::query();
        if($timeStart==0) {
            $timeStart = strtotime(date("Y-m-d 00:00:00"));
        }
        $orderCreated = CashInModel::where(function($query) use ($timeStart,$timeEnd) {
            if($timeStart>0) {
                $query->where('time_create','>=',$timeStart);
            }
            if($timeEnd>0) {
                $query->where('time_create','<=',$timeEnd);
            }
        })->count();
        $orderSuccess = $cardinModel->where(function($query) use ($timeStart,$timeEnd) {
            $query->where('status','SUCCESS');
            if($timeStart>0) {
                $query->where('time_create','>=',$timeStart);
            }
            if($timeEnd>0) {
                $query->where('time_create','<=',$timeEnd);
            }
        })->count();
        $orderPaid = CashInModel::where(function($query) use ($timeStart,$timeEnd) {
            $query->where('status','PROCESSING');
            if($timeStart>0) {
                $query->where('time_create','>=',$timeStart);
            }
            if($timeEnd>0) {
                $query->where('time_create','<=',$timeEnd);
            }
        })->count();

        $orderAmount = CashInModel::where(function($query) use ($timeStart,$timeEnd) {
            $query->where('status','SUCCESS');
            if($timeStart>0) {
                $query->where('time_create','>=',$timeStart);
            }
            if($timeEnd>0) {
                $query->where('time_create','<=',$timeEnd);
            }
        })->sum('amount');


        $orderBuyAmount = CashInModel::where(function($query) use ($timeStart,$timeEnd) {
            $query->where('status','PROCESSING');
            if($timeStart>0) {
                $query->where('time_create','>=',$timeStart);
            }
            if($timeEnd>0) {
                $query->where('time_create','<=',$timeEnd);
            }
        })->sum('amount');

        $response = [
            'orderCreated'  =>  $orderCreated,
            'orderSuccess'  =>  $orderSuccess,
            'orderPaid'     =>  $orderPaid,
            'orderAmount'   =>  $orderAmount,
            'orderBuyAmount'=>  $orderBuyAmount
        ];
        return Response::json($response);
    }

    public function getListcashin(){
        $email                  = Input::has('email')           ? Input::get('email')              : '';
        $transaction_id         = Input::has('transaction_id')  ? Input::get('transaction_id')     : '';
        $refer_code             = Input::has('refer_code')      ? Input::get('refer_code')         : '';
        $status                 = Input::has('status')          ? Input::get('status')             : '';
        $type                   = Input::has('type')            ? Input::get('type')               : '';

        $from_date              = Input::has('from_date')       ? Input::get('from_date')          : 0;
        $to_date                = Input::has('to_date')         ? Input::get('to_date')            : 0;
        $page                   = Input::has('page')            ? (int)Input::get('page')          : 1 ;
        $itemPage               = Input::has('item_page')       ? (int)Input::get('item_page')     : 20;
        $offset                 = ($page - 1) * $itemPage;

        $Model = new CashInModel;
        $UserModel = new \User;

        $Total = 0;

        $CashinData = $Model->with(['user' => function ($q){
                        $q->select(array('id','email', 'fullname'));
                      }])->orderBy('time_create', 'DESC');


        if(!empty($email)){
            $_u = $UserModel->where('email', $email)->first();
            if(isset($_u->id)){
                $CashinData = $CashinData->where('user_id', $_u->id);
            }else {
                $CashinData = $CashinData->where('user_id', 0);
            }
        }

        if(!empty($transaction_id)){
            $CashinData = $CashinData->where('transaction_id', $transaction_id );
        }

        if(!empty($refer_code)){
            $CashinData = $CashinData->where('refer_code', $refer_code);
        }
        if(!empty($status)){
            $CashinData = $CashinData->where('status', $status);
        }
        if(!empty($type)){
            $CashinData = $CashinData->where('type', $type);
        }

        if($from_date > 0){
            $CashinData = $CashinData->where('time_success','>=',$from_date);
            $CashinData = $CashinData->where('time_create','>=',$from_date-(90*86400));
        }


        if($to_date > 0){
            $CashinData = $CashinData->where('time_success','<',$to_date);
        }

        $Total = $CashinData->count();
        $CashinData = $CashinData->skip($offset)->take($itemPage)->get()->toArray();



        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $CashinData,
            'total'         => $Total,
            'item_page'     => $itemPage,
            'check'         => 1
        );
        return Response::json($contents);
    }


    public function renderStatus($status){
        switch ($status) {
            case 'SUCCESS':
                return 'Thành công';
                break;
            case 'PROCESSING':
                return 'Đã thanh toán';
                break;
            case 'WAITING':
                return 'Chưa thanh toán';
            break;
            case 'CANCEL':
                return 'Đã hủy';
            break;
        }
    }

    
    public function postUpload(){
        $UserInfo   = $this->UserInfo();
        //
        if (Input::hasFile('file')) {
            $File       = Input::file('file');
            $name       = $File->getClientOriginalName();
            $extension  = $File->getClientOriginalExtension();
            $MimeType   = $File->getMimeType();
            $name       = md5($name.$UserInfo['id'].$this->time()).$name;

            if(in_array((string)$extension, array('csv','xls','xlsx')) && in_array((string)$MimeType,array('text/plain','application/vnd.ms-excel','application/vnd.ms-office','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))){
                
                $uploadPath = storage_path(). DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'excel' . DIRECTORY_SEPARATOR . 'oms';
                $File->move($uploadPath, $name);

                $LMongo = new LMongo;
                $id = (string)$LMongo::collection('log_update_excel_content')->insert(
                    array('link_tmp' => $uploadPath. DIRECTORY_SEPARATOR .$name, 'user_id' => (int)$UserInfo['id'],'action' => array('del' => 0, 'insert' => 0))
                );
                if(!empty($id)){
                    if($this->ReadExcel((string)$id)){
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



    function Readexcel($id){
        $CountryId      = Input::has('country_id')          ? (int)Input::get('country_id')             : 237;

        $LMongo     = new LMongo;
        $ListImport = $LMongo::collection('log_update_excel_content')->find($id);

        $Data = Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
            $reader->skip(5)->noHeading()->select()->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
            $DataInsert = array();

            $ModelCashIn = new CashInModel;
            $UserModel = new \User;

            foreach($Data AS $key => $value){
                
                if(!empty($value[1]) && !empty($value[4]) && !empty($value[5]) && (!empty($value[2]) || !empty($value[3]))){
                    $error              = "";
                    $email              = trim($value[1]);
                    $transaction_id     = $value[2];
                    $refer_code         = $value[3];
                    $amount             = $value[4];
                    $type               = trim(explode('-', $value[5])[0]);


                    $_user = $UserModel->where('email', $email)->get()->toArray();
                    if(empty($_user) || $_user == null){
                        $error = "EMAIL_NOT_FOUND";
                    }
                    if($ModelCashIn->where('transaction_id', $transaction_id)->first()){
                        $error = "DUPLICATE_TRANSACTION";   
                    };



                    $DataInsert[] = array(
                        'partner'           => $id,
                        'active'            => (empty($error)) ? 1 : 0 ,
                        'error'             => $error,
                        'email'             => $email,
                        'transaction_id'    => $transaction_id,
                        'refer_code'        => $refer_code,
                        'amount'            => $amount,
                        'type'              => $type,
                        'country_id'        => $CountryId,
                        'time'              => $this->time()
                    );
                }
            }

            if(!empty($DataInsert)){
                $Model  = $LMongo::collection('log_create_cashin_excel');
                $Insert = $Model->batchInsert($DataInsert);
                 
                if($Insert){
                    return true;
                }
            }
        }
        return false;
    }


    function getListexcel($id){
        $CountryId      = Input::has('country_id')          ? (int)Input::get('country_id')             : 237;

        $Model     = LMongo::collection('log_create_cashin_excel');
        $Model     = $Model->where('country_id',$CountryId);
        
        $contents = array(
            'error'     => false,
            'data'      => $Model->where('partner', $id)->get()->toArray(),
            'total'     => $Model->where('partner', $id)->where('active',0)->count(),
            'message'   => 'success'
        );
        return Response::json($contents);
    }





    // Excel export 

    public function getExportexcelcashin(){
        $email                  = Input::has('email')           ? Input::get('email')              : '';
        $transaction_id         = Input::has('transaction_id')  ? Input::get('transaction_id')     : '';
        $refer_code             = Input::has('refer_code')      ? Input::get('refer_code')         : '';
        $status                 = Input::has('status')          ? Input::get('status')             : '';
        $from_date              = Input::has('from_date')       ? Input::get('from_date')          : 0;
        $to_date                = Input::has('to_date')         ? Input::get('to_date')            : 0;
        /*$page                   = Input::has('page')            ? (int)Input::get('page')          : 1 ;
        $itemPage               = Input::has('item_page')       ? (int)Input::get('item_page')     : 20;
        $offset                 = ($page - 1) * $itemPage;*/

        $Model = new CashInModel;
        $UserModel = new \User;
        $Total = 0;

        $CashinData = $Model->with(['user' => function ($q){
                        $q->select(array('id','email'));
                      }])->orderBy('time_create', 'DESC');


        if(!empty($email)){
            $_u = $UserModel->where('email', $email)->first();
            if(isset($_u->id)){
                $CashinData = $CashinData->where('user_id', $_u->id);
            }else {
                $CashinData = $CashinData->where('user_id', 0);
            }
        }

        if(!empty($transaction_id)){
            $CashinData = $CashinData->where('transaction_id', $transaction_id );
        }

        if(!empty($refer_code)){
            $CashinData = $CashinData->where('refer_code', $refer_code);
        }
        if(!empty($status)){
            $CashinData = $CashinData->where('status', $status);
        }

        if($from_date > 0){
            $CashinData = $CashinData->where('time_success','>=',$from_date);
            $CashinData = $CashinData->where('time_create','>=',$from_date-(90*86400));
        }


        if($to_date > 0){
            $CashinData = $CashinData->where('time_success','<',$to_date);
        }

        $CashinData = $CashinData->get()->toArray();
        $Total = $Model->count();

        $contents = array(
            'error'         => false,
            'message'       => 'success',            
            'data'          => $CashinData
        );

        
        //xuat du lieu ra excel
        return Excel::create('Quan_ly_nap_tien', function ($excel) use($CashinData) {
            $excel->sheet('Giao dịch', function ($sheet) use($CashinData) {
                $sheet->mergeCells('C1:F1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','Quản lý nạp tiền'));
                // set width column
                $sheet->setWidth(array(
                    'A'     => 5,
                    'B'     => 20,
                    'C'     => 15,
                    'D'     => 15,
                    'E'     => 40,
                    'F'     => 30,
                    'G'     => 20,
                    'H'     => 30,
                    'I'     => 30
                ));
                // set content row
                $sheet->row(3, array(
                     'STT',
                     'Email',
                     'Mã giao dịch',
                     'Mã tham chiếu',
                     'Số tiền',
                     'Kiểu thanh toán',
                     'Trạng thái',
                     'Ngày tạo',
                     'Thời gian thành công'
                ));
                $sheet->row(3,function($row){
                    $row->setBackground('#B6B8BA');
                    $row->setBorder('solid','solid','solid','solid');
                    $row->setFontSize(12);
                });
                //
                $i = 1;
                foreach ($CashinData AS $value) {
                    $dataExport = array(
                        'STT' => $i++,
                        'Email' => $value['user']['email'],
                        'Mã giao dịch'  => $value['transaction_id'],
                        'Mã tham chiếu' => $value['refer_code'],
                        'Số tiền' => $value['amount'],
                        'Kiểu thanh toán' => ($value['type'] == 1) ? 'Ngân lượng' : 'Ngân hàng', 
                        'Trạng thái' => $this->renderStatus($value['status']),
                        'Ngày tạo' => date("d/M/y H:m",$value['time_create']),
                        'Thời gian thành công' => date("d/M/y H:m",$value['time_success'])
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }

    //huy van don
    public function postCancelcashin(){
        $Id          = Input::has('id')               ? (int)Input::get('id')               : 0;
        if($Id > 0){
            $ModelCashIn = new CashInModel;
            $Update = $ModelCashIn::where('id',$Id)->update(array('status' => 'CANCEL'));
            if($Update){
                return Response::json(array(
                    'error'         => false,
                    'message'       => ''
                ));
            }else{
                return Response::json(array(
                    'error'         => true,
                    'message'       => 'Do not update!'
                ));
            }
        }else{
            return Response::json(array(
                'error'         => true,
                'message'       => 'Do not data!'
            ));
        }
    }

    public function postCreatecashin(){
		$v = Validator::make(Input::all(), [
	        'email'    => 'required',
	        'amount'   => 'required',
	        'type'     => 'required',
            'transaction_id'    => 'required',
            'refer_code'        => 'required'
    	]);

	    if ($v->fails())
		{
			return Response::json(array(
				'error'         => true,
	            'message'       => $v->errors(),
	            'data'          => array()
			));
		}
    	$email      	= Input::has('email')            	? (string)Input::get('email')               : '';
        $amount       	= Input::has('amount')           	? Input::get('amount')                    	: 0;
        $type      		= Input::has('type')      			? trim(Input::get('type'))         			: '';
        $transaction_id = Input::has('transaction_id')      ? trim(Input::get('transaction_id'))        : '';
        $refer_code 	= Input::has('refer_code')         	? trim(Input::get('refer_code'))            : '';
        $status 		= Input::has('status')         		? trim(Input::get('status'))            	: 'PROCESSING';
        $CountryId      = Input::has('country_id')          ? (int)Input::get('country_id')             : 237;

        $ModelCashIn = new CashInModel;
        $UserModel = new \User;
        $UserModel = $UserModel->where('email', $email);

        $_user = $UserModel->get()->toArray();

        if(empty($_user) || $_user == null){
        	return Response::json(array(
				'error'         => true,
	            'message'       => "USER_NOT_FOUND",
	            'data'          => array()
			));
        }

        if($ModelCashIn->where('transaction_id', $transaction_id)->first()){
            return Response::json(array(
                'error'         => true,
                'message'       => "DUPLICATE_TRANSACTION",
                'data'          => array()
            ));
        };

        $ModelCashIn->country_id        = $CountryId;
        $ModelCashIn->user_id           = $_user[0]['id'];
        $ModelCashIn->amount            = $amount;
        $ModelCashIn->type              = $type;
    	$ModelCashIn->transaction_id    = $transaction_id;
        $ModelCashIn->refer_code        = $refer_code;
        
        
        $ModelCashIn->status        = $status;
        $ModelCashIn->time_create   = $this->time();
		$result = $ModelCashIn->save();

		if($result){
			return Response::json(array(
				'error'         => false,
	            'message'       => '',
	            'data'          => $result
			));
		}
		return Response::json(array(
			'error'         => true,
            'message'       => "user not found",
            'data'          => array()
		));
    }


    public function postUpdatecashinbyfield(){
        $v = Validator::make(Input::all(), [
            'id'    => 'required',
            'data'  => 'required',
            'field' => 'required',
        ]);

        if ($v->fails())
        {
            return Response::json(array(
                'error'         => true,
                'message'       => $v->errors(),
                'data'          => array()
            ));
        }

        $id             = Input::has('id')                  ? (int)Input::get('id')               : '';
        $data           = Input::has('data')                ? (string)Input::get('data')          : '';
        $field          = Input::has('field')               ? (string)Input::get('field')         : '';
        
        $ModelCashIn = new CashInModel;


        $item = $ModelCashIn::find($id);

        if(isset($item->id)){
            if($item->status !== 'SUCCESS'){
                $item->$field = $data;
                if($field == 'transaction_id'){
                    
                    if($ModelCashIn->where('transaction_id', $data)->first()){
                        return Response::json(array(
                            'error'         => true,
                            'message'       => 'DUPLICATE_TRANSACTION',
                            'data'          => array()
                        ));
                    };
                    $item->status = 'PROCESSING';

                    if($item->type == 2){
                        $item->refer_code = $item->transaction_id;
                    }

                }
                $item = $item->save();

                if($item){
                    return Response::json(array(
                        'error'         => false,
                        'message'       => "",
                        'data'          => $item
                    ));
                }else {
                    return Response::json(array(
                        'error'         => true,
                        'message'       => "save error",
                        'data'          => array()
                    ));
                }
            }else {
                return Response::json(array(
                    'error'         => true,
                    'message'       => "Giao dịch đã thành công , không thể sửa",
                    'data'          => array()
                ));
            }

        }

        return Response::json(array(
            'error'         => true,
            'message'       => "record not found !",
            'data'          => array()
        ));
    }
}
?>