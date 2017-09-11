<?php namespace ops;
class ReportCtrl extends BaseCtrl{
    private $record_date     = 25;
    private $data           = [];
    private $total          = 0;
    private $list_statistic = [];

    function __construct(){
        
    }

    public function __get_list_month(){
        $ListMonth  = [];
        for($i = 2016; $i<= 2019; $i++){
            for($j = 1; $j <= 12; $j++){
                $ListMonth[]    = $i.'-'.$j.'-'.$this->record_date;
            }
        }

        return $ListMonth;
    }

    public  function __ngay_chot_doanh_thu($TimeStart, $TimeEnd){
        $RecordDate = '';
        if(date('m', $TimeStart) == date('m', $TimeEnd)){ // Cùng tháng
            $RecordDate = date('Y-m-'.$this->record_date, $TimeStart);
        }else{
            if(strtotime(date('Y-m-'.$this->record_date), $TimeStart) >= $TimeStart){ // Cùng tháng với ngày bắt đầu
                $RecordDate = date('Y-m-'.$this->record_date, $TimeStart);
            }else{
                $RecordDate = date('Y-m-'.$this->record_date, $TimeEnd);
            }
        }

        if($TimeStart <= strtotime($RecordDate) && $TimeEnd > strtotime($RecordDate)){
            return $RecordDate;
        }else{
            return '';
        }
    }

    public function getStatisticReturnMerchant(){
        $Interval           = Input::has('interval')    ? (int)Input::get('interval')       : 0;
        $ListEmployee       = Input::has('employee')    ? trim(Input::get('employee'))      : '';

        $TimeEnd    = strtotime(date('Y-m-d')); // 0h hôm nay
        $End        = explode('-', date('Y-m-d', strtotime("-1 days")));
        $Data       = [];

        if(empty($ListEmployee)){
            return Response::json([
                'error'         => false,
                'message'       => 'Success',
                'data'          => $Data
            ]);
        }
        $ListEmployee   = explode(',', $ListEmployee);

        switch ($Interval) { // Xem dữ liệu tính từ ngày hôm qua
            case 1:
                $TimeStart      = strtotime('monday last week', strtotime(date('Y-m-d 00:00:00')));
                break;
            case 2:
                if($End[2] < $this->record_date){
                    $TimeStart      = strtotime(date('Y-m-'. $this->record_date, strtotime("-1 month")));
                }else{
                    $TimeStart      = strtotime(date('Y-m-'. $this->record_date));
                }

                break;
            case 3:
                if($End[2] < $this->record_date){
                    $TimeStart      = strtotime(date('Y-m-'. $this->record_date, strtotime("-1 month")));
                }else{
                    $TimeStart      = strtotime(date('Y-m-'. $this->record_date));
                }
                break;
            case 7:
                $TimeStart  = strtotime('-30 days', strtotime(date('Y-m-d 00:00:00')));
                break;
            case 8:
                $TimeStart  = strtotime('-90 days', strtotime(date('Y-m-d 00:00:00')));
                break;
            default:
                return Response::json([
                    'error'         => false,
                    'message'       => 'Success',
                    'data'          => $Data
                ]);
        }

        $SellerModel    = new \omsmodel\SellerModel;
        $SellerModel    = $SellerModel::where(function($query) use($TimeStart, $TimeEnd){
                                        $query->where(function($q) use($TimeStart, $TimeEnd){
                                            $q->where('first_time_incomings',0)
                                                ->where('first_time_pickup', '>=', $TimeStart)
                                                ->where('first_time_pickup', '<', $TimeEnd);
                                        })->orWhere(function($q) use($TimeStart, $TimeEnd){
                                            $q->where('first_time_incomings', '>=', $TimeStart)
                                                ->where('first_time_incomings', '<', $TimeEnd);
                                        });
                                    })->whereIn('seller_id', $ListEmployee);

        $SubModel     = clone $SellerModel;
        $ListCustomer = $SubModel->lists('user_id');
        if(empty($ListCustomer)){
            return Response::json([
                'error'         => false,
                'message'       => 'Success',
                'data'          => $Data
            ]);
        }

        $LogSellerModel = new \omsmodel\LogSellerModel;
        $LogCustomer    = $LogSellerModel::whereIn('user_id', $ListCustomer)->lists('user_id');
        if(empty($LogCustomer)){
            return Response::json([
                'error'         => false,
                'message'       => 'Success',
                'data'          => $Data
            ]);
        }

        $CustomerReturn = $SellerModel->whereIn('user_id',$LogCustomer)
                                      ->groupBy('seller_id')
                                      ->get(['seller_id',DB::raw('count(*) as total')])->toArray();
        if(empty($CustomerReturn)){
            return Response::json([
                'error'         => false,
                'message'       => 'Success',
                'data'          => $Data
            ]);
        }

        foreach($CustomerReturn as $val){
            $Data[$val['seller_id']]    = $val['total'];
        }

        return Response::json([
            'error'         => false,
            'message'       => 'Success',
            'data'          => $Data
        ]);
    }

    public function getStatisticKpiTeam(){
        $Interval           = Input::has('interval')    ? (int)Input::get('interval')       : 0;
        $ListEmployee       = Input::has('employee')    ? trim(Input::get('employee'))      : '';
        $Code               = Input::has('code')        ? trim(Input::get('code'))          : 0;

        $TimeEnd    = date('Y-m-d', strtotime("-1 days")); // chốt số liệu ngày hôm trước
        $End        = explode('-', $TimeEnd);
        $Data       = [];
        $CheckData  = [];
        $Firstmonth = [];
        $Nextmonth  = [];
        $Percent    = [];
        $Total      = [];

        $ListKpi    = \reportmodel\KPICategoryModel::where('code', $Code)
            ->where('active', 1)
            ->lists('id');
        if(empty($ListKpi) || empty($ListEmployee)){
            return Response::json([
                'error'         => false,
                'message'       => 'Success',
                'data'          => $Data,
                'firstmonth'    => $Firstmonth,
                'nextmonth'     => $Nextmonth,
                'percent'       => $Percent,
                'total'         => $Total
            ]);
        }

        $ListEmployee   = explode(',', $ListEmployee);

        //List Assign
        $Employee = \reportmodel\KPIConfigModel::whereIn('category_id', $ListKpi)->where('active',1)
                                                ->whereIn('user_id', $ListEmployee)
                                                ->get(['category_id', 'user_id'])->toArray();
        if(empty($Employee)){
            return Response::json([
                'error'         => false,
                'message'       => 'Success',
                'data'          => $Data,
                'firstmonth'    => $Firstmonth,
                'nextmonth'     => $Nextmonth,
                'percent'       => $Percent,
                'total'         => $Total
            ]);
        }
        $ReferEmployee = [];
        foreach($Employee as $val){
            $ReferEmployee[$val['category_id']][] = $val['user_id'];
        }

        $Model      = new \reportmodel\KPIModel;
        $Model      = $Model::whereIn('category_id', $ListKpi)->where('user_id', 0);

        // Lấy dữ liệu ngày mới nhất trước, nếu có thì tính tiếp, ko có thì coi = 0
        switch ($Interval) { // Xem dữ liệu tính từ ngày hôm qua
            case 1://this week
                $TimeStart      = date('Y-m-d', strtotime('monday this week'));

                /*
                 * Số liệu ngày cuối
                 */
                //Get Max Date
                $ModelMaxDate   = clone $Model;
                $MaxDate        = $ModelMaxDate->where('date', '>=', $TimeStart)
                                               ->groupBy('category_id')
                                               ->get(['category_id',DB::raw('MAX(date) as max_date')])->toArray();
                if(empty($MaxDate)){
                    break;
                }

                $ModelLast  = clone $Model;
                $LastData  = $ModelLast->where(function($query) use($MaxDate){
                    foreach($MaxDate as $val){
                        $query = $query->orWhere(function($q) use($val){
                            $q->where('category_id', $val['category_id'])
                                ->where('date', $val['max_date']);
                        });
                    }
                })->get(['category_id','succeed','total','percent','revenue_firstmonth','revenue_nextmonth','date','succeed_target'])->toArray();

                if(empty($LastData)){
                    break;
                }

                foreach($LastData as $val){
                    $Data[$val['category_id']]          = $val['succeed'];
                    $Percent[$val['category_id']]       = $val['percent'];
                    $Firstmonth[$val['category_id']]    = $val['revenue_firstmonth'];
                    $Nextmonth[$val['category_id']]     = $val['revenue_nextmonth'];
                    $Total[$val['category_id']]         = $val['total'];
                    $CheckData[$val['category_id']]     = ['end_date'      => strtotime($val['date'])];
                }
                unset($ModelMaxDate);
                unset($MaxDate);
                unset($ModelLast);
                unset($LastData);

                /*
                 * Số liệu ngày đầu tuần
                 */
                //Get Min Date
                $ModelMinDate   = clone $Model;
                $MinDate        = $ModelMinDate->where('date', '<', $TimeStart)
                                            ->groupBy('category_id')
                                            ->get(['category_id',DB::raw('Max(date) as max_date')])->toArray();

                if(!empty($MinDate)){
                    $ModelFirst = clone $Model;
                    $FirstData  = $ModelFirst->where(function($query) use($MinDate){
                        foreach($MinDate as $val){
                            $query = $query->orWhere(function($q) use($val){
                                $q->where('category_id', $val['category_id'])
                                    ->where('date', $val['max_date']);
                            });
                        }
                    })->get(['category_id','succeed','total','percent','revenue_firstmonth','revenue_nextmonth','date'])->toArray();
                    foreach($FirstData as $val){
                        if(isset($Data[$val['category_id']])){
                            $Data[$val['category_id']]          -= ($val['succeed']);
                            $Percent[$val['category_id']]       -= $val['percent'];
                            $Total[$val['category_id']]         -= $val['total'];
                            $Firstmonth[$val['category_id']]    -= $val['revenue_firstmonth'];
                            $Nextmonth[$val['category_id']]     -= $val['revenue_nextmonth'];
                        }
                    }
                    unset($ModelFirst);
                    unset($FirstData);
                }
                unset($ModelMinDate);
                unset($MinDate);


                //Trường hợp nằm ở 2 khoảng khác nhau  tính thêm số liệu của ngày chốt
                $RecordDate = $this->__ngay_chot_doanh_thu(strtotime($TimeStart), strtotime($TimeEnd));
                if(!empty($RecordDate)){
                    $ModelMaxDate   = clone $Model;
                    $MaxDate        = $ModelMaxDate->where('date', '<', $RecordDate)
                                                    ->groupBy('category_id')
                                                    ->get(['category_id',DB::raw('MAX(date) as max_date')])->toArray();
                    if(empty($MaxDate)){
                        break;
                    }

                    $ModelLast  = clone $Model;
                    $LastData  = $ModelLast->where(function($query) use($MaxDate){
                        foreach($MaxDate as $val){
                            $query = $query->orWhere(function($q) use($val){
                                $q->where('category_id', $val['category_id'])
                                    ->where('date', $val['max_date']);
                            });
                        }
                    })->get(['category_id','succeed','total','percent','revenue_firstmonth','revenue_nextmonth','date'])->toArray();
                    if(empty($LastData)){
                        break;
                    }

                    foreach($LastData as $val){
                        if(isset($Data[$val['category_id']]) && (strtotime($val['date']) < $CheckData[$val['category_id']]['end_date'])){
                            $Data[$val['category_id']]          += $val['succeed'];
                            $Percent[$val['category_id']]       += $val['percent'];
                            $Total[$val['category_id']]         += $val['total'];
                            $Firstmonth[$val['category_id']]    += $val['revenue_firstmonth'];
                            $Nextmonth[$val['category_id']]     += $val['revenue_nextmonth'];
                        }
                    }
                    unset($ModelMaxDate);
                    unset($MaxDate);
                    unset($ModelLast);
                    unset($LastData);
                }

                break;
            case 2: // Tháng này => lấy theo báo cáo ngày
                if($End[2] < $this->record_date){
                    $TimeStart      = strtotime(date('Y-m-'. $this->record_date, strtotime("-1 month")));
                }else{
                    $TimeStart      = strtotime(date('Y-m-'. $this->record_date));
                }

                /*
                 * Số liệu ngày cuối
                 */
                //Get Max Date
                $ModelMaxDate   = clone $Model;
                $MaxDate        = $ModelMaxDate->where('date','>=', $TimeStart)
                                                ->groupBy('category_id')
                                                ->get(['category_id',DB::raw('MAX(date) as max_date')])->toArray();
                if(empty($MaxDate)){
                    break;
                }

                $ListData       = $Model->where(function($query) use($MaxDate){
                    foreach($MaxDate as $val){
                        $query = $query->orWhere(function($q) use($val){
                              $q->where('category_id', $val['category_id'])
                                ->where('date', $val['max_date']);
                        });
                    }
                })->get(['category_id','revenue_firstmonth','revenue_nextmonth','succeed','total','percent','date'])->toArray();

                foreach($ListData as $val){
                    $Data[$val['category_id']]          = $val['succeed'];
                    $Percent[$val['category_id']]       = $val['percent'];
                    $Total[$val['category_id']]         = $val['total'];
                    $Firstmonth[$val['category_id']]    = $val['revenue_firstmonth'];
                    $Nextmonth[$val['category_id']]     = $val['revenue_nextmonth'];
                }
                break;
            case 3: // this year
                $TimeStart  = strtotime(date('Y-1-'. $this->record_date, strtotime("-1 month")));

                /*
                 * Số liệu ngày cuối
                 */
                //Get Max Date
                $ModelMaxDate   = clone $Model;
                $MaxDate        = $ModelMaxDate->where('date', '>=', $TimeStart)
                                               ->groupBy('category_id')
                                               ->get(['category_id',DB::raw('MAX(date) as max_date')])->toArray();
                if(empty($MaxDate)){
                    break;
                }

                $ModelLast  = clone $Model;
                $LastData  = $ModelLast->where(function($query) use($MaxDate){
                    foreach($MaxDate as $val){
                        $query = $query->orWhere(function($q) use($val){
                              $q->where('category_id', $val['category_id'])
                                ->where('date', $val['max_date']);
                        });
                    }
                })->get(['category_id','succeed','percent','total','revenue_firstmonth','revenue_nextmonth','date'])->toArray();

                if(empty($LastData)){
                    break;
                }

                foreach($LastData as $val){
                    $Data[$val['category_id']]          = $val['succeed'];
                    $Percent[$val['category_id']]       = $val['percent'];
                    $Total[$val['category_id']]         = $val['total'];
                    $Firstmonth[$val['category_id']]    = $val['revenue_firstmonth'];
                    $Nextmonth[$val['category_id']]     = $val['revenue_nextmonth'];

                    $CheckData[$val['category_id']]     = [
                        'end_date'    => strtotime($val['date'])
                    ];
                }
                unset($ModelMaxDate);
                unset($MaxDate);
                unset($ModelLast);
                unset($LastData);


                if($End[2] >= $this->record_date && $End[2] < ($this->record_date + 3)){// từ 25 đến 27
                    // Cộng thêm ngày  24 tháng này nhưng chưa được tính chốt KPI
                    $ModelMaxDate   = clone $Model;
                    $ModelSub       = clone $Model;
                    $MaxDate        = $ModelMaxDate->groupBy('category_id')
                        ->where('date','<',date('Y-m-'.$this->record_date, strtotime($TimeEnd)))
                        ->get(['category_id',DB::raw('MAX(date) as max_date')])->toArray();

                    if(!empty($MaxDate)){
                        $Record  = $ModelSub->where(function($query) use($MaxDate){
                            foreach($MaxDate as $val){
                                $query = $query->orWhere(function($q) use($val){
                                    $q->where('category_id', $val['category_id'])->where('date', $val['max_date']);
                                });
                            }
                        })->get(['category_id','succeed','percent','total','revenue_firstmonth','revenue_nextmonth','date'])->toArray();

                        foreach($Record as $val){
                            if(isset($Data[$val['category_id']]) && ($CheckData[$val['category_id']]['end_date'] > strtotime($val['date']))){
                                $Data[$val['category_id']]          += $val['succeed'];
                                $Percent[$val['category_id']]       += $val['percent'];
                                $Total[$val['category_id']]         += $val['total'];
                                $Firstmonth[$val['category_id']]    += $val['revenue_firstmonth'];
                                $Nextmonth[$val['category_id']]     += $val['revenue_nextmonth'];
                            }
                        }

                        unset($MaxDate);
                        unset($ModelMaxDate);
                        unset($ModelSub);
                        unset($Record);
                    }
                }

                // Số liệu các tháng trước, lấy theo báo cáo kpi
                $Record = \reportmodel\KPIEmployeeDetailModel::whereIn('category_id', $ListKpi)
                                                            ->where('year', $End[0])
                                                            ->groupBy('month')
                                                            ->groupBy('year')
                                                            ->groupBy('category_id')
                                                            ->get(['month','year','category_id','succeed','total','percent','revenue_firstmonth','revenue_nextmonth'])
                                                            ->toArray();

                foreach($Record as $val){
                    if(!isset($Data[$val['category_id']]) || $CheckData[$val['category_id']]['end_date'] > strtotime("-1 day", strtotime(date($val['year'].'-'.$val['month'].'-'.$this->record_date)))){
                        if(!isset($Data[$val['category_id']])){
                            $Data[$val['category_id']]          = $val['succeed'];
                            $Percent[$val['category_id']]       = $val['percent'];
                            $Total[$val['category_id']]         = $val['total'];
                            $Firstmonth[$val['category_id']]    = $val['revenue_firstmonth'];
                            $Nextmonth[$val['category_id']]     = $val['revenue_nextmonth'];
                        }else{
                            $Data[$val['category_id']]          += $val['succeed'];
                            $Percent[$val['category_id']]       += $val['percent'];
                            $Total[$val['category_id']]         += $val['total'];
                            $Firstmonth[$val['category_id']]    += $val['revenue_firstmonth'];
                            $Nextmonth[$val['category_id']]     += $val['revenue_nextmonth'];
                        }
                    }
                }
                break;
            case 7: // 30 ngày trước
                $TimeStart  = date('Y-m-d', strtotime("-31 days"));
                /*
                 * Số liệu ngày cuối
                 */
                //Get Max Date
                $ModelMaxDate   = clone $Model;
                $MaxDate        = $ModelMaxDate->where('date', '>=', $TimeStart)
                                               ->groupBy('category_id')
                                               ->get(['user_id','category_id',DB::raw('MAX(date) as max_date')])->toArray();
                if(empty($MaxDate)){
                    break;
                }

                $ModelLast  = clone $Model;
                $LastData  = $ModelLast->where(function($query) use($MaxDate){
                    foreach($MaxDate as $val){
                        $query = $query->orWhere(function($q) use($val){
                            $q->where('category_id', $val['category_id'])
                                ->where('date', $val['max_date']);
                        });
                    }
                })->get(['category_id','succeed','percent','total','revenue_firstmonth','revenue_nextmonth','date'])->toArray();

                if(empty($LastData)){
                    break;
                }

                foreach($LastData as $val){
                    $Data[$val['category_id']]          = $val['succeed'];
                    $Percent[$val['category_id']]       = $val['percent'];
                    $Total[$val['category_id']]         = $val['total'];
                    $Firstmonth[$val['category_id']]    = $val['revenue_firstmonth'];
                    $Nextmonth[$val['category_id']]     = $val['revenue_nextmonth'];
                    $CheckData[$val['category_id']]     = [
                        'end_date'    => strtotime($val['date'])
                    ];
                }
                unset($ModelMaxDate);
                unset($MaxDate);
                unset($ModelLast);
                unset($LastData);

                /*
                 * Số liệu ngày đầu
                 */
                //Get Min Date
                $ModelMinDate   = clone $Model;
                $MinDate        = $ModelMinDate->where('date', '<', $TimeStart)
                                               ->groupBy('category_id')
                                                ->get(['category_id',DB::raw('Max(date) as max_date')])->toArray();
                if(!empty($MinDate)){
                    $ModelFirst = clone $Model;
                    $FirstData  = $ModelFirst->where(function($query) use($MinDate){
                        foreach($MinDate as $val){
                            $query = $query->orWhere(function($q) use($val){
                                $q->where('category_id', $val['category_id'])
                                    ->where('date', $val['max_date']);
                            });
                        }
                    })->get(['date','category_id','succeed','percent','total','revenue_firstmonth','revenue_nextmonth'])->toArray();
                    if(empty($FirstData)){
                        break;
                    }

                    foreach($FirstData as $val){
                        if(isset($Data[$val['category_id']] )){
                            $Data[$val['category_id']]          -= ($val['succeed']);
                            $Percent[$val['category_id']]       -= $val['percent'];
                            $Total[$val['category_id']]         -= $val['total'];
                            $Firstmonth[$val['category_id']]    -= $val['revenue_firstmonth'];
                            $Nextmonth[$val['category_id']]     -= $val['revenue_nextmonth'];
                        }
                    }
                    unset($ModelFirst);
                    unset($FirstData);
                }
                unset($ModelMinDate);
                unset($MinDate);

                // Ngày ngắt quoãng => ngày chốt kpi
                $TimeNextMonth = strtotime('+1 months', strtotime($TimeStart));
                if($TimeNextMonth >= strtotime($TimeEnd)){// Có 1 khoảng
                    $RecordFirst = $this->__ngay_chot_doanh_thu(strtotime($TimeStart), strtotime($TimeEnd));
                    if(!empty($RecordFirst)){
                        $ModelMaxDate   = clone $Model;
                        $MaxDate        = $ModelMaxDate->where('date', '<', $RecordFirst)
                                                       ->groupBy('category_id')
                                                       ->get(['category_id',DB::raw('MAX(date) as max_date')])->toArray();
                        if(empty($MaxDate)){
                            break;
                        }

                        $ModelLast  = clone $Model;
                        $LastData  = $ModelLast->where(function($query) use($MaxDate){
                            foreach($MaxDate as $val){
                                $query = $query->orWhere(function($q) use($val){
                                    $q->where('category_id', $val['category_id'])
                                        ->where('date', $val['max_date']);
                                });
                            }
                        })->get(['category_id','succeed','total','percent','revenue_firstmonth','revenue_nextmonth','date'])->toArray();
                        if(empty($LastData)){
                            break;
                        }

                        foreach($LastData as $val){
                            if(isset($Data[$val['category_id']]) && (strtotime($val['date']) < $CheckData[$val['category_id']]['end_date'])){
                                $Data[$val['category_id']]          += $val['succeed'];
                                $Percent[$val['category_id']]       += $val['percent'];
                                $Total[$val['category_id']]         += $val['total'];
                                $Firstmonth[$val['category_id']]    += $val['revenue_firstmonth'];
                                $Nextmonth[$val['category_id']]     += $val['revenue_nextmonth'];
                            }
                        }
                        unset($ModelMaxDate);
                        unset($MaxDate);
                        unset($ModelLast);
                        unset($LastData);
                    }
                }else{// Có thể có 2 khoảng
                    $RecordFirst = $this->__ngay_chot_doanh_thu(strtotime($TimeStart), strtotime($TimeNextMonth));
                    if(!empty($RecordFirst)){
                        $ModelMaxDate   = clone $Model;
                        $MaxDate        = $ModelMaxDate->where('date', '<', $RecordFirst)
                                                       ->groupBy('category_id')
                                                       ->get(['category_id',DB::raw('MAX(date) as max_date')])->toArray();
                        if(!empty($MaxDate)){
                            $ModelLast  = clone $Model;
                            $LastData  = $ModelLast->where(function($query) use($MaxDate){
                                foreach($MaxDate as $val){
                                    $query = $query->orWhere(function($q) use($val){
                                        $q->where('category_id', $val['category_id'])
                                            ->where('date', $val['max_date']);
                                    });
                                }
                            })->get(['category_id','succeed','percent','total','revenue_firstmonth','revenue_nextmonth','date'])->toArray();

                            foreach($LastData as $val){
                                if(isset($Data[$val['category_id']]) && (strtotime($val['date']) < $CheckData[$val['category_id']]['end_date'])){
                                    $Data[$val['category_id']]          += $val['succeed'];
                                    $Percent[$val['category_id']]       += $val['percent'];
                                    $Total[$val['category_id']]         += $val['total'];
                                    $Firstmonth[$val['category_id']]    += $val['revenue_firstmonth'];
                                    $Nextmonth[$val['category_id']]     += $val['revenue_nextmonth'];
                                }
                            }
                            unset($ModelLast);
                            unset($LastData);
                        }
                        unset($ModelMaxDate);
                        unset($MaxDate);
                    }

                    $RecordSecond = $this->__ngay_chot_doanh_thu(strtotime($TimeNextMonth), strtotime($TimeEnd));
                    if(!empty($RecordFirst)){
                        $ModelMaxDate   = clone $Model;
                        $MaxDate        = $ModelMaxDate->where('date', '<', $RecordSecond)
                                                    ->groupBy('category_id')
                                                    ->get(['category_id',DB::raw('MAX(date) as max_date')])->toArray();

                        if(!empty($MaxDate)){
                            $ModelLast  = clone $Model;
                            $LastData  = $ModelLast->where(function($query) use($MaxDate){
                                foreach($MaxDate as $val){
                                    $query = $query->orWhere(function($q) use($val){
                                        $q->where('category_id', $val['category_id'])
                                            ->where('date', $val['max_date']);
                                    });
                                }
                            })->get(['category_id','succeed','percent','total','revenue_firstmonth','revenue_nextmonth','date'])->toArray();
                            foreach($LastData as $val){
                                if(isset($Data[$val['category_id']]) && (strtotime($val['date']) < $CheckData[$val['category_id']]['end_date'])){
                                    $Data[$val['category_id']]          += $val['succeed'];
                                    $Percent[$val['category_id']]       += $val['percent'];
                                    $Total[$val['category_id']]         += $val['total'];
                                    $Firstmonth[$val['category_id']]    += $val['revenue_firstmonth'];
                                    $Nextmonth[$val['category_id']]     += $val['revenue_nextmonth'];
                                }
                            }
                            unset($ModelLast);
                            unset($LastData);
                        }
                        unset($ModelMaxDate);
                        unset($MaxDate);
                    }
                }
                break;

            case 8: // 90 ngày trước
                $TimeStart      = date('Y-m-d', strtotime("-91 days"));
                $Time           = explode('-', $TimeStart);

                /*
                 * Số liệu ngày cuối
                 */
                //Get Max Date
                $ModelMaxDate   = clone $Model;
                $MaxDate        = $ModelMaxDate->where('date', '>=', $TimeStart)
                                               ->groupBy('category_id')
                                               ->get(['category_id',DB::raw('MAX(date) as max_date')])->toArray();
                if(empty($MaxDate)){
                    break;
                }

                $ModelLast  = clone $Model;
                $LastData  = $ModelLast->where(function($query) use($MaxDate){
                    foreach($MaxDate as $val){
                        $query = $query->orWhere(function($q) use($val){
                            $q->where('category_id', $val['category_id'])
                                ->where('date', $val['max_date']);
                        });
                    }
                })->get(['category_id','succeed','percent','total','revenue_firstmonth','revenue_nextmonth','date'])->toArray();

                if(empty($LastData)){
                    break;
                }

                foreach($LastData as $val){
                    $Data[$val['category_id']]          = $val['succeed'];
                    $Percent[$val['category_id']]       = $val['percent'];
                    $Total[$val['category_id']]         = $val['total'];
                    $Firstmonth[$val['category_id']]    = $val['revenue_firstmonth'];
                    $Nextmonth[$val['category_id']]     = $val['revenue_nextmonth'];
                    $CheckData[$val['category_id']]     = strtotime($val['date']);
                }
                unset($ModelMaxDate);
                unset($MaxDate);
                unset($ModelLast);
                unset($LastData);

                /*
                 * Số liệu ngày đầu
                 */
                //Get Min Date
                $ModelMinDate   = clone $Model;
                $MinDate        = $ModelMinDate->where('date', '<', $TimeStart)
                                                ->groupBy('category_id')
                                                ->get(['category_id',DB::raw('Max(date) as max_date')])->toArray();
                if(!empty($MinDate)){
                    $ModelFirst = clone $Model;
                    $FirstData  = $ModelFirst->where(function($query) use($MinDate){
                        foreach($MinDate as $val){
                            $query = $query->orWhere(function($q) use($val){
                                $q->where('category_id', $val['category_id'])
                                    ->where('date', $val['max_date']);
                            });
                        }
                    })->get(['category_id','succeed','percent','total','revenue_firstmonth','revenue_nextmonth'])->toArray();
                    foreach($FirstData as $val){
                        if(isset($Data[$val['category_id']] )){
                            $Data[$val['category_id']]          -= ($val['succeed']);
                            $Percent[$val['category_id']]       -= $val['percent'];
                            $Total[$val['category_id']]         -= $val['total'];
                            $Firstmonth[$val['category_id']]    -= $val['revenue_firstmonth'];
                            $Nextmonth[$val['category_id']]     -= $val['revenue_nextmonth'];
                        }
                    }
                    unset($ModelFirst);
                    unset($FirstData);
                }
                unset($ModelMinDate);
                unset($MinDate);



                //Lấy số liệu cuối cùng của tháng đầu tiên
                if($Time[2] >= 25){
                    $DateFirst  = date('Y-n-'.$this->record_date, strtotime('+1 month', strtotime($TimeStart)));
                }else{
                    $DateFirst  = date('Y-n-'.$this->record_date, strtotime($TimeStart));
                }

                // Lấy số liệu các tháng nằm giữa
                $ListMonth  = $this->__get_list_month();

                $Open       = 0;
                $ListDate   = [];
                foreach($ListMonth as $val){
                    if(strtotime($val) > strtotime($TimeEnd)){
                        break;
                    }

                    if($Open == 1){
                        $val = explode('-', $val);
                        $ListDate[] = [
                            'month' => $val[1],
                            'year'  => $val[0]
                        ];
                    }

                    if($val == $DateFirst){
                        $Open = 1;
                    }
                }

                if(!empty($ListDate)){
                    $Record = \reportmodel\KPIEmployeeDetailModel::whereIn('category_id', $ListKpi)
                        ->where(function($query) use($ListDate){
                            foreach($ListDate as $val){
                                $query->orWhere(function($q)use($val){
                                    $q->where('year', $val['year'])
                                        ->where('month', $val['month']);
                                });
                            }
                        })
                        ->groupBy('year')
                        ->groupBy('month')
                        ->groupBy('category_id')
                        ->get(['month','year','category_id','succeed','total','percent','revenue_firstmonth','revenue_nextmonth'])
                        ->toArray();

                    foreach($Record as $val){
                        $Time = strtotime("-1 day", strtotime(date($val['year'].'-'.$val['month'].'-'.$this->record_date)));
                        if(isset($Data[$val['category_id']])){
                            if(!isset($CheckData[$val['category_id']]) || ($CheckData[$val['category_id']] > $Time)){
                                $Data[$val['category_id']]          += $val['succeed'];
                                $Percent[$val['category_id']]       += $val['percent'];
                                $Total[$val['category_id']]         += $val['total'];
                                $Firstmonth[$val['category_id']]    += $val['revenue_firstmonth'];
                                $Nextmonth[$val['category_id']]     += $val['revenue_nextmonth'];
                            }
                        }else{
                            $Data[$val['category_id']]          = $val['succeed'];
                            $Percent[$val['category_id']]       = $val['percent'];
                            $Total[$val['category_id']]         = $val['total'];
                            $Firstmonth[$val['category_id']]    = $val['revenue_firstmonth'];
                            $Nextmonth[$val['category_id']]     = $val['revenue_nextmonth'];
                        }
                    }
                }
                break;
            default:
                break;
        }

        $Refer = $Data;
        $Data  = [];
        if(!empty($Refer)){
            foreach ($Refer as $key => $val){
                if(isset($ReferEmployee[$key])){
                    foreach($ReferEmployee[$key] as $v){
                        $Data[$v]   = $val;
                    }
                }
            }
        }

        $Refer      = $Percent;
        $Percent    = [];
        if(!empty($Refer)){
            foreach ($Refer as $key => $val){
                if(isset($ReferEmployee[$key])){
                    foreach($ReferEmployee[$key] as $v){
                        $Percent[$v]   = $val;
                    }
                }
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'Success',
            'data'          => $Data,
            'firstmonth'    => $Firstmonth,
            'nextmonth'     => $Nextmonth,
            'percent'       => $Percent,
            'total'         => $Total
        ]);
    }



    public function getStatisticKpi(){
        $Interval           = Input::has('interval')    ? (int)Input::get('interval')           : 0;
        $ListEmployee       = Input::has('employee')    ? trim(Input::get('employee'))          : '';
        $Code               = Input::has('code')        ? strtolower(trim(Input::get('code')))  : 0;
        $IsCompare          = Input::has('is_compare')  ? (int)Input::get('is_compare')         : 0;

        $TimeEnd    = date('Y-m-d', strtotime("-1 days")); // chốt số liệu ngày hôm trước
        if(in_array($Code, ['revenue', 'team revenue'])){ // doanh thu chốt số liệu 3 ngày trước
            $TimeEnd    = date('Y-m-d', strtotime("-3 days"));
        }

        $End        = explode('-', $TimeEnd);
        $Data       = [];
        $Firstmonth = [];
        $Nextmonth  = [];
        $CheckData  = [];
        $Percent    = [];
        $Target     = ['total'  => 0, 'succeed_target' => 0, 'percent_target' => 0];

        $ListKpi    = \reportmodel\KPICategoryModel::where('code', $Code)
                                                    ->where('active', 1)
                                                    ->lists('id');
        if(empty($ListKpi) || empty($ListEmployee)){
            return Response::json([
                'error'         => false,
                'message'       => 'Success',
                'data'          => $Data,
                'firstmonth'    => $Firstmonth,
                'nextmonth'     => $Nextmonth,
                'percent'       => $Percent,
                'target'        => $Target
            ]);
        }

        $ListEmployee   = explode(',', $ListEmployee);

        $Model      = new \reportmodel\KPIModel;
        $Model      = $Model::whereIn('category_id', $ListKpi)->whereIn('user_id', $ListEmployee);

        // Lấy dữ liệu ngày mới nhất trước, nếu có thì tính tiếp, ko có thì coi = 0
        switch ($Interval) { // Xem dữ liệu tính từ ngày hôm qua
            case 1://this week
                $TimeStart  = date('Y-m-d', strtotime('monday this week'));
                if($IsCompare){
                    $Period     = strtotime($TimeEnd) - strtotime($TimeStart);
                    $TimeStart  = date('Y-m-d', strtotime('monday last week'));
                    $TimeEnd    = date('Y-m-d', (strtotime($TimeStart) + $Period));
                }

                /*
                 * Số liệu ngày cuối
                 */
                //Get Max Date
                $ModelMaxDate   = clone $Model;
                $MaxDate        = $ModelMaxDate->where('date','>=', $TimeStart)
                                               ->where('date','<=',$TimeEnd)
                                               ->groupBy('user_id')->groupBy('category_id')
                                               ->get(['user_id','category_id',DB::raw('MAX(date) as max_date')])
                                               ->toArray();
                if(empty($MaxDate)){
                    break;
                }

                $ModelLast  = clone $Model;
                $LastData  = $ModelLast->where(function($query) use($MaxDate){
                    foreach($MaxDate as $val){
                        $query = $query->orWhere(function($q) use($val){
                            $q->where('user_id', $val['user_id'])
                                ->where('category_id', $val['category_id'])
                                ->where('date', $val['max_date']);
                        });
                    }
                })->get(['user_id','category_id','succeed','percent','revenue_firstmonth','revenue_nextmonth','date','succeed_target','percent_target'])->toArray();

                if(empty($LastData)){
                    break;
                }

                foreach($LastData as $val){
                    $Data[$val['user_id']]          = $val['succeed'];
                    $Firstmonth[$val['user_id']]    = $val['revenue_firstmonth'];
                    $Nextmonth[$val['user_id']]     = $val['revenue_nextmonth'];
                    $Percent[$val['user_id']]       = $val['percent'];
                    $CheckData[$val['user_id']]     = ['end_date'      => strtotime($val['date'])];

                    $Target['succeed_target']       +=  $val['succeed_target'];
                    $Target['percent_target']       +=  $val['percent_target'];
                    $Target['total']                +=  1;
                }
                unset($ModelMaxDate);
                unset($MaxDate);
                unset($ModelLast);
                unset($LastData);

                /*
                 * Số liệu ngày đầu tuần
                 */
                //Get Min Date
                $ModelMinDate   = clone $Model;
                $MinDate        = $ModelMinDate->where('date', '<', $TimeStart)
                                               ->groupBy('user_id')->groupBy('category_id')
                                               ->get(['user_id','category_id',DB::raw('Max(date) as max_date')])->toArray();

                if(!empty($MinDate)){
                    $ModelFirst = clone $Model;
                    $FirstData  = $ModelFirst->where(function($query) use($MinDate){
                        foreach($MinDate as $val){
                            $query = $query->orWhere(function($q) use($val){
                                $q->where('user_id', $val['user_id'])
                                    ->where('category_id', $val['category_id'])
                                    ->where('date', $val['max_date']);
                            });
                        }
                    })->get(['user_id','category_id','succeed','percent','revenue_firstmonth','revenue_nextmonth','date','succeed_target'])->toArray();
                    foreach($FirstData as $val){
                        if(isset($Data[$val['user_id']])){
                            $Data[$val['user_id']]          -= ($val['succeed']);
                            $Percent[$val['user_id']]       -= $val['percent'];
                            $Firstmonth[$val['user_id']]    -= $val['revenue_firstmonth'];
                            $Nextmonth[$val['user_id']]     -= $val['revenue_nextmonth'];
                        }
                    }
                    unset($ModelFirst);
                    unset($FirstData);
                }
                unset($ModelMinDate);
                unset($MinDate);

                //Trường hợp nằm ở 2 khoảng khác nhau  tính thêm số liệu của ngày chốt
                $RecordDate = $this->__ngay_chot_doanh_thu(strtotime($TimeStart), strtotime($TimeEnd));
                if(!empty($RecordDate)){
                    $ModelMaxDate   = clone $Model;
                    $MaxDate        = $ModelMaxDate->where('date', '<', $RecordDate)
                                                   ->groupBy('user_id')->groupBy('category_id')
                                                   ->get(['user_id','category_id',DB::raw('MAX(date) as max_date')])->toArray();
                    if(empty($MaxDate)){
                        break;
                    }

                    $ModelLast  = clone $Model;
                    $LastData  = $ModelLast->where(function($query) use($MaxDate){
                                                foreach($MaxDate as $val){
                                                    $query = $query->orWhere(function($q) use($val){
                                                        $q->where('user_id', $val['user_id'])
                                                            ->where('category_id', $val['category_id'])
                                                            ->where('date', $val['max_date']);
                                                    });
                                                }
                                            })->get(['user_id','category_id','succeed','percent','revenue_firstmonth','revenue_nextmonth','date','succeed_target'])->toArray();
                    if(empty($LastData)){
                        break;
                    }

                    foreach($LastData as $val){
                        if(isset($Data[$val['user_id']]) && (strtotime($val['date']) < $CheckData[$val['user_id']]['end_date'])){
                            $Data[$val['user_id']]                  += $val['succeed'];
                            $Percent[$val['user_id']]               += $val['percent'];
                            $Firstmonth[$val['user_id']]            += $val['revenue_firstmonth'];
                            $Nextmonth[$val['user_id']]             += $val['revenue_nextmonth'];
                        }
                    }
                    unset($ModelMaxDate);
                    unset($MaxDate);
                    unset($ModelLast);
                    unset($LastData);
                }

                $Target['succeed_target']       =  round(($Target['succeed_target']/4),0);
                $Target['percent_target']       =  round(($Target['percent_target']/$Target['total']),3);
                break;
            case 2: // Tháng này => lấy theo báo cáo ngày
                if($End[2] < $this->record_date){
                    $TimeStart      = date('Y-m-'. $this->record_date, strtotime("-1 month"));
                }else{
                    $TimeStart      = date('Y-m-'. $this->record_date);
                }

                if($IsCompare){
                    $Period     = strtotime($TimeEnd) - strtotime($TimeStart);
                    $TimeStart  = date('Y-m-'. $this->record_date, strtotime("-1 month", strtotime($TimeStart)));
                    $TimeEnd    = date('Y-m-d', (strtotime($TimeStart) + $Period));
                }

                /*
                 * Số liệu ngày cuối
                 */
                //Get Max Date
                $ModelMaxDate   = clone $Model;
                $MaxDate        = $ModelMaxDate->where('date','>=', $TimeStart)
                                               ->where('date','<=',$TimeEnd)
                                               ->groupBy('user_id')->groupBy('category_id')
                                               ->get(['category_id','user_id',DB::raw('MAX(date) as max_date')])->toArray();
                if(empty($MaxDate)){
                    break;
                }

                $ListData       = $Model->where(function($query) use($MaxDate){
                                                foreach($MaxDate as $val){
                                                    $query = $query->orWhere(function($q) use($val){
                                                          $q->where('user_id', $val['user_id'])
                                                            ->where('category_id', $val['category_id'])
                                                            ->where('date', $val['max_date']);
                                                    });
                                                }
                                            })->get(['user_id','percent','category_id','revenue_firstmonth','revenue_nextmonth','succeed','date','succeed_target','percent_target'])->toArray();

                foreach($ListData as $val){
                    $Data[$val['user_id']]          = $val['succeed'];
                    $Percent[$val['user_id']]       = $val['percent'];
                    $Firstmonth[$val['user_id']]    = $val['revenue_firstmonth'];
                    $Nextmonth[$val['user_id']]     = $val['revenue_nextmonth'];

                    $Target['succeed_target']       +=  $val['succeed_target'];
                    $Target['percent_target']       +=  $val['percent_target'];
                    $Target['total']                +=  1;
                }

                $Target['percent_target']   =  round(($Target['percent_target']/$Target['total']),3);
                break;
            case 3: // this year
                $TimeStart  = strtotime(date('Y-1-'. $this->record_date, strtotime("-1 month")));

                if($IsCompare){
                    $Period     = strtotime($TimeEnd) - strtotime($TimeStart);
                    $TimeStart  = date('Y-m-'. $this->record_date, strtotime("-1 year", strtotime($TimeStart)));
                    $TimeEnd    = date('Y-m-d', (strtotime($TimeStart) + $Period));
                }

                /*
                 * Số liệu ngày cuối
                 */
                //Get Max Date
                $ModelMaxDate   = clone $Model;
                $MaxDate        = $ModelMaxDate->where('date','>=', $TimeStart)
                                               ->where('date','<=',$TimeEnd)
                                               ->groupBy('user_id')->groupBy('category_id')
                                               ->get(['user_id','category_id',DB::raw('MAX(date) as max_date')])->toArray();
                if(empty($MaxDate)){
                    break;
                }

                $ModelLast  = clone $Model;
                $LastData  = $ModelLast->where(function($query) use($MaxDate){
                    foreach($MaxDate as $val){
                        $query = $query->orWhere(function($q) use($val){
                            $q->where('user_id', $val['user_id'])
                                ->where('category_id', $val['category_id'])
                                ->where('date', $val['max_date']);
                        });
                    }
                })->get(['user_id','category_id','succeed','percent','revenue_firstmonth','revenue_nextmonth','date','succeed_target','percent_target'])->toArray();

                if(empty($LastData)){
                    break;
                }

                foreach($LastData as $val){
                    $Data[$val['user_id']]          = $val['succeed'];
                    $Percent[$val['user_id']]       = $val['percent'];
                    $Firstmonth[$val['user_id']]    = $val['revenue_firstmonth'];
                    $Nextmonth[$val['user_id']]     = $val['revenue_nextmonth'];

                    $CheckData[$val['user_id']]     = [
                        'end_date'    => strtotime($val['date'])
                    ];

                    $Target['succeed_target']       +=  $val['succeed_target'];
                    $Target['percent_target']       +=  $val['percent_target'];
                    $Target['total']                +=  1;
                }
                unset($ModelMaxDate);
                unset($MaxDate);
                unset($ModelLast);
                unset($LastData);


                if($End[2] >= $this->record_date && $End[2] < ($this->record_date + 3)){// từ 25 đến 27
                    // Cộng thêm ngày  24 tháng này nhưng chưa được tính chốt KPI
                    $ModelMaxDate   = clone $Model;
                    $ModelSub       = clone $Model;
                    $MaxDate        = $ModelMaxDate->groupBy('user_id')->groupBy('category_id')
                                                   ->where('date','<',date('Y-m-'.$this->record_date, strtotime($TimeEnd)))
                                                   ->get(['user_id','category_id',DB::raw('MAX(date) as max_date')])->toArray();

                    if(!empty($MaxDate)){
                        $Record  = $ModelSub->where(function($query) use($MaxDate){
                            foreach($MaxDate as $val){
                                $query = $query->orWhere(function($q) use($val){
                                    $q->where('user_id', $val['user_id'])->where('category_id', $val['category_id'])->where('date', $val['max_date']);
                                });
                            }
                        })->get(['user_id','category_id','succeed','percent','revenue_firstmonth','revenue_nextmonth','date','succeed_target','percent_target'])->toArray();

                        foreach($Record as $val){
                            if(isset($Data[$val['user_id']]) && ($CheckData[$val['user_id']]['end_date'] > strtotime($val['date']))){
                                $Data[$val['user_id']]          += $val['succeed'];
                                $Percent[$val['user_id']]       += $val['percent'];
                                $Firstmonth[$val['user_id']]    += $val['revenue_firstmonth'];
                                $Nextmonth[$val['user_id']]     += $val['revenue_nextmonth'];

                                $Target['succeed_target']       +=  $val['succeed_target'];
                                $Target['percent_target']       +=  $val['percent_target'];
                                $Target['total']                +=  1;
                            }
                        }

                        unset($MaxDate);
                        unset($ModelMaxDate);
                        unset($ModelSub);
                        unset($Record);
                    }
                }

                // Số liệu các tháng trước, lấy theo báo cáo kpi
                $Record = \reportmodel\KPIEmployeeDetailModel::whereIn('category_id', $ListKpi)
                            ->where('year', $End[0])
                            ->whereIn('user_id', $ListEmployee)
                            ->get(['month','year','user_id','category_id','succeed','percent','revenue_firstmonth','revenue_nextmonth','succeed_target','percent_target'])
                            ->toArray();

                foreach($Record as $val){
                    if(!isset($Data[$val['user_id']]) || $CheckData[$val['user_id']]['end_date'] > strtotime("-1 day", strtotime(date($val['year'].'-'.$val['month'].'-'.$this->record_date)))){
                        if(!isset($Data[$val['user_id']])){
                            $Data[$val['user_id']]          = $val['succeed'];
                            $Percent[$val['user_id']]       = $val['percent'];
                            $Firstmonth[$val['user_id']]    = $val['revenue_firstmonth'];
                            $Nextmonth[$val['user_id']]     = $val['revenue_nextmonth'];
                        }else{
                            $Data[$val['user_id']]          += $val['succeed'];
                            $Percent[$val['user_id']]       += $val['percent'];
                            $Firstmonth[$val['user_id']]    += $val['revenue_firstmonth'];
                            $Nextmonth[$val['user_id']]     += $val['revenue_nextmonth'];
                        }

                        $Target['succeed_target']       +=  $val['succeed_target'];
                        $Target['percent_target']       +=  $val['percent_target'];
                        $Target['total']                +=  1;
                    }
                }

                $Target['percent_target']       =  round(($Target['percent_target']/$Target['total']),3);
                break;
            case 7: // 30 ngày trước
                $TimeStart  = date('Y-m-d', strtotime("-31 days"));

                if($IsCompare){
                    $TimeEnd    = date('Y-m-d', strtotime("-1 days", strtotime($TimeStart)));
                    $TimeStart  = date('Y-m-d', strtotime("-31 days", strtotime($TimeStart)));
                }

                /*
                 * Số liệu ngày cuối
                 */
                //Get Max Date
                $ModelMaxDate   = clone $Model;
                $MaxDate        = $ModelMaxDate->where('date', '>=', $TimeStart)
                                               ->where('date','<=',$TimeEnd)
                                               ->groupBy('user_id')->groupBy('category_id')
                                               ->get(['user_id','category_id',DB::raw('MAX(date) as max_date')])->toArray();
                if(empty($MaxDate)){
                    break;
                }

                $ModelLast  = clone $Model;
                $LastData  = $ModelLast->where(function($query) use($MaxDate){
                    foreach($MaxDate as $val){
                        $query = $query->orWhere(function($q) use($val){
                            $q->where('user_id', $val['user_id'])
                                ->where('category_id', $val['category_id'])
                                ->where('date', $val['max_date']);
                        });
                    }
                })->get(['user_id','category_id','percent','succeed','revenue_firstmonth','revenue_nextmonth','date','succeed_target','percent_target'])->toArray();

                if(empty($LastData)){
                    break;
                }

                foreach($LastData as $val){
                    $Data[$val['user_id']]          = $val['succeed'];
                    $Percent[$val['user_id']]       = $val['percent'];
                    $Firstmonth[$val['user_id']]    = $val['revenue_firstmonth'];
                    $Nextmonth[$val['user_id']]     = $val['revenue_nextmonth'];
                    $CheckData[$val['user_id']]     = [
                        'end_date'    => strtotime($val['date'])
                    ];
                    $Target['succeed_target']       +=  $val['succeed_target'];
                    $Target['percent_target']       +=  $val['percent_target'];
                    $Target['total']                +=  1;
                }
                unset($ModelMaxDate);
                unset($MaxDate);
                unset($ModelLast);
                unset($LastData);

                /*
                 * Số liệu ngày đầu
                 */
                //Get Min Date
                $ModelMinDate   = clone $Model;
                $MinDate        = $ModelMinDate->where('date', '<', $TimeStart)
                                               ->groupBy('user_id')->groupBy('category_id')
                                               ->get(['user_id','category_id',DB::raw('Max(date) as max_date')])->toArray();
                if(!empty($MinDate)){
                    $ModelFirst = clone $Model;
                    $FirstData  = $ModelFirst->where(function($query) use($MinDate){
                        foreach($MinDate as $val){
                            $query = $query->orWhere(function($q) use($val){
                                $q->where('user_id', $val['user_id'])
                                    ->where('category_id', $val['category_id'])
                                    ->where('date', $val['max_date']);
                            });
                        }
                    })->get(['id','date','user_id','percent','category_id','succeed','revenue_firstmonth','revenue_nextmonth','succeed_target'])->toArray();
                    if(empty($FirstData)){
                        break;
                    }

                    foreach($FirstData as $val){
                        if(isset($Data[$val['user_id']] )){
                            $Data[$val['user_id']]          -= ($val['succeed']);
                            $Percent[$val['user_id']]       -= $val['percent'];
                            $Firstmonth[$val['user_id']]    -= $val['revenue_firstmonth'];
                            $Nextmonth[$val['user_id']]     -= $val['revenue_nextmonth'];
                        }
                    }
                    unset($ModelFirst);
                    unset($FirstData);
                }
                unset($ModelMinDate);
                unset($MinDate);

                // Ngày ngắt quoãng => ngày chốt kpi
                $TimeNextMonth = strtotime('+1 months', strtotime($TimeStart));
                if($TimeNextMonth >= strtotime($TimeEnd)){// Có 1 khoảng
                    $RecordFirst = $this->__ngay_chot_doanh_thu(strtotime($TimeStart), strtotime($TimeEnd));
                    if(!empty($RecordFirst)){
                        $ModelMaxDate   = clone $Model;
                        $MaxDate        = $ModelMaxDate->where('date', '<', $RecordFirst)
                            ->groupBy('user_id')->groupBy('category_id')
                            ->get(['user_id','category_id',DB::raw('MAX(date) as max_date')])->toArray();
                        if(empty($MaxDate)){
                            break;
                        }

                        $ModelLast  = clone $Model;
                        $LastData  = $ModelLast->where(function($query) use($MaxDate){
                            foreach($MaxDate as $val){
                                $query = $query->orWhere(function($q) use($val){
                                    $q->where('user_id', $val['user_id'])
                                        ->where('category_id', $val['category_id'])
                                        ->where('date', $val['max_date']);
                                });
                            }
                        })->get(['user_id','category_id','succeed','percent','revenue_firstmonth','revenue_nextmonth','date','succeed_target'])->toArray();
                        if(empty($LastData)){
                            break;
                        }

                        foreach($LastData as $val){
                            if(isset($Data[$val['user_id']]) && (strtotime($val['date']) < $CheckData[$val['user_id']]['end_date'])){
                                $Data[$val['user_id']]          += $val['succeed'];
                                $Percent[$val['user_id']]       += $val['percent'];
                                $Firstmonth[$val['user_id']]    += $val['revenue_firstmonth'];
                                $Nextmonth[$val['user_id']]     += $val['revenue_nextmonth'];
                            }
                        }
                        unset($ModelMaxDate);
                        unset($MaxDate);
                        unset($ModelLast);
                        unset($LastData);
                    }
                }else{// Có thể có 2 khoảng
                    $RecordFirst = $this->__ngay_chot_doanh_thu(strtotime($TimeStart), strtotime($TimeNextMonth));
                    if(!empty($RecordFirst)){
                        $ModelMaxDate   = clone $Model;
                        $MaxDate        = $ModelMaxDate->where('date', '<', $RecordFirst)
                                                       ->groupBy('user_id')->groupBy('category_id')
                                                       ->get(['user_id','category_id',DB::raw('MAX(date) as max_date')])->toArray();
                        if(!empty($MaxDate)){
                            $ModelLast  = clone $Model;
                            $LastData  = $ModelLast->where(function($query) use($MaxDate){
                                foreach($MaxDate as $val){
                                    $query = $query->orWhere(function($q) use($val){
                                        $q->where('user_id', $val['user_id'])
                                            ->where('category_id', $val['category_id'])
                                            ->where('date', $val['max_date']);
                                    });
                                }
                            })->get(['user_id','category_id','succeed','percent','revenue_firstmonth','revenue_nextmonth','date','succeed_target'])->toArray();

                            foreach($LastData as $val){
                                if(isset($Data[$val['user_id']]) && (strtotime($val['date']) < $CheckData[$val['user_id']]['end_date'])){
                                    $Data[$val['user_id']]          += $val['succeed'];
                                    $Percent[$val['user_id']]       += $val['percent'];
                                    $Firstmonth[$val['user_id']]    += $val['revenue_firstmonth'];
                                    $Nextmonth[$val['user_id']]     += $val['revenue_nextmonth'];
                                }
                            }
                            unset($ModelLast);
                            unset($LastData);
                        }
                        unset($ModelMaxDate);
                        unset($MaxDate);
                    }

                    $RecordSecond = $this->__ngay_chot_doanh_thu(strtotime($TimeNextMonth), strtotime($TimeEnd));
                    if(!empty($RecordFirst)){
                        $ModelMaxDate   = clone $Model;
                        $MaxDate        = $ModelMaxDate->where('date', '<', $RecordSecond)
                            ->groupBy('user_id')->groupBy('category_id')
                            ->get(['user_id','category_id',DB::raw('MAX(date) as max_date')])->toArray();

                        if(!empty($MaxDate)){
                            $ModelLast  = clone $Model;
                            $LastData  = $ModelLast->where(function($query) use($MaxDate){
                                foreach($MaxDate as $val){
                                    $query = $query->orWhere(function($q) use($val){
                                        $q->where('user_id', $val['user_id'])
                                            ->where('category_id', $val['category_id'])
                                            ->where('date', $val['max_date']);
                                    });
                                }
                            })->get(['user_id','category_id','succeed','percent','revenue_firstmonth','revenue_nextmonth','date','succeed_target'])->toArray();
                            foreach($LastData as $val){
                                if(isset($Data[$val['user_id']]) && (strtotime($val['date']) < $CheckData[$val['user_id']]['end_date'])){
                                    $Data[$val['user_id']]          += $val['succeed'];
                                    $Percent[$val['user_id']]       += $val['percent'];
                                    $Firstmonth[$val['user_id']]    += $val['revenue_firstmonth'];
                                    $Nextmonth[$val['user_id']]     += $val['revenue_nextmonth'];
                                }
                            }
                            unset($ModelLast);
                            unset($LastData);
                        }
                        unset($ModelMaxDate);
                        unset($MaxDate);
                    }
                }

                $Target['percent_target']       =  round(($Target['percent_target']/$Target['total']),3);
                break;

            case 8: // 90 ngày trước
                $TimeStart      = date('Y-m-d', strtotime("-91 days"));
                if($IsCompare){
                    $TimeEnd    = date('Y-m-d', strtotime("-1 days", strtotime($TimeStart)));
                    $TimeStart  = date('Y-m-d', strtotime("-91 days", strtotime($TimeStart)));
                }

                $Time           = explode('-', $TimeStart);

                //Ngày chốt đầu tiên
                $TimeNext       = $this->__ngay_chot_doanh_thu(strtotime($TimeStart), strtotime($TimeEnd));

                /*
                 * Số liệu ngày cuối
                 */
                //Get Max Date
                $ModelMaxDate   = clone $Model;
                $MaxDate        = $ModelMaxDate->where('date', '>=', $TimeStart)
                                               ->where('date', '<=', $TimeEnd)
                                               ->where('succeed','>',0)
                                               ->groupBy('user_id')->groupBy('category_id')
                                               ->get(['user_id','category_id',DB::raw('MAX(date) as max_date')])->toArray();
                if(empty($MaxDate)){
                    break;
                }

                $ModelLast  = clone $Model;
                $LastData  = $ModelLast->where(function($query) use($MaxDate){
                    foreach($MaxDate as $val){
                        $query = $query->orWhere(function($q) use($val){
                            $q->where('user_id', $val['user_id'])
                                ->where('category_id', $val['category_id'])
                                ->where('date', $val['max_date']);
                        });
                    }
                })->get(['user_id','category_id','succeed','percent','revenue_firstmonth','revenue_nextmonth','date','succeed_target','percent_target'])
                  ->toArray();

                if(empty($LastData)){
                    break;
                }

                foreach($LastData as $val){
                    $Data[$val['user_id']]          = $val['succeed'];
                    $Percent[$val['user_id']]       = $val['percent'];
                    $Firstmonth[$val['user_id']]    = $val['revenue_firstmonth'];
                    $Nextmonth[$val['user_id']]     = $val['revenue_nextmonth'];
                    $CheckData[$val['user_id']]     = strtotime($val['date']);

                    $Target['succeed_target']       +=  $val['succeed_target'];
                    $Target['percent_target']       +=  $val['percent_target'];
                    $Target['total']                +=  1;
                }
                unset($ModelMaxDate);
                unset($MaxDate);
                unset($ModelLast);
                unset($LastData);

                /*
                 * Số liệu ngày đầu
                 */
                //Get Min Date
                $ModelMinDate   = clone $Model;
                $MinDate        = $ModelMinDate->where('date', '<', $TimeStart)
                                        ->groupBy('user_id')->groupBy('category_id')
                                        ->get(['user_id','category_id',DB::raw('Max(date) as max_date')])->toArray();
                if(!empty($MinDate)){
                    $ModelFirst = clone $Model;
                    $FirstData  = $ModelFirst->where(function($query) use($MinDate){
                        foreach($MinDate as $val){
                            $query = $query->orWhere(function($q) use($val){
                                $q->where('user_id', $val['user_id'])
                                    ->where('category_id', $val['category_id'])
                                    ->where('date', $val['max_date']);
                            });
                        }
                    })->get(['id','date','user_id','category_id','succeed','percent','revenue_firstmonth','revenue_nextmonth','succeed_target','percent_target'])->toArray();
                    foreach($FirstData as $val){
                        if(isset($Data[$val['user_id']])){
                            $Data[$val['user_id']]          -= ($val['succeed']);
                            $Percent[$val['user_id']]       -= $val['percent'];
                            $Firstmonth[$val['user_id']]    -= $val['revenue_firstmonth'];
                            $Nextmonth[$val['user_id']]     -= $val['revenue_nextmonth'];

                            $Target['succeed_target']       +=  $val['succeed_target']*((strtotime($TimeNext) - strtotime($TimeStart))/(86400*30));
                            $Target['percent_target']       +=  $val['percent_target'];
                            $Target['total']                +=  1;
                        }
                    }
                    unset($ModelFirst);
                    unset($FirstData);
                }
                unset($ModelMinDate);
                unset($MinDate);



                //Lấy số liệu cuối cùng của tháng đầu tiên
                if($Time[2] >= 25){
                    $DateFirst  = date('Y-n-'.$this->record_date, strtotime('+1 month', strtotime($TimeStart)));
                }else{
                    $DateFirst  = date('Y-n-'.$this->record_date, strtotime($TimeStart));
                }

                // Lấy số liệu các tháng nằm giữa
                $ListMonth  = $this->__get_list_month();

                $Open       = 0;
                $ListDate   = [];
                foreach($ListMonth as $val){
                    if(strtotime($val) > strtotime($TimeEnd)){
                        break;
                    }

                    if($Open == 1){
                        $val = explode('-', $val);
                        $ListDate[] = [
                            'month' => $val[1],
                            'year'  => $val[0]
                        ];
                    }

                    if($val == $DateFirst){
                        $Open = 1;
                    }
                }

                if(!empty($ListDate)){
                    $Record = \reportmodel\KPIEmployeeDetailModel::whereIn('category_id', $ListKpi)
                                                                ->where(function($query) use($ListDate){
                                                                    foreach($ListDate as $val){
                                                                        $query->orWhere(function($q)use($val){
                                                                            $q->where('year', $val['year'])
                                                                              ->where('month', $val['month']);
                                                                        });
                                                                    }
                                                                })
                                                                ->whereIn('user_id', $ListEmployee)
                                                                ->get(['month','year','user_id','category_id','succeed','percent','revenue_firstmonth','revenue_nextmonth','succeed_target','percent_target'])
                                                                ->toArray();

                    foreach($Record as $val){
                        $Time = strtotime("-1 day", strtotime(date($val['year'].'-'.$val['month'].'-'.$this->record_date)));
                        if(isset($Data[$val['user_id']])){
                            if(!isset($CheckData[$val['user_id']]) || ($CheckData[$val['user_id']] > $Time)){
                                $Data[$val['user_id']]          += $val['succeed'];
                                $Percent[$val['user_id']]       += $val['percent'];
                                $Firstmonth[$val['user_id']]    += $val['revenue_firstmonth'];
                                $Nextmonth[$val['user_id']]     += $val['revenue_nextmonth'];
                            }
                        }else{
                            $Data[$val['user_id']]          = $val['succeed'];
                            $Percent[$val['user_id']]       = $val['percent'];
                            $Firstmonth[$val['user_id']]    = $val['revenue_firstmonth'];
                            $Nextmonth[$val['user_id']]     = $val['revenue_nextmonth'];
                        }

                        $Target['succeed_target']       +=  $val['succeed_target'];
                        $Target['percent_target']       +=  $val['percent_target'];
                        $Target['total']                +=  1;
                    }
                }

                $Target['percent_target']       =  round(($Target['percent_target']/$Target['total']),3);
                break;
            default:
                break;
        }

        return Response::json([
            'error'         => false,
            'message'       => 'Success',
            'data'          => $Data,
            'firstmonth'    => $Firstmonth,
            'nextmonth'     => $Nextmonth,
            'percent'       => $Percent,
            'target'        => $Target
        ]);
    }

    public function __report_sale_fulfill($Interval, $ListEmployee){
        $Time           = $this->__get_time($Interval);
        $TimeStart      = $Time[0];

        $Model          = new \omsmodel\SellerModel;
        $Data           = [];

        // Doanh thu đầu tháng
        $Fulfill        = $Model::where('first_shipment_time', '>=', $TimeStart)
            ->whereIn('seller_id', $ListEmployee)
            ->where('active',1)
            ->groupBy('seller_id')
            ->get(['seller_id',DB::raw('count(*) as total')])->toArray();

        if(!empty($Fulfill)){
            foreach($Fulfill as $val){
                $Data[(int)$val['seller_id']]  = $val['total'];
            }
        }

        return $Data;

    }

    private function __get_list_period($id){
        $Data = [];
        switch ($id) {
            case 1://month
                for($i = 1; $i <= 10; $i++){
                    $j = $i;
                    if(date('j', strtotime('-3 days')) >= $this->record_date){
                        $j = $i + 1;
                    }
                    $Data[] = date('Y-m', strtotime(($j-10).' month' ,strtotime(date('Y-m-d', strtotime('-3 days')))));
                }
                break;
            case 2://week
                for($i = 1; $i <= 10; $i++){
                    $Data[] = date('W-Y', strtotime('-'.(10-$i).' week' ,strtotime(date('Y-m-d', strtotime('-3 days')))));
                }
                break;
            case 3://day
                for($i = 1; $i <= 10; $i++){
                    $Data[] = date('Y-m-d', strtotime('-'. (13 -$i) .' days'));
                }
                break;
            default:
                break;
        }

        return $Data;
    }

    public function __get_list_revenue_by_period($id, $list_period, $list_user){
        $Data = [];
        $ListKPI    = \reportmodel\KPICategoryModel::whereIn('code', ['revenue'])->lists('id');
        if(empty($ListKPI)){
            return $Data;
        }

        switch ($id) {
            case 1://month
                $Revenue    = \reportmodel\KPIEmployeeDetailModel::whereIn('category_id', $ListKPI)
                                                                 ->whereIn('user_id', $list_user)
                                                                 ->where(function($query) use($list_period){
                                                                     foreach($list_period as $val){
                                                                         $val = explode('-', $val);
                                                                         $query = $query->orWhere(function($q) use($val){
                                                                             $q->where('month', $val[1])->where('year', $val[0]);
                                                                         });
                                                                     }
                                                                 })->get()->toArray();

                $Check = false;
                foreach($Revenue as $val){
                    if(!isset($Data[$val['user_id']])){
                        $Data[$val['user_id']] = [];
                    }
                    foreach($list_period as $k => $v){
                        $Time = explode('-', $v);
                        if($val['year'] == $Time[0] && $val['month'] == $Time[1]){
                            $Data[$val['user_id']][$v] = $val['succeed'];
                            if($k == 9) $Check = true;
                        }else{
                            if(!isset($Data[$val['user_id']][$v])){
                                $Data[$val['user_id']][$v]  = 0;
                            }
                        }
                    }
                }

                if(!$Check){ // Nếu chưa có kpi tháng hiện tại => tự tính
                    $Model          = \reportmodel\KPIModel::whereIn('category_id', $ListKPI)->whereIn('user_id', $list_user);
                    //Get Max Date
                    $ModelMaxDate   = clone $Model;
                    $MaxDate        = $ModelMaxDate->groupBy('category_id')
                        ->get(['category_id',DB::raw('MAX(date) as max_date')])->toArray();
                    if(!empty($MaxDate)){
                        $Model      = $Model->where(function($query) use($MaxDate){
                            foreach($MaxDate as $val){
                                $query = $query->orWhere(function($q) use($val){
                                    $q->where('category_id', $val['category_id'])->where('date', $val['max_date']);
                                });
                            }
                        });

                        $LastData   = $Model->get(['user_id','category_id','succeed','date','succeed_target'])->toArray();

                        //Tổng số
                        foreach($LastData as $val){
                            if(!isset($Data[$val['user_id']])){
                                $Data[$val['user_id']] = [];
                            }
                            $Data[$val['user_id']][$list_period[9]] = $val['succeed'];
                        }
                    }

                }

                break;
            case 2://week
                $ListPeriod     = [];
                $list_period    = array_reverse($list_period);
                $DateStart      = $DateEnd = '';
                $ListDate       = [];
                foreach($list_period as $key => $val){
                    if($key == 0){
                        $DateEnd   = date('Y-m-d', strtotime('-3 days'));
                        $DateStart = date('Y-m-d', strtotime('last Sunday', strtotime($DateEnd)));
                    }else{
                        $DateEnd    = $DateStart;
                        $DateStart  = date('Y-m-d', strtotime('-7 days', strtotime($DateStart)));
                    }

                    $ListPeriod[$val] = [
                        'week'          => $val,
                        'date_end'      => $DateEnd,
                        'date_start'    => $DateStart,
                        'record_date'   => false
                    ];

                    $ListDate[] = $DateStart;
                    $ListDate[] = $DateEnd;

                    $TimeStart  = explode('-', $DateStart);
                    $TimeEnd    = explode('-', $DateEnd);
                    if($TimeStart[2] < 25 && ($TimeEnd[2] >= 25 || ($TimeStart[1] != $TimeEnd[1]))){
                        $ListPeriod[$val]['record_date']    = $TimeStart[0].'-'.$TimeStart[1].'-24';
                        $ListDate[]                         = $ListPeriod[$val]['record_date'];
                    }
                }

                $Revenue        = \reportmodel\KPIModel::whereIn('category_id', $ListKPI)->whereIn('user_id', $list_user)
                                                        ->whereIn('date', $ListDate)
                                                        ->orderBy('date', 'ASC')
                                                        ->get(['user_id','category_id','succeed','date'])->toArray();

                $ListPeriod = array_reverse($ListPeriod);
                foreach($Revenue as $val){
                    if(!isset($Data[$val['user_id']])){
                        $Data[$val['user_id']] = [];
                    }

                    foreach($ListPeriod as $k => $v){
                        if(!isset($Data[$val['user_id']][$k])){
                            $Data[$val['user_id']][$k] = 0;
                        }
                        if($v['date_start'] ==  $val['date']){
                            $Data[$val['user_id']][$k]  -= $val['succeed'];
                        }
                        if($v['date_end'] ==  $val['date']){
                            $Data[$val['user_id']][$k]  += $val['succeed'];
                        }

                        if(!empty($v['record_date']) && $v['record_date'] == $val['date']){
                            $Data[$val['user_id']][$k]  += $val['succeed'];
                        }
                    }
                }
                break;
            case 3://day
                array_unshift($list_period, date('Y-m-d', strtotime('-1 day', strtotime($list_period[0]))));
                $Revenue        = \reportmodel\KPIModel::whereIn('category_id', $ListKPI)->whereIn('user_id', $list_user)
                                                       ->whereIn('date', $list_period)
                                                        ->orderBy('date', 'ASC')
                                                        ->get(['user_id','category_id','succeed','date'])->toArray();

                $RevenueLastDay = [];
                foreach($Revenue as $val){
                    if(!isset($Data[$val['user_id']])){
                        $Data[$val['user_id']] = [];
                    }
                    if(!isset($RevenueLastDay[$val['user_id']])){
                        $RevenueLastDay[$val['user_id']] = 0;
                    }

                    foreach($list_period as $k => $v){
                        $Time = explode('-', $v);
                        if($v == $val['date']){
                            if($Time[2] == $this->record_date){ // ngày đầu tháng
                                $Data[$val['user_id']][$v]  = $val['succeed'];
                            }else{
                                $Data[$val['user_id']][$v]  = $val['succeed'] - $RevenueLastDay[$val['user_id']];
                            }

                        }else{
                            if(!isset($Data[$val['user_id']][$v])){
                                $Data[$val['user_id']][$v]  = 0;
                            }
                        }
                    }
                    $RevenueLastDay[$val['user_id']] = $val['succeed'];
                }
                break;
            default:
                break;
        }

        return $Data;
    }

    public function __get_statistic_order_accept($ListUser, $Period){
        $TimeEnd        = date('Y-m-d');
        $ListStatistic  = [];
        foreach($ListUser as $val){
            $ListStatistic[(int)$val]    = [0,0,0,0,0,0,0];
        }

        $OrdersModel   = new \ordermodel\OrdersModel;
        $OrdersModel   = $OrdersModel::where('time_accept', '>=', $this->time() - (($Period*7 + 30)*86400))
                                     ->whereIn('from_user_id', $ListUser);

        $ListPeriod = [];
        switch ($Period) {
            case 1://Today
                // To day
                $FirstModel = clone $OrdersModel;
                $Order      = $FirstModel->where('time_pickup', '>=', strtotime($TimeEnd))
                    ->groupBy('from_user_id')
                    ->get(['from_user_id', DB::raw('count(*) as total')])->toArray();
                foreach($Order as $val){
                    $ListStatistic[$val['from_user_id']][6]    = $val['total'];
                }
                unset($FirstModel);
                unset($Order);

                // Last day
                $FirstModel = clone $OrdersModel;
                $Order      = $FirstModel->where('time_pickup', '>=', (strtotime($TimeEnd) - 86400))
                    ->where('time_pickup', '<', strtotime($TimeEnd))
                    ->groupBy('from_user_id')
                    ->get(['from_user_id', DB::raw('count(*) as total')])->toArray();
                foreach($Order as $val){
                    $ListStatistic[$val['from_user_id']][5]    = $val['total'];
                }
                unset($FirstModel);
                unset($Order);

                //Các ngày còn lại có thể lấy ở report merchant accounting
                $TimeStart              = date('Y-m-d', strtotime(' -6 day'));
                $TimeStart              = explode('-', $TimeStart);

                for($i = 1; $i <= 6; $i++){
                    $ListPeriod[date('Y-n-j', strtotime(' -'. (7 - $i) .' day'))]    = ($i - 1);
                }

                break;

            case 3:
            case 7:
            case 30:
                $FirstModel = clone $OrdersModel;
                $Order      = $FirstModel->where('time_pickup', '>=', (strtotime($TimeEnd) - 86400*$Period))
                    ->where('time_pickup', '<', strtotime($TimeEnd))
                    ->groupBy('from_user_id')
                    ->get(['from_user_id', DB::raw('count(*) as total')])->toArray();
                foreach($Order as $val){
                    $ListStatistic[$val['from_user_id']][6]    = $val['total'];
                }
                unset($FirstModel);
                unset($Order);

                for($i = 0; $i <= 5; $i++){
                    for($j = 0; $j <= ($Period - 1); $j++){
                        $ListPeriod[date('Y-n-j', strtotime(' -'. (($Period*7) - ($j+$i*$Period)) .' day'))]    = $i;
                    }
                }

                $TimeStart              = date('Y-n-j', strtotime(' -'. $Period*7 .' day'));
                $TimeStart              = explode('-', $TimeStart);
                break;

            default:
                return [];
                break;
        }

        $ReportMerchantModel    = new \accountingmodel\ReportMerchantModel;
        $ReportMerchant = $ReportMerchantModel::whereIn('user_id', $ListUser)
            ->where(function($query) use($TimeStart){
                $query->where(function($q) use($TimeStart){
                    $q->where('date','>=', $TimeStart[2])
                        ->where('month',$TimeStart[1])
                        ->where('year',$TimeStart[0]);
                })->orWhere(function($q) use($TimeStart){
                    $q->where('month','>',$TimeStart[1])
                        ->where('year', $TimeStart[0]);
                })->orWhere(function($q) use($TimeStart){
                    $q->where('year','>', $TimeStart[0]);
                });
            })->orderBy('year','ASC')
            ->orderBy('month','ASC')
            ->orderBy('date','ASC')
            ->get(['date','month','year','user_id','generate','sc_pvc','sc_cod','sc_pbh','sc_discount_pvc','sc_discount_cod'])->toArray();

        foreach($ReportMerchant as $val){
            if(isset($ListPeriod[$val['year'].'-'.$val['month'].'-'.$val['date']])){
                $ListStatistic[$val['user_id']][$ListPeriod[$val['year'].'-'.$val['month'].'-'.$val['date']]]    += $val['generate'];
            }
        }

        return $ListStatistic;
    }

    private function __response_sale_employee(){
        return Response::json([
            'error'             => false,
            'message'           => 'Success',
            'data'              => $this->data
        ]);
    }

    public function getSaleEmployee(){
        $CountryId          = Input::has('country_id')          ? (int)Input::get('country_id')             : 0;
        $Team               = Input::has('team')                ? trim(Input::get('team'))                  : 0;
        $Email              = Input::has('email')               ? strtolower(trim(Input::get('email')))     : 0;
        $Active             = Input::has('active')              ? (int)Input::get('active')                 : null;

        //1-  this week    2 - this month    3 - this year      7 - last 30 days  8 - last 90 days
        $Interval           = Input::has('interval')            ? (int)Input::get('interval')               : 0;
        $Group              = 3;

        $GroupConfig    = \reportmodel\KPIGroupConfigModel::where('group', $Group)->where('active',1)->lists('id');
        if(empty($GroupConfig)){
            return $this->__response_sale_employee();
        }

        $Model      = new \reportmodel\CrmEmployeeModel;
        $Model      = $Model::whereIn('group_config', $GroupConfig);

        if(!empty($CountryId)){
            $Model  = $Model->where('country_id', $CountryId);
        }

        if(!empty($Team)){
            $Team   = explode(',',$Team);
            $Model  = $Model->whereIn('group_config', $Team);
        }

        if(!empty($Email)){
            $Email      = explode(',',$Email);
            $Model      = $Model->whereIn('email', $Email);
        }

        if(isset($Active)){
            $Model  = $Model->where('active', $Active);
        }

        $this->data = $Model->with('__user')->get()->toArray();
        return $this->__response_sale_employee();
    }

    public function getSaleRevenueEmployee(){
        $CountryId          = Input::has('country_id')          ? (int)Input::get('country_id')             : 0;
        $Team               = Input::has('team')                ? trim(Input::get('team'))                  : 0;
        $Email              = Input::has('email')               ? strtolower(trim(Input::get('email')))     : 0;
        $Active             = Input::has('active')              ? (int)Input::get('active')                 : null;

        //        1-  month    2 - week    3 - day
        $Period             = Input::has('period')              ? (int)Input::get('period')                 : 1;
        $Group              = 3;

        $GroupConfig        = \reportmodel\KPIGroupConfigModel::where('group', $Group)->where('active',1)->lists('id');
        if(empty($GroupConfig)){
            return Response::json(['error' => false, 'data' => [], 'list_period' => [], 'revenue_employee' => []]);
        }

        $Model              = new \reportmodel\CrmEmployeeModel;
        $Model              = $Model::whereIn('group_config', $GroupConfig);
        $RevenueEmployee    = [];
        $ListPeriod         = $this->__get_list_period($Period);

        if(!empty($CountryId)){
            $Model  = $Model->where('country_id', $CountryId);
        }

        if(!empty($Team)){
            $Team   = explode(',',$Team);
            $Model  = $Model->whereIn('group_config', $Team);
        }

        if(!empty($Email)){
            $Email      = explode(',',$Email);
            $Model      = $Model->whereIn('email', $Email);
        }

        if(isset($Active)){
            $Model  = $Model->where('active', $Active);
        }

        $Data       = $Model->with('__user')->get()->toArray();
        if(!empty($Data)){
            $ListUser   = [];
            foreach($Data as $val){
                $ListUser[] = $val['user_id'];
            }
            $RevenueEmployee =  $this->__get_list_revenue_by_period($Period, $ListPeriod, $ListUser);
        }

        return Response::json(['error' => false, 'data' => $Data, 'list_period' => $ListPeriod, 'revenue_employee' => $RevenueEmployee]);
    }

    public function getSaleCustomer(){
        $itemPage           = 20;
        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $Email              = Input::has('email')               ? strtolower(trim(Input::get('email')))     : '';
        $Status             = Input::has('status')              ? (int)Input::get('status')                 : 0;
        $Period             = Input::has('period')              ? (int)Input::get('period')                 : 1;

        $Data           = [];
        $ReturnUser     = [];
        $ListStatistic  = [];
        $ListRevenue    = [];
        $TimeEnd        = date('Y-m-d');

        $Total          = 0;
        $offset         = ($page - 1)*$itemPage;

        if(empty($Email)){
            return Response::json(['error' => false, 'data' => $Data, 'total' => $Total, 'return_user'  => $ReturnUser, 'list_statistic' => $ListStatistic, 'list_revenue' => $ListRevenue]);
        }

        //Get employee
        $Employee = \reportmodel\CrmEmployeeModel::where('email', $Email)->first();
        if(!isset($Employee->id)){
            return Response::json(['error' => false, 'data' => $Data, 'total' => $Total, 'return_user'  => $ReturnUser, 'list_statistic' => $ListStatistic, 'list_revenue' => $ListRevenue]);
        }

        $SellerModel        = new \omsmodel\SellerModel;
        $SellerModel        = $SellerModel::where('seller_id', $Employee->user_id);
        $LogCustomer        = new \omsmodel\LogSellerModel;
        $ReturnUser         = $LogCustomer::where('seller_id', $Employee->user_id)->remember(60)->lists('user_id');

        //Get List Customer New
        switch ($Status) {
            case 1://Khách mới
                if(!empty($ReturnUser)){
                    $SellerModel    = $SellerModel->whereNotIn('user_id', $ReturnUser);
                }
                break;

            case 2://Inactive
                $SellerModel    = $SellerModel->where('active', 0);
                break;

            case 3://Return
                if(!empty($ReturnUser)){
                    $SellerModel    = $SellerModel->whereIn('user_id', $ReturnUser);
                }else{
                    return Response::json(['error' => false, 'data' => $Data, 'total' => $Total, 'return_user'  => $ReturnUser, 'list_statistic' => $ListStatistic, 'list_revenue' => $ListRevenue]);
                }
                break;

            default:
            break;
        }

        $TotalModel     = clone $SellerModel;
        $Total          = $TotalModel->count();
        if($Total > 0){
            $Data   = $SellerModel->with('__get_user')->orderBy('first_time_pickup', 'DESC')->skip($offset)->take($itemPage)->get(['id','user_id', 'first_time_pickup','release','active','total_firstmonth','total_nextmonth']);
            $ListUser   = [];
            foreach($Data as $val){
                $ListUser[]                             = (int)$val['user_id'];
            }
            $ListStatistic = $this->__get_statistic_order_accept($ListUser, $Period);
        }

        return Response::json(['error' => false, 'data' => $Data, 'total' => $Total, 'return_user'  => $ReturnUser, 'list_statistic' => $ListStatistic]);
    }

    public function getSaleOverView(){
        $CountryId          = Input::has('country_id')          ? (int)Input::get('country_id')             : 0;
        $Team               = Input::has('team')                ? trim(Input::get('team'))                  : 0;
        $Email              = Input::has('email')               ? strtolower(trim(Input::get('email')))     : 0;
        $Active             = Input::has('active')              ? (int)Input::get('active')                 : null;
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : 3;

        $GroupConfig    = \reportmodel\KPIGroupConfigModel::where('group', $Group)->where('active',1)->lists('id');
        if(empty($GroupConfig)){
            return Response::json(['error' => false, 'data' => []]);
        }

        $Model      = new \reportmodel\CrmEmployeeModel;
        $Model      = $Model::whereIn('group_config', $GroupConfig);

        if(!empty($CountryId)){
            $Model  = $Model->where('country_id', $CountryId);
        }

        if(!empty($Team)){
            $Team   = explode(',',$Team);
            $Model  = $Model->whereIn('group_config', $Team);
        }

        if(!empty($Email)){
            $Email      = explode(',',$Email);
            $Model      = $Model->whereIn('email', $Email);
        }

        if(isset($Active)){
            $Model  = $Model->where('active', $Active);
        }

        return Response::json(['error' => false, 'data' => $Model->with('__user')->get(['user_id','level'])->toArray()]);
    }

    private function __response_follow_up_customer(){
        return Response::json([
            'error'         => false,
            'message'       => 'Success',
            'total'         => $this->total,
            'data'          => $this->data,
            'statistic'     => $this->list_statistic
        ]);
    }

    private function __check_fail_reason($Result, $Reason, $Code){
        if($Result == 3){
            if(!empty($Reason)){
                if(in_array($Code, $Reason)){
                    return 1;
                }
            }
        }

        return 0;
    }

    public function postUpdateCustomer(){
        $UserInfo = $this->UserInfo();

        $validation = \Validator::make(Input::all(), array(
            'result'     => 'required|in:0,1,2,3',
            'activity'   => 'required|in:1,2,3,4,5,6',
            'user_id'   => 'required|numeric|min:2'
        ));
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'code' => 'INVALID', 'message_error' => $validation->messages()));
        }

        $Result     = Input::has('result')      ? (int)Input::get('result')     : 0;
        $Activity   = Input::has('activity')    ? (int)Input::get('activity')   : '';
        $Reason     = Input::has('reason')      ? trim(Input::get('reason'))    : '';
        $Note       = Input::has('note')        ? trim(Input::get('note'))      : '';
        $UserId     = Input::has('user_id')     ? (int)Input::get('user_id')    : 0;

        if(!empty($Reason)){
            $Reason     = explode(',',$Reason);
        }

        try{
            \omsmodel\ActivityModel::insert([
                'activity'              => $Activity,
                'seller_id'             => $UserInfo['id'],
                'user_id'               => $UserId,
                'result'                => $Result,
                'fail_reason_price'     => $this->__check_fail_reason($Result, $Reason, 1),
                'fail_reason_cs'        => $this->__check_fail_reason($Result, $Reason, 2),
                'fail_reason_pickup'    => $this->__check_fail_reason($Result, $Reason, 3),
                'fail_reason_delivery'  => $this->__check_fail_reason($Result, $Reason, 4),
                'fail_reason_sale'      => $this->__check_fail_reason($Result, $Reason, 5),
                'note'                  => $Note,
                'time_create'           => $this->time()
            ]);

            \omsmodel\SellerModel::where('user_id', $UserId)->update(['activity_status' => $Result]);
        }catch (\Exception $e){
            return Response::json(array('error' => true, 'code' => 'ERROR', 'message_error' => $e->getMessage()));
        }

        return Response::json(array('error' => false, 'code' => 'SUCCESS', 'message_error' => 'Thành Công'));
    }

    public function postUpdateKpi(){
        $UserId = $this->UserInfo();

        $validation = \Validator::make(Input::all(), array(
            'code'      => 'required|in:satisfaction',
            'value'     => 'required|max:100',
            'user_id'   => 'required|numeric|min:2'
        ));
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'code' => 'INVALID', 'message_error' => $validation->messages()));
        }

        $Code   = Input::has('code')    ? trim(Input::get('code'))      : '';
        $Value  = Input::has('value')   ? (int)Input::get('value')      : 0;
        $UserId = Input::has('user_id') ? (int)Input::get('user_id')    : 0;
        $Date   = Input::has('date')    ? trim(Input::get('date'))      : '';
        $Month  = Input::has('month')   ? trim(Input::get('month'))     : '';

        if(empty($Date) && empty($Month)){
            return Response::json([
                'error'         => true,
                'message'       => 'TIME_ERROR',
                'message_error' => 'Cập nhật thất bại'
            ]);
        }

        if(!empty($Month)){
            $Time   = date('Y-m-d', strtotime('-1 days' , strtotime(date($Month.'-'. $this->record_date))));
        }else{
            $Time   = $Date;
        }

        $Category    = \reportmodel\KPICategoryModel::where('code', $Code)->where('active', 1)->first();
        if(!isset($Category->id)){
            return Response::json([
                'error'         => true,
                'message'       => 'CATEGORY_NOT_EXISTS',
                'message_error' => 'Mã Code không tồn tại'
            ]);
        }

        if(strtotime(date('Y-m-d')) > strtotime($Time)){// Nếu ngày sửa nhỏ hơn ngày hôm nay => ko cho sửa
            return Response::json([
                'error'         => true,
                'message'       => 'CANNOT_EDIT',
                'message_error' => 'Không thể cập nhật'
            ]);
        }

        $Model      = new \reportmodel\KPIModel;
        try{
            $Model                  = $Model::firstOrNew(['date' => $Time,'user_id' => $UserId,'category_id' => $Category->id]);
            $Model->percent         = ($Value/100);
            $Model->weight          = $Category->weight;
            $Model->succeed_target  = $Category->target;
            $Model->percent_target  = $Category->percent;
            $Model->time_create     = $this->time();
            $Model->save();
        }catch (\Exception $e){
            return Response::json([
                'error'         => true,
                'message'       => 'ERROR',
                'message_error' => 'Không thể cập nhật'
            ]);
        }
        return Response::json(array('error' => false, 'code' => 'SUCCESS', 'message_error' => 'Thành Công'));
    }

    public function getFollowUpCustomers()
    {
        $itemPage   = 20;
        $page       = Input::has('page')            ? (int)Input::get('page')                   : 1;
        $CountryId  = Input::has('country_id')      ? (int)Input::get('country_id')             : 0;
        $Team       = Input::has('team')            ? trim(Input::get('team'))                  : '';
        $Status     = Input::has('status')          ? trim(Input::get('status'))                : '';
        $Email      = Input::has('email')           ? strtolower(trim(Input::get('email')))     : '';
        $Customer   = Input::has('customer')        ? strtolower(trim(Input::get('customer')))  : 0;
        $NotApprove = Input::has('not_approve')     ? (int)Input::get('not_approve')            : 0;
        $NotSignIn  = Input::has('not_sign_in')     ? (int)Input::get('not_sign_in')            : 0;
        $Tab        = Input::has('tab')             ? (int)Input::get('tab')                    : null;

        $offset                 = ($page - 1)*$itemPage;
        $Model                  = new \omsmodel\SellerModel;

        $ListCustomer           = [];

        if(isset($Tab)){
            $Model = $Model->where('activity_status', $Tab);
        }

        if(!empty($Team) || !empty($Email)){
            $EmployeeModel  = new \reportmodel\CrmEmployeeModel;
            if(!empty($Email)){
                $Email          = explode(',',$Email);
                $EmployeeModel  = $EmployeeModel->whereIn('email', $Email);
            }

            if(!empty($Team)){
                $Team           = explode(',',$Team);
                $EmployeeModel  = $EmployeeModel->whereIn('group_config', $Team);
            }

            $ListEmployee   = $EmployeeModel->lists('user_id');
            if(empty($ListEmployee)){
                return $this->__response_follow_up_customer();
            }
            $Model          = $Model->whereIn('seller_id', $ListEmployee);
        }

        if(!empty($Customer) || !empty($NotSignIn)){
            $User   = new \User;
            $User   = $User->where('time_last_login', '>=', strtotime(date('Y-m-d', strtotime("-90 days"))));

            if(!empty($Customer)){
                $Customer   = explode(',',$Customer);
                $User       = $User::whereIn('email', $Customer);
            }

            if(!empty($NotSignIn)){
                switch ($NotSignIn){
                    case 1:// To day
                        $User = $User->where('time_last_login', '>=', strtotime(date('Y-m-d')));
                        break;

                    case 2:// Last 3 days
                            //  Thời gian đăng nhập gần nhất quá 3 ngày trước
                        $User = $User->where('time_last_login', '<=', strtotime(date('Y-m-d', strtotime("-3 days"))));
                        break;

                    case 3:// Last 7 days
                        $User = $User->where('time_last_login', '<=', strtotime(date('Y-m-d', strtotime("-7 days"))));
                        break;

                    case 4:// Last 30 days
                        $User = $User->where('time_last_login', '<=', strtotime(date('Y-m-d', strtotime("-30 days"))));
                        break;

                    default:
                        return $this->__response_follow_up_customer();
                        break;
                }
            }

            $User   = $User->lists('id');
            if(empty($User)){
                return $this->__response_follow_up_customer();
            }
            $Model      = $Model->whereIn('user_id', $User);
        }

        /*
            1 : 'Orders',
            2 : 'Picked',
            3 : 'Delivered',
            4 : 'Returned',
            5 : 'CF. Payment',
            6 : 'Paid'
         */
        if(!empty($Status)){
            $Status     = explode(',',$Status);
            if(!in_array(2, $Status)){
                $Model  = $Model->where('first_time_pickup', 0);
            }
        }else{
            $Status = [0];
            $Model  = $Model->where('first_time_pickup', 0);
        }

        $ModelSub   = clone $Model;
        $ListUser   = $ModelSub->lists('user_id');
        if(empty($ListUser)){
            return $this->__response_follow_up_customer();
        }
        unset($ModelSub);

        if(!empty($NotApprove) || (!in_array(1, $Status)) || (!in_array(3, $Status)) || (!in_array(4, $Status)) || (!in_array(6, $Status))){
            $CustomerAdminModel = new \omsmodel\CustomerAdminModel;
            $CustomerAdminModel = $CustomerAdminModel::whereIn('user_id', $ListUser);

            if(!empty($NotApprove)){
                switch ($NotApprove){
                    case 1:// To day
                        $CustomerAdminModel = $CustomerAdminModel->where('last_accept_order_time', '>=', strtotime(date('Y-m-d')));
                        break;

                    case 2:// Last 3 days
                        //  Thời gian đăng nhập gần nhất từ 3 ngày trước đến hôm qua
                        $CustomerAdminModel = $CustomerAdminModel->where('last_accept_order_time', '<=', strtotime(date('Y-m-d', strtotime("-3 days"))));
                        break;

                    case 3:// Last 7 days
                        $CustomerAdminModel = $CustomerAdminModel->where('last_accept_order_time', '<=', strtotime(date('Y-m-d', strtotime("-7 days"))));
                        break;

                    case 4:// Last 30 days
                        $CustomerAdminModel = $CustomerAdminModel->where('last_accept_order_time', '<=', strtotime(date('Y-m-d', strtotime("-30 days"))));
                        break;

                    default:
                        return $this->__response_follow_up_customer();
                        break;
                }
            }

            if(!in_array(1, $Status)){
                $CustomerAdminModel = $CustomerAdminModel->where('first_order_time', 0);
            }

            if(!in_array(3, $Status)){
                $CustomerAdminModel = $CustomerAdminModel->where('first_success_order_time', 0);
            }

            if(in_array(4, $Status)){
                $CustomerAdminModel = $CustomerAdminModel->where('first_return_order_time', 0);
            }

            if(in_array(6, $Status)){
                $CustomerAdminModel = $CustomerAdminModel->where('first_time_paid', 0);
            }

            $ListCustomer   = $CustomerAdminModel->lists('user_id');
            if(empty($ListCustomer)){
                return $this->__response_follow_up_customer();
            }
        }



        if(!in_array(5, $Status)){
            $UserInfoModel  = new \sellermodel\UserInfoModel;
            $UserInfo       = $UserInfoModel::whereIn('user_id', $ListUser);
            $UserInfo       = $UserInfo->where('user_nl_id', 0)->where('priority_payment',1)->lists('user_id');

            if(empty($UserInfo)){
                return $this->__response_follow_up_customer();
            }

            if(!empty($ListCustomer)){
                $ListCustomer = array_intersect($ListCustomer, $UserInfo);
                if(empty($ListCustomer)){
                    return $this->__response_follow_up_customer();
                }
            }else{
                $ListCustomer = $UserInfo;
            }
        }

        if(!empty($ListCustomer)){
            $Model                  = $Model->whereIn('user_id', $ListCustomer);
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();
        if($this->total > 0) {
            $this->data = $Model->with(['__get_user','__get_return','new_customer','__get_userinfo'])->orderBy('first_time_pickup', 'DESC')
                                ->skip($offset)->take($itemPage)
                                ->get(['id', 'user_id', 'first_time_pickup', 'active'])->toArray();

            $ListUser   = [];
            foreach($this->data as $val){
                $ListUser[]                             = (int)$val['user_id'];
            }
            $this->list_statistic = $this->__get_statistic_order_accept($ListUser, 7);
        }
        return $this->__response_follow_up_customer();
    }

    public function getStatisticKpiTeamByDate(){
        $Date           = Input::has('date')        ? trim(Input::get('date'))          : '';
        $Month          = Input::has('month')       ? trim(Input::get('month'))         : '';
        $ListEmployee   = Input::has('employee')    ? trim(Input::get('employee'))      : '';
        $Code           = Input::has('code')        ? trim(Input::get('code'))          : 0;

        $Data = $ListReport = [];

        if(empty($Date) && empty($Month)){
            return Response::json([
                'error'         => false,
                'message'       => 'success',
                'data'          => $Data
            ]);
        }

        $ListKpi    = \reportmodel\KPICategoryModel::where('code', $Code)
                                                   ->where('active', 1)
                                                   ->lists('id');
        if(empty($ListKpi) || empty($ListEmployee)){
            return Response::json([
                'error'         => false,
                'message'       => 'Success',
                'data'          => $Data
            ]);
        }

        $ListEmployee   = explode(',', $ListEmployee);
        //List Assign
        $Employee = \reportmodel\KPIConfigModel::whereIn('category_id', $ListKpi)->where('active',1)
                                                ->whereIn('user_id', $ListEmployee)
                                                ->get(['category_id', 'user_id'])->toArray();
        if(empty($Employee)){
            return Response::json([
                'error'         => false,
                'message'       => 'Success',
                'data'          => $Data
            ]);
        }
        $ReferEmployee = [];
        foreach($Employee as $val){
            $ReferEmployee[$val['category_id']][] = $val['user_id'];
        }

        $Model      = new \reportmodel\KPIModel;
        $Model      = $Model::whereIn('category_id', $ListKpi)->where('user_id', 0);

        if(!empty($Month)){
            $TimeEnd            = date($Month.'-'. $this->record_date);
            $TimeStart          = date('Y-m-'. $this->record_date, strtotime("-1 month", strtotime($TimeEnd)));

            $Model              = $Model->where('date', '>=', $TimeStart)->where('date', '<', $TimeEnd);

            $MaxModel   = clone $Model;
            $MaxData    = $MaxModel->groupBy('category_id')->get(['category_id',DB::raw('MAX(date) as max_date')])->toArray();
            if(!empty($MaxData)){
                $ListReport  = $Model->where(function($query) use($MaxData){
                    foreach($MaxData as $val){
                        $query = $query->orWhere(function($q) use($val){
                            $q->where('category_id', $val['category_id'])
                                ->where('date', $val['max_date']);
                        });
                    }
                })->get()->toArray();
            }

        }else{
            $ListReport = $Model->where('date', $Date)->get()->toArray();
        }

        if(!empty($ListReport)){
            foreach($ListReport as $val){
                if(isset($ReferEmployee[$val['category_id']])){
                    foreach($ReferEmployee[$val['category_id']] as $v){
                        $Data[$v]   = [
                            'percent'               => $val['percent'],
                            'succeed'               => $val['succeed'],
                            'total'                 => $val['total'],
                            'revenue_firstmonth'    => $val['revenue_firstmonth'],
                            'revenue_nextmonth'     => $val['revenue_nextmonth'],
                            'succeed_target'        => $val['succeed_target'],
                            'percent_target'        => $val['percent_target']
                        ];
                    }
                }
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'Success',
            'data'          => $Data
        ]);
    }

    public function getStatisticKpiByDate(){
        $Date           = Input::has('date')        ? trim(Input::get('date'))          : '';
        $Month          = Input::has('month')       ? trim(Input::get('month'))         : '';
        $ListEmployee   = Input::has('employee')    ? trim(Input::get('employee'))      : '';
        $Code           = Input::has('code')        ? trim(Input::get('code'))          : 0;

        $Data = $ListReport = [];

        if(empty($Date) && empty($Month)){
            return Response::json([
                'error'         => false,
                'message'       => 'success',
                'data'          => $Data
            ]);
        }

        $ListKpi    = \reportmodel\KPICategoryModel::where('code', $Code)
            ->where('active', 1)
            ->lists('id');
        if(empty($ListKpi) || empty($ListEmployee)){
            return Response::json([
                'error'         => false,
                'message'       => 'Success',
                'data'          => $Data
            ]);
        }

        $ListEmployee   = explode(',', $ListEmployee);

        $Model      = new \reportmodel\KPIModel;
        $Model      = $Model::whereIn('category_id', $ListKpi)->whereIn('user_id', $ListEmployee);

        if(!empty($Month)){
            $TimeEnd            = date($Month.'-'. $this->record_date);
            $TimeStart          = date('Y-m-'. $this->record_date, strtotime("-1 month", strtotime($TimeEnd)));

            $Model              = $Model->where('date', '>=', $TimeStart)->where('date', '<', $TimeEnd);

            $MaxModel   = clone $Model;
            $MaxData    = $MaxModel->groupBy('user_id')->get(['user_id',DB::raw('MAX(date) as max_date')])->toArray();
            if(!empty($MaxData)){
                $ListReport  = $Model->where(function($query) use($MaxData){
                    foreach($MaxData as $val){
                        $query = $query->orWhere(function($q) use($val){
                            $q->where('user_id', $val['user_id'])
                                ->where('date', $val['max_date']);
                        });
                    }
                })->get()->toArray();
            }

        }else{
            $ListReport = $Model->where('date', $Date)->get()->toArray();
        }

        if(!empty($ListReport)){
            foreach($ListReport as $val){
                $Data[$val['user_id']] = [
                    'percent'               => $val['percent'],
                    'succeed'               => $val['succeed'],
                    'total'                 => $val['total'],
                    'revenue_firstmonth'    => $val['revenue_firstmonth'],
                    'revenue_nextmonth'     => $val['revenue_nextmonth'],
                    'succeed_target'        => $val['succeed_target']
                ];
            }
        }

        return Response::json([
            'error'         => false,
            'message'       => 'Success',
            'data'          => $Data
        ]);
    }

    public function getStatisticEmployee()
    {
        $itemPage   = 40;
        $page       = Input::has('page')            ? (int)Input::get('page')                   : 1;
        $Team       = Input::has('team')            ? trim(Input::get('team'))                  : '';
        $Email      = Input::has('email')           ? strtolower(trim(Input::get('email')))     : '';
        $Date       = Input::has('date')            ? trim(Input::get('date'))                  : '';
        $Month      = Input::has('month')           ? trim(Input::get('month'))                 : '';

        // Group Privilege
        $Group          = Input::has('group')           ? (int)Input::get('group')                  : 3;

        $Data   = $ListEmployee = $Statistic = $ListReport = [];
        $Total  = 0;

        //Get List User by Group
        $GroupConfig    = \reportmodel\KPIGroupConfigModel::where('group', $Group)->where('active',1)->lists('id');
        if(empty($GroupConfig)){
            return Response::json([
                'error'         => false,
                'message'       => 'success',
                'total'         => $Total,
                'data'          => $Data,
                'statistic'     => $Statistic
            ]);
        }

        $offset = ($page - 1)*$itemPage;
        $Model  = new \reportmodel\CrmEmployeeModel;
        $Model  = $Model::whereIn('group_config',$GroupConfig)->where('active',1);

        if(!empty($Email)){
            $Email  = explode(',',$Email);
            $Model  = $Model->whereIn('email', $Email);
        }

        if(!empty($Team)){
            $Team   = explode(',',$Team);
            $Model  = $Model->whereIn('group_config', $Team);
        }

        if(empty($Date) && empty($Month)){
            return Response::json([
                'error'         => false,
                'message'       => 'success',
                'total'         => $Total,
                'data'          => $Data,
                'statistic'     => $Statistic
            ]);
        }

        $TotalModel     = clone $Model;
        $Total          = $TotalModel->count();
        if($Total > 0) {
            $Data = $Model->with(['__user'])->skip($offset)->take($itemPage)->get()->toArray();
            foreach($Data as $val){
                $ListEmployee[] =   $val['user_id'];
            }

            $KPIEmployeeModel = \reportmodel\KPIEmployeeModel::whereIn('user_id', $ListEmployee);
            if(!empty($Month)){
                $TimeEnd            = date($Month.'-'. $this->record_date);
                $TimeStart          = date('Y-m-'. $this->record_date, strtotime("-1 month", strtotime($TimeEnd)));

                $KPIEmployeeModel   = $KPIEmployeeModel->where('date', '>=', $TimeStart)->where('date', '<', $TimeEnd);

                $MaxModel   = clone $KPIEmployeeModel;
                $MaxData    = $MaxModel->groupBy('user_id')->get(['user_id',DB::raw('MAX(date) as max_date')])->toArray();
                if(!empty($MaxData)){
                    $ListReport  = $KPIEmployeeModel->where(function($query) use($MaxData){
                        foreach($MaxData as $val){
                            $query = $query->orWhere(function($q) use($val){
                                $q->where('user_id', $val['user_id'])
                                    ->where('date', $val['max_date']);
                            });
                        }
                    })->get()->toArray();
                }

            }else{
                $ListReport = $KPIEmployeeModel->where('date', $Date)->get()->toArray();
            }

            if(!empty($ListReport)){
                foreach($ListReport as $val){
                    $Statistic[$val['user_id']] = [
                        'percent'       => $val['percent'],
                        'commission'    => $val['commission'],
                        'bonus'         => $val['bonus'],
                        'allowance'     => $val['allowance'],
                        'salary'        => $val['salary']
                    ];
                }
            }

        }

        return Response::json([
            'error'         => false,
            'message'       => 'Success',
            'total'         => $Total,
            'data'          => $Data,
            'statistic'     => $Statistic
        ]);
    }

}
