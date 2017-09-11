'use strict';

//Provider report
angular.module('app').controller('TransactionCtrl', ['$scope', 'Transaction',
 	function($scope, Transaction) {
        
        $scope.currentPage  = 1;
        $scope.item_page    = 20;
        $scope.maxSize      = 5;
        $scope.item_stt     = 0;
        $scope.totalItems   = 0;

        
        $scope.time         = {time_start: new Date(date.getFullYear(), date.getMonth(), 1), first_shipment_start : ''};
        $scope.frm          = {};

        $scope.list_data    = {};
        $scope.user         = {};
        $scope.waiting      = false;

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
                $scope.time_create_end_open = true;
            }else if(type == "first_shipment_start"){
                $scope.first_shipment_start_open = true;
            }
        };

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

            if($scope.time.first_shipment_start != undefined && $scope.time.first_shipment_start != ''){
                $scope.frm.first_shipment_start   = +Date.parse($scope.time.first_shipment_start)/1000 + 86399;
            }else{
                $scope.frm.first_shipment_start   = 0;
            }

            if(cmd != 'export'){
                $scope.list_data        = [];
                $scope.user             = [];
                $scope.waiting          = true;
            }

        }
        
        $scope.setPage = function(){
            $scope.refresh('');
            Transaction.load($scope.currentPage,$scope.frm, '').then(function (result) {
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
        
        //$scope.setPage();

        $scope.exportExcel = function(){
            $scope.refresh('export');
            return Transaction.load(1,$scope.frm,'export');
        }
    }
]);
