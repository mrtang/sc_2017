'use strict';
angular.module('app')
.controller('LogStatisticListCtrl', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster','$sce',
function($scope, $modal, $http, $state, $window, $stateParams, toaster,$sce) {
    var id =  $stateParams.id;
    var fromDate = $stateParams.from_date;
    var toDate = $stateParams.to_date;
    $scope.maxSize      = 5;
    $scope.item_page    = 20;
	$scope.setPage = function(key,page){
        if(key == undefined){
            key = '';
        }
        $scope.currentPage  = page;
        $scope.listData = [];
        $scope.stateLoading = true;

        var url = ApiPath+'log/statisticlist/'+id+'?page='+page+'&from_date='+fromDate+'&to_date='+toDate;

        if(key != undefined && key != ''){
            url += '&key='+key;
        }
        
    	$http({
            url: url,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listData     = result.data;
            $scope.totalItems   = result.total;
            $scope.item_stt     = $scope.item_page * (page - 1);
        }
        $scope.stateLoading = false;
        });
    };
    $scope.setPage('',1);
    //
    $scope.viewContent = function(id,type){
        var url = ApiPath+'log/viewcontent?id='+id;
        if(type != undefined && type != ''){
            url += '&type='+type;
        }

        var modalInstance = $modal.open({
            templateUrl: 'tpl/log/modal.view_content.html',
            controller: function($scope, $modalInstance, $http) {
                $scope.submit_loading = false;
                $http.get(url).success(function (resp){
                    $scope.submit_loading = false;
                    //$scope.content = resp.content;
                    $scope.content= $sce.trustAsHtml(resp.content);
                })

                $scope.cancel = function() {
                    $modalInstance.dismiss('cancel');
                };

            },
            size: 'md',
            resolve: {
                id: function () {
                    return id; 
                }
            }
        });
    }
    //content sms
    $scope.viewContentSms = function(id,type){
        var url = ApiPath+'log/viewcontentsms?id='+id;
        if(type != undefined && type != ''){
            url += '&type='+type;
        }

        var modalInstance = $modal.open({
            templateUrl: 'tpl/log/modal.view_content_sms.html',
            controller: function($scope, $modalInstance, $http) {
                $scope.submit_loading = false;
                $http.get(url).success(function (resp){
                    $scope.submit_loading = false;
                    //$scope.content = resp.content;
                    $scope.content= resp.content;
                })

                $scope.cancel = function() {
                    $modalInstance.dismiss('cancel');
                };

            },
            size: 'md',
            resolve: {
                id: function () {
                    return id; 
                }
            }
        });
    }


}]);