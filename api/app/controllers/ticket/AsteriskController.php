<?php
namespace ticket;

use Response;
use Exception;
use Input;
use Validator;
use Excel;
use DB;
use LMongo;

use User;
use AsteriskCDRModel;

use ordermodel\OrdersModel;
use omsmodel\PipeJourneyModel;
use sellermodel\UserInventoryModel;
use ticketmodel\RequestModel;
use ticketmodel\AssignModel;


class AsteriskController extends \BaseController
{


    public function __construct()
    {
    }

    public function getSyncCdr(){
        $CallReportModel = new \ticketmodel\CallReportModel;
        $CdrModel        = new AsteriskCDRModel;
        $CdrData         = $CdrModel->where("lastapp", "Dial")->where('sync', 2)->take(50)->get();

        if($CdrData->isEmpty()){
            return "SYNC DONE";
        }

        $InsertData = [];
        $ListCdrId  = [];
        foreach ($CdrData as $key => $value) {
            $ListCdrId[]  = $value->uniqueid;
            $InsertData[] = [
                "src"           => $value->cnum,
                "dst"           => $value->dst,
                "disposition"   => $value->disposition,
                "direction"     => strlen($value->cnum) > 8 ? "incoming" : "outgoing",
                "billsec"       => $value->billsec,
                "duration"      => $value->duration,
                "recordingfile"   => $value->recordingfile,
                "time_call"     => explode(".", $value->uniqueid)[0],
                "time_create"   => $this->time()
            ];
        }
        try {
            $CallReportModel->insert($InsertData);
            $CdrModel->whereIn('uniqueid', $ListCdrId)->update(["sync" => 1]);
        } catch (Exception $e) {
            
        }
        return 'NEXT';

    }

    private function getModel(){
        $StartDate = Input::has('startDate') ? (int)Input::get('startDate') : "";
        $EndDate   = Input::has('endDate')   ? (int)Input::get('endDate')   : "";

        $CallReportModel = new \ticketmodel\CallReportModel;

        if(!empty($StartDate)){
            $CallReportModel = $CallReportModel->where('time_call', '>=', $StartDate);
        }else {
            $CallReportModel = $CallReportModel->where('time_call', '>=', strtotime(date('Y-m-1 00:00:00')));
        }

        if(!empty($EndDate)){
            $CallReportModel = $CallReportModel->where('time_call', '<', $EndDate);
        }
        return $CallReportModel;
    }
    public function getCdr()
    {
        $Phone      = Input::has('phone')       ? Input::get('phone') : '';
        $Src        = Input::has('src')         ? Input::get('src') : '';
        $Dst        = Input::has('dst')         ? Input::get('dst') : '';
        $StartDate  = Input::has('start_date')  ? Input::get('start_date') : 0;
        $EndDate    = Input::has('end_date')    ? Input::get('end_date') : 0;
        $ItemPage   = Input::has('item_page')   ? (int)Input::get('item_page') : 20;
        $Page       = Input::has('page')        ? (int)Input::get('page') : 1;
        $Cmd        = Input::has('cmd')         ? Input::get('cmd') : '';
        $offset     = ($Page - 1) * $ItemPage;


        $model = new \ticketmodel\CallReportModel;
        $model = $model->where('time_call', '>=', strtotime(date('Y-m-1 00:00:00')));
        //$model = $model->where("lastapp", "Dial");

        if (!empty($Phone)) {
            $model = $model->where('src', $Phone)->orWhere('dst', $Phone);
        }

        if (!empty($Src)) {
            $model = $model->where('src', $Src);
        }

        if (!empty($Dst)) {
            $model = $model->where('dst', $Dst);
        }

        $Data = $model->skip($offset)->take($ItemPage)->orderBy('time_call', 'DESC')->get()->toArray();

        return Response::json([
            'error' => false,
            'error_message' => '',
            'data' => $Data
        ]);
    }


    public function getReportSystem(){
        $StartDate = Input::has('startDate') ? Input::get('startDate') : "";
        $EndDate = Input::has('endDate') ? Input::get('endDate') : "";

        $CallReportModel = $this->getModel();


        $CallReportModel = $CallReportModel
                            ->select(DB::raw('direction, count(*) as number_call,  SUM( billsec ) AS total_time, DATE(FROM_UNIXTIME(time_call)) as dates'))
                            ->groupBy('direction')
                            ->groupBy('dates')
                            ->orderBy('dates')
                            ->get()->toArray();


        $categories = [];
        foreach ($CallReportModel as $key => $value) {
            $categories[] = $value['dates'];
        }

        $Incoming  = [
            "name" => "Cuộc gọi đến",
            "data" => []
        ];

        $Outgoing = [
            "name" => "Cuộc gọi đi",
            "data" => []
        ];

        $categories = array_keys(array_flip($categories));
        
        foreach ($categories as $key => $value) {
            $Incoming['data'][] = 0;
            $Outgoing['data'][] = 0;
            foreach ($CallReportModel as $k => $v) {
                if($v['dates'] == $value){
                    if($v['direction'] == 'outgoing'){
                        $Outgoing['data'][$key] = $v['number_call'];
                    }else {
                        $Incoming['data'][$key] = $v['number_call'];
                    }
                }
            }
        }
        

        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => [
                'series'        => [$Incoming, $Outgoing],
                'categories'    => $categories,
                'data_table'    => $CallReportModel
            ]
        ]);

    }


    public function getReportOutgoingCs(){
        $StartDate = Input::has('startDate') ? Input::get('startDate') : "";
        $EndDate   = Input::has('endDate') ? Input::get('endDate') : "";

        

        $CallReportModel = $this->getModel();
        $ListCS          = \sellermodel\UserInfoModel::whereIn('group', [6, 15])->with(['user'])->get()->toArray();

        $ListCSId        = [];
        $ListCSName      = [];
        $xAsix           = [];
        $ListExtension   = [];

        foreach ($ListCS as $key => $value) {
            if(!empty($value['sip_account'])){
                $ListCSName[$value['sip_account']] = $value['user']['fullname'];
                $xAsix[] = $value['user']['fullname'];
                $ListExtension[] = $value['sip_account'];
            }
        }

        $CallReportModel = $CallReportModel
                            ->whereIn('src', $ListExtension)
                            ->select(DB::raw('src, count(*) as total'))
                            ->groupBy('src')
                            ->groupBy('direction')
                            ->get()->toArray();

        $Outgoing  = [
            "name" => "Cuộc gọi đi",
            "data" => []
        ];
        $DataTable = [];


         foreach ($ListExtension as $k => $ext) {
            $Outgoing['data'][$k] = 0;
            $DataTable[$k] = [
                'employee'      => $ListCSName[$ext],
                'outgoing'      => 0,
            ];
            foreach ($CallReportModel as $key => $value) {
                if($value['src'] == $ext){
                    $Outgoing['data'][$k]         = $value['total'];
                    $DataTable[$k]['outgoing']    =  $value['total'];
                }
            }
        }
        

        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => [
                'series'        => [$Outgoing],
                'categories'    => $xAsix,
                'data_table'    => $DataTable
            ]
        ]);

    }


    public function getReportIncoming(){
        $StartDate = Input::has('startDate') ? Input::get('startDate') : "";
        $EndDate = Input::has('endDate') ? Input::get('endDate') : "";

        $CallReportModel = $this->getModel();


        $CallReportModel = $CallReportModel
                            ->where('direction', 'incoming')
                            ->select(DB::raw('src, count(*) as total, sum(billsec) as total_time'))
                            ->groupBy('src')
                            ->orderBy('total', 'DESC')
                            /*->with(['user'])*/
                            ->take('10')
                            ->get()->toArray();

        $DataTable  = [];
        $Series     = [
            "name" => "Cuộc gọi đến",
            "data" => ""
        ];
        $Categories = [];
        $ListPhone  = [];
        $ListUser   = [];
        $User       = [];

        foreach ($CallReportModel as $key => $value) {
            $ListPhone[] = $value['src'];
        }
        if(!empty($ListPhone)){
            $User = User::whereIn('phone', $ListPhone)->orWhereIn('phone2', $ListPhone)->get()->toArray();
        }
        

        foreach ($User as $key => $value) {
            $ListUser[$value['phone']] = $value['fullname'];
            $ListUser[$value['phone2']] = $value['fullname'];
        }


        foreach ($CallReportModel as $key => $value) {
            if(!empty($ListUser[$value['src']])){
                $Categories[] = $ListUser[$value['src']];
            }else {
                $Categories[] = $value['src'];
            }
            $Series["data"][] = $value['total'];
            $DataTable[] = [
                "name"       => !empty($ListUser[$value['src']]) ? $ListUser[$value['src']]. " <".$value['src'].">" : "Ẩn danh <".$value['src'].">",
                "total"      => $value['total'],
                'total_time' => $value['total_time']
            ];
        }
        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => [
                'series'        => [$Series],
                'categories'    => $Categories,
                'data_table'    => $DataTable
            ]
        ]);

    }
    public function getExportExcel(){

        $CallReportModel = $this->getModel()->get();

        return Excel::create('Bao_cao_cuoc_goi', function($excel) use($CallReportModel){
            $excel->sheet('Sheet1', function($sheet) use($CallReportModel){
                
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách cuộc gọi'));
                // Set multiple column formats
                
                $sheet->setWidth(array(
                    'A'     =>  10, 'B' =>  30, 'C'     =>  30, 'D'     =>  60, 'E'     =>  30, 'F'     =>  30, 'G'     =>  50,'H'     =>  50,
                    'I'  => 50,'J'  => 30,'K'  => 30,'L'  => 30
                ));

                $sheet->row(3, array(
                    'STT', 'Số gọi', 'Số nhận', 'Trạng thái', 'Loại cuộc gọi', 'Tổng thời gian cuộc gọi (s)', 'Tổng thời gian nghe (s)', 'Tổng thời gian chờ (s)', 'Thời gian gọi'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });
                $sheet->setBorder('A3:K3', 'thin');
                $i = 1;

                $sheet->appendRow(['']);


                foreach ($CallReportModel as $val) {
                    $dataExport = array(
                        $i++,
                        $val['src'],
                        $val['dst'],
                        $val['disposition'],
                        $val['direction'] == 'incoming' ? 'Cuộc gọi đến' : 'Cuộc gọi đi',
                        $val['billsec'],
                        $val['duration'],
                        $val['duration'] - $val['billsec'],
                        date("m/d/y H:i", $val['time_call']),
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }

    public function getReportByHours(){
        $Model = $this->getModel();
        $Model = $Model->select(DB::raw("COUNT(*) as total, HOUR( FROM_UNIXTIME(`time_call`) ) AS hours, disposition, direction"))
                    ->groupBy('hours')
                    ->groupBy('disposition')
                    ->groupBy('direction')
                    ->get();


        $categories = [];
        foreach ($Model as $key => $value) {
            if(!in_array($value['hours'], $categories)){
                $categories[] = $value['hours'];
            }
        }

        $DataTable = [];

        $Incoming  = [
            "name" => "Cuộc gọi đến",
            "data" => []
        ];

        $IncomingSuccess  = [
            "name" => "Cuộc gọi đến thành công",
            "data" => []
        ];

        $Outgoing = [
            "name" => "Cuộc gọi đi",
            "data" => []
        ];

        
        
        foreach ($categories as $key => $value) {
            $Incoming['data'][] = 0;
            $Outgoing['data'][] = 0;
            $IncomingSuccess['data'][] = 0;
            $Data = [
                'outgoing'          => 0,
                'incoming_success'  => 0,
                'incoming'          => 0
            ];
            foreach ($Model as $k => $v) {
                if($v['hours'] == $value){
                    $Data['time'] = $v['hours'].'h - '.($v['hours'] + 1).'h';

                    if($v['direction'] == 'outgoing'){
                        $Outgoing['data'][$key] += $v['total'];
                        $Data['outgoing'] += $v['total'];
                    }else {
                        if($v['disposition'] == 'ANSWERED'){
                            $IncomingSuccess['data'][$key] += $v['total'];
                            $Data['incoming_success'] += $v['total'];
                        }

                        $Data['incoming'] += $v['total'];
                        $Incoming['data'][$key] += $v['total'];
                    }
                }
            }
            if(!empty($Data)){
                $DataTable[] = $Data;
            }
            
        }

        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => [
                'series'        => [$Outgoing, $Incoming,  $IncomingSuccess],
                'categories'    => $categories,
                'data_table'    => $DataTable
            ]
        ]);

        


    }
    public function getReportCs(){
        $StartDate  = Input::has('startDate')   ? Input::get('startDate')   : "";
        $EndDate    = Input::has('endDate')     ? Input::get('endDate')     : "";


        $CallReportModel = $this->getModel();


        $ListCS          = \sellermodel\UserInfoModel::whereIn('group', [6, 15])->with(['user'])->get()->toArray();

        $ListCSId        = [];
        $ListCSName      = [];
        $xAsix           = [];

        foreach ($ListCS as $key => $value) {
            if(!empty($value['sip_account'])){
                $ListCSName[$value['sip_account']] = $value['user']['fullname'];
                $xAsix[] = $value['user']['fullname'];
                $ListExtension[] = $value['sip_account'];
            }
        }


        $CallReportModel = $CallReportModel
                            ->select(DB::raw('dst, disposition, count(*) as number_call,  SUM( billsec ) AS total_time'))
                            ->whereIn('dst', $ListExtension)
                            ->whereIn('disposition', ["ANSWERED", "NO ANSWER"])
                            ->groupBy('disposition')
                            ->groupBy('dst')
                            ->get();




        $AnsweredCalls = [
            "name" => "Cuộc gọi nghe",
            "data" => []
        ];

        $NoAnsweredCalls = [
            "name" => "Cuộc gọi hủy",
            "data" => []
        ];

        $DataTable = [];

        foreach ($ListExtension as $k => $ext) {
            $AnsweredCalls['data'][$k] = 0;
            $NoAnsweredCalls['data'][$k] = 0;
            $DataTable[$k] = [
                'employee'      => $ListCSName[$ext],
                'answered'      => 0,
                'no_answered'   => 0,
                'total_time'    => 0
            ];
            foreach ($CallReportModel as $key => $value) {
                
                if($value['dst'] == $ext){
                    if($value['disposition'] == 'ANSWERED'){
                        $AnsweredCalls['data'][$k]      = $value['number_call'];
                        $DataTable[$k]['answered']      = $value['number_call'];
                        $DataTable[$k]['total_time']    =  $value['total_time'];
                    }
                    if($value['disposition'] == 'NO ANSWER'){
                        $DataTable[$k]['no_answered']   = $value['number_call'];
                        $NoAnsweredCalls['data'][$k]    = $value['number_call'];
                    }
                }
            }
        }

        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => [
                'series'        => [$AnsweredCalls, $NoAnsweredCalls],
                'categories'    => $xAsix,
                'data_table'    => $DataTable
            ]
        ]);
    }
    public function getUserByPhone(){
        $Phone = Input::has('phone') ? Input::get('phone') : "";
        if (empty($Phone)) {
            return Response::json(['error'=> false, 'error_message'=> "", 'data'=> []]);
        }
        $User = User::where('phone', $Phone)
                ->select(['id', 'email', 'phone', 'fullname', 'identifier'])
                ->first();

        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => $User
        ]);
    }

    public function getUserInfo($KeyWord){
        $UserCtrl = new \oms\UserCtrl();
        return $UserCtrl->getStatistic($KeyWord);
    }


}
