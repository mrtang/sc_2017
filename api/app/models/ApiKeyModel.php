<?php

class ApiKeyModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'merchant_token' ;
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public static function checkMerchantToken ($token){
        return self::where('key', '=', $token)->first();
    }
	public function user(){
		return $this->belongsTo('User', 'user_id');
	}    
}
