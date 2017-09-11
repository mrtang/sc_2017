<?php namespace seller;

use sellermodel\CourierModel;

class CourierConfigCtrl extends BaseCtrl {
    public function __construct(){

    }

    public function getCourierConfig(){
        $UserInfo           = $this->UserInfo();

        return Response::json(
            [
                'error'         => false,
                'code'          => 'SUCCESS',
                'error_message' => 'Thành công',
                'data'          => CourierModel::where('user_id', (int)$UserInfo['id'])->where('active',1)->get(['courier_id','config_type','priority','amount_start','amount_end','active'])->toArray()
            ]
        );
    }

    public function postActiveCourier(){
        $CourierId      = Input::has('courier_id')      ? (int)Input::get('courier_id')     : 0;
        $ConfigType     = Input::has('config_type')     ? (int)Input::get('config_type')    : 0;
        $Priority       = Input::has('priority')        ? (int)Input::get('priority')       : null;
        $MinAmount      = Input::has('amount_start')    ? (int)Input::get('amount_start')   : 0;
        $MaxAmount      = Input::has('amount_end')      ? (int)Input::get('amount_end')     : 0;

        $Active         = Input::has('active')      ? (int)Input::get('active')         : null;

        if(empty($CourierId) || empty($ConfigType)){
            return Response::json(
                [
                    'error'         => true,
                    'code'          => 'INVALID',
                    'error_message' => 'Lỗi dữ liệu !'
                ]
            );
        }

        $UserInfo           = $this->UserInfo();
        $CourierModel   = new CourierModel;

        $Config             = $CourierModel::firstOrNew([
            'user_id'       => (int)$UserInfo['id'],
            'courier_id'    => (int)$CourierId,
            'config_type'   => (int)$ConfigType
        ]);

        if(isset($Active)){
            $Config->active     = $Active;
        }

        if(!empty($Priority)){
            $Config->priority   = $Priority;
        }

        if(!empty($MinAmount)){
            if($MinAmount >= $Config->amount_end){
                return Response::json(
                    [
                        'error'         => true,
                        'code'          => 'VALUE_ERROR',
                        'error_message' => 'Giá trị không hợp lệ'
                    ]
                );
            }

            $Config->amount_start   = $MinAmount;
        }

        if(!empty($MaxAmount)){
            if($MaxAmount <= $Config->amount_start){
                return Response::json(
                    [
                        'error'         => true,
                        'code'          => 'VALUE_ERROR',
                        'error_message' => 'Giá trị không hợp lệ'
                    ]
                );
            }
            $Config->amount_end     = $MaxAmount;
        }

        try{
            $Config->save();
            return Response::json(
                [
                    'error'         => false,
                    'code'          => 'SUCCESS',
                    'error_message' => 'Thành Công'
                ]
            );

        }catch (Exception $e){
            return Response::json(
                [
                    'error'         => true,
                    'code'          => 'UPDATE_ERROR',
                    'error_message' => 'Cập nhật thất bại'
                ]
            );
        }
    }

    public function postPriorityCourier(){
        $Priority       = Input::has('priority')    ? (int)Input::get('priority')       : null;
        $CourierId      = Input::has('courier_id')  ? (int)Input::get('courier_id')     : 0;

        if(!isset($Priority) || empty($CourierId)){
            return Response::json(
                [
                    'error'         => true,
                    'code'          => 'INVALID',
                    'error_message' => 'Lỗi dữ liệu !'
                ]
            );
        }

        $UserInfo           = $this->UserInfo();
        $CourierModel       = new CourierModel;

        $ConfigModel        = new BaseCtrl;
        $ConfigTypeCourier  = $ConfigModel->getConfigTypeCourier(false);
        if(empty($ConfigTypeCourier)){
            return Response::json(
                [
                    'error'         => true,
                    'code'          => 'ERROR',
                    'error_message' => 'Lỗi, hãy thử lại !'
                ]
            );
        }

        try{
            if(CourierModel::where('user_id', $UserInfo['id'])->where('courier_id', $CourierId)->count() >= count($ConfigTypeCourier)){
                $Update = ['priority' => $Priority];
                if(empty($Priority)){
                    $Update['active']   = 0;
                }else{
                    $Update['active']   = 1;
                }

                $CourierModel::where('user_id', $UserInfo['id'])->where('courier_id', $CourierId)->update($Update);
            }else{
                foreach($ConfigTypeCourier as $val){
                    $Config = $CourierModel->firstOrNew(['user_id' => $UserInfo['id'], 'courier_id' => $CourierId, 'config_type' => (int)$val['id']]);
                    $Config->priority   = $Priority;
                    if(empty($Priority)){
                        $Config->active   = 0;
                    }

                    $Config->save();
                }
            }

            return Response::json(
                [
                    'error'         => false,
                    'code'          => 'SUCCESS',
                    'error_message' => 'Thành Công'
                ]
            );
        }catch (Exception $e){
            return Response::json(
                [
                    'error'         => true,
                    'code'          => 'UPDATE_ERROR',
                    'error_message' => 'Cập nhật lỗi'
                ]
            );
        }

    }



    public function getAbc(){
        $Priority       = 5;
        $CourierId      = 11;

        if(empty($Priority) || empty($CourierId)){
            return Response::json(
                [
                    'error'         => true,
                    'code'          => 'INVALID',
                    'error_message' => 'Lỗi dữ liệu !'
                ]
            );
        }

        
        $CourierModel       = new CourierModel;

        $ConfigModel        = new BaseCtrl;

        $ConfigTypeCourier  = $ConfigModel->getConfigTypeCourier(false);
        if(empty($ConfigTypeCourier)){
            return Response::json(
                [
                    'error'         => true,
                    'code'          => 'ERROR',
                    'error_message' => 'Lỗi, hãy thử lại !'
                ]
            );
        }
        //$listUser = [37358,33751,63783,2272,65167,56855,52174,67268,55306,64250,1844,31412,45331,37738,37419,45507,46043,67255,53739,67382,36678,53892,39170,60833,55938,60780,58311,56727,67168,53734,61554,63855,49585,61455,62718,64862,60140,37743,43054,63319,65453,63198,66458,56998,48150,64545,56290,52127,63603,42103,48368,36888,64480,61337,42297,50183,51868,58563,53459,59547,51378,64564,51730,55718,39998,65366,62124,40589,65571,67344,55244,44962,2484,35681,34212,44396,54349,57638,35089,60493,53490,35206,3523,65743,42395,38169,66095,62512,64786,53988,42082,50924,67235,43125,49142,61316,48133,67180,36646,47744,31194,47169,47390,49270,58534,60826,55419,63466,57752,54919,67317,35296,67195,63755,66968,50135,57668,50738,43298,56110,67132,64182,64129,66865,67274,67194,67292,49311,57952,64017,66294,64806,41232,48192,63724,43678,62697,67301,52536,3735,57256,60432,37633,52817,64620,47648,3756,37775,38078,54171,52922,67329,41035,59504,54631,54223,42354,53146,51700,34873,60020,66478,609,35557,47457,49073,67222,62565,42535,35981,67232,58659,62882,48763,55760,63870,30524,58929,65472,43116,58931,60429,63958,59762,33830,49249,65367,64951,60839,61044,48390,67296,67357,31221,65325,62881,46592,36233,61596,60078,59307,33325,58674,66663,57036,58349,59014,67065,62675,55151,45899,55265,52446,37800,55184,66924,37862,58152,47165,51205,52352,52241,62753,45112,60043,57450,67289,38662,49784,63896,33703,56620,3621,58542,66161,55688,52066,36445,51988,51939,66232,62412,67187,50463,62715,67316,63542,51204,56728,3062,66058,63861,50674,47721,51187,36617,32538,55986,3514];
        $listUser = [37358,50064,54005,49391,33751,34735,63783,2272,64792,47499,3538,57468,65167,46587,56855,31224,67456,34014,35090,52174,62452,67268,59907,67491,46067,67452,46481,67410,39928,53044,60646,55306,64250,36796,1844,67413,65320,30618,31412,45331,57162,40698,62942,62436,39233,62556,57677,47688,37738,59220,37419,41168,45507,46043,67255,67443,53739,50879,67421,32053,60585,67382,36678,45826,64616,58507,45778,53892,48551,39170,66837,3649,60833,56394,66481,30693,55938,52469,60780,58311,56727,67168,53734,59189,61554,63855,63558,49585,61455,45868,62718,56804,63875,64862,1386,60140,37158,48980,37743,57405,65981,38743,43054,63319,58609,65453,57014,63198,32726,3528,66458,39042,4022,31066,56998,48150,64545,56290,66507,67102,52127,58611,63603,61776,42103,64821,48368,4042,58282,67358,51052,47136,36888,63094,52328,63964,53979,62557,50378,57005,64480,61337,65737,42297,50183,32051,51868,1468,51357,38646,45086,58563,61234,3447,53459,41339,50024,55383,59547,51378,66003,59910,36253,60769,46643,40761,64564,51730,55718,39998,65366,49425,41038,62124,66068,40589,65571,39783,67344,55244,44775,2113,44962,30384,58447,2484,59847,2184,57866,35681,34212,44396,50662,54349,63212,57638,35089,60493,53490,31079,38870,35206,49746,3523,65743,42395,66949,38169,53731,66095,54069,62512,31927,64786,53988,46595,53636,46848,48402,42082,59475,30486,50924,33714,2743,38039,38599,67235,43125,43231,49142,64385,61316,51157,48133,67180,2458,30630,48516,1413,36646,47027,42344,45391,47744,67434,31194,51289,47169,47390,49270,36990,57603,52961,40400,49280,58534,66664,60826,39407,46564,55409,65863,67460,55419,2383,63466,57752,67444,54919,67317,35296,41110,67195,32574,56054,63048,63755,67464,66968,41024,30032,50135,30546,57668,44163,50738,43298,41709,56110,67132,46316,64182,67364,65950,61423,64129,66865,66195,40336,67274,56090,64368,66145,67194,47679,67292,32187,51010,35208,49311,67340,57952,49802,31243,65066,67449,67478,64017,30496,65229,42503,49559,51583,66294,64806,41232,48192,54875,37768,1343,63724,46436,43678,62697,67301,52536,3735,57256,65777,67476,57302,60432,37633,52817,30320,64620,64958,47648,47307,2441,67334,56008,3756,37775,55251,67049,66825,38078,41530,54171,64201,52922,32243,39155,67329,41035,59504,35899,61806,54631,54223,42354,45867,30530,53146,52863,41558,60658,38163,51700,34873,60020,47775,66478,57577,56233,609,35557,47457,64028,49073,45827,40333,67222,30437,44022,34859,67264,62565,42535,59456,35981,61783,56435,37866,63195,67232,67409,2990,34229,41752,58659,53391,62882,48763,49020,55760,60676,63870,53527,34768,1259,58360,30524,58929,65472,43116,58931,60429,59099,45820,63958,2997,65400,57552,50731,59762,33830,49249,65367,47563,61438,52765,64951,1405,53432,60839,61044,49242,48390,67296,67357,67423,963,31221,65325,53840,64940,62881,46592,36233,53168,61596,67489,48527,55945,58676,63908,60078,59307,67320,41502,30490,31617,31621,33325,38514,58674,66663,3398,62693,33824,57036,58349,59014,33182,41784,67065,62675,55151,64846,45899,55265,67414,65350,52446,37800,59484,56191,48065,55184,66924,42664,36988,37862,58152,47165,63709,51205,65742,52352,52241,62753,34175,45112,60043,47969,35077,65909,57450,67289,37425,38662,49784,62045,46058,45153,55255,63896,33703,31218,56620,61382,3621,1840,58542,59825,48442,66161,32627,55688,52066,36445,46403,48238,35044,51988,58518,67459,67442,64890,51939,66232,53100,62412,32967,38543,67187,50463,62715,67316,46732,63542,64668,51204,62781,53785,56728,3727,3062,66058,63861,50674,819,48783,44,57408,35147,47721,59486,51187,37530,32776,36617,62858,32538,55986,3514];
        
        try{

            foreach ($listUser as $key => $value) {
                $UserId = $value;

                if(CourierModel::where('user_id', $UserId)->where('courier_id', $CourierId)->count() >= count($ConfigTypeCourier)){
                    $CourierModel::where('user_id', $UserId)->where('courier_id', $CourierId)->update(['priority' => $Priority]);
                }else{
                    foreach($ConfigTypeCourier as $val){
                        $Config = $CourierModel->firstOrNew(['user_id' => $UserId, 'courier_id' => $CourierId, 'config_type' => (int)$val['id']]);
                        $Config->priority   = $Priority;
                        $Config->save();
                    }
                }
                
            }

            return Response::json(
                [
                    'error'         => false,
                    'code'          => 'SUCCESS',
                    'error_message' => 'Thành Công'
                ]
            );
        }catch (Exception $e){
            return Response::json(
                [
                    'error'         => true,
                    'code'          => 'UPDATE_ERROR',
                    'error_message' => 'Cập nhật lỗi'
                ]
            );
        }

    }
}
