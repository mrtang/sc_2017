<?php

class DiscountConfigModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'discount_config' ;
    protected $connection   = 'courierdb';
    public    $timestamps   = false;
    
    // get courier
    public function courier(){
        return $this->belongsTo('CourierModel','courier_id');
    }
    
    // get discount_type
    public function discount_type(){
        return $this->belongsTo('DiscountTypeModel','type_id');
    }
    
    // get discount use
    public function discount_setup(){
        return $this->hasMany('DiscountSetupModel','discount_id');
    }
    
}
