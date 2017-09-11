'use strict';

angular.module('app').controller('PickupSlowCtrl', ['$scope', '$rootScope', '$filter', 'Order', 'Config_Status', 'Base',
 	function($scope, $rootScope, $filter, Order, Config_Status, Base) {

        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;
        $scope.total_group          = [];
        $scope.total_all            = 0;
        $scope.time                 = {accept_start : new Date(date.getFullYear(), date.getMonth(), date.getDate())};
        $scope.frm                  = {group : 107, type_process : 5, location : 0,group_order : 108, global: 0};

        $scope.list_data            = {};
        $scope.list_location        = {'list_city': {},'list_district': {},'list_ward': {}};

        $scope.list_color           = Config_Status.order_color;
        $scope.list_pipe_status     = {};
        $scope.pipe_status          = {};
        $scope.pipe_limit           = 0;
        $scope.pipe_priority        = {};
        $scope.check_box            = [];
        $scope.check_box_order      = [];
        $scope.check_box_status     = [];
        $scope.tab                  = 27;
        $scope.pipe_journey         = {};
        $scope.list_pipe_status_order = {};
        $scope.waiting_export       = false;

        $scope.pickup_slow    = [
            { code : 4      , content : 'dưới 4h'},
            { code : 8      , content : 'trên 4h'},
            { code : 24     , content : 'trên 8h'},
            { code : 25     , content : 'trên 24h'}
        ];

        $scope.list_reponse         = {};
        $scope.list_color           = Config_Status.order_color;
        $scope.tag_color            = Config_Status.tag_color;

        $scope.waiting              = false;
        $scope.totalItems           = 0;

        $scope.__get_time_slow  = function(time){
            var date = Date.parse(new Date)/1000;
            var long = 0;
            if(date > time){
                long    = (date - 1*time)/3600;
            }
            return long;
        }
        $scope.$watch('frm.global', function(newVal, oldVal) {
            if(newVal == 1){
                $scope.frm.to_city = 0;
                $scope.frm.to_district = 0;
            }
        });

        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "time_accept_start"){
                $scope.time_accept_start_open = true;
            }else if(type == "time_accept_end"){
                $scope.time_accept_end = true;
            }
        };

        Base.PipeStatus(107, 5).then(function (result) {
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

        Base.PipeStatus(108, 5).then(function (result) {
            if(!result.data.error){
                $scope.list_pipe_status_order      = result.data.data;
            }
        });

        $scope.toggleSelection = function(id) {
            var data = angular.copy($scope.check_box);
            var idx = +data.indexOf(id);

            if (idx > -1) {
                $scope.check_box.splice(idx, 1);
            }
            else {
                $scope.check_box.push(id);
            }
        };

        $scope.toggleSelectionOrder = function(id) {
            var data = angular.copy($scope.check_box_order);
            var idx = +data.indexOf(id);

            if (idx > -1) {
                $scope.check_box_order.splice(idx, 1);
            }
            else {
                $scope.check_box_order.push(id);
            }
        };

        $scope.toggleSelectionStatus = function(id) {
            var data = angular.copy($scope.check_box_status);
            var idx = +data.indexOf(id);

            if (idx > -1) {
                $scope.check_box_status.splice(idx, 1);
            }
            else {
                $scope.check_box_status.push(id);
            }
        };

        $scope.refresh = function(cmd){


            if($scope.time.accept_start != undefined && $scope.time.accept_start != ''){
                $scope.frm.accept_start    = +Date.parse($scope.time.accept_start)/1000;
            }else{
                $scope.frm.accept_start    = 0;
            }

            if($scope.time.accept_end != undefined && $scope.time.accept_end != ''){
                $scope.frm.accept_end      = +Date.parse($scope.time.accept_end)/1000 + 86399;
            }else{
                $scope.frm.accept_end      = 0;
            }

            if($scope.time.pickup_start != undefined && $scope.time.pickup_start != ''){
                $scope.frm.pickup_start   = +Date.parse($scope.time.pickup_start)/1000;
            }else{
                $scope.frm.pickup_start   = 0;
            }
            if($scope.time.pickup_end != undefined && $scope.time.pickup_end != ''){
                $scope.frm.pickup_end     = +Date.parse($scope.time.pickup_end)/1000 + 86399;
            }else{
                $scope.frm.pickup_end     = 0;
            }

            if($scope.check_box_status != undefined && $scope.check_box_status != []){
                $scope.tab_status  = '';
                angular.forEach($scope.check_box_status, function(value) {
                    if($scope.group_order_status[value] != undefined){
                        $scope.tab_status += $scope.group_order_status[value].toString()+',';
                    }
                });
            }else{
                $scope.tab_status       = [];
            }

            if($scope.check_box != undefined && $scope.check_box.length > 0){
                $scope.frm.pipe_status      = $scope.check_box.toString();
            }else{
                $scope.frm.pipe_status  = '';
            }

            if($scope.check_box_order != undefined && $scope.check_box_order.length > 0){
                $scope.frm.pipe_status_order      = $scope.check_box_order.toString();
            }else{
                $scope.frm.pipe_status_order  = '';
            }

            if(cmd != 'export'){
                $scope.list_data            = {};
                $scope.list_location        = {'list_city': {},'list_district': {},'list_ward': {}};
                $scope.pipe_journey         = {};
                $scope.waiting              = true;
            }

        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.refresh('');
            Order.PickupSlow($scope.currentPage,$scope.frm, $scope.tab_status, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data                        = result.data.data;
                    $scope.totalItems                       = result.data.total;
                    $scope.item_stt                         = $scope.item_page * ($scope.currentPage - 1);
                    $scope.list_location.list_city          = result.data.list_city;
                    $scope.list_location.list_district      = result.data.list_district;
                    $scope.list_location.list_ward          = result.data.list_ward;
                    $scope.pipe_journey                     = result.data.list_pipe_journey;
                }
                $scope.waiting  = false;
            });
            return;
        }

        $scope.export_excel = function(){
            $scope.refresh('export');
            $scope.waiting_export   = true;
            var html =
                "<table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh Sach Lay Cham</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>TG Duyệt</th>" +
                "<th rowspan='2'>TG Duyệt HVC</th>" +
                "<th rowspan='2'>TG Lấy Hàng</th>" +
                "<th rowspan='2'>Hạn lấy hàng</th>" +
                "<th rowspan='2'>Mã SC</th>" +
                "<th rowspan='2'>HVC</th>" +
                "<th rowspan='2'>Mã HVC</th>" +
                "<th rowspan='2'>Domain</th>" +
                "<th rowspan='2'>Dịch vụ</th>" +
                "<th rowspan='2'>Trạng thái</th>" +
                "<th rowspan='2'>Email Khách hàng</th>" +
                "<th colspan='3'>Kho</th>" +
                "<th colspan='4'>Địa chỉ</th>" +

                "<th rowspan='2'>TG Quá Hạn(h)</th>" +
                "<th rowspan='2'>Hành trình xử lý</th>" +

                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Tên Kho</td>" +
                "<td>Người liên hệ</td>" +
                "<td>Số điện thoại</td>" +
                "<td>Tỉnh Thành</td>" +
                "<td>Quận Huyện</td>" +
                "<td>Phường Xã</td>" +
                "<td>Địa chỉ</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;
            var data = angular.copy($scope.frm);
            data.group = 108;
            Order.PickupSlow($scope.currentPage,data, $scope.tab_status, 'export').then(function (result) {
                if(!result.data.error){
                    var list_district   = result.data.list_district;
                    var list_ward       = result.data.list_ward;
                    var list_inventory  = result.data.list_inventory;
                    var list_pipe_journey   = result.data.list_pipe_journey;

                    var time_slow       = 0;
                    var slow            = 0;
                    var pipe_journey    = '';

                    angular.forEach(result.data.data, function(value) {
                        time_slow       = 0;
                        slow            = 0;
                        pipe_journey    = '';

                        if(value.time_slow == undefined){
                            time_slow   = $scope.__get_time_slow(value.promise_pickup_time);
                            if(time_slow > 0){
                                slow  = time_slow;
                            }
                        }
                        if(1*value.time_slow > 0){
                            slow   = (value.time_slow/3600);
                        }

                        if(list_pipe_journey[1*value.order_id] != undefined){
                            angular.forEach(list_pipe_journey[1*value.order_id], function(v) {
                                pipe_journey += v['note']+', ';
                            });

                        }

                        html+= "<tr>" +
                            "<td>"+ i++ +"</td>" +
                            "<td>"+  (value.time_accept  > 0 ? $filter('date')(value.time_accept*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_approve  > 0 ? $filter('date')(value.time_approve*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_pickup  > 0 ? $filter('date')(value.time_pickup*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.promise_pickup_time  > 0 ? $filter('date')(value.promise_pickup_time*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  value.tracking_code +"</td>" +
                            "<td>"+ $scope.courier[value.courier_id] +"</td>" +
                            "<td>"+ value.courier_tracking_code +"</td>" +
                            "<td>"+ value.domain +"</td>" +
                            "<td>"+ $scope.service[value.service_id] +"</td>" +
                            "<td>"+ $scope.list_status[value.status] +"</td>" +

                            "<td>"+ value.email +"</td>" +

                            "<td>"+  ((list_inventory[1*value.from_address_id])           ? list_inventory[1*value.from_address_id]['name']                 : '') +"</td>" +
                            "<td>"+  ((list_inventory[1*value.from_address_id])           ? list_inventory[1*value.from_address_id]['user_name']                 : '') +"</td>" +
                            "<td>"+  ((list_inventory[1*value.from_address_id])           ? '_'+list_inventory[1*value.from_address_id]['phone']                 : '') +"</td>" +

                            "<td>"+  (($scope.city[1*value.from_city_id])           ? $scope.city[1*value.from_city_id]                 : '') +"</td>" +
                            "<td>"+  ((list_district[1*value.from_district_id])     ? list_district[1*value.from_district_id]           : '') +"</td>" +
                            "<td>"+  ((list_ward[1*value.from_district_id])         ? list_ward[1*value.from_district_id]               : '') +"</td>" +
                            "<td>"+ ((value.from_address) != undefined ? value.from_address : '')  +"</td>" +

                            "<td>"+ $filter('number')(slow, 0) +"</td>" +
                            "<td>"+ pipe_journey +"</td>" +
                            "</tr>"
                        ;
                    });
                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "danh_sach_lay_cham.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
            return;
        }

        $scope.setCountGroup    = function(){return 1;
            $scope.total_all    = 0;
            $scope.total_group  = [];
            $scope.list_reponse = {};
            Order.CountGroup($scope.frm, '','status').then(function (result) {
                if(!result.data.error){
                    $scope.total_all        = result.data.total;

                    angular.forEach(result.data.data, function(value, key) {
                        if($scope.total_group[$scope.status_group[key]] == undefined){
                            $scope.total_group[$scope.status_group[key]]    = 0;
                        }

                        $scope.total_group[$scope.status_group[key]]    += 1*value;
                    });

                }
            });
        }

        $scope.ChangeTab    = function(tab){
            $scope.tab              = tab;
            $scope.check_box_status = [];
            if(tab == 'ALL'){
                $scope.tab_status  = [];
            }else{
                $scope.tab_status   = $scope.group_order_status[tab].toString();
            }
            $scope.setPage(1);
        }

        //$scope.setPage(1);
        //$scope.setCountGroup();

        $scope.exportExcel = function(){
            $scope.refresh('export');
            return Order.ListOrder(1,$scope.frm,$scope.tab_status,'export');
        }

        $scope.getReponse   = function(item, group_status){
            item.waiting = true;
            if($scope.list_reponse[item.id] == undefined || $scope.list_reponse[item.id].length == 0){
                Order.StatusOrder(item.id, group_status).then(function (result) {
                    if(!result.data.error){
                        $scope.list_reponse[item.id]    = '';
                        angular.forEach(result.data.data, function(value) {
                            $scope.list_reponse[item.id] += ', '+value.note
                        });

                        if($scope.list_reponse[item.id] != ''){
                            $scope.list_reponse[item.id]    = $scope.list_reponse[item.id].substr(2);
                        }else{
                            $scope.list_reponse[item.id]    = 'Không có dữ liệu !';
                        }

                        item.waiting = false;
                    }
                });
            }else{
                item.waiting = false;
            }
        }
    }
]);
