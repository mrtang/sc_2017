'use strict';
angular.module('app')
.controller('PostOfficeCtrl', ['$scope', '$modal', '$http', '$state', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state, $window, toaster, bootbox) {
	$scope.currentPage = 1;
    $scope.item_page = 20;
    $scope.listCourierC        = {1:'Viettelpost',2:'Bưu điện (VNP)',3:'Giaohangnhanh',4:'123giao',5:'Netco',6:'Giaohangtietkiem',7:'ShipChung',8:'Bưu điện (EMS)',9:'Goldtimes',10:'CityPost',11:'Kerry TTC'};
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
    //city
    $http({
        url: ApiPath+'city/cachecourier',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listCityCache = result.data;
        }
    });
	//
	$scope.setPage = function(currentPage,courier,courier_code){
        if(courier == undefined){
            courier = '';
        }
        if(courier_code == undefined){
            courier_code = '';
        }
    	$http({
            url: ApiPath+'post-office?page='+$scope.currentPage+'&courier='+courier+'&bccode='+courier_code,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listData = result.data;
		    $scope.totalItems = result.total;
            $scope.maxSize = 5;
            $scope.item_stt = $scope.item_page * ($scope.currentPage - 1);
        }
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu trả ra!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    };
    
    $scope.setPage();
    //Delete
    $scope.delOffice = function(id,index){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
                $http({
                    url: ApiPath+'post-office/destroy/'+id,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                    	$scope.listData.splice(index, 1); 
                        toaster.pop('success', 'Thông báo', 'Xoá thành công!');
                    }          
                    else{
                        toaster.pop('error', 'Thông báo', 'Không thể xoá dữ liệu!');
                    }
                });
            }
        });
    }





}]);