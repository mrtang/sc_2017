<?php
/**
 * Facebook OAuth2 Provider
 *
 * @package    CodeIgniter/OAuth2
 * @category   Provider
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 * @license    http://philsturgeon.co.uk/code/dbad-license
 */
error_reporting(E_ALL);

class ShipchungClient
{
	protected $scope = array('login', 'email', 'read_stream');
    public $client_id, $client_secret;


    // You need get url authorize before linked your customer account to user shipchung account

	public function get_url_authorize()
	{
		return "http://mc.shipchung.vn/openid.html?response_type=code&client_id=".$this->client_id."&state=login";
	}

    // when user login shipchung success, shipchung redirect to uri and added your code (example: http://fb.page365.vn/linked_account_shipchung.html?code=b1f91770d0e43ad7bc16fc91394f1e71a4be263c
    // you need call this function get a access token
	function get_access_token($grant_type = 'client_credentials',$code =''){
        $url = "http://api.shipchung.vn/oauth_token.php";
        switch($grant_type){
            case "authorization_code":
                    $params = array(
                                'grant_type'                    => 'authorization_code',
                                'code'                          => $code,
                                'client_id'                     => $this->client_id,
                                'client_secret'                 => $this->client_secret
                            );
                    break;
            default:
                $params = array(
                                'client_id'                     => $this->client_id,
                                'client_secret'                 => $this->client_secret,
                                'grant_type'                    => 'client_credentials'
                            );
        }

		// Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL            => $url . '?grant_type=' . $grant_type,
           // CURLOPT_USERAGENT    => 'Shipchung Sample cURL Request',
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => $params
        ));
        // Send the request & save response to $resp
        $resp = curl_exec($curl);
        // Close request to clear up some resources
        curl_close($curl);
        return $resp;
	}
    // when you linked your account , you want to call another api (GetUserInfo, Calculate, create lading... )
    function call_api($function,$access_token, $params)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://api.shipchung.vn/shipchung_public_api_1.0.php');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        if(isset($params))
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
                                                                                    'function'             => $function,
                                                                                    'access_token'         => $access_token,
                                                                                    'params'                => $params
                                                                                                        ))
            );
        }

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }


}
