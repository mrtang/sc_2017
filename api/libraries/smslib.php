<?php

require_once app_path().'/libraries/nusoap.php';
use Artisaninweb\SoapWrapper\Facades\SoapWrapper;

class Smslib {
    
    private static function utf8convert($str) {
    	$str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", "a", $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", "e", $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", "o", $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", "u", $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
        $str = preg_replace("/(đ)/", "d", $str);
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", "A", $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", "E", $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", "I", $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", "O", $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", "U", $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", "Y", $str);
        $str = preg_replace("/(Đ)/", "D", $str);
        //$str = str_replace(" ", "-", str_replace("&*#39;","",$str));
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        return $str;
    }
    
    public static function send(){
        $LMongo         = new LMongo;
        $dbData = $LMongo::collection('log_send_sms')->where('status',0)->take(10)->get()->toArray();
        //return self::_process_sending_viettel();
        //return $dbData;
        if(!$dbData){
            die('NOT_EXIST_DATA_SMS');
        }       
        
        foreach($dbData as $value){
            if($value['telco'] == 'viettel'){
                self::_process_sending_viettel_soap($value);
            }
            else{
                self::_process_sending($value);
            }
        }
    }
    
    private static function _process_sending($dbData = array()) {
        // Add a new service to the wrapper
        SoapWrapper::add(function ($service) {
            $service
                ->name('SoapClient')
                ->wsdl('http://g3g4.vn/smsws/services/SendMT?wsdl')
                ->trace(true);
        });

        $data        = [
            'username'     =>  'shipchung',
            'password'     =>  'shipchung123',
            'receiver'     =>  $dbData['to_phone'],
            'content'      =>  self::utf8convert($dbData['content']),
            'loaisp'       =>  $dbData['telco'] == 'viettel' ? 2 : 1, // 1: Gửi đầu só | 2: brand name
            'brandname'    =>  $dbData['telco'] == 'viettel' ? 'Shipchung' : '',
            'target'       =>  microtime(),
        ];
       
        // Using the added service
        SoapWrapper::service('SoapClient', function ($service) use ($data, $dbData) {
            //var_dump($service->getFunctions());die;
            $LMongo         = new LMongo;
        
            try {
                $result     = $service->call('sendSMS', [$data])->return;
                $arrSms     = array();
                
                if(!$result)
                {
                    echo 'Loi Khong gui dc SMS';die;
                }
        
                var_dump($result);echo '<hr>';//die;
        
                $error = explode('|',$result);
                # insert du lieu vao bang log_sms
                $arrSms['status']          = $error[0] == 0 ? 1 : 2;
                $arrSms['error_result']    = $result;
                $arrSms['time_send']       = time();
                // Update lai log SMS
                $update = $LMongo::collection('log_send_sms')->where('_id',new \MongoId($dbData['_id']))->update($arrSms);
                if($update){
                    dd($arrSms);die;
                }
                die('error');
            }
            catch (SoapFault $soapFault) {
                if($soapFault){
                    var_dump($soapFault);
                    echo "Request :<br>", htmlentities($service->__getLastRequest()), "<br>";
                    echo "Response :<br>", htmlentities($service->__getLastResponse()), "<br>";
                }
            }
            
        });
    }
    
    private static function _process_sending_viettel_soap($dbData = array()) {
        $LMongo         = new LMongo;
        // Define Info API - WS Viettel Post
        define('USER_WEBSERVICE_VTP','Peacesoft');
        define('PASS_WEBSERVICE_VTP','123gcs456a@');
        define('KEY_WEBSERVICE_VTP','6D3A33F6EE2A17923869220CA65D1D02');
        define('LINK_WEBSERVICE_VTP','http://203.113.131.101/VTPSMS/Sms.asmx?wsdl');
        define('LINK_WEBSERVICE_VTP_LOGIN','http://203.113.131.101/VTPSMS/UserName.asmx?wsdl');
        $soapClient         = new nusoapclient(LINK_WEBSERVICE_VTP, 'wsdl');
        $soapClientLogin    = new nusoapclient(LINK_WEBSERVICE_VTP_LOGIN, 'wsdl');
        $soapClient->soap_defencoding = 'UTF-8';
        $soapClientLogin->soap_defencoding = 'UTF-8';
        
                // Login VTP
                $loginParams = array(
                    'userName'  => USER_WEBSERVICE_VTP,
                    'passWord'  => PASS_WEBSERVICE_VTP,
                ); 
                  
                $resultLogin = $soapClientLogin->call('Login',$loginParams);  //var_dump($resultLogin);die;
        
        $token = (string)$resultLogin['LoginResult']['iTOKENKEY'];
        $headers = <<<EOT
            <ServiceAuthHeader xmlns="http://viettelshop.vn">
              <Token>$token</Token>
            </ServiceAuthHeader>
EOT;

        //var_dump($header);die;
        $soapClient->setHeaders($headers);
        
        $smsParams = array(
                    'wsContent'     => (string) self::utf8convert($dbData['content']),
                    'wsMobifone'    => (string) '84'.substr($dbData['to_phone'],1), // 84986008112
                    'wsStatus'      => (int) 1,
                    'wsCreateby'    => (int) 1
                );
        
        $result     =  $soapClient->call('InsertSMSMSG',$smsParams);
        $arrSms     = array();
        
        if(isset($result['InsertSMSMSGResult'])){
            # insert du lieu vao bang log_sms
            $arrSms['status']          = (int)$result['InsertSMSMSGResult'];
            $arrSms['error_result']    = $result;
            $arrSms['time_send']       = time();
            
            // Update lai log SMS
            $update = $LMongo::collection('log_send_sms')->where('_id',new \MongoId($dbData['_id']))->update($arrSms);
            if($update){
                dd($arrSms);die;
            }
            die('error');
        }
        
        var_dump($result);echo '<hr><br>';
    }
    
    
    private static function _process_sending_viettel(){
        // Add SoapWrapper Login
        SoapWrapper::add(function ($service) {
            $service->name('SoapClientLogin')->wsdl('http://203.113.131.101/VTPSMS/UserName.asmx?wsdl')->trace(true);
        });
        
        // Using the added service
        SoapWrapper::service('SoapClientLogin', function ($service){
            // Login VTP
            $loginParams = array(
                'userName'  => 'Peacesoft',
                'passWord'  => '123gcs456a@',
            );
            //var_dump($service->getFunctions());die;
            try {
                $resultLogin    = $service->call('Login', [$loginParams])->LoginResult;
                //dd($resultLogin);die;
                $token      = (string)$resultLogin->iTOKENKEY;
                $headers    = '<ServiceAuthHeader xmlns="http://viettelshop.vn">
                                <Token>'.$token.'</Token>
                                </ServiceAuthHeader>';

                SoapWrapper::add(function ($service) use ($headers){
                    $service->name('SoapClient')->wsdl('http://203.113.131.101/VTPSMS/Sms.asmx?wsdl')
                        ->header('http://schemas.xmlsoap.org/soap/encoding/','',$headers)->trace(true);
                });
                
                SoapWrapper::service('SoapClient', function ($service) {
                    $smsParams = array(
                        'wsContent'     => (string) 'test abc xyz',//$this->utf8convert($this->dataLog['content']),
                        'wsMobifone'    => (string) '84988768120',//'84'.substr($this->dataLog['to_phone'],1),
                        'wsStatus'      => (int) 1,
                        'wsCreateby'    => (int) 1
                    );
                    
                    try {
                        $result = $service->call('InsertSMSMSG', [$smsParams])->InsertSMSMSGResult;
                        dd($result);
                    }
                    catch (SoapFault $soapFault) {
                        if($soapFault){
                            var_dump($soapFault);
                            echo "Request :<br>", htmlentities($service->__getLastRequest()), "<br>";
                            echo "Response :<br>", htmlentities($service->__getLastResponse()), "<br>";
                        }
                    }
                });
                
            }
            catch (SoapFault $soapFault) {
                if($soapFault){
                    var_dump($soapFault);
                    echo "Request :<br>", htmlentities($service->__getLastRequest()), "<br>";
                    echo "Response :<br>", htmlentities($service->__getLastResponse()), "<br>";
                }
            }
            
        });


        $resultLogin = $soapClientLogin->call('Login',$loginParams);

        var_dump($resultLogin);

        $token = (string)$resultLogin['LoginResult']['iTOKENKEY'];
        $headers = '<ServiceAuthHeader xmlns="http://viettelshop.vn">
              <Token>'.$token.'</Token>
            </ServiceAuthHeader>';
        //var_dump($header);die;
        $soapClient->setHeaders($headers);

        var_dump($this->dataLog);echo '<hr>';
        $smsParams = array(
                    'wsContent'     => (string) $this->utf8convert($this->dataLog['content']),
                    'wsMobifone'    => (string) '84'.substr($this->dataLog['to_phone'],1),
                    'wsStatus'      => (int) 1,
                    'wsCreateby'    => (int) 1
                );

        $result =  $soapClient->call('InsertSMSMSG',$smsParams);

        if(isset($result['InsertSMSMSGResult'])){
            $this->CI->load->model('_log_model','log_model');
            # insert du lieu vao bang log_sms
            $arrSms['status']          = (int)$result['InsertSMSMSGResult'];
            $arrSms['error_result']    = $result;
            $arrSms['time_send']       = time();
            $this->CI->log_model->update('log_send_sms',array('_id' => new MongoId($this->dataLog['_id'])),$arrSms);
        }
        var_dump($result);echo '<hr><br>';
    }
        
    // Kiểm tra số điện thoại có hợp lệ hay ko và convert về số hợp lệ
    public static function CheckPhone($phone)
    {        
        $arrViettel = array('096','097','098','016');
        $arrMobi    = array('090','093','0120','0121','0122','0126','0128');
        $arrVina    = array('091','094','0123','0124','0125','0127','0129');
        $arrVietnam = array('092','0188');
        $arrBeeline = array('0996','0199');
        $arrSfone   = array('095');
        
        $phone      = str_replace(' ','',trim($phone));
        $phone      = str_replace('.','',$phone);
        $phone      = str_replace('+84','0',$phone);
        
        if (substr($phone,0,3) == 084) {
            $phone      = str_replace('084','0',$phone);
        }
        
        if(strlen($phone) < 10 || strlen($phone) > 11){
            return null;
        }
        
        $dau3so = substr($phone,0,3);
        $dau4so = substr($phone,0,4);
        
        if(in_array($dau3so,$arrViettel)){
            return 'viettel';
        }

        if(in_array($dau3so,$arrMobi) || in_array($dau4so,$arrMobi)){
            return 'mobi';
        }
        
        if(in_array($dau3so,$arrVina) || in_array($dau4so,$arrVina)){
            return 'vina';
        }
        
        if(in_array($dau3so,$arrVietnam) || in_array($dau4so,$arrVietnam)){
            return 'vietnam';
        }
        
        if(in_array($dau4so,$arrBeeline)){
            return 'beeline';
        }
        
        if(in_array($dau4so,$arrSfone)){
            return 'sfone';
        }
        
        return null;
    }
    
}