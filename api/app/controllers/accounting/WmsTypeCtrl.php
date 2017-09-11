<?php namespace accounting;

use sellermodel\UserWMSTypeModel;

class WmsTypeCtrl extends BaseCtrl {
    private $data   = [];
    private $total  = 0;

    public function getIndex()
    {
        $page           = Input::has('page')            ? (int)Input::get('page')                : 1;
        $TimeStart      = Input::has('time_start')      ? trim(Input::get('time_start'))         : '';
        $TimeEnd        = Input::has('time_end')        ? trim(Input::get('time_end'))           : '';
        $Search         = Input::has('search')          ? trim(Input::get('search'))             : '';
        $Type           = Input::has('type')            ? (int)Input::get('type')                : null;
        $cmd            = Input::has('cmd')             ? trim(strtolower(Input::get('cmd')))    : '';
        $Active         = Input::has('active')          ? (int)Input::get('active')              : null;
        $itemPage       = 20;

        $Model = new UserWMSTypeModel;

        if(!empty($TimeStart)){
            $Model = $Model->where('start_date','>=',$TimeStart);
        }else{
            $Model = $Model->where('start_date','>=',$this->time() - $this->time_limit);
        }

        if(!empty($TimeEnd)){
            $Model = $Model->where('start_date','<',$TimeEnd);
        }

        if(isset($Type)){
            $Model  = $Model->where('wms_type', $Type);
        }

        if(isset($Active)){
            $Model  = $Model->where('active', $Active);
        }

        if(!empty($Search)){
            $ModelUser  = new \User;
            if (filter_var($Search, FILTER_VALIDATE_EMAIL)){  // search email
                $ModelUser          = $ModelUser->where('email', $Search);
            }elseif(filter_var((int)$Search, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $ModelUser          = $ModelUser->where('phone', $Search);
            }else{ // search code
                $ModelUser          = $ModelUser->where('fullname','LIKE','%'.$Search.'%');
            }
            $ListUser               = $ModelUser->lists('id');

            if(empty($ListUser)){
                return $this->ResponseData(false);
            }
            $Model  = $Model->whereIn('user_id',$ListUser);
        }

        if($cmd == 'export'){
            $Data = [];
            $Model->orderBy('start_date','DESC')->with('__get_user')->chunk('1000', function($query) use(&$Data){
                foreach($query as $val){
                    $val                = $val->toArray();
                    $Data[]             = $val;
                }
            });

            $this->data = $Data;
            return $this->ResponseData(false);
        }

        $ModelTotal     = clone $Model;
        $this->total    = $ModelTotal->count();
        $Model          = $Model->orderBy('start_date','DESC');

        if($this->total > 0){
            $itemPage       = (int)$itemPage;
            $offset         = ($page - 1)*$itemPage;
            $this->data     = $Model->with('__get_user')->skip($offset)->take($itemPage)->get()->toArray();
        }

        return $this->ResponseData(false);
    }

    private function ResponseData($error){
        return Response::json([
            'error'         => $error,
            'code'          => 'success',
            'error_message' => 'Thành công',
            'item_page'     => 20,
            'total'         => $this->total,
            'data'          => $this->data,
        ]);
    }
}
