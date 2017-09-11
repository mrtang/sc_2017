<?php namespace mobile_order;

use Input;
use Response;
use Exception;
use DB;
use ordermodel\VerifyModel;
use ordermodel\OrdersModel;
use accountingmodel\MerchantModel;
use accountingmodel\TransactionModel;
use User;
use Excel;
use LMongo;
use File;

class VerifyController extends \BaseController
{
    private $Status;
    private $Active;
    private $master_id = 1;
    private $list_status = [
        'WAITING' => 'Chờ đối soát',
        'PROCESSING' => 'Đang đối soát',
        'SUCCESS' => 'Đã đối soát'
    ];

    private $verify_status = [
        'INSERT' => 'Chờ xác nhận',
        'WAITING' => 'Chờ chuyển tiền',
        'PROCESSING' => 'Đang chuyển tiền',
        'SUCCESS' => 'Đã chuyển tiền'
    ];

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function getIndex()
    {
        $page = Input::has('page') ? (int)Input::get('page') : 1;
        $itemPage = Input::has('limit') ? Input::get('limit') : 20;
        $TimeStart = Input::has('time_start') ? trim(Input::get('time_start')) : '';
        $TimeEnd = Input::has('time_end') ? trim(Input::get('time_end')) : '';
        $Search = Input::has('search') ? (int)Input::get('search') : 0;
        $cmd = Input::has('cmd') ? Input::get('cmd') : "";


        if (!empty($cmd) && $cmd == 'demo') {
            $Data = [
                [
                    "id" => 104152,
                    "user_id" => 41882,
                    "user_nl_id" => 166197,
                    "accept_id" => 39942,
                    "email_nl" => "thinh.nl@gmail.com",
                    "total_fee" => 132000,
                    "total_money_collect" => 790000,
                    "balance_available" => -225500,
                    "config_balance" => 200000,
                    "balance" => 461500,
                    "real_blance" => 4615200,
                    "type_payment" => 1,
                    "transaction_code" => "",
                    "transaction_id" => 14278362,
                    "type" => 1,
                    "time_create" => 1427765259,
                    "time_accept" => 1427855251,
                    "time_start" => 1427302800,
                    "time_end" => 1427648399,
                    "status" => "SUCCESS",
                    "status_name" => "Đã chuyển tiền",
                    "note" => null,
                    "notification" => 0,
                    "user" => [
                        "id" => 41882,
                        "email" => "nguyenvanthinhypbn@gmail.com",
                        "fullname" => "Nguyễn Văn Thịnh",
                        "phone" => "01626616817"
                    ]

                ],
                [
                    "id" => 104152,
                    "user_id" => 41882,
                    "user_nl_id" => 166197,
                    "accept_id" => 39942,
                    "email_nl" => "thinh.nl@gmail.com",
                    "total_fee" => 132000,
                    "total_money_collect" => 900000,
                    "balance_available" => -325500,
                    "config_balance" => 200000,
                    "real_blance" => 4615200,
                    "balance" => 461500,
                    "type_payment" => 1,
                    "transaction_code" => "",
                    "transaction_id" => 14278362,
                    "type" => 2,
                    "time_create" => 1427765259,
                    "time_accept" => 1427855251,
                    "time_start" => 1427302800,
                    "time_end" => 1427648399,
                    "status" => "SUCCESS",
                    "status_name" => "Đã chuyển tiền",
                    "note" => null,
                    "notification" => 0,
                    "user" => [
                        "id" => 41882,
                        "email" => "nguyenvanthinhypbn@gmail.com",
                        "fullname" => "Nguyễn Văn Thịnh",
                        "phone" => "01626616817"
                    ]

                ]
            ];


            return Response::json(
                [
                    'error' => false,
                    'error_message' => '',
                    'item_page' => $itemPage,
                    'total' => sizeof($Data),
                    'total_page' => ceil(sizeof($Data) / $itemPage),
                    'data' => $Data
                ]);
        }


        $UserInfo = $this->UserInfo();
        $id = (int)$UserInfo['id'];


        $Model = new VerifyModel;
        $Model = $Model->where('user_id', $id);
        $Total = 0;

        if (!empty($TimeStart)) {
            $TimeStart = time() - $TimeStart * 86400;
            $Model = $Model->where('time_create', '>=', $TimeStart);
        } else {
            $Model = $Model->where('time_create', '>=', strtotime(date('Y-m-1 00:00:00')));
        }

        if (!empty($Search)) {
            $Model = $Model->where('id', $Search);
        }

        if (!empty($TimeEnd)) {
            $Model = $Model->where('time_create', '<', $TimeEnd);
        }

        $ModelTotal = clone $Model;

        $Total = $ModelTotal->count();
        $Data = array();

        $Model = $Model->orderBy('time_create', 'DESC');
        if ($Total > 0) {
            if ((int)$itemPage > 0) {
                $itemPage = (int)$itemPage;
                $offset = ($page - 1) * $itemPage;
                $Model = $Model->skip($offset)->take($itemPage);
            }

            $Data = $Model->with(array('User' => function ($query) {
                $query->get(['id', 'email', 'fullname', 'phone']);
            }))->get()->toArray();
        }


        foreach ($Data as $key => $val) {
            if (!empty($this->verify_status[$val['status']])) {
                $Data[$key]['status_name'] = $this->verify_status[$val['status']];
            }
            $ThucNhan = 0;
            if ($val['type'] == 2) {
                $ThucNhan = ($val['total_money_collect'] - $val['total_fee']);
            } else {
                $ThucNhan = $val['total_money_collect'] + $val['balance'] - $val['total_fee'] + (($val['balance_available'] - $val['config_balance']) < 0 ? ($val['balance_available'] - $val['config_balance']) : 0) ;
                /*$ThucNhan = ($val['total_money_collect'] + $val['balance'] - $val['total_fee'] + (($val['balance_available'] - $val['config_balance']) < 0 ? ($val['balance_available'] - $val['config_balance']) : 0)) > 0 ?
                    ($val['total_money_collect'] + $val['balance'] - $val['total_fee'] + (($val['balance_available'] - $val['config_balance']) < 0 ? ($val['balance_available'] - $val['config_balance']) : 0)) : 0;*/
            }
            $Data[$key]['real_blance'] = $ThucNhan;
        }

        $contents = array(
            'error' => false,
            'error_message' => 'Thành công',
            'item_page' => $itemPage,
            'total' => $Total,
            'data' => $Data
        );

        return Response::json($contents);
    }

    public function getLastverify()
    {
        $Model = new VerifyModel;
        $Model = $Model->where('time_create', '>=', strtotime(date('Y-m-1 00:00:00')));
        $Model = $Model->where('status', '=', 'SUCCESS');
        $Model = $Model->orderBy('time_create', 'DESC');
        $Verify = $Model->select('id', 'time_create', 'time_accept')->first();
        //var_dump($Verify);


        return Response::json(array(
            "error" => false,
            "data" => ($Verify) ? $Verify : [],
            "message" => ""
        ));

    }


    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function getShow($id)
    {
        $UserInfo = $this->UserInfo();
        $UserId = (int)$UserInfo['id'];

        $TimeStart = Input::has('time_start') ? trim(Input::get('time_start')) : 0;
        $Cmd = Input::has('cmd') ? trim(Input::get('cmd')) : "";
        $page = Input::has('page') ? (int)Input::get('page') : 1;
        $itemPage = Input::has('limit') ? Input::get('limit') : 20;

        /*if (empty($TimeStart)) {
            return Response::json([
                'error' => false,
                'error_message' => 'Thành công',
                'data' => []
            ]);
        }*/

        if ($Cmd == 'demo') {
            $Data = [
                "id" => 147616,
                "order" => [
                    [
                        "id" => 617953,
                        "tracking_code" => "SC51580509112",
                        "verify_id" => 147616,
                        "status" => 52,
                        "order_detail" => [
                            "id" => 616117,
                            "order_id" => 617953,
                            "money_collect" => 320000,
                            "sc_cod" => 15000,
                            "sc_discount_cod" => 0,
                            "sc_discount_pvc" => 0,
                            "sc_pbh" => 0,
                            "sc_pch" => 0,
                            "sc_pvc" => 28400,
                            "sc_pvk" => 0,
                            "seller_cod" => 0,
                            "seller_discount" => 0,
                            "seller_pvc" => 28400
                        ],
                        "status_name" => "Đã phát thành công"
                    ],
                    [
                        "id" => 636932,
                        "tracking_code" => "SC5363633126",
                        "verify_id" => 147616,
                        "status" => 66,
                        "order_detail" => [
                            "id" => 635093,
                            "order_id" => 636932,
                            "money_collect" => 280000,
                            "sc_cod" => 15000,
                            "sc_discount_cod" => 0,
                            "sc_discount_pvc" => 0,
                            "sc_pbh" => 0,
                            "sc_pch" => 14700,
                            "sc_pvc" => 29400,
                            "sc_pvk" => 0,
                            "seller_cod" => 0,
                            "seller_discount" => 0,
                            "seller_pvc" => 0
                        ],
                        "status_name" => "Đã chuyển hoàn/Phát hoàn thành công"
                    ],
                ]
            ];

            $contents = array(
                'error'         => false,
                'error_message' => 'Thành công',
                'data'          => $Data
            );
            return Response::json($contents);
        }
        $validation = \Validator::make(array('id' => $id), array(
            'id' => 'required|numeric|min:1'
        ));

        //error
        if ($validation->fails()) {
            return Response::json(array('error' => true, 'error_message' => 'Không tìm thấy mã bản kê '));
        }


        $Model = new OrdersModel;
        $Total = 0;


        $itemPage = (int)$itemPage;
        $offset = ($page - 1) * $itemPage;

        $query = $Model->where('verify_id', $id)
                ->where('time_create', '>=', time() - $this->time_limit)
                ->where('time_accept', '>=', time() - $this->time_limit)
                ->where('time_create', '<', time());

        $Total = clone $query;
        $Total = $Total->count();
        $Data  = [];

        $Data = $query->skip($offset)->take($itemPage)
            ->with(['OrderDetail' => function ($q) {
                $q->select(['id', 'order_id', 'money_collect', 'sc_cod', 'sc_discount_cod', 'sc_discount_pvc', 'sc_pbh', 'sc_pch', 'sc_pvc', 'sc_pvk', 'seller_cod', 'seller_discount', 'seller_pvc']);
            }])->get(array('id', 'tracking_code', 'verify_id', 'status'))->toArray();




        $TotalFee   = 0;
        $Real       = 0;
        $Discount   = 0;
        if (!empty($Data)) {
            $listStatus = $this->getStatus();
            foreach ($Data as $key => $value) {

                

                if($value['status'] == 66){
                    $Real     = 0 - ($value['order_detail']['sc_pvc'] + $value['order_detail']['sc_pvk'] + $value['order_detail']['sc_pch'] - $value['order_detail']['sc_discount_pvc']);
                    $TotalFee = ($value['order_detail']['sc_pvc'] + $value['order_detail']['sc_pvk'] + $value['order_detail']['sc_pch'] - $value['order_detail']['sc_discount_pvc']);

                    $Data[$key]['order_detail']['money_collect'] = 0;
                    $Data[$key]['order_detail']['sc_cod'] = 0;
                    $Data[$key]['order_detail']['sc_pbh'] = 0;
                    $Data[$key]['order_detail']['sc_pch'] = 0;

                    $Discount = $Data[$key]['order_detail']['sc_discount_pvc'];
                }else {
                    $TotalFee = ($value['order_detail']['sc_pvc'] + $value['order_detail']['sc_cod'] + $value['order_detail']['sc_pvk'] + $value['order_detail']['sc_pbh'] - ($value['order_detail']['sc_discount_cod'] + $value['order_detail']['sc_discount_pvc']));
                    $Real     = $value['order_detail']['money_collect'] - ($value['order_detail']['sc_pvc'] + $value['order_detail']['sc_cod'] + $value['order_detail']['sc_pvk'] + $value['order_detail']['sc_pbh'] - ($value['order_detail']['sc_discount_cod'] + $value['order_detail']['sc_discount_pvc']));
                    $Discount = $Data[$key]['order_detail']['sc_discount_cod'] + $Data[$key]['order_detail']['sc_discount_pvc'];

                }
                $Data[$key]['order_detail']['total_fee'] = $TotalFee;
                $Data[$key]['order_detail']['total_discount'] = $Discount;
                $Data[$key]['real_blance'] = $Real;

                if (!empty($listStatus[$value['status']])) {
                    $Data[$key]['status_name'] = $listStatus[$value['status']];
                }
            }
        }

        $contents = array(
            'error'         => false,
            'error_message' => 'Thành công',
            'total'=> $Total,
            'total_page'    => ceil($Total/$itemPage),
            'data' => [
                'order'=> $Data
            ]
        );
        return Response::json($contents);
    }

    /**
     * get verify detail
     */
    public function getVerifydetail($id)
    {
        $page = Input::has('page') ? (int)Input::get('page') : 1;
        $Search = Input::has('search') ? trim(Input::get('search')) : '';
        $itemPage = Input::has('limit') ? Input::get('limit') : 20;
        $TimeStart = Input::has('time_start') ? (int)Input::get('time_start') : 0;

        $Model = new OrdersModel;
        $Model = $Model::where('time_accept', '>=', $TimeStart - $this->time_limit)
            ->where('time_accept', '<=', $TimeStart)
            ->where('verify_id', (int)$id);

        if (!empty($Search)) {
            $Model = $Model->where('tracking_code', $Search);
        }

        $ModelTotal = clone $Model;
        $Total = $ModelTotal->count();
        $Data = [];


        if ($Total > 0) {
            $itemPage = (int)$itemPage;
            $offset = ($page - 1) * $itemPage;
            $Data = $Model->skip($offset)->take($itemPage)->with('OrderDetail')->get(array('id', 'tracking_code', 'verify_id', 'status'))->toArray();
        }
        $Real = 0;
        foreach($Data as $key=> $value){

            if($value['status'] == 66){
                $Real = ($value['order_detail']['sc_pvc'] + $value['order_detail']['sc_pvk'] + $value['order_detail']['sc_pch'] - $value['order_detail']['sc_discount_pvc']);
            }else {
                $Real = ($value['order_detail']['sc_pvc'] + $value['order_detail']['sc_cod'] + $value['order_detail']['sc_pvk'] + $value['order_detail']['sc_pbh'] - ($value['order_detail']['sc_discount_cod'] + $value['order_detail']['sc_discount_pvc']));
            }

            $Data[$key]['real_blance'] = $Real;

        }


        $contents = array(
            'error' => false,
            'message' => 'success',
            'total' => $Total,
            'data' => $Data
        );

        return Response::json($contents);

    }


}
