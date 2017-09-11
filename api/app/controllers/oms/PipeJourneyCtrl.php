<?php
namespace oms;
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
        $Model = $Model->where('type', $type)->orderBy('id', 'ASC')->get()->toArray();

        for ($i=0; $i < sizeof($Model) ; $i++) { 
            if($Model[$i]['code'] == $currentPipe['group_status']){

                $nextElement = $i + 1;
                if(!empty($Model[$nextElement])){
                    return $Model[$nextElement];
                }else {
                    return false;
                }
            }
        }
    }
    
    public function postCreate(){
        $UserInfo = $this->UserInfo();
        Input::merge(Input::json()->all());
        
        $validation = Validator::make(Input::all(), array(
            'tracking_code' => 'required',
            'pipe_status'   => 'required|required|numeric|min:1'
        ));

        //error
        if ($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $data               = Input::all();
        $PipeStatus         = (isset($data['pipe_status']))     ? (int)$data['pipe_status']         : 0;
        $Group              = (isset($data['group']))           ? (int)$data['group']               : 0;
        $TrackingCode       = (isset($data['tracking_code']))   ? $data['tracking_code']            : "";
        $Note               = (isset($data['note']))            ? trim($data['note'])               : "";
        $Note               = mb_substr($Note,0,150,'utf8');
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
            $DataUpdate = ['time_update' => $this->time()];
            $isEnd = $this->getIsEndStep($PipeStatus, $Group);
            if($isEnd){
                $nextGroup = $this->getNextGroup($Type, $isEnd);
                if($nextGroup){
                    $DataUpdate['pipe_status']  = $nextGroup['code'];
                }
            }

            try {
                UserInfoModel::where('user_id', $TrackingCode)->update($DataUpdate);
            } catch (Exception $e) {
                return Response::json(['error'=> true, 'message' => 'UPDATE_ERROR','error_message'=> 'Khách hàng không tồn tại!']);
            }
        }elseif($Type == 1){
            if(preg_match("/sc/i", $TrackingCode)){
                $Order = OrdersModel::where('tracking_code', $TrackingCode)->where('time_accept','>=',$this->time() - 86400*120)->first(['id']);
                if(empty($Order)){
                    return Response::json(['error'=> true, 'message' => 'UPDATE_ERROR', 'error_message'=> 'Đơn hàng không tồn tại!']);
                }
                $TrackingCode = $Order->id;
            }

            if(($Group == 29 && $PipeStatus == 707) || ($Group == 31 && $PipeStatus == 903)){
                $Jouney = PipeJourneyModel::where('tracking_code', $TrackingCode)->whereIn('pipe_status', [707, 903])->first();
                if(isset($Jouney->id)){
                    return Response::json([
                        'error'         => true,
                        'message'       => 'ERROR',
                        'error_message' => 'Bạn đã gửi yêu cầu phát lại cho đơn hàng này , không thể gủi thêm.'
                    ]);
                }
            }
        }



        try{
            $Id = $Model->insertGetId ([
                'user_id'           => (int)$UserInfo['id'],
                'tracking_code'     => $TrackingCode,
                'type'              => $Type,
                'group_process'     => $Group,
                'pipe_status'       => $PipeStatus,
                'note'              => $Note,
                'time_create'       => $this->time()
            ]);

            if($Type == 1){
                if($Group == 29 && $PipeStatus == 707){
                    // phát thất bại ycpl
                    \ordermodel\OrderProblemModel::where('order_id',$TrackingCode)->where('type',1)
                                                 ->where('status',0)->where('active',1)
                                                 ->update(['action' => 1,'status' => 1,'time_update' => time()]);
                }

                if($Group == 31 && $PipeStatus == 903){
                    // phát thất bại ycpl
                    \ordermodel\OrderProblemModel::where('order_id',$TrackingCode)->where('type',2)
                        ->where('status',0)->where('active',1)
                        ->update(['action' => 1,'status' => 1,'time_update' => time()]);
                }


                if((($Group == 29 && $PipeStatus == 711) || ($Group == 31 && $PipeStatus == 908))){
                    // gửi yêu cầu phát lại cho hvc khi vận hành xác minh
                    $this->PredisReportReplay($Id);
                }
            }
        }catch (Exception $e){
            return Response::json([
                'error'         => true,
                'message'       => 'INSERT_ERROR',
                'error_message' => $e->getMessage()
            ]);
        }


        $QueueId = Input::get('queue_id');
        if (!empty($QueueId) && (int)$QueueId > 0) {
            try {
                \QueueModel::where('id', (int)$QueueId)->update(['view'=> 1]);
            } catch (Exception $e) {
                
            }
        }

            
        return Response::json([
            'error'             => false,
            'message'           => 'SUCCESS',
            'error_message'     => 'Thành công'
        ]);
    }
}
?>