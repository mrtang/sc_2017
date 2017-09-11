<?php namespace mobile_seller;

use Response;
use Exception;
use Input;
use accountingmodel\MerchantModel;
use Validator;
use Excel;
use DB;

class MerchantCtr extends \BaseController {

    public function getShow()
    {
        $UserInfo   = $this->UserInfo();
        $Id = (int)$UserInfo['id'];

        $Model      = new MerchantModel;
        $Data       = $Model->where('merchant_id',$Id)->first(array('merchant_id', 'balance', 'freeze', 'provisional'));

        if(empty($Data)){
            $Data['balance']    = 0;
            $Data['freeze']     = 0;
        }

        $Data['queue']     = 0;

        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'        => $Data
        );

        return Response::json($contents);
    }
}
