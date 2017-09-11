<?php namespace ticketmodel;

use Illuminate\Database\Eloquent\Model;

class LiveChatModel extends Model {


    protected $table            = 'ticket_live_chat' ;
    protected $connection       = 'ticketdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function request(){
        return $this->hasOne('ticketmodel\RequestModel','id','ticket_id');
    }
}