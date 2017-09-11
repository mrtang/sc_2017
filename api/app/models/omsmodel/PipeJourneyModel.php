<?php namespace omsmodel;

use Eloquent;
class PipeJourneyModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'pipe_journey' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function User(){
        return $this->belongsTo('\User','user_id')->select(['id','email','fullname','phone']);
    }

    public function PipeStatus(){
        return $this->belongsTo('\omsmodel\PipeStatusModel','pipe_status','status')->select(['id','status','group_status','type','name']);
    }
}
