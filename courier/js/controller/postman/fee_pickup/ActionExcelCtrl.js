'use strict';
angular.module('app')
.controller('ActionExcelCtrl', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster', 'bootbox',
function($scope, $modal, $http, $state, $window, $stateParams, toaster, bootbox) {
	$scope.dynamic          = 0;
    $scope.list_data = {};
    $scope.create_all       = false;
    //define
    $scope.listService        = {1:'Chuyển phát tiết kiệm',2:'Chuyển phát nhanh'};
    $scope.listCourier        = {1:'Viettelpost',2:'Bưu điện (VNP)',3:'Giaohangnhanh',4:'123giao',5:'Netco',6:'Giaohangtietkiem',7:'ShipChung',8:'Bưu điện (EMS)',9:'Goldtimes',10:'CityPost',11:'Kerry TTC'};
    $scope.listFrom           = {1:'Nội thành',2:'Ngoại thành',3:'Huyện xã'};
    $scope.listTo             = {1:'Nội thành',2:'Liên tỉnh',3:'Nội thành cùng quận',4:'Nội thành khác quận',5:'Huyện xã'};

	$scope.setPage = function(){
    	$http({
            url: ApiPath+'fee-pickup/listexcel/'+$stateParams.id,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.list_data = result.data;
		    $scope.totalItems = result.total;
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
    $scope.id = $stateParams.id;
    //
    $scope.action_add = function(data){
        $scope.create_all   = true;
        $scope.create_multi(data,0);
    }
       
    $scope.create_multi = function(data,num){
        $scope.dynamic  = num;
        if(data){
            $http({
            url: ApiPath+'fee-pickup/process/'+$stateParams.id,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
	        if(!result.error){
	            $scope.create_multi(data,+num+1);
	        }
            if(result.code == 2){
                toaster.pop('success', 'Thông báo', 'Kết thúc !'); 
            }
        	});
        }else{
           $scope.create_all    = false;
           toaster.pop('success', 'Thông báo', 'Kết thúc !'); 
        }
    }



}]);