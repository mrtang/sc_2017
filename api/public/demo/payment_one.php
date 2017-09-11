<?php

	$items = array(
		'code'			=> 5253,	// Mã sản phẩm	
		'name'			=> 'Máy quay chuẩn HD',	// Tên sản phẩm
		'weight'		=> 1000,	// Tên sản phẩm
		'price'			=> 2900000,  // Giá sản phẩm
	);
	
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
		<form action="javacript:;" method="post">
		<tr>
			<td align="center">1</td>
			<td><?php echo $items['name'];?></td>
			<td align="center"><input name="soluong" id="soluong" value="1" style="width:60px; text-align:center;" /></td>
            <td align="center"><?php echo $items['weight'];?></td>
			<td align="right"><?php echo $items['price'];?> đ</td>
		</tr>		
		<tr>
			<td colspan="5" align="right">
            
            <shipchung type="detail" method="ajax" level="diamond" nganluong_url="http://nganluong.vn" free_shipping="yes" ajax_file="ajax.php" input_quantity="soluong" id="1"></shipchung>
            <script language="javascript" src="http://services.shipchung.vn/sdk/scripts/sc-button.js"></script>
            </td>
            </tr>            
		</form>
	</table>
</div>


</body>
</html>


