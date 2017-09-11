<?php namespace api;

use ticketmodel\CaseTypeModel;
use ticketmodel\RequestModel;
use ticketmodel\CaseTicketModel;
use ticketmodel\StatisticModel;
use ticketmodel\AssignModel;
use ticketmodel\FeedbackModel;


class SyncReportCtrl extends \BaseController {

    //sync data status = CLOSED
    public function getIndex() {

        $LMongo = new \LMongo\Facades\LMongo;
        $timeStart = $this->time() - 90*86400;
        $Tickets = RequestModel::where('status','CLOSED')->where('has_log',0)->where('time_create','>=',$timeStart)->take(10)->get();
        $StatisticsInsert = array();
        $num = 0;


        if($Tickets->isEmpty()) {
            return "Hien da het ticket dong de cap nhat";
        }
        //list ticket
        $ListTickets = array();

        if(!$Tickets->isEmpty()) {
            foreach($Tickets as $OneTicket) {
                $ListTickets[] = $OneTicket->id;
            }
        }

        //check ticket in statistic is exist
        $ListTicketsInStatistic = StatisticModel::whereIn('ticket_id',$ListTickets)->where('time_create','>=',$timeStart)->lists('ticket_id');

        //get List Case Types
        $CaseTypes = CaseTypeModel::all();
        $ListCaseType = [];

        if(!$CaseTypes->isEmpty()) {
            foreach($CaseTypes as $OneCaseType) {
                $ListCaseType[$OneCaseType->id] = $OneCaseType->case_id;
            }
        }


        $TypeTickets = CaseTicketModel::whereIn('ticket_id',$ListTickets)->where('active',1)->select(['ticket_id','type_id'])->get();
        $ListTypes = [];

        if(!$TypeTickets->isEmpty()) {
            foreach($TypeTickets as $OneTypeTicket) {
                $ListTypes[$OneTypeTicket->ticket_id]['type_id'] = $OneTypeTicket->type_id;
                $ListTypes[$OneTypeTicket->ticket_id]['case_id'] = isset($ListCaseType[$OneTypeTicket->type_id]) ? $ListCaseType[$OneTypeTicket->type_id] : 0;
            }
        }
        //get List Ticket Assign
        $TicketAssign = AssignModel::whereIn('ticket_id',$ListTickets)->orderBy('time_create')->get();
        $ListTicketAssign = [];

        if(!$TicketAssign->isEmpty()) {
            foreach($TicketAssign as $OneTicketAssign) {
                $ListTicketAssign[$OneTicketAssign->ticket_id][] = $OneTicketAssign;
            }
        }

        //get List Feedback
        $Feedbacks = FeedbackModel::select(['user_id','ticket_id','time_create'])->whereIn('ticket_id',$ListTickets)->orderBy('time_create')->groupBy(['ticket_id','user_id'])->get();
        $ListFeedbacks = [];

        if(!$Feedbacks->isEmpty()) {
            foreach($Feedbacks as $OneFeedback) {
                $ListFeedbacks[$OneFeedback->ticket_id][$OneFeedback->user_id] = $OneFeedback->time_create;
            }
        }

        if(!$Tickets->isEmpty()) {
            $k = $j = 0;
            foreach($Tickets as $OneTicket) {
                $timeCreate = $OneTicket->time_create;
                $LogChangeTicket = $LMongo::collection('log_change_ticket')->where('id', $OneTicket->id)->where('type','status')->get();
                $ArrLogChangeTicket = (array)$LogChangeTicket;
                
                $TimeProcess = 0;
                $lastUserID = 0;
                $lastUserAssignID = 0;
                $lastTimeAssign = 0;
                $hasUserAssign = false;
                if(isset($ArrLogChangeTicket[0])) {
                    foreach($LogChangeTicket as $OneLogChangeTicket) {
                        if($OneLogChangeTicket['new']['status']=='PROCESSED') {
                            $TimeProcess = $OneLogChangeTicket['time_create'] - $timeCreate;
                        }
                        if((isset($OneLogChangeTicket['new']['active'])) && $OneLogChangeTicket['new']['active'] == 1 && $OneLogChangeTicket['new']['status'] != 'CLOSED') {
                            $lastUserAssignID = $OneLogChangeTicket['new']['assign_id'];
                            $lastUserID = $OneLogChangeTicket['user_id'];
                            $lastTimeAssign = $OneLogChangeTicket['time_create'];
                        }
                    }
                }
                if(isset($ListTicketAssign[$OneTicket->id]) && !empty($ListTicketAssign[$OneTicket->id])) {
                    $listAssignRemoved = [];
                    foreach($ListTicketAssign[$OneTicket->id] as $OneTicketAssign) {
                        if($OneTicketAssign->active == 0) {
                            $listAssignRemoved[] = $OneTicketAssign->assign_id;
                        }
                    }
                    if(!empty($listAssignRemoved)) {
                        StatisticModel::where('ticket_id',$OneTicket->id)->whereIn('assign_id',$listAssignRemoved)->delete();
                    }
                    foreach($ListTicketAssign[$OneTicket->id] as $OneTicketAssign) {
                        if($OneTicketAssign->active == 1) {
                            $hasUserAssign = true;
                            if(!in_array($OneTicket->id,$ListTicketsInStatistic)) {
                                $StatisticsInsert[$k]['ticket_id']        =   $OneTicket->id;
                                $StatisticsInsert[$k]['case_id']          =   isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['case_id'] : 0;
                                $StatisticsInsert[$k]['type_id']          =   isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['type_id'] : 0;
                                $StatisticsInsert[$k]['user_create_id']   =   $OneTicket->user_id;
                                $StatisticsInsert[$k]['status']           =   'CLOSED';
                                $StatisticsInsert[$k]['time_create']      =   $OneTicket->time_create;
                                $StatisticsInsert[$k]['time_reply']       =   $OneTicket->time_reply;
                                $StatisticsInsert[$k]['time_process']     =   $TimeProcess;
                                $StatisticsInsert[$k]['time_update']      =   $OneTicket->time_update;
                                $StatisticsInsert[$k]['time_close']       =   $OneTicket->time_update - $OneTicket->time_create;

                                $StatisticsInsert[$k]['user_id']          =   $OneTicketAssign->user_id;
                                $StatisticsInsert[$k]['assign_id']        =   $OneTicketAssign->assign_id;
                                $StatisticsInsert[$k]['time_assign']      =   $OneTicketAssign->time_create;
                                $StatisticsInsert[$k]['time_over']        =   $OneTicket->time_over;
                                $StatisticsInsert[$k]['out_of_date']        =   ($OneTicket->time_over > 0 && $OneTicket->time_over-$OneTicket->time_update > 0) ? 0 : 1;
                                $StatisticsInsert[$k]['process']          =   isset($ListFeedbacks[$OneTicket->id][$OneTicketAssign->assign_id]) ? 1 : 0;
                                ++$k;
                            } else {
                                //check this ticket assign exist
                                $StatisticsUpdate = StatisticModel::where('ticket_id',$OneTicket->id)
                                    ->where('assign_id',$OneTicketAssign->assign_id)->first();
                                if(!empty($StatisticsUpdate)) {
                                    $StatisticsUpdate->case_id = isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['case_id'] : 0;
                                    $StatisticsUpdate->type_id = isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['type_id'] : 0;
                                    $StatisticsUpdate->user_create_id = $OneTicket->user_id;
                                    $StatisticsUpdate->status = 'CLOSED';
                                    $StatisticsUpdate->user_id = $OneTicketAssign->user_id;
                                    $StatisticsUpdate->assign_id    =   $OneTicketAssign->assign_id;
                                    $StatisticsUpdate->time_assign  =   $OneTicketAssign->time_create;
                                    $StatisticsUpdate->time_process  =   $TimeProcess;
                                    $StatisticsUpdate->time_close  =   $OneTicket->time_update - $OneTicket->time_create;
                                    $StatisticsUpdate->time_update  =   $OneTicket->time_update;
                                    $StatisticsUpdate->time_over        =   $OneTicket->time_over;
                                    $StatisticsUpdate->out_of_date        =   ($OneTicket->time_over > 0 && $OneTicket->time_over-$OneTicket->time_update > 0) ? 0 : 1;
                                    $StatisticsUpdate->process          =   isset($ListFeedbacks[$OneTicket->id][$OneTicketAssign->assign_id]) ? 1 : 0;
                                    $StatisticsUpdate->save();
                                } else {
                                    $StatisticsInsert[$k]['ticket_id']        =   $OneTicket->id;
                                    $StatisticsInsert[$k]['case_id']          =   isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['case_id'] : 0;
                                    $StatisticsInsert[$k]['type_id']          =   isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['type_id'] : 0;
                                    $StatisticsInsert[$k]['user_create_id']   =   $OneTicket->user_id;
                                    $StatisticsInsert[$k]['status']           =   'CLOSED';
                                    $StatisticsInsert[$k]['time_create']      =   $OneTicket->time_create;
                                    $StatisticsInsert[$k]['time_reply']       =   $OneTicket->time_reply;
                                    $StatisticsInsert[$k]['time_process']     =   $TimeProcess;
                                    $StatisticsInsert[$k]['time_update']      =   $OneTicket->time_update;
                                    $StatisticsInsert[$k]['time_close']       =   $OneTicket->time_update - $OneTicket->time_create;

                                    $StatisticsInsert[$k]['user_id']          =   $OneTicketAssign->user_id;
                                    $StatisticsInsert[$k]['assign_id']        =   $OneTicketAssign->assign_id;
                                    $StatisticsInsert[$k]['time_assign']      =   $OneTicketAssign->time_create;
                                    $StatisticsInsert[$k]['time_over']        =   $OneTicket->time_over;
                                    $StatisticsInsert[$k]['out_of_date']        =   ($OneTicket->time_over > 0 && $OneTicket->time_over-$OneTicket->time_update > 0) ? 0 : 1;
                                    $StatisticsInsert[$k]['process']          =   isset($ListFeedbacks[$OneTicket->id][$OneTicketAssign->assign_id]) ? 1 : 0;
                                    ++$k;
                                }


                            }

                            ++$num;
                        }
                    }
                }
                if(!$hasUserAssign) {
                    if(!in_array($OneTicket->id,$ListTicketsInStatistic)) {
                        $StatisticsInsert[$k]['ticket_id']        =   $OneTicket->id;
                        $StatisticsInsert[$k]['case_id']          =   isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['case_id'] : 0;
                        $StatisticsInsert[$k]['type_id']          =   isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['type_id'] : 0;
                        $StatisticsInsert[$k]['user_create_id']   =   $OneTicket->user_id;
                        $StatisticsInsert[$k]['status']           =   'CLOSED';
                        $StatisticsInsert[$k]['time_create']      =   $OneTicket->time_create;
                        $StatisticsInsert[$k]['time_reply']       =   $OneTicket->time_reply;
                        $StatisticsInsert[$k]['time_process']     =   $TimeProcess;
                        $StatisticsInsert[$k]['time_update']      =   $OneTicket->time_update;
                        $StatisticsInsert[$k]['time_close']       =   $OneTicket->time_update - $OneTicket->time_create;

                        $StatisticsInsert[$k]['user_id']          =   $lastUserID;
                        $StatisticsInsert[$k]['assign_id']        =   $lastUserAssignID;
                        $StatisticsInsert[$k]['time_assign']      =   $lastTimeAssign;
                        $StatisticsInsert[$k]['time_over']        =   $OneTicket->time_over;
                        $StatisticsInsert[$k]['out_of_date']        =   ($OneTicket->time_over > 0 && $OneTicket->time_over-$OneTicket->time_update > 0) ? 0 : 1;
                        $StatisticsInsert[$k]['process']          =   0;
                        ++$k;
                    } else {
                        //check this ticket assign exist
                        $StatisticsUpdate = StatisticModel::where('ticket_id',$OneTicket->id)
                            ->where('assign_id',$lastUserAssignID)->first();
                        if(!empty($StatisticsUpdate)) {
                            $StatisticsUpdate->case_id = isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['case_id'] : 0;
                            $StatisticsUpdate->type_id = isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['type_id'] : 0;
                            $StatisticsUpdate->user_create_id = $OneTicket->user_id;
                            $StatisticsUpdate->status = 'CLOSED';
                            $StatisticsUpdate->user_id = $lastUserID;
                            $StatisticsUpdate->assign_id    =   $lastUserAssignID;
                            $StatisticsUpdate->time_assign  =   $lastTimeAssign;
                            $StatisticsUpdate->time_process  =   $TimeProcess;
                            $StatisticsUpdate->time_close  =   $OneTicket->time_update - $OneTicket->time_create;
                            $StatisticsUpdate->time_update  =   $OneTicket->time_update;
                            $StatisticsUpdate->time_over        =   $OneTicket->time_over;
                            $StatisticsUpdate->out_of_date        =   ($OneTicket->time_over > 0 && $OneTicket->time_over-$OneTicket->time_update > 0) ? 0 : 1;
                            $StatisticsUpdate->process          =   0;
                            $StatisticsUpdate->save();
                        } else {
                            $StatisticsInsert[$k]['ticket_id']        =   $OneTicket->id;
                            $StatisticsInsert[$k]['case_id']          =   isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['case_id'] : 0;
                            $StatisticsInsert[$k]['type_id']          =   isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['type_id'] : 0;
                            $StatisticsInsert[$k]['user_create_id']   =   $OneTicket->user_id;
                            $StatisticsInsert[$k]['status']           =   'CLOSED';
                            $StatisticsInsert[$k]['time_create']      =   $OneTicket->time_create;
                            $StatisticsInsert[$k]['time_reply']       =   $OneTicket->time_reply;
                            $StatisticsInsert[$k]['time_process']     =   $TimeProcess;
                            $StatisticsInsert[$k]['time_update']      =   $OneTicket->time_update;
                            $StatisticsInsert[$k]['time_close']       =   $OneTicket->time_update - $OneTicket->time_create;

                            $StatisticsInsert[$k]['user_id']          =   $lastUserID;
                            $StatisticsInsert[$k]['assign_id']        =   $lastUserAssignID;
                            $StatisticsInsert[$k]['time_assign']      =   $lastTimeAssign;
                            $StatisticsInsert[$k]['time_over']        =   $OneTicket->time_over;
                            $StatisticsInsert[$k]['out_of_date']      =   ($OneTicket->time_over > 0 && $OneTicket->time_over-$OneTicket->time_update > 0) ? 0 : 1;
                            $StatisticsInsert[$k]['process']          =   0;
                            ++$k;
                        }


                    }

                    ++$num;
                }
            }
        }


        if(!empty($StatisticsInsert)) {
            //write log statistic
            StatisticModel::insert($StatisticsInsert);
        }
        RequestModel::whereIn('id',$ListTickets)->update(['has_log'=>1]);
        return $num . " rows duoc cap nhat tu ".count($ListTickets). " tickets";

    }


    public function getSyncOtherTicket() {
        $timeStart = $this->time() - 90*86400;

        $LMongo = new \LMongo\Facades\LMongo;
        $Tickets = RequestModel::where('status','!=','NEW_ISSUE')->where('status','!=','CLOSED')->where('time_create','>=',$timeStart)->orderBy('time_sync')->take(10)->get();

        $StatisticsInsert = array();
        $num = 0;

        if($Tickets->isEmpty()) {
            return "Hien da het ticket de cap nhat";
        }
        //list ticket
        $ListTickets = array();

        if(!$Tickets->isEmpty()) {
            foreach($Tickets as $OneTicket) {
                $ListTickets[] = $OneTicket->id;
            }
        }

        //check ticket in statistic is exist
        $ListTicketsInStatistic = StatisticModel::whereIn('ticket_id',$ListTickets)->where('time_create','>=',$timeStart)->lists('ticket_id');


        //get List Case Types
        $CaseTypes = CaseTypeModel::all();
        $ListCaseType = [];

        if(!$CaseTypes->isEmpty()) {
            foreach($CaseTypes as $OneCaseType) {
                $ListCaseType[$OneCaseType->id] = $OneCaseType->case_id;
            }
        }

        $TypeTickets = CaseTicketModel::whereIn('ticket_id',$ListTickets)->where('active',1)->select(['ticket_id','type_id'])->get();
        $ListTypes = [];
        if(!$TypeTickets->isEmpty()) {
            foreach($TypeTickets as $OneTypeTicket) {
                $ListTypes[$OneTypeTicket->ticket_id]['type_id'] = $OneTypeTicket->type_id;
                $ListTypes[$OneTypeTicket->ticket_id]['case_id'] = isset($ListCaseType[$OneTypeTicket->type_id]) ? $ListCaseType[$OneTypeTicket->type_id] : 0;
            }
        }
        //get List Ticket Assign
        $TicketAssign = AssignModel::whereIn('ticket_id',$ListTickets)->orderBy('time_create')->get();
        $ListTicketAssign = [];

        if(!$TicketAssign->isEmpty()) {
            foreach($TicketAssign as $OneTicketAssign) {
                $ListTicketAssign[$OneTicketAssign->ticket_id][] = $OneTicketAssign;
            }
        }

        //get List Feedback
        $Feedbacks = FeedbackModel::select(['user_id','ticket_id','time_create'])->whereIn('ticket_id',$ListTickets)->orderBy('time_create')->groupBy(['ticket_id','user_id'])->get();
        $ListFeedbacks = [];

        if(!$Feedbacks->isEmpty()) {
            foreach($Feedbacks as $OneFeedback) {
                $ListFeedbacks[$OneFeedback->ticket_id][$OneFeedback->user_id] = $OneFeedback->time_create;
            }
        }

        if(!$Tickets->isEmpty()) {
            $k = 0;
            foreach($Tickets as $OneTicket) {
                $timeCreate = $OneTicket->time_create;
                $LogChangeTicket = $LMongo::collection('log_change_ticket')->where('id', $OneTicket->id)->where('type','status')->get();
                $ArrLogChangeTicket = (array)$LogChangeTicket;
                $TimeProcess = 0;
                $lastUserID = 0;
                $lastUserAssignID = 0;
                $lastTimeAssign = 0;
                $hasUserAssign = false;
                if(isset($ArrLogChangeTicket[0])) {
                    foreach($LogChangeTicket as $OneLogChangeTicket) {
                        if($OneLogChangeTicket['new']['status']=='PROCESSED') {
                            $TimeProcess = $OneLogChangeTicket['time_create'] - $timeCreate;
                        }
                        if(isset($OneLogChangeTicket['new']['active']) && $OneLogChangeTicket['new']['active'] == 1 && $OneLogChangeTicket['new']['status'] != 'CLOSED') {
                            $lastUserAssignID = $OneLogChangeTicket['new']['assign_id'];
                            $lastUserID = $OneLogChangeTicket['user_id'];
                            $lastTimeAssign = $OneLogChangeTicket['time_create'];
                        }
                    }
                }
                if(isset($ListTicketAssign[$OneTicket->id]) && !empty($ListTicketAssign[$OneTicket->id])) {
                    $listAssignRemoved = [];
                    foreach($ListTicketAssign[$OneTicket->id] as $OneTicketAssign) {
                        if($OneTicketAssign->active == 0) {
                            $listAssignRemoved[] = $OneTicketAssign->assign_id;
                        }
                    }
                    if(!empty($listAssignRemoved)) {
                        StatisticModel::where('ticket_id',$OneTicket->id)->whereIn('assign_id',$listAssignRemoved)->delete();
                    }
                    foreach($ListTicketAssign[$OneTicket->id] as $OneTicketAssign) {
                        if($OneTicketAssign->active == 1) {
                            $hasUserAssign = true;
                            if(!in_array($OneTicket->id,$ListTicketsInStatistic)) {
                                $StatisticsInsert[$k]['ticket_id'] = $OneTicket->id;
                                $StatisticsInsert[$k]['case_id'] = isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['case_id'] : 0;
                                $StatisticsInsert[$k]['type_id'] = isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['type_id'] : 0;
                                $StatisticsInsert[$k]['user_create_id'] = $OneTicket->user_id;
                                $StatisticsInsert[$k]['status'] = $OneTicket->status;
                                $StatisticsInsert[$k]['time_create'] = $OneTicket->time_create;
                                $StatisticsInsert[$k]['time_reply'] = $OneTicket->time_reply;
                                $StatisticsInsert[$k]['time_update'] = $OneTicket->time_update;
                                $StatisticsInsert[$k]['time_process']   =   $TimeProcess;
                                $StatisticsInsert[$k]['time_close']   =   $OneTicket->time_update - $OneTicket->time_create;

                                $StatisticsInsert[$k]['user_id'] = $OneTicketAssign->user_id;
                                $StatisticsInsert[$k]['assign_id'] = $OneTicketAssign->assign_id;
                                $StatisticsInsert[$k]['time_assign'] = $OneTicketAssign->time_create;
                                $StatisticsInsert[$k]['process']          =   isset($ListFeedbacks[$OneTicket->id][$OneTicketAssign->assign_id]) ? 1 : 0;
                                ++$k;
                            } else {
                                //check this ticket assign exist
                                $StatisticsUpdate = StatisticModel::where('ticket_id',$OneTicket->id)
                                    ->where('assign_id',$OneTicketAssign->assign_id)->first();
                                if(!empty($StatisticsUpdate)) {
                                    $StatisticsUpdate->case_id = isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['case_id'] : 0;
                                    $StatisticsUpdate->type_id = isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['type_id'] : 0;
                                    $StatisticsUpdate->user_create_id = $OneTicket->user_id;
                                    $StatisticsUpdate->status = $OneTicket->status;
                                    $StatisticsUpdate->time_reply   =   $OneTicket->time_reply;
                                    $StatisticsUpdate->user_id = $OneTicketAssign->user_id;
                                    $StatisticsUpdate->assign_id    =   $OneTicketAssign->assign_id;
                                    $StatisticsUpdate->time_assign  =   $OneTicketAssign->time_create;
                                    $StatisticsUpdate->time_process  =   $TimeProcess;

                                    $StatisticsUpdate->time_update  =   $OneTicket->time_update;
                                    $StatisticsUpdate->time_close   =   $OneTicket->time_update - $OneTicket->time_create;
                                    $StatisticsUpdate->process      =   isset($ListFeedbacks[$OneTicket->id][$OneTicketAssign->assign_id]) ? 1 : 0;
                                    $StatisticsUpdate->save();
                                } else {
                                    $StatisticsInsert[$k]['ticket_id']        =   $OneTicket->id;
                                    $StatisticsInsert[$k]['case_id']          =   isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['case_id'] : 0;
                                    $StatisticsInsert[$k]['type_id']          =   isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['type_id'] : 0;
                                    $StatisticsInsert[$k]['user_create_id']   =   $OneTicket->user_id;
                                    $StatisticsInsert[$k]['status']           =   $OneTicket->status;
                                    $StatisticsInsert[$k]['time_create']      =   $OneTicket->time_create;
                                    $StatisticsInsert[$k]['time_reply']       =   $OneTicket->time_reply;
                                    $StatisticsInsert[$k]['time_process']     =   $TimeProcess;
                                    $StatisticsInsert[$k]['time_update']      =   $OneTicket->time_update;
                                    $StatisticsInsert[$k]['time_close']       =   $OneTicket->time_update - $OneTicket->time_create;

                                    $StatisticsInsert[$k]['user_id']          =   $OneTicketAssign->user_id;
                                    $StatisticsInsert[$k]['assign_id']        =   $OneTicketAssign->assign_id;
                                    $StatisticsInsert[$k]['time_assign']      =   $OneTicketAssign->time_create;
                                    $StatisticsInsert[$k]['process']          =   isset($ListFeedbacks[$OneTicket->id][$OneTicketAssign->assign_id]) ? 1 : 0;
                                    ++$k;
                                }

                            }
                            ++$num;
                        }
                    }
                }
                if(!$hasUserAssign) {
                    if(!in_array($OneTicket->id,$ListTicketsInStatistic)) {
                        $StatisticsInsert[$k]['ticket_id']        =   $OneTicket->id;
                        $StatisticsInsert[$k]['case_id']          =   isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['case_id'] : 0;
                        $StatisticsInsert[$k]['type_id']          =   isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['type_id'] : 0;
                        $StatisticsInsert[$k]['user_create_id']   =   $OneTicket->user_id;
                        $StatisticsInsert[$k]['status']           =   $OneTicket->status;
                        $StatisticsInsert[$k]['time_create']      =   $OneTicket->time_create;
                        $StatisticsInsert[$k]['time_reply']       =   $OneTicket->time_reply;
                        $StatisticsInsert[$k]['time_process']     =   $TimeProcess;
                        $StatisticsInsert[$k]['time_update']      =   $OneTicket->time_update;
                        $StatisticsInsert[$k]['time_close']       =   $OneTicket->time_update - $OneTicket->time_create;

                        $StatisticsInsert[$k]['user_id']          =   $lastUserID;
                        $StatisticsInsert[$k]['assign_id']        =   $lastUserAssignID;
                        $StatisticsInsert[$k]['time_assign']      =   $lastTimeAssign;
                        $StatisticsInsert[$k]['process']          =   0;
                        ++$k;
                    } else {
                        //check this ticket assign exist
                        $StatisticsUpdate = StatisticModel::where('ticket_id',$OneTicket->id)
                            ->where('assign_id',$lastUserAssignID)->first();
                        if(!empty($StatisticsUpdate)) {
                            $StatisticsUpdate->case_id = isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['case_id'] : 0;
                            $StatisticsUpdate->type_id = isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['type_id'] : 0;
                            $StatisticsUpdate->user_create_id = $OneTicket->user_id;
                            $StatisticsUpdate->status = $OneTicket->status;
                            $StatisticsUpdate->user_id = $lastUserID;
                            $StatisticsUpdate->assign_id    =   $lastUserAssignID;
                            $StatisticsUpdate->time_assign  =   $lastTimeAssign;
                            $StatisticsUpdate->time_process  =   $TimeProcess;
                            $StatisticsUpdate->time_close  =   $OneTicket->time_update - $OneTicket->time_create;
                            $StatisticsUpdate->time_update  =   $OneTicket->time_update;
                            $StatisticsInsert[$k]['process']          =   0;
                            $StatisticsUpdate->save();
                        } else {
                            $StatisticsInsert[$k]['ticket_id']        =   $OneTicket->id;
                            $StatisticsInsert[$k]['case_id']          =   isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['case_id'] : 0;
                            $StatisticsInsert[$k]['type_id']          =   isset($ListTypes[$OneTicket->id]) ? $ListTypes[$OneTicket->id]['type_id'] : 0;
                            $StatisticsInsert[$k]['user_create_id']   =   $OneTicket->user_id;
                            $StatisticsInsert[$k]['status']           =   $OneTicket->status;
                            $StatisticsInsert[$k]['time_create']      =   $OneTicket->time_create;
                            $StatisticsInsert[$k]['time_reply']       =   $OneTicket->time_reply;
                            $StatisticsInsert[$k]['time_process']     =   $TimeProcess;
                            $StatisticsInsert[$k]['time_update']      =   $OneTicket->time_update;
                            $StatisticsInsert[$k]['time_close']       =   $OneTicket->time_update - $OneTicket->time_create;

                            $StatisticsInsert[$k]['user_id']          =   $lastUserID;
                            $StatisticsInsert[$k]['assign_id']        =   $lastUserAssignID;
                            $StatisticsInsert[$k]['time_assign']      =   $lastTimeAssign;
                            $StatisticsInsert[$k]['process']          =   0;
                            ++$k;
                        }


                    }

                    ++$num;
                }
            }
        }

        if(!empty($StatisticsInsert)) {
            //write log statistic
            StatisticModel::insert($StatisticsInsert);
        }

        $currentTime = $this->time();
        RequestModel::whereIn('id',$ListTickets)->update(array('time_sync'=>$currentTime));
        return $num . " rows duoc cap nhat tu ".count($ListTickets). " tickets";
    }
}