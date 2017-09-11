'use strict';

//Ticket
angular.module('app').controller('RedeliveryCtrl', ['$scope', '$http', '$state', '$rootScope', 'PhpJs', 'Order', 'Config_Status', 'Config', '$modal', 'Location', '$timeout', 'city', 'district',
    function ($scope, $http, $state, $rootScope, PhpJs, Order, Config_Status, Config, $modal, Location, $timeout, city, district) {
        console.log(city, district);
        $scope.service          = Config.service;
        $scope.selectedCity     = city;
        $scope.selectedDistrict = district;

        $scope.frm = {
            from_city: $scope.selectedCity,
            from_district: $scope.selectedDistrict,
            to_city: "",
            to_district: ""
        };

        $scope.currentPage  = 1;
        $scope.item_page    = 20;
        $scope.maxSize      = 5;
        $scope.item_stt     = 0;
        $scope.total        = 0;
        $scope.loadingState = true;
        $scope.data         = [];

        $scope.list_color         = Config_Status.order_color;

        $scope.list_status        = {};
        $scope.group_status       = {};
        $scope.status_group       = {};
        $scope.group_order_status = {};

        $scope.pipe_status        = [];
        $scope.pipe_priority      = [];
        $scope.check_box          = [];

        $scope.toggleSelection = function (id) {
            var data = angular.copy($scope.check_box);
            var idx = +data.indexOf(id);

            if (idx > -1) {
                $scope.check_box.splice(idx, 1);
            }
            else {
                $scope.check_box.push(id);
            }
        };

        $scope.toggleSelectionAll = function (check) {
            var check_box = $scope.check_box;
            if (check == 0) {
                $scope.check_box = [];
            } else {
                $scope.check_box = [];
                angular.forEach($scope.data, function (value, key) {
                    $scope.check_box.push(value.id);
                });
            }
        };
        $scope.check_list = function (code) {
            var data = angular.copy($scope.check_box);
            var idx = +data.indexOf(code);
            if (idx > -1) {
                return true;
            }
            else {
                return false;
            }
        };


        Order.Status().then(function (result) {
            $scope.list_status = result.data;
        });

        Location.province('all').then(function (result){
            $scope.cities = result.data.data;
            $timeout(function (){
                $scope.frm.from_city    = parseInt($scope.selectedCity);
                $timeout(function (){
                    if($scope.selectedDistrict){
                        $scope.frm.from_district = parseInt($scope.selectedDistrict);
                    }

                }, 1500)
            }, 0)

        });

        Order.ListStatus(4).then(function (result) {
            if (!result.data.error) {
                $scope.group_order_status = {};
                angular.forEach(result.data.list_group, function (value, key) {
                    $scope.group_status[value.id] = value.name;
                    if (value.group_order_status) {
                        angular.forEach(value.group_order_status, function (v) {
                            $scope.status_group[+v.order_status_code] = v.group_status;

                            if ($scope.group_order_status[+v.group_status] == undefined) {
                                $scope.group_order_status[+v.group_status] = [];
                            }
                            $scope.group_order_status[+v.group_status].push(+v.order_status_code);
                        });
                    }
                });
            }
        });

        Order.PipeStatus(null, 1).then(function (result) {
            if (!result.data.error) {
                $scope.list_pipe_status = result.data.data;
                angular.forEach(result.data.data, function (value) {
                    if (value.priority > $scope.pipe_limit) {
                        $scope.pipe_limit = +value.priority;
                    }
                    $scope.pipe_status[value.status] = value.name;
                    $scope.pipe_priority[value.status] = value.priority;
                });
            }
        });


        $scope.loadPage = function ( page, cmd) {
            page = page ? page : 1;

            var url = ApiPath + 'ticket-dashbroad/re-delivery?limit=' + $scope.item_page + '&to_city_id=' + $scope.frm.to_city + '&to_district_id=' + $scope.frm.to_district +'&page=' + page + '&city_id=' + $scope.selectedCity + '&district_id=' + $scope.selectedDistrict;

            if($scope.frm.service){
                url += '&service=' + $scope.frm.service;
            }
            if($scope.frm.keyword){
                url += '&keyword=' + $scope.frm.keyword;
            }
            if($scope.frm.to_user){
                url += '&to_user=' + $scope.frm.to_user;
            }
            if($scope.frm.tracking_code){
                url += '&tracking_code=' + $scope.frm.tracking_code;
            }


            if (cmd == 'export') {
                url += '&cmd=export';
                window.location = url;
                return false;
            }

            //$scope.total        = 1110;
            $scope.loadingState = true;


            $http.get(url)
                .success(function (resp) {
                    $scope.loadingState = false;
                    if (!resp.error) {
                        $scope.total = resp.total;
                        $scope.data = resp.data;
                    }
                })
        };

        $scope.action   = function(item, list_pipe_status, step, type, group){
            var modalInstance = $modal.open({
                templateUrl: 'tpl/courier/partials/modal_add_journey.html',
                controller: 'ModalAddJourneyCtrl',
                resolve: {
                    items: function () {
                        return item;
                    },
                    pipe_status : function(){
                        return list_pipe_status;
                    },
                    step : function(){
                        return step;
                    },
                    type : function(){
                        return type;
                    },
                    group : function(){
                        return group;
                    }
                }
            });

            modalInstance.result.then(function (data) {
                if(data && data.multiple){
                    angular.forEach($scope.data, function (value, key){

                        if(type == 2){
                            value.pipe_status = 1 * group;
                        }else{
                            value.pipe_status = data.data.pipe_status;
                        }

                        value.pipe_journey.push({
                            user_id         : $rootScope.userInfo.id,
                            pipe_status     : 1 * data.data.pipe_status,
                            group_process   : 1 * group,
                            note            : data.data.note,
                            time_create     : Date.now()/1000
                        });

                    });

                }else if(data && !data.multiple){

                    if(type == 2){
                        item.pipe_status = 1 * group;
                    }else{
                        item.pipe_status = data.data.pipe_status;
                    }

                    item.pipe_journey.push({
                        user_id         : $rootScope.userInfo.id,
                        pipe_status     : 1 * data.data.pipe_status,
                        group_process   : 1 * group,
                        note            : data.data.note,
                        time_create     : Date.now()/1000
                    });

                }
            });
        };

        $scope.loadPage(1);
    }]);
