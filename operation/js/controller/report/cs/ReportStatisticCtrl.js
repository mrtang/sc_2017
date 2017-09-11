'use strict';

angular.module('app').controller('ReportStatisticCtrl', ['$scope', '$q', 'dateFilter', 'Report',
 	function($scope, $q, dateFilter, Report) {
        $scope.totalItems           = 0;
        $scope.item_stt             = 0;
        $scope.item_page            = 40;
        $scope.waiting              = false;
        $scope.list_data            = {};
        $scope.statistic            = {};
        $scope.employee_statistic   = {};

        $scope.time     = {date : '', month : new Date(date.getFullYear(), date.getMonth())};
        $scope.frm      = {country_id : 0, team : [], email : [], active: 1, show_salary : 0, show_kpi : 0, group : 6};

        $scope.saveKpi  = function(user, code, val){
            if(!val){
                return 'Dữ liệu không được để trống !';
            }

            var data  = {'user_id' : user, 'code' : code, 'value' : val};
            if($scope.time.date != undefined && $scope.time.date != ''){
                data.date             = dateFilter($scope.time.date, 'yyyy-MM-dd');
            }else{
                data.date             = '';
            }

            if($scope.time.month != undefined && $scope.time.month != ''){
                data.month            = dateFilter($scope.time.month, 'yyyy-MM');
            }else{
                data.month            = '';
            }

            var defer  = $q.defer();
            Report.UpdateKpi(data).then(function (result) {
                if(result.data.error){
                    defer.resolve(result.data.error_message);
                }else{
                    defer.resolve(true);
                }
            });
            return defer.promise;
        }

        $scope.setPage = function(page){
            $scope.waiting              = true;
            $scope.currentPage          = page;
            $scope.list_data            = {};
            $scope.statistic            = {};
            $scope.employee_statistic   = {};
            var list_employee           = [];

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

            if($scope.time.date != undefined && $scope.time.date != ''){
                data.date             = dateFilter($scope.time.date, 'yyyy-MM-dd');
            }else{
                data.date             = '';
            }

            if($scope.time.month != undefined && $scope.time.month != ''){
                data.month            = dateFilter($scope.time.month, 'yyyy-MM');
            }else{
                data.month            = '';
            }


            Report.StatisticCustomer($scope.currentPage, data).then(function (result) {
                $scope.waiting  = false;
                if(!result.data.error){
                    $scope.list_data    = result.data.data;
                    $scope.statistic    = result.data.statistic;
                    $scope.totalItems   = $scope.list_data.length;
                    $scope.item_stt     = $scope.item_page * ($scope.currentPage - 1);

                    if($scope.totalItems > 0){
                        angular.forEach(result.data.data, function(value) {
                            list_employee.push(value.user_id);
                        });
                        return Report.StatisticKpiTeamByDate({date: data.date, month : data.month,employee: list_employee.toString(), code: 'tntc_call'});
                    }
                }
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['tntc_call'] = result.data.data;
                }
                return Report.StatisticKpiTeamByDate({date: data.date, month : data.month, employee: list_employee.toString(), code: 'tntc_chat'});
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['tntc_chat'] = result.data.data;
                }
                return Report.StatisticKpiTeamByDate({date: data.date, month : data.month, employee: list_employee.toString(), code: 'tntc_facebook'});
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['tntc_facebook']    = result.data.data;
                    return Report.StatisticKpiTeamByDate({date: data.date, month : data.month, employee: list_employee.toString(), code: 'tntc_email'});
                }
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['tntc_email']    = result.data.data;
                    return Report.StatisticKpiTeamByDate({date: data.date, month : data.month, employee: list_employee.toString(), code: 'tntc_chat_offiline'});
                }
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['tntc_chat_offiline']    = result.data.data;
                    return Report.StatisticKpiTeamByDate({date: data.date, month : data.month, employee: list_employee.toString(), code: 'tndh_ticket'});
                }
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['tndh_ticket']    = result.data.data;
                    return Report.StatisticKpiTeamByDate({date: data.date, month : data.month, employee: list_employee.toString(), code: 'tndh_email'});
                }
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['tndh_email']    = result.data.data;
                    return Report.StatisticKpiTeamByDate({date: data.date, month : data.month, employee: list_employee.toString(), code: 'tndh_facebook'});
                }
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['tndh_facebook']    = result.data.data;
                    return Report.StatisticKpiTeamByDate({date: data.date, month : data.month, employee: list_employee.toString(), code: 'tndh_chat_offline'});
                }
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['tndh_chat_offline']    = result.data.data;
                    return Report.StatisticKpiTeamByDate({date: data.date, month : data.month, employee: list_employee.toString(), code: 'search_feedback'});
                }
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['search_feedback']    = result.data.data;
                    return Report.StatisticKpiTeamByDate({date: data.date, month : data.month, employee: list_employee.toString(), code: 'happycall'});
                }
            }).then(function (result){
                if(!result.data.error){
                    $scope.employee_statistic['happycall']    = result.data.data;
                }
            });
            return;
        }
            //$scope.setPage();

    }
]);