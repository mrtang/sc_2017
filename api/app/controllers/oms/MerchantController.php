<?php
namespace oms;

use Response;
use Exception;
use Input;
use Validator;
use Excel;
use DB;

use sellermodel\UserInfoModel;
use omsmodel\PipeStatusModel;
use omsmodel\PipeJourneyModel;
use omsmodel\SellerModel;

class MerchantController extends \BaseController
{
    private $error    = false;
    private $message  = 'Thành công';
    private $total    = 0;
    private $data     = [];
    private $district = [];

    public function getShow() {
        if(!$this->check_privilege('PRIVILEGE_SELLER','view')){
            return Response::json(array(
                "error" => true,
                "error_message" => "Bạn không có quyền thao tác.",
                "data"  => ""
            ));
        }
        return Response::json(array(
                "error" => true,
                "error_message" => "Bạn không có quyền thao tác.",
                "data"  => ""
        ));

        $group_process       = Input::has('group_process') 	        ? (int)Input::get('group_process') 	        : 200;
        $pipe_status         = Input::has('pipe_status')            ? trim(Input::get('pipe_status'))           : 0;
        $search              = Input::has('search') 	            ? trim(Input::get('search')) 	            : '';
        $searchType          = Input::has('search_type')            ? (int)Input::get('search_type')            : 0;
        
        $timeCreateStart     = Input::has('time_create_start')      ? (int)Input::get('time_create_start')      :   0;
        $timeCreateEnd       = Input::has('time_create_end')        ? (int)Input::get('time_create_end')        :   0;
        $timeUpdateStart     = Input::has('time_update_start')      ? (int)Input::get('time_update_start')      :   0;
        $timeUpdateEnd       = Input::has('time_update_end')        ? (int)Input::get('time_update_end')        :   0;
        $timeFirstOrderStart = Input::has('first_order_start')      ? (int)Input::get('first_order_start')      :   0;
        $timeFirstOrderEnd   = Input::has('first_order_end')        ? (int)Input::get('first_order_end')        :   0;
        $timeLastOrderStart  = Input::has('last_order_start')       ? (int)Input::get('last_order_start')       :   0;
        $timeLastOrderEnd    = Input::has('last_order_end')         ? (int)Input::get('last_order_end')         :   0;
        
        
        $AvgLadingStart      = Input::has('avg_lading_start')       ? (int)Input::get('avg_lading_start')       :   0;
        $AvgLadingEnd        = Input::has('avg_lading_end')         ? (int)Input::get('avg_lading_end')         :   0;
        $PlaceCity           = Input::has('place_city')             ? (int)Input::get('place_city')             :   0;
        $PlaceDistrict       = Input::has('place_district')         ? (int)Input::get('place_district')         :   0;
        $BusinessModel       = Input::has('business_model')         ? (int)Input::get('business_model')         :   0;
        
        $Cmd                 = Input::has('cmd')                    ? trim(Input::get('cmd'))                   : '';
        $page                = Input::has('page') 		            ? (int)Input::get('page')                   : 1;
        $itemPage            = Input::has('item_page')              ? (int)Input::get('item_page')              : 20;
        $offset              = ($page - 1) * $itemPage;

        
        $CheckSeller         = false;
        $SellerID            = $ListUser = $PipeJouney = [];

        $UserInfo   = $this->UserInfo();

        /*
        * Params : @searchType
        * 1: Theo Email ,SDT 
        * 2: Theo người quản lý
        */

        $Model     = new UserInfoModel;

        // Search theo khách hàng
        $userIds = [];
        if($searchType == 1 && !empty($searchType)){
            if(filter_var($search, FILTER_VALIDATE_EMAIL)) {
                $byUserId  = \User::where('email',$search);
            } else if(filter_var((int)$search,FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))) {
                $byUserId = \User::where('phone',$search);
            }else{
                $byUserId = \User::where('fullname',$search);
            }

            $ListUser   = $byUserId->lists('id');
            if(empty($ListUser)){
                return $this->ResponseData();
            }
        }

        //Search theo thời gian
        if(!empty($timeCreateEnd) || !empty($timeCreateStart) ){
            $UserModel = new \User;
            if(!empty($timeCreateStart)){
                $UserModel = $UserModel->where('time_create', '>=', $timeCreateStart);
            }

            if(!empty($timeCreateEnd)){
                $UserModel = $UserModel->where('time_create', '<=', $timeCreateEnd);
            }

            $ListUserIdByTimeCreate = $UserModel->first([DB::raw('MAX(id) as max , MIN(id) as min')]);
            if(!isset($ListUserIdByTimeCreate->max)){
                return $this->ResponseData();
            }

            $Model = $Model->where('user_id', '>=',$ListUserIdByTimeCreate->min)->where('user_id','<=',$ListUserIdByTimeCreate->max);
        }

        // Search theo người quản lý
        $SellerModel = new SellerModel;

        if($UserInfo['privilege'] == 3){
            $SellerModel      = SellerModel::where('seller_id',$UserInfo['id']);
            $CheckSeller      = true;
        }

        if($searchType == 2 && !empty($searchType)){
            if(filter_var($search, FILTER_VALIDATE_EMAIL)) {
                $SellerID = \User::where('email',$search);
            } else if(filter_var((int)$search,FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))) {
                $SellerID = \User::where('phone',$search);
            }else{
                $SellerID = \User::where('phone',$search);
            }

            $SellerID = $SellerID->lists('id');
            if(empty($SellerID)){
                return $this->ResponseData();
            }

            $SellerModel    = $SellerModel->whereIn('seller_id', $SellerID);
            $CheckSeller    = true;
        }

        if(!empty($timeFirstOrderStart)){
            $SellerModel    = $SellerModel->where('first_time_pickup', '>=', $timeFirstOrderStart);
            $CheckSeller    = true;
        }

        if(!empty($timeFirstOrderEnd)){
            $SellerModel    = $SellerModel->where('first_time_pickup', '<=', $timeFirstOrderEnd);
            $CheckSeller    = true;
        }

        if(!empty($timeLastOrderStart)){
            $SellerModel    = $SellerModel->where('last_time_pickup', '>=', $timeLastOrderStart);
            $CheckSeller    = true;
        }

        if(!empty($timeLastOrderEnd)){
            $SellerModel    = $SellerModel->where('last_time_pickup', '<=', $timeLastOrderEnd);
            $CheckSeller    = true;
        }


        if(!empty($AvgLadingStart)){
            $SellerModel    = $SellerModel->where('avg_lading', '>=', $AvgLadingStart);
            $CheckSeller    = true;
        }

        if(!empty($AvgLadingEnd)){
            $SellerModel    = $SellerModel->where('avg_lading', '<=', $AvgLadingEnd);
            $CheckSeller    = true;
        }

        if(!empty($BusinessModel)){
            $SellerModel    = $SellerModel->where('business_model',  $BusinessModel);
            $CheckSeller    = true;
        }
        if(!empty($PlaceDistrict)){
            $SellerModel    = $SellerModel->where('place_district', $PlaceDistrict);
            $CheckSeller    = true;
        }
        if(!empty($PlaceCity)){
            $SellerModel    = $SellerModel->where('place_city', $PlaceCity);
            $CheckSeller    = true;
        }




        if($CheckSeller){
            $SellerID   = [];
            $CityId     = [];
            $DistrictId = [];

            $SellerModel   = $SellerModel->select(['user_id', 'place_city', 'place_district'])->get()->toArray();

            foreach ($SellerModel as $key => $value) {
                $SellerID[]   = $value['user_id'];
                $CityId[]     = $value['place_city'];
                $DistrictId[] = $value['place_district'];
            }


            if(empty($SellerID)){
                return $this->ResponseData();
            }

            if(!empty($DistrictId)){
                $DistrictId = array_unique($DistrictId);
                $this->district   = $this->getProvince($DistrictId);
            }

            $Model = $Model->whereIn('user_id', $SellerID);

        }

        $Model = $Model->where('pipe_status', $group_process);

        $PipeJouneyModel = new PipeJourneyModel;
        $PipeJouneyModel = $PipeJouneyModel->where('type', 2)->where('group_process',$group_process);

        if(!empty($timeUpdateStart)){
            $PipeJouneyModel    = $PipeJouneyModel->where('time_create','>=',$timeUpdateStart);
        }

        if(!empty($timeUpdateEnd)){
            $PipeJouneyModel    = $PipeJouneyModel->where('time_create','<=',$timeUpdateEnd);
        }

        if(!empty($pipe_status)){
            $pipe_status     = explode(',',$pipe_status);
            $PipeJouneyModel    = $PipeJouneyModel->whereIn('pipe_status', $pipe_status);
        }

        if(!empty($timeUpdateStart) || !empty($timeUpdateEnd) || !empty($pipe_status)){
            $PipeJouney = $PipeJouneyModel->lists('tracking_code');

            if(empty($PipeJouney)){
                return $this->ResponseData();
            }

            $PipeJouney = array_unique($PipeJouney);
        }

        // map các điều kiện theo user_id
        if(!empty($ListUser) || !empty($SellerID) || !empty($PipeJouney)){
            $ListId = [];
            if(!empty($ListUser)){
                $ListId = $ListUser;
            }


            if(!empty($SellerID)){
                if(empty($ListId)){
                    $ListId = $SellerID;
                }else{
                    $ListId = array_intersect($ListId, $SellerID);
                    if(empty($ListId)){
                        return $this->ResponseData();
                    }
                }
            }

            if(!empty($PipeJouney)){
                if(empty($ListId)){
                    $ListId = $PipeJouney;
                }else{
                    $ListId = array_intersect($ListId, $PipeJouney);
                }
            }

            if(!empty($ListId)){
                $Model  = $Model->whereRaw("user_id in (". implode(",", $ListId) .")");
            }else{
                return $this->ResponseData();
            }
        }


        // Get Journey process
        $ListPipeId         = [];
        $PipeStatusByGroup  = new PipeStatusModel;
        $ListPipeId         = $PipeStatusByGroup->where('type',2)->where('group_status', $group_process)->lists('status');

        if($Cmd == 'export'){
            return $this->ExportExcel($Model->with(['user', 'bankInfo', 'merchant', 'pipe_journey' => function ($query) use ($group_process){
                $query->where('group_process', $group_process)->where('type',2)->orderBy('time_create', 'ASC');
            }, 'orderInfo']));
        }

        $Total = clone $Model;
        $this->total = $Total->count();

        if($this->total == 0){
            return $this->ResponseData();
        }

        $this->data = $Model->with(['user', 'bankInfo', 'merchant', 'pipe_journey' => function ($query) use ($group_process){
            $query->where('group_process', $group_process)->where('type',2)->orderBy('time_create', 'ASC');
        }, 'orderInfo'])->skip($offset)->take($itemPage)->orderBy('id','DESC')->get()->toArray();

        foreach($this->data as $key => $val){
            if(!empty($val['pipe_journey'])){
                foreach($val['pipe_journey'] as $v){
                    $this->data[$key]['status_journey'] = (int)$v['pipe_status'];
                }
            }
        }

        return $this->ResponseData();
    }

    private function ResponseData(){
        $Cmd                 = Input::has('cmd')                    ? trim(Input::get('cmd'))                   : '';

        if($Cmd == 'export'){
            return $this->ExportExcel([]);
        }

        return Response::json([
            'error'         => $this->error,
            'message'       => $this->message,
            'total'         => $this->total,
            'data'          => $this->data,
            'district'      => $this->district
        ]);
    }

    public function ExportExcel($Data){
        $FileName       = 'Danh_sach_khach_hang';

        $Data           = $Data->get()->toArray();
        $ListManageId   = [];
        $ListCityId     = [];
        $ListDistrictId = [];
        $ListCity       = [];
        $ListDistrict   = [];

        foreach ($Data as $key => $value) {
            
            if($value['order_info']){
                $ListManageId[] = $value['order_info']['seller_id'];
            }
            if(!empty($value['user'])){

                if(!empty($value['user']['city_id'])){
                    $ListCityId[]   = $value['user']['city_id'];
                }

                if(!empty($value['user']['district_id'])){
                    $ListDistrictId[] = $value['user']['district_id'];
                }
            }
        }

        if(!empty($ListCityId)){
            $ListCityId = array_unique($ListCityId);
            $ListCity   = $this->getCity($ListCityId);
        }

        if(!empty($ListDistrictId)){
            $ListDistrictId = array_unique($ListDistrictId);
            $ListDistrict   = $this->getProvince($ListDistrictId);
        }


        $Manager = [];
        $ListManageId = array_unique($ListManageId);
        if (!empty($ListManageId)) {
            $UserModel =  new \User;
            $UserModel = $UserModel->whereIn('id', $ListManageId)->get();
            
            foreach ($UserModel as $key => $value) {
                $Manager[$value['id']]  = $value['fullname'];
            }
        }



        return Excel::create($FileName, function($excel) use($Data, $Manager, $ListDistrict, $ListCity){
            $excel->sheet('Sheet1', function($sheet) use($Data, $Manager, $ListDistrict, $ListCity){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách khách hàng'));

                $sheet->setWidth(array(
                    'A' => 10, 'B'  =>  30, 'C'     =>  30, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,'H'     =>  30,
                    'I' => 30, 'J'  => 30, 'K' => 30, 'L' => 30, 'M' => 30, 'N' => 30, 'O' => 30, 'P' => 30, 'Q' => 30
                ));

                $sheet->row(3, array(
                    'STT', 'ID', 'Thời gian đăng ký','Họ tên', 'Email', 'Số điện thoại', 'Địa chỉ', 'Người quản lý',  'Số dư hiện tại', 'Tiền thu hộ tạm tính', 'Phí vận chuyển tạm tính',
                    'Số dư khả dụng', 'Số đơn trung bình', 'Phát sinh vận đơn đầu tiên', 'Phát sinh vận đơn cuối', 'Doanh thu đầu tháng', 'Doanh thu lũy kế'
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

                foreach ($Data as $val) {
                    $Address = (empty($val['user']['address']) || empty($val['user']['city_id']) || empty($val['user']['district_id'] )) ? '' : $val['user']['address'].', '.$ListDistrict[$val['user']['district_id']].', '.$ListCity[$val['user']['city_id']];
                    $dataExport = array(
                        $i++,
                        $val['user_id'],
                        isset($val['user']) && $val['user']['time_create'] > 0 ? date("d/m/y H:m", $val['user']['time_create']) : '',
                        isset($val['user']) ? $val['user']['fullname'] : '',
                        isset($val['user']) ? $val['user']['email'] : '',
                        isset($val['user']) ? $val['user']['phone'] : '',
                        $Address,
                        isset($val['order_info']) && isset($Manager[$val['order_info']['seller_id']]) ? $Manager[$val['order_info']['seller_id']] : '',
                        isset($val['merchant']) ? number_format($val['merchant']['balance']) : '0',
                        isset($val['merchant']) ? number_format($val['merchant']['provisional']) : '0',
                        isset($val['merchant']) ? number_format($val['merchant']['freeze']) : '0',
                        isset($val['merchant']) ? number_format($val['merchant']['balance'] + $val['merchant']['provisional'] - $val['merchant']['freeze'] ) : '0',
                        isset($val['order_info']) ? $val['order_info']['num_order_avg'] : '0',
                        isset($val['order_info']) && $val['order_info']['first_time_pickup'] > 0 ? date("d/m/y H:m", $val['order_info']['first_time_pickup']) : '',
                        isset($val['order_info']) && $val['order_info']['last_time_pickup'] > 0 ? date("d/m/y H:m", $val['order_info']['last_time_pickup']) : '',
                        isset($val['order_info']) ? number_format($val['order_info']['total_firstmonth']) : '0',
                        isset($val['order_info']) ? number_format($val['order_info']['total_nextmonth']) : '0',
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }

    public function getVip() {
        $group_process  = Input::has('group_process')   ? Input::get('group_process')   :   0;
        //$group_type     = Input::has('group_type')      ? Input::get('group_type')        : 2;
        $search         = Input::has('search')          ? Input::get('search')  :   '';
        $searchType     = Input::has('search_type')     ? Input::get('search_type')    :   1;
        $vip            = Input::has('vip')             ? Input::get('vip')         :   '';
        $page           = Input::has('page')            ? (int)Input::get('page') : 1;
        $itemPage       = Input::has('item_page')       ? (int)Input::get('item_page') : 20;
        $offset         = ($page - 1) * $itemPage;

        /*
        * Params : @searchType
        * 1: Theo Email ,SDT 
        * 2: Theo người quản lý
        */

        $userIds = [];
        if($searchType == 1 && !empty($searchType)){
            if(filter_var($search, FILTER_VALIDATE_EMAIL)) {
                $byUserId = \User::where('email',$search)->pluck('id');
                $userIds[] = $byUserId ? $byUserId : 999999999999;
            } else if(filter_var((int)$search,FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))) {
                $byUserId = \User::where('phone',$search)->pluck('id');
                $userIds[] = $byUserId ? $byUserId : 999999999999;
            }
        }

        if($searchType == 2 && !empty($searchType)){
            if(filter_var($search, FILTER_VALIDATE_EMAIL)) {
                $SellerID = \User::where('email',$search)->pluck('id');
            } else if(filter_var((int)$search,FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))) {
                $SellerID = \User::where('phone',$search)->pluck('id');
            }
            $SellerModel = new SellerModel;
            $Seller      = $SellerModel::where('seller_id', $SellerID)->get(['seller_id','user_id']);
            foreach ($Seller as $key => $value) {
               $userIds[] = $value['user_id'];
            }
        }


        $Model  = new UserInfoModel;

        if(!empty($userIds)){
            $Model = $Model->whereIn('user_id', $userIds);
        }
        

        $TotalGroup              = [];
        $GroupProcessSellerModel = new GroupProcessSellerModel;
        $GroupProcess            = $GroupProcessSellerModel->where('id', $group_process)->first();
        if(!$GroupProcess){
            return Response::json(array(
                "error" => true,
                "error_message" => "Nhóm không tồn tại",
                "data"  => ""
            ));
        };

        $Model = $Model->where('pipe_status_vip', $GroupProcess->code);

        $ListPipeId         = [];
        $PipeStatusByGroup  = new PipeStatusModel;
        $PipeStatusByGroup  = $PipeStatusByGroup->where('group_status', $GroupProcess->id)->get();

        foreach ($PipeStatusByGroup as $key => $value) {
            $ListPipeId[] = $value['status'];
        }

        $Total = clone $Model;
        $Total = $Total->count();
        
        $with  = [];


        
        $Model->with(['user', 'bankInfo', 'merchant', 'pipe_journey' => function ($query) use ($ListPipeId){

            if(!empty($ListPipeId)){
                $query->whereIn('pipe_status', $ListPipeId);
            }
        },'orderInfo'])->skip($offset)->take($itemPage)->orderBy('id','DESC');   




        try {
            $Data = $Model->get();
        } catch (Exception $e) {
            return Response::json(array(
                "error"         => true,
                "error_message" => "Lỗi kết nối máy chủ, vui lòng thử lại sau !",
                "data"          => $e->getMessage()
            ));
        }

        return Response::json(array(
                "error"         => false,
                "error_message" => "Thành công",
                "total"         => $Total,
                "data"          => $Data,
        ));
    }


    public function getStatusFromGroup ($groups, $type){
        $groups    = explode(',', $groups);
        $PipeModel = new PipeStatusModel;
    	try {
            $PipeModel = $PipeModel->whereIn('group_status', $groups)->where('type', $type);
            $List      = $PipeModel->get()->toArray();
    	} catch (Exception $e) {
    		return false;
    	}
    	
    	if($List){
    		$listStatusId =  array_map(function ($value){
    			return $value['status'];
    		}, $List);
    	}
    	return (!empty($listStatusId)) ? array_unique($listStatusId) : false;
    }

}
