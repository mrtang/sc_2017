<?php namespace loyaltymodel;

class CategoryModel extends \Eloquent {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    protected $table            = 'category' ;
    protected $connection       = 'loyaltydb';
    protected $guarded          = array();
    public    $timestamps       = false;

}
