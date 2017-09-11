<?php
namespace ops;

class KpiCtrl extends BaseCtrl
{
    private $error              = false;
    private $message            = 'success';
    private $total              = 0;
    private $data               = [];
    private $time_bonus         = 0;// thời gian xử lý cộng thêm cho ngày nghỉ, ngày lễ
    private $zurmo_crm          = 'http://crm.boxme.asia/index.php';
    private $zurmo_name         = 'hanvanloi';
    private $zurmo_pass         = 'sc123';
    private $record_date        = 25;

    function __construct(){

    }



    private function ResponseData(){

        return Response::json([
            'error'         => $this->error,
            'message'       => $this->message,
            'total'         => $this->total,
            'data'          => $this->data,
        ]);
    }

    public function getCategory(){
        $GroupCategoryId    = Input::has('group_category_id')   ? (int)Input::get('group_category_id')      : 0;
        $Group              = Input::has('group')               ? (int)Input::get('group')                  : 0;
        $Active             = Input::has('active')              ? (int)Input::get('active')                 : null;

        $Model          = new \reportmodel\KPICategoryModel;
        if(!empty($Group)){
            $ListGroupCategory = \reportmodel\KPIGroupCategoryModel::where('group', $Group)->lists('id');
            if(!empty($ListGroupCategory)){
                $Model          = $Model->whereIn('group_category_id',$ListGroupCategory);
            }else{
                return $this->ResponseData();
            }
        }

        if(!empty($GroupCategoryId)){
            $Model          = $Model->where('group_category_id',$GroupCategoryId);
        }

        if(isset($Active)){
            $Model          = $Model->where('active',$Active);
        }

        $this->data     = $Model->with('__group_category')->orderBy('group_category_id','DESC')->orderBy('id','DESC')->get()->toArray();
        return $this->ResponseData();
    }

    public function postCreateGroupCategory(){
        $Name       = Input::has('name')    ? trim(Input::get('name'))  : '';
        $Group      = Input::has('group')   ? (int)Input::get('group')  : 0;

        $validation = Validator::make(Input::all(), array(
            'name'              => 'required',
            'group'             => 'required|integer|min:1'
        ));

        //error
        if($validation->fails()) {
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => $validation->messages()]);
        }

        //Check group user
        $GroupUser  = \omsmodel\GroupUserModel::where('id', $Group)->where('active', 1)->count();
        if(empty($GroupUser)){
            return Response::json(['error' => true, 'code' => 'GROUP_ERROR','error_message' => 'Nhóm nhân viên không tồn tại']);
        }

        try{
            \reportmodel\KPIGroupCategoryModel::insert([
                'group'         => $Group,
                'group_name'    => $Name,
                'active'        => 1,
                'time_create'   => $this->time()
            ]);
        }catch(\Exception $e){
            return Response::json([
                'error'         => true,
                'code'          => 'INSERT_ERROR',
                'error_message' => 'Thêm mới thất bại '. $e->getMessage()
            ]);
        }
        Cache::forget('cache_kpi_group_category_'.$Group);
        return Response::json([
            'error'         => false,
            'code'          => 'SUCCESS',
            'error_message' => 'Thành Công'
        ]);
    }

    public function postCreateCategory(){
        $Id                 = Input::has('id')                  ? (int)Input::get('id')                 : 0;
        $GroupCategoryId    = Input::has('group_category_id')   ? (int)Input::get('group_category_id')  : 0;
        $WordName           = Input::has('work_name')           ? trim(Input::get('work_name'))         : '';
        $Percent            = Input::has('percent')             ? (int)Input::get('percent')            : 0;
        $Weight             = Input::has('weight')              ? (int)Input::get('weight')             : 0;
        $Target             = Input::has('target')              ? (int)Input::get('target')             : 0;
        $Active             = Input::has('active')              ? (int)Input::get('active')             : null;

        $validation = Validator::make(Input::all(), array(
            'group_category_id' => 'sometimes|required|integer|min:1',
            'work_name'         => 'sometimes|required',
            'percent'           => 'sometimes|required|integer|min:1|max:100',
            'weight'            => 'sometimes|required|integer|min:1|max:100'
        ));

        //error
        if($validation->fails()) {
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => $validation->messages()]);
        }

        $Category   = new \reportmodel\KPICategoryModel;
        if(!empty($Id)){
            $Category   = $Category::find($Id);
            if(!isset($Category->id)){
                return Response::json(['error' => true, 'code' => 'CATEGORY_ERROR','error_message' => 'Công việc không tồn tại']);
            }

            $GroupCategoryId = $Category->group_category_id;
        }else{
            $Category->time_create = time();
        }

        //Check group category
        $GroupCategory  = \reportmodel\KPIGroupCategoryModel::where('id', $GroupCategoryId)->where('active', 1)->count();
        if(empty($GroupCategory)){
            return Response::json(['error' => true, 'code' => 'GROUP_CATEGORY_ERROR','error_message' => 'Nhóm công việc không tồn tại']);
        }

        if(!empty($GroupCategoryId)){
            $Category->group_category_id    = $GroupCategoryId;
        }

        if(!empty($WordName)){
            $Category->work_name    = $WordName;
        }

        if(!empty($Percent)){
            $Percent              = $Percent/100;
            $Category->percent    = $Percent;
        }

        if(!empty($Weight)){
            $Category->weight    = $Weight;
        }

        if(!empty($Target)){
            $Category->target    = $Target;
        }

        if(isset($Active)){
            $Category->active    = $Active;
        }

        try{
            $Category->save();
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
            'data'          => $Category->id
        ]);
    }

    public function postCreateConfig(){
        $CategoryId         = Input::has('category_id')         ? (int)Input::get('category_id')        : 0;
        $UserId             = Input::has('user_id')             ? (int)Input::get('user_id')            : 0;
        $Active             = Input::has('active')              ? (int)Input::get('active')             : null;

        $validation = Validator::make(Input::all(), array(
            'category_id'       => 'sometimes|required|integer|min:1',
            'user_id'           => 'sometimes|required|integer|min:1',
            'group_category_id' => 'sometimes|required|integer|min:1',
            'active'            => 'sometimes|required|in:0,1'
        ));

        //error
        if($validation->fails()) {
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => $validation->messages()]);
        }

        //Check User
        $UserInfo = \sellermodel\UserInfoModel::where('user_id', $UserId)->where('privilege','>', 0)->first();
        if(!isset($UserInfo->user_id)){
            return Response::json(['error' => true, 'code' => 'USER_ERROR', 'error_message' => 'Người dùng không tồn tại']);
        }

        //Check Category
        $Category   = \reportmodel\KPICategoryModel::where('id', $CategoryId)->where('active', 1)->first();
        if(!isset($Category->id)){
            return Response::json(['error' => true, 'code' => 'CATEGORY_ERROR', 'error_message' => 'Loại công việc không tồn tại']);
        }
        //CheckGroupCategory
        $GroupCategory   = \reportmodel\KPIGroupCategoryModel::where('id', $Category->group_category_id)->where('active', 1)->first();
        if(!isset($GroupCategory->id)){
            return Response::json(['error' => true, 'code' => 'GROUP_CATEGORY_ERROR', 'error_message' => 'Nhóm công việc không tồn tại']);
        }

        if($GroupCategory->group != $UserInfo->group){
            return Response::json(['error' => true, 'code' => 'GROUP_ERROR', 'error_message' => 'Nhóm công việc không phù hợp với người được giao']);
        }

        $Config = \reportmodel\KPIConfigModel::where('category_id', $CategoryId)->where('user_id', $UserId)->first();
        if(!isset($Config->id)){
            $Config =  new \reportmodel\KPIConfigModel;
            $Config->category_id    = $CategoryId;
            $Config->user_id        = $UserId;
            $Config->time_create    = time();
        }

        if(isset($Active)){
            $Config->active = $Active;
        }

        try{
            $Config->save();
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
            'data'          => $Config->id
        ]);
    }

    public function getConfig(){
        $UserId         = Input::has('user_id')             ? (int)Input::get('user_id')        : 0;
        $Group          = Input::has('group')               ? (int)Input::get('group')          : 0;
        $Active         = Input::has('active')              ? (int)Input::get('active')         : null;

        $Model          = new \reportmodel\KPIConfigModel;

        if(!empty($Group)){
            $ListUserId = \sellermodel\UserInfoModel::where('group', $Group)->lists('user_id');
            if(!empty($ListUserId)){
                $Model          = $Model->whereIn('user_id',$ListUserId);
            }else{
                return $this->ResponseData();
            }
        }

        if(!empty($UserId)){
            $Model          = $Model->where('user_id',$UserId);
        }

        if(isset($Active)){
            $Model          = $Model->where('active',$Active);
        }

        $this->data     = $Model->with('__category')->orderBy('category_id','DESC')->orderBy('id','DESC')->get()->toArray();
        return $this->ResponseData();
    }

    /***
     *
     *
     *  Get Infomation Zurmo CRM
     *
     */
    public function getLoginZurmo(){
        $CacheName = 'LoginZurmoCache';
        if(Cache::has($CacheName)){
            $cache = Cache::get($CacheName);
            if(empty($cache)){
                Cache::forget($CacheName);
            }
            else{
                return ['error' => false, 'message' => 'SUCCESS', 'data' => $cache];
            }
        }

        $request = \cURL::newRequest('post', $this->zurmo_crm.'/zurmo/api/login', [])
            ->setHeader('Accept', 'application/json')
            ->setHeader('ZURMO_AUTH_USERNAME', $this->zurmo_name)
            ->setHeader('ZURMO_AUTH_PASSWORD', $this->zurmo_pass)
            ->setHeader('ZURMO_API_REQUEST_TYPE', 'REST')
            ->send();

        if(empty($request)){
            return ['error' => true, 'message' => 'LOGIN_ERROR', 'data' => []];
        }

        $request = json_decode($request, 1);
        if(isset($request['status']) && $request['status'] == 'SUCCESS'){
            Cache::put($CacheName, $request['data'], 60);
            return ['error' => false, 'message' => 'SUCCESS', 'data' => $request['data']];
        }else{
            return ['error' => true, 'message' => 'LOGIN_ERROR', 'data' => $request];
        }
    }

    public function getUpdateEmployeeSale(){
        $LoginData = $this->getLoginZurmo();
        if($LoginData['error']){
            return $LoginData;
        }

        $Data = [
            'pagination'    => [
                'page'      => 1,
                'pageSize'  => 10000
            ]
        ];

        $request = \cURL::newRequest('get', $this->zurmo_crm.'/users/user/api/list/filter/'.http_build_query($Data), [])
            ->setHeader('Accept', 'application/json')
            ->setHeader('ZURMO_SESSION_ID', $LoginData['data']['sessionId'])
            ->setHeader('ZURMO_TOKEN', $LoginData['data']['token'])
            ->setHeader('ZURMO_API_REQUEST_TYPE', 'REST')
            ->send();

        if(empty($request)){
            return ['error' => true, 'message' => 'API_ERROR', 'data' => []];
        }

        $request = json_decode($request, 1);
        if(isset($request['status']) && $request['status'] == 'SUCCESS'){
            $Data = $request['data']['items'];

            if(!empty($Data)){
                $Insert         = [];
                $UpdateActive   = [];
                $UpdateUnactive = [];
                $ListEmail      = [];
                $ListEmailUnset = [];

                foreach($Data as $val){
                    if(isset($val['primaryEmail']['emailAddress']) && !empty($val['primaryEmail']['emailAddress'])){
                        $val['primaryEmail']['emailAddress']    = trim(strtolower($val['primaryEmail']['emailAddress']));
                        $Insert[$val['primaryEmail']['emailAddress']] = [
                            'zurmo_id'  => (int)$val['id'],
                            'email'     => $val['primaryEmail']['emailAddress'],
                            'active'    => (int)$val['isActive']
                        ];
                        $ListEmail[]    = $val['primaryEmail']['emailAddress'];
                    }
                }

                $ListEmployee = \reportmodel\CrmEmployeeModel::where('group_config','<',20)->get()->toArray();
                if(!empty($ListEmployee)){
                    foreach($ListEmployee as $val){
                        if(isset($Insert[$val['email']])){
                            if($val['active'] != $Insert[$val['email']]['active']){
                                if($Insert[$val['email']]['active'] == 0){
                                    $UpdateUnactive[]   = $Insert[$val['email']]['zurmo_id'];
                                }else{
                                    $UpdateActive[]     = $Insert[$val['email']]['zurmo_id'];
                                }
                            }
                            $ListEmailUnset[]   = $val['email'];
                            unset($Insert[$val['email']]);
                        }
                    }
                }

                //Xử lý
                $DataInsert = [];
                if(!empty($Insert)){ // Còn dữ liệu mới cần cập nhật
                    if(!empty($ListEmailUnset)){
                        $ListEmail  = array_diff($ListEmail, $ListEmailUnset);
                    }

                    $ListUser   = \User::whereIn('email', $ListEmail)->with('user_info')->get(['id', 'email', 'city_id'])->toArray();
                    if(!empty($ListUser)){
                        foreach($ListUser as $val){
                            if(isset($Insert[$val['email']]) && isset($val['user_info']) && $val['user_info']['group'] == 3){
                                $DataInsert[]   = [
                                    'user_id'       => (int)$val['id'],
                                    'country_id'    => (int)$val['user_info']['country_id'],
                                    'city_id'       => (int)$val['city_id'],
                                    'crm_id'        => $Insert[$val['email']]['zurmo_id'],
                                    'email'         => $Insert[$val['email']]['email'],
                                    'active'        => $Insert[$val['email']]['active']
                                ];
                            }
                        }
                    }

                }

                $CrmEmployeeModel   = new \reportmodel\CrmEmployeeModel;
                $CrmEmployeeModel   = $CrmEmployeeModel::where('group_config','<',20);
                try{
                    if(!empty($DataInsert)){
                        $ModelInsert    = clone $CrmEmployeeModel;
                        $ModelInsert::insert($DataInsert);
                    }

                    if(!empty($UpdateActive)){
                        $ModelUpdateActive = clone $CrmEmployeeModel;
                        $ModelUpdateActive::whereIn('crm_id', $UpdateActive)->update(['active' => 1]);
                    }

                    if(!empty($UpdateUnactive)){
                        $ModelUpdateUnActive = clone $CrmEmployeeModel;
                        $ModelUpdateUnActive::whereIn('crm_id', $UpdateActive)->update(['active' => 0]);
                    }

                }catch (Exception $e){
                    return ['error' => true, 'message' => 'UPDATE_ERROR', 'data' => $e->getMessage()];
                }
            }
            return ['error' => false, 'message' => 'SUCCESS'];

        }else{
            return ['error' => true, 'message' => 'API_ERROR', 'data' => $request];
        }

    }

    private function __calculate_commission($TotalRevenue){
        if($TotalRevenue < 20000000){
            $Commission = $TotalRevenue*0.03;
        }elseif($TotalRevenue < 40000000){
            $Commission = $TotalRevenue*0.035;
        }elseif($TotalRevenue < 60000000){
            $Commission = $TotalRevenue*0.04;
        }elseif($TotalRevenue < 80000000){
            $Commission = $TotalRevenue*0.05;
        }else{
            $Commission = $TotalRevenue*0.06;
        }

        return ceil($Commission);
    }

    public function getSaleSalary(){
        //$Date   = date('Y-m-d', strtotime(' -4 day'));
        $Date   = Input::has('date')             ? trim(Input::get('date'))               : '';
        $Start  = explode('-', $Date);
        //if $Start[2] == $this->record_date  => ngày chốt
        // Từ 1 -> 20 : group sale
        $Employee   = \reportmodel\CrmEmployeeModel::where('group_config','<',20)
                                                   ->where('active',1)
                                                   ->where('date','<>', $Date)->first();
        if(!isset($Employee->id)){
            return Response::json(['error' => false, 'code' => 'PROCESSED', 'error_message' => 'Xử lý xong']);
        }

        //Lấy danh sách category_id
        $ListCategory   = \reportmodel\KPIConfigModel::where('active',1)
                                                     ->where('user_id', $Employee->user_id)
                                                     //->where('time_create', '<', strtotime($Date))
                                                     ->lists('category_id');
        if(empty($ListCategory)){
            return Response::json(['error' => true, 'code' => 'EMPTY_CATEGORY', 'data' => $Employee]);
        }

        // Chi tiết Kpi
        $ListKPI    = \reportmodel\KPICategoryModel::whereIn('id', $ListCategory)->get(['id','code'])->toArray();
        if(empty($ListKPI)){
            return Response::json(['error' => true, 'code' => 'EMPTY_KPI_CATEGORY', 'error_message' => 'Lỗi danh sách kpi']);
        }
        $ReferKpi   = [];
        foreach($ListKPI as $val){
            $ReferKpi[(int)$val['id']]  = $val;
        }

        //Lấy danh sách KPI
        $ListKpi    = \reportmodel\KPIModel::whereIn('user_id', [0, $Employee->user_id])
                                            ->whereIn('category_id',$ListCategory)
                                            ->where('date', $Date)
                                            ->orderBy('user_id', 'DESC')
                                            ->get()->toArray();
        if(empty($ListKpi)){
            return Response::json(['error' => true, 'code' => 'EMPTY_LIST_CATEGORY', 'error_message' => 'Lỗi danh sách kpi']);
        }

        $ListDetailInsert = [];
        foreach($ListKpi as $val){
            $ListDetailInsert[$val['category_id']]    = [
                'user_id'           => $Employee->user_id,
                'month'             => (int)$Start[1],
                'year'              => (int)$Start[0],
                'category_id'       => $val['category_id'],
                'percent'           => $val['percent'],
                'succeed'           => $val['succeed'],
                'revenue_firstmonth' => $val['revenue_firstmonth'],
                'revenue_nextmonth'  => $val['revenue_nextmonth'],
                'total'             => $val['total'],
                'weight'            => $val['weight'],
                'succeed_target'    => $val['succeed_target'],
                'percent_target'    => $val['percent_target'],
                'time_create'   => time()
            ];
        }

        $TotalWeight    = 0;
        $TotalPercent   = 0;
        $TotalRevenue   = 0;
        $Insert     = [
            'user_id'       => $Employee->user_id,
            'date'          => $Date,
            'percent'       => 0,
            'commission'    => 0,
            'bonus'         => 0,
            'salary'        => 0,
            'allowance'     => 0,
            'time_create'   => time(),
            'active'        => 0
        ];
        foreach($ListDetailInsert as $key => $val){
            $TotalWeight    += $val['weight'];
            // Doanh thu tính 10% phí VAT
            $val['percent'] = round(($val['percent']/$val['percent_target']),4);

            if(isset($ReferKpi[$val['category_id']]) && in_array($ReferKpi[$val['category_id']]['code'], ['revenue', 'team revenue'])){
                $val['succeed']             = round(($val['succeed']/1.1),0);
                $val['percent']             = round(($val['percent']/1.1),4);
                $val['revenue_firstmonth']  = round(($val['revenue_firstmonth']/1.1),0);
                $val['revenue_nextmonth']   = round(($val['revenue_nextmonth']/1.1),0);

                $ListDetailInsert[$key]['percent']               = $val['percent'];
                $ListDetailInsert[$key]['succeed']               = $val['succeed'];
                $ListDetailInsert[$key]['revenue_firstmonth']    = $val['revenue_firstmonth'];
                $ListDetailInsert[$key]['revenue_nextmonth']     = $val['revenue_nextmonth'];

                if($ReferKpi[$val['category_id']]['code'] == 'revenue'){
                    $TotalRevenue   = $val['succeed'];
                }
            }

            $TotalPercent   += $val['percent']*$val['weight'];
        }
        $Insert['percent']  = round(($TotalPercent/$TotalWeight),4);

        // Salary
        $SalaryPercent  = 0;
        $Bonus          = 0; // Thưởng kpi
        $Commission     = 0; // Hoa hồng doanh thu
        if($Insert['percent'] < 1){
            $SalaryPercent = $Insert['percent'];
        }elseif($Insert['percent'] <= 1.2){
            $SalaryPercent = 1;
        }else{
            $SalaryPercent = 1;
            $Bonus         = 300000;
        }
        $Insert['salary']       = $SalaryPercent*$Employee->salary;
        $Insert['bonus']        = $Bonus;
        $Insert['allowance']    = ($TotalRevenue*0.01 < 300000) ? $TotalRevenue*0.01 : 300000;
        $Insert['commission']   = $this->__calculate_commission($TotalRevenue);

        DB::connection('reportdb')->beginTransaction();
        try{
            if(date('j', strtotime('+1 days', strtotime($Date))) == $this->record_date){
                // Ngày chốt
                \reportmodel\KPIEmployeeDetailModel::insert($ListDetailInsert);
                $Insert['active']   = 1;
            }

            \reportmodel\KPIEmployeeModel::insert($Insert);
            $Employee->date = $Date;
            $Employee->save();
            DB::connection('reportdb')->commit();
        }catch (\Exception $e){
            return Response::json(['error' => false, 'code' => 'INSERT_ERROR', 'error_message' => $e->getMessage()]);
        }
        return Response::json(['error' => false, 'code' => 'SUCCESS', 'error_message' => 'Thành công']);
    }

    public function getSalary(){
        $Date     = date('Y-m-d', strtotime(' -2 day'));
        $Start  = explode('-', $Date);
        //if $Start[2] == $this->record_date  => ngày chốt
        // Từ 1 -> 20 : group sale
        $Employee   = \reportmodel\CrmEmployeeModel::where('group_config','>=',20)->where('group_config','<',30)->where('active',1)->where('date','<>', $Date)->first();
        if(!isset($Employee->id)){
            return Response::json(['error' => false, 'code' => 'PROCESSED', 'error_message' => 'Xử lý xong']);
        }

        //Lấy danh sách category_id
        $ListCategory   = \reportmodel\KPIConfigModel::where('active',1)
            ->where('user_id', $Employee->user_id)
            ->lists('category_id');
        if(empty($ListCategory)){
            return Response::json(['error' => true, 'code' => 'EMPTY_CATEGORY', 'data' => $Employee]);
        }

        // Chi tiết Kpi
        $ListKPI    = \reportmodel\KPICategoryModel::whereIn('id', $ListCategory)->get(['id','code'])->toArray();
        if(empty($ListKPI)){
            return Response::json(['error' => true, 'code' => 'EMPTY_KPI_CATEGORY', 'error_message' => 'Lỗi danh sách kpi']);
        }
        $ReferKpi   = [];
        foreach($ListKPI as $val){
            $ReferKpi[(int)$val['id']]  = $val;
        }

        //Lấy danh sách KPI
        $ListKpi    = \reportmodel\KPIModel::whereIn('user_id', [0, $Employee->user_id])
            ->whereIn('category_id',$ListCategory)
            ->where('date', $Date)
            ->orderBy('user_id', 'DESC')
            ->get()->toArray();
        if(empty($ListKpi)){
            return Response::json(['error' => true, 'code' => 'EMPTY_LIST_CATEGORY', 'error_message' => 'Lỗi danh sách kpi']);
        }

        $ListDetailInsert = [];
        foreach($ListKpi as $val){
            $ListDetailInsert[$val['category_id']]    = [
                'user_id'           => $Employee->user_id,
                'month'             => (int)$Start[1],
                'year'              => (int)$Start[0],
                'category_id'       => $val['category_id'],
                'percent'           => $val['percent'],
                'succeed'           => $val['succeed'],
                'revenue_firstmonth' => $val['revenue_firstmonth'],
                'revenue_nextmonth'  => $val['revenue_nextmonth'],
                'total'             => $val['total'],
                'weight'            => $val['weight'],
                'succeed_target'    => $val['succeed_target'],
                'percent_target'    => $val['percent_target'],
                'time_create'   => time()
            ];
        }

        $TotalWeight    = 0;
        $TotalPercent   = 0;
        $TotalRevenue   = 0;
        $Insert     = [
            'user_id'       => $Employee->user_id,
            'date'          => $Date,
            'percent'       => 0,
            'commission'    => 0,
            'bonus'         => 0,
            'salary'        => 0,
            'allowance'     => 0,
            'time_create'   => time(),
            'active'        => 0
        ];
        foreach($ListDetailInsert as $key => $val){
            $TotalWeight    += $val['weight'];
            $TotalPercent   += $val['percent']*$val['weight'];
        }
        $Insert['percent']  = round(($TotalPercent/$TotalWeight),4);

        // Lấy tham chiếu về tính kpi
        $ReferKpi   = \reportmodel\KPIReferModel::where('group_config', $Employee->group_config)
            ->where('level', $Employee->level)
            ->where('active',1)
            ->where('start','<=',$Insert['percent'])
            ->where('end','>',$Insert['percent'])
            ->orderBy('id','DESC')
            ->first();
        if(!isset($ReferKpi->id)){
            return Response::json(['error' => false, 'code' => 'EMPTY_LIST_REFER_KPI', 'error_message' => 'Chưa có danh sách tính kpi']);
        }

        // Salary
        $SalaryPercent  = $ReferKpi->percent;
        $Bonus          = $ReferKpi->bonus; // Thưởng kpi
        $Allowance      = $ReferKpi->allowance;

        $Insert['salary']       = $SalaryPercent*$Employee->kpi + $Employee->salary;
        $Insert['bonus']        = $Bonus;
        $Insert['allowance']    = $Allowance;

        DB::connection('reportdb')->beginTransaction();
        try{
            if(date('j', strtotime('+1 days', strtotime($Date))) == $this->record_date){
                // Ngày chốt
                \reportmodel\KPIEmployeeDetailModel::insert($ListDetailInsert);
                $Insert['active']   = 1;
            }

            \reportmodel\KPIEmployeeModel::insert($Insert);
            $Employee->date = $Date;
            $Employee->save();
            DB::connection('reportdb')->commit();
        }catch (\Exception $e){
            return Response::json(['error' => false, 'code' => 'INSERT_ERROR', 'error_message' => $e->getMessage()]);
        }
        return Response::json(['error' => false, 'code' => 'SUCCESS', 'error_message' => 'Thành công']);
    }

    /**
     * Get Data
     */
    public function getOpportunity($json = true){
        $page               = Input::has('page')                ? (int)Input::get('page')                   : 1;
        $pageSize           = Input::has('page_size')           ? (int)Input::get('page_size')              : 10;
        $TimeStart          = Input::has('time_start')          ? (int)Input::get('time_start')             : 0;
        $TimeEnd            = Input::has('time_end')            ? (int)Input::get('time_end')               : 0;
        $OwnerId            = Input::has('owner_id')            ? (int)Input::get('owner_id')               : 0;
        $Stage              = Input::has('stage')               ? trim(Input::get('stage'))                 : '';

//        1 -  tháng này
//        2 - 30 ngày trước
//        3 - 90 ngày trước
//        4 - 1 năm trước
        $Interval            = Input::has('interval')            ? (int)Input::get('interval')               : 0;
        if(empty($TimeStart) && !empty($Interval)){
            switch ($Interval) {
                case 4:
                    $TimeStart = strtotime(date('Y-12-25 00:00:00', strtotime('-1 year')));
                    break;

                case 2:
                    if(date('d') >= 25){
                        $TimeStart  = strtotime(date('Y-m-25 00:00:00'), strtotime("-1 month"));
                    }else{
                        $TimeStart  = strtotime(date('Y-m-25 00:00:00', strtotime("-2 month")));
                    }
                    break;

                case 3:
                    if(date('d') >= 25){
                        $TimeStart  = strtotime(date('Y-m-25 00:00:00'), strtotime("-3 month"));
                    }else{
                        $TimeStart  = strtotime(date('Y-m-25 00:00:00', strtotime("-4 month")));
                    }
                    break;

                default:
                    if(date('d') >= 25){
                        $TimeStart  = strtotime(date('Y-m-25 00:00:00'));
                    }else{
                        $TimeStart  = strtotime(date('Y-m-25 00:00:00', strtotime("-1 month")));
                    }
            }
        }

        $LoginData = $this->getLoginZurmo();
        if($LoginData['error']){
            return $json ? Response::json($LoginData) : $LoginData;
        }

        if(empty($TimeStart)){
            return $json ? Response::json(['error' => true, 'message' => 'timeStart_EMPTY']) : ['error' => true, 'message' => 'timeStart_EMPTY'];
        }

        if(empty($OwnerId)){
            return $json ? Response::json(['error' => true, 'message' => 'OwnerId_EMPTY']) : ['error' => true, 'message' => 'OwnerId_EMPTY'];
        }

        if(empty($Stage)){
            return $json ? Response::json(['error' => true, 'message' => 'Stage_EMPTY']) : ['error' => true, 'message' => 'Stage_EMPTY'];
        }

        $TimeStart  = date("Y-m-d H:i:s", $TimeStart);
        if(!empty($TimeEnd)){
            $TimeEnd  = date("Y-m-d H:i:s", $TimeEnd);
        }

        if($Stage == 'ALL'){
            $searchAttributeData    = [
                'clauses'   => [
                    1 => array(
                        'attributeName'         => 'owner',
                        'relatedAttributeName'  => 'id',
                        'operatorType'          => 'equals',
                        'value'                 => $OwnerId
                    ),
                    2 => array(
                        'attributeName'         => 'createdDateTime',
                        'operatorType'          => 'greaterThanOrEqualTo',
                        'value'                 => $TimeStart
                    )
                ],
                'structure' => '1 AND 2'
            ];

            if(!empty($TimeEnd)){
                $searchAttributeData['clauses'][]  = [
                    'attributeName'         => 'createdDateTime',
                    'operatorType'          => 'lessThanOrEqualTo',
                    'value'                 => $TimeEnd
                ];
                $searchAttributeData['structure']   = '1 AND 2 AND 3';
            }
        }else{
            $searchAttributeData    = [
                'clauses'   => [
                    1 => array(
                        'attributeName'         => 'stage',
                        'relatedAttributeName'  => 'value',
                        'operatorType'          => 'startsWith',
                        'value'                 => $Stage
                    ),
                    2 => array(
                        'attributeName'         => 'owner',
                        'relatedAttributeName'  => 'id',
                        'operatorType'          => 'equals',
                        'value'                 => $OwnerId
                    ),
                    3 => array(
                        'attributeName'         => 'stageModifiedDateTime',
                        'operatorType'          => 'greaterThanOrEqualTo',
                        'value'                 => $TimeStart
                    )
                ],
                'structure' => '1 AND 2 AND 3'
            ];

            if(!empty($TimeEnd)){
                $searchAttributeData['clauses'][]  = [
                    'attributeName'         => 'createdDateTime',
                    'operatorType'          => 'lessThanOrEqualTo',
                    'value'                 => $TimeEnd
                ];
                $searchAttributeData['structure']   = '1 AND 2 AND 3 AND 4';
            }
        }

        $Data = [
            'pagination' => [
                'page'     => $page,
                'pageSize' => $pageSize
            ],
            'search' => [
                'modelClassName'            => 'Opportunity',
                'searchAttributeData'       => $searchAttributeData
            ]
        ];

        $request = \cURL::newRequest('post', 'http://crm.boxme.asia/index.php/opportunities/opportunity/api/search/filter', ['data' => $Data])
                            ->setHeader('Accept', 'application/json')
                            ->setHeader('ZURMO_SESSION_ID', $LoginData['data']['sessionId'])
                            ->setHeader('ZURMO_TOKEN', $LoginData['data']['token'])
                            ->setHeader('ZURMO_API_REQUEST_TYPE', 'REST')
                            ->send();

        if(empty($request)){
            return $json ? Response::json(['error' => true, 'message' => 'API_ERROR']) : ['error' => true, 'message' => 'API_ERROR'];
        }

        $request = json_decode($request, 1);
        if(isset($request['status']) && $request['status'] == 'SUCCESS'){
            $Respond    = ['error' => false, 'message' => 'SUCCESS', 'data' => $request['data']];
        }else{
            $Respond    = ['error' => true, 'message' => 'API_ERROR', 'data' => $request];
        }

        return $json ? Response::json($Respond) : $Respond;
    }

    /***
     *
     *
     * PROCESSING KPI
     *
     *
     */


    public function getProcessingKpi($Category){
        $Date       = date('Y-m-d');
        $Category   = (int)$Category;

        $KPI = \reportmodel\KPICategoryModel::where('date','<>', $Date)->where('id', $Category)->orderBy('id', 'ASC')->first();
        if(!isset($KPI->id)){
            return Response::json(['error' => false, 'code' => 'PROCESSED', 'error_message' => 'Xử lý xong']);
        }

        $funcName   = '__category_'.$Category;
        if(method_exists($this ,$funcName)){
            // Call function
            $DataCategory = \reportmodel\KPIModel::firstOrCreate([
                'date'           => $Date,
                'category_id'   => $Category,
            ]);

            $result     = $this->$funcName($DataCategory);
            if($result['error']){
                return Response::json($result);
            }
        }else{
            return Response::json(['ERROR' => 'FUNCTION_NOT_EXISTS', 'DATA' => $funcName]);
        }

        DB::connection('reportdb')->beginTransaction();
        try{
            $DataCategory->percent          = round(($result['succeed']/$result['total']),3);
            $DataCategory->succeed          = $result['succeed'];
            $DataCategory->total            = $result['total'];
            $DataCategory->time_create      = time();
            $DataCategory->save();

            $KPI->date          = $Date;
            $KPI->save();

            if(!empty($result['data'])){
                $LMongo = new \LMongo;
                $LMongo::collection('log_kpi')->batchInsert($result['data']);
            }

            DB::connection('reportdb')->commit();
        }catch (\Exception $e){
            return Response::json(['error' => true, 'message' => $e->getMessage()]);
        }

        $LMongo     = new \LMongo;

        return Response::json(['error' => false, 'message' => 'Thành công']);
    }

    public function getKpiSaleProcessing(){
        $Date       = date('Y-m-d', strtotime(' -1 day'));

        $ListKPI = \reportmodel\KPICategoryModel::whereIn('id', [12,13])->get()->toArray();
        if(empty($ListKPI)){
            return Response::json(['error' => false, 'code' => 'EMPTY', 'error_message' => 'Không có dữ liệu']);
        }

        $KPI        = [];
        $DataKpi    = [];
        foreach($ListKPI as $val){
            $KPI[]                  = $val['id'];
            $DataKpi[$val['id']]    = $val;
        }


        $Config = \reportmodel\KPIConfigModel::whereIn('category_id', $KPI)->where('date','<>', $Date)->where('active',1)
                                             ->orderBy('category_id','ASC')->first();
        if(!isset($Config->id)){
            return $this->getKpiSaleFulfill();
            //return Response::json(['error' => false, 'code' => 'PROCESSED', 'error_message' => 'Xử lý xong']);
        }

        $funcName   = '__category_'.$Config->category_id;
        if(method_exists($this ,$funcName)){
            // Call function
            $DataCategory = \reportmodel\KPIModel::firstOrCreate([
                'date'              => $Date,
                'user_id'           => $Config->user_id,
                'category_id'       => $Config->category_id
            ]);

            $DataCategory->succeed_target   = $DataKpi[$Config->category_id]['target'];
            $DataCategory->percent_target   = $DataKpi[$Config->category_id]['percent'];
            $DataCategory->weight           = $DataKpi[$Config->category_id]['weight'];
            $result                         = $this->$funcName($DataCategory);
            if($result['error']){
                return Response::json($result);
            }
        }else{
            return Response::json(['ERROR' => 'FUNCTION_NOT_EXISTS', 'DATA' => $funcName]);
        }

        DB::connection('reportdb')->beginTransaction();
        try{
            $DataCategory->time_create      = time();
            $DataCategory->save();

            $Config->date   = $Date;
            $Config->save();
            DB::connection('reportdb')->commit();
        }catch (\Exception $e){
            return Response::json(['error' => true, 'message' => $e->getMessage()]);
        }

        return Response::json(['error' => false, 'message' => 'Thành công']);
    }

    public function getKpiSaleRevenue(){
        $Date       = date('Y-m-d', strtotime('-3 day'));

        $ListKPI = \reportmodel\KPICategoryModel::whereIn('code', ['revenue', 'team revenue'])->get()->toArray();
        if(empty($ListKPI)){
            return Response::json(['error' => false, 'code' => 'EMPTY', 'error_message' => 'Không có dữ liệu']);
        }

        $KPI            = [];
        $DataKpi        = [];

        $KPITeam        = [];
        $DataKpiTeam    = [];

        foreach($ListKPI as $val){
            if($val['code'] == 'revenue'){
                $KPI[]                  = $val['id'];
                $DataKpi[$val['id']]    = $val;
            }else{
                $KPITeam[]                  = $val['id'];
                $DataKpiTeam[$val['id']]    = $val;
            }
        }

        $Config = \reportmodel\KPIConfigModel::whereIn('category_id', $KPI)->where('date','<>', $Date)
                                             ->where('active',1)->orderBy('category_id','ASC')->first();
        if(!isset($Config->id)){
            return Response::json(['error' => false, 'code' => 'PROCESSED', 'error_message' => 'Xử lý xong']);
        }

        if(!empty($KPITeam)){
            $ConfigKpiTeam  = \reportmodel\KPIConfigModel::whereIn('category_id', $KPITeam)->where('user_id', $Config->user_id)
                                                         ->where('active',1)->orderBy('id','ASC')->first();
        }

        $funcName   = '__category_'.$DataKpi[$Config->category_id]['code'];
        if(method_exists($this ,$funcName)){
            // Call function
            $DataCategory = \reportmodel\KPIModel::firstOrCreate([
                'date'              => $Date,
                'user_id'           => $Config->user_id,
                'category_id'       => $Config->category_id
            ]);

            $DataCategory->succeed_target   = $DataKpi[$Config->category_id]['target'];
            $DataCategory->percent_target   = $DataKpi[$Config->category_id]['percent'];
            $DataCategory->weight           = $DataKpi[$Config->category_id]['weight'];
            $result                         = $this->$funcName($DataCategory);
            if($result['error']){
                return Response::json($result);
            }

        }else{
            return Response::json(['ERROR' => 'FUNCTION_NOT_EXISTS', 'DATA' => $funcName]);
        }

        DB::connection('reportdb')->beginTransaction();
        try{
            $DataCategory->time_create      = time();
            $DataCategory->save();

            // Insert kpi team
            if(isset($ConfigKpiTeam->user_id)){
                $TeamRevenue    = \reportmodel\KPIModel::firstOrCreate([
                    'date'              => $Date,
                    'user_id'           => 0,
                    'category_id'       => $ConfigKpiTeam->category_id
                ]);

                $TeamRevenue->revenue_firstmonth    += $DataCategory->revenue_firstmonth;
                $TeamRevenue->revenue_nextmonth     += $DataCategory->revenue_nextmonth;
                $TeamRevenue->succeed               += $DataCategory->succeed;
                $TeamRevenue->weight                = $DataKpiTeam[$TeamRevenue->category_id]['weight'];
                $TeamRevenue->succeed_target        = $DataKpiTeam[$TeamRevenue->category_id]['target'];
                $TeamRevenue->percent_target        = $DataKpiTeam[$TeamRevenue->category_id]['percent'];
                $TeamRevenue->percent               = round(($TeamRevenue->succeed/$TeamRevenue->succeed_target),3);
                $TeamRevenue->time_create           = time();
                $TeamRevenue->save();
            }

            $Config->date   = $Date;
            $Config->save();
            DB::connection('reportdb')->commit();
        }catch (\Exception $e){
            return Response::json(['error' => true, 'message' => $e->getMessage()]);
        }

        return Response::json(['error' => false, 'message' => 'Thành công']);
    }

    //Chỉ tiêu khách hàng nhập kho boxme
    public function getKpiSaleFulfill(){
        $Date   = date('Y-m-d', strtotime('-1 day'));
        if(date('d', strtotime($Date)) >= 25){
            $TimeStart  = strtotime(date('Y-m-25 00:00:00', strtotime($Date)));
        }else{
            $TimeStart  = strtotime(date('Y-m-25 00:00:00', strtotime(' -1 month', strtotime($Date))));
        }

        $ListKPI = \reportmodel\KPICategoryModel::where('date','<>', $Date)->where('code', 'fulfill')->get()->toArray();
        if(empty($ListKPI)){
            return $this->getKpiSaleRevenue();
            //return Response::json(['error' => false, 'code' => 'EMPTY', 'error_message' => 'Không có dữ liệu']);
        }

        $KPI            = [];
        $DataInsert     = [];
        foreach($ListKPI as $val){
            $KPI[]                      = $val['id'];
            $DataKpi[$val['id']]        = $val;
            $DataInsert[$val['id']]     = [
                'date'              => $Date,
                'user_id'           => 0,
                'category_id'       => $val['id'],
                'percent'           => 0,
                'succeed'           => 0,
                'weight'            => $val['weight'],
                'succeed_target'    => $val['target'],
                'percent_target'    => $val['percent'],
                'time_create'       => time()
            ];
        }

        $ListConfig = \reportmodel\KPIConfigModel::whereIn('category_id', $KPI)->where('date','<>', $Date)
                                             ->where('active',1)->orderBy('category_id','ASC')->get()->toArray();
        if(empty($ListConfig)){
            return Response::json(['error' => false, 'code' => 'EMPTY CONFIG', 'error_message' => 'Không có dữ liệu']);
        }

        $Config             = [];
        $CategoryRefer      = [];
        $DataSellerInsert   = [];
        foreach ($ListConfig as $val){
            $Config[]                       = $val['user_id'];
            $CategoryRefer[$val['user_id']] = $val['category_id'];
            $DataSellerInsert[$val['user_id']]                   = [
                'date'              => $Date,
                'user_id'           => $val['user_id'],
                'category_id'       => $val['category_id'],
                'percent'           => 0,
                'succeed'           => 0,
                'weight'            => $DataKpi[$val['category_id']]['weight'],
                'succeed_target'    => $DataKpi[$val['category_id']]['target'],
                'percent_target'    => $DataKpi[$val['category_id']]['percent'],
                'time_create'       => time()
            ];
        }

        //Lấy số lượng khách hàng nhập kho boxme
        $ListMerchant   = \omsmodel\SellerModel::where('first_shipment_time', '>=', $TimeStart)
                                                ->where('first_shipment_time', '<', strtotime($Date) + 86400)
                                                ->whereIn('seller_id', $Config)
                                                ->groupBy('seller_id')
                                                ->get(['seller_id',DB::raw('count(*) as total')])->toArray();
        $Seller = [];
        foreach($ListMerchant as $val){
            $DataSellerInsert[$val['seller_id']]['succeed']             = $val['total'];
            $DataSellerInsert[$val['seller_id']]['percent']             = round(($val['total']/$DataSellerInsert[$val['seller_id']]['succeed_target']),3);

            $DataInsert[$CategoryRefer[$val['seller_id']]]['succeed']   += $val['total'];
            $DataInsert[$CategoryRefer[$val['seller_id']]]['percent']   = round(($DataInsert[$CategoryRefer[$val['seller_id']]]['succeed']/$DataInsert[$CategoryRefer[$val['seller_id']]]['succeed_target']),3);
        }

        $DataInsert = array_merge($DataInsert, $DataSellerInsert);
        DB::connection('reportdb')->beginTransaction();
        try{
            \reportmodel\KPICategoryModel::whereIn('id', $KPI)->update(['date' => $Date]);
            \reportmodel\KPIModel::insert($DataInsert);
            DB::connection('reportdb')->commit();
        }catch (\Exception $e){
            return Response::json(['error' => true, 'message' => $e->getMessage()]);
        }

        return Response::json(['error' => false, 'message' => 'Thành công']);
    }


    private function __check_time_cs($TimeStart, $TimeEnd, $Range){
        // $Ranger  : giờ
        if(date('N', $TimeStart)){
            $TimeStart = strtotime(date('Y-m-d 8:00:00', strtotime('+1 days', $TimeStart)));
        }

        if(date('H') < 8){
            $TimeStart = strtotime(date('Y-m-d 8:00:00', $TimeStart));
        }

        if(date('H') > (18 - $Range)){
            $Deduct     = strtotime(date('Y-m-d 18:00:00', $TimeStart)) - $TimeStart;

            $TimeStart  = strtotime(date('Y-m-d 8:00:00', strtotime('+1 days', $TimeStart)));
            $TimeStart -=  $Deduct;
        }

        if(($TimeStart + $Range*3600) >= $TimeEnd){
            return true;
        }else{
            return false;
        }
    }
    public function getKpiCsTicket(){
        $Date       = date('Y-m-d', strtotime('-1 day'));
        $TimeEnd    = strtotime(date('Y-m-17 00:00:00'));

        if(date('d', strtotime($Date)) >= 25){
            $TimeStart  = strtotime(date('Y-m-25 00:00:00', strtotime($Date)));
        }else{
            $TimeStart  = strtotime(date('Y-m-25 00:00:00', strtotime(' -1 month', strtotime($Date))));
        }

        $ListKPI = \reportmodel\KPICategoryModel::where('date','<>', $Date)->where('code', 'tndh_ticket')->get()->toArray();
        if(empty($ListKPI)){
            return ['ERROR' => 'PROCCEED'];
        }

        foreach($ListKPI as $val){
            $KPI[]                      = $val['id'];
            $DataKpi[$val['id']]        = $val;
            $DataInsert[$val['id']]     = [
                'date'              => $Date,
                'user_id'           => 0,
                'category_id'       => $val['id'],
                'percent'           => 0,
                'succeed'           => 0,
                'weight'            => $val['weight'],
                'succeed_target'    => $val['target'],
                'percent_target'    => $val['percent'],
                'time_create'       => time()
            ];
        }

        $ListConfig = \reportmodel\KPIConfigModel::whereIn('category_id', $KPI)->where('date','<>', $Date)
            ->where('active',1)->orderBy('category_id','ASC')->get()->toArray();
        if(empty($ListConfig)){
            return Response::json(['error' => false, 'code' => 'EMPTY CONFIG', 'error_message' => 'Không có dữ liệu']);
        }

        $Config             = [];
        $CategoryRefer      = [];
        $DataSellerInsert   = [];
        foreach ($ListConfig as $val){
            $Config[]                       = $val['user_id'];
            $CategoryRefer[$val['user_id']] = $val['category_id'];
            $DataUserInsert[$val['user_id']]                   = [
                'date'              => $Date,
                'user_id'           => $val['user_id'],
                'category_id'       => $val['category_id'],
                'percent'           => 0,
                'succeed'           => 0,
                'weight'            => $DataKpi[$val['category_id']]['weight'],
                'succeed_target'    => $DataKpi[$val['category_id']]['target'],
                'percent_target'    => $DataKpi[$val['category_id']]['percent'],
                'time_create'       => time()
            ];
        }

        //Get List Ticket
        $ListTicket = \ticketmodel\RequestModel::where('time_create','>=',$TimeStart)
                                               ->where('time_create', '<', $TimeEnd)
                                               ->where('time_receive','>',0)
                                               ->whereRaw('time_receive > time_create')
                                               ->orderBy('id', 'ASC')
                                               ->get(['id','time_create','time_receive'])->toArray();
        if(!empty($ListTicket)){
            $Total              = 0;
            $ListSuccessful     = [];
            $ListUnsuccessful   = [];
            $ListTicketId       = [];
            $MinId              = 0;

            $ListUnAssignSuccessful     = [];
            $ListUnAssignUnSuccessful   = [];

            foreach($ListTicket as $val){
                $Total += 1;
                $ListTicketId[]   = $val['id'];
                if($this->__check_time_cs($val['time_create'], $val['time_receive'], 1)){
                    $ListSuccessful[]       = $val['id'];
                }else{
                    $ListUnsuccessful[]     = $val['id'];
                }

                if(empty($MinId)) $MinId = $val['id'];
            }

            foreach($DataInsert as $key => $val){
                //$DataInsert[$key]['']
            }

            Input::merge(['group' => 6, 'active' => 1, 'country_id' => 237]);
            $ListCsEmployee = $this->getEmployeeGroup(false);

            if(!empty($ListCsEmployee)){
                // Tính số lượng ticket đã được cs assign
                $ListAssign   = new \ticketmodel\AssignModel;
                $ListAssign   = $ListAssign::whereRaw("ticket_id in (". implode(",", $ListTicketId) .")")->lists('ticket_id');
                if(!empty($ListAssign)){
                    if(!empty($ListSuccessful)){
                        $AssignModel    = new \ticketmodel\AssignModel;
                        $ListSS         = $AssignModel::whereRaw("ticket_id in (". implode(",", $ListSuccessful) .")")
                            ->whereIn('user_id', $ListCsEmployee)
                            ->groupBy('user_id')
                            ->get(['user_id',DB::raw('count(*) as total')])->toArray();
                        if(!empty($ListSS)){
                            foreach($ListSS as $val){
                                foreach($DataInsert as $key => $val){
                                    //$DataInsert[$key]['']
                                }
                            }
                        }

                        $ListUnAssignSuccessful     = array_diff($ListSuccessful, $ListAssign);
                    }

                    if(!empty($ListUnsuccessful)){
                        $AssignModel    = new \ticketmodel\AssignModel;
                        $ListUnSS       = $AssignModel::whereRaw("ticket_id in (". implode(",", $ListUnsuccessful) .")")
                            ->whereIn('user_id', $ListCsEmployee)
                            ->groupBy('user_id')
                            ->get(['user_id',DB::raw('count(*) as total')])->toArray();

                        $ListUnAssignUnSuccessful   = array_diff($ListUnsuccessful, $ListAssign);
                    }
                }else{
                    $ListUnAssignSuccessful     = $ListSuccessful;
                    $ListUnAssignUnSuccessful   = $ListUnsuccessful;
                }

                if(!empty($ListUnAssignSuccessful)){
                    $UnAssignSuccessful    = new \ticketmodel\FeedbackModel;
                    $UnAssignSuccessful    = $UnAssignSuccessful::whereRaw("ticket_id in (". implode(",", $ListUnAssignSuccessful) .")")
                        ->whereIn('user_id', $ListCsEmployee)
                        ->groupBy('user_id')
                        ->get(['user_id',DB::raw('count(*) as total')])->toArray();
                }

                if(!empty($ListUnAssignUnSuccessful)){
                    $UnAssignUnSuccessful    = new \ticketmodel\FeedbackModel;
                    $UnAssignUnSuccessful    = $UnAssignUnSuccessful::whereRaw("ticket_id in (". implode(",", $ListUnAssignUnSuccessful) .")")
                        ->whereIn('user_id', $ListCsEmployee)
                        ->groupBy('user_id')
                        ->get(['user_id',DB::raw('count(*) as total')])->toArray();
                }


            }
            
        }



    }

//    Processing Category 1
//    KPI Tồn lấy - Xử lý thành công
//    Xử lý cách 3 ngày -> chốt hết ngày 24 hàng tháng
    private function __category_1($DataCategory){
        $Date       = date('d');
        $TimePromisePickupEnd   = strtotime(date('Y-m-d 23:59:59')) - 86400*3;

        if($Date > 24){
            $TimePromisePickupStart = strtotime(date('Y-m-22 00:00:00'));
        }else{
            $TimePromisePickupStart = strtotime(date('Y-m-22 00:00:00', strtotime("-1 month")));
        }


        //Danh sách đơn khách hàng báo hủy
        $ListCancel = \omsmodel\PipeJourneyModel::where('type',5)->where('group_process',108)
                                                ->whereIn('pipe_status',[10,15])
                                                ->where('time_create', '>=', $TimePromisePickupStart - 86400*4)
                                                ->lists('tracking_code');

        $LMongo     = new \LMongo;
        // Lấy danh sách tồn lấy
        $Data       = $LMongo::collection('log_journey_pickup')->where('active',1)->where('message','SUCCESS')
                                    ->whereGte('promise_pickup_time', $TimePromisePickupStart)
                                    ->whereLt('promise_pickup_time', $TimePromisePickupEnd);

        if(!empty($ListCancel)){
            $Data   = $Data->whereNin('order_id', $ListCancel);
        }
        $Data       = $Data->where(function($query) use($TimePromisePickupEnd){
                            $query->where('time_pickup', 0)
                                ->orWhere(function($q) use($TimePromisePickupEnd){
                                    $q->whereGt('time_pickup', ($TimePromisePickupEnd - 86400))->whereExists('time_slow')->whereGt('time_slow',0);
                                });
                        })->get([
                            'tracking_code','email', 'status', 'from_user_id','time_pickup', 'time_update','time_slow',
                                    'promise_pickup_time', 'from_address_id','from_country_id','from_city_id','from_district_id',
                                    'from_ward_id', 'from_address', 'active', 'message'
                        ])->toArray();

        $ListStock                  = [0];
        $ListStockPicked            = [];
        $ListStockPickupLastDay     = [];
        $DataInsert                 = [];

        if(empty($Data)){
            return ['error' => false, 'total' => 0, 'succeed' => 0, 'data' => []];
        }

        foreach($Data as $val){
            $val['from_address_id'] = (int)$val['from_address_id'];
            $val['from_user_id']    = (int)$val['from_user_id'];

            // Trạng thái khác hủy , hoặc đã hủy và thời gian hủy sau thời gian xử lý
            if(!in_array($val['status'], [22,23,24,25,27,28,29,121]) || ($val['time_update'] > $TimePromisePickupEnd)){
                if($val['from_address_id'] == 0){
                    $val['from_address_id'] = (int) ($val['from_user_id'].$val['from_country_id'].$val['from_city_id'].$val['from_district_id'].$val['from_ward_id']);
                }

                if(!in_array($val['from_address_id'], $ListStock)){
                    $ListStock[] = $val['from_address_id'];
                    $DataInsert[$val['from_address_id']]    = [
                        'category_type'         => 2,
                        'category_id'           => $DataCategory->id,
                        'email'                 => $val['email'],
                        'from_user_id'          => $val['from_user_id'],
                        'from_address_id'       => $val['from_address_id'],
                        'from_country_id'       => $val['from_country_id'],
                        'from_city_id'          => $val['from_city_id'],
                        'from_district_id'      => $val['from_district_id'],
                        'from_ward_id'          => $val['from_ward_id'],
                        'from_address'          => $val['from_address'],
                        'total'                 => 1,
                        'total_picked'          => 0,
                        'tracking_code'         => (string) $val['tracking_code'],
                        'tracking_code_picked'  => ''
                    ];
                }else{
                    $DataInsert[$val['from_address_id']]['total']    += 1;
                    $DataInsert[$val['from_address_id']]['tracking_code']   .= ','.$val['tracking_code'];
                }

                // đã lấy 1 đơn cũng tính là kho đã lấy
                // Lấy sau ngày xử lý $TimePromisePickupEnd - 86400
                if($val['time_pickup'] > 0){
                    if(!in_array($val['from_address_id'], $ListStockPicked)){
                        $ListStockPicked[]          = $val['from_address_id'];
                    }
                    $DataInsert[$val['from_address_id']]['total_picked']    += 1;
                    $DataInsert[$val['from_address_id']]['tracking_code_picked']   .= $val['tracking_code'].',';
                    $DataInsert[$val['from_address_id']]['category_type']    = 1;
                }
            }
        }

        //Tổng kho hàng được lấy hàng ngày hôm qua trong số kho hàng tồn lấy
        $LMongo     = new \LMongo;
        $Data     = $LMongo::collection('log_journey_pickup')->where('active',1)->where('message','SUCCESS')
            ->whereGte('promise_pickup_time', $TimePromisePickupStart)
            ->whereLt('promise_pickup_time', $TimePromisePickupEnd)
            ->whereGte('time_pickup', ($TimePromisePickupEnd - 172800))
            ->whereLte('time_pickup', ($TimePromisePickupEnd - 86400))
            ->whereIn('from_address_id', $ListStock)
            ->get(['promise_pickup_time','time_pickup','from_address_id','from_user_id','from_country_id','from_city_id','from_district_id','from_ward_id','active','message'])->toArray();
        foreach($Data as $val){
            if($val['from_address_id'] == 0){
                $val['from_address_id'] = (int) ($val['from_user_id'].$val['from_country_id'].$val['from_city_id'].$val['from_district_id'].$val['from_ward_id']);
            }

            if(!in_array($val['from_address_id'], $ListStockPickupLastDay)){
                $ListStockPickupLastDay[] = $val['from_address_id'];
            }
        }

        if(!empty($ListStockPickupLastDay)){
            $ListStock          = array_diff($ListStock, $ListStockPickupLastDay);
            $ListStockPicked    = array_diff($ListStockPicked, $ListStockPickupLastDay);

            foreach($ListStockPickupLastDay as $val){
                unset($DataInsert[$val]);
            }
        }

        //Lấy thành công = tổng kh - kh chưa lấy
        return ['error' => false, 'total' => (count($ListStock) - 1), 'succeed' => count($ListStockPicked),'data' => $DataInsert];
    }

    //    Processing Category 2
//    KPI Tồn lấy - Hủy lỗi do người bán
//    Xử lý cách 3 ngày -> chốt hết ngày 24 hàng tháng
    private function __category_2($DataCategory){
        // Dựa vào 3 trạng thái xử lý
        // Hủy đơn do lấy chậm
        // Hủy đơn/ KH báo hủy
        // Hủy đơn do quá hạn lấy hàng
        $Date       = date('d');
        $TimePromisePickupEnd   = strtotime(date('Y-m-d 23:59:59')) - 86400*2;

        if($Date > 24){
            $TimePromisePickupStart = strtotime(date('Y-m-22 00:00:00'));
        }else{
            $TimePromisePickupStart = strtotime(date('Y-m-22 00:00:00', strtotime("-1 month")));
        }

        $LMongo     = new \LMongo;
        // Lấy danh sách tồn lấy
        $Data       = $LMongo::collection('log_journey_pickup')->where('active',1)->where('message','SUCCESS')
            ->whereGte('promise_pickup_time', $TimePromisePickupStart)
            ->whereLt('promise_pickup_time', $TimePromisePickupEnd)
            ->where('time_pickup', 0)
            ->whereIn('status', [22,28])
            ->get(['email','order_id', 'tracking_code', 'service_id', 'status', 'from_user_id', 'from_address_id', 'time_accept',
                'time_pickup', 'time_approve', 'from_location', 'promise_pickup_time','from_country_id', 'from_city_id','from_district_id','from_ward_id', 'from_address'])
            ->toArray();

        if(empty($Data)){
            return ['error' => false, 'total' => 0, 'succeed' => 0, 'data' => []];
        }

        $ListOrder = [];
        $OrderId   = [];

        foreach($Data as $val){
            $OrderId[]  = $val['order_id'];
            $ListOrder[$val['order_id']]  = $val;
        }

        $ListProcessing = \omsmodel\PipeJourneyModel::whereRaw("tracking_code in (". implode(",", $OrderId) .")")
                                                    ->where('type',5)->where('group_process', 108)->whereIn('pipe_status', [10,13,15,16])
                                                    ->get(['tracking_code', 'note', 'pipe_status', 'time_create'])->toArray();

        if(empty($ListProcessing)){
            return ['error' => false, 'total' => 0, 'succeed' => 0, 'data' => []];
        }

        $Total          = 0;
        $TotalSucceed   = 0;
        $Data           = [];
        foreach($ListProcessing as $val){
            $Total += 1;
            if(in_array($val['pipe_status'], [10,15])){
                $TotalSucceed   += 1;
            }

            $Data[] = [
                'category_id'          => $DataCategory->id,
                'category_type'         => (in_array($val['pipe_status'], [10,15])) ? 1 : 2, // 1 do người bán - 2 ko
                'order_id'              => $val['tracking_code'],
                'pipe_status'           => $val['pipe_status'],
                'note_processing'       => $val['note'],
                'time_processing'       => $val['time_create'],
                'tracking_code'         => isset($ListOrder[$val['tracking_code']]) ? $ListOrder[$val['tracking_code']]['tracking_code'] : '',
                'email'                 => isset($ListOrder[$val['tracking_code']]) ? $ListOrder[$val['tracking_code']]['email'] : '',
                'service_id'            => isset($ListOrder[$val['tracking_code']]) ? $ListOrder[$val['tracking_code']]['service_id'] : 0,
                'status'                => isset($ListOrder[$val['tracking_code']]) ? $ListOrder[$val['tracking_code']]['status'] : 0,
                'from_user_id'          => isset($ListOrder[$val['tracking_code']]) ? $ListOrder[$val['tracking_code']]['from_user_id'] : 0,
                'time_accept'           => isset($ListOrder[$val['tracking_code']]) ? $ListOrder[$val['tracking_code']]['time_accept'] : 0,
                'time_pickup'           => isset($ListOrder[$val['tracking_code']]) ? $ListOrder[$val['tracking_code']]['time_pickup'] : 0,
                'time_approve'          => isset($ListOrder[$val['tracking_code']]) ? $ListOrder[$val['tracking_code']]['time_approve'] : 0,
                'promise_pickup_time'   => isset($ListOrder[$val['tracking_code']]) ? $ListOrder[$val['tracking_code']]['promise_pickup_time'] : 0,
                'from_country_id'       => isset($ListOrder[$val['tracking_code']]) ? $ListOrder[$val['tracking_code']]['from_country_id'] : 0,
                'from_city_id'          => isset($ListOrder[$val['tracking_code']]) ? $ListOrder[$val['tracking_code']]['from_city_id'] : 0,
                'from_district_id'      => isset($ListOrder[$val['tracking_code']]) ? $ListOrder[$val['tracking_code']]['from_district_id'] : 0,
                'from_ward_id'          => isset($ListOrder[$val['tracking_code']]) ? $ListOrder[$val['tracking_code']]['from_ward_id'] : 0,
                'from_address'          => isset($ListOrder[$val['tracking_code']]) ? $ListOrder[$val['tracking_code']]['from_address'] : '',
            ];
        }

        return ['error' => false, 'total' => $Total, 'succeed' => $TotalSucceed, 'data' => $Data];
    }


    //    Processing Category 3
    //    KPI Tồn lấy - Đúng hạn
    //    Xử lý cách 3 ngày -> chốt hết ngày 24 hàng tháng
    private function __check_processing_pickup_slow($TimePromise ,$TimePickup){
        $Bonus          = $this->time_bonus;
        $Date           = date('Y-m-d 23:59:59', $TimePromise); // Thời gian hạn lấy hàng
        $TimeSucceed    = strtotime($Date) + 86400*2; // Thời gian hạn xử lý tồn - Cộng thêm 2 ngày xử lý tồn (1 ngày xử lý , 1 ngày lấy thành công)

        $DatePromise = date('N', $TimePromise);
        $DateSucceed = date('N', $TimeSucceed);

        if($DateSucceed == 7 || ($DatePromise > $DateSucceed)){
            $Bonus += 86400;
        }
        
        if($TimePickup > ($TimeSucceed + $Bonus)){
            return false;
        }else{
            return true;
        }
    }

    private function __category_3($DataCategory){
        $Date       = date('d');
        $TimePromisePickupEnd   = strtotime(date('Y-m-d 23:59:59')) - 86400*2;

        if($Date > 24){
            $TimePromisePickupStart = strtotime(date('Y-m-22 00:00:00'));
        }else{
            $TimePromisePickupStart = strtotime(date('Y-m-22 00:00:00', strtotime("-1 month")));
        }


        //Danh sách đơn khách hàng báo hủy
        $ListCancel = \omsmodel\PipeJourneyModel::where('type',5)->where('group_process',108)
            ->whereIn('pipe_status',[10,15])
            ->where('time_create', '>=', $TimePromisePickupStart - 86400*4)
            ->lists('tracking_code');

        $LMongo = new \LMongo;

        // Lấy danh sách lấy chậm đã lấy
        $Data   = $LMongo::collection('log_journey_pickup')->where('active',1)->where('message','SUCCESS')
            ->whereGte('promise_pickup_time', $TimePromisePickupStart)
            ->whereLt('promise_pickup_time', $TimePromisePickupEnd);
        if(!empty($ListCancel)){
            $Data   = $Data->whereNin('order_id', $ListCancel);
        }
        $Data   = $Data->whereExists('time_slow')->whereGt('time_slow',0)->whereGt('time_pickup',0)
                       ->get(['email','from_user_id', 'from_address_id', 'promise_pickup_time','time_pickup','time_slow','from_country_id','from_city_id','from_district_id','from_ward_id', 'from_address'])
                       ->toArray();
        if(empty($Data)){
            return ['error' => false, 'total' => 0, 'succeed' => 0, 'data' => []];
        }

        $ListStock                  = [0];
        $ListStockSlow              = []; // Danh sách kho hàng xử lý quá hạn
        $ListStockPickupLastDay     = [];
        $DataInsert                 = [];

        foreach($Data as $val){
            $val['from_address_id'] = (int)$val['from_address_id'];
            $val['from_user_id']    = (int)$val['from_user_id'];
            $Insert                 = [];

            if($val['from_address_id'] == 0){
                $val['from_address_id'] = $val['from_user_id'].'-'.$val['from_country_id'].'-'.$val['from_city_id'].'-'.$val['from_district_id'].'-'.$val['from_ward_id'];
            }

            //Tổng số kho hàng lấy chậm đã lấy
            if(!in_array($val['from_address_id'], $ListStock)){
                $ListStock[] = $val['from_address_id'];
                $Insert                 = [
                    'category_type'         => 1,
                    'category_id'           => $DataCategory->id,
                    'email'                 => $val['email'],
                    'from_user_id'          => $val['from_user_id'],
                    'from_address_id'       => $val['from_address_id'],
                    'from_country_id'       => $val['from_country_id'],
                    'from_city_id'          => $val['from_city_id'],
                    'from_district_id'      => $val['from_district_id'],
                    'from_ward_id'          => $val['from_ward_id'],
                    'from_address'          => $val['from_address'],
                ];
            }

            //Check xử lý tồn đúng hạn
            // Nếu hôm nay là hạn, ngày mai xử lý -> hết ngày kia sẽ là hết hạn lấy hàng, dính chủ nhật + thêm 1 ngày
            if(!$this->__check_processing_pickup_slow($val['promise_pickup_time'], $val['time_pickup'])){
                if(!in_array($val['from_address_id'], $ListStockSlow)){
                    $ListStockSlow[]          = $val['from_address_id'];
                    if(!empty($Insert)){
                        $Insert['category_type']    = 2;
                    }
                }
            }

            if(!empty($Insert)){
                $DataInsert[]   = $Insert;
            }
        }

        //Tổng kho hàng được lấy hàng ngày hôm qua trong số kho hàng tồn lấy
        $LMongo     = new \LMongo;
        $Data     = $LMongo::collection('log_journey_pickup')->where('active',1)->where('message','SUCCESS')
            ->whereGte('promise_pickup_time', $TimePromisePickupStart)
            ->whereLt('promise_pickup_time', $TimePromisePickupEnd)
            ->whereGte('time_pickup', ($TimePromisePickupEnd - 172800))
            ->whereLte('time_pickup', ($TimePromisePickupEnd - 86400))
            ->whereIn('from_address_id', $ListStock)
            ->get(['from_address_id','from_user_id','from_country_id','from_city_id','from_district_id','from_ward_id'])->toArray();
        foreach($Data as $val){
            if($val['from_address_id'] == 0){
                $val['from_address_id'] = $val['from_user_id'].'-'.$val['from_country_id'].'-'.$val['from_city_id'].'-'.$val['from_district_id'].'-'.$val['from_ward_id'];
            }

            if(!in_array($val['from_address_id'], $ListStockPickupLastDay)){
                $ListStockPickupLastDay[] = $val['from_address_id'];
            }
        }

        if(!empty($ListStockPickupLastDay)){
            $ListStock          = array_diff($ListStock, $ListStockPickupLastDay);
            $ListStockSlow    = array_diff($ListStockSlow, $ListStockPickupLastDay);
            foreach($DataInsert as $key => $val){
                if(in_array($val['from_address_id'], $ListStockPickupLastDay)){
                    unset($DataInsert[$key]);
                }
            }

        }

        $TotalStock = count($ListStock);
        $TotalSlow  = count($ListStockSlow);

        //Lấy thành công = tổng kh - kh chưa lấy
        return ['error' => false, 'total' => $TotalStock, 'succeed' => ($TotalStock - $TotalSlow),'data' => $DataInsert];
    }




    /**
     *
     * KPI SALE
     *
     */
    //Opportunity
    private function __category_12($DataCategory){
        if(date('d', strtotime($DataCategory['date'])) >= 25){
            $TimeStart  = strtotime(date('Y-m-25 00:00:00', strtotime($DataCategory['date'])));
        }else{
            $TimeStart  = strtotime(date('Y-m-25 00:00:00', strtotime(' -1 month', strtotime($DataCategory['date']))));
        }

        $TimeEnd    = strtotime($DataCategory['date'] . '23:59:59');

        $OwnerId    = \reportmodel\CrmEmployeeModel::where('user_id', $DataCategory->user_id)->first();
        if(!isset($OwnerId->crm_id)){
            return ['error' => true, 'message' => 'OwnerId Empty'];
        }

        Input::merge([
            'page_size'         => 0,
            'time_start'        => $TimeStart,
            'time_end'          => $TimeEnd,
            'owner_id'          => $OwnerId->crm_id,
            'stage'             => 'ALL'
        ]);
        $Opportunity    = $this->getOpportunity(false);
        if($Opportunity['error']){
            return $Opportunity;
        }

        $DataCategory->succeed          = $Opportunity['data']['totalCount'];
        $DataCategory->percent          = round(($DataCategory->succeed/$DataCategory->succeed_target),3);
        return ['error' => false, 'data' => $DataCategory];
    }

    //Won
    private function __category_13($DataCategory){
        if(date('d', strtotime($DataCategory['date'])) >= 25){
            $TimeStart  = strtotime(date('Y-m-25 00:00:00', strtotime($DataCategory['date'])));
        }else{
            $TimeStart  = strtotime(date('Y-m-25 00:00:00', strtotime(' -1 month', strtotime($DataCategory['date']))));
        }

        $TimeEnd    = strtotime($DataCategory['date'] . '23:59:59');

        $OwnerId    = \reportmodel\CrmEmployeeModel::where('user_id', $DataCategory->user_id)->first();
        if(!isset($OwnerId->crm_id)){
            return ['error' => true, 'message' => 'OwnerId Empty'];
        }

        Input::merge([
            'page_size'         => 0,
            'time_start'        => $TimeStart,
            'time_end'          => $TimeEnd,
            'owner_id'          => $OwnerId->crm_id,
            'stage'             => 'Closed Won'
        ]);
        $Opportunity    = $this->getOpportunity(false);
        if($Opportunity['error']){
            return $Opportunity;
        }

        $DataCategory->succeed          = $Opportunity['data']['totalCount'];
        $DataCategory->percent          = round(($DataCategory->succeed/$DataCategory->succeed_target),3);
        return ['error' => false, 'data' => $DataCategory];
    }

    //Revenue
    private function __category_revenue($DataCategory){
        if(date('d', strtotime($DataCategory->date)) < 25){
            $TimeStart      = strtotime(date('Y-m-25 00:00:00', strtotime("-1 month", strtotime($DataCategory->date))));
            $TimePreMonth   = strtotime(date('Y-m-25 00:00:00', strtotime("-2 month", strtotime($DataCategory->date))));
        }else{
            $TimeStart      = strtotime(date('Y-m-25 00:00:00', strtotime($DataCategory->date)));
            $TimePreMonth   = strtotime(date('Y-m-25 00:00:00', strtotime("-1 month", strtotime($DataCategory->date))));
        }

        $Model          = new \omsmodel\SellerModel;
        $ModelLastMonth = new \omsmodel\SellerModel;
        $Data           = [
            'total_firstmonth'  => 0,
            'total_nextmonth'   => 0
        ];
        $SumTotal       = 0;

        // Doanh thu đầu tháng
        $DataSum        = $Model::where(function($query) use($TimeStart){
                                $query->where(function($q) use($TimeStart){
                                    $q->where('first_time_incomings',0)
                                        ->where('first_time_pickup', '>=', $TimeStart);
                                })->orWhere(function($q) use($TimeStart){
                                    $q->where('first_time_incomings', '>=', $TimeStart);
                                });
                            })->where('seller_id',(int)$DataCategory->user_id)
                            ->where('active',1)
                            ->first([DB::raw('sum(total_firstmonth) as total_firstmonth')]);

        if(isset($DataSum->total_firstmonth)){
            $SumTotal                       = $DataSum->total_firstmonth;
            $Data['total_firstmonth']       = $DataSum->total_firstmonth;
        }

        // Doanh thu lũy kế
        $DataSum     = $ModelLastMonth::where(function($query) use($TimeStart, $TimePreMonth){
                                        $query->where(function($q) use($TimeStart, $TimePreMonth){
                                            $q->where('first_time_incomings',0)
                                                ->where('first_time_pickup', '>=', $TimePreMonth)
                                                ->where('first_time_pickup', '<', $TimeStart);
                                        })->orWhere(function($q) use($TimeStart, $TimePreMonth){
                                            $q->where('first_time_incomings', '>=', $TimePreMonth)
                                                ->where('first_time_incomings', '<', $TimeStart);
                                        });
                                    })
                                    ->where('active',1)
                                    ->where('seller_id',(int)$DataCategory->user_id)
                                    ->first([DB::raw('sum(total_nextmonth) as total_nextmonth')]);

        if(isset($DataSum->total_nextmonth)){
            $SumTotal                      += $DataSum->total_nextmonth;
            $Data['total_nextmonth']        = $DataSum->total_nextmonth;
        }

        // Doanh thu lũy kế khác hàng ngừng sử dụng
        $LogSellerModel = new \omsmodel\LogSellerModel;
        $DataPreStop     = $LogSellerModel::where(function($query) use($TimeStart, $TimePreMonth){
                                            $query->where(function($q) use($TimeStart, $TimePreMonth){
                                                $q->where('first_time_incomings',0)
                                                    ->where('first_time_pickup', '>=', $TimePreMonth)
                                                    ->where('first_time_pickup', '<', $TimeStart);
                                            })->orWhere(function($q) use($TimeStart, $TimePreMonth){
                                                $q->where('first_time_incomings', '>=', $TimePreMonth)
                                                    ->where('first_time_incomings', '<', $TimeStart);
                                            });
                                        })
                                        ->where('seller_id',(int)$DataCategory->user_id)
                                        ->where('active',1)
                                        ->first([DB::raw('sum(total_nextmonth) as total_nextmonth')]);
        if(isset($DataPreStop->total_nextmonth)){
            $SumTotal                      += $DataPreStop->total_nextmonth;
            $Data['total_nextmonth']       += $DataPreStop->total_nextmonth;
        }

        $DataCategory->revenue_firstmonth   = $Data['total_firstmonth'];
        $DataCategory->revenue_nextmonth    = $Data['total_nextmonth'];
        $DataCategory->succeed              = $SumTotal;
        $DataCategory->percent              = round(($DataCategory->succeed/$DataCategory->succeed_target),3);

        return ['error' => false, 'data' => $DataCategory];
    }

}
