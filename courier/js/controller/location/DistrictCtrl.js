'use strict';
var city            = [];
var list_city       = [];

//District
 angular.module('app')
 .controller('DistrictCtrl', ['$scope','$modal','$http','$state','$window','$stateParams','toaster','bootbox', 
 	function($scope,$modal,$http,$state,$window,$stateParams,toaster,bootbox) {
    
    // config
 	$scope.currentPage    = 1;
    $scope.item_page      = 20;
    $scope.list_district  = [];
    $scope.city           = city;
    $scope.list_city      = list_city;
    
    // List City
    $scope.get_city  = function(){
        $http({
            url: ApiPath+'city?limit=all',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            list_city           = result.data;
            $scope.list_city    = list_city;
            $.each($scope.list_city, function(index,value){
                city[value.id]  = {id : value.id, city_name : value.city_name};
            });
            $scope.city     = city;
            
            if(city[$stateParams.city_id]){
                $scope.search_city    = city[$stateParams.city_id];
            }
            
            $scope.GetDistrict();
        }  
        else{
            toaster.pop('error', 'Thông báo', 'Tải danh sách thành phố lỗi !');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.get_city();
    
    // List District
    $scope.GetDistrict = function(){
        var url_link = ApiPath+'district?page='+$scope.currentPage;
        if($scope.search_city){
            url_link = url_link+'&city_id='+$scope.search_city['id'];
        }
        if($scope.search_district){
            url_link = url_link+'&district_name='+$scope.search_district;
        }
        
    	$http({
            url: url_link,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.list_district= result.data;
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
    
    // Cập nhật District
    $scope.change = function(id,data,field) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'district/edit/'+id,
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
    
    //Xoa City
    $scope.del = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
            	$http({
                    url: ApiPath+'district/destroy/'+id,
                    method: "get",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
        			if(!result.error){
        				$scope.list_district.splice(item, 1);
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
            controller: 'ModalCreateDistrictCtrl',
            size:size
        });
    };
    
}]);

angular.module('app').controller('ModalCreateDistrictCtrl', ['$scope', '$modalInstance', '$http', 'toaster',
function($scope, $modalInstance, $http, toaster) {
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
    
    $scope.list_city    = list_city;
    //Them moi
    $scope.Create = function (data) {
        if(data['city_id']['id']){
            var DataForm = {city_id : data['city_id']['id'], district_name : data['district_name']}
            $http({
                url: ApiPath+'district/create',
                method: "POST",
                data:DataForm,
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
        }else{
            toaster.pop('warning', 'Error!', 'Tỉnh/Thành Phố không chính xác !');
        }
        return;
    };
    
}]);