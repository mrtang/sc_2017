<?php
namespace oms;
use DB;
use Input;
use Response;
use Cache;
use ordermodel\OrdersModel;
use omsmodel\CustomerAdminModel;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends \BaseController {
    private $domain = '*';

	public function __construct(){
        
    }


    public function getNewcustomer(){
        $userInfo = $this->UserInfo();
        if($userInfo['privilege'] != 2 && $userInfo['group'] != 2) {
            return Response::json([
                'error' =>  true,
                'message'   =>  'Không có quyền'
            ]);
        }
    	$page       = Input::has('page') ? (int)Input::get('page') : 1;
        $itemPage   = Input::has('item_page') ? (int)Input::get('item_page') : 20;
        $offset     = ($page - 1)*$itemPage;
        $time       = Input::has('time_late')  ? Input::get('time_late')                    : 1;
    	$timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-'.$time.' day',$this->time())));
    	
    	$Order = new OrdersModel;
    	$Customer = new CustomerAdminModel;
    	$listNewUser = \User::skip($offset)->take($itemPage)->where('time_create','>',$timeStart)->get(array('id','fullname','time_create','email','phone','time_last_login'))->toArray();
    	$total = \User::where('time_create','>',$timeStart)->count();
    	if(!empty($listNewUser)){
    		$listUserId = array();
    		$listInfoUser = $output = array();
    		foreach($listNewUser AS $oneUser){
    			$listUserId[] = $oneUser['id'];
    			$listInfoUser[$oneUser['id']] = $oneUser;
    		}

    		if(!empty($listUserId)){
    			$infoCustomer = $Customer::whereIn('user_id',$listUserId)->get()->toArray();
    			$userOrder = $Order::whereIn('from_user_id',$listUserId)->groupBy('from_user_id')->get(array('from_user_id',DB::raw('count(*) as count')))->toArray();
    			foreach($listInfoUser AS $one){
	    			foreach($userOrder AS $oneOrder){
	    				if($one['id'] == $oneOrder['from_user_id']){
	    					$listInfoUser[$one['id']]['count_order'] = $oneOrder['count'];
	    				}
	    			}
	    		}
	    		foreach($listInfoUser AS $one){
	    			foreach($infoCustomer AS $oneCustom){
	    				if($one['id'] == $oneCustom['user_id']){
	    					$listInfoUser[$one['id']]['active'] = $oneCustom['active'];
	    					$listInfoUser[$one['id']]['supporter'] = $oneCustom['support_id'];
	    					$listInfoUser[$one['id']]['integrate'] = $oneCustom['integrate'];
	    					$listInfoUser[$one['id']]['oms_new_customer_id'] = $oneCustom['id'];
	    				}
	    			}
	    		}
	    		foreach($listInfoUser AS $key => $value){
	    			$output[] = $value;
	    		}
	    		
	    		$contents = array(
		            'error'     => false,
		            'message'   => 'success',
		            'total'		=>	$total,
		            'data'      => $output
		        );
		        
		        return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
    		}else{
    			$contents = array(
		            'error'     => true,
		            'message'   => 'Not Data!!!!',
		            'data'      => ''
		        );
		        
		        return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
    		}
    	}else{
			$contents = array(
	            'error'     => true,
	            'message'   => 'Not Data!!!!',
	            'data'      => ''
	        );
	        
	        return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
		}
    }

    public function getExportNewcustomer() {

        $userInfo = $this->UserInfo();
        if($userInfo['privilege'] != 2 && $userInfo['group'] != 2) {
            return Response::json([
                'error' =>  true,
                'message'   =>  'Không có quyền'
            ]);
        }
        $time       = Input::has('time_late')  ? Input::get('time_late')                    : 1;
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-'.$time.' day',$this->time())));

        $Order = new OrdersModel;
        $Customer = new CustomerAdminModel;
        $listNewUser = \User::where('time_create','>',$timeStart)->get(array('id','fullname','time_create','email','phone','time_last_login'))->toArray();

        if(!empty($listNewUser)) {
            $listUserId = array();
            $listInfoUser = $output = array();
            foreach ($listNewUser AS $oneUser) {
                $listUserId[] = $oneUser['id'];
                $listInfoUser[$oneUser['id']] = $oneUser;
            }

            if (!empty($listUserId)) {
                $infoCustomer = $Customer::whereIn('user_id', $listUserId)->get()->toArray();
                $userOrder = $Order::whereIn('from_user_id', $listUserId)->groupBy('from_user_id')->get(array('from_user_id', DB::raw('count(*) as count')))->toArray();

                foreach ($listInfoUser AS $one) {
                    foreach ($userOrder AS $oneOrder) {
                        if ($one['id'] == $oneOrder['from_user_id']) {
                            $listInfoUser[$one['id']]['count_order'] = $oneOrder['count'];
                        }
                    }
                }
                foreach ($listInfoUser AS $one) {
                    foreach ($infoCustomer AS $oneCustom) {
                        if ($one['id'] == $oneCustom['user_id']) {
                            $listInfoUser[$one['id']]['active'] = $oneCustom['active'];
                            $listInfoUser[$one['id']]['supporter'] = $oneCustom['support_id'];
                            $listInfoUser[$one['id']]['integrate'] = $oneCustom['integrate'];
                            $listInfoUser[$one['id']]['oms_new_customer_id'] = $oneCustom['id'];
                            $listInfoUser[$one['id']]['first_order_time'] = $oneCustom['first_order_time'];
                            $listInfoUser[$one['id']]['last_order_time'] = $oneCustom['last_order_time'];
                        }
                    }
                }
                foreach ($listInfoUser AS $key => $value) {
                    $output[] = $value;
                }
            }
            //xuat du lieu ra excel
            return Excel::create('Khach_hang_moi', function ($excel) use($output) {
                $excel->sheet('Khách hàng mới', function ($sheet) use($output) {
                    $sheet->mergeCells('C1:F1');
                    $sheet->row(1, function ($row) {
                        $row->setFontSize(20);
                    });
                    $sheet->row(1, array('','','Khách hàng mới'));
                    // set width column
                    $sheet->setWidth(array(
                        'A'     => 5,
                        'B'     => 20,
                        'C'     => 20,
                        'D'     => 25,
                        'E'     => 15,
                        'F'     => 20,
                        'G'     => 30,
                        'H'     => 30,
                        'I'     => 30,
                        'J'     => 30,
                        'K'     => 30,
                        'L'     => 30,
                        'M'     => 30,
                        'N'     => 30,
                        'O'     => 30,
                        'P'     => 30,
                        'Q'     => 30,
                        'R'     => 30,
                        'S'     => 30
                    ));
                    // set content row
                    $sheet->row(3, array(
                        'STT', 'Họ tên', 'Số điện thoại', 'Email','Đơn hàng','Thời gian tạo tài khoản', 'Thời gian tạo vận đơn đầu tiên', 'Thời gian tạo vận đơn cuối cùng'
                    ));
                    $sheet->row(3,function($row){
                        $row->setBackground('#B6B8BA');
                        $row->setBorder('solid','solid','solid','solid');
                        $row->setFontSize(12);
                    });
                    //
                    $i = 1;
                    foreach ($output AS $value) {
                        $dataExport = array(
                            'STT' => $i++,
                            'Họ tên' => $value['fullname'],
                            'Số điện thoại' => $value['phone'],
                            'Email' => $value['email'],
                            'Đơn hàng' => isset($value['count_order']) ? $value['count_order'] : 0,
                            'Thời gian tạo tài khoản' => ($value['time_create'] > 0) ? date("d/m/Y",$value['time_create']) : '',
                            'Thời gian tạo vận đơn đầu tiên'  => (isset($value['first_order_time']) && $value['first_order_time'] > 0) ? date("d/m/Y H:i",$value['first_order_time']) : '',
                            'Thời gian tạo vận đơn cuối cùng' => (isset($value['last_order_time']) && $value['last_order_time'] > 0) ? date("d/m/Y H:i",$value['last_order_time']) : ''
                        );
                        $sheet->appendRow($dataExport);
                    }
                });
            })->export('xls');
        } else {
            return "Không có khách hàng mới";
        }
    }

    //lay khach hang can lay hang
    public function getPickupcustomer(){
    	$page       = Input::has('page') ? (int)Input::get('page') : 1;
        $itemPage   = Input::has('item_page') ? (int)Input::get('item_page') : 20;
        $offset     = ($page - 1)*$itemPage;
        $fromDate   = Input::has('from_date')  ? (int)Input::get('from_date')                    : 0;
        $toDate   = Input::has('to_date')  ? (int)Input::get('to_date')                    : 0;
        $fromCity   = Input::has('city_id') ? (int)Input::get('city_id')     :   0;
        $fromDistrict   = Input::has('district_id') ? (int)Input::get('district_id')     :   0;

    	$Order = new OrdersModel;
    	$Customer = new CustomerAdminModel;

    	$listUserId = $output = array();
        $timeEnd = $timeStart = 0;
        if($toDate > 0){
            $timeEnd = $toDate;
        }else{
            $timeEnd = $this->time();
        }
        if($fromDate > 0){
            $timeStart = $fromDate;
        }else{
            $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',$this->time())));
        }


        if($fromCity > 0){
            $Order = $Order->where('from_city_id',$fromCity);
        }
        if($fromDistrict > 0){
            $Order = $Order->where('from_district_id',$fromDistrict);
        }

        $listData  = $Order->skip($offset)->take($itemPage)->whereIn('status',array(30,35))->where('time_accept','>',$timeStart)->where('time_accept','<',$timeEnd)->get(array('from_user_id','from_city_id','from_address','from_district_id','total_weight'))->toArray();
    	$total = $Order->whereIn('status',array(30,35))->where('time_accept','>',$timeStart)->where('time_accept','<',$timeEnd)->count();
    	if(!empty($listData)){
    		foreach($listData AS $one){
    			$listUserId[] = $one['from_user_id'];
    			$output[$one['from_user_id']] = $one;
    		}

    		$listInfoUser = \User::whereIn('id',$listUserId)->get(array('id','fullname','email','phone'))->toArray();
    		foreach($listInfoUser AS $oneUser){
    			$output[$oneUser['id']] += $oneUser;
    		}

    		$userOrder = $Order->whereIn('status',array(30,35))->where('time_accept','>',$timeStart)->where('time_accept','<',$timeEnd)->groupBy('from_user_id')->get(array('from_user_id',DB::raw('count(*) as count')))->toArray();
    		foreach($output AS $one){
    			foreach($userOrder AS $oneOrder){
    				if($one['id'] == $oneOrder['from_user_id']){
    					$output[$one['id']]['count_order'] = $oneOrder['count'];
    				}
    			}
    		}

    		$totalWeight = $Order->whereIn('status',array(30,35))->where('time_accept','>',$timeStart)->where('time_accept','<',$timeEnd)->groupBy('from_user_id')->get(array('from_user_id',DB::raw('sum(total_weight) as weight')))->toArray();
    		foreach($output AS $one){
    			foreach($totalWeight AS $value){
    				if($one['id'] == $value['from_user_id']){
    					$output[$one['id']]['weight'] = $value['weight'];
    				}
    			}
    		}
    		
    		$listCity = \CityModel::all();
    		if(!empty($listCity)){
                foreach($listCity AS $one){
                    $LCity[$one['id']] = $one['city_name'];
                }
                foreach($output as $key => $val){
                    if (isset($LCity[(int)$val['from_city_id']])){
                        $output[$key]['from_city_name'] = $LCity[(int)$val['from_city_id']];
                    }
                }
            }
            $listDistrict = \DistrictModel::all();
            if(!empty($listDistrict)){
                foreach($listDistrict AS $one){
                    $LDistrict[$one['id']] = $one['district_name'];
                }
                foreach($output as $key => $val){
                    if (isset($LDistrict[(int)$val['from_district_id']])){
                        $output[$key]['from_district_name'] = $LDistrict[(int)$val['from_district_id']];
                    }
                }
            }

            $contents = array(
	            'error'     => false,
	            'message'   => 'success',
	            'total'		=>	$total,
	            'data'      => $output,
                'item_page'     => $itemPage
	        );

			return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
    	}else{
    		$contents = array(
	            'error'     => true,
	            'message'   => 'error',
	            'data'      => ''
	        );
			
			return Response::json($contents, 200, array('Access-Control-Allow-Origin' => $this->domain));
    	}
    
    	
    }


}
?>