<?php
class DevCourierFeeDetailModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */

	protected $table = 'courier_fee_detail_12_2015' ;
    protected $connection = 'courierdb';
    public    $timestamps = false;
}
