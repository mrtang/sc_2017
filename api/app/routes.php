<?php
date_default_timezone_set("Asia/Ho_Chi_Minh");
require app_path().'/libraries/php-jwt/vendor/autoload.php';
require app_path().'/libraries/vendor/autoload.php';
require app_path().'/ModelObserve.php';
new ModelObserve();

use \Firebase\JWT\JWT;

function RouteController($group_url = "", $url, $function){
	$uri = "";
	if($group_url == '/' || empty($group_url)){
		$uri = $url;
	}else {
		$uri = $group_url.'/'.$url;
	}
	if(Request::is($uri.'/*') || Request::is($uri)){
		Route::controller($url, $function);
	}
}

function setLocate(){
	$headers = getallheaders();
	$lang 	 = 'vi';
	if(isset($headers['LANGUAGE']) && !empty($headers['LANGUAGE'])){
		$lang 	 = $headers['LANGUAGE'];
	}
	$input_lang  = Input::get('lang');
	if(!empty($input_lang)){
		$lang 	 = $input_lang;
	}

	App::setLocale($lang);

}

setLocate();




/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

// Route::filter('check_token', function($route, $request)
// {
// 	if (!Request::is('*/*/app/*')){
// 		if(!Session::has('user_info')){ // Chua dang nh?p
// 			$contents   = array(
// 				'error'         => true,
// 				'message'       => 'login timeout'
// 			);
// 			return Response::json($contents, 440);
// 		}
		
// 	 // filter csrf_token
// 		 if(!(Request::method() == 'GET' && Input::has('cmd'))){
// 			$headers = getallheaders();

// 			if(!isset($headers['Authorization']) || empty($headers['Authorization'])){
// 				return Response::json(['error'=> true, 'message'=> 'UNSUPPORT'], 403);
// 			}

// 			if(Session::token() != $headers['Authorization']){
// 				return Response::json(['error'=> true, 'message'=> 'UNSUPPORT'], 403);
// 			}
// 		}
	
// 		$User = Session::get('user_info');
// 		if(isset($User['time_over']) && $User['time_over'] > 0 && $User['time_over'] < time()){ // black list
// 			$contents   = array(
// 				'error'         => true,
// 				'message'       => 'login timeout'
// 			);
// 			return Response::json($contents, 440);
// 		}
// 	}
// 	return;
// });

Route::filter('check_merchant_key', function ($route, $request){
	if (Session::get('user_info'))
	{
		Session::forget('user_info');
	}

	if(!Input::has('MerchantKey')){
		return Response::json([
			'error'         => true,
			'error_message' => "Can't find merchantkey, please try again !",
		], 403);
	}


	$dbKey = ApiKeyModel::where('key',Input::get('MerchantKey'))->first(['user_id','auto']);

	if (empty($dbKey)) {
		return Response::json([
			'error'         => true,
			'error_message' => "Can't find merchantkey, please try again !",
		], 403);
	}

	$UserInfo = sellermodel\UserInfoModel::where('user_id',$dbKey->user_id)->first(['user_id', 'privilege']);

	if(empty($UserInfo)){
		return Response::json([
			'error'         => true,
			'error_message' => "Can't find merchantkey, please try again !",
		], 403);
	}    

	Session::put('user_info', ['id' => $UserInfo->user_id, 'privilege' => $UserInfo->privilege]);
});

	
	


Route::filter('check_permission', function($route, $request)
{
	$permissionCanAccess = Config::get('auth.privilege_id');
	$groupCanAccess = Config::get('auth.group_id');
	if (!Session::has('user_info') || (!in_array(Session::get('user_info')['group'],$groupCanAccess) && !in_array(Session::get('user_info')['privilege'],$permissionCanAccess))) {
		$contents   = array(
			'error'         => true,
			'message'       => 'login timeout'
		);
		return Response::json($contents, 440);
	}
});

Route::filter('check_token_app', function($route, $request)
{
	$headers = getallheaders();


	if(empty($headers['Authorization'])){
		return Response::json(['error'=> true, 'error_message'=> 'Bạn không có quyền sử dụng chức năng này, vui lòng liên hệ bộ phận CSKH để được hỗ trợ.'], 403);
	}
	/*$Model = sellermodel\UserInfoModel::where('api_access_token', $headers['Authorization'])->first();
	if(!$Model){
		return Response::json(['error'=> true, 'error_message'=> 'Bạn không có quyền sử dụng chức năng này, vui lòng liên hệ bộ phận CSKH để được hỗ trợ.', 'message'=>'TOKEN_NOT_FOUND'], 403);
	}*/

	$access_token = $headers['Authorization'];

	try {
		$decoded = JWT::decode($access_token, Config::get('app.key'), array('HS256'));
	} catch (Exception $e) {
		return Response::json(['error'=> true, 'error_message'=> 'Bạn không có quyền sử dụng chức năng này, vui lòng liên hệ bộ phận CSKH để được hỗ trợ.', 'message'=>'TOKEN_INVALID'], 403);
	}

	if ($decoded->exp < time()) {
		return Response::json(['error'=> true, 'error_message'=> 'Bạn không có quyền sử dụng chức năng này, vui lòng liên hệ bộ phận CSKH để được hỗ trợ', 'message'=>'TOKEN_EXPIRED'], 403);
	}


	Input::merge(array('App'=> true, 'Domain'=> 'app.shipchung.vn'));
	Session::put('user_info',(array) $decoded->data);
});



Route::filter('check_token', function($route, $request)
{
	$headers = getallheaders();
	$access_token = "";
	$InputAccess = Input::get('access_token');
	if(isset($headers['Authorization'])){
		$access_token = $headers['Authorization'];
	}elseif(isset($headers['authorization'])){
		$access_token = $headers['authorization'];

	}elseif(!empty($InputAccess)){
		
		$access_token = $InputAccess;
	}


	if(empty($access_token)){
		return Response::json(['error'=> true, 'error_message'=> 'Bạn không có quyền sử dụng chức năng này, vui lòng liên hệ bộ phận CSKH để được hỗ trợ.'], 403);
	}

	/*$Model = sellermodel\UserInfoModel::where('api_access_token', $headers['Authorization'])->first();
	if(!$Model){
		return Response::json(['error'=> true, 'error_message'=> 'Bạn không có quyền sử dụng chức năng này, vui lòng liên hệ bộ phận CSKH để được hỗ trợ.', 'message'=>'TOKEN_NOT_FOUND'], 403);
	}*/
	
	try {
		$decoded = JWT::decode($access_token, Config::get('app.key'), array('HS256'));
	} catch (\Exception $e) {
		return Response::json(['error'=> true, 'error_message'=> 'Bạn không có quyền sử dụng chức năng này, vui lòng liên hệ bộ phận CSKH để được hỗ trợ.', 'message'=>'TOKEN_INVALID'], 403);
	}

	if ($decoded->exp < time()) {
		return Response::json(['error'=> true, 'error_message'=> 'Bạn không có quyền sử dụng chức năng này, vui lòng liên hệ bộ phận CSKH để được hỗ trợ', 'message'=>'TOKEN_EXPIRED'], 403);
	}


	//Input::merge(array('App'=> true, 'Domain'=> 'app.shipchung.vn'));
	$UserInfo = (array) $decoded->data;
	if($UserInfo['privilege'] > 0 && $UserInfo['group'] > 0){
		$App = new AppController();
		$PrivilegeGroup     = $App->Privilege((int)$UserInfo['group']);
		$UserInfo['group_privilege'] = $PrivilegeGroup;
	}

	Session::put('user_info', (array) $UserInfo);
});




Route::group(array('prefix'=> 'api/merchant/public', 'before' => ['check_merchant_key'] ), function (){
	
	RouteController('api/merchant/public', 'order'                   , 'mobile_order\OrderController');
	RouteController('api/merchant/public', 'order-change'            , 'order\ChangeOrderCtrl');
	RouteController('api/merchant/public', 'journey' , 'trigger\CourierAcceptJourney');
	RouteController('api/merchant/public', 'ticket-request'          , 'ticket\RequestController');
	RouteController('api/merchant/public', 'ticket-feedback'         , 'ticket\FeedbackController');
	RouteController('api/merchant/public', 'ticket-case-type'        , 'ticket\CaseTypeController');

	Route::post('pipe-journey/create'           , 'oms\PipeJourneyCtrl@postCreate');
});



Route::group(array('prefix'=> 'api/public/v1', 'before' => ['check_token_app'] ), function (){
	
	RouteController('api/public/v1', 'inventory-config'        , 'mobile_seller\UserInventoryController');
	RouteController('api/public/v1', 'merchant'                , 'mobile_seller\MerchantCtr');
	RouteController('api/public/v1', 'user'                    , 'mobile\UserController');
	
	RouteController('api/public/v1', 'fee-config'              , 'mobile_seller\FeeController');
	RouteController('api/public/v1', 'user-info'               , 'mobile_seller\UserInfoController');
	RouteController('api/public/v1', 'bank-config'             , 'mobile_seller\VimoController');
	RouteController('api/public/v1', 'order'                   , 'mobile_order\OrderController');
	RouteController('api/public/v1', 'order-change'            , 'order\ChangeOrderCtrl');
	RouteController('api/public/v1', 'order-verify'            , 'mobile_order\VerifyController');
	RouteController('api/public/v1', 'upload'                  , 'mobile\UploadController');
	RouteController('api/public/v1', 'statistic'               , 'mobile\StatisticController');

	RouteController('api/public/v1', 'ticket-case-type'        , 'mobile_ticket\CaseTypeController');
	RouteController('api/public/v1', 'ticket-request'          , 'mobile_ticket\RequestController');
	RouteController('api/public/v1', 'ticket-feedback'         , 'mobile_ticket\FeedbackController');

	RouteController('api/public/v1', 'accept-status-order'         , 'mobile_trigger\AcceptStatusOrder');
	
	RouteController('api/public/v1', 'post-office'             , 'mobile\PostOfficeController');

	Route::get('location/city'                  , array('uses' => 'ApiMerchantCtrl@getCity'));
	Route::get('location/district'              , array('uses' => 'ApiMerchantCtrl@getProvince'));
	Route::get('location/ward'                  , array('uses' => 'ApiMerchantCtrl@getWard'));
	
	Route::post('courier/calculate'             , 'ApiCourierCtrl@postCalculate');
	Route::post('courier/create'                , 'ApiCourierCtrl@postCreate');
	
	Route::post('global/calculate'                  , 'ApiGlobalCtrl@postCalculate');
	Route::post('global/create'                      , 'ApiGlobalCtrl@postCreate');

	Route::post('pipe-journey/create'           , 'oms\PipeJourneyCtrl@postCreate');


	RouteController('api/public/v1', 'journey' , 'trigger\CourierAcceptJourney');
	RouteController('api/public/v1', 'config-transport'                 , 'mobile\UserConfigTransportController');
	RouteController('api/public/v1', 'coupon'                  , 'seller\CouponController');
	RouteController('api/public/v1', 'notification'                  , 'mobile\PushNotificationController');
	RouteController('api/public/v1', 'upload'                  , 'UploadController');
	
	RouteController('api/public/v1', 'user-cash-in'            , 'mobile_seller\CashInController');
	RouteController('api/public/v1', 'base'            , 'mobile_v2\MobileApiV2Controller');
	

});


	Route::get('/', function()
	{
		//echo DNS1D::getBarcodePNG('SC51614725103', "EAN8",1.5,40);
	//    echo DNS1D::getBarcodeHTML("51614725103", "EAN13");
	//    echo DNS1D::getBarcodeHTML("SC51614725103", "C128");
	//    echo DNS1D::getBarcodeHTML("SC51614725103", "C128A");
	//    echo DNS1D::getBarcodeHTML("SC51614725103", "C128B");
	//    echo DNS1D::getBarcodeHTML("51614725103", "EAN2");
		//echo DNS1D::getBarcodeHTML("51614725103", "EAN5");
		//echo 'Hello World';

		
		echo Lang::get('response.server_error').' - ' . Config::get('app.locale');
	});


Route::group(array('prefix' => 'oauth'), function()
{
	RouteController('oauth', 'token', 'OauthCtrl');
});

Route::group(array('prefix' => 'api/v1'), function() {
	RouteController('api/v1', 'app'                     , 'AppController');
	RouteController('api/v1', 'mobile'                  , 'mobile\AppController');
});


Route::group(array('prefix' => 'api/v1','before'    =>  'check_oms'), function() {
   
	RouteController('api/v1', 'user-group'              , 'UserGroupController');
	RouteController('api/v1', 'user-privilege'          , 'UserPrivilegeController');
	RouteController('api/v1', 'group'                   , 'GroupController');
	RouteController('api/v1', 'group-privilege'         , 'GroupPrivilegeController');
	RouteController('api/v1', 'cashin'                  , 'oms\CashInController');
});

Route::group(array('prefix' => 'api/v1','before' => 'check_token'), function()
{
	
	RouteController('api/v1', 'user'                    , 'UserController');
	RouteController('api/v1', 'locate'                  , 'LocateController');
	RouteController('api/v1', 'postman'                 , 'PostManController');
	RouteController('api/v1', 'postman-care'            , 'PostManCareController');
	RouteController('api/v1', 'courier-promise'         , 'CourierPromiseController');
	RouteController('api/v1', 'courier-comission'       , 'CourierComissionController');
	RouteController('api/v1', 'courier-service'         , 'CourierServiceController');
	RouteController('api/v1', 'courier-service-group'   , 'CourierServiceGroupController');
	RouteController('api/v1', 'courier-type'            , 'CourierTypeController');
	RouteController('api/v1', 'courier-status'          , 'CourierStatusController');
	RouteController('api/v1', 'courier-vas'             , 'CourierVasController');
	RouteController('api/v1', 'courier-area'            , 'CourierAreaController');
	RouteController('api/v1', 'area-location'           , 'AreaLocationController');
	RouteController('api/v1', 'courier-fee'             , 'CourierFeeController');
	RouteController('api/v1', 'courier-vas-fee'         , 'CourierFeeVasController');
	RouteController('api/v1', 'api-key'                 , 'ApiKeyController');
	RouteController('api/v1', 'fee-pickup'              , 'oms\FeePickupController');
	RouteController('api/v1', 'fee-delivery'            , 'oms\FeeDeliveryController');
	RouteController('api/v1', 'post-office'             , 'oms\PostOfficeController');
	RouteController('api/v1', 'status-accept'           , 'oms\StatusAcceptController');
	RouteController('api/v1', 'courier-estimate'        , 'CourierEstimateController');
	
	//Notification 
	RouteController('api/v1', 'transport'               , 'TransportController');
	RouteController('api/v1', 'template'                , 'TemplateController');
	RouteController('api/v1', 'scenario'                , 'ScenarioController');
	RouteController('api/v1', 'scenario-template'       , 'ScenarioTemplateController');
	RouteController('api/v1', 'discount-config'         , 'DiscountConfigController');
	RouteController('api/v1', 'system-scenario-config'  , 'SystemScenarioConfigController');
	RouteController('api/v1', 'user-config-transport'   , 'UserConfigTransportController');
	RouteController('api/v1', 'user-scenario-config'    , 'UserScenarioConfigController');
	RouteController('api/v1', 'queue'                   , 'QueueController');
	RouteController('api/v1', 'ticket-notification'     , 'TicketNotificationController');
	RouteController('api/v1', 'order-notification'      , 'OrderNotificationController');
	RouteController('api/v1', 'notice'                  , 'NoticeController');
	
	// Upload
	RouteController('api/v1', 'upload'                  , 'UploadController');
	
	//seller
	RouteController('api/v1', 'bussiness-info'          , 'seller\BussinessInfoController');
	RouteController('api/v1', 'inventory-config'        , 'seller\UserInventoryController');
	RouteController('api/v1', 'banking-config'          , 'seller\BankingController');
	RouteController('api/v1', 'vimo-config'             , 'seller\VimoController');
	RouteController('api/v1', 'fee-config'              , 'seller\FeeController');
	RouteController('api/v1', 'courier-config'          , 'seller\CourierController');
	RouteController('api/v1', 'child-config'            , 'seller\ChildInfoController');
	RouteController('api/v1', 'user-info'               , 'seller\UserInfoController');
	RouteController('api/v1', 'user-cash-in'            , 'seller\CashInController');
	RouteController('api/v1', 'coupon'                  , 'seller\CouponController');
	
	RouteController('api/v1', 'merchant'                , 'seller\MerchantCtrl');
	RouteController('api/v1', 'transaction'             , 'seller\TransactionCtrl');

	RouteController('api/v1', 'dashbroad'               , 'DashbroadCtrl');

	// ticket
	RouteController('api/v1', 'ticket-request'          , 'ticket\RequestController');
	RouteController('api/v1', 'ticket-feedback'         , 'ticket\FeedbackController');
	RouteController('api/v1', 'ticket-rating'           , 'ticket\RatingController');
	RouteController('api/v1', 'ticket-question'         , 'ticket\QuestionController');
	RouteController('api/v1', 'ticket-refer'            , 'ticket\ReferController');
	RouteController('api/v1', 'ticket-case-ticket'      , 'ticket\CaseTicketController');
	RouteController('api/v1', 'ticket-assign'           , 'ticket\AssignController');
	RouteController('api/v1', 'ticket-case'             , 'ticket\CaseController');
	RouteController('api/v1', 'ticket-case-type'        , 'ticket\CaseTypeController');
	RouteController('api/v1', 'statistic'               , 'ticket\StatisticController');
	RouteController('api/v1', 'reply-template'          , 'ticket\ReplyTemplateController');
	RouteController('api/v1', 'ticket-extend-time'      , 'ticket\RequestExtendTimeController');
	RouteController('api/v1', 'ticket-dashbroad'        , 'ticket\DashbroadController');
	RouteController('api/v1', 'call-center'             , 'ticket\AsteriskController');

	// order
	RouteController('api/v1', 'order-verify'            , 'order\VerifyController');
	RouteController('api/v1', 'warehouse-verify'        , 'order\WarehouseVerifyController');
	
	RouteController('api/v1', 'order-invoice'           , 'order\InvoiceController');
	RouteController('api/v1', 'order-change'            , 'order\ChangeOrderCtrl');
	RouteController('api/v1', 'order'                   , 'order\OrderESController');
	RouteController('api/v1', 'order-address'           , 'order\OrderAddressController');
	RouteController('api/v1', 'order-provide'           , 'order\AccountingController');
	RouteController('api/v1', 'order-process'           , 'order\OrderProcessController');

	RouteController('api/v1', 'mailchimp'               , 'MailchimpController');

	//OMS
	RouteController('api/v1', 'customer'                , 'oms\CustomerController');
	RouteController('api/v1', 'customer-admin'          , 'oms\CustomerAdminController');
	RouteController('api/v1', 'excel-update'            , 'oms\ExcelUpdateController');
	RouteController('api/v1', 'overweight-excel-update' , 'oms\OverWeightExcelUpdateController');
	
	RouteController('api/v1', 'seller'                  , 'oms\SellerController');
	RouteController('api/v1', 'log'                     , 'oms\LogController');
	RouteController('api/v1', 'fb'                     , 'oms\FbController');

	//Pickup,Delivery,Return,Complain
	RouteController('api/v1', 'pickup'                  , 'PickupController');
	RouteController('api/v1', 'delivery'                , 'DeliveryController');
	RouteController('api/v1', 'return'                  , 'ReturnController');
	//RouteController('api/v1', 'complain'                , 'ComplainController');
	RouteController('api/v1', 'request-delivery'        , 'oms\RequestDeliveryController');

	//SMS
	RouteController('api/v1', 'sms'                     , 'SmsController');
	//search
	RouteController('api/v1', 'search'                  , 'SearchController');
	// Elasticsearch 
	RouteController('api/v1', '_search'                 , 'ElasticSearchController');
	
	RouteController('api/v1', 'export'                  , 'oms\ExportController');
	RouteController('api/v1', 'facebook'                , 'FacebookController');    
	Route::get('pipe-status/pipebygroup'        , 'oms\PipeStatusController@getPipebygroup');   
	Route::post('pipe-journey/create'           , 'oms\PipeJourneyCtrl@postCreate');
	/// New oms routes 

	RouteController('api/v1', 'group-user'               , 'oms\GroupUserController');    
	RouteController('api/v1', 'privilege'                , 'oms\PrivilegeController');    
	RouteController('api/v1', 'pipe-status'              , 'oms\PipeStatusController');    

	RouteController('api/v1', '/oms/merchant'            , 'oms\MerchantController');


	RouteController('api/v1', 'tasks'                   , 'oms\TasksController');
	RouteController('api/v1', 'ticket-reminder'                   , 'ticket\TicketReminderController');

	RouteController('api/v1', 'ticket-user-dashbroad'        , 'ticket\DashbroadUserController');
	RouteController('api/v1', 'ops-dashboard'  , 'ops\UserDashboardCtrl');

	RouteController('api/v1', 'product-trading'             , 'seller\ProductTradingController');
	RouteController('api/v1', 'refund-confirm'    , 'ops\RefundConfirmCtrl');

	/// Move from group api/rest 
	Route::post('courier/calculate'             , 'ApiCourierCtrl@postCalculate');
	Route::post('courier/create_lading'                , 'ApiCourierCtrl@postCreate');
	
	Route::post('global/calculate'                  , 'ApiGlobalCtrl@postCalculate');
	Route::post('global/create_lading'                      , 'ApiGlobalCtrl@postCreate');



	
});

Route::group(array('prefix' => 'api/v1'), function()
{
	
	RouteController('api/v1', 'city'                    , 'CityController');
	RouteController('api/v1', 'district'                , 'DistrictController');
	RouteController('api/v1', 'ward'                    , 'WardController');
	RouteController('api/v1', 'location'                , 'LocationController');
	RouteController('api/v1', 'courier'                 , 'CourierController');
	
	RouteController('api/v1', 'public-order'            , 'order\PublicOrderController');
	RouteController('api/v1', 'order-status'            , 'order\StatusOrderCtrl');

	Route::get('list_status'                    , 'BaseController@getStatus');
	Route::get('user_vip'                       , 'oms\UserCtrl@getVip');
	Route::get('order-oms/tag'                  , 'oms\OrderCtrl@getTag');
	Route::get('group-process'                  , 'oms\PipeStatusController@getGroupProcess');
	Route::get('pipestatus/pipegroup'          , 'oms\PipeStatusController@getPipebygroup');
	Route::get('access/verify-bank/{token}'        , 'seller\VimoController@getVerifyBank');
	Route::get('access/confirmchangenl/{refer_code}'        , 'seller\UserInfoController@getConfirmchangenl');
	Route::get('access/verifychild/{refer_code}'        , 'seller\ChildInfoController@getVerifychild');
	Route::get('access/zurmo/{email}'        , 'ZurmoController@getFollow');
});

Route::group(array('prefix' => 'cronjob'), function()
{   
	
	
	RouteController('cronjob', 'ticket-notification'      , 'TicketNotificationController');
	RouteController('cronjob', 'order-notification'          , 'OrderNotificationController');
	RouteController('cronjob', 'user-notification'       , 'UserNotificationController');
	RouteController('cronjob', 'verify-notification'      , 'VerifyNotificationController');
	RouteController('cronjob', 'notice'                      , 'NoticeController');
	RouteController('cronjob', 'zurmo'                   , 'ZurmoController');
	RouteController('cronjob', 'zalo'                   , 'ZmsController');
	RouteController('cronjob', 'app'                     , 'AppNotificationController');
	RouteController('cronjob', 'convert'                     , 'ConvertController');
	RouteController('cronjob', 'facebook'                , 'FacebookNotificationController');
	RouteController('cronjob', 'order-buyer'                 , 'OrderBuyerController');
	RouteController('cronjob', 'order-item'              , 'OrderItemController');
	Routecontroller('cronjob', 'loyalty-notice'                     , 'LoyaltyController');

	Route::get('sync-latlng-inventory'          		, 'seller\UserInventoryController@getSyncLatlng');
	Route::get('ticket/autoclose-ticket'        		, 'ticket\RequestController@getAutocloseTicket');
	Route::get('call-center/sync'             			, 'ticket\AsteriskController@getSyncCdr');

	Route::get('verify_courier_fee' 					, 'accounting\CourierVerifyCtrl@getVerifyFee');
	Route::get('verify_courier_collect' 				, 'accounting\CourierVerifyCtrl@getVerifyMoneyCollect');
	Route::get('verify_courier_service' 				, 'accounting\CourierVerifyCtrl@getVerifyService');

	Route::get('kpi-sale-revenue'         				, 'ops\KpiCtrl@getKpiSaleRevenue');
	Route::get('kpi-sale-processing'         			, 'ops\KpiCtrl@getKpiSaleProcessing');
	Route::get('kpi-sale-fulfill'         				, 'ops\KpiCtrl@getKpiSaleFulfill');

	Route::get('kpi-sale-salary'         				, 'ops\KpiCtrl@getSaleSalary');
	Route::get('kpi-salary'         					, 'ops\KpiCtrl@getSalary');

	Route::get('kpi-processing'         				, 'ops\KpiCtrl@getKpiCsTicket');

	Route::get('processing-report/{category}'         	, 'ops\KpiCtrl@getProcessingKpi');
	Route::get('crm-update-employee'             		, 'ops\KpiCtrl@getUpdateEmployeeSale');
});




// API for courier  

Route::group(array('prefix' => 'api/v2/courier'), function()
{   
	RouteController('api/v2/courier', 'journey',     'trigger\ReceiverJourneyCtrl');
});






Route::group(array('prefix' => 'api/rest'), function()
{   
	
	Route::post('courier/calculate'                 , 'ApiCourierCtrl@postCalculate');
	Route::get('courier/calculate_courier'          , 'ApiCourierCtrl@getCaculaterCourier');
	Route::post('global/calculate'                  , 'ApiGlobalCtrl@postCalculate');
	Route::post('global/create'                      , 'ApiGlobalCtrl@postCreate');

	Route::post('courier/checkoutnganluong/{token?}'        , 'ApiCourierCtrl@postCheckoutnganluong');
	Route::post('courier/create'                            , 'ApiCourierCtrl@postCreate');
	Route::post('lading/create', array('uses' => 'ApiDispatcherCtrl@postCreate'));
	Route::post('lading/createformulti', array('uses' => 'ApiDispatcherCtrl@postCreateformulti'));
	// Update lich trinh
	Route::post('courier/journey/v1', array('uses' => 'ApiJourneyCtrl@postJourneyv1'));
	Route::post('courier/journey', array('before' => 'oauth', 'uses' => 'ApiJourneyCtrl@postJourney'));
	Route::post('courier/journey/njv/{id}', array('uses' => 'ApiJourneyCtrl@postJourneynjv'));
	

	/**
	* @desc API tra cứu hóa đơn 
	* ThinhNV
	*/


	Route::get('invoice', array('uses' => 'order\InvoiceController@InvoiceAPI'));
	Route::get('transaction', array('uses' => 'ApiDispatcherCtrl@TransactionAPI'));
	Route::get('verify', array('uses' => 'order\VerifyController@VerifyAPI'));
	Route::get('verify-detail', array('uses' => 'order\VerifyController@getShow'));
	Route::post('courier/accept'        , 'ApiMerchantCtrl@postAccept');

	//đồng bộ live chat
	RouteController('api/rest', 'migrate-live-chat'      , 'api\MigrateLiveChatCtrl');

	//đồng bộ ticket
	RouteController('api/rest', 'report',     'api\SyncReportCtrl');

	//luu thong ke van don
	RouteController('api/rest', 'order',     'api\ApiOrderCtrl');

	RouteController('api/rest', 'lading'      , 'ApiDispatcherCtrl');
	RouteController('api/rest', 'crawl'      , 'trigger\CourierCrawlJourney');


	RouteController('api/rest', 'ticket-rating'           , 'ticket\RatingController');
});

Route::group(array('prefix' => 'api/merchant/rest'), function()
{   
	
	RouteController('api/merchant/rest', 'lading'      , 'ApiMerchantCtrl'); 

	Route::post('lading/create', array('uses' => 'ApiMerchantCtrl@postCreate'));
	Route::post('lading/createformulti', array('uses' => 'ApiMerchantCtrl@postCreateformulti'));
	
	Route::post('upload_exchange', array('uses' => 'UploadController@postExchange'));

	//
	Route::get('invoice', array('uses' => 'order\InvoiceController@InvoiceAPI'));
	Route::get('transaction', array('uses' => 'accounting\TransactionController@TransactionAPI'));
	Route::get('verify', array('uses' => 'order\VerifyController@VerifyAPI'));
	Route::post('box-order', array('uses' => 'WebServiceCtrl@postBoxOrder'));
	Route::post('upload_logo', array('uses' => 'WebServiceCtrl@postUploadLogo'));
	});
		
Route::group(array('prefix' => 'adapter'), function()
{   
	RouteController('adapter', 'soap'      , 'ApiSoapCtrl');
});

Route::group(array('prefix' => 'trigger'), function()
{   
	RouteController('trigger', 'courier' , 'trigger\CourierAcceptLadingCtrl');
	RouteController('trigger', 'journey' , 'trigger\CourierAcceptJourney');
	RouteController('trigger', 'crawl' , 'trigger\CourierCrawlJourney');
});

Route::group(array('prefix' => 'popup'), function()
{   
	if (Request::is('popup/*'))
	{
	Route::get('nganluong', array('uses' => 'WebServiceCtrl@VerifyNganLuong'));
	Route::controller( '/'      , 'WebServiceCtrl');
	}
});





// Check Privilege Accounting
Route::filter('check_accounting', function($route, $request)
{
	$headers 	= getallheaders();
	$UserInfo   = Session::get('user_info');
	// if($UserInfo['privilege'] != 2  && (!isset($UserInfo['group_privilege']['PRIVILEGE_ACCOUNTING']) || $UserInfo['group_privilege']['PRIVILEGE_ACCOUNTING']['view'] != 1)){
	// 	return Response::json([
	// 		'error'     => true,
	// 		'message'   => 'USER_NOT_ALLOW'
	// 	], 200);
	// }
	
	if(isset($headers['ULocation']) && !empty($headers['ULocation'])){
		Input::merge(['country_id'	=> $headers['ULocation']]);
	}


});

// Check permission ops
Route::filter('check_operation_system', function($route, $request)
{
	$headers 	= getallheaders();
	$UserInfo   = (array)Session::get('user_info');


	// try{
	// 	if($UserInfo['privilege'] != 2  && (!isset($UserInfo['group_privilege']['PRIVILEGE_OPERATION_SYSTEM']) || $UserInfo['group_privilege']['PRIVILEGE_OPERATION_SYSTEM']['view'] != 1)){
	// 		return Response::json([
	// 			'error'     => true,
	// 			'message'   => 'USER_NOT_ALLOW'
	// 		], 200);
	// 	}	
	// }catch(\Exception $e){
	// 	return Response::json([
	// 		'error'     => true,
	// 		'message'   => 'USER_NOT_ALLOW',
	// 		'user'=> $UserInfo,
	// 		'tyoe'=> gettype($UserInfo)
	// 	], 200);
	// }
	

	if(isset($headers['ULocation']) && !empty($headers['ULocation'])){
		Input::merge(['from_country_id'	=> $headers['ULocation']]);
	}
});

// Check permission oms
Route::filter('check_oms', function($route, $request)
{
	$headers 	= getallheaders();
	$UserInfo   = Session::get('user_info');
	if(!in_array((int)$UserInfo['privilege'], [1,2,3]) && $UserInfo['group'] == 0){
		return Response::json([
			'error'     => true,
			'message'   => 'USER_NOT_ALLOW'
		], 200);
	}

	if(isset($headers['ULocation']) && !empty($headers['ULocation'])){
		Input::merge(['country_id'	=> $headers['ULocation']]);
	}
});

// Check permission ticket
Route::filter('check_ticket', function($route, $request)
{
	$UserInfo   = Session::get('user_info');
	if($UserInfo['privilege']   == 0){
		return Response::json([
			'error'     => true,
			'message'   => 'USER_NOT_ALLOW'
		], 200);
	}
});

// Check accounting boxme
Route::filter('check_accounting_bm', function($route, $request)
{
	$UserInfo   = Session::get('user_info');
	// if($UserInfo['privilege'] != 2  && (!isset($UserInfo['group_privilege']['PRIVILEGE_BOXME_ACCOUNTING']) || $UserInfo['group_privilege']['PRIVILEGE_BOXME_ACCOUNTING']['view'] != 1)){
	// 	return Response::json([
	// 		'error'     => true,
	// 		'message'   => 'USER_NOT_ALLOW'
	// 	], 200);
	// }
});


/**
 * router Base
 */
Route::group(array('prefix' => 'api/base'), function()
{
	if (Request::is('api/base/*'))
	{
	Route::controller('/' , 'BaseCtrl');
	}
});

/**
 * router Seller
 */
Route::group(array('prefix' => 'api/seller'), function()
{
	RouteController('api/seller', 'base'    , 'seller\BaseCtrl');
	RouteController('api/seller', 'config'  , 'seller\CourierConfigCtrl');
	
	RouteController('api/seller', 'order'   , 'seller\OrderDetailCtrl');
	RouteController('api/seller', 'exchange'    , 'seller\ExchangeCtrl');
	RouteController('api/seller', 'verify'  , 'seller\VerifyCtrl');
	RouteController('api/seller', 'loyalty'  , 'seller\LoyaltyCtrl');
	
	Route::post('exchange_create'   , array('uses' => 'exchange\ExchangeCtrl@postCreate'));
});


/**
 * router Ticket
 */
Route::group(array('prefix' => 'ticket','before' => 'check_token|check_ticket'), function()
{
	if (Request::is('ticket/*'))
	{
	Route::controller('base'        , 'ticket\BaseCtrl');
	}
});


/**
 * router Accounting
 */
Route::group(array('prefix' => 'accounting','before' => 'check_token|check_accounting'), function()
{
	
	RouteController('accounting', 'order'           	, 'accounting\OrderCtrl');
	RouteController('accounting', 'report'              , 'accounting\ReportCtrl');
	RouteController('accounting', 'transaction'    		, 'accounting\TransactionCtrl');
	RouteController('accounting', 'merchant'      		, 'accounting\MerchantCtrl');
	RouteController('accounting', 'cash-out'         	, 'accounting\CashOutController');
	RouteController('accounting', 'cash-in'          	, 'accounting\CashInController');
	RouteController('accounting', 'invoice'           	, 'accounting\InvoiceCtrl');
	RouteController('accounting', 'verify'         		, 'accounting\VerifyCtrl');
	RouteController('accounting', 'courier-verify'      , 'accounting\CourierVerifyCtrl');
	RouteController('accounting', 'warehouse'      		, 'accounting\WareHouseCtrl');
	RouteController('accounting', 'wmstype'      		, 'accounting\WmsTypeCtrl');
	RouteController('accounting', 'warehouse-verify'  	, 'accounting\WareHouseVerifyCtrl');
	RouteController('accounting', 'partner-verify'      , 'accounting\PartnerVerifyCtrl');
}); 




/**
 * router OPS
 */
Route::group(array('prefix' => 'ops','before' => 'check_token|check_operation_system'), function()
{
	RouteController('ops', 'base'            	, 'ops\BaseCtrl');
	RouteController('ops', 'order'          	, 'ops\OrderESCtrl');
	RouteController('ops', 'log'               	, 'ops\LogCtrl');
	RouteController('ops', 'complain'        	, 'ops\ComplainCtrl');
	Routecontroller('ops', 'marketing'     		, 'ops\MarketingCtrl');
	Routecontroller('ops', 'courier'          	, 'ops\CourierCtrl');
	RouteController('ops', 'refund-confirm'    	, 'ops\RefundConfirmCtrl');
	RouteController('ops', 'kpi'				, 'ops\KpiCtrl');
	RouteController('ops', 'report'				, 'ops\ReportCtrl');

	RouteController('ops', 'warehouse-problem'  , 'warehouse\ProblemCtrl');
	RouteController('ops', 'warehouse-shipment'	, 'warehouse\ShipmentCtrl');
	RouteController('ops', 'warehouse-packed'	, 'warehouse\WareHousePackageCtrl');
	RouteController('ops', 'warehouse-return'	, 'warehouse\WareHouseReturnCtrl');
});

/**
 * router OMS
 */

Route::group(array('prefix' => 'api/oms','before' => 'check_token|check_oms'), function()
{
	RouteController('api/oms', 'order'           , 'oms\OrderCtrl');
	RouteController('api/oms', 'pipe-journey'    , 'oms\PipeJourneyCtrl');
	RouteController('api/oms', 'inventory'       , 'oms\InventoryCtrl');
	RouteController('api/oms', 'user'            , 'oms\UserCtrl');
	RouteController('api/oms', 'upload'          , 'oms\UploadCtrl');
});

/**
 * Router loyalty
 */
Route::group(array('prefix' => 'loyalty','before' => 'check_token|check_oms'), function()
{
	RouteController('loyalty', 'user'           	, 'loyalty\UserCtrl');
	RouteController('loyalty', 'config'           	, 'loyalty\ConfigCtrl');
	RouteController('loyalty', 'campaign'       	, 'loyalty\CampaignCtrl');
});

/**
 * Api Public
 */
Route::group(array('prefix' => '/'), function()
{
	Route::controller('public'  , 'ApiPublicCtrl');
	// push status
	Route::controller('push'    , 'trigger\PushStatusCtrl');
	Route::controller('wetrack'    , 'trigger\WetrackCtrl');
	
	

	// k�o tr?ng th�i vtp
	Route::get('upload/status-verify/{params}'    , 'oms\UploadCtrl@getStatusVerify');
	Route::get('loyalty/cron-loyalty'    , 'loyalty\CampaignCtrl@getCronLoyalty');
});

/**
 * Api Boxme
 */
Route::group(array('prefix' => 'boxme'), function()
{
	if (Request::is('boxme/*'))
	{
	//Đối soát
	Route::get('create-verify'          	, 'ApiBoxmeCtrl@getCreateVerify');
	Route::get('verify'             		, 'ApiBoxmeCtrl@getVerify');

	//Đối soát Hãng
	Route::get('accept-partner-verify'      , 'ApiBoxmeCtrl@getVerifyPartner');

	//Tính phí lưu kho tạm tính
	Route::get('create-provisional'  		, 'ApiBoxmeCtrl@getCreateProvisional');
	Route::get('provisional'                , 'ApiBoxmeCtrl@getProvisional');

	//Thay đổi hình thức tính phí
	Route::get('change-payment-type'        , 'ApiBoxmeCtrl@getChangePaymentType');
	} 
});


/**
 * router Boxme Accounting */

Route::group(array('prefix' => 'api/bm_accounting','before' => 'check_token|check_accounting_bm'), function()
{
	
	RouteController('api/bm_accounting', 'merchant'            , 'bm_accounting\MerchantCtrl');
	RouteController('api/bm_accounting', 'transaction'         , 'bm_accounting\TransactionCtrl');
	RouteController('api/bm_accounting', 'verify'              , 'bm_accounting\VerifyCtrl');
	RouteController('api/bm_accounting', 'verify-partner'      , 'bm_accounting\VerifyPartnerCtrl');
	RouteController('api/bm_accounting', 'items'               , 'bm_accounting\ItemsCtrl');
	RouteController('api/bm_accounting', 'cash-in'             , 'bm_accounting\CashInCtrl');
	RouteController('api/bm_accounting', 'cash-out'             , 'bm_accounting\CashOutCtrl');
});