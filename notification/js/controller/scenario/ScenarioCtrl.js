'use strict';
angular.module('app')
.controller('ScenarioCtrl', ['$scope', '$modal', '$http', '$state', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state, $window, toaster, bootbox) {
	$scope.currentPage = 1;
    $scope.item_page = 20;
    // List 
    $scope.setPage = function(){
    	$http({
            url: ApiPath+'scenario?page='+$scope.currentPage,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listScenario = result.data;
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
    $scope.delScenario = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
            	$http({
                    url: ApiPath+'scenario/destroy/'+id,
                    method: "get",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
        			if(!result.error){
          				$scope.listScenario.splice(item, 1); 
        				toaster.pop('success', 'Thông báo', 'Thành công!');
        			}	       
        			else{
        				toaster.pop('error', 'Thông báo', 'Bạn không thể xoá!');
        			}
                });
            }
        });
    };
}]);