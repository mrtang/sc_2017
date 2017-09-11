<?php namespace ticket;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use sellermodel\StatisticModel;
use ticketmodel\CaseModel;
use ticketmodel\CaseTypeModel;
use ticketmodel\RequestModel;
use ticketmodel\CaseTicketModel;
use ticketmodel\AssignModel;
use ticketmodel\FeedbackModel;

use Input;
use User;
use Lang;

class StatisticController extends \BaseController {


    public function getStatistic() {
        $timeStart = Input::has('from_date') ? (int)Input::get('from_date') : 0;
        $timeEnd = Input::has('to_date') ? (int)Input::get('to_date') : 0;
        $caseID = Input::has('case_id') ? (int)Input::get('case_id') : 0;
        $typeID = Input::has('type_id') ? (int)Input::get('type_id') : 0;

        if($timeStart==0) {
            $timeStart = $this->time() - 30*86400;
        }

        $StatisticModel = new StatisticModel();
        $StatisticModel = $StatisticModel->where('time_create','>=',$timeStart);
        if($timeEnd>0) {
            $StatisticModel = $StatisticModel->where('time_create','<=',$timeEnd);
        }
        if($caseID>0) {
            $StatisticModel = $StatisticModel->where('case_id',$caseID);
        }
        if($typeID>0) {
            $StatisticModel = $StatisticModel->where('type_id',$typeID);
        }
        $TicketReportWaiting = $StatisticModel->select([
            DB::raw('COUNT(DISTINCT ticket_id) as total')
        ])->where('status','ASSIGNED')->pluck('total');


        $StatisticModel = new StatisticModel();
        $StatisticModel = $StatisticModel->where('time_create','>=',$timeStart);
        if($timeEnd>0) {
            $StatisticModel = $StatisticModel->where('time_create','<=',$timeEnd);
        }
        if($caseID>0) {
            $StatisticModel = $StatisticModel->where('case_id',$caseID);
        }
        if($typeID>0) {
            $StatisticModel = $StatisticModel->where('type_id',$typeID);
        }
        $TicketReportProcessing = $StatisticModel->select([
            DB::raw('COUNT(DISTINCT ticket_id) as total')
        ])->where('status','PENDING_FOR_CUSTOMER')->pluck('total');


        $StatisticModel = new StatisticModel();
        $StatisticModel = $StatisticModel->where('time_create','>=',$timeStart);
        if($timeEnd>0) {
            $StatisticModel = $StatisticModel->where('time_create','<=',$timeEnd);
        }
        if($caseID>0) {
            $StatisticModel = $StatisticModel->where('case_id',$caseID);
        }
        if($typeID>0) {
            $StatisticModel = $StatisticModel->where('type_id',$typeID);
        }
        $TicketReportProcessed = $StatisticModel->select([
            DB::raw('COUNT(DISTINCT ticket_id) as total')
        ])->where('status','PROCESSED')->pluck('total');

        $StatisticModel = new StatisticModel();
        $StatisticModel = $StatisticModel->where('time_create','>=',$timeStart);
        if($timeEnd>0) {
            $StatisticModel = $StatisticModel->where('time_create','<=',$timeEnd);
        }
        if($caseID>0) {
            $StatisticModel = $StatisticModel->where('case_id',$caseID);
        }
        if($typeID>0) {
            $StatisticModel = $StatisticModel->where('type_id',$typeID);
        }
        $StatisticModel = $StatisticModel->where('assign_id','>',0);
        $TimeReport = $StatisticModel->select([
            DB::raw('AVG(time_reply) as per_time_reply'),
            DB::raw('AVG(time_process) as per_time_process'),
        ])->where('status','CLOSED')->groupBy('ticket_id')->first();

        return Response::json([
            'time_reply'            =>  (!empty($TimeReport)) ? $TimeReport->per_time_reply : 0,
            'time_process'          =>  (!empty($TimeReport)) ? $TimeReport->per_time_process : 0,
            'number_of_waiting'     =>  $TicketReportWaiting,
            'number_of_response'    =>  $TicketReportProcessing,
            'number_of_close'       =>  $TicketReportProcessed,
        ]);
    }


    public function getInsight() {

        $caseID         = Input::has('case_id') ? (int)Input::get('case_id') : 0;
        $typeID         = Input::has('type_id') ? (int)Input::get('type_id') : 0;
        $timeStart      = Input::has('from_date') ? (int)Input::get('from_date') : 0;
        $timeEnd        = Input::has('to_date') ? (int)Input::get('to_date') : 0;

        $StatisticModel = new StatisticModel;

        if($timeStart==0) {
            $timeStart = 30*86400;
        }
        $StatisticModel = $StatisticModel->where('time_create','>=',$timeStart);
        if($timeEnd>0) {
            $StatisticModel = $StatisticModel->where('time_create', '<=', $timeEnd);
        }
        if($caseID>0) {
            $StatisticModel = $StatisticModel->where('case_id',$caseID);
        }
        if($typeID>0) {
            $StatisticModel = $StatisticModel->where('type_id',$typeID);
        }
        $StatisticModel = $StatisticModel->where('assign_id','>',0);
        $StatisticModel = $StatisticModel->where('time_reply','>',0);

        $StatisticModel = $StatisticModel->groupBy('assign_id');

        $StatisticModel = $StatisticModel->select(['*',DB::raw('AVG(time_process) as total_time_process'),DB::raw('AVG(time_reply) as per_time_reply')]);
        $ListStatistics = $StatisticModel->get();
        if(!$ListStatistics->isEmpty()) {

            //merge users
            $ListUserID = array();
            foreach($ListStatistics as $Statistic) {
                $ListUserID[] = $Statistic->assign_id;
            }
            $ListUsers = User::whereIn('id',$ListUserID)->get();
            $UserArr = array();
            if(!$ListUsers->isEmpty()) {
                foreach($ListUsers as $User) {
                    $UserArr[$User->id] = $User;
                }
            }
            //merge ticket status
            $StatisticModel = new StatisticModel;
            if($timeStart==0) {
                $timeStart = 30*86400;
            }
            $StatisticModel = $StatisticModel->where('time_create','>=',$timeStart);
            if($timeEnd>0) {
                $StatisticModel = $StatisticModel->where('time_create', '<=', $timeEnd);
            }
            if($caseID>0) {
                $StatisticModel = $StatisticModel->where('case_id',$caseID);
            }
            if($typeID>0) {
                $StatisticModel = $StatisticModel->where('type_id',$typeID);
            }
            $TicketStatusByUser = $StatisticModel->select(['assign_id','status','process',DB::raw('COUNT(status) as total')])->groupBy(['assign_id','status','process'])->get();
            $TicketStatusByUserArr = array();
            if(!$TicketStatusByUser->isEmpty()) {
                foreach($TicketStatusByUser as $OneTicketStatusByUser) {
                    $TicketStatusByUserArr[$OneTicketStatusByUser->assign_id][$OneTicketStatusByUser->status][$OneTicketStatusByUser->process]    =   $OneTicketStatusByUser->total;

                }
            }
            
            //get list out of date
            $StatisticModel = new StatisticModel;
            if($timeStart==0) {
                $timeStart = 30*86400;
            }
            $StatisticModel = $StatisticModel->where('time_create','>=',$timeStart);
            if($timeEnd>0) {
                $StatisticModel = $StatisticModel->where('time_create', '<=', $timeEnd);
            }
            if($caseID>0) {
                $StatisticModel = $StatisticModel->where('case_id',$caseID);
            }
            if($typeID>0) {
                $StatisticModel = $StatisticModel->where('type_id',$typeID);
            }
            $listOutOfDate = $StatisticModel->where('time_over','>',0)
                ->select([
                    'assign_id',
                    'out_of_date',
                    DB::raw('COUNT(*) as total')
                ])
                ->groupBy([
                    'assign_id',
                    'out_of_date'
                ])->get();
            $LOUtOfDate = [];
            if(!$listOutOfDate->isEmpty()) {
                foreach($listOutOfDate as $OneOutOfDate) {
                    if(!isset($LOUtOfDate[$OneOutOfDate->assign_id])) {
                        $LOUtOfDate[$OneOutOfDate->assign_id][0] = 0;
                        $LOUtOfDate[$OneOutOfDate->assign_id][1] = 0;
                    }
                    $LOUtOfDate[$OneOutOfDate->assign_id][$OneOutOfDate->out_of_date] = $OneOutOfDate->total;
                }
            }
            //get list real out of date
            $StatisticModel = new StatisticModel;
            if($timeStart==0) {
                $timeStart = 30*86400;
            }
            $StatisticModel = $StatisticModel->where('time_create','>=',$timeStart);
            if($timeEnd>0) {
                $StatisticModel = $StatisticModel->where('time_create', '<=', $timeEnd);
            }
            if($caseID>0) {
                $StatisticModel = $StatisticModel->where('case_id',$caseID);
            }
            if($typeID>0) {
                $StatisticModel = $StatisticModel->where('type_id',$typeID);
            }
            $listRealOutOfDate = $StatisticModel->where('time_over','>',0)
                ->select([
                    'assign_id',
                    'out_of_date',
                    DB::raw(' IF((time_create + time_process) > time_over, 1, 0) as is_over'),
                    DB::raw('COUNT(*) as total')
                ])
                ->groupBy([
                    'assign_id',
                    'is_over'
                ])->get();

            $LRealOutOfDate = [];
            if(!$listRealOutOfDate->isEmpty()) {
                foreach($listRealOutOfDate as $OneOutOfDate) {
                    if(!isset($LRealOutOfDate[$OneOutOfDate->assign_id])) {
                        $LRealOutOfDate[$OneOutOfDate->assign_id][0] = 0;
                        $LRealOutOfDate[$OneOutOfDate->assign_id][1] = 0;
                    }
                    $LRealOutOfDate[$OneOutOfDate->assign_id][$OneOutOfDate->is_over] = $OneOutOfDate->total;
                }
            }
            //merge statistic, users, ticket status
            foreach($ListStatistics as $k => $Statistic) {
                if(isset($UserArr[$Statistic->assign_id])) {
                    $ListStatistics[$k]->user = $UserArr[$Statistic->assign_id];
                }
                if(isset($TicketStatusByUserArr[$Statistic->assign_id])) {
                    $ListStatistics[$k]->status = $TicketStatusByUserArr[$Statistic->assign_id];
                }
                if(!empty($LOUtOfDate[$Statistic->assign_id])) {
                    $ListStatistics[$k]->outOfDatePercent = round($LOUtOfDate[$Statistic->assign_id][1]/($LOUtOfDate[$Statistic->assign_id][0] + $LOUtOfDate[$Statistic->assign_id][1]),2)*100;
                }
                if(!empty($LRealOutOfDate[$Statistic->assign_id])) {
                    $ListStatistics[$k]->realOutOfDatePercent = round($LRealOutOfDate[$Statistic->assign_id][1]/($LRealOutOfDate[$Statistic->assign_id][0] + $LRealOutOfDate[$Statistic->assign_id][1]),2)*100;
                }
            }



            $response = [
                'error' =>  false,
                'data'  =>  $ListStatistics
            ];
            return Response::json($response);
        } else {

            return Response::json([
                'error'     =>  true,
                'message'   =>  Lang::get('response.NOT_DATA')
            ]);
        }
    }


    public function getReportCaseType() {

        $TimeStart = Input::has('from_date') ? (int)Input::get('from_date') : 0;
        $TimeEnd = Input::has('to_date') ? (int)Input::get('to_date') : 0;
        $CaseID = Input::has('case_id') ? (int)Input::get('case_id') : 0;
        $TypeID = Input::has('type_id') ? (int)Input::get('type_id') : 0;
        $FromUser = Input::has('from_user') ? Input::get('from_user') : 0;
        $ToUser = Input::has('to_user') ? Input::get('to_user') : 0;

        if($TimeStart==0) {
            $TimeStart = 30*86400;
        }
        //get from user id
        if(filter_var($FromUser,FILTER_VALIDATE_EMAIL)) {
            $KeyWhereFromUser = "email";
        } else {
            $KeyWhereFromUser = "fullname";
        }
        if(!empty($FromUser)) {
            $FromUerID = User::where($KeyWhereFromUser,$FromUser)->pluck('id');
        }

        //get to user id
        if(filter_var($ToUser,FILTER_VALIDATE_EMAIL)) {
            $KeyWhereToUser = "email";
        } else {
            $KeyWhereToUser = "fullname";
        }
        if(!empty($ToUser)) {
            $ToUerID = User::where($KeyWhereToUser,$ToUser)->pluck('id');
        }
        $StatisticModel = new StatisticModel();
        $StatisticModel = $StatisticModel->where('time_create','>=',$TimeStart);
        if($TimeEnd>0) {
            $StatisticModel = $StatisticModel->where('time_create','<=',$TimeEnd);
        }
        if($CaseID>0) {
            $StatisticModel = $StatisticModel->where('case_id',$CaseID);
        }
        if($TypeID>0) {
            $StatisticModel = $StatisticModel->where('type_id',$TypeID);
        } else {
            $StatisticModel = $StatisticModel->where('type_id','>',0);
        }

        if(isset($FromUerID)) {
            $StatisticModel = $StatisticModel->where('user_id',$FromUerID);
        }
        if(isset($ToUerID)) {
            $StatisticModel = $StatisticModel->where('assign_id',$ToUerID);
        }

        $StatisticByTypeModel = clone $StatisticModel;

        $StatisticModel = $StatisticModel->where('assign_id','>',0);

        $StatisticModel = $StatisticModel->where('status','CLOSED');
        $StatisticModel = $StatisticModel->groupBy('type_id');
        $StatisticModel = $StatisticModel->select([
            'type_id',
            'case_id',
            DB::raw('AVG(time_reply) as per_time_reply'),
            DB::raw('AVG(time_process) as total_time_process'),
            DB::raw('AVG(time_assign-time_create) as per_time_assign'),
            DB::raw('AVG(time_close) as per_time_close')
        ]);
        $ListReportCaseType = $StatisticModel->get();

        //list ticket dung han theo type
        $StatisticByTypeModel = $StatisticByTypeModel->where('time_over','>',0);
        $StatisticByTypeModel = $StatisticByTypeModel->groupBy([
            'type_id',
            'out_of_date'
        ]);
        $listStatisticByType = $StatisticByTypeModel->get([
            'type_id',
            'out_of_date',
            DB::raw('COUNT(*) as total')
        ]);
        $listStatisticByTypeArr = [];
        if(!$listStatisticByType->isEmpty()) {
            foreach($listStatisticByType as $oneStatisticByType) {
                $listStatisticByTypeArr[$oneStatisticByType->type_id][$oneStatisticByType->out_of_date] = $oneStatisticByType->total;
            }
        }

        $ListCaseID = $ListTypeID = array();
        if(!$ListReportCaseType->isEmpty()) {
            foreach($ListReportCaseType as $OneReportCaseType) {
                if(!in_array($OneReportCaseType->case_id,$ListCaseID)) {
                    $ListCaseID[] = $OneReportCaseType->case_id;
                }
                $ListTypeID[] = $OneReportCaseType->type_id;
            }

            $ListType = CaseTypeModel::whereIn('id',$ListTypeID)->get();
            $ListCase = CaseModel::whereIn('id',$ListCaseID)->get();
            $ListCaseArr = $ListTypeArr = array();
            if(!$ListCase->isEmpty()) {
                foreach($ListCase as $OneCase) {
                    $ListCaseArr[$OneCase->id] = $OneCase->name;
                }
            }
            if(!$ListType->isEmpty()) {
                foreach($ListType as $OneType) {
                    $ListTypeArr[$OneType->id] = $OneType;
                }
            }

            foreach($ListReportCaseType as $k => $OneReportCaseType) {
                $ListReportCaseType[$k]->type = isset($ListTypeArr[$OneReportCaseType->type_id]) ? $ListTypeArr[$OneReportCaseType->type_id] : '';
                $ListReportCaseType[$k]->case = isset($ListCaseArr[$OneReportCaseType->case_id]) ? $ListCaseArr[$OneReportCaseType->case_id] : '';
                if(isset($listStatisticByTypeArr[$OneReportCaseType->type_id][0])) {
                    $numberOfTicket = $listStatisticByTypeArr[$OneReportCaseType->type_id][0];
                } else {
                    $numberOfTicket = 0;
                }
                if(isset($listStatisticByTypeArr[$OneReportCaseType->type_id][1])) {
                    $numberOfTicketLate = $listStatisticByTypeArr[$OneReportCaseType->type_id][1];
                } else {
                    $numberOfTicketLate = 0;
                }
                $totalTicket = $numberOfTicket + $numberOfTicketLate;
                if($totalTicket == 0) {
                    $percentOfTicket = 100;
                } else {
                    $percentOfTicket = round(($numberOfTicket/$totalTicket),2)*100;
                }

                $ListReportCaseType[$k]->number_of_ticket = $totalTicket;
                $ListReportCaseType[$k]->percent_of_ticket = $percentOfTicket;
            }

            $Response = [
                'error' =>  false,
                'data'  =>  $ListReportCaseType,
            ];
        } else {
            $Response = [
                'error'     =>  true,
                'message'   =>  'Không có kết quả'
            ];
        }

        return Response::json($Response);




    }



    public function getReportCreateTicket() {

        $TimeStart = Input::has('from_date') ? (int)Input::get('from_date') : 0;
        $TimeEnd   = Input::has('to_date') ? (int)Input::get('to_date') : 0;
        $Type      = Input::has('type_id') ? (int)Input::get('type_id') : 0;
        $CaseID     = Input::has('case_id')   ?   Input::get('case_id') : 0;
        $User      = Input::has('user') ? Input::get('user') : 0;
        

        if($TimeStart == 0) {
            $TimeStart = $this->time() - 30 * 86400;
        }
        //get from user id
        if(filter_var($User,FILTER_VALIDATE_EMAIL)) {
            $KeyWhereUser = "email";
        } else {
            $KeyWhereUser = "fullname";
        }
        if(!empty($User)) {
            $UserId = User::where($KeyWhereUser,$User)->pluck('id');
        }

        $Model = new RequestModel;

        $Model = $Model->where('time_create', '>=', $TimeStart);
        
        if($TimeEnd>0) {
            $Model = $Model->where('time_create','<=',$TimeEnd);
        }
        if(!empty($UserId)){
            $Model = $Model->where('user_id', '>=', $UserId);
        }

        if(!empty($Type)){
            $ListTickId = CaseTicketModel::where('type_id', $Type)->lists('ticket_id');
            $Model = $Model->whereIn('id',  $ListTickId);
        }
        $Model = $Model->select(['user_id', DB::raw('count(user_id) as total')])->groupBy('user_id')->with(['users'=> function ($query){
            $query->select(['email', 'fullname', 'id']);
        }])->orderBy('total', 'DESC')->take(50);
        $Data  = $Model->get()->toArray();


        $Response = [
            'error' =>  false,
            'data'  =>  $Data,
        ];
        
        return Response::json($Response);
    }

    public function getGraphCreateTicket (){
         $TimeStart = Input::has('from_date') ? (int)Input::get('from_date') : 0;
         $TimeEnd   = Input::has('to_date') ? (int)Input::get('to_date') : 0;
         $TypeID      = Input::has('type_id') ? (int)Input::get('type_id') : 0;
         $CaseID     = Input::has('case_id')   ?   Input::get('case_id') : 0;
         $User      = Input::has('user') ? Input::get('user') : 0;

         $statisticModel = new StatisticModel;
        if($TimeStart==0) {
            $TimeStart = $this->time() - 30 * 86400;
        }
        $statisticModel = $statisticModel->where('time_create','>=',$TimeStart);

        if($TimeEnd>0) {
            $statisticModel = $statisticModel->where('time_create','<=',$TimeEnd);
        }

        if($TypeID>0) {
            $statisticModel = $statisticModel->where('type_id',$TypeID);
        }
        if($User>0) {
            $statisticModel = $statisticModel->where('user_create_id',$User);
        }

        $groupBy = "case_id";
        if(!empty($CaseID)) {
            $groupBy = "type_id";
        }
        $statisticModel = $statisticModel->groupBy([
            DB::raw('DATE(FROM_UNIXTIME(`time_create`))'),
            $groupBy
        ]);
        $statisticModel = $statisticModel->select([
            DB::raw('DATE(FROM_UNIXTIME(`time_create`)) as time'),
            DB::raw('COUNT(DISTINCT ticket_id) as total'),
            $groupBy
        ]);

        $ListStatistics       = $statisticModel->get();
        $ListCase             = array();
        $ticks                = [];
        $listStatisticsByCase = [];

        $LStatistic = new \stdClass();
        $LStatistic->series = [];
        $LStatistic->data = [];


        if($CaseID>0 || $TypeID>0) {
            if($TypeID>0) {
                $Cases = CaseTypeModel::where('id',$TypeID)->get();
            } else {
                $Cases = CaseTypeModel::where('case_id',$CaseID)->get();
            }
            if(!$Cases->isEmpty()) {
                foreach($Cases as $oneType) {
                    $ListCase[$oneType->id] = $oneType->type_name;
                    $ticks[] = $oneType->type_name;
                }
            }
        } else {
            $CasesModel = CaseModel::where('active',1);
            $Cases = $CasesModel->get();
            if(!$Cases->isEmpty()) {
                $ListCase = [];
                foreach($Cases as $OneCase) {
                    $ListCase[$OneCase->id] = $OneCase->name;
                    $ticks[] = $OneCase->name;
                }
            }
        }

        //statistic by case
        if(!$ListStatistics->isEmpty()) {
            foreach($ListStatistics as $oneStatistic) {
                if($CaseID > 0) {
                    $key = $oneStatistic->type_id;
                } else {
                    $key = $oneStatistic->case_id;
                }
                $listStatisticsByCase[$key][strtotime($oneStatistic->time)] = $oneStatistic->total;
            }
        }


        $start    = new \DateTime(date("Y-m-d 00:00:00",$TimeStart));
        $end      = new \DateTime(date("Y-m-d 23:59:00",$TimeEnd));

        $interval = \DateInterval::createFromDateString('1 day');
        $period   = new \DatePeriod($start, $interval, $end);
        $newChartDataCase = $newChartDataReply = $newChartDataProcess = [];

        $totalPeriod = 0;
        foreach($period as $var) {
            ++$totalPeriod;
        }
        //map col X
        $colX = [];
        $colOffset = 0;
        if(!empty($period)) {
            $totalRowPerCol = round(($totalPeriod/6),0,PHP_ROUND_HALF_UP);
            if($totalRowPerCol == 0) {
                $totalRowPerCol = 1;
            }
            foreach ($period as $k => $dt) {
                if($k==0 || $k%$totalRowPerCol==0) {
                    $date = strtotime($dt->format("Y-m-d").' 00:00:00');
                    $colX[$colOffset]['col_name'] = date("d/m",$date);
                    $colX[$colOffset]['from_date'] = $date;
                }
                if(($k+1)%$totalRowPerCol==0 || ($k+1)==$totalPeriod) {
                    $date = strtotime($dt->format("Y-m-d").' 23:59:59');
                    if($colX[$colOffset]['col_name'] != date("d/m",$date)) {
                        $colX[$colOffset]['col_name'] .= " - ".date("d/m",$date);
                    }
                    $colX[$colOffset]['to_date'] = $date;
                    ++$colOffset;
                }
            }
        }

        if(!empty($colX)) {
            foreach($colX as $j=> $oneColX) {
                //set data cases
                $oneRecordReport = new \stdClass();
                $oneRecordReport->x = $oneColX['col_name'];
                $oneRecordReport->y = array();
                $oneRecordReport->tooltip = array();
                foreach($Cases as $oneCase) {
                    $CaseID = $oneCase->id;
                    $total = 0;
                    if(!empty($listStatisticsByCase[$CaseID])) {
                        foreach($listStatisticsByCase[$CaseID] as $time => $oneStatisticByTime) {
                            if($time >= $oneColX['from_date'] && $time <= $oneColX['to_date']) {
                                $total += $oneStatisticByTime;
                            }
                        }
                    }
                    $oneRecordReport->y[] = $total;
                    if(Input::has('case_id')) {
                        $column = $oneCase->type_name;
                    } else {
                        $column = $oneCase->name;
                    }
                    $oneRecordReport->tooltip[] = $column." có ".$total." tickets";
                }
                $newChartDataCase[] = $oneRecordReport;
            }
        }
        $LStatistic->series  = $ticks;
        $LStatistic->data = $newChartDataCase;

        return Response::json($LStatistic);


    }

    public function getSetTime() {
        $timeStart = $this->time() - 90* 86400;
        $ticket = RequestModel::where('time_create','>=',$timeStart)->where('time_reply',0)->select(['id','time_create'])->whereIn('status', array('PROCESSING','PROCESSED','CLOSED'))->take(10)->get();
        $ticketID = [];
        if(!$ticket->isEmpty()) {
            foreach($ticket as $var) {
                $ticketID[] = $var->id;
            }
        }
        if(!empty($ticketID)) {
            $feedbacks = FeedbackModel::whereIn('ticket_id',$ticketID)->where('source','!=','note')->groupBy('ticket_id')->orderBy('time_create')->get();
        }else{
            return Response::json(array("EMPTY TICKET"));
        }
        if(!$feedbacks->isEmpty()) {
            foreach($ticket as $var) {
                $update = false;
                foreach($feedbacks as $feedback) {
                    if($var->id == $feedback->ticket_id) {
                        $request = RequestModel::where('time_create','>=',$timeStart)->where('id',$var->id)->first();
                        $request->time_reply = $feedback->time_create - $var->time_create;
                        $request->save();
                        $update = true;
                    }
                }
                if(!$update){
                    $request = RequestModel::where('time_create','>=',$timeStart)->where('id',$var->id)->first();
                    $request->time_reply = $var->time_update - $var->time_create;
                    $request->save();
                }

            }
        }else{
            foreach($ticket as $var) {
                $request = RequestModel::where('time_create','>=',$timeStart)->where('id',$var->id)->first();
                $request->time_reply = $var->time_update - $var->time_create;
                $request->save();
            }
        }
        return Response::json(array($feedbacks,$ticketID));
    }


    public function getReportCases() {
        $FromDate   = Input::has('from_date') ? Input::get('from_date') : 0;
        $ToDate     = Input::has('to_date')   ?   Input::get('to_date') : 0;
        $CaseID     = Input::has('case_id')   ?   Input::get('case_id') : 0;
        $TypeID     = Input::has('type_id')   ?   Input::get('type_id') : 0;
        $FromUser     = Input::has('from_user')   ?   Input::get('from_user') : '';
        $ToUser     = Input::has('to_user')   ?   Input::get('to_user') : '';
        $FromUserID = $ToUserID = 0;
        if(!empty($FromUser)) {
            $FromUserID = User::where("email",$FromUser)->pluck('id');
        }
        if(!empty($ToUser)) {
            $ToUserID = User::where("email",$ToUser)->pluck('id');
        }

        //get list data date & case
        $ListStatistics = $this->getStatisticByCases($FromDate, $ToDate, $CaseID, $TypeID, $FromUserID, $ToUserID);

        //get list data date & time reply
        $ListStatisticsReply = $this->getStatisticReply($FromDate, $ToDate, $CaseID, $TypeID, $FromUserID, $ToUserID);

        //get list data date & time process
        $ListStatisticsProcess = $this->getStatisticProcess($FromDate, $ToDate, $CaseID, $TypeID, $FromUserID, $ToUserID);


        $ListCase = array();

        $ticks = [];
        if($CaseID>0 || $TypeID>0) {
            if($TypeID>0) {
                $Cases = CaseTypeModel::where('id',$TypeID)->get();
            } else {
                $Cases = CaseTypeModel::where('case_id',$CaseID)->get();
            }
            if(!$Cases->isEmpty()) {
                foreach($Cases as $oneType) {
                    $ListCase[$oneType->id] = $oneType->type_name;
                    $ticks[] = $oneType->type_name;
                }
            }
        } else {
            $CasesModel = CaseModel::where('active',1);
            $Cases = $CasesModel->get();
            if(!$Cases->isEmpty()) {
                $ListCase = [];
                foreach($Cases as $OneCase) {
                    $ListCase[$OneCase->id] = $OneCase->name;
                    $ticks[] = $OneCase->name;
                }
            }
        }



        //case chart
        $LStatistic = new \stdClass();
        $LStatistic->series = [];
        $LStatistic->data = [];

        //reply chart
        $ReplyStatistic = new \stdClass();
        $ReplyStatistic->series = [];
        $ReplyStatistic->data = [];

        //process chart
        $ProcessStatistic = new \stdClass();
        $ProcessStatistic->series = [];
        $ProcessStatistic->data = [];

        $listStatisticsByCase = $listStatisticsByTimeReply = $listStatisticsByTimeProcess = [];

        //statistic by case
        if(!$ListStatistics->isEmpty()) {
            foreach($ListStatistics as $oneStatistic) {
                if($CaseID > 0) {
                    $key = $oneStatistic->type_id;
                } else {
                    $key = $oneStatistic->case_id;
                }
                $listStatisticsByCase[$key][strtotime($oneStatistic->time)] = $oneStatistic->total;
            }
        }
        //statistic by time reply
        if(!$ListStatisticsReply->isEmpty()) {
            foreach($ListStatisticsReply as $oneStatistic) {
                $listStatisticsByTimeReply[strtotime($oneStatistic->time)] = $oneStatistic->per_time_reply;
            }
        }
        //statistic by time process
        if(!$ListStatisticsProcess->isEmpty()) {
            foreach($ListStatisticsProcess as $oneStatistic) {
                $listStatisticsByTimeProcess[strtotime($oneStatistic->time)] = $oneStatistic->per_time_process;
            }
        }
        $start    = new \DateTime(date("Y-m-d 00:00:00",$FromDate));
        $end      = new \DateTime(date("Y-m-d 23:59:00",$ToDate));

        $interval = \DateInterval::createFromDateString('1 day');
        $period   = new \DatePeriod($start, $interval, $end);
        $newChartDataCase = $newChartDataReply = $newChartDataProcess = [];

        $totalPeriod = 0;
        foreach($period as $var) {
            ++$totalPeriod;
        }
        //map col X
        $colX = [];
        $colOffset = 0;
        if(!empty($period)) {
            $totalRowPerCol = round(($totalPeriod/6),0,PHP_ROUND_HALF_UP);
            if($totalRowPerCol == 0) {
                $totalRowPerCol = 1;
            }
            foreach ($period as $k => $dt) {
                if($k==0 || $k%$totalRowPerCol==0) {
                    $date = strtotime($dt->format("Y-m-d").' 00:00:00');
                    $colX[$colOffset]['col_name'] = date("d/m",$date);
                    $colX[$colOffset]['from_date'] = $date;
                }
                if(($k+1)%$totalRowPerCol==0 || ($k+1)==$totalPeriod) {
                    $date = strtotime($dt->format("Y-m-d").' 23:59:59');
                    if($colX[$colOffset]['col_name'] != date("d/m",$date)) {
                        $colX[$colOffset]['col_name'] .= " - ".date("d/m",$date);
                    }
                    $colX[$colOffset]['to_date'] = $date;
                    ++$colOffset;
                }
            }
        }
        if(!empty($colX)) {
            foreach($colX as $j=> $oneColX) {
                //set data cases
                $oneRecordReport = new \stdClass();
                $oneRecordReport->x = $oneColX['col_name'];
                $oneRecordReport->y = array();
                $oneRecordReport->tooltip = array();
                foreach($Cases as $oneCase) {
                    $CaseID = $oneCase->id;
                    $total = 0;
                    if(!empty($listStatisticsByCase[$CaseID])) {
                        foreach($listStatisticsByCase[$CaseID] as $time => $oneStatisticByTime) {
                            if($time >= $oneColX['from_date'] && $time <= $oneColX['to_date']) {
                                $total += $oneStatisticByTime;
                            }
                        }
                    }
                    $oneRecordReport->y[] = $total;
                    if(Input::has('case_id')) {
                        $column = $oneCase->type_name;
                    } else {
                        $column = $oneCase->name;
                    }
                    $oneRecordReport->tooltip[] = $column." có ".$total." tickets";
                }
                $newChartDataCase[] = $oneRecordReport;
                //set data time reply
                $totalTime = 0;
                $oneRecordReportReply = new \stdClass();
                $oneRecordReportReply->x = $oneColX['col_name'];
                $oneRecordReportReply->y = array();
                if(!empty($listStatisticsByTimeReply)) {
                    foreach($listStatisticsByTimeReply as $time => $oneStatisticByTime) {
                        if($time >= $oneColX['from_date'] && $time <= $oneColX['to_date']) {
                            $totalTime += $oneStatisticByTime;
                        }
                    }
                }
                $oneRecordReportReply->y[] = $totalTime;
                $newChartDataReply[] = $oneRecordReportReply;


                //set data time process
                $totalTime = 0;
                $oneRecordReportProcess = new \stdClass();
                $oneRecordReportProcess->x = $oneColX['col_name'];
                $oneRecordReportProcess->y = array();
                if(!empty($listStatisticsByTimeProcess)) {
                    foreach($listStatisticsByTimeProcess as $time => $oneStatisticByTime) {
                        if($time >= $oneColX['from_date'] && $time <= $oneColX['to_date']) {
                            $totalTime += $oneStatisticByTime;
                        }
                    }
                }
                $oneRecordReportProcess->y[] = $totalTime;
                $newChartDataProcess[] = $oneRecordReportProcess;
            }
        }
        $LStatistic->series = $ProcessStatistic->series = $ticks;
        $LStatistic->data = $newChartDataCase;

        $ReplyStatistic->series = ['Thời gian trả lời trung bình'];
        $ProcessStatistic->series = ['Thời gian xử lý trung bình'];

        $ReplyStatistic->data = $newChartDataReply;
        $ProcessStatistic->data = $newChartDataProcess;


        $statisticOverTime = $this->getStatisticOverTime($FromDate, $ToDate, $CaseID, $TypeID, $FromUserID, $ToUserID);
        $numberOfTicketInOverTime = 100;
        $numberOfTicketOutOfOverTime = 0;

        if(!$statisticOverTime->isEmpty()) {
            foreach($statisticOverTime as $oneStatisticOverTime) {
                if($oneStatisticOverTime->out_of_date == 0) {
                    $numberOfTicketInOverTime = $oneStatisticOverTime->total;
                } else {
                    $numberOfTicketOutOfOverTime = $oneStatisticOverTime->total;
                }
            }
        }
        $ticketInOverTimePercent  = round(($numberOfTicketInOverTime/($numberOfTicketInOverTime + $numberOfTicketOutOfOverTime)*100),2);
        $ticketOutOfOverTimePercent = 100 - $ticketInOverTimePercent;

        $OverStatistic = new \stdClass();
        $OverStatistic->series = ['Ticket đúng hạn', 'Ticket quá hạn'];
        $ticketInOverTime = new \stdClass();
        $ticketInOverTime->x = "Ticket đúng hạn";
        $ticketInOverTime->y = [$ticketInOverTimePercent];

        $ticketOutOfOverTime = new \stdClass();
        $ticketOutOfOverTime->x = "Ticket quá hạn";
        $ticketOutOfOverTime->y = [$ticketOutOfOverTimePercent];
        $OverStatistic->data[0] = $ticketInOverTime;
        $OverStatistic->data[1] = $ticketOutOfOverTime;
        $chart = [
            'case'  =>  $LStatistic,
            'reply' =>  $ReplyStatistic,
            'process'   =>  $ProcessStatistic,
            'over'      =>  $OverStatistic
        ];
        $Response = [
            'error'     =>  false,
            'data'      =>  $chart
        ];

        return Response::json($Response);
    }

    private function getStatisticByCases($FromDate, $ToDate, $CaseID, $TypeID, $FromUserID, $ToUserID) {
        $statisticModel = new StatisticModel;
        if($FromDate==0) {
            $FromDate = 30*86400;
        }
        $statisticModel = $statisticModel->where('time_create','>=',$FromDate);
        if($ToDate>0) {
            $statisticModel = $statisticModel->where('time_create','<=',$ToDate);
        }
        if($CaseID>0) {
            $statisticModel = $statisticModel->where('case_id',$CaseID);
        }
        if($TypeID>0) {
            $statisticModel = $statisticModel->where('type_id',$TypeID);
        }
        if($FromUserID>0) {
            $statisticModel = $statisticModel->where('user_id',$FromUserID);
        }
        if($ToUserID>0) {
            $statisticModel = $statisticModel->where('user_id',$ToUserID);
        }
        $groupBy = "case_id";
        if($CaseID > 0) {
            $groupBy = "type_id";
        }
        $statisticModel = $statisticModel->groupBy([
            DB::raw('DATE(FROM_UNIXTIME(`time_create`))'),
            $groupBy
        ]);
        $statisticModel = $statisticModel->select([
            DB::raw('DATE(FROM_UNIXTIME(`time_create`)) as time'),
            DB::raw('COUNT(DISTINCT ticket_id) as total'),
            $groupBy
        ]);

        return $ListStatistics = $statisticModel->get();
    }

    private function getStatisticReply($FromDate, $ToDate, $CaseID, $TypeID, $FromUserID, $ToUserID) {
        $statisticModel = new StatisticModel;
        if($FromDate==0) {
            $FromDate = 30*86400;
        }
        $statisticModel = $statisticModel->where('time_create','>=',$FromDate);
        if($ToDate>0) {
            $statisticModel = $statisticModel->where('time_create','<=',$ToDate);
        }
        if($CaseID>0) {
            $statisticModel = $statisticModel->where('case_id',$CaseID);
        }
        if($TypeID>0) {
            $statisticModel = $statisticModel->where('type_id',$TypeID);
        }
        if($FromUserID>0) {
            $statisticModel = $statisticModel->where('user_id',$FromUserID);
        }
        if($ToUserID>0) {
            $statisticModel = $statisticModel->where('user_id',$ToUserID);
        }
        $statisticModel = $statisticModel->groupBy([
            DB::raw('DATE(FROM_UNIXTIME(`time_create`))')
        ]);
        $statisticModel = $statisticModel->select([
            DB::raw('DATE(FROM_UNIXTIME(`time_create`)) as time'),
            DB::raw('AVG(time_reply) as per_time_reply')
        ]);

        return $ListStatistics = $statisticModel->get();
    }

    private function getStatisticProcess($FromDate, $ToDate, $CaseID, $TypeID, $FromUserID, $ToUserID) {
        $statisticModel = new StatisticModel;
        if($FromDate==0) {
            $FromDate = 30*86400;
        }
        $statisticModel = $statisticModel->where('time_create','>=',$FromDate);
        if($ToDate>0) {
            $statisticModel = $statisticModel->where('time_create','<=',$ToDate);
        }
        if($CaseID>0) {
            $statisticModel = $statisticModel->where('case_id',$CaseID);
        }
        if($TypeID>0) {
            $statisticModel = $statisticModel->where('type_id',$TypeID);
        }
        if($FromUserID>0) {
            $statisticModel = $statisticModel->where('user_id',$FromUserID);
        }
        if($ToUserID>0) {
            $statisticModel = $statisticModel->where('user_id',$ToUserID);
        }
        $statisticModel = $statisticModel->groupBy([
            DB::raw('DATE(FROM_UNIXTIME(`time_create`))')
        ]);
        $statisticModel = $statisticModel->select([
            DB::raw('DATE(FROM_UNIXTIME(`time_create`)) as time'),
            DB::raw('AVG(time_process) as per_time_process')
        ]);

        return $ListStatistics = $statisticModel->get();
    }

    private function getStatisticOverTime($FromDate, $ToDate, $CaseID, $TypeID, $FromUserID, $ToUserID) {
        $statisticModel = new StatisticModel;
        if($FromDate==0) {
            $FromDate = 30*86400;
        }
        $statisticModel = $statisticModel->where('time_create','>=',$FromDate);
        if($ToDate>0) {
            $statisticModel = $statisticModel->where('time_create','<=',$ToDate);
        }
        if($CaseID>0) {
            $statisticModel = $statisticModel->where('case_id',$CaseID);
        }
        if($TypeID>0) {
            $statisticModel = $statisticModel->where('type_id',$TypeID);
        }
        if($FromUserID>0) {
            $statisticModel = $statisticModel->where('user_id',$FromUserID);
        }
        if($ToUserID>0) {
            $statisticModel = $statisticModel->where('user_id',$ToUserID);
        }

        $statisticModel = $statisticModel->where('time_over','>=',0);
        $statisticModel = $statisticModel->groupBy('out_of_date');
        $totalTicket = $ListStatistics = $statisticModel->get([
            'out_of_date',
            DB::raw('COUNT(*) as total')
        ]);
        return $totalTicket;
    }

}