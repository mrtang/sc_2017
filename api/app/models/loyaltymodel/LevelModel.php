<?php namespace loyaltymodel;

class LevelModel extends \Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'level' ;
    protected $connection       = 'loyaltydb';
    protected $guarded          = array();
    public    $timestamps       = false;

}
