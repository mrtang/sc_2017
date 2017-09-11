<?php
    /**
	 * Filename: sc_ketqua
	 *
	 * @ File nhận kết quả thành công trả về.
	 * @warning: Không được thay đổi tên file.
     * 
	 * @version SC 1.0
	 * @since 2012
     * @example		: http://domain.com/sc_ketqua.php/v1.1/123456789/123456789
	 * @do			: success (thành công) - cancel (thất bại)
     * @token		: token key order
     * @merchant	: merchant public user
     * @author		: KienNT@peacesoft.net
	 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Thanh toán Thành Công</title>
<style>
html{height:100%;}
body {
	margin: 0px;
	padding: 0px;
    height:100%;
}
</style>
</head>
<body>

<!-- Bắt đầu đặt Tag hiển thị iFrame -->

<sc_result></sc_result>

<!-- Kết thúc đặt Tag hiển thị iFrame -->


<!-- File js bắt buộc phải có (đặt ở đâu cũng được) -->
<script language="javascript" src="http://api.shipchung.vn/v1.2/shipchung.apps.finish.js"></script>

</body>
</html>