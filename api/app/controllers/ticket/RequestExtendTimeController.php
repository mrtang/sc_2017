<?php namespace ticket;

use sellermodel\UserInfoModel;
use Validator;
use Response;
use Input;
use LMongo;
use Exception;
use ticketmodel\RequestModel;
use ticketmodel\AssignModel;
use ticketmodel\CaseTypeModel;
use ticketmodel\LogViewModel;
use ticketmodel\ReferModel;
use ticketmodel\CaseTicketModel;
use ordermodel\OrdersModel;
use ordermodel\AddressModel;
use ticketmodel\AssignGroupModel;
use ticketmodel\RequestExtendTimeModel;
use User;
use DB;
use Cache;
use Excel;
use ticketmodel\ReplyTemplateModel;

class RequestExtendTimeController extends \BaseController {

    public function postCreateRequest(){
        $UserInfo = $this->UserInfo();
        $validation = Validator::make(Input::json()->all(), array(
            'ticket_id' => 'required',
            'time'      => 'required',
            'note'      => 'required'
        ));

        if ($validation->fails()) {
            return Response::json(array('error' => true, 'error_message' => $validation->messages()));
        }

        if($UserInfo['privilege'] == 0){
            $contents = array(
                'error'         => true,
                'message'       => 'Bạn không có quyền!',
                'data'          => ''
            );
            return Response::json($contents);
        }


        
        $TicketId = Input::json()->get('ticket_id');
        $Time     = Input::json()->get('time');
        $Note     = Input::json()->get('note');

        $Model              = new RequestExtendTimeModel;
        $Model->ticket_id   = $TicketId;
        $Model->user_id     = $UserInfo['id'];
        $Model->time        = $Time;
        $Model->note        = $Note;
        $Model->time_create = $this->time();
        try {
            $Model->save();
        } catch (Exception $e) {
            return Response::json(array(
                'error'         => true,
                'error_message' => 'Lỗi kết nối máy chủ, vui lòng thử lại sau !',
                'data'          => $e->getMessage()
            ));
        }

        return Response::json(array(
            'error'         => false,
            'error_message' => 'Thành công',
            'data'          => $Model
        ));
    }

    public function getShow($id = 0){
        $page      = Input::has('page')        ? (int)Input::get('page')                    : 1;
        $itemPage  = Input::has('limit')       ? (int)Input::get('limit')                   : 20;
        $Status    = Input::has('status')      ? (int)Input::get('status')                  : "";
        $Keyword   = Input::has('keyword')     ? (int)Input::get('keyword')                 : "";
        $TimeStart = Input::has('time_start')  ? (int)Input::get('time_start')              : "";
        $TimeEnd   = Input::has('time_end')    ? (int)Input::get('time_end')                : "";
        $Cmd       = Input::has('cmd')         ? (int)Input::get('cmd')                     : "";

        $offset   = ($page - 1)*$itemPage;

        $Model    = new RequestExtendTimeModel;

       
        if(!empty($Status)){
            $Model = $Model->where('status', $Status);
        }elseif ((string)$Status == "0"){
            $Model = $Model->where('status', $Status);
        }

        if(!empty($Keyword)){
            $Model = $Model->where('ticket_id', $Keyword);
        }
        if(!empty($TimeStart)){
            $Model = $Model->where('time_create','>=',$TimeStart);
        }
        
        if(!empty($TimeEnd)){
            $Model = $Model->where('time_create','<',$TimeEnd);
        }
        

        $Data = $Model->with(['user' => function ($q){
            return $q->select(['fullname', 'email', 'id']);
        }, 'ticket']);

         
        $Data = $Data->orderBy('time_create', "DESC");

        if($Cmd == 'export'){
            return $this->Export($Data);
        }


        $Total = $Data->count();


        if($Total == 0){
            return Response::json(array(
                'error'         => false,
                'error_message' => 'Thành công',
                'data'          => []
            ));
        }
        if(!empty($id)){
            $Data = $Data->where('id', $id)->first();
        }else {
            $Data = $Data->skip($offset)->take($itemPage)->get();
        }

        return Response::json([
            'error'         => false,
            'error_message' => '',
            'data'          => $Data,
            'total'         => $Total
        ]);
    }

    public function Export($Model){
        $Data = $Model->get()->toArray();

        $FileName   = 'Danh_sach_yeu_cau_gia_han';


        return Excel::create($FileName, function($excel) use($Data){
            $excel->sheet('Sheet1', function($sheet) use($Data){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách yêu cầu gia hạn'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  10, 'C'     =>  40, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,'H'     =>  30,
                    'I'  => 30, 'J'  => 30, 'K' => 30
                ));

                $sheet->row(3, array(
                    'STT', '#ID','Tiêu đề', 'Thời gian xử lý', 'Nội dung yêu cầu', 'Người yêu cầu', 'Số giờ muốn gia hạn', 'Thời gian gửi yêu cầu', 'Trạng thái'
                ));

                $sheet->row(3, function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });
                $sheet->setBorder('A3:K3', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        isset($val['ticket']) ? $val['ticket']['id'] : '',
                        isset($val['ticket']) ? $val['ticket']['title'] : '',
                        isset($val['ticket']) ? date("d/m/y H:m", $val['ticket']['time_over']) : '',
                        
                        $val['note'],
                        isset($val['user']) ? $val['user']['fullname'] : '',
                        $val['time'].' giờ',
                        $val['time_create'] > 0 ? date("d/m/y H:m",$val['time_create']) : '',
                        $val['status'] == 1 ? 'Đã xác nhận' : ($val['status'] == 2 ? 'Đã bị hủy' : 'Yêu cầu mới gửi'),
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');

    }
    public function postAccept($id){
        $UserInfo = $this->UserInfo();
        $Model = new RequestExtendTimeModel;
        $Model = $Model->where('id', $id)->where('status', 0)->first();
        if(!$Model){
            return Response::json(array(
                'error'         => true,
                'error_message' => 'Yêu cầu không tồn tại, hoặc đã được gia hạn',
                'data'          => []
            ));
        }
        $RequestModel       = new RequestModel;
        $Request            = $RequestModel->where('id', $Model->ticket_id)->first();
        $OldTime            = $Request->time_over;


        $TOver = (8 * 3600) * (int)$Model->time;
        $fromTime = $this->time();
        $currentHour = date("G", $fromTime);
        $currentMinute = date("i", $fromTime);
        $currentDay = date("N", $fromTime);
        $timeProcess = 0;
        if ($currentHour < 8) {
            $timeBonusFirstDay = 8 * 3600 + (24 - 17.5) * 3600 + 1.5 * 3600;
            $timeProcess = 8 * 3600;
        } else if ($currentHour >= 18) {
            $timeBonusFirstDay = (24 - 17.5) * 3600;
        } else if ($currentHour == 17 && $currentMinute >= 30) {
            $timeBonusFirstDay = (24 - 17.5) * 3600;
        } else {
            $timeBonusFirstDay = (24 - 17.5) * 3600 + (1.5 * 3600);
            $timeProcess = (17.5 - $currentHour) * 3600 - (1.5 * 3600) - ($currentMinute) * 60;;
        }

        $newTOver = $TOver - $timeProcess;
        $totalDays = (floor(($newTOver / (8 * 3600))));
        /*
         * tong time cua cac ngay lam day du + tong time bonus cua ngay nhan dau
         * + time process ngay dau
         * + 8h do bi day sang ngay hom sau + so du* thoi gian con lai
         */
        $timeBonus = $timeBonusFirstDay + $timeProcess;

        if ($totalDays > 0) {
            $timeBonus += $totalDays * 86400;
        }
        if ($TOver > $timeProcess) {
            /*
             * neu qua' 1 ngay thi se~ co so du thoi gian con lai
             */
            $timeBonus += 8 * 3600 + $newTOver % (8 * 3600);
            ++$totalDays;
        }
        $numberOfWeek = floor(($currentDay + $totalDays) / 7);

        if ($numberOfWeek > 0) {
            $timeBonus += $numberOfWeek * 24 * 3600;
        }


        /*$NewTime            = $OldTime + ((int)$Model->time * 3600) * 24;*/

        $NewTime            = $OldTime + $timeBonus;


        $Request->time_over = $NewTime;



        try {
            $Request->save();    
            $Model->status = 1;
            $Model->time_update = $this->time();
            $Model->save();
        } catch (Exception $e) {
            return Response::json(array(
                'error'         => true,
                'error_message' => 'Lỗi kết nối máy chủ !',
                'data'          => []
            ));   
        }

        try {

            $Create = LMongo::collection('log_change_ticket')->insert(array(
                'id'            => $Request->id,
                'new'           => ['time'=> $Model->time, 'unix' => $NewTime],
                'old'           => $OldTime,
                'time_create'   => $this->time(),
                'user_id'       => (int)$UserInfo['id'],
                'type'          => 'extend_time_over'
            ));
        } catch (Exception $e) {
            return array('error' => true, 'message' => 'UPDATE_LOG_FAIL' );
        }

        return Response::json(array(
            'error'         => false,
            'error_message' => 'Thành công',
            'data'          => $NewTime
        ));   
    }

    public function InsertLog($id, $DataOld, $Data, $Type){
        $UserInfo   = $this->UserInfo();
        $DataInsert = array(
            'id'            => (int)$id,
            'new'           => $Data,
            'old'           => $DataOld,
            'time_create'   => $this->time(),
            'user_id'       => (int)$UserInfo['id'],
            'type'          => $Type
        );

        $validation = Validator::make($DataInsert, array(
            'id'     => 'required|numeric|min:1',
            'new'    => 'required|array',
            'old'    => 'required|array',
        ));

        //error
        if($validation->fails()) {
            return array('error' => true, 'message' => $validation->messages());
        }

        try {
            $Create = LMongo::collection('log_change_ticket')->insert($DataInsert);
        } catch (Exception $e) {
            return array('error' => true, 'message' => 'FAIL');
        }
        return array('error' => false, 'message' => 'SUCCESS');
    }

    public function postReject($id){
        $UserInfo = $this->UserInfo();
        $Model = new RequestExtendTimeModel;
        $Model = $Model->where('id', $id)->where('status', 0)->first();
        if(!$Model){
            return Response::json(array(
                'error'         => true,
                'error_message' => 'Yêu cầu không tồn tại',
                'data'          => []
            ));
        }
        
        try {
            $Model->status = 2;
            $Model->time_update = $this->time();
            $Model->save();
        } catch (Exception $e) {
            return Response::json(array(
                'error'         => true,
                'error_message' => 'Lỗi kết nối máy chủ !',
                'data'          => []
            ));   
        }

        try {
            $Create = LMongo::collection('log_ticket_request_extend_time')->insert(array(
                'request_id'   => $id,
                'accept_by'   => $UserInfo['id'],
                'ticket_id'   => $Request->id,
                'reject'      => true,
                'time_accept' => $this->time()
            ));
        } catch (Exception $e) {
            return array('error' => true, 'message' => 'UPDATE_LOG_FAIL' );
        }

        return Response::json(array(
            'error'         => false,
            'error_message' => 'Hủy gia hạn thành công',
            'data'          => $NewTime
        ));   
    }
}
