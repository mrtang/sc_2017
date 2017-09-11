'use strict';
angular.module('app')
.controller('EstimateCtrl', ['$scope', '$modal', '$http', '$state', '$window','$stateParams', 'toaster', 'bootbox',
function($scope, $modal, $http, $state, $window,$stateParams, toaster, bootbox) {
	$scope.currentPage = 1;
    $scope.item_page = 20;
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
    //load district
    $scope.loadDistrict = function(city_id){
    	$http({
	        url: ApiPath+'district?city_id='+city_id+'&limit=all',
	        method: "GET",
	        dataType: 'json'
	    }).success(function (result, status, headers, config) {
	        if(!result.error){
	            $scope.listDistrictByCity = result.data;
	        }
	    });
    }
    $scope.loadDistrictTo = function(city_id){
    	$http({
	        url: ApiPath+'district?city_id='+city_id+'&limit=all',
	        method: "GET",
	        dataType: 'json'
	    }).success(function (result, status, headers, config) {
	        if(!result.error){
	            $scope.listDistrictByCityTo = result.data;
	        }
	    });
    }
    //load district cache
    $http({
        url: ApiPath+'district/cachealldistrict',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        $scope.listDistrict = result;
    });
    //load city
    $http({
        url: ApiPath+'city?limit=all',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listCity = result.data;
        }
    });
    //
    $scope.listService        = {1:'Chuyển phát tiết kiệm',2:'Chuyển phát nhanh'};
    $scope.listCourierC = {1:'Viettelpost',2:'Bưu điện (VNP)',3:'Giaohangnhanh',4:'123giao',5:'Netco',6:'Giaohangtietkiem',7:'ShipChung',8:'Bưu điện (EMS)',9:'Goldtimes',10:'CityPost',11:'Kerry TTC'};
    //
    $scope.setPage = function(currentPage,courier,from_district,to_district){
        if(courier == undefined){
            courier = '';
        }
        if(from_district == undefined){
            from_district = '';
        }
        if(to_district == undefined){
            to_district = '';
        }
        $http({
            url: ApiPath+'courier-estimate?page='+$scope.currentPage+'&courier='+courier+'&from_district='+from_district+'&to_district='+to_district,
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
    //update status
    $scope.setActive = function(status,field,id) {
        var myData = {};
        myData[field] = status;
        $http({
            url: ApiPath+'courier-estimate/edit/'+id,
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

}]);