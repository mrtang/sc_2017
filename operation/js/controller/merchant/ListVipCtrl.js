'use strict';
angular.module('app').controller('ListVipCtrl', 
    ['$scope', '$rootScope',  '$http', '$state', '$window','$modal', '$filter', 'toaster', 'User', 
    function($scope, $rootScope, $http, $state, $window, $modal, $filter, toaster, User) {
        
        $scope.customerData = { currentPage : 1, itemsPerPage: 20 };
        $scope.totalItems = 0;
        $scope.listData = [];
        $scope.setPage = function(page) {
            $scope.stateLoading = true;
            $scope.listData = [];
            if(page == undefined) {
                $scope.customerData.currentPage = 1;
            } else {
                $scope.customerData.currentPage = page;
            }
            User.vip($scope.customerData)
                .success(function(response) {
                    if(response.error) {
                        $scope.totalItems = 0;
                    } else {
                        $scope.totalItems = response.total;
                        $scope.listData = response.data;
                        $scope.item_stt = ($scope.customerData.currentPage - 1)*$scope.customerData.itemsPerPage;
                    }
                    $scope.stateLoading = false;
                })
        };

        $scope.openModalManageCustomer = function(customer) {
            var modalInstance = $modal.open({
                    templateUrl : 'tpl/merchant/vip-list/modalManageCustomer.html',
                    controller  : 'ModalManageCustomerCtrl',
                    size : 'lg',
                    resolve: {
                        customer : function() {
                            return customer;
                        }
                    }
                });

            modalInstance.result.then(function (result) {
                $scope.setPage();
            }, function () {
            });
        };

        $scope.openModalManageHistory = function(customer) {
            var modal = $modal.open({
                templateUrl : 'tpl/merchant/vip-list/modalManageHistory.html',
                controller  : 'ModalManageHistoryCtrl',
                size : 'lg',
                resolve: {
                    customer : function() {
                        return customer;
                    }
                }
            });
        }

}]);

angular.module('app').controller('ModalManageCustomerCtrl', function($scope, $modalInstance, Search, customer, User, toaster) {
    $scope.customer = customer;
    $scope.listUser = [];
    $scope.postData = { type: 'cs'};
    User.admin()
        .success(function(response) {
            if(!response.error) {
                $scope.listUser = response.data;
            }
        });

    $scope.saveData = function() {
        Search.takeUser($scope.customer.id, $scope.postData)
            .success(function(response) {
                if(response.error) {
                    toaster.pop('error','Thất bại',response.message);
                } else {
                    toaster.pop('success','Thành công','Cập nhật thành công');
                    $modalInstance.close(response.data);
                }
            })
    }

    $scope.close = function () {
        $modalInstance.dismiss('cancel');
    };
});



angular.module('app').controller('ModalManageHistoryCtrl', function($scope, $modalInstance, Seller, customer, toaster) {
    $scope.customer = customer;
    $scope.listData = [];
    Seller.historyCS(customer.id)
        .success(function(response) {
            if(!response.error) {
                $scope.listData = response.data;
            }
        });

    $scope.close = function () {
        $modalInstance.dismiss('cancel');
    };
});

