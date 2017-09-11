'use strict';
var list_service = [];

//Courier
 angular.module('app')
 .controller('CourierActionCtrl', ['$scope','$modal','$http','$state','$stateParams', '$window','toaster', 
 	function($scope,$modal,$http,$state,$stateParams,$window,toaster) {
    $scope.CourierData  = {};
    $scope.services  = [];
    $scope.CourierId    = parseInt($stateParams.id);
    // List courier Type
    $http({
        url: ApiPath+'courier-type/index/1',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listCourierType = result.data;
        }          
        else{
            toaster.pop('warning', "Warning!", "Error Server.");
        }
    });
    
    //Them moi - sua xoa
    $scope.updateCourierType = function (data,service) {
        var myData = new Array();
        data.services = service;
        myData.push(data);
        $http({
            url: ApiPath+'courier/' + (parseInt($stateParams.id) > 0 ? 'edit/'+$stateParams.id : 'create'),
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
    };
    
    // Get 1 courier
    if(parseInt($stateParams.id) > 0){
        $http({
            url: ApiPath+'courier/show/'+$stateParams.id,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.form = result.data;
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
    }
    // List service//List service group
    $scope.get_list_service  = function(){
        $http({
            url: ApiPath+'courier-service',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            list_service = result.data;
            $scope.list_service = list_service;
        }        
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.get_list_service();
    
}]);