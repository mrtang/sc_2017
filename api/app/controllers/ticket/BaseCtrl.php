<?php namespace ticket;

class BaseCtrl extends \BaseCtrl{

    public $time_limit  = 8035200;  // 93 ngày

    function __construct(){

    }

    public function getUserAdmin($json = true){
        if(Cache::has('cache_user_admin')){
            $UserAdmin    =  Cache::get('cache_user_admin');
        }else{
            $UserAdmin    = \sellermodel\UserInfoModel::where('privilege','>','0')->whereNotIn('user_id',[521,30203])->with('user')->get(['user_id', 'group'])->toArray();
            if(!empty($UserAdmin)){
                Cache::put('cache_user_admin', $UserAdmin, 30);
            }
        }

        return $json ? Response::json(['error'  => false,'message'  => 'success','data' => $UserAdmin]) : $UserAdmin;
    }

    public function getGroupAssign($json = true) {
        if(Cache::has('cache_group_assign')){
            $GroupAssign    =  Cache::get('cache_group_assign');
        }else{
            $GroupAssign    = \ticketmodel\TicketGroupModel::with(['user_assign'])->get()->toArray();
            if(!empty($GroupAssign)){
                Cache::put('cache_group_assign', $GroupAssign, 30);
            }
        }

        return $json ? Response::json(['error'  => false,'message'  => 'success','data' => $GroupAssign]) : $GroupAssign;
    }
}
