<?php namespace reportmodel;

use Eloquent;
class KPIGroupConfigModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'report_group_config' ;
    protected $connection       = 'reportdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
