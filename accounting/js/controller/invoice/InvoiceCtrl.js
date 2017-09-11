'use strict';

//Provider report
angular.module('app').controller('InvoiceCtrl', ['$scope', '$rootScope', 'Invoice', 'Config_Status',
 	function($scope, $rootScope, Invoice, Config_Status) {
        
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;
        $scope.totalItems           = 0;

        $scope.User                 = {};

        
        $scope.time                 = {create_start: new Date(date.getFullYear(), date.getMonth(), 1), first_shipment_start : ''};
        $scope.frm                  = {};

        $scope.list_data            = {};
        $scope.list_sum             = {};
        $scope.waiting              = false;

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.sum_fee  = function(item){
            return item.total_sc_pvc + item.total_sc_cod + item.total_sc_pbh + item.total_sc_pvk + item.total_sc_pch + item.total_premote +
                            item.total_pclearance + item.total_sc_plk + item.total_sc_pdg + item.total_sc_pxl +
                            item.total_lsc_pvc + item.total_lsc_cod + item.total_lsc_pbh +  item.total_lsc_pvk + item.total_lsc_pch +
                            item.total_lsc_pclearance + item.total_lsc_premote + item.total_lsc_plk + item.total_lsc_pdg + item.total_lsc_pxl
                            - item.total_sc_discount_pvc - item.total_sc_discount_cod - item.total_lsc_discount_pvc - item.total_lsc_discount_cod
                            - item.total_discount_plk - item.total_discount_pdg - item.total_discount_pxl - item.total_ldiscount_plk - item.total_ldiscount_pdg
                            - item.total_ldiscount_pxl;
        }

        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "time_create_start"){
                $scope.time_create_start_open = true;
            }else if(type == "time_create_end"){
                $scope.time_create_end_open = true;
            }else if(type == "first_shipment_start"){
                $scope.first_shipment_start_open = true;
            }
        };

        $scope.refresh = function(cmd){
            if($scope.time.create_start != undefined && $scope.time.create_start != ''){
                $scope.frm.time_start           = +Date.parse($scope.time.create_start)/1000;
            }else{
                $scope.frm.time_start           = 0;
            }

            if($scope.time.create_end != undefined && $scope.time.create_end != ''){
                $scope.frm.time_create_end             = +Date.parse($scope.time.create_end)/1000 + 86399;
            }else{
                $scope.frm.time_create_end             = 0;
            }

            if($scope.time.first_shipment_start != undefined && $scope.time.first_shipment_start != ''){
                $scope.frm.first_shipment_start   = +Date.parse($scope.time.first_shipment_start)/1000 + 86399;
            }else{
                $scope.frm.first_shipment_start   = 0;
            }

            if(cmd != 'export'){
                $scope.list_data = [];
                $scope.list_sum  = [];
                $scope.waiting          = true;
            }

        }
        
        $scope.setPage = function(){
            $scope.refresh('');
            Invoice.load($scope.currentPage,$scope.frm, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                    $scope.User             = result.data.user;
                    $scope.list_sum         = result.data.sum;
                }
                $scope.waiting  = false;
            });
            return;
        }
        
        //$scope.setPage();

        $scope.exportExcel = function(){
            $scope.refresh('export');
            return Invoice.load(1,$scope.frm,'export');
        }
    }
]);
