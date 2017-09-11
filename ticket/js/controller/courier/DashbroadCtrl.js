'use strict';

//Ticket
angular.module('app').controller('DashbroadCtrl', ['$scope', '$http', '$state', '$rootScope', 'PhpJs', 'Order', 'Config_Status', 'Config', '$modal', 'Location',
    function($scope, $http, $state, $rootScope, PhpJs, Order, Config_Status, Config, $modal, Location) {

        $scope.list_color        = Config_Status.order_color;

        $scope.loading = {
            slowdelivery : true,
            slowpickup   : true,
            redelivery   : true,
            ticket       : true,
            'return'     : true
        };

        $scope.currentPageReDelivery   = 1;
        $scope.currentPageSlowDelivery = 1;
        $scope.currentPageReturn       = 1;
        $scope.currentPageSlowPickup   = 1;
        $scope.item_page               = 20;

        $scope.list_status        = {};
        $scope.group_status       = {};
        $scope.status_group       = {};
        $scope.group_order_status = {};
        
        $scope.slowdelivery       = [];
        $scope.slowpickup         = [];
        $scope.ticket             = [];
        $scope['return']          = [];
        
        $scope.pipe_status        = [];
        $scope.pipe_priority      = [];
        
        
        $scope.slowdelivery_total = 0;
        $scope.redelivery_total   = 0;
        $scope.slowpickup_total   = 0;
        $scope.return_total       = 0;

        $scope.slowdelivery_total_done = 0;
        $scope.redelivery_total_done   = 0;
        $scope.slowpickup_total_done   = 0;
        $scope.return_total_done       = 0;



        $scope.cities   = [];
        $scope.district = [];
        $scope.selectedDistrict = 0;
        $scope.selectedCity     = 0;





        /*Order.Status().then(function (result) {
            $scope.list_status  = result.data;
        });*/

        /*Order.ListStatus(4).then(function (result) {
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
        });*/

        /*Order.PipeStatus(null, 1).then(function (result) {
            if(!result.data.error){
                $scope.list_pipe_status = result.data.data;
                angular.forEach(result.data.data, function(value) {
                    if(value.priority > $scope.pipe_limit){
                        $scope.pipe_limit   = +value.priority;
                    }
                    $scope.pipe_status[value.status]    = value.name;
                    $scope.pipe_priority[value.status]  = value.priority;
                });
            }
        });*/


        $scope.processInDay = {};


        $scope.loadProcessInDay = function (){
            var url = ApiPath + 'ticket-dashbroad/processed';
            $http.get(url)
                .success(function (resp){
                    if(!resp.error){

                        angular.forEach(resp.data, function (value, key){
                            $scope.processInDay[value.from_district_id] = value.total;
                        });

                        console.log($scope.processInDay);

                    }
                })
        };

        $scope.openModal = function (type, city, district){
            var template    = "",
                controller  = "";

            switch (type){
                case 'slowdelivery':
                    template ='tpl/courier/slowdelivery.html';
                    controller = 'SlowDeliveryCtrl';
                    break;
                case 'redelivery':
                    template ='tpl/courier/redelivery.html';
                    controller = 'RedeliveryCtrl';
                    break;
                case 'slowpickup':
                    template ='tpl/courier/slowpickup.html';
                    controller = 'SlowPickupCtrl';
                    break;
                case 'return':
                    template ='tpl/courier/return.html';
                    controller = 'ReturnCtrl';
                    break;
            }
            $modal.open({
                templateUrl: template,
                controller: controller,
                size: 'lg',
                resolve: {
                    city: function (){
                        return city;
                    },
                    district: function (){
                        return district;
                    }

                }
            });
        };

        

        $scope.loadSlowDelivery = function (city, district, cmd){

            var url = ApiPath + 'ticket-dashbroad/slow-delivery?limit='+$scope.item_page + '&city_id=' + city + '&district_id=' + district ;
            if(cmd == 'export'){
                url += '&cmd=export';
                window.location  = url;
                return false;
            }
            if(cmd == 'count'){
                url += '&cmd=count';
            }else {
                $scope.slowdelivery_total = 0;
                $scope.slowdelivery_total_done = 0;
            }



            $scope.loading.slowdelivery = true;
            $http.get(url)
            .success(function (resp){
                $scope.loading.slowdelivery = false;
                if(!resp.error){
                    $scope.slowdelivery_total = resp.total;
                    $scope.slowdelivery_total_done = resp.total_done;
                    $scope.slowdelivery = resp.data;
                }
            })
        };

        $scope.loadSlowPickup = function (city, district, cmd){
            var url = ApiPath + 'ticket-dashbroad/slow-pickup?limit='+$scope.item_page + '&city_id=' + city + '&district_id='+district;
            if(cmd == 'export'){
                url += '&cmd=export';
                window.location  = url;
                return false;
            }
            if(cmd == 'count'){
                url += '&cmd=count';
            }else {
                $scope.slowpickup_total   = 0;
                $scope.slowpickup_total_done   = 0;
            }



            $scope.loading.slowpickup = true;
            $http.get(url)
            .success(function (resp){
                $scope.loading.slowpickup = false;
                if(!resp.error){
                    $scope.slowpickup_total = resp.total;
                    $scope.slowpickup_total_done = resp.total_done;
                    $scope.slowpickup = resp.data;
                }
            })
        };

        $scope.loadReDelivery = function (city, district, cmd){

            var url = ApiPath + 'ticket-dashbroad/re-delivery?limit='+$scope.item_page+ '&city_id=' + city + '&district_id='+district ;
            if(cmd == 'export'){
                url += '&cmd=export';
                window.location  = url;
                return false;
            }
            if(cmd == 'count'){
                url += '&cmd=count';
            }else {
                $scope.redelivery_total   = 0;
                $scope.redelivery_total_done   = 0;
            }


            $scope.loading.redelivery = true;
            $http.get(url)
            .success(function (resp){
                $scope.loading.redelivery = false;
                if(!resp.error){
                    $scope.redelivery_total = resp.total;
                    $scope.redelivery_total_done = resp.total_done;

                    $scope.redelivery = resp.data;
                }
            })
        };

        $scope.loadReturn = function (city, district, cmd){
            var url = ApiPath + 'ticket-dashbroad/return?limit='+$scope.item_page + '&city_id=' + city + '&district_id='+district;

            if(cmd == 'export'){
                url += '&cmd=export';
                window.location  = url;
                return false;
            }
            if(cmd == 'count'){
                url += '&cmd=count';
            }else {
                $scope.return_total       = 0;
                $scope.return_total_done       = 0;
            }

            $scope.loading['return'] = true;
            $http.get(url)
            .success(function (resp){
                $scope.loading['return'] = false;
                if(!resp.error){
                    $scope.return_total = resp.total;
                    $scope.return_total_done = resp.total_done;

                    $scope['return']    = resp.data;
                }
            })
        };

        $scope.loadTicket = function (){
            $scope.loading.ticket = true;
            $http.get(ApiPath + 'ticket-dashbroad/ticket')
            .success(function (resp){
                $scope.loading.ticket = false;
                if(!resp.error){
                    $scope.ticket_total = resp.total;
                    $scope.ticket = resp.data;
                }
            })
        };

        $scope.action = function(item, list_pipe_status, step, type, group){
            var modalInstance = $modal.open({
                templateUrl: 'tpl/courier/modal_add_journey.html',
                controller: 'ModalAddCtrl',
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
                if(data) {
                    if(type == 2){
                        item.pipe_status = 1*group;
                    }else{
                        item.pipe_status = data.pipe_status;
                    }

                    item.pipe_journey.push({
                        user_id         : $rootScope.userInfo.id,
                        pipe_status     : 1*data.pipe_status,
                        group_process   : 1*group,
                        note            : data.note,
                        time_create     : Date.now()/1000
                    });
                }
            });
        };

        $scope.countByDistrict = function (district){
            $scope.selectedDistrict = district;
            $scope.loadReturn("", district, 'count');
            $scope.loadReDelivery("", district, 'count');
            $scope.loadSlowPickup("", district, 'count');
            $scope.loadSlowDelivery("", district, 'count');
        };

        $scope.countByCity = function (city){
            $scope.selectedCity = city;
            $scope.selectedDistrict = "";

            $scope.loadReturn(city, "", 'count');
            $scope.loadReDelivery(city, "", 'count');
            $scope.loadSlowPickup(city, "", 'count');
            $scope.loadSlowDelivery(city, "", 'count');
        };

        /*if($rootScope.userInfo.privilege == 4){
            $scope.countByCity($rootScope.userInfo.location_id);
        }else {
            $scope.countByDistrict($rootScope.userInfo.location_id);
        }*/


        Location.province('all').then(function (result){
            $scope.cities = result.data.data;
            $scope.loadDistrict($scope.cities[0].id);
        });

        $scope.loadDistrict = function (city_id){
            $scope.countByCity(city_id);
            Location.district(city_id, 'all').then(function (result){
                $scope.district = result.data.data;
            });
        };

        $scope.loadProcessInDay();


}]);
