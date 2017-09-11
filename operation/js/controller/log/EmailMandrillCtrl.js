'use strict';
angular.module('app')
.controller('EmailMandrillCtrl', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster',
function($scope, $modal, $http, $state, $window, $stateParams, toaster) {
	$scope.currentPage  = 1;
    $scope.item_page    = 20;
    $scope.maxSize      = 5;
    $scope.totalItems   = 0;
    $scope.stateLoading = false;
    $scope.email        = '';

    $scope.keys = function(obj){
        return obj? Object.keys(obj) : [];
    }

    // List 
    $scope.setPage = function(page){
        $scope.currentPage  = page;
        $scope.listData = [];
        $scope.stateLoading = true;

        var url = ApiOps+'log/emailreject?page='+page;

        if($scope.email != undefined && $scope.email != ''){
            url += '&email='+$scope.email;
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