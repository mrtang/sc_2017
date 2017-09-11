<?php namespace omsmodel;

use Eloquent;
class PipeStatusModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'pipe_status' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function group_status(){
    	return $this->belongsTo('\metadatamodel\GroupStatusModel', 'group_status');
    }
    public function group_status_seller(){
        return $this->belongsTo('\omsmodel\GroupProcessSellerModel', 'group_status');
    }

    public function getPipe($group,$type = 1){
        return $this->where('type', $type)->where('group_status', $group)->lists('status');
    }
}
