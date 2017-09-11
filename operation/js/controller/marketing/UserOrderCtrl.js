'use strict';

angular.module('app').controller('UserOrderCtrl', ['$scope', '$modal', '$http', '$state', '$window', 'toaster','$timeout', '$rootScope',
function($scope, $modal, $http, $state, $window, toaster, $timeout, $rootScope) {

	$scope.setPage = function(num){
        $scope.listData = [];
        $scope.stateLoading = true;
        
        $http({
            url: ApiOps+'marketing/userorder/' + num ,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listData = result.data;
            $scope.listCount = result.counts;
        }        
        else{
            $scope.listData = [];
        }
        $scope.stateLoading = false;
        });
    };
    $scope.export = function(num){
    	var url = ApiOps + 'marketing/export?cmd=export';

        if(num != undefined && num != ''){
            url += '&num='+num;
        }

        return url;
    }

}]);