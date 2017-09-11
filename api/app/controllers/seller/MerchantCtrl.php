<?php namespace seller;

use Response;
use Exception;
use Input;
use accountingmodel\MerchantModel;
use Validator;
use Excel;
use DB;

class MerchantCtrl extends \BaseController {
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function getShow()
    {
        $UserInfo   = $this->UserInfo();
        $Id = (int)$UserInfo['id'];

        $Model          = new MerchantModel;
        $QueueModel     = new \QueueModel;

        $Data   = $Model->where('merchant_id',$Id)->first(array('merchant_id', 'balance', 'freeze', 'provisional'));
        //$Queue  = $QueueModel->where('user_id',$Id)->where('transport_id',3)->where('view',0)->count();

        if(empty($Data)){
            $Data['balance']    = 0;
            $Data['freeze']     = 0;
        }

        $Data['queue']     = 0;
        /*if($Queue > 0){
            $Data['queue']     = $Queue;
        }*/

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'        => $Data
        );

        return Response::json($contents);
    }
}
