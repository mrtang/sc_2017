<?php namespace omsmodel;

use Eloquent;
class LogSellerModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'log_seller' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
