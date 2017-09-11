<?php namespace fulfillmentmodel;


class WareHouseVerifyModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'ff_warehouseverify' ;
    protected $connection       = 'ffdb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_user(){
		return $this->belongsTo('\User','user_id')->select(['id','fullname','email','phone']);
	}
}
