<?php namespace ticketmodel;

use Eloquent;
class StatisticModel extends Eloquent
{
    protected $table            = 'statistic' ;
    protected $connection       = 'ticketdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}