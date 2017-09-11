'use strict';
angular.module('app')
.controller('LogChangePaymentCtrl', ['$scope', '$http',
function($scope, $http) {
	$scope.currentPage  = 1;
    $scope.item_page    = 20;
    $scope.sc_code      = '';
    $scope.totalItems   = 0;
    $scope.maxSize      = 5;
    $scope.listData     = [];
    $scope.order        = [];
    $scope.stateLoading = false;
    // List

    $scope.keys = function(obj){
        return obj? Object.keys(obj) : [];
    }

    $scope.type = {
        priority_payment : 'Trạng thái',
        email_nl         : 'Email ngân lượng'
    };


    $scope.setPage = function(page){
        $scope.currentPage  = page;
        $scope.listData     = [];
        $scope.order        = [];
        $scope.user         = {}
        $scope.stateLoading = true;

        var url = ApiOps+'log/log-change-payment?page='+page;

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
            $scope.listData = result.data;
            $scope.user     = result.user;
		    $scope.totalItems = result.total;
			$scope.maxSize = 5;
            $scope.item_stt = $scope.item_page * (page - 1);
        }

        $scope.stateLoading = false;
        });
    };
}]);