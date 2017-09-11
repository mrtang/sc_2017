'use strict';
//
var listPrivilegeGroup = {};
angular.module('app').controller('GroupDetailCtrl', ['$scope', '$modal', '$http', '$state','$stateParams', '$window', 'toaster','bootbox',
function($scope, $modal, $http, $state,$stateParams, $window, toaster,bootbox) {
	//
	$scope.listPrivilegeAdded = function(){
		$http({
            url: ApiPath+'group-privilege/privilegebygroup/'+$stateParams.group_id,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listPrivilegeGroup = result.data;
        }        
        });
	}

	$scope.listPrivilegeAdded();

	//
	$scope.setPrivilegeGroup = function(privilege){
		$http({
            url: ApiPath+'group-privilege/action',
            method: "POST",
            data:{'privilege':privilege,'group':$stateParams.group_id},
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
            }          
            else{
                toaster.pop('error', 'Thông báo', 'Bạn không thể tạo mới!');
            }
        });
	}


}]);