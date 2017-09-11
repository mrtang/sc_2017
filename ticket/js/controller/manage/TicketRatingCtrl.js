'use strict';

//Ticket
angular.module('app').controller('TicketRatingCtrl', ['$scope', '$modal', '$timeout', '$http', '$state', '$window', '$stateParams', '$rootScope', 'toaster', 'Storage', 'Config_Status', 'FileUploader', 'Api_Path', 'PhpJs', 'Ticket', 'Notify', 'Order', 'User','Location', 'bootbox',
    function($scope, $modal, $timeout, $http, $state, $window, $stateParams, $rootScope, toaster, Storage, Config_Status, FileUploader, Api_Path, PhpJs, Ticket, Notify, Order, User, Location, bootbox) {
        $scope.currentPage    = 1;
        $scope.item_page      = 20;
        
        $scope.list_data      = []
        $scope.total_record   = 0;
        $scope.accept_process = false;
        $scope.maxSize        = 4;
        $scope.loadingState   = true;
        $scope.current_rate  = 0;
        $scope.frm            = {};
        $scope.data_status   = Config_Status.ticket_btn;

        $scope.refresh = function (){
            $scope.frm = {}
        };
        $scope.setPage = function (page, rate, frm, cmd){
            page                = page || 1;
            rate                = rate ? rate : "";
            var url             = ApiPath + 'ticket-rating/show?page=' + page + '&rate='+ rate;
            $timeout(function (){
                $scope.current_rate = rate;
            }, 0);
            

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
                window.location = url + '&cmd=export' + '&access_token=' + $rootScope.userInfo.token;
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

        

        $scope.timeAgo = function (time){
            moment.lang('vi');
            return moment(time).fromNow();
        }

        $scope.saveExplainProcessing = false;
        $scope.saveExplain = function (id, explain, openExplainBox){
            var url             = ApiPath + 'ticket-rating/update/'+ id;
            var data            = {
                'explain': explain
            };
            $scope.saveExplainProcessing = true;
            $http.post(url, data).success(function (resp){
                $scope.saveExplainProcessing = false;
                if(!resp.error){
                    toaster.pop('success', 'Thông báo', 'Cập nhật thành công');
                    return ;
                }
                toaster.pop('warning', 'Thông báo', 'Lỗi kết nối dữ liệu');
            })
        }

        $scope.setPage(1, "");

}])