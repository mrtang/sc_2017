'use strict';
angular.module('app')
.controller('IndemnifyCtrl', ['$scope', '$modal', '$http', 'toaster','FileUploader','$rootScope',
function($scope, $modal, $http, toaster,FileUploader,$rootScope) {
    $scope.currentPage  = 1;
    $scope.item_page    = 20;
    $scope.totalItems   = 0;
    $scope.maxSize      = 5;
    $scope.stateLoading = false;
    $scope.ticketId     = '';
    $scope.email        = '';
    $scope.status       = 'none';
    $scope.timeStart    = '';
    $scope.statusConfig = {"WAITING":"Chờ xử lý","CONFIRMED":"Đã duyệt","SUCCESS":"Thành công","REJECT":"Từ chối bồi hoàn"};
    $scope.infoUser = $rootScope.userInfo;

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1
    };
    $scope.open = function($event,type) {
        $event.preventDefault();
        $event.stopPropagation();
        if(type == "time_accept_start_open"){
            $scope.time_accept_start_open = true;
        }else if(type == "time_accept_end"){
            $scope.time_accept_end = true;
        }
    };

    $scope.setPage = function(page){
        $scope.currentPage  = page;
        $scope.listData = [];
        $scope.stateLoading = true;
        var url = ApiOps + 'refund-confirm?page='+page;

        if($scope.ticketId != undefined && $scope.ticketId != ''){
            url += '&ticket_id='+$scope.ticketId;
        }
        if($scope.email != undefined && $scope.email != ''){
            url += '&email='+$scope.email;
        }
        if($scope.status != undefined && $scope.status != ''){
            url += '&status='+$scope.status;
        }
        if($scope.timeStart != undefined && $scope.timeStart != ''){
            url += '&time_start='+Date.parse($scope.timeStart)/1000;
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
    
    $scope.setPage(1);
    //Duyet
    $scope.acceptRefund = function(id,ticket){
        $scope.frm = {};
        $scope.frm.id = id;
        $scope.frm.type = 1;
        $scope.frm.ticket_id = ticket;
        $http.post(ApiPath + 'refund-confirm/edit', $scope.frm).success(function (resp){
            if(!resp._error){
                toaster.pop('success', 'Thông báo', 'Đã xác nhận bồi hoàn.');
            }else{
                toaster.pop('warning', 'Thông báo', resp._error_message);
            }
            $scope.submit_loading = false;
        })
    }
    //Huy
    $scope.rejectRefund  = function (id,ticket){
        var modalInstance = $modal.open({
            templateUrl: 'tpl/complain/indemnify/modal.refund_reject.html',
            controller: function($scope, $modalInstance, id, $http) {

                $scope.id         = id;
                $scope.submit_loading = false;
                
                $scope.reject = function (frm){
                    $scope.submit_loading = true;
                    $scope.frm.id   = id;
                    $scope.frm.type = 2;
                    $scope.frm.ticket_id = ticket;

                    $http.post(ApiPath + 'refund-confirm/edit', frm).success(function (resp){
                        $scope.submit_loading = false;
                        toaster.pop('success', 'Thông báo', resp.message);
                        $scope.cancel();
                    })
                }

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
    //excel
    $scope.export = function (email,ticket,status,time){
        var url = ApiPath + 'refund-confirm/export?cmd=export';

        if(email != undefined && email != ''){
            url += '&email='+email;
        }
        if(ticket != undefined && ticket != ''){
            url += '&ticket_id='+ticket;
        }
        if(status != undefined && status != ''){
            url += '&status='+status;
        }
        if(time != undefined && time != ''){
            url += '&time_start='+Date.parse(time)/1000;
        }

        url += '&access_token='+$rootScope.userInfo.token;

        return url;
    }
}]);