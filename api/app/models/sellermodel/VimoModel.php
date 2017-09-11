<?php namespace sellermodel;

use Eloquent;
class VimoModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'vimo_config' ;
    protected $connection       = 'sellerdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
    public function user(){
        return $this->belongsTo('\User','user_id')->select('email', 'fullname', 'id' ,'phone');
    }
    /*public static function hasBanking ($user_id){
        $bank =  self::where('user_id', '=', $user_id)->first();
        if(!empty($bank)){
            return true;
        }
        return false;
     }*/
}
