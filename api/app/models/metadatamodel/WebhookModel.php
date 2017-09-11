<?php namespace metadatamodel;

use Eloquent;
class WebhookModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'user_webhook' ;
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;

	static function getListHookByUser($user_id, $group_status){
		if(empty($user_id)){
			return [];
		}
		return  WebhookModel::where('seller_id', $user_id)->where('active', 1)->where('status_group', $group_status)->first();
	}
}
