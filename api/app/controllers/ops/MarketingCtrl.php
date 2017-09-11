<?php
namespace ops;
use DB;
use Input;
use Response;
use LMongo;
use Cache;
use CourierModel;
use User;
use Excel;
use ordermodel\OrdersModel;

class MarketingCtrl extends BaseCtrl
{
    public function __construct()
    {

    }
    //
    public function getUserorder($num){
    	$itemPage   = Input::has('limit')       ? Input::get('limit')                   : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')               : 1;
        $cmd        = Input::has('cmd')         ? Input::get('cmd')                 : '';

        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',$this->time())));
        $timeEnd = strtotime(date('Y-m-d 00:00:00',$this->time()));
        
    	if($num > 20){
    		$num = $num;
    	}else{
    		$num = 20;
    	}
    	$Data = OrdersModel::where('time_accept','>',$timeStart)->where('time_accept','<',$timeEnd)->groupBy('from_user_id')->having('count','>',$num)->get(array('from_user_id',DB::raw('count(*) as count')));
    	//$Data = OrdersModel::groupBy('from_user_id')->having('count','>',0)->get(array('from_user_id',DB::raw('count(*) as count')));
    	if(!empty($Data)){
    		$listUserId = $listCount = array();
    		foreach($Data AS $Val){
    			$listUserId[] = $Val['from_user_id'];
    			$listCount[$Val['from_user_id']] = $Val['count'];
    		}
    		if(!empty($listUserId)){
	    		$infoUser = User::whereIn('id',$listUserId)->get(array('id','fullname','email','phone'))->toArray();
	    		//xuat excel
	    		if($cmd == 'export'){
	    			return Excel::create('Danh_sach_khach_hang_'.date("d/m/y",$this->time()), function ($excel) use($infoUser) {
	                    $excel->sheet('Danh sách', function ($sheet) use($infoUser) {
	                        // set width column
	                        $sheet->setWidth(array(
	                            'A'     => 5,
	                            'B'     => 20,
	                            'C'     => 45,
	                            'D'     => 15,
	                        ));
	                        // set content row
	                        $sheet->row(1, array(
	                             'STT',
	                             'Họ và tên',
	                             'Email',
	                             'Số điện thoại'
	                        ));
	                        $sheet->row(1,function($row){
	                            $row->setBackground('#B6B8BA');
	                            $row->setBorder('solid','solid','solid','solid');
	                            $row->setFontSize(12);
	                        });
	                        //
	                        $i = 1;
	                        foreach ($Output AS $value) {
	                            $dataExport = array(
	                                'STT' => $i++,
	                                'Họ và tên' => $value['fullname'],
	                                'Email' => $value['email'],
	                                'Số điện thoại' => $value['phone']
	                            );
	                            $sheet->appendRow($dataExport);
	                        }
	                    });
	                })->export('xls');
	    		}
	    		$contents = array(
	                'error'     => false,
	                'message'   => 'Success!',
	                'data' 		=> $infoUser,
	                'counts'    => $listCount
	            );
	    	}else{
	    		$contents = array(
	                'error'     => false,
	                'message'   => 'Not user!',
	                'data' 		=> array()
	            );
	    	}
    	}else{
    		$contents = array(
                'error'     => false,
                'message'   => 'Not data!',
                'data' 		=> array()
            );
    	}

    	return Response::json($contents);
    }

    public function getExport(){
    	$num      = Input::has('num')         ? Input::get('num')               : 20;
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',$this->time())));
        $timeEnd = strtotime(date('Y-m-d 00:00:00',$this->time()));

    	$Data = OrdersModel::where('time_accept','>',$timeStart)->where('time_accept','<',$timeEnd)->groupBy('from_user_id')->having('count','>',$num)->get(array('from_user_id',DB::raw('count(*) as count')));
    	if(!empty($Data)){
    		$listUserId = array();
    		foreach($Data AS $Val){
    			$listUserId[] = $Val['from_user_id'];
    		}
    		if(!empty($listUserId)){
	    		$infoUser = User::whereIn('id',$listUserId)->get(array('fullname','email','phone'))->toArray();
	    		//xuat excel
	    		if(!empty($infoUser)){
	    			return Excel::create('Danh_sach_khach_hang_'.date("d/m/y",$this->time()), function ($excel) use($infoUser) {
	                    $excel->sheet('Danh sách', function ($sheet) use($infoUser) {
	                        // set width column
	                        $sheet->setWidth(array(
	                            'A'     => 5,
	                            'B'     => 20,
	                            'C'     => 45,
	                            'D'     => 15,
	                        ));
	                        // set content row
	                        $sheet->row(1, array(
	                             'STT',
	                             'Họ và tên',
	                             'Email',
	                             'Số điện thoại'
	                        ));
	                        $sheet->row(1,function($row){
	                            $row->setBackground('#B6B8BA');
	                            $row->setBorder('solid','solid','solid','solid');
	                            $row->setFontSize(12);
	                        });
	                        //
	                        $i = 1;
	                        foreach ($infoUser AS $value) {
	                            $dataExport = array(
	                                'STT' => $i++,
	                                'Họ và tên' => $value['fullname'],
	                                'Email' => $value['email'],
	                                'Số điện thoại' => $value['phone']
	                            );
	                            $sheet->appendRow($dataExport);
	                        }
	                    });
	                })->export('xls');
	    		}else{
	    			return false;
	    		}
	    	}else{
	    		$contents = array(
	                'error'     => false,
	                'message'   => 'Not user!',
	                'data' 		=> array()
	            );
	    	}
    	}else{
    		$contents = array(
                'error'     => false,
                'message'   => 'Not data!',
                'data' 		=> array()
            );
    	}

    	return Response::json($contents);
    }
}
?>