'use strict';
angular.module('app')
    .controller('ReportCreateTicketCtrl', ['$scope', '$http', '$state', '$window', '$stateParams', '$location', 'toaster', 'Report',
        function($scope, $http, $state, $window, $stateParams, $location, toaster, Report) {
            $scope.SearchData = { currentPage : 1, item_page : 20 };
            $scope.listCase = [];
            $scope.listCaseType = [];
            $scope.SearchResultData = [];
            $scope.statisticData = {};
            $scope.maxSize            = 5;
            $scope.selectedUser = {};
            var date = new Date();
            if($scope.f_date == undefined) {
                $scope.f_date = new Date(date.getFullYear(), date.getMonth()-1, date.getDate());
            }
            if($scope.t_date == undefined) {
                $scope.t_date = new Date(date.getFullYear(), date.getMonth(), date.getDate(),23,59);
            }

            $scope.load = function (){
                $scope.SearchData.from_date =   Date.parse($scope.f_date)/1000;
                $scope.SearchData.to_date   =   Date.parse($scope.t_date)/1000;
                $http.get(ApiPath + 'statistic/report-create-ticket', {params: $scope.SearchData}).success(function (resp){
                    if(resp.error){
                        toaster.pop('success','Thông báo','Không có dữ liệu');
                    }else {
                        $scope.SearchResultData = resp.data;
                        if($scope.SearchResultData.length > 0){
                            $scope.loadGraph($scope.SearchResultData[0]);
                        }
                    }
                })
            }

            $scope.load();
            
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
            $scope.diagram = [];
            $scope.loadingGraph = false;
            $scope.loadGraph = function (user){
                $scope.SearchData.from_date =   Date.parse($scope.f_date)/1000;
                $scope.SearchData.to_date   =   Date.parse($scope.t_date)/1000;
                var params  = angular.copy($scope.SearchData);
                params['user'] = user.user_id;

                $scope.selectedUser = user;
                $scope.loadingGraph = true;
                $http.get(ApiPath + 'statistic/graph-create-ticket', {params: params}).success(function (resp){
                    $scope.loadingGraph = false;
                    if(resp.error){

                        toaster.pop('success','Thông báo','Không có dữ liệu');
                    }else {
                        $scope.diagram = resp;
                        
                    }
                })
            }

            Report.getCase().success(function(response) {
                if(!response.error) {
                    $scope.listCase = response.data;
                    Report.getCaseType().success(function(response) {
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

/*            $scope.disabled = function(date, mode) {
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
