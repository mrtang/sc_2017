<?php
namespace oms;
use DB;
use Input;
use Response;
use LMongo;
use ordermodel\OrdersModel;
use CourierPickupModel;
use Excel;

class FeePickupController extends \BaseController {

	public function __construct(){
        
    }

    //
    public function getIndex(){
        $itemPage   = Input::has('limit')       ? Input::get('limit')                    : 20;
        $page       = Input::has('page')        ? (int)Input::get('page')                : 1;
        $courier    = Input::has('courier')        ? (int)Input::get('courier')                : 0;
        
        $Model = new CourierPickupModel;
        if($courier > 0){
            $Model = $Model->where('courier_id',$courier);
        }

        $offset = ($page - 1) * $itemPage;
        $total = $Model->count();
        $data = $Model->skip($offset)->take($itemPage)->get()->toArray();

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $data,
            'total'         => $total,
            'item_page'     => $itemPage
        );
        return Response::json($contents);
    }

    public function postUpload(){
        $UserInfo   = $this->UserInfo();
        
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
                $id = (string)$LMongo::collection('log_import_create_lading')->insert(
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
        		if(!empty($value[0]) && !empty($value[1]) && !empty($value[2])){
        			$ArrCourier = explode('-', $value[0]);
                    $ArrService = explode('-', $value[1]);
                    $ArrFrom = explode('-', $value[2]);
                    $ArrTo = explode('-', $value[3]);
        			$DataInsert[] = array(
        				'partner' 	=> $id,
        				'active'	=> 0,        				
                        'courier'   => (int)$ArrCourier[1],
                        'service'   => (int)$ArrService[1],
                        'from'   => (int)$ArrFrom[1],
                        'to'   => (int)$ArrTo[1],
                        'fee' => $value[4],
                        'time_create' => $this->time()
        			);
        		}
        	}
        	if(!empty($DataInsert)){
        		$Model  = $LMongo::collection('log_fee_pickup');
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
        $Model     = LMongo::collection('log_fee_pickup');
        
        $contents = array(
            'error'     => false,
            'data'      => $Model->where('partner', $id)->where('active',0)->get()->toArray(),
            'total'     => $Model->where('partner', $id)->where('active',0)->count(),
            'message'   => 'success'
        );
        
        return Response::json($contents);
    }
    //
    function getProcess($id){
        sleep(2);
    	$DataUpdate = LMongo::collection('log_fee_pickup')->where('partner', $id)->where('active',0)->first();
    	if(empty($DataUpdate)){
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
            'courier_id' => $DataUpdate['courier'],
            'service_id' => $DataUpdate['service'],
            'from_location' => $DataUpdate['from'],
            'to_location' => $DataUpdate['to'],
            'fee' => $DataUpdate['fee']
        );
        if(CourierPickupModel::insert($Insert)){
            $updateActive = LMongo::collection('log_fee_pickup')->where('_id', new \MongoId($DataUpdate['_id']))->update(array('active' => 1));
            return Response::json( array(
                'error'         => false, 
                'error_message' => 'Thành công',
                'data'          => null
                ) );
        }
        else{
            return Response::json( array(
                'error'         => 'db_fail', 
                'error_message' => 'Lỗi không insert được!',
                'data'          => null
                ) );
        }
    }
    //Edit
    function postEdit($Id){
        $Data                   = Input::json()->all();
        $Model = new CourierPickupModel;
        if(!empty($Data)){
            $update = $Model->where('id',(int)$Id)->update($Data);
            if($update){
                return Response::json( array(
                    'error'         => false, 
                    'error_message' => 'Cập nhật thành công!',
                    'data'          => null
                ));
            }else{
                return Response::json( array(
                    'error'         => true, 
                    'error_message' => 'Cập nhật không thành công!',
                    'data'          => null
                ));
            }
        }else{
            return Response::json( array(
                'error'         => true, 
                'error_message' => 'Không có dữ liệu!',
                'data'          => null
                ));
        }
    }
    //Delete
    public function getDestroy($Id){
        $Model      = new CourierPickupModel;
        $Model      = $Model::find($Id);
        $statusCode = 200;
        
        if($Model){
            $Delete = $Model->delete();
            if($Delete){
                $contents = array(
                    'error'     => false,
                    'message'   => 'success'
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'delete error'
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'not exits'
            );
        }
        
        return Response::json($contents,$statusCode);
    }

}
?>