'use strict';
angular.module('app')
.controller('LogCreateLadingCtrl', ['$scope', '$http', 'Config_Status',
function($scope, $http, Config_Status) {
	$scope.currentPage  = 1;
    $scope.item_page    = 20;
    $scope.maxSize      = 5;
    $scope.totalItems   = 0;
    $scope.sc_code      = '';
    $scope.user         = {};
    $scope.district     = {};
    $scope.ward             = {};
    $scope.status_reponse   = Config_Status.StatusVerify;
    $scope.stateLoading = false;

    // List
        $scope.keys = function(obj){
            return obj? Object.keys(obj) : [];
        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.listData         = {};
            $scope.user             = {};
            $scope.district         = {};
            $scope.ward             = {};
            $scope.stateLoading     = true;

            var url = ApiOps+'log/create-lading?page='+page;

            if($scope.sc_code != undefined && $scope.sc_code != ''){
                url += '&sc_code='+$scope.sc_code;
            }

            $http({
                url: url,
                method: "GET",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.listData         = result.data;
                $scope.totalItems       = result.total;
                $scope.user             = result.list_user;
                $scope.district         = result.list_district;
                $scope.ward             = result.list_ward;
                $scope.item_stt         = $scope.item_page * (page - 1);
            }
                $scope.stateLoading = false;
            });
        };
}]);