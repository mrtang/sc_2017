<?php
use crmmodel\CustomfieldModel;
use crmmodel\OpportunityModel;
use crmmodel\OwnerModel;
use crmmodel\PersonModel;
use crmmodel\UserCrmModel;
use crmmodel\EmailModel;
use crmmodel\ContactModel;
use omsmodel\SellerModel;
use ordermodel\OrdersModel;
use omsmodel\CustomerAdminModel;
use sellermodel\BusinessModel;

class ZurmoController extends \BaseController {
    private $domain = '*';
    /**
     * Display a listing of the resource.
     *
     * @return Response
     * @params $scenario, $user_id
     */
     
    public function __construct(){
        
    }

    //
    public function getOppo(){
        $listWon = CustomfieldModel::where('value','Closed Won')->get(array('id'))->toArray();
        if(!empty($listWon)){
            $listIdWon = array();
            foreach($listWon AS $one){
                $listIdWon[] = $one['id'];
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not Won!!'
            );
            return Response::json($contents);
        }
        $oppotunity = OpportunityModel::where('sync_ops',0)->whereIn('stage_customfield_id',$listIdWon)->first();
        if(!empty($oppotunity)){
            $owner = OwnerModel::where('id',$oppotunity['ownedsecurableitem_id'])->first();
            if(!empty($owner)){
                $infoOwner = UserCrmModel::where('id',$owner['owner__user_id'])->first();
                if(!empty($infoOwner)){
                    $personOwner = PersonModel::where('id',$infoOwner['person_id'])->first();
                    $emailOwner = EmailModel::where('id',$personOwner['primaryemail_email_id'])->first();
                    $infoOwnerInShipchung = User::where('email',$emailOwner['emailaddress'])->first();
                    if(empty($infoOwnerInShipchung)){
                        $up = OpportunityModel::where('id',$oppotunity['id'])->update(array('sync_ops' => 3));
                        $contents = array(
                            'error'     => true,
                            'message'   => 'Owner not in shipchung!!'
                        );
                        return Response::json($contents);
                    }
                    $customer = ContactModel::where('account_id',$oppotunity['account_id'])->first();
                    $customerInZurmo = PersonModel::where('id',$customer['person_id'])->first();
                    $emailCustomer = EmailModel::where('id',$customerInZurmo['primaryemail_email_id'])->first();
                    $infoCustomerInShipchung = User::where('email',$emailCustomer['emailaddress'])->first();
                    if(empty($infoCustomerInShipchung)){
                        $up = OpportunityModel::where('id',$oppotunity['id'])->update(array('sync_ops' => 2));
                        $contents = array(
                            'error'     => true,
                            'message'   => 'Customer not in shipchung!!'
                        );
                        return Response::json($contents);
                    }
                    $check = SellerModel::where('user_id',$infoCustomerInShipchung['id'])->first();
                    //check order
                    $timeCheck = strtotime(date('Y-m-01 00:00:00',$this->time()));
                    $timePickupCheck = 2*86400 + $check['last_time_pickup'];
                    $checkOrder = OrdersModel::where('from_user_id',$infoCustomerInShipchung['id'])->where('time_create','>',$timeCheck - 30 * 86400)->where('time_create','<',$timeCheck)->where('time_accept','>',$timeCheck - 30 * 86400)->where('time_accept','<',$timeCheck)->first();
                    if(empty($check)){
                        $dataInsert = array(
                            'seller_id' => $infoOwnerInShipchung['id'],
                            'user_id'  => $infoCustomerInShipchung['id'],
                            'time_sync_insightly' => $this->time()
                        );
                        $insert = SellerModel::insert($dataInsert);
                        if($insert){
                            $up = OpportunityModel::where('id',$oppotunity['id'])->update(array('sync_ops' => 1,'time_sync_ops' => $this->time()));
                            $contents = array(
                                'error'     => false,
                                'message'   => 'Insert success!!'
                            );
                            return Response::json($contents);
                        }else{
                            $contents = array(
                                'error'     => true,
                                'message'   => 'Do not insert 1!!'
                            );
                            return Response::json($contents);
                        }
                    }elseif(!empty($check)){
                        $timeWon = strtotime(date($check['stagemodifieddatetime'],$this->time()));//xem co qua 2 ngay ko
                        if($check['seller_id'] > 0 && empty($checkOrder) && $check['release'] > 0){
                            $update = SellerModel::where('id',$check['id'])->update(array('seller_id' => $infoOwnerInShipchung['id'],'time_sync_insightly' => $this->time(),'active' => 1));
                            if($update){
                                $up = OpportunityModel::where('id',$oppotunity['id'])->update(array('sync_ops' => 1,'time_sync_ops' => $this->time()));
                                $contents = array(
                                    'error'     => false,
                                    'message'   => 'Update success1!!'
                                );
                                return Response::json($contents);
                            }else{
                                $contents = array(
                                    'error'     => true,
                                    'message'   => 'Do not update 1!!'
                                );
                                return Response::json($contents);
                            }
                        }elseif($check['seller_id'] == 0){
                            $update = SellerModel::where('id',$check['id'])->update(array('seller_id' => $infoOwnerInShipchung['id'],'time_sync_insightly' => $this->time(),'active' => 1));
                            if($update){
                                $up = OpportunityModel::where('id',$oppotunity['id'])->update(array('sync_ops' => 1,'time_sync_ops' => $this->time()));
                                $contents = array(
                                    'error'     => false,
                                    'message'   => 'Update success3!!'
                                );
                                return Response::json($contents);
                            }else{
                                $contents = array(
                                    'error'     => true,
                                    'message'   => 'Do not update 3!!'
                                );
                                return Response::json($contents);
                            }
                        }elseif($check['release'] == 0 && $check['first_time_pickup'] > $timeCheck && $timeWon < $timePickupCheck){
                            $update = SellerModel::where('id',$check['id'])->update(array('seller_id' => $infoOwnerInShipchung['id'],'time_sync_insightly' => $this->time(),'active' => 1));
                            if($update){
                                $up = OpportunityModel::where('id',$oppotunity['id'])->update(array('sync_ops' => 1,'time_sync_ops' => $this->time()));
                                $contents = array(
                                    'error'     => false,
                                    'message'   => 'Update success4!!'
                                );
                                return Response::json($contents);
                            }else{
                                $contents = array(
                                    'error'     => true,
                                    'message'   => 'Do not update 4!!'
                                );
                                return Response::json($contents);
                            }
                        }elseif($check['release'] == 0 && $timeWon > $timePickupCheck){
                            $update = SellerModel::where('id',$check['id'])->update(array('seller_id' => $infoOwnerInShipchung['id'],'time_sync_insightly' => $this->time(),'active' => 0));
                            if($update){
                                $up = OpportunityModel::where('id',$oppotunity['id'])->update(array('sync_ops' => 1,'time_sync_ops' => $this->time()));
                                $contents = array(
                                    'error'     => false,
                                    'message'   => 'Update success4!!'
                                );
                                return Response::json($contents);
                            }else{
                                $contents = array(
                                    'error'     => true,
                                    'message'   => 'Do not update 4!!'
                                );
                                return Response::json($contents);
                            }
                        }else{
                            $update = OpportunityModel::where('id',$oppotunity['id'])->update(array('sync_ops' => 5,'time_sync_ops' => $this->time()));
                            if($update){
                                $contents = array(
                                    'error'     => false,
                                    'message'   => 'Success Nupdate!!'
                                );
                                return Response::json($contents);
                            }else{
                                $contents = array(
                                    'error'     => true,
                                    'message'   => 'Do not update 5!!'
                                );
                                return Response::json($contents);
                            }
                        }
                    }else{
                        $update = OpportunityModel::where('id',$oppotunity['id'])->update(array('sync_ops' => 1,'time_sync_ops' => $this->time()));
                        if($update){
                            $contents = array(
                                'error'     => false,
                                'message'   => 'Update success5!!'
                            );
                            return Response::json($contents);
                        }else{
                            $contents = array(
                                'error'     => true,
                                'message'   => 'Do not update 5!!'
                            );
                            return Response::json($contents);
                        }
                    }
                }else{
                    $contents = array(
                        'error'     => true,
                        'message'   => 'Not info owner in zurmo!!'
                    );
                    return Response::json($contents);
                }
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not owner in zurmo!!'
                );
                return Response::json($contents);
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data sync!!'
            );
            return Response::json($contents);
        }
    }
    private function login($username, $password)
    {
        $headers = array(
            'Accept: application/json',
            'ZURMO_AUTH_USERNAME: ' . $username,
            'ZURMO_AUTH_PASSWORD: ' . $password,
            'ZURMO_API_REQUEST_TYPE: REST',
        );
        $response = ApiRestHelper::createApiCall('http://crm.boxme.asia/index.php/zurmo/api/login', 'POST', $headers);
        $response = json_decode($response, true);
        if ($response['status'] == 'SUCCESS')
        {
            return $response['data'];
        }
        else
        {
            return false;
        }
    }

    //dong bo tu shipchung len zurmo
    public function getSync(){
        $timeStart = strtotime(date('Y-m-d 00:00:00',$this->time())) - 86400;
        $authenticationData = $this->login('shipchung.vn','shipchung@123');
        $headers = array(
            'Accept: application/json',
            'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
            'ZURMO_TOKEN: ' . $authenticationData['token'],
            'ZURMO_API_REQUEST_TYPE: REST',
        );
        $data = CustomerAdminModel::where('sync_zurmo',0)->where('first_order_time',0)->where('time_create','<',$timeStart)->where('time_create','>',$this->time() - 3*86400)->first();
        if(!empty($data)){
            //kiem tra xem da co sales quan ly chua
            $check = SellerModel::where('user_id',$data['user_id'])->first();
            if(!empty($check) && $check['seller_id'] > 0){
                $update = CustomerAdminModel::where('id',$data['id'])->update(array('sync_zurmo' => 2));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success update!!'
                );
                return Response::json($contents);
            }
            //thong tin kinh doanh
            $infoBusiness = BusinessModel::where('user_id',$data['user_id'])->first();
            if(!empty($infoBusiness['name'])){
                $companyName = $infoBusiness['name'];
            }else{
                $companyName = 'Unknown';
            }
            if(!empty($infoBusiness['website'])){
                $website = $infoBusiness['website'];
            }else{
                $website = '';
            }
            //lay ra tinh thanh pho
            $city = BaseController::getCity();
            //
            $infoUser = User::where('id',$data['user_id'])->first();
            if($infoUser['phone'] == ''){
                $update = CustomerAdminModel::where('id',$data['id'])->update(array('sync_zurmo' => 2));
                $contents = array(
                    'error'     => true,
                    'message'   => 'Not phone!!'
                );
                return Response::json($contents);
            }
            //kiem tra xem ton tai trong Lead chua
            $paramSearch = array('search' => array('mobilePhone' => $infoUser['phone']));
            $searchParamsQuery = http_build_query($paramSearch);
            $checkLead = ApiRestHelper::createApiCall('http://crm.boxme.asia/index.php/contacts/contact/api/list/filter/'.$searchParamsQuery,'GET',$headers);
            $checkLead = json_decode($checkLead,1);
            if ($checkLead['status'] == 'SUCCESS'){
                $update = CustomerAdminModel::where('id',$data['id'])->update(array('sync_zurmo' => 3));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Updated!!'
                );
                return Response::json($contents);
            }
            if($infoUser['city_id'] > 0){
                $cityName = $city[$infoUser['city_id']];
            }else{
                $cityName = '';
            }
            if($infoUser['city_id'] > 0 && $infoUser['city_id'] < 37){
                $region = 'North';
            }elseif($infoUser['city_id'] >= 37 && $infoUser['city_id'] < 70){
                $region = 'South';
            }else{
                $region = 'None';
            }
            if(strlen($infoUser['fullname']) > 32){
                $name = substr($infoUser['fullname'], 0,32);
            }else{
                $name = $infoUser['fullname'];
            }
            $dataSync = array(
                'firstName' => $infoUser['identifier'],
                'lastName' => $name,
                'jobTitle' => '',
                'department' => '',
                'officePhone' => '',
                'mobilePhone' => $infoUser['phone'],
                'officeFax' => '',
                'description' => '',
                'companyName' => $companyName,
                'website' => $website,
                'industry' => array('value' => 'None'),
                'source' => array('value' => 'Marketing Shipchung'),
                'state' => array('id' => 1),
                'primaryEmail' => array('emailAddress' => $infoUser['email'],'optOut' => 0),
                'primaryAddress' => array('city' => $cityName,'country' => 'Vietnam'),
                'orderperdayCstm' => array('value' => 'None'),
                'regionalCstm' => array('value' => $region),
                //'modelRelations' => array('contacts' => array(array('action' => 'add','modelId' => 31)))
            );

            $response = ApiRestHelper::createApiCall('http://crm.boxme.asia/index.php/contacts/contact/api/create/', 'POST', $headers, array('data' => $dataSync));
            $response = json_decode($response, true);
            if ($response['status'] == 'SUCCESS'){
                $update = CustomerAdminModel::where('id',$data['id'])->update(array('sync_zurmo' => 1));
                $contents = array(
                    'error'     => false,
                    'message'   => 'Success!!',
                    'data'      => $response
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => $response['errors']
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Not data sync!!'
            );
        }
        return Response::json($contents);
    }

    //tesst
    public function getUser(){
        $User = User::where('time_create','>',1464714000)->where('time_create','<',1465578000)->get(array('id','fullname','email','phone'))->toArray();
        foreach($User AS $U){
            $listId[] = $U['id'];
        }
        $count = CustomerAdminModel::where('first_order_time','>',0)->whereIn('user_id',$listId)->count();

        var_dump($count);

        $countOrder = OrdersModel::whereIn('from_user_id',$listId)->count();
        var_dump($countOrder);die;
        $Data = OrdersModel::where('time_accept','>',$timeStart)->where('time_accept','<',$timeEnd)->groupBy('from_user_id')->having('count','>',$num)->get(array('from_user_id',DB::raw('count(*) as count')));
    }
    //doi nv quanly
    public function getChangesales(){
        $eCustom = Input::get('ecustom');
        $eSales  = Input::get('esales');
        //
        $infoCustom = User::where('email',$eCustom)->first();
        $infoSales  = User::where('email',$eSales)->first();
        //check xem co trong new customer chua
        $check = SellerModel::where('user_id',$infoCustom['id'])->first();
        if($check){
            $update = SellerModel::where('id',$check['id'])->update(array('seller_id' => $infoSales['id']));
            if($update){
                $response = array(
                    'error'         => false,
                    'message'       => 'Thành công'
                );
            }else{
                $response = array(
                    'error'         => true,
                    'message'       => 'Loi cap nhat'
                );
            }
        }else{
            $response = array(
                'error'         => true,
                'message'       => 'Chua ton tai user'
            );
        }
        return Response::json($response);
    }
    //get user MKT
    public function getUsermkt(){
        $tStart = 1464714000;
        $tEnd = 1485882000;
        //get user
        //$user = User::where('time_create','>',$tStart)->where('time_create','<',$tEnd)->get(array('email','phone','fullname'))->toArray();
        //Kh dang ky co tao don
        $uOrder = CustomerAdminModel::where('time_create','>',$tStart)->where('time_create','<',$tEnd)->where('first_accept_order_time','>',0)->get(array('user_id'))->toArray();
        if(!empty($uOrder)){
            foreach($uOrder AS $od){
                $lUser[] = $od['user_id'];
            }
        }
        $user = User::whereIn('id',$lUser)->get(array('email','phone','fullname'))->toArray();
        $html = '<table>';
        foreach($user AS $one){
            $html .= '<tr><td>'.$one['email'].'</td><td>'.$one['phone'].'</td><td>'.$one['fullname'].'</td></tr>';
        }
        echo $html.'</table>';

    }
}
?>