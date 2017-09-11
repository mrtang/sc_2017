'use strict';
angular.module('app')
.controller('QueueCtrl', ['$scope', '$modal', '$http', '$state','$stateParams', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state,$stateParams, $window, toaster, bootbox) {
	$scope.currentPage = 1;
    $scope.item_page = 20;
    // List 
    $scope.setPage = function(key){
        if(key == undefined){
            key = '';
        }
    	$http({
            url: ApiPath+'queue?page='+$scope.currentPage+'&search='+key,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listQueue = result.data;
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