<?php namespace omsmodel;

class PartnerStatusModel extends \Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'partner_status' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function __order_status(){
        return $this->belongsTo('metadatamodel\OrderStatusModel','status','code');
    }
}
