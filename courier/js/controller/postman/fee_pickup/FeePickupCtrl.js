'use strict';
angular.module('app')
.controller('FeePickupCtrl', ['$scope', '$modal', '$http', '$state', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state, $window, toaster, bootbox) {
	$scope.listService        = {1:'Chuyển phát tiết kiệm',2:'Chuyển phát nhanh'};
    $scope.listCourier        = {1:'Viettelpost',2:'Bưu điện (VNP)',3:'Giaohangnhanh',4:'123giao',5:'Netco',6:'Giaohangtietkiem',7:'ShipChung',8:'Bưu điện (EMS)',9:'Goldtimes',10:'CityPost',11:'Kerry TTC'};
    $scope.listFrom           = {1:'Nội thành',2:'Ngoại thành',3:'Huyện xã'};
    $scope.listTo             = {1:'Nội thành',2:'Liên tỉnh',3:'Nội thành cùng quận',4:'Nội thành khác quận',5:'Huyện xã'};
    $scope.currentPage = 1;
    $scope.item_page = 20;

	$scope.setPage = function(){
    	$http({
            url: ApiPath+'fee-pickup?page='+$scope.currentPage,
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
}]);