<?php
namespace oms;
use DB;
use Input;
use Response;
use LMongo;
use ordermodel\OrdersModel;
use Excel;

class ExcelUpdateController extends \BaseController {

    /**
     * get list import excel
     */
    public function getListimport(){
        $page       = Input::has('page')        ? (int)Input::get('page')           : 1;
        $itemPage   = Input::has('limit')       ? Input::get('limit')               : 20;
        $TimeStart  = Input::has('time_start')  ? (int)Input::get('time_start')     : 0;
        $TimeEnd    = Input::has('time_end')    ? (int)Input::get('time_end')       : 0;

        $offset     = ($page - 1)*$itemPage;

        $LMongo     = \LMongo::collection('log_import_create_lading')->whereExists('courier');

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
                foreach($Data as $key => $val){
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

    public function postUpload($Courier){
        $UserInfo   = $this->UserInfo();
        if($Courier == 0){
            $contents = array(
                'error'     => true,
                'message'   => 'upload fail'
            );
        }
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
                $id = (string)$LMongo::collection('log_import_create_lading')->insert([
                    'link_tmp'      => $uploadPath. DIRECTORY_SEPARATOR .$name,
                    'link_download' => $this->link_upload.'/excel/oms/'.$name,
                    'user_id'       => (int)$UserInfo['id'],
                    'action'        => array('del' => 0, 'insert' => 0),
                    'courier'       => $Courier,
                    'name'          => $File->getClientOriginalName(),
                    'time_create'   => $this->time()
                ]);

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
    //
    function Readexcel($id){
    	$LMongo     = new LMongo;
        $ListImport = $LMongo::collection('log_import_create_lading')->find($id);
        $Data       = Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
          	$reader->skip(3)->select(
          	array(2,3,4,5,6)
          	)->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
        	$DataInsert = array();
        	foreach($Data AS $key => $value){
        		if(!empty($value[0]) && !empty($value[2])){
        			$ArrStatus = explode('-', $value[2]);
        			$DataInsert[] = array(
        				'partner' 	    => $id,
        				'active'	    => 0,
        				'tracking_code' => $value[0],
        				'courier_tracking_code' => $value[1],
        				'status'	    => (int)$ArrStatus[1],
        				'content'	    => $value[3],
                        'city'          => $value[4],
                        'courier'       => $ListImport['courier'],
                        'user_id'       => $ListImport['user_id'],
                        'time_create'   => $this->time()
        			);
        		}
        	}
        	if(!empty($DataInsert)){
        		$Model  = $LMongo::collection('log_update_pickup');
                $Insert = $Model->batchInsert($DataInsert);
                 
                if($Insert){
                	return true;
                }
        	}
        }
        return false;
    }
    //get data import later
    function getListexcel($id){
        $show       = Input::has('show')    ? strtolower(trim(Input::get('show')))   : '';
        $Model      = LMongo::collection('log_update_pickup');

        if($show != 'all'){
            $Model = $Model->where('active',0);
        }
        
        $contents = array(
            'error'     => false,
            'data'      => $Model->where('partner', $id)->get()->toArray(),
            'total'     => $Model->where('partner', $id)->count(),
            'message'   => 'success'
        );
        
        return Response::json($contents);
    }
    //
    function getProcess($id){
        sleep(2);
    	$DataUpdate = LMongo::collection('log_update_pickup')->where('partner', $id)->where('active',0)->first();
    	if(empty($DataUpdate)){
            \LMongo::collection('log_import_create_lading')->where('_id', new \MongoId($id))->update(array('action.insert' => 1));

            $contents = array(
                'error'     => true,
                'message'   => 'Not exists!!',
                'data'      => array(),
                'code'      => 2
            );
            return Response::json($contents);
        }
        //Build data
        $Insert = array(
            "tracking_code"     => $DataUpdate['tracking_code'],
            'tracking_number'   => (int)substr($DataUpdate['tracking_code'],2),
            "input" => Array (
                "username" => $DataUpdate['courier'],
                "function" => "LichTrinh",
                "params" => Array (
                    "SC_CODE"   => $DataUpdate['tracking_code'],
                    "STATUS"    => $DataUpdate['status'],
                    "CITY"      => $DataUpdate['city'],
                    "NOTE"      => $DataUpdate['content'],
                ),
                "TrackingOrder" =>  isset($DataUpdate['courier_tracking_code']) ? $DataUpdate['courier_tracking_code'] : $DataUpdate['tracking_code'],
                "TrackingCode"  =>  $DataUpdate['tracking_code'],
                "Status"        => $DataUpdate['status'],
                "Note"          => $DataUpdate['content'],
                "City"          => $DataUpdate['city']
            ),
            'UserId'        => $DataUpdate['user_id'],
            "accept"        => 0,
            "priority"      => 1,
            "time_create"   => $this->time(),
            "time_update"   => $this->time()
        );
        if(LMongo::collection('log_journey_lading')->insert($Insert)){
            $updateActive = LMongo::collection('log_update_pickup')->where('_id', new \MongoId($DataUpdate['_id']))->update(array('active' => 1));
            return Response::json( array(
                'error'         => false, 
                'error_message' => 'Thành công',
                'data'          => null
                ) );
        }
        else{
            return Response::json( array(
                'error'         => 'db_fail', 
                'error_message' => 'Lỗi ghi log lịch trình',
                'data'          => null
                ) );
        }
    }

}
?>