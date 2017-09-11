<?php

class CountryModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    protected $table        = 'lc_country' ;
    protected $connection   = 'metadb';
    public    $timestamps   = false;

    static function getCountry(){
    	if(\Cache::has('cache_country')){
            $CountryModel = \Cache::get('cache_country');
        }else{
            $CountryModel = new CountryModel;
            $CountryModel = $CountryModel::get()->toArray();

            if(!empty($CountryModel)){
                \Cache::put('cache_country', $CountryModel, 1440);
            }
        }
        return $CountryModel;

    }
    static function getCountryCodeById($id){
    	$Country  = CountryModel::getCountry();
    	foreach ($Country as $key => $value) {
			if($value['id'] == $id){
				return $value['country_code'];
			}
    	}
    	return "";
    }
}
