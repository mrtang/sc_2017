'use strict';
var list_city = [];
var city      = [];
var district  = [];

//Ward
 angular.module('app')
 .controller('WardCtrl', ['$scope','$filter','$modal','$http','$state','$window','$stateParams','toaster','bootbox', 
 	function($scope,$filter,$modal,$http,$state,$window,$stateParams,toaster,bootbox) {
    
    // config
 	$scope.currentPage    = 1;
    $scope.item_page      = 20;
    $scope.list_city      = list_city;
    $scope.list_district  = [];
    $scope.list_ward      = [];
    $scope.city           = city;
    $scope.district       = district;
    
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
                $scope.onSelectCity($stateParams.city_id);
            }
        }  
        else{
            toaster.pop('warning', 'Thông báo', 'Tải danh sách phường xã lỗi !');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.get_city();
    
    $scope.onSelectCity = function ($item, $model, $label) {
        var id;
        if($item.id){
            id = $item.id;
        }else{
            id = $item;
        }
        
        $http({
            url: ApiPath+'district?city_id='+id+'&limit=all',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.list_district = result.data;
            
            if($stateParams.district_id){
                angular.forEach($scope.list_district,function(value, key){
                    if(parseInt(value.id) == parseInt($stateParams.district_id)){
                        $scope.search_district    = value;
                    }
                })
                $scope.GetWard();
            }   
        }   
        else{
            toaster.pop('warning', 'Thông báo', 'Tải danh sách quận huyện lỗi !');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    };
    
    // List Ward
    $scope.GetWard = function(){
        var url_link = ApiPath+'ward?page='+$scope.currentPage;
        // search
        if($scope.search_city){
            url_link    = url_link+'&city_id='+$scope.search_city['id'];
        }
        if($scope.search_district){
            url_link    = url_link+'&district_id='+$scope.search_district['id'];
        }
        if($scope.search_ward){
            url_link    = url_link+'&ward_name='+$scope.search_ward;
        }
        
    	$http({
            url: url_link,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.list_ward    = result.data;
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
    
    if(!$stateParams.district_id){
        $scope.GetWard();
    }
    
    // Load District
    $scope.loadDistrict = function(id){
        
        $http({
            url: ApiPath+'district?limit=all&city_id='+id,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.list_district_table = result.data;
            district    = [];
            $.each($scope.list_district_table, function(index,value){
                district[value.id]  = {id : value.id, district_name : value.district_name};
            });
            $scope.district     = district;
        }   
        else{
            toaster.pop('warning', 'Thông báo', 'Tải danh sách quận huyện lỗi !');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    
    
  
    // Cập nhật City
    $scope.change = function(id,data,field) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'ward/edit/'+id,
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
    
    //Xoa Ward
    $scope.del = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
            	$http({
                    url: ApiPath+'ward/destroy/'+id,
                    method: "get",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
        			if(!result.error){
          				$scope.list_ward.splice(item, 1);
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
    
    // Open Popup
    $scope.open_popup = function(size){
        $modal.open({
            templateUrl: 'ModalCreate.html',
            controller: 'ModalCreateWardCtrl',
            size:size
        });
    };
    
}]);

angular.module('app').controller('ModalCreateWardCtrl', ['$scope', '$modalInstance', '$http', 'toaster',
function($scope, $modalInstance, $http, toaster) {
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
    
    $scope.list_city        = list_city;
    $scope.list_district    = [];
    // Load District
    $scope.loadDistrict = function ($item, $model, $label) {
        $http({
            url: ApiPath+'district?city_id='+$item.id,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.list_district = result.data;
        }   
        else{
            toaster.pop('warning', 'Thông báo', 'Tải danh sách quận huyện lỗi !');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    };
    
     //Them moi
    $scope.Create = function (data) {
        if(data['city_id']['id'] && data['district_id']['id']){
            var DataForm = {city_id : data['city_id']['id'], district_id : data['district_id']['id'], ward_name : data['ward_name']};
            $http({
                url: ApiPath+'ward/create',
                method: "POST",
                data:DataForm,
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(!result.error && result.message == 'success'){
                    toaster.pop('success', 'Alert!', 'Thành công!');
                }          
                else{
                    toaster.pop('warning', 'Error!', result.message);
                }
            }).error(function (data, status, headers, config) {
                toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
            });
        }else{
            toaster.pop('warning', 'Error!', 'Tỉnh/Thành Phố hoặc Quận Huyện không chính xác !');
        }
        return;
    };
    
}]);