var courier_global      = {};
var list_area_global    = {};

'use strict';
angular.module('app')
.controller('AreaLocationCtrl', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster', 'bootbox',
function($scope, $modal, $http, $state, $window, $stateParams, toaster, bootbox) {
    // config
    var courier_id          = $stateParams.courier_id;
    $scope.area_id          = 0;
    $scope.index            = '';
    $scope.city             = 0;
    $scope.list_city                = {};
    $scope.list_area                = {};
    $scope.list_district            = {};
    $scope.list_district_by_city    = {};
    $scope.listlocation             = [{'id': 1, 'name':'Quận trung tâm'}, {'id': 2, 'name':'Ngoại thành 1'}, {'id':3, 'name':'Ngoại thành 2'}, {'id':4, 'name':'Huyện xã'}, {'id':5, 'name':'Hải đảo'}];
    $scope.location_key             = {1:'Quận trung tâm', 2:'Ngoại thành 1', 3:'Ngoại thành 2', 4:'Huyện xã', 5:'Hải đảo'};
    
    //Load courier
    $http({
        url: ApiPath+'courier/show/'+courier_id,
        method: "GET",
        dataType: 'json',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).success(function (result, status, headers, config) {
    if(!result.error){
        $scope.courier  = result.data;
        courier_global  = $scope.courier;
    }   
    else{
        toaster.pop('warning', 'Thông báo', 'Tải thông tin hãng vận chuyển lỗi !');
    }
    }).error(function (data, status, headers, config) {
        toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
    });
    
    // Load List Area
    $http({
        url: ApiPath+'area-location/listarea?courier_id='+courier_id,
        method: "GET",
        dataType: 'json',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).success(function (result, status, headers, config) {
    if(!result.error){
        $scope.list_area    = result.data;
        list_area_global    = $scope.list_area;
    }   
    else{
        toaster.pop('warning', 'Thông báo', 'Tải dịch vụ lỗi !');
    }
    }).error(function (data, status, headers, config) {
        toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
    });
    
    
    // List City
    $scope.get_list_city = function(){
        $http({
            url: ApiPath+'area-location/listcity/'+$scope.area_id,
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
    
    /**
    *   Action
    **/
     
    // change service
    $scope.change_area = function(){
        $scope.list_city        = {};
        $scope.city             = 0;
        $scope.index            = '';
        
        if($scope.area_id > 0){
            $scope.get_list_city();
        }
        return;
    }
    
    // active city
    $scope.active_city = function(province_id,district_id,active){
        if(province_id > 0 || district_id > 0){
            var param = {'area_id':$scope.area_id,'active':active};
            
            if(province_id > 0){
                param['province_id']    = +province_id;
            }
            
            if(district_id > 0){
                param['district_id']    = +district_id;
            }
            
            $http({
            url: ApiPath+'area-location/create',
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
            var location_id;
                        
            if(data.district == 0 || data.district == ''){
                district_id = 0;
            }else{
                district_id = parseInt(data.district.id);
            }
            
            if(data.location == 0 || data.location == ''){
                location_id = 0;
            }else{
                location_id = parseInt(data.location.id);
            }
            
            var param = {'area_id':$scope.area_id, 'province_id':$scope.city, 'district_id': district_id, 'location_id': location_id, 'active':1};
            $http({
            url: ApiPath+'area-location/create',
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
                        $scope.list_district[district_id]['active']             = 1;
                        $scope.list_district[district_id]['location_id']        = location_id;
                    }
                    else{     
                        if(listArray.length == 0){
                            $scope.list_district            = {};
                        }
                        $scope.list_district[district_id] = {'id':result.id, 'area_id':$scope.area_id, 'province_id':$scope.city, 'district_id':district_id, 'location_id':location_id, active:1, 'district_name': data.district.district_name};
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
                url: ApiPath+'area-location/destroy/'+id,
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
    
    // List District
    $scope.load_district    = function(index,city_id){
        if(city_id > 0){
            $scope.index    = index;
            $scope.city     = city_id;
            $scope.get_district_by_city();
            $http({
            url: ApiPath+'area-location/listdistrict?area_id='+$scope.area_id+'&province_id='+city_id,
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
    
    $scope.$on('update_area',function(){
        $scope.list_area = list_area_global;
    })
    
     // Popup Edit config
    $scope.popup_area = function (size) {
        $modal.open({
            templateUrl: 'ModalAreaCourier.html',
            controller: 'ModalAreaCourierCtrl',
            size:size
        });
    };
    
}]);


//Modal add Area
angular.module('app').controller('ModalAreaCourierCtrl', ['$scope', '$modalInstance', '$http', 'toaster', 'bootbox',
function($scope, $modalInstance, $http, toaster, bootbox) {
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
    
    // config
    $scope.courier      = courier_global;
    courier_id          = $scope.courier.id;
    $scope.list_area    = {};
    $scope.area         = {};
    
    // Load List Area
    $http({
        url: ApiPath+'area-location/listarea?courier_id='+courier_id,
        method: "GET",
        dataType: 'json',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).success(function (result, status, headers, config) {
    if(!result.error){
        $scope.list_area  = result.data;
    }   
    else{
        toaster.pop('warning', 'Thông báo', 'Tải dịch vụ lỗi !');
    }
    }).error(function (data, status, headers, config) {
        toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
    });
    
    //Save
    $scope.add = function(data){
        if(data != ''){
            data['courier_id']  = courier_id;
            $http({
                url: ApiPath+'area-location/createarea',
                method: "POST",
                data:data,
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(!result.error){
                    toaster.pop('success', 'Thông báo', 'Thành công!');
                    $scope.list_area.unshift({'id' : result.id, 'name' : data.name});
                    list_area_global = $scope.list_area
                    $scope.$broadcast('update_area');
                }          
                else{
                    toaster.pop('error', 'Thông báo', result.message);
                }
            }).error(function (data, status, headers, config) {
                toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
            });
        }else{
            toaster.pop('error', 'Thông báo', 'Cần nhập tên !');
        }
        return;
    }
    //Update
    $scope.change = function(id,data,field) {
        var myData = {};
        myData[field] = data;
        myData['id']  = id;
        $http({
            url: ApiPath+'area-location/editarea',
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
    
    //XOA
    $scope.del = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
                $http({
                url: ApiPath+'area-location/destroyarea/'+id,
                method: "get",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                        $scope.list_area.splice(item, 1); 
                        list_area_global = $scope.list_area;
                        toaster.pop('success', 'Thông báo', 'Thành Công!');
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
