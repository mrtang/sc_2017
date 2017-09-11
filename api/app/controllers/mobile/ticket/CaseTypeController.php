<?php namespace mobile_ticket;

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
        if(Cache::has('mobile_case_type_ticket')){
            $Data = Cache::get('mobile_case_type_ticket');
        }else{
            $Data  = CaseTypeModel::where('active',1)->select(['id', 'type_name', 'active', 'estimate_time'])->get()->toArray();
            if(!empty($Data)){
                Cache::put('mobile_case_type_ticket', $Data, 1440);
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
