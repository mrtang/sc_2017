'use strict';
angular.module('app')
.controller('ConfigInfoCtrl', ['$scope', '$modal', '$http', '$state', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state, $window, toaster, bootbox) {
	var list_location = [];
	var list_vas = [];
	var list_area = [];
    $scope.listType = [
        { id: 1, name: 'Money'},
        { id: 2, name: 'Weight'}
    ];	//list courier
    $http({
        url: ApiPath+'courier',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listCourier = result.data;
        }          
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
    });
	/**
	* Location
	*
	*/
    $scope.get_list_location = function(){
        $http({
            url: ApiPath+'location',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            list_location = result.data;
            $scope.list_location = list_location;
        }        
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.get_list_location();
    //Save
    $scope.SaveLocationBtn = function(data){
        $http({
            url: ApiPath+'location/create',
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
                list_location.unshift({'id' : result.id, 'name' : data.name,'active' : 1});
                $scope.list_location = list_location;
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
    $scope.changeLocation = function(id,data,field) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'location/edit/'+id,
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
    $scope.delLocation = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
                $http({
                url: ApiPath+'location/destroy/'+id,
                method: "get",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                        list_location.splice(item, 1); 
                        $scope.list_location = list_location;
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
    /**
	* Courier VAS
	*
	*/
	$scope.get_list_vas = function(){
        $http({
            url: ApiPath+'courier-vas',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            list_vas = result.data;
            $scope.list_vas = list_vas;
        }        
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.get_list_vas();
    //Save
    $scope.SaveVasBtn = function(data){
        $http({
            url: ApiPath+'courier-vas/create',
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
                list_vas.unshift({'id' : result.id, 'name' : data.name_vas,'code' : data.code,'active' : 1});
                $scope.list_vas = list_vas;
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
    $scope.changeVas = function(id,data,field) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'courier-vas/edit/'+id,
            method: "POST",
            data:myData,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
                $window.location.reload();
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
    $scope.delVas = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
                $http({
                url: ApiPath+'courier-vas/destroy/'+id,
                method: "get",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                        list_vas.splice(item, 1); 
                        $scope.list_vas = list_vas;
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
    /**
    *Area
    **/
    //List
    $scope.get_list_area = function(){
        $http({
            url: ApiPath+'courier-area',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            list_area = result.data;
            $scope.list_area = list_area;
        }        
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.get_list_area();
    //Save
    $scope.SaveAreaBtn = function(data){
        $http({
            url: ApiPath+'courier-area/create',
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
                list_area.unshift({'id' : result.id, 'name' : data.name_area,'courier_id' : data.courier_id,'active' : 1});
                $scope.list_area = list_area;
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
    $scope.changeArea = function(id,data,field) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'courier-area/edit/'+id,
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
    $scope.delArea = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
                $http({
                url: ApiPath+'courier-area/destroy/'+id,
                method: "get",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                        list_area.splice(item, 1); 
                        $scope.list_area = list_area;
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


