'use strict';

//Provider report
angular.module('app').controller('OrderReportCtrl', ['$scope', '$filter', '$rootScope', 'Order', 'Config_Status',
 	function($scope, $filter, $rootScope, Order, Config_Status) {
    // config
        $scope.list_type            = {
            1 : 'Vận đơn CoD',
            2 : 'Vận đơn không CoD'
        };
        
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.totalItems           = 0;
        
        $scope.time                 = {accept_start : new Date(date.getFullYear(), date.getMonth(), 1)};
        $scope.frm                  = {courier: '', verify_money_collect : 0,type : 2, global: 0};
        $scope.check_box            = [];
        $scope.list_color           = Config_Status.order_color;
        $scope.waiting_export       = false;
       
        
        $scope.list_data            = {};
        $scope.data_sum             = {};
        $scope.total_all            = 0;
        $scope.total_group          = {};
        $scope.check_box_status     = [];
        $scope.tab                  = 0;

        $scope.waiting              = false;

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
                $scope.time_accept_end_open = true;
            }else if(type == "time_success_start"){
                $scope.time_success_start_open = true;
            }else if(type == "time_success_end"){
                $scope.time_success_end_open = true;
            }else if(type == "time_pickup_start"){
                $scope.time_pickup_start_open = true;
            }else if(type == "time_pickup_end"){
                $scope.time_pickup_end_open = true;
            }
        };

        // action

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
            $scope.data_sum     = [];
            var data = angular.copy($scope.frm);
            Order.CountGroup(data, 'status').then(function (result) {
                if(!result.data.error){
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
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Báo cáo vận đơn</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>TG Tạo</th>" +
                "<th rowspan='2'>TG Duyệt</th>" +
                "<th rowspan='2'>TG Lấy Hàng</th>" +
                "<th rowspan='2'>TG Giao Hàng</th>" +
                "<th rowspan='2'>Mã vận đơn</th>" +
                "<th rowspan='2'>Bảng kê</th>" +
                "<th rowspan='2'>Domain</th>" +
                "<th rowspan='2'>Mã Order</th>" +
                "<th rowspan='2'>HVC</th>" +
                "<th rowspan='2'>Mã HVC</th>" +
                "<th rowspan='2'>Dịch Vụ</th>" +
                "<th rowspan='2'>Trạng thái</th>" +
                "<th colspan='8'>Gửi</th>" +
                "<th colspan='8'>Nhận</th>" +
                "<th colspan='5'>Sản phẩm</th>" +

                "<th colspan='3'>Phí Kho</th>" +
                "<th colspan='3'>Giảm giá Phí Kho</th>" +
                "<th colspan='7'>Phí</th>" +
                "<th colspan='2'>Giảm giá</th>" +

                "<th rowspan='2'>Tổng tiền thu hộ</th>" +
                "<th rowspan='2'>Thanh Toán</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Họ tên</td>" +
                "<td>Email</td>" +
                "<td>SDT</td>" +
                "<td>KHTT</td>"+
                "<td>Tỉnh/Thành Phố</td>" +
                "<td>Quận/Huyện</td>" +
                "<td>Phường/Xã</td>" +
                "<td>Địa chỉ</td>" +
                "<td>Họ tên</td>" +
                "<td>Email</td>" +
                "<td>SDT</td>" +
                "<td>Tỉnh/Thành Phố</td>" +
                "<td>Quận/Huyện</td>" +
                "<td>Phường/Xã</td>" +
                "<td>Địa chỉ</td>" +
                "<th>Mã BC</th>" +

                "<td>Tên</td>" +
                "<td>Giá trị</td>" +
                "<td>K Lượng</td>" +
                "<td>Số lượng</td>" +
                "<td>K Thước</td>" +

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

                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;
            var data = angular.copy($scope.frm);
            Order.ListOrder($scope.currentPage,data,'export').then(function (result) {
                if(!result.data.error){
                    var to_address  = result.data.to_address;
                    var city        = result.data.city;
                    var district    = result.data.district;
                    var ward        = result.data.ward;
                    var user        = result.data.user;
                    var user_loyalty    = result.data.user_loyalty;
                    var user_info   = result.data.user_info;
                    var payment     = 2;

                    angular.forEach(result.data.data, function(value) {
                        payment    = (user_info[1*value.from_user_id]) ? 1*user_info[1*value.from_user_id]['priority_payment'] : 2;
                        html+= "<tr>" +
                            "<td>"+  i++ +"</td>" +
                            "<td>"+  (value.time_accept  > 0 ? $filter('date')(value.time_create*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_accept  > 0 ? $filter('date')(value.time_accept*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_pickup  > 0 ? $filter('date')(value.time_pickup*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_success  > 0 ? $filter('date')(value.time_success*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  value.tracking_code +"</td>" +
                            "<td>"+  value.verify_id +"</td>" +
                            "<td>"+  value.domain +"</td>" +
                            "<td>"+  value.order_code +"</td>" +
                            "<td>"+  (($scope.list_courier[1*value.courier_id]) ? $scope.list_courier[1*value.courier_id]['name'] : '') +"</td>" +
                            "<td>"+  value.courier_tracking_code +"</td>" +
                            "<td>"+  (($scope.list_service[1*value.service_id]) ? $scope.list_service[1*value.service_id]['name'] : '') +"</td>" +
                            "<td>"+  (($scope.list_status[1*value.status]) ? $scope.list_status[1*value.status] : '') +"</td>" +

                            "<td>"+  ((user[1*value.from_user_id])           ? user[1*value.from_user_id]['fullname']    : '') +"</td>" +
                            "<td>"+  ((user[1*value.from_user_id])           ? user[1*value.from_user_id]['email']       : '') +"</td>" +
                            "<td>"+  ((user[1*value.from_user_id])           ? user[1*value.from_user_id]['phone']       : '') +"</td>" +
                            "<td>"+  ((user_loyalty[1*value.from_user_id] != undefined && $scope.sc_loyalty_level[user_loyalty[1*value.from_user_id]['level']] != undefined)   ? $scope.sc_loyalty_level[user_loyalty[1*value.from_user_id]['level']]['name']       : '') +"</td>" +

                            "<td>"+  ((city[1*value.from_city_id])           ? city[1*value.from_city_id]         : '') +"</td>" +
                            "<td>"+  ((district[1*value.from_district_id])   ? district[1*value.from_district_id]        : '') +"</td>" +
                            "<td>"+  ((ward[1*value.from_ward_id])           ? ward[1*value.from_ward_id]                : '') +"</td>" +
                            "<td>"+  value.from_address +"</td>" +

                            "<td>"+  value.to_name +"</td>" +
                            "<td>"+  value.to_email +"</td>" +
                            "<td>"+  value.to_phone +"</td>" +
                            "<td>"+  ((city[1*value.to_city_id])           ? city[1*value.to_city_id]         : '') +"</td>" +
                            "<td>"+  ((district[1*value.to_district_id])   ? district[1*value.to_district_id]        : '') +"</td>" +
                            "<td>"+  (((to_address[1*value.to_address_id]) && ward[to_address[1*value.to_address_id]['ward_id']])            ? ward[to_address[1*value.to_address_id]['ward_id']]            : '') +"</td>" +
                            "<td>"+  ((to_address[1*value.to_address_id])    ? to_address[1*value.to_address_id]['address']                  : '') +"</td>" +
                            "<td>"+  ((value.__post_office != undefined) ? value.__post_office.to_postoffice_code : "") +"</td>" +

                            "<td>"+  value.product_name +"</td>" +
                            "<td>"+  $filter('number')(value.total_amount, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.total_weight, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.total_quantity, 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? value.order_fulfillment.size : 0) +"</td>" +

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

                            "<td>"+  (([66,67].indexOf(1*value.status) == -1) ? $filter('number')(value.order_detail.money_collect, 0) : 0) +"</td>" +
                            "<td>"+  ((payment == 1)       ? 'Vimo' : 'Ngân lượng') +"</td></tr>";
                    });

                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "Bao_cao_khach_hang.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
        }
    }
]);
