<?php

class CourierPostOfficeDetailModel extends Eloquent{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'post_office_detail';
    protected $connection   = 'courierdb';
    protected $guarded      = array();
    public    $timestamps   = false;

}
