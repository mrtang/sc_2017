<?php
class CourierFeeDetailModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table = 'courier_fee_detail' ;
    protected $connection = 'courierdb';
    public    $timestamps = false;
}
