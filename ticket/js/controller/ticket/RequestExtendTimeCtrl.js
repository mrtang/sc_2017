'use strict';

//Ticket
angular.module('app').controller('RequestExtendTimeCtrl', ['$scope', '$modal', '$timeout', '$http', '$state', '$window', '$stateParams', '$rootScope', 'toaster', 'Storage', 'Config_Status', 'FileUploader', 'Api_Path', 'PhpJs', 'Ticket', 'Notify', 'Order', 'User','Location', 'bootbox',
    function($scope, $modal, $timeout, $http, $state, $window, $stateParams, $rootScope, toaster, Storage, Config_Status, FileUploader, Api_Path, PhpJs, Ticket, Notify, Order, User, Location, bootbox) {
        $scope.currentPage    = 1;
        $scope.item_page      = 20;
        
        $scope.list_data      = []
        $scope.total_record   = 0;
        $scope.accept_process = false;
        $scope.maxSize        = 4;
        $scope.loadingState   = true;
        $scope.current_status = "";
        $scope.frm            = {};

        $scope.refresh = function (){
            $scope.frm = {}
        };
        $scope.setPage = function (page, status, frm, cmd){
            page                  = page || 1;
            status                = typeof status == undefined ? "" : status;
            $scope.current_status = status;
            var url               = ApiPath + 'ticket-extend-time/show?page=' + page + '&status='+ status;

            if($scope.frm.keyword){
                url += '&keyword=' + $scope.frm.keyword;
            }

            if($scope.frm.time_start){

                url += '&time_start=' + Date.parse($scope.frm.time_start)/1000;   
            }

            if($scope.frm.time_end){
                url += '&time_end=' + Date.parse($scope.frm.time_end)/1000
            }
            if(cmd == 'export'){
                window.location = url + '&cmd=export';
            }


            $scope.loadingState   = true;
            $http.get(url).success(function (resp){
                $scope.loadingState = false;
                if(!resp.error){
                    $scope.list_data    = resp.data;
                    $scope.total_record = resp.total
                }
            }).error(function (){
                toaster.pop('warning', 'Thông báo', 'Lỗi kết nối dữ liệu');
            })
        }

        $scope.acceptRequest = function (item){
            if(!confirm('Bạn muốn gia hạn yêu cầu này ? ')){
                return false;
            }
            $scope.accept_process = true;
            var url = ApiPath + 'ticket-extend-time/accept/' + item.id;
            $http.post(url).success(function (resp){
                $scope.accept_process = false;
                if(resp.error){
                    toaster.pop('warning', 'Thông báo', resp.error_message);
                }else {
                    item.status = 1;
                    toaster.pop('success', 'Thông báo', 'Xác nhận yêu cầu gia hạn thành công');
                }
            })
        }


        $scope.rejectRequest = function (item){
            if(!confirm('Bạn muốn hủy gia hạn yêu cầu này ? ')){
                return false;
            }
            $scope.accept_process = true;
            var url = ApiPath + 'ticket-extend-time/reject/' + item.id;
            $http.post(url).success(function (resp){
                $scope.accept_process = false;
                if(resp.error){
                    toaster.pop('warning', 'Thông báo', resp.error_message);
                }else {
                    item.status = 1;
                    toaster.pop('success', 'Thông báo', 'Hủy yêu cầu gia hạn thành công');
                }
            })
        }

        $scope.timeAgo = function (time){
            moment.lang('vi');
            return moment(time).fromNow();
        }

        $scope.setPage(1, "");

}])