'use strict';

angular.module('app').controller('CourierEstimateCtrl', ['$scope', '$filter', 'Courier',
 	function($scope, $filter, Courier) {
        $scope.maxSize              = 5;
        $scope.currentPage          = 1;
        $scope.item_page            = 20;

        $scope.frm                  = {service : 2, from_city : 18, to_city : 18};
        $scope.list_data            = {};
        $scope.waiting              = false;
        $scope.waiting_export       = false;
        $scope.totalItems           = 0;

        $scope.setPage = function(page){
            $scope.waiting      = true;
            $scope.list_data    = {};
            $scope.currentPage  = page;
            Courier.estimate($scope.currentPage,$scope.frm,'').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting              = false;
            });
            return;
        }

        $scope.exportExcel = function(){
            $scope.waiting_export   = true;

            var html =
                "<meta http-equiv='content-type' content='application/vnd.ms-excel; charset=UTF-8'><table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Cam kết thời gian giao hàng HVC</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>Hãng vận chuyển</th>" +
                "<th rowspan='2'>Dịch vụ</th>" +
                "<th colspan='2'>Điểm đi</th>" +
                "<th colspan='2'>Điểm đến</th>" +
                "<th colspan='2'>Thời gian cam kết</th>" +
                "<th rowspan='2'>Ưu tiên</th>" +
                "<th rowspan='2'>Trạng thái</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Tỉnh/Thành Phố</td>" +
                "<td>Quận/Huyện</td>" +
                "<td>Tỉnh/Thành Phố</td>" +
                "<td>Quận/Huyện</td>" +
                "<td>HVC</td>" +
                "<td>SC</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;
            Courier.estimate(1,$scope.frm,'export').then(function (result) {
                if(!result.data.error){
                    angular.forEach(result.data.data, function(value) {
                        html+= "<tr>" +
                            "<td>"+  i++ +"</td>" +
                            "<td>"+  (($scope.courier[1*value.courier_id]) ? $scope.courier[1*value.courier_id] : '') +"</td>" +
                            "<td>"+  (($scope.service[1*value.service_id]) ? $scope.service[1*value.service_id] : '') +"</td>" +

                            "<td>"+  (($scope.district[1*value.from_district] && $scope.city[$scope.district[1*value.from_district]['city_id']])  ? $scope.city[$scope.district[1*value.from_district]['city_id']] : '') +"</td>" +
                            "<td>"+  (($scope.district[1*value.from_district]) ? $scope.district[1*value.from_district]['district_name']      : '') +"</td>" +

                            "<td>"+  (($scope.district[1*value.to_district] && $scope.city[$scope.district[1*value.to_district]['city_id']])  ? $scope.city[$scope.district[1*value.to_district]['city_id']] : '') +"</td>" +
                            "<td>"+  (($scope.district[1*value.to_district]) ? $scope.district[1*value.to_district]['district_name']      : '') +"</td>" +

                            "<td>"+  ($filter('number')((value.courier_estimate_delivery/3600), 0)) +"</td>"+
                            "<td>"+  ((value.verified == 1) ? $filter('number')((value.estimate_delivery/3600), 0) : 0) +"</td>"+

                            "<td>"+  value.priority +"</td>"+
                            "<td>"+  (value.active == 1 ? 'Đang sử dụng' : 'Ngưng sử dụng') +"</td></tr>";
                    });

                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "Cam_ket_hvc.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
        }

    }
]);