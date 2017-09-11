'use strict';
angular.module('app')
.controller('FeeDeliveryActionCtrl', ['$scope', '$modal', '$http', '$state', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state, $window, toaster, bootbox) {
    $scope.from_area = {};
    $scope.check_box        = [];
    $scope.check_box_city   = [];
	//load courier
    $http({
        url: ApiPath+'courier',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listCourier = result.data;
        }
    });
    //load service
    $http.get(ApiPath+'courier-service').success(function(result){$scope.listService = result.data;});

    //load fee
    $scope.loadFee = function(courier_id){
        $scope.listFee = [];
        $http({
            url: ApiPath+'courier-fee/feebycourier/'+courier_id,
            method: "GET",
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.listFee = result.data;
            }
        });
    }
    //load area
    $scope.loadArea = function(courier_id){
        $scope.toCity = {};
        $http({
            url: ApiPath+'courier-area/cityinarea/'+courier_id,
            method: "GET",
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.listArea = result.data;
            }
        });
    }

    //load fromcity
    $http({
        url: ApiPath+'city?limit=all',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listCityFrom = result.data;
        }
    });
    //load to city
    $http({
        url: ApiPath+'city/cachecourier',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listCityTo = result.data;
        }
    });

    // Checkbox
    $scope.toggleSelection = function(id) {
    var data = angular.copy($scope.check_box);
    var idx = +data.indexOf(id);
     
        if (idx > -1) {
            $scope.check_box.splice(idx, 1);
        }
        else {
            $scope.check_box.push(id);
        }
    };
    
    $scope.check_list = function(id,action){
        var data = angular.copy($scope.check_box);
        var idx = +data.indexOf(id);
        
        if (idx > -1) {
            if(action == 'delete'){
                delete  $scope.check_box[idx];
            }
            return true;
        }
        else {
            return false;
        }
        
    }
    
    $scope.toggleSelectionAll = function (check,area_checked){
        if(area_checked == 0){
            angular.forEach(check, function(value, key) {
                angular.forEach($scope.check_box, function(val, k){
                    if(value.city_id == val){
                        $scope.check_box.splice(k,1);
                    }
                });
            });
        }else{
            $scope.check_box_city        = [];
            angular.forEach(check, function(value, key) {
                $scope.check_box.push(value.city_id);
            });
        }
    }
    //save
    $scope.saveData = function(data){
        data['to_city'] = $scope.check_box;
        //
        $http({
            url: ApiPath+'fee-delivery/createmulty',
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', result.message);
            }
            else{
                toaster.pop('error', 'Thông báo', result.message);
            }
        });
    }



}]);