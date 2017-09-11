'use strict';
angular.module('app')
.controller('TemplateActionCtrl', ['$scope', '$modal', '$http', '$state','$stateParams', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state,$stateParams, $window, toaster, bootbox) {
	//Load transport
	$scope.get_list_transport  = function(){
        $http({
            url: ApiPath+'transport',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.list_transport = result.data;
        }        
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.get_list_transport();
	//create-save
	$scope.saveTemplate = function(data){
		$http({
            url: ApiPath+'template/' + (parseInt($stateParams.id) > 0 ? 'edit/'+$stateParams.id : 'create'),
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
	//get 1 template
    if(parseInt($stateParams.id) > 0){
        $http({
            url: ApiPath+'template/show/'+$stateParams.id,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.template = result.data;
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
    }
}]);