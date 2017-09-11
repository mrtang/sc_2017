'use strict';
angular.module('app')
.controller('StatusAcceptCtrl', ['$scope', '$modal', '$http', '$state', '$window','$stateParams', 'toaster', 'bootbox',
function($scope, $modal, $http, $state, $window,$stateParams, toaster, bootbox) {
    $scope.currentPage = 1;
    $scope.item_page = 20;
    //load status
    $http({
        url: ApiPath+'order-status/statusc',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listStatus = result.data;
        }
    });
    //
    $scope.setPage = function(currentPage){
        $http({
            url: ApiPath+'status-accept?page='+$scope.currentPage,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listData = result.data;
            $scope.totalItems = result.total;
            $scope.maxSize = 5;
            $scope.item_stt = $scope.item_page * ($scope.currentPage - 1);
        }        
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu trả ra!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    };
    $scope.setPage();
    //Delete
    $scope.delStatusAccept = function(id,index){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
                $http({
                    url: ApiPath+'status-accept/destroy/'+id,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                        $scope.listData.splice(index, 1);
                        toaster.pop('success', 'Thông báo', 'Xoá thành công!');
                    }          
                    else{
                        toaster.pop('error', 'Thông báo', 'Không thể xoá dữ liệu!');
                    }
                });
            }
        });
    }




}]);