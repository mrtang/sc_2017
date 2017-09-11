'use strict';

//Ticket
angular.module('app').controller('UserDashbroadCtrl', ['$scope', '$modal', '$timeout', '$http', '$state', '$window', '$stateParams', '$rootScope', 'toaster', 'Storage', 'Config_Status', 'FileUploader', 'Api_Path', 'PhpJs', 'Ticket', 'Notify', 'Order', 'User','Location', 'bootbox',
    function($scope, $modal, $timeout, $http, $state, $window, $stateParams, $rootScope, toaster, Storage, Config_Status, FileUploader, Api_Path, PhpJs, Ticket, Notify, Order, User, Location, bootbox) {
        $scope.frm            = {};
        $scope.data_status    = Config_Status.ticket_btn;

        $scope.reminderLoading   = true;

        $scope.LoadReminder = function (filter){

            $scope.reminderLoading  = true;
            filter                  = filter || "";
            $scope.list_reminder    = [];

            $http.get(ApiPath + 'ticket-user-dashbroad/reminder', {params: {filter: filter}}).success(function (resp){
                $scope.reminderLoading   = false;
                if(!resp.error){
                    $scope.list_reminder    = resp.data;
                    $scope.total_reminder   = resp.total;
                    $scope.total_waiting    = resp.total_waiting;
                }
            }).error(function (){
                toaster.pop('warning', 'Thông báo', 'Lỗi kết nối dữ liệu');
            })
        }


        $scope.TicketLoading = {};
        $scope.TicketTotal   = {};
        $scope.TicketData    = {};

        $scope.LoadTicket = function (filter){

            $scope.TicketLoading[filter]  = true;
            $scope.TicketData[filter]     = [];

            $http.get(ApiPath + 'ticket-user-dashbroad/ticket', {params: {filter: filter}}).success(function (resp){
                $scope.TicketLoading[filter]  = false;
                if(!resp.error){
                    $scope.TicketData[filter]     =  resp.data;
                    $scope.TicketTotal[filter]   = resp.total;
                }
            }).error(function (){
                toaster.pop('warning', 'Thông báo', 'Lỗi kết nối dữ liệu');
            })
        }


        $scope.timeAgo = function (time){
            moment.lang('vi');
            return moment(time).fromNow();
        }


        $scope.isReminded = function (time){
            if(time > (Date.now() / 1000)){
                return false;
            }
            return true;
        }

        $scope.LoadReminder();  
        $scope.LoadTicket('priority');
        $scope.LoadTicket('overdue');
        $scope.LoadTicket('overing_due');
        

}])