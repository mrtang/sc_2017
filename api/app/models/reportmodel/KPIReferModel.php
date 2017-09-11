<?php namespace reportmodel;

use Eloquent;
class KPIReferModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'report_kpi_refer' ;
    protected $connection       = 'reportdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
