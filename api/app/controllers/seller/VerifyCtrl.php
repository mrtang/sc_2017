<?php namespace seller;
use Response;
use Input;
use DB;
use ordermodel\VerifyFreezeModel;
use Cache;

class VerifyCtrl extends \BaseController {
    public  function __construct(){

    }

    public function getShowFreeze($id)
    {
        $Data       = VerifyFreezeModel::where('verify_id',(int)$id)
                            ->get()->toArray();

        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        );

        return Response::json($contents);
    }
}
