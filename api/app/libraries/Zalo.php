<?php
	use exception\ZaloSdkException;
	use service\ZaloServiceFactory;
	use service\ZaloServiceConfigure;
	use service\onbehalf\ZaloOnBehalfServiceFactory;
	use service\onbehalf\ZaloOnBehalfServiceConfigure;
	require_once 'zalo/src/service/ZaloServiceFactory.php';
	require_once 'zalo/src/service/ZaloMessageService.php';
	require_once 'zalo/src/service/ZaloQueryService.php';
	require_once 'zalo/src/service/ZaloUploadService.php';
	require_once 'zalo/src/service/ZaloSocialService.php';
	require_once 'zalo/src/service/ZaloFanService.php';
	require_once 'zalo/src/service/ZaloServiceConfigure.php';
	require_once 'zalo/src/service/onbehalf/ZaloOnBehalfServiceFactory.php';
	require_once 'zalo/src/service/onbehalf/ZaloOnBehalfServiceConfigure.php';
	require_once 'zalo/src/service/onbehalf/ZaloOnBehalfMessageService.php';
	require_once 'zalo/src/exception/ZaloSdkException.php';
	
	/**
	* 
	*/
	class Zalo
	{
		private $factory;
		private $messageService;
		
		public function __construct()
		{
			$this->configure = new ZaloServiceConfigure(3662493445123912059, 'JPMubK7itn43G2J3FDYv');
		    $this->factory = $this->configure->getZaloServiceFactory();
		    $this->messageService = $this->factory->getZaloMessageService();
		}

		/**
		* send
		*/

		public function send($data = array()){
			$smsMsg = "Shipchung.vn";
			if(empty($data)){
                echo 'Khong co du lieu gui';die;
			}
			try{
				$send = $this->messageService->sendTemplateTextMessageByPhoneNum($data['phone'], $data['templateId'],$data['data'], $smsMsg, true);
				$err = $send->getError();
				return array('err' => $err,'msg' => '');
			}catch(ZaloSdkException $ex) {
				return array('err' => $ex->getZaloSdkExceptionErrorCode(),'msg' => $ex->getZaloSdkExceptionMessage());
			}
		}
		/**
		* send not template
		**/
		public function sendnottemplate(){
			$smsMsg = "Shipchung.vn";
			$send = $this->messageService->sendTextMessageByPhoneNum('84976395263', $smsMsg,'test thoi',true);
			$err = $send->getError();
			return array('err' => $err,'msg' => '');
		}

		/**
		* Buyer
		*/

		//giao hang thanh cong
		public function successDelivery($data = array()){
			if(empty($data)){
                echo 'Khong co du lieu gui';die;
			}
			$templateId = "2c6d71874dc2a49cfdd3";
			$phone = (int)$data['phone'];
			$smsMsg = "Shipchung.vn";
			try{
				$send = $this->messageService->sendTemplateTextMessageByPhoneNum($phone, $templateId,array('tracking_code' => $data['tracking_code'],'item_name' => $data['item_name'],'customer_name' => $data['customer_name']), $smsMsg, true);
				$err = $send->getError();
				var_dump($err);
			}catch(ZaloSdkException $ex) {
			    echo("</br></br>Err = " . $ex->getZaloSdkExceptionErrorCode());
			    echo("</br></br>Mes = " . $ex->getZaloSdkExceptionMessage());
			}catch(Exception $ex) {
			    echo("</br></br>Message = " + $ex->getMessage());
			}
		}

		//Giao di phat
		public function delivering($data = array()){
			if(empty($data)){
                echo 'Khong co du lieu gui';die;
			}
			$templateId = "48a6164c2a09c3579a18";
			$phone = (int)$data['phone'];
			$smsMsg = "Shipchung.vn";
			try{
				$send = $this->messageService->sendTemplateTextMessageByPhoneNum($phone, $templateId,array('tracking_code' => $data['tracking_code'],'item_name' => $data['item_name'],'deliver_phone_num' => $data['phone_postman']), $smsMsg, true);
				$err = $send->getError();
				var_dump($err);
			}catch(ZaloSdkException $ex) {
			    echo("</br></br>Err = " . $ex->getZaloSdkExceptionErrorCode());
			    echo("</br></br>Mes = " . $ex->getZaloSdkExceptionMessage());
			}catch(Exception $ex) {
			    echo("</br></br>Message = " + $ex->getMessage());
			}
		}

		//Giao that bai
		public function deliveryFail($data = array()){
			if(empty($data)){
                echo 'Khong co du lieu gui';die;
			}
			$templateId = "98b7c75dfb1812464b09";
			$phone = (int)$data['phone'];
			$smsMsg = "Shipchung.vn";
			try{
				$send = $this->messageService->sendTemplateTextMessageByPhoneNum($phone, $templateId,array('tracking_code' => $data['tracking_code'],'item_name' => $data['item_name'],'deliver_phone_num' => $data['phone_postman']), $smsMsg, true);
				$err = $send->getError();
				var_dump($err);
			}catch(ZaloSdkException $ex) {
			    echo("</br></br>Err = " . $ex->getZaloSdkExceptionErrorCode());
			    echo("</br></br>Mes = " . $ex->getZaloSdkExceptionMessage());
			}catch(Exception $ex) {
			    echo("</br></br>Message = " + $ex->getMessage());
			}
		}

		//Giao cham
		public function deliveryLate($data = array()){
			if(empty($data)){
                echo 'Khong co du lieu gui';die;
			}
			$templateId = "86a2de48e20d0b53521c";
			$phone = (int)$data['phone'];
			$smsMsg = "Shipchung.vn";
			try{
				$send = $this->messageService->sendTemplateTextMessageByPhoneNum($phone, $templateId,array('tracking_code' => $data['tracking_code'],'item_name' => $data['item_name'],'delay_days' => $data['delay_days'],'post_office_name' => $data['post_office_name'],'post_office_phone' => $data['post_office_phone']), $smsMsg, true);
				$err = $send->getError();
				var_dump($err);
			}catch(ZaloSdkException $ex) {
			    echo("</br></br>Err = " . $ex->getZaloSdkExceptionErrorCode());
			    echo("</br></br>Mes = " . $ex->getZaloSdkExceptionMessage());
			}catch(Exception $ex) {
			    echo("</br></br>Message = " + $ex->getMessage());
			}
		}

		//Da lay hang
		public function successPickup($data = array()){
			if(empty($data)){
                echo 'Khong co du lieu gui';die;
			}
			$templateId = "f293ab79973c7e62272d";
			$phone = (int)$data['phone'];
			$smsMsg = "Shipchung.vn";
			try{
				$send = $this->messageService->sendTemplateTextMessageByPhoneNum($phone, $templateId,array('tracking_code' => $data['tracking_code'],'item_name' => $data['item_name'],'customer_name' => $data['customer_name'],'remain_days' => $data['remain_days'],'cod_amount' => $data['cod_amount']), $smsMsg, true);
				$err = $send->getError();
				var_dump($err);
			}catch(ZaloSdkException $ex) {
			    echo("</br></br>Err = " . $ex->getZaloSdkExceptionErrorCode());
			    echo("</br></br>Mes = " . $ex->getZaloSdkExceptionMessage());
			}catch(Exception $ex) {
			    echo("</br></br>Message = " + $ex->getMessage());
			}
		}

		/**
		* Seller
		*/

		//dang ky  moi
		public function newRegis($phone){
			if(empty($phone)){
                echo 'Khong co du lieu gui';die;
			}
			$templateId = "209f5c756030896ed021";
			$phone = (int)$phone;
			$smsMsg = "Shipchung.vn";
			try{
				$send = $this->messageService->sendTemplateTextMessageByPhoneNum($phone, $templateId, array('tracking_code' => "SC4353452"), $smsMsg, true);
				$err = $send->getError();
				return $err;
			}catch(ZaloSdkException $ex) {
			    return $ex->getZaloSdkExceptionErrorCode();
			}
		}

		//Don dau tien sau 2 gio chua duyet
		public function notAccept($data = array()){
			if(empty($data)){
                echo 'Khong co du lieu gui';die;
			}
			$templateId = "8de4f00ecc4b25157c5a";
			$phone = (int)$data['phone'];
			$smsMsg = "Shipchung.vn";
			try{
				$send = $this->messageService->sendTemplateTextMessageByPhoneNum($phone, $templateId,array('tracking_code' => $data['tracking_code']), $smsMsg, true);
				$err = $send->getError();
				return $err;
			}catch(ZaloSdkException $ex) {
				$err = $ex->getZaloSdkExceptionErrorCode();
				return array('err' => $ex->getZaloSdkExceptionErrorCode(),'msg' => $ex->getZaloSdkExceptionMessage());
			}
		}

		//Duyet don dau tien
		public function acceptFirst($data = array()){
			if(empty($data)){
                echo 'Khong co du lieu gui';die;
			}
			$templateId = "5bfa25101955f00ba944";
			$phone = (int)$data['phone'];
			$smsMsg = "Shipchung.vn";
			try{
				$send = $this->messageService->sendTemplateTextMessageByPhoneNum($phone, $templateId,array('tracking_code' => $data['tracking_code'],'post_office_name' => 'tổng đài CSKH','post_office_phone' => 1900636030), $smsMsg, true);
				$err = $send->getError();
				return $err;
			}catch(ZaloSdkException $ex) {
			    return $ex->getZaloSdkExceptionErrorCode();
			}
		}

		//Giao don dau tien thanh cong
		public function deliveredFirst($data = array()){
			if(empty($data)){
                echo 'Khong co du lieu gui';die;
			}
			$templateId = "c1bb9451a814414a1805";
			$phone = (int)$data['phone'];
			$smsMsg = "Shipchung.vn";
			try{
				$send = $this->messageService->sendTemplateTextMessageByPhoneNum($phone, $templateId,array('tracking_code' => $data['tracking_code']), $smsMsg, true);
				$err = $send->getError();
				return $err;
			}catch(ZaloSdkException $ex) {
			    return array('err' => $ex->getZaloSdkExceptionErrorCode(),'msg' => $ex->getZaloSdkExceptionMessage());
			}
		}

		function sendT(){
			try{
			    $phone = 84976395263;
			    $smsMsg = "[WTE] Test new codebase";
			    $templateId = "f648b5a289e760b939f6";
			    
			    $test = $this->messageService->sendTemplateTextMessageByPhoneNum($phone, $templateId,array('tracking_code' => "SC4353452",'product_name' => "phone",'estimate_time' => "1 day"), $smsMsg, true);
			    $a = $test->getError();
			    //var_dump($a);
			}catch (ZaloSdkException $ex) {
			    echo("</br></br>Err = " . $ex->getZaloSdkExceptionErrorCode());
			    echo("</br></br>Mes = " . $ex->getZaloSdkExceptionMessage());
			} catch (Exception $ex) {
			    echo("</br></br>Message = " + $ex->getMessage());
			}
		}
	}
?>