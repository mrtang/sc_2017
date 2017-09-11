'use strict';
angular.module('app')
.controller('UserTransportCtrl', ['$scope', '$modal', '$http', '$state','$stateParams', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state,$stateParams, $window, toaster, bootbox) {
	var list_transport_user = [];
	//get 1user
	if(parseInt($stateParams.id) > 0){
        $http({
            url: ApiPath+'user/show/'+$stateParams.id,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.info_user = result.data;
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
        //list transport by user
        $http({
            url: ApiPath+'user-config-transport/transportconfigbyuserid/'+$stateParams.id,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.list_transport_user = result.data;
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
    }
    // list transport
    $http({
        url: ApiPath+'transport',
        method: "GET",
        dataType: 'json',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).success(function (result, status, headers, config) {
    if(!result.error){
        $scope.listTransport = result.data;
    }        
    else{
        toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
    }
    }).error(function (data, status, headers, config) {
        toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
    });
    //
    $scope.setAction = function(id){
        $http({
            url: ApiPath+'user-config-transport/action',
            method: "POST",
            data:{'user_id':$stateParams.id,'transport_id':id},
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
            }          
            else{
                toaster.pop('error', 'Thông báo', 'Bạn không thể thao tác!');
            }
        });
    }
    //Save
    $scope.saveUserConfigTransport = function(id,active){
    	$http({
            url: ApiPath+'user-config-transport/create',
            method: "POST",
            data:{'user_id':$stateParams.id,'transport_id':id,'active' : active},
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error && result.message == 'success'){
                toaster.pop('success', 'Alert!', 'Thành công!');
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
    }

}]);