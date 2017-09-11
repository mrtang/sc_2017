<<<<<<< HEAD
'use strict';

//Provider report
angular.module('app').controller('EventCtrl', ['$scope', '$filter', 'Order',
 	function($scope, $filter, Order) {
        $scope.chart        = {'20' : [[1,0],[2,0],[3,0],[4,0],[5,0]], '21' : [[1,0],[2,0],[3,0],[4,0],[5,0]], '22' : [[1,0],[2,0],[3,0],[4,0],[5,0]], '23' : [[1,0],[2,0],[3,0],[4,0],[5,0]], '24' : [[1,0],[2,0],[3,0],[4,0],[5,0]], '25' : [[1,0],[2,0],[3,0],[4,0],[5,0]], '26' : [[1,0],[2,0],[3,0],[4,0],[5,0]]};
        $scope.date_month   = [[ 1, 'Khách hàng đăng ký mới' ], [ 2, 'Khách hàng tạo đơn' ], [ 3, 'Order mang ra bưu cục' ], [ 4, 'Mang ra bưu cục thành công' ], [ 5, 'Mang ra bưu cục không thành công' ]];
        $scope.total        = {
          'user'            : 0,
          'user_order'      : 0,
          'order'           : 0,
          'order_success'   : 0,
          'order_fail'      : 0
        };

        $scope.waiting          = false;

        $scope.getReport = function(){
            Order.EventDashBoard().then(function (result) {
                if(!result.data.error){
                    var user = {};
                    angular.forEach(result.data.user, function(value) {
                        user[value.group_tc]    = value;
                        $scope.total.user += 1*value.count;
                    });
                    angular.forEach(result.data.user_order, function(value) {
                        $scope.total.user_order += 1*value.count;
                    });
                    angular.forEach(result.data.order, function(value) {
                        $scope.total.order += 1*value.count;
                    });
                    angular.forEach(result.data.order_success, function(value) {
                        $scope.total.order_success += 1*value.count;
                    });
                    angular.forEach(result.data.order_fail, function(value) {
                        $scope.total.order_fail += 1*value.count;
                    });

                    $scope.chart            = {
                        '20'    : [
                            [1, (user[20] != undefined ? user[20]['count'] : 0)],
                            [2, (result.data.user_order[20] != undefined ? result.data.user_order[20]['count'] : 0)],
                            [3, (result.data.order[20] != undefined ? result.data.order[20]['count'] : 0)],
                            [4, (result.data.order_success[20] != undefined ? result.data.order_success[20]['count'] : 0)],
                            [5, (result.data.order_fail[20] != undefined ? result.data.order_fail[20]['count'] : 0)]
                        ],
                        '21'    : [
                            [1, (user[21] != undefined ? user[21]['count'] : 0)],
                            [2, (result.data.user_order[21] != undefined ? result.data.user_order[21]['count'] : 0)],
                            [3, (result.data.order[21] != undefined ? result.data.order[21]['count'] : 0)],
                            [4, (result.data.order_success[21] != undefined ? result.data.order_success[21]['count'] : 0)],
                            [5, (result.data.order_fail[21] != undefined ? result.data.order_fail[21]['count'] : 0)]
                        ],
                        '22'    : [
                            [1, (user[22] != undefined ? user[22]['count'] : 0)],
                            [2, (result.data.user_order[22] != undefined ? result.data.user_order[22]['count'] : 0)],
                            [3, (result.data.order[22] != undefined ? result.data.order[22]['count'] : 0)],
                            [4, (result.data.order_success[22] != undefined ? result.data.order_success[22]['count'] : 0)],
                            [5, (result.data.order_fail[22] != undefined ? result.data.order_fail[22]['count'] : 0)]
                        ],
                        '23'    : [
                            [1, (user[23] != undefined ? user[23]['count'] : 0)],
                            [2, (result.data.user_order[23] != undefined ? result.data.user_order[23]['count'] : 0)],
                            [3, (result.data.order[23] != undefined ? result.data.order[23]['count'] : 0)],
                            [4, (result.data.order_success[23] != undefined ? result.data.order_success[23]['count'] : 0)],
                            [5, (result.data.order_fail[23] != undefined ? result.data.order_fail[23]['count'] : 0)]
                        ],
                        '24'    : [
                            [1, (user[24] != undefined ? user[24]['count'] : 0)],
                            [2, (result.data.user_order[24] != undefined ? result.data.user_order[24]['count'] : 0)],
                            [3, (result.data.order[24] != undefined ? result.data.order[24]['count'] : 0)],
                            [4, (result.data.order_success[24] != undefined ? result.data.order_success[24]['count'] : 0)],
                            [5, (result.data.order_fail[24] != undefined ? result.data.order_fail[24]['count'] : 0)]
                        ],
                        '25'    : [
                            [1, (user[25] != undefined ? user[25]['count'] : 0)],
                            [2, (result.data.user_order[25] != undefined ? result.data.user_order[25]['count'] : 0)],
                            [3, (result.data.order[25] != undefined ? result.data.order[25]['count'] : 0)],
                            [4, (result.data.order_success[25] != undefined ? result.data.order_success[25]['count'] : 0)],
                            [5, (result.data.order_fail[25] != undefined ? result.data.order_fail[25]['count'] : 0)]
                        ],
                        '26'    : [
                            [1, (user[26] != undefined ? user[26]['count'] : 0)],
                            [2, (result.data.user_order[26] != undefined ? result.data.user_order[26]['count'] : 0)],
                            [3, (result.data.order[26] != undefined ? result.data.order[26]['count'] : 0)],
                            [4, (result.data.order_success[26] != undefined ? result.data.order_success[26]['count'] : 0)],
                            [5, (result.data.order_fail[26] != undefined ? result.data.order_fail[26]['count'] : 0)]
                        ]
                    };
                }
            });

            return;
        }

        $scope.getReport();

    }
]);
=======
'use strict';

//Provider report
angular.module('app').controller('EventCtrl', ['$scope', '$filter', 'Order',
 	function($scope, $filter, Order) {
        $scope.chart        = {'20' : [[1,0],[2,0],[3,0],[4,0],[5,0]], '21' : [[1,0],[2,0],[3,0],[4,0],[5,0]], '22' : [[1,0],[2,0],[3,0],[4,0],[5,0]], '23' : [[1,0],[2,0],[3,0],[4,0],[5,0]], '24' : [[1,0],[2,0],[3,0],[4,0],[5,0]], '25' : [[1,0],[2,0],[3,0],[4,0],[5,0]], '26' : [[1,0],[2,0],[3,0],[4,0],[5,0]]};
        $scope.date_month   = [[ 1, 'Khách hàng đăng ký mới' ], [ 2, 'Khách hàng tạo đơn' ], [ 3, 'Order mang ra bưu cục' ], [ 4, 'Mang ra bưu cục thành công' ], [ 5, 'Mang ra bưu cục không thành công' ]];
        $scope.total        = {
          'user'            : 0,
          'user_order'      : 0,
          'order'           : 0,
          'order_success'   : 0,
          'order_fail'      : 0,
          'percent_success' : 0,
          'percent_fail'    : 0
        };

        $scope.waiting          = true;

        $scope.getReport = function(){
            Order.EventDashBoard().then(function (result) {
                if(!result.data.error){
                    var user = {};
                    angular.forEach(result.data.user, function(value) {
                        user[value.group_tc]    = value;
                        $scope.total.user += 1*value.count;
                    });
                    angular.forEach(result.data.user_order, function(value) {
                        $scope.total.user_order += 1*value.count;
                    });
                    angular.forEach(result.data.order, function(value) {
                        $scope.total.order += 1*value.count;
                    });
                    angular.forEach(result.data.order_success, function(value) {
                        $scope.total.order_success += 1*value.count;
                    });
                    angular.forEach(result.data.order_fail, function(value) {
                        $scope.total.order_fail += 1*value.count;
                    });

                    $scope.total.percent_success    = ($scope.total.order_success/$scope.total.order)*100;
                    $scope.total.percent_fail       = ($scope.total.order_fail/$scope.total.order)*100;

                    $scope.chart            = {
                        '20'    : [
                            [1, (user[20] != undefined ? user[20]['count'] : 0)],
                            [2, (result.data.user_order[20] != undefined ? result.data.user_order[20]['count'] : 0)],
                            [3, (result.data.order[20] != undefined ? result.data.order[20]['count'] : 0)],
                            [4, (result.data.order_success[20] != undefined ? result.data.order_success[20]['count'] : 0)],
                            [5, (result.data.order_fail[20] != undefined ? result.data.order_fail[20]['count'] : 0)]
                        ],
                        '21'    : [
                            [1, (user[21] != undefined ? user[21]['count'] : 0)],
                            [2, (result.data.user_order[21] != undefined ? result.data.user_order[21]['count'] : 0)],
                            [3, (result.data.order[21] != undefined ? result.data.order[21]['count'] : 0)],
                            [4, (result.data.order_success[21] != undefined ? result.data.order_success[21]['count'] : 0)],
                            [5, (result.data.order_fail[21] != undefined ? result.data.order_fail[21]['count'] : 0)]
                        ],
                        '22'    : [
                            [1, (user[22] != undefined ? user[22]['count'] : 0)],
                            [2, (result.data.user_order[22] != undefined ? result.data.user_order[22]['count'] : 0)],
                            [3, (result.data.order[22] != undefined ? result.data.order[22]['count'] : 0)],
                            [4, (result.data.order_success[22] != undefined ? result.data.order_success[22]['count'] : 0)],
                            [5, (result.data.order_fail[22] != undefined ? result.data.order_fail[22]['count'] : 0)]
                        ],
                        '23'    : [
                            [1, (user[23] != undefined ? user[23]['count'] : 0)],
                            [2, (result.data.user_order[23] != undefined ? result.data.user_order[23]['count'] : 0)],
                            [3, (result.data.order[23] != undefined ? result.data.order[23]['count'] : 0)],
                            [4, (result.data.order_success[23] != undefined ? result.data.order_success[23]['count'] : 0)],
                            [5, (result.data.order_fail[23] != undefined ? result.data.order_fail[23]['count'] : 0)]
                        ],
                        '24'    : [
                            [1, (user[24] != undefined ? user[24]['count'] : 0)],
                            [2, (result.data.user_order[24] != undefined ? result.data.user_order[24]['count'] : 0)],
                            [3, (result.data.order[24] != undefined ? result.data.order[24]['count'] : 0)],
                            [4, (result.data.order_success[24] != undefined ? result.data.order_success[24]['count'] : 0)],
                            [5, (result.data.order_fail[24] != undefined ? result.data.order_fail[24]['count'] : 0)]
                        ],
                        '25'    : [
                            [1, (user[25] != undefined ? user[25]['count'] : 0)],
                            [2, (result.data.user_order[25] != undefined ? result.data.user_order[25]['count'] : 0)],
                            [3, (result.data.order[25] != undefined ? result.data.order[25]['count'] : 0)],
                            [4, (result.data.order_success[25] != undefined ? result.data.order_success[25]['count'] : 0)],
                            [5, (result.data.order_fail[25] != undefined ? result.data.order_fail[25]['count'] : 0)]
                        ],
                        '26'    : [
                            [1, (user[26] != undefined ? user[26]['count'] : 0)],
                            [2, (result.data.user_order[26] != undefined ? result.data.user_order[26]['count'] : 0)],
                            [3, (result.data.order[26] != undefined ? result.data.order[26]['count'] : 0)],
                            [4, (result.data.order_success[26] != undefined ? result.data.order_success[26]['count'] : 0)],
                            [5, (result.data.order_fail[26] != undefined ? result.data.order_fail[26]['count'] : 0)]
                        ]
                    };
                    $scope.waiting = false;
                }
            });

            return;
        }

        $scope.getReport();

    }
]);
>>>>>>> 3ddd673854c271c38981f38c095b70c36f2ea515
