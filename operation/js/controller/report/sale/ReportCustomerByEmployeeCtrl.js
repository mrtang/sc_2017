'use strict';

angular.module('app').controller('ReportCustomerByEmployeeCtrl', ['$scope', '$rootScope', '$stateParams', '$filter', 'Report',
 	function($scope, $rootScope, $stateParams, $filter, Report) {
        $scope.totalItems           = 0;
        $scope.waiting              = false;
        $scope.list_data            = {};
        $scope.return_user          = {};
        $scope.list_statistic       = {};
        $scope.period               = 1;

        $scope.frm                  = {email : $stateParams.email, period : '1', status : ''};

        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;

        $scope.report_status = {
            1 : 'New',
            2 : 'InActive',
            3 : 'Return'
        };

        $scope.list_period          = {
            1 : 'Today',
            2 : 'Last 3 days',
            3 : 'Last 7 days',
            4 : 'Last 30 days'
        };

        $scope.check_return = function(id){
            return $scope.return_user.indexOf(1*id) != -1 ? 'Return' : 'New';
        }

        $scope.setPage = function(page){
            $scope.currentPage      = page;
            $scope.waiting          = true;
            var data                = angular.copy($scope.frm);
            switch($scope.frm.period) {
                case '2':
                    data.period = 3;
                    break;
                case '3':
                    data.period = 7;
                    break;
                case '4':
                    data.period = 30;
                    break;
                default:
                    data.period = 1;
            }
            $scope.period   = data.period;

            $scope.list_data            = {};
            $scope.return_user          = {};
            $scope.revenue_employee     = {};
            $scope.list_statistic       = {};

            Report.SaleCustomer(page, data).then(function (result) {
                if(!result.data.error){
                    $scope.list_data            = result.data.data;
                    $scope.return_user          = result.data.return_user;
                    $scope.list_statistic       = result.data.list_statistic;
                    $scope.totalItems           = result.data.total;
                    $scope.item_stt             = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting  = false;
            });
            return;
        }
    }
]);