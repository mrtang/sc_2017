'use strict';

//Provider report
angular.module('app').controller('ProviderReportCtrl', ['$scope', '$filter', '$rootScope', 'Order', 'Config_Status',
 	function($scope, $filter, $rootScope, Order, Config_Status) {
    // config
        $scope.frm                  = {verify_money_collect : 0, type : 1, global: 0};
        $scope.time                 = {accept_start : new Date(date.getFullYear(), date.getMonth(), 1)};
        $scope.currentPage          = 1;
        $scope.totalItems           = 0;
        $scope.total_all            = 0;
        $scope.total_group          = {};
        $scope.list_data            = {};
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.data_sum             = {};

        $scope.check_box            = [];
        $scope.list_color           = Config_Status.order_color;
        $scope.waiting              = false;
        $scope.waiting_export       = false;
            
        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.list_type            = {
            1 : 'Vận đơn CoD',
            2 : 'Vận đơn không CoD'
        };

        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "time_accept_start"){
                $scope.time_accept_start_open = true;
            }else if(type == "time_accept_end"){
                $scope.time_accept_end_open = true;
            }if(type == "time_pickup_start"){
                $scope.time_pickup_start_open = true;
            }else if(type == "time_pickup_end"){
                $scope.time_pickup_end_open = true;
            }else if(type == "time_success_start"){
                $scope.time_success_start_open = true;
            }else if(type == "time_success_end"){
                $scope.time_success_end_open = true;
            }
        };
        
        // action
        
        $scope.ChangeTab = function(cou){
            if(cou != 'ALL'){
                $scope.frm.courier  = cou;
            }else{
                $scope.frm.courier  = 0;
            }

            $scope.setPage(1);
            $scope.setCountGroup();
        }
        
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
        
        $scope.refresh = function(cmd){
            if($scope.frm.courier == undefined || $scope.frm.courier  == ''){
                $scope.frm.courier  = 0;
            }

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
                $scope.frm.pickup_start    = +Date.parse($scope.time.pickup_start)/1000;
            }else{
                $scope.frm.pickup_start    = 0;
            }

            if($scope.time.pickup_end != undefined && $scope.time.pickup_end != ''){
                $scope.frm.pickup_end      = +Date.parse($scope.time.pickup_end)/1000 + 86399;
            }else{
                $scope.frm.pickup_end      = 0;
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

            if($scope.check_box != undefined && $scope.check_box != []){
                $scope.frm.list_status  = '';
                angular.forEach($scope.check_box, function(value) {
                    if($scope.group_order_status[value] != undefined){
                        $scope.frm.list_status += $scope.group_order_status[value].toString()+',';
                    }
                });
            }else{
                $scope.frm.list_status       = [];
            }

            if(cmd != 'export'){
                $scope.waiting          = true;
                $scope.list_data        = [];
                $scope.total_all        = 0;
                $scope.total_group      = [];
            }
        }
        
        $scope.setPage = function(page){
            $scope.currentPage  = page;

            $scope.refresh('');
            var data = angular.copy($scope.frm);
            Order.ListOrder($scope.currentPage,data,'').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting              = false;
            });
            return;
        }

        $scope.setCountGroup    = function(){
            $scope.total_all    = 0;
            $scope.total_group  = [];
            $scope.data_sum     = [];
            var data = angular.copy($scope.frm);
            Order.CountGroup(data).then(function (result) {
                if(!result.data.error){
                    $scope.total_all        = result.data.total;
                    $scope.total_group      = result.data.data;
                    $scope.data_sum         = result.data.sum_total;
                }
            });
        }

        $scope.exportExcel = function(){
            $scope.refresh('export');
            $scope.waiting_export   = true;

            var html =
                "<table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Báo cáo Hãng Vận Chuyển</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>TG Duyệt</th>" +
                "<th rowspan='2'>TG Lấy Hàng</th>" +
                "<th rowspan='2'>TG Giao Hàng</th>" +
                "<th rowspan='2'>Mã vận đơn</th>" +
                "<th rowspan='2'>Domain</th>" +
                "<th rowspan='2'>Kho</th>" +
                "<th rowspan='2'>Mã Order</th>" +
                "<th rowspan='2'>HVC</th>" +
                "<th rowspan='2'>Mã HVC</th>" +
                "<th rowspan='2'>Dịch Vụ</th>" +
                "<th rowspan='2'>Dịch Vụ HVC</th>" +
                "<th rowspan='2'>Trạng thái</th>" +
                "<th rowspan='2'>Khối Lượng</th>" +
                "<th rowspan='2'>Số Lượng</th>" +
                "<th rowspan='2'>Kích Thước</th>" +
                "<th colspan='3'>Gửi</th>" +
                "<th colspan='2'>Nhận</th>" +

                "<th colspan='3'>Phí Kho</th>" +
                "<th colspan='3'>Giảm giá Phí Kho</th>" +
                "<th colspan='7'>Phí</th>" +
                "<th colspan='2'>Giảm giá</th>" +

                "<th colspan='3'>Phí Kho(NCC)</th>" +
                "<th colspan='3'>Giảm giá Phí Kho(NCC)</th>" +

                "<th colspan='4'>Phí(NCC)</th>" +

                "<th rowspan='2'>Tổng tiền thu hộ</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Email</td>" +
                "<td>Tỉnh/Thành Phố</td>" +
                "<td>Quận/Huyện</td>" +
                "<td>Tỉnh/Thành Phố</td>" +
                "<td>Quận/Huyện</td>" +

                "<td>Phí LK</td>" +
                "<td>Phí ĐG</td>" +
                "<td>Phí XL</td>" +
                "<td>Phí LK</td>" +
                "<td>Phí ĐG</td>" +
                "<td>Phí XL</td>" +

                "<td>Phí VC</td>" +
                "<td>Phí CoD</td>" +
                "<td>Phí BH</td>" +
                "<td>Phí CH</td>" +
                "<td>Phí VK</td>" +
                "<td>Phí Vùng Xa</td>" +
                "<td>Phí Khác</td>" +

                "<td>Phí VC</td>" +
                "<td>Phí CoD</td>" +

                "<td>Phí LK</td>" +
                "<td>Phí ĐG</td>" +
                "<td>Phí XL</td>" +
                "<td>Phí LK</td>" +
                "<td>Phí ĐG</td>" +
                "<td>Phí XL</td>" +

                "<td>Phí VC</td>" +
                "<td>Phí CoD</td>" +
                "<td>Phí BH</td>" +
                "<td>Phí CH</td>" +

                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;
            var data = angular.copy($scope.frm);
            Order.ListOrder($scope.currentPage,data,'export').then(function (result) {
                if(!result.data.error){
                    var city        = result.data.city;
                    var district    = result.data.district;
                    var user        = result.data.user;

                    angular.forEach(result.data.data, function(value) {
                        html+= "<tr>" +
                            "<td>"+  i++ +"</td>" +
                            "<td>"+  (value.time_accept  > 0 ? $filter('date')(value.time_accept*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_pickup  > 0 ? $filter('date')(value.time_pickup*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_success  > 0 ? $filter('date')(value.time_success*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  value.tracking_code +"</td>" +
                            "<td>"+  value.domain +"</td>" +
                            "<td>"+  value.warehouse +"</td>" +
                            "<td>"+  value.order_code +"</td>" +
                            "<td>"+  (($scope.list_courier[1*value.courier_id]) ? $scope.list_courier[1*value.courier_id]['name'] : '') +"</td>" +
                            "<td>"+  value.courier_tracking_code +"</td>" +
                            "<td>"+  (($scope.list_service[1*value.service_id]) ? $scope.list_service[1*value.service_id]['name'] : '') +"</td>" +
                            "<td>"+  ((value.courier_service) ? value.courier_service : '') +"</td>" +

                            "<td>"+  (($scope.list_status[1*value.status]) ? $scope.list_status[1*value.status] : '') +"</td>" +

                            "<td>"+  $filter('number')(value.total_weight, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.total_quantity, 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? value.order_fulfillment.size : '') +"</td>" +

                            "<td>"+  ((user[1*value.from_user_id])           ? user[1*value.from_user_id]['email']       : '') +"</td>" +
                            "<td>"+  ((city[1*value.from_city_id])           ? city[1*value.from_city_id]                : '') +"</td>" +
                            "<td>"+  ((district[1*value.from_district_id])   ? district[1*value.from_district_id]        : '') +"</td>" +

                            "<td>"+  ((city[1*value.to_city_id])             ? city[1*value.to_city_id]                  : '') +"</td>" +
                            "<td>"+  ((district[1*value.to_district_id])     ? district[1*value.to_district_id]        : '') +"</td>" +

                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.sc_plk, 0) : 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.sc_pdg, 0) : 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.sc_pxl, 0) : 0) +"</td>" +

                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.sc_discount_plk, 0) : 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.sc_discount_pdg, 0) : 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.sc_discount_pxl, 0) : 0) +"</td>" +

                            "<td>"+  $filter('number')(value.order_detail.sc_pvc, 0) +"</td>" +
                            "<td>"+  (([66,67].indexOf(1*value.status) == -1) ? $filter('number')(value.order_detail.sc_cod, 0) : 0) +"</td>" +
                            "<td>"+  (([66,67].indexOf(1*value.status) == -1) ? $filter('number')(value.order_detail.sc_pbh, 0) : 0) +"</td>" +
                            "<td>"+  ((value.status == 66) ? $filter('number')(value.order_detail.sc_pch, 0) : 0) +"</td>" +
                            "<td>"+  $filter('number')(value.order_detail.sc_pvk, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.order_detail.sc_remote, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.order_detail.sc_clearance, 0) +"</td>" +

                            "<td>"+  $filter('number')(value.order_detail.sc_discount_pvc, 0)                   +"</td>" +
                            "<td>"+  (([66,67].indexOf(1*value.status) == -1) ? $filter('number')(value.order_detail.sc_discount_cod, 0) : 0) +"</td>" +

                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.historical_plk, 0) : 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.historical_pdg, 0) : 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.historical_pxl, 0) : 0) +"</td>" +

                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.historical_discount_plk, 0) : 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.historical_discount_pdg, 0) : 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.historical_discount_pxl, 0) : 0) +"</td>" +

                            "<td>"+  $filter('number')(value.order_detail.hvc_pvc, 0) +"</td>" +
                            "<td>"+  (([66,67].indexOf(1*value.status) == -1) ? $filter('number')(value.order_detail.hvc_cod, 0) : 0) +"</td>" +
                            "<td>"+  (([66,67].indexOf(1*value.status) == -1) ? $filter('number')(value.order_detail.hvc_pbh, 0) : 0) +"</td>" +
                            "<td>"+  ((value.status == 66) ? $filter('number')(value.order_detail.hvc_pch, 0) : 0) +"</td>" +

                            "<td>"+  (([66,67].indexOf(1*value.status) == -1) ? $filter('number')(value.order_detail.money_collect, 0) : 0) +"</td></tr>";
                    });

                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "Bao_cao_hang_van_chuyen.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
        }


        $scope.exportExcelItem = function(){
            $scope.refresh('export');
            $scope.waiting_export   = true;

            var html =
                "<table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Báo cáo theo item</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>TG Lưu kho</th>" +
                "<th rowspan='2'>TG Đóng gói</th>" +
                "<th rowspan='2'>Mã vận đơn</th>" +
                "<th rowspan='2'>UID</th>" +
                "<th colspan='2'>Phí Kho</th>" +
                "<th colspan='2'>Giảm giá Phí Kho</th>" +
                "<th colspan='2'>Phí Kho(NCC)</th>" +
                "<th colspan='2'>Giảm giá Phí Kho(NCC)</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Phí LK</td>" +
                "<td>Phí XL</td>" +
                "<td>Phí LK</td>" +
                "<td>Phí XL</td>" +
                "<td>Phí LK</td>" +
                "<td>Phí XL</td>" +
                "<td>Phí LK</td>" +
                "<td>Phí XL</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;
            var data = angular.copy($scope.frm);
            data.type = 3;
            Order.ListOrder($scope.currentPage,data,'export').then(function (result) {
                if(!result.data.error){
                    angular.forEach(result.data.data, function(value) {
                        if(value.__get_detail != undefined && value.__get_detail != []){
                            angular.forEach(value.__get_detail, function(item) {
                                html+= "<tr>" +
                                    "<td>"+  i++ +"</td>" +
                                    "<td>"+  (item.time_stocked  > 0 ? $filter('date')(item.time_stocked*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                                    "<td>"+  (item.time_packed  > 0 ? $filter('date')(item.time_packed*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                                    "<td>"+  value.tracking_code +"</td>" +
                                    "<td>"+  item.uid +"</td>" +
                                    "<td>"+  $filter('number')(item.warehouse_fee, 0) +"</td>" +
                                    "<td>"+  $filter('number')(item.handling_fee, 0) +"</td>" +
                                    "<td>"+  $filter('number')(item.discount_warehouse, 0) +"</td>" +
                                    "<td>"+  $filter('number')(item.discount_handling, 0) +"</td>" +

                                    "<td>"+  $filter('number')(item.historical_warehouse_fee, 0) +"</td>" +
                                    "<td>"+  $filter('number')(item.historical_handling_fee, 0) +"</td>" +

                                    "<td>"+  $filter('number')(item.historical_discount_warehouse, 0) +"</td>" +
                                    "<td>"+  $filter('number')(item.historical_discount_handling, 0) +"</td></tr>";
                            })
                        }
                    });

                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "Bao_cao_theo_item.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
        }
    }
]);
