'use strict';

angular.module('app').controller('WareHousePackageErrorCtrl', ['$scope', '$filter', 'Warehouse',
 	function($scope, $filter, Warehouse) {
        $scope.waiting              = false;
        $scope.waiting_export       = false;
        $scope.item_stt             = 0;
        $scope.totalItems           = 0;
        $scope.time                 = {create_start : new Date(date.getFullYear(), date.getMonth(), date.getDate())};
        $scope.check_box            = [];
        $scope.check_box_status     = [];
        $scope.frm                  = {type_process : 13, group : 6};
        $scope.list_data            = {};
        $scope.group                = {};

        $scope.__get_list_pipe_status(6,13);

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
                $scope.frm.package_status          = $scope.check_box_status.toString();
            }else{
                $scope.frm.package_status          =    [];
            }

            if(cmd != 'export'){
                $scope.list_data            = {};
                $scope.group                = {};
                $scope.waiting              = true;
            }

        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.refresh('');
            Warehouse.packed_error_size($scope.currentPage,$scope.frm).then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.group            = result.data.count_group;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting  = false;
            });
            return;
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
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh Sách Đóng Gói - Sai kích thước</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th colspan='2'>Thời gian</th>" +
                "<th colspan='6'>Mã</th>" +
                "<th rowspan='2'>Kho</th>" +
                "<th colspan='3'>Khách hàng</th>" +
                "<th colspan='2'>Sản phẩm</th>" +
                "<td>Trạng thái</td>" +
                "</tr>" +

                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Tạo Package</td>" +
                "<td>Tạo PutAway</td>" +
                "<td>Package</td>" +
                "<td>PutAway</td>" +
                "<td>UID</td>" +
                "<td>SKU</td>" +
                "<td>ORDER</td>" +
                "<td>SC</td>" +
                "<td>FullName</td>" +
                "<td>Email</td>" +
                "<td>Phone</td>" +
                "<td>Sản phẩm</td>" +
                "<td>Size</td>" +
                "<td>Size Đóng gói</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            Warehouse.packed_error_size($scope.currentPage,$scope.frm,'export').then(function (result) {
                if(!result.data.error){
                    angular.forEach(result.data.data, function(value) {
                        html+= "<tr>" +
                            "<td>"+ i++ +"</td>" +
                            "<td>"+ value.create +"</td>" +
                            "<td>"+ ((value.__get_putaway != undefined) ? value.__get_putaway.create_time : "") +"</td>" +

                            "<td>"+ value.package_code +"</td>" +
                            "<td>"+ ((value.__get_putaway != undefined) ? value.__get_putaway.put_away_code : "") +"</td>" +

                            "<td>"+ value.uid +"</td>" +
                            "<td>"+ '_'+value.sku +"</td>" +
                            "<td>"+ value.order_number +"</td>" +
                            "<td>"+ value.tracking_code +"</td>" +

                            "<td>"+ (($scope.warehouse[value.warehouse] != undefined) ? $scope.warehouse[value.warehouse]['name'] : "") +"</td>" +
                            "<td>"+ ((value.__get_product != undefined && value.__get_product.__get_user != undefined) ? value.__get_product.__get_user.fullname : '') +"</td>" +
                            "<td>"+ ((value.__get_product != undefined && value.__get_product.__get_user != undefined) ? value.__get_product.__get_user.email : '') +"</td>" +
                            "<td>"+ ((value.__get_product != undefined && value.__get_product.__get_user != undefined) ? '_'+value.__get_product.__get_user.phone : '') +"</td>" +

                            "<td>"+ ((value.__get_product != undefined && value.__get_product) ? value.__get_product.name : '') +"</td>" +
                            "<td>"+ ((value.__get_history != undefined) ? value.__get_history[0]['box'] : '') +"</td>" +
                            "<td>"+ ((value.__get_package != undefined) ? value.__get_package.size : '') +"</td>" +

                            "<td>"+ ((value.__get_package != undefined && $scope.package_status[value.__get_package.status] != undefined) ? $scope.package_status[value.__get_package.status]['name'] : '') +"</td>" +
                            "</tr>"
                        ;
                    });
                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "danh_sach_dong_goi_sai_kich_thuoc.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
            return;
        }
    }
]);