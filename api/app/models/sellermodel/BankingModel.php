<?php namespace sellermodel;

use Eloquent;
class BankingModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'banking_config' ;
    protected $connection       = 'sellerdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    

    public static function hasBanking ($user_id){
        $bank =  self::where('user_id', '=', $user_id)->first();
        if(!empty($bank)){
            return true;
        }
        return false;
     }
}
