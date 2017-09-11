'use strict';

angular.module('app').controller('DeliverySlowNTCtrl', ['$scope', '$rootScope', 'Order', 'Config_Status', 'Base',
    function($scope, $rootScope, Order, Config_Status, Base) {

        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;
        $scope.total_group          = [];
        $scope.total_all            = 0;
        $scope.time                 = {accept_start : new Date(date.getFullYear(), date.getMonth(), 1)};

        $scope.frm                  = {group : 103, location: 1, type_process : 5, from_city:18, to_city: 18, type_noithanh: 0};
        //$scope.frm                  = {group : 103, delivery_slow: "1", vip : 0, type_process : 5, location : 0, num_slow : 0};

        $scope.list_data            = {};
        $scope.list_location        = {'list_city': {},'list_district': {},'list_ward': {},'list_to_address': {},'list_from_address': {}};
        $scope.list_post_office     = {};

        $scope.list_color           = Config_Status.order_color;
        $scope.list_pipe_status     = {};
        $scope.pipe_status          = {};
        $scope.pipe_limit           = 0;
        $scope.pipe_priority        = {};
        $scope.check_box            = [];
        $scope.check_box_status     = [];
        $scope.tab                  = 27;
        $scope.pipe_journey         = {};
        $scope.waiting_export       = false;

        $scope.list_reponse         = {};
        $scope.list_color           = Config_Status.order_color;
        $scope.tag_color            = Config_Status.tag_color;

        if($scope.group_order_status[$scope.tab] == undefined){
            $scope.group_order_status[$scope.tab]   = Config_Status.group_status[$scope.tab];
        }
        $scope.tab_status           = $scope.group_order_status[$scope.tab].toString();

        $scope.waiting              = false;
        $scope.totalItems           = 0;



        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "time_accept_start_open"){
                $scope.time_accept_start_open = true;
            }else if(type == "time_accept_end"){
                $scope.time_accept_end_open = true;
            }
        };

        Base.PipeStatus(28, 1).then(function (result) {
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

        $scope.$watch('frm.from_city', function (newVal){
            if(newVal !== null || newVal !== undefined){
                $scope.frm.to_city = newVal;
            }
        })

        $scope.toggleSelection = function(id) {
            var data = angular.copy($scope.check_box);
            var idx = +data.indexOf(id);

            if (idx > -1) {
                $scope.check_box.splice(idx, 1);
            }
            else {
                $scope.check_box.push(id);
            }
        };

        $scope.toggleSelectionStatus = function(id) {
            var data = angular.copy($scope.check_box_status);
            var idx = +data.indexOf(id);

            if (idx > -1) {
                $scope.check_box_status.splice(idx, 1);
            }
            else {
                $scope.check_box_status.push(id);
            }
        };


        $scope.refresh = function(cmd){
            if($scope.time.accept_start != undefined && $scope.time.accept_start != ''){
                $scope.frm.accept_start    = +Date.parse($scope.time.accept_start)/1000;
            }else{
                $scope.frm.accept_start    = 0;
            }

            if($scope.time.accept_end != undefined && $scope.time.accept_end != ''){
                $scope.frm.accept_end      = +Date.parse($scope.time.accept_end)/1000 + 86399;
            }else{
                $scope.frm.accept_end      = 0;
            }

            if($scope.time.pickup_start != undefined && $scope.time.pickup_start != ''){
                $scope.frm.pickup_start   = +Date.parse($scope.time.pickup_start)/1000;
            }else{
                $scope.frm.pickup_start   = 0;
            }
            if($scope.time.pickup_end != undefined && $scope.time.pickup_end != ''){
                $scope.frm.pickup_end     = +Date.parse($scope.time.pickup_end)/1000 + 86399;
            }else{
                $scope.frm.pickup_end     = 0;
            }

            if($scope.time.success_start != undefined && $scope.time.success_start != ''){
                $scope.frm.success_start   = +Date.parse($scope.time.success_start)/1000;
            }else{
                $scope.frm.success_start   = 0;
            }
            if($scope.time.success_end != undefined && $scope.time.success_end != ''){
                $scope.frm.success_end     = +Date.parse($scope.time.success_end)/1000 + 86399;
            }else{
                $scope.frm.success_end     = 0;
            }

            if($scope.check_box_status != undefined && $scope.check_box_status.length > 0){
                $scope.tab_status           = $scope.check_box_status.toString();
            }else{
                $scope.tab_status           = $scope.group_order_status[$scope.tab].toString();
            }

            if($scope.check_box != undefined && $scope.check_box.length > 0){
                $scope.frm.pipe_status      = $scope.check_box.toString();
            }else{
                $scope.frm.pipe_status  = '';
            }

            if(cmd != 'export'){
                $scope.list_data            = {};
                $scope.list_location        = {'list_city': {},'list_district': {},'list_ward': {},'list_to_address': {},'list_from_address': {}};
                $scope.list_post_office     = {};
                $scope.waiting              = true;
            }

        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.refresh('');
            Order.ListOrder($scope.currentPage,$scope.frm, $scope.tab_status, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                    $scope.list_location.list_city              = result.data.list_city;
                    $scope.list_location.list_district          = result.data.list_district;
                    $scope.list_location.list_ward              = result.data.list_ward;

                    $scope.list_location.list_to_address        = result.data.list_to_address;
                    $scope.list_location.list_from_address      = result.data.list_from_address;
                    $scope.list_post_office                     = result.data.list_postoffice;
                }
                $scope.waiting  = false;
            });
            return;
        }

        $scope.setCountGroup    = function(){
            $scope.total_all    = 0;
            $scope.total_group  = [];
            $scope.list_reponse = {};
            Order.CountGroup($scope.frm, $scope.tab_status, 'status').then(function (result) {
                if(!result.data.error){
                    $scope.total_all        = result.data.total;
                    $scope.total_group      = result.data.data;
                }
            });
        }

        $scope.getReponse   = function(item){
            item.waiting = true;
            if($scope.list_reponse[item.id] == undefined || $scope.list_reponse[item.id].length == 0){
                Order.StatusOrder(item.id, 67).then(function (result) {
                    if(!result.data.error){
                        $scope.list_reponse[item.id]    = '';
                        angular.forEach(result.data.data, function(value) {
                            $scope.list_reponse[item.id] += ', '+value.note
                        });

                        if($scope.list_reponse[item.id] != ''){
                            $scope.list_reponse[item.id]    = $scope.list_reponse[item.id].substr(2);
                        }else{
                            $scope.list_reponse[item.id]    = 'Không có dữ liệu !';
                        }

                        item.waiting = false;
                    }
                });
            }else{
                item.waiting = false;
            }
        }

        $scope.ChangeTab    = function(tab){
            $scope.tab              = tab;
            $scope.check_box_status = [];
            if(tab == 'ALL'){
                $scope.tab_status  = [];
            }else{
                $scope.tab_status   = $scope.group_order_status[tab].toString();
            }
            $scope.setPage(1);
        }

        //$scope.setPage(1);
        //$scope.setCountGroup();

        $scope.exportExcel = function(){
            $scope.refresh('export');
            return Order.ListOrder(1,$scope.frm,$scope.tab_status,'export');
        }
    }
]);
