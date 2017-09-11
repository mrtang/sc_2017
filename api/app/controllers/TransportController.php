<?php
class TransportController extends \BaseController {
    private $domain = '*';
    
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
     
    public function __construct(){
        
    }
    //
    public function getIndex()
	{
		$Model = new TransportModel;
        $statusCode = 200;
        $contents = array(
            'error'         => false,
            'message'       => '',
            'total'         => $Model->count(),
            'data'          => $Model->get()
        );
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
}
?>