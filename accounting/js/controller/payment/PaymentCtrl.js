'use strict';

angular.module('app').controller('PaymentCtrl', ['$scope', '$filter', 'Payment', 'Config_Status',
 	function($scope, $filter, Payment, Config_Status) {
    // config
        
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.totalItems           = 0;
        $scope.item_stt             = 0;

        $scope.frm                  = {};
        $scope.time                 = {create_start : new Date(date.getFullYear(), date.getMonth(), 1), time_end : '', first_shipment_start : ''};
        $scope.list_data            = {};
        $scope.User                 = {};
        $scope.waiting              = false;
        $scope.list_status          = Config_Status.StatusVerify;
        
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
            $scope.list_data        = [];
            $scope.total_all        = 0;
            $scope.User             = {};
        }

        $scope.get_time = function(){
            if($scope.time.create_start != undefined && $scope.time.create_start != ''){
                $scope.frm.create_start    = +Date.parse($scope.time.create_start)/1000;
            }else{
                $scope.frm.create_start     = 0;
            }

            if($scope.time.create_end != undefined && $scope.time.create_end != ''){
                $scope.frm.create_end      = +Date.parse($scope.time.create_end)/1000 + 86399;
            }else{
                $scope.frm.create_end       = 0;
            }

            if($scope.time.accept_start != undefined && $scope.time.accept_start != ''){
                $scope.frm.accept_start   = +Date.parse($scope.time.accept_start)/1000;
            }else{
                $scope.frm.accept_start   = 0;
            }

            if($scope.time.accept_end != undefined && $scope.time.accept_end != ''){
                $scope.frm.accept_end     = +Date.parse($scope.time.accept_end)/1000 + 86399;
            }else{
                $scope.frm.accept_end     = 0;
            }

            if($scope.time.first_shipment_start != undefined && $scope.time.first_shipment_start != ''){
                $scope.frm.first_shipment_start   = +Date.parse($scope.time.first_shipment_start)/1000 + 86399;
            }else{
                $scope.frm.first_shipment_start   = 0;
            }
        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.waiting      = true;

            $scope.get_time();
            
            $scope.refresh('');
            Payment.load($scope.currentPage, $scope.frm, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                    $scope.data_sum         = result.data.data_sum;

                    if(result.data.user){
                        angular.forEach(result.data.user, function(value) {
                            $scope.User[value.id]   = {};
                            $scope.User[value.id]['fullname']    = value.fullname;
                            $scope.User[value.id]['phone']       = value.phone;
                            $scope.User[value.id]['email']       = value.email;
                        });
                    }
                }
                $scope.waiting = false;
            });
            return;
        }

        $scope.export_excel = function(){
            $scope.get_time();
            $scope.waiting_export   = true;

            var html =
                "<table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Bảng kê thanh toán đối soát</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>Mã bảng kê</th>" +
                "<th rowspan='2'>Thời gian</th>" +
                "<th colspan='2'>Khách hàng</th>" +
                "<th rowspan='2'>Account</th>" +
                "<th rowspan='2'>Account Name</th>" +
                "<th rowspan='2'>Account Number</th>" +
                "<th rowspan='2'>Hình thức</th>" +
                "<th rowspan='2'>Tổng phí</th>" +
                "<th rowspan='2'>Tổng tiền thu hộ</th>" +
                "<th rowspan='2'>Số dư hiện tại</th>" +
                "<th rowspan='2'>Số dư cấu hình</th>" +
                "<th rowspan='2'>Số dư tạm dữ</th>" +
                "<th rowspan='2'>Thực nhận</th>" +
                "<th rowspan='2'>Trạng thái</th>" +
                "<th rowspan='2'></th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Họ tên</td>" +
                "<td>Email</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            Payment.load(1,$scope.frm,'export').then(function (result) {
                if(!result.data.error){
                    var text;
                    var amount;
                    angular.forEach(result.data.data, function(value) {
                        text    = 'Không đủ điều kiện';
                        amount  = 1*value['total_money_collect'] + 1*value['balance'] - 1*value['total_fee'] + ((1*value['balance_available'] - 1*value['config_balance']) < 0 ? (1*value['balance_available'] - 1*value['config_balance']) : 0);

                        //Check điều kiện thanh toán
                        if(value.type_payment == 1){// ngân lượng
                            if(amount >= 100000 && value.acc_number != '' && value.acc_number != 0){
                                text = 'Đủ điều kiện';
                            }
                        }else{// vimo
                            if(amount >= 100000 && amount <= 200000000 && value.acc_number != '' && value.acc_number != 0){
                                text = 'Đủ điều kiện';
                            }
                        }

                        html+= "<tr>" +
                            "<td>"+  i++ +"</td>" +
                            "<td>"+  value.id +"</td>" +
                            "<td>"+  (value.time_create  > 0 ? $filter('date')(value.time_create*1000, "dd/MM/yyyy  HH:mm:ss") : '') +"</td>" +
                            "<td>"+  ((value.user != undefined)           ? value.user.fullname                : '') +"</td>" +
                            "<td>"+  ((value.user != undefined)           ? value.user.email                : '') +"</td>" +

                            "<td>"+  value.account +"</td>" +
                            "<td>"+  value.acc_name +"</td>" +
                            "<td>"+  "'"+ String(value.acc_number) +"</td>" +
                            "<td>"+  ((1*value.type_payment == 1) ? 'Ngân lượng' : 'Vimo')+"</td>" +
                            "<td>"+  $filter('number')(value.total_fee, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.total_money_collect, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.balance, 0) +"</td>" +
                            "<td>"+  $filter('number')(value.config_balance, 0) +"</td>" +
                            "<td>"+  $filter('number')((1*value['balance_available'] - 1*value['config_balance']), 0) +"</td>" +
                            "<td>"+  $filter('number')(amount, 0) +"</td>" +
                            "<td>"+  value.status +"</td>" +
                            "<td>"+  text +"</td>" +
                            "</tr>"
                    });

                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "Bang_ke_thanh_toan_doi_soat.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
        }

        /**
         * Kiểm tra số tiền thực nhận
         */
        $scope.calculate    = function(item){
            return item.total_money_collect + item.balance - item.total_fee + (((item.balance_available - item.config_balance) < 0 ? (item.balance_available - item.config_balance) : 0));
        }
    }
]);
