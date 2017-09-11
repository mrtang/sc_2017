<?php

class CourierModel extends Eloquent{
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'courier' ;
    protected $connection   = 'courierdb';
    public    $timestamps   = false;
    
    public function seller_courier_config(){
        return $this->hasMany('sellermodel\CourierModel','courier_id');
    }

    public function get_courier(){
        $Data    = [];
        $Courier = self::where('active',1)->remember(3600)->get(['id','name','prefix','money_pickup','money_delivery'])->toArray();
        if(!empty($Courier)){
            foreach($Courier as $val){
                $Data[(int)$val['id']]  = $val;
            }
        }
        return $Data;
    }
}
