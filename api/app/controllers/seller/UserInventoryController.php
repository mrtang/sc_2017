<?php namespace seller;

use Validator;
use Response;
use Input;
use Exception;
use sellermodel\UserInventoryModel;
use Guzzle\Http\Client;
use Lang;

class UserInventoryController extends \BaseController {
    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function postCreate()
    {
        /**
        *  Validation params
        * */
        
        $UserInfo   = $this->UserInfo();
        $UserId = (int)$UserInfo['id'];
        
        Validator::getPresenceVerifier()->setConnection('metadb');
        
        $validateParams = [
            'id'            => 'sometimes|numeric|min:1|exists:user_inventory,id',
            'country_id'    => 'sometimes|numeric|min:1',
            'city_id'       => 'sometimes|required|numeric|min:1',
            'zipcode'       => 'sometimes',
            'address'       => 'sometimes|required',
            'active'        => 'sometimes|boolean',
            'delete'        => 'sometimes|boolean',
        ];

        $CountryId          = Input::json()->get('country_id');

        if(empty($CountryId) || $CountryId == 237){
            $validateParams['province_id']  = 'sometimes|required|numeric|min:1';
            $validateParams['ward_id']      = 'sometimes|required|numeric|min:1';
            
        }
        
        

        $validation = Validator::make(Input::json()->all(), $validateParams);
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }
        
        /**
         * Get Data 
         * */
        $Id                 = Input::json()->get('id');
        $Name               = Input::json()->get('name');
        $UserName           = Input::json()->get('user_name');
        $Phone              = Input::json()->get('phone');
        $CityId             = Input::json()->get('city_id');
        $ProvinceId         = Input::json()->get('province_id');
        $WardId             = Input::json()->get('ward_id');
        $Zipcode            = Input::json()->get('zipcode');
        
        $Address            = Input::json()->get('address');
        $Active             = Input::json()->get('active');
        $Delete             = Input::json()->get('delete');
        
        $Model              = new UserInventoryModel;

        if($Id > 0){
            $Data   = $Model::find($Id);
            if(!empty($Name))           $Data->name         = $Name;
            if(!empty($UserName))       $Data->user_name    = $UserName;
            
            if(isset($Active))          $Data->active       = $Active;
            if(isset($Delete))          $Data->delete       = $Delete;

            if(isset($WardId))          $Data->ward_id      = $WardId;

            if(isset($CountryId))       $Data->country_id   = $CountryId;
            if(isset($Zipcode))         $Data->zipcode      = $Zipcode;
            
            
            if(!empty($Phone) && gettype($Phone) == 'string'){
                $Data->phone        = $Phone;
            }elseif(!empty($Phone) && gettype($Phone) == 'array'){
                $_phone = [];
                foreach($Phone as $val){
                    if(!empty($val['text'])){
                        $_phone[] = $val['text'];
                    }
                }
                $_phone = implode(',', $_phone);
                $Data->phone = $_phone;

            }

            try{
                $Data->save();
                $contents = [
                    'error'     => false,
                    'message'   => 'success',
                    'data'        => $Id
                ];
            }catch (Exception $e){
                $contents = array(
                    'error'     => true,
                    'message'   => 'update false',
                    'data'      => $Id
                );
            }

        }else{
            try{
                if(!empty($Phone) && gettype($Phone) == 'string'){
                
                }elseif(!empty($Phone) && gettype($Phone) == 'array'){
                    $_phone = [];
                    foreach($Phone as $val){
                        if(!empty($val['text'])){
                            $_phone[] = $val['text'];
                        }
                    }
                    $_phone = implode(',', $_phone);
                    $Phone  = $_phone;
                }

                $Id = $Model->insertGetId([
                    'user_id'       => $UserId,
                    'name'          => $Name,
                    'user_name'     => $UserName,
                    'phone'         => $Phone,
                    'country_id'    => !empty($CountryId) ? $CountryId : 237,
                    'city_id'       => $CityId,
                    'province_id'   => $ProvinceId,
                    'zipcode'       => $Zipcode,
                    'ward_id'       => $WardId,
                    'address'       => $Address,
                    'time_create'   => $this->time()
                ]);
                $contents = [
                    'error'     => false,
                    'message'   => 'success',
                    'data'        => $Id
                ];
            }catch (Exception $e){
                $contents = array(
                    'error'     => true,
                    'message'   => 'update false'.$e->getMessage(),
                    'data'      => []
                );
            }
        }
        
        return Response::json($contents);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function getShow()
    {
        /**
        *  Validation params
        * */
        
        $UserInfo   = $this->UserInfo();
        $Id         = (int)$UserInfo['id'];
        
        $Model      = new UserInventoryModel;
        $Data       = $Model::where('user_id','=',$Id)->where('delete',0)->where('active',1)->with(array(
                                                                'city',
                                                                'district',
                                                                'ward',
                                                                'country'
                                                                ))->orderBy('id', 'DESC')->get()->toArray();
        

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $Data
        );
        
        return Response::json($contents);
    }
    /*
     *
     *
     * */
    public function getCheckWard(){
        $UserInfo   = $this->UserInfo();
        $Id =   (int)$UserInfo['id'];

        $Model      = new UserInventoryModel;
        $Data       = $Model::where('user_id','=',$Id)->where('ward_id', 0)->where('delete',0)->where('active',1)->with(array(
            'city',
            'district',
            'ward',
        ))->orderBy('id', 'DESC')->get()->toArray();


        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $Data
        );

        return Response::json($contents);
    }


    public function getShowWithPostoffice(){
        $Lat    = Input::has('lat') ? Input::get('lat') : 0;
        $Lng    = Input::has('lng') ? Input::get('lng') : 0;
        $Limit  = Input::has('limit') ? Input::get('limit') : 10;
        $idbc   = Input::has('bc')   ? Input::get('bc')   : 0;

        $source   = Input::has('source')   ? Input::get('source')   : "";


        

        $UserInfo   = $this->UserInfo();
        $Id         = (int)$UserInfo['id'];
        
        $Model              = new UserInventoryModel;
        $InventoryModel     = $Model::where('user_id', '=', $Id)
                                ->where('delete',0)
                                ->where('active',1);

        if($source == 'shipchung'){
            $InventoryModel = $InventoryModel->whereNull('warehouse_code');
        }

        if($source == 'boxme'){
            $InventoryModel = $InventoryModel->whereNotNull('warehouse_code');
        }


        $InventoryModel = $InventoryModel->with(array(
                                'city',
                                'district',
                                'ward',
                                'country'
                        ))
                        ->orderBy('id', 'DESC');
        
        $InventoryModel = $InventoryModel->get()->toArray();
        $Data = [];

        foreach ($InventoryModel as $key => $value) {

            $full_address   = [];
            $full_address[] = $value['address'];

            if(!empty($value['ward'])){
                $full_address[] = $value['ward']['ward_name'];
            }

            if(!empty($value['district'])){
                $full_address[] = $value['district']['district_name'];
            }

            if(!empty($value['city'])){
                $full_address[] = $value['city']['city_name'];
            }

            if(!empty($value['country'])){
                $full_address[] = $value['country']['country_name'];
            }

            $item = [
                'id'                => $value['id'],
                'address'           => $value['address'],
                'name'              => $value['name'],
                'phone'             => $value['phone'],
                'province_id'       => $value['province_id'],
                'country_id'        => $value['country_id'],
                'city_id'           => $value['city_id'],
                'ward_id'           => $value['ward_id'],
                
                'province'          => $value['district'],
                'city'              => $value['city'],
                'ward'              => $value['ward'],
                'country'           => $value['country'],
                'user_name'         => $value['user_name'],
                'full_address'      => implode(', ', $full_address),
                'warehouse_code'    => $value['warehouse_code'],
                'inventory'         => Lang::get('response.MY_INVENTORY'),
            ];

            // Thêm group kho boxme 
            if(!empty($value['warehouse_code'])){
                $item['inventory'] = Lang::get('response.BM_INVENTORY');
                array_unshift($Data, $item);
            }else {
                $Data[] = $item;
            }

        }
        $PostOffice = [];
        

        // if(!empty($Lat) &&  !empty($Lng) && !empty($Data)){
        //     $Model = new \CourierPostOfficeModel;
        //     $Model = $Model->select('*', DB::raw("ROUND(3959 * acos (cos ( radians($Lat) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians($Lng) ) + sin ( radians($Lat) ) * sin( radians( lat ))), 2) AS distance"));
        //     $PostOffice = $Model
        //         ->having('distance', '<=', 5)
        //         ->where('courier_id', '!=', 2)
        //         ->where('lat', '>', 0)
        //         ->where('lng', '>', 0)
        //         ->where('verified',  1)
        //         ->orderBy('distance', 'ASC')
        //         ->with(array(
        //                 'city',
        //                 'district',
        //                 'ward',
        //         ))
        //         ->take($Limit)
        //         ->get()->toArray();
        // }

        // if (!empty($idbc)) {
        //     $ModelBC = new \CourierPostOfficeModel;
        //     $ModelBC = $ModelBC->select('*', DB::raw("ROUND(3959 * acos (cos ( radians($Lat) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians($Lng) ) + sin ( radians($Lat) ) * sin( radians( lat ))), 2) AS distance"))
        //                        ->where('id', $idbc)
        //                        ->with(array(
        //                                 'city',
        //                                 'district',
        //                                 'ward',
        //                         ))
        //                         ->first();
          
        //     if ($ModelBC) {
        //         $PostOffice[] = $ModelBC;
        //     }
        // }

        

        
        // foreach ($PostOffice as $key => $value) {
        //     $Name = $value['name'];
        //     if($value['courier_id'] == 1){
        //         $Name = 'Vietelpost '.$Name;
        //     }
        //     $Data[] = [
        //         'id'            => $value['id'],
        //         'address'       => $value['address'],
        //         'name'          => $Name,
        //         'bccode'        => $value['bccode'],
        //         'phone'         => $value['phone'],
        //         'province_id'   => $value['district_id'],
        //         'city_id'       => $value['city_id'],
        //         'ward_id'       => $value['ward_id'],

        //         'province'      => $value['district'],
        //         'city'          => $value['city'],
        //         'ward'          => $value['ward'],

        //         'user_name'     => $Name,
        //         'full_address'  => $value['address'],
        //         'distance'      => $value['distance'],
        //         'inventory'     => Lang::get('response.BRING_TO_POSTOFFICE'),
        //         'post_office'    => true
        //     ];
        // }

        return Response::json([
            'error'         => false,
            'error_message' => "",
            'source'        => $source,
            'data'          => $Data
        ]);

    }

    public function getRequestGooogle($address = ''){
        $client = new Client('http://maps.googleapis.com/maps/api/geocode/json');
        $params = array(
            'address' => $address,
            'sensor'  => false
        );

        $client->setDefaultOption('query', $params);
        $params   = $client->getDefaultOption('query');
        $request  = $client->get();
        $response = $request->send()->json();
        $LatLng   = [];
        if(sizeof($response['results']) > 0){
            $Location = $response['results'][0]['geometry']['location'];
            $LatLng = [
                'lat' => $Location['lat'],
                'lng' => $Location['lng'],
            ];
        }
        return $LatLng;
    }

    public function getSyncLatlng (){
        set_time_limit(300);
        $Model  = new UserInventoryModel;
        $Data   = $Model
                ->where('delete',0)
                ->where('lat', 0)
                ->where('lng', 0)
                ->take(100)
                ->with(array('city', 'district', 'ward'))
                ->orderBy('id', 'DESC')
                ->get();
        
        if($Data->isEmpty()){
            return 'DONE';
        }

        foreach ($Data as $key => $value) {
            $address   = [];
            $address[] = $value->address;

            if ($value->ward && strpos($value->address, $value->ward->ward_name) === false) {
                $address[] = $value->ward->ward_name;
            }

            if ($value->district && strpos($value->address, $value->district->district_name) === false) {
                $address[] = $value->district->district_name;
            }

            if ($value->city && strpos($value->address, $value->city->city_name) === false) {
                $address[] = $value->city->city_name;
            }else {
                $address   = [];
                $address[] = $value->address;
            }

            $address =  implode($address, ', ');
            $LatLng  =  $this->getRequestGooogle($address);
            if(!empty($LatLng)){
                
                $value->lat = $LatLng['lat'];
                $value->lng = $LatLng['lng'];
                try {
                    $value->save();
                } catch (Exception $e) {
                    
                }
            }
        }
        return 'COUNTINUE';

    }
}
