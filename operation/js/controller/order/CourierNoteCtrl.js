'use strict';

angular.module('app').controller('CourierNoteCtrl', ['$scope', '$http', '$state', '$window', '$modal', '$rootScope', 'toaster', 'Order', 'Config_Status', 'Base',
 	function($scope, $http, $state, $window, $modal, $rootScope, toaster, Order, Config_Status, Base) {

        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;
        $scope.total_group          = [];
        $scope.total_all            = 0;
        $scope.frm                  = {time_create : new Date(date.getFullYear(), date.getMonth(), 1)};
        $scope.list_data            = {};
        $scope.pipe_priority        = {};
        $scope.pipe_status          = {};
        $scope.list_pipe_status     = {};
        $scope.status_group         = {};
        $scope.waiting              = true;

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        Base.GroupStatus().then(function (result) {
            if(!result.data.error){
                $scope.group_order_status   = {};
                angular.forEach(result.data.list_group, function(value, key) {
                    $scope.group_status[value.id]   = value.name;
                    if(value.group_order_status){
                        angular.forEach(value.group_order_status, function(v) {
                            $scope.status_group[+v.order_status_code]    = v.group_status;

                            if($scope.group_order_status[+v.group_status] == undefined){
                                $scope.group_order_status[+v.group_status]  = [];
                            }
                            $scope.group_order_status[+v.group_status].push(+v.order_status_code);
                        });
                    }
                });
            }
        });




        Base.PipeStatus(null, 1).then(function (result) {
            if(!result.data.error){
                $scope.list_pipe_status      = result.data.data;
                angular.forEach(result.data.data, function(value) {
                    if(value.priority > $scope.pipe_limit){
                        $scope.pipe_limit   = +value.priority;
                    }
                    $scope.pipe_status[value.status]    = value.name;
                    $scope.pipe_priority[value.status]  = value.priority;
                });
            }
        });

        $scope.getPipeByGroup = function (group){
            var data = [];
            angular.forEach($scope.list_pipe_status, function (value, key){
                if(value.group_status == group){
                    data.push(value);
                }
            });
            return data;
        };

        $scope.setPage = function (page){
            $scope.currentPage = page;
            var frm = angular.copy($scope.frm);
            frm.time_create = new Date(frm.time_create).getTime() / 1000 ;
            frm.page = page;

            $scope.waiting              = true;
            $http.get(ApiPath + 'ticket-dashbroad/order-note', {params: frm}).success(function (resp){
                $scope.waiting              = false;
                if(!resp.error){
                    angular.forEach(resp.data, function (value, key){
                        value.active = value.active != 1;
                    });
                    $scope.list_data    = resp.data;
                    $scope.totalItems   = resp.total;
                }

            })
        };
        $scope.updateReaded = function (noteId, active){
            $http.post(ApiPath + 'ticket-dashbroad/update-note', {note_id: noteId, active: active}).success(function (resp){
                if(resp.error){
                    toaster.pop('warning', resp.error_message);
                    return;
                }
                toaster.pop('success', resp.error_message);
            })
        };
        $scope.setPage(1)



    }
]);
