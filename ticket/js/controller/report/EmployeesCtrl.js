'use strict';
angular.module('app')
    .controller('EmployeesCtrl', ['$scope', '$http', '$state', '$window', '$stateParams', '$location', 'toaster', 'Report',
        function($scope, $http, $state, $window, $stateParams, $location, toaster, Report) {

            $scope._Math        = Math;
            $scope.dateRange    = {
                startDate: moment().subtract('days', 7),
                endDate : moment()
            };
            $scope.moment       = moment;
            $scope.ranges       = {
                'Hôm nay': [moment().startOf('day'), moment().endOf('day')],
                'Hôm qua': [moment().subtract('days', 1).startOf('day'), moment().subtract('days', 1)],
                '7 ngày trước': [moment().subtract('days', 7), moment()],
                '30 ngày trước': [moment().subtract('days', 30), moment()],
                'Tháng này': [moment().startOf('month'), moment().endOf('month')]
            };

            $scope.ReportCallCS = {
                "options": {
                    "chart": {
                      "type": "column"
                    },
                    "plotOptions": {
                      "series": {
                        "stacking": "normal"
                      }
                    }
                },
                title: {
                    text: 'Thống kê cuộc gọi chăm sóc'
                },
                xAxis: {
                    categories: []
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Tổng số cuộc gọi'
                    }
                },
                tooltip: {
                    pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b> ({point.percentage:.0f}%)<br/>',
                    shared: true
                },
                plotOptions: {
                    column: {
                        stacking: 'percent'
                    }
                },
                series: [],
                loading: true
            }

            $scope.reportCallOutgoing = angular.copy($scope.ReportCallCS);
            $scope.reportCallOutgoing.title.text = "Thống kê cuộc gọi đi CS";

            $scope.reportCallIncoming = angular.copy($scope.ReportCallCS);
            $scope.reportCallIncoming.title.text = "Thống kê khách hàng gọi đến"


            $scope.onDatepickedChange = function (){
                $scope.getReportCallCs();
                $scope.getReportCallSt();
                $scope.getReportCallOutgoingCs();
                $scope.getReportCallIncoming();
                $scope.getReportCallByHours();
            }

            

            $scope.getReportCallIncoming = function (){
                $scope.reportCallIncoming.loading             = true;
                var params = {
                    "startDate": ($scope.dateRange.startDate) ? moment($scope.dateRange.startDate).unix() : moment().subtract('days', 7).startOf("day").unix(),
                    "endDate": ($scope.dateRange.endDate) ? moment($scope.dateRange.endDate).unix() : moment().endOf("day").unix()
                };
                $http.get(ApiPath + 'call-center/report-incoming', {params: params}).success(function (resp){
                    $scope.reportCallIncoming.loading             = false;
                    if(!resp.error){
                        $scope.reportCallIncoming.series              = resp.data.series;
                        $scope.reportCallIncoming.xAxis.categories    = resp.data.categories;
                        $scope.ReportCallIncomingData                 = resp.data.data_table
                    }
                })
            }


            $scope.getReportCallOutgoingCs = function (){
                $scope.reportCallOutgoing.loading             = true;
                var params = {
                    "startDate": ($scope.dateRange.startDate) ? moment($scope.dateRange.startDate).unix() : moment().subtract('days', 7).startOf("day").unix(),
                    "endDate": ($scope.dateRange.endDate) ? moment($scope.dateRange.endDate).unix() : moment().endOf("day").unix()
                };
                $http.get(ApiPath + 'call-center/report-outgoing-cs', {params: params}).success(function (resp){
                    $scope.reportCallOutgoing.loading             = false;
                    if(!resp.error){
                        $scope.reportCallOutgoing.series              = resp.data.series;
                        $scope.reportCallOutgoing.xAxis.categories    = resp.data.categories;
                        $scope.ReportCallOutgoingCSData                 = resp.data.data_table
                    }
                })
            }


            $scope.getReportCallCs = function (){
                $scope.ReportCallCS.loading             = true;
                var params = {
                    "startDate": ($scope.dateRange.startDate) ? moment($scope.dateRange.startDate).unix() : moment().subtract('days', 7).startOf("day").unix(),
                    "endDate": ($scope.dateRange.endDate) ? moment($scope.dateRange.endDate).unix() : moment().endOf("day").unix()
                };

                $http.get(ApiPath + 'call-center/report-cs', {params: params}).success(function (resp){
                    $scope.ReportCallCS.loading             = false;
                    if(!resp.error){
                        $scope.ReportCallCS.series              = resp.data.series;
                        $scope.ReportCallCS.xAxis.categories    = resp.data.categories;
                        
                        $scope.ReportCallCSData                 = resp.data.data_table
                    }
                })
            }


            $scope.getReportCallSt= function (){
                $scope.ReportCallSystem.loading             = true;
                var params = {
                    "startDate": ($scope.dateRange.startDate) ? moment($scope.dateRange.startDate).unix() : moment().subtract('days', 7).startOf("day").unix(),
                    "endDate": ($scope.dateRange.endDate) ? moment($scope.dateRange.endDate).unix() : moment().endOf("day").unix()
                };

                $http.get(ApiPath + 'call-center/report-system', {params: params}).success(function (resp){
                    $scope.ReportCallSystem.loading             = false;
                    if(!resp.error){
                        $scope.ReportCallSystem.series              = resp.data.series;
                        $scope.ReportCallSystem.xAxis.categories    = resp.data.categories;
                        
                        $scope.ReportCallSystemData                 = resp.data.data_table
                    }
                })
            }


            $scope.getReportCallByHours= function (){
                $scope.ReportCallByHours.loading             = true;
                var params = {
                    "startDate": ($scope.dateRange.startDate) ? moment($scope.dateRange.startDate).unix() : moment().subtract('days', 7).startOf("day").unix(),
                    "endDate": ($scope.dateRange.endDate) ? moment($scope.dateRange.endDate).unix() : moment().endOf("day").unix()
                };

                $http.get(ApiPath + 'call-center/report-by-hours', {params: params}).success(function (resp){
                    $scope.ReportCallByHours.loading             = false;
                    if(!resp.error){
                        $scope.ReportCallByHours.series              = resp.data.series;
                        $scope.ReportCallByHours.xAxis.categories    = resp.data.categories.map(function (value){
                            return value + 'h - ' + (value+1) + 'h'
                        });
                        
                        $scope.ReportCallByHoursData                 = resp.data.data_table
                    }
                })
            }

            $scope.exportExcel = function (){
                var params = {
                    "startDate": ($scope.dateRange.startDate) ? moment($scope.dateRange.startDate).unix() : moment().subtract('days', 7).startOf("day").unix(),
                    "endDate": ($scope.dateRange.endDate) ? moment($scope.dateRange.endDate).unix() : moment().endOf("day").unix()
                };
                window.location = ApiPath + 'call-center/export-excel?startDate=' + params.startDate + '&endDate=' + params.endDate;

            }
            

            $scope.ReportCallSystem = {
                "options": {
                    "chart": {
                      "type": "areaspline"
                    },
                    "plotOptions": {
                      "series": {
                        "stacking": "normal"
                      }
                    }
                },
                title: {
                    text: 'Thống kê cuộc gọi hệ thống'
                },
                xAxis: {
                    categories: [
                    ],

                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Tổng số cuộc gọi'
                    }
                },
                tooltip: {
                    pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b> ({point.percentage:.0f}%)<br/>',
                    shared: true
                },
                plotOptions: {
                    column: {
                        stacking: 'percent'
                    }
                },
                series: [],
                exporting: {
                    buttons: {
                        exportButton: {
                            align: 'left',
                            x: 40
                        }
                    }
                }
            }



            $scope.ReportCallByHours = {
                "options": {
                    "chart": {
                      "type": "areaspline"
                    },
                    "plotOptions": {
                        legend: {
                            layout: 'vertical',
                            align: 'right',
                            verticalAlign: 'middle',
                            borderWidth: 0
                        }

                    }
                },
                title: {
                    text: 'Thống kê cuộc gọi theo giờ'
                },
                xAxis: {
                    categories: [
                    ],

                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Tổng số cuộc gọi'
                    },
                    plotLines: [{
                        value: 0,
                        width: 1,
                        color: '#808080'
                    }]

                },
                tooltip: {
                    pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b> ({point.percentage:.0f}%)<br/>',
                    shared: true
                },
                plotOptions: {
                    column: {
                        stacking: 'percent'
                    }
                },
                series: [],
                exporting: {
                    buttons: {
                        exportButton: {
                            align: 'left',
                            x: 40
                        }
                    }
                }
            }




            


            /*$scope.getReportCallCs();
            $scope.getReportCallOutgoingCs();
            $scope.getReportCallSt();
            $scope.getReportCallIncoming();
            $scope.getReportCallByHours();*/
            $scope.onDatepickedChange();

            
        }]);
