<?php namespace ticketmodel;

use Eloquent;
class CallReportModel extends Eloquent{

    protected $table            = 'call_report' ;
    protected $connection       = 'ticketdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    public function user(){
        return $this->belongsTo('User','src', 'phone')->select(['id','email','phone','fullname', 'time_create']);
    }

}
