<?php namespace fulfillmentmodel;


class ItemHistoryModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ff_product_itemhistory' ;
    protected $connection       = 'ffdb';
    protected $guarded          = array();
    public    $timestamps       = false;
	public function __employee(){
		return $this->belongsTo('warehousemodel\AuthEmployeeModel','createby','id')->select(['id','fullname','email','phone']);
	}
}
