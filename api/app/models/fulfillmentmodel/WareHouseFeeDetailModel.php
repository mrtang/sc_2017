<?php namespace fulfillmentmodel;


class WareHouseFeeDetailModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ff_warehousefeedetail' ;
    protected $connection       = 'ffdb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function get_log_warehouse(){
		return $this->belongsTo('bm_fulfillment\WareHouseFeeModel','log_id','id')->select(['id','date','organization']);
	}
}
