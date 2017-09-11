'use strict';
//
angular.module('app').controller('UserCtrl', ['$scope', '$modal', '$http', '$state', '$window', 'toaster',
function($scope, $modal, $http, $state, $window, toaster) {
	$scope.currentPage = 1;
    $scope.item_page = 20;
    // List 
    $scope.setPage = function(keyword){
        if(keyword == undefined){
            keyword = '';
        }
    	$http({
            url: ApiPath+'user?page='+$scope.currentPage+'&search='+keyword,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.dataResult = result.data;
            //
		    $scope.totalItems = result.total;
			$scope.maxSize = 5;
            $scope.item_page = result.item_page;
            $scope.item_stt = $scope.item_page * ($scope.currentPage - 1);
        }        
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
        });
    };
    $scope.setPage();

}]);