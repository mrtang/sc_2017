<?php namespace mobile;

require app_path().'/libraries/php-jwt/vendor/autoload.php';
use \Firebase\JWT\JWT;
use Response;
use Input;
use DB;
use LMongo;
use Excel;
use Validator;
use Cache;
use Session;
use Config;
use accountingmodel\ReportMerchantModel;
use ordermodel\OrdersModel;



class StatisticController extends \BaseController {

    public function __construct(){

    }

    public function getIndex(){
        $Date      = Input::has('date') ? Input::get('date') : "";
        if(empty($Date)){
            $Date = date('Y-m-d');
        }else {
            $Date = date('Y-m-d', $Date);
        }

        $UserInfo  = $this->UserInfo();
        $UserId    = $UserInfo['id'];

        if(!$UserId){
            return Response::json(array(
                'error'         => true,
                'error_message' => 'Bạn không có quyền truy cập trang này',
                'privilege'     => 0
            ));
        }

        $DateRange = $this->rangeWeek($Date);
        $TimeStart = $DateRange['start'];
        $TimeEnd   = $DateRange['end'];

        $OrderModel             = new \ordermodel\OrdersModel;
        $Model = $OrderModel::where('time_accept','>=',$TimeStart - 86400*60)
            ->where('from_user_id', $UserId)
            ->where(function($query) use($TimeStart, $TimeEnd){
                $query->where(function($q)use($TimeStart, $TimeEnd){
                    $q->where('time_pickup','>=',$TimeStart)
                        ->where('time_pickup','<',$TimeEnd);
                })->orWhere(function($q) use($TimeStart, $TimeEnd){
                    $q->where('time_success','>=',$TimeStart)
                        ->where('time_success','<',$TimeEnd)
                        ->whereIn('status',[52,53,66]);
                });
            });

        $ListOrder  = $Model->with('OrderDetail')
            ->get(['id', 'from_user_id','status','time_pickup','time_success'])->toArray();

        $Data = [
            'generate'      => 0,
            'success'       => 0,
            'total_return'  => 0,
            'sc_pvc'        => 0,
            'sc_cod'        => 0,
            'sc_pvk'        => 0,
            'sc_pbh'        => 0,
            'sc_discount_pvc' => 0,
            'sc_discount_cod' => 0,
            'money_collect' => 0,
        ];
        foreach($ListOrder as $val) {
            if($val['time_pickup'] >= $TimeStart && $val['time_pickup'] < $TimeEnd) { // Đơn lấy hàng trong tuần
                $Data['generate'] += 1;
                if (!empty($val['order_detail'])) {
                    $Data['sc_pvc'] += $val['order_detail']['sc_pvc'];
                    $Data['sc_cod'] += $val['order_detail']['sc_cod'];
                    $Data['sc_pbh'] += $val['order_detail']['sc_pbh'];
                    $Data['sc_discount_pvc'] += $val['order_detail']['sc_discount_pvc'];
                    $Data['sc_discount_cod'] += $val['order_detail']['sc_discount_cod'];
                    $Data['money_collect'] += $val['order_detail']['money_collect'];
                }
            }else {
                if($val['status'] == 66) { // Chuyển hoàn
                    if(!empty($val['order_detail'])) {
                        $Data['sc_pvk']                 += $val['order_detail']['sc_pvk'];
                    }
                }else{
                    if(!empty($val['order_detail'])) {
                        $Data['sc_pvk'] += $val['order_detail']['sc_pvk'];
                    }
                }
            }
        }
        $RetData = [
            'generate'      => 0,
            'money_collect' => 0,
            'total_pvc'       => 0,
        ];

        $RetData['generate'] = (int)$Data['generate'];
        $RetData['money_collect'] = (int)$Data['money_collect'];
        $RetData['total_pvc'] = ((int)$Data['sc_pvc'] + (int)$Data['sc_cod'] + (int)$Data['sc_pbh'] + (int)$Data['sc_pvk']) - ((int)$Data['sc_discount_pvc'] + (int)$Data['sc_discount_cod']);


        return Response::json([
            'error'         => false,
            'error_message' => "",
            'data'          => $RetData
        ]);

    }

	/*public function getIndex()
	{
        $Date      = Input::has('date') ? Input::get('date') : "";
        if(empty($Date)){
            $Date = date('Y-m-d');
        }else {
            $Date = date('Y-m-d', $Date);
        }

        $UserInfo  = $this->UserInfo();
        $UserId    = $UserInfo['id'];

        if(!$UserId){
            return Response::json(array(
                'error'         => true,
                'error_message' => 'Bạn không có quyền truy cập trang này',
                'privilege'     => 0
            ));
        }

        $DateRange = $this->rangeWeek($Date);

        $TimeStart = getdate($DateRange['start']);
        $TimeEnd   = getdate($DateRange['end']);

        $Model     = new ReportMerchantModel;


        if((int)$TimeEnd['mon'] < (int)$TimeStart['mon'] && (int)$TimeEnd['year'] <= (int)$TimeStart['year']){
            return Response::json([
                'error'         => false,
                'error_message' => "",
                'data'          => []
            ]);
        }

        if((int)$TimeStart['mon'] == (int)$TimeEnd['mon'] && (int)$TimeStart['year'] == (int)$TimeEnd['year']){
            $Model  = $Model->where('date', '>=', (int)$TimeStart['mday'])
                            ->where('date', '<=', (int)$TimeEnd['mday'])
                            ->where('month', (int)$TimeEnd['mon'])
                            ->where('year', (int)$TimeEnd['year']);

        }else{
            $Model = $Model->where(function($query) use ($TimeStart, $TimeEnd) {
                $query
                ->where(function ($q) use ($TimeStart) {
                    $q->where('date', '>=', (int)$TimeStart['mday'])
                        ->where('month', (int)$TimeStart['mon'])
                        ->where('year', (int)$TimeStart['year']);
                })
                ->orWhere(function ($q) use ($TimeEnd) {
                    $q->where('date', '<=', (int)$TimeEnd['mday'])
                        ->where('month', (int)$TimeEnd['mon'])
                        ->where('year', (int)$TimeEnd['year']);
                });
            });
        }
        $Model = $Model->where('user_id', $UserId);
        $Model = $Model->groupBy('user_id')->orderBy('id','DESC');
        $Data  = $Model->with('User')->get(array(DB::raw(
                  'user_id,sum(generate) as generate,
                   sum(total_return) as total_return,
                   sum(sc_pvc) as sc_pvc,
                   sum(sc_cod) as sc_cod,
                   sum(sc_pbh) as sc_pbh,
                   sum(sc_discount_pvc) as sc_discount_pvc,
                   sum(sc_discount_cod) as sc_discount_cod,
                   sum(money_collect) as money_collect,
                   sum(sc_pvk) as sc_pvk'
              )))->toArray();

        $RetData = [
          'generate'      => 0,
          'money_collect' => 0,
          'revenus'       => 0,
        ];
        if(sizeof($Data) > 0){
          $Data = $Data[0];
          $RetData['generate'] = (int)$Data['generate'];
          $RetData['money_collect'] = (int)$Data['money_collect'];
          $RetData['revenus'] = (int)$Data['money_collect'] - ((int)$Data['sc_pvc'] + (int)$Data['sc_cod'] + (int)$Data['sc_pbh'] + (int)$Data['sc_pvk']);
        };

        return Response::json([
          'error'         => false,
          'error_message' => "",
          'data'          => $RetData
        ]);
        

       //return $this->rangeWeek('2015-08-17');
   }*/

    public function getGraphAccept(){
        $Date      = Input::has('date') ? Input::get('date') : "";
        $Cmd       = Input::has('cmd') ? Input::get('cmd') : "";
        if(empty($Date)){
            $Date = date('Y-m-d');
        }else {
            $Date = date('Y-m-d', $Date);
        }
        $DateRange = $this->rangeWeek($Date);
        $TimeStart = $DateRange['start'];
        $TimeEnd   = $DateRange['end'];
        
        $UserInfo  = $this->UserInfo();
        $UserId    = $UserInfo['id'];

        if(!$UserId){
          return Response::json(array(
            'error'         => true,
            'error_message' => 'Bạn không có quyền truy cập trang này',
            'privilege'     => 0
          ));
        }

        
        $Model = new OrdersModel;

        $Model = $Model->where('from_user_id', $UserId);
        $Model = $Model->where('time_accept','>=',$TimeStart);
        $Model = $Model->where('time_accept','<',$TimeEnd);

        $Model = $Model->select([
                  DB::raw('DATE(FROM_UNIXTIME(`time_accept`)) as time'),
                  DB::raw('COUNT(DISTINCT id) as total'),
                ]);
        $Model  = $Model->groupBy('time');
        $Data   = $Model->get()->toArray();

        $_RetData   = [];
        
        for ($i=0; $i < 7; $i++) { 
          $day           = date('Y-m-d', $TimeStart + 86400 * $i);
          $_RetData[$day] =  0;
        }

        foreach ($Data as $key => $value) {
          if(isset($_RetData[$value['time']])){
            $_RetData[$value['time']] = $value['total'];
          }
        }
        $RetData  = array_map(function ($key, $values){

            return $values;
        }, array_keys($_RetData), $_RetData);



        if($Cmd == 'demo'){
          $RetData = [
            261,
            234,
            22,
            1000,
            300,
            240,
            100
          ];
        }
        array_unshift($RetData, $RetData[0]);
        array_push($RetData, $RetData[sizeof($RetData) - 1]);

        return Response::json(array(
          'error'         => false,
          'error_message' => "",
          'data'          => $RetData,
        ));


    }

    public function rangeWeek($datestr) {
        date_default_timezone_set(date_default_timezone_get());
        $dt           = strtotime($datestr);

        $res['start'] = strtotime(date('N', $dt)==1 ? date('Y-m-d', $dt) : date('Y-m-d', strtotime('last monday', $dt)));
        $res['end']   = strtotime(date('N', $dt)==7 ? date('Y-m-d', $dt) : date('Y-m-d', strtotime('next sunday', $dt)));

        return $res;
    }
}
