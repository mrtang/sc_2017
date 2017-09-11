'use strict';

angular.module('app').controller('ReportReplayCtrl', ['$scope', '$rootScope', '$filter', 'Order', 'Config_Status',
 	function($scope, $rootScope, $filter, Order, Config_Status) {

        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;
        $scope.total_group          = [];
        $scope.total_all            = 0;
        $scope.time                 = {pickup_start : new Date(date.getFullYear(), date.getMonth(), 1)};
        $scope.frm                  = {report_replay : 0, global: 0, accept_start : +Date.parse(new Date(date.getFullYear(), 1*date.getMonth() - 2, 1))/1000 };

        $scope.list_data            = {};
        $scope.list_location        = {'list_city': {},'list_district': {},'list_ward': {},'list_to_address': {},'list_from_address': {}};

        $scope.list_color           = Config_Status.order_color;
        $scope.list_pipe_status     = {};
        $scope.pipe_status          = {707 : 'Phát thất bại - YCPL', 708 : 'Phát thất bại - Đã báo HVC', 903 : 'Chờ XNCH - YCPL', 904 : 'Chờ XNCH - Đã báo HVC'};
        $scope.pipe_limit           = 0;
        $scope.check_box_status     = [];
        $scope.tab                  = 28;

        $scope.list_reponse         = {};
        $scope.list_color           = Config_Status.order_color;
        $scope.tag_color            = Config_Status.tag_color;

        if($scope.group_order_status[$scope.tab] == undefined){
            $scope.group_order_status[$scope.tab]   = Config_Status.group_status[$scope.tab];
        }
        $scope.tab_status           = $scope.group_order_status[$scope.tab].toString();

        $scope.waiting              = false;
        $scope.waiting_export       = false;

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "time_pickup_start"){
                $scope.time_pickup_start_open = true;
            }else if(type == "time_pickup_end"){
                $scope.time_pickup_end = true;
            }
        };

        $scope.$watch('frm.global', function(newVal, oldVal) {
            if(newVal == 1){
                $scope.frm.to_city = 0;
                $scope.frm.to_district = 0;
            }
        });

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

            if($scope.time.report_start != undefined && $scope.time.report_start != ''){
                $scope.frm.report_start   = +Date.parse($scope.time.report_start)/1000;
            }else{
                $scope.frm.report_start   = 0;
            }
            if($scope.time.report_end != undefined && $scope.time.report_end != ''){
                $scope.frm.report_end     = +Date.parse($scope.time.report_end)/1000 + 86399;
            }else{
                $scope.frm.report_end     = 0;
            }

            if($scope.check_box_status != undefined && $scope.check_box_status.length > 0){
                $scope.tab_status           = $scope.check_box_status.toString();
            }else{
                $scope.tab_status           = $scope.group_order_status[$scope.tab].toString();
            }

            if(cmd != 'export'){
                $scope.list_data            = {};
                $scope.list_location        = {'list_city': {},'list_district': {},'list_ward': {},'list_to_address': {},'list_from_address': {}};
                $scope.waiting              = true;
            }

        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.refresh('');
            Order.ListReportReplay($scope.currentPage,$scope.frm, $scope.tab_status, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                    $scope.list_location.list_city              = result.data.list_city;
                    $scope.list_location.list_district          = result.data.list_district;
                    $scope.list_location.list_ward              = result.data.list_ward;
                    $scope.list_location.list_to_address        = result.data.list_to_address;
                    $scope.list_location.list_from_address      = result.data.list_from_address;
                }
                $scope.waiting  = false;
            });
            return;
        }

        $scope.setCountGroup    = function(){
            $scope.total_all    = 0;
            $scope.total_group  = [];
            $scope.list_reponse = {};
            Order.CountReportReplay($scope.frm, '','status').then(function (result) {
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

        $scope.exportExcel = function(){
            $scope.refresh('export');
            $scope.waiting_export   = true;

            var html =
                "<table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Yêu cầu phát lại</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>TG Duyệt</th>" +
                "<th rowspan='2'>TG Lấy Hàng</th>" +
                "<th rowspan='2'>TG Phát thành công</th>" +
                "<th rowspan='2'>TG tạo yêu cầu</th>" +
                "<th rowspan='2'>Mã vận đơn</th>" +
                "<th rowspan='2'>Mã đơn hàng</th>" +
                "<th rowspan='2'>HVC</th>" +
                "<th rowspan='2'>Mã HVC</th>" +
                "<th rowspan='2'>Trạng thái yêu cầu</th>" +
                "<th rowspan='2'>Dịch Vụ</th>" +
                "<th rowspan='2'>Trạng thái</th>" +
                "<th colspan='6'>Gửi</th>" +
                "<th colspan='7'>Nhận</th>" +
                "<th rowspan='2'>Nội dung</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Họ tên</td>" +
                "<td>Email</td>" +
                "<td>SDT</td>" +
                "<td>Tỉnh/Thành Phố</td>" +
                "<td>Quận/Huyện</td>" +
                "<td>Địa chỉ</td>" +
                "<td>Họ tên</td>" +
                "<td>Email</td>" +
                "<td>SDT</td>" +
                "<td>Bưu cục</td>" +
                "<td>Tỉnh/Thành Phố</td>" +
                "<td>Quận/Huyện</td>" +
                "<td>Địa chỉ</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            Order.ListReportReplay($scope.currentPage,$scope.frm, $scope.tab_status, 'export').then(function (result) {
                if(!result.data.error){
                    var to_address      = result.data.list_to_address;
                    var user            = result.data.user;
                    var pipe_journey    = result.data.list_pipe_journey;
                    var post_office     = result.data.post_office;

                    angular.forEach(result.data.data, function(value) {
                        if(value.journey != undefined){
                            angular.forEach(value.journey, function(v) {
                                html+= "<tr>" +
                                    "<td>"+  i++ +"</td>" +
                                    "<td>"+  (value.time_accept  > 0 ? $filter('date')(value.time_accept*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                                    "<td>"+  (value.time_pickup  > 0 ? $filter('date')(value.time_pickup*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                                    "<td>"+  (value.time_success  > 0 ? $filter('date')(value.time_success*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                                    "<td>"+  ((v.time_create  > 0) ? $filter('date')(v.time_create*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +

                                    "<td>"+  value.tracking_code +"</td>" +
                                    "<td>"+  value.order_code +"</td>" +
                                    "<td>"+  (($scope.list_courier[1*value.courier_id]) ? $scope.list_courier[1*value.courier_id]['name'] : '') +"</td>" +
                                    "<td>"+  value.courier_tracking_code +"</td>" +

                                    "<td>"+  (($scope.pipe_status[v.pipe_status]  != undefined) ? $scope.pipe_status[v.pipe_status] : '') +"</td>" +

                                    "<td>"+  (($scope.service[1*value.service_id]) ? $scope.service[1*value.service_id] : '') +"</td>" +
                                    "<td>"+  (($scope.list_status[1*value.status]) ? $scope.list_status[1*value.status] : '') +"</td>" +


                                    "<td>"+  ((user[1*value.from_user_id])           ? user[1*value.from_user_id]['fullname']    : '') +"</td>" +
                                    "<td>"+  ((user[1*value.from_user_id])           ? user[1*value.from_user_id]['email']       : '') +"</td>" +
                                    "<td>"+  ((user[1*value.from_user_id])           ? user[1*value.from_user_id]['phone']       : '') +"</td>" +
                                    "<td>"+  (($scope.city[1*value.from_city_id])    ? $scope.city[1*value.from_city_id]         : '') +"</td>" +
                                    "<td>"+  (($scope.district[1*value.from_district_id])   ? $scope.district[1*value.from_district_id]['district_name']        : '') +"</td>" +
                                    "<td>"+  value.from_address +"</td>" +

                                    "<td>"+  value.to_name +"</td>" +
                                    "<td>"+  value.to_email +"</td>" +
                                    "<td>"+  value.to_phone +"</td>" +
                                    "<td>"+  ((post_office[1*value.id] != undefined)           ? post_office[1*value.id]['to_postoffice_code']                : '') +"</td>" +
                                    "<td>"+  (((to_address[1*value.to_address_id]) && $scope.city[to_address[1*value.to_address_id]['city_id']])     ? $scope.city[to_address[1*value.to_address_id]['city_id']]     : '') +"</td>" +
                                    "<td>"+  (((to_address[1*value.to_address_id]) && $scope.district[to_address[1*value.to_address_id]['province_id']])    ? $scope.district[to_address[1*value.to_address_id]['province_id']]['district_name']    : '') +"</td>" +
                                    "<td>"+  ((to_address[1*value.to_address_id])    ? to_address[1*value.to_address_id]['address']                  : '') +"</td>" +

                                    "<td>"+ v.note +"</td></tr>"
                            });
                        }
                    });

                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "Yeu_cau_phat_lai.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
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
