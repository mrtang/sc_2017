<?php
$timeCheck = strtotime(date('2016-10-03 07:49:41',time()));
//var_dump($timeCheck);die;
$Params = array(
 "From"  => array(
    'City' => 52,
    'Province' => 567,
    'Ward' => 4104,
    'Name' => 'Fashion Collections',
    'Phone' => '123123123',
    'Address' => '123/67 PVH'
 ),
 "To" => array(
    'City' => 52,
    'Province' => 567,
    'Ward' => 11696,
    'Name' => 'HCK',
    'Phone' => '123123123123',
    'Address' => '123/67 Phan Van Hon',
 ),
 'Order' => 
  array (
    'Amount' => 398000,
    'Weight' => 200,
    'BoxSize' => '20x20x20',
    'Code' => 4,
    'Quantity' => 1,
    'ProductName' => 'Leather shoes (M)',
  ),

 "Type"  => 'excel',
 "Config" => array(
  "Service"   => 1, //1 là chậm, 2 là nhanh
  "CoD"       => 2, //Giao hàng thu tiền tại nhà, 1: là có, 2: là ko
  "Protected" => 2, //1 là có, 2 là ko
  "Checking"  => 2, //
  "Payment"   => 1, //1: Tôi trả phí; 2: Người mua trả
  "Fragile"   => 2, //Hàng dễ vỡ
  "AutoAccept" => 0
 ),
 'Domain' => '',
 "MerchantKey" => "044531c195b9225bc00079280f281c14"
);

$CurlStart = curl_init();
curl_setopt ($CurlStart, CURLOPT_URL, "http://services.shipchung.vn/api/rest/courier/calculate");
//curl_setopt ($CurlStart, CURLOPT_URL, "https://nhantokhai.gdt.gov.vn/ihtkk_nnt/loginForm.jsp");
curl_setopt ($CurlStart, CURLOPT_RETURNTRANSFER, 1);
//curl_setopt ($CurlStart, CURLOPT_REFERER, $url);
curl_setopt ($CurlStart, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; nl; rv:1.9.1.11) Gecko/20100701 Firefox/3.5.11");
curl_setopt ($CurlStart, CURLOPT_HEADER, false);
curl_setopt ($CurlStart, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($CurlStart, CURLOPT_POSTFIELDS, http_build_query($Params));
curl_setopt($CurlStart, CURLOPT_POST, true);

$header_size = curl_getinfo($CurlStart, CURLINFO_HEADER_SIZE);


$source = curl_exec ($CurlStart);
$header = substr($source, 0, $header_size);
$body = substr($source, $header_size);
curl_close ($CurlStart);
echo ($body);
?>