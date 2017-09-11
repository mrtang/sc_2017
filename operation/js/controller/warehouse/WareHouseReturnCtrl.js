'use strict';

angular.module('app').controller('WareHouseReturnCtrl', ['$scope', 'Warehouse',
 	function($scope, Warehouse) {
        $scope.waiting              = false;
        $scope.waiting_export       = false;
        $scope.item_stt             = 0;
        $scope.total_group          = [];
        $scope.totalItems           = 0;
        $scope.time                 = {create_start : new Date(date.getFullYear(), date.getMonth(), 1)};
        $scope.check_box            = [];
        $scope.frm                  = {};
        $scope.list_data            = {};

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
                $scope.frm.list_status  = $scope.check_box.toString();
            }else{
                $scope.frm.list_status       = [];
            }

            if(cmd != 'export'){
                $scope.list_data            = {};
                $scope.inventory_excel      = {};
                $scope.waiting              = true;
            }

        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.refresh('');
            Warehouse.return($scope.currentPage,$scope.frm).then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                    $scope.inventory        = result.data.inventory;
                }
                $scope.waiting  = false;
            });
            return;
        }

        $scope.setCountGroup    = function(){
            $scope.total_group  = [];
            Warehouse.return_count_group($scope.frm).then(function (result) {
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
        //$scope.setCountGroup();

        $scope.exportExcel = function(){
            $scope.refresh('export');
            $scope.waiting_export   = true;
            var html =
            "<table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh Sách Return Item</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>TG Tạo</th>" +
                "<th colspan='4'>Mã</th>" +
                "<th rowspan='2'>Kho</th>" +
                "<th colspan='3'>Khách hàng</th>" +
                "<th rowspan='2'>Sản phẩm</th>" +
                "<th colspan='2'>Trạng thái</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>RC</td>" +
                "<td>Order</td>" +
                "<td>SC Code</td>" +
                "<td>UID</td>" +
                "<td>SKU</td>" +
                "<td>FullName</td>" +
                "<td>Email</td>" +
                "<td>Phone</td>" +
                "<td>RC</td>" +
                "<td>UID</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            Warehouse.return(1,$scope.frm, 'export').then(function (result) {
                if(!result.data.error){
                    angular.forEach(result.data.data, function(value, key) {
                        html+= "<tr>" +
                            "<td>"+ i++ +"</td>" +
                            "<td>"+ value.created +"</td>" +
                            "<td>"+ value.return_code +"</td>" +
                            "<td>"+ value.order_code +"</td>" +
                            "<td>"+ value.tracking_code +"</td>" +
                            "<td>"+ value.uid +"</td>" +
                            "<td>"+ value.sku +"</td>" +
                            "<td>"+ (($scope.warehouse[value.warehouse] != undefined) ? $scope.warehouse[value.warehouse]['name'] : "") +"</td>" +
                            "<td>"+ ((value.__get_seller_product != undefined && value.__get_seller_product.__get_user != undefined) ? value.__get_seller_product.__get_user.fullname : '') +"</td>" +
                            "<td>"+ ((value.__get_seller_product != undefined && value.__get_seller_product.__get_user != undefined) ? value.__get_seller_product.__get_user.email : '') +"</td>" +
                            "<td>"+ ((value.__get_seller_product != undefined && value.__get_seller_product.__get_user != undefined) ? value.__get_seller_product.__get_user.phone : '') +"</td>" +
                            "<td>"+ ((value.__get_seller_product != undefined && value.__get_seller_product.__product != undefined) ? value.__get_seller_product.__product.name : '') +"</td>" +
                            "<td>"+ (($scope.warehouse_status[value.status] != undefined) ? $scope.warehouse_status[value.status]['name'] : '') +"</td>" +
                            "<td>"+ ((value.__get_seller_product != undefined && $scope.warehouse_item_status[value.__get_seller_product.status] != undefined) ? $scope.warehouse_item_status[value.__get_seller_product.status]['name'] : '') +"</td>" +
                            "</tr>"
                        ;
                    });
                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "danh_sach_return.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
            return;
        }
    }
]);