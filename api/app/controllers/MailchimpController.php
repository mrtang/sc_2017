<?php
use omsmodel\CustomerAdminModel;
use ordermodel\OrdersModel;
class MailchimpController extends \BaseController {
    public function __construct(){
        
    }
    //get list
    public function getList(){
        $lists = MailchimpWrapper::lists()->getList();
        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $lists['data']
        );
        return Response::json($contents);
    }
    
    //get list
    public function postSubscriber(){
        $Data       = Input::json()->all();
        $Id     = $Data['id'];
        //list user
        $User       = User::skip(0)->take(1000)->get(array('id','fullname','email','phone'))->toArray();
        if($User){
            $dataAdd = array();
            foreach($User AS $val){
                $batch[] = array(
                        'email'         =>  array('email' => $val['email']),
                        'email_type'    => 'html',
                        'merge_vars'    => array('FULLNAME'=>$val['fullname'], 'PHONE'=>$val['phone'])
                );
            }
        }
        
        //var_dump($batch);die;
        
        $result = MailchimpWrapper::lists()->batchSubscribe($Id,$batch,0,1,1);

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $result
        );
        return Response::json($contents);
    }
    //TEST
    public function getTest(){
        $Id     = 'e1af6805a1';
        $batch[] = array(
            'email'         =>  array('email' => 'page365.vn@gmail.com'),
            'email_type'    => 'html',
            'merge_vars'    => array('FULLNAME'=>'Page365', 'PHONE'=>'090989785622','FIRSTORDER' => 1,'MERGE7' => date("d M Y g:i a",1427184167),'CITY' => 'HaNoi')
        );

        $result = MailchimpWrapper::lists()->batchSubscribe($Id,$batch,0,1,1);
        $contents = array(
            'data'          => $result
        );
        return Response::json($contents);
    }
    //insert User first order
    public function getUserfirstorder(){
        $Id     = 'e1af6805a1';
        $timeStart = strtotime(date('Y-m-d 00:00:00',strtotime('-1 day',$this->time())));
        $timeEnd = strtotime(date('Y-m-d 00:00:00',$this->time()));
        $Model = new CustomerAdminModel;
        $listUserId = $batch = $dataBuild = array();
        //check send
        $checkSend = NotifyOrderModel::where('type','pickup')->where('time_start',$timeStart)->where('time_end',$timeEnd)->first();

        if(!empty($checkSend)){
            $contents = array(
                'error'         => true,
                'message'       => 'Not send 2nd!!',
            );
        }else{
            $listData = $Model->where('time_create','>',$timeStart)->where('time_create','<',$timeEnd)->where('first_order_time','>',0)->get(array('user_id','first_order_time'))->toArray();
            if(!empty($listData)){
                foreach($listData AS $one){
                    $listUserId[] = $one['user_id'];
                }
                $listUserInfo = User::whereIn('id',$listUserId)->get(array('id','fullname','email','phone'))->toArray();
                if(!empty($listUserInfo)){
                    foreach($listUserInfo AS $oneUser){
                        foreach($listData AS $oneData){
                            if($oneUser['id'] == $oneData['user_id']){
                                $oneData['email'] = $oneUser['email'];
                                $oneData['fullname'] = $oneUser['fullname'];
                                $oneData['phone'] = $oneUser['phone'];
                                $oneData['first_order'] = 1;
                                $dataBuild[] = $oneData;
                            }
                        }
                    }
                    if(!empty($dataBuild)){
                        foreach($dataBuild AS $val){
                            $batch[] = array(
                                'email'         =>  array('email' => $val['email']),
                                'email_type'    => 'html',
                                'merge_vars'    => array('FULLNAME'=>$val['fullname'], 'PHONE'=>$val['phone'],'FIRSTORDER' => $val['first_order'],'MERGE7' => date("d M Y g:i a",$val['first_order_time']))
                            );
                        }
                        $result = MailchimpWrapper::lists()->batchSubscribe($Id,$batch,0,1,1);
                        $result = json_decode($result,1);
                        if(empty($result['errors'])){
                            NotifyOrderModel::insert(array('type' => 'first_order','time_start' => $timeStart,'time_end' => $timeEnd,'time_send' => $this->time()));
                            $contents = array(
                                'error'         => false,
                                'message'       => 'Success!!!',
                            );
                        }else{
                            $contents = array(
                                'error'         => true,
                                'message'       => 'Fail!!!',
                                'data'          => json_encode($result['errors'])
                            );
                        }
                    }else{
                        $contents = array(
                            'error'         => true,
                            'message'       => 'Not data send!!'
                        );
                    }
                }else{
                    $contents = array(
                        'error'         => true,
                        'message'       => 'Not data send!'
                    );
                }
            }else{
                $contents = array(
                    'error'         => true,
                    'message'       => 'Not data send!!!'
                );
            }
        }
        return Response::json($contents);
    }
    //last order
    public function getUserordernotaccept(){

    }
    //merge data
    public function postMergecity(){
        $Data       = Input::json()->all();
        $Id     = $Data['id'];
        //list user
        $User       = User::limit(1000)->take(0)->get(array('id','fullname','email','phone'))->toArray();
        if($User){
            $listId = array();
            foreach($User AS $val){
                $listId[] = $val['id'];
                $arr_user[$val['id']] = $val['id'];
            }
            $address = OrderModel::whereIn('from_user_id',$listId)->get(array('id','from_address_id','from_user_id'))->toArray();
            if($address){
                $address_id = array();
                foreach($address AS $one){
                    $address_id[] = $one['from_address_id'];
                    if($arr_user[$one['from_user_id']] == $one['from_user_id']){
                        $arr_user_address[$one['from_user_id']] = $one['from_address_id'];
                    }
                }
                $city = OrderAddressModel::whereIn('id',$address_id)->get(array('id','city_id','seller_id'))->toArray();
                $arr_user_city = array();
                foreach($city AS $c){
                    $city_id_arr[] = $c['city_id'];
                    if($arr_user_address[$c['seller_id']] == $c['id']){
                        $arr_user_city[$c['seller_id']] = $c['city_id'];
                    }
                }
                $list_name_city = CityModel::whereIn('id',$city_id_arr)->get(array('id','city_name'))->toArray();
                if($list_name_city){
                    foreach($list_name_city AS $value){
                        foreach($arr_user_city AS $key=>$val){
                            if($val == $value['id']){
                                $arr_city_name_return[$key] = $value['city_name']; 
                            }
                        }
                    }
                }
                $name = '';
                foreach($User AS $val){
                    if(isset($arr_city_name_return[$val['id']])){
                        $name = $arr_city_name_return[$val['id']];
                        $batch[] = array(
                            'email'         =>  array('email' => $val['email']),
                            'email_type'    => 'html',
                            'merge_vars'    => array('FULLNAME'=>$val['fullname'], 'PHONE'=>$val['phone'],'CITY' => $name)
                        );
                    }
                }
                $result = MailchimpWrapper::lists()->batchSubscribe($Id,$batch,0,1,1);

                $contents = array(
                    'error'         => false,
                    'message'       => 'success',
                    'data'          => $result
                );
                return Response::json($contents);
            }
        }
    }


}
?>