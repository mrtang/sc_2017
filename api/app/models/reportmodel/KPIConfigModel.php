<?php namespace reportmodel;

use Eloquent;
class KPIConfigModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'report_kpi_config' ;
    protected $connection       = 'reportdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function __category(){
        return $this->belongsTo('\reportmodel\KPICategoryModel','category_id')->select(['id','group_category_id','code','work_name','percent','weight','target']);
    }
}
