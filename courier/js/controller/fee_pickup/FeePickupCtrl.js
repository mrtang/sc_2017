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
    //Load Courier
    $scope.listCourierSelect = [{"id":1,"name":"Viettelpost"},{"id":2,"name":"Bưu điện (VNP)"},{"id":3,"name":"Giaohangnhanh"},{"id"
:4,"name":"123giao"},{"id":5,"name":"Netco"},{"id":6,"name":"Giaohangtietkiem"},{"id":7,"name":"ShipChung"},{"id":8,"name":"Bưu điện (EMS)"},{"id":9,"name":"Goldtimes"},{"id":10,"name":"CityPost"},{"id":11,"name":"Kerry TTC"}];

	$scope.setPage = function(currentPage,courier){
        if(courier == undefined){
            courier = '';
        }
    	$http({
            url: ApiPath+'fee-pickup?page='+$scope.currentPage+'&courier='+courier,
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
    //
    $scope.updateData = function(data,field,id) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'fee-pickup/edit/'+id,
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
    //Delete
    $scope.delFee = function(id,index){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
                $http({
                    url: ApiPath+'fee-pickup/destroy/'+id,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    $scope.listData.splice(index, 1); 
                    if(!result.error){
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