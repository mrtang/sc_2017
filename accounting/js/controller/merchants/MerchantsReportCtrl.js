'use strict';

//Provider report
angular.module('app').controller('MerchantsReportCtrl', ['$scope', '$http', '$state', '$window', 'toaster', 'Order', 'Courier', 'Config_Status',
 	function($scope, $http, $state, $window, toaster, Order, Courier, Config_Status) {
        
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;

        
        $scope.time                 = {create_start: new Date(date.getFullYear(), date.getMonth(), 1),accept_start : new Date(date.getFullYear(), date.getMonth(), 1), success_start : new Date(date.getFullYear(), date.getMonth(), 1), accept_end: new Date(date.getFullYear(), date.getMonth(), date.getDate()), success_end: new Date(date.getFullYear(), date.getMonth(), date.getDate())};
        $scope.frm                  = {};

        $scope.list_data            = {};
        $scope.waiting              = true;

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };
        
        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "time_accept_start"){
                $scope.time_accept_start_open = true;
            }else if(type == "time_accept_end"){
                $scope.time_accept_end_open = true;
            }else if(type == "time_success_start"){
                $scope.time_success_start_open = true;
            }else if(type == "time_success_end"){
                $scope.time_success_end_open = true;
            }else if(type == "time_create_start"){
                $scope.time_create_start_open = true;
            }else if(type == "time_create_end"){
                $scope.time_create_end_open = true;
            }
        };

        $scope.refresh = function(cmd){
            if($scope.time.create_start != undefined && $scope.time.create_start != ''){
                $scope.frm.time_create_start           = +Date.parse($scope.time.create_start)/1000;
            }else{
                $scope.frm.time_create_start           = 0;
            }

            if($scope.time.create_end != undefined && $scope.time.create_end != ''){
                $scope.frm.time_create_end             = +Date.parse($scope.time.create_end)/1000 + 86399;
            }else{
                $scope.frm.time_create_end             = 0;
            }

            if($scope.time.accept_start != undefined && $scope.time.accept_start != ''){
                $scope.frm.time_accept_start           = +Date.parse($scope.time.accept_start)/1000;
            }else{
                $scope.frm.time_accept_start           = 0;
            }

            if($scope.time.accept_end != undefined && $scope.time.accept_end != ''){
                $scope.frm.time_accept_end             = +Date.parse($scope.time.accept_end)/1000 + 86399;
            }else{
                $scope.frm.time_accept_end             = 0;
            }

            if($scope.time.success_start != undefined && $scope.time.success_start != ''){
                $scope.frm.time_success_start          = +Date.parse($scope.time.success_start)/1000;
            }else{
                $scope.frm.time_success_start          = 0;
            }

            if($scope.time.success_end != undefined && $scope.time.success_end != ''){
                $scope.frm.time_success_end            = +Date.parse($scope.time.success_end)/1000 + 86399;
            }else{
                $scope.frm.time_success_end            = 0;
            }

            if(cmd != 'export'){
                $scope.list_data = [];
                $scope.waiting          = true;
            }

        }
        
        $scope.setPage = function(){
            $scope.refresh('');
        	Order.OrderAccounting($scope.currentPage,$scope.frm, '').then(function (result) {
                if(result){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                }else{
                    toaster.pop('error', 'Thông báo', 'Lỗi hệ thống !');
                }
                $scope.waiting  = false;
            });
            return;
        }
        
        $scope.setPage();

        $scope.exportExcel = function(){
            $scope.refresh('export');
            return Order.OrderAccounting(1,$scope.frm,'export');
        }
    }
]);
