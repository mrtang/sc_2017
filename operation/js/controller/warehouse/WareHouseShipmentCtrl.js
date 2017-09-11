'use strict';

angular.module('app').controller('WareHouseShipmentCtrl', ['$scope', 'Warehouse',
 	function($scope, Warehouse) {
        $scope.waiting              = false;
        $scope.waiting_export       = false;
        $scope.item_stt             = 0;
        $scope.totalItems           = 0;
        $scope.time                 = {create_start : new Date(date.getFullYear(), date.getMonth(), 1)};
        $scope.check_box_status     = [];
        $scope.frm                  = {type_process : 12, group : 2};
        $scope.list_data            = {};
        $scope.total_group          = [];

        $scope.toggleSelectionStatus = function(id) {
            var data = angular.copy($scope.check_box_status);
            var idx = +data.indexOf(id);

            if (idx > -1) {
                $scope.check_box_status.splice(idx, 1);
            }
            else {
                $scope.check_box_status.push(id);
            }
        };

        $scope.refresh = function(cmd){
            if($scope.time.expect_start != undefined && $scope.time.expect_start != ''){
                $scope.frm.expect_start   = +Date.parse($scope.time.expect_start)/1000;
            }else{
                $scope.frm.expect_start   = 0;
            }
            if($scope.time.expect_end != undefined && $scope.time.expect_end != ''){
                $scope.frm.expect_end     = +Date.parse($scope.time.expect_end)/1000 + 86399;
            }else{
                $scope.frm.expect_end     = 0;
            }

            if($scope.time.pickup_start != undefined && $scope.time.pickup_start != ''){
                $scope.frm.pickup_start   = +Date.parse($scope.time.pickup_start)/1000;
            }else{
                $scope.frm.pickup_start   = 0;
            }
            if($scope.time.pickup_end != undefined && $scope.time.pickup_end != ''){
                $scope.frm.pickup_end     = +Date.parse($scope.time.pickup_end)/1000 + 86399;
            }else{
                $scope.frm.pickup_end     = 0;
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

            if($scope.time.delivered_start != undefined && $scope.time.delivered_start != ''){
                $scope.frm.delivered_start   = +Date.parse($scope.time.delivered_start)/1000;
            }else{
                $scope.frm.delivered_start   = 0;
            }
            if($scope.time.delivered_end != undefined && $scope.time.delivered_end != ''){
                $scope.frm.delivered_end     = +Date.parse($scope.time.delivered_end)/1000 + 86399;
            }else{
                $scope.frm.delivered_end     = 0;
            }

            if($scope.check_box_status != undefined && $scope.check_box_status != []){
                $scope.frm.list_status  = $scope.check_box_status.toString();
            }else{
                $scope.frm.list_status       = '';
            }

            if(cmd != 'export'){
                $scope.list_data            = {};
                $scope.waiting              = true;
            }

        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.refresh('');
            Warehouse.shipment($scope.currentPage,$scope.frm, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting  = false;
            });
            return;
        }

        $scope.setCountGroup    = function(){
            $scope.total_group  = [];
            Warehouse.shipment_count_group($scope.frm).then(function (result) {
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

        $scope.exportExcel = function(){
            $scope.refresh('export');
            $scope.Export($scope.frm,'Danh sach nhap kho', 'danh_sach_nhap_kho.xls');
        }
    }
]);