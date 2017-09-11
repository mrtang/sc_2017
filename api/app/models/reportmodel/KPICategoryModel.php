<?php namespace reportmodel;

use Eloquent;
class KPICategoryModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'report_kpi_category' ;
    protected $connection       = 'reportdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function __group_category(){
        return $this->belongsTo('\reportmodel\KPIGroupCategoryModel','group_category_id')->select(['id','group','group_name']);
    }
}
