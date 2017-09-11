<?php
use ordermodel\AddressModel;
class DashbroadCtrl extends \BaseController {
    public $from_time;
    public $to_time;
    public function __construct(){
        $this->from_time      = $this->time() - 7 * 86400; 
        $this->to_time        = $this->time(); 
    }
    private function getModel($byTime = 'time_create'){
        $this->from_time      = Input::has('from_time')   ? (int)Input::get('from_time')  : $this->$from_time; 
        $this->to_time        = Input::has('to_time')     ? (int)Input::get('to_time')    : 0; 
        $UserId               = Input::has('user_id')     ? (int)Input::get('user_id')    : 0;

        $Model          = new ordermodel\OrdersModel;
        $UserInfo       = $this->UserInfo();
        $_ret           = [];


        if($UserId > 0 && $UserInfo['privilege'] > 0){
            $id     = $UserId;
        }else {
            $id = (int)$UserInfo['id'];
        }

        if(!empty($id)){
            $Model  = $Model::where('from_user_id',$id);
        }


        $Model          = $Model->where($byTime, '>=', $this->from_time);

        if($this->to_time > 0){
            $Model          = $Model->where($byTime, '<=', $this->to_time);
        }

        $Model          = $Model->where('time_accept', '>=', $this->time() - $this->time_limit);

        if($byTime != 'time_create'){
            //$this->from_time    = $this->time() - 30 * 86400;
            $Model              = $Model->where('time_create', '>=', $this->time() - 30 * 86400);
        }
        return $Model;
    }

    private function getDateRange (){
        $dates  = array();
        $start  = $current = $this->from_time;
        $end    = $this->to_time;

        while ($current <= $end) {
            $dates[] = [strtotime("midnight", $current) * 1000, 0];
            $current = strtotime('+1 days', $current);
        }
        return $dates;
    }

    public function getOrderGraph(){
        $Model      = $this->getModel();
        $ordersCod  = clone $Model;
        $ordersCod  = $ordersCod
                        ->join('order_detail', 'orders.id', '=', 'order_detail.order_id')
                        ->select(DB::raw("time_create, DATE_FORMAT(FROM_UNIXTIME(`time_create`), '%d-%m-%y') AS `time_created`, count(time_create) as total"))
                        ->groupBy('time_created')
                        ->orderBy('time_create', 'ASC')
                        ->where('order_detail.sc_cod', '>', 0)
                        ->get();

        $OrderPas   = clone $Model;
        $OrderPas   = $OrderPas
                        ->join('order_detail', 'orders.id', '=', 'order_detail.order_id')
                        ->select(DB::raw("time_create, DATE_FORMAT(FROM_UNIXTIME(`time_create`), '%d-%m-%y') AS `time_created`, count(time_create) as total"))
                        ->groupBy('time_created')
                        ->orderBy('time_create', 'ASC')
                        ->where('order_detail.sc_cod', '=', 0)
                        ->get();

        
        $pasSeries  = [
            "name"      => "Vận đơn PaS",
            "data"      => [],
            "type"      => "spline",
            "tooltip"   => [
                "valueDecimals"=> 0
            ]
        ];

        $codSeries  = [
            "name"      => "Vận đơn CoD",
            "data"      => [],
            "type"      => "spline",
            "tooltip"   => [
                "valueDecimals"=> 0
            ]
        ];

        foreach ($OrderPas as $key => $value) {
            /*$check = $value['time_create']+date("Z",$value['time_create']);
            $time =  strftime("%m-%d", $check);*/

            $month  = date('m', $value['time_create']);
            $day    = date('d', $value['time_create']);
            $years  = date('Y', $value['time_create']);
            $stamp  = mktime(0, 0, 0, $month, $day, $years);

            $pasSeries['data'][] = [$stamp * 1000, $value['total']];

        }

        foreach ($ordersCod as $key => $value) {
            $month  = date('m', $value['time_create']); // 1-12 = Jan-Dec
            $day    = date('d', $value['time_create']); // 1-31, day of the month
            $years  = date('Y', $value['time_create']); // 1-31, day of the month
            $stamp  = mktime(0, 0, 0, $month, $day, $years);

            $codSeries['data'][] = [$stamp * 1000, $value['total']];
        }




        return Response::json([
            'error'             => false,
            'error_message'     => "",
            'data'              => [$pasSeries, $codSeries]
        ]);
    }

    public function getGraphStatitics(){
        $DateRange = $this->getDateRange();


        $Model = $this->getModel('time_pickup');
        $Model = $Model->select(DB::raw("time_pickup, DATE_FORMAT(FROM_UNIXTIME(`time_pickup`), '%d-%m-%y') AS `time`, count(time_pickup) as total"))
                        ->where('time_pickup', '>', 0)
                        ->groupBy('time')
                        ->get();


        $ModelSuccess = $this->getModel('time_success');
        $ModelSuccess = $ModelSuccess->select(DB::raw("time_success, DATE_FORMAT(FROM_UNIXTIME(`time_success`), '%d-%m-%y') AS `time`, count(time_success) as total"))
                        ->where('time_success', '>', 0)
                        ->groupBy('time')
                        ->get();

        $ModelReturn = $this->getModel('time_success');
        $ModelReturn = $ModelReturn->select(DB::raw("time_success, DATE_FORMAT(FROM_UNIXTIME(`time_success`), '%d-%m-%y') AS `time`, count(time_success) as total, status"))
                        ->where('time_success', '>', 0)
                        ->where('status', 66)
                        ->groupBy('time')
                        ->get();


        $DateRange = $this->getDateRange();
        $pickedSeries  = [
            "name"      => "Đơn lấy thành công",
            "data"      => $DateRange,
            "color"     => "#27ae60",
            "type"      => "column",//areaspline
            "tooltip"   => [
                "valueDecimals"=> 0
            ]
        ];

        $deliveriedSeries  = [
            "name"      => "Giao thành công",
            "data"      => $DateRange,
            "color"     => "#7cb5ec",
            "type"      => "column",
            "tooltip"   => [
                "valueDecimals"=> 0
            ]
        ];

        $returnedSeries  = [
            "name"      => "Đơn chuyển hoàn",
            "data"      => $DateRange,
            "color"     => "#8e44ad",
            "type"      => "column",
            "tooltip"   => [
                "valueDecimals"=> 0
            ]
        ];

        



        

        foreach($pickedSeries['data'] as $k => $v){
            foreach ($Model as $key => $value) {
                $month  = date('m', $value['time_pickup']);
                $day    = date('d', $value['time_pickup']);
                $years  = date('Y', $value['time_pickup']);
                $stamp  = mktime(0, 0, 0, $month, $day, $years);
                $time   = $stamp * 1000;

                if($time == $v[0]){
                    $pickedSeries['data'][$k][1] = $value['total'];
                }
            }
        }



        foreach($deliveriedSeries['data'] as $k => $v){
            foreach ($ModelSuccess as $key => $value) {
                $month  = date('m', $value['time_success']);
                $day    = date('d', $value['time_success']);
                $years  = date('Y', $value['time_success']);
                $stamp  = mktime(0, 0, 0, $month, $day, $years);
                $time   = $stamp * 1000;
                if($time == $v[0]){
                    $deliveriedSeries['data'][$k][1] = $value['total'];
                }
            }
        }

        foreach($returnedSeries['data'] as $k => $v){
            foreach ($ModelReturn as $key => $value) {
                $month  = date('m', $value['time_success']);
                $day    = date('d', $value['time_success']);
                $years  = date('Y', $value['time_success']);
                $stamp  = mktime(0, 0, 0, $month, $day, $years);
                $time   = $stamp * 1000;
                if($time == $v[0]){
                    $returnedSeries['data'][$k][1] = $value['total'];
                }
            }
        }




        /*foreach ($ModelSuccess as $key => $value) {
            $month  = date('m', $value['time_success']);
            $day    = date('d', $value['time_success']);
            $years  = date('Y', $value['time_success']);
            $stamp  = mktime(0, 0, 0, $month, $day, $years);

            $time   = $stamp * 1000;
            $indexOfTime = array_search($time, $deliveriedSeries['data']);
            if($indexOfTime !== false){
                array_push($deliveriedSeries['data'][$indexOfTime], $value['total']);
            }else {
                array_push($deliveriedSeries['data'][$indexOfTime], 0);
            }
        }*/

      /*  foreach ($ModelReturn as $key => $value) {
            $month  = date('m', $value['time_success']);
            $day    = date('d', $value['time_success']);
            $years  = date('Y', $value['time_success']);
            $stamp  = mktime(0, 0, 0, $month, $day, $years);

            $time   = $stamp * 1000;
            $indexOfTime = array_search($time, $returnedSeries['data']);
            if($indexOfTime !== false){
                array_push($returnedSeries['data'][$indexOfTime], $value['total']);
            }else {
                array_push($returnedSeries['data'][$indexOfTime], 0);
            }
        }
*/

        return Response::json([
            'error'             => false,
            'error_message'     => "",
            'data'              => [$pickedSeries, $deliveriedSeries, $returnedSeries]
        ]);


    }

    public function getStatitics(){
        $Model                  = $this->getModel();
        $StatusOrderCtrl        = new order\StatusOrderCtrl;
        $Group                  = [];
        $ListGroupStatus        = [];
        $GroupStatusOrder       = [];    


        $ListGroup  = $StatusOrderCtrl->getStatusgroup(false);

        if(!empty($ListGroup)) {
            foreach($ListGroup as $val){
                foreach($val['group_order_status'] as $v) {
                    if(!isset($GroupStatusOrder[$val['id']])){
                        $GroupStatusOrder[$val['id']] = [];        
                    }
                    $GroupStatusOrder[$val['id']][]    = (int)$v['order_status_code'];
                    $ListStatus[]       = (int)$v['order_status_code'];
                    $ListGroupStatus[(int)$v['order_status_code']]    = $v['group_status'];
                }
            }
        }

        if(!empty($ListStatus)) {
            $OrderModel     = clone $Model;
            $DataGroup      = $OrderModel->groupBy('status')->get(array('status', DB::raw('count(*) as count')));

            if(!empty($DataGroup)){
                foreach($DataGroup as $val){
                    if(!isset($Group[(int)$ListGroupStatus[(int)$val['status']]])){
                        $Group[(int)$ListGroupStatus[(int)$val['status']]]  = 0;
                    }
                    $Group[(int)$ListGroupStatus[(int)$val['status']]] += $val['count'];
                }
            }
        }

        $Orders = clone $Model;
        $Orders = $Orders->with(['OrderDetail' => function ($q){
            return $q->select(['order_id', 'sc_pvc', 'sc_cod', 'sc_pbh', 'sc_pvk', 'sc_pch','money_collect']);
        }])->where('status', '>', 21)->whereNotIn('status', $GroupStatusOrder[22])->select('tracking_code', 'id')->get();

        $totalCod           = 0;
        $totalFee           = 0;

        foreach ($Orders as $key => $value) {
            $totalCod           += $value['order_detail']['money_collect'];
            $totalFee           += $value['order_detail']['sc_pvc'] + $value['order_detail']['sc_cod'] + $value['order_detail']['sc_pbh']
                                + $value['order_detail']['sc_pvk'] + $value['order_detail']['sc_ch'];
        }

        $_ret['order_confirm_return']       = isset($Group[20]) ? $Group[20] : 0;
        $_ret['order_return']               = isset($Group[21]) ? $Group[21] : 0;
        $_ret['order_accept']               = isset($Group[13]) ? $Group[13] : 0;
        $_ret['order_pickuping']            = isset($Group[14]) ? $Group[14] : 0;
        $_ret['order_delivery']             = isset($Group[19]) ? $Group[19] : 0;

        

        $_ret['total_cod']                  = $totalCod;
        $_ret['total_fee']                  = $totalFee;
        if($_ret['order_return'] > 0 && $_ret['order_delivery'] > 0){
            $_ret['percent_order_return']       = ($_ret['order_return'] / ($_ret['order_return'] + $_ret['order_delivery'])) * 100;
        }else {
            $_ret['percent_order_return']       = 0;    
        }
        


        return Response::json([
            "error"     => false,
            "message"   => "",
            "data"      => $_ret
        ]);

    }	


    public function getStatiticsByCity (){
        $this->from_time      = Input::has('from_time')   ? (int)Input::get('from_time')  : $this->$from_time; 
        $this->to_time        = Input::has('to_time')     ? (int)Input::get('to_time')    : 0; 



        $UserInfo = $this->UserInfo();
        $Model  = AddressModel::where('seller_id', $UserInfo['id'])
                ->where('time_update', '>=', $this->from_time)
                ->where('time_update', '<=', $this->to_time)
                ->with(['City'])
                ->groupBy('city_id')
                ->select(DB::raw("city_id, COUNT(city_id) as total"))
                ->orderBy('total', 'DESC')
                ->get()
                ->toArray();
        $Data = [];


        if(empty($Model)){
            return Response::json([
                'error'         => true,
                'error_message' => "",
                'data'          => $Data
            ]);
        }
        $Total = 0;

        for ($i=0; $i < sizeof($Model); $i++) {
            $Total += $Model[$i]['total'];
        }

        $TotalPercent = 0;

        $Flag  = false;
        for ($i=0; $i < sizeof($Model); $i++) {
            if($i < 10){
                $percent        = (float)number_format(($Model[$i]['total'] / $Total) * 100, 2);
                $TotalPercent   += $percent;
                $Data[] = [
                    "name"  => $Model[$i]['city']['city_name'],
                    "y"     => $percent
                ];
            }else {
                $Flag  = true;
            }
        }

        if($Flag){
            $Data[] = [
                "name"  => "Tỉnh thành khác",
                "y"     => 100 - $TotalPercent
            ];
        }

        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => $Data,
            'from'          => $this->from_time,
            'to'          => $this->to_time,
        ]);
    }

    /*public function getAbc(){
        $Model       = new \sellermodel\UserInfoModel;
        $Model = $Model->where('android_device_token', '!=', "")->where('ios_device_token', "")->get()->lists('user_id');

        return Response::json($Model);
    }
*/
    public function getNewsestVerify(){
        $UserInfo = $this->UserInfo();
        $Ctrl = new \oms\UserCtrl();
        Input::merge(['seller'=> $UserInfo['id']]);
        return $Ctrl->getNewestVerify();
    }

    public function getNewestTicket(){
        $UserInfo = $this->UserInfo();
        $Ctrl = new \oms\UserCtrl();
        Input::merge(['seller'=> $UserInfo['id']]);
        return $Ctrl->getNewestTicket();
    }
}
