<?php namespace reportmodel;

use Eloquent;
class KPIModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'report_kpi' ;
    protected $connection       = 'reportdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
