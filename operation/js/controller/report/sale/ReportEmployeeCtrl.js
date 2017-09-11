'use strict';

angular.module('app').controller('ReportEmployeeCtrl', ['$scope', '$rootScope', '$localStorage', 'Report', 'KPI',
 	function($scope, $rootScope, $localStorage, Report, KPI) {
        $scope.totalItems           = 0;
        $scope.waiting              = false;
        $scope.list_data            = {};
        $scope.list_category        = [];
        $scope.list_team            = [];
        $scope.employee_statistic   = {};

        $scope.frm = {interval : '1', country_id : 0, show_salary : 0, show_kpi : 0, team : [], email : [], active: 1};

        KPI.Config({group: 3, active: 1})
        .then(function (result) {
            if(!result.data.error){
                angular.forEach(result.data.data, function(value) {
                    if(value.__category != undefined){
                        if($scope.list_category[value.user_id] == undefined){
                            $scope.list_category[value.user_id] = [];
                        }

                        if(value.__category.target > 0){
                            $scope.list_category[value.user_id][value.__category.code] = value.__category.target;
                        }else{
                            $scope.list_category[value.user_id][value.__category.code] = value.__category.percent;
                        }
                    }
                });
            }
        });

        $scope.__get_value  = function(data, user_id){
            if(data != undefined && data[user_id] != undefined && data[user_id] != null){
                return data[user_id];
            }else{
                return 0;
            }
        }

        $scope.setPage = function(){
            $scope.waiting                  = true;
            $scope.totalItems               = 0;
            $scope.list_data                = {};
            $scope.employee_statistic       = {};
            var list_employee               = [];

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

            Report.SaleEmloyee(data).then(function (result) {
                $scope.waiting  = false;
                if(!result.data.error){
                    $scope.list_data    = result.data.data;
                    $scope.totalItems   = $scope.list_data.length;

                    if($scope.totalItems > 0){
                        angular.forEach(result.data.data, function(value) {
                            list_employee.push(value.user_id);
                        });
                        return Report.StatisticKpi({interval: data.interval, employee: list_employee.toString(), code: 'opps'});
                    }
                }
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['opps'] = result.data.data;
                    $scope.employee_statistic['opps_p'] = result.data.percent;
                }
                return Report.StatisticKpi({interval: data.interval, employee: list_employee.toString(), code: 'won'});
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['won'] = result.data.data;
                    $scope.employee_statistic['won_p'] = result.data.percent;
                }
                return Report.StatisticReturnMerchant({interval: data.interval, employee: list_employee.toString()});
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['return'] = result.data.data;
                }
                return Report.StatisticKpi({interval: data.interval, employee: list_employee.toString(), code: 'revenue'});
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['revenue']    = result.data.data;
                    $scope.employee_statistic['revenue_p']  = result.data.percent;
                    $scope.employee_statistic['firstmonth'] = result.data.firstmonth;
                    $scope.employee_statistic['nextmonth']  = result.data.nextmonth;
                }
                return Report.StatisticKpi({interval: data.interval, employee: list_employee.toString(), code: 'satisfaction'});
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['satisfaction']    = result.data.data;
                    $scope.employee_statistic['satisfaction_p']  = result.data.percent;
                }
                return Report.StatisticKpiTeam({interval: data.interval, employee: list_employee.toString(), code: 'fulfill'});
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['fulfill']    = result.data.data;
                    $scope.employee_statistic['fulfill_p']  = result.data.percent;
                }
                return Report.StatisticKpiTeam({interval: data.interval, employee: list_employee.toString(), code: 'team revenue'});
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['team_revenue'] = result.data.data;
                    $scope.employee_statistic['team_revenue_p']  = result.data.percent;
                }
            });
            return;
        }
            //$scope.setPage();

    }
]);