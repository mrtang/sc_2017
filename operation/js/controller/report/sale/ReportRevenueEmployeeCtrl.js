'use strict';

angular.module('app').controller('ReportRevenueEmployeeCtrl', ['$scope', '$rootScope', 'Report',
 	function($scope, $rootScope, Report) {
        $scope.totalItems           = 0;
        $scope.waiting              = false;
        $scope.list_data            = {};
        $scope.list_team            = [];
        $scope.revenue_employee     = {};

        $scope.frm                  = {country_id : 0, team : [], email : [], active : 1, period : '1'};

        $scope.setPage = function(){
            $scope.waiting          = true;
            $scope.totalItems       = 0;

            var data  = angular.copy($scope.frm);

            if(data.team != undefined && data.team != []){
                data.team       = data.team.toString();
            }else{
                data.team       = '';
            }

            if(data.email != undefined && data.email != []){
                data.email  = data.email.toString();
            }else{
                data.email  = '';
            }

            $scope.list_data            = {};
            $scope.list_period          = {};
            $scope.revenue_employee     = {};

            Report.SaleRevenueEmployee(data).then(function (result) {
                if(!result.data.error){
                    $scope.list_data            = result.data.data;
                    $scope.list_period          = result.data.list_period;
                    $scope.revenue_employee     = result.data.revenue_employee;
                    $scope.totalItems           = $scope.list_data.length;
                }
                $scope.waiting  = false;
            });
            return;
        }
    }
]);