'use strict';

angular.module('app').controller('FreezeCtrl', ['$scope', '$state', '$stateParams', 'MerchantVerify', 'Config_Status',
 	function($scope, $state, $stateParams, MerchantVerify, Config_Status) {

        if(!$stateParams.id.length){
            $state.go('app.dashboard');
        }

    // config
        
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.search               = '';
        $scope.list_data            = {};
        $scope.waiting              = true;
        $scope.id                   = $stateParams.id;
        $scope.list_color           = Config_Status.order_color;

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        // action
        
        $scope.refresh = function(){
            $scope.list_data        = [];
            $scope.total_all        = 0;
        }
        
        $scope.setPage = function(page){
            $scope.currentPage = page;
            $scope.waiting      = true;
            $scope.refresh($scope.search);
            MerchantVerify.Freeze($scope.currentPage, $scope.id,{tracking_code : $scope.search}).then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting = false;
            });
            return;
        }
        
        $scope.setPage(1);
    }
]);
