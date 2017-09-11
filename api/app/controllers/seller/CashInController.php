<?php namespace seller;

use Validator;
use Response;
use Exception;
use Input;
use Cache;
use Config;
use DB;
use sellermodel\CashInModel;
use accountingmodel\MerchantModel;
use accountingmodel\TransactionModel;

class CashInController extends \BaseController {
    private $MasterId   = 1;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function getIndex()
    {
        return Config::get('constants');
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function postCreate()
    {
        $UserInfo   = $this->UserInfo();

        /**
         *  Validation params
         * */
        $validation = Validator::make(Input::json()->all(), array(
            'type'              => 'required|numeric|in:1,2',
            'amount'             => 'required|numeric|min:10000'
        ));

        //error
        if($validation->fails()) {
            return Response::json(array(
                'error'         =>  true, 
                'message'       =>  $validation->messages(), 
                'error_message' =>  'Dữ liệu gửi lên không đúng'
            ));
        }

        /**
         * Get Data
         **/

        $Type               = (int)Input::json()->get('type');
        $Amount             = trim(Input::json()->get('amount'));

        $Model              = new CashInModel;
        $Model->user_id     = $UserInfo['id'];
        $Model->amount      = $Amount;
        $Model->type        = $Type;
        $Model->status      = 'WAITING';
        $Model->time_create = $this->time();

        
        $contents = array(
            'error'         => false,
            'message'       => 'success'
        );

        $OrderCode  = 'SC_NT_'.$UserInfo['id'].'_'.$this->time();

        if($Type == 1){
            $buyer_info        = $UserInfo['fullname'].'*|*'.$UserInfo['email'].'*|*'.$UserInfo['phone'].'*|*';
            $params = array(
                'merchant_site_code'    => strval(Config::get('constants.MERCHANT_ID_SC')),
                'return_url'            => strval(Config::get('constants.LINK_SELLER').'app/cash/'.$OrderCode),
                'receiver'              => strval(Config::get('constants.RECEIVER_EMAIL')),
                'transaction_info'      => strval('Nạp tiền phí'),
                'order_code'            => strval($OrderCode),
                'price'                 => strval($Amount),
                'currency'              => strval('vnd'),
                'quantity'              => strval(1),
                'tax'                   => strval(0),
                'discount'              => strval(0),
                'fee_cal'               => strval(0),
                'fee_shipping'          => strval(0),
                'order_description'     => strval('Nạp tiền phí vận chuyển vào tài khoản trên shipchung.vn'),
                'buyer_info'            => strval($buyer_info),
                'affiliate_code'        => ''
            );

            $secure_code = implode(' ', $params) . ' ' . Config::get('constants.MERCHANT_PASS_SC');

            $params['secure_code']  = md5($secure_code);
            $params['cancel_url']  = strval('http://seller.shipchung.vn');

            $url = Config::get('config_api.API_POST_NL').'?'.http_build_query($params);
            //$url = 'https://www.nganluong.vn/checkout.api.nganluong.post.php'.'?'.http_build_query($params);


            $Model->refer_code          = $OrderCode;

            $contents = [
                            'error'     => false,
                            'message'   => 'success',
                            'url'       => $url
                        ];
        }else {

            $validation = Validator::make(Input::json()->all(), array(
                'transfer_bank'         => 'required',
                'card_name'             => 'required',
                'card_number'           => 'required',
                'transfer_time'         => 'required',
                'transfer_body'         => 'required',
            ));

            //error
            if($validation->fails()) {
                return Response::json(array(
                    'error'         =>  true, 
                    'message'       =>  $validation->messages(), 
                    'error_message' =>  'Dữ liệu gửi lên không đúng'
                ));
            }

            $TransferBank   = Input::get('transfer_bank');
            $CardName       = Input::get('card_name');
            $CardNumber     = Input::get('card_number');
            $TransferTime   = Input::get('transfer_time');
            $TransferBody   = Input::get('transfer_body');
            $TransferCode   = Input::get('transfer_code');
            

            $Model->transfer_bank   = $TransferBank;
            $Model->card_name       = $CardName;
            $Model->card_number     = $CardNumber;
            $Model->transfer_time   = $TransferTime;
            $Model->transfer_body   = $TransferBody;
            $Model->transfer_code   = $TransferCode;
            

        }

        

        try {
            $Data = $Model->save();
        } catch (Exception $e) {
            $contents = array(
                'error'     => true,
                'message'   => 'INSERT FALSE'
            );
        }
        return Response::json($contents);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function getResultnl()
    {
        $UserInfo   = $this->UserInfo();

        $validation = Validator::make(Input::all(), array(
            'code'              => 'required',
            'token_nl'          => 'required',
            'secure_code'       => 'required',
            'price'             => 'required'
        ));

        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

        $OrderCode      = Input::get('code');
        $Token          = Input::get('token_nl');
        $Price          = Input::get('price');
        $SecureCode     = Input::get('secure_code');
        $PaymentId      = Input::get('payment_id');
        $PaymentType    = (int)Input::get('payment_type');
        $ErrorText      = urldecode(Input::get('error_text'));
        $TransactionInfo= urldecode(Input::get('transaction_info'));

        $Model      = new CashInModel;
        $CashIn     = $Model->where('user_id',(int)$UserInfo['id'])
                            ->where('refer_code',$OrderCode)
                            ->where('status','WAITING')
                            ->where('time_create','>=',$this->time() - $this->time_limit)
                            ->first();

        if(!isset($CashIn->id)){
            return Response::json([
                                        'error'         => true,
                                        'message'       => 'NOT_EXISTS'
                                    ]);
        }

        // Check
        $verify_secure_code = ' '.strval($TransactionInfo) . ' ' . strval($OrderCode) . ' ' . strval($Price) . ' ' . strval($PaymentId) .
                            ' ' . strval($PaymentType) . ' ' . strval($ErrorText) . ' ' . strval(Config::get('constants.MERCHANT_ID_SC')) .
                            ' ' . strval(Config::get('constants.MERCHANT_PASS_SC'));

        $verify_secure_code = md5($verify_secure_code);

        if($verify_secure_code === $SecureCode){
            try {
                $CashIn->transaction_id = $PaymentId;
                $CashIn->status         = 'PROCESSING';
                $CashIn->save();
                DB::connection('accdb')->commit();
                $contents = array(
                    'error'     => false,
                    'message'   => 'SUCCESS',
                    'amount'    => $CashIn->amount,
                    'type'      => $CashIn->type

                );

            } catch (Exception $e) {
                $contents = array(
                    'error'     => true,
                    'message'   => 'UPDATE_FALSE',
                    'amount'    => $CashIn->amount
                );
            }
        }else{
            return Response::json([
                'error'         => true,
                'message'       => 'CASHIN_NL_FAIL',
                'error_code'    => $ErrorText
            ]);
        }

        return Response::json($contents);
    }

    function encryptData($data, $publicKey) {
        $rsa = new Crypt_RSA();
        $rsa->loadKey($publicKey); // public key
        $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
        $output = $rsa->encrypt($data);
        return base64_encode($output);
    }


    function decryptData($data, $publicKey) {
        $rsa = new Crypt_RSA();
        $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
        $ciphertext = base64_decode($data);
        $rsa->loadKey($publicKey); // public key
        $output = $rsa->decrypt($ciphertext);
        // $output = $rsa->decrypt($data);
        return $output;
    }

    function decryptCallbackData($data, $publicKey){
        $decoded = base64_decode($data);
        return decryptData($decoded, $publicKey);
    }

    public function getCreateAlepay(){
        $Amount         = 10000;
        $OrderCode      = 'SC123456789';

        $Params = [
            'orderCode'         => $OrderCode,
            'amount'            => $Amount,
            'currency'          => 'VND',
            'orderDescription'  => 'Nạp tiền phí vận chuyển vào tài khoản trên shipchung.vn',
            'totalItem'         => 1,
            'checkoutType'      => 1,
            'bankCode'          => 'VIETINBANK',
            'returnUrl'         => strval(Config::get('constants.LINK_SELLER').'app/cash/'.$OrderCode),
            'cancelUrl'         => strval(Config::get('constants.LINK_SELLER').'app/cash/'.$OrderCode),
            'buyerName'         => 'Shipchung',
            'buyerEmail'        => 'shipchung@gmail.com',
            'buyerPhone'        => '01232032828',
            'buyerAddress'      => '12A 18 Tam Trinh Hai Bà Trưng Hà Nội',
            'buyerCity'         => 'Hà Nội',
            'buyerCountry'      => 'Việt Nam',
            'paymentHours'      => 1
        ];

        $dataEncrypt = $this->encryptData(json_encode($Params),Config::get('config_api.ALEPAY_ENCRYPT_KEY'));
        //echo json_encode($params);
        $checksum = md5($dataEncrypt . Config::get('config_api.ALEPAY_CHECKSUM_KEY'));
        //var_dump($this->URL['requestPayment']);die;
        $items = array(
            'token' => Config::get('config_api.ALEPAY_API_KEY'),
            'data' => $dataEncrypt ,
            'checksum' => $checksum,
            'ip' => "10.0.0.1"
        );
        $data_string = json_encode($items);
        $ch = curl_init($this->URL['requestPayment']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
        curl_close($ch);
        return $result = json_decode($result);

    }
}
