<?php namespace omsmodel;

use Eloquent;
class RefundConfirmModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'refund_confirm';
    protected $connection       = 'omsdb';
    protected $guarded          = array();
    public    $timestamps       = false;
    
}
