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
use Lang;

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

	public function postAssginPostoffice(){
	  $TicketId = Input::has('id') ? Input::get('id') : "";
	  $Order_ID  = Input::has('order') ? Input::get('order') : "";
	  $UserInfo = $this->UserInfo();
	  $Id       = $UserInfo['id'];
	  $OrderId  = explode(',', $Order_ID);


	  if(empty($OrderId)){
		return Response::json(['error'=> true, 'error_message'=> '', 'data' =>[]]);
	  }


	  $TicketModel    = new RequestModel;
	  $Ticket         = RequestModel::where('time_create','>=',$this->time() - $this->time_limit)->where('id',$TicketId)->first();

	  if(empty($Ticket)){
		  $contents = array(
			  'error'     => true,
			  'message'   => 'NOT_EXISTS'
		  );
		  return Response::json($contents);
	  }

	  $OrderModel = OrdersModel::whereIn('tracking_code', $OrderId)
					->where(function($query) {
						$query->where('time_accept','>=', $this->time() - 86400*90)
							->orWhere('time_accept',0);
					})
				  ->select(['id', 'tracking_code', 'courier_id', 'from_district_id', 'from_city_id','from_ward_id'])
				  ->get()->toArray();

	  if(empty($OrderModel)){
		return Response::json(['error'=> true, 'error_message'=> '', 'data' =>[]]);
	  }

	  $ListDistrict = [];
	  $ListCity     = [];
	  $ListUserId   = [];
	  $ListWard     = [];
	  $CourierId    = [];
	  $ListUser         = [];
	  $ListUserAssign   = [];

	  $ListInsert         = [];
	  $InsertLog          = [];

	  foreach ($OrderModel as $key => $value) {
		  $ListDistrict[]   = $value['from_district_id'];
		  $ListCity[]       = $value['from_city_id'];

		  $CourierId[]      = $value['courier_id'];

		  if($value['from_ward_id'] > 0){
			  $ListWard[]       = $value['from_ward_id'];
		  }
	  }


		$ListCity           = array_unique($ListCity);
		$ListDistrict       = array_unique($ListDistrict);
		$ListWard           = array_unique($ListWard);
		$UserInfoModel      = new UserInfoModel;

	  if(!empty($ListWard)){
			$ListPostOffice = CourierPostOfficeDetailModel::whereIn('ward_id',$ListWard)->where('active',1)->lists('post_office_id');
			if(!empty($ListPostOffice)){
				$ListUser   = $UserInfoModel->whereIn('courier_id', $CourierId)->whereIn('privilege',[3,4])->whereIn('post_office_id', $ListPostOffice)->orderBy('privilege','ASC')->remember(60)->get(['id', 'user_id', 'privilege', 'courier_id'])->toArray();
			}
	  }

		if(empty($ListUser)){
			  $ListUser = $UserInfoModel->whereIn('courier_id', $CourierId)
										->whereIn('privilege', [4,5])
										->whereIn('location_id', array_merge($ListDistrict, $ListCity))
										->orderBy('privilege','ASC')
										->remember(60)
										->get(['id', 'user_id', 'privilege','courier_id'])->toArray();
		}

		if(empty($ListUser)){
			return Response::json(['error'=> true, 'error_message'=> Lang::get('response.NOT_DELIVERY'), 'data' =>[]]);
		}

	   $Check = [];
	   foreach($ListUser as $val){
		   if($val['privilege'] == 3){
			   $ListUserAssign[(int)$val['courier_id']][] = (int)$val['user_id'];
		   }else{
				if(empty($ListUserAssign[(int)$val['courier_id']]) || $val['privilege'] == 5){
					$ListUserAssign[(int)$val['courier_id']][] = (int)$val['user_id'];
				}
		   }
	   }

		$ListUserAssign = array_flatten($ListUserAssign);
		foreach($ListUserAssign as $val){
			$ListInsert[(int)$val] = [
				'ticket_id'    => $TicketId,
				'assign_id'    => (int)$val,
				'active'       => 1,
				'user_id'      => $Id,
				'time_create'  => $this->time(),
				'notification' => 0
			];

			$InsertLog[(int)$val] = [
				'id' => (int)$TicketId,
				'new' => [
					'active'    => 1,
					'assign_id' => (int)$val
				],
				'old' => [
					'active'    => 0,
					'assign_id' => (int)$val
				],
				'time_create' => $this->time(),
				'user_id' => $Id,
				'type' => 'assign'
			];
		}



	  DB::connection('ticketdb')->beginTransaction();
	  try{
		  AssignModel::insert($ListInsert);
	  }catch (Exception $e){
		  $contents = array(
			  'error'     => true,
			  'message'   => Lang::get('response.INSERT_FAIL')
		  );
		  return Response::json($contents);
	  }

	  if($Ticket->status == 'NEW_ISSUE'){
		  $Ticket->status = 'ASSIGNED';
		  $InsertLog[]    = [
			  'id' => (int)$TicketId,
			  'new' => [
				  'status'    => 'ASSIGNED',
			  ],
			  'old' => [
				  'status'    => 'NEW_ISSUE',
			  ],
			  'time_create'   => $this->time(),
			  'user_id'       => $Id,
			  'type'          => 'status'
		  ];
	  }

	  try{

		  $Ticket->time_update        = $this->time();
		  $Ticket->user_last_action   = $Id;
		  $Ticket->save();

	  }catch (Exception $e){
		  $contents = array(
			  'error'           => true,
			  'error_message'   => Lang::get('response.UPDATE_TICKET_FAIL')
		  );
		  return Response::json($contents);
	  }


	  if($this->InsertMultiLog($InsertLog)){
		  DB::connection('ticketdb')->commit();
	  }else{
		  $contents = array(
			  'error'     => true,
			  'error_message'   => Lang::get('response.UPDATE_LOG_FAIL')
		  );
		  return Response::json($contents);
	  }

	  $Assginer = User::whereIn('id', $ListUserAssign)->select(['id', 'fullname'])->get()->toArray();
	  $contents = array(
		  'error'           => false,
		  'error_message'   => Lang::get('response.SUCCESS'),
		  'list_id'         => $Assginer
	  );

	  return Response::json($contents);


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
			$Model = $Model->where('time_create', '>=', $this->time() - $TimeStart * 86400);
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
			$TimeNow = $this->time();
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
			'message' => Lang::get('response.SUCCESS'),
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
		$OrdersModel::where('time_create', '>=', $this->time() - 10368000)
					  ->where(function($query) {
						$query->where('time_accept','>=', $this->time() - 10368000)
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
	

	private function checkHanXuLy($CaseID, $time_create, $time_reply){
	  $str = '';
	  if($time_reply == 0){
		$str = 'Chưa tiếp nhận - ';
		$time_reply = $this->time() - $time_create;
	  }
	  
	  

	  if ($this->hasWeekendInRange($time_create, $time_create + $time_reply) > 0) {
		$time_reply -= 86400;
	  }

	  $ReplyTime = $time_create + $time_reply;
	  

	  

	  $ReplyDay     = date("d", $ReplyTime);
	  $ReplyMonth   = date("m", $ReplyTime);
	  $ReplyHours   = date("Hi",$ReplyTime);
	  $ReplyYear    = date("Y", $ReplyTime);

	  $CreateDay    = date("d", $time_create);
	  $CreateHour   = date("Hi", $time_create);
	  $CreateMonth  = date("m", $time_create);
	  $CreateYear   = date("Y", $time_create);

	  $CreateDate = $CreateYear.'-'.$CreateMonth.'-'.$CreateDay;


	  if((int)$CaseID == 9){ // Kieu Nai

		if( $time_create >= strtotime(date($CreateDate.' 08:00:00'))  
			&& $time_create <= strtotime(date($CreateDate.' 12:00:00'))
			&& $time_reply < 21600
		){
		  return $str."Đúng hạn";
		}



		if (
		  $time_create >= strtotime(date($CreateDate.' 12:00:00'))
		  && $time_create <= strtotime(date($CreateDate.' 07:59:00')) + 86400
		  && $ReplyTime <= strtotime(date($CreateDate.' 12:00:00')) + 86400
		) {
		  return $str."Đúng hạn";
		}

		if (
		  $time_create >=  strtotime(date($CreateDate.' 00:00:00'))
		  && $time_create <= strtotime(date($CreateDate.' 07:59:00'))
		  && $ReplyTime <= strtotime(date($CreateDate.' 12:00:00'))
		) {
		  return $str."Đúng hạn";
		}

		return $str."Quá hạn";
	  }

		if (in_array((int)$CaseID, [4, 2, 3])){
			if (
					$time_create >= strtotime(date($CreateDate.' 08:00:00'))
				&&  $time_create <= strtotime(date($CreateDate.' 09:00:00')) 
				&&  $ReplyTime <= strtotime(date($CreateDate.' 11:00:00')) 
			) {
			  return $str."Đúng hạn";
			}

			if (
					$time_create >= strtotime(date($CreateDate.' 09:00:00'))
				&&  $time_create <= strtotime(date($CreateDate.' 10:30:00')) 
				&&  $time_reply <= 7200
			) {
			  return $str."Đúng hạn";
			}

			if (
					$time_create >= strtotime(date($CreateDate.' 10:30:00'))
				&&  $time_create <= strtotime(date($CreateDate.' 13:30:00')) 
				&&  $ReplyTime <= strtotime(date($CreateDate.' 15:30:00')) 
			) {
			  return $str."Đúng hạn";
			}

			if (
					$time_create >= strtotime(date($CreateDate.' 13:30:00'))
				&&  $time_create <= strtotime(date($CreateDate.' 15:30:00')) 
				&&  $time_reply <= 7200
			) {
			  return $str."Đúng hạn";
			}

			if (
					$time_create >= strtotime(date($CreateDate.' 00:00:00'))
				&&  $time_create <= strtotime(date($CreateDate.' 07:59:00'))
				&&  $ReplyTime <= strtotime(date($CreateDate.' 10:00:00'))
			) {
			  return $str."Đúng hạn";
			}

			if (
					$time_create >= strtotime(date($CreateDate.' 15:30:00'))
				&&  $time_create <= strtotime(date($CreateDate.' 07:59:00')) + 86400
				&&  $ReplyTime <= strtotime(date($CreateDate.' 10:00:00')) + 86400
			) {
			  return $str."Đúng hạn";
			}


			return $str."Quá hạn";
		}




	}


	private function ExportData($FileName,$Data){
		set_time_limit(1800);
		$Type       = [];
		$User       = [];
		$Refer      = [];
		$Order      = [];
		$Courier    = [];
		$City       = [];
		$District   = [];
		$Logs       = [];
		$LogProcess = [];
		$LogAssgin = [];
		$CaseID     = '';

		if(!empty($Data)){
			$CaseID       = Input::has('case') ? (int)Input::get('case') : '';

			$ListType     = CaseTypeModel::where('active', 1)->remember(60)->get()->toArray();
			$ListUserId   = [];
			$ListTicketId = [];




			foreach($ListType as $val){
				$Type[(int)$val['id']]  = $val['type_name'];
			}

			foreach($Data as $oneUser) {
				$ListTicketId[] = $oneUser['id'];

				$ListUserId[]   = $oneUser['user_id'];
				if(!empty($oneUser['assign'])) {
					foreach($oneUser['assign'] as $oneAssign) {
						$ListUserId[] = $oneAssign['assign_id'];
					}
				}

				if(!empty($oneUser['refer'])){
					foreach($oneUser['refer'] as $v){
						if((int)$v['type'] == 1){
							$Refer[]    = strtoupper(trim($v['code']));
						}
					}
				}
			}

			if(!empty($Refer)){
				$Order = $this->OrderRefer($Refer);
				$Refer = [];
				if(!empty($Order)){
					$Courier    	= $this->getCourier();
					$ListDistrictId = [];
					$ListCityId		= [];

					foreach($Order as $val){
						$Refer[$val['tracking_code']]   = $val;
						$ListCityId[]		= $val['from_city_id'];
						$ListCityId[]		= $val['to_city_id'];
						$ListDistrictId[] 	= $val['to_order_address']['province_id'];
						$ListDistrictId[] 	= $val['from_district_id'];
					}

					if(!empty($ListDistrictId)){
						$ListDistrictId = array_unique($ListDistrictId);
						$District       = $this->getProvince($ListDistrictId);
					}
					if(!empty($ListCityId)){
						$ListCityId 	= array_unique($ListCityId);
						$BaseCtrl 		= new \ops\BaseCtrl;
						$City       	= $BaseCtrl->getCityById($ListCityId);
					}
				}
			}
			$ListTicketId = array_unique($ListTicketId);
			$ListUserId   = array_unique($ListUserId);


			if(!empty($ListUserId)){
				$UserModel  = new User();
				$ListUser   = $UserModel->whereIn('id',$ListUserId)->with(['user_info'])->get(array('id','email'));
				if(!empty($ListUser)){
					foreach($ListUser as $val){
						$User[(int)$val['id']]  = $val;
					}
				}
			}
			$Logs       = [];
			$LogProcess = []; // Log phan loai
			$LogAssgin  = [];

			/*if(!empty($ListTicketId)){
				$listLog = LMongo::collection('log_change_ticket')->whereIn('id', $ListTicketId)->where('new.status', 'PROCESSED')->get()->toArray();
				foreach ($listLog as $key => $val) {
					$Logs[(int)$val['id']] = $val;
				}
			}*/

			if(!empty($ListTicketId)){
				$listLog = LMongo::collection('log_change_ticket')->whereIn('id', $ListTicketId)->get()->toArray();
				foreach ($listLog as $key => $val) {

					if(!empty($val['new']['status']) && $val['new']['status'] == 'PROCESSED'){
					  $Logs[(int)$val['id']] = $val;
					}

					if(!empty($val['new']['status']) && $val['new']['status'] == 'ASSIGNED'){
					  $LogAssgin[(int)$val['id']] = $val;
					}

					if($val['type'] == 'type_process'){
					  $LogProcess[(int)$val['id']] = $val;
					}
				}
			}
		}

		Excel::selectSheetsByIndex(0)->load('/data/www/html/storage/template/ticket/danh_sach.xlsx', function($reader) use($Data,$Type,$User, $Logs, $Refer, $Courier, $City, $District, $LogProcess, $LogAssgin, $CaseID) {
			$reader->sheet(0,function($sheet) use($Data,$Type,$User, $Logs, $Refer, $Courier, $City, $District, $LogProcess, $LogAssgin, $CaseID)
			{
				$i = 1;
				foreach ($Data AS $val) {
					$TypeCase   = '';
					$ReferCode  = '';
					$Assign     = '';
					$UserCreate = '';
					$UserVip    = '';
					$Hvc            = '';
					$QuanHuyen      = '';
					$QuanHuyenNhan  = '';
					$ThanhPho       = '';
					$ThanhPhoNhan   = '';
					$DcNhan         = '';
					$DcGui          = '';

					$QuaHanXuly     = '';


					$AssginTime     = [];


					if(!empty($CaseID)){
						$QuaHanXuly = $this->checkHanXuLy($CaseID, $val['time_create'], $val['time_reply']);
					}

					if(isset($User[(int)$val['user_id']])){
						$UserCreate = $User[(int)$val['user_id']]['email'];
					}

					if(isset($User[(int)$val['user_id']])){
						if($User[(int)$val['user_id']]['user_info']['is_vip'] == 1){
							$UserVip = "VIP";
						}
					}

					if(!empty($val['case_ticket'])){
						foreach($val['case_ticket'] as $v){
							if(isset($Type[$v['type_id']])){
								$TypeCase .= $Type[$v['type_id']].', ';
							}
						}
					}

					if(!empty($val['refer'])){
						foreach($val['refer'] as $v){
							$ReferCode .= $v['code'].', ';
							if(($v['type']   == 1) && isset($Refer[$v['code']])){

								if(isset($Courier[$Refer[$v['code']]['courier_id']])){
									$Hvc    .= $Courier[$Refer[$v['code']]['courier_id']].', ';
								}

								if(isset($City[$Refer[$v['code']]['from_city_id']])){
									$ThanhPho    .= $City[$Refer[$v['code']]['from_city_id']].', ';
								}

								if(isset($District[$Refer[$v['code']]['from_district_id']])){
									$QuanHuyen    .= $District[$Refer[$v['code']]['from_district_id']].', ';
								}

								if(isset($Refer[$v['code']]['to_order_address']['address'])){
									$DcNhan .= $Refer[$v['code']]['to_order_address']['address'].', ';
								}

								if(isset($Refer[$v['code']]['from_address'])){
									$DcGui .= $Refer[$v['code']]['from_address'].', ';
								}

								if(isset($Refer[$v['code']]['to_order_address']['city_id']) && isset($City[$Refer[$v['code']]['to_order_address']['city_id']])){
									$ThanhPhoNhan .= $City[$Refer[$v['code']]['to_order_address']['city_id']].', ';
								}

								if(isset($Refer[$v['code']]['to_order_address']['province_id']) && isset($District[$Refer[$v['code']]['to_order_address']['province_id']])){
									$QuanHuyenNhan .= $District[$Refer[$v['code']]['to_order_address']['province_id']].', ';
								}
							}
						}
					}

					if(!empty($val['assign'])){
						foreach($val['assign'] as $v){
							if(isset($User[(int)$v['assign_id']])){
								$Assign .= $User[(int)$v['assign_id']]['email'].', ';

								if(!empty($v['time_create']) && empty($AssginTime[$v['ticket_id']])){
								  $AssginTime[$v['ticket_id']] = $v['time_create'];
								}
							}
						}
					}

					$timeTiepNhan = "";
					if((int)$val['time_reply'] > 0){
					  $timeTiepNhan = (int)$val['time_create'] + (int)$val['time_reply'];
					  $timeTiepNhan = date("m/d/y H:i", $timeTiepNhan);
					}


					$dataExport = array(
						$i++,
						(int)$val['id'],
						$UserCreate,
						$UserVip,
						date("m/d/y H:i",$val['time_create']), // Thoi gian tao
						$timeTiepNhan, // Thoi gian tiep nhan
						date("m/d/y H:i",$val['time_over']),
						isset($Logs[(int)$val['id']]) ? date("m/d/y H:i", $Logs[(int)$val['id']]['time_create']) : '', // Thoi gian xu ly xong
						isset($AssginTime[(int)$val['id']]) ?  date("m/d/y H:i", $AssginTime[(int)$val['id']]): '',
						isset($LogProcess[(int)$val['id']]) ? date("m/d/y H:i", $LogProcess[(int)$val['id']]['time_create']) : '', // Thoi gian Phan loai
						$val['type']    == 1 ? 'Đã phân loại' : 'Chưa phân loại',
						$QuaHanXuly,
						$this->list_priority[(int)$val['priority']],
						$this->SwearFilter($val['title']),
						$this->SwearFilter($val['content']),
						$this->list_status[$val['status']],
						$TypeCase,
						$ReferCode,
						$Hvc,
						$ThanhPho,
						$QuanHuyen,
						$DcGui,
						$ThanhPhoNhan,
						$QuanHuyenNhan,
						$DcNhan,
						$Assign
					);
					$sheet->appendRow($dataExport);
				}
			});

			$reader->sheet(1,function($sheet) use($Data,$Type,$User, $Logs)
			{
				$i = 1;
				foreach ($Data AS $val) {
					$TypeCase   = '';
					$Refer      = '';
					$Assign     = '';
					$UserCreate = '';
					$UserVip    = '';

					if($this->isDuplicateTicket($val['refer'])){


						if(isset($User[(int)$val['user_id']])){
							$UserCreate = $User[(int)$val['user_id']]['email'];
						}

						if(isset($User[(int)$val['user_id']])){
							if($User[(int)$val['user_id']]['user_info']['is_vip'] == 1){
								$UserVip = "VIP";
							}

						}

						if(!empty($val['case_ticket'])){
							foreach($val['case_ticket'] as $v){
								if(isset($Type[$v['type_id']])){
									$TypeCase .= $Type[$v['type_id']].', ';
								}
							}
						}

						if(!empty($val['refer'])){
							foreach($val['refer'] as $v){
								$Refer .= $v['code'].', ';
							}
						}

						if(!empty($val['assign'])){
							foreach($val['assign'] as $v){
								if(isset($User[(int)$v['assign_id']])){
									$Assign .= $User[(int)$v['assign_id']]['email'].', ';
								}
							}
						}

						$dataExport = array(
							$i++,
							(int)$val['id'],
							$UserCreate,
							$UserVip,
							date("m/d/y H:i",$val['time_create']),
							date("m/d/y H:i",$val['time_over']),
							isset($Logs[(int)$val['id']]) ? date("m/d/y H:i", $Logs[(int)$val['id']]['time_create']) : '',
							$this->list_priority[(int)$val['priority']],
							$val['title'],
							$val['content'],
							$this->list_status[$val['status']],
							$TypeCase,
							$Refer,
							$Assign
						);
						$sheet->appendRow($dataExport);
					}

				}
			});

		},'UTF-8',true)->export('xlsx');
	}

		//
	public function getTicketbyprivilege(){
		
		$UserInfo   = $this->UserInfo();
		$id = (int)$UserInfo['id'];

		if($UserInfo['privilege'] == 0){
			$contents = array(
				'error'         => true,
				'message'       => Lang::get('response.PERMISSION_DENIED_OPS'),
				'data'          => ''
			);
			return Response::json($contents);
		}

		//
		$page       = Input::has('page')        ? (int)Input::get('page')                   : 1;
		$itemPage   = Input::has('limit')       ? (int)Input::get('limit')                  : 20;
		$TimeStart  = Input::has('time_start')  ? (string) trim(Input::get('time_start'))            : 0;
		$Priority   = Input::has('priority')    ? (int)Input::get('priority')               : 0;
		$offset     = ($page - 1)*$itemPage;

		$Status     = Input::has('status')      ? strtoupper(trim(Input::get('status')))    : 'ALL';
		$Search     = Input::has('search')      ? Input::get('search')                      : '';

		$Type       = Input::has('type_ticket') ? (int)Input::get('type_ticket')            : 0;
		$CaseID     = Input::has('case')        ? (int)Input::get('case')                   : '';

		$byAssigner = Input::has('by_assigner') ? Input::get('by_assigner')                 : 0;
		$isVip      = Input::has('isVip')       ? Input::get('isVip')                       : 0;

		//Phân loại ticket đã phân loại
		$TypeProcess    = Input::has('type_process')    ? (int)Input::get('type_process')   : null;

		$TimeCStart     = Input::has('time_create_start')  ? trim(Input::get('time_create_start')) : 0;
		$TimeEStart     = Input::has('time_create_end')  ? trim(Input::get('time_create_end'))   : 0;

		$Cmd        = Input::has('cmd')         ? strtoupper(trim(Input::get('cmd')))       : null;

		$Model          = new RequestModel;
		$TimeNow        = $this->time();
		$timeOver       = 0;
		$ListRefer      = [];

		if($TimeStart === 'lastday'){
			$TimeCreateStart    = strtotime("-1 day 00:00:00");
			$Model              = $Model->where('time_create','<',strtotime("-1 day 23:59:59"));
			$FileName           = 'Khieu_nai_mot_ngay_truoc';
		}else if($TimeStart === 'now'){
			$TimeCreateStart    = strtotime("now 00:00:00");
			$FileName           = 'Khieu_nai_trong_ngay';
		}else if($TimeStart > 0) {
			$TimeCreateStart = $TimeNow - $TimeStart*86400;
			$FileName           = 'Khieu_'.$TimeStart.'_ngay_truoc';
		}else {
			$TimeCreateStart = $TimeNow - 30*86400;
			$FileName           = 'Khieu_thang_truoc';
		}

		if(!empty($TimeCStart)){
			$Model              = $Model->where('time_create','>=',$TimeCStart);
		}else{
			$Model          = $Model->where('time_create','>=',$TimeCreateStart);
		}

		if(!empty($TimeEStart)){
			$Model              = $Model->where('time_create','<',$TimeEStart);
		}

		if($TimeStart === 'overtime'){
			$Model          = $Model->where('time_over','>',0)->where(function($query) use($TimeNow){
				$query->where(function($q){
					$q->where('status','CLOSED')
					   ->whereRaw('time_over < time_update');
				})->orWhere(function($q) use($TimeNow){
					$q->where('status','<>','CLOSED')
						->where('time_over','<=',$TimeNow);
				});
			});
			$FileName       = 'Khieu_nai_qua_han';
		} elseif($TimeStart === 'over1day') {
			$FileName       = 'Khieu_nai_sap_het_han';
			$timeOver = $TimeNow + 86400;
			$Model = $Model->where('time_over','>=',$TimeNow);
		} elseif($TimeStart === 'over2day') {
			$FileName       = 'Khieu_nai_sap_het_han_2_ngay';
			$timeOver = $TimeNow + 2*86400;
			$Model = $Model->where('time_over','>=',$TimeNow + 86400);
		}

		if($timeOver > 0) {
			$Model = $Model->where('time_over','<=',$timeOver);
		}
		if(!empty($Priority)){
			$Model  = $Model->where('priority', $Priority);
		}

		//CheckCase
		if(!empty($Type) || $CaseID>0){
			$listTypeIDs = [];
			if($CaseID>0) {
				$listTypeIDs = CaseTypeModel::where('case_id',$CaseID)->lists('id');
			}
			if(!empty($Type)) {
				$listTypeIDs[] = $Type;
			}
			$CaseTicketModel    = new CaseTicketModel;
			$ListTicketId       = $CaseTicketModel->whereIn('type_id',$listTypeIDs)->where('active',1)->lists('ticket_id');
			if(empty($ListTicketId)){
				return $this->ResponseData();
			}
		}

		//Check Vip
		if($isVip==1) { //Khách hàng vip
			$listVipUserID = UserInfoModel::where('is_vip',1)->lists('user_id');
			if(!empty($listVipUserID)){
				$Model = $Model->whereIn('user_id', $listVipUserID);
			}else{
				return $this->ResponseData();
			}
		}elseif($isVip==2) { //Yêu cầu do tôi assign
			$ListTicketAssign   = AssignModel::where('user_id',$UserInfo['id'])->where('time_create','>=',$TimeCreateStart)->where('active',1)->lists('ticket_id');
			if(!empty($ListTicketAssign)){
				if(!empty($ListTicketId)){
					$ListTicketId = array_intersect($ListTicketId,$ListTicketAssign);
				}else{
					$ListTicketId = $ListTicketAssign;
				}
			}

			if(empty($ListTicketAssign) || empty($ListTicketId)){
				return $this->ResponseData();
			}
		}
		elseif($isVip==3) { // Yêu cầu assign cho tôi
			$ListTicketAssign = AssignModel::where('assign_id',$UserInfo['id'])->where('time_create','>=',$TimeCreateStart)->where('active',1)->lists('ticket_id');
			if(!empty($ListTicketAssign)){
				if(!empty($ListTicketId)){
					$ListTicketId = array_intersect($ListTicketId,$ListTicketAssign);
				}else{
					$ListTicketId = $ListTicketAssign;
				}
			}

			if(empty($ListTicketAssign) || empty($ListTicketId)){
				return $this->ResponseData();
			}
		}elseif($isVip == 4){ // Yêu cầu chưa assign cho ai
			$ListAssignTicketId = AssignModel::where('time_create','>',($TimeCreateStart - 30*86400))->where('active',1)->groupBy('ticket_id')->having('count', '>', 1)->get(array('ticket_id',DB::raw('count(*) as count')));

			if(!empty($ListAssignTicketId)){
				$ListIdNotIn    = [];
				foreach($ListAssignTicketId as $val){
					$ListIdNotIn[]  = (int)$val['ticket_id'];
				}

				$Model = $Model->whereRaw("id not in (". implode(",", $ListIdNotIn) .")");
			}
		}elseif($isVip == 5){ // đã giao cho HVC
			$ListCourier  = UserInfoModel::where('courier_id','>',0)->where('privilege','>',0)->remember(60)->lists('user_id');
			if(empty($ListCourier)){
				return $this->ResponseData();
			}

			$AssignModel    = new AssignModel;
			$ListCourier    = $AssignModel::where('time_create', '>=', $TimeCreateStart)->where('active',1)
										  ->whereIn('assign_id', $ListCourier)
										  ->lists('ticket_id');
			if(empty($ListCourier)){
				return $this->ResponseData();
			}

			if(!empty($ListCourier)){
				if(!empty($ListTicketId)){
					$ListTicketId = array_intersect($ListTicketId,$ListCourier);
				}else{
					$ListTicketId = $ListCourier;
				}
			}

			if(empty($ListCourier) || empty($ListTicketId)){
				return $this->ResponseData();
			}
		}elseif($isVip == 6){ // Khách hàng mới
			$ListUserID = User::where('time_create', '>=', $this->time() - 30 * 86400)->lists('id');

			if(!empty($ListUserID)){
				$Model = $Model->whereIn('user_id', $ListUserID);
			}else{
				return $this->ResponseData();
			}
		}elseif($isVip == 7){ // Khách hàng Loyalty
			$ListUserID = new \loyaltymodel\UserModel;
			$ListUserID = $ListUserID->lists('user_id');

			if(!empty($ListUserID)){
				$Model = $Model->whereIn('user_id', $ListUserID);
			}else{
				return $this->ResponseData();
			}
		}

		if(!empty($byAssigner)){ // giao cho ai
			$AssignModel    = new AssignModel;
			$byAssigner = explode(',', $byAssigner);
			$ListTicketAssign = $AssignModel::whereIn('assign_id',$byAssigner)->where('time_create','>=',$TimeCreateStart)->where('active',1)->lists('ticket_id');
			if(!empty($ListTicketAssign)){
				if(!empty($ListTicketId)){
					$ListTicketId = array_intersect($ListTicketId,$ListTicketAssign);
				}else{
					$ListTicketId = $ListTicketAssign;
				}
			}

			if(empty($ListTicketAssign) || empty($ListTicketId)){
				return $this->ResponseData();
			}
		}

		if(!empty($Search)){
			$UserModel  = new User;
			if (filter_var($Search, FILTER_VALIDATE_EMAIL)){ // search email
				$DataUser          = $UserModel::where('email',$Search)->lists('id');
			}elseif(filter_var((int)$Search, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>1,'max_range'=>5)))){
				$Model  = $Model->where('id',$Search);
			}elseif(filter_var((int)$Search, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
				$DataUser          = $UserModel::where('phone',$Search)->lists('id');
			}else{ // search code
				$ReferModel     = new ReferModel;
				$ListRefer      = $ReferModel->where('code',$Search)->lists('ticket_id');

				if(empty($ListRefer)){
					return $this->ResponseData();
				}

				//$Model = $Model->whereRaw("id in (". implode(",", $ListRefer) .")");   ;
			}

			if(isset($DataUser)){
				if(!empty($DataUser)){
					$Model  = $Model->whereIn('user_id',$DataUser);
				}else{
					return $this->ResponseData();
				}
			}
		}

		//Phân loại
		if(isset($TypeProcess)){
			$Model  = $Model->where('type',$TypeProcess);
		}

		if(in_array($UserInfo['privilege'], [1,2])){
			if(!empty($ListTicketId)){
				if(!empty($ListRefer)){
					$ListTicketId = array_intersect($ListTicketId, $ListRefer);

					if(empty($ListTicketId)){
						return $this->ResponseData();
					}
				}

				$Model  = $Model->whereRaw("id in (". implode(",", $ListTicketId) .")");
			}elseif(!empty($ListRefer)){
				$Model  = $Model->whereRaw("id in (". implode(",", $ListRefer) .")");
			}
		}else{
			$AssignModel        = new AssignModel;
			$AssignModel         = $AssignModel->where('time_create','>=',$TimeCreateStart)->where('active',1);

			if($UserInfo['privilege'] == 3){
				$ListAssign         = $AssignModel->where('assign_id',$id)->lists('ticket_id');
			}elseif(!empty($UserInfo['courier_id']) && $isVip != 3){
				$UserInfoModel  = new UserInfoModel;
				$ListUserInfo   = [];

				if($UserInfo['privilege'] == 4){ // Giám đốc bưu cục - quản lý quận
					if(!empty($UserInfo['post_office_id'])){
						$ListUserInfo   = $UserInfoModel::where('courier_id', $UserInfo['courier_id'])
														->where('privilege',3)
														->where('post_office_id', $UserInfo['post_office_id'])
														->remember(60)->lists('user_id');
					}elseif(!empty($UserInfo['location_id'])){
						$ListPostOffice = \CourierPostOfficeModel::where('courier_id', $UserInfo['courier_id'])
																 ->where('district_id', $UserInfo['location_id'])
																 ->remember(60)
																 ->lists('id');

						if(!empty($ListPostOffice)){
							$ListUserInfo   = $UserInfoModel::where('courier_id', $UserInfo['courier_id'])
															->whereIn('privilege',[3,4])
															->whereIn('post_office_id', $ListPostOffice)
															->remember(60)->lists('user_id');
						}
					}
				}elseif($UserInfo['privilege'] == 5){ // QUản lý cấp tỉnh
					if(!empty($UserInfo['location_id'])){
						$ListPostOffice = \CourierPostOfficeModel::where('courier_id', $UserInfo['courier_id'])
																->where('city_id', $UserInfo['location_id'])
																->lists('id');

						if(!empty($ListPostOffice)){
							$ListUserInfo   = $UserInfoModel::where('courier_id', $UserInfo['courier_id'])
								->whereIn('privilege',[3,4])
								->whereIn('post_office_id', $ListPostOffice)
								->remember(60)->lists('user_id');
						}
					}
				}

				$ListUserInfo[]     = (int)$UserInfo['id'];
				$ListAssign         = $AssignModel->whereIn('assign_id',$ListUserInfo)->lists('ticket_id');
			}

			if(!empty($ListAssign)){
				if(!empty($ListTicketId)){
					$ListTicketId   = array_intersect($ListTicketId, $ListAssign);
					if(empty($ListTicketId)){
						return $this->ResponseData();
					}
				}else{
					$ListTicketId   = $ListAssign;
				}
			}

			if(empty($ListAssign) || empty($ListTicketId)){
				return $this->ResponseData();
			}

			if(!empty($ListRefer)){
				$ListTicketId = array_intersect($ListTicketId, $ListRefer);
				if(empty($ListTicketId)){
					return $this->ResponseData();
				}
			}

			$Model  = $Model->where(function($query) use($id, $ListTicketId, $UserInfo, $ListRefer){
				$query = $query->whereRaw("id in (". implode(",", $ListTicketId) .")")
						->orWhere('user_id',$id);
			if($UserInfo['courier_id'] == 0 && empty($ListRefer)){
				$query->orWhere('status','NEW_ISSUE');
			}
			});
		}

		if($Cmd == 'EXPORT'){
			if($Status != 'ALL'){
				$Model = $Model->where('status','=',$Status);
			}

			$Data   = [];

			$Model->orderBy('time_create','DESC')
				->with([
					'refer',
					'case_ticket'   => function($query) {
						$query->where('active',1);
					},
					'assign'       => function($query){
						$query->where('active',1);
					}
				])->chunk('1000', function($query) use(&$Data){
				foreach($query as $val){
					$Data[]             = $val->toArray();
				}
			});
			return $this->ExportData($FileName, $Data);
		}

		/*
		 * count group
		 */
		$ModelTotal = clone $Model;
		$Total         = $ModelTotal->groupBy('status')->get(array('status',DB::raw('count(*) as count')));

		$TotalAll   = 0;
		$TotalGroup = array('ALL' => 0, 'NEW_ISSUE' => 0, 'ASSIGNED' => 0, 'PENDING_FOR_CUSTOMER' => 0, 'CUSTOMER_REPLY' => 0,'PROCESSED' => 0, 'CLOSED' => 0);
		if(!empty($Total)){
			foreach($Total as $val){
				$TotalAll                   += $val['count'];
				$TotalGroup[$val['status']]  = $val['count'];
			}
		}
		$TotalGroup['ALL']  = $TotalAll;

		// get data
		if($Status != 'ALL'){
			$Model = $Model->where('status','=',$Status);
		}

		$Data       = [];
		$DataAction = [];

		$ModelData  = clone $Model;
		$ModelCount = clone $Model;

		if(in_array($Status,['PROCESSED','NEW_ISSUE'])){
			$Data = $ModelData->orderBy('time_update', 'ASC')
				->skip($offset)
				->take($itemPage)
				->with(['refer','users'])->get()->toArray();
		}else{
			if(empty($Search)){
				$ModelData  =   $ModelData->where('user_last_action','<>',$id);
				$ModelCount =   $ModelCount->where('user_last_action','<>',$id);
			}

			$CountData  = $ModelCount->count();
			if($CountData > 0){
				$Data = $ModelData//->orderBy('time_update', 'DESC')
					->orderBy('priority','DESC')
					->orderBy('time_create','DESC')
					->skip($offset)
					->take($itemPage)
					->with(['refer','users'])->get()->toArray();
			}

			if((int)$itemPage > 0 && count($Data) < $itemPage && (count($Data) + $offset) < $TotalGroup[$Status]){
				$DataAction = $Model->where('user_last_action',$id)
					//->orderBy('time_update', 'DESC')
					->orderBy('priority','DESC')
					->orderBy('time_create','DESC')
					->skip(floor(($offset - $CountData)/$itemPage))
					->take($itemPage - count($Data))
					->with(['refer','users'])->get()->toArray();
			}
		}

		if(empty($Data) && !empty($DataAction)){
			$Data   = $DataAction;
		}elseif(!empty($Data) && !empty($DataAction)){
			$Data   = array_merge($Data, $DataAction);
		}

		if($Data){
			$ListIdTicket       = [];
			foreach($Data as $key => $val){
				$Data[$key]['time_before']          = $this->ScenarioTime(($TimeNow - $val['time_create']));
				$Data[$key]['time_update_before']   = $this->ScenarioTime(($TimeNow - $val['time_update']));
				$Data[$key]['time_over_before']     = $this->ScenarioTime(($val['time_over'] - $TimeNow));
				$ListIdTicket[] = (int)$val['id'];
			}

			if(!empty($ListIdTicket)){
				$LogViewModel   = new LogViewModel;
				$this->log_view = $LogViewModel->where('user_id',$id)
					->whereIn('ticket_id',$ListIdTicket)
					->get()->toArray();
			}
		}

		$this->data         = $Data;
		$this->total        = $TotalAll;
		$this->total_group  = $TotalGroup;
		return $this->ResponseData();
	}


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

		$validation = Validator::make(Input::json()->all(), array(
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
		$Data       = Input::json()->get('data');
		$Title      = $Data['title'];
		$Content    = $Data['content'];
		$Contact    = (int)Input::json()->get('customer_id');
		$DataType   = Input::json()->get('type_id');
		$ListAssign = [];
		$InsertLog  = [];

		$DataInsert = array(
			'title' => $Title,
			'content' => $Content,
			'source' => 'web',
			'time_create' => $this->time(),
			'time_update' => $this->time(),
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


				if (!empty($Type)) {
					if (!empty($Type['estimate_time'])) {
						$DataInsert['time_over'] = $this->time() + $timeBonus;
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

		/** Giao cho 1 chăm sóc bất kỳ
		if (!empty($DataType)) {
			$CS             = [43792,45614,52755,52923,53206,53988];
			$ran            = array_rand($CS,1);
			if(!empty($ListAssign)){
				$ListAssign     = array_unique($ListAssign);
				$AssignCS       = array_intersect($ListAssign, $CS);
				if(empty($AssignCS)){
					$ListAssign[]   = $CS[$ran];
				}
			}else{
				$ListAssign[]   = $CS[$ran];
			}
		}**/

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
				'time_create'   => $this->time(),
				'user_id'       => (int)$UserInfo['id'],
				'type'          => 'status'
			];
		}

		// Assign
		if ($ListAssign) {
			$InsertAssign   			= [];
			$ListAssign     			= array_unique($ListAssign);
			$DataInsert['time_receive']	= $DataInsert['time_create'];
			$AssignModel    = new AssignModel;
			foreach ($ListAssign as $val) {
				$InsertAssign[] =[
					'ticket_id' => (int)$Insert,
					'assign_id' => (int)$val,
					'user_id'   => (int)$UserInfo['id'],
					'active'    => 1,
					'time_create' => $this->time(),
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
					'time_create'   => $this->time(),
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
				'time_create'   => $this->time(),
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
				'message' => Lang::get('response.INSERT_LOG_FAIL')
			]);
		}

		DB::connection('ticketdb')->commit();

		$contents = array(
			'error' => false,
			'message' => Lang::get('response.SUCCESS'),
			'id' => $Insert
		);
		return Response::json($contents);
	}


	public function getOrderRefer($id){
		$validation = Validator::make(array('id' => $id), array(
			'id'        => 'required|numeric|min:1'
		));

		//error
		if($validation->fails()) {
			return Response::json(array('error' => true, 'message' => $validation->messages()));
		}

		$UserInfo   = $this->UserInfo();
		$TimeNow    = $this->time();
		$ReferModel = new ReferModel;
		$ReferModel = $ReferModel->where('ticket_id', $id)->where('type', 1)->get();

		$listTrackingCode = [];
		foreach ($ReferModel as $key => $value) {
			$listTrackingCode[] = $value['code'];
		}

		if(empty($listTrackingCode)){
			return Response::json(['error'=> false, "message" => "", "data" => []]);
		}

		$OrderModel     = new OrdersModel;
		$Districts      = [];
		$Cities         = [];
		$ListToAddress  = [];
		$Address        = [];
		$ListProvinceId = [];
		$ListCityID = [];



		$ListOrder  = $OrderModel::where(function($query) {
			$query->where('time_accept','>=', $this->time() - 86400*90)
				->orWhere('time_accept',0);
		})
		->whereIn('tracking_code',$listTrackingCode)
		->with(['Courier', 'MetaStatus', 'pipe_journey'])
		->get(array('id', 'tracking_code', 'status', 'courier_id', 'from_city_id', 'from_district_id', 'to_phone', 'to_email','to_name',  'status','courier_tracking_code', 'to_address_id', 'time_create', 'time_update'))
		->toArray();


		foreach ($ListOrder as $key => $val) {
			$ListToAddress[]    = (int)$val['to_address_id'];

		}

		if(!empty($ListToAddress)){
			$AddressModel   = new AddressModel;
			$ListAddress    = $AddressModel::whereIn('id',$ListToAddress)->get()->toArray();
			if(!empty($ListAddress)){
				foreach($ListAddress as $val){
					$Address[(int)$val['id']]    = $val;
					$ListProvinceId[]   = (int)$val['province_id'];
					$ListCityID[]   = (int)$val['city_id'];
				}
			}
		}

		if(!empty($ListProvinceId)){
			$District = $this->getProvince($ListProvinceId);
		}


		$contents = array(
			'error'         => false,
			'message'       => Lang::get('response.SUCCESS'),
			'data'          => $ListOrder,
			'district'      => isset($District) ? $District : [],
			'address'       => isset($Address) ? $Address : $this->address
		);

		return Response::json($contents);
	}



	public function getShow($id)
	{
	   /**
		*  Validation params
		* */

		$validation = Validator::make(array('id' => $id), array(
			'id'        => 'required|numeric|min:1'
		));

		//error
		if($validation->fails()) {
			return Response::json(array('error' => true, 'message' => $validation->messages()));
		}


		// List Courier

		$UserInfo   = $this->UserInfo();
		$TimeNow    = $this->time();
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
				$User       = $UserModel->whereIn('id',$ListUser)->with(['user_info', 'loyalty'])->get(array('id', 'identifier', 'email', 'fullname', 'phone', 'time_create', 'time_last_login'))->toArray();
			}

			// Update Log View
			$LogViewModel   = new LogViewModel;
			$LogView        = $LogViewModel::firstOrNew(['ticket_id' => $id, 'user_id' => (int)$UserInfo['id']]);

			if(!$LogView->exists || ($LogView->exists && $LogView->view == 0)){
				if(!$LogView->exists){
					$LogView->time_create = $this->time();
				}
				$LogView->view  = 1;
				$LogView->save();
			}



			$timeStart = $this->time() - 30*86400;
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
					$oneTicketByUser->time_create_str = $this->ScenarioTime($this->time() - $oneTicketByUser->time_create);
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
			/*if($UserInfo['privilege'] < 1){*/
/*            $ListDuplicateTick = ReferModel::where('code',$id)->where('type',3)->lists('ticket_id');
			$Data['duplicate'] = $ListDuplicateTick;
*/            /*}*/
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
							$user->identifier." ".$user['fullname'],
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
				'message'       => Lang::get('response.SUCCESS'),
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
				'message'   => Lang::get('response.DATA_EMPTY')
			);
		}

		return Response::json($contents);
	}


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
				'message' =>  Lang::get('response.ASSIGN_TO_STAFF')
			]);
		}
		$Model      = new RequestModel;
		$Data       = $Model::find($id);
		$UserInfo   = $this->UserInfo();


		// Update  ticket  status closed
		if($Data->status == 'CLOSED' && (int)$UserInfo['privilege'] != 2 && (int)$UserInfo['group'] != 15){
			$contents = array(
				'error'     => true,
				'message'   => Lang::get('response.USER_NOT_ALLOWED')
			);
			return Response::json($contents);
		}


		if ($Data->user_id == $UserInfo['id'] && !empty($Status) && !in_array($Status, ['PENDING_FOR_CUSTOMER', 'CLOSED'])) {
		  $contents = array(
			  'error'     => true,
			  'message'   => Lang::get('response.USER_NOT_ALLOWED')
		  );
		  return Response::json($contents);
		}


		// Update status to closed
		if($Status == 'CLOSED' &&  !$this->check_privilege('PRIVILEGE_TICKET','edit') && $Data->user_id != $UserInfo['id']){
			$contents = array(
				'error' => true,
				'message' => Lang::get('response.USER_NOT_ALLOWED')
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

		$Data->time_update      = $this->time();
		$Data->user_last_action = (int)$UserInfo['id'];
		$Update                 = $Data->save();

		if($Update){
			$this->InsertLog($id, $this->data_old, $this->data_new, $this->field);
			$contents = array(
				'error'     => false,
				'message'   => Lang::get('response.SUCCESS'),
				'time_over' =>  $Data->time_over
			);
		}else{
			$contents = array(
				'error' => true,
				'message' => Lang::get('response.UPDATE_TICKET_FAIL')
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
			return array('error' => true, 'message' => Lang::get('response.FAIL_QUERY'));
		}
		return array('error' => false, 'message' => Lang::get('response.SUCCESS'));
	}

	public function InsertMultiLog($Data){
		try {
			$Create = LMongo::collection('log_change_ticket')->batchInsert($Data);
		} catch (Exception $e) {
			return false;
		}
		return true;
	}

	public function getExcel(){
		$Status     = Input::has('status')      ? strtoupper(trim(Input::get('status')))    : 'ALL';
		$UserId     = Input::has('user_id')     ? (int)Input::get('user_id')                : 0;
		$TimeStart  = Input::has('time_start')  ? trim(Input::get('time_start'))            : $this->time() - 86400*30;
		$TimeEnd    = Input::has('time_end')    ? trim(Input::get('time_end'))              : $this->time();
		$Type       = Input::has('type_ticket') ? (int)Input::get('type_ticket')            : 0;
		$CaseID     = Input::has('case')        ? (int)Input::get('case')                   : 0;
		$OverTime   = Input::has('over_time')   ? (int)Input::get('over_time')              : null;

		$Data       = [];

		$Model  = new RequestModel;

		if(!empty($TimeStart)){
			$Model = $Model->where('time_create','>=',$TimeStart);
		}
		if(!empty($TimeEnd)){
			$Model = $Model->where('time_create','<=',$TimeEnd);
		}

		if($Status != 'ALL'){
			$Model = $Model->where('status','=',$Status);
		}

		$User       = [];
		$Data       = [];

		if($UserId > 0){
			$AssignModel    = new AssignModel;
			$ListAssign         = $AssignModel->where('time_create','>=',$TimeStart)->where('time_create','<',$TimeEnd + 86400*60)->where('assign_id', $UserId)->where('active',1);

			if(!empty($Type) || $CaseID>0){
				$listTypeIDs = array();
				if($CaseID>0) {
					$listTypeIDs = CaseTypeModel::where('case_id',$CaseID)->lists('id');
				}
				if(!empty($Type)) {
					$listTypeIDs[] = $Type;
				}
				$CaseTicketModel    = new CaseTicketModel;
				$ListCase           = $CaseTicketModel->whereIn('type_id',$listTypeIDs)->where('active',1)->get(['ticket_id','type_id','active'])->toArray();
				$ListTicketId[]           = 0;
				if(!empty($ListCase)){
					foreach($ListCase as $val){
						$ListTicketId[]   = $val['ticket_id'];
					}
				}

				if(!empty($ListTicketId)){
					$ListAssign = $ListAssign->whereIn('ticket_id', $ListTicketId);
				}
			}

			$ListAssign = $ListAssign->lists('ticket_id');
		}

		if(!empty($ListAssign)){
			if(isset($OverTime)){
				if($OverTime == 1){
					$Model  = $Model->where('time_over','>',0)->whereRaw('time_update > time_over');
				}
			}

			$Data  = $Model->whereIn('id', $ListAssign)->orderBy('time_create','DESC')
				->with(array(
					'refer',
					'case_ticket'   => function($query){
						$query->where('active',1);
					},
					'assign'       => function($query){
						$query->where('active',1);
					}
				))->get()->toArray();

			$Type       = [];
			if(!empty($Data)){
				if(Cache::has('case_type_ticket')){
					$ListType = Cache::get('case_type_ticket');
				}else{
					$ListType  = CaseTypeModel::where('active',1)->get()->toArray();
				}
				$ListUserId = [];

				foreach($ListType as $val){
					$Type[(int)$val['id']]  = $val['type_name'];
				}

				foreach($Data as $oneUser) {
					$ListUserId[] = $oneUser['user_id'];
					if(!empty($oneUser['assign'])) {
						foreach($oneUser['assign'] as $oneAssign) {
							$ListUserId[] = $oneAssign['assign_id'];
						}
					}
				}

				$ListUserId = array_unique($ListUserId);
				if(!empty($ListUserId)){
					$UserModel  = new User();
					$ListUser   = $UserModel->whereIn('id',$ListUserId)->get(array('id','email'));
					if(!empty($ListUser)){
						foreach($ListUser as $val){
							$User[(int)$val['id']]  = $val;
						}
					}
				}

			}
		}

		return Excel::create('Danh_sach_yeu_cau', function($excel) use($Data,$Type,$User){
			$excel->sheet('Sheet1', function($sheet) use($Data,$Type,$User){
				$sheet->mergeCells('E1:G1');
				$sheet->row(1, function ($row) {
					$row->setFontSize(20);
				});
				$sheet->row(1, array('','','','','Danh sách khiếu nại'));

				$sheet->row(3, array(
					'STT', '#ID', 'Người tạo', 'Thời gian tạo', 'Thời gian xử lý', 'Mức độ', 'Tiêu đề', 'Nội dung', 'Trạng thái', 'Trường hợp', 'Mã tham chiếu', 'Người xử lý'
				));
				$sheet->row(3,function($row){
					$row->setBackground('#989898');
					$row->setFontSize(12);
				});
				$sheet->setBorder('A3:K3', 'thin');

				$i = 1;
				foreach ($Data AS $val) {
					$TypeCase   = '';
					$Refer      = '';
					$Assign     = '';
					$UserCreate = '';

					if(isset($User[(int)$val['user_id']])){
						$UserCreate = $User[(int)$val['user_id']]['email'];
					}

					if(!empty($val['case_ticket'])){
						foreach($val['case_ticket'] as $v){
							if(isset($Type[$v['type_id']])){
								$TypeCase .= $Type[$v['type_id']].', ';
							}
						}
					}

					if(!empty($val['refer'])){
						foreach($val['refer'] as $v){
							$Refer .= $v['code'].', ';
						}
					}

					if(!empty($val['assign'])){
						foreach($val['assign'] as $v){
							if(isset($User[(int)$v['assign_id']])){
								$Assign .= $User[(int)$v['assign_id']]['email'].', ';
							}
						}
					}

					$dataExport = array(
						$i++,
						(int)$val['id'],
						$UserCreate,
						date("m/d/y H:i",$val['time_create']),
						date("m/d/y H:i",$val['time_over']),
						$this->list_priority[(int)$val['priority']],
						$this->SwearFilter($val['title']),
						$this->SwearFilter($val['content']),
						$this->list_status[$val['status']],
						$TypeCase,
						$Refer,
						$Assign
					);
					$sheet->appendRow($dataExport);
				}
			});
		})->export('xlsx');
	}

	public function getOver(){
		$start      = Input::has('start') ? Input::get('start') : strtotime('first day of last month');
		$end        = Input::has('end')   ? Input::get('end')   : strtotime('first day of this month');
		$Model      = new RequestModel;

		$listTicket   = [];
		$listTicketId = [];
		$Model        = $Model
					  ->where('time_create','>=', $start)
					  ->where('time_create','<=', $end)
					  ->where('time_over', '>', 0)
					  ->whereRaw('time_update > time_over')
					  ->select(['id', 'user_id', 'title', 'time_create', 'time_over'])->get()->toArray();

		if(empty($Model)){
			return Response::json([
				'errr'          => false,
				'error_message' => Lang::get('response.NOT_DATA'),
				'data'          => []
			]);
		}
		foreach ($Model as $key => $value) {
			$listTicketId[] = $value['id'];
		}


		$MinID = min($listTicketId);
		$MaxID = max($listTicketId);



		$listLog    = LMongo::collection('log_change_ticket')->whereGte('id', $MinID)->whereLte('id',  $MaxID)->where('type', 'assign')->where('new.active', 0)->where('old.active', 1)->get()->toArray();


		$listData   = [];
		$listUserId = [];
		// Remove chính mình

		foreach ($listLog as $key => $value) {
			if($value['user_id'] !== $value['old']['assign_id']){
				foreach ($Model as $k => $val) {
					if($value['id'] == $val['id']){
						$listUserId[] = $value['user_id']; // người gỡ
						$listUserId[] = $value['old']['assign_id']; // người bị gỡ

						$data                       = $value;
						$data['ticket']             = $val;
						$listData[]                 = $data;
					}
				}

			}
		}
		$User = [];

		if(!empty($listUserId)){
			$listUserId = array_unique($listUserId);
			$UserModel  = \User::whereIn('id', $listUserId)->get()->toArray();
			foreach ($UserModel as $key => $value) {
				$User[$value['id']] = $value;
			}
		}



		return Excel::create('Report', function($excel) use($listData, $User){
			$excel->sheet('Sheet1', function($sheet) use($listData, $User){
				$sheet->mergeCells('E1:G1');
				$sheet->row(1, function ($row) {
					$row->setFontSize(20);
				});
				$sheet->row(1, array('','','','','Danh sách khiếu nại'));

				$sheet->row(3, array(
					'STT', '#ID', 'Thời gian tạo', 'Thời gian xử lý',  'Tiêu đề', 'Người gỡ tên',  'Người bị gỡ tên', 'Thời gian gỡ tên'
				));
				$sheet->row(3,function($row){
					$row->setBackground('#989898');
					$row->setFontSize(12);
				});
				$sheet->setBorder('A3:K3', 'thin');

				$i = 1;
				foreach ($listData AS $val) {
					if ($val['time_create'] > $val['ticket']['time_over']) {
						$dataExport = array(
							$i++,
							(int)$val['id'],
							date("m/d/y H:i", $val['ticket']['time_create']),
							date("m/d/y H:i", $val['ticket']['time_over']),
							$val['ticket']['title'],
							isset($User[$val['user_id']]) ? $User[$val['user_id']]['fullname']  : "",
							isset($User[$val['old']['assign_id']]) ? $User[$val['old']['assign_id']]['fullname'] : "",
							date("m/d/y H:i", $val['time_create']),
						);
						$sheet->appendRow($dataExport);
					}
				}
			});
		})->export('xlsx');
	}


	function getAutocloseTicket(){
		$time_start = Input::has('start')  ? Input::get('start') : 0;
		$time_end   = Input::has('end')    ? Input::get('end') : 0;

		$Model = new RequestModel;
		$Model = $Model->where('status', 'PROCESSED');

		if(empty($time_start)){
			$Model = $Model->where('time_create', '>=', $this->time() - 30 * 86400);
		}else {
			$Model = $Model->where('time_create', '>=', $time_start);
		}

		if(!empty($time_end)){
			$Model = $Model->where('time_create', '<=', $time_end);
		}

		$Model  = $Model->where('time_update', '<=', $this->time() - 1 * 86400);
		$Model  = $Model->take(100);
		$ListId = $Model->lists('id');

		if(empty($ListId)){
			return Response::json([
				'error'         => false,
				'error_message' => 'Hết rồi nhé :D'
			]);
		}

		try {
			RequestModel::whereIn('id', $ListId)->update(array('status'=>'CLOSED', 'time_update'=> $this->time()));
		} catch (Exception $e) {
			return Response::json([
				'error'         => false,
				'error_message' => 'Lỗi rồiiiiiiiiiii',
				'data'          => $e
			]);
		}
		$InsertLog = [];
		foreach ($ListId as $key => $value) {
			$InsertLog[]    = [
			  'id'      => $value,
			  'new'     => [
				  'status'  => 'CLOSED'
			  ],
			  'old'     => [
				  'status'  => 'PROCESSED'
			  ],
				'time_create'   => $this->time(),
				'user_id'       => 1,
				'type'          => 'status'
			];
		}

		if($this->InsertMultiLog($InsertLog)){
			return Response::json([
				'error'         => false,
				'error_message' => 'Thành công tén tén tèn..'
			]);
		};


		return Response::json([
			'error'         => false,
			'error_message' => 'Lỗi ghi log rồiiiiiiiiiii'
		]);




	}

	/*
	* Cập nhật trạng thái đã xử lý với yêu cầu phát lại
	*/



	public function getCron(){
		$time_start = Input::has('start')  ? Input::get('start') : 0;
		$time_end   = Input::has('end')    ? Input::get('end') : 0;

		$Model = new RequestModel;
		$Model = $Model->where('status', 'ASSIGNED');
		if(empty($time_start)){
			$Model = $Model->where('time_create', '>=', mktime(0, 0, 0));
		}else {
			$Model = $Model->where('time_create', '>=', $time_start);
		}

		if(!empty($time_end)){
			$Model = $Model->where('time_create', '<=', $time_end);
		}


		$Data = $Model
			->with([
				'refer'=> function ($query){
					$query->where('type', 1);
				}
			])
			->whereHas('case_ticket', function ($query){
					$query->where('type_id', 34);
			})
			->get();

		$count = 0;
		foreach ($Data as $tkey => $ticket) {
			$scCodes = [];
			foreach ($ticket['refer'] as $rkey => $refer) {
				$scCodes[] = $refer['code'];
			}

			if(!empty($scCodes)){
				$status = GroupOrderStatusModel::whereIn('group_status',array(41, 20))->lists('order_status_code');
				$Orders = new OrdersModel;
				$Orders = $Orders
						->whereIn('tracking_code', $scCodes)
						->where('time_update', '>', $ticket['time_create'])
						->whereNotIn('status', $status)
						->get()->toArray();
				if(!empty($Orders) && sizeof($scCodes) == sizeof($Orders)){

					$Data[$tkey]->status           = 'PROCESSED';
					$Data[$tkey]->time_update      = $this->time();
					$Data[$tkey]->user_last_action = 1;

					try {
						$Data[$tkey]->save();
					} catch (Exception $e) {
						return Response::json(['error' => true, 'error_message'=> $e->getMessage()]);
					}

					$DataInsert = array(
						'id'            => (int)$Data[$tkey]->id,
						'new'           => ['status'=> 'PROCESSED'],
						'old'           => ['status'=> 'ASSIGNED'],
						'time_create'   => $this->time(),
						'user_id'       => 1,
						'type'          => 'status'
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

					$count++ ;
				}
			}
		}
		return Response::json(['error_message'  =>  'DONE : '.$count]);



	}
}
