<?php
//echo '{"result_code":"00","token":"23531422-53f7757a222f3dfb5eced325","link_checkout":"http:\/\/localhost\/shipchung\/merchants","link_checkout_cod":"http:\/\/mc.shipchung.vn\/checkout\/eff4863f1b15ef26b5e2e2eecba9c31c\/53f7757a222f3dfb5eced325","time_limit":"22\/08\/2014, 23:53:14","result_description":"Th\u00e0nh c\u00f4ng"}';
//die;
    date_default_timezone_set("Asia/Bangkok");
	require_once('shipchung/shipchung.microcheckout.class.php');
	$obj = new SC_MicroCheckout();

	if (isset($_POST)) {

		//Nên select thông tin từ DB merchant để bảo mật hơn.
		$item_id 	= $_POST['id']; // ID của sản phẩm trong Database (không thay tên trường "id")
		$quantity	= (int)$_POST['quantity']; // Số lượng sản phẩm - Lấy từ ajax post (không thay tên trường "quantity")
		//echo $obj->is_base64_encoded($item_id);die;
		// Xử lý dữ liệu trên trang payment (giỏ hàng hoặc thanh toán - dành cho nhiều sản phẩm)
		if(base64_decode($item_id, true)) {

			$param_items = base64_decode($item_id, true);

			foreach ($param_items as $value) {
				$amount 		+= $value['item_quantity'] * $value['item_amount'];
				$total_weight 	+= $value['item_quantity'] * $value['item_weight'];
			}
		} // End nhiều item


		// Xử lý dữ liệu trên trang detail ( chi tiết sản phẩm - dành cho 1 sản phẩm)
		else {
			// data demo để test code
			$items = array(
				'SC_code'			=> 5253,	// Mã sản phẩm
				'SC_name'			=> 'Máy quay chuẩn HD',	// Tên sản phẩm
				'SC_weight'			=> 1000,	// Khối lượng sản phẩm
				'SC_price'			=> 50000,  // Giá sản phẩm
				'SC_quantity'		=> $quantity
			);

			// data demo để test code
			$name             = addslashes($items['SC_name']);
			$quantity         = (int)$items['SC_quantity'];
			$weight           = (int)$items['SC_weight'];
			$price            = (int)$items['SC_price'];
			$code             = (int)$items['SC_code'];
            $image            = $items['SC_image'];
			// data demo để test code

			$amount           = $price * $quantity;
			$total_weight     = $weight * $quantity;
			$items = array(
					'item_id'		=> $code,	// Mã sản phẩm
					'item_name'		=> $name,	// Tên sản phẩm
                    'item_image'    => $image,	// Ảnh sản phẩm
					'item_quantity'	=> $quantity, // Số lượng
					'item_weight'	=> $weight,	// Tên sản phẩm
					'item_amount'	=> $price,  // Giá sản phẩm
					'item_url'		=> 'http://www.1top.vn/deals/'.$code.'/ShipChung-VN.html', // Link đến sản phẩm
			);

			$param_items = array($items);

		} // End 1 item

		// địa chỉ trang nhận kết quả thanh toán thành công (VD: http://webcuaban.com/shipchung/payment_success.php)
		$return_url = 'http://api.shipchung.vn/demo/scketqua.php';

		$inputs = array(
			'order_code'				=> 'Đơn hàng-'.date('His-dmY'), // mã đơn hàng
			'amount'					=> $amount, // tổng số tiền thanh toán đơn hàng (đã bao gồm: thuế + phí vận chuyển - số tiền giảm giá)
			'weight'				    => $total_weight, // tổng trọng lượng hàng
            'currency_code'				=> 'vnd', // loại đơn vị tiền tệ (vnd hoặc usd)
			'tax_amount'				=> '0', // thuế đơn hàng
			'discount_amount'			=> '0', // số tiền giảm giá
            'time_limit'				=> time(), // Thời gian cho phép thanh toán
			'return_url'				=> $return_url, // địa chỉ trang nhận kết quả thanh toán thành công
			'language'					=> 'vi', // ngôn ngữ hiển thị (vi : tiếng Việt hoặc en : tiếng Anh)
			'items'						=> $param_items // thông tin các sản phẩm
		);

		$link_checkout = '';
		$result = $obj->setShipChungCheckoutPayment($inputs);
        //
		if ($result != false) {
			echo json_encode($result); die();
		} else {
			echo json_encode(array('result_code'=>100,'result_description'=> 'Lỗi kết nối ShipChung'));
			die();
		}
	}


?>