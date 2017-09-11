<?php namespace mobile_ticket;

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
use metadatamodel\GroupOrderStatusModel;
use ordermodel\OrdersModel;
use ordermodel\AddressModel;
use ticketmodel\AssignGroupModel;
use User;
use DB;
use Cache;
use Excel;
use ticketmodel\ReplyTemplateModel;
use CourierPostOfficeDetailModel;

class RequestController extends \BaseController
{
    private $data_new = array();
    private $data_old = array();
    private $field = '';
    private $list_status = [
        'NEW_ISSUE'             => 'Mới tạo',
        'ASSIGNED'              => 'Đã tiếp nhận',
        'PENDING_FOR_CUSTOMER'  => 'Đã trả lời',
        'CUSTOMER_REPLY'        => 'Khách đã phản hồi',
        'PROCESSED'             => 'Đã xử lý',
        'CLOSED'                => 'Đã đóng'
    ];
    private $list_priority = [
        0 => '',
        1 => 'Bình thường',
        2 => 'Quan Trọng',
        3 => 'Rất quan trọng'
    ];

    private $total = 0;
    private $total_group = [];
    private $log_view = [];
    private $data = [];

    private function SetData($type, $data_old, $data_new)
    {
        $this->data_old[$type] = $data_old;
        $this->data_new[$type] = $data_new;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */

    public function getIndex()
    {
        echo 'haiz';
        die;
    }


    public function getListbyuser()
    {
        $UserInfo = $this->UserInfo();

        $page = Input::has('page') ? (int)Input::get('page') : 1;
        $Status = Input::has('status') ? strtoupper(trim(Input::get('status'))) : 'ALL';
        $itemPage = Input::has('limit') ? Input::get('limit') : 20;
        $Search = Input::has('search') ? Input::get('search') : '';
        $TimeStart = Input::has('time_start') ? (int)Input::get('time_start') : 0;
        $id = $UserInfo['id'];

        $offset = ($page - 1) * $itemPage;
        $Model = new RequestModel;
        $Model = $Model->where('user_id', '=', $id);

        if ($TimeStart > 0) {
            $Model = $Model->where('time_create', '>=', time() - $TimeStart * 86400);
        }

        if (!empty($Search)) {
            if (filter_var((int)$Search, FILTER_VALIDATE_INT, array('option' => array('min_range' => 1, 'max_range' => 6)))) {
                $Model = $Model->where('id', (int)$Search);
            } else {
                $ListId = [];
                if (preg_match('/^SC\d+$/i', $Search)) {
                    $ReferModel = new ReferModel;
                    $ListRefer = $ReferModel->where('code', 'LIKE', '%' . $Search . '%')->get(array('ticket_id', 'code'))->ToArray();
                    $ListId = [0];
                    if (!empty($ListRefer)) {
                        foreach ($ListRefer as $val) {
                            $ListId[] = $val['ticket_id'];
                        }
                    }
                }

                $Model = $Model->where(function ($query) use ($Search, $ListId) {
                    if (!empty($ListId)) {
                        $query = $query->whereIn('id', $ListId);
                    }

                    $query->orWhere('title', 'LIKE', '%' . $Search . '%')
                        ->orWhere('content', 'LIKE', '%' . $Search . '%');
                });
            }
        }


        $ModelTotal = clone $Model;
        $Total = $ModelTotal->groupBy('status')->get(array('status', DB::raw('count(*) as count')));
        $TotalAll = 0;
        $TotalGroup = array('ALL' => 0, 'NEW_ISSUE' => 0, 'ASSIGNED' => 0, 'PENDING_FOR_CUSTOMER' => 0, 'CUSTOMER_REPLY' => 0, 'PROCESSED' => 0, 'CLOSED' => 0);
        if (!empty($Total)) {
            foreach ($Total as $val) {
                $TotalAll += $val['count'];
                $TotalGroup[$val['status']] = $val['count'];
            }
        }

        if ($Status != 'ALL') {
            $Model = $Model->where('status', '=', $Status);
        }

        $TotalGroup['ALL'] = $TotalAll;

        if (isset($TotalGroup[$Status]) && $TotalGroup[$Status] > 0) {
            $Data = [];
            $DataAction = [];

            $ModelData = clone $Model;
            $ModelCount = clone $Model;

            if (empty($Search)) {
                $ModelData = $ModelData->where('user_last_action', '<>', $id);
                $ModelCount = $ModelCount->where('user_last_action', '<>', $id);
            }

            $CountData = $ModelCount->count();
            if ($CountData > 0) {
                $Data = $ModelData->orderBy('time_update', 'DESC')
                    ->orderBy('priority', 'DESC')
                    ->orderBy('time_create', 'DESC')
                    ->skip($offset)
                    ->take($itemPage)
                    ->with(array('refer'))->get()->toArray();
            }

            if ((int)$itemPage > 0 && count($Data) < $itemPage && (count($Data) + $offset) < $TotalGroup[$Status]) {
                $DataAction = $Model->where('user_last_action', $id)
                    ->orderBy('time_update', 'DESC')
                    ->orderBy('priority', 'DESC')
                    ->orderBy('time_create', 'DESC')
                    ->skip(floor(($offset - $CountData) / $itemPage))
                    ->take($itemPage - count($Data))
                    ->with(array('refer'))->get()->toArray();
            }

            if (empty($Data) && !empty($DataAction)) {
                $Data = $DataAction;
            } elseif (!empty($Data) && !empty($DataAction)) {
                $Data = array_merge($Data, $DataAction);
            }
        }


        if (isset($Data) && !empty($Data)) {
            $TimeNow = time();
            $ListIdTicket = [];

            foreach ($Data as $key => $val) {
                $Data[$key]['time_before'] = $this->ScenarioTime(($TimeNow - $val['time_create']));
                $Data[$key]['time_update_before'] = $this->ScenarioTime(($TimeNow - $val['time_update']));
                $ListIdTicket[] = (int)$val['id'];
            }

            if (!empty($ListIdTicket)) {
                $LogViewModel = new LogViewModel;
                $LogView = $LogViewModel->where('user_id', $id)
                    ->whereIn('ticket_id', $ListIdTicket)
                    ->get()->toArray();
            }
        }

        $contents = array(
            'error' => false,
            'message' => 'success',
            'total' => $TotalAll,
            'total_group' => $TotalGroup,
            'data' => isset($Data) ? $Data : new \stdClass(),
            'log_view' => isset($LogView) ? $LogView : []
        );

        return Response::json($contents);
    }

    private function ResponseData()
    {
        $Cmd = Input::has('cmd') ? strtoupper(trim(Input::get('cmd'))) : null;

        if ($Cmd == 'EXPORT') {
            return $this->ExportData('Danh sách khiếu nại', $this->data);
        }

        return Response::json([
            'error'       => false,
            'message'     => 'success',
            'total'       => $this->total,
            'total_group' => $this->total_group,
            'data'        => $this->data,
            'log_view'    => $this->log_view
        ]);
    }

    // Add by ThinhNV
    private function isDuplicateTicket($refer)
    {
        foreach ($refer as $key => $value) {
            if ($value['type'] == 3) {
                return true;
                break;
            }
        }
        return false;
    }
    private function hasWeekendInRange($start, $end){
      while ($start <= $end) {
          if (date('N', $start) == 7) {
              return 86400;
          }
          $start += 86400;
      }
      return 0;
    }
    private function OrderRefer($ListCode){
        $Data   = [];
        $OrdersModel = new OrdersModel;
        $OrdersModel::where('time_create', '>=', time() - 10368000)
                      ->where(function($query) {
                        $query->where('time_accept','>=', time() - 10368000)
                            ->orWhere('time_accept',0);
                      })
                      ->whereRaw("tracking_code in ('". implode("','", $ListCode) ."')")
                      ->with('ToOrderAddress')
                      ->chunk('1000', function($query) use(&$Data) {
                          foreach($query as $val){
                              $Data[]             = $val->toArray();
                          }
                      });
        return $Data;
    }
    

    


    
        //


    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function postCreate()
    {
        $UserInfo = $this->UserInfo();

        /**
         *  Validation params
         * */

        $validation = Validator::make(Input::all(), array(
            'data.title' => 'required',
            'data.content' => 'required',
            'customer_id' => 'sometimes|required|numeric',
            'type_id' => 'sometimes|required|numeric|min:1'
        ));

        //error
        if ($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        /**
         * Get Data
         * */
        $Data       = Input::get('data');
        $Title      = $Data['title'];
        $Content    = $Data['content'];
        $Contact    = (int)Input::get('customer_id');
        $DataType   = Input::get('type_id');
        $ListAssign = [];
        $InsertLog  = [];

        $DataInsert = array(
            'title' => $Title,
            'content' => $Content,
            'source' => 'web',
            'time_create' => time(),
            'time_update' => time(),
            'status' => 'NEW_ISSUE',
            'user_last_action' => 0,
            'user_create'      => $UserInfo['id']
        );

        if (!empty($DataType)) {
            $CaseTypeModel = new CaseTypeModel;
            $Type = $CaseTypeModel->where('id', (int)$DataType)->first(array('assign_id', 'estimate_time', 'priority'))->toArray();
            //priority
            $DataInsert['priority'] = isset($Type['priority'])  ? (int)$Type['priority'] : 0;

            //$Type['estimate_time'] is timestamp
            if (!empty($Type['estimate_time'])) {
                $TOver = $Type['estimate_time'];
                $fromTime = time();
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


                if (!empty($Type)) {
                    if (!empty($Type['estimate_time'])) {
                        $DataInsert['time_over'] = time() + $timeBonus;
                    }

                }
            }
            if (!empty($Type['assign_id'])) {
                $ListAssign           = explode(',', $Type['assign_id']);
            }
        }

        if (!empty($Contact) && $UserInfo['privilege'] > 0) {
            $DataInsert['user_id'] = $Contact;
            $ListAssign[]          = (int)$UserInfo['id'];

        } else {
            $DataInsert['user_id'] = $UserInfo['id'];
        }


        if(!empty($ListAssign)){
            $DataInsert['status']   = 'ASSIGNED';
        }

        //Insert
        DB::connection('ticketdb')->beginTransaction();

        try{
            $Insert = RequestModel::insertGetId($DataInsert);
        }catch (Exception $e){
            return Response::json([
                'error' => true,
                'message' => 'INSERT_FAIL'
            ]);
        }

        //Change status
        if($DataInsert['status'] != 'NEW_ISSUE'){
            $InsertLog[]    = [
              'id'            => (int)$Insert,
              'new'     => [
                  'status'  => $DataInsert['status']
              ],
              'old'     => [
                  'status'  => 'NEW_ISSUE'
              ],
                'time_create'   => time(),
                'user_id'       => (int)$UserInfo['id'],
                'type'          => 'status'
            ];
        }

        // Assign
        if ($ListAssign) {
            $InsertAssign   = [];
            $ListAssign     = array_unique($ListAssign);
            $AssignModel    = new AssignModel;
            foreach ($ListAssign as $val) {
                $InsertAssign[] =[
                    'ticket_id' => (int)$Insert,
                    'assign_id' => (int)$val,
                    'user_id'   => (int)$UserInfo['id'],
                    'active'    => 1,
                    'time_create' => time(),
                    'notification' => 0
                ];

                $InsertLog[]    = [
                    'id'            => (int)$Insert,
                    'new'           => [
                        'active'    => 1,
                        'assign_id' => (int)$val
                    ],
                    'old'           => [
                        'active'    => 0,
                        'assign_id' => (int)$val
                    ],
                    'time_create'   => time(),
                    'user_id'       => (int)$UserInfo['id'],
                    'type'          => 'assign'
                ];
            }

            try{
                AssignModel::insert($InsertAssign);
            }catch (Exception $e){
                return Response::json([
                    'error' => true,
                    'message' => 'ASSIGN_FAIL'
                ]);
            }
        }

        if (!empty($DataType)) {
            try{
                CaseTicketModel::insert([
                    'ticket_id' => (int)$Insert,
                    'type_id'   => (int)$DataType
                ]);
            }catch (Exception $e){
                return Response::json([
                    'error' => true,
                    'message' => 'INSERT_TYPE_FAIL'
                ]);
            }

            $InsertLog[]    = [
                'id'            => (int)$Insert,
                'new'           => [
                    'active'    => 1,
                    'type_id'   => (int)$DataType
                ],
                'old'           => [
                    'active'    => 0,
                    'type_id'   => (int)$DataType
                ],
                'time_create'   => time(),
                'user_id'       => (int)$UserInfo['id'],
                'type'          => 'case'
            ];
        }

        $Refer  = Input::json()->get('refer');

        if(!empty($Refer)){
            $DataInsert = [];
            foreach($Refer as $val){
                if(!empty($val['text'])){
                    $type = 2;
                    $val['text']    = strtoupper($val['text']);
                    if(preg_match('/^SC\d+$/i',$val['text'])){
                        $type = 1;
                    }

                    $DataInsert[]   = array(
                        'ticket_id'     =>  (int)$Insert,
                        'type'          =>  $type,
                        'code'          => strtoupper(trim($val['text']))
                    );
                }
            }

            try{
                ReferModel::insert($DataInsert);
            }catch (Exception $e){
                return Response::json([
                    'error' => true,
                    'message' => 'INSERT_REFER_FAIL'
                ]);
            }
        }

        if(!$this->InsertMultiLog($InsertLog)){
            return Response::json([
                'error' => true,
                'message' => 'INSERT_LOG_FAIL'
            ]);
        }

        DB::connection('ticketdb')->commit();

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $Insert
        );
        return Response::json($contents);
    }


    public function getShow($id){
        if (empty($id)) {
            return Response::json([
                'error'         => true,
                'error_message' => 'Không tìm thấy khiếu nại'
            ]);
        }

        $UserInfo   = $this->UserInfo();
        $TimeNow    = time();
        $Model      = new RequestModel;
        $Data       = $Model::where('id','=',$id)->with(array('refer','feedback' => function($query) use($UserInfo){
                                                                if($UserInfo['privilege'] < 1){
                                                                    $query = $query->where('source','<>','note');
                                                                }
                                                                $query->with(array('attach' => function($q){
                                                                                        $q->where('type','=',2);
                                                                                    }))
                                                                      ->orderBy('time_create','DESC');
                                                            },'rating',
                                                            'attach' => function($query){
                                                                $query->where('type','=',1);
                                                            }
                                                            ))->first();        

        if (!empty($Data) && !empty($Data['feedback'])) {
            $listUserId = [];
            $ListUser   = [];
            foreach ($Data['feedback'] as $key => $value) {
                $listUserId[] = $value['user_id'];
            }
            $ListReferCode = [];

            $TicketRefer = (string)301664;
            foreach ($Data['refer'] as $key => $value) {
                if ($value['type'] == 1) {
                    $ListReferCode[] = $value['code'];
                }

                if ($value['type'] == 3) {
                    $TicketRefer = $value['code'];
                }
            }


            $Data['ticket_refer_code']  = $TicketRefer;
            $Data['ticket_refer_info']  = (object)[];
            $Data['refer_str']          = implode(',', $ListReferCode);

            if (!empty($TicketRefer)) {
                $Data['ticket_refer_info'] = RequestModel::where('id', $TicketRefer)->select(['id', 'title', 'time_create'])->first();
            }

            foreach ($Data['attach'] as $key => $value) {
                $Data['attach'][$key]['link_tmp'] = 'http://cloud.shipchung.vn/'.$value['link_tmp'];
                $Data['attach'][$key]['thumb']    = $Data['attach'][$key]['link_tmp'];
                if (!in_array($Data['attach'][$key]['extension'], ['png', 'jpg', 'jpge'])) {
                    $Data['attach'][$key]['thumb'] = 'http://cloud.shipchung.vn/uploads/images/cards/e6380279b045a709a32b7cbdd997e2a5.png';
                }
            }

            if (empty($Data['rating']) && ($Data['status'] == 'CLOSED' ||$Data['status'] == 'PROCESSED') ) {
                $Data['can_rating'] = true;
            }else{
                $Data['can_rating'] = true;
            }

            if (!empty($listUserId)) {
                $_temp = User::whereIn('id', $listUserId)->select(['id', 'fullname'])->get()->toArray();

                foreach ($_temp as $key => $value) {
                    $ListUser[$value['id']] = $value['fullname'];
                }

                foreach ($Data['feedback'] as $key => $value) {
                    if (!empty($ListUser[$value['user_id']])) {

                        $Data['feedback'][$key]['fullname'] = $ListUser[$value['user_id']];

                        if ($value['user_id'] == $Data['user_id']) {
                            $Data['feedback'][$key]['avt'] = 'http://www.gravatar.com/avatar/964e605bd598de603f2997e6610045c5?s=80&d=mm&r=g';
                        }else {
                            $Data['feedback'][$key]['avt'] = 'http://cloud.shipchung.vn//uploads/images/cards/210e5e3589f1ca6f9274868efb66bd86.png';
                        }

                    }
                }
            }


        }

        return Response::json([
                'error'=> false,
                'error_message'=> '',
                'data'  => $Data
        ]);
    }


/*  public function getShow($id)
    {
       
        $validation = Validator::make(array('id' => $id), array(
            'id'        => 'required|numeric|min:1'
        ));

        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }


        // List Courier

        $UserInfo   = $this->UserInfo();
        $TimeNow    = time();
        $Model      = new RequestModel;
        $ListOrder  = [];  // order refer
        $Data       = $Model::where('id','=',$id)->with(array('refer','feedback' => function($query) use($UserInfo){
                                                                if($UserInfo['privilege'] < 1){
                                                                    $query = $query->where('source','<>','note');
                                                                }
                                                                $query->with(array('attach' => function($q){
                                                                                        $q->where('type','=',2);
                                                                                    }))
                                                                      ->orderBy('time_create','DESC');
                                                            },'rating',
                                                             'assign' => function($query){
                                                                $query->where('active',1)->orderBy('time_create','ASC');
                                                            },
                                                            'attach' => function($query){
                                                                $query->where('type','=',1);
                                                            },
                                                            'case_ticket' => function($query){
                                                                $query->where('active',1)->with('case_type');
                                                            }
                                                            ))->first();

        if($Data){
            $Data['time_update_str']  = $this->ScenarioTime($TimeNow - $Data['time_update']);
            $Data['rate']             = [];
            $ListUser                 = [];
            $User                     = [];

            $Log    = array();
            if($UserInfo['privilege'] > 0){
                $Log = LMongo::collection('log_change_ticket')->where('id', (int)$id)->take(10)->orderBy('time_create','desc')->get(array('id','new','old','time_create','user_id','type'))->toArray();
            }

            if(!empty($Data['user_id'])){
                $ListUser[] = (int)$Data['user_id'];
            }

            if(!empty($Log)){
                foreach($Log as $key => $val){
                    $ListUser[]                             = (int)$val['user_id'];
                    if(isset($val['new']) && isset($val['new']['assign_id'])){
                        $ListUser[] = (int)$val['new']['assign_id'];
                    }

                    $Log[$key]['time_create_str']           = $this->ScenarioTime($TimeNow - $val['time_create']);
                }
            }

            $FeedBack   = [];
            if(!empty($Data['feedback'])){
                foreach($Data['feedback'] as $key => $val){
                    $Data['feedback'][$key]['content'] = nl2br($val['content']);
                    $Data['feedback'][$key]['time_create_str']  = $this->ScenarioTime($TimeNow - $val['time_create']);
                    $ListUser[] = (int)$val['user_id'];
                }
                $FeedBack   = $Data['feedback'];
            }

            try{
                unset($Data['feedback']);
            }catch(Exception $e){

            }

            if(!empty($Data['assign'])){
                foreach($Data['assign'] as $key => $val){
                    $Data['assign'][$key]['time_create_str']  = $this->ScenarioTime($TimeNow - $val['time_create']);
                    $ListUser[] = (int)$val['assign_id'];
                }
            }


            if(!empty($Data['refer'])){
                $ListRefer  = [];
                foreach($Data['refer'] as $key => $val){
                    if($val['type'] == 1){
                        $ListRefer[] = $val['code'];
                    }
                }
                if(!empty($ListRefer)){
                    $OrderModel = new OrdersModel;
                    $ListOrder  = $OrderModel::where('time_create','>=',$TimeNow - 90*86400)
                        ->whereIn('tracking_code',$ListRefer)
                        ->with('Courier')
                        ->get(array('tracking_code','courier_id', 'from_city_id','status','courier_tracking_code'))
                        ->toArray();
                }
            }

            if(!empty($ListUser)){
                $ListUser   = array_unique($ListUser);
                $UserModel  = new User;
                $User       = $UserModel->whereIn('id',$ListUser)->with('user_info')->get(array('id', 'identifier', 'email', 'fullname', 'phone', 'time_create', 'time_last_login'))->toArray();
            }

            // Update Log View
            $LogViewModel   = new LogViewModel;
            $LogView        = $LogViewModel::firstOrNew(['ticket_id' => $id, 'user_id' => (int)$UserInfo['id']]);

            if(!$LogView->exists || ($LogView->exists && $LogView->view == 0)){
                if(!$LogView->exists){
                    $LogView->time_create = time();
                }
                $LogView->view  = 1;
                $LogView->save();
            }



            $timeStart = time() - 30*86400;
            //get user create ticket

            $listTicketByUserID = RequestModel::select(['id','title','status','time_create'])

                ->where('id','!=',$id)
                ->where('status','!=','CLOSED')
                ->where('status','!=','PROCESSED')
                ->where('user_id',$Data->user_id)->where('time_create','>=',$timeStart)->get();

            $listTicketID = [];
            $listReferTick = [];
            if(!$listTicketByUserID->isEmpty()) {
                foreach($listTicketByUserID as $oneTicketByUser) {
                    $listTicketID[] = $oneTicketByUser->id;
                    $oneTicketByUser->time_create_str = $this->ScenarioTime(time() - $oneTicketByUser->time_create);
                    $listReferTick[] = $oneTicketByUser;
                }
            }


            $listReferCode = [];
            if(!empty($listTicketID)) {
                $referCode = ReferModel::where('type',1)->whereIn('ticket_id',$listTicketID)->get();
                if(!$referCode->isEmpty()) {
                    foreach($referCode as $oneReferCode) {
                        $listReferCode[$oneReferCode->ticket_id][] = $oneReferCode;
                    }
                }
            }

            if(!$listTicketByUserID->isEmpty()) {
                foreach($listTicketByUserID as $k => $oneTicketByUser) {
                    $listTicketByUserID[$k]->referCode = isset($listReferCode[$oneTicketByUser->id]) ? $listReferCode[$oneTicketByUser->id] : [];
                }
            }

            $Data['link'] = [];
            $listTicketRefer = ReferModel::where('ticket_id', $id)->where('type', 3)->lists('code');
            if(!empty($listTicketRefer)) {
                $Data['link']  = RequestModel::whereIn('id',$listTicketRefer)->get();
            }



            $logView = LogViewModel::where('view',1)->where('user_id', $Data['user_id'])->where('ticket_id',$id)->first();
            if(!empty($logView)) {
                $Data['log_view']                    = $logView;
                $Data['log_view']['time_create_str'] = $this->ScenarioTime($TimeNow - $logView['time_create']);;
            }


            //add link ticket
            if(!empty($FeedBack)) {
                foreach($FeedBack as $k => $oneFeedback) {
                    if(preg_match_all("/(@)[0-9]{1,}/", $oneFeedback->content, $output)) {
                        if(!empty($output[0])) {
                            $output[0] = array_unique($output[0]);
                            foreach($output[0] as $ticketID) {
                                $FeedBack[$k]->content = str_replace($ticketID,'<a class="text-info" data-ng-click="show_detail('.str_replace("@","",$ticketID).')">'.$ticketID.'</a>',$oneFeedback->content);
                            }
                        }
                    }
                }
            }
            $listTypeID = [];
            if(!empty($Data['case_ticket'])) {
                foreach($Data['case_ticket'] as $oneType) {
                    $listTypeID[] = $oneType->type_id;
                }
            }
            $template = [];
            if(!empty($listTypeID)) {
                $template = ReplyTemplateModel::where('active',1)->whereIn('type_id',$listTypeID)->get();
                if(!$template->isEmpty()) {
                    $user = User::find($Data['user_id']);
                    $maVD = "";
                    if(!empty($Data['refer'])) {
                        foreach($Data['refer'] as $oneRefer) {
                            if($oneRefer->type == 1) {
                                $maVD .= $oneRefer->code.", ";
                            }
                        }
                        if(strlen($maVD) > 2) {
                            $maVD = substr($maVD,0,-2);
                        }
                    }
                    foreach($template as $k=> $oneTemplate) {
                        $search = [
                            "{ten}",
                            "{danhxung}",
                            "{mavandon}",
                            "{email}",
                            "{sdt}"
                        ];
                        $replace = [
                            $user->identifier." ".$user->fullname,
                            $user->identifier,
                            $maVD,
                            $user->email,
                            $user->phone
                        ];
                        $template[$k]->message = str_replace($search,$replace,$oneTemplate->message);
                    }
                }
            }
            $Data['template'] = $template;
            $ListTicketRefer = $this->getDuplicate($id, false);
            $Data['list_ticket_refer'] = ($ListTicketRefer) ? $ListTicketRefer : [];
            $contents = array(
                'error'         => false,
                'message'       => 'success',
                'data'          => $Data,
                'feedback'      => $FeedBack,
                'user'          => $User,
                'list_order'    => $ListOrder,
                'ticket_refer'  =>  $listTicketByUserID,
                'log'           => $Log
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'data empty'
            );
        }

        return Response::json($contents);
    }*/





    public function getDuplicate($id, $json = true){
        $RefModel  = new ReferModel;
        $ListRefId = $RefModel->where('code', $id)->where('type', 3)->lists('ticket_id');
        if($json){
            return Response::json(array(
                'error'         => false,
                'error_message' => '',
                'data'          => $ListRefId
            ));
        }else {
            return $ListRefId;
        }

    }

    public function postCloseTicket($id){
        Validator::getPresenceVerifier()->setConnection('ticketdb');
        $validation = Validator::make(array('id' => $id), array(
            'id'       => 'required|numeric|exists:ticket_request,id'
        ));

        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => 'Không thể cập nhật yêu cầu này, vui lòng thử lại sau !.', 'error_message' => 'Không thể cập nhật yêu cầu này, vui lòng thử lại sau !.'));
        }

        $Status     = "CLOSED";
        $Model      = new RequestModel;

        $Data       = $Model::find($id);
        $UserInfo   = $this->UserInfo();

        if ($Data->user_id == $UserInfo['id'] && !empty($Status) && !in_array($Status, ['PENDING_FOR_CUSTOMER', 'CLOSED'])) {
          $contents = array(
              'error'           => true,
              'message'         => 'Không thể cập nhật yêu cầu này, vui lòng thử lại sau !.',
              'error_message'   => 'Không thể cập nhật yêu cầu này, vui lòng thử lại sau !.'
          );
          return Response::json($contents);
        }

        if(!empty($Status)){
            $this->SetData('status', $Data->status, $Status);
            $this->field        = 'status';
            $Data->status       = $Status;

            /// Closed ticket refer
            if($Status == 'CLOSED' && !empty($ListTicketRefer)){
                $Req = new RequestModel;
                $ReqList = $Req->whereIn('id', $ListTicketRefer)->get();
                foreach ($ReqList as $key => $value) {
                    $old = $value->status;
                    $value->status = 'CLOSED';
                    $value->save();
                    $this->InsertLog($value->id, $old, 'CLOSED', 'status');
                }
            }
        }


        $Data->time_update      = time();
        $Data->user_last_action = (int)$UserInfo['id'];
        $Update                 = $Data->save();

        if($Update){
            $this->InsertLog($id, $this->data_old, $this->data_new, $this->field);
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'time_over' =>  $Data->time_over
            );
        }else{
            $contents = array(
                'error' => true,
                'message' => 'edit error'
            );
        }

        return Response::json($contents);
    }

    public function postEdit($id)
    {
        /**
        *  Validation params
        * */
        Validator::getPresenceVerifier()->setConnection('ticketdb');
        $validation = Validator::make(array('id' => $id), array(
            'id'       => 'required|numeric|exists:ticket_request,id'
        ));

        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $ListTicketRefer = Input::json()->has('list_ticket_refer') ? Input::json()->get('list_ticket_refer') : "";
        $Status          = Input::json()->get('status');
        $Priority        = Input::json()->get('priority');
        $TOver           = Input::json()->get('time_over');
        $TypeProcess     = Input::json()->has('type_process') ? Input::json()->get('type_process') : null;

        $checkAssign     = AssignModel::where('ticket_id',$id)->where('active',1)->count();
        if($checkAssign == 0) {
            return Response::json([
                'error'   =>  true,
                'message' =>  'Cần assign cho nhân viên trước khi chuyển trạng thái'
            ]);
        }
        $Model      = new RequestModel;
        $Data       = $Model::find($id);
        $UserInfo   = $this->UserInfo();


        // Update  ticket  status closed
        if($Data->status == 'CLOSED' && (int)$UserInfo['privilege'] != 2 && (int)$UserInfo['group'] != 15){
            $contents = array(
                'error'         => true,
                'message'       => 'USER_NOT_ALLOWED',
                'error_message' => 'Không thể mở lại yêu cầu này, quý khách vui lòng tạo yêu cầu mới, hoặc liên hệ trực tiếp với Shipchung để được hỗ trợ.'
            );
            return Response::json($contents);
        }


        if ($Data->user_id == $UserInfo['id'] && !empty($Status) && !in_array($Status, ['PENDING_FOR_CUSTOMER', 'CLOSED'])) {
          $contents = array(
              'error'           => true,
              'message'         => 'USER_NOT_ALLOWED',
              'error_message'   => 'Không thể mở lại yêu cầu này, quý khách vui lòng tạo yêu cầu mới, hoặc liên hệ trực tiếp với Shipchung để được hỗ trợ.'
          );
          return Response::json($contents);
        }


        // Update status to closed
        if($Status == 'CLOSED' &&  !$this->check_privilege('PRIVILEGE_TICKET','edit') && $Data->user_id != $UserInfo['id']){
            $contents = array(
                'error'         => true,
                'message'       => 'USER_NOT_ALLOWED',
                'error_message' => 'Không thể mở lại yêu cầu này, quý khách vui lòng tạo yêu cầu mới, hoặc liên hệ trực tiếp với Shipchung để được hỗ trợ.'
            );
            return Response::json($contents);
        }

        if(!empty($Priority)){
            $this->SetData('priority', $Data->priority, $Priority);
            $this->field    = 'priority';
            $Data->priority   = $Priority;
        }

        if(!empty($TOver)){
            $fromTime      = ($Data->time_over == 0) ? $Data['time_create'] : $Data['time_over'];
            $currentHour   = date("G",$fromTime);
            $currentMinute = date("i",$fromTime);
            $currentDay    = date("N",$fromTime);
            $timeProcess   = 0;
            if ($currentHour < 8) {
                $timeBonusFirstDay = 8 * 3600 + (24 - 17.5) * 3600 + 1.5*3600;
                $timeProcess       = 8 * 3600;
            } else if ($currentHour >= 18) {
                $timeBonusFirstDay = (24 - 17.5) * 3600;
            } else if ($currentHour == 17 && $currentMinute >= 30) {
                $timeBonusFirstDay = 24 - 17.5 * 3600;
            } else {
                $timeBonusFirstDay = (24 - 17.5) * 3600 + (1.5 * 3600);
                $timeProcess       = (17.5 - $currentHour) * 3600 - (1.5 * 3600) - ($currentMinute) * 60;;
            }


             $newTOver  = $TOver - $timeProcess;
             $totalDays = (floor(($newTOver/(8*3600))));
             /*
             * tong time cua cac ngay lam day du + tong time bonus cua ngay nhan dau
             * + time process ngay dau
             * + 8h do bi day sang ngay hom sau + so du* thoi gian con lai
             */
             $timeBonus = $timeBonusFirstDay + $timeProcess;

            if($totalDays>0) {
                $timeBonus += $totalDays*86400;
            }
            if($TOver > $timeProcess) {
                /*
                 * neu qua' 1 ngay thi se~ co so du thoi gian con lai
                 */
                $timeBonus += 8*3600 + $newTOver%(8*3600);
                ++$totalDays;
            }
            $numberOfWeek = floor(($currentDay + $totalDays)/7);
            if($numberOfWeek>0) {
                $timeBonus += $numberOfWeek * 24*3600;
            }

            if($Data['time_over'] > 0){
                $this->SetData('time_over', $Data->time_over, $timeBonus + $Data['time_over']);
                $Data->time_over   = $timeBonus + $Data['time_over'];
            }else{
                $this->SetData('time_over', 0, $timeBonus + $Data['time_create']);
                $Data->time_over   = $timeBonus + $Data['time_create'];
            }
            $this->field    = 'time_over';
        }

        if(!empty($Status)){
            $this->SetData('status', $Data->status, $Status);
            $this->field        = 'status';
            $Data->status       = $Status;

            /// Closed ticket refer
            if($Status == 'CLOSED' && !empty($ListTicketRefer)){
                $Req = new RequestModel;
                $ReqList = $Req->whereIn('id', $ListTicketRefer)->get();
                foreach ($ReqList as $key => $value) {
                    $old = $value->status;
                    $value->status = 'CLOSED';
                    $value->save();
                    $this->InsertLog($value->id, $old, 'CLOSED', 'status');
                }
            }

        }

        if(isset($TypeProcess) && $UserInfo['courier_id'] == 0){
            $this->SetData('type_process', $Data->type, $TypeProcess);
            $this->field        = 'type_process';
            $Data->type = $TypeProcess;
        }

        $Data->time_update      = time();
        $Data->user_last_action = (int)$UserInfo['id'];
        $Update                 = $Data->save();

        if($Update){
            $this->InsertLog($id, $this->data_old, $this->data_new, $this->field);
            $contents = array(
                'error'         => false,
                'message'       => 'Cập nhật thành công',
                'error_message' => 'Cập nhật thành công',
                'time_over' =>  $Data->time_over
            );
        }else{
            $contents = array(
                'error' => true,
                'message' => 'Cập nhật thất bại, quý khách vui lòng thử lại sau !',
                'error_message' => 'Cập nhật thất bại, quý khách vui lòng thử lại sau !'
            );
        }

        return Response::json($contents);
    }

    public function ScenarioTime($time){
        $str = '';
        if($time > 0){
            $hours   = floor($time/60);

            if($hours > 518400){
                $str   = floor($hours/518400).' năm';
            }
            elseif($hours > 43200){ // 30 ngày
                $str   = floor($hours/43200).' tháng';
            }elseif($hours > 1440){ // 1 ngày
                $str   = floor($hours/1440).' ngày';
            }elseif($hours > 60){// 1 hours
                $str   = floor($hours/60).' giờ';
            }elseif($hours > 0){
                $str   = $hours.' phút';
            }else{
                $str   = '1 phút';
            }

        }
        return $str;
    }

    public function InsertLog($id, $DataOld, $Data, $Type){
        $UserInfo   = $this->UserInfo();
        $DataInsert = array(
            'id'            => (int)$id,
            'new'           => $Data,
            'old'           => $DataOld,
            'time_create'   => time(),
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

    public function InsertMultiLog($Data){
        try {
            $Create = LMongo::collection('log_change_ticket')->batchInsert($Data);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    

    public function postRating($id)
    {
        /**
        *  Validation params
        * */
        
        Validator::getPresenceVerifier()->setConnection('ticketdb');
        
        $validation = Validator::make(array('id' => $id, 'rate' => Input::get('rate'), ), array(
            'id'        => 'sometimes|numeric|exists:ticket_request,id',
            'rate'      => 'required'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }
        
        /**
         * Get Data 
         * */
         
        $Rate               = Input::get('rate');
        $Comment            = Input::get('comment');

        try {
            $Model = \ticketmodel\RatingModel::firstOrNew(['ticket_id'=> $id, 'question_id' => 1, 'rate' => (int)$Rate, 'source'=> 'app', 'note' => $Comment]);

            if($Model->time_create == 0){
                    $Model->time_create = time();
                    $Model->save();
            }
        } catch (Exception $e) {
            
        }
        
        
        $contents = array(
            'error'     => false,
            'message'   => 'succes'
        );
        
        return Response::json($contents);
    }
    




    /*
    * Cập nhật trạng thái đã xử lý với yêu cầu phát lại
    */



}
