<?php
namespace loyalty;
use LMongo;


class ConfigCtrl extends BaseCtrl
{
    public function __construct()
    {

    }

    public function postEditLevel(){
        $validation = Validator::make(Input::all(), array(
            'id'                => 'sometimes|required|integer|min:0',
            'code'              => 'sometimes|required|integer|min:0',
            'name'              => 'sometimes|required',
            'point'             => 'sometimes|required|integer|min:0',
            'maintain_point'    => 'sometimes|required|integer|min:0',
            'bg-color'          => 'sometimes|required',
            'active'            => 'sometimes|required|integer|in:0,1',
        ));

        //error
        if($validation->fails()) {
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => $validation->messages()]);
        }

        $Id             = Input::has('id')                  ? (int)Input::get('id')                 : 0;
        $Code           = Input::has('code')                ? (int)Input::get('code')               : null;
        $Name           = Input::has('name')                ?  trim(Input::get('name'))             : '';
        $Point          = Input::has('point')               ? (int)Input::get('point')              : null;
        $MaintainPoint  = Input::has('maintain_point')      ? (int)Input::get('maintain_point')     : null;
        $BgColor        = Input::has('bg_color')            ?  trim(Input::get('bg_color'))         : '';
        $Active         = Input::has('active')              ? (int)Input::get('active')             : null;

        $CheckCode = \loyaltymodel\LevelModel::where('code', $Code);
        if(!empty($Id)){ // thay đổi
            $Level  = \loyaltymodel\LevelModel::find($Id);
            if(!isset($Level->id)){
                return Response::json([
                    'error'         => true,
                    'code'          => 'INSERT_ERROR',
                    'error_message' => 'Mã  không tồn tại'
                ]);
            }

            $CheckCode  = $CheckCode->where('id','<>',$Id);
        }else{ // insert
            $Level  = new \loyaltymodel\LevelModel;
        }

        if(isset($Code)){

            if($CheckCode->count() > 0){
                return Response::json([
                    'error'         => true,
                    'code'          => 'CODE_EXISTS',
                    'error_message' => 'Mã code đã tồn tại'
                ]);
            }
            $Level->code    = $Code;
        }

        if(!empty($Name)){
            $Level->name    = $Name;
        }

        if(isset($Point)){
            $Level->point    = $Point;
        }

        if(isset($MaintainPoint)){
            $Level->maintain_point    = $MaintainPoint;
        }

        if(!empty($BgColor)){
            $Level->bg_color    = $BgColor;
        }

        if(isset($Active)){
            $Level->active    = $Active;
        }


        try{
            $Level->save();
            Cache::forget('cache_sc_loyalty_level');
        }catch(\Exception $e){
            return Response::json([
                'error'         => true,
                'code'          => 'INSERT_ERROR',
                'error_message' => 'Thêm mới thất bại '. $e->getMessage()
            ]);
        }

        return Response::json([
            'error'         => false,
            'code'          => 'SUCCESS',
            'error_message' => 'Thành Công',
            'id'            =>  $Level->id
        ]);
    }
}
?>