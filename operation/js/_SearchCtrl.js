'use strict';

angular.module('app').controller('SearchCtrl', ['$scope', '$http', '$state', '$q', '$stateParams', 'toaster', 'Order', 'Base', 'Config_Status', 'Config_Accounting', '$modal', '$rootScope',
 	function($scope, $http, $state, $q, $stateParams, toaster, Order, Base, Config_Status, Config_Accounting, $modal, $rootScope) {
        $scope.currentPage       = 1;
        $scope.item_page         = 20;
        $scope.maxSize           = 5;
        $scope.item_stt          = 0;
        
        $scope.total_all         = 0;
        $scope.infoUser = $rootScope.userInfo;
        $scope.list_status_verify      =
        {
            'INSERT'      : {
                'name'      : 'Chờ xác nhận',
                'bg'        : 'bg-info'
            },
            'WAITING'      : {
                'name'      : 'Chờ chuyển tiền',
                'bg'        : 'bg-primary'
            },
            'PROCESSING'      : {
                'name'      : 'Đang chuyển tiền',
                'bg'        : 'bg-warning'
            },
            'SUCCESS'      : {
                'name'      : 'Đã chuyển tiền',
                'bg'        : 'bg-success'
            }
        }
        ;
        
        $scope.frm               = {};
        $scope.list_data         = {};
        $scope.user              = [];
        $scope.object            = '';
        $scope.tab_status        = '';
        
        $scope.bank              = Config_Accounting.vimo;
        
        $scope.list_to_address   = {};
        $scope.list_from_address = {};
        $scope.list_district     = {};
        $scope.list_ward         = {};
        $scope.list_color        = Config_Status.order_color;
        $scope.ticket_btn        = Config_Status.ticket_btn;
        $scope.seller            = 0;
        $scope.list_user         = {};
        $scope.time              = {}
        
        $scope.waiting           = true;
        $scope.list_waiting      = true;
        $scope.pipe_priority     = {};
        $scope.pipe_status       = {};
        $scope.list_pipe_status  = {};
        
        $scope.newest_ticket     = [];
        $scope.newest_verify     = [];


        $scope.list_post_office     = {};


        $scope.loading = {
            newest_verify : true,
            newest_ticket : true,
            newest_email  : true,
            manage_process: false,
        }
        $scope.stateReady = 1;// 1: Đang tải dữ liệu | 2: Có dữ liệu | 3: Không có dữ liệu


        if($stateParams.search != undefined && $stateParams.search != ''){
            if($stateParams.search.match(/^O\d+$/g)){
                $state.go('warehouse.search',{keyword:$stateParams.search});
            }

            Base.PipeStatus(null, 1).then(function (result) {
                if(!result.data.error){
                    $scope.list_pipe_status      = result.data.data;
                    angular.forEach(result.data.data, function(value) {
                        if(value.priority > $scope.pipe_limit){
                            $scope.pipe_limit   = +value.priority;
                        }
                        $scope.pipe_status[value.status]    = value.name;
                        $scope.pipe_priority[value.status]  = value.priority;
                    });
                }
            });

            $scope.list_pipe_status_current = function(status){
                var data = [];
                angular.forEach($scope.list_pipe_status, function(value) {
                    if(value.group_status == status){
                        data.push(value);
                    }
                });
                return data;
            }

            $scope.pipe_priority_current    = function(journey_pipe, group_status){
                var priority = 0;
                angular.forEach(journey_pipe, function(value) {
                    if(value.group_process == group_status){
                        if($scope.pipe_priority[value.pipe_status] > priority){
                            priority = $scope.pipe_priority[value.pipe_status];
                        }
                    }
                });
                return priority;
            }

            $scope.refresh = function(cmd){
                if(cmd != 'export'){
                    $scope.list_data            = {};
                    $scope.list_to_address      = {};
                    $scope.list_district        = {};
                    $scope.list_ward            = {};
                    $scope.list_from_address    = {};
                    $scope.list_waiting         = true;
                }
            }

            $scope.round = function (number){
                return Math.round(number);
            }
            $scope.SearchUser   = function(search){
                Base.Search(search).then(function (result) {

                    if(!result.data.error){
                        if(Object.keys(result.data.user).length > 0){

                            $scope.stateReady = 2;
                            $scope.user       = result.data.user;
                            $scope.object     = result.data.object;
                            if($scope.object == 'order'){
                                $scope.getNewestTicket();
                            }else if($scope.object == 'seller'){
                                if($scope.user.id > 0){
                                    $scope.getMerchant(1);
                                }
                            }else{
                                if($scope.user.id != undefined && $scope.user.id > 0){
                                    $scope.frm.from_user  = $scope.user.id;
                                    $scope.setPage(1);
                                    $scope.setCountGroup();
                                    $scope.getNewestVerify();
                                    $scope.getNewestTicket();
                                    $scope.getNewestEmail();
                                    $scope.getOrderStatistic();

                                }else{
                                    $scope.list_waiting  = false;
                                }
                            }
                            return ;
                        }
                        $scope.stateReady = 3;


                    }
                });
            }

            $scope.setPage = function(page){
                $scope.currentPage = page;
                $scope.refresh('');
                $scope.waiting = true;

                var date = new Date();

                if($scope.frm.tracking_code != undefined && $scope.frm.tracking_code.match(/^SC\d+$/gi)){
                    var time_create = new Date(date.getFullYear(), date.getMonth() - 3, date.getDate());
                }else{
                    var time_create = new Date(date.getFullYear(), date.getMonth() - 1, date.getDate());
                }

                $scope.frm.create_start = Date.parse(time_create) / 1000;
                
                Order.ListOrder($scope.currentPage,$scope.frm, $scope.tab_status, '').then(function (result) {
                    $scope.waiting = false;
                    if(!result.data.error){
                        if(result.data.data.length > 0){
                            $scope.list_data         = result.data.data;
                            $scope.totalItems        = result.data.total;
                            $scope.item_stt          = $scope.item_page * ($scope.currentPage - 1);
                            $scope.list_to_address   = result.data.list_to_address;
                            $scope.list_city         = result.data.list_city;
                            $scope.list_district     = result.data.list_district;
                            $scope.list_ward         = result.data.list_ward;
                            $scope.list_from_address = result.data.list_from_address;
                            $scope.list_post_office  = result.data.list_postoffice;


                            if($scope.object == 'order'){
                                $scope.SearchUser($scope.list_data[0]['from_user_id']);

                            }else if($scope.object == 'receiver'){
                                if($scope.list_data[0] != undefined){
                                    $scope.stateReady = 2;
                                    $scope.user = {
                                        fullname        : $scope.list_data[0]['to_name'],
                                        email           : $scope.list_data[0]['to_email'],
                                        phone           : $scope.list_data[0]['to_phone']
                                    };
                                }
                            }
                        }else {
                            if($scope.object == 'order'){
                                $scope.list_waiting  = false;
                                $scope.stateReady = 3;
                                return;  
                            }
                            
                            /*
                            */
                       }
                    
                    }

                    if($scope.user.length == 0 && $scope.object !== 'order' ){
                        $scope.list_waiting  = false;
                        $scope.stateReady = 3;
                    }
                    
                });
                return;
            }

            $scope.setCountGroup    = function(){
                $scope.total_all    = 0;
                $scope.total_group  = [];
                Order.CountGroup($scope.frm, $scope.tab_status, 'status').then(function (result) {
                    if(!result.data.error){
                        $scope.total_all    = result.data.total;
                        angular.forEach(result.data.data, function(value, key) {
                            if($scope.total_group[$scope.status_group[+key]] == undefined){
                                $scope.total_group[$scope.status_group[+key]]   = 0;
                            }
                            $scope.total_group[$scope.status_group[+key]]   += value;
                        });
                    }
                });
            }

            //Get merchant
            $scope.getMerchant = function(page, cmd){
                $scope.currentPage    = page;
                if(cmd !== 'export'){
                    $scope.list_waiting   = true ;
                    $scope.list_user      = {};
                    $scope.list_data      = {};
                    
                }
                
                

                if($scope.time.time_create_start != undefined && $scope.time.time_create_start != ''){
                    var time_create_start    = +Date.parse($scope.time.time_create_start)/1000;
                }else{
                    var time_create_start    = 0;
                }

                if($scope.time.time_create_end != undefined && $scope.time.time_create_end != ''){
                    var time_create_end    = +Date.parse($scope.time.time_create_end)/1000;
                }else{
                    var time_create_end    = 0;
                }


                Base.Merchant($scope.currentPage,{'seller' : $scope.user.id, keyword: $scope.frm.keyword,  first_time_pickup_start: time_create_start, first_time_pickup_end: time_create_end}, cmd).then(function (result) {
                    if(!result.data.error){
                        $scope.list_data        = result.data.data;
                        $scope.totalItems       = result.data.total;
                        $scope.list_user        = result.data.user;
                        $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                    }
                    $scope.list_waiting  = false;
                });
                return;
            }

            $scope.getNewestVerify = function (){

                $http.get(ApiOms + 'user/newest-verify?seller=' + $scope.user.id + '&item_page=10').success(function (resp){
                    $scope.loading.newest_verify = false
                    if(!resp.error){
                        $scope.newest_verify = resp.data;
                    }
                })
            }
            $scope.genHTMLVerify = function (item){
                var html ="";
                if(item.type != 2){
                    html += '<span  class="label ' + $scope.list_status_verify[item.status].bg + '">' + $scope.list_status_verify[item.status].name+ '</span> <br />';
                    if(item.type_payment == 2){
                        html += '<span class="label bg-info" > Ví Vimo </span><br/>';
                    }

                    if(item.type_payment == 1){
                        html += '<span class="label bg-warning" > Ngân lượng </span><br/>';
                    }
                }else if(item.type == 2) {
                    html += '<span  class="label bg-success">Đã ghi nhận số dư</span><br />';
                }
                return html;
                
            }

            $scope.getNewestTicket = function (){
                $http.get(ApiOms + 'user/newest-ticket?seller=' + $scope.user.id + '&item_page=10').success(function (resp){
                    $scope.loading.newest_ticket = false
                    if(!resp.error){
                        $scope.newest_ticket = resp.data;
                    }
                })
            }

            $scope.getNewestEmail = function (){
                $http.get(ApiPath + 'queue/emailbyuser/' + $scope.user.id).success(function (resp){
                    $scope.loading.newest_email = false
                    if(!resp.error){
                        $scope.newest_email = resp.data;
                    }
                })
            }

            $scope.getOrderStatistic = function (){
                $http.get(ApiOms + 'user/order-statistic?user_id=' + $scope.user.id).success(function (resp){
                    if(!resp.error){
                        $scope.order_statistic = resp.data;
                    }
                })   
            }

            $scope.load_submit_remove_p  = false;
            $scope.removeSalePermission = function (item){
                if(!confirm('Bạn muốn hủy quyền quản lý các khách hàng của sale này ? ')){
                    return false;
                }

                $scope.load_submit_remove_p  = true;
                $http.post(ApiPath + 'seller/remove-sale/'+ item.id).success(function (resp){
                    $scope.load_submit_remove_p  = false;
                    if(!resp.error){
                        $scope.list_data.splice($scope.list_data.indexOf(item),1);
                        toaster.pop('success', 'Thông báo', 'Thành công');
                    }else {
                        toaster.pop('warning', 'Thông báo', resp.error_message);
                    }
                })
            }

            $scope.OpenModalManager = function (){
                var modalInstance = $modal.open({
                    templateUrl: 'tpl/merchant/modal/manager.html',
                    controller: function($scope, $modalInstance, $http, user, Config) {
                        $scope.list_business_model = Config.business_model;
                        $scope.list_city           = {};
                        $scope.list_district       = {};

                        Base.City().then(function (result) {
                            if(!result.data.error){
                                $scope.list_city        = result.data.data;
                            }
                        });

                        $scope.$watch('frm.place_city', function (newVal){
                            if(!newVal){
                                return; 
                            }

                            $scope.loadDictricts(newVal);
                        })
                        $scope.loadDictricts = function (city){
                             Base.district(city, 100).then(function (result){
                                if(!result.data.error){
                                    $scope.list_district        = result.data.data;
                                }
                             })
                        }
                        $scope.manage_process = false;
                        $scope.Manager = function(frm) {

                            if(!frm.place_city || !frm.place_district || !frm.business_model || !frm.avg_lading){
                                return false;
                            }
                            if(user.id) {
                                $scope.manage_process = true;
                                $http.post(ApiPath + 'seller/take-user/'+user.id, frm).success(function (resp){
                                    $scope.manage_process = false;
                                    if(resp.error){
                                        toaster.pop('error','Thông báo', resp.message);
                                    }else {
                                        user.manager          = {};
                                        user.manager.fullname = resp.data.fullname;
                                        toaster.pop('success','Thông báo',resp.message);
                                    }
                                })
                            }
                        }


                    },
                    size: 'md',
                    resolve: {
                        user: function () {
                            return $scope.user;
                        }
                    }
                });
            }
            // Nhận quyền quản lý
            
            // Đổi quyền quản lý
            $scope.OpenChangeManager = function (){
                var modalInstance = $modal.open({
                    templateUrl: 'tpl/merchant/modal/change_manager.html',
                    controller: function($scope, $modalInstance, $http, user) {
                        $scope.list_user = [];
                        $scope.submit_load = false;
                        function getFullname  (id){
                            for(var property in $scope.list_user){
                                if($scope.list_user[property].user_id == id){
                                    return $scope.list_user[property].user.fullname;
                                }
                            }
                        }

                        $scope.loadSeller = function (){
                            $http.get(ApiPath + 'user-info/useradmin').success(function(resp){
                                if(!resp.error){
                                    $scope.list_user = resp.data;
                                }
                            })
                        }
                        $scope.cancel = function() {
                            $modalInstance.dismiss('cancel');
                        };
                        $scope.change = function (sellerId, note){
                            if(user.id && sellerId && note) {
                                $scope.submit_load = true;
                                $http.post(ApiPath + 'seller/change-user/'+user.id, {seller: sellerId, note: note}).success(function (resp){
                                    $scope.submit_load = false;
                                    if(resp.error){
                                        toaster.pop('error','Thông báo', resp.message);
                                    }else {
                                        user.manager = {};
                                        user.manager.fullname = getFullname(sellerId);
                                        toaster.pop('success','Thông báo',resp.message);
                                        $scope.cancel();
                                    }
                                }).error(function (){
                                    toaster.pop('error','Thông báo', 'Lỗi kết nối server ');
                                })
                            }else {
                                toaster.pop('warning','Thông báo', 'Vui lòng chọn người quản lý mới và nhập lý do thay đổi');
                            }
                        }

                        $scope.loadSeller();

                    },
                    size: 'md',
                    resolve: {
                        user: function () {
                            return $scope.user;
                        }
                    }
                });
            }

            $scope.updateVip = function (action){
                if(!action){
                    return;
                }
                var msg = action == 'update' ? 'Bạn muốn cập nhật thành viên này lên VIP' : 'Bạn chuyển thành viên này về thành viên thường';
                if(!confirm(msg)){
                    return ;
                }
                $http({
                    url: ApiPath+'user/vip/' + $scope.user.id + '?action='+action,
                    method: "POST",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                        if(action == 'update'){
                            toaster.pop('success', 'Thông báo', 'Cập nhật lên thành viên VIP thành công !');
                            $scope.user.is_vip = 1;
                            return;
                        }
                        $scope.user.is_vip = 0;
                        toaster.pop('success', 'Thông báo', 'Hủy thành viên VIP thành công !');
                    }
                    else{
                        toaster.pop('error', 'Thông báo', 'Không thể cập nhật dữ liệu!');
                    }
                });

            }


            // Hiển thị lịch sử quản lý KH
            $scope.OpenHistoryManager = function (){
                var modalInstance = $modal.open({
                    templateUrl: 'tpl/merchant/modal/history_manager.html',
                    controller: function($scope, $modalInstance, $http, user) {
                        $scope.loading   = true;
                        $scope.list_data = [];
                        
                        $scope.loadHistory = function (){
                            var url = ApiPath + 'seller/history/' + user.id;
                            $http.get(url).success(function (resp){
                                $scope.loading = false;
                                if(resp.error){
                                    toaster.pop('warning', 'Thông báo', 'Lỗi kết nối ');
                                }else {
                                    $scope.list_data = resp.data;
                                    toaster.pop('success', 'Thông báo', resp.message);
                                }
                            });
                        }

                        $scope.loadHistory();
                    },
                    size: 'md',
                    resolve: {
                        user: function () {
                            return $scope.user;
                        }
                    }
                });
            }

            $scope.ChangeTab    = function(tab){
                if(tab != 'ALL'){
                    if($scope.group_order_status[tab] != undefined){
                        $scope.tab_status   = $scope.group_order_status[tab].toString();
                    }else{
                        $scope.tab_status   = Config_Status.group_status[tab].toString();
                    }
                }else {
                    $scope.tab_status = null;
                }
                $scope.setPage(1);
            }

                
            $scope.return_pickup = function (item){
                if(!confirm('Bạn muốn xử dụng chức năng này ')){
                    return ;
                }
                var url = 'http://services.shipchung.vn/trigger/courier/report-resume?tracking_code=' + item.tracking_code;
                window.open(url);
                /*$http.get(url,{
                    'crossDomain'    : true
                }).success(function (resp){
                    $scope.loading = false;
                    if(resp.error){
                        toaster.pop('warning', 'Thông báo', resp.error_message);
                    }else {
                        item.requested = true;
                        toaster.pop('success', 'Thông báo', "Thành công");
                    }
                });*/

            }
            if($stateParams.search.match(/^SC\d+$/gi)  || $stateParams.search.match(/^EV\d\w+$/gi)){
                $scope.frm.tracking_code    = $stateParams.search;
                $scope.setPage(1);
                $scope.object = 'order';
            }else if($stateParams.search.match(/^@/g)){
                $scope.frm.to_user  = $stateParams.search.substr(1);
                $scope.object = 'receiver';
                $scope.setPage(1);
                $scope.setCountGroup();
            }else if($stateParams.search.match(/^!/g)){
                $scope.frm.courier_tracking_code    = $stateParams.search.substr(1);
                $scope.setPage(1);
                $scope.object = 'order';
            }else if($stateParams.search.split('.').length > 2 && $stateParams.search.split('.')[0] == 'S20'){
                $scope.frm.tracking_code    = $stateParams.search;
                $scope.setPage(1);
                $scope.object = 'order';
            }else{

                $scope.SearchUser($stateParams.search);
            }


            $scope.exportExcel = function(){
                $scope.refresh('export');
                return Accounting.report(1,$scope.frm,'export');
            }
        }else{
            $scope.waiting              = false;
        }


        $scope.ActiveWarehouse = function (user){
            if(!confirm('Bạn muốn kích hoạt boxme cho khách hàng này ? ')){
                user.fulfillment    = 0;
                return false;
            }

            $http.post(ApiPath + 'seller/active-warehouse/'+ user.id).success(function (resp){
                if(!resp.error){
                    user.fulfillment    = 1;
                    toaster.pop('success', 'Thông báo', 'Thành công');
                }else {
                    toaster.pop('warning', 'Thông báo', resp.error_message);
                }
            })
        }

        $scope.opened = {};
        $scope.open = function ($event, time){
            $event.preventDefault();
            $event.stopPropagation();
            $scope.opened[time] = !$scope.opened[time];
        }

        $scope.UpdateIncomingsTime  = function(time, user){
            var deferred     = $q.defer();
            var time_start  = '';

            if(time != undefined && time != ''){
                time_start    = +Date.parse(time)/1000;
            }else{
                deferred.reject('Cần chọn thời gian phù hợp');
                return deferred.promise;
            }

            $http({
                url: ApiPath + 'seller/change-incomings-time/'+ user.id,
                method: "POST",
                data:{'time_start' : time_start},
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(!result.error){
                    deferred.resolve(result.error_message);
                    toaster.pop('success', 'Thông báo', 'Thành công');
                }
                else{
                    user.first_time_incomings    = '';
                    toaster.pop('error', 'Thông báo', result.error_message);
                    deferred.reject(result.error_message);
                }
            }).catch(function(error) {
                user.first_time_incomings    = '';
                deferred.reject('Kết nối dữ liệu thất bại !');
            });
            
            return deferred.promise;
        }

        $scope.saveContract = function(data,field,id){
            var myData = {};
            myData[field] = data;
            $http({
                url: ApiOps+'log/contract/'+id,
                method: "POST",
                data:myData,
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(!result.error){
                    toaster.pop('success', 'Thông báo', 'Thành công!');
                }          
                else{
                    toaster.pop('error', 'Thông báo', 'Không thể cập nhật dữ liệu!');
                }
            });
        }
        //yeu cau luu kho
        $scope.storeStock = function(id){
            if(id < 0 ){
                return;
            }
            var modalInstance = $modal.open({
                templateUrl: 'tpl/search/modal.stock.html',
                controller: function($scope, $modalInstance, $http) {
                    $scope.dateOptions = {
                        formatYear: 'yy',
                        startingDay: 1
                    };
                    //
                    $scope.change_processs  = function(option, action, group, status, note, send_zalo, time_store, callback){
                        var dataupdate = {};

                        if(note != undefined && note != ''&& id > 0 ){
                            $http.post(ApiPath + 'order-process/' + 'create-journey-cs', {
                                'tracking_code' : id,
                                'option'        : option,
                                'pipe_status'   : status,
                                'note'          : note,
                                'send_zalo'     : send_zalo,
                                'group'         : group,
                                'action'        : action,
                                'time_store'    : time_store
                            }).success(function (resp){
                                if(resp.error){
                                    callback(true, resp);
                                }else {
                                    callback(null, resp);
                                }
                            })
                        }

                        return;
                    };

                    $scope.open = function($event,type) {
                        $event.preventDefault();
                        $event.stopPropagation();
                        if(type == "time_store"){
                            $scope.time_store = true;
                        }
                    };
                    $scope.save = function(time,note) {
                        var timeStore = Date.parse(time)/1000;
                        $scope.change_processs(1, 1, 29, 707, note , '', timeStore,  function (err, resp ){
                            if(!err){
                                toaster.pop('success', 'Thông báo', resp.error_message);
                                $modalInstance.dismiss('cancel');
                            }else {
                                toaster.pop('warning', 'Thông báo', resp.error_message);
                            }
                        });
                    };

                    $scope.cancel = function() {
                        $modalInstance.dismiss('cancel');
                    };
                },
                size: 'md',
                resolve: {
                }
            });
        }


    }
]);
