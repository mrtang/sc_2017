<?php namespace ticket;

use Validator;
use Response;
use Input;
use Exception;
use ticketmodel\CaseTicketModel;
use ticketmodel\RequestModel;
use ticketmodel\CaseTypeModel;

class CaseTicketController extends \BaseController {
    private $data_new   = array();
    private $data_old   = array();
    private $field      = '';

    private function SetData($type, $data_old, $data_new){
        $this->data_old[$type]     = $data_old;
        $this->data_new[$type]      = $data_new;
    }

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate($id)
	{
		$validation = Validator::make(Input::json()->all(), array(
            'type_id'   => 'sometimes|required|numeric',
            'active'    => 'sometimes|required|in:0,1'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $UserInfo           = $this->UserInfo();

        $TicketModel    = new RequestModel;
        $Ticket = $TicketModel->where('id',$id)->first();
        if(!isset($Ticket->id)){
            $contents = array(
                'error'     => true,
                'message'   => 'NOT_EXISTS'
            );
            return Response::json($contents);
        }

        if($UserInfo['privilege'] != 2 && $Ticket->status == 'CLOSED'){
            $contents = array(
                'error'     => true,
                'message'   => 'USER_NOT_ALLOWED'
            );
            return Response::json($contents);
        }

        $TypeId             = [];
        $Data               = Input::json()->all();
        $Active             = isset($Data['active']) ? (int)$Data['active'] : null;
        $TypeId[]           = isset($Data['type_id']) ? (int)$Data['type_id'] : (int)$Data['datacase']['type_id'];

        // Kiểm tra ticket đã được phân loại chưa
        if(!isset($Active) || !empty($Active)){
            $ListCase   = CaseTicketModel::where('ticket_id', (int)$Ticket->id)->where('active',1)->get()->toArray();
            if(!empty($ListCase)){
                foreach($ListCase as $val){
                    $TypeId[]   = (int)$val['type_id'];
                }
            }
        }


        // update
        $Case = CaseTypeModel::whereIn('id',$TypeId)->get()->toArray();
        if(empty($Case)){
            return Response::json([
                'error'     => true,
                'message'   => 'CASE_TYPE_NOT_EXISTS'
            ]);
        }

        $TOver      = 0;
        $Priority   = $Ticket->priority;
        foreach($Case as $val){
            if($val['estimate_time'] > $TOver){
                $TOver  = $val['estimate_time'];
            }

            if($val['priority'] > $Priority){
                $Priority   = (int)$val['priority'];
            }
        }

        // độ quan trọng
        if(!isset($Active) || !empty($Active)){
            $Ticket->priority   = $Priority;
        }

        // CHưa có thời gian xử lý
        if(empty($Ticket->time_over)){
            $fromTime = $this->time();
            $currentHour = date("G", $fromTime);
            $currentMinute = date("i", $fromTime);
            $currentDay = date("N", $fromTime);
            $timeProcess = 0;
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

            try{
                $Ticket->time_over      = $this->time() + $timeBonus;
                $Ticket->time_update    = $this->time();
                if(!Input::has('data.content')){
                    $Ticket->user_last_action   = (int)$UserInfo['id'];
                }

                $Ticket->save();
            }catch (Exception $e){
                return Response::json([
                    'error'     => true,
                    'message'   => 'UPDATE_TIME_OVER_ERROR'
                ]);
            }
        }

        $Model  = new CaseTicketModel;
        try {
            $Insert                     = $Model::firstOrCreate(array('ticket_id' => $id, 'type_id' => $TypeId[0]));
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'id'        => $Insert->id
            );

            if(isset($Active)){
                $this->SetData('active', $Insert->active, $Active);
                $Insert->active = $Active;
                $this->SetData('type_id', $TypeId[0], $TypeId[0]);
                $this->field    = 'case';
                $RequestController  = new RequestController;
                $RequestController->InsertLog($id, $this->data_old, $this->data_new,$this->field);
            }
            $Insert->save();
        } catch (Exception $e) {
            $contents = array(
                'error'     => true,
                'message'   => 'INSERT_FAIL'
            );
        }
        
        return Response::json($contents);
	}
}
