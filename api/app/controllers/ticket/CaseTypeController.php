<?php namespace ticket;

use Cache;
use Response;
use ticketmodel\CaseTypeModel;

class CaseTypeController extends \BaseController {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
    public function getIndex($json = true)
    {
        if(Cache::has('case_type_ticket')){
            $Data = Cache::get('case_type_ticket');
        }else{
            $Data  = CaseTypeModel::where('active',1)->get()->toArray();
            if(!empty($Data)){
                Cache::put('case_type_ticket', $Data, 60);
            }
        }

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $Data
        );

        return $json ? Response::json($contents) : $contents;
    }

}
