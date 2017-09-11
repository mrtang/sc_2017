<?php namespace metadatamodel;

use Eloquent;
use Cache;
class CurrencyModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'currency' ;
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;

    static function getCurrencyById($currency_id){
        $cache_key = 'cache_currency'.'_'.$currency_id;

        if(Cache::has($cache_key)){
            return Cache::get($cache_key);
        }

        $result = CurrencyModel::where('id', $currency_id)->first();

        if(!empty($result)){
            Cache::put($cache_key, $result, 1440);
            return $result;
        }
        return [];
    }
}
