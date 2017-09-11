<?php namespace accountingmodel;

use Input;
class CashInModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'cash_in' ;
    protected $connection       = 'accdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function newQuery($excludeDeleted = true) {
        $CountryId      = Input::has('country_id')      ? (int)Input::get('country_id')          : 237;

        return parent::newQuery($excludeDeleted = true)
            ->where('country_id', '=', $CountryId);
    }

    public function User(){
        return $this->belongsTo('\User','merchant_id')->select(['id','fullname','email','phone']);
    }
}
