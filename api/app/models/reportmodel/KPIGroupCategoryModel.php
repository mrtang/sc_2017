<?php namespace reportmodel;

use Eloquent;
class KPIGroupCategoryModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'report_kpi_groupcategory' ;
    protected $connection       = 'reportdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
