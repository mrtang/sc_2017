<?php namespace reportmodel;

use Eloquent;
class CrmEmployeeModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'report_crm_employee' ;
    protected $connection       = 'reportdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function __user(){
        return $this->belongsTo('User','user_id')->select(['id','email','fullname','phone']);
    }
}
