<?php namespace sellermodel;

use Eloquent;
class UserWMSTypeModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'user_wmstype' ;
    protected $connection       = 'sellerdb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __get_user(){
		return $this->belongsTo('\User','user_id')->select(['id','fullname','email','phone']);
	}
}
