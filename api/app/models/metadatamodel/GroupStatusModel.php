<?php namespace metadatamodel;

use Eloquent;
class GroupStatusModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'group_status' ;
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function group_order_status(){
        return $this->hasMany('metadatamodel\GroupOrderStatusModel','group_status');
    }
    public function pipe_status(){
    	return $this->hasMany('omsmodel\PipeStatusModel','group_status')->orderBy('priority', 'ASC')->where('type', 1);	
    }
    public function getNameAttribute($value)
    {
        $ret  = $this->attributes['name'];
        if(\Config::get('app.locale') == 'en'){
            $ret  = $this->attributes['name_en'];
        }
        return $ret;
    }
}
