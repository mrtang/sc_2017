'use strict';
angular.module('app')
.controller('ScenarioActionCtrl', ['$scope', '$modal', '$http', '$state','$stateParams', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state,$stateParams, $window, toaster, bootbox) {
	//create-save
	$scope.saveScenario = function(data){
		$http({
            url: ApiPath+'scenario/' + (parseInt($stateParams.id) > 0 ? 'edit/'+$stateParams.id : 'create'),
            method: "POST",
            data:data,
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
	//get 1
    if(parseInt($stateParams.id) > 0){
        $http({
            url: ApiPath+'scenario/show/'+$stateParams.id,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.data = result.data;
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
    }
}]);