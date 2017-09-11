<?php namespace mobile_v2;

use Cache;
use Input;
use Response;
use ticketmodel\CaseTypeModel;

class MobileApiV2Controller extends \BaseController {


    public function getGroupStatus (){
        $BaseCtrl = new \BaseCtrl();
        Input::merge(['group'=> 3]);
        $Data = $BaseCtrl->getGroupByStatus(false);
        return $this->_ResponseData($Data);
    }

    
}
