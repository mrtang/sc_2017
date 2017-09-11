'use strict';
angular.module('app')
.controller('CouponsReferListCtrl', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster', 'bootbox', 'Coupons', 
function($scope, $modal, $http, $state, $window, $stateParams, toaster, bootbox, Coupons) {
    var campaign_id =  3;
    $scope.currentPage = 1;
    $scope.item_page = 20;
    // List 


    $scope.checkExpired = function (date){
        return (date < Date.now()) ? true : false; 
    }
    $scope.setPage = function(page, code, email){
        $scope.listData = [];
        $scope.stateLoading = true;
        
        if(page == undefined){
            page = 1;
        }
        code = code || "";
        email = email || "";
        $http({
            url: ApiPath+'coupon/showrefer?page='+page + '&code=' + code + '&email=' + email ,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listData = result.data;
            $scope.listOrder = result.orders;
            $scope.totalItems = result.total;
            $scope.users = result.users;
            $scope.refers = result.refers;
            $scope.coupons = result.infoCoupon;
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

    $scope.openCouponMembers = function (coupon_id){
        var modalInstance = $modal.open({
            templateUrl: 'tpl/accounting/coupons/modal.coupon.member.html',
            controller: function ($scope, coupon_id, $modalInstance){
                $scope.list_member  = [];
                $scope.newMembers   = [];
                $scope.stateLoading = true;
                $scope.AddLoading   = false;

                $scope.load = function (){
                    $scope.stateLoading = true;
                    $http({
                        url: ApiPath+'coupon/members/'+ coupon_id,
                        method: "GET",
                        dataType: 'json',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    }).success(function (result, status, headers, config) {
                        if(!result.error){
                            $scope.list_member = result.data;
                        }        
                        $scope.stateLoading = false;
                    });
                }

                $scope.suggestUser = function(query) {
                    var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
                    if(filter.test(query)){
                        return $http.get(ApiPath + 'user/suggest?query='+query);
                    }
                };

                $scope.addMember = function (members){
                    $scope.AddLoading   = true;
                    $http({
                        url: ApiPath+'coupon/insert-member/'+ coupon_id,
                        method: "POST",
                        dataType: 'json',
                        data: {seller: members },
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    } ).success(function (result, status, headers, config) {
                        if(!result.error){
                            $scope.newMembers = [];
                            $scope.load();
                        }        
                        $scope.AddLoading   = false;
                    });
                }

                $scope.close  = function (){
                    $modalInstance.dismiss();
                }

                $scope.load();
            },
            size: 'lg',
            resolve: {
                coupon_id : function (){
                    return coupon_id;
                }
            }
        });
    }
    $scope.setPage();
}]);

