'use strict';
var list_configmeta = [];
var list_service = [];
//Courier
 angular.module('app')
 .controller('CourierCtrl', ['$scope', '$modal', '$http', '$state', '$window', 'toaster', 'bootbox',
 	function($scope, $modal, $http, $state, $window, toaster, bootbox) {
 	$scope.currentPage = 1;
    $scope.item_page = 20;
    // List Courier
 
    $scope.setPage = function(){
    	$http({
            url: ApiPath+'courier?page='+$scope.currentPage,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.list_courier = result.data;
            //
		    $scope.totalItems = result.total;
			$scope.maxSize = 5;
            $scope.item_page = result.item_page;
            $scope.item_stt = $scope.item_page * ($scope.currentPage - 1);
        }        
        else{
            
        }
        }).error(function (data, status, headers, config) {
            
        });
        return;
    };
    
    $scope.setPage();
    
    //Xoa Courier
    $scope.del_courier = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
            	$http({
                    url: ApiPath+'courier/destroy/'+id,
                    method: "get",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
        			if(!result.error){
          				$scope.list_courier.splice(item, 1); 
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
    
    // Cập nhật Courier
    $scope.change = function(id,data,field) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'courier/edit/'+id,
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
    
    /**
    *   Popup
    **/

    // Popup Add service
    $scope.open_add_service = function(size){
        $modal.open({
            templateUrl: 'ModalAddService.html',
            controller: 'ModalAddServiceCtrl',
            size:size
        });
    };
    
    // Popup Edit config
    $scope.open_add_courier_type = function (size) {
        $modal.open({
            templateUrl: 'ModalCourierType.html',
            controller: 'ModalCourierTypeCtrl',
            size:size
        });
    };
    
    // Popup Status courier
    $scope.open_status = function (size,item) {
        $modal.open({
            templateUrl: 'ModalStatus.html',
            controller: 'ModalStatusCtrl',
            size:size,
            resolve: {
             items: function () {
              return item;
             }
            }
        });
    };
    
}]);

//Modal status
angular.module('app').controller('ModalStatusCtrl', ['$scope', '$modalInstance', '$http', 'toaster', 'bootbox', 'items',
function($scope, $modalInstance, $http, toaster, bootbox, items) {
    // Config
    $scope.courier_id           = items;
    $scope.list_courier_status  = {};
    $scope.frm_add              = false;
    $scope.data                 = {active:1, courier_id:$scope.courier_id};
        
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
    
    //List
    $scope.courier_status  = function(){
        $http({
            url: ApiPath+'courier-status/show/'+$scope.courier_id,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.list_courier_status = result.data;
        }        
        else{
            toaster.pop('warning', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.courier_status();
    
    //Save
    $scope.add = function(data){
        $scope.frm_add              = true;
        $http({
            url: ApiPath+'courier-status/create',
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
                
                if(result.action == 'create'){
                    $scope.list_courier_status.unshift({'id' : result.id, 'courier_id' : $scope.courier_id ,'name' : data.name,'active' : data.active,'code': data.code});
                }else{
                    var key = $scope.filter_data($scope.list_courier_status,result.id);
                    $scope.list_courier_status[key] = {'id' : result.id, 'courier_id' : $scope.courier_id , 'name' : data.name,'active' : data.active,'code': data.code};
                }
                
                $scope.data   = {active:1, courier_id:$scope.courier_id};
            }          
            else{
                toaster.pop('warning', 'Thông báo', result.message);
            }
            $scope.frm_add              = false;
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
            $scope.frm_add              = false;
        });
        return;
    }
    
    //Update
    $scope.change = function(data) {
        $http({
            url: ApiPath+'courier-status/create',
            method: "POST",
            data:data,
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
    //XOA
    $scope.del = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
                $http({
                url: ApiPath+'courier-status/destroy/'+id,
                method: "get",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                        $scope.list_courier_status.splice(item, 1);
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
    
    // filter
    $scope.filter_data = function(items,value) {
        var result;
        angular.forEach(items, function(val, k) {
            if (val.id == value) {
                result = k;
            }
        });
        return result;
    }
    
}]);

//Modal courier
angular.module('app').controller('ModalCourierTypeCtrl', ['$scope', '$modalInstance', '$http', 'toaster', 'bootbox',
function($scope, $modalInstance, $http, toaster, bootbox) {
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
    //List
    $scope.courier_type  = function(){
        $http({
            url: ApiPath+'courier-type',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.list_courier_type = result.data;
        }        
        else{
            toaster.pop('warning', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.courier_type();
    
    //Save
    $scope.add = function(data){
        $http({
            url: ApiPath+'courier-type/create',
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
                $scope.list_courier_type.unshift({'id' : result.id, 'name' : data.name,'active' : 1});
            }          
            else{
                toaster.pop('warning', 'Thông báo', result.message);
            }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    
    //Update
    $scope.change = function(id,data,field) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'courier-type/edit/'+id,
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
    //XOA
    $scope.del = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
                $http({
                url: ApiPath+'courier-type/destroy/'+id,
                method: "get",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                        $scope.list_courier_type.splice(item, 1);
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
}]);

//Modal add Service
angular.module('app').controller('ModalAddServiceCtrl', ['$scope', '$modalInstance', '$http', 'toaster', 'bootbox',
function($scope, $modalInstance, $http, toaster, bootbox) {
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
    
    //List
    $scope.get_list_service  = function(){
        $http({
            url: ApiPath+'courier-service',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            list_service = result.data;
            $scope.list_service = list_service;
        }        
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.get_list_service();
    //Save
    $scope.SaveServiceBtn = function(data){
        $http({
            url: ApiPath+'courier-service/create',
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
                list_service.unshift({'id' : result.id, 'name' : data.name,'active' : 1});
                $scope.list_service = list_service;
            }          
            else{
                toaster.pop('error', 'Thông báo', result.message);
            }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    //Update
    $scope.changeService = function(id,data,field) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'courier-service/edit/'+id,
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
    $scope.delService = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
                $http({
                url: ApiPath+'courier-service/destroy/'+id,
                method: "get",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                        list_service.splice(item, 1); 
                        $scope.list_service = list_service;
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