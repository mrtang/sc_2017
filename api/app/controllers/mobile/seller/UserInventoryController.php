<?php namespace mobile_seller;

use Validator;
use Response;
use Input;
use Exception;
use DB;
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

        if (!$UserId) {
            return Response::json(array('error' => true, 'message' => 'NOT_ACCESS', 'error_message' => 'You are not permitted to perform this action'), 403);
        }
        
        Validator::getPresenceVerifier()->setConnection('sellerdb');
        
        $validation = Validator::make(Input::all(), array(
            'id'            => 'sometimes|numeric|min:1|exists:user_inventory,id',
            'city_id'       => 'sometimes|required|numeric|min:1',
            'province_id'   => 'sometimes|required|numeric|min:1',
            'ward_id'       => 'sometimes|numeric|min:0',
            'address'       => 'sometimes|required',
            'active'        => 'sometimes',
            'delete'        => 'sometimes|boolean',
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }
        
        /**
         * Get Data 
         * */

        $Id         = Input::get('id');
        $Name       = Input::get('name');
        $UserName   = Input::get('user_name');
        $Phone      = Input::get('phone');
        $CityId     = Input::get('city_id');
        $ProvinceId = Input::get('province_id');
        $WardId     = Input::get('ward_id');
        $Address    = Input::get('address');
        $Active     = Input::get('active');
        $Delete     = Input::get('delete');
        
        $Model              = new UserInventoryModel;

        if($Id > 0){
            $Data   = $Model::find($Id);
            if(!empty($Name))           $Data->name      = $Name;
            if(!empty($UserName))       $Data->user_name = $UserName;
            
            if(isset($Active))          $Data->active    = $Active;
            if(isset($Delete))          $Data->delete    = $Delete;
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
                    'error'         => false,
                    'message'       => 'success',
                    'error_message' => 'Cập nhật thành công !',
                    'data'          => $Id
                ];
            }catch (Exception $e){
                $contents = array(
                    'error'         => true,
                    'message'       => $e->getMessage(),
                    'error_message' => 'Cập nhật thất bại',
                    'data'          => $Id
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
                    $Phone = $_phone;
                }

                $Id = $Model->insertGetId([
                    'user_id'       => $UserId,
                    'name'          => $Name,
                    'user_name'     => $UserName,
                    'phone'         => $Phone,
                    'city_id'       => $CityId,
                    'province_id'   => $ProvinceId,
                    'ward_id'       => $WardId,
                    'address'       => $Address,
                    'time_create'   => time()
                ]);

                $contents = [
                    'error'         => false,
                    'message'       => 'success',
                    'error_message' => 'Tạo kho hàng thành công !',
                    'data'          => $Id
                ];
            }catch (Exception $e){
                $contents = array(
                    'error'             => true,
                    'message'           => 'QUERY_ERROR',
                    'exception_message' => $e->getMessage(),
                    'error_message'     => 'Lỗi khi tạo kho, vui lòng thử lại',
                    'data'              => []
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
        
        $Active   = Input::has('active') ? Input::get('active') : 1;
        $UserInfo = $this->UserInfo();
        $Id       = (int)$UserInfo['id'];
        
		$Model      = new UserInventoryModel;
        $Data       = $Model::where('user_id','=',$Id)->where('delete',0)->with(array(
                                                                'city',
                                                                'district',
                                                                'ward',
                                                                ))->orderBy('id', 'DESC')->take(10)->get()->toArray();
        foreach ($Data as $key => $value) {
            $add = [];
            $add[] = $value['address'];

            if(!empty($value['ward'])){
                $add[] = $value['ward']['ward_name'];
            }

            if(!empty($value['district'])){
                $add[] = $value['district']['district_name'];
            }

            if(!empty($value['city'])){
                $add[] = $value['city']['city_name'];
            }

            $Data[$key]['_address'] = implode(', ', $add);
        }

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

            $item = [
                'id'                => $value['id'],
                'address'           => $value['address'],
                'name'              => $value['name'],
                'phone'             => $value['phone'],
                'province_id'       => $value['province_id'],
                'city_id'           => $value['city_id'],
                'ward_id'           => $value['ward_id'],
                'province'          => $value['district'],
                'city'              => $value['city'],
                'ward'              => $value['ward'],
                'user_name'         => $value['user_name'],
                'full_address'      => implode(', ', $full_address),
                'warehouse_code'    => $value['warehouse_code'],
                'inventory'         => Lang::get('response.MY_INVENTORY'),
                'type'              => 'shipchung'
            ];

            // Thêm group kho boxme 
            if(!empty($value['warehouse_code'])){
                $item['inventory']  = Lang::get('response.BM_INVENTORY');
                $item['type']       = 'boxme';
                
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
        //         'warehouse_code'    => "",                
        //         'user_name'     => $Name,
        //         'full_address'  => $value['address'],
        //         'distance'      => $value['distance'],
        //         'inventory'     => Lang::get('response.BRING_TO_POSTOFFICE'),
        //         'post_office'   => true,
        //         'type'          => 'buucuc'
        //     ];
        // }

        return Response::json([
            'error'         => false,
            'error_message' => "",
            'source'        => $source,
            'data'          => $Data
        ]);
    }

    
}
