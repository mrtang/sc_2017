<?php namespace warehousemodel;


class PickupListModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'warehouse_pickuplist' ;
    protected $connection       = 'warehousebm';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __employee(){
		return $this->belongsTo('warehousemodel\AuthEmployeeModel','employee_id','id')->select(['id','fullname','email','phone']);
	}
}
