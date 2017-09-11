'use strict';

angular.module('app').controller('RequestCtrl', ['$scope', '$http', '$state', '$window', 'toaster', 'Order', 'Config_Status',
 	function($scope, $http, $state, $window, toaster, Order, Config_Status) {

        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;
        $scope.total_group          = [];
        $scope.total_all            = 0;
        $scope.time                 = {create_start : new Date(date.getFullYear(), date.getMonth(), 1)};
        $scope.frm                  = {vip : 0, global: 0};
        $scope.list_data            = {};
        $scope.list_location        = {'list_city': {},'list_district': {},'list_ward': {},'list_to_address': {},'list_from_address': {}};
        $scope.list_color           = Config_Status.order_color;
        $scope.tag_color            = Config_Status.tag_color;

        if($scope.group_order_status[23] == undefined){
            $scope.group_order_status[23]   = Config_Status.group_status[23];
        }

        $scope.waiting              = false;

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "time_create_start"){
                $scope.time_create_start_open = true;
            }else if(type == "time_create_end"){
                $scope.time_create_end = true;
            }
        };

        $scope.$watch('frm.global', function(newVal, oldVal) {
            if(newVal == 1){
                $scope.frm.to_city = 0;
                $scope.frm.to_district = 0;
            }
        });

        $scope.refresh = function(cmd){
            if($scope.frm.courier == undefined || $scope.frm.courier  == ''){
                $scope.frm.courier  = 'ALL';
            }

            if($scope.time.accept_start != undefined && $scope.time.accept_start != ''){
                $scope.frm.accept_start    = +Date.parse($scope.time.accept_start)/1000;
            }else{
                $scope.frm.accept_start    = 0;
            }

            if($scope.time.accept_end != undefined && $scope.time.accept_end != ''){
                $scope.frm.accept_end      = +Date.parse($scope.time.accept_end)/1000 + 86399;
            }else{
                $scope.frm.accept_end      = 0;
            }

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

            if(cmd != 'export'){
                $scope.list_data            = {};
                $scope.list_location        = {'list_city': {},'list_district': {},'list_ward': {},'list_to_address': {},'list_from_address': {}};
                $scope.waiting              = true;
            }

        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.refresh('');
            Order.ListOrder($scope.currentPage,$scope.frm, $scope.group_order_status[23].toString(), '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);

                    $scope.list_location.list_city              = result.data.list_city;
                    $scope.list_location.list_district          = result.data.list_district;
                    $scope.list_location.list_ward              = result.data.list_ward;

                    $scope.list_location.list_to_address        = result.data.list_to_address;
                    $scope.list_location.list_from_address      = result.data.list_from_address;
                }
                $scope.waiting  = false;
            });
            return;
        }

        $scope.exportExcel = function(){
            $scope.refresh('export');
            return Order.ListOrder(1,$scope.frm,$scope.group_order_status[23].toString(),'export');
        }

        $scope.setCountGroup    = function(){
            $scope.total_all    = 0;
            $scope.total_group  = [];
            Order.CountGroup($scope.frm, $scope.group_order_status[23].toString()).then(function (result) {
                if(!result.data.error){
                    $scope.total_all        = result.data.total;
                    $scope.total_group      = result.data.data;
                }
            });
        }

        $scope.ChangeTab    = function(courier){
            $scope.frm.courier  = courier;
            $scope.setPage(1);
        }


        $scope.action   = function(item){
                var dataupdate = {};
                // Update status

                dataupdate['tracking_code'] = item.tracking_code;
                dataupdate['status'] = 21;

                Order.Edit(dataupdate).then(function (result) {
                    if (result.data.error) {
                        if (result.data.message == 'NOT_ENOUGH_MONEY') {
                            toaster.pop('warning', 'Thông báo', 'Tài khoản không đủ tiền !');
                        }
                    } else {
                        item.status = 21;
                    }
                });
            }
            return;
        }
]);
