<?php namespace order;
use Response;
use Input;
use DB;
use ordermodel\OrdersModel;
use sellermodel\UserInventoryModel;
use Cache;
use CourierController;
use CityModel;
use DistrictModel;
use CourierModel;
use WardModel, User;
use ordermodel\PostOfficeModel;
use CourierPostOfficeModel;



class PublicOrderController extends \BaseController {

    private function getPostoffice($ListOrderId){
        $return_data     = [];

        $OrderPostOffice = PostOfficeModel::whereIn('order_id', $ListOrderId)->where('time_create', '>=', $this->time() - $this->time_limit)->get();
        $ListBCC         = [];

        foreach ($OrderPostOffice as $key => $value) {
            if(!empty($value['to_postoffice_code'])){
                $ListBCC[] = $value['to_postoffice_code'];
            }
        }

        if(!empty($ListBCC)){
            $PostOfficeData = CourierPostOfficeModel::whereIn('bccode', $ListBCC)->get()->toArray();
            $ListPostOffice = [];
            foreach ($PostOfficeData as $key => $value) {
                $ListPostOffice[$value['bccode']] = $value;
            }

            foreach ($OrderPostOffice as $key => $value) {
                if (!empty($ListPostOffice[$value['to_postoffice_code']])) {
                    $return_data[$value['order_id']] = $ListPostOffice[$value['to_postoffice_code']];
                }
                
            }
        }
        return $return_data;
    }

    public function getPrintmulti($code,$json = true){
        $ListCode   = explode(',',$code);
        if(empty($ListCode)){
            $contents = array(
                'error'     => true,
                'message'   => 'empty code',
                'data'      => []
            );
            return Response::json($contents);
        }

        $Model              = new OrdersModel;
        $City       = [];
        $District   = [];
        $Ward       = [];
        $PostOffice = [];
        $ListCourierTracking = [];
        $Data       = $Model->where(function($query){
                        $query->where('time_accept','>=',$this->time() - $this->time_limit)
                      ->orWhere('time_accept',0);
                    })
                    ->whereIn('tracking_code',$ListCode)
                    ->with(array(
                        'OrderItem',
                        'OrderItems',
                        'ToOrderAddress',
                        'OrderDetail',
                        'MetaStatus',
                        'OrderTracking',
                        'OrderStatus'  => function($query){
                            $query->with('MetaStatus')->orderBy('time_create','DESC');
                        }
                    ))->get(array('id','tracking_code', 'checking', 'fragile','status','service_id','courier_id','total_weight', 'total_amount',
                        'total_quantity','to_name','to_phone','to_email','from_user_id','from_address_id', 'product_name',
                        'to_address_id','time_accept','time_create','estimate_delivery','from_city_id', 'verify_id',
                        'from_district_id','from_ward_id','from_address', 'order_code', 'time_success', 'domain'))
                    ->toArray();

        if(!empty($Data)){
            foreach($Data as $key => $val){

                $ListOrderId[] = (int)$val['id'];

                $Data[$key]['barcode']  = $this->getBarcode($val['tracking_code']);
                if((int)$val['from_city_id'] > 0){
                    $ListCityId[]       = (int)$val['from_city_id'];
                }

                if((int)$val['from_district_id'] > 0){
                    $ListDistrictId[]   = (int)$val['from_district_id'];
                }

                if((int)$val['from_ward_id'] > 0){
                    $ListWardId[]       = (int)$val['from_ward_id'];
                }

                if(!empty($val['to_order_address'])){
                    if((int)$val['to_order_address']['city_id'] > 0){
                        $ListCityId[]       = (int)$val['to_order_address']['city_id'];
                    }

                    if((int)$val['to_order_address']['province_id'] > 0){
                        $ListDistrictId[]   = (int)$val['to_order_address']['province_id'];
                    }

                    if((int)$val['to_order_address']['ward_id'] > 0){
                        $ListWardId[]       = (int)$val['to_order_address']['ward_id'];
                    }
                }
                if(!empty($val['order_tracking'])){
                    $ListCourierTracking[] = $val['order_tracking']['courier_tracking_id'];
                }
            }

            if(!empty($ListOrderId) && count($ListOrderId) == 1){
                $PostOffice = $this->getPostoffice($ListOrderId);
            }

            $CourierTracking = [];

            if($ListCourierTracking){
                $ListCourier = CourierModel::where('id', $ListCourierTracking)->select(['id', 'type_id', 'tracking_url', 'name'])->get()->toArray();
                foreach($ListCourier as $value){
                    $CourierTracking[$value['id']] = $value;
                }
            }
            if(!empty($ListCityId)){
                $CityModel  = new CityModel;
                $ListCityId = array_unique($ListCityId);
                $ListCity   = $CityModel->whereIn('id',$ListCityId)->get(array('id','city_name'))->toArray();
                if(!empty($ListCity)){
                    foreach($ListCity as $val){
                        $City[(int)$val['id']]  = $val['city_name'];
                    }
                }
            }

            if(!empty($ListDistrictId)){
                $DistrictModel  = new DistrictModel;
                $ListDistrictId = array_unique($ListDistrictId);
                $ListDistrict   = $DistrictModel->whereIn('id',$ListDistrictId)->get(array('id','district_name'))->toArray();
                if(!empty($ListDistrict)){
                    foreach($ListDistrict as $val){
                        $District[(int)$val['id']]  = $val['district_name'];
                    }
                }
            }

            if(!empty($ListWardId)){
                $WardModel  = new WardModel;
                $ListWardId = array_unique($ListWardId);
                $ListWard   = $WardModel->whereIn('id',$ListWardId)->get(array('id','ward_name'))->toArray();
                if(!empty($ListWard)){
                    foreach($ListWard as $val){
                        $Ward[(int)$val['id']]  = $val['ward_name'];
                    }
                }
            }
        }

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $Data,
            'city'      => $City,
            'district'  => $District,
            'ward'      => $Ward,
            'postoffice'    => $PostOffice,
            'header'        => getallheaders(),
            'courier_tracking'=> $CourierTracking
        );
        return $json ? Response::json($contents) : $contents;
    }

    public function getShow($code)
    {
        $Type       = Input::has('type') ? strtolower(Input::get('type')) : 'detail';
        $Status     = [];
        $Model      = new OrdersModel;

        $Model      = $Model
                    ->where(function($query){
                        $query->where('time_accept','>=',$this->time() - $this->time_limit)
                            ->orWhere('time_accept',0);
                    })
                    ->where('tracking_code',strtoupper(trim($code)))
                    ->with(['ToOrderAddress', 'pipe_journey'=> function ($query){
                        return $query->orderBy('id', 'DESC');
                    }]);

        if($Type == 'detail'){
            $Data       = $this->getDetail($Model);
            $Status     = $this->getStatus();
        }else{
            $Data = $this->getPrint($Model);
        }

        if(!empty($Data)){
            $CourierController = new CourierController;
            $Courier    = $CourierController->getCache();
            if(isset($Courier[$Data->courier_id])){
                $Data->courier  = $Courier[$Data->courier_id];
            }

            $City       = $this->getCity();
            if(isset($Data->to_order_address->city_id) && isset($City[$Data->to_order_address->city_id])){
                $Data->to_city  = $City[$Data->to_order_address->city_id];
            }
            if(isset($City[$Data->from_city_id])){
                $Data->from_city  = $City[$Data->from_city_id];
            }

            $Service    = $this->getService();
            if(isset($Service[$Data->service_id])){
                $Data->service  = $Service[$Data->service_id];
            }

            if(isset($Data->to_order_address->province_id) && $Data->to_order_address->province_id > 0){
                $ProvinceId[]   = $Data->to_order_address->province_id;
            }

            if($Data->from_district_id > 0){
                $ProvinceId[]   = $Data->from_district_id;
            }

            if(!empty($ProvinceId)){
                $Province   = $this->getProvince($ProvinceId);
                if(isset($Province[$Data->to_order_address->province_id])){
                    $Data->to_province  = $Province[$Data->to_order_address->province_id];
                }
                if(isset($Province[$Data->from_district_id])){
                    $Data->from_province  = $Province[$Data->from_district_id];
                }
            }

            if(isset($Data->to_order_address->ward_id) && $Data->to_order_address->ward_id > 0){
                $WardId[]   = $Data->to_order_address->ward_id;
            }

            if($Data->from_ward_id > 0){
                $WardId[]   = $Data->from_ward_id;
            }

            if(!empty($WardId)){
                $Ward   = $this->getWard($WardId);
                if(isset($Ward[$Data->to_order_address->ward_id])){
                    $Data->to_ward  = $Ward[$Data->to_order_address->ward_id];
                }
                if(isset($Ward[$Data->from_ward_id])){
                    $Data->from_ward  = $Ward[$Data->from_ward_id];
                }
            }
            $Ward       = $this->getWard([$Data->to_order_address->ward_id]);

            $PostOffice = $this->getPostoffice([$Data->id]);

            $Data->from_user = (new \User)->select('id', 'city_id', 'district_id', 'address', 'phone', 'fullname')->find($Data->from_user_id);
            $Data->from_user->city = (isset($City[$Data->from_user['city_id']]) ? $City[$Data->from_user['city_id']] : "");
            $Data->from_user->district = (isset($Province[$Data->from_user['district_id']]) ? $Province[$Data->from_user['district_id']] : "");
            $Data->from_user->address = (isset($Ward[$Data->from_user['address']]) ? $Ward[$Data->from_user['address']] : "");
        }

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data,
            'status'        => $Status,
            'post_office'   => !empty($PostOffice) ? $PostOffice : []
        );

        return Response::json($contents);
    }

    public function getPrint($code){
        $ListCode   = explode(',',$code);
        if(empty($ListCode)){
            $contents = array(
                'error'     => true,
                'message'   => 'empty code',
                'data'      => []
            );
            return Response::json($contents);
        }

        $Model      = new OrdersModel;
        $Data       = $Model->where(function($query){
                            $query->where('time_accept','>=',$this->time() - $this->time_limit)
                                ->orWhere('time_accept',0);
                        })->whereIn('tracking_code',$ListCode)->with(['OrderItem', 'OrderItems', 'ToOrderAddress','OrderDetail'])->get(array('id','tracking_code','service_id','courier_id', 'from_user_id','to_name','to_phone','to_email','from_address_id','status',
                        'from_city_id', 'from_district_id', 'from_ward_id', 'from_address', 'to_address_id','time_create','time_accept', 'product_name', 'total_quantity', 'total_weight', 'total_amount', 'checking', 'fragile','domain'))->toArray();

        if(!empty($Data)){
            // get  data
            $CourierController  = new CourierController;
            $Courier            = $CourierController->getCache();
            $City               = $this->getCity();
            $Service            = $this->getService();
            $Status             = $this->getStatus();
            $ListBarcode        = [];
            $ListProvince       = [];
            $ListCityGlobal     = [];
            $ListWard           = [];
            $ListUser           = [];
            $ListUserId         = [];
            $ListInventory      = [];
            $ListCountry        = [];
            foreach($Data as $val){
                if($val['from_district_id'] > 0){
                    $ListProvince[] = (int)$val['from_district_id'];
                }
                if($val['from_ward_id'] > 0){
                    $ListWard[] = (int)$val['from_ward_id'];
                }
                if($val['from_address_id'] > 0){
                    $ListInventory[]    = (int)$val['from_address_id'];
                }

                $ListBarcode[$val['tracking_code']] = $this->getBarcode($val['tracking_code']);

                if(isset($val['to_order_address']['province_id']) && $val['to_order_address']['province_id'] > 0){
                    $ListProvince[] = (int)$val['to_order_address']['province_id'];
                }

                if(isset($val['to_order_address']['ward_id']) && $val['to_order_address']['ward_id'] > 0){
                    $ListWard[]                 = (int)$val['to_order_address']['ward_id'];
                }

                if(isset($val['to_order_address']['city_id']) && $val['to_order_address']['city_id'] > 0 && $val['to_order_address']['country_id'] !== 237){
                    $ListCityGlobal[]   = (int)$val['to_order_address']['city_id'];
                    $ListCountry[]      = (int)$val['to_order_address']['country_id'];
                    
                    
                }

                $ListUserId[]     = (int)$val['from_user_id'];
            }
 
            $ListProvince   = array_unique($ListProvince);
            $ListWard       = array_unique($ListWard);
            $ListCityGlobal = array_unique($ListCityGlobal);
            


            if(!empty($ListProvince)){
                $Province   = $this->getProvince($ListProvince);
            }

            if(!empty($ListWard)){
                $Ward   = $this->getWard($ListWard);
            }

            if(!empty($ListCityGlobal)){
                $CityGlobal   = $this->getCityGlobal($ListCountry, $ListCityGlobal);
                foreach($CityGlobal as $key=>$value){
                    $City[$key] = $value;
                }
            }

            if(!empty($ListInventory)){
                $ListInventory = UserInventoryModel::whereIn('id',$ListInventory)->get(['id','user_name','phone'])->toArray();
                if(!empty($ListInventory)){
                    foreach($ListInventory as $val){
                        $Inventory[(int)$val['id']]  = $val;
                    }
                }
            }

            $UserModel  = new \User;
            $ListUser = $UserModel::whereIn('id',$ListUserId)->get(['id','fullname','email','phone'])->toArray();
            if(!empty($ListUser)){
                foreach($ListUser as $val){
                    $User[$val['id']]   = $val;
                }
            }

            $LabelConfig = new \fulfillmentmodel\SellerLabelConfigModel;
            $LabelConfig = $LabelConfig::whereIn('user_id', $ListUserId)->get()->toArray();
            $Labels = [];

            if(!empty($LabelConfig)){
                foreach($LabelConfig as $val){
                    $Labels[$val['user_id']]   = $val;
                }
            }
        }

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data,
            'status'        => isset($Status) ? $Status : [],
            'courier'       => isset($Courier) ? $Courier : [],
            'city'          => isset($City) ? $City : [],
            'service'       => isset($Service) ? $Service : [],
            'province'      => isset($Province) ? $Province : [],
            'ward'          => isset($Ward) ? $Ward : [],
            'inventory'     => isset($Inventory) ? $Inventory : [],
            'barcode'       => isset($ListBarcode) ? $ListBarcode : [],
            'user'          => isset($User) ? $User : [],
            'label'         => isset($Labels) ? $Labels : []
        );

        return Response::json($contents);
    }

    private function getDetail($Model){
        $Data = $Model->with(array(
            'OrderStatus' => function($query){
                $query->orderBy('id', 'DESC');
            }
        ))->first(array('id','verify_id','tracking_code','service_id','courier_id','to_name','to_phone','to_email','status','to_address_id','time_create','time_accept', 'time_success', 'from_user_id'));

        return $Data;
    }
}
