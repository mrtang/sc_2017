'use strict';
angular.module('app')
.controller('IndemnifyProcessCtrl', ['$scope', '$modal','$rootScope', '$http', '$state', '$window', '$stateParams', 'toaster', 'bootbox', '$timeout',
function($scope, $modal,$rootScope, $http, $state, $window, $stateParams, toaster, bootbox, $timeout) {

	$scope.setPage = function(){
    	$http({
            url: ApiOps + 'complain/listindemnify/' + $stateParams.id,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {

    	$scope.listExcelLoading = false;
    	
        if(!result.error){
            $scope.list_data = [];
            
            for(var property in result.data){
                $scope.list_data.push(result.data[property]);
            }
		    $scope.totalItems 	= result.total;
        }
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu trả ra!');
        }
        }).error(function (data, status, headers, config) {
        	$scope.listExcelLoading = false;
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    };
    
    $scope.setPage();
    $scope.id = $stateParams.id;
    $scope.infoUser = $rootScope.userInfo;
    //
    $scope.actionAccept = function(id){
    	$http({
            url: ApiOps + 'complain/accept/' + id,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
    	
        if(!result.error){
             toaster.pop('success', 'Thông báo', 'Thành công!');
        }
        else{
            toaster.pop('error', 'Thông báo', result.message);
        }
        }).error(function (data, status, headers, config) {
        	$scope.listExcelLoading = false;
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    };
    //export excel
    $scope.export = function (id){
        return  ApiOps + 'complain/exportexcel/' + id +'?cmd=export';
    }

}]);