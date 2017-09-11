'use strict';
angular.module('app')
.controller('SystemScenarioConfigCtrl', ['$scope', '$modal', '$http', '$state','$stateParams', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state,$stateParams, $window, toaster, bootbox) {
	var sysConfig = [];
	var listTransport = [];
	//info scenario
	if(parseInt($stateParams.id) > 0){
        $http({
            url: ApiPath+'scenario/show/'+$stateParams.id,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.scenario = result.data;
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
        //
        $http({
            url: ApiPath+'system-scenario-config/transport/'+$stateParams.id,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.sysConfig = result.data;
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
    }
    //
    $scope.setAction = function(id){
        $http({
            url: ApiPath+'system-scenario-config/action',
            method: "POST",
            data:{'scenario_id':$stateParams.id,'transport_id':id,'active' : 1},
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
}]);