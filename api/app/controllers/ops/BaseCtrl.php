<?php namespace ops;
use Elasticsearch\Client;

class BaseCtrl extends \BaseCtrl{

    public $time_limit  = 8035200;  // 93 ngày

    public $_error           = false;
    public $_error_code      = "";
    public $_error_message   = "";
    public $_total           = 0;



    function __construct(){
        
    }

    public function ExecuteES($Query){
        $result     = [];
        $Data       = [];

        $connParams = array();
        $connParams['hosts'] = ['10.0.20.164:9202'];

        try {
            $client = new Client($connParams);
            $result = $client->search($Query);
        } catch (Exception $e) {
            return Response::json([
                "error"             => true,
                "error_message"     => Lang::get('response.FAIL_QUERY'),
                "data"              => []
            ]);
        }

        $this->_total =  $result['hits']['total'];
        $hits = $result['hits']['hits'];
        foreach ($hits as $key => $value) {
            $Data[] = $value['_source'];
        }
        

        return $Data;
    }

    public function _ResponseData($data = [], $additional = []) {
        $returnData = [
            'error'         => $this->_error,
            'error_code'    => $this->_error_code,
            'error_message' => $this->_error_message,
            'data'          => $data
        ];
        
        $returnData = array_merge($returnData, $additional);
        
        return Response::json($returnData);
    }

    

    public function getPipeByGroup($json = true){
        $group          = Input::has('group')       ? (int)Input::get('group')      : 0;
        $type           = Input::has('type')        ? (int)Input::get('type')       : 0;
        $active         = Input::has('active')      ? (int)Input::get('active')     : null;

        $Model       = new \omsmodel\PipeStatusModel;

        if(!empty($group)){
            $Model = $Model->where('group_status', $group);
        }

        if(!empty($type)){
            $Model = $Model->where('type', $type);
        }

        if(isset($active)){
            $Model = $Model->where('active', $active);
        }

        $Data   = $Model->orderBy('priority', 'ASC')->get()->toArray();

        return $json ? Response::json(['error'     => false,'message'   => 'success','data'      => $Data]) : $Data;
    }

    public function getGroupProcess($json = true){
        $code           = Input::has('code')        ? trim(Input::get('code'))      : '';
        $type           = Input::has('type')        ? (int)Input::get('type')       : 0;

        $Model       = new \omsmodel\GroupProcessModel;

        if(!empty($code)){
            $Model = $Model->where('code', $code);
        }

        if(!empty($type)){
            $Model = $Model->where('type', $type);
        }

        $Data   = $Model->orderBy('id', 'ASC')->get()->toArray();

        return $json ?  Response::json(['error'  => false,'message'  => 'success',   'data'      => $Data]) : $Data;
    }

    public function getListCity(){
        $City   = [];
        if (Cache::has('list_city_cache')){
            $City    = Cache::get('list_city_cache');
        }else{
            $listCity           = \CityModel::all(array('id','city_name'));
            if(!$listCity->isEmpty()){
                foreach($listCity as $val){
                    $City[(int)$val['id']]   = $val['city_name'];
                }
                Cache::put('list_city_cache', $City, 1440);
            }
        }
        return $City;
    }

    public function getCityById($ListCityId){
        $City               = [];
        $CityGlobalModel    = new \CityGlobalModel;
        $ListCity           =  $CityGlobalModel::whereIn('id',$ListCityId)->get(['id','city_name'])->toArray();
        if(!empty($ListCity)){
            foreach($ListCity as $val){
                if(in_array($val['id'], [18,19,6,1,3,14,12,7,10,5,4,17,16,15,11,2,23,22,25,24,8,28,20,26,31,30,27,32,29,35,34,37,36,33])){
                    $val['city_name'] .= '(MB)';
                }else{
                    $val['city_name'] .= '(MN)';
                }
                $City[$val['id']]   = $val['city_name'];
            }
        }
        return $City;
    }

    public function getProvince($ListProvinceId){
        $Province      = [];
        $DistrictModel = new \DistrictModel;
        $ListProvince  =  $DistrictModel::whereIn('id',$ListProvinceId)->get(['id','district_name'])->toArray();
        if(!empty($ListProvince)){
            foreach($ListProvince as $val){
                $Province[$val['id']]   = $val['district_name'];
            }
        }
        return $Province;
    }

    public function getWard($ListWardId){
        $Ward      = [];
        $WardModel = new \WardModel;
        $ListWard  =  $WardModel::whereIn('id',$ListWardId)->get(['id','ward_name'])->toArray();
        if(!empty($ListWard)){
            foreach($ListWard as $val){
                $Ward[$val['id']]   = $val['ward_name'];
            }
        }
        return $Ward;
    }

    public function getUser($ListUserId){
        $User       = [];

        $ListUser   = \User::whereRaw("id in (". implode(",", $ListUserId) .")")->get(['id','fullname', 'phone', 'email'])->toArray();
        if(!empty($ListUser)){
            foreach($ListUser as $val){
                $User[$val['id']]   = $val;
            }
        }
        return $User;
    }

    public function getUserInfo($ListUserId){
        $UserModel = new \sellermodel\UserInfoModel;
        $User       = [];

        $ListUser   = $UserModel::whereRaw("user_id in (". implode(",", $ListUserId) .")")->get(['id','user_id', 'user_nl_id', 'pipe_status', 'priority_payment'])->toArray();
        if(!empty($ListUser)){
            foreach($ListUser as $val){
                $User[$val['user_id']]   = $val;
            }
        }
        return $User;
    }

    public function getPackageByTime($TimeStart, $TimeEnd){
        if(empty($TimeStart) && empty($TimeEnd)) return [];

        $Data = \warehousemodel\PackageModel::whereNotNull('tracking_code');

        if(!empty($TimeStart)){
            $TimeStart    = $this->__convert_time($TimeStart);
            $Data   = $Data->where('create','>=',$TimeStart);
        }

        if(!empty($TimeEnd)){
            $TimeEnd    = $this->__convert_time($TimeEnd);
            $Data   = $Data->where('create','<=',$TimeEnd);
        }

        return $Data->lists('tracking_code');

    }
}
