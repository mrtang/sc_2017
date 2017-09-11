'use strict';

angular.module('app').controller('WareHouseInBoundConfigCtrl', ['$scope', '$localStorage', '$filter', 'Warehouse',
 	function($scope, $localStorage, $filter, Warehouse) {
        $scope.currentPage      = 1;
        $scope.item_page        = 20;
        $scope.maxSize          = 5;
        $scope.waiting_export   = false;

        $scope.area_location    = [
            { code : 1      , content : 'Nội thành'},
            { code : 2      , content : 'Ngoại thành'},
            { code : 3      , content : 'Liên tỉnh'}
        ];

        $scope.delivery_slow    = [
            { code : 4      , content : '4h'},
            { code : 8      , content : '8h'},
            { code : 24     , content : '1 ngày'},
            { code : 48     , content : '2 ngày'},
            { code : 96     , content : '4 ngày'},
            { code : 168    , content : '7 ngày'}
        ];

        $scope.shipping_method    = [
            { code : 0      , content : 'Boxme'},
            { code : 1      , content : 'Khách hàng'}
        ];

        $scope.Export = function(data, name, file_name){
            $scope.waiting_export   = true;
            var html =
                "<table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>" + name + "</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th colspan='5'>Thoi gian</th>" +
                "<th rowspan='2'>Ma Shipment</th>" +
                "<th rowspan='2'>Ma SC</th>" +
                "<th rowspan='2'>Kho</th>" +
                "<th rowspan='2'>So luong</th>" +
                "<th rowspan='2'>Dieu chinh</th>" +
                "<th colspan='2'>Khach hang</th>" +
                "<th rowspan='2'>Tinh thanh</th>" +
                "<th rowspan='2'>Quan huyen</th>" +
                "<th rowspan='2'>Trang thai Shipment</th>" +
                "<th rowspan='2'>Trang thai SC</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Tao</td>" +
                "<td>Duyet</td>" +
                "<td>Giao toi kho</td>" +
                "<td>Kho nhan</td>" +
                "<td>Cap nhat</td>" +
                "<td>Email</td>" +
                "<td>Phone</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            Warehouse.shipment(1,data, 'export').then(function (result) {
                if(!result.data.error){
                    angular.forEach(result.data.data, function(value) {
                        html+= "<tr>" +
                            "<td>"+ i++ +"</td>" +
                            "<td>"+ value.created +"</td>" +
                            "<td>"+ value.approved +"</td>" +
                            "<td>"+ '' +"</td>" +
                            "<td>"+ '' +"</td>" +
                            "<td>"+ value.updated_at +"</td>" +
                            "<td>"+ value.request_code +"</td>" +
                            "<td>"+ value.tracking_number +"</td>" +
                            "<td>"+ (($scope.warehouse[value.warehouse] != undefined) ? $scope.warehouse[value.warehouse]['name'] : '') +"</td>" +
                            "<td>"+ $filter('number')($scope.count_shipment(value.__get_shipment_product), 0) +"</td>" +
                            "<td>"+ '' +"</td>" +
                            "<td>"+ ((value.__get_user != undefined) ? value.__get_user.email : '') +"</td>" +
                            "<td>"+ ((value.__get_user != undefined) ? "_"+value.__get_user.phone : '') +"</td>" +
                            "<td>"+ ((value.__get_outbound.city_id != undefined) ? $scope.city[value.__get_outbound.city_id] : '') +"</td>" +
                            "<td>"+ ((value.__get_outbound.province_id != undefined) ? $scope.district[value.__get_outbound.province_id]['district_name'] : '') +"</td>" +
                            "<td>"+ (($scope.shipment_status[value.status] != undefined) ? $scope.shipment_status[value.status]['name'] : '') +"</td>" +
                            "<td>" + '' + "</td>" +
                            "</tr>"
                        ;
                    });
                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, file_name);
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
            return;
        }
    }
]);