<?php namespace warehousemodel;


class ReturnModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'warehouse_return' ;
    protected $connection       = 'warehousebm';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __employee(){
		return $this->belongsTo('warehousemodel\AuthEmployeeModel','update_by','id')->select(['id','fullname','email','phone']);
	}
}
