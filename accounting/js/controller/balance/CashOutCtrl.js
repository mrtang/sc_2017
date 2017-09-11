'use strict';

//Rút tiền
angular.module('app').controller('CashOutCtrl', ['$scope', 'CashOut', 'Config_Status',
 	function($scope, CashOut, Config_Status) {
    // config
        
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.frm                  = {keyword : ''};
        $scope.time                 = {time_start : '', time_end : '', first_shipment_start : ''};
        $scope.time_start           = '';
        $scope.time_end             = '';
        $scope.list_data            = {};
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
            }
        };

        // action
        
        $scope.ChangeTab = function(tab){
            $scope.frm.tab  = tab;
            $scope.setPage();
        }
        
        $scope.refresh = function(){
            $scope.list_data        = [];
            $scope.total_all        = 0;
            $scope.total_group      = [];
            $scope.data_sum         = [];
            $scope.waiting          = true;
        }
        
        $scope.setPage = function(){
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
            
            $scope.refresh();
            CashOut.load($scope.currentPage, $scope.frm,'').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                    $scope.data_sum         = result.data.data_sum;
                }
                $scope.waiting          = false;
            });
            return;
        }
        
        //$scope.setPage();
    }
]);
