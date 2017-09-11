'use strict';
angular.module('app')
.controller('AreaLocationCtrl', ['$scope', '$modal', '$http', '$state', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state, $window, toaster, bootbox) {
	//List Area Location
	$scope.currentPage = 1;
    $scope.item_page = 20;
    // 
    $scope.list_area_location = function(){
    	$http({
            url: ApiPath+'area-location?page='+$scope.currentPage,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.data_area_location = result.data;
            //
		    $scope.totalItems = result.total;
			$scope.maxSize = 5;
            $scope.item_page = result.item_page;
            $scope.item_stt = $scope.item_page * ($scope.currentPage - 1);
        }        
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
        });
    };
    $scope.list_area_location();
    //Update
    $scope.changeAreaLocation = function(id,data,field) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'area-location/edit/'+id,
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
    $scope.delAreaLocation = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
                $http({
                url: ApiPath+'area-location/destroy/'+id,
                method: "get",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                        $scope.data_area_location.splice(item, 1); 
                        $scope.data_area_location = data_area_location;
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
angular.module('app')
.controller('ModalCreateAreaLocation', ['$scope', '$modal', '$http', '$state', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state, $window, toaster, bootbox) {
	// Popup Add
    $scope.open_add_area_location = function (size) {
        $modal.open({
            templateUrl: 'ModalAreaLocation.html',
            controller: 'CreateAreaLocationCtrl',
            size:size
        });
    };
}]);
//Edit
angular.module('app')
.controller('ModalEditAreaLocation', ['$scope', '$modal',
function($scope, $modal) {
	// Popup Add
    $scope.open_edit_area_location = function (size,id) {
        $modal.open({
            templateUrl: 'ModalEditAreaLocation.html',
            controller: 'EditAreaLocationCtrl',
            size:size,
            resolve: {
		        id: function () {
		          	return id;
		        }
		    }
        });
    };
}]);
//Add
angular.module('app')
.controller('CreateAreaLocationCtrl', ['$scope', '$modal','$http','$window', 'toaster', 'bootbox',
function($scope, $modal,$http,$window, toaster, bootbox) {
    //List courier
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
	//List Area
	$scope.list_area  = function(id){
        $http({
            url: ApiPath+'courier-area/areabycourier/'+id,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.data_area = result.data;
        }
        else{
            toaster.pop('warning', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.list_area();

    //List city
    $scope.myCity = {};
    $http.get(ApiPath+'city').success(function(result) {
        $scope.myCity.CitySelected  = '';
        $scope.myCity.options       = result.data;
        return false;
    });
    //Load district
    $scope.myCity.loadDistrict = function(){
        if($scope.myCity.CitySelected > 0)
        {
            $http({method: 'GET', url: ApiPath+'district?city_id='+$scope.myCity.CitySelected})
            .success(function(result, status, headers, config) {
                $scope.myDistrict = {};
                $scope.myDistrict.DistSelected  = '';
                $scope.myDistrict.options       = result.data;
            });
        }
    }
    //Location
    $scope.list_location  = function(){
        $http({
            url: ApiPath+'location',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.data_locate = result.data;
        }
        else{
            toaster.pop('warning', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.list_location();
    //Save
    $scope.saveAreaLocation = function (data) {
        $http({
            url: ApiPath+'area-location/create',
            method: "POST",
            data:{'province_id':$scope.myCity.CitySelected,'area_id':data.AreaSelected,'district_id':$scope.myDistrict.DistSelected,'location_id':data.LocateSelected,'active':data.active},
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
}]);
//edit
angular.module('app')
.controller('EditAreaLocationCtrl', ['$scope', '$modal', '$http','$state', '$stateParams', '$window', 'toaster', 'bootbox','id',
function($scope, $modal, $http,$state, $stateParams, $window, toaster, bootbox,id) {
	$scope.dat = [];
	$scope.myCity = [];
	$scope.myDistrict = [];
	//info
	$http({
        url: ApiPath+'area-location/show/'+id,
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.dat.AreaSelected = result.data.area_id;
            $scope.dat.LocateSelected = result.data.location_id;
            $scope.myCity.CitySelected = result.data.province_id;
            //
            //list district current
		    if(result.data.province_id > 0)
		    {
		        $http({method: 'GET', url: ApiPath+'district?city_id='+result.data.province_id})
		        .success(function(result1, status, headers, config) {
		            $scope.myDistrict.DistSelected  = result.data.district_id;
		            $scope.myDistrict.options       = result1.data;
		        });
		    }
		    $scope.id = id;
        }
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
    });
	//List Area
	$scope.list_area  = function(){
        $http({
            url: ApiPath+'courier-area',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.data_area = result.data;
        }
        else{
            toaster.pop('warning', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.list_area();
    //List city
    $scope.myCity = {};
    $http.get(ApiPath+'city').success(function(result) {
        $scope.myCity.options       = result.data;
        return false;
    });
    //Load district
    $scope.myCity.loadDistrict = function(){
        if($scope.myCity.CitySelected > 0)
        {
            $http({method: 'GET', url: ApiPath+'district?city_id='+$scope.myCity.CitySelected})
            .success(function(result, status, headers, config) {
                $scope.myDistrict.options       = result.data;
            });
        }
    }
    //Location
    $scope.list_location  = function(){
        $http({
            url: ApiPath+'location',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.data_locate = result.data;
        }
        else{
            toaster.pop('warning', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.list_location();
    //Save
    $scope.saveEditAreaLocation = function (id,data) {
        $http({
            url: ApiPath+'area-location/edit/'+id,
            method: "POST",
            data:{'province_id':$scope.myCity.CitySelected,'area_id':data.AreaSelected,'district_id':$scope.myDistrict.DistSelected,'location_id':data.LocateSelected,'active':data.active},
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
}]);


