'use strict';
var app = angular.module('app');
// ProcessCtrl

    app.controller('VipProcessCtrl', 
    ['$scope', '$rootScope',  '$http', '$state', '$window', 'bootbox', 'Order', 'Location', 'Config_Status','$modal', '$filter', 'toaster', 
    function($scope, $rootScope, $http, $state, $window, bootbox, Order, Location, Config_Status, $modal, $filter, toaster) {
        // config
        $scope.currentPage      = 1;
        $scope.item_page        = 20;
        $scope.maxSize          = 5;
        $scope.list_data        = [];
        $scope.list_city        = [];
        $scope.list_courier     = [];
        $scope.city             = [];
        $scope.district         = {};
        $scope.courier          = {};
        $scope.address          = {};
        $scope.list_color       = Config_Status.order_color;
        $scope.tab_status       = {};
        $scope.list_status      = {};
        $scope.list_group       = {};
        $scope.isTabOverweight  = false;
        
        $scope.list_status_temp = {};

        var date                = new Date();
        $scope.time             = {};
        $scope.waiting          = true;

        $scope.popoverTemplate  = 'myPopoverTemplate.html';
        $scope.popoverData      = {};

        $scope.frm = {
            keyword : '',
            to_district: 0,
            time_create_start : new Date(date.getFullYear(), date.getMonth() - 1 , date.getDate()),
            time_accept_start : '', 
            time_create_end : new Date(date.getFullYear(), date.getMonth(), date.getDate()),
            time_accept_end : '', 
            courier: ''
        };

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };
        $scope.statusLoading = false;

        $scope.onMouseOverPop = function (item){
            $scope.popoverData = [];
            if($scope.list_status_temp[item.tracking_code]){
                $scope.popoverData  = $scope.list_status_temp[item.tracking_code];
                return;
            }
            $scope.statusLoading = true;
            $http.get(ApiPath + 'order-status/order-status?TrackingCode='+item.tracking_code+'&limit=3').success(function (resp){
                $scope.statusLoading = false;
                if(!resp.error){
                    $scope.list_status_temp[item.tracking_code] = resp.data;
                    $scope.popoverData = resp.data;
                }
            })
        }
        $scope.processTimer = function(time){
            var currentDate = new Date();
            var expireDate  = moment(time.time_update * 1000).add(24, 'hours');
            var isExpired   = expireDate.isBefore(currentDate);
            return {
                isExpired   : isExpired,
                diff        : expireDate.diff(currentDate, 'hours')
            }
            /*if(isExpired){
                return "Đã quá hạn xử lý đơn hàng ";
            }
            return 'Còn ' + expireDate.diff(currentDate, 'hours') + ' tiếng nữa để xử lý !';*/
        }

        
        // list city
        Location.province('all').then(function (result) {
            if(!result.data.error){
                $scope.list_city  = result.data.data;
                angular.forEach(result.data.data, function(value) {
                    $scope.city[value.id]   = value.city_name;
                });
            }
        });

        // list courier
        Order.ListCourier().then(function (result) {
            if(!result.data.error){
                $scope.list_courier  = result.data.data;
                angular.forEach(result.data.data, function(value) {
                    $scope.courier[value.id]   = value.name;
                });
            }
        });


        Order.ListStatusOrderProcess().then(function (result) {
            var tab_status = [];
            if(result.data.list_group){
                angular.forEach(result.data.list_group, function(value) {
                    $scope.list_group[+value.id] = value.name;
                    tab_status.push({id : +value.id, name : value.name});
                    if(value.group_order_status){

                        angular.forEach(value.group_order_status, function(v) {
                            $scope.list_status[+v.order_status_code]    = v.group_status;
                        });
                    }
                });
                $scope.tab_status   = tab_status;
            }
            

        });



        // list courier
        $scope.refresh_data = function(cmd){
            var time_create_start = '';
            var time_create_end   = '';
            var time_accept_start = '';
            var time_accept_end   = '';

            if($scope.frm.time_create_start != undefined && $scope.frm.time_create_start != ''){
                $scope.time.time_create_start  = +Date.parse($scope.frm.time_create_start)/1000;
            }else{
                $scope.time.time_create_start  = 0;
            }

            if($scope.frm.time_create_end != undefined && $scope.frm.time_create_end != ''){
                $scope.time.time_create_end  = +Date.parse($scope.frm.time_create_end) / 1000 + 86399;
            }else{
                $scope.time.time_create_end    = 0;
            }

            if($scope.frm.time_accept_start != undefined && $scope.frm.time_accept_start != ''){
                $scope.time.time_accept_start  = +Date.parse($scope.frm.time_accept_start) / 1000;
            }else{
                $scope.time.time_accept_start   = 0;
            }

            if($scope.frm.time_accept_end != undefined && $scope.frm.time_accept_end != ''){
                $scope.time.time_accept_end  = +Date.parse($scope.frm.time_accept_end)/1000 + 86399;
            }else{
                $scope.time.time_accept_end  = 0;
            }

            if($scope.check_box != undefined && $scope.check_box != []){
                $scope.frm.list_status      = $scope.check_box;
            }else{
                $scope.frm.list_status  = [];
            }

            if(cmd != 'export'){
                $scope.waiting      = true;
                $scope.check_action = true;
                $scope.list_data    = {};
                $scope.status_group = {};
                $scope.total        = 0;
            }
        }

        $scope.genTooltip = function (item){
            var content = "";
            item = item.order_process;
            if(item.length > 0){
                for(var i = 0; i < item.length; i++){
                    if(i < 5){
                        var status = (item[i].status) == 1 ? "Chưa xử lý" : "Đã xử lý";
                        content += "<p><span>" + $filter('date')(item[i].time_create*1000, "dd-MM-yyyy HH:mm")  + "</span> <br/>" + item[i].action + "</p>";
                    }
                }
            }
            return content;
        }

        $scope.genTooltipProductInfo = function (item){
            return '<i class="fa fa-shopping-cart"></i> ' + item.product_name + '<br />' +
                                                '<i class="fa fa-tags"></i> ' + $filter('vnNumber')(item.total_amount) + ' đ<br />' +
                                                '<i class="fa fa-shopping-cart"></i> ' + $filter('vnNumber')(item.total_weight) + ' gram';
        }


        $scope.listGroupStatus = [41];
        $scope.change_tab = function (tab_id){
            if(tab_id == 'OVERWEIGHT'){
                $scope.listGroupStatus = [];
                $scope.setPage(true);
            }else {
                $scope.listGroupStatus = [tab_id];
                $scope.setPage(false);
            }
        }


        $scope.exportExcel = function (overweight){
            Order.ListOrderProcess($scope.currentPage, '', $scope.frm.keyword, $scope.listGroupStatus, $scope.time.time_create_start, $scope.time.time_create_end, $scope.time.time_accept_start, $scope.time.time_accept_end, $scope.frm.courier, $scope.frm.to_city, $scope.frm.to_district, 'export', overweight, true);
        }
        $scope.setPage = function(overweight){
            $scope.processTooltip = "";
            $scope.refresh_data('');
            Order.ListOrderProcess($scope.currentPage, '', $scope.frm.keyword, $scope.listGroupStatus, $scope.time.time_create_start, $scope.time.time_create_end, $scope.time.time_accept_start, $scope.time.time_accept_end, $scope.frm.courier, $scope.frm.to_city, $scope.frm.to_district, '', overweight, true).then(function (result) {

                $scope.isTabOverweight  = overweight;
                
                if(!result.data.error){
                    $scope.list_data            = result.data.data;
                    $scope.totalItems           = result.data.total;
                    $scope.item_stt             = $scope.item_page * ($scope.currentPage - 1);
                    $scope.district             = result.data.district;
                    $scope.address              = result.data.address;
                    $scope.status               = result.data.status;
                    $scope.status_group         = result.data.status_group;
                    $scope.total_group          = result.data.total_group;
                    $scope.total_over_weight    = result.data.total_over_weight;
                    $scope.toggleSelectionAll(1);
                }

                $scope.waiting = false;
            });
            return;
        };

        

        
        //Mở popup tạo yêu cầu / khiếu lại cho vận đơn

        var ModalCreateTicket = null;
        $scope.openPopupCreateTicket = function (item, size, isTabOverweight) {
            /*console.log(item.order_process[0].time_create ,  item.time_update);*/
            /*if(item.order_process.length > 0 && !item.time_update || item.order_process[0].time_create > item.time_update){
                bootbox.alert('Vận đơn này đang có một yêu cầu đăng xử lý , bạn không thể gửi thêm !');
            }else {*/
                ModalCreateTicket = $modal.open({
                    templateUrl : 'PopupCreateTicket.html',
                    controller  : 'ProcessOrderCreateTicketCtrl',
                    size : size,
                    resolve: {
                        Item: function () {
                            return item;
                        },
                        modalIns: function (){
                            return ModalCreateTicket;
                        },
                        processType: function (){

                            return (isTabOverweight) ? 3 : ($scope.listGroupStatus[0] == 41) ? 1 : 2;
                        }
                    }
                });
            /*}*/
            
        };


        var sendProcessAction = function (order_id, action, note){
            Order.CreateProcess({
                'order_id'  : order_id,
                'action'    : action,
                'note'      : note
            }, function (err, resp){

            })
        }

        $scope.confirm_pickup = function (item){
            bootbox.prompt({
                message: "<p>Nhập ghi chú cho yêu cầu này để Shipchung hỗ trợ bạn một cách tốt nhất !</p>",
                placeholder: "Thông tin địa chỉ, số điện thoại trường hợp có thay đổi ",
                title: "Bạn chắc chắn muốn yêu cầu lấy lại đơn hàng này ?",
                inputType:"textarea",
                callback: function (result) {
                    if(result !== null){
                        $scope.change(item.status, 38, 'status', item, result,  function (err, resp ){
                            if(!err){
                                $scope.list_data.splice($scope.list_data.indexOf(item), 1);
                                sendProcessAction(item.id, 4, result);
                            }
                        });
                    }
                 }
            });
        }
        $scope.call = function (){
            console.log('blalblabla');
        };

        $scope.confirm_update_contact = function (item){
            var message = "<p>Nhập thông tin liên lạc , ghi chú cho đơn hàng !</p>";
            message     += "<label><input type='radio' value='75' name='status' checked>Phát không thành công / chờ phát lần 2</label><br/>";
            message     += "<label><input type='radio' value='60' name='status'>Chờ XN chuyển hoàn</label><br/>";

            bootbox.prompt({
                message: message,
                placeholder: "Thông tin địa chỉ, số điện thoại trường hợp có thay đổi ",
                title: "Bạn chắc chắn muốn thực hiện chức năng này ?",
                inputType:"textarea",
                callback: function (result) {
                    if(result !== null){
                        var statusChange = $('.bootbox-form').find('input[name="status"]:checked').val();
                        
                        $scope.change(item.status, statusChange, 'status', item, result,  function (err, resp ){
                            if(!err){
                                $scope.list_data.splice($scope.list_data.indexOf(item), 1);
                                sendProcessAction(item.id, 5, result);
                            }
                        });
                    }
                 }
            });
        }
        
        $scope.confirm_order_cancel = function (item){
            bootbox.confirm( "Bạn chắc chắn muốn hủy đơn hàng này ?" , function (result) {
                if(result){
                    $scope.change(item.status, 22, 'status', item, "",  function (err, resp ){
                        if(!err){
                            $scope.list_data.splice($scope.list_data.indexOf(item),1);
                            //sendProcessAction(item.id, processType);
                        }
                    });
                }
            });
        }


        $scope.confirm_report_cancel  = function(item, processType){

            /**
            * @param processType
            * 1 : Yêu cầu giao lại
            * 2 : Xác nhận chuyển hoàn
            * 3 : 
            * 4 : 
            */

            var msg = "";

            if(processType == 1){
                msg = "Bạn chắc chắn muốn yêu cầu giao lại đơn hàng này ?";
                bootbox.prompt({
                    message: "<p>Nhập ghi chú cho yêu cầu này để Shipchung hỗ trợ bạn một cách tốt nhất !</p>",
                    placeholder: "Thông tin địa chỉ, số điện thoại người nhận trong trường hợp có thay đổi ",
                    title: msg,
                    inputType:"textarea",
                    callback: function (result) {
                        if(result !== null){
                            $scope.change(item.status, 67, 'status', item, result,  function (err, resp ){
                                if(!err){
                                    $scope.list_data.splice($scope.list_data.indexOf(item),1);
                                    sendProcessAction(item.id, processType, result);
                                }
                            });
                        }
                     }
                });
                return;
            }

            msg = "Bạn chắc chắn muốn xác nhận chuyển hoàn đơn hàng này ?";
            bootbox.confirm( msg , function (result) {
                if(result){
                    $scope.change(item.status, 61, 'status', item, "",  function (err, resp ){
                        if(!err){
                            $scope.list_data.splice($scope.list_data.indexOf(item),1);
                            sendProcessAction(item.id, processType);
                        }
                    });
                }
            });
            return;
        }




        $scope.check_list = function(id){
            var data = angular.copy($scope.check_box_order);
            var idx = +data.indexOf(id);
            if (idx > -1) {
                return true;
            }
            else {
                return false;
            }
        }

        $scope.toggleSelectionOrder = function(code) {
            var data = angular.copy($scope.check_box_order);
            var idx = +data.indexOf(code);

            if (idx > -1) {
                $scope.check_box_order.splice(idx, 1);
            }
            else {
                $scope.check_box_order.push(code);
            }
        };

        $scope.toggleSelectionAll = function (check){
            var check_box = $scope.check_box_order;
            if(check == 0){
                $scope.check_box_order        = [];
            }else{
                $scope.check_box_order        = [];
                angular.forEach($scope.list_data, function(value, key) {
                    $scope.check_box_order.push(value.tracking_code);
                });
            }
        }


        $scope.mutil_accept_process = false;
        $scope.mutil_accept_over_weight = function (){
            $scope.mutil_accept_process = true;
            if($scope.check_box_order.length > 0){

                Order.AcceptOverWeight($scope.check_box_order[0], function (err, resp){
                    if(err){
                        if($scope.check_box_order.length == 0){
                            toaster.pop('success', 'Thông báo', 'Kết thúc');
                        }else {
                            toaster.pop('warning', 'Thông báo', 'Lỗi kết nối');
                        }
                        $scope.change_tab('OVERWEIGHT');
                    }else {

                        $rootScope._OrderProcess.data['total_over_weight'] --;
                        $rootScope._OrderProcess.total --;
                        $scope.check_box_order.splice(0, 1);
                        $scope.mutil_accept_over_weight();
                    }
                });

            }else {
                toaster.pop('success', 'Thông báo', 'Hoàn thành');
                $scope.mutil_accept_process = false;
                $scope.change_tab('OVERWEIGHT');
            }
        }


        $scope.AcceptOverWeight = function (item){
            Order.AcceptOverWeight(item.tracking_code, function (err, resp){
                if(err){
                    toaster.pop('warning', 'Thông báo', 'Cập nhật thất bại !');
                }else {
                    toaster.pop('success', 'Thông báo', 'Cập nhật thành công !');
                    $scope.change_tab('OVERWEIGHT');
                    $rootScope._OrderProcess.data['total_over_weight'] --;
                    $rootScope._OrderProcess.total --;

                }
            })
        }



        /**
         *   Edit order
         */
        $scope.change   = function(old_value, new_value, field, item, note, callback){
            var dataupdate = {};

            if(new_value != undefined && new_value != ''&& old_value != new_value && item.id > 0 ){
                // Update status
                if(field == 'status'){
                    $scope.waiting_status   = true;
                }

                dataupdate['id']  = item.id;
                dataupdate[field] = new_value;
                dataupdate['note'] = note;

                return  Order.Edit(dataupdate).then(function (result) {
                    if(result.data.error){
                        if(field == 'status' && new_value == 21 && result.data.message == 'NOT_ENOUGH_MONEY'){
                            var modalInstance = $modal.open({
                                templateUrl: 'ModalError.html',
                                controller: 'ModalErrorCtrl',
                                resolve: {
                                    items: function () {
                                        return result.data;
                                    }
                                }
                            });

                            modalInstance.result.then(function () {
                                $scope.cash_in('');
                            });
                        }

                        $scope.waiting_status   = false;
                        callback(true, null);
                        return 'Cập nhật lỗi';
                    }else{
                        if(field == 'status'){
                            callback(null, true);
                        }
                    }
                    return;
                });
            }
            return;
        };

        // action
        $scope.setPage('');
    }
]);

