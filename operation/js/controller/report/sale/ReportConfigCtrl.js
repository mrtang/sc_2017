'use strict';

angular.module('app').controller('ReportConfigCtrl', ['$scope', '$rootScope', '$filter', 'Base', 'Report',
 	function($scope, $rootScope, $filter, Base, Report) {
        $scope.frm                  = {interval : '1', country_id : 0, team : [], email : [], active : 1, period : '1', status : ''};

        $scope.venture = {
            99  : '- IN',
            133 : '- MY',
            174 : '- PH',
            195 : '- SG',
            216 : '- TH',
            237 : '- VN'
        };

        $scope.team = {};

        $scope.interval = {
            1 : 'This Week',
            2 : 'This Month',
            3 : 'This Year',
            //4 : 'Last Week',
            //5 : 'Last 1 months',
            //6 : 'Last 3 months',
            7 : 'Last 30 days',
            8 : 'Last 90 days'
        };

        $scope.period = {
            1 : 'By Month',
            2 : 'By Week',
            3 : 'By Day'
        };

        $scope.report_status = {
            1 : 'New',
            2 : 'InActive',
            3 : 'Return'
        };

        $scope.list_period = {1 :'', 2 : '',3 : '',4 : '',5 : '',6 : '',7 : '',8 : '',9 : '',10 : ''};

        $scope.list_fail_reason = {
            'fail_reason_price'     : 'Price',
            'fail_reason_cs'        : 'SC',
            'fail_reason_pickup'    : 'Pickup',
            'fail_reason_delivery'  : 'Delivery',
            'fail_reason_undefined' : 'Undefined'
        };

        $scope.check_return = function(id){
            return $scope.return_user.indexOf(1*id) != -1 ? 'Return' : 'New';
        }

        $scope.select2Options = {
            'multiple': true,
            'simple_tags': true,
            'tags': []
        };

        $scope.__convert_to_array = function(obj){
            var arr = [];
            if(obj == undefined){
                return arr;
            }

            angular.forEach(obj, function(value, key) {
                arr.push(value);
            });
            return arr;
        }

        $scope.__compare = function(oldvalue, newvalue){
            var result = Math.abs(newvalue - oldvalue);
            result = $filter('number')(((result*100)/oldvalue), 0);
            return ((newvalue < oldvalue) ? '- ' : '+ ') + result;
        }

        $scope.$watch('frm.country_id', function(newValue, oldValue) {
            if(newValue != undefined){
                Base.kpi_group_config({group: 3, country_id: newValue, active : 1})
                    .then(function (result) {
                        if(!result.data.error){
                            $scope.team     = result.data.data;
                        }
                    });
            }
        });
    }
]);