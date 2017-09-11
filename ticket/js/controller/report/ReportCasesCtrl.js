'use strict';
angular.module('app')
    .controller('ReportCasesCtrl', ['$scope', '$http', '$state', '$window', '$stateParams', '$location', 'toaster', 'Report' , '$filter',
        function($scope, $http, $state, $window, $stateParams, $location, toaster, Report, $filter) {
            $scope.SearchData = { };
            $scope.listCase = [];
            $scope.listCaseType = [];
            $scope.SearchResultData = [];
            $scope.statisticData = {};
            $scope.maxSize            = 5;

            $scope.reportCaseData = {};
            $scope.reportReplyData = {};
            $scope.reportProcessData = {};
            $scope.reportOverTicketData = {};
            $scope.config = {
                title: 'Số tickets',
                tooltips: true,
                labels: false,
                mouseover: function() {},
                mouseout: function() {},
                click: function() {},
                legend: {
                    display: true,
                    //could be 'left, right'
                    position: 'right'
                },
                lineLegend: 'traditional', // Only on line Charts
                lineCurveType: 'cardinal' // change this as per d3 guidelines to avoid smoothline
            };

            $scope.configHasTime = {
                title: 'Thời gian (tính theo giây)',
                tooltips: function(data) {
                    return $filter('time')(data.value);
                },
                labels: false,

                mouseover: function() {},
                mouseout: function() {},
                click: function() {},
                legend: {
                    display: true,
                    //could be 'left, right'
                    position: 'right'
                },
                lineLegend: 'traditional', // Only on line Charts
                lineCurveType: 'cardinal' // change this as per d3 guidelines to avoid smoothline
            };

            $scope.configPercent = {
                title: 'Tính theo %',
                tooltips: function(data) {
                    return data.value + '%';
                },
                labels: false,
                mouseover: function() {},
                mouseout: function() {},
                click: function() {},
                legend: {
                    display: true,
                    //could be 'left, right'
                    position: 'right'
                },
                lineLegend: 'traditional', // Only on line Charts
                lineCurveType: 'cardinal' // change this as per d3 guidelines to avoid smoothline
            };
            var date = new Date();
            if($scope.f_date == undefined) {
                $scope.f_date = new Date(date.getFullYear(), date.getMonth()-1, date.getDate());
            }
            if($scope.t_date == undefined) {
                $scope.t_date = new Date(date.getFullYear(), date.getMonth(), date.getDate(),23,59);
            }


            $scope.statisticCase = function() {

                $scope.SearchData.from_date = Date.parse($scope.f_date)/1000;
                $scope.SearchData.to_date   = Date.parse($scope.t_date)/1000;

                Report.reportCase($scope.SearchData)
                    .success(function(response) {
                        if(response.error) {
                            toaster.pop('error','Thông báo',response.message);
                        } else {
                            $scope.reportCaseData = response.data.case;
                            $scope.reportReplyData = response.data.reply;
                            $scope.reportProcessData = response.data.process;
                            $scope.reportOverTicketData = response.data.over;
                        }
                    });
            }

            $scope.statisticCase();


            Report.getCase()
                .success(function(response) {
                    if(!response.error) {
                        $scope.listCase = response.data;

                        Report.getCaseType()
                            .success(function(response) {
                                var listCaseType = response.data;
                                for(var i =0; i<$scope.listCase.length;i++) {
                                    var k =0;
                                    $scope.listCaseType[$scope.listCase[i].id] = [];
                                    for(var j =0; j<listCaseType.length; j++) {
                                        if($scope.listCase[i].id==listCaseType[j].case_id) {
                                            $scope.listCaseType[$scope.listCase[i].id][k] = listCaseType[j];
                                            ++k;
                                        }
                                    }
                                }
                            });
                    }
                });


            //datepicker
            $scope.dateOptions = {
                formatYear: 'yy',
                startingDay: 1
            };

            /*$scope.disabled = function(date, mode) {
                return ( mode === 'day' && ( date.getDay() === 0 || date.getDay() === 6 ) );
            };*/
            $scope.open = function($event,type) {
                $event.preventDefault();
                $event.stopPropagation();
                if(type == "from_date"){
                    $scope.from_date_open = true;
                }else if(type == "to_date"){
                    $scope.to_date_open = true;
                }
            };

        }]);
