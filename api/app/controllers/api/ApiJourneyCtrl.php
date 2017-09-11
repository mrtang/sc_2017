<?php
class ApiJourneyCtrl extends \BaseController {
    
    private $validation, $__Lading, $__Address;
    
    function __construct(){
        Input::merge(Input::json()->all());

        if(Input::get('username') == 'ttc'){
            Input::merge([
                'params'    => [
                    'NOTE'      =>  urldecode(Input::get('params.NOTE')),
                    'CITY'      =>  urldecode(Input::get('params.CITY')),
                    'SC_CODE'   => Input::get('params.SC_CODE'),
                    'STATUS'    => Input::get('params.STATUS'),
                    'WEIGHT'    => Input::get('params.WEIGHT'),
                    'COLLECT'   => Input::get('params.COLLECT'),
                ]
            ]);
        }

        if(Input::has('params')){            
            $merge = array(
                'TrackingOrder' => (!preg_match("/sc/i", Input::get('params.SC_CODE')) ? 'SC' : '') . Input::get('params.SC_CODE'),
                'TrackingCode'  => Input::has('params.HVC_CODE') ? Input::get('params.HVC_CODE') : ( (!preg_match("/sc/i", Input::get('params.SC_CODE')) ? 'SC' : '') . Input::get('params.SC_CODE') ),
                'Status'        => Input::get('params.STATUS'),
                'Note'          => Input::get('params.NOTE'),
                'City'          => Input::get('params.CITY'),
            );

            if(Input::has('params.WEIGHT')){
                $merge['Weight']    = Input::get('params.WEIGHT');
            }

            if(Input::has('params.Weight')){
                $merge['Weight']    = Input::get('params.Weight');
            }

            if(Input::has('params.COLLECT')){
                $merge['Collect']    = Input::get('params.COLLECT');
            }

            if(Input::has('params.Collect')){
                $merge['Collect']    = Input::get('params.Collect');
            }

            Input::merge($merge);
        }
    }
    
    private function _check_login_v1(){
        $arrAcc = Config::get('config_api.cfg_carrier_api');

        if(isset($arrAcc[Input::get('username')]) && $arrAcc[Input::get('username')] == Input::get('password'))
        {
            return array('error' => 'SUCCESS');
        }

        return array('error' => 'NOT_ACCESS');
    }
    
	public function getIndex()
	{
        return Response::json('1', 200);
	}

    private function _validation($params = false){
        $dataInput = array(
            //'Courier'           => 'required',
            'Status'            => 'required',
            'Note'              => 'required',
            'TrackingCode'      => 'required', // Mã HVC
            'TrackingOrder'     => 'required', // Mã SC
            'Weight'            => 'sometimes|required|numeric|min:0',
            'Collect'           => 'sometimes|required|numeric|min:0'
        );
        if($params){
            $dataInput += array(
                'City'              => 'required',
                'Province'          => 'required',
                'PostOffice'        => 'required',
            );
        }
        
        $this->validation = Validator::make(Input::all(), $dataInput);
    }

    private function _checkTrackingCode(){
        $this->__Lading = ordermodel\OrdersModel::where('tracking_code',Input::get('TrackingOrder'))
                            ->where('time_accept','>=',$this->time() - $this->time_limit)
                            ->first(['tracking_code','courier_id','status','to_address_id','domain']);
        
        //$this->__Address = ordermodel\AddressModel::where('id',$this->__Lading['to_address_id'])->first(array('city_id','province_id'));
    }

    private function _checkTrackingCodeNjv(){
        $this->__Lading = ordermodel\OrdersModel::where('tracking_code',Input::get('tracking_id'))
                            ->where('time_accept','>=',$this->time() - $this->time_limit)
                            ->first(['tracking_code','courier_id','status','to_address_id','domain']);
        
        //$this->__Address = ordermodel\AddressModel::where('id',$this->__Lading['to_address_id'])->first(array('city_id','province_id'));
    }
    
    public function postJourneyv1()
	{
        $this->_validation();
        // Check và báo invalid
        if($this->validation->fails()) {
            return Response::json(array('error' => 301, 'error_message' => $this->validation->messages()));
        }

        if(Input::get('TrackingOrder') == 'SC188754213'){
            Input::merge([
                'ip'                => $_SERVER['REMOTE_ADDR'],
                'port'              => $_SERVER['REMOTE_PORT'],
                'user_agent'        => $_SERVER['HTTP_USER_AGENT'],
            ]);
        }
        
        // Check Login
        $checkLogin = $this->_check_login_v1();
        
        $LMongo = new LMongo;
        
        if($checkLogin['error'] != 'SUCCESS'){
            return $checkLogin;
        }
        
        $LMongo = new LMongo;
        
        $idLog  = $LMongo::collection('log_journey_lading')
                                    ->insert(array(
                                                'tracking_code'     => Input::get('TrackingOrder'),
                                                'tracking_number'   => (int)substr(Input::get('TrackingOrder'),2),
                                                'input'         => Input::all(),
                                                "priority"      => 2,
                                                'accept'        => 0,
                                                'time_create'   => $this->time(),
                                            ));
        
        
        // Check Exist Lading
        $this->_checkTrackingCode();

        //
        if(is_numeric(Input::get('City')))
            Input::merge(array('City' => (int)Input::get('City')));

        if(is_numeric(Input::get('Province')))
            Input::merge(array('Province' => (int)Input::get('Province')));

        if(Input::get('TrackingCode')){
            Input::merge(array('courier_tracking_code' => Input::get('TrackingCode')));
        }

        if(!$this->__Lading){
            return Response::json(array('error' => 301, 'error_message' => 'Không tồn tại vận đơn'));
        }
        
        if((int)$this->__Address['city_id'] > 0){
            Input::merge(array('City' => (int)$this->__Address['city_id']));
        }
        if((int)$this->__Address['province_id'] > 0){
            Input::merge(array('Province' => (int)$this->__Address['province_id']));
        }

        if($idLog){
            $LMongo::collection('log_journey_lading')->where('_id', new \MongoId($idLog))->update(array(
                'courier'       => (int)$this->__Lading['courier_id'],
                'domain'        => $this->__Lading['domain'],
                'status'        => Input::get('Status'),
                'address'       => ['city' => Input::get('City'), 'province' => Input::get('Province')],                                                
                'note'          => Input::get('Note'),
                'weight'        => Input::has('Weight') ? (int)Input::get('Weight') : 0,
                'collect'       => Input::has('Collect') ? (int)Input::get('Collect') : 0,
                'time_update'   => $this->time(),
            ));

           // Call Predis
           try{
               $this->RabbitJourney($idLog);
           } catch(\Exception $e){

           }


            return Response::json(array(
                'error'         => 200, 
                'error_message' => 'success',
                'data'          => array(
                                    'LadingCode'    => Input::get('TrackingOrder'),
                                    'Status'        => Input::get('Status'),
                                )
                ));
        }
        else{
            return Response::json(array(
                'error'         => 301, 
                'error_message' => 'fail',
                'data'          => array(
                                    'LadingCode'    => Input::get('TrackingOrder'),
                                    'Status'        => Input::get('Status'),
                                )
                ));
        }
    }
    
	// public function postJourney()
	// {   
    //     $this->_validation();
    //     // Check và báo invalid
    //     if($this->validation->fails()) {
    //         return Response::json(array('error' => 'invalid', 'error_message' => $this->validation->messages()), 400);
    //     }
        
    //     //check ma van don co ton tai khong
    //     $Lading = $this->_checkTrackingCode(Input::get('TrackingOrder'));
        
    //     if((int)$Lading['city_id'] > 0){
    //         Input::merge(array('City' => (int)$Lading['city_id']));
    //     }
    //     if((int)$Lading['province_id'] > 0){
    //         Input::merge(array('Province' => (int)$Lading['province_id']));
    //     }
    //     if(Input::get('TrackingCode')){
    //         Input::merge(array('courier_tracking_code' => Input::get('TrackingCode')));
    //     }
    //     //
    //     if(is_numeric(Input::get('City')))
    //         Input::merge(array('City' => (int)Input::get('City')));
        
    //     if(is_numeric(Input::get('Province')))
    //         Input::merge(array('Province' => (int)Input::get('Province')));
            
    //     $CourierCode    = ResourceServer::getClientId();
        
    //     $LMongo         = new LMongo;
        
    //     // Check exist
    //     $this->idLog    = $LMongo::collection('log_journey_lading')
    //                                 ->insert(array(
    //                                             'courier'       => (int)$CourierCode,
    //                                             'domain'        => $this->__Lading['domain'],
    //                                             'tracking_code' => Input::get('TrackingOrder'),
    //                                             'tracking_number'   => (int)substr(Input::get('TrackingOrder'),2),
    //                                             'status'        => Input::get('Status'),
    //                                             'address'       => ['city' => Input::get('City'), 'province' => Input::get('Province')],                                                
    //                                             'note'          => Input::get('Note'),
    //                                             'input'         => Input::all(),
    //                                             'accept'        => 0,
    //                                             "priority"      => 2,
    //                                             'time_create'   => $this->time(),
    //                                             'time_update'   => $this->time(),
    //                                         ));
        
    //     return Response::json(array(
    //             'error'         => false, 
    //             'error_message' => 'success',
    //             'data'          => array(
    //                                 'LadingCode'    => Input::get('LadingCode'),
    //                                 'Status'        => Input::get('Status'),
    //                             )
    //             ), 200);
	// }

    //
    public function postUpdateJourney($data)
    {   
        $this->_validation();
        // Check và báo invalid
        if($this->validation->fails()) {
            return Response::json(array('error' => 'invalid', 'error_message' => $this->validation->messages()), 400);
        }
        //check ma van don co ton tai khong
        $Lading = $this->_checkTrackingCode(Input::get('TrackingOrder'));
        if((int)$Lading['city_id'] > 0){
            Input::merge(array('City' => (int)$Lading['city_id']));
        }
        if((int)$Lading['province_id'] > 0){
            Input::merge(array('Province' => (int)$Lading['province_id']));
        }
        if($data['TrackingCode']){
            Input::merge(array('courier_tracking_code' => $data['TrackingCode']));
        }
        //
        if(is_numeric(Input::get('City')))
            Input::merge(array('City' => (int)Input::get('City')));
        
        if(is_numeric(Input::get('Province')))
            Input::merge(array('Province' => (int)Input::get('Province')));
            
        $CourierCode    = ResourceServer::getClientId();
        
        $LMongo         = new LMongo;
        
        // Check exist
        $this->idLog    = $LMongo::collection('log_journey_lading')
                                    ->insert(array(
                                                'courier'       => (int)$CourierCode,
                                                'domain'        => $this->__Lading['domain'],
                                                'tracking_code' => $data['TrackingOrder'],
                                                'tracking_number'   => (int)substr(Input::get('TrackingOrder'),2),
                                                'status'        => $data['Status'],
                                                'input'         => Input::all(),
                                                'accept'        => (int)0,
                                                "priority"      => 2,
                                                'time_create'   => $this->time(),
                                                'time_update'   => $this->time(),
                                            ));
        
        return Response::json(array(
                'error'         => false, 
                'error_message' => 'success',
                'data'          => array(
                                    'LadingCode'    => Input::get('LadingCode'),
                                    'Status'        => Input::get('Status'),
                                )
                ), 200);
    }

    //xu ly Ninjavan
    public function postJourneynjv($id){
        if((int)$id < 0){
            return Response::json(array('error' => 301, 'error_message' => 'Not status'));
        }

        //define pickup, delivery fail
        $dataPickupFail = array(
            'Nobody at Location' => array('reason_id' => 1,'reason'=> 'Người bán không ở địa chỉ lấy hàng đã cũng cấp'),
            'Inaccurate Address' => array('reason_id' => 2,'reason'=> 'Địa chỉ lấy hàng không chính xác'),
            'Parcel Not Available' => array('reason_id' => 3,'reason'=> 'Không có hàng gửi'),
            'Parcel Too Bulky'  => array('reason_id' => 4,'reason'=> 'Hàng gửi quá cồng kềnh'),
            'Cancellation Requested' => array('reason_id' => 5,'reason'=> 'Huỷ yêu cầu lấy hàng do bưu tá huỷ')
        );
        $dataDeliveryFail = array(
            'Return to Sender: Nobody at address' => array('reason_id' => 1,'reason'=> 'Phát không thành công khách hàng không ở địa chỉ cung cấp'),
            'Return to Sender: Unable to find Address' => array('reason_id' => 2,'reason'=> 'Không thể xác định địa điểm giao hàng'),
            'Return to Sender: Item refused at Doorstep' => array('reason_id' => 3,'reason'=> 'Khách hàng từ chối nhận hàng'),
            'Return to Sender: Refused to pay COD' => array('reason_id' => 4,'reason'=> 'Khách hàng từ chối trả tiền'),
            'Return to Sender: Customer delayed beyond delivery period' => array('reason_id' => 5,'reason'=> 'Khách hàng hẹn giao hàng vào lần khác'),
            'Return to Sender: Cancelled by Shipper' => array('reason_id' => 6,'reason'=> 'Huỷ giao hàng từ bưu tá'),
        );

        $dataInput = array();
        switch ($id) {
            case '100'://vua duyet sang HVC
                $dataInput = array(
                    'username' => 'njv',
                    'password' => '',
                    'function' => '',
                    'params'   => array(
                        'SC_CODE'      => Input::get('tracking_id'),
                        'STATUS'       => $id,
                        'CITY'         => 'Ha Noi',
                        'NOTE'         => $id.' / Tạo đơn hàng qua NJV thành công',
                        'ERROR_CODE'   => '',
                        'Weight'       => '',
                        'MABUUCUC'     => ''
                    ),
                    'TrackingOrder' => Input::get('tracking_id'),
                    'TrackingCode'  => Input::get('tracking_id'),
                    'Note'          => $id.' / Tạo đơn hàng qua NJV thành công',
                    'City'          => 'Ha Noi',
                    'Weight'        => '',
                    'Status'        => $id
                );
                break;
            case '103'://cho lay hang
                $dataInput = array(
                    'username' => 'njv',
                    'password' => '',
                    'function' => '',
                    'params'   => array(
                        'SC_CODE'      => Input::get('tracking_id'),
                        'STATUS'       => $id,
                        'CITY'         => 'Ha Noi',
                        'NOTE'         => $id.' / Chờ lấy hàng',
                        'ERROR_CODE'   => '',
                        'Weight'       => '',
                        'MABUUCUC'     => ''
                    ),
                    'TrackingOrder' => Input::get('tracking_id'),
                    'TrackingCode'  => Input::get('tracking_id'),
                    'Note'          => $id.' / Chờ lấy hàng',
                    'City'          => 'Ha Noi',
                    'Weight'        => '',
                    'Status'        => $id
                );
                break;
            case '104':// dang lay hang
                $dataInput = array(
                    'username' => 'njv',
                    'password' => '',
                    'function' => '',
                    'params'   => array(
                        'SC_CODE'      => Input::get('tracking_id'),
                        'STATUS'       => $id,
                        'CITY'         => 'Ha Noi',
                        'NOTE'         => $id.' / Đang lấy hàng',
                        'ERROR_CODE'   => '',
                        'Weight'       => '',
                        'MABUUCUC'     => ''
                    ),
                    'TrackingOrder' => Input::get('tracking_id'),
                    'TrackingCode'  => Input::get('tracking_id'),
                    'Note'          => $id.' / Đang lấy hàng',
                    'City'          => 'Ha Noi',
                    'Weight'        => '',
                    'Status'        => $id
                );
                break;
            case '105'://nhan bang ke den
                $dataInput = array(
                    'username' => 'njv',
                    'password' => '',
                    'function' => '',
                    'params'   => array(
                        'SC_CODE'      => Input::get('tracking_id'),
                        'STATUS'       => $id,
                        'CITY'         => 'Ha Noi',
                        'NOTE'         => $id.' / Nhận bảng kê đến',
                        'ERROR_CODE'   => '',
                        'Weight'       => '',
                        'MABUUCUC'     => ''
                    ),
                    'TrackingOrder' => Input::get('tracking_id'),
                    'TrackingCode'  => Input::get('tracking_id'),
                    'Note'          => $id.' / Nhận bảng kê đến',
                    'City'          => 'Ha Noi',
                    'Weight'        => '',
                    'Status'        => $id
                );
                break;
            case '106'://lay hang khong thanh cong
                $note = $dataPickupFail[Input::get('comments')]['reason'];
                $dataInput = array(
                    'username' => 'njv',
                    'password' => '',
                    'function' => '',
                    'params'   => array(
                        'SC_CODE'      => Input::get('tracking_id'),
                        'STATUS'       => $id.$dataPickupFail[Input::get('comments')]['reason_id'],
                        'CITY'         => 'Ha Noi',
                        'NOTE'         => $note,
                        'ERROR_CODE'   => '',
                        'Weight'       => '',
                        'MABUUCUC'     => ''
                    ),
                    'TrackingOrder' => Input::get('tracking_id'),
                    'TrackingCode'  => Input::get('tracking_id'),
                    'Note'          => $note,
                    'City'          => 'Ha Noi',
                    'Weight'        => '',
                    'Status'        => $id.$dataPickupFail[Input::get('comments')]['reason_id']
                );
                break;
            case '107'://thay doi kich thuoc
                $dataInput = array(
                    'username' => 'njv',
                    'password' => '',
                    'function' => '',
                    'params'   => array(
                        'SC_CODE'      => Input::get('tracking_id'),
                        'STATUS'       => $id,
                        'CITY'         => 'Ha Noi',
                        'NOTE'         => $id.' / Đã được thay đổi kích thước',
                        'ERROR_CODE'   => '',
                        'Weight'       => '',
                        'MABUUCUC'     => ''
                    ),
                    'TrackingOrder' => Input::get('tracking_id'),
                    'TrackingCode'  => Input::get('tracking_id'),
                    'Note'          => $id.' / Đã được thay đổi kích thước',
                    'City'          => 'Ha Noi',
                    'Weight'        => '',
                    'Status'        => $id
                );
                break;
            case '200'://lay thanh cong
                $dataInput = array(
                    'username' => 'njv',
                    'password' => '',
                    'function' => '',
                    'params'   => array(
                        'SC_CODE'      => Input::get('tracking_id'),
                        'STATUS'       => $id,
                        'CITY'         => 'Ha Noi',
                        'NOTE'         => $id.' / Đã lấy hàng',
                        'ERROR_CODE'   => '',
                        'Weight'       => '',
                        'MABUUCUC'     => ''
                    ),
                    'TrackingOrder' => Input::get('tracking_id'),
                    'TrackingCode'  => Input::get('tracking_id'),
                    'Note'          => $id.' / Đã lấy hàng',
                    'City'          => 'Ha Noi',
                    'Weight'        => '',
                    'Status'        => $id
                );
                break;
            case '300'://san sang phat hang
                $dataPod = Input::get('pod');
                $dataInput = array(
                    'username' => 'njv',
                    'password' => '',
                    'function' => '',
                    'params'   => array(
                        'SC_CODE'      => Input::get('tracking_id'),
                        'STATUS'       => $id,
                        'CITY'         => 'Ha Noi',
                        'NOTE'         => $id.' / Đang giao hàng '.$dataPod['name'].' - '.$dataPod['uri'],
                        'ERROR_CODE'   => '',
                        'Weight'       => '',
                        'MABUUCUC'     => ''
                    ),
                    'TrackingOrder' => Input::get('tracking_id'),
                    'TrackingCode'  => Input::get('tracking_id'),
                    'Note'          => $id.' / Đang giao hàng '.$dataPod['name'].' - '.$dataPod['uri'],
                    'City'          => 'Ha Noi',
                    'Weight'        => '',
                    'Status'        => $id
                );
                break;
            case '400'://huy don hang
                $dataInput = array(
                    'username' => 'njv',
                    'password' => '',
                    'function' => '',
                    'params'   => array(
                        'SC_CODE'      => Input::get('tracking_id'),
                        'STATUS'       => $id,
                        'CITY'         => 'Ha Noi',
                        'NOTE'         => $id.' / Huỷ đơn hàng từ HVC',
                        'ERROR_CODE'   => '',
                        'Weight'       => '',
                        'MABUUCUC'     => ''
                    ),
                    'TrackingOrder' => Input::get('tracking_id'),
                    'TrackingCode'  => Input::get('tracking_id'),
                    'Note'          => $id.' / Huỷ đơn hàng từ HVC',
                    'City'          => 'Ha Noi',
                    'Weight'        => '',
                    'Status'        => $id
                );
                break;
            case '401'://phat khong thanh cong, hen phat lai
                $dataInput = array(
                    'username' => 'njv',
                    'password' => '',
                    'function' => '',
                    'params'   => array(
                        'SC_CODE'      => Input::get('tracking_id'),
                        'STATUS'       => $id,
                        'CITY'         => 'Ha Noi',
                        'NOTE'         => $id.' / Phát không thành công, hẹn lịch phát lại',
                        'ERROR_CODE'   => '',
                        'Weight'       => '',
                        'MABUUCUC'     => ''
                    ),
                    'TrackingOrder' => Input::get('tracking_id'),
                    'TrackingCode'  => Input::get('tracking_id'),
                    'Note'          => $id.' / Phát không thành công, hẹn lịch phát lại',
                    'City'          => 'Ha Noi',
                    'Weight'        => '',
                    'Status'        => $id
                );
                break;
            case '500'://hoan hang
                $note = $dataDeliveryFail[Input::get('comments')]['reason'];
                $dataInput = array(
                    'username' => 'njv',
                    'password' => '',
                    'function' => '',
                    'params'   => array(
                        'SC_CODE'      => Input::get('tracking_id'),
                        'STATUS'       => $id.$dataDeliveryFail[Input::get('comments')]['reason_id'],
                        'CITY'         => 'Ha Noi',
                        'NOTE'         => $note,
                        'ERROR_CODE'   => '',
                        'Weight'       => '',
                        'MABUUCUC'     => ''
                    ),
                    'TrackingOrder' => Input::get('tracking_id'),
                    'TrackingCode'  => Input::get('tracking_id'),
                    'Note'          => $note,
                    'City'          => 'Ha Noi',
                    'Weight'        => '',
                    'Status'        => $id.$dataDeliveryFail[Input::get('comments')]['reason_id']
                );
                break;
            case '600'://phat thanh cong
                $dataInput = array(
                    'username' => 'njv',
                    'password' => '',
                    'function' => '',
                    'params'   => array(
                        'SC_CODE'      => Input::get('tracking_id'),
                        'STATUS'       => $id,
                        'CITY'         => 'Ha Noi',
                        'NOTE'         => $id.' / Phát thành công',
                        'ERROR_CODE'   => '',
                        'Weight'       => '',
                        'MABUUCUC'     => ''
                    ),
                    'TrackingOrder' => Input::get('tracking_id'),
                    'TrackingCode'  => Input::get('tracking_id'),
                    'Note'          => $id.' / Phát thành công',
                    'City'          => 'Ha Noi',
                    'Weight'        => '',
                    'Status'        => $id
                );
                break;
            default :
                $dataInput = array(
                    'username' => 'njv',
                    'password' => '',
                    'function' => '',
                    'params'   => array(
                        'SC_CODE'      => Input::get('tracking_id'),
                        'STATUS'       => $id,
                        'CITY'         => 'Ha Noi',
                        'NOTE'         => $id,
                        'ERROR_CODE'   => '',
                        'Weight'       => '',
                        'MABUUCUC'     => ''
                    ),
                    'TrackingOrder' => Input::get('tracking_id'),
                    'TrackingCode'  => Input::get('tracking_id'),
                    'Note'          => $id,
                    'City'          => 'Ha Noi',
                    'Weight'        => '',
                    'Status'        => $id
                );
                break;
        }

        //ghi vao log hanh trinh
        $LMongo = new LMongo;
        $idLog  = $LMongo::collection('log_journey_lading')
                                    ->insert(array(
                                                'tracking_code'     => Input::get('tracking_id'),
                                                'tracking_number'   => (int)Input::get('tracking_ref_no'),
                                                'input'         => $dataInput,
                                                "priority"      => 2,
                                                'accept'        => 0,
                                                'time_create'   => $this->time(),
                                            ));

        //check exist lading
        $this->_checkTrackingCodeNjv();
        if(!$this->__Lading){
            return Response::json(array('error' => 301, 'error_message' => 'Không tồn tại vận đơn'));
        }

        if($idLog){
            $LMongo::collection('log_journey_lading')->where('_id', new \MongoId($idLog))->update(array(
                                                'courier'       => (int)$this->__Lading['courier_id'],
                                                'domain'        => $this->__Lading['domain'],
                                                'status'        => (int)$id,
                                                //'address'       => ['city' => Input::get('City'), 'province' => Input::get('Province')],                                                
                                                'note'          => Input::get('comments') ? Input::get('comments') : '',
                                                'weight'        => Input::has('Weight') ? (int)Input::get('Weight') : 0,
                                                'collect'       => Input::has('Collect') ? (int)Input::get('Collect') : 0,
                                                'time_update'   => $this->time(),
                                            ));

           // Call Predis
            if($id != 600){ // Trạng thái cuối không cho xử lý bằng rabit tránh tình trạng đẩy đồng thời
                try{
                    $this->RabbitJourney($idLog);
                } catch(\Exception $e){

                }
            }

            return Response::json(array(
                'error'         => 200, 
                'error_message' => 'success',
                'data'          => array(
                                    'LadingCode'    => Input::get('tracking_id'),
                                    'Status'        => (int)$id,
                                )
                ));
        }else{
            return Response::json(array(
                'error'         => 301, 
                'error_message' => 'fail',
                'data'          => array(
                                    'LadingCode'    => Input::get('tracking_id'),
                                    'Status'        => (int)$id,
                                )
                ));
        } 
    }



}