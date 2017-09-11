<?php namespace seller;

use Response;
use Exception;
use Input;
use Cache;
use sellermodel\ProductTradingModel;
use sellermodel\SellerProductTradingModel;
use Validator;
use Excel;
use DB;

class ProductTradingController extends \BaseController {
    
    private function getListProduct()
    {
        $Model  = new ProductTradingModel;

        if(Cache::has('product_trading')){
            return Cache::get('product_trading');
        }

        $Data  = $Model->where('active', 1)->get()->toArray();

        return $Data;
    }


    public function getShowConfig(){
        $UserInfo   = $this->UserInfo();

        if(empty($UserInfo['id'])){
            $this->_error           = true;
            $this->_error_message   = "Bạn không có quyền !";
            return $this->_ResponseData([]);
        }


        $Model  = new SellerProductTradingModel;   
        $Data   = $Model->where('active',1)->where('user_id',(int)$UserInfo['id'])->get()->toArray();

        $this->_error = false;
        $this->_error_message  = "";
        return $this->_ResponseData($Data, ['product_type'=> $this->getListProduct()]);
    }


    public function postUpdate (){
        $UserInfo   = $this->UserInfo();
        $Id     = Input::has('id')      ? Input::get('id')      : "";
        $Value  = Input::get('value');

        if($Id == ""){
            $this->_error           = true;
            $this->_error_message   = "Lỗi !";
            return $this->_ResponseData([]);
        }

        $Model  = new SellerProductTradingModel;   
        $Data   = $Model::firstOrNew(['product_id'=> $Id, 'user_id'=> (int)$UserInfo['id']]);

        if($Value == true){
            $Data->active = 1;
        }else {
            $Data->active = 0;
        }
        try {
            $Data->save();
        } catch (Exception $e) {
            $this->_error           = true;
            $this->_error_message   = "Lỗi : ". $e->getMessage();
            return $this->_ResponseData([]);
        }

        $this->_error           = false;
        $this->_error_message   = "Thành công";
        return $this->_ResponseData([]);
        

    }
}
