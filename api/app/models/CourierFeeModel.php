<?php

class CourierFeeModel extends Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table        = 'courier_fee' ;
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps = false;
    
    public function fee_detail(){
        return $this->hasMany('CourierFeeDetailModel','fee_id');
    }
}
