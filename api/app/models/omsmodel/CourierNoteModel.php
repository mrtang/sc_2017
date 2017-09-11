<?php namespace omsmodel;

use Eloquent;
class CourierNoteModel extends Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'oms_courier_note' ;
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function order(){
        return $this->belongsTo('ordermodel\OrdersModel', 'order_id', 'id');
    }

}
