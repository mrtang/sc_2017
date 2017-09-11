<?php
use \ordermodel\BuyerModel;
use \sellermodel\UserInventoryModel;

class ElasticSyncController extends \BaseController {
		const SYNC_BUYER = "last_sync_buyer_id";
		private function getLastSync(){
		$get = \LMongo::connection('elasticsearch')->collection('sync_logs')->where('type', self::SYNC_BUYER)->first();
		if(empty($get)){
			return false;
		}
		return $get['value'];
	}

	private function setLastSync($value){
		$get = \LMongo::connection('elasticsearch')->collection('sync_logs')->where('type', self::SYNC_BUYER)->first();
		if(empty($get)){
			\LMongo::connection('elasticsearch')->collection('sync_logs')->insert([
				'type'	=> self::SYNC_BUYER,
				'value'	=> $value
			]);
		}else {
			\LMongo::connection('elasticsearch')->collection('sync_logs')->where('type', self::SYNC_BUYER)->update(array('value' => $value));
		}
	}


	public function getSyncBuyer(){
		set_time_limit(1800);
		
		$_total = 0;
		$Model  = new BuyerModel;
		$Data   = $Model->join('order_address', 'order_buyer.address_id', '=' , 'order_address.id')->orderBy('order_buyer.id', 'ASC');

		
		$LastSync = $this->getLastSync();
		if($LastSync){
			$Data = $Data->where('order_buyer.id', '>', $LastSync);
		}

	    $Data  = $Data->where('order_address.time_update', '>', $this->time() - 30 * 86400);

	    if($_total == 0){
	        $_total = clone $Data;
	        $_total = $_total->count();
	    }

	    $Data  = $Data->take(200);
		$Data  = $Data->select([
			'order_buyer.id',
			'order_buyer.seller_id',
			'fullname',
			'phone',
			'email', 
			'address_id',
			'city_id',
			'province_id',
			'ward_id',
			'address'
		])->get();

		$ListDistrictID = [];
		$ListWardID     = [];
		$Cities    = $this->getCity();
		
		foreach ($Data as $key => $value) {
			$ListDistrictID[] = $value['province_id'] ? $value['province_id'] : "";
			$ListWardID[]     = $value['ward_id'] ? $value['ward_id'] : "";
		}

		if(!empty($ListWardID)){
			$ListWardID = array_unique($ListWardID);
			$Wards = $this->getWard($ListWardID);
		}

		if(!empty($ListDistrictID)){
			$ListDistrictID = array_unique($ListDistrictID);
			$Districts = $this->getProvince($ListDistrictID);
		}
		$ListID = [];

		foreach ($Data as $key => $value) {
			$ListID[] = $value['id'];

			$data = \LMongo::connection('elasticsearch')
					->collection('order_buyer')
					->where('phone', $value['phone'])
					->where('seller_id', $value['seller_id'])
					->first();

			if($data){
            	//echo $value['fullname']." da ton tai ! \n";
	        }else {
	        	$CityName     = !empty($Cities[$value['city_id']]) ? $Cities[$value['city_id']]  :  "";
				$DistrictName = !empty($Districts[$value['province_id']]) ? $Districts[$value['province_id']] : "";
				$WardName     = !empty($Wards[$value['ward_id']]) ? $Wards[$value['ward_id']] : "";

	            $ret = \LMongo::connection('elasticsearch')->collection('order_buyer')->insert([
					'seller_id' 	=> $value['seller_id'],
					'fullname'		=> $value['fullname'],
					'phone'			=> $value['phone'],
					'email'			=> $value['email'], 
					'city_id'		=> $value['city_id'],
					'district_id'	=> $value['province_id'],
					'ward_id'		=> $value['ward_id'],
					'full_address'	=> $value['address'].', '.$WardName.', '.$DistrictName.', '.$CityName,
					'city_name'		=> $CityName,
					'district_name'	=> $DistrictName,
					'ward_name'		=> $WardName,
					'address'		=> $value['address']
	            ]);

	            if($ret){
	                
	            }else {
	                echo "Cap nhat loi : ".$value['seller_id']." \n";
	            }    
	        }
		}

		if(!empty($ListID)){
			$Max = max($ListID);
			$this->setLastSync($Max);
		}else {

		}
        echo ("Done : " .$_total);
	}

	public function getSyncAddress(){
		set_time_limit(1800);
		$City = DB::connection('metadb')->select(DB::raw("SELECT id, city_name FROM lc_city"));
		$Count = 0;
		foreach ($City as $key => $value) {
			//$_query = "SELECT district_name, lc_district.id, ward_name, lc_ward.id as ward_id FROM lc_district JOIN lc_ward on lc_ward.district_id = lc_district.id WHERE lc_district.city_id=".$value->id;
			$_query = "SELECT district_name, id FROM lc_district WHERE city_id=".$value->id;
			$Data   = DB::connection('metadb')->select(DB::raw($_query));
			foreach ($Data as $k => $v) {
				$Address = $v->district_name.', '.$value->city_name;

				$MongoData = \LMongo::connection('elasticsearch')
						->collection('s_address')
						->where('city_id', $value->id)
						->where('district_id', $v->id)
						->first();
				if(!$MongoData){
					$ret  = \LMongo::connection('elasticsearch')->collection('s_address')->insert([
						'city_name'     => $value->city_name,
						'city_id'       => $value->id,
						'district_name' => $v->district_name,
						'district_id'   => $v->id,
						'full_address'  => $Address,
		            ]);
		            $Count ++;
				}
			}
		}
		echo 'DONE : '.$Count;
		
	}

	public function getSyncInventory(){
		set_time_limit(1800);

		$Cities    = $this->getCity();

		$ListCityID     = [];
		$ListDistrictID = [];
		$ListWardID     = [];
		$Districts      = [];
		$Wards          = [];

		$InventoryModel = new UserInventoryModel;
		$InventoryModel = $InventoryModel->orderBy('id', 'ASC');

		$clearCache = Input::has('clear');
		if($clearCache){
			Cache::forever('last_id_sync_inventory', 0);
		}

		if(Cache::has('last_id_sync_inventory')){
			$InventoryModel = $InventoryModel->where('id', '>', Cache::get('last_id_sync_inventory'));
		}

		$InventoryModel = $InventoryModel->take(1000);
		$_total         = clone $InventoryModel;
		$_total         = $_total->count();
		$ListID         = [];

		if($_total > 0){
			$InventoryModel = $InventoryModel->get();

			foreach ($InventoryModel as $key => $value) {
				$ListDistrictID[] = $value['province_id'] ? $value['province_id'] : "";
				$ListWardID[]     = $value['ward_id'] ? $value['ward_id'] : "";
			}

			if(!empty($ListWardID)){
				$Wards = $this->getWard($ListWardID);
			}

			if(!empty($ListDistrictID)){
				$Districts = $this->getProvince($ListDistrictID);
			}

			foreach ($InventoryModel as $key => $value) {

				$ListID[] = $value['id'];
				$data = \LMongo::connection('elasticsearch')
						->collection('user_inventory')
						->where('inventory_id', $value['id'])
						->first();

				if($data){
	            	//echo $value['fullname']." da ton tai ! \n";
		        }else {

					$CityName     = !empty($Cities[$value['city_id']]) ? $Cities[$value['city_id']]  :  "";
					$DistrictName = !empty($Districts[$value['province_id']]) ? $Districts[$value['province_id']] : "";
					$WardName     = !empty($Wards[$value['ward_id']]) ? $Wards[$value['ward_id']] : "";

		            $ret 		  = \LMongo::connection('elasticsearch')->collection('user_inventory')->insert([
						'inventory_id'  => $value['id'],
						'user_id'       => $value['user_id'],
						'user_name'     => $value['user_name'],
						'name'			=> $value['name'],
						'city_id'       => $value['city_id'],
						'province_id'   => $value['province_id'], 
						'city_name'     => $CityName,
						'district_name' => $DistrictName,
						'ward_name'     => $WardName,
						'full_address'	=> $value['address'].', '.$WardName.', '.$DistrictName.', '.$CityName,
						'ward_id'       => $value['ward_id'],
						'lat'           => $value['lat'],
						'lng'           => $value['lng'],
						'address'       => $value['address'],
						'active'        => $value['active'],
						'delete'        => $value['delete'],
		            ]);

		            if($ret){
		                
		            }else {
		                echo "Cap nhat loi : ". $add." \n";
		            }    
		        }
			}
			if(!empty($ListID)){
				$Max = max($ListID);
				Cache::forever('last_id_sync_inventory', $Max);
			}else {
			}
			echo 'CONTINUES: '.$_total;
		}
		echo ("Done") ;
	}

	public function getSyncItem(){
		set_time_limit(1800);

		$_total = 0;
		
		$Model 	= new \OrderItemModel;
		$Data 	= $Model->join('items', 'order_item.item_id', '=', 'items.id')->orderBy('order_item.id', 'ASC');

		$clearCache = Input::has('clear');
		if($clearCache){
			Cache::forever('last_id_sync_item', 0);
		}
		if(Cache::has('last_id_sync_item')){
			$Data = $Data->where('order_item.id', '>', Cache::get('last_id_sync_item'));
		}
		$Data  = $Data->where('items.time_update', '>', $this->time() - 30 * 86400);

		$Data  	= $Data->take(1000);

		$_total = clone $Data;
		$_total = $_total->count();

		$ListID = [];
		if($_total > 0){
			
			$Data  = $Data->select([
				'order_item.id as item_id',
				'items.seller_id',
				'items.price',
				'items.weight',
				'items.name', 
				'quantity',
				'product_name',
				'description',
			])->get();
			


			foreach ($Data as $key => $value) {
				$ListID[] = $value['item_id'];
				$data = \LMongo::connection('elasticsearch')
						->collection('order_item')
						->where('product_name', $value['product_name'])
						->where('seller_id', $value['seller_id'])
						->first();

				if($data){
	            	//echo $value['fullname']." da ton tai ! \n";
		        }else {
		            $ret = \LMongo::connection('elasticsearch')->collection('order_item')->insert([
		            	'seller_id'		=> $value['seller_id'],
						'product_name' 	=> $value['product_name'],
						'quantity' 		=> 1,
						'price'			=> $value['price'],
						'weight'		=> $value['weight']
		            ]);
		            if($ret){
		                
		            }else {
		                echo "Cap nhat loi : ". $add." \n";
		            }    
		        }
			}
			if(!empty($ListID)){
				$Max = max($ListID);
				Cache::forever('last_id_sync_item', $Max);
			}else {

			}
			echo 'CONTINUES: '.$_total;
			return;
		}
        echo ("Done") ;
	}

}