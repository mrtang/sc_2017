'use strict';

//Provider report
angular.module('app').controller('PartnerVerifyCtrl', ['$scope', '$filter', 'Warehouse',
 	function($scope, $filter, Warehouse) {
        
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;

        
        $scope.time                 = {create_start: new Date(date.getFullYear(), date.getMonth(), 1)};
        $scope.frm                  = {};

        $scope.list_data            = {};
        $scope.waiting              = false;

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.list_config          = {
            1 : 'Theo pallet',
            2 : 'Theo khoang'
        };
        
        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "create_start"){
                $scope.create_start_open = true;
            }else if(type == "create_end"){
                $scope.create_end_open = true;
            }
        };

        $scope.refresh = function(cmd){
            if($scope.time.create_start != undefined && $scope.time.create_start != ''){
                $scope.frm.create_start           = +Date.parse($scope.time.create_start)/1000;
            }else{
                $scope.frm.create_start           = '';
            }

            if($scope.time.create_end != undefined && $scope.time.create_end != ''){
                $scope.frm.create_end             = +Date.parse($scope.time.create_end)/1000 + 86399;
            }else{
                $scope.frm.create_end             = 0;
            }

            if(cmd != 'export'){
                $scope.list_data = [];
                $scope.waiting          = true;
            }

        }
        
        $scope.setPage = function(){
            $scope.refresh('');
            Warehouse.partner_verify($scope.currentPage,$scope.frm, '').then(function (result) {
                if(result){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting  = false;
            });
            return;
        }



        $scope.__export_warehouse = function(data){
            $scope.refresh('export');
            $scope.waiting_export   = true;

            var html =
                "<meta http-equiv='content-type' content='application/vnd.ms-excel; charset=UTF-8'><table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh sách chi tiết theo ngày</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>Mã BK</th>" +
                "<th rowspan='2'>ID</th>" +
                "<th rowspan='2'>Thời gian</th>" +
                "<th rowspan='2'>Hình thức lưu kho</th>" +
                "<th rowspan='2'>Kho</th>" +
                "<th colspan='3'>Chi tiết</th>" +
                "<th colspan='2'>Chi phí - Khách hàng</th>" +
                "<th colspan='2'>Chi phí - Đối tác</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Item</td>" +
                "<td>Sku</td>" +
                "<td>Floor</td>" +

                "<td>Phí</td>" +
                "<td>Miễn phí</td>" +

                "<td>Phí</td>" +
                "<td>Miễn phí</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;
            angular.forEach(data, function(value) {
                html+= "<tr>" +
                    "<td>"+  i++ +"</td>" +
                    "<td>"+  value.partner_verify_id +"</td>" +
                    "<td>"+  value.id +"</td>" +
                    "<td>"+  value.date +"</td>" +
                    "<td>"+  (($scope.list_config[1*value.payment_type] != undefined)       ? $scope.list_config[1*value.payment_type]              : '') +"</td>" +
                    "<td>"+  (($scope.warehouse_warehouse[value.warehouse] != undefined)  ? $scope.warehouse_warehouse[value.warehouse]['name'] : '') +"</td>" +
                    "<td>"+  $filter('number')(value.total_item, 0) +"</td>" +
                    "<td>"+  $filter('number')(value.total_sku, 0) +"</td>" +
                    "<td>"+  $filter('number')(value.floor, 0) +"</td>" +
                    "<td>"+  $filter('number')(value.total_fee, 0) +"</td>" +
                    "<td>"+  $filter('number')(value.total_discount, 0) +"</td>" +
                    "<td>"+  $filter('number')(value.partner_total_fee, 0) +"</td>" +
                    "<td>"+  $filter('number')(value.partner_total_discount, 0) +"</td></tr>";
            });

            html        +=  "</tbody></table>";
            var blob = new Blob([html], {
                type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
            });
            saveAs(blob, "Danh_sach_luu_kho_chi_tiet_theo_ngay.xls");
        }

        $scope.__export_detail_sku  = function(data){
            var html =
                "<meta http-equiv='content-type' content='application/vnd.ms-excel; charset=UTF-8'><table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Chi tiết theo sku</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th>STT</th>" +
                "<th>Mã BK</th>" +
                "<th>ID</th>" +
                "<th>Kho</th>" +
                "<th>Loại sản phẩm</th>" +
                "<th>Sku</th>" +
                "<th>Item</th>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            angular.forEach(data, function(value) {
                html+= "<tr>" +
                    "<td>"+  i++ +"</td>" +
                    "<td>"+  value.partner_verify_id +"</td>" +
                    "<td>"+  value.log_id +"</td>" +
                    "<td>"+  (($scope.warehouse_warehouse[value.warehouse] != undefined)  ? $scope.warehouse_warehouse[value.warehouse]['name'] : '') +"</td>" +
                    "<td>"+  value.type_sku +"</td>" +
                    "<td>"+  value.sku +"</td>" +
                    "<td>"+  $filter('number')(value.total_item, 0) +"</td></tr>";
            });

            html        +=  "</tbody></table>";
            var blob = new Blob([html], {
                type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
            });
            saveAs(blob, "Danh_sach_chi_tiet_theo_sku.xls");
        }

        $scope.__export_detail  = function(data){
            var html =
                "<meta http-equiv='content-type' content='application/vnd.ms-excel; charset=UTF-8'><table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Chi tiết theo loại sản phẩm</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>Mã BK</th>" +
                "<th rowspan='2'>ID</th>" +
                "<th rowspan='2'>Kho</th>" +
                "<th rowspan='2'>Loại sản phẩm</th>" +
                "<th colspan='3'>Chi tiết</th>" +
                "<th colspan='2'>Tiêu chuẩn</th>" +
                "<th colspan='2'>Chi phí - Khách hàng</th>" +
                "<th colspan='2'>Chi phí - Đối tác</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Item</td>" +
                "<td>Sku</td>" +
                "<td>Floor</td>" +

                "<td>Item</td>" +
                "<td>Floor</td>" +

                "<td>Phí</td>" +
                "<td>Miễn phí</td>" +

                "<td>Phí</td>" +
                "<td>Miễn phí</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            angular.forEach(data, function(value) {
                html+= "<tr>" +
                    "<td>"+  i++ +"</td>" +
                    "<td>"+  value.partner_verify_id +"</td>" +
                    "<td>"+  value.log_id +"</td>" +
                    "<td>"+  (($scope.warehouse_warehouse[value.warehouse] != undefined)  ? $scope.warehouse_warehouse[value.warehouse]['name'] : '') +"</td>" +
                    "<td>"+  value.type_sku +"</td>" +

                    "<td>"+  $filter('number')(value.total_item, 0) +"</td>" +
                    "<td>"+  $filter('number')(value.total_sku, 0) +"</td>" +
                    "<td>"+  $filter('number')(value.floor, 0) +"</td>" +

                    "<td>"+  $filter('number')(value.standard_item, 0) +"</td>" +
                    "<td>"+  $filter('number')(value.standard_sku, 0) +"</td>" +

                    "<td>"+  $filter('number')(value.fee, 0) +"</td>" +
                    "<td>"+  $filter('number')(value.discount_fee, 0) +"</td>" +

                    "<td>"+  $filter('number')(value.partner_fee, 0) +"</td>" +
                    "<td>"+  $filter('number')(value.partner_discount_fee, 0) +"</td></tr>";
            });

            html        +=  "</tbody></table>";
            var blob = new Blob([html], {
                type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
            });
            saveAs(blob, "Danh_sach_chi_tiet_theo_loai_san_pham.xls");
        }

        $scope.exportExcel = function(){
            $scope.refresh('export');
            $scope.waiting_export   = true;

            var html =
                "<meta http-equiv='content-type' content='application/vnd.ms-excel; charset=UTF-8'><table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh sách đối soát đối tác</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>Mã BK</th>" +
                "<th rowspan='2'>Thời gian</th>" +
                "<th rowspan='2'>Đối Tác</th>" +
                "<th rowspan='2'>Kho</th>" +
                "<th colspan='3'>Chi tiết</th>" +
                "<th colspan='2'>Chi phí</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Item</td>" +
                "<td>Sku</td>" +
                "<td>Floor</td>" +
                "<td>Phí</td>" +
                "<td>Miễn phí</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;
            Warehouse.partner_verify(1,$scope.frm,'export').then(function (result) {
                if(!result.data.error){
                    var data_verify = [];
                    var data_detail = [];
                    var data_sku    = [];

                    angular.forEach(result.data.data, function(value) {
                        if(value.__get_refer != undefined){
                            angular.forEach(value.__get_refer, function(val) {
                                if(val.__get_warehouse_fee != undefined){
                                    val.__get_warehouse_fee['partner_verify_id'] = value['id'];
                                    data_verify.push(val.__get_warehouse_fee);

                                    if(val.__get_warehouse_fee.__warehouse_detail != undefined){
                                        angular.forEach(val.__get_warehouse_fee.__warehouse_detail, function(v) {
                                            v['partner_verify_id'] = value['id'];
                                            data_detail.push(v);
                                        });
                                    }

                                    if(val.__get_warehouse_fee.__warehouse_sku_detail != undefined){
                                        angular.forEach(val.__get_warehouse_fee.__warehouse_sku_detail, function(v) {
                                            v['partner_verify_id'] = value['id'];
                                            data_sku.push(v);
                                        });
                                    }
                                }

                            })

                        }

                        html+= "<tr>" +
                            "<td>"+  i++ +"</td>" +
                            "<td>"+  value.id +"</td>" +
                            "<td>"+  value.date +"</td>" +
                            "<td>"+  (($scope.list_courier[1*value.courier] != undefined)   ? $scope.list_courier[1*value.courier]['name']  : '') +"</td>" +
                            "<td>"+  (($scope.warehouse_warehouse[value.warehouse] != undefined)  ? $scope.warehouse_warehouse[value.warehouse]['name'] : '') +"</td>" +
                            "<td>"+  $filter('number')(value.total_uid_storage, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.total_sku, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.floor, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.warehouse_fee, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.discount_warehouse, 0) +"</td></tr>";
                    });

                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "Danh_sach_doi_soat_doi_tac.xls");

                    $scope.__export_warehouse(data_verify);
                    $scope.__export_detail(data_detail);
                    $scope.__export_detail_sku(data_sku);
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
        }
    }
]);
