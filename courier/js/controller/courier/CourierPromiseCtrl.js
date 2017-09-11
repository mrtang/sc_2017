'use strict';
//Courier Promise
 angular.module('app')
 .controller('CourierPromiseCtrl', ['$scope', '$http', '$state', '$window', '$stateParams', '$filter', 'toaster', 'bootbox',
 	function($scope, $http, $state, $window, $stateParams, $filter, toaster, bootbox) {
    // config
    var courier_id = $stateParams.courier_id;
    $scope.service_id               = 0;
    $scope.city                     = 0;
    $scope.index                    = '';
    $scope.search                   = '';
    $scope.form                     = {};
    $scope.list_service             = {};
    $scope.list_city                = {};
    $scope.courier                  = {};
    $scope.list_district            = {};
    $scope.list_district_by_city    = {};
    // function
    
    // change service
    $scope.change_service = function(){
        $scope.list_city        = {};
        $scope.city             = 0;
        $scope.index            = '';
        
        if($scope.service_id > 0){
            $scope.get_list_city();
        }
        return;
    }
    
    //Load courier
    $http({
        url: ApiPath+'courier/show/'+courier_id,
        method: "GET",
        dataType: 'json',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).success(function (result, status, headers, config) {
    if(!result.error){
        $scope.courier  = result.data;
    }   
    else{
        toaster.pop('warning', 'Thông báo', 'Tải thông tin hãng vận chuyển lỗi !');
    }
    }).error(function (data, status, headers, config) {
        toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
    });
    
    $scope.get_list_service = function(){
        $http({
            url: ApiPath+'courier-service?active=1',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.list_service  = result.data;
        }   
        else{
            toaster.pop('warning', 'Thông báo', 'Tải dịch vụ lỗi !');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.get_list_service();
    
    // List City
    $scope.get_list_city = function(){
        $http({
            url: ApiPath+'courier-promise/listcity/'+courier_id+'?stage=pickup&service_id='+$scope.service_id,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.list_city  = result.data;
        }   
        else{
            toaster.pop('warning', 'Thông báo', 'Tải danh sách Tỉnh/Thành phố lỗi !');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    
    // List District
    $scope.load_district    = function(index,city_id){
        if(city_id > 0){
            $scope.index    = index;
            $scope.city     = city_id;
            $scope.get_district_by_city();
            $http({
            url: ApiPath+'courier-promise/listdistrict/'+courier_id+'?service_id='+$scope.service_id+'&province_id='+city_id+'&stage=pickup'+'&district_name='+$scope.search,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (result, status, headers, config) {
            if(!result.error){
                 if(result.data){
                    $scope.list_district   = result.data;
                }
            }   
            else{
                toaster.pop('error', 'Thông báo', 'Tải danh sách quận huyện lỗi !');
            }
            }).error(function (data, status, headers, config) {
                toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
            });
        }
        return;
    }
 
    // List District by City
    $scope.get_district_by_city = function(){
        if($scope.city > 0){
            $http({
            url: ApiPath+'district?limit=all&city_id='+$scope.city,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.list_district_by_city = result.data;
                toaster.pop('success', 'Thông báo', 'Thành công !');
            }   
            else{
                toaster.pop('error', 'Thông báo', 'Cập nhật Lỗi !');
            }
            }).error(function (data, status, headers, config) {
                toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
            });
        }   
    }
    
    // active city
    $scope.active_city = function(province_id,district_id,active){
        if(province_id > 0 || district_id > 0){
            var param = {'service_id':$scope.service_id, 'province_id':province_id, 'stage':'pickup', 'active':active};
            
            if(district_id > 0){
                param['district_id']    = +district_id;
            }
            
            $http({
            url: ApiPath+'courier-promise/create/'+courier_id,
            method: "POST",
            data: param,
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (result, status, headers, config) {
            if(!result.error){
                if(province_id == $scope.city && !district_id){
                    angular.forEach($scope.list_district, function(value, key) {
                        $scope.list_district[key]['active'] = active;
                    });
                }
                
                toaster.pop('success', 'Thông báo', 'Thành công !');
            }   
            else{
                toaster.pop('error', 'Thông báo', 'Cập nhật Lỗi !');
            }
            }).error(function (data, status, headers, config) {
                toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
            });
        }   
    }
    
    // save add new 
    $scope.save_district    = function(data){
        if(data && $scope.city){
            var district_id;            
            if(data.district == 0 || data.district == ''){
                district_id = 0;
            }else{
                district_id = parseInt(data.district.id);
            }
            
            var param = {'service_id':$scope.service_id, 'province_id':$scope.city, 'district_id': district_id, 'estimate_pickup': data.estimate, 'stage':'pickup', 'active':1};
            $http({
            url: ApiPath+'courier-promise/create/'+courier_id,
            method: "POST",
            data: param,
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công !');
                $scope.list_city[$scope.city]['active'] = 1;
                
                if(district_id != 0){
                    var listArray = Object.keys($scope.list_district);
                    if (listArray.indexOf(district_id) >= 0) {
                        $scope.list_district[district_id]['active']            = 1;
                        $scope.list_district[district_id]['estimate_pickup']   = data.estimate;
                    }
                    else{     
                        if(listArray.length == 0){
                            $scope.list_district            = {};
                        }
                        $scope.list_district[district_id] = {'id':result.id, 'courier_id':courier_id, 'service_id':$scope.service_id, 'province_id':$scope.city, 'district_id':district_id, 'estimate_pickup':data.estimate, active:1, 'district_name': data.district.district_name};
                    }
                }
            }   
            else{
                toaster.pop('error', 'Thông báo', 'Cập nhật Lỗi !');
            }
            }).error(function (data, status, headers, config) {
                toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
            });
        }
        return;
    }
    
    // Del 
    $scope.del = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
                $http({
                url: ApiPath+'courier-promise/destroy/'+id,
                method: "get",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                        delete $scope.list_district[item];
                        toaster.pop('success', 'Thông báo', 'Thành Công!');
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
    
    // filter region list city
    $scope.filterCity = function(items,region) {
        var result = {};
        angular.forEach(items, function(value, key) {
            if (value.region == region) {
                result[key] = value;
            }
        });
        return result;
    }
    
}]);
