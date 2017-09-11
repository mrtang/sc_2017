<?php

class CourierServiceModel extends Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'courier_service';
    protected $connection   = 'courierdb';
    public    $timestamps   = false;

    public function get_service(){
        $Data    = [];
        $Service = self::where('active',1)->remember(3600)->get(['id','name'])->toArray();
        if(!empty($Service)){
            foreach($Service as $val){
                $Data[(int)$val['id']]  = $val;
            }
        }
        return $Data;
    }
}
