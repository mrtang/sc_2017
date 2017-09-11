<?php
class TestController extends \BaseController {
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 * @params $scenario, $user_id
	 */
     
    public function __construct(){
        
    }

    public function getSendsms(){
    	$list = Smslib::send();
    	var_dump($list);die;
    }
}
?>