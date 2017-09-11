<?php namespace reportmodel;

use Eloquent;
class KPIEmployeeModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'report_kpi_employee' ;
    protected $connection       = 'reportdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
