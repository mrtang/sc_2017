'use strict';

angular.module('app').controller('ReturnSlowCtrl', ['$scope', '$filter', 'Order', 'Config_Status', 'Base',
 	function($scope, $filter, Order, Config_Status, Base) {

        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;
        $scope.total_group          = [];
        $scope.total_all            = 0;
        $scope.time                 = {accept_start : new Date(date.getFullYear(), date.getMonth(), 1)};
        $scope.frm                  = {group : 106, return_slow: 1, type_process : 5, courier : "1", global: 0};

        $scope.list_data            = {};
        $scope.list_location        = {'list_city': {},'list_district': {},'list_from_address': {}};

        $scope.list_color           = Config_Status.order_color;
        $scope.list_pipe_status     = {};
        $scope.pipe_status          = {};
        $scope.pipe_limit           = 0;
        $scope.pipe_priority        = {};
        $scope.check_box            = [];
        $scope.check_box_status     = [];
        $scope.tab                  = 30;
        $scope.waiting_export       = false;

        $scope.list_reponse         = {};
        $scope.list_color           = Config_Status.order_color;
        $scope.tag_color            = Config_Status.tag_color;

        if($scope.group_order_status[$scope.tab] == undefined){
            $scope.group_order_status[$scope.tab]   = Config_Status.group_status[$scope.tab];
        }
        $scope.tab_status           = $scope.group_order_status[$scope.tab].toString();

        $scope.waiting              = true;

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "time_accept_start"){
                $scope.time_accept_start_open = true;
            }else if(type == "time_accept_end"){
                $scope.time_accept_end = true;
            }
        };

        $scope.$watch('frm.global', function(newVal, oldVal) {
            if(newVal == 1){
                $scope.frm.to_city = 0;
                $scope.frm.to_district = 0;
            }
        });

        Base.PipeStatus(106, 5).then(function (result) {
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

            if($scope.time.accept_return_start != undefined && $scope.time.accept_return_start != ''){
                $scope.frm.accept_return_start   = +Date.parse($scope.time.accept_return_start)/1000;
            }else{
                $scope.frm.accept_return_start   = 0;
            }
            if($scope.time.accept_return_end != undefined && $scope.time.accept_return_end != ''){
                $scope.frm.accept_return_end     = +Date.parse($scope.time.accept_return_end)/1000 + 86399;
            }else{
                $scope.frm.accept_return_end     = 0;
            }

            if($scope.time.success_start != undefined && $scope.time.success_start != ''){
                $scope.frm.success_start    = +Date.parse($scope.time.success_start)/1000;
            }else{
                $scope.frm.success_start    = 0;
            }

            if($scope.time.success_end != undefined && $scope.time.success_end != ''){
                $scope.frm.success_end      = +Date.parse($scope.time.success_end)/1000 + 86399;
            }else{
                $scope.frm.success_end      = 0;
            }

            if($scope.check_box_status != undefined && $scope.check_box_status.length > 0){
                $scope.tab_status           = $scope.check_box_status.toString();
            }else{
                $scope.tab_status           = $scope.group_order_status[$scope.tab].toString();
            }

            if($scope.check_box != undefined && $scope.check_box.length > 0){
                $scope.frm.pipe_status      = $scope.check_box.toString();
            }else{
                $scope.frm.pipe_status  = '';
            }

            if(cmd != 'export'){
                $scope.list_data            = {};
                $scope.list_location        = {'list_city': {},'list_district': {},'list_from_address': {}};
                $scope.waiting              = true;
            }

        }

        $scope.__get_time_slow  = function(time_now, time){

            if(time_now == ''){
                var date = Date.parse(new Date)/1000;
            }else{
                var date = time_now;
            }

            var long = 0;
            if(date > time){
                long    = (date - 1*time)/3600;
            }
            return long;
        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.refresh('');
            Order.ReturnSlow($scope.currentPage,$scope.frm, $scope.tab_status, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);

                    $scope.list_location.list_city              = result.data.list_city;
                    $scope.list_location.list_district          = result.data.list_district;
                    
                    $scope.list_location.list_from_address      = result.data.list_from_address;
                }
                $scope.waiting  = false;
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

        $scope.setPage(1);
        $scope.setCountGroup();

        $scope.export_excel = function(){
            $scope.refresh('export');
            $scope.waiting_export   = true;
            var html =
                "<table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh Sách Hoàn Chậm</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>TG Duyệt</th>" +
                "<th rowspan='2'>TG Lấy Hàng</th>" +
                "<th rowspan='2'>TG Thành Công</th>" +
                "<th rowspan='2'>Mã SC</th>" +
                "<th rowspan='2'>HVC</th>" +
                "<th rowspan='2'>Mã HVC</th>" +
                "<th rowspan='2'>Domain</th>" +
                "<th rowspan='2'>Dịch vụ</th>" +
                "<th rowspan='2'>Trạng thái</th>" +
                "<th colspan='5'>Bên gửi</th>" +
                "<th colspan='6'>Bên Nhận</th>" +
                "<th rowspan='2'>Thời gian duyệt hoàn</th>" +
                "<th colspan='2'>Cam kết HVC</th>" +
                "<th rowspan='2'>TG Quá Hạn (h)</th>" +

                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Email</td>" +
                "<td>Họ tên</td>" +
                "<td>Số điện thoại</td>" +
                "<td>Tỉnh Thành</td>" +
                "<td>Quận Huyện</td>" +

                "<td>Email</td>" +
                "<td>Họ tên</td>" +
                "<td>Số điện thoại</td>" +
                "<td>Mã BC</td>" +
                "<td>Tỉnh Thành</td>" +
                "<td>Quận Huyện</td>" +

                "<td>HVC (h)</td>" +
                "<td>Thời gian</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            Order.ReturnSlow($scope.currentPage,$scope.frm, $scope.tab_status, 'export').then(function (result) {
                if(!result.data.error){
                    var list_city           = result.data.list_city;
                    var list_district       = result.data.list_district;
                    var list_from_address   = result.data.list_from_address;
                    var list_user           = result.data.list_user;
                    var time_slow           = 0;

                    angular.forEach(result.data.data, function(value) {
                        time_slow   = 0;
                        if(value.time_success > 0){
                            time_slow   = $scope.__get_time_slow(value.time_success, value.promise_time);
                        }else{
                            time_slow   = $scope.__get_time_slow('', value.promise_time);
                        }

                        html+= "<tr>" +
                            "<td>"+ i++ +"</td>" +
                            "<td>"+  (value.time_accept  > 0 ? $filter('date')(value.time_accept*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_pickup  > 0 ? $filter('date')(value.time_pickup*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_success > 0 ? $filter('date')(value.time_success*1000,"dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  value.tracking_code +"</td>" +
                            "<td>"+ $scope.courier[value.courier_id] +"</td>" +
                            "<td>"+ value.courier_tracking_code +"</td>" +
                            "<td>"+ value.domain +"</td>" +
                            "<td>"+ $scope.service[value.service_id] +"</td>" +
                            "<td>"+ $scope.list_status[value.status] +"</td>" +

                            "<td>"+ list_user[value.from_user_id]['email'] +"</td>" +
                            "<td>"+ ((list_from_address[1*value.from_address_id])   ? list_from_address[1*value.from_address_id]['phone']   : list_user[value.from_user_id]['phone']) +"</td>" +
                            "<td>"+ ((list_from_address[1*value.from_address_id])   ? list_from_address[1*value.from_address_id]['user_name']   : list_user[value.from_user_id]['fullname']) +"</td>" +
                            "<td>"+  ((list_city[1*value.from_city_id])             ? list_city[1*value.from_city_id]                 : '') +"</td>" +
                            "<td>"+  ((list_district[1*value.from_district_id])     ? list_district[1*value.from_district_id]           : '') +"</td>" +

                            "<td>"+ value.to_email +"</td>" +
                            "<td>"+ value.to_name +"</td>" +
                            "<td>"+ value.to_phone +"</td>" +
                            "<td>"+ (value.__post_office != undefined ? value.__post_office.to_postoffice_code : "") +"</td>" +
                            "<td>"+  ((list_city[1*value.to_city_id])               ? list_city[1*value.to_city_id]                 : '') +"</td>" +
                            "<td>"+  ((list_district[1*value.to_district_id])       ? list_district[1*value.to_district_id]         : '') +"</td>" +

                            "<td>"+ (value.time_accept_return  > 0 ? $filter('date')(value.time_accept_return*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+ value.courier_estimate +"</td>" +
                            "<td>"+ ((value.promise_time  > 0) ? $filter('date')(value.promise_time*1000, "dd/MM/yyyy  HH:mm:ss") : '')  +"</td>" +
                            "<td>"+ $filter('number')(time_slow, 0) +"</td>" +
                            "</tr>"
                        ;
                    });
                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "danh_sach_hoan_cham.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
            return;
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
