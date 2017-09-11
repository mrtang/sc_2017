<?php
namespace oms;
use DB;
use Input;
use Response;
use LMongo;
use ordermodel\OrdersModel;
use Excel;

class OverWeightExcelUpdateController extends \BaseController {

	public function __construct(){
        
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
    //
    function Readexcel($id){
    	$LMongo     = new LMongo;
        $ListImport = $LMongo::collection('log_update_excel_content')->find($id);

        $Data = Excel::selectSheetsByIndex(0)->load($ListImport['link_tmp'], function($reader) {
          	$reader->skip(3)->noHeading()->select()->get()->toArray();
        },'UTF-8')->get()->toArray();

        if($Data){
        	$DataInsert = array();

            $Orders = new OrdersModel;

        	foreach($Data AS $key => $value){
                
                

        		if(!empty($value[0]) && !empty($value[1]) && !empty($value[2])){
                    $_order = $Orders::where('tracking_code', trim($value[1]))->get(array('total_weight', 'id'))->toArray();
                    $_old_weight = 0;
                    $_order_id   = 0;

                    if(sizeof($_order)> 0){
                        $_old_weight =  $_order[0]['total_weight'];
                        $_order_id   =  $_order[0]['id'];

                    }
                    

        			$DataInsert[] = array(
        				'partner' 	            => $id,
        				'active'    	        => (sizeof($_order)> 0) ? 1 : 0,
                        'order_id'              => $_order_id,
        				'tracking_code'         => $value[1],
        				'current_weight'        => $value[2],
                        'old_weight'            => $_old_weight
        			);
        		}
        	}

        	if(!empty($DataInsert)){
        		$Model  = $LMongo::collection('log_update_overweight');
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
        $Model     = LMongo::collection('log_update_overweight');
        
        $contents = array(
            'error'     => false,
            'data'      => $Model->where('partner', $id)->get()->toArray(),
            'total'     => $Model->where('partner', $id)->where('active',0)->count(),
            'message'   => 'success'
        );
        return Response::json($contents);
    }
    //
    function getProcess($id){
    	$DataUpdate = LMongo::collection('log_update_pickup')->where('_id', new \MongoId($id))->where('active',0)->first();
    	if(!$DataUpdate){
            $contents = array(
                'error'     => true,
                'message'   => 'Not exists!!',
                'data'      => array(),
                'code'      => 2
            );
            return Response::json($contents);
        }
        //Build data
        $Params = array(
        	'TrackingCode'  => $DataUpdate['courier_tracking_code'],
        	'Status'		=> $DataUpdate['status'],
        	'Note'			=> $DataUpdate['content'],
        	'TrackingOrder' => $DataUpdate['tracking_code']
        );
        $update = api\ApiJourneyCtrl::postUpdateJourney($Params);
        if($update){
        	$contents = array(
                'error'     => false,
                'message'   => 'Success',
                'data'      => array(),
                'code'      => 1
            );
            return Response::json($contents);
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not update data!!!',
                'code'      => 2
            );
            return Response::json($contents);
        }
    }



}
?>