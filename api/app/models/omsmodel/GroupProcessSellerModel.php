<?php namespace omsmodel;

use Eloquent;
class GroupProcessSellerModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'group_process_seller';
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function pipe_status(){
        return $this->hasMany('omsmodel\PipeStatusModel','group_status')->orderBy('priority', 'ASC');
    }
}
