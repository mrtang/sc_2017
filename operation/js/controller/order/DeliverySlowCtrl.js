'use strict';

angular.module('app').controller('DeliverySlowCtrl', ['$scope', '$rootScope', '$filter', 'Order', 'Config_Status', 'Base',
 	function($scope, $rootScope, $filter, Order, Config_Status, Base) {

        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;
        $scope.total_group          = [];
        $scope.total_all            = 0;
        $scope.time                 = {accept_start : new Date(date.getFullYear(), date.getMonth(), 1)};
        $scope.frm                  = {group : 103, delivery_slow: "1", type_process : 5, location : 0, num_slow : 0, global: 0};

        $scope.list_data            = {};
        $scope.list_location        = {'list_city': {},'list_district': {}};

        $scope.list_color           = Config_Status.order_color;
        $scope.list_pipe_status     = {};
        $scope.pipe_status          = {};
        $scope.pipe_limit           = 0;
        $scope.pipe_priority        = {};
        $scope.check_box            = [];
        $scope.check_box_status     = [];
        $scope.tab                  = 27;
        $scope.pipe_journey         = {};
        $scope.waiting_export       = false;

        $scope.list_reponse         = {};
        $scope.list_color           = Config_Status.order_color;
        $scope.tag_color            = Config_Status.tag_color;

        if($scope.group_order_status[$scope.tab] == undefined){
            $scope.group_order_status[$scope.tab]   = Config_Status.group_status[$scope.tab];
        }
        $scope.tab_status           = $scope.group_order_status[$scope.tab].toString();

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

        Base.PipeStatus(103, 5).then(function (result) {
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

            if($scope.time.success_start != undefined && $scope.time.success_start != ''){
                $scope.frm.success_start   = +Date.parse($scope.time.success_start)/1000;
            }else{
                $scope.frm.success_start   = 0;
            }
            if($scope.time.success_end != undefined && $scope.time.success_end != ''){
                $scope.frm.success_end     = +Date.parse($scope.time.success_end)/1000 + 86399;
            }else{
                $scope.frm.success_end     = 0;
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
                $scope.list_location        = {'list_city': {},'list_district': {}};
                $scope.pipe_journey         = {};
                $scope.waiting              = true;
            }

        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.refresh('');
            Order.DeliverySlow($scope.currentPage,$scope.frm, $scope.tab_status, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data                    = result.data.data;
                    $scope.totalItems                   = result.data.total;
                    $scope.item_stt                     = $scope.item_page * ($scope.currentPage - 1);
                    $scope.list_location.list_city      = result.data.list_city;
                    $scope.list_location.list_district  = result.data.list_district;
                    $scope.pipe_journey                 = result.data.list_pipe_journey;
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
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh Sách Giao Chậm</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>TG Duyệt</th>" +
                "<th rowspan='2'>TG Lấy Hàng</th>" +
                "<th rowspan='2'>TG duyệt hoàn</th>" +
                "<th rowspan='2'>TG Thành Công</th>" +
                "<th rowspan='2'>Mã SC</th>" +
                "<th rowspan='2'>HVC</th>" +
                "<th rowspan='2'>Mã HVC</th>" +
                "<th rowspan='2'>Domain</th>" +
                "<th rowspan='2'>Dịch vụ</th>" +
                "<th rowspan='2'>Trạng thái</th>" +
                "<th colspan='1'>Khách hàng</th>" +
                "<th colspan='2'>Bên gửi</th>" +
                "<th colspan='3'>Bên Nhận</th>" +
                "<th colspan='3'>Thời gian PTB</th>" +
                "<th colspan='4'>Cam kết HVC</th>" +
                "<th colspan='3'>TG Quá Hạn</th>" +
                "<th rowspan='2'>Ghi chú</th>" +

                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Email</td>" +

                "<td>Tỉnh Thành</td>" +
                "<td>Quận Huyện</td>" +

                "<td>Tỉnh Thành</td>" +
                "<td>Quận Huyện</td>" +
                "<td>Mã BC</td>" +

                "<td>Lần 1</td>" +
                "<td>Lần 2</td>" +
                "<td>Lần 3</td>" +

                "<td>HVC (h)</td>" +
                "<td>Lần 1</td>" +
                "<td>Lần 2</td>" +
                "<td>Lần 3</td>" +

                "<td>Lần 1 (h)</td>" +
                "<td>Lần 2 (h)</td>" +
                "<td>Lần 3 (h)</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            Order.DeliverySlow($scope.currentPage,$scope.frm, $scope.tab_status, 'export').then(function (result) {
                if(!result.data.error){
                    var list_city       = result.data.list_city;
                    var list_district   = result.data.list_district;
                    var list_possoffice = result.data.post_office;
                    var note           = result.data.note;
                    var first_slow      = '';
                    var second_slow     = '';
                    var third_slow      = '';
                    var time_slow       = 0;
                    angular.forEach(result.data.data, function(value) {
                        first_slow      = '';
                        second_slow     = '';
                        third_slow      = '';
                        time_slow       = 0;

                        if(value.first_slow == undefined){
                            time_slow   = $scope.__get_time_slow(value.first_promise_time);
                            if(time_slow > 0){
                                first_slow  = time_slow;
                            }
                        }
                        if(1*value.first_slow > 0){
                            first_slow   = (value.first_slow/3600);
                        }

                        if(value.second_slow == undefined && value.second_promise_time > 0){
                            time_slow   = $scope.__get_time_slow(value.second_promise_time);
                            if(time_slow > 0){
                                second_slow  = time_slow;
                            }
                        }
                        if(1*value.second_slow > 0){
                            second_slow   = (value.second_slow/3600);
                        }

                        if(value.third_slow == undefined && value.third_promise_time > 0){
                            time_slow   = $scope.__get_time_slow(value.third_promise_time);
                            if(time_slow > 0){
                                third_slow  = time_slow;
                            }
                        }

                        if(1*value.third_slow > 0){
                            third_slow   = (value.third_slow/3600);
                        }

                        html+= "<tr>" +
                            "<td>"+ i++ +"</td>" +
                            "<td>"+  (value.time_accept  > 0 ? $filter('date')(value.time_accept*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_pickup  > 0 ? $filter('date')(value.time_pickup*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_accept_return  > 0 ? $filter('date')(value.time_accept_return*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_success > 0 ? $filter('date')(value.time_success*1000,"dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  value.tracking_code +"</td>" +
                            "<td>"+ $scope.courier[value.courier_id] +"</td>" +
                            "<td>"+ value.courier_tracking_code +"</td>" +
                            "<td>"+ value.domain +"</td>" +
                            "<td>"+ $scope.service[value.service_id] +"</td>" +
                            "<td>"+ $scope.list_status[value.status] +"</td>" +

                            "<td>"+ (value.email != undefined ? value.email : '') +"</td>" +

                            "<td>"+  ((list_city[1*value.from_city_id])           ? list_city[1*value.from_city_id]                 : '') +"</td>" +
                            "<td>"+  ((list_district[1*value.from_district_id])     ? list_district[1*value.from_district_id]           : '') +"</td>" +

                            "<td>"+  ((list_city[1*value.to_city_id])         ? list_city[1*value.to_city_id]               : '') +"</td>" +
                            "<td>"+  ((list_district[1*value.to_district_id])   ? list_district[1*value.to_district_id]         : '') +"</td>" +
                            "<td>"+  ((list_possoffice[1*value.order_id])   ? list_possoffice[1*value.order_id]['to_postoffice_code']         : '') +"</td>" +

                            "<td>"+ (value.first_fail_time  > 0 ? $filter('date')(value.first_fail_time*1000, "dd/MM/yyyy  HH:mm:ss") : '')  +"</td>" +
                            "<td>"+ (value.second_fail_time  > 0 ? $filter('date')(value.second_fail_time*1000, "dd/MM/yyyy  HH:mm:ss") : '')  +"</td>" +
                            "<td>"+ (value.third_fail_time  > 0 ? $filter('date')(value.third_fail_time*1000, "dd/MM/yyyy  HH:mm:ss") : '')  +"</td>" +

                            "<td>"+ value.courier_estimate +"</td>" +
                            "<td>"+ (value.first_promise_time  > 0 ? $filter('date')(value.first_promise_time*1000, "dd/MM/yyyy  HH:mm:ss") : '')  +"</td>" +
                            "<td>"+ (value.second_promise_time  > 0 ? $filter('date')(value.second_promise_time*1000, "dd/MM/yyyy  HH:mm:ss") : '')  +"</td>" +
                            "<td>"+ (value.third_promise_time  > 0 ? $filter('date')(value.third_promise_time*1000, "dd/MM/yyyy  HH:mm:ss") : '')  +"</td>" +
                            "<td>"+ $filter('number')(first_slow, 0) +"</td>" +
                            "<td>"+ $filter('number')(second_slow, 0) +"</td>" +
                            "<td>"+ $filter('number')(third_slow, 0) +"</td>" +
                            "<td>"+  ((note[1*value.order_id])     ? note[1*value.order_id]           : '') +"</td>" +
                            "</tr>"
                        ;
                    });
                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "danh_sach_giao_cham.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
            return;
        }

        $scope.export_statistic = function(){
            $scope.refresh('export');
            $scope.waiting_export   = true;
            var html =
                "<table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Thống kê</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th>Không liên lạc được</th>" +
                "<th>Từ chối nhận</th>" +
                "<th>Sai địa chỉ</th>" +
                "<th>Khách đi vắng</th>" +
                "<th>Hẹn lên bưu cục</th>" +
                "<th>Lý do khác</th>" +
                "</tr></thead><tbody><tr>";

            var i = 1;

            Order.DeliverySlow($scope.currentPage,$scope.frm, $scope.tab_status, 'statistic').then(function (result) {
                if(!result.data.error){
                    var list_city       = result.data.list_city;
                    var list_district   = result.data.list_district;
                    var note            = result.data.note;
                    var problem         = result.data.problem;
                    var total_group     = result.data.total_group;

                    // Add group
                    html += "<td>"+ (total_group['ko_ll_duoc'] != undefined ? (total_group['ko_ll_duoc']) : "0") + "/" + total_group['total'] +"</td>";
                    html += "<td>"+ (total_group['tu_choi_nhan'] != undefined ? (total_group['tu_choi_nhan']) : "0") + "/" + total_group['total'] +"</td>";
                    html += "<td>"+ (total_group['sai_dia_chi'] != undefined ? (total_group['sai_dia_chi']) : "0") + "/" + total_group['total'] +"</td>";
                    html += "<td>"+ (total_group['di_vang'] != undefined ? (total_group['di_vang']) : "0") + "/" + total_group['total'] +"</td>";
                    html += "<td>"+ (total_group['hen_len_bc'] != undefined ? (total_group['hen_len_bc']) : "0") + "/" + total_group['total'] +"</td>";
                    html += "<td>"+ (total_group['ly_do_khac'] != undefined ? (total_group['ly_do_khac']) : "0") + "/" + total_group['total'] +"</td>";

                    html += "</tr><tr></tr><tr></tr></thead></tbody></table>" +
                        "<table width='100%' border='1'>" +
                        "<thead><tr>" +
                        "<td style='border-style:none'></td>" +
                        "<td style='border-style:none'></td>"+
                        "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh sách lý do giao chậm</strong></td></tr>" +
                        "<tr></tr>" +
                        "<tr style='font-size: 14px; background: #6b94b3'>" +
                        "<th rowspan='2'>STT</th>" +
                        "<th rowspan='2'>TG Duyệt</th>" +
                        "<th rowspan='2'>TG Lấy Hàng</th>" +
                        "<th rowspan='2'>TG duyệt hoàn</th>" +
                        "<th rowspan='2'>TG Thành Công</th>" +
                        "<th rowspan='2'>Mã SC</th>" +
                        "<th rowspan='2'>HVC</th>" +
                        "<th rowspan='2'>Mã HVC</th>" +
                        "<th rowspan='2'>Dịch vụ</th>" +
                        "<th rowspan='2'>Trạng thái</th>" +
                        "<th colspan='2'>Bên gửi</th>" +
                        "<th colspan='2'>Bên Nhận</th>" +
                        "<th rowspan='2'>Phân loại</th>" +
                        "<th rowspan='2'>Lý do</th>" +

                        "</tr>" +
                        "<tr style='font-size: 14px; background: #6b94b3'>" +

                        "<td>Tỉnh Thành</td>" +
                        "<td>Quận Huyện</td>" +

                        "<td>Tỉnh Thành</td>" +
                        "<td>Quận Huyện</td>" +
                        "</tr>" +
                        "</thead>" +
                        "<tbody>";

                    angular.forEach(result.data.data, function(value) {
                        html+= "<tr>" +
                            "<td>"+ i++ +"</td>" +
                            "<td>"+  (value.time_accept  > 0 ? $filter('date')(value.time_accept*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_pickup  > 0 ? $filter('date')(value.time_pickup*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_accept_return  > 0 ? $filter('date')(value.time_accept_return*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_success > 0 ? $filter('date')(value.time_success*1000,"dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  value.tracking_code +"</td>" +
                            "<td>"+ $scope.courier[value.courier_id] +"</td>" +
                            "<td>"+ value.courier_tracking_code +"</td>" +
                            "<td>"+ $scope.service[value.service_id] +"</td>" +
                            "<td>"+ $scope.list_status[value.status] +"</td>" +

                            "<td>"+  ((list_city[1*value.from_city_id])           ? list_city[1*value.from_city_id]                 : '') +"</td>" +
                            "<td>"+  ((list_district[1*value.from_district_id])     ? list_district[1*value.from_district_id]           : '') +"</td>" +

                            "<td>"+  ((list_city[1*value.to_city_id])         ? list_city[1*value.to_city_id]               : '') +"</td>" +
                            "<td>"+  ((list_district[1*value.to_district_id])   ? list_district[1*value.to_district_id]         : '') +"</td>" +
                            "<td>"+  ((problem[1*value.order_id])     ? problem[1*value.order_id]           : '') +"</td>" +
                            "<td>"+  ((note[1*value.order_id])     ? note[1*value.order_id]           : '') +"</td>" +
                            "</tr>"
                        ;
                    });
                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "thong_ke_ly_do_giao_cham.xls");
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
