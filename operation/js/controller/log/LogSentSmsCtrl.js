'use strict';
angular.module('app')
.controller('LogSentSmsCtrl', ['$scope', '$http','$rootScope',
function($scope, $http, $rootScope) {
	$scope.currentPage  = 1;
    $scope.item_page    = 20;
    $scope.maxSize      = 5;
    $scope.totalItems   = 0;
    $scope.stateLoading = false;
    $scope.phone        = '';
    $scope.infoUser = $rootScope.userInfo;

    $scope.keys = function(obj){
        return obj? Object.keys(obj) : [];
    }

    // List 
    $scope.setPage = function(page){
        $scope.currentPage  = page;
        $scope.listData = [];
        $scope.stateLoading = true;

        var url = ApiOps+'log/log-sms?page='+page;

        if($scope.phone != undefined && $scope.phone != ''){
            url += '&phone='+$scope.phone;
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

