<?php namespace ticket;

use Validator;
use Response;
use Input;
use ticketmodel\ReferModel;
use ticketmodel\RequestModel;
use ordermodel\OrdersModel;
use metadatamodel\GroupOrderStatusModel;

use User;


class ReferController extends \BaseController {
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function getRefer()
    {
        $Code           = Input::has('code')        ? strtoupper(trim(Input::get('code'))) : null;
        $hasCloseStatus = Input::has('close')       ? Input::get('close') : "";
        $UserInfo   = $this->UserInfo();

        if(empty($Code) || $UserInfo['privilege'] < 1){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => [],
                'user'      => []

            );
            return Response::json($contents);
        }
        
        $Model       = new RequestModel;
        $ReferModel  = new ReferModel;

        $Code        = explode(',', $Code);
        $ListRefer   = $ReferModel::where('type',1)->whereIn('code', $Code)->get(array('ticket_id','type','code'))->toArray();
        $ListId      = [];
        if(!empty($ListRefer)){
            foreach($ListRefer as $val){
                $ListId[]   = $val['ticket_id'];
            }
        }
        //return $ListId;

        $ListTicket  = $Model->where('time_create', '>=', time() - $this->time_limit)->where(function($query) use($Code, $ListId, $hasCloseStatus){
                                if(!empty($ListId)){
                                    $query = $query->whereIn('id',$ListId);
                                } 
                                    if(!empty($hasCloseStatus)){
                                        $query = $query->where('status', '!=', 'CLOSED');
                                    }
                                    foreach ($Code as $key => $value) {
                                        $query = $query->orWhere('content','LIKE','%'.$value.'%')
                                        ->orWhere('title','LIKE','%'.$value.'%');    
                                    }
                                })
                             ->with(array(
                                'case_ticket',
                                 'assign'   => function($query){
                                     $query->where('active',1);
                                 }
                             ))
                             ->get()->toArray();

        if(!empty($ListTicket)) {
            $ListUser   = [];
            foreach($ListTicket as $key => $val){
                $ListUser[] = $val['user_id'];
                $ListTicket[$key]['action'] = 1;
                if(!empty($val['assign'])){
                    foreach($val['assign'] as $v){
                        $ListUser[] = $v['assign_id'];
                        if($UserInfo['id']  == $v['assign_id']){
                            $ListTicket[$key]['action'] = 0;
                        }
                    }
                }
            }

            if(!empty($ListUser)){
                $ListUser   = array_unique($ListUser);
                $UserModel  = new User;
                $User       = $UserModel->whereIn('id',$ListUser)->get(array('id','email','fullname','phone'))->toArray();
            }

        }



        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $ListTicket,
            'user'      => isset($User) ? $User : []

        );
        return Response::json($contents);

    }

    public function getReferseller()
    {
        $Code     = Input::has('code')        ? strtoupper(trim(Input::get('code'))) : null;
        $UserInfo = $this->UserInfo();
        
        if(empty($Code)/* || $UserInfo['privilege'] < 0*/){

            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => [],
                'user'      => []

            );
            return Response::json($contents);
        }

        $Model       = new RequestModel;
        $ReferModel  = new ReferModel;
        $OrdersModel = new OrdersModel;
        $Code        = explode(',', $Code);
        $Code        = array_unique($Code);
        

        $ListTrackingOrder = [];
        
        if(sizeof($Code) == 1){
            $ListGroupStatus       = array(41, 20);
            $GroupOrderStatusModel = new GroupOrderStatusModel;
            $ListStatusOrder       = $GroupOrderStatusModel::whereIn('group_status', $ListGroupStatus)->get(array('group_status', 'order_status_code'))->toArray();

            $ListStatus = [];

            if(!empty($ListStatusOrder)){
                foreach($ListStatusOrder as $val){
                    $ListStatus[]   = (int)$val['order_status_code'];
                }
            }

            // Lay don hang Phat khong thanh cong, XN chuyen hoan 

            $OrdersModel           = $OrdersModel
                                    ->where('tracking_code', $Code)
                                    ->with(['pipe_journey' => function ($query){
                                        $query->whereIn('pipe_status', [903, 707]);
                                    }, 'GroupStatus'])
                                    ->whereIn('status', $ListStatus )
                                    //->where('time_create', '<=', time() - 30 * 86400)
                                    ->select(array('status', 'tracking_code', 'id'))
                                    ->first();
            $ListTrackingOrder = $OrdersModel;

        }




        $ListRefer   = $ReferModel::where('type',1)->whereIn('code', $Code)->orderBy('id', 'ASC')->get(array('ticket_id', 'type', 'code'))->toArray();
        
        $ListId        = [];
        if(!empty($ListRefer)){
            foreach($ListRefer as $val){
                $ListId[]   = $val['ticket_id'];
            }
        }
        

        $ListTicket = [];
        if(!empty($ListId)){
            $ListTicket  = $Model->where(function($query) use($Code, $ListId, $UserInfo){
                if(!empty($ListId)){
                    $query->whereIn('id',$ListId);
                } 
                    
                    $query->where('user_id', (int)$UserInfo['id']);
                    $query->where('status', '!=', 'CLOSED');

                    
                    foreach ($Code as $key => $value) {
                        $query = $query->orWhere('content','LIKE','%'.$value.'%')
                        ->orWhere('title','LIKE','%'.$value.'%');    
                    }

                })
             ->with(array(
                'case_ticket',
                 'assign'   => function($query){
                     $query->where('active',1);
                 }
             ))
             ->limit(10)->get()->toArray();
             
         }


       



        $contents = array(
            'error'     => false,
            'message'   => 'success',
            'data'      => $ListTicket,
            'user'      => isset($User) ? $User : [],
            'order'     => $ListTrackingOrder

        );
        return Response::json($contents);

    }


    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function postCreate($id)
    {   
       /**
        *  Validation params
        * */
        Validator::getPresenceVerifier()->setConnection('ticketdb');
        $validation = Validator::make(array('ticket_id' => $id), array(
            'ticket_id'        => 'required|numeric|exists:ticket_request,id'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }
        
        
        $Data               = Input::json()->get('refer');
        $DataInsert         = [];
        
        if($Data){
            foreach($Data as $val){
                if(!empty($val['text'])){
                    $type = 2;
                    $val['text']    = strtoupper($val['text']);
                    if(preg_match('/^SC\d+$/i',$val['text'])){
                        $type = 1;
                    }
                    
                    $DataInsert[]   = array(
                        'ticket_id'     =>  $id,
                        'type'          => $type,
                        'code'          => strtoupper(trim($val['text']))
                    );
                }
            }

            if(!empty($DataInsert)){
                $Model              = new ReferModel;
                $Insert             = $Model::insert($DataInsert);
                
                $contents = array(
                    'error'     => false,
                    'message'   => 'success'
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'Data empty'
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'Data empty'
            );
        }
        
        return Response::json($contents);
    }


    public function postRemoveRefer (){
        
        $ReferId  = Input::has('refer_id')  ? Input::get('refer_id') : "";
        if(empty($ReferId)){
            return Response::json([
                'error'=> true,
                'error_message'=> "Lỗi !"
            ]);
        }


        $Data = ReferModel::where('id', $ReferId)->where('type', 3)->first();
        if(!empty($Data)){
            $Data->delete();
            return Response::json([
                'error'=> false,
                'error_message'=> "Thành công"
            ]);
        } 

        return Response::json([
            'error'         => true,
            'error_message' => "Không tìm thấy liên kết"
        ]);
    }
}
