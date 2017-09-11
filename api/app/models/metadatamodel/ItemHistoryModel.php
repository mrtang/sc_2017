<?php namespace metadatamodel;


class ItemHistoryModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'product_item_history' ;
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;

	public function __employee(){
		return $this->belongsTo('warehousemodel\AuthEmployeeModel','createby','id')->select(['id','fullname','email','phone']);
	}
}
