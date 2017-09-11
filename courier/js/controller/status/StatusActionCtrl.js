'use strict';
angular.module('app')
.controller('StatusActionCtrl', ['$scope', '$modal', '$http', '$state', '$window','$stateParams', 'toaster', 'bootbox',
function($scope, $modal, $http, $state, $window,$stateParams, toaster, bootbox) {
	//load courier
    $http({
        url: ApiPath+'courier',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listCourier = result.data;
        }
    });
    //load status
    $http({
        url: ApiPath+'list_status',
        method: "GET",
        dataType: 'json'
    }).success(function (result) {
        $scope.listStatusSC = result;
    });
    //save
    $scope.saveData = function(data){
    	//
        $http({
            url: ApiPath+'courier-status/create',
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
    // Get info office
    if(parseInt($stateParams.id) > 0){
    	$scope.id = parseInt($stateParams.id);
        $http({
            url: ApiPath+'courier-status/show/'+$stateParams.id,
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
	            url: ApiPath+'courier-status/edit/'+parseInt($stateParams.id),
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