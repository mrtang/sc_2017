<?php namespace ticket;

use Cache;
use Response;
use ticketmodel\CaseModel;

class CaseController extends \BaseController {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
        if(Cache::has('case_ticket')){
            $Data = Cache::get('case_ticket');
        }else{
            $Data  = CaseModel::where('active',1)->get()->toArray();
            if(!empty($Data)){
                Cache::put('case_ticket', $Data, 60);
            }
        }

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $Data
        );

        return Response::json($contents);
	}
}
