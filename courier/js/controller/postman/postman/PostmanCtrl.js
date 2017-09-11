'use strict';

//Postman
 angular.module('app')
 .controller('PostmanCtrl', ['$scope', '$modal', '$http', '$state', '$window', 'toaster', 'bootbox',
 	function($scope, $modal, $http, $state, $window, toaster, bootbox) {
 	$scope.currentPage = 1;
    $scope.item_page = 20;
    // List Postman
    $scope.setPage = function(){
    	$http({
            url: ApiPath+'postman?page='+$scope.currentPage,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listPostman = result.data;
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

    $scope.setPage();
    //Xoa postman
    $scope.delPostman = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
            	$http({
                    url: ApiPath+'postman/destroy/'+id,
                    method: "get",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
        			if(!result.error){
          				$scope.listPostman.splice(item, 1); 
        				toaster.pop('success', 'Thông báo', 'Thành công!');
        			}	       
        			else{
        				toaster.pop('error', 'Thông báo', 'Bạn không thể xoá!');
        			}
                });
            }
        });
    };
    //Them moi post man
    $scope.savePostmanBtn = function (data) {
        $http({
            url: ApiPath+'postman/create',
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
            }          
            else{
                toaster.pop('error', 'Thông báo', 'Bạn không thể tạo mới!');
            }
        });
    };
    //list courier
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
    //update status
    $scope.setActive = function(status,field,id) {
        var myData = {};
        myData[field] = status;
        $http({
            url: ApiPath+'postman/edit/'+id,
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
}]);

angular.module('app').controller('ModalCreatePostman', ['$scope', '$modal', function($scope, $modal) {
    $scope.open = function (size) {
        $modal.open({
            templateUrl: 'ModalPostman.html',
            controller: 'PostmanCtrl',
            size:size,
            resolve: {
                items: function () {
                    return $scope.listCourier;
                }
            }
        });
    };
}]);
//
angular.module('app').controller('ModalCreateLocationCare', ['$scope', '$modal', function($scope, $modal) {
    $scope.open = function (size) {
        $modal.open({
            templateUrl: 'ModalLocation.html',
            controller: 'LocationCtrl',
            size:size,
            resolve: {
                items: function () {
                    return $scope.CareExist;
                }
            }
        });
    };
}]);
//
angular.module('app').controller('LocationCtrl', ['$scope','$http','$state','$stateParams','toaster', function($scope,$http,$state,$stateParams,toaster) {
   $scope.exist = $stateParams;
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
    //Load ward
    $scope.myCity.loadWard = function(){
        if($scope.myDistrict.DistSelected > 0)
        {
            $http({method: 'GET', url: ApiPath+'ward?district_id='+$scope.myDistrict.DistSelected})
            .success(function(result, status, headers, config) {
                $scope.myWard = {};
                $scope.myWard.WardSelected  = '';
                $scope.myWard.options       = result.data;
            });
        }
    }
    //Save
    $scope.saveLocation = function (data) {
        $http({
            url: ApiPath+'postman-care/create',
            method: "POST",
            data:{'city_id':$scope.myCity.CitySelected,'postman_id':$stateParams.postman_id,'district_id':$scope.myDistrict.DistSelected,'ward_id':$scope.myWard.WardSelected},
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

angular.module('app').controller('DetailPostmanCtrl', ['$scope','$http','$state','$stateParams','toaster', function($scope,$http,$state,$stateParams,toaster) {
    var id = $stateParams.postman_id;
    $http({
        url: ApiPath+'postman/show/'+id,
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.infoPostman = result.data;
            $scope.idPostman = id;
        }
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
    });
    // list care
    $http({
        url: ApiPath+'postman-care?postman_id='+id,
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listLocationCareTotal = result.total;
            $scope.city = result.data.city_name;
            $scope.listLocationCare = result.data.child;
            $scope.idPostman = id;
			$stateParams.care = 3333;
        }
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
            $scope.city = 'Location undifined';
        }
    });
    //
    $scope.updatePostman = function(data,field) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'postman/edit/'+id,
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
    //List city
    $scope.myCity = {};
    $http({
        url: ApiPath+'city',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.citySelected  = '18';
            $scope.listCity       = result.data;
        }
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
    });
    //Load district
    $scope.loadDistrict = function(){
        if($scope.citySelected > 0)
        {
            $http({
                url: ApiPath+'district',
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(!result.error){
                    $scope.listCity       = result.data;
                }
                else{
                    toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
                }
            });
        }
    };
    //del location care
    $scope.delLocationCare = function(id) {
        $http({
            url: ApiPath+'postman-care/destroy/'+id,
            method: "get",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.listLocationCare.splice(item, 1); 
                toaster.pop('success', 'Thông báo', 'Thành công!');
            }
            else{
                toaster.pop('error', 'Thông báo', 'Bạn không thể xoá!');
            }
        });
    };
}]);


