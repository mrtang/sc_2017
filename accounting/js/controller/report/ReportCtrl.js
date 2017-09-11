'use strict';

//Provider report
angular.module('app').controller('ReportCtrl', ['$scope', 'Report', 'dateFilter',
 	function($scope, Report, dateFilter) {
        
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;
        $scope.totalItems           = 0;

        
        $scope.time                 = {from_day : '', to_day : '', month : ''};
        $scope.frm                  = {};

        $scope.list_data            = {};
        $scope.waiting              = false;
        $scope.list_sum             = {};

        $scope.refresh = function(cmd){
            if($scope.time.from_day != undefined && $scope.time.from_day != ''){
                $scope.frm.from_day           = dateFilter($scope.time.from_day, 'dd-MM-yyyy');
            }else{
                $scope.frm.from_day           = 0;
            }

            if($scope.time.to_day != undefined && $scope.time.to_day != ''){
                $scope.frm.to_day             = dateFilter($scope.time.to_day, 'dd-MM-yyyy');
            }else{
                $scope.frm.to_day             = 0;
            }

            if($scope.time.month != undefined && $scope.time.month != ''){
                $scope.frm.month            = dateFilter($scope.time.month, 'MM-yyyy');
            }else{
                $scope.frm.month            = 0;
            }

            if(cmd != 'export'){
                $scope.list_data = {};
                $scope.list_sum  = {};
                $scope.waiting          = true;
            }

        }
        
        $scope.setPage = function(){
            $scope.refresh('');
            Report.merchant($scope.currentPage,$scope.frm, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.list_sum         = result.data.data_sum;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting  = false;
            });
            return;
        }

        $scope.exportExcel = function(){
            $scope.refresh('export');
            return Report.merchant(1,$scope.frm,'export');
        }
    }
]);
