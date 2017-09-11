<?php
namespace oms;
use DB;
use Input;
use Response;
use Cache;
use omsmodel\GroupUserModel;
use omsmodel\PrivilegeModel;
use omsmodel\GroupPrivilegeModel;

class GroupUserController extends \BaseController {
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
       
        $Model  = new GroupUserModel;
        if($id){
            $Data   = $Model->where('id', $id)->with(['group_privilege'])->first();
        }else {
            $Data   = $Model->with(['group_privilege'])->skip($offset)->take($itemPage)->orderBy('id','ASC')->get()->toArray();
        }
        

        if(is_array($Data)){
            foreach ($Data as $key => $value) {
                $_group_privilege = $value['group_privilege'];
                $group_privilege = [];
                foreach ($_group_privilege as $k => $v) {
                    $group_privilege[$v['privilege_id']] = $v;
                }
                $Data[$key]['group_privilege'] = $group_privilege;
            }
        }else {
            $_group_privilege = $Data['group_privilege'];
            $group_privilege = array();

            foreach ($_group_privilege as $k => $v) {
                
                $group_privilege[$v['privilege_id']] = $v;

            }

            unset($Data['group_privilege']);
            $Data['group_privilege'] = $group_privilege;
        }
        
/*        
        return Response::json($Data);
            die;*/
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

    public function postSave($id = 0){
        $data           = Input::json()->all();
        $GroupPrivilege = (isset($data['group_privilege']))    ? $data['group_privilege'] :  [];
        $GroupName      = (isset($data['name']))               ? $data['name']     : "";
        $GroupStatus    = (isset($data['active']))             ? $data['active']   : 1;
        
        if($id){
            $GroupModel = new GroupUserModel;
            $GroupModel = $GroupModel::where('id', $id)->first();
            if(!$GroupModel){
                $contents = array(
                    'error'         => true,
                    'error_message' => 'Nhóm này không tồn tại !',
                );
                return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
            }

            if($GroupName){
                $GroupModel->name   = $GroupName;
            }

            if(!empty($GroupStatus) || $GroupStatus == 0){
                $GroupModel->active = $GroupStatus;

            }
            
            $GroupModel->save();
            $groupId = $GroupModel->id;
            Cache::forget('oms_privilege');

        }else {
            if(empty($GroupName)){
                $contents = array(
                    'error'         => true,
                    'error_message' => 'Tên nhóm không được để rỗng !',
                );
                return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));    
            }

            $GroupModel = new GroupUserModel(array(
                'name'    => $GroupName,
                'active'  => $GroupStatus,
            ));
            $GroupModel->save();
            Cache::forget('oms_privilege');
            $groupId = $GroupModel->id;
        }


        if($groupId){
            if(!empty($GroupPrivilege)){
                $InsertGroupPrivilege = [];
                $SaveGroupPrivilege   = [];
                foreach ($GroupPrivilege as $key => $value) {
                    if(is_array($value)){
                        $_temp =  array(
                            'group_id'      => $groupId,
                            'privilege_id'  => $key,
                            'add'           => (isset($value['add'])) ? $value['add']   : 0,
                            'edit'          => (isset($value['edit'])) ? $value['edit'] : 0,
                            'del'           => (isset($value['del'])) ? $value['del']   : 0,
                            'view'          => (isset($value['view'])) ? $value['view'] : 0,
                            'export'        => (isset($value['export'])) ? $value['export'] : 0,
                            'active'        => 1
                        );


                        if(!empty($value['id'])){
                            $_temp['id'] = $value['id'];
                            $SaveGroupPrivilege[] = $_temp;
                        }else {

                            $InsertGroupPrivilege[] = $_temp;
                        }
                    }
                }
                $Privileges = new GroupPrivilegeModel;
                if(sizeof($InsertGroupPrivilege) > 0){
                    $res = $Privileges::insert($InsertGroupPrivilege);


                }

                if(sizeof($SaveGroupPrivilege) > 0){
                    foreach ($SaveGroupPrivilege as $key => $value) {
                        $data = $Privileges->where('id', $value['id'])->first();
                        if(($data['add'] == $value['add']) && ($data['edit'] == $value['edit']) && ($data['del'] == $value['del']) && ((int)$data['view'] == (int)$value['view']) && ((int)$data['export'] == (int)$value['export'])){
                        }else {
                            $data['add']        = $value['add'];
                            $data['edit']       = $value['edit'];
                            $data['del']        = $value['del'];
                            $data['view']       = (isset($value['view'])) ? $value['view'] : 0;
                            $data['export']     = (isset($value['export'])) ? $value['export'] : 0;
                            $data->save();
                        }
                    }
                }

                Cache::forget('oms_privilege');
                Cache::forget('oms_group_privilege_'.$groupId);
                Cache::forget('oms_user_privilege_'.$groupId);
                $contents = array(
                    'error'         => false,
                    'error_message' => 'Thành công',
                    'data'          => !$id ? $this->getShow($groupId, false) : []
                );

                return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
            }else {
                $contents = array(
                    'error'         => false,
                    'error_message' => 'Thành công',
                    'data'          => !$id ? $this->getShow($groupId, false) : []
                );
                return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
            }
        }else {
            $contents = array(
                'error'         => true,
                'error_message' => 'Lỗi kết nối, vui lòng thử lại sau !',
            );
            return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
        }
    }

    public function postRemove($id){
        if(is_numeric($id) && $id> 0){
            $GroupUser  = new GroupUserModel;
            $GroupPrivileges = new GroupPrivilegeModel;

            try {
                $removeGroup = $GroupUser::destroy($id);
                $remote_gp = $GroupPrivileges->where('group_id', $id)->delete();
            } catch (Exception $e) {
                $contents = array(
                    'error'         => true,
                    'error_message' => 'Lỗi kết nối máy chủ !',
                );
                return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
            }
            
            if($remote_gp){
                Cache::forget('oms_group_privilege_'.$id);
                Cache::forget('oms_user_privilege_'.$id);
                $contents = array(
                    'error'         => false,
                    'error_message' => 'Xóa thành công'
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

}
?>