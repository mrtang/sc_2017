'use strict';

angular.module('app').controller('WareHouseShipmentLostCtrl', ['$scope', '$filter', 'Warehouse',
 	function($scope, $filter, Warehouse) {
        $scope.waiting              = false;
        $scope.waiting_export       = false;
        $scope.item_stt             = 0;
        $scope.totalItems           = 0;
        $scope.time                 = {create_start : new Date(date.getFullYear(), date.getMonth(), date.getDate())};
        $scope.check_box            = [];
        $scope.check_box_status     = [];
        $scope.frm                  = {type_process : 13, group : 5};
        $scope.list_data            = {};
        $scope.total_group          = {};

        $scope.__get_list_pipe_status(5,13);

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
            if($scope.time.create_start != undefined && $scope.time.create_start != ''){
                $scope.frm.create_start   = +Date.parse($scope.time.create_start)/1000;
            }else{
                $scope.frm.create_start   = 0;
            }
            if($scope.time.create_end != undefined && $scope.time.create_end != ''){
                $scope.frm.create_end     = +Date.parse($scope.time.create_end)/1000 + 86399;
            }else{
                $scope.frm.create_end     = 0;
            }


            if($scope.check_box != undefined && $scope.check_box != []){
                $scope.frm.pipe_status  = $scope.check_box.toString();
            }else{
                $scope.frm.pipe_status       = [];
            }

            if($scope.check_box_status != undefined && $scope.check_box_status.length > 0){
                $scope.frm.list_status          = $scope.check_box_status.toString();
            }else{
                $scope.frm.list_status          =    [];
            }

            if(cmd != 'export'){
                $scope.list_data            = {};
                $scope.waiting              = true;
            }

        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.refresh('');
            Warehouse.shipment_missing($scope.currentPage,$scope.frm).then(function (result) {
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
            Warehouse.shipment_lost_count_group($scope.frm).then(function (result) {
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

        $scope.exportExcel = function(){
            $scope.refresh('export');
            $scope.waiting_export   = true;
            var html =
                "<table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh Sách Nhập Kho Thiếu</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th colspan='4'>Thời gian</th>" +
                "<th colspan='4'>Mã</th>" +
                "<th rowspan='2'>Kho</th>" +
                "<th colspan='3'>Khách hàng</th>" +
                "<th rowspan='2'>Sản phẩm</th>" +
                "<th rowspan='2'>Thời gian lưu kho (h)</th>" +
                "<th colspan='2'>Trạng thái</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Tạo</td>" +
                "<td>Nhập kho</td>" +
                "<td>Lấy hàng</td>" +
                "<td>Đóng gói</td>" +
                "<td>Shipment</td>" +
                "<td>UID</td>" +
                "<td>SKU</td>" +
                "<td>BINID</td>" +
                "<td>FullName</td>" +
                "<td>Email</td>" +
                "<td>Phone</td>" +
                "<td>Shipment</td>" +
                "<td>UID</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            Warehouse.shipment_missing(1,$scope.frm,'export').then(function (result) {
                if(!result.data.error){
                    var time_stock = 0;
                    angular.forEach(result.data.data, function(value) {
                        time_stock = $scope.get_time_stock(value);
                        html+= "<tr>" +
                            "<td>"+ i++ +"</td>" +
                            "<td>"+ value.created +"</td>" +
                            "<td>"+ value.update_stocked +"</td>" +
                            "<td>"+ value.update_picked +"</td>" +
                            "<td>"+ value.update_packed +"</td>" +
                            "<td>"+ ((value.__shipment != undefined) ? value.__shipment.request_code : '') +"</td>" +
                            "<td>"+ value.serial_number +"</td>" +
                            "<td>"+ value.sku +"</td>" +
                            "<td>"+ ((value.__putaway != undefined) ? value.__putaway.bin : '') +"</td>" +
                            "<td>"+ ((value.__shipment != undefined &&  $scope.warehouse[value.__shipment.warehouse] != undefined) ? $scope.warehouse[value.__shipment.warehouse]['name'] : "") +"</td>" +
                            "<td>"+ ((value.__get_user != undefined) ? value.__get_user.fullname : '') +"</td>" +
                            "<td>"+ ((value.__get_user != undefined) ? value.__get_user.email : '') +"</td>" +
                            "<td>"+ ((value.__get_user != undefined) ? value.__get_user.phone : '') +"</td>" +
                            "<td>"+ ((value.__product != undefined) ? value.__product.name : '') +"</td>" +
                            "<td>"+ $filter('number')(time_stock, 0) +"</td>" +
                            "<td>"+ ((value.__shipment != undefined && $scope.shipment_status[value.__shipment.status] != undefined) ? $scope.shipment_status[value.__shipment.status]['name'] : '') +"</td>" +
                            "<td>"+ (($scope.warehouse_item_status[value.status] != undefined) ? $scope.warehouse_item_status[value.status]['name'] : '') +"</td>" +
                            "</tr>"
                        ;
                    });
                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "danh_sach_nhap_kho_thieu.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
            return;
        }
    }
]);