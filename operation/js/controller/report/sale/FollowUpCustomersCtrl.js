'use strict';

angular.module('app').controller('FollowUpCustomersCtrl', ['$scope', '$timeout', '$modal', 'Report',
 	function($scope, $timeout, $modal, Report) {
        $scope.totalItems           = 0;
        $scope.waiting              = false;
        $scope.list_data            = {};
        $scope.list_statistic       = {};
        $scope.check_box            = [1,2,3,4,5,6];

        $scope.frm                  = {country_id : '', team : [], email : [], customer : [], not_sign_in : '2', not_approve : '3', tab : 0};

        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;

        $scope.list_period          = {
            1 : 'Today',
            2 : 'Last 3 days',
            3 : 'Last 7 days',
            4 : 'Last 30 days'
        };

        $scope.list_report_status   = {
            1 : 'Orders',
            2 : 'Picked',
            3 : 'Delivered',
            4 : 'Returned',
            5 : 'CF. Payment',
            6 : 'Paid'
        }

        $scope.list_tab   = {
            0 : 'Waiting',
            1 : 'Doing',
            2 : 'Done',
            3 : 'Fail'
        }

        $scope.toggleSelection = function(id) {
            id = 1*id;
            var data = angular.copy($scope.check_box);
            var idx = 1*data.indexOf(id);

            if (idx > -1) {
                $scope.check_box.splice(idx, 1);
            }
            else {
                $scope.check_box.push(id);
            }
        };

        $scope.ChangeTab    = function(tab){
            $scope.frm.tab  = tab;
            $scope.setPage(1);
        }

        $scope.openSaveModal = function (item){
            var modalInstance = $modal.open({
                templateUrl: 'UpdateFailReason.html',
                controller: 'UpdateFailReasonCtrl',
                resolve: {
                    item : function (){
                        return item;
                    },
                    list_tab : function(){
                      return $scope.list_tab
                    }
                }
            });

            modalInstance.result.then(function (result) {
                $scope.list_data[$scope.list_data.indexOf(item)] = result.item;
            }, function () {
            });
        }
       

        $scope.setPage = function(page){
            $scope.currentPage      = page;
            $scope.waiting          = true;
            var data                = angular.copy($scope.frm);

            if(data.team != undefined && data.team != []){
                data.team       = data.team.toString();
            }else{
                data.team       = '';
            }

            if(data.email   != undefined && data.email != []){
                data.email  = data.email.toString();
            }else{
                data.email  = '';
            }

            if(data.customer    != undefined && data.customer != []){
                data.customer       = data.customer.toString();
            }else{
                data.customer       = '';
            }

            if($scope.check_box != undefined && $scope.check_box != []){
                data.status  = $scope.check_box.toString();
            }else{
                data.status       = [];
            }
            
            $scope.list_data            = {};
            $scope.list_statistic       = {};

            Report.FollowUpCustomers(page, data).then(function (result) {
                if(!result.data.error){
                    $scope.list_data            = result.data.data;
                    $scope.list_statistic       = result.data.statistic;
                    $scope.totalItems           = result.data.total;
                    $scope.item_stt             = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting  = false;
            });
            return;
        }
    }
]);

angular.module('app').controller('UpdateFailReasonCtrl', ['$scope', '$modalInstance', 'toaster', '$filter', 'Report', 'list_tab', 'item',
    function ($scope, $modalInstance, toaster, $filter, Report, list_tab, item){
    $scope.item             = angular.copy(item);
    $scope.list_tab         = list_tab;
    $scope.reason           = [];
    $scope.frm              = {'result' : '', 'activity' : '', 'reason' : '', 'note' : '','user_id' : item.user_id};
    $scope.waiting          = true;

    $scope.list_activity    = {
        1 : 'Call',
        2 : 'Email',
        3 : 'Meeting',
        4 : 'SMS',
        5 : 'Follow Up'
    };

    $scope.list_reason      = {
        1   : 'Price',
        2   : 'CS',
        3   : 'Pickup',
        4   : 'Delivery',
        5   : 'Sale'
    };

    $scope.toggleSelection = function(id) {
        var data = angular.copy($scope.reason);
        var idx = +data.indexOf(id);

        if (idx > -1) {
            $scope.reason.splice(idx, 1);
        }
        else {
            $scope.reason.push(id);
        }
    };

    $scope.save = function (frm){
        $scope.waiting  = true;
        if($scope.reason != undefined && $scope.reason != []){
            $scope.frm.reason      = $scope.reason.toString();
        }else{
            $scope.frm.reason  = '';
        }

        if($scope.frm.result == undefined){
            toaster.pop('warning', 'Thông báo', 'Yêu cầu chọn kết quả!');
            $scope.waiting  = false;
            return;
        }

        if(1*$scope.frm.result == 3 && ($scope.frm.reason == undefined || $scope.frm.reason == '')){
            toaster.pop('warning', 'Thông báo', 'Yêu cầu chọn lý do thất bại!');
            $scope.waiting  = false;
            return;
        }

        Report.UpdateCustomer(frm)
            .then(function (resp){return;
                if(!resp.data.error){
                }else {
                    $scope.item[id] = (value == 1 ? 0 : 1);
                }
            });
        return;
    }


    $scope.close = function (){
        $modalInstance.close({
            'item' : $scope.item,
        });
    }
}])

;