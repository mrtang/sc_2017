<?php namespace sellermodel;

use Eloquent;
class UserIntegrateModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'user_integrate' ;
    protected $connection       = 'sellerdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public function user(){
        
    }
    
    public static function getUserIntegrate ($user_id){
        $seller =  self::where('user_id', '=', $user_id)->select('banking_payment', 'online_payment', 'choice_service', 'checking')->first();
        if(!empty($seller)){
            return $seller;
        }
        return false;
     }
}
