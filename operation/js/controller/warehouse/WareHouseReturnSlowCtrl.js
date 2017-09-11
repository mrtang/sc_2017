'use strict';

angular.module('app').controller('WareHouseReturnSlowCtrl', ['$scope', '$filter', 'Warehouse',
 	function($scope, $filter, Warehouse) {
        $scope.waiting              = false;
        $scope.waiting_export       = false;
        $scope.item_stt             = 0;
        $scope.totalItems           = 0;
        $scope.time                 = {accept_start : new Date(date.getFullYear(), date.getMonth(), 1)};
        $scope.check_box            = [];
        $scope.frm                  = {type_process : 13, group : 8,list_status : '66,67,22'};
        $scope.list_data            = {};
        $scope.list_order           = {};
        $scope.total_group          = {};

        $scope.__get_list_pipe_status(8,13);

        $scope.refresh = function(cmd){
            $scope.frm.time_success_end   = Date.parse(new Date)/1000 - 86400;

            if($scope.time.accept_start != undefined && $scope.time.accept_start != ''){
                $scope.frm.accept_start   = +Date.parse($scope.time.accept_start)/1000;
            }else{
                $scope.frm.accept_start   = 0;
            }
            if($scope.time.accept_end != undefined && $scope.time.accept_end != ''){
                $scope.frm.accept_end     = +Date.parse($scope.time.accept_end)/1000 + 86399;
            }else{
                $scope.frm.accept_end     = 0;
            }

            if($scope.time.packed_start != undefined && $scope.time.packed_start != ''){
                $scope.frm.packed_start   = +Date.parse($scope.time.packed_start)/1000;
            }else{
                $scope.frm.packed_start   = 0;
            }
            if($scope.time.packed_end != undefined && $scope.time.packed_end != ''){
                $scope.frm.packed_end     = +Date.parse($scope.time.packed_end)/1000 + 86399;
            }else{
                $scope.frm.packed_end     = 0;
            }

            if($scope.check_box != undefined && $scope.check_box != []){
                $scope.frm.pipe_status      = $scope.check_box.toString();
            }else{
                $scope.frm.pipe_status  = '';
            }

            if(cmd != 'export'){
                $scope.list_data            = {};
                $scope.list_order           = {};
                $scope.waiting              = true;
            }

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

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.refresh('');
            Warehouse.return_slow($scope.currentPage,$scope.frm, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.list_order       = result.data.list_order;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting  = false;
            });
            return;
        }

        $scope.setCountGroup    = function(){
            $scope.total_group  = [];
            Warehouse.return_slow_count_group($scope.frm).then(function (result) {
                if(!result.data.error){
                    $scope.total_group      = result.data.data;
                }
            });
        }

        $scope.ChangeTab    = function(warehouse){
            $scope.frm.warehouse  = warehouse;
            $scope.setPage(1);
        }

        //$scope.setPage(1);

        $scope.ExportSku    = function(){
            $scope.refresh('export');
            return $scope.exportExcelSku($scope.frm, $scope.waiting_export);
        }

        $scope.exportExcel = function(){
            $scope.refresh('export');
            $scope.waiting_export   = true;
            var html =
            "<meta http-equiv='content-type' content='application/vnd.ms-excel; charset=UTF-8'><table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh Sách Hoàn Chậm</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th>STT</th>" +
                "<th>Hoàn thành công</th>" +
                "<th>Mã đơn hàng</th>" +
                "<th>Hãng vận chuyển</th>" +
                "<th>Mã đóng gói</th>" +
                "<th>Mã lấy hàng</th>" +
                "<th>UID</th>" +
                "<th>SKU</th>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            Warehouse.return_slow(1,$scope.frm, 'export').then(function (result) {
                var list_order;
                var list_courier;
                var courier;
                if(!result.data.error){
                    list_order = result.data.list_order;
                    list_courier = result.data.list_courier;
                    var time_accept = 0;
                    angular.forEach(result.data.data, function(value) {
                        time_accept = 0;
                        courier     = '';

                        if(list_order[value.order_number] != undefined && list_order[value.order_number] > 0){
                            time_accept = list_order[value.order_number];
                        }else if(list_order[value.tracking_code] != undefined && list_order[value.tracking_code] > 0){
                            time_accept = list_order[value.tracking_code];
                        }

                        if(list_courier[value.tracking_code] != undefined && $scope.courier[list_courier[value.tracking_code]] != undefined){
                            courier = $scope.courier[list_courier[value.tracking_code]];
                        }else if(list_courier[value.order_number] != undefined && $scope.courier[list_courier[value.order_number]] != undefined){
                            courier = $scope.courier[list_courier[value.order_number]];
                        }

                        html+= "<tr>" +
                            "<td>"+ i++ +"</td>" +
                            "<td>"+ ((time_accept > 0) ? $filter('date')(time_accept*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+ value.tracking_code +"</td>" +
                            "<td>"+ courier +"</td>" +
                            "<td>"+ value.package_code +"</td>" +
                            "<td>"+ value.pickup_code +"</td>" +
                            "<td>"+ value.uid +"</td>" +
                            "<td>"+ value.sku +"</td>" +
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
    }
]);