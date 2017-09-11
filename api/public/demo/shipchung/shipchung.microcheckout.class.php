<?php
	/**
	 * Class SC_MicroCheckout
	 *
	 * Su dung khi tich hop thanh toan voi ShipChung.vn
	 *
     * @author  KienNT
	 * @version SC 1.1
	 * @since   2012
	 */
    //ini_set('arg_separator.output','&');
	class SC_MicroCheckout
	{
		//private $urlPOST  = 'http://api.shipchung.vn/shipchung_checkout_curl_v1.1.php'; // không thay đổi khai báo này
        private $urlPOST  = 'http://services.shipchung.vn/popup/checkoutv1';
		public $sc_merchant_id      = '266594';// Merchant ID do ShipChung cung cấp
        public $sc_merchant_token   = 'e80d1bb5cde172364fdd6c338b8966ac';// Merchant Token do ShipChung cung cấp
        public $nl_merchant_site    = '34986';// Merchant Site đăng ký tại ngân lượng
        public $nl_merchant_pass    = 'canon####@@@123';// Merchant Password khai báo tại ngân lượng
        public $nl_email_recive     = 'cmv@canon.com.vn';// Email chính đăng ký bên NL

        function __construct()
		{
			if(!$this->nl_email_recive || intval($this->sc_merchant_id) < 1 || $this->sc_merchant_token =='' || intval($this->nl_merchant_site) < 1 || $this->nl_merchant_pass =='') {
				return false;
			}
		}

		// input data to ws by SetExpressCheckoutPayment
		public function setShipChungCheckoutPayment($inputs)
		{
			$params = array (
				'sc_merchant_id'		=> $this->sc_merchant_id,
                'sc_merchant_token'     => $this->sc_merchant_token,
				'nl_merchant_site'		=> $this->nl_merchant_site,
				'nl_merchant_pass'		=> $this->nl_merchant_pass,
                'nl_merchant_email'		=> $this->nl_email_recive,
				'params'				=> $inputs
			);
            //return $params;
            if (!function_exists('curl_init')){
                return array('result_code'=>100,'result_description'=> 'Bạn cần phải bật cURL để tiếp tục.');
            }

            if (!function_exists('curl_exec')){
                return array('result_code'=>100,'result_description'=> 'Bạn cần phải bật hàm curl_exec để tiếp tục.');
            }

			$curl = curl_init();
			$params = http_build_query($params);
			curl_setopt ($curl, CURLOPT_URL, $this->urlPOST);
			curl_setopt ($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt ($curl, CURLOPT_HEADER, 0);
			curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt ($curl, CURLOPT_POST, 1);
			curl_setopt ($curl, CURLOPT_POSTFIELDS, $params);
			$result = curl_exec($curl);
			$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$error = curl_error($curl);
			curl_close ($curl);
			return json_decode($result,true);
		}

		public function string_to_array($data)
		{
			if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $data)) {
				$decode = base64_decode($data);
				$code	= json_decode($decode,true);
				return $code;
			} else {
				return false;
			}
		}

	}
?>