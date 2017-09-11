<?php namespace loyalty;

class BaseCtrl extends \BaseCtrl{

    public $itemPage    = 20;
    public $total       = 0;
    public $data        = [];

    function __construct(){
        
    }

    public function ResponseData(){

        return Response::json([
            'error'         => false,
            'message'       => 'Thành công',
            'total'         => $this->total,
            'data'          => $this->data
        ]);
    }

}
