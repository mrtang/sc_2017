<?php namespace omsmodel;

use Eloquent;
class GroupUserModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'oms_group_user' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function group_privilege(){
    	return $this->hasMany('\omsmodel\GroupPrivilegeModel', 'group_id');
    }
}
