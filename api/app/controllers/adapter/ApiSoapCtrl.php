<?php
header('Content-Type: application/json');
use Artisaninweb\SoapWrapper\Facades\SoapWrapper;

class ApiSoapCtrl extends \BaseController {

    
    function GetErrorMessage($error_code) {
		$arrCode = array(
    		'00'=>  'Không có lỗi, thành công!',
    		'99'=>  'Lỗi không được định nghĩa hoặc không rõ nguyên nhân',
    		'01'=>  'Lỗi trùng mã vận đơn',
    		'02'=>  'Địa chỉ IP không được chấp nhận',
    		'03'=>  'Sai tham số gửi tới (có tham số sai tên hoặc kiểu dữ liệu)',
    		'04'=>  'Tên hàm API gọi tới không hợp lệ (không tồn tại)',
    		'05'=>  'Sai version của API',
    		'06'=>  'Mã truy cập không tồn tại hoặc chưa được kích hoạt',
    		'07'=>  'Sai mật khẩu truy cập',
            
    		'08'=>  'Không hỗ trợ lấy hàng',
    		'09'=>  'Không hỗ trợ giao hàng',
    		'10'=>  'Không hỗ trợ dịch vụ chuyển phát',
        );
        
		return $arrCode[(string)$error_code];
	}
        
        
    public function getCreateorder()
    {
        // Add a new service to the wrapper
        SoapWrapper::add(function ($service) {
            $service
                ->name('SoapClient')
                ->wsdl('http://ems.com.vn/shipchung/EMS_ShipChung.asmx?WSDL')
                ->trace(true)                                                     // Optional: (parameter: true/false)
                //->header()                                                      // Optional: (parameters: $namespace,$name,$data,$mustunderstand,$actor)
                //->cookie()                                                      // Optional: (parameters: $name,$value)
                //->location()                                                    // Optional: (parameter: $location)
                //->cache(WSDL_CACHE_NONE)                                        // Optional: Set the WSDL cache
                //->options(['login' => 'username', 'password' => 'password'])    // Optional: Set some extra options
                ;
        });

        $data = [
            "pass"                  => "ems!@#",
            "OrderNumber"           => "#123456",
            "TrackingNumber"        => 'SC1416897526',//"SC".time(),
            "ServiceCode"           => 2,
            "ShipperName"           => "Kiên Nguyễn",
            "ShipperPhone"          => "090909090",
            "PickupAddress"         => "Xóm liều",
            "ConsigneeName"         => "Trần Quốc Tuấn",
            "ConsigneePhone"        => "0988889999",
            "ConsigneeAddress"      => "Hà Thành",
            "PickupZipCode"         => 20720,
            "ConsigneeZipCode"      => 20700,
            "Description"           => "Hàng không cần mô tả",
            "MoneyCollectAmount"    => 200000,
            "Weight"                => 250,
            "Volume"                => 0,
            "UseCoDService"         => 1,
            "UseInsuranceService"   => 1,
            "UseCoChecking"         => 1,
        ];

        // Using the added service
        SoapWrapper::service('SoapClient', function ($service) use ($data) {
            //var_dump($service->getFunctions());
            //var_dump($service->call('CREAT_ORDER', [$data])->CREAT_ORDERResult);
            
            try {
                $respond    = $service->call('create', [$data])->createResult;
                echo $respond;
                $decode     = json_decode($respond);
                
                if($decode->error != '00'){
                    die($decode->error_message);
                }
                
                echo 'Code: '.$decode->data->CheckingNumber;
                echo '<br>CoD: '.$decode->data->CoDAmount;
                echo '<br>BH: '.$decode->data->UseInsuranceService;
                
            }
            catch (SoapFault $soapFault) {
                var_dump($soapFault);
                echo "Request :<br>", htmlentities($service->__getLastRequest()), "<br>";
                echo "Response :<br>", htmlentities($service->__getLastResponse()), "<br>";
            }
            
        });
    }
    
    public function getUpdateorder()
    {
        // Add a new service to the wrapper
        SoapWrapper::add(function ($service) {
            $service
                ->name('SoapClient')
                ->wsdl('http://ems.com.vn/shipchung/EMS_ShipChung.asmx?WSDL')
                ->trace(true)                                                     // Optional: (parameter: true/false)
                //->header()                                                      // Optional: (parameters: $namespace,$name,$data,$mustunderstand,$actor)
                //->cookie()                                                      // Optional: (parameters: $name,$value)
                //->location()                                                    // Optional: (parameter: $location)
                //->cache(WSDL_CACHE_NONE)                                        // Optional: Set the WSDL cache
                //->options(['login' => 'username', 'password' => 'password'])    // Optional: Set some extra options
                ;
        });

        $data = [
            "pass"                  => "ems!@#",
            "TrackingNumber"        => "SC6272308411",
            "MoneyCollectAmount"    => 410000,
            "Weight"                => 890,
            //"Status"                => 'NEW',
            "ShipperName"           => "Lê Thị Đan Thục",
        ];

        // Using the added service
        SoapWrapper::service('SoapClient', function ($service) use ($data) {
            //var_dump($service->getFunctions());die;
            var_dump($service->call('update', [$data])->updateResult);
        });
    }
    
    public function getDetailorder()
    {
        // Add a new service to the wrapper
        SoapWrapper::add(function ($service) {
            $service
                ->name('SoapClient')
                ->wsdl('http://ems.com.vn/shipchung/EMS_ShipChung.asmx?WSDL')
                ->trace(true)                                                     // Optional: (parameter: true/false)
                //->header()                                                      // Optional: (parameters: $namespace,$name,$data,$mustunderstand,$actor)
                //->cookie()                                                      // Optional: (parameters: $name,$value)
                //->location()                                                    // Optional: (parameter: $location)
                //->cache(WSDL_CACHE_NONE)                                        // Optional: Set the WSDL cache
                //->options(['login' => 'username', 'password' => 'password'])    // Optional: Set some extra options
                ;
        });

        $data = [
            "pass"                  => "ems!@#",
            "TrackingNumber"        => "SC6650364308"
        ];

        // Using the added service
        SoapWrapper::service('SoapClient', function ($service) use ($data) {
            //var_dump($service->getFunctions());die;
            $result = json_decode($service->call('detail', [$data])->detailResult,1);
            print_r($result);
        });
    }
    
    public function getJourneyorder()
    {
        // Add a new service to the wrapper
        SoapWrapper::add(function ($service) {
            $service
                ->name('SoapClient')
                ->wsdl('http://ems.com.vn/shipchung/EMS_ShipChung.asmx?WSDL')
                ->trace(true)                                                     // Optional: (parameter: true/false)
                //->header()                                                      // Optional: (parameters: $namespace,$name,$data,$mustunderstand,$actor)
                //->cookie()                                                      // Optional: (parameters: $name,$value)
                //->location()                                                    // Optional: (parameter: $location)
                //->cache(WSDL_CACHE_NONE)                                        // Optional: Set the WSDL cache
                //->options(['login' => 'username', 'password' => 'password'])    // Optional: Set some extra options
                ;
        });

        $data = [
            "pass"                  => "ems!@#",
            "TrackingNumber"        => "SC6650364308"
        ];

        // Using the added service
        SoapWrapper::service('SoapClient', function ($service) use ($data) {
            //var_dump($service->getFunctions());die;
            $result = json_decode($service->call('journey', [$data])->journeyResult,1);
            print_r($result);
        });
    }
}