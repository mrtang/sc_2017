<?php namespace sellermodel;

use Eloquent;
class FeeModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'fee_config' ;
    protected $connection       = 'sellerdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public static function getConfig ($user_id){
        $config = self::where('user_id', '=', $user_id)->first();
        if(!empty($config)){
            return $config->shipping_fee;
        }
        return false;
     }
}
