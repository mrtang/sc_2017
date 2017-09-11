'use strict';
angular.module('app')
.controller('CouponsCtrl', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster', 'bootbox', 'Coupons',
function($scope, $modal, $http, $state, $window, $stateParams, toaster, bootbox, Coupons) {
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
            url: ApiPath+'coupon/show-campaign?page='+page,
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

    $scope.openSaveCampaign = function (selectedItem){
        var modalInstance = $modal.open({
            templateUrl: 'tpl/accounting/coupons/modal.create.campaigns.html',
            controller: 'SaveCampaignCtrl',
            size: 'lg',
            resolve: {
                item : function (){
                    return selectedItem;
                }
            }
        });

        modalInstance.result.then(function (resp) {
            if(resp.action == 'create'){
                $scope.listData.unshift(resp.data);
            }
            if(resp.action == 'update'){
                angular.extend(selectedItem, resp.data);
            }
        }, function () {

        });
    }

    $scope.openCreateCoupon = function (campaign){
        Coupons.openModalCreate(campaign, function (resp){
            
        })
    }
    $scope.setPage();
}]);

angular.module('app').controller('SaveCampaignCtrl', ['$scope', '$modalInstance', 'item', 'bootbox', '$http', 'toaster', function ($scope, $modalInstance, item, bootbox, $http, toaster){
    $scope.selectedItem = angular.copy(item);
    if($scope.selectedItem.id){
        $scope.selectedItem.time_start  = item.time_start * 1000;
        $scope.selectedItem.time_end    = item.time_end * 1000;
    }
    
    $scope.save = function (item){
        var _item = angular.copy(item);
        if(!_item.time_start || !_item.time_end){
            bootbox.alert('Vui lòng chọn thời gian !');
            return ;
        }
        _item.time_start = new Date(_item.time_start) / 1000;
        _item.time_end   = new Date(_item.time_end) / 1000;

        var url = ApiPath + 'coupon/create-campaign';
        if(_item.id){
            url += '/'+_item.id
        }

        $http.post(url,  _item).success(function (resp){
            if(resp.error){
                toaster.pop('warning', 'Thông báo', resp.error_message);
            }else {
                if(_item.id){
                    $modalInstance.close({action: 'update', data: resp.data});
                }else {
                    $modalInstance.close({action: 'create', data: resp.data});
                }
                
                toaster.pop('success', 'Thông báo', resp.error_message);
            }
        })

    }

    $scope.close  = function (){
        $modalInstance.dismiss();
    }

}])
