'use strict';
angular.module('app')
    .controller('ReportInsightCtrl', ['$scope', '$http', '$state', '$window', '$stateParams', '$location', 'toaster', 'Report',
        function($scope, $http, $state, $window, $stateParams, $location, toaster, Report) {
            $scope.SearchData = { currentPage : 1, item_page : 20 };
            $scope.listCase = [];
            $scope.listCaseType = [];
            $scope.SearchResultData = [];
            $scope.statisticData = {};
            $scope.maxSize            = 5;
            var date = new Date();
            if($scope.f_date == undefined) {
                $scope.f_date = new Date(date.getFullYear(), date.getMonth()-1, date.getDate());
            }
            if($scope.t_date == undefined) {
                $scope.t_date = new Date(date.getFullYear(), date.getMonth(), date.getDate(),23,59);
            }

            $scope.statisticCaseType = function() {
                $scope.SearchResultData = [];
                $scope.SearchData.from_date =   Date.parse($scope.f_date)/1000;
                $scope.SearchData.to_date   =   Date.parse($scope.t_date)/1000;
                Report.reportCaseType($scope.SearchData)
                    .success(function(response) {
                        if(response.error) {
                            toaster.pop('success','Thông báo','Không có dữ liệu');
                        } else {
                            response.data.sort(function(a, b){
                                var key = 'case_id';
                                var x = a[key]; var y = b[key];
                                return ((x < y) ? -1 : ((x > y) ? 1 : 0));
                            });
                            $scope.SearchResultData = response.data;
                            $scope.totalItems = response.totalItems;
                            $scope.item_stt = $scope.SearchData.item_page * ($scope.SearchData.currentPage - 1);
                        }
                    })
            }
            $scope.statisticCaseType();

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
