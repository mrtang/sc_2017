<?php
use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;

class QueueModel extends Eloquent {
	protected $table = 'queue' ;
    protected $connection = 'noticedb';
    public    $timestamps = false;

    public function user(){
        return $this->belongsTo('User', 'user_id');
    }
    public function scenario(){
    	return $this->hasOne('ScenarioModel', 'id', 'scenario_id');
    }

}
