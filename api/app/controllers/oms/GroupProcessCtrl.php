<?php
namespace oms;
use DB;
use Input;
use Response;
use omsmodel\GroupProcessSellerModel;
use omsmodel\PipeStatusSellerModel;
use omsmodel\GroupProcessModel;

class GroupProcessSellerController extends \BaseController {

    public function getShow($id = 0, $json = true)
    {  
        $page       = Input::has('page')        ? (int)Input::get('page')      : 1;
        $itemPage   = Input::has('item_page')   ? (int)Input::get('item_page') : 20;
        $type       = Input::has('type')        ? (int)Input::get('type')      : 2;
        $offset     = ($page - 1) * $itemPage;
       
        $Model      = new GroupProcessModel;

        if($id){
            $Data   = $Model->with(['pipe_status'])->where('id', $id)->first();
        }else {
            $Data   = $Model->with(['pipe_status' => function ($query) use ($type){
                $query->where('type', $type);
            }])->where('type', $type)->skip($offset)->take($itemPage)->orderBy('id','ASC')->get()->toArray();    
        }

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'total'         => $Model::count(),
            'item_page'     => $itemPage,
            'data'          => $Data
        );

        return $json ? Response::json($contents) : $Data;
    }

    public function postRemove($id){
        if(is_numeric($id) && $id> 0){
            $Model  = new GroupProcessModel;

            try {
                $removePipeStatus = $Model::destroy($id);
            } catch (Exception $e) {
                $contents = array(
                    'error'         => true,
                    'error_message' => 'Lỗi kết nối máy chủ !',
                );
                return Response::json($contents);
            }
            
            
            $contents = array(
                'error'         => false,
                'error_message' => 'Xóa thành công'
            );
            return Response::json($contents);
            
        }else {
            $contents = array(
                'error'         => true,
                'error_message' => 'Mã không hợp lệ',
            );
            return Response::json($contents);
        }
    }

    public function postSave($id = ""){
        $params        = Input::json()->all();
        $name          = Input::has('name')       ? $params['name']      : '';
        $code          = Input::has('code')       ? $params['code']      : '';
        $type          = Input::has('type')       ? $params['type']      : 1;

        $Model = new GroupProcessModel;

        if(empty($id)){
            // Check params
            if(empty($name) || empty($code) || empty($type)){
                $contents = array(
                    'error'         => true,
                    'error_message' => 'Dữ liệu gửi lên không đúng, vui lòng thử lại',
                );
                return Response::json($contents);
            }

        }else {
            $Model = $Model->where('id', $id)->first();
            if(!isset($Model->id)){
                $contents = array(
                    'error'         => true,
                    'error_message' => 'Nhóm không tôn tại',
                );
                return Response::json($contents);
            }
        }

        if($code){
            $hasCode = (new GroupProcessModel)->where('code', $code)->first();
            if($hasCode){
                $contents = array(
                    'error'         => true,
                    'error_message' => 'Mã code đã tồn tại !',
                );
                return Response::json($contents);   
            }
        }

        if($name){
            $Model->name = $name;
        }
        if($type){
            $Model->type = $type;
        }
        if($code){
            $Model->code = $code;
        }
       
        try {
            $Model->save();
        } catch (Exception $e) {
            $contents = array(
                'error'         => true,
                'error_message' => 'Lỗi truy vấn vui lòng thử lại sau !',
            );
            return Response::json($contents);
        }
       

        $contents = array(
            'error'         => false,
            'error_message' => 'Tạo thành công',
            'data'          => $this->getShow($Model->id, false)
        );
        return Response::json($contents);
    }

}
?>