'use strict';
var list_service = [];
//Courier
 angular.module('app')
 .controller('CourierFeeVasActionCtrl', ['$scope','$http','$state','$stateParams', '$window','toaster', 
 	function($scope,$http,$state,$stateParams,$window,toaster) {
    $scope.services             = [];
    $scope.CourierFeeVasId      = parseInt($stateParams.id);
    $scope.listDistrict         = [];
    $scope.list_courier_vas     = [];
    $scope.list_value_type      = [{'id':1,'name':'Khối lượng'},{'id':2,'name':'Tổng tiền'}];
    $scope.form                 = [];
    //Load Courier
    $http.get(ApiPath+'courier').success(function(result){$scope.listCourier = result.data;});
    
    // Load Area
    $http.get(ApiPath+'courier-area').success(function(result){$scope.listArea = result.data;});
    
    // Load District by Area
    $scope.DistrictByArea = function (area) {
       $http.get(ApiPath+'area-location/locationbyarea/'+area).success(function(result){$scope.listDistrict = result.data;});
    }
    
    // Load Courier Vas
    $http.get(ApiPath+'courier-vas').success(function(result){
        if(result.data){
              $scope.list_courier_vas = result.data;
        }
    });
    
    $scope.change_courier = function (){
        $scope.listDistrict = [];
    }
    
    //Them moi - sua xoa
    $scope.updateCourierFeeVas = function (data) {
        $http({
            url: ApiPath+'courier-vas-fee/' + ($stateParams.courier_fee_id > 0 ? 'edit/'+$stateParams.courier_fee_id : 'create'),
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error && result.message == 'success'){
                toaster.pop('success', 'Alert!', 'Thành công!');
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
    };
    
    if(parseInt($stateParams.id) > 0){
        $http({
            url: ApiPath+'courier-vas-fee/show/'+$stateParams.id,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.form = result.data;
                $scope.DistrictByArea($scope.form.from_area_id);
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
    }
    
    // watch 
    $scope.$watch('form.vat + form.money + form.surcharge', function (newValues, oldValues, scope) {
      $scope.form.total_amount = +$scope.form.money + +($scope.form.vat*$scope.form.money)/100 + +$scope.form.surcharge;
   });
   
}]);