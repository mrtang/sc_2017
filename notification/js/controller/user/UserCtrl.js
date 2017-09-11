'use strict';
angular.module('app')
.controller('UserCtrl', ['$scope', '$modal', '$http', '$state','$stateParams', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state,$stateParams, $window, toaster, bootbox) {
	$scope.currentPage = 1;
    $scope.item_page = 20;
    // List 
    $scope.setPage = function(keyword){
        if(keyword == undefined){
            keyword = '';
        }
    	$http({
            url: ApiPath+'user?page='+$scope.currentPage+'&search='+keyword,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.dataResult = result.data;
            //
		    $scope.totalItems = result.total;
			$scope.maxSize = 5;
            $scope.item_page = result.item_page;
            $scope.item_stt = $scope.item_page * ($scope.currentPage - 1);
        }        
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
        });
    };
    $scope.setPage();
}]);

angular.module('app')
.controller('ModalMailChimpCtrl', ['$scope', '$modal', '$http', '$state','$stateParams', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state,$stateParams, $window, toaster, bootbox) {
    $scope.open = function (size) {
        $modal.open({
            templateUrl: 'ModalMailChimp.html',
            controller: 'MailchimpCtrl',
            size:size,
            resolve: {
                items: function () {
                    return $scope.listCourier;
                }
            }
        });
    };
}]);
angular.module('app')
.controller('MailchimpCtrl', ['$scope', '$modal', '$http', '$state','$stateParams', '$window', 'toaster', 'bootbox',
function($scope, $modal, $http, $state,$stateParams, $window, toaster, bootbox) {
    // List id mail 
    $http({
        url: ApiPath+'mailchimp/list',
        method: "GET",
        dataType: 'json',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).success(function (result, status, headers, config) {
    if(!result.error){
        $scope.listMC = result.data;
    }
    else{
        toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
    }});
    //save
    $scope.saveListMailchimp = function(id){
        $http({
            url: ApiPath+'mailchimp/subscriber',
            method: "POST",
            data:{'id':id},
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            toaster.pop('success', 'Thông báo', 'Thành công!');
        }
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }});
    }
}]);



