'use strict';
angular.module('app')
.controller('LoyHistoryCtrl', ['$scope', 'dateFilter', 'Loyalty',
function($scope, dateFilter, Loyalty) {
    $scope.totalItems   = 0;
    $scope.frm          = {};
    $scope.time         = {time_start : '', time_end : ''};

    $scope.setPage = function(page){
        $scope.currentPage  = page;
        if($scope.time.time_start != undefined && $scope.time.time_start != ''){
            $scope.frm.time_start   = dateFilter($scope.time.time_start, 'MM-yyyy');
        }else{
            $scope.frm.time_start   = '';
        }
        if($scope.time.time_end != undefined && $scope.time.time_end != ''){
            $scope.frm.time_end     = dateFilter($scope.time.time_end, 'MM-yyyy');
        }else{
            $scope.frm.time_end     = 0;
        }
        
        Loyalty.history($scope.currentPage,$scope.frm).then(function (result) {
            if(!result.data.error){
                $scope.list_data        = result.data.data;
                $scope.totalItems       = result.data.total;
                $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
            }
            $scope.waiting  = false;
        });
        return;
    }
}]);

