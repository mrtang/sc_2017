<?php

class CourierAreaModel extends Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'courier_area';
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;

    public function get_area_id($courier){
        return self::where('courier_id',$courier)->remember('1440')->lists('id');
    }
}
