<?php

class CountryModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */

    protected $table        = 'lc_city_global' ;
    protected $connection   = 'metadb';
    public    $timestamps   = false;
}
