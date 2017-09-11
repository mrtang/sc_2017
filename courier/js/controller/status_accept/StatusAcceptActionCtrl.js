'use strict';
angular.module('app')
.controller('StatusAcceptActionCtrl', ['$scope', '$modal', '$http', '$state', '$window','$stateParams', 'toaster', 'bootbox',
function($scope, $modal, $http, $state, $window,$stateParams, toaster, bootbox) {
	//load status
	$http({
        url: ApiPath+'order-status/statussystem',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listStatus = result.data;
        }
    });
    //save
    $scope.saveData = function(data){
    	//
        $http({
            url: ApiPath+'status-accept/create',
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', result.message);
            }
            else{
                toaster.pop('error', 'Thông báo', result.message);
            }
        });
    }
    //
    if(parseInt($stateParams.id) > 0){
    	$scope.id = parseInt($stateParams.id);
        $http({
            url: ApiPath+'status-accept/show/'+$stateParams.id,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.data = result.data;
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
        //
        $scope.saveEdit = function(data){
	        $http({
	            url: ApiPath+'status-accept/edit/'+parseInt($stateParams.id),
	            method: "POST",
	            data:data,
	            dataType: 'json'
	        }).success(function (result, status, headers, config) {
	            if(!result.error){
	                toaster.pop('success', 'Thông báo', result.message);
	            }
	            else{
	                toaster.pop('error', 'Thông báo', result.message);
	            }
	        });
	    }
    }


}]);