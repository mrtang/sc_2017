'use strict';
var list_region = [{code : '1', name : 'Miền Bắc'},{code : '2', name : 'Miền Trung'},{code : '3' , name : 'Miền Nam'}];
var list_city   = [];
//City
 angular.module('app')
 .controller('CityCtrl', ['$scope','$filter','$modal','$http','$state','$window','toaster','bootbox', 
 	function($scope,$filter,$modal,$http,$state,$window,toaster,bootbox) {
    
    // config
 	$scope.currentPage    = 1;
    $scope.item_page      = 20;
    $scope.list_city      = list_city; 
    $scope.list_region    = list_region;
    
    // List City
    $scope.GetCity = function(name){
    //ApiPath+'city?page='+$scope.currentPage,
        var url_link = ApiPath+'city?page='+$scope.currentPage;
        if(name){
            url_link    = url_link+'&city_name='+name;
        }
        
    	$http({
            url: url_link,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            list_city           = result.data;
            $scope.list_city    = list_city;
            $scope.totalItems   = result.total;
            $scope.maxSize      = 5;
            $scope.item_stt     = $scope.item_page * ($scope.currentPage - 1);
        }        
        else{
            toaster.pop('warning', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    };
    $scope.GetCity('');
    
    // Cập nhật City
    $scope.change = function(id,data,field) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'city/edit/'+id,
            method: "POST",
            data:myData,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
            }          
            else{
                toaster.pop('warning', 'Thông báo', 'Thất bại!');
            }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    };
    
    $scope.showRegion = function(region) {
        var selected = [];
        if(region) {
          selected = $filter('filter')($scope.list_region, {code: region});
        }
        return selected.length ? selected[0].name : 'Not set';
    };
    
    //Xoa City
    $scope.del = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
            	$http({
                    url: ApiPath+'city/destroy/'+id,
                    method: "get",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
        			if(!result.error){
          				list_city.splice(item, 1); 
        				$scope.list_city    = list_city;
                        toaster.pop('success', 'Thông báo', 'Thành công!');
        			}	       
        			else{
        				toaster.pop('warning', 'Thông báo', 'Thất bại!');
        			}
                }).error(function (data, status, headers, config) {
                    toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
                });
                return;
            }
        });
        return;
    }
    
    $scope.$on('update',function(){
        $scope.list_city    = list_city;
    })
    
    // Open Popup
    $scope.open_popup = function(size){
        $modal.open({
            templateUrl: 'ModalCreate.html',
            controller: 'ModalCreateCityCtrl',
            size:size
        });
    };
    
}]);

angular.module('app').controller('ModalCreateCityCtrl', ['$scope', '$modalInstance', '$http', 'toaster',
function($scope, $modalInstance, $http, toaster) {
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
    $scope.list_region    = list_region;
    
    //Them moi
    $scope.CreateCity = function (data) {
        $http({
            url: ApiPath+'city/create',
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error && result.message == 'success'){
                if(list_city.length < 20){
                    list_city.push({'id' : result.id, 'code' : data.code, 'city_name' : data.city_name,'region' : data.region});
                    $scope.$emit('update');  // call to update to  CityCtrl
                }
                toaster.pop('success', 'Alert!', 'Thành công!');
            }          
            else{
                toaster.pop('warning', 'Error!', result.message);
            }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
    };
    
}]);