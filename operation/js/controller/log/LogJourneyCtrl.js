'use strict';
angular.module('app')
.controller('LogJourneyCtrl', ['$scope', '$http', 'Config_Status',
function($scope, $http, Config_Status) {
    $scope.currentPage  = 1;
    $scope.item_page    = 20;
    $scope.maxSize      = 5;
    $scope.sc_code      = '';
    $scope.status       = '';
    $scope.courier_status       = '';
    $scope.courier_id       = '';
    $scope.totalItems   = 0;
    $scope.list_color   = Config_Status.order_color;
    $scope.stateLoading = false;


    $scope.time         = {create_start : '', create_end : ''};

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1
    };


    // List
    $scope.keys = function(obj){
        return obj? Object.keys(obj) : [];
    }

    $scope.setPage = function(page){
        $scope.currentPage  = page;
        $scope.listData     = [];
        $scope.stateLoading = true;

        var url = ApiOps+'log/journey?page='+page;

        if($scope.sc_code != undefined && $scope.sc_code != ''){
            url += '&sc_code='+$scope.sc_code;
        }
        if($scope.status != undefined && $scope.status > 0){
            url += '&status='+$scope.status;
        }

        if($scope.courier_status != undefined && $scope.courier_status > 0){
            url += '&courier_status='+$scope.courier_status;
        }

        if($scope.courier_id != undefined && $scope.courier_id > 0){
            url += '&courier='+$scope.courier_id;
        }

        if($scope.time.create_start != undefined && $scope.time.create_start != ''){
            url += '&time_start='+ 1*Date.parse($scope.time.create_start)/1000;
        }

        if($scope.time.create_end != undefined && $scope.time.create_end != ''){
            url += '&time_end='+ (1*Date.parse($scope.time.create_end)/1000 + 86399);
        }



        $http({
            url: url,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listData     = result.data;
            $scope.totalItems   = result.total;
            $scope.item_stt     = $scope.item_page * ($scope.currentPage - 1);
        }

        $scope.stateLoading = false;
        });
    };
}]);

