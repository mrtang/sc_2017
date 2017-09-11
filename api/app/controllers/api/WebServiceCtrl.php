<?php

class WebServiceCtrl extends BaseController {
    function getIndex(){
        echo 'abc :)';
    }

	// get checkout merchant info
	public function getCheckout(){
		$checkoutId = Input::get('checkoutId');

		LMongo::connection();
		
		if($checkoutId){
			$checkout = LMongo::collection('log_checkout_merchant')->where('_id', new MongoId($checkoutId))->first();
			if($checkout){
                $merchantInfo = ApiKeyModel::checkMerchantToken($checkout['MerchantKey']);
                if(!empty($merchantInfo)){
                    $userIntegrate = sellermodel\UserIntegrateModel::getUserIntegrate($merchantInfo->user_id);
                }
                if(!empty($userIntegrate)){
                    $checkout['IntegrateConfig'] = $userIntegrate;
                }
				return Response::json(array("error" => false, "message"=> "success", "data"=> $checkout));
			}else {
				return Response::json(array("error" => true, "message"=> $checkoutId, "data"=> array()));
			}
		}else {
			return 'Token not found';
		}
	}


    

    public function getReturnUrl(){
        $checkoutId     = Input::has('id')              ? Input::get('id')              : 0;
        $tracking_code  = Input::has('tracking_code')   ? Input::get('tracking_code')   : 0;
        $method         = Input::has('method')          ? Input::get('method')          : 0;
        
        LMongo::connection();
        if($checkoutId){
            $checkout = LMongo::collection('log_checkout_merchant')->where('_id', new MongoId($checkoutId))->first();
            if($checkout){
                if($checkout['ReturnUrl']){
                    $_returnUrl = explode("?", $checkout['ReturnUrl']);
                    $retUrl = $checkout['ReturnUrl'];
                    
                    if(sizeof($_returnUrl) > 1){
                        $retUrl .= '&';
                    }else {
                        $retUrl .= '?';
                    }
                    if($method == 'cod'){
                        $method = 'CoD';
                    }else {
                        $method = 'PaS';
                    }
                    $retUrl .= 'tracking_code='.$tracking_code . '&payment_type='.$method;

                    return Response::json(array("error" => false, "message"=> "success",  'data' => $retUrl));
                };
                return Response::json(array("error" => false, "message"=> "no return url", 'data'=> []));
            }else {
                return Response::json(array("error" => true, "message"=> $checkoutId, "data"=> array()));
            }
        }else {
            return Response::json(array("error" => true, "message"=> 'Token not found', "data"=> array()));
        }
    }
    public function getHandlerreturnurl (){


        $checkoutId     = Input::has('id')              ? Input::get('id')              : 0;
        $tracking_code  = Input::has('tracking_code')   ? Input::get('tracking_code')   : 0;
        $method         = Input::has('method')          ? Input::get('method')          : 0;
        

        LMongo::connection();
        if($checkoutId){
            
            $checkout = LMongo::collection('log_checkout_merchant')->where('_id', new MongoId($checkoutId))->first();
            if($checkout){
                if($checkout['ReturnUrl']){

                    $_returnUrl = explode("?", $checkout['ReturnUrl']);
                    $retUrl = $checkout['ReturnUrl'];
                    
                    if(sizeof($_returnUrl) > 1){
                        $retUrl .= '&';
                    }else {
                        $retUrl .= '?';
                    }
                    if($method == 'cod'){
                        $method = 'CoD';
                    }else {
                        $method = 'PaS';
                    }
                    $retUrl .= 'tracking_code='.$tracking_code . '&payment_type='.$method;

                    $content = file_get_contents($retUrl);
                    return Response::json(array("error" => false, "message"=> "success", "data"=> $content, 'url' => $retUrl));
                };
                return Response::json(array("error" => false, "message"=> "success", "data"=> $checkout));
            }else {
                return Response::json(array("error" => true, "message"=> $checkoutId, "data"=> array()));
            }
        }else {
            return 'Token not found';
        }
    }
	
	public function postCheckout(){
		LMongo::connection();
		$validate = Validator::make(Input::all(), array(
			"MerchantKey" 	=> "required",
			"Item" 			=> "required",
            //"ReturnUrl"     => "required",
			"Order" 		=> "required"
		));

		$merchantInfo = ApiKeyModel::checkMerchantToken(Input::get('MerchantKey'));
		if(!empty($merchantInfo)){
            $hasNLAccount  = sellermodel\UserInfoModel::checkNLAccount($merchantInfo->user_id);
            $hasBanking    = sellermodel\BankingModel::hasBanking($merchantInfo->user_id);
            $userIntegrate = sellermodel\UserIntegrateModel::getUserIntegrate($merchantInfo->user_id);
            $FeeConfig     = sellermodel\FeeModel::getConfig($merchantInfo->user_id);

            if ($userIntegrate) {
                $userIntegrate = json_decode(json_encode($userIntegrate));
            }
            
			if($validate->fails()){
				return Response::json(array("error" => true, "message"=> $validate->messages(), "data"=> array()));
			}
            
			$saveData = array(
                "MerchantKey"     => Input::get('MerchantKey'),
                "Order"           => Input::get('Order'),
                "Item"            => Input::get('Item'),
                "Config"          => array(
                    'Payment'     => (!empty($FeeConfig) && $FeeConfig == 3) ? 1 : 2
                ),
                "Domain"          => Input::get('Domain'),
                "UserIp"          => Input::get('UserIp') ? Input::get('UserIp') : '0.0.0.0' , 
                "ReceiverEmail"   => (isset($hasNLAccount['email_nl'])) ? $hasNLAccount['email_nl'] : "",
                "hasBanking"      => $hasBanking,
                "IntegrateConfig" => !empty($userIntegrate) ? $userIntegrate : false,
                "BrowserInfo"     => (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : Input::get('BrowserInfo'),
                "ReturnUrl"       => Input::has('ReturnUrl') ? Input::get('ReturnUrl') : null,
                "TimeCreate"      => $this->time()
			);

			$query  = LMongo::collection('log_checkout_merchant')->insert($saveData);

			if(!$query){
				return Response::json(array("error" => true, "message"=> "false", "data"=> array()));
			}else {
				return Response::json(array("error" => false, "message"=> "success", "data"=> array(
                    // Link popup
					//"SCFrameUrl"=> 'http://10.0.1.247:8000/#/process?version=2&id='.$query
                    "SCFrameUrl"=> '//services.shipchung.vn/sdk/popup/index.html#/process?version=2&id='.$query
				)));
			}
            //
		}
        else {
			return Response::json(array(
				"error" 	=> true,
				"message"	=> "TOKEN_NOT_FOUND",
				"data"		=> array()
			));
		}
	}
    
    function VerifyNganLuong(){
        $validate = Validator::make(Input::all(), array(
			"Token"              => "required",
			"OrderCode"          => "required"
		));
        
        if($validate->fails()){
			return Response::json(array("error" => 'invalid', "message"=> $validate->messages(), "data"=> null));
		}
                
        echo '<script>window.close();</script>';
    }
    
    function VerifyNganLuong__(){
        $validate = Validator::make(Input::all(), array(
			"Token"              => "required",
			"OrderCode"          => "required"
		));
        
        if($validate->fails()){
			return Response::json(array("error" => 'invalid', "message"=> $validate->messages(), "data"=> null));
		}
        
        $dbTrans = sellermodel\TransactionNLmodel::where('token',Input::get('Token'))
                                        ->where('tracking_code',Input::get('OrderCode'))
                                        ->where('time_due','>=',$this->time())
                                        ->first(['status','id','time_success','transaction_code'])->toArray();

        if(!$dbTrans){
            return Response::json(array("error" => 'error Transaction', "message"=> 'Không tồn tại giao dịch hoặc Giao dịch đã hết hạn xử lý', "data"=> null));
        }

        if($dbTrans['status'] === 'SUCCESS'){
            return Response::json(array("error" => 'success', "message"=> 'Giao dịch thành công', "data"=> array('TrackingCode' => Input::get('OrderCode'),'TimeSuccess' => $dbTrans['time_success'])));
        }
        
        if($dbTrans['status'] !== 'PENDING'){
            return Response::json(array("error" => 'error Transaction', "message"=> 'Giao dịch lỗi, không thể tiếp tục xử lý', "data"=> null));
        }
        
        $params = array(
            'merchant_id'       => Config::get('constants.MERCHANT_ID_SC'),
            'merchant_password' => Config::get('constants.MERCHANT_PASS_SC'),
            'version'           => '3.1',
            'function'          => 'GetTransactionDetail',
            'token'             => $dbTrans['transaction_code'],
        );

        $xml_result             = preg_replace('#&(?=[a-z_0-9]+=)#', '&amp;',(string)\cURL::post(Config::get('config_api.API_POST_NL'),$params));
        $nl_result              = simplexml_load_string($xml_result);

        $nl_errorcode           = (string)$nl_result->error_code;
        $nl_transaction_status  = (string)$nl_result->transaction_status;
        $nl_transaction_id      = (string)$nl_result->transaction_id;
        $nl_token               = (string)$nl_result->token;
        $nl_payment_type        = (string)$nl_result->payment_type;
        
        if($nl_errorcode != '00' || in_array($nl_transaction_status,array('00','01'))){
            sellermodel\TransactionNLmodel::where('id',$dbTrans['id'])->update(['status' => 'ERROR','respond_verify' => json_encode($nl_result),'time_update' => $this->time()]);
            return Response::json(array("error" => 'error NganLuong', "message"=> 'Lỗi xử lý Ngân Lượng - '.$nl_errorcode, "data"=> null));
        }
        
        if(in_array($nl_transaction_status,array('00','01'))){
            sellermodel\TransactionNLmodel::where('id',$dbTrans['id'])->update(['status' => 'SUCCESS','respond_verify' => json_encode($nl_result),'time_update' => $this->time(),'time_success' => $this->time()]);
            //return Response::json(array("error" => 'success', "message"=> 'Giao dịch thành công', "data"=> array( 'TrackingCode' => Input::get('OrderCode'),'TimeSuccess' => $this->time() )));
            echo "<script>window.close();</script>";
        }
        
    }
    
    public function postCheckoutv1(){
        
        LMongo::connection();
        $validate = Validator::make(Input::all(), array(
            "sc_merchant_token"     => "required",
            "params"                => "required"
        ));
        
        $merchantInfo = ApiKeyModel::checkMerchantToken(Input::get('sc_merchant_token'));
        
        if(!empty($merchantInfo)){
            $hasNLAccount  = sellermodel\UserInfoModel::checkNLAccount($merchantInfo->user_id);
            $hasBanking    = sellermodel\BankingModel::hasBanking($merchantInfo->user_id);
            $FeeConfig     = sellermodel\FeeModel::getConfig($merchantInfo->user_id);
            if($validate->fails()){
                return Response::json(array(
                        "error"  => true,
                        "message" => $validate->messages(),
                        "data"  => array()
                    )
                );
            } 
            else 
            {
                $_params = Input::get('params');
                
                $Order = array(
                    "ProductName"   => "",
                    "Weight"        => $_params['weight'],
                    "Amount"        => $_params['amount'],
                    "Quantity"      =>  0
                );
                

                // Modify by ThinhNV 25/2/15  (fix name of order on v1)

                $Items          = array();
                $OrderNameArr   = array();

                foreach($_params['items'] as $value){
                    $Items[] = array(
                        'Name'      => $value['item_name'],
                        'Quantity'  => $value['item_quantity'],
                        'Price'     => $value['item_amount'],
                        'Image'     => isset($value['item_image']) ? $value['item_image'] : null,
                        'Link'      => isset($value['item_url']) ? $value['item_url'] : null,
                        'Weight'    => $value['item_weight']
                    );
                    $OrderNameArr[] = $value['item_name'];
                    $Order['Quantity'] += (int)$value['item_quantity'];
                }
                
                $Order['ProductName'] = implode(', ', $OrderNameArr);

                // End modify 

                $ConfigPayment = 2;
                if((Input::has('params') && isset(Input::get('params')['free_shipping']) && Input::get('params')['free_shipping'] == 1)){
                    $ConfigPayment = 1;
                }else if($FeeConfig == 3){
                    $ConfigPayment = 1;
                }

                $saveData = array(
                    "MerchantKey"   => Input::get('sc_merchant_token'),
                    "Order"         => $Order,
                    "Item"          => $Items,
                    "Config"        => array(
                        'Payment'   => $ConfigPayment
                    ),
                    "Domain"        => Input::get('Domain') ? Input::get('Domain') : null ,
                    "UserIp"        => Input::get('UserIp') ? Input::get('UserIp') : '0.0.0.0' ,
                    "ReceiverEmail" => (isset($hasNLAccount['email_nl'])) ? $hasNLAccount['email_nl'] : "",
                    "hasBanking"    => $hasBanking,
                    "BrowserInfo"   => (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : Input::get('BrowserInfo'),
                    "ReturnUrl"     => $_params['return_url'] ? $_params['return_url'] : null,
                    "TimeCreate"    => $this->time()
                );
            
                $query  = LMongo::collection('log_checkout_merchant')->insert($saveData);
            
                if(!$query){
                    return Response::json(array("error" => true, "message"=> "false", "data"=> array()));
                }
                else 
                {
                
                    return Response::json(array("error" => false, "message"=> "success", "data"=> array(
                        //"SCFrameUrl"=> 'http://10.0.1.199:8001/#/process?version=2&id='.$query
                        'FeeConfig'=> $FeeConfig,
                        "SCFrameUrl"=> '//services.shipchung.vn/sdk/popup/index.html#/process?version=2&id='.$query
                    )));
                }
            }
        
        }
        else {
            return Response::json(array(
                "error"  => true,
                "message" => "TOKEN_NOT_FOUND",
                "data"  => array()
            ));
        }
    }

    public function postBoxOrder(){
        $validate = Validator::make(Input::all(), array(
            "box"       => "required",
            "buyer"     => "required"
        ));

        if($validate->fails()){
            return Response::json(array("error" => true, "message"=> $validate->messages(), "data"=> null));
        }
        $saveData = array(
            'box_type'          => Input::get('box')['model'],   
            'box_w'             => Input::get('box')['w'],     
            'box_l'             => Input::get('box')['l'],     
            'box_h'             => Input::get('box')['h'],     
            'logo_src'          => Input::get('box')['file'],
            'logo_type'         => Input::get('box')['logo'],
            'buyer_fullname'    => Input::get('buyer')['name'],
            'buyer_city'        => Input::get('buyer')['city'], 
            'buyer_district'    => Input::get('buyer')['district'],
            'buyer_address'     => Input::get('buyer')['address'],
            'buyer_phone'       => Input::get('buyer')['phone'],
            'buyer_email'       => Input::get('buyer')['email'],
            'quantity'          => Input::get('box')['qty'],
            'create_time'       => $this->time(),
            'amount'            => '2000000',
            'status'            => 'WAITING',
        );
        
        $Model = new sellermodel\BoxOrderModel($saveData);
        try {
            $Model->save();
        } catch (Exception $e) {
            return Response::json(array("error" => true, "message"=> "Lỗi kết nối, vui lòng thử lại sau 1!", "data"=> null));
        }
        $refer_code = 'SC_BOX_'.$Model->id.'_'.$this->time();
        $transaction_url = $this->_createNLTransaction($Model, $refer_code);
        if($transaction_url){
            $Model->refer_code = $refer_code;
            try {
                $Model->save();
            } catch (Exception $e) {
                return Response::json(array("error" => true, "message"=> "Lỗi kết nối, vui lòng thử lại sau 2!", "data"=> null));
            }
            $Model->transaction_url = $transaction_url;
        }else {
            return Response::json(array("error" => true, "message"=> "Lỗi kết nối, vui lòng thử lại sau 3!", "data"=> null));
        }

        
        return Response::json(array("error" => true, "message"=> "Thành công", "data"=> $Model));
    }
    private function _createNLTransaction($Order, $refer_code){
        
        $OrderCode  = $refer_code;
        

        $params = array(
            'merchant_id'       => Config::get('constants.MERCHANT_ID_SC'),
            'merchant_password' => Config::get('constants.MERCHANT_PASS_SC'),
            'version'           => '3.1',
            'function'          => 'SetExpressCheckout',
            'receiver_email'    => Config::get('constants.RECEIVER_EMAIL'),
            'order_code'        => $OrderCode,
            'total_amount'      => $Order->amount,
            'payment_method'    => 'NL',
            'order_description' => 'Thanh toán đơn hàng hộp loại '.$Order->box_type.' với size '.$Order->box_l.'x'.$Order->box_w.'x'.$Order->box_h.'cm, kiểu logo : '.$Order->logo_type,
            'fee_shipping'      => 0,
            'return_url'        => Config::get('constants.LINK_SELLER').'order_box/'.$OrderCode,
            'buyer_fullname'    => $Order->buyer_fullname,
            'buyer_email'       => $Order->buyer_email,
            'buyer_mobile'      => $Order->buyer_phone,
            'buyer_address'     => '',
            'total_item'        => 1,
            'item_name1'        => 'Thanh toán đơn hàng hộp loại '.$Order->box_type.' với size '.$Order->box_l.'x'.$Order->box_w.'x'.$Order->box_h.'cm, kiểu logo : '.$Order->logo_type,
            'item_amount1'      => $Order->amount,
            'item_quantity1'    => 1,
            'item_weight1'    => 0
        );

        $xml_result =  preg_replace('#&(?=[a-z_0-9]+=)#', '&amp;',(string)\cURL::post(Config::get('config_api.API_POST_NL'),$params));
        $nl_result  = simplexml_load_string($xml_result);
        $nl_errorcode       = (string)$nl_result->error_code;
        $nl_checkout_url    = (string)$nl_result->checkout_url;
        $nl_token           = (string)$nl_result->token;
        $nl_time_limit      = (string)$nl_result->time_limit;
        $nl_description     = (string)$nl_result->description;


        if($nl_errorcode == '00'){
            return $nl_checkout_url;
        }

        return false;
    }

    public function postUploadLogo(){
        if (Input::hasFile('file')) {
            $File       = Input::file('file');
            $name       = $File->getClientOriginalName();
            $extension  = $File->getClientOriginalExtension();
            $MimeType   = $File->getMimeType();
            $name       = md5($this->time()).$name;
            if(in_array((string)$extension, array('jpg','png','ai', 'eps', 'pdf'))){
                $uploadPath = storage_path(). DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'logo';
                $File->move($uploadPath, $name);
                $fullpath = DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'logo'. DIRECTORY_SEPARATOR .$name;
                return Response::json(array(
                    'error'         => false,
                    'error_message' => 'Tải lên thành công',
                    'data'          => $fullpath
                ));
            }else {
                return Response::json(array(
                    'error'         => true,
                    'error_message' => 'Định dạng file không đúng.',
                    'data'          => ''
                ));
            }
        }
        return Response::json(array(
            'error'         => true,
            'error_message' => 'Tải lên không thành công.',
            'data'          => ''
        ));
    }
}
