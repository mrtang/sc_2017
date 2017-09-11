'use strict';

//Provider report
angular.module('app').controller('ReportOrderCtrl', ['$scope', 'Report', 'dateFilter',
 	function($scope, Report, dateFilter) {
        
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;
        $scope.totalItems           = 0;
        $scope.list_bonus           = {0: 0, 8 : 1, 9 : 3, 10 : 3, 11 : 0, 12 : 5, 1 : 2, 2 : 6, 3 : 5, 4: 2, 5 : 0};
        $scope.month                = 0;
        $scope.list_bonus_day       = [];
        
        $scope.time                 = {from_day : '', to_day : '', month : ''};
        $scope.sort_value           = false;
        $scope.frm                  = {search : '', sort_date: '', sort_value: ''};
        $scope.list_date            = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31];

        $scope.list_data            = {};
        $scope.waiting              = false;

        $scope.refresh = function(cmd){
            if($scope.time.month != undefined && $scope.time.month != ''){
                $scope.frm.month            = dateFilter($scope.time.month, 'MM-yyyy');
                $scope.month                = 1*dateFilter($scope.time.month, 'MM');
                $scope.list_bonus_day       = [$scope.list_bonus[$scope.month]*1, $scope.list_bonus[$scope.month]*1 + 1, $scope.list_bonus[$scope.month]+ 7*1,  $scope.list_bonus[$scope.month] + 7*1 + 1, $scope.list_bonus[$scope.month] + 7*2, $scope.list_bonus[$scope.month]  + 7*2 + 1,$scope.list_bonus[$scope.month]+ 7*3, $scope.list_bonus[$scope.month] + 7*3 + 1, $scope.list_bonus[$scope.month] + 7*4, $scope.list_bonus[$scope.month] + 7*4 + 1];
            }else{
                $scope.frm.month            = 0;
                $scope.list_bonus_day       = [];
            }
            
            if($scope.sort_value){
                $scope.frm.sort_value   = "DESC";
            }else{
                $scope.frm.sort_value   = "ASC";
            }

            if(cmd != 'export'){
                $scope.list_data = {};
                $scope.waiting          = true;
            }

        }

        $scope.sort = function(i){
            if (i == $scope.frm.sort_date) {
                $scope.sort_value = !$scope.sort_value;
            } else {
                $scope.sort_value = false;
            }

            $scope.frm.sort_date = i;
            $scope.currentPage = 1;
            $scope.setPage();
        }
        
        $scope.setPage = function(){
            $scope.refresh('');
            Report.order($scope.currentPage,$scope.frm, '').then(function (result) {
                if(!result.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                }
                
                $scope.waiting  = false;
            });
            return;
        }

        $scope.exportExcel = function(){
            $scope.refresh('export');
            return Report.order(1,$scope.frm,'export');
        }
    }
]);
