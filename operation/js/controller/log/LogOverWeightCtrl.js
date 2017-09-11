'use strict';
angular.module('app')
.controller('LogOverWeightCtrl', ['$scope', '$http', '$window', 'Config_Status',
function($scope, $http, $window, Config_Status) {
	$scope.currentPage  = 1;
    $scope.item_page    = 20;
    $scope.maxSize      = 5;
    $scope.totalItems   = 0;
    $scope.stateLoading = false;
    $scope.frm          = {status : 0};
    $scope.time         = {create_start : '', create_end : ''};
    $scope.status_verify        = Config_Status.StatusVerify;

    $scope.keys = function(obj){
        return obj? Object.keys(obj) : [];
    }

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1
    };

    $scope.open = function($event,type) {
        $event.preventDefault();
        $event.stopPropagation();
        if(type == "time_create_start_open"){
            $scope.time_create_start_open = true;
        }else if(type == "time_accept_end_open"){
            $scope.time_accept_end_open = true;
        }
    };

    // List 
    $scope.setPage = function(page,cmd){
        if(cmd != 'export'){
            $scope.currentPage  = page;
            $scope.listData = [];
            $scope.stateLoading = true;
        }

        var url = ApiOps+'log/over-weight?page='+page;

        if($scope.time.create_start != undefined && $scope.time.create_start != ''){
            url += '&create_start='+ 1*Date.parse($scope.time.create_start)/1000;
        }

        if($scope.time.create_end != undefined && $scope.time.create_end != ''){
            url += '&create_end='+ (1*Date.parse($scope.time.create_end)/1000 + 86399);
        }

        if($scope.frm.tracking_code != undefined && $scope.frm.tracking_code != ''){
            url += '&tracking_code='+$scope.frm.tracking_code;
        }

        if($scope.frm.status != undefined && $scope.frm.status != ''){
            url += '&status='+$scope.frm.status;
        }

        if (cmd != undefined && cmd != '') {
            url += '&cmd=' + cmd;
            $window.open(url, '_blank');
            return '';
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
            $scope.item_stt     = $scope.item_page * (page - 1);
        }
        $scope.stateLoading = false;
        });
    };
}]);

