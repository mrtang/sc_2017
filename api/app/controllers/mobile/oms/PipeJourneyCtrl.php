<?php
namespace mobile_oms;
use DB;
use Input;
use Response;
use Validator;
use Exception;
use omsmodel\PipeJourneyModel;
use omsmodel\PipeStatusModel;
use omsmodel\GroupProcessModel;
use sellermodel\UserInfoModel;
use ordermodel\OrdersModel;

class PipeJourneyCtrl extends \BaseController {
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */

    public function getIsEndStep($pipe_status, $group){
        $Model = new PipeStatusModel;

        $currentPipe = $Model->where('type',2)->where('group_status', $group)->where('status', $pipe_status)->first();
        $Model = $Model->where('type',2)->where('group_status', $group)->where('active',1)->orderBy('priority', 'DESC')->first();
        if($currentPipe->status == $Model->status){
            return $currentPipe;
        }
        return false;
    }
    public function getNextGroup($type, $currentPipe){
        $Model = new GroupProcessModel;
        $Model = $Model->where('type', $type)->orderBy('id', 'ASC')->get();
        for ($i=0; $i < sizeof($Model) ; $i++) { 
            if($Model[$i]['code'] == $currentPipe['group_status']){
                if(!empty($Model[$i++])){
                    return $Model[$i++];
                }else {
                    return false;
                }
            }
        }
        return false;
    }
    
    public function postCreate(){
        $UserInfo = $this->UserInfo();
        $validation = Validator::make(Input::json()->all(), array(
            'tracking_code' => 'required|numeric|min:1',
            'pipe_status'   => 'required|required|numeric|min:1'
        ));

        //error
        if ($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $data               = Input::json()->all();
        $PipeStatus         = (isset($data['pipe_status']))     ? (int)$data['pipe_status']         : 0;
        $Group              = (isset($data['group']))           ? (int)$data['group']               : 0;
        $TrackingCode       = (isset($data['tracking_code']))   ? $data['tracking_code']            : "";
        $Note               = (isset($data['note']))            ? trim($data['note'])               : "";
        $Type               = (isset($data['type']))            ? (int)$data['type']                : 1;
        $Model              = new PipeJourneyModel;

        if(empty($PipeStatus) || empty($TrackingCode) || empty($Group)){
            return Response::json([
                'error'         => true,
                'message'       => 'DATA_EMPTY',
                'error_message' => 'Nhập thiếu dữ liệu !'
            ]);
        }

        if($Type == 2){// User Info
            $DataUpdate = ['time_update' => time()];
            $isEnd = $this->getIsEndStep($PipeStatus, $Group);
            if($isEnd){
                $nextGroup = $this->getNextGroup($Type, $isEnd);
                if($nextGroup){
                    $DataUpdate['pipe_status']  = $nextGroup->code;
                }
            }

            try {
                UserInfoModel::where('user_id', $TrackingCode)->update($DataUpdate);
            } catch (Exception $e) {
                return Response::json(['error'=> true, 'message' => 'UPDATE_ERROR','error_message'=> 'Khách hàng không tồn tại!']);
            }
        }elseif($Type == 1){
            if(($Group == 29 && $PipeStatus == 707) || ($Group == 31 && $PipeStatus == 903)){
                $Jouney = new PipeJourneyModel;
                $Jouney = $Jouney->where('tracking_code', $TrackingCode)->whereIn('pipe_status', [707, 903])->first();
                if($Jouney){
                    return Response::json([
                        'error'         => true,
                        'message'       => 'ERROR',
                        'error_message' => 'Bạn đã gửi yêu cầu phát lại cho đơn hàng này , không thể gủi thêm.'
                    ]);
                }
            }

            $OrdersModel    = new OrdersModel;
            try{
                $OrdersModel::where('id', $TrackingCode)->where('time_accept','>=',time() - 86400*120)->update(['time_update' => time()]);
            }catch (Exception $e){
                return Response::json([
                    'error'             => true,
                    'message'           => 'UPDATE_ORDER_ERROR',
                    'error_message'     => 'Cập nhật Order thất bại!'
                ]);
            }
        }



        try{
            $Model->insert([
                'user_id'           => (int)$UserInfo['id'],
                'tracking_code'     => $TrackingCode,
                'type'              => $Type,
                'group_process'     => $Group,
                'pipe_status'       => $PipeStatus,
                'note'              => $Note,
                'time_create'       => time()
            ]);
        }catch (Exception $e){
            return Response::json([
                'error'         => true,
                'message'       => 'INSERT_ERROR',
                'error_message' => $e->getMessage()
            ]);
        }

        return Response::json([
            'error'             => false,
            'message'           => 'SUCCESS',
            'error_message'     => 'Thành công'
        ]);
    }
}
?>