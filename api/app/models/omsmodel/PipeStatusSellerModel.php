<?php namespace omsmodel;

use Eloquent;
class PipeStatusSellerModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'pipe_status_seller' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;
	public function group(){
    	return $this->belongsTo('\omsmodel\GroupProcessSellerModel', 'group');
    }
}
