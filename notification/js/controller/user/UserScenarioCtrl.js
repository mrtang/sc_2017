'use strict';
angular.module('app')
.controller('UserScenarioCtrl', ['$scope', '$modal', '$http', '$state','$stateParams', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state,$stateParams, $window, toaster, bootbox) {
	var list_scenario_user = [];
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
        //list scenario by user
        $http({
            url: ApiPath+'user-scenario-config/scenarioconfigbyuserid/'+$stateParams.id,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.list_scenario_user = result.data;
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
    //list scenario
    $http({
        url: ApiPath+'scenario',
        method: "GET",
        dataType: 'json',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).success(function (result, status, headers, config) {
    if(!result.error){
        $scope.listScenario = result.data;
    }        
    else{
        toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
    }
    }).error(function (data, status, headers, config) {
        toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
    });
    //save
    $scope.saveUserScenario = function(scenario_id,transport_id){
    	$http({
            url: ApiPath+'user-scenario-config/create',
            method: "POST",
            data:{'user_id':$stateParams.id,'transport_id':transport_id,'scenario_id' : scenario_id},
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
    //
    $scope.setActive = function(status,field,id) {
        var myData = {};
        myData[field] = status;
        $http({
            url: ApiPath+'user-scenario-config/edit/'+id,
            method: "POST",
            data:myData,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
            }          
            else{
                toaster.pop('error', 'Thông báo', 'Không thể cập nhật dữ liệu!');
            }
        });
    };
    //
    $scope.delScenarioConfig = function(id,item){
        bootbox.confirm("Bạn có chắc chắn muốn xóa ?", function (result) {
            if(result){
            	$http({
                    url: ApiPath+'user-scenario-config/destroy/'+id,
                    method: "get",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
        			if(!result.error){
          				$scope.list_scenario_user.splice(item, 1); 
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