<?php namespace omsmodel;

use Eloquent;
class ExchangeModel extends Eloquent {

    protected $table            = 'exchange' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function get_image(){
        return $this->hasMany('omsmodel\ExchangeAttachModel','exchange_id')->orderBy('time_create','DESC');
    }
}
