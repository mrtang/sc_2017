<?php namespace partnermodel;


class PartnerReferModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'partner_refer_log' ;
    protected $connection       = 'partnerdb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_warehouse_fee(){
		return $this->belongsTo('\fulfillmentmodel\WareHouseFeeModel','log_id');
	}
}
