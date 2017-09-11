'use strict';

angular.module('app').controller('ReportOverViewCtrl', ['$scope', '$rootScope', '$localStorage', 'Base', 'Report', 'KPI',
 	function($scope, $rootScope, $localStorage, Base, Report, KPI) {
        $scope.waiting              = true;
        $scope.frm                  = {interval : '1', country_id : 0, team : [], email : [], active : 1};
        $scope.list_employee        = {};
        $scope.employee_statistic   = {'opps' : 0, 'won' : 0, 'revenue' : 0, 'fulfill' : 0,'new_revenue' : 0, 'cum_revenue' : 0};
        $scope.employee_target      = {'opps' : {'succeed_target' : 0, 'percent_target' : 0}, 'won' : {'succeed_target' : 0, 'percent_target' : 0}};
        $scope.employee_compare     = {'opps' : 0, 'won' : 0, 'revenue' : 0, 'fulfill' : 0,'new_revenue' : 0, 'cum_revenue' : 0};
        $scope.employee_report      = {'opps' : {}, 'won' : {}, 'revenue' : {}, 'satisfaction' : {}};

        $scope.setPage = function(){
            $scope.waiting          = true;

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

            var list_employee           = [];
            $scope.list_employee        = {};
            $scope.employee_report      = {'opps' : {}, 'won' : {}, 'revenue' : {}, 'satisfaction' : {}};
            $scope.employee_compare     = {'opps' : 0, 'won' : 0, 'revenue' : 0, 'fulfill' : 0,'new_revenue' : 0, 'cum_revenue' : 0};
            $scope.employee_statistic   = {'opps' : 0, 'won' : 0, 'revenue' : 0, 'fulfill' : 0,'new_revenue' : 0, 'cum_revenue' : 0};
            $scope.employee_target      = {'opps' : {'succeed_target' : 0, 'percent_target' : 0}, 'won' : {'succeed_target' : 0, 'percent_target' : 0},'revenue' : {'succeed_target' : 0, 'percent_target' : 0}};
            Report.SaleOverView(data).then(function (result) {

                if(!result.data.error){
                    $scope.list_employee    = result.data.data;
                    angular.forEach(result.data.data, function(value) {
                        list_employee.push(value.user_id);
                    });
                    return Report.StatisticKpi({interval: data.interval, employee: list_employee.toString(), code: 'opps'});
                }
                $scope.waiting  = false;
            }).then(function (result){
                if(!result.data.error){
                    angular.forEach(result.data.data, function(value) {
                        $scope.employee_statistic.opps += value;
                    });
                    $scope.employee_report.opps      = result.data.percent;
                    $scope.employee_target.opps.succeed_target = result.data.target.succeed_target;
                    $scope.employee_target.opps.percent_target = result.data.target.percent_target;
                }
                return Report.StatisticKpi({interval: data.interval, employee: list_employee.toString(), code: 'won'});
            }).then(function (result){
                if(!result.data.error){
                    angular.forEach(result.data.data, function(value) {
                        $scope.employee_statistic.won += value;
                    });
                    $scope.employee_report.won      = result.data.percent;
                    $scope.employee_target.won.succeed_target = result.data.target.succeed_target;
                    $scope.employee_target.won.percent_target = result.data.target.percent_target;
                }
                return Report.StatisticKpi({interval: data.interval, employee: list_employee.toString(), code: 'satisfaction'});
            }).then(function (result){
                if(!result.data.error){
                    var total       = 0;
                    var sum_percent = 0;
                    angular.forEach(result.data.percent, function(value) {
                        sum_percent += value;
                        total   += 1;
                    });

                    $scope.employee_statistic.satisfaction      = sum_percent/total;
                    $scope.employee_report.satisfaction         = result.data.percent;
                }
                return Report.StatisticKpi({interval: data.interval, employee: list_employee.toString(), code: 'revenue'});
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_report.revenue      = result.data.percent;

                    angular.forEach(result.data.data, function(value) {
                        $scope.employee_statistic.revenue += value;
                    });

                    angular.forEach(result.data.firstmonth, function(value) {
                        $scope.employee_statistic.new_revenue += value;
                    });
                    angular.forEach(result.data.nextmonth, function(value) {
                        $scope.employee_statistic.cum_revenue += value;
                    });

                    $scope.employee_target.revenue.succeed_target = result.data.target.succeed_target;
                    $scope.employee_target.revenue.percent_target = result.data.target.percent_target;
                }
                return Report.StatisticKpi({interval: data.interval, employee: list_employee.toString(), code: 'fulfill'});
            }).then(function (result){
                if(!result.data.error){
                    angular.forEach(result.data.data, function(value) {
                        $scope.employee_statistic.fulfill += value;
                    });
                }
                return Report.StatisticKpi({interval: data.interval, employee: list_employee.toString(), code: 'revenue', is_compare : 1});
            }).then(function (result){
                if(!result.data.error){
                    angular.forEach(result.data.data, function(value) {
                        $scope.employee_compare.revenue += value;
                    });

                    angular.forEach(result.data.firstmonth, function(value) {
                        $scope.employee_compare.new_revenue += value;
                    });
                    angular.forEach(result.data.nextmonth, function(value) {
                        $scope.employee_compare.cum_revenue += value;
                    });
                }
            });
            return;
        }
    }
]);