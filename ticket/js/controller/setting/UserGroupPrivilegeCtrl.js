'use strict';
//
var listUserGroup = {};
angular.module('app').controller('UserGroupPrivilegeCtrl', ['$scope', '$modal', '$http', '$state','$stateParams', '$window', 'toaster','bootbox',
function($scope, $modal, $http, $state,$stateParams, $window, toaster,bootbox) {
	//
	$scope.listGroupAdded = function(){
		$http({
            url: ApiPath+'user-group/groupbyuser/'+$stateParams.user_id,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listUserGroup = result.data;
        }        
        });
	}

	$scope.listGroupAdded();
	//
	$scope.setUserGroup = function(group){
		$http({
            url: ApiPath+'user-group/action',
            method: "POST",
            data:{'group':group,'user':$stateParams.user_id},
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