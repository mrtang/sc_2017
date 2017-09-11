'use strict';
angular.module('app')
.controller('ScenarioTemplateActionCtrl', ['$scope', '$modal', '$http', '$state','$stateParams', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state,$stateParams, $window, toaster, bootbox) {
	var tempScenario = [];
	var listTemplate = [];
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
        $http({
            url: ApiPath+'scenario-template/templatebyscenario/'+$stateParams.id,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.tempScenario = result.data;
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
    }
    //
    $scope.list_template = function(){
    	$http({
            url: ApiPath+'template/templatebytype',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listTemplate = result.data;
        }
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
        });
    };
    $scope.list_template();
    //
    $scope.setAction = function(id){
        $http({
            url: ApiPath+'scenario-template/action',
            method: "POST",
            data:{'scenario_id':$stateParams.id,'template_id':id},
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