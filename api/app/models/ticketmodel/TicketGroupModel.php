<?php namespace ticketmodel;

use Eloquent;
class TicketGroupModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'ticket_group' ;
    protected $connection       = 'ticketdb';
    protected $guarded          = array();
    public    $timestamps       = false;


    public function user_assign(){
        return $this->hasMany('\ticketmodel\AssignGroupModel','group_id');
    }

}
