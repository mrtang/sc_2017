'use strict';

angular.module('app').controller('WareHousePickupCtrl', ['$scope', '$filter', 'Config_Status', 'Warehouse',
 	function($scope, $filter, Config_Status, Warehouse) {
        $scope.waiting              = false;
        $scope.waiting_export       = false;

        $scope.item_stt             = 0;
        $scope.totalItems           = 0;
        $scope.time                 = {accept_start : new Date(date.getFullYear(), date.getMonth() - 3, 1)};
        $scope.check_box            = [];
        $scope.check_box_status     = [];
        $scope.frm                  = {type_process : 13, group : 4, domain: 'boxme.vn', location: 4};

        $scope.list_data            = {};
        $scope.total_group          = {};

        $scope.__get_list_pipe_status(4,13);

        if($scope.warehouse_group_order_status[43] == undefined){
            $scope.warehouse_group_order_status[43]   = Config_Status.group_status[43];
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
            $scope.frm.accept_start             = +Date.parse($scope.time.accept_start)/1000;

            if($scope.check_box != undefined && $scope.check_box != []){
                $scope.frm.pipe_status  = $scope.check_box.toString();
            }else{
                $scope.frm.pipe_status       = [];
            }

            if($scope.check_box_status != undefined && $scope.check_box_status.length > 0){
                $scope.frm.list_status          = $scope.check_box_status.toString();
            }else{
                $scope.frm.list_status          =    $scope.warehouse_group_order_status[43].toString();
            }

            if(cmd != 'export'){
                $scope.list_data            = {};
                $scope.waiting              = true;
            }

        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.refresh('');
            Warehouse.pickup_slow($scope.currentPage,$scope.frm, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting  = false;
            });
            return;
        }

        $scope.setCountGroup    = function(){
            $scope.total_group  = [];
            Warehouse.pickup_slow_count_group($scope.frm).then(function (result) {
                if(!result.data.error){
                    $scope.total_group      = result.data.data;
                }
            });
        }

        $scope.ChangeTab    = function(warehouse){
            $scope.frm.warehouse  = warehouse;
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
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh Sách Lấy Hàng Chậm</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th>STT</th>" +
                "<th>Thời gian duyệt</th>" +
                "<th>Mã đơn hàng</th>" +
                "<th>Sản phẩm</th>" +
                "<th>Số lượng</th>" +
                "<th>Trạng thái</th>" +
                "<th>Kho</th>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            Warehouse.pickup_slow(1,$scope.frm, 'export').then(function (result) {
                if(!result.data.error){
                    angular.forEach(result.data.data, function(value) {
                        html+= "<tr>" +
                            "<td>"+ i++ +"</td>" +
                            "<td>"+ (value.time_accept  > 0 ? $filter('date')(value.time_accept*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+ value.tracking_code +"</td>" +
                            "<td>"+ value.product_name +"</td>" +
                            "<td>"+ value.total_quantity +"</td>" +
                            "<td>"+ $scope.list_status[value.status] +"</td>" +
                            "<td>"+ value.warehouse +"</td>" +
                            "</tr>"
                        ;
                    });
                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "danh_sach_lay_hang_cham.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
            return;
        }
    }
]);