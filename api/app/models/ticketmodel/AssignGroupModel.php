<?php namespace ticketmodel;

use Eloquent;
class AssignGroupModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'ticket_assign_group' ;
    protected $connection       = 'ticketdb';
    protected $guarded          = array();
    public    $timestamps       = false;

}
