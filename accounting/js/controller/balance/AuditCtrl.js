'use strict';

//Balance report
angular.module('app').controller('AuditCtrl', ['$scope', '$filter', 'Audit',
 	function($scope, $filter, Audit) {
    // config
        
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.totalItems           = 0;

        $scope.frm                  = {type : 0, search: '',record : 0};
        $scope.time                 = {time_start : '', time_end : '', first_shipment_start : ''};
        $scope.list_data            = {};
        $scope.data_sum             = {};
        $scope.waiting              = false;
        $scope.waiting_export       = false;
        
        
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

        // action
        
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
            Audit.load($scope.currentPage, $scope.frm, '').then(function (result) {
                if(result){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                    $scope.data_sum         = result.data.data_sum;
                }
                $scope.waiting  = false;
            });
            return;
        }
        $scope.doSettimeout = function(i){
            setTimeout(function() { $scope.exportExcel(i + 1); }, i*20000);
        }
        $scope.fncExport = function(num){
            for (var i = 0; i <= (num + 1); ++i) {
                $scope.doSettimeout(i);
            };
        }

        $scope.exportExcel = function(num){
            $scope.refresh('export');
            $scope.waiting_export   = true;
            $scope.frm.num = num;

            var html =
                "<table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh sách số dư theo ngày</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>Thời gian</th>" +
                "<th colspan='3'>Khách hàng</th>" +
                "<th rowspan='2'>Số dư</th>" +
                "<th rowspan='2'>Số dư hệ thống</th>" +
                "<th rowspan='2'>Trạng thái</th>" +

                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Tên</td>" +
                "<td>Email</td>" +
                "<td>Phone</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            Audit.load($scope.currentPage, $scope.frm, 'export').then(function (result) {
                if(!result.data.error){
                    angular.forEach(result.data.data, function(value) {
                        html+= "<tr>" +
                            "<td>"+ i++ +"</td>" +
                            "<td>"+  (value.time_end  > 0 ? $filter('date')(value.time_end*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+ (value.user != null ? value.user.fullname : '') +"</td>" +
                            "<td>"+ (value.user != null ? value.user.email : '') +"</td>" +
                            "<td>"+ (value.user != null ? value.user.phone : '') +"</td>" +

                            "<td>"+  $filter('number')(value.balance, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.audit, 0) +"</td>" +
                            "<td>"+ value.status +"</td>" +
                            "</tr>"
                        ;
                    });
                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "danh_sach_so_du_theo_ngay.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
            return;
        }
    }
]);
