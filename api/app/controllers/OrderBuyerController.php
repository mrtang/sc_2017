<?php

use \ordermodel\BuyerModel;
class OrderBuyerController extends \BaseController {
	const SYNC_BUYER = "last_sync_buyer_time";

	private function getLastSync(){
		$get = \LMongo::connection('cdt')->collection('sync_logs')->where('type', self::SYNC_BUYER)->first();
		if(empty($get)){
			return false;
		}
		return $get->value;
	}

	private function setLastSync($value){
		$get = \LMongo::connection('cdt')->collection('sync_logs')->where('type', self::SYNC_BUYER)->first();
		if(empty($get)){
			\LMongo::connection('cdt')->collection('sync_logs')->insert([
				'type'	=> self::SYNC_BUYER,
				'value'	=> $value
			]);
		}else {
			$get->value = $value;
		}
		$get->save();
	}

	/*
	public function getSync($_skip = 0, $_take = 1000, $_total = 0){
		set_time_limit(1800);
		$Model = new BuyerModel;
		$Data = $Model->join('order_address', 'order_buyer.address_id', '=' , 'order_address.id');
		if($this->getLastSync()){
			$Data = $Data->where('time_create', '>=', $this->getLastSync());
			$Data = $Data->where('time_create', '<=', $this->time());
		}
		

		

		if($_total == 0){
	        $_total = clone $Data;
	        $_total = $_total->count();
	    }

	    $Data  = $Data->skip($_skip)->take($_take);
		$Data = $Data->select([
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
		foreach ($Data as $key => $value) {
			$data = \LMongo::connection('cdt')->collection('order_buyer')->where('fullname', $value['fullname'])->where('phone', $value['phone'])->where('seller_id', $value['seller_id'])->orderBy('time_create', 'ASC')->first();
			if($data){
            	//echo $value['fullname']." da ton tai ! \n";
	        }else {
	            $ret = \LMongo::connection('cdt')->collection('order_buyer')->insert([
					'seller_id' 	=> $value['seller_id'],
					'fullname'		=> $value['fullname'],
					'phone'			=> $value['phone'],
					'email'			=> $value['email'], 
					'city_id'		=> $value['city_id'],
					'district_id'	=> $value['province_id'],
					'ward_id'		=> $value['ward_id'],
					'address'		=> $value['address']
	            ]);

	            if($ret){
	                
	            }else {
	                echo "Cap nhat loi : ". $add." \n";
	            }    
	        }
		}

		

        if($_total > $_skip){
            $_skip = $_skip + $take;
            echo "Da cap nhat : ".$_skip." ban ghi \n";
            $this->getSync($_skip, $_take, $_total);
        }else {
             echo ("Done : " .$_total) ;
             $this->setLastSync($this->time())
        }
	}*/
}