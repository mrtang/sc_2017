<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Thông tin đơn hàng</title>
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
	<form method="post" action="payment.php">
	<table border="1" cellpadding="3" cellspacing="0" style="border-collapse:collapse;">
		<tr>
			<th width="30">#</th>
			<th width="250" align="left">Tên sản phẩm</th>
			<th width="90">Số lượng <u>(sp)</u></th>
            <th width="90">Trọng lượng <u>(g)</u></th>
			<th width="100">Giá tiền <u>(đ)</u></th>
		</tr>
		<tr>
			<td align="center"><input type="checkbox" name="index[]" value="0" /></td>
			<td>Máy quay chuẩn HD<input type="hidden" name="name[]" value="Máy quay chuẩn HD" /><input type="hidden" name="code[]" value="5253" /></td>
			<td align="right">1000<input type="hidden" name="weight[]" value="1000" /></td>
			<td align="center"><input type="text" name="quantity[]" value="1" style="width:60px; text-align:center;" /></td>
			<td align="right">2.900.000<input type="hidden" name="price[]" value="2900000" /></td>
		</tr>
		<tr>
			<td align="center"><input type="checkbox" name="index[]" value="1" /></td>
			<td>Giầy hiệu Glaze <input type="hidden" name="name[]" value="Giầy hiệu Glaze" /><input type="hidden" name="code[]" value="5274" /></td>
			<td align="right">1200<input type="hidden" name="weight[]" value="1200" /></td>
			<td align="center"><input type="text" name="quantity[]" value="1" style="width:60px; text-align:center;" /></td>
			<td align="right">1.350.000<input type="hidden" name="price[]" value="1350000" /></td>
		</tr>
		<tr>
			<td align="center"><input type="checkbox" name="index[]" value="2" /></td>
			<td>Xe đẩy cao cấp<input type="hidden" name="name[]" value="Xe đẩy cao cấp" /><input type="hidden" name="code[]" value="5468" /></td>
			<td align="right">1500<input type="hidden" name="weight[]" value="1500" /></td>
			<td align="center"><input type="text" name="quantity[]" value="1" style="width:60px; text-align:center;" /></td>
			<td align="right">399.000<input type="hidden" name="price[]" value="399000" /></td>
		</tr>
		<tr>
			<td align="center"><input type="checkbox" name="index[]" value="3" /></td>
			<td>Đai massage giảm béo<input type="hidden" name="name[]" value="Đai massage giảm béo" /><input type="hidden" name="code[]" value="5549" /></td>
			<td align="right">1800<input type="hidden" name="weight[]" value="1800" /></td>
			<td align="center"><input type="text" name="quantity[]" value="1" style="width:60px; text-align:center;" /></td>
			<td align="right">480.000<input type="hidden" name="price[]" value="480000" /></td>
		</tr>
		<tr>
			<td colspan="5" align="right"><input class="button" type="submit" value="Mua hàng" /></td>
		</tr>
	</table>
	</form>
</div>
</body>
</html>
