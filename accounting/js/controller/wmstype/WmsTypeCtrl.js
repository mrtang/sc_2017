'use strict';

//Provider report
angular.module('app').controller('WmsTypeCtrl', ['$scope', '$filter', 'Warehouse',
 	function($scope, $filter, Warehouse) {
        
        $scope.currentPage  = 1;
        $scope.item_page    = 20;
        $scope.maxSize      = 5;
        $scope.item_stt     = 0;
        $scope.totalItems   = 0;

        
        $scope.time         = {time_start: new Date(date.getFullYear(), date.getMonth(), 1)};
        $scope.frm          = {};
        $scope.typewms      = {
            0 : 'Theo sản phẩm',
            1 : 'Theo m2',
            2 : 'Theo m3'
        }

        $scope.is_active      = {
            0 : 'Ngừng sử dụng',
            1 : 'Đang sử dụng',
        }

        $scope.list_data    = {};
        $scope.user         = {};
        $scope.waiting      = false;

        $scope.refresh = function(cmd){
            if($scope.time.time_start != undefined && $scope.time.time_start != ''){
                $scope.frm.time_start       = +Date.parse($scope.time.time_start)/1000;
            }else{
                $scope.frm.time_start       = 0;
            }

            if($scope.time.time_end != undefined && $scope.time.time_end != ''){
                $scope.frm.time_end         = +Date.parse($scope.time.time_end)/1000;
            }else{
                $scope.frm.time_end         = 0;
            }

            if(cmd != 'export'){
                $scope.list_data        = [];
                $scope.user             = [];
                $scope.waiting          = true;
            }

        }
        
        $scope.setPage = function(){
            $scope.refresh('');
            Warehouse.wmstype($scope.currentPage,$scope.frm, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.user             = result.data.user;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting  = false;
            });
            return;
        }

        $scope.exportExcel = function(){
            $scope.refresh('export');
            $scope.waiting_export   = true;
            var html =
                "<table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Lịch sử lưu kho</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>Khách hàng</th>" +
                "<th rowspan='2'>Hình thức</th>" +
                "<th colspan='2'>Thời gian</th>" +
                "<th rowspan='2'>Trạng thái</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Bắt đầu</td>" +
                "<td>Kết thúc</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            Warehouse.wmstype(1,$scope.frm, 'export').then(function (result) {
                if(!result.data.error){
                    angular.forEach(result.data.data, function(value, key) {
                        html+= "<tr>" +
                            "<td>"+ i++ +"</td>" +
                            "<td>"+ ((value.__get_user != undefined) ? value.__get_user.email : '') +"</td>" +
                            "<td>"+ ($scope.typewms[value.wms_type] != undefined ? $scope.typewms[value.wms_type] : '') +"</td>" +
                            "<td>"+  (value.start_date  > 0 ? $filter('date')(value.start_date*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  (value.end_date  > 0 ? $filter('date')(value.end_date*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+ ($scope.is_active[value.active] != undefined ? $scope.is_active[value.active] : '') +"</td>" +
                            "</tr>"
                        ;
                    });
                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "lich_su_luu_kho.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
            return;
        }
    }
]);
