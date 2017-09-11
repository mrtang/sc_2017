'use strict';

angular.module('app').controller('ConfigCtrl', ['$scope', '$rootScope',  '$http', '$state', '$stateParams', '$window', 'Base',
 	function($scope, $rootScope,  $http, $state, $stateParams,  $window, Base) {
        // danh sách list_process
        $scope.list_process_2         = {};
        $scope.list_process_3         = {};
        $scope.list_process_4         = {};
        $scope.list_process_5         = {};
        $scope.list_process_10        = {};
        $scope.list_process_11        = {};
        $scope.list_process_12        = {};
        $scope.list_process_13        = {};

        $scope.list_status_2          = {};


        $scope.list_process_order   = {}; // trạng thái xử lý đơn hàng    1
        $scope.list_problem_order   = {}; // đơn hàng cần xử lý           5
        $scope.list_merchant        = {};
        $scope.list_merchant_vip    = {};
        $scope.list_address         = {};

        $scope.list_boxme_order     = {};
        $scope.list_boxme_uid       = {};
        $scope.list_boxme_shipment  = {};
        $scope.list_boxme_problem  = {};


        Base.PipeStatus('', '').then(function (result) {
            if(!result.data.error){
                angular.forEach(result.data.data, function(value) {
                   if(value['type'] == 1){ // đơn hàng
                       if($scope.list_process_order[+value.group_status] == undefined){
                           $scope.list_process_order[+value.group_status]  = [];
                       }
                       $scope.list_process_order[+value.group_status].push(value);
                   }else if(value['type'] == 2){
                       if($scope.list_merchant[+value.group_status] == undefined){
                           $scope.list_merchant[+value.group_status]  = [];
                       }
                       $scope.list_merchant[+value.group_status].push(value);


                       if($scope.list_status_2[+value.group_status] == undefined){
                           $scope.list_status_2[+value.group_status]    = [];
                       }
                       $scope.list_status_2[+value.group_status][+value.status] = value;
                   }else if(value['type'] == 3){
                       if($scope.list_merchant_vip[+value.group_status] == undefined){
                           $scope.list_merchant_vip[+value.group_status]  = [];
                       }
                       $scope.list_merchant_vip[+value.group_status].push(value);
                   }else if(value['type'] == 4){
                       if($scope.list_address[+value.group_status] == undefined){
                           $scope.list_address[+value.group_status]  = [];
                       }
                       $scope.list_address[+value.group_status].push(value);
                   }else if(value['type'] == 5){
                       if($scope.list_problem_order[+value.group_status] == undefined){
                           $scope.list_problem_order[+value.group_status]  = [];
                       }
                       $scope.list_problem_order[+value.group_status].push(value);
                   }else if(value['type'] == 10){
                       if($scope.list_boxme_order[+value.group_status] == undefined){
                           $scope.list_boxme_order[+value.group_status]  = [];
                       }
                       $scope.list_boxme_order[+value.group_status].push(value);
                   }else if(value['type'] == 11){
                       if($scope.list_boxme_uid[+value.group_status] == undefined){
                           $scope.list_boxme_uid[+value.group_status]  = [];
                       }
                       $scope.list_boxme_uid[+value.group_status].push(value);
                   }else if(value['type'] == 12){
                       if($scope.list_boxme_shipment[+value.group_status] == undefined){
                           $scope.list_boxme_shipment[+value.group_status]  = [];
                       }
                       $scope.list_boxme_shipment[+value.group_status].push(value);
                   }else if(value['type'] == 13){
                       if($scope.list_boxme_problem[+value.group_status] == undefined){
                           $scope.list_boxme_problem[+value.group_status]  = [];
                       }
                       $scope.list_boxme_problem[+value.group_status].push(value);
                   }
                });
            }
        });

        Base.getGroupProcess('', '').then(function (result) {
            if(!result.data.error){
                angular.forEach(result.data.data, function(value) {
                    if(value.type == 2){
                        if($scope.list_process_2[+value['code']] == undefined){
                            $scope.list_process_2[+value['code']]  = '';
                        }
                        $scope.list_process_2[+value['code']] = value.name;
                    }else if(value.type == 3){
                        if($scope.list_process_3[+value['code']] == undefined){
                            $scope.list_process_3[+value['code']]  = '';
                        }
                        $scope.list_process_3[+value['code']] = value.name;
                    }else if(value.type == 4){
                        if($scope.list_process_4[+value['code']] == undefined){
                            $scope.list_process_4[+value['code']]  = '';
                        }
                        $scope.list_process_4[+value['code']] = value.name;
                    }else if(value.type == 5){
                        if($scope.list_process_5[+value['code']] == undefined){
                            $scope.list_process_5[+value['code']]  = '';
                        }
                        $scope.list_process_5[+value['code']] = value.name;
                    }else if(value.type == 10){
                        if($scope.list_process_10[+value['code']] == undefined){
                            $scope.list_process_10[+value['code']]  = '';
                        }
                        $scope.list_process_10[+value['code']] = value.name;
                    }else if(value.type == 11){
                        if($scope.list_process_11[+value['code']] == undefined){
                            $scope.list_process_11[+value['code']]  = '';
                        }
                        $scope.list_process_11[+value['code']] = value.name;
                    }else if(value.type == 12){
                        if($scope.list_process_12[+value['code']] == undefined){
                            $scope.list_process_12[+value['code']]  = '';
                        }
                        $scope.list_process_12[+value['code']] = value.name;
                    }else if(value.type == 13){
                        if($scope.list_process_13[+value['code']] == undefined){
                            $scope.list_process_13[+value['code']]  = '';
                        }
                        $scope.list_process_13[+value['code']] = value.name;
                    }

                });
            }
        });
    }
]);