<?php namespace omsmodel;

use Eloquent;
class ExchangeAttachModel extends Eloquent {

    protected $table            = 'image_exchange' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
