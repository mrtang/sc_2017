<?php namespace metadatamodel;

class OrganizationUserModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'organization_user' ;
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function getMerchant(){
		return $this->hasOne('bm_accmodel\MerchantModel','merchant_id');
	}

	public function get_user(){
		return $this->hasMany('\User','organization')->select(['id','organization','fullname','email','phone']);
	}
}
