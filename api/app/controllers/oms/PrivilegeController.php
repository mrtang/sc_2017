<?php
namespace oms;
use DB;
use Input;
use Response;
use Cache;
use omsmodel\GroupUserModel;
use omsmodel\PrivilegeModel;

class PrivilegeController extends \BaseController {
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
        $offset     = ($page - 1)*$itemPage;
       
        $Model  = new PrivilegeModel;
        if($id){
            $Data   = $Model->where('id', $id)->first();
        }else {
            $Data   = $Model->skip($offset)->take($itemPage)->orderBy('id','ASC')->get()->toArray();    
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
            $Model  = new PrivilegeModel;
            try {
                $removeGroup = $Model::destroy($id);
            } catch (Exception $e) {
                $contents = array(
                    'error'         => true,
                    'error_message' => 'Lỗi kết nối máy chủ !',
                );
                return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
            }
            
            if($removeGroup){
                Cache::forget('oms_privilege');
                $contents = array(
                    'error'         => false,
                    'error_message' => 'Xóa thành công'
                );
                return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
            }else {
                $contents = array(
                    'error'         => true,
                    'error_message' => 'Xóa thất bại'
                );
                return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
            }
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
        $Description    = (isset($data['description']))               ? $data['description']     : "";
        $Code           = (isset($data['code']))                    ? $data['code']          : "";
        try {
            
        
            if($id){
                $Model = new PrivilegeModel;
                $Model = $Model::where('id', $id)->first();
                if(!$Model){
                    $contents = array(
                        'error'         => true,
                        'error_message' => 'Mã này không tồn tại !',
                    );
                    return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
                }

                if($Description){
                    $Model->description   = $Description;
                }

                if($Code){
                    $Model->code   = $Code;
                }
                $Model->save();
                $privilegeId = $Model->id;

            }else {
                if(empty($Description) || empty($Code)){
                    $contents = array(
                        'error'         => true,
                        'error_message' => 'Dữ liệu gửi lên không đúng, vui lòng thử lại',
                    );
                    return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));    
                }

                $Model = new PrivilegeModel(array(
                    'description'    => $Description,
                    'code'           => $Code,
                ));
                $Model->save();
                $privilegeId = $Model->id;
            }
        } catch (Exception $e) {
            $contents = array(
                'error'         => true,
                'error_message' => 'Lỗi kết nối máy chủ !',
            );
            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }

        Cache::forget('oms_privilege');

        $contents = array(
            'error'         => false,
            'error_message' => 'Thành công',
            'data'          => !$id ? $this->getShow($privilegeId, false) : []
        );
        return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
    }

    
}
?>