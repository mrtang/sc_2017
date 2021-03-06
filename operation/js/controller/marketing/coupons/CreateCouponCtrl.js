'use strict';
angular.module('app')
.controller('CreateCouponsCtrl', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster', 'bootbox', 'Coupons', 'campaign', 'Base', '$modalInstance', '$timeout', 
function($scope, $modal, $http, $state, $window, $stateParams, toaster, bootbox, Coupons, campaign, Base, $modalInstance, $timeout) {


    $scope.saveData     = {
        code: "",
        seller: []
    };
    $scope.campaign = campaign;
    $scope.courier  = [];
    
    // Tự tạo random mã coupon 
    $scope.genCodeLoading = false;
    $scope.generation_code = function(){

        $scope.genCodeLoading = true;
        $http.post(ApiPath + 'coupon/coupon-code').success(function (resp){
            $scope.genCodeLoading = false;
            if(!resp.error){
                $timeout(function(){
                    $scope.saveData.code = resp.data;
                })
              
               return 
            }
            toaster.pop('warning', 'Thông báo', 'Tạo mã thất bại, vui lòng thử lại sau');
        }).error(function (){
            toaster.pop('warning', 'Thông báo', 'Tạo mã thất bại, vui lòng thử lại sau');
        })
    }
    // Tạo mã coupon
    $scope.createCoupons = function (data){
        var _data = angular.copy(data);
        _data.time_expired = new Date(_data.time_expired) / 1000;
        _data.campaign_id = $scope.campaign;

        $http.post(ApiPath + 'coupon/create-coupon', _data).success(function (resp){
            if(!resp.error){
                toaster.pop('success', 'Thông báo', resp.error_message);
                $modalInstance.close({
                    'action': 'add',
                    'data'  : resp.data
                })
                return 
            }
            toaster.pop('warning', 'Thông báo', resp.error_message);
        }).error(function (){
            toaster.pop('warning', 'Thông báo', resp.error_message);
        })
    }

    $scope.suggestUser = function(query) {
        return $http.get(ApiPath + 'user/suggest?query='+query);
    };

    // Đóng modal
    $scope.close = function (){
        $modalInstance.dismiss();
    }
    
}]);

