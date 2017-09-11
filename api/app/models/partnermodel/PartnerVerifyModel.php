<?php namespace partnermodel;


class PartnerVerifyModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'partner_verify' ;
    protected $connection       = 'partnerdb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_refer(){
		return $this->hasMany('\partnermodel\PartnerReferModel','partner_id');
	}
}
