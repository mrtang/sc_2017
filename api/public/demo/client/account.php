<?php
require_once("shipchung.client.php");
    header('Content-Type: text/html; charset=utf-8');
    $shipchungclient = new ShipchungClient();
    $shipchungclient->client_id = "page365_access";
    $shipchungclient->client_secret = "page365.vn@477786913079296";
/*
    $code = $_GET['code'];
    $token = $shipchungclient->get_access_token("authorization_code",$code);
    $token = json_decode($token,true);
    $access_token = $token['access_token'];
     echo '<h1>Token info with authorization_code</h1>';
     var_dump($token);

    //Account Linked
     $UserInfo = $shipchungclient->call_api("GetUserOpenID",$access_token,array());

     echo '<h1>UserInfo Account Linked</h1>';
     var_dump(json_decode($UserInfo,1));die;
*/
    // GET new access token connect to api with sc_user_id
    $token = $shipchungclient->get_access_token('client_credentials');
    $token = json_decode($token,true);
    $access_token = $token['access_token'];
    echo 'Token is:'. $access_token .'<br/>';
    $params    = array(
        'user_id'     => 2
     );
    $userinfo = $shipchungclient->call_api("GetUserOpenID",$access_token,$params);
    var_dump($userinfo);
    die;
    //Caculate Fee
    $params = array(
    "user_id"     => 2,
    "item_weight" =>  800,
    "item_price"  =>  400000,
    "service"     =>  2,
    "to_city_id"  =>   18,
    "to_district_id"  =>  181,
    "to_ward_id"      =>  0,
    "from_address"=>  "8 ngĂµ 10 Sá»£n Lá»™c",
    "from_city_id"=>  18,
    "from_district_id"=>  183,
    "from_ward_id"=>  0,
    "vas"=>   array("cod")  // received ,cod
    );
    $CaculateFee = $shipchungclient->call_api("Calculate",$access_token,$params);
    var_dump($CaculateFee);
    echo '<h1>Hàm Tính phí</h1>';
    var_dump(json_decode($CaculateFee,1));die;


     //CreateLading
     $params    = array(
        //"item_width"    => ''
        //"item_height"   => ''
        //"item_hight"    => ''
        "user_id"     => $UserInfo['USER_ID'],
        "item_weight" =>  800,
        "item_price"  =>  400000,
        "service"     =>  2,
        "to_city_id"  =>   18,
        "to_district_id"  =>  181,
        "to_ward_id"      =>  0,
        "from_address"    =>  "8 ngĂµ 10 Sá»£n Lá»™c",
        "from_city_id"    =>  18,
        "from_district_id"  =>  183,
        "from_ward_id"      =>  0,
        "vas"               =>   array("cod"),
        "user_id"           => 3850,
        "from_name"         => 'Dương',
        "from_phone"        => '123412513',
        "to_name"           => 'Dương123',
        "to_phone"          => '12421323',
        "to_address"        => 'Số 8 Ngõ 10'
     );
     echo '<h1>Hàm Tạo vận đơn</h1>';
     $CreateLading = $shipchungclient->call_api("CreateLading",$access_token,$params);
     var_dump(json_decode($CreateLading,1));die;


     //AcceptLading
     $params    = array(
        'sc_code'   => 'SC144911963557',
        'user_id'     => $UserInfo['USER_ID']
     );
     $AcceptLading = $shipchungclient->call_api("AcceptLading",$access_token,$params);
     echo '<h1>Hàm duyệt vận đơn</h1>';
     var_dump(json_decode($AcceptLading,1));die;


     //Journey
     $params    = array(
        'user_id'   => $UserInfo['USER_ID'],
        'sc_code'   => 'SC1393789422'
     );
     $Journey = $shipchungclient->call_api("Journey",$access_token,$params);
     echo '<h1>Hàm lấy hành trình vận đơn</h1>';
     var_dump(json_decode($Journey,1));die;


     //GetLading
     $params    = array(
        'user_id'     => $UserInfo['USER_ID'],
        'sc_code'   => 'SC144911963557'
     );
     $GetLading = $shipchungclient->call_api("GetLading",$access_token,$params);
     echo '<h1>Hàm lấy vận đơn</h1>';
     var_dump(json_decode($GetLading,1));die;


     //GetListLading
     $params    = array(
        'time_start'   => 1,
        'time_end'     => time(),
        'status'       => 'SUCCESS',  // vận đơn thành công
        'start'        => 0,
        'limit'        => 100,
        'user_id'     => $UserInfo['USER_ID']

     );
     $GetListLading = $shipchungclient->call_api("GetListLading",$access_token,$params);
     echo '<h1>Hàm lấy danh sách vận đơn</h1>';
     var_dump(json_decode($GetListLading,1));die;


     //ListCity
     $params    = array();
     $ListCity = $shipchungclient->call_api("ListCity",$access_token,$params);
     echo '<h1>Hàm lấy danh sách tỉnh / thành phố</h1>';
     var_dump(json_decode($ListCity,1));die;


     //ListDistrict
     $params    = array('city_id'   => 18);
     $ListDistrict = $shipchungclient->call_api("ListDistrict",$access_token,$params);
     echo '<h1>Hàm lấy danh sách Quận / Huyện</h1>';
     var_dump(json_decode($ListDistrict,1));die;


     //ListWard
     $params    = array('city_id'   => 18);  // or district_id = 183
     $ListWard = $shipchungclient->call_api("ListWard",$access_token,$params);
     echo '<h1>Hàm lấy danh sách Phường / Xã</h1>';
     var_dump(json_decode($ListWard,1));die;


     //GetPaymentInfo
     $params    = array('payment_id'   => 3850);
     $GetPaymentInfo = $shipchungclient->call_api("GetPaymentInfo",$access_token,$params);
     echo '<h1>PaymentInfo</h1>';
     var_dump(json_decode($GetPaymentInfo,1));die;



?>
