'use strict';

//Provider report
angular.module('app').controller('RMerchantCtrl', ['$scope', '$filter', '$stateParams', 'Report', 'dateFilter',
 	function($scope, $filter, $stateParams, Report, dateFilter) {
        $scope.month    = '';
        $scope.data     = {'ticket' : {'total' : 0, 'closed' : 0, 'overtime' : 0}};
        $scope.rlocation = {};
        $scope.chart    = {};
        $scope.ticket       = 0;
        $scope.over_time    = 0;
        $scope.list_month   = {
            1   : 'Jan',
            2   : 'Feb',
            3   : 'Mar',
            4   : 'Apr',
            5   : 'May',
            6   : 'Jun',
            7   : 'Jul',
            8   : 'Aug',
            9   : 'Sep',
            10  : 'Oct',
            11  : 'Nov',
            12  : 'Dec'
        };
        $scope.date_month   = [];

        $scope.waiting          = false;
        $scope.waiting_location = false;

        $scope.refresh_chart    = function(){
            $scope.chart        = {'ps' : [[1,0],[2,0],[3,0]], 'ch' : [[1,0],[2,0],[3,0]], 'tc' : [[1,0],[2,0],[3,0]], 'ton' : [[1,0],[2,0],[3,0]]};
            $scope.date_month   = [[ 1, 'Jan' ], [ 2, 'Feb' ], [ 3, 'Mar' ]];
            $scope.rlocation    = {'cttt' : [[10, 0]], 'cthx' : [[10,0]], 'lttt' : [[10, 0]], 'lthx' : [[10, 0]]};
            $scope.ticket       = 0;
            $scope.over_time    = 0;
            $scope.data         = {'ticket' : {'total' : 0, 'closed' : 0, 'overtime' : 0}};
        }
        $scope.refresh_chart();

        $scope.getReport = function(time){
            Report.statistic($stateParams.id, time).then(function (result) {
                if(!result.data.error){
                    $scope.data             = result.data.data;
                    $scope.chart            = {
                        'ps'    : [
                            [$scope.data.last_month.month, $scope.data.last_month.total],
                            [$scope.data.order.month, $scope.data.order.total],
                            [$scope.data.next_month.month, $scope.data.next_month.total]
                        ],
                        'ch'    : [
                            [$scope.data.last_month.month, $scope.data.last_month.return],
                            [$scope.data.order.month, $scope.data.order.return],
                            [$scope.data.next_month.month, $scope.data.next_month.return]
                        ],
                        'tc'    : [
                            [$scope.data.last_month.month, $scope.data.last_month.success],
                            [$scope.data.order.month, $scope.data.order.success],
                            [$scope.data.next_month.month, $scope.data.next_month.success]
                        ],
                        'ton'    : [
                            [$scope.data.last_month.month, $scope.data.last_month.backlog],
                            [$scope.data.order.month, $scope.data.order.backlog],
                            [$scope.data.next_month.month, $scope.data.next_month.backlog]
                        ]
                    };

                    $scope.ticket       = ($scope.data.ticket.closed/$scope.data.ticket.total)*100;
                    $scope.over_time    = ($scope.data.ticket.overtime/$scope.data.ticket.total)*100;

                    $scope.date_month        = [];
                    $scope.date_month.push([
                        $scope.data.last_month.month, $scope.list_month[$scope.data.last_month.month]
                    ]);
                    $scope.date_month.push([
                        $scope.data.order.month, $scope.list_month[$scope.data.order.month]
                    ]);
                    $scope.date_month.push([
                        $scope.data.next_month.month, $scope.list_month[$scope.data.next_month.month]
                    ]);
                }
                $scope.waiting  = false;
            });

            return;
        }

        $scope.ReportLocation = function(time){
            Report.location($stateParams.id, time).then(function (result) {
                if(!result.data.error){
                    $scope.rlocation     = {'cttt' : [[10, 1*result.data.data.CTTT]], 'cthx' : [[10, 1*result.data.data.CTHX]], 'lttt' : [[10, 1*result.data.data.LTTT]], 'lthx' : [[10, 1*result.data.data.LTHX]]};
                }
                $scope.waiting_location  = false;
            });
            return;
        }

        $scope.$watch('month', function (NewVal, OldVal){
            var time        = '';
            $scope.waiting  = true;
            $scope.waiting_location = true;
            $scope.waiting_ticket   = true;
            $scope.waiting_overtime = true;
            $scope.refresh_chart();
            if(NewVal != undefined && NewVal != ''){
                time = dateFilter(NewVal, 'MM-yyyy');
                $scope.getReport(time);
                $scope.ReportLocation(time);
            }else{
                $scope.waiting  = false;
                $scope.waiting_location = false;
            }
        })

    }
]);
