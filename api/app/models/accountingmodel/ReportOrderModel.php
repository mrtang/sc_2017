<?php namespace accountingmodel;

use Eloquent;
use Input;
class ReportOrderModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'report_order' ;
    protected $connection       = 'accdb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function newQuery($excludeDeleted = true) {
		$CountryId      = Input::has('country_id')      ? (int)Input::get('country_id')          : 237;

		return parent::newQuery($excludeDeleted = true)
			->where('country_id', '=', $CountryId);
	}
}
