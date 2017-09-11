<?php
namespace ops;

class CourierCtrl extends BaseCtrl
{
    private $error              = false;
    private $message            = 'success';
    private $total              = 0;
    private $data               = [];

    function __construct(){
        //set_time_limit (180);
    }

    private function ResponseData(){

        return Response::json([
            'error'         => $this->error,
            'message'       => $this->message,
            'total'         => $this->total,
            'data'          => $this->data,
        ]);
    }

    public function getEstimateCourier(){
        $itemPage           = 20;
        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $Cmd                = Input::has('cmd')                 ? trim(Input::get('cmd'))                   : '';

        $ServiceId          = Input::has('service')             ? (int)Input::get('service')                : 0;
        $CourierId          = Input::has('courier_id')          ? (int)Input::get('courier_id')             : 0;
        $FromCity           = Input::has('from_city')           ? (int)Input::get('from_city')              : 0;
        $FromDistrict       = Input::has('from_district')       ? (int)Input::get('from_district')          : 0;

        $ToCity             = Input::has('to_city')             ? (int)Input::get('to_city')                : 0;
        $ToDistrict         = Input::has('to_district')         ? (int)Input::get('to_district')            : 0;

        $Model = new \systemmodel\CourierPromiseModelDev;

        if(!empty($ServiceId)){
            $Model  = $Model->where('service_id', $ServiceId);
        }

        if(!empty($CourierId)){
            $Model  = $Model->where('courier_id', $CourierId);
        }

        if(!empty($FromDistrict)){
            $Model          = $Model->where('from_district',$FromDistrict);
        }elseif(!empty($FromCity)){
            Input::merge(['city' => $FromCity]);
            $ListDistrictId = $this->getDistrict(false);
            if(!empty($ListDistrictId)){
                $ListId = [];
                foreach($ListDistrictId as $val){
                    $ListId[]   = (int)$val['id'];
                }
                if(empty($ListId)){
                    $this->error = true;
                    return $this->ResponseData();
                }
                $Model          = $Model->whereRaw("from_district in (". implode(",", $ListId) .")");
            }else{
                $this->error = true;
                return $this->ResponseData();
            }
        }

        if(!empty($ToDistrict)){
            $Model          = $Model->where('to_district',$ToDistrict);
        }elseif(!empty($ToCity)){
            Input::merge(['city' => $ToCity]);
            $ListDistrictId = $this->getDistrict(false);
            if(!empty($ListDistrictId)){
                $ListId = [];
                foreach($ListDistrictId as $val){
                    $ListId[]   = (int)$val['id'];
                }
                if(empty($ListId)){
                    $this->error = true;
                    return $this->ResponseData();
                }
                $Model          = $Model->whereRaw("to_district in (". implode(",", $ListId) .")");
            }else{
                $this->error = true;
                return $this->ResponseData();
            }
        }

        if($Cmd == 'export'){
            $this->data = $Model->get()->toArray();
            return $this->ResponseData();
        }

        $TotalModel     = clone $Model;
        $this->total    = $TotalModel->count();

        if($this->total > 0) {
            $offset         = ($page - 1) * $itemPage;
            $this->data     = $Model->skip($offset)->take($itemPage)->get()->toArray();
        }

        return $this->ResponseData();
    }
}
