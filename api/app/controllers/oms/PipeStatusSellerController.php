<?php
namespace oms;
use DB;
use Input;
use Response;
use omsmodel\GroupUserModel;
use omsmodel\PrivilegeModel;
use omsmodel\PipeStatusSellerModel;
class PipeStatusSellerController extends \BaseController {
    private $domain = '*';
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
     
    public function __construct(){
        
    }

    public function getShow($id = 0, $json = true)
    {  
        $page       = Input::has('page')      ? (int)Input::get('page')      : 1;
        $itemPage   = Input::has('item_page') ? (int)Input::get('item_page') : 20;
        $offset     = ($page - 1) * $itemPage;
       
        $Model  = new PipeStatusSellerModel;
        if($id){
            $Data   = $Model->with(['group'])->where('id', $id)->first();
        }else {
            $Data   = $Model->with(['group'])->skip($offset)->take($itemPage)->orderBy('id','ASC')->get()->toArray();    
        }
        
        
        $statusCode = 200;
        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'total'         => $Model::count(),
            'item_page'     => $itemPage,
            'data'          => $Data
        );
        
        if($json){
            return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
        }else {
            return $Data;
        }
    }

    public function postRemove($id){
        if(is_numeric($id) && $id> 0){
            $Model  = new PipeStatusSellerModel;

            try {
                $removePipeStatus = $Model::destroy($id);
            } catch (Exception $e) {
                $contents = array(
                    'error'         => true,
                    'error_message' => 'Lỗi kết nối máy chủ !',
                );
                return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
            }
            
            
            $contents = array(
                'error'         => false,
                'error_message' => 'Xóa thành công'
            );
            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
            
        }else {
            $contents = array(
                'error'         => true,
                'error_message' => 'Mã không hợp lệ',
            );
            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }


    public function postSave($id = 0){
        $data           = Input::json()->all();
        $Status         = (isset($data['status']))               ? $data['status']              : "";
        $Priority       = (isset($data['priority']))             ? (int)$data['priority']       : "";
        $Name           = (isset($data['name']))                 ? $data['name']                : "";
        $GroupStatus    = (isset($data['group']))                ? (int)$data['group']          : "";

        try {
            
            if($id){
                $Model = new PipeStatusSellerModel;
                $Model = $Model::where('id', $id)->first();

                if(!$Model){
                    $contents = array(
                        'error'         => true,
                        'error_message' => 'Mã này không tồn tại !',
                    );
                    return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
                }

                if($Status){
                    $Model->status   = $Status;
                }
                if($Priority){
                    $Model->priority   = $Priority;
                }
                if($Name){
                    $Model->name   = $Name;
                }

                if($GroupStatus){
                    $Model->group   = $GroupStatus;
                }

                $Model->save();
                $pipeStatusID = $Model->id;

            }else {
                if(empty($Status) || empty($Priority) || empty($Name) || empty($GroupStatus)){
                    $contents = array(
                        'error'         => true,
                        'error_message' => 'Dữ liệu gửi lên không đúng, vui lòng thử lại',
                    );
                    return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));    
                }

                $Model = new PipeStatusSellerModel(array(
                    'status'        => $Status,
                    'priority'      => $Priority,
                    'name'          => $Name,
                    'group'  => $GroupStatus,
                ));
                $Model->save();
                $pipeStatusID = $Model->id;
            }
        } catch (Exception $e) {
            $contents = array(
                'error'         => true,
                'error_message' => 'Lỗi kết nối máy chủ !',
            );
            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }

        $contents = array(
            'error'         => false,
            'error_message' => 'Thành công',
            'data'          => $this->getShow($pipeStatusID, false)
        );
        return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
    }

    
}
?>