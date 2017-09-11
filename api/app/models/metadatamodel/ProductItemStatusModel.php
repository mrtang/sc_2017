<?php namespace metadatamodel;


class ProductItemStatusModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'product_item_status' ;
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;
}
