<?php
namespace oms;
use DB;
use Input;
use Response;
use omsmodel\GroupUserModel;
use omsmodel\PrivilegeModel;
use omsmodel\PipeStatusModel;
use omsmodel\GroupProcessModel;
use metadatamodel\GroupStatusModel;
use Cache;
use Lang;

class PipeStatusController extends \BaseController {

    public function getShow($id = 0, $json = true)
    {  
        $type       = Input::has('type')      ? (int)Input::get('type')      : "";
        $page       = Input::has('page')      ? (int)Input::get('page')      : 1;
        $itemPage   = Input::has('item_page') ? (int)Input::get('item_page') : 20;
        $offset     = ($page - 1) * $itemPage;
       
        $Model  = new PipeStatusModel;

        if($id){
            $Data   = $Model->where('id', $id);
            if($type == 1){
                $Data = $Data->with(['group_status']);
            }elseif($type == 2){
                $Data = $Data->with(['group_status_seller']);
            }
            $Data   = $Data->first();
            if(isset($Data->group_status_seller)){
                $Data->group_status = $Data->group_status_seller;
                unset($Data->group_status_seller);
            }
        }else {
            $Data   = $Model->skip($offset)->take($itemPage)->orderBy('id','ASC');

            if($type == 1){
                $Data = $Data->with(['group_status']);
            }elseif($type == 2){
                $Data = $Data->with(['group_status_seller']);
            }

            if(!empty($type)){
                $Data->where('type', $type);
            }
            $Data   = $Data->get()->toArray();    
            foreach ($Data as $key => $value) {
                if (!empty($value['group_status_seller'])) {
                    $Data[$key]['group_status'] = $value['group_status_seller'];
                    unset($Data[$key]['group_status_seller']);
                }
            }
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

    public function postSave($id = 0){
        $data           = Input::json()->all();
        $Type           = (isset($data['type']))                 ? $data['type']                : "";
        $Status         = (isset($data['status']))               ? $data['status']              : "";
        $Priority       = (isset($data['priority']))             ? (int)$data['priority']       : "";
        $Name           = (isset($data['name']))                 ? $data['name']                : "";
        $GroupStatus    = (isset($data['group_status']))         ? (int)$data['group_status']   : "";
        $Estimate       = (isset($data['estimate_time']))        ? (int)$data['estimate_time']  : "";
        $Active         = (isset($data['active']))               ? (int)$data['active']         : null;

        $Model          = new PipeStatusModel;

        if(!empty($id)){
            $Model = $Model::where('id', $id)->first();
            if(!isset($Model->id)){
                $contents = array(
                    'error'         => true,
                    'error_message' => Lang::get('response.CODE_NOT_EXISTS'),
                );
                return Response::json($contents);
            }

            if(!empty($Status) || !empty($Type) || !empty($GroupStatus)){
                if(empty($Status))          $Status     = $Model->status;
                if(empty($Type))            $Type       = $Model->type;
                if(empty($GroupStatus))    $GroupStatus = $Model->group_status;
            }
        }else{
            if(empty($Status) || empty($Priority) || empty($Name) || empty($GroupStatus) || empty($Type)){
                $contents = array(
                    'error'         => true,
                    'error_message' => Lang::get('response.DATA_PUSH_FAIL'),
                );
                return Response::json($contents);
            }
        }

        if(!empty($Status) && !empty($GroupStatus) && !empty($Type) && PipeStatusModel::where('status', $Status)->where('type',$Type)->where('group_status',$GroupStatus)->count() > 0){
            $contents = array(
                'error'         => true,
                'error_message' => Lang::get('response.GROUP_STATUS_EXISTS'),
            );
            return Response::json($contents);
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
            $Model->group_status   = $GroupStatus;
        }
        if($Type){
            $Model->type   = $Type;
        }
        if($Estimate){
            $Model->estimate_time   = $Estimate;
        }

        if(isset($Active)) {
            $Model->active  = $Active;
        }


        try {
            $Model->save();
        } catch (Exception $e) {
            $contents = array(
                'error'         => true,
                'error_message' => Lang::get('response.server_error')
            );
            return Response::json($contents);
        }

        $contents = array(
            'error'         => false,
            'error_message' => Lang::get('response.SUCCESS'),
            'data'          => $Model->id
        );
        return Response::json($contents);
    }

    public function getPipebygroup($json = true){
        $group          = Input::has('group')       ? (int)Input::get('group')      : 0;
        $type           = Input::has('type')        ? (int)Input::get('type')       : 0;
        $active         = Input::has('active')      ? (int)Input::get('active')     : null;

        $Model       = new PipeStatusModel;

       if(!empty($group)){
           $Model = $Model->where('group_status', $group);
       }

       if(!empty($type)){
           $Model = $Model->where('type', $type);
       }

        if(isset($active)){
            $Model = $Model->where('active', $active);
        }

        return $json ? Response::json([
            'error'     => false,
            'message'   => 'success',
            'data'      => $Model->orderBy('priority', 'ASC')->get()->toArray()
        ]) : $Model->orderBy('priority', 'ASC')->get()->toArray();
    }

    public function getGroupProcess(){
        $code           = Input::has('code')        ? trim(Input::get('code'))      : '';
        $type           = Input::has('type')        ? (int)Input::get('type')       : 0;

        $Model       = new GroupProcessModel;

        if(!empty($code)){
            $Model = $Model->where('code', $code);
        }

        if(!empty($type)){
            $Model = $Model->where('type', $type);
        }

        return Response::json([
            'error'     => false,
            'message'   => 'success',
            'data'      => $Model->orderBy('id', 'ASC')->get()->toArray()
        ]);
    }

    public function postSaveProcess($id = 0){
        $data           = Input::json()->all();
        $Code           = (isset($data['code']))                 ? (int)$data['code']           : "";
        $Name           = (isset($data['name']))                 ? $data['name']                : "";
        $Type           = (isset($data['type']))                 ? (int)$data['type']           : 0;
        $Active         = (isset($data['active']))               ? (int)$data['active']         : null;

        $Model          = new GroupProcessModel;

        if(!empty($id)){
            $Model = $Model::where('id', $id)->first();
            if(!isset($Model->id)){
                $contents = array(
                    'error'         => true,
                    'error_message' => Lang::get('response.CODE_NOT_EXISTS'),
                );
                return Response::json($contents);
            }

            if(!empty($Type) || !empty($Code)){
                if(empty($Type))            $Type           = $Model->type;
                if(empty($Code))            $Code           = $Model->code;
            }
        }else{
            if(empty($Code) || empty($Name) || empty($Type)){
                $contents = array(
                    'error'         => true,
                    'error_message' => Lang::get('response.DATA_PUSH_FAIL'),
                );
                return Response::json($contents);
            }
        }

        if(!empty($Type) && !empty($Code) && GroupProcessModel::where('type', $Type)->where('code',$Code)->count() > 0){
            $contents = array(
                'error'         => true,
                'error_message' => Lang::get('response.GROUP_STATUS_EXISTS'),
            );
            return Response::json($contents);
        }

        if($Name){
            $Model->name   = $Name;
        }

        if($Code){
            $Model->code   = $Code;
        }
        if($Type){
            $Model->type   = $Type;
        }
        if(isset($Active)) {
            $Model->active  = $Active;
        }


        try {
            $Model->save();
        } catch (Exception $e) {
            $contents = array(
                'error'         => true,
                'error_message' => Lang::get('response.server_error')
            );
            return Response::json($contents);
        }

        $contents = array(
            'error'         => false,
            'error_message' => Lang::get('response.SUCCESS'),
            'data'          => $Model->id
        );
        return Response::json($contents);
    }
    
}
?>