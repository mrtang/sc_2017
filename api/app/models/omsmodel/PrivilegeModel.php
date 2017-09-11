<?php namespace omsmodel;

use Eloquent;
class PrivilegeModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'oms_privilege' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;


    public function get_privilege(){
        $Privilege  = [];
        $Data =  $this->get()->toArray();
        if(!empty($Data)){
            foreach($Data as $val){
                $Privilege[(int)$val['id']]  = strtoupper(trim($val['code']));
            }
        }
        return $Privilege;
    }
}
