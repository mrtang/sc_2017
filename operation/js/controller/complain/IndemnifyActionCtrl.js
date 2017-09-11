'use strict';
angular.module('app')
.controller('IndemnifyActionCtrl', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster', 'bootbox', '$timeout',
function($scope, $modal, $http, $state, $window, $stateParams, toaster, bootbox, $timeout) {

	$scope.dynamic          = 0;
	$scope.listExcelLoading = true;

	$scope.setPage = function(){
    	$http({
            url: ApiPath + 'log/listindemnify/' + $stateParams.id,
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
    //
    $scope.action_update = function(data){
        console.log(data);

	    if(Object.keys(data).length > 0){
	        $scope.update_all   = true;
	        $scope.update_multi(data ,0);
	    }
	    return;
    }
    $scope.update_multi = function(data, num){
        $scope.dynamic  = num;
        if(data[num] && Object.keys(data[num]).length > 0  ){
            $http({
                url: ApiPath+'log/process/'+$stateParams.id,
                method: "GET",
                dataType: 'json',
            }).success(function (result, status, headers, config) {
                if(result.data.total > 0) {
                    if (!result.data.error) {
                        toaster.pop('success', 'Thông báo', 'Thành công !');
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Lỗi');
                    }
                }
                $scope.update_multi(data,+num+1);
            });
        }else{
           $scope.update_all    = false;
           toaster.pop('success', 'Thông báo', 'Kết thúc !'); 

           $timeout(function (){
                $state.go('shipchung.complain.indemnifyprocess',{id:$stateParams.id});
           }, 1000);
           
        }
    }

}]);