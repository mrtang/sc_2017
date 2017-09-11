'use strict';

//Balance report
angular.module('app').controller('BalanceReportCtrl', ['$scope', '$filter', 'Merchant',
 	function($scope, $filter, Merchant) {
    // config
        
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.totalItems           = 0;

        $scope.frm                  = {type : 0, search: ''};
        $scope.time                 = {time_start : '', time_end : '', first_shipment_start : ''};
        $scope.list_data            = {};
        $scope.data_sum             = {};
        $scope.waiting              = false;
        $scope.waiting_export       = false;

        // action

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "time_start"){
                    $scope.time_start_open = true;
                }else if(type == "time_end"){
                    $scope.time_end_open = true;
                }else if(type == "first_shipment_start"){
                $scope.first_shipment_start_open = true;
            }
        };
        
        $scope.refresh = function(cmd){
            if($scope.time.time_start != undefined && $scope.time.time_start != ''){
                $scope.frm.time_start   = +Date.parse($scope.time.time_start)/1000;
            }else{
                $scope.frm.time_start   = 0;
            }

            if($scope.time.time_end != undefined && $scope.time.time_end != ''){
                $scope.frm.time_end     = +Date.parse($scope.time.time_end)/1000 + 86399;
            }else{
                $scope.frm.time_end   = 0;
            }

            if($scope.time.first_shipment_start != undefined && $scope.time.first_shipment_start != ''){
                $scope.frm.first_shipment_start   = +Date.parse($scope.time.first_shipment_start)/1000 + 86399;
            }else{
                $scope.frm.first_shipment_start   = 0;
            }



            if(cmd != 'export'){
                $scope.list_data        = [];
                $scope.total_all        = 0;
                $scope.total_group      = [];
                $scope.data_sum         = [];
                $scope.waiting          = true;
            }
        }
        
        $scope.setPage = function(){
            $scope.refresh('');
            Merchant.load($scope.currentPage, $scope.frm, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                    $scope.data_sum         = result.data.data_sum;
                }
                $scope.waiting  = false;
            });
            return;
        }

        $scope.ChangeTab    = function(action){
            $scope.frm.type         = action;
            $scope.currentPage  = 1;
            $scope.setPage();
        }

        //$scope.setPage();

        /**
         *   Edit merchant
         */
        $scope.change   = function(item, new_value, field){
            var dataupdate = {};
            if(new_value != undefined && item[field] != new_value && item.id > 0 ){
                dataupdate[field] = new_value;
                return  Merchant.edit(item.id, dataupdate).then(function (result) {
                    if(result.data.error){
                        return 'Cập nhật lỗi';
                    }
                    return;
                });
            }
            return;
        };

        $scope.exportExcel = function(){
            $scope.refresh('export');
            $scope.waiting_export   = true;

            var html =
                "<meta http-equiv='content-type' content='application/vnd.ms-excel; charset=UTF-8'><table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh sách khách hàng</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th>STT</th>" +
                "<th>Ngày tạo</th>" +
                "<th>Khách hàng</th>" +
                "<th>Email</th>" +
                "<th>SDT</th>" +
                "<th>Level</th>" +
                "<th>Số dư</th>" +
                "<th>Phí VC(tạm tính)</th>" +
                "<th>Thu hộ(tạm tính) </th>" +
                "<th>Hạn mức</th>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;
            Merchant.load($scope.currentPage, $scope.frm, 'export').then(function (result) {
                if(!result.data.error){
                    angular.forEach(result.data.data, function(value) {
                        html+= "<tr>" +
                            "<td>"+  i++ +"</td>" +
                            "<td>"+  (value.time_create  > 0 ? $filter('date')(value.time_create*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  ((value.user != undefined) ? value.user.fullname : '') +"</td>" +
                            "<td>"+  ((value.user != undefined) ?  value.user.email : '') +"</td>" +
                            "<td>"+  ((value.user != undefined) ?  value.user.phone : '') +"</td>" +
                            "<td>"+  value.level +"</td>" +
                            "<td>"+  $filter('number')(value.balance, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.freeze, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.provisional, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.quota, 0) +"</td></tr>";
                    });

                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "Danh_sach_khach_hang.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
        }
    }
]);
