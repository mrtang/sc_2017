'use strict';
//Courier Fee
angular.module('app').controller('CourierFeeCtrl', ['$scope','$filter','$http','$state','$window','toaster','bootbox', 
 	function($scope,$filter,$http,$state,$window,toaster,bootbox) {
    
    // config
 	$scope.currentPage          = 1;
    $scope.item_page            = 20;
    $scope.list_courier_fee     = [];
    $scope.list_courier         = [];
    $scope.list_service         = [];
    $scope.list_area            = [];
    $scope.search_courier       = 0;
    $scope.liststate    = {1:'pickup',2:'delivery',3:'return'};
    
    //Load Courier
    $http.get(ApiPath+'courier?limit=all').success(function(result){$scope.list_courier = result.data;});
    
    // Load Service
    $http.get(ApiPath+'courier-service').success(function(result){
        if(result.data){
            angular.forEach(result.data, function(value, key) {
              $scope.list_service[value.id] = value.name;
            });
        }
    });
    
    // Load Area
    $http.get(ApiPath+'courier-area').success(function(result){
        if(result.data){
            angular.forEach(result.data, function(value, key) {
              $scope.list_area[value.id] = value.name;
            });
        }
    });
    
    // List Courier Fee
    $scope.GetCourierFee = function(){
        if($scope.search_courier > 0){
        	$http({
                url: ApiPath+'courier-fee?page='+$scope.currentPage+'&courier_id='+$scope.search_courier,
                method: "GET",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.list_courier_fee     = result.data;
                $scope.totalItems           = result.total;
                $scope.maxSize              = 5;
                $scope.item_stt             = $scope.item_page * ($scope.currentPage - 1);
                if(parseInt(result.total) == 0){
                    toaster.pop('warning', 'Thông báo', 'Không có dữ liệu!');
                }
            }        
            else{
                toaster.pop('warning', 'Thông báo', 'Không có dữ liệu!');
            }
            }).error(function (data, status, headers, config) {
                toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
            });
        }
        return;
    };
    
    // Cập nhật
    $scope.change = function(id,data,field) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'courier-fee/edit/'+id,
            method: "POST",
            data:myData,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
            }          
            else{
                toaster.pop('error', 'Thông báo', 'Thất bại!');
            }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    };
    
    //Xoa
    $scope.del = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
            	$http({
                    url: ApiPath+'courier-fee/destroy/'+id,
                    method: "get",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
        			if(!result.error){
          				$scope.list_courier_fee.splice(item, 1); 
        				toaster.pop('success', 'Thông báo', 'Thành công!');
        			}	       
        			else{
        				toaster.pop('error', 'Thông báo', 'Thất bại!');
        			}
                }).error(function (data, status, headers, config) {
                    toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
                });
                return;
            }
        });
        return;
    }
}]);