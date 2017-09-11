<?php


class OrderItemController extends \BaseController {

	public function getSync($_skip = 0, $_take = 1000, $_total = 0){
		set_time_limit(1800);
		$Model 	= new \OrderItemModel;
		$Data 	= $Model->join('items', 'order_item.item_id', '=', 'items.id');

		if($_total == 0){
	        $_total = clone $Data;
	        $_total = $_total->count();
	    }

	    $Data  	= $Data->skip($_skip)->take($_take);
		$Data 	= $Data->get();
		foreach ($Data as $key => $value) {
			$data = \LMongo::connection('cdt')->collection('order_item')->where('product_name', $value['product_name'])->where('seller_id', $value['seller_id'])->first();
			if($data){
            	//echo $value['fullname']." da ton tai ! \n";
	        }else {
	            $ret = \LMongo::connection('cdt')->collection('order_item')->insert([
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

		

        if($_total > $_skip){
            $_skip = $_skip + 1000;
            echo "Da cap nhat : ".$_skip." ban ghi \n";
            $this->getSync($_skip, $_take, $_total);
        }else {
             echo ("Done : " .$_total) ;
        }
	}
}