<?php namespace omsmodel;

use Eloquent;
class ActivityModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'oms_activity';
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
