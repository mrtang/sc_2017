'use strict';

angular.module('app').controller('ReturnCtrl', ['$scope', '$rootScope', '$filter', 'Order', 'Config_Status', 'Base',
    function($scope, $rootScope, $filter, Order, Config_Status, Base) {

        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;
        $scope.total_group          = [];
        $scope.total_all            = 0;
        $scope.time                 = {accept_start : new Date(date.getFullYear(), date.getMonth(), 1)};
        $scope.frm                  = {group : 36, global: 0, list_status : ''};

        $scope.list_data            = {};
        $scope.list_location        = {'list_city': {},'list_district': {},'list_ward': {},'list_to_address': {},'list_from_address': {}};
        $scope.list_post_office     = {};

        $scope.list_color           = Config_Status.order_color;
        $scope.list_pipe_status     = {};
        $scope.pipe_status          = {};
        $scope.pipe_limit           = 0;
        $scope.pipe_priority        = {};
        $scope.check_box            = [];
        $scope.list_reponse         = {};
        $scope.tag_color            = Config_Status.tag_color;

        $scope.waiting              = false;

        if($scope.group_order_status[36] == undefined){
            $scope.group_order_status[36]   = Config_Status.group_status[36];
        }

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "time_accept_start_open"){
                $scope.time_accept_start_open = true;
            }else if(type == "time_accept_end"){
                $scope.time_accept_end_open = true;
            }
        };

        $scope.$watch('frm.global', function(newVal, oldVal) {
            if(newVal == 1){
                $scope.frm.to_city = 0;
                $scope.frm.to_district = 0;
            }
        });

        Base.PipeStatus(36, 1).then(function (result) {
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

        $scope.refresh = function(cmd){
            if($scope.frm.list_status == undefined || $scope.frm.list_status.length  == 0){
                $scope.frm.list_status  = $scope.group_order_status[36].toString();
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

            if($scope.check_box != undefined && $scope.check_box != []){
                $scope.frm.pipe_status      = $scope.check_box.toString();
            }else{
                $scope.frm.pipe_status  = '';
            }

            if(cmd != 'export'){
                $scope.list_data            = {};
                $scope.list_post_office     = {};
                $scope.list_location        = {'list_city': {},'list_district': {},'list_ward': {},'list_to_address': {},'list_from_address': {}};
                $scope.waiting              = true;
            }

        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.refresh('');
            Order.GetOrder($scope.currentPage,$scope.frm, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);

                    $scope.list_location.list_city              = result.data.list_city;
                    $scope.list_location.list_district          = result.data.list_district;
                    $scope.list_location.list_ward              = result.data.list_ward;

                    $scope.list_location.list_to_address        = result.data.list_to_address;
                    $scope.list_location.list_from_address      = result.data.list_from_address;

                    $scope.list_post_office                     = result.data.list_postoffice;
                }
                $scope.waiting  = false;
            });
            return;
        }

        $scope.setCountGroup    = function(){
            $scope.total_all    = 0;
            $scope.total_group  = [];
            $scope.list_reponse = {};
            Order.CountGroupOrder($scope.frm, 'status').then(function (result) {
                if(!result.data.error){
                    $scope.total_all        = result.data.total;
                    $scope.total_group      = result.data.data;
                }
            });
        }

        $scope.getReponse   = function(item){
            item.waiting = true;
            if($scope.list_reponse[item.id] == undefined || $scope.list_reponse[item.id].length == 0){
                Order.StatusOrder(item.id, 67).then(function (result) {
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

        $scope.ChangeTab    = function(tab){
            if(tab == 'ALL'){
                $scope.frm.list_status  = '';
            }else{
                $scope.frm.list_status   = tab;
            }
            $scope.setPage(1);
        }

        $scope.exportExcel = function(){
            $scope.refresh('export');
            $scope.waiting_export   = true;

            var html =
                "<meta http-equiv='content-type' content='application/vnd.ms-excel; charset=UTF-8'><table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh sách chuyển hoàn</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>TG Duyệt</th>" +
                "<th rowspan='2'>TG Lấy Hàng</th>" +
                "<th rowspan='2'>TG Hoàn TC</th>" +
                "<th rowspan='2'>TG CXNH</th>" +
                "<th rowspan='2'>TG XNCH</th>" +
                "<th rowspan='2'>Mã vận đơn</th>" +
                "<th rowspan='2'>Mã đơn hàng</th>" +
                "<th rowspan='2'>Mã đối soát</th>" +
                "<th rowspan='2'>Domain</th>" +
                "<th rowspan='2'>Kho</th>" +
                "<th rowspan='2'>HVC</th>" +
                "<th rowspan='2'>Mã HVC</th>" +
                "<th rowspan='2'>Dịch Vụ</th>" +
                "<th rowspan='2'>Trạng thái</th>" +
                "<th colspan='9'>Gửi</th>" +
                "<th colspan='9'>Nhận</th>" +
                "<th colspan='5'>Thông tin sản phẩm</th>" +
                "<th colspan='7'>Chi phí vận chuyển</th>" +
                "<th colspan='2'>Discount</th>" +
                "<th colspan='3'>Chi phí kho</th>" +
                "<th colspan='3'>Discount</th>" +
                "<th rowspan='2'>Tổng tiền thu hộ</th>" +
                "<th rowspan='2'>Lý do PTB</th>" +
                "<th rowspan='2'>Lý do XN Hoàn</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Họ tên</td>" +
                "<td>Email</td>" +
                "<td>SĐT</td>" +
                "<td>Quốc gia</td>" +
                "<td>Tỉnh/Thành Phố</td>" +
                "<td>Quận/Huyện</td>" +
                "<td>Phường/Xã</td>" +
                "<td>Địa chỉ</td>" +
                "<td>Mã Bưu Cục</td>" +

                "<td>Họ tên</td>" +
                "<td>Email</td>" +
                "<td>SĐT</td>" +
                "<td>Quốc gia</td>" +
                "<td>Tỉnh/Thành Phố</td>" +
                "<td>Quận/Huyện</td>" +
                "<td>Phường/Xã</td>" +
                "<td>Địa chỉ</td>" +
                "<td>Mã Bưu Cục</td>" +

                "<th>Tên SP</th>" +
                "<th>Tổng giá trị</th>" +
                "<th>Khối Lượng</th>" +
                "<th>Số Lượng</th>" +
                "<th>Kích Thước</th>" +

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
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;
            var data = angular.copy($scope.frm);
            Order.GetOrder($scope.currentPage,data,'export').then(function (result) {
                if(!result.data.error){
                    var city            = result.data.list_city;
                    var district        = result.data.list_district;
                    var ward            = result.data.list_ward;
                    var to_address      = result.data.list_to_address;
                    var from_address    = result.data.list_from_address;

                    var user        = result.data.user;

                    angular.forEach(result.data.data, function(value) {
                        var note                = '';
                        var time_rt             = '';
                        var time_accept_rt      = '';
                        var returning_reason    = '';

                        if(value.order_status != undefined){
                            angular.forEach(value.order_status, function(v) {
                                if([60,61].indexOf(1*v.status) != -1){
                                    if(v.status*1 == 60){
                                        time_rt    = v.time_create;
                                    }else{
                                        time_accept_rt = v.time_create;
                                        returning_reason += v.note+',';
                                    }
                                }else{
                                    note += v.note+',';
                                }

                            });
                        }

                        html+= "<tr>" +
                            "<td>"+  i++ +"</td>" +
                            "<td>"+  (value.time_accept  > 0 ? $filter('date')(value.time_accept*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_pickup  > 0 ? $filter('date')(value.time_pickup*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.time_success > 0 ? $filter('date')(value.time_success*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (time_rt  > 0 ? $filter('date')(time_rt*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (time_accept_rt  > 0 ? $filter('date')(time_accept_rt*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  value.tracking_code +"</td>" +
                            "<td>"+  value.order_code +"</td>" +
                            "<td>"+  value.verify_id +"</td>" +
                            "<td>"+  value.domain +"</td>" +
                            "<td>"+  ($scope.warehouse[value.warehouse] != undefined ? $scope.warehouse[value.warehouse].name : value.warehouse) +"</td>" +
                            "<td>"+  (($scope.courier[1*value.courier_id])      ? $scope.courier[1*value.courier_id] : '') +"</td>" +
                            "<td>"+  value.courier_tracking_code +"</td>" +
                            "<td>"+  (($scope.service[1*value.service_id])      ? $scope.service[1*value.service_id] : '') +"</td>" +
                            "<td>"+  (($scope.list_status[1*value.status])      ? $scope.list_status[1*value.status] : '') +"</td>" +

                            "<td>"+ ((from_address[value.from_address_id] != undefined)     ? from_address[value.from_address_id].user_name         : value.from_user.fullname) +"</td>" +
                            "<td>"+ (value.from_user.email != undefined                     ? value.from_user.email                                 : '') +"</td>" +
                            "<td>"+ ((from_address[value.from_address_id]  != undefined)    ? from_address[value.from_address_id].phone     : value.from_user.phone) +"</td>" +
                            "<td>"+ (($scope.country[1*value.from_country_id])  ? $scope.country[1*value.from_country_id]                   : '') +"</td>" +
                            "<td>"+ ((city[1*value.from_city_id])               ? city[1*value.from_city_id]                                : '') +"</td>" +
                            "<td>"+ ((district[1*value.from_district_id])       ? district[1*value.from_district_id]                        : '') +"</td>" +
                            "<td>"+ ((ward[1*value.from_ward_id])               ? ward[1*value.from_ward_id]                                : '') +"</td>" +
                            "<td>"+ value.from_address +"</td>" +
                            "<td>"+ ((value.__post_office != undefined && value.__post_office.from_postoffice_code != undefined)    ? value.__post_office.from_postoffice_code   : '') +"</td>" +

                            "<td>"+  value.to_name +"</td>" +
                            "<td>"+  value.to_email +"</td>" +
                            "<td>"+  value.to_phone +"</td>" +
                            "<td>"+ (($scope.country[1*value.to_country_id])    ? $scope.country[1*value.to_country_id]                     : '') +"</td>" +
                            "<td>"+ ((city[1*value.to_city_id])                 ? city[1*value.to_city_id]                                  : '') +"</td>" +
                            "<td>"+ ((district[1*value.to_district_id])         ? district[1*value.to_district_id]                          : '') +"</td>" +
                            "<td>"+ (((to_address[1*value.to_address_id]    != undefined)  && (ward[1*to_address[1*value.to_address_id].ward_id] != undefined))    ? ward[1*to_address[1*value.to_address_id].ward_id] : '') +"</td>" +
                            "<td>"+ ((to_address[1*value.to_address_id]     != undefined)   ? (to_address[1*value.to_address_id].address)    : '') +"</td>" +
                            "<td>"+ ((value.__post_office != undefined && value.__post_office.to_postoffice_code != undefined)      ? value.__post_office.to_postoffice_code        : '') +"</td>" +

                            "<td>"+  value.product_name +"</td>" +
                            "<td>"+  $filter('number')(value.total_amount, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.total_weight, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.total_quantity, 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? value.order_fulfillment.size : '') +"</td>" +

                            "<td>"+  $filter('number')(value.order_detail.sc_pvc, 0) +"</td>" +
                            "<td>"+  (([66,67].indexOf(1*value.status) == -1) ? $filter('number')(value.order_detail.sc_cod, 0) : 0) +"</td>" +
                            "<td>"+  (([66,67].indexOf(1*value.status) == -1) ? $filter('number')(value.order_detail.sc_pbh, 0) : 0) +"</td>" +
                            "<td>"+  ((value.status == 66) ? $filter('number')(value.order_detail.sc_pch, 0) : 0) +"</td>" +
                            "<td>"+  $filter('number')(value.order_detail.sc_pvk, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.order_detail.sc_remote, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.order_detail.sc_clearance, 0) +"</td>" +

                            "<td>"+  $filter('number')(value.order_detail.sc_discount_pvc, 0)                   +"</td>" +
                            "<td>"+  (([66,67].indexOf(1*value.status) == -1) ? $filter('number')(value.order_detail.sc_discount_cod, 0) : 0) +"</td>" +


                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.sc_plk, 0) : 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.sc_pdg, 0) : 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.sc_pxl, 0) : 0) +"</td>" +

                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.sc_discount_plk, 0) : 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.sc_discount_pdg, 0) : 0) +"</td>" +
                            "<td>"+  (value.order_fulfillment != undefined ? $filter('number')(value.order_fulfillment.sc_discount_pxl, 0) : 0) +"</td>" +

                            "<td>"+  (([66,67].indexOf(1*value.status) == -1) ? $filter('number')(value.order_detail.money_collect, 0) : 0) +"</td>" +
                            "<td>"+ note +"</td>" +
                            "<td>"+ returning_reason +"</td></tr>";
                    });

                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "Danh_sach_chuyen_hoan.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
        }
    }
]);
