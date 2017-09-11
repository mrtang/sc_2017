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
use omsmodel\IndemnifyModel;

class ComplainCtrl extends BaseCtrl
{
    public function __construct()
    {

    }
    //get data import later
    public function getListindemnify($id){
        $Model     = new IndemnifyModel;
        
        $contents = array(
            'error'     => false,
            'data'      => $Model->where('partner', $id)->get()->toArray(),
            'message'   => 'success'
        );
        return Response::json($contents);
    }
    //Accept
    public function getAccept($id){
    	$Model     = new IndemnifyModel;
    	$update = $Model->where('partner',$id)->where('status',0)->update(array('status' => 1,'time_accept' => $this->time()));
    	if($update){
    		$ud = LMongo::collection('log_import_create_lading')->where('_id', new \MongoId($id))->update(array('status' => 1));
    		$contents = array(
	            'error'     => false,
	            'message'   => Lang::get('response.SUCCESS')
	        );
	        return Response::json($contents);
    	}else{
    		$contents = array(
	            'error'     => true,
	            'message'   => Lang::get('response.NOT_ACCEPT')
	        );
	        return Response::json($contents);
    	}
    }
    //Export
    public function getExportexcel($id){
    	$Data = IndemnifyModel::where('partner',$id)->where('status',1)->get(array('tracking_code','email','amount','description','note'))->toArray();
    	if(!empty($Data)){
    		return Excel::create('Danh_sach_boi_hoan_'.date("d/m/y",$this->time()), function ($excel) use($Data) {
	            $excel->sheet('Danh sách', function ($sheet) use($Data) {
	                // set width column
	                $sheet->setWidth(array(
	                    'A'     => 5,
	                    'B'     => 20,
	                    'C'     => 15,
	                    'D'     => 15,
	                    'E'     => 40,
	                ));
	                // set content row
	                $sheet->row(1, array(
	                     'STT',
	                     'Tài khoản nhận',
	                     'Mã giao dịch',
	                     'Số tiền',
	                     'Lý do'
	                ));
	                $sheet->row(1,function($row){
	                    $row->setBackground('#B6B8BA');
	                    $row->setBorder('solid','solid','solid','solid');
	                    $row->setFontSize(12);
	                });
	                //
	                $i = 1;
	                foreach ($Data AS $value) {
	                    $dataExport = array(
	                        'STT' => $i++,
	                        'Tài khoản nhận' => $value['email'],
	                        'Mã giao dịch'  => '',
	                        'Số tiền' => $value['amount'],
	                        'Lý do' => $value['description']
	                    );
	                    $sheet->appendRow($dataExport);
	                }
	            });
	        })->export('xls');
		}else{
			return false;
    	}
	}
}
?>