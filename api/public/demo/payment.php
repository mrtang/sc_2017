<?php

	require_once('shipchung/shipchung.microcheckout.class.php');
	
	if (isset($_POST['index']) && !empty($_POST['index'])) {
		$index            = $_POST['index'];
		$name             = $_POST['name'];
		$quantity         = $_POST['quantity'];
        $weight           = $_POST['weight'];
		$price            = $_POST['price'];
		$code             = $_POST['code'];
		$items            = array();
		foreach ($index as $i) {
			$items[$i] = array(
				'item_id'		=> (int)$code[$i],	// Mã sản phẩm	
				'item_name'		=> addslashes($name[$i]),	// Tên sản phẩm
				'item_quantity'	=> (int)$quantity[$i], // Số lượng
                'item_weight'	=> (int)$weight[$i],	// Tên sản phẩm
				'item_amount'	=> (int)$price[$i],  // Giá sản phẩm
				'item_url'		=> 'http://www.1top.vn/deals/'.$code[$i].'/ShipChung-VN.html', // Link đến sản phẩm
			);
		}
		
		$array_items = base64_encode(json_encode($items));
	
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Thanh toán đơn hàng</title>
<style type="text/css">
body {
	font-family:Arial, Helvetica, sans-serif;
	font-size:12px;
	color:#333;
}
.center {
	margin:50px auto;
	width:800px;
}
.button {
	color:#FF6633;
	font-weight:bold;
}
</style>
</head>
<body>
<div class="center">
	<h3>Thông tin đơn hàng</h3>
	<table border="1" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">
		<tr>
			<th width="30">#</th>
			<th width="250" align="left">Tên sản phẩm</th>
			<th width="90">Số lượng <u>(sp)</u></th>
            <th width="90">Trọng lượng <u>(g)</u></th>
			<th width="100">Giá tiền <u>(đ)</u></th>
		</tr>
<?php
	if (isset($items)) {
		foreach ($items as $i=>$row) {
?>
		<tr>
			<td align="center"><?php echo $i;?></td>
			<td><?php echo $row['item_name'];?></td>
			<td align="center"><?php echo $row['item_quantity'];?></td>
            <td align="center"><?php echo $row['item_weight'];?></td>
			<td align="right"><?php echo $row['item_amount'];?> đ</td>
		</tr>		
<?php	
		}
	}
?>		
		<tr>
			<td colspan="5" align="right">
			<input type="button" value="Chọn lại sản phẩm" onclick="document.location.href='cart.php';" />
			<!-- Show button ShipChung -->
			<shipchung type="payment" method="ajax" ajax_file="ajax.php" id="<?php echo $array_items;?>"></shipchung>
			</td>
		</tr>
	</table>
</div>
<script language="javascript" src="http://127.0.0.1/shipchung/api/v1.1/shipchung.apps.mcflow.js"></script>
</body>
</html>
