'use strict';
angular.module('app')
.controller('CouponsListCtrl', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster', 'bootbox', 'Coupons',
function($scope, $modal, $http, $state, $window, $stateParams, toaster, bootbox, Coupons) {
    var campaign_id =  $stateParams.id;
    $scope.currentPage = 1;
    $scope.item_page = 20;
    // List 


    $scope.checkExpired = function (date){
        return (date < Date.now()) ? true : false; 
    }
    $scope.setPage = function(page){
        $scope.listData = [];
        $scope.stateLoading = true;
        
        if(page == undefined){
            page = 1;
        }
        
        $http({
            url: ApiPath+'coupon/show/'+ campaign_id +'?page='+page,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listData = result.data;
            $scope.totalItems = result.total;
            $scope.maxSize = 5;
            $scope.item_stt = $scope.item_page * (page - 1);
        }        
        else{
            $scope.totalItems = 0;
        }
        $scope.stateLoading = false;
        });
    };


    $scope.openCreateCoupon = function (){
        Coupons.openModalCreate(campaign_id, function (resp){
            if(resp.action == 'add'){
                $scope.listData.unshift(resp.data);
            }
        })
    }
    $scope.setPage();
}]);

